<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_flexdisplay.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('rkrk_access_control.php'); 

  
	//check impropriate IP addresses accessing page
	CheckIPandExit();

	$ImageHeight = 80;
	$ButtonHeightSmall =30;
	$PicSize=25;
	$Layer=true;	

// Example to call the site with options:
//https://tchr-frog.synology.me:5682/rkropen/test/test2023_flex_template_rkrk.php?Reg=21,25,28&Area=on&Poi=on&FFilter=28-029%2021-031#map


if (isset($_GET["Reg"])) {
	$JG = $_GET["Reg"];
} else	{
	$JG = '';
}

if (isset($_GET["GA"])) { 
	$Gast = $_GET["GA"];
	$OnlyGuest=true;
} else {
	$Gast="";
	$OnlyGuest=false;
}


if (isset($_POST["Mode"])) {
	$Mode = $_POST["Mode"];
	$ModeSet = "&Mode=".$_POST["Mode"];
} elseif (isset($_GET["Mode"])) { 
	$Mode = $_GET["Mode"];
	$ModeSet = "&Mode=".$_GET["Mode"];
} else	{
	$Mode = 'J';
	$ModeSet = "&Mode=J";
}

if (empty($_GET["MenuOn"])) {
	$MenuOn = true;
} else {
	if (htmlspecialchars($_GET["MenuOn"])=="off") {
		$MenuOn = false;
	} else {
		$MenuOn = true;
	}
}


if ($Mode == "J")
	$Titel= " Jäger";
else
	$Titel= " Piloten";

	$pagetitle = "Fluglisten" . $Titel;
	headerpage("Fluglisten" . $Titel, false);
	GrantAccess();
if ($MenuOn) {
	CreateTopMenu();
}
	//PrintRKRKHeader($pagetitle);
		//WARNUNG!!
		//echo "<span style='background-color: #FDEDEC;'>". chr(10);
		//echo "<i><Funktion nicht freigegeben! Nur zum Testen. Freigabeprozesse gegenüber den Jägern müsste noch gelöst werden, bevor einsetztbar!</i><br>". chr(10);
		//echo "</span>". chr(10);

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	//pre_r($Feldkatalog);
	$FeldkatalogList = GetFeldKatalogList ($Feldkatalog);
	//pre_r($FeldkatalogList);
	
	
	//Read CSV file
	$csv_file= $RKRKContactFile;
	transferGoogleSheetToCSV();
	$Adresskatalog = csv_to_array($csvMissionLocation.$csv_file,';');


	foreach ($Adresskatalog as $Person) {
		$AdressDataArray[$Person['Initialen']]['Initialen']= $Person['Initialen'];
		$AdressDataArray[$Person['Initialen']]['Name']= $Person['Name'];
		$AdressDataArray[$Person['Initialen']]['Kontaktnummer']= $Person['Kontaktnummer'];
		$AdressDataArray[$Person['Initialen']]['Text']= $Person['JG'] . " " . $Person['Name'] . " " . $Person['Kontaktnummer'];
	}
	//pre_R($AdressDataArray);
	//pre_R($Adresskatalog);

	foreach ($RegionSettings as $Reg) {
		$RegDataArray[$Reg['Reg']]['Reg']= $Reg['Reg'];
		$RegDataArray[$Reg['Reg']]['RegUp']= $Reg['RegUp'];
		foreach ($Adresskatalog as $Person) {
			if ((strpos(" " . $Person['Jagdgebiet'], strval($Reg['Reg'])) !== false) and
				(strpos(" " . $Person['Jagdgebiet'], "(". strval($Reg['Reg']) .")" ) == false)) {
				$PilotDataArray[$Reg['Reg']]['Reg']=$Reg['Reg'];
				$PilotDataArray[$Reg['Reg']]['Pilot']=$Person['Initialen'];
			}
		}
	}
	//pre_R($RegDataArray);
	//pre_R($PilotDataArray);
	
	// ===========================================
	// Gobal Defintions
	// ===========================================

		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");
		$TableType= "Table"; //Table, Flex
		
		$ScreenTableSetup= $ScreenTableSetupSettings[$TableType];
	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
		$debugmode = false;

	// ===========================================
	// Paramters
	// ===========================================

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

if (isset($_GET["SelFrom"])) { 
	$FromDate = $_GET["SelFrom"];
} elseif (isset($_POST["SelFrom"])) { 
	$FromDate = $_POST["SelFrom"];
} else {
	$FromDate =date('Y-m-d');
	//For Debugging
	//$FromDate ="2023-05-03";
}

if (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
} else if (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} else {
	$ToDate =date('Y-m-d', strtotime('+1 week'));
	//For Debugging
	//$ToDate ="2023-05-10";
}
$Today = date("Y-m-d");

if (isset($_POST["FeldNamenWahl"])) {
	$FeldNamenWahl = $_POST["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

if (isset($_POST["Pilot"])) {
	$PilotenWahl = $_POST["Pilot"];
} elseif (isset($_GET["Pilot"])) { 
	$PilotenWahl = $_GET["Pilot"];
} else	{
	$PilotenWahl = '';
}
$PilotenWahlSet = "Pilot=".$PilotenWahl;

if (isset($_POST["Jaeger"])) {
	$JaegerWahl = $_POST["Jaeger"];
} elseif (isset($_GET["Jaeger"])) { 
	$JaegerWahl = $_GET["Jaeger"];
} else	{
	$JaegerWahl = '';
}

$JaegerWahlSet  = "Jaeger=".$JaegerWahl;
$JagerWahlSet = $JaegerWahlSet;
if (!isset($EnableDeleteSet)) {
	$EnableDeleteSet = "";
}

//echo "FromDate: " . $FromDate . " bis ToDate: " . $ToDate . "<br>";

//Select Options
//$Pilots[]= "";
//foreach ($OptionPilot as $Pilot) {
//	$Pilots[]=$Pilot[Opt];
//}

$OptControlFlight = Array("", "Ja", "Nein");


	// ===========================================
	// Variables
	// ===========================================

	$InputParamterSettings = Array(
				"Options" => Array(
					"DBW" => Array(
							"Text" => "Select Debug Database",
							"Icon" => "",
							"Value" => false,
							"Options" => "ON,OFF"
						),	
					"SelFrom" => Array(
							"Text" => "Feld",
							"Icon" => "button_feld_off.png",
							"Value" => date('Y-m-d'),
							"Options" => "on,off"
						),	
					"SelTo" => Array(
							"Text" => "Flugpfad",
							"Icon" => "button_pfad_off.png",
							"Value" => date('Y-m-d', strtotime('+1 week')),
							"Options" => "on,off"
						),	
					),
				);


Function CreateOutputLine($dataline, $TableSetup, $line, $MaxLine, $Type="data") {
	global  $ScreenTableSetup,
			$FeldkatalogList, 
			$AdressDataArray, 
			$RKRKLocalLoactionMissionFiles, 
			$ImageHeight,
			$OptionFrei,
			$OptionWarning,
			$IconLoc,
			$PicSize,
			$OnlyGuest,
			$ManualFieldLabel,
			$OptionGemeinde;
			
	while ($line <= $MaxLine) {
		//echo $line . " " . $dataline["Datum"]. " " . $dataline["Pilot"]. " " . $dataline["Feld"] . "<br>";
		echo $ScreenTableSetup["<tr>"] .chr(10);
		if (!$OnlyGuest) {
			 //öffentliche Flugliste
		echo $ScreenTableSetup["<td>"] .chr(10);
		//echo "" . $line . "<br>" .chr(10);
		if ($dataline['Status']=='locked') {
			//if (!$OnlyGuest) {
				echo '<img id="planung"  src="' . $IconLoc . '/inplanung_left.png" title="Flug noch in Planung, bitte auf Freigabe warten!">' . chr(10);
			//} else {
			//	echo '<img id="planung"  src="' . $IconLoc . '/inplanung_left_small.png" title="Flug noch in Planung, bitte auf Freigabe warten!">' . chr(10);
			//}
		}
		echo "<br>" .chr(10);
		if (!$OnlyGuest) {
			if ($dataline["Zeit"]!="") {
				echo "<b>" . $dataline["Zeit"] . " h</b><br><br>" .chr(10);
			} else {
				echo " __:__ h<br><br>" .chr(10);
			}
		} else {
			if ($dataline["Zeit"]=="") {
				echo "<i>Zeit offen</i><br><br>" .chr(10);
			} else {
				echo "<i>eingeplant</i><br><br>" .chr(10);
			}
		}
		echo "Pilot:  <b>" . $AdressDataArray[$dataline["Pilot"]]["Name"] . "</b>". "<br>" . chr(10);
		echo "Jäger:  <b>" . $AdressDataArray[$dataline["Jaeger"]]["Name"] . "</b>" .chr(10);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		if ($dataline["Feld"]!=$ManualFieldLabel) {
			//echo "<i>Suchfeld:</i><br>" . chr(10);
			$SuchfeldLink = $FeldkatalogList[$dataline["Feld"]]["SuchfeldBild"];
			$SuchfeldBild = explode(",", $SuchfeldLink);
			echo "<a href='" . $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]. "'><img src='" . $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]. "' id='thumpnail' height='" . $ImageHeight ."' width='" . $ImageHeight ."' onmouseover='Large(this)'></a>";
		}
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		//echo "<i>FlugplanBild:</i><br>" . chr(10);
		if ($dataline["Feld"]!=$ManualFieldLabel) {
			$FlugplanBilddLink = $FeldkatalogList[$dataline["Feld"]]["FlugplanBild"];
			$FlugplanBild = explode(",", $FlugplanBilddLink);
			$SelectedFlugplanBild = GetFlugplanbild($FlugplanBild,$FeldkatalogList[$dataline["Feld"]]["StdFlughohe"]);
			
			echo "<a href='" . $RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild. "'><img src='" . $RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild. "' id='thumpnail' height='" . $ImageHeight ."' width='" . $ImageHeight ."' onmouseover='Large(this)'></a>";
		}
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		
		$FeldName = "" . $dataline["Feld"] . " ";
		$FeldName .=  $FeldkatalogList[$dataline["Feld"]]["Feldname"];
		if ($FeldkatalogList[$dataline["Feld"]]["Flurname"]!="")
			$FeldName .= " - " . $FeldkatalogList[$dataline["Feld"]]["Flurname"];
		if ($FeldkatalogList[$dataline["Feld"]]["GrundstueckID"]!="")
			$FeldName .= " (" . $FeldkatalogList[$dataline["Feld"]]["GrundstueckID"] . ")";
		echo "<a href='rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$dataline["Feld"]."'>";
		echo $FeldName . "</a>";
		if ($dataline["Feld"]!=$ManualFieldLabel) {
			echo "<br><br>";
		} else {
			echo "<br>".$dataline["Felder"]."<br>";
		}
		echo "JG:  " .$dataline["JG"] .chr(10);
		echo ", Gde:  " .$dataline["Gde"] . "<br>".chr(10);
		foreach ($OptionFrei  as $key => $Frei) {
			if (like($FeldkatalogList[$dataline["Feld"]]["Freigabe"], $key)) {
				echo  " <img src='" . $IconLoc . $Frei["Pic"] . "' title='" . $Frei["Text"] . "' height='" . $PicSize ."'>" . chr(10); 
			}
		}
		echo "  ".chr(10);
		foreach ($OptionWarning as $key => $Warning) {
			if (like($dataline["Warnung"], $key)) {
				echo  " <img src='" . $IconLoc . $Warning["Pic"] . "' title='" . $Warning["Text"] . "' height='" . $PicSize ."'>" . chr(10); 
			}
		}
		//echo $dataline["Warnung"] . "<br>".chr(10);
		echo "<br>";
		//echo "Geflogen:  " .$dataline["Geflogen"] . "". "<br>".chr(10);
		//echo "<br>";
		//echo "Geflogen: ".chr(10);
		if ($dataline["Geflogen"]=="Ja")
				echo  " <img src='" . $IconLoc . "/allok.png' title='Geflogen' height='" . $PicSize ."'>" ; 
		//echo "Funde:  " .$dataline["#Funde"] . "" . "<br>".chr(10);
		//echo "Funde: " .chr(10);
		for ($i = 1; $i <= $dataline["#Funde"]; $i++) {
				echo  " <img src='" . $IconLoc . "/reh.png' title='Kitzfund' height='" . $PicSize ."'>" ; 
		} 
		echo $ScreenTableSetup["</td>"] .chr(10);
		//foreach ($TableSetup AS $TableEntry) {
			//echo $i ." . " .$TableEntry['Field']  . " - " . $dataline[$TableEntry["Field"]] . "<br>";
			//CreateOutputField($dataline, $TableEntry, $line, $Type);
		//}
		}  else {
			 //öffentliche Flugliste
			echo $ScreenTableSetup["<td>"] .chr(10);
			if ($dataline["Geflogen"]=="") {
				if ($dataline["Zeit"]=="") {
					echo "<i>in planung</i>" .chr(10);
				} else {
					echo "<i>eingeplant</i>" .chr(10);
				}
			} elseif ($dataline["Geflogen"]=="Ja") {
				echo "<i>geflogen</i>" .chr(10);
			} else {
				echo "<i><b>nicht</b> geflogen</i>" .chr(10);
			}	
			echo $ScreenTableSetup["</td>"] .chr(10);
			echo $ScreenTableSetup["<td>"] .chr(10);
			$FeldName = "" . $dataline["Feld"] . " ";
			$FeldName .=  $FeldkatalogList[$dataline["Feld"]]["Feldname"];
				if ($FeldkatalogList[$dataline["Feld"]]["Flurname"]!="")
				$FeldName .= " - " . $FeldkatalogList[$dataline["Feld"]]["Flurname"];
			if ($FeldkatalogList[$dataline["Feld"]]["GrundstueckID"]!="")
				$FeldName .= " (" . $FeldkatalogList[$dataline["Feld"]]["GrundstueckID"] . ")";
			echo "<b>".$FeldName . "</b>";
			echo $ScreenTableSetup["</td>"] .chr(10);
			echo $ScreenTableSetup["<td>"] .chr(10);
			echo $AdressDataArray[$dataline["Pilot"]]["Name"] . chr(10);
			echo $ScreenTableSetup["</td>"] .chr(10);
			echo $ScreenTableSetup["<td>"] .chr(10);
			echo $OptionGemeinde[$dataline["Gde"]]["Text"]. chr(10);
			echo $ScreenTableSetup["</td>"] .chr(10);
			echo $ScreenTableSetup["<td>"] .chr(10);
			echo $dataline["Flaeche"] . " ha". chr(10);
			echo $ScreenTableSetup["</td>"] .chr(10);
			echo $ScreenTableSetup["<td>"] .chr(10);
			if (($dataline["Geflogen"]!="") and ($dataline["Fund"]!="")) {
				echo $dataline["Fund"] .  "Rehkitz(e)" . chr(10);
			}
			echo $ScreenTableSetup["</td>"] .chr(10);
		}	
		$line++;
		echo $ScreenTableSetup["</tr>"] .chr(10);
	}
}


Function CreateTitleField($TableEntry) {
	global $ScreenTableSetup,$OnlyGuest;
	if (!$OnlyGuest) {
		//interne Liste
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Fluginformation" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Suchfeld" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		if (!$OnlyGuest) {
			echo "Flugplan" .chr(10);
		}
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Feldinformation" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
	} else {
		//öffentliche Liste
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Status" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Feld" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Pilot" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Gemeinde" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Fläche" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Funde" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
	}	
}



Function CreateTitleLine($TableSetup) {
	global $ScreenTableSetup;
	echo $ScreenTableSetup["<tr>"] .chr(10);
	//echo "Title" . "<br>";
	//foreach ($TableSetup AS $TableEntry) {
	CreateTitleField(array());
	//}
	echo $ScreenTableSetup["</tr>"] .chr(10);
}



// Update Flightdata
$Year = date('Y', strtotime($FromDate));
UpdateFlightsAndSaveKatalog(strval($Year));

///////////////////////////////////////
// Load file
///////////////////////////////////////

	$csv_file= date('Y', strtotime($FromDate)) .$RKRKFlightsCSV;
	$csv_deliminator = ";";

	//Read CSV file
	$data = csv_to_array($csvMissionLocation.$csv_file, $csv_deliminator );
	$MaxFlugID = 0;
	$data = FormatFlugDataArray($data);
	// change key to FlugID
	// echo "MaxID: " . $MaxFlugID . "<br>"; 
	// pre_r($data);
	// echo "FromDate: " . $FromDate . " bis ToDate: " . $ToDate . "<br>";
	// echo "JG: ". $JG . "<br>";
	
	//count flights in daterange
	$FlightCount=0;
	foreach ($data as $flight) {
		if ((strtotime($flight['Datum'])>=strtotime($FromDate))
			and (strtotime($flight['Datum'])<=strtotime($ToDate))
			and (strpos(" ". $JG, $flight['JG'])) or ($JG=="")) {
			$FlightCount++;
		}
	}



	//read file (again after writing to file
	//echo $csvMissionLocation.$csv_file;
	transferGoogleSheetToCSV($csvMissionLocation.$csv_file);
	$data = csv_to_array($csvMissionLocation.$csv_file,';');
	$MaxFlugID = 0;
	$data = FormatFlugDataArray($data);
	if ($Mode=="J") {
		array_multisort(array_column($data, 'Datum'), SORT_NUMERIC, SORT_ASC,
						array_column($data, 'Zeit'), SORT_NUMERIC, SORT_ASC,	
						array_column($data, 'Jaeger'), SORT_ASC, SORT_STRING,
						$data);	
	} else {
		array_multisort(array_column($data, 'Datum'), SORT_NUMERIC, SORT_ASC,
						array_column($data, 'Zeit'), SORT_NUMERIC, SORT_ASC,	
						array_column($data, 'Pilot'), SORT_ASC, SORT_STRING,
						$data);	
	}

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
	// Page control disabled
	if (isset($_POST["AnzLinesPPage"])) {
		if ($_POST["AnzLinesPPage"]=="all") {
			$AnzLinesPPage= 999;
		} else {
			$AnzLinesPPage= $_POST["AnzLinesPPage"];
		}
	} else {
		$AnzLinesPPage= 25;
	}
	if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
		$ActPage= $_GET["ActPage"];
	} else {
		$ActPage= 1;
	}
	if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
		$FontSize= $_POST["FontSize"];
	} else {
		$FontSize= "100%";
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung

	if ($FontSize== "") {
		$FontSize= "100%";
	}	
	
	SetTableWidth("100%");
	//page

	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$i= 1;
	$DataSetFound = false;

// ===========================================
// HEADER FORM
// ===========================================
?>
<style>
#planung 
{
    vertical-align : top;
    position : absolute;
    background : transparent; 
    filter: Alpha(Opacity=100);
    overflow : hidden;
    height : 70px;
    width : 350px;
    z-index : 5;
}

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

#imgbox 
{
    vertical-align : middle;
    position : absolute;
    border: 1px solid #999;
    background : #FFFFFF; 
    filter: Alpha(Opacity=100);
    visibility : hidden;
	height : 600px;
    width : 600px;
    z-index : 2;
    overflow : hidden;
    text-align : center;
	border-radius: 2%;
}

#thumpnail 
{
    height : 100px;
    width : 100px;
	border-radius: 5%;
}
</style>

<?php

$EnabledRegion = Array();
//form to change setting
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
/*
if ($Layout == "2023") {
	echo "  <div class='container content center padding-32' style='max-width:1500px' id='choice'>" .chr(10);
	echo "    <h2 class='wide'>Fluglisten".  $Titel."</h2>" .chr(10);
	echo "    <p class='opacity'><i>unsere Flugbuchungen</i></p>" .chr(10);
	echo "  </div>" .chr(10);
	echo "  <div class='container content left padding-32' style='max-width:1500px' id='choice'>" .chr(10);
} else {
	//echo "Flightcount: " . $FlightCount . "</br>" . chr(10);
	echo "    <h2 id='Titel' name='Titel' class='wide'>Flugpläne</i></h2>" .chr(10);
	//echo "Flightcount: " . $FlightCount . "</br>" . chr(10);
	
}
*/
if (($FlightCount==0) and ($Gast == 'gast'))
	echo "<i>Die nächsten Tage sind keine Flüge geplant.</i>".chr(10);

//hide if no flight
if (($FlightCount==0) and ($Gast == 'gast'))
  echo "<span hidden>";
else
  echo "<span>";
	
echo "  <div class='container content center padding-32' style='max-width:1500px' id='choice'>" .chr(10);
echo "  </div>" .chr(10);
$i= 0;
$NoSelection = true;
if ($MenuOn) {
	echo "<span  id='menu'>" .chr(10);
} else {
/*
	//echo date('d.m.Y',strtotime($FromDate)) . " bis " . date('d.m.Y',strtotime($ToDate)) ."<br>";
	$datediff = round((strtotime($FromDate)- strtotime(date("Y-m-d")))/ (60 * 60 * 24)) . "  ";
	switch ($datediff) {
    case 0:
        echo "Heute, ";
        break;
    case 1:
        echo "Morgen, ";
        break;
    case -1:
        echo "Gestern, ";
        break;
	}
	echo date('d.m.Y',strtotime($FromDate)) . "<br>";
*/
	echo "<span hidden id='menu'>" .chr(10);
}

//echo $i . " " . $KMLFileSettings->name . chr(10)
	if ($Layout!=2023) {
		echo CreateSiteLink ("EditFeld", "");
		echo CreateSiteLink ("EditFeldliste", "");
		echo CreateSiteLink ("Fluege", "");
		echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
		echo CreateSiteLink ("FlugflaechenKarte", "SelFrom=" . $FromDate . "&SelTo=" . $ToDate);
		echo CreateSiteLink ("Home", "");
	}

	echo "<table width=100%>";

		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Edit Fluege ", $FeldID ?? '', $RKRKMissionSelected,"");

	echo "<tr><td>";
	echo "Regionen: ";
	echo "</td><td colspan=1>";
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
	// JG/Region selection
	$RegionsSelected = array();
	foreach (explode(",", (string)$ListOfRegions) as $Reg) {
		if ($Reg === '' || !isset($RegionSettings[$Reg]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$Reg]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
			$RegionsSelected[$JGtoSelect] = $JGtoSelect;
			$RegOptions[$JGtoSelect]["Value"]=1;
			//$EnabledOptions["Regions"]["List"][$JGtoSelect] =$JGtoSelect;
		}
	}
	$Reg = implode(" ", $RegionsSelected);
	$RegOption = "Reg= ". $Reg;
	echo '<br>'. chr(10);
	$i++;
	echo "</td>";
/*
	if ($Layout !== "2023") {
		$logo= "<td valign='top' align='right'  rowspan=1>";
		$logo.= "IG ". chr(34) ."Rehktitzrettung Knonaueramt und Zug". chr(34) ."<br><a href='http://www.drohnenrehkitzrettung.ch' target='blank'>www.drohnenrehkitzrettung.ch</a>";
		$logo.= "   </td>". chr(10);
		$logo.= "   <td width='80' align='right' valign='top' rowspan=1>";
		$logo.= "<img src='" . $IconLoc . "/202121 Logo-RKRKZ.png' height='80px'>". chr(10);
		$logo.= "</td>". chr(10);
		echo $logo;
	}
*/
	
	if ($fileloaded=true) {
		$Pilots[]= "";
		$PilotLegend ="";
		if ($PilotenWahl!="") {
			$Pilots[$PilotenWahl]= $PilotenWahl;
		}
		$Jaeger[]= "";
		if ($JaegerWahl!="") {
			$Jaeger[$JaegerWahl]= $JaegerWahl;
		}
		foreach ($data as $Key => $Flug) {
			// selected to show
			if (	
					
					((
						$JaegerWahl == ""
						or ($Flug["Jaeger"] == $JaegerWahl)
					)
						and
					(
						$PilotenWahl == ""
						or ($Flug["Pilot"] == $PilotenWahl)
					))
						and
					(  
						$FeldNamenWahl == "" 
						or like($Flug["FeldID"], strval($FeldNamenWahl))
						or (strpos($FeldNamenWahl, strval($Flug["FeldID"]))!== false)
						or like($Flug["Pilot"], $FeldNamenWahl)
						or like($Flug["Kontaktnummer"], $FeldNamenWahl)
						or like($Flug["Jaeger"], $FeldNamenWahl)
						or like($Flug["Bemerkung"], $FeldNamenWahl)
						or like($Flug["Felder"], $FeldNamenWahl)
						or like($Flug["Landwirt"], $FeldNamenWahl)
						or like(($AdressDataArray[$Flug["Pilot"]]["Name"] ?? ''), $FeldNamenWahl)
						or like(($AdressDataArray[$Flug["Jaeger"]]["Name"] ?? ''), $FeldNamenWahl)
					) 
					and 
					(
						(in_array($Flug["JG"], $EnabledOptions["Regions"]["List"])
						or 	(in_array("alle", $EnabledOptions["Regions"]["List"])))
					)
					and
					(
						($Flug["Datum"] >= $FromDate) and
						($Flug["Datum"] <= $ToDate)
					)
					and
					// Ausblenden von Zukünftigen Flügen mit Geflogen = Nein
					(
						($Flug["Datum"] <= $Today) or
						($Flug["Geflogen"] != "Nein")
					)
				) 
			{
				$data[$Flug["FlugID"]]["ListNr"]= $i;
				$Jaeger[$data[$Flug["FlugID"]]["Jaeger"]]=$data[$Flug["FlugID"]]["Jaeger"];
				$Pilots[$data[$Flug["FlugID"]]["Pilot"]]=$data[$Flug["FlugID"]]["Pilot"];
				$i++;
			} else {
				$data[$Flug["FlugID"]]["ListNr"]= "";
			}
			//pre_r($Flug);
		}
		$MaxSelectedEntries = $i;
	}
	//pre_r($data);


	$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
	$MinDate =date('Y-m-d', strtotime('-1 year'));
	$MinDate ="2021-01-01";
	$MaxDate =date('Y-m-d', strtotime('+1 year'));
	
	echo "<tr><td>";
	echo "  Datum: " .chr(10);
	echo "</td><td>";
	echo DateJSFunctionField("SelFrom", $FromDate, $MinDate , $MaxDate ,"UpdSelDatum", "this.value", $i, 4 );
	echo " - " ;
	echo DateField("SelTo", $ToDate, $MinDate , $MaxDate);
	echo "</td>";
	echo "<td>";
	echo "<input type='image' value='minus1woche' src='" . $IconLoc . "/minus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" .  $JagerWahlSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet ."&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 week')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='minus1tag' src='" . $IconLoc . "/minus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet . "&SelFrom=".  date('Y-m-d', strtotime($FromDate. '-1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '-1 day')) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='plus1tag' src='" . $IconLoc . "/plus1tag.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet . "&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 day')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Tag auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='plus1woche' src='" . $IconLoc . "/plus1woche.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet . "&SelFrom=".  date('Y-m-d', strtotime($FromDate. '+1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate. '+1 week')) . "' height='" . $ButtonHeight . "'  title='Datum plus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "</td>";
	echo "<td rowspan=3>";
	echo CreateSiteLink ("PdfFlugliste", "FromDate=".  $FromDate . "&ToDate=" . $ToDate. "&Jaeger=" . $JaegerWahl . "&Pilot=" . $PilotenWahl);
	echo "<br>";
	echo "<br>";
	//Tabellen Modus
	//QR code anzeigen
	if (GetAccess ("PilotPartners")) { 
		echo  "<span id='qrswop1'>swop</span>";
	}
	//echo  MissionLibraryButtons("&" . $ModeSet . "&" . $JagerWahlSet . "&". $PilotenWahlSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate,true);
	echo  MissionLibraryButtons("&" . $RegOption . "&" . $ModeSet . "&" . $JagerWahlSet . "&". $PilotenWahlSet ."&SelFrom=".  $FromDate . "&SelTo=" . $ToDate,true);

	echo "</td>";
	echo "</tr>";
	echo "<tr><td>";
	echo "  Suche: " .chr(10);
	echo "</td><td>";
	echo InputField("FeldNamenWahl", $FeldNamenWahl, 20);
	echo "</td><td>";
	echo "<input type='image' value='heute' src='" . $IconLoc . "/heute.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet ."&SelFrom=".  $Today . "&SelTo=" . $Today . "' height='" . $ButtonHeight . "'  title='Datum Heute auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='morgen' src='" . $IconLoc . "/morgen.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet ."&SelFrom=".  $Tomorrow  . "&SelTo=" . $Tomorrow . "' height='" . $ButtonHeight . "'  title='Datum Morgen auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "<input type='image' value='saison' src='" . $IconLoc . "/saison.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . $ModeSet . "&" . $JagerWahlSet . "&" . $PilotenWahlSet ."&SelFrom=".  GetSasionStartDate(date('Y', strtotime($FromDate))) . "&SelTo=" . GetSasionEndDate(date('Y', strtotime($FromDate))) . "' height='" . $ButtonHeight . "'  title='Datum minus 1 Woche auswählen'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "</td></tr>";
	echo "<tr><td>";
	echo "  Pilot: " . "<br>" .chr(10);
	echo "  Jäger: " .chr(10);
	echo "</td><td>";
	echo SelectField("Pilot", $PilotenWahl ,$Pilots) . chr(10);
	$Legend= "";
	foreach ($Pilots as $key => $entry) {
		if ($key !== "" && isset($AdressDataArray[$key]['Initialen'])) {
			$Legend .= $AdressDataArray[$key]['Initialen'] . ": ";
			$Legend .=  ($AdressDataArray[$key]['Name'] ?? '') . ", " ;
		}
	}
	echo "<i> - " .  substr($Legend,0, -2) . "</i>";
	echo "<br>";
	echo SelectField("Jaeger", $JaegerWahl ,$Jaeger) . chr(10);
	$Legend= "";
	foreach ($Jaeger as $key => $entry) {
		if ($key !== "" && isset($AdressDataArray[$key]['Initialen'])) {
			$Legend .=  $AdressDataArray[$key]['Initialen'] . ": ";
			$Legend .=  ($AdressDataArray[$key]['Name'] ?? '') . ", " ;
		}
	}
	echo "<i> - " . substr($Legend,0, -2) . "</i>";
	echo "</td><td>";
	if ($Mode=="J") {
		//Jaeger
		echo "<input type='image' value='Mode' src='" . $IconLoc . "/jaeger_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . "&Mode=J&SelFrom=". $FromDate .  "&SelTo=" .  $ToDate.  "' height='" . $ButtonHeight . "'  title='Sicht Jäger'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		echo "<input type='image' value='Mode' src='" . $IconLoc . "/pilot_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . "&Mode=P&SelFrom=". $FromDate . "&SelTo=" .  $ToDate.  "' height='" . $ButtonHeight . "'  title='Sicht Pilot'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	} else {
		//Pilot
		echo "<input type='image' value='Mode' src='" . $IconLoc . "/jaeger_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . "&Mode=J&SelFrom=". $FromDate . "&SelTo=" .  $ToDate.  "' height='" . $ButtonHeight . "'  title='Sicht Jäger'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		echo "<input type='image' value='Mode' src='" . $IconLoc . "/pilot_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $RegOption . "&" .$MissionSelectionParameter . "&Mode=P&SelFrom=". $FromDate . "&SelTo=" .  $ToDate.  "' height='" . $ButtonHeight . "'  title='Sicht Pilot'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	}
	echo "</td></tr>";
	echo "</table  width=100%>";
	echo "<input type='submit' name='Einlesen' value='Einlesen' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet . $ModeSet . "'> ". chr(10);
	echo "</span>" .chr(10);

	echo "<br>";

	echo '<span z-index:0;>';
	$i=1;
	// page control disabled
	//$pageline = 1;
	//$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	//$showfrom = ($ActPage-1)*$AnzLinesPPage - 1;
	//$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	//echo "AnzLinesPPage: " .$AnzLinesPPage . "<br>";
	//echo "Show: " .$showfrom . " - " . $showto ."<br>";

	echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	echo "<br>" . chr(10);
	// page control disabled
	//if ($TableType=="Table") {
	//	AlternateLineTable($colorTableEven, $FontSize);
	//	echo $ScreenTableSetup["<div>"].chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false);
	//	echo $ScreenTableSetup["</div>"].chr(10);
	//}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo ($ScreenTableSetup["<table line>"] ?? '');
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);

	// page control disabled
	//if ($TableType=="Flex") {
	//	echo $ScreenTableSetup["<tr>"] .chr(10);
	//	echo $ScreenTableSetup["<td line>"] .chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
	//	echo $ScreenTableSetup["</td>"] .chr(10);
	//	echo $ScreenTableSetup["</tr>"] .chr(10);
	//}
	//echo $ScreenTableSetup["</table>"] .chr(10);

	
	//form to change setting
	echo "<small>";
	echo $ScreenTableSetup["<table>"] .chr(10);
	CreateTitleLine(array());

	// Extisting Entries
	$PrevPilot ="";
	$PrevJaeger ="";
	$ActDate =$FromDate;
	$PrevDate =$Tomorrow;
//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?FFilter=25-048+25-049+21-001+21-004+21-007&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL2 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&" . $MissionSelectionParameter;
	foreach ($data as $dataline) {
		//pre_R($dataline);
		if ($dataline["ListNr"]!="" &&
			true
			//Page control disabled
			//$dataline["ListNr"]>=$showfrom && 
			//$dataline["ListNr"]<=$showto
			){
			// Separator
			if (
				(($Mode=="P") and
				(($PrevPilot !=$dataline['Pilot'])) or
				($PrevDate !=$dataline['Datum']))
				or
				(($Mode=="J") and
				(($PrevJaeger !=$dataline['Jaeger'])) or
				($PrevDate !=$dataline['Datum']))
				)			
			{
				if ($PrevDate=="") {
					$defaultvalues["Datum"] = $Tomorrow;
				} else {
					$defaultvalues["Datum"] = $PrevDate;
				}
				//$ActPilot = $dataline['Pilot'];
				$DayFieldList = array();
				$MaxEntry = $i+1;
				$PilotDayFieldList =array();
				$JaegerDayFieldList =array();
				$defaultvalues["Pilot"] = $PrevPilot;
				$defaultvalues["Jaeger"] = $PrevJaeger;
				$NewLines= true;	
				//get link to map
				foreach ($data as $dl) {
					if (($dl['Datum']==$dataline['Datum'])
						and
						// Ausblenden von Zukünftigen Flügen mit Geflogen = Nein
						(
							($dl["Datum"] <= $Today) or
							($dl["Geflogen"] != "Nein")
						))						
						{
						$DayFieldList[] =$dl['Feld'];
						$c[] =$dl['Feld'];
						if ($dl['Pilot']==$dataline['Pilot'] ) {
							$PilotDayFieldList[] =$dl['Feld'];
						}
						if ($dl['Jaeger']==$dataline['Jaeger'] ) {
							$JaegerDayFieldList[] =$dl['Feld'];
						}
					}
				}
				//pre_R($JaegerDayFieldList);
				$PilotDayFieldList = implode('+', $PilotDayFieldList);
				//echo "PI:" .$PilotDayFieldList;
				$JaegerDayFieldList = implode('+', $JaegerDayFieldList);
				$DayFieldList = implode('+', $DayFieldList);
				//echo "JD:" .$JaegerDayFieldList;
				//echo "<tr><td colspan='10'>";
				echo $ScreenTableSetup["<tr>"] . chr(10);
				echo $ScreenTableSetup["<td>"] . chr(10);
				if (!$OnlyGuest) {
					if ($Mode=="J") {
						//Jäger
						echo "<b><i>" . $dataline['Datum'] . "/ " . $dataline['Jaeger'] . "</i></b>". chr(10);
					} else	{
						//Pilot
						echo "<b><i>" . $dataline['Datum'] . "/ " . $dataline['Pilot'] . "</i></b>". chr(10);
					}
				} else {
					if ($Mode=="J") {
						//Jäger
						 
						echo "<b><i>" . $dataline['Datum'] . "/ " . $AdressDataArray[$dataline["Jaeger"]]["Name"] . "</i></b>". chr(10);
					} else	{
						//Pilot
						echo "<b><i>" . $dataline['Datum'] . "/ " . $AdressDataArray[$dataline["Pilot"]]["Name"] . "</i></b>". chr(10);
					}
				}	
				echo $ScreenTableSetup["</td>"] . chr(10);
				echo $ScreenTableSetup["<td line>"] . chr(10);
				if (!$OnlyGuest) {
					if ($Mode=="J") {
						//Jäger
						// GeoMap Link
						echo "<i>Tagesflugplan </i>" . chr(10);
						echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'&Jaeger='. $dataline['Jaeger'] . '" target="_pdfplan"><img src="' . $IconLoc . '/pdf_jaeger.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Jägers auf PDF" ></a>' .chr(10);
						echo'<a href="' .$GeoMapURL1 . $JaegerDayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_jaeger.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Jägers auf Geomap"></a>' .chr(10);
						echo " <i> des Jägers </i>". chr(10);
					} else	{
						//Pilot
						// GeoMap Link
						echo "<i>Tagesflugplan </i>" . chr(10);
						echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'&Pilot='. $dataline['Pilot'] . '" target="_pdfplan"><img src="' . $IconLoc . '/pdf_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf PDF" ></a>' .chr(10);
						echo'<a href="' .$GeoMapURL1 . $PilotDayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf Geomap"></a>' .chr(10);
						echo " <i> des Piloten </i>". chr(10);
						echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'" target="_pdfplan"><img src="' . $IconLoc . '/pdf_tag.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf PDF"></a>' .chr(10);
						echo'<a href="' .$GeoMapURL1 . $DayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_day.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf Geomap"></a>' .chr(10);
						echo " <i> des Teams </i>". chr(10);
					}
				}
				echo " ". chr(10);
				echo $ScreenTableSetup["</td>"] . chr(10);
				echo $ScreenTableSetup["</tr>"] . chr(10);
			}
			// Existing Data
			CreateOutputLine($dataline, array(), $i, $i);
			$PrevPilot =$dataline['Pilot'];
			$PrevJaeger =$dataline['Jaeger'];
			$PrevDate =$dataline['Datum'];
			$ActDate =$PrevDate;

		}
		$i++;
	}

	echo $ScreenTableSetup["</table>"] .chr(10);
	echo "</small>";
	echo '</span> ';

//if ($Mode == "J")  
//	//$ParameterSet = $JaegerWahlSet . $ModeSet. "&SelFrom=" . $FromDate . "&SelTo=" . $ToDate . "&" . $MissionSelectionParameter;
//	$ParameterSet = $RegOption . "&" . $JaegerWahlSet . $ModeSet. "&" . $MissionSelectionParameter;
//else
	//$ParameterSet = $PilotenWahlSet . $ModeSet. "&SelFrom=" .  $FromDate. "&SelTo=" . $ToDate. "&" . $MissionSelectionParameter;
	$ParameterSet = $RegOption . "&" . $JagerWahlSet . "&" . $PilotenWahlSet . $ModeSet. "&" . $MissionSelectionParameter;

//$QRCode = $WebLink_public . "rkrk_flugliste.php" ."?" . str_replace(array("&", " "),array("%26","%2B"), $ParameterSet); 
$QRCode = $WebLink_public . "rkrk_flugliste.php" ."?" . $ParameterSet; 
$qrcode1 = "";
$qrcode2 = "";
if ($QRCode!="") {
	//echo $QRCode;
	$qrcode1.= "<br>Link zu diesem Flugplan:<br>";
	//$qrcode1.= "<a href='".$QRCode."'><img src='https://chart.googleapis.com/chart?chs=350x350&cht=qr&chl=".$QRCode."&choe=UTF-8&chld=L' title='Link auf Fluglisten Ansicht' /></a><br>";
	
	// new source 21.05.2024 https://goqr.me/
	$qrcode1.= '<a href="'.$QRCode.'"><img src="https://api.qrserver.com/v1/create-qr-code/?color=000000&amp;bgcolor=FFFFFF&amp;data='.$QRCode.'&amp;qzone=1&amp;margin=0&amp;size=350x350&amp;ecc=M" alt="qr code" / title="Link auf Fluglisten Ansicht" ></a><br>';	

	//$qrcode1.= "<p style='font-size:4;width: 150px'>".$WebLink."</p>";
	$qrcode2.= "";
}
echo "<div style='display:none;top:80px;left:80px;z-index:8;background-color:white' id='qrbloc1'>" .chr(10);
echo $qrcode1;
echo "</div>";
echo "<div style='display' id='qrbloc2'>" .chr(10);
echo $qrcode2;
echo "</div>";

	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

	$FontSizeInPoints = 12;
	if ($FontSize== "") {
		$FontSize= "120%";
	}

	$TableColor =  GetTableColor ($BaseColor);
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	
	if ($TableType=="Table") {
		echo $ScreenTableSetup["<div>"].chr(10);
		//PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
		echo $ScreenTableSetup["</div>"].chr(10);
	}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo ($ScreenTableSetup["<table line>"] ?? '');
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);
	
	// Pagecontrol disabled
	//if ($TableType=="Flex") {
	//	echo $ScreenTableSetup["<tr>"] .chr(10);
	//	echo $ScreenTableSetup["<td line>"] .chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
	//	echo $ScreenTableSetup["</td>"] .chr(10);
	//	echo $ScreenTableSetup["</tr>"] .chr(10);
	//}
	//echo $ScreenTableSetup["</table>"] .chr(10);



	echo '</form>'. chr(10);
    echo '<br>'. chr(10);

 if ($Layer)
	echo "<div  id='imgbox' style='mybox,top:10px;right:60px;z-index:1;'></div>" . chr(10);
else 
	echo "<div  id='imgbox'></div>" . chr(10);

//hide if no flight
echo "</span>";

//QR code

?>
<script> 
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

function getElementLeft(elm) 
{
    var x = 0;

    //set x to elm’s offsetLeft
    x = elm.offsetLeft;

    //set elm to its offsetParent
    elm = elm.offsetParent;

    //use while loop to check if elm is null
    // if not then add current elm’s offsetLeft to x
    //offsetTop to y and set elm to its offsetParent

    while(elm != null)
    {
        x = parseInt(x) + parseInt(elm.offsetLeft);
        elm = elm.offsetParent;
    }
    return x;
}

function getElementTop(elm) 
{
    var y = 0;

    //set x to elm’s offsetLeft
    y = elm.offsetTop;

    //set elm to its offsetParent
    elm = elm.offsetParent;

    //use while loop to check if elm is null
    // if not then add current elm’s offsetLeft to x
    //offsetTop to y and set elm to its offsetParent

    while(elm != null)
    {
        y = parseInt(y) + parseInt(elm.offsetTop);
        elm = elm.offsetParent;
    }

    return y;
}

function Large(obj)
{
    var imgbox=document.getElementById("imgbox");
    imgbox.style.visibility='visible';
    var img = document.createElement("img");
    img.src=obj.src;
    img.style.width='600px';
    img.style.height='600px';
    
    if(img.addEventListener){
        img.addEventListener('mouseout',Out,false);
    } else {
        img.attachEvent('onmouseout',Out);
    }             
    imgbox.innerHTML='';
    imgbox.appendChild(img);
    imgbox.style.left=(getElementLeft(obj)-100) +'px';
    imgbox.style.top=(getElementTop(obj)-350) + 'px';
}

function Out()
{
    document.getElementById("imgbox").style.visibility='hidden';
}
</script>


</body>
</HTML>
