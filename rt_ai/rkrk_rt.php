<?php
// rkrk_rt.php – FINAL
// Features:
// - IDs: Rechnung REYYYYKKNNNN, Beispiel BSPYYYYKKNNNN (Counter pro Jahr+Kategorie)
// - Menge step 0.25, keine negativen Werte
// - Preise/Total rechtsbündig, CHF Format "CHF 1’230.00"
// - Rabatt% wird beim Abzug auf 0.5 gerundet
// - DnD Sortierung Positionen innerhalb Sektion (1. Spalte Handle)
// - Beispiele Ansicht (mode=examples): nur Menge editierbar (+ Risiko Faktor), Zeilen mit menge 0 ausblenden,
//   Zeilen-Total anzeigen, Zeile hinzufügen, Speichern bleibt in mode=examples
// - Beispiel-Liste: Edit Button

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function chf_fmt($n){ return "CHF " . number_format((float)$n, 2, '.', '’'); }

$baseDir = __DIR__ . '/data';
$invoicesDir = $baseDir . '/invoices';
$examplesDir = $baseDir . '/examples';
$defaultsFile = $baseDir . '/invoice_default.json';
$customersFile = $baseDir . '/customers.json';
$invoiceCountersFile = $baseDir . '/invoice_counters.json';
$exampleCountersFile = $baseDir . '/example_counters.json';

@mkdir($invoicesDir, 0777, true);
@mkdir($examplesDir, 0777, true);

$DEFAULTS = file_exists($defaultsFile) ? (json_decode(@file_get_contents($defaultsFile), true) ?: []) : [];
$CUSTOMERS = file_exists($customersFile) ? (json_decode(@file_get_contents($customersFile), true) ?: []) : [];
$KATEGORIEN = isset($DEFAULTS['kategorien']) && is_array($DEFAULTS['kategorien']) ? $DEFAULTS['kategorien'] : [];


// ---------------------------------------------------------
// Kategorie Design: Logo aus logosets[kategorien[kat].logoset], Fallbacks wie zuvor
// ---------------------------------------------------------
function category_design(string $kat, array $KATEGORIEN, array $defaults = []): array {
  $kat = preg_replace('/[^A-Z]/','', strtoupper($kat));
  if($kat==='') $kat='SC';
  $conf = $KATEGORIEN[$kat] ?? [];

  $color = $conf['farbe'] ?? ($conf['color'] ?? '#333');

  $logo = '';
  $logosets = isset($defaults['logosets']) && is_array($defaults['logosets']) ? $defaults['logosets'] : [];
  $lsKey = isset($conf['logoset']) ? trim((string)$conf['logoset']) : '';
  if ($lsKey !== '' && isset($logosets[$lsKey]['logo']) && $logosets[$lsKey]['logo'] !== '') {
    $logo = '/' . ltrim((string)$logosets[$lsKey]['logo'], '/');
  }
  if ($logo === '') {
    if(!empty($conf['logo'])) $logo = (string)$conf['logo'];
    elseif(!empty($conf['logo_sky'])) $logo = '/images/logo_sky.png';
    elseif(!empty($conf['logo_rk'])) $logo = '/images/logo_rk.png';
    elseif(!empty($conf['watermarks'])) $logo = '/' . ltrim((string)$conf['watermarks'], '/');
    elseif(!empty($conf['swissqrcode'])) $logo = '/' . ltrim((string)$conf['swissqrcode'], '/');
  }

  if($logo !== '' && strpos($logo,'http')!==0){
    $check = __DIR__ . '/' . ltrim($logo,'/');
    if(!is_file($check)){
      $alt1 = __DIR__ . '/images/' . basename($logo);
      $alt2 = __DIR__ . '/assets/' . basename($logo);
      if(is_file($alt1)) $logo = '/images/' . basename($logo);
      elseif(is_file($alt2)) $logo = '/assets/' . basename($logo);
      else $logo = '';
    }
  }

  return ['kat'=>$kat,'color'=>$color,'logo'=>$logo];
}

$mode = $_GET['mode'] ?? 'list';
// Backward compatibility: ältere Links nutzen ?mode=form wie rkrk_rt_260409.php
if ($mode === 'form') {
  $mode = 'invoice';
}

// -------------------------
// Counter helpers
// -------------------------
function readCounters($file){
  if(!file_exists($file)) return [];
  $c = json_decode(@file_get_contents($file), true);
  return is_array($c) ? $c : [];
}
function writeCounters($file, $arr){
  $json = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if($json === false) return false;
  return file_put_contents($file, $json, LOCK_EX) !== false;
}

function nextInvoiceId($kat, $file){
  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)$kat));
  if($kat==='') $kat='SC';
  $year = date('Y');
  $key = $year . $kat;
  $c = readCounters($file);
  // backward compat: old schema maybe only category
  if(!isset($c[$key]) || !is_numeric($c[$key])){
    if(isset($c[$kat]) && is_numeric($c[$kat])) $c[$key] = (int)$c[$kat];
    else $c[$key] = 1;
  }
  $nr = (int)$c[$key];
  $c[$key] = $nr + 1;
  writeCounters($file, $c);
  return 'RE' . $year . $kat . str_pad((string)$nr, 4, '0', STR_PAD_LEFT);
}

function nextExampleId($kat, $file){
  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)$kat));
  if($kat==='') $kat='SC';
  $year = date('Y');
  $key = $year . $kat;
  $c = readCounters($file);
  // backward compat: old schema global BSP or category
  if(!isset($c[$key]) || !is_numeric($c[$key])){
    if(isset($c['BSP']) && is_numeric($c['BSP'])) $c[$key] = (int)$c['BSP'];
    elseif(isset($c[$kat]) && is_numeric($c[$kat])) $c[$key] = (int)$c[$kat];
    else $c[$key] = 1;
  }
  $nr = (int)$c[$key];
  $c[$key] = $nr + 1;
  writeCounters($file, $c);
  return 'BSP' . $year . $kat . str_pad((string)$nr, 4, '0', STR_PAD_LEFT);
}

// -------------------------
// Risiko-Logik
// -------------------------
function riskRelevant($label){
  $l = strtolower(trim((string)$label));
  $keys = ['flugzeit','vorbereitung','programmierung','pilot'];
  foreach($keys as $k){ if(strpos($l,$k)!==false) return true; }
  return false;
}
function extractFreeKm($note){
  $note = (string)$note;
  if(preg_match('/(\d+)\s*km/i', $note, $m)) return (float)$m[1];
  if(preg_match('/(\d+)/', $note, $m)) return (float)$m[1];
  return 0.0;
}

function normalizeDoc(&$data){
  if(!isset($data['kunde_details']) || !is_array($data['kunde_details'])) $data['kunde_details'] = [];
  if(!isset($data['absender']) || !is_array($data['absender'])) $data['absender'] = [];
  if(!isset($data['datum']) || $data['datum']==='') $data['datum'] = date('Y-m-d');
  if(!isset($data['sections']) || !is_array($data['sections'])) $data['sections'] = [];
  foreach($data['sections'] as &$sec){
    if(!isset($sec['title'])) $sec['title'] = '';
    if(!isset($sec['anmerkungen'])) $sec['anmerkungen'] = '';
    if(!isset($sec['lines']) || !is_array($sec['lines'])) $sec['lines'] = [];
    foreach($sec['lines'] as &$l){
      if(!isset($l['label'])) $l['label'] = '';
      if(!isset($l['note'])) $l['note'] = '';
      if(!isset($l['einheit'])) $l['einheit'] = 'h';
      $l['menge'] = (float)($l['menge'] ?? 0);
      if($l['menge'] < 0) $l['menge'] = 0;
      $l['preis'] = (float)($l['preis'] ?? 0);
      if($l['preis'] < 0) $l['preis'] = 0;
      $l['total'] = (float)($l['total'] ?? 0);
    }
  }
  unset($sec,$l);
}

function roundToHalf($x){
  return round(((float)$x)/0.5) * 0.5;
}

function computeTotals(&$data){
  normalizeDoc($data);

  $risikoF = 1.0;
  foreach($data['sections'] as $sec){
    foreach($sec['lines'] as $line){
      $lab = strtolower(trim((string)($line['label'] ?? '')));
      if($lab==='risiko' && isset($line['risiko'])){
        $v = (float)$line['risiko'];
        if($v>0) $risikoF = $v;
      }
    }
  }

  $sum = 0.0;
  foreach($data['sections'] as &$sec){
    foreach($sec['lines'] as &$line){
      $label = (string)($line['label'] ?? '');
      $ll = strtolower(trim($label));
      $menge = (float)($line['menge'] ?? 0);
      if($menge < 0) $menge = 0;
      $preis = (float)($line['preis'] ?? 0);
      if($preis < 0) $preis = 0;
      $einheit = (string)($line['einheit'] ?? 'h');
      $note = (string)($line['note'] ?? '');

      $total = 0.0;
      if($ll==='risiko'){
        $line['menge']=1; $line['preis']=0; $line['einheit']='h';
        $total = 0.0;
      }
      elseif(strpos($ll,'fahrspesen')!==false && strtolower($einheit)==='km'){
        $inkl = extractFreeKm($note);
        $verr = $menge - $inkl; if($verr<0) $verr=0;
        $total = $verr * $preis;
      }
      elseif(riskRelevant($label)){
        $base = isset($line['base_preis']) ? (float)$line['base_preis'] : $preis;
        if($base < 0) $base = 0;
        $line['base_preis'] = $base;
        $line['preis'] = $base; // Grundpreis
        $total = $menge * ($base * $risikoF);
      }
      else{
        $total = $menge * $preis;
      }

      $line['menge'] = $menge;
      $line['preis'] = (float)$line['preis'];
      $line['einheit'] = $einheit;
      $line['note'] = $note;
      $line['total'] = round($total,2);

      $sum += $line['total'];
    }
  }
  unset($sec,$line);

  $rabattCHF = (float)($data['rabattCHF'] ?? 0);
  if($rabattCHF < 0) $rabattCHF = 0;
  $rabattProzent = (float)($data['rabattProzent'] ?? 0);
  if($rabattProzent < 0) $rabattProzent = 0;
  $rabattProzent = roundToHalf($rabattProzent); // ⭐️ beim Abzug runden

  $data['rabattCHF'] = $rabattCHF;
  $data['rabattProzent'] = $rabattProzent;

  $data['zwischensumme'] = round($sum,2);
  $data['total'] = round($sum - ($sum*($rabattProzent/100.0)) - $rabattCHF, 2);
}

// -------------------------
// API: save invoice
// -------------------------
if($mode==='save_invoice'){
  header('Content-Type: application/json; charset=utf-8');
  $data = json_decode(file_get_contents('php://input'), true);
  if(!is_array($data)) { http_response_code(400); echo json_encode(['error'=>'invalid json']); exit; }

  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)($data['kategorie'] ?? 'SC')));
  if($kat==='') $kat='SC';

  $id = trim((string)($data['id'] ?? ''));
  if($id==='' || $id==='null') $id = nextInvoiceId($kat, $invoiceCountersFile);
  else $id = basename($id);

  $data['id']=$id;
  $data['kategorie']=$kat;
  if(!isset($data['datum']) || $data['datum']==='') $data['datum']=date('Y-m-d');

  computeTotals($data);

  $file = $invoicesDir.'/'.basename($id).'.json';
  $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  if($json===false){ http_response_code(500); echo json_encode(['error'=>'json_encode failed']); exit; }
  if(file_put_contents($file,$json,LOCK_EX)===false){ http_response_code(500); echo json_encode(['error'=>'cannot write']); exit; }

  echo json_encode(['ok'=>true,'id'=>$id]);
  exit;
}

// -------------------------
// API: save example
// -------------------------
if($mode==='save_example'){
  header('Content-Type: application/json; charset=utf-8');
  $data = json_decode(file_get_contents('php://input'), true);
  if(!is_array($data)) { http_response_code(400); echo json_encode(['error'=>'invalid json']); exit; }

  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)($data['kategorie'] ?? 'SC')));
  if($kat==='') $kat='SC';

  $id = trim((string)($data['id'] ?? ''));
  if($id==='' || $id==='null') $id = nextExampleId($kat, $exampleCountersFile);
  else $id = basename($id);

  $data['id']=$id;
  $data['kategorie']=$kat;
  if(!isset($data['datum']) || $data['datum']==='') $data['datum']=date('Y-m-d');

  // schema parity (not required for UI)
  if(!isset($data['kunde_details']) || !is_array($data['kunde_details'])) $data['kunde_details']=['anrede'=>'','vorname'=>'','nachname'=>'','adresse'=>'','plz'=>'','ort'=>'','land'=>''];
  if(!isset($data['absender']) || !is_array($data['absender'])) $data['absender']=['vorname'=>'','nachname'=>''];

  computeTotals($data);

  $file = $examplesDir.'/'.basename($id).'.json';
  $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  if($json===false){ http_response_code(500); echo json_encode(['error'=>'json_encode failed']); exit; }
  if(file_put_contents($file,$json,LOCK_EX)===false){ http_response_code(500); echo json_encode(['error'=>'cannot write']); exit; }

  echo json_encode(['ok'=>true,'id'=>$id]);
  exit;
}

// delete
if($mode==='delete_invoice'){
  $id=basename($_GET['id']??'');
  if($id){ $file=$invoicesDir.'/'.$id.'.json'; if(is_file($file)) @unlink($file); }
  header('Location: ?mode=list');
  exit;
}
if($mode==='delete_example'){
  $id=basename($_GET['id']??'');
  if($id){ $file=$examplesDir.'/'.$id.'.json'; if(is_file($file)) @unlink($file); }
  header('Location: ?mode=examples');
  exit;
}

// load doc
$isExample = ($mode==='example');
$doc=null;
if(($mode==='invoice' || $mode==='example') && !empty($_GET['id'])){
  $fid=basename($_GET['id']);
  $dir=$isExample?$examplesDir:$invoicesDir;
  $file=$dir.'/'.$fid.'.json';
  if(is_file($file)){
    $doc=json_decode(file_get_contents($file),true);
    if(!is_array($doc)) $doc=null;
  }
}
if(!$doc){
  $doc=[
    'id'=>'','betreff'=>'','kategorie'=>'SC','datum'=>date('Y-m-d'),
    'kunde_details'=>['anrede'=>'','vorname'=>'','nachname'=>'','adresse'=>'','plz'=>'','ort'=>'','land'=>'Schweiz'],
    'absender'=>['vorname'=>'','nachname'=>''],
    'rabattCHF'=>0,'rabattProzent'=>0,'zwischensumme'=>0,'total'=>0,
    'sections'=>[['title'=>'','lines'=>[],'anmerkungen'=>'']]
  ];
}
normalizeDoc($doc);

// load examples for view
$examplesAll=[];
if($mode==='examples'){
  $files=glob($examplesDir.'/*.json');
  sort($files);
  foreach($files as $f){
    $d=json_decode(@file_get_contents($f),true);
    if(is_array($d)){ normalizeDoc($d); $examplesAll[]=$d; }
  }
}

$title = ($mode==='invoice')?'Rechnung bearbeiten':(($mode==='example')?'Beispielrechnung bearbeiten':(($mode==='examples')?'Beispielrechnungen – Ansicht':'Rechnungsübersicht'));

// Für Live-Logo/Farbe beim Kategorie-Wechsel im Editor (JS)
$designByKat = [];
foreach (array_keys($KATEGORIEN) as $_kat) {
  $designByKat[$_kat] = category_design($_kat, $KATEGORIEN, $DEFAULTS);
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <link rel="stylesheet" href="rkrk_rt.css">
  <style>
    
    /* Logo oben rechts (Listen + Editor) */
    .top-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}
    .top-logo{max-height:70px;max-width:220px;object-fit:contain;}
    .line-risiko{background:#fff3b0;}
    .section-title-big{font-size:1.25rem;font-weight:600;}
    .total-big{font-size:1.4rem;font-weight:700;}
/* Button-Höhen harmonisieren */
    .btn, .btn-primary { height:36px; line-height:36px; padding:0 14px; display:inline-block; box-sizing:border-box; }

    /* Tabellen rechtsbündig für Preise */
    td.price, th.price, td.total, th.total { text-align:right; }

    .example-card{border:1px solid #c8d9c0; border-radius:10px; padding:12px; margin:12px 0; background:#fff;}
    .example-head{display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; justify-content:space-between;}
    .example-head h3{margin:0;}
    .mini{font-size:.9rem; opacity:.8;}
    .dnd-handle{cursor:grab; user-select:none; padding:0 6px;}
    .drag-handle{cursor:grab; user-select:none; padding:0 6px;}
    .section-dnd-handle{cursor:grab; user-select:none; padding:0 8px; font-size:1.1rem; opacity:.7;}
    .section-drag-over{outline:2px dashed var(--kat-color, #6F8B5E); outline-offset:2px;}
    .row-dragging{opacity:.5;}
    .pill{display:inline-block; padding:2px 8px; border:1px solid #c8d9c0; border-radius:999px; font-size:.85rem;}
  </style>
</head>
<body>
<div class="page">

<?php if($mode==='list'): ?>

<div class="top-header"><h1>Rechnungsübersicht</h1><img class="top-logo" src="images/logo.png" alt="Logo"></div>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=form">Neue Rechnung</a>
  <a class="btn" href="?mode=example">Neue Beispielrechnung</a>
  <a class="btn" href="?mode=examples">Beispielrechnungen</a>
</div>

<h2>Rechnungen</h2>
<table>
  <thead><tr><th>ID</th><th>Datum</th><th>Kategorie</th><th>Betreff</th><th class="total">Total</th><th>Aktionen</th></tr></thead>
  <tbody>
  <?php
    $files=glob($invoicesDir.'/*.json'); sort($files);
    if(!$files){ echo "<tr><td colspan='6'><i>Noch keine Rechnungen vorhanden.</i></td></tr>"; }
    foreach($files as $f){
      $data=json_decode(@file_get_contents($f),true)?:[];
      $id=basename($f,'.json');
      $datum=h($data['datum']??'');
      $totalVal=(float)($data['total']??0);
      echo '<tr>';
      echo '<td>'.h($id).'</td>';
      echo '<td>'.$datum.'</td>';
      echo '<td>'.h($data['kategorie']??'').'</td>';
      echo '<td>'.h($data['betreff']??'').'</td>';
      echo '<td class="total">'.h(chf_fmt($totalVal)).'</td>';
      echo '<td>';
      echo '<a class="btn" href="?mode=form&id='.rawurlencode($id).'">Öffnen</a> ';
      echo '<a class="btn" href="rkrk_pdf.php?id='.rawurlencode($id).'" target="_blank">PDF</a> ';
      echo '<a class="btn" href="?mode=delete_invoice&id='.rawurlencode($id).'" onclick="return confirm(\'Rechnung wirklich löschen?\')">🗑</a>';
      echo '</td>';
      echo '</tr>';
    }
  ?>
  </tbody>
</table>

<?php elseif($mode==='invoice' || $mode==='example'): ?>
<?php $design = category_design(($doc['kategorie'] ?? 'SC'), $KATEGORIEN, $DEFAULTS); ?>
<style>:root{--kat-color:<?=h($design['color'])?>;}</style>
<div class="top-header">
  <h1><?= $isExample ? 'Beispielrechnung bearbeiten' : 'Rechnung bearbeiten' ?></h1>
  <img id="editorLogo" class="top-logo" alt="Logo" src="<?= !empty($design['logo']) ? h($design['logo']) : '' ?>" style="<?= empty($design['logo']) ? 'display:none;' : '' ?>">
</div>
<form id="docForm">
  <input type="hidden" id="docId" value="<?= h($doc['id']) ?>">

  <h2>Grunddaten</h2>
  <div class="rkrk-row">
    <div style="flex:2;">
      <label><b>Betreff</b></label>
      <input type="text" id="betreff" value="<?= h($doc['betreff']) ?>" style="font-size:1.1rem;">
    </div>
    <div>
      <label>Kategorie</label>
      <select id="kategorie">
        <?php foreach(array_keys($KATEGORIEN) as $k): ?>
          <option value="<?= h($k) ?>" <?= ($doc['kategorie']===$k)?'selected':'' ?>><?= h($k) ?> – <?= h($KATEGORIEN[$k]['name'] ?? '') ?></option>
        <?php endforeach; ?>
        <?php if(!$KATEGORIEN): ?><option value="SC">SC</option><?php endif; ?>
      </select>
    </div>
    <div>
      <label>Datum</label>
      <input type="date" id="datum" value="<?= h($doc['datum'] ?? date('Y-m-d')) ?>">
    </div>
  </div>

  <?php if(!$isExample): ?>
  <h2>Kunde</h2>
  <div class="rkrk-row">
    <div>
      <label>Kunde auswählen</label>
      <select id="kunde_select">
        <option value="">– Kunde auswählen –</option>
        <?php foreach($CUSTOMERS as $key => $c): ?>
          <option value="<?= h($key) ?>"><?= h(trim(($c['firma'] ?? '').' '.($c['vorname'] ?? '').' '.($c['nachname'] ?? ''))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <h2>Kundendaten</h2>
  <div class="rkrk-row">
    <div><label>Anrede</label><input type="text" id="anrede" value="<?= h($doc['kunde_details']['anrede'] ?? '') ?>"></div>
    <div><label>Vorname</label><input type="text" id="vorname" value="<?= h($doc['kunde_details']['vorname'] ?? '') ?>"></div>
    <div><label>Nachname</label><input type="text" id="nachname" value="<?= h($doc['kunde_details']['nachname'] ?? '') ?>"></div>
  </div>
  <div class="rkrk-row">
    <div><label>Adresse</label><input type="text" id="adresse" value="<?= h($doc['kunde_details']['adresse'] ?? '') ?>"></div>
    <div><label>PLZ</label><input type="text" id="plz" value="<?= h($doc['kunde_details']['plz'] ?? '') ?>"></div>
    <div><label>Ort</label><input type="text" id="ort_kunde" value="<?= h($doc['kunde_details']['ort'] ?? '') ?>"></div>
    <div><label>Land</label><input type="text" id="land" value="<?= h($doc['kunde_details']['land'] ?? 'Schweiz') ?>"></div>
  </div>

  <h2>Absender</h2>
  <div class="rkrk-row">
    <div><label>Vorname</label><input type="text" id="abs_vorname" value="<?= h($doc['absender']['vorname'] ?? '') ?>"></div>
    <div><label>Nachname</label><input type="text" id="abs_nachname" value="<?= h($doc['absender']['nachname'] ?? '') ?>"></div>
  </div>
  <?php else: ?>
    <div class="mini">Hinweis: Beispielrechnungen brauchen keine Kundendaten und keinen Absender.</div>
  <?php endif; ?>

  <h2>Rechnungspositionen</h2>
  <div id="sectionsContainer"></div>
  <button type="button" class="btn" id="addSectionBtn">➕ Sektion hinzufügen</button>

  <h2>Summen</h2>
  <div class="rkrk-row" style="align-items:flex-end;">
    <div><label>Zwischensumme</label><input type="text" id="zwischensumme" readonly value="<?= h(chf_fmt($doc['zwischensumme'] ?? 0)) ?>"></div>
    <div><label>Rabatt CHF</label><input type="number" step="0.5" min="0" id="rabattCHF" value="<?= h($doc['rabattCHF'] ?? 0) ?>"></div>
    <div><label>Rabatt %</label><input type="number" step="0.5" min="0" id="rabattProzent" value="<?= h($doc['rabattProzent'] ?? 0) ?>"></div>
    <div><label>Total</label><input type="text" id="total" readonly style="font-weight:700;" value="<?= h(chf_fmt($doc['total'] ?? 0)) ?>"></div>
  </div>

  <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
    <button type="submit" class="btn-primary">💾 Speichern</button>
    <?php if($isExample): ?>
      <a class="btn" href="?mode=examples">Zurück zu Beispielen</a>
    <?php else: ?>
      <a class="btn" href="?mode=list">Zurück</a>
    <?php endif; ?>
  </div>
</form>

<script>
var IS_EXAMPLE = <?= $isExample ? 'true' : 'false' ?>;
var SAVE_MODE = IS_EXAMPLE ? 'save_example' : 'save_invoice';
var AFTER_SAVE = IS_EXAMPLE ? '?mode=examples' : '?mode=list';

var DOC = <?= json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var CUSTOMERS = <?= json_encode($CUSTOMERS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var DEFAULTS = <?= json_encode($DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var DESIGN_BY_KAT = <?= json_encode($designByKat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;


function chf(n){
  n = Number(n); if(isNaN(n)) n=0;
  var s = n.toFixed(2);
  var parts = s.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "’");
  return 'CHF ' + parts.join('.');
}
function roundHalf(x){ return Math.round((Number(x)||0)/0.5)*0.5; }
function riskRelevant(label){ var l=String(label||'').trim().toLowerCase(); return (l.indexOf('flugzeit')>=0)||(l.indexOf('vorbereitung')>=0)||(l.indexOf('programmierung')>=0)||(l.indexOf('pilot')>=0); }
function extractFreeKm(note){ note=String(note||''); var m=note.match(/(\d+)\s*km/i); if(m) return Number(m[1])||0; m=note.match(/(\d+)/); if(m) return Number(m[1])||0; return 0; }
function getRisikoFaktor(){ var f=1.0; (DOC.sections||[]).forEach(function(sec){ (sec.lines||[]).forEach(function(l){ if(String(l.label||'').trim().toLowerCase()==='risiko'){ var v=Number(l.risiko); if(!isNaN(v)&&v>0) f=v; } }); }); return f; }
function displayedPrice(line, risikoF){
  if(riskRelevant(line.label)){
    var base=(line.base_preis!==undefined && line.base_preis!==null)?Number(line.base_preis):Number(line.preis||0);
    return base*risikoF;
  }
  return Number(line.preis||0);
}
function parseRisikoOptions(){
  var bem='';
  try{
    if(DEFAULTS && DEFAULTS.standardpositionen && DEFAULTS.standardpositionen.RISIKO && DEFAULTS.standardpositionen.RISIKO.bemerkung){
      bem = String(DEFAULTS.standardpositionen.RISIKO.bemerkung);
    } else if(DEFAULTS && DEFAULTS.risiko_bemerkung){
      bem = String(DEFAULTS.risiko_bemerkung);
    }
  }catch(e){}
  var opts=[];
  var re2=/([^,;\n]+?)\s*[:=-]\s*([0-9]+[.,]?[0-9]*)/g;
  var m;
  while((m=re2.exec(bem))!==null){
    opts.push({label:m[1].trim(), value:Number(String(m[2]).replace(',','.'))});
  }
  if(opts.length===0){
    opts=[{label:'1.0',value:1.0},{label:'1.2',value:1.2},{label:'1.5',value:1.5},{label:'2.0',value:2.0}];
  }
  return opts;
}

/** Kategorien mit gruppen[] + standardgruppen (wie rkrk_rt_260409.php) */
function groupIdsForCategory(kat){
  var kc = DEFAULTS && DEFAULTS.kategorien && DEFAULTS.kategorien[kat];
  if(!kc) return [];
  return Array.isArray(kc.gruppen) ? kc.gruppen : [];
}
function lineFromStandardposition(pid){
  var p = DEFAULTS && DEFAULTS.standardpositionen && DEFAULTS.standardpositionen[pid];
  if(!p) return null;
  var line={
    label:p.label||'',
    note:p.bemerkung||'',
    menge:0.25,
    einheit:p.einheit||'h',
    preis:Number(p.preis||0),
    total:0
  };
  if(String(line.label).trim().toLowerCase()==='risiko'){
    line.menge=1; line.preis=0; line.einheit='h'; line.risiko=1;
  }
  return line;
}
function positionsFromGroupId(gid){
  var g = DEFAULTS && DEFAULTS.standardgruppen && DEFAULTS.standardgruppen[gid];
  var pos = DEFAULTS && DEFAULTS.standardpositionen;
  if(!g || !pos || !Array.isArray(g.positionen)) return [];
  var out = [];
  g.positionen.forEach(function(pid){
    var line = lineFromStandardposition(pid);
    if(line) out.push(line);
  });
  return out;
}
function addStdPosFromPid(sec, pid){
  var line = lineFromStandardposition(pid);
  if(line) sec.lines.push(line);
}
function addGroupLinesToSection(sec, gid){
  positionsFromGroupId(gid).forEach(function(line){ sec.lines.push(line); });
}
/** Select: zuerst Standardgruppen (Reihenfolge wie in Kategorie), unten Optgroup Standardpositionen */
function fillPositionSelect(sel, kat){
  sel.innerHTML='';
  var o0=document.createElement('option');
  o0.value=''; o0.textContent='– wählen –';
  sel.appendChild(o0);

  var ogGrp=document.createElement('optgroup');
  ogGrp.label='Standardgruppen';
  groupIdsForCategory(kat).forEach(function(gid){
    var g=DEFAULTS.standardgruppen && DEFAULTS.standardgruppen[gid];
    var op=document.createElement('option');
    op.value='g:'+gid;
    op.textContent=(g && g.label) ? g.label : gid;
    ogGrp.appendChild(op);
  });
  if(ogGrp.children.length) sel.appendChild(ogGrp);

  var ogPos=document.createElement('optgroup');
  ogPos.label='Standardpositionen';
  var sp=DEFAULTS.standardpositionen||{};
  Object.keys(sp).forEach(function(pid){
    var p=sp[pid];
    if(!p) return;
    var op=document.createElement('option');
    op.value='p:'+pid;
    op.textContent=(p.label||pid)+' ('+(p.einheit||'')+')';
    ogPos.appendChild(op);
  });
  if(ogPos.children.length) sel.appendChild(ogPos);
}
function addLinesFromChoice(sec, choice){
  if(!choice) return;
  if(choice.indexOf('g:')===0){ addGroupLinesToSection(sec, choice.slice(2)); return; }
  if(choice.indexOf('p:')===0){ addStdPosFromPid(sec, choice.slice(2)); }
}

function ensureSections(){
  if(!Array.isArray(DOC.sections)||DOC.sections.length===0) DOC.sections=[{title:'',lines:[],anmerkungen:''}];
  DOC.sections.forEach(function(sec){
    if(!Array.isArray(sec.lines)) sec.lines=[];
    if(sec.anmerkungen===undefined||sec.anmerkungen===null) sec.anmerkungen='';
  });
}

function recalcAll(){
  var sum=0, risikoF=getRisikoFaktor();
  (DOC.sections||[]).forEach(function(sec){
    (sec.lines||[]).forEach(function(l){
      var label=String(l.label||''), ll=label.trim().toLowerCase();
      var menge=Number(l.menge)||0; if(menge<0) menge=0;
      var preis=Number(l.preis)||0; if(preis<0) preis=0;
      var einheit=String(l.einheit||'h');
      var note=String(l.note||'');
      var total=0;
      if(ll==='risiko'){ total=0; l.menge=1; l.preis=0; l.einheit='h'; }
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(label)){ if(l.base_preis===undefined||l.base_preis===null) l.base_preis=preis; total=menge*(Number(l.base_preis||0)*risikoF); }
      else total=menge*preis;
      l.total=Math.round(total*100)/100;
      sum += l.total;
    });
  });

  var rchf = Math.max(0, Number(document.getElementById('rabattCHF').value)||0);
  var rproRaw = Math.max(0, Number(document.getElementById('rabattProzent').value)||0);
  var rpro = roundHalf(rproRaw); // ⭐️ runden beim Abzug
  document.getElementById('rabattCHF').value = rchf;
  document.getElementById('rabattProzent').value = rpro;

  var tot = sum - (sum*(rpro/100)) - rchf;
  DOC.zwischensumme = Math.round(sum*100)/100;
  DOC.total = Math.round(tot*100)/100;
  DOC.rabattCHF = rchf;
  DOC.rabattProzent = rpro;

  document.getElementById('zwischensumme').value = chf(DOC.zwischensumme);
  document.getElementById('total').value = chf(DOC.total);
}

function enableDnD(tbody, sec){
  var dragging=null;
  tbody.querySelectorAll('tr').forEach(function(row){
    row.addEventListener('dragstart', function(e){
      if(!(e.target && e.target.closest && e.target.closest('.drag-handle'))) { e.preventDefault(); return; }
      dragging=row; row.classList.add('row-dragging'); e.dataTransfer.effectAllowed='move';
    });
    row.addEventListener('dragend', function(){ row.classList.remove('row-dragging'); dragging=null; });
    row.addEventListener('dragover', function(e){
      e.preventDefault();
      if(!dragging || dragging===row) return;
      var rect=row.getBoundingClientRect(); var mid=rect.top+rect.height/2;
      if(e.clientY < mid) tbody.insertBefore(dragging,row); else tbody.insertBefore(dragging,row.nextSibling);
    });
    row.addEventListener('drop', function(e){ e.preventDefault(); syncOrderFromDOM(tbody, sec); });
  });
}
function syncOrderFromDOM(tbody, sec){
  var rows=Array.from(tbody.querySelectorAll('tr'));
  var old=sec.lines, newLines=[];
  rows.forEach(function(r){ newLines.push(old[Number(r.dataset.li)]); });
  sec.lines=newLines;
  renderSections();
}

function renderSections(){
  ensureSections();
  var cont=document.getElementById('sectionsContainer');
  cont.innerHTML='';
  var risikoOpts=parseRisikoOptions();
  var risikoF=getRisikoFaktor();

  DOC.sections.forEach(function(sec,sIdx){
    var wrap=document.createElement('div');
    wrap.className='section-block';

    var header=document.createElement('div');
    header.className='rkrk-row';
    header.style.justifyContent='space-between';
    header.style.alignItems='flex-end';

    var tdiv=document.createElement('div'); tdiv.style.flex='1';
    var tlab=document.createElement('label'); tlab.textContent='Sektionstitel';
    var tinp=document.createElement('input'); tinp.type='text'; tinp.value=sec.title||'';
    tinp.addEventListener('input',function(){ sec.title=tinp.value; });
    tdiv.appendChild(tlab); tdiv.appendChild(tinp);

    var bdiv=document.createElement('div'); bdiv.style.display='flex'; bdiv.style.gap='8px';
    var delBtn=document.createElement('button'); delBtn.type='button'; delBtn.className='btn'; delBtn.textContent='🗑 Sektion';
    delBtn.addEventListener('click',function(){ DOC.sections.splice(sIdx,1); renderSections(); recalcAll(); });
    bdiv.appendChild(delBtn);
    header.appendChild(tdiv); header.appendChild(bdiv);

    var tools=document.createElement('div');
    tools.className='rkrk-row';
    tools.style.marginTop='6px';
    tools.style.gap='8px';
    tools.style.alignItems='flex-end';

    var posSelWrap=document.createElement('div');
    posSelWrap.style.flex='1';
    posSelWrap.style.minWidth='200px';
    var posLab=document.createElement('label'); posLab.textContent='Gruppe / Standardposition';
    var posSel=document.createElement('select');
    posSel.style.width='100%';
    var katNow=document.getElementById('kategorie').value;
    fillPositionSelect(posSel, katNow);
    posSelWrap.appendChild(posLab); posSelWrap.appendChild(posSel);

    var addPos=document.createElement('button'); addPos.type='button'; addPos.className='btn'; addPos.textContent='➕ Positionen hinzufügen';
    addPos.addEventListener('click', function(){
      var ch=posSel.value;
      if(!ch){ return; }
      addLinesFromChoice(sec, ch);
      posSel.value='';
      renderSections(); recalcAll();
    });

    var addLine=document.createElement('button'); addLine.type='button'; addLine.className='btn'; addLine.textContent='➕ freie Zeile';
    addLine.addEventListener('click', function(){ sec.lines.push({label:'',note:'',menge:0.25,einheit:'h',preis:0,total:0}); renderSections(); recalcAll(); });

    tools.appendChild(posSelWrap);
    tools.appendChild(addPos);
    tools.appendChild(addLine);

    var table=document.createElement('table');
    table.className='table rechnung'; table.style.marginTop='10px';
    table.innerHTML='<thead><tr>'+
      '<th style="width:34px;">⇅</th><th>Nr.</th><th>Bezeichnung</th><th>Bemerkungen</th>'+
      '<th class="price">Menge</th><th>Einheit</th><th class="price">Preis (Grund)</th><th class="price">Preis (verr.)</th><th class="total">Total</th><th></th>'+
      '</tr></thead><tbody></tbody>';
    var tbody=table.querySelector('tbody');

    sec.lines.forEach(function(line,idx){
      var isRisiko = String(line.label||'').trim().toLowerCase()==='risiko';
      var tr=document.createElement('tr'); tr.draggable=true; tr.dataset.li=idx; if(isRisiko) tr.classList.add('line-risiko');

      var tdH=document.createElement('td'); tdH.className='drag-handle'; tdH.textContent='⇅';
      var tdNr=document.createElement('td'); tdNr.textContent=String(idx+1);

      var tdLab=document.createElement('td');
      var inLab=document.createElement('input'); inLab.value=line.label||'';
      inLab.addEventListener('input',function(){ line.label=inLab.value; renderSections(); recalcAll(); });
      tdLab.appendChild(inLab);

      var tdNote=document.createElement('td');
      var inNote=document.createElement('input'); inNote.value=line.note||'';
      inNote.addEventListener('input',function(){ line.note=inNote.value; recalcAll(); });
      tdNote.appendChild(inNote);

      var tdM=document.createElement('td'); tdM.className='price';
      if(isRisiko){
        var sel=document.createElement('select');
        risikoOpts.forEach(function(o){ var op=document.createElement('option'); op.value=o.value; op.textContent=o.label; if(Number(line.risiko||1)==Number(o.value)) op.selected=true; sel.appendChild(op); });
        sel.addEventListener('change',function(){ line.risiko=Number(sel.value)||1; recalcAll(); renderSections(); });
        tdM.appendChild(sel);
      } else {
        var inM=document.createElement('input'); inM.type='number'; inM.step='0.25'; inM.min='0';
        inM.value=String(Number(line.menge||0));
        inM.addEventListener('input',function(){ var v=Number(inM.value)||0; if(v<0) v=0; inM.value=v; line.menge=v; recalcAll(); renderSections(); });
        tdM.appendChild(inM);
      }

      var tdE=document.createElement('td');
      if(isRisiko){ tdE.textContent=''; }
      else {
        var selE=document.createElement('select');
        ['h','Stk','km','–'].forEach(function(u){ var op=document.createElement('option'); op.value=u; op.textContent=u; if((line.einheit||'h')===u) op.selected=true; selE.appendChild(op); });
        selE.addEventListener('change',function(){ line.einheit=selE.value; recalcAll(); renderSections(); });
        tdE.appendChild(selE);
      }

      var tdP=document.createElement('td'); tdP.className='price';
      if(isRisiko){ tdP.textContent=''; }
      else {
        var inP=document.createElement('input'); inP.type='number'; inP.step='0.05'; inP.min='0';
        inP.value=String(Number(line.preis||0));
        inP.addEventListener('input',function(){ var pv=Number(inP.value)||0; if(pv<0) pv=0; inP.value=pv; line.preis=pv; if(riskRelevant(line.label)) line.base_preis=line.preis; recalcAll(); renderSections(); });
        tdP.appendChild(inP);
      }

      var tdVP=document.createElement('td'); tdVP.className='price';
      if(isRisiko){ tdVP.textContent=''; }
      else { tdVP.innerHTML='<span class="mini">'+chf(displayedPrice(line,risikoF)).replace('CHF ','')+'</span>'; }

      var tdT=document.createElement('td'); tdT.className='total'; tdT.innerHTML='<b>'+chf(Number(line.total||0))+'</b>';

      var tdA=document.createElement('td');
      var del=document.createElement('button'); del.type='button'; del.className='btn'; del.textContent='🗑';
      del.addEventListener('click',function(){ sec.lines.splice(idx,1); renderSections(); recalcAll(); });
      tdA.appendChild(del);

      tr.appendChild(tdH); tr.appendChild(tdNr); tr.appendChild(tdLab); tr.appendChild(tdNote); tr.appendChild(tdM); tr.appendChild(tdE); tr.appendChild(tdP); tr.appendChild(tdVP); tr.appendChild(tdT); tr.appendChild(tdA);
      tbody.appendChild(tr);
    });

    enableDnD(tbody, sec);

    var notesWrap=document.createElement('div');
    notesWrap.style.marginTop='10px';
    var notesLab=document.createElement('label');
    notesLab.textContent='Anmerkungen zur Sektion';
    notesLab.style.display='block';
    notesLab.style.marginBottom='4px';
    var notesTa=document.createElement('textarea');
    notesTa.rows=3;
    notesTa.style.width='100%';
    notesTa.style.boxSizing='border-box';
    notesTa.value=sec.anmerkungen||'';
    notesTa.addEventListener('input',function(){ sec.anmerkungen=notesTa.value; });
    notesWrap.appendChild(notesLab);
    notesWrap.appendChild(notesTa);

    wrap.appendChild(header);
    wrap.appendChild(tools);
    wrap.appendChild(table);
    wrap.appendChild(notesWrap);
    cont.appendChild(wrap);
  });

  recalcAll();
}

if(!IS_EXAMPLE){
  var kundeSel=document.getElementById('kunde_select');
  if(kundeSel){
    kundeSel.addEventListener('change',function(){
      var key=kundeSel.value; if(!key||!CUSTOMERS[key]) return;
      var c=CUSTOMERS[key];
      document.getElementById('anrede').value=c.anrede||'';
      document.getElementById('vorname').value=c.vorname||'';
      document.getElementById('nachname').value=c.nachname||'';
      document.getElementById('adresse').value=c.adresse||'';
      document.getElementById('plz').value=c.plz||'';
      document.getElementById('ort_kunde').value=c.ort||'';
      document.getElementById('land').value=c.land||'';
    });
  }
}

document.getElementById('rabattCHF').addEventListener('input',recalcAll);
document.getElementById('rabattProzent').addEventListener('input',recalcAll);
document.getElementById('addSectionBtn').addEventListener('click',function(){ ensureSections(); DOC.sections.push({title:'',lines:[],anmerkungen:''}); renderSections(); });

function applyEditorDesign(kat){
  var d = DESIGN_BY_KAT[kat];
  if(!d){
    var ks = Object.keys(DESIGN_BY_KAT||{});
    d = ks.length ? DESIGN_BY_KAT[ks[0]] : {logo:'', color:'#333'};
  }
  var img = document.getElementById('editorLogo');
  if(img){
    if(d.logo){
      img.src = d.logo.charAt(0)==='/' ? d.logo : d.logo;
      img.style.display='';
    } else {
      img.removeAttribute('src');
      img.style.display='none';
    }
  }
  document.documentElement.style.setProperty('--kat-color', d.color||'#333');
}
var katEl = document.getElementById('kategorie');
if(katEl){
  katEl.addEventListener('change', function(){
    applyEditorDesign(katEl.value);
    renderSections();
    recalcAll();
  });
  if(document.getElementById('editorLogo')) applyEditorDesign(katEl.value);
}

renderSections();

document.getElementById('docForm').addEventListener('submit',function(e){
  e.preventDefault();
  var payload=JSON.parse(JSON.stringify(DOC));
  payload.id=document.getElementById('docId').value; if(!payload.id) delete payload.id;
  payload.betreff=document.getElementById('betreff').value;
  payload.kategorie=document.getElementById('kategorie').value;
  payload.datum=document.getElementById('datum').value;

  if(!IS_EXAMPLE){
    payload.kunde_details={
      anrede:document.getElementById('anrede').value,
      vorname:document.getElementById('vorname').value,
      nachname:document.getElementById('nachname').value,
      adresse:document.getElementById('adresse').value,
      plz:document.getElementById('plz').value,
      ort:document.getElementById('ort_kunde').value,
      land:document.getElementById('land').value
    };
    payload.absender={
      vorname:document.getElementById('abs_vorname').value,
      nachname:document.getElementById('abs_nachname').value
    };
  } else {
    payload.kunde_details=payload.kunde_details||{anrede:'',vorname:'',nachname:'',adresse:'',plz:'',ort:'',land:''};
    payload.absender=payload.absender||{vorname:'',nachname:''};
  }

  payload.rabattCHF=Math.max(0, Number(document.getElementById('rabattCHF').value)||0);
  payload.rabattProzent=roundHalf(Math.max(0, Number(document.getElementById('rabattProzent').value)||0));

  fetch('?mode='+SAVE_MODE,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(function(r){return r.text().then(function(t){return {ok:r.ok,status:r.status,text:t};});})
    .then(function(res){ if(!res.ok){alert(res.text);return;} location.href=AFTER_SAVE; })
    .catch(function(err){ alert(String(err)); });
});
</script>

<?php elseif($mode==='examples'): ?>

<div class="top-header"><h1>Beispielrechnungen</h1><img class="top-logo" src="images/logo.png" alt="Logo"></div>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=example">Neue Beispielrechnung</a>
  <a class="btn" href="?mode=list">Zurück zur Übersicht</a>
</div>
<div id="examplesRoot"></div>

<script>
var EXAMPLES = <?= json_encode($examplesAll, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var SAVE_URL = '?mode=save_example';

function chf(n){ n=Number(n); if(isNaN(n)) n=0; var s=n.toFixed(2).split('.'); s[0]=s[0].replace(/\B(?=(\d{3})+(?!\d))/g,"’"); return 'CHF '+s.join('.'); }
function roundHalf(x){ return Math.round((Number(x)||0)/0.5)*0.5; }
function riskRelevant(label){ var l=String(label||'').trim().toLowerCase(); return (l.indexOf('flugzeit')>=0)||(l.indexOf('vorbereitung')>=0)||(l.indexOf('programmierung')>=0)||(l.indexOf('pilot')>=0); }
function extractFreeKm(note){ note=String(note||''); var m=note.match(/(\d+)\s*km/i); if(m) return Number(m[1])||0; m=note.match(/(\d+)/); if(m) return Number(m[1])||0; return 0; }
function getRisikoFaktor(ex){ var f=1.0; (ex.sections||[]).forEach(function(sec){ (sec.lines||[]).forEach(function(l){ if(String(l.label||'').trim().toLowerCase()==='risiko'){ var v=Number(l.risiko); if(!isNaN(v)&&v>0) f=v; } }); }); return f; }
function fmt(n){ n=Number(n); if(isNaN(n)) n=0; return n.toFixed(2); }
function billedPrice(line, risikoF){
  if(riskRelevant(line.label)){
    var base = (line.base_preis!==undefined && line.base_preis!==null) ? Number(line.base_preis) : Number(line.preis||0);
    return base * risikoF;
  }
  return Number(line.preis||0);
}

function recalcExample(ex){
  var risikoF=getRisikoFaktor(ex), sum=0;
  (ex.sections||[]).forEach(function(sec){
    (sec.lines||[]).forEach(function(l){
      var ll=String(l.label||'').trim().toLowerCase();
      var menge=Number(l.menge)||0; if(menge<0) menge=0;
      var preis=Number(l.preis)||0; if(preis<0) preis=0;
      var einheit=String(l.einheit||'h');
      var note=String(l.note||'');
      var total=0;
      if(ll==='risiko') total=0;
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(l.label)) total=menge*billedPrice(l,risikoF);
      else total=menge*preis;
      l.total=Math.round(total*100)/100;
      l.menge = menge;
      l.preis = preis;
      sum += l.total;
    });
  });
  ex.zwischensumme=Math.round(sum*100)/100;
  var rchf=Math.max(0, Number(ex.rabattCHF||0));
  var rpro=Math.max(0, Number(ex.rabattProzent||0));
  rpro = roundHalf(rpro);
  ex.rabattCHF=rchf; ex.rabattProzent=rpro;
  ex.total=Math.round((sum-(sum*(rpro/100))-rchf)*100)/100;
}

function addExampleLine(ex, si){
  ex.sections = ex.sections || [];
  ex.sections[si] = ex.sections[si] || {title:'', lines:[]};
  ex.sections[si].lines = ex.sections[si].lines || [];
  // Neue Zeile sichtbar: Grundpreis 1.00, Menge 0.25
  ex.sections[si].lines.push({label:'Neue Position', note:'', menge:0.25, einheit:'h', preis:1.00, total:0});
  renderExamples();
}

function renderExamples(){
  var root=document.getElementById('examplesRoot');
  root.innerHTML='';

  EXAMPLES.forEach(function(ex){
    ex.sections = ex.sections || [];
    if(ex.rabattCHF===undefined) ex.rabattCHF=0;
    if(ex.rabattProzent===undefined) ex.rabattProzent=0;
    recalcExample(ex);

    var card=document.createElement('div');
    card.className='example-card';

    var head=document.createElement('div');
    head.className='example-head';
    var title = ex.betreff ? ex.betreff : ('Beispiel ' + (ex.id||''));
    head.innerHTML = '<div><h3>'+title+'</h3>'+
      '<div class="mini"><span class="pill">'+(ex.id||'')+'</span> <span class="pill">'+(ex.kategorie||'')+'</span></div>'+
      '<div class="mini" style="margin-top:6px;">Zwischensumme: <b>'+chf(ex.zwischensumme)+'</b> | Rabatt %: <b>'+ex.rabattProzent.toFixed(1)+'</b> | Rabatt CHF: <b>'+chf(ex.rabattCHF)+'</b> | Total: <b>'+chf(ex.total)+'</b></div>'+
      '</div>';

    var actions=document.createElement('div');
    actions.innerHTML = '<a class="btn" href="?mode=example&id='+encodeURIComponent(ex.id)+'">✏️ Edit</a> ' +
                        '<button class="btn" type="button">💾 Speichern</button> ' +
                        '<a class="btn" href="?mode=delete_example&id='+encodeURIComponent(ex.id)+'" onclick="return confirm(\'Beispiel wirklich löschen?\')">🗑</a>';
    actions.querySelector('button').addEventListener('click', function(){ saveExample(ex); });
    head.appendChild(actions);
    card.appendChild(head);

    ex.sections.forEach(function(sec, si){
      var st=document.createElement('div');
      st.style.marginTop='10px';

      var titleRow=document.createElement('div');
      titleRow.style.display='flex';
      titleRow.style.justifyContent='space-between';
      titleRow.style.alignItems='center';
      titleRow.innerHTML = '<div class="mini"><b>'+(sec.title||'(ohne Titel)')+'</b></div>';

      var addBtn=document.createElement('button');
      addBtn.type='button';
      addBtn.className='btn';
      addBtn.textContent='➕ Zeile hinzufügen';
      addBtn.addEventListener('click', function(){ addExampleLine(ex, si); });
      titleRow.appendChild(addBtn);
      st.appendChild(titleRow);

      var table=document.createElement('table');
      table.innerHTML = '<thead><tr>'+
        '<th style="width:34px;">⇅</th><th>Bezeichnung</th><th>Bemerkung</th>'+
        '<th class="price" style="width:90px;">Menge</th><th style="width:90px;">Einheit</th>'+
        '<th class="price" style="width:120px;">Preis (Grund)</th><th class="price" style="width:140px;">Preis (verr.)</th>'+
        '<th class="total" style="width:140px;">Total</th>'+
        '</tr></thead><tbody></tbody>';
      var tbody=table.querySelector('tbody');

      var lines = (sec.lines||[]);
      lines.forEach(function(line, li){
        var ll = String(line.label||'').trim().toLowerCase();
        // Zeilen mit menge 0 ausblenden (Risiko immer sichtbar)
        if(ll!=='risiko' && (Number(line.menge||0) <= 0)) return;
        // Nur Zeilen mit Grundpreis>0 anzeigen (Risiko immer)
        if(ll!=='risiko' && (Number(line.preis||0) <= 0)) return;

        var tr=document.createElement('tr');
        tr.draggable=true;
        tr.dataset.si=String(si);
        tr.dataset.li=String(li);

        var tdH=document.createElement('td'); tdH.className='dnd-handle'; tdH.textContent='⇅';
        var tdLab=document.createElement('td'); tdLab.textContent=line.label||'';
        var tdNote=document.createElement('td'); tdNote.textContent=line.note||'';

        var tdM=document.createElement('td'); tdM.className='price';
        if(ll==='risiko'){
          var sel=document.createElement('select');
          [1.0,1.2,1.5,2.0].forEach(function(v){
            var op=document.createElement('option'); op.value=String(v); op.textContent=String(v);
            if(Number(line.risiko||1)===v) op.selected=true;
            sel.appendChild(op);
          });
          sel.addEventListener('change', function(){ line.risiko=Number(sel.value)||1; renderExamples(); });
          tdM.appendChild(sel);
        } else {
          var inM=document.createElement('input');
          inM.type='number'; inM.step='0.25'; inM.min='0';
          inM.value=String(Number(line.menge||0));
          inM.addEventListener('input', function(){ var v=Number(inM.value)||0; if(v<0) v=0; inM.value=v; line.menge=v; renderExamples(); });
          tdM.appendChild(inM);
        }

        var tdE=document.createElement('td'); tdE.textContent = (ll==='risiko') ? '' : (line.einheit||'');
        var tdP=document.createElement('td'); tdP.className='price'; tdP.textContent = (ll==='risiko') ? '0.00' : fmt(Number(line.preis||0));
        var rf=getRisikoFaktor(ex);
        var tdBP=document.createElement('td'); tdBP.className='price'; tdBP.textContent = (ll==='risiko') ? '' : fmt(billedPrice(line, rf));
        var tdT=document.createElement('td'); tdT.className='total'; tdT.innerHTML = '<b>'+chf(Number(line.total||0))+'</b>';

        tr.appendChild(tdH); tr.appendChild(tdLab); tr.appendChild(tdNote); tr.appendChild(tdM); tr.appendChild(tdE); tr.appendChild(tdP); tr.appendChild(tdBP); tr.appendChild(tdT);
        tbody.appendChild(tr);
      });

      enableDnDExample(tbody, ex, si);
      st.appendChild(table);
      card.appendChild(st);
    });

    root.appendChild(card);
  });
}

function enableDnDExample(tbody, ex, si){
  var dragging=null;
  tbody.querySelectorAll('tr').forEach(function(row){
    row.addEventListener('dragstart', function(e){
      if(!(e.target && e.target.closest && e.target.closest('.dnd-handle'))) { e.preventDefault(); return; }
      dragging=row; row.classList.add('row-dragging'); e.dataTransfer.effectAllowed='move';
    });
    row.addEventListener('dragend', function(){ row.classList.remove('row-dragging'); dragging=null; });
    row.addEventListener('dragover', function(e){
      e.preventDefault();
      if(!dragging || dragging===row) return;
      var rect=row.getBoundingClientRect(); var mid=rect.top+rect.height/2;
      if(e.clientY < mid) tbody.insertBefore(dragging,row);
      else tbody.insertBefore(dragging,row.nextSibling);
    });
    row.addEventListener('drop', function(e){ e.preventDefault(); syncExampleOrderFromDOM(tbody, ex, si); });
  });
}

function syncExampleOrderFromDOM(tbody, ex, si){
  var rows = Array.from(tbody.querySelectorAll('tr'));
  var original = ex.sections[si].lines;
  var newLines=[];
  rows.forEach(function(r){ newLines.push(original[Number(r.dataset.li)]); });
  // Rest anhängen (versteckte Zeilen)
  var used = new Set(newLines);
  original.forEach(function(l){ if(!used.has(l)) newLines.push(l); });
  ex.sections[si].lines = newLines;
  renderExamples();
}

function saveExample(ex){
  fetch(SAVE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(ex)})
    .then(function(r){ return r.text().then(function(t){ return {ok:r.ok, status:r.status, text:t};}); })
    .then(function(res){ if(!res.ok){ alert(res.text); return; } /* bleiben in examples */ alert('Gespeichert: '+(ex.id||'')); })
    .catch(function(err){ alert(String(err)); });
}

renderExamples();
</script>

<?php endif; ?>

</div>
</body>
</html>
