<?php
// ==================
// Funktionen für die Rehktitzrettung
// ==================
  //session_start();
  include_once('rkrk_gen.php');
  include_once('rkrk_dji_kmz_interface.php');
  if (!function_exists('rkrk_felddaten_json_seconds_to_catalog_min')) {
	  include_once(__DIR__ . '/rkrk_felddaten.php');
  }


// ==================
// Definitions
// ==================

	$JsonTemplates = Array(
		"M2EA" => Array(
			"Application" => "Pilot1",
			"File" => "m2ea_template.json",
			"active" => true,
		),
		"M2" => Array(
			"Application" => "Litchi",
			"File" => "m2_litchi_template.json",
			"active" => true,
		),
		"3T" => Array(
			"Application" => "Pilot2",
			"File" => "m3t_template.json",
			"active" => true,
		),
	);
	
	$JsonTemplateLocation = "uav_templates/";

   $DebugOutput = true;
   

   $TestJsonFile = "21_Aeugst/21-003_Puenten_1875_50_CH_21.json";
   $TestJsonFile = "21_Aeugst/21-001_Böösch_2152_50_CH_21.json";
   $w1TestJsonFile = "21_Aeugst/TestJson1.json";
   $w2TestJsonFile = "21_Aeugst/TestJson2.json";

   
// ==================
// Read JSON file and convert to array
// ==================


	//26.06.2023 Telefonnummer im UAV Editor darf keine Spaces mehr haben. Wir möchten aber Spaces in der Telefonnummer
	// support only for Switzerland

	function CleanFormatPhoneNumber($PhoneNumber) {
		if ($PhoneNumber!="") {
			if (function_exists("rkrk_adr_format_phones_for_json")) {
				return rkrk_adr_format_phones_for_json($PhoneNumber);
			}
			if (preg_match('/[,;\/]/', $PhoneNumber)) {
				$parts = preg_split('/[,;\/]+/', $PhoneNumber);
				$out = array();
				$seen = array();
				foreach ($parts as $part) {
					$part = trim($part);
					if ($part === "") {
						continue;
					}
					$one = str_replace(" ", "", FormatPhoneNumber($part));
					if ($one !== "" && !isset($seen[$one])) {
						$seen[$one] = true;
						$out[] = $one;
					}
				}
				return implode(",", $out);
			}
			$PhoneWOSpace = str_replace(" ", "", FormatPhoneNumber($PhoneNumber));
			return ($PhoneWOSpace);
		} else {
			return ($PhoneNumber);
		}
	}

	function FormatPhoneNumber($PhoneWOSpace) {
		// Case 0: 079 / 5203625 => clean unwanted entries
		$PhoneWOSpace = str_replace(array("/"," "),Array("",""), $PhoneWOSpace);
		//echo $PhoneWOSpace . "<br>";
		// Case 1: 0041795203625 => +41 79 520 36 25
		if (substr($PhoneWOSpace,0,2)==="00")	
		{
			$PhoneWOSpace = "+41" . substr($PhoneWOSpace,2, 15);
			//echo "Case1: " .$PhoneWOSpace . "<br>";
		}
		// Case 2: 795203625 => +41 79 520 36 25
		elseif (strlen((string)$PhoneWOSpace) >= 1 && $PhoneWOSpace[0]=="7") 
		{
			$PhoneWOSpace = "+41" . $PhoneWOSpace;
			//echo "Case2: " .$PhoneWOSpace . "<br>";
		}
		// Case 3: 0795203625 => +41 79 520 36 25
		elseif (strlen((string)$PhoneWOSpace) >= 1 && $PhoneWOSpace[0]=="0") 
		{
			$PhoneWOSpace = "+41" . substr($PhoneWOSpace,1,15);
			//echo "Case3: " .$PhoneWOSpace . "<br>";
		}
		elseif (strlen((string)$PhoneWOSpace) >= 1 && $PhoneWOSpace[0]!="+") 
		{
			$PhoneWOSpace = "+" . $PhoneWOSpace;
			//echo "Case4: " .$PhoneWOSpace . "<br>";
		}

		$digits = preg_replace('/\D+/', '', $PhoneWOSpace);
		if (substr($digits, 0, 2) === "41" && strlen($digits) > 2) {
			$national = substr($digits, 2, 9);
		} elseif (isset($digits[0]) && $digits[0] === "0" && strlen($digits) > 1) {
			$national = substr($digits, 1, 9);
		} else {
			$national = substr($digits, 0, 9);
		}
		if (strlen($national) >= 9) {
			return "+41 " . substr($national, 0, 2) . " " . substr($national, 2, 3) . " " . substr($national, 5, 2) . " " . substr($national, 7, 2);
		}
		if ($digits !== "") {
			return "+" . $digits;
		}
		return $PhoneWOSpace;
	}



// Read Jason File and return Array of Data
function ReadJsonFile ($Filename) {
	if (function_exists('rkrk_read_json_file')) {
		return rkrk_read_json_file($Filename);
	}
	if (!file_exists($Filename)) {
		return array();
	}
	$JsonString = file_get_contents($Filename);
	if ($JsonString === false || $JsonString === "") {
		return array();
	}
	if (strncmp($JsonString, "\xEF\xBB\xBF", 3) === 0) {
		$JsonString = substr($JsonString, 3);
	}
	if (function_exists("mb_check_encoding") && !mb_check_encoding($JsonString, "UTF-8")) {
		if (function_exists("mb_convert_encoding")) {
			$JsonString = mb_convert_encoding($JsonString, "UTF-8", "ISO-8859-1");
		}
	}
	$JsonArray = json_decode($JsonString, true);
	return is_array($JsonArray) ? $JsonArray : array();
}

function WriteJsonFile ($Filename, $JsonArray) {
	if (function_exists('rkrk_write_json_file')) {
		rkrk_write_json_file($Filename, $JsonArray);
		return;
	}
	$myfile = fopen($Filename, "w") or die($Filename. " JSON File kann nicht geöffnet werden!");
	fwrite($myfile, json_encode($JsonArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
	fclose($myfile);
}

// Read JSON data to Internal Array
function GetJSONData($Filename) {
	global $DebugOutput, $searchSDZ, $replaceSDZ;
    $DebugInfo = ""; 

	//Read JSON File
	$DataA = ReadJsonFile($Filename);
	//echo "read JSON data ". $Filename ."<br>";
	//pre_R($DataA);
	
	//Reassign Data to Internal Array
	$DataOut = array();

	$DataOut["FileName"] = $DataA["fileName"];
	$DebugInfo .=  "<li> Filename: " . $DataOut["FileName"] . "</li>" . chr(10);

	$DataOut["Name"] = $DataA["name"];
	$DebugInfo .=  "<li> Name: " . $DataOut["Name"] . "</li>" . chr(10);

	$DataOut["GroupAreaName"] = $DataA["pilotGroupAreaName"] ?? "";
	$DebugInfo .=  "<li> GroupAreaName: " . $DataOut["GroupAreaName"] . "</li>" . chr(10);

	$DataOut["Wegpunkte"] = $DataA["featureCount"]["waypoints"];
	$DebugInfo .=  "<li> Wegpunkte: " . $DataOut["Wegpunkte"] . "</li>" . chr(10);

	$DataOut["Flugzeit"] = rkrk_felddaten_json_seconds_to_catalog_min($DataA["flightTime"] ?? 0);
	$DebugInfo .=  "<li> Flugzeit: " . $DataOut["Flugzeit"] . " min (JSON: " . ($DataA["flightTime"] ?? "") . " s)</li>" . chr(10);

	$DataOut["Flaeche"] = $DataA["area"];
	$DebugInfo .=  "<li> Fläche: " . $DataOut["Flaeche"] . "</li>" . chr(10);

	$DataOut["Distanz"] = $DataA["flightDistance"] ?? "";
	$DebugInfo .=  "<li> Distanz: " . $DataOut["Distanz"] . "</li>" . chr(10);

	if (isset($DataA["center"]["0"], $DataA["center"]["1"]) && $DataA["center"]["0"] !== "" && $DataA["center"]["1"] !== "") {
		$DataOut["Grundstueckdaten"]["Zentrum"] = $DataA["center"]["0"] . ", " . $DataA["center"]["1"];
	} else {
		$DataOut["Grundstueckdaten"]["Zentrum"] = "";
	}
	$DebugInfo .=  "<li> Zentrum: " . $DataOut["Grundstueckdaten"]["Zentrum"] . "</li>" . chr(10);
	$DataOut["Flaechendaten"] = "";
	$FirstPoint = "";
	$LastPoint = "";
	if (isset($DataA["geojson"]["features"]["0"]["geometry"]["coordinates"]) && is_array($DataA["geojson"]["features"]["0"]["geometry"]["coordinates"])) {
	foreach ($DataA["geojson"]["features"]["0"]["geometry"]["coordinates"] as &$value) {
		if (isset($value["0"], $value["1"]) && $value["0"] !== ""){
			$CurrentPoint = $value["0"] . ", " . $value["1"] . "     ";
			$DataOut["Flaechendaten"] .=  $CurrentPoint;
			if ($FirstPoint=="") {
				$FirstPoint= $CurrentPoint;
			}
			$LastPoint= $CurrentPoint;
		}
	}
	}
	//Close Polygone
	if ($FirstPoint<>$LastPoint) {
		$DataOut["Flaechendaten"] .=  $FirstPoint;
	}
// vor 16.05.2025
/*
	if ($DataOut["Flaechendaten"]=="") {
		foreach ($DataA["geojson"]["features"]["1"]["geometry"]["coordinates"] as &$value) {
			if ($value["0"]!="") {
				$DataOut["Flaechendaten"] .=  $value["0"] . ", " . $value["1"] . "     ";
			}
		}
	}
*/
// von 16.02.2025
	$FirstPoint = "";
	if ($DataOut["Flaechendaten"]=="") {
		$CurrentPoint = "";
		if (isset($DataA["geojson"]["features"]["1"]["geometry"]["coordinates"]) && is_array($DataA["geojson"]["features"]["1"]["geometry"]["coordinates"])) {
		foreach ($DataA["geojson"]["features"]["1"]["geometry"]["coordinates"] as &$value) {
			if (isset($value["0"], $value["1"]) && $value["0"] !== "") {
				$CurrentPoint = $value["0"] . ", " . $value["1"] . "     ";
				$DataOut["Flaechendaten"] .=  $CurrentPoint;
				if ($FirstPoint=="") {
					$FirstPoint= $CurrentPoint;
				}
			}
		}
		}
		$LastPoint= $CurrentPoint;
	}
	//Close Polygone
	if ($FirstPoint<>$LastPoint) {
		$DataOut["Flaechendaten"] .=  $FirstPoint;
	}

	$DataOut["Grundstueckdaten"]["Startplatz"] = "";
	$DataOut["Grundstueckdaten"]["Parkplatz"] = "";
	$DataOut["Flugweg"] = "";
	if (isset($DataA["geojson"]["features"]) && is_array($DataA["geojson"]["features"])) {
	foreach ($DataA["geojson"]["features"] as &$value) {
		if (isset($value["geometry"]["type"]) && $value["geometry"]["type"] == "Point") {
			$pointHasCoord = isset($value["geometry"]["coordinates"]["0"], $value["geometry"]["coordinates"]["1"])
				&& $value["geometry"]["coordinates"]["0"] !== ""
				&& $value["geometry"]["coordinates"]["1"] !== "";
			$pointProps = (isset($value["properties"]) && is_array($value["properties"])) ? $value["properties"] : array();
			$pointType = $pointProps["type"] ?? null;
			$pointHeight = $pointProps["height"] ?? 0;
			if ($pointHasCoord
				&& ($DataOut["Grundstueckdaten"]["Startplatz"]=="") 
				&& ($pointType ==6)) {
				// Startpunkt
				$DataOut["Grundstueckdaten"]["Startplatz"] =  $value["geometry"]["coordinates"]["0"] . ", " . $value["geometry"]["coordinates"]["1"];
			}
			if ($pointHasCoord
				&& ($DataOut["Grundstueckdaten"]["Parkplatz"]=="")
				&& ($pointType ==9)) {
				// Parkplatz
				$DataOut["Grundstueckdaten"]["Parkplatz"] =  $value["geometry"]["coordinates"]["0"] . ", " . $value["geometry"]["coordinates"]["1"];
			}
			if (($pointHeight > 0) 
				&& ($pointType ==1))
				// gesetzte POI's (type = 2) für die Kameraausrichtung werden für die Wegpunkte ausgefiltert.
				{
				// Wegpunkt
				if ($pointHasCoord) {
					$DataOut["Flugweg"].=   $value["geometry"]["coordinates"]["0"] . ", " . $value["geometry"]["coordinates"]["1"] . ", ". $pointHeight . "    ";
				}
				// Wegpunkt (Height above See
				//$DataOut["Flugweg"].=   $value["properties"]["aboveSea"]["lat"] . ", " . $value["properties"]["aboveSea"]["lng"] . ", ". $value["properties"]["aboveSea"]["value"] + //$value["properties"]["height"]. ", " . "    ";
			} else {
				// Startpunkt
				if ($pointHasCoord) {
					$DataOut["Grundstueckdaten"]["Startplatz"] =  $value["geometry"]["coordinates"]["0"] . ", " . $value["geometry"]["coordinates"]["1"];
				}
			}
		}
	}
	}
	//Information Landwirt
	$owner = (isset($DataA["data"]["owner"]) && is_array($DataA["data"]["owner"])) ? $DataA["data"]["owner"] : array();
	$ownerFirst = $owner["firstname"] ?? "";
	$ownerLast = $owner["name"] ?? "";
	$ownerAddress = (isset($owner["address"]) && is_array($owner["address"])) ? ($owner["address"]["formatted"] ?? "") : "";
	$ownerPhone = $owner["phone"] ?? "";
	$ownerEmail = $owner["email"] ?? "";
	$ownerNotice = $owner["notice"] ?? "";
	$contributorNotice = (isset($DataA["contributor"]) && is_array($DataA["contributor"])) ? ($DataA["contributor"]["notice"] ?? "") : "";
	$ownerBlock = $ownerFirst . " " . $ownerLast . chr(10) .
						"A: ". $ownerAddress . chr(10) .
						"T: ". FormatPhoneNumber($ownerPhone) . chr(10) .
						"M: ". $ownerEmail;
	$DataOut["Bauer"] = function_exists('rkrk_text_utf8')
		? rkrk_text_utf8($ownerBlock)
		: $ownerBlock;
	$DataOut["Bauer Name"] =   $ownerFirst . " " . $ownerLast; 
	$DataOut["Bauer Adresse"] = function_exists('rkrk_text_utf8')
		? rkrk_text_utf8($ownerAddress)
		: $ownerAddress;
	$DataOut["Bauer Telefon"] =  FormatPhoneNumber($ownerPhone);
	$DataOut["Bauer Email"] = $ownerEmail;

	$tmp_str = function_exists('rkrk_text_utf8')
		? rkrk_text_utf8($ownerNotice) . rkrk_text_utf8($contributorNotice)
		: ($ownerNotice . $contributorNotice);
	$prefix=", ";
	if (substr($tmp_str, 0, strlen($prefix)) == $prefix) 
	{
		$tmp_str = substr($tmp_str, strlen($prefix));
	} 
	$DataOut["Notiz"] = $tmp_str;

	$DataOut["Bauer Email"] = $ownerEmail;
	
	//Gruppengebiete (Auftragskoordination)
	$DataOut["GruppenGebiet"] = $DataA["pilotGroupAreaName"] ?? "";
	$DataOut["ZoneID"] = (isset($DataA["data"]) && is_array($DataA["data"])) ? ($DataA["data"]["zoneId"] ?? "") : "";

	$DebugInfo .=  "<li> Startplatz: " . $DataOut["Grundstueckdaten"]["Startplatz"] . "</li>" . chr(10);
	$DebugInfo .=  "<li> Flaechendaten: " . $DataOut["Flaechendaten"] . "</li>" . chr(10);

	$DebugInfo .=  "<li> Flaechendaten: " . $DataOut["Flugweg"] . "</li>" . chr(10);

	if (function_exists("rkrk_felddaten_json_fill_flugzeit_distanz")) {
		rkrk_felddaten_json_fill_flugzeit_distanz($DataOut, $DataA);
	}

	if (($DebugInfo != "") && ($DebugOutput) && false) {
		echo "<div style='background-color: #FDEDEC;'>". chr(10);
		echo "Debug Meldung Read JSON: <br>". chr(10);
		echo $DebugInfo;
		echo "</div>". chr(10);
	}
	//pre_R($DataOut);
	return ($DataOut);

}


// Update JSONFile
function UpdateJSONData($DataOut, $Filename ) {
	global $DebugOutput;
    $DebugInfo = ""; 

	//Read latest JSON data
	$DataA = ReadJsonFile($Filename);
	//pre_R($DataA);
	
	//update data in JSON Array
	if ($DataOut["FileName"] != "") {
		$DataA["fileName"] = $DataOut["FileName"]  .  chr(10);
		$DebugInfo .=  "<li> Filename: " . $DataOut["FileName"] . "</li>" . chr(10);
    }
	
	if ($DataOut["name"] != "") {
		$DataA["name"] = $DataOut["Name"]  . chr(10);
		$DebugInfo .=  "<li> Name: " . $DataOut["FileName"] . "</li>" . chr(10);
	}
	
	if ($DataOut["Wegpunkte"] != "") {
		$DataA["featureCount"]["waypoints"] = $DataOut["Wegpunkte"]  . chr(10);
		$DebugInfo .=  "<li> Wegpunkte: " . $DataA["featureCount"]["waypoints"] . "</li>" . chr(10);
	}

	if ($DataOut["Flugzeit"] != "") {
		$DataA["flightTime"] = rkrk_felddaten_catalog_min_to_json_seconds($DataOut["Flugzeit"]);
		$DebugInfo .=  "<li> Flugzeit: " . $DataOut["Flugzeit"] . " min → JSON " . $DataA["flightTime"] . " s</li>" . chr(10);
	}

	if ($DataOut["Flaeche"] != "") {
		$DataA["area"] = $DataOut["Flaeche"] . chr(10);
		$DebugInfo .=  "<li> Fläche: " . $DataA["area"] . "</li>" . chr(10);
	}

	if ($DataOut["Distanz"] != "") {
		$DataA["flightDistance"] = $DataOut["Distanz"] . chr(10);
		$DebugInfo .=  "<li> Distanz: " . $DataA["flightDistance"] . "</li>" . chr(10);
	}


	if ($DataOut["Grundstueckdaten"]["Zentrum"] != "") {
		//Get Coodrinates
		$DataExplode = explode(",", $DataOut["Grundstueckdaten"]["Zentrum"]);
		$DataA["center"]["0"] = $DataExplode[0];
		$DataA["center"]["1"] = $DataExplode[1];
		$DebugInfo .=  "<li> Zentrum: " . $DataOut["Grundstueckdaten"]["Zentrum"] . "</li>" . chr(10);
	}
	
	if ($DataOut["Flaechendaten"] != "") {
		//Get Points
		$DataExplode = explode("    ", $DataOut["Flaechendaten"]);
		$DebugInfo .=  "<li> Feldpunkt: " . chr(10);
		$DebugInfo .= "<ul>";
		foreach ($DataExplode as $Point) {
			$DataPoint = explode("," , $Point);
			if (count($DataPoint)>1) {
			  $DataA["geojson"]["features"]["0"]["geometry"]["coordinates"]["0"] = $DataPoint["0"];
			  $DataA["geojson"]["features"]["0"]["geometry"]["coordinates"]["1"] = $DataPoint["1"];
			  $DebugInfo .=  "<li>" . $DataA["geojson"]["features"]["0"]["geometry"]["coordinates"]["0"] . ", " . $DataA["geojson"]["features"]["0"]["geometry"]["coordinates"]["1"] . "</li>" . chr(10);
			}
		}
		$DebugInfo .= "</ul>";
		$DebugInfo .= "</li>" . chr(10);;
	}

	if (($DataOut["Flugweg"] != "") || ($DataOut["Grundstueckdaten"]["Startplatz"] != "")) {
		// Startpunkt
		$i = 1;
		//pre_R($DataA);
		if ($DataA["geojson"]["features"][strval($i)]["geometry"]["type"] == 6) {
			$DataPoint = explode("," , $DataOut["Grundstueckdaten"]["Startplatz"]);
			$DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["0"] = $DataPoint["0"];
			$DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["1"] = $DataPoint["1"];
			$DebugInfo .=  "<li> Startplatz: " . $DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["0"] . ", "  . $DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["1"] ."</li>" . chr(10);
			$i++;
		}
		
		$DataExplode = explode("    ", $DataOut["Flugweg"]);
		$DebugInfo .=  "<li> Flugweg: " . chr(10);
		$DebugInfo .= "<ul>";
		foreach ($DataExplode as $Point) {
			$DataPoint = explode("," , $Point);
			if (count($DataPoint)>1) {
				$DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["0"] = $DataPoint["0"];
				$DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["1"] = $DataPoint["1"];
				$DataA["geojson"]["features"][strval($i)]["geometry"]["properties"]["height"]= $DataPoint["2"];
				$DebugInfo .=  "<li>" . $DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["0"] . ", " . $DataA["geojson"]["features"][strval($i)]["geometry"]["coordinates"]["1"] . ", " . $DataA["geojson"]["features"][strval($i)]["geometry"]["properties"]["height"] . "</li>" . chr(10);
				$i++;
			}

		}
		$DebugInfo .= "</ul>";
		$DebugInfo .= "</li>" . chr(10);;
	}

	
	if (($DebugInfo != "") && ($DebugOutput) && false) {
		echo "<div style='background-color: #FDEDEC;'>". chr(10);
		echo "Debug Meldung Schreiben JSON: <br>". chr(10);
		echo $DebugInfo;
		echo "</div>". chr(10);
	}
	//Write hole dataset
	WriteJsonFile($Filename, $DataA);
}

function UpdateGruppengebietInJson($Filename, $JG)
{
	global $RKRKLocalLoactionMissionFiles, $RegionSettings;
	// update JSON fileinformation
	//echo "JSON FileLocation: " . $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename . "<br>";
	//echo "JSON FileName: " . $FilenameEntry . "<br>";
	$JsonFile = $Filename;
	//$JsonFile = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename;
	$JsonArray = ReadJsonFile($JsonFile);
	//pre_r($JsonArray);
	$JsonArray["pilotGroupAreaName"] = $RegionSettings[$JG]["GroupAreaName"];
	$JsonArray["data"]["zoneId"] = $RegionSettings[$JG]["GroupAreaID"];
	$JsonArray["data"]["zoneName"] =null;
	//pre_R($RegionSettings);
	echo "Gruppe: " . $JG . " GroupAreaID: " . $RegionSettings[$JG]["GroupAreaID"] . "<br>";
	WriteJsonFile ($JsonFile, $JsonArray);
}


function UpdateFeldkatalogNummerInJson($Filename, $FeldkatalogID)
{
	global $RKRKLocalLoactionMissionFiles;
	// update JSON fileinformation
	//echo "JSON FileLocation: " . $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename . "<br>";
	//echo "JSON FileName: " . $FilenameEntry . "<br>";
	$JsonFile = $Filename;
	//$JsonFile = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename;
	$JsonArray = ReadJsonFile($JsonFile);
	//pre_r($JsonArray);
	$JsonArray["sharedFieldCatalog"]["catalogIdentifier"] = $FeldkatalogID;
	//pre_R($RegionSettings);
	echo "Feld: " . $FeldkatalogID . "<br>";
	WriteJsonFile ($JsonFile, $JsonArray);
}

// obsolete 05.03.2024
/*
function UpdateFilenameAndGenFeldIDInJson($Filename, $FilenameEntry, $JG)
{
global $RKRKLocalLoactionMissionFiles, $RegionSettings;
	// update JSON fileinformation
		//echo "JSON FileLocation: " . $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename . "<br>";
		//echo "JSON FileName: " . $FilenameEntry . "<br>";
		$JsonFile = $RKRKLocalLoactionMissionFiles.$RegionSettings[$JG]["Dir"]."/".$Filename;
		$JsonArray = ReadJsonFile($JsonFile);
		//pre_r($JsonArray);
		$JsonArray["name"] = $FilenameEntry;
		$JsonArray["originalName"] = $FilenameEntry;
		//pre_R($RegionSettings);
		//echo "JG: " . $JG . " GroupareaName: " . $RegionSettings[$JG]["GroupAreaName"] . "<br>";
		$JsonArray["sharedFieldCatalog"]["catalogIdentifier"]=explode("_",$Filename)[0];
		$JsonArray["data"]["zoneId"] = $RegionSettings[$JG]["GroupAreaID"];
		$JsonArray["data"]["zoneName"] = null;
		$JsonArray["pilotGroupAreaName"] = $RegionSettings[$JG]["GroupAreaName"];
		$JsonArray["fileNameComplete"] = $FilenameEntry;
		$JsonArray["fileNameCompleteForExport"] = $FilenameEntry;
		$JsonArray["fileName"] = $FilenameEntry;
		//pre_r($JsonArray);
		WriteJsonFile ($JsonFile, $JsonArray);
		
}
*/

Function SetFeldAndFilenameInJson(&$JsonData, $FilenameEntry) {
	$FeldNamenArray = GetMeaningFilename($FilenameEntry);
	//pre_R($FeldNamenArray); 
	if (!(array_key_exists(0, $FeldNamenArray))) {
		//not RKRK fileformat
		$Feldname = $FilenameEntry;
		$Dateiname = $FilenameEntry;
	} elseif ($FeldNamenArray[2]=="") { 
		// kein Flurname
		$Feldname = $FeldNamenArray[1] . "_" . $FeldNamenArray[3] . "_" . $FeldNamenArray[4] . "_" . $FeldNamenArray[5] . "_" . $FeldNamenArray[6]; 
		$Dateiname = $FeldNamenArray[0] . "_" . $Feldname; 

	} else {
		$Feldname = $FeldNamenArray[1] . "_" . $FeldNamenArray[2]. "_" . $FeldNamenArray[3] . "_" . $FeldNamenArray[4] . "_" . $FeldNamenArray[5] . "_" . $FeldNamenArray[6]; 
		$Dateiname = $FeldNamenArray[0] . "_" . $Feldname; 
	}
	//pre_r(explode("_", $JsonData["name"]));
	//echo "Feldname: " . $Feldname . " - Dateiname: " . " - " . $Dateiname . "<br>";
	$JsonData["name"] = $Feldname;
	$JsonData["originalName"] = $Feldname;
	$JsonData["fileName"] = $Feldname;
	$JsonData["fileNameComplete"] = $Dateiname;
	$JsonData["fileNameCompleteForExport"] = $Dateiname;		
}

function UpdateFilenameAndFeldIDInJson($Filename, $FilenameEntry, $OldJG, $NewJG, $FeldID, $absolutePath = '')
{
global 	$RKRKLocalLoactionMissionFiles, 
		$RegionSettings;

		
	// update JSON fileinformation
		//echo "JSON FileLocation: " . $RKRKLocalLoactionMissionFiles.$RegionSettings[$OldJG]["Dir"]."/".$Filename . "<br>";
		//echo "JSON FileName: " . $FilenameEntry . "<br>";
		//echo "OLDJG: " . $OldJG . "<br>";
		//echo "NEWJG: " . $NewJG . "<br>";
		$JsonFile = trim((string)$absolutePath);
		if ($JsonFile === '' || !is_file($JsonFile)) {
			$JsonFile = $RKRKLocalLoactionMissionFiles.$RegionSettings[$OldJG]["Dir"]."/".$Filename;
		}
		if (!is_file($JsonFile)) {
			$inField = $RKRKLocalLoactionMissionFiles.$RegionSettings[$OldJG]["Dir"]."/".$FeldID."/".$Filename;
			if (is_file($inField)) {
				$JsonFile = $inField;
			}
		}
		//echo "JsonFile Location: " . $JsonFile . "<br>";
		$JsonArray = ReadJsonFile($JsonFile);
		//pre_r($JsonArray);
		
		//pre_R($RegionSettings);
		//echo "JG: " . $NewJG . " GroupareaName: " . $RegionSettings[$NewJG]["GroupAreaName"] . "<br>";
		$JsonArray["sharedFieldCatalog"]["catalogIdentifier"]=$FeldID;
		$JsonArray["data"]["zoneId"] = $RegionSettings[$NewJG]["GroupAreaID"];
		$JsonArray["data"]["zoneName"] = null;
		$JsonArray["pilotGroupAreaName"] = $RegionSettings[$NewJG]["GroupAreaName"];

		SetFeldAndFilenameInJson($JsonArray, $FilenameEntry);
	    // replaced  with function 29.01.2026
		//$JsonArray["name"] = $FilenameEntry;
		//$JsonArray["originalName"] = $FilenameEntry;
		//$JsonArray["fileNameComplete"] = $FilenameEntry;
		//$JsonArray["fileNameCompleteForExport"] = $FilenameEntry;
		//$JsonArray["fileName"] = $FilenameEntry;
		//pre_r($JsonArray);
		
		WriteJsonFile ($JsonFile, $JsonArray);
		
}

function UpdateAdresseBauer($JG, $Filenames, $FeldID, $Adresse)
{
global $RKRKLocalLoactionMissionFiles, $RegionSettings;
	// update JSON fileinformation
	$FileList = explode(",",$Filenames);
	foreach ($FileList as $File) {
		$File = trim($File);
		if ($File === "") {
			continue;
		}
		$JsonFile = $RKRKLocalLoactionMissionFiles."/".$File;
		$JsonArray = ReadJsonFile($JsonFile);
		if (!is_array($JsonArray) || count($JsonArray) === 0) {
			continue;
		}
		if (!isset($JsonArray["data"]["owner"]) || !is_array($JsonArray["data"]["owner"])) {
			$JsonArray["data"]["owner"] = array();
		}
		if (!isset($JsonArray["data"]["owner"]["address"]) || !is_array($JsonArray["data"]["owner"]["address"])) {
			$JsonArray["data"]["owner"]["address"] = array();
		}
//		 [data] => Array
//        (
//            [owner] => Array
//                (
//                    [number] => 
//                    [externalNumber] => ZG 763
//                    [name] => Rickenbacher
//                    [firstname] => Thomas
//                    [address] => Array
//                        (
//                            [street] => Spiess 1
//                            [zip] => 6330
//                            [city] => Cham
//                            [country] => 
//                            [formatted] => Spiess 1, 6330 Cham, Schweiz
//                            [coordinates] => 
//                        )

//                    [email] => 
//                    [phone] => +41 76 445 40 40 (Neu ab 26.06.2026 OHNE SPACE)
//                    [notice] => Flurname: Spiess; Altes File: ZG 763 Spiess 1 22127 28625_0.585ha_9WP.json; Betrieb#: ZG 763; 
//                )

//            [hasPowerline] => 
//            [hasTrees] => 
//            [hasHeightDifference] => 
//            [hasFacility] => 
//        )
//=======================
		// update JSON data
		//echo "JSON Adresse (alt): " . $JsonArray["data"]["owner"]["address"]["formatted"] . "<br>";
		//pre_r($Adresse);
		$JsonArray["data"]["owner"]["externalNumber"] = $Adresse["address"]["externalNumber"];
		$JsonArray["data"]["owner"]["name"] = function_exists("rkrk_adr_clean_person_name_part")
			? rkrk_adr_clean_person_name_part($Adresse["address"]["name"])
			: $Adresse["address"]["name"];
		$JsonArray["data"]["owner"]["firstname"] = function_exists("rkrk_adr_clean_person_name_part")
			? rkrk_adr_clean_person_name_part($Adresse["address"]["firstname"])
			: $Adresse["address"]["firstname"];
		$JsonArray["data"]["owner"]["address"]["street"] = $Adresse["address"]["street"];
		$JsonArray["data"]["owner"]["address"]["zip"] =$Adresse["address"]["zip"];
		$JsonArray["data"]["owner"]["address"]["city"] =$Adresse["address"]["city"];
		$JsonArray["data"]["owner"]["address"]["country"] =$Adresse["address"]["country"];
		if (function_exists("rkrk_adr_build_json_formatted_address")) {
			$JsonArray["data"]["owner"]["address"]["formatted"] = rkrk_adr_build_json_formatted_address(
				$Adresse["address"]["street"],
				$Adresse["address"]["zip"],
				$Adresse["address"]["city"],
				$Adresse["address"]["country"]
			);
		} else {
			$JsonArray["data"]["owner"]["address"]["formatted"] =
						$JsonArray["data"]["owner"]["address"]["street"] . ", " .
						$JsonArray["data"]["owner"]["address"]["zip"] . " " .
						$JsonArray["data"]["owner"]["address"]["city"] . ", " .
						$JsonArray["data"]["owner"]["address"]["country"];
		}
					
		$JsonArray["data"]["owner"]["email"] = isset($Adresse["Mail"]) ? $Adresse["Mail"] : "";

		//26.06.2023 UAV Edtior does not support Phone with spaces or "/"
		$phoneRaw = isset($Adresse["Kontaktnummer"]) ? $Adresse["Kontaktnummer"] : "";
		$JsonArray["data"]["owner"]["phone"] = function_exists("rkrk_adr_format_phones_for_json")
			? rkrk_adr_format_phones_for_json($phoneRaw)
			: CleanFormatPhoneNumber($phoneRaw);
		if (isset($JsonArray["connectedPlanningData"]["pilotObject"]["profile"]["phone"])) {
			$JsonArray["connectedPlanningData"]["pilotObject"]["profile"]["phone"] = CleanFormatPhoneNumber($JsonArray["connectedPlanningData"]["pilotObject"]["profile"]["phone"]);
		}
		if (isset($JsonArray["contributor"]["phone"])) {
			$JsonArray["contributor"]["phone"] = CleanFormatPhoneNumber($JsonArray["contributor"]["phone"]);
		}
		//Debug output
//		echo "JSON name: " . $JsonArray["data"]["owner"]["name"] . "<br>";
//		echo "JSON firstname: " . $JsonArray["data"]["owner"]["firstname"] . "<br>";
//		echo "JSON street: " . $JsonArray["data"]["owner"]["address"]["street"] . "<br>";
//		echo "JSON zip: " . $JsonArray["data"]["owner"]["address"]["zip"] . "<br>";
//		echo "JSON city: " . $JsonArray["data"]["owner"]["address"]["city"] . "<br>";
//		echo "JSON country: " . $JsonArray["data"]["owner"]["address"]["country"] . "<br>";
//		echo "JSON Adresse: " . $JsonArray["data"]["owner"]["address"]["formatted"] . "<br>";
//		echo "JSON phone: " . $JsonArray["data"]["owner"]["phone"] . "<br>";
//		echo "JSON email: " . $JsonArray["data"]["owner"]["email"] . "<br>";
		//pre_r($JsonArray);
		WriteJsonFile ($JsonFile, $JsonArray);
	}
}


//$JsonData =  GetJSONData("../".$RKRKLocalLoactionMissionFiles.$TestJsonFile);
//pre_R($JsonData);
//UpdateJSONData($JsonData, "../".$RKRKLocalLoactionMissionFiles.$TestJsonFile);
/*
$Filename = "../".$RKRKLocalLoactionMissionFiles.$RegionSettings["21"]["Dir"]."/"."21-060_Wässeri-Oeko_537_50_CH_22.json";
$JsonData =  ReadJsonFile($Filename);
//pre_R($JsonData);
$JsonData["name"]= "21-017_Buechholz_2620_50_CH_22.json";
$JsonData["name"]= "21-060_";
$JsonData["fileNameComplete"]= "21-060_";
$JsonData["fileName"]= "21-060_";
$Filename = "../".$RKRKLocalLoactionMissionFiles.$RegionSettings["21"]["Dir"]."/"."21-999_Wässeri-Oeko_537_50_CH_22.json";
WriteJsonFile($Filename, $JsonData ); 
*/

function ChangeJSONforNewDrone(&$JsonArray,$TargetDrone,$SourceHeight,$TargetHeight,$SourceFilename,$TargetFilename) {
	global 	$DroneSettings;
	
	
	//Only working on Pilot2 drones
	$JsonArray["missionSettings"]["djiPilot2"]["droneType"] = $DroneSettings[$TargetDrone]["UAVDroneType"];
	$JsonArray["missionSettings"]["djiPilot2"]["height"] = $TargetHeight;
	//Change height in each point
	//pre_R($JsonArray["geojson"]["features"]);
	foreach ($JsonArray["geojson"]["features"] as $key => $entry) {
		if ($entry["properties"]["type"]==1) {
			//a waypoint
			//pre_R($entry["properties"]);
			if ($entry["properties"]["height"]??0==$SourceHeight) {
				//change only if source hight is confirmed
				$JsonArray["geojson"]["features"][$key]["properties"]["height"]=$TargetHeight;
				$JsonArray["geojson"]["features"][$key]["properties"]["_height"]=$TargetHeight;
			}	
		}	
	}
	SetFeldAndFilenameInJson($JsonArray, $TargetFilename);
    // replaced  with function 29.01.2026
	//$JsonArray["originalName"] = $TargetFilename;
	//$JsonArray["name"] = $TargetFilename;
	//$JsonArray["fileNameComplete"] = $TargetFilename . substr($JsonArray["fileNameComplete"], strlen($SourceFilename), strlen($JsonArray["fileNameComplete"])- strlen($SourceFilename)) ;
	//$JsonArray["fileNameCompleteForExport"] = $TargetFilename . substr($JsonArray["fileNameCompleteForExport"], strlen($SourceFilename), strlen($JsonArray["fileNameCompleteForExport"])- strlen($SourceFilename)) ;
	//$JsonArray["fileName"] = $TargetFilename;

	$JsonArray["copterProfile"]= "dji-pilot-2";
	if (in_array($TargetDrone,Array("30T","4T","4TD"))) {
		$JsonArray["missionSettings"]["djiPilot2"]["transitionalSpeed"] = 1;
		$JsonArray["missionSettings"]["djiPilot2"]["transitionalSpeedActive"] = false;
	}	
	//END Pilot 2 drones
}	

function CreateJSONforNewDrone($JsonFile, $SourceDrone, $TargetDrone, $persist = true) {
	global 	$RKRKLocalLoactionMissionFiles,
			$RKRKRelWebLocation,
			$RegionSettings, 
			$MissionDroneTypes, 
			$MaxFlightHeight,
			$tempfilelocation;

	//Read Soruce JSON File
	//$RKRKRelWebLocation="../../rkropen/";
	$SourceJsonFile = $RKRKLocalLoactionMissionFiles.trim($JsonFile);
	//echo "Source JSON File: " . $SourceJsonFile . "<br>";
	$JG= multiexplode(array("-","_"),basename($JsonFile))[0];
	//echo "JG: " . $JG . "<br>";
	$JsonArray = ReadJsonFile($SourceJsonFile);
	//pre_R($JsonArray["missionSettings"]["djiPilot2"]);
	//get FlightHeight
	$SourceHeight = $JsonArray["missionSettings"]["djiPilot2"]["height"];
	//using horizontal cam settings as default
	$TargetHeight = GetFlightHeightForAltDrone($SourceDrone, $TargetDrone, $SourceHeight, "IRCamAngle_H", 5);
	//echo "FlightHeights S-" . $SourceDrone . ": " . $SourceHeight . "  T-" . $TargetDrone . ": "  . $TargetHeight . "<br>";
	$MeaningFileName = GetMeaningFilename(basename($SourceJsonFile));
	//pre_r($MeaningFileName);
	$SourceFilename = basename($SourceJsonFile, ".json");
	//$TargetFilename = $MeaningFileName[0] .  "_" . $MeaningFileName[7] . "_" . $TargetHeight . "_" . $MeaningFileName[5] . "_" . $MeaningFileName[6];
	$TargetFilename = ChangeHeightInFilename($SourceJsonFile, $TargetHeight, $TargetDrone, false);
	$TargetJsonFile = $RKRKRelWebLocation.$MissionDroneTypes[$TargetDrone]["MissionLocation"].$RegionSettings[$JG]["Dir"]."/".trim($TargetFilename).".json";
	//echo "Target JSON File: ". $TargetJsonFile . "<br>";
	//echo "Filename S: " . $SourceFilename ." T: " . $TargetFilename . "<br>";
	//echo "Filelcation S: " . $MissionDroneTypes[$SourceDrone]["MissionLocation"] ." T: " . $MissionDroneTypes[$TargetDrone]["MissionLocation"] . "<br>";
	
	//Changes JSON Content for New Drone
	if (($SourceDrone!="M2") and ($TargetDrone!="M2")) {
		ChangeJSONforNewDrone($JsonArray,$TargetDrone,$SourceHeight,$TargetHeight,$SourceFilename,$TargetFilename);
	}
	if (!$persist) {
		$out = rtrim(str_replace('\\', '/', (string)$tempfilelocation), '/') . '/' . trim($TargetFilename) . '.json';
		WriteJsonFile($out, $JsonArray);
		return $out;
	}
	WriteJsonFile ($TargetJsonFile, $JsonArray);
	return $TargetFilename.".json";
}


function KopiereFeldDaten(&$Source, &$Target) {
	$Target["FeldID"] 			= $Source["FeldID"];
	$Target["JG"] 				= $Source["JG"];
	$Target["Feldname"] 		= $Source["Feldname"];
	$Target["Flurname"] 		= $Source["Flurname"];
	$Target["GrundstueckID"] 	= $Source["GrundstueckID"];
	$Target["Flugzeit"] 		= $Source["Flugzeit"];
	$Target["Flaeche"] 			= $Source["Flaeche"];
	$Target["Wegpunkte"] 		= $Source["Wegpunkte"];
	$Target["Distanz"] 			= $Source["Distanz"];
	$Target["Gemeinde"] 		= $Source["Gemeinde"];
	$Target["Betrieb"] 			= $Source["Betrieb"];
	$Target["Freigabe"] 		= $Source["Freigabe"];
	$Target["Warnung"] 			= $Source["Warnung"];
	$Target["Jaeger"] 			= $Source["Jaeger"];
	$Target["Bemerkung"] 		= $Source["Bemerkung"];
	//$Target["Flug"] 			= $Source["Flug"];
	//$Target["Funde"] 			= $Source["Funde"];
	//$Target["StdFlughohe"] 	= $Source["StdFlughohe"];
	$Target["Bauer"] 			= $Source["Bauer"];
	$Target["Notiz"] 			= $Source["Freigabe"];
	$Target["Grundstueckdaten"]["Startplatz"]  		= $Source["Grundstueckdaten"]["Startplatz"] ;
	$Target["Grundstueckdaten"]["Zentrum"]  		= $Source["Grundstueckdaten"]["Zentrum"] ;
	$Target["Flaechendaten"] 	= $Source["Flaechendaten"];
	$Target["Flugweg"] 			= $Source["Flugweg"];
	$Target["Export"] 			= $Source["Export"];
	$Target["Erstellt"] 		= $Source["Erstellt"];
	$Target["Update"] 			= $Source["Update"];
	$Target["Aenderung"] 		= date("m.d.Y"); ;
	//pre_r($Source);	
	//pre_r($Target);
}

function VergleicheFeldDaten($Source, $Target) {
	$Vergleich = true;
	$val = function ($row, $key) {
		return (is_array($row) && array_key_exists($key, $row)) ? $row[$key] : null;
	};
	$nested = function ($row, $key, $sub) {
		if (!is_array($row) || !isset($row[$key]) || !is_array($row[$key]) || !array_key_exists($sub, $row[$key])) {
			return null;
		}
		return $row[$key][$sub];
	};
	//echo " T: " .$Target["FeldID"] . " S: " .$Source["FeldID"] . "<br>" ;
	$Vergleich = $Vergleich && ($val($Target, "FeldID") == $val($Source, "FeldID"));
	//echo " T: " .$Target["JG"] . " S: " .$Source["JG"] . "<br>" ;
	$Vergleich = $Vergleich && ($val($Target, "JG") 				== $val($Source, "JG"));
	//echo " T: " .$Target["Feldname"] . " S: " .$Source["Feldname"] . "<br>" ;
	$Vergleich = $Vergleich && ($val($Target, "Feldname") 		== $val($Source, "Feldname"));
	//echo " T: " .$Target["Flurname"] . " S: " .$Source["Flurname"] . "<br>" ;
	$Vergleich = $Vergleich and ($val($Target, "Flurname") 		== $val($Source, "Flurname"));
	$Vergleich = $Vergleich and ($val($Target, "GrundstueckID") 	== $val($Source, "GrundstueckID"));
	$Vergleich = $Vergleich and ($val($Target, "Flugzeit") 		== $val($Source, "Flugzeit"));
	$Vergleich = $Vergleich and ($val($Target, "Flaeche") 			== $val($Source, "Flaeche"));
	$Vergleich = $Vergleich and ($val($Target, "Wegpunkte") 		== $val($Source, "Wegpunkte"));
	$Vergleich = $Vergleich and ($val($Target, "Distanz") 			== $val($Source, "Distanz"));
	$Vergleich = $Vergleich and ($val($Target, "Gemeinde") 		== $val($Source, "Gemeinde"));
	$Vergleich = $Vergleich and ($val($Target, "Betrieb") 			== $val($Source, "Betrieb"));
	$Vergleich = $Vergleich and ($val($Target, "Freigabe") 		== $val($Source, "Freigabe"));
	$Vergleich = $Vergleich and ($val($Target, "Warnung") 			== $val($Source, "Warnung"));
	$Vergleich = $Vergleich and ($val($Target, "Jaeger") 			== $val($Source, "Jaeger"));
	$Vergleich = $Vergleich and ($val($Target, "Bemerkung") 		== $val($Source, "Bemerkung"));
	//$Vergleich = $Vergleich and ($Target["Flug"] 			== $Source["Flug"]);
	//$Vergleich = $Vergleich and ($Target["Funde"] 			== $Source["Funde"]);
	//$Vergleich = $Vergleich and ($Target["StdFlughohe"] 	== $Source["StdFlughohe"]);
	$Vergleich = $Vergleich and ($val($Target, "Bauer") 			== $val($Source, "Bauer"));
	$Vergleich = $Vergleich and ($val($Target, "Notiz") 			== $val($Source, "Freigabe"));
	$Vergleich = $Vergleich and ($nested($Target, "Grundstueckdaten", "Startplatz")  		== $nested($Source, "Grundstueckdaten", "Startplatz") );
	$Vergleich = $Vergleich and ($nested($Target, "Grundstueckdaten", "Zentrum")  		== $nested($Source, "Grundstueckdaten", "Zentrum") );
	$Vergleich = $Vergleich and ($val($Target, "Flaechendaten") 	== $val($Source, "Flaechendaten"));
	$Vergleich = $Vergleich and ($val($Target, "Flugweg") 			== $val($Source, "Flugweg"));
	return $Vergleich;
}


function UpdateXMLDateInOtherMission($JG, $FeldIDListe, &$Feldkatalog, $FileName, $SourceMission, $TargetMission, $TransferAllFilesToNewDrone) {
	global 	$RKRKMissionSelected,
			$RKRKRelWebLocation,
			$xmlFileDirectory,
			$RegionSettings,
			$RKRKFelderfileXML,
			$MissionDroneTypes;

	foreach (explode(",", $FeldIDListe) as $FeldID) {
		//echo $FeldID . "/" . $JG . ": Copy from " . $RKRKMissionSelected . " to " . $TargetMission . "<br>";
		//read TargetFeldkatalog
		$RelSourceMissionLocation = $RKRKRelWebLocation. $MissionDroneTypes[$RKRKMissionSelected]["MissionLocation"];
		$RelTargetMissionLocation = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"];
		$RelXMLFeldkatalog = $RelSourceMissionLocation  . $xmlFileDirectory.$RKRKFelderfileXML;
		$RelTargetXMLFeldkatalog = $RelTargetMissionLocation. $xmlFileDirectory.$RKRKFelderfileXML;
		$SystemInfo .= "Target Feldkatalog: " . $RelTargetXMLFeldkatalog . "<br>";
		$TargetFeldkatalog= GetFeldkatalog($RelTargetXMLFeldkatalog);
		//pre_R($Feldkatalog[$JG][$FeldID]);
		//Kopiere Feldinhalte
		KopiereFeldDaten($Feldkatalog[$JG][$FeldID], $TargetFeldkatalog[$JG][$FeldID]);
		//pre_R($TargetFeldkatalog[$JG][$FeldID]);
		if ($FileName=="") {
			$JSONFileListe= $Feldkatalog[$JG][$FeldID]["JasonFile"];
			$KMZFileListe= $Feldkatalog[$JG][$FeldID]["KMZFile"];
		} else {
			if (pathinfo($FileName, PATHINFO_EXTENSION)=="json")
				$JSONFileListe= $RegionSettings[$JG]["Dir"]."/".$FileName;
				$KMZFileListe="";
			if (pathinfo($FileName, PATHINFO_EXTENSION)=="kmz")
				$KMZFileListe= $RegionSettings[$JG]["Dir"]."/".$FileName;
				$JSONFileListe="";
		}
		//echo "JSONFileListe: " . $JSONFileListe . "<br>";
		//echo "KMZFileListe: " . $KMZFileListe . "<br>";
		//JSON Filetransfer
		foreach (explode(",", $JSONFileListe) as $File) {
			//echo "Process JSON File: " . $File . "<br>";
			$NewFileList=explode($TargetFeldkatalog[$JG][$FeldID]["JasonFile"]);
			$NewFile = $RegionSettings[$JG]["Dir"]."/".CreateJSONforNewDrone($File, $RKRKMissionSelected, $TargetMission);
			//echo "File transferred: " . $File . " to " . $NewFile . "<br>";
			$NewFileList[] = $NewFile;
			if ($File<>$NewFile) {
				if (file_exists($RelTargetMissionLocation.$File)) {
					unlink($RelTargetMissionLocation.$File);
				}
			}
		}
		$TargetFeldkatalog[$JG][$FeldID]["JasonFile"] = implode(",",$NewFileList);
		
		foreach (explode(",", $KMZFileListe) as $File) {
			//echo "Process KMZ File: " . $File . "<br>";
			$NewFileList=explode($TargetFeldkatalog[$JG][$FeldID]["KMZFile"]);
			$NewFile = $RegionSettings[$JG]["Dir"]."/". CreateKMZforNewDrone($File, $RKRKMissionSelected, $TargetMission);
			//echo "File transferred: " . $File . " to " . $NewFile . "<br>";
			$NewFileList[] = $NewFile;
			if ($File<>$NewFile) {
				if (file_exists($RelTargetMissionLocation.$File)) {
					unlink($RelTargetMissionLocation.$File);
				}
			}
		}
		$TargetFeldkatalog[$JG][$FeldID]["KMZFile"] = implode(",",$NewFileList);
	
		if (($TransferAllFilesToNewDrone==true) or true) {
			//Set standard height
			$TargetFeldkatalog[$JG][$FeldID]["StdFlughohe"]= GetFlightHeightForAltDrone($RKRKMissionSelected, $TargetMission, $Feldkatalog[$JG][$FeldID]["StdFlughohe"]);

			//SuchfeldBild: Remove old and entry file if existing
			$FileListe = explode(",",$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]);
			$NewFileList = Array();
			foreach ($FileListe as $File) {
				$NewFile = $File;
				//echo "Process: " . $RelSourceMissionLocation.$File. " to " . $RelTargetMissionLocation.$NewFile. "<br>";
				copy($RelSourceMissionLocation.$File, $RelTargetMissionLocation.$NewFile);
				$NewFileList[] = $NewFile;
			}
			$TargetFeldkatalog[$JG][$FeldID]["SuchfeldBild"] = implode(",",$NewFileList);

			//FlugplanBild: Remove old and entry file if existing
			$FileListe = explode(",",$Feldkatalog[$JG][$FeldID]["FlugplanBild"]);
			$NewFileList = Array();
			foreach ($FileListe as $File) {
				$NewHeight = GetFlightHeightForAltDrone($RKRKMissionSelected, $TargetMission, GetMeaningFilename($File)[4], "IRCamAngle_H", 5);
				$NewFilename = ChangeHeightInFilename($File, $NewHeight, $TargetMission, true);
				$NewFile = $RegionSettings[$JG]["Dir"]."/". $NewFilename;
				//echo "Process: " . $RelSourceMissionLocation.$File. " to " . $RelTargetMissionLocation.$NewFile. "<br>";
				copy($RelSourceMissionLocation.$File, $RelTargetMissionLocation.$NewFile);
				if ($File<>$NewFile) {
					if (file_exists($RelTargetMissionLocation.$File)) {
						unlink($RelTargetMissionLocation.$File);
					}
				}
				$NewFileList[] = $NewFile;
			}
			$TargetFeldkatalog[$JG][$FeldID]["FlugplanBild"] = implode(",",$NewFileList);

		}
		$Feldkatalog[$JG][$FeldID]["Aenderung"]= date("d.m.Y");
		//pre_R($TargetFeldkatalog[$JG][$FeldID]);
		if (true) {
			SaveFlugkatalog($TargetFeldkatalog, true, $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"] . $xmlFileDirectory);
		}
	}
}

function DeleteXMLDateInOtherMission($JG, $FeldIDListe, &$Feldkatalog, $FileName, $SourceMission, $TargetMission, $TransferAllFilesToNewDrone) {
	global 	$RKRKMissionSelected,
			$RKRKRelWebLocation,
			$xmlFileDirectory,
			$RegionSettings,
			$RKRKFelderfileXML,
			$MissionDroneTypes;

	foreach (explode(",", $FeldIDListe) as $FeldID) {
		//echo $FeldID . "/" . $JG . ": Delete from" . $TargetMission . "<br>";
		//read TargetFeldkatalog
		$RelTargetMissionLocation = $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"];
		$RelTargetXMLFeldkatalog = $RelTargetMissionLocation. $xmlFileDirectory.$RKRKFelderfileXML;
		$SystemInfo .= "Target Feldkatalog: " . $RelTargetXMLFeldkatalog . "<br>";
		$TargetFeldkatalog= GetFeldkatalog($RelTargetXMLFeldkatalog);
		//pre_R($Feldkatalog[$JG][$FeldID]);
		//Lösche Feldinhalte
		unset($TargetFeldkatalog[$JG][$FeldID]);
		echo "Search files: " .$RelTargetMissionLocation.$RegionSettings[$JG]["Dir"]."/".$FeldID.'*' . "<br>";
		$files = glob($RelTargetMissionLocation.$RegionSettings[$JG]["Dir"]."/".$FeldID.'*'); // get all file names
		foreach($files as $file){ // iterate files
			if(is_file($file)) {
			//echo "Delete: " . $file . "<br>";
			unlink($file); // delete file
			}
		}
		//pre_R($TargetFeldkatalog[$JG][$FeldID]);
		if (true) {
			SaveFlugkatalog($TargetFeldkatalog, true, $RKRKRelWebLocation. $MissionDroneTypes[$TargetMission]["MissionLocation"] . $xmlFileDirectory);
		}
	}
}


////////////////////////////////////////////////
// conversion from M2 (Pilot 1) to M3 (Pilot 2)
////////////////////////////////////////////////

Function ReadJsonTemplate($Jsontemplate) {
	global 	$JsonTemplateLocation,
			$JsonTemplates;
	if (array_key_exists($Jsontemplate,$JsonTemplates)) {
		if ($JsonTemplates[$Jsontemplate]["active"]) {
			$JsonTemplate = ReadJsonFile($JsonTemplateLocation.$JsonTemplates[$Jsontemplate]["File"]);
			//pre_R($JsonTemplate);	
		}
	}
	
	$ReturnTemplate["ALL"] = $JsonTemplate;
	return ($JsonTemplate);
}

Function SplitJSONToStructure($JsonData, &$ConversionError="") {
	//define entry orders
	$ErrorNoSeaLevel=false;
	
	foreach ($JsonData as $key => $data) {
		$ReturnData["EntryOrders"][] = $key;
	}
	//echo "Copterprofile in JSON: " . $JsonData["copterProfile"] . "<br>";
	$ReturnData["Profile"]["copterProfile"]=$JsonData["copterProfile"];
	if ($JsonData["copterProfile"]=="dji") {
		$ReturnData["Profile"]["droneType"] = "other";
	}
	if ($JsonData["copterProfile"]=="dji-pilot") {
		$ReturnData["Profile"]["droneType"] = $JsonData["missionSettings"]["djiPilot"]["droneType"];
	}
	if ($JsonData["copterProfile"]=="dji-pilot-2") {
		$ReturnData["Profile"]["droneType"] = $JsonData["missionSettings"]["djiPilot2"]["droneType"];
	}

	//echo "Profile " . $JsonData["copterProfile"] . "<br>";
	
	switch  ($JsonData["copterProfile"]) {
		case "dji-pilot-2":
			if (array_key_exists("height", $JsonData["missionSettings"]["djiPilot2"])) {
				//pre_R($JsonData["missionSettings"]["djiPilot2"]);
				$ReturnData["BaseInformation"]["MissionHeight"] = $JsonData["missionSettings"]["djiPilot2"]["height"];
			}
			break;
		case "dji-pilot":
			if (array_key_exists("height", $JsonData["missionSettings"]["djiPilot"])) {
				//pre_R($JsonData["missionSettings"]["djiPilot"]);
				$ReturnData["BaseInformation"]["MissionHeight"] = $JsonData["missionSettings"]["djiPilot"]["height"];
			}
			break;
		case "dji":
			if (array_key_exists("height", $JsonData["geojson"]["features"][2]["properties"])) {
				//pre_R($JsonData["geojson"]["features"]);
				$ReturnData["BaseInformation"]["MissionHeight"] = $JsonData["geojson"]["features"][2]["properties"];
			}
			break;
	}
	//echo "Missionheight: " . $ReturnData["BaseInformation"]["MissionHeight"] . "<br>";
	
	if (array_key_exists("fileName", $JsonData)) {
		$ReturnData["BaseInformation"]["Filename"] = $JsonData["fileName"];
	}
	
	$ReturnData["missionSettings"] = $JsonData["missionSettings"];
	//identify features
	foreach ($JsonData["geojson"]["features"] as $key => $feature) {
		switch ($feature["properties"]["type"]) {
			case 1;
				//Punkt oder Feldumarandung
				if ($feature["geometry"]["type"]=="LineString") {
					//Feldumarandung
					$ReturnData["Feldrand"] = $feature;
					$ReturnData["Template"]["Feldrand"] = $feature;
					foreach ($feature["geometry"]["coordinates"] as $fkey => $point) {
						$ReturnData["Punkte"]["Feldrand"][$fkey]["lat"] = $feature["geometry"]["coordinates"][$fkey]["1"];
						$ReturnData["Punkte"]["Feldrand"][$fkey]["lng"] = $feature["geometry"]["coordinates"][$fkey]["0"];
					}
				}
				if ($feature["geometry"]["type"]=="Point") {
					//Wegkpunkt
					$ReturnData["Template"]["Wegpunkte"] = $feature;
					if (array_key_exists("aboveSea", $feature["properties"])) {
						$ReturnData["Punkte"]["Wegpunkte"][$key]["lat"] = $feature["properties"]["aboveSea"]["lat"];
						$ReturnData["Punkte"]["Wegpunkte"][$key]["lng"] = $feature["properties"]["aboveSea"]["lng"];
						$ReturnData["Punkte"]["Wegpunkte"][$key]["value"] = $feature["properties"]["aboveSea"]["value"];
					} else {
						// For Litchi Files which are directly downloaded from Litchi and UAV editor is not adding sealevel information
						// Below is a workaorund option to still convert it, but does add 0 m altiuted, which could lead to problems
						//20.08.2025 BHO: Better not to convert and answer with a appropriate error message
						//if (array_key_exists("coordinates", $feature["geometry"])) {
						//	//pre_R($feature["geometry"]["coordinates"]);
						//	$ReturnData["Punkte"]["Wegpunkte"][$key]["lat"] = $feature["geometry"]["coordinates"][1];
						//	$ReturnData["Punkte"]["Wegpunkte"][$key]["lng"] = $feature["geometry"]["coordinates"][0];
						//	$ReturnData["Punkte"]["Wegpunkte"][$key]["value"] = 0;
						//}
						$ErrorNoSeaLevel=true;
					}	
					$ReturnData["Punkte"]["Wegpunkte"][$key]["height"] = $feature["properties"]["height"];
					if (is_null($feature["properties"]["direction"])) {
						$ReturnData["Punkte"]["Wegpunkte"][$key]["direction"] = null;
					} else {
						$ReturnData["Punkte"]["Wegpunkte"][$key]["direction"] = round($feature["properties"]["direction"],1);
					}
					if ($JsonData["Profile"]["copterProfile"]="dji") {
						//take speed from dji-pilot Profile
						//speed in JSON Litchi WP alreay in in m/s
						if (array_key_exists("speed", ($feature["properties"] ))) {
							$ReturnData["Punkte"]["Wegpunkte"][$key]["speed"]=round($feature["properties"]["speed"],1);
						}	
					} else {
						//Pilot1 or Pilot2
						$ReturnData["Punkte"]["Wegpunkte"][$key]["speed"] = $feature["properties"]["speed"];
					}

				}
				break;
			case 6;
				//Startpunkt
				if ($feature["geometry"]["type"]=="Point") {
					//Wegkpunkt
					$ReturnData["Template"]["StartPunkt"] = $feature;
					//pre_R($feature);
					//pre_r(explode(",",$feature["geometry"]["coordinates"]));
					$ReturnData["Punkte"]["StartPunkt"]["lat"] =$feature["geometry"]["coordinates"][1];
					$ReturnData["Punkte"]["StartPunkt"]["lng"] = $feature["geometry"]["coordinates"][0];
				}
				break;
			case 9;
				//Parkplatz
				if ($feature["geometry"]["type"]=="Point") {
					//Wegkpunkt
					$ReturnData["Template"]["Parkplatz"] = $feature;
					$ReturnData["Punkte"]["Parkplatz"]["lat"] = $feature["geometry"]["coordinates"][1];
					$ReturnData["Punkte"]["Parkplatz"]["lng"] = $feature["geometry"]["coordinates"][0];
				}
				break;
		}	
	}
	$ReturnData["ALL"] = $JsonData;
	// Error handling
	if ($ErrorNoSeaLevel) {
		$ConversionError .= '<br><b>Hinweis:</b> Diese Datei wurde direkt von Litchi heruntergeladen und es fehlen wichtige Angaben für die erfolgreiche Konvertierung. ';
		$ConversionError .= 'Gehe folgendermassen vor, um eine gültige Datei zu erhalten: Öffne das Feld in UAV-Editor. Klicke auf einen Wegpunkt und wähle das Register "Alle Wegpunkte" an. Gib die gewünschte Flughöhe ein und aktiviere "Auto Höhenanpassung". Klicke auf Speichern und exportiere die Datei erneut als JSON.<br>';
	}

	return ($ReturnData);
}
	
Function ConvertToTemplateStructure (&$JsonData, &$JsonTemplate) {
	//pre_R($JsonTemplate);
	//pre_R($JsonData);
	
	
	foreach ($JsonTemplate["EntryOrders"] as $key => $TempData) {
		echo "Json Key: " . $key . "/ " . $TempData . "<br>";
		
		switch ($TempData) {
			//special case handling
			case "geojson":
				foreach ($TempData["features"] as $fkey => $fData) {
					//Feldumarandung
					if (($Data["geometry"]["type"]== "LineString") and
						($Data["properties"]["type"]== 1)) {
						//$ReturnJSON[$TempData]["features"][$fkey] = $JsonData[$TempData];
							
					}	
				}
				break;
			default:
				//copy data as is
				if (array_key_exists($TempData,$JsonData)) {
					$ReturnJSON[$TempData] = $JsonData[$TempData];
				}
		}	
	}
	
	Return ($JsonData);
}

Function CreateNewJSON(&$JsonData, &$JsonTemplate) {
	$OutputData=Array();
	
	foreach ($JsonData["EntryOrders"] as $key => $entry) {
		//echo "KeyEntry: " . $entry . "<br>";
		//pre_r($JsonData["ALL"][$entry]);
		if (array_key_exists("copterProfile",$JsonData)) {
			if ($JsonData["copterProfile"]=="dji-pilot-2") {
				// Pilot 2 (no structural changes required)
				$OutputData[$entry]=$JsonData["ALL"][$entry];
			}
		}
		
		if (in_array($JsonData["Profile"]["copterProfile"],array("dji-pilot","dji"))) {
			// Pilot 1 (adoption required)
			$SpecialFields= Array("copterProfile","missionSettings","geojson");
			//add data to outputfile
			if (in_array($entry,$SpecialFields)) {
				//echo "SpecialField: " . $entry . "<br>";
				//special data handling
				switch ($entry) {
					case "copterProfile":
						$OutputData[$entry] = "dji-pilot-2";
						break;
					case "missionSettings":
						foreach ($JsonData[$entry] as $mkey => $mdata) {
							//pre_R($mdata);
							if (true) {
								//1:+ copy of the date in the differnt controll setting
								$OutputData[$entry][$mkey]= $mdata;
							}
						}
						//special handling for specific data in MissionDroneTypes
						if ($JsonData["Profile"]["copterProfile"]=="dji-pilot") {
							//take speed from dji-pilot Profile
							$OutputData[$entry]["djiPilot2"]["speed"]=$JsonData[$entry]["djiPilot"]["speed"];
							//echo "Set travel Speed in WP to (dji-pilot) " . $OutputData[$entry]["djiPilot2"]["speed"] . "<br>";
						}
						// Litchi hat den speed im waypoint auch in m/s
						if ($JsonData["Profile"]["copterProfile"]=="dji") {
							//take speed from dji-pilot Profile
							//travel speed in km/h speed in m/s
							$OutputData[$entry]["djiPilot2"]["speed"]=round($JsonData[$entry]["dji"]["travelSpeed"]/3.6,1);
							//echo "Set travel Speed in WP to (dji) " . $OutputData[$entry]["djiPilot2"]["speed"] . "<br>";
						}
						break;
					case "geojson":
						//echo "In Field: " . $entry . "<br>";
						
						$i=0;

						//add Feldrand
						//-----------------------------
						//pre_R($JsonData["Punkte"]);
						if (array_key_exists("Feldrand", $JsonData["Punkte"])) {
							//echo "Feldranddaten hinzufügen <br>";
							$fkey = "features";
							$OutputData[$entry][$fkey][$i] = $JsonData["Template"]["Feldrand"];
							//delete existing entries
							unset($OutputData[$entry][$fkey][$i]["geometry"]["coordinates"]);
							//add new Feldrand
							foreach($JsonData["Punkte"]["Feldrand"] as $pkey => $pdata) {
								//echo "Punkt " . $pkey . "hinzfügen <br>";
								$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][$pkey][0] = $pdata["lng"];
								$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][$pkey][1] = $pdata["lat"];
							}
						}

						//add Startpunkt (if available)
						//-----------------------------
						if (array_key_exists("StartPunkt", $JsonData["Punkte"])) {
							$i++;
							//echo "Startpunktdaten hinzufügen <br>";
							$OutputData[$entry][$fkey][$i] = $JsonTemplate["Template"]["StartPunkt"];
							$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][0] = $JsonData["Punkte"]["StartPunkt"]["lng"];
							$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][1] = $JsonData["Punkte"]["StartPunkt"]["lat"];
						}
						//add Parkplatz (if available)
						//-----------------------------
						if (array_key_exists("Parkplatz", $JsonData["Punkte"])) {
							$i++;
							//echo "Parkplatzdaten hinzufügen <br>";
							$OutputData[$entry][$fkey][$i] = $JsonTemplate["Template"]["Parkplatz"];
							$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][0] = $JsonData["Punkte"]["Parkplatz"]["lng"];
							$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][1] = $JsonData["Punkte"]["Parkplatz"]["lat"];
						}

						//add Wegpunkte
						//-----------------------------
						if (array_key_exists("Wegpunkte", $JsonData["Punkte"])) {
							//pre_R($JsonData["Template"]["Wegpunkte"]);
							//$OutputData[$entry][$fkey][$i] = $JsonTemplate["Template"]["Wegpunkte"];
							foreach ($JsonData["Punkte"]["Wegpunkte"] as $wkey => $wdata) {
								$i++;
								$OutputData[$entry][$fkey][$i] = $JsonTemplate["Template"]["Wegpunkte"];
								//echo "Wegpunktdaten hinzufügen " . $i. "/entry " . $entry . "/fkey " .$fkey."/i " .$i." <br>";
								//pre_R($OutputData[$entry][$fkey][$i]);
								if (array_key_exists("lng", $wdata)) {
									//echo "lng " . $OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["lng"] . " set to " . $wdata["lng"]. "<br>";
									$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][0] = $wdata["lng"];
									$OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["lng"] = $wdata["lng"];
									//pre_r($OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["lng"]);
								}	
								if (array_key_exists("lat", $wdata)) {
									//echo "lat " . $OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["lat"] . " set to " .$wdata["lat"]. "<br>";
									$OutputData[$entry][$fkey][$i]["geometry"]["coordinates"][1] = $wdata["lat"];
									$OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["lat"] = $wdata["lat"];
								}	
								if (array_key_exists("value", $wdata)) {
									//echo "aboveSea " . $OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["value"] . " set to " .$wdata["value"]. "<br>";
									$OutputData[$entry][$fkey][$i]["properties"]["aboveSea"]["value"] = $wdata["value"];
								}	
								$OutputData[$entry][$fkey][$i]["properties"]["height"] = $wdata["height"];
								$OutputData[$entry][$fkey][$i]["properties"]["_height"] = $wdata["height"];
								$OutputData[$entry][$fkey][$i]["properties"]["direction"] = $wdata["direction"];
								if (array_key_exists("speed",  $wdata)) {
									$OutputData[$entry][$fkey][$i]["properties"]["speed"] = $wdata["speed"];
								}	
								//pre_r($OutputData[$entry][$fkey][$i]["properties"]);
								//echo "END POINT  ".$i."<br>";
							}
						}
						
						break;
				}		
			} else {
				// 1:1 copy of Data
				$OutputData[$entry]=$JsonData["ALL"][$entry];
			}
		} else {
			
		}
	}	
	//pre_R($OutputData);
	Return ($OutputData);
}



Function JSONPilot1toPilot2($JsonData, $JsonTemplateName, $ToDrone, $FromDrone="") {
	global $DroneSettings;
	
	$ErrorMessage = "";
	
	$ReadJsonTemplate = ReadJsonTemplate($JsonTemplateName);
	//pre_r($ReadJsonTemplate);
	echo "Template - " ;
	$JsonTemplateStructure = SplitJSONToStructure($ReadJsonTemplate, $ErrorMessage);
	//pre_r($JsonTemplateStructure);
	echo "JsonData - " ;
	$JsonStructured = SplitJSONToStructure($JsonData, $ErrorMessage);
	$FileName = $JsonStructured["BaseInformation"]["Filename"];
	//pre_r($JsonData["geojson"]["features"]);
	//pre_r($JsonStructured["Profile"]);
	//pre_r($JsonStructured);
	$CopterProfile = $JsonStructured["Profile"]["copterProfile"];
	$DroneType = $JsonStructured["Profile"]["droneType"];
	//pre_r($JsonStructured["Punkte"]);
	//pre_r($JsonStructure["Punkte"]["StartPunkt"]);
	if ($CopterProfile == "dji-pilot-2") {
		$NewJson = $JsonData;
	} else {	
		$NewJson = CreateNewJSON($JsonStructured, $JsonTemplateStructure);
	}
	//pre_r($NewJson);
	//Drohnentyp ermitteln
	if (!(in_array($DroneType, array("other","matrice300","matrice350")))) {
		// Dronetype in JSON decisive, else need to get the input from Outside
		foreach ($DroneSettings as $key => $drone) {
			if ($JsonStructured["Profile"]["droneType"]==$drone["UAVDroneType"]) {
				$FromDrone = $key;
				//echo "Von Drohne: " . $FromDrone . "<br>";
				break;
			}
		}
	}	

	$BaseHeight = $JsonStructured["BaseInformation"]["MissionHeight"];
	$NewHeight = GetFlightHeightForAltDrone($FromDrone, $ToDrone, $BaseHeight, "IRCamAngle_H", 5);
	$ToFilename = ChangeHeightInFilename($FileName , $NewHeight, $ToDrone, false).".json";
	//echo "FromDrone: " . $FromDrone . " NewHeight: " . $NewHeight . " - New Filename: " . $ToFilename . "<br>"; 
	if ($NewHeight>0) { 
		ChangeJSONforNewDrone($NewJson,$ToDrone,$BaseHeight,$NewHeight,$Filename,$ToFilename);
		//pre_R($ChangedJson);
	} else {
		$NewJson="";
	}
	return($NewJson);
}



?>