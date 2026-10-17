<?php
ob_start();
?>
<!--
<!--
  Funktion zum Anzeigen der Felddaten und identifizieren von neuen Files im Flugkatalog
  
--->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
  session_start();
  if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
  }
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_xmlfiles.php');
  //include_once('scripts/rkrk_csvfiles.php');
  include_once('scripts/rkrk_jsonfiles.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_map_functions.php');
  include_once('rkrk_access_control.php'); 
  include_once('scripts/rkrk_screenshot.php'); 
  
	$pagetitle = "Flugkatalog";
	headerpage($pagetitle, false);
	GrantAccess();
	CreateTopMenu();
	echo "  <link rel='stylesheet' href='template/rkrk_felddaten.css?v=20261013pilotmap' type='text/css'>" . chr(10);
	echo "<style>"
		. ".rkrk-felddaten-search td.rkrk-search-actions img,"
		. ".rkrk-felddaten-search td.rkrk-search-actions image,"
		. ".rkrk-felddaten-search td.rkrk-search-actions input[type=\"image\"]{"
		. "box-sizing:border-box !important;"
		. "height:26px !important;"
		. "min-height:26px !important;"
		. "width:auto !important;"
		. "max-height:none !important;"
		. "max-width:none !important;"
		. "padding:2px 10px !important;"
		. "margin:0 2px !important;"
		. "border:0 !important;"
		. "border-radius:0 !important;"
		. "background-color:transparent !important;"
		. "vertical-align:middle !important;"
		. "object-fit:contain;"
		. "}"
		. "</style>" . chr(10);
	echo "<div class='rkrk-felddaten'>" . chr(10);
	PrintRKRKHeader($pagetitle);


	// ===========================================
	// Definitions
	// ===========================================
		$fileoutput = "";
		$debugoutput = true;
		$geofence = true; // Geofence enabled
		global $RegionSettings;
		$PicHeight1 = 250;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","110%", "125%","150%","200%");

		$TableSetup = Array(
							"FeldID" => array ( "Field" => "FeldID",
												"Name" => "FeldID",
												"Type" => "text",
												"Show" => true),
							"Feldname" 	=> array ( "Field" => "Feldname",
												"Name" => "Name",
												"Type" => "text",
												"Show" => true),
							"Flurname" => array ( "Field" => "Flurname",
												"Name" => "Flurname",
												"Type" => "text",
												"Show" => true),
							"GrundstueckID" => array ( "Field" => "GrundstueckID",
												"Name" => "GeoID",
												"Type" => "text",
												"Show" => true),
							"Gemeinde" => array ( "Field" => "Gemeinde",
												"Name" => "Gde",
												"Type" => "text",
												"Show" => true),
							"Betrieb" => array ( "Field" => "Betrieb",
												"Name" => "Betrieb",
												"Type" => "text",
												"Show" => true),
							"Freigabe" => array ( "Field" => "Freigabe",
												"Name" => "Freigabe",
												"Type" => "text",
												"Show" => true),
							"Warnung" => array ( "Field" => "Warnung",
												"Name" => "Warnung",
												"Type" => "text",
												"Show" => true),
							"Jaeger" => array ( "Field" => "Jaeger",
												"Name" => "Jaeger",
												"Type" => "text",
												"Show" => true),
							"Bemerkung" => array ( "Field" => "Bemerkung",
												"Name" => "Bemerkung",
												"Type" => "text",
												"Show" => true),
							"Flug" => array ( "Field" => "Flug",
												"Name" => "Flug",
												"Type" => "text",
												"Show" => true),
							"Funde" => array ( "Field" => "Funde",
												"Name" => "Funde",
												"Type" => "text",
												"Show" => true),
							"Bauer" => array ( "Field" => "Bauer",
												"Name" => "Bauer",
												"Type" => "text",
												"Show" => true),
							"Notiz" => array ( "Field" => "Notiz",
												"Name" => "Notiz",
												"Type" => "text",
												"Show" => true),
							"SuchfeldBild" => array ( "Field" => "SuchfeldBild",
												"Name" => "SuchfeldBild",
												"Type" => "text",
												"Show" => true),
							"FlugplanBild" => array ( "Field" => "FlugplanBild",
												"Name" => "FlugplanBild",
												"Type" => "text",
												"Show" => true),
							"JasonFile" => array ( "Field" => "JasonFile",
												"Name" => "JasonFile",
												"Type" => "text",
												"Show" => true),
							"KMLFile" => array ( "Field" => "KMLFile",
												"Name" => "KMLFile",
												"Type" => "text",
												"Show" => true),
							"KMZFile" => array ( "Field" => "KMZFile",
												"Name" => "KMZFile",
												"Type" => "text",
												"Show" => ($RKRKMissionSelected != "M2")),
							"LichtiFile" => array ( "Field" => "LichtiFile",
												"Name" => "LichtiFile",
												"Type" => "text",
												"Show" => ($RKRKMissionSelected == "M2")),
							"Export" => array ( "Field" => "Export",
												"Name" => "Export",
												"Type" => "text",
												"Show" => true),
							"Select" => array ( "Field" => "Select",
												"Name" => "Select",
												"Type" => "text",
												"Show" => true),
										);



	$FormSet = Array(
							"NewOnly" => array ( "Field" => "NewOnly",
												"Name" => "Nur neue Felder",
												"Type" => "text",
												"Set" => true),
							"Changed" => array ( "Field" => "Changed",
												"Name" => "Geänderte Felder",
												"Type" => "text",
												"Set" => true),
										);

	$xml_file= "RKRKFelder_N.xml";
	$DefHeightNum=60;
	$SystemInfo = "";
	$DebugInfo = "";
	foreach ($OptionPilot as $PP) {
		//pre_R( $_SESSION[$SessNme_RegisteredInitialen]);
		if ($PP["Opt"]==($_SESSION[$SessNme_RegisteredInitialen] ?? '')) {
			$Pilot= $PP["Opt"];
			//pre_R($PP);
		}	
	}	
	// ===========================================
	// Functions
	// ============================================

// internal Legend
function Legends() {
	global $OptionFrei, $OptionWarning, $OptionGemeinde;
	//pre_r($Feldkatalog);
	echo "</br>";
	echo "<table class='rkrk-felddaten-legend'>";
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
		echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
	}
	echo "</ul>" . chr(10);
	echo "</td><td>";
	echo "<ul>" . chr(10);
	foreach ($OptionWarning as $Key => $Entry) {
		echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
	}
	echo "</td><td>";
	echo "</ul>" . chr(10);
	echo "<ul>" . chr(10);
	$i= 0;
	foreach ($OptionGemeinde as $Key => $Entry) {
		if ($Entry["Opt"]!="") {
			echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
			//echo $i%5 . "<br>";
			if (($i%6)==5) {
				echo "</td><td>";
			}
			$i++;
		}	
	}
	echo "</ul>" . chr(10);
	echo "</td></tr>";
	echo "</table>";
	
}



// ===========================================
// HEADER FORM
// ===========================================

$EnabledRegion = Array();

//form to change setting

echo "<form name='form' method='POST' action='". htmlspecialchars($_SERVER["PHP_SELF"]) . "' enctype='multipart/form-data'>" . chr(10);
$i= 0;
$NoSelection = true;
// enable all regions
foreach ($RegionSettings as $Reg ) {
	if (("NO" != $Reg["Reg"]) && ("ALL" !=$Reg["Reg"])) {
		$checked = "checked";
		$EnabledRegion[$Reg["Reg"]] = 1;
		$NoSelection = false;
	}
}

//echo $i . " " . $KMLFileSettings->name . chr(10)
//Felder konfiguration
	$ShowFieldsConfiguration = "";
	$ShowFieldsConfiguration .= "	<div class='rkrk-feldwahl'>". chr(10);
	$ShowFieldsConfiguration .= "		<span class='rkrk-feldwahl-label'>Felder:</span>". chr(10);
	$ShowFieldsConfiguration .= "		<div class='rkrk-sels rkrk-reg-pills'>". chr(10);

	foreach ($TableSetup as $Feld ) {
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
		//if (isset($_POST[$Feld["Field"] . "_en"])) {
			//echo $Feld["Field"] . " ". ($_POST[$Feld["Field"] . "_en"]) . "/ ";
			//echo "post  ";
			if (($_POST[$Feld["Field"] . "_en"] ?? '')=="on") {
				$checked = "checked";
				$TableSetup[$Feld["Field"]]["Show"] = true;
			} else {
				$checked = "";
				$TableSetup[$Feld["Field"]]["Show"] = false;
			}
			$ShowFieldsConfiguration .=  '			<label class="rkrk-sel rkrk-reg-pill"><input type="checkbox" name="'. $Feld["Field"] . "_en" . '" ' . $checked . '>'. $Feld["Name"]. '</label>'. chr(10);
		}	else {
			//echo "default settings  ";
			//echo $Feld["Field"] . " ". $Feld["Show"] . "/ ";
			if ($Feld["Show"]==true) {
				$checked = "checked";
			} else {
				$checked = "";
			}
			$ShowFieldsConfiguration .=  '			<label class="rkrk-sel rkrk-reg-pill"><input type="checkbox" name="'. $Feld["Field"] . "_en" . '" ' . $checked . '>'. $Feld["Name"]. '</label>'. chr(10);
		}
	}
	$ShowFieldsConfiguration .=  "		</div>". chr(10);
	$i++;

	$ShowFieldsConfiguration .=  "	</div>";
	$ShowFieldsConfiguration .=  '<input type="hidden" name="Act" id="Act" value="save" />'. chr(10);
//echo '</form>';
	$ShowFieldsConfiguration .= '<br>';
//pre_r($EnabledRegion);
//pre_r($RegionSettings);
//pre_r($TableSetup);
//pre_r($FormSet);


//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (XML)
//////////////////////////////////////////////

$OptionJaeger = Array();
$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
/*
//echo $xmlMissionLocation.$RKRKFelderfileXML;
if(file_exists($xmlMissionLocation.$RKRKFelderfileXML)) {
	//$xml_felder = simplexml_load_file($xmlMissionLocation.$RKRKFelderfileXML);     //Lädt die XML-Datei in ein Objekt
	//echo "file exists <br>";
	$xml_felder = simplexml_load_string(utf8_encode(htmlentities_decode(file_get_contents($xmlMissionLocation.$RKRKFelderfileXML))));
	if($xml_felder && false) {    //Prüfen, ob Datei Fehler enthielt
		//Print Array Content
		$objJsonDocument = json_encode($xml_felder);
		$FeldKatalog = json_decode($objJsonDocument, TRUE);
		pre_r($FeldKatalog);
	}
} else {
	$fileloaded = false;
	echo '        <p>Die Datei names '. $felder_file .' konnte nicht geöffnet werden</p>';
}

$Feldkatalog = TransferFeldKatFromXMLtoArr ($xml_felder, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
*/

///////////////////////////////////////
// Process Input Data
///////////////////////////////////////
if (isset($_GET["FeldID"]) && trim((string)$_GET["FeldID"]) !== '') {
	$FeldIDWahl = $_GET["FeldID"];
} elseif (isset($_GET["Feld"]) && trim((string)$_GET["Feld"]) !== '') {
	$FeldIDWahl = $_GET["Feld"];
} elseif (isset($_POST["FeldIDWahl"])) {
	$FeldIDWahl = $_POST["FeldIDWahl"];
} else {
	$FeldIDWahl = '';
}

$rkrkFeldImKatalog = is_array(rkrk_felddaten_locate_entry($Feldkatalog, $FeldIDWahl));
$rkrkFeldFromGet = isset($_GET["FeldID"]) && trim((string)$_GET["FeldID"]) !== '';
if ($FeldIDWahl !== '' && !$rkrkFeldImKatalog && !$rkrkFeldFromGet) {
	$FeldIDWahl = '';
}

if (isset($_GET["Search"])) {
	$FeldNamenWahl = $_GET["Search"];
} else {
	if (isset($_POST["FeldNamenWahl"])) {
		$FeldNamenWahl = $_POST["FeldNamenWahl"];
	} else	{
		$FeldNamenWahl = '';
	}
}

if (($FeldIDWahl == '') &&
   ($FeldNamenWahl == '')) {
		$NoSelection = true;
   } else {
		$NoSelection = false;
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

if (isset($_GET["NeuesFeld"])) { 
	$newfield= $_GET["NeuesFeld"];
} else {
	$newfield= "";
}
$rkrkJsonShot = null;

if (isset($_GET["deletefile"])) { 
	$deletefile= $_GET["deletefile"];
} else {
	$deletefile= false;
}

if (isset($_GET["addentry"])) { 
	$addentry= $_GET["addentry"];
} else {
	$addentry= false;
}

if (isset($_GET["cleanandadd"])) { 
	$cleanandadd= $_GET["cleanandadd"];
} else {
	$cleanandadd= false;
}


if (isset($_GET["addnadaptentry"])) { 
	$addnadaptentry= $_GET["addnadaptentry"];
} else {
	$addnadaptentry= false;
}

if (isset($_GET["deleteentry"])) { 
	$deleteentry= $_GET["deleteentry"];
} else {
	$deleteentry= false;
}
if (isset($_GET["transfer"])) { 
	$transfer= $_GET["transfer"];
} else {
	$transfer= false;
}

if (isset($_GET["XMLLabel"])) { 
	$XMLLabel= $_GET["XMLLabel"];
} else {
	$XMLLabel= false;
}


if (isset($_GET["updatefile"])) { 
	$updatefile= $_GET["updatefile"];
} else {
	$updatefile= false;
}

if (isset($_GET["File"])) { 
	$updateFileName= $_GET["File"];
	$Flughoehe = GetMeaningFilename($updateFileName)[4];
} else {
	$updateFileName= false;
}

if (isset($_GET["NewFileName"])) { 
	$NewFileName= $_GET["NewFileName"];
} else {
	$NewFileName= false;
}

// Rotes X: der Dateiname liegt im POST-Körper. Ein input type=image mit
// formmethod=get wirft die Query (deletefile, File, XMLLabel) weg.
if (isset($_POST["rkrkPicDelete"]) && is_string($_POST["rkrkPicDelete"])) {
	$rkrkPicParts = explode("|", $_POST["rkrkPicDelete"], 2);
	if (count($rkrkPicParts) === 2) {
		$rkrkPicLabel = rawurldecode($rkrkPicParts[0]);
		$rkrkPicFile = rawurldecode($rkrkPicParts[1]);
		if ($rkrkPicFile !== "" && in_array($rkrkPicLabel, array("SuchfeldBild", "FlugplanBild"), true)) {
			$deletefile = "Y";
			$XMLLabel = $rkrkPicLabel;
			$updateFileName = $rkrkPicFile;
			$_GET["deletefile"] = "Y";
			$_GET["XMLLabel"] = $rkrkPicLabel;
			$_GET["File"] = $rkrkPicFile;
		}
	}
}


function rkrk_felddaten_locate_entry($Feldkatalog, $feldId) {
	$feldId = trim((string)$feldId);
	if ($feldId === '' || !is_array($Feldkatalog)) {
		return null;
	}
	foreach ($Feldkatalog as $jg => $region) {
		if (!is_array($region)) {
			continue;
		}
		if (isset($region[$feldId]) && is_array($region[$feldId])) {
			return array((string)$jg, (string)$feldId);
		}
		foreach ($region as $key => $row) {
			if (!is_array($row) || !isset($row["FeldID"]) || is_array($row["FeldID"])) {
				continue;
			}
			if (trim((string)$row["FeldID"]) === $feldId) {
				return array((string)$jg, (string)$key);
			}
		}
	}
	return null;
}

function rkrk_felddaten_shot_area_text($area) {
	if (is_array($area)) {
		$parts = array();
		array_walk_recursive($area, function ($v) use (&$parts) {
			if (is_scalar($v) && trim((string)$v) !== '') {
				$parts[] = trim((string)$v);
			}
		});
		return trim(implode(' ', $parts));
	}
	return trim((string)$area);
}

function rkrk_felddaten_shot_job($feldId, $area, $mdb, $flaeche = null) {
	$feldId = trim((string)$feldId);
	// ZG6-278 ist Buchstaben und Zahl, nicht nur Zahl-Zahl.
	if (!preg_match('/^[A-Za-z0-9]+-\d+$/', $feldId)) {
		return null;
	}
	$text = rkrk_felddaten_shot_area_text($area);
	$nums = array();
	preg_match_all('/-?\d+(?:\.\d+)?/', $text, $nums);
	$n = count($nums[0] ?? array());
	if ($n < 6) {
		$flaecheText = str_replace(',', '.', rkrk_felddaten_shot_area_text($flaeche));
		if ($flaecheText === '' || !is_numeric($flaecheText) || (float)$flaecheText <= 0) {
			return null;
		}
	}
	global $RKRKExternalLoactionMissionFiles;
	$ga = isset($_GET['GA']) ? trim((string)$_GET['GA']) : '';
	return array(
		'id' => $feldId,
		'feld_id' => $feldId,
		'mdb' => (string)$mdb,
		'ga' => $ga,
		'fileBase' => (string)($RKRKExternalLoactionMissionFiles ?? ''),
		'mapBase' => 'rkrk_map.php',
		'apiUrl' => 'fe/api_field_editor.php?KAT=&MDB=' . rawurlencode((string)$mdb),
	);
}

function rkrk_felddaten_pic_line($name, $kind) {
	$safe = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
	if ($kind === 'such' || $kind === 'flug') {
		return "<span class='rkrk-pic-name' data-rkrk-picname='" . $kind . "'>" . $safe . "</span>";
	}
	return (string)$name . "<br>";
}

function rkrk_felddaten_fold_filename($name) {
	$base = basename(str_replace('\\', '/', trim((string)$name)));
	if ($base === '') {
		return '';
	}
	if (class_exists('Normalizer')) {
		$norm = Normalizer::normalize($base, Normalizer::FORM_C);
		if (is_string($norm) && $norm !== '') {
			$base = $norm;
		}
	}
	$base = strtr($base, array(
		'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
		'Ä' => 'a', 'Ö' => 'o', 'Ü' => 'u',
		'à' => 'a', 'á' => 'a', 'â' => 'a',
		'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
		'À' => 'a', 'Á' => 'a', 'Â' => 'a',
		'É' => 'e', 'È' => 'e', 'Ê' => 'e',
	));
	if (function_exists('mb_strtolower')) {
		return mb_strtolower($base, 'UTF-8');
	}
	return strtolower($base);
}

function rkrk_felddaten_same_file($a, $b) {
	$fa = rkrk_felddaten_fold_filename($a);
	$fb = rkrk_felddaten_fold_filename($b);
	return $fa !== '' && $fa === $fb;
}

function rkrk_felddaten_delete_input($src, $height, $title, $self, $actPage, $feldId, $jg, $fileName, $xmlLabel, $picture, $dbw = null) {
	$q = 'ActPage=1'
		. '&FeldID=' . rawurlencode((string)$feldId)
		. '&JG=' . rawurlencode((string)$jg);
	if ($dbw !== null && $dbw !== '') {
		$q .= '&DBW=' . rawurlencode((string)$dbw);
	}
	$payload = rawurlencode((string)$xmlLabel) . '|' . rawurlencode((string)$fileName);
	$action = htmlspecialchars((string)$self, ENT_QUOTES, 'UTF-8') . '?' . $q;
	return "<button type='submit' name='rkrkPicDelete' value='" . htmlspecialchars($payload, ENT_QUOTES, 'UTF-8') . "'"
		. " formmethod='post' formaction='" . $action . "'"
		. " title='" . htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') . "'"
		. " style='background:transparent;border:0;padding:0;margin:0;vertical-align:middle;cursor:pointer;'>"
		. "<img src='" . htmlspecialchars((string)$src, ENT_QUOTES, 'UTF-8') . "' height='" . htmlspecialchars((string)$height, ENT_QUOTES, 'UTF-8') . "' alt=''>"
		. "</button>";
}

function rkrk_felddaten_redirect_same_field($feldId) {
	$feldId = trim((string)$feldId);
	if ($feldId === '') {
		return;
	}
	$q = array(
		'FeldID' => $feldId,
		'ActPage' => '1',
	);
	if (isset($_GET['DBW']) && trim((string)$_GET['DBW']) !== '') {
		$q['DBW'] = trim((string)$_GET['DBW']);
	}
	if (isset($_GET['GA']) && trim((string)$_GET['GA']) !== '') {
		$q['GA'] = trim((string)$_GET['GA']);
	}
	if (isset($_GET['MDB']) && trim((string)$_GET['MDB']) !== '') {
		$q['MDB'] = trim((string)$_GET['MDB']);
	}
	$self = (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] !== '') ? $_SERVER['PHP_SELF'] : 'rkrk_edit_felddaten.php';
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Location: ' . $self . '?' . http_build_query($q), true, 303);
	exit;
}

function rkrk_felddaten_pic_entry($nameHtml, $icons, $kind, $color = '') {
	if ($kind === 'such' || $kind === 'flug') {
		$name = ($color !== '') ? "<span style='color: " . $color . ";'>" . $nameHtml . "</span>" : $nameHtml;
		return "<div class='rkrk-pic-file' style='display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;gap:6px;margin:0 0 8px;max-width:100%;'><span class='rkrk-pic-actions'>" . $icons . "</span>" . $name . "</div>";
	}
	if ($color !== '') {
		return $icons . "<span style='color: " . $color . ";'>" . $nameHtml . "</span>";
	}
	return $icons . $nameHtml;
}

function GetNewFilename($FeldID, $FileName, $Height="", $Ext="") {
	$FileMeaning = GetMeaningFilename($FileName);
	if ($Ext=="") {
		$FileActualExt = pathinfo($FileName, PATHINFO_EXTENSION);
	} else {
		$FileActualExt=  $Ext;
	}
	
	if ($Height=="") {
		return $FeldID . "_" . $FileMeaning[7] . "_" .  $FileMeaning[4] . "_" . $FileMeaning[5] . "_" . $FileMeaning[6] . "." . $FileActualExt;
	} else {
		return $FeldID . "_" . $FileMeaning[7] . "_" .  $Height . "_" . $FileMeaning[5] . "_" . $FileMeaning[6] . "." . $FileActualExt;
	}
}

	//echo "Start:<br>";
	//pre_R($Feldkatalog[$_GET["JG"]][$FeldIDWahl]["SuchfeldBild"]);
	//pre_R($Feldkatalog[$_GET["JG"]][$FeldIDWahl]["FlugplanBild"]);
	//pre_R($Feldkatalog[$_GET["JG"]][$FeldIDWahl]["JasonFile"]);
	//pre_R($Feldkatalog[$_GET["JG"]][$FeldIDWahl]["Flaechendaten"]);


// =================================
// Add or delete entry or delete files
// =================================

$rkrkAddLoc = null;
if (($deleteentry=="Y") or ($deletefile=="Y") or ($addentry=="Y") or ($addnadaptentry=="Y") or ($cleanandadd=="Y") or ($transfer=="Y")) {
	$rkrkAddLoc = rkrk_felddaten_locate_entry($Feldkatalog, $FeldIDWahl);
}
if (is_array($rkrkAddLoc)) {
	$KeepFieldStatus = false;
							
	$JG= $rkrkAddLoc[0];
	$FeldID=$rkrkAddLoc[1];
	$FileName = (string)($_GET["File"] ?? "");
	if ($FileName === '' && is_string($updateFileName ?? null)) {
		$FileName = $updateFileName;
	}
	$file = ($FileName !== "")
		? $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName
		: "";
	
	$rawEntrys = $Feldkatalog[$JG][$FeldID][$XMLLabel] ?? '';
	$Entrys = function_exists('rkrk_explode_csv')
		? rkrk_explode_csv($rawEntrys)
		: explode(",", is_array($rawEntrys) ? implode(",", $rawEntrys) : (string)$rawEntrys);
	//pre_R($Entrys);

	$rkrkPicDelete = in_array((string)$XMLLabel, array("SuchfeldBild", "FlugplanBild"), true)
		&& (($deletefile === "Y") || ($deleteentry === "Y"));
	if ($rkrkPicDelete) {
		// Rotes X: genau diese eine Bilddatei austragen und auf der Platte löschen.
		// Süd/Sud und URL-Schreibweise gelten als dieselbe Datei, _24 und _26 nicht.
		$addentry = false;
		$picDir = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/";
		$wantBase = basename(str_replace("\\", "/", $FileName));
		$diskBase = "";
		if ($wantBase !== "" && is_file($picDir.$wantBase)) {
			$diskBase = $wantBase;
		} elseif ($wantBase !== "" && is_dir($picDir)) {
			$folded = array();
			foreach (scandir($picDir) as $cand) {
				if ($cand === "." || $cand === ".." || !is_file($picDir.$cand)) {
					continue;
				}
				if (rkrk_felddaten_same_file($cand, $wantBase)) {
					$folded[] = $cand;
				}
			}
			if (count($folded) === 1) {
				$diskBase = $folded[0];
			}
		}
		if ($diskBase !== "") {
			$file = $picDir.$diskBase;
			$destDir = (string)$deletefilelocation;
			if ($destDir !== "" && !is_dir($destDir)) {
				@mkdir($destDir, 0775, true);
			}
			if ($destDir !== "" && is_dir($destDir)) {
				rename($file, $destDir.date("YmdHi")."_".basename($file));
			}
		}
		WriteToLog("del", basename($_SERVER['PHP_SELF']), "Felddaten ", $file, $RKRKMissionSelected, "");
		foreach ($Entrys as $key => $Entry) {
			$entryBase = basename(str_replace("\\", "/", trim((string)$Entry)));
			if ($entryBase === $wantBase || ($diskBase !== "" && $entryBase === $diskBase) || rkrk_felddaten_same_file($entryBase, $wantBase)) {
				unset($Entrys[$key]);
			}
		}
	} elseif ($deletefile=="Y") {
		 //move files to delete folder
		$file = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName;
		if(is_file($file)) {  //To check whether the filename is valid
			rename($file, $deletefilelocation.date("YmdHi")."_".basename($file) );
			//echo "deleted " . $file . "<br>";
		 }
	}

	if (!$rkrkPicDelete && (($deleteentry=="Y")  or ($deletefile=="Y"))){
		WriteToLog("del", basename($_SERVER['PHP_SELF']), "Felddaten ",$file, $RKRKMissionSelected,"");
		echo  "Delete: " . "Felddaten ",$file. "<br>";
		foreach ($Entrys as $key => $Entry) {
			if ((trim($Entry)==$RegionSettings[$JG]["Dir"]."/".$FileName)) {
				//echo  $key . ": " . $Entry . "  -- " . $RegionSettings[$JG]["Dir"]."/".$FileName . "<br>";
				unset($Entrys[$key]);
			}
		}
	}
	
	if ($addentry=="Y") {
		WriteToLog("add", basename($_SERVER['PHP_SELF']), "Felddaten ",$file, $RKRKMissionSelected,"");
		$Entrys[]=$RegionSettings[$JG]["Dir"]."/".$FileName;
	}

	if ($addnadaptentry=="Y") {
		//Rename File to new Name
		$file = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName;
		if(is_file($file)) {  //To check whether the filename is valid
			//echo "Rename file:" . $FileName . " to " . $NewFileName . "<br>";
			rename($file, $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$NewFileName);
		 }
		
		WriteToLog("addnadapt", basename($_SERVER['PHP_SELF']), "Felddaten ",$file,$RKRKMissionSelected,"");
		$Entrys[]=$RegionSettings[$JG]["Dir"]."/".$NewFileName;
	}

	if ($transfer=="Y") {
		//transfer JSON to dependant Missions
		//pre_R($MissionDroneTypes[$RKRKMissionSelected]);
		$SystemInfo .= "<br>MissionList:" .  implode(",",$MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"]) . "<br>";
		foreach(explode(",",$MissionDroneTypes[$RKRKMissionSelected]["dependantMissions"]) as $key => $TargetMission) {
			if ($TargetMission!="") {
				$SystemInfo .=  $FeldIDWahl . ": " . $updateFileName . " transfered from " . $RKRKMissionSelected . " to " . $TargetMission . "<br>";
				UpdateXMLDateInOtherMission($JG, $FeldIDWahl, $Feldkatalog, $updateFileName, $RKRKMissionSelected, $TargetMission, true);
			}
		}
	}

	foreach($Entrys as $key => $Entry) {
		if (trim($Entry)=="") {
			unset($Entrys[$key]);
			//echo "lösche: " . $key;
		}
	}
	
	//pre_r($Entrys);

	$Feldkatalog[$JG][$FeldID][$XMLLabel]= implode(",",$Entrys);

	//echo "Entries added<br>";
	//pre_r($Feldkatalog[$JG][$FeldID]);

	if ($cleanandadd) {
		$FileSearch = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FeldIDWahl."*.*";
		$FilesForField = glob($FileSearch);
		//pre_r($FilesForField);

		foreach ($FlyFileSetting as $FileSetting) {
			
			//clean all entries
			$TempStore= $Feldkatalog[$JG][$FeldID][$FileSetting["XMLLabel"]];
			$Feldkatalog[$JG][$FeldID][$FileSetting["XMLLabel"]]= "";
			//echo "XMLL: " . $FileSetting["XMLLabel"] . "<br>";

			//add new entries
			foreach ($FilesForField as $SourcFile) {
				$FileMeaning = GetMeaningFilename($SourcFile);
				//pre_r($FileMeaning);
				//$Corefilename = GetCoreFilename(pathinfo($SourcFile)['basename'], $FeldID);
				$FileExtension = pathinfo($SourcFile)['extension'];
				if (	($FileExtension ==$FileSetting["KatField"])
						 and ($FileMeaning[4] <> "")  
						 and (
								(($FileMeaning[4] == "00") and ($FileSetting["XMLLabel"]=="SuchfeldBild")) 
								or (($FileMeaning[4] <> "00") and ($FileSetting["XMLLabel"]=="FlugplanBild"))
								or (($FileSetting["XMLLabel"]<>"SuchfeldBild") and ($FileSetting["XMLLabel"]<>"FlugplanBild"))
					)) {
						
					if ($FileMeaning[4] <> "") {
						// Nehme Höhe von SourcFile wenn vorhanden!
						//$NewFilename = $FeldIDWahl . "_" . $FileMeaning[7] . "_" .  $FileMeaning[4] . "_" . $FileMeaning[5] . "_" . $FileMeaning[6] . "." . $FileExtension;
						$NewFilename = $FeldIDWahl . "_" .$Feldkatalog[$JG][$FeldID]['Feldname']."_".$Feldkatalog[$JG][$FeldID]['GrundstueckID'] . "_" .  $FileMeaning[4] . "_" . $FileMeaning[5] . "_" . date("y");
						//Set Filename according to database entires
					} else {
						// Wenn keine Höhe im SourcFile dann die Standardhöhe
						$NewFileName = $FeldID."_".$Feldkatalog[$JG][$FeldID]['Feldname']."_".$Feldkatalog[$JG][$FeldID]['GrundstueckID']. "_" .  $_POST["Flughoehe1"] . "_" . $_POST["Pilot1"] . "_" . date("y").".".$FileSetting["KatField"];
					}
					//echo "JG:  " . $JG . " - ";
					//echo "NFN: " . $NewFilename. " - ";
					//echo "CFN: " . $Corefilename. " - ";
					//echo "EXE: " . $FileExtension. "<br>";
					if (pathinfo($SourcFile)['filename'] <> $NewFilename) {
						//echo "Rename:" . $SourcFile . " to " . $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$NewFilename . "<br>";
						$NewFileList[$FileSetting["XMLLabel"]][] = $RegionSettings[$JG]["Dir"]."/".pathinfo($NewFilename)['basename'].".".$FileExtension;
						if ($FileSetting["KatField"]=="json") {
							$updatefile="Y";
							UpdateFilenameAndFeldIDInJson(pathinfo($SourcFile)['filename'].".".$FileExtension, pathinfo($NewFilename)['filename'], $JG, $JG, $FeldID);
							$KeepFieldStatus = true;
							//echo "update JSON ". basename($NewFilename) . "<br>";
							$Flughoehe = $FileMeaning[4];
							//echo "setze Standardflughöhe auf : " . $FileMeaning[4] . "<br>";
						}
						rename($SourcFile, $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$NewFilename.".".$FileExtension);
						//if (is_file($SourcFile)) {
						//	unlink($SourcFile);
						//}	
					} else {
						//echo "Keep:" . $SourcFile . "<br>";
						$NewFileList[$FileSetting["XMLLabel"]][] = $RegionSettings[$JG]["Dir"]."/".pathinfo($SourcFile)['basename'];
						if ($FileSetting["KatField"]=="json") {
							$updatefile="Y";
							UpdateFilenameAndFeldIDInJson(pathinfo($SourcFile)['filename'].".".$FileExtension, pathinfo($SourcFile)['filename'], $JG, $JG, $FeldID);
							$KeepFieldStatus = true;
							//echo "update JSON ". basename($SourcFile) . "<br>";
							$Flughoehe = $FileMeaning[4];
							//echo "setze STandardflughöhe auf : " . $FileMeaning[4] . "<br>";
						}
					}
				}
			}
		}
		//pre_r($NewFileList);
		foreach ($NewFileList as $key => $NewFiles) {
			$Feldkatalog[$JG][$FeldID][$key]= implode(",",$NewFiles);
			//echo "Einträge geändert: " . $key . ": " . implode(",",$NewFiles) . "<br>";
			//pre_r($Feldkatalog[$JG][$FeldID][$key]);
		}
		
		//pre_R($Feldkatalog[$JG][$FeldID]);
	}
	
	//echo "Entries end of adding<br>";
	//pre_r($Feldkatalog[$JG][$FeldID]);
	
	$Feldkatalog[$JG][$FeldID]["Aenderung"]= date("d.m.Y");
	$dosafe= true;
}

if ($dosafe) {
	SaveFlugkatalog($Feldkatalog, true);
	SaveFlugkatalog2CSV ($Feldkatalog);
}
// =================================
// NEW FIELD MODE
// =================================

if ($newfield=="Y") {
	if ($_SERVER["REQUEST_METHOD"] == "POST"){
		if (isset($_POST["Pilot"])) {
			$Pilot= $_POST["Pilot"];
		}
		 
		if (isset($_POST["JSON_Flughoehe"])) {
			//echo "FH:" . $Flughoehe= $_POST["JSON_Flughoehe1"]. "<br>" . chr(10);
		}

		if (isset($_POST["Region"])) {
			$JG = $_POST["Region"];
			//echo "JG: ". $JG . "<br>";
		}
		
		//if (isset($_POST["JSON_File1"])) {
			$FileName = $_FILES[$FlyFileSetting["JSON"]["FileVar"] . "1" ]['name'];
			$FileTmpName = $_FILES[$FlyFileSetting["JSON"]["FileVar"] . "1" ]['tmp_name'];
			$FileSize = $_FILES[$FlyFileSetting["JSON"]["FileVar"] . "1" ]['size'];
			$FileError = $_FILES[$FlyFileSetting["JSON"]["FileVar"] . "1" ]['error'];
			$FileType = $_FILES[$FlyFileSetting["JSON"]["FileVar"] . "1" ]['type'];
			$Flughoehe = $_POST[$FlyFileSetting["JSON"]["HightVar"] . "1" ];
			$FileExtension = pathinfo($FileName, PATHINFO_EXTENSION);
			//echo $FileName = str_replace($searchUML3, $replaceUML3, $FileName);

			//echo $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName."<br>";
			WriteToLog("add", basename($_SERVER['PHP_SELF']), "Felddaten ",$RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName,$RKRKMissionSelected,"");

			//echo "JF " . $FileName. "<br>" . chr(10);
		//}
		
			//echo "Next KatID: " . $_POST["Region"] . "-" . sprintf("%03d", $RegionSettings[$JG]["MaxID"]+1)  . " (" .$RegionSettings[$JG]["MaxID"] . ")<br>" . chr(10);
		$KatMaxNum = $JG . "-" . sprintf("%03d", $RegionSettings[$JG]["MaxID"]+1);

/*
		if ($FileName !="") {
			echo "upload JSON data <br>";
			echo $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName."<BR>";
			$JsonData =  GetJSONData($RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName);
			pre_R($JsonData);
		}
*/		
		$FilenameSplit = explode("_",$FileName);
		//pre_r($FilenameSplit);
		///////////////////////////////////////////
		// Check for Valid KatNum and define KatID
		///////////////////////////////////////////
		
		$KatIDSplit = explode("-",$FilenameSplit[0]);
		//pre_r($KatIDSplit);
		if ( 
			(strlen($KatIDSplit[0])==2) and 
			(strlen($KatIDSplit[1])==3) and
			(is_numeric($KatIDSplit[1]))
			) {
			//echo "<br> KatID Format is correkt ". $FilenameSplit[0]; 
			if (
				(in_array($FilenameSplit[0] ,$Feldkatalog[$JG])) and
				($FilenameSplit[0]==$JG)
				) {
				//echo "<br> KatID not used and in same JG". $JG . " - " . $FilenameSplit[0]; 
				$KatID =$FilenameSplit[0];
			} else {
				//echo "<br> KatID already used, or not in selected JG ". $JG . " - " . $FilenameSplit[0]; 
				$KatID = $KatMaxNum;
			}
		} else {
			//echo "<br> KatID Format NOT correkt ". $FilenameSplit[0]; 
			$KatID = $KatMaxNum;
		}

		//echo "<br>Selected KatID: ". $KatID;

	}
}


// ADD new file
if ($newfield=="Y"){	
	if ($_SERVER["REQUEST_METHOD"] == "POST"){
		$Corefilename = GetCoreFilename($FileName, $KatID);
		$FilenameMeaning = GetMeaningFilename($FileName);
		$FileExtension = pathinfo($FileName)['extension'];
		//echo "<br>FilenameMeaning: " . $FilenameMeaning[7];

		//pre_r($FilenameMeaning);
		//echo "<br>CoreFilename: " . $Corefilename . "<br>";
		
		if ($Corefilename !="") {
			$NewFileName = $Corefilename . "_" .  $Flughoehe . "_" . $Pilot . "_" . date("y");
		}
		
		// add new field to Feldkatlog
		$FeldNamenDaten = multiexplode(array("_","."), $Corefilename);

	// ==============================
	// Move and upload JSON File
	// ==============================
		// upload JSON File

		if (($FileName !="") and ($NewFileName!="")) {
			if ($FileError === 0) {
			$DebugInfo .=  "<li> File nicht leer: " . $FileName;
			$FileExt = explode('.', $FileName);
			$FileActualExt = strtolower(end($FileExt));
			
			$allowed = $FlyFileSetting["JSON"]["allowed"];
			
			if (in_array($FileActualExt, $allowed)) {
					if ($FileSize < 10000000) {
						//$FileDestination = strval($_POST["FeldID" . $i]) .  "_" . $_POST["Feldname" . $i] . "_" . $_POST["GrundstueckID" . $i] . "_" .  $Flughoehe . "_" . $Pilot . "_" . date("y") . "." . $FileActualExt;
						//$FileDestination = strval($Feldkatalog[$JG][$FeldID]["FeldID"]) .  "_" . $Feldkatalog[$JG][$FeldID]["Feldname"] . "_" . $Feldkatalog[$JG][$FeldID]["GrundstueckID"] . "_" .  $Flughoehe . "_" . $Pilot . "_" . date("y") . "." . $FileActualExt;
						$FileDestination = $NewFileName. "." . $FileExtension;
						$DebugInfo .=  "<li> Ziel Filename: " . $FileDestination;
						$SystemInfo .= $FileName ." nach " . $FileDestination . " : File geladen.  <br>"; 
						$FileDestination = $RegionSettings[$JG]["Dir"]."/".$FileDestination;
						//echo "Move File: " . $FileTmpName . " to " . $RKRKLocalLoactionMissionFiles.$FileDestination . "<br>";
						move_uploaded_file($FileTmpName,$RKRKLocalLoactionMissionFiles.$FileDestination);
						//header("Location: ". htmlspecialchars($_SERVER["PHP_SELF"]) . "?uploadsuccess");

						if ($RKRKLocalLoactionMissionFiles.$FileDestination !="") {
							//echo "<br>upload JSON data: ";
							//echo "File Destination: " . $FileDestination. "<BR>";
							//echo "Filename: " . $FileName. "<BR>";
							$JsonData =  GetJSONData($RKRKLocalLoactionMissionFiles.$FileDestination);
							rkrk_felddaten_json_import_apply_metrics($RKRKLocalLoactionMissionFiles.$FileDestination, $JsonData);
							//pre_R($JsonData);
							// upload JSON File
							//UpdateFilenameInJson(basename($NewFileName).".". $FileActualExt, basename($NewFileName).".". $FileActualExt, $JG);
							//UpdateFilenameAndGenFeldIDInJson(basename($NewFileName).".". $FileActualExt, basename($NewFileName).".". $FileActualExt, $JG, $JG, $FeldIDWahl);
							//echo "FeldID: " . $FeldID . " -  "  . explode("_",basename($NewFileName))[0] . " - " . basename($NewFileName) . "<br>";
							UpdateFilenameAndFeldIDInJson(basename($NewFileName).".".$FileExtension, basename($NewFileName), $JG, $JG, explode("_",basename($NewFileName))[0]);
						}
					} else {
						$SystemInfo .= $FileName . ": File zu gross! <br>";
					}
				} else {
					$SystemInfo .= $FileName . ": FEHLER beim laden!  <br>";
				}
			} else {
			$SystemInfo .= $FileName . ": Dieses File kann NICHT geladen werden! <br>";
			}
		} else {
			$DebugInfo .=  "<li>Fehler im Filenamen: " . $FileName . " oder NewFilename ". $NewFileName;
		}
	}
}

if ($updatefile=="Y") {	
	$JG= $_GET["JG"];
	$FeldID=$FeldIDWahl;
	$FileName =$updateFileName;
	$jsonPath = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileName;
	$JsonData =  GetJSONData($jsonPath);
	rkrk_felddaten_json_import_apply_metrics($jsonPath, $JsonData);
	//echo "udpate file " . $updatefile . " - " . $FileName . "<br>";
	//pre_R($JsonData);
}

// Update data in Flugkatalog
if ((($newfield=="Y") or ($updatefile=="Y")) && isset($JsonData)){
	//pre_R($JsonData);

	// ==============================
	// Update Feldkatalog
	// ==============================

	// Get Geofencde information
	//echo "Point: " . $JsonData["Grundstueckdaten"]["Zentrum"] . "<br>";
	$GeoGde = GetGdeOfPOint($JsonData["Grundstueckdaten"]["Zentrum"]);
	//echo "Gde: " . $GeoGde . "<br>";
	$GeoJgd = GetJgdOfPOint($JsonData["Grundstueckdaten"]["Zentrum"]);
	//echo "Jgd: " . $GeoJgd . "<br>";
	$GeoWarnFlgZ = GetWarnungFlgZOfPOint($JsonData["Grundstueckdaten"]["Zentrum"]);
	$GeoWarnSun = GetSunOfPOint($JsonData["Grundstueckdaten"]["Zentrum"]);
	$GeoWarnSora = GetSoraOfPOint($JsonData);
	//echo "Warnung FlgZ: " . $GeoWarnFlgZ . " " . $GeoWarnSun .  " " .$GeoWarnSora ."<br>";
	
	if (($updatefile!="Y")){	
		$FeldID = $KatID;
		//echo "FeldID: " . $FeldID . "<br>" . chr(10);
		$Feldkatalog[$JG][$FeldID]["FeldID"]= $FeldID;
		$Feldkatalog[$JG][$FeldID]["JG"]= $JG;
		$Feldkatalog[$JG][$FeldID]["Feldname"]= $FilenameMeaning[1];
		//echo "Feldname: " . $Feldkatalog[$JG][$FeldID]["Feldname"] . "<br>" . chr(10);
		$Feldkatalog[$JG][$FeldID]["Flurname"]= $FilenameMeaning[2];
		//echo "Flur: " . $Feldkatalog[$JG][$FeldID]["Flurname"] . "<br>" . chr(10);
		$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= $FilenameMeaning[3];
		//echo "GstkID" . $Feldkatalog[$JG][$FeldID]["GrundstueckID"] . "<br>" . chr(10);
		//$Flughoehe = $FeldNamenDaten[$StartPattern];
	}
		$Feldkatalog[$JG][$FeldID]["StdFlughohe"]= $Flughoehe;
		$Feldkatalog[$JG][$FeldID]["Flugzeit"]= round($JsonData["Flugzeit"], 1);
		$Feldkatalog[$JG][$FeldID]["Flaeche"]= round($JsonData["Flaeche"], 2);
		$Feldkatalog[$JG][$FeldID]["Wegpunkte"]= round($JsonData["Wegpunkte"], 0);
		$Feldkatalog[$JG][$FeldID]["Flugweg"]= $JsonData["Flugweg"];
		$Feldkatalog[$JG][$FeldID]["Distanz"]= round($JsonData["Distanz"], 1);
		$Feldkatalog[$JG][$FeldID]["Gemeinde"]= $GeoGde;
		if (($updatefile!=="Y")){	
			//neu
			if ($JsonData["Notiz"] != "") {
				$Feldkatalog[$JG][$FeldID]["Betrieb"] = explode(",",str_replace($searchSDZ, $replaceSDZ,$JsonData["Notiz"]))[0];
			} else {
				$Feldkatalog[$JG][$FeldID]["Betrieb"]= "";
			}	
		} else {
			//no changen on update
		}
		if (!($KeepFieldStatus ?? false))	{	
			$Feldkatalog[$JG][$FeldID]["Freigabe"]= $OptionFrei["NEU"]["Opt"];
		}
		if (($updatefile!=="Y")){	
			//neu
			//$Feldkatalog[$JG][$FeldID]["Warnung"]= $GeoWarnFlgZ;
			$Feldkatalog[$JG][$FeldID]["Warnung"]= trim($GeoWarnFlgZ . " " . $GeoWarnSun . " " . $GeoWarnSora);
		} else {
			//no changen on update
		}
		$Feldkatalog[$JG][$FeldID]["Aenderung"]= date("d.m.Y");
		if (($updatefile!=="Y")){
			//Neu
			$Feldkatalog[$JG][$FeldID]["Jaeger"]= "";
			//$Feldkatalog[$JG][$FeldID]["Bemerkung"]= "Neu " . date("d.m.Y");
			$Feldkatalog[$JG][$FeldID]["Update"]= date("d.m.Y");
			$Feldkatalog[$JG][$FeldID]["Erstellt"]= date("d.m.Y");
		} else {
			//update json
			//if ($Feldkatalog[$JG][$FeldID]["Bemerkung"]!="")
			//	$Feldkatalog[$JG][$FeldID]["Bemerkung"] .= "\n";
			//$Feldkatalog[$JG][$FeldID]["Bemerkung"] .= "Update von Jason " . date("d.m.Y");
			$Feldkatalog[$JG][$FeldID]["Update"]= date("d.m.Y");
		}
		if (($JsonData["Notiz"] ?? "") != "") {
			if (($Feldkatalog[$JG][$FeldID]["Bemerkung"] ?? "") !="") {
				$Feldkatalog[$JG][$FeldID]["Bemerkung"] .= ", ";
			} else {
				$Feldkatalog[$JG][$FeldID]["Bemerkung"] = "";
			}
			$Feldkatalog[$JG][$FeldID]["Bemerkung"] .= cleanString(str_replace($searchSDZ, $replaceSDZ,$JsonData["Notiz"]));
			//For Zug only
			
			$prefix="ZG";
			if (substr($Feldkatalog[$JG][$FeldID]["Bemerkung"], 0, strlen($prefix)) == $prefix) 
			{
				$Feldkatalog[$JG][$FeldID]["Jaeger"]= "AZ"; //AZ Adrian Zehnder
			} 
		}
	if (($updatefile!=="Y")){	
			//Neu
		$Feldkatalog[$JG][$FeldID]["Flug"]= "";
		$Feldkatalog[$JG][$FeldID]["Funde"]= "";
		$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= "";
		$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= "";
		$Feldkatalog[$JG][$FeldID]["JasonFile"]= $RegionSettings[$JG]["Dir"]."/".$NewFileName. "." . $FileExtension; 
		$Feldkatalog[$JG][$FeldID]["KMLFile"]= "";
		$Feldkatalog[$JG][$FeldID]["LichtiFile"]= "";
	}
		//update json
		$Feldkatalog[$JG][$FeldID]["Bauer"]= cleanString(str_replace($searchSDZ, $replaceSDZ,$JsonData["Bauer"]));
		//echo "Bauer: " . $Feldkatalog[$JG][$FeldID]["Bauer"] . "<br>" . chr(10);
		$Feldkatalog[$JG][$FeldID]["Notiz"]= cleanString(str_replace($searchSDZ, $replaceSDZ,$JsonData["Notiz"]));
		if (($JsonData["Grundstueckdaten"]["Parkplatz"] ?? "")<>"") {
			//echo "save Parkplatz";
			$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Parkplatz"]= $JsonData["Grundstueckdaten"]["Parkplatz"];
		} else {
			//echo "delete Parkplatz";
			unset($Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Parkplatz"]);
		}

		$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Startplatz"]= $JsonData["Grundstueckdaten"]["Startplatz"];
		$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Zentrum"]= $JsonData["Grundstueckdaten"]["Zentrum"];
		$Feldkatalog[$JG][$FeldID]["Flaechendaten"]= $JsonData["Flaechendaten"];

	if (($updatefile!=="Y")){	
		$Feldkatalog[$JG][$FeldID]["Flugweg"]= $JsonData["Flugweg"];
		$Feldkatalog[$JG][$FeldID]["Export"]= "Y";
		$Feldkatalog[$JG][$FeldID]["Select"]= "Y";
		if (isset($FileSetting["XMLLabel"], $FieldEntry)) {
			$Feldkatalog[$JG][$FeldID][$FileSetting["XMLLabel"]] = $RegionSettings[$JG]["Dir"] . "/" .$FieldEntry;
		}
		$Feldkatalog[$JG][$FeldID]["Aenderung"]= date("d.m.Y");
	}

	if ($Feldkatalog[$JG][$FeldID]["SuchfeldBild"] =="") {
		$SuchfeldDateiname = $FeldID . "_" .$Feldkatalog[$JG][$FeldID]["Feldname"]."_".$Feldkatalog[$JG][$FeldID]["GrundstueckID"] . "_" .  "00" . "_" . $Pilot . "_" . date("y").".jpg";
	} else {
		$SuchfeldDateiname  = basename($Feldkatalog[$JG][$FeldID]["SuchfeldBild"]);
	}
	
	if ($Feldkatalog[$JG][$FeldID]["FlugplanBild"] =="") {
		$FlugplanDateiname = $FeldID . "_" .$Feldkatalog[$JG][$FeldID]["Feldname"]."_".$Feldkatalog[$JG][$FeldID]["GrundstueckID"] . "_" .  $Feldkatalog[$JG][$FeldID]["StdFlughohe"] . "_" . $Pilot . "_" . date("y").".jpg";
	} else {
		$FlugplanDateiname = basename($Feldkatalog[$JG][$FeldID]["FlugplanBild"]);
	}
	
	//echo "WriteUpdatFeld: <br>" . $SuchfeldDateiname . "<br>" . $FlugplanDateiname."<br><br>";
	WriteToUpdFeldListe($FeldID, $Feldkatalog[$JG][$FeldID]["Feldname"],explode("/",$Feldkatalog[$JG][$FeldID]["JasonFile"])[0] , basename($Feldkatalog[$JG][$FeldID]["JasonFile"]), $SuchfeldDateiname, $FlugplanDateiname);	

	SaveFlugkatalog($Feldkatalog, true);
	SaveFlugkatalog2CSV ($Feldkatalog);
		
	$FeldIDWahl= $FeldID;
	$NoSelection = false;
	$rkrkShotJob = rkrk_felddaten_shot_job($FeldID, $Feldkatalog[$JG][$FeldID]["Flaechendaten"] ?? '', $RKRKMissionSelected ?? '', $Feldkatalog[$JG][$FeldID]["Flaeche"] ?? '');
	if (is_array($rkrkShotJob)) {
		$rkrkJsonShot = $rkrkShotJob;
	}
}

		
// =================================
// EDIT MODE
// upload information to xml file
// =================================
if (($newfield!="N") and ($updatefile!="Y")){
	$OptionGemeinde[""] = Array("Opt" => "","Kml" => "", "Text" => "");
	array_multisort($OptionGemeinde, SORT_ASC);

	if ($_SERVER["REQUEST_METHOD"] == "POST"){
		//echo "<br>Postentries: " . $_POST["NumEntries"]. "<br><br>";
		if (($_POST["NumEntries"] ?? 0)>0) {
			//new configuration, safe configuration file
			//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
			for ($i = 1; $i < $_POST["NumEntries"]; $i++) {
				//echo $i . "<br>";
				//echo  $i . "  " . $_POST["FeldID" . $i] . " " . $_POST["Feldname" . $i] . " " . $_POST["GrundstueckID" . $i];
				if (true) {		
					// safe history
					$file = $xmlMissionLocation . $RKRKFelderfileXML;
					$Min =  intval(date("i")/10)*10;
					$newfile = $historyfilelocation . date("YmdH") . sprintf("%02d", $Min) . "_" . $RKRKFelderfileXML;
					//echo "<br>History: " . $file . " to  " . $newfile;
					if (!copy($file, $newfile)) {
						$SystemInfo .= "failed to copy $file...\n";
					}

					// READ Input data to array
					// Tabellenfelder
					//echo "Round: " . $i . "<br>";
					$JG = strval($_POST["JG" . $i]);
					$FeldID = strval($_POST["FeldID" . $i]);

					//Write to LOGfile
					WriteToLog("save", basename($_SERVER['PHP_SELF']), "Felddaten ",$FeldID, $RKRKMissionSelected,"");

					if (isset($_POST["FeldID" . $i])) {
						$Feldkatalog[$JG][$FeldID]["FeldID"]= $FeldID;
					}
					if (isset($_POST["Number" . $i])) {
						$Feldkatalog[$JG][$FeldID]["JG"]= $JG;
					}
					if (isset($_POST["Number" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Number"]= $_POST["Number" . $i];
					}
					if (isset($_POST["Feldname" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Feldname"]= cleanString($_POST["Feldname" . $i]);
					}
					if (isset($_POST["Flurname" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flurname"]= cleanString($_POST["Flurname" . $i]);
					}
					if (isset($_POST["GrundstueckID" . $i])) {
						$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= $_POST["GrundstueckID" . $i];
					}
					if (isset($_POST["Flugzeit" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flugzeit"]= $_POST["Flugzeit" . $i];
					}
					if (isset($_POST["Flaeche" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flaeche"]= $_POST["Flaeche" . $i];
					}
					if (isset($_POST["Wegpunkte" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Wegpunkte"]= $_POST["Wegpunkte" . $i];
					}
					if (isset($_POST["Distanz" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Distanz"]= $_POST["Distanz" . $i];
					}
					if (isset($_POST["Gemeinde" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Gemeinde"]= $_POST["Gemeinde" . $i];
					}
					if (isset($_POST["Betrieb" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Betrieb"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Betrieb" . $i]));
						//echo "B" .$i .  $JG . "/" . $FeldID . " : "  . $_POST["Betrieb" . $i] . " - ". $InputData[$JG][$FeldID]["Betrieb"] . " - ". $Feldkatalog[$JG][$FeldID]["Betrieb"] . " <br>";
					}
					if (isset($_POST["Freigabe" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Freigabe"]= $_POST["Freigabe" . $i];
					}
					if (isset($_POST["Warnung" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Warnung"]= $_POST["Warnung" . $i];
					}
					if (isset($_POST["Jaeger" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Jaeger"]= $_POST["Jaeger" . $i];
					}
					if (isset($_POST["Bemerkung" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Bemerkung"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Bemerkung" . $i]));
					}
					if (isset($_POST["Flug" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flug"]= $_POST["Flug" . $i];
					}
					if (isset($_POST["Funde" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Funde"]= $_POST["Funde" . $i];
					}
					if (isset($_POST["Pilot" . $i])) {
						$Pilot= $_POST["Pilot" . $i];
					}
					if (isset($_POST["Flughoehe" . $i])) {
						$Flughoehe= $_POST["Flughoehe" . $i];
						$StdFlughoehe = $_POST["Flughoehe" . $i];
						//echo "Standard Flughöhe: " . $Flughoehe ."<br>";
					}
					/*
					Files are be treated separately / see below
					if (isset($_POST["SuchfeldBild" . $i])) {
						$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= $_POST["SuchfeldBild" . $i];
					}
					if (isset($_POST["FlugplanBild" . $i])) {
						$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= $_POST["FlugplanBild" . $i];
					}
					if (isset($_POST["JasonFile" . $i])) {
						$Feldkatalog[$JG][$FeldID]["JasonFile"]= $_POST["JasonFile" . $i];
					}
					if (isset($_POST["KMLFile" . $i])) {
						$Feldkatalog[$JG][$FeldID]["KMLFile"]= $_POST["KMLFile" . $i];
					}
					if (isset($_POST["LCHM_File" . $i])) {
						$Feldkatalog[$JG][$FeldID]["LichtiFile"] .= isset($_POST["LCHM_File" . $i . $ii]);
					}
					*/
					if (isset($_POST["Bauer" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Bauer"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Bauer" . $i]));
					}
					if (isset($_POST["Notiz" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Notiz"]= cleanString(str_replace($searchSDZ, $replaceSDZ, $_POST["Notiz" . $i]));
					} 
					
					if (isset($_POST["Startplatz" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Startplatz"]= $_POST["Startplatz" . $i];
					}
					if (isset($_POST["Parkplatz" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Parkplatz"]= $_POST["Parkplatz" . $i];
					}
					if (isset($_POST["Zentrum" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Zentrum"]= $_POST["Zentrum" . $i];
					}
					if (isset($_POST["Flaechendaten" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flaechendaten"]= $_POST["Flaechendaten" . $i];
					}
					if (isset($_POST["Flugweg" . $i])) {
						$Feldkatalog[$JG][$FeldID]["Flugweg"]= $_POST["Flugweg" . $i];
					}
					$Feldkatalog[$JG][$FeldID]["Aenderung"]= date("d.m.Y");
					
					if (isset($_POST["Export" . $i])) {
						if (($_POST["Export" . $i]) == "on") {
							$Feldkatalog[$JG][$FeldID]["Export"]= "Y";
						} else {
							$Feldkatalog[$JG][$FeldID]["Export"]= "N";
						}
					}
					if (isset($_POST["Select" . $i])) {
						if (($_POST["Select" . $i]) == "on") {
							$Feldkatalog[$JG][$FeldID]["Select"]= "Y";
						} else {
							$Feldkatalog[$JG][$FeldID]["Select"]= "N";
						}
					}

					// upload MULTI File
					foreach ($FlyFileSetting as $FileSetting) {
						if (!$FileSetting["Picture"] or true) {
							//echo "Filetype: " . $FileSetting["Type"] . "<br>"; 

							$FileOutput= "";

	//						for ($ii = 1; $ii <= $_POST['NumJSONFiles'. $i]; $ii++) {
							
								$uploadKey = $FileSetting["FileVar"] . $i;
								$uploaded = (isset($_FILES[$uploadKey]) && is_array($_FILES[$uploadKey])) ? $_FILES[$uploadKey] : array();
								$FileName = (string)($uploaded['name'] ?? '');
								$FileTmpName = (string)($uploaded['tmp_name'] ?? '');
								$FileSize = (int)($uploaded['size'] ?? 0);
								$FileError = $uploaded['error'] ?? UPLOAD_ERR_NO_FILE;
								$FileType = (string)($uploaded['type'] ?? '');
								$Flughoehe = (string)($_POST[$FileSetting["HightVar"] . $i] ?? '');
								//$FileName = str_replace($searchUML3, $replaceUML3, $FileName);
								//echo "num: " . $ii . " -  " . $FileName . "<br>";
									

								if ($FileName != "") {
									$DebugInfo .=  "<li> File nicht leer: " . $FileName;
									$FileExt = explode('.', $FileName);
									$FileActualExt = strtolower(end($FileExt));
									
									//$allowed = array('json');
									$allowed = $FileSetting["allowed"];
									
									$NumEntries = 0;
									if (in_array($FileActualExt, $allowed)) {
										//echo $NumEntries . "<br>";												
										if ($FileError === 0) {
											if ($FileSize < 10000000) {
												//$FileDestination = strval($_POST["FeldID" . $i]) .  "_" . $_POST["Feldname" . $i] . "_" . $_POST["GrundstueckID" . $i] . "_" .  $Flughoehe . "_" . $Pilot . "_" . date("y") . "." . $FileActualExt;
												if ($FileSetting["Type"] == "SuchfeldBild") {
													$CoreFilename = strval($_POST["FeldID" . $i]) .  "_" . $_POST["Feldname" . $i] . "_" . $_POST["GrundstueckID" . $i]. "_"  .  "00";
													$FileDestination = $CoreFilename . "_" . $Pilot . "_" . date("y") . "." . $FileActualExt;
												} else {
													$CoreFilename = strval($_POST["FeldID" . $i]) .  "_" . $_POST["Feldname" . $i] . "_" . $_POST["GrundstueckID" . $i];
													$FileDestination = $CoreFilename . "_" .  $Flughoehe . "_" . $Pilot . "_" . date("y") . "." . $FileActualExt;
												}
												$DebugInfo .=  "<li> Ziel Filename: " . $FileDestination;
												$SystemInfo .= $FileName ." nach " . $FileDestination . " : File geladen.  <br>"; 
												$FileDestination = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$FileDestination;
												$NumEntries++;
												if ($NumEntries > 0) {
													$FileOutput = $FileOutput . ", ";
												}	
												$FileOutput .= $weblocation . "/" . $FileDestination;
												move_uploaded_file($FileTmpName,$FileDestination);
												//header("Location: ". htmlspecialchars($_SERVER["PHP_SELF"]) . "?uploadsuccess");
												// Add file to XML table
												$FieldEntry = "";
												foreach ($allowed as $FileExtension) {
													//$SearchFile = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"] . "/" . $CoreFilename . "*." . $FileExtension;
													$SearchFile =$RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"] . "/" . $CoreFilename . "*." . $FileExtension;
													$FilesList = array_diff(glob($SearchFile), array('.', '..'));
													//echo "JSONSearch " . $SearchFile . "<br>";
													//pre_R(glob($SearchFile));
													foreach ($FilesList as $file) {
														$FileName = pathinfo($file);
														$FileSplitt = explode("_", $FileName['basename']);
														//pre_r($FileSplitt);
														if ($FieldEntry!= "") {
															$FieldEntry =  $FieldEntry . ",";
														}
														if (($FileSetting["Type"] == "SuchfeldBild")  && (($FileSplitt[3]=="00") || ($FileSplitt[4]=="00") || ($FileSplitt[5]=="00"))) {
															$FieldEntry .= $RegionSettings[$JG]["Dir"] . "/" . $FileName['basename'];
														}
														if (($FileSetting["Type"] != "SuchfeldBild") && (($FileSplitt[3]!="00") && ($FileSplitt[4]!="00") && ($FileSplitt[5]!="00"))) {
															$FieldEntry .= $RegionSettings[$JG]["Dir"] . "/" . $FileName['basename'];
														}
														// Update JSON Filename
														if ($FileSetting["Type"] == "JSON") {
															$JSONFilename = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"] . "/" . $FileName['basename'];
															$Feldname = explode(".", $FileName['basename']);
															//pre_r($Feldname);
															$JsonData =  ReadJsonFile($JSONFilename);
															$JsonData["name"]= $FileName['basename'];
															$JsonData["fileNameComplete"]= $Feldname[0];
															$JsonData["fileNameCompleteForExport"]= $Feldname[0];
															$JsonData["fileNameComplete"]= $FileName['basename'];
															$JsonData["fileName"]= $FileName['basename'];
															WriteJsonFile($JSONFilename, $JsonData ); 
														}
													}
												}
												//echo $Feldkatalog[$JG][$FeldID][$FileSetting["XMLLabel"]] . ":  " .  $FieldEntry . "<br>";
												$Feldkatalog[$JG][$FeldID][$FileSetting["XMLLabel"]] = $FieldEntry;
											} else {
												$SystemInfo .= $FileName . ": File zu gross! <br>";
											}
											
										} else {
											$SystemInfo .= $FileName . ": FEHLER beim laden!  <br>";
										}
									} else {
									$SystemInfo .= $FileName . ": Dieses File kann NICHT geladen werden! <br>";
									}

								} else {
									$DebugInfo .=  "<li>" . $FileSetting["Type"] . "File ist leer.";
								}
							$DebugInfo .= "<li>" . $FileSetting["Type"] . " " . $FileOutput;
						}
					}

				}
			}	
		}
	// update XML
	SaveFlugkatalog($Feldkatalog, $dosafe);
	SaveFlugkatalog2CSV ($Feldkatalog);
	if (empty($rkrkJsonShot) && isset($JG, $FeldID) && isset($Feldkatalog[$JG][$FeldID]) && is_array($Feldkatalog[$JG][$FeldID])) {
		$rkrkSaveJob = rkrk_felddaten_shot_job($FeldID, $Feldkatalog[$JG][$FeldID]["Flaechendaten"] ?? '', $RKRKMissionSelected ?? '', $Feldkatalog[$JG][$FeldID]["Flaeche"] ?? '');
		if (is_array($rkrkSaveJob)) {
			$rkrkJsonShot = $rkrkSaveJob;
		}
	} 
	}	
}

$rkrkUploaded = false;
if (!empty($_FILES) && is_array($_FILES)) {
	foreach ($_FILES as $rkrkUp) {
		if (!is_array($rkrkUp)) {
			continue;
		}
		$rkrkUpName = $rkrkUp['name'] ?? '';
		if (is_array($rkrkUpName)) {
			$rkrkUpName = implode('', $rkrkUpName);
		}
		if (trim((string)$rkrkUpName) !== '') {
			$rkrkUploaded = true;
			break;
		}
	}
}
$rkrkMutated = (
	$deletefile === 'Y' || $deleteentry === 'Y' || $addentry === 'Y' || $addnadaptentry === 'Y'
	|| $cleanandadd === 'Y' || $transfer === 'Y' || $updatefile === 'Y' || $rkrkUploaded
	|| (isset($_GET['DoSafe']) && $_GET['DoSafe'] === 'Y')
	|| ($newfield === 'Y' && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'))
);
if ($rkrkMutated) {
	$rkrkBackId = trim((string)$FeldIDWahl);
	if ($rkrkBackId === '' && isset($_POST['FeldID1'])) {
		$rkrkBackId = trim((string)$_POST['FeldID1']);
	}
	rkrk_felddaten_redirect_same_field($rkrkBackId);
}

//pre_r(array_keys($Feldkatalog));
//control elements
$AnzLinesPPage= 1;

if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
	$ActPage= $_GET["ActPage"];
} else {
	$ActPage= 1;
}

$FontSize = "100%";
if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
	$FontSize= $_POST["FontSize"];
}

// =================================
// read updated XML file
// =================================

	$KatFileList = ReadMissionFiles ($RegionSettings, $EnabledRegion);
	//echo "Feldwahl:" . $FeldIDWahl .  " - " .  strval($FeldNamenWahl);
	if (true) {
		// ===========================================
		// Filter Anzeige
		// ===========================================
		$i=1;
		foreach ($Feldkatalog as $jgKey => $Region) {
			if (!is_array($Region)) {
				continue;
			}
			$regionOn = !empty($EnabledRegion[$jgKey]);
			foreach ($Region as $feldKey => $Feld) {
				if (!is_array($Feld)) {
					continue;
				}
				$fid = (isset($Feld["FeldID"]) && !is_array($Feld["FeldID"])) ? trim((string)$Feld["FeldID"]) : '';
				if ($fid === '') {
					$fid = trim((string)$feldKey);
				}
				$match = ($fid !== '' && $fid === (string)$FeldIDWahl)
					|| ($FeldNamenWahl !== '' && (
						like($fid, strval($FeldNamenWahl))
						|| (strpos((string)$FeldNamenWahl, $fid) !== false)
						|| like((string)($Feld["Feldname"] ?? ''), $FeldNamenWahl)
						|| like((string)($Feld["Flurname"] ?? ''), $FeldNamenWahl)
					));
				if ($match && ($regionOn || $FeldIDWahl !== '')) {
					if ($fid !== '' && !isset($Feldkatalog[$jgKey][$feldKey]["FeldID"])) {
						$Feldkatalog[$jgKey][$feldKey]["FeldID"] = $fid;
					}
					$Feldkatalog[$jgKey][$feldKey]["ListNr"] = $i;
					$i++;
				} else {
					$Feldkatalog[$jgKey][$feldKey]["ListNr"] = "";
				}
			}
		}
		$MaxSelectedEntries = $i;
		$rkrkAddWithoutRow = (($addentry === "Y") || ($addnadaptentry === "Y") || ($cleanandadd === "Y"))
			&& !is_array(rkrk_felddaten_locate_entry($Feldkatalog, $FeldIDWahl));
		$rkrkImportedNow = (($newfield === "Y") && isset($JsonData)) || ($updatefile === "Y" && isset($JsonData));
		if (empty($rkrkJsonShot) && !$rkrkAddWithoutRow && $rkrkImportedNow && $FeldIDWahl !== '') {
			$rkrkOpenLoc = rkrk_felddaten_locate_entry($Feldkatalog, $FeldIDWahl);
			if (is_array($rkrkOpenLoc)) {
				$rkrkOpenRow = $Feldkatalog[$rkrkOpenLoc[0]][$rkrkOpenLoc[1]] ?? array();
				$rkrkOpenArea = (is_array($rkrkOpenRow) && array_key_exists('Flaechendaten', $rkrkOpenRow)) ? $rkrkOpenRow['Flaechendaten'] : '';
				$rkrkOpenFlaeche = (is_array($rkrkOpenRow) && array_key_exists('Flaeche', $rkrkOpenRow)) ? $rkrkOpenRow['Flaeche'] : '';
				$rkrkOpenJob = rkrk_felddaten_shot_job($FeldIDWahl, $rkrkOpenArea, $RKRKMissionSelected ?? '', $rkrkOpenFlaeche);
				if (is_array($rkrkOpenJob)) {
					$rkrkJsonShot = $rkrkOpenJob;
				}
			}
		}


		if ($newfield!="N") {
			// Feldwahl für Editfunktion
			echo "<i>Wähle die Katalognummer des Feldes, dass zu ändern möchtest:</i><br>";
			echo "<table class='rkrk-felddaten-search'>" .chr(10);
			echo "<tr>" .chr(10);
			echo "<td>" .chr(10);
			$FeldListe = array_merge(Array(""=>""),GetFieldList($Feldkatalog));
			echo "Feld-ID: " .chr(10);
			$feldKeys = array_values(array_keys($FeldListe));
			$feldIdx = ($FeldIDWahl !== '') ? array_search($FeldIDWahl, $feldKeys, true) : false;
			if ($FeldIDWahl!="" && $feldIdx !== false && $feldIdx > 0) {
				$PrevFeldID = $feldKeys[$feldIdx - 1];
				echo " <a href='" . $ThisPage . "?DBW=off&FeldID=".$PrevFeldID."'><img src='" . $GenIconLoc . "/step_left.png' height='" . $ButtonHeightSmall*.7 . "px' title='ein Feld zurück' style='background-color:transparent; border-color:transparent;'></a>" . chr(10);

			}
			echo SelectField("FeldIDWahl", $FeldIDWahl ,$FeldListe );
			if ($FeldIDWahl!="" && $feldIdx !== false && isset($feldKeys[$feldIdx + 1])) {
				$NextFeldID = $feldKeys[$feldIdx + 1];
				echo " <a href='" . $ThisPage . "?DBW=off&FeldID=".$NextFeldID."'><img src='" . $GenIconLoc . "/step_right.png' height='" . $ButtonHeightSmall*.7 . "px' title='nächstes Feld' style='background-color:transparent; border-color:transparent;'></a>" . chr(10);
			}
			echo "  Suche: " .chr(10);
			echo InputField("FeldNamenWahl", $FeldNamenWahl, 20);
			//pre_R($TableSetup);
			//pre_R($FeldListe);
			//pre_R($OptionJaeger);
			//echo '<input type="submit" name="Einlesen" value="Einlesen"> ';
			echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '">' .chr(10);
			echo "</td>" .chr(10);
			echo "<td class='rkrk-search-actions'>" .chr(10);
			echo MissionLibraryButtons();
//			echo "<br>";
			if ($Layout!=2023) {
				echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
				echo CreateSiteLink ("EditFeldliste", "");
				echo CreateSiteLink ("Fluege", "");
				echo CreateSiteLink ("EditFiles", "");
				echo CreateSiteLink ("FeldKarteIntern", "Reg=ALL" . $StandardSettingMap);
				echo CreateSiteLink ("FeldListeIntern", "Reg=ALL&Typ=intern");
				echo CreateSiteLink ("Home", "");
			} else {
				echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
			}
			echo "</td>" .chr(10);
			echo "</tr>" .chr(10);
			echo "</table>" .chr(10);
		}
		

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
		
		if ($NoSelection==false) {
			// =================================
			// Feld editieren
			///==================================
			
			$i=1;
			$pageline = 1;
			$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage)-1;
			$showfrom = ($ActPage)*$AnzLinesPPage;
			$showto = $showfrom;
			$FontSizeInPoints = 12;
			if ($FontSize== "") {
				$FontSize= "100%";
			}
			$TableColor =  GetTableColor ("");
			AlternateLineTable($TableColor['even'], "120%",12, $TableColor['odd']);

			echo "<br>" .chr(10);
			PageControl($ActPage, $MaxPages, $AnzLinesPPage);
			echo "<br>" .chr(10);
			//page
			echo "<tr><td>" .chr(10);
			echo "<div id='alle' class='tabcontent'>" . chr(10);
			echo "<style>"
				. ".rkrk-flight-params-details{margin:8px 0 4px;border:1px solid #cfd6df;border-radius:6px;padding:4px 10px 10px;background:#f8fafc;}"
				. ".rkrk-flight-params-details>summary{cursor:pointer;font-weight:600;padding:4px 2px;list-style-position:outside;}"
				. ".rkrk-flight-params-warn{color:#b91c1c;font-weight:bold;}"
				. ".rkrk-flight-params-table td{padding:4px 14px 4px 0;vertical-align:middle;}"
				. "</style>" . chr(10);
			echo "<table class='rkrk-felddaten-form'>" .chr(10);
			$changeinfo = '';
			//echo $showfrom . ", " . $showto . ", " . $MaxSelectedEntries . ", " . $AnzLinesPPage . ", " . $MaxPages;
			//pre_r($Feldkatalog);
			//pre_r($KatFileList);
			foreach ($Feldkatalog as $Key => $Region) {
				//echo ($Key) . "<br>";
				//pre_r($Region);
				// no region filtering
				if ($EnabledRegion[$Key]==1 or true) {
					foreach ($Region as $Key => $Feld) {
						//echo ($Key) . " - " . $Feld["ListNr"] . "<br>";
						//pre_r($Feld);
						if (($Feld["ListNr"] ?? '')!="" &&
							$Feld["ListNr"]>=$showfrom && 
							$Feld["ListNr"]<=$showto){
							//echo "Feld: " . $Feld["ListNr"] . "<br>";
							//page show control
							// -----------
							// Start Liste
							// -----------
							$CoreFileName = $Feld["CorName"] ?? '';
							$CoreFileNameWoULM = str_replace($searchSDZ2 ?? array(), $replaceSDZ2 ?? array(), $CoreFileName);
							$LenFeldID = strlen((isset($Feld["FeldID"]) && !is_array($Feld["FeldID"])) ? (string)$Feld["FeldID"] : '');
							echo "<tr class='rkrkedit'>" .chr(10);
							echo "<td colspan=3>" . chr(10);
							echo "<h2>Felddaten</h2>" . chr(10);
							echo "</td>" . chr(10);
							echo "</tr>" . chr(10);
							echo "<tr class='rkrkedit'>" .chr(10);
							// Number
							echo "<td >" . chr(10);
							echo "<i>#:</i>" . chr(10);
							echo "</td>" . chr(10);
							echo "<td  colspan=2>" . chr(10);
							$Inputfieldname = "Number".$i;
							echo $pageline;
							echo InputField($Inputfieldname, $i, 0, "left", "hidden");
							// JG
							$Inputfieldname = "JG".$i;
							echo InputField($Inputfieldname, $Feld["JG"], 0, "left", "hidden");
							$katJg = (isset($Feld["JG"], $KatFileList[$Feld["JG"]]) && is_array($KatFileList[$Feld["JG"]])) ? $KatFileList[$Feld["JG"]] : array();
							$katField = (isset($Feld["FeldID"], $katJg[$Feld["FeldID"]]) && is_array($katJg[$Feld["FeldID"]])) ? $katJg[$Feld["FeldID"]] : array();
							$katId = (string)($katField["FeldID"] ?? '');
							$katFeldname = (string)($katField["Feldname"] ?? '');
							$katGrund = (string)($katField["GrundstueckID"] ?? '');
							// FeldID  
							if ($TableSetup["FeldID"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>FeldID:</i>" . chr(10);
								echo "</td>" . chr(10);
								if ($katId == $Feld["FeldID"]) {
									echo "<td  colspan=2>" .chr(10);
									echo $Feld["FeldID"];
								} else {
									echo "<td   colspan=2 style='background-color:" . $colorNOK . "'>" .chr(10);
									echo $Feld["FeldID"];
									echo "<span style='color:" . $colorNOKtext . "'>  (". $katId.") </span>" .chr(10);
								}
								$Inputfieldname = "FeldID".$i;
								echo InputField($Inputfieldname, $Feld["FeldID"], 5, "left", "hidden");
								//Geofence Jagdgebiet
								if ($geofence) {
									$geoZentrum = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"]) && isset($Feld["Grundstueckdaten"]["Zentrum"])) {
										$geoZentrum = $Feld["Grundstueckdaten"]["Zentrum"];
									}
									$GeoJgd = GetJgdOfPOint($geoZentrum);
/*
									if ($GeoJgd != "") {
									  if (substr($Feld["FeldID"],2)==$GeoJgd) {
										  $textcolor = $colorOKtext;
									  } else {
										  $textcolor = $colorNOKtext;
									  }
									  echo "<i> - <span style='color:" . $textcolor . "'>" . $GeoJgd . "</span><i>";
								   }
*/								}
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
							}
							// Feldname
							if ($TableSetup["Feldname"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Feldname:</i>" . chr(10);
								echo "</td>" . chr(10);
								$SearchA = Array(" ","ä","ö","ü","Ä","Ö","Ü","-"," ","_");
								$FindA = Array("-","a","o","u","A","O","U","-","-","-");
								if ( 
									(str_replace($SearchA, $FindA, $katFeldname) == str_replace($SearchA, $FindA, $Feld["Feldname"]))  
									or (str_replace($SearchA, $FindA, $katFeldname)  == str_replace($SearchA, $FindA, $Feld["Feldname"] . "_" . ($Feld["Flurname"] ?? '')))
									){
								//if ($KatFileList[$Feld["JG"]][$Feld["FeldID"]]["Feldname"] == $Feld["Feldname"]) {
									echo "<td  colspan=2>" .chr(10);
									echo $Feld["Feldname"];
								} else {
									echo "<td  colspan=2 style='background-color:" . $colorNOK . "'>" .chr(10);
									echo $Feld["Feldname"];
									echo "<span style='color:" . $colorNOKtext . "'>  (". $katFeldname.") </span>" .chr(10);
								}
								$Inputfieldname = "Feldname".$i;
								echo InputField($Inputfieldname, $Feld["Feldname"], 15, "left", "hidden");
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Flurname
							if ($TableSetup["Flurname"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Flurname:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Flurname".$i;
								echo InputField($Inputfieldname, $Feld["Flurname"], 30);
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
							}
							// GrundstückID
							if ($TableSetup["GrundstueckID"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Grundstück:</i>" . chr(10);
								echo "</td>" . chr(10);
								if ($katGrund == ($Feld["GrundstueckID"] ?? '')) {
									echo "<td  colspan=2>" .chr(10);
									echo $Feld["GrundstueckID"] ?? '';
								} else {
									echo "<td  colspan=2 style='background-color:" . $colorNOK . "'>" .chr(10);
									echo $Feld["GrundstueckID"] ?? '';
									echo "<span style='color:" . $colorNOKtext . "'>  (". $katGrund.") </span>" .chr(10);
								}
								$Inputfieldname = "GrundstueckID".$i;
								echo InputField($Inputfieldname, $Feld["GrundstueckID"], 0, "left", "hidden");
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
							}
							// Gemeinde
							if ($TableSetup["Gemeinde"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Gemeinde:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Gemeinde".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $Feld["Gemeinde"] ,$OptionGemeinde );
								//Geofence Gemeinde
								if ($geofence) {
									//echo " GEO: " . $Feld["Grundstueckdaten"]["Zentrum"];
									$gdZentrum = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) {
										$gdZentrum = trim((string)($Feld["Grundstueckdaten"]["Zentrum"] ?? ''));
									}
									$GeoGde = ($gdZentrum !== '' && $gdZentrum !== ',') ? GetGdeOfPOint($gdZentrum) : '';
									if ($GeoGde != "") {
									  //set color
									  if ($Feld["Gemeinde"]==$GeoGde) {
										  $textcolor = $colorOKtext;
									  } else {
										  $textcolor = $colorNOKtext;
									  }
									  echo "<i> - <span style='color:" . $textcolor . "'>" . $GeoGde . "</span><i>";
									}
								}
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
							}
							// Betrieb
							if ($TableSetup["Betrieb"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Betrieb:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Betrieb".$i;
								echo InputField($Inputfieldname, $Feld["Betrieb"], 10);
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
							}
							// Freigabe
							if ($TableSetup["Freigabe"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td >" . chr(10);
								echo "<i>Freigabe:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Freigabe".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $Feld["Freigabe"] ,$OptionFrei );
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Warnung
							if ($TableSetup["Warnung"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Warnung:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Warnung".$i;
								//echo InputField($Inputfieldname, $Feld["Warnung"], 12);
								// Test von Eingabefilter
								$action = 'onchange="CheckWarnEntry(document.form.'.$Inputfieldname.')"';
								echo InputField($Inputfieldname, $Feld["Warnung"], 12, "left", "", "", $action);
								echo "<span id = 'change_warning_message' style='color:red'> </span>";
								
								//Geofence Warning Flugzone
								if ($geofence) {
									$geoZentrum = '';
									if (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"]) && isset($Feld["Grundstueckdaten"]["Zentrum"])) {
										$geoZentrum = $Feld["Grundstueckdaten"]["Zentrum"];
									}
									$GeoWarnFlgZ = GetWarnungFlgZOfPOint($geoZentrum);
									$GeoWarnSun = GetSunOfPOint($geoZentrum);
									$GeoWarnSora = GetSoraOfPOint($Feld);
									$GeoWarnFlgZ= trim($GeoWarnFlgZ . " " . $GeoWarnSun  . " " . $GeoWarnSora);
									
									if ($GeoWarnFlgZ != "") {
									  //echo  "|". $GeoWarnFlgZ . "|" . " " .  "|" .$Feld["Warnung"]. "|";
									  if (in_array_all(explode(" ",str_replace("  ", " ",$GeoWarnFlgZ)),explode(" ", str_replace("  ", " ",$Feld["Warnung"])))) {
									  //if (trim($Feld["Warnung"])==trim($GeoWarnFlgZ)) {
										  $textcolor = $colorOKtext;
									  } else {
										  $textcolor = $colorNOKtext;
									  }
									  echo "<i> - <span style='color:" . $textcolor . "'>" . $GeoWarnFlgZ . "</span><i>";
									}
								}
								echo "</td>";
								echo "</tr>" .chr(10);
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
							if ($TableSetup["Jaeger"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Jäger:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Jaeger".$i;
								echo SelectField($Inputfieldname, $Feld["Jaeger"] ,$OptionJaeger );
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Bemerkung
							if ($TableSetup["Bemerkung"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Bemerkung:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Bemerkung".$i;
								echo InputTextarea($Inputfieldname, $Feld["Bemerkung"] ?? "", 12, 2, 50);
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Flug
							if ($TableSetup["Flug"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Flüge:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Flug".$i;
								echo InputField($Inputfieldname, $Feld["Flug"], 6);
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Funde
							if ($TableSetup["Funde"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Rehkitzfunde:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Funde".$i;
								echo InputField($Inputfieldname, $Feld["Funde"], 6);
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Bauer
							if ($TableSetup["Bauer"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Bauer:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" .chr(10);
								$Inputfieldname = "Bauer".$i;
								echo InputTextarea($Inputfieldname, $Feld["Bauer"], 12, 5, 50);
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Notiz
							if ($TableSetup["Notiz"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Notizen:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" . chr(10);
								$Inputfieldname = "Notiz".$i;
								echo InputTextarea($Inputfieldname, $Feld["Notiz"] ?? "", 12, 5, 50);
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Export
							if ($TableSetup["Export"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Export:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td  colspan=2>" . chr(10);
								$Inputfieldname = "Export".$i;
								echo CheckField($Inputfieldname, ($Feld["Export"]=="Y"));
								echo "</td>";
								echo "</tr>" .chr(10);
							}
							// Select
							if ($TableSetup["Select"]["Show"]== true) {
								echo "<tr>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Select:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td colspan=2 >" . chr(10);
								$Inputfieldname = "Select".$i;
								echo CheckField($Inputfieldname, ($Feld["Select"]=="Y"));
								echo "</td>";
							}
							if (($TableSetup["SuchfeldBild"]["Show"]== true) ||
							    ($TableSetup["FlugplanBild"]["Show"]== true) ||
								($TableSetup["JasonFile"]["Show"]== true) ||
								($TableSetup["KMLFile"]["Show"]== true) ||
								(!empty($TableSetup["LCHMFile"]["Show"])) ||
								(!empty($TableSetup["LichtiFile"]["Show"]))) 
								{
									
								echo "<tr class='rkrkedit'>" .chr(10);
								echo "<td colspan=3>" . chr(10);
								echo "<h2>Bilder und Filereferenzen</h2>" . chr(10);
								echo "</td>" . chr(10);
								echo "</tr>" . chr(10);
								
								// Pilot
								echo "<tr class='rkrk-pilot-row'>" .chr(10);
								echo "<td>" . chr(10);
								echo "<i>Pilot:</i>" . chr(10);
								echo "</td>" . chr(10);
								echo "<td class='rkrk-pilot-cell'>" .chr(10);
								$Inputfieldname = "Pilot".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $Pilot , rkrk_pilot_options_for_assign(null, $Pilot) );
								$rkrkRenewBtn = "";
								$rkrkHasPicRows = false;
								foreach ($FlyFileSetting as $rkrkFs) {
									$rkrkFsType = (string)($rkrkFs["Type"] ?? "");
									if ($rkrkFsType !== "SuchfeldBild" && $rkrkFsType !== "FlugplanBild") {
										continue;
									}
									if (in_array($rkrkFs["XMLLabel"] ?? "", explode(",", (string)($MissionDroneTypes[$RKRKMissionSelected]["DataFile"] ?? "")))
										&& in_array("edit_felddaten", explode(",", (string)($rkrkFs["Show"] ?? "")))) {
										$rkrkHasPicRows = true;
										break;
									}
								}
								if ($rkrkHasPicRows) {
									$rkrkRenewJob = rkrk_felddaten_shot_job(
										(string)($Feld["FeldID"] ?? ''),
										$Feld["Flaechendaten"] ?? '',
										(string)($RKRKMissionSelected ?? ''),
										$Feld["Flaeche"] ?? ''
									);
									$rkrkRenewJson = json_encode($rkrkRenewJob, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
									if (!is_string($rkrkRenewJson) || $rkrkRenewJson === '') {
										$rkrkRenewJson = 'null';
									}
									$rkrkRenewBtn = "<button type='button' class='rkrk-renew-pics' id='rkrkRenewPicsBtn' title='Bilder erneuern' data-job='" . htmlspecialchars($rkrkRenewJson, ENT_QUOTES, 'UTF-8') . "'><img src='" . $IconLoc . "/dataupdate.png' alt='' height='" . $ButtonHeight . "'></button>";
								}
								$rkrkPilotIcons = "";
								if (GetAccess("PilotPartners"))	{							
									if (GetAccess("DebugDataBase")) {
										// clean and add all files from drive
										$rkrkPilotIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/cleanandadd.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&cleanandadd=Y&DBW=" . ($_GET["DBW"] ?? "off"). "' height='" . $ButtonHeight . "'  title='Dateien von " . $Feld["FeldID"] . " löschen und Neue hinzufügen und XML Daten erneuern'>";
									} else {
										// clean and add all files from drive
										$rkrkPilotIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/cleanandadd.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&cleanandadd=Y' height='" . $ButtonHeight . "'  title='Alle D von " . $Feld["FeldID"] . " löschen und Neue hinzufügen und XML Daten erneuern'>";
									}
									if (GetAccess("TestOnly")) {
										//Transfer JSON to dependant DroneMissions
										$rkrkPilotIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/jsontansfer.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&transfer=Y' height='" . $ButtonHeight . "'  title='". "Alle Daten von " . $Feld["FeldID"] . " auf verlinkte Drohne umwandlen und speichern'>";
									}
								}
								$rkrkPilotIcons .= $rkrkRenewBtn;
								$rkrkPilotToolsHtml = "";
								if ($rkrkPilotIcons !== "") {
									$rkrkPilotToolsHtml = "<div class='rkrk-pilot-above-map'><span class='rkrk-pilot-icons'>" . $rkrkPilotIcons . "</span>";
									if ($rkrkRenewBtn !== "") {
										$rkrkPilotToolsHtml .= "<span id='rkrkRenewPicsNote'></span>";
									}
									$rkrkPilotToolsHtml .= "</div>";
								}
								echo "</td>" . chr(10);
								echo "</tr>" .chr(10);
								// Standard Flughöhe
								//echo "<tr>" .chr(10);
								//echo "<td>" . chr(10);
								//echo "<i>Standard Flughöhe:</i>" . chr(10);
								//echo "</td>" . chr(10);
								//echo "<td  colspan=2>" .chr(10);
								//$Inputfieldname = "Flughoehe".$i;
								//echo SelectFieldMultiOptArr($Inputfieldname, $Feld["StdFlughohe"] ,$OptionFlughoehe ) . "<i> -  nur gebraucht in Kombination mit folgender Funktion</i>: ";
								//echo "</td>";
								//echo "</tr>" .chr(10);
							}

							// 1. Spalte
							//=======================
							// MULTIPLE
							$dosave_again = false;
							if (!isset($rkrkPilotToolsHtml)) {
								$rkrkPilotToolsHtml = "";
							}
							foreach ($FlyFileSetting as $FileSetting) {
								$rkrkThirdCell = false;
								$rkrkPicKind = '';
								//echo "Label: " . $FileSetting["XMLLabel"] . "<br>";
								//echo "FileList: " . $MissionDroneTypes[$RKRKMissionSelected]["DataFile"] . "<br>";
								if (!$FileSetting["Picture"] or true) {
									if ( 
										in_array($FileSetting["XMLLabel"], explode(",",$MissionDroneTypes[$RKRKMissionSelected]["DataFile"]))
										) {
										if(in_array("edit_felddaten", explode(",",$FileSetting["Show"]))) {
											//Check if file on Drive
											//echo $FileSetting["Type"] . "-" . $FileSetting["KatField"] . "<br>";
											$TempContent="";
											$labelFiles = (string)($Feld[$FileSetting["XMLLabel"]] ?? '');
											$Files["InKat"] = ($labelFiles === '') ? array() : explode(",",$labelFiles);
											//get basefilename
											foreach ($Files["InKat"] as $key => $file) {
												$Files["InKat"][$key] = pathinfo($file)["basename"];
											}
											//pre_r($KatFileList);
											$jgFiles = $KatFileList[$Feld["JG"]] ?? array();
											$fieldFiles = $jgFiles[$Feld["FeldID"]] ?? array();
											$Files["OnDrive"] = $fieldFiles[$FileSetting["KatField"]] ?? array();
											$Files["MissingOnDrive"] = array_diff($Files["InKat"], $Files["OnDrive"]);
											$Files["MissingInKat"] = array_diff($Files["OnDrive"], $Files["InKat"]);
											$Files["InKatAndDrive"] = array_diff(array_diff($Files["OnDrive"], $Files["MissingOnDrive"]),$Files["MissingInKat"]);
											
											if ($FileSetting["Type"]=="SuchfeldBild") {
												// Suchfeldbild (00 height)
												foreach ($Files["OnDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]!="00") {
														unset($Files["OnDrive"][$key]);
													}
												}
												foreach ($Files["InKatAndDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]!="00") {
														unset($Files["InKatAndDrive"][$key]);
													}
												}
												foreach ($Files["MissingOnDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]!="00") {
														unset($Files["MissingOnDrive"][$key]);
													}
												}
												foreach ($Files["MissingInKat"] as $key => $file) {
													if (GetMeaningFilename($file)[4]!="00") {
														unset($Files["MissingInKat"][$key]);
													}
												}
											}
											if ($FileSetting["Type"]=="FlugplanBild") {
												// FlugplanBild (!00 height)
												foreach ($Files["OnDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]=="00") {
														unset($Files["OnDrive"][$key]);
													}
												}
												foreach ($Files["InKatAndDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]=="00") {
														unset($Files["InKatAndDrive"][$key]);
													}
												}
												foreach ($Files["MissingOnDrive"] as $key => $file) {
													if (GetMeaningFilename($file)[4]=="00") {
														unset($Files["MissingOnDrive"][$key]);
													}
												}
												foreach ($Files["MissingInKat"] as $key => $file) {
													if (GetMeaningFilename($file)[4]=="00") {
														unset($Files["MissingInKat"][$key]);
													}
												}
											}
											//pre_r($Files);
											
											//echo $FileBasename = pathinfo($file)['filename'];
											$Files= array_map('array_values',$Files);
											//pre_r($Files);
											//	pre_r($FileMeaning);
											//$NewFilename = $FileMeaning[7] . "_" .  $FileMeaning[4] . "_" . $FileMeaning[5] . "_" . $FileMeaning[6] . "." . $FileActualExt;
											//echo "<br>" . $FileSetting["XMLLabel"] . " - " . $FileSetting["KatField"]. "<br>";
											

											if ($FileSetting["Type"] == "SuchfeldBild") {
												$rkrkPicKind = "such";
											} elseif ($FileSetting["Type"] == "FlugplanBild") {
												$rkrkPicKind = "flug";
											}
											$TempContent .= "<tr" . (($rkrkPicKind === "such" || $rkrkPicKind === "flug") ? " data-rkrk-picrow='" . $rkrkPicKind . "'" : "") . ">" . chr(10);
											$TempContent .= "<td>" . chr(10);
											$TempContent .= "<i>" . $FileSetting["Text"] . "</i>" . chr(10);
											$TempContent .= "</td>" . chr(10);
											$TempContent .= "<td>" . chr(10);
											
											$PropPicNameSB = $FeldIDWahl . "_" .$Feld['Feldname']."_".$Feld['GrundstueckID'] . "_" .  "00" . "_" . $Pilot . "_" . date("y");
											$PropPicNameFB = $FeldIDWahl . "_" .$Feld['Feldname']."_".$Feld['GrundstueckID'] . "_" .  ($Feld['StdFlughohe'] ?? '') . "_" . $Pilot . "_" . date("y");
											
											$LocalMissionDirectory = $MissionLocation.$RegionSettings[$Feld["JG"]]["Dir"]."/";
											$rkrkPicOnce = "";
											if (GetAccess("PilotPartners") and $FileSetting["Type"] =="SuchfeldBild") {
												$rkrkPicOnce .= "<a href='rkrk_map.php?FFilter=".$Feld["FeldID"]. $SuchplanBildParameter ."&MenuOn=off&MapButtons=off' target='_geomap'><img src='" . $IconLoc . "/suchplanbild.png' height='" . 36 ."' title='Zeige Suchfeldbild'></a>";
											}
											if (GetAccess("TestOnly") and $FileSetting["Type"] =="SuchfeldBild") {
												$Parameter = 'javascript:startSnapshot({ffilter: "'.$FeldIDWahl.'",filename: "'.$PropPicNameSB .".jpg".'", mode: "suchfeld",zoom: 70,w: 600,h: 600,off: 0});';
												$flugplanbild_button = "<a href='". $Parameter."'>";
												$flugplanbild_button .= "<img src='" . $IconLoc . "/flugplanbild_shot.png' height='" . 36 ."' title='Kreiere Suchfeldbild und lade es in den lokalen Download Ordner'>";
												$flugplanbild_button .= "</a>";
												$rkrkPicOnce .= $flugplanbild_button;
											}
											if (GetAccess("PilotPartners") and $FileSetting["Type"] =="SuchfeldBild") {
												$Parameter = 'javascript:startSnapshot({ffilter: "'.$FeldIDWahl.'",filename: "'.$PropPicNameSB .".jpg" .'", mode: "suchfeld",zoom: 70,w: 600,h: 600,off: 0, serverPath: "'.$LocalMissionDirectory.'"});';
												$flugplanbild_button = "<a href='". $Parameter."'>";
												$flugplanbild_button .= "<img src='" . $IconLoc . "/flugplanbild_shot_server.png' height='" . 36 ."' title='Kreiere Suchfeldbild und lade es auf den Server'>";
												$flugplanbild_button .= "</a>";
												$rkrkPicOnce .= $flugplanbild_button;
											}
											if (GetAccess("PilotPartners") and $FileSetting["Type"] =="FlugplanBild") {
												$rkrkPicOnce .= "<a href='rkrk_map.php?FFilter=".$Feld["FeldID"]. $FlugplanBildParameter ."&MenuOn=off&MapButtons=off' target='_geomap'><img src='" . $IconLoc . "/flugplanbild.png' height='" . 36 ."' title='Zeige Flugplanbild'></a>";
											}
											if (GetAccess("TestOnly") and $FileSetting["Type"] =="FlugplanBild") {
												$Parameter = 'javascript:startSnapshot({ffilter: "'.$FeldIDWahl.'",filename: "'. $PropPicNameFB.".jpg" .'", mode: "flugplan",zoom: 90,w: 600,h: 600,off: 0});';
												$suchplanbild_button = "<a href='". $Parameter."'>";
												$suchplanbild_button .= "<img src='" . $IconLoc . "/suchplanbild_shot.png' height='" . 36 ."' title='Kreiere FlugplanBild und lade es in den lokalen Download Ordner'>";
												$suchplanbild_button .= "</a>";
												$rkrkPicOnce .= $suchplanbild_button;
											}
											if (GetAccess("PilotPartners") and $FileSetting["Type"] =="FlugplanBild") {
												$Parameter = 'javascript:startSnapshot({ffilter: "'.$FeldIDWahl.'",filename: "'. $PropPicNameFB .".jpg" .'", mode: "flugplan",zoom: 90,w: 600,h: 600,off: 0, serverPath: "'.$LocalMissionDirectory.'" });';
												$suchplanbild_button = "<a href='". $Parameter."'>";
												$suchplanbild_button .= "<img src='" . $IconLoc . "/suchplanbild_shot_server.png' height='" . 36 ."' title='Kreiere FlugplanBild und lade es auf den Server'>";
												$suchplanbild_button .= "</a>";
												$rkrkPicOnce .= $suchplanbild_button;
											}
											if ($rkrkPicOnce !== "" && ($rkrkPicKind === "such" || $rkrkPicKind === "flug")) {
												$TempContent .= "<div class='rkrk-pic-tools' style='display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;justify-content:flex-start;gap:6px;margin:0 0 8px;'>" . $rkrkPicOnce . "</div>" . chr(10);
											}
											//if (count($Files["inKat"]))
											//	$TempContent .= "<br>All Files in Kat: <li> ". implode("<li>", $Files["inKat"]) . "<br>";
											//if (count($Files["OnDrive"]))
											//	$TempContent .= "<br>All Files on drive: <li> ". implode("<li>", $Files["OnDrive"]) . "<br>";

											//======================
											// InKatAndDrive
											//======================
											if (count($Files["InKatAndDrive"])) {
												//$TempContent .= "<br>Files In Kat and Drive: <li> ". implode("<li>", $Files["InKatAndDrive"]) . "<br>";
												foreach ($Files["InKatAndDrive"] as $FileName) {
													$HeightNum = GetMeaningFilename($FileName)[4];
													if ($HeightNum === "") {
														$HeightNum = (($Feld["StdFlughohe"] ?? "") !== "") ? $Feld["StdFlughohe"] : $DefHeightNum;
													}
													$lineIcons = "";
													// delete: bei Suchfeld/Flugplan Datei und Katalogeintrag, Dateiname URL-kodiert
													$rkrkPicRow = ($rkrkPicKind === "such" || $rkrkPicKind === "flug");
													$rkrkDelTitle = $FileSetting["Type"] . "-" . $HeightNum . " Datei " . $Feld["FeldID"] . " löschen (" . $FileName . ")";
													if ($rkrkPicRow) {
														$lineIcons .= rkrk_felddaten_delete_input($IconLoc . "/delete.png", $ButtonHeightSmall, $rkrkDelTitle, $_SERVER["PHP_SELF"], $ActPage, $Feld["FeldID"], $Feld["JG"], $FileName, $FileSetting["XMLLabel"], true, GetAccess("DebugDataBase") ? ($_GET["DBW"] ?? "off") : null);
													} elseif (GetAccess("DebugDataBase")) {
														$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . rawurlencode((string)$FileName) . "&deletefile=Y&XMLLabel=".$FileSetting["XMLLabel"]."&DBW=" . ($_GET["DBW"] ?? "off"). "' height='" . $ButtonHeightSmall . "' title='". $rkrkDelTitle . "'>";
													} else {
														$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . rawurlencode((string)$FileName) . "&deletefile=Y&XMLLabel=".$FileSetting["XMLLabel"]."' height='" . $ButtonHeightSmall . "'  title='". $rkrkDelTitle . "'>";
													}
													if ((GetAccess("InternPilots")) and $FileSetting["Type"]=="JSON") {
														//Transfer JSON to dependant DroneMissions
														$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/jsontansfer.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . $FileName . "&transfer=Y' height='" . $ButtonHeightSmall . "'  title='". "Datei " . $Feld["FeldID"] . " auf verlinkte Drohne umwandlen und speichern (".$FileName.")'>";
													}
													if ((GetAccess("PilotPartners")) and $FileSetting["Type"]=="JSON") {
														$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/update.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . $FileName . "&updatefile=Y&DBW=" . ($_GET["DBW"] ?? "off"). "' height='" . $ButtonHeightSmall . "' title='Datei " . $Feld["FeldID"] . " mit Daten neu laden'>";
													}
													$TempContent .= rkrk_felddaten_pic_entry(rkrk_felddaten_pic_line($FileName, $rkrkPicKind), $lineIcons, $rkrkPicKind);
												}
											} else {
												$FileMeaning = GetMeaningFilename($Files["OnDrive"]);
												if ($FileSetting["Type"] =="SuchfeldBild") {
													$TempContent .= "<i><small>". "Vorschlag Dateinname: " . $PropPicNameSB . "</i><br></small>";
												
												} 
												if ($FileSetting["Type"] =="FlugplanBild") {
													$TempContent .= "<i><small>". "Vorschlag Dateinname: " . $PropPicNameFB . "</i><br></small>";
												}
											}

											//======================
											// MissingOnDrive
											//======================
											if (count($Files["MissingOnDrive"]))
												//$TempContent .= "<br>Files missing drive: <li> ". implode("<li>", $Files["MissingOnDrive"]) . "<br>";
											foreach ($Files["MissingOnDrive"] as $FileName) {
												$HeightNum = GetMeaningFilename($FileName)[4];
												if ($HeightNum === "") {
													$HeightNum = (($Feld["StdFlughohe"] ?? "") !== "") ? $Feld["StdFlughohe"] : $DefHeightNum;
												}
												$lineIcons = "";
												// delete: Suchfeld/Flugplan trägt aus und löscht die Datei
												if ($rkrkPicKind === "such" || $rkrkPicKind === "flug") {
													$lineIcons .= rkrk_felddaten_delete_input($IconLoc . "/delete.png", $ButtonHeightSmall, $FileSetting["Type"] . "-" . $HeightNum . " Datei " . $Feld["FeldID"] . " löschen (" . $FileName . ")", $_SERVER["PHP_SELF"], $ActPage, $Feld["FeldID"], $Feld["JG"], $FileName, $FileSetting["XMLLabel"], true);
												} else {
													$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . rawurlencode((string)$FileName) . "&deleteentry=Y&XMLLabel=".$FileSetting["XMLLabel"]."' height='" . $ButtonHeightSmall . "'  title='". $FileSetting["Type"] ."-".$HeightNum."Eintrag " . $Feld["FeldID"] . " löschen (".$FileName.")'>";
												}
												if ((GetAccess("InternLinks")) and $FileSetting["Type"]=="JSON") {
													$lineIcons .= "<img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall . "'>";
												}
												$TempContent .= rkrk_felddaten_pic_entry(rkrk_felddaten_pic_line($FileName, $rkrkPicKind), $lineIcons, $rkrkPicKind, "#E95F11");
											}

											//======================
											// MissingInKat
											//======================
											if (count($Files["MissingInKat"]))
												//$TempContent .= "<br>Files Missing in Kat: <li> ". implode("<li>", $Files["MissingInKat"]) . "<br>";
											foreach ($Files["MissingInKat"] as $FileName) {
												$HeightNum = GetMeaningFilename($FileName)[4];
												if ($HeightNum === "") {
													$HeightNum = (($Feld["StdFlughohe"] ?? "") !== "") ? $Feld["StdFlughohe"] : $DefHeightNum;
												}
												$lineIcons = "";
												// delete: Suchfeld/Flugplan trägt aus und löscht die Datei
												if ($rkrkPicKind === "such" || $rkrkPicKind === "flug") {
													$lineIcons .= rkrk_felddaten_delete_input($IconLoc . "/delete.png", $ButtonHeightSmall, $FileSetting["Type"] . "-" . $HeightNum . " Datei " . $Feld["FeldID"] . " löschen (" . $FileName . ")", $_SERVER["PHP_SELF"], $ActPage, $Feld["FeldID"], $Feld["JG"], $FileName, $FileSetting["XMLLabel"], true);
												} else {
													$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . rawurlencode((string)$FileName) . "&deletefile=Y&XMLLabel=".$FileSetting["XMLLabel"]."' height='" . $ButtonHeightSmall . "'  title='". $FileSetting["Type"] ."-".$HeightNum."Datei " . $Feld["FeldID"] . " löschen (".$FileName.")'>";
												}
												// add files from drive
												$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/add.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . rawurlencode((string)$Feld["FeldID"]) . "&JG=" . rawurlencode((string)$Feld["JG"]) . "&File=" . rawurlencode((string)$FileName) . "&addentry=Y&XMLLabel=".rawurlencode((string)$FileSetting["XMLLabel"])."' height='" . $ButtonHeightSmall . "'  title='". $FileSetting["Type"] ."-".$HeightNum."Datei " . $Feld["FeldID"] . " hinzufügen (".$FileName.")'>";
												$FeldID = (isset($Feld["FeldID"]) && !is_array($Feld["FeldID"]) && (string)$Feld["FeldID"] !== '')
													? (string)$Feld["FeldID"]
													: (string)($FeldIDWahl ?? '');
												$NewFilename =  GetNewFilename($FeldID, $FileName);
												if ($NewFilename != $FileName) {
													// add files from drive and adapt name
													$lineIcons .= "<input type='image' value='1st page' src='" . $IconLoc . "/addnadpat.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&JG=" . $Feld["JG"] . "&File=" . $FileName . "&NewFileName=" . $NewFilename ."&addnadaptentry=Y&XMLLabel=".$FileSetting["XMLLabel"]."' height='" . $ButtonHeightSmall . "'  title='". $FileSetting["Type"] ."-".$HeightNum."Datei " . $Feld["FeldID"] . " hinzufügen und nach " . $NewFilename ." umbenennen'>";
												}
												$TempContent .= rkrk_felddaten_pic_entry(rkrk_felddaten_pic_line($FileName, $rkrkPicKind), $lineIcons, $rkrkPicKind, "#125B07");
											}
											if ($rkrkPicKind !== "such" && $rkrkPicKind !== "flug") {
												$TempContent .= "<br>";
											}

											//======================
											// Neues File
											//======================
											$TempContent .= "<div" . (($rkrkPicKind === "such" || $rkrkPicKind === "flug") ? " class='rkrk-pic-add'" : "") . ">" . chr(10);
											$TempContent .= "  <label for='" . $FileSetting["FileVar"] . $i ."'>Datei hinzufügen</label>" . chr(10);
											$TempContent .= "	<input type='file' id='" . $FileSetting["FileVar"] . $i . "' name='" . $FileSetting["FileVar"] . $i . "' accept='" . $FileSetting["allowedList"] .  "'>" . chr(10);
											//echo "	<button type='submit' name='JSON_File_Load'>File laden</button>" . chr(10);
											if ($FileSetting["Type"] != "SuchfeldBild") {
												$HeightNum=$Feld["StdFlughohe"] ?? '';
												if ($HeightNum=="" ) {
													$HeightNum =$DefHeightNum;
												}
												$TempContent .= SelectFieldMultiOptArr($FileSetting["HightVar"] . $i , $HeightNum ,$OptionFlughoehe );
											}	
											$TempContent .= "</div>" . chr(10);
											$TempContent .= "<br>" . chr(10);
											//$TempContent .= "<br><i>Feldeintrag: </i>" . "<li>" . implode("<li>",$KatFileList[$Feld["JG"]][$Feld["FeldID"]][$FileSetting["KatField"]]);




											// 2. Spalte
											//=======================

											$TempContent .= "</td>";
											if ($FileSetting["Type"]=="JSON") {
												$TempContent .= "<td rowspan='3'>";
												$rkrkThirdCell = true;
											} elseif (($FileSetting["Type"]!="KML") and ($FileSetting["Type"]!="LCHM")) {
												if ($rkrkPicKind === "such" || $rkrkPicKind === "flug") {
													$TempContent .= "<td data-rkrk-piccell='" . $rkrkPicKind . "'>";
													if ($rkrkPicKind === "such" && !empty($rkrkPilotToolsHtml)) {
														$TempContent .= $rkrkPilotToolsHtml;
														$rkrkPilotToolsHtml = "";
													}
												} else {
													$TempContent .= "<td>";
												}
												$rkrkThirdCell = true;
											}
												
											//echo $FileName;
											//pre_r( $KatFileList[$Feld["JG"]][$Feld["FeldID"]][]);
											//echo $FileSetting["Type"] . " " .$FileName;
											

											// SuchfeldBild, Flugplanbild
											if (($FileSetting["Picture"]) && (count($Files["InKatAndDrive"]))) {
												if (
												(($FileSetting["Type"]!="SuchfeldBild") or
												($FileSetting["Type"]=="SuchfeldBild"))
												) {
													//echo $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$FileName . "<br><br>";
													//pre_r($Files);
													$SelectedFlugplanBild = GetFlugplanbild($Files["InKatAndDrive"],$Feld["StdFlughohe"] ?? '');
													$rkrkPicAttr = ($rkrkPicKind === "such" || $rkrkPicKind === "flug") ? " data-rkrk-piclink='" . $rkrkPicKind . "'" : "";
													$rkrkImgAttr = ($rkrkPicKind === "such" || $rkrkPicKind === "flug") ? " data-rkrk-picimg='" . $rkrkPicKind . "'" : "";
													$TempContent .= "<a" . $rkrkPicAttr . " href='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$SelectedFlugplanBild. "' target='blank'><img" . $rkrkImgAttr . " src='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$SelectedFlugplanBild . "' height='" . $PicHeight1 . "' style='object-fit: cover;'></a>" . chr(10);
													//$TempContent .= "<a href='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$Files["InKatAndDrive"][0]. "' target='blank'><img src='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$Files["InKatAndDrive"][0]. "' height='" . $PicHeight1 . "' style='object-fit: cover;'></a>" . chr(10);
												}
											};

											// GEOMAP
											if ($FileSetting["Type"]=="JSON") {
												$parameterset = str_replace("&","%26","Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&Poi=on&HSP=on&EX=NO&KA=S&MAP=1&Zoom=0.8&FFilter=" . $Feld["FeldID"]);
												$geoweblocation = str_replace($searchURL, $replaceURL, $weblocation);
												$PicZoom = 1.5;
												$Search = "";
												$KA = 'ch.swisstopo.swissimage';
												$gdMap = (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) ? $Feld["Grundstueckdaten"] : array();
												$gdMapZentrum = trim((string)($gdMap["Zentrum"] ?? ''));
												if (($gdMapZentrum!="") and ($gdMapZentrum!=",")) {
													$SemPos = strpos($gdMapZentrum, ",");
													if ($SemPos != false) {
														$Centerpoint["Lat"] = trim(explode(",", $gdMapZentrum)[1]);
														$Centerpoint["Lon"] =  trim(explode(",", $gdMapZentrum)[0]);
													}
													//pre_R($Centerpoint);
													$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
													$MapZoom= 8.5;
													//echo $parameterset;
													$KartenArt =  $MapSettings["Types"]["S"]["Type"];
													//echo KartenArt($KA);
													//$TempContent .= "<iframe allow='geolocation' src='https://map.geo.admin.ch/#/embed?" . $Search . "lang=de&topic=ech&bgLayer=" . $KartenArt ."&layers=ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo-vd.geometa-grundbuch,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,ch.bazl.einschraenkungen-drohnen,GPX%7C%7C" .$geoweblocation . "%2Frkrk_dokml.php?". $parameterset . "&layers_opacity=0.75,0.75,0.2,1,0.6,1&layers_visibility=false,false,false,false,false,true&layers_timestamp=18641231,,,,,&E=" . $MidPointFromFFilter["LV95East"] . "&N=" . $MidPointFromFFilter["LV95North"] . "&zoom=" . $MapZoom . "'' width='250' height='" . $PicHeight1 . "' frameborder='0' style='border:0'></iframe >";
													$rkrkMapSrc = "rkrk_map.php?FFilter=" . rawurlencode((string)$Feld["FeldID"]) . "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8&MenuOn=off&MapButtons=off&FeldFill=1";
													$rkrkPicSide = (int)$PicHeight1;
													$TempContent .= "<iframe class=\"rkrk-feldkarte\" title=\"Flugplan\" src='" . htmlspecialchars($rkrkMapSrc, ENT_QUOTES, "UTF-8") . "' width='" . $rkrkPicSide . "' height='" . $rkrkPicSide . "' frameborder='0' style='border:0;width:" . $rkrkPicSide . "px;height:" . $rkrkPicSide . "px'></iframe>";
												}
												
											}
										}
										// Output
										echo $TempContent . chr(10);
										if (!empty($rkrkThirdCell)) {
											echo "</td>";
										}
										echo "</tr>" .chr(10);
									}
								}	
							}

							// Flugzeit, Fläche, Distanz — eingeklappt; (!) wenn ein Wert 0 oder leer
							$rkrkFlightParamIsZero = static function ($v) {
								$v = trim((string)$v);
								if ($v === '') {
									return true;
								}
								return ((float)str_replace(',', '.', $v)) == 0.0;
							};
							$flugzeitZero = $rkrkFlightParamIsZero($Feld["Flugzeit"] ?? '');
							$flaecheZero = $rkrkFlightParamIsZero($Feld["Flaeche"] ?? '');
							$distanzZero = $rkrkFlightParamIsZero($Feld["Distanz"] ?? '');
							$flightParamsWarn = ($flugzeitZero || $flaecheZero || $distanzZero);
							echo "<tr class='rkrkedit'>" . chr(10);
							echo "<td colspan=3>" . chr(10);
							$flightParamsSummary = "Flugparameter: Flugzeit, Fläche, Distanz";
							if ($flightParamsWarn) {
								$flightParamsSummary .= " <span class='rkrk-flight-params-warn' title='Flugzeit, Fläche oder Distanz ist 0 oder leer'>!</span>";
							}
							echo "<details class='rkrk-flight-params-details'>" . chr(10);
							echo "<summary>" . $flightParamsSummary . "</summary>" . chr(10);
							echo "<table class='rkrk-flight-params-table'>" . chr(10);
							echo "<tr><td><i>Flugzeit:</i></td><td>" . chr(10);
							echo InputField("Flugzeit" . $i, $Feld["Flugzeit"], 10);
							if ($flugzeitZero) {
								echo " <span class='rkrk-flight-params-warn' title='Flugzeit ist 0 oder leer'>!</span>";
							}
							echo "</td></tr>" . chr(10);
							echo "<tr><td><i>Fläche:</i></td><td>" . chr(10);
							echo InputField("Flaeche" . $i, $Feld["Flaeche"], 10);
							if ($flaecheZero) {
								echo " <span class='rkrk-flight-params-warn' title='Fläche ist 0 oder leer'>!</span>";
							}
							echo "</td></tr>" . chr(10);
							echo "<tr><td><i>Distanz:</i></td><td>" . chr(10);
							echo InputField("Distanz" . $i, $Feld["Distanz"], 10);
							if ($distanzZero) {
								echo " <span class='rkrk-flight-params-warn' title='Distanz ist 0 oder leer'>!</span>";
							}
							echo "</td></tr>" . chr(10);
							echo "</table>" . chr(10);
							echo "</details>" . chr(10);
							echo "</td>" . chr(10);
							echo "</tr>" . chr(10);

							echo "</tr>" .chr(10);
							echo "<tr>" .chr(10);
							echo "<td colspan=4>" .chr(10);
							echo "<hr>" .chr(10);
							echo "</td>" .chr(10);
							echo "</tr>" .chr(10);
							$changeinfo = "<span style='fontsize=7'><i>" .chr(10);
							//echo date("Y-m-d",strtotime($KatalogBaseline)) . "  " . date("Y-m-d", strtotime($Feld["Aenderung"])) . "  " . (strtotime($KatalogBaseline)<strtotime($Feld["Aenderung"])) .  "<br>";
							$feldAenderung = (string)($Feld["Aenderung"] ?? '');
							$feldUpdate = (string)($Feld["Update"] ?? '');
							$feldErstellt = (string)($Feld["Erstellt"] ?? '');
							if (date("Y-m-d",strtotime($KatalogBaseline))>date("Y-m-d", strtotime($feldAenderung)))
								$ChangeColor = $colorTransparent;
							else
								$ChangeColor = $colorSIM;  
							if (date("Y-m-d",strtotime($KatalogBaseline))>date("Y-m-d", strtotime($feldUpdate)))
								$UpdateColor = $colorTransparent;
							else
								$UpdateColor = $colorSIM;  
							if (date("Y-m-d",strtotime($KatalogBaseline))>date("Y-m-d", strtotime($feldErstellt)))
								$EstColor = $colorTransparent;
							else
								$EstColor = $colorSIM;  
								
							//echo $KatalogBaseline . "<br>";
							$BaseDate= date($KatalogBaseline ) . "<br>";
							//echo date("d.m.Y") . "<br>";
							$changeinfo .= "<span style='background-color:".$ChangeColor.";'>Geändert:" . $feldAenderung . "</span>";
							$changeinfo .= " - " ."<span style='background-color:".$UpdateColor.";'>JasonUpdate:" . $feldUpdate . "</span>";
							$changeinfo .= " - " ."<span style='background-color:".$EstColor.";'>Erstellt:" . $feldErstellt . "</span>";
							// -----------
							// Ende Liste
							// -----------
							$i++;
							$pageline++;
/*
							if ($cleanandadd) {
								if ($dosave_again) {
									// update XML
									echo "do save again" . "<br>";
									pre_r($Feldkatalog[$Feld["JG"]][$Feld["FeldID"]]);
									SaveFlugkatalog($Feldkatalog, $dosave_again);
									SaveFlugkatalog2CSV ($Feldkatalog); 
								}
							}
*/
						}
					}
				}
			}
			echo "</table>" .chr(10);

			// end tab
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
			PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true);
			echo "</td></tr>" .chr(10);
			echo "</table>" .chr(10);
			echo $changeinfo;
		} else	{
			if  (true) {
				if ($newfield=="N") {
					// =================================
					// Neues Feld hinzufügen
					///==================================
					echo "<table>" .chr(10);
					// Titel
					echo "<tr class='rkrkedit'>" .chr(10);
					echo "<td colspan=2>";
					echo "<br><i>JSON Referenzfile und Region wählen</i><br>" .chr(10);
					echo "</td>";
					echo "<td rowspan= 5>";
						//Edit Feld
					echo MissionLibraryButtons();
					echo CreateSiteLink ("EditFeld", "");
					echo "</td>";
					echo "</tr>";

					// Pilot
					echo "<tr class='rkrkedit'>" .chr(10);
					echo "<td width='200'>" . chr(10);
					echo "<i>Pilot:</i>" . chr(10);
					echo "</td>" . chr(10);
					echo "<td>" .chr(10);
					$Inputfieldname = "Pilot";
					echo SelectFieldMultiOptArr($Inputfieldname, $Pilot , rkrk_pilot_options_for_assign(null, $Pilot) );
					echo "</td>";
					echo "</tr>" .chr(10);


					// Region
					echo "<tr class='rkrkedit'>" .chr(10);
					echo "<td>";
					echo "  <label for='Region'>Region wählen </label>" . chr(10);
					echo "</td>" . chr(10);
					echo "<td>" .chr(10);
					$rkrkNewRegion = '26';
					if (isset($_GET['Reg']) && trim((string)$_GET['Reg']) !== '') {
						$rkrkNewRegion = trim((string)$_GET['Reg']);
					}
					if (isset($_GET['Region']) && trim((string)$_GET['Region']) !== '') {
						$rkrkNewRegion = trim((string)$_GET['Region']);
					}
					if (isset($_POST['Region']) && trim((string)$_POST['Region']) !== '') {
						$rkrkNewRegion = trim((string)$_POST['Region']);
					}
					echo SelectFieldMultiOptArr("Region", $rkrkNewRegion, $RegionSettings);
					echo "</td>";
					echo "</tr>";
					echo "<tr class='rkrkedit'>" .chr(10);
					echo "<td>";

					//Neues JSON File
					$NumJSONFiles=1;
					echo "<label for='JSON_File" . $NumJSONFiles . "'>Datei hinzufügen </label>" . chr(10);
					echo "</td>" . chr(10);
					echo "<td>" .chr(10);
					echo "<input type='file' id='". $FlyFileSetting["JSON"]["FileVar"] . $NumJSONFiles . "' name='JSON_File" . $NumJSONFiles . "' accept='application/json' size='30' required>" . chr(10);
					echo "</td>";

					//Flughöhe
					echo "</tr>";
					echo "<tr class='rkrkedit'>" .chr(10);
					echo "<td>";
									//echo "	<button type='submit' name='JSON_File_Load'>File laden</button>" . chr(10);
					echo "<label for='". $FlyFileSetting["JSON"]["HightVar"] . $NumJSONFiles . "'>Flughöhe </label>" . chr(10);
					echo "</td>" . chr(10);
					echo "<td>" .chr(10);
					echo SelectFieldMultiOptArr("JSON_Flughoehe"  . $NumJSONFiles, $DefHeightNum ,$OptionFlughoehe );
					echo '<input type="hidden" name="NumJSONFiles" id="NumJSONFiles" value="'. $NumJSONFiles . '" required />'. chr(10);
					echo "<br>" . chr(10);
					echo "</td>";
					echo "</tr>";
					echo "</table>" .chr(10);
					$rkrkNeuQs = 'NeuesFeld=Y';
					if (isset($_GET['GA']) && trim((string)$_GET['GA']) !== '') {
						$rkrkNeuQs .= '&GA=' . rawurlencode(trim((string)$_GET['GA']));
					}
					if (isset($_GET['MDB']) && trim((string)$_GET['MDB']) !== '') {
						$rkrkNeuQs .= '&MDB=' . rawurlencode(trim((string)$_GET['MDB']));
					}
					echo '<input type="submit" name="NeuEinlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . htmlspecialchars($rkrkNeuQs, ENT_QUOTES, 'UTF-8') . '">' .chr(10);
					echo "<h3>JSON Dateinamendefinition</h3>";
					echo "<i>Bitte prüfe, dass dein JSON Dateiname folgender Konvention folgt, so dass die Felddaten korrekt eingelesen werden können.</i><br><br>";
					echo FilenameDefinition ();
					
				}

			} else {
				// =================================
				// Keine Auswahl getroffen
				///==================================
				echo "<br><i>Feldauswahl wählen und einlesen dücken!</i>" .chr(10);
			}
		}
		
		echo "<br><br>Feldwahl: <br>". chr(10);
		echo $ShowFieldsConfiguration;
		echo "</form>" .chr(10);
	}






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
	message.innerHTML= inputtxt;
	if(inputtxt.value.match(filter)) 
	{ 
		message.style.color = "green";
		message.innerHTML= "Eingabe korrekt";
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
<?php
	if (($SystemInfo != "") && ($DebugWork)) {
		echo "<div style='background-color: #FEF9E7;'>". chr(10);
		echo "System Meldungen: <br>". chr(10);
		echo $SystemInfo;
		echo "</div>". chr(10);
	}
	if (($DebugInfo != "") && ($DebugWork)) {
		echo "<div style='background-color: #FDEDEC;'>". chr(10);
		echo "Debug Meldung: <br>". chr(10);
		echo $DebugInfo;
		echo "</div>". chr(10);
	}
	echo "<br>". chr(10);
	Legends();
echo "</br>";
	include_once('scripts/rkrk_catalog_shot.php');
	echo "<script>\n";
	echo "(function(){\n";
	echo "  var btn = document.getElementById('rkrkRenewPicsBtn');\n";
	echo "  if (!btn) return;\n";
	echo "  var note = document.getElementById('rkrkRenewPicsNote');\n";
	echo "  var job = null;\n";
	echo "  try { job = JSON.parse(btn.getAttribute('data-job') || 'null'); } catch (e) { job = null; }\n";
	echo "  function paint(text){ if (note) note.textContent = text; }\n";
	echo "  function fileUrl(rel, bust){\n";
	echo "    var base = String((job && job.fileBase) || '');\n";
	echo "    var url = base + String(rel || '');\n";
	echo "    if (!bust) return url;\n";
	echo "    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + Date.now();\n";
	echo "  }\n";
	echo "  function showPic(kind, rel){\n";
	echo "    if (!rel) return;\n";
	echo "    var name = String(rel).split('/').pop();\n";
	echo "    var names = document.querySelectorAll('[data-rkrk-picname=\"' + kind + '\"]');\n";
	echo "    if (names.length) {\n";
	echo "      names[0].textContent = name;\n";
	echo "      for (var i = 1; i < names.length; i++) names[i].style.display = 'none';\n";
	echo "    }\n";
	echo "    var img = document.querySelector('[data-rkrk-picimg=\"' + kind + '\"]');\n";
	echo "    var link = document.querySelector('[data-rkrk-piclink=\"' + kind + '\"]');\n";
	echo "    var plain = fileUrl(rel, false);\n";
	echo "    var shown = fileUrl(rel, true);\n";
	echo "    if (img) {\n";
	echo "      img.src = shown;\n";
	echo "      if (link) link.href = plain;\n";
	echo "      return;\n";
	echo "    }\n";
	echo "    var cell = document.querySelector('[data-rkrk-piccell=\"' + kind + '\"]');\n";
	echo "    if (!cell) return;\n";
	echo "    var a = document.createElement('a');\n";
	echo "    a.href = plain;\n";
	echo "    a.target = 'blank';\n";
	echo "    a.setAttribute('data-rkrk-piclink', kind);\n";
	echo "    var im = document.createElement('img');\n";
	echo "    im.setAttribute('data-rkrk-picimg', kind);\n";
	echo "    im.height = 250;\n";
	echo "    im.style.objectFit = 'cover';\n";
	echo "    im.alt = kind === 'such' ? 'Suchfeld' : 'Flugplan';\n";
	echo "    im.src = shown;\n";
	echo "    a.appendChild(im);\n";
	echo "    cell.appendChild(a);\n";
	echo "  }\n";
	echo "  btn.addEventListener('click', function(ev){\n";
	echo "    ev.preventDefault();\n";
	echo "    if (!job || !job.feld_id || !/^[A-Za-z0-9]+-\\d+$/.test(String(job.feld_id))) {\n";
	echo "      paint('Für dieses Feld können Suchfeld- und Flugplanbild nicht erzeugt werden.');\n";
	echo "      return;\n";
	echo "    }\n";
	echo "    if (typeof window.rkrkStoreCatalogPictures !== 'function') {\n";
	echo "      paint('Suchfeld- und Flugplanbild konnten nicht erzeugt werden.');\n";
	echo "      return;\n";
	echo "    }\n";
	echo "    btn.disabled = true;\n";
	echo "    paint('Suchfeld- und Flugplanbild werden erneuert …');\n";
	echo "    window.rkrkStoreCatalogPictures(job).then(function(res){\n";
	echo "      if (!(res && res.ok)) {\n";
	echo "        paint('Suchfeld- und Flugplanbild konnten nicht erzeugt werden.');\n";
	echo "        return;\n";
	echo "      }\n";
	echo "      showPic('such', res.suchfeld_bild || '');\n";
	echo "      showPic('flug', res.flugplan_bild || '');\n";
	echo "      paint('Suchfeld- und Flugplanbild wurden erneuert.');\n";
	echo "    }).catch(function(){\n";
	echo "      paint('Suchfeld- und Flugplanbild konnten nicht erzeugt werden.');\n";
	echo "    }).then(function(){\n";
	echo "      btn.disabled = false;\n";
	echo "    });\n";
	echo "  });\n";
	echo "})();\n";
	echo "</script>\n";
	if (!empty($rkrkJsonShot) && is_array($rkrkJsonShot)) {
		$rkrkShotJson = json_encode($rkrkJsonShot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		echo "<div id='rkrkJsonShotNote'>Suchfeld- und Flugplanbild werden erzeugt …</div>\n";
		echo "<script>\n";
		echo "(function(){\n";
		echo "  var job = " . $rkrkShotJson . ";\n";
		echo "  var note = document.getElementById('rkrkJsonShotNote');\n";
		echo "  function paint(text){ if (note) note.textContent = text; }\n";
		echo "  if (!job || !job.id || !/^[A-Za-z0-9]+-\\d+$/.test(String(job.feld_id || ''))) { paint(''); return; }\n";
		echo "  if (typeof window.rkrkStoreCatalogPictures !== 'function') { paint('Feld ist gespeichert. Suchfeld- und Flugplanbild konnten nicht erzeugt werden.'); return; }\n";
		echo "  window.rkrkStoreCatalogPictures(job).then(function(res){\n";
		echo "    if (!(res && res.ok)) { paint('Feld ist gespeichert. Suchfeld- und Flugplanbild konnten nicht erzeugt werden.'); return; }\n";
		echo "    paint('Suchfeld- und Flugplanbild wurden am Feld gespeichert.');\n";
		echo "    var base = String(job.fileBase || '');\n";
		echo "    var box = document.getElementById('rkrkJsonShotPics');\n";
		echo "    if (!box) { box = document.createElement('div'); box.id = 'rkrkJsonShotPics'; if (note && note.parentNode) note.parentNode.insertBefore(box, note.nextSibling); }\n";
		echo "    function addPic(rel, label){\n";
		echo "      if (!rel || !box) return;\n";
		echo "      var img = document.createElement('img');\n";
		echo "      img.alt = label;\n";
		echo "      img.height = 220;\n";
		echo "      img.style.objectFit = 'cover';\n";
		echo "      img.style.margin = '8px 8px 0 0';\n";
		echo "      img.src = base + rel + (String(rel).indexOf('?') >= 0 ? '&' : '?') + 'v=' + Date.now();\n";
		echo "      box.appendChild(img);\n";
		echo "    }\n";
		echo "    addPic(res.suchfeld_bild, 'Suchfeld');\n";
		echo "    addPic(res.flugplan_bild, 'Flugplan');\n";
		echo "  }).catch(function(){\n";
		echo "    paint('Feld ist gespeichert. Suchfeld- und Flugplanbild konnten nicht erzeugt werden.');\n";
		echo "  });\n";
		echo "})();\n";
		echo "</script>\n";
	}
	echo "<script>\n";
	echo "(function(){\n";
	echo "  function missionPics(){\n";
	echo "    return Array.prototype.filter.call(document.querySelectorAll('img'), function(img){\n";
	echo "      var h = parseInt(img.getAttribute('height') || '0', 10);\n";
	echo "      var src = img.getAttribute('src') || '';\n";
	echo "      return h >= 200 && /\\.(jpe?g|png|webp)(\\?|$)/i.test(src);\n";
	echo "    });\n";
	echo "  }\n";
	echo "  function syncFeldkarte(){\n";
	echo "    var frames = document.querySelectorAll('iframe.rkrk-feldkarte');\n";
	echo "    var images = missionPics();\n";
	echo "    for (var i = 0; i < frames.length; i++) {\n";
	echo "      var frame = frames[i];\n";
	echo "      var pic = null;\n";
	echo "      for (var j = 0; j < images.length; j++) {\n";
	echo "        if (frame.compareDocumentPosition(images[j]) & Node.DOCUMENT_POSITION_PRECEDING) pic = images[j];\n";
	echo "      }\n";
	echo "      if (!pic) continue;\n";
	echo "      var w = pic.offsetWidth;\n";
	echo "      var h = pic.offsetHeight;\n";
	echo "      if (!(w >= 40 && h >= 40)) continue;\n";
	echo "      frame.style.width = w + 'px';\n";
	echo "      frame.style.height = h + 'px';\n";
	echo "      frame.setAttribute('width', String(w));\n";
	echo "      frame.setAttribute('height', String(h));\n";
	echo "    }\n";
	echo "  }\n";
	echo "  if (document.readyState === 'complete') syncFeldkarte();\n";
	echo "  else window.addEventListener('load', syncFeldkarte);\n";
	echo "})();\n";
	echo "</script>\n";
	echo "</div>" . chr(10);
?>


