<?php
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_jsonfiles.php');
	include_once('scripts/rkrk_dji_kmz_interface.php');
	
	
echo "<H1>TEST M2 M3 conversion</H1>"; 

//TestJSONFile
 $TempDir= "../tmp/testfiles/";
$JSON_Testfile_Pilot= Array();
$i=0;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M2_320_L_50_2.009ha_9WP.json";
$JSON_Testfile_Pilot[$i]["DroneType"] = "FB320 50°";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3TD_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M2AE_P1_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3T_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M3TD_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M4T_85_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M30T_60_2.009ha_7WP.json";
$i++;
$JSON_Testfile_Pilot[$i]["File"] = "Test_M300_60_2.009ha_7WP.json";
$JSON_Testfile_Pilot[$i]["DroneType"] = "Zenmuse H30T";

$ToDrone = "4T";


//pre_r($JSON_Testfile_Pilot);
foreach($JSON_Testfile_Pilot as $key => $TestFile) {
	//pre_R( $TestFile);
	$JSON_Testfile = $TempDir. $TestFile["File"];
	//Load JSON File
	//echo "Json Testfile: " . $JSON_Testfile["File"] . "<br>";

	$JsonP1 = ReadJsonFile($JSON_Testfile);
	//pre_R($JsonP1);

	if (array_key_exists("DroneType",$TestFile)) {
		$JsonOutput = JSONPilot1toPilot2($JsonP1, "3T", $ToDrone, $TestFile["DroneType"]);
	} else {
		$JsonOutput = JSONPilot1toPilot2($JsonP1, "3T", $ToDrone);
	}
	//pre_r($JsonOutput);
	if ($JsonOutput!="") {
		$Filename = $JsonOutput["fileName"];
		$OutputFilename = $TempDir.pathinfo($JsonOutput["fileName"],PATHINFO_FILENAME)."_testoutput". ".json";
		echo "Datei " . $TestFile["File"] . " konvertiert nach " . $ToDrone . ": " . $OutputFilename ."<br>" ."<br>"; 
		WriteJsonFile ($OutputFilename , $JsonOutput);
	} else {
		echo "Datei " . $TestFile["File"] . " KANN NICHT nach " . $ToDrone . " konvertiert werden." ."<br>"."<br>";
	}

}	

?>