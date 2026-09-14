<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<head>
<body>
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
   include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('rkrk_access_control.php'); 
	
	$pagetitle = "Überprüfen der Dateiamenstrukturen";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

  
	// ===========================================
	// Vergleiche Feldkatalog mit FileListe
	// ===========================================


SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

// ==================
// Funktionen für die Rehktitzrettung
// ==================

	//echo $xmlMissionLocation.$RKRKFelderfileXML; 
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 
	//pre_R($Feldkatalog);

	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	

$RegList = array_diff($RegionSettings, array('NO','ALL'));
$EnabledRegion = Array();
$RegionList = Array();
//form to change setting
echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;

$TableColor =  GetTableColor ("");
AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

echo "<table class='rkrk-filter-form' width=100%>";
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Regionen: ";
		echo "</td><td>";
		$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, '', false);
		foreach ($EnabledRegion as $regKey => $regOn) {
			if (!empty($regOn)) {
				$RegionList[] = $regKey;
			}
		}
		$i++;
	echo "</td><td>";
	echo "<td rowspan=2>";
	//back
	//echo '<button type="button" value="Go back!" style="background-color:transparent; border-color:transparent;vertical-align: middle;" onclick="history.back()"><img src="' . $IconLoc . '/return.png" height="' . $ButtonHeight .'" title="Home"></button>';
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

	//pre_r($RegionList);


	echo "<table>";
				// [0] KatID
				// [1] Feldname
				// [2] Flurname
				// [3] GeoID
				// [4] Heigth
				// [5] Pilot
				// [6] Year
				// [7] Corefilename
	echo "<tr>";
	echo "<th>";
	echo "Basename";
	echo "</th>";
	echo "<th>";
	echo "Corefilename";
	echo "</th>";
	echo "<th>";
	echo "KatID";
	echo "</th>";
	echo "<th>";
	echo "Feldname";
	echo "</th>";
	echo "<th>";
	echo "Flurname";
	echo "</th>";
	echo "<th>";
	echo "GeoID";
	echo "</th>";
	echo "<th>";
	echo "Heigth";
	echo "</th>";
	echo "<th>";
	echo "Pilot";
	echo "</th>";
	echo "<th>";
	echo "Year";
	echo "</th>";
	echo "</tr>";

	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=0;
		if (in_array($JKey,$RegionList)) {
			foreach ($JRegion as $Field) {
				//DO action
				$PathInfo = pathinfo($Field["JasonFile"]);
				$FNMeaning = GetMeaningFilename($PathInfo["basename"]); 
				// [0] KatID
				// [1] Feldname
				// [2] Flurname
				// [3] GeoID
				// [4] Heigth
				// [5] Pilot
				// [6] Year
				// [7] Corefilename
				echo "<tr>";
				echo "<td>";
				echo $PathInfo["basename"];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[7];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[0];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[1];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[2];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[3];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[4];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[5];
				echo "</td>";
				echo "<td>";
				echo $FNMeaning[6];
				echo "</td>";
				echo "</tr>";
			}	
		}
	}
	echo "</table>";
?>
</body>
</html>