<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<!-- examples
https://ordinarycoders.com/blog/article/11-chart-js-examples
https://www.w3schools.com/js/js_graphics_google_chart.asp
-->
<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
    include_once('scripts/rkrk_felddaten.php');
    include_once('scripts/rkrk_googlechart.php');
  
	headerpage("Flugstatistik", false);

?>
<style>
BODY, PRE { 
  color: black;                                 /* Value: <color> */
/*  background-image: url(index_background.gif);  */                /* Initial: none (e.g. BODY { background-image: url(marble.gif) }*/
  font-size: 100%;                              /* relative to 'font-size', i.e. 12pt */
  background-color: #FFFFFF;                      /* Value: <color> | transparent */
  font-weight: normal;                          /* Value: normal | bold | bolder | lighter | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 */
  background-repeat: no-repeat;                 /* Value: repeat | repeat-x | repeat-y | no-repeat */
  background-position: center, top;                  /* Value: [<percentage> | <length>]{1,2} | [top | center | bottom] || [left | center | right], Initial: 0% */
  background-attachment: fixed;
  font-family: tahoma, helvetica, sans-serif;
}
</style>
<?php
	function FormatDataArray($data) {
		global $MaxFlugID;
		$MaxFlugID = 0;
		foreach ($data as $key => $entry) {
			//echo $entry["FlugID"] . "<br>";
			$data[$entry["FlugID"]] = $entry;
			unset($data[$key]);
			if ($MaxFlugID < strval(substr($entry["FlugID"],-4, 4)))
				$MaxFlugID= strval(substr($entry["FlugID"],-4, 4));
		}
		array_multisort(
				array_column($data, 'Datum'), SORT_ASC,
				array_column($data, 'Pilot'), SORT_ASC,
                array_column($data, 'Zeit'),  SORT_ASC,
                array_column($data, 'Feld'),  SORT_ASC,
                $data);
		return $data;
	}
	// ===========================================
	// Gobal Defintions
	// ===========================================

		global $RegionSettings;
		$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
		$debugmode = false;
		$Today = date("yyyy-mm-dd", strtotime(now)); 
		//$Today = "2025-06-01";



	// ===========================================
	// Function
	// ===========================================
 


	// ===========================================
	// Paramters
	// ===========================================



if (isset($_GET["Reg"])) { 
	$SelRegType = $_GET["Reg"];
} else {
	$SelRegType="JG";
}



if (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

if (isset($_POST["PilotenWhal"])) {
	$PilotenWahl = $_POST["PilotenWhal"];
} else	{
	$PilotenWahl = '';
}

if (isset($_GET["Jahr"])) {
	$Jahr = $_GET["Jahr"];
} else	{
	$Jahr = '2023';
}

if (isset($_GET["SelFrom"])) { 
	$FromDate = $_GET["SelFrom"];
} 
elseif (isset($_POST["SelFrom"])) { 
	$FromDate = $_POST["SelFrom"];
} else {
	$FromDate =$Today;
	//For Debugging
	$FromDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-05-01"));
}

if (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
} 
elseif (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} else {
	$ToDate =date('Y-m-d', strtotime('+1 week'));
	//For Debugging
	$ToDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-06-15"));
}	


	//Select Options
	$Pilots[]= "";
	foreach ($OptionPilot as $Pilot) {
		$Pilots[]=$Pilot[Opt];
	}
	$OptControlFlight = Array("", "Ja", "Nein");



///////////////////////////////////////
// Load file
///////////////////////////////////////

	$Files = glob($csvMissionLocation."*_RKRKFluege.csv");
	foreach ($Files as $File) {
		$Pathinfo = pathinfo($File);
		$Year = substr($Pathinfo['basename'],0,4);
		$StatFiles[$Year]['Year'] = $Jahr;
		$StatFiles[$Year]['dirname'] = $Pathinfo['dirname'];
		$StatFiles[$Year]['basename'] = $Pathinfo['basename'];
	}
	//pre_r($StatFiles);
	
	$csv_file= $Jahr ."_RKRKFluege.csv";
	$csv_deliminator = ";";

	//Read CSV file
	transferGoogleSheetToCSV();
	$data = csv_to_array($csvMissionLocation.$csv_file, $csv_deliminator );
	$MaxFlugID = 0;
	$data = FormatDataArray($data);
	// change key to FlugID
//	echo "MaxID: " . $MaxFlugID . "<br>"; 
	//pre_r($data);


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
	
	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$colorTableEven = "#eeeeee ";

	AlternateLineTable($colorTableEven, "120%", 12, "#fbfbfb");
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
$EnabledPilot = Array();
//Select all

/*
//form to change setting
echo "<h1>Flugbuchungen</h1>";
//echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoRegSelection = true;
//echo $i . " " . $KMLFileSettings->name . chr(10)
echo "<table width=100%>";
	echo "<tr><td>";
		echo "Regionen: ";
		echo "</td><td colspan=2>";
		foreach ($RegionSettings as $Reg ) {
			if (("NO" != $Reg["Reg"]) && ("ALL" !=$Reg["Reg"])) {
				if ((($_POST[$Reg["Reg"] . $i])=="on") or isset($_GET["ALL"])) {
					$checked = "checked";
					$EnabledRegion[$Reg["Reg"]] = 1;
					$NoRegSelection = false;
				} else {
					$checked = "";
					$EnabledRegion[$Reg["Reg"]] = 0;
				}	
				echo '<input type="checkbox" name="'. $Reg["Reg"] . $i . '" ' . $checked . '>'. $Reg["Reg"]. chr(10);
			}	
		}
		echo '<br>'. chr(10);
		$i++;
	echo "</td><i>unsere Flugbuchungen</i><td>";
	echo "</td><td>";
	echo "</td><td>";
	//Edit Feld
	echo " <a href='rkrk_edit_felddaten.php' target='_editfield'><!--Feld editieren--><img src='" . $IconLoc . "/edit_feld.png' height='" . $ButtonHeight ."' title='Feld editieren'></a>    ".chr(10);
	if (($WebFreigabe == "test")) {
		//Edit Files
		echo " <a href='rkrk_edit_feldfileliste.php' target='_editfieldfile'><!--File umbenennen--><img src='" . $IconLoc . "/filerename.png' height='" . $ButtonHeight ."' title='Feld editieren'></a>    ".chr(10);
	}	
	//Interne GEOmap
	echo " <a href='rkrk_map.php?Reg=ALL" . $StandardSettingMap . "' target='_geomap'><!--Karte auf MapGeoAdmin.ch--><img src='" . $IconLoc . "/geomap.png' height='" . $ButtonHeight ."' title='Suchfelder auf GeoMap'></a>    ".chr(10);	
	//interne Feldliste
	echo "<a href='./rkrk_felder.php?Reg=ALL&Typ=intern' height='50px' target='_field'><img src='" . $IconLoc . "\intern.png' height='" . $ButtonHeight . "px'></a> " . chr(10);
	// Home
	echo '<a href="rkrk.php"><img src="' . $IconLoc . '/home.png" height="' . $ButtonHeight .'" title="Home"></a>' .chr(10);
	echo "</td></tr>";

	
	echo "<tr><td>";
	echo "  Datum: " .chr(10);
	echo "</td><td>";
	echo DateJSFunctionField("SelFrom", $FromDate, $MinDate , $MaxDate ,"UpdSelDatum", "this.value", $i, 4 );
	echo " - " ;
	echo DateField("SelTo", $ToDate, $MinDate , $MaxDate);
	echo "</td>";
	echo "<td>";
	// akutelles Jahr
	$FromSelDate = date("Y-m-d", strtotime("first Monday " . date('Y') . "-05-01"));
	$ToSelDate = date("Y-m-d", strtotime("first Monday " . date('Y') . "-06-15"));
	//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</a>  ";
	echo "<button  type='Aktuelle Saison' value='Aktuelle Saison' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</button>".chr(10);
	// Saison wo es Statistik files hat
	foreach ($StatFiles as $File) {
		$FromSelDate = date("Y-m-d", strtotime("first Monday " . $File["Year"] . "-05-01"));
		$ToSelDate = date("Y-m-d", strtotime("first Monday " . $File["Year"]  . "-06-15"));
		//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison" . $File["Year"] . "</a>  ";
		echo "<button  type='Saison " . $File["Year"] ."' value='Saison " . $File["Year"] . "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison " . $File["Year"] ."</button>".chr(10);

	}
	echo "</td>";
	echo "<td rowspan=3>";
	//DEBUGSWITCH
	if (GetAccess("DebugDataBase")) {
		if ($DebugWork) {
			//echo "DB - ON";
			echo "<input type='image' value='DBW' src='" . $IconLoc . "/debug_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?DBW=off&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "' title='Debug Moduls ausschalten'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
		} else {
			//echo "DB - OFF";
			echo "<input type='image' value='DBW' src='" . $IconLoc . "/debug_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?DBW=on&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "' height='" . $ButtonHeight . "'  title='Debug Moduls einschalten'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
		}
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
	//echo SelectField("PilotenWhal", $PilotenWahl ,$Pilots);
		
	$NoPilSelection = true;
	foreach ($OptionPilot as $Pilot ) {
		if ((($_POST["Pilot" . $i])=="on") or isset($_GET["ALL"])) {
			$checked = "checked";
			$EnabledPilot[$Pilot["Opt"]] = 1;
			$NoPilSelection = false;
		} else {
			$checked = "";
			$EnabledPilot[$Pilot["Opt"]] = 0;
		}	
		echo '<input type="checkbox" name="Pilot' . $i . '" ' . $checked . '>'. $Pilot["Opt"]. chr(10);
		$i++;
	}	
	echo '<br>'. chr(10);
	echo "</td><td>";
	echo "<button  type='Region' value='RegionGde' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&Reg=Gde' height='50px' target='_field'>Gemeinde</button>".chr(10);
	echo "<button  type='Region' value='RegionJg' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&Reg=JG' height='50px' target='_field'>Jagdgebiet</button>".chr(10);
	echo "<button  type='Region' value='RegionReg' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&Reg=Reg' height='50px' target='_field'>Region</button>".chr(10);
	echo "</td></tr>";
	echo "</table  width=100%>";
	if ($DebugWork) {
		//echo "DB - ON";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=on&" . $EnableDeleteSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "> '. chr(10);
	} else {
		//echo "DB - OFF";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=off&" . $EnableDeleteSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "> '. chr(10);
	}
	echo "<br>";
*/

	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Edit Fluege ",$FeldID,$DebugWorkSet,"");
		foreach ($data as $Key => $Flug) {
			// selected to show
//			foreach ($Feldkatalog as $Key => $Region) {
//				if ($EnabledRegion[$Key]==1) {
			if (	
//					(
//						$PilotenWahl == ""
//						or ($Flug["Pilot"] == $PilotenWahl)
//					)
					   
//					and
					(  
						$FeldNamenWahl == "" 
						or like($Flug["FeldID"], strval($FeldNamenWahl))
						or (strpos($FeldNamenWahl, strval($Flug["FeldID"]))!== false)
						or like($Flug["Felder"], $FeldNamenWahl)
						or like($Flug["Landwirt"], $FeldNamenWahl)
					) 
					and
					(
						($Flug["Datum"] >= $FromDate) and
						($Flug["Datum"] <= $ToDate)
					)
//					and 
//					(
//					  ($EnabledRegion[$Flug["JG"]]==1)
//					)
//					and
//					(
//					  ($EnabledPilot[$Flug["Pilot"]]==1)
//					)
					
				) 
			{
				$data[$Flug["FlugID"]]["ListNr"]= $i;
				$i++;
			} else {
				$data[$Flug["FlugID"]]["ListNr"]= "";
			}
			//pre_r($Flug);
		}
		$MaxSelectedEntries = $i;
	}
	//pre_r($data);
	//PRE_R($EnabledPilot);

    include_once('scripts/rkrk_statdata.php');

// ========================================
// Process Statistics Data
// ========================================
// Felddaten 
if (!$NoRegSelection and !$NoPilSelection) {
	foreach ($Feldkatalog as $JGFields) {
		foreach ($JGFields as $key => $Field)  {
			$FieldDataArray[$key]["FeldID"] = $Field["FeldID"];
			$FieldDataArray[$key]["Text"] = $Field["Feldname"];
			if ($Field["Flurname"]!="") {
				$FieldDataArray[$key]["Text"] .= " " . $Field["Flurname"];
			}
			$FieldDataArray[$key]["Text"] .= " " . $Field["GrundstueckID"];
			$FieldDataArray[$key]["Flugzeit"] = $Field["Flugzeit"];
			$FieldDataArray[$key]["Flaeche"] = $Field["Flaeche"];
			$FieldDataArray[$key]["GrundstueckID"] = $Field["GrundstueckID"];
			$FieldDataArray[$key]["JG"] = $Field["JG"];
			$FieldDataArray[$key]["Gemeinde"] = $Field["Gemeinde"];
			$FieldDataArray[$key]["Jaeger"] = $Field["Jaeger"];
		} // JG Fields
	} // $Feldkatalog


	//pre_R($FieldDataArray);
	//Adressdaten
	$csv_file= $RKRKContactFile;

	//Read CSV file
	transferGoogleSheetToCSV();
	$Adresskatalog = csv_to_array($csvMissionLocation.$csv_file,',');

	foreach ($Adresskatalog as $Person) {
		$AdressDataArray[$Person['Initialen']]['Initialen']= $Person['Initialen'];
		$AdressDataArray[$Person['Initialen']]['Text']= $Person['JG'] . " " . $Person['Name'] . " " . $Person['Kontaktnummer'];
	}
	//pre_R($AdressDataArray);

	foreach ($RegionSettings as $Reg) {
		$RegDataArray[$Reg['Reg']]['Reg']= $Reg['Reg'];
		$RegDataArray[$Reg['Reg']]['RegUp']= $Reg['RegUp'];
		//echo "JG: " .$Reg['Reg']. " - Reg: " . $Reg['RegUp'] . "<br>";
	}
	//pre_R($RegDataArray);

	// Prep Statistics

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);	
	//pre_r($Feldkatalog);
	$FeldStatistics = Array();
	foreach ($Feldkatalog as $JGkatalog) {
		foreach ($JGkatalog as $dataline) {
//			if ($EnabledRegion[$dataline['JG']]==1) {
				// Gemeinde
				$FeldStatistics['Gde'][$dataline['Gemeinde']]['Felder'] += 1;
				$FeldStatistics['Gde'][$dataline['Gemeinde']]['Fläche'] += $dataline['Flaeche'];
				$FeldStatistics['Gde'][$dataline['Gemeinde']]['Flugzeit'] += $dataline['Flugzeit'];
				// JG
				$FeldStatistics['JG'][$dataline['JG']]['Felder'] += 1;
				$FeldStatistics['JG'][$dataline['JG']]['Fläche'] += $dataline['Flaeche'];
				$FeldStatistics['JG'][$dataline['JG']]['Flugzeit'] += $dataline['Flugzeit'];
				// Reg
				$FeldStatistics['Reg'][$RegDataArray[$dataline['JG']]['RegUp']]['Felder'] += 1;
				$FeldStatistics['Reg'][$RegDataArray[$dataline['JG']]['RegUp']]['Fläche'] += $dataline['Flaeche'];
				$FeldStatistics['Reg'][$RegDataArray[$dataline['JG']]['RegUp']]['Flugzeit'] += $dataline['Flugzeit'];
				//echo "JG: " .$dataline['JG']. " - Reg: " . $RegDataArray[$dataline['JG']]['RegUp']. "<br>";

//			}
		}
	}


//	pre_r($FeldStatistics);
	$FlightStatistics = Array();
	foreach ($data as $dataline) {
		if (
			($dataline["ListNr"]!="") 
			and ($dataline['Datum'] <= $Today)
			){
			// Pilot
			$FlightStatistics['Pilot'][$dataline['Pilot']]['Felder'] += 1;
			$FlightStatistics['Pilot'][$dataline['Pilot']]['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['Pilot'][$dataline['Pilot']]['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Pilot'][$dataline['Pilot']]['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['Pilot'][$dataline['Pilot']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
			$FlightStatistics['Pilot'][$dataline['Pilot']]['AnzEinsätze'] = count($FlightStatistics['Pilot'][$dataline['Pilot']]['Einsatztage']);
			// Gemeinde
			$FlightStatistics['Gde'][$dataline['Gde']]['Felder'] += 1;
			$FlightStatistics['Gde'][$dataline['Gde']]['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['Gde'][$dataline['Gde']]['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Gde'][$dataline['Gde']]['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['Gde'][$dataline['Gde']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
			$FlightStatistics['Gde'][$dataline['Gde']]['AnzEinsätze'] = count($FlightStatistics['Gde'][$dataline['Gde']]['Einsatztage']);
			// JG
			$FlightStatistics['JG'][$dataline['JG']]['Felder'] += 1;
			$FlightStatistics['JG'][$dataline['JG']]['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['JG'][$dataline['JG']]['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['JG'][$dataline['JG']]['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['JG'][$dataline['JG']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
			$FlightStatistics['JG'][$dataline['JG']]['AnzEinsätze'] = count($FlightStatistics['JG'][$dataline['JG']]['Einsatztage']);
			// Reg
			$FlightStatistics['Reg'][$dataline['Reg']]['Felder'] += 1;
			$FlightStatistics['Reg'][$dataline['Reg']]['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['Reg'][$dataline['Reg']]['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Reg'][$dataline['Reg']]['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['Reg'][$dataline['Reg']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
			$FlightStatistics['Reg'][$dataline['Reg']]['AnzEinsätze'] = count($FlightStatistics['Reg'][$dataline['Reg']]['Einsatztage']);
			// Datum
			$FlightStatistics['Datum'][$dataline['Datum']]['Felder'] += 1;
			$FlightStatistics['Datum'][$dataline['Datum']]['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['Datum'][$dataline['Datum']]['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Datum'][$dataline['Datum']]['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['Datum'][$dataline['Datum']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
			$FlightStatistics['Datum'][$dataline['Datum']]['AnzEinsätze'] = count($FlightStatistics['Datum'][$dataline['Datum']]['Einsatztage']);
			// Sum
			$FlightStatistics['Sum']['Felder'] += 1;
			$FlightStatistics['Sum']['Fläche'] += $dataline['Fläche'];
			$FlightStatistics['Sum']['Flugzeit'] += $dataline['Flugzeit'];
			if ($dataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Sum']['Fund'] += $dataline['Fund'];
			}
			$FlightStatistics['Sum']['Einsatztage'][$dataline['Datum']."-".$dataline['Pilot']] = $dataline['Datum'];
			$FlightStatistics['Sum']['AnzEinsätze'] = count($FlightStatistics['Sum']['Einsatztage']);
			//Day
			//$FlightStatistics['Day']['Felder'][$dataline['Datum']] += 1;
			//$FlightStatistics['Day']['Fläche'][$dataline['Datum']] += $dataline['Fläche'];
			//$FlightStatistics['Day']['Flugzeit'][$dataline['Datum']] += $dataline['Flugzeit'];
			//if ($dataline['Kontrollflug']!= "Ja") {
			//	$FlightStatistics['Day']['Fund'][$dataline['Datum']] += $dataline['Fund'];
			//}
			//$FlightStatistics['Day']['Einsatztage'[$dataline['Datum']]][$dataline['Datum']] = $dataline['Datum'];
			//$FlightStatistics['Day']['AnzEinsätze'][$dataline['Datum']] = count($FlightStatistics['Datum'][$dataline['Datum']]['Einsatztage']);
		}
	}


	//pre_r($FlightStatistics);
	//pre_r($FlightStatistics['Sum']);

	// Chart Arrays
	$c_green=array("#48673A", "#527642","#6A9955","#96B588","#A0C291","#C5E0B4");
	//$c_green=array("rgba(72, 103, 58, 1)", "rgba(82, 118, 66, 1)","rgba(106, 153, 85, 1)","rgba(150, 181, 136, 1)","rgba(160, 194, 145, 1)","rgba(197, 224, 180, 1)");
	//$c_bggreen=array("rgba(72, 103, 58, 0.6)", "rgba(82, 118, 66, 0.6)","rgba(106, 153, 85, 0.6)","rgba(150, 181, 136, 0.6)","rgba(160, 194, 145, 0.6)","rgba(197, 224, 180, 0.6)");
	$c_red=array("#7A201C", "#922722","#B3322C","C23C35","#F84941");
	//$c_red=array("rgba(112, 32, 28, 1)", "rgba(146, 39, 34, 1)","rgba(179, 50, 44, 1)","rgba(210, 60, 53, 1)","rgba(248, 73, 65, 1)");
	//$c_bgred=array("rgba(112, 32, 28, 0.6)", "rgba(146, 39, 34, 0.6)","rgba(179, 50, 44, 0.6)","rgba(210, 60, 53, 0.6)","rgba(248, 73, 65, 0.6)");
	$c1 = array($c_green[0],$c_red[0],$c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5], $c_green[0],$c_red[0],$c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5]);
	$c2 = array($c_red[0],$c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0], $c_red[0],$c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0]);
	$c3 = array($c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0], $c_green[1],$c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0]);
	$c4 = array($c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0],$c_green[1], $c_red[1],$c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0],$c_green[1]);
	$c5 = array($c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0],$c_green[1],$c_red[1], $c_green[2],$c_red[2],$c_green[3],$c_red[3],$c_green[4],$c_red[4],$c_green[5],$c_green[0],$c_red[0],$c_green[1],$c_red[1]);
	$cbg1 = array($c_bggreen[0],$c_bgred[0],$c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5], $c_bggreen[0],$c_bgred[0],$c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5]);
	$cbg2 = array($c_bgred[0],$c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0], $c_bgred[0],$c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0]);
	$cbg3 = array($c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0], $c_bggreen[1],$c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0]);
	$cbg4 = array($c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0],$c_bggreen[1], $c_bgred[1],$c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0],$c_bggreen[1]);
	$cbg5 = array($c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0],$c_bggreen[1],$c_bgred[1], $c_bggreen[2],$c_bgred[2],$c_bggreen[3],$c_bgred[3],$c_bggreen[4],$c_bgred[4],$c_bggreen[5],$c_bggreen[0],$c_bgred[0],$c_bggreen[1],$c_bgred[1]);


	$BarColors = "[ '". implode("', '", $c1) . "']";
	$linecolor = array("#48673A", "#527642","#6A9955","#96B588","#A0C291","#C5E0B4"); 
	$fillcolor = array("#48673A", "#527642","#6A9955","#96B588","#A0C291","#C5E0B4");

	if ($SelRegType=="Gde") {
		$SelRegTypeTitle = "Gemeinden";
	} elseif  ($SelRegType=="JG") {
		$SelRegTypeTitle = "Jagdgebiete";
	} else {
		$SelRegTypeTitle = "Regionen";
	}


	$FeldGde_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['Gde'], 'Felder Gemeinden'),
							GetDataFromStrArrayField($FeldStatistics['Gde'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['Gde'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FeldStatistics['Gde'], 'Flugzeit', 'Flugzeit [min]' ));

	$FeldJG_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['JG'], 'Felder Jagdtgebiete'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Flugzeit', 'Flugzeit [min]' ));

	$FeldReg_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['Reg'], 'Felder Regionen'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Flugzeit', 'Flugzeit [min]' ));
	//pre_r($FeldStatistics);
	//pre_r($FeldStatistics['Reg']);
	//pre_r(GetDataFromStrArrayField($FeldStatistics['Reg'], 'Felder'));
	//pre_r(GetDataFromStrArrayField($FeldStatistics['Reg'], 'Fläche'));
	//pre_r(GetDataFromStrArrayField($FeldStatistics['Reg'], 'Flugzeit'));

	$Pilot_Data = array( 	GetDataFromStrArrayKey($FlightStatistics['Pilot'], 'Einsätze Piloten'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Fund', 'Rehkitzfunde'));

	$Gde_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['Gde'], 'Einsätze in Gemeinden'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Fund', 'Rehkitzfunde'));


	$Reg_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['Reg'], 'Einsätze pro Region'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Fund', 'Rehkitzfunde'));

	$JG_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['JG'], 'Einsätze pro Jagdgebiet'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Fläche', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Fund', 'Rehkitzfunde'));

	$Datum_Data = array( 	GetDataFromStrArrayKey($FlightStatistics['Datum'], 'Einsätze pro Tag'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Fund', 'Rehkitzfunde'));
	
	$i=1;
	$Day_Data[0][0]="'" . "Tagesaktiviäten" . "'";
	$Day_Data[0][1]="'" . "Felder" . "'";
	$Day_Data[0][2]="'" . "Fläche [ha]" . "'";
	$Day_Data[0][3]="'" . "Flugzeit [min]" . "'";
	$Day_Data[0][4]="'" . "Funde" . "'" ;
	foreach ($FlightStatistics['Datum'] as $key => $day) {
		$Day_Data[$i][0]="'" . date("d.m", strtotime($key)) . "'";
		$Day_Data[$i][1]=$day['Felder'];
		$Day_Data[$i][2]=$day['Fläche'];
		$Day_Data[$i][3]=$day['Flugzeit'];
		$Day_Data[$i][4]=$day['Fund'];
		$i++;
	}
	//pre_r($Day_Data);
	//pre_r($Datum_Data);
	
	$Gde_DataFlaeche =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'Fläche', 'Fläche  [ha]' ));

	$Gde_DataFelder =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'Felder', 'Felder'));

	$Gde_DataZeit =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'Flugzeit', 'Flugzeit [min]'));

	$Gde_DataEinsatz =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'AnzEinsätze', 'Einsatztage'));

	$Gde_DataFund =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'Fund', 'Rehkitzfunde'));

	$Pilot_DataFlaeche =  Array( 	GetDataFromStrArrayKey($FlightStatistics[$SelRegType], $SelRegTypeTitle),
									GetDataFromStrArrayField($FlightStatistics[$SelRegType], 'Fläche', 'Fläche  [ha]'));


	$Reg_TitelFlaeche =  Array( 	GetDataFromStrArrayKey($FlightStatistics['Reg'], 'Region'),
									GetDataFromStrArrayField($FlightStatistics['Reg'], 'Fläche', 'Fläche  [ha]'));

	$JG_DataFlaeche =  Array( 	GetDataFromStrArrayKey($FlightStatistics['JG'], 'Gemeinden'),
									GetDataFromStrArrayField($FlightStatistics['JG'], 'Fläche', 'Fläche  [ha]'));


	//pre_R($FeldStatistics);
	
	////////////////////////////////
	// Charts
	////////////////////////////////

	SetTableWidth("600");
	$DivWitdh = 800;

	echo '<table>';
	echo '<tr>';
	echo '<th>Einsatztage</th>';
	echo '<th>Felder</th>';
	echo '<th>Funde</th>';
	echo '<th>Fläche [ha]</th>';
	echo '</tr>';
	echo '<tr height="80px">';
	echo '<td style="text-align:center; color:orange; font-size:60px">'. $FlightStatistics['Sum']['AnzEinsätze'].'</td>';
	echo '<td style="text-align:center; color:red; font-size:60px">'. $FlightStatistics['Sum']['Felder'].'</td>';
	echo '<td style="text-align:center; color:green; font-size:60px">'. $FlightStatistics['Sum']['Fund'].'</td>';
	echo '<td style="text-align:center; color:blue; font-size:60px">'. intval($FlightStatistics['Sum']['Fläche']) .'</td>';
	echo '<tr>';
	echo '</table>';


	echo '<table>';
	echo '<tr><td>';
	if (count($Pilot_Data[1])>1) {
		echo '<div id="Chart1" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Pilot_Data, $linecolor, $fillcolor, true));
		$ChartDate = Collapse2dArray(GetChartOptions($Pilot_Data, true));
		echo createColumnChart('Chart1', 'Einsätze Piloten' , $ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Pilot_Data);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	if (count($Pilot_Data[1])>1) {
		echo '<div id="Chart2" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Gde_Data, $linecolor, $fillcolor, true));
		$ChartDate = Collapse2dArray(GetChartOptions($Pilot_Data, true));
		echo createColumnChart('Chart2', 'Einsätze in Gemeinden' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_Data);
		echo "<br>";
		//echo Collapse2dArray($Gde_Data);
		echo '<br>';
	}
	echo '</td></tr>';
/*	echo '<tr><td>';
	if (count($Reg_Data[1])>1) {
		echo '<div id="Chart3" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Reg_Data, $linecolor, $fillcolor, true));
		$ChartDate = Collapse2dArray(GetChartOptions($Reg_Data, true));
		echo createBarChart('Chart3', 'Einsätze in Regionen' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Reg_Data);
		echo "<br>";
		echo '<br>';
	}echo '</td></tr>';
*/	echo '<tr><td>';
	echo '</tr><tr>';
	if (count($JG_Data[1])>1) {
		echo '<div id="Chart4" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($JG_Data, $linecolor, $fillcolor, true));
		$ChartDate = Collapse2dArray(GetChartOptions($JG_Data, true));
		echo createColumnChart('Chart4', 'Einsätze in Jagdgebieten' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($JG_Data);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
/*	echo '<tr><td>';
	if (count($Gde_DataFlaeche[1])>1) {
		echo '<div id="Chart5" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFlaeche)));
		echo createPieChart('Chart5', 'Fläche [ha]' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_DataFlaeche);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	if (count($Gde_DataFelder[1])>1) {
		echo '<div id="Chart6" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFelder)));
		echo createPieChart('Chart6', 'Einsatztage' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_DataFelder);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	if (count($Gde_DataZeit[1])>1) {
		echo '<div id="Chart7" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataZeit)));
		echo createPieChart('Chart7', 'Flugzeit [min]' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_DataZeit);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
*/	echo '<tr><td>';
	if (count($Gde_DataEinsatz[1])>1) {
		echo '<div id="Chart8" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataEinsatz)));
		echo createPieChart('Chart8', 'Einsatztage' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_DataEinsatz);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	if (count($Gde_DataFund[1])>1) {
		echo '<div id="Chart9" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFund)));
		echo createPieChart('Chart9', 'Rehkitzfunde' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Gde_DataFund);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	if (count($Day_Data[1])>1) {
		echo '<div id="Chart10" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(GetChartOptions(FillEmptyEntries($Day_Data), true));
		echo createBarChart('Chart10', 'Einsätze pro Tag' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($Day_Data);
		echo '<br>';
		echo '<br>';
	}
	echo '</td></tr>';
/*	echo '<tr><td>';
	if (count($FeldGde_Data[1])>1) {
		echo '<div id="Chart11" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($FeldGde_Data)), true));
		echo createColumnChart('Chart11', 'Felder pro Gemeinde' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($FeldGde_Data);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
	echo '<tr><td>';
	//pre_r($FeldJG_Data);
	if (count($FeldJG_Data[1])>1) {
		echo '<div id="Chart12" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(GetChartOptions(FillEmptyEntries($FeldJG_Data), true));
		echo createColumnChart('Chart12', 'Felder pro Jagdgebiet' ,$ChartDate, $BarColors, "", "");	
		//echo GetTableFromArray($FeldJG_Data);
		echo '<br>';
		echo '<br>';
	}
	echo '</td></tr>';
*/	echo '<tr><td>';
	if (count($FeldReg_Data[1])>1) {
		echo '<div id="Chart13" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
		$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($FeldReg_Data)), true));
		echo createColumnChart('Chart13', 'Felder pro Region' ,$ChartDate, $BarColors, "", "");	
		echo GetTableFromArray($FeldReg_Data);
		echo "<br>";
		echo '<br>';
	}
	echo '</td></tr>';
/*	echo '<tr><td>';
	echo '<div id="Chart14" style="width:100%; max-width:' . $DivWitdh . '; height:400px;"></div>';
	echo '<br>';
	echo '</td></tr>';
*/	echo '</table>';

/*
	////////////////////////////////
	// Table
	////////////////////////////////
	if ((strpos(strval($ThisPage),"v2") != false) or (strpos(strval($_SERVER['PHP_SELF']),"v5") != false) or (strpos(strval($ThisPage),"test") != false)){
		$i=1;

		$OptionJaeger = Array();
		$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
		
		echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
		//echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
		//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&" . $EnableDeleteSet . "'>" .chr(10);
		echo "<br>" .chr(10);
		echo "<br>" .chr(10);
		
		//form to change setting
		echo "<table>" .chr(10);
		echo "<tr >" .chr(10);
		if ($EnableDelete) {
			echo "<th></th>" .chr(10);
		}
		$FeldListe = GetFieldList($Feldkatalog);
				
	//Datum,Zeit,Pilot,Feld,Kontrollflug,Fund,UAV#,Felder,Jäger,Kontaktnummer,Landwirt,Bemerkung,Flugzeit,AnzFelder,#Funde,Einsatztag,GrundstückID,Gde,JG,Reg,Fläche
		if ($debugmode) {
			echo "<th>#</th>" .chr(10);
		}
		echo "<th>Datum*</th>" .chr(10);
		echo "<th>Zeit</th>" .chr(10);
		echo "<th>Pilot</th>" .chr(10);
		echo "<th>Feld*</th>" .chr(10);
		echo "<th>Kontrollflug</th>" .chr(10);
		echo "<th>Fund</th>" .chr(10);
		echo "<th>UAV#</th>" .chr(10);
		echo "<th>Felder</th>" .chr(10);
		echo "<th>Jäger</th>" .chr(10);
		echo "<th>GrundstückID</th>" .chr(10);
		echo "<th>Gde</th>" .chr(10);
		echo "<th>JG</th>" .chr(10);
		echo "<th>Reg</th>" .chr(10);
		echo "<th>Fläche</th>" .chr(10);
		echo "<th>Flugzeit</th>" .chr(10);
		echo "</tr >" .chr(10);
		//new data
		echo "<tr><td colspan='20'><i>Buchungen:</i><td></tr>". chr(10);
		// Extisting Entries
		$PrevPilot ="";
		$ActDate =$FromDate;
		$PrevDate =$Tomorrow;
		$PrevPilot = "";

		foreach ($data as $dataline) {
			//pre_R($dataline);
			if ($dataline["ListNr"]!=""){
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
					echo "<tr><td colspan='20'><b><i>" . $dataline['Datum'] . "/ " . $dataline['Pilot'] . "</i></b><td></tr>". chr(10);
				}
				// Existing Data
				echo '<tr>' .chr(10);
				if ($debugmode) {
					echo '<td>' .chr(10);
					echo $i;
					echo '</td>' .chr(10);
				}	
				echo '<td>' .chr(10);
				echo $dataline['Datum'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Zeit'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Pilot'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Feld'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontrollflug'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Fund'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['UAV#'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Felder'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Jäger'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['GrundstückID'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Gde'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['JG'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Reg'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Fläche'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Flugzeit'];
				echo "</td>" .chr(10);
				echo "</tr>" .chr(10);
				$PrevPilot =$dataline['Pilot'];
				$PrevDate =$dataline['Datum'];
				$ActDate =$PrevDate;
			}
			$i++;
		}
		echo "</table>" .chr(10);
		echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

	//	echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
		//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&" . $EnableDeleteSet . "'>" .chr(10);
		echo '</form>'. chr(10);
		echo '<br>'. chr(10);
		//pre_r($data);
	} 
*/
} else {
	//NoSelection
	echo "<i>Bitte Jagdgebiet und Pilot selektieren und <Einlesen> drücken.</i>";
}


?>

</script>
</body>
</HTML>
