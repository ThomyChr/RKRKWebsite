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

	$pagetitle = "Konvertierung von Flugfeldern";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

// ===========================================
// Konversion nur zwischen aktiven Feldkatalogen.
// Der Debug-Katalog baut auf der Mavic 3T auf und ist kein eigenes Ziel.
// ===========================================

function rkrk_convert_mission_active($key) {
	global $MissionDroneTypes;
	$key = (string)$key;
	if ($key === '' || !isset($MissionDroneTypes[$key]) || !is_array($MissionDroneTypes[$key])) {
		return false;
	}
	return !empty($MissionDroneTypes[$key]['active']);
}

function rkrk_convert_basis_key($key) {
	$key = (string)$key;
	return $key === 'D' || $key === '4CX' ? '3T' : $key;
}

function rkrk_convert_active_targets($selected) {
	global $MissionDroneTypes;
	$basis = rkrk_convert_basis_key($selected);
	$out = array();
	foreach ($MissionDroneTypes as $key => $row) {
		$key = (string)$key;
		if ($key === 'D' || $key === $basis || $key === (string)$selected || !rkrk_convert_mission_active($key)) {
			continue;
		}
		if (trim((string)($row['MissionLocation'] ?? '')) === '' || trim((string)($row['kmzConvertTo'] ?? '')) !== '') {
			continue;
		}
		$out[] = $key;
	}
	return $out;
}

function rkrk_convert_target_allowed($selected, $target) {
	return in_array((string)$target, rkrk_convert_active_targets($selected), true);
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

if (isset($_GET["UpdFeld"])) {
	$UpdFeld= $_GET["UpdFeld"];
} else {
	$UpdFeld="";
}

if (isset($_GET["DelFeld"])) {
	$DelFeld= $_GET["DelFeld"];
} else {
	$DelFeld="";
}

if (isset($_POST["Reg"])) {
	$RegSelected = $_POST["Reg"];
} elseif (isset($_GET["Reg"])) {
	$RegSelected = $_GET["Reg"];
} else {
	$RegSelected = "";
}
//echo "Reg: " . $RegSelected . "<br>";

///////////////////////////////////////
// Conversion to other Drone
///////////////////////////////////////
if (($ConFile!="") and ($ConTo!="") and rkrk_convert_target_allowed($RKRKMissionSelected, $ConTo)) {
	//echo $ConFile . " transfered from " . $RKRKMissionSelected . " to " . $ConTo . "<br>";
	$ext = pathinfo($ConFile, PATHINFO_EXTENSION);
	$specFrom = rkrk_convert_basis_key($RKRKMissionSelected);
	if ($ext=="json") {
		//echo "JSON Konvertieren: " . $ConFile . "<br>";
		CreateJSONforNewDrone($ConFile, $specFrom, $ConTo);
	} else {
		//echo "KMZ Konvertieren: " . $ConFile . "<br>";
		CreateKMZforNewDrone($ConFile, $specFrom, $ConTo);
	}
	//readfile($RKRKRelWebLocation. $MissionDroneTypes[$RKRKMissionSelected]["MissionLocation"].$ConFile); 
}


///////////////////////////////////////
// Update Daten in xml		
///////////////////////////////////////
if ($UpdFeld!="" && rkrk_convert_target_allowed($RKRKMissionSelected, $ConTo)){
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	//echo "Update " . $UpdFeld . " in " . $ConTo . "<br>";
	UpdateXMLDateInOtherMission(multiexplode(array("_","-"), $UpdFeld)[0], $UpdFeld, $Feldkatalog, "", $RKRKMissionSelected, $ConTo, true);
}

///////////////////////////////////////
// Löschen von Daten in xml	und im Drive
///////////////////////////////////////
if ($DelFeld!="" && rkrk_convert_target_allowed($RKRKMissionSelected, $ConTo)){
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	//echo "Delete " . $DelFeld . " in " . $ConTo . "<br>";
	DeleteXMLDateInOtherMission(multiexplode(array("_","-"), $DelFeld)[0], $DelFeld, $Feldkatalog, "", $RKRKMissionSelected, $ConTo, true);
}



// ===========================================
// Vergleiche Feldkatalog mit FileListe
// ===========================================

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
$TargetFeldkatalog = array();
$TargetMissionsArray = rkrk_convert_mission_active($RKRKMissionSelected)
	? rkrk_convert_active_targets($RKRKMissionSelected)
	: array();
foreach ($TargetMissionsArray as $TargetMission) {
	if ($TargetMission === '' || !isset($MissionDroneTypes[$TargetMission]["MissionLocation"])) {
		continue;
	}
	$RelTargetMissionLocation = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"];
	$RelTargetXMLFeldkatalog = $RelTargetMissionLocation. $xmlFileDirectory.$RKRKFelderfileXML;
	$TargetFeldkatalog[$TargetMission] = GetFeldkatalog($RelTargetXMLFeldkatalog);
}	
//pre_R($TargetMissionsArray);
//pre_r($TargetFeldkatalog);

function rkrk_convert_katalog_feld($katalog, $mission, $jg, $feldId) {
	if (!is_array($katalog) || $mission === null || $mission === '') {
		return array();
	}
	if (!isset($katalog[$mission]) || !is_array($katalog[$mission])) {
		return array();
	}
	if ($jg === null || $jg === '' || !isset($katalog[$mission][$jg]) || !is_array($katalog[$mission][$jg])) {
		return array();
	}
	if ($feldId === null || $feldId === '' || !isset($katalog[$mission][$jg][$feldId]) || !is_array($katalog[$mission][$jg][$feldId])) {
		return array();
	}
	return $katalog[$mission][$jg][$feldId];
}

function DoConvertDataList ($FilterJG, $Stop=0) {
global 	$RKRKRelWebLocation,
		$RKRKLocalLoactionMissionFiles,
		$xmlMissionLocation, 
		$RKRKFelderfileXML, 
		$TargetFeldkatalog,
		$TargetMissionsArray, 
		$RKRKMissionSelected,
		$IconLoc,
		$ButtonHeight,
		$MissionDroneTypes,
		$RegionSettings;

	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	$RegList = implode(" ", $FilterJG);
	//pre_r ($TargetFeldkatalog);
	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=0;
		if (in_array($JKey,$FilterJG) && is_array($JRegion)) {
			foreach ($JRegion as $Field) {
				if (!is_array($Field)) {
					continue;
				}
				$feldId = isset($Field["FeldID"]) ? (string)$Field["FeldID"] : '';
				if ($feldId === '') {
					continue;
				}
				$feldJg = array_key_exists("JG", $Field) ? $Field["JG"] : $JKey;
				$ConData[$feldId]["FeldID"] = $feldId;
				//$ConData[$Field["FeldID"]]["RefPoint"] = $Field["Grundstueckdaten"]["Zentrum"];
				$ConData[$feldId]["JG"] = $feldJg;
				$ConData[$feldId]["Feldname"] = isset($Field["Feldname"]) ? $Field["Feldname"] : '';
				$ConData[$feldId]["GrundstueckID"] = isset($Field["GrundstueckID"]) ? $Field["GrundstueckID"] : '';
				//echo "Zentrum: " . $Field["Grundstueckdaten"]["Zentrum"];
				$ConData[$feldId]["Warnung"] = isset($Field["Warnung"]) ? $Field["Warnung"] : '';
	
				foreach($TargetMissionsArray as $TargetMission) {
					if ($TargetMission!="") {
						//echo "TargetMission: " . $TargetMission . " JG " . $JKey . " FeldID " . $Field["FeldID"] . "<br>";
						$TargetField = rkrk_convert_katalog_feld($TargetFeldkatalog, $TargetMission, $feldJg, $feldId);
						$SourceField = $ConData[$feldId];
						//if ($Field["FeldID"]=="21-014") {
							//pre_r($TargetFeldkatalog[$TargetMission][$JKey][$Field["FeldID"]]);
							//pre_r($TargetField);
							//pre_R($SourceField );
						//}
						$TargetFieldByRegion = rkrk_convert_katalog_feld($TargetFeldkatalog, $TargetMission, $JKey, $feldId);
						if (VergleicheFeldDaten($SourceField, $TargetFieldByRegion)) {
						//if (VergleicheFeldDaten($SourceField, $TargetField)) {
							$ConData[$Field["FeldID"]]["upd"][$TargetMission]["Icon"] = $IconLoc . "/allok.png";
							//$ConData[$Field["FeldID"]]["upd"][$TargetMission]["Link"] = htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList;
							$ConData[$Field["FeldID"]]["upd"][$TargetMission]["Link"] = htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList . "&UpdFeld=" . $Field["FeldID"] . "&ConTo=" . $TargetMission;
							$ConData[$Field["FeldID"]]["del"][$TargetMission]["Icon"] = $IconLoc . "/delete.png";
							$ConData[$Field["FeldID"]]["del"][$TargetMission]["Link"] = htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList . "&DelFeld=" . $Field["FeldID"] . "&ConTo=" . $TargetMission;
						} else  {
							$ConData[$Field["FeldID"]]["upd"][$TargetMission]["Icon"] = $IconLoc . "/dataupdate.png";
							$ConData[$Field["FeldID"]]["upd"][$TargetMission]["Link"] = htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList . "&UpdFeld=" . $Field["FeldID"] . "&ConTo=" . $TargetMission;
							$ConData[$Field["FeldID"]]["del"][$TargetMission]["Icon"] = "";
							$ConData[$Field["FeldID"]]["del"][$TargetMission]["Link"] = "";
						}
						$JsonFileList= explode(",",$Field["JasonFile"]);
						foreach($JsonFileList as $FileLink) {
							$NewHeight = GetFlightHeightForAltDrone(rkrk_convert_basis_key($RKRKMissionSelected), $TargetMission, GetMeaningFilename($FileLink)[4], "IRCamAngle_H", 5);
							$NewDroneFilename = ChangeHeightInFilename($FileLink, $NewHeight, $TargetMission, false).".json";
							$FullFileLink = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$RegionSettings[$Field['JG']]["Dir"]."/".$NewDroneFilename;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Filename"]=$FileLink;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["NewHeight"]=$NewHeight;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["NewFilename"]=$NewDroneFilename;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["FullFileLink"]=$FullFileLink;
							if (file_exists($FullFileLink)) {
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["FileExists"]=true;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Icon"]=$IconLoc . "/". $MissionDroneTypes[$TargetMission]["json_button"];
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Link"]=$RKRKLocalLoactionMissionFiles.$FileLink;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Title"]="Herunterladen von ".  $FileLink;
							} else {
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["FileExists"]=false;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Icon"]=$IconLoc . "/". $MissionDroneTypes[$TargetMission]["json_button_orange"];
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Link"]=htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList . "&ConFile=" . $FileLink . "&ConTo=" . $TargetMission;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["JSON"][$FileLink]["Title"]="Konvertieren von ".  $FileLink;
							}
						}
						$KMZFileList= explode(",",$Field["KMZFile"]);
						foreach($KMZFileList as $FileLink) {
							$NewHeight = GetFlightHeightForAltDrone(rkrk_convert_basis_key($RKRKMissionSelected), $TargetMission, GetMeaningFilename($FileLink)[4], "IRCamAngle_H", 5);
							$NewDroneFilename = ChangeHeightInFilename($FileLink, $NewHeight, $TargetMission, false).".kmz";
							$FullFileLink = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"].$RegionSettings[$Field['JG']]["Dir"]."/".$NewDroneFilename;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Filename"]=$FileLink;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["NewHeight"]=$NewHeight;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["NewFilename"]=$NewDroneFilename;
							$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["FullFileLink"]=$FullFileLink;
							if (file_exists($FullFileLink)) {
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["FileExists"]=true;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Icon"]=$IconLoc . "/". $MissionDroneTypes[$TargetMission]["kmz_button"];
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Link"]=$FullFileLink;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Title"]="Herunterladen von ".  $FileLink;
							} else {
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["FileExists"]=false;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Icon"]=$IconLoc . "/". $MissionDroneTypes[$TargetMission]["kmz_button_orange"];
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Link"]=htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "Reg=" . $RegList . "&ConFile=" . $FileLink . "&ConTo=" . $TargetMission;
								$ConData[$Field["FeldID"]]["Files"][$TargetMission]["KMZ"][$FileLink]["Title"]="Konvertieren von ".  $FileLink;
							}
						}
					}
				}

				$i++;
				if (($i >= $Stop) && ($Stop>0)) break;	
			}	
		}
	}

	//pre_r($ConData["21-014"]);
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

	//pre_R($ConData);
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
	foreach ($TargetMissionsArray as $TargetMission) {
		echo "<th>";
		echo $TargetMission . " Json";
		echo "</th>";
		echo "<th>";
		echo $TargetMission . "KMZ";
		echo "</th>";
		echo "<th>";
		echo $TargetMission . " Daten";
		echo "</th>";
	}	
	echo "<th>";
	echo "FeldID";
	echo "</th>";
	echo "</tr>";

	foreach ($ConData as $Data) {
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
		foreach ($Data["Files"] as $key => $Files) {
			foreach ($Files as $Mission) {
				echo "<td>";
				foreach ($Mission as $File) {
					//pre_r($File);
				echo "<a href='" . $File["Link"] . "'><img src='". $File["Icon"] . "' height='" . $ButtonHeight ."' title='". $File["Title"] ."' ></a> <wbr>	" . chr(10);
				}
				echo "</td>";
			}
		}
		foreach ($Data["upd"] as $key => $TargetMission) {
			//pre_R($TargetMission);
			echo "<td>";
			echo "<a href='" . $TargetMission["Link"] . "'><img src='". $TargetMission["Icon"] . "' height='" . $ButtonHeight ."' title='JSON und KMZ erstellen und XML Daten auf Mission " . $key . " abgleichen.' ></a> <wbr>	" . chr(10);
			if ($Data["del"][$key]["Link"]<>"") {
				echo "<a href='" . $Data["del"][$key]["Link"] . "'><img src='". $Data["del"][$key]["Icon"] . "' height='" . $ButtonHeight ."' title='Alle Daten von " .$Data["FeldID"] . " in Mission " . $key . " löschen.' ></a> <wbr>	" . chr(10);
			}	
			echo "</td>";
		}	
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
$RegionsSelected = Array();

//form to change setting
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
	
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. ($EnableDeleteSet ?? '').'">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. ($EnableDeleteSet ?? '').'"> '. chr(10);
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