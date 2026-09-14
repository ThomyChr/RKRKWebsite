<?php
	// Embed: Output puffern, am Ende nur geo.admin laden (kein Nested-Iframe / kein Menü-Müll).
	$__rkrkMapEmbedEarly = isset($_GET['Embed']) && (trim((string)$_GET['Embed']) === '1' || strtolower(trim((string)$_GET['Embed'])) === 'on');
	if ($__rkrkMapEmbedEarly) {
		ob_start();
	}
?><!DOCTYPE html>
<html lang="en">
<head>

<?php
/*
	https://tchr-frog.synology.me/rkropen/test/rkrk_map.php?Reg=21&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=ons
	https://tchr-frog.synology.me/rkropen/test/rkrk_map.php?MDB=M2&Reg=21&KA=S&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&HSP=off&Label=off&GeLa=off&Sun=off&SORA=off&WF=off&EX=no&MAP=&FFilter=&Zoom=0.8
	
	KA : Kartentyp F: Farbe, S: S/W, G: grau, M: SwissTLM, 
	MDB : Platform library M2; M3T; ""=Debug
	Reg : selektiere Regtion z.B, 21,15,25, ZG, AG, ALL
	File : file zum speicher der Daten z.B. "21_Felder" (extension braucht es nicht!)
	Deb : enable debug output
	Html : enable html output
	Strt : Ouput Startpoint (on/off)
	Cntr : Ouput Zentrumspunkt  (on/off)
	Area : Output Flightarea (on/off)
	Pfad : Output Flightpath (on/off)
	GDE : Output Gemeindegrenzen (on/off)
	JGG : Output Jagdgebietsgrenzen (on/off)
	HSP: Hochspannungsleitungen (on/off)
	Sun: Morgensonne (on/off  )
	KT: Kantone (on/off)
	ORT: Hauptorte mit Label (on/off)
	WW: Wegweiser,Reiseroute anzeigen (on/off)
	WF Wasserflächen anzeigen (on/off)
	SORA: SORA Zone (on/off)
	FZ: Flugzone (on/off)
	EG: Einsatzgebiet (on/off)
	Label : Output Label (on/off)
	Poi : Output POI daten (on/off)
	EX: External view (YES/NO) - for showing internal data
	MAP:
	FFilter: Filter Felder (21-002 21-006)
	Zoom : Grösse der Bilder und der Schriften
	JG: Jagdgebiet
	KA: Kartenart
	LAT: Lat centerpoint map
	LON: Lon centerpoint map
	ZO: Zoom Karte map
	OVR: Overviewpicture setting Suchplan
	SL=COL: single color (on/off)
	FZO: Flight Zone Options
	FLP: Overviewpicture setting Flugplanbild
	FDY: Flugtag
	MenuOn : Switch of Menu View (on/off)
	MapButtons: Show/hide Map Buttons (on/off) - default: on
	Webaccess: Zugriffslevel
*/


	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_geofence.php');
	include_once('scripts/rkrk_map_functions.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('rkrk_access_control.php'); 

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	$Layer = true;
	$ShowKeyUI = false;
	$ShowGraphUI = true;
	$DebugWork = false;
	$DebugOutput ="";

//============================
// Secure URL
// ===========================
	//$UseSecUrl=true;
	//pre_r($SecParamters);

	
// Example to call the site with options:
//https://tchr-frog.synology.me:5682/rkropen/test/test2023_flex_template_rkrk.php?Reg=21,25,28&Area=on&Poi=on&FFilter=28-029%2021-031#map



$MenuOn= GetURLtoFalseTrue("MenuOn", "MenuOn", "", true);
$isEmbeddedMap = isset($_GET['Embed']) && (trim((string)$_GET['Embed']) === '1' || strtolower(trim((string)$_GET['Embed'])) === 'on');
if ($isEmbeddedMap) {
	$MenuOn = false;
}

headerpage("Karten Drohnenrehkitzrettung", false);
echo '<link rel="stylesheet" href="template/rkrk_reg_pills.css?v=20260831a">' . "\n";
GrantAccess();

if ($isEmbeddedMap) {
	echo "<style>
html, body { margin:0 !important; padding:0 !important; height:100% !important; overflow:hidden !important; background:#ddd !important; }
.top, .bar, #debuginfo, #navDemo, form { display:none !important; }
#myDIV {
  position:fixed !important;
  top:0 !important; left:0 !important; right:0 !important; bottom:0 !important;
  margin:0 !important; padding:0 !important;
  z-index:1 !important;
  display:block !important;
  visibility:visible !important;
}
#myDIV > div, #myDIV > span { display:none !important; }
#myDIV iframe {
  display:block !important;
  position:absolute !important;
  top:0 !important; left:0 !important;
  width:100% !important; height:100% !important;
  min-height:100% !important;
  border:0 !important;
}
</style>";
	echo "<script>document.documentElement.classList.add('rkrk-embed-map');document.body.classList.add('rkrk-embed-map');</script>";
}

// Debug Message
// -------------
	echo "<div style='background-color: #FDEDEC;' id='debuginfo' name='debuginfo'>". chr(10);
	echo "</div>". chr(10);

//TopMenu
//--------------

if ($MenuOn) {
	CreateTopMenu();
	echo "<style>#navDemo{display:none !important;}#navDemo.show{display:block !important;}</style>";
	echo "<script>
	(function(){
	  window.addEventListener('DOMContentLoaded', function(){
		var navBars = document.querySelectorAll('div.top > div.bar.black.card');
		if (navBars.length > 1) {
		  for (var i = 1; i < navBars.length; i++) {
			var wrap = navBars[i].parentElement;
			if (wrap) wrap.remove();
		  }
		}
	  });
	})();
	</script>";
}

SetMissionLocation();
$MissionSelectionParameter = "&MDB=" . $RKRKMissionSelected;

	
// =================================
// Check input parameters 
// =================================

//if ($SecUrl) {
//	echo "<br><br>SecURL ON";
//	pre_R($SecParamters);
//} else {
//	echo "<br><br>SecURL OFF";
//}


// Check if esternal site// Intern-Extern
$EXselect =  GetURLwDefault_Lower("EX", "EX", "EXselect", "no");
	
if ($EXselect=="no") {
	//default
	$EXselect = "";
	$EX= "no";
} else {
	$EXselect = "on";
	$EX = "";
}	

// Schriftgrösse, Zoom
$SelectedZoom=  GetURLwDefault("Zoom", "Zoom", "SelectedZoom",1.0); 

// DebugData
$DebugWork=  GetURLwDefault_Lower("DBW", "DBW", "",""); 

//Suchplanbild
$Overview=  GetURLwDefault_Lower("OVR", "OVR", "","off"); 

//Flightplan
$Flugplan=  GetURLwDefault_Lower("FLP", "FLP", "","off"); 

// Map ZoomOut
$MapZoomOut=  GetURLwDefault("MapZoomOut", "MapZoomOut", "","1"); 

//Single color map
$SingleColor=  GetURLwDefault_Lower("SCOL", "SCOL", "","off"); 


if ($SingleColor=="on") {
		$SingleColor = true;
		$SColParameter = "&SCOL=on";
} else {
	//default
	$SingleColor = false;
	$SColParameter = "";
}

//FZO: Flight Zone Options
$FlightZoneOptions=  GetURLwDefault("FZO", "FZO", "",""); 

//Enable map buttons
$MapButtons=  GetURLwDefault_Lower("MapButtons", "MapButtons", "","on"); 

//echo "DebugWork: ". $DebugWork . "<br>";


//Write to LOGfile
WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ",$RKRKMissionSelected,"","");

//Load Feldkatalog
if (!isset($EnabledRegion) || !is_array($EnabledRegion)) {
	$EnabledRegion = array();
}
$Feldkatalog = GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
if (!is_array($Feldkatalog)) {
	$Feldkatalog = array();
}


//Logo
//echo "<img align='right' style='padding: 5px' src='" . $IconLoc . "/202302 Logo_RKRKZ_dark' height='80px'>". chr(10);

// Auswahl
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
if ($MenuOn) {
	if ($Layout == "2023") {
		echo "  <div class='container content center padding-16' style='max-width:1500px' id='choice'>" .chr(10);
		echo "    <br><h2 id='Titel' name='Titel' class='wide'>Flugfelder</h2>" .chr(10);
		//echo "    <p class='opacity'><i>Bitte auswählen:</i></p>" .chr(10);
		echo "  </div>" .chr(10);
		//echo "  <div class='container content left padding-32' style='max-width:1500px' id='choice'>" .chr(10);
	} else {
		echo "    <h2 id='Titel' name='Titel' class='wide'>Flugfelder</h2>" .chr(10);
	}
}


//ShowHide GUI
if ($ShowKeyUI)
	$HideSettingKeyUI = "";
else
	$HideSettingKeyUI = "hidden";
if ($ShowGraphUI)
	$HideSettingGraphUI = "";
else
	$HideSettingGraphUI = "hidden";


// NON GRAPHICAL GUI
if ($ShowKeyUI) 
	echo "<div>";
else
	echo "<div " . $HideSettingKeyUI. "";

	
echo "<table>";

//outside
echo "<tr>";
echo "<td>";

if ($MenuOn) {
	echo "<span  id='menu'>" .chr(10);
} else {
	echo "<span hidden id='menu'>" .chr(10);
}	

echo "<span style='display' id='bloc1'>" .chr(10);
//inside table
echo "<table>";
echo "<tr>";
// Regionen
echo "<td><b>Regionen: </b></td>";
echo "<td  colspan=2>";

$GrpName = "Opt";
$Gruppe = 200;
$i= $Gruppe;
$TmpArr = array();
$rkrkIsSpecialReg = function ($key) {
	$k = strcasecmp(trim((string)$key), 'leer') === 0
		|| strcasecmp(trim((string)$key), 'ALL') === 0
		|| strcasecmp(trim((string)$key), 'alle') === 0
		|| strcasecmp(trim((string)$key), 'NO') === 0;
	return $k || trim((string)$key) === '';
};
$fromPost = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST');
//process Regions preselections
if ($fromPost) {
	$orderedKeys = array('alle', 'leer');
	foreach ($RegionSettings as $Reg) {
		if (empty($Reg['Aktiv'])) {
			continue;
		}
		$key = (string)$Reg['Reg'];
		if ($rkrkIsSpecialReg($key)) {
			continue;
		}
		$orderedKeys[] = $key;
	}
	foreach ($orderedKeys as $key) {
		if (isset($_POST[$GrpName . '-' . $i])) {
			$TmpArr[$key] = $key;
		}
		$i++;
	}
	$GetRegions = $TmpArr;
} elseif ($SecUrl  and $SecParamters["Reg"]<>"") {
	$GetRegions= multiexplode(array(" ", ","),$SecParamters["Reg"]);
	foreach ($GetRegions as $Region) {
		$Region = trim((string)$Region);
		if ($Region !== '') {
			$TmpArr[$Region] = $Region;
		}
	}
	$GetRegions = $TmpArr;
} elseif (isset($_GET["Reg"]) and !$EnforceUseSecUrl) {
	$GetRegions = multiexplode(array(" ", ","),$_GET["Reg"]);
	$TmpArr = array();
	foreach ($GetRegions as $Region) {
		$Region = trim((string)$Region);
		if ($Region !== '') {
			$TmpArr[$Region] = $Region;
		}
	}
	$GetRegions = $TmpArr;
} else {
	$GetRegions = array();
}
$selectAllRegions = false;
$selectEmptyRegion = false;
foreach ($GetRegions as $rgKey => $rgVal) {
	$rg = trim((string)$rgKey);
	if (strcasecmp($rg, 'ALL') === 0 || strcasecmp($rg, 'alle') === 0) {
		$selectAllRegions = true;
	}
	if (strcasecmp($rg, 'leer') === 0 || strcasecmp($rg, 'NO') === 0) {
		$selectEmptyRegion = true;
	}
}
if ($GetRegions === array()) {
	$selectAllRegions = true;
}
if ($selectAllRegions) {
	$selectEmptyRegion = false;
}

//pre_r($GetRegions);
//pre_r($RegionSettings);

//process Regions form
$ArrayOfFields= array();
$RegSelectedList = array();
$RegOptions = array();
$RegOptions['alle'] = array('Text' => 'alle', 'Value' => $selectAllRegions, 'exclusive' => true);
$RegOptions['leer'] = array('Text' => 'leer', 'Value' => $selectEmptyRegion, 'exclusive' => true);
foreach ($RegionSettings as $Reg) {
	if (empty($Reg['Aktiv'])) {
		continue;
	}
	$key = (string)$Reg['Reg'];
	if ($rkrkIsSpecialReg($key)) {
		continue;
	}
	$RegOptions[$key] = array('Text' => $key, 'Value' => false, 'exclusive' => false);
	$isOn = !$selectAllRegions && !$selectEmptyRegion && (($GetRegions[$key] ?? '') !== '');
	if ($isOn) {
		$RegOptions[$key]['Value'] = true;
		$RegSelectedList[] = $key;
		if (isset($Feldkatalog[$key]) && is_array($Feldkatalog[$key])) {
			$ArrayOfFields = array_merge($ArrayOfFields, array_keys($Feldkatalog[$key]));
		}
	}
}
if ($selectAllRegions) {
	$RegSelectedList = array('ALL');
	foreach ($RegionSettings as $Reg) {
		if (empty($Reg['Aktiv'])) {
			continue;
		}
		$key = (string)$Reg['Reg'];
		if ($rkrkIsSpecialReg($key)) {
			continue;
		}
		if (isset($Feldkatalog[$key]) && is_array($Feldkatalog[$key])) {
			$ArrayOfFields = array_merge($ArrayOfFields, array_keys($Feldkatalog[$key]));
		}
	}
} elseif ($selectEmptyRegion) {
	$RegSelectedList = array('leer');
	$ArrayOfFields = array();
}
$RegSelected = implode(", ", $RegSelectedList);
$ListOfFields = implode(", ", $ArrayOfFields);
if ($RegSelected == "") 
	$RegSelected= "alle";
$EnabledOptions["Regions"] = PrintSelection($RegOptions , $GrpName ,$Gruppe, false);

//echo "Regions selected: ". $RegSelected;

//Flugtag
//echo "Flugtag: " . $_GET["FDY"] . "<br>";
$Flightday= GetURLwDefault("FDY", "FDY", "", "");
$DayFieldList = "";

if ($Flightday == "") {
	$FlightData = "";
}

if ($Flightday<>"") {
	//$Flightday = $_GET["FDY"];
	/**********************/
	//echo "Flugtag: " . $_GET["FDY"] . "<br>";
	//Laden Flugpläne
	$csv_file= date('Y', strtotime($Flightday)) . $RKRKFlightsCSV;
	$csv_deliminator = ";";

	//Read CSV file
	transferGoogleSheetToCSV();
	//echo "Filename: " . $csvDocLocation.$csv_file . "<br>";
	$data = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	//pre_R($data);
	$DayFieldList = Array();
	//echo "RegSelected: " . $RegSelected . "<br>";
	foreach ($data as $flight) {
		/*
		if (
			(strtotime($Flightday)>date("yyyy-mm-dd"))
			and (($RegSelected == "alle") 
		    or (strpos(" " . $RegSelected,$flight["Reg"])===false))
			)
			{
		*/
		//Heute & Zukunft
			if (
				($flight["Datum"]==$Flightday)
				and (($RegSelected == "alle") 
				or (strpos(" " . $RegSelected, $flight["Reg"])===false))
				)
				{
				$DayFieldList[] = $flight["Feld"];
			}
		/*
		} else {
			//Vergangenheit
			if (($flight["Datum"]==$Flightday) and
				($flight["Geflogen"]== "Ja")) {
				$DayFieldList[] = $flight["Feld"];
			}
		}
		*/
	}
	$DayFieldList = implode(" ", $DayFieldList);
	if ($DayFieldList=="")
		$DayFieldList="keine";
	//echo "DeayFlightList: " . $DayFieldList . "<br>";
	/**********************/
}

// Feld Filter
// $MenuOn bleibt beim ersten Default (an), ausser Embed=1 oder explizit MenuOn=off.
if ($isEmbeddedMap) {
	$MenuOn = false;
}

$FFilter= GetURLwDefault("FFilter", "FFilter", "FFilter", "");

if ($FFilter=="") {
	$FFilter = $DayFieldList;
}


//pre_R($ArrayOfFields);

echo "</td>";
if ($Layout != "2023") {
	$logo= "<td valign='center' align='right'  rowspan=5>";
	$logo.= "IG ". chr(34) ."Rehktitzrettung Knonaueramt und Zug". chr(34) ."<br><a href='http://www.drohnenrehkitzrettung.ch' target='blank'>www.drohnenrehkitzrettung.ch</a>";
	$logo.= "   </td>". chr(10);
	$logo.= "   <td width='80' align='right' rowspan=10>";
	$logo.= "<img src='" . $IconLoc . "/202121 Logo-RKRKZ.png' height='80px'>". chr(10);
	$logo.= "</td>". chr(10);
	echo $logo;
}
echo "</tr>";

$Suche=  GetURLwDefault("Suche", "Suche", "",""); 

//echo "Feldfilter: ";
//echo "<tr><td>Suche: </td><td colspan=4>";
//echo InputField("Suche", $Suche, 20);
//echo "</td></tr>";
//echo "<tr><td colspan=5>";
//echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '#choice' . '"> '. chr(10);
//echo "</td></tr>";

//////////////////
// Map
//////////////////
$MapTypes=Array();

echo "<tr>";
echo "<td>";
echo "<div " . $HideSettingKeyUI. ">";
echo "<b>Kartenansicht: </b>";
echo "</div>";
echo "</td>";
echo "<td colspan=2>";
echo "<div " . $HideSettingKeyUI. ">";
// Kartenansichten
$MapType =  GetURLwDefault("KA", "KA", "MapType", "S");
//$DebugOutput .= "MapType:" . $MapType . "<br>";

Foreach ($MapSettings["Types"] as $Key => $Map) {
	if ($Map["Aktiv"])
		$MapTypes[$Key]= $Map["Text"];
}

echo RadioField("MapType", $MapType ,$MapTypes);
//$EnabledOptions["Map"] = PrintSelection($MapTypes , 300, false);
echo "</div>";
echo "</td></tr>";
echo "<tr><td>";
echo "<div " . $HideSettingKeyUI. ">";
echo "<b>Optionen: </b>";
echo "</div>";
echo "</td><td colspan=4>";
echo "<div " . $HideSettingKeyUI. ">";
// Kartenoptionen
$MapOptions=Array();
$GrpName = "Opt";
$Gruppe =300;
$i= $Gruppe;
$MapParameter="";
$MinimalMapParameter="";
//$DebugOutput .= "QUERY:" . $_SERVER['QUERY_STRING'] . "<br>";
//$DebugOutput .= "WebFreigabe: " . $WebFreigabe . "<br>";
//$DebugOutput .= "MapOptions: " . implode(", ", array_keys($MapSettings["Options"])) . "<br>";
//$DebugOutput .= "GET: ";
Foreach ($MapSettings["Options"] as $Key => $Opt) {
	if (($Opt["Aktiv"]) and CheckAccess ($Opt["Access"])) {
//	if (($Opt["Aktiv"])) {
		$MapOptions[$Key]["Text"]= $Opt["Text"];
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if (isset($_POST[$GrpName."-".$i]))
				$MapOptions[$Key]["Value"]= $_POST[$GrpName."-".$i];
			else
				$MapOptions[$Key]["Value"]= false;
			//$DebugOutput .= " p " . $Key;
		} elseif ($SecUrl and ($SecParamters[$Key]<>"")) {
			if ($SecParamters[$Key]=="on") {
				$MapOptions[$Key]["Value"]= true;
			} else {
				$MapOptions[$Key]["Value"]= false;
			}
			//$DebugOutput .= " s " . $Key;
		//} elseif (isset($_GET[$Key]) and !$EnforceUseSecUrl) {
		} elseif (isset($_GET[$Key])) {
			if ($_GET[$Key]=="on") {
				$MapOptions[$Key]["Value"]= true;
			} else {
				$MapOptions[$Key]["Value"]= false;
			}
			//$DebugOutput .= " g " . $Key;
		} else {
			$MapOptions[$Key]["Value"]= $Opt["Value"];
			//$DebugOutput .= " v " . $Key;
		}
		
		if ($MapOptions[$Key]["Value"]) {
			$MapParameter .= "&".$Key."=".explode(",",$Opt["Options"])[0];
			$MinimalMapParameter .= "&".$Key."=".explode(",",$Opt["Options"])[0];
		} else
			$MapParameter .= "&".$Key."=".explode(",",$Opt["Options"])[1];
		$i++;
	}
}
//$DebugOutput .= "<br>";

//pre_R($MapSettings["Options"]);
//echo $MapParameter;
//$DebugOutput .= "MapParamter: " .$MapParameter . "<br>";

$EnabledOptions["Opt"] = PrintSelection($MapOptions , $GrpName ,$Gruppe, false);
echo "</div>";
echo "</td>";
echo "</tr><tr>";
echo "<td>";

echo "</td>";
echo "<td>";

echo "<div " . $HideSettingGraphUI. ">";
// "&EX=" . $EX . "&KA=F&MAP=" . $MAP. "&FFilter=". $FFilter . "&OVR=" . $Overview .
$SpecialOptions= $SColParameter;
if ($FlightZoneOptions<>"") 
	$SpecialOptions .= "&FZO=".$FlightZoneOptions;
if ($Flugplan=="on")
	$SpecialOptions .= "&FLP=on";

if ($Overview=="on")
	$SpecialOptions .= "&OVR=on";

$Otherparameter = "&EX=" . $EX . "&MAP=" . $MAP. "&FFilter=". $FFilter . $SpecialOptions;
//echo "RegSelected " . $RegSelected;
//pre_R($MapOptions);

$Search="";
//echo "MapType: " . $MapType . "<br>";
// Generate Arguments for MAP view
if ($selectAllRegions) {
	$RegionSelected = 'ALL';
} elseif ($selectEmptyRegion) {
	$RegionSelected = 'leer';
} else {
	$RegionSelected = implode(' ', $RegSelectedList);
	if ($RegionSelected === '') {
		$RegionSelected = 'leer';
	}
}

$MissionSelectionParameter = "&MDB=" . $RKRKMissionSelected;
$ParameterSet="Reg=".$RegionSelected.$MapParameter."&KA=".$MapType."&Zoom=". $SelectedZoom ."&FFilter=".$FFilter."&SFilter=".$Suche. $SpecialOptions.$MissionSelectionParameter.$GuestAccessParamter;
$MinimalMapParameter="Reg=".$RegionSelected.$MinimalMapParameter."&KA=".$MapType."&Zoom=". $SelectedZoom ."&FFilter=".$FFilter."&SFilter=".$Suche.$SpecialOptions.$MissionSelectionParameter.$GuestAccessParamter;
//$ParameterSet="Reg=AZ 25 ZG1&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=on&Label=off&Zoom=0.8";
//$ParameterSet="Reg=Amt&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&FFilter=";

if ($MenuOn) {
	echo KartenMenu2023 ($ThisPage, $RegSelected, $MapType, $MapOptions, $SelectedZoom, $MissionSelectionParameter , $Otherparameter);
	if ($EX=='no') {
		if (GetAccess ("InternPilots")) {
			if ($FFilter!="") {
				echo " <a href='rkrk_fluege.php?add=Y&AddFlugID=" . $FFilter . "&AddFlugToDate=" . date("Y-m-d", strtotime('tomorrow')). "' target='_editfluege'><img src='" . $IconLoc . "/addflug.png' height='" . $ButtonHeight . "px' title='Flugfelder: " . $FFilter . " für Morgen hinzufügen'></a>  ";
			}
			if ($UseSecUrl) {
				echo " <a href='rkrk_dokml.php?" . base64_encode($ParameterSet . "&OVR=" . $Overview . $SColParameter ."&File=RKRK_Felder_" .  str_replace(" ","_", $RegionSelected)) . "'><img src='" . $IconLoc . "/save.png' height='" . $ButtonHeight* 0.8 . "px' title='save' target='_savekml'></a>" . chr(10);
				echo " <a href='rkrk_dokml.php?" . base64_encode($ParameterSet . "&OVR=" . $Overview . $SColParameter ."&File=RKRK_Felder_" .  str_replace(" ","_", $RegionSelected) . "&DL=1") . "' download><img src='" . $IconLoc . "/dwnld_kml_35.png' height='" . $ButtonHeight* 0.8 . "px' title='KML lokal herunterladen'></a>" . chr(10);
			} else {
				echo " <a href='rkrk_dokml.php?" . $ParameterSet . "&OVR=" . $Overview . $SColParameter ."&File=RKRK_Felder_" .  str_replace(" ","_", $RegionSelected) . "'><img src='" . $IconLoc . "/save.png' height='" . $ButtonHeight* 0.8 . "px' title='save' target='_savekml'></a>" . chr(10);
				echo " <a href='rkrk_dokml.php?" . $ParameterSet . "&OVR=" . $Overview . $SColParameter ."&File=RKRK_Felder_" .  str_replace(" ","_", $RegionSelected) . "&DL=1' download><img src='" . $IconLoc . "/dwnld_kml_35.png' height='" . $ButtonHeight* 0.8 . "px' title='KML lokal herunterladen'></a>" . chr(10);
			}
			echo " <a href='rkrk_kml2xml.php'><img src='" . $IconLoc . "/settings.png' height='" . $ButtonHeight* 0.8 . "px' title='Settings'></a>" . chr(10);
		}
	}
}

if (!$ShowKeyUI) {
	// File Filter
	echo "  Feldfilter: ";
	echo InputField("FFilter", $FFilter, 30);
}
echo "</div>";
echo "</td><td>";

if ($ShowKeyUI) {
	// File Filter
	echo "Feldfilter: ";
	echo InputField("FFilter", $FFilter, 30);
}

echo "<div " . $HideSettingKeyUI. ">";
// Schriftgrösse
echo "Schriftgrösse:";
echo SelectField("SelectedZoom", $SelectedZoom ,Array(2.0,1.8,1.6,1.2,1.0,0.8,0.6,0.4));


SetMissionLocation();
//echo "<br>" ."DBW: " . $DebugWork. "<br>";
//echo "FileLoc: " .$MissionLocation. "<br>";
echo CheckField ("DBW", $DebugWork);
echo " Debug Daten";


SetMissionLocation();
//echo "<br>" ."DBW: " . $DebugWork. "<br>";
//echo "FileLoc: " .$MissionLocation. "<br>";
echo CheckField ("EXselect", $EXselect);
echo " Exerne Seite";
echo "</div>";

//pre_R($MapParameter);
echo '<input type="submit" name="Einlesen" value="Darstellen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '' . '"> '. chr(10);

echo  " <span id='swop1'>swop</span>";

echo "</td></tr>";
echo "</div hidden>";


//echo "<tr><td colspan=5>";
echo "</td></tr>";
echo "</table>";
echo "</td></tr>";
echo "<tr><td>";
//echo $ThisPage . " - " . $RegionSelected. " - " . $EX. " - " . $FFilter;

echo "</span>" .chr(10);

echo "<span style='display:none' id='bloc2'>" .chr(10);
echo  "<span id='swop2'>swop</span>";
//menu swop
echo "</span>" .chr(10);
//menu switch
echo "</span>" .chr(10);

?>
<script>
<?php 
echo "var MenuOn = '". $MenuOn . "';\n";
 ?>
	swop1.innerHTML = "<img  id='pic1' src='" +  iconloc + "/less.png' height='22px' title='Bedienungselemente verbergen' onclick='SwopOnImage(\"bloc1\", \"bloc2\" ,\"pic1\", \"" +  iconloc + "/more.png\", \"" +  iconloc + "/less.png\" )'>";
	swop2.innerHTML = "<img  id='pic1' src='" +  iconloc + "/more.png' height='22px' title='Bedienungselemente anzeigen' onclick='SwopOnImage(\"bloc1\", \"bloc2\" ,\"pic1\", \"" +  iconloc + "/more.png\", \"" +  iconloc + "/less.png\" )'>";
</script>
<?php

//ouside table
echo "</td></tr>";
echo "</table>";
echo "</form>" . chr(10);
echo '<script>
(function () {
  var form = document.querySelector("form");
  if (!form) return;
  var boxes = Array.prototype.slice.call(form.querySelectorAll(\'input[type="checkbox"][name^="Opt-"]\')).filter(function (cb) {
    var m = String(cb.name || "").match(/^Opt-(\\d+)$/);
    return m && Number(m[1]) >= 200 && Number(m[1]) < 300;
  });
  if (!boxes.length) return;
  function isExclusive(v) {
    v = String(v || "").toLowerCase();
    return v === "alle" || v === "all" || v === "leer";
  }
  var alle = null;
  var leer = null;
  boxes.forEach(function (cb) {
    var v = String(cb.value || "").toLowerCase();
    if (v === "alle" || v === "all") alle = cb;
    if (v === "leer") leer = cb;
  });
  function syncExclusive() {
    var exclusiveOn = (alle && alle.checked) || (leer && leer.checked);
    boxes.forEach(function (b) {
      if (isExclusive(b.value)) return;
      b.disabled = false;
      if (exclusiveOn) b.checked = false;
    });
  }
  boxes.forEach(function (cb) {
    cb.addEventListener("change", function () {
      var v = String(cb.value || "").toLowerCase();
      if (cb.checked && (v === "alle" || v === "all")) {
        if (leer) leer.checked = false;
      }
      if (cb.checked && v === "leer") {
        if (alle) alle.checked = false;
      }
      if (cb.checked && !isExclusive(cb.value)) {
        if (alle) alle.checked = false;
        if (leer) leer.checked = false;
      }
      syncExclusive();
    });
  });
  syncExclusive();
})();
</script>' . chr(10);
if ($UseSecUrl and false) {
	$QRCode = $WebLink_public . "rkrk_map.php?" . base64_encode(str_replace(array("&", " ","&EX=NO", "&EX=no"),array("%26","%2B", "", ""), $ParameterSet)); 
	$WebLink = $WebLink_public . "rkrk_map.php?" . base64_encode(str_replace("off","", $MinimalMapParameter)); 
} else {
	$QRCode = $WebLink_public . "rkrk_map.php?" . str_replace(array("&", " ","&EX=NO", "&EX=no"),array("%26","%2B", "", ""), $ParameterSet); 
	$WebLink = $WebLink_public . "rkrk_map.php?" . str_replace("off","", $MinimalMapParameter); 
}
//Print QR Code
if ($QRCode!="") {
	//echo $QRCode;
	$qrcode1.= "<br>Link zu dieser Grafik:<br>";
	//$qrcode1.= "<a href='".$WebLink."'><img src='https://chart.googleapis.com/chart?chs=350x350&cht=qr&chl=".$QRCode."&choe=UTF-8&chld=L' title='Link auf Map Ansicht' /></a><br>";
	// new source 21.05.2024 https://goqr.me/
	$qrcode1.= '<img src="https://api.qrserver.com/v1/create-qr-code/?color=000000&amp;bgcolor=FFFFFF&amp;data='.$QRCode.'&amp;qzone=1&amp;margin=0&amp;size=300x300&amp;ecc=M" alt="qr code" title="Link auf Map Ansicht"/>' . chr(10);
	//$qrcode1.= "<p style='font-size:4;width: 150px'>".$WebLink."</p>";
	$qrcode2.= "";
}

?>
<script>
	const qrSwop1 = document.getElementById('qrswop1');
	const qrSwop2 = document.getElementById('qrswop2');
	if (qrSwop1) {
		qrSwop1.innerHTML = "<img id='pic2' src='" +  iconloc + "/show_qr_t.png' height='35px' title='QR code anzeigen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
	}
	if (qrSwop2) {
		qrSwop2.innerHTML = "<img id='pic2' src='" +  iconloc + "/hide_qr_t.png' height='22px' title='QR code verbergen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
	}

</script>
<?php


// Create Map View	
// example call
//https://map.geo.admin.ch/?lang=de&topic=ech&bgLayer=ch.swisstopo.swissimage&layers=ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo-vd.geometa-grundbuch,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,ch.bazl.einschraenkungen-drohnen,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DALL%26Strt%3D%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Doff%26JGG%3Doff%26Poi%3Don%26Label%3Doff%26EX%3DNO%26KA%3DS%26MAP%3D%26Zoom%3D0.8&layers_visibility=false,false,false,false,false,true&E=2677473.70&N=1231853.81&zoom=6&layers_opacity=0.75,0.75,1,1,0.6,1


//$MapSettings["Types"][$KA]["Type"]
//$MapSettings["Types"][$KA]["Visibility"]
//$MapSettings["Types"][$KA]["Opacity"]

//echo "MapType " . $MapType . "<br>";
KartenSettings($MapType, $KaType, $KaLayer, $KaVisLayer, $KaOpacity);

$geoweblocation = dirname($ThisPage);
if (isset($ThisPageDirectory) && trim((string)$ThisPageDirectory) !== "") {
	$geoweblocation = rtrim((string)$ThisPageDirectory, "/");
}
// Fallback: absolute URL aus Host + Script-Verzeichnis
if ($geoweblocation === "" || $geoweblocation === "." || strpos($geoweblocation, "http") !== 0) {
	$__con = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https://" : "http://";
	$__host = isset($_SERVER["HTTP_HOST"]) ? (string)$_SERVER["HTTP_HOST"] : "";
	$__dir = str_replace("\\", "/", dirname((string)($_SERVER["PHP_SELF"] ?? "")));
	$geoweblocation = rtrim($__con . $__host . $__dir, "/");
}
$URLSearch= Array(" ", "|","=","&");
$URLReplace= Array("%20", "%7C","%3D","%26");
//$ParameterSet="Reg=AZ&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=on&JGG=on&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8";

//Handling Filefilters, Mid Point and Zoom
//----------------------------------------
//echo $RegionSelected;
if ($MapNorth=='' && $MapEast=='') {
	if ((strpos($RegionSelected, " ")==false) 
		and ($RegionSelected != "alle")
		and ($RegionSelected != "ALL")
		and ($RegionSelected != "leer"))
	{

		//single regions selected
		//$MapEast = GetRegionalSetting($RegionSelected, "MapEast");
		//$MapNorth = GetRegionalSetting($RegionSelected, "MapNorth");
		//$MapZoom = GetRegionalSetting($RegionSelected, "MapZoom");
		$MapEast = $RegionSettings[$RegionSelected]["GeoMapSetting"]["East"];
		$MapNorth = $RegionSettings[$RegionSelected]["GeoMapSetting"]["North"];
		$MapZoom = $RegionSettings[$RegionSelected]["GeoMapSetting"]["Zoom"];
	} else{
		if (($RegionSelected == "alle") or ($RegionSelected == "ALL") or ($RegionSelected == "leer")) {
			//multiple regions selected
			//$MapEast = GetRegionalSetting("ALL", "MapEast");
			//$MapNorth = GetRegionalSetting("ALL", "MapNorth");
			//$MapZoom = GetRegionalSetting("ALL", "MapZoom");
			$MapEast = $RegionSettings["ALL"]["GeoMapSetting"]["East"];
			$MapNorth = $RegionSettings["ALL"]["GeoMapSetting"]["North"];
			$MapZoom = $RegionSettings["ALL"]["GeoMapSetting"]["Zoom"];
		} else {
			//echo "Feldliste: " . $ListOfFields . "<br>";
			$FieldDiameter = DiameterOfFieldList($ArrayOfFields);
			$Centerpoint= explode(",", $FieldDiameter);
			//pre_R($Centerpoint);
			$Centerpoint["Lon"]=$Centerpoint[0];
			$Centerpoint["Lat"]=$Centerpoint[1];
			unset($Centerpoint[0]);
			unset($Centerpoint[1]);
			$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
			$MapEast=$MidPointFromFFilter["LV95East"];
			$MapNorth=$MidPointFromFFilter["LV95North"];
			$ZoomFactor = 22;
			$ZoomPower = -0.14;
			$MapZoom = $ZoomFactor*pow($Centerpoint[3],$ZoomPower);
		}
	}
	if ((($FFilter!="") or ($ListOfFields!="")) and ($Flightday=="")){
	//if ((FFilter!="") or ($ListOfFields!="")){
		if ($FFilter!="")
			$FFilterentries = explode(" ", str_replace(";"," ",str_replace(","," ",$FFilter)));
		else
			$FFilterentries = explode(" ", str_replace(";"," ",str_replace(","," ",$FFilter)));
		$FFilterentries = array_values(array_filter(array_map('trim', $FFilterentries), function($v){ return $v !== ""; }));
		$FocusEntries = $FFilterentries;
		if (count($FFilterentries) >= 3) {
			$pts = array();
			foreach ($FFilterentries as $fid) {
				$reg = substr($fid, 0, strpos($fid, "-"));
				if (!isset($Feldkatalog[$reg][$fid])) continue;
				$z = trim((string)($Feldkatalog[$reg][$fid]["Grundstueckdaten"]["Zentrum"] ?? ""));
				if ($z === "") {
					$z = trim((string)($Feldkatalog[$reg][$fid]["Grundstueckdaten"]["Startplatz"] ?? ""));
				}
				if ($z === "") continue;
				$parts = array_map('trim', explode(",", $z));
				if (count($parts) < 2) continue;
				$p = array("Lon" => (float)$parts[0], "Lat" => (float)$parts[1]);
				$lv = GetLSV95EastNorthFromLonLat($p);
				if (!isset($lv["LV95East"]) || !isset($lv["LV95North"])) continue;
				$pts[] = array(
					"id" => $fid,
					"e" => (float)$lv["LV95East"],
					"n" => (float)$lv["LV95North"]
				);
			}
			if (count($pts) >= 3) {
				$es = array_map(function($p){ return $p["e"]; }, $pts);
				$ns = array_map(function($p){ return $p["n"]; }, $pts);
				sort($es); sort($ns);
				$medE = $es[(int)floor(count($es)/2)];
				$medN = $ns[(int)floor(count($ns)/2)];
				$d = array();
				foreach ($pts as $p) {
					$dx = $p["e"] - $medE;
					$dy = $p["n"] - $medN;
					$d[] = array("id" => $p["id"], "dist" => sqrt($dx*$dx + $dy*$dy));
				}
				usort($d, function($a,$b){
					if ($a["dist"] == $b["dist"]) return 0;
					return ($a["dist"] < $b["dist"]) ? -1 : 1;
				});
				$cntD = count($d);
				$maxD = $d[$cntD-1]["dist"];
				$secondD = $d[$cntD-2]["dist"];
				if ($secondD > 0 && $maxD > (2.8 * $secondD)) {
					$dropId = $d[$cntD-1]["id"];
					$FocusEntries = array_values(array_filter($FFilterentries, function($x) use ($dropId){
						return $x !== $dropId;
					}));
				}
			}
		}
		if (count($FocusEntries) === 0) $FocusEntries = $FFilterentries;
		$FirstFileInFFilter = $FocusEntries[0] ?? "";
		$CountFilterEntries = count($FocusEntries);
		if ($FirstFileInFFilter!="") {
			$Centerpoint= explode(",", DiameterOfFieldList($FocusEntries));
			//$Centerpoint = explode(",", $Feldkatalog[substr($FirstFileInFFilter,0,strpos($FirstFileInFFilter,"-"))][$FirstFileInFFilter]["Grundstueckdaten"]["Zentrum"]);
			//echo "CenterP/Lon: " . $Centerpoint["Lon"];
			//echo " CenterP/Lat: " . $Centerpoint["Lat"];
			$Centerpoint["Lon"]=$Centerpoint[0];
			$Centerpoint["Lat"]=$Centerpoint[1];
			unset($Centerpoint[0]);
			unset($Centerpoint[1]);
			//pre_r($Centerpoint);
			$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
			//pre_r($MidPointFromFFilter);
			$MapEast=$MidPointFromFFilter["LV95East"];
			$MapNorth=$MidPointFromFFilter["LV95North"];
			//ECHO $CountFilterEntries;

			$ZoomFactor = 22;
			$ZoomPower = -0.13;
			//$ZoomFactor = 19;
			//$ZoomPower = -0.14;
			if ($CountFilterEntries!=1) {
				$MapZoom = $ZoomFactor*pow($Centerpoint[3],$ZoomPower);
			} else {
				//$FieldDiameter = DiameterOfField($FirstFileInFFilter);
				//$MapZoom = $ZoomFactor*pow($FieldDiameter,$ZoomPower);
				if ($Overview == "off") {
					$FieldDiameter = DiameterOfField($FirstFileInFFilter);
					$MapZoom = $ZoomFactor*pow($FieldDiameter,$ZoomPower);
				} else {
					$MapZoom =9.5;  // for 9.9 for Dave, orginal 9.0)
				}
			}
		}
	}
	if (($MapEast<0) or ($MapNorth<0)) {
		$MapEast = $RegionSettings["ALL"]["GeoMapSetting"]["East"];
		$MapNorth = $RegionSettings["ALL"]["GeoMapSetting"]["North"];
		$MapZoom = $RegionSettings["ALL"]["GeoMapSetting"]["Zoom"];
	}
}
//if ($InputUserInfo["Login"]["Level"]!="") $WebFreigabe = $InputUserInfo["Login"]["Level"];
$WebAccessParamter = "&WebAccess=".$WebFreigabe;

?>
<style>
#myDIV {
  width:99%;
  position:absolute;
  height:100%;
}
#myDIV div{
//  background-color:white !important;
  opacity:1!important;
  position:absolute;
//  opacity:0.5;
  border-radius: 2%
}
#myBox {
  position:absolute;
  background-color:red!important;
  opacity:1!important;
  z-index: 2;
}
</style>

<?php

echo "<div id='myDIV'>" .chr(10);
//echo "<div id='myBox'>myBox</div>" .chr(10);
//echo "<div style='top:20px;left:40px;z-index:0;'>z-index 0</div>" .chr(10);
echo "<span id='qrswop1'></span>" . chr(10);
echo "<span style='display:none' id='qrswop2'></span>" . chr(10);
if ($MapButtons=="on") {
	if ($Layer)
		echo "<div style='top:10px;right:60px;z-index:1;'>" .chr(10);
	echo PageButton($ThisPage, $RegionSelected, $EX, $FFilter);
	if ($Layer)
		echo "</div>";
}

echo "<div style='display:none;top:60px;left:20px;z-index:1;background-color:white;text-align : center;
' id='qrbloc1'>" .chr(10);
echo $qrcode1;
echo "</div>";
echo "<div style='display' id='qrbloc2'>" .chr(10);
echo $qrcode2;
echo "</div>";


/*
Änderungen:
1. https://test.map.geo.admin.ch/#/map statt https://map.geo.admin.ch/
2. layers-Auflistung ist neu ; getrennt und nicht mehr ,
3. KML-Notation ist neu
KML|https...
4. Zentrierung: &center=2660000,1190000&z=2
statt &E=2677894.19&N=1230647.47&zoom=8
*/
	
	//$MapURL = "https://test.map.geo.admin.ch/embed.html?";
	//Showing Map with header
	//$MapGeoAdminURL = "https://map.geo.admin.ch/#/map?";
	//Showing Map embedded without a header
	//$MapGeoAdminURL = "https://map.geo.admin.ch/#/embed?";	

	$ZoomParamter = "&z=" . (is_numeric($MapZoom) && is_numeric($MapZoomOut) && floatval($MapZoomOut) != 0
		? ($MapZoom / $MapZoomOut)
		: 6);
	if ($MapEast === '' || $MapEast === null || $MapNorth === '' || $MapNorth === null) {
		$MapEast = $RegionSettings["ALL"]["GeoMapSetting"]["East"] ?? 2678000;
		$MapNorth = $RegionSettings["ALL"]["GeoMapSetting"]["North"] ?? 1232000;
	}
	$Centering = "center=" . $MapEast . "," . $MapNorth;
	if (!isset($BBoxEast)) { $BBoxEast = ''; }
	if (!isset($BBoxNorth)) { $BBoxNorth = ''; }
	if (!isset($BBoxZoom)) { $BBoxZoom = ''; }




if ($UseSecUrl) {
	$DoKMLURL  = "rkrk_dokml.php?". base64_encode(str_replace($URLSearch, $URLReplace, $ParameterSet.$WebAccessParamter));
	//$DoKMLURL  = "rkrk_dokml.php?". str_replace($URLSearch, $URLReplace, $ParameterSet.$WebAccessParamter) . "&" . $Centering . $ZoomParamter;
} else {
	//$DoKMLURL  = "rkrk_dokml.php?". str_replace($URLSearch, $URLReplace, $ParameterSet.$WebAccessParamter);
	$DoKMLURL  = "rkrk_dokml.php?". str_replace($URLSearch, $URLReplace, $ParameterSet.$WebAccessParamter);
}
// Vorgefertigte KML-Datei (z.B. geflogene Flächen) statt dynamischem FFilter
$UseKmlFile = '';
if (isset($_GET['UseKmlFile']) && trim((string)$_GET['UseKmlFile']) !== '') {
	$UseKmlFile = basename(preg_replace('/\.kml$/i', '', trim((string)$_GET['UseKmlFile'])));
} elseif (isset($_GET['UseFile']) && trim((string)$_GET['UseFile']) !== '') {
	$UseKmlFile = basename(preg_replace('/\.kml$/i', '', trim((string)$_GET['UseFile'])));
}
if ($UseKmlFile !== '') {
	// Query vollständig encodieren (inkl. & / =), sonst zerbricht geo.admin die KML-URL
	$DoKMLURL = "rkrk_dokml.php?" . str_replace(
		$URLSearch,
		$URLReplace,
		"UseFile=" . $UseKmlFile . $MissionSelectionParameter . $WebAccessParamter
	);
} else {
	$DoKMLURL .= "&bbox_east=$BBoxEast&bbox_north=$BBoxNorth&bbox_zoom=$BBoxZoom";
}

//$DebugOutput .= "<br>DO_KML Link: " .$DoKMLURL;

//$FullMapURL = $MapGeoAdminURL . $Search . "lang=de&topic=ech&bgLayer=" . $KaType  ."&layers=" . $KaLayer  ."KML|https:%2F%2Ftchr-frog.synology.me%2Frkropen%2Fv2%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26FZ%3Don%26GDE%3Doff%26JGG%3Doff%26Poi%3Don%26HSP%3Doff%26Label%3Doff%26GeLa%3Doff%26Alert%3Doff%26Sun%3Doff%26EG%3Doff%26SORA%3Doff%26WF%3Doff%26KA%3DS%26Zoom%3D0.8%26FFilter%3D%26SFilter%3D%26MDB%3DM3T%26GA%3Dgast%26WebAccess%3Dtest" . $Centering . $ZoomParamter;
$FullMapURL = $MapGeoAdminURL . $Search . "lang=de&topic=ech&" . $Centering . $ZoomParamter . "&bgLayer=" . $KaType  ."&layers=" . $KaLayer  .";KML|" . $geoweblocation. "/" . $DoKMLURL;

// Auto-Save URL (SVG only by default)
$AutoSaveURL = $FullMapURL . "&SVG=on&PNG=off&JPG=off&auto=0";

//$DebugOutput .= "<br>FullMapURL Link: " .$FullMapURL;

//echo $FullMapURL . "<br><br><br>";
//echo "<iframe allow='geolocation' src='".$FullMapURL."'' framebOrder='0' style='bOrder:0;overflow:hidden;height:100%;width:100%;min-height:80vh'></iframe>";

if (!empty($isEmbeddedMap)) {
	// Buffer verwerfen und dieses Frame direkt auf geo.admin navigieren (vermeidet leere Nested-Iframes).
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-store');
	}
	$mapJson = json_encode((string)$FullMapURL, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<title>Karte</title>';
	echo '<style>html,body{margin:0;height:100%;overflow:hidden;background:#cfd5dc;font:14px/1.4 sans-serif;color:#334}</style>';
	echo '<script>try{location.replace(' . $mapJson . ');}catch(e){document.write(' . json_encode('<p>Karte konnte nicht geladen werden.</p>', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');}</script>';
	echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars((string)$FullMapURL, ENT_QUOTES, 'UTF-8') . '">';
	echo '<p style="padding:12px"><a href="' . htmlspecialchars((string)$FullMapURL, ENT_QUOTES, 'UTF-8') . '">Karte öffnen</a></p></noscript>';
	echo '</head><body><p style="padding:12px">Karte wird geladen …</p></body></html>';
	exit;
}

echo "<iframe allow='geolocation' src='".$FullMapURL."'' framebOrder='0' style='bOrder:0;overflow:hidden;height:100%;width:100%;min-height:80vh'></iframe>";


/* neu kopiert
https://test.map.geo.admin.ch/#/map?lang=de&center=2681575.51,1232534.3&z=6&bgLayer=ch.swisstopo.swissimage&topic=ech&layers=ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f;ch.bazl.einschraenkungen-drohnen,f;KML%7CFGHO+2024+plus.kml
*/

/*
https://test.map.geo.admin.ch/#/map?lang=de&topic=ech
&bgLayer=ch.swisstopo.swissimage&layers=ch.bfe.elektrische-anlagen_ueber_36,ch.bazl.luftfahrthindernis,ch.bazl.einschraenkungen-drohnen,
KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26FZ%3Don%26GDE%3Doff%26JGG%3Doff%26Poi%3Don%26HSP%3Doff%26Label%3Doff%26GeLa%3Doff%26Alert%3Doff%26Sun%3Doff%26EG%3Doff%26SORA%3Doff%26WF%3Doff%26KA%3DS%26Zoom%3D0.8%26FFilter%3D%26SFilter%3D%26MDB%3DM3T%26GA%3Dgast%26WebAccess%3Dtest
&layers_visibility=false,false,false,true&center=2660000,1190000&z=2&layers_opacity=1,1,0.6,1

https://test.map.geo.admin.ch/#/map?lang=de&topic=ech
&bgLayer=ch.swisstopo.swissimage
&layers=KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Fv2%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26FZ%3Don%26GDE%3Doff%26JGG%3Doff%26Poi%3Don%26HSP%3Doff%26Label%3Doff%26GeLa%3Doff%26Alert%3Doff%26Sun%3Doff%26EG%3Doff%26SORA%3Doff%26WF%3Doff%26KA%3DS%26Zoom%3D0.8%26FFilter%3D%26SFilter%3D%26MDB%3DM3T%26GA%3Dgast%26WebAccess%3Dtest
&E=2677894.19&N=1230647.47&zoom=8
*/

if ($RegSelected!="leer") {
	if ($Layout != "2023" or true) {
		if ($Layer)
			echo "<div style='bottom:". round($SelectedZoom*60) ."px;left:20px;z-index:1;color:".$colorfontstd."'>" .chr(10);
			//echo "<div style='bottom:". round($SelectedZoom*170) ."px;left:20px;z-index:1;color:".$colorfontstd."'>" .chr(10);
		//echo "LEGEND L: ".$SingleColor."  M: ".$MenuOn."<br>";
		//if (!$SingleColor) {
		if ((!$SingleColor) AND ($MenuOn)) {
			$legend = mapcolor_legend($EX == "no",round($SelectedZoom*50) . "%");
			$legend .= "<br>";
			$legend .= region_legend(round($SelectedZoom*50) . "%", str_replace(" ",",",$RegionSelected));
			echo $legend;
		}	
		
		//mapcolor_legend($EX == "no",round($SelectedZoom*120) . "%");
		//echo "<br>";
		//region_legend(round($SelectedZoom*120) . "%");
		if ($Layer)
			echo "</div>";
		//footer();
	}
}
//echo "Mapparameter: " . $MapParameter . "<br>";
//echo "Parameterset: " . $ParameterSet . "<br>";

echo "</div>";

?>


