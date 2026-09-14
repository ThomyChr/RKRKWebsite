<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<!-- examples
https://ordinarycoders.com/blog/article/11-chart-js-examples
https://www.w3schools.com/js/js_graphics_google_chart.asp
-->
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
    include_once('scripts/rkrk_felddaten.php');
    include_once('scripts/rkrk_googlechart.php');
	include_once('scripts/rkrk_menu.php');
	//include_once('rkrk_access_control.php'); 
	
	//check impropriate IP addresses accessing page
	CheckIPandExit();

	$pagetitle = "Seitenstatiskik";
	headerpage($pagetitle , false);
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

  
	// ===========================================
	// Gobal Defintions
	// ===========================================

		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");
		$SiteArt = array("all","test","intern","extern", "pub");

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


SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

$EnableDeleteSet = "EnableDelete=off";

if (isset($_GET["Jahr"])) {
	$Jahr = $_GET["Jahr"];
} else	{
	$Jahr = date('Y');
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


if (isset($_GET["Format"])) {
	$PageFormat = $_GET["Format"];
} else	{
	$PageFormat = 'full';
}

if (isset($_GET["Charts"])) {
	$Chartslist = explode(",",$_GET["Charts"]);
	$PageFormat = 'selected';
} else	{
	$Chartslist = 'all';
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
		$Pilots[]=$Pilot[Opt];
	}
	$OptControlFlight = Array("", "Ja", "Nein");

	headerpage("Flugstatistik", false, $BaseColor);
	//GrantAccess();

///////////////////////////////////////
// Header page
///////////////////////////////////////

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

	function AddTotalColumn ($data) {
		foreach ($data as $linekey => $line) {
			if ($linekey !=0) {
				foreach ($line as $entrykey => $entry) {
					$data[$linekey]["Total"] += strval($entry);
					//echo $entry . " ";
				}
			} else {
				$data[$linekey]["Total"] = "Total";
			}
		}
		return $data;
	}
	
	
///////////////////////////////////////
// Load file
///////////////////////////////////////

	$csv_file= $RKRKSiteStatsCSV;
	
	//echo $logfilelocation.$RKRKSiteStatsCSV;

	$csv_deliminator = ";";

	//Read CSV file
	transferGoogleSheetToCSV();
	$SiteData = csv_to_array($logfilelocation.$csv_file, $csv_deliminator );
	//pre_r($SiteData);


	$fileloaded=true;
    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 12;
	if ($FontSize== "") {
		$FontSize= "120%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");
	//page

	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$i= 1;
	$DataSetFound = false;

// ===========================================
// HEADER FORM
// ===========================================

//removes what does not belong to tick list
$EnabledSiteArt = Array();
//Select all

	//form to change setting
	if (($PageFormat == 'full') and
		($Chartslist == 'all'))	{
		//echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
		echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
		$i= 0;
		$NoRegSelection = true;
		//echo $i . " " . $KMLFileSettings->name . chr(10)
		echo "<table width=100%>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><i>unsere Flugbuchungen</i><td>";
		// Home
		echo '<a href="rkrk.php"><img src="' . $IconLoc . '/home.png" height="' . $ButtonHeight .'" title="Home"></a>' .chr(10);
		echo "</td></tr>";

		$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
		$MinDate =date('Y-m-d', strtotime('-1 year'));
		$MinDate ="2021-01-01";
		$MaxDate =date('Y-m-d', strtotime('+1 year'));
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
		$FromSelDate = date("Y-m-d", strtotime("first Monday " . date('Y') . "-05-01"));
		$ToSelDate = date("Y-m-d", strtotime("first Monday " . date('Y') . "-06-15"));
		//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=off&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</a>  ";
		echo "<button  type='Aktuelle Saison' value='Aktuelle Saison' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Aktuelle Saison</button>".chr(10);
		// Saison wo es Statistik files hat
		foreach ($StatFiles as $File) {
			$FromSelDate = date("Y-m-d", strtotime("first Monday " . $File["Year"] . "-03-01"));
			$ToSelDate = date("Y-m-d", strtotime("first Monday " . $File["Year"]  . "-08-15"));
			//echo "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&EnableDelete=off&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison" . $File["Year"] . "</a>  ";
			echo "<button  type='Saison " . $File["Year"] ."' value='Saison " . $File["Year"] . "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $DebugWorkSet . "&SelFrom=".  $FromSelDate . "&SelTo=" . $ToSelDate . "' height='50px' target='_field'>Saison " . $File["Year"] ."</button>".chr(10);

		}
		echo "</td>";
		echo "<td rowspan=3>";
		//Not needed on this page
		//echo  MissionLibraryButtons("&SelFrom=".  $FromDate . "&SelTo=" . $ToDate,true);

		echo "</td>";
		echo "</tr>";
		echo "<tr><td>";
		echo "  Art: " .chr(10);
		echo "</td><td>";
		//echo SelectField("PilotenWhal", $PilotenWahl ,$Pilots);
			
		$NoSiteSelection = true;
		foreach ($SiteArt as $Art ) {
			if ((($_POST["SiteArt" . $i])=="on") or isset($_GET["ALL"])) {
				$checked = "checked";
				$EnabledSiteArt[$Art] = 1;
				$NoSiteSelection = false;
			} else {
				$checked = "";
				$EnabledSiteArt[$Art] = 0;
			}	
			echo '<input type="checkbox" name="SiteArt' . $i . '" ' . $checked . '>'. $Art. chr(10);
			$i++;
		}	
		echo '<br>'. chr(10);
		echo "</td><td>";
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
	}  // REDUCED VIEW

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


$JGs2Select = explode(",", $RegionSettings[$RegionSelected]["JGList"]);
foreach ($JGs2Select as $JGtoSelect)
{
  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
}

	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Site Statisik ","","","");
		foreach ($data as $Key => $Flug) {
			// selected to show
//			foreach ($Feldkatalog as $Key => $Region) {
//				if ($EnabledRegion[$Key]==1) {
			if (	(
						$PilotenWahl == ""
						or ($Flug["Pilot"] == $PilotenWahl)
						or ($PageFormat != 'full')
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
					  //($EnabledRegion[$Flug["JG"]]==1)
					  (in_array($Flug["JG"], $RegionsSelected))
 						or ($PageFormat != 'full')
					)
					and
					(
					  ($EnabledPilot[$Flug["Pilot"]]==1)
  						or ($PageFormat != 'full')
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

    include_once('scripts/rkrk_statdata.php');

// ========================================
// Process Statistics Data
// ========================================

// Felddaten 
if (!$NoSiteSelection) {

	//Adressdaten
	$csv_file= $RKRKContactFile;

/*
	//Read CSV file
	transferGoogleSheetToCSV();
	$Adresskatalog = csv_to_array($csvMissionLocation.$csv_file,',');
*/

	///////////////////////////////////
	// Wetterdaten und Verfügbarkeit
	///////////////////////////////////
	
	$MonatsListe = Array();
	$SiteStatistics = Array();
	foreach ($SiteData as $DataLine) {
		//if (
		//	($dataline["ListNr"]!="") 
		//	and ($dataline['Datum'] <= $Today)
		//	){
			// Visit pro Monat
			//$SiteStatistics['SiteProMonat'][$DataLine['Seite']]['Seite'] = $DataLine['Seite'];
			$SiteStatistics['SiteProMonat'][$DataLine['Seite']][$DataLine['Monat']] += $DataLine['Anzahl'];
			//$MonatsListe[$DataLine['Monat']] = $DataLine['Monat'];
			$MonatsListe[trim($DataLine['Monat'])] = trim($DataLine['Monat']);

			// Level pro Site
			//$SiteStatistics['SiteProLevel'][$DataLine['Level']][$DataLine['Seite']] = $DataLine['Level'];
			$SiteStatistics['SiteProLevel'][$DataLine['Level']][$DataLine['Seite']] += $DataLine['Anzahl'];
			$SiteListe[trim($DataLine['Seite'])] = trim($DataLine['Seite']);

			// pro Site
			$SiteStatistics['Sites'][$DataLine['Seite']]['Seite'] = $DataLine['Seite'];
			$SiteStatistics['Sites'][$DataLine['Seite']]['Anzahl'] += $DataLine['Anzahl'];
		//}
	}
	//pre_r($SiteStatistics['SiteProMonat']);
	//pre_r($SiteStatistics['SiteProLevel']);
	//pre_r($MonatsListe);
	//pre_r($SiteListe);

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

			// pro Site
//			$SiteStatistics['Sites'][$DataLine['Seite']]['Seite'] = $DataLine['Seite'];
//			$SiteStatistics['Sites'][$DataLine['Seite']]['Anzahl'] += $DataLine['Anzahl'];

	$Site_Data = array( 	GetDataFromStrArrayKey($SiteStatistics['Sites'], 'Seite'),
							GetDataFromStrArrayField($SiteStatistics['Sites'], 'Anzahl', 'Anzahl')
							);
	//pre_R($Site_Data);

	$SiteProMonat_Data[] = GetDataFromStrArrayKey($SiteStatistics['SiteProMonat'], 'Seite');
	foreach ($MonatsListe as $Monat) {
		$SiteProMonat_Data[] = GetDataFromStrArrayField($SiteStatistics['SiteProMonat'], $Monat, $Monat);
	}
	//pre_R($SiteProMonat_Data);

	$SiteProLevel_Data[] = GetDataFromStrArrayKey($SiteStatistics['SiteProLevel'], 'Level');
	foreach ($SiteListe as $Site) {
		$SiteProLevel_Data[] = GetDataFromStrArrayField($SiteStatistics['SiteProLevel'], $Site, $Site);
	}
	//pre_R($SiteProLevel_Data);

	////////////////////////////////
	// Charts
	////////////////////////////////

	if ($PageFormat == 'full') {
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


	if ((in_array("Sites", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($Site_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo "1";
			echo '<div id="Chart1" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Pilot_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(XTransform2dArray(FillEmptyEntries(GetChartOptions($Site_Data, false))));
			//pre_r($Site_Data);
			echo createBarChart('Chart1', 'Seiten Aufrufe' , $ChartDate, $BarColors, "", "[#]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray(XTransform2dArray(AddTotalColumn ($Site_Data)));
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
	}

	if ((in_array("Visit pro Monat", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($SiteProMonat_Data[1])>1) {
			echo $tableFieldOpen2;
			//echo "2";
			echo '<div id="Chart2" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Pilot_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(FillEmptyEntries(XTransform2dArray(GetChartOptions($SiteProMonat_Data, false))));
			echo createBarChart('Chart2', 'Seiten Aufrufe' , $ChartDate, $BarColors, "", "[#]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray(XTransform2dArray(AddTotalColumn ($SiteProMonat_Data)));
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose2;
		}
	}

	if ((in_array("Visit pro Site und Level", $Chartslist)) or 
		($Chartslist == 'all') ) {
		if (count($SiteProLevel_Data[1])>1) {
			echo $tableFieldOpen1;
			//echo "3";
			echo '<div id="Chart3" style="width:100%; max-width:'.$ChartWidth.'px; height:'.$ChartHeight.'px;"></div>';
			//$ChartDate = Collapse2dArray(GetChartOptionsWithColor($Pilot_Data, $linecolor, $fillcolor, true));
			$ChartDate = Collapse2dArray(FillEmptyEntries($SiteProLevel_Data, false));
			//pre_r($SiteProLevel_Data);
			echo createBarChart('Chart3', 'Seiten Aufrufe' , $ChartDate, $BarColors, "", "[#]",$ChartHeight,$ChartWidth);	
			echo GetTableFromArray(AddTotalColumn ($SiteProLevel_Data));
			echo "<br>";
			echo '<br>';
			echo $tableFieldClose1;
		}
	}

	echo '</tr>';
	echo '</table>';

} else {
	//NoSelection
	echo "<i>Bitte Jagdgebiet und Pilot selektieren und <Einlesen> drücken.</i>";
}


?>

</script>
</body>
</HTML>
