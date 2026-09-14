<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
/*
	https://tchr-frog.synology.me/rkropen/test/rkrk_fluege.php?MDB=M3T&EnableDelete=off&SelFrom=2024-04-11&SelTo=2024-04-18
	https://tchr-frog.synology.me/rkropen/test/rkrk_fluege.php?Free=FID000003+FID000001+FID000002&MDB=M3T&EnableDelete=off&EnableNeu=off&Modus=off&SelFrom=2024-04-11&SelTo=2024-04-18
		
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

	//check impropriate IP addresses accessing page
	CheckIPandExit();

	$pagetitle = "Flugbuchungen";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
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
		a.setAttribute('download', '');
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
/* Stage 1: Tablet landscape / small laptop (3 fields per row) */
@media (max-width: 1300px){
  .rkrk-flight-main tr.rkrk-mobile-header-row{
    display:none !important;
  }
  .rkrk-flight-main{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
  }
  .rkrk-flight-main tr{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:8px 10px;
    margin:0;
    padding:8px;
    border:1px solid #d9d9d9;
    border-radius:10px;
    background:#f3f5f8;
  }
  .rkrk-flight-main td{
    display:block;
    box-sizing:border-box;
    border:none;
    padding:2px;
  }
  .rkrk-flight-main td::before{
    content:attr(data-label);
    display:block;
    margin:0 0 3px 0;
    font-weight:600;
    color:#445;
    font-size:12px;
  }
  .rkrk-flight-main td input,
  .rkrk-flight-main td select,
  .rkrk-flight-main td textarea{
    width:100% !important;
    max-width:none !important;
    box-sizing:border-box;
    min-height:36px;
  }
  .rkrk-flight-main td input[type='image']{
    min-height:0 !important;
    width:auto !important;
  }
  .rkrk-flight-main td.rkrk-mobile-full,
  .rkrk-flight-main td[colspan]{
    grid-column:1 / -1;
  }
  .rkrk-flight-main td.rkrk-mobile-full::before{
    content:'';
    display:none;
  }
  .rkrk-flight-main td.rkrk-mobile-iconcell{
    display:flex;
    align-items:center;
    gap:6px;
  }
  .rkrk-flight-main td.rkrk-mobile-iconcell::before{
    content:'';
    display:none;
  }
  .rkrk-flight-main td.rkrk-mobile-hide{
    display:none !important;
  }
  .rkrk-flight-main td img,
  .rkrk-flight-main td input[type='image']{
    width:auto !important;
    max-width:none !important;
    object-fit:contain;
  }
}
/* Stage 2: Tablet portrait (2 fields per row) */
@media (max-width: 1024px){
  .rkrk-flight-main tr{
    grid-template-columns:repeat(2, minmax(0, 1fr));
  }
}
/* Stage 3: Mobile (1 field per row) */
@media (max-width: 700px){
  .rkrk-flight-main tr{
    grid-template-columns:1fr;
    gap:7px;
    padding:7px;
  }
  .rkrk-flight-main td::before{
    font-size:13px;
  }
  .rkrk-flight-main td input,
  .rkrk-flight-main td select,
  .rkrk-flight-main td textarea{
    min-height:40px;
    font-size:15px;
  }
  .rkrk-flight-main td input[type='image']{
    min-height:0 !important;
  }
}
</style>";
echo "<script>
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
    var t = (ths[h].textContent || '').trim();
    labels.push(t);
  }
  for (var r=0; r<rows.length; r++){
    if (rows[r] === headerRow) continue;
    if (rows[r].querySelectorAll('th').length > 0) continue;
    var tds = rows[r].querySelectorAll('td');
    for (var c=0; c<tds.length; c++){
      var td = tds[c];
      if (td.hasAttribute('colspan')) {
        td.classList.add('rkrk-mobile-full');
        td.setAttribute('data-label', '');
        continue;
      }
      var label = (labels[c] || '').trim();
      td.setAttribute('data-label', label);

      // Hide effectively empty/hidden cells in responsive card mode.
      var hasVisibleContent = (td.textContent || '').trim() !== '';
      var controls = td.querySelectorAll('input, select, textarea, button, img, a');
      for (var k = 0; k < controls.length; k++){
        var el = controls[k];
        if (el.type === 'hidden') continue;
        if (el.hasAttribute('hidden')) continue;
        hasVisibleContent = true;
        break;
      }
      if (!hasVisibleContent) {
        td.classList.add('rkrk-mobile-hide');
      }

      // Cells without header text are icon/action columns: keep compact, no label.
      if (label === '') {
        td.classList.add('rkrk-mobile-iconcell');
      }
    }
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
window.addEventListener('DOMContentLoaded', function(){
  rkrkApplyMobileFlightLabels();
  rkrkApplyDesktopTableScroll();
});
window.addEventListener('resize', rkrkApplyDesktopTableScroll);
</script>";

	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
	$FluegeFileSettings = rkrk_fluege_mission_file_settings();
	//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
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
	
	// create external selection list
	$SelectPilots[]= "";
	foreach ($OptionPilot as $key => $Pilot) {
		if ((strpos(" " . $Pilot["VisibleOn"], $WebFreigabe)!= false)) {
			$VisblePilots[$key] = $Pilot;
			$SelectPilots[]=$Pilot[Opt];
		}
	}

	//pre_R($VisblePilots);
	
	//Select Options
	$Pilots[]= "";
	foreach ($OptionPilot as $Pilot) {
		$Pilots[]=$Pilot[Opt];
	}
	$OptControlFlight = Array("", "Ja", "Nein");

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

	function rkrk_fluege_looks_corrupt_json($text) {
		$text = ltrim((string)$text);
		return ($text !== '' && ($text[0] === '{' || $text[0] === '[') && strpos($text, '"0"') !== false);
	}

	function rkrk_fluege_repair_display_text($text) {
		$text = trim((string)$text);
		if ($text === '' || !rkrk_fluege_looks_corrupt_json($text)) {
			return $text;
		}
		for ($i = 0; $i < 6; $i++) {
			if (!rkrk_fluege_looks_corrupt_json($text)) {
				return $text;
			}
			$decoded = json_decode($text, true);
			if (!is_array($decoded)) {
				return $text;
			}
			$next = '';
			foreach ($decoded as $part) {
				if (is_string($part) && trim($part) !== '') {
					$next = trim($part);
					break;
				}
			}
			if ($next === '') {
				if (preg_match('/(?:\\\\"|")0(?:\\\\"|")\s*:\s*(?:\\\\"|")([^"\\\\]+)/u', $text, $m)) {
					return trim($m[1]);
				}
				return $text;
			}
			$text = $next;
		}
		return $text;
	}

	function rkrk_fluege_scalar_text($value) {
		if (is_array($value)) {
			foreach ($value as $part) {
				$flat = rkrk_fluege_scalar_text($part);
				if ($flat !== '') {
					return $flat;
				}
			}
			return '';
		}
		$text = trim(rkrk_fluege_repair_display_text($value));
		if ($text === '{}' || $text === '[]') {
			return '';
		}
		return $text;
	}

	function rkrk_fluege_clean_felder_text($text) {
		$text = rkrk_fluege_scalar_text($text);
		$text = preg_replace('/\s*\{\}\s*/u', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text);
		return trim($text);
	}

	function rkrk_fluege_build_field_text($field) {
		if (!is_array($field)) {
			return rkrk_fluege_clean_felder_text($field);
		}
		$text = rkrk_fluege_scalar_text($field['Feldname'] ?? '');
		$flurname = rkrk_fluege_scalar_text($field['Flurname'] ?? '');
		if ($flurname !== '') {
			$text .= ($text !== '' ? ' ' : '') . $flurname;
		}
		$gstk = rkrk_fluege_scalar_text($field['GrundstueckID'] ?? '');
		if ($gstk !== '') {
			$text .= ($text !== '' ? ' ' : '') . $gstk;
		}
		return trim($text);
	}

	function rkrk_fluege_format_bauer_phone($phoneRaw) {
		$phoneRaw = trim((string)$phoneRaw);
		if ($phoneRaw === '') {
			return '';
		}
		$digits = preg_replace('/\D+/', '', $phoneRaw);
		if (strlen($digits) < 9) {
			return '';
		}
		if (strpos($digits, '41') === 0 && strlen($digits) > 2) {
			$digits = '0' . substr($digits, 2);
		}
		if (strlen($digits) === 9 && $digits[0] === '7') {
			$digits = '0' . $digits;
		}
		if (strlen($digits) < 10) {
			return '';
		}
		$display = preg_replace('/^\+41\s*/', '0', $phoneRaw);
		return trim($display);
	}

	function rkrk_fluege_extract_bauer_phone($line) {
		$line = trim((string)$line);
		if ($line === '' || stripos($line, 'T:') !== 0) {
			return '';
		}
		return rkrk_fluege_format_bauer_phone(trim(substr($line, 2)));
	}

	function rkrk_fluege_landwirt_is_invalid($text) {
		$text = trim((string)$text);
		if ($text === '') {
			return false;
		}
		if (preg_match('/^(A:|T:|M:|E:)/i', $text)) {
			return true;
		}
		if (preg_match('/^A:\s*[\+]?\s*$/u', $text)) {
			return true;
		}
		if (preg_match('/^[\+\s\d\(\)\/-]+$/u', $text) && !preg_match('/\d{9,}/', preg_replace('/\D+/', '', $text))) {
			return true;
		}
		return false;
	}

	function rkrk_fluege_clean_landwirt_text($text) {
		$text = rkrk_fluege_scalar_text($text);
		if ($text === '') {
			return '';
		}
		if (strpos($text, "\n") !== false || preg_match('/^(A:|T:|M:|E:)/im', $text)) {
			return rkrk_fluege_build_bauer_text($text);
		}
		if (rkrk_fluege_landwirt_is_invalid($text)) {
			return '';
		}
		return $text;
	}

	function rkrk_fluege_build_bauer_text($bauerRaw) {
		$bauerRaw = rkrk_fluege_scalar_text($bauerRaw);
		if ($bauerRaw === '') {
			return '';
		}
		$lines = preg_split('/\R/u', $bauerRaw);
		$name = '';
		$phone = '';
		foreach ($lines as $line) {
			$line = trim((string)$line);
			if ($line === '') {
				continue;
			}
			if (stripos($line, 'T:') === 0) {
				if ($phone === '') {
					$phone = rkrk_fluege_extract_bauer_phone($line);
				}
				continue;
			}
			if (preg_match('/^(A:|M:|E:)/i', $line)) {
				continue;
			}
			if ($name === '') {
				$name = $line;
			}
		}
		$name = trim($name);
		if ($name === '' || preg_match('/^(A:|T:|M:|E:)/i', $name)) {
			return '';
		}
		return $phone !== '' ? ($name . ' ' . $phone) : $name;
	}

	function rkrk_fluege_lookup_field_meta($fieldDataArray, $fid) {
		$fid = trim((string)$fid);
		if ($fid === '' || !is_array($fieldDataArray)) {
			return null;
		}
		if (isset($fieldDataArray[$fid]) && is_array($fieldDataArray[$fid])) {
			return $fieldDataArray[$fid];
		}
		$fidUp = strtoupper($fid);
		foreach ($fieldDataArray as $key => $meta) {
			if (strtoupper((string)$key) === $fidUp && is_array($meta)) {
				return $meta;
			}
		}
		return null;
	}

	function rkrk_fluege_sync_row_from_catalog(&$row, $fieldDataArray) {
		global $ManualFieldLabel;

		$row['Gde'] = rkrk_fluege_scalar_text($row['Gde'] ?? '');
		$row['Landwirt'] = rkrk_fluege_clean_landwirt_text($row['Landwirt'] ?? '');
		$row['Felder'] = rkrk_fluege_clean_felder_text($row['Felder'] ?? '');
		$row['Kontaktnummer'] = rkrk_fluege_scalar_text($row['Kontaktnummer'] ?? '');
		$row['Bemerkung'] = rkrk_fluege_scalar_text($row['Bemerkung'] ?? '');

		$fid = trim((string)($row['Feld'] ?? ''));
		if ($fid === '' || $fid === $ManualFieldLabel) {
			return;
		}
		$meta = rkrk_fluege_lookup_field_meta($fieldDataArray, $fid);
		if ($meta === null) {
			return;
		}
		$catFelder = rkrk_fluege_clean_felder_text($meta['Text'] ?? '');
		if ($row['Gde'] === '' || rkrk_fluege_looks_corrupt_json($row['Gde'])) {
			$row['Gde'] = rkrk_fluege_scalar_text($meta['Gemeinde'] ?? '');
		}
		$catLandwirt = rkrk_fluege_clean_landwirt_text($meta['Bauer'] ?? '');
		if ($row['Landwirt'] === '' || rkrk_fluege_looks_corrupt_json($row['Landwirt']) || rkrk_fluege_landwirt_is_invalid($row['Landwirt'])) {
			if ($catLandwirt !== '') {
				$row['Landwirt'] = $catLandwirt;
			}
		}
		if ($catFelder !== '' && (
			$row['Felder'] === ''
			|| rkrk_fluege_looks_corrupt_json($row['Felder'])
			|| strpos((string)$row['Felder'], '{}') !== false
			|| $row['Felder'] !== $catFelder
		)) {
			$row['Felder'] = $catFelder;
		}
		if (trim((string)($row['GrundstückID'] ?? '')) === '') {
			$row['GrundstückID'] = rkrk_fluege_scalar_text($meta['GrundstueckID'] ?? '');
		}
		if (trim((string)($row['JG'] ?? '')) === '') {
			$row['JG'] = rkrk_fluege_scalar_text($meta['JG'] ?? '');
		}
		if (trim((string)($row['Flaeche'] ?? '')) === '') {
			$row['Flaeche'] = rkrk_fluege_scalar_text($meta['Flaeche'] ?? '');
		}
		if (trim((string)($row['Warnung'] ?? '')) === '') {
			$row['Warnung'] = rkrk_fluege_scalar_text($meta['Warnung'] ?? '');
		}
		if (trim((string)($row['Jaeger'] ?? '')) === '') {
			$row['Jaeger'] = rkrk_fluege_scalar_text($meta['Jaeger'] ?? '');
		}
	}

	function rkrk_fluege_repair_all_flight_data(&$data, $fieldDataArray) {
		if (!is_array($data)) {
			return;
		}
		foreach ($data as $flugId => $row) {
			if (!is_array($row)) {
				continue;
			}
			rkrk_fluege_sync_row_from_catalog($data[$flugId], $fieldDataArray);
		}
	}

	function GetNewLine() {
		global $MaxEntry, $EnableDelete, $debugmode, $debugfieldshow, $ActDate, $MinDate, $MaxDate, $PrevPilot ,$Pilots,$FeldListe,$OptControlFlight,$OptionJaeger, $ButtonHeight ,$IconLoc, $i, $FromDate,$ToDate, $MissionSelectionParameter, $EnableDeleteSet, $EnableNeuSet, $EnableConSet, $EnableCon, $MissionDroneTypes, $RKRKMissionSelected;
		while ($i < $MaxEntry) {
			if (true) {
			// Configsform empty
			// Existing Data
				echo '<tr>' .chr(10);
				if ($EnableDelete) {
					echo "<td></td>" .chr(10);
				}
				if ($debugmode) {
					echo '<td>' .chr(10);
					echo $i;
					echo '</td>' .chr(10);
				}
				echo '<td>' .chr(10);
				//SAVE
				echo "<input type='image' value='SaveLine' src='" . $IconLoc . "/save_line.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" .$EnableDeleteSet . "&" . $EnableNeuSet . "&" . $EnableConSet ."&SaveLine=".$i."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Zeile speichern'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo DateJSFunctionField("Datum-" . $i, $ActDate, $MinDate , $MaxDate ,"UpdDatum", "this.value", $i, 4 );
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo InputTimeField("Zeit-" . $i, "", 3 , "right");
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				//Flugwarnung Nachtflug-/Sperrzonen
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo SelectJSFunctionField("Pilot-" . $i, $PrevPilot ,$Pilots , "UpdPilot", "this.value", $i );
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo SelectJSFunctionField("Feld-" . $i, "" ,$FeldListe , "UpdField", "this.value", $i );
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo SelectField("Kontrollflug-" . $i, "" ,$OptControlFlight );
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				// geomap
				echo "</td>" .chr(10);
				foreach (rkrk_fluege_mission_file_settings() as $fileType) {
					echo '<td></td>' .chr(10);
				}
				if (GetAccess("Convert") and $EnableCon) {
					foreach (explode(",", (string)($MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"] ?? "")) as $TargetMission) {
						if ($TargetMission !== "") {
							echo '<td></td>' .chr(10);
							echo '<td></td>' .chr(10);
						}
					}
				}
				echo '<td>' .chr(10);
				echo InputField("Flugzeit-" . $i, "", 2, "right", "", "", "readonly='readonly'");
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Flaeche'];
				echo InputField("Flaeche-" . $i, "", 3, "right", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['JG'];
				echo InputJSFunctionField("JG-" . $i, "", "UpdReg", "this.value",  $i, 2, "right", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Gde'];
				echo InputField("Gde-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['GrundstückID'];
				echo InputField("GrundstückID-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo SelectField("Geflogen-" . $i, "" ,$OptControlFlight );
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputJSFunctionField("Fund-" . $i, "", "UpdFund", "this.value", $i, 4 );
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputField("UAV#-" . $i, "", 7);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputField("Felder-" . $i, "" , 30);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo SelectJSFunctionField("Jaeger-" . $i, "" ,$OptionJaeger , "UpdJaeger", "this.value" , $i);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputField("Kontaktnummer-" . $i, "", 28);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputField("Landwirt-" . $i, "", 28);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo InputField("Bemerkung-" . $i, "", 30);
				echo "</td>" .chr(10);
				//DEBUG
				//------------------------
				echo '<td>' .chr(10);
				//$dataline['AnzFelder'];
				echo InputField("AnzFelder-" . $i, "", 2, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['#Funde'];
				echo InputField("#Funde-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Einsatztag'];
				echo InputField("Einsatztag-" . $i, "", 6, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Warnung'];
				echo InputField("Warnung-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Reg'];
				echo InputField("Reg-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['FlugID'];
				echo InputField("FlugID-" . $i, "", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				//$dataline['Status'];
				echo InputField("Status-" . $i, "locked", 5, "left", "", "", "", $debugfieldshow);
				echo "</td>" .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
	}

	function ChecklistPilotName($pilotCode) {
		$map = array(
			"TC" => "Thomy",
			"CH" => "Chris",
			"AC" => "Armon",
			"NC" => "Nando",
			"MF" => "Marina",
			"LF" => "Lars",
			"VP" => "Vesa",
			"MB" => "Marc",
		);
		$code = strtoupper(trim((string)$pilotCode));
		return $map[$code] ?? $pilotCode;
	}

	function BuildChecklistLink($params) {
		// Template pfadfrei bestimmen (kein fester Dateisystempfad):
		// 1) optional per URL, 2) bewährtes Standard-Template.
		$template = trim((string)($_GET["ChecklistTemplate"] ?? ""));
		if ($template === "") {
			$template = "rkrk_rehkitzsuche.json";
		}

		$base = "rkrk_checklist.php";
		$q = array(
			"mode" => "form",
			"file" => basename($template),
		);
		foreach ($params as $k => $v) {
			if ($v === null) continue;
			$val = trim((string)$v);
			if ($val === "") continue;
			$q[$k] = $val;
		}
		return $base . "?" . http_build_query($q);
	}

	function NormalizeChecklistFlugort($ort, $jg = '') {
		global $RegionSettings;

		$jgKey = trim((string)$jg);
		if ($jgKey !== '' && isset($RegionSettings[$jgKey]["Text"])) {
			$txt = trim((string)$RegionSettings[$jgKey]["Text"]);
			// remove leading number/code prefix like "25-" / "25_" / "25 "
			$txt = preg_replace('/^\s*\d+\s*[-_ ]\s*/u', '', $txt);
			// normalize underscores in region names
			$txt = str_replace('_', ' ', $txt);
			$txt = trim(preg_replace('/\s+/u', ' ', $txt));
			if ($txt !== '') return $txt;
		}

		$val = trim((string)$ort);
		$val = str_replace('_', ' ', $val);
		return trim(preg_replace('/\s+/u', ' ', $val));
	}

	function CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, $xmlLabelFilter = null) {
		global $FlyFileSetting;
		$links = array();
		foreach ($data as $dl) {
			if (($dl['Pilot'] ?? '') !== $pilotCode) continue;
			if (($dl['Datum'] ?? '') !== $date) continue;
			$fieldId = trim((string)($dl['Feld'] ?? ''));
			if ($fieldId === '' || strpos($fieldId, '-') === false) continue;
			$jg = explode('-', $fieldId)[0];
			if (!isset($feldkatalog[$jg][$fieldId])) continue;
			foreach ($FlyFileSetting as $fileType) {
				$label = (string)($fileType["XMLLabel"] ?? '');
				if ($label !== "JasonFile" && $label !== "KMZFile") continue;
				if ($xmlLabelFilter !== null && $label !== $xmlLabelFilter) continue;
				$linkData = explode(",", (string)($feldkatalog[$jg][$fieldId][$label] ?? ''));
				foreach ($linkData as $link) {
					$link = trim((string)$link);
					if ($link === '') continue;
					$links[$link] = $link;
				}
			}
		}
		return array_values($links);
	}

	function BuildPilotDownloadJsonOnClick($data, $pilotCode, $date, $feldkatalog, $baseMissionPath) {
		$links = CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, "JasonFile");
		$urls = array();
		foreach ($links as $rel) {
			$urls[] = $baseMissionPath . $rel;
		}
		$jsonUrls = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return "return StartPilotDownloads(" . $jsonUrls . ");";
	}
	function BuildPilotDownloadKMZOnClick($data, $pilotCode, $date, $feldkatalog, $baseMissionPath) {
		$links = CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, "KMZFile");
		$urls = array();
		foreach ($links as $rel) {
			$urls[] = $baseMissionPath . $rel;
		}
		$jsonUrls = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return "return StartPilotDownloads(" . $jsonUrls . ");";
	}


	// ===========================================
	// Paramters
	// ===========================================

if (isset($_GET["EnableDelete"])) {
   if (strtolower($_GET["EnableDelete"])=="on") {
		//echo "EnableDelete=on";
 		$EnableDelete= true;
	} else {
		//echo "EnableDelete=of";
		$EnableDelete= false;
	}
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

if (isset($_GET["EnableNeu"])) {
   if ($_GET["EnableNeu"]=="on") {
		//echo "EnableNeu=on";
 		$EnableNeu= true;
	} else {
		//echo "EnableNeu=off";
		$EnableNeu= false;
	}
}
if (isset($_GET["EnableCon"])) {
   if ($_GET["EnableCon"]=="on") {
		//echo "EnableCon=on";
 		$EnableCon= true;
	} else {
		//echo "EnableCon=off";
		$EnableCon= false;
	}
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

if (isset($_GET["Modus"])) {
	if ($_GET["Modus"]=="on") {
		//echo "Modus=on";
		$Modus= true;
	} else {
		//echo "Modus=off";
		$Modus= false;
	}
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
			//echo "MaxFlugID " . $MaxFlugID . "<br>";
			//echo "Flugbuffer: " . $FluzeitStartUndLandung . " - " . $Feldkatalog[$JG][$AddFlugID]["Flugzeit"] . "<br>";
			$JG=substr($AddFlugID,0,strpos($AddFlugID,"-"));
			// add new line based on 
			// https://tchr-frog.synology.me:5682/rkropen/test/rkrk_fluege.php?add=Y&AddFlugID=21-001&AddFlugToDate=2022-05-03&MDB=M3T
			$MaxFlugID++;
			$FlugID = "FID" .sprintf("%06d", $MaxFlugID);
			$data[$FlugID]["FlugID"] = $FlugID;
			$data[$FlugID]["Datum"] = $AddFlugToDate;
			$data[$FlugID]["Zeit"] = "";
			$data[$FlugID]["Pilot"] = $PilotDataArray[$JG]['Pilot'];
			$data[$FlugID]["Feld"] = $AddFlugID;
			$data[$FlugID]["Kontrollflug"] = $_POST["Kontrollflug-" . $i];
			$data[$FlugID]["Geflogen"] = "";
			$data[$FlugID]["Fund"] = "";
			$data[$FlugID]["UAV#"] = "";
			$data[$FlugID]["Felder"] = rkrk_fluege_build_field_text($Feldkatalog[$JG][$AddFlugID] ?? array());
			$data[$FlugID]["Jaeger"] = $Feldkatalog[$JG][$AddFlugID]["Jaeger"];
			$data[$FlugID]["Kontaktnummer"] = $AdressDataArray[$Feldkatalog[$JG][$AddFlugID]["Jaeger"]]['Text'];
			$data[$FlugID]["Landwirt"] = $FieldDataArray[$AddFlugID]["Bauer"];
			$data[$FlugID]["Bemerkung"] = "";
			//if ($dataline['Flugzeit']>$FlugzeitTreshold) {
				// Zeit in Sekunden
			//	$data[$FlugID]["Flugzeit"] = $Feldkatalog[$JG][$AddFlugID]["Flugzeit"];
			//} else {
			// Zeit in Minuten
			//echo "Flugzeit: " . $JG . " / " .$AddFlugID . " / " .$FluzeitStartUndLandung+$Feldkatalog[$JG][$AddFlugID]["Flugzeit"] . "<br>";
			$data[$FlugID]["Flugzeit"] = $FluzeitStartUndLandung+$Feldkatalog[$JG][$AddFlugID]["Flugzeit"];
			//}
			$data[$FlugID]["AnzFelder"] = 1;
			$data[$FlugID]["#Funde"] = "";
			$data[$FlugID]["Einsatztag"] = $AddFlugToDate;
			$data[$FlugID]["GrundstückID"] = $Feldkatalog[$JG][$AddFlugID]["GrundstueckID"];
			$data[$FlugID]["Gde"] = $Feldkatalog[$JG][$AddFlugID]["Gemeinde"];
			$data[$FlugID]["JG"] = $Feldkatalog[$JG][$AddFlugID]["JG"];
			$data[$FlugID]["Warnung"] = $Feldkatalog[$JG][$AddFlugID]["Warnung"];
			$data[$FlugID]["Reg"] = $RegDataArray[$Feldkatalog[$JG][$AddFlugID]["JG"]]['RegUp'];
			$data[$FlugID]["Flaeche"] = $Feldkatalog[$JG][$AddFlugID]["Flaeche"];
			$data[$FlugID]["Status"] = "locked";
			//echo "added Flug " . $AddFlugID . " to date " . $AddFlugToDate . "<br>";
			//pre_r($data[$FlugID]);
		}
	}

/// POST
	//echo "SaveLine: " . $SaveLine . "<br>";
	if (
		($_SERVER["REQUEST_METHOD"] == "POST")
		 and $dosafe) {
					
		//echo "MaxFlugID: " . $MaxFlugID . "<br>";
		for ($i = 1; $i <= $_POST["NumEntries"]; $i++) {
		//echo $i . " " . $_POST["Initialen-" . $i] . " -  " . $_POST["FlugID-" . $i] . "<br>";
		//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
			if (
				($_POST["FlugID-" . $i]!="") &&
				($_POST["Feld-" . $i]!="")
				) {
				$FlugID = $_POST["FlugID-" . $i];
			} else {
				"FlugID: " . $_POST["FlugID-" . $i] . "Feld-". $i . ": " . $_POST["Feld-" . $i] . "UAV#-". $i . ": " . $_POST["UAV#-" . $i]. "Bemerkung-". $i . ": " . $_POST["Bemerkung-" . $i]. "<br>";
				$MaxFlugID++;
				$FlugID = "FID".sprintf("%06d", $MaxFlugID);
				//echo "NewFlugID: " . $FlugID . "<br>";
			}
			//echo "FlugID: " . $FlugID . " Saveline " . $SaveLine." line " . $i."<br>";
			if (
				 (
				   ($_POST["Feld-" . $i] <>"") or 
			       ($_POST["UAV#-" . $i] <>"") or 
				   ($_POST["Bemerkung-" . $i] <>"")
				  ) and (
				   ($SaveLine=="")
				   or (strpos(" ".$SaveLine, "FID") and ($SaveLine==$FlugID))
				   or ((strval($SaveLine)==strval($i)))
				  )
				) { 
				
				//echo "Speichern FlugID: " . $FlugID . "Zeit: " . $_POST["Zeit-" . $i] ."<br>";
				$dosafe=true;
				$data[$FlugID]["FlugID"] = $FlugID;
				$data[$FlugID]["Datum"] = $_POST["Datum-" . $i];
				$data[$FlugID]["Zeit"] = $_POST["Zeit-" . $i];
				$data[$FlugID]["Pilot"] = $_POST["Pilot-" . $i];
				$data[$FlugID]["Feld"] = str_replace($searchSD, $replaceSD, $_POST["Feld-" . $i]);
				//$data[$FlugID]["Feld"] = cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Feld-" . $i]));
				$data[$FlugID]["Kontrollflug"] = $_POST["Kontrollflug-" . $i];
				$data[$FlugID]["Geflogen"] = $_POST["Geflogen-" . $i];
				$data[$FlugID]["Fund"] = $_POST["Fund-" . $i];
				$data[$FlugID]["UAV#"] = $_POST["UAV#-" . $i];
				$data[$FlugID]["Felder"] = rkrk_fluege_clean_felder_text($_POST["Felder-" . $i]);
				//if ($_POST["Jaeger-" . $i]!="") 
					$data[$FlugID]["Jaeger"] = $_POST["Jaeger-" . $i];
				//else
				//	$data[$FlugID]["Jaeger"] = $Feldkatalog[$_POST["JG-" . $i]][$_POST["Feld-" . $i]]["JG"];
					
				//$data[$FlugID]["Kontaktnummer"] = str_replace($searchSDZ, $replaceSDZ, $_POST["Kontaktnummer-" . $i]);
				//if ($_POST["Kontaktnummer-" . $i]!="") 
					$data[$FlugID]["Kontaktnummer"] = cleanString(str_replace($searchSD, $replaceSD, $_POST["Kontaktnummer-" . $i]));
				//else
				//	$data[$FlugID]["Kontaktnummer"] = cleanString($AdressDataArray[$Feldkatalog[$_POST["JG-" . $i]][$_POST["Feld-" . $i]]["Jaeger"]]['Text']);
				//$data[$FlugID]["Landwirt"] = str_replace($searchSDZ, $replaceSDZ, $_POST["Landwirt-" . $i]);
				//if ($_POST["Landwirt-" . $i]!="") 
					$data[$FlugID]["Landwirt"] = rkrk_fluege_clean_landwirt_text(cleanString(str_replace($searchSD, $replaceSD, $_POST["Landwirt-" . $i])));
				//else
				//	$data[$FlugID]["Landwirt"] = $Feldkatalog[$_POST["JG-" . $i]][$_POST["Feld-" . $i]]["Bauer"];
				//$data[$FlugID]["Bemerkung"] = str_replace($searchSDZ, $replaceSDZ, $_POST["Bemerkung-" . $i]);
				$data[$FlugID]["Bemerkung"] = cleanString(str_replace($searchSD, $replaceSD, $_POST["Bemerkung-" . $i]));
				//if ($_POST["Flugzeit-" . $i]!="")
					//echo "Post Flugzeit: " . $i . "/ " . round($_POST["Flugzeit-" . $i],1) . "<br>";
					$data[$FlugID]["Flugzeit"] = round($_POST["Flugzeit-" . $i],1);
				//else
				//	$data[$FlugID]["Flugzeit"] = $FluzeitStartUndLandung+$Feldkatalog[$_POST["JG-" . $i]][$_POST["Feld-" . $i]]["Flugzeit"];
				$data[$FlugID]["AnzFelder"] = $_POST["AnzFelder-" . $i];
				$data[$FlugID]["#Funde"] = $_POST["#Funde-" . $i];
				$data[$FlugID]["Einsatztag"] = $_POST["Einsatztag-" . $i];
				$data[$FlugID]["GrundstückID"] = $_POST["GrundstückID-" . $i];
				$data[$FlugID]["Gde"] = rkrk_fluege_scalar_text($_POST["Gde-" . $i]);
				$data[$FlugID]["JG"] = $_POST["JG-" . $i];
				$data[$FlugID]["Warnung"] = $_POST["Warnung-" . $i];
				$data[$FlugID]["Reg"] = $_POST["Reg-" . $i];
				$data[$FlugID]["Flaeche"] = $_POST["Flaeche-" . $i];
				$data[$FlugID]["Status"] = $_POST["Status-" . $i];
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
		$filecontent = str_replace(";",$csv_deliminator ,'FlugID;Datum;Zeit;Pilot;Feld;Kontrollflug;Geflogen;Fund;UAV#;Felder;Jaeger;Kontaktnummer;Landwirt;Bemerkung;Flugzeit;AnzFelder;#Funde;Einsatztag;GrundstückID;Gde;JG;Warnung;Reg;Flaeche;Status') . chr(13) . chr(10);
		foreach ($data as $entry) {
		//	echo $i . " " . $_POST["Initialen-" . $i];
		//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	
			IF ($entry[FlugID]!="") {
			
				//$rowcontent = chr(34);
				$rowcontent = "";
				$FlugID = $entry[FlugID]; 
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
				$rowcontent .= $Feldkatalog[$data[$FlugID]["JG"]][$data[$FlugID]["Feld"]]["Warnung"] . $csv_deliminator;
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
			file_put_contents($df, "");
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
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "120%";
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

//removes what does not belong to tick list
$RegList = array_diff($RegionSettings, array('NO','ALL'));
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
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Edit Fluege ",$FeldID, $RKRKMissionSelected,"");
		
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
	if (GetAccess("TestOnly") or ($PilotenWahl != "")) {
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Alle Flüge speichern", false);
	} else {
		echo "<font color='red'> <i>Speichern nur bei Auswahl eines Piloten möglich</i></font>";
	}	
	echo "<br>" .chr(10);
	//form to change setting
echo "<div class='rkrk-flight-table-wrap'>" . chr(10);
echo "<table class='rkrk-flight-main'>" .chr(10);
	echo "<tr >" .chr(10);
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
	echo "<th></th>" .chr(10);
	foreach ($FluegeFileSettings as $FileKey => $FileType) {
		echo "<th></th>" .chr(10);
	}
	if (GetAccess("Convert") and  $EnableCon) {
		//Konversion
		foreach(explode(",",$MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"]) as $TargetMission) {
			if ($TargetMission!="") {
				echo "<th></th>" .chr(10);
				echo "<th></th>" .chr(10);
			}
		}
	}

	echo "<th>FZeit min</th>" .chr(10);
	//Manuelle Flug-Felder
	echo "<th><span " . $manuallabelshow . " id='L_Flaeche'>Fläche</span></th>" .chr(10);
	echo "<th><span " . $manuallabelshow . " id='L_JG'><label title='Jagdgebiet'>JG</label></span></th>" .chr(10);
	echo "<th><span " . $manuallabelshow . " id='L_Gde'><label title='Gemeinde'>Gde</label></span></th>" .chr(10);
	echo "<th><span " . $manuallabelshow . " id='L_GrundstückID'><label title='GrundstückID'>GstkID</label></span></th>" .chr(10);
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
	echo "<tr><td colspan='30'><i>Buchungen:</i><td></tr>". chr(10);
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
				echo "<tr><td colspan='14'>";
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
			echo '<tr>' .chr(10);
			if ($EnableDelete) {
				echo '<td>' .chr(10);
				// delete
				if (GetAccess("DeleteData")) {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?DelFlugID=" . $dataline["FlugID"] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&" . $EnableConSet . "&" .$EnableNeuSet ."&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Flug " . $dataline["FlugID"] . " löschen'>" .chr(10);
				} else {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?Line=" . $i . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&" . $EnableConSet . "&" .$EnableNeuSet . "' height='" . $ButtonHeightSmall . "'  title='Linie " . $i . " löschen'>" .chr(10);
				}
			echo '</td>' .chr(10);
			}
			if ($debugmode) {
				echo '<td>' .chr(10);
				echo $i;
				echo '</td>' .chr(10);
			}	
			echo '<td>' .chr(10);
			//SAVE
			echo "<input type='image' value='SaveLine' src='" . $IconLoc . "/save_line.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter  . "&" . $EnableDeleteSet . "&" . $EnableConSet . "&" .$EnableNeuSet . "&SaveLine=".$dataline["FlugID"]."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Flug " .$dataline["FlugID"]. " speichern'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
			echo '</td>' .chr(10);
			echo '<td>' .chr(10);
			//echo $dataline['Datum'] . " ";
			echo DateJSFunctionField("Datum-" . $i, $dataline['Datum'], $MinDate , $MaxDate ,"UpdDatum", "this.value", $i, 4 );
			echo '</td>' .chr(10);
			echo '<td>' .chr(10);
			//echo $dataline['Zeit'] . " ";
			echo InputTimeField("Zeit-" . $i, $dataline['Zeit'], 2 , "left");
			echo '</td>' .chr(10);
			echo '<td>' .chr(10);
			//Flugwarnung Nacht-/Sperrzonen
				$Warning = $Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["Warnung"];
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
			echo '<td>' .chr(10);
			//echo SelectField("Pilot-" . $i, strval($dataline['Pilot']) ,$Pilots );
			echo SelectJSFunctionField("Pilot-" . $i, strval($dataline['Pilot']) ,$Pilots , "UpdPilot", "this.value", $i );
			echo '</td>' .chr(10);
			echo '<td>' .chr(10);
			//echo SelectField("Feld-" . $i, $dataline['Feld'] ,$FeldListe );
			echo SelectJSFunctionField("Feld-" . $i, $dataline['Feld'] ,$FeldListe , "UpdField", "this.value", $i );
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo SelectField("Kontrollflug-" . $i, $dataline['Kontrollflug'] ,$OptControlFlight );
			echo "</td>" .chr(10);
			
			// Download Litchi
			echo "<td>" .chr(10);
			if (GetAccess("GoogleMapLocation")){
				//Zeig Parkplatz an falls definiert, ansonsten den Startpunkt)
				if ($Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["Grundstueckdaten"]["Parkplatz"]!="") {
					$Location = explode("," ,$Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["Grundstueckdaten"]["Parkplatz"]);

				} else {
					$Location = explode("," ,$Feldkatalog[explode("-",$dataline['Feld'])[0]][$dataline['Feld']]["Grundstueckdaten"]["Startplatz"]);
				}
					
				$StartLat = $Location[0];
				$StartLon = $Location[1];
				
				if ($StartLat != "" && $StartLon !='') {
					echo "<!--startpunkt--><a href='https://maps.google.ch/?q=". trim($StartLon) . " ," . trim($StartLat) . "&t=k'><img src='" . $IconLoc . "\startpnt.png' height='" . $ButtonHeight ."' title='Startpunkt File " . $dataline['Feld'] . " auf GoogleMap'></a> " . chr(10);
				}
			}
			echo '</td>' .chr(10);
			$JsonFileList=Array();
			$KMZFileList=Array();
			foreach ($FluegeFileSettings as $FileKey => $FileType) {
				echo "<td>" .chr(10);
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
						echo "<a href='" . htmlspecialchars($RKRKLocalLoactionMissionFiles . $Link, ENT_QUOTES, 'UTF-8') . "' download><img src='" . htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') . "' height='" . (int)$ButtonHeight . "' title='" . htmlspecialchars($FileType["Text"] . " " . $dataline['Feld'] . " - " . $Link, ENT_QUOTES, 'UTF-8') . "' ></a> <wbr>" . chr(10);
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
						echo "<td>" .chr(10);
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
						echo "<td>" .chr(10);
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
			echo '<td>' .chr(10);
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
			echo '<td>' .chr(10);
			//echo InputField("Flaeche-" . $i, $dataline['Flaeche'], 5);
			echo InputField("Flaeche-" . $i, $dataline['Flaeche'], 3, "right", "", "", "", $ManualFieldshow);
			//$dataline['Flaeche'];
			//echo InputField("Flaeche-". $i, $dataline['Flaeche'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//echo InputField("JG-" . $i, $dataline['JG'], 5);
			echo InputJSFunctionField("JG-" . $i, $dataline['JG'], "UpdReg", "this.value", $i, 3, "right", "", "", "", $ManualFieldshow);
			
			//$dataline['JG'];
			//echo InputField("JG-". $i, $dataline['JG'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//echo InputField("Gde-" . $i, $dataline['Gde'], 5);
			echo InputField("Gde-" . $i, $dataline['Gde'], 5, "left", "", "", "", $ManualFieldshow);
			//$dataline['Gde'];
			//echo InputField("Gde-". $i, $dataline['Gde'], "left", "hidden");
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//$dataline['GrundstückID'];
			echo InputField("GrundstückID-" . $i, $dataline['GrundstückID'], 5, "left", "", "", "", $ManualFieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo SelectField("Geflogen-" . $i, $dataline['Geflogen'] ,$OptControlFlight );
			echo '<td>' .chr(10);
			//echo InputField("Fund-" . $i, $dataline['Fund'], 2);
			echo InputJSFunctionField("Fund-" . $i, $dataline['Fund'], "UpdFund", "this.value", $i, 4 );
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("UAV#-" . $i, $dataline['UAV#'], 10);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Felder-" . $i, $dataline['Felder'], 30);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo SelectJSFunctionField("Jaeger-" . $i, $dataline['Jaeger'] ,$OptionJaeger , "UpdJaeger", "this.value" , $i);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Kontaktnummer-" . $i, $dataline['Kontaktnummer'], 28);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Landwirt-" . $i, $dataline['Landwirt'], 28);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Bemerkung-" . $i, $dataline['Bemerkung'], 30);
			//echo InputField("Bemerkung-" . $i, $dataline['Bemerkung'], 30, "left", "", "", "", "hidden");
			//$FieldName, $Value ,$Size=10, $align="left", $type="", $class="" , $action="" , $show=""
			echo "</td>" .chr(10);
			//DEBUG Felder
			//------------------------
			echo '<td>' .chr(10);
			//$dataline['AnzFelder'];
			echo InputField("AnzFelder-" . $i, $dataline['AnzFelder'], 2, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//$dataline['#Funde'];
			echo InputField("#Funde-" . $i, $dataline['#Funde'], 5, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//$dataline['Einsatztag'];
			echo InputField("Einsatztag-" . $i, $dataline['Einsatztag'], 6, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//$dataline['Warnung'];
			echo InputField("Warnung-" . $i, $dataline['Warnung'], 8, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//$dataline['Reg'];
			echo InputField("Reg-" . $i, $dataline['Reg'], 5, "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			//echo InputField("FlugID-". $i, $dataline['FlugID'], "left", "hidden");
			echo InputField("FlugID-". $i, $dataline['FlugID'], 5 , "left", "", "", "", $debugfieldshow);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
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
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Alle Flüge speichern", false,);
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
}
function UpdPilot(val, line) {
  //alert("Pilot hat geändert auf: " + val);
}
function UpdFund(val, line) {
  var fundEl = document.getElementById('Fund-' + line);
  var fundeEl = document.getElementById('#Funde-' + line);
  if (fundEl && fundeEl) fundeEl.value = fundEl.value;
}
function UpdReg(val, line) {
  var regUp = (val && RegDataArray && RegDataArray[val] && RegDataArray[val].RegUp)
    ? RegDataArray[val].RegUp : '';
  rkrkSetLineField(line, 'Reg', regUp);
}
function UpdDatum(val, line) {
  var datumEl = document.getElementById('Datum-' + line);
  rkrkSetLineField(line, 'Einsatztag', datumEl ? datumEl.value : '');
}
function UpdSelDatum(val, line) {
  var selFrom = document.getElementById('SelFrom');
  var selTo = document.getElementById('SelTo');
  if (selFrom && selTo) selTo.value = selFrom.value;
}

function UpdField(val, line) {
  var sel = document.getElementById('Feld-' + line);
  val = String(val != null ? val : (sel ? sel.value : '')).trim();
  var flugzeitEl = document.getElementById('Flugzeit-' + line);
  var isManual = sel && String(sel.value) === ManualFieldLabel;

  if (!isManual) {
    if (val === '') {
      rkrkClearCatalogFields(line);
    } else {
      rkrkApplyCatalogField(val, line);
    }
    if (flugzeitEl) flugzeitEl.readOnly = true;
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
    if (flugzeitEl) flugzeitEl.readOnly = false;
  }
}

</script>

</body>
</HTML>
