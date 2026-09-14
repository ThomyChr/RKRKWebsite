<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// AJAX-Save Bootstrap (?mode=save)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_GET['mode'] ?? '') === 'save' || ($_POST['ajax'] ?? '') === '1')) {
    if (($_GET['mode'] ?? '') !== 'save') {
        $_GET['mode'] = 'save';
    }
    if (($_GET['ajax'] ?? '') !== '1') {
        $_GET['ajax'] = '1';
    }
    if (($_POST['ajax'] ?? '') !== '1') {
        $_POST['ajax'] = '1';
    }
    if (!empty($_POST['MDB'])) {
        $_GET['MDB'] = trim((string)$_POST['MDB']);
        if (strcasecmp($_GET['MDB'], 'M3T') === 0) {
            $_GET['MDB'] = '3T';
        }
    }
}

  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_csvfiles.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php');

/* --- CHECKLIST SYNC + FLUG-CSV --- */
if (!function_exists('normalizeKey')) {
	function normalizeKey($str) {
		$str = trim((string)$str);
		$str = str_replace(
			array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'),
			array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'),
			$str
		);
		$str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
		$str = preg_replace('/_+/', '_', $str);
		return strtolower(trim($str, '_'));
	}
}

if (!function_exists('rkrk_is_dynamic_status_label')) {
	function rkrk_is_dynamic_status_label($label) {
		return preg_match('/^Status und Suchresultate\s*\([^)]+\)$/u', trim((string)$label)) === 1;
	}
}

if (!function_exists('rkrk_is_dynamic_status_other_label')) {
	function rkrk_is_dynamic_status_other_label($label) {
		return preg_match('/^Status und Suchresultate andere\s*\([^)]+\)$/u', trim((string)$label)) === 1;
	}
}

if (!function_exists('rkrk_checklist_feld_id_regex')) {
	function rkrk_checklist_feld_id_regex() {
		return '[A-Z0-9]{1,4}-\d{1,4}';
	}
}

if (!function_exists('rkrk_checklist_feld_id_preg')) {
	function rkrk_checklist_feld_id_preg($anchors = '') {
		$rx = rkrk_checklist_feld_id_regex();
		if ($anchors === '^' || $anchors === '^$') {
			return '/^' . $rx . '$/i';
		}
		return '/' . $anchors . $rx . $anchors . '/i';
	}
}

if (!function_exists('rkrk_checklist_extract_feld_id')) {
	function rkrk_checklist_extract_feld_id($text) {
		$text = trim((string)$text);
		if ($text === '') {
			return '';
		}
		if (preg_match(rkrk_checklist_feld_id_preg(), $text, $m)) {
			return (string)$m[0];
		}
		return '';
	}
}

if (!function_exists('rkrk_parse_flight_dt')) {
	function rkrk_parse_flight_dt($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return null;
		}
		$tz = new DateTimeZone(date_default_timezone_get());
		$dt = DateTime::createFromFormat('Y-m-d\\TH:i', $raw, $tz);
		if ($dt) {
			return $dt;
		}
		$dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw, $tz);
		if ($dt) {
			return $dt;
		}
		$dt = DateTime::createFromFormat('d.m.Y H:i', $raw, $tz);
		if ($dt) {
			return $dt;
		}
		try {
			return new DateTime($raw, $tz);
		} catch (Exception $e) {
			return null;
		}
	}
}

if (!function_exists('rkrk_sync_norm')) {
	function rkrk_sync_norm($v) {
		return mb_strtolower(trim((string)$v), 'UTF-8');
	}
}

if (!function_exists('rkrk_sync_extract_field_ids')) {
	function rkrk_sync_extract_field_ids($raw) {
		$tokens = preg_split('/[;,\n\r\t ]+/', (string)$raw);
		$ids = array();
		foreach ((array)$tokens as $tok) {
			$tok = trim((string)$tok);
			if ($tok === '') {
				continue;
			}
			$id = rkrk_checklist_extract_feld_id($tok);
			if ($id !== '') {
				$ids[] = $id;
			}
		}
		return array_values(array_unique($ids));
	}
}

if (!function_exists('rkrk_sync_time5')) {
	function rkrk_sync_time5($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		if (preg_match('/(\d{1,2}):(\d{2})/', $raw, $m)) {
			return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
		}
		return '';
	}
}

if (!function_exists('rkrk_sync_parse_field_time_map')) {
	function rkrk_sync_parse_field_time_map($raw) {
		$out = array();
		$parts = preg_split('/[;,\n\r]+/', (string)$raw);
		foreach ((array)$parts as $p) {
			$p = trim((string)$p);
			if ($p === '') {
				continue;
			}
			if (preg_match('/(' . rkrk_checklist_feld_id_regex() . ')\s*[:=]\s*(\d{1,2}:\d{2})/iu', $p, $m)) {
				$fid = function_exists('rkrk_checklist_normalize_feld_id')
					? rkrk_checklist_normalize_feld_id($m[1])
					: strtoupper(trim((string)$m[1]));
				if ($fid !== '') {
					$out[$fid] = sprintf('%02d:%02d', (int)substr($m[2], 0, 2), (int)substr($m[2], 3, 2));
				}
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_sync_day_ymd')) {
	function rkrk_sync_day_ymd($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		$dt = rkrk_parse_flight_dt($raw);
		if ($dt instanceof DateTime) {
			return $dt->format('Y-m-d');
		}
		return '';
	}
}

if (!function_exists('rkrk_sync_normalize_csv_day')) {
	function rkrk_sync_normalize_csv_day($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		$ymd = rkrk_sync_day_ymd($raw);
		if ($ymd !== '') {
			return $ymd;
		}
		$tz = new DateTimeZone(date_default_timezone_get());
		foreach (array('d.m.Y', 'd.m.y', 'Y/m/d', 'd-m-Y') as $fmt) {
			$dt = DateTime::createFromFormat($fmt, $raw, $tz);
			if ($dt instanceof DateTime) {
				return $dt->format('Y-m-d');
			}
		}
		return $raw;
	}
}

if (!function_exists('rkrk_sync_csv_day_matches')) {
	function rkrk_sync_csv_day_matches($csvDatum, $checklistDay) {
		$a = rkrk_sync_normalize_csv_day($csvDatum);
		$b = rkrk_sync_normalize_csv_day($checklistDay);
		return ($a !== '' && $b !== '' && $a === $b);
	}
}

if (!function_exists('rkrk_sync_csv_row_on_day')) {
	function rkrk_sync_csv_row_on_day(array $row, $day) {
		if (rkrk_sync_csv_day_matches($row['Datum'] ?? '', $day)) {
			return true;
		}
		return rkrk_sync_csv_day_matches($row['Einsatztag'] ?? '', $day);
	}
}

if (!function_exists('rkrk_sync_merge_per_field_map')) {
	function rkrk_sync_merge_per_field_map(array $perField) {
		$out = array();
		foreach ($perField as $fid => $st) {
			if (!is_array($st)) {
				continue;
			}
			$nk = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id($fid)
				: strtoupper(trim((string)$fid));
			if ($nk === '') {
				continue;
			}
			if (!isset($out[$nk])) {
				$out[$nk] = $st;
				continue;
			}
			$out[$nk]['touched'] = !empty($out[$nk]['touched']) || !empty($st['touched']);
			if (!empty($st['flown'])) {
				$out[$nk]['flown'] = true;
			}
			if (!empty($st['not_flown'])) {
				$out[$nk]['not_flown'] = true;
			}
			$out[$nk]['fund'] = max(intval($out[$nk]['fund'] ?? 0), intval($st['fund'] ?? 0));
			$remark = trim((string)($st['remark'] ?? ''));
			if ($remark !== '') {
				$old = trim((string)($out[$nk]['remark'] ?? ''));
				$out[$nk]['remark'] = ($old === '') ? $remark : $old;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_sync_pilot_match')) {
	function rkrk_sync_pilot_match($csvPilot, $checklistPilot) {
		$a = rkrk_sync_norm($csvPilot);
		$b = rkrk_sync_norm($checklistPilot);
		if ($a === '' || $b === '') {
			return false;
		}
		if ($a === $b) {
			return true;
		}
		$nameFn = function_exists('rkrk_fluege_sync_pilot_name')
			? 'rkrk_fluege_sync_pilot_name'
			: (function_exists('ChecklistPilotName') ? 'ChecklistPilotName' : null);
		if ($nameFn) {
			$aName = rkrk_sync_norm($nameFn((string)$csvPilot));
			if ($aName !== '' && ($aName === $b || mb_strpos($b, $aName) !== false || mb_strpos($aName, $b) !== false)) {
				return true;
			}
			$bName = rkrk_sync_norm($nameFn((string)$checklistPilot));
			if ($bName !== '' && ($bName === $a || mb_strpos($a, $bName) !== false || mb_strpos($bName, $a) !== false)) {
				return true;
			}
		}
		$codeA = function_exists('rkrk_checklist_pilot_code')
			? rkrk_checklist_pilot_code((string)$csvPilot)
			: strtoupper(trim((string)$csvPilot));
		$codeB = function_exists('rkrk_checklist_pilot_code')
			? rkrk_checklist_pilot_code((string)$checklistPilot)
			: strtoupper(trim((string)$checklistPilot));
		if ($codeA !== '' && $codeB !== '' && $codeA === $codeB) {
			return true;
		}
		return (mb_strpos($a, $b) !== false || mb_strpos($b, $a) !== false);
	}
}

if (!function_exists('rkrk_sync_feld_id_parts')) {
	function rkrk_sync_feld_id_parts($fid) {
		$fid = function_exists('rkrk_checklist_extract_feld_id')
			? rkrk_checklist_extract_feld_id($fid)
			: trim((string)$fid);
		if ($fid === '') {
			return null;
		}
		if (!preg_match('/^(.+)-(\d+)$/iu', $fid, $m)) {
			return null;
		}
		return array(strtoupper(trim((string)$m[1])), intval($m[2], 10));
	}
}

if (!function_exists('rkrk_sync_feld_ids_match')) {
	function rkrk_sync_feld_ids_match($a, $b) {
		$pa = rkrk_sync_feld_id_parts($a);
		$pb = rkrk_sync_feld_id_parts($b);
		if ($pa !== null && $pb !== null) {
			return ($pa[0] === $pb[0] && $pa[1] === $pb[1]);
		}
		$na = function_exists('rkrk_checklist_normalize_feld_id')
			? rkrk_checklist_normalize_feld_id($a)
			: strtoupper(trim((string)$a));
		$nb = function_exists('rkrk_checklist_normalize_feld_id')
			? rkrk_checklist_normalize_feld_id($b)
			: strtoupper(trim((string)$b));
		return ($na !== '' && $nb !== '' && strcasecmp($na, $nb) === 0);
	}
}

if (!function_exists('rkrk_sync_extract_checklist_state')) {
	function rkrk_sync_extract_checklist_state($fields) {
		$result = array(
			'day' => '', 'year' => '', 'time5' => '', 'pilot' => '',
			'field_ids' => array(), 'per_field' => array(),
			'field_time_map' => array(), 'entries' => array(),
		);
		if (!is_array($fields)) {
			return $result;
		}

		$datumZeit = '';
		$pilot = '';
		$feldRaw = '';
		$fieldTimeRaw = '';
		$allg = (isset($fields['Allgemein']) && is_array($fields['Allgemein'])) ? $fields['Allgemein'] : array();
		foreach ($allg as $lbl => $val) {
			$lblNorm = normalizeKey((string)$lbl);
			$v = is_array($val) ? implode('; ', $val) : trim((string)$val);
			if (($lblNorm === 'datum_zeit' || $lblNorm === 'flugzeit' || $lblNorm === 'zeit') && $datumZeit === '') {
				$datumZeit = $v;
			}
			if ($lblNorm === 'pilot' && $pilot === '') {
				$pilot = $v;
			}
			if (($lblNorm === 'feld' || $lblNorm === 'feld_nummer') && $feldRaw === '') {
				$feldRaw = $v;
			}
			if (($lblNorm === 'zeit_zum_feld' || $lblNorm === 'feld_zeit' || $lblNorm === 'fieldtimemap') && $fieldTimeRaw === '') {
				$fieldTimeRaw = $v;
			}
		}
		foreach ($fields as $secVals) {
			if (!is_array($secVals)) {
				continue;
			}
			foreach ($secVals as $lbl => $val) {
				$lblNorm = normalizeKey((string)$lbl);
				$v = is_array($val) ? implode('; ', $val) : (string)$val;
				if (($lblNorm === 'datum_zeit' || $lblNorm === 'flugzeit' || $lblNorm === 'zeit') && $datumZeit === '') {
					$datumZeit = trim($v);
				}
				if ($lblNorm === 'pilot' && $pilot === '') {
					$pilot = trim($v);
				}
				if (($lblNorm === 'feld' || $lblNorm === 'feld_nummer') && $feldRaw === '') {
					$feldRaw = trim($v);
				}
				if (($lblNorm === 'zeit_zum_feld' || $lblNorm === 'feld_zeit' || $lblNorm === 'fieldtimemap') && $fieldTimeRaw === '') {
					$fieldTimeRaw = trim($v);
				}
			}
		}

		$day = rkrk_sync_day_ymd($datumZeit);
		$time5 = rkrk_sync_time5($datumZeit);
		$year = ($day !== '' ? substr($day, 0, 4) : '');
		$fieldIds = rkrk_sync_extract_field_ids($feldRaw);
		$perField = array();
		foreach ($fieldIds as $fidRaw) {
			$fid = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id($fidRaw)
				: strtoupper(trim((string)$fidRaw));
			if ($fid === '') {
				continue;
			}
			$perField[$fid] = array('flown' => false, 'not_flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);
		}

		$sr = (isset($fields['Suchresultat']) && is_array($fields['Suchresultat'])) ? $fields['Suchresultat'] : array();
		foreach ($sr as $lbl => $val) {
			$label = trim((string)$lbl);
			if (!preg_match('/\((' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([0-2]?\d:[0-5]\d))?(?:\s*\|\s*(\d+))?/iu', $label, $m)) {
				continue;
			}
			$fid = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id($m[1])
				: strtoupper(trim((string)$m[1]));
			$tm = trim((string)($m[2] ?? ''));
			$seq = trim((string)($m[3] ?? '1'));
			$ekey = $fid . '|' . $tm . '|' . $seq;
			if (!isset($perField[$fid])) {
				$perField[$fid] = array('flown' => false, 'not_flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);
			}
			$perField[$fid]['touched'] = true;
			if (!isset($result['entries'][$ekey])) {
				$result['entries'][$ekey] = array('field_id' => $fid, 'time' => $tm, 'flown' => false, 'fund' => 0, 'remark' => '');
			}

			if (rkrk_is_dynamic_status_label($label)) {
				$vals = is_array($val) ? $val : array($val);
				$vals = array_map(function ($x) { return trim((string)$x); }, $vals);
				if (in_array('geflogen', $vals, true)) {
					$perField[$fid]['flown'] = true;
					$result['entries'][$ekey]['flown'] = true;
				}
				if (in_array('nicht geflogen (r)', $vals, true)) {
					$perField[$fid]['flown'] = false;
					$perField[$fid]['not_flown'] = true;
					$result['entries'][$ekey]['flown'] = false;
				}
				if (in_array('2 Kitze', $vals, true)) {
					$perField[$fid]['fund'] = max($perField[$fid]['fund'], 2);
					$result['entries'][$ekey]['fund'] = max($result['entries'][$ekey]['fund'], 2);
				} elseif (in_array('1 Kitz', $vals, true)) {
					$perField[$fid]['fund'] = max($perField[$fid]['fund'], 1);
					$result['entries'][$ekey]['fund'] = max($result['entries'][$ekey]['fund'], 1);
				} elseif (in_array('Keine Funde', $vals, true)) {
					$perField[$fid]['fund'] = max($perField[$fid]['fund'], 0);
				}
			} elseif (rkrk_is_dynamic_status_other_label($label)) {
				$txt = trim((string)$val);
				if ($txt !== '') {
					$fundFromText = 0;
					if (preg_match('/(\d+)\s*(rehkitz(?:e)?|kitz(?:e)?)/iu', $txt, $mCnt)) {
						$fundFromText = (int)$mCnt[1];
					}
					if ($fundFromText > 0) {
						$perField[$fid]['fund'] = max($perField[$fid]['fund'], $fundFromText);
						$result['entries'][$ekey]['fund'] = max($result['entries'][$ekey]['fund'], $fundFromText);
					}
					$remark = preg_replace('/^\s*\d+\s*(rehkitz(?:e)?|kitz(?:e)?)\s*[,:;\-]?\s*/iu', '', $txt);
					$remark = trim((string)$remark);
					if ($remark !== '') {
						$perField[$fid]['remark'] = $remark;
						$result['entries'][$ekey]['remark'] = $remark;
					}
				}
			}
		}

		$result['day'] = $day;
		$result['year'] = $year;
		$result['time5'] = $time5;
		$result['pilot'] = $pilot;
		$result['field_ids'] = array_keys($perField);
		$result['per_field'] = $perField;
		$result['entries'] = array_values($result['entries']);
		$result['field_time_map'] = rkrk_sync_parse_field_time_map($fieldTimeRaw);
		return $result;
	}
}

if (!function_exists('rkrk_sync_csv_read_assoc')) {
	function rkrk_sync_csv_read_assoc($path, $delimiter = ';') {
		if (!is_file($path)) {
			return array(array(), array());
		}
		$rows = array();
		$header = array();
		$fp = @fopen($path, 'r');
		if (!$fp) {
			return array(array(), array());
		}
		$lineNo = 0;
		while (($cols = fgetcsv($fp, 0, $delimiter)) !== false) {
			$lineNo++;
			if ($lineNo === 1) {
				$header = array_map(function ($h) {
					$h = (string)$h;
					$h = preg_replace('/^\xEF\xBB\xBF/u', '', $h);
					return trim($h);
				}, $cols);
				continue;
			}
			if (count($cols) === 1 && trim((string)$cols[0]) === '') {
				continue;
			}
			$row = array();
			foreach ($header as $i => $key) {
				$row[$key] = isset($cols[$i]) ? (string)$cols[$i] : '';
			}
			$rows[] = $row;
		}
		fclose($fp);
		return array($header, $rows);
	}
}

if (!function_exists('rkrk_sync_csv_write_assoc')) {
	function rkrk_sync_csv_write_assoc($path, $header, $rows, $delimiter = ';') {
		$dir = dirname($path);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$lines = array();
		$lines[] = implode($delimiter, array_map('strval', $header));
		foreach ((array)$rows as $row) {
			$out = array();
			foreach ($header as $k) {
				$v = isset($row[$k]) ? (string)$row[$k] : '';
				if (strpos($v, $delimiter) !== false || strpos($v, '"') !== false || strpos($v, "\n") !== false || strpos($v, "\r") !== false) {
					$v = '"' . str_replace('"', '""', $v) . '"';
				}
				$out[] = $v;
			}
			$lines[] = implode($delimiter, $out);
		}
		$content = implode("\r\n", $lines) . "\r\n";
		$written = @file_put_contents($path, $content, LOCK_EX);
		if ($written === false) {
			$written = @file_put_contents($path, $content);
		}
		return ($written !== false);
	}
}

if (!function_exists('rkrk_sync_append_yy_desc')) {
	function rkrk_sync_append_yy_desc($csvList, $yy) {
		$vals = array_filter(array_map('trim', explode(',', (string)$csvList)), function ($x) { return $x !== ''; });
		$vals[] = (string)$yy;
		$vals = array_values(array_unique($vals));
		usort($vals, function ($a, $b) {
			return intval($b) <=> intval($a);
		});
		return implode(',', $vals);
	}
}

if (!function_exists('rkrk_sync_update_felder_xml')) {
	function rkrk_sync_update_felder_xml($perField, $year) {
		if (function_exists('SetMissionLocation')) {
			SetMissionLocation();
		}
		global $xmlMissionLocation, $RKRKFelderfileXML, $RKRKLocalLoactionMissionFiles, $xmlFileDirectory;
		$xmlPath = '';
		$xmlFile = trim((string)($RKRKFelderfileXML ?? 'RKRKFelder.xml'));
		$xmlCandidates = array();
		$xmlBaseMission = trim((string)($xmlMissionLocation ?? ''));
		if ($xmlBaseMission !== '') {
			$xmlCandidates[] = rtrim($xmlBaseMission, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
		}
		$xmlLocal = trim((string)($RKRKLocalLoactionMissionFiles ?? ''));
		$xmlDir = trim((string)($xmlFileDirectory ?? 'xmlfiles/'));
		if ($xmlLocal !== '') {
			$xmlCandidates[] = rtrim($xmlLocal, "/\\") . DIRECTORY_SEPARATOR . trim($xmlDir, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
		}
		foreach ($xmlCandidates as $cand) {
			if ($cand !== '' && is_file($cand)) {
				$xmlPath = $cand;
				break;
			}
		}
		if (!is_file($xmlPath)) {
			return;
		}
		$raw = @file_get_contents($xmlPath);
		if ($raw === false || trim($raw) === '') {
			return;
		}
		$xml = @simplexml_load_string($raw);
		if (!$xml || !isset($xml->Feld)) {
			return;
		}
		$yy = substr((string)$year, -2);
		$changed = false;
		foreach ($xml->Feld as $feld) {
			$fid = trim((string)($feld->FeldID ?? ''));
			if ($fid === '' || !isset($perField[$fid])) {
				continue;
			}
			$st = $perField[$fid];
			if (!is_array($st)) {
				continue;
			}
			if (!empty($st['flown'])) {
				$feld->Flug = rkrk_sync_append_yy_desc((string)($feld->Flug ?? ''), $yy);
				$feld->Freigabe = 'OK';
				$changed = true;
				if (intval($st['fund'] ?? 0) > 0) {
					$feld->Funde = rkrk_sync_append_yy_desc((string)($feld->Funde ?? ''), $yy);
				}
			}
		}
		if ($changed) {
			@file_put_contents($xmlPath, $xml->asXML());
		}
	}
}

if (!function_exists('rkrk_checklist_json_response')) {
	function rkrk_checklist_json_response(array $payload, $httpCode = 200){
		$GLOBALS['RKRK_CHECKLIST_JSON_SENT'] = true;
		while (ob_get_level() > 0) { @ob_end_clean(); }
		if (!headers_sent()) {
			http_response_code((int)$httpCode);
			header('Content-Type: application/json; charset=utf-8');
		}
		$flags = JSON_UNESCAPED_UNICODE;
		if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$json = json_encode($payload, $flags);
		if ($json === false) {
			$json = json_encode(['ok' => false, 'error' => 'encode_failed'], JSON_UNESCAPED_UNICODE);
		}
		echo $json;
		exit;
	}
}

if (!function_exists('rkrk_checklist_pilot_map')) {
	function rkrk_checklist_pilot_map() {
		return array(
			'TC' => 'Thomy', 'CH' => 'Chris', 'AC' => 'Armon', 'NC' => 'Nando',
			'MF' => 'Marina', 'LF' => 'Lars', 'VP' => 'Vesa', 'MB' => 'Marc',
		);
	}
}

if (!function_exists('rkrk_checklist_pilot_name')) {
	function rkrk_checklist_pilot_name($pilotCode) {
		$map = rkrk_checklist_pilot_map();
		$code = strtoupper(trim((string)$pilotCode));
		return $map[$code] ?? $pilotCode;
	}
}

if (!function_exists('rkrk_checklist_pilot_code')) {
	function rkrk_checklist_pilot_code($pilotNameOrCode) {
		$map = rkrk_checklist_pilot_map();
		$raw = trim((string)$pilotNameOrCode);
		if ($raw === '') {
			return '';
		}
		$code = strtoupper($raw);
		if (isset($map[$code])) {
			return $code;
		}
		foreach ($map as $c => $name) {
			if (strcasecmp($name, $raw) === 0) {
				return $c;
			}
		}
		return $code;
	}
}

if (!function_exists('rkrk_fluege_sync_pilot_name')) {
	function rkrk_fluege_sync_pilot_name($pilotCode) {
		return rkrk_checklist_pilot_name($pilotCode);
	}
}

if (!function_exists('rkrk_checklist_flights_csv_file_for_year')) {
	/** Flug-CSV der aktuellen Mission (gleicher MDB-Kontext wie Checkliste/JSON). */
	function rkrk_checklist_flights_csv_file_for_year($year) {
		if (function_exists('rkrk_checklist_sync_mission_context')) {
			rkrk_checklist_sync_mission_context();
		} elseif (function_exists('SetMissionLocation')) {
			SetMissionLocation();
		}
		global $RKRKMissionSelected;
		$mdb = trim((string)($RKRKMissionSelected ?? ''));
		if (function_exists('rkrk_flights_csv_file')) {
			$info = rkrk_flights_csv_file($year);
			$info['mdb'] = $mdb;
			$info['csv_doc'] = (string)(isset($GLOBALS['csvDocLocation']) ? $GLOBALS['csvDocLocation'] : '');
			return $info;
		}
		global $csvDocLocation, $RKRKFlightsCSV;
		$y = intval($year);
		if ($y < 2000 || $y > 2100) {
			$y = intval(date('Y'));
		}
		$csvFile = $y . ($RKRKFlightsCSV ?? '_RKRKFluege.csv');
		$rel = (string)($csvDocLocation ?? '') . $csvFile;
		return array(
			'file' => $csvFile,
			'rel' => $rel,
			'path' => $rel,
			'mdb' => $mdb,
			'csv_doc' => (string)($csvDocLocation ?? ''),
		);
	}
}

/** Lesepfad: aufgelöster Pfad (identisch mit rkrk_fluege.php). */
if (!function_exists('rkrk_checklist_flights_csv_read_path')) {
	function rkrk_checklist_flights_csv_read_path($year) {
		if (function_exists('rkrk_flights_csv_resolve_path')) {
			return rkrk_flights_csv_resolve_path($year);
		}
		$info = rkrk_checklist_flights_csv_file_for_year($year);
		$path = trim((string)($info['path'] ?? ''));
		if ($path !== '' && is_file($path)) {
			return $path;
		}
		$rel = trim((string)($info['rel'] ?? ''));
		if ($rel !== '' && is_file($rel)) {
			return $rel;
		}
		return $path !== '' ? $path : $rel;
	}
}

/** Schreibpfad: aufgelöster Pfad (identisch mit rkrk_fluege.php). */
if (!function_exists('rkrk_checklist_flights_csv_write_path')) {
	function rkrk_checklist_flights_csv_write_path($year) {
		$info = rkrk_checklist_flights_csv_file_for_year($year);
		$path = trim((string)($info['path'] ?? ''));
		return $path !== '' ? $path : trim((string)($info['rel'] ?? ''));
	}
}

if (!function_exists('rkrk_checklist_flights_csv_path_for_year')) {
	function rkrk_checklist_flights_csv_path_for_year($year) {
		return rkrk_checklist_flights_csv_read_path($year);
	}
}

if (!function_exists('rkrk_checklist_flights_csv_write_targets')) {
	function rkrk_checklist_flights_csv_write_targets($year) {
		$info = rkrk_checklist_flights_csv_file_for_year($year);
		$targets = array();
		$path = trim((string)($info['path'] ?? ''));
		$rel = trim((string)($info['rel'] ?? ''));
		if ($path !== '') {
			$targets[] = $path;
		}
		if ($rel !== '' && !in_array($rel, $targets, true)) {
			$targets[] = $rel;
		}
		return $targets;
	}
}

if (!function_exists('rkrk_checklist_csv_geflogen_fund_to_checked')) {
	/** CSV-Spalten Geflogen/Fund → Checkbox-Werte der Suchresultat-Zeile. */
	function rkrk_checklist_csv_geflogen_fund_to_checked($geflogen, $fund) {
		$checked = array();
		$g = trim((string)$geflogen);
		$f = trim((string)$fund);
		if ($g === 'Ja') {
			$checked[] = 'geflogen';
		} elseif ($g === 'Nein') {
			$checked[] = 'nicht geflogen (r)';
		}
		$n = intval($f);
		if ($n >= 2) {
			$checked[] = '2 Kitze';
		} elseif ($n === 1) {
			$checked[] = '1 Kitz';
		} elseif ($g === 'Ja' && ($f === '' || $f === '0')) {
			$checked[] = 'Keine Funde';
		}
		return $checked;
	}
}

if (!function_exists('rkrk_checklist_flights_csv_load_for_fields')) {
	/**
	 * Geflogen/Fund aus YYYY_RKRKFluege.csv für Tag/Pilot laden.
	 *
	 * @return array<string, array{Geflogen:string,Fund:string,Zeit:string,Feld:string}>
	 */
	function rkrk_checklist_flights_csv_load_for_fields($year, $day, $pilot) {
		if (!function_exists('rkrk_checklist_sync_mission_context')) {
			if (function_exists('SetMissionLocation')) {
				SetMissionLocation();
			}
		} else {
			rkrk_checklist_sync_mission_context();
		}
		$csvPath = rkrk_checklist_flights_csv_read_path($year);
		if ($csvPath === '' || !is_file($csvPath)) {
			return array();
		}
		$rows = array();
		if (function_exists('rkrk_sync_csv_read_assoc')) {
			list($header, $rows) = rkrk_sync_csv_read_assoc($csvPath, ';');
			unset($header);
		}
		if (empty($rows) && function_exists('csv_to_array')) {
			$csvData = csv_to_array($csvPath, ';');
			if (is_array($csvData)) {
				$rows = $csvData;
			}
		}
		$out = array();
		$pilotCl = trim((string)$pilot);
		foreach ((array)$rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			if (!function_exists('rkrk_sync_csv_row_on_day') || !rkrk_sync_csv_row_on_day($row, $day)) {
				continue;
			}
			if ($pilotCl !== '' && function_exists('rkrk_sync_pilot_match') && !rkrk_sync_pilot_match($row['Pilot'] ?? '', $pilotCl)) {
				continue;
			}
			$fidRaw = function_exists('rkrk_checklist_extract_feld_id')
				? rkrk_checklist_extract_feld_id($row['Feld'] ?? '')
				: trim((string)($row['Feld'] ?? ''));
			if ($fidRaw === '') {
				continue;
			}
			$nk = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id($fidRaw)
				: strtoupper($fidRaw);
			$fundVal = trim((string)($row['Fund'] ?? ''));
			if ($fundVal === '') {
				$fundVal = trim((string)($row['#Funde'] ?? ''));
			}
			$out[$nk] = array(
				'Geflogen' => trim((string)($row['Geflogen'] ?? '')),
				'Fund' => $fundVal,
				'Zeit' => trim((string)($row['Zeit'] ?? '')),
				'Feld' => $fidRaw,
			);
		}
		return $out;
	}
}

if (!function_exists('rkrk_checklist_flugbuch_summary_html')) {
	function rkrk_checklist_flugbuch_summary_html(array $flightsCsvPerField) {
		if (empty($flightsCsvPerField)) {
			return '<p class="rkrk-flugbuch-empty" style="margin:0;color:#64748b;font-size:13px;">Keine Flugbuch-Einträge für Tag/Pilot in der CSV.</p>';
		}
		$html = '<ul class="rkrk-flugbuch-list" style="margin:0;padding:0 0 0 18px;">';
		foreach ($flightsCsvPerField as $fidRow => $fRow) {
			$g = trim((string)($fRow['Geflogen'] ?? ''));
			$f = trim((string)($fRow['Fund'] ?? ''));
			$gLabel = ($g === 'Ja') ? 'geflogen' : (($g === 'Nein') ? 'nicht geflogen' : '–');
			$fLabel = ($f !== '') ? $f : '0';
			$html .= '<li><code>' . safe((string)($fRow['Feld'] ?? $fidRow)) . '</code>: Geflogen '
				. safe($gLabel) . ', Fund ' . safe($fLabel) . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}
}

if (!function_exists('rkrk_checklist_render_flugbuch_panel')) {
	function rkrk_checklist_render_flugbuch_panel(array $flightsCsvPerField, $flightsCsvYear, $checklistMdb = '', $checklistDay = '', $checklistPilot = '') {
		if (!preg_match('/^\d{4}$/', (string)$flightsCsvYear)) {
			return;
		}
		$count = count($flightsCsvPerField);
		$countHint = $count > 0 ? (' · ' . $count . ' Feld' . ($count === 1 ? '' : 'er')) : '';
		$dayHint = trim((string)$checklistDay) !== '' ? (' · ' . safe($checklistDay)) : '';
		$pilotHint = trim((string)$checklistPilot) !== '' ? (' · ' . safe($checklistPilot)) : '';
		echo "<div class='rkrk-flugbuch-panel' id='rkrkFlugbuchPanel' style='margin-top:16px;border:1px solid #bfdbfe;border-radius:8px;background:#f0f7ff;overflow:hidden;'>";
		echo "<button type='button' class='rkrk-flugbuch-toggle' id='rkrkFlugbuchToggle' aria-expanded='false' aria-controls='rkrkFlugbuchBody' "
			. "style='display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;padding:10px 12px;border:0;background:transparent;cursor:pointer;font:inherit;text-align:left;color:#1e3a5f;'>";
		echo "<span><strong>Flugbuch</strong> (" . safe($flightsCsvYear . '_RKRKFluege.csv') . ")";
		if ($checklistMdb !== '') {
			echo " · Mission " . safe($checklistMdb);
		}
		echo safe($dayHint . $pilotHint . $countHint) . "</span>";
		echo "<span class='rkrk-flugbuch-chev' id='rkrkFlugbuchChev' aria-hidden='true' style='font-size:.9em;color:#4b5563;'>&#9662;</span>";
		echo "</button>";
		echo "<div class='rkrk-flugbuch-body' id='rkrkFlugbuchBody' hidden style='padding:0 12px 12px;font-size:13px;'>";
		echo "<div id='rkrkFlightsCsvSummary'>" . rkrk_checklist_flugbuch_summary_html($flightsCsvPerField) . "</div>";
		echo "</div></div>";
		echo "<script>(function(){"
			. "var t=document.getElementById('rkrkFlugbuchToggle');"
			. "var b=document.getElementById('rkrkFlugbuchBody');"
			. "var c=document.getElementById('rkrkFlugbuchChev');"
			. "if(!t||!b||!c)return;"
			. "t.addEventListener('click',function(){"
			. "var open=b.hidden;b.hidden=!open;t.setAttribute('aria-expanded',open?'true':'false');"
			. "c.innerHTML=open?'&#9652;':'&#9662;';"
			. "});"
			. "})();</script>";
	}
}

if (!function_exists('rkrk_checklist_merge_flights_csv_into_status_initial')) {
	function rkrk_checklist_merge_flights_csv_into_status_initial(array &$initial, array $csvPerField, array $fieldTimeMap = array()) {
		foreach ($csvPerField as $fid => $row) {
			if (!is_array($row)) {
				continue;
			}
			$checked = rkrk_checklist_csv_geflogen_fund_to_checked($row['Geflogen'] ?? '', $row['Fund'] ?? '');
			if (empty($checked)) {
				continue;
			}
			$matchedKey = null;
			foreach ($initial as $key => $st) {
				$parts = explode('|', (string)$key);
				$kfid = trim((string)($parts[0] ?? ''));
				if (function_exists('rkrk_sync_feld_ids_match')
					? rkrk_sync_feld_ids_match($kfid, $fid)
					: (strcasecmp($kfid, (string)$fid) === 0)) {
					$matchedKey = (string)$key;
					break;
				}
			}
			if ($matchedKey === null) {
				$time = '';
				if (isset($fieldTimeMap[$fid])) {
					$time = trim((string)$fieldTimeMap[$fid]);
				}
				if ($time === '' && isset($fieldTimeMap[$row['Feld'] ?? ''])) {
					$time = trim((string)$fieldTimeMap[$row['Feld']]);
				}
				if ($time === '') {
					$time = trim((string)($row['Zeit'] ?? ''));
				}
				$matchedKey = $fid . '|' . $time . '|1';
				if (!isset($initial[$matchedKey])) {
					$initial[$matchedKey] = array('checked' => array(), 'other' => '');
				}
			}
			if (empty($initial[$matchedKey]['checked'])) {
				$initial[$matchedKey]['checked'] = $checked;
			}
		}
	}
}

if (!function_exists('rkrk_checklist_funde_text_from_csv')) {
	function rkrk_checklist_funde_text_from_csv(array $csvPerField) {
		$chunks = array();
		foreach ($csvPerField as $fid => $row) {
			if (!is_array($row)) {
				continue;
			}
			$n = intval($row['Fund'] ?? 0);
			$g = trim((string)($row['Geflogen'] ?? ''));
			if ($g === '' && $n <= 0) {
				continue;
			}
			$label = trim((string)($row['Feld'] ?? $fid));
			if ($n >= 2) {
				$chunks[] = $label . ': 2 Kitze';
			} elseif ($n === 1) {
				$chunks[] = $label . ': 1 Kitz';
			} elseif ($g === 'Ja') {
				$chunks[] = $label . ': keine Funde';
			} elseif ($g === 'Nein') {
				$chunks[] = $label . ': nicht geflogen';
			}
		}
		return count($chunks) ? implode('; ', $chunks) : '';
	}
}

if (!function_exists('rkrk_sync_per_field_get')) {
	function rkrk_sync_per_field_get(array $perField, $fid) {
		$nk = function_exists('rkrk_checklist_normalize_feld_id')
			? rkrk_checklist_normalize_feld_id($fid)
			: strtoupper(trim((string)$fid));
		if ($nk === '') {
			return null;
		}
		if (isset($perField[$nk])) {
			return $perField[$nk];
		}
		foreach ($perField as $k => $v) {
			if (function_exists('rkrk_sync_feld_ids_match')
				? rkrk_sync_feld_ids_match($k, $nk)
				: (strcasecmp(
					function_exists('rkrk_checklist_normalize_feld_id') ? rkrk_checklist_normalize_feld_id($k) : trim((string)$k),
					$nk
				) === 0)) {
				return $v;
			}
		}
		return null;
	}
}

if (!function_exists('rkrk_checklist_merge_dynamic_status_into_fields')) {
	function rkrk_checklist_merge_dynamic_status_into_fields(array &$fields, $rawJson = null) {
		$raw = ($rawJson !== null) ? trim((string)$rawJson) : trim((string)($_POST['dynamic_status_state'] ?? ''));
		if ($raw === '') {
			return;
		}
		$dyn = json_decode($raw, true);
		if (!is_array($dyn)) {
			return;
		}
		if (!isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) {
			$fields['Suchresultat'] = array();
		}
		foreach ($dyn as $fieldId => $row) {
			if (!is_array($row)) {
				continue;
			}
			$fid = trim((string)($row['field_id'] ?? $fieldId));
			$statusKey = trim((string)($row['status_label'] ?? ''));
			$otherKey = trim((string)($row['other_label'] ?? ''));
			if ($statusKey === '' || $otherKey === '') {
				$nfid = function_exists('rkrk_checklist_normalize_feld_id')
					? rkrk_checklist_normalize_feld_id($fid)
					: trim((string)$fid);
				if ($nfid === '' || (function_exists('rkrk_checklist_is_valid_feld_id') && !rkrk_checklist_is_valid_feld_id($nfid))) {
					continue;
				}
				$inner = $nfid;
				$time = trim((string)($row['time'] ?? ''));
				$seq = trim((string)($row['seq'] ?? '1'));
				if ($time !== '') {
					$inner .= ' | ' . $time;
				}
				$inner .= ' | ' . ($seq !== '' ? $seq : '1');
				if ($statusKey === '') {
					$statusKey = 'Status und Suchresultate (' . $inner . ')';
				}
				if ($otherKey === '') {
					$otherKey = 'Status und Suchresultate andere (' . $inner . ')';
				}
			}
			$checked = array();
			if (isset($row['checked']) && is_array($row['checked'])) {
				$checked = array_values(array_filter(array_map(function ($v) {
					return trim((string)$v);
				}, $row['checked']), function ($v) {
					return $v !== '';
				}));
			}
			$fields['Suchresultat'][$statusKey] = $checked;
			$fields['Suchresultat'][$otherKey] = trim((string)($row['other'] ?? ''));
			if (trim((string)($row['flugplan'] ?? '')) !== '') {
				$fields['Suchresultat']['Flugplan (' . $fid . ')'] = trim((string)$row['flugplan']);
			}
			if (trim((string)($row['startpoint'] ?? '')) !== '') {
				$fields['Suchresultat']['Startpunkt (' . $fid . ')'] = trim((string)$row['startpoint']);
			}
			if (trim((string)($row['startpoint_google'] ?? '')) !== '') {
				$fields['Suchresultat']['Startpunkt Google (' . $fid . ')'] = trim((string)$row['startpoint_google']);
			}
		}
	}
}

if (!function_exists('rkrk_checklist_augment_per_field_from_dynamic_json')) {
	/** Suchresultat-Status direkt aus dynamic_status_state (Fallback wenn POST-fields unvollständig). */
	function rkrk_checklist_augment_per_field_from_dynamic_json(array $perField, $rawJson) {
		$raw = trim((string)$rawJson);
		if ($raw === '') {
			return $perField;
		}
		$dyn = json_decode($raw, true);
		if (!is_array($dyn)) {
			return $perField;
		}
		foreach ($dyn as $row) {
			if (!is_array($row)) {
				continue;
			}
			$fid = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id((string)($row['field_id'] ?? ''))
				: strtoupper(trim((string)($row['field_id'] ?? '')));
			if ($fid === '') {
				continue;
			}
			if (!isset($perField[$fid]) || !is_array($perField[$fid])) {
				$perField[$fid] = array('flown' => false, 'not_flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);
			}
			$perField[$fid]['touched'] = true;
			$checked = (isset($row['checked']) && is_array($row['checked'])) ? $row['checked'] : array();
			$checked = array_map(function ($v) { return trim((string)$v); }, $checked);
			if (in_array('geflogen', $checked, true)) {
				$perField[$fid]['flown'] = true;
			}
			if (in_array('nicht geflogen (r)', $checked, true)) {
				$perField[$fid]['flown'] = false;
				$perField[$fid]['not_flown'] = true;
			}
			if (in_array('2 Kitze', $checked, true)) {
				$perField[$fid]['fund'] = max(intval($perField[$fid]['fund'] ?? 0), 2);
			} elseif (in_array('1 Kitz', $checked, true)) {
				$perField[$fid]['fund'] = max(intval($perField[$fid]['fund'] ?? 0), 1);
			}
			$other = trim((string)($row['other'] ?? ''));
			if ($other !== '') {
				$fundFromText = 0;
				if (preg_match('/(\d+)\s*(rehkitz(?:e)?|kitz(?:e)?)/iu', $other, $mCnt)) {
					$fundFromText = (int)$mCnt[1];
				}
				if ($fundFromText > 0) {
					$perField[$fid]['fund'] = max(intval($perField[$fid]['fund'] ?? 0), $fundFromText);
				}
				$remark = preg_replace('/^\s*\d+\s*(rehkitz(?:e)?|kitz(?:e)?)\s*[,:;\-]?\s*/iu', '', $other);
				$remark = trim((string)$remark);
				if ($remark !== '') {
					$perField[$fid]['remark'] = $remark;
				}
			}
		}
		return function_exists('rkrk_sync_merge_per_field_map')
			? rkrk_sync_merge_per_field_map($perField)
			: $perField;
	}
}

if (!function_exists('rkrk_checklist_flights_csv_backup')) {
	function rkrk_checklist_flights_csv_backup($csvPath, $csvFile) {
		global $historyfilelocation;
		if (!is_file($csvPath) || trim((string)($historyfilelocation ?? '')) === '') {
			return;
		}
		$histDir = $historyfilelocation;
		if (!is_dir($histDir)) {
			@mkdir($histDir, 0777, true);
		}
		$newfile = $histDir . date('YmdH') . '00_' . basename($csvFile);
		@copy($csvPath, $newfile);
	}
}

if (!function_exists('rkrk_checklist_fund_display')) {
	function rkrk_checklist_fund_display($fundCount) {
		$n = intval($fundCount);
		if ($n <= 0) {
			return '';
		}
		return (string)$n;
	}
}

/**
 * Checklisten-Felder in passende Zeilen der Flug-CSV schreiben.
 *
 * @return array{ok:bool,updated:int,matched:int,message:string,year?:string,csv?:string}
 */
function rkrk_checklist_store_fields_to_flights_csv(array $fields) {
	if (!function_exists('rkrk_sync_extract_checklist_state')) {
		return array('ok' => false, 'updated' => 0, 'matched' => 0, 'message' => 'CSV-Helfer nicht geladen.');
	}
	if (function_exists('rkrk_checklist_sync_mission_context')) {
		rkrk_checklist_sync_mission_context();
	} elseif (function_exists('SetMissionLocation')) {
		SetMissionLocation();
	}
	if (function_exists('rkrk_checklist_merge_dynamic_status_into_fields')) {
		rkrk_checklist_merge_dynamic_status_into_fields($fields);
	}

	$state = rkrk_sync_extract_checklist_state($fields);
	$day = (string)($state['day'] ?? '');
	$year = (string)($state['year'] ?? '');
	$yearOverride = trim((string)($_POST['flights_csv_year'] ?? $_GET['flights_csv_year'] ?? ''));
	if (preg_match('/^\d{4}$/', $yearOverride)) {
		$year = $yearOverride;
	}
	if ($day === '' || $year === '') {
		return array('ok' => false, 'updated' => 0, 'matched' => 0, 'message' => 'Kein Datum in der Checkliste.');
	}

	$csvInfo = function_exists('rkrk_checklist_flights_csv_file_for_year')
		? rkrk_checklist_flights_csv_file_for_year($year)
		: array('rel' => '', 'abs' => '', 'file' => $year . '_RKRKFluege.csv', 'mdb' => '', 'csv_doc' => '');
	$csvFile = (string)($csvInfo['file'] ?? ($year . '_RKRKFluege.csv'));
	$csvPath = rkrk_checklist_flights_csv_read_path($year);
	$writePath = rkrk_checklist_flights_csv_write_path($year);
	$writeTargets = array($csvPath);
	if ($writePath !== '' && $writePath !== $csvPath) {
		$writeTargets[] = $writePath;
	}
	$relCsv = trim((string)($csvInfo['rel'] ?? ''));
	if ($relCsv !== '' && $relCsv !== $csvPath && !in_array($relCsv, $writeTargets, true)) {
		$writeTargets[] = $relCsv;
	}
	$missionMdb = (string)($csvInfo['mdb'] ?? '');

	if ($csvPath === '' || !is_file($csvPath)) {
		return array(
			'ok' => false,
			'updated' => 0,
			'matched' => 0,
			'message' => 'Flug-CSV nicht gefunden (' . $csvFile . ').',
			'year' => $year,
			'csv' => $writePath !== '' ? $writePath : (string)($csvInfo['rel'] ?? ''),
			'csv_rel' => (string)($csvInfo['rel'] ?? ''),
			'mdb' => $missionMdb,
			'day' => $day,
			'pilot' => trim((string)($state['pilot'] ?? '')),
		);
	}

	list($header, $rows) = rkrk_sync_csv_read_assoc($csvPath, ';');
	if (function_exists('csv_to_array') && empty($rows)) {
		$csvData = csv_to_array($csvPath, ';');
		if (is_array($csvData) && !empty($csvData)) {
			$rows = $csvData;
			$first = reset($csvData);
			if (is_array($first)) {
				$header = array_keys($first);
			}
		}
	}
	if (empty($header)) {
		$header = explode(';', 'FlugID;Datum;Zeit;Pilot;Feld;Kontrollflug;Geflogen;Fund;UAV#;Felder;Jaeger;Kontaktnummer;Landwirt;Bemerkung;Flugzeit;AnzFelder;#Funde;Einsatztag;GrundstückID;Gde;JG;Warnung;Reg;Flaeche;Status');
	}

	$perField = rkrk_sync_merge_per_field_map(is_array($state['per_field'] ?? null) ? $state['per_field'] : array());
	$rawDynState = trim((string)($_POST['dynamic_status_state'] ?? ''));
	if ($rawDynState !== '' && function_exists('rkrk_checklist_augment_per_field_from_dynamic_json')) {
		$perField = rkrk_checklist_augment_per_field_from_dynamic_json($perField, $rawDynState);
	}
	$timeMap = is_array($state['field_time_map'] ?? null) ? $state['field_time_map'] : array();
	$pilotCl = trim((string)($state['pilot'] ?? ''));
	$updated = 0;
	$matched = 0;
	$rowsOnDay = 0;

	$applyRowUpdate = function ($idx, array &$rows) use ($perField, $timeMap, $state, &$updated, &$matched) {
		$fid = function_exists('rkrk_checklist_extract_feld_id')
			? rkrk_checklist_extract_feld_id($rows[$idx]['Feld'] ?? '')
			: '';
		if ($fid === '' && function_exists('rkrk_sync_extract_field_ids')) {
			$feldIds = rkrk_sync_extract_field_ids($rows[$idx]['Felder'] ?? '');
			if (count($feldIds) === 1) {
				$fid = (string)$feldIds[0];
			}
		}
		if ($fid === '') {
			$fid = function_exists('rkrk_checklist_normalize_feld_id')
				? rkrk_checklist_normalize_feld_id($rows[$idx]['Feld'] ?? '')
				: strtoupper(trim((string)($rows[$idx]['Feld'] ?? '')));
		}
		$pf = function_exists('rkrk_sync_per_field_get')
			? rkrk_sync_per_field_get($perField, $fid)
			: ($perField[$fid] ?? null);
		if ($fid === '' || !is_array($pf)) {
			return false;
		}
		$fundN = intval($pf['fund'] ?? 0);
		$flown = !empty($pf['flown']);
		$notFlown = !empty($pf['not_flown']);
		if (!$flown && $fundN > 0) {
			$flown = true;
		}
		$hasResult = !empty($pf['touched']) || $flown || $notFlown || $fundN > 0 || trim((string)($pf['remark'] ?? '')) !== '';
		if (!$hasResult) {
			return false;
		}
		$matched++;
		if ($flown) {
			$rows[$idx]['Geflogen'] = 'Ja';
		} elseif ($notFlown) {
			$rows[$idx]['Geflogen'] = 'Nein';
		}
		$fundStr = rkrk_checklist_fund_display($fundN);
		$rows[$idx]['Fund'] = $fundStr;
		$rows[$idx]['#Funde'] = $fundStr;
		$zeitVal = '';
		foreach ($timeMap as $mapFid => $mapTime) {
			if (function_exists('rkrk_sync_feld_ids_match')
				? rkrk_sync_feld_ids_match($mapFid, $fid)
				: (strcasecmp((string)$mapFid, (string)$fid) === 0)) {
				$zeitVal = trim((string)$mapTime);
				break;
			}
		}
		if ($zeitVal !== '' && trim((string)($rows[$idx]['Zeit'] ?? '')) === '') {
			$rows[$idx]['Zeit'] = $zeitVal;
		}
		if (trim((string)($state['time5'] ?? '')) !== '' && trim((string)($rows[$idx]['Zeit'] ?? '')) === '' && $zeitVal === '') {
			$rows[$idx]['Zeit'] = $state['time5'];
		}
		$remark = trim((string)($pf['remark'] ?? ''));
		if ($remark !== '') {
			$old = trim((string)($rows[$idx]['Bemerkung'] ?? ''));
			if ($old === '') {
				$rows[$idx]['Bemerkung'] = $remark;
			} elseif (mb_strpos($old, $remark) === false) {
				$rows[$idx]['Bemerkung'] = $old . ' | ' . $remark;
			}
		}
		$updated++;
		return true;
	};

	foreach ($rows as $idx => $row) {
		if (!rkrk_sync_csv_row_on_day($row, $day)) {
			continue;
		}
		$rowsOnDay++;
		if ($pilotCl !== '' && function_exists('rkrk_sync_pilot_match')) {
			if (!rkrk_sync_pilot_match($row['Pilot'] ?? '', $pilotCl)) {
				continue;
			}
		}
		$applyRowUpdate($idx, $rows);
	}

	if ($updated === 0 && $rowsOnDay > 0) {
		foreach ($rows as $idx => $row) {
			if (!rkrk_sync_csv_row_on_day($row, $day)) {
				continue;
			}
			$applyRowUpdate($idx, $rows);
		}
	}

	if ($updated === 0) {
		$touchedFields = array();
		foreach ($perField as $fid => $pf) {
			if (!is_array($pf)) {
				continue;
			}
			if (!empty($pf['touched']) || !empty($pf['flown']) || intval($pf['fund'] ?? 0) > 0) {
				$touchedFields[] = $fid;
			}
		}
		$msg = ($rowsOnDay === 0)
			? ('Keine Flugzeilen am ' . $day . ' in der CSV.')
			: 'Keine passenden Flugzeilen für Tag/Pilot/Feld# gefunden.';
		return array(
			'ok' => false,
			'updated' => 0,
			'matched' => $matched,
			'message' => $msg,
			'year' => $year,
			'csv' => (string)($csvInfo['rel'] ?? $csvPath),
			'mdb' => $missionMdb,
			'day' => $day,
			'pilot' => $pilotCl,
			'fields' => $touchedFields,
			'rows_on_day' => $rowsOnDay,
		);
	}

	rkrk_checklist_flights_csv_backup($writePath !== '' ? $writePath : $csvPath, $csvFile);
	$writeOk = false;
	$writtenPaths = array();
	foreach ($writeTargets as $target) {
		$written = false;
		if (function_exists('rkrk_flights_csv_write_rows')) {
			$written = rkrk_flights_csv_write_rows($target, $header, $rows, ';');
		}
		if (!$written) {
			$written = rkrk_sync_csv_write_assoc($target, $header, $rows, ';');
		}
		if ($written) {
			$writeOk = true;
			$writtenPaths[] = $target;
		}
	}
	if (!$writeOk) {
		return array(
			'ok' => false,
			'updated' => 0,
			'matched' => $matched,
			'message' => 'CSV konnte nicht geschrieben werden.',
			'year' => $year,
			'csv' => $writePath !== '' ? $writePath : $csvPath,
			'mdb' => $missionMdb,
		);
	}

	if (function_exists('rkrk_sync_update_felder_xml')) {
		rkrk_sync_update_felder_xml($perField, $year);
	}

	$flightsSummary = function_exists('rkrk_checklist_flights_csv_load_for_fields')
		? rkrk_checklist_flights_csv_load_for_fields($year, $day, $pilotCl)
		: array();

	return array(
		'ok' => true,
		'updated' => $updated,
		'matched' => $matched,
		'message' => $updated . ' Flugzeile(n) in ' . basename($csvFile) . ' gespeichert (Mission ' . $missionMdb . ').',
		'year' => $year,
		'csv' => $writtenPaths[0] ?? $writePath,
		'csv_read' => $csvPath,
		'csv_written' => $writtenPaths,
		'csv_rel' => (string)($csvInfo['rel'] ?? ''),
		'mdb' => $missionMdb,
		'day' => $day,
		'pilot' => $pilotCl,
		'flights_summary' => $flightsSummary,
	);
}

	$mode = $_GET['mode'] ?? 'list';
	$isApiMode = in_array($mode, ['templatefields', 'weather', 'saveweatherclient', 'backfillweatherrecent', 'backfillweatherbyid', 'exportjson', 'templateexport', 'templateprint'], true)
		|| ($mode === 'save' && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && (
			(($_POST['ajax'] ?? '') === '1') || (($_GET['ajax'] ?? '') === '1')
		))
		|| ($mode === 'importjson' && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'))
		|| ($mode === 'templateimport' && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'));
	$isAjaxSaveRequest = ($mode === 'save' && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && (
		(($_POST['ajax'] ?? '') === '1') || (($_GET['ajax'] ?? '') === '1')
	));
	if ($isApiMode) {
		$GLOBALS['RKRK_AJAX_JSON_MODE'] = true;
		global $AccessDebugOutput;
		$AccessDebugOutput = false;
		@ini_set('display_errors', '0');
		register_shutdown_function(function () {
			if (!empty($GLOBALS['RKRK_CHECKLIST_JSON_SENT'])) {
				return;
			}
			if (empty($GLOBALS['RKRK_AJAX_JSON_MODE'])) {
				return;
			}
			$err = error_get_last();
			if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
				if (function_exists('rkrk_checklist_json_response')) {
					rkrk_checklist_json_response([
						'ok' => false,
						'error' => 'server_error',
						'message' => $err['message'] ?? 'fatal',
					], 500);
				}
			}
		});
	}
	CheckIPandExit();
	GrantAccess();
	global $LoggedOn, $EnableAccessControl;
	if ($isAjaxSaveRequest && $EnableAccessControl && empty($LoggedOn)) {
		rkrk_checklist_json_response(['ok' => false, 'error' => 'not_logged_in'], 401);
	}
	if (!$isApiMode) {
		headerpage("Drohnenrehkitzrettung.ch", false);
		$currentAccessLevel = '';
		if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
			$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
		} elseif (isset($InputUserInfo['Login']['Level'])) {
			$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
		}
		$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
		$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
		if ($showTopMenu) {
			// Render navigation exactly once for checklist page.
			CreateTopMenu();
			echo "<style>#navDemo{display:none !important;}</style>";
			echo "<br><br>";
		}
	}

//Write to LOGfile
if (!$isApiMode) {
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "","","","");
}

if (!$isApiMode) {
	ob_start();
}

$mode = $_GET['mode'] ?? 'list';
if ($mode === 'templatefields') {
    templateFieldsApi();
}

if (!$isApiMode) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
//session_start();

/* MODE SOFORT LESEN */
$mode = $_GET['mode'] ?? 'list';

/* API MODES: DÜRFEN KEIN HTML SEHEN */
if ($mode === 'templatefields') {
    templateFieldsApi(); // gibt JSON aus und exit
}

/* ERST JETZT HTML */
/*
headerpage("Digitale Checkliste", false);
?>
<body>
<?
*/

/* ---------------- FOLDER SETUP ---------------- */
$TEMPLATE_FOLDER  = $RKRKLocalLoactionMissionFiles . "_checklists/templates";
$JSON_FOLDER      = $RKRKLocalLoactionMissionFiles . "_checklists/json";

if (!is_dir($TEMPLATE_FOLDER)) mkdir($TEMPLATE_FOLDER, 0777, true);
if (!is_dir($JSON_FOLDER)) mkdir($JSON_FOLDER, 0777, true);

function rkrk_checklist_resolve_folder_path($relPath) {
    $relPath = trim((string)$relPath);
    if ($relPath === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z]:[\/\\\\]/', $relPath) || ($relPath[0] === '/' || $relPath[0] === '\\')) {
        return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relPath);
    }
    $combined = __DIR__ . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relPath);
    $parent = dirname($combined);
    $resolvedParent = @realpath($parent);
    if ($resolvedParent !== false) {
        return $resolvedParent . DIRECTORY_SEPARATOR . basename($combined);
    }
    return $combined;
}

function rkrk_checklist_sync_mission_context() {
	global $RKRKMissionSelected;
	if (!function_exists('SetMissionLocation')) {
		return trim((string)($RKRKMissionSelected ?? ''));
	}
	if (!empty($_POST['MDB'])) {
		$_GET['MDB'] = trim((string)$_POST['MDB']);
	}
	$mdb = trim((string)($_GET['MDB'] ?? ''));
	if (strcasecmp($mdb, 'M3T') === 0) {
		$_GET['MDB'] = '3T';
	}
	SetMissionLocation();
	return trim((string)($RKRKMissionSelected ?? ''));
}

function rkrk_checklist_refresh_json_folder() {
    global $JSON_FOLDER, $RKRKLocalLoactionMissionFiles;
    rkrk_checklist_sync_mission_context();
    $JSON_FOLDER = rkrk_checklist_resolve_folder_path($RKRKLocalLoactionMissionFiles . '_checklists/json');
    if (!is_dir($JSON_FOLDER)) {
        @mkdir($JSON_FOLDER, 0777, true);
    }
    return $JSON_FOLDER;
}

if (!$isApiMode) {
    $_rkrk_chk_toolbar = __DIR__ . '/assess/rkrk_checklist_toolbar_inc.php';
    if (is_file($_rkrk_chk_toolbar)) {
        require_once $_rkrk_chk_toolbar;
    }
    $_rkrk_tpl_csv = __DIR__ . '/assess/rkrk_template_csv_inc.php';
    if (is_file($_rkrk_tpl_csv)) {
        require_once $_rkrk_tpl_csv;
    }
}

/* ---------------- NORMALIZE KEYS (normalizeKey → inline above) ---------------- */
function getPresetForLabel($label) {
    $target = normalizeKey($label);

    foreach ($_GET as $k => $v) {
        if (normalizeKey($k) === $target) {
            return $v;
        }
    }

    return null; // no matching preset
}

function rkrk_checklist_default_date_ymd(){
    return date('Y-m-d', strtotime('+1 day'));
}

function rkrk_checklist_default_datetime_local(){
    return rkrk_checklist_default_date_ymd() . 'T' . date('H:i');
}

function rkrk_status_multi_type(){
    return "multi:geplant|auf RC/geprüft|geflogen|nicht geflogen (r)|Keine Funde|1 Kitz|2 Kitze|andere";
}

function rkrk_is_dynamic_flightplan_label($label){
    return preg_match('/^Flugplan\s*\([^)]+\)$/u', trim((string)$label)) === 1;
}

function rkrk_is_dynamic_startpoint_label($label){
    return preg_match('/^Startpunkt\s*\([^)]+\)$/u', trim((string)$label)) === 1;
}

function rkrk_is_dynamic_startpoint_google_label($label){
    return preg_match('/^Startpunkt Google\s*\([^)]+\)$/u', trim((string)$label)) === 1;
}

/** Dynamische Zeilen pro Feld# (gleiche Logik wie Feld-Meta / PDF-Warnungen). */
function rkrk_label_is_per_feld_dynamic_suchresultat($label){
    return rkrk_is_dynamic_status_label($label)
        || rkrk_is_dynamic_status_other_label($label)
        || rkrk_is_dynamic_flightplan_label($label)
        || rkrk_is_dynamic_startpoint_label($label)
        || rkrk_is_dynamic_startpoint_google_label($label);
}

/** Feld-ID-Helfer (regex/extract → inline above) */
function rkrk_checklist_normalize_feld_id($text){
    $id = rkrk_checklist_extract_feld_id($text);
    return $id !== '' ? $id : trim((string)$text);
}

function rkrk_checklist_is_valid_feld_id($text){
    $text = trim((string)$text);
    if ($text === '') return false;
    return preg_match(rkrk_checklist_feld_id_preg('^'), $text) === 1;
}

/** Erste Feld-ID „JG-Nummer“ aus dem Klammerinhalt (z. B. „ZG4-056 | 10:30 | 1“ → „ZG4-056“). */
function rkrk_extract_feld_id_from_dynamic_label($label){
    $t = trim((string)$label);
    if ($t === '') return '';
    if (!preg_match('/\(([^)]*)\)\s*$/u', $t, $m)) return '';
    return rkrk_checklist_extract_feld_id((string)$m[1]);
}

/** Gespeicherte dynamische Suchresultat-Zeilen (nicht als Formularfelder rendern). */
function rkrk_checklist_label_is_suchresultat_field_storage($label){
    return rkrk_is_dynamic_status_label($label)
        || rkrk_is_dynamic_status_other_label($label)
        || rkrk_is_dynamic_flightplan_label($label)
        || rkrk_is_dynamic_startpoint_label($label)
        || rkrk_is_dynamic_startpoint_google_label($label);
}

/**
 * Suchresultat-Blöcke pro Feld# aus gespeicherten Labels (Status / Funde / andere).
 * @return array<string, array>
 */
function rkrk_checklist_collect_suchresultat_blocks(array $sr, $onlyWithContent = false){
    $blocks = [];
    $ord = 0;
    foreach ($sr as $lbl => $val) {
        $label = trim((string)$lbl);
        if (!preg_match('/^Status und Suchresultate(?: andere)? \(([^)]+)\)$/u', $label, $mInner)) {
            continue;
        }
        $inner = trim((string)$mInner[1]);
        if (!preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([0-2]?\d:[0-5]\d))?(?:\s*\|\s*(\d+))?/iu', $inner, $m)) {
            continue;
        }
        $fid = $m[1];
        $ftime = trim((string)($m[2] ?? ''));
        $seq = trim((string)($m[3] ?? '1'));
        $key = $fid . '|' . $ftime . '|' . $seq;
        if (!isset($blocks[$key])) {
            $ord++;
            $blocks[$key] = [
                'ord' => $ord,
                'fid' => $fid,
                'time' => $ftime,
                'seq' => $seq,
                'status' => [],
                'funde' => [],
                'other' => '',
            ];
        }
        $isOther = (mb_strpos($label, ' andere ') !== false);
        if ($isOther) {
            $blocks[$key]['other'] = trim(is_array($val) ? implode(', ', array_map('strval', $val)) : (string)$val);
        } else {
            $vals = is_array($val) ? $val : [$val];
            $vals = array_values(array_filter(array_map(static function ($x) {
                return trim((string)$x);
            }, $vals), static function ($x) {
                return $x !== '';
            }));
            $statusSet = ['geplant', 'auf RC', 'geprüft', 'auf RC/geprüft', 'geflogen', 'nicht geflogen', 'nicht geflogen (r)'];
            $fundeSet = ['Keine Funde', '1 Kitz', '2 Kitze', 'andere'];
            foreach ($vals as $v1) {
                if (in_array($v1, $statusSet, true) && !in_array($v1, $blocks[$key]['status'], true)) {
                    $blocks[$key]['status'][] = $v1;
                }
                if (in_array($v1, $fundeSet, true) && !in_array($v1, $blocks[$key]['funde'], true)) {
                    $blocks[$key]['funde'][] = $v1;
                }
            }
        }
    }
    if ($onlyWithContent) {
        foreach ($blocks as $k => $b) {
            $hasStatus = !empty($b['status']);
            $hasFunde = !empty($b['funde']);
            $hasOther = (trim((string)($b['other'] ?? '')) !== '');
            if (!$hasStatus && !$hasFunde && !$hasOther) {
                unset($blocks[$k]);
            }
        }
    }
    uasort($blocks, static function ($a, $b) {
        return ($a['ord'] ?? 0) <=> ($b['ord'] ?? 0);
    });
    return $blocks;
}

/** Initialzustand für dynamische Suchresultat-Zeilen (Bearbeiten + Readonly). */
function rkrk_checklist_build_dynamic_status_initial(array $srFields){
    $dynamicStatusInitial = [];
    foreach ($srFields as $lbl => $val) {
        $labelText = trim((string)$lbl);
        if ($labelText === '') continue;

        $fieldId = '';
        $fieldTime = '';
        $fieldSeq = '1';
        if (rkrk_is_dynamic_status_label($labelText) || rkrk_is_dynamic_status_other_label($labelText)) {
            if (preg_match('/\((' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([0-2]?\d:[0-5]\d))?(?:\s*\|\s*(\d+))?/iu', $labelText, $mId)) {
                $fieldId = $mId[1];
                $fieldTime = trim((string)($mId[2] ?? ''));
                $fieldSeq = trim((string)($mId[3] ?? '1'));
            }
        } elseif (preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')\b/iu', $labelText, $mLegacy)) {
            $fieldId = $mLegacy[1];
        }
        if ($fieldId === '') continue;
        $fieldKey = $fieldId . '|' . $fieldTime . '|' . $fieldSeq;

        if (!isset($dynamicStatusInitial[$fieldKey])) {
            $dynamicStatusInitial[$fieldKey] = ['checked' => [], 'other' => ''];
        }
        if (rkrk_is_dynamic_status_label($labelText)) {
            $vals = is_array($val) ? $val : [$val];
            $vals = array_values(array_filter(array_map(static function ($x) {
                return trim((string)$x);
            }, $vals), static function ($x) {
                return $x !== '';
            }));
            $dynamicStatusInitial[$fieldKey]['checked'] = $vals;
        } elseif (rkrk_is_dynamic_status_other_label($labelText)) {
            $dynamicStatusInitial[$fieldKey]['other'] = trim((string)$val);
        }

        // Alias ohne Uhrzeit (JS-Instanz kann zuerst fid||seq ohne Zeit haben)
        $altKey = $fieldId . '||' . $fieldSeq;
        if ($altKey !== $fieldKey && !isset($dynamicStatusInitial[$altKey])) {
            $dynamicStatusInitial[$altKey] = [
                'checked' => $dynamicStatusInitial[$fieldKey]['checked'],
                'other' => $dynamicStatusInitial[$fieldKey]['other'],
            ];
        }
    }
    return $dynamicStatusInitial;
}

/** HH:MM für Feldtitel (keine Feld-ID wie ZG4-056). */
function rkrk_checklist_flugzeit_for_title($raw){
    $t = trim((string)$raw);
    if ($t === '') return '';
    if (preg_match(rkrk_checklist_feld_id_preg('^'), $t)) return '';
    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $t, $m)) {
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    }
    if (preg_match('/(\d{1,2}):(\d{2})/', $t, $m)) {
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    }
    return '';
}

function rkrk_checklist_field_title_with_flugzeit($fid, $name, $timeRaw){
    $title = trim((string)$fid);
    $name = trim((string)$name);
    if ($name !== '') $title .= ' - ' . $name;
    $ft = rkrk_checklist_flugzeit_for_title($timeRaw);
    if ($ft !== '') return $ft . 'h ' . $title;
    return $title;
}

/** Fehlende Uhrzeiten in Blöcken aus „Zeit zum Feld“ ergänzen. */
function rkrk_checklist_apply_field_times_to_blocks(array $blocks, $zeitZumFeldRaw){
    $map = rkrk_sync_parse_field_time_map($zeitZumFeldRaw);
    if (!$map) return $blocks;
    $queues = [];
    foreach ($map as $fid => $t) {
        $ft = rkrk_checklist_flugzeit_for_title($t);
        if ($ft === '') continue;
        if (!isset($queues[$fid])) $queues[$fid] = [];
        $queues[$fid][] = $ft;
    }
    foreach ($blocks as $k => $b) {
        if (trim((string)($b['time'] ?? '')) !== '') continue;
        $fid = trim((string)($b['fid'] ?? ''));
        if ($fid === '' || empty($queues[$fid])) continue;
        $blocks[$k]['time'] = array_shift($queues[$fid]);
    }
    return $blocks;
}

/** Reihenfolge der gespeicherten Feld-Instanzen (fid|time|seq). */
function rkrk_checklist_saved_instance_order(array $srFields){
    $blocks = rkrk_checklist_collect_suchresultat_blocks($srFields, false);
    $order = [];
    foreach ($blocks as $b) {
        $fid = trim((string)($b['fid'] ?? ''));
        if ($fid === '') continue;
        $order[] = $fid . '|' . trim((string)($b['time'] ?? '')) . '|' . trim((string)($b['seq'] ?? '1'));
    }
    return $order;
}

/** HTML für NGT-Icons (PicSmall), wie rkrk_pdf_fluege.php / rkrk_echo_nachflugzone_title_icons. */
function rkrk_render_nachflugzone_title_icons_html($warnung, $iconHeightPx = 18){
    $w = trim((string)$warnung);
    if ($w === '' || !function_exists('rkrk_echo_nachflugzone_title_icons')) {
        return '';
    }
    ob_start();
    rkrk_echo_nachflugzone_title_icons($w, $iconHeightPx);
    return (string)ob_get_clean();
}

/** Flugzonen-Icons (z.B. CTR + NGT1/2/3) direkt am Titel. */
function rkrk_render_flugzone_title_icons_html($warnung, $iconHeightPx = 18){
    $w = trim((string)$warnung);
    if ($w === '') return '';
    global $OptionWarning, $IconLoc;
    if (!is_array($OptionWarning)) return '';
    if (!is_string($IconLoc) || trim($IconLoc) === '') return '';
    $h = max(16, intval($iconHeightPx));
    $html = '';
    foreach (array('CTR', 'NGT1', 'NGT2', 'NGT3') as $k) {
        if (!isset($OptionWarning[$k])) continue;
        if (!like((string)$w, $k)) continue;
        $pic = !empty($OptionWarning[$k]['PicSmall']) ? $OptionWarning[$k]['PicSmall'] : ($OptionWarning[$k]['Pic'] ?? '');
        if ($pic === '') continue;
        $text = htmlspecialchars((string)($OptionWarning[$k]['Text'] ?? $k), ENT_QUOTES, 'UTF-8');
        $html .= ' <img src="' . $IconLoc . $pic . '" title="' . $text . '" alt="" style="vertical-align:middle;margin-left:2px;" height="' . $h . '">';
    }
    return $html;
}

function rkrk_normalize_catalog_display_text($text){
    global $searchSDZ, $replaceSDZ;
    $s = trim((string)$text);
    if ($s === '') {
        return '';
    }
    if (isset($searchSDZ, $replaceSDZ) && is_array($searchSDZ) && is_array($replaceSDZ)) {
        $s = str_replace($searchSDZ, $replaceSDZ, $s);
    }
    return $s;
}

/**
 * Bauer-/Grundstückszusatz fürs Flugpanel. Nur wenn im Katalogeintrag <b>Bauer</b> nicht leer ist
 * (reine Grundstücksdaten ohne Bauern‑Text werden nicht angezeigt).
 *
 * @return array{show:bool, summary:string, body_html:string}
 */
function rkrk_field_meta_bauer_details_payload($bauerRaw, $jaegerRaw, $grundstueckId, $parkplatz, $zentrum){
    $bauer = rkrk_normalize_catalog_display_text($bauerRaw);
    $jaeger = rkrk_normalize_catalog_display_text($jaegerRaw);
    $bauerCheck = mb_strtolower(trim((string)$bauer), 'UTF-8');
    $jaegerCheck = mb_strtolower(trim((string)$jaeger), 'UTF-8');
    if (
        ($bauerCheck === '' || in_array($bauerCheck, array('-', '—', 'n/a', 'na', 'none', 'kein', 'keine', 'unbekannt'), true))
        && ($jaegerCheck === '' || in_array($jaegerCheck, array('-', '—', 'n/a', 'na', 'none', 'kein', 'keine', 'unbekannt'), true))
    ) {
        return array('show' => false, 'summary' => '', 'body_html' => '');
    }

    $grundId = trim((string)$grundstueckId);
    $park = trim((string)$parkplatz);
    $zent = trim((string)$zentrum);

    $firstLine = '';
    foreach (array($jaeger, $bauer) as $summaryCandidate) {
        if ($summaryCandidate === '') continue;
        foreach (preg_split('/\r\n|\r|\n/', $summaryCandidate) as $ln) {
            $t = trim((string)$ln);
            if ($t !== '') {
                $firstLine = $t;
                break 2;
            }
        }
    }
    if ($firstLine === '') {
        $firstLine = ($jaeger !== '') ? $jaeger : $bauer;
    }

    $summ = $firstLine;
    if (function_exists('mb_strlen')) {
        if (mb_strlen($summ, 'UTF-8') > 100) {
            $summ = mb_substr($summ, 0, 97, 'UTF-8') . '…';
        }
    } elseif (strlen($summ) > 100) {
        $summ = substr($summ, 0, 97) . '…';
    }
    $summary = 'Felddaten: ' . $summ;

    $chunks = array();
    if ($jaeger !== '') {
        $chunks[] = '<div><strong>Jäger:</strong> ' . nl2br(htmlspecialchars($jaeger, ENT_QUOTES, 'UTF-8')) . '</div>';
    }
    if ($bauer !== '') {
        $chunks[] = '<div><strong>Bauer:</strong> ' . nl2br(htmlspecialchars($bauer, ENT_QUOTES, 'UTF-8')) . '</div>';
    }
    foreach (array(
        'Grundstück-ID' => $grundId,
        'Parkplatz' => $park,
        'Zentrum (Koordinaten)' => $zent,
    ) as $lbl => $val) {
        $val = trim((string)$val);
        if ($val === '') {
            continue;
        }
        $chunks[] = '<div><strong>' . htmlspecialchars((string)$lbl, ENT_QUOTES, 'UTF-8') . ':</strong> '
            . nl2br(htmlspecialchars($val, ENT_QUOTES, 'UTF-8')) . '</div>';
    }

    return array(
        'show' => true,
        'summary' => $summary,
        'body_html' => implode('', $chunks)
    );
}

/**
 * URL für img/href: Pfadsegmente mit Umlauten percent-encodieren (Browser/Webserver).
 */
function rkrk_checklist_encode_media_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    $fragment = '';
    $hashPos = strpos($url, '#');
    if ($hashPos !== false) {
        $fragment = substr($url, $hashPos);
        $url = substr($url, 0, $hashPos);
    }
    $query = '';
    $qPos = strpos($url, '?');
    if ($qPos !== false) {
        $query = substr($url, $qPos);
        $url = substr($url, 0, $qPos);
    }
    $encodeSegments = static function ($path) {
        if ($path === '' || $path === '/') {
            return $path;
        }
        $leading = (strpos($path, '/') === 0);
        $trailing = (substr($path, -1) === '/');
        $segments = explode('/', trim($path, '/'));
        $segments = array_map(static function ($seg) {
            if ($seg === '') {
                return $seg;
            }
            $decoded = rawurldecode($seg);
            return rawurlencode($decoded);
        }, $segments);
        $out = ($leading ? '/' : '') . implode('/', $segments);
        return $trailing ? $out . '/' : $out;
    };
    if (preg_match('#^(https?)://#i', $url, $m)) {
        $scheme = strtolower($m[1]);
        $rest = substr($url, strlen($m[0]));
        $slashPos = strpos($rest, '/');
        if ($slashPos === false) {
            return $scheme . '://' . $rest . $query . $fragment;
        }
        $authority = substr($rest, 0, $slashPos);
        $path = substr($rest, $slashPos);
        return $scheme . '://' . $authority . $encodeSegments($path) . $query . $fragment;
    }
    return $encodeSegments($url) . $query . $fragment;
}

/**
 * Flugplan-Bild-URL wie in rkrk_edit_felddaten / rkrk_felderliste:
 * CSV aus XML, GetFlugplanbild(StdFlughoehe), RegionSettings-Ordner, dann Web-URL.
 */
function rkrk_checklist_resolve_flugplan_url($flugplanBildRaw, $jg, $stdFlughoehe) {
    $flugplanBildRaw = trim((string)$flugplanBildRaw);
    if ($flugplanBildRaw === '') {
        return '';
    }

    global $RKRKExternalLoactionMissionFiles, $RKRKLocalLoactionMissionFiles, $RegionSettings;

    if (!function_exists('GetFlugplanbild')) {
        $felddaten = __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'rkrk_felddaten.php';
        if (is_file($felddaten)) {
            include_once($felddaten);
        }
    }

    $files = array_values(array_filter(array_map('trim', explode(',', $flugplanBildRaw)), static function ($f) {
        return $f !== '';
    }));
    if (!$files) {
        return '';
    }

    $stdFlughoehe = trim((string)$stdFlughoehe);
    $selected = $files[0];
    if (function_exists('GetFlugplanbild')) {
        $selected = GetFlugplanbild($files, $stdFlughoehe);
    } elseif ($stdFlughoehe !== '') {
        foreach ($files as $bild) {
            if (strpos($bild, '_' . $stdFlughoehe . '_') !== false) {
                $selected = $bild;
                break;
            }
        }
    }

    $selected = str_replace('\\', '/', trim((string)$selected));
    if ($selected === '') {
        return '';
    }

    $relativePath = $selected;
    if (strpos($selected, '/') === false) {
        $jgKey = trim((string)$jg);
        if ($jgKey === '') {
            $fidFromPath = rkrk_checklist_extract_feld_id($selected);
            if ($fidFromPath !== '' && strpos($fidFromPath, '-') !== false) {
                $jgKey = explode('-', $fidFromPath, 2)[0];
            }
        }
        $regionDir = '';
        if ($jgKey !== '' && isset($RegionSettings) && is_array($RegionSettings)) {
            if (isset($RegionSettings[$jgKey]['Dir'])) {
                $regionDir = trim((string)$RegionSettings[$jgKey]['Dir']);
            }
        }
        if ($regionDir !== '') {
            $relativePath = rtrim($regionDir, '/') . '/' . ltrim($selected, '/');
        }
    }

    $extBase = trim((string)($RKRKExternalLoactionMissionFiles ?? ''));
    if ($extBase !== '') {
        return rkrk_checklist_encode_media_url(
            rtrim(str_replace('\\', '/', $extBase), '/') . '/' . ltrim($relativePath, '/')
        );
    }

    $locBase = trim((string)($RKRKLocalLoactionMissionFiles ?? ''));
    if ($locBase !== '') {
        return rkrk_checklist_encode_media_url(
            rtrim(str_replace('\\', '/', $locBase), '/') . '/' . ltrim($relativePath, '/')
        );
    }

    return rkrk_checklist_encode_media_url($relativePath);
}

function rkrk_get_field_meta_map(){
    static $cachedMap = null;
    static $mapLoaded = false;
    if ($mapLoaded) {
        return $cachedMap;
    }
    $mapLoaded = true;

    $out = array();
    if (function_exists('SetMissionLocation')) {
        SetMissionLocation();
    }

    global $xmlMissionLocation, $RKRKFelderfileXML, $RKRKLocalLoactionMissionFiles, $RKRKExternalLoactionMissionFiles, $xmlFileDirectory, $IconLoc;
    $xmlFile = trim((string)($RKRKFelderfileXML ?? 'RKRKFelder.xml'));
    $candidates = array();
    $base = trim((string)($xmlMissionLocation ?? ''));
    if ($base !== '') $candidates[] = rtrim($base, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
    $missionBase = trim((string)($RKRKLocalLoactionMissionFiles ?? ''));
    $xmlDir = trim((string)($xmlFileDirectory ?? 'xmlfiles/'));
    if ($missionBase !== '') {
        $candidates[] = rtrim($missionBase, "/\\") . DIRECTORY_SEPARATOR . trim($xmlDir, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
    }

    $xmlPath = '';
    foreach ($candidates as $cand) {
        if ($cand !== '' && file_exists($cand)) {
            $xmlPath = $cand;
            break;
        }
    }
    if ($xmlPath === '') {
        $cachedMap = $out;
        return $cachedMap;
    }

    $xml = null;
    if (function_exists('rkrk_load_xml_file_safe')) {
        $xml = @rkrk_load_xml_file_safe($xmlPath);
    }
    if (!$xml) {
        $raw = @file_get_contents($xmlPath);
        if ($raw !== false) $xml = @simplexml_load_string($raw);
    }
    if (!$xml || !isset($xml->Feld)) {
        $cachedMap = $out;
        return $cachedMap;
    }

    foreach ($xml->Feld as $feld) {
        $fid = trim((string)($feld->FeldID ?? ''));
        if ($fid === '') continue;
        $name = trim((string)($feld->Feldname ?? ''));
        $warnung = trim((string)($feld->Warnung ?? ''));
        $flugplanBild = trim((string)($feld->FlugplanBild ?? ''));
        $flugplanImg = rkrk_checklist_resolve_flugplan_url(
            $flugplanBild,
            trim((string)($feld->JG ?? '')),
            trim((string)($feld->StdFlughohe ?? ''))
        );
        $startRaw = trim((string)($feld->Grundstueckdaten->Startplatz ?? ''));
        $startLat = "";
        $startLon = "";
        if ($startRaw !== "") {
            $parts = array_map('trim', explode(',', $startRaw));
            if (count($parts) >= 2) {
                // Gleiches Mapping wie in rkrk_fluege.php:
                // erster Wert -> Lat, zweiter Wert -> Lon; Google bekommt Lon,Lat.
                $startLat = (string)$parts[0];
                $startLon = (string)$parts[1];
            }
        }
        $google = "";
        if ($startLon !== "" && $startLat !== "") {
            $google = "https://maps.google.ch/?q=" . trim($startLon) . " ," . trim($startLat) . "&t=k";
        }
        $startIcon = trim((string)($IconLoc ?? ""));
        if ($startIcon !== "") {
            $startIcon = rtrim(str_replace("\\", "/", $startIcon), "/") . "/startpnt.png";
        }
        $gs = isset($feld->Grundstueckdaten) ? $feld->Grundstueckdaten : null;
        $parkplatz = trim((string)($gs ? $gs->Parkplatz : ''));
        $zentrum = trim((string)($gs ? $gs->Zentrum : ''));
        $grundstueckId = trim((string)($feld->GrundstueckID ?? ''));
        $bauerPayload = rkrk_field_meta_bauer_details_payload(
            (string)($feld->Bauer ?? ''),
            (string)($feld->Jaeger ?? ''),
            $grundstueckId,
            $parkplatz,
            $zentrum
        );
        $out[$fid] = array(
            "name" => $name,
            "flugplan" => $flugplanImg,
            "startpoint" => ($startLat !== "" && $startLon !== "") ? ($startLat . ", " . $startLon) : "",
            "startpoint_google" => $google,
            "startpoint_icon" => $startIcon,
            "warnung" => $warnung,
            "ngt_icons_html" => rkrk_render_flugzone_title_icons_html($warnung, 18),
            "bauer_details_show" => $bauerPayload['show'],
            "bauer_details_summary" => $bauerPayload['summary'],
            "bauer_details_body_html" => $bauerPayload['body_html']
        );
    }
    $cachedMap = $out;
    return $cachedMap;
}

function rkrk_get_field_name_map(){
    $meta = rkrk_get_field_meta_map();
    $out = array();
    foreach ($meta as $fid => $row) {
        if (!is_array($row)) continue;
        $out[$fid] = trim((string)($row["name"] ?? ""));
    }
    return $out;
}

/* ---------------- SAFE OUTPUT ---------------- */
function safe($v){
    if (is_array($v)) return htmlspecialchars(implode("; ", $v));
    if (!is_string($v)) return "";
    return htmlspecialchars($v);
}

function safeListValue($v){
    if (is_array($v)) $v = implode("; ", $v);
    if (!is_string($v)) return "";
    // Show stored entities like "&amp;" as "&" in list output.
    $decoded = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, "UTF-8");
    return safe($decoded);
}

function redirectTo($url){
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    if (!headers_sent()) {
        header("Location:$url");
        exit;
    }

    $urlEsc = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
    $urlJs = json_encode($url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<script>window.location.href=$urlJs;</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=$urlEsc'></noscript>";
    exit;
}

// rkrk_checklist_json_response → inline above

/* ---------------- LOAD TEMPLATE JSON ---------------- */
function loadTemplate($file){
    global $TEMPLATE_FOLDER;
    $path = "$TEMPLATE_FOLDER/$file";
    if (!file_exists($path)) return null;

    $tpl = json_decode(file_get_contents($path), true);

    if (!$tpl || !isset($tpl["sections"])) {
        return ["title" => $file, "sections" => []];
    }

    $changed = false;

    // Ungültige Feldnamen aus alten Versionen
    $invalidLabels = ["index","id","parent","trigger","undefined","0",""];

    foreach ($tpl["sections"] as $secName => $fields) {

        if (!is_array($fields)) {
            $tpl["sections"][$secName] = [];
            $changed = true;
            continue;
        }

        foreach ($fields as $label => $meta) {

            // ❌ Ungültige Labels entfernen
            if (in_array(trim($label), $invalidLabels, true)) {
                unset($tpl["sections"][$secName][$label]);
                $changed = true;
                continue;
            }

            // Meta muss Array oder Typstring sein
            if (!is_array($meta)) {
                if (is_string($meta) && $meta !== "") {
                    $tpl["sections"][$secName][$label] = [
                        "type"     => $meta,
                        "inname"   => "0",
                        "inresult" => "0"
                    ];
                } else {
                    unset($tpl["sections"][$secName][$label]);
                }
                $changed = true;
                continue;
            }

            // Typ reparieren
            if (!isset($meta["type"]) || trim($meta["type"]) === "") {
                $tpl["sections"][$secName][$label]["type"] = "text";
                $changed = true;
            }

            // Flags reparieren
            if (!isset($meta["inname"])) {
                $tpl["sections"][$secName][$label]["inname"] = "0";
                $changed = true;
            }
            if (!isset($meta["inresult"])) {
                $tpl["sections"][$secName][$label]["inresult"] = "0";
                $changed = true;
            }

            // Placeholder/Info nur Strings
            foreach (["placeholder", "info"] as $opt) {
                if (isset($meta[$opt]) && !is_string($meta[$opt])) {
                    unset($tpl["sections"][$secName][$opt]);
                    $changed = true;
                }
            }
        }
    }

    if ($changed) {
        file_put_contents($path, json_encode($tpl, JSON_PRETTY_PRINT));
    }

    return $tpl;
}

/* ---------------- LOAD CHECKLIST JSON ---------------- */
function loadChecklist($id){
    global $JSON_FOLDER;
    $path = "$JSON_FOLDER/$id.json";
    if (!file_exists($path)) return null;
    return json_decode(file_get_contents($path), true);
}

/* ---------------- AUTO DEPENDENT FIELD PARSER ---------------- */
function parseDependentField($label){
    $label = trim((string)$label);
    if ($label === '') return null;

    // Flexible separators:
    // - "=" with optional spaces (e.g. "Parent=ja", "Parent = ja")
    // - "–" with optional spaces
    // - "-" only with spaces around to avoid breaking names like "Kompass-Kalibrierung"
    $patterns = [
        '/^(.+?)\s*=\s*(.+)$/u',
        '/^(.+?)\s*–\s*(.+)$/u',
        '/^(.+?)\s+-\s+(.+)$/u',
    ];

    foreach ($patterns as $rx) {
        if (preg_match($rx, $label, $m)) {
            return [
                "parent"  => trim($m[1]),
                "trigger" => trim($m[2])
            ];
        }
    }

    return null;
}

/* ---------------- HEADER ---------------- */
function pageHeaderLocal($title){
    global $IconLoc, $mode;

    // Fallback falls $mode nicht gesetzt ist
    if (!isset($mode)) {
        $mode = $_GET['mode'] ?? 'list';
    }

    echo "
    <link rel='stylesheet' href='template/flex.css'>
	<div class='rkrk-wrap' data-mode='".safe($mode)."'>
      <div class='cl-header'>
        <h1>".safe($title)."</h1>
		<!--
        <img src='" . $IconLoc . "/logo.jpg' style='height:80px; position:absolute; top:20px; right:20px;'><br><br>
		-->
      </div>
    ";
}

/* ---------------- FOOTER + JS ENGINE ---------------- */
function pageFooterLocal(){
    echo "
<script>
// ---------------- DEPENDENT FIELD ENGINE (robust, color-safe) ----------------

// Farbcode am Ende entfernen: \"OK (g)\" -> \"OK\"
function stripColorCode(v){
    if (typeof v !== 'string') return v;
    return v.replace(/\\s*\\([a-z]\\)\\s*$/i, '').trim();
}

document.querySelectorAll('[data-dependent]').forEach(dep => {

    const parentKey = dep.dataset.parentKey;
    const trigger   = dep.dataset.trigger;

    const inputs = document.querySelectorAll('[data-field-key=\"' + parentKey + '\"]');

    function update() {
        let show = false;

        inputs.forEach(i => {

            // RADIO
            if (
                i.type === 'radio' &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // SINGLE CHECKBOX
            if (
                i.type === 'checkbox' &&
                !i.name.endsWith('[]') &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // MULTI CHECKBOX (array)
            if (
                i.type === 'checkbox' &&
                i.name.endsWith('[]') &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // TEXT / NUMBER / DATE / DATETIME
            if (
                (i.type === 'text' ||
                 i.type === 'number' ||
                 i.type === 'date' ||
                 i.type === 'datetime-local') &&
                stripColorCode(i.value.trim()) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // SELECT
            if (
                i.tagName === 'SELECT' &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }
        });

        dep.classList.toggle('visible', show);
    }

    inputs.forEach(i => i.addEventListener('change', update));
    update();
});

// ---------------- COLLAPSIBLE SECTIONS ----------------
document.querySelectorAll('.section-title-box').forEach(title=>{
    const fields = title.nextElementSibling;
    if (!fields) return;

    const id = title.dataset.sectionId;
    const key = 'cl_section_' + id;

    if(localStorage.getItem(key) === '1'){
        fields.classList.add('collapsed');
        title.classList.add('open');
    }

    title.addEventListener('click', ()=>{
        const collapsed = fields.classList.toggle('collapsed');
        title.classList.toggle('open', collapsed);
        localStorage.setItem(key, collapsed ? '1' : '0');
    });
});

// ---------------- INFO POPUP (MODAL WINDOW) ----------------
document.querySelectorAll('.info-icon').forEach(icon => {
    icon.addEventListener('click', () => {
        const text = icon.dataset.info;

        const overlay = document.createElement('div');
        overlay.className = 'info-overlay';

        const popup = document.createElement('div');
        popup.className = 'info-popup';
        popup.innerHTML =
            \"<div class='info-popup-close'>✖</div>\" +
            \"<div style='padding-top:5px;'>\" +
            text.replace(/\\\\n/g, \"<br>\") +
            \"</div>\";

        document.body.appendChild(overlay);
        document.body.appendChild(popup);

        popup.querySelector('.info-popup-close').addEventListener('click', () => {
            popup.remove();
            overlay.remove();
        });

        overlay.addEventListener('click', () => {
            popup.remove();
            overlay.remove();
        });
    });
});

// ---------------- N/R CHIP TOGGLING ----------------
document.querySelectorAll('.nr-chip input').forEach(chk => {
    chk.addEventListener('change', () => {
        const chip = chk.closest('.nr-chip');
        if (chk.checked) {
            chip.classList.add('active');
        } else {
            chip.classList.remove('active');
        }
    });
});

</script>
</div>";
}

/* -----------------------------------------------------------
   TEMPLATE MANAGER
----------------------------------------------------------- */
function showTemplateManager(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
		
    pageHeaderLocal("Checklisten‑Templates");

	if ($isGuest){
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='?mode=list' class='btn'>⬅ Zurück</a>
              </div>";
        pageFooterLocal();
        return;
    }

    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
    }
	
    echo "<div class='rkrk-card'>
            <h3>Neues Template erstellen</h3>
            <form method='post' action='?mode=createtemplate'>
                <input type='text' name='title' placeholder='Template‑Titel' class='text-input'>
                <button class='btn btn-primary'>Template anlegen</button>
            </form>
          </div>";

    echo "<div class='rkrk-card'>
            <h3>Bestehende Templates</h3>
            <table class='list-table'>
                <tr>
                    <th>Titel</th>
                    <th>Datei</th>
                    <th>Aktion</th>
                </tr>";

    foreach (glob("$TEMPLATE_FOLDER/*.json") as $file){
        $base  = basename($file);
        $benc  = rawurlencode($base);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;
        $jsonDl = htmlspecialchars($base, ENT_QUOTES, "UTF-8");

        echo "<tr>
                <td>".safe($title)."</td>
                <td>".safe($base)."</td>
                <td>
                    <span class='rk-tpl-actions' style='display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center;'>
                    <a class='chk-act' href='?mode=edittemplate&file=$benc' title='Template im Editor bearbeiten' aria-label='Bearbeiten'>" . rkrk_checklist_toolbar_svg_edit() . "</a>
                    <a class='chk-act' href='?mode=templateprint&file=$benc' target='_blank' rel='noopener' title='Druckansicht: Struktur lesen oder ausdrucken' aria-label='Druckansicht'>" . rkrk_checklist_toolbar_svg_print() . "</a>
                    <a class='chk-act' href='?mode=templateexport&file=$benc' download='$jsonDl' title='Template als JSON herunterladen (Backup / Bearbeiten extern)' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "</a>
                    <a class='chk-act' href='?mode=copytemplate&file=$benc' onclick='return confirm(\"Template wirklich kopieren?\");' title='Template duplizieren' aria-label='Kopieren'>" . rkrk_checklist_toolbar_svg_copy() . "</a>
                    <a class='chk-act' href='?mode=form&file=$benc&blank=1' title='Neue Checkliste mit diesem Template' aria-label='Neue Checkliste'>" . rkrk_checklist_toolbar_svg_file_plus() . "</a>
                    <a class='chk-act' href='?mode=deletetemplate&file=$benc' onclick='return confirm(\"Template wirklich löschen?\");' title='Template-Datei endgültig löschen' aria-label='Löschen' style='color:#8b2e2e;border-color:#e8c8c8;'>" . rkrk_checklist_toolbar_svg_trash() . "</a>
                    </span>
                </td>
              </tr>";
    }

    echo "</table></div>";

    echo "<div style='text-align:center;margin-top:20px;'>
            <a href='?mode=list' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE ANLEGEN
----------------------------------------------------------- */
function createTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

     //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates erstellen.</div>";
        return;
    }

    $title = trim($_POST['title'] ?? "");
    if ($title === ""){
        redirectTo("?mode=templates");
    }

    $file = slugifyTitle($title) . ".json";
    $path = "$TEMPLATE_FOLDER/$file";

    if (!file_exists($path)){
        $tpl = [
            "title" => $title,
            "sections" => [
                "Allgemein" => [
                    "Pilot" => "text"
                ]
            ]
        ];
        file_put_contents($path, json_encode($tpl, JSON_PRETTY_PRINT));
    }

    redirectTo("?mode=edittemplate&file=$file");
}
/* -----------------------------------------------------------
   TEMPLATE LÖSCHEN
----------------------------------------------------------- */
function deleteTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates löschen.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $path = "$TEMPLATE_FOLDER/$file";

    if (file_exists($path)) unlink($path);

    redirectTo("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE KOPIEREN
----------------------------------------------------------- */
function copyTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates kopieren.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $src  = "$TEMPLATE_FOLDER/$file";

    if (!file_exists($src)) {
        redirectTo("?mode=templates");
    }

    $tpl = loadTemplate($file);
    if (!$tpl) {
        redirectTo("?mode=templates");
    }

    $title = $tpl["title"] ?? "template";
    $baseName = slugifyTitle($title);
    $baseName = trim($baseName, "_");

    if ($baseName === "") {
        $baseName = "template";
    }

    $newFile = $baseName . ".json";
    $dst = "$TEMPLATE_FOLDER/$newFile";

    $i = 1;
    while (file_exists($dst)) {
        $newFile = $baseName . "_copy_" . $i . ".json";
        $dst = "$TEMPLATE_FOLDER/$newFile";
        $i++;
    }

    file_put_contents($dst, json_encode($tpl, JSON_PRETTY_PRINT));

    redirectTo("?mode=edittemplate&file=$newFile");
}
/* -----------------------------------------------------------
   TEMPLATE: JSON Export/Import, Druck — rkrk_template_csv_inc.php
----------------------------------------------------------- */
function handleTemplateJsonExport()
{
    global $TEMPLATE_FOLDER, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        redirectTo("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_export_json_download($TEMPLATE_FOLDER, $f);
    redirectTo("?mode=templates");
}

function handleTemplateJsonImport()
{
    global $TEMPLATE_FOLDER, $InputUserInfo;
    $isGuest = false;
    if ($isGuest || (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST")) {
        redirectTo("?mode=templates");
    }
    $f = basename((string)($_POST["file"] ?? ""));
    $code = rkrk_template_import_json_upload($TEMPLATE_FOLDER, $f, $_FILES["jsonfile"] ?? null);
    $fe = rawurlencode($f);
    if ($code === "ok") {
        redirectTo("?mode=edittemplate&file=" . $fe . "&imp=ok");
    }
    if ($code === "invalid") {
        redirectTo("?mode=edittemplate&file=" . $fe . "&imp=invalid");
    }
    redirectTo("?mode=edittemplate&file=" . $fe . "&imp=err");
}

function showTemplatePrintPreview()
{
    global $TEMPLATE_FOLDER, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        redirectTo("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_print_html($TEMPLATE_FOLDER, $f);
    redirectTo("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE EDITOR
----------------------------------------------------------- */
function editTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $tpl  = loadTemplate($file);

    if (!$tpl){
        pageHeaderLocal("Template nicht gefunden");
        echo "<div class='rkrk-card'>Template existiert nicht.</div>";
        pageFooterLocal();
        return;
    }

    $title    = $tpl["title"];
    $sections = $tpl["sections"];

    pageHeaderLocal("Template bearbeiten");

    if (isset($_GET["imp"])) {
        $im = (string)$_GET["imp"];
        if ($im === "ok") {
            echo "<div class='rkrk-card' style='border-color:#86c586;background:#f0faf0;'>JSON-Import: Template-Datei wurde ersetzt.</div>";
        } elseif ($im === "err") {
            echo "<div class='rkrk-card danger'>JSON-Import fehlgeschlagen (Datei fehlt oder Upload-Fehler).</div>";
        } elseif ($im === "invalid") {
            echo "<div class='rkrk-card danger'>JSON-Import: Ungueltige Datei (erwartet wird gueltiges JSON mit einem <code>sections</code>-Objekt).</div>";
        }
    }

    $jsonDlName = htmlspecialchars($file, ENT_QUOTES, "UTF-8");
    $fenc = rawurlencode($file);
    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
    }
    echo "<div class='chk-toolbar no-print' role='region' aria-label='Template JSON und Druck' style='margin-bottom:14px;'>";
    echo "<span class='chk-toolbar-hint' style='flex:0 0 auto;font-weight:600;color:#334155;min-width:unset;'><strong>Backup / Austausch</strong></span>";
    echo "<a class='chk-tbtn' href='?mode=templateprint&file=$fenc' target='_blank' rel='noopener' title='Druckansicht: Struktur lesen oder ausdrucken' aria-label='Druckansicht'>" . rkrk_checklist_toolbar_svg_print() . "<span>Druckansicht</span></a>";
    echo "<a class='chk-tbtn' href='?mode=templateexport&file=$fenc' download='$jsonDlName' title='Template als JSON herunterladen' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "<span>JSON exportieren</span></a>";
    echo "<form class='chk-import' method='post' action='?mode=templateimport' enctype='multipart/form-data' title='JSON importieren'>";
    echo "<input type='hidden' name='file' value='" . safe($file) . "'>";
    echo "<label style='margin:0;font-size:13px;color:#5f6b66;' title='Vorlage: zuerst exportieren, bearbeiten, dann hier wieder einlesen'>Import JSON:</label>";
    echo "<input type='file' name='jsonfile' accept='.json,application/json' required title='UTF-8 JSON mit sections'>";
    echo "<button class='chk-tbtn' type='submit' title='Ersetzt die gesamte Template-Datei auf dem Server' aria-label='JSON importieren' onclick='return confirm(\"Import ersetzt diese Template-Datei vollstaendig durch die hochgeladene JSON-Datei. Fortfahren?\");'>" . rkrk_checklist_toolbar_svg_import() . "<span>Importieren</span></button>";
    echo "</form>";
    echo "<span class='chk-toolbar-hint'>Struktur wie beim Export; Feld <code>sections</code> ist Pflicht.</span>";
    echo "</div>";

    echo "<div class='rkrk-card'>
        <form method='post' action='?mode=savetemplate'>
		<input type='hidden' id='weather_json' name='weather_json'>
        <input type='hidden' name='file' value='".safe($file)."'>";
?>
<script>
async function loadWeatherForChecklist() {

    // 1) Standort aus der Checkliste holen (falls vorhanden)
    let lat = null;
    let lon = null;

    const latField = document.querySelector('[data-field-key="lat"]');
    const lonField = document.querySelector('[data-field-key="lon"]');

    if (latField && lonField) {
        lat = parseFloat(latField.value);
        lon = parseFloat(lonField.value);
    }

    // Falls keine Koordinaten → Abbrechen
    if (!lat || !lon) return;

    // 2) Open-Meteo API abrufen
    const url =
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}` +
      `&timezone=auto&current=temperature_2m,relative_humidity_2m,dew_point_2m,weather_code,wind_speed_80m,wind_direction_10m,wind_gusts_10m,visibility,precipitation_probability,cloud_cover`;

    try {
        const res = await fetch(url);
        const weather = await res.json();

        // 3) Snapshot bauen (wie in deiner Wetterbox)
        const cur = weather.current || {};

        const snapshot = {
            time: cur.time,
            weather_code: cur.weather_code,
            temp_c: cur.temperature_2m,
            humidity_pct: cur.relative_humidity_2m,
            dewpoint_c: cur.dew_point_2m,
            precip_prob_pct: cur.precipitation_probability,
            visibility_m: cur.visibility,
            cloud_cover_pct: cur.cloud_cover,
            wind_80m_kmh: cur.wind_speed_80m,
            wind_dir_deg: cur.wind_direction_10m,
            wind_gusts_kmh: cur.wind_gusts_10m
        };

        // 4) Speichern im Hidden Field
        document.getElementById("weather_json").value = JSON.stringify({
            snapshot: snapshot
        });

    } catch (e) {
        console.warn("Wetter konnte nicht geladen werden:", e);
    }
}

// Beim Laden der Checkliste automatisch Wetter laden
document.addEventListener("DOMContentLoaded", loadWeatherForChecklist);
</script>
<?php
    echo "<div class='field'>
            <label class='field-label'>Template‑Titel</label>
            <input type='text' name='title' value='".safe($title)."'>
          </div>";

    echo "<h3>Sektionen & Felder</h3>";

    echo "<table class='template-table'>
            <tr>
                <th>Sektion</th>
                <th>Label</th>
                <th>Typ</th>
                <th style='width:450px'>Optionen</th>
                <th>Placeholder</th>
                <th style='width:450px'>Info</th>
                <th>N/R</th>
                <th>Aktion</th>
            </tr>";

    $flat = [];
    foreach ($sections as $secName => $fields){
        foreach ($fields as $label => $type){
            $placeholder = "";
            $info = "";
            $inname = "";
            $inresult = "";
            $typeVal = $type;

            if (is_array($type)){
                $placeholder = $type["placeholder"] ?? "";
                $info = $type["info"] ?? "";
                $inname = $type["inname"] ?? "0";
                $inresult = $type["inresult"] ?? "0";
                $typeVal = $type["type"] ?? $typeVal;
            }

            $flat[] = [
                "sec"         => $secName,
                "label"       => $label,
                "type"        => $typeVal,
                "placeholder" => $placeholder,
                "info"        => $info,
                "inname"      => $inname,
                "inresult"    => $inresult
            ];
        }
    }

    $prevSec = null;

    foreach ($flat as $i => $row){

        $isFirstRowOfSection = ($row["sec"] !== $prevSec);
        $prevSec = $row["sec"];

        $typeStored = $row["type"];
        $typeBase   = $typeStored;
        $optVal     = "";

        if (strpos($typeStored, "radio:") === 0){
            $typeBase = "radio";
            $optVal = substr($typeStored, 6);
        }
        elseif (strpos($typeStored, "multi:") === 0){
            $typeBase = "multi";
            $optVal = substr($typeStored, 6);
        }

        echo "<tr>";

        echo "<td style='white-space:nowrap'>";
        if ($isFirstRowOfSection){
            echo "<span style='display:inline-block; margin-right:6px'>";
            echo "<button type='submit' name='move_sec_up' value='".safe($row["sec"])."'>⇑</button>";
            echo "<button type='submit' name='move_sec_down' value='".safe($row["sec"])."'>⇓</button>";
            echo "</span>";
        }
        echo "<input type='text' name='sec[]' value='".safe($row["sec"])."' style='width:160px; text-align:right; float:right' placeholder='Sektion'>";
        echo "</td>";

        echo "<td>
                <input type='text' name='label[]' value='".safe($row["label"])."' placeholder='Label'>
              </td>";

        echo "<td><select name='type[]' style='width:100%'>";
        
		
		$types = [ 
		  "text"=>"Text",
		  "textarea"=>"Textarea",
		  "number"=>"Number",
		  "date"=>"Date",
		  "datetime"=>"Datetime",
		  "flugzeit"=>"Flugzeit",
		  "flugort"=>"Flugort",
		  "checkbox"=>"Checkbox",
		  "radio"=>"Radio",
		  "multi"=>"Multi"
		];

        foreach($types as $k=>$v){
            $sel = ($typeBase === $k) ? "selected" : "";
            echo "<option value='".safe($k)."' $sel>".safe($v)."</option>";
        }
        echo "</select></td>";

        echo "<td><textarea name='options[]' rows='2' placeholder='a|b|c'>".safe($optVal)."</textarea></td>";

        echo "<td><input type='text' name='placeholder[]' value='".safe($row["placeholder"])."' placeholder='Platzhalter'></td>";

        echo "<td><textarea name='info[]' rows='2' placeholder='Infomationen (mehrzeilig)'>".safe($row["info"])."</textarea></td>";

        $chkName   = ($row["inname"] === "1") ? "checked" : "";
        $chkResult = ($row["inresult"] === "1") ? "checked" : "";

        echo "<td style='text-align:center'>
                <div class='nr-toggle'>
                    <label class='nr-chip ".($chkName ? "active" : "")."'>
                        N
                        <input type='checkbox' name='inname[$i]' value='1' ".($chkName ? "checked" : "").">
                    </label>

                    <label class='nr-chip ".($chkResult ? "active" : "")."'>
                        R
                        <input type='checkbox' name='inresult[$i]' value='1' ".($chkResult ? "checked" : "").">
                    </label>
                </div>
              </td>";
?>		
		<script>
		document.addEventListener("DOMContentLoaded", () => {
			if (window.weatherData) {
				document.getElementById("weather_json").value = JSON.stringify(window.weatherData);
			}
		});
		</script>
<?php
        echo "<td>
                <button type='submit' name='move_up' value='".$i."'>▲</button>
                <button type='submit' name='move_down' value='".$i."'>▼</button>
                <button type='submit' name='delete_row' value='".$i."'>🗑</button>
              </td>";

        echo "</tr>";
    }

    echo "</table>";

    echo "<h3>Neues Feld hinzufügen</h3>

    <table class='template-table'>
        <tr>
            <th>Sektion</th>
            <th>Label</th>
            <th>Typ</th>
            <th style='width:450px'>Optionen</th>
            <th>Placeholder</th>
            <th style='width:450px'>Info</th>
            <th>N/R</th>
        </tr>

        <tr>
            <td><input type='text' name='new_sec' placeholder='Sektion'></td>
            <td><input type='text' name='new_label' placeholder='Label'></td>
            <td>
                <select name='new_type' class='text-input'>
                    <option value='text'>Text</option>
                    <option value='textarea'>Textarea</option>
                    <option value='number'>Number</option>
                    <option value='date'>Date</option>
                    <option value='datetime'>Datetime</option>
                    <option value='flugzeit'>Flugzeit</option>
                    <option value='flugort'>Flugort</option>
                    <option value='checkbox'>Checkbox</option>
                    <option value='radio'>Radio</option>
                    <option value='multi'>Multi</option>
                </select>
            </td>
            <td><input type='text' name='new_options' placeholder='a|b|c'></td>
            <td><input type='text' name='new_placeholder' placeholder='Platzhalter'></td>
            <td><textarea name='new_info' rows='2' placeholder='Info (mehrzeilig)'></textarea></td>

            <td style='text-align:center'>
                <div class='nr-toggle'>
                    <input type='hidden' name='new_inname' value='0'>
                    <label class='nr-chip'>
                        N
                        <input type='checkbox' name='new_inname' value='1'>
                    </label>

                    <input type='hidden' name='new_inresult' value='0'>
                    <label class='nr-chip'>
                        R
                        <input type='checkbox' name='new_inresult' value='1'>
                    </label>
                </div>
            </td>
        </tr>
    </table>

    <button class='btn btn-primary'>Template speichern</button>
    </form>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const weatherBox = document.getElementById('weatherBox');
		const hidden = document.getElementById('weather_json');

		if (!weatherBox || !hidden) return;

		const syncWeather = () => {
			const dataEl = weatherBox.querySelector('#weather-data');
			if (dataEl && dataEl.dataset.weatherJson) {
				hidden.value = dataEl.dataset.weatherJson;
			}
		};

		syncWeather();

		new MutationObserver(syncWeather).observe(weatherBox, {
			childList: true,
			subtree: true
		});
	});
</script>
    </div>";

    echo "<div style='text-align:center;margin-top:20px;'>
            <a href='?mode=templates' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE SPEICHERN
----------------------------------------------------------- */
function saveTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates speichern.</div>";
        return;
    }

    $file  = basename($_POST['file']);
    $title = trim($_POST['title'] ?? "");

    /* Template-Dateiname ggf. ändern */
    $oldFile = $file;
    $newFile = slugifyTitle($title) . ".json";

    if ($newFile !== $oldFile) {
        $oldPath = "$TEMPLATE_FOLDER/$oldFile";
        $newPath = "$TEMPLATE_FOLDER/$newFile";

        /* Falls Zielname existiert → _2, _3, ... */
        if (file_exists($newPath)) {
            $base = pathinfo($newFile, PATHINFO_FILENAME);
            $i = 2;
            do {
                $newFile = $base . "_" . $i . ".json";
                $newPath = "$TEMPLATE_FOLDER/$newFile";
                $i++;
            } while (file_exists($newPath));
        }

        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }

        /* Ab hier mit neuem Dateinamen weiterarbeiten */
        $file = $newFile;
    }

    /* Eingabefelder einlesen */
    $secArr   = $_POST['sec']   ?? [];
    $labelArr = $_POST['label'] ?? [];
    $typeArr  = $_POST['type']  ?? [];
    $placeholderArr = $_POST['placeholder'] ?? [];
    $infoArr  = $_POST['info'] ?? [];
    $optionsArr = $_POST['options'] ?? [];
    $innameArr = $_POST['inname'] ?? [];
    $inresultArr = $_POST['inresult'] ?? [];

    /* Flache Liste erzeugen */
    $flat = [];
    foreach ($secArr as $i => $sec){
        $sec   = trim($sec);
        $label = trim($labelArr[$i] ?? "");
        $typeBase = trim($typeArr[$i] ?? "");
        $opts = trim($optionsArr[$i] ?? "");
        $placeholder = trim($placeholderArr[$i] ?? "");
        $info = trim($infoArr[$i] ?? "");

        $inname   = ($innameArr[$i] ?? "0") === "1" ? "1" : "0";
        $inresult = ($inresultArr[$i] ?? "0") === "1" ? "1" : "0";

        if ($sec === "" || $label === "") continue;

        if ($typeBase === "radio" && $opts !== "") $type = "radio:" . $opts;
        elseif ($typeBase === "multi" && $opts !== "") $type = "multi:" . $opts;
        else $type = $typeBase;

        $flat[] = [
            "sec"         => $sec,
            "label"       => $label,
            "type"        => $type,
            "placeholder" => $placeholder,
            "info"        => $info,
            "inname"      => $inname,
            "inresult"    => $inresult
        ];
    }

    /* Reihenfolge ändern */
    if (isset($_POST['move_up'])) {
        $i = intval($_POST['move_up']);
        if ($i > 0) {
            $tmp = $flat[$i];
            $flat[$i] = $flat[$i-1];
            $flat[$i-1] = $tmp;
        }
    }

    if (isset($_POST['move_down'])) {
        $i = intval($_POST['move_down']);
        if ($i < count($flat)-1) {
            $tmp = $flat[$i];
            $flat[$i] = $flat[$i+1];
            $flat[$i+1] = $tmp;
        }
    }

    /* Sektionen verschieben */
    if (isset($_POST['move_sec_up'])) {
        $secName = $_POST['move_sec_up'];
        $secKeys = array_values(array_unique(array_column($flat, "sec")));
        $pos = array_search($secName, $secKeys);

        if ($pos > 0) {
            $tmp = $secKeys[$pos];
            $secKeys[$pos] = $secKeys[$pos-1];
            $secKeys[$pos-1] = $tmp;
        }

        $newFlat = [];
        foreach ($secKeys as $sec) {
            foreach ($flat as $row) {
                if ($row["sec"] === $sec) $newFlat[] = $row;
            }
        }
        $flat = $newFlat;
    }

    if (isset($_POST['move_sec_down'])) {
        $secName = $_POST['move_sec_down'];
        $secKeys = array_values(array_unique(array_column($flat, "sec")));
        $pos = array_search($secName, $secKeys);

        if ($pos < count($secKeys)-1) {
            $tmp = $secKeys[$pos];
            $secKeys[$pos] = $secKeys[$pos+1];
            $secKeys[$pos+1] = $tmp;
        }

        $newFlat = [];
        foreach ($secKeys as $sec) {
            foreach ($flat as $row) {
                if ($row["sec"] === $sec) $newFlat[] = $row;
            }
        }
        $flat = $newFlat;
    }

    /* Zeile löschen */
    if (isset($_POST['delete_row'])) {
        $i = intval($_POST['delete_row']);
        unset($flat[$i]);
        $flat = array_values($flat);
    }

    /* Neues Feld hinzufügen */
    if (!empty($_POST['new_label'])) {
        $newSec   = trim($_POST['new_sec']);
        $newLabel = trim($_POST['new_label']);
        $newType  = trim($_POST['new_type']);
        $newOpts  = trim($_POST['new_options']);
        $newPlaceholder = trim($_POST['new_placeholder']);
        $newInfo  = trim($_POST['new_info']);

        $newinname   = ($_POST['new_inname'] ?? "0") === "1" ? "1" : "0";
        $newinresult = ($_POST['new_inresult'] ?? "0") === "1" ? "1" : "0";

        if (($newType === "radio" || $newType === "multi") && $newOpts !== "") {
            $newType = $newType . ":" . $newOpts;
        }

        $flat[] = [
            "sec"         => $newSec,
            "label"       => $newLabel,
            "type"        => $newType,
            "placeholder" => $newPlaceholder,
            "info"        => $newInfo,
            "inname"      => $newinname,
            "inresult"    => $newinresult
        ];
    }

    /* Struktur wiederherstellen */
    $sections = [];
    foreach ($flat as $row){
        if (!isset($sections[$row["sec"]])) $sections[$row["sec"]] = [];

        $entry = ["type" => $row["type"]];

        if ($row["placeholder"] !== "") $entry["placeholder"] = $row["placeholder"];
        if ($row["info"] !== "") $entry["info"] = $row["info"];

        $entry["inname"]   = $row["inname"] === "1" ? "1" : "0";
        $entry["inresult"] = $row["inresult"] === "1" ? "1" : "0";

        $sections[$row["sec"]][$row["label"]] = $entry;
    }

    /* Template speichern */
    $tpl = [
        "title" => $title,
        "sections" => $sections
    ];

    file_put_contents("$TEMPLATE_FOLDER/$file", json_encode($tpl, JSON_PRETTY_PRINT));

    /* Redirect robust: Header, sonst JS-Fallback */
    redirectTo("?mode=edittemplate&file=$file");
}

function slugifyTitle($title){
    $title = trim((string)$title);
    $title = str_replace(
        ["ä","ö","ü","Ä","Ö","Ü","ß"],
        ["ae","oe","ue","Ae","Oe","Ue","ss"],
        $title
    );
    $title = preg_replace('/[^a-zA-Z0-9]+/', '_', $title);
    $title = preg_replace('/_+/', '_', $title);
    $title = strtolower(trim($title, '_'));
    return $title !== "" ? $title : "template";
}

function rkrk_checklist_allocate_new_id($templateFile){
    global $JSON_FOLDER;
    $templateFile = basename(trim((string)$templateFile));
    $tpl = loadTemplate($templateFile);
    $tplTitle = $tpl["title"] ?? $templateFile;
    $tplSlug = slugifyTitle($tplTitle);
    $stamp = date('Ymd_Hi');
    $idBase = $tplSlug . "_" . $stamp;
    $id = $idBase;
    $path = "$JSON_FOLDER/$id.json";
    $i = 2;
    while (file_exists($path)) {
        $id = $idBase . "_" . $i;
        $path = "$JSON_FOLDER/$id.json";
        $i++;
    }
    return $id;
}

function rkrk_checklist_extract_allgemein(array $fields){
    $out = array('datum' => '', 'pilot' => '', 'feld' => '');
    $allg = (isset($fields['Allgemein']) && is_array($fields['Allgemein'])) ? $fields['Allgemein'] : array();
    foreach ($allg as $lbl => $val) {
        $n = normalizeKey((string)$lbl);
        $v = is_array($val) ? implode('; ', $val) : trim((string)$val);
        if (($n === 'datum_zeit' || $n === 'flugzeit' || $n === 'zeit') && $out['datum'] === '') {
            $out['datum'] = $v;
        }
        if ($n === 'pilot' && $out['pilot'] === '') {
            $out['pilot'] = $v;
        }
        if (($n === 'feld' || $n === 'feld_nummer') && $out['feld'] === '') {
            $out['feld'] = $v;
        }
    }
    if ($out['datum'] !== '' && $out['pilot'] !== '' && $out['feld'] !== '') {
        return $out;
    }
    foreach ($fields as $secValues) {
        if (!is_array($secValues)) {
            continue;
        }
        foreach ($secValues as $lbl => $val) {
            $n = normalizeKey((string)$lbl);
            $v = is_array($val) ? implode('; ', $val) : trim((string)$val);
            if (($n === 'datum_zeit' || $n === 'flugzeit' || $n === 'zeit') && $out['datum'] === '') {
                $out['datum'] = $v;
            }
            if ($n === 'pilot' && $out['pilot'] === '') {
                $out['pilot'] = $v;
            }
            if (($n === 'feld' || $n === 'feld_nummer') && $out['feld'] === '') {
                $out['feld'] = $v;
            }
        }
    }
    return $out;
}

function rkrk_checklist_resolve_save_id($templateFile, $editIdRaw, array $fields){
    global $JSON_FOLDER;
    $id = rkrk_checklist_extract_allgemein($fields);
    $matchId = findExistingChecklistId($templateFile, $id['datum'], $id['pilot'], $id['feld']);
    if ($matchId !== '') {
        return $matchId;
    }
    $editId = basename(trim((string)$editIdRaw));
    if ($editId !== '') {
        return $editId;
    }
    return rkrk_checklist_allocate_new_id($templateFile);
}

function rkrk_checklist_clear_dynamic_suchresultat_keys(array &$fields){
    if (!isset($fields["Suchresultat"]) || !is_array($fields["Suchresultat"])) {
        return;
    }
    foreach (array_keys($fields["Suchresultat"]) as $lbl) {
        if (rkrk_label_is_per_feld_dynamic_suchresultat((string)$lbl)) {
            unset($fields["Suchresultat"][$lbl]);
        }
    }
}

if (!function_exists('rkrk_checklist_build_feld_instances_from_fields')) {
	function rkrk_checklist_build_feld_instances_from_fields(array $fields, $feldOverride = '') {
		$feldRaw = trim($feldOverride !== '' ? (string)$feldOverride : (string)($fields['Allgemein']['Feld#'] ?? ''));
		$fieldOrder = rkrk_parse_field_ids_with_duplicates($feldRaw);
		$timeRaw = trim((string)($fields['Suchresultat']['Zeit zum Feld'] ?? ''));
		$timeEntries = rkrk_parse_field_time_entries($timeRaw);
		$timeQueues = array();
		foreach ($timeEntries as $te) {
			$fid = (string)($te['fid'] ?? '');
			if ($fid === '') {
				continue;
			}
			if (!isset($timeQueues[$fid])) {
				$timeQueues[$fid] = array();
			}
			$timeQueues[$fid][] = (string)($te['time'] ?? '');
		}
		$seqMap = array();
		$instances = array();
		foreach ($fieldOrder as $fid) {
			$seqMap[$fid] = ($seqMap[$fid] ?? 0) + 1;
			$tm = '';
			if (isset($timeQueues[$fid]) && count($timeQueues[$fid]) > 0) {
				$tm = (string)array_shift($timeQueues[$fid]);
			}
			$instances[] = array('fid' => $fid, 'seq' => $seqMap[$fid], 'time' => $tm);
		}
		return $instances;
	}
}

if (!function_exists('rkrk_checklist_instance_key_from_parts')) {
	function rkrk_checklist_instance_key_from_parts($fid, $time, $seq) {
		$seq = trim((string)$seq);
		if ($seq === '') {
			$seq = '1';
		}
		return (string)$fid . '|' . trim((string)$time) . '|' . $seq;
	}
}

if (!function_exists('rkrk_checklist_prune_suchresultat_to_feld_list')) {
	function rkrk_checklist_prune_suchresultat_to_feld_list(array &$fields) {
		if (!isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) {
			return;
		}
		$instances = rkrk_checklist_build_feld_instances_from_fields($fields);
		$validKeys = array();
		foreach ($instances as $inst) {
			$validKeys[rkrk_checklist_instance_key_from_parts($inst['fid'], $inst['time'], $inst['seq'])] = true;
		}
		foreach (array_keys($fields['Suchresultat']) as $lbl) {
			if (!rkrk_label_is_per_feld_dynamic_suchresultat((string)$lbl)) {
				continue;
			}
			$t = trim((string)$lbl);
			if (!preg_match('/\(([^)]+)\)\s*$/u', $t, $mInner)) {
				unset($fields['Suchresultat'][$lbl]);
				continue;
			}
			$inner = trim((string)$mInner[1]);
			if (!preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([0-2]?\d:[0-5]\d))?(?:\s*\|\s*(\d+))?/iu', $inner, $m)) {
				unset($fields['Suchresultat'][$lbl]);
				continue;
			}
			$key = rkrk_checklist_instance_key_from_parts($m[1], trim((string)($m[2] ?? '')), trim((string)($m[3] ?? '1')));
			if (!isset($validKeys[$key])) {
				unset($fields['Suchresultat'][$lbl]);
			}
		}
	}
}


function extractColor($label){
    if (preg_match('/\((g|r|o)\)$/i', $label, $m)){
        $colorKey = strtolower($m[1]);

        $map = [
            'g' => '#6bb36b', // green
            'r' => '#d06a6a', // red
            'o' => '#d89b4c', // orange
        ];

        $clean = trim(preg_replace('/\((g|r|o)\)$/i', '', $label));

        return [$clean, $map[$colorKey] ?? null];
    }

    return [$label, null];
}

function formatCHDatetime($raw){
    $raw = trim((string)$raw);
    if ($raw === "") return "";

    // HTML datetime-local
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
    if ($dt) return rkrk_format_display_datetime($dt);

    // ISO mit Sekunden
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw);
    if ($dt) return rkrk_format_display_datetime($dt);

    // Fallback
    try {
        $dt = new DateTime($raw);
        return rkrk_format_display_datetime($dt);
    } catch (Exception $e) {
        return $raw;
    }
}

function rkrk_format_display_datetime(DateTime $dt){
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez'
    ];
    $m = intval($dt->format('n'));
    $mmm = $months[$m] ?? $dt->format('M');
    return sprintf(
        '%02d.%s.%04d %02d:%02d',
        intval($dt->format('d')),
        $mmm,
        intval($dt->format('Y')),
        intval($dt->format('H')),
        intval($dt->format('i'))
    );
}

function formatChecklistDatetime($dtRaw){
    $dtRaw = trim((string)$dtRaw);
    if ($dtRaw === "") return "";

    // 1) HTML datetime-local Format: 2026-03-31T18:33
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dtRaw);

    // 2) Fallback: gespeichertes Format mit Sekunden: 2026-03-31 18:33:00
    if (!$dt) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dtRaw);
    }

    // 3) Fallback: einfach versuchen zu parsen
    if (!$dt) {
        try { $dt = new DateTime($dtRaw); } catch (Exception $e) { return $dtRaw; }
    }

    // Ausgabe: dd.mmm.yyyy hh:mm  -> z.B. 31.Mar.2026 18:33
    return rkrk_format_display_datetime($dt);
}

function formatChecklistDatetimeNumeric($dtRaw){
    $dtRaw = trim((string)$dtRaw);
    if ($dtRaw === "") return "";

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dtRaw);
    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dtRaw);
    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i', $dtRaw);
    if (!$dt) {
        try { $dt = new DateTime($dtRaw); } catch (Exception $e) { return $dtRaw; }
    }
    return $dt->format('d.m.Y H:i');
}

function formatPrintTimeByLabel($label, $value){
    $l = mb_strtolower(trim((string)$label), 'UTF-8');
    if ($l === 'flugzeit' || $l === 'zeit' || $l === 'datum & zeit') {
        return formatChecklistDatetimeNumeric($value);
    }
    return $value;
}

/* -----------------------------------------------------------
   COLORIZE PRINT
----------------------------------------------------------- */
function colorizePrintValue($value, $typeString = ''){
    if ($value === "" || $value === null) return "";

    // Multiline ONLY for textarea or real newlines
    $isTextarea = ($typeString === "textarea");
    $hasNewline = (!is_array($value) && preg_match("/\r\n|\r|\n/", (string)$value));

    if ($isTextarea || $hasNewline) {
        return nl2br(safe((string)$value));
    }

    // Font-color-only styling (color comes from extractColor, same as form options)
    $renderOne = function($raw){
        list($clean, $color) = extractColor($raw);
        if (!$color) {
            return safe($clean);
        }
        return "<span style='color:$color; font-weight:600;'>".safe($clean)."</span>";
    };

    // MULTI (array) → one line, comma-separated
    if (is_array($value)) {
        $out = [];
        foreach ($value as $v) {
            if (trim((string)$v) === "") continue;
            $out[] = $renderOne($v);
        }
        return implode(", ", $out);
    }

    // MULTI stored as string "a, b" or "a; b" → still one line
    $raw = trim((string)$value);
    if (strpos($raw, ",") !== false || strpos($raw, ";") !== false) {
        $parts = preg_split('/\s*[,;]\s*/', $raw);
        if (count($parts) > 1) {
            $out = [];
            foreach ($parts as $p) {
                if (trim($p) === "") continue;
                $out[] = $renderOne($p);
            }
            return implode(", ", $out);
        }
    }

    // SINGLE value
    return $renderOne($raw);
}

/* -----------------------------------------------------------
   CHECKLISTEN-LISTE PRINT
----------------------------------------------------------- */
function showChecklistPrint(){
    global $JSON_FOLDER, $TEMPLATE_FOLDER, $IconLoc;

    $id = basename($_GET['id'] ?? "");
    $path = "$JSON_FOLDER/$id.json";

    if (!file_exists($path)) {
        echo "Checkliste nicht gefunden.";
        exit;
    }

    $data = json_decode(file_get_contents($path), true);
    $tplName = $data["template"] ?? "";
    $tpl = loadTemplate($tplName);

    $title = $tpl["title"] ?? "Checkliste";
    $fields = $data["fields"] ?? [];

    // 🔥 Print-Dateiname = gespeicherter JSON-Dateiname
    $printTitle = $id . " – Druckansicht";

    echo "<!doctype html>
<html lang='de'>
<head>
<meta charset='utf-8'>
<title>".htmlspecialchars($printTitle)."</title>

<style>
body {
    font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;
    margin: 40px;
    color: #1a1a1a;
}

/* Header */
.print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.print-title {
    font-size: 28px;
    font-weight: 700;
    color: #4F6A3C; /* Grün */
}

.print-logo img {
    height: 80px;
    opacity: 0.95;
}

/* Abschnitt */
.section {
    margin-top: 25px;
    border-top: 3px solid #4F6A3C; /* Grün */
    padding-top: 12px;
}

.section h2 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #4F6A3C; /* Grün */
}

/* Zweispaltiges Layout */
.field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 40px;
}

/* Feld */
.field {
    border-bottom: 1px solid #A9C48A; /* hellgrün */
    padding-bottom: 6px;
}

.label {
    font-weight: 600;
    color: #4F6A3C; /* Grün */
}

.value {
    margin-top: 2px;
    color: #000;
}

/* Hervorgehobene Felder */
.highlight .label,
.highlight .value {
    font-weight: 700;
    color: #2F4A1F; /* dunkleres Grün */
}

.wx-print-warn {
    color: #9a5600;
    font-weight: 700;
}

.wx-print-bad {
    color: #8f1624;
    font-weight: 700;
}

.plan-print-icon {
    display: inline-block;
    margin-right: 6px;
    font-size: 1.05em;
}

.print-field-block {
    border: 1px solid #A9C48A;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
}

.print-field-title {
    font-weight: 700;
    color: #2F4A1F;
    margin-bottom: 8px;
}
.print-field-title .wx-ngt-print-inline {
    display: inline;
    vertical-align: middle;
    margin-left: 4px;
}
.print-field-title .wx-ngt-print-inline img {
    vertical-align: middle;
    height: 16px;
    margin-left: 3px;
}

.print-field-layout {
    display: grid;
    grid-template-columns: minmax(180px, 320px) 1fr;
    gap: 10px;
    align-items: start;
}

.print-plan-img {
    display: block;
    max-width: 360px;
    max-height: 220px;
    width: auto;
    height: auto;
    border: 1px solid #cfd6df;
    border-radius: 4px;
    margin-bottom: 8px;
}

/* Print */
@media print {
    body { margin: 20px; }
    .no-print { display: none !important; }
    .print-field-layout {
        display: grid !important;
        grid-template-columns: minmax(180px, 320px) 1fr !important;
    }
    .wx-print-warn, .wx-print-bad {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}</style>
</head>

<body>
<div class='print-header'>
    <div class='print-title'>".htmlspecialchars($title)."</div>
    <div class='print-logo'>
        <img src='".$IconLoc."/logo.jpg'>
    </div>
</div>
";

    /* ----------------------------------------
       1) TEMPLATE-FELDER DRUCKEN (zweispaltig)
    ---------------------------------------- */
    $fieldMetaMap = rkrk_get_field_meta_map();
    foreach ($tpl["sections"] as $secName => $secFields) {
        $printSecName = ($secName === "Wetter") ? "Wetter zum Startzeitpunkt" : $secName;
        echo "<div class='section'><h2>".htmlspecialchars($printSecName)."</h2>";

        if ($secName === "Suchresultat") {
            $sr = (isset($fields["Suchresultat"]) && is_array($fields["Suchresultat"])) ? $fields["Suchresultat"] : [];
            $blocks = rkrk_checklist_collect_suchresultat_blocks($sr, false);
            $blocks = rkrk_checklist_apply_field_times_to_blocks($blocks, trim((string)($sr['Zeit zum Feld'] ?? '')));
            // Fehlende Flugfelder aus "Feld" ebenfalls anzeigen (auch ohne Status/Funde).
            $feldRaw = '';
            foreach ($fields as $secVals) {
                if (!is_array($secVals)) continue;
                foreach ($secVals as $lbl => $val) {
                    if (normalizeKey((string)$lbl) === 'feld') {
                        $feldRaw = is_array($val) ? implode(', ', array_map('strval', $val)) : (string)$val;
                        break 2;
                    }
                }
            }
            $plannedOrder = rkrk_parse_field_ids_with_duplicates($feldRaw);
            if (count($plannedOrder) > 0) {
                $timeQueues = [];
                $timeEntries = rkrk_parse_field_time_entries(trim((string)($sr['Zeit zum Feld'] ?? '')));
                foreach ($timeEntries as $te) {
                    $fidQ = (string)($te['fid'] ?? '');
                    $tmQ = trim((string)($te['time'] ?? ''));
                    if ($fidQ === '' || $tmQ === '') continue;
                    if (!isset($timeQueues[$fidQ])) $timeQueues[$fidQ] = [];
                    $timeQueues[$fidQ][] = $tmQ;
                }
                $existingKeys = [];
                foreach ($blocks as $b0) {
                    $k0 = (string)($b0['fid'] ?? '') . '|' . trim((string)($b0['time'] ?? '')) . '|' . (string)($b0['seq'] ?? '1');
                    $existingKeys[$k0] = true;
                }
                $seqByFid = [];
                foreach ($plannedOrder as $fidPlanned) {
                    $fidPlanned = trim((string)$fidPlanned);
                    if ($fidPlanned === '') continue;
                    $seqByFid[$fidPlanned] = ($seqByFid[$fidPlanned] ?? 0) + 1;
                    $seqPlanned = (string)$seqByFid[$fidPlanned];
                    $tmPlanned = '';
                    if (!empty($timeQueues[$fidPlanned])) {
                        $tmPlanned = trim((string)array_shift($timeQueues[$fidPlanned]));
                    }
                    $kPlanned = $fidPlanned . '|' . $tmPlanned . '|' . $seqPlanned;
                    if (isset($existingKeys[$kPlanned])) continue;
                    $blocks[] = [
                        'ord' => 100000 + count($blocks),
                        'fid' => $fidPlanned,
                        'time' => $tmPlanned,
                        'seq' => $seqPlanned,
                        'status' => [],
                        'funde' => [],
                        'other' => '',
                    ];
                    $existingKeys[$kPlanned] = true;
                }
            }
            foreach ($blocks as $bk => $b) {
                $fid = (string)($b['fid'] ?? '');
                $meta = $fieldMetaMap[$fid] ?? [];
                $blocks[$bk]['name'] = trim((string)($meta['name'] ?? ''));
                $blocks[$bk]['flugplan'] = trim((string)($meta['flugplan'] ?? ''));
            }
            usort($blocks, static function ($a, $b) {
                $ta = rkrk_checklist_flugzeit_for_title((string)($a['time'] ?? ''));
                $tb = rkrk_checklist_flugzeit_for_title((string)($b['time'] ?? ''));
                $ha = ($ta !== '');
                $hb = ($tb !== '');
                if ($ha && !$hb) return -1;
                if (!$ha && $hb) return 1;
                if ($ha && $hb && $ta !== $tb) return strcmp($ta, $tb);
                $fidCmp = strcmp((string)($a['fid'] ?? ''), (string)($b['fid'] ?? ''));
                if ($fidCmp !== 0) return $fidCmp;
                return intval($a['seq'] ?? 0) <=> intval($b['seq'] ?? 0);
            });
            if (count($blocks) === 0) {
                echo "<div class='field'><div class='value'>Keine Suchresultat-Blöcke vorhanden.</div></div>";
            } else {
                foreach ($blocks as $b) {
                    $title = rkrk_checklist_field_title_with_flugzeit($b["fid"], $b["name"] ?? '', $b["time"] ?? '');
                    $fidMeta = $fieldMetaMap[$b["fid"]] ?? [];
                    echo "<div class='print-field-block'>";
                    echo "<div class='print-field-layout'>";
                    echo "<div>";
                    if ($b["flugplan"] !== "") {
                        $planSrc = rkrk_checklist_encode_media_url($b["flugplan"]);
                        echo "<img class='print-plan-img' src='" . htmlspecialchars($planSrc, ENT_QUOTES, 'UTF-8') . "' alt='Flugplan " . htmlspecialchars($b["fid"], ENT_QUOTES, 'UTF-8') . "'>";
                    } else {
                        echo "<div class='field'><div class='value'>Kein Flugplanbild</div></div>";
                    }
                    if (!empty($fidMeta["bauer_details_show"])) {
                        $psum = trim((string)($fidMeta["bauer_details_summary"] ?? ""));
                        $pbody = (string)($fidMeta["bauer_details_body_html"] ?? "");
                        if ($psum !== "") {
                            echo "<div class='print-bauer-below-plan' style='margin-top:8px;padding:6px;border:1px solid #ddd;border-radius:4px;font-size:9pt;line-height:1.35;text-align:left;color:#111827;background:#fafafa;'><strong>"
                                . htmlspecialchars($psum, ENT_QUOTES, 'UTF-8')
                                . "</strong>"
                                . ($pbody !== ""
                                    ? "<div style='margin-top:5px;border-top:1px solid #e5e7eb;padding-top:5px;'>" . $pbody . "</div>"
                                    : "")
                                . "</div>";
                        }
                    }
                    echo "</div>";
                    echo "<div>";
                    $ngtPrint = trim((string)($fidMeta["ngt_icons_html"] ?? ""));
                    echo "<div class='print-field-title'>" . htmlspecialchars($title);
                    if ($ngtPrint !== "") {
                        echo ' <span class="wx-ngt-print-inline" aria-hidden="true">' . $ngtPrint . '</span>';
                    }
                    echo "</div>";
                    echo "<div class='field'><div class='label'>Status</div><div class='value'>" . htmlspecialchars(implode(", ", $b["status"])) . "</div></div>";
                    echo "<div class='field'><div class='label'>Funde</div><div class='value'>" . htmlspecialchars(implode(", ", $b["funde"])) . "</div></div>";
                    if (trim((string)$b["other"]) !== "") {
                        echo "<div class='field'><div class='label'>Andere</div><div class='value'>" . htmlspecialchars((string)$b["other"]) . "</div></div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
            }
            echo "</div>";
            continue;
        }

        echo "<div class='field-grid'>";

        foreach ($secFields as $label => $meta) {
            $val = $fields[$secName][$label] ?? "";
            $isDependentField = (parseDependentField((string)$label) !== null);
            if ($isDependentField) {
                $hasValue = false;
                if (is_array($val)) {
                    foreach ($val as $vv) {
                        if (trim((string)$vv) !== '') { $hasValue = true; break; }
                    }
                } else {
                    $hasValue = (trim((string)$val) !== '');
                }
                if (!$hasValue) continue;
            }
            if (is_array($val)) $val = implode(", ", $val);
            $val = formatPrintTimeByLabel($label, $val);
            $wxCtx = ($secName === 'Wetter') ? ($fields[$secName] ?? null) : null;
            $valHtml = rkrk_print_weather_value($secName, $label, $val, $wxCtx);
            $isFlightPlanLabel = (rkrk_is_dynamic_flightplan_label($label) || trim((string)$label) === 'Flugplan');
            if ($isFlightPlanLabel && trim((string)$val) !== '') {
                $url = htmlspecialchars((string)$val, ENT_QUOTES);
                $valHtml = "<span class='plan-print-icon'>🗺️</span><a href='{$url}' target='_blank' rel='noopener'>Flugplan anzeigen</a>";
            }

            // 🔥 Spezialfelder fett
            $isHighlight = in_array($label, [
                "Datum & Zeit", "Auftrag", "Feld", "Ort", "Flugort", "Flugzeit"
            ]);

            $cls = $isHighlight ? "field highlight" : "field";

            echo "<div class='$cls'>
                    <div class='label'>".htmlspecialchars($label)."</div>
                    <div class='value'>{$valHtml}</div>
                  </div>";
        }

        echo "</div></div>";
    }

    /* ----------------------------------------
       2) ZUSÄTZLICHE FELDER DRUCKEN
    ---------------------------------------- */
    $extra = [];

    foreach ($fields as $secName => $secFields) {
        foreach ($secFields as $label => $value) {
            if (!isset($tpl["sections"][$secName][$label])) {
                if ($secName === "Suchresultat" && (
                    rkrk_is_dynamic_status_label($label) ||
                    rkrk_is_dynamic_status_other_label($label) ||
                    rkrk_is_dynamic_flightplan_label($label) ||
                    rkrk_is_dynamic_startpoint_label($label) ||
                    rkrk_is_dynamic_startpoint_google_label($label) ||
                    trim((string)$label) === "Zeit zum Feld"
                )) {
                    continue;
                }
                $extra[$secName][$label] = $value;
            }
        }
    }

    if (!empty($extra)) {
        echo "<div class='section'><h2>Zusätzliche Felder</h2>";
        echo "<div class='field-grid'>";

        foreach ($extra as $secName => $secFields) {
            foreach ($secFields as $label => $value) {
                $isDependentField = (parseDependentField((string)$label) !== null);
                if ($isDependentField) {
                    $hasValue = false;
                    if (is_array($value)) {
                        foreach ($value as $vv) {
                            if (trim((string)$vv) !== '') { $hasValue = true; break; }
                        }
                    } else {
                        $hasValue = (trim((string)$value) !== '');
                    }
                    if (!$hasValue) continue;
                }
                if (is_array($value)) $value = implode(", ", $value);
                $value = formatPrintTimeByLabel($label, $value);
                $wxCtxX = ($secName === 'Wetter') ? ($secFields ?? null) : null;
                $valueHtml = rkrk_print_weather_value($secName, $label, $value, $wxCtxX);
                $isFlightPlanLabel = (rkrk_is_dynamic_flightplan_label($label) || trim((string)$label) === 'Flugplan');
                if ($isFlightPlanLabel && trim((string)$value) !== '') {
                    $url = htmlspecialchars((string)$value, ENT_QUOTES);
                    $valueHtml = "<span class='plan-print-icon'>🗺️</span><a href='{$url}' target='_blank' rel='noopener'>Flugplan anzeigen</a>";
                }

                echo "<div class='field'>
                        <div class='label'>".htmlspecialchars($label)."</div>
                        <div class='value'>{$valueHtml}</div>
                      </div>";
            }
        }

        echo "</div></div>";
    }

    echo "</body></html>";
}

/* -----------------------------------------------------------
   CHECKLISTEN-LISTE
----------------------------------------------------------- */
function showChecklistList($showAll = false){
    global $JSON_FOLDER, $TEMPLATE_FOLDER;

    // Lokaler Modus – verhindert Undefined Variable Notices
    $mode = $_GET['mode'] ?? 'list';

    pageHeaderLocal("Checklisten");
	if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
		rkrk_checklist_toolbar_echo_styles_once();
	}

    if(isset($_GET['saved']))
        echo "<div class='rkrk-card success'>✓ Checkliste gespeichert.</div>";

    if(isset($_GET['deleted']))
        echo "<div class='rkrk-card danger'>✓ Eintrag gelöscht.</div>";

    if (isset($_GET["err"]) && (string)$_GET["err"] === "import") {
        echo "<div class='rkrk-card' style='border-color:#fca5a5;background:#fff5f5;'><strong>Import abgebrochen</strong>: Datei fehlt oder ungültige Anfrage.</div>";
    }

    echo "<div class='rkrk-card checklist-header'>
        <div class='checklist-title'>Neue Checkliste</div>
        <div class='checklist-buttons'>";

    foreach (glob("$TEMPLATE_FOLDER/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;

        echo "<a href='?mode=form&file=$base&blank=1' class='btn btn-primary'>
                ➕ ".safe($title)."
              </a>";
    }

    echo "</div></div>";

    echo "<div class='rkrk-card'>
            <h3>".($showAll ? "Alle gespeicherten Checklisten" : "Letzte 12 gespeicherte Checklisten")."</h3>";

    $fltTemplate = trim((string)($_GET['flt_template'] ?? ''));
    $fltPilot    = trim((string)($_GET['flt_pilot'] ?? ''));
    $fltText     = trim((string)($_GET['flt_text'] ?? ''));
    $fltFrom     = trim((string)($_GET['flt_from'] ?? ''));
    $fltTo       = trim((string)($_GET['flt_to'] ?? ''));

    if ($showAll) {
        echo "<form method='get' action='' id='listallFilterForm' style='margin:10px 0 14px 0; display:flex; gap:8px; flex-wrap:wrap; align-items:end;'>
                <input type='hidden' name='mode' value='listall'>
                <div>
                    <label style='font-size:12px; color:#666;'>Template</label><br>
                    <input type='text' data-live-filter='1' name='flt_template' value='".safe($fltTemplate)."' style='min-width:180px;' placeholder='z.B. Rehkitzsuche'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Pilot</label><br>
                    <input type='text' data-live-filter='1' name='flt_pilot' value='".safe($fltPilot)."' style='min-width:140px;' placeholder='z.B. Thomy'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Textsuche</label><br>
                    <input type='text' data-live-filter='1' name='flt_text' value='".safe($fltText)."' style='min-width:220px;' placeholder='Name/Info enthält...'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Von</label><br>
                    <input type='date' data-live-filter='1' name='flt_from' value='".safe($fltFrom)."'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Bis</label><br>
                    <input type='date' data-live-filter='1' name='flt_to' value='".safe($fltTo)."'>
                </div>
                <a class='btn' href='?mode=listall'>Reset</a>
              </form>
              <script>
              (function(){
                var form = document.getElementById('listallFilterForm');
                if (!form) return;
                var t = null;
                var submitSoon = function(){
                  clearTimeout(t);
                  t = setTimeout(function(){ form.submit(); }, 220);
                };
                form.querySelectorAll('[data-live-filter=\"1\"]').forEach(function(el){
                  el.addEventListener('input', submitSoon);
                  el.addEventListener('change', submitSoon);
                });
              })();
              </script>";
    }

    $jsonFiles = glob("$JSON_FOLDER/*.json");
    usort($jsonFiles, function($a, $b){
        $getTs = function($path){
            $raw = @file_get_contents($path);
            if ($raw !== false && trim((string)$raw) !== '') {
                $obj = json_decode($raw, true);
                if (is_array($obj) && isset($obj['fields']) && is_array($obj['fields'])) {
                    $ctx = rkrk_extract_flight_context_from_fields($obj['fields']);
                    $rawDt = trim((string)($ctx['flugzeit'] ?? ''));
                    $dt = rkrk_parse_flight_dt($rawDt);
                    if ($dt instanceof DateTime) return $dt->getTimestamp();
                }
            }
            $mtime = @filemtime($path);
            return ($mtime === false) ? 0 : (int)$mtime;
        };
        return $getTs($b) <=> $getTs($a);
    });

    if (!$showAll) {
        $jsonFiles = array_slice($jsonFiles, 0, 12);
    }

    echo "<table class='list-table'>
            <tr>
                <th>Template</th>
                <th>Datum & Zeit</th>
                <th>Name</th>
                <th>Pilot</th>
                <th>Information</th>
                <th>Aktion</th>
            </tr>";

    foreach ($jsonFiles as $jsonFile){

        $id   = basename($jsonFile, ".json");
        $data = loadChecklist($id);

        if (!$data) continue;

        /* Template laden – robust */
        $tplName = $data["template"] ?? "";
        $tpl = null;

        if ($tplName !== "") {
            // 1) exakter Name
            $tpl = loadTemplate($tplName);

            // 2) falls ohne .json gespeichert → ergänzen
            if (!$tpl && file_exists("$TEMPLATE_FOLDER/$tplName.json")) {
                $tpl = loadTemplate("$tplName.json");
            }
        }

        /* Falls Template fehlt → leere Struktur */
        if (!$tpl || !isset($tpl["sections"])) {
            $tpl = ["title" => $tplName, "sections" => []];
        }

        $title = $tpl["title"] ?? $tplName;
        $fields = $data["fields"] ?? [];

        /* Standardfelder */
        
		// Flugzeit bevorzugen, falls vorhanden
		$flugzeitRaw = $fields["Allgemein"]["Flugzeit"] ?? "";
		$datumZeitRaw = $fields["Allgemein"]["Datum & Zeit"] ?? "";

		// Formatierung
		if (trim($flugzeitRaw) !== "") {
			$dt = formatChecklistDatetime($flugzeitRaw);
		} else {
			$dt = formatChecklistDatetime($datumZeitRaw);
		}

		$pilotRaw = $fields["Allgemein"]["Pilot"] ?? "";
        $pilot = is_array($pilotRaw) ? implode(", ", $pilotRaw) : $pilotRaw;

        /* -------------------------
           Name (N‑Flag)
        ------------------------- */
        $nameParts = [];

        foreach ($tpl["sections"] as $secName => $secFields){
            foreach ($secFields as $label => $meta){

                if (($meta["inname"] ?? "0") !== "1") continue;

                $val = $fields[$secName][$label] ?? "";

                if (is_array($val)) $val = implode("; ", $val);
                if (trim($val) === "") continue;

                $nameParts[] = $label.": ".$val;
            }
        }

        $name = implode(" | ", $nameParts);
        $nameDisplayPartsRaw = array_values(array_filter(array_map('trim', explode('|', (string)$name)), function($p){
            return $p !== '';
        }));
        $nameDisplayParts = [];
        foreach ($nameDisplayPartsRaw as $p) {
            if (preg_match('/^\s*(Datum\s*&\s*Zeit|Flugzeit)\s*:\s*(.+)\s*$/iu', (string)$p, $mNoLabel)) {
                $nameDisplayParts[] = formatChecklistDatetime(trim((string)$mNoLabel[2])); // local format, no label
            } else {
                $nameDisplayParts[] = $p;
            }
        }
        $nameHtml = htmlspecialchars(implode("\n", $nameDisplayParts), ENT_QUOTES);
        $nameHtml = nl2br($nameHtml);

        /* -------------------------
           Information: nur Funde-Summary
        ------------------------- */
        $fundeValue = "";
        foreach ($fields as $secName => $secValues) {
            if (strcasecmp((string)$secName, "Suchresultat") !== 0) continue;
            if (!is_array($secValues)) continue;
            if (!array_key_exists("Funde", $secValues)) continue;
            $fundeValue = $secValues["Funde"];
            break;
        }
        if (is_array($fundeValue)) $fundeValue = implode("; ", $fundeValue);
        $fundeValue = trim((string)$fundeValue);
        $info = ($fundeValue !== "") ? $fundeValue : "keine";

        // Filters (active mainly for listall)
        $hayTemplate = mb_strtolower((string)$title);
        $hayPilot    = mb_strtolower((string)$pilot);
        $hayBlob     = mb_strtolower((string)($name . " | " . $info));
        $dtDay = "";
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$flugzeitRaw, $m)) {
            $dtDay = $m[1];
        } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$datumZeitRaw, $m2)) {
            $dtDay = $m2[1];
        }

        if ($fltTemplate !== '' && mb_strpos($hayTemplate, mb_strtolower($fltTemplate)) === false) continue;
        if ($fltPilot !== '' && mb_strpos($hayPilot, mb_strtolower($fltPilot)) === false) continue;
        if ($fltText !== '' && mb_strpos($hayBlob, mb_strtolower($fltText)) === false) continue;
        if ($fltFrom !== '' && $dtDay !== '' && $dtDay < $fltFrom) continue;
        if ($fltTo !== '' && $dtDay !== '' && $dtDay > $fltTo) continue;

        $rowActions = ""
            . "<a class='chk-act' title='Bearbeiten' aria-label='Bearbeiten' href='?mode=form&amp;file=" . rawurlencode($tplName) . "&amp;edit=" . rawurlencode($id) . "'>" . rkrk_checklist_toolbar_svg_edit() . "</a>"
            . "<a class='chk-act' title='Nur lesen' aria-label='Lesen' href='?mode=form&amp;file=" . rawurlencode($tplName) . "&amp;edit=" . rawurlencode($id) . "&amp;readonly=1'>" . rkrk_checklist_toolbar_svg_read() . "</a>"
            . "<a class='chk-act' title='Kopieren' aria-label='Kopieren' href='?mode=copyjson&amp;id=" . rawurlencode($id) . "'>" . rkrk_checklist_toolbar_svg_copy() . "</a>"
            . "<a class='chk-act' title='Druckansicht (Strg+P / PDF)' aria-label='Drucken' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_print_link($id), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener'>" . rkrk_checklist_toolbar_svg_print() . "</a>"
            . "<a class='chk-act' title='Löschen' aria-label='Löschen' href='?mode=deletejson&amp;id=" . rawurlencode($id) . "' onclick='return confirm(\"Eintrag wirklich löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.\")' style='color:#8b2e2e;border-color:#e8c8c8;'>" . rkrk_checklist_toolbar_svg_trash() . "</a>";

        echo "<tr>
                <td>".safeListValue($title)."</td>
                <td>".safeListValue($dt)."</td>
                <td style='white-space:normal; line-height:1.25;'>".$nameHtml."</td>
                <td>".safeListValue($pilot)."</td>
                <td>".safeListValue($info)."</td>
                <td class='checklist-actions' style='white-space:nowrap;'>" . $rowActions . "</td>
              </tr>";
    }

    echo "</table></div>";

    echo "<div style='text-align:center; margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;'>

    <a href='?mode=list' 
       class='btn' 
       ".($mode=='list' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Neueste 12 Checklisten
    </a>

    <a href='?mode=listall' 
       class='btn' 
       ".($mode=='listall' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Alle Checklisten
    </a>";
	
	//if (GetAccess("TestOnly")) {
	

		if (GetAccessByFunction ('ChecklistTemplate')) {		
			echo "
				<a href='?mode=templates' class='btn'>
					📁 Templates bearbeiten
				</a>
			";
		}	
		echo "	
			<a href='?mode=linkbuilder' class='btn'>
				🔗 URL Generator
			</a>
		";
	//}
echo "	
</div>
";
    pageFooterLocal();
}

/* -----------------------------------------------------------
   Merge Template with Saved Checklist
----------------------------------------------------------- */
function mergeTemplateWithSaved($templateSections, $savedFields) {
    $merged = [];

    foreach ($templateSections as $secName => $fields) {
        foreach ($fields as $label => $meta) {
            $merged[$secName][$label] = $savedFields[$secName][$label] ?? "";
        }
    }

    foreach ($savedFields as $secName => $fields) {
        foreach ($fields as $label => $value) {
            if (!isset($merged[$secName][$label])) {
                $merged[$secName][$label] = $value;
            }
        }
    }

    return $merged;
}

function findExistingChecklistId($templateFile, $datumZeitValue = "", $pilotValue = "", $feldValue = "") {
    global $JSON_FOLDER;
    $templateFile = basename(trim((string)$templateFile));
    if ($templateFile === "" || !is_dir($JSON_FOLDER)) return "";

    $targetDay = rkrk_sync_day_ymd((string)$datumZeitValue);
    if ($targetDay === "") $targetDay = substr(trim((string)$datumZeitValue), 0, 10);
    $targetPilot = trim((string)$pilotValue);
    $targetFelder = rkrk_parse_field_ids_with_duplicates((string)$feldValue);
    $targetFelder = array_values(array_unique($targetFelder));
    if ($targetDay === "" && $targetPilot === "" && count($targetFelder) === 0) {
        return "";
    }
    $targetSet = array_flip($targetFelder);

    $bestId = "";
    $bestScore = -1;
    $bestTs = -1;
    foreach (glob($JSON_FOLDER . "/*.json") as $jsonFile) {
        $raw = @file_get_contents($jsonFile);
        if ($raw === false || trim($raw) === "") continue;
        $obj = json_decode($raw, true);
        if (!is_array($obj)) continue;
        if (basename((string)($obj["template"] ?? "")) !== $templateFile) continue;

        $fields = $obj["fields"] ?? [];
        if (!is_array($fields)) continue;

        $savedId = rkrk_checklist_extract_allgemein($fields);
        $datumZeit = $savedId['datum'];
        $pilot = $savedId['pilot'];
        $savedFeldRaw = $savedId['feld'];

        $savedDay = rkrk_sync_day_ymd((string)$datumZeit);
        if ($savedDay === "") $savedDay = substr((string)$datumZeit, 0, 10);
        if ($targetDay !== "" && $savedDay !== $targetDay) continue;
        if ($targetPilot !== "") {
            $pilotOk = false;
            if (function_exists('rkrk_sync_pilot_match')) {
                $pilotOk = rkrk_sync_pilot_match($pilot, $targetPilot);
            } else {
                $pilotOk = (mb_strtolower(trim((string)$pilot), 'UTF-8') === mb_strtolower($targetPilot, 'UTF-8'));
            }
            if (!$pilotOk) continue;
        }

        $savedFelder = array_values(array_unique(rkrk_parse_field_ids_with_duplicates($savedFeldRaw)));
        $overlap = 0;
        if (count($targetSet) > 0 && count($savedFelder) > 0) {
            foreach ($savedFelder as $fid) {
                if (isset($targetSet[$fid])) $overlap++;
            }
            if ($overlap === 0) continue;
        }

        $hasResultData = 0;
        if (isset($fields["Suchresultat"]) && is_array($fields["Suchresultat"])) {
            foreach ($fields["Suchresultat"] as $k => $v) {
                $kt = trim((string)$k);
                if (rkrk_is_dynamic_status_label($kt) || rkrk_is_dynamic_status_other_label($kt)) {
                    $hasResultData = 1;
                    break;
                }
            }
        }
        $score = 0;
        if ($targetDay !== "" && $targetPilot !== "") {
            $score = 100;
        }
        $score += ($overlap * 10) + ($hasResultData * 3);

        $mtime = @filemtime($jsonFile);
        $mtime = ($mtime === false) ? 0 : (int)$mtime;
        if ($score > $bestScore || ($score === $bestScore && $mtime >= $bestTs)) {
            $bestScore = $score;
            $bestTs = $mtime;
            $bestId = basename($jsonFile, ".json");
        }
    }

    return $bestId;
}

/* Sync-Helfer (rkrk_sync_* etc.) → inline above */

function rkrk_parse_field_ids_with_duplicates($raw){
    $out = [];
    $tokens = preg_split('/[;,\n\r\t ]+/', (string)$raw);
    foreach ((array)$tokens as $tok) {
        $tok = trim((string)$tok);
        if ($tok === '') continue;
        $id = rkrk_checklist_extract_feld_id($tok);
        if ($id !== '') $out[] = $id;
    }
    return $out;
}

function rkrk_parse_field_time_entries($raw){
    $out = [];
    $parts = preg_split('/[;,\n\r]+/', (string)$raw);
    foreach ((array)$parts as $p) {
        $p = trim((string)$p);
        if ($p === '') continue;
        if (preg_match('/(' . rkrk_checklist_feld_id_regex() . ')\s*[:=]\s*([0-2]?\d:[0-5]\d)?/iu', $p, $m)) {
            $out[] = ['fid' => $m[1], 'time' => trim((string)($m[2] ?? ''))];
        }
    }
    return $out;
}

function rkrk_migrate_suchresultat_legacy_labels(&$fields, $overrideFeldRaw = "", $overrideZeitZumFeldRaw = ""){
    if (!is_array($fields) || !isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) return;
    $sr = $fields['Suchresultat'];
    $hasLegacy = false;
    foreach ($sr as $lbl => $_v) {
        $t = trim((string)$lbl);
        if (preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([^|)]*))?(?:\s*\|\s*(\d+))?\)$/iu', $t, $mDyn)) {
            $dynTime = trim((string)($mDyn[2] ?? ''));
            $dynSeq  = trim((string)($mDyn[3] ?? ''));
            // Migrate when this is an old dynamic format without time and/or sequence.
            if ($dynTime === '' || $dynSeq === '') {
                $hasLegacy = true;
                break;
            }
        } elseif (
            preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')\)$/iu', $t) ||
            preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')\b/iu', $t)) {
            $hasLegacy = true;
            break;
        }
    }
    if (!$hasLegacy) return;

    $feldRaw = '';
    foreach ($fields as $secVals) {
        if (!is_array($secVals)) continue;
        foreach ($secVals as $lbl => $val) {
            if (normalizeKey((string)$lbl) === 'feld') {
                $feldRaw = is_array($val) ? implode(', ', array_map('strval', $val)) : (string)$val;
                break 2;
            }
        }
    }
    $fieldOrder = rkrk_parse_field_ids_with_duplicates($feldRaw);
    $overrideFieldOrder = rkrk_parse_field_ids_with_duplicates((string)$overrideFeldRaw);
    if (count($overrideFieldOrder) > 0) {
        $fieldOrder = $overrideFieldOrder;
    }
    $timeRaw = trim((string)($sr['Zeit zum Feld'] ?? ''));
    $overrideTimeRaw = trim((string)$overrideZeitZumFeldRaw);
    if ($overrideTimeRaw !== '') {
        $timeRaw = $overrideTimeRaw;
        $fields['Suchresultat']['Zeit zum Feld'] = $overrideTimeRaw;
    }
    $timeEntries = rkrk_parse_field_time_entries($timeRaw);
    $timeQueues = [];
    foreach ($timeEntries as $te) {
        $fid = (string)$te['fid'];
        if (!isset($timeQueues[$fid])) $timeQueues[$fid] = [];
        $timeQueues[$fid][] = (string)$te['time'];
    }
    $seqMap = [];
    $instances = [];
    foreach ($fieldOrder as $fid) {
        $seqMap[$fid] = ($seqMap[$fid] ?? 0) + 1;
        $tm = '';
        if (isset($timeQueues[$fid]) && count($timeQueues[$fid]) > 0) $tm = (string)array_shift($timeQueues[$fid]);
        $instances[] = ['fid'=>$fid, 'seq'=>$seqMap[$fid], 'time'=>$tm];
    }

    $legacy = [];
    foreach ($sr as $lbl => $val) {
        $t = trim((string)$lbl);
        $fid = '';
        $isOther = false;
        if (preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([^|)]*))?(?:\s*\|\s*(\d+))?\)$/iu', $t, $mDyn)) {
            $fid = $mDyn[1];
            $isOther = (mb_strpos($t, ' andere ') !== false);
        } elseif (preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')\)$/iu', $t, $m)) {
            $fid = $m[1];
            $isOther = (mb_strpos($t, ' andere ') !== false);
        } elseif (preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')\b/iu', $t, $m2)) {
            // Very old format: "148-113 - ...", "... - andere"
            $fid = $m2[1];
            $isOther = (mb_stripos($t, 'andere') !== false);
        } else {
            continue;
        }
        if (!isset($legacy[$fid])) $legacy[$fid] = ['status'=>[], 'other'=>[]];
        if ($isOther) $legacy[$fid]['other'][] = $val;
        else $legacy[$fid]['status'][] = $val;
    }

    foreach (array_keys($sr) as $lbl) {
        $t = trim((string)$lbl);
        if (preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')(?:\s*\|\s*([^|)]*))?(?:\s*\|\s*(\d+))?\)$/iu', $t) ||
            preg_match('/^Status und Suchresultate(?: andere)? \((' . rkrk_checklist_feld_id_regex() . ')\)$/iu', $t) ||
            preg_match('/^(' . rkrk_checklist_feld_id_regex() . ')\b/iu', $t)) {
            unset($sr[$lbl]);
        }
    }

    foreach ($instances as $inst) {
        $fid = $inst['fid'];
        if (!isset($legacy[$fid])) continue;
        $inner = $fid;
        if ($inst['time'] !== '') $inner .= ' | ' . $inst['time'];
        $inner .= ' | ' . (string)$inst['seq'];
        $kStatus = 'Status und Suchresultate (' . $inner . ')';
        $kOther  = 'Status und Suchresultate andere (' . $inner . ')';
        if (!array_key_exists($kStatus, $sr) && !empty($legacy[$fid]['status'])) {
            $sr[$kStatus] = array_shift($legacy[$fid]['status']);
        }
        if (!array_key_exists($kOther, $sr) && !empty($legacy[$fid]['other'])) {
            $sr[$kOther] = array_shift($legacy[$fid]['other']);
        }
    }
    $fields['Suchresultat'] = $sr;
}

function applyPresetValue($label, $typeString, $currentValue) {
    global $rkrkChecklistBlankNew;
    if (!empty($rkrkChecklistBlankNew)) {
        return $currentValue;
    }

    // Find matching GET param by normalized key
    $raw = getPresetForLabel($label);
    if ($raw === null) {
        return $currentValue; // nothing to preset
    }

    // Ensure typeString is always a string
    if (is_array($typeString)) {
        $typeString = $typeString['type'] ?? 'text';
    }

    $preset = trim((string)$raw);

    // MULTI: a;b;c or a,b,c
    if (strpos($typeString, "multi:") === 0) {
        $parts = preg_split('/[;,]/', $preset);
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts, fn($x) => $x !== ""));
    }

    // CHECKBOX (single)
    if ($typeString === "checkbox") {
        $p = strtolower($preset);
        return ($p === "1" || $p === "true" || $p === "ja" || $p === "on") ? "1" : "0";
    }

    // RADIO
    if (strpos($typeString, "radio:") === 0) {
        $opts = explode("|", substr($typeString, 6));
        foreach ($opts as $o) {
            if (trim($o) === $preset) {
                return $preset;
            }
        }
        return $currentValue; // invalid preset → ignore
    }

    // TEXT / NUMBER / DATE / DATETIME
    return $preset;
}

/* -----------------------------------------------------------
   Render Felder
----------------------------------------------------------- */
function renderField($secName, $label, $type, $value, $readonly) {

    $placeholder = "";
    $info = "";

    if (is_array($type)) {
        $placeholder = $type["placeholder"] ?? "";
        $info        = $type["info"] ?? "";
        $typeString  = $type["type"] ?? "text";
    } else {
        $typeString = $type;
    }

    $value = applyPresetValue($label, $typeString, $value);
    $key   = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));

    /* Dependencies */
    $dep = parseDependentField($label);
    $depAttr = "";
    $depClass = "";

    if ($dep) {
        $parentKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $dep['parent']));
        $depAttr =
            " data-dependent='1' " .
            " data-parent-key=\"$parentKey\" " .
            " data-trigger=\"" . htmlspecialchars($dep['trigger'], ENT_QUOTES) . "\" ";
        $depClass = " dependent-field ";
    }

    $disabled = "";
    $readOnlyAttr = "";
    if ($readonly) {
        if (strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0 || $typeString === "checkbox") {
            $disabled = "disabled";
        } else {
            $readOnlyAttr = " readonly";
        }
    }

    echo "<div class='field $depClass" . ($readonly ? " field-readonly" : "") . "' $depAttr>";
    echo "<label class='field-label'>".safe($label);

    if (strcasecmp((string)$secName, "Suchresultat") === 0 && rkrk_label_is_per_feld_dynamic_suchresultat($label)) {
        $fidWx = rkrk_extract_feld_id_from_dynamic_label($label);
        if ($fidWx !== "") {
            $metaRow = rkrk_get_field_meta_map();
            $warnungTxt = trim((string)(($metaRow[$fidWx]["warnung"] ?? "")));
            if ($warnungTxt !== "") {
                echo rkrk_render_flugzone_title_icons_html($warnungTxt, 18);
            }
        }
    }

    if (!empty($info)) {
        $containsHtml = strip_tags($info) !== $info;

        if ($containsHtml) {
            echo " <span class='info-icon' data-info=\"".htmlspecialchars($info, ENT_QUOTES)."\">ⓘ</span>";
        } else {
            echo " <span class='info-icon'>ⓘ
                    <span class='info-tooltip'>".nl2br(safe($info))."</span>
                   </span>";
        }
    }

    echo "</label>";

    if (!empty($placeholder) &&
        (strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0)) {

        echo "<small class='field-placeholder'>".safe($placeholder)."</small>";
    }

    /* Helper: builds inline style so selected background == border color */
 
	$buildColorStyle = function($color, $isSelected) {
        if (!$color) return "";
        $parts = [];
        $parts[] = "border-color:$color";
        $parts[] = "--option-color:$color";
        //if ($isSelected) {
            // This is the key change: selected background = border color
            //$parts[] = "background:$color";
        //}
        return "style='" . implode("; ", $parts) . ";'";
    };

    /* -------------------------
       RADIO (mit Farbcodes)
    ------------------------- */
    if (strpos($typeString, "radio:") === 0) {

        $opts = explode("|", substr($typeString, 6));
        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = ($value === $oTrim);

            list($cleanLabel, $color) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);

            echo "<label class='color-option$selectedClass' $style>
                    <input type='radio'
                           name='fields[$secName][$label]'
                           data-field-key=\"$key\"
                           value='".safe($oTrim)."'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                  </label>";
        }

        echo "</div>";
    }

    /* -------------------------
       MULTI (mit Farbcodes)
    ------------------------- */
    elseif (strpos($typeString, "multi:") === 0) {

        $opts     = explode("|", substr($typeString, 6));
        $selected = is_array($value) ? $value : explode(";", (string)$value);

        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = in_array($oTrim, $selected, true);

            list($cleanLabel, $color) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);

            echo "<label class='color-option$selectedClass' $style>
                    <input type='checkbox'
                           name='fields[$secName][$label][]'
                           data-field-key=\"$key\"
                           value='".safe($oTrim)."'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                  </label>";
        }

        echo "</div>";
    }

    /* -------------------------
       CHECKBOX (einzeln)
    ------------------------- */
    elseif ($typeString === "checkbox") {

        $isSelected = ($value == "1");

        list($cleanLabel, $color) = extractColor($label);

        $selectedClass = $isSelected ? " selected" : "";
        $style = $buildColorStyle($color, $isSelected);

        echo "<div class='option-grid'>
                <label class='color-option$selectedClass' $style>
                    <input type='checkbox'
                           name='fields[$secName][$label]'
                           data-field-key=\"$key\"
                           value='1'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                </label>
              </div>";
    }

    /* TEXTAREA */
    elseif ($typeString === "textarea") {
        echo "<textarea
                 name='fields[$secName][$label]'
                 data-field-key=\"$key\"
                 rows='3'
                 placeholder='".safe($placeholder)."'
                 $disabled>"
                .safe($value).
             "</textarea>";
    }

    /* DATETIME */
    elseif ($typeString === "datetime") {
        global $rkrkChecklistIsNew;
        if (($value === "" || $value === null) && !empty($rkrkChecklistIsNew)) {
            $val = rkrk_checklist_default_datetime_local();
        } else {
            $val = $value;
        }

        echo "<input type='datetime-local'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($val)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* DATE */
    elseif ($typeString === "date") {
        global $rkrkChecklistIsNew;
        if (($value === "" || $value === null) && !empty($rkrkChecklistIsNew)) {
            $val = rkrk_checklist_default_date_ymd();
        } else {
            $val = $value;
        }
        echo "<input type='date'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($val)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* NUMBER */
    elseif ($typeString === "number") {
        echo "<input type='number'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

	/* ------------------------- FLUGZEIT ------------------------- */
	elseif ($typeString === "flugzeit") {
		// identisch wie datetime-local, aber mit eigener Semantik
		global $rkrkChecklistIsNew;
		if (($value === "" || $value === null) && !empty($rkrkChecklistIsNew)) {
			$val = rkrk_checklist_default_datetime_local();
		} else {
			$val = $value;
		}
		echo "<input id='field_$key' data-field-type='flugzeit' data-field-key='$key' type='datetime-local' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' $disabled $readOnlyAttr />";
	}

	/* ------------------------- FLUGORT ------------------------- */
	elseif ($typeString === "flugort") {
		// normaler Textinput
		$val = (string)$value;
		echo "<input id='field_$key' data-field-type='flugort' data-field-key='$key' type='text' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' placeholder='".htmlspecialchars($placeholder, ENT_QUOTES)."' $disabled $readOnlyAttr />";
	}

    /* STANDARD TEXT */
    else {
        echo "<input type='text'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled $readOnlyAttr>";
    }

    echo "</div>";
}

/* -----------------------------------------------------------
   CHECKLIST FORM
----------------------------------------------------------- */



/* -----------------------------------------------------------
   WETTER (Flugort / Flugzeit)
   - Live-Abfrage (Open-Meteo) wenn Flugzeit nicht >6h in der Vergangenheit
   - Speichern der Abfrage in der Checklist-JSON bei vorhandenem Edit-ID
   - Anzeige aus JSON + Hinweis, wenn Flugzeit >6h in der Vergangenheit
   - Anzeige enthält Taupunkt + Windrichtung
   - Zusätzlich: Vorhersage ±1 Stunde um die Flugzeit
   - Ort kann via Ortsname UND/ODER via LAT/LON angegeben werden.
----------------------------------------------------------- */

function rkrk_http_get_json($url){
    // robust: auch wenn allow_url_fopen deaktiviert ist, per curl versuchen
    if (function_exists('curl_init')){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: RKRK-Checklist/1.0'],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $raw === '' || $code >= 400) {
            // fallback to file_get_contents
        } else {
            $json = json_decode($raw, true);
            return is_array($json) ? $json : null;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 8,
            'header'  => "User-Agent: RKRK-Checklist/1.0\r\nAccept: application/json\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || trim($raw) === '') return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

/* rkrk_parse_flight_dt → inline above */

function rkrk_is_older_than_6h(DateTime $dt){
    $now = new DateTime('now', $dt->getTimezone());
    return (($now->getTimestamp() - $dt->getTimestamp()) > 6*3600);
}

function rkrk_deg_to_compass($deg){
    if ($deg === null || $deg === '') return '';
    $deg = floatval($deg);
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $idx = (int)round(($deg % 360) / 22.5) % 16;
    return $dirs[$idx];
}

function rkrk_valid_latlon($lat, $lon){
    $lat = str_replace(',', '.', trim((string)$lat));
    $lon = str_replace(',', '.', trim((string)$lon));
    if ($lat === '' || $lon === '') return false;
    if (!is_numeric($lat) || !is_numeric($lon)) return false;
    $lat = floatval($lat);
    $lon = floatval($lon);
    return !($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180);
}

function rkrk_parse_latlon_inline($input){
    $s = trim((string)$input);
    if ($s === '') return null;

    $s2 = preg_replace('/\b(lat|latitude|lon|lng|longitude)\b\s*[:=]?/i', '', $s);
    $s2 = trim($s2);
    $s2 = str_replace([';', '/', "\t", "\n", "\r"], ' ', $s2);
    $s2 = preg_replace('/\s*,\s*/', ' ', $s2);

    $parts = preg_split('/\s+/', trim($s2));
    if (count($parts) < 2) return null;

    $lat = str_replace(',', '.', $parts[0]);
    $lon = str_replace(',', '.', $parts[1]);
    if (!is_numeric($lat) || !is_numeric($lon)) return null;

    $lat = floatval($lat);
    $lon = floatval($lon);
    if ($lat < -90 || $lat > 90) return null;
    if ($lon < -180 || $lon > 180) return null;

    return ['latitude'=>$lat, 'longitude'=>$lon];
}

function rkrk_geocode_open_meteo($name){
    // 1) Koordinaten im Ortsfeld
    $ll = rkrk_parse_latlon_inline($name);
    if ($ll){
        return [
            'name'     => trim((string)$name),
            'latitude' => $ll['latitude'],
            'longitude'=> $ll['longitude'],
            'timezone' => 'auto',
            'admin1'   => null,
            'country'  => null,
            'source'   => 'latlon_inline'
        ];
    }

    // 2) Ortsname → Geocoding
    $nameEnc = rawurlencode($name);
    $url = "https://geocoding-api.open-meteo.com/v1/search?name={$nameEnc}&count=1&language=de&format=json";
    $j = rkrk_http_get_json($url);
    if (!$j || empty($j['results'][0])) return null;
    $r = $j['results'][0];
    return [
        'name'     => $r['name'] ?? $name,
        'latitude' => $r['latitude'] ?? null,
        'longitude'=> $r['longitude'] ?? null,
        'timezone' => $r['timezone'] ?? 'auto',
        'admin1'   => $r['admin1'] ?? null,
        'country'  => $r['country'] ?? null,
        'source'   => 'name'
    ];
}

function rkrk_pick_hourly_window($hourly, $targetTs){
    $times = $hourly['time'] ?? [];
    if (!$times) return [];

    $bestI = 0; $bestD = null;
    foreach ($times as $i => $t){
        $d = abs(intval($t) - intval($targetTs));
        if ($bestD === null || $d < $bestD){ $bestD = $d; $bestI = $i; }
    }

    $out = [];
    foreach ([-1,0,1] as $off){
        $i = $bestI + $off;
        if (!isset($times[$i])) continue;
        $out[] = [
            'time'              => $times[$i],
            'temperature_2m'    => $hourly['temperature_2m'][$i] ?? null,
            'dewpoint_2m'       => $hourly['dewpoint_2m'][$i] ?? null,
            'precipitation_probability' => $hourly['precipitation_probability'][$i] ?? null,
            'cloudcover'        => $hourly['cloudcover'][$i] ?? null,
            'visibility'        => $hourly['visibility'][$i] ?? null,
            'windspeed_10m'     => $hourly['windspeed_10m'][$i] ?? null,
            'windspeed_80m'     => $hourly['windspeed_80m'][$i] ?? null,
            'winddirection_10m' => $hourly['winddirection_10m'][$i] ?? null,
            'winddirection_80m' => $hourly['winddirection_80m'][$i] ?? null,
            'weathercode'       => $hourly['weathercode'][$i] ?? null,
        ];
    }
    return $out;
}

function rkrk_fetch_weather_open_meteo($lat, $lon, DateTime $dt, $timezone='auto'){
    if ($lat === null || $lon === null) return null;
    $date = $dt->format('Y-m-d');

    $vars = 'temperature_2m,dewpoint_2m,precipitation_probability,cloudcover,visibility,windspeed_10m,windspeed_80m,winddirection_10m,winddirection_80m,weathercode';

	$url = "https://api.open-meteo.com/v1/forecast"
	 . "?latitude={$lat}"
	 . "&longitude={$lon}"
	 . "&hourly={$vars}"
	 . "&daily=sunrise,sunset"
	 . "&start_date={$date}"
	 . "&end_date={$date}"
	 . "&timezone=" . rawurlencode($timezone)
	 . "&timeformat=unixtime";

    $j = rkrk_http_get_json($url);
    if (!$j || empty($j['hourly']['time'])) return null;

    $hourly = $j['hourly'];
    $target = $dt->getTimestamp();
    $window = rkrk_pick_hourly_window($hourly, $target);
    $sunrise = $j['daily']['sunrise'][0] ?? null;
    $sunset  = $j['daily']['sunset'][0] ?? null;

    return [
        'provider'   => 'open-meteo',
        'fetched_at' => date('Y-m-d H:i:s'),
        'timezone'   => $j['timezone'] ?? $timezone,
        'target_unix'=> $target,
        'window'     => $window,
        'sunrise'    => $sunrise,
        'sunset'     => $sunset,
        'units'      => $j['hourly_units'] ?? [],
        'kp'         => rkrk_fetch_kp_index($target),
    ];
}

function rkrk_kp_label($kp){
    if (!is_numeric($kp)) return '';
    $k = floatval($kp);
    if ($k <= 2) return 'ruhig / leicht aktiv';
    if ($k <= 3) return 'leicht aktiv';
    if ($k <= 4) return 'aktiv';
    if ($k <= 5) return 'stuermisch';
    return 'stark stuermisch';
}

function rkrk_pick_latest_kp_from_rows($rows, $mode = 'object'){
    if (!is_array($rows) || count($rows) === 0) return null;
    $fallback = null;

    for ($i = count($rows)-1; $i >= 0; $i--){
        $row = $rows[$i];
        $kp = null;
        $time = null;

        if ($mode === 'products'){
            if (!is_array($row)) continue;
            $kp = $row['Kp'] ?? ($row['kp'] ?? ($row['kp_index'] ?? ($row[1] ?? null)));
            $time = $row['time_tag'] ?? ($row[0] ?? null);
        } else {
            if (!is_array($row)) continue;
            $kp = $row['kp'] ?? $row['kp_index'] ?? null;
            $time = $row['time_tag'] ?? null;
        }

        if (!is_numeric($kp)) continue;
        $kpf = floatval($kp);
        if (!is_finite($kpf) || $kpf < 0 || $kpf > 9) continue;

        $candidate = [
            'value' => $kpf,
            'label' => rkrk_kp_label($kpf),
            'time_utc' => $time
        ];
        // 0 kann real sein, wird aber nur als Fallback genommen.
        if ($kpf > 0) return $candidate;
        if ($fallback === null) $fallback = $candidate;
    }
    return $fallback;
}

function rkrk_fetch_kp_index($targetTs = null){
    // KPI aus NOAA 3-Day Forecast (3h-Bins)
    $j = rkrk_http_get_json("https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json");
    if (!is_array($j) || count($j) === 0) return null;

    $target = is_numeric($targetTs) ? intval($targetTs) : time();
    $entries = [];

    foreach ($j as $row){
        if (!is_array($row)) continue;
        $t = $row['time_tag'] ?? ($row[0] ?? null);
        $kpRaw = $row['Kp'] ?? ($row['kp'] ?? ($row['kp_index'] ?? ($row[1] ?? null)));
        if (!is_numeric($kpRaw)) continue;
        $kpf = floatval($kpRaw);
        if (!is_finite($kpf) || $kpf < 0 || $kpf > 9) continue;
        $tsRaw = trim((string)$t);
        if ($tsRaw === '') continue;
        $epoch = strtotime($tsRaw . ' UTC');
        if ($epoch === false) {
            $epoch = strtotime($tsRaw);
        }
        if ($epoch === false) continue;
        $entries[] = ['ts' => intval($epoch), 'kp' => $kpf, 'time_utc' => $tsRaw];
    }
    if (!$entries) return null;

    usort($entries, fn($a,$b) => $a['ts'] <=> $b['ts']);
    $picked = null;
    foreach ($entries as $e){
        if ($e['ts'] >= $target){ $picked = $e; break; }
    }
    if ($picked === null) $picked = $entries[count($entries)-1];

    return [
        'value' => $picked['kp'],
        'label' => rkrk_kp_label($picked['kp']),
        'time_utc' => $picked['time_utc']
    ];
}

function rkrk_estimate_visible_satellites($cloudCoverPct, $targetTs, $sunriseTs=null, $sunsetTs=null){
    // GNSS-Sichtbarkeit ist nicht vom Wetter/Tag-Nacht abhängig.
    // Ohne TLE-Berechnung nutzen wir eine konservative GPS-dominierte Schätzung,
    // die in Mitteleuropa typischerweise um ~11 liegt.
    $elevationMaskDeg = 10.0;
    $totalGnssSat = 31.0;
    $fraction = (1.0 - sin(deg2rad($elevationMaskDeg))) / 2.0;
    $estimate = intval(round($totalGnssSat * $fraction * 0.9));
    return max(8, min(16, $estimate));
}

/* -----------------------------------------------------------
   ZENTRALE KONFIGURATION: Kritische Wetterwerte
----------------------------------------------------------- */
function rkrk_weather_critical_config(){
    return [
        'wind_80m_kmh_max' => 28.0,
        'precipitation_probability_pct_max' => 60.0,
        'cloudcover_pct_max' => 85.0,
        /** Geschätzte Wolkuntergrenze AGL (LCL T/Taupunkt); darüber ist hohe Bewölkung nicht „kritisch“. */
        'cloud_ceiling_clear_min_agl_m' => 150.0,
        'visibility_km_warn' => 2.0,
        'visibility_km_bad' => 0.5,
        'visibility_km_min' => 0.5,
        'kp_index_warn' => 5.0,
        'kp_index_bad' => 7.0,
        'kp_index_min' => 7.0
    ];
}

function rkrk_first_number($value){
    if (is_numeric($value)) return floatval($value);
    $s = str_replace(',', '.', trim((string)$value));
    if (preg_match('/-?\d+(?:\.\d+)?/', $s, $m)) return floatval($m[0]);
    return null;
}

function rkrk_visibility_km_from_value($value){
    $raw = trim((string)$value);
    if ($raw === '') return null;
    $n = rkrk_first_number($raw);
    if ($n === null) return null;
    $s = mb_strtolower($raw, 'UTF-8');
    if (strpos($s, 'km') !== false) return $n;
    if (strpos($s, ' m') !== false || preg_match('/\d\s*m$/u', $s)) return $n / 1000.0;
    if ($n > 50) return $n / 1000.0; // nackte Meterwerte (z.B. 1800)
    return $n; // bereits km
}

function rkrk_is_weather_value_critical($metric, $value){
    $cfg = rkrk_weather_critical_config();
    $n = rkrk_first_number($value);
    if ($n === null) return false;
    switch ($metric){
        case 'wind_80m_kmh':
            return $n >= $cfg['wind_80m_kmh_max'];
        case 'precipitation_probability':
            return $n >= $cfg['precipitation_probability_pct_max'];
        case 'cloudcover':
            return $n >= $cfg['cloudcover_pct_max'];
        case 'visibility_km':
            $km = rkrk_visibility_km_from_value($value);
            if ($km === null) return false;
            return $km < $cfg['visibility_km_bad'];
        case 'kp_index':
            return $n > $cfg['kp_index_bad'];
        default:
            return false;
    }
}

/** Grobe Wolkuntergrenze AGL aus 2-m-T/Taupunkt (Lifting-Condensation-Level-Näherung, ~125 m pro °C Spreizung). */
function rkrk_est_cloud_base_agl_m_lcl($tempCValue, $dewCValue){
    $t = rkrk_first_number($tempCValue);
    $td = rkrk_first_number($dewCValue);
    if ($t === null || $td === null) return null;
    $spread = $t - $td;
    if ($spread < 0) $spread = 0;
    return 125.0 * $spread;
}

/**
 * Hohe Bewölkung: nur kritisch wenn Schwellen-Prozent erreicht UND geschätzte Untergrenze ≤ Grenze oder unbekannt.
 */
function rkrk_is_cloudcover_critical($coverValue, $tempCValue = null, $dewCValue = null){
    $cfg = rkrk_weather_critical_config();
    $n = rkrk_first_number($coverValue);
    if ($n === null || $n < $cfg['cloudcover_pct_max']) return false;
    $base = rkrk_est_cloud_base_agl_m_lcl($tempCValue, $dewCValue);
    if ($base === null) return true;
    return $base <= $cfg['cloud_ceiling_clear_min_agl_m'];
}

function rkrk_mark_weather_value($value, $level){
    $safeVal = safe((string)$value);
    $lvl = ($level === true) ? 'bad' : (string)$level;
    if ($lvl === 'bad') return "<span style='color:#b91c1c;font-weight:700;'>⚠️ {$safeVal} (kritisch)</span>";
    if ($lvl === 'warn') return "<span style='color:#9a5600;font-weight:700;'>⚠️ {$safeVal} (Warnung)</span>";
    return $safeVal;
}

function rkrk_kp_level($value){
    $cfg = rkrk_weather_critical_config();
    $n = rkrk_first_number($value);
    if ($n === null) return '';
    if ($n > $cfg['kp_index_bad']) return 'bad';
    if ($n > $cfg['kp_index_warn']) return 'warn';
    return '';
}

function rkrk_weathercode_icon($code){
    $c = intval($code);
    if ($c === 0) return '☀️';
    if ($c >= 1 && $c <= 3) return '🌤️';
    if ($c === 45 || $c === 48) return '🌫️';
    if (in_array($c, [51,53,55,56,57], true)) return '🌦️';
    if (in_array($c, [61,63,65,66,67,80,81,82], true)) return '🌧️';
    if (in_array($c, [71,73,75,77,85,86], true)) return '🌨️';
    if (in_array($c, [95,96,99], true)) return '⛈️';
    return '❓';
}

function rkrk_weathercode_text($code){
    $c = intval($code);
    if ($c === 0) return 'klar';
    if ($c === 1) return 'ueberwiegend klar';
    if ($c === 2) return 'teilweise bewoelkt';
    if ($c === 3) return 'bedeckt';
    if ($c === 45) return 'Nebel';
    if ($c === 48) return 'Reif-Nebel';
    if (in_array($c, [51,53,55], true)) return 'Niesel';
    if (in_array($c, [56,57], true)) return 'gefrierender Niesel';
    if (in_array($c, [61,63,65], true)) return 'Regen';
    if (in_array($c, [66,67], true)) return 'gefrierender Regen';
    if (in_array($c, [71,73,75], true)) return 'Schnee';
    if ($c === 77) return 'Schneekoerner';
    if (in_array($c, [80,81,82], true)) return 'Regenschauer';
    if (in_array($c, [85,86], true)) return 'Schneeschauer';
    if ($c === 95) return 'Gewitter';
    if (in_array($c, [96,99], true)) return 'Gewitter mit Hagel';
    return 'Wettercode ' . strval($c);
}

function rkrk_weathercode_meta($code){
    if (!preg_match('/\d+/', (string)$code, $m)) {
        return ['icon' => '', 'text' => ''];
    }
    $n = intval($m[0]);
    return [
        'icon' => rkrk_weathercode_icon($n),
        'text' => rkrk_weathercode_text($n)
    ];
}

function rkrk_weather_print_level($label, $value, $wetterContext = null){
    $lbl = strtolower(trim((string)$label));
    if (strpos($lbl, 'sichtweite') !== false) {
        $cfg = rkrk_weather_critical_config();
        $km = rkrk_visibility_km_from_value($value);
        if ($km === null) return '';
        if ($km < $cfg['visibility_km_bad']) return 'bad';
        if ($km < $cfg['visibility_km_warn']) return 'warn';
        return '';
    }
    $n = rkrk_first_number($value);
    if ($n === null) return '';

    if (strpos($lbl, 'temperatur') !== false) {
        if ($n < 0) return 'bad';
        if ($n < 5) return 'warn';
        return '';
    }
    if (strpos($lbl, 'windb') !== false || strpos($lbl, 'boeen') !== false || strpos($lbl, 'böen') !== false) {
        if ($n >= 36) return 'bad';
        if ($n >= 25) return 'warn';
        return '';
    }
    if (strpos($lbl, 'windrichtung') !== false || strpos($lbl, 'richtung') !== false) {
        return '';
    }
    if (strpos($lbl, 'wind') !== false) {
        if ($n >= 36) return 'bad';
        if ($n >= 25) return 'warn';
        return '';
    }
    if (strpos($lbl, 'regen') !== false && rkrk_is_weather_value_critical('precipitation_probability', $value)) return 'bad';
    if (strpos($lbl, 'bew') !== false) {
        $ctx = (is_array($wetterContext) && $wetterContext) ? $wetterContext : null;
        $t = $ctx['Temperatur'] ?? null;
        $td = $ctx['Taupunkt'] ?? null;
        if (rkrk_is_cloudcover_critical($value, $t, $td)) return 'bad';
    }
    if (strpos($lbl, 'sichtweite') !== false && rkrk_is_weather_value_critical('visibility_km', $value)) return 'bad';
    if (strpos($lbl, 'kp') !== false || strpos($lbl, 'aurora') !== false) {
        return rkrk_kp_level($value);
    }

    return '';
}

function rkrk_print_weather_value($secName, $label, $value, $wetterContext = null){
    $safeVal = safe((string)$value);
    if (strcasecmp((string)$secName, 'Wetter') !== 0) return $safeVal;
    $ctx = is_array($wetterContext) ? $wetterContext : null;
    $lvl = rkrk_weather_print_level($label, $value, $ctx);
    if ($lvl === 'warn') return "<span class='wx-print-warn'>{$safeVal}</span>";
    if ($lvl === 'bad') return "<span class='wx-print-bad'>{$safeVal}</span>";
    return $safeVal;
}

function rkrk_weather_nonempty($value){
    if ($value === null) return false;
    if (is_array($value)) return count($value) > 0;
    return trim((string)$value) !== '';
}

function rkrk_weather_has_meaningful_data($weather){
    if (!is_array($weather)) return false;
    $keys = [
        'Temperatur',
        'Taupunkt',
        'Regenwahrscheinlichkeit',
        'Wind (80m)',
        'Windrichtung',
        'Bewölkung',
        'Sichtweite',
        'Wettercode',
        'Koordinaten'
    ];
    foreach ($keys as $k) {
        if (rkrk_weather_nonempty($weather[$k] ?? null)) return true;
    }
    return false;
}

/**
 * Baut aus dem JS-Snapshot (Open-Meteo-Stunde) die gleichen Schlüssel wie im Checklisten-Wetter-JSON.
 */
function rkrk_normalize_snapshot_to_wetter_fields(array $s){
    $out = array();
    $ort = trim((string)($s['ort'] ?? ''));
    if ($ort !== '') {
        $out['Ort'] = $ort;
    }
    $zeit = trim((string)($s['flight_time_local'] ?? ''));
    if ($zeit === '' && isset($s['time'])) {
        $zeit = trim((string)$s['time']);
    }
    if ($zeit !== '') {
        $out['Zeit'] = $zeit;
    }

    if (array_key_exists('temp_c', $s) && $s['temp_c'] !== '' && $s['temp_c'] !== null) {
        $n = floatval($s['temp_c']);
        if (is_finite($n)) $out['Temperatur'] = str_replace('.', ',', sprintf('%.1f', $n));
    }
    if (array_key_exists('dewpoint_c', $s) && $s['dewpoint_c'] !== '' && $s['dewpoint_c'] !== null) {
        $n = floatval($s['dewpoint_c']);
        if (is_finite($n)) $out['Taupunkt'] = str_replace('.', ',', sprintf('%.1f', $n));
    }
    if (array_key_exists('precip_prob_pct', $s) && $s['precip_prob_pct'] !== '' && $s['precip_prob_pct'] !== null) {
        $n = floatval($s['precip_prob_pct']);
        if (is_finite($n)) $out['Regenwahrscheinlichkeit'] = strval(round($n));
    }
    if (array_key_exists('cloud_cover_pct', $s) && $s['cloud_cover_pct'] !== '' && $s['cloud_cover_pct'] !== null) {
        $n = floatval($s['cloud_cover_pct']);
        if (is_finite($n)) $out['Bewölkung'] = strval(round($n));
    }
    if (array_key_exists('weathercode', $s) && $s['weathercode'] !== '' && $s['weathercode'] !== null) {
        $n = floatval($s['weathercode']);
        if (is_finite($n)) {
            $out['Wettercode'] = strval(round($n));
            $wxMeta = rkrk_weathercode_meta($out['Wettercode']);
            if ($wxMeta['icon'] !== '') $out['Wettericon'] = $wxMeta['icon'];
            if ($wxMeta['text'] !== '') $out['Wetterbedeutung'] = $wxMeta['text'];
        }
    }

    $ws80 = isset($s['wind_80m_kmh']) ? floatval($s['wind_80m_kmh']) : null;
    if ($ws80 !== null && is_finite($ws80)) {
        $out['Wind (80m)'] = sprintf('%.1f km/h', $ws80);
    }

    $wd = isset($s['wind_dir_deg']) ? $s['wind_dir_deg'] : null;
    if ($wd !== null && $wd !== '' && is_finite(floatval($wd))) {
        $d = floatval($wd);
        $out['Windrichtung'] = strval(round($d)) . '° (' . rkrk_deg_to_compass($d) . ')';
    }

    $vis = isset($s['visibility_m']) ? floatval($s['visibility_m']) : null;
    if ($vis !== null && is_finite($vis) && $vis > 0) {
        $out['Sichtweite'] = strval(round($vis / 1000)) . ' km';
    }

    $gust = isset($s['wind_gusts_10m_kmh']) ? floatval($s['wind_gusts_10m_kmh']) : null;
    if ($gust !== null && is_finite($gust)) {
        $out['Windböen'] = sprintf('%.1f km/h', $gust);
    }

    $rh = isset($s['rh_pct']) ? floatval($s['rh_pct']) : null;
    if ($rh !== null && is_finite($rh)) {
        $out['Luftfeuchte'] = strval(round($rh)) . ' %';
    }

    $kpTxt = trim((string)($s['kp_display'] ?? ''));
    $kpLbl = trim((string)($s['kp_label'] ?? ''));
    if ($kpTxt !== '') {
        $out['KPI Aurora'] = $kpTxt;
        $out['KP Index'] = $kpTxt;
    }
    if ($kpLbl !== '') {
        $out['Aurora'] = $kpLbl;
    }

    $sat = isset($s['gnss_sat_count']) ? $s['gnss_sat_count'] : null;
    if ($sat !== null && $sat !== '' && is_finite(floatval($sat))) {
        $n = strval(round(floatval($sat)));
        $out['Sichtbare GNSS Satelliten'] = $n;
        $out['Sichtbare Satelliten'] = $n;
    }

    $lat = isset($s['lat']) ? $s['lat'] : null;
    $lon = isset($s['lon']) ? $s['lon'] : null;
    if ($lat !== null && $lon !== null && is_finite(floatval($lat)) && is_finite(floatval($lon))) {
        $out['Koordinaten'] = trim((string)$lat) . ', ' . trim((string)$lon);
    }

    return $out;
}

/**
 * Verstecktes Feld weather_json → fields.Wetter inkl. „Gespeichert am“ (beim Formular-Speichern).
 */
function rkrk_merge_post_weather_json_into_fields(array &$fields, $rawJson){
    $rawJson = trim((string)$rawJson);
    if ($rawJson === '') return;
    $w = json_decode($rawJson, true);
    if (!is_array($w) || !isset($w['snapshot']) || !is_array($w['snapshot'])) return;

    $row = rkrk_normalize_snapshot_to_wetter_fields($w['snapshot']);
    if (!rkrk_weather_has_meaningful_data($row)) return;

    $merged = (isset($fields['Wetter']) && is_array($fields['Wetter'])) ? $fields['Wetter'] : array();
    foreach ($row as $k => $v) {
        if (rkrk_weather_nonempty($v)) $merged[$k] = trim((string)$v);
    }
    $merged['Gespeichert am'] = date('Y-m-d H:i:s');
    $fields['Wetter'] = $merged;
}

function rkrk_extract_flight_context_from_fields($fields){
    $out = ['flugzeit' => '', 'flugort' => ''];
    if (!is_array($fields)) return $out;
    foreach ($fields as $secVals) {
        if (!is_array($secVals)) continue;
        foreach ($secVals as $lbl => $val) {
            $n = normalizeKey((string)$lbl);
            $v = is_array($val) ? implode('; ', array_map('strval', $val)) : trim((string)$val);
            if ($v === '') continue;
            if (($n === 'datum_zeit' || $n === 'flugzeit' || $n === 'zeit' || (strpos($n, 'datum') !== false && strpos($n, 'zeit') !== false)) && $out['flugzeit'] === '') {
                $out['flugzeit'] = $v;
            }
            if (($n === 'flugort' || $n === 'ort') && $out['flugort'] === '') {
                $out['flugort'] = $v;
            }
        }
    }
    if ($out['flugort'] === '' && isset($fields['Wetter']) && is_array($fields['Wetter'])) {
        $wOrt = trim((string)($fields['Wetter']['Ort'] ?? ''));
        if ($wOrt !== '') $out['flugort'] = $wOrt;
    }
    return $out;
}

function rkrk_extract_any_startpoint_latlon($fields){
    if (!is_array($fields) || !isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) return null;
    foreach ($fields['Suchresultat'] as $label => $value) {
        if (!rkrk_is_dynamic_startpoint_label((string)$label)) continue;
        $txt = trim((string)$value);
        if ($txt === '') continue;
        $ll = rkrk_parse_latlon_inline($txt);
        if (is_array($ll) && isset($ll['lat']) && isset($ll['lon'])) return $ll;
        if (preg_match('/^\s*(-?\d{1,2}(?:[.,]\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/', $txt, $m)) {
            return ['lat' => floatval(str_replace(',', '.', $m[1])), 'lon' => floatval(str_replace(',', '.', $m[2]))];
        }
    }
    return null;
}

function handleBackfillWeatherRecentMode(){
    global $JSON_FOLDER;
    $weeks = intval($_GET['weeks'] ?? 6);
    if ($weeks < 1) $weeks = 1;
    if ($weeks > 12) $weeks = 12;

    $cutoff = new DateTime('now');
    $cutoff->modify('-' . $weeks . ' weeks');

    $stats = [
        'weeks' => $weeks,
        'total_files' => 0,
        'in_range' => 0,
        'already_had_weather' => 0,
        'updated' => 0,
        'skipped_missing_context' => 0,
        'skipped_unresolved_location' => 0,
        'skipped_no_weather' => 0,
        'errors' => 0,
        'samples' => [
            'missing_context_ids' => [],
            'unresolved_location_ids' => [],
            'no_weather_ids' => []
        ]
    ];

    foreach (glob($JSON_FOLDER . '/*.json') as $path) {
        $stats['total_files']++;
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') continue;
        $json = json_decode($raw, true);
        if (!is_array($json)) continue;
        $fields = $json['fields'] ?? [];
        if (!is_array($fields)) continue;

        $ctx = rkrk_extract_flight_context_from_fields($fields);
        $flugzeit = trim((string)$ctx['flugzeit']);
        $flugort = trim((string)$ctx['flugort']);
        $dt = rkrk_parse_flight_dt($flugzeit);
        if (!$dt || $dt < $cutoff) continue;
        $stats['in_range']++;

        $existing = (isset($fields['Wetter']) && is_array($fields['Wetter'])) ? $fields['Wetter'] : [];
        if (rkrk_weather_has_meaningful_data($existing)) {
            $stats['already_had_weather']++;
            continue;
        }
        $startLatLon = rkrk_extract_any_startpoint_latlon($fields);
        if ($flugort === '' && $startLatLon) {
            $flugort = $startLatLon['lat'] . ', ' . $startLatLon['lon'];
        }
        if ($flugort === '') {
            $stats['skipped_missing_context']++;
            if (count($stats['samples']['missing_context_ids']) < 20) $stats['samples']['missing_context_ids'][] = basename($path, '.json');
            continue;
        }

        $geo = rkrk_resolve_flugort($flugort);
        if (!$geo && $startLatLon) {
            $geo = [
                'name' => $flugort,
                'latitude' => floatval($startLatLon['lat']),
                'longitude' => floatval($startLatLon['lon']),
                'timezone' => 'Europe/Zurich'
            ];
        }
        if (!$geo) {
            $feldRaw = '';
            foreach ($fields as $secVals) {
                if (!is_array($secVals)) continue;
                foreach ($secVals as $lbl => $val) {
                    if (normalizeKey((string)$lbl) === 'feld') {
                        $feldRaw = is_array($val) ? implode(', ', array_map('strval', $val)) : (string)$val;
                        break 2;
                    }
                }
            }
            if ($feldRaw !== '') {
                $ids = rkrk_parse_field_ids_with_duplicates($feldRaw);
                $meta = rkrk_get_field_meta_map();
                foreach ($ids as $fid) {
                    $sp = trim((string)($meta[$fid]['startpoint'] ?? ''));
                    if ($sp === '') continue;
                    $ll = rkrk_parse_latlon_inline($sp);
                    if (!is_array($ll) || !isset($ll['lat']) || !isset($ll['lon'])) continue;
                    $geo = [
                        'name' => $flugort !== '' ? $flugort : $fid,
                        'latitude' => floatval($ll['lat']),
                        'longitude' => floatval($ll['lon']),
                        'timezone' => 'Europe/Zurich'
                    ];
                    break;
                }
            }
        }
        if (!$geo) {
            $stats['skipped_unresolved_location']++;
            if (count($stats['samples']['unresolved_location_ids']) < 20) $stats['samples']['unresolved_location_ids'][] = basename($path, '.json');
            continue;
        }

        $wx = rkrk_fetch_weather_open_meteo($geo['latitude'], $geo['longitude'], $dt, $geo['timezone'] ?? 'auto');
        if (!$wx) {
            $fallbackWx = rkrk_find_weather_fallback_from_recent_checklists($flugort, $dt, basename($path, '.json'));
            if ($fallbackWx) {
                $ok = rkrk_save_weather_array_to_checklist(basename($path, '.json'), $fallbackWx, 'Fallback aus zeitnaher Checkliste');
                if ($ok) {
                    $stats['updated']++;
                    continue;
                }
            }
            $stats['skipped_no_weather']++;
            if (count($stats['samples']['no_weather_ids']) < 20) $stats['samples']['no_weather_ids'][] = basename($path, '.json');
            continue;
        }

        $id = basename($path, '.json');
        $ok = rkrk_save_weather_to_checklist($id, [
            'flugort' => $flugort,
            'flugzeit' => $flugzeit,
            'geo' => $geo,
            'wx' => $wx
        ]);
        if ($ok) $stats['updated']++;
        else $stats['errors']++;
    }

    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function rkrk_render_weather_html($flugort, DateTime $dt, $geo, $wx, $note=''){
    if (!$wx) {
        return "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
    }
    $isSavedPayload = isset($wx['Temperatur']) || isset($wx['Wind (80m)']) || isset($wx['Wettercode']);
    if ($isSavedPayload) {
        $temp = $wx['Temperatur'] ?? '–';
        $dew  = $wx['Taupunkt'] ?? '–';
        $wind = $wx['Wind (80m)'] ?? ($wx['Wind'] ?? '–');
        $dir  = $wx['Windrichtung'] ?? ($wx['Richtung'] ?? '–');
        $code = $wx['Wettercode'] ?? '–';
        $rain = $wx['Regenwahrscheinlichkeit'] ?? '–';
        $cc   = $wx['Bewölkung'] ?? '–';
        $vis  = $wx['Sichtweite'] ?? '–';
        $kpText = $wx['KPI Aurora'] ?? ($wx['KP Index'] ?? '–');
        $auroraText = $wx['Aurora'] ?? '–';
        $satText = $wx['Sichtbare GNSS Satelliten'] ?? ($wx['Sichtbare Satelliten'] ?? '–');
    } else {
        if (!isset($wx['window']) || !is_array($wx['window']) || count($wx['window']) === 0) {
            return "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
        }
        $c = $wx['window'][1] ?? $wx['window'][0];
        $temp = $c['temperature_2m'] ?? '–';
        $dew  = $c['dewpoint_2m'] ?? '–';
        $windSpeed = $c['windspeed_80m'] ?? $c['windspeed_10m'] ?? null;
        $wind = ($windSpeed === null || $windSpeed === '') ? '–' : ($windSpeed . " km/h");
        $dirRaw = $c['winddirection_80m'] ?? $c['winddirection_10m'] ?? null;
        $dir = ($dirRaw === null || $dirRaw === '') ? '–' : (intval(round($dirRaw)) . "° (" . rkrk_deg_to_compass($dirRaw) . ")");
        $code = $c['weathercode'] ?? '–';
        $rain = $c['precipitation_probability'] ?? '–';
        $cc   = $c['cloudcover'] ?? '–';
        $visRaw = $c['visibility'] ?? null;
        $vis = ($visRaw === null || $visRaw === '') ? '–' : (intval(round($visRaw / 1000)) . " km");
        $kp = $wx['kp']['value'] ?? null;
        $kpLabel = $wx['kp']['label'] ?? '';
        $kpText = ($kp === null || $kp === '') ? '–' : (intval(round($kp)) . " " . $kpLabel);
        $auroraText = ($kpLabel === '') ? '–' : $kpLabel;
        $satCount = rkrk_estimate_visible_satellites(
            $c['cloudcover'] ?? null,
            $wx['target_unix'] ?? $dt->getTimestamp(),
            $wx['sunrise'] ?? null,
            $wx['sunset'] ?? null
        );
        $satText = strval($satCount);
    }

    $critWind = rkrk_is_weather_value_critical('wind_80m_kmh', $wind);
    $critRain = rkrk_is_weather_value_critical('precipitation_probability', $rain);
    $cloudTempCtx = $isSavedPayload ? ($wx['Temperatur'] ?? null) : ($c['temperature_2m'] ?? null);
    $cloudDewCtx  = $isSavedPayload ? ($wx['Taupunkt'] ?? null) : ($c['dewpoint_2m'] ?? null);
    $critCloud = rkrk_is_cloudcover_critical($cc, $cloudTempCtx, $cloudDewCtx);
    $critVis = rkrk_is_weather_value_critical('visibility_km', $vis);
    $kpLevel = rkrk_kp_level($kpText);

    $codeNum = null;
    if (preg_match('/\d+/', (string)$code, $mCode)) {
        $codeNum = intval($mCode[0]);
    }
    $codeOut = safe((string)$code);
    if ($codeNum !== null) {
        $codeOut = rkrk_weathercode_icon($codeNum) . " " . safe(rkrk_weathercode_text($codeNum)) . " (" . safe((string)$code) . ")";
    }

    $windOut = rkrk_mark_weather_value($wind, $critWind);
    $rainOut = rkrk_mark_weather_value($rain . " %", $critRain);
    $ccOut = rkrk_mark_weather_value($cc . " %", $critCloud);
    $visOut = rkrk_mark_weather_value($vis, $critVis);
    $kpOut = rkrk_mark_weather_value($kpText, $kpLevel);
    $hasCritical = ($critWind || $critRain || $critCloud || $critVis || $kpLevel === 'bad');

    $html = "<div class='wxcard'>
        <b>Wetter für {$flugort}</b><br>
        <small>" . rkrk_format_display_datetime($dt) . "</small><br><br>

        Temperatur: {$temp} °C<br>
        Taupunkt: {$dew} °C<br>
        Wind (80m): {$windOut}<br>
        Windrichtung: {$dir}<br>
        Regenwahrscheinlichkeit: {$rainOut}<br>
        Bewölkung: {$ccOut}<br>
        Sichtweite: {$visOut}<br>
        KPI Aurora: {$kpOut}<br>
        Aurora: {$auroraText}<br>
        Sichtbare GNSS Satelliten: {$satText}<br>
        Wettercode: {$codeOut}<br>
    ";

    if ($hasCritical) {
        $html .= "<br><span style='color:#b91c1c;font-weight:700;'>⚠️ Kritische Wetterparameter erkannt</span>";
    }

    if ($note !== '') {
        $html .= "<br><i>{$note}</i>";
    }

    return $html . "</div>";
}


function rkrk_load_saved_weather($editId){
    if (!$editId) return null;

    $data = loadChecklist($editId);
    if (!$data) return null;

    return $data['fields']['Wetter'] ?? null;
}


function rkrk_save_weather_to_checklist($editId, $payload){
    if (!$editId) return false;
    global $JSON_FOLDER;

    $path = "$JSON_FOLDER/" . basename($editId) . ".json";
    if (!file_exists($path)) return false;

    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];

    if (!isset($data['fields']) || !is_array($data['fields'])) {
        $data['fields'] = [];
    }
    if (!isset($data['fields']['Wetter'])) {
        $data['fields']['Wetter'] = [];
    }

    // Wetterdaten aus Payload
    $wx  = $payload['wx']  ?? null;
    $geo = $payload['geo'] ?? null;

    // Stabil: wir nehmen IMMER den nächsten Stundenwert
    $c = null;
    if (isset($wx['window']) && is_array($wx['window']) && count($wx['window']) > 0) {
        // Nimm den mittleren Wert (0 = vorher, 1 = exakt, 2 = nachher)
        $mid = floor(count($wx['window']) / 2);
        $c = $wx['window'][$mid];
    }

    if (!$c) return false;

    $windSpeed = $c['windspeed_80m'] ?? $c['windspeed_10m'] ?? "";
    $windDirRaw = $c['winddirection_80m'] ?? $c['winddirection_10m'] ?? null;
    $windDirText = ($windDirRaw === null || $windDirRaw === '')
        ? ""
        : (intval(round($windDirRaw)) . "° (" . rkrk_deg_to_compass($windDirRaw) . ")");
    $visRaw = $c['visibility'] ?? null;
    $visText = ($visRaw === null || $visRaw === '') ? "" : (intval(round($visRaw / 1000)) . " km");
    $kp = $wx['kp']['value'] ?? null;
    $kpLabel = $wx['kp']['label'] ?? '';
    $kpText = ($kp === null || $kp === '') ? "" : (intval(round($kp)) . " " . $kpLabel);
    $satCount = rkrk_estimate_visible_satellites(
        $c['cloudcover'] ?? null,
        $wx['target_unix'] ?? time(),
        $wx['sunrise'] ?? null,
        $wx['sunset'] ?? null
    );
    $wxMeta = rkrk_weathercode_meta($c['weathercode'] ?? '');

    $data['fields']['Wetter'] = [
        "Ort"                   => $payload['flugort'] ?? "",
        "Zeit"                  => $payload['flugzeit'] ?? "",
        "Temperatur"            => $c['temperature_2m'] ?? "",
        "Taupunkt"              => $c['dewpoint_2m'] ?? "",
        "Regenwahrscheinlichkeit" => $c['precipitation_probability'] ?? "",
        "Wind (80m)"            => ($windSpeed === "" ? "" : ($windSpeed . " km/h")),
        "Windrichtung"          => $windDirText,
        "Bewölkung"             => $c['cloudcover'] ?? "",
        "Sichtweite"            => $visText,
        "KPI Aurora"            => $kpText,
        "KP Index"              => $kpText,
        "Aurora"                => $kpLabel,
        "Sichtbare GNSS Satelliten" => strval($satCount),
        "Sichtbare Satelliten"  => strval($satCount),
        "Wettercode"            => $c['weathercode'] ?? "",
        "Wettericon"            => $wxMeta['icon'],
        "Wetterbedeutung"       => $wxMeta['text'],
        "Koordinaten"           => ($geo['latitude'] ?? "") . ", " . ($geo['longitude'] ?? ""),
        "Gespeichert am"        => date('Y-m-d H:i:s')
    ];

    $data['modified_at'] = date('Y-m-d H:i:s');

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

function rkrk_save_weather_array_to_checklist($editId, $weather, $sourceNote=''){
    if (!$editId || !is_array($weather) || !rkrk_weather_has_meaningful_data($weather)) return false;
    global $JSON_FOLDER;
    $path = "$JSON_FOLDER/" . basename($editId) . ".json";
    if (!file_exists($path)) return false;
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];
    if (!isset($data['fields']) || !is_array($data['fields'])) $data['fields'] = [];
    $existing = (isset($data['fields']['Wetter']) && is_array($data['fields']['Wetter'])) ? $data['fields']['Wetter'] : [];
    $merged = $existing;
    foreach ($weather as $k => $v) {
        if (rkrk_weather_nonempty($v)) $merged[$k] = $v;
    }
    $wxMeta = rkrk_weathercode_meta($merged['Wettercode'] ?? '');
    if (($merged['Wettericon'] ?? '') === '' && $wxMeta['icon'] !== '') $merged['Wettericon'] = $wxMeta['icon'];
    if (($merged['Wetterbedeutung'] ?? '') === '' && $wxMeta['text'] !== '') $merged['Wetterbedeutung'] = $wxMeta['text'];
    if ($sourceNote !== '') $merged['Quelle'] = $sourceNote;
    $merged['Gespeichert am'] = date('Y-m-d H:i:s');
    $data['fields']['Wetter'] = $merged;
    $data['modified_at'] = date('Y-m-d H:i:s');
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

function rkrk_norm_place($s){
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = str_replace(["ä","ö","ü","ß"], ["ae","oe","ue","ss"], $s);
    $s = preg_replace('/[^a-z0-9]+/u', '', $s);
    return $s;
}

function rkrk_find_weather_fallback_from_recent_checklists($flugort, DateTime $targetDt, $excludeEditId=''){
    global $JSON_FOLDER;
    if (!is_dir($JSON_FOLDER)) return null;
    $targetPlace = rkrk_norm_place($flugort);
    $targetTs = $targetDt->getTimestamp();
    $candidates = [];
    foreach (glob($JSON_FOLDER . '/*.json') as $path) {
        $id = basename($path, '.json');
        if ($excludeEditId !== '' && $id === basename((string)$excludeEditId)) continue;
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') continue;
        $json = json_decode($raw, true);
        if (!is_array($json)) continue;
        $fields = $json['fields'] ?? null;
        if (!is_array($fields)) continue;
        $wx = (isset($fields['Wetter']) && is_array($fields['Wetter'])) ? $fields['Wetter'] : null;
        if (!rkrk_weather_has_meaningful_data($wx)) continue;
        $ctx = rkrk_extract_flight_context_from_fields($fields);
        $candDt = rkrk_parse_flight_dt((string)($ctx['flugzeit'] ?? ''));
        if (!$candDt) continue;
        $candPlace = rkrk_norm_place((string)($ctx['flugort'] ?? ($wx['Ort'] ?? '')));
        $placeMatch = true;
        if ($targetPlace !== '' && $candPlace !== '') {
            $placeMatch = ($candPlace === $targetPlace || mb_strpos($candPlace, $targetPlace) !== false || mb_strpos($targetPlace, $candPlace) !== false);
        }
        $dtDiff = abs($candDt->getTimestamp() - $targetTs);
        if ($dtDiff > 72 * 3600) continue;
        $candidates[] = ['wx' => $wx, 'dt' => $dtDiff, 'placeMatch' => $placeMatch];
    }
    if (count($candidates) === 0) return null;
    usort($candidates, function($a, $b){
        if (($a['placeMatch'] ? 1 : 0) !== ($b['placeMatch'] ? 1 : 0)) {
            return ($a['placeMatch'] ? -1 : 1);
        }
        return $a['dt'] <=> $b['dt'];
    });
    return $candidates[0]['wx'] ?? null;
}

function rkrk_extract_place_tokens($flugort){
    $raw = trim((string)$flugort);
    if ($raw === '') return [];
    $parts = preg_split('/[^[:alnum:]\x{00C0}-\x{017F}]+/u', $raw);
    if (!is_array($parts)) return [];
    $stop = [
        'bei','im','in','am','an','der','die','das','und','von','vom','zum','zur',
        'ort','flugort','feld','jg','region','gebiet'
    ];
    $out = [];
    foreach ($parts as $p){
        $t = trim((string)$p);
        if ($t === '') continue;
        if (mb_strlen($t, 'UTF-8') < 3) continue;
        if (in_array(mb_strtolower($t, 'UTF-8'), $stop, true)) continue;
        $out[] = $t;
    }
    return array_values(array_unique($out));
}

function rkrk_try_resolve_flugort_tokens($flugort){
    $tokens = rkrk_extract_place_tokens($flugort);
    if (count($tokens) === 0) return null;
    foreach ($tokens as $tok){
        $geo = rkrk_resolve_flugort($tok);
        if (is_array($geo) && isset($geo['latitude'], $geo['longitude'])) {
            $geo['name'] = trim((string)$flugort);
            $geo['source'] = 'token_fallback';
            return $geo;
        }
    }
    return null;
}

function rkrk_collect_geo_candidates_for_flight($flugort, $lat=null, $lon=null){
    $cands = [];
    $seen = [];
    $add = function($geo) use (&$cands, &$seen, $flugort){
        if (!is_array($geo) || !isset($geo['latitude'], $geo['longitude'])) return;
        $latN = round(floatval($geo['latitude']), 5);
        $lonN = round(floatval($geo['longitude']), 5);
        $k = $latN . ',' . $lonN;
        if (isset($seen[$k])) return;
        $seen[$k] = true;
        if (!isset($geo['timezone']) || trim((string)$geo['timezone']) === '') $geo['timezone'] = 'auto';
        if (!isset($geo['name']) || trim((string)$geo['name']) === '') $geo['name'] = trim((string)$flugort);
        $cands[] = $geo;
    };

    if (rkrk_valid_latlon($lat, $lon)) {
        $add([
            'name' => trim((string)$flugort),
            'latitude' => floatval($lat),
            'longitude' => floatval($lon),
            'timezone' => 'auto',
            'source' => 'latlon_direct'
        ]);
        return $cands;
    }

    $add(rkrk_resolve_flugort($flugort));
    $add(rkrk_geocode_open_meteo($flugort));

    foreach (rkrk_extract_place_tokens($flugort) as $tok){
        $gTok = rkrk_resolve_flugort($tok);
        if (is_array($gTok)) {
            $gTok['name'] = trim((string)$flugort);
            $gTok['source'] = 'token:' . $tok;
        }
        $add($gTok);
        $add(rkrk_geocode_open_meteo($tok));
    }
    return $cands;
}


function rkrk_resolve_flugort($flugort){
    $flugort = trim((string)$flugort);
    if ($flugort === '') return null;

    // -------------------------------------------------
    // 1) LAT/LON direkt im Text (z.B. "47.17, 8.52")
    // -------------------------------------------------
    if (preg_match(
        '/^\s*(-?\d{1,2}(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/',
        $flugort,
        $m
    )){
        $lat = floatval(str_replace(',', '.', $m[1]));
        $lon = floatval(str_replace(',', '.', $m[2]));
        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180){
            return [
                'name'      => $flugort,
                'latitude'  => $lat,
                'longitude' => $lon,
                'timezone'  => 'Europe/Zurich',
                'country'   => 'CH',
                'source'    => 'latlon_inline'
            ];
        }
    }

    // -------------------------------------------------
    // 2) Online‑Geocoding (kann fehlschlagen)
    // -------------------------------------------------
    $nameEnc = rawurlencode($flugort);
    $url = "https://geocoding-api.open-meteo.com/v1/search"
         . "?name={$nameEnc}&count=5&language=de&format=json";

    $j = rkrk_http_get_json($url);

    if (is_array($j) && !empty($j['results'])){
        foreach ($j['results'] as $r){
            if (!isset($r['latitude'], $r['longitude'])) continue;
            return [
                'name'      => $r['name'] ?? $flugort,
                'latitude'  => floatval($r['latitude']),
                'longitude' => floatval($r['longitude']),
                'timezone'  => $r['timezone'] ?? 'Europe/Zurich',
                'admin1'    => $r['admin1'] ?? null,
                'country'   => $r['country'] ?? 'CH',
                'source'    => 'geocoding_online'
            ];
        }
    }

    // -------------------------------------------------
    // 3) ✅ HARTE CH‑FALLBACKS (wenn HTTP blockiert ist)
    // -------------------------------------------------
    $chFallback = [
        'kappel' => [47.2292, 8.5259],
        'hausen' => [47.2458, 8.5313],
        'mettmenstetten' => [47.2441, 8.4639],
        'zug'    => [47.1724, 8.5174],
        'knonau' => [47.2190, 8.4560],
        'zürich' => [47.3769, 8.5417],
        'bern'   => [46.9480, 7.4474],
        'basel'  => [47.5596, 7.5886],
        'luzern' => [47.0502, 8.3093],
        'aarau'  => [47.3925, 8.0450],
        'chur'   => [46.8508, 9.5320]
    ];

    $key = strtolower($flugort);
    if (isset($chFallback[$key])){
        return [
            'name'      => $flugort,
            'latitude'  => $chFallback[$key][0],
            'longitude' => $chFallback[$key][1],
            'timezone'  => 'Europe/Zurich',
            'country'   => 'CH',
            'source'    => 'ch_fallback'
        ];
    }

    // -------------------------------------------------
    // 4) ❌ Nicht auflösbar
    // -------------------------------------------------
    return null;
}

function rkrk_get_weather_for_flight($flugort, $flugzeitRaw, $editId='', $lat=null, $lon=null){
    $dt = rkrk_parse_flight_dt($flugzeitRaw);
    if (!$dt){
        return [ 'html' => "<div class='system-info-box'>⚠️ Ungültige Flugzeit.</div>" ];
    }

    // Wenn älter als 6h → gespeichertes Wetter (Stand rkrk_checklist_20260428.php).
    // Kein Snapshot auf dem Server: Hinweistext — die Wetterbox im Browser lädt dann
    // per Open-Meteo direkt (siehe looksEmpty mit „Keine gespeicherten“).
    if (rkrk_is_older_than_6h($dt)){
        $saved = rkrk_load_saved_weather($editId);
        if (!$saved){
            return [ 'html' => "<div class='system-info-box'>ℹ️ Keine gespeicherten Wetterdaten vorhanden.</div>" ];
        }
        return [ 'html' => rkrk_render_weather_html($flugort, $dt, null, $saved, 'gespeichert') ];
    }

    $geoCandidates = rkrk_collect_geo_candidates_for_flight($flugort, $lat, $lon);
    if (count($geoCandidates) === 0){
        return [ 'html' => "<div class='system-info-box'>⚠️ Flugort nicht gefunden.</div>" ];
    }

    // Wetter je Kandidat sequenziell prüfen (z.B. "Aeugst, Knonau" -> erst Aeugst, dann Knonau)
    $geo = null;
    $wx = null;
    foreach ($geoCandidates as $g) {
        $tmp = rkrk_fetch_weather_open_meteo($g['latitude'], $g['longitude'], $dt, $g['timezone'] ?? 'auto');
        if ($tmp) {
            $geo = $g;
            $wx = $tmp;
            break;
        }
    }
    if (!$geo) {
        // Kein Wetter abrufbar: ersten Kandidaten für konsistente Anzeige/Koordinaten behalten.
        $geo = $geoCandidates[0];
    }

    // Speichern
    if ($wx && $editId){
        rkrk_save_weather_to_checklist($editId, [
            'flugort'  => $flugort,
            'flugzeit' => $flugzeitRaw,
            'geo'      => $geo,
            'wx'       => $wx
        ]);
    }

    return [ 'html' => rkrk_render_weather_html($flugort, $dt, $geo, $wx) ];
}


function handleWeatherMode() {
    $ort  = $_GET['flugort']  ?? '';
    $zeit = $_GET['flugzeit'] ?? '';
    $edit = $_GET['edit']     ?? '';
    $lat  = $_GET['lat']      ?? null;
    $lon  = $_GET['lon']      ?? null;

    if (!$ort || !$zeit) {
        echo "<div class='system-info-box'>⚠️ Ungültige Wetteranfrage.</div>";
        return;
    }

    $wx = rkrk_get_weather_for_flight($ort, $zeit, $edit, $lat, $lon);

    echo $wx['html'] ?? "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
}

function handleSaveWeatherClientMode() {
    global $JSON_FOLDER;
    $edit = basename($_POST['edit'] ?? '');
    if ($edit === '') {
        echo "ERR";
        return;
    }
    $path = "$JSON_FOLDER/$edit.json";
    if (!file_exists($path)) {
        echo "ERR";
        return;
    }

    $payload = json_decode($_POST['weather'] ?? '', true);
    if (!is_array($payload)) {
        echo "ERR";
        return;
    }
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];
    if (!isset($data['fields']) || !is_array($data['fields'])) $data['fields'] = [];

    $existingWx = (isset($data['fields']['Wetter']) && is_array($data['fields']['Wetter'])) ? $data['fields']['Wetter'] : [];
    $candidate = [
        "Ort" => (string)($payload['Ort'] ?? ''),
        "Zeit" => (string)($payload['Zeit'] ?? ''),
        "Temperatur" => (string)($payload['Temperatur'] ?? ''),
        "Taupunkt" => (string)($payload['Taupunkt'] ?? ''),
        "Luftfeuchte" => (string)($payload['Luftfeuchte'] ?? ''),
        "Regenwahrscheinlichkeit" => (string)($payload['Regenwahrscheinlichkeit'] ?? ''),
        "Wind (80m)" => (string)($payload['Wind (80m)'] ?? ''),
        "Windrichtung" => (string)($payload['Windrichtung'] ?? ''),
        "Windböen" => (string)($payload['Windböen'] ?? ''),
        "Bewölkung" => (string)($payload['Bewölkung'] ?? ''),
        "Sichtweite" => (string)($payload['Sichtweite'] ?? ''),
        "KPI Aurora" => (string)($payload['KPI Aurora'] ?? ''),
        "KP Index" => (string)($payload['KP Index'] ?? ''),
        "Aurora" => (string)($payload['Aurora'] ?? ''),
        "Sichtbare GNSS Satelliten" => (string)($payload['Sichtbare GNSS Satelliten'] ?? ''),
        "Sichtbare Satelliten" => (string)($payload['Sichtbare Satelliten'] ?? ''),
        "Wettercode" => (string)($payload['Wettercode'] ?? ''),
        "Wettericon" => (string)($payload['Wettericon'] ?? ''),
        "Wetterbedeutung" => (string)($payload['Wetterbedeutung'] ?? ''),
        "Koordinaten" => (string)($payload['Koordinaten'] ?? '')
    ];
    if (!rkrk_weather_has_meaningful_data($candidate)) {
        echo "SKIP";
        return;
    }
    $mergedWx = $existingWx;
    foreach ($candidate as $k => $v) {
        if (rkrk_weather_nonempty($v)) $mergedWx[$k] = $v;
    }
    $wxMeta = rkrk_weathercode_meta($mergedWx['Wettercode'] ?? '');
    if (($mergedWx['Wettericon'] ?? '') === '' && $wxMeta['icon'] !== '') $mergedWx['Wettericon'] = $wxMeta['icon'];
    if (($mergedWx['Wetterbedeutung'] ?? '') === '' && $wxMeta['text'] !== '') $mergedWx['Wetterbedeutung'] = $wxMeta['text'];
    $mergedWx["Gespeichert am"] = date('Y-m-d H:i:s');
    $data['fields']['Wetter'] = $mergedWx;
    $data['modified_at'] = date('Y-m-d H:i:s');
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "OK";
}


function showChecklistForm(){
    global $TEMPLATE_FOLDER, $JSON_FOLDER, $InputUserInfo, $rkrkChecklistBlankNew, $rkrkChecklistIsNew, $RKRKMissionSelected;

    rkrk_checklist_sync_mission_context();
    $checklistMdb = trim((string)($_GET['MDB'] ?? $RKRKMissionSelected ?? ''));
    $isGuest=false;

    $file     = basename($_GET['file'] ?? "");
    $edit     = isset($_GET['edit']) ? basename($_GET['edit']) : null;
    $readonly = isset($_GET['readonly']);
    $isBlankNew = !$edit && isset($_GET['blank']);
    $rkrkChecklistBlankNew = $isBlankNew;
    $rkrkChecklistIsNew = !$edit;
    $presetFeldFromLink = $isBlankNew ? "" : (string)(getPresetForLabel("Feld#") ?? "");
    $presetZeitZumFeldFromLink = $isBlankNew ? "" : (string)(getPresetForLabel("Zeit zum Feld") ?? "");

    // Wenn aus Flugplanung ohne edit geöffnet und bereits passende Checkliste existiert:
    // bestehende Checkliste wieder öffnen statt neue zu erstellen.
    if (!$edit && !$isBlankNew) {
        $presetDatumZeit = (string)(getPresetForLabel("Datum & Zeit") ?? "");
        $presetPilot = (string)(getPresetForLabel("Pilot") ?? "");
        $presetFeld = (string)(getPresetForLabel("Feld#") ?? "");
        $existingId = findExistingChecklistId($file, $presetDatumZeit, $presetPilot, $presetFeld);
        if ($existingId !== "") {
            redirectTo("?mode=form&file=" . rawurlencode($file) . "&edit=" . rawurlencode($existingId));
        }
    }
    if ($edit) {
        $rkrkChecklistIsNew = !is_file("$JSON_FOLDER/$edit.json");
    } else {
        $rkrkChecklistIsNew = true;
    }

    $tpl = loadTemplate($file);
    $title    = $tpl["title"] ?? "Checkliste";
    $sections = $tpl["sections"] ?? [];

    $saved = [];
    $extraFields = [];
    $createdAt = null;
    $modifiedAt = null;
    $editIdForWeather = "";
    if ($edit) {
        $editIdForWeather = (string)$edit;
    }

    // --- NEU: Flugzeit/Flugort für Auto-Wetterpanel sammeln
    $flugzeit = "";
    $flugort  = "";

    if ($edit){
        $json = loadChecklist($edit);
        if ($json){
            $savedRaw   = $json["fields"] ?? [];
            rkrk_migrate_suchresultat_legacy_labels($savedRaw, $presetFeldFromLink, $presetZeitZumFeldFromLink);
            $createdAt  = $json["created_at"]  ?? null;
            $modifiedAt = $json["modified_at"] ?? null;

            $saved = mergeTemplateWithSaved($sections, $savedRaw);
            $editIdForWeather = (string)$edit;

            foreach ($savedRaw as $secName => $fields) {
                foreach ($fields as $label => $value) {
                    if (!isset($sections[$secName][$label])) {
                        $extraFields[$secName][$label] = $value;
                    }
                }
            }
        }
    }

	// Flugzeit/Flugort vorab ermitteln (für Wetteranzeige direkt unter dem Titel)
	// Grundlage ist der Feldtyp im Template: "flugzeit" / "flugort"
	$hasFlightTimeField = false;
	$hasFlightPlaceField = false;
	foreach ($sections as $secName => $fieldsMeta){
	  foreach ($fieldsMeta as $label => $meta){

		$t = is_array($meta) ? ($meta['type'] ?? 'text') : (string)$meta;

		if ($t === 'flugzeit') {
		  $hasFlightTimeField = true;
		}
		if ($t === 'flugort') {
		  $hasFlightPlaceField = true;
		}

		if ($t !== 'flugzeit' && $t !== 'flugort') {
		  continue;
		}

		$v = $saved[$secName][$label] ?? ($isBlankNew ? '' : ($_GET[$label] ?? ''));
		if (trim((string)$v) === '') continue;

		if ($t === 'flugzeit') $flugzeit = (string)$v;
		if ($t === 'flugort') $flugort = (string)$v;
	  }
	}

	$showStartWeather = ($hasFlightTimeField && $hasFlightPlaceField);

    $fieldTimeMapRaw = '';
    if (isset($saved['Suchresultat']) && is_array($saved['Suchresultat'])) {
        $fieldTimeMapRaw = trim((string)($saved['Suchresultat']['Zeit zum Feld'] ?? ''));
    }
    if ($fieldTimeMapRaw === '' && !$isBlankNew) {
        $tmp = getPresetForLabel('Zeit zum Feld');
        if ($tmp === null) $tmp = getPresetForLabel('Feld-Zeit');
        if ($tmp === null && isset($_GET['FieldTimeMap'])) $tmp = $_GET['FieldTimeMap'];
        if ($tmp !== null) $fieldTimeMapRaw = trim((string)$tmp);
    }
    if ($fieldTimeMapRaw === '' && trim($presetZeitZumFeldFromLink) !== '') {
        $fieldTimeMapRaw = trim($presetZeitZumFeldFromLink);
    }

    $flightsCsvYear = trim((string)($_GET['FlightsCsvYear'] ?? ''));
    if (!preg_match('/^\d{4}$/', $flightsCsvYear)) {
        $dtForYear = '';
        if (isset($saved['Allgemein']['Datum & Zeit'])) {
            $dtForYear = trim((string)$saved['Allgemein']['Datum & Zeit']);
        }
        if ($dtForYear === '' && !$isBlankNew) {
            $dtForYear = trim((string)(getPresetForLabel('Datum & Zeit') ?? ''));
        }
        if ($dtForYear !== '' && function_exists('rkrk_sync_day_ymd')) {
            $ymd = rkrk_sync_day_ymd($dtForYear);
            if ($ymd !== '') {
                $flightsCsvYear = substr($ymd, 0, 4);
            }
        }
    }

    // Tagesflugplan (nur wenn Feld# in der Checkliste vorhanden ist)
    $hasFeldHashField = false;
    $feldHashValue = "";
    $pilotValueForPlan = "";
    $datumZeitForPlan = "";
    foreach ($sections as $secName => $fieldsMeta) {
        foreach ($fieldsMeta as $label => $meta) {
            $labelNorm = normalizeKey((string)$label);
            $v = $saved[$secName][$label] ?? ($isBlankNew ? '' : ($_GET[$label] ?? ''));
            if (is_array($v)) $v = implode(', ', array_map('strval', $v));
            if ($labelNorm === 'feld') {
                $hasFeldHashField = true;
                if (trim((string)$v) !== '') $feldHashValue = (string)$v;
            } elseif ($labelNorm === 'pilot') {
                if (trim((string)$v) !== '') $pilotValueForPlan = (string)$v;
            } elseif ($labelNorm === 'datum_zeit') {
                if (trim((string)$v) !== '') $datumZeitForPlan = (string)$v;
            }
        }
    }
    $showTagesflugplan = $hasFeldHashField;
    $tagesflugplanUrl = "";
    if ($hasFeldHashField) {
        $tokens = preg_split('/[;,\n\r\t ]+/', (string)$feldHashValue);
        $fieldIds = array();
        foreach ((array)$tokens as $tok) {
            $tok = trim((string)$tok);
            if ($tok === '') continue;
            $fidPlan = rkrk_checklist_extract_feld_id($tok);
            if ($fidPlan !== '') {
                $fieldIds[] = $fidPlan;
            }
        }
        $fieldIds = array_values(array_unique($fieldIds));
        if (count($fieldIds) > 0) {
            $ffilter = implode('+', $fieldIds);
            $missionParam = "";
            if (isset($_GET['MDB']) && trim((string)$_GET['MDB']) !== '') {
                $missionParam = "&MDB=" . rawurlencode(trim((string)$_GET['MDB']));
            } elseif (trim((string)($checklistMdb ?? '')) !== '') {
                $missionParam = "&MDB=" . rawurlencode(trim((string)$checklistMdb));
            }
            $tagesflugplanUrl = "rkrk_map.php?FFilter=" . rawurlencode($ffilter) . "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off&WW=on&Label=off&EX=NO&KA=S&MAP=&Zoom=1.0&MenuOn=off&MapButtons=off&Embed=1" . $missionParam;
        }
    }
    $tagesflugplanMapSuffix = "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off&WW=on&Label=off&EX=NO&KA=S&MAP=&Zoom=1.0&MenuOn=off&MapButtons=off&Embed=1";
    if (isset($missionParam) && trim((string)$missionParam) !== '') {
        $tagesflugplanMapSuffix .= $missionParam;
    } elseif (trim((string)($checklistMdb ?? '')) !== '') {
        $tagesflugplanMapSuffix .= "&MDB=" . rawurlencode(trim((string)$checklistMdb));
    }

    $tagesflugplanBoxHtml = "";
    if ($showTagesflugplan) {
        ob_start();
        echo "<style>
        .planbox{margin:8px 0 12px 0;padding:8px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;}
        .planbox-toggle{display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer;font-weight:600;color:#1f2937;margin-bottom:8px;}
        .planbox-toggle .chev{font-size:.9em;color:#4b5563;}
        .planbox-body{display:none;}
        .planbox-body.open{display:block;}
        .planbox-frame{width:100%;height:540px;border:1px solid #d1d5db;border-radius:8px;background:#fff;}
        .planbox-link{margin-top:6px;font-size:.9em;}
        @media (max-width:900px){.planbox-frame{height:420px;}}
        </style>";
        $urlEsc = safe($tagesflugplanUrl);
        echo "<div class='planbox' id='rkrkTagesflugplanBox' data-map-suffix='" . safe($tagesflugplanMapSuffix) . "'>
            <div class='planbox-toggle' id='toggleFlugplanBox'>
                <span>Flugplan anzeigen</span><span class='chev' id='toggleFlugplanChevron'>&#9662;</span>
            </div>
            <div class='planbox-body' id='flugplanBoxBody'>";
        if ($tagesflugplanUrl !== "") {
            echo "<iframe class='planbox-frame' src='$urlEsc' loading='lazy' referrerpolicy='no-referrer-when-downgrade'></iframe>";
            echo "<div class='planbox-link'><a href='$urlEsc' target='_blank' rel='noopener'>Flugplan in rkrk_map öffnen</a></div>";
        } else {
            echo "<div class='system-info-box' style='margin:0;'>Bitte zuerst im Feld <b>Feld#</b> mindestens eine gültige Feldnummer (z.B. 25-049 oder ZG4-056) eintragen.</div>";
        }
        echo "</div>
        </div>
        <script>
        (function(){
            var t=document.getElementById('toggleFlugplanBox');
            var b=document.getElementById('flugplanBoxBody');
            var c=document.getElementById('toggleFlugplanChevron');
            if(!t||!b||!c) return;
            t.addEventListener('click', function(){
                var open = b.classList.toggle('open');
                c.innerHTML = open ? '&#9652;' : '&#9662;';
            });
        })();
        </script>";
        $tagesflugplanBoxHtml = (string)ob_get_clean();
    }

    $checklistDay = function_exists('rkrk_sync_day_ymd') ? rkrk_sync_day_ymd($datumZeitForPlan) : '';
    if ($checklistDay === '' && !$isBlankNew) {
        $checklistDay = function_exists('rkrk_sync_day_ymd')
            ? rkrk_sync_day_ymd((string)(getPresetForLabel('Datum & Zeit') ?? ''))
            : '';
    }
    $checklistPilot = trim($pilotValueForPlan);
    if ($checklistPilot === '' && !$isBlankNew) {
        $checklistPilot = trim((string)(getPresetForLabel('Pilot') ?? ''));
    }
    $flightsCsvPerField = array();
    if ($checklistDay !== '' && preg_match('/^\d{4}$/', $flightsCsvYear)) {
        $flightsCsvPerField = rkrk_checklist_flights_csv_load_for_fields($flightsCsvYear, $checklistDay, $checklistPilot);
    }
    $fundeFromFlightsCsv = function_exists('rkrk_checklist_funde_text_from_csv')
        ? rkrk_checklist_funde_text_from_csv($flightsCsvPerField)
        : '';

    // CSV/XML-Sync deaktiviert (nur JSON-Checkliste verwenden).

    pageHeaderLocal($title);

	if (isset($_GET["err"]) && (string)$_GET["err"] === "importjson") {
		echo "<div class='rkrk-card' style='border-color:#fca5a5;background:#fff5f5;margin-bottom:12px;'><strong>Import</strong>: Ungültige JSON-Datei — es wird ein Objekt mit Schlüssel <code>fields</code> erwartet.</div>";
	}
	if (isset($_GET["imported"])) {
		echo "<div class='rkrk-card success' style='margin-bottom:12px;'>✓ Import: Felder aus der JSON-Datei wurden übernommen.</div>";
	}

    // Wetter direkt unter dem Titel
	// -------------------- WETTERBOX (nur wenn Flugzeit + Flugort im Template vorhanden) --------------------
	if ($showStartWeather) {
		$flugzeitEsc = safe(formatCHDatetime($flugzeit));
		$flugortEsc  = safe($flugort);
    	$editJson = json_encode($editIdForWeather, JSON_UNESCAPED_UNICODE);

		echo <<<HTML
		<style>
		/* Wetterbox – kompakt, keine Färbung/Highlight */
		.wxbox{margin:12px 0 18px 0; padding:12px; border:1px solid #e5e7eb; border-radius:12px; background:#fff;}
		.wxbox h4{margin:0;}
		.wxtitlebar{display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 10px 0;}
		.wxmeta{margin:0; color:#374151; font-size:0.95em; text-align:right;}
		@media (max-width: 900px){
		  .wxtitlebar{flex-direction:column; align-items:flex-start;}
		  .wxmeta{text-align:left;}
		}
		.wxnote{margin-top:10px; color:#6b7280; font-size:0.9em;}

		.wxcard{border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; background:#fafafa;}
		.wxtop{display:flex; align-items:center; justify-content:space-between; gap:10px; font-weight:700;}
		.wxdesc{color:#374151; font-size:0.95em; margin-top:4px;}
		.wxsep{height:1px; background:#e5e7eb; margin:10px 0;}

		/* Werte: 1/2/3 Spalten */
		.wxkvgrid{display:grid; gap:8px 14px; grid-template-columns:1fr; margin-top:10px;}
		@media (min-width: 720px){ .wxkvgrid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
		@media (min-width: 1100px){ .wxkvgrid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
		.wxkv{display:flex; gap:8px; align-items:baseline; min-width:0;}
		.wxk{color:#6b7280; font-size:0.88em; min-width:0;}
		.wxv{color:#111827; font-weight:600; font-size:0.95em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
		.wxv-warn{color:#9a5600; font-weight:700;}
		.wxv-critical{color:#b91c1c; font-weight:700;}
		</style>

		<div class="wxbox" id="flightWxBox">
		  <div class="wxtitlebar">
			<h4>Wetter beim Start</h4>
			<div class="wxmeta" id="flightWxMeta">
			  <b>Flugort:</b> <span id="flightWxPlace">{$flugortEsc}</span>
			  &nbsp; <b>Flugzeit:</b> <span id="flightWxTime">{$flugzeitEsc}</span>
			</div>
		  </div>
		  <div id="flightWxContent" class="wxnote">ℹ️ Wetter wird geladen sobald Flugort und Flugzeit gesetzt sind…</div>
		</div>

		<script>
		(function(){
		  const \$ = (sel) => document.querySelector(sel);
		  const weatherHidden = () => document.querySelector("input[name='weather_json']");
		  function setWeatherSnapshot(snapshot){
			const hidden = weatherHidden();
			if (!hidden) return;
			hidden.value = JSON.stringify({ snapshot: snapshot || {} });
			if (typeof window.rkrkChecklistScheduleSave === 'function') window.rkrkChecklistScheduleSave();
		  }
		  function clearWeatherSnapshot(){
			const hidden = weatherHidden();
			if (!hidden) return;
			hidden.value = '';
		  }

		  function findFlightInputs(){
			const timeInput =
			  document.querySelector("input[data-field-type='flugzeit']") ||
			  document.querySelector("[data-field-type='flugzeit']");
			const placeInput =
			  document.querySelector("input[data-field-type='flugort']") ||
			  document.querySelector("[data-field-type='flugort']");
			return { timeInput, placeInput };
		  }

		  function esc(s){
			return String(s ?? '').replace(/[&<>"']/g, m => ({
			  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
			}[m]));
		  }

		  function fmtDisplayDateTime(raw){
			const s = String(raw || '').trim();
			if (!s) return '';
			const d = parseDateFlexible(s);
			if (!isFinite(d.getTime())) return s;
			const mmm = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
			const dd = String(d.getDate()).padStart(2,'0');
			const mm = mmm[d.getMonth()] || String(d.getMonth()+1).padStart(2,'0');
			const yyyy = String(d.getFullYear());
			const hh = String(d.getHours()).padStart(2,'0');
			const mi = String(d.getMinutes()).padStart(2,'0');
			return dd + '.' + mm + '.' + yyyy + ' ' + hh + ':' + mi;
		  }

		  function parseDateFlexible(raw){
			const s = String(raw || '').trim();
			if (!s) return new Date(NaN);
			let d = new Date(s);
			if (isFinite(d.getTime())) return d;
			const m = s.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:[ T]([0-2]?\d):([0-5]\d))?$/);
			if (!m) return new Date(NaN);
			const dd = Number(m[1]), mm = Number(m[2]), yyyy = Number(m[3]);
			const hh = Number(m[4] || 0), mi = Number(m[5] || 0);
			d = new Date(yyyy, mm - 1, dd, hh, mi, 0, 0);
			return isFinite(d.getTime()) ? d : new Date(NaN);
		  }

		  function toIsoLocalMinute(raw){
			const d = parseDateFlexible(raw);
			if (!isFinite(d.getTime())) return String(raw || '').trim();
			const yyyy = String(d.getFullYear());
			const mm = String(d.getMonth() + 1).padStart(2, '0');
			const dd = String(d.getDate()).padStart(2, '0');
			const hh = String(d.getHours()).padStart(2, '0');
			const mi = String(d.getMinutes()).padStart(2, '0');
			return yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + mi;
		  }

		  function wxIcon(code){
			const c = parseInt(code,10);
			if (Number.isNaN(c)) return '❓';
			if (c===0) return '☀️';
			if (c>=1 && c<=3) return '🌤️';
			if (c===45 || c===48) return '🌫️';
			if ([51,53,55,56,57].includes(c)) return '🌦️';
			if ([61,63,65,66,67,80,81,82].includes(c)) return '🌧️';
			if ([71,73,75,77,85,86].includes(c)) return '🌨️';
			if ([95,96,99].includes(c)) return '⛈️';
			return '🌡️';
		  }

		  function wxText(code){
			const c = parseInt(code,10);
			if (Number.isNaN(c)) return 'unbekannt';
			if (c===0) return 'klar';
			if (c===1) return 'überwiegend klar';
			if (c===2) return 'teilweise bewölkt';
			if (c===3) return 'bedeckt';
			if (c===45) return 'Nebel';
			if (c===48) return 'Reif-Nebel';
			if ([51,53,55].includes(c)) return 'Niesel';
			if ([56,57].includes(c)) return 'gefrierender Niesel';
			if ([61,63,65].includes(c)) return 'Regen';
			if ([66,67].includes(c)) return 'gefrierender Regen';
			if ([71,73,75].includes(c)) return 'Schnee';
			if (c===77) return 'Schneekörner';
			if ([80,81,82].includes(c)) return 'Regenschauer';
			if ([85,86].includes(c)) return 'Schneeschauer';
			if (c===95) return 'Gewitter';
			if ([96,99].includes(c)) return 'Gewitter mit Hagel';
			return 'Wettercode ' + c;
		  }

		  function toCompass(deg){
			const d = Number(deg);
			if (!isFinite(d)) return '';
			const dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
			return dirs[Math.round(((d%360)/22.5))%16];
		  }

		  function nearestIndex(times, targetTs){
			let bestI = 0, bestD = null;
			for (let i=0;i<times.length;i++){
			  const d = Math.abs((times[i]||0) - targetTs);
			  if (bestD===null || d<bestD){ bestD=d; bestI=i; }
			}
			return bestI;
		  }
		  function buildWxSnapshot(place, dtLocal, geo, h, i, kpObj){
			const sat = estimateGnssVisibleCount();
			const wd80 = (h.winddirection_80m && h.winddirection_80m[i] != null) ? Number(h.winddirection_80m[i]) : null;
			const ws80 = (h.windspeed_80m && h.windspeed_80m[i] != null) ? Number(h.windspeed_80m[i]) : null;
			const gust = (h.windgusts_10m && h.windgusts_10m[i] != null) ? Number(h.windgusts_10m[i]) : null;
			const wxc = (h.weathercode && h.weathercode[i] != null) ? Number(h.weathercode[i]) : '';
			const timeUnix = (h.time && h.time[i] != null) ? Number(h.time[i]) : null;
			const kpDisp = kpObj && isFinite(Number(kpObj.kp))
			  ? (String(Math.round(kpObj.kp)) + ' ' + String(kpObj.label||'').trim())
			  : '';
			const snap = {
			  ort: String(place||'').trim(),
			  flight_time_local: String(dtLocal||'').trim(),
			  time: (timeUnix !== null && isFinite(timeUnix)) ? new Date(timeUnix*1000).toISOString() : dtLocal,
			  temp_c: h.temperature_2m ? h.temperature_2m[i] : '',
			  dewpoint_c: h.dewpoint_2m ? h.dewpoint_2m[i] : '',
			  rh_pct: h.relative_humidity_2m ? h.relative_humidity_2m[i] : '',
			  precip_prob_pct: h.precipitation_probability ? h.precipitation_probability[i] : '',
			  wind_80m_kmh: Number.isFinite(ws80) ? ws80 : '',
			  wind_dir_deg: Number.isFinite(wd80) ? wd80 : '',
			  wind_gusts_10m_kmh: Number.isFinite(gust) ? gust : '',
			  cloud_cover_pct: h.cloudcover ? h.cloudcover[i] : '',
			  visibility_m: h.visibility ? h.visibility[i] : '',
			  weathercode: Number.isFinite(wxc) ? wxc : '',
			  kp_display: kpDisp.trim(),
			  kp_label: kpObj ? String(kpObj.label||'').trim() : '',
			  gnss_sat_count: sat,
			  lat: (geo&&geo.lat!=null) ? geo.lat : '',
			  lon: (geo&&geo.lon!=null) ? geo.lon : ''
			};
			return snap;
		  }

		  async function persistWeatherViaOpenMeteo(place, dtLocal, geo, editId){
			if (!geo||geo.lat==null||geo.lon==null) return;
			const d = parseDateFlexible(dtLocal);
			if (!isFinite(d.getTime())) return;
			const dateStr = toIsoLocalMinute(dtLocal).slice(0,10);
			const targetTs = Math.floor(d.getTime()/1000);
			if (!dateStr || !isFinite(targetTs)) return;
			let day = null;
			try { day = await fetchDay(geo.lat, geo.lon, dateStr, geo.timezone); } catch(e) { day = null; }
			const hFull = day && day.hourly ? day.hourly : null;
			if (!hFull || !hFull.time || !hFull.time.length) return;
			let kpObj = null;
			try { kpObj = await fetchKp(targetTs); } catch(e) { kpObj = null; }
			await applyWxSnapshotClientSave(place, dtLocal, geo, editId, hFull, targetTs, kpObj);
		  }

		  async function applyWxSnapshotClientSave(place, dtLocal, geo, editId, h, targetTs, kpObj){
			if (!h || !h.time || !h.time.length) return;
			const i = nearestIndex(h.time, targetTs);
			const snap = buildWxSnapshot(place, dtLocal, geo, h, i, kpObj);
			setWeatherSnapshot(snap);
			const eid = String(editId||'').trim();
			if (!eid) return;
			try{
			  const wd = h.winddirection_80m&&h.winddirection_80m[i]!=null ? Number(h.winddirection_80m[i]) : null;
			  const ws = h.windspeed_80m&&h.windspeed_80m[i]!=null ? Number(h.windspeed_80m[i]) : null;
			  const gustV = h.windgusts_10m&&h.windgusts_10m[i]!=null ? Number(h.windgusts_10m[i]) : null;
			  const rhV = h.relative_humidity_2m&&h.relative_humidity_2m[i]!=null ? Number(h.relative_humidity_2m[i]) : null;
			  const sat = estimateGnssVisibleCount();
			  const kpText = kpObj && isFinite(Number(kpObj.kp)) ? (String(Math.round(kpObj.kp)) + ' ' + String(kpObj.label||'').trim()) : '';
			  const payload = {
				'Ort': String(place||'').trim(),
				'Zeit': String(dtLocal||'').trim(),
				'Temperatur': h.temperature_2m ? String(h.temperature_2m[i] ?? '') : '',
				'Taupunkt': h.dewpoint_2m ? String(h.dewpoint_2m[i] ?? '') : '',
				'Luftfeuchte': Number.isFinite(rhV) ? (String(Math.round(rhV)) + ' %') : '',
				'Regenwahrscheinlichkeit': h.precipitation_probability ? String(h.precipitation_probability[i] ?? '') : '',
				'Wind (80m)': Number.isFinite(ws) ? (ws + ' km/h') : '',
				'Windrichtung': Number.isFinite(wd) ? (Math.round(wd) + '° (' + toCompass(wd) + ')') : '',
				'Windböen': Number.isFinite(gustV) ? (fmtNum(gustV, 1) + ' km/h') : '',
				'Bewölkung': h.cloudcover ? String(h.cloudcover[i] ?? '') : '',
				'Sichtweite': h.visibility ? (Math.round(Number(h.visibility[i] || 0)/1000) + ' km') : '',
				'KPI Aurora': kpText.trim(),
				'KP Index': kpText.trim(),
				'Aurora': kpObj ? String(kpObj.label||'') : '',
				'Sichtbare GNSS Satelliten': String(sat),
				'Sichtbare Satelliten': String(sat),
				'Wettercode': h.weathercode ? String(h.weathercode[i] ?? '') : '',
				'Koordinaten': (geo.lat + ', ' + geo.lon)
			  };
			  const body = new URLSearchParams();
			  body.set('edit', eid);
			  body.set('weather', JSON.stringify(payload));
			  await fetch('?mode=saveweatherclient', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
				body: body.toString()
			  });
			}catch(err){ console.warn(err); }
		  }

		  function parseLatLonInline(s){
			const m = String(s||'').trim().match(/^\\s*(-?\\d{1,2}(?:[\\.,]\\d+)?)\\s*[,;\\s]\\s*(-?\\d{1,3}(?:[\\.,]\\d+)?)\\s*$/);
			if (!m) return null;
			const lat = parseFloat(m[1].replace(',','.'));
			const lon = parseFloat(m[2].replace(',','.'));
			if (!isFinite(lat) || !isFinite(lon)) return null;
			if (lat<-90 || lat>90 || lon<-180 || lon>180) return null;
			return { lat, lon, timezone:'auto', name:String(lat)+','+String(lon) };
		  }

		  function placeCandidates(raw){
			const src = String(raw || '').trim();
			if (!src) return [];
			const out = [];
			const seen = new Set();
			const add = (v) => {
			  const t = String(v || '').trim();
			  if (!t) return;
			  const key = t.toLowerCase();
			  if (seen.has(key)) return;
			  seen.add(key);
			  out.push(t);
			};
			add(src);
			src.split(/[;,/|]+/).forEach(add);
			src.split(/[\s\-]+/).forEach((t) => { if (String(t).trim().length >= 3) add(t); });
			return out;
		  }

		  async function geocode(name){
			const tries = placeCandidates(name);
			for (const q of tries){
			  const ll = parseLatLonInline(q);
			  if (ll) return ll;
			  const url = 'https://geocoding-api.open-meteo.com/v1/search?name='
				+ encodeURIComponent(q) + '&count=1&language=de&format=json';
			  const r = await fetch(url);
			  if (!r.ok) continue;
			  const j = await r.json();
			  const res = j && j.results ? j.results[0] : null;
			  if (!res) continue;
			  return {
				name: res.name ?? q,
				lat: res.latitude,
				lon: res.longitude,
				timezone: res.timezone ?? 'auto',
				admin1: res.admin1 ?? null,
				country: res.country ?? null
			  };
			}
			return null;
		  }

		  async function fetchDay(lat, lon, dateStr, timezone){
			const hourlyVars = [
			  'temperature_2m',
			  'relative_humidity_2m',
			  'dewpoint_2m',
			  'cloudcover',
			  'visibility',
			  'precipitation_probability',
			  'windspeed_80m',
			  'winddirection_80m',
			  'windgusts_10m',
			  'weathercode'
			].join(',');

			const dailyVars = ['sunrise','sunset'].join(',');

			const url = 'https://api.open-meteo.com/v1/forecast'
			  + '?latitude=' + encodeURIComponent(lat)
			  + '&longitude=' + encodeURIComponent(lon)
			  + '&hourly=' + encodeURIComponent(hourlyVars)
			  + '&daily=' + encodeURIComponent(dailyVars)
			  + '&start_date=' + encodeURIComponent(dateStr)
			  + '&end_date=' + encodeURIComponent(dateStr)
			  + '&timezone=' + encodeURIComponent(timezone || 'auto')
			  + '&timeformat=unixtime';

			const r = await fetch(url);
			if (!r.ok) return null;
			return await r.json();
		  }

		  // KP (optional) – aus NOAA 3-Day Forecast passend zur Flugzeit
		  async function fetchKp(targetTs){
			try{
			  const labelFor = (kpVal) =>
				kpVal <= 2 ? 'ruhig / leicht aktiv' :
				kpVal <= 3 ? 'leicht aktiv' :
				kpVal <= 4 ? 'aktiv' :
				kpVal <= 5 ? 'stürmisch' :
				'stark stürmisch';

			  try {
				const r1 = await fetch('https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json', { cache:'no-store' });
				if (r1.ok) {
				  const j1 = await r1.json();
				  if (Array.isArray(j1) && j1.length){
					const targetMs = Number.isFinite(Number(targetTs)) ? Number(targetTs) * 1000 : Date.now();
					const entries = [];
					for (let i=0; i<j1.length; i++){
					  const row = j1[i];
					  if (!row) continue;
					  const ts = String((row.time_tag ?? row[0] ?? '')).trim();
					  const kpVal = parseFloat(row.Kp ?? row.kp ?? row.kp_index ?? row[1]);
					  if (!ts || !isFinite(kpVal) || kpVal < 0 || kpVal > 9) continue;
					  const ms = Date.parse(/Z$/.test(ts) ? ts : (ts + 'Z'));
					  if (!isFinite(ms)) continue;
					  entries.push({ ms, ts, kp: kpVal });
					}
					if (entries.length){
					  entries.sort((a,b)=>a.ms-b.ms);
					  const next = entries.find(e => e.ms >= targetMs);
					  const pick = next || entries[entries.length - 1];
					  return { kp: pick.kp, label: labelFor(pick.kp), time_utc: pick.ts };
					}
				  }
				}
			  } catch(e) {}

			  return null;
			}catch(e){
			  return null;
			}
		  }

		  function fmtNum(v, digits){
			const n = Number(v);
			if (!isFinite(n)) return '–';
			return n.toFixed(digits);
		  }
		  function fmtInt(v){
			const n = Number(v);
			if (!isFinite(n)) return '–';
			return String(Math.round(n));
		  }
		  function fmtKmFromMeters(m){
			const n = Number(m);
			if (!isFinite(n)) return '–';
			return (n/1000).toFixed(0) + ' km';
		  }
		  function fmtTimeHHMMFromUnix(ts){
			const n = Number(ts);
			if (!isFinite(n)) return '–';
			const d = new Date(n*1000);
			return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
		  }

		  // Zentrale Grenzwerte fuer kritische Wetterparameter
		  const WX_CRITICAL_CFG = {
			wind80Max: 28,
			precipProbMax: 60,
			cloudCoverMax: 85,
			cloudCeilingClearMinM: 150,
			visibilityKmWarn: 2,
			visibilityKmBad: 0.5,
			kpWarn: 5,
			kpBad: 7
		  };

		  function firstNum(v){
			if (v == null) return NaN;
			if (typeof v === 'number') return v;
			const m = String(v).replace(',', '.').match(/-?\d+(?:\.\d+)?/);
			return m ? Number(m[0]) : NaN;
		  }
		  function isCrit(metric, value){
			const n = firstNum(value);
			if (!isFinite(n)) return false;
			if (metric === 'wind80') return n >= WX_CRITICAL_CFG.wind80Max;
			if (metric === 'precip') return n >= WX_CRITICAL_CFG.precipProbMax;
			if (metric === 'cloud') return n >= WX_CRITICAL_CFG.cloudCoverMax;
			if (metric === 'visKm') return n < WX_CRITICAL_CFG.visibilityKmBad;
			if (metric === 'kp') return n > WX_CRITICAL_CFG.kpBad;
			return false;
		  }
		  /** LCL-Näherung Wolkuntergrenze AGL [m]: ~125 m pro °C (T − Taupunkt). */
		  function estCloudBaseAglM(tempC, dewC){
			const t = Number(tempC), td = Number(dewC);
			if (!isFinite(t) || !isFinite(td)) return null;
			const spread = Math.max(0, t - td);
			return 125 * spread;
		  }
		  function isCloudCoverCritical(ccPct, tempC, dewC){
			const n = Number(ccPct);
			if (!isFinite(n) || n < WX_CRITICAL_CFG.cloudCoverMax) return false;
			const base = estCloudBaseAglM(tempC, dewC);
			if (base === null) return true;
			return base <= WX_CRITICAL_CFG.cloudCeilingClearMinM;
		  }
		  function wxVal(text, level){
			const normalized = (level === true) ? 'bad' : (level || '');
			const cls = normalized === 'bad' ? 'wxv wxv-critical' : (normalized === 'warn' ? 'wxv wxv-warn' : 'wxv');
			const prefix = normalized === 'bad' ? '⚠️ ' : '';
			const suffix = normalized === 'bad' ? ' (kritisch)' : (normalized === 'warn' ? ' (Warnung)' : '');
			return '<div class="' + cls + '">' + prefix + esc(text) + suffix + '</div>';
		  }
		  function visLevelFromKm(visKm){
			const n = Number(visKm);
			if (!isFinite(n)) return '';
			if (n < WX_CRITICAL_CFG.visibilityKmBad) return 'bad';
			if (n < WX_CRITICAL_CFG.visibilityKmWarn) return 'warn';
			return '';
		  }
		  function kpLevelFromValue(kp){
			const n = Number(kp);
			if (!isFinite(n)) return '';
			if (n > WX_CRITICAL_CFG.kpBad) return 'bad';
			if (n > WX_CRITICAL_CFG.kpWarn) return 'warn';
			return '';
		  }

		  function estimateGnssVisibleCount(){
			const elevationMaskDeg = 10;
			const totalGnssSat = 31;
			const fraction = (1 - Math.sin(elevationMaskDeg * Math.PI / 180)) / 2;
			const est = Math.round(totalGnssSat * fraction * 0.9);
			return Math.max(8, Math.min(16, est));
		  }

		  function renderSingleHour(day, targetTs, kpObj){
			const h = day && day.hourly ? day.hourly : null;
			const u = day && day.hourly_units ? day.hourly_units : {};
			if (!h || !h.time || !h.time.length) return '<div class="wxnote">⚠️ Keine Daten verfügbar.</div>';

			const i = nearestIndex(h.time, targetTs);

			const wc = h.weathercode ? h.weathercode[i] : null;

			const temp = h.temperature_2m ? h.temperature_2m[i] : null;
			const rh   = h.relative_humidity_2m ? h.relative_humidity_2m[i] : null;
			const dp   = h.dewpoint_2m ? h.dewpoint_2m[i] : null;
			const cc   = h.cloudcover ? h.cloudcover[i] : null;
			const vis  = h.visibility ? h.visibility[i] : null;
			const pp   = h.precipitation_probability ? h.precipitation_probability[i] : null;

			const ws80 = h.windspeed_80m ? h.windspeed_80m[i] : null;
			const wd80 = h.winddirection_80m ? h.winddirection_80m[i] : null;
			const gust = h.windgusts_10m ? h.windgusts_10m[i] : null;

			const sunrise = day && day.daily && day.daily.sunrise ? day.daily.sunrise[0] : null;
			const sunset  = day && day.daily && day.daily.sunset  ? day.daily.sunset[0]  : null;
			const satCount = estimateGnssVisibleCount();

			const windDirText = (wd80==null || wd80==='') ? '–' : (fmtInt(wd80) + '° (' + toCompass(wd80) + ')');

			const kpText = kpObj ? (kpObj.kp.toFixed(0) + ' ' + kpObj.label) : '–';

			const tUnit  = u.temperature_2m || '°C';
			const rhUnit = u.relative_humidity_2m || '%';
			const dpUnit = u.dewpoint_2m || '°C';
			const ccUnit = u.cloudcover || '%';
			const ppUnit = u.precipitation_probability || '%';
			const wsUnit = u.windspeed_80m || 'km/h';
			const gsUnit = u.windgusts_10m || 'km/h';

			const hh = esc(new Date(h.time[i]*1000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}));
			const visText = fmtKmFromMeters(vis);
			const windText = fmtNum(ws80,1) + ' ' + esc(wsUnit);
			const rainText = fmtInt(pp) + ' ' + esc(ppUnit);
			const cloudText = fmtInt(cc) + ' ' + esc(ccUnit);
			const kpDisplay = kpText;

			const cWind = isCrit('wind80', ws80);
			const cRain = isCrit('precip', pp);
			const cCloud = isCloudCoverCritical(cc, temp, dp);
			const visLevel = visLevelFromKm(firstNum(visText));
			const cVis = (visLevel === 'bad');
			const kpLevel = kpLevelFromValue(kpObj ? kpObj.kp : null);
			const cKp = (kpLevel === 'bad');
			const hasCrit = (cWind || cRain || cCloud || cVis || cKp);

			return ''
			  + '<div class="wxcard">'
			  + '  <div class="wxtop"><span><b>Wetter</b>: ' + wxIcon(wc) + ' ' + esc(wxText(wc)) + '</span><span>' + hh + '</span></div>'
			  + '  <div class="wxsep"></div>'
			  + '  <div class="wxkvgrid">'
			  + '    <div class="wxkv"><div class="wxk">Temperatur</div>' + wxVal(fmtNum(temp,1) + ' ' + tUnit, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Luftfeuchte</div>' + wxVal(fmtInt(rh) + ' ' + rhUnit, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Taupunkt</div>' + wxVal(fmtNum(dp,1) + ' ' + dpUnit, false) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Bewölkung</div>' + wxVal(cloudText, cCloud) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Sichtweite</div>' + wxVal(visText, visLevel) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Niederschlag (W’keit)</div>' + wxVal(rainText, cRain) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Wind (80m)</div>' + wxVal(windText, cWind) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Windrichtung</div>' + wxVal(windDirText, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Windböen</div>' + wxVal(fmtNum(gust,1) + ' ' + gsUnit, false) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Sonnenauf/untergang</div>' + wxVal(fmtTimeHHMMFromUnix(sunrise) + ' / ' + fmtTimeHHMMFromUnix(sunset), false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">KP</div>' + wxVal(kpDisplay, kpLevel) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Sichtbare GNSS Satelliten</div>' + wxVal(String(satCount), false) + '</div>'
			  + '  </div>'
			  + (hasCrit ? '  <div class="wxnote" style="color:#b91c1c;font-weight:700;">⚠️ Kritische Wetterparameter erkannt</div>' : '')
			  + '</div>';
		  }

		  async function loadWeather(){
			const inp = findFlightInputs();
			const timeInput = inp.timeInput;
			const placeInput = inp.placeInput;

			const dtLocal = (timeInput && timeInput.value) ? String(timeInput.value).trim() : '';
			const place = (placeInput && placeInput.value) ? String(placeInput.value).trim() : '';

			if (\$('#flightWxPlace')) \$('#flightWxPlace').textContent = place;
			if (\$('#flightWxTime'))  \$('#flightWxTime').textContent  = fmtDisplayDateTime(dtLocal);

			if (!dtLocal || !place){
			  clearWeatherSnapshot();
			  \$('#flightWxContent').innerHTML =
				'<div class="wxnote">ℹ️ Wetter wird angezeigt sobald <b>Flugzeit</b> und <b>Flugort</b> gesetzt sind.</div>';
			  return;
			}

			\$('#flightWxContent').innerHTML = '<div class="wxnote">⏳ Wetter wird geladen…</div>';

			try{
			  const editId = {$editJson};
			  let geo = null;
			  try { geo = await geocode(place); } catch(e) { geo = null; }
			  const dtQuery = toIsoLocalMinute(dtLocal);
			  const params = new URLSearchParams({
				mode: 'weather',
				flugort: place,
				flugzeit: dtQuery
			  });
			  if (geo && geo.lat != null && geo.lon != null){
				params.set('lat', String(geo.lat));
				params.set('lon', String(geo.lon));
			  }
			  if (editId) params.set('edit', editId);
			  const res = await fetch('?' + params.toString(), { cache: 'no-store' });
			  if (!res.ok) throw new Error('Wetterdienst nicht erreichbar');
			  const html = await res.text();
			  const looksEmpty = /Keine Wetterdaten verfügbar|Flugort nicht gefunden|Ungültige Wetteranfrage|Keine gespeicherten Wetterdaten/i.test(html || '');
			  if (!looksEmpty){
				\$('#flightWxContent').innerHTML = html;
				if (geo && geo.lat != null && geo.lon != null){
				  persistWeatherViaOpenMeteo(place, dtLocal, geo, editId).catch(() => {});
				}
				return;
			  }

			  // Fallback: direkte Browser-Abfrage, falls Serverzugriff blockiert ist
			  if (!geo || geo.lat == null || geo.lon == null){
				\$('#flightWxContent').innerHTML = html;
				return;
			  }
			  const d = parseDateFlexible(dtLocal);
			  const targetTs = Math.floor(d.getTime()/1000);
			  const dateIso = toIsoLocalMinute(dtLocal).slice(0,10);
			  if (!isFinite(targetTs) || !dateIso){
				\$('#flightWxContent').innerHTML = '<div class="wxnote">⚠️ Ungültiges Flugzeit-Format.</div>';
				return;
			  }
			  const day = await fetchDay(geo.lat, geo.lon, dateIso, geo.timezone);
			  if (!day || !day.hourly || !day.hourly.time || !day.hourly.time.length){
				\$('#flightWxContent').innerHTML = html;
				return;
			  }
			  let kpObj = null;
			  try { kpObj = await fetchKp(targetTs); } catch(e) { kpObj = null; }
			  \$('#flightWxContent').innerHTML = renderSingleHour(day, targetTs, kpObj);
			  await applyWxSnapshotClientSave(place, dtLocal, geo, editId, day.hourly, targetTs, kpObj);

			}catch(e){
			  console.error(e);
			  const msg = (e && e.message) ? e.message : String(e);
			  \$('#flightWxContent').innerHTML =
				'<div class="wxnote">⚠️ Fehler: <b>' + esc(msg) + '</b></div>';
			}
		  }

		  let t=null;
		  function schedule(){ clearTimeout(t); t=setTimeout(loadWeather, 350); }

		  window.addEventListener('DOMContentLoaded', () => {
			loadWeather();
			const inp = findFlightInputs();
			if (inp.timeInput) inp.timeInput.addEventListener('input', schedule);
			if (inp.placeInput) inp.placeInput.addEventListener('input', schedule);
		  });
		})();
		</script>
		HTML;
	}
    // Auto-Refresh wenn Flugzeit oder Flugort geändert werden

    if ($readonly) {
        echo "<style>
        .checklist-readonly-form .field input:disabled,
        .checklist-readonly-form .field textarea:disabled,
        .checklist-readonly-form .field select:disabled{opacity:1;color:#111827;-webkit-text-fill-color:#111827;}
        .checklist-readonly-form .color-option input:disabled{pointer-events:none;}
        .checklist-readonly-form .generated-status-readonly .color-option{cursor:default;}
        .checklist-readonly-form .generated-plan-thumb{pointer-events:auto;cursor:zoom-in;}
        .checklist-readonly-form .generated-bauer-details summary{pointer-events:auto;cursor:pointer;}
        </style>";
        echo "<div class='rkrk-card' style='border-color:#93c5fd;background:#eff6ff;margin-bottom:12px;'>"
            . "<strong>Nur Lesen</strong> — Änderungen sind nicht möglich. "
            . "<a href='?mode=form&amp;file=" . rawurlencode($file) . ($edit ? "&amp;edit=" . rawurlencode($edit) : "") . "'>Bearbeiten</a>"
            . "</div>";
    }

    echo "<div class='rkrk-card checklist-readonly-form'>
            <form method='post' action='rkrk_checklist.php?mode=save' id='rkrkChecklistForm'" . (!$readonly && !$isGuest ? " data-autosave='1'" : "") . ">
            <input type='hidden' name='file' value='$file'>
            <input type='hidden' id='weather_json' name='weather_json' value=''>
            <input type='hidden' id='dynamic_status_state' name='dynamic_status_state' value=''>
            <input type='hidden' id='field_time_map' name='field_time_map' value='".safe($fieldTimeMapRaw)."'>";
    if (preg_match('/^\d{4}$/', $flightsCsvYear)) {
        echo "<input type='hidden' name='flights_csv_year' id='rkrkFlightsCsvYear' value='".safe($flightsCsvYear)."'>";
    }
    if ($checklistMdb !== '') {
        echo "<input type='hidden' name='MDB' id='rkrkChecklistMdb' value='".safe($checklistMdb)."'>";
    }
    if ($edit){
        echo "<input type='hidden' name='edit' id='rkrkChecklistEditId' value='" . safe($edit) . "'>";
    }

	foreach ($sections as $secName => $fields){

		$secId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $secName));
        $secTitleHtml = safe($secName);
        if (strcasecmp((string)$secName, "Suchresultat") === 0) {
            // No extra subtitle line here.
        }

		echo "<div class='section-row'>
				<div class='section-title-box' data-section-id='$secId'>".$secTitleHtml."</div>
				<div class='section-fields' data-section-id='$secId'>";

        if (strcasecmp((string)$secName, "Suchresultat") === 0 && $tagesflugplanBoxHtml !== "") {
            echo "<div class='generated-group-title' style='margin-top:0;'>Flugplan</div>";
            echo $tagesflugplanBoxHtml;
        }

		$deferredFundeValue = null;

		// Felder rendern
		foreach ($fields as $label => $type){
			if (strcasecmp((string)$secName, "Suchresultat") === 0 && trim((string)$label) === "Zeit zum Feld") {
				continue;
			}
			if (strcasecmp((string)$secName, "Suchresultat") === 0 && rkrk_checklist_label_is_suchresultat_field_storage($label)) {
				continue;
			}
			if (strcasecmp((string)$secName, "Suchresultat") === 0) {
				if (strcasecmp((string)$label, "Funde") === 0) {
					$tmpFunde = $saved[$secName][$label] ?? ($isBlankNew ? "" : ($_GET[$label] ?? ""));
					if (trim((string)$tmpFunde) === "" && !$isBlankNew) {
						$tmpFunde = ($fundeFromFlightsCsv !== '') ? $fundeFromFlightsCsv : "keine";
					} elseif (trim((string)$tmpFunde) === "keine" && $fundeFromFlightsCsv !== '') {
						$tmpFunde = $fundeFromFlightsCsv;
					}
					$deferredFundeValue = $tmpFunde;
					continue;
				}
				if (strcasecmp((string)$label, "Beboachtungen") === 0) {
					continue;
				}
			}
			$value = $saved[$secName][$label] ?? ($isBlankNew ? "" : ($_GET[$label] ?? ""));
			renderField($secName, $label, $type, $value, $readonly);
		}

		// Wetter-Hinweis anzeigen
		if ($secName === "Wetter" && isset($saved["Wetter"]["Gespeichert am"]) && trim((string)$saved["Wetter"]["Gespeichert am"]) !== '') {
			echo "<div class='system-info-box' style='margin-top:10px;'>
					🌤️ Wetterdaten gespeichert am: " . safe($saved["Wetter"]["Gespeichert am"]) . "
				  </div>";
		}

		// Dynamische Status-Zeilen im Suchresultat (gleiches Layout wie Bearbeiten)
		if (strcasecmp((string)$secName, "Suchresultat") === 0) {
			echo "<div id='dynamic-status-rows' class='".($readonly ? "generated-status-readonly" : "")."' data-sec-name='".safe($secName)."' data-readonly='".($readonly ? "1" : "0")."'></div>";
			renderField($secName, "Funde", "text", $deferredFundeValue ?? ($isBlankNew ? "" : "keine"), $readonly);
		}

		// Extra-Felder
		if (isset($extraFields[$secName])) {
			foreach ($extraFields[$secName] as $label => $value) {
				// Diese Felder werden im dynamischen Feld#-Block gerendert.
				if (
					strcasecmp((string)$secName, "Suchresultat") === 0 &&
					(
						trim((string)$label) === "Zeit zum Feld" ||
						rkrk_checklist_label_is_suchresultat_field_storage($label)
					)
				) {
					continue;
				}
				$extraType = "text";
				if (rkrk_is_dynamic_status_label($label)) {
					$extraType = rkrk_status_multi_type();
				} elseif (rkrk_is_dynamic_status_other_label($label)) {
					$extraType = "text";
				}
				renderField($secName, $label, $extraType, $value, $readonly);
			}
		}

        if (!$readonly && !$isGuest){
            $newSecId = $secId . "_new";

            echo "
            <div class='new-field-add-button'>
                <button type='button' class='btn-newfield' onclick=\"addNewField('$secName', '$newSecId')\">
                    +
                </button>
            </div>

            <div class='section-fields new-fields-container collapsed' id='new-fields-$newSecId'></div>
            ";
        }

        echo "</div></div>";
    }

    if ($checklistDay !== '' && function_exists('rkrk_checklist_render_flugbuch_panel')) {
        rkrk_checklist_render_flugbuch_panel($flightsCsvPerField, $flightsCsvYear, $checklistMdb, $checklistDay, $checklistPilot);
    }

    if (!$readonly && !$isGuest) {
        $saveStatusInit = $modifiedAt
            ? ('Zuletzt gespeichert: ' . safe($modifiedAt))
            : 'Änderungen werden automatisch gespeichert';
        echo "<div class='rkrk-checklist-save-bar' style='margin-top:16px;padding:10px 12px;background:#f8faf8;border:1px solid #e2e8e0;border-radius:6px;text-align:center;'>";
        echo "<span id='rkrkChecklistSaveStatus' style='font-size:13px;color:#556;' aria-live='polite'>" . $saveStatusInit . "</span>";
        echo "</div>";
    } elseif ($modifiedAt) {
        echo "<div class='rkrk-checklist-save-bar' style='margin-top:16px;padding:10px 12px;background:#f8faf8;border:1px solid #e2e8e0;border-radius:6px;text-align:center;'>";
        echo "<span style='font-size:13px;color:#556;'>Zuletzt gespeichert: " . safe($modifiedAt) . "</span>";
        echo "</div>";
    }

    echo "</form></div>";

    if ($createdAt){
        echo "<div class='system-info-box'>🕒 Erstellt am: ".safe($createdAt)."</div>";
    }

    if ($edit){
        echo "<div style='text-align:center;margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;align-items:center;'>";
        echo "<a href='?mode=listall' class='btn' title='Zurück zur Übersicht'>Zurück zu den Checklisten</a>";
        echo "</div>";
    }
	if (function_exists("rkrk_checklist_toolbar_echo_form_bar")) {
		rkrk_checklist_toolbar_echo_form_bar($file, $edit, $readonly);
	}
	echo "
	<script>
	let newFieldCounter = 0;

	function addNewField(secName, secId) {

		const container = document.getElementById('new-fields-' + secId);

		// Toggle Sichtbarkeit
		if (container.classList.contains('collapsed')) {
			container.classList.remove('collapsed');
		} else {
			container.classList.add('collapsed');
			return; // nicht erneut Feld hinzufügen
		}

		const index = newFieldCounter++;

		const html = `
			<div class='field new-field-block'>
				<label class='field-label'>Neues Feld</label>

				<input type='text'
					   name='new_fields[\${secName}][label][\${index}]'
					   placeholder='Label'>

				<select name='new_fields[\${secName}][type][\${index}]'>
					<option value='text'>Text</option>
					<option value='textarea'>Textarea</option>
					<option value='number'>Number</option>
					<option value='date'>Date</option>
					<option value='datetime'>Datetime</option>
					<option value='checkbox'>Checkbox</option>
					<option value='radio'>Radio (Optionen folgen)</option>
					<option value='multi'>Multi (Optionen folgen)</option>
				</select>

				<input type='text'
					   name='new_fields[\${secName}][options][\${index}]'
					   placeholder='Optionen (a|b|c)'>

				<input type='text'
					   name='new_fields[\${secName}][value][\${index}]'
					   placeholder='Wert'>
			</div>
		`;

		container.insertAdjacentHTML('beforeend', html);
		if (typeof window.rkrkChecklistScheduleSave === 'function') {
			window.rkrkChecklistScheduleSave(400);
		}
	}
	</script>";

    $dynamicStatusInitial = [];
    $srForInit = [];
    if (isset($saved["Suchresultat"]) && is_array($saved["Suchresultat"])) {
        $srForInit = $saved["Suchresultat"];
    }
    if (isset($extraFields["Suchresultat"]) && is_array($extraFields["Suchresultat"])) {
        $srForInit = array_merge($srForInit, $extraFields["Suchresultat"]);
    }
    if (count($srForInit) > 0) {
        $dynamicStatusInitial = rkrk_checklist_build_dynamic_status_initial($srForInit);
    }
    $fieldTimeMapParsed = rkrk_sync_parse_field_time_map($fieldTimeMapRaw);
    if (!empty($flightsCsvPerField) && function_exists('rkrk_checklist_merge_flights_csv_into_status_initial')) {
        rkrk_checklist_merge_flights_csv_into_status_initial($dynamicStatusInitial, $flightsCsvPerField, $fieldTimeMapParsed);
    }

    $fieldMetaMapJson = json_encode(rkrk_get_field_meta_map(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!$fieldMetaMapJson) $fieldMetaMapJson = "{}";
    $fieldTimeMapJson = json_encode($fieldTimeMapParsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!$fieldTimeMapJson) $fieldTimeMapJson = "{}";
    $dynamicStatusInitialJson = json_encode($dynamicStatusInitial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!$dynamicStatusInitialJson) $dynamicStatusInitialJson = "{}";
    $savedInstanceOrder = rkrk_checklist_saved_instance_order($srForInit);
    $savedInstanceOrderJson = json_encode($savedInstanceOrder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!$savedInstanceOrderJson) $savedInstanceOrderJson = "[]";
    $readonlyJsFlag = $readonly ? 'true' : 'false';
    echo <<<HTML
<script>
(function(){
    const READONLY_MODE = {$readonlyJsFlag};
    const host = document.getElementById('dynamic-status-rows');
    if (!host) return;
    if (!document.getElementById('dynamic-status-inline-style')) {
        const st = document.createElement('style');
        st.id = 'dynamic-status-inline-style';
        st.textContent = ''
            + '.generated-status-grid{display:flex;flex-wrap:wrap;gap:6px;overflow:visible;padding-bottom:2px;}'
            + '.generated-compact-option{margin:0;min-width:auto;flex:0 0 auto;}'
            + '.generated-compact-option .color-badge{padding:4px 8px;font-size:0.9em;line-height:1.1;white-space:nowrap;}'
            + '.generated-group-title{font-size:0.8em;color:#4b5563;margin:2px 0 4px 2px;font-weight:600;}'
            + '.generated-group-wrap{margin-bottom:4px;}'
            + '.generated-plan-thumb{cursor:zoom-in;display:inline-block;max-width:100%;}'
            + '.generated-plan-thumb img{display:block;width:min(360px,100%);max-width:100%;height:auto;max-height:280px;object-fit:contain;border:1px solid #cfd6df;border-radius:4px;background:#fff;}'
            + '.generated-bauer-details{margin-top:8px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;padding:2px 10px 8px;font-size:0.9em;}'
            + '.generated-bauer-details > summary{cursor:pointer;font-weight:600;color:#1f2937;padding:6px 2px;list-style-position:outside;}'
            + '.generated-bauer-details[open]{background:#fff;}'
            + '.generated-bauer-body{margin-top:4px;padding-top:8px;border-top:1px solid #e5e7eb;color:#374151;line-height:1.45;text-align:left;}'
            + '.generated-layout{display:block;}'
            + '.generated-layout-left{margin-bottom:6px;}'
            + '.generated-layout-right{min-width:0;}'
            + '.generated-image-modal{position:fixed;inset:0;background:rgba(0,0,0,.72);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;}'
            + '.generated-image-modal.open{display:flex;}'
            + '.generated-image-modal-content{max-width:96vw;max-height:92vh;border-radius:8px;background:#fff;padding:8px;box-shadow:0 16px 48px rgba(0,0,0,.35);}'
            + '.generated-image-modal-content img{display:block;max-width:95vw;max-height:88vh;width:auto;height:auto;border-radius:4px;}'
            + '@media (min-width: 700px){'
            + '  .generated-layout{display:grid;grid-template-columns:minmax(200px,360px) 1fr;gap:8px;align-items:start;}'
            + '  .generated-layout-left{margin-bottom:0;}'
            + '}'
            + '.generated-status-readonly .color-option input:disabled{pointer-events:none;}'
            + '.generated-status-readonly .generated-status-other input:disabled{opacity:1;-webkit-text-fill-color:#111827;}';
        document.head.appendChild(st);
    }

    const secName = host.dataset.secName || 'Suchresultat';
    function resolveFeldInput(){
        return document.querySelector('[data-field-key="feld_"]')
            || document.querySelector('input[name*="[Feld#]"]');
    }
    const feldInput = resolveFeldInput();
    if (!feldInput) return;
    const FIELD_META_MAP = $fieldMetaMapJson || {};
    const FIELD_TIME_MAP = $fieldTimeMapJson || {};
    const INITIAL_STATUS_STATE = $dynamicStatusInitialJson || {};
    const LIVE_STATUS_STATE = Object.assign({}, INITIAL_STATUS_STATE);
    const SAVED_INSTANCE_ORDER = $savedInstanceOrderJson || [];
    const RKRK_FELD_ID_RE = /([A-Z0-9]{1,4}-\d{1,4})/gi;

    const OPTIONS = ['geplant','auf RC/geprüft','geflogen','nicht geflogen (r)','Keine Funde','1 Kitz','2 Kitze','andere'];

    function escHtml(s){
        return String(s ?? '').replace(/[&<>"']/g, (m) => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[m]));
    }

    function optionUiInfo(raw){
        const src = String(raw || '').trim();
        const m = src.match(/\s*\((g|r|o)\)$/i);
        const colorMap = { g:'#6bb36b', r:'#d06a6a', o:'#d89b4c' };
        if (!m) return { value: src, label: src, style: '' };
        const key = String(m[1] || '').toLowerCase();
        const clean = src.replace(/\s*\((g|r|o)\)$/i, '').trim();
        const color = colorMap[key] || '';
        const style = color ? (' style="border-color:' + color + ';--option-color:' + color + ';"') : '';
        return { value: src, label: clean, style: style };
    }

    /** Pfad mit Umlauten für img src / href (Segmentweise encodeURIComponent). */
    function encodeMediaUrl(url){
        const u = String(url || '').trim();
        if (!u) return '';
        const encSeg = (seg) => {
            if (!seg) return seg;
            try { return encodeURIComponent(decodeURIComponent(seg)); }
            catch (e) { return encodeURIComponent(seg); }
        };
        try {
            if (/^https?:\/\//i.test(u)) {
                const parsed = new URL(u);
                parsed.pathname = parsed.pathname.split('/').map(encSeg).join('/');
                return parsed.href;
            }
        } catch (e) { /* relative URL */ }
        const q = u.indexOf('?');
        const base = q >= 0 ? u.slice(0, q) : u;
        const query = q >= 0 ? u.slice(q) : '';
        const parts = base.split('/');
        return parts.map((seg, i) => {
            if (!seg) return seg;
            if (i === 0 && /^[a-z][a-z0-9+.-]*:$/i.test(seg)) return seg;
            return encSeg(seg);
        }).join('/') + query;
    }

    function parseFieldList(raw){
        const out = [];
        const s = String(raw || '');
        const re = new RegExp(RKRK_FELD_ID_RE.source, 'gi');
        let m;
        while ((m = re.exec(s)) !== null) {
            if (m[1]) out.push(String(m[1]).trim());
        }
        return out;
    }

    function parseFieldTimeMap(raw){
        const out = [];
        const s = String(raw || '');
        const re = new RegExp('(' + RKRK_FELD_ID_RE.source + ')\\s*[:=]\\s*([0-2]?\\d:[0-5]\\d)', 'gi');
        let m;
        while ((m = re.exec(s)) !== null) {
            if (m[1]) out.push({ fid: String(m[1]).trim(), time: String(m[2] || '').trim() });
        }
        return out;
    }

    function timeFromStateKeys(fid, seq){
        const wantSeq = String(seq || 1);
        let found = '';
        Object.keys(INITIAL_STATUS_STATE).forEach(key => {
            if (found) return;
            const parts = key.split('|');
            if (normalizeFieldId(parts[0] || '') !== fid) return;
            if (String(parts[2] || '1') !== wantSeq) return;
            const t = flugzeitForTitle(parts[1] || '');
            if (t) found = t;
        });
        return found;
    }

    function lookupInitialState(inst){
        const k = instanceKey(inst);
        if (INITIAL_STATUS_STATE[k]) {
            const t = flugzeitForTitle(inst.time) || timeFromStateKeys(normalizeFieldId(inst.fid), inst.seq);
            return { state: INITIAL_STATUS_STATE[k], time: t };
        }
        const fid = normalizeFieldId(inst.fid);
        const seq = String(inst.seq || 1);
        const noTimeKey = fid + '||' + seq;
        if (INITIAL_STATUS_STATE[noTimeKey]) {
            const t = flugzeitForTitle(inst.time) || timeFromStateKeys(fid, seq);
            return { state: INITIAL_STATUS_STATE[noTimeKey], time: t };
        }
        if (INITIAL_STATUS_STATE[fid]) {
            return { state: INITIAL_STATUS_STATE[fid], time: String(inst.time || '').trim() };
        }
        const wantTime = String(inst.time || '').trim();
        const candidates = [];
        Object.keys(INITIAL_STATUS_STATE).forEach(key => {
            const parts = key.split('|');
            const kFid = normalizeFieldId(parts[0] || '');
            const kSeq = String(parts[2] || '1');
            if (kFid !== fid || kSeq !== seq) return;
            candidates.push({
                key: key,
                state: INITIAL_STATUS_STATE[key],
                time: String(parts[1] || '').trim()
            });
        });
        if (!candidates.length) return null;
        if (wantTime) {
            const exact = candidates.find(c => c.time === wantTime);
            if (exact) return exact;
        }
        return candidates[0];
    }

    function sortInstancesBySavedOrder(instances){
        if (!Array.isArray(SAVED_INSTANCE_ORDER) || !SAVED_INSTANCE_ORDER.length) return instances;
        const rank = {};
        SAVED_INSTANCE_ORDER.forEach((k, i) => {
            rank[String(k)] = i;
            const parts = String(k).split('|');
            const fid = normalizeFieldId(parts[0] || '');
            const seq = String(parts[2] || '1');
            rank[fid + '||' + seq] = (rank[fid + '||' + seq] === undefined) ? i : Math.min(rank[fid + '||' + seq], i);
        });
        return instances.slice().sort((a, b) => {
            const ka = instanceKey(a);
            const kb = instanceKey(b);
            const ra = rank[ka] ?? rank[normalizeFieldId(a.fid) + '||' + String(a.seq || 1)] ?? 99999;
            const rb = rank[kb] ?? rank[normalizeFieldId(b.fid) + '||' + String(b.seq || 1)] ?? 99999;
            if (ra !== rb) return ra - rb;
            return 0;
        });
    }

    function inputName(label, isArray){
        return 'fields[' + secName + '][' + label + ']' + (isArray ? '[]' : '');
    }

    function getFundeInput(){
        return document.querySelector('input[name="' + inputName('Funde', false) + '"]');
    }

    function statusLabel(inst){
        let inner = normalizeFieldId(inst.fid);
        if (inst.time) inner += ' | ' + inst.time;
        inner += ' | ' + String(inst.seq || 1);
        return 'Status und Suchresultate (' + inner + ')';
    }

    function otherLabel(inst){
        let inner = normalizeFieldId(inst.fid);
        if (inst.time) inner += ' | ' + inst.time;
        inner += ' | ' + String(inst.seq || 1);
        return 'Status und Suchresultate andere (' + inner + ')';
    }

    function normalizeFieldId(token){
        const t = String(token || '').trim();
        const re = new RegExp(RKRK_FELD_ID_RE.source, 'i');
        const m = t.match(re);
        return m ? String(m[1]).trim() : t;
    }

    function fieldDisplay(fieldId){
        const id = normalizeFieldId(fieldId);
        const info = FIELD_META_MAP[id] || {};
        const name = String(info.name || '').trim();
        return name ? (id + ' - ' + name) : id;
    }

    /** Flugzeit HH:MM für Anzeige im Titel (keine Feld-ID). */
    function flugzeitForTitle(raw){
        const t = String(raw || '').trim();
        if (!t) return '';
        const fidRe = new RegExp('^' + RKRK_FELD_ID_RE.source + '$', 'i');
        if (fidRe.test(t)) return '';
        let m = t.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
        if (m) return String(parseInt(m[1], 10)).padStart(2, '0') + ':' + m[2];
        m = t.match(/(\d{1,2}):(\d{2})/);
        if (m) return String(parseInt(m[1], 10)).padStart(2, '0') + ':' + m[2];
        return '';
    }

    function resolveInstanceTime(inst){
        let t = flugzeitForTitle(inst.time);
        if (t) return t;
        const fid = normalizeFieldId(inst.fid);
        const seq = String(inst.seq || 1);
        t = timeFromStateKeys(fid, seq);
        if (t) return t;
        const hit = lookupInitialState(inst);
        if (hit && hit.time) {
            t = flugzeitForTitle(hit.time);
            if (t) return t;
        }
        return '';
    }

    function fieldTitleWithFlugzeit(inst, timeOverride){
        const base = fieldDisplay(inst.fid);
        const ft = flugzeitForTitle(timeOverride !== undefined ? timeOverride : inst.time);
        if (ft) return ft + 'h ' + base;
        return base;
    }

    function buildTimeQueues(){
        const q = {};
        Object.keys(FIELD_TIME_MAP || {}).forEach(fid => {
            const k = normalizeFieldId(fid);
            const t = flugzeitForTitle(FIELD_TIME_MAP[fid]);
            if (!t) return;
            if (!q[k]) q[k] = [];
            q[k].push(t);
        });
        parseFieldTimeMap((document.getElementById('field_time_map') || {}).value || '').forEach(e => {
            const k = normalizeFieldId(e.fid);
            const t = flugzeitForTitle(e.time);
            if (!t) return;
            if (!q[k]) q[k] = [];
            q[k].push(t);
        });
        return q;
    }

    function fieldMeta(fieldId){
        const id = normalizeFieldId(fieldId);
        const info = FIELD_META_MAP[id];
        if (!info || typeof info !== 'object') {
            return {
                flugplan:'', startpoint:'', google:'', ngt_icons_html:'',
                bauer_details_show:false, bauer_details_summary:'', bauer_details_body_html:''
            };
        }
        return {
            flugplan: String(info.flugplan || '').trim(),
            startpoint: String(info.startpoint || '').trim(),
            google: String(info.startpoint_google || '').trim(),
            startIcon: String(info.startpoint_icon || '').trim(),
            ngt_icons_html: String(info.ngt_icons_html || ''),
            bauer_details_show: !!info.bauer_details_show,
            bauer_details_summary: String(info.bauer_details_summary || ''),
            bauer_details_body_html: String(info.bauer_details_body_html || '')
        };
    }

    function ngtIconsHtmlForField(fieldId){
        return String((fieldMeta(fieldId).ngt_icons_html) || '');
    }

    function bauerDetailsHtmlForField(fieldId){
        const m = fieldMeta(fieldId);
        if (!m.bauer_details_show) return '';
        const sumRaw = String(m.bauer_details_summary || '').trim();
        const sum = sumRaw ? escHtml(sumRaw) : escHtml('Bauerndaten');
        const body = String(m.bauer_details_body_html || '');
        if (!body.trim()) return '';
        return '<details class="generated-bauer-details">' +
            '<summary>' + sum + '</summary>' +
            '<div class="generated-bauer-body">' + body + '</div>' +
            '</details>';
    }

    function instanceKey(inst){
        return normalizeFieldId(inst.fid) + '|' + String(inst.time || '') + '|' + String(inst.seq || 1);
    }

    function buildInstances(){
        const fields = parseFieldList(feldInput.value);
        const q = buildTimeQueues();
        const cnt = {};
        const instances = fields.map(f => {
            const fid = normalizeFieldId(f);
            cnt[fid] = (cnt[fid] || 0) + 1;
            let time = '';
            if (q[fid] && q[fid].length) time = q[fid].shift();
            return { fid, time, seq: cnt[fid] };
        });
        return sortInstancesBySavedOrder(instances);
    }

    function captureDomState(){
        const map = {};
        host.querySelectorAll('[data-generated-key]').forEach(row => {
            const key = String(row.getAttribute('data-generated-key') || '').trim();
            if (!key) return;
            const checked = [];
            row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (cb.checked) checked.push(String(cb.value || '').trim());
            });
            const otherInput = host.querySelector('input[data-generated-other-for="' + key + '"]');
            map[key] = {
                checked: checked.filter(Boolean),
                other: otherInput ? String(otherInput.value || '').trim() : ''
            };
        });
        return map;
    }

    function stateForInstance(inst, domMap){
        const k = instanceKey(inst);
        if (domMap[k]) return domMap[k];
        if (LIVE_STATUS_STATE[k]) return LIVE_STATUS_STATE[k];
        const altNoTime = normalizeFieldId(inst.fid) + '||' + String(inst.seq || 1);
        if (LIVE_STATUS_STATE[altNoTime]) return LIVE_STATUS_STATE[altNoTime];
        const hit = lookupInitialState(inst);
        if (hit && typeof hit.state === 'object') {
            return {
                checked: Array.isArray(hit.state.checked)
                    ? hit.state.checked.map(v => String(v || '').trim()).filter(Boolean)
                    : [],
                other: typeof hit.state.other === 'string' ? hit.state.other : ''
            };
        }
        return { checked: [], other: '' };
    }

    function restoreState(instances){
        const domMap = captureDomState();
        Object.keys(domMap).forEach(k => { LIVE_STATUS_STATE[k] = domMap[k]; });
        const map = {};
        instances.forEach(inst => {
            const k = instanceKey(inst);
            map[k] = stateForInstance(inst, domMap);
            const hit = lookupInitialState(inst);
            const resolved = resolveInstanceTime(inst);
            if (resolved) {
                inst.time = resolved;
            } else if (!String(inst.time || '').trim() && hit && hit.time) {
                inst.time = flugzeitForTitle(hit.time) || hit.time;
            }
        });
        const valid = new Set(instances.map(inst => instanceKey(inst)));
        Object.keys(LIVE_STATUS_STATE).forEach(k => {
            if (!valid.has(k)) delete LIVE_STATUS_STATE[k];
        });
        return map;
    }

    function syncOtherWrapVisibility(genKey){
        const row = host.querySelector('[data-generated-key="' + genKey + '"]');
        const otherWrap = host.querySelector('[data-generated-other-wrap="' + genKey + '"]');
        const cbOther = row ? row.querySelector('input[type="checkbox"][value="andere"]') : null;
        if (otherWrap) {
            otherWrap.style.display = (cbOther && cbOther.checked) ? '' : 'none';
        }
    }

    function lockGeneratedRowsReadonly(){
        host.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = true; });
        host.classList.add('generated-status-readonly');
        host.querySelectorAll('[data-generated-key]').forEach(row => {
            const genKey = row.getAttribute('data-generated-key') || '';
            if (genKey) syncOtherWrapVisibility(genKey);
        });
    }

    function updateRules(genKey){
        if (READONLY_MODE) {
            syncOtherWrapVisibility(genKey);
            return;
        }
        const row = host.querySelector('[data-generated-key="' + genKey + '"]');
        if (!row) return;
        const cbPlanned = row.querySelector('input[type="checkbox"][value="geplant"]');
        const cbRcChecked = row.querySelector('input[type="checkbox"][value="auf RC/geprüft"]');
        const cbNotFlown = row.querySelector('input[type="checkbox"][value="nicht geflogen (r)"]');
        const cbFlown = row.querySelector('input[type="checkbox"][value="geflogen"]');
        const cbOther = row.querySelector('input[type="checkbox"][value="andere"]');
        const otherWrap = host.querySelector('[data-generated-other-wrap="' + genKey + '"]');

        if (cbRcChecked && cbRcChecked.checked) {
            if (cbPlanned) cbPlanned.checked = true;
        }
        // Reverse dependency: removing prerequisite removes "auf RC/geprüft".
        if (cbRcChecked && (!cbPlanned || !cbPlanned.checked)) cbRcChecked.checked = false;
        if (cbNotFlown && cbNotFlown.checked && cbFlown) {
            cbFlown.checked = false;
        }
        if (cbFlown && cbFlown.checked && cbNotFlown) {
            cbNotFlown.checked = false;
        }

        if (otherWrap) {
            otherWrap.style.display = (cbOther && cbOther.checked) ? '' : 'none';
        }
        updateFundeFromStatuses();
    }

    function updateFundeFromStatuses(){
        const fundeInput = getFundeInput();
        if (!fundeInput) return;

        const chunks = [];
        host.querySelectorAll('[data-generated-field]').forEach(row => {
            const fieldId = row.dataset.generatedField || '';
            const key = row.dataset.generatedKey || '';
            if (!fieldId || !key) return;

            const checkedVals = [];
            row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (cb.checked) checkedVals.push(cb.value);
            });

            let kitzCount = 0;
            if (checkedVals.includes('2 Kitze')) kitzCount = 2;
            else if (checkedVals.includes('1 Kitz')) kitzCount = 1;

            if (kitzCount > 0) {
                chunks.push(fieldDisplay(fieldId) + ': ' + (kitzCount === 1 ? '1 Kitz' : '2 Kitze'));
            }

            if (checkedVals.includes('andere')) {
                const otherInput = host.querySelector('input[data-generated-other-for="' + key + '"]');
                const otherTxt = otherInput ? String(otherInput.value || '').trim() : '';
                if (otherTxt !== '') {
                    chunks.push(fieldDisplay(fieldId) + ': ' + otherTxt);
                }
            }
        });

        fundeInput.value = chunks.length ? chunks.join('; ') : 'keine';
        syncDynamicStateToHidden();
    }

    function syncDynamicStateToHidden(){
        const sink = document.getElementById('dynamic_status_state');
        if (!sink) return;
        const state = {};
        host.querySelectorAll('[data-generated-field]').forEach(row => {
            const fieldId = normalizeFieldId(row.dataset.generatedField || '');
            const key = String(row.dataset.generatedKey || '');
            const time = String(row.dataset.generatedTime || '').trim();
            const seq = parseInt(String(row.dataset.generatedSeq || '1'), 10) || 1;
            if (!fieldId) return;
            const meta = fieldMeta(fieldId);
            const checked = [];
            row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (cb.checked) checked.push(String(cb.value || '').trim());
            });
            const otherInput = host.querySelector('input[data-generated-other-for="' + key + '"]');
            const inst = { fid: fieldId, time: time, seq: seq };
            state[key] = {
                checked: checked,
                other: otherInput ? String(otherInput.value || '').trim() : '',
                flugplan: meta.flugplan,
                startpoint: meta.startpoint,
                startpoint_google: meta.google,
                status_label: statusLabel(inst),
                other_label: otherLabel(inst),
                field_id: fieldId,
                time: time,
                seq: seq
            };
        });
        sink.value = JSON.stringify(state);
    }

    function bindRow(genKey){
        const row = host.querySelector('[data-generated-key="' + genKey + '"]');
        if (!row) return;
        if (READONLY_MODE) {
            syncOtherWrapVisibility(genKey);
            return;
        }
        row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', () => {
                updateRules(genKey);
                if (typeof window.rkrkChecklistScheduleSave === 'function') window.rkrkChecklistScheduleSave();
            });
        });
        const otherInput = host.querySelector('input[data-generated-other-for="' + genKey + '"]');
        if (otherInput) {
            otherInput.addEventListener('input', updateFundeFromStatuses);
            otherInput.addEventListener('change', updateFundeFromStatuses);
        }
        updateRules(genKey);
    }

    function updateTagesflugplanMap(){
        const body = document.getElementById('flugplanBoxBody');
        const box = document.getElementById('rkrkTagesflugplanBox');
        if (!body || !box) return;
        const suffix = box.getAttribute('data-map-suffix') || '';
        const ids = buildInstances().map(i => normalizeFieldId(i.fid)).filter(Boolean);
        let iframe = body.querySelector('.planbox-frame');
        let linkWrap = body.querySelector('.planbox-link');
        let placeholder = body.querySelector('.system-info-box');
        if (!ids.length) {
            if (iframe) iframe.remove();
            if (linkWrap) linkWrap.remove();
            if (!placeholder) {
                body.insertAdjacentHTML('afterbegin', '<div class="system-info-box" style="margin:0;">Bitte zuerst im Feld <b>Feld#</b> mindestens eine gültige Feldnummer (z.&nbsp;B. 25-049 oder ZG4-056) eintragen.</div>');
            }
            return;
        }
        if (placeholder) placeholder.remove();
        const url = 'rkrk_map.php?FFilter=' + encodeURIComponent(ids.join('+')) + suffix;
        if (!iframe) {
            body.insertAdjacentHTML('afterbegin', '<iframe class="planbox-frame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>');
            iframe = body.querySelector('.planbox-frame');
        }
        if (iframe && iframe.src !== url) iframe.src = url;
        if (!linkWrap) {
            body.insertAdjacentHTML('beforeend', '<div class="planbox-link"><a href="#" target="_blank" rel="noopener">Flugplan in rkrk_map öffnen</a></div>');
            linkWrap = body.querySelector('.planbox-link');
        }
        const a = linkWrap ? linkWrap.querySelector('a') : null;
        if (a) a.href = url;
    }

    function buildRows(){
        const fundeInput = getFundeInput();
        if (fundeInput) {
            fundeInput.readOnly = true;
            fundeInput.title = 'Wird automatisch aus Status und Suchresultate berechnet';
        }
        const instances = buildInstances();
        const keep = restoreState(instances);
        host.innerHTML = '';

        instances.forEach(inst => {
            const key = instanceKey(inst);
            const statusLbl = statusLabel(inst);
            const otherLbl = otherLabel(inst);
            const titleTime = resolveInstanceTime(inst);
            const statusTitle = fieldTitleWithFlugzeit(inst, titleTime);
            const otherTitle = statusTitle + ' — andere';
            const meta = fieldMeta(inst.fid);
            const selected = (keep[key] && Array.isArray(keep[key].checked)) ? keep[key].checked : [];
            if (selected.includes('geprüft') || selected.includes('auf RC')) {
                if (!selected.includes('auf RC/geprüft')) selected.push('auf RC/geprüft');
            }
            const otherVal = (keep[key] && typeof keep[key].other === 'string') ? keep[key].other : '';

            const statusOptions = (inst.seq > 1)
                ? ['geflogen','nicht geflogen (r)']
                : ['geplant','auf RC/geprüft','geflogen','nicht geflogen (r)'];
            const fundOptions = ['Keine Funde','1 Kitz','2 Kitze','andere'];

            let statusOptionsHtml = '';
            statusOptions.forEach(opt => {
                const checked = selected.includes(opt) ? 'checked' : '';
                const ui = optionUiInfo(opt);
                statusOptionsHtml +=
                    '<label class="color-option generated-compact-option"' + ui.style + '>' +
                    '<input type="checkbox" name="' + escHtml(inputName(statusLbl, true)) + '" value="' + escHtml(ui.value) + '" ' + checked + '>' +
                    '<span class="color-badge">' + escHtml(ui.label) + '</span>' +
                    '</label>';
            });

            let fundOptionsHtml = '';
            fundOptions.forEach(opt => {
                const checked = selected.includes(opt) ? 'checked' : '';
                fundOptionsHtml +=
                    '<label class="color-option generated-compact-option">' +
                    '<input type="checkbox" name="' + escHtml(inputName(statusLbl, true)) + '" value="' + escHtml(opt) + '" ' + checked + '>' +
                    '<span class="color-badge">' + escHtml(opt) + '</span>' +
                    '</label>';
            });

            const bauerUnderPlan = bauerDetailsHtmlForField(inst.fid);

            const flugplanUrl = encodeMediaUrl(meta.flugplan);
            const flugplanHtml = flugplanUrl
                ? (
                    '<a href="' + escHtml(flugplanUrl) + '" class="generated-plan-thumb" data-generated-plan-full="' + escHtml(flugplanUrl) + '" title="Flugplanbild vergrößern">' +
                    '<img src="' + escHtml(flugplanUrl) + '" alt="Flugplan ' + escHtml(fieldDisplay(inst.fid)) + '">' +
                    '</a>'
                )
                : '<span style="opacity:.7;">Flugplan: -</span>';
            const titleStartIconHtml = (meta.google && meta.startIcon)
                ? ('<a href="' + escHtml(meta.google) + '" title="Startpunkt File ' + escHtml(normalizeFieldId(inst.fid)) + ' auf GoogleMap" target="_google"><img src="' + escHtml(meta.startIcon) + '" height="22"></a> ')
                : '';

            const ngtIcons = ngtIconsHtmlForField(inst.fid);

            const blockHtml =
                '<div class="field generated-status" data-generated-field="' + escHtml(inst.fid) + '" data-generated-key="' + escHtml(key) + '" data-generated-time="' + escHtml(inst.time) + '" data-generated-seq="' + escHtml(String(inst.seq)) + '">' +
                    '<label class="field-label">' + titleStartIconHtml + escHtml(statusTitle) + ngtIcons + '</label>' +
                    '<div class="generated-layout">' +
                        '<div class="generated-layout-left">' +
                            '<div class="generated-group-wrap"><div>' + flugplanHtml + bauerUnderPlan + '</div></div>' +
                        '</div>' +
                        '<div class="generated-layout-right">' +
                            '<div class="generated-group-wrap">' +
                                '<div class="generated-group-title">Status (bis geflogen)</div>' +
                                '<div class="option-grid generated-status-grid">' + statusOptionsHtml + '</div>' +
                            '</div>' +
                            '<div class="generated-group-wrap">' +
                                '<div class="generated-group-title">Funde</div>' +
                                '<div class="option-grid generated-status-grid">' + fundOptionsHtml + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="field generated-status-other" data-generated-other-wrap="' + escHtml(key) + '" style="display:none;">' +
                    '<label class="field-label">' + escHtml(otherTitle) + ngtIcons + '</label>' +
                    '<input type="text" data-generated-other-for="' + escHtml(key) + '" name="' + escHtml(inputName(otherLbl, false)) + '" value="' + escHtml(otherVal) + '">' +
                '</div>';

            host.insertAdjacentHTML('beforeend', blockHtml);
            bindRow(key);
        });
        updateTagesflugplanMap();
        if (READONLY_MODE) {
            lockGeneratedRowsReadonly();
        } else {
            updateFundeFromStatuses();
            syncDynamicStateToHidden();
        }
    }

    function ensureImageModal(){
        let modal = document.getElementById('generated-image-modal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'generated-image-modal';
        modal.className = 'generated-image-modal';
        modal.innerHTML = '<div class="generated-image-modal-content"><img alt="Flugplan gross"></div>';
        modal.addEventListener('click', function(e){
            if (e.target === modal) modal.classList.remove('open');
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') modal.classList.remove('open');
        });
        document.body.appendChild(modal);
        return modal;
    }

    host.addEventListener('click', function(e){
        const a = e.target && e.target.closest ? e.target.closest('a[data-generated-plan-full]') : null;
        if (!a) return;
        e.preventDefault();
        const src = String(a.getAttribute('data-generated-plan-full') || '').trim();
        if (!src) return;
        const modal = ensureImageModal();
        const img = modal.querySelector('img');
        if (img) img.src = src;
        modal.classList.add('open');
    });

    function onFeldListChanged(){
        buildRows();
        if (typeof window.rkrkChecklistScheduleSave === 'function') {
            window.rkrkChecklistScheduleSave(400);
        }
    }

    if (!READONLY_MODE) {
        feldInput.addEventListener('input', onFeldListChanged);
        feldInput.addEventListener('change', onFeldListChanged);
        const ftmInput = document.getElementById('field_time_map');
        if (ftmInput) {
            ftmInput.addEventListener('input', onFeldListChanged);
            ftmInput.addEventListener('change', onFeldListChanged);
        }
        window.rkrkChecklistUpdateFlugbuchSummary = function(summary, year){
            const box = document.getElementById('rkrkFlightsCsvSummary');
            if (!box || !summary || typeof summary !== 'object') return;
            const keys = Object.keys(summary);
            let html = '';
            if (!keys.length) {
                html = '<p class="rkrk-flugbuch-empty" style="margin:0;color:#64748b;font-size:13px;">Keine Flugbuch-Einträge für Tag/Pilot in der CSV.</p>';
            } else {
                html = '<ul class="rkrk-flugbuch-list" style="margin:0;padding:0 0 0 18px;">';
                keys.forEach(function(fid){
                    const row = summary[fid] || {};
                    const g = String(row.Geflogen || '').trim();
                    const f = String(row.Fund || '').trim();
                    const gLabel = (g === 'Ja') ? 'geflogen' : ((g === 'Nein') ? 'nicht geflogen' : '–');
                    const fLabel = f !== '' ? f : '0';
                    const disp = String(row.Feld || fid);
                    html += '<li><code>' + disp.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</code>: Geflogen ' + gLabel + ', Fund ' + fLabel + '</li>';
                });
                html += '</ul>';
            }
            box.innerHTML = html;
            const toggle = document.getElementById('rkrkFlugbuchToggle');
            if (toggle) {
                const y = year || (document.getElementById('rkrkFlightsCsvYear') || {}).value || '';
                const mdbEl = document.getElementById('rkrkChecklistMdb');
                const countHint = keys.length > 0 ? (' · ' + keys.length + ' Feld' + (keys.length === 1 ? '' : 'er')) : '';
                let title = '<strong>Flugbuch</strong> (' + (y ? (y + '_RKRKFluege.csv') : 'RKRKFluege.csv') + ')';
                if (mdbEl && mdbEl.value) title += ' · Mission ' + mdbEl.value;
                title += countHint;
                const labelSpan = toggle.querySelector('span');
                if (labelSpan) labelSpan.innerHTML = title;
            }
        };
        window.rkrkChecklistSyncBeforeSave = function(){
            updateFundeFromStatuses();
            syncDynamicStateToHidden();
        };
    }
    buildRows();
})();
</script>
HTML;

    if (!$readonly && !$isGuest) {
        echo <<<'HTML'
<script>
(function(){
  function initAutosave(){
    const form = document.getElementById('rkrkChecklistForm');
    if (!form || form.getAttribute('data-autosave') !== '1') return;

    const statusEl = document.getElementById('rkrkChecklistSaveStatus');
    const saveUrlObj = new URL('rkrk_checklist.php', window.location.href);
    saveUrlObj.searchParams.set('mode', 'save');
    saveUrlObj.searchParams.set('ajax', '1');
    const mdbElInit = document.getElementById('rkrkChecklistMdb');
    const mdb = (mdbElInit && mdbElInit.value)
      ? mdbElInit.value
      : new URL(window.location.href).searchParams.get('MDB');
    if (mdb) saveUrlObj.searchParams.set('MDB', mdb);
    const saveUrl = saveUrlObj.href;
    let timer = null;
    let saving = false;
    let pending = false;
    const DEBOUNCE_MS = 900;

    function setStatus(text, kind){
      if (!statusEl) return;
      statusEl.textContent = text;
      statusEl.style.color = (kind === 'err') ? '#b91c1c' : ((kind === 'ok') ? '#166534' : '#556');
    }

    async function doSave(){
      if (saving) {
        pending = true;
        return;
      }
      saving = true;
      pending = false;
      setStatus('Speichert…', 'wait');
      let raw = '';
      try {
        if (typeof window.rkrkChecklistSyncBeforeSave === 'function') {
          window.rkrkChecklistSyncBeforeSave();
        }
        const fd = new FormData(form);
        fd.set('ajax', '1');
        const dynEl = document.getElementById('dynamic_status_state');
        if (dynEl) fd.set('dynamic_status_state', dynEl.value || '');
        const ftmEl = document.getElementById('field_time_map');
        if (ftmEl) fd.set('field_time_map', ftmEl.value || '');
        const fcyEl = document.getElementById('rkrkFlightsCsvYear');
        if (fcyEl && fcyEl.value) fd.set('flights_csv_year', fcyEl.value);
        const mdbEl = document.getElementById('rkrkChecklistMdb');
        const mdbParam = new URL(window.location.href).searchParams.get('MDB');
        if (mdbEl && mdbEl.value) fd.set('MDB', mdbEl.value);
        else if (mdbParam) fd.set('MDB', mdbParam);
        const wjEl = document.getElementById('weather_json');
        if (wjEl) fd.set('weather_json', wjEl.value || '');
        const editInp = form.querySelector('input[name="edit"]');
        if (editInp && editInp.value) {
          fd.set('edit', editInp.value);
        }
        const resp = await fetch(saveUrl, { method: 'POST', body: fd, credentials: 'same-origin', redirect: 'manual' });
        if (resp.type === 'opaqueredirect' || resp.status === 301 || resp.status === 302) {
          throw new Error('redirect_login');
        }
        raw = await resp.text();
        let data = null;
        try {
          data = raw ? JSON.parse(raw) : null;
        } catch (parseErr) {
          console.warn('Checklist autosave invalid JSON:', raw.slice(0, 300));
          if (/^\s*</.test(raw)) {
            throw new Error('html_response');
          }
          throw new Error('invalid_json');
        }
        if (!resp.ok || !data || !data.ok) {
          let err = (data && data.error) ? data.error : 'save_failed';
          if (data && data.message) err += ': ' + data.message;
          if (data && data.hint) err += ' · ' + data.hint;
          if (data && data.folder) err += ' [' + data.folder + ']';
          throw new Error(err);
        }
        if (data.id) {
          let editField = form.querySelector('input[name="edit"]');
          if (!editField) {
            editField = document.createElement('input');
            editField.type = 'hidden';
            editField.name = 'edit';
            editField.id = 'rkrkChecklistEditId';
            form.appendChild(editField);
          }
          editField.value = data.id;
          const fileInp = form.querySelector('input[name="file"]');
          const f = fileInp ? fileInp.value : '';
          const newUrl = '?mode=form&file=' + encodeURIComponent(f) + '&edit=' + encodeURIComponent(data.id);
          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', newUrl);
          }
        }
        let statusMsg = data.modified_at
          ? ('Zuletzt gespeichert: ' + data.modified_at)
          : 'Gespeichert';
        setStatus(statusMsg, 'ok');
        if (data.flights_csv && data.flights_csv.ok && data.flights_csv.updated > 0) {
          setStatus(
            statusMsg + ' · ' + (data.flights_csv.message || (data.flights_csv.updated + ' Flugzeile(n) im Flugbuch')),
            'ok'
          );
          if (data.flights_csv.flights_summary && typeof window.rkrkChecklistUpdateFlugbuchSummary === 'function') {
            window.rkrkChecklistUpdateFlugbuchSummary(data.flights_csv.flights_summary, data.flights_csv.year || '');
          }
        } else if (data.flights_csv && !data.flights_csv.ok) {
          let flMsg = data.flights_csv.message || 'Flugbuch nicht aktualisiert';
          if (data.flights_csv.day) flMsg += ' (' + data.flights_csv.day;
          if (data.flights_csv.pilot) flMsg += ', ' + data.flights_csv.pilot;
          if (data.flights_csv.fields && data.flights_csv.fields.length) flMsg += ', Felder: ' + data.flights_csv.fields.join(', ');
          if (data.flights_csv.day) flMsg += ')';
          setStatus(statusMsg + ' · Flugbuch: ' + flMsg, 'err');
        }
      } catch (e) {
        const code = (e && e.message) ? e.message : 'unknown';
        if (raw) {
          console.warn('Checklist autosave failed:', code, raw.slice(0, 300));
        } else {
          console.warn('Checklist autosave failed:', code);
        }
        setStatus('Speichern fehlgeschlagen (' + code + ')', 'err');
      } finally {
        saving = false;
        if (pending) scheduleSave(300);
      }
    }

    function scheduleSave(ms){
      clearTimeout(timer);
      timer = setTimeout(doSave, (ms == null) ? DEBOUNCE_MS : ms);
    }

    window.rkrkChecklistScheduleSave = scheduleSave;
    window.rkrkChecklistSaveNow = function(){
      clearTimeout(timer);
      return doSave();
    };

    form.addEventListener('submit', function(e){
      e.preventDefault();
      doSave();
    });
    form.addEventListener('input', function(){ scheduleSave(); }, true);
    form.addEventListener('change', function(){ scheduleSave(); }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutosave);
  } else {
    initAutosave();
  }
})();
</script>
HTML;
    }

    pageFooterLocal();
}

/* -----------------------------------------------------------
   CHECKLISTE → FLUG-CSV (Fund, Geflogen, Zeiten)
----------------------------------------------------------- */
function rkrk_checklist_save_flights_to_csv(array $fields){
    if (!function_exists('rkrk_checklist_sync_mission_context') && !function_exists('SetMissionLocation')) {
        return array('ok' => false, 'updated' => 0, 'matched' => 0, 'message' => 'Mission/Ort nicht verfügbar.');
    }
    if (function_exists('rkrk_checklist_sync_mission_context')) {
        rkrk_checklist_sync_mission_context();
    } else {
        SetMissionLocation();
    }
    if (!function_exists('rkrk_checklist_store_fields_to_flights_csv')) {
        return array('ok' => false, 'updated' => 0, 'matched' => 0, 'message' => 'Flug-CSV-Helfer nicht geladen.');
    }
    return rkrk_checklist_store_fields_to_flights_csv($fields);
}

/* -----------------------------------------------------------
   CHECKLISTE SPEICHERN
----------------------------------------------------------- */
function rkrk_checklist_preserve_fields_from_existing(array &$fields, array $existingFields) {
	if (empty($existingFields)) {
		return;
	}
	if (!isset($fields['Allgemein']) || !is_array($fields['Allgemein'])) {
		$fields['Allgemein'] = isset($existingFields['Allgemein']) && is_array($existingFields['Allgemein'])
			? $existingFields['Allgemein']
			: array();
	} elseif (isset($existingFields['Allgemein']) && is_array($existingFields['Allgemein'])) {
		foreach (array('Datum & Zeit', 'Pilot', 'Feld#', 'Flugort', 'Auftraggeber', 'Art des Fluges') as $label) {
			$cur = trim((string)($fields['Allgemein'][$label] ?? ''));
			$old = trim((string)($existingFields['Allgemein'][$label] ?? ''));
			if ($cur === '' && $old !== '') {
				$fields['Allgemein'][$label] = $existingFields['Allgemein'][$label];
			}
		}
	}
	$ftmPost = trim((string)($_POST['field_time_map'] ?? ''));
	if ($ftmPost === '' && isset($existingFields['Suchresultat']['Zeit zum Feld'])) {
		$oldFtm = trim((string)$existingFields['Suchresultat']['Zeit zum Feld']);
		if ($oldFtm !== '') {
			if (!isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) {
				$fields['Suchresultat'] = array();
			}
			$fields['Suchresultat']['Zeit zum Feld'] = $oldFtm;
		}
	}
}

function rkrk_checklist_perform_save(){
    global $JSON_FOLDER, $TEMPLATE_FOLDER;

    $file = basename($_POST['file'] ?? "");
    if ($file === "") {
        return ['ok' => false, 'error' => 'missing_file'];
    }

    $fields = $_POST['fields'] ?? [];
    if (!is_array($fields)) {
        $fields = [];
    }
    rkrk_checklist_refresh_json_folder();

    $editId = $_POST['edit'] ?? null;
    rkrk_migrate_suchresultat_legacy_labels($fields, (string)($fields["Allgemein"]["Feld#"] ?? ""), trim((string)($_POST['field_time_map'] ?? '')));
    rkrk_checklist_clear_dynamic_suchresultat_keys($fields);
    $postedFieldTimeMap = trim((string)($_POST['field_time_map'] ?? ''));
    if ($postedFieldTimeMap !== '') {
        if (!isset($fields['Suchresultat']) || !is_array($fields['Suchresultat'])) {
            $fields['Suchresultat'] = [];
        }
        $fields['Suchresultat']['Zeit zum Feld'] = $postedFieldTimeMap;
    }

    if (!empty($_POST['dynamic_status_state'])) {
        rkrk_checklist_merge_dynamic_status_into_fields($fields, (string)$_POST['dynamic_status_state']);
    }
    rkrk_checklist_prune_suchresultat_to_feld_list($fields);

	if (!empty($_POST['weather_json'])) {
		rkrk_merge_post_weather_json_into_fields($fields, (string)$_POST['weather_json']);
	}

    if (isset($_POST['new_fields']) && is_array($_POST['new_fields'])) {
        foreach ($_POST['new_fields'] as $secName => $items) {
            if (!is_array($items) || !isset($items['label']) || !is_array($items['label'])) continue;
            if (!isset($fields[$secName])) {
                $fields[$secName] = [];
            }
            foreach (array_keys($items['label']) as $i) {
                $label = trim($items['label'][$i]);
                if ($label === "") continue;
                $type    = trim($items['type'][$i] ?? "text");
                $options = trim($items['options'][$i] ?? "");
                $value   = trim($items['value'][$i] ?? "");
                if ($type === "radio" && $options !== "") {
                    $type = "radio:" . $options;
                }
                if ($type === "multi" && $options !== "") {
                    $type = "multi:" . $options;
                }
                $fields[$secName][$label] = $value;
            }
        }
    }

    $id = rkrk_checklist_resolve_save_id($file, $editId, $fields);
    $path = "$JSON_FOLDER/$id.json";
    $existing = file_exists($path) ? json_decode((string)file_get_contents($path), true) : array();
    if (!is_array($existing)) {
        $existing = array();
    }
    if (!empty($existing['fields']) && is_array($existing['fields'])) {
        rkrk_checklist_preserve_fields_from_existing($fields, $existing['fields']);
    }
    $createdAt = $existing['created_at'] ?? date('Y-m-d H:i:s');

    $modifiedAt = date('Y-m-d H:i:s');
    $data = [
        'template'    => $file,
        'fields'      => $fields,
        'created_at'  => $createdAt,
        'modified_at' => $modifiedAt
    ];

    $jsonPayload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonPayload === false) {
        return ['ok' => false, 'error' => 'encode_failed'];
    }
    if (!is_dir($JSON_FOLDER)) {
        @mkdir($JSON_FOLDER, 0777, true);
    }
    $written = @file_put_contents($path, $jsonPayload, LOCK_EX);
    if ($written === false) {
        $written = @file_put_contents($path, $jsonPayload);
    }
    if ($written === false) {
        return [
            'ok' => false,
            'error' => 'write_failed',
            'id' => $id,
            'hint' => (is_dir($JSON_FOLDER) && is_writable($JSON_FOLDER)) ? 'file_lock_or_path' : ('folder_not_writable:' . basename($JSON_FOLDER)),
            'folder' => basename($JSON_FOLDER),
        ];
    }

    $flightsCsv = array('ok' => false, 'updated' => 0, 'matched' => 0, 'message' => 'Übersprungen.');
    try {
        $flightsCsv = rkrk_checklist_save_flights_to_csv($fields);
    } catch (Throwable $e) {
        $flightsCsv = array(
            'ok' => false,
            'updated' => 0,
            'matched' => 0,
            'message' => 'Flug-CSV: ' . $e->getMessage(),
        );
    }

    return [
        'ok' => true,
        'id' => $id,
        'file' => $file,
        'modified_at' => $modifiedAt,
        'created_at' => $createdAt,
        'flights_csv' => $flightsCsv,
    ];
}

function saveChecklist(){
    $ajax = (($_POST['ajax'] ?? '') === '1') || (($_GET['ajax'] ?? '') === '1');
    if ($ajax) {
        @ini_set('display_errors', '0');
        while (ob_get_level() > 0) { @ob_end_clean(); }
    }
    try {
        $result = rkrk_checklist_perform_save();
    } catch (Throwable $e) {
        $result = ['ok' => false, 'error' => 'server_error', 'message' => $e->getMessage()];
    }
    if ($ajax) {
        $code = empty($result['ok']) ? 400 : 200;
        rkrk_checklist_json_response($result, $code);
    }

    if (empty($result['ok'])) {
        redirectTo("?mode=list");
        return;
    }

    redirectTo("?mode=list&saved=1");
}


/* -----------------------------------------------------------
   CHECKLISTE LÖSCHEN
----------------------------------------------------------- */
function deleteJsonChecklist(){
    global $JSON_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge löschen.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    $path = "$JSON_FOLDER/$id.json";

    if (file_exists($path)) unlink($path);

    redirectTo("?mode=list&deleted=1");
}

function copyJsonChecklist(){
    global $JSON_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
    $isGuest=false;
    if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge kopieren.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    if ($id === "") {
        redirectTo("?mode=list");
    }

    $src = "$JSON_FOLDER/$id.json";
    if (!file_exists($src)) {
        redirectTo("?mode=list");
    }

    $data = json_decode(file_get_contents($src), true);
    if (!is_array($data)) {
        redirectTo("?mode=list");
    }

    $base = $id . "_copy_" . date('Ymd_His');
    $newId = $base;
    $dst = "$JSON_FOLDER/$newId.json";
    $i = 2;
    while (file_exists($dst)) {
        $newId = $base . "_" . $i;
        $dst = "$JSON_FOLDER/$newId.json";
        $i++;
    }

    $now = date('Y-m-d H:i:s');
    $data['created_at'] = $now;
    $data['modified_at'] = $now;

    file_put_contents($dst, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $tplName = basename((string)($data['template'] ?? ''));
    if ($tplName === '') {
        redirectTo("?mode=list&saved=1");
    }
    redirectTo("?mode=form&file=" . urlencode($tplName) . "&edit=" . urlencode($newId));
}

/* -----------------------------------------------------------
 TEMPLATE FIELDS API (für LinkBuilder Dropdown)
----------------------------------------------------------- */
function templateFieldsApi(){
    global $TEMPLATE_FOLDER;

    // Falls vorher HTML kam: weg damit
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    $file = basename($_GET['file'] ?? '');
    if ($file === '') {
        echo json_encode(['ok'=>false,'error'=>'missing file']);
        exit;
    }

    $tpl = loadTemplate($file);
    if (!$tpl || !isset($tpl['sections'])) {
        echo json_encode(['ok'=>false,'error'=>'invalid template']);
        exit;
    }

    $out = [];
    foreach ($tpl['sections'] as $sec => $fields) {
        if (is_array($fields)) {
            $out[$sec] = array_keys($fields);
        }
    }

    echo json_encode(['ok'=>true,'sections'=>$out], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -----------------------------------------------------------
 LINK BUILDER (Checklisten-URL Generator)
----------------------------------------------------------- */
function showLinkBuilder(){
    global $TEMPLATE_FOLDER;

    pageHeaderLocal("Checklisten‑URL Generator");

    // Basis-URL (rkrk_checklist.php)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $selfUrl = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    /* -------------------------------------------------------
       SERVERSEITIG: Template -> Sections -> Labels Map bauen
       (ohne fetch/API, sofort verfügbar)
    ------------------------------------------------------- */
    $fieldMap = [];
    foreach (glob($TEMPLATE_FOLDER . "/*.json") as $tplFile){
        $base = basename($tplFile);
        $tpl  = loadTemplate($base);
        $sections = isset($tpl["sections"]) ? $tpl["sections"] : [];

        $out = [];
        if (is_array($sections)){
            foreach ($sections as $secName => $fields){
                if (!is_array($fields)) continue;
                $out[$secName] = array_keys($fields);
            }
        }
        $fieldMap[$base] = $out;
    }

    // Sicher für JS einbetten
    $fieldMapJson = json_encode(
        $fieldMap,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    $selfUrlJson = json_encode(
        $selfUrl,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    // Template-Optionen bauen
    $tplOptions = "";
    foreach (glob($TEMPLATE_FOLDER . "/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = isset($tpl['title']) ? $tpl['title'] : $base;
        $tplOptions .= "<option value=\"" . safe($base) . "\">" . safe($title) . "</option>\n";
    }

    echo <<<HTML
<div class='rkrk-card'>
  <h3>🔗 Checklisten‑URL erzeugen</h3>
  <p style='color:#555; margin-top:6px;'>
    Wähle ein Template, dann ein Feld aus der Liste, trage den Wert ein – und erzeuge eine Preset‑URL.
  </p>

  <div class='field'>
    <label class='field-label'>Template</label>
    <select id='lb_template' class='text-input' style='width:100%'>
      {$tplOptions}
    </select>
  </div>

  <hr style='margin:18px 0'>

  <h4>📌 Vorbelegte Felder (aus Template wählbar)</h4>

  <div id='lb_params' style='display:flex; flex-direction:column; gap:10px;'>
    <div class='lb_row' style='display:grid; grid-template-columns: 1fr 1fr auto; gap:10px;'>
      <select class='text-input lb_field' style='width:100%'>
        <option value=''>— Feld wählen —</option>
      </select>
      <input type='text' class='text-input lb_value' placeholder='Wert'>
      <button type='button' class='btn' onclick='lb_remove(this)'>✖</button>
    </div>
  </div>

  <div style='margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;'>
    <button type='button' class='btn' onclick='lb_add()'>➕ Feld hinzufügen</button>
    <button type='button' class='btn btn-primary' onclick='lb_generate()'>✅ URL erzeugen</button>
  </div>

  <hr style='margin:18px 0'>

  <h4>⚙️ Optionen</h4>
  <label>
    <input type='checkbox' id='lb_readonly'> Readonly (nur Lesen)
  </label>

  <hr style='margin:18px 0'>

  <h4>🔗 Ergebnis</h4>
  <input type='text' id='lb_result' class='text-input' readonly style='width:100%;'>
  <div style='margin-top:10px; display:flex; gap:10px;'>
    <button type='button' class='btn' onclick='lb_copy()'>📋 Kopieren</button>
  </div>
</div>

<div style='text-align:center;margin-top:20px;'>
  <a href='?mode=listall' class='btn'>⬅ Zurück</a>
</div>

<script>
var LB_SELF = {$selfUrlJson};
var LB_FIELD_MAP = {$fieldMapJson};
var LB_FIELDS = {};

function lb_getCurrentFields(){
  var tplEl = document.getElementById('lb_template');
  var tpl = tplEl ? tplEl.value : '';
  LB_FIELDS = (LB_FIELD_MAP && LB_FIELD_MAP[tpl]) ? LB_FIELD_MAP[tpl] : {};
}

function lb_fillSelect(sel){
  var current = sel.value;
  sel.innerHTML = "<option value=''>— Feld wählen —</option>";

  // Optgroups nach Sektion (ES5 kompatibel)
  for (var sec in LB_FIELDS){
    if (!LB_FIELDS.hasOwnProperty(sec)) continue;
    var labels = LB_FIELDS[sec] || [];
    if (!labels.length) continue;

    var og = document.createElement('optgroup');
    og.label = sec;

    for (var i=0; i<labels.length; i++){
      var label = labels[i];
      var opt = document.createElement('option');
      opt.value = label;
      opt.textContent = label;
      og.appendChild(opt);
    }
    sel.appendChild(og);
  }

  if (current) sel.value = current;
}

function lb_fillAllSelects(){
  var sels = document.querySelectorAll('.lb_field');
  for (var i=0; i<sels.length; i++){
    lb_fillSelect(sels[i]);
  }
}

function lb_reloadFieldsNow(){
  lb_getCurrentFields();
  lb_fillAllSelects();
}

function lb_add(){
  var wrap = document.getElementById('lb_params');
  var row = document.createElement('div');
  row.className = 'lb_row';
  row.style.display = 'grid';
  row.style.gridTemplateColumns = '1fr 1fr auto';
  row.style.gap = '10px';

  row.innerHTML =
    "<select class='text-input lb_field' style='width:100%'><option value=''>— Feld wählen —</option></select>" +
    "<input type='text' class='text-input lb_value' placeholder='Wert'>" +
    "<button type='button' class='btn' onclick='lb_remove(this)'>✖</button>";

  wrap.appendChild(row);

  // neue Select sofort befüllen
  lb_getCurrentFields();
  lb_fillSelect(row.querySelector('.lb_field'));
}

function lb_remove(btn){
  var rows = document.querySelectorAll('.lb_row');
  if (rows.length <= 1){
    // mind. eine Zeile behalten
    var inputs = rows[0].querySelectorAll('input');
    for (var i=0; i<inputs.length; i++) inputs[i].value = '';
    var selects = rows[0].querySelectorAll('select');
    for (var j=0; j<selects.length; j++) selects[j].value = '';
    return;
  }
  btn.parentNode.remove();
}

function lb_generate(){
    var tpl = document.getElementById('lb_template').value;

    var u = LB_SELF + '?mode=form&file=' + encodeURIComponent(tpl);

    document.querySelectorAll('.lb_row').forEach(function(row){
        var field = (row.querySelector('.lb_field') ? row.querySelector('.lb_field').value : '').trim();
        var val   = (row.querySelector('.lb_value') ? row.querySelector('.lb_value').value : '').trim();

        if (field !== ''){
            u += '&' + encodeURIComponent(field) + '=' + encodeURIComponent(val);
        }
    });

    if (document.getElementById('lb_readonly') && document.getElementById('lb_readonly').checked){
        u += '&readonly=1';
    }

    // ✅ WICHTIG: HIER KEIN safe(), KEIN innerHTML
    document.getElementById('lb_result').value = u;
}


function lb_copy(){
    var inp = document.getElementById('lb_result');
    var val = inp.value || '';

    // Falls doch irgendwo HTML-escaped
    val = val.replace(/&amp;/g, '&');

    if (navigator.clipboard && navigator.clipboard.writeText){
        navigator.clipboard.writeText(val);
    } else {
        inp.value = val;
        inp.focus();
        inp.select();
        document.execCommand('copy');
    }
}

// Trigger: Templatewechsel => sofort Felder neu laden
var tplSel = document.getElementById('lb_template');
if (tplSel){
  tplSel.addEventListener('change', lb_reloadFieldsNow);
  tplSel.addEventListener('input', lb_reloadFieldsNow);
}

// Initial füllen
lb_reloadFieldsNow();
</script>
HTML;

    pageFooterLocal();
}



/* -----------------------------------------------------------
   ROUTER / DISPATCH
----------------------------------------------------------- */
if ($isAjaxSaveRequest) {
    saveChecklist();
}

switch ($mode) {

	case 'weather':
		handleWeatherMode();
		exit;

	case 'saveweatherclient':
		handleSaveWeatherClientMode();
		exit;

	case 'backfillweatherrecent':
		handleBackfillWeatherRecentMode();
		exit;

    case 'list':
        showChecklistList();
        break;

    case 'listall':
        showChecklistList(true);
        break;

    case 'form':
        showChecklistForm();
        break;

    case 'save':
        saveChecklist();
        break;

    case 'deletejson':
        deleteJsonChecklist();
        break;

    case 'copyjson':
        copyJsonChecklist();
        break;

    case 'templates':
        showTemplateManager();
        break;

    case 'createtemplate':
        createTemplate();
        break;

    case 'edittemplate':
        editTemplate();
        break;

    case 'savetemplate':
        saveTemplate();
        break;

    case 'deletetemplate':
        deleteTemplate();
        break;

    case 'copytemplate':
        copyTemplate();
        break;

    case 'templateprint':
        showTemplatePrintPreview();
        break;

    case 'templateexport':
        handleTemplateJsonExport();
        break;

    case 'templateimport':
        handleTemplateJsonImport();
        break;

	case 'templatefields':
		templateFieldsApi();
		break;

	case 'linkbuilder':
		showLinkBuilder();
		break;

	case 'print':
		showChecklistPrint();
		break;

	case 'exportjson':
		if (!function_exists('rkrk_checklist_toolbar_handle_export_json')) {
			http_response_code(500);
			header("Content-Type: text/plain; charset=utf-8");
			echo "Toolbar-Include fehlt (assess/rkrk_checklist_toolbar_inc.php).";
			exit;
		}
		rkrk_checklist_toolbar_handle_export_json($JSON_FOLDER);
		exit;

	case 'importjson':
		if (!function_exists('rkrk_checklist_toolbar_handle_import_json')) {
			redirectTo("?mode=list");
			exit;
		}
		rkrk_checklist_toolbar_handle_import_json($JSON_FOLDER, static function (string $u): void {
			redirectTo($u);
		});
		exit;

    default:
        showChecklistList();
        break;

}

if (!$isApiMode) {
?>
<script>
(function(){
  function isVisible(el){
    return !!(el && (el.offsetParent !== null));
  }

  // Nur echte Eingabefelder (keine Buttons), damit "letztes Feld" wirklich das letzte Feld ist
  function getFields(form){
    const sel = 'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])';
    return Array.from(form.querySelectorAll(sel)).filter(isVisible);
  }

  document.addEventListener('keydown', function(e){
    if (e.key !== 'Enter') return;

    const t = e.target;
    if (!t || !t.matches('input, select, textarea')) return;

    const form = t.form;
    if (!form) return; // nur innerhalb eines Formulars

    const fields = getFields(form);
    const idx = fields.indexOf(t);
    const isLast = (idx >= 0 && idx === fields.length - 1);

    // Ctrl+Enter: im letzten Feld sofort speichern (Auto-Save)
    if (e.ctrlKey){
      if (isLast){
        e.preventDefault();
        if (typeof window.rkrkChecklistSaveNow === 'function') {
          window.rkrkChecklistSaveNow();
        }
      }
      return; // in allen anderen Feldern: nichts tun
    }

    // Normales Enter: Textarea erlaubt Zeilenumbruch
    if (t.tagName === 'TEXTAREA') return;

    // Normales Enter: NICHT submitten, sondern zum nächsten Feld
    e.preventDefault();

    if (idx >= 0 && idx + 1 < fields.length){
      fields[idx + 1].focus();
    }
  }, true);
})();
</script>

</body>
</html>
<?php
}