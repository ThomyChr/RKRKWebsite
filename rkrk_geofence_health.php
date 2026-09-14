<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  //include_once('scripts/rkrk_xmlfiles.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php'); 
  

	$pagetitle = "Vergleich GeoFence Daten  mit Felddaten";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

if (isset($_GET["Reg"])) {
	$RegSelected = $_GET["Reg"];
} else {
	$RegSelected = "";
}

	// ===========================================
	// Vergleiche Feldkatalog mit FileListe
	// ===========================================

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

function DoGeofenceDataList ($FilterJG, $Stop=0) {
global $xmlMissionLocation, $RKRKFelderfileXML, $colorOKtext, $colorNOKtext, $colorNOK, $colorTableEven;
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 

	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=0;
		if (in_array($JKey,$FilterJG)) {
			foreach ($JRegion as $Field) {
				$GeoData[$Field["FeldID"]]["FeldID"] = $Field["FeldID"];
				//$GeoData[$Field["FeldID"]]["RefPoint"] = $Field["Grundstueckdaten"]["Zentrum"];
				$GeoData[$Field["FeldID"]]["JG"] = $Field["JG"];
				//echo "Zentrum: " . $Field["Grundstueckdaten"]["Zentrum"];
				$GeoData[$Field["FeldID"]]["GeoJG"] = GetJgdOfPOint(trim($Field["Grundstueckdaten"]["Zentrum"]));
				$GeoData[$Field["FeldID"]]["Gemeinde"] = $Field["Gemeinde"];
				$GeoData[$Field["FeldID"]]["GeoGemeinde"] = GetGdeOfPOint(trim($Field["Grundstueckdaten"]["Zentrum"]));
				$GeoData[$Field["FeldID"]]["Warnung"] = $Field["Warnung"];
				$GeoData[$Field["FeldID"]]["GeoFlgZ"] = GetFlgZOfPOint(trim($Field["Grundstueckdaten"]["Zentrum"]));
				$GeoData[$Field["FeldID"]]["GeoWarnung"] = GetWarnungFlgZOfPOint(trim($Field["Grundstueckdaten"]["Zentrum"]));
				$GeoData[$Field["FeldID"]]["Sun"] = GetSunOfPOint(trim($Field["Grundstueckdaten"]["Zentrum"]));
				$GeoData[$Field["FeldID"]]["Sora"] = GetSoraOfPOint($Field);
				//$GeoData[$Field["FeldID"]]["Durchmesser"] = DiameterOfField($Field["FeldID"]);
				//$GeoData[$Field["FeldID"]]["Durchmesser"] = DiameterOfField($Field["FeldID"]) ."/ " .DiameterOfArea(trim($Field["Grundstueckdaten"]["Zentrum"]), trim($Field["Flaechendaten"]));
				//echo $Field["FeldID"] . "FD" . $Field["Flaechendaten"] ."<br>";
				$i++;
				if (($i >= $Stop) && ($Stop>0)) break;	
			}	
		}
	}

	//pre_r($GeoData);
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

	//pre_R($GeoData);
	echo "<table>";
	echo "<tr>";
	echo "<th>";
	echo "FeldID";
	echo "</th>";
	//echo "<th>";
	//echo "RefPoint";
	//echo "</th>";
	echo "<th>";
	echo "JG";
	echo "</th>";
	echo "<th>";
	echo "GeoJG";
	echo "</th>";
	echo "<th>";
	echo "Gemeinde";
	echo "</th>";
	echo "<th>";
	echo "GeoGemeinde";
	echo "</th>";
	echo "<th>";
	echo "Warnung";
	echo "</th>";
	echo "<th>";
	echo "GeoFlgZ";
	echo "</th>";
	echo "<th>";
	echo "GeoWarnung";
	echo "</th>";
	echo "<th>";
	echo "Morgensonne";
	echo "</th>";
	echo "<th>";
	echo "SORA Zone";
	echo "</th>";
	echo "<th>";
	echo "Durchmesser";
	echo "</th>";
	echo "<th>";
	echo "FeldID";
	echo "</th>";
	echo "</tr>";

	foreach ($GeoData as $Data) {
		echo "<tr>";
		echo "<td>";
		echo $Data["FeldID"];
		echo "</td>";
		//echo "<td>";
		//echo $Data["RefPoint"];
		//echo "</td>";
		echo "<td>";
		echo $Data["JG"];
		//echo $Data["JG"] . " " .substr($Data["FeldID"],0,strpos($Data["FeldID"],"-"));
		echo "</td>";
		if ($Data["GeoJG"] != "") {
			  if (substr($Data["FeldID"],0,strpos($Data["FeldID"],"-"))==strval($Data["GeoJG"])) {
			  //if (substr($Data["FeldID"],0,2)==strval($Data["GeoJG"])) {
				  echo "<td>";
				  $textcolor = $colorOKtext;
			  } else {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
			  }  
			echo "<span style='color:" . $textcolor . "'>" . $Data["GeoJG"]. "</span>";
		} else {
			echo "<td>";
		}	
		echo "</td>";
		echo "<td>";
		echo $Data["Gemeinde"];
		echo "</td>";
		if ($Data["GeoGemeinde"] != "") {
			if ($Data["Gemeinde"]==$Data["GeoGemeinde"]) {
				  echo "<td>";
				  $textcolor = $colorOKtext;
			} else {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
			}	  
			echo "<span style='color:" . $textcolor . "'>" . $Data["GeoGemeinde"]. "</span>";
		} else {
			echo "<td>";
		}	
		echo "</td>";
		echo "<td>";
		echo $Data["Warnung"];
		echo "</td>";
		if ($Data["GeoWarnung"] != "") {
			if (strpos($Data["Warnung"], $Data["GeoWarnung"])!== false) {
				  echo "<td>";
				  $textcolor = $colorOKtext;
			} else {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
			}	  
			echo "<span style='color:" . $textcolor . "'>" . $Data["GeoWarnung"]. "</span>";
		} else {
			if (strpos($Data["Warnung"], "CTR")!== false) {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
				  echo "<s><span style='color:" . $textcolor . "'>" . "CTR". "</span></s>";
			} else {
				  echo "<td>";
			}	  
		}	
		echo "</td>";
		echo "<td>";
		echo $Data["GeoFlgZ"];
		echo "</td>";
		if ($Data["Sun"] != "") {
			if (strpos($Data["Warnung"], $Data["Sun"])!== false) {
				  echo "<td>";
				  $textcolor = $colorOKtext;
			} else {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
			}	  
		} else {
			if (strpos($Data["Sun"], "SUN")!== false) {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
				  echo "<s><span style='color:" . $textcolor . "'>" . "SUN". "</span></s>";
			} else {
				  echo "<td>";
			}	  
		}	
		echo "<span style='color:" . $textcolor . "'>" . $Data["Sun"]. "</span>";
		echo "</td>";
		if ($Data["Sora"] != "") {
			if (strpos($Data["Warnung"], $Data["Sora"])!== false) {
				  echo "<td>";
				  $textcolor = $colorOKtext;
			} else {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
			}	  
			echo "<span style='color:" . $textcolor . "'>" . $Data["Sora"]. "</span>";
		} else {
			if (strpos($Data["Sora"], "Sora")!== false) {
				  echo "<td bgcolor='" . $colorNOK . "'>";
				  $textcolor = $colorNOKtext;
				  echo "<s><span style='color:" . $textcolor . "'>" . "Sora". "</span></s>";
			} else {
				  echo "<td>";
			}	  
		}	
		echo "</td>";
		echo "<td>";
		//echo $Data["Durchmesser"];
		echo DiameterOfField($Data["FeldID"]);
		echo "</td>";
		echo "<td>";
		echo $Data["FeldID"];
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	unset($_SESSION[$PoiArraySession]);

}


$RegList = array_diff($RegionSettings, array('NO','ALL'));
$EnabledRegion = Array();
$RegionList = Array();

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
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'"> '. chr(10);
	echo '<br>';
	echo '</form>';
	echo '<br>';
	echo '<br>';


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

// Fast die oberen Funtionen zusammen
//GetPoiOfPointCharacteristics($MyHome, Array("Gde", "Jgd", "FlgZ"));

//21-018
$RefPoint = "8.510696346909569, 47.27203274458011";
//25-045
$RefPoint = "8.498555147802964, 47.23507217090989";
//21-017
$RefPoint = "8.508411140556301, 47.273293329831375";

//GetGeoData($RefPoint); 


if (count($RegionsSelected)>0) {
	//$RegionList = array("21","25"); 
	DoGeofenceDataList($RegionsSelected, 0);
//	DoGeofenceDataList($RegionList, 0);
}
?>
</body>
</html>