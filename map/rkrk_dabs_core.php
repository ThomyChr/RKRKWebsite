<?php
/**
 * DABS scrape/parse without site chrome (rkrk_gen / access control).
 * Used by the site API and by standalone map/ / mapII/.
 */
declare(strict_types=1);

function rkrk_dabs_emit(): void
{
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$day = strtolower(trim((string)($_GET['day'] ?? 'today')));
	if ($day !== 'tomorrow') {
		$day = 'today';
	}
	echo json_encode(rkrk_dabs_load($day), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function rkrk_dabs_fail($message)
{
	return array(
		'ok' => false,
		'error' => $message,
		'features' => array(),
		'meta' => array(),
	);
}

function rkrk_dabs_load($day)
{
	$cacheTtl = 20 * 60;
	$cacheDir = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR;
	$cacheFile = $cacheDir . 'rkrk_dabs_' . $day . '_agl120_poly2.json';
	if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < $cacheTtl) {
		$cached = json_decode((string)file_get_contents($cacheFile), true);
		if (is_array($cached) && !empty($cached['ok'])) {
			$cached['meta']['cached'] = true;
			return $cached;
		}
	}

	$url = ($day === 'tomorrow')
		? 'https://www.skybriefing.com/o/dabs?tomorrow'
		: 'https://www.skybriefing.com/o/dabs?today';
	$pdf = rkrk_dabs_http_get($url, $httpCode, $err);
	if ($pdf === false || $pdf === '') {
		return rkrk_dabs_fail($err !== '' ? $err : 'DABS konnte nicht geladen werden (HTTP ' . $httpCode . ')');
	}
	if (stripos($pdf, '%PDF') !== 0) {
		$plain = trim(preg_replace('/\s+/', ' ', strip_tags($pdf)));
		if (stripos($plain, 'not available') !== false || stripos($plain, 'nicht verfügbar') !== false || stripos($plain, 'nicht verfuegbar') !== false) {
			return array(
				'ok' => true,
				'available' => false,
				'features' => array(),
				'meta' => array(
					'day' => $day,
					'source' => $url,
					'message' => 'DABS für dieses Datum ist noch nicht veröffentlicht.',
				),
			);
		}
		return rkrk_dabs_fail('Unerwartete DABS-Antwort (kein PDF)');
	}

	$strings = rkrk_dabs_pdf_strings($pdf);
	$joined = implode("\n", $strings);
	if (stripos($joined, 'not available') !== false && stripos($joined, 'NOTAM Item E') === false) {
		return array(
			'ok' => true,
			'available' => false,
			'features' => array(),
			'meta' => array(
				'day' => $day,
				'source' => $url,
				'message' => 'DABS für dieses Datum ist noch nicht veröffentlicht.',
			),
		);
	}

	$maxAgl = 120;
	$items = rkrk_dabs_parse_items($strings);
	$features = array();
	$skipped = 0;
	foreach ($items as $item) {
		if (!isset($item['lat'], $item['lon'], $item['radius_m'])) {
			continue;
		}
		if (!rkrk_dabs_affects_agl($item, $maxAgl)) {
			$skipped++;
			continue;
		}
		$item['agl_filter_m'] = $maxAgl;
		$rr = rkrk_dabs_remark_radius_m((string)($item['remark'] ?? ''));
		if ($rr) {
			$item['radius_m'] = $rr;
		}
		$geom = rkrk_dabs_item_geometry($item);
		if (!$geom) {
			continue;
		}
		$item['shape'] = (isset($geom['type']) && strtolower($geom['type']) === 'polygon') ? 'polygon' : 'circle';
		$props = $item;
		unset($props['geometry']);
		$features[] = array(
			'type' => 'Feature',
			'geometry' => $geom,
			'properties' => $props,
		);
	}

	$polyN = 0;
	$circN = 0;
	foreach ($features as $f) {
		if (isset($f['properties']['shape']) && $f['properties']['shape'] === 'polygon') {
			$polyN++;
		} else {
			$circN++;
		}
	}
	$meta = array(
		'day' => $day,
		'source' => $url,
		'official' => 'https://www.skybriefing.com/de/dabs',
		'date' => rkrk_dabs_find_meta($strings, 'DABS Date:'),
		'version' => rkrk_dabs_find_meta($strings, 'Version'),
		'count' => count($features),
		'polygons' => $polyN,
		'circles' => $circN,
		'skipped_high' => $skipped,
		'agl_max_m' => $maxAgl,
		'disclaimer' => 'Nur Meldungen im Segment GND bis 120 m AGL. DABS ersetzt keine NOTAMs.',
	);
	$result = array(
		'ok' => true,
		'available' => true,
		'features' => $features,
		'meta' => $meta,
	);
	@file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	$result['meta']['cached'] = false;
	return $result;
}

function rkrk_dabs_http_get($url, &$httpCode, &$err)
{
	$httpCode = 0;
	$err = '';
	$errors = array();

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 25,
			CURLOPT_USERAGENT => 'RKRK-DABS/1.0',
			CURLOPT_HTTPHEADER => array('Accept: application/pdf,application/octet-stream,*/*'),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		));
		$raw = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($raw === false) {
			$errors[] = 'curl: ' . curl_error($ch);
		}
		curl_close($ch);
		if (is_string($raw) && $raw !== '' && $httpCode >= 200 && $httpCode < 400) {
			return $raw;
		}
		if (is_string($raw) && $raw !== '') {
			return $raw;
		}
	} else {
		$errors[] = 'curl_init missing';
	}

	$binRaw = rkrk_dabs_cli_curl($url, $cliCode, $cliErr);
	if (is_string($binRaw) && $binRaw !== '') {
		$httpCode = $cliCode > 0 ? $cliCode : 200;
		return $binRaw;
	}
	if ($cliErr !== '') {
		$errors[] = $cliErr;
	}

	$ctx = stream_context_create(array(
		'http' => array(
			'timeout' => 25,
			'header' => "User-Agent: RKRK-DABS/1.0\r\nAccept: application/pdf\r\n",
			'follow_location' => 1,
		),
		'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
	));
	$raw = @file_get_contents($url, false, $ctx);
	if (is_string($raw) && $raw !== '') {
		$httpCode = 200;
		return $raw;
	}
	$err = implode('; ', $errors);
	if ($err === '') {
		$err = 'file_get_contents failed';
	}
	return false;
}

function rkrk_dabs_cli_curl($url, &$httpCode, &$err)
{
	$httpCode = 0;
	$err = '';
	if (!function_exists('exec')) {
		$err = 'exec disabled';
		return false;
	}
	$tmp = tempnam(sys_get_temp_dir(), 'dabs');
	if ($tmp === false) {
		$err = 'tempnam failed';
		return false;
	}
	$cmd = 'curl -sS -L --max-time 25 -A ' . escapeshellarg('RKRK-DABS/1.0')
		. ' -o ' . escapeshellarg($tmp)
		. ' -w ' . escapeshellarg('%{http_code}')
		. ' ' . escapeshellarg($url) . ' 2>&1';
	$out = array();
	$ret = 1;
	@exec($cmd, $out, $ret);
	$codeLine = trim(implode('', $out));
	if (preg_match('/(\d{3})\s*$/', $codeLine, $m)) {
		$httpCode = (int)$m[1];
	}
	$raw = is_file($tmp) ? (string)file_get_contents($tmp) : '';
	@unlink($tmp);
	if ($ret !== 0 && $raw === '') {
		$err = 'cli curl failed: ' . $codeLine;
		return false;
	}
	return $raw !== '' ? $raw : false;
}

function rkrk_dabs_pdf_strings($pdf)
{
	$out = array();
	if (!preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $matches)) {
		return $out;
	}
	foreach ($matches[1] as $stream) {
		$stream = ltrim($stream, "\r\n");
		$decoded = @gzuncompress($stream);
		if ($decoded === false) {
			$decoded = @gzinflate($stream);
		}
		if ($decoded === false) {
			$decoded = $stream;
		}
		if (!preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/', $decoded, $parts)) {
			continue;
		}
		foreach ($parts[0] as $raw) {
			$inner = substr($raw, 1, -1);
			$inner = str_replace(array('\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'), array("\n", "\r", "\t", '(', ')', '\\'), $inner);
			$inner = trim($inner);
			if ($inner !== '') {
				$out[] = $inner;
			}
		}
	}
	return $out;
}

function rkrk_dabs_find_meta($strings, $prefix)
{
	$prefix = strtolower($prefix);
	foreach ($strings as $i => $s) {
		if (stripos($s, $prefix) === 0) {
			$rest = trim(substr($s, strlen($prefix)));
			if ($rest !== '') {
				return $rest;
			}
			if (isset($strings[$i + 1])) {
				return trim((string)$strings[$i + 1]);
			}
		}
	}
	return '';
}

function rkrk_dabs_is_id($s)
{
	return (bool)preg_match('/^(LSR\d+[A-Z]?|W\d{4}\/\d{2})$/i', $s);
}

function rkrk_dabs_is_validity($s)
{
	return (bool)preg_match('/^\d{4}\s*-\s*\d{4}$/', $s);
}

function rkrk_dabs_is_center($s)
{
	return (bool)preg_match('/^\d{6}[NS]\s+\d{7}[EW]$/i', $s);
}

function rkrk_dabs_is_radius($s)
{
	return (bool)preg_match('/^[\d.]+\s*KM\s*\/\s*[\d.]+\s*NM$/i', $s);
}

function rkrk_dabs_is_noise($s)
{
	$t = strtolower($s);
	if ($s === '!' || $s === 'Page' || $s === 'a MICAMS product') {
		return true;
	}
	if (preg_match('/^page\s+\d+$/i', $s)) {
		return true;
	}
	if (strpos($t, 'skyguide') !== false && strlen($s) < 40) {
		return true;
	}
	return false;
}

function rkrk_dabs_parse_coord($s)
{
	if (!preg_match('/^(\d{2})(\d{2})(\d{2})([NS])\s*(\d{3})(\d{2})(\d{2})([EW])$/i', trim($s), $m)) {
		return null;
	}
	$lat = ((int)$m[1]) + ((int)$m[2]) / 60 + ((int)$m[3]) / 3600;
	if (strtoupper($m[4]) === 'S') {
		$lat = -$lat;
	}
	$lon = ((int)$m[5]) + ((int)$m[6]) / 60 + ((int)$m[7]) / 3600;
	if (strtoupper($m[8]) === 'W') {
		$lon = -$lon;
	}
	return array($lat, $lon);
}

function rkrk_dabs_parse_radius_m($s)
{
	if (!preg_match('/^([\d.]+)\s*KM/i', trim($s), $m)) {
		return null;
	}
	return (int)round(((float)$m[1]) * 1000);
}

function rkrk_dabs_remark_radius_m($remark)
{
	$u = strtoupper((string)$remark);
	if (preg_match('/RADIUS\s+([\d.]+)\s*KM/i', $u, $m)) {
		return (int)round(((float)$m[1]) * 1000);
	}
	if (preg_match('/RADIUS\s+([\d.]+)\s*NM/i', $u, $m)) {
		return (int)round(((float)$m[1]) * 1852);
	}
	return null;
}

function rkrk_dabs_remark_ring($remark)
{
	$u = (string)$remark;
	$u = preg_replace('/\(\s*\d{6}[NS]\s*\d{7}[EW]\s+RADIUS[^)]*\)/i', ' ', $u);
	$u = preg_replace('/CENTERED\s+ON\s+\d{6}[NS]\s*\d{7}[EW]/i', ' ', $u);
	if (!preg_match_all('/(\d{6}[NS])\s*(\d{7}[EW])/i', $u, $m, PREG_SET_ORDER)) {
		return null;
	}
	$pts = array();
	foreach ($m as $row) {
		$ll = rkrk_dabs_parse_coord($row[1] . $row[2]);
		if (!$ll) {
			continue;
		}
		$pts[] = array($ll[1], $ll[0]);
	}
	$uniq = array();
	foreach ($pts as $p) {
		$last = $uniq ? $uniq[count($uniq) - 1] : null;
		if ($last && abs($last[0] - $p[0]) < 0.00001 && abs($last[1] - $p[1]) < 0.00001) {
			continue;
		}
		$uniq[] = $p;
	}
	if (count($uniq) < 3) {
		return null;
	}
	$first = $uniq[0];
	$last = $uniq[count($uniq) - 1];
	if (abs($first[0] - $last[0]) > 0.00001 || abs($first[1] - $last[1]) > 0.00001) {
		$uniq[] = $first;
	}
	return $uniq;
}

function rkrk_dabs_item_geometry($item)
{
	$remark = (string)($item['remark'] ?? '');
	$ring = rkrk_dabs_remark_ring($remark);
	if (is_array($ring) && count($ring) >= 4) {
		return array(
			'type' => 'Polygon',
			'coordinates' => array($ring),
		);
	}
	$lon = isset($item['lon']) ? (float)$item['lon'] : null;
	$lat = isset($item['lat']) ? (float)$item['lat'] : null;
	$rm = rkrk_dabs_remark_radius_m($remark);
	if ($rm === null) {
		$rm = isset($item['radius_m']) ? (int)$item['radius_m'] : null;
	} else {
		$item['radius_m'] = $rm;
	}
	if ($lon === null || $lat === null || !$rm) {
		return null;
	}
	return array(
		'type' => 'Point',
		'coordinates' => array($lon, $lat),
	);
}

function rkrk_dabs_ft_to_m($ft)
{
	return ((float)$ft) * 0.3048;
}

function rkrk_dabs_lower_is_gnd($s)
{
	return (bool)preg_match('/\b(GND|SFC|GROUND|SURFACE)\b/i', (string)$s);
}

function rkrk_dabs_parse_fl($s)
{
	if (preg_match('/\bFL\s*(\d+)\b/i', (string)$s, $m)) {
		return (int)$m[1];
	}
	return null;
}

function rkrk_dabs_parse_meters($s)
{
	if (preg_match('/(\d+(?:\.\d+)?)\s*m\b/i', (string)$s, $m)) {
		return (float)$m[1];
	}
	return null;
}

function rkrk_dabs_remark_agl_m($remark)
{
	$u = strtoupper((string)$remark);
	$vals = array();
	if (preg_match_all('/(\d+)\s*M\s*\/?\s*AGL/', $u, $mm)) {
		foreach ($mm[1] as $v) {
			$vals[] = (float)$v;
		}
	}
	if (preg_match_all('/(\d+)\s*FT\s*\/?\s*AGL/', $u, $mm)) {
		foreach ($mm[1] as $v) {
			$vals[] = rkrk_dabs_ft_to_m($v);
		}
	}
	if (preg_match_all('/(\d+)\s*FT\s*(?:\/\s*)?GND/', $u, $mm)) {
		foreach ($mm[1] as $v) {
			$vals[] = rkrk_dabs_ft_to_m($v);
		}
	}
	if (preg_match_all('/(\d+)\s*M\s*(?:\/\s*)?GND/', $u, $mm)) {
		foreach ($mm[1] as $v) {
			$vals[] = (float)$v;
		}
	}
	return $vals;
}

function rkrk_dabs_affects_agl($item, $maxAglM = 120)
{
	$lower = (string)($item['lower'] ?? '');
	$remark = (string)($item['remark'] ?? '');
	$maxAglM = (float)$maxAglM;
	$aglVals = rkrk_dabs_remark_agl_m($remark);

	if (preg_match('/LOWER LIMIT[^.]{0,80}?(\d+)\s*FT\s*(?:AMSL\s*OR\s*)?(\d+)\s*FT\s*GND/i', $remark, $m)) {
		$floorAgl = rkrk_dabs_ft_to_m($m[2]);
		if ($floorAgl > $maxAglM) {
			return false;
		}
	} elseif (preg_match('/(\d+)\s*FT\s*GND\s*WHICHEVER/i', $remark, $m)) {
		$floorAgl = rkrk_dabs_ft_to_m($m[1]);
		if ($floorAgl > $maxAglM) {
			return false;
		}
	}

	$fl = rkrk_dabs_parse_fl($lower);
	if ($fl !== null && $fl >= 10 && !rkrk_dabs_lower_is_gnd($lower)) {
		return false;
	}

	$lowerM = rkrk_dabs_parse_meters($lower);
	if ($lowerM !== null && $lowerM >= 1500 && !rkrk_dabs_lower_is_gnd($lower) && !$aglVals) {
		return false;
	}

	if (rkrk_dabs_lower_is_gnd($lower)) {
		return true;
	}
	if ($aglVals) {
		$maxOp = max($aglVals);
		$minOp = min($aglVals);
		return ($minOp < $maxAglM && $maxOp > 0);
	}
	if ($fl !== null) {
		return false;
	}
	return rkrk_dabs_lower_is_gnd($lower);
}

function rkrk_dabs_kind($text)
{
	$u = strtoupper($text);
	if (strpos($u, 'UNMANNED') !== false || strpos($u, 'BVLOS') !== false || strpos($u, 'DRONE') !== false) {
		return 'uas';
	}
	if (strpos($u, 'FRNG') !== false || strpos($u, 'FIRING') !== false || preg_match('/\bLSR\d/', $u)) {
		return 'firing';
	}
	if (strpos($u, 'R-AREA') !== false || strpos($u, 'PROHIBITED') !== false || strpos($u, 'TEMPO') !== false) {
		return 'restrict';
	}
	return 'info';
}

function rkrk_dabs_parse_items($strings)
{
	$start = 0;
	foreach ($strings as $i => $s) {
		if (strcasecmp($s, 'NOTAM Item E') === 0) {
			$start = $i + 1;
			break;
		}
	}
	$n = count($strings);
	$items = array();
	$i = $start;
	while ($i < $n) {
		$s = trim((string)$strings[$i]);
		if ($s === '' || rkrk_dabs_is_noise($s)) {
			$i++;
			continue;
		}
		if (stripos($s, 'Activities not shown') === 0) {
			break;
		}
		if (!rkrk_dabs_is_id($s)) {
			$i++;
			continue;
		}
		$area = $s;
		$notam = '';
		$i++;
		if ($i < $n && rkrk_dabs_is_id($strings[$i]) && strtoupper($strings[$i][0]) === 'W') {
			$notam = $strings[$i];
			$i++;
		} elseif (rkrk_dabs_is_id($area) && strtoupper($area[0]) === 'W') {
			$notam = $area;
			$area = '';
		}
		$validity = '';
		$lower = '';
		$upper = '';
		if ($i < $n && rkrk_dabs_is_validity($strings[$i])) {
			$validity = $strings[$i];
			$i++;
		}
		if ($i < $n && !rkrk_dabs_is_center($strings[$i]) && !rkrk_dabs_is_radius($strings[$i]) && !rkrk_dabs_is_id($strings[$i])) {
			$lower = $strings[$i];
			$i++;
		}
		if ($i < $n && !rkrk_dabs_is_center($strings[$i]) && !rkrk_dabs_is_radius($strings[$i]) && !rkrk_dabs_is_id($strings[$i])) {
			$upper = $strings[$i];
			$i++;
		}
		$center = '';
		if ($i < $n && rkrk_dabs_is_center($strings[$i])) {
			$center = $strings[$i];
			$i++;
		}
		$radius = '';
		if ($i < $n && rkrk_dabs_is_radius($strings[$i])) {
			$radius = $strings[$i];
			$i++;
		}
		$remarkParts = array();
		while ($i < $n) {
			$t = trim((string)$strings[$i]);
			if ($t === '' || $t === '!') {
				$i++;
				continue;
			}
			if (rkrk_dabs_is_id($t) && isset($strings[$i + 1]) && (rkrk_dabs_is_id($strings[$i + 1]) || rkrk_dabs_is_validity($strings[$i + 1]))) {
				break;
			}
			if (stripos($t, 'Activities not shown') === 0 || strcasecmp($t, 'Page') === 0) {
				break;
			}
			$remarkParts[] = $t;
			$i++;
		}
		$ll = $center !== '' ? rkrk_dabs_parse_coord($center) : null;
		$rm = $radius !== '' ? rkrk_dabs_parse_radius_m($radius) : null;
		if (!$ll || !$rm) {
			continue;
		}
		$remark = trim(preg_replace('/\s+/', ' ', implode(' ', $remarkParts)));
		$title = $area !== '' ? $area : $notam;
		if ($area !== '' && $notam !== '') {
			$title = $area . ' · ' . $notam;
		}
		$items[] = array(
			'name' => $title,
			'area' => $area,
			'notam' => $notam,
			'validity' => $validity,
			'lower' => $lower,
			'upper' => $upper,
			'radius' => $radius,
			'radius_m' => $rm,
			'lat' => $ll[0],
			'lon' => $ll[1],
			'remark' => $remark,
			'kind' => rkrk_dabs_kind($area . ' ' . $remark),
		);
	}
	return $items;
}
