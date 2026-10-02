<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_menu.php');
	include_once('rkrk_access_control.php'); 


	//check impropriate IP addresses accessing page
	CheckIPandExit();
  
	$pagetitle = "Einsatzplanung";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

	
	// ===========================================
	// Gobal Defintions
	// ===========================================

		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");

	foreach ($WeatherOptions as $Entry) {
		$WeatherIcon[] = $Entry["Opt"];
		$WeatherText[] = $Entry["text"];
	}
    //pre_r($WeatherText);
	//pre_r($WeatherIcon);

	foreach ($AvailablityOptions as $Entry) {
		$AvailablityIcon[] = $Entry["Opt"];
		$AvailablityText[] = $Entry["text"];
	}
    //pre_r($AvailablityText);
	//pre_r($AvailablityIcon);

	$PilotList = array();
	$PilotListWrite = array();
	//pre_r($PilotList);
	
	 
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

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

if (isset($_GET["DoSafe"])) { 
	if ($_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}
} else {
	$dosafe= false;
}
if (!isset($EnableDelete)) {
	$EnableDelete = false;
}
if (!isset($EnableDeleteSet)) {
	$EnableDeleteSet = "";
}

if (isset($_GET["Jahr"])) {
	$Jahr = $_GET["Jahr"];
} else	{
	$Jahr = '2023';
}


if (isset($_GET["SelTo"])) { 
	$ToDate = $_GET["SelTo"];
} 
elseif (isset($_POST["SelTo"])) { 
	$ToDate = $_POST["SelTo"];
} else {
	$ToDate =GetSasionEndDate($Jahr);
}

if (isset($_GET["SelFrom"])) { 
	$FromDate = $_GET["SelFrom"];
	$Jahr = date("Y",strtotime($_GET["SelFrom"]));
} 
elseif (isset($_POST["SelFrom"])) { 
	$FromDate = $_POST["SelFrom"];
} else {
	if (GetSasionStartDate($Jahr) < date('Y-m-d'))
		$FromDate = GetSasionStartDate($Jahr);
	else
		$FromDate = date('Y-m-d', strtotime('last monday', strtotime('next monday')));
}

///////////////////////////////////////
// Load file
///////////////////////////////////////

	$csv_file= $Jahr . $RKRKWeatherCSV;
	//$csv_file= $Jahr ."_RKRKPlan.csv";
	$csv_deliminator = ";";

	//Read CSV file
	transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	$data = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	$data = FormatWeatherDataArray($data);
	$checkDate = !empty($FromDate) ? $FromDate : date('Y-m-d');
	$PilotList = array();
	foreach ($OptionPilot as $Pilot) {
		if (!empty($Pilot["plan"]) && rkrk_pilot_is_selectable($Pilot, $checkDate, '', 'assign')) {
			$PilotList[] = $Pilot;
		}
	}
	$PilotListWrite = $PilotList;
	$haveWrite = array();
	foreach ($PilotListWrite as $p) {
		$haveWrite[$p['Opt']] = true;
	}
	if (is_array($data)) {
		foreach ($data as $row) {
			if (!is_array($row)) {
				continue;
			}
			foreach ($row as $col => $v) {
				if (strpos((string)$col, '_Icon') !== false) {
					continue;
				}
				if (!preg_match('/^Plan_([A-Za-z0-9]+)$/', (string)$col, $m)) {
					continue;
				}
				$code = $m[1];
				if (!empty($haveWrite[$code])) {
					continue;
				}
				if (isset($OptionPilot[$code])) {
					$PilotListWrite[] = $OptionPilot[$code];
				} else {
					$PilotListWrite[] = array('Opt' => $code, 'Text' => $code);
				}
				$haveWrite[$code] = true;
			}
			break;
		}
	}
	// change key to FlugID
	//pre_r($data);



///////////////////////////////////////
// Process Input Data
///////////////////////////////////////


/// POST
	if (
		($_SERVER["REQUEST_METHOD"] == "POST")
		 and $dosafe) {
					
		//echo "Datum: " . $_POST["Datum-1"] . "<br>";
		for ($i = 1; $i <= $_POST["NumEntries"]; $i++) {
			$date = $_POST["Datum-" . $i];
			$data[$date]["Datum"] =$date;
			$data[$date]["Wetter"] =str_replace($WeatherIcon,$WeatherText, $_POST["Wetter_Icon-" . $i]);
			
			$data[$date]["Wetter_Icon"] = $_POST["Wetter_Icon-" . $i];
			$data[$date]["Temp"] = $_POST["Temp-" . $i];
			foreach ($PilotList as $Pilot) {
				$data[$date]["Plan_" . $Pilot["Opt"] . "_Icon"] = $_POST['Plan_'  . $Pilot['Opt'] . '_Icon-' . $i];
				$data[$date]["Plan_" . $Pilot["Opt"]] = str_replace($AvailablityIcon, $AvailablityText, $_POST['Plan_'  . $Pilot['Opt'] . '_Icon-' . $i]);
				//$data[$date]["Einsatz_"  . $Pilot["Opt"]] = $_POST["Einsatz_"  . $Pilot["Opt"] . "-" . $i];
			}

/*			$data[$date]["Piloten"] = $_POST["Piloten-" . $i];
			$data[$date]["Felder"] = $_POST["Felder-" . $i];
			$data[$date]["Funde"] = $_POST["Funde-" . $i];
			$data[$date]["Fläche"] = $_POST["Fläche-" . $i];
*/		}

	}	
	//pre_r($data);

///////////////////////////////////////
// Generate Output File Data
///////////////////////////////////////
	$dosafe= true;	
	if ($dosafe) {

		//new configuration, safe configuration file
		//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
		
		$i= 1;

		$filecontent = "";
		$filecontent .=	'Datum;Wetter;Temp;';
		foreach ($PilotListWrite as $Pilot) {
			$filecontent .= "Plan_"  . $Pilot["Opt"] . $csv_deliminator;
		}
		$filecontent = substr($filecontent,0,-1) . chr(13) . chr(10);
		//$filecontent .= "<br>";
		//$filecontent .= 'Piloten;Felder;Funde;Fläche' . chr(13) . chr(10);
		foreach ($data as $entry) {
		//	echo $i . " " . $_POST["Initialen-" . $i];
		//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
			
			IF ($entry["Datum"]!="") {

				//$rowcontent = chr(34);
				$rowcontent = "";
				$rowcontent .= $entry["Datum"] . $csv_deliminator;
				$rowcontent .= ($entry["Wetter"] ?? '') . $csv_deliminator;
				$rowcontent .= ($entry["Temp"] ?? '') . $csv_deliminator;
				foreach ($PilotListWrite as $Pilot) {
					$rowcontent .= ($entry["Plan_"  . $Pilot["Opt"]] ?? '') . $csv_deliminator;
				}
				$rowcontent = substr($rowcontent,0,-1) . chr(13) . chr(10);
				//echo $rowcontent . "<br>";
				
				$filecontent .= $rowcontent;
			} 
		} // foreach

		
		//echo "output: <br><br>" . $filecontent;
		if ($dosafe) {
			$filepath= $csvDocLocation . $csv_file;

			// safe history
			$file = $filepath;
			$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
			//echo "<br>History: " . $file . " to  " . $newfile;
			if (!copy($file, $newfile)) {
				$SystemInfo .= "failed to copy $file...\n";
			}

			// safe file
			//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
			$df = fopen($filepath, 'w');
			fputs($df, $filecontent);
			//echo $filecontent;
			fclose($df);
		} else {
			//echo $filecontent;
		}
	}

	//read file (again after writing to file
	transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	//echo "FileLocation: " . $csvDocLocation.$csv_file . "<br>";
	$data = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	$data = csv_to_array($csvDocLocation.$csv_file,';');
	$data = FormatWeatherDataArray($data);
	
	// Assure datasetlength 

	$begin = strtotime(GetSasionStartDate($Jahr));
	$end = strtotime(GetSasionEndDate($Jahr));
	//echo date("Y-m-d", $begin) . "<br>";
	$i = 0;
	do {
		$newTime = strtotime('+'.$i++.' days',$begin); 
		//echo date("Y-m-d",$newTime);
		$data[date("Y-m-d",$newTime)]["Datum"] = date("Y-m-d",$newTime);
	} while ($newTime < $end);
//	$data = FormatWeatherDataArray($data);
	
	// Add Icons, Weather, Availability
	foreach ($data as $Key => $Datum) {
		$data[$Key]["Wetter_Icon"] = str_replace($WeatherText, $WeatherIcon, $data[$Key]["Wetter"] ?? '');
		foreach ($PilotList as $Pilot) {
			$data[$Key]['Plan_'  . $Pilot["Opt"] . '_Icon'] = str_replace($AvailablityText, $AvailablityIcon, $data[$Key]["Plan_"  . $Pilot["Opt"]] ?? '');
		}
	}
	ksort($data); 
	
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
		$FontSize= "120%";
	}


	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Planung ", $FeldID ?? '', $RKRKMissionSelected,"");
		foreach ($data as $Key => $Datum) {
			if (	
					(
						($Datum["Datum"] >= $FromDate) and
						($Datum["Datum"] <= $ToDate)
					)
				) 
			{
				$data[$Key]["ListNr"]= $i;
				$i++;
			} else {
				$data[$Key]["ListNr"]= "";
			}
		}
		$MaxSelectedEntries = $i;
	}
	//pre_r($data);
	//PRE_R($EnabledPilot);


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 11;
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

$EnabledRegion = Array();
//form to change setting
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;
//echo $i . " " . $KMLFileSettings->name . chr(10)
echo "<table width=100%>";
	echo "<tr><td>";
	echo "</td><td>";
	echo "</td><td>";
	echo "</td><td>";
	if ($Layout!=2023) {
		echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
		echo CreateSiteLink ("EditFeld", "");
		echo CreateSiteLink ("EditFiles", "");
		echo CreateSiteLink ("Fluege", "");
		echo CreateSiteLink ("FlugStats", "");
		echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
		echo CreateSiteLink ("Home", "");
	}

	// ===========================================
	// Tabelle
	// ===========================================

	echo "</td></tr>";

	$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
	$MinDate =date('Y-m-d', strtotime('-1 year'));
	$MinDate ="2021-01-01";
	$MaxDate =date('Y-m-d', strtotime('+4 year'));
	$Today = date("Y-m-d");
	//$ToDate = date("Y-m-d",strtotime("third sunday " . $Jahr . "-07"));
	
	echo "<tr><td>";
	echo "  Datum: " .chr(10);
	echo "</td><td>";
	echo DateJSFunctionField("SelFrom", $FromDate, $MinDate , $MaxDate ,"UpdSelDatum", "this.value", $i, 4 );
	echo " - " ;
	echo DateField("SelTo", $ToDate, $MinDate , $MaxDate);
	//echo " Datum laufend ";
	//echo CheckField ("Adopt", $Adopt);
	echo "</td>";
	echo "<td>";
	echo "</td>";
	echo "<td rowspan=3>";
	//Not needed on this page
	//echo  MissionLibraryButtons($EnableDeleteSet . "&SelFrom=".  $FromDate . "&SelTo=" . $ToDate . "&Jahr=" . $Jahr,true);
	if (GetAccess("TestOnly")) {
		echo "<span id='exceldwnld'><a href='".$csvDocLocation.$csv_file."' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Planung herunterladen'></a></span> ";
		//$csvDocLocation.$RKRKWeatherCSV = '_RKRKPlan.csv';
	}
	echo "</td>";
	echo "</tr>";

	echo "</td></tr>";
	echo "</table  width=100%>";
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&' .  '&Jahr=' . $Jahr. '"> '. chr(10);
	echo "<br>";

	//$MaxSelectedEntries = count($data);
	//$MaxSelectedEntries = $i;
	$i=1;
	$pageline = 1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage - 1;
	$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	//echo "AnzLinesPPage: " .$AnzLinesPPage . "<br>";
	//echo "Show: " .$showfrom . " - " . $showto ."<br>";

	//echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	echo "<br>" .chr(10);
	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Speichern", false, "&Jahr=" . $Jahr);
	echo "<br>" .chr(10);
	
	//form to change setting
	echo "<table style='width:600px'>" .chr(10);
	echo "<tr >" .chr(10);
	//$FeldListe = GetFieldList($Feldkatalog);
	echo 	"";		
//Datum;Wetter;Temp;Plan_TC;Plan_VP;Plan_CH;Plan_LZ;Einsatz_TC;Einsatz_VP;Einsatz_CH;Einsatz_LZ;Piloten;Felder;Funde;Fläche
	if ($EnableDelete) {
		echo "<th></th>" .chr(10);
	}
	if ($debugmode) {
		echo "<th>#</th>" .chr(10);
	}
	echo "<th>Datum*</th>" .chr(10);
	echo "<th>Wetter</th>" .chr(10);
	echo "<th>Temp</th>" .chr(10);
	foreach ($PilotList as $Pilot) {
		echo "<th><div title='" . $Pilot["Text"] . "'>"  . $Pilot["Opt"] . "</div></th>" .chr(10);
		//echo "<th>Einsatz_"  . $Pilot["Opt"] . "</th>" .chr(10);
	}
/*
	echo "<th>Piloten</th>" .chr(10);
	echo "<th>Felder</th>" .chr(10);
	echo "<th>Funde</th>" .chr(10);
	echo "<th>Fläche</th>" .chr(10);
*/

	echo "</tr >" .chr(10);
	//new data
	echo "<tr><td colspan='20'><i>Wetterentwicklung und Verfügbarkeit:</i><td></tr>". chr(10);
	// Extisting Entries
	$PrevPilot ="";
	$ActDate =$FromDate;
	$PrevDate =$Tomorrow;
	$PrevPilot = "";
	$MaxEntry = $i+2;
//	GetNewLine();
	$NewLines = false;
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?FFilter=25-048+25-049+21-001+21-004+21-007&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL2 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8";
	
	//pre_R($data);
	
	
	foreach ($data as $dataline) {
		if ($dataline["ListNr"]!="" &&
		//pre_R($dataline);
			$dataline["ListNr"]>=$showfrom && 
			$dataline["ListNr"]<=$showto){

			// Separator

			if (
				(
				(date('M', strtotime((string)$dataline['Datum'])) != date('M', strtotime((string)$PrevDate))) or
				(date('N',strtotime($dataline['Datum']))==1)
				) and
				true
				)
			{
				echo "<tr><td colspan='20'><b><i>Woche " . date('W',strtotime($dataline['Datum']))  . "</i></b></td>";
				echo "</tr>". chr(10);
				echo "</td></tr>" .chr(10);
			}
			// Existing Data
			echo '<tr>' .chr(10);
			if ($debugmode) {
				echo '<td>' .chr(10);
				echo $i;
				echo '</td>' .chr(10);
			}	
			echo '<td>' .chr(10);
			//echo $dataline['Datum'] . " ";
			echo '<input type="hidden" name="Datum-'.$i.'" id="Datum-'.$i.'" value="'. $dataline['Datum'] . '" />'. chr(10);
			echo $DayShortList[date('N',strtotime($dataline['Datum']))] . " ";
			echo date('d. M',strtotime($dataline['Datum']));
			echo '</td>' .chr(10);
			echo '<td>' .chr(10);
			$Inputfieldname= "Wetter_Icon-" . $i;
			echo SelectFieldMultiOptArr($Inputfieldname, $dataline['Wetter_Icon'] ?? '' ,$WeatherOptions );
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Temp-" . $i, $dataline['Temp'] ?? '', 3);
			echo "</td>" .chr(10);
			foreach ($PilotList as $Pilot) {
				echo '<td>' .chr(10);
				$Inputfieldname= 'Plan_'  . $Pilot['Opt'] . '_Icon-' . $i;
				echo SelectFieldMultiOptArr($Inputfieldname, $dataline['Plan_'  . $Pilot['Opt'] . '_Icon'] ?? '' ,$AvailablityOptions );
				echo "</td>" .chr(10);
				/*
				echo '<td>' .chr(10);
				$Inputfieldname= 'Einsatz_'  . $Pilot['Opt'] . '_Icon-' . $i;
				echo InputField("Einsatz_"  . $Pilot["Opt"] . "-" . $i, $dataline['Einsatz_'  . $Pilot["Opt"] ], 5);
				echo "</td>" .chr(10);
				*/
			}
	/*		echo '<td>' .chr(10);
			echo InputField("Piloten-" . $i, $dataline['Piloten'], 5);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Felder-" . $i, $dataline['Felder'], 5);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Funde-" . $i, $dataline['Funde'], 5);
			echo "</td>" .chr(10);
			echo '<td>' .chr(10);
			echo InputField("Fläche-" . $i, $dataline['Fläche'], 5);
			echo "</td>" .chr(10);
	*/ 
			echo "</tr>" .chr(10);
			$PrevDate =$dataline['Datum'];
			$ActDate =$PrevDate;
		}
		$i++;
	}
	$MaxEntry = $i+2;
	echo "</table>" .chr(10);
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, "&Jahr=" . $Jahr);
	
	echo '</form>'. chr(10);
    echo '<br>'. chr(10);
  

// ========================================
// Date for JS array update
// ========================================

// Felddaten 
if (is_array($Feldkatalog ?? null)) {
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
}
//pre_R($FieldDataArray);
//Adressdaten

echo "<i>Legende:</i> <br>" . chr(10);

foreach ($AvailablityOptions as $Option) {
	if ($Option["Opt"]!="")
		echo $Option["Opt"] . ": " . $Option["text"] . "<br>";
}
?>

<pre id="debug"></pre>
<script type='text/javascript'>
<?php
//echo json_encode($AdressDataArray);
echo "var FieldDataArray = ". json_encode($FieldDataArray ?? new stdClass()) . ";\n";
echo "var AdressDataArray = ". json_encode($AdressDataArray ?? new stdClass()) . ";\n";
echo "var RegDataArray = ". json_encode($RegDataArray ?? new stdClass()) . ";\n";
?>

// Transfering date from PHP to JSON
//var FieldDataArray = $.parseJSON(<?php echo json_encode($FieldDataArray ?? new stdClass()); ?>);
//var AdressDataArray = $.parseJSON(<?php echo json_encode($AdressDataArray ?? new stdClass()); ?>);

//  document.getElementById("debug").innerHTML = FieldDataArray["21-012"]["FeldID"];

// update fields according to changes
function UpdJaeger(val, line) {
  document.getElementById("Kontaktnummer-" + line).value = AdressDataArray[val]["Text"];
  //alert("Jäger hat geändert auf: " + val);
}
function UpdPilot(val, line) {
  //alert("Pilot hat geändert auf: " + val);
}
function UpdFund(val, line) {
  document.getElementById("#Funde-" + line).value = document.getElementById("Fund-" + line).value;
  //alert("Fund hat geändert auf: " + val);
}
function UpdDatum(val, line) {
  //document.getElementById("#Einsatztag-" + line).value = document.getElementById("Datum-" + line).value;
  //alert("Datum hat geändert auf: " + val);
}
function UpdSelDatum(val, line) {
  //document.getElementById("SelTo").value = document.getElementById("SelFrom").value;
  //alert("Selektiertes Datum hat geändert auf: " + val);
}

function UpdField(val, line) {
  if (val=="") {
	document.getElementById("Felder-" + line).value = "";
	document.getElementById("GrundstückID-" + line).value = "";
	document.getElementById("Gde-" + line).value = "";
	document.getElementById("JG-" + line).value = "";
	document.getElementById("Reg-" + line).value = "";
	document.getElementById("Fläche-" + line).value = "";
  } else {
	document.getElementById("Felder-" + line).value = FieldDataArray[val]["Text"];
	document.getElementById("GrundstückID-" + line).value = FieldDataArray[val]["GrundstueckID"];
	document.getElementById("Gde-" + line).value = FieldDataArray[val]["Gemeinde"];
	document.getElementById("JG-" + line).value = FieldDataArray[val]["JG"];
	document.getElementById("Reg-" + line).value = RegDataArray[FieldDataArray[val]["JG"]]["RegUp"];
	document.getElementById("Fläche-" + line).value = FieldDataArray[val]["Flaeche"];
	document.getElementById("#Einsatztag-" + line).value = document.getElementById("Datum-" + line).value+1;
  }
  //  alert("Feld hat geändert auf: " + val);
}

</script>

</body>
</HTML>
