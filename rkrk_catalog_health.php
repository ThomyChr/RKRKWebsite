<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_xmlfiles.php');
  include_once('scripts/rkrk_dji_kmz_interface.php');
  include_once('scripts/rkrk_kmz.php');
  include_once('scripts/rkrk_jsonfiles.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php'); 

	$pagetitle = "Feldkatalog Health";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

// ===========================================
// Konversion
// ===========================================


if (isset($_POST["SAVE"])) {
	$doSave= true;
} elseif (isset($_GET["SAVE"])) {
	$doSave= true;
} else {
	$doSave= false;
}

if (isset($_POST["Reg"])) {
	$RegSelected = $_POST["Reg"];
} elseif (isset($_GET["Reg"])) {
	$RegSelected = $_GET["Reg"];
} else {
	$RegSelected = "";
}
//echo "Reg: " . $RegSelected . "<br>";

if (isset($_POST["FeldIDListe"])) { 
	if ($_POST["FeldIDListe"]!="") {
		$FeldIDListe= $_POST["FeldIDListe"];
	} else {
		$FeldIDListe= "";
	}
} elseif (isset($_GET["FeldIDListe"])) { 
	if ($_GET["FeldIDListe"]!="") {
		$FeldIDListe= $_GET["FeldIDListe"];
	} else {
		$FeldIDListe= "";
	}
} else {
	$FeldIDListe= "";
}


SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

function DoConvertDataList ($FilterJG, $Stop=0) {
global 	$RKRKLocalLoactionMissionFiles,
		$xmlMissionLocation, 
		$RKRKFelderfileXML, 
		$RKRKMissionSelected,
		$RegionSettings,
		$FeldIDListe,
		$doSave;

	//echo "Feldkatalog: " . $xmlMissionLocation.$RKRKFelderfileXML . "<br>";
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	$RegList = implode(" ", $FilterJG);
	//echo "FeldIDListe: " . $FeldIDListe ."<br>";
	if ($FeldIDListe<>"") {
		$FeldIDArray = explode(" " ,$FeldIDListe);
	}
	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=0;
		if (in_array($JKey,$FilterJG)) {
			foreach ($JRegion as $Field) {
				if ((in_array($Field["FeldID"], $FeldIDArray)) or ($FeldIDListe=="")) {
					$OutputData[$Field["FeldID"]]["FeldID"] = $Field["FeldID"];
					//$OutputData[$Field["FeldID"]]["RefPoint"] = $Field["Grundstueckdaten"]["Zentrum"];
					$OutputData[$Field["FeldID"]]["JG"] = $Field["JG"];
					$OutputData[$Field["FeldID"]]["Feldname"] = $Field["Feldname"];
					$OutputData[$Field["FeldID"]]["GrundstueckID"] = $Field["GrundstueckID"];
					//echo "Zentrum: " . $Field["Grundstueckdaten"]["Zentrum"];
					$OutputData[$Field["FeldID"]]["Warnung"] = $Field["Warnung"];
					$OutputData[$Field["FeldID"]]["JasonFile"] = $Field["JasonFile"];
					foreach (explode("," ,$Field["JasonFile"]) as $Filename) {
						$JsonData = GetJSONData($RKRKLocalLoactionMissionFiles.$Filename);
						//pre_r($JsonData);
						//$OutputData[$Field["FeldID"]]["Flugweg"] = $JsonData["Flugweg"]; 
						$OutputData[$Field["FeldID"]]["Flaechendaten(alt)"] = $Field["Flaechendaten"]; 
						$OutputData[$Field["FeldID"]]["Flaechendaten"] = $JsonData["Flaechendaten"];
						$Feldkatalog[$Field["JG"]][$Field["FeldID"]]["Flaechendaten"] = $JsonData["Flaechendaten"];
					} //JSON File

					//pre_R($Field);
					$i++;
					if (($i >= $Stop) && ($Stop>0)) break;	
				}
			}	
		}
	}

	//pre_r($OutputData["21-014"]);
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	if ($doSave) {
		echo "Daten korrigiert!" . "<br>";
		SaveFlugkatalog($Feldkatalog, true);
		SaveFlugkatalog2CSV ($Feldkatalog);
	}
	
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

	//pre_R($OutputData);
	echo "<table>";
	echo "<tr>";
	echo "<th>";
	echo "FeldID";
	echo "</th>";
	echo "<th>";
	echo "JG";
	echo "</th>";
	echo "<th>";
	echo "Feldname";
	echo "</th>";
	echo "<th>";
	echo "GeoID";
	echo "</th>";
	echo "<th>";
	echo "Flächendaten (alt)";
	echo "</th>";
	echo "<th>";
	echo "Flächendaten (neu)";
	echo "</th>";
	echo "<th>";
	echo "FeldID";
	echo "</th>";
	echo "</tr>";

	foreach ($OutputData as $Data) {
		echo "<tr>";
		echo "<td>";
		echo $Data["FeldID"];
		echo "</td>";
		echo "<td>";
		echo $Data["JG"];
		echo "</td>";
		echo "<td>";
		echo $Data["Feldname"];
		echo "</td>";
		echo "<td>";
		echo $Data["GrundstueckID"];
		echo "</td>";
		echo "<td>";
		echo $Data["Flaechendaten(alt)"];
		echo "</td>";
		echo "<td>";
		echo $Data["Flaechendaten"];
		echo "</td>";
		echo "<td>";
		echo $Data["FeldID"];
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	unset($_SESSION[$PoiArraySession]);

}


$EnabledRegion = Array();
$RegionList = Array();

//form to change setting
echo "<b>Schliesst die Polygone für eine fehlerfreie Anzeige auf map.geo.admin.ch.:</b><br><br>";
echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;
//echo $i . " " . $KMLFileSettings->name . chr(10)
echo "<table class='rkrk-filter-form' width=100%>";
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Regionen: ";
		echo "</td><td>";
		$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, $RegSelected, true);
		$i++;
	echo "</td><td>";
	echo "<td rowspan=2>";
	echo MissionLibraryButtons();
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}
	
	echo "</td>";
	echo "<tr><td>";
	echo "  Felderliste: " .chr(10);
	echo "</td><td colspan=1>";
	echo InputField("FeldIDListe", $FeldIDListe, 20);
	echo "</td><td colspan>";
	echo "<i>Felder-ID List mit <Space> Abstand</i>";
	echo "</tr>";
	echo "</table  width=100%>";

	// JG/Region selection
	//pre_r($EnabledRegion);
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg==1)
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		}
		//pre_R($JGs2Select);
	}
	//pre_R($RegionsSelected);
    //pre_r($Feldkatalog);
	
	$RegionList= implode(" ",$RegionsSelected);
	//echo "RegList: " . ($RegionList) . "<br>";
	
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . '"> '. chr(10);
	echo '<input type="submit" name="Korrigieren" value="Korrigieren" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. "SAVE".'"> '. chr(10);
	echo '<br>';
	echo '</form>';
	echo '<br>';
	echo '<br>';

	
if (count($RegionsSelected)>0) {
	//$RegionList = array("21","25"); 
	DoConvertDataList($RegionsSelected, 0);
//	DoConvertDataList($RegionList, 0);
}
?>
</body>
</html>