<?php
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_jsonfiles.php');
	include_once('scripts/rkrk_kmz.php');
	include_once('scripts/rkrk_dji_kmz_interface.php');
	

////////////////////////////////////
// Definitione
////////////////////////////////////


////////////////////////////////////
// Functions
////////////////////////////////////

/* Mission  UAV 21.05.2025
"djiPilot2": {
	"droneType": "mavic3T",
	"directionMode": "followWayline",
	"endAction": "noAction",
	"pathModus": "straight",
	"speed": 5,
	"defaultCurveSize": 5,
	"height": 50,
	"takeOffSecurityHeight": 20,
	"curveSize": 5,
	"gimbal": "none",
	"powerSavingMode": false,
	"dronePayload1": null,
	"dronePayload2": null,
	"gimbalControl": "manuel",
	"dronePayload3": null,
	"flightHeightMode": "relativeToStartPoint",
	"flyToWaylineMode": true,
	"waypointType": "coordinateTurn",
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

/* Waypoint UAV 21.05.2025
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
	"curveSize": 5,
	"useMissionPathMode": true,
	"useMissionYawDirection": true,
	"useMissionWaypointType": true,
	"speed": 5,
	"useMissionSpeed": true,
	"height": 50,
	"useMissionHeight": true,
	"type": 1,
	"pathMode": "straight",
	"autoHeight": true,
	"yawDirection": "followWayline",
	"waypointType": "coordinateTurn",
	"turningDistance": 5,
	"alignmentAircraft": "followBadArc",
	"aboveSea": {
		"value": 635,
		"lat": 47.282983,
		"lng": 8.491918
	},
	"_height": 50
}
*/


/* KMZ template 21.05.2025 M4T
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:wpml="http://www.dji.com/wpmz/1.0.2">
  <Document>
    <wpml:author>UAV</wpml:author>
    <wpml:createTime>1747203317802</wpml:createTime>
    <wpml:updateTime>1747203395963</wpml:updateTime>
    <wpml:missionConfig>
      <wpml:droneInfo>
        <wpml:droneEnumValue>150</wpml:droneEnumValue>
        <wpml:droneSubEnumValue>1</wpml:droneSubEnumValue>
      </wpml:droneInfo>
      <wpml:payloadInfo>
        <wpml:payloadEnumValue>89</wpml:payloadEnumValue>
        <wpml:payloadSubEnumValue>0</wpml:payloadSubEnumValue>
        <wpml:payloadPositionIndex>0</wpml:payloadPositionIndex>
      </wpml:payloadInfo>
      <wpml:finishAction>noAction</wpml:finishAction>
      <wpml:exitOnRCLost>executeLostAction</wpml:exitOnRCLost>
      <wpml:executeRCLostAction>goBack</wpml:executeRCLostAction>
      <wpml:flyToWaylineMode>safely</wpml:flyToWaylineMode>
      <wpml:takeOffSecurityHeight>20</wpml:takeOffSecurityHeight>
      <wpml:globalTransitionalSpeed>5.0</wpml:globalTransitionalSpeed>
    </wpml:missionConfig>
    <Folder>
      <wpml:globalHeight>85.0</wpml:globalHeight>
      <wpml:caliFlightEnable>0</wpml:caliFlightEnable>
      <wpml:autoFlightSpeed>5.0</wpml:autoFlightSpeed>
      <wpml:globalWaypointHeadingParam>
        <wpml:waypointHeadingMode>followWayline</wpml:waypointHeadingMode>
      </wpml:globalWaypointHeadingParam>
      <wpml:globalWaypointTurnMode>coordinateTurn</wpml:globalWaypointTurnMode>
      <wpml:globalUseStraightLine>1</wpml:globalUseStraightLine>
      <wpml:waylineCoordinateSysParam>
        <wpml:coordinateMode>WGS84</wpml:coordinateMode>
        <wpml:heightMode>relativeToStartPoint</wpml:heightMode>
        <wpml:positioningType>GPS</wpml:positioningType>
      </wpml:waylineCoordinateSysParam>
      <wpml:gimbalPitchMode>manual</wpml:gimbalPitchMode>
      <wpml:templateType>waypoint</wpml:templateType>
      <wpml:templateId>0</wpml:templateId>
      <Placemark>
        <Point>
          <coordinates>8.464291973221094,47.21943633544783</coordinates>
        </Point>
        <wpml:index>0</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>85</wpml:ellipsoidHeight>
        <wpml:height>85</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>0</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.46420139968026,47.21797466849683</coordinates>
        </Point>
        <wpml:index>1</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>93</wpml:ellipsoidHeight>
        <wpml:height>93</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>5</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.463264989637974,47.21937516997079</coordinates>
        </Point>
        <wpml:index>2</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>86</wpml:ellipsoidHeight>
        <wpml:height>86</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>5</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.463643969325677,47.21945954599989</coordinates>
        </Point>
        <wpml:index>3</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>85</wpml:ellipsoidHeight>
        <wpml:height>85</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>5</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.464566681677951,47.21807951596547</coordinates>
        </Point>
        <wpml:index>4</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>92</wpml:ellipsoidHeight>
        <wpml:height>92</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>5</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.464931965124142,47.218184362273384</coordinates>
        </Point>
        <wpml:index>5</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>90</wpml:ellipsoidHeight>
        <wpml:height>90</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>5</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
      <Placemark>
        <Point>
          <coordinates>8.464022950225578,47.21954392077905</coordinates>
        </Point>
        <wpml:index>6</wpml:index>
        <wpml:useGlobalHeight>0</wpml:useGlobalHeight>
        <wpml:ellipsoidHeight>85</wpml:ellipsoidHeight>
        <wpml:height>85</wpml:height>
        <wpml:useGlobalSpeed>1</wpml:useGlobalSpeed>
        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>
        <wpml:useStraightLine>1</wpml:useStraightLine>
        <wpml:waypointTurnParam>
          <wpml:waypointTurnDampingDist>0</wpml:waypointTurnDampingDist>
          <wpml:waypointTurnMode>coordinateTurn</wpml:waypointTurnMode>
        </wpml:waypointTurnParam>
        <wpml:useGlobalHeadingParam>1</wpml:useGlobalHeadingParam>
      </Placemark>
    </Folder>
  </Document>
</kml>

*/

////////////////////////////////////
// Main - TestFile
////////////////////////////////////

	
echo "<H1>TEST KMZ Creation</H1>"; 

//TestJSONFile
$TempDir= "../tmp/testfiles/";
$JSON_Testfile_Pilot= Array();
$i=0;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3TD_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3T_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3TD_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M4T_85_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M30T_60_2.009ha_7WP.json";
$ToDrone = "4T";

$SubDirKMZ = "wpmz/";
$Directory = "../tmp/conversion/";
$ErrorMessage= "";

//pre_r($JSON_Testfile_Pilot);
foreach($JSON_Testfile_Pilot as $key => $TestFile) {
	//pre_R( $TestFile);
	$JSON_Testfile = $TempDir. $TestFile["File"];
	//Load JSON File
	//echo "Json Testfile: " . $JSON_Testfile["File"] . "<br>";

	$JsonData = ReadJsonFile($JSON_Testfile);
	//pre_R($JsonData);
	$JsonSplit = SplitJSONToStructure($JsonData, $ErrorMessage);
	//pre_R($JsonSplit);

	// Compile PointData to create KMZ
	$KmzData["Profile"] = $JsonSplit["Profile"];
	$KmzData["BaseInformation"] = $JsonSplit["BaseInformation"];
	$KmzData["missionSettings"] = $JsonSplit["missionSettings"]["djiPilot2"];
	$KmzData["Wegpunkte"] = $JsonSplit["Punkte"]["Wegpunkte"];
	//pre_r($PointData);
	
	//compile KMZ/KML data
	$KmzOutputData["Document"] = compileKmzData($KmzData);
	//pre_r($KmzOutputData);
	
	// CREATE KML data from array
	// --------------------------
	$XmlFileContent = arrayToXml($KmzOutputData);
	//var_dump($XmlFileContent);
	
	// CREATE DIRECTORY
	// ----------------
	
	$TargetFilename = $Directory. pathinfo($TestFile["File"], PATHINFO_FILENAME)."_neu" . ".kmz";

	$ZipDirectory = $Directory.pathinfo($TargetFilename, PATHINFO_FILENAME)."/".$SubDirKMZ;
	$TargetZipFile = pathinfo($TargetFilename, PATHINFO_FILENAME);
	$TargetDirectory = $Directory.$TargetZipFile."/".$SubDirKMZ;
	$OutputKmzFile = $TargetDirectory.$KmzFileName;
	//echo "TargetDirectory " . $TargetDirectory . " OutputKmz: " . $OutputKmzFile . "<br>";

	CreateDirectoryAndSaveFile($TargetDirectory, $OutputKmzFile, $XmlFileContent);
	//CreateZipFile($Directory, $TargetZipFile, $OutputKmzFile, "zip", true);
	$ZipFileLocation =  CreateZipFile($Directory, $TargetZipFile, $OutputKmzFile, "kmz", false);

	$icon= $IconLoc . '/kmzxx.png';

	echo '<a href="' . $ZipFileLocation. '" download >';
	echo '<img src="' . $icon. '" height="' . $ButtonHeight . '"  title="Generierte Datei ' . basename($ZipFileLocation) . ' herunterladen.">';
	echo '</a> ';
	echo '<a href="' . $ZipFileLocation. '" download >' . basename($ZipFileLocation).' </a>' . chr(10);
	//if (($testmode) or false)
		echo ' - <a href="' . $Directory.$TargetZipFile."/".$SubDirKMZ.'/template.kml' . '" download >' . 'KML' . ' </a>' . chr(10);		
	echo '<br><br>' . chr(10);
}	

?>