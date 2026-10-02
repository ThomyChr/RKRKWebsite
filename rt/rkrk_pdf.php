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

/** Datei unter data/rt (images/) auflösen – z.B. Kategorie-Swiss-QR aus Admin. */
function resolve_rt_media_path(string $rel, string $baseDir): string {
    $rel = str_replace('\\', '/', trim($rel));
    if ($rel === '' || preg_match('#^https?://#i', $rel)) {
        return '';
    }
    $rel = ltrim($rel, '/');
    $bn = basename($rel);
    $candidates = [
        $baseDir . '/' . $rel,
        $baseDir . '/images/' . $bn,
        __DIR__ . '/' . $rel,
        __DIR__ . '/images/' . $bn,
    ];
    foreach ($candidates as $cand) {
        if (is_file($cand)) {
            return $cand;
        }
    }
    return '';
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

function format_bic(string $bic): string {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $bic));
}

/** Erste nicht-leere Zeichenkette (leerer String zählt als fehlend). */
function first_nonempty_str(...$candidates): string {
    foreach ($candidates as $v) {
        if ($v === null) {
            continue;
        }
        $s = trim((string)$v);
        if ($s !== '') {
            return $s;
        }
    }
    return '';
}

function is_risk_label($label): bool {
    if (!is_string($label)) {
        return false;
    }
    $n = strtolower(trim($label));
    return $n !== '' && strpos($n, 'risiko') !== false;
}

function risk_options_from_defaults(array $defaults): array {
    $bem = '';
    if (isset($defaults['standardpositionen']['RISIKO']['bemerkung'])) {
        $bem = (string) $defaults['standardpositionen']['RISIKO']['bemerkung'];
    } elseif (isset($defaults['risiko_bemerkung'])) {
        $bem = (string) $defaults['risiko_bemerkung'];
    }
    $opts = [];
    if (preg_match_all('/([^,;\n]+?)\s*[:=-]\s*([0-9]+(?:[.,][0-9]+)?)/u', $bem, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $label = trim((string) $row[1]);
            $value = (float) str_replace(',', '.', (string) $row[2]);
            if ($label !== '' && $value > 0) {
                $opts[] = ['label' => $label, 'value' => $value];
            }
        }
    }
    return $opts;
}

function risk_label_for_value($value, array $riskOptions): string {
    if (!is_numeric($value)) {
        return '';
    }
    $v = (float) $value;
    foreach ($riskOptions as $opt) {
        $ov = (float) ($opt['value'] ?? 0);
        if ($ov > 0 && abs($ov - $v) < 0.0001) {
            return trim((string) ($opt['label'] ?? ''));
        }
    }
    return '';
}

/** Whitespace/case-normalize a person-name part for signature matching. */
function signature_name_norm(string $s): string {
    $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    if ($s === '') {
        return '';
    }
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

/** True if this catalog key looks like an admin sig id, not a display name. */
function signature_key_is_id(string $sid): bool {
    return (bool) preg_match('/^sig[a-z0-9_]+$/i', $sid);
}

/** Admin-signaturen-Eintrag zu absender.vorname + absender.nachname. */
function resolve_signature_entry_for_absender(array $defaults, array $data): ?array {
    $absV = signature_name_norm((string) getv($data, ['absender', 'vorname'], ''));
    $absN = signature_name_norm((string) getv($data, ['absender', 'nachname'], ''));
    if ($absV === '' && $absN === '') {
        return null;
    }
    $full = trim($absV . ' ' . $absN);
    $sigs = isset($defaults['signaturen']) && is_array($defaults['signaturen']) ? $defaults['signaturen'] : [];
    $orderIdx = [];
    if (isset($defaults['signaturen_order']) && is_array($defaults['signaturen_order'])) {
        foreach ($defaults['signaturen_order'] as $i => $sid) {
            $orderIdx[(string) $sid] = (int) $i;
        }
    }
    $matches = [];
    foreach ($sigs as $sid => $row) {
        if (!is_array($row)) {
            continue;
        }
        $vn = signature_name_norm((string) ($row['vorname'] ?? ''));
        $nn = signature_name_norm((string) ($row['name'] ?? $row['nachname'] ?? ''));
        $cand = trim($vn . ' ' . $nn);
        $sidStr = is_string($sid) ? trim($sid) : '';
        $nameHit = ($vn !== '' || $nn !== '') && $vn === $absV && $nn === $absN;
        $fullHit = $cand !== '' && $cand === $full;
        $keyHit = $sidStr !== '' && !signature_key_is_id($sidStr) && signature_name_norm($sidStr) === $full;
        if (!$nameHit && !$fullHit && !$keyHit) {
            continue;
        }
        $rel = trim((string) ($row['bild'] ?? ''));
        $path = $rel !== '' ? signature_bild_path($rel) : '';
        $hasImg = ($path !== '' && is_file($path));
        $pos = trim((string) ($row['position'] ?? ''));
        $bez = trim((string) ($row['bezeichnung'] ?? ''));
        $hasMeta = ($pos !== '' || $bez !== '');
        $ord = array_key_exists((string) $sid, $orderIdx) ? $orderIdx[(string) $sid] : -1;
        $matches[] = ['row' => $row, 'hasImg' => $hasImg, 'hasMeta' => $hasMeta, 'ord' => $ord];
    }
    if ($matches === []) {
        return null;
    }
    usort($matches, static function (array $a, array $b): int {
        if ($a['hasImg'] !== $b['hasImg']) {
            return $a['hasImg'] ? -1 : 1;
        }
        if ($a['hasMeta'] !== $b['hasMeta']) {
            return $a['hasMeta'] ? -1 : 1;
        }
        return $b['ord'] <=> $a['ord'];
    });
    return $matches[0]['row'];
}

function signature_bild_path(string $rel): string {
    $rel = str_replace('\\', '/', ltrim($rel, '/'));
    if ($rel === '') {
        return '';
    }
    $resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
    $rtBase = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
    $cand = __DIR__ . '/' . $rel;
    if (is_file($cand)) {
        return $cand;
    }
    $candRt = $rtBase . '/' . $rel;
    if (is_file($candRt)) {
        return $candRt;
    }
    $bn = basename($rel);
    foreach (['images', 'assets'] as $sub) {
        $try = __DIR__ . '/' . $sub . '/' . $bn;
        if (is_file($try)) {
            return $try;
        }
        $tryRt = $rtBase . '/' . $sub . '/' . $bn;
        if (is_file($tryRt)) {
            return $tryRt;
        }
    }
    return '';
}

/** Dunkle Pixel einer Signatur-PNG in $hex einfärben (helle/transparente Bereiche bleiben). */
function signature_tinted_data_uri(string $path, string $hex): ?string {
    if (!function_exists('imagecreatefrompng') || !function_exists('imagepng')) {
        return null;
    }
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'png') {
        return null;
    }
    $im = @imagecreatefrompng($path);
    if ($im === false) {
        return null;
    }
    if (function_exists('imagepalettetotruecolor') && !imageistruecolor($im)) {
        imagepalettetotruecolor($im);
    }
    imagesavealpha($im, true);
    imagealphablending($im, false);

    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        imagedestroy($im);
        return null;
    }
    $tr = hexdec(substr($hex, 0, 2));
    $tg = hexdec(substr($hex, 2, 2));
    $tb = hexdec(substr($hex, 4, 2));

    $w = imagesx($im);
    $h = imagesy($im);
    for ($yy = 0; $yy < $h; $yy++) {
        for ($xx = 0; $xx < $w; $xx++) {
            $rgba = imagecolorat($im, $xx, $yy);
            $a = ($rgba >> 24) & 127;
            if ($a >= 127) {
                continue;
            }
            $r = ($rgba >> 16) & 255;
            $g = ($rgba >> 8) & 255;
            $b = $rgba & 255;
            // Paper/halo: near-white (also light mint). Do not use luminance —
            // brand-green signatures are bright and would vanish.
            if ($r >= 220 && $g >= 220 && $b >= 220) {
                $clear = imagecolorallocatealpha($im, 255, 255, 255, 127);
                if ($clear !== false) {
                    imagesetpixel($im, $xx, $yy, $clear);
                }
                continue;
            }
            $col = imagecolorallocatealpha($im, $tr, $tg, $tb, $a);
            if ($col !== false) {
                imagesetpixel($im, $xx, $yy, $col);
            }
        }
    }

    imagealphablending($im, true);
    ob_start();
    imagepng($im);
    $pngData = ob_get_clean();
    imagedestroy($im);
    if ($pngData === '' || $pngData === false) {
        return null;
    }
    return 'data:image/png;base64,' . base64_encode($pngData);
}

/* ----------------------------
   ID lesen
----------------------------- */
$id = '';
$src = 'invoice';
if (PHP_SAPI === 'cli') {
    global $argv;
    $id = isset($argv[1]) ? (string)$argv[1] : '';
} else {
    $id = isset($_GET['id']) ? (string)$_GET['id'] : '';
    $src = isset($_GET['src']) ? strtolower((string)$_GET['src']) : 'invoice';
}
$id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
if ($id === '') {
    http_response_code(400);
    echo "Fehler: ID fehlt. Nutzung: ?id=RE2026IN0001 (optional &debug=1)";
    exit;
}
$isExamplePdf = in_array($src, ['example', 'examples', 'bsp'], true);
$renderPaymentSlip = !$isExamplePdf;

/* ----------------------------
   invoice_default.json laden (RT-Datenbasis)
----------------------------- */
$resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
$dataDir = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
$baseDir = $dataDir;

$defaultsFile = $baseDir . '/invoice_default.json';
$defaults = [];
if (is_file($defaultsFile)) {
    $defaults = json_decode((string)file_get_contents($defaultsFile), true);
    if (!is_array($defaults)) $defaults = [];
}
$riskOptions = risk_options_from_defaults($defaults);
require_once __DIR__ . '/rkrk_category.php';

/* ----------------------------
   JSON laden (Rechnung oder Beispiel)
----------------------------- */
$jsonSubdir = $isExamplePdf ? 'examples' : 'invoices';
$jsonFile = $baseDir . "/{$jsonSubdir}/{$id}.json";
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

$php80File = dirname(__DIR__) . '/scripts/rkrk_php80.php';
$faviconFile = dirname(__DIR__) . '/scripts/rkrk_favicon.php';
if (is_file($php80File)) {
    require_once $php80File;
}
if (is_file($faviconFile)) {
    require_once $faviconFile;
}
if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
    $shellTitle = trim((string) getv($data, ['id'], $id));
    rkrk_pdf_maybe_emit_html_shell($shellTitle !== '' ? $shellTitle : 'Rechnung');
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
if (!$isExamplePdf && $land) $kundeBlock[] = (string)$land;
$kundeBlockText = implode("\n", $kundeBlock);

$anredeZeile = trim((string)$anrede);
$anredeZeile = $anredeZeile !== '' ? $anredeZeile : null;

/* ----------------------------
   Kategorie + Zahlungsstammdaten (konto + kategorien[kat].finanz)
----------------------------- */
$cat = category_conf_for_code($kategorie, $KATEGORIEN);
$finanz = merged_finanz($defaults, $cat);
$pdfLayout = isset($defaults['pdf_layout']) && is_array($defaults['pdf_layout']) ? $defaults['pdf_layout'] : [];
$catDesign = category_design($kategorie, $KATEGORIEN, $defaults);
$abschlussSlogan = trim((string) ($cat['abschlussslogan'] ?? ''));

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

/* Finanzinstitut pro Konto (kategorien[kat].finanz), Fallback pdf_layout */
$bankName = first_nonempty_str(
    $finanz['bank'] ?? null,
    $finanz['finanzinstitut'] ?? null,
    $pdfLayout['finanzinstitut_name'] ?? null
);
$bankOrt = first_nonempty_str(
    $finanz['bank_ort'] ?? null,
    $finanz['bank_plz_ort'] ?? null,
    $pdfLayout['finanzinstitut_ort'] ?? null
);
$bic = format_bic(first_nonempty_str(
    getv($data, ['bank', 'bic']),
    getv($data, ['bank', 'bic_swift']),
    $finanz['bic'] ?? null,
    $finanz['bic_swift'] ?? null,
    $pdfLayout['bic'] ?? null
));
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
if ($bic !== '') {
    $absenderBankLines[] = 'BIC/SWIFT:';
    $absenderBankLines[] = $bic;
}
if ($ibanFormatted !== '') {
    $absenderBankLines[] = 'IBAN:';
    $absenderBankLines[] = $ibanFormatted;
}
$absenderBankText = implode("\n", $absenderBankLines);
if ($isExamplePdf) {
    // Für Beispiel-PDFs keine Finanzinstitut-/Bankangaben im Kopf rechts anzeigen.
    $absenderBankText = '';
}

/** Kopf links: kein Finanzblock. Rechts unter Logo: empfaenger, Strasse, PLZ/Ort, Bank */
$absLineEmpfaenger = trim($empfaengerDefault);
if ($isExamplePdf) {
    // Für Beispiel-PDFs Kontobesitzer unter dem Logo ausblenden.
    $absLineEmpfaenger = '';
}
$absLineStrasse = trim($strasseDefault);
$absLineCity = trim(trim($plzDefault) . ' ' . trim($ortDefault));

$absenderHeadLines = [];
if ($absLineStrasse !== '') {
    $absenderHeadLines[] = $absLineStrasse;
}
$absenderHeadText = implode("\n", $absenderHeadLines);
$hasLogoAbsender = ($absLineEmpfaenger !== '' || $absenderHeadText !== '' || $absLineCity !== '' || $absenderBankText !== '');

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
if ($absSigSrc !== null && $absSigPath !== '' && is_file($absSigPath) && strtolower(pathinfo($absSigPath, PATHINFO_EXTENSION)) === 'png') {
    $tinted = signature_tinted_data_uri($absSigPath, (string) ($catDesign['color'] ?? '#333333'));
    if ($tinted !== null) {
        $absSigSrc = $tinted;
    }
}
$sigPosition = $sigEntry ? trim((string) ($sigEntry['position'] ?? '')) : '';
$sigBezeichnung = $sigEntry ? trim((string) ($sigEntry['bezeichnung'] ?? '')) : '';
$absVorname = trim((string) getv($data, ['absender', 'vorname'], ''));
$absNachname = trim((string) getv($data, ['absender', 'nachname'], ''));
$absNameLine = trim($absVorname . ' ' . $absNachname);

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
            $candRt = $baseDir . '/' . $rel;
            if (is_file($candRt)) {
                $logoPath = $candRt;
            }
            if ($logoPath === '') {
                $bn = basename($rel);
                foreach (['images', 'assets'] as $sub) {
                    $try = __DIR__ . '/' . $sub . '/' . $bn;
                    if (is_file($try)) {
                        $logoPath = $try;
                        break;
                    }
                    $tryRt = $baseDir . '/' . $sub . '/' . $bn;
                    if (is_file($tryRt)) {
                        $logoPath = $tryRt;
                        break;
                    }
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
$pdfSectionCount = 0;
if (is_array($sections)) {
    foreach ($sections as $sec) {
        if (!is_array($sec)) {
            continue;
        }
        $hasTitle = trim((string) getv($sec, ['title'], '')) !== '';
        $secLines = getv($sec, ['lines'], []);
        $hasLine = false;
        if (is_array($secLines)) {
            foreach ($secLines as $l) {
                if (is_array($l)) {
                    $hasLine = true;
                    break;
                }
            }
        }
        if ($hasTitle || $hasLine) {
            $pdfSectionCount++;
        }
    }
}
$showSectionZwischensumme = $pdfSectionCount > 1;

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

        $secAnm = trim((string) getv($sec, ['anmerkungen'], ''));
        if ($showSectionZwischensumme && $sectionSum > 0) {
            $lines[] = [
                'label'       => '###SECTION_SUM###',
                'sum'         => $sectionSum,
                'anmerkungen' => $secAnm,
            ];
        } elseif ($secAnm !== '') {
            $lines[] = [
                'label'       => '###SECTION_SUM###',
                'sum'         => null,
                'anmerkungen' => $secAnm,
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
        if (is_risk_label($lab)) {
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
                    if (is_risk_label($lab)) {
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
   Swiss-QR (Einzahlungsschein) — Bild aus Admin (kategorien[kat].swissqrcode)
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

$swissQrRel = trim((string)($cat['swissqrcode'] ?? ''));
$swissQrPath = $swissQrRel !== '' ? resolve_rt_media_path($swissQrRel, $baseDir) : '';

$qrPrimary = trim((string) ($catDesign['color'] ?? '#000000'));
if ($qrPrimary === '') {
    $qrPrimary = '#000000';
}

$swissQrSrc = null;
if ($swissQrPath !== '') {
    // Dunkle Module in Kategorie-Primärfarbe (Styleset primary)
    $swissQrSrc = signature_tinted_data_uri($swissQrPath, $qrPrimary);
    if ($swissQrSrc === null) {
        $swissQrSrc = img_data_uri($swissQrPath);
    }
}

$qrHtml = '';
if ($swissQrSrc !== null) {
    $qrHtml = '<img src="' . h($swissQrSrc) . '" alt="Swiss QR-Code" style="width:40mm;height:40mm;display:block;">';
} else {
    // Fallback: dynamisch erzeugen, falls in Admin kein Swiss-QR hochgeladen ist
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

    $qrHtml = qr_svg($qrString, $qrPrimary);
}

$slipIbanLine = $ibanFormatted !== '' ? $ibanFormatted : format_iban_ch((string) $qrIban);
$slipCredName = $crName;
$slipCredStreet = trim($crStreet);
$slipCredZipCity = trim(trim($crZip) . ' ' . trim($crCity));
$slipCredLand = trim((string) $landDefault);
$slipAmtFmt = chf($amountVal);

$hasClosingSign = ($absSigSrc || $absNameLine !== '' || $sigPosition !== '' || $sigBezeichnung !== '');
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

  /* Kopf inkl. Absender-Adresse unter Kontobesitzer (Empfängerblock folgt darunter) */
  .headerSlot {
    position: relative;
    height: 42mm;
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
  .headerMain { display: table-cell; vertical-align: top; width: 62%; padding-right: 4mm; min-height: 1px; }
  .headerMain-empty { font-size: 1px; line-height: 0; color: transparent; }
  .pdfHeader .logoCell {
    display: table-cell;
    vertical-align: top;
    width: 38%;
    text-align: right;
  }
  .pdfHeader .logoCell img { max-width: 34mm; max-height: 22mm; object-fit: contain; display: block; margin-left: auto; margin-right: 0; }
  .absenderUnderLogo {
    margin-top: 1.5mm;
    text-align: right;
    font-size: 8px;
    line-height: 1;
  }
  .absenderUnderLogo-line {
    white-space: pre-line;
    line-height: 1.05;
    font-size: 8px;
    margin: 0.6mm 0 0 0;
    text-align: right;
  }
  .absenderUnderLogo-line:first-child {
    margin-top: 0;
  }
  .closingSign {
    margin-top: 8mm;
    text-align: left;
    font-size: 11px;
    line-height: 1.25;
    color: inherit;
  }
  .closingSign-sig { margin-bottom: 3mm; }
  .closingSign .absSigImg { width: 40mm; height: 18mm; object-fit: contain; display: block; margin: 0; }
  .closingSign-line { margin-top: 1mm; }
  .closingSign-meta { color: #000; }

  .tagline { font-size: 10px; font-weight: 700; line-height: 1.35; margin: 0; }
  .pdfHl { font-weight: 700; margin-top: 2mm; }

  .recipient { white-space: pre-line; margin-top: 0; font-size: 11px; line-height: 1.12; }
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

  .slip-bleed {
    page-break-before: always;
    page-break-inside: avoid;
    position: relative;
    width: 210mm;
    height: 297mm;
    margin: -18mm -16mm -16mm -16mm;
    padding: 0;
    box-sizing: border-box;
    font-size: 9px;
    line-height: 1;
    color: #000;
  }
  .slip-abs { position: absolute; margin: 0; padding: 0; white-space: pre-line; }
  .slip-lbl { font-weight: 700; }
  .slip-kontoBlock { line-height: 1.05; }
  .slip-money { font-size: 12px; }
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
    <?php else: ?>
      <span class="headerMain-empty">&#160;</span>
    <?php endif; ?>
  </div>

  <div class="logoCell">
    <?php if ($logoSrc): ?>
      <img src="<?= h($logoSrc) ?>" alt="Logo">
    <?php endif; ?>
    <?php if ($hasLogoAbsender): ?>
      <div class="absenderUnderLogo" style="color: <?= $absColColor ?>;">
        <?php if ($absLineEmpfaenger !== ''): ?>
          <div class="absenderUnderLogo-line"><?= h($absLineEmpfaenger) ?></div>
        <?php endif; ?>
        <?php if ($absenderHeadText !== ''): ?>
          <div class="absenderUnderLogo-line"><?= h($absenderHeadText) ?></div>
        <?php endif; ?>
        <?php if ($absLineCity !== ''): ?>
          <div class="absenderUnderLogo-line"><?= h($absLineCity) ?></div>
        <?php endif; ?>
        <?php if ($absenderBankText !== ''): ?>
          <div class="absenderUnderLogo-line"><?= h($absenderBankText) ?></div>
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
            $secAnm = trim((string) (getv($l, ['anmerkungen']) ?? ''));
            $secSum = getv($l, ['sum']);
      ?>
        <?php if ($secAnm !== ''): ?>
        <tr>
          <td colspan="3" style="padding:1mm 2mm 2mm 2mm; font-size:10px; line-height:1.35; color:#000;">
            <?= nl2br(h(text($secAnm))) ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php if ($secSum !== null && $secSum !== '' && (float)$secSum > 0): ?>
        <tr>
          <td colspan="3" class="section-sum" style="border:1px solid <?= h($catBorderColor) ?>; padding:1.5mm 2mm; color: <?= h($catDesign['color']) ?>; font-weight:700; font-size:11px; line-height:1;">
            <table style="width:100%; border-collapse:collapse;">
              <tr>
                <td style="width:56%;"></td>
                <td style="width:24%; text-align:right; vertical-align:middle;">Zwischensumme:</td>
                <td style="width:20%; text-align:right; vertical-align:middle;"><?= chf($secSum) ?> <?= h($currency) ?></td>
              </tr>
            </table>
          </td>
        </tr>
        <?php endif; ?>
      <?php
          continue;
        }

        $label  = getv($l, ['label']);
        $risikoVal = getv($l, ['risiko']);
        $isRisiko = is_risk_label($label) || (is_numeric($risikoVal) && (float)$risikoVal > 0);
        $note   = trim((string)(getv($l, ['note']) ?? ''));
        $ltotal = getv($l, ['total']);
        $ltotalNum = is_numeric($ltotal) ? (float)$ltotal : null;
        $ltotalText = trim((string)$ltotal);
        $risikoLabelText = $isRisiko ? risk_label_for_value($risikoVal, $riskOptions) : '';
        if (($label === null || $label === '') && ($ltotal === null || $ltotal === '' || (float)$ltotal == 0.0)) {
            continue;
        }

        $menge  = getv($l, ['menge']);
        $einheit= getv($l, ['einheit']);
        $preis  = getv($l, ['preis']);
        $lineBild = trim((string) (getv($l, ['bild']) ?? ''));
        $lineBildPath = $lineBild !== '' ? signature_bild_path($lineBild) : '';
        $lineBildSrc = ($lineBildPath !== '' && is_file($lineBildPath)) ? img_data_uri($lineBildPath) : null;

        if ($menge === null || $menge === '' || (float) $menge <= 0) {
            continue;
        }

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
            if ($lineBildSrc) {
                echo '<div style="margin-top:1.2mm;"><img src="' . h($lineBildSrc) . '" alt="" style="max-width:40mm; max-height:24mm; object-fit:contain; border:1px solid #ddd;"></div>';
            }
        ?></td>

        <td class="p2">
          <?php if (!$isRisiko && $showPdfDetails && $menge !== null && $preis !== null && $menge !== '' && $preis !== '' && (float)$preis != 0.0): ?>
            <?= h($menge) ?> <?= h($einheit ?: '') ?> × <?= chf($preis) ?> <?= h($currency) ?>
          <?php endif; ?>
        </td>

        <td class="p3">
          <?php if ($isRisiko && $risikoLabelText !== ''): ?>
            <?= h(text($risikoLabelText)) ?>
          <?php elseif ($isRisiko && $ltotalText !== '' && !is_numeric($ltotalText)): ?>
            <?= h(text($ltotalText)) ?>
          <?php elseif (!$isRisiko && $ltotalNum !== null): ?>
            <?= chf($ltotalNum) ?> <?= h($currency) ?>
          <?php endif; ?>
        </td>
      </tr>

    <?php endforeach; ?>

    </tbody>
  </table>
<?php endif; ?>

<!-- ========================= GESAMTSUMMEN ========================= -->
<div class="sumBlock">
  <?php
    $hasRabatt = ($rabattCHF !== null && (float)$rabattCHF != 0.0)
        || ($rabattProz !== null && (float)$rabattProz != 0.0);
    $showInvoiceZwischensumme = !empty($showSectionZwischensumme) || $hasRabatt;
  ?>
  <?php if ($showInvoiceZwischensumme && $zwischensumme !== null && $zwischensumme !== ''): ?>
    <span class="sumLine">Zwischensumme: <?= chf($zwischensumme) ?> <?= h($currency) ?></span>
  <?php endif; ?>

  <?php if ($rabattCHF !== null && (float)$rabattCHF != 0.0): ?>
    <span class="sumLine">Rabatt: −<?= chf($rabattCHF) ?> <?= h($currency) ?></span>
  <?php elseif ($rabattProz !== null && (float)$rabattProz != 0.0): ?>
    <span class="sumLine">Rabatt: −<?= h($rabattProz) ?>%</span>
  <?php endif; ?>

  <span class="sumLine"><strong>TOTAL: <?= chf($amountVal) ?> <?= h($currency) ?></strong></span>
</div>
<?php if ($catInfo !== ''): ?>
  <div class="block" style="margin-top:3mm;"><?= nl2br(h($catInfo)) ?></div>
<?php endif; ?>

<?php if ($abschluss): ?>
  <div class="block" style="margin-top:6mm;"><?= nl2br(h($abschluss)) ?></div>
<?php endif; ?>

<?php if ($grussText): ?>
  <div class="block" style="margin-top:8mm;">
    <?= nl2br(h($grussText)) ?>
  </div>
<?php endif; ?>

<?php if ($hasClosingSign): ?>
  <div class="closingSign">
    <?php if ($absSigSrc): ?>
      <div class="closingSign-sig">
        <img class="absSigImg" src="<?= h($absSigSrc) ?>" alt="">
      </div>
    <?php endif; ?>
    <?php if ($absNameLine !== '' || $sigPosition !== '' || $sigBezeichnung !== ''): ?>
    <div class="closingSign-meta">
      <?php if ($absNameLine !== ''): ?>
        <div class="closingSign-line"><?= h($absNameLine) ?></div>
      <?php endif; ?>
      <?php if ($sigPosition !== ''): ?>
        <div class="closingSign-line"><?= h($sigPosition) ?></div>
      <?php endif; ?>
      <?php if ($sigBezeichnung !== ''): ?>
        <div class="closingSign-line"><?= h($sigBezeichnung) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($abschlussSlogan !== ''): ?>
  <div class="block" style="margin-top:5mm; text-align:center; color:<?= h($catDesign['color']) ?>; font-weight:700;">
    <?= nl2br(h(text($abschlussSlogan))) ?>
  </div>
<?php endif; ?>

<?php if ($renderPaymentSlip): ?>
<!-- ========================= SEITE 2: Einzahlungsschein (Koordinaten ab Blattunterkante/-rand, A4 210×297 mm) ========================= -->
<div class="slip-bleed">
  <div style="position:absolute; left:0; right:0; bottom:9cm; height:0; border-top:0.4mm solid <?= h($catBorderColor) ?>;"></div>
  <div style="position:absolute; left:6.5cm; bottom:0; width:0; height:9cm; border-left:0.4mm solid <?= h($catBorderColor) ?>;"></div>

  <div class="slip-abs" style="left:7.0cm; bottom:4.1cm; width:40mm; height:40mm; overflow:hidden;"><?= $qrHtml ?></div>

  <!-- Empfangsschein (links) -->
  <div class="slip-abs slip-lbl" style="left:1cm; bottom:8.2cm;">Empfangsschein</div>
  <div class="slip-abs slip-kontoBlock" style="left:1cm; bottom:5.9cm;"><span class="slip-lbl">Konto / Zahlbar an</span><br><?= h($slipIbanLine) ?><?php if ($slipCredName !== ''): ?><br><?= h($slipCredName) ?><?php endif; ?><?php if ($slipCredStreet !== ''): ?><br><?= h($slipCredStreet) ?><?php endif; ?><?php if ($slipCredZipCity !== ''): ?><br><?= h($slipCredZipCity) ?><?php endif; ?><?php if ($slipCredLand !== ''): ?><br><?= h($slipCredLand) ?><?php endif; ?></div>
  <?php if ($kundeBlockText): ?>
  <div class="slip-abs" style="left:1cm; bottom:3.5cm;"><span class="slip-lbl">Zahlbar durch (Name/Adresse)</span><br><?= h($kundeBlockText) ?></div>
  <?php endif; ?>
  <div class="slip-abs slip-lbl" style="left:1cm; bottom:3.0cm;">Währung</div>
  <div class="slip-abs slip-lbl" style="left:2.5cm; bottom:3.0cm;">Betrag</div>
  <div class="slip-abs slip-money" style="left:1cm; bottom:2.6cm;"><?= h($currency) ?></div>
  <div class="slip-abs slip-money" style="left:2.5cm; bottom:2.6cm;"><?= h($slipAmtFmt) ?></div>

  <!-- Zahlteil (rechts): Inhalt unterhalb Überschrift um 1 cm nach oben -->
  <div class="slip-abs slip-lbl" style="left:7.0cm; bottom:8.2cm;">Zahlteil</div>
  <div class="slip-abs slip-kontoBlock" style="left:11.7cm; bottom:6.5cm;"><span class="slip-lbl">Konto / Zahlbar an</span><br><?= h($slipIbanLine) ?><?php if ($slipCredName !== ''): ?><br><?= h($slipCredName) ?><?php endif; ?><?php if ($slipCredStreet !== ''): ?><br><?= h($slipCredStreet) ?><?php endif; ?><?php if ($slipCredZipCity !== ''): ?><br><?= h($slipCredZipCity) ?><?php endif; ?><?php if ($slipCredLand !== ''): ?><br><?= h($slipCredLand) ?><?php endif; ?></div>
  <div class="slip-abs slip-lbl" style="left:11.7cm; bottom:5.9cm;">Zusätzliche Information</div>
  <div class="slip-abs" style="left:11.7cm; bottom:5.5cm; line-height:1;"><?= h($additionalInfo) ?></div>
  <?php if ($kundeBlockText): ?>
  <div class="slip-abs" style="left:11.7cm; bottom:3.88cm;"><span class="slip-lbl">Zahlbar durch (Name/Adresse)</span><br><?= h($kundeBlockText) ?></div>
  <?php endif; ?>
  <div class="slip-abs slip-lbl" style="left:7.0cm; bottom:3.0cm;">Währung</div>
  <div class="slip-abs slip-lbl" style="left:8.5cm; bottom:3.0cm;">Betrag</div>
  <div class="slip-abs slip-money" style="left:7.0cm; bottom:2.6cm;"><?= h($currency) ?></div>
  <div class="slip-abs slip-money" style="left:8.5cm; bottom:2.6cm;"><?= h($slipAmtFmt) ?></div>
</div>
<?php endif; ?>

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

$pdfDir = $baseDir . '/pdf';
if (!is_dir($pdfDir)) @mkdir($pdfDir, 0775, true);

$pdfFile = $pdfDir . '/Rechnung_' . $invoiceId . '.pdf';
file_put_contents($pdfFile, $dompdf->output());

$dompdf->stream('Rechnung_' . $invoiceId . '.pdf', ['Attachment' => false]);
exit;
