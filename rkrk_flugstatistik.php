<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >

<!-- examples
https://www.chartjs.org/docs/latest/
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
	$excel_csv_file = "RKRK_SummaryFluege (".($_SESSION[$SessNme_RegisteredInitialen] ?? '').").csv";
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

	function rkrk_stat_num($value) {
		if (is_int($value) || is_float($value)) {
			return $value;
		}
		if (is_string($value)) {
			$value = str_replace(',', '.', trim($value));
			if (is_numeric($value)) {
				return $value + 0;
			}
		}
		return 0;
	}

	function rkrk_stat_inc(&$root, $path, $add) {
		if (!is_array($root)) {
			$root = array();
		}
		$node = &$root;
		$last = array_pop($path);
		foreach ($path as $key) {
			if (!isset($node[$key]) || !is_array($node[$key])) {
				$node[$key] = array();
			}
			$node = &$node[$key];
		}
		$node[$last] = rkrk_stat_num($node[$last] ?? 0) + rkrk_stat_num($add);
	}

	function rkrk_summary_accumulate_flight(&$yearStats, $statdataline) {
		$pilotKey = 'P' . (string)($statdataline['Pilot'] ?? '');
		$gdeKey = 'G' . (string)($statdataline['Gde'] ?? '');
		$jgKey = 'J' . (string)($statdataline['JG'] ?? '');
		$regKey = 'R' . (string)($statdataline['Reg'] ?? '');
		$jgRegKey = 'R' . (string)($statdataline['JG'] ?? '');
		$dateKey = 'D' . (string)($statdataline['Datum'] ?? '');
		rkrk_stat_inc($yearStats, array('Pilot', $pilotKey, 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('Pilot', $pilotKey, 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('Pilot', $pilotKey, 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['Pilot'][$pilotKey]['Einsatztage'][$dateKey] = $statdataline['Datum'] ?? '';
		$yearStats['Pilot'][$pilotKey]['AnzEinsaetze'] = count($yearStats['Pilot'][$pilotKey]['Einsatztage']);

		rkrk_stat_inc($yearStats, array('Gde', $gdeKey, 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('Gde', $gdeKey, 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('Gde', $gdeKey, 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['Gde'][$gdeKey]['Einsatztage'][$dateKey] = $statdataline['Datum'] ?? '';
		$yearStats['Gde'][$gdeKey]['AnzEinsaetze'] = count($yearStats['Gde'][$gdeKey]['Einsatztage']);

		rkrk_stat_inc($yearStats, array('JG', $jgKey, 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('JG', $jgKey, 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('JG', $jgKey, 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['JG'][$jgKey]['Einsatztage'][$dateKey] = $statdataline['Datum'] ?? '';
		$yearStats['JG'][$jgKey]['AnzEinsaetze'] = count($yearStats['JG'][$jgKey]['Einsatztage']);

		rkrk_stat_inc($yearStats, array('Reg', $regKey, 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('Reg', $regKey, 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('Reg', $regKey, 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['Reg'][$regKey]['Einsatztage'][$dateKey] = $statdataline['Datum'] ?? '';
		$yearStats['Reg'][$regKey]['AnzEinsaetze'] = count($yearStats['Reg'][$regKey]['Einsatztage']);

		rkrk_stat_inc($yearStats, array('JGPIL', $jgRegKey, $pilotKey, 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('JGPIL', $jgRegKey, $pilotKey, 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('JGPIL', $jgRegKey, $pilotKey, 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['JGPIL'][$jgRegKey][$pilotKey]['Einsatztage'][$dateKey] = $statdataline['Datum'] ?? '';
		$yearStats['JGPIL'][$jgRegKey][$pilotKey]['AnzEinsaetze'] = count($yearStats['JGPIL'][$jgRegKey][$pilotKey]['Einsatztage']);

		rkrk_stat_inc($yearStats, array('Sum', 'Felder'), 1);
		rkrk_stat_inc($yearStats, array('Sum', 'Flaeche'), round(rkrk_stat_num($statdataline['Flaeche'] ?? 0)));
		if (($statdataline['Kontrollflug'] ?? '') != "Ja") {
			rkrk_stat_inc($yearStats, array('Sum', 'Fund'), $statdataline['Fund'] ?? 0);
		}
		$yearStats['Sum']['Einsatztage'][(string)($statdataline['Pilot'] ?? '')."-".(string)($statdataline['Datum'] ?? '')] = $statdataline['Pilot']."-".$statdataline['Datum'];
		$yearStats['Sum']['AnzEinsaetze'] = count($yearStats['Sum']['Einsatztage']);

		$warnung = (string)($statdataline['Warnung'] ?? '');
		rkrk_stat_inc($yearStats, array('Warn', 'CTR'), preg_match("/(CTR)/", $warnung));
		rkrk_stat_inc($yearStats, array('Warn', 'NGT'), preg_match("/(NGT)/", $warnung));
		rkrk_stat_inc($yearStats, array('Warn', 'STP'), preg_match("/(STP)/", $warnung));
		rkrk_stat_inc($yearStats, array('Warn', 'SORA'), preg_match("/(SORA)/", $warnung));
		rkrk_stat_inc($yearStats, array('Warn', 'SPEZ'), preg_match("/(NSG|NSF|FVB|USP)/", $warnung));
	}

	function rkrk_build_cumulative_year_stats($yearStats, $untilYear, $fromYear = 2020) {
		$out = array();
		if (!is_array($yearStats)) {
			return $out;
		}
		$fromYear = intval($fromYear);
		$untilYear = intval($untilYear);
		if ($untilYear < $fromYear) {
			return $out;
		}
		$years = range($fromYear, $untilYear);
		$cumDays = 0;
		$cumFunde = 0;
		$cumHa = 0;
		foreach ($years as $yn) {
			$row = array();
			if (isset($yearStats[$yn]) && is_array($yearStats[$yn])) {
				$row = $yearStats[$yn];
			} elseif (isset($yearStats[(string)$yn]) && is_array($yearStats[(string)$yn])) {
				$row = $yearStats[(string)$yn];
			}
			$cumDays += intval($row['AnzEinsaetze'] ?? 0);
			$cumFunde += intval($row['Fund'] ?? 0);
			$cumHa += intval($row['Flaeche'] ?? 0);
			$out[(string)$yn] = array(
				'AnzEinsaetze' => $cumDays,
				'Fund' => $cumFunde,
				'Flaeche' => $cumHa,
			);
		}
		return $out;
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
				or 	(in_array("alle", $EnabledOptions["Regions"]["List"] ?? array()))
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
				or 	(in_array("alle", $EnabledOptions["Regions"]["List"] ?? array()))
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

if (!isset($Jahr) || $Jahr === '') {
	$Jahr = date('Y');
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

$nlEmbed = isset($_GET['Embed']) && $_GET['Embed'] !== '' && $_GET['Embed'] !== '0';
if ($nlEmbed) {
	$all = true;
}


if (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

$PilotenWahl = '';
if (isset($_GET["PilotenWahl"])) { 
	$PilotenWahl = str_replace(array(",",";"), array(" ", " "), $_GET["PilotenWahl"]);
	//pre_R(explode(" ", $PilotenWahl));
	foreach ($OptionPilot as $Pilot ) {
		if ((($_POST["Pilot" . $i] ?? '')=="on") 
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
		if ((($_POST["Pilot" . $i] ?? '')=="on") 
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
	$Chartslist[]="Kumulativ";
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
					$statdata[$linekey]["Total"] = rkrk_stat_num($statdata[$linekey]["Total"] ?? 0) + rkrk_stat_num($entry);
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
	if (!isset($FontSize) || $FontSize === "") {
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
.rkrk-stat-kpis {
	display: grid;
	grid-template-columns: repeat(4, minmax(0, 1fr));
	gap: 12px;
	margin: 0 0 16px;
	max-width: 920px;
}
.rkrk-stat-kpi {
	background: #fff;
	border: 1px solid #e4e8ee;
	border-radius: 12px;
	padding: 14px 12px 12px;
	text-align: center;
	box-shadow: 0 1px 3px rgba(16,24,40,.06);
}
.rkrk-stat-kpi .lbl { font-size: 13px; color: #5b6570; letter-spacing: .02em; }
.rkrk-stat-kpi .val { font-size: 42px; font-weight: 700; line-height: 1.15; margin-top: 4px; }
.rkrk-stat-charts {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 20px 24px;
	width: 100%;
	max-width: 1440px;
	align-items: start;
	background: transparent;
}
.rkrk-stat-cell {
	min-width: 0;
	max-width: 100%;
}
.rkrk-stat-cell-span {
	grid-column: 1 / -1;
}
.rkrk-stat-charts-single {
	grid-template-columns: minmax(0, 1fr);
	max-width: 760px;
}
table.rkrk-stat-legend,
table.rkrk-stat-flights {
	width: auto !important;
	max-width: 100%;
}
.rkrk-stat-scroll {
	max-width: 1440px;
	overflow-x: auto;
}
@media (max-width: 960px) {
	.rkrk-stat-charts {
		grid-template-columns: minmax(0, 1fr);
	}
}
@media (max-width: 720px) {
	.rkrk-stat-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	.rkrk-stat-kpi .val { font-size: 32px; }
}

</style>
<?php


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
				$GetRegions = array();
			//process Regions form
			//pre_r($RegionSettings);
			$ListOfRegions = "";
			Foreach ($RegionSettings as $Reg) {
				if ($Reg["Aktiv"]) {
					$RegOptions[$Reg["Reg"]]["Text"]= $Reg["Reg"];
					if (!empty($GetRegions[$Reg["Reg"]])) { 
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
						or 	(in_array("alle", $EnabledOptions["Regions"]["List"] ?? array())))
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
			if ((($_POST["Pilot" . $i] ?? '')=="on") 
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

		$ParameterSet = "SelFrom=" . $FromDate . "&" . "SelTo=" . $ToDate . "&" . "RegList=" . str_replace(",", " ", $ListOfRegions) . "&" ."PilotenWahl=". str_replace(",", " ", $ListOfPilots). "&Charts=Top,EinsatzPiloten,EinsatzJagdgebiete,Einsatztage1,Rehkitzfunde,FelderProRegion,Entwicklung1,Entwicklung2,Kumulativ,EinsaetzeProTagSchmal,Legende"."&Color=white&GA=gast&" . $MissionSelectionParameter;
		$PageLink =  $WebLink_public . "rkrk_flugstatistik.php" ."?" . $ParameterSet;
		//echo "SeitenLink: " . $PageLink . "<br>";
		//echo "RegList: " . $ListOfRegions . "   PilotList" .$ListOfPilots; 

		$QRCode = $WebLink_public . "rkrk_flugstatistik.php" ."?" . str_replace(array("&", " "),array("%26","%2B"), $ParameterSet); 
		//$QRCode = $WebLink_public . "rkrk_flugstatistik.php" ."?" . $ParameterSet; 
		$qrcode1 = "";
		$qrcode2 = "";
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
						or (in_array("alle", $EnabledOptions["Regions"]["List"] ?? array())
 						or ($PageFormat == 'reduced')
 						or ($PageFormat == 'selected')
						)
					)
					and
					(
					  (($EnabledPilot[$Flug["Pilot"] ?? ''] ?? 0)==1)
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

    if (is_file(__DIR__ . '/scripts/rkrk_statdata.php')) {
		include_once(__DIR__ . '/scripts/rkrk_statdata.php');
	}


// ========================================
// Process Statistics Data
// ========================================
// Felddaten 
//if (!$NoRegSelection and !$NoPilSelection) {
if (empty($NoPilSelection)) {
	if (!is_array($Feldkatalog ?? null)) {
		$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	}
	if (!is_array($Feldkatalog)) {
		$Feldkatalog = array();
	}
	foreach ($Feldkatalog as $JGFields) {
		if (!is_array($JGFields)) {
			continue;
		}
		foreach ($JGFields as $key => $Field)  {
			if (!is_array($Field)) {
				continue;
			}
			$FieldDataArray[$key]["FeldID"] = $Field["FeldID"] ?? '';
			$FieldDataArray[$key]["Text"] = $Field["Feldname"] ?? '';
			if (($Field["Flurname"] ?? '') != "") {
				$FieldDataArray[$key]["Text"] .= " " . $Field["Flurname"];
			}
			$FieldDataArray[$key]["Text"] .= " " . ($Field["GrundstueckID"] ?? '');
			//$FieldDataArray[$key]["Flugzeit"] = $Field["Flugzeit"];
			$FieldDataArray[$key]["Flaeche"] = $Field["Flaeche"] ?? '';
			$FieldDataArray[$key]["GrundstueckID"] = $Field["GrundstueckID"] ?? '';
			$FieldDataArray[$key]["JG"] = $Field["JG"] ?? '';
			$FieldDataArray[$key]["Gemeinde"] = $Field["Gemeinde"] ?? '';
			$FieldDataArray[$key]["Jaeger"] = $Field["Jaeger"] ?? '';
		} // JG Fields
	} // $Feldkatalog


	//pre_R($FieldDataArray);
	//Adressdaten
	$csv_file= $RKRKContactFile;

	//Read CSV file
	transferGoogleSheetToCSV();
	$Adresskatalog = csv_to_array($csvDocLocation.$csv_file, ';');
	if (!is_array($Adresskatalog)) {
		$Adresskatalog = array();
	}

	foreach ($Adresskatalog as $Person) {
		if (!is_array($Person)) {
			continue;
		}
		$initialen = trim((string)($Person['Initialen'] ?? ''));
		if ($initialen === '') {
			continue;
		}
		$AdressDataArray[$initialen]['Initialen'] = $initialen;
		$AdressDataArray[$initialen]['Text'] = ($Person['JG'] ?? '') . " " . ($Person['Name'] ?? '') . " " . ($Person['Kontaktnummer'] ?? '');
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
	if (!is_array($Feldkatalog)) {
		$Feldkatalog = array();
	}
	foreach ($Feldkatalog as $JGkatalog) {
		if (!is_array($JGkatalog)) {
			continue;
		}
		foreach ($JGkatalog as $statdataline) {
			if (!is_array($statdataline)) {
				continue;
			}
			$jg = (string)($statdataline['JG'] ?? '');
			$regRow = $RegDataArray[$jg] ?? null;
			$regUp = is_array($regRow) ? (string)($regRow['RegUp'] ?? '') : '';
			if ((($EnabledRegion[$jg] ?? 0)==1) or 
				($PageFormat == 'selected') or ($PageFormat == 'reduced'))
				{
				// Gemeinde
				$Gde = (string)($statdataline['Gemeinde'] ?? '');
				if ($Gde=="") {
					$Gde="andere";
				}
				$flaeche = round(rkrk_stat_num($statdataline['Flaeche'] ?? 0));
				$flugzeit = round(rkrk_stat_num($statdataline['Flugzeit'] ?? 0));
				$flugzeit1 = round(rkrk_stat_num($statdataline['Flugzeit'] ?? 0), 1);
				rkrk_stat_inc($FeldStatistics, array('Gde', $Gde, 'Felder'), 1);
				rkrk_stat_inc($FeldStatistics, array('Gde', $Gde, 'Fläche'), $flaeche);
				rkrk_stat_inc($FeldStatistics, array('Gde', $Gde, 'Flugzeit'), $flugzeit);
				// JG
				rkrk_stat_inc($FeldStatistics, array('JG', $jg, 'Felder'), 1);
				rkrk_stat_inc($FeldStatistics, array('JG', $jg, 'Fläche'), $flaeche);
				rkrk_stat_inc($FeldStatistics, array('JG', $jg, 'Flugzeit'), $flugzeit1);
				// Reg
				rkrk_stat_inc($FeldStatistics, array('Reg', $regUp, 'Felder'), 1);
				rkrk_stat_inc($FeldStatistics, array('Reg', $regUp, 'Fläche'), $flaeche);
				rkrk_stat_inc($FeldStatistics, array('Reg', $regUp, 'Flugzeit'), $flugzeit);
				// Year
				$updateYear = date('Y', strtotime((string)($statdataline['Update'] ?? '')));
				rkrk_stat_inc($FeldStatistics, array('Felder', $regUp, $updateYear, 'Felder'), 1);
				rkrk_stat_inc($FeldStatistics, array('Felder', $regUp, $updateYear, 'Fläche'), $flaeche);
				rkrk_stat_inc($FeldStatistics, array('Felder', $regUp, $updateYear, 'Flugzeit'), $flugzeit);
			}
		}
	}
	//Sum up Year
	
	for ($x = 2021; $x <= $Jahr; $x++) {
	//for ($x = 2021; $x <= date("Y"); $x++) {
		$YearList[$x]=$x;
	}
	if (!isset($FeldStatistics['Felder']) || !is_array($FeldStatistics['Felder'])) {
		$FeldStatistics['Felder'] = array();
	}
	foreach ($FeldStatistics['Felder'] as $Reg => $statdataReg) {
		foreach ($statdataReg as $Year => $statdataline) {
			// sum up per year
			foreach ($YearList as $SumYear => $SumDataYear) {
			//echo $Year . " ". $SumYear . " " . $FeldStatistics['Felder'][$Reg][$Year]['Felder']. "<br>";
				$yearFelder = rkrk_stat_num($statdataline['Felder'] ?? 0);
				if (($SumYear>=$Year) and ($yearFelder>0)) {
					$FeldStatistics['FelderRegSum'][$Reg][$SumYear]['Jahr'] = $SumYear;
					rkrk_stat_inc($FeldStatistics, array('FelderRegSum', $Reg, $SumYear, 'Felder'), round(rkrk_stat_num($statdataline['Felder'] ?? 0)));
					rkrk_stat_inc($FeldStatistics, array('FelderRegSum', $Reg, $SumYear, 'Fläche'), round(rkrk_stat_num($statdataline['Fläche'] ?? 0)));
					rkrk_stat_inc($FeldStatistics, array('FelderRegSum', $Reg, $SumYear, 'Flugzeit'), round(rkrk_stat_num($statdataline['Flugzeit'] ?? 0)));
					rkrk_stat_inc($FeldStatistics, array('FelderSum', $SumYear, 'Felder'), round(rkrk_stat_num($statdataline['Felder'] ?? 0)));
					rkrk_stat_inc($FeldStatistics, array('FelderSum', $SumYear, 'Fläche'), round(rkrk_stat_num($statdataline['Fläche'] ?? 0)));
					rkrk_stat_inc($FeldStatistics, array('FelderSum', $SumYear, 'Flugzeit'), round(rkrk_stat_num($statdataline['Flugzeit'] ?? 0)));
				}
			}
		}
		if (isset($FeldStatistics['FelderRegSum'][$Reg]) && is_array($FeldStatistics['FelderRegSum'][$Reg])) {
			ksort($FeldStatistics['FelderRegSum'][$Reg]);
		}
	}
	if (isset($FeldStatistics['FelderSum']) && is_array($FeldStatistics['FelderSum'])) {
		ksort($FeldStatistics['FelderSum']);
	}
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
			$pilot = (string)($statdataline['Pilot'] ?? '');
			$jg = (string)($statdataline['JG'] ?? '');
			$reg = (string)($statdataline['Reg'] ?? '');
			$datum = (string)($statdataline['Datum'] ?? '');
			$flaeche = round(rkrk_stat_num($statdataline['Flaeche'] ?? 0));
			$flugzeit = round(rkrk_stat_num($statdataline['Flugzeit'] ?? 0));
			$fund = $statdataline['Fund'] ?? 0;
			$kontrollflug = ($statdataline['Kontrollflug'] ?? '') != "Ja";
			// Pilot
			rkrk_stat_inc($FlightStatistics, array('Pilot', $pilot, 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('Pilot', $pilot, 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('Pilot', $pilot, 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('Pilot', $pilot, 'Fund'), $fund);
			}
			$FlightStatistics['Pilot'][$pilot]['Einsatztage'][$datum] = $datum;
			$FlightStatistics['Pilot'][$pilot]['AnzEinsätze'] = count($FlightStatistics['Pilot'][$pilot]['Einsatztage']);
			// Gemeinde
			$Gde = (string)($statdataline['Gde'] ?? '');
			if ($Gde=="") {
				$Gde="andere";
			}
			rkrk_stat_inc($FlightStatistics, array('Gde', $Gde, 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('Gde', $Gde, 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('Gde', $Gde, 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('Gde', $Gde, 'Fund'), $fund);
			}
			$FlightStatistics['Gde'][$Gde]['Einsatztage'][$datum] = $datum;
			$FlightStatistics['Gde'][$Gde]['AnzEinsätze'] = count($FlightStatistics['Gde'][$Gde]['Einsatztage']);
			// JG
			rkrk_stat_inc($FlightStatistics, array('JG', $jg, 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('JG', $jg, 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('JG', $jg, 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('JG', $jg, 'Fund'), $fund);
			}
			$FlightStatistics['JG'][$jg]['Einsatztage'][$datum] = $datum;
			$FlightStatistics['JG'][$jg]['AnzEinsätze'] = count($FlightStatistics['JG'][$jg]['Einsatztage']);
			// Reg
			rkrk_stat_inc($FlightStatistics, array('Reg', $reg, 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('Reg', $reg, 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('Reg', $reg, 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('Reg', $reg, 'Fund'), $fund);
			}
			$FlightStatistics['Reg'][$reg]['Einsatztage'][$datum] = $datum;
			$FlightStatistics['Reg'][$reg]['AnzEinsätze'] = count($FlightStatistics['Reg'][$reg]['Einsatztage']);
			// Datum
			rkrk_stat_inc($FlightStatistics, array('Datum', $datum, 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('Datum', $datum, 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('Datum', $datum, 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('Datum', $datum, 'Fund'), $fund);
			}
			$FlightStatistics['Datum'][$datum]['Einsatztage'][$datum] = $datum;
			$FlightStatistics['Datum'][$datum]['AnzEinsätze'] = count($FlightStatistics['Datum'][$datum]['Einsatztage']);
			$FlightStatistics['Datum'][$datum]['Piloten'][$pilot] = $pilot;
			$FlightStatistics['Datum'][$datum]['AnzPiloten'] = count($FlightStatistics['Datum'][$datum]['Piloten']);
			// Sum
			rkrk_stat_inc($FlightStatistics, array('Sum', 'Felder'), 1);
			rkrk_stat_inc($FlightStatistics, array('Sum', 'Fläche'), $flaeche);
			rkrk_stat_inc($FlightStatistics, array('Sum', 'Flugzeit'), $flugzeit);
			if ($kontrollflug) {
				rkrk_stat_inc($FlightStatistics, array('Sum', 'Fund'), $fund);
			}
			$FlightStatistics['Sum']['Einsatztage'][$datum."-".$pilot] = $datum;
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
			$warnung = (string)($statdataline['Warnung'] ?? '');
			rkrk_stat_inc($FlightStatistics, array('Warn', 'CTR'), preg_match("/(CTR)/", $warnung));
			if ($Jahr<2024) {
				rkrk_stat_inc($FlightStatistics, array('Warn', 'NGT'), preg_match("/(NGT)/", $warnung));
			} else {	
				rkrk_stat_inc($FlightStatistics, array('Warn', 'NGT1'), preg_match("/(NGT1)/", $warnung));
				rkrk_stat_inc($FlightStatistics, array('Warn', 'NGT2'), preg_match("/(NGT2)/", $warnung));
			}
			rkrk_stat_inc($FlightStatistics, array('Warn', 'STP'), preg_match("/(STP)/", $warnung));
			rkrk_stat_inc($FlightStatistics, array('Warn', 'SORA'), preg_match("/(SORA)/", $warnung));
			rkrk_stat_inc($FlightStatistics, array('Warn', 'SPEZ'), preg_match("/(NSG|NSF|FVB|USP)/", $warnung));
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

	if (isset($FlightStatistics['JG']) && is_array($FlightStatistics['JG'])) {
		ksort($FlightStatistics['JG']);
	}
	if (isset($FlightStatistics['Gde']) && is_array($FlightStatistics['Gde'])) {
		ksort($FlightStatistics['Gde']);
	}
	// create summary data
	if (!isset($SumStats) || !is_array($SumStats)) {
		$SumStats = array();
	}
	if (!isset($SumStats['JGPIL']) || !is_array($SumStats['JGPIL'])) {
		$SumStats['JGPIL'] = array();
	}
	
	foreach ($SummaryStatistics as $Y_key => $Y_data) {
		ksort($SumStats['JGPIL']);
		//echo $Y_key;
		if (!is_array($Y_data) || substr($Y_key,-4) > $Jahr) {
			continue;
		}
			//echo "a";
			$jgPil = (isset($Y_data["JGPIL"]) && is_array($Y_data["JGPIL"])) ? $Y_data["JGPIL"] : array();
			foreach ($jgPil as $J_key => $J_data) {
				//echo substr($J_key,1) . "<br>";
				if (!is_array($J_data)) {
					continue;
				}
				if ((($EnabledRegion[substr((string)$J_key,1)] ?? 0)==1)
					or $all){
					
					//echo "b";
					foreach ($J_data as $P_key => $P_data) {
					//echo "b1";
					//echo substr($P_key,1) . "<br>";
						if (!is_array($P_data)) {
							continue;
						}
						$yearKey = substr($Y_key,1);
						$SumStats[$yearKey]['JGPIL']['AnzEinsaetze']=0;
						//pre_R($P_data);
						if ((($EnabledPilot[substr((string)$P_key,1)] ?? 0)==1) 
							or $all){
							//echo "c";
					
							if (isset($P_data["Einsatztage"]) && is_array($P_data["Einsatztage"])) {
								foreach ($P_data["Einsatztage"] as $key => $data) {
									$SumStats['JGPIL'][$yearKey]['Einsatztage'][$P_key.$key] = $data;
								}
							}
							//echo "d";
							rkrk_stat_inc($SumStats, array('JGPIL', $yearKey, 'Felder'), $P_data['Felder'] ?? 0);
							rkrk_stat_inc($SumStats, array('JGPIL', $yearKey, 'Flaeche'), $P_data['Flaeche'] ?? 0);
							rkrk_stat_inc($SumStats, array('JGPIL', $yearKey, 'Fund'), $P_data['Fund'] ?? 0);
						}
						$tage = $SumStats['JGPIL'][$yearKey]['Einsatztage'] ?? array();
						$SumStats['JGPIL'][$yearKey]['AnzEinsaetze'] = is_array($tage) ? count($tage) : 0;
					}	
				}	
			}

			if (isset($Y_data['JG']) && is_array($Y_data['JG'])) {
				ksort($Y_data['JG']);
			}
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
			$sum = (isset($Y_data['Sum']) && is_array($Y_data['Sum'])) ? $Y_data['Sum'] : array();
			$yearKey = substr($Y_key,1);
			$SumStats[$yearKey]['Felder'] = $sum['Felder'] ?? 0;
			$SumStats[$yearKey]['Flaeche'] = $sum['Flaeche'] ?? 0;
			$SumStats[$yearKey]['Fund'] = $sum['Fund'] ?? 0;
			$SumStats[$yearKey]['AnzEinsaetze'] = $sum['AnzEinsaetze'] ?? 0;
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
				$datum = (string)$statdataline['Datum'];
				$flightDay = $FlightStatistics['Datum'][$datum] ?? array();
				if (!is_array($flightDay)) {
					$flightDay = array();
				}
				$weatherDay = $Weather[$datum] ?? array();
				if (!is_array($weatherDay)) {
					$weatherDay = array();
				}
				$MissionsStatistics['Datum'][$datum]['Datum'] = $datum;
				$MissionsStatistics['Datum'][$datum]['Wetter'] = $weatherDay['Wetter'] ?? '';
				$MissionsStatistics['Datum'][$datum]['Temperatur'] = $weatherDay['Temp'] ?? '';
				$MissionsStatistics['Datum'][$datum]["Wetter_Icon"] = str_replace($WeatherText, $WeatherIcon, (string)($weatherDay['Wetter'] ?? ''));
				$MissionsStatistics['Datum'][$datum]['Fund'] = intval(rkrk_stat_num($flightDay['Fund'] ?? 0));
				$MissionsStatistics['Datum'][$datum]['Felder'] = intval(rkrk_stat_num($flightDay['Felder'] ?? 0));
				$MissionsStatistics['Datum'][$datum]['Piloten'] = intval(rkrk_stat_num($flightDay['AnzPiloten'] ?? 0));
				//MissionsStatistics['Datum'][$statdataline['Datum']]['Wetter'] = 
			}
	}	
	//pre_r($MissionsStatistics);

	// Chart Arrays
	$c_green=array("#48673A", "#527642","#6A9955","#96B588","#A0C291","#C5E0B4");
	$c_bggreen=array("rgba(72, 103, 58, 0.6)", "rgba(82, 118, 66, 0.6)","rgba(106, 153, 85, 0.6)","rgba(150, 181, 136, 0.6)","rgba(160, 194, 145, 0.6)","rgba(197, 224, 180, 0.6)");
	$c_red=array("#7A201C", "#922722","#B3322C","#C23C35","#F84941");
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

	$KumulativStats = rkrk_build_cumulative_year_stats($SumStats['JGPIL'] ?? array(), $Jahr, 2020);
	$Kumulativ_Data = array(
		GetDataFromStrArrayKey($KumulativStats, 'Kumulativ seit 2020 [Jahr]'),
		GetDataFromStrArrayField($KumulativStats, 'AnzEinsaetze', 'Einsatztage'),
		GetDataFromStrArrayField($KumulativStats, 'Fund', 'Gerettete Rehkitze'),
		GetDataFromStrArrayField($KumulativStats, 'Flaeche', 'Fläche [ha]'),
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
	$JGLegend = array();
	$JGList = GetDataFromStrArrayKey($FlightStatistics['JG']);
	foreach ($JGList as $entry) {
	  $JG = str_replace("'","", $entry);
	  if ($JG!="") {
		$jgText = (isset($RegionSettings[$JG]) && is_array($RegionSettings[$JG])) ? (string)($RegionSettings[$JG]['Text'] ?? '') : '';
		$JGLegend[$JG] = $JG . ": " . $jgText;
	  }
	}
	$JGLegend = implode (', ', $JGLegend);
	//echo $JGLegend;
	//Gde
	$GdeLegend = array();
	$GdeList = GetDataFromStrArrayKey($FlightStatistics['Gde']);
	foreach ($GdeList as $entry) {
	  $Gde = str_replace("'","", $entry);	
	  if ($Gde!="") {
		$gdeText = (isset($OptionGemeinde[$Gde]) && is_array($OptionGemeinde[$Gde])) ? (string)($OptionGemeinde[$Gde]['Text'] ?? '') : '';
		$GdeLegend[$Gde] = $Gde . ": " . $gdeText;
	  }
	}
	$GdeLegend = implode (', ', $GdeLegend);
	//echo $GdeLegend;

	//Pilots
	$PilotsLegend = array();
	$PilotList = GetDataFromStrArrayKey($FlightStatistics['Pilot']);
	foreach ($PilotList as $entry) {
	  $Pilot = str_replace("'","", $entry);	
	  if ($Pilot!="") {
		$pilotText = (isset($OptionPilot[$Pilot]) && is_array($OptionPilot[$Pilot])) ? (string)($OptionPilot[$Pilot]['Text'] ?? '') : '';
		$PilotsLegend[$Pilot] = $Pilot . ": " . $pilotText;
	  }
	}
	$PilotsLegend = implode (', ', $PilotsLegend);
	//echo $PilotsLegend;
	//Reg
	$RegionsLegend = array();
	$RegList = GetDataFromStrArrayKey($FlightStatistics['Reg']);
	foreach ($RegList as $entry) {
	  $Regup = str_replace("'","", $entry);	
	  if ($Regup!="") {
		$regText = (isset($OptionRegUp[$Regup]) && is_array($OptionRegUp[$Regup])) ? (string)($OptionRegUp[$Regup]['Text'] ?? '') : '';
		$RegionsLegend[$Regup] = $Regup . ": " . $regText;
	  }
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
		$Day_Data[$i][4]=$day['Fund'] ?? 0;
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

	if (!function_exists('rkrk_chart_wanted')) {
		function rkrk_chart_wanted($name, $alsoOnFull = false) {
			global $Chartslist, $PageFormat;
			if ($Chartslist === 'all') {
				return true;
			}
			if (is_array($Chartslist) && in_array($name, $Chartslist, true)) {
				return true;
			}
			return $alsoOnFull && ($PageFormat === 'full');
		}
		function rkrk_stat_chart_has_data($rows, $axis = 1) {
			return is_array($rows) && isset($rows[$axis]) && count($rows[$axis]) > 1;
		}
		function rkrk_emit_stat_chart($cfg) {
			global $NumChart, $ChartHeight, $ChartWidth, $BarColors;
			$useColors = (isset($cfg['colors']) && $cfg['colors'] !== '') ? $cfg['colors'] : $BarColors;
			$id = 'Chart' . $NumChart;
			$NumChart++;
			$wanted = array_key_exists('wanted', $cfg)
				? (bool)$cfg['wanted']
				: rkrk_chart_wanted($cfg['name'], !empty($cfg['alsoOnFull']));
			if (!$wanted) {
				return;
			}
			$rows = $cfg['rows'];
			$axis = isset($cfg['countAxis']) ? $cfg['countAxis'] : 1;
			if (!rkrk_stat_chart_has_data($rows, $axis)) {
				if (!empty($cfg['emptyMsg'])) {
					echo $cfg['emptyMsg'];
				}
				return;
			}
			$slot = isset($cfg['slot']) ? $cfg['slot'] : '1';
			$spanClass = ($slot === 'span') ? ' rkrk-stat-cell-span' : '';
			echo '<div class="rkrk-stat-cell'.$spanClass.'">';
			$w = isset($cfg['width']) ? $cfg['width'] : $ChartWidth;
			$h = isset($cfg['height']) ? $cfg['height'] : $ChartHeight;
			echo '<div id="'.$id.'" class="rkrk-chart-host" style="width:100%; max-width:'.$w.'px; height:'.$h.'px;"></div>';
			$prep = isset($cfg['prep']) ? $cfg['prep'] : 'options';
			if ($prep === 'optionsFill') {
				$data = Collapse2dArray(GetChartOptions(FillEmptyEntries($rows), true));
			} elseif ($prep === 'optionsX') {
				$data = Collapse2dArray(GetChartOptions(XTransform2dArray(FillEmptyEntries($rows)), true));
			} elseif ($prep === 'pie' || $prep === 'xfill') {
				$data = Collapse2dArray(XTransform2dArray(FillEmptyEntries($rows)));
			} elseif ($prep === 'combo') {
				$data = Collapse2dArray(GetChartOptions(FillEmptyEntries($rows), false));
			} else {
				$data = Collapse2dArray(GetChartOptions($rows, true));
			}
			$type = $cfg['type'];
			$title = $cfg['title'];
			$hTitle = isset($cfg['hTitle']) ? $cfg['hTitle'] : '';
			$vTitle = isset($cfg['vTitle']) ? $cfg['vTitle'] : '';
			if ($type === 'column') {
				echo createColumnChart($id, $title, $data, $useColors, $hTitle, $vTitle, $h, $w);
			} elseif ($type === 'bar') {
				echo createBarChart($id, $title, $data, $useColors, $hTitle, $vTitle, $h, $w);
			} elseif ($type === 'line') {
				echo createLineChart($id, $title, $data, $useColors, $hTitle, $vTitle, $h, $w);
			} elseif ($type === 'pie') {
				echo createPieChart($id, $title, $data, $useColors, $h, $w);
			} elseif ($type === 'combo') {
				echo createComboChart($id, $title, $data, $useColors, $hTitle, $vTitle, $h, $w);
			}
			if (!array_key_exists('table', $cfg) || $cfg['table'] !== false) {
				$tableRows = isset($cfg['tableRows']) ? $cfg['tableRows'] : $rows;
				echo GetTableFromArray($tableRows);
			}
			echo '</div>';
		}
	}

   //pre_R($FlightStatistics);
	if (rkrk_chart_wanted('Top')) {
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
		$kpiItems = array(
			array('Einsatztage', $topEinsaetze, '#e67e22'),
			array('Felder', $topFelder, '#c0392b'),
			array('Funde', $topFund, '#2e7d32'),
			array('Fläche [ha]', $topFlaeche, '#1565c0'),
		);
		echo '<div class="rkrk-stat-kpis">';
		foreach ($kpiItems as $kpi) {
			echo '<div class="rkrk-stat-kpi"><div class="lbl">'.htmlspecialchars($kpi[0], ENT_QUOTES, 'UTF-8').'</div>';
			echo '<div class="val" style="color:'.$kpi[2].'">'.htmlspecialchars((string)$kpi[1], ENT_QUOTES, 'UTF-8').'</div></div>';
		}
		echo '</div>';
	}
	$chartsClass = 'rkrk-stat-charts';
	if (!(($PageFormat == 'full') or ($Gast == 'gast'))) {
		$chartsClass .= ' rkrk-stat-charts-single';
	}
	echo '<div class="'.$chartsClass.'">';
	//	$tableFieldOpen1 = "<tr><td>";
	//	$tableFieldOpen2 = "<td>";
	//	$tableFieldClose1 = "</td>";
	//	$tableFieldClose2 = "</td></tr>";
	$NumChart = 1;
	rkrk_emit_stat_chart(array(
		'name' => 'EinsatzPiloten', 'type' => 'column', 'slot' => '1',
		'title' => 'Einsätze Piloten', 'rows' => $Pilot_Data,
		'vTitle' => '[#, ha, min, #, #]',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsatzGemeinde', 'type' => 'column', 'slot' => '2',
		'title' => 'Einsätze in Gemeinden', 'rows' => $Gde_Data,
		'vTitle' => '[#, ha, min, #, #]',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsatzRegionen', 'type' => 'bar', 'slot' => '1', 'alsoOnFull' => true,
		'title' => 'Einsätze in Regionen', 'rows' => $Reg_Data,
		'hTitle' => '[#, ha, min, #, #]',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsatzJagdgebiete', 'type' => 'column', 'slot' => '2',
		'title' => 'Einsätze in Jagdgebieten', 'rows' => $JG_Data,
		'vTitle' => '[#, ha, min, #, #]',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Flaeche', 'type' => 'pie', 'slot' => '1', 'alsoOnFull' => true,
		'title' => 'Fläche [ha]', 'rows' => $Gde_DataFlaeche, 'prep' => 'pie',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Geflogene Felder', 'type' => 'pie', 'slot' => '2', 'alsoOnFull' => true,
		'title' => 'Geflogene Felder', 'rows' => $Gde_DataFelder, 'prep' => 'pie',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Einsatztage1', 'type' => 'pie', 'slot' => '1',
		'title' => 'Einsatztage', 'rows' => $Gde_DataEinsatz, 'prep' => 'pie',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Rehkitzfunde', 'type' => 'pie', 'slot' => '2',
		'title' => 'Rehkitzfunde', 'rows' => $Gde_DataFund, 'prep' => 'pie',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsaetzeProTagBar', 'type' => 'column', 'slot' => '1',
		'title' => 'Einsätze pro Tag', 'rows' => $Day_Data, 'prep' => 'optionsFill',
		'hTitle' => '[#, ha, min, #]',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Warnzonen', 'type' => 'column', 'slot' => '2', 'alsoOnFull' => true,
		'title' => 'Flüge in Spezialzonen', 'rows' => $Warn_Data, 'prep' => 'xfill',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'FelderProGemeinde', 'type' => 'column', 'slot' => '1', 'alsoOnFull' => true,
		'title' => 'Felder pro Gemeinde', 'rows' => $FeldGde_Data, 'prep' => 'optionsX',
		'hTitle' => 'Gemeinde',
		'tableRows' => XTransform2dArray(AddTotalColumn($FeldGde_Data)),
	));
	rkrk_emit_stat_chart(array(
		'name' => 'FelderProJagdgebiet', 'type' => 'column', 'slot' => '2', 'alsoOnFull' => true,
		'title' => 'Felder pro Jagdgebiet', 'rows' => $FeldJG_Data, 'prep' => 'optionsFill',
		'tableRows' => XTransform2dArray(AddTotalColumn($FeldJG_Data)),
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Entwicklung2', 'type' => 'line', 'slot' => '1', 'alsoOnFull' => true,
		'title' => 'Entwicklung Einsätze/Funde', 'rows' => $EntEiFu_Data,
		'prep' => 'optionsX', 'countAxis' => 0, 'hTitle' => 'Jahr',
		'emptyMsg' => 'Keine Daten über die Einsatz- und Fundentwicklung.<br>',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Entwicklung1', 'type' => 'line', 'slot' => '2', 'alsoOnFull' => true,
		'title' => 'Entwicklung Felder/Fläche', 'rows' => $EntFeFl_Data,
		'prep' => 'optionsX', 'countAxis' => 0, 'hTitle' => 'Jahr',
		'emptyMsg' => 'Keine Daten über die Feld- und Flächenentwicklung.<br>',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'Kumulativ', 'type' => 'line', 'slot' => 'span', 'alsoOnFull' => true,
		'title' => 'Kumulativ seit 2020', 'rows' => $Kumulativ_Data,
		'prep' => 'optionsX', 'countAxis' => 0, 'hTitle' => 'Jahr',
		'vTitle' => 'Summe',
		'width' => 2 * $ChartWidth,
		'colors' => "['#e67e22','#2e7d32','#1565c0']",
		'emptyMsg' => 'Keine kumulierten Daten seit 2020.<br>',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'FelderProRegion', 'type' => 'column', 'slot' => '1',
		'title' => 'Alle Felder pro Region', 'rows' => $FeldReg_Data,
		'prep' => 'optionsX', 'hTitle' => 'Region',
		'tableRows' => AddTotalColumn($FeldReg_Data),
	));
	rkrk_emit_stat_chart(array(
		'name' => 'AufbauFelder', 'type' => 'line', 'slot' => '2',
		'title' => 'Felder Aufbau alle Regionen', 'rows' => $FeldStat_Data,
		'prep' => 'optionsX', 'countAxis' => 0, 'hTitle' => 'Jahr',
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsaetzeProTagBreit', 'type' => 'combo', 'slot' => 'span',
		'title' => 'Einsätze pro Tag', 'rows' => $Mission_Data, 'prep' => 'combo',
		'hTitle' => 'Tag', 'vTitle' => '[C,#]', 'width' => 2 * $ChartWidth,
		'table' => false,
		'wanted' => (is_array($Chartslist) && in_array('EinsaetzeProTagBreit', $Chartslist, true))
			|| ($Chartslist === 'all' && $PageFormat === 'full'),
	));
	rkrk_emit_stat_chart(array(
		'name' => 'EinsaetzeProTagSchmal', 'type' => 'combo', 'slot' => '1',
		'title' => 'Einsätze pro Tag', 'rows' => $Mission_Data, 'prep' => 'combo',
		'hTitle' => 'Tag', 'vTitle' => '[C,#]',
		'table' => false,
		'wanted' => (is_array($Chartslist) && in_array('EinsaetzeProTagSchmal', $Chartslist, true) && $Chartslist !== 'all')
			|| ($PageFormat === 'full'),
	));
	echo '</div>';

	if (rkrk_chart_wanted('Legende')) {
		//Legende
		echo "<table class=\"rkrk-stat-legend\">";
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
	if (rkrk_chart_wanted('Tabelle', true)) {

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
			echo '<div class="rkrk-stat-scroll">';
			echo "<table class=\"rkrk-stat-flights\">" .chr(10);
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
			echo '</div>';
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
<?php if (!empty($nlEmbed)) { ?>
<script>
(function () {
	var charts = document.querySelector('.rkrk-stat-charts');
	if (!charts) return;
	document.body.insertBefore(charts, document.body.firstChild);
	var nodes = document.body.children;
	for (var i = 0; i < nodes.length; i++) {
		var n = nodes[i];
		if (n === charts || n.tagName === 'SCRIPT' || n.tagName === 'STYLE') continue;
		n.style.display = 'none';
	}
	document.body.style.margin = '0';
	document.body.style.background = '#fff';
	charts.style.display = 'grid';
	charts.style.gridTemplateColumns = 'repeat(2, minmax(0, 1fr))';
	charts.style.maxWidth = 'none';
	charts.style.margin = '0';
	charts.style.width = '100%';
	charts.style.gap = '8px';
	var tables = charts.querySelectorAll('table');
	for (var t = 0; t < tables.length; t++) tables[t].style.display = 'none';
})();
</script>
<?php } ?>
</body>
</HTML>
