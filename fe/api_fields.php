<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';

$xmlPath = resolve_fields_xml_path();
$fields = load_fields_xml($xmlPath);

$summary = array_map(static function ($f) {
    return [
        'id' => $f['id'],
        'feld_id' => $f['feld_id'],
        'name' => $f['name'],
        'manual_distance_m' => $f['manual_meta']['distanz_m'],
        'manual_waypoints' => $f['manual_meta']['wegpunkte'],
        'manual_flight_time_min' => $f['manual_meta']['flugzeit_min'],
        'reference_agl_m' => $f['manual_meta']['std_flughoehe_m'],
    ];
}, $fields);

usort($summary, static function (array $a, array $b): int {
    $ka = mb_strtolower(trim((string)($a['feld_id'] ?? '')) . ' ' . trim((string)($a['name'] ?? '')));
    $kb = mb_strtolower(trim((string)($b['feld_id'] ?? '')) . ' ' . trim((string)($b['name'] ?? '')));
    return $ka <=> $kb;
});

echo json_encode(['fields' => $summary], JSON_UNESCAPED_UNICODE);

