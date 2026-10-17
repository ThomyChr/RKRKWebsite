<?php
// ==================
// Funktionen für die Rehktitzrettung
// ==================
	//session_start();
	include_once(__DIR__ . '/rkrk_gen.php');
	include_once(__DIR__ . '/rkrk_csvfiles.php');
	include_once(__DIR__ . '/rkrk_xmlfiles.php');

	//============================================
	// EINLESEN der Files in den Unterordnern
	//============================================
	// scandir()
	function clean_scandir($dir) {
		$files =array();
		if (!is_dir($dir)) {
			return $files;
		}
		$scanned = @scandir($dir);
		if ($scanned === false) {
			return $files;
		}
		$files = array_values(array_diff($scanned, array('.','..','._DAV')));
		return $files;
	};

	// opendir()
	function clean_readdir($dir) {
		$files =array();
		if ($handle = Opendir($dir)) {
			while (false !== ($file=  readdir($handle))) {
				if ($file != '.' && $file != '..' && $file != '._DAV') {
					//echo $file."<br>";
					$files[] =  $file;
				}
			}
		}
		closedir($handle);
		return $files;
	};

/**
 * Skalar aus JSON-Feld (trim, Zeilenumbrüche entfernen).
 */
function rkrk_felddaten_json_scalar_float($v, $default = 0.0) {
	if ($v === null || $v === "") {
		return $default;
	}
	if (is_numeric($v)) {
		return floatval($v);
	}
	$s = trim(str_replace(array("\r", "\n", " "), "", strval($v)));
	if ($s === "" || !is_numeric($s)) {
		return $default;
	}
	return floatval($s);
}

/**
 * JSON flightTime (Sekunden) → Feldkatalog Flugzeit (Minuten, dezimal).
 */
function rkrk_felddaten_json_seconds_to_catalog_min($seconds) {
	return round(rkrk_felddaten_json_scalar_float($seconds, 0.0) / 60.0, 2);
}

/**
 * Feldkatalog Flugzeit (Minuten, dezimal) → JSON flightTime (Sekunden).
 */
function rkrk_felddaten_catalog_min_to_json_seconds($minutes) {
	return rkrk_felddaten_json_scalar_float($minutes, 0.0) * 60.0;
}

/**
 * Haversine-Distanz zweier WGS84-Punkte (Meter).
 */
function rkrk_felddaten_haversine_m($lon1, $lat1, $lon2, $lat2) {
	$R = 6371000.0;
	$toRad = M_PI / 180.0;
	$lat1r = $lat1 * $toRad;
	$lat2r = $lat2 * $toRad;
	$dLat = ($lat2 - $lat1) * $toRad;
	$dLon = ($lon2 - $lon1) * $toRad;
	$a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1r) * cos($lat2r) * sin($dLon / 2) * sin($dLon / 2);
	if ($a < 0.0) {
		$a = 0.0;
	}
	if ($a > 1.0) {
		$a = 1.0;
	}
	$c = 2 * atan2(sqrt($a), sqrt(1.0 - $a));
	return $R * $c;
}

/**
 * Parst $JsonData["Flugweg"] (lon, lat, höhe — Punkte durch mehrere Leerzeichen getrennt).
 */
function rkrk_felddaten_parse_flugweg_waypoints($flugwegStr) {
	$pts = array();
	if ($flugwegStr === null || $flugwegStr === "") {
		return $pts;
	}
	$chunks = preg_split('/\s{2,}/', trim($flugwegStr));
	foreach ($chunks as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === "") {
			continue;
		}
		$parts = preg_split('/\s*,\s*/', $chunk);
		if (count($parts) < 3) {
			continue;
		}
		$pts[] = array(
			"lon" => rkrk_felddaten_json_scalar_float($parts[0]),
			"lat" => rkrk_felddaten_json_scalar_float($parts[1]),
			"h" => rkrk_felddaten_json_scalar_float($parts[2]),
		);
	}
	return $pts;
}

/**
 * 3D-Pfadlänge in Metern: am ersten Wegpunkt Startflughöhe 0 m, vertikal auf dessen Flughöhe,
 * dann Summe der euklidischen 3D-Segmente (horizontal Haversine + Höhendifferenz) entlang des Flugwegs.
 */
function rkrk_felddaten_flugweg_path_length_m($waypoints) {
	$n = count($waypoints);
	if ($n < 1) {
		return 0.0;
	}
	$sum = abs($waypoints[0]["h"]);
	for ($i = 0; $i < $n - 1; $i++) {
		$a = $waypoints[$i];
		$b = $waypoints[$i + 1];
		$dh = $b["h"] - $a["h"];
		$hor = rkrk_felddaten_haversine_m($a["lon"], $a["lat"], $b["lon"], $b["lat"]);
		$sum += sqrt($hor * $hor + $dh * $dh);
	}
	return $sum;
}

/**
 * Missions-Geschwindigkeit (m/s) aus missionSettings.
 */
function rkrk_felddaten_json_mission_speed_ms($rawJson) {
	if (!is_array($rawJson) || !isset($rawJson["missionSettings"]) || !is_array($rawJson["missionSettings"])) {
		return 5.0;
	}
	$ms = $rawJson["missionSettings"];
	foreach (array("djiPilot2", "djiPilot", "dji", "px", "plan") as $key) {
		if (!isset($ms[$key]) || !is_array($ms[$key])) {
			continue;
		}
		if (isset($ms[$key]["speed"])) {
			$s = rkrk_felddaten_json_scalar_float($ms[$key]["speed"], 0.0);
			if ($s > 0.01) {
				return $s;
			}
		}
		if (isset($ms[$key]["travelSpeed"])) {
			$s = rkrk_felddaten_json_scalar_float($ms[$key]["travelSpeed"], 0.0);
			if ($s > 0.01) {
				return $s;
			}
		}
		if (isset($ms[$key]["cruiseSpeed"])) {
			$s = rkrk_felddaten_json_scalar_float($ms[$key]["cruiseSpeed"], 0.0);
			if ($s > 0.01) {
				return $s;
			}
		}
	}
	return 5.0;
}

/**
 * waypointType aus Wegpunkt-Properties bzw. Missions-Default.
 */
function rkrk_felddaten_json_resolve_waypoint_type($props, $rawJson) {
	if (!is_array($props)) {
		$props = array();
	}
	$wpType = isset($props["waypointType"]) ? trim((string)$props["waypointType"]) : "";
	if ($wpType !== "" && empty($props["useMissionWaypointType"])) {
		return $wpType;
	}
	if (is_array($rawJson) && isset($rawJson["missionSettings"]) && is_array($rawJson["missionSettings"])) {
		foreach (array("djiPilot2", "djiPilot") as $key) {
			if (isset($rawJson["missionSettings"][$key]["waypointType"])) {
				$t = trim((string)$rawJson["missionSettings"][$key]["waypointType"]);
				if ($t !== "") {
					return $t;
				}
			}
		}
	}
	return "coordinateTurn";
}

/**
 * Effektive Geschwindigkeit (m/s) eines Wegpunkts.
 */
function rkrk_felddaten_json_waypoint_speed_ms($props, $rawJson) {
	if (!is_array($props)) {
		return rkrk_felddaten_json_mission_speed_ms($rawJson);
	}
	if (empty($props["useMissionSpeed"])) {
		$s = rkrk_felddaten_json_scalar_float($props["speed"] ?? 0, 0.0);
		if ($s > 0.01) {
			return $s;
		}
	}
	return rkrk_felddaten_json_mission_speed_ms($rawJson);
}

/**
 * Zusatzzeit pro Wegpunkt: coordinateTurn 1 s, sonst 5.5 s.
 */
function rkrk_felddaten_json_turn_seconds($waypointType) {
	return (strcasecmp(trim((string)$waypointType), "coordinateTurn") === 0) ? 1.0 : 5.5;
}

/**
 * Wegpunkte aus geojson (type=1, height>0) inkl. Properties.
 */
function rkrk_felddaten_json_mission_waypoints_from_raw($rawJson) {
	$wps = array();
	if (!is_array($rawJson) || !isset($rawJson["geojson"]["features"]) || !is_array($rawJson["geojson"]["features"])) {
		return $wps;
	}
	foreach ($rawJson["geojson"]["features"] as $feature) {
		if (!is_array($feature)) {
			continue;
		}
		$geom = $feature["geometry"] ?? array();
		if (($geom["type"] ?? "") !== "Point") {
			continue;
		}
		$props = $feature["properties"] ?? array();
		$height = rkrk_felddaten_json_scalar_float($props["height"] ?? 0, 0.0);
		$type = intval($props["type"] ?? 0);
		if ($height <= 0.0 || $type !== 1) {
			continue;
		}
		$coords = $geom["coordinates"] ?? array();
		$wps[] = array(
			"lon" => rkrk_felddaten_json_scalar_float($coords[0] ?? 0),
			"lat" => rkrk_felddaten_json_scalar_float($coords[1] ?? 0),
			"h" => $height,
			"props" => $props,
		);
	}
	return $wps;
}

/**
 * 3D-Segmentlänge zwischen zwei Wegpunkten (Meter).
 */
function rkrk_felddaten_waypoint_segment_m($a, $b) {
	$dh = $b["h"] - $a["h"];
	$hor = rkrk_felddaten_haversine_m($a["lon"], $a["lat"], $b["lon"], $b["lat"]);
	return sqrt($hor * $hor + $dh * $dh);
}

/**
 * Flugzeit (s): Summe Segmentdistanz/Geschwindigkeit + Wendezusatz pro Wegpunkt.
 */
function rkrk_felddaten_json_compute_flight_time_s($waypoints, $rawJson) {
	$n = count($waypoints);
	if ($n < 1) {
		return 0.0;
	}
	$tFly = 0.0;
	$speed0 = rkrk_felddaten_json_waypoint_speed_ms($waypoints[0]["props"], $rawJson);
	if ($speed0 > 0.01) {
		$tFly += abs($waypoints[0]["h"]) / $speed0;
	}
	for ($i = 0; $i < $n - 1; $i++) {
		$seg = rkrk_felddaten_waypoint_segment_m($waypoints[$i], $waypoints[$i + 1]);
		$speed = rkrk_felddaten_json_waypoint_speed_ms($waypoints[$i + 1]["props"], $rawJson);
		if ($speed > 0.01) {
			$tFly += $seg / $speed;
		}
	}
	$tTurn = 0.0;
	foreach ($waypoints as $wp) {
		$wpType = rkrk_felddaten_json_resolve_waypoint_type($wp["props"], $rawJson);
		$tTurn += rkrk_felddaten_json_turn_seconds($wpType);
	}
	return $tFly + $tTurn;
}

/**
 * flightTime / flightDistance im Roh-JSON setzen.
 * Flugzeit im Katalog: Minuten (dezimal); flightTime im JSON: Sekunden (UAV-Editor).
 */
function rkrk_felddaten_json_sync_flight_metrics_to_raw(&$rawJson, $JsonData) {
	if (!is_array($rawJson)) {
		return;
	}
	if (isset($JsonData["Flugzeit"])) {
		$rawJson["flightTime"] = rkrk_felddaten_catalog_min_to_json_seconds($JsonData["Flugzeit"]);
	}
	if (isset($JsonData["Distanz"])) {
		$rawJson["flightDistance"] = rkrk_felddaten_json_scalar_float($JsonData["Distanz"], 0.0);
	}
}

/**
 * Nach GetJSONData: fehlende Flugzeit/Distanz aus 3D-Flugweg berechnen.
 *
 * Distanz: 3D-Pfadlänge in Metern (flightDistance im JSON).
 * Flugzeit: (Segmente / speed + Wendezusatz) in Minuten (dezimal, 2 Nachkommastellen).
 *
 * @return bool true wenn mindestens ein Wert neu berechnet wurde
 */
function rkrk_felddaten_json_fill_flugzeit_distanz(&$JsonData, $rawJson = null) {
	$fz = isset($JsonData["Flugzeit"]) ? rkrk_felddaten_json_scalar_float($JsonData["Flugzeit"], -1.0) : -1.0;
	$needFlugzeit = ($fz <= 0.0);
	if (is_array($rawJson) && array_key_exists("flightTime", $rawJson)) {
		$needFlugzeit = (rkrk_felddaten_json_scalar_float($rawJson["flightTime"], -1.0) <= 0.0);
	}

	$dist = isset($JsonData["Distanz"]) ? rkrk_felddaten_json_scalar_float($JsonData["Distanz"], -1.0) : -1.0;
	$needDistanz = ($dist <= 0.0);

	if (!$needFlugzeit && !$needDistanz) {
		return false;
	}

	$waypoints = array();
	if (is_array($rawJson)) {
		$waypoints = rkrk_felddaten_json_mission_waypoints_from_raw($rawJson);
	}
	if (count($waypoints) < 1) {
		$flugweg = isset($JsonData["Flugweg"]) ? $JsonData["Flugweg"] : "";
		$parsed = rkrk_felddaten_parse_flugweg_waypoints($flugweg);
		foreach ($parsed as $p) {
			$waypoints[] = array(
				"lon" => $p["lon"],
				"lat" => $p["lat"],
				"h" => $p["h"],
				"props" => array("useMissionSpeed" => true),
			);
		}
	}
	if (count($waypoints) < 1) {
		return false;
	}

	$changed = false;
	$path_m = rkrk_felddaten_flugweg_path_length_m($waypoints);

	if ($needDistanz) {
		$JsonData["Distanz"] = round($path_m, 2);
		$changed = true;
	}

	if ($needFlugzeit) {
		$t_s = rkrk_felddaten_json_compute_flight_time_s($waypoints, $rawJson);
		$JsonData["Flugzeit"] = round($t_s / 60.0, 2);
		$changed = true;
	}

	return $changed;
}

/**
 * GetJSONData + fehlende Metriken berechnen und ggf. JSON-Datei zurückschreiben.
 */
function rkrk_felddaten_json_import_apply_metrics($jsonFilePath, &$JsonData) {
	if ($jsonFilePath === "" || !is_file($jsonFilePath)) {
		return false;
	}
	if (!function_exists("ReadJsonFile") || !function_exists("WriteJsonFile")) {
		return false;
	}
	$rawJson = ReadJsonFile($jsonFilePath);
	if (!is_array($rawJson)) {
		return false;
	}
	if (!rkrk_felddaten_json_fill_flugzeit_distanz($JsonData, $rawJson)) {
		return false;
	}
	rkrk_felddaten_json_sync_flight_metrics_to_raw($rawJson, $JsonData);
	WriteJsonFile($jsonFilePath, $rawJson);
	return true;
}


function GetFeldkatalog($catalogPath, $forceReload = false) {
	global $EnabledRegion, $OptionJaeger, $MaxSelectedEntries;
	if (function_exists('rkrk_log_data_access')) {
		rkrk_log_data_access('Missionenkatalog', basename((string)$catalogPath));
	}
	if (!is_array($EnabledRegion)) {
		$EnabledRegion = array();
	}
	if (function_exists('rkrk_feldkatalog_load')) {
		return rkrk_feldkatalog_load($catalogPath, $forceReload);
	}
	$xml_felder = array();
	if (function_exists('rkrk_catalog_load_simplexml')) {
		$xml_felder = rkrk_catalog_load_simplexml($catalogPath);
	} elseif (is_string($catalogPath) && $catalogPath !== '' && file_exists($catalogPath)) {
		if (function_exists('rkrk_load_xml_file_safe')) {
			$xml_felder = rkrk_load_xml_file_safe($catalogPath);
		} else {
			$xml_felder = simplexml_load_string(file_get_contents($catalogPath));
		}
	}
	return TransferFeldKatFromXMLtoArr($xml_felder ? $xml_felder : array(), $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
}

function GetXMLFeldKatLoaded ($FileName) {
	SetMissionLocation();
	return GetFeldkatalog($FileName);
}


/**
 * Feldkatalog-Eintrag zu einem Flug (JG/Feld), mit Fallback über Feld-ID.
 */
function rkrk_feldkatalog_entry_for_flight($Feldkatalog, $Flug) {
	if (!is_array($Feldkatalog) || !is_array($Flug)) {
		return null;
	}
	$jg = trim((string)($Flug['JG'] ?? ''));
	$feld = trim((string)($Flug['Feld'] ?? $Flug['FeldID'] ?? ''));
	if ($jg !== '' && $feld !== '' && isset($Feldkatalog[$jg][$feld]) && is_array($Feldkatalog[$jg][$feld])) {
		return $Feldkatalog[$jg][$feld];
	}
	if ($feld !== '') {
		if (strpos($feld, '-') !== false) {
			$jgFromFeld = explode('-', $feld, 2)[0];
			if (isset($Feldkatalog[$jgFromFeld][$feld]) && is_array($Feldkatalog[$jgFromFeld][$feld])) {
				return $Feldkatalog[$jgFromFeld][$feld];
			}
		}
		foreach ($Feldkatalog as $fields) {
			if (!is_array($fields)) {
				continue;
			}
			if (isset($fields[$feld]) && is_array($fields[$feld])) {
				return $fields[$feld];
			}
		}
	}
	return null;
}


function GetFeldListArrayFromXML($xmlMissionLocation) {
//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (JSON, XML-Fallback)
//////////////////////////////////////////////
global $EnabledRegion, $OptionJaeger, $MaxSelectedEntries;

if (!is_array($EnabledRegion)) {
	$EnabledRegion = array();
}
if (function_exists('rkrk_feldkatalog_load')) {
	return rkrk_feldkatalog_load($xmlMissionLocation);
}

$xml_felder = array();
if(file_exists($xmlMissionLocation)) {
	if (function_exists('rkrk_load_xml_file_safe')) {
		$xml_felder = rkrk_load_xml_file_safe($xmlMissionLocation);
	} else {
		$xml_felder = simplexml_load_string(file_get_contents($xmlMissionLocation));
	}
}

$Feldkatalog = TransferFeldKatFromXMLtoArr ($xml_felder, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
Return $Feldkatalog;

}


function ReadMissionFiles ($RegionSettings, $EnabledRegion, $onlyFeldIds = null) {
    global $RKRKLocalLoactionMissionFiles,$OptionFlughoehe;
	$onlySet = null;
	if (is_array($onlyFeldIds)) {
		$onlySet = array();
		foreach ($onlyFeldIds as $onlyId) {
			$onlyId = trim((string)$onlyId);
			if ($onlyId !== '') {
				$onlySet[$onlyId] = true;
			}
		}
		if ($onlySet === array()) {
			return array();
		}
	}
	$FlightHeights = array();
	if (isset($OptionFlughoehe) && is_array($OptionFlughoehe)) {
		$FlightHeights = array_keys($OptionFlughoehe);
	}
	$FlightHeights[] = 00;
	asort($FlightHeights);
	
	// ===========================================
	// File Liste einlesen
	// list file in den subordnern
	// ===========================================

	// JG/Region selection
	//pre_r($EnabledRegion);
	$RegionsSelected = array();
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg != 1) {
			continue;
		}
		if (!isset($RegionSettings[$key]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $JGtoSelect = trim((string)$JGtoSelect);
		  if ($JGtoSelect !== '') {
			  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		  }
		}
	}
	//pre_R($RegionsSelected);
    //pre_r($Feldkatalog);

	$KatFileList = Array();
	//echo "<ul>" .chr(10);
	//echo "Readfiles <br>";
	foreach ($RegionSettings as $Reg ) {
		//if (($EnabledRegion[$Reg["Reg"]])==1) {
		if (in_array($Reg["Reg"], $RegionsSelected)) {
			
			//echo "<li>". $Reg["Reg"] . " </li>" .chr(10);
			$KatFileList[$Reg["Reg"]]["JG"] = $Reg["Reg"];
			//$glocal_dir = "/volume1/web/rkropen/Missionen22";
			$glocal_dir= $RKRKLocalLoactionMissionFiles;
			$local_dir = $glocal_dir . $Reg["Dir"];
			if (!is_dir($local_dir)) {
				continue;
			}
			//pre_r(clean_scandir($local_dir));
			$FileList = clean_scandir($local_dir);
			//echo $FileList;
			foreach ($FileList as $singlefile ) {
				if ("@eaDir" != $singlefile && "Thumbs.db"!= $singlefile) {
					//echo $singlefile;
					$filenamelen = strlen($singlefile);
					$EndKatID = strpos($singlefile, "_");
					if ($EndKatID === false) {
						continue;
					}
					//echo " - ";
					$StartFileType =  strpos($singlefile,".",$filenamelen-8)+1;
					$FeldID = substr($singlefile, 0, $EndKatID);
					$KatFileList[$Reg["Reg"]]["__hasfiles"] = true;
					if ($onlySet !== null && !isset($onlySet[$FeldID])) {
						continue;
					}
					//echo " " . $Reg["Reg"] . " / ";
					$FileType = substr($singlefile, $StartFileType ,$filenamelen-$StartFileType);
					$KatFileList[$Reg["Reg"]][$FeldID]["JG"] = $Reg["Reg"];
					$KatFileList[$Reg["Reg"]][$FeldID]["FeldID"] = $FeldID;
					$KatFileList[$Reg["Reg"]][$FeldID]["Location"] = $local_dir;
					//echo "<br>";
					if ($FileType=="json" or $FileType=="jpg" ) {
						//echo $singlefile;
/* Kann Umgestellt werden auf die Einheitliche Feldnamenerkennung... wenn alle Feldnamen konsistent sind.
						$MeaningFilename = GetMeaningFilename($singlefile);
						$KatFileList[$Reg["Reg"]][$FeldID]["CorName"] = $MeaningFilename[7];
						$KatFileList[$Reg["Reg"]][$FeldID]["Feldname"] = $MeaningFilename[1];
						$KatFileList[$Reg["Reg"]][$FeldID]["Flurname"] = $MeaningFilename[2];
						$KatFileList[$Reg["Reg"]][$FeldID]["GrundstueckID"] = $MeaningFilename[3];
*/
/**/
						//Search for height _40_/_50_/_60_/_70_
						//$FlightHeights = Array(00,40,50,60,70,80,90,100);
						//pre_R($FlightHeights);
						foreach ($FlightHeights as $Flightheight) {
						//foreach ($OptionFlughoehe as $Flightheight) {
							$FlHghtPos = strpos($singlefile,"_".$Flightheight."_");
							if ($FlHghtPos !== false) {
								//check if Field by accident has the same GrundstueckID as flightheight
								$NextFlHghtPos = strpos($singlefile,"_".$Flightheight."_",$FlHghtPos + 1);
								if ($NextFlHghtPos !== false && $NextFlHghtPos != $FlHghtPos) {
									$FlHghtPos = $NextFlHghtPos;
								}
							}
							if ($FlHghtPos !== false) {
								$CorFieldName = substr($singlefile, $EndKatID+1,$FlHghtPos-$EndKatID-1);
								$fromJson = (strcasecmp((string)$FileType, 'json') === 0);
								$haveJson = !empty($KatFileList[$Reg["Reg"]][$FeldID]["NameFromJson"]);
								if ($fromJson || !$haveJson) {
									$KatFileList[$Reg["Reg"]][$FeldID]["CorName"] = $CorFieldName;
									//Split to GrundstueckID and Filename
									//GrundstueckID starts with last "_"
									$StrtStr= 0;
									$StrFnd= strpos($CorFieldName, "_", $StrtStr);
									while ($StrFnd !== false) {
										$StrtStr= $StrFnd;
										$StrFnd= strpos($CorFieldName, "_", $StrtStr+1);
									}
									$KatFileList[$Reg["Reg"]][$FeldID]["Feldname"] = substr($CorFieldName,0,$StrtStr);
									$KatFileList[$Reg["Reg"]][$FeldID]["GrundstueckID"] = substr($CorFieldName,$StrtStr+1,strlen($CorFieldName)-$StrtStr-1);
									if ($fromJson) {
										$KatFileList[$Reg["Reg"]][$FeldID]["NameFromJson"] = true;
									}
								}
								break;
							}	
							
						}
/**/
						//echo "<br>";
					}
					$KatFileList[$Reg["Reg"]][$FeldID][$FileType][] = $singlefile;
					$FileList[] = $singlefile;
					//echo ".";
				}
			}
			//pre_r(clean_readdir($local_dir));
		}
	}
	//pre_r($KatFileList);
	return $KatFileList;
}


Function GetFieldList ($Feldkatalog) {
	$FieldList = Array();
	if (!is_array($Feldkatalog)) {
		return $FieldList;
	}
	foreach ($Feldkatalog as $JG) {
		if (!is_array($JG)) {
			continue;
		}
		foreach ($JG as $Field) {
			if (!is_array($Field)) {
				continue;
			}
			$fid = trim((string)($Field["FeldID"] ?? ''));
			if ($fid === '') {
				continue;
			}
			$FieldList[$fid] = $fid;
		}
	}
	asort($FieldList);
	return $FieldList;
}

Function GetFeldKatalogList ($Feldkatalog) {
	$FeldKatalogList = Array();
	if (!is_array($Feldkatalog)) {
		return $FeldKatalogList;
	}
	foreach ($Feldkatalog as $JG) {
		if (!is_array($JG)) {
			continue;
		}
		foreach ($JG as $Field) {
			if (!is_array($Field)) {
				continue;
			}
			$fid = trim((string)($Field["FeldID"] ?? ''));
			if ($fid === '') {
				continue;
			}
			$FeldKatalogList[$fid] = $Field;
		}
	}
	return $FeldKatalogList;
}

Function AdjustFeldKatalog (&$Feldkatalog) {
	foreach ($Feldkatalog as $RKey => $Region) {
		foreach ($Region as $FKey => $Feld) {
			
			//Retour sortieren 20240429
			//<Flug>24,23,22,21</Flug>
			//$FlightList = str_replace(" ", "", strval($Feld["Flug"]));
			//$FlightListArray=explode(",",$FlightList);
			//arsort($FlightListArray);
			//$Feldkatalog[$RKey][$FKey]["Flug"]= implode(",",$FlightListArray);
			//pre_r($FlightListArray);
			
			//Retour sortieren 20240429
			//<Funde>24,21</Funde>
			//$FlightList = str_replace(" ", "", strval($Feld["Funde"]));
			//$FlightListArray=explode(",",$FlightList);
			//arsort($FlightListArray);
			//$Feldkatalog[$RKey][$FKey]["Funde"]= implode(",",$FlightListArray);
			//pre_r($FlightListArray);
		}
	}
	//pre_r($Feldkatalog);
}


Function TransferFeldKatFromXMLtoArr ($xml_felder, $EnabledRegion, &$OptionJaeger, &$MaxSelectedEntries) {
	global $searchSDZ, $replaceSDZ, $RegionSettings, $searchSDZH, $replaceSDZH,$csvMissionLocation,$RKRKContactFile;
	// ===========================================
	// Transfer Feldkatalog von XML to Structured Array
	// ===========================================
	//	$searchSDZ  = array('Ã¼', 'Ã¶', 'Ã?', 'Ã¤', 'Ã?Â¼', 'Ã?Â¤' ,'Ã¨' , 'Ã¶', '%3F', 'Ã', 'Ã','Ã', 'Ã', 'ï¿½', '&'    ); // '\n' ,
	//	$replaceSDZ = array('ü'	, 'ö' , 'Ü' , 'ä' , 'ü'   ,'ä'     , 'Ü' , 'Ü' , 'Ü' , 'Ü' , 'Ä'    ,'Ä'   , 'Ö'    , '?'  , '&#38;');  // '; '  ,

	// JG/Region selection
	//pre_r($EnabledRegion);
	$RegionsSelected = array();
	if (!is_array($EnabledRegion)) {
		$EnabledRegion = array();
	}
	if (!is_iterable($xml_felder)) {
		$xml_felder = array();
	}
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg != 1) {
			continue;
		}
		if (!isset($RegionSettings[$key]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $JGtoSelect = trim((string)$JGtoSelect);
		  if ($JGtoSelect !== '') {
			  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		  }
		}
	}


	//pre_r($xml_felder);
/*
	foreach ($xml_felder as $feld) {

		$JG = strval($feld->JG);
		$FeldID = strval($feld->FeldID);
		$Feldkatalog[$JG][$FeldID]["JG"]= $JG;
		$Feldkatalog[$JG][$FeldID]["Number"]= strval($feld->Number);
		$Feldkatalog[$JG][$FeldID]["FeldID"]= $FeldID;
		$Feldkatalog[$JG][$FeldID]["Feldname"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->Feldname));
		$Feldkatalog[$JG][$FeldID]["Flurname"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->Flurname));
		$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= strval($feld->GrundstueckID);
		$Feldkatalog[$JG][$FeldID]["Flugzeit"]= strval($feld->Flugzeit);
		$Feldkatalog[$JG][$FeldID]["Flaeche"]= strval($feld->Flaeche);
		$Feldkatalog[$JG][$FeldID]["Wegpunkte"]= strval($feld->Wegpunkte);
		$Feldkatalog[$JG][$FeldID]["Distanz"]= strval($feld->Distanz);
		$Feldkatalog[$JG][$FeldID]["Gemeinde"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->Gemeinde));
		$Feldkatalog[$JG][$FeldID]["Gde"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->Gde));
		$Feldkatalog[$JG][$FeldID]["JgdGebiet"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->JgdGebiet));
		$Feldkatalog[$JG][$FeldID]["Betrieb"]= strval($feld->Betrieb);
		$Feldkatalog[$JG][$FeldID]["Freigabe"]= strval($feld->Freigabe);
		$Feldkatalog[$JG][$FeldID]["Warnung"]= strval($feld->Warnung);
		$OptionJaeger[strval($feld->Jaeger)] = strval($feld->Jaeger);
		$Feldkatalog[$JG][$FeldID]["Jaeger"]= str_replace($searchSDZ, $replaceSDZ ,$feld->Jaeger);
		$Feldkatalog[$JG][$FeldID]["Bemerkung"]= str_replace($searchSDZ, $replaceSDZ ,$feld->Bemerkung);
		$Feldkatalog[$JG][$FeldID]["Flug"]= strval($feld->Flug);
		$Feldkatalog[$JG][$FeldID]["Funde"]= strval($feld->Funde);
		$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->SuchfeldBild));
		$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->FlugplanBild));
		$Feldkatalog[$JG][$FeldID]["JasonFile"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->JasonFile));
		$Feldkatalog[$JG][$FeldID]["KMLFile"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->KMLFile));
		$Feldkatalog[$JG][$FeldID]["LichtiFile"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->LichtiFile));
		$Feldkatalog[$JG][$FeldID]["Bauer"]= str_replace($searchSDZ, $replaceSDZ ,strval($feld->Bauer));
		$Feldkatalog[$JG][$FeldID]["Notiz"]= str_replace($searchSDZ, $replaceSDZ ,$feld->Notiz);
		$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Startplatz"]= strval($feld->Grundstueckdaten->Startplatz);
		$Feldkatalog[$JG][$FeldID]["Grundstueckdaten"]["Zentrum"]= strval($feld->Grundstueckdaten->Zentrum);
		$Feldkatalog[$JG][$FeldID]["Flaechendaten"]= strval($feld->Flaechendaten);
		$Feldkatalog[$JG][$FeldID]["Flugweg"]= strval($feld->Flugweg);
		$Feldkatalog[$JG][$FeldID]["Export"]= strval($feld->Export);
		$Feldkatalog[$JG][$FeldID]["Select"]= strval($feld->Select);
		$i++;
	}
*/	
	//create Feldliste
	$FeldList = Array();
	$i=0;
	$readXmlText = function ($v) {
		$text = function_exists('rkrk_xml_prepare_field_text')
			? rkrk_xml_prepare_field_text($v)
			: (function_exists('rkrk_xml_to_string') ? rkrk_xml_to_string($v) : strval($v));
		return function_exists('rkrk_text_utf8') ? rkrk_text_utf8($text) : $text;
	};
	foreach ($xml_felder as $feld) {

		$FeldList[$i]["JG"]= strval($feld->JG);
		$FeldList[$i]["Number"]= strval($feld->Number);
		$FeldList[$i]["FeldID"]= strval($feld->FeldID);
		$FeldList[$i]["Feldname"]= $readXmlText($feld->Feldname);
		$FeldList[$i]["Flurname"]= $readXmlText($feld->Flurname);
		$FeldList[$i]["GrundstueckID"]= strval($feld->GrundstueckID);
		$FeldList[$i]["Flugzeit"]= strval($feld->Flugzeit);
		$FeldList[$i]["Flaeche"]= strval($feld->Flaeche);
		$FeldList[$i]["Wegpunkte"]= strval($feld->Wegpunkte);
		$FeldList[$i]["Distanz"]= strval($feld->Distanz);
		$FeldList[$i]["Gemeinde"]= $readXmlText($feld->Gemeinde);
		$FeldList[$i]["Gde"]= $readXmlText($feld->Gde);
		$FeldList[$i]["JgdGebiet"]= $readXmlText($feld->JgdGebiet);
		$FeldList[$i]["Betrieb"]= $readXmlText($feld->Betrieb);
		$FeldList[$i]["Freigabe"]= strval($feld->Freigabe);
		$FeldList[$i]["Warnung"]= strval($feld->Warnung);
		//$OptionJaeger[strval($feld->Jaeger)] = strval($feld->Jaeger);
		$FeldList[$i]["Jaeger"]= $readXmlText($feld->Jaeger);
		$FeldList[$i]["Bemerkung"]= $readXmlText($feld->Bemerkung);
		$FeldList[$i]["Flug"]= strval($feld->Flug);
		$FeldList[$i]["Funde"]= strval($feld->Funde);
		$FeldList[$i]["StdFlughohe"]= strval($feld->StdFlughohe);
		$FeldList[$i]["SuchfeldBild"]= $readXmlText($feld->SuchfeldBild);
		$FeldList[$i]["FlugplanBild"]= $readXmlText($feld->FlugplanBild);
		$FeldList[$i]["JasonFile"]= $readXmlText($feld->JasonFile);
		$FeldList[$i]["KMLFile"]= $readXmlText($feld->KMLFile);
		$FeldList[$i]["KMZFile"]= $readXmlText($feld->KMZFile);
		$FeldList[$i]["LichtiFile"]= $readXmlText($feld->LichtiFile);
		$FeldList[$i]["Bauer"]= $readXmlText($feld->Bauer);
		$FeldList[$i]["Notiz"]= $readXmlText($feld->Notiz);
		$FeldList[$i]["Grundstueckdaten"]["Startplatz"]= strval($feld->Grundstueckdaten->Startplatz);
		if (strval($feld->Grundstueckdaten->Parkplatz)<>"")
			$FeldList[$i]["Grundstueckdaten"]["Parkplatz"]= strval($feld->Grundstueckdaten->Parkplatz);
		$FeldList[$i]["Grundstueckdaten"]["Zentrum"]= strval($feld->Grundstueckdaten->Zentrum);
		$FeldList[$i]["Flaechendaten"]= strval($feld->Flaechendaten);
		$FeldList[$i]["Flugweg"]= strval($feld->Flugweg);
		$FeldList[$i]["Erstellt"]= strval($feld->Erstellt);
		$FeldList[$i]["Aenderung"]= strval($feld->Aenderung);
		$FeldList[$i]["Update"]= strval($feld->Update);
		$FeldList[$i]["Export"]= strval($feld->Export);
		$FeldList[$i]["Select"]= strval($feld->Select);
		$i++;
	}
	return TransferFeldKatFromListToArr($FeldList, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
}

Function TransferFeldKatFromListToArr ($FeldList, $EnabledRegion, &$OptionJaeger, &$MaxSelectedEntries) {
	global $RegionSettings, $csvMissionLocation, $RKRKContactFile;
	if (!is_array($FeldList)) {
		$FeldList = array();
	}
	if (!is_array($EnabledRegion)) {
		$EnabledRegion = array();
	}
	$RegionsSelected = array();
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg != 1) {
			continue;
		}
		if (!isset($RegionSettings[$key]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $JGtoSelect = trim((string)$JGtoSelect);
		  if ($JGtoSelect !== '') {
			  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		  }
		}
	}

	if ($FeldList === array()) {
		$MaxSelectedEntries = 1;
		return array();
	}
	// Eine Zeile, drei Spalten: fehlendes oder NULL JG/Feldname/Flurname bleibt leer.
	// Eine NULL-Regionsliste wird oben zum leeren Array und kuerzt diese Spalten nicht.
	$FeldList = array_values($FeldList);
	$sortJg = array();
	$sortFeldname = array();
	$sortFlurname = array();
	$nSort = count($FeldList);
	for ($i = 0; $i < $nSort; $i++) {
		$feld = $FeldList[$i];
		$jg = '';
		$name = '';
		$flur = '';
		if (is_array($feld)) {
			if (isset($feld['JG']) && !is_array($feld['JG']) && !is_object($feld['JG'])) {
				$jg = (string)$feld['JG'];
			}
			if (isset($feld['Feldname']) && !is_array($feld['Feldname']) && !is_object($feld['Feldname'])) {
				$name = (string)$feld['Feldname'];
			}
			if (isset($feld['Flurname']) && !is_array($feld['Flurname']) && !is_object($feld['Flurname'])) {
				$flur = (string)$feld['Flurname'];
			}
		}
		$sortJg[$i] = $jg;
		$sortFeldname[$i] = $name;
		$sortFlurname[$i] = $flur;
	}
	if ($nSort > 0) {
		array_multisort($sortJg, SORT_ASC, $sortFeldname, SORT_ASC, $sortFlurname, SORT_ASC, $FeldList);
	}

	$csv_desc = ';';
	$csv_desc_replace = '/';
	$Contacts = csv_to_array($csvMissionLocation.$RKRKContactFile,$csv_desc);
	if (!is_array($Contacts)) {
		$Contacts = array();
	}
	$OptionJaeger[]="";
	foreach ($Contacts as $line) {
		if (!is_array($line)) {
			continue;
		}
		if (like($line["Typ"] ?? '', "Jäger")) {
			$init = trim((string)($line["Initialen"] ?? ''));
			if ($init !== '') {
				$OptionJaeger[$init] = $init;
			}
		}
	}
	sort($OptionJaeger);
	//pre_R($OptionJaeger);
	//add in structure
	$i=0;
	$si=1;
	$Feldkatalog = Array();
	foreach ($FeldList as $feld) {
		if (!is_array($feld)) {
			continue;
		}
		$JG=$feld["JG"] ?? '';
		$FeldID=$feld["FeldID"] ?? '';
		if ($JG === '' || $FeldID === '') {
			continue;
		}
		$Feldkatalog[$JG][$FeldID] = $feld;
		$i++;

	// debugging

		// Ü - PROBLEM IM xml
		//$searchSDZ  = array('Ã¼', 'Ã¶', 'Ã?', 'Ã¤', 'Ã?Â¼', 'Ã?Â¤' ,'Ã¨' , 'Ã¶', '%3F', 'Ã', 'Ã','Ã', 'Ã', 'ï¿½', '&'    ); // '\n' ,
		//$replaceSDZ = array('ü'	, 'ö' , 'Ü' , 'ä' , 'ü'   ,'ä'     , 'Ü' , 'Ü' , 'Ü' , 'Ü' , 'Ä'    ,'Ä'   , 'Ö'    , '?'  , '&#38;');  // '; '  ,
		//if (($FeldID=="21-006") || ($FeldID=="ZG-083"))  {
		//	echo $FeldID . " - N: " . str_replace($searchSDZ, $replaceSDZ, $feld->Notiz) . "<br>";
		//	echo $FeldID . " - N: " . $Feldkatalog[$JG][$FeldID]["Notiz"] . "<br>";
		//	echo $FeldID . " - N: " . str_replace($searchSDZ, $replaceSDZ, $Feldkatalog[$JG][$FeldID]["Notiz"]) . "<br>";
		//	echo $FeldID . " - B: " . $feld->Bemerkung . "<br>";
		//	echo $FeldID . " - B: " . $Feldkatalog[$JG][$FeldID]["Bemerkung"] . "<br>";
		//	echo $FeldID . " - B: " . str_replace($searchSDZ, $replaceSDZ, $Feldkatalog[$JG][$FeldID]["Bemerkung"]) . "<br>";
		//	ECHO $searchSDZH . " - ". str_replace($searchSDZ, $replaceSDZ, $replaceSDZH) ."<BR>";
		//	PRE_R($searchSDZH);
		//}
		
		/*
		if (substr($feld->FeldID, 0,2) == "TF") {
			echo $feld->FeldID . " - " . str_replace($searchSDZ, $replaceSDZ ,strval($feld->Feldname)) . " - "  . str_replace($searchSDZ, $replaceSDZ ,strval($feld->Flurname)) . "<br>";
			echo $Feldkatalog[$JG][$FeldID]["FeldID"] . " - " . $Feldkatalog[$JG][$FeldID]["Feldname"] . " - "  . $Feldkatalog[$JG][$FeldID]["Flurname"] . "<br><br>";
		}
		*/
		
		// selected to show
		//if ($EnabledRegion[strval($feld->JG)]==1) {
		if (in_array($JG, $RegionsSelected)) {
			$Feldkatalog[$JG][$FeldID]["ListNr"]= $si;
			$si++;
		} else {
			$Feldkatalog[$JG][$FeldID]["ListNr"]= "";
		}	
		
		if (isset($RegionSettings[$JG]) && is_array($RegionSettings[$JG])) {
			if (!isset($RegionSettings[$JG]["MaxID"])) {
				$RegionSettings[$JG]["MaxID"] = 0;
			}
			if (!isset($RegionSettings[$JG]["NumEntries"])) {
				$RegionSettings[$JG]["NumEntries"] = 0;
			}
			if ($RegionSettings[$JG]["MaxID"] < intval(substr("$FeldID", -3))) {
				$RegionSettings[$JG]["MaxID"]  = intval(substr("$FeldID", -3));
			}
			$RegionSettings[$JG]["NumEntries"]  = $RegionSettings[$JG]["NumEntries"] + 1;
		}
		
	}
	$MaxSelectedEntries = $si;
	//echo "MaxNum" . $MaxSelectedEntries;
	asort($OptionJaeger);
	//pre_r($array_keys($Feldkatalog));
	//pre_r($Feldkatalog);
	//pre_r($RegionSettings);
	
	return $Feldkatalog;
	

}


if (!function_exists('SaveFlugkatalog')) {
	Function SaveFlugkatalog($Feldkatalog, $dosafe, $XMLLocation = "") {
		return SaveFlugkatalog2XML($Feldkatalog, $dosafe, $XMLLocation);
	}
}

Function SaveFlugkatalog2XML ($Feldkatalog, $dosafe, $XMLLocation = "") {
	global $xmlMissionLocation, $RKRKFelderfileXML, $FluzeitStartUndLandung, $FlugzeitTreshold ;
	//$dosafe= true;
	$changes= 1;
	$i= 1;
	AdjustFeldKatalog ($Feldkatalog);
	if ($XMLLocation=="") {
		$XMLLocation=$xmlMissionLocation;
	}
	
	if ($dosafe) {
		//echo "do save <br>";
		$filecontent = "";
		$filecontent .= XMLHeader("Felder");
		$xmlText = function ($v) {
			return function_exists('rkrk_xml_prepare_field_text')
				? rkrk_xml_prepare_field_text($v)
				: rkrk_xml_to_string($v);
		};
		foreach ($Feldkatalog as $Key => $Region) {
			if (!is_array($Region)) {
				continue;
			}
			foreach ($Region as $Key => $Feld) {
				//echo $i . "<br>";
				//echo  $Feld["FeldID"] . " " . $Feld["Feldname"] . " " . $Feld["GrundstueckID"] . "<br>";
				// debugging
				//if ($Feld["FeldID"]=="TF-007") {
				//	echo $Feld["FeldID"] . " - " . $Feld["Flurname"];
				//}
				if (!is_array($Feld)) {
					continue;
				}
				$feldId = (isset($Feld["FeldID"]) && !is_array($Feld["FeldID"])) ? trim((string)$Feld["FeldID"]) : '';
				if ($feldId !== "") {
					$setcontent = "";
					$setcontent .= XMLComment("FELD " . $feldId . " " . $Feld["Feldname"] . " " . $Feld["GrundstueckID"], 0);
					$setcontent .= XMLSetStart("Feld", $i , 1);
					$setcontent .= XMLField("Number", $i , 2);
					$setcontent .= XMLField("FeldID", $feldId, 2);
					$setcontent .= XMLField("JG", $Feld["JG"] ?? "", 2);
					$setcontent .= XMLField("Feldname", $xmlText($Feld["Feldname"] ?? ""), 2);
					$setcontent .= XMLField("Flurname", $xmlText($Feld["Flurname"] ?? ""), 2);
					$setcontent .= XMLField("GrundstueckID", $Feld["GrundstueckID"] ?? "", 2);
					if (($Feld["Flugzeit"] ?? '')>$FlugzeitTreshold) {
						// Zeit in Sekunden
						//$setcontent .= XMLField("Flugzeit",round($FluzeitStartUndLandung+($Feld['Flugzeit'])/60,1), 2);
						$setcontent .= XMLField("Flugzeit",round(($Feld['Flugzeit'] ?? 0)/60,1), 2);
					} else {
						// Zeit in Minuten
						$setcontent .= XMLField("Flugzeit", $Feld["Flugzeit"] ?? "", 2);
					}	
					$setcontent .= XMLField("Flaeche", $Feld["Flaeche"] ?? "", 2);
					$setcontent .= XMLField("Wegpunkte", $Feld["Wegpunkte"] ?? "", 2);
					$setcontent .= XMLField("Distanz", $Feld["Distanz"] ?? "", 2);
					$setcontent .= XMLField("Gemeinde", $xmlText($Feld["Gemeinde"] ?? ""), 2);
					$setcontent .= XMLField("Betrieb", $xmlText($Feld["Betrieb"] ?? ""), 2);
					$setcontent .= XMLField("Freigabe", $Feld["Freigabe"] ?? "", 2);
					$setcontent .= XMLField("Warnung", $Feld["Warnung"] ?? "", 2);
					$setcontent .= XMLField("Jaeger", $xmlText($Feld["Jaeger"] ?? ""), 2);
					$setcontent .= XMLField("Bemerkung", $xmlText($Feld["Bemerkung"] ?? ""), 2);
					$setcontent .= XMLField("Flug", $Feld["Flug"] ?? "", 2);
					$setcontent .= XMLField("Funde", $Feld["Funde"] ?? "", 2);
					//temporäres setzen der Standard Flughöhe 28.04.2024
					//if ($Feld["StdFlughohe"]!="") {
						$setcontent .= XMLField("StdFlughohe", $Feld["StdFlughohe"] ?? "", 2);
					//} elseif (strpos($Feld["JasonFile"], "_70_") !== false) {
					//	$setcontent .= XMLField("StdFlughohe", "70", 2);
					//} elseif (strpos($Feld["JasonFile"], "_60_") !== false) {
					//	$setcontent .= XMLField("StdFlughohe", "60", 2);
					//} elseif (strpos($Feld["JasonFile"], "_50_") !== false) {
					//	$setcontent .= XMLField("StdFlughohe", "50", 2);
					//} elseif (strpos($Feld["JasonFile"], "_40_") !== false) {
					//	$setcontent .= XMLField("StdFlughohe", "40", 2);
					//} elseif (strpos($Feld["JasonFile"], "_30_") !== false) {
					//	$setcontent .= XMLField("StdFlughohe", "30", 2);
					//} else {
					//	$setcontent .= XMLField("StdFlughohe", $Feld["StdFlughohe"], 2);
					//}
					$setcontent .= XMLField("SuchfeldBild", $xmlText($Feld["SuchfeldBild"] ?? ""), 2);
					$setcontent .= XMLField("FlugplanBild", $xmlText($Feld["FlugplanBild"] ?? ""), 2);
					$setcontent .= XMLField("JasonFile", $xmlText($Feld["JasonFile"] ?? ""), 2);
					$setcontent .= XMLField("KMLFile", $xmlText($Feld["KMLFile"] ?? ""), 2);
					$setcontent .= XMLField("KMZFile", $xmlText($Feld["KMZFile"] ?? ""), 2);
					$gsDaten = (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) ? $Feld["Grundstueckdaten"] : array();
					$setcontent .= XMLField("LichtiFile", $xmlText($Feld["LichtiFile"] ?? ""), 2);
					$setcontent .= XMLField("Bauer", $xmlText($Feld["Bauer"] ?? ""), 2);
					$setcontent .= XMLField("Notiz", $xmlText($Feld["Notiz"] ?? ""), 2);
					$setcontent .= XMLFolderStart("Grundstueckdaten", 2);
					$setcontent .= XMLField("Startplatz", $gsDaten["Startplatz"] ?? "", 3);
					if (($gsDaten["Parkplatz"] ?? "")<>"")
						$setcontent .= XMLField("Parkplatz", $gsDaten["Parkplatz"], 3);
					$setcontent .= XMLField("Zentrum", $gsDaten["Zentrum"] ?? "", 3);
					$setcontent .= XMLFolderEnd("Grundstueckdaten", 2);
					$setcontent .= XMLField("Flaechendaten", $Feld["Flaechendaten"] ?? "", 2);
					$setcontent .= XMLField("Flugweg", $Feld["Flugweg"] ?? "", 2);
					$setcontent .= XMLField("Export", $Feld["Export"] ?? "", 2);
					/* first settings */
					//if ($Feld["Erstellt"]=="") { 
					//	if (strpos(" " . $Feld["Bemerkung"], "Neu")!=0) {
					//		$tmpData = substr(" " . $Feld["Bemerkung"], strpos(" " . $Feld["Bemerkung"], "Neu ")+4, 10);
					//	} else {
					//		$tmpData = "01.04.20" . substr($Feld["SuchfeldBild"], -6,2);
					//	}
					//	$setcontent .= XMLField("Erstellt", $tmpData, 2);
					//} else {
						$setcontent .= XMLField("Erstellt", $Feld["Erstellt"] ?? "", 2);
					//}
					//if ($Feld["Aenderung"]=="") { 
					//	if (strpos(" " . $Feld["Bemerkung"], "Update von Jason ")!=0) {
					//		$tmpData = substr(" " . $Feld["Bemerkung"], strpos(" " . $Feld["Bemerkung"], "Update von Jason ")+17, 10);
					//	} elseif (strpos(" " . $Feld["Bemerkung"], "Neu")!=0) {
					//		$tmpData = substr(" " . $Feld["Bemerkung"], strpos(" " . $Feld["Bemerkung"], "Neu ")+4, 10);
					//	} else {
					//		$tmpData = "01.04.20" . substr($Feld["SuchfeldBild"], -6,2);
					//	}
					//	$setcontent .= XMLField("Aenderung", $tmpData, 2);
					//} else {
						$setcontent .= XMLField("Aenderung", $Feld["Aenderung"] ?? "", 2);
					//}
					//if ($Feld["Update"]=="") { 
					//	if (strpos(" " . $Feld["Bemerkung"], "Update von Jason ")!=0) {
					//		$tmpData = substr(" " . $Feld["Bemerkung"], strpos(" " . $Feld["Bemerkung"], "Update von Jason ")+17, 10);
					//	} elseif (strpos(" " . $Feld["Bemerkung"], "Neu")!=0) {
					//		$tmpData = substr(" " . $Feld["Bemerkung"], strpos(" " . $Feld["Bemerkung"], "Neu ")+4, 10);
					//	} else {
					//		$tmpData = "01.04.20" . substr($Feld["SuchfeldBild"], -6,2);
					//	}
					//	$setcontent .= XMLField("Aenderung", $tmpData, 2);
					//} else {
						$setcontent .= XMLField("Update", $Feld["Update"] ?? "", 2);
					//}
					$setcontent .= XMLField("Select", $Feld["Select"] ?? "", 2);
					$setcontent .= XMLSetEnd("Feld", 0);
					$setcontent .= chr(10). chr(10). chr(10);
					//echo $setcontent . "<br>";
					$filecontent .= $setcontent;
					$i++;
				}
			}
		$changes++;
		}
		$filecontent .= XMLFooter("Felder");
		
		//echo "Safe " . $_GET["DoSafe"] . "#Entry: " . $i . " #Changes:" . $changes . "<br>";
		//echo "output: <br><br>" . $filecontent;
		$filepath = $XMLLocation . $RKRKFelderfileXML;
		if (function_exists('rkrk_xml_to_string')) {
			$filecontent = rkrk_xml_to_string($filecontent);
		}
		if (function_exists('rkrk_write_utf8_file')) {
			rkrk_write_utf8_file($filepath, $filecontent);
		} else {
			file_put_contents($filepath, $filecontent, LOCK_EX);
		}
		if (function_exists('rkrk_feldkatalog_save_json')) {
			rkrk_feldkatalog_save_json($filepath, $Feldkatalog);
		}
	}
}	

Function SaveFlugkatalog2CSV ($Feldkatalog, $CSVLocation="") {
	global $csvMissionLocation, $feldkatalogCsvLocation, $RKRKFelderfileCSV, $historyfilelocation, $FlugzeitTreshold, $csv_desc, $csv_desc_replace;
	

	//$dosafe= true;
	$changes= 1;
	$i= 1;
	if ($CSVLocation=="") {
		if (!empty($feldkatalogCsvLocation)) {
			$CSVLocation=$feldkatalogCsvLocation;
		} else {
			$CSVLocation=$csvMissionLocation;
		}
	}

	
	if (true) {
		//echo "do save <br>";
		$filecontent = "";
		$OutputArray =Array();
		foreach ($Feldkatalog as $Key => $Region) {
			if (!is_array($Region)) {
				continue;
			}
			foreach ($Region as $Key => $Feld) {
				if (!is_array($Feld)) {
					continue;
				}
				$feldId = (isset($Feld["FeldID"]) && !is_array($Feld["FeldID"])) ? (string)$Feld["FeldID"] : '';
				$csvText = function ($v) use ($csv_desc, $csv_desc_replace) {
					$text = function_exists('rkrk_text_utf8') ? rkrk_text_utf8($v) : (string) $v;
					return str_replace($csv_desc, $csv_desc_replace, $text);
				};
				$gsDaten = (isset($Feld["Grundstueckdaten"]) && is_array($Feld["Grundstueckdaten"])) ? $Feld["Grundstueckdaten"] : array();
				$flugzeitRaw = $Feld["Flugzeit"] ?? '';
				$OutputArray[$feldId]["ID"] = $feldId."A";
				$OutputArray[$feldId]["JG"] = $Feld["JG"] ?? '';
				$OutputArray[$feldId]["Name"] = $csvText($Feld["Feldname"] ?? '');
				$OutputArray[$feldId]["Flurname"] = $csvText($Feld["Flurname"] ?? '');
				$OutputArray[$feldId]["Grundstück"] = str_replace($csv_desc,$csv_desc_replace, $Feld["GrundstueckID"] ?? '');
				if ($flugzeitRaw !== '' && $flugzeitRaw>$FlugzeitTreshold) {
					// Zeit in Sekunden
					$OutputArray[$feldId]["Flugzeit"] = round(($flugzeitRaw)/60,1);
				} else {
					// Zeit in Minuten
					$OutputArray[$feldId]["Flugzeit"] = $flugzeitRaw;
				}							
				$OutputArray[$feldId]["Fläche"] = $Feld["Flaeche"] ?? '';
				$OutputArray[$feldId]["Wegpunkte"] = $Feld["Wegpunkte"] ?? '';
				$OutputArray[$feldId]["Distanz"] = $Feld["Distanz"] ?? '';
				$OutputArray[$feldId]["Gemeinde"] = $Feld["Gemeinde"] ?? '';
				$OutputArray[$feldId]["Freigabe"] = $Feld["Freigabe"] ?? '';
				$OutputArray[$feldId]["Warnung"] = $Feld["Warnung"] ?? '';
				$OutputArray[$feldId]["Betrieb"] = $Feld["Betrieb"] ?? '';
				$OutputArray[$feldId]["Jäger"] = $Feld["Jaeger"] ?? '';
				$OutputArray[$feldId]["Bemerkung"] = $csvText($Feld["Bemerkung"] ?? '');
				$OutputArray[$feldId]["Notiz"] = $csvText($Feld["Notiz"] ?? '');
				$OutputArray[$feldId]["Flüge"] = $Feld["Flug"] ?? '';
				$OutputArray[$feldId]["Funde"] = $Feld["Funde"] ?? '';
				$OutputArray[$feldId]["Startplatz"] = $gsDaten["Startplatz"] ?? '';
				$OutputArray[$feldId]["Parkplatz"] = $gsDaten["Parkplatz"] ?? '';
				$OutputArray[$feldId]["Zentrum"] = $gsDaten["Zentrum"] ?? '';
				if (($Feld["Export"] ?? '')=="Y") {
					$OutputArray[$feldId]["Export"] = "Ja";
				} else {
					$OutputArray[$feldId]["Export"] = "Nein";
				}
			}
		}
		//pre_R($OutputArray);
		$file = $RKRKFelderfileCSV;
		$newfile = $historyfilelocation . date("YmdH") . "00_" . $RKRKFelderfileCSV;
		//echo "<br>History: " . $file . " to  " . $newfile;
		if (is_file($CSVLocation . $file) && !copy($CSVLocation . $file, $newfile)) {
			$SystemInfo .= "failed to copy $file...\n";
		}
		//echo "CSV save to " .$file . "<br>";
		$prevCsvMissionLocation = $csvMissionLocation;
		$csvMissionLocation = $CSVLocation;
		SaveArrayToCSV ($OutputArray, $file);
		$csvMissionLocation = $prevCsvMissionLocation;
	}
}	


//====================================
// Update Flightfield in Flightkatalog
//====================================

	//Update Flüge und Funde in Katalog 
	function UpdateFlightsAndSaveKatalog($Year = "2024") {
		global $xmlMissionLocation ,$RKRKFelderfileXML, $csvMissionLocation, $RKRKFlightsCSV;
		
		//echo "<br><br> Jahr. " . $Year ;
		
		$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
		$csv_deliminator = ";";

		//Read CSV file
		$csv_file= $Year .$RKRKFlightsCSV;
		$Flightdata = csv_to_array($csvMissionLocation.$csv_file, $csv_deliminator );
		if (!is_array($Flightdata)) {
			$Flightdata = array();
		}
		//echo "Flugdatei: " . $csvMissionLocation.$csv_file . "<br>";
		//pre_r($Flightdata);
		$Flightdata = FormatFlugDataArray($Flightdata);
		//pre_r($Flightdata);
		//pre_r($Feldkatalog);
		//echo "update flights ". $Year . "<br>";
		$dosafe = false;
		foreach ($Flightdata as $key => $flight) {
			$feldId = trim((string)($flight["Feld"] ?? ''));
			if ($feldId === '' || strpos($feldId, '-') === false) {
				continue;
			}
			$jg = explode('-', $feldId, 2)[0];
			if (!isset($Feldkatalog[$jg][$feldId]) || !is_array($Feldkatalog[$jg][$feldId])) {
				continue;
			}
			if 	($flight["Geflogen"]== "Ja") {
				//Flüge
				//echo "FlightDone: " . $key . "  - " . $flight["Feld"] . "<br>";
				//echo "Flights: " . $Feldkatalog[explode("-", $flight["Feld"])[0]][$flight["Feld"]]["Flug"] . "<br>"; 
				$FlightList = str_replace(" ", "", (string)($Feldkatalog[$jg][$feldId]["Flug"] ?? ''));
				//pre_R($FlightListArray);
				if (!strpos(" ".$FlightList, substr($Year,2,2))) {
					// Flight not in list
					$dosafe = true;
					if ($FlightList=="") {
						$FlightList = substr($Year,2,2);
					} else {
						$FlightListArray=explode(",",$FlightList);
						$FlightListArray[]=substr($Year,2,2);
						arsort($FlightListArray);
						//pre_r($FlightListArray);
						$FlightList= implode(",",$FlightListArray);
						//$FlightList .=  "," . substr($Year,2,2);
					}
					//echo "Flight to update: " . $key . "  - " . $flight["Feld"] . " - " . $FlightList . "<br>";
					$Feldkatalog[$jg][$feldId]["Flug"]= $FlightList;
					// set Flugfeld ok, weil schon geflogen.
					//echo "Flight status to update: " . $key . "  - " . $flight["Feld"] . " - " . "OK" . "<br>";
					$Feldkatalog[$jg][$feldId]["Freigabe"]= "OK";
				}
			}
			if 	(($flight["Fund"]> 0) and
				($flight["Geflogen"]== "Ja")) {
				//Funde
				$FoundList = str_replace(" ", "", (string)($Feldkatalog[$jg][$feldId]["Funde"] ?? ''));
				//pre_R($FlightListArray);
				if (!strpos(" ".$FoundList, substr($Year,2,2))) {
					// Flight not in list
					$dosafe = true;
					if ($FoundList=="") {
						$FoundList = substr($Year,2,2);
					} else {
						$FoundListArray=explode(",", $FoundList);
						$FoundListArray[]=substr($Year,2,2);
						arsort($FoundListArray);
						$FoundList= implode(",",$FoundListArray);
						//$FoundList .= "," . substr($Year,2,2);
					}
					//echo "Funde to update: " . $key . "  - " . $flight["Feld"] . " - " . $FoundList . "<br>";
					$Feldkatalog[$jg][$feldId]["Funde"]= $FoundList;
				}
			}	
		}
		//pre_r($Feldkatalog[explode("-", $flight["Feld"])[0]][$flight["Feld"]]);
		if ($dosafe) {
			//echo "save Flugkatalog" . chr(10);
			SaveFlugkatalog2XML ($Feldkatalog, true);
			SaveFlugkatalog2CSV ($Feldkatalog);
		}
	}

Function GetFlugplanbild($FlugplanFiles,$StandardFlughöhe) {
	$cleanFiles = array();
	foreach ((array)$FlugplanFiles as $one) {
		$s = function_exists('rkrk_str') ? rkrk_str($one) : (is_array($one) || is_object($one) ? '' : (string)($one ?? ''));
		$s = trim($s);
		if ($s !== '') {
			$cleanFiles[] = $s;
		}
	}
	$FlugplanFiles = array_values($cleanFiles);
	if (count($FlugplanFiles) === 0) {
		return '';
	}
	$SelPic = $FlugplanFiles[0];
	$std = trim((string)$StandardFlughöhe);
	foreach ($FlugplanFiles as $Bild) {
		if ($std !== '' && strpos($Bild, "_".$std."_") !== false) {
			$SelPic = $Bild;
		}
	}
	return $SelPic;
}	

?>
