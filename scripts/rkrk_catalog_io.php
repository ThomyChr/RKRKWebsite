<?php
/**
 * Catalog I/O.
 * Feldkatalog (RKRKFelder.*) folgt dem MissionLocation-Ordner des Drohnentyps.
 * POI, Layer und Regionen bleiben in rkrk/webdaten.
 * XML ist nur Fallback, solange $RKRKXmlCatalogSupport true ist.
 */

include_once(__DIR__ . '/rkrk_encoding.php');

if (!function_exists('rkrk_log_data_access')) {
	/**
	 * Ein Zugriff pro HTTP-Request ins Tages-Log, falls die Seite selbst
	 * noch nicht WriteToLog aufgerufen hat (Missionenkatalog / Adressen).
	 */
	function rkrk_log_data_access($kind, $detail = '') {
		if (!empty($GLOBALS['RKRK_PAGE_LOGGED'])) {
			return;
		}
		if (defined('PHP_SAPI') && PHP_SAPI === 'cli') {
			return;
		}
		if (!function_exists('WriteToLog')) {
			return;
		}
		$page = basename((string)($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? ''));
		if ($page === '' || strcasecmp($page, 'rkrk_catalog_io.php') === 0) {
			return;
		}
		$kind = trim((string)$kind);
		if ($kind === '') {
			return;
		}
		$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		$type = ($method === 'POST') ? 'do' : 'get';
		WriteToLog($type, $page, $kind, (string)$detail, '', '');
	}
}

if (!function_exists('rkrk_log_if_address_file')) {
	function rkrk_log_if_address_file($filename) {
		global $RKRKContactFile, $RKRK_LWB_ContactFile;
		$base = basename(str_replace('\\', '/', (string)$filename));
		if ($base === '') {
			return;
		}
		$lwb = !empty($RKRK_LWB_ContactFile)
			? basename((string)$RKRK_LWB_ContactFile)
			: 'Rehkitzrettung Konaueramt - Landwirtschaftsbetriebe.csv';
		$knt = !empty($RKRKContactFile)
			? basename((string)$RKRKContactFile)
			: 'Rehkitzrettung Konaueramt - Kontaktnummern.csv';
		$kind = '';
		if (strcasecmp($base, $lwb) === 0 || substr($base, -strlen($lwb)) === $lwb || stripos($base, 'Landwirtschaftsbetriebe') !== false) {
			$kind = 'Adressen Bauern';
		} elseif (strcasecmp($base, $knt) === 0 || substr($base, -strlen($knt)) === $knt || stripos($base, 'Kontaktnummern') !== false) {
			$kind = 'Adressen Kontakte';
		}
		if ($kind !== '' && function_exists('rkrk_log_data_access')) {
			rkrk_log_data_access($kind, $base);
		}
	}
}

if (!function_exists('rkrk_xml_catalog_support')) {
	function rkrk_xml_catalog_support() {
		global $RKRKXmlCatalogSupport;
		if (!isset($RKRKXmlCatalogSupport)) {
			return true;
		}
		return (bool) $RKRKXmlCatalogSupport;
	}
}

if (!function_exists('rkrk_catalog_xml_basenames')) {
	function rkrk_catalog_xml_basenames() {
		return array(
			'RKRKFelder.xml',
			'rkrk_poidata.xml',
			'rkrk_kmlfile_settings.xml',
			'pers_settings.xml',
			'RKRK_SummaryFluege.xml',
			'rkrk_regional_settings.xml',
			'rkrk_fieldbooking.xml',
			'RKRK_ALLKMLStyles.xml',
		);
	}
}

if (!function_exists('rkrk_is_list_array')) {
	function rkrk_is_list_array($value) {
		if (!is_array($value) || $value === array()) {
			return is_array($value);
		}
		$i = 0;
		foreach ($value as $k => $unused) {
			if ($k !== $i) {
				return false;
			}
			$i++;
		}
		return true;
	}
}

if (!function_exists('rkrk_feldkatalog_min_bytes')) {
	function rkrk_feldkatalog_min_bytes() {
		// Der 4TD-XML-Stub hat ca. 11 KB und ist kein Feldkatalog.
		return 20480;
	}
}

if (!function_exists('rkrk_catalog_is_feldkatalog_basename')) {
	function rkrk_catalog_is_feldkatalog_basename($path) {
		$base = strtolower(basename(str_replace('\\', '/', (string) $path)));
		return $base === 'rkrkfelder.xml' || $base === 'rkrkfelder.json' || $base === 'rkrkfelder.csv' || $base === 'rkrkfelder';
	}
}

if (!function_exists('rkrk_feldkatalog_file_complete')) {
	function rkrk_feldkatalog_file_complete($path) {
		if (!is_string($path) || $path === '' || !is_file($path)) {
			return false;
		}
		$size = @filesize($path);
		if ($size === false || $size < 32) {
			return false;
		}
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($ext === 'json') {
			if ($size >= rkrk_feldkatalog_min_bytes()) {
				return true;
			}
			$head = @file_get_contents($path, false, null, 0, 512);
			return is_string($head)
				&& strpos($head, 'rkrk-feldkatalog') !== false
				&& strpos($head, '"fields"') !== false;
		}
		// XML-Stub (ca. 11 KB) bleibt unter der Mindestgrösse und ist kein Katalog.
		return $size >= rkrk_feldkatalog_min_bytes();
	}
}

if (!function_exists('rkrk_feldkatalog_cross_drone_pages')) {
	function rkrk_feldkatalog_cross_drone_pages() {
		return array(
			'rkrk_planning.php',
			'rkrk_fluege.php',
			'rkrk_flugstatistik.php',
			'rkrk_flugflaechen_karte.php',
			'rkrk_flugliste.php',
		);
	}
}

if (!function_exists('rkrk_feldkatalog_request_is_cross_drone')) {
	function rkrk_feldkatalog_request_is_cross_drone() {
		$page = strtolower(basename(str_replace('\\', '/', (string) ($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? ''))));
		return in_array($page, rkrk_feldkatalog_cross_drone_pages(), true);
	}
}

if (!function_exists('rkrk_feldkatalog_data_root')) {
	function rkrk_feldkatalog_data_root() {
		global $RKRKRelWebLocation;
		$root = rtrim(str_replace('\\', '/', (string) $RKRKRelWebLocation), '/');
		if ($root === '') {
			$root = '../../rkropen';
		}
		return $root;
	}
}

if (!function_exists('rkrk_feldkatalog_cross_drone_xml_path')) {
	function rkrk_feldkatalog_cross_drone_xml_path() {
		global $RKRKFelderfileXML;
		$name = !empty($RKRKFelderfileXML) ? $RKRKFelderfileXML : 'RKRKFelder.xml';
		return rkrk_feldkatalog_data_root() . '/Missionen/xmlfiles/' . $name;
	}
}

if (!function_exists('rkrk_feldkatalog_webdaten_json_path')) {
	function rkrk_feldkatalog_webdaten_json_path() {
		global $jsonDocLocation, $RKRKRelRKRKDataLocation;
		if (!empty($jsonDocLocation)) {
			$dir = $jsonDocLocation;
		} elseif (!empty($RKRKRelRKRKDataLocation)) {
			$dir = $RKRKRelRKRKDataLocation . 'webdaten/';
		} else {
			$dir = rkrk_feldkatalog_data_root() . '/rkrk/webdaten/';
		}
		return rtrim(str_replace('\\', '/', (string) $dir), '/') . '/RKRKFelder.json';
	}
}

if (!function_exists('rkrk_feldkatalog_path_is_mavic3t')) {
	function rkrk_feldkatalog_path_is_mavic3t($path) {
		$p = str_replace('\\', '/', (string) $path);
		if (!preg_match('#/Missionen/(?:xmlfiles|jsonfiles|csvfiles)(?:/|$)#i', $p)) {
			return false;
		}
		return !preg_match('#/Missionen [^/]+/#', $p);
	}
}

if (!function_exists('rkrk_feldkatalog_json_dir_for')) {
	function rkrk_feldkatalog_json_dir_for($xmlFileOrDir) {
		global $feldkatalogJsonLocation, $RKRKLocalLoactionMissionFiles;
		$path = str_replace('\\', '/', (string) $xmlFileOrDir);
		if (preg_match('#^(.*?)/(xmlfiles|jsonfiles|csvfiles|webdaten)(?:/|$)#i', $path, $m)) {
			if (strtolower($m[2]) === 'webdaten') {
				return rtrim($m[1], '/') . '/webdaten/';
			}
			return rtrim($m[1], '/') . '/jsonfiles/';
		}
		if (!empty($feldkatalogJsonLocation)) {
			return rtrim(str_replace('\\', '/', $feldkatalogJsonLocation), '/') . '/';
		}
		if (!empty($RKRKLocalLoactionMissionFiles)) {
			return rtrim(str_replace('\\', '/', $RKRKLocalLoactionMissionFiles), '/') . '/jsonfiles/';
		}
		return '';
	}
}

if (!function_exists('rkrk_feldkatalog_resolve_source')) {
	function rkrk_feldkatalog_resolve_source($xmlFilePath) {
		if (rkrk_feldkatalog_request_is_cross_drone()) {
			$shared = rkrk_feldkatalog_cross_drone_xml_path();
			if ($shared !== '') {
				$xmlFilePath = $shared;
			}
		}
		$jsonPath = rkrk_catalog_json_path($xmlFilePath);
		if (rkrk_feldkatalog_file_complete($jsonPath)) {
			return array('json' => $jsonPath, 'xml' => $xmlFilePath);
		}
		if (rkrk_feldkatalog_file_complete($xmlFilePath)) {
			return array('json' => '', 'xml' => $xmlFilePath);
		}
		if (rkrk_feldkatalog_request_is_cross_drone() || rkrk_feldkatalog_path_is_mavic3t($xmlFilePath)) {
			$legacy = rkrk_feldkatalog_webdaten_json_path();
			if (rkrk_feldkatalog_file_complete($legacy)) {
				return array('json' => $legacy, 'xml' => $xmlFilePath);
			}
		}
		return array('json' => '', 'xml' => '');
	}
}

if (!function_exists('rkrk_catalog_json_dir')) {
	function rkrk_catalog_json_dir($xmlFileOrDir) {
		global $jsonMissionLocation, $jsonFileDirectory, $xmlFileDirectory;
		$path = str_replace('\\', '/', (string) $xmlFileOrDir);
		$looksLikeFile = ($path !== '' && (substr($path, -4) === '.xml' || substr($path, -5) === '.json' || is_file($xmlFileOrDir)));
		if ($looksLikeFile && rkrk_catalog_is_feldkatalog_basename($path)) {
			$feldDir = rkrk_feldkatalog_json_dir_for($xmlFileOrDir);
			if ($feldDir !== '') {
				return $feldDir;
			}
		}
		$shared = '';
		if (!empty($jsonMissionLocation)) {
			$shared = rtrim(str_replace('\\', '/', $jsonMissionLocation), '/') . '/';
		}
		if ($shared !== '') {
			if ($path === '') {
				return $shared;
			}
			$isFile = (substr($path, -4) === '.xml' || substr($path, -5) === '.json' || is_file($xmlFileOrDir));
			$dir = $isFile ? dirname($path) : rtrim($path, '/');
			$base = basename($dir);
			$xmlDirName = !empty($xmlFileDirectory) ? rtrim(str_replace('\\', '/', $xmlFileDirectory), '/') : 'xmlfiles';
			$jsonDirName = !empty($jsonFileDirectory) ? rtrim(str_replace('\\', '/', $jsonFileDirectory), '/') : 'webdaten';
			if ($base === $xmlDirName || $base === 'xmlfiles'
				|| $base === $jsonDirName || $base === 'jsonfiles' || $base === 'webdaten') {
				return $shared;
			}
		} elseif ($path === '') {
			return '';
		}
		$isFile = (substr($path, -4) === '.xml' || substr($path, -5) === '.json' || is_file($xmlFileOrDir));
		$dir = $isFile ? dirname($path) : rtrim($path, '/');
		$base = basename($dir);
		$xmlDirName = !empty($xmlFileDirectory) ? rtrim(str_replace('\\', '/', $xmlFileDirectory), '/') : 'xmlfiles';
		$jsonDirName = !empty($jsonFileDirectory) ? rtrim(str_replace('\\', '/', $jsonFileDirectory), '/') : 'webdaten';
		if ($base === $xmlDirName || $base === 'xmlfiles') {
			return dirname($dir) . '/' . $jsonDirName . '/';
		}
		if ($base === $jsonDirName || $base === 'jsonfiles' || $base === 'webdaten') {
			return rtrim($dir, '/') . '/';
		}
		return rtrim($dir, '/') . '/' . $jsonDirName . '/';
	}
}

if (!function_exists('rkrk_catalog_json_path')) {
	function rkrk_catalog_json_path($xmlFileOrDir, $basename = '') {
		$path = str_replace('\\', '/', (string) $xmlFileOrDir);
		$name = $basename;
		if ($name === '') {
			if ($path !== '' && (substr($path, -4) === '.xml' || substr($path, -5) === '.json')) {
				$name = basename($path);
			}
		}
		if ($name === '') {
			return '';
		}
		$name = preg_replace('/\.xml$/i', '.json', $name);
		if (!preg_match('/\.json$/i', $name)) {
			$name .= '.json';
		}
		return rkrk_catalog_json_dir($xmlFileOrDir) . $name;
	}
}

if (!function_exists('rkrk_catalog_history_copy')) {
	function rkrk_catalog_history_copy($path) {
		global $historyfilelocation;
		if (!is_string($path) || $path === '' || !is_file($path)) {
			return false;
		}
		$dir = '';
		if (!empty($historyfilelocation)) {
			$dir = rtrim(str_replace('\\', '/', $historyfilelocation), '/') . '/';
		}
		if ($dir === '' || !is_dir($dir)) {
			$dir = dirname($path) . '/_history/';
		}
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				return false;
			}
		}
		$dest = $dir . date('YmdHis') . '_' . basename($path);
		return @copy($path, $dest);
	}
}

if (!function_exists('rkrk_catalog_write_json')) {
	function rkrk_catalog_write_json($xmlFileOrDir, $data, $basename = '') {
		$jsonPath = rkrk_catalog_json_path($xmlFileOrDir, $basename);
		if ($jsonPath === '') {
			return false;
		}
		$encoded = null;
		if (rkrk_catalog_is_feldkatalog_basename($jsonPath) && is_file($jsonPath)) {
			$oldSize = @filesize($jsonPath);
			$encoded = function_exists('rkrk_json_encode_utf8') ? rkrk_json_encode_utf8($data) : json_encode($data);
			if ($encoded === false) {
				return false;
			}
			$newSize = strlen($encoded) + 1;
			if ($oldSize !== false && $oldSize >= rkrk_feldkatalog_min_bytes() && $newSize < (int) floor($oldSize * 0.5)) {
				return false;
			}
		}
		if (is_file($jsonPath)) {
			rkrk_catalog_history_copy($jsonPath);
		}
		if ($encoded !== null) {
			return rkrk_write_utf8_file($jsonPath, $encoded . "\n");
		}
		return rkrk_write_json_file($jsonPath, $data);
	}
}

if (!function_exists('rkrk_catalog_envelope')) {
	function rkrk_catalog_envelope($schema, $root, $data) {
		return array(
			'schema' => $schema,
			'version' => 1,
			'root' => $root,
			'data' => $data,
		);
	}
}

if (!function_exists('rkrk_catalog_unwrap')) {
	function rkrk_catalog_unwrap($payload) {
		if (!is_array($payload) || $payload === array()) {
			return array();
		}
		if (isset($payload['data']) && is_array($payload['data'])) {
			return $payload;
		}
		if (isset($payload['fields']) && is_array($payload['fields'])) {
			return $payload;
		}
		if (isset($payload['files']) && is_array($payload['files'])) {
			return $payload;
		}
		return array(
			'schema' => 'rkrk-catalog',
			'version' => 1,
			'root' => '',
			'data' => $payload,
		);
	}
}

if (!function_exists('rkrk_assoc_to_xml_node')) {
	function rkrk_assoc_to_xml_node($node, $data) {
		if (!($node instanceof SimpleXMLElement) || !is_array($data)) {
			return;
		}
		foreach ($data as $key => $value) {
			if ($key === '@attributes' && is_array($value)) {
				foreach ($value as $attrName => $attrVal) {
					$node->addAttribute((string) $attrName, (string) $attrVal);
				}
				continue;
			}
			$tag = is_int($key) ? 'item' : preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $key);
			if ($tag === '') {
				$tag = 'item';
			}
			if (is_array($value) && rkrk_is_list_array($value)) {
				foreach ($value as $item) {
					if (is_array($item)) {
						$child = $node->addChild($tag);
						rkrk_assoc_to_xml_node($child, $item);
					} else {
						$node->addChild($tag, htmlspecialchars(rkrk_bytes_to_utf8((string) $item), ENT_XML1 | ENT_QUOTES, 'UTF-8'));
					}
				}
			} elseif (is_array($value)) {
				$child = $node->addChild($tag);
				rkrk_assoc_to_xml_node($child, $value);
			} else {
				$node->addChild($tag, htmlspecialchars(rkrk_bytes_to_utf8((string) $value), ENT_XML1 | ENT_QUOTES, 'UTF-8'));
			}
		}
	}
}

if (!function_exists('rkrk_array_to_simplexml')) {
	function rkrk_array_to_simplexml($rootName, $data) {
		$rootName = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $rootName);
		if ($rootName === '') {
			$rootName = 'root';
		}
		try {
			$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $rootName . '/>');
		} catch (Exception $e) {
			return false;
		}
		if (is_array($data)) {
			rkrk_assoc_to_xml_node($xml, $data);
		}
		return $xml;
	}
}

if (!function_exists('rkrk_simplexml_to_array')) {
	function rkrk_simplexml_to_array($xml) {
		if (!($xml instanceof SimpleXMLElement)) {
			return array();
		}
		$json = json_encode($xml, JSON_UNESCAPED_UNICODE);
		if (!is_string($json) || $json === '') {
			return array();
		}
		$data = json_decode($json, true);
		return is_array($data) ? $data : array();
	}
}

if (!function_exists('rkrk_catalog_seed_json_from_simplexml')) {
	function rkrk_catalog_seed_json_from_simplexml($xmlPath, $xml, $overwrite = false) {
		if (!($xml instanceof SimpleXMLElement)) {
			return false;
		}
		$jsonPath = rkrk_catalog_json_path($xmlPath);
		if ($jsonPath === '') {
			return false;
		}
		if (!$overwrite && is_file($jsonPath)) {
			$existing = rkrk_read_json_file($jsonPath);
			if (is_array($existing) && $existing !== array()) {
				return true;
			}
		}
		return rkrk_catalog_write_json($xmlPath, rkrk_catalog_envelope(
			'rkrk-catalog',
			$xml->getName(),
			rkrk_simplexml_to_array($xml)
		));
	}
}

if (!function_exists('rkrk_catalog_sync_json')) {
	function rkrk_catalog_sync_json($xmlPath) {
		if (!is_string($xmlPath) || $xmlPath === '' || !is_file($xmlPath)) {
			return false;
		}
		if (rkrk_catalog_is_feldkatalog_basename($xmlPath) && !rkrk_feldkatalog_file_complete($xmlPath)) {
			return false;
		}
		if (function_exists('rkrk_load_xml_file_safe')) {
			$xml = rkrk_load_xml_file_safe($xmlPath);
		} else {
			$raw = rkrk_read_text_file($xmlPath);
			$xml = ($raw !== false) ? @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING) : false;
		}
		if (!($xml instanceof SimpleXMLElement)) {
			return false;
		}
		$base = strtolower(basename($xmlPath));
		if ($base === 'rkrkfelder.xml' && function_exists('rkrk_feldkatalog_save_json_from_xml')) {
			return rkrk_feldkatalog_save_json_from_xml($xmlPath, $xml);
		}
		return rkrk_catalog_seed_json_from_simplexml($xmlPath, $xml, true);
	}
}

if (!function_exists('rkrk_catalog_exists')) {
	function rkrk_catalog_exists($xmlFilePath) {
		if (rkrk_catalog_is_feldkatalog_basename($xmlFilePath)) {
			$jsonPath = rkrk_catalog_json_path($xmlFilePath);
			return rkrk_feldkatalog_file_complete($jsonPath) || rkrk_feldkatalog_file_complete($xmlFilePath);
		}
		$jsonPath = rkrk_catalog_json_path($xmlFilePath);
		if ($jsonPath !== '' && is_file($jsonPath)) {
			return true;
		}
		return rkrk_xml_catalog_support() && is_file($xmlFilePath);
	}
}

if (!function_exists('rkrk_catalog_payload_to_xml_parts')) {
	function rkrk_catalog_payload_to_xml_parts($payload) {
		$payload = rkrk_catalog_unwrap($payload);
		$root = isset($payload['root']) ? (string) $payload['root'] : '';
		if (isset($payload['fields']) && is_array($payload['fields'])) {
			return array($root !== '' ? $root : 'Felder', array('Feld' => $payload['fields']));
		}
		if (isset($payload['styles']) && is_array($payload['styles'])) {
			$styles = array();
			foreach ($payload['styles'] as $st) {
				if (!is_array($st)) {
					continue;
				}
				if (isset($st['id']) && !isset($st['@attributes']['id'])) {
					$st['@attributes'] = array('id' => (string) $st['id']);
					unset($st['id']);
				}
				$styles[] = $st;
			}
			return array(
				'kml',
				array(
					'Document' => array(
						'name' => isset($payload['name']) ? $payload['name'] : 'RKRK Styles',
						'Style' => $styles,
					),
				),
			);
		}
		if (isset($payload['files']) && is_array($payload['files'])) {
			return array($root !== '' ? $root : 'Files', array('File' => $payload['files']));
		}
		$data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : array();
		return array($root !== '' ? $root : 'root', $data);
	}
}

if (!function_exists('rkrk_catalog_load_simplexml')) {
	function rkrk_catalog_load_simplexml($xmlPath) {
		$isFeld = rkrk_catalog_is_feldkatalog_basename($xmlPath);
		$jsonPath = rkrk_catalog_json_path($xmlPath);
		$jsonOk = ($jsonPath !== '' && is_file($jsonPath) && (!$isFeld || rkrk_feldkatalog_file_complete($jsonPath)));
		if ($jsonOk) {
			$payload = rkrk_read_json_file($jsonPath);
			if (is_array($payload) && $payload !== array()) {
				list($root, $data) = rkrk_catalog_payload_to_xml_parts($payload);
				if (is_array($data) && $data !== array()) {
					$xml = rkrk_array_to_simplexml($root, $data);
					if ($xml instanceof SimpleXMLElement) {
						return $xml;
					}
				}
			}
		}
		if (!rkrk_xml_catalog_support() || !is_file($xmlPath)) {
			return false;
		}
		if ($isFeld && !rkrk_feldkatalog_file_complete($xmlPath)) {
			return false;
		}
		if (function_exists('rkrk_load_xml_file_safe')) {
			$xml = rkrk_load_xml_file_safe($xmlPath);
		} else {
			$raw = rkrk_read_text_file($xmlPath);
			$xml = ($raw !== false && $raw !== '')
				? @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING)
				: false;
		}
		if ($xml instanceof SimpleXMLElement) {
			if (!$isFeld) {
				rkrk_catalog_seed_json_from_simplexml($xmlPath, $xml, false);
			}
			return $xml;
		}
		return false;
	}
}

if (!function_exists('rkrk_feldkatalog_to_fields')) {
	function rkrk_feldkatalog_to_fields($Feldkatalog) {
		$fields = array();
		if (!is_array($Feldkatalog)) {
			return $fields;
		}
		foreach ($Feldkatalog as $region) {
			if (!is_array($region)) {
				continue;
			}
			foreach ($region as $feld) {
				if (!is_array($feld) || trim((string) ($feld['FeldID'] ?? '')) === '') {
					continue;
				}
				$row = $feld;
				unset($row['ListNr']);
				$fields[] = rkrk_feldkatalog_repair_row($row);
			}
		}
		return $fields;
	}
}

if (!function_exists('rkrk_catalog_field_text')) {
	function rkrk_catalog_field_text($value) {
		if ($value instanceof SimpleXMLElement) {
			$value = (string) $value;
		} elseif (is_array($value) || is_object($value)) {
			$parts = array();
			foreach ((array) $value as $item) {
				$s = trim(rkrk_catalog_field_text($item));
				if ($s !== '') {
					$parts[] = $s;
				}
			}
			$value = implode(',', $parts);
		} elseif ($value === null || $value === false) {
			$value = '';
		} else {
			$value = (string) $value;
		}
		return function_exists('rkrk_text_utf8') ? rkrk_text_utf8($value) : $value;
	}
}

if (!function_exists('rkrk_explode_csv')) {
	function rkrk_explode_csv($value, $delimiter = ',') {
		if (is_array($value)) {
			$out = array();
			foreach ($value as $item) {
				if (is_array($item) || is_object($item)) {
					foreach (rkrk_explode_csv($item, $delimiter) as $nested) {
						$out[] = $nested;
					}
					continue;
				}
				$s = trim((string) $item);
				if ($s !== '') {
					$out[] = $s;
				}
			}
			return $out;
		}
		$s = trim((string) ($value ?? ''));
		if ($s === '') {
			return array();
		}
		$out = array();
		foreach (explode($delimiter, $s) as $item) {
			$item = trim($item);
			if ($item !== '') {
				$out[] = $item;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_feldkatalog_repair_row')) {
	function rkrk_feldkatalog_repair_row($row) {
		if (!is_array($row)) {
			return array();
		}
		$nestedKeys = array('Grundstueckdaten' => true);
		foreach ($row as $key => $val) {
			if (isset($nestedKeys[$key])) {
				continue;
			}
			$row[$key] = rkrk_catalog_field_text($val);
		}
		$gd = (isset($row['Grundstueckdaten']) && is_array($row['Grundstueckdaten']))
			? $row['Grundstueckdaten']
			: array();
		$outGd = array(
			'Startplatz' => rkrk_catalog_field_text($gd['Startplatz'] ?? ''),
			'Zentrum' => rkrk_catalog_field_text($gd['Zentrum'] ?? ''),
		);
		$park = rkrk_catalog_field_text($gd['Parkplatz'] ?? '');
		if ($park !== '') {
			$outGd['Parkplatz'] = $park;
		}
		$row['Grundstueckdaten'] = $outGd;
		return $row;
	}
}

if (!function_exists('rkrk_feldkatalog_fields_from_payload')) {
	function rkrk_feldkatalog_fields_from_payload($payload) {
		$payload = rkrk_catalog_unwrap($payload);
		if (isset($payload['fields']) && is_array($payload['fields'])) {
			$out = array();
			foreach ($payload['fields'] as $row) {
				if (is_array($row)) {
					$out[] = rkrk_feldkatalog_repair_row($row);
				}
			}
			return $out;
		}
		$data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : array();
		$nested = array();
		if (isset($data['Feld']) && is_array($data['Feld'])) {
			$nested = $data['Feld'];
		} elseif (isset($data['fields']) && is_array($data['fields'])) {
			$nested = $data['fields'];
		}
		if ($nested !== array()) {
			$out = array();
			foreach ($nested as $row) {
				if (is_array($row)) {
					$out[] = rkrk_feldkatalog_repair_row($row);
				}
			}
			return $out;
		}
		return array();
	}
}

if (!function_exists('rkrk_feldkatalog_save_json')) {
	function rkrk_feldkatalog_save_json($xmlFileOrDir, $Feldkatalog) {
		global $RKRKFelderfileXML;
		$basename = !empty($RKRKFelderfileXML) ? $RKRKFelderfileXML : 'RKRKFelder.xml';
		if (function_exists('rkrk_log_data_access')) {
			rkrk_log_data_access('Missionenkatalog', 'speichern ' . basename((string)$xmlFileOrDir));
		}
		$xmlPath = $xmlFileOrDir;
		if (is_string($xmlPath) && $xmlPath !== '' && substr(strtolower($xmlPath), -4) !== '.xml') {
			$xmlPath = rtrim(str_replace('\\', '/', $xmlPath), '/') . '/' . $basename;
		}
		return rkrk_catalog_write_json($xmlPath, array(
			'schema' => 'rkrk-feldkatalog',
			'version' => 1,
			'root' => 'Felder',
			'fields' => rkrk_feldkatalog_to_fields($Feldkatalog),
		), $basename);
	}
}

if (!function_exists('rkrk_feldkatalog_save_json_from_xml')) {
	function rkrk_feldkatalog_save_json_from_xml($xmlPath, $xml) {
		global $EnabledRegion, $OptionJaeger, $MaxSelectedEntries;
		if (rkrk_catalog_is_feldkatalog_basename($xmlPath) && !rkrk_feldkatalog_file_complete($xmlPath)) {
			return false;
		}
		if (!function_exists('TransferFeldKatFromXMLtoArr')) {
			return rkrk_catalog_seed_json_from_simplexml($xmlPath, $xml, true);
		}
		if (!isset($OptionJaeger) || !is_array($OptionJaeger)) {
			$OptionJaeger = array();
		}
		$regions = is_array($EnabledRegion ?? null) ? $EnabledRegion : array();
		$max = 0;
		$kat = TransferFeldKatFromXMLtoArr($xml, $regions, $OptionJaeger, $max);
		return rkrk_feldkatalog_save_json($xmlPath, $kat);
	}
}

if (!function_exists('rkrk_feldkatalog_load')) {
	function rkrk_feldkatalog_load($xmlFilePath, $forceReload = false) {
		global $EnabledRegion, $OptionJaeger, $MaxSelectedEntries;
		static $requestCache = array();
		if ($forceReload) {
			$requestCache = array();
		}
		$source = rkrk_feldkatalog_resolve_source($xmlFilePath);
		if ($source['xml'] !== '') {
			$xmlFilePath = $source['xml'];
		}
		if (function_exists('rkrk_log_data_access')) {
			rkrk_log_data_access('Missionenkatalog', basename((string)$xmlFilePath));
		}
		if (!is_array($EnabledRegion ?? null)) {
			$EnabledRegion = array();
		}
		if (!isset($OptionJaeger) || !is_array($OptionJaeger)) {
			$OptionJaeger = array();
		}
		$jsonPath = $source['json'];
		$cacheKey = $jsonPath !== '' ? $jsonPath : (string)($source['xml'] ?? '');
		if ($cacheKey !== '' && array_key_exists($cacheKey, $requestCache)) {
			return $requestCache[$cacheKey];
		}
		$fields = array();
		if ($jsonPath !== '' && rkrk_feldkatalog_file_complete($jsonPath)) {
			$fields = rkrk_feldkatalog_fields_from_payload(rkrk_read_json_file($jsonPath));
		}
		if ($fields !== array()) {
			if (function_exists('TransferFeldKatFromListToArr')) {
				$kat = TransferFeldKatFromListToArr($fields, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
			} else {
				$kat = array();
				foreach ($fields as $row) {
					$jg = trim((string) ($row['JG'] ?? ''));
					$id = trim((string) ($row['FeldID'] ?? ''));
					if ($jg === '' || $id === '') {
						continue;
					}
					$kat[$jg][$id] = $row;
				}
			}
			if ($cacheKey !== '' && is_array($kat)) {
				$requestCache[$cacheKey] = $kat;
			}
			return is_array($kat) ? $kat : array();
		}

		if (!rkrk_xml_catalog_support() || !rkrk_feldkatalog_file_complete($xmlFilePath) || !function_exists('TransferFeldKatFromXMLtoArr')) {
			return array();
		}
		$xml = function_exists('rkrk_load_xml_file_safe')
			? rkrk_load_xml_file_safe($xmlFilePath)
			: false;
		if ($xml === false) {
			$raw = rkrk_read_text_file($xmlFilePath);
			if ($raw !== false && $raw !== '') {
				$xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
			}
		}
		$kat = TransferFeldKatFromXMLtoArr($xml ? $xml : array(), $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
		return is_array($kat) ? $kat : array();
	}
}

if (!function_exists('rkrk_poi_load')) {
	function rkrk_poi_load($xmlFilePath) {
		$jsonPath = rkrk_catalog_json_path($xmlFilePath);
		if ($jsonPath !== '' && is_file($jsonPath)) {
			$payload = rkrk_catalog_unwrap(rkrk_read_json_file($jsonPath));
			if (isset($payload['data']) && is_array($payload['data']) && $payload['data'] !== array()) {
				return $payload['data'];
			}
		}
		if (!rkrk_xml_catalog_support() || !is_file($xmlFilePath)) {
			return array();
		}
		$xml = function_exists('rkrk_load_xml_file_safe')
			? rkrk_load_xml_file_safe($xmlFilePath)
			: false;
		if (!($xml instanceof SimpleXMLElement)) {
			return array();
		}
		$arr = rkrk_simplexml_to_array($xml);
		if ($arr !== array()) {
			rkrk_catalog_seed_json_from_simplexml($xmlFilePath, $xml, false);
		}
		return $arr;
	}
}

if (!function_exists('rkrk_kml_settings_load')) {
	function rkrk_kml_settings_load($xmlFilePath) {
		return rkrk_catalog_load_simplexml($xmlFilePath);
	}
}

if (!function_exists('rkrk_kml_settings_save_json')) {
	function rkrk_kml_settings_save_json($xmlFilePath, $settingsXml) {
		if ($settingsXml instanceof SimpleXMLElement) {
			return rkrk_catalog_seed_json_from_simplexml($xmlFilePath, $settingsXml, true);
		}
		return rkrk_catalog_sync_json($xmlFilePath);
	}
}

if (!function_exists('rkrk_pers_settings_load')) {
	function rkrk_pers_settings_load($xmlFilePath) {
		return rkrk_catalog_load_simplexml($xmlFilePath);
	}
}

if (!function_exists('rkrk_summary_flights_load')) {
	function rkrk_summary_flights_load($xmlFilePath) {
		$jsonPath = rkrk_catalog_json_path($xmlFilePath);
		if ($jsonPath !== '' && is_file($jsonPath)) {
			$payload = rkrk_catalog_unwrap(rkrk_read_json_file($jsonPath));
			if (isset($payload['data']) && is_array($payload['data']) && $payload['data'] !== array()) {
				return $payload['data'];
			}
		}
		if (!rkrk_xml_catalog_support() || !is_file($xmlFilePath)) {
			return array();
		}
		$xml = function_exists('rkrk_load_xml_file_safe')
			? rkrk_load_xml_file_safe($xmlFilePath)
			: false;
		if (!($xml instanceof SimpleXMLElement)) {
			return array();
		}
		$arr = rkrk_simplexml_to_array($xml);
		if ($arr !== array()) {
			rkrk_summary_flights_save_json($xmlFilePath, $arr);
		}
		return $arr;
	}
}

if (!function_exists('rkrk_summary_flights_save_json')) {
	function rkrk_summary_flights_save_json($xmlFilePath, $summary) {
		if (!is_array($summary)) {
			return false;
		}
		return rkrk_catalog_write_json($xmlFilePath, rkrk_catalog_envelope('rkrk-summary-flights', 'Statistics', $summary));
	}
}

if (!function_exists('rkrk_kml_styles_load')) {
	function rkrk_kml_styles_load($xmlFilePath) {
		$jsonPath = rkrk_catalog_json_path($xmlFilePath);
		if ($jsonPath !== '' && is_file($jsonPath)) {
			$payload = rkrk_read_json_file($jsonPath);
			if (isset($payload['styles']) && is_array($payload['styles'])) {
				return $payload;
			}
		}
		if (!rkrk_xml_catalog_support() || !is_file($xmlFilePath)) {
			return array('schema' => 'rkrk-kml-styles', 'version' => 1, 'root' => 'kml', 'name' => 'RKRK Styles', 'styles' => array());
		}
		$xml = function_exists('rkrk_load_xml_file_safe')
			? rkrk_load_xml_file_safe($xmlFilePath)
			: false;
		if (!($xml instanceof SimpleXMLElement)) {
			return array('schema' => 'rkrk-kml-styles', 'version' => 1, 'root' => 'kml', 'name' => 'RKRK Styles', 'styles' => array());
		}
		$styles = array();
		$nodes = $xml->xpath('//*[local-name()="Style"]');
		if (!is_array($nodes)) {
			$nodes = array();
		}
		foreach ($nodes as $node) {
			$row = rkrk_simplexml_to_array($node);
			$id = '';
			if (isset($row['@attributes']['id'])) {
				$id = (string) $row['@attributes']['id'];
				unset($row['@attributes']);
			} else {
				$id = (string) $node['id'];
			}
			$row['id'] = $id;
			$styles[] = $row;
		}
		$payload = array(
			'schema' => 'rkrk-kml-styles',
			'version' => 1,
			'root' => 'kml',
			'name' => 'RKRK Styles',
			'styles' => $styles,
		);
		rkrk_catalog_write_json($xmlFilePath, $payload);
		return $payload;
	}
}

if (!function_exists('rkrk_regional_seed_json')) {
	function rkrk_regional_seed_json($xmlFilePath, $regionSettings) {
		if (!is_array($regionSettings) || $regionSettings === array()) {
			return false;
		}
		$frozen = function_exists('rkrk_config_region_frozen_fields')
			? rkrk_config_region_frozen_fields()
			: array('Reg', 'Dir');
		$catalog = array();
		foreach ($regionSettings as $key => $row) {
			if (!is_array($row)) {
				continue;
			}
			$copy = $row;
			foreach ($frozen as $field) {
				if (isset($copy[$field])) {
					$copy[$field] = $row[$field];
				}
			}
			$catalog[$key] = $copy;
		}
		rkrk_catalog_write_json($xmlFilePath, rkrk_catalog_envelope('rkrk-regional', 'Regions', $catalog));
		return true;
	}
}

if (!function_exists('rkrk_regional_apply_json')) {
	function rkrk_regional_apply_json() {
		global $xmlMissionLocation, $RegionSettings;
		if (!is_array($RegionSettings ?? null) || empty($xmlMissionLocation)) {
			return false;
		}
		$jsonPath = rkrk_catalog_json_path($xmlMissionLocation . 'rkrk_regional_settings.xml');
		if ($jsonPath === '' || !is_file($jsonPath)) {
			return false;
		}
		$payload = rkrk_catalog_unwrap(rkrk_read_json_file($jsonPath));
		$data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : array();
		if ($data === array()) {
			return false;
		}
		$frozen = function_exists('rkrk_config_region_frozen_fields')
			? rkrk_config_region_frozen_fields()
			: array('Reg', 'Dir');
		foreach ($data as $key => $row) {
			if (!is_array($row)) {
				continue;
			}
			if (!isset($RegionSettings[$key]) || !is_array($RegionSettings[$key])) {
				$RegionSettings[$key] = $row;
				continue;
			}
			if (function_exists('rkrk_config_merge_assoc')) {
				$RegionSettings[$key] = rkrk_config_merge_assoc($RegionSettings[$key], $row, $frozen);
			} else {
				foreach ($row as $field => $value) {
					if (!in_array((string) $field, $frozen, true)) {
						$RegionSettings[$key][$field] = $value;
					}
				}
			}
		}
		return true;
	}
}

if (!function_exists('rkrk_mission_field_rel_dir')) {
	/**
	 * Flacher Gebietsordner. JSON und KMZ liegen direkt darin, nicht in einem Unterordner der Katalognummer.
	 * TF_Testfelder oder TF_Testfelder/TF-065 wird TF_Testfelder.
	 */
	function rkrk_mission_field_rel_dir($regionDir, $feldId) {
		$regionDir = trim(str_replace('\\', '/', (string)$regionDir), '/');
		$feldId = trim(str_replace('\\', '/', (string)$feldId), '/');
		$feldId = str_replace(array('/', '..'), '', $feldId);
		if ($regionDir !== '' && $feldId !== '') {
			$suffix = '/' . $feldId;
			$tail = substr($regionDir, -strlen($suffix));
			if (strcasecmp((string)$tail, $suffix) === 0) {
				$regionDir = substr($regionDir, 0, -strlen($suffix));
			}
		}
		return $regionDir;
	}
}

if (!function_exists('rkrk_mission_field_file_rel')) {
	function rkrk_mission_field_file_rel($regionDir, $feldId, $filename) {
		$dir = rkrk_mission_field_rel_dir($regionDir, $feldId);
		$name = basename(str_replace('\\', '/', (string)$filename));
		if ($name === '' || $name === '.' || $name === '..') {
			return $dir;
		}
		return ($dir === '' ? '' : $dir . '/') . $name;
	}
}
