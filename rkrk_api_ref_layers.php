<?php
/**
 * Reference layers API for RKRK (rkrk_kml2xml.php).
 * actions: groups | settings | save_settings | build_from_kml | geojson | reset_settings
 *          | colors | save_colors | reset_colors
 *          | ui_defaults | save_ui_defaults | reset_ui_defaults
 *          | kml_geojson | save_kml | create_alert | delete_layer
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$GLOBALS['RKRK_AJAX_JSON_MODE'] = true;

include_once __DIR__ . '/scripts/rkrk_gen.php';
include_once __DIR__ . '/scripts/rkrk_catalog_io.php';
include_once __DIR__ . '/scripts/rkrk_ref_layers_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($WebFreigabe) && !empty($SessNme_AccessLevel) && !empty($_SESSION[$SessNme_AccessLevel])) {
    $WebFreigabe = $_SESSION[$SessNme_AccessLevel];
}

function ref_layers_fail($code, $error, $message = '')
{
    http_response_code((int)$code);
    echo json_encode(array('ok' => false, 'error' => $error, 'message' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

function ref_layers_require_write()
{
    if (!function_exists('GetAccess') || !GetAccess('InternLinks')) {
        http_response_code(403);
        echo json_encode(array('ok' => false), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$rawBody = file_get_contents('php://input');
$jsonBody = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : null;
if (!is_array($jsonBody)) {
    $jsonBody = array();
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? ($jsonBody['action'] ?? 'geojson'));
$force = isset($_GET['force']) || isset($_POST['force']) || !empty($jsonBody['force']);

if ($action === 'colors') {
    $colors = ref_layers_load_colors();
    echo json_encode(array_merge(array('ok' => true), $colors), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reset_colors') {
    ref_layers_require_write();
    $defaults = ref_layers_default_colors();
    if (!ref_layers_save_colors($defaults)) {
        ref_layers_fail(500, 'save_failed', 'colors.json konnte nicht geschrieben werden');
    }
    echo json_encode(array_merge(array('ok' => true, 'reset' => true), ref_layers_load_colors()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_colors') {
    ref_layers_require_write();
    $payload = array(
        'groups' => $jsonBody['groups'] ?? array(),
        'styles' => $jsonBody['styles'] ?? array(),
    );
    if (!ref_layers_save_colors($payload)) {
        ref_layers_fail(500, 'save_failed', 'colors.json konnte nicht geschrieben werden');
    }
    echo json_encode(array_merge(array('ok' => true), ref_layers_load_colors()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'ui_defaults') {
    echo json_encode(array_merge(array('ok' => true), ref_layers_load_ui_defaults()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reset_ui_defaults') {
    ref_layers_require_write();
    $defaults = ref_layers_default_ui();
    if (!ref_layers_save_ui_defaults($defaults)) {
        ref_layers_fail(500, 'save_failed', 'ui_defaults.json konnte nicht geschrieben werden');
    }
    echo json_encode(array_merge(array('ok' => true, 'reset' => true), ref_layers_load_ui_defaults()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_ui_defaults') {
    ref_layers_require_write();
    $payload = array(
        'toggles' => $jsonBody['toggles'] ?? array(),
        'filterBbox' => !empty($jsonBody['filterBbox']),
    );
    if (!ref_layers_save_ui_defaults($payload)) {
        ref_layers_fail(500, 'save_failed', 'ui_defaults.json konnte nicht geschrieben werden');
    }
    echo json_encode(array_merge(array('ok' => true), ref_layers_load_ui_defaults()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'groups') {
    $dirs = function_exists('ref_layers_cache_dirs') ? ref_layers_cache_dirs(false) : array();
    $dir = $dirs[0] ?? ref_layers_intermediate_dir(false);
    $cache = array();
    foreach (ref_layers_group_defs() as $d) {
        $p = ref_layers_cache_path($d['key'], false);
        $cache[] = array(
            'key' => $d['key'],
            'label' => $d['label'],
            'cached' => $p && is_file($p),
            'bytes' => ($p && is_file($p)) ? filesize($p) : 0,
            'mtime' => ($p && is_file($p)) ? date('c', filemtime($p)) : null,
        );
    }
    echo json_encode(array(
        'ok' => true,
        'groups' => ref_layers_group_defs(),
        'regions' => ref_layers_region_keys(),
        'kats' => ref_layers_kat_keys(),
        'cache' => $cache,
        'intermediate' => $dir,
        'sources' => ref_layers_kml_source_dirs(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'settings') {
    echo json_encode(array(
        'ok' => true,
        'files' => ref_layers_load_settings(),
        'regions' => ref_layers_region_keys(),
        'kats' => ref_layers_kat_keys(),
        'settings_path' => ref_layers_settings_path(),
        'sources' => ref_layers_kml_source_dirs(),
        'mission_xml' => ref_layers_mission_settings_xml(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reset_settings') {
    ref_layers_require_write();
    $xml = ref_layers_mission_settings_xml();
    if ($xml && is_file($xml)) {
        $files = ref_layers_load_settings_from_xml($xml);
        $seeded = basename($xml);
    } else {
        $files = ref_layers_load_settings();
        $seeded = 'rkrk_kmlfile_settings.json';
    }
    if (!ref_layers_save_settings($files)) {
        ref_layers_fail(500, 'save_failed', 'rkrk_kmlfile_settings.json konnte nicht geschrieben werden');
    }
    echo json_encode(array(
        'ok' => true,
        'files' => ref_layers_load_settings(),
        'seeded_from' => $seeded,
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_settings') {
    ref_layers_require_write();
    $filesIn = $jsonBody['files'] ?? ($_POST['files'] ?? null);
    if (is_string($filesIn)) {
        $filesIn = json_decode($filesIn, true);
    }
    if (!is_array($filesIn)) {
        ref_layers_fail(400, 'files_required');
    }
    $norm = array();
    $pending = array();
    foreach ($filesIn as $f) {
        if (!is_array($f)) {
            continue;
        }
        $name = trim((string)($f['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $kat = trim((string)($f['kat'] ?? 'POI'));
        if (!in_array($kat, ref_layers_kat_keys(), true)) {
            $kat = 'POI';
        }
        $region = trim((string)($f['region'] ?? ''));
        if ($region !== '' && substr($region, -1) !== ',') {
            $region .= ',';
        }
        $sched = function_exists('ref_layers_alert_schedule') ? ref_layers_alert_schedule($f) : array('from' => '', 'to' => '', 'yearly' => false);
        $norm[] = array(
            'name' => $name,
            'region' => $region,
            'kat' => $kat,
            'aktiv' => !empty($f['aktiv']),
            'from' => $sched['from'],
            'to' => $sched['to'],
            'yearly' => $sched['yearly'],
        );
        $stored = trim((string)($f['stored'] ?? ''));
        if ($stored !== '' && $stored !== $name) {
            $pending[] = array('from' => $stored, 'to' => $name, 'at' => count($norm) - 1);
        }
    }
    $applied = array();
    foreach ($pending as $op) {
        $renamed = function_exists('ref_layers_rename_layer')
            ? ref_layers_rename_layer($op['from'], $op['to'])
            : array('ok' => false, 'error' => 'rename_unavailable', 'message' => 'Umbenennen ist nicht verfügbar.');
        if (empty($renamed['ok'])) {
            foreach (array_reverse($applied) as $done) {
                ref_layers_rename_layer($done['to'], $done['from'], false);
            }
            ref_layers_fail(400, $renamed['error'] ?? 'rename_failed', $renamed['message'] ?? 'Der Name konnte nicht gespeichert werden.');
        }
        $norm[$op['at']]['name'] = (string)$renamed['name'];
        $applied[] = array('from' => (string)$op['from'], 'to' => (string)$renamed['name']);
    }
    if (!ref_layers_save_settings($norm)) {
        foreach (array_reverse($applied) as $done) {
            ref_layers_rename_layer($done['to'], $done['from'], false);
        }
        ref_layers_fail(500, 'save_failed', 'rkrk_kmlfile_settings.json konnte nicht geschrieben werden');
    }
    echo json_encode(array('ok' => true, 'files' => ref_layers_load_settings()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete_layer') {
    ref_layers_require_write();
    if (!function_exists('ref_layers_delete_layer')) {
        ref_layers_fail(500, 'delete_unavailable', 'Löschen ist nicht verfügbar.');
    }
    $rawName = (string)($jsonBody['file'] ?? $jsonBody['name'] ?? ($_POST['file'] ?? ''));
    $result = ref_layers_delete_layer($rawName);
    if (empty($result['ok'])) {
        ref_layers_fail(400, $result['error'] ?? 'delete_failed', $result['message'] ?? 'Der Eintrag konnte nicht gelöscht werden.');
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'create_alert') {
    ref_layers_require_write();
    if (!function_exists('ref_layers_create_alert_zone')) {
        ref_layers_fail(500, 'create_unavailable', 'Neue Alert-Zone ist nicht verfügbar.');
    }
    $result = ref_layers_create_alert_zone(is_array($jsonBody) ? $jsonBody : array());
    if (empty($result['ok'])) {
        ref_layers_fail(400, $result['error'] ?? 'create_failed', $result['message'] ?? 'Alert-Zone konnte nicht angelegt werden.');
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'build_from_kml' || $action === 'build') {
    ref_layers_require_write();
    $result = ref_layers_build_from_kml_sources();
    echo json_encode(array_merge(array('ok' => !empty($result['ok'])), $result), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'kml_geojson') {
    $stem = ref_layers_safe_kml_stem((string)($_GET['file'] ?? $jsonBody['file'] ?? ''));
    if ($stem === null) {
        ref_layers_fail(400, 'file_required', 'Parameter file fehlt');
    }
    $kml = ref_layers_find_kml($stem);
    if ($kml === null) {
        ref_layers_fail(404, 'kml_missing', $stem . '.kml nicht gefunden');
    }
    $meta = ref_layers_file_meta($stem);
    $feats = ref_layers_features_from_kml($kml, $meta['region'], $meta['kat']);
    echo json_encode(array(
        'ok' => true,
        'type' => 'FeatureCollection',
        'file' => $stem,
        'kat' => $meta['kat'],
        'region' => $meta['region'],
        'count' => count($feats),
        'path' => basename($kml),
        'writable' => is_writable($kml),
        'features' => $feats,
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_kml') {
    ref_layers_require_write();
    $stem = ref_layers_safe_kml_stem((string)($jsonBody['file'] ?? $_GET['file'] ?? ''));
    if ($stem === null) {
        ref_layers_fail(400, 'file_required', 'Parameter file fehlt');
    }
    $features = $jsonBody['features'] ?? array();
    if (!is_array($features)) {
        ref_layers_fail(400, 'features_required');
    }
    $result = ref_layers_save_kml_features($stem, $features);
    if (empty($result['ok'])) {
        ref_layers_fail(500, $result['error'] ?? 'save_failed', 'KML konnte nicht geschrieben werden');
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

$groupKey = trim((string)($_GET['group'] ?? $_GET['kat'] ?? ($jsonBody['group'] ?? '')));
if ($groupKey === '') {
    ref_layers_fail(400, 'group_required', 'Parameter group fehlt');
}

$path = ref_layers_cache_path($groupKey);
if ($path === null) {
    ref_layers_fail(500, 'intermediate_dir_failed');
}

if ($force || !is_file($path)) {
    $built = ref_layers_build_from_kml_sources();
    if (!is_file($path)) {
        ref_layers_fail(404, 'group_not_built', 'Gruppe nicht im Zwischenspeicher. Bitte unter Eigene Layer verwalten → Erzeugen.');
    }
}

$raw = file_get_contents($path);
$data = json_decode((string)$raw, true);
if (!is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
    ref_layers_fail(500, 'cache_invalid', $path);
}

$region = trim((string)($_GET['reg'] ?? $_GET['region'] ?? ''));
$bbox = null;
if (isset($_GET['bbox']) && trim((string)$_GET['bbox']) !== '') {
    $bb = array_map('floatval', explode(',', (string)$_GET['bbox']));
    if (count($bb) === 4) {
        $bbox = array($bb[0], $bb[1], $bb[2], $bb[3]);
    }
}
$limit = isset($_GET['limit']) ? max(1, min(5000, (int)$_GET['limit'])) : 2500;

$filtered = array();
foreach ($data['features'] as $f) {
    if (!is_array($f)) {
        continue;
    }
    $props = is_array($f['properties'] ?? null) ? $f['properties'] : array();
    if (!ref_layers_region_match((string)($props['region'] ?? ''), $region)) {
        continue;
    }
    $geom = is_array($f['geometry'] ?? null) ? $f['geometry'] : array();
    if (!ref_layers_bbox_hit($bbox, ref_layers_geom_coords($geom))) {
        continue;
    }
    $filtered[] = $f;
    if (count($filtered) >= $limit) {
        break;
    }
}

echo json_encode(array(
    'ok' => true,
    'type' => 'FeatureCollection',
    'group' => $groupKey,
    'count' => count($filtered),
    'truncated' => count($filtered) >= $limit,
    'source' => 'mylayer/' . basename($path),
    'features' => $filtered,
), JSON_UNESCAPED_UNICODE);
