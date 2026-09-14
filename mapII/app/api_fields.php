<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';

$xmlPath = __DIR__ . '/../RKRKFelder.xml';
$fields = load_fields_xml($xmlPath);

$summary = array_map(static function ($f) {
    return [
        'id' => $f['id'],
        'feld_id' => $f['feld_id'],
        'name' => $f['name'],
        'manual_distance_m' => $f['manual_meta']['distanz_m'],
        'manual_waypoints' => $f['manual_meta']['wegpunkte'],
        'manual_flight_time_min' => $f['manual_meta']['flugzeit_min'],
    ];
}, $fields);

echo json_encode(['fields' => $summary], JSON_UNESCAPED_UNICODE);

