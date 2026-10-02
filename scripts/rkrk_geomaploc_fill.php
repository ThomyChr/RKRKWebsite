<?php
/**
 * CLI: fehlende GeoMapLoc in Kontakt- und Bauer-CSV aus der Adresse ergänzen.
 * php scripts/rkrk_geomaploc_fill.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Nur auf der Kommandozeile ausführen.\n");
	exit(1);
}

$AdressTableType = 'Kontakt';
require_once __DIR__ . '/rkrk_adr.php';

$csvDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'rkrk' . DIRECTORY_SEPARATOR . 'webcsv' . DIRECTORY_SEPARATOR;
$files = array(
	'Rehkitzrettung Konaueramt - Kontaktnummern.csv',
	'Rehkitzrettung Konaueramt - Landwirtschaftsbetriebe.csv',
);

foreach ($files as $name) {
	$path = $csvDir . $name;
	$stats = rkrk_geomaploc_fill_file($path);
	echo $name . ': Zeilen ' . $stats['rows']
		. ', bereits gesetzt ' . $stats['kept']
		. ', neu ' . $stats['filled']
		. ', ohne Treffer ' . $stats['miss']
		. ', ohne Adresse ' . $stats['noaddress']
		. PHP_EOL;
}

function rkrk_geomaploc_fill_file($path) {
	$stats = array('rows' => 0, 'kept' => 0, 'filled' => 0, 'miss' => 0, 'noaddress' => 0);
	if (!is_file($path)) {
		fwrite(STDERR, "Datei fehlt: " . $path . PHP_EOL);
		return $stats;
	}
	$handle = fopen($path, 'r');
	if ($handle === false) {
		fwrite(STDERR, "Lesen fehlgeschlagen: " . $path . PHP_EOL);
		return $stats;
	}
	$header = fgetcsv($handle, 1048576, ';');
	if (!is_array($header) || count($header) === 0) {
		fclose($handle);
		fwrite(STDERR, "Kopfzeile fehlt: " . $path . PHP_EOL);
		return $stats;
	}
	$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
	$rows = array();
	while (($cols = fgetcsv($handle, 1048576, ';')) !== false) {
		if ($cols === array(null) || (count($cols) === 1 && trim((string)$cols[0]) === '')) {
			continue;
		}
		if (count($cols) < count($header)) {
			$cols = array_pad($cols, count($header), '');
		} elseif (count($cols) > count($header)) {
			$cols = array_slice($cols, 0, count($header));
		}
		$rows[] = array_combine($header, $cols);
	}
	fclose($handle);

	$insertAt = array_search('Bemerkung', $header, true);
	if ($insertAt === false) {
		$insertAt = count($header);
	}
	if (!in_array('GeoMapLoc', $header, true)) {
		array_splice($header, $insertAt, 0, array('GeoMapLoc'));
		foreach ($rows as $i => $row) {
			$rows[$i]['GeoMapLoc'] = '';
		}
	}

	$queries = array();
	foreach ($rows as $row) {
		$stats['rows']++;
		$geo = trim((string)(isset($row['GeoMapLoc']) ? $row['GeoMapLoc'] : ''));
		$address = trim((string)(isset($row['Adresse']) ? $row['Adresse'] : ''));
		if ($geo !== '') {
			$stats['kept']++;
			continue;
		}
		if ($address === '') {
			$stats['noaddress']++;
			continue;
		}
		$query = rkrk_adr_geomaploc_search_text($address);
		if ($query === '') {
			$stats['noaddress']++;
			continue;
		}
		$key = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
		$queries[$key] = $query;
	}

	echo basename($path) . ': ' . count($queries) . ' verschiedene Adressen abfragen...' . PHP_EOL;
	$hits = rkrk_adr_geocode_queries($queries, 'address');
	$retry = array();
	foreach ($queries as $key => $query) {
		if (!isset($hits[$key]) || $hits[$key] === '') {
			$retry[$key] = $query;
		}
	}
	if (count($retry) > 0) {
		echo basename($path) . ': ' . count($retry) . ' ohne Hausnummer-Treffer, Ortsuche...' . PHP_EOL;
		$fallback = rkrk_adr_geocode_queries($retry, '');
		foreach ($fallback as $key => $value) {
			if ($value !== '') {
				$hits[$key] = $value;
			}
		}
		foreach ($retry as $key => $query) {
			if (!isset($hits[$key]) || $hits[$key] === '') {
				$hits[$key] = rkrk_adr_geocode_address($query);
			}
		}
	}

	foreach ($rows as $i => $row) {
		$geo = trim((string)(isset($row['GeoMapLoc']) ? $row['GeoMapLoc'] : ''));
		if ($geo !== '') {
			continue;
		}
		$address = trim((string)(isset($row['Adresse']) ? $row['Adresse'] : ''));
		if ($address === '') {
			$rows[$i]['GeoMapLoc'] = '';
			continue;
		}
		$query = rkrk_adr_geomaploc_search_text($address);
		$key = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
		$hit = isset($hits[$key]) ? $hits[$key] : '';
		if ($hit === '') {
			$stats['miss']++;
			$rows[$i]['GeoMapLoc'] = '';
			continue;
		}
		$rows[$i]['GeoMapLoc'] = $hit;
		$stats['filled']++;
	}

	$history = dirname($path, 3) . DIRECTORY_SEPARATOR . 'Missionen' . DIRECTORY_SEPARATOR . '_history' . DIRECTORY_SEPARATOR;
	if (!is_dir($history)) {
		$history = dirname($path) . DIRECTORY_SEPARATOR;
	}
	$backup = $history . date('YmdHis') . '_geomap_' . basename($path);
	if (!copy($path, $backup)) {
		fwrite(STDERR, "Backup fehlgeschlagen, Datei nicht geändert: " . $path . PHP_EOL);
		return $stats;
	}

	$tmp = $path . '.geomap.tmp';
	$out = fopen($tmp, 'w');
	if ($out === false) {
		fwrite(STDERR, "Schreiben fehlgeschlagen: " . $tmp . PHP_EOL);
		return $stats;
	}
	rkrk_geomaploc_write_row($out, $header);
	foreach ($rows as $row) {
		$line = array();
		foreach ($header as $col) {
			$line[] = isset($row[$col]) ? $row[$col] : '';
		}
		rkrk_geomaploc_write_row($out, $line);
	}
	fclose($out);
	if (!rename($tmp, $path)) {
		fwrite(STDERR, "Ersetzen fehlgeschlagen: " . $path . PHP_EOL);
	}
	return $stats;
}

function rkrk_geomaploc_write_row($handle, $fields) {
	$out = array();
	foreach ($fields as $field) {
		$field = str_replace(array("\r", "\n"), ' ', (string)$field);
		if (strpos($field, ';') !== false) {
			$field = '"' . str_replace('"', '""', $field) . '"';
		}
		$out[] = $field;
	}
	fwrite($handle, implode(';', $out) . "\r\n");
}
