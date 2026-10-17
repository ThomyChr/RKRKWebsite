<!--
  Funktion zum Anzeigen der Felddaten und identifizieren von neuen Files im Flugkatalog
  
--->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_jsonfiles.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_xmlfiles.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_adr_ui.php');
  include_once('rkrk_access_control.php'); 

	$pagetitle = "Flugkatalog";
	headerpage($pagetitle, false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
echo "<link rel='stylesheet' href='scripts/rkrk_adr_grid.css?v=20261012col1'>";
echo "<script src='scripts/rkrk_adr_grid.js?v=20261012rows1'></script>";
echo "<style>
.rkrk-card-table {
  width: 100%;
  background: #fff;
}
.rkrk-card-table td,
.rkrk-card-table th {
  padding: 1px 3px;
  line-height: 1.15;
}
.rkrk-card-table tbody tr:nth-child(odd) {
  background: #f6f7f9;
}
.rkrk-card-table tbody tr:nth-child(even) {
  background: #eceff3;
}
@media (min-width: 1301px) {
  .rkrk-card-table {
    width: max-content;
    min-width: 100%;
  }
  .rkrk-card-table td,
  .rkrk-card-table th {
    white-space: nowrap;
    vertical-align: middle;
  }
  .rkrk-card-table td input:not([type='image']):not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='hidden']),
  .rkrk-card-table td select,
  .rkrk-card-table td textarea {
    border: 1px solid #d8e4d4 !important;
    border-radius: 3px;
    padding: 0 3px !important;
    height: 22px;
    min-height: 22px;
    max-height: 24px;
    line-height: 20px;
    background: #fff;
    box-sizing: border-box;
    vertical-align: middle;
  }
  .rkrk-card-table td textarea {
    height: auto;
    max-height: none;
    overflow: hidden;
    white-space: nowrap;
  }
}
@media (min-width: 1301px) {
  .rkrk-desktop-scroll {
    width: auto;
    max-width: none;
    margin-left: 0;
    margin-right: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
  }
  .rkrk-desktop-scroll > table {
    min-width: 100%;
  }
}
@media (max-width: 1300px) {
  .rkrk-card-table tr.rkrk-card-header-row {
    display: none !important;
  }
  .rkrk-card-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }
  .rkrk-card-table tr.rkrk-card-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 10px;
    margin: 0;
    padding: 8px;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    background: #f3f5f8;
  }
  .rkrk-card-table td {
    display: block;
    box-sizing: border-box;
    border: none;
    padding: 2px;
  }
  .rkrk-card-table td::before {
    content: attr(data-label);
    display: block;
    margin: 0 0 3px 0;
    font-weight: 600;
    color: #445;
    font-size: 12px;
  }
  .rkrk-card-table td.rkrk-mobile-full,
  .rkrk-card-table td[colspan] {
    grid-column: 1 / -1;
  }
  .rkrk-card-table td.rkrk-mobile-iconcell::before,
  .rkrk-card-table td.rkrk-mobile-full::before {
    content: '';
    display: none;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
    min-height: 36px;
  }
}
@media (max-width: 700px) {
  .rkrk-card-table tr.rkrk-card-row {
    grid-template-columns: 1fr;
    gap: 7px;
    padding: 7px;
  }
  .rkrk-card-table td::before {
    font-size: 13px;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    min-height: 40px;
    font-size: 15px;
  }
}
.rkrk-feld-screen { width: 100%; min-width: 0; }
.rkrk-adr-sort { margin-left: 4px; color: #3f5a32; font-weight: 700; text-decoration: none; cursor: pointer; }
th.rkrk-adr-sortable { cursor: pointer; }
.rkrk-feld-cols {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px 8px;
  margin: 0 0 8px;
}
.rkrk-feld-screen .rkrk-adr-grid-wrap {
  max-height: none;
  box-sizing: border-box;
}
</style>";
echo "<script>
function rkrkApplyResponsiveCardTables() {
  var tables = document.querySelectorAll('table');
  for (var t = 0; t < tables.length; t++) {
    var table = tables[t];
    if (table.classList.contains('rkrk-adr-grid')) continue;
    var rows = table.querySelectorAll('tr');
    var headerRow = null;
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].querySelectorAll('th').length > 0) {
        headerRow = rows[i];
        break;
      }
    }
    if (!headerRow) continue;
    var ths = headerRow.querySelectorAll('th');
    if (ths.length < 3) continue;
    var labels = [];
    for (var h = 0; h < ths.length; h++) {
      labels.push((ths[h].textContent || '').trim());
    }
    table.classList.add('rkrk-card-table');
    headerRow.classList.add('rkrk-card-header-row');
    for (var r = 0; r < rows.length; r++) {
      var row = rows[r];
      if (row === headerRow || row.querySelectorAll('th').length > 0) continue;
      var tds = row.querySelectorAll('td');
      if (!tds.length) continue;
      row.classList.add('rkrk-card-row');
      for (var c = 0; c < tds.length; c++) {
        var td = tds[c];
        if (td.hasAttribute('colspan')) {
          td.classList.add('rkrk-mobile-full');
          td.setAttribute('data-label', '');
          continue;
        }
        var label = (labels[c] || '').trim();
        td.setAttribute('data-label', label);
        if (label === '') td.classList.add('rkrk-mobile-iconcell');
      }
    }
    table.dataset.rkrkResponsiveInit = '1';
  }
}
function rkrkApplyDesktopTableScroll() {
  function stretchWrapperToViewport(wrap) {
    if (!wrap) return;
    var rect = wrap.getBoundingClientRect();
    var left = Math.max(0, Math.round(rect.left || 0));
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var width = Math.max(120, vw - left - 6);
    wrap.style.width = width + 'px';
    wrap.style.maxWidth = width + 'px';
    wrap.style.marginRight = '0';
  }
  function availableWidthFor(el) {
    var parent = el && el.parentNode ? el.parentNode.clientWidth : 0;
    var rect = el && el.getBoundingClientRect ? el.getBoundingClientRect() : null;
    var left = rect ? Math.max(0, Math.round(rect.left || 0)) : 0;
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportRightSpace = Math.max(0, vw - left - 6);
    return Math.max(parent, viewportRightSpace);
  }
  var isNarrow = window.matchMedia('(max-width: 1300px)').matches;
  var wrappers = document.querySelectorAll('.rkrk-desktop-scroll');
  for (var w = 0; w < wrappers.length; w++) {
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
    if (wrappedTable.scrollWidth <= wrapAvailable + 1) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
    }
  }
  if (isNarrow) return;

  var tables = document.querySelectorAll('table');
  for (var i = 0; i < tables.length; i++) {
    var table = tables[i];
    if (!table || table.classList.contains('rkrk-adr-grid') || table.closest('.rkrk-desktop-scroll') || table.closest('.rkrk-adr-grid-wrap')) continue;
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
window.addEventListener('DOMContentLoaded', function() {
  rkrkApplyResponsiveCardTables();
  rkrkApplyDesktopTableScroll();
});
window.addEventListener('resize', rkrkApplyDesktopTableScroll);
</script>";

	// ===========================================
	// Definitions
	// ===========================================
		$fileoutput = "";
		$csv_desc = ';';
		$debugoutput = true;
		if (GetAccess("Geofence")) {
			$geofence = true; // Geofence enabled
		} else {
			$geofence = false; // Geofence disabled
		}
		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");

		$TableSetup = Array(
							"FeldID" => array ( "Field" => "FeldID",
												"Name" => "FeldID",
												"Type" => "text",
												"Sort" => "FeldID",
												"Show" => true),
							"GeoJG" => array ( "Field" => "GeoJG",
												"Name" => "GeoJG",
												"Type" => "text",
												"Sort" => "GeoJG",
												"Show" => false),
							"Feldname" 	=> array ( "Field" => "Feldname",
												"Name" => "Name",
												"Type" => "text",
												"Sort" => "Feldname",
												"Show" => true),
							"Flurname" => array ( "Field" => "Flurname",
												"Name" => "Flurname",
												"Type" => "text",
												"Sort" => "",
												"Show" => true),
							"GrundstueckID" => array ( "Field" => "GrundstueckID",
												"Name" => "GeoID",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
							"Gemeinde" => array ( "Field" => "Gde",
												"Name" => "Gde",
												"Type" => "text",
												"Sort" => "Gemeinde",
												"Show" => true),
							"GeoGde" => array ( "Field" => "GeoGde",
												"Name" => "GeoGde",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
							"Betrieb" => array ( "Field" => "Betrieb",
												"Name" => "Betrieb",
												"Type" => "text",
												"Sort" => "Betrieb",
												"Show" => false),
							"Freigabe" => array ( "Field" => "Freigabe",
												"Name" => "Freigabe",
												"Type" => "text",
												"Sort" => "Freigabe",
												"Show" => true),
							"Warnung" => array ( "Field" => "Warnung",
												"Name" => "Warnung",
												"Type" => "text",
												"Sort" => "Warnung",
												"Show" => true),
							"GeoWrn" => array ( "Field" => "GeoWrn",
												"Name" => "GeoWrn",
												"Type" => "text",
												"Sort" => "GeoWrn",
												"Show" => false),
							"Jaeger" => array ( "Field" => "Jaeger",
												"Name" => "Jaeger",
												"Type" => "text",
												"Sort" => "Jaeger",
												"Show" => true),
							"Bemerkung" => array ( "Field" => "Bemerkung",
												"Name" => "Bemerkung",
												"Type" => "text",
												"Sort" => "",
												"Show" => true),
							"Flug" => array ( "Field" => "Flug",
												"Name" => "Flug",
												"Type" => "text",
												"Sort" => "Flug",
												"Show" => true),
							"Funde" => array ( "Field" => "Funde",
												"Name" => "Funde",
												"Type" => "text",
												"Sort" => "Funde",
												"Show" => true),
							"Bauer" => array ( "Field" => "Bauer",
												"Name" => "Bauer",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
							"Notiz" => array ( "Field" => "Notiz",
												"Name" => "Notiz",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
							"Export" => array ( "Field" => "Export",
												"Name" => "Export",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
							"Select" => array ( "Field" => "Select",
												"Name" => "Select",
												"Type" => "text",
												"Sort" => "",
												"Show" => false),
										);
		// GeoJG, GeoID, GeoGde und GeoWrn bleiben im Spaltenwähler.
		// Show=false ist der Default; nachgerechnet wird nur, wenn die Spalte sichtbar ist.
		$FormSet = Array(
							"NewOnly" => array ( "Field" => "NewOnly",
												"Name" => "Nur neue Felder",
												"Type" => "text",
												"Set" => false),
							"Changed" => array ( "Field" => "Changed",
												"Name" => "Geänderte Felder",
												"Type" => "text",
												"Set" => false),
										);
	$xml_file= "RKRKFelder_N.xml";
echo "<i>XML online Flugkatalog</i>" .chr(10);
	echo "<span id = 'change_warning_message' style='color:red'> </span><br>";

		
	// ===========================================
	// Functions
	// ============================================

if (!function_exists('rkrk_list_fstr')) {
	function rkrk_list_fstr($feld, $key) {
		$v = is_array($feld) ? ($feld[$key] ?? '') : '';
		return function_exists('rkrk_str') ? rkrk_str($v) : (is_array($v) || is_object($v) ? '' : (string)($v ?? ''));
	}
}

if (!function_exists('rkrk_list_is_neu')) {
	function rkrk_list_is_neu($feld) {
		$frei = rkrk_list_fstr($feld, 'Freigabe');
		$parts = preg_split('/[\s,;|]+/', $frei, -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($parts)) {
			return false;
		}
		foreach ($parts as $tok) {
			if (strcasecmp((string)$tok, 'NEU') === 0) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('rkrk_list_ymd')) {
	function rkrk_list_ymd($year, $month, $day) {
		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;
		if (!checkdate($month, $day, $year)) {
			return null;
		}
		$ts = strtotime(sprintf('%04d-%02d-%02d', $year, $month, $day));
		return $ts === false ? null : $ts;
	}
}

if (!function_exists('rkrk_list_date_ts')) {
	function rkrk_list_date_ts($value) {
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/', $value, $m)) {
			return rkrk_list_ymd((int)$m[1], (int)$m[2], (int)$m[3]);
		}
		if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\D|$)/', $value, $m)) {
			return rkrk_list_ymd((int)$m[3], (int)$m[2], (int)$m[1]);
		}
		if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $value, $m)) {
			$year = (int)$m[3];
			$year += ($year >= 70) ? 1900 : 2000;
			return rkrk_list_ymd($year, (int)$m[2], (int)$m[1]);
		}
		return null;
	}
}

if (!function_exists('rkrk_list_changed_since')) {
	function rkrk_list_changed_since($feld, $sinceTs) {
		$sinceTs = (int)$sinceTs;
		if ($sinceTs < 1) {
			return false;
		}
		$sinceDay = strtotime(date('Y-m-d', $sinceTs));
		$today = strtotime(date('Y-m-d'));
		foreach (array('Aenderung', 'Update') as $key) {
			$ts = rkrk_list_date_ts(rkrk_list_fstr($feld, $key));
			if ($ts === null) {
				continue;
			}
			$day = strtotime(date('Y-m-d', $ts));
			if ($day >= $sinceDay && $day <= $today) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('rkrk_list_norm_name')) {
	function rkrk_list_norm_name($value) {
		$search = array(' ', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', '-', '_');
		$find = array('-', 'a', 'o', 'u', 'A', 'O', 'U', '-', '-');
		return str_replace($search, $find, trim((string)$value));
	}
}

if (!function_exists('rkrk_list_name_matches_file')) {
	/** Leerer Dateiname ist keine Abweichung. Sonst Name oder Name_Flurname. */
	function rkrk_list_name_matches_file($fileName, $feldName, $flurName) {
		$fileName = trim((string)$fileName);
		if ($fileName === '') {
			return true;
		}
		$fileNorm = rkrk_list_norm_name($fileName);
		$nameNorm = rkrk_list_norm_name($feldName);
		$bothNorm = rkrk_list_norm_name($feldName . '_' . $flurName);
		if ($fileNorm === $nameNorm || $fileNorm === $bothNorm) {
			return true;
		}
		if ($nameNorm !== '' && strpos($fileNorm, $nameNorm . '-') === 0) {
			return true;
		}
		return $bothNorm !== '' && strpos($fileNorm, $bothNorm . '-') === 0;
	}
}

if (!function_exists('rkrk_list_kat_row')) {
	function rkrk_list_kat_row($katFileList, $jg, $feldId) {
		$jg = trim((string)$jg);
		$feldId = trim((string)$feldId);
		if ($feldId === '' || !is_array($katFileList)) {
			return array();
		}
		if ($jg !== '' && isset($katFileList[$jg][$feldId]) && is_array($katFileList[$jg][$feldId])) {
			return $katFileList[$jg][$feldId];
		}
		foreach ($katFileList as $bucket) {
			if (!is_array($bucket) || !isset($bucket[$feldId]) || !is_array($bucket[$feldId])) {
				continue;
			}
			if (trim((string)($bucket[$feldId]['FeldID'] ?? '')) === '') {
				continue;
			}
			return $bucket[$feldId];
		}
		return array();
	}
}

//======================================
// Read in paramters for selection
//======================================

if (isset($_GET["FeldNamenWahl"])) {
	$FeldNamenWahl = $_GET["FeldNamenWahl"];
} elseif (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

SetMissionLocation();

$EnableDelete = false;
if (isset($_GET["EnableDelete"])) {
   if (strtolower($_GET["EnableDelete"])=="on") {
		//echo "EnableDelete=on";
 		$EnableDelete= true;
	} else {
		//echo "EnableDelete=off";
		$EnableDelete= false;
	}
}

IF ($EnableDelete) {
	$EnableDeleteSet = "EnableDelete=on";
} else {
	$EnableDeleteSet = "EnableDelete=off";
}
$mdbParam = 'MDB=' . rawurlencode((string)($RKRKMissionSelected ?? ''));

if (isset($_GET["DoSafe"])) { 
	if ($_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}
} else {
	$dosafe= false;
}
if (isset($_GET["delete"]) && isset($_GET["FeldID"]) ) { 
	if ($_GET["delete"]=="Y") {
		$dodelete= $_GET["FeldID"];
		$dosafe = true;
	} else {
		$dodelete= false;
	}
} else {
	$dodelete= false;
}

if (isset($_GET["Sortieren"])) {
	$ColSort = $_GET["Sortieren"];
} else {
	$ColSort = "Feldname";
}

if (isset($_GET["Reg"])) {
		$RegSelected = $_GET["Reg"];
	} else {
		$RegSelected = "";
	}

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
		$AnzLinesPPage= 30;
	}
	if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
		$ActPage= $_GET["ActPage"];
	} else {
		$ActPage= 1;
	}
	// max_input_vars ist 1000. Pro Zeile bis zu 18 Felder, deshalb höchstens 30 Zeilen.
	$AnzLines = array(10, 20, 25, 30);
	$AnzLinesPPage = (int)$AnzLinesPPage;
	if ($AnzLinesPPage < 1 || $AnzLinesPPage > 30) {
		$AnzLinesPPage = 30;
	}
	$ActPage = (int)$ActPage;
	if ($ActPage < 1) {
		$ActPage = 1;
	}
	$regUrl = '';
	if (trim((string)$RegSelected) !== '') {
		$regUrl = '&Reg=' . rawurlencode(trim((string)$RegSelected));
	}
	$filterNewOnly = isset($_GET['NewOnly']) && (string)$_GET['NewOnly'] !== '' && (string)$_GET['NewOnly'] !== '0';
	$filterChanged = isset($_GET['Changed']) && (string)$_GET['Changed'] !== '' && (string)$_GET['Changed'] !== '0';
	$rkrkChangedSince = strtotime('2026-10-05');
	if ($filterNewOnly) {
		$regUrl .= '&NewOnly=1';
	}
	if ($filterChanged) {
		$regUrl .= '&Changed=1';
	}
	if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
		$FontSize= $_POST["FontSize"];
	}
	if (!isset($FontSize) || $FontSize === "") {
		$FontSize = "100%";
	}
	if (!isset($MaxSelectedEntries)) {
		$MaxSelectedEntries = 0;
	}



// internal Legend
function Legends() {
	global $OptionFrei, $OptionWarning, $OptionGemeinde;
	//pre_r($Feldkatalog);
	echo "</br>";
	echo "<table>";
	echo "<tr valign='top'><td>";
	echo "<h3>Freigabe</h3>" .chr(10);
	echo "</td><td>";
	echo "<h3>Warnung</h3>" .chr(10);
	echo "</td><td colspan=5>";
	echo "<h3>Gemeinden</h3>" .chr(10);
	echo "</td></tr>";
	echo "<tr valign='top'><td>";
	echo "<ul>" . chr(10);
	foreach ($OptionFrei as $Key => $Entry) {
		if (!is_array($Entry)) {
			continue;
		}
		echo "<li> <b>" . htmlspecialchars((string)($Entry["Opt"] ?? ''), ENT_QUOTES, 'UTF-8') . "</b>  -  ". htmlspecialchars((string)($Entry["Text"] ?? ''), ENT_QUOTES, 'UTF-8') . "</li>";
	}
	echo "</ul>" . chr(10);
	echo "</td><td>";
	echo "<ul>" . chr(10);
	foreach ($OptionWarning as $Key => $Entry) {
		if (!is_array($Entry)) {
			continue;
		}
		echo "<li> <b>" . htmlspecialchars((string)($Entry["Opt"] ?? ''), ENT_QUOTES, 'UTF-8') . "</b>  -  ". htmlspecialchars((string)($Entry["Text"] ?? ''), ENT_QUOTES, 'UTF-8') . "</li>";
	}
	echo "</ul>" . chr(10);
	echo "</td><td>";
	echo "<ul>" . chr(10);
	$i= 0;
	foreach ($OptionGemeinde as $Key => $Entry) {
		if (!is_array($Entry)) {
			continue;
		}
		echo "<li> <b>" . htmlspecialchars((string)($Entry["Opt"] ?? ''), ENT_QUOTES, 'UTF-8') . "</b>  -  ". htmlspecialchars((string)($Entry["Text"] ?? ''), ENT_QUOTES, 'UTF-8') . "</li>";
		//echo $i%5 . "<br>";
		if (($i%6)==5) {
			echo "</td><td>";
		}
		$i++;
	}
	echo "</ul>" . chr(10);
	echo "</td></tr>";
	echo "</table>";
	
}


// ===========================================
// HEADER FORM
// ===========================================

$EnabledRegion = Array();
$defaultHidden = array();
foreach ($TableSetup as $setupKey => $Feld) {
	if (is_array($Feld) && empty($Feld["Show"])) {
		$defaultHidden[] = $setupKey;
	}
}
$adrUi = rkrk_adr_ui_load('felder');
if ((!isset($adrUi['hidden']) || $adrUi['hidden'] === array()) && empty($adrUi['widths'])) {
	$adrUi['hidden'] = $defaultHidden;
}
$hiddenCols = array();
if (!empty($adrUi['hidden']) && is_array($adrUi['hidden'])) {
	foreach ($adrUi['hidden'] as $hiddenKey) {
		$hiddenCols[(string)$hiddenKey] = true;
	}
}
$lazyGeoCols = array('GeoJG' => true, 'GrundstueckID' => true, 'GeoGde' => true, 'GeoWrn' => true);
$geoShown = null;
if (array_key_exists('rkrk_felder_geo', $_COOKIE)) {
	$geoShown = array();
	foreach (explode(',', (string)$_COOKIE['rkrk_felder_geo']) as $geoPart) {
		$geoPart = trim($geoPart);
		if (isset($lazyGeoCols[$geoPart])) {
			$geoShown[$geoPart] = true;
		}
	}
}
foreach ($lazyGeoCols as $geoKey => $geoFlag) {
	if (is_array($geoShown) && isset($geoShown[$geoKey])) {
		unset($hiddenCols[$geoKey]);
	} else {
		$hiddenCols[$geoKey] = true;
	}
}
$adrUi['hidden'] = array_keys($hiddenCols);
echo '<form name="form" method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $mdbParam . '&ActPage=' . (int)$ActPage . '&' . $EnableDeleteSet . $regUrl, ENT_QUOTES, 'UTF-8') . '">';
$i= 0;
$NoSelection = true;
echo '<div class="rkrk-adr-head">';
echo '<div class="rkrk-adr-head-main">';
echo '<div class="rkrk-adr-head-row" data-adr-filter="region">';
echo '<span class="rkrk-adr-head-label">Region</span>';
$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, $RegSelected, true);
$i++;
echo '</div>';
echo "<script>
(function(){
  var root = document.querySelector('[data-adr-filter=\"region\"]');
  if (!root || root.getAttribute('data-reg-nav') === '1') return;
  root.setAttribute('data-reg-nav', '1');
  function labelsFromReg(reg) {
    reg = String(reg || '').trim();
    if (reg === '' || /^alle$/i.test(reg) || /^all$/i.test(reg)) return ['alle'];
    return reg.split(/[\\s,]+/).filter(function(part){ return part !== ''; });
  }
  function writeRegions(labels) {
    try {
      var key = 'rkrk-adr-filter-felder';
      var prev = JSON.parse(localStorage.getItem(key) || '{}') || {};
      prev.regions = labels;
      localStorage.setItem(key, JSON.stringify(prev));
    } catch (e) {}
  }
  var current = new URLSearchParams(window.location.search);
  if (current.has('Reg')) writeRegions(labelsFromReg(current.get('Reg')));
  root.addEventListener('change', function(ev){
    var cb = ev.target;
    if (!cb || cb.type !== 'checkbox') return;
    window.setTimeout(function(){
      var regs = [];
      var alle = false;
      var boxes = root.querySelectorAll('input[type=\"checkbox\"]');
      for (var n = 0; n < boxes.length; n++) {
        var box = boxes[n];
        if (!box.checked) continue;
        var lab = (box.parentNode && box.parentNode.textContent ? box.parentNode.textContent : '').replace(/\\s+/g, ' ').trim();
        var isAll = box.getAttribute('data-rkrk-exclusive') === '1' || /^alle$/i.test(lab) || /^all$/i.test(lab);
        if (isAll) { alle = true; continue; }
        if (lab) regs.push(lab);
      }
      var labels = (alle || regs.length === 0) ? ['alle'] : regs.slice();
      writeRegions(labels);
      var url = new URL(window.location.href);
      url.searchParams.set('Reg', labels[0] === 'alle' && labels.length === 1 ? 'alle' : labels.join(' '));
      url.searchParams.set('ActPage', '1');
      window.location.assign(url.toString());
    }, 30);
  });
})();
</script>\n";
echo '<div class="rkrk-adr-head-row" data-adr-filter="flags">';
echo '<span class="rkrk-adr-head-label">Anzeige</span>';
rkrk_echo_pills_open();
foreach ($FormSet as $Feld ) {
	$FormSet[$Feld["Field"]]["Set"] = false;
	$flag = ($Feld["Field"] === "NewOnly") ? "new" : (($Feld["Field"] === "Changed") ? "changed" : "");
	$flagOn = ($Feld["Field"] === "NewOnly" && !empty($filterNewOnly)) || ($Feld["Field"] === "Changed" && !empty($filterChanged));
	if ($flagOn) {
		$FormSet[$Feld["Field"]]["Set"] = true;
	}
	rkrk_echo_pill_checkbox($Feld["Field"] . "_en", $Feld["Name"], $flagOn, array('attrs' => array('data-flag' => $flag)));
}
rkrk_echo_pills_close();
echo '</div></div>';
echo '<div class="rkrk-adr-head-tools">';
if ($Layout!=2023) {
	echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
	echo CreateSiteLink ("EditFeld", "");
	echo CreateSiteLink ("Fluege", "");
	echo CreateSiteLink ("EditFiles", "");
	echo CreateSiteLink ("FeldKarteIntern", "Reg=ALL" . $StandardSettingMap);
	echo CreateSiteLink ("FeldListeIntern", "Reg=ALL&Typ=intern");
	echo CreateSiteLink ("Home", "");
}
if (GetAccess("DeleteData")) {
	if ($EnableDelete) {
		echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . $mdbParam . "&ActPage=" . $ActPage . "&EnableDelete=off" . $regUrl . "' height='" . $ButtonHeight . "'  title='Delete Moduls ausschalten'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	} else {
		echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . $mdbParam . "&ActPage=" . $ActPage . "&EnableDelete=on" . $regUrl . "' height='" . $ButtonHeight . "'title='Delete Moduls einschalten'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	}
}
$AddURL = "&" . "ActPage=" . $ActPage . "&" . $EnableDeleteSet;
echo MissionLibraryButtons($AddURL);
if (GetAccess("TestOnly")) {
	$feldCsvHref = (!empty($feldkatalogCsvLocation) ? $feldkatalogCsvLocation : $csvMissionLocation) . $RKRKFelderfileCSV;
	if (is_file($feldCsvHref)) {
		echo "<span id='exceldwnld'><a href='" . $feldCsvHref . "' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Feldkatalog herunterladen'></a></span> ";
	}
}
echo '</div></div>';
echo '<input type="hidden" name="Act" id="Act" value="save">'. chr(10);
//pre_r($EnabledRegion);
//pre_r($RegionSettings);
//pre_r($TableSetup);
//pre_r($FormSet);


//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (JSON, XML-Fallback)
//////////////////////////////////////////////

$OptionJaeger = Array();
$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
$fileloaded = is_array($Feldkatalog) && $Feldkatalog !== array();
if (!$fileloaded) {
	$droneName = (string)($RKRKMissionSelected ?? '');
	if (isset($MissionDroneTypes[$droneName]["Name"])) {
		$droneName = (string)$MissionDroneTypes[$droneName]["Name"];
	}
	if ($droneName === '') {
		$droneName = 'diesen Drohnentyp';
	}
	echo '        <p>Feldkatalog für ' . htmlspecialchars($droneName, ENT_QUOTES, 'UTF-8') . ' nicht vorhanden.</p>';
}

////////////////////////////////////////////////////////////
// Korrigiere XML Eingabewerte nru Einschalten wenn nötig
///////////////////////////////////////////////////////////

if (false) {
	foreach ($Feldkatalog as $Key => $Region) {
		foreach ($Region as $Key => $Feld) {
			// 20240420 Fülle alle Wegpunkte
			if (($Feld["Wegpunkte"]=="") and false) {
				echo "Füllen aller leeren Wegpunkte im Flugkatalog <br>"; 
				$JSONfile = explode(",",$Feld["JasonFile"])[0];
				$JsonData =  GetJSONData($RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$JSONfile);
				$Wegpunkte= round($JsonData["Wegpunkte"], 0);
				echo "Feld: " . $Feld["FeldID"] . " - "; 
				echo "Wegpunkte: " . $Wegpunkte . "<br>"; 
				$Feldkatalog[$Feld["JG"]][$Feld["FeldID"]]["Wegpunkte"]= round($JsonData["Wegpunkte"], 0);
			}	
		}
	}
	AdjustFeldKatalog ($Feldkatalog);	
	SaveFlugkatalog2XML ($Feldkatalog, true);
	SaveFlugkatalog2CSV ($Feldkatalog);
}	

///////////////////////////////////////
// Process Input Data
///////////////////////////////////////


if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$numEntries = isset($_POST["NumEntries"]) ? (int)$_POST["NumEntries"] : 0;
	if ($numEntries > 0) {
		//new configuration, safe configuration file
		//echo "NumE: " . $numEntries .  "<br>" . chr(10);

		// safe history
		$file = $xmlMissionLocation . $RKRKFelderfileXML;
		$Min =  intval(date("i")/10)*10;
		$newfile = $historyfilelocation . date("YmdH") . sprintf("%02d", $Min) . "_" . $RKRKFelderfileXML;
		//echo "<br>History: " . $file . " to  " . $newfile;

		if (!copy($file, $newfile)) {
			$SystemInfo .= "failed to copy $file...\n";
		}

		//Write to LOGfile
		// $i ist hier noch der Zähler aus dem Seitenkopf (1), nicht die Zeilenschleife.
		// JG1 ist deshalb oft nicht im POST; fehlenden Schlüssel nicht warnen.
		WriteToLog("save", basename($_SERVER['PHP_SELF']), "Felddatenliste ",strval($_POST["JG" . $i] ?? ''),$RKRKMissionSelected,"");
		for ($i = 1; $i < $numEntries; $i++) {
			//echo $i . "<br>";
			//echo  $i . "  " . $_POST["FeldID" . $i] . " " . $_POST["Feldname" . $i] . " " . $_POST["GrundstueckID" . $i];
			if (true) {		


				$JG = strval($_POST["JG" . $i] ?? '');
				$FeldID = strval($_POST["FeldID" . $i]);


				// READ Input data to array
				// Tabellenfelder
				if (isset($_POST["FeldID" . $i])) {
					//echo "FeldID" . $i . " " . $_POST["FeldID" . $i] ."<br>";
					$Feldkatalog[$JG][$FeldID]["FeldID"]= $FeldID;
				}
				if (isset($_POST["JG" . $i])) {
					//echo "JG" . $i . " " . $_POST["JG" . $i] ."<br>";
					$Feldkatalog[$JG][$FeldID]["JG"]= $JG;
				}
				if (isset($_POST["Number" . $i])) {
					//echo "Number" . $i . " " . $_POST["Number" . $i] ."<br>";
					$Feldkatalog[$JG][$FeldID]["Number"]= $_POST["Number" . $i];
				}
				if (isset($_POST["Feldname" . $i])) {
					//echo "Feldname" . $i . " " . $_POST["Feldname" . $i] ."<br>";
					$Feldkatalog[$JG][$FeldID]["Feldname"]= $_POST["Feldname" . $i];
				}
				if (isset($_POST["Flurname" . $i])) {
					if ($_POST["Flurname" . $i]!="") {
						//echo "Flurname" . $i . " " . $_POST["Flurname" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flurname"]= $_POST["Flurname" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flurname"]= "";
					}
				}
				if (isset($_POST["GrundstueckID" . $i])) {
					if ($_POST["GrundstueckID" . $i]!="") {
						//echo "GrundstueckID" . $i . " " . $_POST["GrundstueckID" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= $_POST["GrundstueckID" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= "";
					}
				}
				if (isset($_POST["Flugzeit" . $i])) {
					if ($_POST["Flugzeit" . $i]!="") {
						//echo "Flugzeit" . $i . " " . $_POST["Flugzeit" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flugzeit"]= $_POST["Flugzeit" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flugzeit"]= "";
					}
				}
				if (isset($_POST["Flaeche" . $i])) {
					if ($_POST["Flaeche" . $i]!="") {
						//echo "Flaeche" . $i . " " . $_POST["Flaeche" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flaeche"]= $_POST["Flaeche" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flaeche"]= "";
					}
				}
				if (isset($_POST["Wegpunkte" . $i])) {
					if ($_POST["Wegpunkte" . $i]!="") {
						//echo "Wegpunkte" . $i . " " . $_POST["Wegpunkte" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Wegpunkte"]= $_POST["Wegpunkte" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Wegpunkte"]= "";
					}
				}
				if (isset($_POST["Distanz" . $i])) {
					if ($_POST["Distanz" . $i]!="") {
						//echo "Distanz" . $i . " " . $_POST["Distanz" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Distanz"]= $_POST["Distanz" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Distanz"]= "";
					}
				}
				if (isset($_POST["Gemeinde" . $i])) {
					if ($_POST["Gemeinde" . $i]!="") {
						//echo "Gemeinde" . $i . " " . $_POST["Gemeinde" . $i] ."<br>";
						//echo "Gd" .$i .  $JG . "/" . $FeldID . " : " . $_POST["Gemeinde" . $i] ." <br>";
						$Feldkatalog[$JG][$FeldID]["Gemeinde"]= $_POST["Gemeinde" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Gemeinde"]= "";
					}
				}
				if (isset($_POST["Betrieb" . $i])) {
					if ($_POST["Betrieb" . $i]!="") {
						//echo "Betrieb" . $i . " " . $_POST["Betrieb" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Betrieb"]= $_POST["Betrieb" . $i];
					//echo "B" .$i .  $JG . "/" . $FeldID . " : "  . $_POST["Betrieb" . $i] . " - ". $InputData[$JG][$FeldID]["Betrieb"] . " - ". $Feldkatalog[$JG][$FeldID]["Betrieb"] . " <br>";
					} else {
						$Feldkatalog[$JG][$FeldID]["Betrieb"]= "";
					}
				}
				if (isset($_POST["Freigabe" . $i])) {
					if ($_POST["Freigabe" . $i]!="") {
						//echo "Freigabe" . $i . " " . $_POST["Freigabe" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Freigabe"]= $_POST["Freigabe" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Freigabe"]= "";
					}
				}
				if (isset($_POST["Warnung" . $i])) {
					if ($_POST["Warnung" . $i]!="") {
						//echo "Warnung" . $i . " " . $_POST["Warnung" . $i] ."<br>";
						//echo "Wa" .$i .  $JG . "/" . $FeldID . " : " . $_POST["Warnung" . $i] ." <br>";
						$Feldkatalog[$JG][$FeldID]["Warnung"]= $_POST["Warnung" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Warnung"]= "";
					}
				}
				if (isset($_POST["Jaeger" . $i])) {
					if ($_POST["Jaeger" . $i]!="") {
						//echo "Jaeger" . $i . " " . $_POST["Jaeger" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Jaeger"]= $_POST["Jaeger" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Jaeger"]= "";
					}
				}
				if (isset($_POST["Bemerkung" . $i])) {
					if ($_POST["Bemerkung" . $i]!="") {
						//echo "Bemerkung" . $i . " " . $_POST["Bemerkung" . $i] ."<br>";
						//echo "Bemerkung" .$i .  $JG . "/" . $FeldID . " : "  . $_POST["Bemerkung" . $i] . " <br>";
						$Feldkatalog[$JG][$FeldID]["Bemerkung"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Bemerkung" . $i]));
					} else {
						$Feldkatalog[$JG][$FeldID]["Bemerkung"]= "";
					}
				}
				if (isset($_POST["Flug" . $i])) {
					if ($_POST["Flug" . $i]!="") {
						//echo "Flug" . $i . " " . $_POST["Flug" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flug"]= $_POST["Flug" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flug"]= "";
					}
				}
				if (isset($_POST["Funde" . $i])) {
					if ($_POST["Funde" . $i]!="") {
						//echo "Funde" . $i . " " . $_POST["Funde" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Funde"]= $_POST["Funde" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Funde"]= "";
					}
				}
				if (isset($_POST["SuchfeldBild" . $i])) {
					if ($_POST["SuchfeldBild" . $i]!="") {
						//echo "SuchfeldBild" . $i . " " . $_POST["SuchfeldBild" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= $_POST["SuchfeldBild" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= "";
					}
				}
				if (isset($_POST["FlugplanBild" . $i])) {
					if ($_POST["FlugplanBild" . $i]!="") {
						//echo "FlugplanBild" . $i . " " . $_POST["FlugplanBild" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= $_POST["FlugplanBild" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= "";
					}
				}
				if (isset($_POST["JasonFile" . $i])) {
					if ($_POST["JasonFile" . $i]!="") {
						//echo "JasonFile" . $i . " " . $_POST["JasonFile" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["JasonFile"]= $_POST["JasonFile" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["JasonFile"]= "";
					}
				}
				if (isset($_POST["KMLFile" . $i])) {
					if ($_POST["KMLFile" . $i]!="") {
						//echo "KMLFile" . $i . " " . $_POST["KMLFile" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["KMLFile"]= $_POST["KMLFile" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["KMLFile"]= "";
					}
				}
				if (isset($_POST["LichtiFile" . $i])) {
					if ($_POST["LichtiFile" . $i]!="") {
						//echo "LichtiFile" . $i . " " . $_POST["LichtiFile" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["LichtiFile"]= $_POST["LichtiFile" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["LichtiFile"]= "";
					}
				}
				if (isset($_POST["Bauer" . $i])) {
					if ($_POST["Bauer" . $i]!="") {
						//echo "Bauer" . $i . " " . $_POST["Bauer" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Bauer"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Bauer" . $i]));
					} else {
						$Feldkatalog[$JG][$FeldID]["Bauer"]= "";
					}
				}
				if (isset($_POST["Notiz" . $i])) {
					if ($_POST["Notiz" . $i]!="") {
						//echo "Notiz" . $i . " " . $_POST["Notiz" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Notiz"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Notiz" . $i]));
					} else {
						$Feldkatalog[$JG][$FeldID]["Notiz"]= "";
					}
				} 
				if (isset($_POST["Startplatz" . $i])) {
					if ($_POST["Startplatz" . $i]!="") {
						//echo "Startplatz" . $i . " " . $_POST["Startplatz" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Startplatz"]= $_POST["Startplatz" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Startplatz"]= "";
					}
				}
				if (isset($_POST["Zentrum" . $i])) {
					if ($_POST["Zentrum" . $i]!="") {
						//echo "Zentrum" . $i . " " . $_POST["Zentrum" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Zentrum"]= $_POST["Zentrum" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Zentrum"]= "";
					}
				}
				if (isset($_POST["Flaechendaten" . $i])) {
					if ($_POST["Flaechendaten" . $i]!="") {
						//echo "Flaechendaten" . $i . " " . $_POST["Flaechendaten" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flaechendaten"]= $_POST["Flaechendaten" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flaechendaten"]= "";
					}
				}
				if (isset($_POST["Flugweg" . $i])) {
					if ($_POST["Flugweg" . $i]!="") {
						//echo "Flugweg" . $i . " " . $_POST["FeldID" . $i] ."<br>";
						$Feldkatalog[$JG][$FeldID]["Flugweg"]= $_POST["Flugweg" . $i];
					} else {
						$Feldkatalog[$JG][$FeldID]["Flugweg"]= "";
					}
				}
				if (isset($_POST["Export" . $i])) {
					//echo "Export" . $i . " " . $_POST["FeldID" . $i] ."<br>";
					if (($_POST["Export" . $i]) == "on") {
						$Feldkatalog[$JG][$FeldID]["Export"]= "Y";
					} else {
						$Feldkatalog[$JG][$FeldID]["Export"]= "N";
					}
				}
				if (isset($_POST["Select" . $i])) {
					//echo "Select" . $i . " " . $_POST["Select" . $i] ."<br><br>";
					if (($_POST["Select" . $i]) == "on") {
						$Feldkatalog[$JG][$FeldID]["Select"]= "Y";
					} else {
						$Feldkatalog[$JG][$FeldID]["Select"]= "N";
					}
				}
				//pre_r($Feldkatalog[$JG][$FeldID]);
				//delete entries if requested
				//echo $FeldID . "<br>";
				if ($dodelete == $FeldID) {
					//Write to LOGfile
					WriteToLog("del", basename($_SERVER['PHP_SELF']), "Felddatenliste ",$FeldID,$RKRKMissionSelected,"");

					//delete files with starting with FeldID
					$filenames2delete = $RKRKLocalLoactionMissionFiles . $RegionSettings[$JG]["Dir"]. "/". $FeldID ."*";
					$files = glob($filenames2delete); // To get a list of the files in the images directory
					
					//pre_r($files);
					//delete files //
					/* 
					foreach($files as $file){ // To iterate through the files
					  if(is_file($file)) {  //To check whether the filename is valid
						unlink($file); // To delete the file
						//echo "deleted " . $file . "<br>";
					  }
					}
					*/
					
					//echo "Do delete: " . $JG . " - " . $dodelete . "<br>";
					
					//move files to delete folder
					foreach($files as $file){ // To iterate through the files
					  if(is_file($file)) {  //To check whether the filename is valid
						rename($file, $deletefilelocation.date("YmdHi")."_".basename($file) );
						//echo "deleted " . $file . "<br>";
						//echo dirname($file) . "<br>";
						//echo $deletefilelocation . "<br>";
						//echo basename($file) . "<br>";
					  }
					}
					//delete entry in Feldkatalog
					unset($Feldkatalog[$JG][$FeldID]);
					//delete empty entries
				}
				//echo count($Feldkatalog);
				//pre_R($Feldkatalog[$JG][$FeldID]);
			}
		}	
	}
	// update XML
	//$NumKatEntries = 0;
	//foreach ($Feldkatalog as $key => $FkJg) {
	//	echo $key . " " . count($FkJg) ." - ";
	//	$NumKatEntries = $NumKatEntries + count($FkJg);
	//}
	//echo "Total " . $NumKatEntries ."<br>";
	unset($Feldkatalog['']);
	//pre_R($Feldkatalog);
	if ($dosafe) {
		SaveFlugkatalog2XML ($Feldkatalog, $dosafe);
		//echo "saving CSV";
		SaveFlugkatalog2CSV ($Feldkatalog);
	}
}	

$FeldkatalogList = GetFeldKatalogList ($Feldkatalog);
//pre_r($Feldkatalog);

	//===========================
	// Andere Settings einlesen
	//===========================
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
	}

	// JG/Region selection
	//pre_r($EnabledRegion);
	$RegionsSelected = array();
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg != 1) {
			continue;
		}
		if (!isset($RegionSettings[$key]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $JGtoSelect = trim((string)$JGtoSelect);
		  if ($JGtoSelect !== '') {
			  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		  }
		}
	}
	//pre_R($RegionsSelected);
	$regionFilterNarrow = false;
	$activeRegionTotal = 0;
	$activeRegionOn = 0;
	if (is_array($RegionSettings)) {
		foreach ($RegionSettings as $regRow) {
			if (!is_array($regRow)) {
				continue;
			}
			$rk = (string)($regRow['Reg'] ?? '');
			if ($rk === '' || $rk === 'NO' || strcasecmp($rk, 'ALL') === 0 || strcasecmp($rk, 'leer') === 0) {
				continue;
			}
			if (empty($regRow['Aktiv'])) {
				continue;
			}
			$activeRegionTotal++;
			if (!empty($EnabledRegion[$rk])) {
				$activeRegionOn++;
			}
		}
	}
	$regionFilterNarrow = ($activeRegionOn > 0 && $activeRegionOn < $activeRegionTotal && count($RegionsSelected) > 0);
	$regKeep = '';
	if ($regionFilterNarrow) {
		$onRegs = array();
		foreach ($EnabledRegion as $rk => $on) {
			if (!empty($on)) {
				$onRegs[] = (string)$rk;
			}
		}
		if ($onRegs !== array()) {
			$regKeep = '&Reg=' . rawurlencode(implode(' ', $onRegs));
		}
	}
	if (!empty($regionFilterNarrow)) {
		foreach ($FeldkatalogList as $fid => $Feld) {
			if (!is_array($Feld) || !isset($RegionsSelected[rkrk_list_fstr($Feld, "JG")])) {
				unset($FeldkatalogList[$fid]);
			}
		}
	}
	if (!empty($filterNewOnly) || !empty($filterChanged)) {
		foreach ($FeldkatalogList as $fid => $Feld) {
			if (!is_array($Feld)) {
				unset($FeldkatalogList[$fid]);
				continue;
			}
			$match = false;
			if (!empty($filterNewOnly) && rkrk_list_is_neu($Feld)) {
				$match = true;
			}
			if (!empty($filterChanged) && rkrk_list_changed_since($Feld, $rkrkChangedSince)) {
				$match = true;
			}
			if (!$match) {
				unset($FeldkatalogList[$fid]);
			}
		}
	}
	aasort($FeldkatalogList, "FeldID");
	aasort($FeldkatalogList, $ColSort);
    //pre_r($Feldkatalog);

	$i=1;
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "Felddatenliste ","",$RKRKMissionSelected,"");
		foreach ($FeldkatalogList as $fid => $Feld) {
			if (!is_array($Feld)) {
				continue;
			}
			$FeldkatalogList[$fid]["ListNr"] = $i;
			$i++;
		}
		$MaxSelectedEntries = $i;
		//pre_r($Feldkatalog);

		// ===========================================
		// Vergleiche Feldkatalog mit FileListe
		// ===========================================

		//pre_r($EnabledRegion);
		$colorOK = "#DAEDDA";
		$colorOKLink = "#368536";
		$colorNOK = "#F1E2E0";
		$colorNOKLink = "#EF5E4A";
		$colorSIM = "#F8FACF";
		$colorSIMLink = "#B7BD3A";
		$colorOKtext = "#6B9E6B";
		$colorNOKtext = "#922618";
		if (!isset($OptionGemeinde) || !is_array($OptionGemeinde)) {
			$OptionGemeinde = array();
		}
		$OptionGemeinde[""] = Array("Opt" => "","Kml" => "", "Text" => "");
		$gdeSort = array();
		foreach ($OptionGemeinde as $gdeKey => $gdeEntry) {
			$gdeSort[$gdeKey] = is_array($gdeEntry) ? (string)($gdeEntry["Opt"] ?? '') : '';
		}
		if (count($OptionGemeinde) > 1) {
			array_multisort($gdeSort, SORT_ASC, SORT_STRING, $OptionGemeinde);
		}
		//pre_r($OptionGemeinde);
		
			$i=1;
			$pageline = 1;
			if ((int)$AnzLinesPPage < 1) {
				$AnzLinesPPage = 30;
			}
			$visibleIds = array();
			foreach ($FeldkatalogList as $fid => $Feld) {
				if (!is_array($Feld)) {
					continue;
				}
				$feldJgVis = rkrk_list_fstr($Feld, "JG");
				if (!empty($regionFilterNarrow) && !isset($RegionsSelected[$feldJgVis])) {
					continue;
				}
				$visibleIds[] = $fid;
			}
			$visibleCount = count($visibleIds);
			$MaxSelectedEntries = $visibleCount;
			$MaxPages = ($visibleCount > 0) ? (int)ceil($visibleCount / (int)$AnzLinesPPage) : 1;
			if ((int)$ActPage > $MaxPages) {
				$ActPage = $MaxPages;
			}
			if ((int)$ActPage < 1) {
				$ActPage = 1;
			}
			$showfrom = ((int)$ActPage - 1) * (int)$AnzLinesPPage;
			$pageIds = array_slice($visibleIds, $showfrom, (int)$AnzLinesPPage);
			$pageSet = array();
			foreach ($pageIds as $pageFid) {
				$pageSet[$pageFid] = true;
			}
			$scanRegion = array();
			foreach ($pageIds as $pageFid) {
				$pageJg = rkrk_list_fstr($FeldkatalogList[$pageFid] ?? array(), "JG");
				if ($pageJg !== '') {
					$scanRegion[$pageJg] = 1;
				}
			}
			$KatFileList = ReadMissionFiles($RegionSettings, $scanRegion, $pageIds);
			$FontSizeInPoints = 11;
			if ($FontSize== "") {
				$FontSize= "100%";
			}
			$fontScale = (float)preg_replace('/[^0-9.]/', '', (string)$FontSize);
			if ($fontScale <= 0) {
				$fontScale = 100;
			}
			$iconHeight = (float)$ButtonHeightSmall * $fontScale / 100;
			$TableColor =  GetTableColor ("");
			AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

			echo "<div id='alle' class='tabcontent'>" . chr(10);
			foreach ($TableSetup as $setupKey => $Feld) {
				if (!is_array($Feld)) {
					continue;
				}
				if (isset($lazyGeoCols[$setupKey])) {
					$TableSetup[$setupKey]["Show"] = !isset($hiddenCols[$setupKey]);
					continue;
				}
				$TableSetup[$setupKey]["Show"] = true;
			}
			echo "<div class='rkrk-feld-screen'>";
			echo "<details class='rkrk-adr-col-vis'>";
			echo "<summary>Spalten ein-/ausblenden</summary>";
			echo "<p class='rkrk-adr-note'>Nicht angehakt = ausgeblendet. Die Einstellung bleibt gespeichert.</p>";
			echo "<div class='rkrk-adr-cols'>";
			foreach ($TableSetup as $setupKey => $TableEntry) {
				if (!is_array($TableEntry)) {
					continue;
				}
				$checked = isset($hiddenCols[(string)$setupKey]) ? '' : ' checked';
				$on = $checked !== '' ? " class='is-on'" : '';
				echo "<label" . $on . "><input type='checkbox' data-col='" . htmlspecialchars((string)$setupKey, ENT_QUOTES, 'UTF-8') . "'" . $checked . "> " . htmlspecialchars((string)$TableEntry["Name"], ENT_QUOTES, 'UTF-8') . "</label>";
			}
			echo "</div>";
			echo "<button type='button' class='rkrk-adr-btn rkrk-adr-showall'>Alle anzeigen</button>";
			echo "</details>";
			echo "<div class='rkrk-adr-grid-wrap'>";
			echo "<table class='rkrk-adr-grid' data-adr-grid='felder' data-ui='" . htmlspecialchars(json_encode($adrUi), ENT_QUOTES, 'UTF-8') . "'>" .chr(10);
			echo "<tr >" .chr(10);
			echo "<th data-col='__nr'>#</th>" .chr(10);
			//echo "<th>dwld</th>" .chr(10);
			foreach ($FlyFileSetting as $FileKey => $FileType) {
				if ((string)$FileKey === 'LCHM') {
					continue;
				}
				$fileShow = function_exists('rkrk_explode_csv')
					? rkrk_explode_csv($FileType["Show"] ?? '')
					: explode(",", (string)($FileType["Show"] ?? ''));
				if (in_array("edit_felddatenliste", $fileShow, true)){
					echo "<th data-col='__file-" . htmlspecialchars((string)$FileKey, ENT_QUOTES, 'UTF-8') . "'></th>" .chr(10);
				}
			}
			foreach ($TableSetup AS $setupKey => $TableEntry) {
				if (!is_array($TableEntry) || empty($TableEntry["Show"])) {
					continue;
				}
				$sortKey = trim((string)($TableEntry["Sort"] ?? ''));
				echo "<th class='rkrk-adr-sortable' data-col='" . htmlspecialchars((string)$setupKey, ENT_QUOTES, 'UTF-8') . "' title='Spalte sortieren'>" . htmlspecialchars((string)$TableEntry["Name"]);
				if ($sortKey !== '') {
					$sortHref = htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8') . "?" . $mdbParam . "&ActPage=1&Sortieren=" . rawurlencode($sortKey) . "&" . $EnableDeleteSet . $regUrl;
					echo " <a class='rkrk-adr-sort' href='" . $sortHref . "' title='Ganze Liste nach " . htmlspecialchars((string)$TableEntry["Name"], ENT_QUOTES, 'UTF-8') . " sortieren'>&#8597;</a>";
				}
				echo "</th>" .chr(10);
			}
			echo "<th data-col='__add'></th><th data-col='__edit'></th>";
			echo "</tr>" .chr(10);

			//echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
			$showGeoJG = !empty($TableSetup["GeoJG"]["Show"]);
			$showGeoGde = !empty($TableSetup["GeoGde"]["Show"]);
			$showGeoWrn = !empty($TableSetup["GeoWrn"]["Show"]);
			//echo $showfrom . ", " . $showto . ", " . $MaxSelectedEntries . ", " . $AnzLinesPPage . ", " . $MaxPages;
//			foreach ($Feldkatalog as $Key => $Region) {
			foreach ($FeldkatalogList as $Key => $Feld) {
				if (!is_array($Feld)) {
					continue;
				}
				$feldJg = rkrk_list_fstr($Feld, "JG");
				if (!empty($regionFilterNarrow) && !isset($RegionsSelected[$feldJg])) {
					continue;
				}
				if (!isset($pageSet[$Key])) {
					continue;
				}
				$listNr = $Feld["ListNr"] ?? '';
				if (is_array($listNr) || is_object($listNr)) {
					$listNr = '';
				}
				if ($listNr !== "" && $listNr !== null) {
							$fId = rkrk_list_fstr($Feld, "FeldID");
							$fName = rkrk_list_fstr($Feld, "Feldname");
							$fFlur = rkrk_list_fstr($Feld, "Flurname");
							$fGeoId = rkrk_list_fstr($Feld, "GrundstueckID");
							$fGde = rkrk_list_fstr($Feld, "Gemeinde");
							$fBetrieb = rkrk_list_fstr($Feld, "Betrieb");
							$fFrei = rkrk_list_fstr($Feld, "Freigabe");
							$fWarn = rkrk_list_fstr($Feld, "Warnung");
							$fJaeger = rkrk_list_fstr($Feld, "Jaeger");
							$fBem = rkrk_list_fstr($Feld, "Bemerkung");
							$fFlug = rkrk_list_fstr($Feld, "Flug");
							$fFunde = rkrk_list_fstr($Feld, "Funde");
							$fBauer = rkrk_list_fstr($Feld, "Bauer");
							$fNotiz = rkrk_list_fstr($Feld, "Notiz");
							$fExport = rkrk_list_fstr($Feld, "Export");
							$fSelect = rkrk_list_fstr($Feld, "Select");
							$CoreFileName = rkrk_list_fstr($Feld, "CorName");
							$searchRep = (isset($searchSDZ2) && (is_array($searchSDZ2) || is_string($searchSDZ2))) ? $searchSDZ2 : array();
							$replaceRep = (isset($replaceSDZ2) && (is_array($replaceSDZ2) || is_string($replaceSDZ2))) ? $replaceSDZ2 : array();
							if (is_array($searchRep) && is_string($replaceRep)) {
								$replaceRep = array($replaceRep);
							} elseif (is_string($searchRep) && is_array($replaceRep)) {
								$searchRep = array($searchRep);
							}
							$CoreFileNameWoULM = str_replace($searchRep, $replaceRep, $CoreFileName);
							$LenFeldID = strlen($fId);
							$katRow = rkrk_list_kat_row($KatFileList, $feldJg, $fId);
							$fileId = rkrk_list_fstr($katRow, "FeldID");
							$katName = rkrk_list_fstr($katRow, "Feldname");
							$katGeo = rkrk_list_fstr($katRow, "GrundstueckID");
							$jgHasFiles = ($feldJg !== '' && !empty($KatFileList[$feldJg]['__hasfiles']));
							$missingFile = ($fileId === '' && $jgHasFiles);
							$isNew = rkrk_list_is_neu($Feld);
							$isChanged = rkrk_list_changed_since($Feld, $rkrkChangedSince);
							$aendShow = rkrk_list_fstr($Feld, 'Aenderung');
							$updShow = rkrk_list_fstr($Feld, 'Update');
							echo "<tr class='rkrkedit' data-jg='" . htmlspecialchars($feldJg, ENT_QUOTES, 'UTF-8') . "' data-new='" . ($isNew ? '1' : '0') . "' data-changed='" . ($isChanged ? '1' : '0') . "' data-aenderung='" . htmlspecialchars($aendShow, ENT_QUOTES, 'UTF-8') . "' data-update='" . htmlspecialchars($updShow, ENT_QUOTES, 'UTF-8') . "'>" .chr(10);
							// Number
							echo "<td>" . chr(10);
							if ($EnableDelete) {
								// delete
								$enableDelQ = htmlspecialchars((string)($_GET["EnableDelete"] ?? 'off'), ENT_QUOTES, 'UTF-8');
								$dbwQ = htmlspecialchars((string)($_GET["DBW"] ?? ''), ENT_QUOTES, 'UTF-8');
								if (GetAccess("DeleteData")) {
									echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . $mdbParam . "&ActPage=" . $ActPage . "&FeldID=" . rawurlencode((string)$Feld["FeldID"]) . "&delete=Y&EnableDelete=" . $enableDelQ . "&DBW=" . $dbwQ . $regUrl . "' height='" . $iconHeight . "' title='Feld " . htmlspecialchars($fId, ENT_QUOTES, 'UTF-8') . " löschen'>" .chr(10);
								} else {
									echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . $mdbParam . "&ActPage=" . $ActPage . "&FeldID=" . rawurlencode((string)$Feld["FeldID"]) . "&delete=Y&EnableDelete=" . $enableDelQ . $regUrl . "' height='" . $iconHeight . "'  title='Feld " . htmlspecialchars($fId, ENT_QUOTES, 'UTF-8') . " löschen'>" .chr(10);
								}
							}
							$Inputfieldname = "Number".$i;
							echo (int)$showfrom + (int)$pageline;
							echo InputField($Inputfieldname, $i, 0, "left", "hidden");
							// JG
							$Inputfieldname = "JG".$i;
							echo InputField($Inputfieldname, $feldJg, 0, "left", "hidden");
							echo "</td>";
							foreach ($FlyFileSetting as $FileKey => $FileType) {
								if ((string)$FileKey === 'LCHM' || !is_array($FileType)) {
									continue;
								}
								$fileShow = function_exists('rkrk_explode_csv')
									? rkrk_explode_csv($FileType["Show"] ?? '')
									: explode(",", (string)($FileType["Show"] ?? ''));
								if (in_array("edit_felddatenliste", $fileShow, true)){
									echo "<td>" .chr(10);
									if (GetAccess($FileType["Access"] ?? '')){
										$rawLinks = $Feld[$FileType["XMLLabel"]] ?? '';
										$LinkData = function_exists('rkrk_explode_csv')
											? rkrk_explode_csv($rawLinks)
											: explode(",", is_array($rawLinks) ? implode(",", $rawLinks) : (string)$rawLinks);
										foreach ($LinkData as $Link) {
											if ($Link === '' || !is_string($Link)) {
												continue;
											}
											$meaning = GetMeaningFilename($Link);
											$Flightheight = is_array($meaning) ? (string)($meaning[4] ?? '') : '';
											if (file_exists($IconLoc . "/" . $FileType["icon"] . $Flightheight . ".png")) {
												$Picture= $IconLoc . "/" . $FileType["icon"] . $Flightheight . ".png";
											} else {
												$Picture= $IconLoc . "/" . $FileType["icon"] . "xx" . ".png";
											}
											$href = htmlspecialchars($RKRKLocalLoactionMissionFiles . $Link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
											$title = htmlspecialchars(rkrk_list_fstr($FileType, "Text") . " " . $fId . " - " . $Link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
											echo "<a href='" . $href . "' download><img src='" . htmlspecialchars($Picture, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "' height='" . $ButtonHeight * .8 ."' title='" . $title . "'></a> <wbr>" . chr(10);
										}
									}
									echo "</td>";
								}
							}


/*
							// Download Litchi
							echo "<td>" .chr(10);
							if (GetAccess("MapDownloadLitchi")){
								$LinkData = explode(",", $Feld["LichtiFile"]);
								foreach ($LinkData as $Link) {
									foreach ($OptionFlughoehe as $Flughoehe) {
										if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
											$Flightheight= $Flughoehe["Opt"];
											$Picture =  $IconLoc . "\litchi" . $Flughoehe["Opt"] . ".png";
										}
									}
									if ($Link !="") {
										echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'><img src='" . $Picture . "' height='" . $ButtonHeight * .8 ."' title='Litchi File " . $Feld["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
									}
								}
							}
							echo "</td>";
							echo "<td>";
							// Download Json
							if (GetAccess("MapDownloadJSON")){
								$LinkData = explode(",", $Feld["JasonFile"]);
								foreach ($LinkData as $Link) {
									foreach ($OptionFlughoehe as $Flughoehe) {
										if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
											$Flightheight= $Flughoehe["Opt"];
											$Picture =  $IconLoc . "\json" . $Flughoehe["Opt"] . ".png";
										}
									}
									if ($Link !="") {
										echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'  download target='_blank'><img src='" . $Picture . "' height='" . $ButtonHeight * .8 ."' title='Jason File " . $Feld["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
									}
								}
							}
							echo "</td>";
*/
							
							// FeldID
							if (!empty($TableSetup["FeldID"]["Show"])) {
								if (!$missingFile && ($fileId === '' || $fileId === $fId)) {
									echo "<td>" .chr(10);
									echo htmlspecialchars($fId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
								} else {
									echo "<td   style='background-color:" . $colorNOK . "'>" .chr(10);
									echo htmlspecialchars($fId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
									echo "<span style='color:" . $colorNOKtext . "'>  (". htmlspecialchars($fileId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .") </span>" .chr(10);
								}
								$Inputfieldname = "FeldID".$i;
								echo InputField($Inputfieldname, $fId, 5, "left", "hidden");
								echo "</td>" . chr(10);
							}
							if ($showGeoJG) {
								if (true) {
									$zJg = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) {
										$zRaw = $Feld["Grundstueckdaten"]["Zentrum"] ?? '';
										$zJg = is_array($zRaw) ? trim(implode(',', $zRaw)) : trim((string)$zRaw);
									}
									$GeoJgd = GetJgdOfPOint($zJg);
									//echo $GeoJgd;
									//$GeoGde= "Gde";
									$jgPrefix = $feldJg !== '' ? $feldJg : ((strpos($fId, '-') !== false) ? substr($fId, 0, strpos($fId, '-')) : $fId);
									if ($GeoJgd != "" && strcasecmp($jgPrefix, (string)$GeoJgd) !== 0) {
											  echo "<td bgcolor='" . $colorNOK . "'>";
											  $textcolor = $colorNOKtext;
									} elseif ($GeoJgd != "") {
											  echo "<td>";
											  $textcolor = $colorOKtext;
									} else {
										echo "<td>";
										$textcolor = "#333";
									}	
									echo "<span style='color:" . $textcolor . "'>" . $GeoJgd. "</span>";
									echo "</td>";
								}
							}
							// Feldname
							if (!empty($TableSetup["Feldname"]["Show"])) {
								if (rkrk_list_name_matches_file($katName, $fName, $fFlur)) {
									echo "<td>" .chr(10);
									echo htmlspecialchars($fName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
								} else {
									echo "<td  style='background-color:" . $colorNOK . "'>" .chr(10);
									echo htmlspecialchars($fName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
									echo "<span style='color:" . $colorNOKtext . "'>  (". htmlspecialchars($katName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .") </span>" .chr(10);
								}
								$Inputfieldname = "Feldname".$i;
								echo InputField($Inputfieldname, $fName, 15, "left", "hidden");
								echo "</td>";
							}
							// Flurname
							if (!empty($TableSetup["Flurname"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Flurname".$i;
								echo InputField($Inputfieldname, $fFlur, 22);
								echo "</td>" . chr(10);
							}
							// GrundstückID
							if (!empty($TableSetup["GrundstueckID"]["Show"])) {
								if ($katGeo === '' || $katGeo === $fGeoId) {
									echo "<td >" .chr(10);
									echo htmlspecialchars($fGeoId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
								} else {
									echo "<td  style='background-color:" . $colorNOK . "'>" .chr(10);
									echo htmlspecialchars($fGeoId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
									echo "<span style='color:" . $colorNOKtext . "'>  (". htmlspecialchars($katGeo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .") </span>" .chr(10);
								}
								$Inputfieldname = "GrundstueckID".$i;
								echo InputField($Inputfieldname, $fGeoId, 0, "left", "hidden");
								echo "</td>" . chr(10);
							}
							// Gemeinde
							if (!empty($TableSetup["Gemeinde"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Gemeinde".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $fGde , isset($OptionGemeinde) && is_array($OptionGemeinde) ? $OptionGemeinde : array() );
								echo "</td>" . chr(10);
							}
							if ($showGeoGde) {
								if (true) {
									$zGeo = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) {
										$zRaw = $Feld["Grundstueckdaten"]["Zentrum"] ?? '';
										$zGeo = is_array($zRaw) ? trim(implode(',', $zRaw)) : trim((string)$zRaw);
									}
									$GeoGde = GetGdeOfPOint($zGeo);
									//echo $GeoGde;
									//$GeoGde= "Gde";
									if ($GeoGde != "" && $fGde !== $GeoGde) {
											  echo "<td bgcolor='" . $colorNOK . "'>";
											  $textcolor = $colorNOKtext;
									} elseif ($GeoGde != "") {
											  echo "<td>";
											  $textcolor = $colorOKtext;
									} else {
										echo "<td>";
										$textcolor = "#333";
									}	
									echo "<span style='color:" . $textcolor . "'>" . $GeoGde. "</span>";
									echo "</td>";
								}
							}	
							// Betrieb
							if (!empty($TableSetup["Betrieb"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Betrieb".$i;
								echo InputField($Inputfieldname, $fBetrieb, 10);
								echo "</td>" . chr(10);
							}
							// Freigabe
							if (!empty($TableSetup["Freigabe"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Freigabe".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $fFrei , isset($OptionFrei) && is_array($OptionFrei) ? $OptionFrei : array() );
								echo "</td>";
							}
							// Warnung
							if (!empty($TableSetup["Warnung"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Warnung".$i;
								echo InputField($Inputfieldname, $fWarn, 12);
								// Test von Eingabefilter
								//$action = 'onchange="CheckWarnEntry(document.form.'.$Inputfieldname.')"';
								//echo InputField($Inputfieldname, $Feld["Warnung"], 12, "left", "", "", $action);
								echo "</td>";
							}
							if ($showGeoWrn) {
								if (true) {
									$zWrn = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) {
										$zRaw = $Feld["Grundstueckdaten"]["Zentrum"] ?? '';
										$zWrn = is_array($zRaw) ? trim(implode(',', $zRaw)) : trim((string)$zRaw);
									}
									$GeoWarnFlgZ = GetWarnungFlgZOfPOint($zWrn);
									//echo $GeoWarnFlgZ;
									if ($GeoWarnFlgZ != "" && $fWarn !== $GeoWarnFlgZ) {
											  echo "<td bgcolor='" . $colorNOK . "'>";
											  $textcolor = $colorNOKtext;
									} elseif ($GeoWarnFlgZ != "") {
											  echo "<td>";
											  $textcolor = $colorOKtext;
									} else {
										echo "<td>";
										$textcolor = "#333";
									}	
									echo "<span style='color:" . $textcolor . "'>" . $GeoWarnFlgZ. "</span>";
									echo "</td>";
								}
							}	
							/*
							echo "<select type='text' id='". $Inputfieldname . "' name='" . $Inputfieldname ."' multiple>";
								foreach ($OptionWarning as $opt) {
									if (like($feld->Warnung , $opt["Opt"])) {
										echo "<option value='". $opt["Opt"] . "' selected>". $opt["Opt"] . "</option>".chr(10);
									} else {
										echo "<option value='". $opt["Opt"] . "'>". $opt["Opt"] . "</option>".chr(10);
									}
								}
							echo "</select>";
							*/
							// Jaeger
							if (!empty($TableSetup["Jaeger"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Jaeger".$i;
								echo SelectField($Inputfieldname, $fJaeger , isset($OptionJaeger) && is_array($OptionJaeger) ? $OptionJaeger : array() );
								echo "</td>";
							}
							// Bemerkung
							if (!empty($TableSetup["Bemerkung"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Bemerkung".$i;
								echo InputField($Inputfieldname, $fBem,45);
								echo "</td>";
							}
							// Flug
							if (!empty($TableSetup["Flug"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Flug".$i;
								echo InputField($Inputfieldname, $fFlug, 5);
								echo "</td>";
							}
							// Funde
							if (!empty($TableSetup["Funde"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Funde".$i;
								echo InputField($Inputfieldname, $fFunde, 5);
								echo "</td>";
							}
							// Bauer
							if (!empty($TableSetup["Bauer"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Bauer".$i;
								//echo InputField($Inputfieldname, $Feld["Bauer"], 30);
								echo InputTextarea($Inputfieldname, $fBauer, 12, 1, 30);
								echo "</td>";
							}
							// Notiz
							if (!empty($TableSetup["Notiz"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Notiz".$i;
								echo InputField($Inputfieldname, $fNotiz, 20);
								echo "</td>";
							}
							// Export
							if (!empty($TableSetup["Export"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Export".$i;
								echo CheckField($Inputfieldname, ($fExport=="Y"));
								echo "</td>";
							}
							// Select
							if (!empty($TableSetup["Select"]["Show"])) {
								echo "<td>" .chr(10);
								$Inputfieldname = "Select".$i;
								echo CheckField($Inputfieldname, ($fSelect=="Y"));
								echo "</td>";
							}
							// Edit
							echo "<td>" .chr(10);
							echo " <a href='rkrk_fluege.php?add=Y&AddFlugID=" . rawurlencode($fId) . "&AddFlugToDate=" . date("Y-m-d", strtotime('tomorrow')). "&" . $mdbParam . "' target='_editfluege'><img src='" . $IconLoc . "/addflug.png' height='" . $iconHeight . "px' title='Flugfeld " . htmlspecialchars($fId, ENT_QUOTES, 'UTF-8') . " für Morgen hinzugfügen'></a>";
							echo "</td>";
							echo "<td>" .chr(10);
							echo " <a href='rkrk_edit_felddaten.php?FeldID=" . rawurlencode($fId) . "&" . $mdbParam . "' target='_editfield'><img src='" . $IconLoc . "/edit.png' height='" . $iconHeight . "px' title='Feld editieren'></a>";
							echo "</td>";
							echo "</tr>";
						
							// -----------
							// Ende Liste
							// -----------
						$i++;
						$pageline++;
						}
			}
			echo "</table></div></div>" .chr(10);
			echo "<script>
(function(){
  var boxes = document.querySelectorAll('.rkrk-adr-cols input[data-col]');
  var hidden = [];
  for (var i = 0; i < boxes.length; i++) {
    if (!boxes[i].checked) hidden.push(boxes[i].getAttribute('data-col'));
  }
  try {
    var key = 'rkrk-adr-ui-felder';
    var prev = JSON.parse(localStorage.getItem(key) || '{}') || {};
    prev.hidden = hidden;
    localStorage.setItem(key, JSON.stringify(prev));
  } catch (e) {}
})();
</script>" .chr(10);
			echo "<script src='scripts/rkrk_adr_grid.js?v=20261012rows1'></script>" .chr(10);
			echo "</div>" . chr(10);
			echo "<div id='Info' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<div id='Freigabe' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<div id='Notizen' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<table>" .chr(10);
			echo "<tr><td>" .chr(10);
			
			echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);
			PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, '&' . $mdbParam . '&Sortieren=' . rawurlencode((string)$ColSort) . $regUrl);
			echo "</td></tr>" .chr(10);
			echo "</table>" .chr(10);
		echo "</form>" .chr(10);
	Legends();
echo "</br>";
/*
?>
<script type='text/javascript'>
<?php
echo "var WarnFilter = /(". implode("|",array_keys($OptionWarning)) . ")/;\n";
echo "var WarnElements = '". implode(",",array_keys($OptionWarning)) . "';\n";
?>
function CheckWarnEntry(inputtxt) 
{ 
	var filter = WarnFilter;
	var message = document.getElementById("change_warning_message")
	if(inputtxt.value.match(filter)) 
	{ 
		message.style.color = "green";
		message.innerHTML= "";
		//alert('Correct, try another...')
		return true;
	}
	else
	{ 
		message.style.color = "red";
		message.innerHTML= " Eingabe inkorrekt - kann nur folgene Elemente enthalten: " + WarnElements;
		//alert('Wrong...!')
		return false;
	}
}
</script>
*/
