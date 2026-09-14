<?php
// rkrk_rt.php – FINAL
// Features:
// - IDs: Rechnung REYYYYKKNNNN, Beispiel BSPYYYYKKNNNN (Counter pro Jahr+Kategorie)
// - Menge/Preis: beliebige Zahlen ≥0 (Dezimal, Komma), step=any; keine negativen Werte
// - Preise/Total rechtsbündig, CHF Format "CHF 1’230.00"
// - Rabatt% wird beim Abzug auf 0.5 gerundet
// - DnD Sortierung Positionen innerhalb Sektion (1. Spalte Handle)
// - Beispiele Ansicht (mode=examples): nur Menge editierbar (+ Risiko Faktor), Zeilen mit menge 0 ausblenden,
//   Zeilen-Total anzeigen, Speichern bleibt in mode=examples
// - Beispiel-Liste: Edit Button

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function chf_fmt($n){ return "CHF " . number_format((float)$n, 2, '.', '’'); }

include_once(__DIR__ . '/rkrk_rt_shell.php');
rkrk_rt_boot();

$resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
$dataDir = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
$webRtBase = rtrim((string)($RkrkLocation ?? ''), "/\\") . "/data/rt";
$baseDir = $dataDir;
$dataDirExists = is_dir($dataDir);
$dataDirInfo = $dataDir . ($dataDirExists ? " (OK)" : " (NICHT GEFUNDEN)");
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
require_once __DIR__ . '/rkrk_category.php';

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
  if(!array_key_exists('einleitung', $data)) $data['einleitung'] = '';
  if(!array_key_exists('abschluss', $data)) $data['abschluss'] = '';
  foreach (['pdf_details_positionen','pdf_positionen_sektionen','pdf_sektionen'] as $_pdfk) {
    if(!array_key_exists($_pdfk, $data)) $data[$_pdfk] = true;
    else $data[$_pdfk] = (bool)$data[$_pdfk];
  }
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

function rkrk_collect_attachment_refs($node, array &$out){
  if (is_array($node)) {
    foreach ($node as $k => $v) {
      $lk = strtolower((string)$k);
      if (is_string($v) && ($lk === 'beleg' || $lk === 'receipt' || $lk === 'attachment' || $lk === 'file' || $lk === 'path')) {
        $out[] = $v;
      } elseif (is_array($v)) {
        if (($lk === 'belege' || $lk === 'receipts' || $lk === 'attachments' || $lk === 'files')) {
          foreach ($v as $item) {
            if (is_string($item)) $out[] = $item;
            elseif (is_array($item)) rkrk_collect_attachment_refs($item, $out);
          }
        } else {
          rkrk_collect_attachment_refs($v, $out);
        }
      }
    }
  }
}

function rkrk_safe_resolve_under_base($ref, $baseDir){
  $ref = trim((string)$ref);
  if ($ref === '' || preg_match('#^https?://#i', $ref)) return null;
  $ref = str_replace('\\', '/', $ref);
  $ref = preg_replace('#^(\.\./|/)+#', '', $ref);
  $candidate = rtrim($baseDir, "/\\") . '/' . ltrim($ref, "/\\");
  $realBase = realpath($baseDir);
  $realCand = realpath($candidate);
  if ($realBase !== false && $realCand !== false && strpos($realCand, $realBase) === 0) {
    return $realCand;
  }
  return is_file($candidate) ? $candidate : null;
}

function rkrk_delete_entry_with_beleg($id, $jsonPath, $baseDir){
  $id = basename((string)$id);
  if ($id === '') return;

  // 1) JSON lesen und explizit referenzierte Belege löschen
  if (is_file($jsonPath)) {
    $doc = json_decode((string)@file_get_contents($jsonPath), true);
    if (is_array($doc)) {
      $refs = [];
      rkrk_collect_attachment_refs($doc, $refs);
      foreach ($refs as $ref) {
        $p = rkrk_safe_resolve_under_base($ref, $baseDir);
        if ($p && is_file($p)) @unlink($p);
      }
    }
  }

  // 2) Konventionelle Beleg-Dateien zum Eintrag löschen
  $candidates = [
    $baseDir . '/pdf/Rechnung_' . $id . '.pdf',
    $baseDir . '/pdf/Beleg_' . $id . '.pdf',
    $baseDir . '/pdf/' . $id . '.pdf'
  ];
  foreach ($candidates as $p) {
    if (is_file($p)) @unlink($p);
  }

  // 3) Übliche Beleg-Ordner nach Prefix durchsuchen
  foreach (['belege', 'receipts', 'attachments', 'files'] as $sub) {
    $dir = rtrim($baseDir, "/\\") . '/' . $sub;
    if (!is_dir($dir)) continue;
    foreach ((array)glob($dir . '/' . $id . '.*') as $f) {
      if (is_file($f)) @unlink($f);
    }
  }

  // 4) JSON selbst löschen
  if (is_file($jsonPath)) @unlink($jsonPath);
}

function computeTotals(&$data){
  normalizeDoc($data);

  $sum = 0.0;
  foreach($data['sections'] as &$sec){
    $risikoF = 1.0;
    foreach($sec['lines'] as $line){
      $lab = strtolower(trim((string)($line['label'] ?? '')));
      if($lab==='risiko' && isset($line['risiko'])){
        $v = (float)$line['risiko'];
        if($v>0) $risikoF = $v;
      }
    }
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
      $line['total'] = round($total,0);

      $sum += $line['total'];
    }
  }
  unset($sec,$line);

  $rabattCHF = (float)($data['rabattCHF'] ?? 0);
  if($rabattCHF < 0) $rabattCHF = 0;
  $rabattProzent = (float)($data['rabattProzent'] ?? 0);
  if($rabattProzent < 0) $rabattProzent = 0;
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
  if($id){
    $file=$invoicesDir.'/'.$id.'.json';
    rkrk_delete_entry_with_beleg($id, $file, $baseDir);
  }
  header('Location: ?mode=list');
  exit;
}
if($mode==='delete_example'){
  $id=basename($_GET['id']??'');
  if($id){
    $file=$examplesDir.'/'.$id.'.json';
    rkrk_delete_entry_with_beleg($id, $file, $baseDir);
  }
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
    'einleitung'=>'','abschluss'=>'',
    'pdf_details_positionen'=>true,'pdf_positionen_sektionen'=>true,'pdf_sektionen'=>true,
    'kunde_details'=>['anrede'=>'','vorname'=>'','nachname'=>'','adresse'=>'','plz'=>'','ort'=>'','land'=>'Schweiz'],
    'absender'=>['vorname'=>'','nachname'=>''],
    'rabattCHF'=>0,'rabattProzent'=>0,'zwischensumme'=>0,'total'=>0,
    'sections'=>[['title'=>'','lines'=>[],'anmerkungen'=>'']]
  ];
}
normalizeDoc($doc);
$doc['kategorie'] = resolve_kategorie_key((string) ($doc['kategorie'] ?? ''), (string) ($doc['id'] ?? ''), $KATEGORIEN);

// load examples for view
$examplesAll=[];
if($mode==='examples'){
  $files=glob($examplesDir.'/*.json');
  sort($files);
  foreach($files as $f){
    $d=json_decode(@file_get_contents($f),true);
    if(is_array($d)){
      normalizeDoc($d);
      $d['kategorie'] = resolve_kategorie_key((string) ($d['kategorie'] ?? ''), (string) ($d['id'] ?? ''), $KATEGORIEN);
      $examplesAll[]=$d;
    }
  }
}

$title = ($mode==='invoice')?'Rechnung bearbeiten':(($mode==='example')?'Beispielrechnung bearbeiten':(($mode==='examples')?'Beispielrechnungen – Ansicht':'Rechnungsübersicht'));

// Für Live-Logo/Farbe beim Kategorie-Wechsel im Editor (JS) — Schlüssel normalisiert wie category_design()
$designByKat = [];
foreach (array_keys($KATEGORIEN) as $_kat) {
  $_kn = preg_replace('/[^A-Z]/', '', strtoupper((string)$_kat));
  if ($_kn === '') {
    continue;
  }
  $designByKat[$_kn] = category_design($_kn, $KATEGORIEN, $DEFAULTS);
}
$listLogoKat = 'SC';
if (!empty($DEFAULTS['kategorien_order']) && is_array($DEFAULTS['kategorien_order'])) {
  foreach ($DEFAULTS['kategorien_order'] as $_k) {
    $_k = preg_replace('/[^A-Z]/', '', strtoupper((string)$_k));
    if ($_k !== '' && isset($KATEGORIEN[$_k])) {
      $listLogoKat = $_k;
      break;
    }
  }
} elseif ($KATEGORIEN) {
  reset($KATEGORIEN);
  $listLogoKat = (string) key($KATEGORIEN);
}
$listHeaderDesign = category_design($listLogoKat, $KATEGORIEN, $DEFAULTS);
rkrk_rt_emit_chrome('rkrk_rt.css');
?>
  <style>
    
    /* Logo oben rechts (Listen + Editor) */
    .top-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}
    .top-logo{max-height:70px;max-width:220px;object-fit:contain;}
    .section-title-big{font-size:1.25rem;font-weight:600;}
    .total-big{font-size:1.4rem;font-weight:700;}
.data-dir-hint{margin:6px 0 12px 0;font-size:.9rem;color:#374151;}
.data-dir-hint.bad{color:#b91c1c;font-weight:700;}
/* Button-Höhen harmonisieren */
    .btn-primary { height:36px; line-height:36px; padding:0 14px; display:inline-block; box-sizing:border-box; }

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
    .section-block{margin-bottom:1.25rem;padding:1rem 0.95rem;background:#f9fcf9;border:1px solid #cfe3c8;border-radius:10px;font-size:1.06rem;}
    .section-block table.rechnung{font-size:1.05rem;}
    .section-block table.rechnung thead th{font-size:0.82rem;font-weight:600;line-height:1.25;}
    .section-block .rkrk-row > div > label{font-size:1.02rem;}
    .table.rechnung th.col-menge,.table.rechnung td.col-menge{min-width:6.25rem;width:11%;max-width:8.5rem;box-sizing:border-box;}
    .table.rechnung th.col-preis,.table.rechnung td.col-preis{width:6%;max-width:5.2rem;box-sizing:border-box;}
    .table.rechnung th.col-total,.table.rechnung td.col-total{min-width:6.16rem;width:8.96%;box-sizing:border-box;}
    .section-block table.rechnung td.col-total,.section-block table.rechnung td.col-total b{font-size:inherit;font-weight:700;line-height:inherit;}
    .table.rechnung td.col-menge input,.table.rechnung td.col-preis input{max-width:100%;box-sizing:border-box;}
    input.no-spinner[type=number]::-webkit-outer-spin-button,
    input.no-spinner[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0;}
    input.no-spinner[type=number]{-moz-appearance:textfield;appearance:textfield;}
    .summen-gross label{font-size:1.05rem;}
    .summen-gross input{font-size:1.12rem;}
    .summen-gross #total{font-size:1.38rem;font-weight:700;}
  </style>
<div class="page">

<?php if($mode==='list'): ?>

<div class="top-header"><h1>Rechnungsübersicht</h1><img class="top-logo" alt="Logo" src="<?= !empty($listHeaderDesign['logo']) ? h($listHeaderDesign['logo']) : '' ?>" style="<?= empty($listHeaderDesign['logo']) ? 'display:none;' : '' ?>"></div>

<!--<div class="data-dir-hint <?= $dataDirExists ? '' : 'bad' ?>">Data directory: <?= h($dataDirInfo) ?></div>-->

<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=form">Neue Rechnung</a>
  <a class="btn" href="?mode=examples">Beispielrechnungen</a>
  <a class="btn" href="rkrk_rt_admin.php">Admin</a>
</div>

<h2>Rechnungen</h2>
<label class="invoice-sort-bar">
  <span>Sortieren</span>
  <select id="invoiceSortSelect" aria-label="Rechnungen sortieren">
    <option value="datum:desc" selected>Datum (neueste zuerst)</option>
    <option value="datum:asc">Datum (älteste zuerst)</option>
    <option value="id:asc">ID (A–Z)</option>
    <option value="id:desc">ID (Z–A)</option>
    <option value="kategorie:asc">Kategorie (A–Z)</option>
    <option value="kategorie:desc">Kategorie (Z–A)</option>
    <option value="betreff:asc">Betreff (A–Z)</option>
    <option value="betreff:desc">Betreff (Z–A)</option>
    <option value="total:asc">Total (niedrigste zuerst)</option>
    <option value="total:desc">Total (höchste zuerst)</option>
  </select>
</label>
<div class="table-invoices-wrap">
<table class="table-invoices" id="invoiceTable">
  <thead>
    <tr>
      <th data-sort="id" role="button" tabindex="0" aria-sort="none">ID</th>
      <th data-sort="datum" role="button" tabindex="0" aria-sort="descending">Datum</th>
      <th data-sort="kategorie" role="button" tabindex="0" aria-sort="none">Kategorie</th>
      <th data-sort="betreff" role="button" tabindex="0" aria-sort="none">Betreff</th>
      <th class="total" data-sort="total" role="button" tabindex="0" aria-sort="none">Total</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
  <?php
    $files=glob($invoicesDir.'/*.json') ?: [];
    $invoiceRows=[];
    foreach($files as $f){
      $data=json_decode(@file_get_contents($f),true)?:[];
      $invoiceRows[]=[
        'file'=>$f,
        'data'=>$data,
        'id'=>basename($f,'.json'),
        'datum'=>(string)($data['datum']??''),
      ];
    }
    usort($invoiceRows, function($a, $b){
      $cmp = strcmp($b['datum'], $a['datum']);
      if ($cmp !== 0) return $cmp;
      return strcmp($b['id'], $a['id']);
    });
    if(!$invoiceRows){ echo "<tr class=\"invoice-empty\"><td colspan='6'><i>Noch keine Rechnungen vorhanden.</i></td></tr>"; }
    foreach($invoiceRows as $row){
      $data=$row['data'];
      $id=$row['id'];
      $datumRaw=$row['datum'];
      $katRaw=(string)($data['kategorie']??'');
      $betreffRaw=(string)($data['betreff']??'');
      $totalVal=(float)($data['total']??0);
      echo '<tr>';
      echo '<td data-label="ID" data-sort-key="id" data-sort-value="'.h($id).'">'.h($id).'</td>';
      echo '<td data-label="Datum" data-sort-key="datum" data-sort-value="'.h($datumRaw).'">'.h($datumRaw).'</td>';
      echo '<td data-label="Kategorie" data-sort-key="kategorie" data-sort-value="'.h($katRaw).'">'.h($katRaw).'</td>';
      echo '<td data-label="Betreff" data-sort-key="betreff" data-sort-value="'.h($betreffRaw).'">'.h($betreffRaw).'</td>';
      echo '<td class="total" data-label="Total" data-sort-key="total" data-sort-value="'.h((string)$totalVal).'">'.h(chf_fmt($totalVal)).'</td>';
      echo '<td class="col-actions" data-label="Aktionen">';
      echo '<a class="btn" href="?mode=form&id='.rawurlencode($id).'">Öffnen</a> ';
      echo '<a class="btn" href="rkrk_pdf.php?id='.rawurlencode($id).'" target="_blank">PDF</a> ';
      echo '<a class="btn" href="?mode=delete_invoice&id='.rawurlencode($id).'" onclick="return confirm(\'Rechnung wirklich löschen?\')">🗑</a>';
      echo '</td>';
      echo '</tr>';
    }
  ?>
  </tbody>
</table>
</div>
<script>
(function(){
  var STORAGE_KEY = 'rkrk-rt-invoice-sort-v2';
  var table = document.getElementById('invoiceTable');
  var select = document.getElementById('invoiceSortSelect');
  if (!table) return;
  var tbody = table.tBodies[0];
  if (!tbody) return;
  var headers = table.querySelectorAll('th[data-sort]');
  var originalOrder = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
  var state = { key: 'datum', dir: 'desc' };

  function loadState(){
    try {
      var raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      var parsed = JSON.parse(raw);
      if (parsed && typeof parsed.key === 'string' && parsed.key) {
        state.key = parsed.key;
        state.dir = parsed.dir === 'desc' ? 'desc' : 'asc';
      }
    } catch (e) {}
  }
  function saveState(){
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {}
  }
  function cellValue(tr, key){
    var td = tr.querySelector('td[data-sort-key="' + key + '"]');
    if (!td) return '';
    return td.getAttribute('data-sort-value') || '';
  }
  function compare(a, b, key, dir){
    var va = cellValue(a, key);
    var vb = cellValue(b, key);
    var cmp;
    if (key === 'total') {
      cmp = (parseFloat(va) || 0) - (parseFloat(vb) || 0);
    } else {
      cmp = String(va).localeCompare(String(vb), 'de', { numeric: true, sensitivity: 'base' });
    }
    return dir === 'desc' ? -cmp : cmp;
  }
  function applySort(){
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function(tr){
      return !tr.classList.contains('invoice-empty');
    });
    if (!state.key) {
      originalOrder.forEach(function(tr){ tbody.appendChild(tr); });
    } else {
      rows.sort(function(a, b){ return compare(a, b, state.key, state.dir); });
      rows.forEach(function(tr){ tbody.appendChild(tr); });
    }
    Array.prototype.forEach.call(headers, function(th){
      var k = th.getAttribute('data-sort');
      if (state.key && k === state.key) {
        th.setAttribute('aria-sort', state.dir === 'desc' ? 'descending' : 'ascending');
      } else {
        th.setAttribute('aria-sort', 'none');
      }
    });
    if (select) {
      select.value = state.key ? (state.key + ':' + state.dir) : '';
    }
  }
  Array.prototype.forEach.call(headers, function(th){
    th.addEventListener('click', function(){
      var key = th.getAttribute('data-sort');
      if (!key) return;
      if (state.key === key) {
        state.dir = state.dir === 'asc' ? 'desc' : 'asc';
      } else {
        state.key = key;
        state.dir = (key === 'datum') ? 'desc' : 'asc';
      }
      saveState();
      applySort();
    });
    th.addEventListener('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        th.click();
      }
    });
  });
  if (select) {
    select.addEventListener('change', function(){
      var v = select.value || '';
      if (!v) {
        state.key = 'datum';
        state.dir = 'desc';
      } else {
        var parts = v.split(':');
        state.key = parts[0] || '';
        state.dir = parts[1] === 'desc' ? 'desc' : 'asc';
      }
      saveState();
      applySort();
    });
  }
  loadState();
  applySort();
})();
</script>

<?php elseif($mode==='invoice' || $mode==='example'): ?>
<?php $design = category_design(($doc['kategorie'] ?? 'SC'), $KATEGORIEN, $DEFAULTS); ?>
<style>:root{--kat-color:<?=h($design['color'])?>;}</style>
<div class="top-header">
  <h1><?= $isExample ? 'Beispielrechnung bearbeiten' : 'Rechnung bearbeiten' ?></h1>
  <img id="editorLogo" class="top-logo" alt="Logo" src="<?= !empty($design['logo']) ? h($design['logo']) : '' ?>" style="<?= empty($design['logo']) ? 'display:none;' : '' ?>">
</div>
<!--<div class="data-dir-hint <?= $dataDirExists ? '' : 'bad' ?>">Data directory: <?= h($dataDirInfo) ?></div>-->
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
        <?php foreach (array_keys($KATEGORIEN) as $k):
          $kNorm = preg_replace('/[^A-Z]/', '', strtoupper((string) $k));
          if ($kNorm === '') {
            continue;
          }
          $sel = (preg_replace('/[^A-Z]/', '', strtoupper((string)($doc['kategorie'] ?? ''))) === $kNorm) || ($doc['kategorie'] ?? '') === $k;
        ?>
          <option value="<?= h($kNorm) ?>" <?= $sel ? 'selected' : '' ?>><?= h($k) ?> – <?= h($KATEGORIEN[$k]['name'] ?? '') ?></option>
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

  <h2>Freitext</h2>
  <div class="rkrk-row" style="flex-direction:column; align-items:stretch;">
    <div>
      <label><b>Einleitung</b> <span class="mini">(mehrzeilig, erscheint nach Anrede vor den Positionen im PDF)</span></label>
      <textarea id="einleitung" rows="5" style="width:100%; box-sizing:border-box;"><?= h($doc['einleitung'] ?? '') ?></textarea>
    </div>
  </div>

  <h2>PDF – Darstellung</h2>
  <p class="mini" style="margin:0 0 8px;">Gilt für die Rechnungs-PDF; die Bearbeitung der Positionen bleibt unverändert.</p>
  <div class="pdf-opts">
    <label>
      <input type="checkbox" id="pdf_details_positionen" <?= (($doc['pdf_details_positionen'] ?? true)) ? 'checked' : '' ?>>
      <span>Details zu Rechnungspositionen einblenden <span class="mini">(Bemerkung, Menge×Preis)</span></span>
    </label>
    <label>
      <input type="checkbox" id="pdf_positionen_sektionen" <?= (($doc['pdf_positionen_sektionen'] ?? true)) ? 'checked' : '' ?>>
      <span>Rechnungspositionen der Sektionen einblenden</span>
    </label>
    <label>
      <input type="checkbox" id="pdf_sektionen" <?= (($doc['pdf_sektionen'] ?? true)) ? 'checked' : '' ?>>
      <span>Sektionen einblenden <span class="mini">(Titel, Zwischensummen)</span></span>
    </label>
  </div>

  <h2>Rechnungspositionen</h2>
  <div id="sectionsContainer"></div>
  <button type="button" class="btn" id="addSectionBtn">➕ Sektion hinzufügen</button>

  <h2>Summen</h2>
  <div class="rkrk-row summen-gross" style="align-items:flex-end;">
    <div><label>Zwischensumme</label><input type="text" id="zwischensumme" readonly value="<?= h(chf_fmt($doc['zwischensumme'] ?? 0)) ?>"></div>
    <div><label>Rabatt CHF</label><input type="number" class="no-spinner" id="rabattCHF" min="0" step="any" value="<?= h($doc['rabattCHF'] ?? 0) ?>"></div>
    <div><label>Rabatt %</label><input type="number" class="no-spinner" id="rabattProzent" min="0" step="any" value="<?= h($doc['rabattProzent'] ?? 0) ?>"></div>
    <div><label>Total</label><input type="text" id="total" readonly style="font-weight:700;" value="<?= h(chf_fmt($doc['total'] ?? 0)) ?>"></div>
  </div>

  <div class="rkrk-row" style="flex-direction:column; align-items:stretch; margin-top:12px;">
    <div>
      <label><b>Abschluss</b> <span class="mini">(mehrzeilig, erscheint nach der Totalzeile im PDF, vor Gruss/Signatur)</span></label>
      <textarea id="abschluss" rows="5" style="width:100%; box-sizing:border-box;"><?= h($doc['abschluss'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
    <button type="submit" class="btn-primary">💾 Speichern</button>
    <?php if($isExample): ?>
      <a class="btn" href="?mode=examples">Zurück zu Beispielen</a>
    <?php else: ?>
      <a class="btn" href="?mode=list">Zurück</a>
    <?php endif; ?>
    <a class="btn" href="rkrk_rt_admin.php">Admin</a>
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
var MEDIA_WEB_BASE = <?= json_encode($webRtBase, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;


function chf(n){
  n = Number(n); if(isNaN(n)) n=0;
  var s = n.toFixed(2);
  var parts = s.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "’");
  return 'CHF ' + parts.join('.');
}
function roundHalf(x){ return Math.round((Number(x)||0)/0.5)*0.5; }
/** Eingabe Menge/Preis: Dezimalpunkt oder Komma, Werte ≥0 */
function parseAmountInput(raw){
  var s=String(raw===undefined||raw===null?'':raw).trim().replace(/\s/g,'');
  if(s===''||s==='-'||s==='+'||s==='.'||s===','||s==='-.'||s==='-,') return 0;
  s=s.replace(',','.');
  var v=parseFloat(s);
  if(isNaN(v)||v<0) return 0;
  return v;
}
function riskRelevant(label){ var l=String(label||'').trim().toLowerCase(); return (l.indexOf('flugzeit')>=0)||(l.indexOf('vorbereitung')>=0)||(l.indexOf('programmierung')>=0)||(l.indexOf('pilot')>=0); }
function extractFreeKm(note){ note=String(note||''); var m=note.match(/(\d+)\s*km/i); if(m) return Number(m[1])||0; m=note.match(/(\d+)/); if(m) return Number(m[1])||0; return 0; }
/** Risikofaktor nur aus Zeilen derselben Sektion (nicht dokumentweit) */
function sectionRisikoFaktor(sec){
  var f=1.0;
  if(!sec||!sec.lines) return f;
  sec.lines.forEach(function(l){
    if(String(l.label||'').trim().toLowerCase()==='risiko'){
      var v=Number(l.risiko); if(!isNaN(v)&&v>0) f=v;
    }
  });
  return f;
}
function displayedPrice(line, risikoF){
  if(riskRelevant(line.label)){
    var base=(line.base_preis!==undefined && line.base_preis!==null)?Number(line.base_preis):Number(line.preis||0);
    return base*risikoF;
  }
  return Number(line.preis||0);
}
function updateInvoiceRowMoney(tr, line, sec){
  if(!tr) return;
  var rf=sectionRisikoFaktor(sec);
  var ll=String(line.label||'').trim().toLowerCase();
  var tdTot=tr.querySelector('td.col-total');
  if(tdTot) tdTot.innerHTML=(ll==='risiko') ? '' : ('<b>'+chf(Number(line.total||0))+'</b>');
  var tdEff=tr.querySelector('td.col-preis-eff');
  if(tdEff){
    if(ll!=='risiko'){
      tdEff.innerHTML='<span class="mini">'+chf(displayedPrice(line,rf)).replace('CHF ','')+'</span>';
    } else {
      tdEff.textContent='';
    }
  }
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
    opts=[
      {label:'klein',value:1.0},
      {label:'mittel',value:1.2},
      {label:'gross',value:1.5},
      {label:'sehr gross',value:2.0}
    ];
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
    menge:0,
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
function normalizePosLabel(label){
  return String(label||'').trim().toLowerCase().replace(/\s+/g,' ');
}

/** Position-IDs der Kategorie-Gruppen in Prioritätsreihenfolge (niedriger = höher). */
function categoryPositionPriority(kat){
  var rank = {};
  var n = 0;
  groupIdsForCategory(kat).forEach(function(gid){
    var g = DEFAULTS.standardgruppen && DEFAULTS.standardgruppen[gid];
    if(!g || !Array.isArray(g.positionen)) return;
    g.positionen.forEach(function(pid){
      if(rank[pid] === undefined) rank[pid] = n++;
    });
  });
  return rank;
}

/**
 * Standardpositionen einmalig nach Label.
 * Bei gleichem Label: Variante aus Kategorie-Gruppen bevorzugen (Gruppenreihenfolge),
 * sonst höheren Preis (vermeidet 0-CHF-Duplikate).
 */
function uniqueStandardpositionIdsForCategory(kat){
  var sp = DEFAULTS.standardpositionen || {};
  var rank = categoryPositionPriority(kat);
  var best = {};
  Object.keys(sp).forEach(function(pid){
    var p = sp[pid];
    if(!p) return;
    var key = normalizePosLabel(p.label);
    if(key === '') return;
    var catRank = (rank[pid] !== undefined) ? rank[pid] : 999999;
    var preis = Number(p.preis || 0);
    var cand = {pid:pid, catRank:catRank, preis:preis};
    var cur = best[key];
    if(!cur){ best[key] = cand; return; }
    if(cand.catRank < cur.catRank){ best[key] = cand; return; }
    if(cand.catRank > cur.catRank) return;
    if(cand.preis > cur.preis) best[key] = cand;
  });
  var rows = Object.keys(best).map(function(k){ return best[k]; });
  rows.sort(function(a, b){
    if(a.catRank !== b.catRank) return a.catRank - b.catRank;
    var la = normalizePosLabel((sp[a.pid] && sp[a.pid].label) || a.pid);
    var lb = normalizePosLabel((sp[b.pid] && sp[b.pid].label) || b.pid);
    if(la < lb) return -1;
    if(la > lb) return 1;
    return String(a.pid).localeCompare(String(b.pid));
  });
  return rows.map(function(r){ return r.pid; });
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
  uniqueStandardpositionIdsForCategory(kat).forEach(function(pid){
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
  var sum=0;
  (DOC.sections||[]).forEach(function(sec){
    var risikoF=sectionRisikoFaktor(sec);
    (sec.lines||[]).forEach(function(l){
      var label=String(l.label||''), ll=label.trim().toLowerCase();
      var menge=parseFloat(l.menge); if(isNaN(menge)||menge<0) menge=0;
      var preis=parseFloat(l.preis); if(isNaN(preis)||preis<0) preis=0;
      var einheit=String(l.einheit||'h');
      var note=String(l.note||'');
      var total=0;
      if(ll==='risiko'){ total=0; l.menge=1; l.preis=0; l.einheit='h'; }
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(label)){ if(l.base_preis===undefined||l.base_preis===null) l.base_preis=preis; total=menge*(Number(l.base_preis||0)*risikoF); }
      else total=menge*preis;
      l.total=Math.round(total);
      sum += l.total;
    });
  });

  var rchf = Math.max(0, Number(document.getElementById('rabattCHF').value)||0);
  var rpro = Math.max(0, Number(document.getElementById('rabattProzent').value)||0);
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
    var handle=row.querySelector('.drag-handle');
    if(!handle) return;
    handle.draggable=true;
    handle.addEventListener('dragstart', function(e){
      dragging=row;
      row.classList.add('row-dragging');
      e.dataTransfer.effectAllowed='move';
      try{ e.dataTransfer.setData('text/plain','line'); }catch(err){}
    });
    handle.addEventListener('dragend', function(){
      row.classList.remove('row-dragging');
      dragging=null;
    });
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

  DOC.sections.forEach(function(sec,sIdx){
    var risikoF=sectionRisikoFaktor(sec);
    var wrap=document.createElement('div');
    wrap.className='section-block';

    var header=document.createElement('div');
    header.className='rkrk-row';
    header.style.flexWrap='wrap';
    header.style.gap='8px';
    header.style.justifyContent='space-between';
    header.style.alignItems='flex-end';

    var tdiv=document.createElement('div'); tdiv.style.flex='1';
    var tlab=document.createElement('label'); tlab.textContent='Sektionstitel';
    var tinp=document.createElement('input'); tinp.type='text'; tinp.value=sec.title||'';
    tinp.addEventListener('input',function(){ sec.title=tinp.value; });
    tdiv.appendChild(tlab); tdiv.appendChild(tinp);

    var bdiv=document.createElement('div'); bdiv.style.display='flex'; bdiv.style.gap='6px'; bdiv.style.alignItems='center';
    var upBtn=document.createElement('button'); upBtn.type='button'; upBtn.className='btn'; upBtn.textContent='▲'; upBtn.title='Sektion nach oben';
    upBtn.style.padding='0 10px'; upBtn.style.minWidth='40px';
    upBtn.disabled=(sIdx===0);
    upBtn.addEventListener('click',function(){
      if(sIdx<=0) return;
      var tmp=DOC.sections[sIdx-1]; DOC.sections[sIdx-1]=DOC.sections[sIdx]; DOC.sections[sIdx]=tmp;
      renderSections(); recalcAll();
    });
    var downBtn=document.createElement('button'); downBtn.type='button'; downBtn.className='btn'; downBtn.textContent='▼'; downBtn.title='Sektion nach unten';
    downBtn.style.padding='0 10px'; downBtn.style.minWidth='40px';
    downBtn.disabled=(sIdx>=DOC.sections.length-1);
    downBtn.addEventListener('click',function(){
      if(sIdx>=DOC.sections.length-1) return;
      var tmp=DOC.sections[sIdx+1]; DOC.sections[sIdx+1]=DOC.sections[sIdx]; DOC.sections[sIdx]=tmp;
      renderSections(); recalcAll();
    });
    var delBtn=document.createElement('button'); delBtn.type='button'; delBtn.className='btn'; delBtn.textContent='🗑 Sektion';
    delBtn.addEventListener('click',function(){ DOC.sections.splice(sIdx,1); renderSections(); recalcAll(); });
    bdiv.appendChild(upBtn); bdiv.appendChild(downBtn); bdiv.appendChild(delBtn);
    header.appendChild(tdiv); header.appendChild(bdiv);

    var tools=document.createElement('div');
    tools.className='rkrk-row';
    tools.style.flexWrap='wrap';
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
    addLine.addEventListener('click', function(){ sec.lines.push({label:'',note:'',menge:0,einheit:'h',preis:0,total:0}); renderSections(); recalcAll(); });

    tools.appendChild(posSelWrap);
    tools.appendChild(addPos);
    tools.appendChild(addLine);

    var table=document.createElement('table');
    table.className='table rechnung'; table.style.marginTop='10px';
    table.innerHTML='<thead><tr>'+
      '<th style="width:34px;">⇅</th><th>Nr.</th><th>Bezeichnung</th><th>Bemerkungen</th>'+
      '<th class="price col-menge">Menge</th><th>Einheit</th><th class="price col-preis">Basispreis</th><th class="price col-preis">Preis</th><th class="total col-total">Total</th><th></th>'+
      '</tr></thead><tbody></tbody>';
    var tbody=table.querySelector('tbody');

    sec.lines.forEach(function(line,idx){
      var isRisiko = String(line.label||'').trim().toLowerCase()==='risiko';
      var tr=document.createElement('tr'); tr.dataset.li=idx; if(isRisiko) tr.classList.add('line-risiko');

      var tdH=document.createElement('td'); tdH.className='drag-handle'; tdH.textContent='⇅'; tdH.title='Zum Sortieren ziehen';
      var tdNr=document.createElement('td'); tdNr.textContent=String(idx+1);

      var tdLab=document.createElement('td');
      var inLab=document.createElement('input'); inLab.value=line.label||'';
      var wasRisikoLabel = String(line.label||'').trim().toLowerCase()==='risiko';
      inLab.addEventListener('input',function(){
        line.label=inLab.value;
        recalcAll();
        updateInvoiceRowMoney(tr,line,sec);
      });
      inLab.addEventListener('blur',function(){
        var isRisikoLabel = String(line.label||'').trim().toLowerCase()==='risiko';
        if (isRisikoLabel !== wasRisikoLabel) {
          renderSections();
          return;
        }
        recalcAll();
        updateInvoiceRowMoney(tr,line,sec);
      });
      tdLab.appendChild(inLab);

      var tdNote=document.createElement('td');
      var inNote=document.createElement('input'); inNote.value=line.note||'';
      inNote.addEventListener('input',function(){ line.note=inNote.value; recalcAll(); });
      tdNote.appendChild(inNote);

      var tdM=document.createElement('td'); tdM.className='price col-menge';
      if(isRisiko){
        var sel=document.createElement('select');
        risikoOpts.forEach(function(o){ var op=document.createElement('option'); op.value=o.value; op.textContent=o.label; if(Number(line.risiko||1)==Number(o.value)) op.selected=true; sel.appendChild(op); });
        sel.addEventListener('change',function(){ line.risiko=Number(sel.value)||1; recalcAll(); renderSections(); });
        tdM.appendChild(sel);
      } else {
        var inM=document.createElement('input'); inM.type='number'; inM.className='no-spinner';
        inM.step='any'; inM.min='0';
        inM.value=String(Number(line.menge||0));
        inM.addEventListener('input',function(){
          line.menge=parseAmountInput(inM.value);
          recalcAll();
          updateInvoiceRowMoney(tr,line,sec);
        });
        inM.addEventListener('blur',function(){
          line.menge=parseAmountInput(inM.value);
          inM.value=line.menge===0?'0':String(line.menge);
          recalcAll();
          updateInvoiceRowMoney(tr,line,sec);
        });
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

      var tdP=document.createElement('td'); tdP.className='price col-preis col-preis-base';
      if(isRisiko){ tdP.textContent=''; }
      else {
        var inP=document.createElement('input'); inP.type='number'; inP.className='no-spinner';
        inP.step='any'; inP.min='0';
        inP.value=String(Number(line.preis||0));
        inP.addEventListener('input',function(){
          line.preis=parseAmountInput(inP.value);
          if(riskRelevant(line.label)) line.base_preis=line.preis;
          recalcAll();
          updateInvoiceRowMoney(tr,line,sec);
        });
        inP.addEventListener('blur',function(){
          line.preis=parseAmountInput(inP.value);
          if(riskRelevant(line.label)) line.base_preis=line.preis;
          inP.value=line.preis===0?'0':String(line.preis);
          recalcAll();
          updateInvoiceRowMoney(tr,line,sec);
        });
        tdP.appendChild(inP);
      }

      var tdVP=document.createElement('td'); tdVP.className='price col-preis col-preis-eff';
      if(isRisiko){ tdVP.textContent=''; }
      else { tdVP.innerHTML='<span class="mini">'+chf(displayedPrice(line,risikoF)).replace('CHF ','')+'</span>'; }

      var tdT=document.createElement('td'); tdT.className='total col-total'; tdT.innerHTML=isRisiko ? '' : ('<b>'+chf(Number(line.total||0))+'</b>');

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

    var scroll=document.createElement('div');
    scroll.className='table-scroll';
    scroll.appendChild(table);
    wrap.appendChild(header);
    wrap.appendChild(tools);
    wrap.appendChild(scroll);
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

function normKatKey(k){
  var s = String(k||'').replace(/[^A-Za-z]/g,'').toUpperCase();
  return s || 'SC';
}
/** Logo/Farbe wie category_design() aus DEFAULTS (invoice_default.json) — Fallback wenn DESIGN_BY_KAT fehlt */
function designFromInvoiceDefaults(kat){
  kat = normKatKey(kat);
  var kats = DEFAULTS.kategorien || {};
  var kc = kats[kat];
  if(!kc){
    Object.keys(kats).some(function(key){
      if(normKatKey(key)===kat){ kc = kats[key]; return true; }
      return false;
    });
  }
  if(!kc) return {logo:'', color:'#333'};
  var color = String(kc.farbe||kc.color||'').trim();
  if(!color){
    var st = String(kc.styleset||'').trim();
    var ss = DEFAULTS.stylesets && DEFAULTS.stylesets[st];
    if(ss && ss.primary) color = String(ss.primary);
  }
  if(!color) color = '#333';
  function webPath(p){
    if(!p) return '';
    p = String(p).replace(/\\/g,'/').trim();
    if(/^https?:\/\//i.test(p)) return p;
    p = p.replace(/^\/+/,'');
    if(/^data\/rt\//i.test(p) || /^\.\.\/data\/rt\//i.test(p)) return p;
    if(/^images\//i.test(p)) return MEDIA_WEB_BASE.replace(/\/+$/,'') + '/' + p;
    if(p.indexOf('/')<0) return MEDIA_WEB_BASE.replace(/\/+$/,'') + '/images/' + p;
    return p;
  }
  function resolveLogosetKey(ls, lsets){
    ls = String(ls||'').trim();
    if(!ls || !lsets) return '';
    if(lsets[ls]) return ls;
    var keys = Object.keys(lsets);
    for(var i=0;i<keys.length;i++){
      if(String(keys[i]).toLowerCase()===ls.toLowerCase()) return keys[i];
    }
    return '';
  }
  var logo = '';
  var lsRaw = String(kc.logoset||'').trim();
  var lsets = DEFAULTS.logosets || {};
  var ls = resolveLogosetKey(lsRaw, lsets);
  if(ls && lsets[ls] && lsets[ls].logo){
    logo = webPath(lsets[ls].logo);
  }
  if(!logo){
    if(kc.logo) logo = webPath(kc.logo);
    else if(kc.logo_sky) logo = webPath('logo_sky.png');
    else if(kc.logo_rk) logo = webPath('logo_rk.png');
    else if(kc.watermarks) logo = webPath(kc.watermarks);
    else if(kc.swissqrcode) logo = webPath(kc.swissqrcode);
  }
  return {logo:logo, color:color};
}
function applyEditorDesign(rawKat){
  var kat = normKatKey(rawKat);
  var dPhp = (DESIGN_BY_KAT && DESIGN_BY_KAT[kat]) ? DESIGN_BY_KAT[kat] : null;
  var dJs = designFromInvoiceDefaults(kat);
  var logo = (dPhp && dPhp.logo) ? dPhp.logo : dJs.logo;
  var color = (dPhp && dPhp.color) ? dPhp.color : (dJs.color || '#333');
  if(!logo){
    var ks = Object.keys(DESIGN_BY_KAT||{});
    for(var i=0;i<ks.length;i++){
      if(DESIGN_BY_KAT[ks[i]] && DESIGN_BY_KAT[ks[i]].logo){ logo = DESIGN_BY_KAT[ks[i]].logo; break; }
    }
  }
  var img = document.getElementById('editorLogo');
  if(img){
    if(logo){
      if(/^https?:\/\//i.test(logo)){
        img.src = logo;
      } else {
        var sep = logo.indexOf('?') >= 0 ? '&' : '?';
        img.src = logo + sep + 'kat=' + encodeURIComponent(kat);
      }
      img.style.display='';
    } else {
      img.removeAttribute('src');
      img.style.display='none';
    }
  }
  document.documentElement.style.setProperty('--kat-color', color || '#333');
}
var katEl = document.getElementById('kategorie');
if(katEl){
  function onKategorieUiUpdate(){
    applyEditorDesign(katEl.value);
    renderSections();
    recalcAll();
  }
  katEl.addEventListener('change', onKategorieUiUpdate);
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
  payload.einleitung=document.getElementById('einleitung').value;
  payload.abschluss=document.getElementById('abschluss').value;
  payload.pdf_details_positionen=document.getElementById('pdf_details_positionen').checked;
  payload.pdf_positionen_sektionen=document.getElementById('pdf_positionen_sektionen').checked;
  payload.pdf_sektionen=document.getElementById('pdf_sektionen').checked;

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
    .then(function(res){ if(!res.ok){alert(res.text);return;} location.href=AFTER_SAVE; })
    .catch(function(err){ alert(String(err)); });
});
</script>

<?php elseif($mode==='examples'): ?>

<div class="top-header"><h1>Beispielrechnungen</h1><img class="top-logo" alt="Logo" src="<?= !empty($listHeaderDesign['logo']) ? h($listHeaderDesign['logo']) : '' ?>" style="<?= empty($listHeaderDesign['logo']) ? 'display:none;' : '' ?>"></div>
<div class="data-dir-hint <?= $dataDirExists ? '' : 'bad' ?>">Data directory: <?= h($dataDirInfo) ?></div>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
  <a class="btn-primary" href="?mode=example">Neue Beispielrechnung</a>
  <a class="btn" href="?mode=list">Zurück zur Übersicht</a>
  <a class="btn" href="rkrk_rt_admin.php">Admin</a>
</div>
<div id="examplesRoot"></div>

<script>
var EXAMPLES = <?= json_encode($examplesAll, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var DEFAULTS = <?= json_encode($DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var DESIGN_BY_KAT = <?= json_encode($designByKat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var SAVE_URL = '?mode=save_example';
var EX_SAVE_TIMERS = Object.create(null);

function chf(n){ n=Number(n); if(isNaN(n)) n=0; var s=n.toFixed(2).split('.'); s[0]=s[0].replace(/\B(?=(\d{3})+(?!\d))/g,"’"); return 'CHF '+s.join('.'); }
function roundHalf(x){ return Math.round((Number(x)||0)/0.5)*0.5; }
function parseAmountInput(raw){
  var s=String(raw===undefined||raw===null?'':raw).trim().replace(/\s/g,'');
  if(s===''||s==='-'||s==='+'||s==='.'||s===','||s==='-.'||s==='-,') return 0;
  s=s.replace(',','.');
  var v=parseFloat(s);
  if(isNaN(v)||v<0) return 0;
  return v;
}
function riskRelevant(label){ var l=String(label||'').trim().toLowerCase(); return (l.indexOf('flugzeit')>=0)||(l.indexOf('vorbereitung')>=0)||(l.indexOf('programmierung')>=0)||(l.indexOf('pilot')>=0); }
function extractFreeKm(note){ note=String(note||''); var m=note.match(/(\d+)\s*km/i); if(m) return Number(m[1])||0; m=note.match(/(\d+)/); if(m) return Number(m[1])||0; return 0; }
function sectionRisikoFaktor(sec){
  var f=1.0;
  if(!sec||!sec.lines) return f;
  sec.lines.forEach(function(l){
    if(String(l.label||'').trim().toLowerCase()==='risiko'){
      var v=Number(l.risiko); if(!isNaN(v)&&v>0) f=v;
    }
  });
  return f;
}
function fmt(n){ n=Number(n); if(isNaN(n)) n=0; return n.toFixed(2); }
function billedPrice(line, risikoF){
  if(riskRelevant(line.label)){
    var base = (line.base_preis!==undefined && line.base_preis!==null) ? Number(line.base_preis) : Number(line.preis||0);
    return base * risikoF;
  }
  return Number(line.preis||0);
}

function recalcExample(ex){
  var sum=0;
  (ex.sections||[]).forEach(function(sec){
    var risikoF=sectionRisikoFaktor(sec);
    (sec.lines||[]).forEach(function(l){
      var ll=String(l.label||'').trim().toLowerCase();
      var menge=parseFloat(l.menge); if(isNaN(menge)||menge<0) menge=0;
      var preis=parseFloat(l.preis); if(isNaN(preis)||preis<0) preis=0;
      var einheit=String(l.einheit||'h');
      var note=String(l.note||'');
      var total=0;
      if(ll==='risiko') total=0;
      else if(ll.indexOf('fahrspesen')>=0 && einheit.toLowerCase()==='km'){ var inkl=extractFreeKm(note); var verr=menge-inkl; if(verr<0) verr=0; total=verr*preis; }
      else if(riskRelevant(l.label)) total=menge*billedPrice(l,risikoF);
      else total=menge*preis;
      l.total=Math.round(total);
      l.menge = menge;
      l.preis = preis;
      sum += l.total;
    });
  });
  ex.zwischensumme=Math.round(sum*100)/100;
  var rchf=Math.max(0, Number(ex.rabattCHF||0));
  var rpro=Math.max(0, Number(ex.rabattProzent||0));
  ex.rabattCHF=rchf; ex.rabattProzent=rpro;
  ex.total=Math.round((sum-(sum*(rpro/100))-rchf)*100)/100;
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
    opts=[
      {label:'klein',value:1.0},
      {label:'mittel',value:1.2},
      {label:'gross',value:1.5},
      {label:'sehr gross',value:2.0}
    ];
  }
  return opts;
}

function normKatKeyEx(k){
  var s = String(k||'').replace(/[^A-Za-z]/g,'').toUpperCase();
  return s || 'SC';
}
function exampleCardLogoSrc(katRaw){
  var kat = normKatKeyEx(katRaw);
  var d = DESIGN_BY_KAT && DESIGN_BY_KAT[kat];
  var logo = d && d.logo ? d.logo : '';
  if(!logo) return '';
  if(/^https?:\/\//i.test(logo)) return logo;
  var sep = logo.indexOf('?') >= 0 ? '&' : '?';
  return logo + sep + 'kat=' + encodeURIComponent(kat);
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

    var headMain=document.createElement('div');
    headMain.style.display='flex';
    headMain.style.gap='12px';
    headMain.style.alignItems='flex-start';
    headMain.style.flex='1';
    headMain.style.minWidth='0';

    var logoSrc = exampleCardLogoSrc(ex.kategorie);
    if(logoSrc){
      var lim=document.createElement('img');
      lim.className='top-logo';
      lim.alt='Logo';
      lim.style.maxHeight='52px';
      lim.style.maxWidth='180px';
      lim.style.objectFit='contain';
      lim.style.flexShrink='0';
      lim.src=logoSrc;
      headMain.appendChild(lim);
    }

    var meta=document.createElement('div');
    meta.style.minWidth='0';
    var title = ex.betreff ? ex.betreff : ('Beispiel ' + (ex.id||''));
    var h3=document.createElement('h3'); h3.textContent=title; h3.style.margin='0';
    meta.appendChild(h3);
    var pills=document.createElement('div'); pills.className='mini';
    var p1=document.createElement('span'); p1.className='pill'; p1.textContent=String(ex.id||'');
    var p2=document.createElement('span'); p2.className='pill'; p2.textContent=String(ex.kategorie||'');
    pills.appendChild(p1); pills.appendChild(document.createTextNode(' ')); pills.appendChild(p2);
    meta.appendChild(pills);
    var sums=document.createElement('div');
    sums.className='mini ex-card-sums';
    sums.style.marginTop='6px';
    sums.innerHTML='Zwischensumme: <b>'+chf(ex.zwischensumme)+'</b> | Rabatt %: <b>'+String(Number(ex.rabattProzent||0))+'</b> | Rabatt CHF: <b>'+chf(ex.rabattCHF)+'</b> | Total: <b>'+chf(ex.total)+'</b>';
    meta.appendChild(sums);
    headMain.appendChild(meta);

    var actions=document.createElement('div');
    actions.innerHTML = '<a class="btn" href="?mode=example&id='+encodeURIComponent(ex.id)+'">✏️ Edit</a> ' +
                        '<a class="btn" href="rkrk_pdf.php?id='+encodeURIComponent(ex.id)+'&src=example" target="_blank">PDF</a> ' +
                        '<button class="btn" type="button">💾 Speichern</button> ' +
                        '<a class="btn" href="?mode=delete_example&id='+encodeURIComponent(ex.id)+'" onclick="return confirm(\'Beispiel wirklich löschen?\')">🗑</a>';
    actions.querySelector('button').addEventListener('click', function(){ saveExample(ex); });
    head.appendChild(headMain);
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
      st.appendChild(titleRow);

      var table=document.createElement('table');
      table.innerHTML = '<thead><tr>'+
        '<th style="width:34px;">⇅</th><th>Bezeichnung</th><th>Bemerkung</th>'+
        '<th class="price" style="min-width:7rem;width:7.5rem;">Menge</th><th style="width:90px;">Einheit</th>'+
        '<th class="price" style="width:120px;">Basispreis</th><th class="price" style="width:140px;">Preis</th>'+
        '<th class="total" style="width:140px;">Total</th>'+
        '</tr></thead><tbody></tbody>';
      var tbody=table.querySelector('tbody');

      var lines = (sec.lines||[]);
      lines.forEach(function(line, li){
        var ll = String(line.label||'').trim().toLowerCase();
        // Zeilen mit menge 0 ausblenden (Risiko immer sichtbar)
        if(ll!=='risiko' && (!(parseFloat(line.menge)>0))) return;
        // Nur Zeilen mit Grundpreis>0 anzeigen (Risiko immer)
        if(ll!=='risiko' && (!(parseFloat(line.preis)>0))) return;

        var tr=document.createElement('tr');
        tr.draggable=true;
        tr.dataset.si=String(si);
        tr.dataset.li=String(li);
        if(ll==='risiko') tr.classList.add('line-risiko');

        var tdH=document.createElement('td'); tdH.className='dnd-handle'; tdH.textContent='⇅';
        var tdLab=document.createElement('td'); tdLab.textContent=line.label||'';
        var tdNote=document.createElement('td'); tdNote.textContent=line.note||'';

        var tdM=document.createElement('td'); tdM.className='price';
        if(ll==='risiko'){
          var sel=document.createElement('select');
          var risikoOptsEx=parseRisikoOptions();
          var curR=Number(line.risiko||1);
          risikoOptsEx.forEach(function(o){
            var op=document.createElement('option'); op.value=String(o.value); op.textContent=o.label;
            if(Math.abs(curR-Number(o.value))<1e-6) op.selected=true;
            sel.appendChild(op);
          });
          sel.addEventListener('change', function(){
            line.risiko=Number(sel.value)||1;
            queueExampleSave(ex);
            renderExamples();
          });
          tdM.appendChild(sel);
        } else {
          var inM=document.createElement('input');
          inM.type='number'; inM.className='no-spinner';
          inM.step='any'; inM.min='0';
          inM.value=String(Number(line.menge||0));
          inM.addEventListener('input', function(){
            line.menge=parseAmountInput(inM.value);
            recalcExample(ex);
            var tdT=tr.querySelector('td.total');
            if(tdT) tdT.innerHTML=(ll==='risiko') ? '' : ('<b>'+chf(Number(line.total||0))+'</b>');
            var card=tr.closest('.example-card');
            if(card){
              var sumEl=card.querySelector('.ex-card-sums');
              if(sumEl) sumEl.innerHTML='Zwischensumme: <b>'+chf(ex.zwischensumme)+'</b> | Rabatt %: <b>'+String(Number(ex.rabattProzent||0))+'</b> | Rabatt CHF: <b>'+chf(ex.rabattCHF)+'</b> | Total: <b>'+chf(ex.total)+'</b>';
            }
            queueExampleSave(ex);
          });
          inM.addEventListener('blur', function(){
            line.menge=parseAmountInput(inM.value);
            inM.value=line.menge===0?'0':String(line.menge);
            recalcExample(ex);
            var tdT=tr.querySelector('td.total');
            if(tdT) tdT.innerHTML=(ll==='risiko') ? '' : ('<b>'+chf(Number(line.total||0))+'</b>');
            var card=tr.closest('.example-card');
            if(card){
              var sumEl=card.querySelector('.ex-card-sums');
              if(sumEl) sumEl.innerHTML='Zwischensumme: <b>'+chf(ex.zwischensumme)+'</b> | Rabatt %: <b>'+String(Number(ex.rabattProzent||0))+'</b> | Rabatt CHF: <b>'+chf(ex.rabattCHF)+'</b> | Total: <b>'+chf(ex.total)+'</b>';
            }
            queueExampleSave(ex);
          });
          tdM.appendChild(inM);
        }

        var tdE=document.createElement('td'); tdE.textContent = (ll==='risiko') ? '' : (line.einheit||'');
        var tdP=document.createElement('td'); tdP.className='price'; tdP.textContent = (ll==='risiko') ? '' : fmt(Number(line.preis||0));
        var rf=sectionRisikoFaktor(sec);
        var tdBP=document.createElement('td'); tdBP.className='price'; tdBP.textContent = (ll==='risiko') ? '' : fmt(billedPrice(line, rf));
        var tdT=document.createElement('td'); tdT.className='total'; tdT.innerHTML = (ll==='risiko') ? '' : ('<b>'+chf(Number(line.total||0))+'</b>');

        tr.appendChild(tdH); tr.appendChild(tdLab); tr.appendChild(tdNote); tr.appendChild(tdM); tr.appendChild(tdE); tr.appendChild(tdP); tr.appendChild(tdBP); tr.appendChild(tdT);
        tbody.appendChild(tr);
      });

      enableDnDExample(tbody, ex, si);
      var scroll=document.createElement('div');
      scroll.className='table-scroll';
      scroll.appendChild(table);
      st.appendChild(scroll);
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

function saveExample(ex, opts){
  opts = opts || {};
  var silent = !!opts.silent;
  fetch(SAVE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(ex)})
    .then(function(r){ return r.text().then(function(t){ return {ok:r.ok, status:r.status, text:t};}); })
    .then(function(res){
      if(!res.ok){ if(!silent) alert(res.text); return; }
      if(!silent) alert('Gespeichert: '+(ex.id||''));
    })
    .catch(function(err){ alert(String(err)); });
}
function queueExampleSave(ex){
  var key = String(ex.id||'');
  if(key==='') return;
  if(EX_SAVE_TIMERS[key]) clearTimeout(EX_SAVE_TIMERS[key]);
  EX_SAVE_TIMERS[key] = setTimeout(function(){
    saveExample(ex, {silent:true});
    delete EX_SAVE_TIMERS[key];
  }, 500);
}

renderExamples();
</script>

<?php endif; ?>

</div>
</body>
</html>
