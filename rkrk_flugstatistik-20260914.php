<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<!-- examples
https://ordinarycoders.com/blog/article/11-chart-js-examples
https://www.w3schools.com/js/js_graphics_google_chart.asp
-->
<?php

/*
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Color=white&Charts=Top&GA=gast;
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Jahr=2023&Color=white&Charts=Top&GA=gast;
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Jahr=2023&Color=white&Charts=Top,Entwicklung1,Entwicklung2&GA=gast;

	MDB : Platform library M2; M3T; ""=Debug
	Reg : selektiere Regtion z.B, 21,15,25, ZG, AG, ALL
	Format: full - Zwei Kollonen
	GA : Gastzugriff (ohne Passowort/ GA=gast)
	Charts: Liste der anzuzeigenden Grafiken (Charts=Top,Entwicklung1,Entwicklung2)
	RegList: Liste der angzeigten Regionen (Reg=21%2024%2025)
	SelFrom : Datum von YYYY-MM-DD
	SelTo : Datum bis YYYY-MM-DD
	Jahr : Jahr der Statistik
	ALL : Alle Grafiken anzeigen
	FeldNamenWahl: Auswahl Feld (Name)
	PilotenWahl: Auswahl Pilot (Kürzel z.B. TC, CH, VP)
	Color: white fro white color
	ChartHeight: Grafikhöhe (px)
	ChartWidth: Grafibreite (px)
*/

	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_googlechart.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_xmlfiles.php');
	include_once('rkrk_access_control.php'); 

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	GrantAccess();
 
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
		$Today = date("Y-m-d"); 
		//$Today = "2025-06-01";
		$Layer=true;
		$EnabledRegion = Array();
		$EnabledPilot = Array();

	// ===========================================
	// Function
	// ===========================================
 
	$JSONSummaryStatisticsFile = (function_exists('rkrk_catalog_json_path')
		? rkrk_catalog_json_path($xmlMissionLocation.'RKRK_SummaryFluege.xml')
		: '');
	$XMLSummaryStatisticsFile = $xmlMissionLocation."RKRK_SummaryFluege.xml";
	$CSVSummaryStatisticsFile = "RKRK_SummaryFluege.csv";
	$excel_csv_file = "RKRK_SummaryFluege (".$_SESSION[$SessNme_RegisteredInitialen].").csv";
	$excel_csv_path = $historyfilelocation . $excel_csv_file;	

	function LoadSummaryStatistics() {
		global $XMLSummaryStatisticsFile;
		if (function_exists('rkrk_summary_flights_load')) {
			$loaded = rkrk_summary_flights_load($XMLSummaryStatisticsFile);
			return is_array($loaded) ? $loaded : array();
		}
		return array();
	}
	function LoadSummaryStatisticsToXML() {
		return LoadSummaryStatistics();
	}

	function SaveSummaryStatistics($SummaryStatistics) {
		global $XMLSummaryStatisticsFile;
		if (function_exists('rkrk_summary_flights_save_json')) {
			return rkrk_summary_flights_save_json($XMLSummaryStatisticsFile, $SummaryStatistics);
		}
		return false;
	}
	function SafeSummaryStatisticsToXML($SummaryStatistics) {
		return SaveSummaryStatistics($SummaryStatistics);
	}

	function SafeSummaryStatisticsToCSV($SummaryStatistics) {
		global	$csvDocLocation, 
				$CSVSummaryStatisticsFile;
		
		ksort($SummaryStatistics);
		//pre_R($SummaryStatistics);

		foreach ($SummaryStatistics as $A_key => $A_Data) {
			foreach ($A_Data as $B_key => $B_Data) {
				//echo "B Key: " . $B_key . "<br>";
				if (in_array($B_key,Array("Gde","JG","Pilot","Reg"))) {
					//echo "B Key: " . $B_key . "<br>";
					foreach ($B_Data as $C_key => $C_Data) {
						//echo "C Key: " . $C_key . "<br>";
						//$data[$A_key."-".$B_key."-".$C_key."-".$D_key] = Array($A_key => $A_key,$B_key => $B_key,$C_key => $C_key);
						foreach ($C_Data as $D_key => $D_Data) {
							if ($D_key!="Einsatztage") {
								if ($D_key=="Felder") {
									$FieldName = "4Felder";
								} elseif ($D_key=="Flaeche") {
									$FieldName = "5Flaeche";
								} elseif ($D_key=="Fund") {
									$FieldName = "6Fund";
								} else {
									//AnzEinsaetze
									$FieldName = "7AnzEinsaetze";
								}	
								
								//echo "data: " . $D_key. "-" .$D_Data . "<br>";
								$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)]["1Jahr"] = substr($A_key,1);
								$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)]["2Kategorie"] = $B_key;
								$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)]["3Name"] = substr($C_key,1);
								$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)][$FieldName] = $D_Data;
								//$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)][$B_key] = $B_key;
								//$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)][substr($A_key,1)] = substr($A_key,1);
								//$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)][substr($C_key,1)] = substr($C_key,1);
								//$data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)][$D_key] = $D_Data;
								ksort($data[substr($A_key,1)."-".$B_key."-".substr($C_key,1)]);
							}	
						}
					}
				}
			}
		}
		ksort($data);
		//pre_R($data["2024-Pilot-CH"]);

		//$data = Array ( 
		//			Array ("col1" => "1", "col2" => "2", "col3" => "3", ),
		//			Array ("col1" => "2", "col2" => "3", "col3" => "4", ),
		//			Array ("col1" => "3", "col2" => "4", "col3" => "5", )
		//		);	
		//SaveArrayToCSV($data, $CSVSummaryStatisticsFile);
		//echo "Parameter " . $csvDocLocation . $CSVSummaryStatisticsFile . "<br>";
		$TableSetup="";
		SaveDataToCSV($data, $TableSetup, $csvDocLocation . $CSVSummaryStatisticsFile, "UTF-8", true, $CSVSummaryStatisticsFile ,";");
	}

	/**
	 * Master-Summary (XML/CSV) nur bei ungefilterter Vollstatistik schreiben –
	 * nicht bei JG-Zusammenfassung, RegList, Charts-Embed usw.
	 */
	function rkrk_flugstatistik_should_persist_summary() {
		global $PageFormat, $PilotenWahl, $FeldNamenWahl;
		if (isset($_GET['RegList']) && trim((string)$_GET['RegList']) !== '') {
			return false;
		}
		if (isset($_GET['Charts']) && trim((string)$_GET['Charts']) !== '') {
			return false;
		}
		if ($PageFormat !== 'full') {
			return false;
		}
		if (trim((string)($FeldNamenWahl ?? '')) !== '') {
			return false;
		}
		if (trim((string)($PilotenWahl ?? '')) !== '') {
			return false;
		}
		return true;
	}

	function rkrk_summary_accumulate_flight(&$yearStats, $statdataline) {
		$yearStats['Pilot']['P'.$statdataline['Pilot']]['Felder'] += 1;
		$yearStats['Pilot']['P'.$statdataline['Pilot']]['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['Pilot']['P'.$statdataline['Pilot']]['Fund'] += $statdataline['Fund'];
		}
		$yearStats['Pilot']['P'.$statdataline['Pilot']]['Einsatztage']['D'.$statdataline['Datum']] = $statdataline['Datum'];
		$yearStats['Pilot']['P'.$statdataline['Pilot']]['AnzEinsaetze'] = count($yearStats['Pilot']['P'.$statdataline['Pilot']]['Einsatztage']);

		$yearStats['Gde']['G'.$statdataline['Gde']]['Felder'] += 1;
		$yearStats['Gde']['G'.$statdataline['Gde']]['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['Gde']['G'.$statdataline['Gde']]['Fund'] += $statdataline['Fund'];
		}
		$yearStats['Gde']['G'.$statdataline['Gde']]['Einsatztage']['D'.$statdataline['Datum']] = $statdataline['Datum'];
		$yearStats['Gde']['G'.$statdataline['Gde']]['AnzEinsaetze'] = count($yearStats['Gde']['G'.$statdataline['Gde']]['Einsatztage']);

		$yearStats['JG']['J'.$statdataline['JG']]['Felder'] += 1;
		$yearStats['JG']['J'.$statdataline['JG']]['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['JG']['J'.$statdataline['JG']]['Fund'] += $statdataline['Fund'];
		}
		$yearStats['JG']['J'.$statdataline['JG']]['Einsatztage']['D'.$statdataline['Datum']] = $statdataline['Datum'];
		$yearStats['JG']['J'.$statdataline['JG']]['AnzEinsaetze'] = count($yearStats['JG']['J'.$statdataline['JG']]['Einsatztage']);

		$yearStats['Reg']['R'.$statdataline['Reg']]['Felder'] += 1;
		$yearStats['Reg']['R'.$statdataline['Reg']]['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['Reg']['R'.$statdataline['Reg']]['Fund'] += $statdataline['Fund'];
		}
		$yearStats['Reg']['R'.$statdataline['Reg']]['Einsatztage']['D'.$statdataline['Datum']] = $statdataline['Datum'];
		$yearStats['Reg']['R'.$statdataline['Reg']]['AnzEinsaetze'] = count($yearStats['Reg']['R'.$statdataline['Reg']]['Einsatztage']);

		$yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['Felder'] += 1;
		$yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['Fund'] += $statdataline['Fund'];
		}
		$yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['Einsatztage']['D'.$statdataline['Datum']] = $statdataline['Datum'];
		$yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['AnzEinsaetze'] = count($yearStats['JGPIL']['R'.$statdataline['JG']]['P'.$statdataline['Pilot']]['Einsatztage']);

		$yearStats['Sum']['Felder'] += 1;
		$yearStats['Sum']['Flaeche'] += round($statdataline['Flaeche']);
		if ($statdataline['Kontrollflug'] != "Ja") {
			$yearStats['Sum']['Fund'] += $statdataline['Fund'];
		}
		$yearStats['Sum']['Einsatztage'][$statdataline['Pilot']."-".$statdataline['Datum']] = $statdataline['Pilot']."-".$statdataline['Datum'];
		$yearStats['Sum']['AnzEinsaetze'] = count($yearStats['Sum']['Einsatztage']);

		$yearStats['Warn']['CTR'] = ($yearStats['Warn']['CTR'] ?? 0) + preg_match("/(CTR)/", $statdataline['Warnung']);
		$yearStats['Warn']['NGT'] = ($yearStats['Warn']['NGT'] ?? 0) + preg_match("/(NGT)/", $statdataline['Warnung']);
		$yearStats['Warn']['STP'] = ($yearStats['Warn']['STP'] ?? 0) + preg_match("/(STP)/", $statdataline['Warnung']);
		$yearStats['Warn']['SORA'] = ($yearStats['Warn']['SORA'] ?? 0) + preg_match("/(SORA)/", $statdataline['Warnung']);
		$yearStats['Warn']['SPEZ'] = ($yearStats['Warn']['SPEZ'] ?? 0) + preg_match("/(NSG|NSF|FVB|USP)/", $statdataline['Warnung']);
	}

	function rkrk_build_year_summary_from_statdata($statdata, $Jahr, $requireListNr = false) {
		$yearStats = array();
		foreach ($statdata as $statdataline) {
			if ($requireListNr && ($statdataline['ListNr'] ?? '') === '') {
				continue;
			}
			if (!(($statdataline['Geflogen'] == "Ja") || ($statdataline['Datum'] <= "2023-01-01"))) {
				continue;
			}
			rkrk_summary_accumulate_flight($yearStats, $statdataline);
		}
		return $yearStats;
	}
	
	// ===========================================
	// Paramters
	// ===========================================

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";


if (isset($_GET["Reg"])) { 
	$SelRegType = $_GET["Reg"];
} else {
	$SelRegType="JG";
}

if (isset($_GET["Format"])) {
	$PageFormat = $_GET["Format"];
} else	{
	$PageFormat = 'full';
}

if (isset($_GET["GA"])) { 
	$Gast = $_GET["GA"];
} else {
	$Gast="";
}

if (isset($_GET["Charts"])) {
	$Chartslist = explode(",",str_replace(" ", ",", $_GET["Charts"]));
	$PageFormat = 'selected';
} else	{
	$Chartslist = 'all';
}

if (isset($_GET["RegList"])) { 
	$SelReg = str_replace(array(";",","),array(" "," "), $_GET["RegList"]);
	foreach ($RegionSettings as $key => $Reg ) {
		if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"]) && ("ALL" !=$Reg["Reg"])) {
			if ((in_array($key, explode(" ", $SelReg))
				or 	(in_array("alle", $EnabledOptions["Regions"]["List"]))
				or 	($SelReg==""))
			) {
				$EnabledRegion[$Reg["Reg"]] = 1;
			} else {
				$EnabledRegion[$Reg["Reg"]] = 0;
			}
		}
	}
	$PageFormat = 'special';
	//pre_r($EnabledRegion);
} elseif (isset($_GET["Reg"])) {
	$SelReg = str_replace(array(";",","),array(" "," "), $_GET["Reg"]);
	foreach ($RegionSettings as $key => $Reg ) {
		if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"]) && ("ALL" !=$Reg["Reg"])) {
			if ((in_array($key, explode(" ", $SelReg))
				or 	(in_array("alle", $EnabledOptions["Regions"]["List"]))
				or 	($SelReg==""))
			) {
				$EnabledRegion[$Reg["Reg"]] = 1;
			} else {
				$EnabledRegion[$Reg["Reg"]] = 0;
			}
		}
	}
	$PageFormat = 'special';
	//pre_r($EnabledRegion);
	
} else {
	$SelReg="";
}

if (isset($_POST["SelFrom"])) { 
	$FromDate = $_POST["SelFrom"];
} elseif (isset($_GET["SelFrom"])) { 
	$FromDate = $_GET["SelFrom"];
	//echo "G: ".  $_GET["SelFrom"] . "<br>";
} 
 else {
	$FromDate =$Today;
	//For Debugging
	$FromDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-04-28"));
}


if (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} elseif (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
}  else {
	$ToDate =date('Y-m-d', strtotime('+1 week'));
	//For Debugging
	//$ToDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-06-15"));
	//$ToDate =date("Y-m-d",strtotime("third sunday " . $Jahr . "-07"));
	$ToDate = GetSasionEndDate($Jahr);

}	

if (isset($_GET["Jahr"])) {
	$Jahr = $_GET["Jahr"];
	if (isset($_GET["SelTo"]))
		$ToDate = $_GET["SelTo"];
	else
		$FromDate = GetSasionStartDate($Jahr);
	if (isset($_GET["SelFrom"])) 
		$FromDate = $_GET["SelFrom"];
	else	
		//$ToDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-06-15"));
		//$ToDate =date("Y-m-d",strtotime("third sunday " . $Jahr . "-07"));
		$ToDate = GetSasionEndDate($Jahr);
} else	{
	if (isset($_POST["SelFrom"])) {
		$Jahr = date('Y', strtotime($_POST["SelFrom"]));;
	} else {
		$Jahr = date('Y');
	}
}

//echo "FromDate: " . $FromDate . "<br>";
//echo "ToDate: " . $ToDate . "<br>";


if (isset($_GET["ALL"])) {
	$all=true;
	if (isset($_GET["Jahr"]))
		$Jahr = $_GET["Jahr"];
	else
		$Jahr = date('Y');
	//$FromDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-04-28"));
	$FromDate =GetSasionStartDate($Jahr);
	//$ToDate =date("Y-m-d", strtotime("first Monday " . $Jahr . "-07-06"));
	//$ToDate =date("Y-m-d",strtotime("third sunday " . $Jahr . "-07"));
	$ToDate = GetSasionEndDate($Jahr);


} else
$all=false;


if (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

if (isset($_GET["PilotenWahl"])) { 
	$PilotenWahl = str_replace(array(",",";"), array(" ", " "), $_GET["PilotenWahl"]);
	//pre_R(explode(" ", $PilotenWahl));
	foreach ($OptionPilot as $Pilot ) {
		if ((($_POST["Pilot" . $i])=="on") 
				or isset($_GET["ALL"])
				or in_array($Pilot["Opt"],explode(" ", $PilotenWahl))) {
			$EnabledPilot[$Pilot["Opt"]] = 1;
			$ListOfPilots .= $Pilot["Opt"] . ",";
		} else {
			$EnabledPilot[$Pilot["Opt"]] = 0;
		}	
	}	
	$PageFormat = 'special';
} elseif (isset($_POST["PilotenWahl"])) {
	$PilotenWahl = str_replace(array(",",";"), array(" ", " "), $_GET["PilotenWahl"]);
	//pre_R(explode(" ", $PilotenWahl));
	foreach ($OptionPilot as $Pilot ) {
		if ((($_POST["Pilot" . $i])=="on") 
				or isset($_GET["ALL"])
				or in_array($Pilot["Opt"],explode(" ", $PilotenWahl))) {
			$EnabledPilot[$Pilot["Opt"]] = 1;
			$ListOfPilots .= $Pilot["Opt"] . ",";
		} else {
			$EnabledPilot[$Pilot["Opt"]] = 0;
		}	
	}	
	$PageFormat = 'special';
} else	{
	foreach ($OptionPilot as $Pilot ) {
		$EnabledPilot[$Pilot["Opt"]] = 1;
	}	
}

//pre_r($EnabledPilot);

if (empty($_GET["MenuOn"])) {
	$MenuOn = true;
} else {
	if (htmlspecialchars($_GET["MenuOn"])=="off") {
		$MenuOn = false;
	} else {
		$MenuOn = true;
	}
}

if (isset($_GET["ChartHeight"])) {
	$ChartHeight=$_GET["ChartHeight"];
} else	{
	$ChartHeight=400;
}

if (isset($_GET["ChartWidth"])) {
	$ChartWidth=$_GET["ChartWidth"];
} else	{
	$ChartWidth=700;
}
//pre_R($Chartslist);

	//Select Options
	$Pilots[]= "";
	foreach ($OptionPilot as $Pilot) {
		$Pilots[]=$Pilot["Opt"];
	}
	$OptControlFlight = Array("", "Ja", "Nein");

	//pre_R($OptionPilot);
	
	$pagetitle = "Flugstatisik ".$Jahr;
	headerpage($pagetitle, false);
if ($MenuOn) {
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
}

//echo "Format: " . $PageFormat . "<br>";

if ($PageFormat == 'reduced') {
	$Chartslist=Array();
	$Chartslist[]="Top";
	$Chartslist[]="EinsatzPiloten";
//	$Chartslist[]="EinsatzGemeinde";
	$Chartslist[]="EinsatzRegionen";
	$Chartslist[]="EinsatzJagdgebiete";
//	$Chartslist[]="Flaeche";
//	$Chartslist[]="Geflogene Felder";
	$Chartslist[]="Einsatztage1";
	$Chartslist[]="Rehkitzfunde";
//	$Chartslist[]="Flugzeit";
//	$Chartslist[]="EinsaetzeProTagBar";
//	$Chartslist[]="Warnzonen";
//	$Chartslist[]="FelderProGemeinde";
	$Chartslist[]="FelderProRegion";
	$Chartslist[]="Entwicklung1";
	$Chartslist[]="Entwicklung2";
	$Chartslist[]="AufbauFelder";
//	$Chartslist[]="EinsaetzeProTagBreit";
	$Chartslist[]="EinsaetzeProTagSchmal";
	$Chartslist[]="Legende";
//	$Chartslist[]="Tabelle";
}
//pre_R($Chartslist);



///////////////////////////////////////
// Header page
///////////////////////////////////////

	function FormatDataArray($statdata) {
		global $MaxFlugID;
		$MaxFlugID = 0;
		foreach ($statdata as $key => $entry) {
			//echo $entry["FlugID"] . "<br>";
			$statdata[$entry["FlugID"]] = $entry;
			unset($statdata[$key]);
			if ($MaxFlugID < strval(substr($entry["FlugID"],-4, 4)))
				$MaxFlugID= strval(substr($entry["FlugID"],-4, 4));
		}
		array_multisort(
				array_column($statdata, 'Datum'), SORT_ASC,
				array_column($statdata, 'Pilot'), SORT_ASC,
				array_column($statdata, 'Zeit'),  SORT_ASC,
				array_column($statdata, 'Feld'),  SORT_ASC,
				$statdata);
		return $statdata;
	}

	function AddTotalColumn ($statdata) {
		foreach ($statdata as $linekey => $line) {
			if ($linekey !=0) {
				foreach ($line as $entrykey => $entry) {
					$statdata[$linekey]["Total"] += strval($entry);
					//echo $entry . " ";
				}
			} else {
				$statdata[$linekey]["Total"] = "Total";
			}
		}
		return $statdata;
	}
	
	
///////////////////////////////////////
// Load yearly statistics file
///////////////////////////////////////

	$csv_file= $Jahr . $RKRKFlightsCSV;
	
	//$csv_file= $Jahr ."_RKRKPlan.csv";

	$Files = glob($csvDocLocation."*". $RKRKFlightsCSV);
	foreach ($Files as $File) {
		$Pathinfo = pathinfo($File);
		$Year = substr($Pathinfo['basename'],0,4);
		$StatFiles[$Year]['Year'] = $Year;
		$StatFiles[$Year]['dirname'] = $Pathinfo['dirname'];
		$StatFiles[$Year]['basename'] = $Pathinfo['basename'];
	}
	//pre_r($StatFiles);
	
	$csv_file= date('Y', strtotime($FromDate)) . $RKRKFlightsCSV;
	$csv_deliminator = ";";

	//Read CSV file
	transferGoogleSheetToCSV();
	$statdata = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	$MaxFlugID = 0;
	$statdata = FormatDataArray($statdata);
	// change key to FlugID
//	echo "MaxID: " . $MaxFlugID . "<br>"; 
	//pre_r($statdata);


///////////////////////////////////////
// Summary statistics file
///////////////////////////////////////
$SummaryStatistics= Array();
$LoadSummaryStatistics= LoadSummaryStatistics();
foreach ($LoadSummaryStatistics as $key => $data) {
	if ($key != "Y".$Jahr) {
		$SummaryStatistics[$key] = $data;
	}
}
//pre_R($SummaryStatistics);

	$fileloaded=true;
	//pre_r($statdata);

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
	$FontSizeInPoints = 12;
	if ($FontSize== "") {
		$FontSize= "120%";
	}
	$TableColor =  GetTableColor ($BaseColor);
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");
	//page

	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$i= 1;
	$statdataSetFound = false;

// ===========================================
// HEADER FORM
// ===========================================
?>
<style>

#qrbloc1, #qrbloc2 
{
    vertical-align : top;
    position : absolute;
    background : transparent; 
    filter: Alpha(Opacity=100);
    overflow : hidden;
    height : 400px;
    width : 400px;
    z-index : 20;
    text-align : center;
	border-radius: 2%;
}

</style>
<?php

//removes what does not belong to tick list
$RegList = array_diff($RegionSettings, array('NO','ALL'));
//Select all

	//form to change setting
	if (($PageFormat == 'full') and
		($Chartslist == 'all'))	{
		//echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
		echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
		$i= 0;
		//$NoRegSelection = true;
		//echo $i . " " . $KMLFileSettings->name . chr(10)
		echo "<table width=100%>";
		echo "<tr><td>";
			echo "Regionen: ";
			echo "</td><td colspan=2>";
			$GrpName = "Opt";
			$Gruppe = 300;
			$i= $Gruppe;
			//process Regions preselections
			if (isset($_GET["Reg"])) {
				$GetRegions = multiexplode(array(" ", ","),$_GET["Reg"]);
				foreach ($GetRegions as $Region) {
					$TmpArr[$Region] = $Region;
				}
				$GetRegions = $TmpArr;
			} else
				$GetRegions= "";
			//process Regions form
			//pre_r($RegionSettings);
			$ListOfRegions = "";
			Foreach ($RegionSettings as $Reg) {
				if ($Reg["Aktiv"]) {
					$RegOptions[$Reg["Reg"]]["Text"]= $Reg["Reg"];
					if (($GetRegions[$Reg["Reg"]])!="") { 
						$RegOptions[$Reg["Reg"]]["Value"]=1;
						$ListOfRegions .= $Reg["Reg"] . ",";
					} elseif (isset($_POST[$GrpName."-".$i])) {
						$RegOptions[$Reg["Reg"]]["Value"]= $_POST[$GrpName."-".$i];
						$ListOfRegions .= $Reg["Reg"] . ",";
					} else
						$RegOptions[$Reg["Reg"]]["Value"]= false;
					$i++;
				}	
			}
			$ListOfRegions = substr($ListOfRegions,0, -1);
			$EnabledOptions["Regions"] = PrintSelection($RegOptions , $GrpName ,$Gruppe, true);
			foreach ($RegionSettings as $key => $Reg ) {
				if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"]) && ("ALL" !=$Reg["Reg"])) {
					if ((in_array($key, $EnabledOptions["Regions"]["List"])
						or 	(in_array("alle", $EnabledOptions["Regions"]["List"])))
					) {
						$EnabledRegion[$Reg["Reg"]] = 1;
					} else {
						$EnabledRegion[$Reg["Reg"]] = 0;
					}
				}
			}
			echo '<br>'. chr(10);
			$i++;
		echo "</td><i>unsere Einsätze</i><td>";
		echo "</td><td>";
		echo "</td><td>";
		
		if ($Layout!=2023) {
			echo CreateSiteLink ("EditFeld", "");
			echo CreateSiteLink ("Fluege", "");
			echo CreateSiteLink ("Flugliste", "");
			echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
			echo CreateSiteLink ("FeldListe", "Reg=ALL&Typ=intern");
			echo CreateSiteLink ("EditFiles", "");
			echo CreateSiteLink ("Home", "");
		}
	/*
		//Neues Feld hinzufügen
		echo " <a href='rkrk_edit_felddaten.php?NeuesFeld=N' target='_editfield'><!--Neues Feld hinzufügen--><img src='" . $IconLoc . "/neuesfeld.png' height='" . $ButtonHeight ."' title='Neues Feld hinzufügen'></a>    ".chr(10);
	*/
	/*
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
	*/
		echo "</td></tr>";

		$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
		$MinDate =date('Y-m-d', strtotime('-1 year'));
		$MinDate ="2021-01-01";
		$MaxDate =date('Y-m-d', strtotime('+2 year'));
		//$Today = date("Y-m-d");
		
		echo "<tr><td>";
		echo "  Datum: " .chr(10);
		echo "</td><td>";
		echo DateJSFunctionField("SelFrom", $FromDate, $MinDate , $MaxDate ,"UpdSelDatum", "this.value", $i, 4 );
		echo " - " ;
		echo DateField("SelTo", $ToDate, $MinDate , $MaxDate);
		echo "</td>";
		echo "<td>";
		// akutelles Jahr
		$FromSelDate = date("Y-m-d", strtotime("first Monday" . date('Y') . "-04-28"));
		$ToSelDate = date("Y-m-d", strtotime("first Monday " . date('Y') . "-07-06"));
		//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</a>  ";
		echo "<button  type='Aktuelle Saison' value='Aktuelle Saison' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&Jahr=".date("Y", strtotime($FromSelDate))."&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</button>".chr(10);
		// Saison wo es Statistik files hat
		foreach ($StatFiles as $File) {
			$FromSelDate = GetSasionStartDate($File["Year"]) ;
			$ToSelDate = GetSasionEndDate($File["Year"]);
			//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&EnableDelete=on&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison" . $File["Year"] . "</a>  ";
			//echo "<button  type='Saison " . $File["Year"] ."' value='Saison " . $File["Year"] . "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&Jahr=". $File["Year"] ."&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison " . $File["Year"] ."</button>".chr(10);
			echo "<button  type='Saison " . $File["Year"] ."' value='Saison " . $File["Year"] . "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&Jahr=". $File["Year"] ."&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>" . $File["Year"] ."</button>".chr(10);

		}
		echo "</td>";
		echo "<td rowspan=3>";
		if (GetAccess("InternPilots"))  {
			echo "<span id='exceldwnld'><a href='".$excel_csv_path ."' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Flugliste herunterladen'></a></span> ";
				//$csvDocLocation.$RKRKContactFile = "Rehkitzrettung Konaueramt - Kontaktnummern.csv";
		}
		//QR code anzeigen
		if (GetAccess ("PilotPartners")) { 
			echo  "<span id='qrswop1'>swop</span>";
		}
		//Not needed on this page
		//echo  MissionLibraryButtons("&Jahr=".date("Y", strtotime($FromDate))."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate,true);

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
		
		if ((GetAccess("PilotSelInStats"))
			or ($PilotenWahl !="")){
			$FieldType = "checkbox";
		} else {
			$FieldType = "hidden";
		}
		$ListOfPilots= "";
		if ($FieldType === "checkbox") {
			rkrk_echo_pills_open();
		}
		foreach ($OptionPilot as $Pilot ) {
			if ((($_POST["Pilot" . $i])=="on") 
					or isset($_GET["ALL"])
					or in_array($Pilot["Opt"],explode(" ", $PilotenWahl))) {
				$checked = true;
				$EnabledPilot[$Pilot["Opt"]] = 1;
				$NoPilSelection = false;
				$ListOfPilots .= $Pilot["Opt"] . ",";
			} else {
				$checked = false;
				$EnabledPilot[$Pilot["Opt"]] = 0;
			}
			$label = function_exists('rkrk_pilot_filter_label') ? rkrk_pilot_filter_label($Pilot) : $Pilot["Opt"];
			if ($FieldType === "checkbox") {
				rkrk_echo_pill_checkbox('Pilot' . $i, $label, $checked);
			} else {
				echo '<input type="'.$FieldType.'" name="Pilot' . $i . '" ' . ($checked ? 'checked' : '') . '>'. htmlspecialchars((string)$Pilot["Opt"], ENT_QUOTES, 'UTF-8'). chr(10);
			}
			$i++;
		}
		if ($FieldType === "checkbox") {
			rkrk_echo_pills_close();
		}
		echo '<br>'. chr(10);

		$ParameterSet = "SelFrom=" . $FromDate . "&" . "SelTo=" . $ToDate . "&" . "RegList=" . str_replace(",", " ", $ListOfRegions) . "&" ."PilotenWahl=". str_replace(",", " ", $ListOfPilots). "&Charts=Top,EinsatzPiloten,EinsatzJagdgebiete,Einsatztage1,Rehkitzfunde,FelderProRegion,Entwicklung1,Entwicklung2,EinsaetzeProTagSchmal,Legende"."&Color=white&GA=gast&" . $MissionSelectionParameter;
		$PageLink =  $WebLink_public . "rkrk_flugstatistik.php" ."?" . $ParameterSet;
		//echo "SeitenLink: " . $PageLink . "<br>";
		//echo "RegList: " . $ListOfRegions . "   PilotList" .$ListOfPilots; 

		$QRCode = $WebLink_public . "rkrk_flugstatistik.php" ."?" . str_replace(array("&", " "),array("%26","%2B"), $ParameterSet); 
		//$QRCode = $WebLink_public . "rkrk_flugstatistik.php" ."?" . $ParameterSet; 
		if ($QRCode!="") {
			//echo $QRCode;
			$qrcode1.= "<br>Link zur Flugstatisikseite:<br>";
			//$qrcode1.= "<a href='".$PageLink."'><img src='https://chart.googleapis.com/chart?chs=350x350&cht=qr&chl=".$QRCode."&choe=UTF-8&chld=L' title='Link auf Fluglisten Ansicht' /></a><br>";
			// new source 21.05.2024 https://goqr.me/
			$qrcode1.= '<img src="https://api.qrserver.com/v1/create-qr-code/?color=000000&amp;bgcolor=FFFFFF&amp;data='.$QRCode.'&amp;qzone=1&amp;margin=0&amp;size=350x350&amp;ecc=M" alt="qr code" / title="Link auf Flugstatisikseite" >' .	
			
			//$qrcode1.= "<p style='font-size:4;width: 150px'>".$WebLink."</p>";
			$qrcode2.= "";
		}


		echo "</td><td>";
		echo "<button  type='Region' value='RegionGde' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&Reg=Gde' height='50px' target='_field'>Gemeinde</button>".chr(10);
		echo "<button  type='Region' value='RegionJg' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "Reg=JG' height='50px' target='_field'>Jagdgebiet</button>".chr(10);
		echo "<button  type='Region' value='RegionReg' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&Reg=Reg' height='50px' target='_field'>Region</button>".chr(10);
		echo "<div style='display:none;top:80px;left:80px;z-index:8;background-color:white' id='qrbloc1'>" .chr(10);
		echo $qrcode1;
		echo "</div>";
		echo "<div style='display' id='qrbloc2'>" .chr(10);
		echo $qrcode2;
		echo "</div>";

		echo "</td></tr>";
		echo "</table  width=100%>";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '"> '. chr(10);
		echo "<br>";
	} elseif ($PageFormat=="special") {
		echo "<table>";
		echo "<tr><td><b><i>Zeitraum:</i></b></td><td><i>" . date("d.M.Y", strtotime($FromDate)) . " bis " . date("d.M.Y", strtotime($ToDate))."</i></td></tr>";
		echo "<tr><td><b><i>Piloten:</i></b></td><td><i><span id='PilotsLegend'></span></i></td></tr>";
		echo "<tr><td><b><i>Jagdgebiete:</i></b></td><td><i><span id='JGLegend'></span></i></td></tr>";
		echo "</table>";
		echo "<br><br>";
	}
	// REDUCED VIEW

//echo "Regionenwahl: ";
//pre_r($EnabledRegion);
//echo "Pilotenwahl: ";
//pre_r($EnabledPilot);

// JG/Region selection
$RegionsSelected = array();
foreach ($EnabledRegion as $key => $Reg) {
	if ($Reg == 1 && isset($RegionSettings[$key]['JGList'])) {
		foreach (explode(',', $RegionSettings[$key]['JGList']) as $JGtoSelect) {
			$JGtoSelect = trim((string)$JGtoSelect);
			if ($JGtoSelect !== '') {
				$RegionsSelected[$JGtoSelect] = $JGtoSelect;
			}
		}
	}
}

//pre_R($RegionsSelected);
//pre_r($EnabledPilot);
//echo "FromDate: " . $FromDate . "<br>";
//echo "ToDate: " . $ToDate . "<br>";
//echo "Pageformat: " .$PageFormat . "<br>";
//pre_R($Chartslist);

	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		//pre_R($statdata);
		//pre_R($RegionsSelected);
		//pre_R($EnabledOptions["Regions"]["List"]);
		//pre_r($EnabledPilot);
		//echo "Pilotenwahl: " .$PilotenWahl;
		
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Flugstatisisik","","","");
		foreach ($statdata as $Key => $Flug) {
			// selected to show
			//echo "Pilot: " . $Flug["Pilot"] .  $EnabledPilot[$Flug["Pilot"]] . "<br>";
//			foreach ($Feldkatalog as $Key => $Region) {
//				if ($EnabledRegion[$Key]==1) {
			if (	
//					(
//						$PilotenWahl == ""
//						or ($Flug["Pilot"] == $PilotenWahl)
//						or ($PageFormat != 'full')
//					)
//					   
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
					  //($EnabledRegion[$Flug["JG"]]==1)
					  (in_array($Flug["JG"], $RegionsSelected))
						or (in_array("alle", $EnabledOptions["Regions"]["List"])
 						or ($PageFormat == 'reduced')
 						or ($PageFormat == 'selected')
						)
					)
					and
					(
					  ($EnabledPilot[$Flug["Pilot"]]==1)
 						or (($PageFormat == 'reduced')
 						or ($PageFormat == 'selected')
						//or $PilotenWahl == ''
						)
					)
					//or (true)
				)
			{
				$statdata[$Flug["FlugID"]]["ListNr"]= $i;
				$i++;
			} else {
				$statdata[$Flug["FlugID"]]["ListNr"]= "";
			}
			
			// collect historic data
			/*
			if ($Flug['Geflogen']='Ja') {
				++$FlightHistory['Y'.date('Y', strtotime($Flug['Datum']))]['JG'][$Flug['JG']];
				++$FlightHistory['Y'.date('Y', strtotime($Flug['Datum']))]['Gde'][$Flug['Gde']];
				++$FlightHistory['Y'.date('Y', strtotime($Flug['Datum']))]['Pilot'][$Flug['Pilot']];
			}
			*/
			//pre_r($Flug);
		}
		$MaxSelectedEntries = $i;
	}
	//pre_r($statdata);
	//pre_r($FlightHistory);
	//PRE_R($EnabledPilot);

    include_once('scripts/rkrk_statdata.php');


// ========================================
// Process Statistics Data
// ========================================
// Felddaten 
//if (!$NoRegSelection and !$NoPilSelection) {
if (!$NoPilSelection) {
	foreach ($Feldkatalog as $JGFields) {
		foreach ($JGFields as $key => $Field)  {
			$FieldDataArray[$key]["FeldID"] = $Field["FeldID"];
			$FieldDataArray[$key]["Text"] = $Field["Feldname"];
			if ($Field["Flurname"]!="") {
				$FieldDataArray[$key]["Text"] .= " " . $Field["Flurname"];
			}
			$FieldDataArray[$key]["Text"] .= " " . $Field["GrundstueckID"];
			//$FieldDataArray[$key]["Flugzeit"] = $Field["Flugzeit"];
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
	$Adresskatalog = csv_to_array($csvDocLocation.$csv_file,',');

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
	/////////////////
	// Feldddaten
	/////////////////
	
	$OptionJaeger = Array();
	$xmlMissionLocation.$RKRKFelderfileXML;
	$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);	
	//pre_r($Feldkatalog);
	//pre_R($EnabledRegion);
	$FeldStatistics = Array();
	foreach ($Feldkatalog as $JGkatalog) {
		foreach ($JGkatalog as $statdataline) {
			if (($EnabledRegion[$statdataline['JG']]==1) or 
				($PageFormat == 'selected') or ($PageFormat == 'reduced'))
				{
				// Gemeinde
				if ($statdataline['Gemeinde']=="") {
					$Gde="andere";
				} else {
					$Gde=$statdataline['Gemeinde'];
				}
				$FeldStatistics['Gde'][$Gde]['Felder'] += 1;
				$FeldStatistics['Gde'][$Gde]['Fläche'] += round($statdataline['Flaeche']);
				$FeldStatistics['Gde'][$Gde]['Flugzeit'] += round($statdataline['Flugzeit']);
				// JG
				$FeldStatistics['JG'][$statdataline['JG']]['Felder'] += 1;
				$FeldStatistics['JG'][$statdataline['JG']]['Fläche'] += round($statdataline['Flaeche']);
				$FeldStatistics['JG'][$statdataline['JG']]['Flugzeit'] += round($statdataline['Flugzeit'],1);
				// Reg
				$FeldStatistics['Reg'][$RegDataArray[$statdataline['JG']]['RegUp']]['Felder'] += 1;
				$FeldStatistics['Reg'][$RegDataArray[$statdataline['JG']]['RegUp']]['Fläche'] += round($statdataline['Flaeche']);
				$FeldStatistics['Reg'][$RegDataArray[$statdataline['JG']]['RegUp']]['Flugzeit'] += round($statdataline['Flugzeit']);
				// Year
				$FeldStatistics['Felder'][$RegDataArray[$statdataline['JG']]['RegUp']][date('Y', strtotime($statdataline['Update']))]['Felder'] += 1;
				$FeldStatistics['Felder'][$RegDataArray[$statdataline['JG']]['RegUp']][date('Y', strtotime($statdataline['Update']))]['Fläche'] += round($statdataline['Flaeche']);
				$FeldStatistics['Felder'][$RegDataArray[$statdataline['JG']]['RegUp']][date('Y', strtotime($statdataline['Update']))]['Flugzeit'] += round($statdataline['Flugzeit']);
			}
		}
	}
	//Sum up Year
	
	for ($x = 2021; $x <= $Jahr; $x++) {
	//for ($x = 2021; $x <= date("Y"); $x++) {
		$YearList[$x]=$x;
	}
	foreach ($FeldStatistics['Felder'] as $Reg => $statdataReg) {
		foreach ($statdataReg as $Year => $statdataline) {
			// sum up per year
			foreach ($YearList as $SumYear => $SumDataYear) {
			//echo $Year . " ". $SumYear . " " . $FeldStatistics['Felder'][$Reg][$Year]['Felder']. "<br>";
				if (($SumYear>=$Year) and ($FeldStatistics['Felder'][$Reg][$Year]['Felder']>0)) {
					$FeldStatistics['FelderRegSum'][$Reg][$SumYear]['Jahr'] = $SumYear;
					$FeldStatistics['FelderRegSum'][$Reg][$SumYear]['Felder'] += round($FeldStatistics['Felder'][$Reg][$Year]['Felder']);
					$FeldStatistics['FelderRegSum'][$Reg][$SumYear]['Fläche'] += round($FeldStatistics['Felder'][$Reg][$Year]['Fläche']);
					$FeldStatistics['FelderRegSum'][$Reg][$SumYear]['Flugzeit'] += round($FeldStatistics['Felder'][$Reg][$Year]['Flugzeit']);
					$FeldStatistics['FelderSum'][$SumYear]['Felder'] += round($FeldStatistics['Felder'][$Reg][$Year]['Felder']);
					$FeldStatistics['FelderSum'][$SumYear]['Fläche'] += round($FeldStatistics['Felder'][$Reg][$Year]['Fläche']);
					$FeldStatistics['FelderSum'][$SumYear]['Flugzeit'] += round($FeldStatistics['Felder'][$Reg][$Year]['Flugzeit']);
				}
			}
		}
		ksort($FeldStatistics['FelderRegSum'][$Reg]);
	}
	ksort($FeldStatistics['FelderSum']);
	//pre_R($FeldStatistics['Felder']);
	//pre_R($FeldStatistics);
	///////////////////////////////////
	// Wetterdaten und Verfügbarkeit
	///////////////////////////////////
	
	$Weather = Array();
	"Weatherfile: " .$csvDocLocation.$Jahr . $RKRKWeatherCSV . "<br>";
	$Weather = csv_to_array($csvDocLocation.$Jahr . $RKRKWeatherCSV, ";");
	$Weather = FormatWeatherDataArray($Weather);
	//pre_r($Weather);
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//pre_r($FeldStatistics);
	$FlightStatistics = Array();
	//pre_r($statdata['FID000009']);
	//pre_r($statdata);
	foreach ($statdata as $statdataline) {
		if (
			(($statdataline["ListNr"]!="") 
			and ($statdataline['Datum'] <= $Today)
			and (($statdataline['Geflogen'] == "Ja")
			or ($statdataline['Datum'] <= "2023-01-01")))
			){
			// Pilot
			$FlightStatistics['Pilot'][$statdataline['Pilot']]['Felder'] += 1;
			$FlightStatistics['Pilot'][$statdataline['Pilot']]['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['Pilot'][$statdataline['Pilot']]['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Pilot'][$statdataline['Pilot']]['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['Pilot'][$statdataline['Pilot']]['Einsatztage'][$statdataline['Datum']] = $statdataline['Datum'];
			$FlightStatistics['Pilot'][$statdataline['Pilot']]['AnzEinsätze'] = count($FlightStatistics['Pilot'][$statdataline['Pilot']]['Einsatztage']);
			// Gemeinde
			if ($statdataline['Gde']=="") {
				$Gde="andere";
			} else {
				$Gde=$statdataline['Gde'];
			}
			$FlightStatistics['Gde'][$Gde]['Felder'] += 1;
			$FlightStatistics['Gde'][$Gde]['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['Gde'][$Gde]['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Gde'][$Gde]['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['Gde'][$Gde]['Einsatztage'][$statdataline['Datum']] = $statdataline['Datum'];
			$FlightStatistics['Gde'][$Gde]['AnzEinsätze'] = count($FlightStatistics['Gde'][$statdataline['Gde']]['Einsatztage']);
			// JG
			$FlightStatistics['JG'][$statdataline['JG']]['Felder'] += 1;
			$FlightStatistics['JG'][$statdataline['JG']]['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['JG'][$statdataline['JG']]['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['JG'][$statdataline['JG']]['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['JG'][$statdataline['JG']]['Einsatztage'][$statdataline['Datum']] = $statdataline['Datum'];
			$FlightStatistics['JG'][$statdataline['JG']]['AnzEinsätze'] = count($FlightStatistics['JG'][$statdataline['JG']]['Einsatztage']);
			// Reg
			$FlightStatistics['Reg'][$statdataline['Reg']]['Felder'] += 1;
			$FlightStatistics['Reg'][$statdataline['Reg']]['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['Reg'][$statdataline['Reg']]['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Reg'][$statdataline['Reg']]['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['Reg'][$statdataline['Reg']]['Einsatztage'][$statdataline['Datum']] = $statdataline['Datum'];
			$FlightStatistics['Reg'][$statdataline['Reg']]['AnzEinsätze'] = count($FlightStatistics['Reg'][$statdataline['Reg']]['Einsatztage']);
			// Datum
			$FlightStatistics['Datum'][$statdataline['Datum']]['Felder'] += 1;
			$FlightStatistics['Datum'][$statdataline['Datum']]['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['Datum'][$statdataline['Datum']]['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Datum'][$statdataline['Datum']]['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['Datum'][$statdataline['Datum']]['Einsatztage'][$statdataline['Datum']] = $statdataline['Datum'];
			$FlightStatistics['Datum'][$statdataline['Datum']]['AnzEinsätze'] = count($FlightStatistics['Datum'][$statdataline['Datum']]['Einsatztage']);
			$FlightStatistics['Datum'][$statdataline['Datum']]['Piloten'][$statdataline['Pilot']] = $statdataline['Pilot'];
			$FlightStatistics['Datum'][$statdataline['Datum']]['AnzPiloten'] = count($FlightStatistics['Datum'][$statdataline['Datum']]['Piloten']);
			// Sum
			$FlightStatistics['Sum']['Felder'] += 1;
			$FlightStatistics['Sum']['Fläche'] += round($statdataline['Flaeche']);
			$FlightStatistics['Sum']['Flugzeit'] += round($statdataline['Flugzeit']);
			if ($statdataline['Kontrollflug']!= "Ja") {
				$FlightStatistics['Sum']['Fund'] += $statdataline['Fund'];
			}
			$FlightStatistics['Sum']['Einsatztage'][$statdataline['Datum']."-".$statdataline['Pilot']] = $statdataline['Datum'];
			$FlightStatistics['Sum']['AnzEinsätze'] = count($FlightStatistics['Sum']['Einsatztage']);
			//Day
			//$FlightStatistics['Day']['Felder'][$statdataline['Datum']] += 1;
			//$FlightStatistics['Day']['Fläche'][$statdataline['Datum']] += $statdataline['Flaeche'];
			//$FlightStatistics['Day']['Flugzeit'][$statdataline['Datum']] += $statdataline['Flugzeit'];
			//if ($statdataline['Kontrollflug']!= "Ja") {
			//	$FlightStatistics['Day']['Fund'][$statdataline['Datum']] += $statdataline['Fund'];
			//}
			//$FlightStatistics['Day']['Einsatztage'[$statdataline['Datum']]][$statdataline['Datum']] = $statdataline['Datum'];
			//$FlightStatistics['Day']['AnzEinsätze'][$statdataline['Datum']] = count($FlightStatistics['Datum'][$statdataline['Datum']]['Einsatztage']);
			$FlightStatistics['Warn']['CTR'] = $FlightStatistics['Warn']['CTR']  + preg_match("/(CTR)/", $statdataline['Warnung']);
			if ($Jahr<2024) {
				$FlightStatistics['Warn']['NGT'] = $FlightStatistics['Warn']['NGT']  + preg_match("/(NGT)/", $statdataline['Warnung']);
			} else {	
				$FlightStatistics['Warn']['NGT1'] = $FlightStatistics['Warn']['NGT1']  + preg_match("/(NGT1)/", $statdataline['Warnung']);
				$FlightStatistics['Warn']['NGT2'] = $FlightStatistics['Warn']['NGT2']  + preg_match("/(NGT2)/", $statdataline['Warnung']);
			}
			$FlightStatistics['Warn']['STP'] = $FlightStatistics['Warn']['STP']  + preg_match("/(STP)/", $statdataline['Warnung']);
			$FlightStatistics['Warn']['SORA'] = $FlightStatistics['Warn']['SORA'] + preg_match("/(SORA)/", $statdataline['Warnung']);
			$FlightStatistics['Warn']['SPEZ'] = $FlightStatistics['Warn']['SPEZ'] + preg_match("/(NSG|NSF|FVB|USP)/", $statdataline['Warnung']);
		}
	}
	//pre_r($FlightStatistics['JG']);

	// Anzeige: gefiltert (ListNr = Region/Zeitraum/Pilot wie auf der Seite)
	$SummaryStatistics['Y'.$Jahr] = rkrk_build_year_summary_from_statdata($statdata, $Jahr, true);

	// Master-Dateien: nur Vollstatistik, aktuelles Jahr immer alle geflogenen Flüge
	if (rkrk_flugstatistik_should_persist_summary()) {
		$summaryToSave = $SummaryStatistics;
		$summaryToSave['Y'.$Jahr] = rkrk_build_year_summary_from_statdata($statdata, $Jahr, false);
		SaveSummaryStatistics($summaryToSave);
		SafeSummaryStatisticsToCSV($summaryToSave);
	}
	ksort($SummaryStatistics);
	
	//pre_r($SummaryStatistics);
	
	//pre_R($FeldStatistics['FelderSum']);
	//pre_r($EnabledRegion);

	ksort($FlightStatistics['JG']);
	ksort($FlightStatistics['Gde']);
	// create summary data
	
	foreach ($SummaryStatistics as $Y_key => $Y_data) {
		ksort($SumStats['JGPIL']);
		//echo $Y_key;
		if (substr($Y_key,-4) <= $Jahr) {
			//echo "a";
			foreach ($Y_data["JGPIL"] as $J_key => $J_data) {
				//echo substr($J_key,1) . "<br>";
				if (($EnabledRegion[substr($J_key,1)]==1)
					or $all){
					
					//echo "b";
					foreach ($J_data as $P_key => $P_data) {
					//echo "b1";
					//echo substr($P_key,1) . "<br>";
						$SumStats[substr($Y_key,1)]['JGPIL']['AnzEinsaetze']=0;
						//pre_R($P_data);
						if (($EnabledPilot[substr($P_key,1)]==1) 
							or $all){
							//echo "c";
					
							foreach ($P_data["Einsatztage"] as $key => $data) {
								$SumStats['JGPIL'][substr($Y_key,1)]['Einsatztage'][$P_key.$key] = $data;
							}
							//echo "d";
							$SumStats['JGPIL'][substr($Y_key,1)]['Felder'] += $P_data['Felder'];
							$SumStats['JGPIL'][substr($Y_key,1)]['Flaeche'] += $P_data['Flaeche'];
							$SumStats['JGPIL'][substr($Y_key,1)]['Fund'] += $P_data['Fund'];
						}
						$SumStats['JGPIL'][substr($Y_key,1)]['AnzEinsaetze'] = count($SumStats['JGPIL'][substr($Y_key,1)]['Einsatztage']);
					}	
				}	
			}

			ksort($Y_data['JG']);
	/*
			foreach ($Y_data["JG"] as $J_key => $J_data) {
				echo substr($J_key,1) . "<br>";
				if ($EnabledRegion[substr($J_key,1)]==1) 
				{
					foreach ($J_data["Einsatztage"] as $key => $data) 
						$SumStats['JG'][$key] = $data;
						$SumStats['JG']['Felder'] += $data['Felder'];
						$SumStats['JG']['Flaeche'] += $data['Flaeche'];
						$SumStats['JG']['Fund'] += $data['Fund'];
				}	
			}
			$SumStats['JG']['AnzEinsaetze'] = $Y_data['Sum']['AnzEinsaetze'];
			$pre_R($SumStats['JG']);
	*/
			//TOTAL SUM
			//echo($Y_key);
			//pre_r($SummaryStatistics[$Y_key]['Sum']);
			$SumStats[substr($Y_key,1)]['Felder'] = $Y_data['Sum']['Felder'];
			$SumStats[substr($Y_key,1)]['Flaeche'] = $Y_data['Sum']['Flaeche'];
			$SumStats[substr($Y_key,1)]['Fund'] = $Y_data['Sum']['Fund'];
			$SumStats[substr($Y_key,1)]['AnzEinsaetze'] = $Y_data['Sum']['AnzEinsaetze'];
		}
	}
	//sort($SumStats);
	//uksort($SumStats);
	//pre_r($SumStats['JGPIL']);

	foreach ($RegionSettings as $Entry) {
		$JGList[] = $Entry["Reg"];
		$JGName[] =  $Entry["Text"];
	}

	foreach ($WeatherOptions as $Entry) {
		$WeatherIcon[] = $Entry["Opt"];
		$WeatherText[] = $Entry["text"];
	}
    //pre_r($WeatherText);
	//pre_r($WeatherIcon);
	//pre_R($FlightStatistics['Datum']);
	
	$MissionsStatistics = Array();
	foreach ($Weather as $statdataline) {
		if (
			($statdataline["Datum"]!="") 
			and ($statdataline['Datum'] <= $Today)
			){
				//echo "Date: " .$statdataline['Datum'] . "<br>";
				//echo "Fund: " . $FlightStatistics['Datum'][$statdataline['Datum']]['Fund'] . "<br>";
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Datum'] = $statdataline['Datum'];
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Wetter'] = $Weather[$statdataline['Datum']]['Wetter'];
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Temperatur'] = $Weather[$statdataline['Datum']]['Temp'];
				$MissionsStatistics['Datum'][$statdataline['Datum']]["Wetter_Icon"] = str_replace($WeatherText, $WeatherIcon, $Weather[$statdataline['Datum']]['Wetter']);
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Fund'] = intval($FlightStatistics['Datum'][$statdataline['Datum']]['Fund']);
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Felder'] = intval($FlightStatistics['Datum'][$statdataline['Datum']]['Felder']);
				$MissionsStatistics['Datum'][$statdataline['Datum']]['Piloten'] = intval($FlightStatistics['Datum'][$statdataline['Datum']]['AnzPiloten']);
				//MissionsStatistics['Datum'][$statdataline['Datum']]['Wetter'] = 
			}
	}	
	//pre_r($MissionsStatistics);

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
							GetDataFromStrArrayField($FeldStatistics['Gde'], 'Flugzeit', 'Flugzeit [min]' ),
							);
	
	$FeldJG_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['JG'], 'Felder Jagdgebiete'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Fläche', 'Fläche  [ha]' ),
							);
	//pre_R($FeldJG_Data);

/*
	$FeldJG_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['JG'], 'Felder Jagdgebiete'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FeldStatistics['JG'], 'Flugzeit', 'Flugzeit [min]' ));
*/
	$FeldReg_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['Reg'], 'Felder Regionen'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Fläche', 'Fläche  [ha]' ),
							);

	$FeldStat_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['FelderSum'], 'Felder Aufbau [Jahr]'),
								GetDataFromStrArrayField($FeldStatistics['FelderSum'], 'Felder', 'Felder'),
								GetDataFromStrArrayField($FeldStatistics['FelderSum'], 'Fläche', 'Fläche  [ha]' ),
							);


/*
	$EntFeFl_Data = array( 	GetDataFromStrArrayKey($SumStats, 'Entwicklung Felder/Fläche [Jahr]'),
								GetDataFromStrArrayField($SumStats, 'Felder', 'Felder'),
								GetDataFromStrArrayField($SumStats, 'Flaeche', 'Fläche  [ha]' ),
							);

	$EntEiFu_Data = array( 	GetDataFromStrArrayKey($SumStats, 'Entwicklung Einsätze/Funde [Jahr]'),
								GetDataFromStrArrayField($SumStats, 'AnzEinsaetze', 'Einsätze'),
								GetDataFromStrArrayField($SumStats, 'Funde', 'Funde' ),
							);
*/
	$EntFeFl_Data = array( 	GetDataFromStrArrayKey($SumStats['JGPIL'], 'Entwicklung Felder/Fläche [Jahr]'),
								GetDataFromStrArrayField($SumStats['JGPIL'], 'Felder', 'Felder'),
								GetDataFromStrArrayField($SumStats['JGPIL'], 'Flaeche', 'Fläche  [ha]' ),
							);

	$EntEiFu_Data = array( 	GetDataFromStrArrayKey($SumStats['JGPIL'], 'Entwicklung Einsätze/Funde [Jahr]'),
								GetDataFromStrArrayField($SumStats['JGPIL'], 'AnzEinsaetze', 'Einsätze'),
								GetDataFromStrArrayField($SumStats['JGPIL'], 'Fund', 'Funde' ),
							);

	//pre_R($FeldStatistics['FelderSum']);
	//pre_R($FeldStat_Data);
	//pre_R($SumStats);
	//pre_R($SumStats['JGPIL']);
	//pre_R($EntFeFl_Data);
	//pre_R($EntEiFu_Data);
/*
	$FeldReg_Data = array( 	GetDataFromStrArrayKey($FeldStatistics['Reg'], 'Felder Regionen'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FeldStatistics['Reg'], 'Flugzeit', 'Flugzeit [min]' ));
*/
	$Pilot_Data = array( 	GetDataFromStrArrayKey($FlightStatistics['Pilot'], 'Einsätze Piloten'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Pilot'], 'Fund', 'Rehkitzfunde'),
							);

	$Gde_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['Gde'], 'Einsätze in Gemeinden'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Gde'], 'Fund', 'Rehkitzfunde'),
							);
	//pre_r($Gde_Data);

	$Reg_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['Reg'], 'Einsätze pro Region'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Reg'], 'Fund', 'Rehkitzfunde'),
							);

	$JG_Data = array( 		GetDataFromStrArrayKey($FlightStatistics['JG'], 'Einsätze pro Jagdgebiet'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['JG'], 'Fund', 'Rehkitzfunde'),
							);

	$Datum_Data = array( 	GetDataFromStrArrayKey($FlightStatistics['Datum'], 'Einsätze pro Tag'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Fläche', 'Fläche  [ha]' ),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Flugzeit', 'Flugzeit [min]' ),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'AnzEinsätze', 'Einsatztage'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Fund', 'Rehkitzfunde'),
							);

	//Warnzonen
	//pre_R($FlightStatistics['Warn']);
	//pre_R($FlightStatistics['JG']);
	//pre_R($FlightStatistics['Gde']);
	$Warn_Data[0][]= "'"."Warnzone"."'";
	$Warn_Data[1][]= "'"."Flüge"."'";
	foreach ($FlightStatistics['Warn'] as $key => $wrndata) {
		$Warn_Data[0][]= "'".$key."'";
		$Warn_Data[1][]= $wrndata;
	}
	//pre_R($Warn_Data);

	
	$Missions_Data = array( 	GetDataFromStrArrayKey($MissionsStatistics['Datum'], 'Einsätze pro Tag'),
							GetDataFromStrArrayField($MissionsStatistics['Datum'], 'Wetter', 'Wetter'),
							GetDataFromStrArrayField($MissionsStatistics['Datum'], 'Fund', 'Rehkitzfunde'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Felder', 'Felder'),
							GetDataFromStrArrayField($FlightStatistics['Datum'], 'Fläche', 'Fläche  [ha]' ),
							);
	
	//pre_R($Missions_Data);
	
	//Create Legends
	//JG
	$JGList = GetDataFromStrArrayKey($FlightStatistics['JG']);
	foreach ($JGList as $entry) {
	  $JG = str_replace("'","", $entry);
	  if ($JG!="") $JGLegend[$JG] = $JG . ": " . $RegionSettings[$JG]['Text'];	
	}
	$JGLegend = implode (', ', $JGLegend);
	//echo $JGLegend;
	//Gde
	$GdeList = GetDataFromStrArrayKey($FlightStatistics['Gde']);
	foreach ($GdeList as $entry) {
	  $Gde = str_replace("'","", $entry);	
	  if ($Gde!="") $GdeLegend[$Gde] = $Gde . ": " . $OptionGemeinde[$Gde]['Text'];	
	}
	$GdeLegend = implode (', ', $GdeLegend);
	//echo $GdeLegend;

	//Pilots
	$PilotList = GetDataFromStrArrayKey($FlightStatistics['Pilot']);
	foreach ($PilotList as $entry) {
	  $Pilot = str_replace("'","", $entry);	
	  if ($Pilot!="") $PilotsLegend[$Pilot] = $Pilot . ": " . $OptionPilot[$Pilot]['Text'];	
	}
	$PilotsLegend = implode (', ', $PilotsLegend);
	//echo $PilotsLegend;
	//Reg
	$RegList = GetDataFromStrArrayKey($FlightStatistics['Reg']);
	foreach ($RegList as $entry) {
	  $Regup = str_replace("'","", $entry);	
	  if ($Regup!="") $RegionsLegend[$Regup] = $Regup . ": " . $OptionRegUp[$Regup]['Text'];	
	}
	$RegionsLegend = implode (', ', $RegionsLegend);
	//echo $RegionsLegend;
	
	$FlightZone['CTR']= $OptionWarning['CTR']["Text"];
	if ($Jahr<2024) {
		$FlightZone['NGT']= $OptionWarning['NGT1']["Text"];
	} else {	
		$FlightZone['NGT1']= $OptionWarning['NGT1']["Text"];
		$FlightZone['NGT2']= $OptionWarning['NGT2']["Text"];
	}
	$FlightZone['STP']= $OptionWarning['STP']["Text"];
	$FlightZone['SORA']= $OptionWarning['SORA']["Text"];
	$FlightZone['SPEZ']= "Spezialzone (NSG, NSF, FVB, USP)";
	foreach ($FlightZone as $key => $entry) {
		$FlightZoneLegend[$key] = $key . ": " . $entry;	
	}
	$FlightZoneLegend = implode (', ', $FlightZoneLegend);
	
	
	$i=1;
	$Day_Data[0][0]="'" . "Tagesaktiviäten" . "'";
	$Day_Data[0][1]="'" . "Felder" . "'";
	$Day_Data[0][2]="'" . "Fläche [ha]" . "'";
	//$Day_Data[0][3]="'" . "Flugzeit [min]" . "'";
	$Day_Data[0][4]="'" . "Funde" . "'" ;
	foreach ($FlightStatistics['Datum'] as $key => $day) {
		$Day_Data[$i][0]="'" . date("d.m", strtotime($key)) . "'";
		$Day_Data[$i][1]=$day['Felder'];
		$Day_Data[$i][2]=$day['Fläche'];
		//$Day_Data[$i][3]=$day['Flugzeit'];
		$Day_Data[$i][4]=$day['Fund'];
		$i++;
	}
	
	//pre_r($Day_Data);
	//pre_r($Datum_Data);
	
	$i=1;
	$Mission_Data=Array();
	$Mission_Data[0][0]="'" . "Saisonübersicht" . "'";
	$Mission_Data[0][1]="'" . "Temperatur" . "'";
	$Mission_Data[0][2]="'" . "Funde" . "'" ;
	$Mission_Data[0][3]="'" . "Felder" . "'" ;
	$Mission_Data[0][4]="'" . "Piloten" . "'" ;
	foreach ($MissionsStatistics['Datum'] as $key => $day) {
		if ($day['Temperatur']!="") {
			$Mission_Data[$i][0]="'" . date("d.m", strtotime($key)) . "'";
			$Mission_Data[$i][1]=$day['Temperatur'];
			$Mission_Data[$i][2]=$day['Fund'];
			$Mission_Data[$i][3]=$day['Felder'];
			$Mission_Data[$i][4]=$day['Piloten'];
			$i++;
		}
	}
	//pre_R($Mission_Data);
	//pre_r($Datum_Data);
	

	//pre_R($FlightStatistics);
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
	
	if (($PageFormat == 'full') 
		or	($Gast== 'gast'))	{
		// two colums
		$tableFieldOpen1 = "<tr  valign= 'top'><td>";
		$tableFieldOpen2 = "<td>";
		$tableFieldClose1 = "</td>";
		$tableFieldClose2 = "</td></tr>";
	} else {
		// one colums
		$tableFieldOpen1 = "<tr valign= 'top'><td>";
		$tableFieldOpen2 = "<tr valign= 'top'><td>";
		$tableFieldClose1 = "</td></tr>";
		$tableFieldClose2 = "</td></tr>";
	}

   //pre_R($FlightStatistics);
	SetTableWidth("100%");
	if ((in_array("Top", $Chartslist)) or 
		($Chartslist == 'all') ) {
		// Bei RegList (JG-Zusammenfassung): gleiche Quelle wie Entwicklung-Charts
		$topEinsaetze = (int)($FlightStatistics['Sum']['AnzEinsätze'] ?? 0);
		$topFelder = (int)($FlightStatistics['Sum']['Felder'] ?? 0);
		$topFund = (int)($FlightStatistics['Sum']['Fund'] ?? 0);
		$topFlaeche = (int)($FlightStatistics['Sum']['Fläche'] ?? 0);
		if (isset($_GET['RegList']) && trim((string)$_GET['RegList']) !== ''
			&& isset($SumStats['JGPIL'][$Jahr])) {
			$topEinsaetze = (int)($SumStats['JGPIL'][$Jahr]['AnzEinsaetze'] ?? $topEinsaetze);
			$topFelder = (int)($SumStats['JGPIL'][$Jahr]['Felder'] ?? $topFelder);
			$topFund = (int)($SumStats['JGPIL'][$Jahr]['Fund'] ?? $topFund);
			$topFlaeche = (int)($SumStats['JGPIL'][$Jahr]['Flaeche'] ?? $topFlaeche);
		}
		echo '<table>';
		echo '<tr>';
		echo '<th>Einsatztage</th>';
		echo '<th>Felder</th>';
		echo '<th>Funde</th>';
		echo '<th>Fläche [ha]</th>';
		echo '</tr>';
		echo '<tr height="80px">';
		echo '<td style="text-align:center; color:orange; font-size:60px">'. $topEinsaetze .'</td>';
		echo '<td style="text-align:center; color:red; font-size:60px">'. $topFelder .'</td>';
		echo '<td style="text-align:center; color:green; font-size:60px">'. $topFund .'</td>';
		echo '<td style="text-align:center; color:blue; font-size:60px">'. $topFlaeche .'</td>';
		echo '<tr>';
		echo '</table>';
	}
	echo '<table>';
	//	$tableFieldOpen1 = "<tr><td>";
	//	$tableFieldOpen2 = "<td>";
	//	$tableFieldClose1 = "</td>";
	//	$tableFieldClose2 = "</td></tr>";
	$NumChart = 1;
	if ((in_array("EinsatzPiloten", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Pilot_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart ;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Pilot_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(GetChartOptions($Pilot_Data, true));
			echo createColumnChart('Chart'.$NumChart .'', 'Einsätze Piloten' , $ChartDate, $BarColors, "", "[#, ha, min, #, #]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Pilot_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
	}
	$NumChart++;
	if ((in_array("EinsatzGemeinde", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Pilot_Data[1])>1) {
			echo $tableFieldOpen2;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Gde_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(GetChartOptions($Gde_Data, true));
			echo createColumnChart('Chart'.$NumChart .'', 'Einsätze in Gemeinden' ,$ChartDate, $BarColors, "", "[#, ha, min, #, #]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Gde_Data);
			echo "<br>";
			//echo Collapse2dArray($Gde_Data);
			echo '<br>';
			echo $tableFieldClose2;
		}
	}
	$NumChart++;
	if ((in_array("EinsatzRegionen", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen1;
		//echo $NumChart;
		if (count($Reg_Data[1])>1) {
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Reg_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(GetChartOptions($Reg_Data, true));
			echo createBarChart('Chart'.$NumChart .'', 'Einsätze in Regionen' ,$ChartDate, $BarColors, "[#, ha, min, #, #]", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Reg_Data);
			echo "<br>";
			echo '<br>';
		}
		echo $tableFieldClose1;
	}
	$NumChart++;
	if ((in_array("EinsatzJagdgebiete", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($JG_Data[1])>1) {
			echo $tableFieldOpen2;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($JG_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(GetChartOptions($JG_Data, true));
			echo createColumnChart('Chart'.$NumChart .'', 'Einsätze in Jagdgebieten' ,$ChartDate, $BarColors, "", "[#, ha, min, #, #]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($JG_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose2;
		}
	}
	$NumChart++;
	if ((in_array("Flaeche", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen1;
		//echo $NumChart;
		if (count($Gde_DataFlaeche[1])>1) {
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFlaeche)));
			echo createPieChart('Chart'.$NumChart .'', 'Fläche [ha]' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Gde_DataFlaeche);
			echo "<br>";
			echo '<br>';
		}
		echo $tableFieldClose1;
	}
	$NumChart++;
	if ((in_array("Geflogene Felder", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen2;
		//echo $NumChart;
		if (count($Gde_DataFelder[1])>1) {
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFelder)));
			echo createPieChart('Chart'.$NumChart .'', 'Geflogene Felder' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Gde_DataFelder);
			echo '<br>';
			echo "<br>";
		}
		echo $tableFieldClose2;
	}
	$NumChart++;
	if ((in_array("Einsatztage1", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Gde_DataEinsatz[1])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataEinsatz)));
			echo createPieChart('Chart'.$NumChart .'', 'Einsatztage' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Gde_DataEinsatz);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
	}
	$NumChart++;
	if ((in_array("Rehkitzfunde", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Gde_DataFund[1])>1) {
			echo $tableFieldOpen2;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataFund)));
			echo createPieChart('Chart'.$NumChart .'', 'Rehkitzfunde' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Gde_DataFund);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose2;
		}
	}
	//$NumChart++;
	//if ((in_array("Flugzeit", $Chartslist)) or 
	//	($Chartslist == 'all') or
	//	($PageFormat == 'full') ) {
	//	echo $tableFieldOpen1;
	//	//echo $NumChart;
	//	if (count($Gde_DataZeit[1])>1) {
	//		echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
	//		$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Gde_DataZeit)));
	//		echo createPieChart('Chart'.$NumChart .'', 'Flugzeit [min]' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
	//		echo GetTableFromArray($Gde_DataZeit);
	//		echo "<br>";
	//		echo '<br>';
	//	}
	//	echo $tableFieldClose1;
	//}
	$NumChart++;
	if ((in_array("EinsaetzeProTagBar", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Day_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(FillEmptyEntries($Day_Data), true));
			//echo createBarChart('Chart'.$NumChart .'', 'Einsätze pro Tag' ,$ChartDate, $BarColors, "[#, ha, min, #]", "",$ChartHeight,$ChartWidth);	
			echo createColumnChart('Chart'.$NumChart .'', 'Einsätze pro Tag' ,$ChartDate, $BarColors, "[#, ha, min, #]", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Day_Data);
			echo '<br>';
			echo '<br>';
			echo $tableFieldClose1;
		}
	}
	$NumChart++;
	if ((in_array("Warnzonen", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen2;
		//echo $NumChart;
		if (count($Warn_Data[1])>1) {
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries($Warn_Data)));
			echo createColumnChart('Chart'.$NumChart .'', 'Flüge in Spezialzonen' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray($Warn_Data);
			echo "<br>";
			echo '<br>';
		}
		echo $tableFieldClose2;
	}
	$NumChart++;
	if ((in_array("FelderProGemeinde", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen1;
		//echo $NumChart;
		if (count($FeldGde_Data[1])>1) {
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($FeldGde_Data)), true));
			echo createColumnChart('Chart'.$NumChart .'', 'Felder pro Gemeinde' ,$ChartDate, $BarColors, "Gemeinde", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray(XTransform2dArray(AddTotalColumn ($FeldGde_Data)));
			echo "<br>";
			echo '<br>';
		}
		echo $tableFieldClose1;
	}
	$NumChart++;
	if ((in_array("FelderProJagdgebiet", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		echo $tableFieldOpen2;
		//echo $NumChart;
		if (count($FeldJG_Data[1])>1) {
		echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(FillEmptyEntries($FeldJG_Data), true));
			echo createColumnChart('Chart'.$NumChart .'', 'Felder pro Jagdgebiet' ,$ChartDate, $BarColors, "", "",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray(XTransform2dArray(AddTotalColumn($FeldJG_Data)));
			echo '<br>';
			echo '<br>';
		}
		echo $tableFieldClose2;
	}
	$NumChart++;
	//echo count($EntEiFu_Data[0]);
	//pre_r($EntEiFu_Data[0]);
	if ((in_array("Entwicklung2", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		if (count($EntEiFu_Data[0])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($EntEiFu_Data)), true));
			echo createLineChart('Chart'.$NumChart .'', 'Entwicklung Einsätze/Funde' ,$ChartDate, $BarColors, "Jahr", "",$ChartHeight,$ChartWidth);	
			//$FeldReg_Data = AddTotalColumn ($EntEiFu_Data);
			//pre_R($EntEiFu_Data);
			echo GetTableFromArray($EntEiFu_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		} else
			echo "Keine Daten über die Einsatz- und Fundentwicklung.<br>";
	}
	$NumChart++;
	if ((in_array("Entwicklung1", $Chartslist)) or 
		($Chartslist == 'all') or
		($PageFormat == 'full') ) {
		if (count($EntFeFl_Data[0])>1) {
			echo $tableFieldOpen2;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($EntFeFl_Data)), true));
			echo createLineChart('Chart'.$NumChart .'', 'Entwicklung Felder/Fläche' ,$ChartDate, $BarColors, "Jahr", "",$ChartHeight,$ChartWidth);	
			//$FeldReg_Data = AddTotalColumn ($EntFeFl_Data);
			//pre_R($EntFeFl_Data);
			echo GetTableFromArray($EntFeFl_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose2;
		} else
			echo "Keine Daten über die Feld- und Flächenentwicklung.<br>";
	}
	$NumChart++;
	if ((in_array("FelderProRegion", $Chartslist)) or 
		($Chartslist == 'all')) {
		//echo $NumChart . "  " . count($FeldReg_Data[1]);
		//pre_R($FeldReg_Data[1]);
		if (count($FeldReg_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($FeldReg_Data)), true));
			echo createColumnChart('Chart'.$NumChart .'', 'Alle Felder pro Region' ,$ChartDate, $BarColors, "Region", "",$ChartHeight,$ChartWidth);	
			$FeldReg_Data = AddTotalColumn ($FeldReg_Data);
			//pre_R($FeldReg_Data);
			echo GetTableFromArray($FeldReg_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
	}
	$NumChart++;
	if ((in_array("AufbauFelder", $Chartslist)) or 
		($Chartslist == 'all'))  {
		//echo $NumChart . "  " . count($FeldReg_Data[1]);
		//pre_R($FeldReg_Data);
		//pre_r($FeldStatistics['Felder']);
		if (count($FeldStat_Data)>1) {
			echo $tableFieldOpen2;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($FeldStat_Data)), true));
			echo createLineChart('Chart'.$NumChart .'', 'Felder Aufbau alle Regionen' ,$ChartDate, $BarColors, "Jahr", "",$ChartHeight,$ChartWidth);	
			//$FeldReg_Data = AddTotalColumn ($FeldStat_Data);
			//pre_R($FeldStat_Data);
			echo GetTableFromArray($FeldStat_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose2;
		}
	}
	$NumChart++;
	if ((in_array("EinsaetzeProTagBreit", $Chartslist)) or 
		($Chartslist == 'all') and
		($PageFormat == 'full')) {
		if (count($Mission_Data[1])>1) {
//			echo $tableFieldOpen1;
			echo "<tr><td colspan=2>";
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate =Collapse2dArray(GetChartOptions(FillEmptyEntries($Mission_Data), false));
			echo createComboChart('Chart'.$NumChart .'', 'Einsätze pro Tag' ,$ChartDate, $BarColors, "Tag", "[C,#]",$ChartHeight,2*$ChartWidth);	
			//echo GetTableFromArray($Mission_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
		//echo $NumChart;
	}
	$NumChart++;
	if (
		(in_array("EinsaetzeProTagSchmal", $Chartslist)) and 
		($Chartslist != 'all') or
		($PageFormat == 'full')) {
		if (count($Mission_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo $NumChart;
			echo '<div id="Chart'.$NumChart .'" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			$ChartDate =Collapse2dArray(GetChartOptions(FillEmptyEntries($Mission_Data), false));
			echo createComboChart('Chart'.$NumChart .'', 'Einsätze pro Tag' ,$ChartDate, $BarColors, "Tag", "[C,#]",$ChartHeight,$ChartWidth);	
			//echo GetTableFromArray($Mission_Data);
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
		//echo $NumChart;
	}
	echo '</tr>';
	echo '</table>';

	if ((in_array("Legende", $Chartslist)) or 
		($Chartslist == 'all') ) {
		//Legende
		echo "<table>";
		echo "<tr><th>Legende:</th></tr>";
		echo "<tr><td><b><i>Piloten:</i></b></td><td><i>" . $PilotsLegend ."</i></td></tr>";
		echo "<tr><td><b><i>Gemeinden:</i></b></td><td><i>" . $GdeLegend ."</i></td></tr>";
		echo "<tr><td><b><i>Jagdgebiete:</i></b></td><td><i>" . $JGLegend ."</i></td></tr>";
		echo "<tr><td><b><i>Regionen</i></b></td><td><i>" . $RegionsLegend ."</i></td></tr>";
		echo "<tr><td><b><i>Flugzonen</i></b></td><td><i>" . $FlightZoneLegend ."</i></td></tr>";
		echo "</table>";
		echo "<br>";
		echo "<br>";
	}
	?>
	<script>
	PilotsLegend.innerHTML ="<?php echo $PilotsLegend; ?>";
	JGLegend.innerHTML ="<?php echo $JGLegend; ?>";
	</script>
	<?php

	////////////////////////////////
	// Table
	////////////////////////////////
	// select exel export data
	
	$excelexport = Array();
	if (
		((in_array("Tabelle", $Chartslist)) or 
		($Chartslist == 'all') ) or
		($PageFormat == 'full')	
		)	{

		if (GetAccess("FlightStatistics")){
			$i=1;
			$OptionJaeger = Array();
			$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
			
			echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
			//echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
			//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
			echo "<br>" .chr(10);
			echo "<br>" .chr(10);
			
			//form to change setting
			echo "<table>" .chr(10);
			echo "<tr >" .chr(10);
			$FeldListe = GetFieldList($Feldkatalog);
					
		//Datum,Zeit,Pilot,Feld,Kontrollflug,Fund,UAV#,Felder,Jäger,Kontaktnummer,Landwirt,Bemerkung,Flugzeit,AnzFelder,#Funde,Einsatztag,GrundstückID,Gde,JG,Reg,Fläche
			if ($debugmode) {
				echo "<th>#</th>" .chr(10);
			}
			echo "<th>Datum*</th>" .chr(10);
			echo "<th>Zeit</th>" .chr(10);
			echo "<th>Pilot</th>" .chr(10);
			echo "<th>Feld*</th>" .chr(10);
			echo "<th>Geflogen</th>" .chr(10);
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
			foreach ($statdata as $statdataline) {
				//pre_R($statdataline);
				if ($statdataline["ListNr"]!=""){
					$excelexport[] = $statdataline;
					// Separator
					if (
						(($PrevPilot !=$statdataline['Pilot']) or
						($PrevDate !=$statdataline['Datum'])) and
						true
						)
					{
						$MaxEntry = $i+2;
						if ($PrevDate=="") {
							$ActDate = $Tomorrow;
						} else {
							$ActDate = $PrevDate;
						}
						echo "<tr><td colspan='20'><b><i>" . $statdataline['Datum'] . "/ " . $statdataline['Pilot'] . "</i></b><td></tr>". chr(10);
					}
					// Existing Data
					echo '<tr>' .chr(10);
					if ($debugmode) {
						echo '<td>' .chr(10);
						echo $i;
						echo '</td>' .chr(10);
					}	
					echo '<td>' .chr(10);
					echo $statdataline['Datum'];
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Zeit'];
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Pilot'];
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Feld'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Geflogen'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Kontrollflug'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Fund'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['UAV#'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Felder'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Jaeger'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['GrundstückID'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Gde'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['JG'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Reg'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Flaeche'];
					echo "</td>" .chr(10);
					echo '<td>' .chr(10);
					echo $statdataline['Flugzeit'];
					echo "</td>" .chr(10);
					echo "</tr>" .chr(10);
					$PrevPilot =$statdataline['Pilot'];
					$PrevDate =$statdataline['Datum'];
					$ActDate =$PrevDate;
				}
				$i++;
			}
			echo "</table>" .chr(10);
			echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

		//	echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
			//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
			echo '</form>'. chr(10);
			echo '<br>'. chr(10);
			//pre_r($statdata);
		} 
		$TableSetup = (array_keys($statdataline));
		unset($TableSetup[0]); // FlugID
		unset($TableSetup[8]); //UAV#
		unset($TableSetup[17]); //Einsatztag
		unset($TableSetup[16]); //#Funde
		unset($TableSetup[24]); // Status
		unset($TableSetup[25]); //ListNr
		//pre_R($TableSetup);
		SaveDataToCSV($excelexport, $TableSetup, $excel_csv_path, "UTF-16LE", true, $excel_csv_file ,";");
	} // REDUCED
} else {
	//NoSelection
	echo "<i>Bitte Jagdgebiet und Pilot selektieren und <Einlesen> drücken.</i>";
}
?>

<script> 
//QR code
<?php 
echo "var iconloc = '". $IconLoc . "';\n";
echo "var buttonsize = ". 22 . ";\n";

 ?>
	
	qrswop1.innerHTML = "<img  id='pic2' src='" +  iconloc + "/show_qr_t.png' height='35px' title='QR code anzeigen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
	qrswop2.innerHTML = "<img  id='pic2' src='" +  iconloc + "/hide_qr_t.png' height='22px' title='QR code verbergen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
function Swop(mybloc1, mybloc2, mybutton, mytext1, mytext2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var b = document.getElementById(mybutton);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		b.innerHTML =  mytext1;
		
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		b.innerHTML = mytext2;
	}
}

</script>
</body>
</HTML>
