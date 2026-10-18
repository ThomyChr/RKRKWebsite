<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
/*
	Mobile/Tablet Compact-UI-Variante von rkrk_fluege.php (Stand-alone Kopie).
	https://tchr-frog.synology.me/rkropen/test/rkrk_fluege26.php?MDB=M3T&EnableDelete=off&SelFrom=2024-04-11&SelTo=2024-04-18
	https://tchr-frog.synology.me/rkropen/test/rkrk_fluege26.php?EnableDelete=off&DBW=off&EnableCon=off&EnableNeu=off
	https://tchr-frog.synology.me/rkropen/test/rkrk_fluege26.php?Free=FID000003+FID000001+FID000002&MDB=M3T&EnableDelete=off&EnableNeu=off&Modus=off&SelFrom=2024-04-11&SelTo=2024-04-18
		
	MDB : Platform library M2; M3T; ""=Debug
	EnableDelete : Löschen ermöglichen (on/off)
	DoSafe : Löschen ermöglichen (on/off)
	EnableNeu: Neuen Eingaben ermöglichen (on/off)
	EnableCon: Konvertierungsfunktionen aktivieren (on/off)
	Modus : (on/off)
	delete : Löschen aktivieren
	DelFlugID : Lösche FlugID
	SelFrom : Datum von YYYY-MM-DD
	SelTo : Datum bis YYYY-MM-DD
	add: hinzufügen aktiveren
	AddFlugID: Flüge Flug hinzu
	SaveLine: Speichere Linie
	FeldNamenWahl: Auswahl Feld (Name)
	PilotenWahl: Auswahl Pilot (Kürzel z.B. TC, CH, VP)
	Free : Gebe Flüge frei (Free=FID000003+FID000001+FID00000)
	Lock : Sperre Flüge	(Lock=FID000003+FID000001+FID000002)
*/

	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_menu.php'); 
	include_once('rkrk_access_control.php'); 
	include_once('scripts/rkrk_jsonfiles.php');
	include_once('scripts/rkrk_kmz.php');
	include_once('scripts/rkrk_einsatz_shared.php');

	//check impropriate IP addresses accessing page
	CheckIPandExit();

	$pagetitle = "Flugbuchungen";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
	$droneName = (string)($MissionDroneTypes[$RKRKMissionSelected]['Name'] ?? $RKRKMissionSelected);
	$droneBtn = (string)($MissionDroneTypes[$RKRKMissionSelected]['button'] ?? '');
	echo "<div style='display:flex;justify-content:flex-end;align-items:center;margin:4px 12px 0;' title='" . htmlspecialchars($droneName, ENT_QUOTES, 'UTF-8') . "'>";
	if ($droneBtn !== '') {
		echo "<img src='" . htmlspecialchars($IconLoc . '/' . $droneBtn, ENT_QUOTES, 'UTF-8') . "' alt='' height='28'>";
	}
	echo "</div>\n";
echo "<script>
function StartPilotDownloads(urls){
	if (!Array.isArray(urls) || urls.length === 0) {
		alert('Keine KMZ/JSON für diesen Pilotentag gefunden.');
		return false;
	}
	let i = 0;
	function next(){
		if (i >= urls.length) return;
		const a = document.createElement('a');
		a.href = urls[i];
		if (String(urls[i]).indexOf('kml=1') === -1) {
			a.setAttribute('download', '');
		}
		a.style.display = 'none';
		document.body.appendChild(a);
		a.click();
		a.remove();
		i++;
		setTimeout(next, 180);
	}
	next();
	return false;
}
document.addEventListener('click', function (ev) {
	var a = ev.target.closest('a[data-kml]');
	if (!a) return;
	var kml = a.getAttribute('data-kml');
	if (!kml) return;
	setTimeout(function () { StartPilotDownloads([kml]); }, 250);
});
</script>";
echo "<style>
.rkrk-flight-controls-wrap,
.rkrk-flight-table-wrap{
  width:100%;
  overflow:visible;
}
.rkrk-flight-main{
  background:#fff;
}
.rkrk-flight-main tbody tr:nth-child(odd){
  background:#f6f7f9;
}
.rkrk-flight-main tbody tr:nth-child(even){
  background:#eceff3;
}
@media (min-width: 1301px){
  .rkrk-flight-main{
    width:100%;
    min-width:0;
  }
  .rkrk-flight-main th,
  .rkrk-flight-main td{
    padding:1px 3px;
    white-space:nowrap;
    vertical-align:middle;
    line-height:1.15;
  }
  .rkrk-flight-main input:not([type='image']):not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='hidden']),
  .rkrk-flight-main select,
  .rkrk-flight-main textarea{
    border:1px solid #d8e4d4 !important;
    border-radius:3px;
    padding:0 3px !important;
    height:22px;
    min-height:22px;
    max-height:24px;
    line-height:20px;
    background:#fff;
    box-sizing:border-box;
    vertical-align:middle;
  }
  .rkrk-flight-main input[readonly]{
    background:#f3f6f3 !important;
    border-color:#e4ece0 !important;
    color:#5b6570;
    cursor:default;
  }
  .rkrk-flight-main textarea{
    height:auto;
    max-height:none;
    overflow:hidden;
    white-space:nowrap;
  }
  .rkrk-flight-main input[type='date']{ min-width:9.2em; }
  .rkrk-flight-main input[type='time']{ min-width:6.2em; }
  .rkrk-flight-main input[id^='Felder-'],
  .rkrk-flight-main input[id^='Kontaktnummer-'],
  .rkrk-flight-main input[id^='Bemerkung-']{
    max-width:16em;
  }
}
@media (min-width: 1301px){
  .rkrk-flight-main.rkrk-meta-collapsed .rkrk-col-meta{
    display:none;
  }
  .rkrk-flight-main.rkrk-meta-collapsed tr.rkrk-row-manual .rkrk-col-meta{
    display:table-cell;
  }
  .rkrk-flight-main.rkrk-meta-collapsed .rkrk-meta-toggle{
    transform:rotate(-90deg);
  }
  .rkrk-flight-main input[id^='Felder-'],
  .rkrk-flight-main input[id^='Kontaktnummer-'],
  .rkrk-flight-main input[id^='Bemerkung-']{
    max-width:12em;
  }
}
.rkrk-files-toggle,
.rkrk-meta-toggle{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:20px;
  height:20px;
  padding:0;
  margin:0;
  border:1px solid #c5d4c0;
  border-radius:4px;
  background:#f3f6f3;
  color:#3d5a40;
  cursor:pointer;
  font-size:11px;
  line-height:1;
  vertical-align:middle;
}
.rkrk-files-toggle:hover,
.rkrk-meta-toggle:hover{
  background:#e6efe4;
}
.rkrk-col-files-toggle,
.rkrk-col-meta-toggle{
  width:1.6em;
  text-align:center;
  white-space:nowrap;
}
@media (min-width: 1301px){
  .rkrk-flight-main.rkrk-files-collapsed .rkrk-col-files{
    display:none;
  }
  .rkrk-flight-main.rkrk-files-collapsed .rkrk-files-toggle{
    transform:rotate(-90deg);
  }
}
.rkrk-flight-summary-th,
.rkrk-flight-summary-cell{
  display:none;
}
.rkrk-flight-edit-backdrop,
.rkrk-flight-edit-sheet{
  display:none;
}
@media (min-width: 1301px){
  .rkrk-desktop-scroll{
    width:auto;
    max-width:none;
    margin-left:0;
    margin-right:0;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    padding-bottom:4px;
  }
  .rkrk-desktop-scroll > table{
    min-width:100%;
  }
}
@media (max-width: 1300px){
  .rkrk-flight-save-sticky{
    position:sticky;
    top:0;
    z-index:40;
    background:#fff;
    padding:6px 0 8px 0;
    border-bottom:1px solid #d0d5dd;
  }
  .rkrk-flight-main tr.rkrk-mobile-header-row{
    display:none !important;
  }
  .rkrk-flight-main{
    width:100%;
    border-collapse:separate;
    border-spacing:0 6px;
  }
  .rkrk-flight-main tr.rkrk-flight-sep-row,
  .rkrk-flight-main tr.rkrk-flight-label-row{
    display:block;
    position:sticky;
    top:48px;
    z-index:30;
    margin:0 0 6px 0;
    padding:8px 10px;
    border:1px solid #cfd5de;
    border-radius:8px;
    background:#e8edf3;
    box-shadow:0 1px 0 rgba(0,0,0,.04);
  }
  .rkrk-flight-main tr.rkrk-flight-label-row{
    top:auto;
    position:relative;
    background:#f3f5f8;
  }
  .rkrk-flight-main tr.rkrk-flight-sep-row td,
  .rkrk-flight-main tr.rkrk-flight-label-row td{
    display:block;
    border:none;
    padding:2px 0;
  }
  .rkrk-flight-main tr.rkrk-flight-sep-row td::before,
  .rkrk-flight-main tr.rkrk-flight-label-row td::before{
    content:none;
  }
  .rkrk-flight-main tr.rkrk-flight-data-row{
    display:block;
    margin:0 0 6px 0;
    padding:0;
    border:1px solid #d5dae2;
    border-radius:10px;
    background:#f7f8fa;
    overflow:hidden;
  }
  .rkrk-flight-summary-cell{
    display:block !important;
    border:none;
    padding:0;
  }
  .rkrk-flight-main tr.rkrk-flight-data-row > td:not(.rkrk-flight-summary-cell){
    position:absolute !important;
    left:-9999px !important;
    width:1px !important;
    height:1px !important;
    overflow:hidden !important;
    margin:0 !important;
    padding:0 !important;
    border:none !important;
  }
  .rkrk-flight-summary{
    display:block;
    width:100%;
    box-sizing:border-box;
    padding:10px 12px;
    text-align:left;
    border:none;
    background:transparent;
    cursor:pointer;
    font:inherit;
    color:inherit;
  }
  .rkrk-flight-summary:active{
    background:#eef2f7;
  }
  .rkrk-flight-summary-line1{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:6px 10px;
    font-size:15px;
    line-height:1.35;
    font-weight:600;
    color:#1f2933;
  }
  .rkrk-flight-summary-line2,
  .rkrk-flight-summary-line3,
  .rkrk-flight-summary-line4{
    margin-top:4px;
    font-size:14px;
    line-height:1.35;
    color:#445;
  }
  .rkrk-flight-summary-line2{
    display:flex;
    flex-wrap:wrap;
    align-items:baseline;
    gap:4px 12px;
    white-space:normal;
  }
  .rkrk-flight-summary-feld{
    min-width:0;
    flex:1 1 auto;
    font-weight:600;
    color:#1f2933;
  }
  .rkrk-flight-summary-jaeger{
    min-width:0;
    flex:1 1 12rem;
    color:#445;
  }
  .rkrk-flight-summary-line3,
  .rkrk-flight-summary-line4{
    white-space:normal;
    word-break:break-word;
  }
  .rkrk-flight-summary-meta{
    color:#5b6570;
    font-weight:500;
  }
  .rkrk-flight-summary-meta-item{
    font-weight:500;
    color:#445;
    white-space:nowrap;
  }
  .rkrk-flight-summary-meta-item .rkrk-flight-summary-meta{
    font-size:12px;
    font-weight:600;
  }
  .rkrk-flight-summary-cell > .rkrk-flight-summary-actions-wrap{
    padding:0 12px 8px 12px;
  }
  .rkrk-flight-summary-actions-toggle{
    display:inline-flex;
    align-items:center;
    gap:4px;
    margin:0;
    padding:2px 8px;
    border:1px solid #cfd5de;
    border-radius:999px;
    background:#f3f5f8;
    color:#445;
    font:inherit;
    font-size:12px;
    font-weight:600;
    line-height:1.4;
    cursor:pointer;
  }
  .rkrk-flight-summary-actions-wrap.is-empty{ display:none; }
  .rkrk-flight-summary-actions-wrap:not(.is-open) .rkrk-flight-summary-actions{ display:none; }
  .rkrk-flight-summary-actions{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:6px;
  }
  .rkrk-flight-summary-actions a{
    display:inline-flex;
    align-items:center;
  }
  .rkrk-flight-summary-actions img{
    height:26px;
    width:auto;
    vertical-align:middle;
  }
  .rkrk-flight-chip-kontroll{
    background:#e3eefc;
    color:#1a4b8c;
  }
  .rkrk-flight-chip{
    display:inline-block;
    padding:1px 7px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
    line-height:1.4;
  }
  .rkrk-flight-chip-geflogen-ja{
    background:#d8f3df;
    color:#146c2e;
  }
  .rkrk-flight-chip-geflogen-nein{
    background:#fde2e1;
    color:#8a1f17;
  }
  .rkrk-flight-chip-geflogen-leer{
    background:#e6e8ec;
    color:#5b6570;
  }
  .rkrk-flight-chip-fund{
    background:#fff3cd;
    color:#7a5b00;
  }
  .rkrk-flight-chip-fund-leer{
    background:transparent;
    color:#8a93a0;
    font-weight:500;
    padding-left:0;
    padding-right:0;
  }
  .rkrk-flight-edit-backdrop{
    display:none;
    position:fixed;
    inset:0;
    z-index:10040;
    background:rgba(20,28,40,.45);
  }
  .rkrk-flight-edit-backdrop.rkrk-open{
    display:block;
  }
  .rkrk-flight-edit-sheet{
    display:none;
    position:fixed;
    left:0;
    right:0;
    bottom:0;
    z-index:10050;
    max-height:85vh;
    overflow:auto;
    -webkit-overflow-scrolling:touch;
    box-sizing:border-box;
    padding:12px 12px 20px 12px;
    border-radius:14px 14px 0 0;
    background:#fff;
    box-shadow:0 -8px 28px rgba(0,0,0,.22);
  }
  .rkrk-flight-edit-sheet.rkrk-open{
    display:block;
  }
  .rkrk-flight-edit-toolbar{
    display:flex;
    gap:8px;
    position:sticky;
    top:0;
    z-index:2;
    background:#fff;
    padding:0 0 10px 0;
    margin-bottom:6px;
    border-bottom:1px solid #e3e7ee;
  }
  .rkrk-flight-edit-toolbar button{
    flex:1;
    min-height:42px;
    border-radius:8px;
    border:1px solid #c5ccd6;
    background:#f3f5f8;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
  }
  .rkrk-flight-edit-save{
    background:#2f9e44 !important;
    border-color:#2f9e44 !important;
    color:#fff !important;
  }
  .rkrk-flight-edit-primary,
  .rkrk-flight-edit-secondary{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:8px 10px;
  }
  .rkrk-flight-edit-sheet td{
    display:block;
    box-sizing:border-box;
    border:none;
    padding:2px;
  }
  .rkrk-flight-edit-sheet td::before{
    content:attr(data-label);
    display:block;
    margin:0 0 3px 0;
    font-weight:600;
    color:#445;
    font-size:12px;
  }
  .rkrk-flight-edit-sheet td.rkrk-mobile-iconcell::before,
  .rkrk-flight-edit-sheet td.rkrk-col-action::before{
    content:none;
    display:none;
  }
  .rkrk-flight-edit-sheet td.rkrk-mobile-iconcell,
  .rkrk-flight-edit-sheet td.rkrk-col-action{
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
  }
  .rkrk-flight-edit-sheet td input,
  .rkrk-flight-edit-sheet td select,
  .rkrk-flight-edit-sheet td textarea{
    width:100% !important;
    max-width:none !important;
    box-sizing:border-box;
    min-height:40px;
    font-size:15px;
  }
  .rkrk-flight-edit-sheet td input[type='image']{
    min-height:0 !important;
    width:auto !important;
  }
  .rkrk-flight-edit-sheet details.rkrk-flight-edit-more{
    margin-top:12px;
    border-top:1px solid #e3e7ee;
    padding-top:8px;
  }
  .rkrk-flight-edit-sheet details.rkrk-flight-edit-more > summary{
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    padding:6px 0 10px 0;
  }
  body.rkrk-flight-editing{
    overflow:hidden;
  }
}
@media (max-width: 700px){
  .rkrk-flight-edit-primary,
  .rkrk-flight-edit-secondary{
    grid-template-columns:1fr;
  }
  .rkrk-flight-summary-line1{
    font-size:15px;
  }
}
</style>";
echo "<script>
function rkrkFlightFieldValue(line, name){
  var el = document.getElementById(name + '-' + line);
  if (!el) return '';
  if (el.tagName === 'SELECT') {
    return String(el.value || '').trim();
  }
  return String(el.value || '').trim();
}
function rkrkFlightLineFromRow(row){
  if (!row) return '';
  var el = row.querySelector('[id^=\"Datum-\"], [id^=\"Zeit-\"], [id^=\"Feld-\"], [id^=\"Pilot-\"]');
  if (!el || !el.id) return '';
  var m = el.id.match(/-(\\d+)$/);
  return m ? m[1] : '';
}
function rkrkBuildFlightSummaryHtml(line){
  var zeit = rkrkFlightFieldValue(line, 'Zeit') || '–';
  var pilot = rkrkFlightFieldValue(line, 'Pilot') || '–';
  var feld = rkrkFlightFieldValue(line, 'Feld') || '–';
  var felder = rkrkFlightFieldValue(line, 'Felder') || '';
  var fzeit = rkrkFlightFieldValue(line, 'Flugzeit');
  var flaeche = rkrkFlightFieldValue(line, 'Flaeche');
  var jg = rkrkFlightFieldValue(line, 'JG');
  var gde = rkrkFlightFieldValue(line, 'Gde');
  var geflogen = rkrkFlightFieldValue(line, 'Geflogen');
  var fund = rkrkFlightFieldValue(line, 'Fund');
  var kontroll = rkrkFlightFieldValue(line, 'Kontrollflug');
  var jaeger = rkrkFlightFieldValue(line, 'Jaeger');
  var kontakt = rkrkFlightFieldValue(line, 'Kontaktnummer');
  var bemerkung = rkrkFlightFieldValue(line, 'Bemerkung');
  var gefClass = 'rkrk-flight-chip-geflogen-leer';
  var gefText = geflogen || '–';
  if (geflogen === 'Ja') gefClass = 'rkrk-flight-chip-geflogen-ja';
  else if (geflogen === 'Nein') gefClass = 'rkrk-flight-chip-geflogen-nein';
  var fundClass = fund ? 'rkrk-flight-chip-fund' : 'rkrk-flight-chip-fund-leer';
  var fundText = fund ? ('Fund ' + fund) : 'Fund –';
  var kontrollChip = '';
  if (kontroll) {
    kontrollChip = '<span class=\"rkrk-flight-chip rkrk-flight-chip-kontroll\">Ktrl ' + rkrkEsc(kontroll) + '</span>';
  }
  var metaBits = '';
  if (fzeit) metaBits += '<span class=\"rkrk-flight-summary-meta-item\"><span class=\"rkrk-flight-summary-meta\">FZeit</span> ' + rkrkEsc(fzeit) + '</span>';
  if (flaeche) metaBits += '<span class=\"rkrk-flight-summary-meta-item\"><span class=\"rkrk-flight-summary-meta\">Fläche</span> ' + rkrkEsc(flaeche) + '</span>';
  if (jg) metaBits += '<span class=\"rkrk-flight-summary-meta-item\"><span class=\"rkrk-flight-summary-meta\">JG</span> ' + rkrkEsc(jg) + '</span>';
  if (gde) metaBits += '<span class=\"rkrk-flight-summary-meta-item\"><span class=\"rkrk-flight-summary-meta\">Gde</span> ' + rkrkEsc(gde) + '</span>';
  var sub = felder ? felder : '—';
  var jaegerFull = kontakt || jaeger || '';
  var bemLine = bemerkung ? ('Bemerkung: ' + bemerkung) : '';
  return '<div class=\"rkrk-flight-summary-line1\">' 
    + '<span>' + rkrkEsc(zeit) + '</span>'
    + '<span>' + rkrkEsc(pilot) + '</span>'
    + '<span>' + rkrkEsc(feld) + '</span>'
    + metaBits
    + '<span class=\"rkrk-flight-chip ' + gefClass + '\">' + rkrkEsc(gefText) + '</span>'
    + '<span class=\"rkrk-flight-chip ' + fundClass + '\">' + rkrkEsc(fundText) + '</span>'
    + kontrollChip
    + '</div>'
    + '<div class=\"rkrk-flight-summary-line2\">' 
    + '<span class=\"rkrk-flight-summary-feld\">' + rkrkEsc(sub) + '</span>'
    + (jaegerFull ? ('<span class=\"rkrk-flight-summary-jaeger\">' + rkrkEsc(jaegerFull) + '</span>') : '')
    + '</div>'
    + (bemLine ? ('<div class=\"rkrk-flight-summary-line4\">' + rkrkEsc(bemLine) + '</div>') : '');
}
function rkrkSummaryActionsOpenPreferred(){
  try { return localStorage.getItem('rkrkFlightSummaryActionsOpen') === '1'; } catch (e) { return false; }
}
function rkrkSetSummaryActionsOpenPreferred(open){
  try { localStorage.setItem('rkrkFlightSummaryActionsOpen', open ? '1' : '0'); } catch (e) {}
}
function rkrkFillFlightSummaryActions(row, wrap){
  if (!row || !wrap) return;
  var host = wrap.querySelector('.rkrk-flight-summary-actions');
  var toggle = wrap.querySelector('.rkrk-flight-summary-actions-toggle');
  if (!host || !toggle) return;
  host.innerHTML = '';
  var seen = {};
  var count = 0;
  var sources = row.querySelectorAll('td[data-summary-action=\"1\"] a');
  for (var i = 0; i < sources.length; i++) {
    var a = sources[i];
    var href = String(a.getAttribute('href') || '');
    var key = href + '|' + String(a.getAttribute('title') || '');
    if (!href || seen[key]) continue;
    seen[key] = 1;
    var clone = a.cloneNode(true);
    clone.classList.add('rkrk-summary-action-link');
    host.appendChild(clone);
    count++;
  }
  if (count === 0) {
    wrap.classList.add('is-empty');
    wrap.classList.remove('is-open');
    return;
  }
  wrap.classList.remove('is-empty');
  var open = rkrkSummaryActionsOpenPreferred();
  wrap.classList.toggle('is-open', open);
  toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  toggle.textContent = open ? ('Links \u25b4 ' + count) : ('Links \u25be ' + count);
}
function rkrkEsc(s){
  return String(s || '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/\"/g,'&quot;');
}
function rkrkRefreshFlightSummary(line){
  var row = document.querySelector('.rkrk-flight-data-row[data-line=\"' + line + '\"]');
  if (!row) {
    var rows = document.querySelectorAll('.rkrk-flight-data-row');
    for (var i=0;i<rows.length;i++){
      if (rkrkFlightLineFromRow(rows[i]) === String(line)) { row = rows[i]; break; }
    }
  }
  if (!row) return;
  var box = row.querySelector('.rkrk-flight-summary');
  if (!box) return;
  box.innerHTML = rkrkBuildFlightSummaryHtml(line);
  var cell = box.parentNode;
  if (cell) {
    var wrap = cell.querySelector(':scope > .rkrk-flight-summary-actions-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'rkrk-flight-summary-actions-wrap';
      wrap.innerHTML = '<button type=\"button\" class=\"rkrk-flight-summary-actions-toggle\" aria-expanded=\"false\">Links</button><div class=\"rkrk-flight-summary-actions\"></div>';
      cell.appendChild(wrap);
    }
    rkrkFillFlightSummaryActions(row, wrap);
  }
}
function rkrkClassifyFlightCells(row, labels){
  var primaryLabels = {
    'Datum*':1,'Datum':1,'Zeit':1,'Pilot':1,'Feld*':1,'Feld':1,
    'Felder':1,'Geflogen':1,'Fund':1,'Kontrollflug':1,
    'Jaeger':1,'Kontaktnummer':1,'Bemerkung':1
  };
  var tds = row.querySelectorAll('td');
  for (var c=0; c<tds.length; c++){
    var td = tds[c];
    if (td.classList.contains('rkrk-flight-summary-cell')) continue;
    if (td.hasAttribute('colspan')) {
      td.classList.add('rkrk-mobile-full');
      continue;
    }
    td.setAttribute('data-col-index', String(c));
    var label = (labels[c] || td.getAttribute('data-label') || '').trim();
    td.setAttribute('data-label', label);
    if (td.classList.contains('rkrk-col-primary') || td.classList.contains('rkrk-col-secondary') || td.classList.contains('rkrk-col-action')) {
      if (label === '' && td.classList.contains('rkrk-col-secondary')) td.classList.add('rkrk-mobile-iconcell');
      continue;
    }
    var saveImg = td.querySelector('input[type=\"image\"][value=\"SaveLine\"], input[type=\"image\"][src*=\"save_line\"]');
    var delImg = td.querySelector('input[type=\"image\"][src*=\"delete\"]');
    if (saveImg || delImg) {
      td.classList.add('rkrk-col-action');
      continue;
    }
    var baseLabel = label.replace(/\\*$/, '');
    if (primaryLabels[label] || primaryLabels[baseLabel]) {
      td.classList.add('rkrk-col-primary');
    } else {
      td.classList.add('rkrk-col-secondary');
      if (label === '') td.classList.add('rkrk-mobile-iconcell');
    }
  }
}
function rkrkEnsureFlightEditUi(){
  var wrap = document.querySelector('.rkrk-flight-table-wrap');
  var host = (wrap && wrap.closest) ? (wrap.closest('form') || wrap) : document.body;
  if (!host) host = document.body;
  if (!document.querySelector('.rkrk-flight-edit-backdrop')) {
    var bd = document.createElement('div');
    bd.className = 'rkrk-flight-edit-backdrop';
    bd.addEventListener('click', function(){ rkrkCloseFlightEdit(); });
    host.appendChild(bd);
  }
  if (!document.querySelector('.rkrk-flight-edit-sheet')) {
    var sheet = document.createElement('div');
    sheet.className = 'rkrk-flight-edit-sheet';
    sheet.innerHTML = ''
      + '<div class=\"rkrk-flight-edit-toolbar\">'
      + '<button type=\"button\" class=\"rkrk-flight-edit-close\">Schliessen</button>'
      + '<button type=\"button\" class=\"rkrk-flight-edit-save\">Speichern</button>'
      + '</div>'
      + '<div class=\"rkrk-flight-edit-primary\"></div>'
      + '<details class=\"rkrk-flight-edit-more\" open>'
      + '<summary>Weitere Angaben</summary>'
      + '<div class=\"rkrk-flight-edit-secondary\"></div>'
      + '</details>';
    sheet.querySelector('.rkrk-flight-edit-close').addEventListener('click', function(){ rkrkCloseFlightEdit(); });
    sheet.querySelector('.rkrk-flight-edit-save').addEventListener('click', function(){ rkrkSaveFlightEdit(); });
    host.appendChild(sheet);
  }
}
function rkrkRestoreFlightRowCells(row){
  if (!row || !row._rkrkCellHome) return;
  var cells = row._rkrkCellHome.slice().sort(function(a,b){
    return (parseInt(a.getAttribute('data-col-index'),10)||0) - (parseInt(b.getAttribute('data-col-index'),10)||0);
  });
  var summary = row.querySelector('.rkrk-flight-summary-cell');
  for (var i=0;i<cells.length;i++){
    if (summary && summary.parentNode === row) row.appendChild(cells[i]);
    else row.appendChild(cells[i]);
  }
  // keep summary first
  if (summary) row.insertBefore(summary, row.firstChild);
  row._rkrkCellHome = null;
}
function rkrkCloseFlightEdit(){
  var row = document.querySelector('.rkrk-flight-data-row.rkrk-row-editing');
  var sheet = document.querySelector('.rkrk-flight-edit-sheet');
  var bd = document.querySelector('.rkrk-flight-edit-backdrop');
  if (row) {
    rkrkRestoreFlightRowCells(row);
    row.classList.remove('rkrk-row-editing');
    var line = row.getAttribute('data-line') || rkrkFlightLineFromRow(row);
    if (line) rkrkRefreshFlightSummary(line);
  }
  if (sheet) sheet.classList.remove('rkrk-open');
  if (bd) bd.classList.remove('rkrk-open');
  document.body.classList.remove('rkrk-flight-editing');
}
function rkrkSaveFlightEdit(){
  var row = document.querySelector('.rkrk-flight-data-row.rkrk-row-editing');
  if (!row) return;
  var saveBtn = null;
  var sheet = document.querySelector('.rkrk-flight-edit-sheet');
  if (sheet) saveBtn = sheet.querySelector('input[type=\"image\"][value=\"SaveLine\"], input[type=\"image\"][src*=\"save_line\"]');
  if (!saveBtn) saveBtn = row.querySelector('input[type=\"image\"][value=\"SaveLine\"], input[type=\"image\"][src*=\"save_line\"]');
  if (saveBtn) {
    saveBtn.click();
    return;
  }
  rkrkCloseFlightEdit();
}
function rkrkOpenFlightEdit(row){
  if (!row || !window.matchMedia('(max-width: 1300px)').matches) return;
  rkrkEnsureFlightEditUi();
  if (row.classList.contains('rkrk-row-editing')) return;
  rkrkCloseFlightEdit();
  var sheet = document.querySelector('.rkrk-flight-edit-sheet');
  var bd = document.querySelector('.rkrk-flight-edit-backdrop');
  var primary = sheet.querySelector('.rkrk-flight-edit-primary');
  var secondary = sheet.querySelector('.rkrk-flight-edit-secondary');
  primary.innerHTML = '';
  secondary.innerHTML = '';
  var cells = [];
  var tds = row.querySelectorAll('td');
  for (var i=0;i<tds.length;i++){
    if (tds[i].classList.contains('rkrk-flight-summary-cell')) continue;
    cells.push(tds[i]);
  }
  row._rkrkCellHome = cells.slice();
  for (var p=0;p<cells.length;p++){
    var td = cells[p];
    if (td.classList.contains('rkrk-col-primary') || td.classList.contains('rkrk-col-action')) primary.appendChild(td);
    else secondary.appendChild(td);
  }
  row.classList.add('rkrk-row-editing');
  sheet.classList.add('rkrk-open');
  bd.classList.add('rkrk-open');
  document.body.classList.add('rkrk-flight-editing');
}
function rkrkApplyMobileFlightLabels(){
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  var rows = table.querySelectorAll('tr');
  var headerRow = null;
  for (var i=0; i<rows.length; i++){
    if (rows[i].querySelectorAll('th').length > 0){
      headerRow = rows[i];
      break;
    }
  }
  if (!headerRow) return;
  headerRow.classList.add('rkrk-mobile-header-row');
  var labels = [];
  var ths = headerRow.querySelectorAll('th');
  for (var h=0; h<ths.length; h++){
    labels.push((ths[h].textContent || '').trim());
  }
  for (var r=0; r<rows.length; r++){
    if (rows[r] === headerRow) continue;
    if (rows[r].querySelectorAll('th').length > 0) continue;
    var tds = rows[r].querySelectorAll('td');
    var hasColspan = false;
    for (var c=0; c<tds.length; c++){
      var td = tds[c];
      if (td.hasAttribute('colspan')) {
        td.classList.add('rkrk-mobile-full');
        td.setAttribute('data-label', '');
        hasColspan = true;
        continue;
      }
      if (td.classList.contains('rkrk-flight-summary-cell')) continue;
      var label = (labels[c] || '').trim();
      td.setAttribute('data-label', label);
    }
    if (hasColspan) {
      if (!rows[r].classList.contains('rkrk-flight-sep-row') && !rows[r].classList.contains('rkrk-flight-label-row')) {
        rows[r].classList.add('rkrk-flight-label-row');
      }
      continue;
    }
    if (!rows[r].classList.contains('rkrk-flight-data-row')) {
      if (rows[r].querySelector('[id^=\"Datum-\"], [id^=\"Feld-\"]')) {
        rows[r].classList.add('rkrk-flight-data-row');
      }
    }
    if (rows[r].classList.contains('rkrk-flight-data-row')) {
      rkrkClassifyFlightCells(rows[r], labels);
      var line = rows[r].getAttribute('data-line') || rkrkFlightLineFromRow(rows[r]);
      if (line) {
        rows[r].setAttribute('data-line', line);
        rkrkRefreshFlightSummary(line);
      }
    }
  }
}
function rkrkInitCompactFlightList(){
  rkrkEnsureFlightEditUi();
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  document.addEventListener('change', function(ev){
    var t = ev.target;
    if (!t || !t.id) return;
    var m = String(t.id).match(/^(Datum|Zeit|Pilot|Feld|Felder|Geflogen|Fund|Kontrollflug|Jaeger|Kontaktnummer|Bemerkung)-(\\d+)$/);
    if (!m) return;
    rkrkRefreshFlightSummary(m[2]);
  });
  document.addEventListener('input', function(ev){
    var t = ev.target;
    if (!t || !t.id) return;
    var m = String(t.id).match(/^(Zeit|Felder|Fund|Kontaktnummer|Bemerkung)-(\\d+)$/);
    if (!m) return;
    rkrkRefreshFlightSummary(m[2]);
  });
  table.addEventListener('click', function(ev){
    if (!window.matchMedia('(max-width: 1300px)').matches) return;
    var toggle = ev.target.closest ? ev.target.closest('.rkrk-flight-summary-actions-toggle') : null;
    if (toggle) {
      ev.preventDefault();
      ev.stopPropagation();
      var wrap = toggle.closest('.rkrk-flight-summary-actions-wrap');
      if (!wrap) return;
      var open = !wrap.classList.contains('is-open');
      wrap.classList.toggle('is-open', open);
      rkrkSetSummaryActionsOpenPreferred(open);
      var n = wrap.querySelectorAll('.rkrk-flight-summary-actions a').length;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.textContent = open ? ('Links \u25b4 ' + n) : ('Links \u25be ' + n);
      var wraps = document.querySelectorAll('.rkrk-flight-summary-actions-wrap:not(.is-empty)');
      for (var w = 0; w < wraps.length; w++) {
        var ww = wraps[w];
        if (ww === wrap) continue;
        ww.classList.toggle('is-open', open);
        var tt = ww.querySelector('.rkrk-flight-summary-actions-toggle');
        var nn = ww.querySelectorAll('.rkrk-flight-summary-actions a').length;
        if (tt) {
          tt.setAttribute('aria-expanded', open ? 'true' : 'false');
          tt.textContent = open ? ('Links \u25b4 ' + nn) : ('Links \u25be ' + nn);
        }
      }
      return;
    }
    if (ev.target.closest && ev.target.closest('.rkrk-summary-action-link, .rkrk-flight-summary-actions a')) {
      ev.stopPropagation();
      return;
    }
    var summary = ev.target.closest ? ev.target.closest('.rkrk-flight-summary') : null;
    if (!summary) return;
    ev.preventDefault();
    var row = summary.closest('.rkrk-flight-data-row');
    if (row) rkrkOpenFlightEdit(row);
  });
  // Auto-open first empty new row on narrow screens
  if (window.matchMedia('(max-width: 1300px)').matches) {
    var neu = table.querySelector('.rkrk-flight-data-row[data-new=\"1\"]');
    if (neu) rkrkOpenFlightEdit(neu);
  }
}
function rkrkApplyDesktopTableScroll(){
  function stretchWrapperToViewport(wrap){
    if (!wrap) return;
    var rect = wrap.getBoundingClientRect();
    var left = Math.max(0, Math.round(rect.left || 0));
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var width = Math.max(120, vw - left - 6);
    wrap.style.width = width + 'px';
    wrap.style.maxWidth = width + 'px';
    wrap.style.marginRight = '0';
  }
  function availableWidthFor(el){
    var parent = el && el.parentNode ? el.parentNode.clientWidth : 0;
    var rect = el && el.getBoundingClientRect ? el.getBoundingClientRect() : null;
    var left = rect ? Math.max(0, Math.round(rect.left || 0)) : 0;
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportRightSpace = Math.max(0, vw - left - 6);
    return Math.max(parent, viewportRightSpace);
  }
  var isNarrow = window.matchMedia('(max-width: 1300px)').matches;
  var wrappers = document.querySelectorAll('.rkrk-desktop-scroll');
  for (var w = 0; w < wrappers.length; w++){
    var wrap = wrappers[w];
    stretchWrapperToViewport(wrap);
    var wrappedTable = wrap.querySelector(':scope > table');
    if (!wrappedTable) continue;
    if (isNarrow) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
      continue;
    }
    var wrapAvailable = wrap.clientWidth;
    if (wrappedTable.scrollWidth <= wrapAvailable + 1){
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
    }
  }
  if (isNarrow) return;

  var tables = document.querySelectorAll('table');
  for (var i = 0; i < tables.length; i++){
    var table = tables[i];
    if (!table || table.closest('.rkrk-desktop-scroll')) continue;
    var rowCount = table.querySelectorAll('tr').length;
    if (rowCount < 2) continue;
    if (table.scrollWidth <= availableWidthFor(table) + 1) continue;
    var wrap = document.createElement('div');
    wrap.className = 'rkrk-desktop-scroll';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
    stretchWrapperToViewport(wrap);
  }
}
function rkrkMarkSaveSticky(){
  var wrap = document.querySelector('.rkrk-flight-table-wrap');
  if (!wrap) return;
  var form = wrap.closest('form') || wrap.parentNode;
  if (!form) return;
  var node = form.firstChild;
  // Prefer wrapping the PageControl save area just before the table
  var prev = wrap.previousSibling;
  while (prev && prev.nodeType === 3) prev = prev.previousSibling;
  // Create sticky wrapper around preceding BR/font/pagecontrol content is fragile;
  // instead mark the form's save submit vicinity:
  var submits = form.querySelectorAll('input[type=\"submit\"], button[type=\"submit\"]');
  for (var i=0;i<submits.length;i++){
    var s = submits[i];
    if ((s.value || s.textContent || '').indexOf('speichern') === -1 &&
        (s.value || s.textContent || '').indexOf('Speichern') === -1) continue;
    var holder = s.parentNode;
    if (holder && !holder.classList.contains('rkrk-flight-save-sticky')) {
      // climb one level if nested
      var box = document.createElement('div');
      box.className = 'rkrk-flight-save-sticky';
      holder.parentNode.insertBefore(box, holder);
      box.appendChild(holder);
    }
  }
}
window.addEventListener('DOMContentLoaded', function(){
  rkrkApplyMobileFlightLabels();
  rkrkInitCompactFlightList();
  rkrkMarkSaveSticky();
  rkrkApplyDesktopTableScroll();
  rkrkInitFilesCollapse();
  rkrkInitMetaCollapse();
});
window.addEventListener('resize', function(){
  if (!window.matchMedia('(max-width: 1300px)').matches) rkrkCloseFlightEdit();
  rkrkApplyDesktopTableScroll();
});
function rkrkSetFilesCollapsed(collapsed){
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  table.classList.toggle('rkrk-files-collapsed', !!collapsed);
  try { localStorage.setItem('rkrk-fluege-files-collapsed', collapsed ? '1' : '0'); } catch (e) {}
  var btns = table.querySelectorAll('.rkrk-files-toggle');
  for (var bi = 0; bi < btns.length; bi++) {
    btns[bi].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }
}
function rkrkInitFilesCollapse(){
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  var stored = null;
  try { stored = localStorage.getItem('rkrk-fluege-files-collapsed'); } catch (e) {}
  rkrkSetFilesCollapsed(stored === null ? true : stored === '1');
  table.addEventListener('click', function(ev){
    var t = ev.target;
    if (t && t.classList && t.classList.contains('rkrk-files-toggle')) {
      ev.preventDefault();
      rkrkSetFilesCollapsed(!table.classList.contains('rkrk-files-collapsed'));
    }
    if (t && t.classList && t.classList.contains('rkrk-meta-toggle')) {
      ev.preventDefault();
      rkrkSetMetaCollapsed(!table.classList.contains('rkrk-meta-collapsed'));
    }
  });
}
function rkrkSetMetaCollapsed(collapsed){
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  table.classList.toggle('rkrk-meta-collapsed', !!collapsed);
  try { localStorage.setItem('rkrk-fluege-meta-collapsed', collapsed ? '1' : '0'); } catch (e) {}
  var btns = table.querySelectorAll('.rkrk-meta-toggle');
  for (var bi = 0; bi < btns.length; bi++) {
    btns[bi].setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }
}
function rkrkInitMetaCollapse(){
  var table = document.querySelector('.rkrk-flight-main');
  if (!table) return;
  var stored = null;
  try { stored = localStorage.getItem('rkrk-fluege-meta-collapsed'); } catch (e) {}
  rkrkSetMetaCollapsed(stored === null ? true : stored === '1');
}
</script>";

	SetMissionLocation();
	if (function_exists('rkrk_apply_m3t_catalog_paths')) {
		rkrk_apply_m3t_catalog_paths();
	}
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
	$FluegeFileSettings = rkrk_fluege_mission_file_settings();
	//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	//pre_r($Feldkatalog);

	//Read CSV file
	$csv_file= $RKRKContactFile;

    $csv_desc = ';';
    $excel_csv_file = "RKRK Fluege (".($_SESSION[$SessNme_RegisteredInitialen] ?? '').").csv";
    $excel_csv_path = $historyfilelocation . $excel_csv_file;
	
	transferGoogleSheetToCSV($csvDocLocation . $csv_file);
	$Adresskatalog = csv_to_array($csvDocLocation.$csv_file,';');
	if (!is_array($Adresskatalog)) {
		$Adresskatalog = array();
	}


	foreach ($Adresskatalog as $Person) {
		$AdressDataArray[$Person['Initialen']]['Initialen']= $Person['Initialen'];
		$AdressDataArray[$Person['Initialen']]['Text']= $Person['JG'] . " " . $Person['Name'] . " " . $Person['Kontaktnummer'];
	}
	//pre_R($AdressDataArray);
	//pre_R($Adresskatalog);

	foreach ($RegionSettings as $Reg) {
		$RegDataArray[$Reg['Reg']]['Reg']= $Reg['Reg'];
		$RegDataArray[$Reg['Reg']]['RegUp']= $Reg['RegUp'];
		foreach ($Adresskatalog as $Person) {
			if ((strpos(" " . $Person['Jagdgebiet'], strval($Reg['Reg'])) !== false) and
				(strpos(" " . $Person['Jagdgebiet'], "(". strval($Reg['Reg']) .")" ) == false)) {
				$PilotDataArray[$Reg['Reg']]['Reg']=$Reg['Reg'];
				$PilotDataArray[$Reg['Reg']]['Pilot']=$Person['Initialen'];
			}
		}
	}
	//pre_R($RegDataArray);
	//pre_R($PilotDataArray);
	
	$SelectPilots = rkrk_pilot_select_codes('filter', null, '', true);
	$VisblePilots = array();
	foreach ($OptionPilot as $key => $Pilot) {
		if ((strpos(" " . ($Pilot["VisibleOn"] ?? ''), $WebFreigabe) != false)) {
			$VisblePilots[$key] = $Pilot;
		}
	}

	$Pilots = rkrk_pilot_select_codes('assign', date('Y-m-d'), '', false);
	$OptControlFlight = rkrk_einsatz_yn_options();

    //pre_R($Pilots);

	
	//pre_R(array_column($VisblePilots,"Opt"));
	// ===========================================
	// Gobal Defintions
	// ===========================================
	

		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
		$debugmode = false;
		$AllVisible=array("test","intern","partner");
				
	// ===========================================
	// Function
	// ===========================================
 
	if ($debugmode) {
		$debugfieldshow = "";
		$debuglabelshow = "";
		$manuallabelshow = "";
	} else {
		$debugfieldshow = "hidden";
		$debuglabelshow = "hidden";
		$manuallabelshow = "hidden";
	}


	function rkrk_fluege_mission_file_settings() {
		global $FlyFileSetting, $MissionDroneTypes, $RKRKMissionSelected;
		$missionDataFiles = array_map('trim', explode(',', (string)($MissionDroneTypes[$RKRKMissionSelected]['DataFile'] ?? '')));
		$settings = array();
		foreach ($FlyFileSetting as $key => $fileType) {
			if (!empty($fileType['Picture'])) {
				continue;
			}
			$showOn = array_map('trim', explode(',', (string)($fileType['Show'] ?? '')));
			if (!in_array('fluege', $showOn, true)) {
				continue;
			}
			$xmlLabel = trim((string)($fileType['XMLLabel'] ?? ''));
			if ($xmlLabel === '' || !in_array($xmlLabel, $missionDataFiles, true)) {
				continue;
			}
			$settings[$key] = $fileType;
		}
		return $settings;
	}

	function rkrk_fluege_field_file_links($feldkatalog, $feldId, $xmlLabel) {
		$feldId = trim((string)$feldId);
		$xmlLabel = trim((string)$xmlLabel);
		if ($feldId === '' || $xmlLabel === '' || strpos($feldId, '-') === false) {
			return array();
		}
		$jg = explode('-', $feldId, 2)[0];
		if (!isset($feldkatalog[$jg][$feldId]) || !is_array($feldkatalog[$jg][$feldId])) {
			return array();
		}
		$links = array();
		foreach (explode(',', (string)($feldkatalog[$jg][$feldId][$xmlLabel] ?? '')) as $link) {
			$link = trim((string)$link);
			if ($link !== '') {
				$links[] = $link;
			}
		}
		return $links;
	}


	function GetNewLine() {
		global $MaxEntry, $EnableDelete, $debugmode, $debugfieldshow, $ActDate, $MinDate, $MaxDate, $PrevPilot ,$Pilots,$FeldListe,$OptControlFlight,$OptionJaeger, $ButtonHeight ,$IconLoc, $i, $FromDate,$ToDate, $MissionSelectionParameter, $EnableDeleteSet, $EnableNeuSet, $EnableConSet, $EnableCon, $MissionDroneTypes, $RKRKMissionSelected;
		while ($i < $MaxEntry) {
			if (true) {
			// Configsform empty
			// Existing Data
				echo '<tr class="rkrk-flight-data-row" data-line="' . $i . '" data-new="1">' .chr(10);
				echo '<td class="rkrk-flight-summary-cell"><button type="button" class="rkrk-flight-summary" aria-label="Flug bearbeiten"></button></td>' .chr(10);
				if ($EnableDelete) {
					echo "<td class='rkrk-col-action'></td>" .chr(10);
				}
				if ($debugmode) {
					echo '<td class="rkrk-col-secondary">' .chr(10);
					echo $i;
					echo '</td>' .chr(10);
				}
				echo '<td class="rkrk-col-action">' .chr(10);
				//SAVE
				echo "<input type='image' value='SaveLine' src='" . $IconLoc . "/save_line.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" .$EnableDeleteSet . "&" . $EnableNeuSet . "&" . $EnableConSet ."&SaveLine=".$i."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Zeile speichern'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo DateJSFunctionField("Datum-" . $i, $ActDate, $MinDate , $MaxDate ,"UpdDatum", "this.value", $i, 4 );
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo InputTimeField("Zeit-" . $i, "", 3 , "right");
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//Flugwarnung Nachtflug-/Sperrzonen
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo SelectJSFunctionField("Pilot-" . $i, $PrevPilot , rkrk_pilot_select_codes('assign', $ActDate, $PrevPilot, false) , "UpdPilot", "this.value", $i );
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo SelectJSFunctionField("Feld-" . $i, "" ,$FeldListe , "UpdField", "this.value", $i );
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo SelectField("Kontrollflug-" . $i, "" ,$OptControlFlight );
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-files-toggle"></td>' .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-files">' .chr(10);
				// geomap
				echo "</td>" .chr(10);
				foreach (rkrk_fluege_mission_file_settings() as $fileType) {
					echo '<td class="rkrk-col-secondary rkrk-col-files"></td>' .chr(10);
				}
				if (GetAccess("Convert") and $EnableCon) {
					foreach (explode(",", (string)($MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"] ?? "")) as $TargetMission) {
						if ($TargetMission !== "") {
							echo '<td class="rkrk-col-secondary rkrk-col-files"></td>' .chr(10);
							echo '<td class="rkrk-col-secondary rkrk-col-files"></td>' .chr(10);
						}
					}
				}
				echo '<td class="rkrk-col-secondary rkrk-col-meta-toggle"></td>' .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
				echo InputField("Flugzeit-" . $i, "", 2, "right", "", "", "readonly='readonly'");
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
				//$dataline['Flaeche'];
				echo InputField("Flaeche-" . $i, "", 3, "right", "", "", "readonly='readonly'", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
				//$dataline['JG'];
				echo InputJSFunctionField("JG-" . $i, "", "UpdReg", "this.value",  $i, 2, "right", "", "", "readonly='readonly'", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
				//$dataline['Gde'];
				echo InputField("Gde-" . $i, "", 5, "left", "", "", "readonly='readonly'", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['GrundstückID'];
				echo InputField("GrundstückID-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo SelectField("Geflogen-" . $i, "" ,$OptControlFlight );
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo InputJSFunctionField("Fund-" . $i, "", "UpdFund", "this.value", $i, 4 );
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo InputField("UAV#-" . $i, "", 7);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-primary">' .chr(10);
				echo InputField("Felder-" . $i, "" , 30);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo SelectJSFunctionField("Jaeger-" . $i, "" ,$OptionJaeger , "UpdJaeger", "this.value" , $i);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo InputField("Kontaktnummer-" . $i, "", 28);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo InputField("Landwirt-" . $i, "", 28);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo InputField("Bemerkung-" . $i, "", 30);
				echo "</td>" .chr(10);
				//DEBUG
				//------------------------
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['AnzFelder'];
				echo InputField("AnzFelder-" . $i, "", 2, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['#Funde'];
				echo InputField("#Funde-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['Einsatztag'];
				echo InputField("Einsatztag-" . $i, "", 6, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['Warnung'];
				echo InputField("Warnung-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['Reg'];
				echo InputField("Reg-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['FlugID'];
				echo InputField("FlugID-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-secondary">' .chr(10);
				//$dataline['Status'];
				echo InputField("Status-" . $i, "locked", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
	}


	// ===========================================
	// Paramters
	// ===========================================

if (isset($_GET["EnableDelete"]) && strtolower((string)$_GET["EnableDelete"])=="on") {
	$EnableDelete= true;
} else {
	$EnableDelete= false;
}

IF ($EnableDelete) {
	$EnableDeleteSet = "EnableDelete=on";
} else {
	$EnableDeleteSet = "EnableDelete=off";
}

if (isset($_GET["DoSafe"])) { 
	if ($_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}
} else {
	$dosafe= false;
}

if (isset($_GET["EnableNeu"]) && $_GET["EnableNeu"]=="on") {
	$EnableNeu= true;
} else {
	$EnableNeu= false;
}
if (isset($_GET["EnableCon"]) && $_GET["EnableCon"]=="on") {
	$EnableCon= true;
} else {
	$EnableCon= false;
}

IF ($EnableCon) {
	$EnableConSet = "EnableCon=on";
} else {
	$EnableConSet = "EnableCon=off";
}

IF ($EnableNeu) {
	$EnableNeuSet = "EnableNeu=on";
} else {
	$EnableNeuSet = "EnableNeu=off";
}

if (isset($_GET["Modus"]) && $_GET["Modus"]=="on") {
	$Modus= true;
} else {
	$Modus= false;
}

IF ($Modus) {
	$ModusSet = "Modus=on";
} else {
	$ModusSet = "Modus=off";
}


if (isset($_GET["delete"]) && isset($_GET["DelFlugID"]) ) { 
	if ($_GET["delete"]=="Y") {
		$delFlugID = $_GET["DelFlugID"];
		$dodelete= true;
		$dosafe = true;
	} else {
		$dodelete= false;
		$delFlugID = "";
	}
} else {
	$dodelete= false;
	$delFlugID = "";
}

if (isset($_GET["SelFrom"])) { 
	$FromDate = $_GET["SelFrom"];
} 
elseif (isset($_POST["SelFrom"])) { 
	$FromDate = $_POST["SelFrom"];
} else {
	$FromDate =date('Y-m-d');
	//For Debugging
	//$FromDate ="2023-05-03";
}

if (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
} 
elseif (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} else {
	$ToDate =date('Y-m-d', strtotime('+1 week'));
	//For Debugging
	//$ToDate ="2023-05-10";
}

if (isset($_GET["add"]) && isset($_GET["AddFlugID"]) ) { 
	if ($_GET["add"]=="Y") {
		$AddFlugIDs = $_GET["AddFlugID"];
		$AddFlugToDate = $_GET["AddFlugToDate"];
		$FromDate = $AddFlugToDate;
		$ToDate = $AddFlugToDate;
		$doadd= true;
		$dosafe = true;
	} else {
		$doadd= false;
		$AddFlugIDs = "";
		$AddFlugToDate ="";
	}
} else {
	$doadd= false;
	$AddFlugIDs = "";
}

if (isset($_GET["SaveLine"])) { 
	$SaveLine = $_GET["SaveLine"];
	$dosafe = true;
} else {
	$SaveLine = "";
}


if (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

if (isset($_POST["PilotenWahl"])) {
	$PilotenWahl = $_POST["PilotenWahl"];
} elseif (isset($_GET["Pilot"])) {
	$PilotenWahl = $_GET["Pilot"];
} else	{
	$PilotenWahl = '';
}


if (isset($_GET["Free"])) {
	$FreeFlights= str_replace("+"," ",$_GET["Free"]);
} else {
	$FreeFlights="";
}

if (isset($_GET["Lock"])) {
	$LockFlights= str_replace("+"," ",$_GET["Lock"]);
} else {
	$LockFlights="";
}

//Convert to other Drone
if (isset($_GET["ConFile"])) {
	$ConFile= $_GET["ConFile"];
} else {
	$ConFile="";
}

if (isset($_GET["ConTo"])) {
	$ConTo= $_GET["ConTo"];
} else {
	$ConTo="";
}


//echo "Free: " . $FreeFlights . "<br>";
//echo "Lock: " . $LockFlights . "<br>";



$OptionJaeger= Array();
// Update Flightdata
$Year = date('Y', strtotime($FromDate));
UpdateFlightsAndSaveKatalog(strval($Year));

///////////////////////////////////////
// Load file
///////////////////////////////////////


	$csv_file= date('Y', strtotime($FromDate)) .$RKRKFlightsCSV;
	$csv_deliminator = ";";

	//Read CSV file
	$data = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	if (!is_array($data)) {
		$data = array();
	}
	$MaxFlugID = 0;
	$data = FormatFlugDataArray($data);
	// change key to FlugID
//	echo "MaxID: " . $MaxFlugID . "<br>"; 
	//pre_r($data);

// ========================================
// Date for JS array update
// ========================================

// Felddaten aus Mission-XML (Feldkatalog), Index = FeldID
$FieldDataArray = array();
foreach ($Feldkatalog as $JGFields) {
	foreach ($JGFields as $key => $Field) {
		$fid = trim((string)($Field["FeldID"] ?? $key));
		if ($fid === '') {
			continue;
		}
		$FieldDataArray[$fid] = array(
			"FeldID" => $fid,
			"Text" => rkrk_fluege_build_field_text($Field),
			"Flugzeit" => rkrk_fluege_scalar_text($Field["Flugzeit"] ?? ''),
			"Flaeche" => rkrk_fluege_scalar_text($Field["Flaeche"] ?? ''),
			"GrundstueckID" => rkrk_fluege_scalar_text($Field["GrundstueckID"] ?? ''),
			"JG" => rkrk_fluege_scalar_text($Field["JG"] ?? ''),
			"Gemeinde" => rkrk_fluege_scalar_text($Field["Gemeinde"] ?? ''),
			"Jaeger" => rkrk_fluege_scalar_text($Field["Jaeger"] ?? ''),
			"Warnung" => rkrk_fluege_scalar_text($Field["Warnung"] ?? ''),
			"Einsatztag" => rkrk_fluege_scalar_text($Field["Einsatztag"] ?? ''),
			"Bauer" => rkrk_fluege_build_bauer_text($Field["Bauer"] ?? ''),
		);
	}
}
rkrk_fluege_repair_all_flight_data($data, $FieldDataArray);
//pre_R($FieldDataArray);
//Adressdaten


///////////////////////////////////////
// Process Input Data
///////////////////////////////////////

/// GET
	if (($doadd) and ($AddFlugIDs)) {
		$dosafe= true;
		//behandeln von Fluglisten
		$AddFlugIDList = explode(" " ,$AddFlugIDs);
		Foreach ($AddFlugIDList as $AddFlugID) {
			rkrk_einsatz_append_catalog_flight($data, $AddFlugID, $AddFlugToDate);
		}
	}

/// POST
	//echo "SaveLine: " . $SaveLine . "<br>";
	$hasPostFlightRows = false;
	$postedFlightIndexMax = 0;
	if (($_SERVER["REQUEST_METHOD"] ?? "") == "POST") {
		foreach (array_keys($_POST) as $postKey) {
			if (preg_match('/^(?:FlugID|Feld|UAV#|Bemerkung)-(\d+)$/', (string)$postKey, $postIndexMatch)) {
				$hasPostFlightRows = true;
				$postedIndex = (int)$postIndexMatch[1];
				if ($postedIndex > $postedFlightIndexMax) {
					$postedFlightIndexMax = $postedIndex;
				}
			}
		}
	}
	if ($dodelete && !$hasPostFlightRows) {
		$dodelete = false;
		$dosafe = false;
	}
	if (
		($_SERVER["REQUEST_METHOD"] == "POST")
		 and $dosafe
		 and $hasPostFlightRows
		 and isset($_POST["NumEntries"])) {
					
		//echo "MaxFlugID: " . $MaxFlugID . "<br>";
		$numEntries = (int)$_POST["NumEntries"];
		if ($postedFlightIndexMax > $numEntries) {
			$numEntries = $postedFlightIndexMax;
		}
		for ($i = 1; $i <= $numEntries; $i++) {
		//echo $i . " " . $_POST["Initialen-" . $i] . " -  " . $_POST["FlugID-" . $i] . "<br>";
		//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
			$postFlugKey = "FlugID-" . $i;
			$postFeldKey = "Feld-" . $i;
			$postUavKey = "UAV#-" . $i;
			$postBemKey = "Bemerkung-" . $i;
			if (
				!isset($_POST[$postFlugKey])
				&& !isset($_POST[$postFeldKey])
				&& !isset($_POST[$postUavKey])
				&& !isset($_POST[$postBemKey])
			) {
				// Zeilen ausserhalb des Datumsfensters/der Seite fehlen im Formular.
				// Luecke ueberspringen, sonst werden alle folgenden Aenderungen verworfen.
				continue;
			}
			$postFlugId = $_POST[$postFlugKey] ?? "";
			$postFeld = $_POST[$postFeldKey] ?? "";
			$postUav = $_POST[$postUavKey] ?? "";
			$postBem = $_POST[$postBemKey] ?? "";
			if (
				($postFlugId != "") &&
				($postFeld != "")
				) {
				$FlugID = $postFlugId;
			} else {
				$MaxFlugID++;
				$FlugID = "FID".sprintf("%06d", $MaxFlugID);
				//echo "NewFlugID: " . $FlugID . "<br>";
			}
			//echo "FlugID: " . $FlugID . " Saveline " . $SaveLine." line " . $i."<br>";
			if (
				 (
				   ($postFeld <> "") or 
			       ($postUav <> "") or 
				   ($postBem <> "")
				  ) and (
				   ($SaveLine=="")
				   or (strpos(" ".$SaveLine, "FID") and ($SaveLine==$FlugID))
				   or ((strval($SaveLine)==strval($i)))
				  )
				) { 
				
				//echo "Speichern FlugID: " . $FlugID . "Zeit: " . $_POST["Zeit-" . $i] ."<br>";
				$dosafe=true;
				$postedFlightValue = function ($suffix) use ($i) {
					$key = $suffix . "-" . $i;
					return array_key_exists($key, $_POST) ? $_POST[$key] : null;
				};
				$data[$FlugID]["FlugID"] = $FlugID;
				if ($postedFlightValue("Datum") !== null) {
					$data[$FlugID]["Datum"] = $postedFlightValue("Datum");
				}
				if ($postedFlightValue("Zeit") !== null) {
					$data[$FlugID]["Zeit"] = $postedFlightValue("Zeit");
				}
				if ($postedFlightValue("Pilot") !== null) {
					$data[$FlugID]["Pilot"] = $postedFlightValue("Pilot");
				}
				$data[$FlugID]["Feld"] = str_replace($searchSD, $replaceSD, $postFeld);
				rkrk_einsatz_apply_result_fields(
					$data[$FlugID],
					$postedFlightValue("Geflogen") ?? "",
					$postedFlightValue("Kontrollflug") ?? "",
					$postedFlightValue("Fund") ?? "",
					$postBem
				);
				$data[$FlugID]["UAV#"] = $postUav;
				if ($postedFlightValue("Felder") !== null) {
					$data[$FlugID]["Felder"] = rkrk_fluege_clean_felder_text($postedFlightValue("Felder"));
				}
				if ($postedFlightValue("Jaeger") !== null) {
					$data[$FlugID]["Jaeger"] = $postedFlightValue("Jaeger");
				} elseif (!isset($data[$FlugID]["Jaeger"])) {
					$data[$FlugID]["Jaeger"] = "";
				}
				if ($postedFlightValue("Kontaktnummer") !== null) {
					$data[$FlugID]["Kontaktnummer"] = cleanString(str_replace($searchSD, $replaceSD, $postedFlightValue("Kontaktnummer")));
				}
				if ($postedFlightValue("Landwirt") !== null) {
					$data[$FlugID]["Landwirt"] = rkrk_fluege_clean_landwirt_text(cleanString(str_replace($searchSD, $replaceSD, $postedFlightValue("Landwirt"))));
				}
				if ($postedFlightValue("Flugzeit") !== null && is_numeric($postedFlightValue("Flugzeit"))) {
					$data[$FlugID]["Flugzeit"] = round($postedFlightValue("Flugzeit"), 1);
				}
				foreach (array("AnzFelder", "#Funde", "Einsatztag", "GrundstückID", "JG", "Warnung", "Reg", "Flaeche", "Status") as $flightColumn) {
					if ($postedFlightValue($flightColumn) !== null) {
						$data[$FlugID][$flightColumn] = $postedFlightValue($flightColumn);
					}
				}
				if ($postedFlightValue("Gde") !== null) {
					$data[$FlugID]["Gde"] = rkrk_fluege_scalar_text($postedFlightValue("Gde"));
				}
				//echo "FlugID: ". $FlugID . "<br>";
				//Write to LOGfile
				IF ($doadd) {
					WriteToLog("add", basename($_SERVER['PHP_SELF']), "Flug hinzgefügt", $FlugID ." - " .  $data[$FlugID]["Feld"], $RKRKMissionSelected,"");
				}	
			}
		}
		IF ($dodelete) {
			// delete marked line
			WriteToLog("del", basename($_SERVER['PHP_SELF']), "Flug gelöscht", $delFlugID ." - " .  $data[$delFlugID]["Feld"], $RKRKMissionSelected,"");

			unset ($data[$delFlugID]);
		}

	}
	
	rkrk_fluege_repair_all_flight_data($data, $FieldDataArray);

	// check and delete entries without Feld
	foreach ($data as $key => $dl) {
		if ($dl["Feld"]=="") {
			echo "delete: " . $dl["FlugID"] . "<br>";
			unset($data[$key]);
		}	
	}
	
////////////////////////////////////////
// Change Status
////////////////////////////////////////


if ($FreeFlights!="") {
	foreach(explode(" ",$FreeFlights) as $Flight) {
		//echo "Free Flight: " . $Flight . "<br>";
		$data[$Flight]["Status"] = "";
		$dosafe= true;
	}
}

if ($LockFlights!="") {
	foreach(explode(" ",$LockFlights) as $Flight) {
		//echo "Lock Flight: " . $Flight . "<br>";
		$data[$Flight]["Status"] = "locked";
		$dosafe= true;
	}
}

///////////////////////////////////////
// Conversion to other Drone
///////////////////////////////////////
if (($ConFile!="") and ($ConTo!="")) {
	//echo $ConFile . " transfered from " . $RKRKMissionSelected . " to " . $ConTo . "<br>";
	$ext = pathinfo($ConFile, PATHINFO_EXTENSION);
	if ($ext=="json") {
		//echo "JSON Konvertieren: " . $ConFile . "<br>";
		CreateJSONforNewDrone($ConFile, $RKRKMissionSelected, $ConTo);
	} else {
		//echo "KMZ Konvertieren: " . $ConFile . "<br>";
		CreateKMZforNewDrone($ConFile, $RKRKMissionSelected, $ConTo);
	}
	//readfile($RKRKRelWebLocation. $MissionDroneTypes[$RKRKMissionSelected]["MissionLocation"].$ConFile); 
}

///////////////////////////////////////
// Generate Output File Data
///////////////////////////////////////
	//$dosafe= true;	
	if ($dosafe) {

		//new configuration, safe configuration file
		//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
		
		$i= 1;

		$filecontent = "";
		//$filecontent = chr(34) .'Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung' . chr(34) . chr(13) . chr(10);
		$filecontent = implode($csv_deliminator, rkrk_einsatz_flight_csv_header()) . chr(13) . chr(10);
		foreach ($data as $entry) {
		//	echo $i . " " . $_POST["Initialen-" . $i];
		//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	
			IF (($entry["FlugID"] ?? "") != "") {
			
				//$rowcontent = chr(34);
				$rowcontent = "";
				$FlugID = $entry["FlugID"]; 
				$rowcontent .= $data[$FlugID]["FlugID"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Datum"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Zeit"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Pilot"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Feld"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Kontrollflug"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Geflogen"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Fund"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["UAV#"] . $csv_deliminator;
				$rowcontent .= str_replace($searchSDZ, $replaceSDZ, $data[$FlugID]["Felder"]) . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Jaeger"] . $csv_deliminator;
				$rowcontent .= str_replace($searchSDZ, $replaceSDZ, $data[$FlugID]["Kontaktnummer"]) . $csv_deliminator;
				$rowcontent .= str_replace($searchSDZ, $replaceSDZ, $data[$FlugID]["Landwirt"]) . $csv_deliminator;
				$rowcontent .= str_replace($searchSDZ, $replaceSDZ, $data[$FlugID]["Bemerkung"]) . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Flugzeit"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["AnzFelder"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["#Funde"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Einsatztag"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["GrundstückID"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Gde"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["JG"] . $csv_deliminator;
				// adding history data to the log
				$warnungOut = $data[$FlugID]["Warnung"] ?? "";
				$warnJg = $data[$FlugID]["JG"] ?? "";
				$warnFeld = $data[$FlugID]["Feld"] ?? "";
				if (
					is_string($warnJg) && $warnJg !== ""
					&& is_string($warnFeld) && $warnFeld !== ""
					&& isset($Feldkatalog[$warnJg]) && is_array($Feldkatalog[$warnJg])
					&& isset($Feldkatalog[$warnJg][$warnFeld]) && is_array($Feldkatalog[$warnJg][$warnFeld])
				) {
					$warnungOut = $Feldkatalog[$warnJg][$warnFeld]["Warnung"] ?? $warnungOut;
				}
				$rowcontent .= $warnungOut . $csv_deliminator;
				//$rowcontent .= $data[$FlugID]["Warnung"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Reg"] . $csv_deliminator;
				$rowcontent .= $data[$FlugID]["Flaeche"]. $csv_deliminator;;
				$rowcontent .= $data[$FlugID]["Status"];
				//$rowcontent .= chr(34). chr(13) . chr(10);
				$rowcontent .= chr(13) . chr(10);
				//echo $rowcontent . "<br>" . "<br>";
				
				$filecontent .= $rowcontent;
			} 
		} // foreach

		
		//echo "output: <br><br>" . $filecontent;
		if ($dosafe) {
			// MUST NOT DO!!
			//$filecontent = mb_convert_encoding( $filecontent, 'UTF-16LE', 'UTF-8');
			//echo "StoreLocation: " . $csvDocLocation . $csv_file . "<br>";
			$filepath= $csvDocLocation . $csv_file;

			// safe history
			$file = $filepath;
			$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
			//echo "<br>History: " . $file . " to  " . $newfile;
			if (!copy($file, $newfile)) {
				$SystemInfo .= "failed to copy $file...\n";
			}

			// safe file
			//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
			$df = fopen($filepath, 'w');
			fputs($df, $filecontent);
			fclose($df);
		} else {
			//echo $filecontent;
		}
}
	//read file (again after writing to file
	transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	//echo "FileLocation: " . $csvDocLocation.$csv_file . "<br>";
	$data = csv_to_array($csvDocLocation.$csv_file,';');
	if (!is_array($data)) {
		$data = array();
	}
	$MaxFlugID = 0;
	$data = FormatFlugDataArray($data);
	
	$fileloaded=true;
	//pre_r($data);

		// ===========================================
		// Print Page content
		// ===========================================
		$output = "";
		
		// Printing all the keys and values one by one

// =====================
// Prepare Page output
///=====================

	//pre_r(array_keys($Feldkatalog));
	//control elements
	if (isset($_POST["AnzLinesPPage"])) {
		if ($_POST["AnzLinesPPage"]=="all") {
			$AnzLinesPPage= 999;
		} else {
			$AnzLinesPPage= $_POST["AnzLinesPPage"];
		}
	} else {
		$AnzLinesPPage= 25;
	}
	if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
		$ActPage= $_GET["ActPage"];
	} else {
		$ActPage= 1;
	}
	if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
		$FontSize= $_POST["FontSize"];
	} else {
		$FontSize= "100%";
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");
	//page

	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$i= 1;
	$DataSetFound = false;

// ===========================================
// HEADER FORM
// ===========================================

$EnabledRegion = Array();
//form to change setting

//verhindere mehrfaches hinzufügen von Flügen
unsetPOST("add,delete");

echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;
//echo $i . " " . $KMLFileSettings->name . chr(10)

echo "<div class='rkrk-flight-controls-wrap'>";
echo "<table width=100%>";
	echo "<tr><td>";

	echo "</td><td>";
	echo "</td><td>";
	echo "</td><td>";
	if ($Layout!=2023) {
		echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
		echo CreateSiteLink ("EditFeld", "");
		echo CreateSiteLink ("Planning", "");
		echo CreateSiteLink ("Flugliste", "");
		echo CreateSiteLink ("FlugStats", "");
		echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
		echo CreateSiteLink ("FeldListe", "Reg=ALL&Typ=intern");
		echo CreateSiteLink ("Home", "");
	}
	echo "</td></tr>";

	$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
	$MinDate =date('Y-m-d', strtotime('-1 year'));
	$MinDate ="2021-01-01";
	$MaxDate =date('Y-m-d', strtotime('+1 year'));
	$Today = date("Y-m-d");
	
	echo "<tr><td>";
	echo "  Datum: " .chr(10);
	echo "</td><td>";
	echo DateJSFunctionField("SelFrom", $FromDate, $MinDate , $MaxDate ,"UpdSelDatum", "this.value", $i, 4 );
	echo " - " ;
	echo DateField("SelTo", $ToDate, $MinDate , $MaxDate);
	echo "</td>";
	echo "<td>";
	//date("Y-m-d", strtotime("last monday " . $Jahr . "-05"));
	//GetSasionStartDate($Jahr);
	echo "<input type='image' value='saison' src='" . $IconLoc . "/saison.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  GetSasionStartDate(date('Y', strtotime($FromDate))) . "&SelTo=" . GetSasionEndDate(date('Y', strtotime($FromDate))) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color: transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='minus1woche' src='" . $IconLoc . "/minus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 week')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='minus1tag' src='" . $IconLoc . "/minus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 day')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='heute' src='" . $IconLoc . "/heute.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  $Today . "&SelTo=" . $Today . "' height='" . $ButtonHeight . "'  title='Datum Heute auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='morgen' src='" . $IconLoc . "/morgen.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  $Tomorrow  . "&SelTo=" . $Tomorrow . "' height='" . $ButtonHeight . "'  title='Datum Morgen auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='plus1tag' src='" . $IconLoc . "/plus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 day')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='plus1woche' src='" . $IconLoc . "/plus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableConSet . "&" . $EnableDeleteSet."&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 week')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo CreateSiteLink ("PdfFlugliste", "FromDate=".  $FromDate . "&ToDate=" . $ToDate. "&Pilot=" . $PilotenWahl."&FeldNamenWahl=".$FeldNamenWahl);
	echo "</td>";
	echo "<td rowspan=3>";
	//Enable Neu
	if (GetAccess("NewField")) {
		if ($EnableNeu) {
			//echo "NEU - ON";
			echo "<input type='image' value='EnableNeu' src='" . $IconLoc . "/neu_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&EnableNeu=off" .  "&" .$ModusSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'title='Nur eingegebene Flüge anzeigen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			//echo "NEU - OFF";
			echo "<input type='image' value='EnableNeu' src='" . $IconLoc . "/neu_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&EnableNeu=on" .  "&" .$ModusSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "' title='Neue Flüge eingeben'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}
	//Enable Convert
	if (GetAccess("Convert")) {
		if ($EnableCon) {
			//echo "NEU - ON";
			echo "<input type='image' value='EnableNeu' src='" . $IconLoc . "/convert_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&EnableCon=off" . "&" . $EnableNeuSet . "&" .$ModusSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'title='Konvertierungsfunktion verbergen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			//echo "NEU - OFF";
			echo "<input type='image' value='EnableNeu' src='" . $IconLoc . "/convert_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet. "&EnableCon=on" . "&" . $EnableNeuSet . "&" .$ModusSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "' title='Konvertierungsfunktion anzeigen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}
	//Enable Delete
	if (GetAccess("DeleteData")) {
		if ($EnableDelete) {
			//echo "ED - ON";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&EnableDelete=off&" . "&" . $EnableConSet . "&" .$EnableNeuSet .  "&" .$ModusSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Delete Moduls ausschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			//echo "ED - OFF";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&EnableDelete=on&" . "&" . $EnableConSet . "&" .$EnableNeuSet .  "&" .$ModusSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'title='Delete Moduls einschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}


	echo  MissionLibraryButtons("&" . $EnableDeleteSet . "&" . $EnableConSet . "&" . $EnableNeuSet .  "&" .$ModusSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate,true);
	if (GetAccess("InternPilots")) {
		echo "<span id='exceldwnld'><a href='".$excel_csv_path."' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Userliste herunterladen'></a></span> ";
		//$csvDocLocation.$RKRKFlightsCSV= "_RKRKFluege.csv";
	}

	
	echo "</td>";
	echo "</tr>";
	echo "<tr><td>";
	echo "  Suche: " .chr(10);
	echo "</td><td>";
	echo InputField("FeldNamenWahl", $FeldNamenWahl, 20);
	echo "</td></tr>";
	echo "<tr><td>";
	echo "  Pilot: " .chr(10);
	echo "</td><td>";
	echo SelectField("PilotenWahl", $PilotenWahl ,$SelectPilots);
	if ($PilotenWahl == "") {
		echo  "<font color='red'> Bitte Pilot auswählen, wenn nicht alle Einträge editiert werden müssen</font>";
	}
			
	echo "</td></tr>";
	echo "</table  width=100%>";
echo "</div>";
	if ($DebugWork) {
		//echo "DB - ON";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=on' . '&' . $EnableConSet . '&' .$EnableNeuSet .'"> '. chr(10);
	} else {
		//echo "DB - OFF";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=off' . '&' . $EnableConSet . '&' .$EnableNeuSet . '"> '. chr(10);
	}
	echo "<br>";


	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Edit Fluege ", $FeldID ?? '', $RKRKMissionSelected,"");
		
		$outputdata = Array();
		
		foreach ($data as $Key => $Flug) {
			// selected to show
		//pre_r($Flug);
		//echo "Flug ID" . $Flug["FeldID"] . "<br>";
		if (	
				(
					$PilotenWahl == ""
					or ($Flug["Pilot"] == $PilotenWahl)
				)
				and
				(  
					$FeldNamenWahl == "" 
					or like($Flug["Feld"], strval($FeldNamenWahl))
					or (strpos($FeldNamenWahl, strval($Flug["Feld"]))!== false)
					or like($Flug["Pilot"], $FeldNamenWahl)
					or like($Flug["Kontaktnummer"], $FeldNamenWahl)
					or like($Flug["Jaeger"], $FeldNamenWahl)
					or like($Flug["Bemerkung"], $FeldNamenWahl)
					or like($Flug["Felder"], $FeldNamenWahl)
					or like($Flug["Landwirt"], $FeldNamenWahl)
				) 
				and // eingeschränkte Sichtbarkeit für extern/partners
				(
					(in_array($Flug["Pilot"], array_column($VisblePilots,"Opt")))
					or (in_array($WebFreigabe, $AllVisible))
				)
				and
				(
					($Flug["Datum"] >= $FromDate) and
					($Flug["Datum"] <= $ToDate)
				)
			)
			{
				$data[$Flug["FlugID"]]["ListNr"]= $i;
				$outputdata[$Flug["FlugID"]] = $Flug;
				//show debug label
				//echo "Flug ID" . $Flug["Feld"] . "<br>";
				if ($Flug["Feld"]==$ManualFieldLabel) {
					$manuallabelshow="";
					//echo "Manueller Flug" . "<br>";
				}	
				$i++;
			} else {
				$data[$Flug["FlugID"]]["ListNr"]= "";
			}
			//pre_r($Flug);
		}
		$MaxSelectedEntries = $i;
	}
	
	//save File or Exceloutput
	//FlugID;Datum;Zeit;Pilot;Feld;Kontrollflug;Geflogen;Fund;UAV#;Felder;Jaeger;Kontaktnummer;Landwirt;Bemerkung;Flugzeit;AnzFelder;#Funde;Einsatztag;GrundstückID;Gde;JG;Warnung;Reg;Flaeche;Status
	
	$TableSetup[] ="FlugID";
    $TableSetup[] ="Datum";
    $TableSetup[] ="Feld";
    $TableSetup[] ="Kontrollflug";
    $TableSetup[] ="Geflogen";
    $TableSetup[] ="Fund";
    $TableSetup[] ="UAV#";
    $TableSetup[] ="Felder";
    $TableSetup[] ="Jaeger";
    $TableSetup[] ="Kontaktnummer";
    $TableSetup[] ="Landwirt";
    $TableSetup[] ="Bemerkung";

	SaveDataToCSV($outputdata, $TableSetup, $excel_csv_path, "UTF-16LE", true, $excel_csv_file, $csv_desc);
	
	//pre_r($data);

	$i=1;
	$pageline = 1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage - 1;
	$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	//echo "AnzLinesPPage: " .$AnzLinesPPage . "<br>";
	//echo "Show: " .$showfrom . " - " . $showto ."<br>";

	echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	//echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
	//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
	echo "<br>" .chr(10);
	echo "<div class='rkrk-flight-save-sticky'>" .chr(10);
	if (GetAccess("TestOnly") or ($PilotenWahl != "")) {
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Alle Flüge speichern", false);
	} else {
		echo "<font color='red'> <i>Speichern nur bei Auswahl eines Piloten möglich</i></font>";
	}	
	echo "</div>" .chr(10);
	echo "<br>" .chr(10);
	//form to change setting
echo "<div class='rkrk-flight-table-wrap'>" . chr(10);
echo "<table class='rkrk-flight-main rkrk-files-collapsed rkrk-meta-collapsed'>" .chr(10);
	echo "<tr >" .chr(10);
	echo "<th class='rkrk-flight-summary-th'></th>" .chr(10);
	if ($EnableDelete) {
		echo "<th></th>" .chr(10);
	}
	$FeldListe[]="";
	$FeldListe = array_merge($FeldListe, GetFieldList($Feldkatalog));
	$FeldListe[$ManualFieldLabel]=$ManualFieldLabel;
	
//Datum,Zeit,Pilot,Feld,Kontrollflug,Fund,UAV#,Felder,Jaeger,Kontaktnummer,Landwirt,Bemerkung,Flugzeit,AnzFelder,#Funde,Einsatztag,GrundstückID,Gde,JG,Warnung,Reg,Flaeche
	//if ($EnableDelete) {
	//	echo "<th></th>" .chr(10);
	//}

			
	echo "<th></th>";
	if ($debugmode) {
		echo "<th>#</th>" .chr(10);
	}
	echo "<th>Datum*</th>" .chr(10);
	echo "<th>Zeit</th>" .chr(10);
	echo "<th></th>" .chr(10);
	echo "<th>Pilot</th>" .chr(10);
	echo "<th>Feld*</th>" .chr(10);
	echo "<th>Kontrollflug</th>" .chr(10);
	echo "<th class='rkrk-col-files-toggle'><button type='button' class='rkrk-files-toggle' title='Karten und Missionsdateien ein- oder ausblenden' aria-expanded='true' aria-label='Karten und Dateien'>&#9662;</button></th>" .chr(10);
	echo "<th class='rkrk-col-files'></th>" .chr(10);
	foreach ($FluegeFileSettings as $FileKey => $FileType) {
		echo "<th class='rkrk-col-files'></th>" .chr(10);
	}
	if (GetAccess("Convert") and  $EnableCon) {
		//Konversion
		foreach(explode(",",$MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"]) as $TargetMission) {
			if ($TargetMission!="") {
				echo "<th class='rkrk-col-files'></th>" .chr(10);
				echo "<th class='rkrk-col-files'></th>" .chr(10);
			}
		}
	}

	echo "<th class='rkrk-col-meta-toggle'><button type='button' class='rkrk-meta-toggle' title='Flugzeit, Fläche, Jagdgebiet und Gemeinde ein- oder ausblenden' aria-expanded='false' aria-label='Zusatzspalten'>&#9662;</button></th>" .chr(10);
	echo "<th class='rkrk-col-meta'>FZeit min</th>" .chr(10);
	//Manuelle Flug-Felder
	echo "<th class='rkrk-col-meta'><span id='L_Flaeche'>Fläche</span></th>" .chr(10);
	echo "<th class='rkrk-col-meta'><span id='L_JG'><label title='Jagdgebiet'>JG</label></span></th>" .chr(10);
	echo "<th class='rkrk-col-meta'><span id='L_Gde'><label title='Gemeinde'>Gde</label></span></th>" .chr(10);
	echo "<th><span id='L_GrundstückID'><label title='GrundstückID'>GstkID</label></span></th>" .chr(10);
	//Manuelle Flug-Felder - Ende
	echo "<th>Geflogen</th>" .chr(10);
	echo "<th>Fund</th>" .chr(10);
	echo "<th>UAV#</th>" .chr(10);
	echo "<th>Felder</th>" .chr(10);
	echo "<th>Jaeger</th>" .chr(10);
	echo "<th>Kontaktnummer</th>" .chr(10);
	echo "<th>Landwirt</th>" .chr(10);
	echo "<th>Bemerkung</th>" .chr(10);
	//Debug Felder
	echo "<th><span " . $debuglabelshow . " id='L_AnzFelder'>AnzFelder</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_#Funde'>#Funde</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_Einsatztag'>Einsatztag</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_Warnung'>Warnung</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_Reg'>Reg</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_FlugID'>FlugID</span></th>" .chr(10);
	echo "<th><span " . $debuglabelshow . " id='L_Status'>Status</span></th>" .chr(10);
	//Debug Felder - Ende

	echo "</tr >" .chr(10);
	//new data
	echo "<tr class='rkrk-flight-label-row'><td colspan='30'><i>Buchungen:</i></td></tr>". chr(10);
	// Extisting Entries
	$PrevPilot ="";
	$ActDate =$FromDate;
	$PrevDate =$Tomorrow;
	$PrevPilot = "";
	$MaxEntry = $i+2;
	if ($EnableNeu) {
		GetNewLine();
	}
	$NewLines = false;
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?FFilter=25-048+25-049+21-001+21-004+21-007&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL21 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off&WW=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&". $MissionSelectionParameter;
	$GeoMapURL22 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off&WW=off&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&". $MissionSelectionParameter;

	foreach ($data as $dataline) {
		//pre_R($dataline);
		if ($dataline["ListNr"]!="" &&
			$dataline["ListNr"]>=$showfrom && 
			$dataline["ListNr"]<=$showto){
			// Separator
			if (
				(($PrevPilot !=$dataline['Pilot']) or
				($PrevDate !=$dataline['Datum'])) and
				true
				)
			{
				$MaxEntry = $i+2;
				if ($PrevDate=="") {
					$ActDate = $Tomorrow;
				} else {
					$ActDate = $PrevDate;
				}
				//$ActPilot = $dataline['Pilot'];
				$DayFieldList = array();
				$PilotDayFieldList =array();
				$PilotDayListInOrder ="";
				$FlightList = array();
				if ($EnableNeu and $NewLines) 
					GetNewLine();
				$NewLines= true;	
				//get link to map
				$LockStatus=false;
				foreach ($data as $dl) {
					if (($dl['Datum']==$dataline['Datum']) 
						and
						// Ausblenden von Zukünftigen Flügen mit Geflogen = Nein
						(
							!(($dl["Datum"] >= $Today) and
							($dl["Geflogen"] == "Nein"))
						)
						)
						{
						$DayFieldList[] =$dl['Feld'];
						$c[] =$dl['Feld'];
						if ($dl['Pilot']==$dataline['Pilot'] ) {
							$FlightList[] =$dl['FlugID'];
							$PilotDayFieldList[] =$dl['Feld'];
							if ($dl['Status']=="locked")
								$LockStatus = true;
						}
					}
				}
				$PilotDayFieldList = implode('+', $PilotDayFieldList);
				$DayFieldList = implode('+', $DayFieldList);
				$FlightList = implode('+', $FlightList);
				
				// Prefill data for checklist button (day + pilot block)
				$firstTime = "";
				$firstFieldId = "";
				$fieldTimeEntries = array();
				$auftragJaeger = array();
				$auftragBauern = array();
				foreach ($data as $dl) {
					if ($dl['Datum'] == $dataline['Datum'] && $dl['Pilot'] == $dataline['Pilot']) {
						if ($firstFieldId == "") $firstFieldId = $dl['Feld'];
						if ($firstTime == "" && trim((string)$dl['Zeit']) != "") $firstTime = trim((string)$dl['Zeit']);
						$fidTmp = trim((string)($dl['Feld'] ?? ''));
						$timeTmp = trim((string)($dl['Zeit'] ?? ''));
						if ($fidTmp !== '' && preg_match('/^\d{1,3}-\d{1,4}$/', $fidTmp)) {
							$fieldTimeEntries[] = $fidTmp . "=" . ($timeTmp !== '' ? substr($timeTmp, 0, 5) : '');
						}
						if (trim((string)$dl['Jaeger']) != "") $auftragJaeger[trim((string)$dl['Jaeger'])] = trim((string)$dl['Jaeger']);
						if (trim((string)$dl['Landwirt']) != "") $auftragBauern[trim((string)$dl['Landwirt'])] = trim((string)$dl['Landwirt']);
					}
				}
				$fieldTimeMapStr = implode("; ", $fieldTimeEntries);
				$dtPrefill = $dataline['Datum'] . "T" . ($firstTime != "" ? substr($firstTime, 0, 5) : "00:00");
				$flugortPrefill = "";
				if ($firstFieldId != "" && isset($FieldDataArray[$firstFieldId])) {
					$flugortPrefill = trim((string)($FieldDataArray[$firstFieldId]["Gemeinde"] ?? ""));
					if ($flugortPrefill == "") {
						$flugortPrefill = trim((string)($FieldDataArray[$firstFieldId]["Text"] ?? ""));
					}
				}
				$jgPrefill = "";
				if ($firstFieldId != "" && isset($FieldDataArray[$firstFieldId]["JG"])) {
					$jgPrefill = (string)$FieldDataArray[$firstFieldId]["JG"];
				}
				$flugortPrefill = NormalizeChecklistFlugort($flugortPrefill, $jgPrefill);
				$feldListForChecklist = str_replace("+", ", ", $PilotDayFieldList);
				$auftraggeber = implode(", ", array_filter(array_unique(array_merge(array_values($auftragJaeger), array_values($auftragBauern)))));
				$checklistUrl = BuildChecklistLink(array(
					"Datum & Zeit"   => $dtPrefill,
					"Flugort"        => $flugortPrefill,
					"Pilot"          => ChecklistPilotName($dataline['Pilot']),
					"Feld#"          => $feldListForChecklist,
					"Zeit zum Feld"  => $fieldTimeMapStr,
					"Auftraggeber"   => $auftraggeber,
					"Art des Fluges" => "Auftrag",
				));
				$downloadJsonOnClick = BuildPilotDownloadJsonOnClick(
					$data,
					$dataline['Pilot'],
					$dataline['Datum'],
					$Feldkatalog,
					$RKRKLocalLoactionMissionFiles
				);
				$downloadKMZOnClick = BuildPilotDownloadKMZOnClick(
					$data,
					$dataline['Pilot'],
					$dataline['Datum'],
					$Feldkatalog,
					$RKRKLocalLoactionMissionFiles
				);
				
				//pre_R($PilotDayFieldList);
				echo "<tr class='rkrk-flight-sep-row'><td colspan='14'>";
				echo "<b><i>" . $dataline['Datum'] . "/ " . $dataline['Pilot'] . "</i></td><td colspan='10'>". chr(10);
				echo "Freigabe:  ". chr(10);
				if ($LockStatus) {
					echo "<input type='image' value='DBW' src='" . $IconLoc . "/locked.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Free=" . $FlightList . "&" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" . $EnableNeuSet .  "&" .$ModusSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeightSmall . "' title='GESPERRT - bei drücken Flüge freigeben'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
				} else {
					echo "<input type='image' value='DBW' src='" . $IconLoc . "/released.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Lock=" . $FlightList . "&". $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" . $EnableNeuSet .  "&" .$ModusSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeightSmall . "' title='FREIGEGEBEN - bei drücken ,Flüge sperren!'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
				}
				echo " - ". chr(10);
				// GeoMap Link
				echo "<i>Tagesflugplan </i>" . chr(10);
				echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'&Pilot='. $dataline['Pilot'] . '&FeldNamenWahl='.$FeldNamenWahl . '" target="_pdfplan"><img src="' . $IconLoc . '/pdf_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf PDF"></a>' .chr(10);
				echo'<a href="' .$GeoMapURL1 . $PilotDayFieldList . $GeoMapURL21 . '" target="_geomapflight"><img src="' . $IconLoc . '/geomapbutton_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf Geomap"></a>' .chr(10);
				echo '<a href="' . htmlspecialchars($checklistUrl) . '" target="_checklist_new" title="Neue Checkliste mit Tagesplanung-Preset öffnen"><img src="' . $IconLoc . '/button_checklist.png" height="' . $ButtonHeightSmall . '" style="vertical-align:middle;" alt="Checkliste"></a>';
				echo '<a href="#" onclick="' . htmlspecialchars($downloadKMZOnClick, ENT_QUOTES, "UTF-8") . '" title="Alle KMZ dieses Piloten am Tag einzeln herunterladen"><img src="' . $IconLoc . '/downloadfiles.png" height="' . $ButtonHeightSmall . '" style="vertical-align:middle; margin-left:6px;" alt="Dateien herunterladen"></a>';
				echo '<a href="#" onclick="' . htmlspecialchars($downloadJsonOnClick, ENT_QUOTES, "UTF-8") . '" title="Alle JSON dieses Piloten am Tag einzeln herunterladen"><img src="' . $IconLoc . '/downloadjsonfiles.png" height="' . $ButtonHeightSmall . '" style="vertical-align:middle; margin-left:6px;" alt="Dateien herunterladen"></a>';
				echo " <i> des Piloten </i>". chr(10);
				if (in_array($WebFreigabe, $AllVisible)) {
					echo " - ";
					echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum']. '&FeldNamenWahl='.$FeldNamenWahl .'" target="_pdfplan"><img src="' . $IconLoc . '/pdf_tag.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf PDF"></a>' .chr(10);
					echo'<a href="' .$GeoMapURL1 . $DayFieldList . $GeoMapURL22 . '" target="_geomapflight"><img src="' . $IconLoc . '/geomapbutton_day.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf Geomap"></a>' .chr(10);
					echo " <i> des Teams </i>";
					}
				//echo "</td></tr>". chr(10);
				echo "</td></tr>" .chr(10);
			}


			// Existing Data
			$flightRowClass = 'rkrk-flight-data-row';
			if (($dataline['Feld'] ?? '') === $ManualFieldLabel) {
				$flightRowClass .= ' rkrk-row-manual';
			}
			echo '<tr class="' . $flightRowClass . '" data-line="' . $i . '">' .chr(10);
			echo '<td class="rkrk-flight-summary-cell"><button type="button" class="rkrk-flight-summary" aria-label="Flug bearbeiten"></button></td>' .chr(10);
			if ($EnableDelete) {
				echo '<td class="rkrk-col-action">' .chr(10);
				// delete
				if (GetAccess("DeleteData")) {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?DelFlugID=" . $dataline["FlugID"] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&" . $EnableConSet . "&" .$EnableNeuSet ."&DBW=" . ($_GET["DBW"] ?? "") . "' height='" . $ButtonHeightSmall . "' title='Flug " . $dataline["FlugID"] . " löschen'>" .chr(10);
				} else {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?Line=" . $i . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&" . $EnableConSet . "&" .$EnableNeuSet . "' height='" . $ButtonHeightSmall . "'  title='Linie " . $i . " löschen'>" .chr(10);
				}
			echo '</td>' .chr(10);
			}
			if ($debugmode) {
				echo '<td class="rkrk-col-secondary">' .chr(10);
				echo $i;
				echo '</td>' .chr(10);
			}	
			echo '<td class="rkrk-col-action">' .chr(10);
			//SAVE
			echo "<input type='image' value='SaveLine' src='" . $IconLoc . "/save_line.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter  . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" .$EnableNeuSet . "&SaveLine=".$dataline["FlugID"]."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Flug " .$dataline["FlugID"]. " speichern'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
			echo '</td>' .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			//echo $dataline['Datum'] . " ";
			echo DateJSFunctionField("Datum-" . $i, $dataline['Datum'], $MinDate , $MaxDate ,"UpdDatum", "this.value", $i, 4 );
			echo '</td>' .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			//echo $dataline['Zeit'] . " ";
			echo InputTimeField("Zeit-" . $i, $dataline['Zeit'], 2 , "left");
			echo '</td>' .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//Flugwarnung Nacht-/Sperrzonen
				$warnFeldId = (string)($dataline['Feld'] ?? '');
				$warnJgId = explode('-', $warnFeldId)[0] ?? '';
				$Warning = '';
				if (
					$warnJgId !== '' && $warnFeldId !== ''
					&& isset($Feldkatalog[$warnJgId]) && is_array($Feldkatalog[$warnJgId])
					&& isset($Feldkatalog[$warnJgId][$warnFeldId]) && is_array($Feldkatalog[$warnJgId][$warnFeldId])
				) {
					$Warning = (string)($Feldkatalog[$warnJgId][$warnFeldId]['Warnung'] ?? '');
				}
				if ($Warning === '') {
					$Warning = (string)($dataline['Warnung'] ?? '');
				}
				//echo $Warning;
				$CheckWarnings = Array("NGT1", "NGT2", "STP");
				foreach ($CheckWarnings as $CheckWarning) {
					//echo $CheckWarning;
					if (strpos($Warning,$CheckWarning)!== false) {
						//echo $CheckWarning;
						$Picture =  $IconLoc . chr(92) . $OptionWarning[$CheckWarning]["Pic"];
						echo " <img src='" . $Picture . "' height='" . 0.7* $ButtonHeight ."' title='".  $OptionWarning[$CheckWarning]["Text"] . "' > ";
					}
				}
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			//echo SelectField("Pilot-" . $i, strval($dataline['Pilot']) ,$Pilots );
			echo SelectJSFunctionField("Pilot-" . $i, strval($dataline['Pilot']) , rkrk_pilot_select_codes('assign', $dataline['Datum'] ?? '', $dataline['Pilot'] ?? '', false) , "UpdPilot", "this.value", $i );
			echo '</td>' .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			//echo SelectField("Feld-" . $i, $dataline['Feld'] ,$FeldListe );
			echo SelectJSFunctionField("Feld-" . $i, $dataline['Feld'] ,$FeldListe , "UpdField", "this.value", $i );
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo SelectField("Kontrollflug-" . $i, $dataline['Kontrollflug'] ,$OptControlFlight );
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary rkrk-col-files-toggle"></td>' .chr(10);
			
			// Download Litchi
			echo "<td class='rkrk-col-secondary rkrk-col-files' data-summary-action='1'>" .chr(10);
			if (GetAccess("GoogleMapLocation")){
				//Zeig Parkplatz an falls definiert, ansonsten den Startpunkt)
				$gs = array();
				$gsFeld = (string)($dataline['Feld'] ?? '');
				$gsJg = ($gsFeld !== '' && strpos($gsFeld, '-') !== false) ? explode('-', $gsFeld, 2)[0] : '';
				if ($gsJg !== '' && isset($Feldkatalog[$gsJg][$gsFeld]['Grundstueckdaten']) && is_array($Feldkatalog[$gsJg][$gsFeld]['Grundstueckdaten'])) {
					$gs = $Feldkatalog[$gsJg][$gsFeld]['Grundstueckdaten'];
				}
				if (($gs["Parkplatz"] ?? '') != "") {
					$Location = explode("," ,$gs["Parkplatz"]);
				} else {
					$Location = explode("," ,(string)($gs["Startplatz"] ?? ''));
				}
					
				$StartLat = $Location[0] ?? '';
				$StartLon = $Location[1] ?? '';
				
				if ($StartLat != "" && $StartLon !='') {
					echo "<!--startpunkt--><a href='https://maps.google.ch/?q=". trim($StartLon) . " ," . trim($StartLat) . "&t=k'><img src='" . $IconLoc . "\startpnt.png' height='" . $ButtonHeight ."' title='Startpunkt File " . $dataline['Feld'] . " auf GoogleMap'></a> " . chr(10);
				}
			}
			echo '</td>' .chr(10);
			$JsonFileList=Array();
			$KMZFileList=Array();
			foreach ($FluegeFileSettings as $FileKey => $FileType) {
				echo "<td class='rkrk-col-secondary rkrk-col-files' data-summary-action='1'>" .chr(10);
				$fileAccess = trim((string)($FileType["Access"] ?? ""));
				if ($fileAccess === "" || GetAccess($fileAccess)) {
					$xmlLabel = trim((string)($FileType["XMLLabel"] ?? ""));
					foreach (rkrk_fluege_field_file_links($Feldkatalog, $dataline['Feld'], $xmlLabel) as $Link) {
						$Flightheight = GetMeaningFilename($Link)[4];
						$iconBase = trim((string)($FileType["icon"] ?? "file"));
						$iconPath = $IconLoc . "/" . $iconBase . $Flightheight . ".png";
						if (!file_exists($iconPath)) {
							$iconPath = $IconLoc . "/" . $iconBase . "xx.png";
						}
						$fileHref = $RKRKLocalLoactionMissionFiles . $Link;
						$convHref = '';
						if (($xmlLabel === 'JasonFile' || $xmlLabel === 'KMZFile') && function_exists('rkrk_file_convert_download_href')) {
							$convHref = rkrk_file_convert_download_href($Link);
							if ($convHref !== '') {
								$fileHref = $convHref;
							}
						}
						if ($xmlLabel === 'JasonFile' && $convHref === '' && function_exists('rkrk_json_flight_export_href')) {
							$exportHref = rkrk_json_flight_export_href($Link);
							if ($exportHref !== '') {
								$fileHref = $exportHref;
							}
						}
						$kmlHref = ($xmlLabel === 'KMZFile' && function_exists('rkrk_debug_kml_href')) ? rkrk_debug_kml_href($Link) : '';
						$isStaticFile = ($fileHref === ($RKRKLocalLoactionMissionFiles . $Link));
						echo "<a href='" . htmlspecialchars($fileHref, ENT_QUOTES, 'UTF-8') . "'" . ($kmlHref !== '' ? " data-kml='" . htmlspecialchars($kmlHref, ENT_QUOTES, 'UTF-8') . "'" : "") . ($isStaticFile ? " download" : "") . "><img src='" . htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') . "' height='" . (int)$ButtonHeight . "' title='" . htmlspecialchars($FileType["Text"] . " " . $dataline['Feld'] . " - " . $Link, ENT_QUOTES, 'UTF-8') . "' ></a> <wbr>" . chr(10);
						if ($xmlLabel === "JasonFile") {
							$JsonFileList[] = $Link;
						}
						if ($xmlLabel === "KMZFile") {
							$KMZFileList[] = $Link;
						}
					}
				}
				echo '</td>' .chr(10);
			}
			if (GetAccess("Convert") and  $EnableCon) {
				//Konversion
				foreach(explode(",",$MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"]) as $TargetMission) {
					if ($TargetMission!="") {
						echo "<td class='rkrk-col-secondary rkrk-col-files'>" .chr(10);
						foreach($JsonFileList as $FileLink) {
							$NewHeight = GetFlightHeightForAltDrone($RKRKMissionSelected, $TargetMission, GetMeaningFilename($FileLink)[4], "IRCamAngle_H", 5);
							$NewDroneFilename = ChangeHeightInFilename($FileLink, $NewHeight, $TargetMission, false).".json";
							$FullFileLink = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$RegionSettings[$dataline['JG']]["Dir"]."/".$NewDroneFilename;
							//$FullFileLink = $WebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$FileLink;
							if (file_exists($FullFileLink)) {
								echo "<a href='" . $FullFileLink . "'  download><img src='". $IconLoc . "/". $MissionDroneTypes[$TargetMission]["json_button"] . "' height='" . $ButtonHeight ."' title='Herunterladen von ".  $FileLink ."' ></a> <wbr>" . chr(10);
							} else {
								echo "<a href='" . htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" . $EnableNeuSet .  "&" . $ModusSet . "&Pilot=" . $PilotenWahl . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "&ConFile=" . $FileLink . "&ConTo=" . $TargetMission . "'><img src='". $IconLoc . "/". $MissionDroneTypes[$TargetMission]["json_button_orange"] . "' height='" . $ButtonHeight ."' title='Konvertieren von ".  $FileLink ."' ></a> <wbr>" . chr(10);
							}
							//echo "File: ". $FullFileLink . "<br>";
						}
						echo '</td>' .chr(10);
						echo "<td class='rkrk-col-secondary rkrk-col-files'>" .chr(10);
						foreach($KMZFileList as $FileLink) {
							$NewHeight = GetFlightHeightForAltDrone($RKRKMissionSelected, $TargetMission, GetMeaningFilename($FileLink)[4], "IRCamAngle_H", 5);
							$NewDroneFilename = ChangeHeightInFilename($FileLink, $NewHeight, $TargetMission, false).".kmz";
							$FullFileLink = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$RegionSettings[$dataline['JG']]["Dir"]."/".$NewDroneFilename;
							//$FullFileLink = $WebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$FileLink;
							if (file_exists($FullFileLink)) {
								echo "<a href='" . $FullFileLink . "'  download><img src='". $IconLoc . "/". $MissionDroneTypes[$TargetMission]["kmz_button"] . "' height='" . $ButtonHeight ."' title='Herunterladen von ".  $FileLink ."' ></a> <wbr>" . chr(10);
							} else {
								echo "<a href='" . htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" . $EnableNeuSet .  "&" .$ModusSet . "&Pilot=" . $PilotenWahl .  "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "&ConFile=" . $FileLink . "&ConTo=" . $TargetMission . "'><img src='". $IconLoc . "/". $MissionDroneTypes[$TargetMission]["kmz_button_orange"] . "' height='" . $ButtonHeight ."' title='Konvertieren von ".  $FileLink ."' ></a> <wbr>" . chr(10);
							}
							//echo "File: ". $FullFileLink . "<br>";
						}
						echo '</td>' .chr(10);
					}
				}
			}

/*
			echo "<td>" .chr(10);
			if (GetAccess("MapDownloadLitchi")){
				$LinkData= explode(",",$Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["LichtiFile"]);
				//echo $LinkData = explode(",",$Feldkatalog[$dataline['Feld']][explode("-",$dataline['Feld'])[0]]["LichtiFile"]);
				foreach ($LinkData as $Link) {
					foreach ($OptionFlughoehe as $Flughoehe) {
						if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
							$Flightheight= $Flughoehe["Opt"];
							$Picture =  $IconLoc . "\litchi" . $Flughoehe["Opt"] . ".png";
						}
					}
					if ($Link !="") {
						echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'><img src='" . $Picture . "' height='" . $ButtonHeight ."' title='Litchi File " . $dataline['Feld'] . " - " . $Link . "' ></a> <wbr>" . chr(10);
					}
				}
			}
			echo '</td>' .chr(10);
			// Download Json
			echo "<td>" .chr(10);
			if (GetAccess("MapDownloadJSON")){
				$LinkData= explode(",",$Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["JasonFile"]);
				//echo $LinkData = explode(",",$Feldkatalog[$dataline['Feld']][explode("-",$dataline['Feld'])[0]]["LichtiFile"]);
				foreach ($LinkData as $Link) {
					foreach ($OptionFlughoehe as $Flughoehe) {
						if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
							$Flightheight= $Flughoehe["Opt"];
							$Picture =  $IconLoc . "\json" . $Flughoehe["Opt"] . ".png";
						}
					}
					if ($Link !="") {
						echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'  download target='_blank'><img src='" . $Picture . "' height='" . $ButtonHeight ."' title='JSON File " . $dataline['Feld'] . " - " . $Link . "'></a> <wbr>" . chr(10);
					}
				}
			}	
			echo '</td>' .chr(10);
/**/
			if ($dataline['Feld'] == $ManualFieldLabel) {
				$ManualFieldshow = "";
			    $manuelaction = "";
			} else {
				$ManualFieldshow = "hidden";
			    $manuelaction = "readonly='readonly'";
			}	
			echo '<td class="rkrk-col-secondary rkrk-col-meta-toggle"></td>' .chr(10);
			echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
			echo InputField("Flugzeit-" . $i, round($dataline['Flugzeit'],1), 2, "right", "", "", $manuelaction);

			/*
			echo "<span id='FZeit-" . $i."'>";
			echo round($dataline['Flugzeit'],1) ." ";
			echo InputField("Flugzeit-" . $i, $dataline['Flugzeit'], 10, "left", "", "", "", "hidden");
			//InputField($FieldName, $Value ,$Size=10, $align="left", $type="", $class="" , $action="" , $show="") 
			//$dataline['Flugzeit'];
			echo "</span>";
			*/
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
			//echo InputField("Flaeche-" . $i, $dataline['Flaeche'], 5);
			echo InputField("Flaeche-" . $i, $dataline['Flaeche'], 3, "right", "", "", $manuelaction);
			//$dataline['Flaeche'];
			//echo InputField("Flaeche-". $i, $dataline['Flaeche'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
			//echo InputField("JG-" . $i, $dataline['JG'], 5);
			echo InputJSFunctionField("JG-" . $i, $dataline['JG'], "UpdReg", "this.value", $i, 3, "right", "", "", $manuelaction);
			
			//$dataline['JG'];
			//echo InputField("JG-". $i, $dataline['JG'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary rkrk-col-meta">' .chr(10);
			//echo InputField("Gde-" . $i, $dataline['Gde'], 5);
			echo InputField("Gde-" . $i, $dataline['Gde'], 5, "left", "", "", $manuelaction);
			//$dataline['Gde'];
			//echo InputField("Gde-". $i, $dataline['Gde'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['GrundstückID'];
			echo InputField("GrundstückID-" . $i, $dataline['GrundstückID'], 5, "left", "", "", $manuelaction);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			echo SelectField("Geflogen-" . $i, $dataline['Geflogen'] ,$OptControlFlight );
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			//echo InputField("Fund-" . $i, $dataline['Fund'], 2);
			echo InputJSFunctionField("Fund-" . $i, $dataline['Fund'], "UpdFund", "this.value", $i, 4 );
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo InputField("UAV#-" . $i, $dataline['UAV#'], 10);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-primary">' .chr(10);
			echo InputField("Felder-" . $i, $dataline['Felder'], 30);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo SelectJSFunctionField("Jaeger-" . $i, $dataline['Jaeger'] ,$OptionJaeger , "UpdJaeger", "this.value" , $i);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo InputField("Kontaktnummer-" . $i, $dataline['Kontaktnummer'], 28);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo InputField("Landwirt-" . $i, $dataline['Landwirt'], 28);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			echo InputField("Bemerkung-" . $i, $dataline['Bemerkung'], 30);
			//echo InputField("Bemerkung-" . $i, $dataline['Bemerkung'], 30, "left", "", "", "", "hidden");
			//$FieldName, $Value ,$Size=10, $align="left", $type="", $class="" , $action="" , $show=""
			echo "</td>" .chr(10);
			//DEBUG Felder
			//------------------------
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['AnzFelder'];
			echo InputField("AnzFelder-" . $i, $dataline['AnzFelder'], 2, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['#Funde'];
			echo InputField("#Funde-" . $i, $dataline['#Funde'], 5, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['Einsatztag'];
			echo InputField("Einsatztag-" . $i, $dataline['Einsatztag'], 6, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['Warnung'];
			echo InputField("Warnung-" . $i, $dataline['Warnung'], 8, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['Reg'];
			echo InputField("Reg-" . $i, $dataline['Reg'], 5, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//echo InputField("FlugID-". $i, $dataline['FlugID'], "left", "hidden");
			echo InputField("FlugID-". $i, $dataline['FlugID'], 5 , "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td class="rkrk-col-secondary">' .chr(10);
			//$dataline['Status'];
			echo InputField("Status-". $i, $dataline['Status'], 5 ,"left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo "</tr>" .chr(10);
			$PrevPilot =$dataline['Pilot'];
			$PrevDate =$dataline['Datum'];
			$ActDate =$PrevDate;

		}
		$i++;
	}
	$MaxEntry = $i+2;
	if ($EnableNeu) 
		GetNewLine();
	
	echo "</table>" .chr(10);
echo "</div>" .chr(10);
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

//	echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
	//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
	//PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Alle Flüge speichern", false);
	if (GetAccess("TestOnly") or ($PilotenWahl != "")) {
		echo "<div class='rkrk-flight-save-sticky'>" .chr(10);
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Alle Flüge speichern", false,);
		echo "</div>" .chr(10);
	} else {
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "", false);
		echo "<font color='red'> <i>Speichern nur bei Auswahl eines Piloten möglich</i></font>";
	}	
	
	echo '</form>'. chr(10);
    echo '<br>'. chr(10);
    echo '<i>*) Feld muss immer einen Wert haben, sonst wird der Flug nicht registriert.</i>'. chr(10);
  

//pre_R($FieldDataArray);


?>

<pre id="debug"></pre>
<script type='text/javascript'>
<?php
//echo json_encode($FieldDataArray);
//echo json_encode($AdressDataArray);
echo "var FieldDataArray = ". json_encode($FieldDataArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)) . ";\n";
echo "var AdressDataArray = ". json_encode($AdressDataArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)) . ";\n";
echo "var RegDataArray = ". json_encode($RegDataArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)) . ";\n";
echo "var FluzeitStartUndLandung = ". $FluzeitStartUndLandung . ";\n";
echo "var ManualFieldLabel = '". $ManualFieldLabel ."';\n";
?>

// Transfering date from PHP to JSON
//var FieldDataArray = $.parseJSON(<?php echo json_encode($FieldDataArray); ?>);
//var AdressDataArray = $.parseJSON(<?php echo json_encode($AdressDataArray); ?>);

//  document.getElementById("debug").innerHTML = FieldDataArray["21-012"]["FeldID"];
	

// update fields according to changes
function rkrkLookupFieldMeta(fid) {
  fid = String(fid || '').trim();
  if (!fid || !FieldDataArray) return null;
  if (FieldDataArray[fid]) return FieldDataArray[fid];
  var fidUp = fid.toUpperCase();
  for (var k in FieldDataArray) {
    if (!Object.prototype.hasOwnProperty.call(FieldDataArray, k)) continue;
    if (String(k).toUpperCase() === fidUp) return FieldDataArray[k];
  }
  return null;
}

function rkrkCleanFelderText(text) {
  if (text == null) return '';
  text = String(text).trim();
  if (text === '{}' || text === '[]') return '';
  return text.replace(/\s*\{\}\s*/g, ' ').replace(/\s+/g, ' ').trim();
}

function rkrkScalarText(value) {
  if (value == null) return '';
  if (typeof value === 'object') {
    if (Array.isArray(value)) {
      for (var i = 0; i < value.length; i++) {
        var part = rkrkScalarText(value[i]);
        if (part !== '') return part;
      }
      return '';
    }
    for (var k in value) {
      if (!Object.prototype.hasOwnProperty.call(value, k)) continue;
      var nested = rkrkScalarText(value[k]);
      if (nested !== '') return nested;
    }
    return '';
  }
  var text = String(value).trim();
  if (text !== '' && (text.charAt(0) === '{' || text.charAt(0) === '[') && text.indexOf('"0"') !== -1) {
    try {
      var decoded = JSON.parse(text);
      if (decoded && typeof decoded === 'object') {
        var repaired = rkrkScalarText(decoded);
        if (repaired !== '') return repaired;
      }
    } catch (e) {}
  }
  return text;
}

function rkrkSetLineField(line, name, value) {
  var el = document.getElementById(name + '-' + line);
  if (el) {
    var text = rkrkScalarText(value);
    if (name === 'Felder') {
      text = rkrkCleanFelderText(text);
    }
    el.value = text;
  }
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}

function rkrkClearCatalogFields(line) {
  rkrkSetLineField(line, 'Felder', '');
  rkrkSetLineField(line, 'GrundstückID', '');
  rkrkSetLineField(line, 'Gde', '');
  rkrkSetLineField(line, 'JG', '');
  rkrkSetLineField(line, 'Reg', '');
  rkrkSetLineField(line, 'Flaeche', '');
  rkrkSetLineField(line, 'Einsatztag', '');
  rkrkSetLineField(line, 'Jaeger', '');
  rkrkSetLineField(line, 'Warnung', '');
  rkrkSetLineField(line, 'Kontaktnummer', '');
  rkrkSetLineField(line, 'Landwirt', '');
  rkrkSetLineField(line, 'Flugzeit', '');
}

function rkrkApplyCatalogField(val, line) {
  var meta = rkrkLookupFieldMeta(val);
  if (!meta) {
    rkrkClearCatalogFields(line);
    return;
  }
  var jg = String(meta.JG || '').trim();
  var regUp = (RegDataArray && RegDataArray[jg] && RegDataArray[jg].RegUp) ? RegDataArray[jg].RegUp : '';
  var jaeger = String(meta.Jaeger || '').trim();
  var kontakt = (jaeger && AdressDataArray && AdressDataArray[jaeger] && AdressDataArray[jaeger].Text)
    ? AdressDataArray[jaeger].Text : '';
  var datumEl = document.getElementById('Datum-' + line);
  var ft = parseFloat(meta.Flugzeit);
  var buf = parseFloat(FluzeitStartUndLandung);
  if (isNaN(ft)) ft = 0;
  if (isNaN(buf)) buf = 0;

  rkrkSetLineField(line, 'Felder', meta.Text || '');
  rkrkSetLineField(line, 'GrundstückID', meta.GrundstueckID || '');
  rkrkSetLineField(line, 'Gde', meta.Gemeinde || '');
  rkrkSetLineField(line, 'JG', jg);
  rkrkSetLineField(line, 'Reg', regUp);
  rkrkSetLineField(line, 'Flaeche', meta.Flaeche || '');
  rkrkSetLineField(line, 'Einsatztag', datumEl ? datumEl.value : '');
  rkrkSetLineField(line, 'Jaeger', jaeger);
  rkrkSetLineField(line, 'Warnung', meta.Warnung || '');
  rkrkSetLineField(line, 'Kontaktnummer', kontakt);
  rkrkSetLineField(line, 'Landwirt', meta.Bauer || '');
  rkrkSetLineField(line, 'Flugzeit', ft + buf);
}

function UpdJaeger(val, line) {
  var txt = (val && AdressDataArray && AdressDataArray[val] && AdressDataArray[val].Text)
    ? AdressDataArray[val].Text : '';
  rkrkSetLineField(line, 'Kontaktnummer', txt);
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}
function UpdPilot(val, line) {
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}
function UpdFund(val, line) {
  var fundEl = document.getElementById('Fund-' + line);
  var fundeEl = document.getElementById('#Funde-' + line);
  if (fundEl && fundeEl) fundeEl.value = fundEl.value;
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}
function UpdReg(val, line) {
  var regUp = (val && RegDataArray && RegDataArray[val] && RegDataArray[val].RegUp)
    ? RegDataArray[val].RegUp : '';
  rkrkSetLineField(line, 'Reg', regUp);
}
function UpdDatum(val, line) {
  var datumEl = document.getElementById('Datum-' + line);
  rkrkSetLineField(line, 'Einsatztag', datumEl ? datumEl.value : '');
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}
function UpdSelDatum(val, line) {
  var selFrom = document.getElementById('SelFrom');
  var selTo = document.getElementById('SelTo');
  if (selFrom && selTo) selTo.value = selFrom.value;
}

function rkrkSetMetaReadonly(line, ro) {
  var metaIds = ['Flugzeit', 'Flaeche', 'JG', 'Gde'];
  for (var mi = 0; mi < metaIds.length; mi++) {
    var el = document.getElementById(metaIds[mi] + '-' + line);
    if (!el) continue;
    el.readOnly = !!ro;
    if (ro) el.setAttribute('readonly', 'readonly');
    else el.removeAttribute('readonly');
  }
  var sel = document.getElementById('Feld-' + line);
  var tr = sel ? sel.closest('tr') : null;
  if (tr) {
    if (ro) tr.classList.remove('rkrk-row-manual');
    else tr.classList.add('rkrk-row-manual');
  }
}
function rkrkLockFlightMetaFields() {
  var sels = document.querySelectorAll('select[id^="Feld-"]');
  for (var i = 0; i < sels.length; i++) {
    var m = String(sels[i].id).match(/^Feld-(\d+)$/);
    if (!m) continue;
    var isManual = String(sels[i].value) === ManualFieldLabel;
    rkrkSetMetaReadonly(m[1], !isManual);
  }
}
function UpdField(val, line) {
  var sel = document.getElementById('Feld-' + line);
  val = String(val != null ? val : (sel ? sel.value : '')).trim();
  var isManual = sel && String(sel.value) === ManualFieldLabel;

  if (!isManual) {
    if (val === '') {
      rkrkClearCatalogFields(line);
    } else {
      rkrkApplyCatalogField(val, line);
    }
    rkrkSetMetaReadonly(line, true);
  } else {
    if (val === '') {
      rkrkClearCatalogFields(line);
    } else {
      rkrkSetLineField(line, 'Felder', '');
      rkrkSetLineField(line, 'GrundstückID', '');
      rkrkSetLineField(line, 'Gde', '');
      rkrkSetLineField(line, 'JG', '');
      rkrkSetLineField(line, 'Reg', '');
      rkrkSetLineField(line, 'Flaeche', '');
      var datumElM = document.getElementById('Datum-' + line);
      rkrkSetLineField(line, 'Einsatztag', datumElM ? datumElM.value : '');
      rkrkSetLineField(line, 'Jaeger', '');
      rkrkSetLineField(line, 'Warnung', '');
      rkrkSetLineField(line, 'Kontaktnummer', '');
      rkrkSetLineField(line, 'Landwirt', '');
      rkrkSetLineField(line, 'Flugzeit', '');
    }
    rkrkSetMetaReadonly(line, false);
  }
  if (typeof rkrkRefreshFlightSummary === 'function') rkrkRefreshFlightSummary(line);
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', rkrkLockFlightMetaFields);
} else {
  rkrkLockFlightMetaFields();
}

</script>

</body>
</HTML>
