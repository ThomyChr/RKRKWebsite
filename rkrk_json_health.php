<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >

<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_jsonfiles.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('rkrk_access_control.php'); 


	//check impropriate IP addresses accessing page
	CheckIPandExit();

	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	// ===========================================
	//Testoutput
	// ===========================================
	$Testoutput = true;
	$TestOutputDirectory = "../../rkropen/JsonOutputs/";
	$ExIntputDirectory = "../../rkropen/JsonExtern/";
	$CSVJsonHealthFile ="";

	$pagetitle = "Prüfen der Json Daten im Felddaten";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

	$OutputData = Array();

	//$EditAccess= GetAccess("TestOnly");
	$EditAccess= GetAccess("InternPilots");
	//$EditAccess= GetAccess("PilotPartners");

	// ===========================================
	// Vergleiche Feldkatalog mit FileListe
	// ===========================================

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

if (isset($_GET["FeldID"])) { 
	if ($_GET["FeldID"]!="") {
		$FeldIDtoUpdate= $_GET["FeldID"];
	} else {
		$FeldIDtoUpdate= "";
	}
} else {
	$FeldIDtoUpdate= "";
}


if (isset($_POST["FeldIDListe"])) { 
	if ($_POST["FeldIDListe"]!="") {
		$FeldIDListe= $_POST["FeldIDListe"];
	} else {
		$FeldIDListe= "";
	}
} elseif (isset($_GET["FeldIDListe"])) { 
	if ($_GET["FeldIDListe"]!="") {
		$FeldIDListe= $_GET["FeldIDListe"];
	} else {
		$FeldIDListe= "";
	}
} else {
	$FeldIDListe= "";
}


if (isset($_GET["UpdateFile"])) { 
	if ($_GET["UpdateFile"]!="") {
		$JsonFileName= $_GET["UpdateFile"];
	} else {
		$JsonFileName= "";
	}
} else {
	$JsonFileName= "";
}

if (isset($_GET["DeleteFile"])) { 
	if ($_GET["DeleteFile"]!="") {
		$DeleteJsonFile= $_GET["DeleteFile"];
	} else {
		$DeleteJsonFile= "";
	}
} else {
	$DeleteJsonFile= "";
}
	//echo "File: " . $DeleteJsonFile . " FeldID" . $FeldIDtoUpdate ."<br>";

if (isset($_GET["OutputFile"])) { 
	if ($_GET["OutputFile"]!="") {
		$JsonOutputFile= $_GET["OutputFile"];
	} else {
		$JsonOutputFile= "";
	}
} else {
	$JsonOutputFile= "";
}

if (isset($_GET["OutputDir"])) { 
	if ($_GET["OutputDir"]!="") {
		$OutputDir= $_GET["OutputDir"];
	} else {
		$OutputDir= "";
	}
} else {
	$OutputDir= "";
}

if (isset($_GET["Reg"])) {
	$RegSelected = $_GET["Reg"];
} else {
	$RegSelected = "";
}

//Ausgeschaltet
//$DataSet= "Korruption";
if (isset($_GET["DataSet"])) { 
	if ($_GET["DataSet"]!="") {
		$FileName= $_GET["DataSet"];
	} else {
		//$DataSet= "Korruption,Structure,Richtung,Pfad,Tspeed";
		//$DataSet= "Korruption";
		$DataSet= "";
		
	}
}

if (isset($_GET["Sortieren"])) { 
	$SortTable= $_GET["Sortieren"];
} else {
	$SortTable= "FeldID";
}


if ($DeleteJsonFile<>"") {
	//delete file name
	if (file_exists($DeleteJsonFile)) {
		//echo "Delete file: " . $DeleteJsonFile . "<br>";
		unlink($DeleteJsonFile);
	} else {
		//echo "File does not exist: " . $DeleteJsonFile . "<br>";
	}	
}	



//echo "Dataset: " . $DataSet . "<br>";
//echo "FeldID: " . $FeldIDtoUpdate . "<br>";
//echo "Input Filename: " . $JsonFileName . "<br>";
//echo "Output Filename: " . $JsonOutputFile . "<br>";

$SystemInfo="";
// ===================
// Standard Setting
// ===================
/*
    "missionSettings": {
        "dji": {
            "directionMode": "next-point",
            "endAction": "none",
            "pathModus": "curve",
            "travelSpeed": 18,
            "maxFlySpeed": 30,
            "photoCaptureInterval": 0,
            "repeat": 0,
            "version": 0,
            "unused": 0,
            "curveSize": 40,
            "gimbal": "none",
            "photoCaptureType": "m",
            "direction": null
        },
*/
$RKRKDefaultHeight = 60;
$RKRKLowerHeight = 50;
$RKRKUpperHeight = 60;
$RKRKDefaultTravelSpeed = 18;
$RKRKDefaultFlightSpeed = 18;
//$RKRKStandardPointCurveSize = 0.2;
$RKRKStandardPointCurveSize = 5;
$RKRKDefaultCurveSize = 40;
$RKRKDefaultDJI2TravelSpeed = 5;
$RKRKDefaultDJI2TravelHeight =60;
$RKRKDefaultDJI2SafeHeight =20; // prüfen, ob dies zu einem Automatischen Start führt.
$RKRKDefaultDJI2CurveSize=5; // no impact to curve size, TC 13.08.2023
$RKRKDefaultDJI2CurveTreshold=45; // Maximale Turndistance in % of the distance between two points, TC 03.01.2024
$RKRKDefaultDJI2CopterProfile = "dji-pilot-2";
$RKRKDefaultDJI2easyModeForm = true;  //easy form  
$RKRKDefaultDJI2easyModeSpeed = (float)$RKRKDefaultDJI2TravelSpeed;  //m/s  
$RKRKDefaultDJI2DroneType = "mavic3T";
$RKRKDefaultDJI2WayPointType = "coordinateTurn";
$RKRKDefaultDJI2HeightMode = "relativeToStartPoint";
$RKRKDefaultDJI2PointgimbalAngle = -90;
$RKRKDefaultDJI2PointSpeed = (float)$RKRKDefaultDJI2TravelSpeed;   //m/s  
$RKRKDefaultDJI2PointHeight = $RKRKDefaultDJI2TravelHeight;
$RKRKDefaultDJI2PointCurve = $RKRKDefaultDJI2CurveSize;  // no impact to curve size, TC 13.08.2023
$RKRKDefaultDJI2Pointrotation = "clockwise";
$RKRKDefaultDJI2PointuseMissionDirectionMode = true;
$RKRKDefaultDJI2PointuseMissionWaypointType = true;
$RKRKDefaultDJI2PointuseMissionSpeed = true;
$RKRKDefaultDJI2PointuseMissionHeight = true;
$RKRKDefaultDJI2PointuseMissionYawDirection = true;
$RKRKDefaultDJI2PointuseMissionPathMode =true;
$RKRKDefaultDJI2PointuseAutoHeight =true;

if ($RKRKDefaultDJI2PointuseMissionDirectionMode) 
	$RKRKDefaultDJI2PointuseMissionDirectionModeValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionDirectionModeValue = "Nein";

if ($RKRKDefaultDJI2PointuseMissionWaypointType) 
	$RKRKDefaultDJI2PointuseMissionWaypFelointTypeValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionWaypointTypeValue = "Nein";

if ($RKRKDefaultDJI2PointuseMissionSpeed) 
	$RKRKDefaultDJI2PointuseMissionSpeedValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionSpeedValue = "Nein";

if ($RKRKDefaultDJI2PointuseMissionHeight) 
	$RKRKDefaultDJI2PointuseMissionHeightValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionHeightValue = "Nein";

if ($RKRKDefaultDJI2PointuseMissionYawDirection) 
	$RKRKDefaultDJI2PointuseMissionYawDirectionValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionYawDirectionValue = "Nein";

if ($RKRKDefaultDJI2PointuseMissionPathMode) 
	$RKRKDefaultDJI2PointuseMissionPathModeValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseMissionPathModeValue = "Nein";

if ($RKRKDefaultDJI2PointuseAutoHeight) 
	$RKRKDefaultDJI2PointuseAutoHeightValue = "Ja";
else	
	$RKRKDefaultDJI2PointuseAutoHeightValue = "Nein";


	$UpdateFields = Array(
		"old" 	=> Array("Field" => "old",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Altes JSON format</b> erkennen",
							"Options" => "Ja,Nein",
							"activ" => false
							),
		"Filename" 	=> Array("Field" => "Filename",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Type" => "",
							"Einheit" => "",
							"Text" => "<b>Dateiname</b> normalisieren",
							"Options" => "",
							"activ" => false),
		//"Struktur" 	=> Array("Field" => "Struktur",
		//					"Default" => "Nein",
		//					"StandardSetting" => "Nein",
		//					"Value" => "",
		//					"Einheit" => "",
		//					"Type" => "select",
		//					"Options" => "Ja,Nein",
		//					"activ" => false
		//					),
		"FlugStatistik" 	=> Array("Field" => "FlugStatistik",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Flugstatisik</b> einschalten",
							"Options" => "Ja,Nein",
							"activ" => true
							),
 		"KatalogNummer" 	=> Array("Field" => "KatalogNummer",
							"Default" => "",
							"StandardSetting" => "Ja",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Feldkatalognummer</b> setzen",
							"Options" => "Ja,Nein",
							"activ" => true
							),
 		"Feldname" 	=> Array("Field" => "Feldname",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Feldname</b> normalisieren (UAV 2026)",
							"Options" => "Ja,Nein",
							"activ" => true
							),
		"GruppenGebiet" 	=> Array("Field" => "GruppenGebiet",
							"Default" => "",
							"StandardSetting" => "Ja",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Gruppengebiet</b> gemäss Regionszuweisung setzen",
							"Options" => "Ja,Nein",
							"activ" => true
							),
		"Phone" 	=> Array("Field" => "Telefon",
							"Default" => "",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Telefonformat</b> normalisieren",
							"Options" => "Ja,Nein",
							"activ" => false
							),
/*Start Mavic II Missionsettings*/
		"Richtung" 	=> Array("Field" => "Richtung",
							"Default" => "next-point",
							"StandardSetting" => "Ja",
							"Value" => "",
							"Type" => "select",
							"Einheit" => "",
							"Text" => "<b>Flugrichtung Richtung</b> setzen (M2)",
							"Options" => "next-point,by-point,by-user",
							"activ" => ($RKRKMissionSelected=="M2")),
		"Pfad" 	=> Array("Field" => "Pfad",
							"Default" => "curve",
							"StandardSetting" => "Ja",
							"Value" => "",
							"Type" => "select",
							"Einheit" => "",
							"Text" => "<b>Wegpunkttyp auf Kurve</b> setzen (M2)",
							"Options" => "curve,straight",
							"activ" => ($RKRKMissionSelected=="M2")),
		"Curve" 	=> Array("Field" => "Curve",
							"Default" => $RKRKDefaultCurveSize,
							"StandardSetting" => "Ja",
							"Value" => "",
							"Type" => "input",
							"Einheit" => "%",
							"Text" => "<b>Kurvengrösse</b> setzen (M2)",
							"Options" => "",
							"activ" => ($RKRKMissionSelected=="M2")),
		"TravelSpeed" 	=> Array("Field" => "TravelSpeed",
							"Default" => $RKRKDefaultTravelSpeed,
							"StandardSetting" => "Ja",
							"Value" => "",
							"Type" => "input",
							"Einheit" => "km/h",
							"Text" => "<b>Fluggeschwindikgeit</b> setzen (M2)",
							"Options" => "",
							"activ" => ($RKRKMissionSelected=="M2")),
/*end Mavic II Missionsettings*/
/*Start Mavic M3T Missionsettings*/
		"3T" 	=> Array("Field" => "3T",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "M2 mit <b>M3T</b> Standardstrukutren ersetzen",
							"Options" => "Ja,Nein",
							"activ" => ($RKRKMissionSelected=="3T")),
		"Dji2WayPoint" 	=> Array("Field" => "Dji2WayPoint",
							"Default" => $RKRKDefaultDJI2WayPointType,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2WayPointType,
							"Type" => "select",
							"Einheit" => "",
							"Text" => "<b>Wegpunkttyp/WayPointType</b> wählen",
							"Options" => "coordinateTurn,toPointAndStopWithDiscontinuityCurvature,toPointAndStopWithContinuityCurvature,toPointAndPassWithContinuityCurvature,toPointAndPassWithContinuityCurvature2",
							//coordinateTurn: Koordinierte Drehnung überspringt den Wegpunkt 01
							//toPointAndStopWithDiscontinuityCurvature: Gerade Route Fluggerät stoppt 04
							//toPointAndStopWithContinuityCurvature: Gekrümmte Route Fluggerät stoppt 05
							//toPointAndPassWithContinuityCurvature: Gekrümmte Route Fluggerät setzt fort03
							//toPointAndPassWithContinuityCurvature2: Wendet vor dem Wegpunkt, fliegt durch 02

							
							"activ" => $CheckMAVIC3T),
		"Dji2Speed" 	=> Array("Field" => "Dji2Speed",
							"Default" => (float)$RKRKDefaultDJI2TravelSpeed,
							"StandardSetting" => "Ja",
							"Value" => "",
							"Type" => "input",
							"Einheit" => "m/s",
							"Text" => "<b>Fluggeschwindigkeit</b> setzen",
							"Options" => "",
							"activ" => ($RKRKMissionSelected=="3T")),
		"Dji2Heigth" 	=> Array("Field" => "Dji2Heigth",
							"Default" => $RKRKDefaultDJI2TravelHeight,
							"StandardSetting" => "Nein",
							"Value" => "",
							"Type" => "input",
							"Einheit" => "m",
							"Text" => "<b>Missionsflughöhe</b> setzen",
							"Options" => "",
							"activ" => ($RKRKMissionSelected=="3T")),
		"Dji2HeightMode" 	=> Array("Field" => "Dji2HeightMode",
							"Default" => $RKRKDefaultDJI2HeightMode,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2HeightMode,
							"Type" => "select",
							"Einheit" => "",
							"Text" => "<b>Höhenmodus</b> relativ zum Startpunkt einschalten",
							"Options" => "relativeToStartPoint",
							"activ" => ($RKRKMissionSelected=="3T")),
		"Dji2Curve" 	=> Array("Field" => "Dji2Curve",
							"Default" => (float)$RKRKDefaultDJI2CurveSize,
							"StandardSetting" => "Ja",
							"Value" => (float)$RKRKDefaultDJI2CurveSize,
							"Type" => "input",
							"Einheit" => "m",
							"Text" => "<b>Kurvendistanz</b> setzen",
							"Options" => "",
							"activ" => ($RKRKMissionSelected=="3T")),
		"Dji2Drone" 	=> Array("Field" => "Dji2Drone",
							"Default" => $RKRKDefaultDJI2DroneType,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2DroneType,
							"Type" => "select",
							"Einheit" => "",
							"Text" => "<b>Drohnentyp</b> auswählen",
							"Options" => implode(",", array_keys($JsonDrohnentyp)), // "mavic3T,mavic3M,mavic3E,matrice30,matrice30T,matrice300",
							"activ" => ($RKRKMissionSelected=="3T")),
		"MissionSpeed" 	=> Array("Field" => "MissionSpeed",
							"Default" => $RKRKDefaultDJI2PointuseMissionSpeedValue,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2PointuseMissionSpeedValue,
							"Einheit" => "",
							"Type" => "select",
							"Options" => "Ja,Nein",
							"Text" => "<b>Missionsgeschwindikgeit  in allen Punkten",
							"activ" => ($RKRKMissionSelected=="3T")),
		"MissionHeight" 	=> Array("Field" => "MissionHeight",
							"Default" => $RKRKDefaultDJI2PointuseMissionHeightValue,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2PointuseMissionHeightValue,
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Missionshöhe</b> (in allen Punkten) setzen",
							"Options" => "Ja,Nein",
							"activ" => ($RKRKMissionSelected=="3T")),
		"MissionWaypointType" 	=> Array("Field" => "MissionWaypointType",
							"Default" => $RKRKDefaultDJI2PointuseMissionPathModeValue,
							"StandardSetting" => "Ja",
							"Value" => RKRKDefaultDJI2PointuseMissionPathModeValue,
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Missions Flugpfad</b> (in allen Punkten) setzen",
							"Options" => "Ja,Nein",
							"activ" => ($RKRKMissionSelected=="3T")),
		"MissionDirection" 	=> Array("Field" => "MissionDirection",
							"Default" => $RKRKDefaultDJI2PointuseMissionYawDirectionValue,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2PointuseMissionYawDirectionValue,
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Missionsflugrichtung</b> (in allen Punkten) setzen",
							"Options" => "Ja,Nein",
							"activ" => ($RKRKMissionSelected=="3T")),
		"AutoHeight" 	=> Array("Field" => "AutoHeight",
							"Default" => $RKRKDefaultDJI2PointuseAutoHeightValue,
							"StandardSetting" => "Ja",
							"Value" => $RKRKDefaultDJI2PointuseAutoHeightValue,
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Autohöhe</b> (in allen Punkten) setzen",
							"Options" => "Ja,Nein",
							"activ" => ($RKRKMissionSelected=="3T")),
 		"EasyMode" 	=> Array("Field" => "EasyMode",
							"Default" => "Ja",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "select",
							"Text" => "<b>Einfacher</b> Modus",
							"Options" => "Ja,Nein",
							"activ" => true
							),
		"StartPtHeight" 	=> Array("Field" => "StartPktHeight",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Starthöhe</b> gleich wie Missionsflughöhe",
							"Options" => "Ja,Nein",
							"activ" => false),
		"FlightHeight" 	=> Array("Field" => "FlightHeight",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "",
							"Options" => "Ja,Nein",
							"activ" => false),
		"NoReturnPoint" 	=> Array("Field" => "NoReturnPoint",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Einheit" => "",
							"Type" => "",
							"Text" => "<b>Endpunktflughöhe</b> gleich wie Missionsflughöhe (kein Rückflugpunkt)",
							"Options" => "Ja,Nein",
							"activ" => false),
/*End Mavic M3T Missionsettings*/
		"FlightTime" 	=> Array("Field" => "FlightTime",
							"Default" => "",
							"StandardSetting" => "Nein",
							"Value" => "",
							"Type" => "enable",
							"Einheit" => "",
							"Text" => "<b>Flugzeit</b>",
							"Options" => "",
							"activ" => false),
		"h_1-h_n" 	=> Array("Field" => "h_1-h_n",
							"Default" => $RKRKDefaultHeight,
							"Value" => "",
							"StandardSetting" => "Nein",
							"Type" => "input",
							"Einheit" => "m",
							"Text" => "<b>Flughöhen pro Punkt, Missionsflughöhe in [.. ]</b>",
							"Options" => "",
							"activ" => false),
		"crv_1-crv_n" 	=> Array("Field" => "crv_1-crv_n",
							"Default" => $RKRKStandardPointCurveSize,
							"Value" => "",
							"StandardSetting" => "Nein",
							"Type" => "input",
							"Einheit" => "m",
							"Text" => "",
							"Text" => "<b>Kurvendistanzen pro Punkt, Missionsurvendistanzen in [.. ]</b>",
							"activ" => false),
		"ChangeDatum" 	=> Array("Field" => "ChangeDatum",
							"Default" => "",
							"StandardSetting" => "",
							"Value" => "",
							"Type" => "",
							"Einheit" => "",
							"Text" => "",
							"Text" => "letztes <b>Änderungsdatum</b>",
							"activ" => false),
	);





/*Foreach ($UpdateFields as $key => $data) {
	$HealthData["Ok"] = false;
	$HealthData["Value"] = "";
}
*/
$RKRKJsonStandardSettings = Array(
		"missionSettings" => Array(
			"dji" => Array(
				"directionMode" => "next-point",
				"endAction" => "none",
				"pathModus" => "curve",
				"travelSpeed" => $RKRKDefaultTravelSpeed,
				"maxFlySpeed" => $RKRKDefaultFlightSpeed ,
//				"autoHeight" => true,
				"gimbal"=> "none",
				"curveSize" => $RKRKDefaultCurveSize,
				"direction" => null,
			),
		),
	);


/* singular point
                "properties": {
                    "type": 1,
                    "tolerance": 10,
                    "height": 50,
                    "pointDirection": null,
                    "direction": null,
                    "speed": 0,
                    "wait": 0,
                    "gimbal": "none",
                    "angle": 0,
                    "climb_rate": -1,
                    "wp_event_channel_value": 0,
                    "auto_trigger_time": -1,
                    "prefix": "P",
                    "direction_temp": null,
                    "altitudeMode": "relative-to-ground",
                    "altitudeAboveGroundLevel": 50,
                    "poiIndex": 0,
                    "float": 0.20000000298023224,
                    "turnMode": 0,
                    "maxReachTime": 0,
                    "repeatActions": 0,
                    "actions": [],
                    "autoHeight": true,
                    "gimbalMode": "none",
                    "recordingIntervalUnit": "m",
                    "recordingInterval": -1,
                    "curveSize": 0.20000000298023224,
                    "aboveSea": {
                        "value": 652,
                        "lat": 47.252251,
                        "lng": 8.478709
                    },
                    "_height": 50
*/


$RKRKJsonStandardPointSettings = Array(
		"point" => Array(
			"properties" => Array(
				"type" => 1,
				"tolerance" => 10,
				"height" => $RKRKDefaultHeight,
				"pointDirection" => null,
				"direction" => null,
				"speed" => 0,
				"wait" => 0,
				"gimbal" => "none",
				"angle" => 0,
				"climb_rate" => -1,
				"wp_event_channel_value" => 0,
				"auto_trigger_time" => -1,
				"prefix" => "P",
				"direction_temp" => null,
				"altitudeMode" => "relative-to-ground",
				"altitudeAboveGroundLevel" => $RKRKDefaultHeight,
				"poiIndex" => 0,
				"float" => 0.20000000298023224,
				"turnMode" => 0,
				"maxReachTime" => 0,
				"repeatActions" => 0,
				"actions" => [],
				"autoHeight" => true,
				"gimbalMode" => "none",
				"recordingIntervalUnit" => "m",
				"recordingInterval" => -1,
				"curveSize" => 0,
				//"curveSize" => $RKRKStandardPointCurveSize,
				"turningDistance" => $RKRKStandardPointCurveSize,
				"_height" => $RKRKDefaultHeight,
			),
		),
	);

// djiPilot2 (MAVIC 3T standard)      
/*
		"djiPilot2": {
            "easyModeForm": true, //Feb 2026
            "easyModeSpeed": 5,   //Feb 2026
            "droneType": "mavic3T",
            "directionMode": "followWayline",
            "endAction": "noAction",
            "pathModus": "straight",
            "speed": 5,
            "height": 50,
            "takeOffSecurityHeight": 20,
            "curveSize": 0,
            "gimbal": "none",
            "powerSavingMode": false,
            "dronePayload1": null,
            "dronePayload2": null,
            "gimbalControl": "manuel",
            "dronePayload3": null,
            "flightHeightMode": "relativeToStartPoint",
            "flyToWaylineMode": null,
            "waypointType": "toPointAndPassWithContinuityCurvature",
            "dronePayloadSettings1MeasurementMethod": null,
            "dronePayloadSettings1DistortionCompensation": null,
            "dronePayloadSettings1ReturnMode": null,
            "dronePayloadSettings1Samplingrate": null,
            "dronePayloadSettings1ScanningMode": null,
            "dronePayloadSettings1RgbColor": null,
            "dronePayloadSettings2MeasurementMethod": null,
            "dronePayloadSettings2DistortionCompensation": null,
            "dronePayloadSettings2ReturnMode": null,
            "dronePayloadSettings2Samplingrate": null,
            "dronePayloadSettings2ScanningMode": null,
            "dronePayloadSettings2RgbColor": null,
            "dronePayloadSettings3MeasurementMethod": null,
            "dronePayloadSettings3DistortionCompensation": null,
            "dronePayloadSettings3ReturnMode": null,
            "dronePayloadSettings3Samplingrate": null,
            "dronePayloadSettings3ScanningMode": null,
            "dronePayloadSettings3RgbColor": null,
            "savePictureZoom": null,
            "savePictureWide": null,
            "savePictureIR": null
        },
*/

$RKRKJsonM3TStandardSettings = Array(
		"djiPilot2" => Array(
            "easyModeForm" => $RKRKDefaultDJI2easyModeForm, 
            "easyModeSpeed" => $RKRKDefaultDJI2easyModeSpeed,
            "droneType"=> $RKRKDefaultDJI2DroneType,
            "directionMode"=> "followWayline",
            "endAction"=> "noAction",
            "pathModus"=> "straight",
            "speed"=> (float)$RKRKDefaultDJI2TravelSpeed,
            "height"=> (float)$RKRKDefaultDJI2TravelHeight,
            "takeOffSecurityHeight"=> $RKRKDefaultDJI2SafeHeight,
            "curveSize"=> (float)$RKRKDefaultDJI2CurveSize,
            "gimbal"=> "none",
            "powerSavingMode"=> false,
            "dronePayload1"=> null,
            "dronePayload2"=> null,
            "gimbalControl"=> "manuel",
            "dronePayload3"=> null,
            "flightHeightMode"=> $RKRKDefaultDJI2HeightMode,
            "flyToWaylineMode"=> null,
            "waypointType"=> $RKRKDefaultDJI2WayPointType,
            "dronePayloadSettings1MeasurementMethod"=> null,
            "dronePayloadSettings1DistortionCompensation"=> null,
            "dronePayloadSettings1ReturnMode"=> null,
            "dronePayloadSettings1Samplingrate"=> null,
            "dronePayloadSettings1ScanningMode"=> null,
            "dronePayloadSettings1RgbColor"=> null,
            "dronePayloadSettings2MeasurementMethod"=> null,
            "dronePayloadSettings2DistortionCompensation"=> null,
            "dronePayloadSettings2ReturnMode"=> null,
            "dronePayloadSettings2Samplingrate"=> null,
            "dronePayloadSettings2ScanningMode"=> null,
            "dronePayloadSettings2RgbColor"=> null,
            "dronePayloadSettings3MeasurementMethod"=> null,
            "dronePayloadSettings3DistortionCompensation"=> null,
            "dronePayloadSettings3ReturnMode"=> null,
            "dronePayloadSettings3Samplingrate"=> null,
            "dronePayloadSettings3ScanningMode"=> null,
            "dronePayloadSettings3RgbColor"=> null,
            "savePictureZoom"=> null,
            "savePictureWide"=> null,
            "savePictureIR"=> null,
		),
	);


/* OLD 03.07.2024
$RKRKJsonM3TPointProperties = Array(
		"properties"=> Array(
			"prefix"=> "P",
			"cameraAngle"=> 0,
			"gimbalAngle"=> $RKRKDefaultDJI2PointgimbalAngle,
			"gimbalYaw"=> 0,
			"gimbalNod"=> 0,
			"cameraModeTime"=> 1,
			"directionType"=> "followWayline",
			"wpEvent"=> 0,
			"autoTrigger"=> 0,
			"climbRate"=> 0.1,
			"tolerance"=> 10,
			"rotation"=> "clockwise",
			"useMissionDirectionMode"=> $RKRKDefaultDJI2PointuseMissionDirectionMode,
			"direction"=> 0,
			"curveSize"=> 0,
			//"curveSize"=> $RKRKDefaultDJI2PointCurve,
			"useMissionPathMode"=> true,
			"useMissionYawDirection"=> false,
			"useMissionWaypointType"=> $RKRKDefaultDJI2PointuseMissionWaypointType,
			"speed"=> (float)$RKRKDefaultDJI2TravelSpeed,
			"useMissionSpeed"=> $RKRKDefaultDJI2PointuseMissionSpeed,
			"height"=> (float)$RKRKDefaultDJI2PointHeight,
			"useMissionHeight"=> (float)$RKRKDefaultDJI2PointuseMissionHeight,
			"type"=> 1,
			"pathMode"=> "straight",
			"turningDistance"=> $RKRKDefaultDJI2PointCurve,
			"aboveSea"=> Array (
				"value"=> 449,
				"lat"=> 47.22616,
				"lng"=> 8.452107
			),
		),
	);
*/

$RKRKJsonM3TPointProperties = Array(
		"properties"=> Array(
			"prefix"=> "P",
			"cameraAngle"=> 0,
			"gimbalAngle"=> $RKRKDefaultDJI2PointgimbalAngle,
			"gimbalYaw"=> 0,
			"gimbalNod"=> 0,
			"cameraModeTime"=> 1,
			"directionType"=> "followWayline",
			"wpEvent"=> 0,
			"autoTrigger"=> 0,
			"climbRate"=> 0.1,
			"tolerance"=> 10,
			"rotation"=> "clockwise",
			"useMissionDirectionMode"=> $RKRKDefaultDJI2PointuseMissionDirectionMode,
			"direction"=> 0,
			"curveSize"=> 0,
			//"curveSize"=> $RKRKDefaultDJI2PointCurve,
			"useMissionPathMode"=> $RKRKDefaultDJI2PointuseMissionPathMode,
			"useMissionYawDirection"=> $RKRKDefaultDJI2PointuseMissionYawDirection,
			"useMissionWaypointType"=> $RKRKDefaultDJI2PointuseMissionWaypointType,
			"speed"=> (float)$RKRKDefaultDJI2TravelSpeed,
			"useMissionSpeed"=> $RKRKDefaultDJI2PointuseMissionSpeed,
			"height"=> (float)$RKRKDefaultDJI2PointHeight,
			"useMissionHeight"=> (float)$RKRKDefaultDJI2PointuseMissionHeight,
			"type"=> 1,
			"pathMode"=> "straight",
			"turningDistance"=> $RKRKDefaultDJI2PointCurve,
			"aboveSea"=> Array (
				"value"=> 449,
				"lat"=> 47.22616,
				"lng"=> 8.452107
			),
			"autoHeight" => $RKRKDefaultDJI2PointuseAutoHeight,
			"_height" => (float)$RKRKDefaultDJI2PointHeight
		),
	);

$RKRKJsonM3TPointCurveProperties = Array(

		"properties"=> Array(
			"prefix"=> "P",
			"cameraAngle"=> 0,
			"gimbalAngle"=> $RKRKDefaultDJI2PointgimbalAngle,
			"gimbalYaw"=> 0,
			"gimbalNod"=> 0,
			"cameraModeTime"=> 1,
			"directionType"=> "followWayline",
			"wpEvent"=> 0,
			"autoTrigger"=> 0,
			"climbRate"=> 0.1,
			"tolerance"=> 10,
			"rotation"=> "clockwise",
			"useMissionDirectionMode"=> $RKRKDefaultDJI2PointuseMissionDirectionMode,
			"direction"=> 0,
			"curveSize"=> 0,
			//"curveSize"=> $RKRKDefaultDJI2PointCurve,
			"useMissionPathMode"=> true,
			"useMissionYawDirection"=> false,
			"useMissionWaypointType"=> false,
			"speed"=> (float)$RKRKDefaultDJI2TravelSpeed,
			"useMissionSpeed"=> $RKRKDefaultDJI2PointuseMissionSpeed,
			"height"=> (float)$RKRKDefaultDJI2PointHeight,
			"useMissionHeight"=> $RKRKDefaultDJI2PointuseMissionHeight,
			"type"=> 1,

			"pathMode"=> "straight",
			"autoHeight" => true,
			"yawDirection" => "followWayline",
			"waypointType" => "coordinateTurn",
			"turningDistance" => $RKRKDefaultDJI2PointCurve,
			"pathMode" => "straight",
			"alignmentAircraft" => "followBadArc",

			"aboveSea"=> Array (
				"value"=> 449,
				"lat"=> 47.22616,
				"lng"=> 8.452107
			),
			"autoHeight" => $RKRKDefaultDJI2PointuseAutoHeight,
			"_height" => $RKRKDefaultDJI2PointuseMissionHeight,
		),
	);

// ==================
// Funktionen für die Rehktitzrettung
// ==================

/*
function GetGeoData($RefPoint) {
	echo "RefPoint: " . $RefPoint ."<br>";
	echo "Gde: " . $GeoGde . "<br>";
	$GeoGde = GetGdeOfPOint($RefPoint);
	echo "Gde: " . $GeoGde . "<br>";
	$GeoJgd = GetJgdOfPOint($RefPoint);
	echo "Jgd: " . $GeoJgd . "<br>";
	$GeoFlgZ = GetFlgZOfPOint($RefPoint);
	echo "FlgZ: " . $GeoFlgZ . "<br>";
	$GeoWarnFlgZ = GetWarnungFlgZOfPOint($RefPoint);
	echo "Warnung FlgZ: " . $GeoWarnFlgZ . "<br>";
} 
*/
/*

Function UpdateArraySettings($SrcArray, $TargetArray) {
	foreach ($SrcArray as $key1 => $data1) {
		TargetArray[$key1] = 
		foreach ($data1 as $key2 => $data2) {
			foreach ($data2 as $key3 => $data3) {
				foreach ($data3 as $key4 => $data4) {
					foreach ($data4 as $key5 => $data5) {
					}
				}
			}
		}
	}
}
*/

function ReadAndCompareJsonData($JsonRefFile, $JsonDataFile) {
global $colorOKtext, $colorNOK ,$colorNOKtext ;

	$JsonRefData = ReadJsonFile($JsonRefFile);
	$JsonFileData = ReadJsonFile($JsonDataFile);
	
	//pre_R($JsonRefData);
	//pre_R($JsonFileData);
	
	echo "RefFile	: " . $JsonRefFile . "<br>";
	echo "DataFile : " . $JsonDataFile . "<br>";
	echo "<table>";
	foreach ($RKRKJsonStandardSettings as $key1 => $data1) {
		echo "<tr>";
		if (array_key_exists($key1,$JsonFileData)) {
			echo "<td>";
			$textcolor = $colorOKtext;
		} else {
			echo "<td bgcolor='" . $colorNOK . "'>";
			$textcolor = $colorNOKtext;
		}
		echo "<b>L1: <b>" . $key1 . "<br>";
		echo "</td>";
		echo "</tr>";
		foreach ($data1 as $key2 => $data2) {
			echo "<tr>";
			if (array_key_exists($key2,$JsonFileData[$key1])) {
				echo "<td>";
				$textcolor = $colorOKtext;
			} else {
				echo "<td bgcolor='" . $colorNOK . "'>";
				$textcolor = $colorNOKtext;
			}
			echo "    L 2: " . $key2 . "<br>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			foreach ($data2 as $key3 => $data3) {
				echo "<tr>";
				if (array_key_exists($key3,$JsonFileData[$key1][$key2])) {
					echo "<td>";
					$textcolor = $colorOKtext;
				} else {
					echo "<td bgcolor='" . $colorNOK . "'>";
					$textcolor = $colorNOKtext;
				}
				echo "    L 3: " . $key3 . "<br>";
				echo "</td>";
				echo "</tr>";
				foreach ($data3 as $key4 => $data4) {
					echo "<tr>";
					if (array_key_exists($key4,$JsonFileData[$key1][$key2][$key3])) {
						echo "<td>";
						$textcolor = $colorOKtext;
					} else {
						echo "<td bgcolor='" . $colorNOK . "'>";
						$textcolor = $colorNOKtext;
					}
					echo "    L 4: " . $key4 . "<br>";
					echo "</td>";
					echo "</tr>";
				}	
			}	
		}	


	}
	echo "</table>";
}


function UpdateJsonDataStructure(&$JsonData, $UpdateFields,$DataSet="", $JG="") {
	global 	$RKRKJsonStandardPointSettings, 
			$SystemInfo, 
			$RKRKDefaultDJI2CopterProfile,
			$RKRKJsonM3TStandardSettings, 
			$RKRKJsonM3TPointProperties, 
			$RKRKJsonM3TPointCurveProperties, 
			$RKRKDefaultDJI2PointCurve, 
			$RegionSettings,
			$RKRKDefaultDJI2PointuseMissionDirectionMode,
			$RKRKDefaultDJI2PointuseMissionWaypointType,
			$RKRKDefaultDJI2PointuseMissionSpeed,
			$RKRKDefaultDJI2PointuseMissionHeight,
			$RKRKDefaultDJI2PointuseMissionYawDirection,
			$RKRKDefaultDJI2PointuseMissionPathMode,
			$RKRKDefaultDJI2PointuseAutoHeight,
			$RKRKDefaultDJI2CurveTreshold,
			$JsonFile;
			
	$SystemInfo .= "Korrekturen :" . $DataSet . "<br>";
	//echo "DataSet: ". $DataSet;
	$DataArr = explode(",", $DataSet);
	//pre_R($DataArr);
	
	//------------------------------------
	// update points and structure in Json
	//------------------------------------
    //pre_R($DataArr);
	//foreach point
	//pre_r($JsonData["geojson"]["features"]);
	foreach ($JsonData["geojson"]["features"] as $key => $Point) {
		if ($JsonData["geojson"]["features"][$key]["properties"]["type"]!=6) {
/*
			if (in_array("Structure",$DataArr)) {

				// not startpoint  (25-04-2023)
				// not existing data anymore in new structure
				//      "wpEvent": 0,
				//      "cameraAngle": 0,
				//      "gimbalYaw": 0,
				//      "gimbalNod": 0,
				//      "cameraModeTime": 1,
				//      "autoTrigger": 0,
				//      "rotation": "clockwise",
				//      "directionIndex": 0,
				//      "directionType": "none",
				//      "climbRate": 0.1,
				unset($JsonData["geojson"]["features"][$key]["properties"]["wpEvent"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["cameraAngle"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["gimbalYaw"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["gimbalNod"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["cameraModeTime"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["autoTrigger"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["rotation"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["directionIndex"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["directionType"]);
				unset($JsonData["geojson"]["features"][$key]["properties"]["climbRate"]);
				// remaining data in new structure (25-04-2023)
				//      "prefix": "P",
				//      "direction": null,
				//      "curveSize": 0,
				//      "speed": 0,
				//      "height": "50",
				//      "tolerance": 10,
				//      "autoHeight": true,
				//      "aboveSea": {
				//          "value": 534,
				//          "lat": 47.233759,
				//          "lng": 8.48916
				//      },
				//      "recordingIntervalUnit": "s",
				//      "gimbalMode": "none",
				//      "type": 1,
				//      "_height": "50"

				
				//echo $key . "<br>";
				// existing
				$JsonData["geojson"]["features"][$key]["properties"]["prefix"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["prefix"];
				//take over values file
				$JsonData["geojson"]["features"][$key]["properties"]["direction"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["direction"];
				//take over values file
				//$JsonData["geojson"]["features"][$key]["properties"]["curveSize"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["curveSize"];
				//take over values file
				//$JsonData["geojson"]["features"][$key]["properties"]["speed"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["speed"];
				//take over values file
				//$JsonData["geojson"]["features"][$key]["properties"]["height"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["height"];
				$JsonData["geojson"]["features"][$key]["properties"]["height"]				= (float)$JsonData["geojson"]["features"][$key]["properties"]["height"];
				$JsonData["geojson"]["features"][$key]["properties"]["tolerance"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["tolerance"];
				$JsonData["geojson"]["features"][$key]["properties"]["autoHeight"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["autoHeight"];
				$JsonData["geojson"]["features"][$key]["properties"]["recordingIntervalUnit"] = $RKRKJsonStandardPointSettings["point"]["properties"]["recordingIntervalUnit"];
				$JsonData["geojson"]["features"][$key]["properties"]["gimbalMode"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["gimbalMode"];
				$JsonData["geojson"]["features"][$key]["properties"]["type"] 					= $RKRKJsonStandardPointSettings["point"]["properties"]["type"];
				//take over values file
				//$JsonData["geojson"]["features"][$key]["properties"]["_height"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["_height"];
				$JsonData["geojson"]["features"][$key]["properties"]["_height"]				= (float)$JsonData["geojson"]["features"][$key]["properties"]["_height"];
				//new
				$JsonData["geojson"]["features"][$key]["properties"]["wait"] 					= $RKRKJsonStandardPointSettings["point"]["properties"]["wait"];
				$JsonData["geojson"]["features"][$key]["properties"]["gimbal"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["gimbal"];
				$JsonData["geojson"]["features"][$key]["properties"]["angle"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["angle"];
				$JsonData["geojson"]["features"][$key]["properties"]["climb_rate"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["climb_rate"];
				$JsonData["geojson"]["features"][$key]["properties"]["wp_event_channel_value"] = $RKRKJsonStandardPointSettings["point"]["properties"]["wp_event_channel_value"];
				$JsonData["geojson"]["features"][$key]["properties"]["auto_trigger_time"] 	= $RKRKJsonStandardPointSettings["point"]["properties"]["auto_trigger_time"];
				$JsonData["geojson"]["features"][$key]["properties"]["pointDirection"] 		= $RKRKJsonStandardPointSettings["point"]["properties"]["pointDirection"];
				$JsonData["geojson"]["features"][$key]["properties"]["direction_temp"] 		= $RKRKJsonStandardPointSettings["point"]["properties"]["direction_temp"];
				$JsonData["geojson"]["features"][$key]["properties"]["altitudeMode"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["altitudeMode"];
				$JsonData["geojson"]["features"][$key]["properties"]["altitudeAboveGroundLevel"] = $RKRKJsonStandardPointSettings["point"]["properties"]["altitudeAboveGroundLevel"];
				$JsonData["geojson"]["features"][$key]["properties"]["poiIndex"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["poiIndex"];
				$JsonData["geojson"]["features"][$key]["properties"]["float"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["float"];
				$JsonData["geojson"]["features"][$key]["properties"]["turnMode"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["turnMode"];
				$JsonData["geojson"]["features"][$key]["properties"]["maxReachTime"] 			= $RKRKJsonStandardPointSettings["point"]["properties"]["maxReachTime"];
				$JsonData["geojson"]["features"][$key]["properties"]["repeatActions"] 		= $RKRKJsonStandardPointSettings["point"]["properties"]["repeatActions"];
				$JsonData["geojson"]["features"][$key]["properties"]["actions"] 				= $RKRKJsonStandardPointSettings["point"]["properties"]["actions"];
				$JsonData["geojson"]["features"][$key]["properties"]["recordingInterval"] 	= $RKRKJsonStandardPointSettings["point"]["properties"]["recordingInterval"];
				//pre_R($Point[$key]);
			}
*/
			if (
				//(in_array("Structure",$DataArr)) or 
				(in_array("Korruption",$DataArr))
				) {
				$JsonData["geojson"]["features"][$key]["properties"]["height"]				= (float)$JsonData["geojson"]["features"][$key]["properties"]["height"];
				$JsonData["geojson"]["features"][$key]["properties"]["_height"]				= (float)$JsonData["geojson"]["features"][$key]["properties"]["_height"];
				$SystemInfo .= "<li>Punkt: " . $key . " Korrigierte evenutelle korrupte Daten (height, _height) - " . $JsonData["geojson"]["features"][$key]["properties"]["height"];
			}
		}
		if(count(array_intersect(array("3T","Dji2Curve","Dji2Drone"), $DataArr)) > 0)
		{	
			//echo $JsonData["geojson"]["features"][$key]["properties"]["type"] . "<br>";
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//
				// Update to DJI2 structure for M3T
				/* Old structure for MavicII
					   "properties": {
							"type": 1,
							"tolerance": 10,
							"height": 15,
							"pointDirection": null,
							"direction": null,
							"speed": 0,
							"wait": 0,
							"gimbal": "none",
							"angle": 0,
							"climb_rate": -1,
							"wp_event_channel_value": 0,
							"auto_trigger_time": -1,
							"prefix": "P",
							"direction_temp": null,
							"altitudeMode": "default",
							"altitudeAboveGroundLevel": null,
							"poiIndex": 0,
							"float": 11.253828048706055,
							"turnMode": 0,
							"maxReachTime": 0,
							"repeatActions": 0,
							"actions": [],
							"autoHeight": false,
							"gimbalMode": "none",
							"recordingIntervalUnit": "m",
							"recordingInterval": -1,
							"curveSize": 0,
							"aboveSea": {
								"value": 460,
								"lat": 47.226113,
								"lng": 8.454678
							}
						}
				*/
				/* New DJI2 structure for M3T
						"properties": {
							"prefix": "P",
							"cameraAngle": 0,
							"gimbalAngle": -90,
							"gimbalYaw": 0,
							"gimbalNod": 0,
							"cameraModeTime": 1,
							"directionType": "followWayline",
							"wpEvent": 0,
							"autoTrigger": 0,
							"climbRate": 0.1,
							"tolerance": 10,
							"rotation": "clockwise",
							"useMissionDirectionMode": true,
							"direction": 0,
							"curveSize": 0,
							"useMissionPathMode": true,
							"useMissionYawDirection": false,
							"useMissionWaypointType": false,
							"speed": 5,
							"useMissionSpeed": false,
							"height": 60,
							"useMissionHeight": false,
							"type": 1,
							"pathMode": "straight",
							"aboveSea": {
								"value": 449,
								"lat": 47.22616,
								"lng": 8.452107
							}
						}
				*/
				//echo "curve:" . (float)$UpdateFields["Dji2Curve"]["Value"] ."<br>";
				if ($RKRKDefaultDJI2PointCurve==0) {
					$NewPointProperties = $RKRKJsonM3TPointProperties;
				} else {
					$NewPointProperties = $RKRKJsonM3TPointCurveProperties;
				}
				//take over relevant old data
				//$NewPointProperties["properties"]["prefix"] =  "P";
				//$NewPointProperties["properties"]["cameraAngle"] =  0;
				//$NewPointProperties["properties"]["gimbalAngle"] =  -90;
				//$NewPointProperties["properties"]["gimbalYaw"] =  0;
				//$NewPointProperties["properties"]["gimbalNod"] =  0;
				//$NewPointProperties["properties"]["cameraModeTime"] =  1;
				//$NewPointProperties["properties"]["directionType"] =  "followWayline";
				//$NewPointProperties["properties"]["wpEvent"] =  0;
				//$NewPointProperties["properties"]["autoTrigger"] =  0;
				//$NewPointProperties["properties"]["climbRate"] =  0.1;
				//$NewPointProperties["properties"]["tolerance"] =  10;
				//$NewPointProperties["properties"]["rotation"] =  "clockwise";
				//$NewPointProperties["properties"]["useMissionDirectionMode"] =  true;
				//$NewPointProperties["properties"]["direction"] =  0;
				//$NewPointProperties["properties"]["curveSize"] =  0;
				//$NewPointProperties["properties"]["useMissionPathMode"] =  true;
				//$NewPointProperties["properties"]["useMissionYawDirection"] =  false;
				//$NewPointProperties["properties"]["useMissionWaypointType"] =  false;
				//$NewPointProperties["properties"]["speed"] =  5;
				//$NewPointProperties["properties"]["useMissionSpeed"] =  false;
				$NewPointProperties["properties"]["height"] =  (float)$JsonData["geojson"]["features"][$key]["properties"]["height"];
				//$NewPointProperties["properties"]["useMissionHeight"] =  false;
				//$NewPointProperties["properties"]["type"] =  1;
				//$NewPointProperties["properties"]["pathMode"] =  "straight";
				$NewPointProperties["properties"]["turningDistance"] = (float)$UpdateFields["Dji2Curve"]["Value"];
				$NewPointProperties["properties"]["aboveSea"]["value"] =  $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["value"];
				$NewPointProperties["properties"]["aboveSea"]["lat"] =  $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lat"];
				$NewPointProperties["properties"]["aboveSea"]["lng"] =  $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lng"];
				$NewPointProperties["properties"]["autoHeight"] =  (float)$JsonData["geojson"]["features"][$key]["properties"]["autoHeight"];
				$NewPointProperties["properties"]["_height"] =  $NewPointProperties["properties"]["height"];
				unset($JsonData["geojson"]["features"][$key]["properties"]);
				$JsonData["geojson"]["features"][$key]["properties"]= $NewPointProperties["properties"];
				//pre_r($JsonData["geojson"]["features"][$key]["properties"]);
				//echo $key . " " . count($JsonData["geojson"]["features"]) . "<br>";
			}
		}
		// --------------------
		// Kurven Einstellungen
		// --------------------
		if (in_array("Dji2Curve",$DataArr)) {
			//09.08.2023 Change Dji2Curve in Point in DJI Pilot 2 "/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//Calculate New TrunDistance in dependency of the distances between the points
				if (($key > 0) and ($key< count($JsonData["geojson"]["features"])-1)) {
					$Dist1 = round(1000*twopoints_on_earth(Array("Lon" => $JsonData["geojson"]["features"][$key-1]["properties"]["aboveSea"]["lng"], "Lat" => $JsonData["geojson"]["features"][$key-1]["properties"]["aboveSea"]["lat"] ),Array("Lon" => $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lng"], "Lat" => $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lat"])),2); 
					$Dist2 = round(1000*twopoints_on_earth(Array("Lon" => $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lng"], "Lat" => $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lat"] ),Array("Lon" => $JsonData["geojson"]["features"][$key+1]["properties"]["aboveSea"]["lng"], "Lat" => $JsonData["geojson"]["features"][$key+1]["properties"]["aboveSea"]["lat"])),2); 
					$NewTurnDistance = round(min($Dist1*$RKRKDefaultDJI2CurveTreshold/100,$Dist2*$RKRKDefaultDJI2CurveTreshold/100, (float)$UpdateFields["Dji2Curve"]["Value"]),1);
					
					/*
					echo $key . ": " ;
					echo "[-1] : " . $JsonData["geojson"]["features"][$key-1]["properties"]["aboveSea"]["lat"] . " / " . $JsonData["geojson"]["features"][$key-1]["properties"]["aboveSea"]["lng"] . "  ";
					echo " , dist: " . $Dist1 . " [m], " ;
					echo "[0] : " . $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lat"] . " / " . $JsonData["geojson"]["features"][$key]["properties"]["aboveSea"]["lng"] . "  ";
					echo " , dist: " . $Dist2 . " [m], " ;
					echo "[+1] : " . $JsonData["geojson"]["features"][$key+1]["properties"]["aboveSea"]["lat"] . " / " . $JsonData["geojson"]["features"][$key+1]["properties"]["aboveSea"]["lng"] . "  ";echo "  -> TurnDistance = " . $NewTurnDistance;
					echo  "<br>";
					*/
				}
				$JsonData["geojson"]["features"][$key]["properties"]["curveSize"] = $NewTurnDistance;
				$JsonData["geojson"]["features"][$key]["properties"]["turningDistance"] = $NewTurnDistance;
			}
		}

		
		// -----------------------
		// Missionsgeschwindigkeit aktiv
		// -----------------------
		if (in_array("MissionSpeed",$DataArr)) {
			//04.07.2024 Setze Missionsgeschindigkeit aktiv"/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//echo "Ändere MissionSpeed  in P" . $key . "  ";
				if (($key > 0) and ($key< count($JsonData["geojson"]["features"]))) {
					//$JsonData["geojson"]["features"][$key]["properties"]["useMissionSpeed"] = $RKRKDefaultDJI2PointuseMissionSpeed;
					if ($UpdateFields["MissionSpeed"]["Value"]=="Ja") {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionSpeed"] = true;
					} else {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionSpeed"] = false;
					}
				}
			}
		}

		// -----------------------
		// Missionshöhe aktiv
		// -----------------------
		if (in_array("MissionHeight",$DataArr)) {
			//04.07.2024 Setze Missionshöhe aktiv"/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//echo "Ändere MissionHeight  in P" . $key . "  ";
				if (($key > 0) and ($key< count($JsonData["geojson"]["features"]))) {
					//$JsonData["geojson"]["features"][$key]["properties"]["useMissionHeight"] = $RKRKDefaultDJI2PointuseMissionHeight;
					if ($UpdateFields["MissionHeight"]["Value"]=="Ja") {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionHeight"] = true;
					} else {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionHeight"] = false;
					}
				}
			}
		}
		
		// -----------------------
		// Missionflugpfadtyp aktiv
		// -----------------------
		if (in_array("MissionWaypointType",$DataArr)) {
			//04.07.2024 Setze Missionflugpfadtyp aktiv"/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//echo "Ändere MissionWaypointType  in P" . $key . "  ";
				if (($key > 0) and ($key< count($JsonData["geojson"]["features"]))) {
					//$JsonData["geojson"]["features"][$key]["properties"]["useMissionWaypointType"] = $RKRKDefaultDJI2PointuseMissionPathMode;
					if ($UpdateFields["MissionWaypointType"]["Value"]=="Ja") {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionWaypointType"] = true;
					} else {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionWaypointType"] = false;
					}
				}
			}
		}

		// -----------------------
		// Missionflugrichtung aktiv
		// -----------------------
		if (in_array("MissionDirection",$DataArr)) {
			//04.07.2024 Setze Missionflugrichtung aktiv"/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//echo "Ändere MissionYawDirection  in P" . $key . "  ";
				if (($key > 0) and ($key< count($JsonData["geojson"]["features"]))) {
					//$JsonData["geojson"]["features"][$key]["properties"]["useMissionYawDirection"] = $RKRKDefaultDJI2PointuseMissionYawDirection;
					if ($UpdateFields["MissionDirection"]["Value"]=="Ja") {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionYawDirection"] = true;
					} else {
						$JsonData["geojson"]["features"][$key]["properties"]["useMissionYawDirection"] = false;
					}
				}
			}
		}
	
		// -----------------------
		// Automatische Flughöhenadaption aktiv
		// -----------------------
		if (in_array("AutoHeight",$DataArr)) {
			//04.07.2024 Setze Automatische Flughöhenadaption aktiv"/"
			if ($JsonData["geojson"]["features"][$key]["properties"]["type"]==1) {
				//echo "Ändere AutoHeight  in P" . $key . "  ";
				if (($key >= 0) and ($key<= count($JsonData["geojson"]["features"]))) {
					//$JsonData["geojson"]["features"][$key]["properties"]["autoHeight"] = $RKRKDefaultDJI2PointuseMissionYawDirection; 
					//echo "Ändere AutoHeight  in P zu " . $UpdateFields["AutoHeight"]["Value"] .  "<br>";
					if ($UpdateFields["AutoHeight"]["Value"]=="Ja") { 
						$JsonData["geojson"]["features"][$key]["properties"]["autoHeight"] = true;
					} else {
						$JsonData["geojson"]["features"][$key]["properties"]["autoHeight"] = false;
					}

				}
			}
		}

	}
	//pre_R($Point);
	//pre_r($JsonData["geojson"]["features"]);


	// ----------------------
	//Update Mission Settings
	//-----------------------
	//pre_r($UpdateFields);
	if (in_array("FlugStatistik",$DataArr)) {
		$JsonData["controlFlights"] = Array();
		$SystemInfo .= "<li>löschne Flug Statisisik";
	}

	if (in_array("KatalogNummer",$DataArr)) {
		//echo $JsonData["name"];
		$KatalogNummer = explode("_", $JsonData["name"])[0];
		//pre_r(explode("_", $JsonData["name"]));
		//echo "KatalogNummer: " . $JsonData["sharedFieldCatalog"]["catalogIdentifier"] . " - " . $KatalogNummer . "<br>";
		$JsonData["sharedFieldCatalog"]["catalogIdentifier"] = $KatalogNummer;
	}

	if (in_array("Feldname",$DataArr)) {

		//$FeldNamenArray = GetMeaningFilename($JsonData["name"]);
		$FeldNamenArray = GetMeaningFilename($JsonData["fileNameComplete"]);
		if (([1]<>"")  &&
			($FeldNamenArray[3]<>"")  &&
			($FeldNamenArray[4]<>"")  &&
			($FeldNamenArray[5]<>"")  &&
			($FeldNamenArray[6]<>"")
			) {
			//Proper filename
			//pre_R($FeldNamenArray);
			
			if ($FeldNamenArray[2]=="") { 
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
	}

	if (in_array("GruppenGebiet",$DataArr)) {
		//echo "Gebiet: " . $JsonData["pilotGroupAreaName"] . " - " . $RegionSettings[$JG]["GroupAreaName"] . "<br>";
		//    "data": {
		//	"zoneId": "6CNisoLvS",
		//	"zoneName": null,

		//$JsonData["orderNumber"] = null;
		$JsonData["data"]["zoneId"] = $RegionSettings[$JG]["GroupAreaID"];
		$JsonData["data"]["zoneName"] = null;
		$JsonData["pilotGroupAreaName"] = $RegionSettings[$JG]["GroupAreaName"];
	}
	
	if (in_array("Telefon",$DataArr)) {
		//26.06.2023 UAV Edtior does not support Phone with spaces or "/"

		//26.06.2023 UAV Edtior does not support Phone with spaces or "/"
		$JsonData["data"]["owner"]["phone"] = CleanFormatPhoneNumber($JsonData["data"]["owner"]["phone"]);
		//$SystemInfo .= "owner:" . $JsonData["data"]["owner"]["phone"] ."<br>";
		$JsonData["connectedPlanningData"]["pilotObject"]["profile"]["phone"] = CleanFormatPhoneNumber($JsonData["connectedPlanningData"]["pilotObject"]["profile"]["phone"]);
		//$SystemInfo .= "pilot:" .$JsonData["connectedPlanningData"]["pilotObject"]["profile"]["phone"] ."<br>";
		$JsonData["contributor"]["phone"] = CleanFormatPhoneNumber($JsonData["contributor"]["phone"]);
		//$SystemInfo .= "contributor:" .$JsonData["contributor"]["phone"] ."<br>";
		$SystemInfo .= "<li>Telefonnumernformat für UAV 2023 angepasst und Leerzeichen entfernt";
		//$SystemInfo .= "<li>"  . $JsonData["data"]["owner"]["phone"]  . " / " . $JsonData["connectedPlanningData"]["pilotObject"]["profile"]["phone"]  . " / " . $JsonData["contributor"]["phone"]  . " / ";
	}
	if (in_array("3T",$DataArr)) {
		//09.08.2023 Readyness for MAVIC M3T, add Standard Mission Settings for M3T "/"
		//echo "add M3T structure";
		$JsonData["copterProfile"] = $RKRKDefaultDJI2CopterProfile;
		$JsonData["missionSettings"]["djiPilot2"] = $RKRKJsonM3TStandardSettings["djiPilot2"];
	}
	if (in_array("Dji2WayPoint",$DataArr)) {
		//09.08.2023 Change waypointType in DJI Pilot 2 "/"
		$JsonData["missionSettings"]["djiPilot2"]["waypointType"] = $UpdateFields["Dji2WayPoint"]["Value"];
	}
	if (in_array("Dji2Speed",$DataArr)) {
		//09.08.2023 Change Speed in DJI Pilot 2 "/"
		$JsonData["missionSettings"]["djiPilot2"]["speed"] = (float)$UpdateFields["Dji2Speed"]["Value"];
	}
	if (in_array("Dji2Heigth",$DataArr)) {
		//09.08.2023 Change Height in DJI Pilot 2 "/"
		$JsonData["missionSettings"]["djiPilot2"]["height"] = (float)$UpdateFields["Dji2Heigth"]["Value"];
	}
	if (in_array("Dji2HeightMode",$DataArr)) {
		//09.08.2023 Change HeightMode in DJI Pilot 2 "/"
		$JsonData["missionSettings"]["djiPilot2"]["flightHeightMode"] = $UpdateFields["Dji2HeightMode"]["Value"];
	}
	//
	//if (in_array("Dji2Curve",$DataArr)) {
	//	//09.08.2023 Change HeightMode in DJI Pilot 2 "/"
	//	$JsonData["missionSettings"]["djiPilot2"]["curveSize"] = (float)$UpdateFields["Dji2Curve"]["Value"];
	//	$JsonData["missionSettings"]["djiPilot2"]["waypointType"] = "toPointAndPassWithContinuityCurvature";
	//}
	if (in_array("Dji2Drone",$DataArr)) {
		//09.08.2023 Change droneType in DJI Pilot 2 "/"
		$JsonData["missionSettings"]["djiPilot2"]["droneType"] = $UpdateFields["Dji2Drone"]["Value"];
		$JsonData["copterProfile"] = $RKRKDefaultDJI2CopterProfile;
	}
	if (in_array("Richtung",$DataArr)) {
		$JsonData["missionSettings"]["dji"]["directionMode"] = $UpdateFields["Richtung"]["Value"];
		$SystemInfo .= "<li>update Richtung: ". $UpdateFields["Richtung"]["Value"];
	}
	if (in_array("Pfad",$DataArr)) {
		$JsonData["missionSettings"]["dji"]["pathModus"] = $UpdateFields["Pfad"]["Value"];
		$SystemInfo .= "<li>update Pfad: ". $UpdateFields["Pfad"]["Value"];
	}
	if (in_array("Curve",$DataArr)) {
		if ($JsonData["missionSettings"]["dji"]["pathModus"]=="curve") { 
			$JsonData["missionSettings"]["dji"]["curveSize"] = intval($UpdateFields["Curve"]["Value"]);
			$SystemInfo .= "<li>update Curve: ". $UpdateFields["Curve"]["Value"];
		} else {
			$JsonData["missionSettings"]["dji"]["curveSize"] = 0;
			$SystemInfo .= "<li>update Curve: 0";
		}
	}
	if (in_array("TravelSpeed",$DataArr)) {
		$JsonData["missionSettings"]["dji"]["travelSpeed"] = intval($UpdateFields["TravelSpeed"]["Value"]);
		$SystemInfo .= "<li>update TravelSpeed: ". $UpdateFields["TravelSpeed"]["Value"];

	}

	if (in_array("EasyMode",$DataArr)) {
		//Set EasyMode
		echo $JsonData["name"] . "set to easymode" . "<br>";
		$JsonData["missionSettings"]["djiPilot2"]["easyModeForm"] = ($UpdateFields["EasyMode"]["Value"]=="Ja");
		$JsonData["missionSettings"]["djiPilot2"]["easyModeSpeed"] = $RKRKJsonM3TStandardSettings["djiPilot2"]["easyModeSpeed"];
	}


	
	// --------------------
	// Standardkurven Einstellungen M3T
	// --------------------
	if (in_array("Dji2Curve",$DataArr)) {
		$JsonData["missionSettings"]["djiPilot2"]["defaultCurveSize"] = (float)$UpdateFields["Dji2Curve"]["Value"];
		$SystemInfo .= "<li>update TravelSpeed: ". (float)$UpdateFields["Dji2Curve"]["Value"];
	}
	
/*
	if (in_array("AutoHeight",$DataArr)) {
		if ($UpdateFields["autoHeight"]["Value"]=="true") {
			$JsonData["missionSettings"]["dji"]["autoHeight"] = true;
		else {
			$JsonData["missionSettings"]["dji"]["autoHeight"] = false;
		}
		$SystemInfo .= "<li>update autoHeight: ". $UpdateFields["autoHeight"]["Value"];
	}
*/
	/*
	if (in_array("h_1-h_n",$DataArr)) {
		$JsonData["missionSettings"]["dji"]["travelSpeed"] = $UpdateFields["h_1-h_n"]["Value"];
	}
	if (in_array("crv_1-crv_n",$DataArr)) {
		$JsonData["missionSettings"]["dji"]["travelSpeed"] = $UpdateFields["crv_1-crv_n"]["Value"];
	}
	*/

	
}


function CheckJsonSettings($JsonFile, &$HealthData, $JG) {
global 	$colorOKtext, 
		$colorNOK ,$colorNOKtext, 
		$RKRKJsonStandardSettings,
		$KatalogBaseline,
		$RKRKDefaultDJI2TravelSpeed ,
		$RKRKDefaultDJI2TravelHeight, 
		$RKRKDefaultDJI2DroneType, 
		$RKRKDefaultDJI2CopterProfile,
		$UpdateFields,
		$RKRKDefaultDJI2WayPointType,
		$RKRKDefaultDJI2HeightMode, 
		$RKRKLowerHeight,
		$RKRKUpperHeight,
		$RegionSettings,
		$searchSDZ, 
		$replaceSDZ;
		
	$JsonFileData="";
	$JsonFileData = ReadJsonFile($JsonFile);
	//pre_R($RKRKJsonStandardPointSettings);

	
	//$HealthData["Struktur"]["Ok"] = array_key_exists("djiPilot", $JsonFileData["missionSettings"]);
	$JsonFileName = str_replace($searchSDZ,	$replaceSDZ, pathinfo($JsonFile)["basename"]);
	$HealthData["Filename"]["Value"] = substr(pathinfo($JsonFile)["basename"], 0 ,strlen($JsonFileName));
	$HealthData["Filename"]["Ok"]= (substr(pathinfo($JsonFile)["basename"], 0 ,strlen($JsonFileName)) == $JsonFileName);

	foreach ($RKRKJsonStandardSettings as $key1 => $data1) {
		foreach ($data1 as $key2 => $data2) {
			$HealthData["Richtung"]["Value"] = $JsonFileData[$key1][$key2]["directionMode"];
			$HealthData["Richtung"]["Ok"]= ($RKRKJsonStandardSettings[$key1][$key2]["directionMode"] == $JsonFileData[$key1][$key2]["directionMode"]);
			$HealthData["Pfad"]["Value"] = $JsonFileData[$key1][$key2]["pathModus"];
			$HealthData["Pfad"]["Ok"]= ($RKRKJsonStandardSettings[$key1][$key2]["pathModus"] == $JsonFileData[$key1][$key2]["pathModus"]);
			$HealthData["TravelSpeed"]["Value"] = round($JsonFileData[$key1][$key2]["travelSpeed"],1);
			$HealthData["TravelSpeed"]["Ok"] = ($RKRKJsonStandardSettings[$key1][$key2]["travelSpeed"] == $JsonFileData[$key1][$key2]["travelSpeed"]);
			//$HealthData["Curve"]["Value"]= round($JsonFileData[$key1][$key2]["curveSize"],1);
			//$HealthData["Curve"]["Ok"] = ($RKRKJsonStandardSettings[$key1][$key2]["curveSize"] == $JsonFileData[$key1][$key2]["curveSize"]);
			$HealthData["Curve"]["Value"]= round($JsonFileData[$key1][$key2]["turningDistance"],1);
			$HealthData["Curve"]["Ok"] = ($RKRKJsonStandardSettings[$key1][$key2]["turningDistance"] == $JsonFileData[$key1][$key2]["turningDistance"]);
			$HealthData["FlightTime"]["Ok"]= !(is_string($JsonFileData["flightTime"]));
			$HealthData["FlightTime"]["Value"]= round($JsonFileData["flightTime"],1);
			// flight distance/ travel speed + 3s per point
			$HealthData["FlightTime"]["CalcValue"]= round($JsonFileData["flightDistance"]/$JsonFileData[$key1][$key2]["travelSpeed"]+$JsonFileData["featureCount"]["waypoints"]*3,1);
			$HealthData["FlightTime"]["Ok"] = ((abs(($HealthData["FlightTime"]["Value"]-$HealthData["FlightTime"]["CalcValue"])/$HealthData["FlightTime"]["CalcValue"])*100)<20);
/*
			$HealthData["Richtung"]["Value"]t = round($JsonFileData[$key1][$key2]["autoHeight"],1);
			$HealthData["Richtung"]["Ok"] = ($RKRKJsonStandardSettings[$key1][$key2]["autoHeight"] == $JsonFileData[$key1][$key2]["autoHeight"]);
*/
			//foreach ($data2 as $key3 => $data3) {
				//pre_R($data3);
			//}	
		}	
	}

	//M3T
	//echo "Sp: "  .$UpdateFields["Dji2Speed"]["Value"] . "<br>";
	$HealthData["M3TSpeed"]["Ok"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["speed"]==$UpdateFields["Dji2Speed"]["Value"]);
	//$HealthData["M3TSpeed"]["Ok"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["speed"]==$RKRKDefaultDJI2TravelSpeed);
	$HealthData["M3TSpeed"]["Value"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["speed"]);
	
	//echo "He: "  .$UpdateFields["Dji2Heigth"]["Value"] . "<br>";
	$HealthData["M3THeight"]["Ok"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["height"]>=$RKRKLowerHeight) && 
									  ($JsonFileData["missionSettings"]["djiPilot2"]["height"]<=$RKRKUpperHeight);
	//$HealthData["M3THeight"]["Ok"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["height"]==$RKRKDefaultDJI2TravelHeight);
	$HealthData["M3THeight"]["Value"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["height"]);
	
	//echo "Ge: "  .$UpdateFields["Dji2Drone"]["Value"] . "<br>";
	$HealthData["M3TGen"]["Ok"] =  (($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]==$UpdateFields["Dji2Drone"]["Value"]) and ($JsonFileData["copterProfile"] == $RKRKDefaultDJI2CopterProfile));
	//echo "droneType " . $JsonFileData["missionSettings"]["djiPilot2"]["droneType"] . " copterProfile " . $JsonFileData["copterProfile"] . "<br>";
	//$HealthData["M3TGen"]["Ok"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]==$RKRKDefaultDJI2DroneType);
	$HealthData["M3TGen"]["Value"] =  ($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]);
	
	//echo "Dr: "  .$UpdateFields["Dji2Drone"]["Value"] . "<br>";
	$HealthData["M3TDrone"]["Ok"] = (($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]==$UpdateFields["Dji2Drone"]["Value"]) and ($JsonFileData["copterProfile"] == $RKRKDefaultDJI2CopterProfile));
	//$HealthData["M3TDrone"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]==$RKRKDefaultDJI2DroneType);
	$HealthData["M3TDrone"]["Value"] = ($JsonFileData["missionSettings"]["djiPilot2"]["droneType"]);
	
	//echo "WP: "  .$UpdateFields["Dji2WayPoint"]["Value"] . "<br>";
	$HealthData["M3TWayPoint"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["waypointType"]==$UpdateFields["Dji2WayPoint"]["Value"]);
	//$HealthData["M3TWayPoint"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["waypointType"]==$RKRKDefaultDJI2WayPointType);
	$HealthData["M3TWayPoint"]["Value"] = ($JsonFileData["missionSettings"]["djiPilot2"]["waypointType"]);
	
	//echo "HM: "  .$UpdateFields["Dji2HeightMode"]["Value"] . "<br>";
	$HealthData["M3THeigthMode"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["flightHeightMode"]==$RKRKDefaultDJI2HeightMode);
	$HealthData["M3THeigthMode"]["Value"] = ($JsonFileData["missionSettings"]["djiPilot2"]["flightHeightMode"]);
	
	//$KatalogNummer = explode("_",$JsonFileData["name"])[0];
	//ab Feb 2026 - Katalognummer nicht mehr im Feldnamen
	//$HealthData["KatalogNummer"]["Ok"] = ($JsonFileData["sharedFieldCatalog"]["catalogIdentifier"] == $KatalogNummer);
	//echo "Feldkatalognummer: " . $JsonFileData["sharedFieldCatalog"]["catalogIdentifier"] . "/" . explode("-",$JsonFileData["sharedFieldCatalog"]["catalogIdentifier"])[0]. " JG: " . $JG . "<br>";
	$HealthData["KatalogNummer"]["Ok"] = (explode("-",$JsonFileData["sharedFieldCatalog"]["catalogIdentifier"])[0] == $JG);
	$HealthData["KatalogNummer"]["Value"] = ($JsonFileData["sharedFieldCatalog"]["catalogIdentifier"]);
	// behinhaltet kein "WP" und kein "ha"

	$Feldname = $JsonFileData["name"];
	//echo $Feldname . " WP: " . strpos($Feldname, "WP") . " ha: " . strpos($Feldname, "ha_") . "<br>";
	//echo "Feldname: " . $Feldname . " Feldkatalog: " . $JsonFileData["sharedFieldCatalog"]["catalogIdentifier"] . "<br>";
	$HealthData["Feldname"]["Ok"] = ((strpos($Feldname, $JsonFileData["sharedFieldCatalog"]["catalogIdentifier"]) === false) and (strpos($Feldname, "WP") === false) and (strpos($Feldname, "ha_") === false));
	$HealthData["Feldname"]["Value"] = ($JsonFileData["name"]);
	
	//EasyMode
	//since Feb 2026
	$HealthData["EasyMode"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["easyModeForm"] === true);
	$HealthData["EasyMode"]["Value"] = ($JsonFileData["name"]);

	//echo "KatID " .  $JsonFileData["sharedFieldCatalog"]["catalogIdentifier"] . " JG " . $JG ." | " . $JsonFileData["pilotGroupAreaName"] . " / ". $RegionSettings[$JG]["GroupAreaName"] . " | " . $JsonFileData["data"]["zoneId"] . " / " .  $RegionSettings[$JG]["GroupAreaID"] . "<br>"; 
	$HealthData["GruppenGebiet"]["Ok"] =  ((str_replace($searchSDZ,$replaceSDZ,$JsonFileData["pilotGroupAreaName"]) == $RegionSettings[$JG]["GroupAreaName"]) and ($JsonFileData["data"]["zoneId"] == $RegionSettings[$JG]["GroupAreaID"]) );
	$HealthData["GruppenGebiet"]["Value"] = ($JsonFileData["pilotGroupAreaName"]);

	//No impcat to curve size, 13.08.2023
	//	$HealthData["M3TCurve"]["Ok"] = ($JsonFileData["missionSettings"]["djiPilot2"]["curveSize"]==(float)$RKRKDefaultDJI2CurveSize);
	//	$HealthData["M3TCurve"]["Value"] = ($JsonFileData["missionSettings"]["djiPilot2"]["curveSize"]);

	// check if flight statisics in JSON file stored
	$HealthData["FlugStatistik"]["Ok"] = 	!(($JsonFileData["controlFlights"][0]["date"]!="")
											or ($JsonFileData["controlFlights"][1]["date"]!="")
											or ($JsonFileData["controlFlights"][2]["date"]!="")
											or ($JsonFileData["controlFlights"][3]["date"]!=""));

	// check Phone number format (without spaces/ +41791112233)
	$HealthData["Phone"]["Ok"] = (substr_count( $JsonFileData["data"]["owner"]["phone"] . 
												$JsonFileData["connectedPlanningData"]["pilotObject"]["profile"]["phone"] . 
												$JsonFileData["contributor"]["phone"], ' ')==0);

	//$HealthData["UpdateDate"]["Value"]= date(substr($JsonFileData["lastModified"],10),"d.m.Y");
	$JsonDate=date_format( date_create(substr($JsonFileData["lastModified"],0,10)),"d.m.Y");
	$HealthData["UpdateDate"]["Value"]=$JsonDate;
	$HealthData["UpdateDate"]["Ok"] = (date("Y-m-d",strtotime($KatalogBaseline))>date("Y-m-d", strtotime($JsonDate)));


	
	//$HealthData["UpdateDate"]["Value"]=$JsonFileData["missionSettings"]["djiPilot2"]["height"];
	$HealthData["Flughoehe"]["Value"]=(GetMeaningFilename($JsonFile)[4]);
	$HealthData["Flughoehe"]["Ok"]=($JsonFileData["missionSettings"]["djiPilot2"]["height"]==GetMeaningFilename($JsonFile)[4]);

	//pre_R(GetMeaningFilename($JsonFile));
	//check if height in not a number
	//echo $JsonFileData["geojson"]["features"][2]["properties"]["height"];
	$HealthData["old"]["Ok"]=!(is_string($JsonFileData["geojson"]["features"][2]["properties"]["height"]));//pre_R($HealthData);
}


function CheckJsonPoints($JsonFile, $Point, &$okgen, &$heigth, &$okheight, &$curve, &$okcurve) {
global 	$colorOKtext, 
		$colorNOK ,
		$colorNOKtext, 
		$RKRKJsonStandardPointSettings,
		$RKRKLowerHeight,
		$RKRKUpperHeight,
		$colorfontstd;
		
	$JsonFileData="";
	$JsonFileData = ReadJsonFile($JsonFile);
	//pre_R($RKRKJsonStandardPointSettings);

	if ($Point=="n") {
		$Point= count($JsonFileData["geojson"]["features"])-1;
	} elseif ($Point=="n-1") {
		$Point= count($JsonFileData["geojson"]["features"])-2; 
	} else {
		$Point++; //Adjust Point numbering to Flight point number
	}
	//echo"Point: " . $Point . "<br>";
	$PointData = $JsonFileData["geojson"]["features"][$Point];


	//pre_R($PointData);
	foreach ($PointData as $key1 => $data1) {
		//$okheight= ($data1["_height"] == $RKRKJsonStandardPointSettings["point"]["properties"]["_height"]);
		$okheight= ($data1["_height"] <= $RKRKUpperHeight) &&
				   ($data1["_height"] >= RKRKLowerHeight);
		$heigth = round($data1["_height"],1);
		if ($height==0) {
			//$okheight= ($data1["height"] == $RKRKJsonStandardPointSettings["point"]["properties"]["height"]);
			$okheight= ($data1["height"] <= $RKRKUpperHeight) &&
				   ($data1["height"] >= RKRKLowerHeight);
			$heigth = round($data1["height"],1);
		}
		
		$okcurve= ($data1["turningDistance"] == $RKRKJsonStandardPointSettings["point"]["properties"]["turningDistance"]);
		$curve = round($data1["turningDistance"],1);
	}

}

function CheckJsonPointSettings($JsonFile, &$heigths, &$okheight, &$turndists, &$okcurve, &$HealthData) {
global 	$RKRKJsonStandardPointSettings,
		$RKRKDefaultDJI2PointuseMissionSpeed,
		$RKRKDefaultDJI2PointuseMissionHeight,
		$RKRKDefaultDJI2PointuseMissionYawDirection,
		$RKRKDefaultDJI2PointuseMissionPathMode,
		$RKRKDefaultDJI2PointuseAutoHeight,
		$UpdateFields,
		$RKRKLowerHeight,
		$RKRKUpperHeight,
		$colorfontstd;
		
	$JsonFileData="";
	unset($heigthsArr);
	unset($turndistsArr);
	$okheight=true;
	$okcurve=true;
	//echo "<br>FileName: " . ($JsonFile);
	$JsonFileData = ReadJsonFile($JsonFile);
	//pre_r($JsonFileData);
	//pre_R($RKRKJsonStandardPointSettings);
  	$heigthsArr[] =  "[" . $JsonFileData["missionSettings"]["djiPilot2"]["height"] . "]__";
	$turndistsArr[] =  "[" . $JsonFileData["missionSettings"]["djiPilot2"]["defaultCurveSize"] . "]__";
	//pre_R($JsonFileData["geojson"]["features"] );
	$StrtPtHeight=0;

	$FlightHeight = $JsonFileData["missionSettings"]["djiPilot2"]["height"] ;
	//echo "<br> F: " . $FlightHeight . "  ";
	$MissionHeightOn = ($RKRKDefaultDJI2PointuseMissionHeight);
	$MissionSpeedOn = ($RKRKDefaultDJI2PointuseMissionSpeed);
	$MissionWaypointTypeOn = ($RKRKDefaultDJI2PointuseMissionYawDirection);
	$MissionFlightDirectionOn = ($RKRKDefaultDJI2PointuseMissionPathMode);
	$AutoheightOn = ($RKRKDefaultDJI2PointuseAutoHeight);

	//echo " ms: " . $MissionHeightOn . " "; 
	//echo " mh: " . $MissionSpeedOn . " "; 
	//echo " md: " . $MissionWaypointTypeOn . " "; 
	//echo " mw: " . $MissionFlightDirectionOn  . " "; 
	//echo " ah: " . $AutoheightOn . " "; 

	foreach ($JsonFileData["geojson"]["features"] as $key1 => $data1) {
		
		if ( ($data1["type"] == "Feature")
				and ($data1["properties"]["type"] == 1)) {

			if ($StrtPtHeight==0) {
				// Startpunkthöhe
				if (!$data1["properties"]["useMissionHeight"]) {
					$StrtPtHeight = round($data1["properties"]["height"],1);
				} else {
					$StrtPtHeight = round($JsonFileData["missionSettings"]["djiPilot2"]["height"],1);
				}
				//echo $key1 . " S: " . $StrtPtHeight . "  ";
			}
			
			// EndHeight
				if (!$data1["properties"]["useMissionHeight"]) {
					$EndPtHeight = round($data1["properties"]["height"],1);
				} else {
					$EndPtHeight = round($JsonFileData["missionSettings"]["djiPilot2"]["height"],1);
				}
			//echo $key1 . " E: " . $EndPtHeight . "  ";

			if (($key1>0) and ($key1< count($JsonFileData["geojson"]["features"])-1)) {
				//pre_R($data1);
				$CurrentPtHeight =round($data1["properties"]["height"],1);
				if ($RKRKJsonStandardPointSettings["point"]["properties"]["_height"]==0) {
					if (($key1>1) and ($key1< count($JsonFileData["geojson"]["features"])-1)) 
						//echo round($data1["properties"]["height"],0). ",";
						//$okheight = $okheight && (round($data1["properties"]["height"],0) == $RKRKJsonStandardPointSettings["point"]["properties"]["height"]);
						$okheight = $okheight && 
										(round($data1["properties"]["height"],0) >= $RKRKLowerHeight) &&
										(round($data1["properties"]["height"],0) <= $RKRKUpperHeight);
					$heigthsArr[] = round($data1["properties"]["height"],1);
				} else {
				}
					if (($key1>1) and ($key1< count($JsonFileData["geojson"]["features"])-1)) 
						//echo round($data1["properties"]["_height"],0). ",";
						//$okheight= $okheight && (round($data1["properties"]["_height"],0) == $RKRKJsonStandardPointSettings["point"]["properties"]["height"]);
						$okheight= $okheight && 
										(round($data1["properties"]["_height"],0) >= $RKRKLowerHeight) &&
										(round($data1["properties"]["_height"],0) <= $RKRKUpperHeight);
					$heigthsArr[] = round($data1["properties"]["_height"],1);
				
				$okcurve= $okcurve && ($data1["properties"]["turningDistance"] >0);
				//echo round($data1["properties"]["turningDistance"],1) . ","; 
				$turndistsArr[] = round($data1["properties"]["turningDistance"],1);
				
				//Missionsgeschwindigkeit
				$CheckValueMissionSpeed = ($UpdateFields["MissionSpeed"]["Value"]=="Ja");
				//if ($data1["properties"]["useMissionSpeed"] != ($RKRKDefaultDJI2PointuseMissionSpeed)) {
				if ($data1["properties"]["useMissionSpeed"] != ($CheckValueMissionSpeed)) {
					//echo " ms: " . $data1["properties"]["useMissionSpeed"] . " "; 
					//$MissionSpeedOn = !($RKRKDefaultDJI2PointuseMissionSpeed);
					$MissionSpeedOn = false;
				}
				//Missionshöhe
				$CheckValueMissionHeight =  ($UpdateFields["MissionHeight"]["Value"]=="Ja");
				//if ($data1["properties"]["useMissionHeight"] != ($RKRKDefaultDJI2PointuseMissionHeight)) {
				if ($data1["properties"]["useMissionHeight"] != ($CheckValueMissionHeight)) {
					//echo " mh: " . $data1["properties"]["useMissionHeight"] . " "; 
					$MissionHeightOn = false;
				}
				//Mission Path direction
				$CheckValueMissionYawDirection =  ($UpdateFields["MissionWaypointType"]["Value"]=="Ja");
				//if ($data1["properties"]["useMissionYawDirection"] != ($RKRKDefaultDJI2PointuseMissionYawDirection)) {
				if ($data1["properties"]["useMissionYawDirection"] != ($CheckValueMissionYawDirection)) {
					//echo " md: " . $data1["properties"]["useMissionYawDirection"] . " "; 
					$MissionFlightDirectionOn = false;
				}
				//Mission Path mode
				$CheckValueMissionPathMode =  ($UpdateFields["MissionDirection"]["Value"]=="Ja"); 
				//if ($data1["properties"]["useMissionWaypointType"] != ($RKRKDefaultDJI2PointuseMissionPathMode)) {
				if ($data1["properties"]["useMissionWaypointType"] != ($CheckValueMissionPathMode)) {
					//echo " mw: " . $data1["properties"]["mode"] . " "; 
					$MissionWaypointTypeOn = false;
				}
			}
			if (($key1>0) and ($key1< count($JsonFileData["geojson"]["features"]))) {
				//Autoheight
				$CheckValueAutoHeight =  ($UpdateFields["AutoHeight"]["Value"]=="Ja");
				//if ($data1["properties"]["autoHeight"] != ($RKRKDefaultDJI2PointuseAutoHeight)) {
				if ($data1["properties"]["autoHeight"] != ($CheckValueAutoHeight)) {
					//echo " ah: " . $data1["properties"]["autoHeight"] . " "; 
					$AutoheightOn = false;
				}
			}
		}	
	}


	$HealthData["StartPtHeight"]["Value"] =($StrtPtHeight);
	$HealthData["StartPtHeight"]["Ok"] = ($StrtPtHeight==$FlightHeight);
	$HealthData["NoReturnPoint"]["Value"] =($EndPtHeight);
	$HealthData["NoReturnPoint"]["Ok"] = ($EndPtHeight==$FlightHeight);
	$HealthData["MissionDirection"]["Value"] = $RKRKDefaultDJI2PointuseMissionYawDirection;
	$HealthData["MissionDirection"]["Ok"] = $MissionFlightDirectionOn;
	$HealthData["MissionSpeed"]["Value"] = $RKRKDefaultDJI2PointuseMissionSpeed;
	$HealthData["MissionSpeed"]["Ok"] = $MissionSpeedOn;
	$HealthData["MissionHeight"]["Value"] = $RKRKDefaultDJI2PointuseMissionHeight;
	$HealthData["MissionHeight"]["Ok"] =$MissionHeightOn;
	$HealthData["MissionWaypointType"]["Value"] = $RKRKDefaultDJI2PointuseMissionPathMode;
	$HealthData["MissionWaypointType"]["Ok"] =$MissionWaypointTypeOn;
	$HealthData["AutoHeight"]["Value"] = $RKRKDefaultDJI2PointuseAutoHeight;
	$HealthData["AutoHeight"]["Ok"] =$AutoheightOn;
	
	$heigths= implode(",",$heigthsArr);
	$turndists= implode(",",$turndistsArr);
}


function DoJsonDataList ($FilterJG, $Stop=0) {
global 	$RKRKLocalLoactionMissionFiles, 
		$xmlMissionLocation, 
		$RKRKFelderfileXML, 
		$colorOKtext, 
		$colorNOKtext, 
		$colorNOK, 
		$colorTableEven, 
		$IconLoc,
		$ButtonHeightSmall, 
		$OptionFlughoehe, 
		$SortTable, 
		$DataSet, 
		$HealthData, 
		$colorTransparent,
		$FileDirectory,
		$MissionSelectionParameter,
		$RKRKMissionSelected,
		$Testoutput,
		$ExIntputDirectory,
		$TestOutputDirectory,
		$FeldIDListe,
		$RegionsSelected,
		$UpdateFields,
		$CSVJsonHealthFile,
		$EditAccess;	
		
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	
	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=0;
		if (in_array($JKey,$FilterJG)) {
			//echo "Region: " . $JKey . "<br>";
			if ($FeldIDListe<>"") {
				$FeldIDArray = explode(" " ,$FeldIDListe);
			}
			foreach ($JRegion as $Field) {
				if ((in_array($Field["FeldID"], $FeldIDArray)) or ($FeldIDListe=="")) {
					$JsonData[$Field["FeldID"]]["FeldID"] = $Field["FeldID"]."-F";
					$JsonData[$Field["FeldID"]]["JG"] = $Field["JG"];
					$JsonData[$Field["FeldID"]]["JasonFile"] = $Field["JasonFile"];
					$JsonData[$Field["FeldID"]]["Location"] = "Feldkatalog";
					$i++;
					if (($i >= $Stop) && ($Stop>0)) break;	
				}	
			}
		}
	}
	if (in_array("EX",$FilterJG)) {
		//behandlung externe files
		//echo "ExFileDir: " . $ExIntputDirectory . "<br>";
		$ExFiles = array_diff(scandir($ExIntputDirectory, SCANDIR_SORT_DESCENDING), array('..', '.'));;
		//pre_R($ExFiles);
		foreach ($ExFiles as $key => $ExFile) {
			//echo "ExFile: " . $key . " - " . $ExFile . "<br>";
			$MeaningFilename = GetMeaningFilename($ExFile);
			$file_parts = pathinfo($ExIntputDirectory.$ExFile);
			if ($file_parts['extension']=="json") {
				$JsonData[$MeaningFilename[0]."E"]["FeldID"] = $MeaningFilename[0]."-E";
				$JsonData[$MeaningFilename[0]."E"]["JG"] = explode("-",$MeaningFilename[0])[0];
				$JsonData[$MeaningFilename[0]."E"]["JasonFile"] = $Field["JasonFile"];
				//$JsonData["EX-".sprintf("%03d", $key)]["FeldID"] = "EX-" . sprintf("%03d", $key);
				//$JsonData["EX-".sprintf("%03d", $key)]["JG"] = "EX";
				$JsonData[$MeaningFilename[0]."E"]["JasonFile"] = $ExIntputDirectory.$ExFile;
				$JsonData[$MeaningFilename[0]."E"]["Location"] = "EXTERN";
			}
		}
	}
	if (in_array("DWNLD",$FilterJG)) {
		//behandlung externe files
		//echo "OutputFileDir: " . $TestOutputDirectory . "<br>";
		$DwnldFiles = array_diff(scandir($TestOutputDirectory, SCANDIR_SORT_DESCENDING), array('..', '.'));;
		//pre_R($DwnldFiles);
		foreach ($DwnldFiles as $key => $DwnldFile) {
			//echo "OutputFile: " . $key . " - " . $DwnldFile . "<br>";
			$MeaningFilename = GetMeaningFilename($DwnldFile);
			$file_parts = pathinfo($TestOutputDirectory.$DwnldFile);
			if ($file_parts['extension']=="json") {
				$JsonData[$MeaningFilename[0]."D"]["FeldID"] = $MeaningFilename[0]."-D";
				$JsonData[$MeaningFilename[0]."D"]["JG"] = explode("-",$MeaningFilename[0])[0];
				//$JsonData["DWNLD-".sprintf("%03d", $key)]["FeldID"] = "DW-" . sprintf("%03d", $key);
				//$JsonData["DWNLD-".sprintf("%03d", $key)]["JG"] = "DWNLD";
				$JsonData[$MeaningFilename[0]."D"]["JasonFile"] = $TestOutputDirectory.$DwnldFile;
				$JsonData[$MeaningFilename[0]."D"]["Location"] = "DOWNLAOD";
			}
		}
	}

	//pre_R($JsonData);
	$PointList = Array(1,2,"n-1","n");

	//pre_R($JsonData);
	echo "<table>";
	echo "<tr>";
	echo "<th>";
	echo "<input type='image' value='Sortieren' src='" . $IconLoc . "/sortieren.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "Sortieren=FeldID&" . $MissionSelectionParameter . "' height='" . $ButtonHeightSmall. "'  title='Spalte sortieren'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "FeldID";
	echo "</th>";
	echo "<th>";
	echo "JG";
	echo "</th>";
	echo "</th>";
	echo "<th>";
	echo "<th>";
	echo "<input type='image' value='Sortieren' src='" . $IconLoc . "/sortieren.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "Sortieren=JasonFile&" . $MissionSelectionParameter . "' height='" . $ButtonHeightSmall. "'  title='Spalte sortieren'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
	echo "Json";
	echo "</th>";
	echo "<th>";
	echo "ok";
	echo "</th>";
	echo "<th>";
	echo "Flug-Stat";
	echo "</th>";
	echo "<th>";
	echo "KatNr";
	echo "</th>";
	echo "<th>";
	echo "Feld-Name";
	echo "</th>";
	echo "<th>";
	echo "Easy-Mode";
	echo "</th>";
	echo "<th>";
	echo "Gebiet";
	echo "</th>";
	if ($UpdateFields["Phone"]["activ"] == "Ja") {
		echo "<th>";
		echo "Tel";
		echo "</th>";
	}
	if ($RKRKMissionSelected=="M2") {
		//echo "<th>";
		//echo "Struktur";
		//echo "</th>";
		echo "<th>";
		echo "Richtung";
		echo "</th>";
		echo "<th>";
		echo "Pfad";
		echo "</th>";
		echo "<th>";
		echo "Curve";
		echo "</th>";
		echo "<th>";
		echo "Travel-Speed";
		echo "</th>";
	}
	if ($RKRKMissionSelected=="3T") {
		echo "<th>";
		echo "Gen";
		echo "</th>";
		echo "<th>";
		echo "Way-Point";
		echo "</th>";
		echo "<th>";
		echo "Speed";
		echo "</th>";
		echo "<th>";
		echo "Height";
		echo "</th>";
		echo "<th>";
		echo "Height-Mode";
		echo "</th>";
		//echo "<th>";
		//echo "Curve";
		//echo "</th>";
		echo "<th>";
		echo "Drone";
		echo "</th>";
		echo "<th>";
		echo "Mis-Speed";
		echo "</th>";
		echo "<th>";
		echo "Mis-Height";
		echo "</th>";
		echo "<th>";
		echo "Mis-FlightMode";
		echo "</th>";
		echo "<th>";
		echo "Mis-Direction";
		echo "</th>";
		echo "<th>";
		echo "Auto-Height";
		echo "</th>";
		echo "<th>";
		echo "StrtPt";
		echo "</th>";
		echo "<th>";
		echo "Flug-höhe";
		echo "</th>";
		echo "<th>";
		echo "EndPt";
		echo "</th>";
	}
	echo "<th>";
	echo "Flight-time";
	echo "</th>";
	//echo "<th>";
	//echo "AutoHeight";
	//echo "</th>";
	echo "<th>";
	echo "height<br>turndist" . $Point;
	echo "</th>";
	//foreach ($PointList as $Point) {
	//	echo "<th>";
	//	echo "h_" . $Point;
	//	echo "</th>";
	//	echo "<th>";
	//	echo "crv_" . $Point;
	//	echo "</th>";
	//}
	echo "<th>";
	echo "letztes update";
	echo "</th>";
	echo "</tr>";

	if ($SortTable=="FeldID") {
		aasort($JsonData, $SortTable);
	}
	foreach ($JsonData as $Data) {
		foreach (explode(",",$Data["JasonFile"]) as $JsonFile) {
			CheckJsonSettings($RKRKLocalLoactionMissionFiles.trim($JsonFile), $HealthData, $Data["JG"]);
			//CheckJsonPoints($RKRKLocalLoactionMissionFiles.trim($JsonFile), 2);
			//CheckJsonPoints($RKRKLocalLoactionMissionFiles.trim($JsonFile), 3);
			//CheckJsonPoints($RKRKLocalLoactionMissionFiles.trim($JsonFile), "last");
			//Pre_R($HealthData);
			echo "<tr>";
			echo "<td>";
			if (GetAccess("InternLinks")) {
				//$FileName=$pathinfo($Data["JasonFile"])['basename'];
				$FileName=pathinfo($JsonFile);
				if ($Data["Location"] != "DOWNLAOD") {
					if ($EditAccess) {
						//Update File;
						//if ($Testoutput) {
							echo "<input type='image' value='1st page' src='" . $IconLoc . "/update.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=" . $Data["FeldID"] . "&UpdateFile=" . $RKRKLocalLoactionMissionFiles.trim($JsonFile) . "&OutputFile=" . $TestOutputDirectory . $FileName['basename'] . "&DataSet=" . $DataSet . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Datei " . $Data["FeldID"] . " mit neuen Daten laden und auf Server speichern'>" .chr(10);
						//} else {
							echo "<input type='image' value='1st page' src='" . $IconLoc . "/update_hot.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=" . $Data["FeldID"] . "&UpdateFile=" . $RKRKLocalLoactionMissionFiles.trim($JsonFile) . "&OutputFile=" . $RKRKLocalLoactionMissionFiles.trim($JsonFile) . "&DataSet=" . $DataSet . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Datei " . $Data["FeldID"] . " mit neuen Daten laden und in Datenbank speichern'>" .chr(10);
						//}
					}	
				} else  {
					//Delete File;
					if ($Testoutput) {
						echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=" . $Data["FeldID"] . "&DeleteFile=" . trim($JsonFile) . "&DataSet=" . $DataSet . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Datei " . $Data["FeldID"] . " löschen'>" .chr(10);
					} else {
						echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=" . $Data["FeldID"] . "&DeleteFile=" . trim($JsonFile) . "&DataSet=" . $DataSet . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Datei " . $Data["FeldID"] . " löschen'>" .chr(10);
					}	
					//Download File;
					echo "<a href='" .trim($JsonFile) . "' download><img src='" . $IconLoc . "/dwnld_file.png' height='" . $ButtonHeightSmall ."' title='Datei " . $Data["FeldID"] . " herunterladen'></a> <wbr>" . chr(10);
				}
			}
			echo $Data["FeldID"];
			$OutputData[$Data["FeldID"]]["FeldID"] = $Data["FeldID"];
			echo "</td>";
			echo "<td>";
			echo $Data["JG"];
			$OutputData[$Data["FeldID"]]["JG"] = $Data["JG"];
			echo "</td>";
			echo "<td>";
			if (GetAccess("InternLinks")) {
				foreach ($OptionFlughoehe as $Flughoehe) {
					if (strpos($Data["JasonFile"],"_" . $Flughoehe["Opt"] . "_") != false) {
						$Flightheight= $Flughoehe["Opt"];
						$Picture =  $IconLoc . "\json" . $Flughoehe["Opt"] . ".png";
					}
				}
				if ($Data["JasonFile"] !="") {
					echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Data["JasonFile"] . "'  download target='_blank'><img src='" . $Picture . "' height='" . $ButtonHeightSmall * .8 ."' title='Jason File " . $Data["FeldID"] . " - " . $Data["JasonFile"] . "'></a> <wbr>" . chr(10);
				}
			}
			echo "</td>";
			if ($HealthData["Filename"]["Ok"]) {
				echo "<td>";
				$textcolor = $colorOKtext;
				echo "<span style='color:" . $textcolor . "'>";
			} else {
				echo "<td bgcolor='" . $colorNOK . "'>";
				$textcolor = $colorNOKtext;
				echo "<span style='color:" . $textcolor . "'>";
			}
			echo pathinfo($JsonFile)["basename"];
			$OutputData[$Data["FeldID"]]["File"] = pathinfo($JsonFile)["basename"];
			echo "</span>";
			//echo "<br>" . $HealthData["Filename"]["Value"];
			echo "</td>";
			echo "<td>";
			//Korruptes JSON
			if ($HealthData["old"]["Ok"]) {
				echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='JSON file ok'>";
			} else {
				echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='JSON file korrupt. Kann nicht in UAV Editor geladen werden.'>";
			}
			//echo $Data["Warnung"];
			echo "</td>";
			//Flug Statistik
			echo "<td>";
			if ($HealthData["FlugStatistik"]["Ok"]) {
				echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Flug Statisik ok'>";
			} else {
				echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Flug Statisik korrupt.'>";
			}

			//KatalogNummer
			echo "<td>";
			//echo $HealthData["KatalogNummer"]["Value"] . "<br>";
			if ($HealthData["KatalogNummer"]["Ok"] and $HealthData["KatalogNummer"]["Value"]!="") {
				echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='KatalogNummer ok'>";
				$OutputData[$Data["FeldID"]]["KatNum"] = "OK";
			} else {
				echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='KatalogNummer nicht vorhanden.'>";
				$OutputData[$Data["FeldID"]]["KatNum"] = "NOK";
			}
			echo "</td>";
			//Feldname
			echo "<td>";
			//echo $HealthData["Feldname"]["Value"] . "<br>";
			if ($HealthData["Feldname"]["Ok"]) {
				echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Feldname ok'>";
			} else {
				echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Feldname nicht vorhanden.'>";
			}
			echo "</td>";
			//EasyMode
			echo "<td>";
			//echo $HealthData["EasyMode"]["Value"] . "<br>";
			if ($HealthData["EasyMode"]["Ok"]) {
				echo "<image src='". $IconLoc . "/easy.png'' height='" . $ButtonHeightSmall . "' title='EasyMode ok'>";
			} else {
				echo "<image src='". $IconLoc . "/expert.png'' height='" . $ButtonHeightSmall . "' title='Im Expert Modus programmiert.'>";
			}
			echo "</td>";
			//GroupArea
			echo "<td>";
			//echo $HealthData["GruppenGebiet"]["Value"] . "<br>";
			if ($HealthData["GruppenGebiet"]["Ok"] and $HealthData["GruppenGebiet"]["Value"]!="") {
				echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='GroupArea ok'>";
			} else {
				echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='GroupArea nicht vorhanden.'>";
			}
			echo "</td>";
			//Phone Number
			if ($UpdateFields["Phone"]["activ"] == "Ja") {
				echo "<td>";
				if ($HealthData["Phone"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Telefonnummer Format für UAV Edtior ok'>";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Telefonnummer Format für UAV Edtior korrupt (muss: +41791112233 sein, ohne Leerzeichen)'>";
				}
			}
			if ($RKRKMissionSelected=="M2") {
				echo "</td>";
				//Struktur - Maturiät
				//if ($HealthData["Struktur"]["Ok"]) {
				//	echo "<td>";
				//	$textcolor = $colorOKtext;
				//	echo "<span style='color:" . $textcolor . "'>ok</span>";
				//} else {
				//	echo "<td bgcolor='" . $colorNOK . "'>";
				//	$textcolor = $colorNOKtext;
				//	echo "<span style='color:" . $textcolor . "'>alt</span>";
				//}
				
				//echo $Data["Warnung"];
				//echo "</td>";
				//direction
				if ($HealthData["Richtung"]["Value"] != "") {
					if ($HealthData["Richtung"]["Ok"]) {
						  echo "<td>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgcolor='" . $colorNOK . "'>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["Richtung"]["Value"]. "</span>";
				echo "</td>";
				//path
				if ($HealthData["Pfad"]["Value"] != "") {
					if ($HealthData["Pfad"]["Ok"]) {
						  echo "<td>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgcolor='" . $colorNOK . "'>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["Pfad"]["Value"]. "</span>";
				echo "</td>";
				//curve
				if ($HealthData["Curve"]["Value"] != "") {
					if ($HealthData["Curve"]["Ok"]) {
						  echo "<td>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgvcolor='" . $colorNOK . "'>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["Curve"]["Value"]. "</span>";
				echo "</td>";
				//travel speed
				if ($HealthData["TravelSpeed"]["Value"] != "") {
					if ($HealthData["TravelSpeed"]["Ok"]) {
						  echo "<td>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgvcolor='" . $colorNOK . "'>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["TravelSpeed"]["Value"] . "</span>";
				echo "</td>";
			}
			if ($RKRKMissionSelected=="3T") {
				echo "<td>";
				//echo "Gen";
				if ($HealthData["M3TGen"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='M3T Daten in JSON: ".$HealthData["M3TGen"]["Value"]."'>";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='M3T Daten NICHT vorhanden in JSON: ".$HealthData["M3TGen"]["Value"]."'>";
				}
				echo "</td>";
				echo "<td>";
				//echo "WayPoint";
				if ($HealthData["M3TWayPoint"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='M3T StandardWayPointSettingM3TWayPoint: ".$HealthData["M3TWayPoint"]["Value"]."'>";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='M3T NICHT StandardWayPointSetting: ".$HealthData["M3TWayPoint"]["Value"]."'>";
				}
				echo "</td>";
				//echo "Speed";
				if ($HealthData["M3TSpeed"]["Value"] != "") {
					if ($HealthData["M3TSpeed"]["Ok"]) {
						  echo "<td valign='left'>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgvcolor='" . $colorNOK . "' valign='left'><b>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	

				$OutputData[$Data["FeldID"]]["Speed"] =  $HealthData["M3TSpeed"]["Value"];
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["M3TSpeed"]["Value"]. "</span>";
				echo "</b></td>";
				//echo "Height";
				if ($HealthData["M3THeight"]["Value"] != "") {
					if ($HealthData["M3THeight"]["Ok"]) {
						  echo "<td valign='left'>";
						  $textcolor = $colorOKtext;
					} else {
						  echo "<td bgvcolor='" . $colorNOK . "' valign='left'><b>";
						  $textcolor = $colorNOKtext;
					}	  
				} else {
					echo "<td>";
				}	
				$OutputData[$Data["FeldID"]]["Hoehe"] =  $HealthData["M3THeight"]["Value"];
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["M3THeight"]["Value"]. "</span>";
				echo "</b></td>";
				echo "<td>";
				//echo "HeightMode";
				if ($HealthData["M3THeigthMode"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='M3T StandardHeightMode: ".$HealthData["M3THeigthMode"]["Value"]."'>";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='M3T NICHT StandardHeightMode: ".$HealthData["M3THeigthMode"]["Value"]."'>";
				}
				echo "</td>";
				//echo "Curve";
				//if ($HealthData["M3TCurve"]["Value"] != "") {
				//	if ($HealthData["M3TCurve"]["Ok"]) {
				//		  echo "<td valign='left'>";
				//		  $textcolor = $colorOKtext;
				//	} else {
				//		  echo "<td bgvcolor='" . $colorNOK . "' valign='left'>";
				//		  $textcolor = $colorNOKtext;
				//	}	  
				//} else {
				//	echo "<td>";
				//}	
				echo "<span style='color:" . $textcolor . "'>" . $HealthData["M3TCurve"]["Value"]. "</span><b>";
				echo "</td>";

				//Prüfen der Flughöhen, Kurven, Start und Entpunkt konfiguration
				//height - curve list
				CheckJsonPointSettings($RKRKLocalLoactionMissionFiles.trim($JsonFile), $heights, $okheight, $turndists, $okcurve, $HealthData);

				echo "<td>";
				//echo "Drone";
				if ($HealthData["M3TDrone"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Drohne M3T gesetzt: ".$HealthData["M3TDrone"]["Value"]."'>";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Drohne NICHT auf M3T gesetzt: ".$HealthData["M3TDrone"]["Value"]."'>";
				}
				echo "</td>";
				//MissionSpeed
				echo "<td>";
				if ($HealthData["MissionSpeed"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Missionsgeschwindigkeit aktiviert..'>";
					$OutputData[$Data["FeldID"]]["MissionSpeed"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Missionsgeschwindigkeit DINAKTIV!'>";
					$OutputData[$Data["FeldID"]]["MissionSpeed"] = "NOK";
				}
				echo "</td>";
				//MissionHeight
				echo "<td>";
				if ($HealthData["MissionHeight"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Missionshöhe aktiviert.'>";
					$OutputData[$Data["FeldID"]]["MissionHeight"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title=''Missionshöhe INAKTIV!'>";
					$OutputData[$Data["FeldID"]]["MissionHeight"] = "NOK";
				}
				echo "</td>";
				//MissionWaypointType
				echo "<td>";
				if ($HealthData["MissionWaypointType"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Missions Flugmode (Kurve) aktiviert.'>";
					$OutputData[$Data["FeldID"]]["MissionWaypointType"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Missions Flugmode (Kurve) INAKTIV!'>";
					$OutputData[$Data["FeldID"]]["MissionWaypointType"] = "NOK";
				}
				echo "</td>";
				//MissionDirection
				echo "<td>";
				if ($HealthData["MissionDirection"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Missions Flugrichtung aktiviert.'>";
					$OutputData[$Data["FeldID"]]["MissionDirection"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Missions Flugrichtung INAKTIV!'>";
					$OutputData[$Data["FeldID"]]["MissionDirection"] = "NOK";
				}
				echo "</td>";
				//AutoHeight
				echo "<td>";
				if ($HealthData["AutoHeight"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Höchenberechnung aktiviert.'>";
					$OutputData[$Data["FeldID"]]["AutoHeight"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Höchenberechnung INAKTIV!'>";
					$OutputData[$Data["FeldID"]]["AutoHeight"] = "NOK";
				}
				echo "</td>";

				echo "<td>";
				//echo "Startpunkt";
				if ($HealthData["StartPtHeight"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Starthöhe auf Standardhöhe gesetzt: ".$HealthData["M3THeight"]["Value"]."'>";
					$OutputData[$Data["FeldID"]]["Starthoehne"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Starthöhe nicht auf Standardhöhe gesetzt: ".$HealthData["M3THeight"]["Value"]."'>";
					$OutputData[$Data["FeldID"]]["Starthoehne"] = "NOK";
				}
				echo "</td>";
				//Flughöhe vs. Filename
				echo "<td>";
				if ($HealthData["Flughoehe"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Flughöhe wie in File definiert'>";
					$OutputData[$Data["FeldID"]]["Flughoehe"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Flughöhe und Filename unterschiedlich.'>";
					$OutputData[$Data["FeldID"]]["Flughoehe"] = "NOK";
				}
				echo "</td>";
				echo "<td>";
				//echo "No ReturnPoint";
				if ($HealthData["NoReturnPoint"]["Ok"]) {
					echo "<image src='". $IconLoc . "/allok.png'' height='" . $ButtonHeightSmall . "' title='Endpunkt auf Standardhöhe gesetzt: ".$HealthData["M3THeight"]["Value"]."'>";
					$OutputData[$Data["FeldID"]]["Endpunkt"] = "OK";
				} else {
					echo "<image src='". $IconLoc . "/warn1.png'' height='" . $ButtonHeightSmall . "' title='Endpunkt NICHT auf Standardhöhe gesetzt:: ".$HealthData["M3THeight"]["Value"]."'>";
					$OutputData[$Data["FeldID"]]["Endpunkt"] = "NOK";
				}
				echo "</td>";
			}
			//FlightTime
			if ($HealthData["FlightTime"]["Value"] != "") {
				if ($HealthData["FlightTime"]["Ok"]) {
					  echo "<td valign='left'>";
					  $textcolor = $colorOKtext;
				} else {
					  echo "<td bgvcolor='" . $colorNOK . "' valign='left'>";
					  $textcolor = $colorNOKtext;
				}	  
			} else {
				echo "<td>";
			}	
			echo "<span style='color:" . $textcolor . "'>" . $HealthData["FlightTime"]["Value"] . " / " . $HealthData["FlightTime"]["CalcValue"]. "</span>";
/*			echo "</td>";
			//autoHeight
			if ($$autoheight != "") {
				if ($autoheightok) {
					  echo "<td>";
					  $textcolor = $colorOKtext;
				} else {
					  echo "<td bgvcolor='" . $colorNOK . "'>";
					  $textcolor = $colorNOKtext;
				}	  
			} else {
				echo "<td>";
			}	
			if ($autoheight==true) 
				echo "<span style='color:" . $textcolor . "'>true</span>";
			else
				echo "<span style='color:" . $textcolor . "'>false</span>";
			echo "</td>";
*/
			echo "<td>";
			if ($okheight) {
				  $textcolor = $colorOKtext;
			} else {
				  $textcolor = $colorNOKtext;
			}	  
			echo "<span style='color:" . $textcolor . "'>" . $heights. "</span>";
			$OutputData[$Data["FeldID"]]["Hoehen"] = $heights;
			
			echo "<br>";
			if ($okcurve) {
				  $textcolor = $colorOKtext;
			} else {
				  $textcolor = $colorNOKtext;
			}	  
			echo "<span style='color:" . $textcolor . "'>" . $turndists. "</span>";
			$OutputData[$Data["FeldID"]]["Drehdistanzen"] = $turndists;
/*
Ersetzt durch Liste, 04.01.2023
			echo "</td>";
			foreach ($PointList as $Point) {
				//height
				CheckJsonPoints($RKRKLocalLoactionMissionFiles.trim($JsonFile), $Point, $okgen, $heigth, $okheight, $curve, $okcurve);
				if ($okheight) {
					  echo "<td>";
					  $textcolor = $colorOKtext;
				} else {
					  echo "<td bgcolor='" . $colorNOK . "'>";
					  $textcolor = $colorNOKtext;
				}	  
				echo "<span style='color:" . $textcolor . "'>" . $heigth. "</span>";
				echo "</td>";
				//curve
				if ($okcurve) {
					  echo "<td>";
					  $textcolor = $colorOKtext;
				} else {
					  echo "<td bgcolor='" . $colorNOK . "'>";
					  $textcolor = $colorNOKtext;
				}	  
				echo "<span style='color:" . $textcolor . "'>" . $curve. "</span>";
				echo "</td>";
			}
*/
			echo "<td>";
			//update datum
			$changeinfo = "<span style='fontsize=7'><i>" .chr(10);
			if ($HealthData["UpdateDate"]["Ok"]) {
				$UpdateColor = $colorTransparent;
				$textcolor = $colorfontstd;
				$tip_title = "JSON ok";
			} else {
				$UpdateColor = $colorNOK;  
				$textcolor = $colorNOKtext;
				$tip_title = "JSON nach dem Baseline-Datum geändert.";
			}
			$OutputData[$Data["FeldID"]]["Geändert"] = $HealthData["UpdateDate"]["Value"];
			echo "<span title='" . $tip_title . "' style='background-color:".$UpdateColor.";color:".$textcolor."'>" . $HealthData["UpdateDate"]["Value"] . "</span>";
			$BaseDate= date($KatalogBaseline );
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
	
	// speichern in CSV File im History Ordner
	//pre_r($OutputData);
	//SaveArrayToCSVToLog ($OutputData, $CSVJsonHealthFile);
	$CSVJsonHealthFile = date("Ymdhi")."_JSONHealth.csv";
	SaveArrayToCSVToHistory ($OutputData, $CSVJsonHealthFile);
}

//=========================================
// Main Function
//=========================================

$RegList = array_diff($RegionSettings, array('NO','ALL'));
if (GetAccess("TestOnly")) {
	$RegionSettings["EX"] = array("Reg" => "EX",
								  "JGList" => "EX",
								  "Aktiv" => true);
}
if ($EditAccess) {
	$RegionSettings["DWNLD"] = array("Reg" => "DWNLD",
									"JGList" => "DWNLD",
									"Aktiv" => true);
}

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
		echo "</td><td colspan=3>";
		$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, $RegSelected, true);
		$i++;
	echo "</td>";
	echo "<td rowspan=2>";
	//echo "<span id='exceldwnld'><a href='".$historyfilelocation."202407080951_JSONHealth.csv' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Report herunterladen'></a></span> ";
	echo "<span id='exceldwnld'>aaa</span> ";
	echo MissionLibraryButtons();
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}
	echo "</td>";
	echo "</tr>";
	echo "<tr><td>";
	echo "  Felderliste: " .chr(10);
	echo "</td><td colspan=2>";
	echo InputField("FeldIDListe", $FeldIDListe, 20);
	echo "</td><td colspan>";
	echo "<i>Felder-ID List mit <Space> Abstand</i>";
	echo "</td></tr>";

	//pre_r($RegionSettings);
	if ($EditAccess) {
		echo "<tr>";
		echo "<td rowspan=20>";
		echo "Felder überschreiben";
		echo "</td>";
		foreach($UpdateFields as $key => $SelectField) {
			if ($SelectField["activ"]) {
				//echo $SelectField["Field"]. "<br>";
				if (isset($_POST[$SelectField["Field"] . "_val"])) {
					//echo $SelectField["Field"] . ": " . $_POST[$SelectField["Field"] . "_cb"] . "<br>";
					if (($_POST[$SelectField["Field"] . "_cb"])=="on") {
						$checked = "checked";
						if (strlen($DataSet) >0) 
							$DataSet .= "," . $SelectField["Field"];
						else
							$DataSet =  $SelectField["Field"];
					} else	{	
						$checked = "";
					}
				} else {
					//echo $SelectField["Field"] . ": " . $_POST[$SelectField["Field"] . "_cb"] . "<br>";
					if (isset($_POST[$SelectField["Field"] . "_cb"])) {
						if ($_POST[$SelectField["Field"] . "_cb"]=="on") {
							$checked = "checked";
							if (strlen($DataSet) >0) 
								$DataSet .= "," . $SelectField["Field"];
							else
								$DataSet =  $SelectField["Field"];
						} else {	
							$checked = "";
						}
					} else {	
						if ($SelectField["StandardSetting"]=="Ja") {
							$checked = "checked";
						} else {	
							$checked = "";
						}
					}	

				}	
				echo "<td>";
				echo "<input type='checkbox' name='" . $SelectField["Field"] . "_cb' " . $checked . ">" . $SelectField["Text"] . chr(10);
				echo "</td>";
				echo "<td>";
				if ($SelectField["Type"] == "input") {
					//echo "Set " . $SelectField["Field"] . ": " . $_POST[$SelectField["Field"] . "_val"] .  "<br>";
					if (Isset($_POST[$SelectField["Field"] . "_val"]))
						$UpdateFields[$key]["Value"] = $_POST[$SelectField["Field"] . "_val"];
					else
						$UpdateFields[$key]["Value"] = $SelectField["Default"];
					echo InputField($SelectField["Field"] . "_val", $UpdateFields[$key]["Value"]);
				} elseif ($SelectField["Type"] == "select") {
					//echo "Set " . $SelectField["Field"] . ": " . $_POST[$SelectField["Field"] . "_val"] .  "<br>";
					if (Isset($_POST[$SelectField["Field"] . "_val"]))
						$UpdateFields[$key]["Value"] = $_POST[$SelectField["Field"] . "_val"];
					else
						$UpdateFields[$key]["Value"] = $SelectField["Default"];
					echo SelectField ($SelectField["Field"] . "_val", $UpdateFields[$key]["Value"] ,explode(",", $SelectField["Options"]));
				}
				echo " ". $SelectField["Einheit"];
				echo "</td>";
				echo "<td>";
				if ($SelectField["Default"] !="")
					echo " <i>   Standardwert: ". $SelectField["Default"] . " " . $SelectField["Einheit"] . "</i>";
				echo "</td>";
				echo "</tr>";
			}
		}
	}

	//pre_R($UpdateFields);
	//echo "Dataset to Update: " . $DataSet;
	echo "</table  width=100%>";
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $MissionSelectionParameter.'">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $MissionSelectionParameter.'"> '. chr(10);
	echo '<br>';
	echo '<br>';
	if ($EditAccess) {
		//$FileName=$pathinfo($Data["JasonFile"])['basename'];
		$FileName=pathinfo($JsonFile);
		echo "<input type='image' value='1st page' src='" . $IconLoc . "/einlesen.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=alle" . "&DataSet=" . $DataSet . "&OutputDir=" . $TestOutputDirectory . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Alle Dateien mit neuen Daten laden und auf Server (separat) speichern'>" .chr(10);
		echo "<input type='image' value='1st page' src='" . $IconLoc . "/einlesen_hot.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?FeldID=alle" . "&DataSet=" . $DataSet . "&OutputDir=" . "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Alle Dateien mit neuen Daten laden und und in Datenbank speichern'>" .chr(10);
	}
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

//===================================
// Update JSON file
// ==================================

/*
if ($FeldIDtoUpdate!="") {
	$SystemInfo .= "<b>" . $FeldIDtoUpdate . "</b><br>";
	$SystemInfo .= "<LI>Update JSON: " . $JsonFileName . "<br>";
	$JsonFileData = ReadJsonFile($JsonFileName);
	//pre_r($JsonFileData);
	UpdateJsonDataStructure($JsonFileData, $UpdateFields,$DataSet);
	$SystemInfo .=  "<LI>Save JSON File to " . $JsonOutputFile  . "<br>";
	WriteJsonFile ($JsonOutputFile, $JsonFileData); 
	//pre_R($RKRKJsonStandardPointSettings);

}
*/
//echo "Update all<br>";
if (($FeldIDtoUpdate!="")) {
	if ($FeldIDtoUpdate!="alle") {
		if  ($JsonOutputFile!="") {
			$SystemInfo .= "<b>" . $FeldIDtoUpdate . "</b><br>";
			$SystemInfo .= "<LI>Update JSON: " . $JsonFileName . "<br>";
			$JsonFileData = ReadJsonFile($JsonFileName);
			//pre_r($JsonFileData);
			echo  "<br>" . "<br>" . $FeldIDtoUpdate . "<br>";
			UpdateJsonDataStructure($JsonFileData, $UpdateFields,$DataSet,  explode("-",$FeldIDtoUpdate)[0]);
			$historyfile = $historyfilelocation . date("YmdH") . "00_" . pathinfo($JsonFileName)[basename];
			if (!copy($JsonFileName, $historyfile)) {
				$SystemInfo .= "failed to copy $file...\n";
			}
			$SystemInfo .=  "<LI>Save JSON File to " . $JsonOutputFile  . "<br>";
			WriteJsonFile ($JsonOutputFile, $JsonFileData); 
			//pre_R($RKRKJsonStandardPointSettings);
		}
	} else {
		//update all files from Region
		$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
		if ($FeldIDListe<>"") {
			$FeldIDArray = explode(" " ,$FeldIDListe);
		}
		foreach ($Feldkatalog as $JKey => $JRegion) {
			if (in_array($JKey,$RegionsSelected)) {
				foreach ($JRegion as $Field) {
				//fiter, wenn FeldList vorhanden 
				if ((in_array($Field["FeldID"], $FeldIDArray)) or ($FeldIDListe=="")) 
					{
					$SystemInfo .= "<b>" . $Field["FeldID"] . "</b><br>";
					foreach (explode(",",$Field["JasonFile"]) as $JsonFileName)
						{
							$JsonInputFile= $RKRKLocalLoactionMissionFiles.trim($JsonFileName);
							//echo $JsonInputFile . "<br>";
							$SystemInfo .= "<LI>Update JSON: " . $JsonInputFile . "<br>";
							$JsonFileData = ReadJsonFile($JsonInputFile);
							//$JsonFileData = ReadJsonFile("../../rkropen/Missionen/21_Aeugst/21-014_Boden_Ost_1131_50_CH_22");
							//pre_r($JsonFileData);
							UpdateJsonDataStructure($JsonFileData, $UpdateFields,$DataSet, $JKey);
							$historyfile = $historyfilelocation . date("YmdH") . "00_" . pathinfo($JsonFileName)[basename];
							if (!copy($file, $historyfile)) {
								$SystemInfo .= "failed to copy $file...\n";
							}
							
							if ($OutputDir<>"") {
							//if ($Testoutput) {
								//write direct to test location
								$SystemInfo .=  "<LI>T Save JSON File to " . $OutputDir . pathinfo($JsonFileName)[basename]  . "<br>";
								WriteJsonFile ($OutputDir .  pathinfo($JsonFileName)[basename], $JsonFileData);
							} else {
								//write direct to mission location
								$SystemInfo .=  "<LI>Save JSON File to " . pathinfo($JsonFileName)[dirname]  ."/" . pathinfo($JsonFileName)[basename]  . "<br>";
								WriteJsonFile ($RKRKLocalLoactionMissionFiles.pathinfo($JsonFileName)[dirname]  ."/".  pathinfo($JsonFileName)[basename], $JsonFileData); 
							}
						}
					}
				}	
			}
		}
		//pre_R ($EnabledRegion);
		if ($EnabledRegion["EX"]==1) {
			echo "EXTERN FILES" , "<br>";
			//behandlung externe files
			//echo "ExFileDir: " . $ExIntputDirectory . "<br>";
			$ExFiles = array_diff(scandir($ExIntputDirectory, SCANDIR_SORT_DESCENDING), array('..', '.'));;
			//pre_R($ExFiles);
			foreach ($ExFiles as $key => $ExFile) {
				//echo "ExFile: " . $key . " - " . $ExFile . "<br>";
				$JsonInputFile= $ExIntputDirectory.trim($ExFile);
				$JsonFileData = ReadJsonFile($JsonInputFile);
				$file_parts = pathinfo($JsonInputFile);
				if ($file_parts['extension']=="json") {
					UpdateJsonDataStructure($JsonFileData, $UpdateFields,$DataSet, "");
					//echo "Write to: " . $TestOutputDirectory  ."/".  pathinfo($ExFile)[basename] . "<br>";
					WriteJsonFile ($TestOutputDirectory ."/".  pathinfo($ExFile)[basename], $JsonFileData); 
				}	
			}
		}
		//pre_R ($EnabledRegion);
		if ($EnabledRegion["DWNLD"]==1) {
			//echo "OUTPUT FILES" , "<br>";
			//behandlung externe files
			//echo "OutputFileDir: " . $TestOutputDirectory . "<br>";
			$DwnldFiles = array_diff(scandir($TestOutputDirectory, SCANDIR_SORT_DESCENDING), array('..', '.'));;
			//pre_R($DwnldFiles);
			foreach ($DwnldFiles as $key => $DwnlodFile) {
				//echo "OutputFile: " . $key . " - " . $DwnlodFile . "<br>";
				$JsonInputFile= $TestOutputDirectory.trim($DwnlodFile);
				$JsonFileData = ReadJsonFile($JsonInputFile);
				$file_parts = pathinfo($JsonInputFile);
				if ($file_parts['extension']=="json") {
					UpdateJsonDataStructure($JsonFileData, $UpdateFields,$DataSet, "");
					//echo "Write to: " . $TestOutputDirectory  ."/".  pathinfo($DwnlodFile)[basename] . "<br>";
					WriteJsonFile ($TestOutputDirectory ."/".  pathinfo($DwnlodFile)[basename], $JsonFileData); 
				}	
			}
		}
	}
}

//===================================
// Table
//===================================

$FontSizeInPoints = 11;
if ($FontSize== "") {
	$FontSize= "100%";
}
$TableColor =  GetTableColor ("");
AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

//pre_R($RegionsSelected);
if (count($RegionsSelected)>0) {
	//$RegionList = array("21","25"); 
	DoJsonDataList($RegionsSelected, 0);
//	DoJsonDataList($RegionList, 0);
}
echo '</form>';
if (($SystemInfo != "")) {
	echo "<div style='background-color: #FEF9E7;'>". chr(10);
	echo "System Meldungen: <br>". chr(10);
	echo $SystemInfo;
	echo "</div>". chr(10);
}


//===================================
// TEST functions
//===================================


//if (($DebugInfo != "") && ($DebugWork)) {
//	echo "<br>". chr(10);
//	echo "<div style='background-color: #FDEDEC;'>". chr(10);
//	echo "Debug Meldung: <br>". chr(10);
	//$Testfile ="28_Mettmenstetten/28-002_Bilimoos_32_50_TC_21";
	//ReadAndCompareJsonData($Testfile);
//	$TestDirectory = "../../rkropen/UAVJsonReference/";
//	$JsonRefFile = $TestDirectory."28-010_Homberg_1227_50_TC_23_0.711ha_7WP";
//	$JsonDataFile = $TestDirectory."28-010_Homberg_1227_50_TC_21";
//	$JsonOutputFile = $TestDirectory."28-010_Homberg_1227_50_TC_23";

//	ReadAndCompareJsonData($JsonRefFile, $JsonDataFile); 
//	UpdateJsonDataStructure($JsonDataFile, $JsonOutputFile);

//	echo "</div>". chr(10);
//}

/*
echo "<H2>" . "Test KMZ" . "</H2>";
$FileName= "TF-006_26-Schutzenhaus-TEST_149_50_TC_23.kmz";
$FDir = $RKRKLocalLoactionMissionFiles.$RegionSettings["TF"]["Dir"];
echo $KMZFile= $RKRKLocalLoactionMissionFiles.$RegionSettings["TF"]["Dir"]."/".$FileName;
echo "<br>";
//print_R(scandir($FDir));
		if(is_file($KMZFile)) {  //To check whether the filename is valid
			echo "File available" . $KMZFile . "<br>";

			$data = file_get_contents($KMZFile); // url of the KMZ file
			pre_R($data);
			file_put_contents("/tmp/kmz_temp",$data);
			ob_start();
			echo "1" . "<br>";
			passthru('unzip -p /tmp/kmz_temp');
			$xml_data = ob_get_clean();
			echo "2" . "<br>";
			header("Content-type: text/xml");
			echo $xml_data;
			echo "3" . "<br>";
		 }
*/

//echo $CSVJsonHealthFile;

?>
<script type='text/javascript'>
<?php
echo "var iconloc = '". $IconLoc . "';\n";
echo "var FileName = '". $CSVJsonHealthFile . "';\n";
echo "var historyloc = '". $historyfilelocation. "';\n";
echo "var buttonheight = ". $ButtonHeight . ";\n";
?>

// update Excellink
	if (FileName!="") {
		exceldwnld.innerHTML = "<a href='" + historyloc + FileName + "' ><image src='" + iconloc + "/excel.png' height='" + buttonheight + "'  title='Excel Report herunterladen'></a>";
		//exceldwnld.innerHTML  = "<a href='".$historyfilelocation.$CSVJsonHealthFile . "' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Report herunterladen'></a> ";
	} else  {
		exceldwnld.innerHTML = "";
	}
</script>


</body>
</html>