<?php
/**
 * Gemeinsame Flugbuchungs-Funktionen fuer rkrk_fluege.php und fe/rkrk_rc_einsatz.php.
 * Katalogtext, neue Flugzeile, Ergebnis-Felder, Checkliste und KMZ-Links gibt es nur hier.
 */

	function rkrk_fluege_looks_corrupt_json($text) {
		$text = ltrim((string)$text);
		return ($text !== '' && ($text[0] === '{' || $text[0] === '[') && strpos($text, '"0"') !== false);
	}

	function rkrk_fluege_repair_display_text($text) {
		$text = trim((string)$text);
		if ($text === '' || !rkrk_fluege_looks_corrupt_json($text)) {
			return $text;
		}
		for ($i = 0; $i < 6; $i++) {
			if (!rkrk_fluege_looks_corrupt_json($text)) {
				return $text;
			}
			$decoded = json_decode($text, true);
			if (!is_array($decoded)) {
				return $text;
			}
			$next = '';
			foreach ($decoded as $part) {
				if (is_string($part) && trim($part) !== '') {
					$next = trim($part);
					break;
				}
			}
			if ($next === '') {
				if (preg_match('/(?:\\\\"|")0(?:\\\\"|")\s*:\s*(?:\\\\"|")([^"\\\\]+)/u', $text, $m)) {
					return trim($m[1]);
				}
				return $text;
			}
			$text = $next;
		}
		return $text;
	}

	function rkrk_fluege_scalar_text($value) {
		if (is_array($value)) {
			foreach ($value as $part) {
				$flat = rkrk_fluege_scalar_text($part);
				if ($flat !== '') {
					return $flat;
				}
			}
			return '';
		}
		$text = trim(rkrk_fluege_repair_display_text($value));
		if ($text === '{}' || $text === '[]') {
			return '';
		}
		return $text;
	}

	function rkrk_fluege_clean_felder_text($text) {
		$text = rkrk_fluege_scalar_text($text);
		$text = preg_replace('/\s*\{\}\s*/u', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text);
		return trim($text);
	}

	function rkrk_fluege_build_field_text($field) {
		if (!is_array($field)) {
			return rkrk_fluege_clean_felder_text($field);
		}
		$text = rkrk_fluege_scalar_text($field['Feldname'] ?? '');
		$flurname = rkrk_fluege_scalar_text($field['Flurname'] ?? '');
		if ($flurname !== '') {
			$text .= ($text !== '' ? ' ' : '') . $flurname;
		}
		$gstk = rkrk_fluege_scalar_text($field['GrundstueckID'] ?? '');
		if ($gstk !== '') {
			$text .= ($text !== '' ? ' ' : '') . $gstk;
		}
		return trim($text);
	}

	function rkrk_fluege_format_bauer_phone($phoneRaw) {
		$phoneRaw = trim((string)$phoneRaw);
		if ($phoneRaw === '') {
			return '';
		}
		$digits = preg_replace('/\D+/', '', $phoneRaw);
		if (strlen($digits) < 9) {
			return '';
		}
		if (strpos($digits, '41') === 0 && strlen($digits) > 2) {
			$digits = '0' . substr($digits, 2);
		}
		if (strlen($digits) === 9 && $digits[0] === '7') {
			$digits = '0' . $digits;
		}
		if (strlen($digits) < 10) {
			return '';
		}
		$display = preg_replace('/^\+41\s*/', '0', $phoneRaw);
		return trim($display);
	}

	function rkrk_fluege_extract_bauer_phone($line) {
		$line = trim((string)$line);
		if ($line === '' || stripos($line, 'T:') !== 0) {
			return '';
		}
		return rkrk_fluege_format_bauer_phone(trim(substr($line, 2)));
	}

	function rkrk_fluege_landwirt_is_invalid($text) {
		$text = trim((string)$text);
		if ($text === '') {
			return false;
		}
		if (preg_match('/^(A:|T:|M:|E:)/i', $text)) {
			return true;
		}
		if (preg_match('/^A:\s*[\+]?\s*$/u', $text)) {
			return true;
		}
		if (preg_match('/^[\+\s\d\(\)\/-]+$/u', $text) && !preg_match('/\d{9,}/', preg_replace('/\D+/', '', $text))) {
			return true;
		}
		return false;
	}

	function rkrk_fluege_clean_landwirt_text($text) {
		$text = rkrk_fluege_scalar_text($text);
		if ($text === '') {
			return '';
		}
		if (strpos($text, "\n") !== false || preg_match('/^(A:|T:|M:|E:)/im', $text)) {
			return rkrk_fluege_build_bauer_text($text);
		}
		if (rkrk_fluege_landwirt_is_invalid($text)) {
			return '';
		}
		return $text;
	}

	function rkrk_fluege_build_bauer_text($bauerRaw) {
		$bauerRaw = rkrk_fluege_scalar_text($bauerRaw);
		if ($bauerRaw === '') {
			return '';
		}
		$lines = preg_split('/\R/u', $bauerRaw);
		$name = '';
		$phone = '';
		foreach ($lines as $line) {
			$line = trim((string)$line);
			if ($line === '') {
				continue;
			}
			if (stripos($line, 'T:') === 0) {
				if ($phone === '') {
					$phone = rkrk_fluege_extract_bauer_phone($line);
				}
				continue;
			}
			if (preg_match('/^(A:|M:|E:)/i', $line)) {
				continue;
			}
			if ($name === '') {
				$name = $line;
			}
		}
		$name = trim($name);
		if ($name === '' || preg_match('/^(A:|T:|M:|E:)/i', $name)) {
			return '';
		}
		return $phone !== '' ? ($name . ' ' . $phone) : $name;
	}

	function rkrk_fluege_lookup_field_meta($fieldDataArray, $fid) {
		$fid = trim((string)$fid);
		if ($fid === '' || !is_array($fieldDataArray)) {
			return null;
		}
		if (isset($fieldDataArray[$fid]) && is_array($fieldDataArray[$fid])) {
			return $fieldDataArray[$fid];
		}
		$fidUp = strtoupper($fid);
		foreach ($fieldDataArray as $key => $meta) {
			if (strtoupper((string)$key) === $fidUp && is_array($meta)) {
				return $meta;
			}
		}
		return null;
	}

	function rkrk_fluege_sync_row_from_catalog(&$row, $fieldDataArray) {
		global $ManualFieldLabel;

		$row['Gde'] = rkrk_fluege_scalar_text($row['Gde'] ?? '');
		$row['Landwirt'] = rkrk_fluege_clean_landwirt_text($row['Landwirt'] ?? '');
		$row['Felder'] = rkrk_fluege_clean_felder_text($row['Felder'] ?? '');
		$row['Kontaktnummer'] = rkrk_fluege_scalar_text($row['Kontaktnummer'] ?? '');
		$row['Bemerkung'] = rkrk_fluege_scalar_text($row['Bemerkung'] ?? '');

		$fid = trim((string)($row['Feld'] ?? ''));
		if ($fid === '' || $fid === $ManualFieldLabel) {
			return;
		}
		$meta = rkrk_fluege_lookup_field_meta($fieldDataArray, $fid);
		if ($meta === null) {
			return;
		}
		$catFelder = rkrk_fluege_clean_felder_text($meta['Text'] ?? '');
		if ($row['Gde'] === '' || rkrk_fluege_looks_corrupt_json($row['Gde'])) {
			$row['Gde'] = rkrk_fluege_scalar_text($meta['Gemeinde'] ?? '');
		}
		$catLandwirt = rkrk_fluege_clean_landwirt_text($meta['Bauer'] ?? '');
		if ($row['Landwirt'] === '' || rkrk_fluege_looks_corrupt_json($row['Landwirt']) || rkrk_fluege_landwirt_is_invalid($row['Landwirt'])) {
			if ($catLandwirt !== '') {
				$row['Landwirt'] = $catLandwirt;
			}
		}
		if ($catFelder !== '' && (
			$row['Felder'] === ''
			|| rkrk_fluege_looks_corrupt_json($row['Felder'])
			|| strpos((string)$row['Felder'], '{}') !== false
			|| $row['Felder'] !== $catFelder
		)) {
			$row['Felder'] = $catFelder;
		}
		if (trim((string)($row['GrundstückID'] ?? '')) === '') {
			$row['GrundstückID'] = rkrk_fluege_scalar_text($meta['GrundstueckID'] ?? '');
		}
		if (trim((string)($row['JG'] ?? '')) === '') {
			$row['JG'] = rkrk_fluege_scalar_text($meta['JG'] ?? '');
		}
		if (trim((string)($row['Flaeche'] ?? '')) === '') {
			$row['Flaeche'] = rkrk_fluege_scalar_text($meta['Flaeche'] ?? '');
		}
		if (trim((string)($row['Warnung'] ?? '')) === '') {
			$row['Warnung'] = rkrk_fluege_scalar_text($meta['Warnung'] ?? '');
		}
		if (trim((string)($row['Jaeger'] ?? '')) === '') {
			$row['Jaeger'] = rkrk_fluege_scalar_text($meta['Jaeger'] ?? '');
		}
	}

	function rkrk_fluege_repair_all_flight_data(&$data, $fieldDataArray) {
		if (!is_array($data)) {
			return;
		}
		foreach ($data as $flugId => $row) {
			if (!is_array($row)) {
				continue;
			}
			rkrk_fluege_sync_row_from_catalog($data[$flugId], $fieldDataArray);
		}
	}

	function ChecklistPilotName($pilotCode) {
		$map = array(
			"TC" => "Thomy",
			"CH" => "Chris",
			"AC" => "Armon",
			"NC" => "Nando",
			"MF" => "Marina",
			"LF" => "Lars",
			"VP" => "Vesa",
			"MB" => "Marc",
		);
		$code = strtoupper(trim((string)$pilotCode));
		return $map[$code] ?? $pilotCode;
	}

	function BuildChecklistLink($params) {
		// Template pfadfrei bestimmen (kein fester Dateisystempfad):
		// 1) optional per URL, 2) bewährtes Standard-Template.
		$template = trim((string)($_GET["ChecklistTemplate"] ?? ""));
		if ($template === "") {
			$template = "rkrk_rehkitzsuche.json";
		}

		$base = "rkrk_checklist.php";
		$q = array(
			"mode" => "form",
			"file" => basename($template),
		);
		foreach ($params as $k => $v) {
			if ($v === null) continue;
			$val = trim((string)$v);
			if ($val === "") continue;
			$q[$k] = $val;
		}
		return $base . "?" . http_build_query($q);
	}

	function NormalizeChecklistFlugort($ort, $jg = '') {
		global $RegionSettings;

		$jgKey = trim((string)$jg);
		if ($jgKey !== '' && isset($RegionSettings[$jgKey]["Text"])) {
			$txt = trim((string)$RegionSettings[$jgKey]["Text"]);
			// remove leading number/code prefix like "25-" / "25_" / "25 "
			$txt = preg_replace('/^\s*\d+\s*[-_ ]\s*/u', '', $txt);
			// normalize underscores in region names
			$txt = str_replace('_', ' ', $txt);
			$txt = trim(preg_replace('/\s+/u', ' ', $txt));
			if ($txt !== '') return $txt;
		}

		$val = trim((string)$ort);
		$val = str_replace('_', ' ', $val);
		return trim(preg_replace('/\s+/u', ' ', $val));
	}

	function CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, $xmlLabelFilter = null) {
		global $FlyFileSetting;
		$links = array();
		foreach ($data as $dl) {
			if (($dl['Pilot'] ?? '') !== $pilotCode) continue;
			if (($dl['Datum'] ?? '') !== $date) continue;
			$fieldId = trim((string)($dl['Feld'] ?? ''));
			if ($fieldId === '' || strpos($fieldId, '-') === false) continue;
			$jg = explode('-', $fieldId)[0];
			if (!isset($feldkatalog[$jg][$fieldId])) continue;
			foreach ($FlyFileSetting as $fileType) {
				$label = (string)($fileType["XMLLabel"] ?? '');
				if ($label !== "JasonFile" && $label !== "KMZFile") continue;
				if ($xmlLabelFilter !== null && $label !== $xmlLabelFilter) continue;
				$linkData = explode(",", (string)($feldkatalog[$jg][$fieldId][$label] ?? ''));
				foreach ($linkData as $link) {
					$link = trim((string)$link);
					if ($link === '') continue;
					$links[$link] = $link;
				}
			}
		}
		return array_values($links);
	}

	function BuildPilotDownloadJsonOnClick($data, $pilotCode, $date, $feldkatalog, $baseMissionPath) {
		$links = CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, "JasonFile");
		$urls = array();
		foreach ($links as $rel) {
			$href = function_exists('rkrk_file_convert_download_href') ? rkrk_file_convert_download_href($rel) : '';
			if ($href === '') {
				$href = rkrk_json_flight_export_href($rel);
			}
			if ($href === '') {
				$href = $baseMissionPath . $rel;
			}
			$urls[] = $href;
		}
		$jsonUrls = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return "return StartPilotDownloads(" . $jsonUrls . ");";
	}
	function BuildPilotDownloadKMZOnClick($data, $pilotCode, $date, $feldkatalog, $baseMissionPath) {
		$links = CollectPilotFlightPlanLinks($data, $pilotCode, $date, $feldkatalog, "KMZFile");
		$urls = array();
		foreach ($links as $rel) {
			$urls[] = $baseMissionPath . $rel;
		}
		$jsonUrls = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return "return StartPilotDownloads(" . $jsonUrls . ");";
	}

function rkrk_einsatz_flight_csv_header() {
	return array(
		"FlugID", "Datum", "Zeit", "Pilot", "Feld", "Kontrollflug", "Geflogen", "Fund", "UAV#",
		"Felder", "Jaeger", "Kontaktnummer", "Landwirt", "Bemerkung", "Flugzeit", "AnzFelder",
		"#Funde", "Einsatztag", "GrundstückID", "Gde", "JG", "Warnung", "Reg", "Flaeche", "Status",
	);
}

function rkrk_einsatz_yn_options() {
	return array("", "Ja", "Nein");
}

function rkrk_einsatz_apply_result_fields(&$row, $geflogen, $kontrollflug, $fund, $bemerkung) {
	global $searchSD, $replaceSD;
	$allowed = rkrk_einsatz_yn_options();
	$geflogen = trim((string)$geflogen);
	$kontrollflug = trim((string)$kontrollflug);
	$row["Geflogen"] = in_array($geflogen, $allowed, true) ? $geflogen : "";
	$row["Kontrollflug"] = in_array($kontrollflug, $allowed, true) ? $kontrollflug : "";
	$row["Fund"] = (string)$fund;
	$bem = (string)$bemerkung;
	if (isset($searchSD, $replaceSD)) {
		$bem = str_replace($searchSD, $replaceSD, $bem);
	}
	if (function_exists("cleanString")) {
		$bem = cleanString($bem);
	}
	$row["Bemerkung"] = $bem;
}

function rkrk_einsatz_apply_detail_fields(&$row, $fields) {
	$map = array(
		"zeit" => "Zeit",
		"jaeger" => "Jaeger",
		"landwirt" => "Landwirt",
		"kontakt" => "Kontaktnummer",
		"flugzeit" => "Flugzeit",
		"uav" => "UAV#",
		"anz_funde" => "#Funde",
		"grundstueck" => "GrundstückID",
	);
	foreach ($map as $key => $column) {
		if (!is_array($fields) || !array_key_exists($key, $fields)) {
			continue;
		}
		$value = trim((string)$fields[$key]);
		if ($column === "Flugzeit" && $value !== "" && is_numeric($value)) {
			$value = (string)round((float)$value, 1);
		}
		if (function_exists("cleanString") && $column !== "Flugzeit" && $column !== "UAV#" && $column !== "#Funde") {
			$value = cleanString($value);
		}
		$row[$column] = $value;
	}
}

function rkrk_einsatz_delete_flight(&$data, $flugId) {
	$flugId = trim((string)$flugId);
	if ($flugId === "" || !isset($data[$flugId]) || !is_array($data[$flugId])) {
		return null;
	}
	$feld = (string)($data[$flugId]["Feld"] ?? "");
	unset($data[$flugId]);
	return $feld;
}

function rkrk_einsatz_append_catalog_flight(&$data, $addFlugId, $addFlugToDate, $pilotOverride = null, $zeit = "") {
	global $MaxFlugID, $Feldkatalog, $FieldDataArray, $AdressDataArray, $RegDataArray, $PilotDataArray, $FluzeitStartUndLandung, $i;

	$AddFlugID = trim((string)$addFlugId);
	if ($AddFlugID === "" || strpos($AddFlugID, "-") === false) {
		return "";
	}
	$JG = substr($AddFlugID, 0, strpos($AddFlugID, "-"));
	$MaxFlugID++;
	$FlugID = "FID" . sprintf("%06d", $MaxFlugID);
	$feldRow = array();
	if (
		isset($Feldkatalog[$JG]) && is_array($Feldkatalog[$JG])
		&& isset($Feldkatalog[$JG][$AddFlugID]) && is_array($Feldkatalog[$JG][$AddFlugID])
	) {
		$feldRow = $Feldkatalog[$JG][$AddFlugID];
	}
	$fieldMeta = rkrk_fluege_lookup_field_meta($FieldDataArray, $AddFlugID);
	$jaegerKey = rkrk_fluege_scalar_text($feldRow["Jaeger"] ?? "");
	$jgCode = rkrk_fluege_scalar_text($feldRow["JG"] ?? "");
	$kontakt = "";
	if ($jaegerKey !== "" && isset($AdressDataArray[$jaegerKey]) && is_array($AdressDataArray[$jaegerKey])) {
		$kontakt = $AdressDataArray[$jaegerKey]["Text"] ?? "";
	}
	$regUp = "";
	if ($jgCode !== "" && isset($RegDataArray[$jgCode]) && is_array($RegDataArray[$jgCode])) {
		$regUp = $RegDataArray[$jgCode]["RegUp"] ?? "";
	}
	$flugzeitKatalog = $feldRow["Flugzeit"] ?? 0;
	if (!is_numeric($flugzeitKatalog)) {
		$flugzeitKatalog = 0;
	}
	$kontrollflug = "";
	if (isset($i)) {
		$kontrollKey = "Kontrollflug-" . $i;
		if ($kontrollKey !== "" && isset($_POST[$kontrollKey])) {
			$kontrollflug = $_POST[$kontrollKey];
		}
	}
	$pilot = "";
	if ($pilotOverride !== null && trim((string)$pilotOverride) !== "") {
		$pilot = trim((string)$pilotOverride);
	} elseif (isset($PilotDataArray[$JG]) && is_array($PilotDataArray[$JG])) {
		$pilot = $PilotDataArray[$JG]["Pilot"] ?? "";
	}
	$landwirt = "";
	if (is_array($fieldMeta) && array_key_exists("Bauer", $fieldMeta)) {
		$landwirt = $fieldMeta["Bauer"];
	} else {
		$landwirt = rkrk_fluege_build_bauer_text($feldRow["Bauer"] ?? "");
	}
	$data[$FlugID]["FlugID"] = $FlugID;
	$data[$FlugID]["Datum"] = $addFlugToDate;
	$data[$FlugID]["Zeit"] = (string)$zeit;
	$data[$FlugID]["Pilot"] = $pilot;
	$data[$FlugID]["Feld"] = $AddFlugID;
	$data[$FlugID]["Kontrollflug"] = $kontrollflug;
	$data[$FlugID]["Geflogen"] = "";
	$data[$FlugID]["Fund"] = "";
	$data[$FlugID]["UAV#"] = "";
	$data[$FlugID]["Felder"] = rkrk_fluege_build_field_text($feldRow);
	$data[$FlugID]["Jaeger"] = $jaegerKey;
	$data[$FlugID]["Kontaktnummer"] = $kontakt;
	$data[$FlugID]["Landwirt"] = $landwirt;
	$data[$FlugID]["Bemerkung"] = "";
	$data[$FlugID]["Flugzeit"] = $FluzeitStartUndLandung + $flugzeitKatalog;
	$data[$FlugID]["AnzFelder"] = 1;
	$data[$FlugID]["#Funde"] = "";
	$data[$FlugID]["Einsatztag"] = $addFlugToDate;
	$data[$FlugID]["GrundstückID"] = $feldRow["GrundstueckID"] ?? "";
	$data[$FlugID]["Gde"] = $feldRow["Gemeinde"] ?? "";
	$data[$FlugID]["JG"] = $jgCode;
	$data[$FlugID]["Warnung"] = $feldRow["Warnung"] ?? "";
	$data[$FlugID]["Reg"] = $regUp;
	$data[$FlugID]["Flaeche"] = $feldRow["Flaeche"] ?? "";
	$data[$FlugID]["Status"] = "locked";
	return $FlugID;
}

function rkrk_einsatz_kmz_urls($data, $pilotCode, $date) {
	global $Feldkatalog, $RKRKLocalLoactionMissionFiles;
	$rels = CollectPilotFlightPlanLinks($data, $pilotCode, $date, $Feldkatalog, "KMZFile");
	$urls = array();
	$script = str_replace("\\", "/", (string)($_SERVER["SCRIPT_NAME"] ?? ""));
	$inFe = (strpos($script, "/fe/") !== false);
	foreach ($rels as $rel) {
		$href = (string)$RKRKLocalLoactionMissionFiles . $rel;
		if ($inFe && !preg_match("#^(https?:)?//#", $href) && ($href === "" || $href[0] !== "/")) {
			$href = "../" . ltrim($href, "/");
		}
		$urls[] = $href;
	}
	return $urls;
}

function rkrk_einsatz_checklist_href($params) {
	$href = BuildChecklistLink($params);
	$script = str_replace("\\", "/", (string)($_SERVER["SCRIPT_NAME"] ?? ""));
	if (strpos($script, "/fe/") !== false) {
		return "../" . $href;
	}
	return $href;
}

function rkrk_json_flight_export_href($rel) {
	$rel = str_replace('\\', '/', trim((string)$rel));
	if ($rel === '' || strpos($rel, '..') !== false || !preg_match('/\.json$/i', $rel)) {
		return '';
	}
	global $RKRKMissionSelected;
	$mdb = trim((string)($RKRKMissionSelected ?? ''));
	$href = 'rkrk_json_flight_export.php?file=' . rawurlencode($rel);
	if ($mdb !== '') {
		$href .= '&MDB=' . rawurlencode($mdb);
	}
	return $href;
}

function rkrk_feld_id_from_mission_filename($rel) {
	$base = basename(str_replace('\\', '/', (string)$rel));
	$base = preg_replace('/\.(json|kmz)$/i', '', $base);
	if (preg_match('/^([A-Za-z0-9]{1,6}-\d{1,4})(?:_|$)/', (string)$base, $match)) {
		return $match[1];
	}
	if (function_exists('GetMeaningFilename')) {
		$parts = GetMeaningFilename((string)$base);
		$id = trim((string)($parts[0] ?? ''));
		if ($id !== '') {
			return $id;
		}
	}
	return '';
}

function rkrk_flight_sightings_count($row) {
	if (!is_array($row)) {
		return 0;
	}
	$raw = trim((string)($row['Fund'] ?? ''));
	if ($raw === '' || $raw === '0' || strcasecmp($raw, 'Nein') === 0) {
		$raw = trim((string)($row['#Funde'] ?? ''));
	}
	if ($raw === '' || $raw === '0' || strcasecmp($raw, 'Nein') === 0) {
		return 0;
	}
	if (is_numeric($raw)) {
		return max(0, (int)round((float)$raw));
	}
	if (strcasecmp($raw, 'Ja') === 0) {
		return 1;
	}
	return 0;
}

function rkrk_control_flight_date_utc($datum) {
	$datum = trim((string)$datum);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
		return '';
	}
	try {
		$local = new DateTime($datum . ' 00:00:00', new DateTimeZone('Europe/Zurich'));
	} catch (Exception $e) {
		return '';
	}
	$local->setTimezone(new DateTimeZone('UTC'));
	return $local->format('Y-m-d\TH:i:s.000\Z');
}

function rkrk_control_flights_for_field($rows, $feldId) {
	$feldId = trim((string)$feldId);
	$flights = array();
	if ($feldId === '' || !is_array($rows)) {
		return $flights;
	}
	foreach ($rows as $row) {
		if (!is_array($row) || trim((string)($row['Feld'] ?? '')) !== $feldId) {
			continue;
		}
		$sightings = rkrk_flight_sightings_count($row);
		if ($sightings < 1) {
			continue;
		}
		$date = rkrk_control_flight_date_utc($row['Datum'] ?? '');
		if ($date === '') {
			continue;
		}
		$item = new stdClass();
		$item->date = $date;
		$item->notice = trim((string)($row['Bemerkung'] ?? ''));
		$item->sightings = $sightings;
		$item->isControllFlight = (strcasecmp(trim((string)($row['Kontrollflug'] ?? '')), 'Ja') === 0);
		$item->additionalSightings = array();
		$flights[] = $item;
	}
	usort($flights, function ($a, $b) {
		return strcmp((string)$a->date, (string)$b->date);
	});
	return $flights;
}

function rkrk_load_flight_book_rows() {
	global $csvDocLocation, $RKRKFlightsCSV, $csv_deliminator;
	$rows = array();
	$dir = rtrim(str_replace('\\', '/', (string)$csvDocLocation), '/') . '/';
	$suffix = (string)($RKRKFlightsCSV ?? '_RKRKFluege.csv');
	if ($dir === '/' || !is_dir($dir) || !function_exists('csv_to_array') || !function_exists('FormatFlugDataArray')) {
		return $rows;
	}
	$names = scandir($dir);
	if (!is_array($names)) {
		return $rows;
	}
	$delimiter = (isset($csv_deliminator) && $csv_deliminator !== '') ? $csv_deliminator : ';';
	foreach ($names as $name) {
		if (!is_string($name) || !preg_match('/^\d{4}' . preg_quote($suffix, '/') . '$/', $name)) {
			continue;
		}
		$loaded = csv_to_array($dir . $name, $delimiter);
		if (!is_array($loaded)) {
			continue;
		}
		$loaded = FormatFlugDataArray($loaded);
		foreach ($loaded as $row) {
			if (is_array($row)) {
				$rows[] = $row;
			}
		}
	}
	return $rows;
}

function rkrk_json_apply_control_flights($json, $feldId, $rows = null) {
	if (!is_object($json)) {
		return;
	}
	if ($rows === null) {
		$rows = rkrk_load_flight_book_rows();
	}
	$flights = rkrk_control_flights_for_field($rows, $feldId);
	if (count($flights) > 0) {
		$json->controlFlights = $flights;
	}
}

function rkrk_json_ensure_mavic3t_drone_type($json) {
	if (!is_object($json)) {
		return;
	}
	if (!isset($json->missionSettings) || !is_object($json->missionSettings)) {
		$json->missionSettings = new stdClass();
	}
	if (!isset($json->missionSettings->djiPilot2) || !is_object($json->missionSettings->djiPilot2)) {
		$json->missionSettings->djiPilot2 = new stdClass();
	}
	$json->missionSettings->djiPilot2->droneType = 'mavic3T';
	if (!isset($json->sortAndQueryHelper) || !is_object($json->sortAndQueryHelper)) {
		$json->sortAndQueryHelper = new stdClass();
	}
	$json->sortAndQueryHelper->droneType = 'mavic3T';
}

function rkrk_json_encode_export($json) {
	$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
	if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
		$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
	}
	$encoded = json_encode($json, $flags);
	return is_string($encoded) ? $encoded . "\n" : '';
}
