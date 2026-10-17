<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';

$fieldId = isset($_GET['field_id']) ? trim((string)$_GET['field_id']) : '';
$hfov = isset($_GET['hfov']) ? floatval($_GET['hfov']) : 48.0;
$agl = isset($_GET['agl']) ? floatval($_GET['agl']) : 50.0;
$overlap = isset($_GET['overlap']) ? floatval($_GET['overlap']) : 0.15;
$edgeFactor = isset($_GET['edge_factor']) ? floatval($_GET['edge_factor']) : 0.40;
$speed = isset($_GET['speed']) ? floatval($_GET['speed']) : 8.0;
$planner = isset($_GET['planner']) ? strtolower(trim((string)$_GET['planner'])) : 'php';

$xmlPath = __DIR__ . '/../RKRKFelder.xml';
$fields = load_fields_xml($xmlPath);
$selected = null;
foreach ($fields as $f) {
    if ($f['id'] === $fieldId) {
        $selected = $f;
        break;
    }
}
if (!$selected) {
    http_response_code(404);
    echo json_encode(['error' => 'field_not_found']);
    exit;
}
if (!$selected['start'] || count($selected['polygon']) < 3) {
    http_response_code(422);
    echo json_encode(['error' => 'field_missing_geometry']);
    exit;
}

if ($planner === 'python') {
    $tmpIn = tempnam(sys_get_temp_dir(), 'rkrk_in_');
    $tmpOut = tempnam(sys_get_temp_dir(), 'rkrk_out_');
    $payload = [
        'field' => [
            'polygon' => $selected['polygon'],
            'start' => $selected['start'],
        ],
        'params' => [
            'HFOV_deg' => $hfov,
            'h_agl_m' => $agl,
            'overlap' => $overlap,
            'edge_factor' => $edgeFactor,
            'agl_tol_m' => 10.0,
            'angle_step_deg' => 5,
            'target_step_m' => 2.0,
            'max_nb_points' => 5000,
            'min_delay_s' => 0.05,
        ],
    ];
    file_put_contents($tmpIn, json_encode($payload, JSON_UNESCAPED_UNICODE));
    $scriptPath = realpath(__DIR__ . '/../api_plan.py');
    $cmd = 'python ' . escapeshellarg((string)$scriptPath) . ' --input ' . escapeshellarg($tmpIn) . ' --output ' . escapeshellarg($tmpOut);
    exec($cmd, $execOut, $exitCode);
    $plannedRaw = @file_get_contents($tmpOut);
    @unlink($tmpIn);
    @unlink($tmpOut);
    $plannedPayload = $plannedRaw ? json_decode($plannedRaw, true) : null;
    if ($exitCode !== 0 || !$plannedPayload || empty($plannedPayload['ok'])) {
        http_response_code(500);
        echo json_encode(['error' => 'python_planner_failed', 'details' => $plannedPayload], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $planned = [
        'path' => $plannedPayload['path'],
        'angle' => isset($plannedPayload['metrics']['theta_deg']) ? floatval($plannedPayload['metrics']['theta_deg']) : 0.0,
        'W' => floatval($plannedPayload['formula']['W'] ?? 0.0),
        'S' => floatval($plannedPayload['formula']['S'] ?? 0.0),
    ];
} else {
    $planned = plan_lawnmower(
        $selected['polygon'],
        $selected['start'],
        $hfov,
        $agl,
        $overlap,
        $edgeFactor
    );
}

$manualMetrics = path_metrics($selected['manual_path']);
$algoMetrics = path_metrics($planned['path']);
$manualTime = $speed > 0 ? ($manualMetrics['distance_m'] / $speed) / 60.0 : 0.0;
$algoTime = $speed > 0 ? ($algoMetrics['distance_m'] / $speed) / 60.0 : 0.0;

echo json_encode([
    'field' => [
        'id' => $selected['id'],
        'feld_id' => $selected['feld_id'],
        'name' => $selected['name'],
        'start' => $selected['start'],
        'polygon' => $selected['polygon'],
    ],
    'formula' => [
        'W' => $planned['W'],
        'S' => $planned['S'],
        'sichtbreite' => 'W = 2 * h * tan(HFOV / 2)',
        'spurabstand' => 'S = (1-overlap) * W',
        'rand' => 'd_rand <= 0.40 * S',
    ],
    'manual' => [
        'path' => $selected['manual_path'],
        'metrics' => [
            'distance_m' => $manualMetrics['distance_m'],
            'waypoints' => $manualMetrics['waypoints'],
            'height_delta_m' => $manualMetrics['height_delta_m'],
            'time_min_est' => $manualTime,
        ],
    ],
    'algorithm' => [
        'path' => $planned['path'],
        'metrics' => [
            'distance_m' => $algoMetrics['distance_m'],
            'waypoints' => $algoMetrics['waypoints'],
            'height_delta_m' => $algoMetrics['height_delta_m'],
            'time_min_est' => $algoTime,
            'angle_deg' => $planned['angle'],
        ],
    ],
    'planner' => $planner,
    'improvement' => [
        'distance_pct' => $manualMetrics['distance_m'] > 0 ? (1.0 - ($algoMetrics['distance_m'] / $manualMetrics['distance_m'])) * 100.0 : 0.0,
        'time_pct' => $manualTime > 0 ? (1.0 - ($algoTime / $manualTime)) * 100.0 : 0.0,
        'height_delta_pct' => $manualMetrics['height_delta_m'] > 0 ? (1.0 - ($algoMetrics['height_delta_m'] / $manualMetrics['height_delta_m'])) * 100.0 : 0.0,
    ],
], JSON_UNESCAPED_UNICODE);

