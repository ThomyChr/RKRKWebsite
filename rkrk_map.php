<?php
	// Embed: Output puffern, am Ende nur geo.admin laden (kein Nested-Iframe).
	$__rkrkMapEmbedEarly = isset($_GET['Embed']) && (trim((string)$_GET['Embed']) === '1' || strtolower(trim((string)$_GET['Embed'])) === 'on');
	if ($__rkrkMapEmbedEarly) {
		ob_start();
	}

	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_geofence.php');
	include_once('scripts/rkrk_map_functions.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('rkrk_access_control.php'); 

	CheckIPandExit();

	$Layer = true;
	$DebugWork = false;
	$qrcode1 = '';
	$qrcode2 = '';
	$MAP = GetURLwDefault('MAP', 'MAP', '', '');
	$MapEast = '';
	$MapNorth = '';
	$MapZoom = '';
	$BBoxEast = '';
	$BBoxNorth = '';
	$BBoxZoom = '';

	$MenuOn = GetURLtoFalseTrue('MenuOn', 'MenuOn', '', true);
	$TopMenuOn = GetURLtoFalseTrue('Top', 'Top', '', true);
$isEmbeddedMap = isset($_GET['Embed']) && (trim((string)$_GET['Embed']) === '1' || strtolower(trim((string)$_GET['Embed'])) === 'on');
if ($isEmbeddedMap) {
	$MenuOn = false;
}

	headerpage('Karten Drohnenrehkitzrettung', false);
GrantAccess();

SetMissionLocation();
	if (function_exists('rkrk_mission_location_dir_exists')
		&& isset($MissionDroneTypes[$RKRKMissionSelected]['MissionLocation'])
		&& !rkrk_mission_location_dir_exists($MissionDroneTypes[$RKRKMissionSelected]['MissionLocation'])
	) {
		$RKRKMissionSelected = '3T';
		if (isset($SessNme_MissionDBSetting)) {
			$storedMission = $_SESSION[$SessNme_MissionDBSetting] ?? null;
			if (!isset($_SESSION[$SessNme_MissionDBSetting]) || is_string($storedMission)) {
				$_SESSION[$SessNme_MissionDBSetting] = '3T';
			}
		}
		if (isset($_GET['MDB'])) {
			$_GET['MDB'] = '3T';
		}
		if (isset($SecParamters) && is_array($SecParamters)) {
			$SecParamters['MDB'] = '3T';
		}
		SetMissionLocation();
	}
	$MissionSelectionParameter = '&MDB=' . $RKRKMissionSelected;

	$EXselect = GetURLwDefault_Lower('EX', 'EX', 'EXselect', 'no');
	if ($EXselect == 'no') {
		$EXselect = '';
		$EX = 'no';
} else {
		$EXselect = 'on';
		$EX = '';
	}

	$SelectedZoom = GetURLwDefault('Zoom', 'Zoom', 'SelectedZoom', 1.0);
	$DebugWork = GetURLwDefault_Lower('DBW', 'DBW', '', '');
	$Overview = GetURLwDefault_Lower('OVR', 'OVR', '', 'off');
	$Flugplan = GetURLwDefault_Lower('FLP', 'FLP', '', 'off');
	$MapZoomOut = GetURLwDefault('MapZoomOut', 'MapZoomOut', '', '1');
	$SingleColor = GetURLwDefault_Lower('SCOL', 'SCOL', '', 'off');
	if ($SingleColor == 'on') {
		$SingleColor = true;
		$SColParameter = '&SCOL=on';
} else {
	$SingleColor = false;
		$SColParameter = '';
	}
	$FlightZoneOptions = GetURLwDefault('FZO', 'FZO', '', '');
	$MapButtons = GetURLwDefault_Lower('MapButtons', 'MapButtons', '', 'on');
	$latIn = GetURLwDefault('LAT', 'LAT', '', '');
	$lonIn = GetURLwDefault('LON', 'LON', '', '');
	$zoIn = GetURLwDefault('ZO', 'ZO', '', '');

	WriteToLog('get', basename($_SERVER['PHP_SELF']), 'öffnen ', $RKRKMissionSelected, '', '');

if (!isset($EnabledRegion) || !is_array($EnabledRegion)) {
	$EnabledRegion = array();
}
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation . $RKRKFelderfileXML);
if (!is_array($Feldkatalog)) {
	$Feldkatalog = array();
}

	$h = function ($s) {
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	};

	$GetRegions = array();
	if ($SecUrl && isset($SecParamters['Reg']) && $SecParamters['Reg'] <> '') {
		$rawRegs = multiexplode(array(' ', ','), $SecParamters['Reg']);
	} elseif (isset($_GET['Reg'])) {
		$rawRegs = multiexplode(array(' ', ','), $_GET['Reg']);
	} else {
		$rawRegs = array();
	}
	foreach ($rawRegs as $Region) {
		$Region = trim((string)$Region);
		if ($Region !== '') {
			$GetRegions[$Region] = $Region;
		}
}
$selectAllRegions = false;
$selectEmptyRegion = false;
foreach ($GetRegions as $rgKey => $rgVal) {
	$rg = trim((string)$rgKey);
	if (strcasecmp($rg, 'ALL') === 0 || strcasecmp($rg, 'alle') === 0) {
		$selectAllRegions = true;
	}
	if (strcasecmp($rg, 'leer') === 0 || strcasecmp($rg, 'NO') === 0) {
		$selectEmptyRegion = true;
	}
}
if ($GetRegions === array()) {
	$selectAllRegions = true;
}
if ($selectAllRegions) {
	$selectEmptyRegion = false;
}

	$ArrayOfFields = array();
$RegSelectedList = array();
$RegOptions = array();
$RegOptions['alle'] = array('Text' => 'alle', 'Value' => $selectAllRegions, 'exclusive' => true);
$RegOptions['leer'] = array('Text' => 'leer', 'Value' => $selectEmptyRegion, 'exclusive' => true);
foreach ($RegionSettings as $Reg) {
	if (empty($Reg['Aktiv'])) {
		continue;
	}
	$key = (string)$Reg['Reg'];
		if ($key === '' || strcasecmp($key, 'leer') === 0 || strcasecmp($key, 'ALL') === 0 || strcasecmp($key, 'alle') === 0 || strcasecmp($key, 'NO') === 0) {
		continue;
	}
	$RegOptions[$key] = array('Text' => $key, 'Value' => false, 'exclusive' => false);
	$isOn = !$selectAllRegions && !$selectEmptyRegion && (($GetRegions[$key] ?? '') !== '');
	if ($isOn) {
		$RegOptions[$key]['Value'] = true;
		$RegSelectedList[] = $key;
		if (isset($Feldkatalog[$key]) && is_array($Feldkatalog[$key])) {
			$ArrayOfFields = array_merge($ArrayOfFields, array_keys($Feldkatalog[$key]));
		}
	}
}
if ($selectAllRegions) {
	$RegSelectedList = array('ALL');
	foreach ($RegionSettings as $Reg) {
		if (empty($Reg['Aktiv'])) {
			continue;
		}
		$key = (string)$Reg['Reg'];
			if ($key === '' || strcasecmp($key, 'leer') === 0 || strcasecmp($key, 'ALL') === 0 || strcasecmp($key, 'alle') === 0 || strcasecmp($key, 'NO') === 0) {
			continue;
		}
		if (isset($Feldkatalog[$key]) && is_array($Feldkatalog[$key])) {
			$ArrayOfFields = array_merge($ArrayOfFields, array_keys($Feldkatalog[$key]));
		}
	}
} elseif ($selectEmptyRegion) {
	$RegSelectedList = array('leer');
	$ArrayOfFields = array();
}
	$RegSelected = implode(', ', $RegSelectedList);
	$ListOfFields = implode(', ', $ArrayOfFields);
	if ($RegSelected == '') {
		$RegSelected = 'alle';
	}
	$selectedJgKeys = array();
	foreach ($RegSelectedList as $rk) {
		$rk = trim((string)$rk);
		if ($rk === '' || strcasecmp($rk, 'leer') === 0 || strcasecmp($rk, 'ALL') === 0 || strcasecmp($rk, 'alle') === 0 || strcasecmp($rk, 'NO') === 0) {
			continue;
		}
		$selectedJgKeys[] = $rk;
	}
	$singleJgOpen = count($selectedJgKeys) === 1;
	$multiJgOpen = $selectAllRegions || count($selectedJgKeys) > 1;

	$Flightday = GetURLwDefault('FDY', 'FDY', '', '');
	$DayFieldList = '';
	if ($Flightday <> '') {
		$csv_file = date('Y', strtotime($Flightday)) . $RKRKFlightsCSV;
		$csv_deliminator = ';';
	transferGoogleSheetToCSV();
		$data = csv_to_array($csvDocLocation . $csv_file, $csv_deliminator);
		$DayFieldListArr = array();
		if (is_array($data)) {
	foreach ($data as $flight) {
				if (($flight['Datum'] ?? '') != $Flightday) {
					continue;
				}
				$flightJg = trim((string)($flight['JG'] ?? $flight['Reg'] ?? ''));
				$inReg = $selectAllRegions || ($RegSelected == 'alle');
				if (!$inReg && $flightJg !== '') {
					foreach ($selectedJgKeys as $rk) {
						if (strcasecmp((string)$rk, $flightJg) === 0) {
							$inReg = true;
							break;
						}
					}
					if (!$inReg) {
						$hay = ' ' . str_replace(array(',', ';'), ' ', (string)$RegSelected) . ' ';
						if (strpos($hay, ' ' . $flightJg . ' ') !== false) {
							$inReg = true;
						}
					}
				}
				if ($inReg) {
					$feld = trim((string)($flight['Feld'] ?? $flight['FeldID'] ?? ''));
					if ($feld !== '') {
						$DayFieldListArr[] = $feld;
					}
				}
			}
		}
		$DayFieldList = implode(' ', $DayFieldListArr);
		if ($DayFieldList == '') {
			$DayFieldList = 'keine';
		}
	}

if ($isEmbeddedMap) {
	$MenuOn = false;
}
	$FFilter = GetURLwDefault('FFilter', 'FFilter', 'FFilter', '');
	if ($FFilter == '' && $DayFieldList !== '' && strcasecmp($DayFieldList, 'keine') !== 0) {
	$FFilter = $DayFieldList;
}
	$Suche = GetURLwDefault('Suche', 'Suche', '', '');
	$MapType = GetURLwDefault('KA', 'KA', 'MapType', 'S');

	$MapOptions = array();
	$MapParameter = '';
	$MinimalMapParameter = '';
	foreach ($MapSettings['Options'] as $Key => $Opt) {
		if (empty($Opt['Aktiv']) || !CheckAccess($Opt['Access'])) {
			continue;
		}
		$MapOptions[$Key]['Text'] = $Opt['Text'];
		if (isset($_GET[$Key])) {
			$MapOptions[$Key]['Value'] = ($_GET[$Key] == 'on');
		} elseif ($SecUrl && isset($SecParamters[$Key]) && $SecParamters[$Key] <> '') {
			$MapOptions[$Key]['Value'] = ($SecParamters[$Key] == 'on');
			} else {
			$MapOptions[$Key]['Value'] = !empty($Opt['Value']);
		}
		$onOff = explode(',', $Opt['Options']);
		if ($MapOptions[$Key]['Value']) {
			$MapParameter .= '&' . $Key . '=' . $onOff[0];
			$MinimalMapParameter .= '&' . $Key . '=' . $onOff[0];
			} else {
			$MapParameter .= '&' . $Key . '=' . $onOff[1];
		}
	}

	$SpecialOptions = $SColParameter;
	if ($FlightZoneOptions <> '') {
		$SpecialOptions .= '&FZO=' . $FlightZoneOptions;
	}
	if ($Flugplan == 'on') {
		$SpecialOptions .= '&FLP=on';
	}
	if ($Overview == 'on') {
		$SpecialOptions .= '&OVR=on';
	}
	$Otherparameter = '&EX=' . $EX . '&MAP=' . $MAP . '&FFilter=' . $FFilter . $SpecialOptions;

if ($selectAllRegions) {
	$RegionSelected = 'ALL';
} elseif ($selectEmptyRegion) {
	$RegionSelected = 'leer';
} else {
	$RegionSelected = implode(' ', $RegSelectedList);
	if ($RegionSelected === '') {
		$RegionSelected = 'leer';
	}
}

	$MissionSelectionParameter = '&MDB=' . $RKRKMissionSelected;
	if (!function_exists('ref_layers_alert_kml_catalog')) {
		include_once(__DIR__ . '/scripts/rkrk_ref_layers_lib.php');
	}
	$AlertKmlSelected = array();
	if (function_exists('ref_layers_alert_kml_selection')) {
		$AlertKmlSelected = ref_layers_alert_kml_selection(GetURLwDefault('AlertKml', 'AlertKml', '', ''));
	}
	$AlertKmlParameter = '';
	if ($AlertKmlSelected !== array()) {
		$AlertKmlParameter = '&AlertKml=' . implode(',', $AlertKmlSelected);
	}
	$alertDateRaw = (string)GetURLwDefault('Stichtag', 'Stichtag', '', '');
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $alertDateRaw)) {
		$alertDateRaw = (string)GetURLwDefault('AlertDate', 'AlertDate', '', '');
	}
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $alertDateRaw)) {
		$AlertKmlParameter .= '&Stichtag=' . $alertDateRaw . '&AlertDate=' . $alertDateRaw;
	}
	$ParameterSet = 'Reg=' . $RegionSelected . $MapParameter . '&KA=' . $MapType . '&Zoom=' . $SelectedZoom . '&FFilter=' . $FFilter . '&SFilter=' . $Suche . $SpecialOptions . $MissionSelectionParameter . $GuestAccessParamter . $AlertKmlParameter;
	$MinimalMapParameter = 'Reg=' . $RegionSelected . $MinimalMapParameter . '&KA=' . $MapType . '&Zoom=' . $SelectedZoom . '&FFilter=' . $FFilter . '&SFilter=' . $Suche . $SpecialOptions . $MissionSelectionParameter . $GuestAccessParamter;
	if (GetURLtoFalseTrue('AreaOnly', 'AreaOnly')) {
		$areaIdsPass = trim((string)GetURLwDefault('AreaIds', 'AreaIds', '', ''));
		$areaIdsPass = preg_replace('/[\s,;+|]+/', '+', $areaIdsPass);
		$areaIdsPass = trim((string)$areaIdsPass, '+');
		$ParameterSet .= '&AreaOnly=on&AreaIds=' . $areaIdsPass;
		$MinimalMapParameter .= '&AreaOnly=on&AreaIds=' . $areaIdsPass;
	}

KartenSettings($MapType, $KaType, $KaLayer, $KaVisLayer, $KaOpacity);

$geoweblocation = dirname($ThisPage);
	if (isset($ThisPageDirectory) && trim((string)$ThisPageDirectory) !== '') {
		$geoweblocation = rtrim((string)$ThisPageDirectory, '/');
	}
	if ($geoweblocation === '' || $geoweblocation === '.' || strpos($geoweblocation, 'http') !== 0) {
		$__con = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$__host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
		$__dir = str_replace('\\', '/', dirname((string)($_SERVER['PHP_SELF'] ?? '')));
		$geoweblocation = rtrim($__con . $__host . $__dir, '/');
	}
	$URLSearch = array(' ', '|', '=', '&');
	$URLReplace = array('%20', '%7C', '%3D', '%26');

	if ($MapNorth == '' && $MapEast == '') {
		if ((strpos($RegionSelected, ' ') == false)
			&& ($RegionSelected != 'alle')
			&& ($RegionSelected != 'leer')
			&& isset($RegionSettings[$RegionSelected]['GeoMapSetting'])
		) {
			$MapEast = $RegionSettings[$RegionSelected]['GeoMapSetting']['East'];
			$MapNorth = $RegionSettings[$RegionSelected]['GeoMapSetting']['North'];
			$MapZoom = $RegionSettings[$RegionSelected]['GeoMapSetting']['Zoom'];
		} else {
			if (($RegionSelected == 'alle') || ($RegionSelected == 'leer') || ($RegionSelected == 'ALL')) {
				$MapEast = $RegionSettings['ALL']['GeoMapSetting']['East'];
				$MapNorth = $RegionSettings['ALL']['GeoMapSetting']['North'];
				$MapZoom = $RegionSettings['ALL']['GeoMapSetting']['Zoom'];
			} else {
			$FieldDiameter = DiameterOfFieldList($ArrayOfFields);
				$Centerpoint = explode(',', $FieldDiameter);
				$Centerpoint['Lon'] = $Centerpoint[0];
				$Centerpoint['Lat'] = $Centerpoint[1];
				unset($Centerpoint[0], $Centerpoint[1]);
			$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
				$MapEast = $MidPointFromFFilter['LV95East'];
				$MapNorth = $MidPointFromFFilter['LV95North'];
			$ZoomFactor = 22;
			$ZoomPower = -0.14;
				$MapZoom = $ZoomFactor * pow($Centerpoint[3], $ZoomPower);
			}
		}
		if ($singleJgOpen && trim((string)$FFilter) === '' && $Flightday === '') {
			// Einzel-JG: GeoMapSetting-Vorgabe behalten
		} elseif ((($FFilter != '') || ($ListOfFields != '')) && ($Flightday == '')) {
			$FFilterNorm = str_replace(array('%2B', '%2b', '_'), array('+', '+', ' '), (string)$FFilter);
			$FFilterentries = preg_split('/[\s,;+|]+/', $FFilterNorm, -1, PREG_SPLIT_NO_EMPTY);
			$FFilterentries = array_values(array_unique(array_filter(array_map('trim', $FFilterentries), function ($v) {
				return $v !== '' && preg_match('/^[A-Za-z0-9]{1,6}-\d{3}$/', $v);
			})));
			if (count($FFilterentries) === 0) {
				$FFilterentries = preg_split('/[\s,;+|]+/', $FFilterNorm, -1, PREG_SPLIT_NO_EMPTY);
				$FFilterentries = array_values(array_filter(array_map('trim', $FFilterentries), function ($v) { return $v !== ''; }));
			}
			$FocusEntries = $FFilterentries;
			$FirstFileInFFilter = $FocusEntries[0] ?? '';
		$CountFilterEntries = count($FocusEntries);
			if ($FirstFileInFFilter != '') {
				$Centerpoint = explode(',', DiameterOfFieldList($FocusEntries));
				$Centerpoint['Lon'] = $Centerpoint[0];
				$Centerpoint['Lat'] = $Centerpoint[1];
				unset($Centerpoint[0], $Centerpoint[1]);
			$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
				$MapEast = $MidPointFromFFilter['LV95East'];
				$MapNorth = $MidPointFromFFilter['LV95North'];
			$ZoomFactor = 22;
			$ZoomPower = -0.13;
				if ($CountFilterEntries != 1) {
					$MapZoom = $ZoomFactor * pow($Centerpoint[3], $ZoomPower);
			} else {
					if ($Overview == 'off') {
					$FieldDiameter = DiameterOfField($FirstFileInFFilter);
						$MapZoom = $ZoomFactor * pow($FieldDiameter, $ZoomPower);
				} else {
						$MapZoom = 9.5;
					}
				}
			}
		}
		if (($MapEast < 0) || ($MapNorth < 0)) {
			$MapEast = $RegionSettings['ALL']['GeoMapSetting']['East'];
			$MapNorth = $RegionSettings['ALL']['GeoMapSetting']['North'];
			$MapZoom = $RegionSettings['ALL']['GeoMapSetting']['Zoom'];
		}
	}

	$WebAccessParamter = '&WebAccess=' . $WebFreigabe;
	$ZoomParamter = '&z=' . (is_numeric($MapZoom) && is_numeric($MapZoomOut) && floatval($MapZoomOut) != 0
		? ($MapZoom / $MapZoomOut)
		: 6);
	if ($MapEast === '' || $MapEast === null || $MapNorth === '' || $MapNorth === null) {
		$MapEast = $RegionSettings['ALL']['GeoMapSetting']['East'] ?? 2678000;
		$MapNorth = $RegionSettings['ALL']['GeoMapSetting']['North'] ?? 1232000;
	}
	$Centering = 'center=' . $MapEast . ',' . $MapNorth;

	$kmlQuery = $ParameterSet . $WebAccessParamter;
$UseKmlFile = '';
if (isset($_GET['UseKmlFile']) && trim((string)$_GET['UseKmlFile']) !== '') {
	$UseKmlFile = basename(preg_replace('/\.kml$/i', '', trim((string)$_GET['UseKmlFile'])));
} elseif (isset($_GET['UseFile']) && trim((string)$_GET['UseFile']) !== '') {
	$UseKmlFile = basename(preg_replace('/\.kml$/i', '', trim((string)$_GET['UseFile'])));
}
if ($UseKmlFile !== '') {
		$kmlQuery = 'UseFile=' . $UseKmlFile . $MissionSelectionParameter . $WebAccessParamter;
	}
	$kmlQueryEncoded = str_replace($URLSearch, $URLReplace, $kmlQuery);
	if ($UseSecUrl) {
		$DoKMLURL = 'rkrk_dokml.php?' . base64_encode($kmlQueryEncoded);
} else {
		$DoKMLURL = 'rkrk_dokml.php?' . $kmlQueryEncoded;
	}
	$KmlMapUrl = 'rkrk_dokml.php?' . $kmlQuery;

	$Search = '';
	$kmlAbsolute = rtrim((string)$geoweblocation, '/') . '/' . $DoKMLURL;
	$kmlLayerToken = 'KML%7C' . str_replace(
		array('|', '?', '&', '#'),
		array('%7C', '%3F', '%26', '%23'),
		$kmlAbsolute
	);
	$layerPrefix = (trim((string)$KaLayer) === '') ? '' : ($KaLayer . ';');
	$FullMapURL = $MapGeoAdminURL . $Search . 'lang=de&topic=ech&' . $Centering . $ZoomParamter . '&bgLayer=' . $KaType . '&layers=' . $layerPrefix . $kmlLayerToken;

if (!empty($isEmbeddedMap)) {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-store');
	}
	$mapJson = json_encode((string)$FullMapURL, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<title>Karte</title>';
	echo '<style>html,body{margin:0;height:100%;overflow:hidden;background:#cfd5dc;font:14px/1.4 sans-serif;color:#334}</style>';
	echo '<script>try{location.replace(' . $mapJson . ');}catch(e){document.write(' . json_encode('<p>Karte konnte nicht geladen werden.</p>', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');}</script>';
		echo '<noscript><meta http-equiv="refresh" content="0;url=' . $h($FullMapURL) . '">';
		echo '<p style="padding:12px"><a href="' . $h($FullMapURL) . '">Karte öffnen</a></p></noscript>';
	echo '</head><body><p style="padding:12px">Karte wird geladen …</p></body></html>';
	exit;
}

	$mapLon = 8.45;
	$mapLat = 47.22;
	$hasFieldFilter = trim((string)$FFilter) !== '';
	$egOn = !empty($MapOptions['EG']['Value']);
	$fitOn = false;
	$fitRaw = '';
	if (isset($_GET['Fit'])) {
		$fitRaw = strtolower(trim((string)$_GET['Fit']));
	} elseif ($SecUrl && isset($SecParamters['Fit'])) {
		$fitRaw = strtolower(trim((string)$SecParamters['Fit']));
	}
	$fitOn = in_array($fitRaw, array('on', '1', 'true', 'yes'), true);
	$fitExtent = $hasFieldFilter || $egOn || $fitOn || ($multiJgOpen && $Flightday === '');
	$fitKeepRatio = ($hasFieldFilter || $egOn) ? 1 : 0.95;
	$fitTarget = $egOn ? 'polygons' : 'fields';
	$keepCurrentView = isset($_GET['CV']) && trim((string)$_GET['CV']) === '1';
	$scaleDenom = 0;
	if (isset($_GET['SCL']) && is_numeric($_GET['SCL'])) {
		$scaleN = (int)$_GET['SCL'];
		if ($scaleN === 10000 || $scaleN === 25000 || $scaleN === 50000) {
			$scaleDenom = $scaleN;
		}
	}
	if ($keepCurrentView) {
		$fitExtent = false;
	} elseif ($fitExtent) {
		$latIn = '';
		$lonIn = '';
		$zoIn = '';
	}
	if ($latIn !== '' && $lonIn !== '' && is_numeric($latIn) && is_numeric($lonIn)) {
		$mapLat = (float)$latIn;
		$mapLon = (float)$lonIn;
	} elseif (function_exists('fromMN95ToWGS')) {
		$wgs = fromMN95ToWGS((int)round((float)$MapEast), (int)round((float)$MapNorth));
		$mapLat = (float)($wgs['WGSlat'] ?? $mapLat);
		$mapLon = (float)($wgs['WGSon'] ?? $mapLon);
	}
	$mapZoomGeo = is_numeric($MapZoom) && is_numeric($MapZoomOut) && floatval($MapZoomOut) != 0
		? ((float)$MapZoom / (float)$MapZoomOut)
		: 6;
	if ($zoIn !== '' && is_numeric($zoIn)) {
		$mapZoomGeo = (float)$zoIn;
	}

	$selfFile = basename((string)($_SERVER['PHP_SELF'] ?? 'rkrk_map.php'));
	$QRCode = $WebLink_public . $selfFile . '?' . str_replace(array('&', ' ', '&EX=NO', '&EX=no'), array('%26', '%2B', '', ''), $ParameterSet);
	$qrcode1 .= '<img src="https://api.qrserver.com/v1/create-qr-code/?color=000000&amp;bgcolor=FFFFFF&amp;data=' . $h($QRCode) . '&amp;qzone=1&amp;margin=0&amp;size=300x300&amp;ecc=M" alt="QR-Code" title="Link auf Map Ansicht"/>';

	$keepKeys = array('MDB', 'GA', 'Color', 'MenuOn', 'Top', 'Side', 'MapButtons', 'FDY', 'FZO', 'OVR', 'FLP', 'SCOL', 'MAP', 'MapZoomOut', 'Suche', 'UseKmlFile', 'UseFile', 'Stichtag', 'AlertDate', 'AlertKml');
	$mapMdbAdd = '';
	$mapQs = (string)($_SERVER['QUERY_STRING'] ?? '');
	if ($mapQs !== '') {
		$mapQsKeep = array();
		foreach (explode('&', $mapQs) as $mapQsPart) {
			if ($mapQsPart === '' || stripos($mapQsPart, 'MDB=') === 0) {
				continue;
			}
			$mapQsKeep[] = $mapQsPart;
		}
		if ($mapQsKeep !== array()) {
			$mapMdbAdd = '&' . implode('&', $mapQsKeep);
		}
	}
	$mapMdbName = (string)($MissionDroneTypes[$RKRKMissionSelected]['Name'] ?? $RKRKMissionSelected);
	$internOk = ($EX == 'no') && GetAccess('InternPilots');
	$legendHtml = '';
	if ((!$SingleColor) && $MenuOn) {
		$legendHtml = mapcolor_legend($EX == 'no', round((float)$SelectedZoom * 50) . '%');
		$legendHtml .= '<br>' . region_legend(round((float)$SelectedZoom * 50) . '%', str_replace(' ', ',', $RegionSelected));
	}

	$overlayRows = array(
		array('electrical', 'Elektrische Anlagen', 100),
		array('boundaries', 'Kantons, Gemeindegrenzen', 100),
		array('hunting', 'Jagdbanngebiete AuLaV', 50, 'Jagdbanngebiete AuLaV (Swisstopo/BAFU)'),
		array('rpas', 'RPAS Karte', 50),
		array('uasZones', 'UAS Aktivitätszonen', 50),
		array('aulavWohngebiete', 'Wohngebiete nach AuLaV', 50, 'Wohngebiete nach AuLaV (BAZL, standard aus)'),
		array('alti3dRelief', 'swissALTI3D Relief', 70, 'swissALTI3D multidirektionales Relief (standard aus)'),
		array('surface3dRelief', 'swissSURFACE3D Relief', 70, 'swissSURFACE3D multidirektionales Relief (standard aus)'),
		array('dabs', 'DABS bis 200 m AGL', 75),
		array('obstacles', 'Luftfahrthindernisse', 100),
		array('landregister', 'Grundbuchinformationen', 100),
		array('cadastralwebmap', 'CadastralWebMap', 100),
		array('nature', 'Naturschutzgebiete', 100),
		array('roadnames', 'Strassennamen', 100),
		array('cableways', 'Seilbahnen', 100),
		array('wanderwege', 'Wanderwege', 100, 'SwissTLM3D Wanderwege (Swisstopo)'),
	);

	if ($MenuOn) {
		echo '<script>document.documentElement.classList.add("rkrk-map27");</script>';
		if ($TopMenuOn) {
		CreateTopMenu();
		echo "<style>#navDemo{display:none !important;}#navDemo.show{display:block !important;}</style>";
		echo "<script>
	(function(){
	  window.addEventListener('DOMContentLoaded', function(){
		var navBars = document.querySelectorAll('div.top > div.bar.black.card');
		if (navBars.length > 1) {
		  for (var i = 1; i < navBars.length; i++) {
			var wrap = navBars[i].parentElement;
			if (wrap) wrap.remove();
		  }
		}
	  });
	})();
	</script>";
		}
	}

	echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />' . "\n";
	if ($MenuOn) {
		echo '<link rel="stylesheet" href="fe/rkrk_layout.css?v=20260905narrow" />' . "\n";
		echo '<link rel="stylesheet" href="template/rkrk_reg_pills.css?v=20260907nodbl" />' . "\n";
	}
	echo '<link rel="stylesheet" href="template/rkrk_map.css?v=20261013picker" />' . "\n";
	echo '<script>document.documentElement.classList.add(' . ($MenuOn ? '"rkrk-map27"' : '"rkrk-map27","rkrk-map27-embed"') . ');';
	if (!$MenuOn) {
		echo 'document.documentElement.setAttribute("data-rkrk-layout","off");';
	}
	echo '</script>' . "\n";
	echo '<div id="debuginfo" name="debuginfo"></div>' . "\n";
?>
<div class="wrap rkrk-map27-wrap">
  <div class="left rkrk-map27-panel" id="map27LeftPanel">
    <form id="map27Form" method="get" action="<?php echo $h($selfFile); ?>">
      <?php
	foreach ($keepKeys as $k) {
		if (!isset($_GET[$k])) {
			continue;
		}
		$v = trim((string)$_GET[$k]);
		if ($v === '') {
			continue;
		}
		echo '<input type="hidden" data-keep="1" name="' . $h($k) . '" value="' . $h($v) . '">' . "\n";
	}
	if (!isset($_GET['MDB']) && isset($RKRKMissionSelected) && $RKRKMissionSelected !== '') {
		echo '<input type="hidden" data-keep="1" name="MDB" value="' . $h($RKRKMissionSelected) . '">' . "\n";
	}
      ?>
      <div class="rkrk-map27-brand">
        <div>
          <h1>Flugfelder</h1>
          <div class="sub">Karte 27 · SwissTopo + Felder-KML</div>
        </div>
      </div>
      <details open>
        <summary>Missionskatalog</summary>
        <div class="panel-body">
          <?php echo MissionLibraryButtons($mapMdbAdd, false); ?>
          <div class="sub"><?php echo $h($mapMdbName); ?></div>
        </div>
      </details>

      <details open>
        <summary>Regionen</summary>
        <div class="panel-body rkrk-sels rkrk-reg-pills">
          <?php
	foreach ($RegOptions as $key => $opt) {
		$checked = !empty($opt['Value']) ? ' checked' : '';
		$cls = !empty($opt['exclusive']) ? 'rkrk-sel rkrk-reg-pill rkrk-sel-exclusive rkrk-reg-pill-exclusive' : 'rkrk-sel rkrk-reg-pill';
		echo '<label class="' . $cls . '"><input type="checkbox" name="RegSel[]" value="' . $h($key) . '"' . $checked . '> ' . $h($opt['Text']) . '</label>';
	}
          ?>
        </div>
      </details>

      <details open>
        <summary>Kartengrund</summary>
        <div class="panel-body rkrk-sels rkrk-reg-pills">
          <?php
	foreach ($MapSettings['Types'] as $key => $map) {
		if (empty($map['Aktiv'])) {
			continue;
		}
		$checked = ($MapType == $key) ? ' checked' : '';
		$label = (string)($map['Text'] ?? $key);
		$icon = (string)($map['IconOn'] ?? '');
		$iconHtml = $icon !== '' ? '<img src="' . $h($IconLoc . '/' . $icon) . '" alt="">' : '';
		$cls = 'rkrk-sel rkrk-reg-pill' . ($iconHtml !== '' ? ' rkrk-sel-icononly' : '');
		echo '<label class="' . $cls . '" aria-label="' . $h($label) . '">' . $iconHtml . '<input type="radio" name="KA" value="' . $h($key) . '"' . $checked . '><span class="rkrk-sel-text"> ' . $h($label) . '</span></label>';
	}
          ?>
        </div>
      </details>

      <details open>
        <summary>Felder / KML</summary>
        <div class="panel-body rkrk-sels rkrk-reg-pills">
          <?php
	foreach ($MapOptions as $key => $opt) {
		$checked = !empty($opt['Value']) ? ' checked' : '';
		$label = (string)($opt['Text'] ?? $key);
		$icon = $MapSettings['Options'][$key]['IconOn'] ?? '';
		$iconHtml = $icon !== '' ? '<img src="' . $h($IconLoc . '/' . $icon) . '" alt="">' : '';
		$cls = 'rkrk-sel rkrk-reg-pill' . ($iconHtml !== '' ? ' rkrk-sel-icononly' : '');
		echo '<label class="' . $cls . '" aria-label="' . $h($label) . '">' . $iconHtml . '<input type="checkbox" data-map-opt="1" data-opt-key="' . $h($key) . '"' . $checked . '><span class="rkrk-sel-text"> ' . $h($label) . '</span></label>';
	}
          ?>
        </div>
      </details>

      <details open>
        <summary>SwissTopo Ebenen</summary>
        <div class="panel-body">
          <?php
	foreach ($overlayRows as $row) {
		$helpTitle = isset($row[3]) ? (string)$row[3] : '';
		$titleAttr = $helpTitle !== '' ? ' title="' . $h($helpTitle) . '"' : '';
		echo '<div class="overlay-option">';
		echo '<label class="rkrk-sel rkrk-reg-pill"' . $titleAttr . '><input class="overlay-toggle" type="checkbox" data-overlay-key="' . $h($row[0]) . '"> ' . $h($row[1]) . '</label>';
		echo '<span class="overlay-opacity"><input type="range" min="0" max="100" step="5" value="' . (int)$row[2] . '" data-overlay-opacity-key="' . $h($row[0]) . '"><span data-overlay-opacity-label="' . $h($row[0]) . '">' . (int)$row[2] . '%</span></span>';
		echo '</div>';
		if ($row[0] === 'obstacles') {
			echo '<div class="overlay-option overlay-subopt" id="obstacleHeightsRow">';
			echo '<label class="rkrk-sel rkrk-reg-pill overlay-subopt"><input type="checkbox" id="obstacleHeights" checked disabled> Höhenangaben</label>';
			echo '</div>';
		}
	}
          ?>
          <div class="rkrk-map27-status" id="overlayStatus"></div>
        </div>
      </details>

      <details open>
        <summary>Filter</summary>
        <div class="panel-body">
          <div class="row">
            <label class="block" for="FFilter">Feldfilter</label>
            <input type="text" id="FFilter" name="FFilter" value="<?php echo $h($FFilter); ?>" placeholder="21-002 21-006">
          </div>
          <div class="row">
            <label class="block" for="Zoom">Beschriftung</label>
            <select id="Zoom" name="Zoom">
              <?php
	foreach (array('2.0', '1.8', '1.6', '1.2', '1.0', '0.8', '0.6', '0.4') as $z) {
		$sel = (abs((float)$SelectedZoom - (float)$z) < 0.001) ? ' selected' : '';
		echo '<option value="' . $z . '"' . $sel . '>' . $z . '</option>';
	}
              ?>
            </select>
          </div>
          <div class="rkrk-sels rkrk-reg-pills">
          <label class="rkrk-sel rkrk-reg-pill"><input type="checkbox" name="DBW" value="on"<?php echo ($DebugWork == 'on' || $DebugWork === true) ? ' checked' : ''; ?>> Debug-Daten</label>
          <label class="rkrk-sel rkrk-reg-pill"><input type="checkbox" name="EXselect" value="on"<?php echo ($EXselect == 'on') ? ' checked' : ''; ?>> Externe Ansicht</label>
          </div>
          <button class="btn" type="submit" name="Einlesen" value="Darstellen">Darstellen</button>
        </div>
      </details>

      <details id="map27SharePanel" open>
        <summary>Export &amp; Teilen</summary>
        <div class="panel-body">
          <div id="shareActionHost" class="rkrk-share-actions">
            <button type="button" id="mapPrintBtn" class="rkrk-share-btn" title="Karte drucken" aria-label="Drucken">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
            </button>
            <button type="button" id="mapPdfBtn" class="rkrk-share-btn" title="Karte als A4-PDF (Massstab wählbar)" aria-label="PDF">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1.5 14H8v-2h4.5a1 1 0 0 0 0-2H10a1 1 0 0 0-1 1v4h5.5a1 1 0 0 0 0-2H12.5v1zM14 9V3.5L19.5 9H14z"/></svg>
            </button>
            <button type="button" id="mapKmlBtn" class="rkrk-share-btn" title="KML für Google Earth / map.geo.admin herunterladen" aria-label="KML">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
            </button>
            <button type="button" id="openSwissMapBtn" class="rkrk-share-btn" title="In map.geo.admin.ch (SwissTopo) öffnen — Massstab: Ansicht oder fest" aria-label="SwissTopo">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>
            </button>
            <select id="mapPdfScaleSelect" aria-label="Massstab für Druck, PDF und SwissTopo" title="Massstab: Ansicht = aktuelle Karte; feste Massstäbe für Druck, PDF und SwissTopo">
              <option value="0">Massstab: Ansicht</option>
              <option value="10000">1:10'000</option>
              <option value="25000">1:25'000</option>
              <option value="50000">1:50'000</option>
            </select>
            <button type="button" id="shareViewLinkBtn" class="rkrk-share-btn" title="Kartenansicht-Link kopieren (mit QR-Code)" aria-label="Kartenansicht-Link">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            </button>
          </div>
          <p class="rkrk-share-hint" id="sharePanelStatus"></p>
          <div id="shareQrBox" class="rkrk-share-qr" hidden>
            <img id="shareQrImg" alt="QR-Code der Kartenansicht" width="180" height="180">
            <a id="shareQrLink" href="#" target="_blank" rel="noopener"></a>
          </div>
        </div>
      </details>
      <?php if ($internOk) { ?>
      <details>
        <summary>Werkzeuge</summary>
        <div class="panel-body">
          <div class="rkrk-map27-tools">
            <?php
		if ($FFilter != '') {
			echo " <a href='rkrk_fluege.php?add=Y&AddFlugID=" . $h($FFilter) . "&AddFlugToDate=" . date('Y-m-d', strtotime('tomorrow')) . "' target='_editfluege'><img src='" . $h($IconLoc) . "/addflug.png' title='Flugfelder für Morgen hinzufügen'></a>  ";
		}
		echo " <a href='rkrk_dokml.php?" . $h($ParameterSet) . "&OVR=" . $h($Overview) . $h($SColParameter) . "&File=RKRK_Felder_" . $h(str_replace(' ', '_', $RegionSelected)) . "' target='_savekml'><img src='" . $h($IconLoc) . "/save.png' title='KML speichern'></a>";
		echo " <a href='rkrk_dokml.php?" . $h($ParameterSet) . "&OVR=" . $h($Overview) . $h($SColParameter) . "&File=RKRK_Felder_" . $h(str_replace(' ', '_', $RegionSelected)) . "&DL=1' download><img src='" . $h($IconLoc) . "/dwnld_kml_35.png' title='KML herunterladen'></a>";
		echo " <a href='rkrk_kml2xml.php'><img src='" . $h($IconLoc) . "/settings.png' title='Settings'></a>";
            ?>
          </div>
        </div>
      </details>
      <?php } ?>
    </form>
  </div>
  <div class="map-wrap">
    <div id="map"></div>
    <div id="map27Load" class="rkrk-map27-load">Karte wird geladen …</div>
    <div id="map27Popup" class="rkrk-map27-popup" style="display:none">
      <button type="button" class="pop-close" id="map27PopupClose" aria-label="Schliessen">×</button>
      <div id="map27PopupBody"></div>
    </div>
    <?php if ($MapButtons == 'on') { ?>
    <div class="rkrk-map27-map-btns" data-rkrk-box="mapbtns" data-rkrk-box-label="Karten-Buttons">
      <?php echo PageButton($selfFile, $RegionSelected, $EX, $FFilter); ?>
    </div>
    <?php } ?>
    <?php if ($legendHtml !== '') { ?>
    <div class="rkrk-map27-legend" data-rkrk-box="legend" data-rkrk-box-label="Legende">
      <?php echo $legendHtml; ?>
    </div>
    <?php } ?>
  </div>
</div>
<script>
window.RKRK_MAP27 = <?php
	echo json_encode(array(
		'kmlUrl' => (string)$KmlMapUrl,
		'lon' => $mapLon,
		'lat' => $mapLat,
		'zoom' => $mapZoomGeo,
		'mapType' => (string)$MapType,
		'menuOn' => (bool)$MenuOn,
		'fitExtent' => !empty($fitExtent),
		'scaleDenom' => (int)$scaleDenom,
		'fitKeepRatio' => isset($fitKeepRatio) ? (float)$fitKeepRatio : 0.95,
		'fitTarget' => isset($fitTarget) ? (string)$fitTarget : 'fields',
		'fFilter' => (string)$FFilter,
		'ovr' => ($Overview === 'on'),
		'flp' => ($Flugplan === 'on'),
		'pfad' => !empty($MapOptions['Pfad']['Value']),
		'fitCrop' => (($Flugplan === 'on') || !empty($MapOptions['Pfad']['Value'])) ? 'flugplan' : 'suchfeld',
		'labelScale' => (float)$SelectedZoom,
		'showLabels' => !empty($MapOptions['Label']['Value']),
		'hideJgLabel' => (isset($_GET['JGLabel']) && $_GET['JGLabel'] === 'off'),
		'geLa' => !empty($MapOptions['GeLa']['Value']),
		'iconLoc' => (string)$IconLoc,
		'geoAdminUrl' => (string)$FullMapURL,
		'geoAdminBase' => (string)$MapGeoAdminURL,
		'internOk' => (bool)$internOk,
		'kmlDownloadName' => 'RKRK_Felder_' . str_replace(' ', '_', (string)$RegionSelected) . '.kml',
		'dabsUrl' => 'rkrk_dabs.php',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
<?php if ($MenuOn) { ?>
<script src="fe/rkrk_layout.js?v=20261015side"></script>
<?php } ?>
<script src="scripts/rkrk_swiss_overlays.js?v=20260917dabs1"></script>
<script src="scripts/rkrk_map27.js?v=20261013picker"></script>
<?php
