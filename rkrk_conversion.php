<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >

<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_jsonfiles.php');
	include_once('scripts/rkrk_kmz.php');
	include_once('scripts/rkrk_dji_kmz_interface.php');
	ini_set('serialize_precision', 16);
	
	$PARAM="";
	$MaxHeight= 120;
	
	//echo "Host: ". $ThisHost . "<br>";
	if ((strpos($ThisHost, "rehkitzrettung.ch") !== false)  or (strpos($ThisPage, "/rkrs/convert"))) {
		$onRKRS = true;
		$Layout= "2023";
		//echo "onRKRS<br>";
		$copyright = "rehkitzrettung.ch";
		$AdvancedMode= false;		
	} else {
		$onRKRS = false;
		//echo "NOTonRKRS<br>";
		$copyright = "drohnenrehkitzrettung.ch";
		$AdvancedMode= false;		
	} 


	if (isset($_GET["ADVANCE"])) {
		$AdvancedMode=true;
		$PARAM="ADVANCE&";
	}	

	if (isset($_GET["TEST"])) {
		$PARAM.="TEST&";
	}

	if (isset($_POST["drone"])) {
		$ToDrone = $_POST["drone"];
	} elseif (isset($_GET["drone"])) {
		$ToDrone = $_GET["drone"];
	} else	{
		$ToDrone = "";
	}	
	//echo "drone: " . $ToDrone . "<br>";

	if (isset($_POST["fromdrone"])) {
		$FromDrone = $_POST["fromdrone"];
	} elseif (isset($_GET["fromdrone"])) {
		$FromDrone = $_GET["fromdrone"];
	} else	{
		$FromDrone = "";
	}	
	//echo "drone: " . $ToDrone . "<br>";

	//Select Options
	$Drones  = Array();
	$DroneData = Array();
	foreach ($DroneSettings as $key => $Drone) {
		if ($Drone["conversion"]==true) {
			$Drones[]=$key;
			$DroneData[$key]["Name"]=$Drone["Name"];
			$DroneData[$key]["DFOV"]=$Drone["DFOV"];
			$DroneData[$key]["key"]=$key;
			$DroneData[$key]["droneEnumValue"]=$DJI_DroneInformation["wpml:droneEnumValue"][$key];
		}	
	}

	$FromDrones =Array();
	$FromDroneData = Array();
	$FromDrones[]="";
	foreach ($DroneSettings as $key => $Drone) {
		if ($Drone["external"]==true) {
			$FromDrones[]=$key;
			$FromDroneData[$key]["Name"]=$Drone["Name"];
			$FromDroneData[$key]["DFOV"]=$Drone["DFOV"];
			$FromDroneData[$key]["IRCamAngle_H"]=$Drone["IRCamAngle_H"];
			$FromDroneData[$key]["key"]=$key;
			if (array_key_exists($key,$DJI_DroneInformation["wpml:droneEnumValue"])) {
				$FromDroneData[$key]["droneEnumValue"]=$DJI_DroneInformation["wpml:droneEnumValue"][$key];
			}	
		}	
	}

	//pre_r($DroneData);
	//pre_r($FromDroneData);
	// ===========================================
	// Definitions
	// ===========================================
	//$TestFile = "21_Aeugst/21-001_Boosch_2152_50_CH_24.kmz";
	$SubDirKMZ = "wpmz/";
	$KmzFileName = "template.kml";
	if ($onRKRS) {
		$TempOpenDirectory = "tmp/";
	} else {
		$TempOpenDirectory = "../tmp/conversion/";
	}
	
	if (!file_exists($TempOpenDirectory)) {
		mkdir($TempOpenDirectory, 0777, true);
	}
	
	$CalcIconLoc = $ImageLoc ."calc/";
	
	$FileSetting = Array (
							"JSON" => array(	
												"Type" => "JSON",
												"Text" => "JSON Wegpunktprogramm aus dem RKRS UAV Editor",
												"allowed" => array('json'),
												"allowedList" => "application/json",
												"KatField" => "jpg",
												),
							"KMZ" => array(	
												"Type" => "KMZ",
												"Text" => "KMZ Wegpunktprogramm für DJI Pilot 2 Drohnen",
												"allowed" => array('kmz'),
												"allowedList" => ".kmz",
												"KatField" => "kmz",
												),
	);

	// ===========================================
	// Functions
	// ===========================================

function ConvertToNewPlatform($Directory, $Filename, $ToDrone, $FromDrone="") {
	global		$IconLoc,
				$ButtonHeight,
				$SubDirKMZ,
				$KmzFileName,
				$DroneSettings,
				$DJI_DroneInformation,
				$testmode,
				$AdvancedMode,
				$MaxHeight;
				
	$JsonTemplateName= "3T";
	$ConversionError = "";
	$FromDroneName = "";
	
	$extension = pathinfo($Filename)['extension'];
	
	switch ($extension) {
		case "json": 
			//echo "json File: " . $Directory.$Filename . "<br>";
			//echo "ToDrone: " . $ToDrone . "<br>";
			$JsonArray = ReadJsonFile($Directory.$Filename);
			$CopterProfile = $JsonArray["copterProfile"];
			//echo "CopterProfile:" . $CopterProfile . "<br>";
			if (array_key_exists("copterProfile", $JsonArray)) {
				if ($CopterProfile<>"dji-pilot-2") {
					if ($AdvancedMode) {
						$ReadJsonTemplate = ReadJsonTemplate($JsonTemplateName);
						//pre_r($ReadJsonTemplate);
						$JsonTemplateStructure = SplitJSONToStructure($ReadJsonTemplate, $ConversionError);
						//pre_r($JsonTemplateStructure);
						$JsonStructured = SplitJSONToStructure($JsonArray, $ConversionError);
						$FileName = $JsonStructured["BaseInformation"]["Filename"];
						//pre_r($JsonArray["geojson"]["features"]);
						//pre_r($JsonStructured["Profile"]);
						//pre_r($JsonStructured);
						//$CopterProfile = $JsonStructured["Prof//ile"]["copterProfile"];
						$DroneType = $JsonStructured["Profile"]["droneType"];
						//echo "DroneType in File :" . $DroneType . "<br>";
						//pre_r($JsonStructured["Punkte"]);
						//pre_r($JsonStructure["Punkte"]["StartPunkt"]);
						
						$JsonArray = CreateNewJSON($JsonStructured, $JsonTemplateStructure);
						//pre_r($JsonArray);
						//Drohnentyp ermitteln
						//echo "From Drone: " . $FromDrone . "<br>";
						switch ($CopterProfile) {
							case "dji-pilot":
								
								$JsonArray["missionSettings"]["djiPilot2"]= $JsonTemplateStructure["missionSettings"]["djiPilot2"];
								//pre_r($JsonArray["missionSettings"]["djiPilot2"]);
								if (array_key_exists("speed", $JsonArray["missionSettings"]["djiPilot"])) {
									$JsonStructured["BaseInformation"]["speed"]=$JsonArray["missionSettings"]["djiPilot"]["speed"];
									$JsonArray["missionSettings"]["djiPilot2"]["speed"]=$JsonArray["missionSettings"]["djiPilot"]["speed"];
								//	echo "set mission speed from ".$JsonArray["missionSettings"]["djiPilot"]["speed"]." km/h to " . $JsonStructured["BaseInformation"]["speed"]. "km/h<br>";
									}
								//get mission height from dji pilot 1 mission height
								if (array_key_exists("height", $JsonArray["missionSettings"]["djiPilot"])) {
									$JsonArray["missionSettings"]["djiPilot2"]["height"]=$JsonArray["missionSettings"]["djiPilot"]["height"];
								}
								//pre_R($JsonData["missionSettings"]["djiPilot"]);
								break;
							case "dji":
								$JsonArray["missionSettings"]["djiPilot2"]= $JsonTemplateStructure["missionSettings"]["djiPilot2"];
								//pre_r($JsonArray["geojson"]["features"]);
								//get height from 1st waypoint as mission height
								if (array_key_exists("travelSpeed", $JsonArray["missionSettings"]["dji"])) {
									$JsonStructured["BaseInformation"]["speed"]=round($JsonArray["missionSettings"]["dji"]["travelSpeed"]/3.6,1);
									$JsonArray["missionSettings"]["djiPilot2"]["speed"]=round($JsonArray["missionSettings"]["dji"]["travelSpeed"]/3.6,1);
								//	echo "set mission speed from ".$JsonArray["missionSettings"]["dji"]["travelSpeed"]." m/s to " . $JsonStructured["BaseInformation"]["speed"]. "km/h<br>";
								}
								if (array_key_exists("height", $JsonArray["geojson"]["features"][2]["properties"] )) {
									//pre_R($JsonData["geojson"]["features"][2]["properties"]);
									$JsonArray["missionSettings"]["djiPilot2"]["height"]=$JsonArray["geojson"]["features"][2]["properties"]["height"];
									$JsonStructured["BaseInformation"]["MissionHeight"]=$JsonArray["geojson"]["features"][2]["properties"]["height"];
								} elseif(array_key_exists("height", $JsonArray["geojson"]["features"][3]["properties"])) {
									//pre_R($JsonData["geojson"]["features"][3]["properties"]);
									$JsonArray["missionSettings"]["djiPilot2"]["height"]=$JsonArray["geojson"]["features"][3]["properties"]["height"];
									$JsonStructured["BaseInformation"]["MissionHeight"]=$JsonArray["geojson"]["features"][3]["properties"]["height"];
								} elseif(array_key_exists("height", $JsonArray["geojson"]["features"][4]["properties"])) {
									//pre_R($JsonData["geojson"]["features"][4]["properties"]);
									$JsonArray["missionSettings"]["djiPilot2"]["height"]=$JsonArray["geojson"]["features"][4]["properties"]["height"];
									$JsonStructured["BaseInformation"]["MissionHeight"]=$JsonArray["geojson"]["features"][4]["properties"]["height"];
								}
								//echo "Baseheight just stored: " . $JsonStructured["BaseInformation"]["MissionHeight"] . "<br>";
								break;
						}
						
/*
						//update point template
						foreach ($JsonArray["geojson"]["features"] as $key => $entry) {
							switch($entry["properties"]["type"]) {
								case 1:
									//waypoint
									//pre_r( $entry);
									//pre_r($JsonArray["geojson"]["features"]);
									//unset($JsonArray["geojson"]["features"][$key]);
									//$JsonArray["geojson"]["features"] = $JsonTemplateStructure["Template"]["Wegpunkte"];
								break;
								//case 6:
									//startpunkt
									
								//break;
								//case 9:
									//parkplatz
									
								//break;
							}
						}
*/
						if (in_array($DroneType, array("other","matrice300","matrice350"))) {
							$FromDroneName=$FromDrone;
						} else {
							foreach ($DroneSettings as $key => $drone) {
								//echo "Dronetype: " . $FromDrone . " - " . $drone["UAVDroneType"] . "<br>";
								if ($DroneType==$drone["UAVDroneType"]) {
									$FromDroneName = $key;
									//echo "Von Drohne: " . $FromDroneName . "<br>";
									break;
								}
							}
						}
						//echo "Drohnentyp: " . $DroneType . "<br>";

						// Dronetype in JSON decisive, else need to get the input from Outside

						$BaseHeight = $JsonStructured["BaseInformation"]["MissionHeight"];
						$NewHeight = GetFlightHeightForAltDrone($FromDroneName, $ToDrone, $BaseHeight, "IRCamAngle_H", 5);
						//pre_r($JsonArray);
					} else {
						//stop Conversion
						$ConversionError .= "<br><b>!!! </b><i>Der Drohnensteuerung konnte nicht als Pilot 2 erkannt werden. Es können nur Pilot 2 JSON Dateien konvertiert werden. Stelle sicher dass der UAV Edtior den Drohnentyp darstellt, ev. musst du die Datei nochmals neu exportieren.</i>";
					}
				} else {
					$BaseHeight= $JsonArray["missionSettings"]["djiPilot2"]["height"];
					$FromDrone= $JsonArray["missionSettings"]["djiPilot2"]["droneType"];

					//Find drone type
					$FromDroneName="";
					foreach ($DroneSettings  as $key => $drone) {
						//echo "Dronetype: " . $FromDrone . " - " . $drone["UAVDroneType"] . "<br>";
						if ($FromDrone== $drone["UAVDroneType"]) { 
							$FromDroneName = $key;
							break;
						}	
					}
				}	
			} else {
				$ConversionError .= "<br><b>!!! </b><i>Der Drohnensteuerung konnte nicht erkannt werden. Es können nur JSON Dateien umgerechnet werden, deren Drohnensteurung defininiert ist. Stelle sicher dass der UAV Edtior den Drohnentyp darstellt, ev. musst du die Datei nochmals neu exportieren.</i>";
			}	
			//echo "From Drone/Camera: " . $FromDroneName . " to Drone/Camera: " .  $ToDrone. " ConfError: " . $ConversionError . "<br>";
			//echo "From " . $FromDrone . " / " . $BaseHeight . "m: " . $key . " - " . $dronedata["UAVDroneType"] . " Dronename: " . $FromDroneName. "<br>";
			if (($FromDroneName) and ($ConversionError=="")) {
				$NewHeight = GetFlightHeightForAltDrone($FromDroneName, $ToDrone, $BaseHeight, "IRCamAngle_H", 5);
				//echo "From Drone: " . $FromDroneName . " / " . $BaseHeight . "m " . " to Drone " . $ToDrone . " NH: ". $NewHeight . "<br>";
				if ($NewHeight>0) {
					$ToFilename = ChangeHeightInFilename($Filename, $NewHeight, $ToDrone, false);
					//if ($CopterProfile=="dji-pilot-2")  {
						//echo "ToFilename " . $ToFilename . "<br>";
						ChangeJSONforNewDrone($JsonArray,$ToDrone,$BaseHeight,$NewHeight,$Filename,$ToFilename);
					//} 
					//pre_r($JsonArray);
					
					// Speichere array
					// --------------------------
					//echo "Write Json To: " . $Directory.$ToFilename . "<br>";
					WriteJsonFile ($Directory.$ToFilename.".json", $JsonArray);
					$JsonFileLocation = $Directory.$ToFilename.".json";
					
					if (file_exists($IconLoc . '/json'.$NewHeight.'.png')) {
						$icon= $IconLoc . '/json'.$NewHeight.'.png';
					} else {
						$icon= $IconLoc . '/jsonxx.png';
					}
					echo '<a href="' . $JsonFileLocation. '" download >';
					echo '<img src="' . $icon . '" height="' . $ButtonHeight . '"  title="Konvertierte Datei ' . basename($JsonFileLocation) . ' herunterladen.">';
					echo '</a> ';
					echo '<a href="' . $JsonFileLocation. '" download >' . basename($JsonFileLocation).' </a>' .chr(10);
					echo '<br><i>Die Datei basierend auf '. $FromDroneName .' für ' . $ToDrone . ' konvertiert, dabei wurde die Flughöhe von ' . number_format($BaseHeight,0)  . 'm auf ' . $NewHeight . 'm angepasst.</i><br>' . chr(10);
					if ($NewHeight>$MaxHeight) {
						echo "<b>!!! </b>Die neue Flughöhe höher als die maximale Flughöhe von ".$MaxHeight."m. Bitte prüfe das Flugprogramm.<br>" . chr(10);
					}
					if ($NewHeight<$BaseHeight) {
						echo "<b>!!! </b>Die neue Flughöhe ist tiefer. Bitte prüfe betreffend Objekten im Flugweg.<br>" . chr(10);
					}
					echo "<br><br>" . chr(10);
				} else {
					echo "<i><b>!!! </b> Deine JSON Datei kann nicht konvertiert werden.</i>" . chr(10);
				}
			} else {
				echo "<b>!!! </b>" . $Filename. ": Datei konnte nicht bearbeitet werden. " . $ConversionError  . "<br><br>" . chr(10);
			}
			break;

		case "kmz": 
			UnzipAndStoreFile($Directory.$Filename,$Directory);
			$ZipDirectory = $Directory.pathinfo($Filename, PATHINFO_FILENAME)."/".$SubDirKMZ;
			$KmzFile = $ZipDirectory.$KmzFileName;

			// EXTRACT KMZ data to Array
			// -------------------------
			//echo "KMZ File: " . $Directory.$Filename . "<br>";
			$KMZData = GetArrayFromKMZ($KmzFile);
			//pre_r($KMZData);
			$BaseHeight= $KMZData["Document"]["Folder"]["wpml:globalHeight"];
			$FromDrone = $KMZData["Document"]["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneEnumValue"];
			$FromPayload = $KMZData["Document"]["wpml:missionConfig"]["wpml:droneInfo"]["wpml:droneSubEnumValue"];
			//Find drone type
			$FromDroneName="";
			// 05.01.2025 TCHR Im UAV Editor wurde für die M4T der wpml:droneEnumValue fälschlicherweise auf 150 statt 99 gesetzt. 
			// Temporäre Fehlerbehandlung:
			//echo "From Drone: " . $FromDrone . "<br>";
			if ($FromDrone==150) 
				$FromDrone=99;
			// Ende Temporäre Fehlerbehandlung
			foreach ($DJI_DroneInformation["wpml:droneEnumValue"] as $key => $value) {
				if (array_key_exists($key, $DJI_DroneInformation["wpml:droneSubEnumValue"])) {
					if (($FromDrone== $value) and ($FromPayload== $DJI_DroneInformation["wpml:droneSubEnumValue"][$key])) {
						$FromDroneName = $key;
						//echo "DroneKey: ". $key . " Value: " . $value . " SubValue: " . $DJI_DroneInformation["wpml:droneSubEnumValue"][$key] . "<br>";
					}	
				}	
			}
			if ($FromDroneName) {
				//echo "Drone found: " . $FromDroneName . "<br>";
				$NewHeight = GetFlightHeightForAltDrone($FromDroneName, $ToDrone, $BaseHeight, "IRCamAngle_H", 5);
				//echo "NewHeight KMZ: " . $NewHeight  . "<br>";
				if ($NewHeight>0) {
					ChangeDroneTypeAndHeightInKMZData($FromDroneName,$ToDrone,$KMZData);

					// CREATE KML data from array
					// --------------------------
					$XmlFileContent = arrayToXml($KMZData);
					//var_dump($XmlFileContent);
					
					// CREATE DIRECTORY
					// ----------------
					$TargetZipFile = ChangeHeightInFilename($Filename, $NewHeight, $ToDrone, false);
					$TargetDirectory = $Directory.$TargetZipFile."/".$SubDirKMZ;
					$OutputKmzFile = $TargetDirectory.$KmzFileName;
					//echo "TargetDirectory " . $TargetDirectory . " OutputKmz: " . $OutputKmzFile . "<br>";

					CreateDirectoryAndSaveFile($TargetDirectory, $OutputKmzFile, $XmlFileContent);
					//CreateZipFile($Directory, $TargetZipFile, $OutputKmzFile, "zip", true);
					$ZipFileLocation =  CreateZipFile($Directory, $TargetZipFile, $OutputKmzFile, "kmz", false);
					
					if (file_exists($IconLoc . '/kmz'.$NewHeight.'.png')) {
						$icon= $IconLoc . '/kmz'.$NewHeight.'.png';
					} else {
						$icon= $IconLoc . '/kmzxx.png';
					}
					
					echo '<a href="' . $ZipFileLocation. '" download >';
					echo '<img src="' . $icon. '" height="' . $ButtonHeight . '"  title="Konvertierte Datei ' . basename($ZipFileLocation) . ' herunterladen.">';
					echo '</a> ';
					echo '<a href="' . $ZipFileLocation. '" download >' . basename($ZipFileLocation).' </a>' . chr(10);
					if (($testmode) or false)
						echo ' - <a href="' . $Directory.$TargetZipFile."/".$SubDirKMZ.'/template.kml' . '" download >' . 'KML' . ' </a>' . chr(10);		
					echo '<br><i>Die Datei basierend auf '. $FromDroneName .' für ' . $ToDrone . ' konvertiert, dabei wurde die Flughöhe von ' . number_format($BaseHeight,0) . 'm auf ' . $NewHeight . 'm angepasst.</i><br>' . chr(10);
					if ($NewHeight<$BaseHeight) {
						echo "<b>!!! </b>Die neue Flughöhe ist tiefer, bitte prüfe betreffend Objekten im Flugweg.<br>" . chr(10);
					}
					echo "<br><br>" . chr(10);
				} else {
					echo "<b>!!! </b>" . $Filename . ": Diese KMZ Datei kann nicht konvertiert werden.<br><br>" . chr(10);
				}
			} else {
				//echo "Datei konnte nicht bearbeitet werden." . chr(10);
			} 
			break;

	}		
}	

	// ===========================================
	// Test
	// ===========================================

	//$FromDrone = "3T";
	//$ToDrone = "4TD";
	$FileDestination = $TempOpenDirectory;

	DeleteFilesOlderThan($TempOpenDirectory, "*.*", 2);	
	
	///////////////////////////////
	// Page Form
	//////////////////////////////

	headerpage("Umrechnung eines Wegpunktprogrammes", false);

	if (isset($_GET["TEST"])) {
		echo "<body>";
		$testmode = true;
		} else	{
		//echo "<body>";
		echo "<body oncontextmenu='return false;'>";
		$testmode = false;
	}	
	
	//echo "<div align='center' style='font-size:25;background-color: #FDEDEC;'>8-TUNG: Seite im Test Status<br><br>Rückmeldungen zur Verbesserung der Funktion sind erwünscht.</div>".chr(10);
	echo "<H2>Umrechnung eines Wegpunktprogrammes</H2>". chr(10);
	echo "<table width=100%>" . chr(10);
	echo "<tr><td>". chr(10);
	echo "<p>Umrechnung eines Wegpunktprogramms auf einen anderen Drohnentyp". chr(10);
	if ($onRKRS) {
		if (!$AdvancedMode) {
			echo " (z. B. von der Mavic 3T auf die Matrice 4T)". chr(10);
		} else {
			echo " (z. B. von der Mavic 2EA auf die Matrice 4T)". chr(10);
		}
	} else {
		echo " (z. B. von der Mavic 3T auf die Matrice 4T)". chr(10);
	}	
	echo ".</p>". chr(10);
	echo "<p>Bei der Übertragung eines Wegpunktflugplans auf einen anderen Drohnentyp bleiben zentrale Flugparameter wie Geschwindigkeit, Abstände und Flugrichtung unverändert. Lediglich die Flughöhe wird angepasst, um eine identische Suchbreite am Boden sicherzustellen.</p>". chr(10);

	echo "<p>Diese Seite wandelt entsprechende JSON-Dateien aus dem <a href='https://rkrs.uaveditor.com/' target='rkrsuav'>UAV Editor</a>";
	if ($onRKRS) {
		if (!$AdvancedMode) {
			echo " (DJI Pilot 2) oder KMZ-Dateien (DJI Pilot 2)". chr(10);
		} else {
			echo " (DJI Pilot 1, Litchi)". chr(10);
		}
	} else {
		echo " (DJI Pilot 1, DJI Pilot 2, Litchi) oder KMZ-Dateien (DJI Pilot 2)";
	}	

	echo " automatisch für den Einsatz mit einem anderen DJI-Drohnentyp um.". chr(10);
	if (($AdvancedMode) and ($onRKRS)) {
		echo " Bei der Umwandlung von DJI Pilot1 oder Lichti Wegpunktprogrammen auf DJI Pilot 2 wird ein Kurvenflug mit 5m Wendedistanz programmiert.". chr(10);
	}
	echo "</p>". chr(10);

	if (($AdvancedMode) and ($onRKRS)) {
		echo "<p><i>Beispiel:</i></p>". chr(10);
		echo "<p><i>Die Mavic 2EA besitzt einen horizontalen Objektivwinkel an der Wärmebildkamera von 48°, die Matrice 4T hingegen 36°. Wurde ein Flugplan für die Mavic 2EA mit einer Flughöhe von 60 m erstellt, so muss die Matrice 4T auf etwa 75 m steigen, um dieselbe Suchbreite von rund 32 m am Boden zu erzielen. Ist diese Anpassung vorgenommen, kann das modifizierte Wegpunktprogramm problemlos mit der Matrice 4T geflogen werden.</i></p>". chr(10);
	} else {
		echo "<p><i>Beispiel:</i></p>". chr(10);
		echo "<p><i>Die Mavic 3T besitzt einen horizontalen Objektivwinkel an der Wärmebildkamera von 41°, die Matrice 4T hingegen 36°. Wurde ein Flugplan für die Mavic 3T mit einer Flughöhe von 60 m erstellt, so muss die Matrice 4T auf etwa 85 m steigen, um dieselbe Suchbreite von rund 38 m am Boden zu erzielen. Ist diese Anpassung vorgenommen, kann das modifizierte Wegpunktprogramm problemlos mit der Matrice 4T geflogen werden.</i></p>". chr(10);
	}

	echo "<p><b>Beachte: </b>Diese Funktion ist ausschliesslich mit registrierten DJI-Drohnen nutzbar";
	if (!$AdvancedMode or !$onRKRS) {
		echo ", die über DJI Pilot 2 gesteuert werden";
	}
	echo ". Für eine einwandfreie Konvertierung von JSON Dateien muss der Drohnetyp im UAV vor dem Export korrekt angezeigt sein.</p>". chr(10);

	echo "</td><td>". chr(10);
	echo "<figure>" .chr(10);
	echo "<img src='".$CalcIconLoc."bahnabstand.jpg' width=300>". chr(10);
	echo "	<figcaption>Bild 1: Flugbahn und Suchbreite</figcaption>" .chr(10);
	echo "</figure>" .chr(10);
	echo "</td></tr>". chr(10);
	echo "</td></tr></table>". chr(10);
	echo "<table width=100%><tr><td>". chr(10);
	echo "<h3>Zieldrohne:</h3>". chr(10);
	echo "<form name='form' method='POST' action='". htmlspecialchars($_SERVER["PHP_SELF"]) . "' enctype='multipart/form-data'>" . chr(10);
	echo "<span id='dronetxt' name='drone'><i>Wähle deine <i>DJI Pilot2</i> basierte <b>Zieldrohne</b> aus:</i></span>" . chr(10);
	echo SelectJSFunctionField("drone", $ToDrone ,$Drones , "UpdDrone", "this.value", "1" ) . "<br><br>". chr(10);
	echo "<span id='dronelegend'></span>" .chr(10);
	echo "<br><br>";
	echo "<h3>Dateienauswahl:</h3>";
	echo "<p><i>Es können mehrere JSON";
	if (!$AdvancedMode or !$onRKRS) {
		echo " oder KMZ ";
	}
	echo "Dateien (max. 50 Dateien pro Aufruf) ausgewählt werden. ";
	if (!$AdvancedMode) {
	 echo " Es sind nur Umwandlungen innerhalb DJI Pilot 2 mit integrierten Infrarot Kameras möglich.";
	} 
	echo "</i></p>";
	echo "  <i><label for='". $FileSetting["JSON"]["Type"] ."'>UAV JSON Datei auswählen</label></i>" . chr(10);
	echo "	<input type='file' id='" . $FileSetting["JSON"]["Type"] . "' name='" . $FileSetting["JSON"]["Type"]. "[]' accept='" . $FileSetting["JSON"]["allowedList"] .  "'  multiple >" . chr(10);
	if ($AdvancedMode) {
		echo "<span id='dronetxt' name='fromdrone'><i><br>Für nachbestückte IR-Kameras, braucht es die genaue <br>Bezeichnung (z.B. bei Mavic 2, Matrice 300):</i></span>" . chr(10);
		echo SelectJSFunctionField("fromdrone", $FromDrone ,$FromDrones , "UpdFromDrone", "this.value", "1" ) . chr(10);
	}
	if (!$AdvancedMode or !$onRKRS) {
		echo "<br><br>";
		echo "  <i><label for='". $FileSetting["KMZ"]["Type"] ."'>DJI KMZ Datei auswählen</label></i>" . chr(10);
		echo "	<input type='file' id='" . $FileSetting["KMZ"]["Type"] ."' name='" . $FileSetting["KMZ"]["Type"] ."[]' accept='" . $FileSetting["KMZ"]["allowedList"] .  "'   multiple >" . chr(10);
	}
	echo "<br><br>";
	//echo "<div align='center' style='font-size:25;background-color: #FEF9E7;'>Beachte: Im Augenblick hat es im UAV-Editor noch einen Fehler beim Importieren von JSON Daten. Für eine Korrekte Funktion, muss dieser Fehler zuerst behoben werden. Wir werden dies hier entsprechend vermerken. Die KMZ Funktion kann auf der Fernbedienung bereits angewendet werden.</div>".chr(10);
	echo '<input type="submit" name="Umwandeln" value="Umwandeln" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'.$PARAM.'">' .chr(10);
	echo '</form>';
	echo "</td><td width=500>". chr(10);
	if ($AdvancedMode) {
		echo "<figure>" .chr(10);
		echo "  <img src='".$IconLoc. "/" . "flughoehenanpassung_advanced.png' width=500>" .chr(10);
		echo "	<figcaption>Bild 2: Flughöhenanpassung - Kurvenflug wird eingefügt</figcaption>" .chr(10);
		echo "</figure>" .chr(10);
	} else {
		echo "<figure>" .chr(10);
		echo "  <img src='".$IconLoc. "/" . "flughoehenanpassung.png' width=500>" .chr(10);
		echo "	<figcaption>Bild 2: Flughöhenanpassung - alle andere Flugparameter bleiben gleich</figcaption>" .chr(10);
		echo "</figure>" .chr(10);
	}
	echo "</td></tr></table>". chr(10);

	echo "<br><br>";
	echo "<br><br>";

	///////////////////////////////
	// Conversion Execution
	//////////////////////////////
	//pre_R($_FILES[$FileSetting["JSON"]["Type"]]['name']);
	
	IF (isset($_FILES[$FileSetting["JSON"]["Type"]]['name'])) {
		$total = count($_FILES[$FileSetting["JSON"]["Type"]]['name']);
		if (($_FILES[$FileSetting["JSON"]["Type"]]['name'][0] <> "") and ($ToDrone<>"")) {
			echo "<h3>Konvertierte JSON Dateien </h2>";
			echo "Link zum <i><a href='https://rkrs.uaveditor.com/' target='rkrsuav'>UAV Editor</a></i>";
			echo "<ul>";
			// Loop through each file
			for( $i=0 ; $i < $total ; $i++ ) {
				$FileName = $_FILES[$FileSetting["JSON"]["Type"]]['name'][$i];
				$FileTmpName = $_FILES[$FileSetting["JSON"]["Type"]]['tmp_name'][$i];
				//echo "<br>". $FileSize = $_FILES[$FileSetting["JSON"]["Type"]]['size'][$i];
				//echo "<br>". $FileError = $_FILES[$FileSetting["JSON"]["Type"]]['error'][$i];
				//echo "<br>". $FileType = $_FILES[$FileSetting["JSON"]["Type"]]['type'][$i];
				move_uploaded_file($FileTmpName,$FileDestination.$FileName);
				echo "<li>";
				ConvertToNewPlatform($FileDestination, $FileName, $ToDrone, $FromDrone);
				echo "</li>";
			}
			echo "</ul>";
		}
	}
	
	IF (isset($_FILES[$FileSetting["KMZ"]["Type"]]['name'])) {
		$total = count($_FILES[$FileSetting["KMZ"]["Type"]]['name']);
		//echo $total."<br>";
		if (($_FILES[$FileSetting["KMZ"]["Type"]]['name'][0] <> "") and ($ToDrone<>"")){
			echo "<h3>Konvertierte KMZ Dateien </h2>";
			echo "<ul>";
			// Loop through each file
			for( $i=0 ; $i < $total ; $i++ ) {
				$FileName = $_FILES[$FileSetting["KMZ"]["Type"]]['name'][$i];
				$FileTmpName = $_FILES[$FileSetting["KMZ"]["Type"]]['tmp_name'][$i];
				//echo "<br>". $FileSize = $_FILES[$FileSetting["KMZ"]["Type"]]['size'][$i];
				//echo "<br>". $FileError = $_FILES[$FileSetting["KMZ"]["Type"]]['error'][$i];
				//echo "<br>". $FileType = $_FILES[$FileSetting["KMZ"]["Type"]]['type'][$i];
				move_uploaded_file($FileTmpName,$FileDestination.$FileName);
				echo "<li>";
				ConvertToNewPlatform($FileDestination, $FileName, $ToDrone);
				echo "</li>";
			}
			echo "</ul>";
		}
	}
	
	//pre_r($DJI_DroneInformation);
	//pre_r($DJI_Commands);
	echo "<div align='center'> <i>Beachte: Die Verwendung der generierten Dateien erfolgt auf eigene Verantwortung. Es ist empfohlen die Wegpunktprogramme vor dem Flug zu prüfen. Jede Haftung wird abgelehnt!</i></div><br>" . chr(10);
	echo "<div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Wegpunktprogramm Konvertierung'>Rückmeldungen</i></a></div>" . chr(10);

	echo "</body>";
?>
<script type='text/javascript'>
<?php
//echo json_encode($DroneData);
echo "var DroneData = ". json_encode($DroneData) . ";\n"; 
echo "var Drones = ". json_encode($Drones) . ";\n"; 

echo "var DefaultSettings = ". json_encode($DefaultSettings ?? new stdClass()) . ";\n"; 
echo "var ToDrone = '".$ToDrone."';\n";
?>
var legend="";

function updPageText() {
	legend = legend + "<h4>Unterstützte Drohnentypen</h4>";
	legend = legend + "<ul>";
	Drones.forEach((drone) => {
		legend = legend + "<li><b>"+ drone + "</b>:"+ DroneData[drone]["Name"]+"</li>";
	});
	if (true) {
		document.getElementById("dronelegend").style.display= 'block';
	} else {
		document.getElementById("dronelegend-").style.display= 'none';
	}
	legend = legend + "</ul>";
	document.getElementById("dronelegend").innerHTML = legend;
}
updPageText();

</script>
