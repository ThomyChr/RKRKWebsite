<?php
ob_start();
session_start();
$mdb = trim((string)($_GET['MDB'] ?? $_GET['mdb'] ?? ''));
if ($mdb !== '') {
	$_GET['MDB'] = $mdb;
}
include_once __DIR__ . '/scripts/rkrk_gen.php';
include_once __DIR__ . '/scripts/rkrk_csvfiles.php';
include_once __DIR__ . '/scripts/rkrk_jsonfiles.php';
include_once __DIR__ . '/scripts/rkrk_einsatz_shared.php';
include_once __DIR__ . '/rkrk_access_control.php';

CheckIPandExit();
GrantAccess();

$rel = str_replace('\\', '/', trim((string)($_GET['file'] ?? '')));
if ($rel === '' || strpos($rel, '..') !== false || !preg_match('/\.json$/i', $rel)) {
	http_response_code(400);
	exit;
}
if (function_exists('rkrk_apply_m3t_catalog_paths')) {
	rkrk_apply_m3t_catalog_paths();
}
$source = function_exists('rkrk_resolve_mission_file_path') ? rkrk_resolve_mission_file_path($rel) : '';
if ($source === '' || !is_file($source)) {
	http_response_code(404);
	exit;
}
$raw = file_get_contents($source);
$json = is_string($raw) ? json_decode($raw) : null;
if (!is_object($json)) {
	http_response_code(500);
	exit;
}
$feldId = rkrk_feld_id_from_mission_filename($rel);
rkrk_json_apply_control_flights($json, $feldId);
$mission = trim((string)($RKRKMissionSelected ?? $mdb));
if ($mission === '3T' || $mission === 'D' || $mission === '') {
	rkrk_json_ensure_mavic3t_drone_type($json);
}
$body = rkrk_json_encode_export($json);
if ($body === '') {
	http_response_code(500);
	exit;
}
while (ob_get_level() > 0) {
	ob_end_clean();
}
header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . basename($rel) . '"');
header('Content-Length: ' . (string)strlen($body));
echo $body;
exit;
