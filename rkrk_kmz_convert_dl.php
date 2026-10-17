<?php
ob_start();
session_start();
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$kmlOnly = array_key_exists('kml', $_GET);
$convert = ($from === '3T' && $to === '4TD');
if ($convert) {
	$_GET['MDB'] = '3T';
} elseif ($kmlOnly && trim((string)($_GET['mdb'] ?? '')) !== '') {
	$_GET['MDB'] = trim((string)$_GET['mdb']);
}
include_once __DIR__ . '/scripts/rkrk_gen.php';
include_once __DIR__ . '/scripts/rkrk_menu.php';
include_once __DIR__ . '/scripts/rkrk_dji_kmz_interface.php';
include_once __DIR__ . '/scripts/rkrk_kmz.php';
include_once __DIR__ . '/scripts/rkrk_jsonfiles.php';
include_once __DIR__ . '/rkrk_access_control.php';

CheckIPandExit();
GrantAccess();

$rel = str_replace('\\', '/', trim((string)($_GET['file'] ?? '')));
if ($rel === '' || strpos($rel, '..') !== false) {
	http_response_code(400);
	exit;
}
if ($kmlOnly) {
	if (!preg_match('/\.kmz$/i', $rel)) {
		http_response_code(400);
		exit;
	}
} elseif (!$convert || !preg_match('/\.(kmz|json)$/i', $rel)) {
	http_response_code(400);
	exit;
}
if ($convert && function_exists('rkrk_apply_m3t_catalog_paths')) {
	rkrk_apply_m3t_catalog_paths();
}
$source = function_exists('rkrk_resolve_mission_file_path') ? rkrk_resolve_mission_file_path($rel) : '';
if ($source === '' || !is_file($source)) {
	http_response_code(404);
	exit;
}
$sendFile = function ($body, $mime, $filename, $length = null) {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Content-Type: ' . $mime);
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Length: ' . (string)($length === null ? strlen($body) : $length));
	if (is_string($body)) {
		echo $body;
	}
	exit;
};
$ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
$kmzPath = $source;
if ($convert && $ext === 'kmz') {
	$kmzPath = (string)CreateKMZforNewDrone($rel, '3T', '4TD', false);
} elseif ($convert) {
	$out = (string)CreateJSONforNewDrone($rel, '3T', '4TD', false);
	if ($out === '' || !is_file($out)) {
		http_response_code(500);
		exit;
	}
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename="' . basename($out) . '"');
	header('Content-Length: ' . (string)filesize($out));
	readfile($out);
	exit;
}
if ($kmzPath === '' || !is_file($kmzPath)) {
	http_response_code(500);
	exit;
}
if ($kmlOnly) {
	$zip = new ZipArchive();
	if ($zip->open($kmzPath) !== true) {
		http_response_code(500);
		exit;
	}
	$kml = $zip->getFromName('wpmz/template.kml');
	if ($kml === false) {
		$kml = $zip->getFromName('template.kml');
	}
	$zip->close();
	if (!is_string($kml) || $kml === '') {
		http_response_code(404);
		exit;
	}
	$kmlName = preg_replace('/\.kmz$/i', '.kml', basename($kmzPath));
	$sendFile($kml, 'application/vnd.google-earth.kml+xml', $kmlName);
}
while (ob_get_level() > 0) {
	ob_end_clean();
}
header('Content-Type: application/vnd.google-earth.kmz');
header('Content-Disposition: attachment; filename="' . basename($kmzPath) . '"');
header('Content-Length: ' . (string)filesize($kmzPath));
readfile($kmzPath);
exit;
