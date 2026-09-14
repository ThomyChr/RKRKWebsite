<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
// Handling of ZIP and KMZ files 
// Needs ZIP package enabled on Server to run

	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_dji_kmz_interface.php');

	// ===========================================
	// Definitions
	// ===========================================
	$SubDirKMZ = "wpmz/";
	$KmzFileName = "template.kml";


$KMZTemplate = Array(
	// KMZ Header
	"header" => Array(
		"wpml:author" => "UAV",
		"wpml:createTime" => time(),
		"wpml:updateTime" => time(),
		"wpml:missionConfig" => Array(
			"wpml:droneInfo" => Array(
				"wpml:droneEnumValue" => 150,
				"wpml:droneSubEnumValue" => 1,
				),
			"wpml:payloadInfo" => Array(
				"wpml:payloadEnumValue" => 89,
				"wpml:droneSubEnumValue" => 0,
				"wpml:payloadPositionIndex" => 0,
				),
			"wpml:finishAction" => "noAction",
			"wpml:exitOnRCLost"=> "executeLostAction",
			"wpml:executeRCLostAction" => "goBack",
			"wpml:flyToWaylineMode" => "safely",
			"wpml:takeOffSecurityHeight" => 20,
			"wpml:globalTransitionalSpeed" => 5.0,
		),
	),

	// KMZ Mission
	"mission" => Array(
		//Header for Waypoints
		"Folder" => Array (
			"wpml:globalHeight" => 85.0,
			"wpml:caliFlightEnable" => 0,
			"wpml:autoFlightSpeed" => 5.0,
			"wpml:globalWaypointHeadingParam" => Array(
				"wpml:waypointHeadingMode" => "followWayline",
			),
			"wpml:globalWaypointTurnMode" => "coordinateTurn",
			"wpml:globalUseStraightLine"=> 1,
			"wpml:waylineCoordinateSysParam" => Array(
				"wpml:coordinateMode" => "WGS84",
				"wpml:heightMode" => "relativeToStartPoint",
				"wpml:positioningType" => "GPS",
				),
			"wpml:gimbalPitchMode" => "manual",
			"wpml:templateType" => "waypoint",
			"wpml:templateId" => 0,

			//WaypointTemplate - repeating on any Point
		),
	),

	// KMZ Waypoint
	"waypoint" => Array(
		"Folder" => Array (
			"Placemark" => Array (
				"Point" => Array (
					"coordinates" => Array(
						8.464291973221094, // lng
						47.21943633544783  // lat
					),
				),
				"wpml:index" => 0,
				"wpml:useGlobalHeight" => 0,
				"wpml:ellipsoidHeight" => 85,
				"wpml:height" => 85,
				"wpml:useGlobalSpeed" => 1,
				"wpml:useGlobalTurnParam" => 0,
				"wpml:useStraightLine" => 1,
				"waypointTurnParam" => Array (
					"wpml:waypointTurnDampingDist" => 0,
					"wpml:waypointTurnMode" => "coordinateTurn",
				),
				"wpml:useGlobalHeadingParam" => 1,
			),
		),
	),

);

//pre_r($KMZTemplate);


	//echo "MissionLoaction: ". $RKRKLocalLoactionMissionFiles . "<br>";
	//echo "TempLocation: ". $tempfilelocation . "<br>";

	// ===========================================
	// Functions
	// ===========================================

	function UnzipAndStoreFile ($ZipFile, $UnzipDirectory) {
		// assuming file.zip is in the same directory as the executing script.

		// get the absolute path to $file
		$path = $UnzipDirectory.pathinfo($ZipFile, PATHINFO_FILENAME);
		echo "zip archive: ".$path. "<br>";

		$zip = new ZipArchive;
		//echo "open ZipFile<br>";
		$res = $zip->open($ZipFile);
		$res=true;
		if ($res === TRUE) {
		  // extract it to the path we determined above
		  $zip->extractTo($path);
		  $zip->close();
		  //echo "GREAT! $ZipFile extracted to $path!<br>";
		} else {
		  echo "File: $ZipFile not found!<br>";
		}
	}
	

	function GetArrayFromKMZ($KmzFile) {
	// Einlesen der KMZ Daten
		//echo "KMZ File: " . $KmzFile . "<br>";
		if(file_exists($KmzFile)) {
			//$KmzData = simplexml_load_file($xmlMissionLocation);     //Lùdt die XML-Datei in ein Objekt
			//echo "file exists <br>";
			// $LoadXML = utf8_encode(htmlentities_decode(file_get_contents($KmzFile)));
			$LoadXML = mb_convert_encoding(htmlentities_decode(file_get_contents($KmzFile)), 'UTF-8', mb_list_encodings());
			//pre_R($LoadXML);
			$p = xml_parser_create();
			xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0); // <-- disable case folding
			xml_parse_into_struct($p, $LoadXML, $vals, $index);
			xml_parser_free($p);
			//echo "Index array\n";
			//pre_r($index);
			//echo "\nVals array\n";
			//pre_r($vals);
			$json = json_encode($vals);
			$array = json_decode($json,TRUE);
			//pre_R($array);
			$array = array();
			$levelkey=array();
			$AddPointNumber="";
			$PointNumber=-1;
			foreach ($vals as $key => $entry) {
				$XMLTagArray = Array("Document","Folder","Placemark","Point","coordinates");
				$WmplLabel = "wpml:";
				$lenLabel = strlen($WmplLabel);
				//identify if next entries are points
				//echo "FO: " . $vals[$key]["tag"] . " - " . $vals[$key+1]["tag"] . " - " . $vals[$key+2]["tag"] . "<br>";
				if (($vals[$key]["tag"]=="Folder") and ($vals[$key+1]["tag"]=="Placemark") and ($vals[$key+2]["tag"]=="Point")) {
					$PointNumber++;
					//echo "Punkte: " .  $PointNumber . "<br>"; 
				}
				if ((in_array($entry["tag"],$XMLTagArray)) or ($WmplLabel==substr($entry["tag"],0,$lenLabel))) {
					if ($WmplLabel==substr($entry["tag"],0,$lenLabel)) {
						//wmpl
						$levelkey[$entry["level"]]=$entry["tag"];
					} else {
						//kml
						if (($PointNumber>=0) and ($entry["tag"]=="Placemark")) {
							$levelkey[$entry["level"]]=$entry["tag"]."-".sprintf('%03d', $PointNumber);
						} else {
							$levelkey[$entry["level"]]=$entry["tag"];
						}
					}

					if (trim($entry["value"]??'')!="") {
						//echo "Wert: " . $entry["value"] . "<br>";
						switch ($entry["level"]) {
							//case 1:
							//	$array[$levelkey[1]]=$entry["value"];
							//	echo "Wert: " . $entry["value"] . " to " . $levelkey[1] . "<br>";
							//	break;
							case 2:
								$array[][$levelkey[2]]=$entry["value"];
								break;
							case 3:
								$array[$levelkey[2]][$levelkey[3]]=$entry["value"];
								break;
							case 4:
								$array[$levelkey[2]][$levelkey[3]][$levelkey[4]]=$entry["value"];
								break;
							case 5:
								$array[$levelkey[2]][$levelkey[3]][$levelkey[4]][$levelkey[5]]=$entry["value"];
								break;
							case 6:
								$array[$levelkey[2]][$levelkey[3]][$levelkey[4]][$levelkey[5]][$levelkey[6]]=$entry["value"];
								break;
							case 7:
								$array[$levelkey[2]][$levelkey[3]][$levelkey[4]][$levelkey[5]][$levelkey[6]][$levelkey[7]]=$entry["value"];
								break;
						}
					}	
				}
			}
		} else {
			$fileloaded = false;
			echo '        <p>Die Datei names '. $felder_file .' konnte nicht geùffnet werden</p>';
		}
		Return $array;
	}


	function arrayToXml($array) {
		
		$XMLText="";
		$tab = "  ";
		$CorLabel= "Placemark";
		

		// L1
		foreach ($array as $k1 => $v1) {
			$l1="";
			//echo $k1 . "<br>";
			if (is_array($v1)) {
				$l1= $tab ."<" .$k1 . ">".chr(10);

				// S L2
				foreach ($v1 as $k2 => $v2) {
					$l2="";
					//echo $k2 . "<br>";
					
					//echo substr($k2,0,strlen($CorLabel)) . " " . (substr($k2,0,strlen($CorLabel))==$CorLabel) ."<br>";
					if (substr($k2,0,strlen($CorLabel))==$CorLabel) {
						$la2=$CorLabel;
						//echo "Korrektur: " . $la2 . "<br>";
					} else {
						$la2 = $k2;
					}
					if (is_array($v2)) {
						$l2	= $tab .$tab ."<" .$la2 . ">".chr(10);
						//pre_R($v2);

						// S L3
						foreach ($v2 as $k3 => $v3) {
							$l3="";
							//echo $k3 . "<br>";
							//echo substr($k3,0,strlen($CorLabel)) . " " . (substr($k3,0,strlen($CorLabel))==$CorLabel) ."<br>";
							if (substr($k3,0,strlen($CorLabel))==$CorLabel) {
								$la3=$CorLabel;
								//echo "Korrektur: " . $la3 . "<br>";
							} else {
								$la3 = $k3;
							}
							if (is_array($v3)) {
								$l3	= $tab .$tab .$tab ."<" .$la3 . ">".chr(10);
								//pre_R($v3);

								// S L4
								foreach ($v3 as $k4 => $v4) {
									$l4="";
									//echo $k4 . "<br>";
									//echo substr($k4,0,strlen($CorLabel)) . " " . (substr($k4,0,strlen($CorLabel))==$CorLabel) ."<br>";
									if (substr($k4,0,strlen($CorLabel))==$CorLabel) {
										$la4=$CorLabel;
										//echo "Korrektur: " . $la4 . "<br>";
									} else {
										$la4 = $k4;
									}
									if (is_array($v4)) {
										$l4	= $tab .$tab .$tab . $tab ."<" .$la4 . ">".chr(10);
										//pre_R($v4);

										// S L5
										foreach ($v4 as $k5 => $v5) {
											$l5="";
											//echo $k5 . "<br>";
											
											if (is_array($v5)) {
												$l5	= $tab .$tab .$tab . $tab . $tab ."<" .$k5 . ">".chr(10);
												//pre_R($v5);

												// S L6
												foreach ($v5 as $k6 => $v6) {
													$l6="";
													//echo $k6 . "<br>";
													
													if (is_array($v6)) {
														$l6	= $tab .$tab .$tab . $tab . $tab . $tab ."<" .$k6 . ">".chr(10);
														//pre_R($v6);

														// S L7
														foreach ($v6 as $k7 => $v7) {
															$l6="";
															//echo $k7 . "<br>";
															
															if (is_array($v7)) {
																$l7	= $tab .$tab .$tab . $tab . $tab .$tab ."<" .$k7 . ">".chr(10);
																//pre_R($v7);
																// S L8
																
																// E L8
																$l7 .= $tab . $tab . $tab . $tab . $tab . $tab ."</" . $k7 . ">" . chr(10);
															} else {
																$l7.= $tab .$tab . $tab . $tab . $tab . $tab . "<" .$k7 . ">". $v7 . "</" .$k7 . ">" . chr(10);
															}	
															$l6 .= $l7;
														}
														// E L6

														$l6 .= $tab . $tab . $tab . $tab . $tab . $tab ."</" . $k6 . ">" . chr(10);
													} else {
														$l6.= $tab .$tab . $tab . $tab . $tab . $tab . "<" .$k6 . ">". $v6 . "</" .$k6 . ">" . chr(10);
													}	
													$l5 .= $l6;
												}
												// E L6

												$l5 .= $tab . $tab . $tab . $tab . $tab ."</" . $k5 . ">" . chr(10);
											} else {
												$l5.= $tab .$tab . $tab . $tab . $tab . "<" .$k5 . ">". $v5 . "</" .$k5 . ">" . chr(10);
											}	
											$l4 .= $l5;
										}
										// E L5

										$l4 .= $tab . $tab . $tab . $tab ."</" . $la4 . ">" . chr(10);
									} else {
										$l4.= $tab .$tab . $tab . $tab . "<" . $la4 . ">". $v4 . "</" . $la4 . ">" . chr(10);
									}	
									$l3 .= $l4;
								}
								// E L4

								$l3 .= $tab . $tab . $tab ."</" . $la3 . ">" . chr(10);
							} else {
								$l3.= $tab .$tab . $tab . "<" . $la3 . ">". $v3 . "</" . $la3 . ">" . chr(10);
							}	
							//echo "<br>L2: " . $l2;
							$l2 .= $l3;
						}
						// E L3

						$l2 .= $tab . $tab . "</" . $la2 . ">" . chr(10);
					} else {
						$l2.= $tab . $tab . "<" .$la2 . ">". $v2 . "</" .$la2 . ">" . chr(10);
					}	
					//echo "<br>L2: " . $l2;
					$l1 .= $l2;
				}
				$l1 .= $tab ."</" .$k1 . ">" . chr(10);
				
			} else {
				$l1.= $tab ."<" .$k1 . ">". $v1 . "</" .$k1 . ">" . chr(10);
			}	
			$XMLText .= $l1;
			//echo "<br>" . $XMLText;
		}
		return $XMLText ;
	}


	Function CreateZipFile($TargetDirectory, $TargetFile, $OutputKmzFile, $Format="kmz", $Output=false) {
	global $SubDirKMZ;

		// Zip diretory to file
		// --------------------
		$zip = new ZipArchive;
		
		//echo "Target KmzFile: " . $TargetDirectory.$TargetFile . "<br>";
		//echo "Target KmzFile: " . $SubDirKMZ.$KmzFileName . "<br>";
		$res = $zip->open($TargetDirectory.$TargetFile.'.'. $Format  , ZipArchive::CREATE);
		if ($res === TRUE) {
			//$zip->addFromString('test.txt', 'file content goes here');
			// $zip->addFile($OutputKmzFile, $SubDirKMZ.$KmzFileName."template.kml");
			$zip->addFile($OutputKmzFile, $SubDirKMZ."template.kml");
			$zip->close();
			$ZipFileLocation = $TargetDirectory.$TargetFile.'.'.$Format;
			if ($Output) {
				echo '<a href="' . $ZipFileLocation . '" download>' . $Format . '</a> - ' ;
				echo '<a href="' . $OutputKmzFile . '" download >kml</a> - ' ;
				echo $TargetFile . ': ' . strtoupper($Format);
				//echo ' - created and stored to ' . $TargetDirectory . '<br>';
			}	
		} else {
			if ($Output) {
				echo $TargetFile . ': creation of ' . strtoupper($Format) . ' file failed <br>';
			}
		}
		return $ZipFileLocation;
	}
	
	Function CreateDirectoryAndSaveFile($TargetDirectory, $OutputKmzFile, $XmlFileContent) {

		// CREATE DIRECTORY
		// ----------------

		$XmlHeader = '<?xml version="1.0" encoding="UTF-8"?>'. chr(10);
		$XmlHeader .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:wpml="http://www.dji.com/wpmz/1.0.2">'. chr(10);
		$XmlFooter = '</kml>'. chr(10);

		//echo "Dir: " . $TargetDirectory . "<br>";
		//var_dump(is_dir($TargetDirectory));
		if (!(is_dir($TargetDirectory))) {
			mkdir($TargetDirectory, 0777, true);
			//echo "NewDir: ". $TargetDirectory . "<br>";
		}

		// Write File to Directory
		// -----------------------
		//echo "FileName: ". $OutputKmzFile . "<br>";
		$myfile = fopen($OutputKmzFile, "w") or die("Unable to open file!");
		$filecontent = $XmlHeader . $XmlFileContent .$XmlFooter;
		fwrite($myfile, $filecontent);
		fclose($myfile);
	}

	Function ChangeDroneTypeAndHeightInKMZData($SourceDrone, $TargetDrone, &$KmzData) {
		global $DJI_DroneInformation;
		
		//pre_r($KmzData["Document"]["wpml:missionConfig"]);
		//Set New Drone Settings
		$KmzData["Document"]["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneEnumValue"] = $DJI_DroneInformation["wpml:droneEnumValue"][$TargetDrone];
		$KmzData["Document"]["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneSubEnumValue"] = $DJI_DroneInformation["wpml:droneSubEnumValue"][$TargetDrone];
		$KmzData["Document"]["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadEnumValue"] = $DJI_DroneInformation["wpml:payloadEnumValue"][$TargetDrone];
		$KmzData["Document"]["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadSubEnumValue"] = $DJI_DroneInformation["wpml:payloadSubEnumValue"][$TargetDrone];
		$KmzData["Document"]["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadPositionIndex"] = $DJI_DroneInformation["wpml:payloadPositionIndex"][$TargetDrone];
		
		//Adjust Height
		$SourceHeight = $KmzData["Document"]["Folder"]["wpml:globalHeight"];
		//echo "SourceHeight: " .$SourceHeight . "<br>";
		$TargetHeight = GetFlightHeightForAltDrone($SourceDrone, $TargetDrone, $SourceHeight, "IRCamAngle_H", 5);
		//echo "TargetHeight: " .$TargetHeight . "<br>";
		$HeightOffset =  $TargetHeight - $SourceHeight;
		//echo "HeightOffset: " .$HeightOffset . "<br>";
		
		$KmzData["Document"]["Folder"]["wpml:globalHeight"] = number_format($KmzData["Document"]["Folder"]["wpml:globalHeight"]+$HeightOffset,1);
		foreach ($KmzData["Document"]["Folder"] as $key => $PlaceMark) {
			if (is_array($PlaceMark) && array_key_exists("Point",$PlaceMark)) {
				//pre_R($PlaceMark);
				$KmzData["Document"]["Folder"][$key]["wpml:ellipsoidHeight"]= number_format($KmzData["Document"]["Folder"][$key]["wpml:ellipsoidHeight"]+$HeightOffset,0);
				$KmzData["Document"]["Folder"][$key]["wpml:height"]= number_format($KmzData["Document"]["Folder"][$key]["wpml:height"]+$HeightOffset,0);
			}
		}	
	/*    FOR M30T
		  <wpml:payloadParam>
			<wpml:payloadPositionIndex>0</wpml:payloadPositionIndex>
			<wpml:meteringMode>average</wpml:meteringMode>
			<wpml:dewarpingEnable>0</wpml:dewarpingEnable>
			<wpml:returnMode>singleReturnStrongest</wpml:returnMode>
			<wpml:samplingRate>240000</wpml:samplingRate>
			<wpml:scanningMode>nonRepetitive</wpml:scanningMode>
			<wpml:modelColoringEnable>0</wpml:modelColoringEnable>
			<wpml:imageFormat/>
		  </wpml:payloadParam>
	*/

		if (in_array($TargetDrone, Array("30T"))) {
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:payloadPositionIndex"]=0;
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:meteringMode"]="average";
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:dewarpingEnable"]=0;
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:returnMode"]="singleReturnStrongest";
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:samplingRate"]=240000;
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:scanningMode"]="nonRepetitive";
			$KmzData["Document"]["Folder"]["wpml:payloadParam"]["wpml:modelColoringEnable"]=0;
		} else {
			//echo "delete wpml:payloadParam";
			//pre_r($KmzData["Document"]["Folder"]["wpml:payloadParam"]);
			unset($KmzData["Document"]["Folder"]["wpml:payloadParam"]);
		}
		//pre_r($KmzData);
	}	

	Function CreateKMZforNewDrone($Confile, $SourceDrone, $TargetDrone) {
		global 	$RKRKRelWebLocation,
				$RegionSettings,
				$MissionDroneTypes,
				$tempfilelocation,
				$RKRKLocalLoactionMissionFiles,
				$KmzFileName,
				$SubDirKMZ;
		
		//Changes KMZ Content for New Drone
		//echo "TargetDrone: " . $TargetDrone . "<br>";
		if (($SourceDrone!="M2") and ($TargetDrone!="M2") and array_key_exists($TargetDrone,$MissionDroneTypes)) {
			//echo "Convert File: " .$RKRKLocalLoactionMissionFiles.$Confile . " to ". $tempfilelocation . "<br>";
			UnzipAndStoreFile($RKRKLocalLoactionMissionFiles.$Confile,$tempfilelocation);
			$CoreFilename = pathinfo($Confile, PATHINFO_FILENAME);
			$ZipDirectory = $tempfilelocation.$CoreFilename."/".$SubDirKMZ;
			$KmzFile = $ZipDirectory.$KmzFileName;
			//echo "Zip Directory: ". $KmzFile . "<br>";

			// EXTRACT KMZ data to Array
			// -------------------------
			$KMZData = GetArrayFromKMZ($KmzFile);
			//pre_r($KMZData);
			
			ChangeDroneTypeAndHeightInKMZData($SourceDrone,$TargetDrone,$KMZData);

			// CREATE KML data from array
			// --------------------------
			$XmlFileContent = arrayToXml($KMZData);
			//var_dump($XmlFileContent);
			
			//echo "TempTargetZipFile: ". $TargetDirectory . $KmzFileName . "<br>";
			
			// CREATE DIRECTORY
			// ----------------
			$NewHeight = GetFlightHeightForAltDrone($SourceDrone, $TargetDrone, GetMeaningFilename($Confile)[4], "IRCamAngle_H", 5);
			$TargetZipFile = ChangeHeightInFilename($Confile, $NewHeight, $TargetDrone, false);
			$TargetDirectory = $tempfilelocation.$TargetZipFile."/".$SubDirKMZ;
			$OutputKmzFile = $TargetDirectory.$KmzFileName;
			//echo "TargetDirectory " . $TargetDirectory . " OutputKmz: " . $OutputKmzFile . "<br>";
			
			CreateDirectoryAndSaveFile($TargetDirectory, $OutputKmzFile, $XmlFileContent);

			//only for debugging
			//CreateZipFile($tempfilelocation, $TargetZipFile, $OutputKmzFile, "zip");
			$KMZFileLocation =  CreateZipFile($tempfilelocation, $TargetZipFile, $OutputKmzFile, "kmz");

			//$TargetZipLocation = $RKRKRelWebLocation.$MissionDroneTypes[$TargetDrone]["MissionLocation"].$RegionSettings[multiexplode(array("_", "-"),pathinfo($ConFile, PATHINFO_FILENAME))[0]]["Dir"]."/".$TargetZipFile
			
			
			$TargetKMZLocation = $RKRKRelWebLocation.$MissionDroneTypes[$TargetDrone]["MissionLocation"] . $RegionSettings[multiexplode(array("_", "-"),$CoreFilename)[0]]["Dir"] . "/" . $TargetZipFile.".kmz";
			//echo "Copy ZIP from " . $KMZFileLocation . " to " . $TargetKMZLocation  . "<br>";
			copy($KMZFileLocation, $TargetKMZLocation);
			usleep(5000);
		}
		return $TargetZipFile.".kmz";
	};



function compileKmzData($PointData) {
	global 	$KMZTemplate,
			$DroneSettings,
			$DJI_DroneInformation;
	
	$KmzData = Array();
	
	//pre_r($PointData);
	//PickDrone
	foreach ($DroneSettings as $key => $data) {
		//echo "Drone: " . $key . "/" . $data["UAVDroneType"] . "/" . $PointData["Profile"]["droneType"] . "<br>"; 
		if ($data["UAVDroneType"]==$PointData["Profile"]["droneType"]) {
			$DroneKey = $key;
			break;
		}	
	}
	if (!isset($DroneKey)) {
		foreach ($DroneSettings as $key => $data) {
			if (is_array($data) && !empty($data["editor"]) && $data["editor"] === true) {
				$DroneKey = $key;
				break;
			}
		}
	}
	if (!isset($DroneKey)) {
		$DroneKey = "3T";
	}
	//echo "DroneKey: " . $DroneKey . "<br>"; 

	//Add header
	$KmzData =$KMZTemplate["header"];
	$KmzData["wpml:author"] = "RKRK";
	$KmzData["wpml:createTime"] = time();
	$KmzData["wpml:updateTime"] = time();
	$KmzData["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneEnumValue"] = $DJI_DroneInformation["wpml:droneEnumValue"][$DroneKey];
	$KmzData["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneSubEnumValue"] = $DJI_DroneInformation["wpml:droneSubEnumValue"][$DroneKey];

	$KmzData["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadEnumValue"] = $DJI_DroneInformation["wpml:payloadEnumValue"][$DroneKey];
	$KmzData["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadSubEnumValue"] = $DJI_DroneInformation["wpml:payloadSubEnumValue"][$DroneKey];
	$KmzData["wpml:missionConfig"]["wpml:payloadInfo"]["wpml:payloadPositionIndex"] = $DJI_DroneInformation["wpml:payloadPositionIndex"][$DroneKey];
	
	//Add mission
	$KmzData =array_merge($KmzData,$KMZTemplate["mission"]);
	$autoSpeed = isset($PointData["speed"]["height"]) ? floatval($PointData["speed"]["height"]) : 5.0;
	if (!is_finite($autoSpeed) || $autoSpeed <= 0.0) {
		$autoSpeed = 5.0;
	}
	$KmzData["wpml:missionConfig"]["wpml:globalTransitionalSpeed"] = $autoSpeed;
    $KmzData["Folder"]["wpml:globalHeight"] = $PointData["missionSettings"]["height"];
    $KmzData["Folder"]["wpml:takeOffSecurityHeight"] = $PointData["missionSettings"]["takeOffSecurityHeight"];
	$KmzData["Folder"]["wpml:autoFlightSpeed"] = $autoSpeed;
	$KmzData["Folder"]["wpml:waylineCoordinateSysParam"]["wpml:heightMode"] = $PointData["missionSettings"]["flightHeightMode"];
	if (isset($PointData["missionSettings"]["globalWaypointHeadingMode"])) {
		$KmzData["Folder"]["wpml:globalWaypointHeadingParam"]["wpml:waypointHeadingMode"] = $PointData["missionSettings"]["globalWaypointHeadingMode"];
	}
	if (isset($PointData["missionSettings"]["globalWaypointTurnMode"])) {
		$KmzData["Folder"]["wpml:globalWaypointTurnMode"] = $PointData["missionSettings"]["globalWaypointTurnMode"];
	}
	if (isset($PointData["missionSettings"]["globalUseStraightLine"])) {
		$KmzData["Folder"]["wpml:globalUseStraightLine"] = $PointData["missionSettings"]["globalUseStraightLine"];
	}

	$routeFollow = !array_key_exists('route_follow_mission', $PointData)
		? true
		: (bool)$PointData['route_follow_mission'];
	$turnMode = isset($PointData["missionSettings"]["placemarkTurnMode"]) ? (string)$PointData["missionSettings"]["placemarkTurnMode"] : "coordinateTurn";
	$useStraight = isset($PointData["missionSettings"]["placemarkUseStraightLine"]) ? intval($PointData["missionSettings"]["placemarkUseStraightLine"]) : 1;
	$turnDampBase = isset($PointData["missionSettings"]["turnDampingM"]) ? floatval($PointData["missionSettings"]["turnDampingM"]) : 0.0;
	if (!is_finite($turnDampBase) || $turnDampBase < 0.0) {
		$turnDampBase = 0.0;
	}
	$headingChildrenDefault = (isset($PointData["missionSettings"]["perWaypointHeadingParamChildren"]) && is_array($PointData["missionSettings"]["perWaypointHeadingParamChildren"]))
		? $PointData["missionSettings"]["perWaypointHeadingParamChildren"]
		: null;
	
	//Add waypoint
	$i=0;
	foreach ($PointData["Wegpunkte"] as $key => $data) {
		if ($i==0) {
			$BaseHeight = floatval($data["value"]);
		}
		$PointKey = "Placemark-". sprintf("%03d", $i);
		$RelHeight = round(floatval($data["value"]) - $BaseHeight + floatval($data["height"]),0); // compared to 1st point
		$KmzData["Folder"][$PointKey]=$KMZTemplate["waypoint"]["Folder"]["Placemark"];
		$KmzData["Folder"][$PointKey]["Point"]["coordinates"] = $data["lng"].",".$data["lat"];
		$KmzData["Folder"][$PointKey]["wpml:index"] = $i;
		$KmzData["Folder"][$PointKey]["wpml:ellipsoidHeight"] = $RelHeight;
		$KmzData["Folder"][$PointKey]["wpml:height"] = $RelHeight;
		$ptTurn = isset($data['wpml_turn_mode']) ? (string)$data['wpml_turn_mode'] : $turnMode;
		$ptStraight = array_key_exists('wpml_use_straight_line', $data) ? intval($data['wpml_use_straight_line']) : $useStraight;
		$dampingDist = ($i === 0) ? 0 : $turnDampBase;
		$KmzData["Folder"][$PointKey]["waypointTurnParam"]["wpml:waypointTurnDampingDist"] = $dampingDist;
		$KmzData["Folder"][$PointKey]["waypointTurnParam"]["wpml:waypointTurnMode"] = $ptTurn;
		$KmzData["Folder"][$PointKey]["wpml:useStraightLine"] = $ptStraight;
		$useGlobalHeading = array_key_exists('wpml_use_global_heading', $data)
			? (intval($data['wpml_use_global_heading']) !== 0)
			: ($routeFollow || ($i === 0));
		$useGlobalSpeed = array_key_exists('wpml_use_global_speed', $data)
			? (intval($data['wpml_use_global_speed']) !== 0)
			: $useGlobalHeading;
		$ptSpeed = isset($data['wpml_waypoint_speed']) ? floatval($data['wpml_waypoint_speed']) : $autoSpeed;
		if (!is_finite($ptSpeed) || $ptSpeed <= 0.0) {
			$ptSpeed = $autoSpeed;
		}
		$ptHeadingChildren = (isset($data['wpml_heading_children']) && is_array($data['wpml_heading_children']))
			? $data['wpml_heading_children']
			: $headingChildrenDefault;
		if ($useGlobalHeading) {
			$KmzData["Folder"][$PointKey]["wpml:useGlobalHeadingParam"] = 1;
			if (isset($KmzData["Folder"][$PointKey]["wpml:waypointHeadingParam"])) {
				unset($KmzData["Folder"][$PointKey]["wpml:waypointHeadingParam"]);
			}
		} else {
			$KmzData["Folder"][$PointKey]["wpml:useGlobalHeadingParam"] = 0;
			if (is_array($ptHeadingChildren)) {
				$KmzData["Folder"][$PointKey]["wpml:waypointHeadingParam"] = $ptHeadingChildren;
			}
		}
		if ($useGlobalSpeed) {
			$KmzData["Folder"][$PointKey]["wpml:useGlobalSpeed"] = 1;
			if (isset($KmzData["Folder"][$PointKey]["wpml:waypointSpeed"])) {
				unset($KmzData["Folder"][$PointKey]["wpml:waypointSpeed"]);
			}
		} else {
			$KmzData["Folder"][$PointKey]["wpml:useGlobalSpeed"] = 0;
			$KmzData["Folder"][$PointKey]["wpml:waypointSpeed"] = $ptSpeed;
		}
		$i++;
	}	
	
	return ($KmzData);	
}	

?>