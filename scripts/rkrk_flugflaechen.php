<?php
/**
 * Geflogene Flächen im Zeitraum: Flüge laden, Feld-IDs sammeln, KML bauen.
 */

if (!function_exists('rkrk_flugflaechen_years_in_range')) {
	function rkrk_flugflaechen_years_in_range($fromDate, $toDate) {
		$y1 = intval(date('Y', strtotime((string)$fromDate)));
		$y2 = intval(date('Y', strtotime((string)$toDate)));
		if ($y1 < 2000) {
			$y1 = intval(date('Y'));
		}
		if ($y2 < 2000) {
			$y2 = $y1;
		}
		if ($y2 < $y1) {
			$tmp = $y1;
			$y1 = $y2;
			$y2 = $tmp;
		}
		$years = array();
		for ($y = $y1; $y <= $y2; $y++) {
			$years[] = $y;
		}
		return $years;
	}
}

if (!function_exists('rkrk_flugflaechen_load_flights')) {
	/**
	 * Alle Flugzeilen der betroffenen Jahres-CSVs laden und normalisieren.
	 */
	function rkrk_flugflaechen_load_flights($fromDate, $toDate) {
		global $csvMissionLocation, $csvDocLocation, $RKRKFlightsCSV;
		$all = array();
		foreach (rkrk_flugflaechen_years_in_range($fromDate, $toDate) as $year) {
			$path = '';
			if (function_exists('rkrk_flights_csv_resolve_path')) {
				$path = rkrk_flights_csv_resolve_path($year);
			}
			if ($path === '' || !is_file($path)) {
				$base = (string)(isset($csvMissionLocation) && $csvMissionLocation !== '' ? $csvMissionLocation : $csvDocLocation);
				$suffix = (isset($RKRKFlightsCSV) && $RKRKFlightsCSV !== '') ? $RKRKFlightsCSV : '_RKRKFluege.csv';
				$fallback = $base . $year . $suffix;
				if (is_file($fallback)) {
					$path = $fallback;
				}
			}
			if ($path === '' || !is_file($path)) {
				continue;
			}
			$rows = csv_to_array($path, ';');
			if (!is_array($rows) || count($rows) === 0) {
				continue;
			}
			if (function_exists('FormatFlugDataArray')) {
				$rows = FormatFlugDataArray($rows);
			}
			foreach ($rows as $key => $row) {
				if (!is_array($row)) {
					continue;
				}
				$id = isset($row['FlugID']) ? (string)$row['FlugID'] : (string)$key;
				$all[$id] = $row;
			}
		}
		return $all;
	}
}

if (!function_exists('rkrk_flugflaechen_filter_geflogen')) {
	/**
	 * Nur Geflogen=Ja im Datumsintervall; optional Region (JG-Liste).
	 *
	 * @param array $flights
	 * @param string $fromDate
	 * @param string $toDate
	 * @param array $regionSelected JG-Codes, leer = alle
	 * @return array
	 */
	function rkrk_flugflaechen_filter_geflogen($flights, $fromDate, $toDate, $regionSelected = array()) {
		$out = array();
		if (!is_array($flights)) {
			return $out;
		}
		$regions = array();
		if (is_array($regionSelected)) {
			foreach ($regionSelected as $jg) {
				$jg = trim((string)$jg);
				if ($jg !== '' && strtoupper($jg) !== 'ALL' && strtoupper($jg) !== 'ALLE') {
					$regions[$jg] = $jg;
				}
			}
		}
		foreach ($flights as $key => $flug) {
			if (!is_array($flug)) {
				continue;
			}
			if (strcasecmp(trim((string)($flug['Geflogen'] ?? '')), 'Ja') !== 0) {
				continue;
			}
			$datum = trim((string)($flug['Datum'] ?? ''));
			if ($datum === '' || $datum < $fromDate || $datum > $toDate) {
				continue;
			}
			if (count($regions) > 0) {
				$jg = trim((string)($flug['JG'] ?? ''));
				if ($jg === '') {
					$feld = trim((string)($flug['Feld'] ?? $flug['FeldID'] ?? ''));
					if (strpos($feld, '-') !== false) {
						$jg = explode('-', $feld, 2)[0];
					}
				}
				if ($jg === '' || !isset($regions[$jg])) {
					continue;
				}
			}
			$out[$key] = $flug;
		}
		return $out;
	}
}

if (!function_exists('rkrk_flugflaechen_flightlog_regions')) {
	/**
	 * Regionen wie rkrk_flugliste.php: aktive Regions-Codes aus Reg, ohne JGList-Ausbau.
	 * Die Flugliste filtert die Spalte JG gegen diese Codes.
	 */
	function rkrk_flugflaechen_flightlog_regions($regParam) {
		global $RegionSettings;
		$out = array();
		$tokens = preg_split('/[\s,;]+/', trim((string)$regParam), -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($tokens)) {
			return array();
		}
		foreach ($tokens as $token) {
			$token = trim((string)$token);
			if ($token === '' || strcasecmp($token, 'ALL') === 0 || strcasecmp($token, 'alle') === 0) {
				continue;
			}
			if (is_array($RegionSettings) && isset($RegionSettings[$token]['Reg'])) {
				$code = trim((string)$RegionSettings[$token]['Reg']);
				if ($code !== '') {
					$out[$code] = $code;
				}
			}
		}
		return array_values($out);
	}
}

if (!function_exists('rkrk_flugflaechen_filter_flightlog')) {
	/**
	 * Dieselbe Menge wie die Flugliste mit Status „geflogen“:
	 * Geflogen genau Ja, Datum im Intervall, JG gleich Regions-Code.
	 */
	function rkrk_flugflaechen_filter_flightlog($flights, $fromDate, $toDate, $regionCodes) {
		$out = array();
		if (!is_array($flights)) {
			return $out;
		}
		$regions = array();
		if (is_array($regionCodes)) {
			foreach ($regionCodes as $jg) {
				$jg = trim((string)$jg);
				if ($jg !== '') {
					$regions[$jg] = true;
				}
			}
		}
		foreach ($flights as $key => $flug) {
			if (!is_array($flug)) {
				continue;
			}
			if (trim((string)($flug['Geflogen'] ?? '')) !== 'Ja') {
				continue;
			}
			$datum = trim((string)($flug['Datum'] ?? ''));
			if ($datum === '' || $datum < (string)$fromDate || $datum > (string)$toDate) {
				continue;
			}
			if (count($regions) > 0) {
				$jg = trim((string)($flug['JG'] ?? ''));
				if ($jg === '' || !isset($regions[$jg])) {
					continue;
				}
			}
			$out[$key] = $flug;
		}
		return $out;
	}
}

if (!function_exists('rkrk_flugflaechen_drawn_stats')) {
	/**
	 * Nur Felder mit Flächenpolygon im Katalog zählen und auflisten.
	 * Flüge ohne zeichenbares Feld fallen aus der Statistik.
	 *
	 * @return array{flights:int,feldIds:array}
	 */
	function rkrk_flugflaechen_drawn_stats($filteredFlights) {
		global $xmlMissionLocation, $RKRKFelderfileXML;
		$feldIds = array();
		$flights = 0;
		$drawable = array();
		$kat = null;
		if (!function_exists('GetFeldkatalog') && is_file(__DIR__ . '/rkrk_felddaten.php')) {
			include_once __DIR__ . '/rkrk_felddaten.php';
		}
		if (function_exists('GetFeldkatalog')) {
			$path = (string)$xmlMissionLocation . (string)$RKRKFelderfileXML;
			$loaded = GetFeldkatalog($path);
			if (is_array($loaded)) {
				$kat = $loaded;
			}
		}
		if (!is_array($filteredFlights)) {
			$filteredFlights = array();
		}
		foreach ($filteredFlights as $flug) {
			if (!is_array($flug)) {
				continue;
			}
			$feld = trim((string)($flug['Feld'] ?? $flug['FeldID'] ?? ''));
			if ($feld === '') {
				continue;
			}
			if (!array_key_exists($feld, $drawable)) {
				$entry = is_array($kat) ? rkrk_flugflaechen_lookup_field($kat, $feld) : null;
				$coords = is_array($entry) ? trim((string)($entry['Flaechendaten'] ?? '')) : '';
				$drawable[$feld] = ($coords !== '');
			}
			if (empty($drawable[$feld])) {
				continue;
			}
			$flights++;
			$feldIds[$feld] = $feld;
		}
		$ids = array_values($feldIds);
		natsort($ids);
		return array(
			'flights' => $flights,
			'feldIds' => array_values($ids),
		);
	}
}

if (!function_exists('rkrk_flugflaechen_collect_stats')) {
	/**
	 * @return array{flights:int,fields:int,flaeche:float,feldIds:array,fieldList:array}
	 */
	function rkrk_flugflaechen_collect_stats($filteredFlights) {
		$feldIds = array();
		$fieldList = array();
		$flaecheSum = 0.0;
		$flightCount = 0;
		if (!is_array($filteredFlights)) {
			return array(
				'flights' => 0,
				'fields' => 0,
				'flaeche' => 0.0,
				'feldIds' => array(),
				'fieldList' => array(),
			);
		}
		foreach ($filteredFlights as $flug) {
			if (!is_array($flug)) {
				continue;
			}
			$flightCount++;
			$feld = trim((string)($flug['Feld'] ?? $flug['FeldID'] ?? ''));
			if ($feld === '') {
				continue;
			}
			$ha = str_replace(',', '.', trim((string)($flug['Flaeche'] ?? '')));
			$haVal = is_numeric($ha) ? floatval($ha) : 0.0;
			if (!isset($feldIds[$feld])) {
				$feldIds[$feld] = $feld;
				$fieldList[$feld] = array(
					'Feld' => $feld,
					'Flights' => 0,
					'Flaeche' => 0.0,
					'LastDate' => '',
					'Pilot' => '',
				);
			}
			$fieldList[$feld]['Flights']++;
			if ($haVal > 0 && $fieldList[$feld]['Flaeche'] <= 0) {
				$fieldList[$feld]['Flaeche'] = $haVal;
				$flaecheSum += $haVal;
			}
			$datum = (string)($flug['Datum'] ?? '');
			if ($datum !== '' && ($fieldList[$feld]['LastDate'] === '' || $datum > $fieldList[$feld]['LastDate'])) {
				$fieldList[$feld]['LastDate'] = $datum;
				$fieldList[$feld]['Pilot'] = (string)($flug['Pilot'] ?? '');
			}
		}
		$ids = array_values($feldIds);
		natsort($ids);
		$ids = array_values($ids);
		return array(
			'flights' => $flightCount,
			'fields' => count($ids),
			'flaeche' => $flaecheSum,
			'feldIds' => $ids,
			'fieldList' => $fieldList,
		);
	}
}

if (!function_exists('rkrk_flugflaechen_basename')) {
	function rkrk_flugflaechen_basename($fromDate, $toDate, $userHint = '') {
		$from = preg_replace('/[^0-9\-]/', '', (string)$fromDate);
		$to = preg_replace('/[^0-9\-]/', '', (string)$toDate);
		$user = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$userHint);
		$name = 'RKRK_Geflogen_' . $from . '_' . $to;
		if ($user !== '') {
			$name .= '_' . $user;
		}
		return $name;
	}
}

if (!function_exists('rkrk_flugflaechen_kml_path')) {
	function rkrk_flugflaechen_kml_path($basename) {
		global $kmlFilelocations;
		$dir = (isset($kmlFilelocations) && $kmlFilelocations !== '') ? $kmlFilelocations : 'kmlfiles/';
		$base = basename((string)$basename);
		$base = preg_replace('/\.kml$/i', '', $base);
		return $dir . $base . '.kml';
	}
}

if (!function_exists('rkrk_flugflaechen_lookup_field')) {
	function rkrk_flugflaechen_lookup_field($Feldkatalog, $feldId) {
		$flug = array('Feld' => $feldId, 'FeldID' => $feldId);
		if (function_exists('rkrk_feldkatalog_entry_for_flight')) {
			return rkrk_feldkatalog_entry_for_flight($Feldkatalog, $flug);
		}
		if (!is_array($Feldkatalog)) {
			return null;
		}
		if (strpos($feldId, '-') !== false) {
			$jg = explode('-', $feldId, 2)[0];
			if (isset($Feldkatalog[$jg][$feldId]) && is_array($Feldkatalog[$jg][$feldId])) {
				return $Feldkatalog[$jg][$feldId];
			}
		}
		foreach ($Feldkatalog as $fields) {
			if (is_array($fields) && isset($fields[$feldId]) && is_array($fields[$feldId])) {
				return $fields[$feldId];
			}
		}
		return null;
	}
}

if (!function_exists('rkrk_flugflaechen_build_kml')) {
	/**
	 * KML nur mit Flächenpolygonen der angegebenen Feld-IDs.
	 * Eigenes Header/Footer (ohne rkrk_kml.php Include — der setzt Content-Type text/xml).
	 */
	function rkrk_flugflaechen_build_kml($Feldkatalog, $feldIds, $title = '') {
		global $searchSD, $replaceSD;
		$title = trim((string)$title);
		if ($title === '') {
			$title = 'Geflogene Flaechen';
		}
		$fileoutput = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . chr(10);
		$fileoutput .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2">' . chr(10);
		$fileoutput .= '<Document>' . chr(10);
		$fileoutput .= '<name>RKRK ' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</name>' . chr(10);
		$fileoutput .= '<!-- ' . date('Y-m-d H:i:s') . ' geflogene Flaechen -->' . chr(10) . chr(10);
		$fileoutput .= '  <Style id="fieldarea_free">' . chr(10);
		$fileoutput .= '    <LineStyle><color>ff2d7653</color><width>2</width></LineStyle>' . chr(10);
		$fileoutput .= '    <PolyStyle><color>7d1cd97d</color></PolyStyle>' . chr(10);
		$fileoutput .= '  </Style>' . chr(10) . chr(10);

		$sdSearch = isset($searchSD) ? $searchSD : array();
		$sdReplace = isset($replaceSD) ? $replaceSD : array();
		$count = 0;
		if (!is_array($feldIds)) {
			$feldIds = array();
		}
		foreach ($feldIds as $feldId) {
			$feldId = trim((string)$feldId);
			if ($feldId === '') {
				continue;
			}
			$entry = rkrk_flugflaechen_lookup_field($Feldkatalog, $feldId);
			if (!is_array($entry)) {
				continue;
			}
			$coords = trim((string)($entry['Flaechendaten'] ?? ''));
			if ($coords === '') {
				continue;
			}
			$name = $feldId . '  ' . ($entry['Feldname'] ?? '') . '  ' . ($entry['GrundstueckID'] ?? '');
			if (is_array($sdSearch) && count($sdSearch) > 0) {
				$name = str_replace($sdSearch, $sdReplace, $name);
			}
			$fileoutput .= '  <Placemark id=' . chr(34) . 'A' . htmlspecialchars($feldId, ENT_QUOTES, 'UTF-8') . chr(34) . '>' . chr(10);
			$fileoutput .= '    <name>' . htmlspecialchars($name, ENT_XML1, 'UTF-8') . '</name>' . chr(10);
			$fileoutput .= '    <styleUrl>#fieldarea_free</styleUrl>' . chr(10);
			$fileoutput .= '    <Polygon>' . chr(10);
			$fileoutput .= '      <tessellate>1</tessellate>' . chr(10);
			$fileoutput .= '      <altitudeMode>clampToGround</altitudeMode>' . chr(10);
			$fileoutput .= '      <outerBoundaryIs><LinearRing><coordinates>' . chr(10);
			$fileoutput .= $coords . chr(10);
			$fileoutput .= '      </coordinates></LinearRing></outerBoundaryIs>' . chr(10);
			$fileoutput .= '    </Polygon>' . chr(10);
			$fileoutput .= '  </Placemark>' . chr(10) . chr(10);
			$count++;
		}
		$fileoutput .= '</Document>' . chr(10) . '</kml>' . chr(10);
		return array('kml' => $fileoutput, 'count' => $count);
	}
}

if (!function_exists('rkrk_flugflaechen_save_kml')) {
	function rkrk_flugflaechen_save_kml($kmlText, $basename) {
		$path = rkrk_flugflaechen_kml_path($basename);
		$dir = dirname($path);
		if (!is_dir($dir)) {
			@mkdir($dir, 0775, true);
		}
		$ok = @file_put_contents($path, $kmlText);
		return ($ok !== false) ? $path : '';
	}
}

if (!function_exists('rkrk_flugflaechen_center_lv95')) {
	/**
	 * Mittelpunkt aus Feld-Zentren (Lon,Lat[,alt]) → LV95 East/North.
	 */
	function rkrk_flugflaechen_center_lv95($Feldkatalog, $feldIds) {
		$sumLon = 0.0;
		$sumLat = 0.0;
		$n = 0;
		foreach ($feldIds as $feldId) {
			$entry = rkrk_flugflaechen_lookup_field($Feldkatalog, $feldId);
			if (!is_array($entry)) {
				continue;
			}
			$zentrum = '';
			if (isset($entry['Grundstueckdaten']['Zentrum'])) {
				$zentrum = trim((string)$entry['Grundstueckdaten']['Zentrum']);
			}
			if ($zentrum === '' && !empty($entry['Flaechendaten'])) {
				$parts = preg_split('/\s+/', trim((string)$entry['Flaechendaten']));
				$zentrum = $parts[0] ?? '';
			}
			if ($zentrum === '') {
				continue;
			}
			$xyz = explode(',', $zentrum);
			if (count($xyz) < 2) {
				continue;
			}
			$sumLon += floatval($xyz[0]);
			$sumLat += floatval($xyz[1]);
			$n++;
		}
		if ($n === 0) {
			return array('East' => 2678000, 'North' => 1232000, 'Zoom' => 6);
		}
		$lon = $sumLon / $n;
		$lat = $sumLat / $n;
		if (function_exists('GetLSV95EastNorthFromLonLat')) {
			$lv = GetLSV95EastNorthFromLonLat(array('Lon' => $lon, 'Lat' => $lat));
			if (is_array($lv) && isset($lv['LV95East'], $lv['LV95North'])) {
				$zoom = ($n <= 3) ? 9 : (($n <= 15) ? 7 : 5);
				return array(
					'East' => $lv['LV95East'],
					'North' => $lv['LV95North'],
					'Zoom' => $zoom,
				);
			}
		}
		return array('East' => 2678000, 'North' => 1232000, 'Zoom' => 6);
	}
}

if (!function_exists('rkrk_flugflaechen_map_query')) {
	/**
	 * Query für rkrk_map.php: bevorzugt FFilter+Area (wie Flugliste).
	 * Bei vielen Feldern und vorhandener KML-Datei: UseKmlFile.
	 */
	function rkrk_flugflaechen_map_query($feldIds, $mdb, $center = null, $kmlBasename = '') {
		if (!is_array($feldIds)) {
			$feldIds = array();
		}
		$feldIds = array_values(array_filter(array_map('strval', $feldIds), function ($v) {
			return trim($v) !== '';
		}));
		$useFile = false; // immer FFilter+Area (zuverlässiger)
		$q = array(
			'Reg' => 'ALL',
			'Strt' => 'off',
			'Cntr' => 'off',
			'Area' => 'on',
			'Pfad' => 'off',
			'GDE' => 'off',
			'JGG' => 'off',
			'Poi' => 'off',
			'Label' => 'off',
			'EX' => 'NO',
			'KA' => 'S',
			'MAP' => '',
			'Zoom' => '0.8',
			'MenuOn' => 'off',
			'Embed' => '1',
		);
		if ($mdb !== '') {
			$q['MDB'] = $mdb;
		}
		if ($useFile) {
			$q['UseKmlFile'] = basename(preg_replace('/\.kml$/i', '', (string)$kmlBasename));
			$q['FFilter'] = implode(' ', array_slice($feldIds, 0, 25));
		} else {
			$q['FFilter'] = implode(' ', $feldIds);
		}
		// E/N nur bei UseKmlFile setzen — sonst zentriert rkrk_map.php korrekt über FFilter
		if ($useFile && is_array($center)) {
			if (isset($center['East'])) {
				$q['E'] = $center['East'];
			}
			if (isset($center['North'])) {
				$q['N'] = $center['North'];
			}
			if (isset($center['Zoom'])) {
				$q['ZO'] = $center['Zoom'];
			}
		}
		return http_build_query($q, '', '&', PHP_QUERY_RFC3986);
	}
}
if (!function_exists('rkrk_flugflaechen_raster_png_from_svg')) {
	/**
	 * SVG-Datei → PNG (Imagick), sonst leerer String.
	 */
	function rkrk_flugflaechen_raster_png_from_svg($svgPath, $pngPath) {
		if (!is_file($svgPath)) {
			return '';
		}
		if (class_exists('Imagick')) {
			try {
				$im = new Imagick();
				$im->setBackgroundColor(new ImagickPixel('white'));
				$im->readImage($svgPath);
				$im->setImageFormat('png');
				$im->writeImage($pngPath);
				$im->clear();
				$im->destroy();
				if (is_file($pngPath)) {
					return $pngPath;
				}
			} catch (Throwable $e) {
				// fall through
			}
		}
		return '';
	}
}
