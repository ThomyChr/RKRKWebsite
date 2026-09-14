<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>

<!--
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.0.1/Chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
-->

<!-- examples
https://ordinarycoders.com/blog/article/11-chart-js-examples
https://www.chartjs.org/docs/latest/
-->
<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
    include_once('scripts/rkrk_felddaten.php');
  
	headerpage("Flugstatistik", false);

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
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
		$debugmode = false;

	// ===========================================
	// Function
	// ===========================================
 


	// ===========================================
	// Paramters
	// ===========================================



if (isset($_GET["DBW"])) {
   if (strtolower($_GET["DBW"])=="on") {
 		$DebugWork= true;
} else {
		$DebugWork= false;
	}
	SetMissionLocation();
}

IF ($DebugWork) {
	$DebugWorkSet= "DBW=on";
} else {
	$DebugWorkSet= "DBW=off";
}


if (isset($_GET["EnableDelete"])) {
   if (strtolower($_GET["EnableDelete"])=="on") {
		//echo "EnableDelete=on";
 		$EnableDelete= true;
	} else {
		//echo "EnableDelete=on";
		$EnableDelete= false;
	}
}

IF ($EnableDelete) {
	$EnableDeleteSet = "EnableDelete=on";
} else {
	$EnableDeleteSet = "EnableDelete=on";
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
	$FromDate =$Today;
	//For Debugging
	$FromDate ="2023-05-03";
}

if (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
} 
elseif (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} else {
	$ToDate =date('Y-m-d', strtotime('+1 week'));
	//For Debugging
	$ToDate ="2023-05-10";
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
		$StatFiles[$Year]['Year'] = $Year;
		$StatFiles[$Year]['dirname'] = $Pathinfo['dirname'];
		$StatFiles[$Year]['basename'] = $Pathinfo['basename'];
	}
	//pre_r($StatFiles);
	
	$csv_file= date('Y', strtotime($FromDate)) ."_RKRKFluege.csv";
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

	AlternateLineTable($colorTableEven, "120%");
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


//form to change setting
echo "<h1>Flugbuchungen</h1>";
//echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;
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
					$NoSelection = false;
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
/*
	//Neues Feld hinzufügen
	echo " <a href='rkrk_edit_felddaten.php?NeuesFeld=N' target='_editfield'><!--Neues Feld hinzufügen--><img src='" . $IconLoc . "/neuesfeld.png' height='" . $ButtonHeight ."' title='Neues Feld hinzufügen'></a>    ".chr(10);
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
*/
	// Home
	echo '<a href="rkrk.php"><img src="' . $IconLoc . '/home.png" height="' . $ButtonHeight .'" title="Home"></a>' .chr(10);
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
/*
	echo "<input type='image' value='saison' src='" . $IconLoc . "/saison.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  date("Y-m-d", strtotime("first wednesday " . date('Y', strtotime($FromDate)) . "-05")) . "&SelTo=" . date("Y-m-d", strtotime(date("Y-m-d", strtotime("first wednesday " . date('Y', strtotime($FromDate)) . "-05")). "+1 week")) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='minus1woche' src='" . $IconLoc . "/minus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 week')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='minus1tag' src='" . $IconLoc . "/minus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 day')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='heute' src='" . $IconLoc . "/heute.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $Today . "&SelTo=" . $Today . "' height='" . $ButtonHeight . "'  title='Datum Heute auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='morgen' src='" . $IconLoc . "/morgen.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  $Tomorrow  . "&SelTo=" . $Tomorrow . "' height='" . $ButtonHeight . "'  title='Datum Morgen auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='plus1tag' src='" . $IconLoc . "/plus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 day')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
	echo "<input type='image' value='plus1woche' src='" . $IconLoc . "/plus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=on&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 week')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;'>" .chr(10);
*/
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
		
	foreach ($OptionPilot as $Pilot ) {
		if ((($_POST["Pilot" . $i])=="on") or isset($_GET["ALL"])) {
			$checked = "checked";
			$EnabledPilot[$Pilot["Opt"]] = 1;
			$NoSelection = false;
		} else {
			$checked = "";
			$EnabledPilot[$Pilot["Opt"]] = 0;
		}	
		echo '<input type="checkbox" name="Pilot' . $i . '" ' . $checked . '>'. $Pilot["Opt"]. chr(10);
		$i++;
	}	
	echo '<br>'. chr(10);
		
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
			if (	(
						$PilotenWahl == ""
						or ($Flug["Pilot"] == $PilotenWahl)
					)
					   
					and
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
					and 
					(
					  ($EnabledRegion[$Flug["JG"]]==1)
					)
					and
					(
					  ($EnabledPilot[$Flug["Pilot"]]==1)
					)
					
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


// ==========================
// Charts
// ==========================


SetTableWidth("100%");

echo '<table>';
echo '<tr><td>';
echo '<canvas id="Piloteneinsaetze" width="700" height="400"></canvas>';
echo '<div id="Table_Piloteneinsaetze"></div>';
echo '<br>';
echo '</td><td>';
echo '<canvas id="Gemeindeeinsaetze" width="700" height="400"></canvas>';
echo '<div id="Table_Gemeindeeinsaetze"></div>';
echo '<br>';
echo '</td></tr><tr><td>';
echo '<canvas id="JGeinsaetze" width="700" height="400"></canvas>';
echo '<div id="Table_JGeinsaetze"></div>';
echo '<br>';
echo '</td><td>';
echo '<canvas id="Regeinsaetze" width="700" height="400"></canvas>';
echo '<div id="Table_Regeinsaetze"></div>';
echo '<br>';
echo '</td></tr><tr><td>';
echo '<canvas id="FundeProGemeinden" width="700" height="400"></canvas>';
echo '<div id="Table_FundeProGemeinden"></div>';
echo '<br>';
echo '</td><td>';
echo '<canvas id="FlugzeitProGemeinden" width="700" height="400"></canvas>';
echo '<div id="Table_FlugzeitProGemeinden"></div>';
echo '<br>';
echo '</td></tr><tr><td>';
echo '<canvas id="FelderProGemeinden" width="700" height="400"></canvas>';
echo '<div id="Table_FelderProGemeinden"></div>';
echo '<br>';
echo '</td><td>';
echo '<canvas id="EinsatztageProGemeinden" width="700" height="400"></canvas>';
echo '<div id="Table_EinsatztageProGemeinden"></div>';
echo '<br>';
echo '</td></tr><tr><td>';
echo '<canvas id="FlaecheProGemeinden" width="700" height="400"></canvas>';
echo '<div id="Table_FlaecheProGemeinden"></div>';
echo '<br>';
echo '</td><td>';
echo '</tr>';
echo '</table>';


// ==========================
// Table
// ==========================

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


// ========================================
// Date for JS array update
// ========================================

// Felddaten 
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
}
//pre_R($RegDataArray);

// Prep Statistics

$Statistics = Array();
foreach ($data as $dataline) {
	if ($dataline["ListNr"]!=""){
		// Pilot
		$Statistics['Pilot'][$dataline['Pilot']]['Felder'] += 1;
		$Statistics['Pilot'][$dataline['Pilot']]['Fläche'] += $dataline['Fläche'];
		$Statistics['Pilot'][$dataline['Pilot']]['Flugzeit'] += $dataline['Flugzeit'];
		if ($dataline['Kontrollflug']!= "Ja") {
			$Statistics['Pilot'][$dataline['Pilot']]['Fund'] += $dataline['Fund'];
		}
		$Statistics['Pilot'][$dataline['Pilot']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
		$Statistics['Pilot'][$dataline['Pilot']]['AnzEinsätze'] = count($Statistics['Pilot'][$dataline['Pilot']]['Einsatztage']);
		// Gemeinde
		$Statistics['Gde'][$dataline['Gde']]['Felder'] += 1;
		$Statistics['Gde'][$dataline['Gde']]['Fläche'] += $dataline['Fläche'];
		$Statistics['Gde'][$dataline['Gde']]['Flugzeit'] += $dataline['Flugzeit'];
		if ($dataline['Kontrollflug']!= "Ja") {
			$Statistics['Gde'][$dataline['Gde']]['Fund'] += $dataline['Fund'];
		}
		$Statistics['Gde'][$dataline['Gde']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
		$Statistics['Gde'][$dataline['Gde']]['AnzEinsätze'] = count($Statistics['Gde'][$dataline['Gde']]['Einsatztage']);
		// JG
		$Statistics['JG'][$dataline['JG']]['Felder'] += 1;
		$Statistics['JG'][$dataline['JG']]['Fläche'] += $dataline['Fläche'];
		$Statistics['JG'][$dataline['JG']]['Flugzeit'] += $dataline['Flugzeit'];
		if ($dataline['Kontrollflug']!= "Ja") {
			$Statistics['JG'][$dataline['JG']]['Fund'] += $dataline['Fund'];
		}
		$Statistics['JG'][$dataline['JG']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
		$Statistics['JG'][$dataline['JG']]['AnzEinsätze'] = count($Statistics['JG'][$dataline['JG']]['Einsatztage']);
		// Reg
		$Statistics['Reg'][$dataline['Reg']]['Felder'] += 1;
		$Statistics['Reg'][$dataline['Reg']]['Fläche'] += $dataline['Fläche'];
		$Statistics['Reg'][$dataline['Reg']]['Flugzeit'] += $dataline['Flugzeit'];
		if ($dataline['Kontrollflug']!= "Ja") {
			$Statistics['Reg'][$dataline['Reg']]['Fund'] += $dataline['Fund'];
		}
		$Statistics['Reg'][$dataline['Reg']]['Einsatztage'][$dataline['Datum']] = $dataline['Datum'];
		$Statistics['Reg'][$dataline['Reg']]['AnzEinsätze'] = count($Statistics['Reg'][$dataline['Reg']]['Einsatztage']);
	}
}

//pre_r($Statistics);

// Chart Arrays
//$c_green=array("#48673A", "#527642","#6A9955","#96B588","#A0C291","#C5E0B4");
$c_green=array("rgba(72, 103, 58, 1)", "rgba(82, 118, 66, 1)","rgba(106, 153, 85, 1)","rgba(150, 181, 136, 1)","rgba(160, 194, 145, 1)","rgba(197, 224, 180, 1)");
$c_bggreen=array("rgba(72, 103, 58, 0.6)", "rgba(82, 118, 66, 0.6)","rgba(106, 153, 85, 0.6)","rgba(150, 181, 136, 0.6)","rgba(160, 194, 145, 0.6)","rgba(197, 224, 180, 0.6)");
//$c_red=array("#7A201C", "#922722","#B3322C","C23C35","#F84941");
$c_red=array("rgba(112, 32, 28, 1)", "rgba(146, 39, 34, 1)","rgba(179, 50, 44, 1)","rgba(210, 60, 53, 1)","rgba(248, 73, 65, 1)");
$c_bgred=array("rgba(112, 32, 28, 0.6)", "rgba(146, 39, 34, 0.6)","rgba(179, 50, 44, 0.6)","rgba(210, 60, 53, 0.6)","rgba(248, 73, 65, 0.6)");
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


function GetListFromNumberArray($data) {
	$output = array();
    foreach($data as $key => $v){
        $output[] = $v;
    }
    return implode(", ", $output);
}

function GetListFromStrArray($data) {
	$output = array();
    foreach($data as $key => $v){
        $output[] = '"' . $v . '"';
    }
    return implode(", ", $output);
}

function GetListFromNumberArrayField($data, $Type) {
    foreach($data as $key => $v){
        $output[] = $v[$Type];
    }
    return implode(", ", $output);
}


function GetListFromStrArrayField($data, $Type) {
    foreach($data as $key => $v){
        $output[] = '"' . $v[$Type] . '"';
    }
    return implode(", ", $output);
}

function GetListFromStrArrayKey($data) {
	$output = array();
    foreach($data as $key => $v){
        $output[] = '"' . $key . '"';
    }
    return implode(", ", $output);
}
?>

<script>


<?PHP

	echo "var barColors = 	[	[ ". GetListFromStrArray($c1) . "],
								[ ". GetListFromStrArray($c2) . "],
								[ ". GetListFromStrArray($c3) . "],
								[ ". GetListFromStrArray($c4) . "],
								[ ". GetListFromStrArray($c5) . "] ];" .chr(10);
	echo "var barBgColors = [	[ ". GetListFromStrArray($cbg1) . "],
								[ ". GetListFromStrArray($cbg2) . "],
								[ ". GetListFromStrArray($cbg3) . "],
								[ ". GetListFromStrArray($cbg4) . "],
								[ ". GetListFromStrArray($cbg5) . "] ];" .chr(10);
	echo "var PilotTitel = [	[ 'Einsätze Piloten' ], 
								[ 'Felder' ],
								[ 'Fläche  [ha]' ],
								[ 'Flugzeit [min]' ],
								[ 'Einsatztage' ],
								[ 'Rehkitzfunde' ] ];" .chr(10);
	echo "var PilotData	 = [	[". GetListFromStrArrayKey($Statistics['Pilot']) . "], 
								[". GetListFromStrArrayField($Statistics['Pilot'], 'Fläche') . "],
								[". GetListFromStrArrayField($Statistics['Pilot'], 'Felder') . "],
								[". GetListFromStrArrayField($Statistics['Pilot'], 'Flugzeit') . "],
								[". GetListFromStrArrayField($Statistics['Pilot'], 'AnzEinsätze') . "],
								[". GetListFromStrArrayField($Statistics['Pilot'], 'Fund') . "] ];" .chr(10);
	echo "var GdeTitel = [	[ 'Einsätze in Gemeinden' ], 
								[ 'Fläche  [ha]' ],
								[ 'Felder' ],
								[ 'Flugzeit [min]' ],
								[ 'Einsatztage' ],
								[ 'Rehkitzfunde' ] ];" .chr(10);
	echo "var GdeData	 = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'Fläche') . "],
								[". GetListFromStrArrayField($Statistics['Gde'], 'Felder') . "],
								[". GetListFromStrArrayField($Statistics['Gde'], 'Flugzeit') . "],
								[". GetListFromStrArrayField($Statistics['Gde'], 'AnzEinsätze') . "],
								[". GetListFromStrArrayField($Statistics['Gde'], 'Fund') . "] ];" .chr(10);
	echo "var RegTitel = [	[ 'Einsätze pro Region' ], 
								[ 'Felder' ],
								[ 'Fläche  [ha]' ],
								[ 'Flugzeit [min]' ],
								[ 'Einsatztage' ],
								[ 'Rehkitzfunde' ] ];" .chr(10);
	echo "var RegData	 = [	[". GetListFromStrArrayKey($Statistics['Reg']) . "], 
								[". GetListFromStrArrayField($Statistics['Reg'], 'Fläche') . "],
								[". GetListFromStrArrayField($Statistics['Reg'], 'Felder') . "],
								[". GetListFromStrArrayField($Statistics['Reg'], 'Flugzeit') . "],
								[". GetListFromStrArrayField($Statistics['Reg'], 'AnzEinsätze') . "],
								[". GetListFromStrArrayField($Statistics['Reg'], 'Fund') . "] ];" .chr(10);
	echo "var JGTitel = [	[ 'Einsätze pro JG' ], 
								[ 'Felder' ],
								[ 'Fläche  [ha]' ],
								[ 'Flugzeit [min]' ],
								[ 'Einsatztage' ],
								[ 'Rehkitzfunde' ] ];" .chr(10);
	echo "var JGData	 = [	[". GetListFromStrArrayKey($Statistics['JG']) . "], 
								[". GetListFromStrArrayField($Statistics['JG'], 'Fläche') . "],
								[". GetListFromStrArrayField($Statistics['JG'], 'Felder') . "],
								[". GetListFromStrArrayField($Statistics['JG'], 'Flugzeit') . "],
								[". GetListFromStrArrayField($Statistics['JG'], 'AnzEinsätze') . "],
								[". GetListFromStrArrayField($Statistics['JG'], 'Fund') . "] ];" .chr(10);
	echo "var GdeTitelFlaeche = [ 	[ 'Gemeinden' ], 
									['Fläche'] ] ;".chr(10);
	echo "var GdeDataFlaeche = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'Fläche') . "] ];" .chr(10);
	echo "var GdeTitelFelder = [ 	[ 'Gemeinden' ], 
									['Felder'] ] ;".chr(10);
	echo "var GdeDataFelder = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'Felder') . "] ];" .chr(10);
	echo "var GdeTitelZeit = 	[ 	[ 'Gemeinden' ], 
									['Flugzeit [min]'] ] ;".chr(10);
	echo "var GdeDataZeit = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'Flugzeit') . "] ];" .chr(10);
	echo "var GdeTitelEinsatz = 	[ 	[ 'Gemeinden' ], 
									['Einsätze'] ] ;".chr(10);
	echo "var GdeDataEinsatz = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'AnzEinsätze') . "] ];" .chr(10);
	echo "var GdeTitelFund = 	[ 	[ 'Gemeinden' ], 
									['Fund'] ] ;".chr(10);
	echo "var GdeDataFund = [	[". GetListFromStrArrayKey($Statistics['Gde']) . "], 
								[". GetListFromStrArrayField($Statistics['Gde'], 'Fund') . "] ];" .chr(10);
	echo "var Regionen =	[ ". GetListFromStrArrayKey($Statistics['Reg']) . "];" .chr(10);
	echo "var Jagdgebiete =	[ ". GetListFromStrArrayKey($Statistics['JG']) . "];" .chr(10);
?>

function addTable(vBox, vTitel, vData) {
// new myTableDiv(document.getElementById(vBox), {
	var myTableDiv = document.getElementById(vBox);

  var table = document.createElement('TABLE');
  table.width  = 800;

  var tableBody = document.createElement('TBODY');
  table.appendChild(tableBody);

  for(var i=0; i<vData.length; i++) {
    var tr = document.createElement('TR');
    tableBody.appendChild(tr);

      for(var j=0; j<vData[i].length; j++){
	  if (j===0) {
	  var td = document.createElement('TD');
		td.appendChild(document.createTextNode(vTitel[i]));
		td.width = '100';
		tr.appendChild(td);
	  }	  
		var td = document.createElement('TD');
		td.width = '50';
      td.appendChild(document.createTextNode(vData[i][j]));
      tr.appendChild(td);
    }
  }
  myTableDiv.appendChild(table);
}

/*
function makeTableHTML(vData) {
    var result = "<table border=1>";
    for(var i=0; i<vData.length; i++) {
        result += "<tr>";
        for(var j=0; j<vData[i].length; j++){
            result += "<td>"+vData[i][j]+"</td>";
        }
        result += "</tr>";
    }
    result += "</table>";

    return result;
}
*/

function NewChart4(vType, vTitle ,vBox , vLabel, vData ,vColors, vBGColors) {
	// vType: bar, horizontalBar
	new Chart(document.getElementById(vBox), {
		type: vType,
		data: {
		  labels: vData[0],
		  datasets: [
			{
			  label: vLabel[1][0],
			  borderColor: vBGColors[0],
			  backgroundColor: vColors[0],
			  borderWidth: 1,
			  data: vData[1]
			} , {
			  label: vLabel[2][0],
			  borderColor: vBGColors[1],
			  backgroundColor: vColors[1],
			  borderWidth: 1,
			  data: vData[2]
			} , {
			  label: vLabel[3][0],
			  borderColor: vBGColors[2],
			  backgroundColor: vColors[2],
			  borderWidth: 1,
			  data: vData[3]
			} , {
			  label: vLabel[4][0],
			  borderColor: vBGColors[3],
			  backgroundColor: vColors[3],
			  borderWidth: 1,
			  data: vData[4]
			}  
		  ]
		},
		options: {
		  responsive: false,
		  legend: { display: false },
		  title: {
			display: true,
			text: vTitle
		  }
		}
	});
}

function NewChart5(vType, vTitle ,vBox , vLabel, vData ,vColors, vBGColors) {
	// vType: bar, horizontalBar
	new Chart(document.getElementById(vBox), {
		type: vType,
		data: {
		  labels: vData[0],
		  datasets: [
			{
			  label: vLabel[1][0],
			  borderColor: vBGColors[0],
			  backgroundColor: vColors[0],
			  borderWidth: 1,
			  data: vData[1]
			} , {
			  label: vLabel[2][0],
			  borderColor: vBGColors[1],
			  backgroundColor: vColors[1],
			  borderWidth: 1,
			  data: vData[2]
			} , {
			  label: vLabel[3][0],
			  borderColor: vBGColors[2],
			  backgroundColor: vColors[2],
			  borderWidth: 1,
			  data: vData[3]
			} , {
			  label: vLabel[4][0],
			  borderColor: vBGColors[3],
			  backgroundColor: vColors[3],
			  borderWidth: 1,
			  data: vData[4]
			} , {
			  label: vLabel[5][0],
			  borderColor: vBGColors[4],
			  backgroundColor: vColors[4],
			  borderWidth: 1,
			  data: vData[5]
			}  
		  ]
		},
		options: {
		  responsive: false,
		  legend: { display: false },
		  title: {
			display: true,
			text: vTitle
		  }
		}
	});
}

function NewStackedChart5(vType, vTitle ,vBox , vLabel, vData ,vColors, vBGColors) {
	// vType: bar, horizontalBar
	new Chart(document.getElementById(vBox), {
		type: vType,
		data: {
		  labels: vData[0],
		  datasets: [
			{
			  label: vLabel[1][0],
			  borderColor: vBGColors[0],
			  backgroundColor: vColors[0],
			  borderWidth: 1,
			  stack: 'Stack 0',
			  data: vData[1]
			} , {
			  label: vLabel[2][0],
			  borderColor: vBGColors[1],
			  backgroundColor: vColors[1],
			  borderWidth: 1,
			  stack: 'Stack 0',
			  data: vData[2]
			} , {
			  label: vLabel[3][0],
			  borderColor: vBGColors[2],
			  backgroundColor: vColors[2],
			  borderWidth: 1,
			  stack: 'Stack 0',
			  data: vData[3]
			} , {
			  label: vLabel[4][0],
			  borderColor: vBGColors[3],
			  backgroundColor: vColors[3],
			  borderWidth: 1,
			  stack: 'Stack 0',
			  data: vData[4]
			} , {
			  label: vLabel[5][0],
			  borderColor: vBGColors[4],
			  backgroundColor: vColors[4],
			  borderWidth: 1,
			  stack: 'Stack 0',
			  data: vData[5]
			}  
		  ]
		},
		options: {
		  responsive: false,
		  legend: { display: false },
		  title: {
			display: true,
			text: vTitle
		  }
		}
	});
}


function NewPieChart(vType, vTitle ,vBox , vLabels, vData,vColors, vBGColors) {
	new Chart(document.getElementById(vBox), {
	  type: vType,
	  data: {
		labels: vLabels,
		datasets: [{
		  backgroundColor: vColors,
		  backgroundColor: vBGColors,
		  borderWidth: 1,
		  data: vData
		}]
	  },
	  options: {
		  responsive: false,
		  title: {
		  display: true,
		  text: vTitle
		}, 
		plugins: {
		  datalabels: {
			display: true,
			align: 'bottom',
			color: 'white',
			backgroundColor: '#ccc',
			borderRadius: 3,
			font: {
			  size: 18,
			},
		  },
		},
	  }
	});
}

//Produce the charts
NewChart5("bar", "Einsätze Piloten" ,"Piloteneinsaetze", PilotTitel , PilotData, barColors, barBgColors);
addTable("Table_Piloteneinsaetze", PilotTitel, PilotData );
NewChart5("bar", "Einsätze in Gemeinden" ,"Gemeindeeinsaetze", GdeTitel , GdeData, barColors, barBgColors);
addTable("Table_Gemeindeeinsaetze", GdeTitel, GdeData );
NewChart5("bar", "Einsätze Piloten" ,"Regeinsaetze", RegTitel , RegData, barColors, barBgColors);
addTable("Table_Regeinsaetze", RegTitel, RegData );
NewChart5("bar", "Einsätze in Regionen" ,"JGeinsaetze",JGTitel , JGData, barColors, barBgColors);
addTable("Table_JGeinsaetze", JGTitel, JGData );
NewPieChart("pie","#Felder pro Gemeinde", "FelderProGemeinden", GdeData[0], GdeData[1], barColors[0], barBgColors[0]);
addTable("Table_FelderProGemeinden", GdeTitelFelder, GdeDataFelder );
NewPieChart("pie","Fläche [ha] pro Gemeinde", "FlaecheProGemeinden", GdeData[0], GdeData[1], barColors[0], barBgColors[0]);
addTable("Table_FlaecheProGemeinden", GdeTitelFlaeche, GdeDataFlaeche);
NewPieChart("pie","Flugzeit [min] pro Gemeinde", "FlugzeitProGemeinden", GdeData[0], GdeData[3], barColors[0], barBgColors[0]);
addTable("Table_FlugzeitProGemeinden", GdeTitelZeit, GdeDataZeit );
NewPieChart("pie","Einsatztage pro Gemeinde", "EinsatztageProGemeinden", GdeData[0], GdeData[4], barColors[0], barBgColors[0]);
addTable("Table_EinsatztageProGemeinden", GdeTitelEinsatz, GdeDataEinsatz );
NewPieChart("pie","Funde pro Gemeinde", "FundeProGemeinden", GdeData[0], GdeData[5], barColors[0], barBgColors[0]);
addTable("Table_FundeProGemeinden", GdeTitelFund, GdeDataFund );



</script>
</body>
</HTML>
