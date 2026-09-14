<?php
// rkrk_rt.php – FINAL (IDs REYYYYKKNNNN / BSPYYYYKKNNNN, DnD, 0.5 Menge, Beispiel Edit)
// PHP 7.4 safe, CSS: rkrk_rt.css

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

include_once('../scripts/rkrk_gen.php');
if (!function_exists('headerpage')) {
  @include_once __DIR__ . '/../scripts/rkrk_gen.php';
  @include_once __DIR__ . '/../../scripts/rkrk_gen.php';
}
if (function_exists('headerpage')) {
  headerpage("Drohnenrehkitzrettung.ch", false);
}

$resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
$dataDir = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
$baseDir = $dataDir;
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

$mode = $_GET['mode'] ?? 'list';

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

// REYYYYKKNNNN
function nextInvoiceId($kat, $file){
  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)$kat));
  if($kat==='') $kat='SC';
  $year = date('Y');
  $key = $year . $kat;
  $c = readCounters($file);
  // Backward compat: falls altes schema nur Kategorie
  if(!isset($c[$key]) || !is_numeric($c[$key])){
    if(isset($c[$kat]) && is_numeric($c[$kat])) $c[$key] = (int)$c[$kat];
    else $c[$key] = 1;
  }
  $nr = (int)$c[$key];
  $c[$key] = $nr + 1;
  writeCounters($file, $c);
  return 'RE' . $year . $kat . str_pad((string)$nr, 4, '0', STR_PAD_LEFT);
}

// BSPYYYYKKNNNN
function nextExampleIdBSP($kat, $file){
  $kat = preg_replace('/[^A-Z]/','', strtoupper((string)$kat));
  if($kat==='') $kat='SC';
  $year = date('Y');
  $key = $year . $kat;
  $c = readCounters($file);
  // Backward compat: altes schema global BSP oder kat
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
    if(!isset($sec['lines']) || !is_array($sec['lines'])) $sec['lines'] = [];
    foreach($sec['lines'] as &$l){
      if(!isset($l['label'])) $l['label'] = '';
      if(!isset($l['note'])) $l['note'] = '';
      if(!isset($l['einheit'])) $l['einheit'] = 'h';
      $l['menge'] = (float)($l['menge'] ?? 0);
      $l['preis'] = (float)($l['preis'] ?? 0);
      $l['total'] = (float)($l['total'] ?? 0);
    }
  }
  unset($sec,$l);
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
      $preis = (float)($line['preis'] ?? 0);
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
        $line['base_preis'] = $base;
        $effPreis = $base * $risikoF;
        $line['preis'] = $base; // Grundpreis
        $total = $menge * $effPreis;
      }
      else{
        $total = $menge * $preis;
      }

      $line['total'] = round($total,2);
      $sum += $line['total'];
    }
  }
  unset($sec,$line);

  $rabattCHF = (float)($data['rabattCHF'] ?? 0);
  $rabattProzent = (float)($data['rabattProzent'] ?? 0);
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
  if($id==='' || $id==='null') $id = nextExampleIdBSP($kat, $exampleCountersFile);
  else $id = basename($id);

  $data['id']=$id;
  $data['kategorie']=$kat;
  if(!isset($data['datum']) || $data['datum']==='') $data['datum']=date('Y-m-d');

  // Schema parity
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
    'sections'=>[['title'=>'','lines'=>[]]]
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
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <link rel="stylesheet" href="rkrk_rt.css">
  <style>
    .example-card{border:1px solid #c8d9c0; border-radius:10px; padding:12px; margin:12px 0; background:#fff;}
    .example-head{display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; justify-content:space-between;}
    .example-head h3{margin:0;}
    .mini{font-size:.9rem; opacity:.8;}
    .dnd-handle{cursor:grab; user-select:none; padding:0 6px;}
    .drag-handle{cursor:grab; user-select:none; padding:0 6px;}
    .row-dragging{opacity:.5;}
    .pill{display:inline-block; padding:2px 8px; border:1px solid #c8d9c0; border-radius:999px; font-size:.85rem;}
  </style>
</head>
<body>
<div class="page">

<?php if($mode==='list'): ?>

<h1>Rechnungsübersicht</h1>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=invoice">Neue Rechnung</a>
  <a class="btn" href="?mode=example">Neue Beispielrechnung</a>
  <a class="btn" href="?mode=examples">Beispielrechnungen Ansicht</a>
</div>

<h2>Rechnungen</h2>
<table>
  <thead><tr><th>ID</th><th>Datum</th><th>Kategorie</th><th>Betreff</th><th>Total</th><th>Aktionen</th></tr></thead>
  <tbody>
  <?php
    $files=glob($invoicesDir.'/*.json'); sort($files);
    if(!$files){ echo "<tr><td colspan='6'><i>Noch keine Rechnungen vorhanden.</i></td></tr>"; }
    foreach($files as $f){
      $data=json_decode(@file_get_contents($f),true)?:[];
      $id=basename($f,'.json');
      $datum=h($data['datum']??'');
      $totalVal=(float)($data['total']??0);
      $totalStr=number_format($totalVal,2,'.','’');
      echo '<tr>';
      echo '<td>'.h($id).'</td>';
      echo '<td>'.$datum.'</td>';
      echo '<td>'.h($data['kategorie']??'').'</td>';
      echo '<td>'.h($data['betreff']??'').'</td>';
      echo '<td>'.$totalStr.' CHF</td>';
      echo '<td>';
      echo '<a class="btn" href="?mode=invoice&id='.rawurlencode($id).'">Öffnen</a> ';
      echo '<a class="btn" href="rkrk_pdf.php?id='.rawurlencode($id).'" target="_blank">PDF</a> ';
      echo '<a class="btn" href="?mode=delete_invoice&id='.rawurlencode($id).'" onclick="return confirm(\'Rechnung wirklich löschen?\')">🗑</a>';
      echo '</td>';
      echo '</tr>';
    }
  ?>
  </tbody>
</table>

<?php elseif($mode==='invoice' || $mode==='example'): ?>

<h1><?= $isExample ? 'Beispielrechnung bearbeiten' : 'Rechnung bearbeiten' ?></h1>
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
    <div><label>Zwischensumme</label><input type="text" id="zwischensumme" readonly value="<?= h($doc['zwischensumme'] ?? 0) ?>"></div>
    <div><label>Rabatt CHF</label><input type="number" step="0.5" min="0" id="rabattCHF" value="<?= h($doc['rabattCHF'] ?? 0) ?>"></div>
    <div><label>Rabatt %</label><input type="number" step="0.5" min="0" id="rabattProzent" value="<?= h($doc['rabattProzent'] ?? 0) ?>"></div>
    <div><label>Total</label><input type="text" id="total" readonly style="font-weight:700;" value="<?= h($doc['total'] ?? 0) ?>"></div>
  </div>

  <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
    <button type="submit" class="btn-primary">💾 Speichern</button>
    <a class="btn" href="?mode=list">Zurück</a>
  </div>
</form>

<script>
var IS_EXAMPLE = <?= $isExample ? 'true' : 'false' ?>;
var SAVE_MODE = IS_EXAMPLE ? 'save_example' : 'save_invoice';
var DOC = <?= json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var CUSTOMERS = <?= json_encode($CUSTOMERS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var DEFAULTS = <?= json_encode($DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function fmtCHF(n){ n=Number(n); if(isNaN(n)) n=0; return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,"’"); }
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
  return [ {label:'1.0',value:1.0},{label:'1.2',value:1.2},{label:'1.5',value:1.5},{label:'2.0',value:2.0} ];
}
function ensureSections(){ if(!Array.isArray(DOC.sections)||DOC.sections.length===0) DOC.sections=[{title:'',lines:[]}]; DOC.sections.forEach(function(sec){ if(!Array.isArray(sec.lines)) sec.lines=[]; }); }

function recalcAll(){
  var sum=0, risikoF=getRisikoFaktor();
  (DOC.sections||[]).forEach(function(sec){
    (sec.lines||[]).forEach(function(l){
      var label=String(l.label||''), ll=label.trim().toLowerCase();
      var menge=Number(l.menge)||0, preis=Number(l.preis)||0, einheit=String(l.einheit||'h'), note=String(l.note||'');
      var total=0;
      if(ll==='risiko'){ total=0; l.menge=1; l.preis=0; l.einheit='h'; }
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(label)){ if(l.base_preis===undefined||l.base_preis===null) l.base_preis=preis; total=menge*(Number(l.base_preis||0)*risikoF); }
      else total=menge*preis;
      l.total=Math.round(total*100)/100; sum+=l.total;
    });
  });
  var rchf=Math.max(0, Number(document.getElementById('rabattCHF').value)||0);
  var rpro=Math.max(0, Number(document.getElementById('rabattProzent').value)||0);
  document.getElementById('rabattCHF').value = rchf;
  document.getElementById('rabattProzent').value = rpro;
  var tot=sum-(sum*(rpro/100))-rchf;
  DOC.zwischensumme=Math.round(sum*100)/100;
  DOC.total=Math.round(tot*100)/100;
  document.getElementById('zwischensumme').value=fmtCHF(DOC.zwischensumme);
  document.getElementById('total').value=fmtCHF(DOC.total);
}

function enableDnD(tbody, sec){
  var dragging=null;
  tbody.querySelectorAll('tr').forEach(function(row){
    row.addEventListener('dragstart', function(e){
      if(!e.target.classList.contains('drag-handle')) { e.preventDefault(); return; }
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

    var table=document.createElement('table');
    table.className='table rechnung'; table.style.marginTop='10px';
    table.innerHTML='<thead><tr>'+
      '<th style="width:34px;">⇅</th><th>Nr.</th><th>Bezeichnung</th><th>Bemerkungen</th>'+
      '<th>Menge</th><th>Einheit</th><th>Preis (Grund)</th><th>Preis (verr.)</th><th>Total</th><th></th>'+
      '</tr></thead><tbody></tbody>';
    var tbody=table.querySelector('tbody');

    sec.lines.forEach(function(line,idx){
      var isRisiko = String(line.label||'').trim().toLowerCase()==='risiko';
      var tr=document.createElement('tr'); tr.draggable=true; tr.dataset.li=idx;
      if(isRisiko) tr.className='line-risiko';

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

      var tdM=document.createElement('td');
      if(isRisiko){
        var sel=document.createElement('select');
        risikoOpts.forEach(function(o){ var op=document.createElement('option'); op.value=o.value; op.textContent=o.label; if(Number(line.risiko||1)==Number(o.value)) op.selected=true; sel.appendChild(op); });
        sel.addEventListener('change',function(){ line.risiko=Number(sel.value)||1; recalcAll(); renderSections(); });
        tdM.appendChild(sel);
      } else {
        var inM=document.createElement('input'); inM.type='number'; inM.step='0.5'; inM.min='0';
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

      var tdP=document.createElement('td');
      if(isRisiko){ tdP.textContent=''; }
      else {
        var inP=document.createElement('input'); inP.type='number'; inP.step='0.05'; inP.min='0';
        inP.value=String(Number(line.preis||0));
        inP.addEventListener('input',function(){ var pv=Number(inP.value)||0; if(pv<0) pv=0; inP.value=pv; line.preis=pv; if(riskRelevant(line.label)) line.base_preis=line.preis; recalcAll(); renderSections(); });
        tdP.appendChild(inP);
      }

      var tdVP=document.createElement('td');
      if(isRisiko){ tdVP.textContent=''; }
      else { tdVP.innerHTML='<span class="mini">'+fmtCHF(displayedPrice(line,risikoF))+'</span>'; }

      var tdT=document.createElement('td'); tdT.innerHTML='<b>CHF '+fmtCHF(Number(line.total||0))+'</b>';

      var tdA=document.createElement('td');
      var del=document.createElement('button'); del.type='button'; del.className='btn'; del.textContent='🗑';
      del.addEventListener('click',function(){ sec.lines.splice(idx,1); renderSections(); recalcAll(); });
      tdA.appendChild(del);

      tr.appendChild(tdH); tr.appendChild(tdNr); tr.appendChild(tdLab); tr.appendChild(tdNote); tr.appendChild(tdM); tr.appendChild(tdE); tr.appendChild(tdP); tr.appendChild(tdVP); tr.appendChild(tdT); tr.appendChild(tdA);
      tbody.appendChild(tr);
    });

    enableDnD(tbody, sec);
    wrap.appendChild(header);
    wrap.appendChild(table);
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

document.getElementById('kategorie').addEventListener('change',function(){ DOC.kategorie=document.getElementById('kategorie').value; renderSections(); });
document.getElementById('rabattCHF').addEventListener('input',recalcAll);
document.getElementById('rabattProzent').addEventListener('input',recalcAll);
document.getElementById('addSectionBtn').addEventListener('click',function(){ ensureSections(); DOC.sections.push({title:'',lines:[]}); renderSections(); });

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
  payload.rabattProzent=Math.max(0, Number(document.getElementById('rabattProzent').value)||0);

  fetch('?mode='+SAVE_MODE,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(function(r){return r.text().then(function(t){return {ok:r.ok,status:r.status,text:t};});})
    .then(function(res){ if(!res.ok){alert(res.text);return;} location.href='?mode=list'; })
    .catch(function(err){ alert(String(err)); });
});
</script>

<?php elseif($mode==='examples'): ?>

<h1>Beispielrechnungen – Ansicht</h1>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=example">Neue Beispielrechnung</a>
  <a class="btn" href="?mode=list">Zurück zur Übersicht</a>
</div>
<div id="examplesRoot"></div>

<script>
var EXAMPLES = <?= json_encode($examplesAll, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var SAVE_URL = '?mode=save_example';

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
      var menge=Number(l.menge)||0;
      var preis=Number(l.preis)||0;
      var einheit=String(l.einheit||'h');
      var note=String(l.note||'');
      var total=0;
      if(ll==='risiko') total=0;
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(l.label)) total=menge*billedPrice(l,risikoF);
      else total=menge*preis;
      l.total=Math.round(total*100)/100;
      sum += l.total;
    });
  });
  ex.zwischensumme=Math.round(sum*100)/100;
  var rchf=Math.max(0, Number(ex.rabattCHF||0));
  var rpro=Math.max(0, Number(ex.rabattProzent||0));
  ex.rabattCHF=rchf; ex.rabattProzent=rpro;
  ex.total=Math.round((sum-(sum*(rpro/100))-rchf)*100)/100;
}

function renderExamples(){
  var root=document.getElementById('examplesRoot');
  root.innerHTML='';

  EXAMPLES.forEach(function(ex){
    if(!ex.sections) ex.sections=[];
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
      '<div class="mini" style="margin-top:6px;">Zwischensumme: <b>'+fmt(ex.zwischensumme)+'</b> CHF | Rabatt %: <b>'+fmt(ex.rabattProzent)+'</b> | Rabatt CHF: <b>'+fmt(ex.rabattCHF)+'</b> | Total: <b>'+fmt(ex.total)+'</b> CHF</div>'+
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
      st.innerHTML = '<div class="mini" style="margin-bottom:6px;"><b>'+(sec.title||'(ohne Titel)')+'</b></div>';

      var table=document.createElement('table');
      table.innerHTML = '<thead><tr>'+
        '<th style="width:34px;">⇅</th><th>Bezeichnung</th><th>Bemerkung</th>'+
        '<th style="width:90px;">Menge</th><th style="width:90px;">Einheit</th>'+
        '<th style="width:120px;">Preis (Grund)</th><th style="width:140px;">Preis (verr.)</th>'+
        '<th style="width:120px;">Total</th>'+
        '</tr></thead><tbody></tbody>';
      var tbody=table.querySelector('tbody');

      var lines = (sec.lines||[]);
      lines.forEach(function(line, li){
        var ll = String(line.label||'').trim().toLowerCase();
        // Nur Zeilen mit Grundpreis>0 UND Menge>0 (Risiko immer)
        var show = (ll==='risiko') || ((Number(line.preis||0) > 0) && (Number(line.menge||0) > 0));
        if(!show) return;

        var tr=document.createElement('tr');
        tr.draggable=true;
        tr.dataset.si=String(si);
        tr.dataset.li=String(li);

        var tdH=document.createElement('td'); tdH.className='dnd-handle'; tdH.textContent='⇅';
        var tdLab=document.createElement('td'); tdLab.textContent=line.label||'';
        var tdNote=document.createElement('td'); tdNote.textContent=line.note||'';

        var tdM=document.createElement('td');
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
          inM.type='number'; inM.step='0.5'; inM.min='0';
          inM.value=String(Number(line.menge||0));
          inM.addEventListener('input', function(){ var v=Number(inM.value)||0; if(v<0) v=0; inM.value=v; line.menge=v; renderExamples(); });
          tdM.appendChild(inM);
        }

        var tdE=document.createElement('td'); tdE.textContent = (ll==='risiko') ? '' : (line.einheit||'');
        var tdP=document.createElement('td'); tdP.textContent = (ll==='risiko') ? '0.00' : fmt(Number(line.preis||0));
        var rf=getRisikoFaktor(ex);
        var tdBP=document.createElement('td'); tdBP.textContent = (ll==='risiko') ? '' : fmt(billedPrice(line, rf));
        var tdT=document.createElement('td'); tdT.innerHTML = '<b>'+fmt(Number(line.total||0))+'</b>';

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
      if(!e.target.classList.contains('dnd-handle')) { e.preventDefault(); return; }
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
    .then(function(res){ if(!res.ok){ alert(res.text); return; } alert('Gespeichert: '+ex.id); })
    .catch(function(err){ alert(String(err)); });
}

renderExamples();
</script>

<?php endif; ?>

</div>
</body>
</html>
