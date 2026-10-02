<?php
function rkrk_adr_ico($name) {
	$paths = array(
		'trash' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M9 7V5h6v2M8 7l1 13h6l1-13"/>',
		'plus' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M12 5v14M5 12h14"/>',
		'save' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7M8 20v-6h8v6"/>',
	);
	$d = isset($paths[$name]) ? $paths[$name] : '';
	return '<svg class="rkrk-adr-ico" viewBox="0 0 24 24" aria-hidden="true">' . $d . '</svg>';
}

function rkrk_adr_ui_path() {
	return dirname(__DIR__) . '/../rkrk/data/adr_ui.json';
}

function rkrk_adr_ui_read() {
	$path = rkrk_adr_ui_path();
	if (!is_file($path)) {
		return array();
	}
	$data = json_decode((string)file_get_contents($path), true);
	return is_array($data) ? $data : array();
}

function rkrk_adr_ui_load($list) {
	$all = rkrk_adr_ui_read();
	$row = isset($all[$list]) && is_array($all[$list]) ? $all[$list] : array();
	$hidden = isset($row['hidden']) && is_array($row['hidden']) ? array_values($row['hidden']) : array();
	$widths = isset($row['widths']) && is_array($row['widths']) ? $row['widths'] : array();
	return array('hidden' => $hidden, 'widths' => $widths);
}

function rkrk_adr_ui_save($list, $patch) {
	if (!preg_match('/^[a-z0-9_-]{1,40}$/', (string)$list)) {
		return false;
	}
	$all = rkrk_adr_ui_read();
	$current = isset($all[$list]) && is_array($all[$list]) ? $all[$list] : array();
	if (isset($patch['hidden']) && is_array($patch['hidden'])) {
		$hidden = array();
		foreach ($patch['hidden'] as $name) {
			$name = (string)$name;
			if ($name !== '' && strpos($name, '__') !== 0) {
				$hidden[] = $name;
			}
		}
		$current['hidden'] = array_values(array_unique($hidden));
	}
	if (isset($patch['widths']) && is_array($patch['widths'])) {
		$widths = isset($current['widths']) && is_array($current['widths']) ? $current['widths'] : array();
		foreach ($patch['widths'] as $name => $width) {
			$name = (string)$name;
			$width = (int)$width;
			if ($name === '' || $width < 36 || $width > 640) {
				continue;
			}
			$widths[$name] = $width;
		}
		$current['widths'] = $widths;
	}
	$all[$list] = $current;
	$path = rkrk_adr_ui_path();
	$dir = dirname($path);
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	if (!is_dir($dir)) {
		return false;
	}
	return file_put_contents($path, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
	session_start();
	chdir(dirname(__DIR__));
	include_once 'scripts/rkrk_gen.php';
	$userKey = isset($SessNme_RegisteredUser) ? $SessNme_RegisteredUser : 'Benutzername';
	if (trim((string)($_SESSION[$userKey] ?? '')) === '') {
		header('HTTP/1.1 403 Forbidden');
		header('Content-Type: application/json; charset=utf-8');
		echo '{"ok":false}';
		exit;
	}
	header('Content-Type: application/json; charset=utf-8');
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$payload = json_decode((string)file_get_contents('php://input'), true);
		if (!is_array($payload)) {
			$payload = $_POST;
		}
		$list = (string)($payload['list'] ?? '');
		$ok = rkrk_adr_ui_save($list, $payload);
		echo json_encode(array('ok' => $ok));
		exit;
	}
	$list = (string)($_GET['list'] ?? '');
	echo json_encode(rkrk_adr_ui_load($list));
	exit;
}
