<?php
// rkrk_rt.php – Rechnungen + Beispielrechnungen mit RISIKO-Logik

error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

include_once('../scripts/rkrk_gen.php');
if (!function_exists('headerpage')) {
    @include_once __DIR__ . '/../scripts/rkrk_gen.php';
    @include_once __DIR__ . '/../../scripts/rkrk_gen.php';
}
if (function_exists('headerpage')) {
    headerpage("Drohnenrehkitzrettung.ch", false);
}

// ---------------------------------------------------------
// Konfiguration laden
// ---------------------------------------------------------
$resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
$dataDir = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
$defaultsFile   = $dataDir . "/invoice_default.json";
$invoicesDir    = $dataDir . "/invoices";
$customersFile  = $dataDir . "/customers.json";
$examplesDir    = $dataDir . "/examples";

if (!file_exists($defaultsFile)) {
    die('invoice_default.json nicht gefunden');
}

$DEFAULTS  = json_decode(@file_get_contents($defaultsFile), true) ?: [];
$CUSTOMERS = json_decode(@file_get_contents($customersFile), true) ?: [];

$STANDARDPOSITIONEN = $DEFAULTS['standardpositionen'] ?? [];
$STYLESETS          = $DEFAULTS['stylesets'] ?? [];
$KATEGORIEN         = $DEFAULTS['kategorien'] ?? [];

// Aktuelle Kategorie
$currentKategorieKey = $_GET['kat'] ?? 'SC';
if (!isset($KATEGORIEN[$currentKategorieKey])) {
    $currentKategorieKey = array_key_first($KATEGORIEN);
}
$currentKategorie    = $KATEGORIEN[$currentKategorieKey];
$currentStylesetKey  = $currentKategorie['styleset'] ?? 'style_default';
$STYLE               = $STYLESETS[$currentStylesetKey] ?? reset($STYLESETS);

// ---------------------------------------------------------
// Mode
// ---------------------------------------------------------
$mode   = $_GET['mode']   ?? 'list';
$id     = $_GET['id']     ?? null;
$filter = $_GET['filter'] ?? 'all';

// ---------------------------------------------------------
// Rechnung speichern (AJAX)  ✅ NEU: direkt in rkrk_rt.php
// ---------------------------------------------------------
if ($mode === 'invoice_save') {
    header('Content-Type: application/json; charset=utf-8');

    if (!is_dir($invoicesDir)) {
        @mkdir($invoicesDir, 0777, true);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Ungültiges JSON"]);
        exit;
    }

    // Migration alter Felder
    if (isset($data["Gruss"]) && !isset($data["gruss"])) {
        $data["gruss"] = $data["Gruss"];
        unset($data["Gruss"]);
    }

    // Absicherungen
    if (!isset($data["absender"]) || !is_array($data["absender"])) {
        $data["absender"] = ["vorname" => "", "nachname" => ""];
    }
    if (!isset($data["betreff"])) $data["betreff"] = "";
    if (!isset($data["kategorie"])) $data["kategorie"] = ($currentKategorieKey ?? "IN");

    // Struktur: lines -> sections migrieren (falls alt)
    if (!isset($data["sections"]) || !is_array($data["sections"])) {
        $oldLines = $data["lines"] ?? [];
        $data["sections"] = [[ "title" => "", "lines" => is_array($oldLines) ? $oldLines : [] ]];
        unset($data["lines"]);
    }

    // ID generieren, falls neu
    
	$idIn = trim((string)($data["id"] ?? ""));
	if ($idIn === "" || $idIn === "null") {
		$idIn = "";
	}

    if ($idIn === "") {
        $kat = preg_replace('/[^A-Z]/', '', strtoupper($data["kategorie"] ?? "IN"));
        if ($kat === "") $kat = "IN";
        $year = date("Y");
        $prefix = "RE{$year}{$kat}";
        $files = glob($invoicesDir . "/{$prefix}*.json");

        $max = 0;
        foreach ($files as $f) {
            $base = basename($f, ".json");
            $num = intval(substr($base, -4));
            if ($num > $max) $max = $num;
        }
        $next = str_pad((string)($max + 1), 4, "0", STR_PAD_LEFT);
        $idIn = $prefix . $next;
    }

    $data["id"] = $idIn;

    // Totals serverseitig neu berechnen (robust, verhindert kaputte Summen)
    $zwischensumme = 0.0;

    foreach ($data["sections"] as &$sec) {
        if (!isset($sec["lines"]) || !is_array($sec["lines"])) $sec["lines"] = [];
        foreach ($sec["lines"] as &$line) {
            $menge = (float)($line["menge"] ?? 0);
            $preis = (float)($line["preis"] ?? 0);

            // Risiko-Zeile total immer 0
            $labelLower = strtolower(trim((string)($line["label"] ?? "")));
            if ($labelLower === "risiko") {
                $lineTotal = 0.0;
            } else {
                // Falls line.total fehlt, rechne default: menge*preis
                $lineTotal = isset($line["total"]) ? (float)$line["total"] : ($menge * $preis);
            }

            $line["menge"] = $menge;
            $line["preis"] = $preis;
            $line["total"] = $lineTotal;

            $zwischensumme += $lineTotal;
        }
    }

    $rabattCHF = (float)($data["rabattCHF"] ?? 0);
    $rabattProzent = (float)($data["rabattProzent"] ?? 0);

    $rabattWertProzent = $zwischensumme * ($rabattProzent / 100.0);
    $total = $zwischensumme - $rabattWertProzent - $rabattCHF;

    $data["zwischensumme"] = round($zwischensumme, 2);
    $data["total"] = round($total, 2);

    // Speichern
    $safeId = basename($data["id"]); // path traversal verhindern
    $file = $invoicesDir . "/" . $safeId . ".json";

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(["error" => "JSON-Encoding fehlgeschlagen"]);
        exit;
    }

    if (file_put_contents($file, $json, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(["error" => "Datei konnte nicht gespeichert werden"]);
        exit;
    }

    echo json_encode(["status" => "ok", "id" => $data["id"]]);
    exit;
}

// ---------------------------------------------------------
// Beispiel löschen (AJAX) ✅ NEU: wird in deiner UI aufgerufen
// ---------------------------------------------------------
if ($mode === 'example_delete') {
    $exId = $_GET['id'] ?? '';
    $exId = basename($exId);

    if ($exId === '') {
        http_response_code(400);
        echo "missing id";
        exit;
    }

    $file = $examplesDir . "/" . $exId . ".json";
    if (is_file($file)) {
        unlink($file);
    }
    echo "ok";
    exit;
}

// ---------------------------------------------------------
// Beispiel speichern (AJAX)
// ---------------------------------------------------------
if ($mode === 'example_save') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $exId = $_GET['id'] ?? null;
    if (!$exId) {
        $exId = uniqid('EX');
    }

    if (!is_dir($examplesDir)) {
        mkdir($examplesDir, 0777, true);
    }

    $file = "$examplesDir/$exId.json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo $exId;
    exit;
}

// ---------------------------------------------------------
// Rechnung laden
// ---------------------------------------------------------
$invoice = null;
if ($id) {
    $file = $invoicesDir . "/" . basename($id) . ".json";
    if (is_file($file)) {
        $invoice = json_decode(file_get_contents($file), true);
    }
}

// Fallback / neue Rechnung
if (!$invoice) {
    $invoice = [
        "id"            => "",
        "betreff"       => "",
        "kategorie"     => $currentKategorieKey,
        "kunde_details" => [
            "anrede"   => "",
            "vorname"  => "",
            "nachname" => "",
            "adresse"  => "",
            "plz"      => "",
            "ort"      => "",
            "land"     => "Schweiz"
        ],
        "absender"      => [
            "vorname"  => "",
            "nachname" => ""
        ],
        "ort"           => "",
        "datum"         => date("Y-m-d"),
        "einleitung"    => "",
        "gruss"         => "",
        "rabattCHF"     => 0,
        "rabattProzent" => 0,
        "zwischensumme" => 0,
        "total"         => 0,
        "sections"      => []
    ];
}

// Migration Gruss
if (isset($invoice["Gruss"]) && !isset($invoice["gruss"])) {
    $invoice["gruss"] = $invoice["Gruss"];
    unset($invoice["Gruss"]);
}

// Absender sicherstellen
if (!isset($invoice["absender"]) || !is_array($invoice["absender"])) {
    $invoice["absender"] = ["vorname" => "", "nachname" => ""];
}

// Default-Absender
if ($invoice["id"] === "" && empty($invoice["absender"]["vorname"]) && !empty($DEFAULTS["signaturen"])) {
    $first = reset($DEFAULTS["signaturen"]);
    $invoice["absender"]["vorname"]  = $first["vorname"] ?? "";
    $invoice["absender"]["nachname"] = $first["name"]    ?? "";
}

// Sections-Struktur sicherstellen
if (!isset($invoice["sections"]) || !is_array($invoice["sections"])) {
    $oldLines = $invoice["lines"] ?? [];
    $invoice["sections"] = [[
        "title" => "",
        "lines" => $oldLines
    ]];
    unset($invoice["lines"]);
}

// Standardpositionen für neue Rechnung
if ($invoice["id"] === "" && empty($invoice["sections"][0]["lines"])) {
    $kat      = $invoice["kategorie"];
    $sections = [];

    if (!empty($DEFAULTS["kategorien"][$kat]["gruppen"])) {
        $secLines = [];
        foreach ($DEFAULTS["kategorien"][$kat]["gruppen"] as $gid) {
            if (empty($DEFAULTS["standardgruppen"][$gid]["positionen"])) continue;
            foreach ($DEFAULTS["standardgruppen"][$gid]["positionen"] as $pid) {
                if (empty($DEFAULTS["standardpositionen"][$pid])) continue;
                $p = $DEFAULTS["standardpositionen"][$pid];
                $secLines[] = [
                    "label"   => $p["label"],
                    "note"    => $p["bemerkung"] ?? "",
                    "menge"   => 1,
                    "einheit" => $p["einheit"],
                    "preis"   => $p["preis"],
                    "total"   => $p["preis"]
                ];
            }
        }
        $sections[] = [
            "title" => "",
            "lines" => $secLines
        ];
    }

    if ($sections) {
        $invoice["sections"] = $sections;
    }
}

$title = ($mode === 'list') ? 'Rechnungen' : (($mode === 'examples') ? 'Beispielrechnungen' : 'Rechnung bearbeiten');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= safe($title) ?></title>
    <link rel="stylesheet" href="rkrk_rt.css">
    <style>
        :root {
            --green-dark:      #6F8B5E;
            --green-mid:       #AFC9A1;
            --green-light:     #DDEED6;
            --green-vlight:    #F6FBF4;
            --green-brand:     <?= safe($STYLE['primary'] ?? '#007A3D') ?>;
            --green-brand-dark:#005F2E;
            --white:           #FFFFFF;
            --black:           #000000;
            --text-dark:       #2F3E28;
            --text-mid:        #4F6A3C;
            --border:          #C8D9C0;
            --shadow:          rgba(0,0,0,0.08);
            --table-header:    #E8F5EE;
            --table-row:       #FFFFFF;
            --table-row-alt:   #F9FDFB;
            --table-hover:     #E0FFE0;
            --surface:         #F4F8F6;
            --background:      #FFFFFF;
        }
        body {
            font-family: system-ui, sans-serif;
            margin: 20px;
            background: var(--surface);
            color: var(--text-dark);
        }
        .page {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            background: var(--background);
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px var(--shadow);
            border: 1px solid var(--border);
        }
        .navbar a {
            margin-right: 15px;
            text-decoration: none;
            color: var(--green-brand);
            font-weight: 600;
        }
        .navbar a:hover { text-decoration: underline; }
        .btn-primary {
            padding: 8px 16px;
            background: var(--green-brand);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary:hover { background: var(--green-brand-dark); }
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-mid);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 6px 8px;
        }
        th {
            background: var(--table-header);
        }
        tr:nth-child(even) td { background: var(--table-row-alt); }
        tr:nth-child(odd) td { background: var(--table-row); }
        tr:hover td { background: var(--table-hover); }
        .rkrk-row {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        .rkrk-row > div { flex: 1; }
        input[type="text"],
        input[type="number"],
        select {
            padding: 6px;
            border: 1px solid var(--border);
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="number"] { text-align: right; }
        .total-box {
            background: var(--surface);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            margin-top: 15px;
        }
        .line-risiko {
            background: #fff7d6 !important;
            border-left: 3px solid #e0b200;
        }
        .line-risiko select {
            border-color: #e0b200;
        }
        .drag-handle { cursor: grab; text-align:center; }
    </style>
</head>
<body>

<div class="page">

<div class="header-top" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <h1><?= safe($title) ?></h1>

    <div class="header-logos">
        <img src="../images/23color/202410_skyscan_logo.png"
             id="logo_sky"
             style="height:80px; display:none;">
        <img src="../images/23color/202302 Logo_RKRKZ_dark.png"
             id="logo_rk"
             style="height:80px; display:none;">
    </div>
</div>

<div style="display:flex; gap:15px;">
	<?php  if ($mode === 'list') { ?>
		<a href="rkrk_rt.php?mode=form" class="btn-primary">Neue Rechnung</a>
	<?php } ?>
	<?php  if ($mode === 'list') { ?>
		<a href="rkrk_rt.php?mode=examples&kat=<?= safe($currentKategorieKey) ?>" class="btn">Beispielrechnungen</a>
	<?php } ?>
	<?php  if ($mode === 'examples') { ?>
    <a href="rkrk_rt.php?mode=examples&kat=<?= safe($currentKategorieKey) ?>" class="btn-primary">
        Neues Beispiel
    </a>
	<?php } ?>

	<a href="rkrk_rt_admin.php" class="btn">Admin</a>
	<?php  if ($mode != 'list') { ?>
		<a href="rkrk_rt.php?mode=list&kat=<?= safe($currentKategorieKey) ?>" class="btn">Zurück zur Übersicht</a>
	<?php } ?>
</div>

<?php if ($mode === 'list'): ?>

    <div class="rkrk-row" style="justify-content: space-between; margin-bottom:20px;">
 
        <div>
            <label>Filter:</label>
            <select onchange="location.href='rkrk_rt.php?mode=list&filter='+this.value">
                <option value="all"     <?= $filter==='all'?'selected':'' ?>>Alle</option>
                <option value="anzeigen"<?= $filter==='anzeigen'?'selected':'' ?>>Anzeigen</option>
            </select>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Datum</th>
            <th>Kategorie</th>
            <th>Ort / Betreff</th>
            <th>Total</th>
            <th>Aktionen</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $files = glob($invoicesDir . "/*.json");
        sort($files);

        if (!$files) {
            echo "<tr><td colspan='6'><i>Noch keine Rechnungen vorhanden.</i></td></tr>";
        }

        foreach ($files as $f):
            $data   = json_decode(file_get_contents($f), true);
            $idFile = basename($f, ".json");

            if ($filter === 'anzeigen' && !empty($data['admin_only'])) continue;

            $totalVal = (float)($data["total"] ?? 0);
            $totalStr = number_format($totalVal, 2, '.', '’');
            ?>
            <tr>
                <td><?= safe($idFile) ?></td>
                <td><?= safe($data["datum"] ?? '') ?></td>
                <td><?= safe($data["kategorie"] ?? '') ?></td>
                <td>
                    <?= safe($data["betreff"] ?? '') ?>
                    <?php if (!empty($data["ort"])): ?>
                        – <?= safe($data["ort"]) ?>
                    <?php endif; ?>
                </td>
                <td><?= $totalStr ?> CHF</td>
                <td>
                    <a class="btn" href="rkrk_rt.php?mode=form&id=<?= safe($idFile) ?>&kat=<?= safe($currentKategorieKey) ?>">Öffnen</a>
                    <a class="btn" href="rkrk_pdf.php?id=<?= safe($idFile) ?>" target="_blank">PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($mode === 'form'): ?>

<form id="invoiceForm">

    <input type="hidden" id="invoiceId" value="<?= safe($invoice['id']) ?>">

    <div class="rkrk-row">
        <div style="width:100%;">
            <label><b>Betreff</b></label>
            <input type="text" id="betreff"
                   value="<?= safe($invoice["betreff"] ?? "") ?>"
                   style="width:100%; font-size:1.2rem;">
        </div>
    </div>

    <div class="rkrk-row">
        <div>
            <label>Kategorie</label>
            <select id="kategorie">
                <?php foreach ($DEFAULTS["kategorien"] as $kat => $cfg): ?>
                    <option value="<?= $kat ?>" <?= $invoice["kategorie"]===$kat?'selected':'' ?>>
                        <?= $kat ?> – <?= safe($cfg["name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Kunde</label>
            <select id="kunde_select">
                <option value="">– Kunde auswählen –</option>
                <?php foreach ($CUSTOMERS as $key => $c): ?>
                    <option value="<?= safe($key) ?>">
                        <?= safe(trim(($c["firma"] ?? "")." ".($c["vorname"] ?? "")." ".($c["nachname"] ?? ""))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="rkrk-row">
        <div><label>Anrede</label><input type="text" id="anrede" value="<?= safe($invoice["kunde_details"]["anrede"] ?? "") ?>"></div>
        <div><label>Vorname</label><input type="text" id="vorname" value="<?= safe($invoice["kunde_details"]["vorname"] ?? "") ?>"></div>
        <div><label>Nachname</label><input type="text" id="nachname" value="<?= safe($invoice["kunde_details"]["nachname"] ?? "") ?>"></div>
    </div>

    <div class="rkrk-row">
        <div><label>Adresse</label><input type="text" id="adresse" value="<?= safe($invoice["kunde_details"]["adresse"] ?? "") ?>"></div>
        <div><label>PLZ</label><input type="text" id="plz" value="<?= safe($invoice["kunde_details"]["plz"] ?? "") ?>"></div>
        <div><label>Ort</label><input type="text" id="ort_kunde" value="<?= safe($invoice["kunde_details"]["ort"] ?? "") ?>"></div>
        <div><label>Land</label><input type="text" id="land" value="<?= safe($invoice["kunde_details"]["land"] ?? "Schweiz") ?>"></div>
    </div>

    <h2>Absender</h2>
    <div class="rkrk-row">
        <div><label>Vorname</label><input type="text" id="abs_vorname" value="<?= safe($invoice["absender"]["vorname"] ?? "") ?>"></div>
        <div><label>Nachname</label><input type="text" id="abs_nachname" value="<?= safe($invoice["absender"]["nachname"] ?? "") ?>"></div>
    </div>

    <h2>Rechnungspositionen</h2>

    <div id="sectionsContainer"></div>

    <button type="button" class="btn" id="addSectionBtn">➕ Sektion hinzufügen</button>

    <h2>Summen</h2>

	<div class="rkrk-row" style="display:flex; gap:20px; align-items:flex-end; margin-top:10px;">

		<div style="flex:1;">
			<label>Zwischensumme</label>
			<input type="text" id="zwischensumme" 
				   value="<?= safe($invoice["zwischensumme"]) ?>">
		</div>

		<div style="flex:1;">
			<label>Rabatt CHF</label>
			<input type="number" step="0.5" id="rabattCHF" 
				   value="<?= safe($invoice["rabattCHF"]) ?>">
		</div>

		<div style="flex:1;">
			<label>Rabatt %</label>
			<input type="number" step="0.5" id="rabattProzent" 
				   value="<?= safe($invoice["rabattProzent"]) ?>">
		</div>

	</div>
    <div class="rkrk-row">
        <div>
            <label>Total</label>
            <input type="text" id="total" value="<?= safe($invoice["total"]) ?>" style="font-weight:bold;">
        </div>
    </div>

    <button type="submit" class="btn-primary">💾 Speichern</button>
    <a href="rkrk_rt.php?mode=list&kat=<?= safe($currentKategorieKey) ?>" class="btn">Zurück zur Übersicht</a>

</form>

<script>
window.INVOICE_DATA = <?= json_encode($invoice,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.DEFAULTS     = <?= json_encode($DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.CUSTOMERS    = <?= json_encode($CUSTOMERS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php elseif ($mode === 'examples'): ?>

<?php
$exampleId   = $_GET['id'] ?? null;
$exampleData = null;

if ($exampleId) {
    $file = $examplesDir . "/$exampleId.json";
    if (file_exists($file)) {
        $exampleData = json_decode(file_get_contents($file), true);
    }
}

$positionen    = $exampleData['sektionen'][0]['positionen'] ?? array_values($STANDARDPOSITIONEN);
$titleEx       = $exampleData['sektionen'][0]['titel'] ?? '';
$rabattProzent = $exampleData['rabattProzent'] ?? 0;
$rabattCHF     = $exampleData['rabattCHF'] ?? 0;

// RISIKO über Flugzeit sortieren
usort($positionen, function($a, $b) {
    $la = strtolower($a['label'] ?? '');
    $lb = strtolower($b['label'] ?? '');
    if ($la === 'risiko' && $lb !== 'risiko') return -1;
    if ($lb === 'risiko' && $la !== 'risiko') return 1;
    return 0;
});
?>


<?php
$allExamples = [];
foreach (glob($examplesDir . "/*.json") as $file) {
    $id = basename($file, ".json");
    $data = json_decode(file_get_contents($file), true);

    $sek = $data['sektionen'][0] ?? [];
    $pos = $sek['positionen'] ?? [];

    // Total berechnen
    $sum = 0;
    foreach ($pos as $p) {
        $sum += ($p['preis'] ?? 0) * ($p['menge'] ?? 0);
    }

    $rabattCHF     = $data['rabattCHF']     ?? 0;
    $rabattProzent = $data['rabattProzent'] ?? 0;

    $rabattWert = $sum * ($rabattProzent / 100);
    $total = $sum - $rabattWert - $rabattCHF;

    $allExamples[] = [
        'id'    => $id,
        'title' => $sek['titel'] ?? '(ohne Titel)',
        'pos'   => $pos,
        'sum'   => $sum,
        'rabattCHF' => $rabattCHF,
        'rabattProzent' => $rabattProzent,
        'total' => $total
    ];
}
?>


<h2>Beispielrechnungen</h2>

<table id="exampleListTable" class="table" style="margin-bottom:20px;">
    <thead>
        <tr>
            <th style="width:30px;">⇅</th>
            <th>Beispiel</th>
            <th style="width:120px;">Aktionen</th>
        </tr>
    </thead>
    <tbody id="exampleListBody">
        <?php foreach ($allExamples as $ex): ?>
            <tr draggable="true" data-id="<?= $ex['id'] ?>">
                <td class="drag-handle">⇅</td>

                <td>
                    <div class="example-title"
                         data-id="<?= $ex['id'] ?>"
                         style="cursor:pointer; font-weight:bold;">
                        <?= safe($ex['title']) ?>
                    </div>

                    <div id="exampleContent_<?= $ex['id'] ?>"
                         style="display:block; margin-top:8px;">

                        <table class="table small" style="margin-bottom:10px;">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Menge</th>
                                    <th>Einheit</th>
                                    <th>Preis</th>
                                    <th>Risiko</th>
                                    <th>Bemerkung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ex['pos'] as $p): ?>
                                    <tr>
                                        <td><?= safe($p['label'] ?? '') ?></td>
                                        <td><?= safe($p['menge'] ?? '') ?></td>
                                        <td><?= safe($p['einheit'] ?? '') ?></td>
                                        <td><?= safe($p['preis'] ?? '') ?></td>
                                        <td><?= safe($p['risiko'] ?? '') ?></td>
                                        <td><?= safe($p['bemerkung'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="font-size:0.9rem; opacity:0.8;">
                            Zwischensumme: <b><?= number_format($ex['sum'],2) ?></b> CHF<br>
                            Rabatt %: <b><?= $ex['rabattProzent'] ?></b>%<br>
                            Rabatt CHF: <b><?= number_format($ex['rabattCHF'],2) ?></b> CHF<br>
                            Total: <b><?= number_format($ex['total'],2) ?></b> CHF
                        </div>

                    </div>
                </td>

                <td>
                    <button type="button"  class="btn-small edit-example"
                            data-id="<?= $ex['id'] ?>">✏ Bearbeiten</button>
                    <button type="button"  class="btn-small delete-example"
                            data-id="<?= $ex['id'] ?>">🗑 Löschen</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<script>
const listBody = document.getElementById("exampleListBody");

// --- EIN/AUSKLAPPEN ---
listBody.addEventListener("click", e => {
    const title = e.target.closest(".example-title");
    if (title) {
        const id = title.dataset.id;
        const box = document.getElementById("exampleContent_" + id);
        box.style.display = box.style.display === "none" ? "block" : "none";
        return;
    }
});

// --- BEARBEITEN ---
listBody.addEventListener("click", e => {
    const btn = e.target.closest(".edit-example");
    if (!btn) return;

    const id = btn.dataset.id;

    // Editor öffnen
    document.getElementById("newExampleBox").style.display = "block";
    document.getElementById("toggleNewExample").innerText =
        "➖ Neue Beispielrechnung ausblenden";

    // Beispiel laden
    window.location = "rkrk_rt.php?mode=examples&id=" + id + "&kat=<?= safe($currentKategorieKey) ?>";
});

// --- LÖSCHEN ---
listBody.addEventListener("click", e => {
    const btn = e.target.closest(".delete-example");
    if (!btn) return;

    const id = btn.dataset.id;

    if (!confirm("Beispiel wirklich löschen?")) return;

    fetch("rkrk_rt.php?mode=example_delete&id=" + id, {
        method: "POST"
    })
    .then(() => location.reload());
});

// --- DRAG & DROP ---
function enableExampleListDrag() {
    let dragging = null;

    listBody.querySelectorAll("tr").forEach(row => {

        row.addEventListener("dragstart", e => {
            if (!e.target.classList.contains("drag-handle")) {
                e.preventDefault();
                return;
            }
            dragging = row;
            row.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
        });

        row.addEventListener("dragend", () => {
            row.classList.remove("dragging");
            dragging = null;
        });

        row.addEventListener("dragover", e => {
            e.preventDefault();
            if (!dragging || dragging === row) return;

            const rect = row.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;

            if (e.clientY < mid) {
                listBody.insertBefore(dragging, row);
            } else {
                listBody.insertBefore(dragging, row.nextSibling);
            }
        });
    });
}

enableExampleListDrag();



</script>

<div id="toggleNewExample"
     style="cursor:pointer; font-weight:bold; padding:8px; background:#eee; border-radius:4px; margin-bottom:10px;">
    ➕ Neue Beispielrechnung erstellen
</div>

<div id="newExampleBox" style="display:none;">
	<div class="rkrk-row" style="justify-content: space-between; margin-bottom:20px;">
		<?php if ($exampleId): ?>
			<span style="opacity:0.7;">Beispiel-ID: <?= safe($exampleId) ?></span>
		<?php endif; ?>
	</div>

	<h2>Beispielrechnung <?= $exampleId ? "bearbeiten" : "erstellen" ?> – Kategorie: <?= safe($currentKategorie['label'] ?? $currentKategorieKey) ?></h2>

	<div class="rkrk-row">
		<div>
			<label>Titel</label>
			<input type="text" id="exampleTitle" value="<?= safe($titleEx) ?>" placeholder="Titel der Beispielrechnung">
		</div>
	</div>

	<table id="exampleTable">
		<thead>
		<tr>
			<th style="width:30px;">⇅</th>
			<th>Label</th>
			<th>Einheit</th>
			<th>Preis</th>
			<th style="width:60px;">Menge</th>
			<th>Risiko</th>
			<th>Bemerkung</th>
			<th>Total</th>
		</tr>
		</thead>
		 <tbody id="sortableBody">
		<?php foreach ($positionen as $p): ?>
			<?php
			$isRisiko = (strtolower($p['label'] ?? '') === 'risiko');

			// Risiko-Optionen extrahieren
			$risikoOpts = [];
			if ($isRisiko && !empty($STANDARDPOSITIONEN['RISIKO']['bemerkung'])) {
				if (preg_match_all('/([A-Za-zÄÖÜäöü]+)\s*[:\-]?\s*([0-9]+[.,]?[0-9]*)/', 
					$STANDARDPOSITIONEN['RISIKO']['bemerkung'], $m, PREG_SET_ORDER)) {
					foreach ($m as $o) {
						$risikoOpts[] = ['label' => $o[1], 'value' => str_replace(',', '.', $o[2])];
					}
				}
			}
			?>
			
			<tr data-preis="<?= safe($p['preis'] ?? 0) ?>" 
				draggable="true" 
				class="draggable <?= $isRisiko ? 'line-risiko' : '' ?>">

				<!-- Drag handle -->
				<td class="drag-handle">⇅</td>

				<!-- Label -->
				<td><?= safe($p['label'] ?? '') ?></td>

				<!-- Einheit (bei Risiko verstecken) -->
				<td>
					<?php if ($isRisiko): ?>
						<span style="opacity:0;">–</span>
					<?php else: ?>
						<?= safe($p['einheit'] ?? '') ?>
					<?php endif; ?>
				</td>

				<!-- Preis (bei Risiko verstecken) -->
				<td class="preis-cell">
					<?php if ($isRisiko): ?>
						<span style="opacity:0;">0.00</span>
					<?php else: ?>
						<?= number_format($p['preis'] ?? 0, 2) ?>
					<?php endif; ?>
				</td>

				<!-- Menge (bei Risiko verstecken) -->
				<td style="width:60px;">
					<?php if ($isRisiko): ?>
						<span style="opacity:0;">0</span>
					<?php else: ?>
						<input type="number" class="menge"
							   value="<?= safe($p['menge'] ?? 0) ?>"
							   min="0" step="0.1" style="width:100%;">
					<?php endif; ?>
				</td>

				<!-- Risiko-Select -->
				<td>
					<?php if ($isRisiko && $risikoOpts): ?>
						<select class="risiko">
							<?php
							$currentRisiko = $p['risiko'] ?? 1.0;
							foreach ($risikoOpts as $o): ?>
								<option value="<?= $o['value'] ?>" 
									<?= ($currentRisiko == $o['value']) ? 'selected' : '' ?>>
									<?= safe($o['label']) ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else: ?>
						–
					<?php endif; ?>
				</td>

				<!-- Bemerkung -->
				<td>
					<input type="text" class="bemerkung"
						   value="<?= safe($p['bemerkung'] ?? '') ?>"
						   placeholder="Bemerkung / Freikilometer">
				</td>

				<!-- Total -->
				<td class="total">0.00</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h3>Rabatt</h3>
	<div class="rkrk-row" style="align-items:flex-end;">
		<div style="max-width:150px;">
			<label>Rabatt %</label>
			<input type="number" id="rabatt" value="<?= safe($rabattProzent) ?>" min="0" max="100" step="1">
		</div>
		<div style="max-width:150px;">
			<label>Rabatt CHF</label>
			<input type="number" id="rabattCHF" value="<?= safe($rabattCHF) ?>" min="0" step="0.5">
		</div>
	</div>

	<div class="total-box" style="margin-top:10px;">
		<p>Zwischensumme: <strong id="summe">0.00</strong> <strong>(</strong>CHF - Rabatt %: <strong id="rabattWert">0.00</strong> CHF, Rabatt CHF: <strong id="rabattAbsolutWert">0.00</strong> CHF <strong>)</strong></p>
		<p>Total: <strong id="total">0.00</strong> CHF</p>
	</div>

	<br>

	<button class="btn-primary" id="saveExample">💾 Beispiel speichern</button>
	<a href="rkrk_rt.php?mode=list&kat=<?= safe($currentKategorieKey) ?>" class="btn">Zurück zur Übersicht</a>

	<?php endif; ?>

	</div>
</div>
<script>
document.getElementById("toggleNewExample").addEventListener("click", () => {
    const box = document.getElementById("newExampleBox");
    const open = box.style.display === "block";

    box.style.display = open ? "none" : "block";

    document.getElementById("toggleNewExample").innerText =
        open ? "➕ Neue Beispielrechnung erstellen"
             : "➖ Neue Beispielrechnung ausblenden";
});

// -------- Logos nach Kategorie --------
function updateLogosForCategory(kat) {
    const sky = document.getElementById("logo_sky");
    const rk  = document.getElementById("logo_rk");
    if (!sky || !rk) return;

    sky.style.display = "none";
    rk.style.display  = "none";

    if (kat === "SC" || kat === "IN" || kat === "MA" || kat === "XX") {
        sky.style.display = "inline-block";
    }
    if (kat === "RK") {
        rk.style.display = "inline-block";
    }
}
updateLogosForCategory("<?= safe($currentKategorieKey) ?>");

// -------- Freikilometer aus Bemerkung --------
function extractFreeKm(text) {
    const match = text.match(/(\d+([.,]\d+)?)/);
    if (!match) return 0;
    return parseFloat(match[1].replace(',', '.'));
}

// -------- Drag & Drop (Beispiele) --------
let dragSrc = null;
document.querySelectorAll('.draggable').forEach(row => {
    row.addEventListener('dragstart', e => {
        dragSrc = row;
        row.style.opacity = '0.4';
    });
    row.addEventListener('dragend', e => {
        row.style.opacity = '1';
    });
    row.addEventListener('dragover', e => {
        e.preventDefault();
        row.classList.add('drag-over');
    });
    row.addEventListener('dragleave', e => {
        row.classList.remove('drag-over');
    });
    row.addEventListener('drop', e => {
        e.preventDefault();
        row.classList.remove('drag-over');
        if (dragSrc !== row) {
            row.parentNode.insertBefore(dragSrc, row);
        }
    });
});

// -------- Risiko-Faktor aus RISIKO-Zeile (Beispiele) --------
function getExampleRisikoFaktor() {
    const rows = document.querySelectorAll('#exampleTable tbody tr');
    for (const tr of rows) {
        const label = tr.children[1].innerText.trim().toLowerCase();
        if (label === 'risiko') {
            const sel = tr.querySelector('.risiko');
            if (sel) return parseFloat(sel.value || 1.0);
        }
    }
    return 1.0;
}

// -------- Recalc Beispiele --------
function recalcExample() {
    const table = document.getElementById('exampleTable');
    if (!table) return;

    let sum = 0;
    const risikoFaktor = getExampleRisikoFaktor();

    table.querySelectorAll('tbody tr').forEach(tr => {
        const label = tr.children[1].innerText.trim().toLowerCase();
        const basePreis = parseFloat(tr.dataset.preis || 0);
        const menge = parseFloat(tr.querySelector('.menge')?.value || 0);
        const bemerkung = (tr.querySelector('.bemerkung')?.value || '').trim();
        const preisCell = tr.querySelector('.preis-cell');

        let total = 0;
        let effektiverPreis = basePreis;

        if ((label === 'flugzeit')  || (label === 'Programmierung & Vorbereitung') || (label === 'Pilot')) {
            effektiverPreis = basePreis * risikoFaktor;
            if (preisCell) {
                preisCell.innerText = effektiverPreis.toFixed(2) + " (" + basePreis.toFixed(2) + "×" + risikoFaktor + ")";
            }
            total = effektiverPreis * menge;
			} else if (label === 'fahrspesen') {
            const freieKm = extractFreeKm(bemerkung);
            let verrechenbareKm = menge - freieKm;
            if (verrechenbareKm < 0) verrechenbareKm = 0;
            total = basePreis * verrechenbareKm;
            if (preisCell) preisCell.innerText = basePreis.toFixed(2);
        } else if (label === 'risiko') {
            total = 0;
            if (preisCell) preisCell.innerText = basePreis.toFixed(2);
        } else {
            total = basePreis * menge;
            if (preisCell) preisCell.innerText = basePreis.toFixed(2);
        }

        const totalCell = tr.querySelector('.total');
        if (totalCell) totalCell.innerText = total.toFixed(2);
        sum += total;
    });

    const rabattProzent = parseFloat(document.getElementById('rabatt')?.value || 0);
    const rabattCHF = parseFloat(document.getElementById('rabattCHF')?.value || 0);

    const rabattWertProzent = sum * (rabattProzent / 100);
    const totalNachProzent = sum - rabattWertProzent;
    const total = totalNachProzent - rabattCHF;

    if (document.getElementById('summe')) {
        document.getElementById('summe').innerText = sum.toFixed(2);
        document.getElementById('rabattWert').innerText = rabattWertProzent.toFixed(2);
        document.getElementById('rabattAbsolutWert').innerText = rabattCHF.toFixed(2);
        document.getElementById('total').innerText = total.toFixed(2);
    }
}

['.menge', '.bemerkung', '.risiko'].forEach(sel => {
    document.querySelectorAll(sel).forEach(inp => inp.addEventListener('input', recalcExample));
});
['rabatt', 'rabattCHF'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', recalcExample);
});
recalcExample();

// -------- Beispiel speichern --------
const saveBtn = document.getElementById('saveExample');
if (saveBtn) {
    saveBtn.addEventListener('click', function () {
        const title = document.getElementById('exampleTitle').value.trim();
        if (!title) {
            alert('Bitte einen Titel eingeben.');
            return;
        }

        let pos = [];
        document.querySelectorAll('#exampleTable tbody tr').forEach(tr => {
            const labelText = tr.children[1].innerText.trim();
            const label = labelText.toLowerCase();
            const menge = parseFloat(tr.querySelector('.menge')?.value || 0);
            const risikoSel = tr.querySelector('.risiko');

            if (menge > 0 || label === 'risiko') {
				pos.push({
					label: labelText,
					menge: menge,
					einheit: tr.children[2].innerText,
					preis: parseFloat(tr.dataset.preis || 0),
					risiko: (label === 'risiko' && risikoSel) ? parseFloat(risikoSel.value || 1.0) : null,
					bemerkung: tr.querySelector('.bemerkung')?.value || ''
				});
            }
        });

        if (pos.length === 0) {
            alert('Bitte mindestens eine Menge > 0 eingeben.');
            return;
        }

        const rabattProzent = parseFloat(document.getElementById('rabatt')?.value || 0);
        const rabattCHF = parseFloat(document.getElementById('rabattCHF')?.value || 0);

        const data = {
            sektionen: [{
                titel: title,
                positionen: pos
            }],
            rabattProzent: rabattProzent,
            rabattCHF: rabattCHF
        };

        const params = new URLSearchParams(location.search);
        const currentId = params.get('id') || '';

        fetch('rkrk_rt.php?mode=example_save&id=' + encodeURIComponent(currentId) + '&kat=<?= safe($currentKategorieKey) ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.text())
        .then(id => {
            window.location = 'rkrk_rt.php?mode=examples&id=' + id + '&kat=<?= safe($currentKategorieKey) ?>';
        });
    });
}

function enableExampleTableDrag() {
    const tbody = document.getElementById("sortableBody");
    if (!tbody) return;

    let dragSrc = null;

    tbody.querySelectorAll("tr").forEach(row => {
        row.addEventListener("dragstart", e => {
            dragSrc = row;
            row.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
        });

        row.addEventListener("dragend", () => {
            row.classList.remove("dragging");
        });

        row.addEventListener("dragover", e => {
            e.preventDefault();
            const dragging = tbody.querySelector(".dragging");
            if (!dragging || dragging === row) return;

            const rect = row.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;

            if (e.clientY < mid) {
                tbody.insertBefore(dragging, row);
            } else {
                tbody.insertBefore(dragging, row.nextSibling);
            }
        });
    });

	function normalizeLine(line) {
		return {
			label:      line.label      ?? "",
			note:       line.note       ?? "",
			menge:      line.menge      ?? 0,
			einheit:    line.einheit    ?? "h",

			preis:      line.preis      ?? 0,
			base_preis: line.base_preis ?? line.preis ?? 0,

			risiko:     line.risiko     ?? null,
			total:      line.total      ?? 0
		};
	}

	function normalizeSections(invoice) {
		if (!invoice.sections) invoice.sections = [];

		invoice.sections.forEach(sec => {
			if (!sec.lines) sec.lines = [];
			sec.lines = sec.lines.map(normalizeLine);
		});
	}


</script>

<?php if ($mode === 'form'): ?>
<script>

	document.addEventListener("DOMContentLoaded", () => {
	  const invoice = window.INVOICE_DATA;
	  const defaults = window.DEFAULTS;
	  const customers = window.CUSTOMERS;

	  function formatCHF(n) {
		if (isNaN(n)) n = 0;
		return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "’");
	  }

	  function getInvoiceRisikoFaktor() {
		let faktor = 1.0;
		(invoice.sections || []).forEach(sec => {
		  (sec.lines || []).forEach(line => {
			if ((line.label || "").trim().toLowerCase() === "risiko") {
			  if (line.risiko) faktor = parseFloat(line.risiko) || 1.0;
			}
		  });
		});
		return faktor;
	  }

	  function recalcLine(line, tr) {
		const menge = parseFloat(line.menge) || 0;
		const preis = parseFloat(line.preis) || 0;
		const labelLower = (line.label || "").trim().toLowerCase();
		const note = line.note || "";
		const risikoFaktor = getInvoiceRisikoFaktor();

		let total = 0;

		// Risiko-relevante Labels
		const isRiskRelevant = isRiskRelevantLabel(line.label);
		if (isRiskRelevant) {
		  const base = preis;
		  const preisNeu = base * risikoFaktor;

		  const preisCell = tr.querySelector(".line-preis");
		  if (preisCell) {
			preisCell.value = preisNeu.toFixed(2);
			preisCell.title = `${base.toFixed(2)} × ${risikoFaktor}`;
			preisCell.style.background = "#fff7d6";
		  }
		  total = preisNeu * menge;
		}
		// Fahrspesen (Freikilometer)
		else if (labelLower.includes("fahrspesen") && line.einheit === "km") {
		  const match = note.match(/(\d+)\s*km/i);
		  const inkl = match ? parseFloat(match[1]) : 0;
		  const verrechenbar = Math.max(0, menge - inkl);
		  total = verrechenbar * preis;
		}
		// Risiko-Zeile selbst
		else if (labelLower === "risiko") {
		  total = 0;
		}
		// Standard
		else {
		  total = menge * preis;
		}

		line.total = total;

		const totalCell = tr.querySelector(".line-total");
		if (totalCell) totalCell.innerHTML = `<b>CHF ${formatCHF(total)}</b>`;

		recalcTotals();
	  }

	  function recalcTotals() {
		let sum = 0;
		(invoice.sections || []).forEach(sec => {
		  (sec.lines || []).forEach(l => sum += (l.total || 0));
		});

		const sumRounded = Math.round(sum * 10) / 10;
		invoice.zwischensumme = sumRounded;
		document.getElementById("zwischensumme").value = formatCHF(sumRounded);

		const rabattCHF = parseFloat(document.getElementById("rabattCHF").value) || 0;
		const rabattPro = parseFloat(document.getElementById("rabattProzent").value) || 0;
		const rabatt = rabattCHF + (sumRounded * (rabattPro / 100));

		const totalRounded = Math.round((sumRounded - rabatt) * 10) / 10;
		invoice.total = totalRounded;
		document.getElementById("total").value = formatCHF(totalRounded);
	  }

	function normalizeLine(line) {
		return {
			label:      line.label      ?? "",
			note:       line.note       ?? "",
			menge:      line.menge      ?? 0,
			einheit:    line.einheit    ?? "h",

			// Preisfelder
			preis:      line.preis      ?? 0,
			base_preis: line.base_preis ?? line.preis ?? 0,

			// Risiko
			risiko:     line.risiko     ?? null,

			// Total
			total:      line.total      ?? 0
		};
	}

	function normalizeSections(invoice) {
		if (!invoice.sections) return;

		invoice.sections.forEach(sec => {
			if (!sec.lines) sec.lines = [];
			sec.lines = sec.lines.map(normalizeLine);
		});
	}
	
    const kundeSelect = document.getElementById("kunde_select");
    if (kundeSelect) {
        kundeSelect.addEventListener("change", () => {
            const key = kundeSelect.value;
            if (!key || !customers[key]) return;
            const c = customers[key];
            document.getElementById("anrede").value    = c.anrede   || "";
            document.getElementById("vorname").value   = c.vorname  || "";
            document.getElementById("nachname").value  = c.nachname || "";
            document.getElementById("adresse").value   = c.adresse  || "";
            document.getElementById("plz").value       = c.plz      || "";
            document.getElementById("ort_kunde").value = c.ort      || "";
            document.getElementById("land").value      = c.land     || "";
        });
    }

    document.getElementById("betreff").addEventListener("input", e => {
        invoice.betreff = e.target.value;
    });

    function ensureSections() {
        if (!Array.isArray(invoice.sections) || invoice.sections.length === 0) {
            invoice.sections = [{
                title: "",
                lines: []
            }];
        }
    }

    function getGroupsForCurrentCategory() {
        const kat = document.getElementById("kategorie").value;
        const katCfg = defaults.kategorien[kat] || {};
        const gids = katCfg.gruppen || [];
        const result = [];
        gids.forEach(gid => {
            if (defaults.standardgruppen && defaults.standardgruppen[gid]) {
                result.push({
                    id: gid,
                    label: defaults.standardgruppen[gid].label || gid
                });
            }
        });
        return result;
    }

	 function renderSections() {
		ensureSections();
		sectionsContainer.innerHTML = "";

		const groupsForCat = getGroupsForCurrentCategory();

		invoice.sections.forEach((sec, sIdx) => {
			const wrapper = document.createElement("div");
			wrapper.className = "section-block";

			const titleInputId = "section_title_" + sIdx;
			const tableBodyId  = "section_body_" + sIdx;
			const groupSelectId= "section_group_" + sIdx;

			wrapper.innerHTML = `
				<div class="rkrk-row" style="justify-content: space-between; align-items:center;">
					<div style="flex:1;">
						<label>Sektionstitel</label>
						<input type="text" id="${titleInputId}" value="${sec.title || ""}">
					</div>
					<button type="button" class="btn section-delete" data-sec="${sIdx}" style="margin-left:10px;">🗑 Sektion</button>
				</div>

				<div class="rkrk-row" style="margin-top:10px; gap:10px; align-items:flex-end;">
					<div>
						<label>Gruppe aus Standardpositionen</label>
						<select id="${groupSelectId}">
							<option value="">– Gruppe wählen –</option>
							${groupsForCat.map(g => `<option value="${g.id}">${g.label}</option>`).join("")}
						</select>
					</div>
					<button type="button" class="btn section-add-group" data-sec="${sIdx}">➕ Gruppe einfügen</button>
					<button type="button" class="btn section-add-line" data-sec="${sIdx}">➕ Freie Zeile</button>
				</div>

				<table class="table rechnung" style="margin-top:10px;">
					<thead>
						<tr>
							<th style="width:40px;">Nr.</th>
							<th>Bezeichnung</th>
							<th>Bemerkungen</th>
							<th>Menge</th>
							<th>Einheit</th>
							<th>Preis</th>
							<th>Total</th>
							<th></th>
						</tr>
					</thead>
					<tbody id="${tableBodyId}"></tbody>
				</table>
			`;

			sectionsContainer.appendChild(wrapper);

			document.getElementById(titleInputId).addEventListener("input", e => {
				sec.title = e.target.value;
			});

			wrapper.querySelector(".section-delete").addEventListener("click", () => {
				invoice.sections.splice(sIdx, 1);
				renderSections();
				recalcTotals();
			});

			wrapper.querySelector(".section-add-line").addEventListener("click", () => {
				sec.lines.push({
					label: "",
					note: "",
					menge: 0,
					einheit: "h",
					preis: 0,
					risiko: null,
					total: 0
				});
				renderSections();
				recalcTotals();
			});

			wrapper.querySelector(".section-add-group").addEventListener("click", () => {
				const sel = document.getElementById(groupSelectId);
				const gid = sel.value;

				if (!gid || !defaults.standardgruppen[gid]) return;

				const grp = defaults.standardgruppen[gid];

				grp.positionen.forEach(pid => {
					const p = defaults.standardpositionen[pid];
					if (!p) return;

					sec.lines.push({
						label: p.label,
						note: p.bemerkung || "",
						menge: 0,
						einheit: p.einheit,
						preis: p.preis,
						risiko: null,
						total: p.preis
					});
				});

				renderSections();
				recalcTotals();
			});

			renderLinesForSection(sec, sIdx, tableBodyId);
		});

		renderLinesForSection(sec, sIdx, tableBodyId);
		enableDragAndDrop("#" + tableBodyId);
	}


	function renderLinesForSection(sec, sIdx, tbodyId) {
		const tbody = document.getElementById(tbodyId);
		tbody.innerHTML = "";

		sec.lines.forEach((line, index) => {
			const isRisiko = (line.label || "").trim().toLowerCase() === "risiko";
			const tr = document.createElement("tr");
			if (isRisiko) tr.classList.add("line-risiko");

			// --- RISIKO PARSER (robust) ---
			let risikoSelectHtml = "";
			if (isRisiko && defaults.standardpositionen?.RISIKO?.bemerkung) {
				const bem = defaults.standardpositionen.RISIKO.bemerkung;
				const regex = /([A-Za-zÄÖÜäöü]+)\s*[:\-]?\s*([0-9]+[.,]?[0-9]*)/g;

				const opts = [];
				let m;
				while ((m = regex.exec(bem)) !== null) {
					opts.push({
						label: m[1],
						value: m[2].replace(",", ".")
					});
				}

				const current = line.risiko || 1.0;

				risikoSelectHtml = `
					<select class="line-risiko-select">
						${opts.map(o => `
							<option value="${o.value}" ${current == o.value ? "selected" : ""}>
								${o.label}
							</option>
						`).join("")}
					</select>
				`;
			}

			tr.innerHTML = `
				<td>${index + 1}</td>

				<td><input class="line-label" value="${line.label || ""}"></td>

				<td class="note-cell">
					<input class="line-note" value="${line.note || ""}">
				</td>

				<td>
					${isRisiko
						? risikoSelectHtml
						: `<input type="number" step="0.1" class="line-menge" value="${line.menge || 0}">`
					}
				</td>

				<td>
					${isRisiko ? "" : `
						<select class="line-einheit">
							<option value="h"   ${line.einheit==="h"?"selected":""}>h</option>
							<option value="Stk" ${line.einheit==="Stk"?"selected":""}>Stk</option>
							<option value="km"  ${line.einheit==="km"?"selected":""}>km</option>
							<option value="–"   ${line.einheit==="–"?"selected":""}>–</option>
						</select>
					`}
				</td>

				<td>
					${isRisiko ? "" : `
						<input type="number" class="line-preis" value="${line.preis || 0}">
					`}
				</td>

				<td class="line-total">
					${isRisiko ? "" : `<b>CHF ${formatCHF(line.total || 0)}</b>`}
				</td>

				<td>
					<span class="line-move" data-move="up">▲</span>
					<span class="line-move" data-move="down">▼</span>
					<span class="line-move" data-move="delete">🗑</span>
				</td>
			`;

			// --- EVENTS ---
			tr.querySelector(".line-label").addEventListener("input", e => {
				line.label = e.target.value;
			});

			const noteInput = tr.querySelector(".line-note");
			if (noteInput) {
				noteInput.addEventListener("input", e => {
					line.note = e.target.value;
					recalcLine(line, tr);
				});
			}

			const risikoSelect = tr.querySelector(".line-risiko-select");
			if (risikoSelect) {
				risikoSelect.addEventListener("change", e => {

					line.risiko = parseFloat(e.target.value) || 1.0;

					// Alle risiko-relevanten Zeilen neu berechnen
					
					invoice.sections.forEach((sec2, sIdx2) => {
					  const tbody2 = document.getElementById(`section_body_${sIdx2}`);
					  if (!tbody2) return;

					  sec2.lines.forEach((l2, idx2) => {
						if (isRiskRelevantLabel(l2.label)) {
						  const tr2 = tbody2.querySelectorAll("tr")[idx2];
						  if (tr2) recalcLine(l2, tr2);
						}
					  });
					});

					recalcTotals();
					renderLinesForSection(sec, sIdx, tbodyId);
				});
			}


			const mengeInput = tr.querySelector(".line-menge");
			if (mengeInput) {
				mengeInput.addEventListener("input", e => {
					line.menge = parseFloat(e.target.value) || 0;
					recalcLine(line, tr);
				});
			}

			const einheitSelect = tr.querySelector(".line-einheit");
			if (einheitSelect) {
				einheitSelect.addEventListener("change", e => {
					line.einheit = e.target.value;
					recalcLine(line, tr);
				});
			}

			const preisInput = tr.querySelector(".line-preis");
			if (preisInput) {
				preisInput.addEventListener("input", e => {
					line.preis = parseFloat(e.target.value) || 0;
					recalcLine(line, tr);
				});
			}

			tr.querySelectorAll(".line-move").forEach(btn => {
				btn.addEventListener("click", () => {
					const action = btn.dataset.move;

					if (action === "delete") {
						sec.lines.splice(index, 1);
					}

					if (action === "up" && index > 0) {
						const tmp = sec.lines[index - 1];
						sec.lines[index - 1] = sec.lines[index];
						sec.lines[index] = tmp;
					}

					if (action === "down" && index < sec.lines.length - 1) {
						const tmp = sec.lines[index + 1];
						sec.lines[index + 1] = sec.lines[index];
						sec.lines[index] = tmp;
					}

					renderSections();
					recalcTotals();
				});
			});

			tbody.appendChild(tr);
		});
	}


    function recalcTotals() {
        let sum = 0;
        (invoice.sections || []).forEach(sec => {
            (sec.lines || []).forEach(l => {
                sum += (l.total || 0);
            });
        });

        const sumRounded = Math.round(sum * 10) / 10;
        invoice.zwischensumme = sumRounded;
        document.getElementById("zwischensumme").value = formatCHF(sumRounded);

        const rabattCHF = parseFloat(document.getElementById("rabattCHF").value) || 0;
        const rabattPro = parseFloat(document.getElementById("rabattProzent").value) || 0;

        let rabatt = rabattCHF + (sumRounded * (rabattPro / 100));
        let total = sumRounded - rabatt;
        const totalRounded = Math.round(total * 10) / 10;

        invoice.total = totalRounded;
        document.getElementById("total").value = formatCHF(totalRounded);
    }

    document.getElementById("rabattCHF").addEventListener("input", recalcTotals);
    document.getElementById("rabattProzent").addEventListener("input", recalcTotals);

    addSectionBtn.addEventListener("click", () => {
        invoice.sections.push({
            title: "",
            lines: []
        });
        renderSections();
        recalcTotals();
    });

    renderSections();
    recalcTotals();

    document.getElementById("invoiceForm").addEventListener("submit", async (e) => {
        e.preventDefault();

		const invoiceIdField = document.getElementById("invoiceId").value.trim();
		
        const payload = {  
			...(invoiceIdField ? { id: invoiceIdField } : {}),
			  betreff: document.getElementById("betreff").value,
			  kategorie: document.getElementById("kategorie").value,


            kunde_details: {
                anrede:   document.getElementById("anrede").value,
                vorname:  document.getElementById("vorname").value,
                nachname: document.getElementById("nachname").value,
                adresse:  document.getElementById("adresse").value,
                plz:      document.getElementById("plz").value,
                ort:      document.getElementById("ort_kunde").value,
                land:     document.getElementById("land").value
            },

            absender: {
                vorname:  document.getElementById("abs_vorname").value,
                nachname: document.getElementById("abs_nachname").value
            },

            ort: invoice.ort || "",
            datum: invoice.datum || "",
            einleitung: invoice.einleitung || "",
            gruss: invoice.gruss || "",
            rabattCHF: parseFloat(document.getElementById("rabattCHF").value) || 0,
            rabattProzent: parseFloat(document.getElementById("rabattProzent").value) || 0,
            zwischensumme: invoice.zwischensumme || 0,
            total: invoice.total || 0,
            sections: invoice.sections || []
        };


		const resp = await fetch("rkrk_rt.php?mode=invoice_save&kat=" + encodeURIComponent(document.getElementById("kategorie").value), {
		  method: "POST",
		  headers: {"Content-Type": "application/json"},
		  body: JSON.stringify(payload)
		});

        const data = await resp.json();
        if (data && data.id) {
            window.location = "rkrk_rt.php?mode=form&id=" + encodeURIComponent(data.id) + "&kat=<?= safe($currentKategorieKey) ?>";
        } else {
            alert("Fehler beim Speichern");
        }
    });
});
</script>
<?php endif; ?>

<script>
// Zentrale Risiko-Labels
const RISK_LABELS = [
    "flugzeit",
    "vorbereitung",
    "programmierung",
    "pilot"
];

// Hilfsfunktion: prüft ob Label risiko-relevant ist
function isRiskRelevantLabel(label) {
    const l = (label || "").trim().toLowerCase();
    return RISK_LABELS.some(key => l.includes(key));
}

function enableDragAndDrop(tbodySelector) {
    const tbody = document.querySelector(tbodySelector);
    if (!tbody) return;

    let draggingRow = null;

    tbody.querySelectorAll("tr").forEach(row => {

        row.addEventListener("dragstart", e => {
            // Nur starten, wenn der Klick auf der ersten Spalte (drag-handle) war
            if (!e.target.classList.contains("drag-handle")) {
                e.preventDefault();
                return;
            }

            draggingRow = row;
            row.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
        });

        row.addEventListener("dragend", () => {
            row.classList.remove("dragging");
            draggingRow = null;
        });

        row.addEventListener("dragover", e => {
            e.preventDefault();
            if (!draggingRow || draggingRow === row) return;

            const rect = row.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;

            if (e.clientY < mid) {
                tbody.insertBefore(draggingRow, row);
            } else {
                tbody.insertBefore(draggingRow, row.nextSibling);
            }
        });
    });
}

</script>

</body>
</html>
