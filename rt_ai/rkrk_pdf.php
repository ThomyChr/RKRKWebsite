<?php
declare(strict_types=1);

require __DIR__ . '/dompdf/vendor/autoload.php';
require_once __DIR__ . '/qr_svg.php'; // liegt im selben Ordner

use Dompdf\Dompdf;
use Dompdf\Options;

/* ----------------------------
   Debug optional: ?debug=1
----------------------------- */
$debug = (PHP_SAPI !== 'cli') && isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

/* ----------------------------
   Helpers
----------------------------- */
function getv($src, array $path, $default = null) {
    foreach ($path as $k) {
        if (!is_array($src) || !array_key_exists($k, $src)) return $default;
        $src = $src[$k];
    }
    return $src;
}
function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function text($v): string {
    return html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function chf($n): string {
    if ($n === null || $n === '') return '';
    return number_format((float)$n, 2, '.', '’');
}
function date_de_short($iso): string {
    if (!$iso) return '';
    $ts = strtotime((string)$iso);
    if (!$ts) return (string)$iso;

    $m = (int)date('n', $ts);
    $mons = [1=>'Jan.',2=>'Feb.',3=>'März',4=>'Apr.',5=>'Mai',6=>'Juni',7=>'Juli',8=>'Aug.',9=>'Sep.',10=>'Okt.',11=>'Nov.',12=>'Dez.'];
    $mon = $mons[$m] ?? date('M', $ts);
    return date('j', $ts) . '. ' . $mon . ' ' . date('Y', $ts);
}
function img_data_uri(?string $path): ?string {
    if (!$path || !is_file($path)) return null;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = 'application/octet-stream';
    if ($ext === 'png') $mime = 'image/png';
    elseif ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
    elseif ($ext === 'gif') $mime = 'image/gif';
    elseif ($ext === 'svg') $mime = 'image/svg+xml';

    $data = base64_encode((string)file_get_contents($path));
    return "data:$mime;base64,$data";
}

/** Nur assoziatives konto-Objekt (nicht JSON-Array []) */
function defaults_konto_assoc(array $defaults): array {
    $k = $defaults['konto'] ?? null;
    if (!is_array($k) || $k === []) {
        return [];
    }
    $keys = array_keys($k);
    if ($keys === range(0, count($k) - 1)) {
        return [];
    }
    return $k;
}

/** Zahlungsdaten: Root-konto + kategorien[kat].finanz (finanz überschreibt) */
function merged_finanz(array $defaults, array $cat): array {
    return array_merge(defaults_konto_assoc($defaults), isset($cat['finanz']) && is_array($cat['finanz']) ? $cat['finanz'] : []);
}

function format_iban_ch(string $iban): string {
    $iban = strtoupper(preg_replace('/\s+/', '', $iban));
    if ($iban === '') {
        return '';
    }
    return trim(chunk_split($iban, 4, ' '));
}

/** signaturen-Eintrag zu absender.vorname + ' ' + absender.nachname (Key oder Felder vorname/name) */
function resolve_signature_entry_for_absender(array $defaults, array $data): ?array {
    $absV = trim((string) getv($data, ['absender', 'vorname'], ''));
    $absN = trim((string) getv($data, ['absender', 'nachname'], ''));
    $full = trim($absV . ' ' . $absN);
    if ($full === '') {
        return null;
    }
    $sigs = isset($defaults['signaturen']) && is_array($defaults['signaturen']) ? $defaults['signaturen'] : [];
    if (isset($sigs[$full]) && is_array($sigs[$full])) {
        return $sigs[$full];
    }
    $low = function_exists('mb_strtolower') ? mb_strtolower($full, 'UTF-8') : strtolower($full);
    foreach ($sigs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $vn = trim((string) ($row['vorname'] ?? ''));
        $nn = trim((string) ($row['name'] ?? $row['nachname'] ?? ''));
        $cand = trim($vn . ' ' . $nn);
        if ($cand === '') {
            continue;
        }
        $lowCand = function_exists('mb_strtolower') ? mb_strtolower($cand, 'UTF-8') : strtolower($cand);
        if ($lowCand === $low) {
            return $row;
        }
    }
    return null;
}

function signature_bild_path(string $rel): string {
    $rel = str_replace('\\', '/', ltrim($rel, '/'));
    if ($rel === '') {
        return '';
    }
    $cand = __DIR__ . '/' . $rel;
    if (is_file($cand)) {
        return $cand;
    }
    $bn = basename($rel);
    foreach (['images', 'assets'] as $sub) {
        $try = __DIR__ . '/' . $sub . '/' . $bn;
        if (is_file($try)) {
            return $try;
        }
    }
    return '';
}

/* ----------------------------
   ID lesen
----------------------------- */
$id = '';
if (PHP_SAPI === 'cli') {
    global $argv;
    $id = isset($argv[1]) ? (string)$argv[1] : '';
} else {
    $id = isset($_GET['id']) ? (string)$_GET['id'] : '';
}
$id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
if ($id === '') {
    http_response_code(400);
    echo "Fehler: ID fehlt. Nutzung: ?id=RE2026IN0001 (optional &debug=1)";
    exit;
}

/* ----------------------------
   invoice_default.json laden (wie rkrk_rt.php: data/invoice_default.json)
----------------------------- */
$defaultsFile = __DIR__ . '/data/invoice_default.json';
$defaults = [];
if (is_file($defaultsFile)) {
    $defaults = json_decode((string)file_get_contents($defaultsFile), true);
    if (!is_array($defaults)) $defaults = [];
}
require_once __DIR__ . '/rkrk_category.php';

/* ----------------------------
   Rechnungs-JSON laden
----------------------------- */
$jsonFile = __DIR__ . "/data/invoices/{$id}.json";
if (!is_file($jsonFile)) {
    http_response_code(404);
    echo "Fehler: JSON nicht gefunden: {$jsonFile}";
    exit;
}
$data = json_decode((string)file_get_contents($jsonFile), true);
if (!is_array($data)) {
    http_response_code(500);
    echo "Fehler: JSON ungültig: {$jsonFile}";
    exit;
}

/* ----------------------------
   Sicherheitscheck
----------------------------- */
if (!function_exists('qr_svg')) {
    throw new RuntimeException("qr_svg() fehlt – qr_svg.php wurde nicht korrekt geladen.");
}

/* ----------------------------
   Daten aus JSON
----------------------------- */
$invoiceId = (string)getv($data, ['id'], $id);
$kategorieRaw = (string)getv($data, ['kategorie'], '');
$KATEGORIEN = isset($defaults['kategorien']) && is_array($defaults['kategorien']) ? $defaults['kategorien'] : [];
$kategorie = resolve_kategorie_key($kategorieRaw, $invoiceId, $KATEGORIEN);

$kunde = (array)getv($data, ['kunde_details'], []);
$anrede   = getv($kunde, ['anrede']);
$vorname  = getv($kunde, ['vorname']);
$nachname = getv($kunde, ['nachname']);
$adresse  = getv($kunde, ['adresse']);
$plz      = getv($kunde, ['plz']);
$ortK     = getv($kunde, ['ort']);
$land     = getv($kunde, ['land']);

$ortTitleRaw = getv($data, ['ort']);
$ortTitle = $ortTitleRaw !== null ? text($ortTitleRaw) : null;

$datumRaw = getv($data, ['datum']);
$datum = $datumRaw ? date_de_short((string)$datumRaw) : null;

$einleitungRaw = getv($data, ['einleitung']);
$einleitung = $einleitungRaw !== null ? text($einleitungRaw) : null;

$abschlussRaw = getv($data, ['abschluss']);
$abschluss = $abschlussRaw !== null ? text($abschlussRaw) : null;

$grussRaw = getv($data, ['gruss']);
$gruss = $grussRaw !== null ? text($grussRaw) : null;

$zwischensumme = getv($data, ['zwischensumme']);
$rabattCHF     = getv($data, ['rabattCHF']);
$rabattProz    = getv($data, ['rabattProzent']);
$total         = getv($data, ['total']);

$currency = (string)getv($data, ['currency'], 'CHF');

/* ----------------------------
   Empfängerblock
----------------------------- */
$kundeBlock = [];
$nameLine = trim(trim((string)$vorname . ' ' . (string)$nachname));
if ($nameLine !== '') $kundeBlock[] = $nameLine;
if ($adresse) $kundeBlock[] = (string)$adresse;
$cityLine = trim(trim((string)$plz . ' ' . (string)$ortK));
if ($cityLine !== '') $kundeBlock[] = $cityLine;
if ($land) $kundeBlock[] = (string)$land;
$kundeBlockText = implode("\n", $kundeBlock);

$anredeZeile = trim(trim((string)$anrede . ' ' . (string)$nachname));
$anredeZeile = $anredeZeile !== '' ? $anredeZeile : null;

/* ----------------------------
   Kategorie + Zahlungsstammdaten (konto + kategorien[kat].finanz)
----------------------------- */
$cat = category_conf_for_code($kategorie, $KATEGORIEN);
$finanz = merged_finanz($defaults, $cat);
$pdfLayout = isset($defaults['pdf_layout']) && is_array($defaults['pdf_layout']) ? $defaults['pdf_layout'] : [];
$catDesign = category_design($kategorie, $KATEGORIEN, $defaults);

$stylesetsAll = isset($defaults['stylesets']) && is_array($defaults['stylesets']) ? $defaults['stylesets'] : [];
$styleSetKey = trim((string) ($cat['styleset'] ?? ''));
$catBorderColor = '#000000';
if ($styleSetKey !== '' && isset($stylesetsAll[$styleSetKey]['border']) && (string) $stylesetsAll[$styleSetKey]['border'] !== '') {
    $catBorderColor = (string) $stylesetsAll[$styleSetKey]['border'];
}

$ibanDefault = (string)($finanz['iban'] ?? '');
$iban = (string)getv($data, ['bank', 'iban'], $ibanDefault);
$empfaengerDefault = (string)($finanz['empfaenger'] ?? '');
$strasseDefault = (string)($finanz['strasse'] ?? '');
$plzDefault = (string)($finanz['plz'] ?? '');
$ortDefault = (string)($finanz['ort'] ?? '');
$landDefault = (string)($finanz['land'] ?? '');

$bankName = trim((string)($finanz['bank'] ?? $finanz['finanzinstitut'] ?? $pdfLayout['finanzinstitut_name'] ?? ''));
$bankOrt = trim((string)($finanz['bank_ort'] ?? $finanz['bank_plz_ort'] ?? $pdfLayout['finanzinstitut_ort'] ?? ''));
$bic = trim((string)($finanz['bic'] ?? $finanz['bic_swift'] ?? $pdfLayout['bic'] ?? ''));
$ibanFormatted = format_iban_ch($iban);

$absenderBankLines = [];
if ($bankName !== '' || $bankOrt !== '') {
    $absenderBankLines[] = 'Finanzinstitut:';
    if ($bankName !== '') {
        $absenderBankLines[] = $bankName;
    }
    if ($bankOrt !== '') {
        $absenderBankLines[] = $bankOrt;
    }
}
if ($bic !== '' || $ibanFormatted !== '') {
    $absenderBankLines[] = 'BIC/SWIFT:';
    if ($bic !== '') {
        $absenderBankLines[] = $bic;
    }
    if ($ibanFormatted !== '') {
        $absenderBankLines[] = $ibanFormatted;
    }
}
$absenderBankText = implode("\n", $absenderBankLines);

/** Im PDF-Kopf rechts unter dem Logo: nur Name des Kontobesitzers (Zahlungsempfänger), keine Adresszeilen */
$kontobesitzerNameTop = trim($empfaengerDefault);

$tagline1 = trim((string)($pdfLayout['tagline_line1'] ?? $pdfLayout['tagline_1'] ?? ''));
$tagline2 = trim((string)($pdfLayout['tagline_line2'] ?? $pdfLayout['tagline_2'] ?? ''));

$betreff = trim((string)getv($data, ['betreff'], ''));
$rechnungsOrt = $ortDefault;
$dateLinePrinted = ($datum !== null && $datum !== '') ? (($rechnungsOrt !== '' ? $rechnungsOrt . ', ' : '') . $datum) : '';

/* ----------------------------
   Gruss
----------------------------- */
$defaultGruss = isset($finanz['zusatzinfo']) ? $finanz['zusatzinfo'] : null;
if ($defaultGruss === null || $defaultGruss === '') {
    $rootK = defaults_konto_assoc($defaults);
    $defaultGruss = getv($rootK, ['zusatzinfo']);
}
$grussText = $gruss ?: ($defaultGruss ? text((string)$defaultGruss) : null);

/* ----------------------------
   Signatur (zu absender.vorname/nachname aus signaturen)
----------------------------- */
$sigEntry = resolve_signature_entry_for_absender($defaults, $data);
$absSigRel = $sigEntry ? trim((string) ($sigEntry['bild'] ?? '')) : '';
$absSigPath = $absSigRel !== '' ? signature_bild_path($absSigRel) : '';
$absSigSrc = ($absSigPath !== '' && is_file($absSigPath)) ? img_data_uri($absSigPath) : null;
$sigPosition = $sigEntry ? trim((string) ($sigEntry['position'] ?? '')) : '';
$sigBezeichnung = $sigEntry ? trim((string) ($sigEntry['bezeichnung'] ?? '')) : '';

/* ----------------------------
   Logo (Kategorie → logosets / kategorien wie invoice_default.json)
----------------------------- */
$logoRel = (string)($catDesign['logo'] ?? '');
$logoPath = '';
if ($logoRel !== '') {
    if (!preg_match('#^https?://#i', $logoRel)) {
        $rel = str_replace('\\', '/', ltrim($logoRel, '/'));
        $cand = __DIR__ . '/' . $rel;
        if (is_file($cand)) {
            $logoPath = $cand;
        } else {
            $bn = basename($rel);
            foreach (['images', 'assets'] as $sub) {
                $try = __DIR__ . '/' . $sub . '/' . $bn;
                if (is_file($try)) {
                    $logoPath = $try;
                    break;
                }
            }
        }
    }
}
if ($logoPath === '') {
    $senderLogo = getv($data, ['sender', 'logo'], '');
    if ($senderLogo !== null && $senderLogo !== '') {
        $sl = __DIR__ . '/' . ltrim(str_replace('\\', '/', (string)$senderLogo), '/');
        if (is_file($sl)) {
            $logoPath = $sl;
        }
    }
}
if ($logoPath === '' && is_file(__DIR__ . '/assets/logo.png')) {
    $logoPath = __DIR__ . '/assets/logo.png';
}
$logoSrc = img_data_uri($logoPath);

/* ----------------------------
   Positionen (Sektionen) — vor Betragsberechnung
   Defaults: invoice_default.json → pdf_settings; Rechnungs-JSON kann überschreiben (Root oder pdf_settings).
   pdf_sektionen (true): Sektionstitel + Sektions-Zwischensummen; false: nur Rechnungstotal (keine Tabelle).
   pdf_positionen_sektionen (true): Positionszeilen; false: nur Titel + Sektionstotale.
   pdf_details_positionen (true): Bemerkung inline + Spalte Menge×Preis; false: ausgeblendet.
----------------------------- */
$pdfCfg = isset($defaults['pdf_settings']) && is_array($defaults['pdf_settings']) ? $defaults['pdf_settings'] : [];
$dataPdf = isset($data['pdf_settings']) && is_array($data['pdf_settings']) ? $data['pdf_settings'] : [];
$pdfBool = static function (string $key, bool $def) use ($data, $dataPdf, $pdfCfg): bool {
    if (array_key_exists($key, $data)) {
        return (bool) $data[$key];
    }
    if (array_key_exists($key, $dataPdf)) {
        return (bool) $dataPdf[$key];
    }
    if (array_key_exists($key, $pdfCfg)) {
        return (bool) $pdfCfg[$key];
    }
    return $def;
};
$showPdfSektionen = $pdfBool('pdf_sektionen', true);
$showPdfPositionen = $pdfBool('pdf_positionen_sektionen', true);
$showPdfDetails = $pdfBool('pdf_details_positionen', true);

$lines = [];
$sections = getv($data, ['sections'], []);

if ($showPdfSektionen && is_array($sections)) {
    foreach ($sections as $sec) {

        $title = trim((string) getv($sec, ['title'], ''));

        if ($title !== '') {
            $lines[] = [
                'label' => '###SECTION_TITLE###',
                'title' => $title
            ];
        }

        $secLines = getv($sec, ['lines'], []);
        $sectionSum = 0.0;

        if (is_array($secLines)) {
            foreach ($secLines as $l) {
                if (!is_array($l)) {
                    continue;
                }

                $ltotal = getv($l, ['total']);
                if ($ltotal !== null && $ltotal !== '') {
                    $sectionSum += (float) $ltotal;
                }

                if ($showPdfPositionen) {
                    $lines[] = $l;
                }
            }
        }

        if ($sectionSum > 0) {
            $lines[] = [
                'label' => '###SECTION_SUM###',
                'sum'   => $sectionSum
            ];
        }
    }
}

/* ----------------------------
   Betrag
----------------------------- */
$amountVal = ($total !== null && $total !== '') ? (float) $total : null;
if ($amountVal === null) {
    $sumDetail = 0.0;
    foreach ($lines as $l) {
        if (!is_array($l)) {
            continue;
        }
        $lab = getv($l, ['label']);
        if ($lab === '###SECTION_TITLE###' || $lab === '###SECTION_SUM###') {
            continue;
        }
        if (is_string($lab) && strtolower(trim($lab)) === 'risiko') {
            continue;
        }
        $lt = getv($l, ['total']);
        if ($lt !== null && $lt !== '') {
            $sumDetail += (float) $lt;
        }
    }
    if ($sumDetail > 0) {
        $amountVal = $sumDetail;
    } else {
        $sumRaw = 0.0;
        $sectionsAll = getv($data, ['sections'], []);
        if (is_array($sectionsAll)) {
            foreach ($sectionsAll as $sec) {
                foreach (getv($sec, ['lines'], []) as $l) {
                    if (!is_array($l)) {
                        continue;
                    }
                    $lab = getv($l, ['label']);
                    if (is_string($lab) && strtolower(trim($lab)) === 'risiko') {
                        continue;
                    }
                    $lt = getv($l, ['total']);
                    if ($lt !== null && $lt !== '') {
                        $sumRaw += (float) $lt;
                    }
                }
            }
        }
        $amountVal = $sumRaw;
    }
}
$amountIso = number_format((float)$amountVal, 2, '.', '');

/* ----------------------------
   Swiss-QR (Einzahlungsschein) — dynamisch aus Betrag/Kundenadresse
----------------------------- */
$catInfo = trim((string)($cat['zusatzinfo'] ?? $cat['Zusatzinformation'] ?? ''));

$qrIban = str_replace(' ', '', (string)($iban ?: $ibanDefault));
$crName = (string)($empfaengerDefault ?: '');
$crStreet = (string)($strasseDefault ?: '');
$crZip = (string)($plzDefault ?: '');
$crCity = (string)($ortDefault ?: '');
$crCountry = 'CH';

$dbName = $nameLine;
$dbStreet = (string)($adresse ?: '');
$dbZip = (string)($plz ?: '');
$dbCity = (string)($ortK ?: '');
$dbCountry = 'CH';

$additionalInfo = $catInfo !== '' ? $catInfo : ($ortTitle ?: (trim($betreff) !== '' ? $betreff : ('Rechnung ' . $invoiceId)));
if (strlen($additionalInfo) > 140) {
    $additionalInfo = substr($additionalInfo, 0, 137) . '...';
}

$qrString =
    "SPC\n0200\n1\n" .
    $qrIban . "\n" .
    "K\n" .
    $crName . "\n" .
    $crStreet . "\n" .
    $crZip . "\n" .
    $crCity . "\n" .
    $crCountry . "\n" .
    "K\n" .
    $dbName . "\n" .
    $dbStreet . "\n" .
    $dbZip . "\n" .
    $dbCity . "\n" .
    $dbCountry . "\n" .
    $amountIso . "\n" .
    $currency . "\n" .
    "NON\n" .
    "\n" .
    $additionalInfo . "\n";

$qrModuleColor = trim((string) ($catDesign['color'] ?? '#000000'));
if ($qrModuleColor === '') {
    $qrModuleColor = '#000000';
}
$qrSvg = qr_svg($qrString, $qrModuleColor);

$slipIbanLine = $ibanFormatted !== '' ? $ibanFormatted : format_iban_ch((string) $qrIban);
$slipCredName = $crName;
$slipCredAddr = trim($crStreet . "\n" . trim($crZip . ' ' . $crCity));
$slipAmtFmt = chf($amountVal);

$hasAbsCol = ($absenderBankText !== '' || $kontobesitzerNameTop !== '');
$hasClosingSign = ($absSigSrc || $sigPosition !== '' || $sigBezeichnung !== '');
$absColColor = h($catDesign['color']);

/* ----------------------------
   HTML
----------------------------- */
ob_start();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title><?= h($invoiceId) ?></title>
<style>
  @page { margin: 18mm 16mm 16mm 16mm; }
  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.45;
    color: #000;
  }

  /* 5,5 cm Empfänger ab Blattoberkante: @page top 18mm → 55mm − 18mm = 37mm Kopfzone */
  .headerSlot {
    position: relative;
    height: 37mm;
    margin: 0;
    padding: 0;
  }
  .pdfHeader {
    display: table;
    width: 100%;
    table-layout: fixed;
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
  }
  .headerMain { display: table-cell; vertical-align: top; width: 62%; padding-right: 4mm; }
  .pdfHeader .logoCell {
    display: table-cell;
    vertical-align: top;
    width: 38%;
    text-align: right;
  }
  .pdfHeader .logoCell img { max-width: 34mm; max-height: 22mm; object-fit: contain; display: block; margin-left: auto; }
  .absenderUnderLogo {
    margin-top: 1.5mm;
    text-align: right;
    font-size: 8px;
    line-height: 1;
    white-space: pre-line;
  }
  .closingSign {
    margin-top: 8mm;
    text-align: left;
    font-size: 11px;
    line-height: 1.25;
  }
  .closingSign .absSigImg { width: 40mm; max-height: 22mm; object-fit: contain; display: block; margin: 0 0 2mm 0; }

  .tagline { font-size: 10px; font-weight: 700; line-height: 1.35; margin: 0; }
  .pdfHl { font-weight: 700; margin-top: 2mm; }

  .recipient { white-space: pre-line; margin-top: 0; font-size: 11px; line-height: 1.45; }
  .dateLine { text-align:right; margin-top: 5mm; font-size: 11px; }

  .subject { margin-top: 5mm; font-weight:700; font-size: 14px; }
  .subject .subtitle { font-weight:400; font-size: 11px; margin-top: 1mm; display:block; }

  .salutation { margin-top: 5mm; }

  table.pos { width:100%; border-collapse: collapse; margin-top: 5mm; font-size: 11px; line-height: 1; }
  table.pos td { padding: 1.2mm 0; vertical-align: top; font-size: 11px; line-height: 1; }
  td.p1 { width: 56%; font-weight: 400; white-space: pre-line; }
  td.p2 { width: 24%; text-align:right; white-space: nowrap; }
  td.p3 { width: 20%; text-align:right; white-space: nowrap; font-weight: 700; }

  .section-sum td { font-size: 11px; line-height: 1; }

  .sumBlock { margin-top: 6mm; }
  .sumLine { display:block; margin-top: 1mm; }
  .sumLine strong { font-weight: 700; }

  .block { margin-top: 4mm; }
  .sign { margin-top: 10mm; }

  .page-break { page-break-before: always; }

  .slip-bleed { position: relative; width: 210mm; height: 297mm; margin: -18mm -16mm -16mm -16mm; padding: 0; box-sizing: border-box; font-size: 8px; line-height: 1; color: #000; }
  .slip-abs { position: absolute; margin: 0; padding: 0; white-space: pre-line; }
  .slip-lbl { font-weight: 700; }
</style>
</head>
<body>

<!-- ========================= SEITE 1 (Vorlage: Kopf + Adresse + Betreff + Positionen) ========================= -->
<div class="headerSlot">
<div class="pdfHeader">
  <div class="headerMain">
    <?php if ($tagline1 !== '' || $tagline2 !== ''): ?>
      <div class="tagline">
        <?php if ($tagline1 !== ''): ?><?= h($tagline1) ?><?php if ($tagline2 !== ''): ?><br><?php endif; ?><?php endif; ?>
        <?php if ($tagline2 !== ''): ?><?= h($tagline2) ?><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="logoCell">
    <?php if ($logoSrc): ?>
      <img src="<?= h($logoSrc) ?>" alt="Logo">
    <?php endif; ?>
    <?php if ($hasAbsCol): ?>
      <div class="absenderUnderLogo" style="color: <?= $absColColor ?>;">
        <?php if ($absenderBankText !== ''): ?>
          <div><?= h($absenderBankText) ?></div>
        <?php endif; ?>
        <?php if ($kontobesitzerNameTop !== ''): ?>
          <div><?= h($kontobesitzerNameTop) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</div>

<?php if ($kundeBlockText): ?>
  <div class="recipient"><?= h($kundeBlockText) ?></div>
<?php endif; ?>

<?php if ($dateLinePrinted !== ''): ?>
  <div class="dateLine"><?= h($dateLinePrinted) ?></div>
<?php elseif ($datum): ?>
  <div class="dateLine"><?= h($datum) ?></div>
<?php endif; ?>

<div class="subject" style="color: <?= h($catDesign['color']) ?>;">
  <?= $betreff !== '' ? h($betreff) : h('Rechnung') ?>
  <?php if ($ortTitle): ?>
    <span class="subtitle" style="color: <?= h($catDesign['color']) ?>;"><?= h($ortTitle) ?></span>
  <?php endif; ?>
</div>

<?php if ($anredeZeile): ?>
  <div class="salutation"><?= h($anredeZeile) ?></div>
<?php endif; ?>

<?php if ($einleitung): ?>
  <div class="block"><?= nl2br(h($einleitung)) ?></div>
<?php endif; ?>

<!-- ========================= POSITIONEN ========================= -->
<?php if (!empty($lines)): ?>
  <table class="pos">
    <tbody>

    <?php foreach ($lines as $l): ?>

      <?php
        if (isset($l['label']) && $l['label'] === '###SECTION_TITLE###') {
      ?>
        <tr>
          <td colspan="3" style="padding-top:4mm; font-weight:700; font-size:11px; line-height:1; border-bottom:1px solid <?= h($catBorderColor) ?>;">
            <?= h($l['title']) ?>
          </td>
        </tr>
      <?php
          continue;
        }

        if (isset($l['label']) && $l['label'] === '###SECTION_SUM###') {
      ?>
        <tr>
          <td colspan="3" class="section-sum" style="border:1px solid <?= h($catBorderColor) ?>; padding:1.5mm 2mm; color: <?= h($catDesign['color']) ?>; font-weight:700; font-size:11px; line-height:1;">
            <table style="width:100%; border-collapse:collapse;"><tr>
              <td style="width:56%;"></td>
              <td style="width:24%; text-align:right; vertical-align:middle;">Zwischensumme:</td>
              <td style="width:20%; text-align:right; vertical-align:middle;"><?= chf($l['sum']) ?> <?= h($currency) ?></td>
            </tr></table>
          </td>
        </tr>
      <?php
          continue;
        }

        $label  = getv($l, ['label']);
        if (is_string($label) && strtolower(trim($label)) === 'risiko') {
            continue;
        }
        $note   = trim((string)(getv($l, ['note']) ?? ''));
        $ltotal = getv($l, ['total']);
        if (($label === null || $label === '') && ($ltotal === null || $ltotal === '' || (float)$ltotal == 0.0)) {
            continue;
        }

        $menge  = getv($l, ['menge']);
        $einheit= getv($l, ['einheit']);
        $preis  = getv($l, ['preis']);

        $p1Label = ($label !== null && $label !== '') ? text((string) $label) : '';
      ?>

      <tr>
        <td class="p1"><?php
            if ($p1Label !== '') {
                echo h($p1Label);
            }
            if ($showPdfDetails && $note !== '') {
                if ($p1Label !== '') {
                    echo ' ';
                }
                echo '<span style="color:' . h($catBorderColor) . '">' . h(text($note)) . '</span>';
            }
        ?></td>

        <td class="p2">
          <?php if ($showPdfDetails && $menge !== null && $preis !== null && $menge !== '' && $preis !== '' && (float)$preis != 0.0): ?>
            <?= h($menge) ?> <?= h($einheit ?: '') ?> × <?= chf($preis) ?> <?= h($currency) ?>
          <?php endif; ?>
        </td>

        <td class="p3">
          <?php if ($ltotal !== null && $ltotal !== ''): ?>
            <?= chf($ltotal) ?> <?= h($currency) ?>
          <?php endif; ?>
        </td>
      </tr>

    <?php endforeach; ?>

    </tbody>
  </table>
<?php endif; ?>

<!-- ========================= GESAMTSUMMEN ========================= -->
<div class="sumBlock">
  <?php if ($zwischensumme !== null && $zwischensumme !== ''): ?>
    <span class="sumLine">Zwischensumme: <?= chf($zwischensumme) ?> <?= h($currency) ?></span>
  <?php endif; ?>

  <?php if ($rabattCHF !== null && (float)$rabattCHF != 0.0): ?>
    <span class="sumLine">Rabatt: −<?= chf($rabattCHF) ?> <?= h($currency) ?></span>
  <?php elseif ($rabattProz !== null && (float)$rabattProz != 0.0): ?>
    <span class="sumLine">Rabatt: −<?= h($rabattProz) ?>%</span>
  <?php endif; ?>

  <span class="sumLine"><strong>TOTAL: <?= chf($amountVal) ?> <?= h($currency) ?></strong></span>
</div>

<?php if ($abschluss): ?>
  <div class="block" style="margin-top:6mm;"><?= nl2br(h($abschluss)) ?></div>
<?php endif; ?>

<?php if ($grussText): ?>
  <div class="block" style="margin-top:8mm;">
    <?= nl2br(h($grussText)) ?>
  </div>
<?php endif; ?>

<?php if ($hasClosingSign): ?>
  <div class="closingSign" style="color: <?= $absColColor ?>;">
    <?php if ($absSigSrc): ?>
      <img class="absSigImg" src="<?= h($absSigSrc) ?>" alt="" style="border-bottom:0.15mm solid <?= $absColColor ?>; padding-bottom:1mm;">
    <?php endif; ?>
    <?php if ($sigPosition !== ''): ?>
      <div><?= h($sigPosition) ?></div>
    <?php endif; ?>
    <?php if ($sigBezeichnung !== ''): ?>
      <div><?= h($sigBezeichnung) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- ========================= SEITE 2: Einzahlungsschein (Koordinaten ab Blattunterkante/-rand, A4 210×297 mm) ========================= -->
<div class="page-break"></div>

<div class="slip-bleed">
  <div style="position:absolute; left:0; right:0; bottom:10cm; height:0; border-top:0.4mm solid <?= h($catBorderColor) ?>;"></div>
  <div style="position:absolute; left:6.5cm; bottom:1cm; width:0; height:9cm; border-left:0.4mm solid <?= h($catBorderColor) ?>;"></div>

  <div class="slip-abs" style="left:7cm; bottom:8cm; width:40mm; height:40mm; overflow:hidden;"><?= $qrSvg ?></div>

  <!-- Empfangsschein (links) -->
  <div class="slip-abs slip-lbl" style="left:1cm; bottom:9.2cm;">Empfangsschein</div>
  <div class="slip-abs" style="left:1cm; bottom:8.4cm;"><span class="slip-lbl">Konto / Zahlbar an</span><br><?= h($slipIbanLine) ?><br><?= h($slipCredName) ?><br><?= h($slipCredAddr) ?></div>
  <?php if ($kundeBlockText): ?>
  <div class="slip-abs" style="left:1cm; bottom:5.6cm;"><span class="slip-lbl">Zahlbar durch (Name/Adresse)</span><br><?= h($kundeBlockText) ?></div>
  <?php endif; ?>
  <div class="slip-abs slip-lbl" style="left:1cm; bottom:3.5cm;">Währung</div>
  <div class="slip-abs slip-lbl" style="left:2.5cm; bottom:3.5cm;">Betrag</div>
  <div class="slip-abs" style="left:2.5cm; bottom:3.1cm;"><?= h($currency) ?> <?= h($slipAmtFmt) ?></div>

  <!-- Zahlteil (rechts) -->
  <div class="slip-abs slip-lbl" style="left:7.0cm; bottom:9.2cm;">Zahlteil</div>
  <div class="slip-abs" style="left:11.7cm; bottom:8.0cm;"><span class="slip-lbl">Konto / Zahlbar an</span><br><?= h($slipIbanLine) ?><br><?= h($slipCredName) ?><br><span style="font-size:10px; line-height:1;"><?= h($slipCredAddr) ?></span></div>
  <div class="slip-abs slip-lbl" style="left:11.7cm; bottom:6.9cm;">Zusätzliche Information</div>
  <div class="slip-abs" style="left:11.7cm; bottom:6.5cm; font-size:10px; line-height:1;"><?= h($additionalInfo) ?></div>
  <?php if ($kundeBlockText): ?>
  <div class="slip-abs" style="left:11.7cm; bottom:6.4cm;"><span class="slip-lbl">Zahlbar durch (Name/Adresse)</span><br><?= h($kundeBlockText) ?></div>
  <?php endif; ?>
  <div class="slip-abs" style="left:11.7cm; bottom:5.4cm; font-size:10px; line-height:1;"><?= h($slipIbanLine) ?><?php if ($slipCredName !== ''): ?><br><?= h($slipCredName) ?><?php endif; ?></div>
  <div class="slip-abs slip-lbl" style="left:7.0cm; bottom:3.5cm;">Währung</div>
  <div class="slip-abs slip-lbl" style="left:8.5cm; bottom:3.5cm;">Betrag</div>
  <div class="slip-abs" style="left:7.0cm; bottom:3.1cm;"><?= h($currency) ?></div>
  <div class="slip-abs" style="left:8.5cm; bottom:3.1cm;"><?= h($slipAmtFmt) ?></div>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

/* ----------------------------
   DOMPDF render
----------------------------- */
$options = new Options();
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$pdfDir = __DIR__ . '/data/pdf';
if (!is_dir($pdfDir)) @mkdir($pdfDir, 0775, true);

$pdfFile = $pdfDir . '/Rechnung_' . $invoiceId . '.pdf';
file_put_contents($pdfFile, $dompdf->output());

$dompdf->stream('Rechnung_' . $invoiceId . '.pdf', ['Attachment' => false]);
exit;
