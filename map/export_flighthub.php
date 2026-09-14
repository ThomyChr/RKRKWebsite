<?php
declare(strict_types=1);

/**
 * FlightHub 2 annotation push (one-shot, not live).
 *
 * GET  → { ok, configured }  (no secrets)
 * POST → { name, elements[] } forwarded to
 *        POST {api_base}/map/api/v1.0/projects/{uuid}/elements
 *        with X-Organization-Key / X-User-Token / X-Project-Uuid.
 *
 * Keys stay on the server (map/flighthub.local.php or env).
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/map_env.php';

function flighthub_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flighthub_hex_color($value): string
{
    $c = strtolower(trim((string)$value));
    if ($c === '') {
        return '#ff8a00';
    }
    if ($c[0] !== '#') {
        $c = '#' . $c;
    }
    if (preg_match('/^#[0-9a-f]{3}$/', $c)) {
        $c = '#' . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3];
    }
    return preg_match('/^#[0-9a-f]{6}$/', $c) ? $c : '#ff8a00';
}

function flighthub_num($value): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $n = (float)$value;
    if (is_nan($n) || is_infinite($n)) {
        return null;
    }
    return $n;
}

function flighthub_point_coords($raw): ?array
{
    if (!is_array($raw) || count($raw) < 2) {
        return null;
    }
    $lon = flighthub_num($raw[0] ?? null);
    $lat = flighthub_num($raw[1] ?? null);
    if ($lon === null || $lat === null) {
        return null;
    }
    return [$lon, $lat, 0];
}

function flighthub_line_coords($raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $pts = [];
    foreach ($raw as $p) {
        $pt = flighthub_point_coords($p);
        if ($pt === null) {
            continue;
        }
        $pts[] = [$pt[0], $pt[1]];
    }
    return count($pts) >= 2 ? $pts : null;
}

function flighthub_ring_coords($raw): ?array
{
    $line = flighthub_line_coords($raw);
    if ($line === null || count($line) < 3) {
        return null;
    }
    $a = $line[0];
    $b = $line[count($line) - 1];
    if (abs($a[0] - $b[0]) > 1e-9 || abs($a[1] - $b[1]) > 1e-9) {
        $line[] = [$a[0], $a[1]];
    }
    return count($line) >= 4 ? $line : null;
}

function flighthub_sanitize_element($raw, string $folderName): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $resource = $raw['resource'] ?? null;
    if (!is_array($resource)) {
        return null;
    }
    $type = (int)($resource['type'] ?? -1);
    $content = $resource['content'] ?? null;
    if (!is_array($content)) {
        return null;
    }
    $geometry = $content['geometry'] ?? null;
    if (!is_array($geometry)) {
        return null;
    }
    $props = is_array($content['properties'] ?? null) ? $content['properties'] : [];
    $color = flighthub_hex_color($props['color'] ?? '#ff8a00');
    $name = trim((string)($raw['name'] ?? 'Objekt'));
    if ($name === '') {
        $name = 'Objekt';
    }
    if ($folderName !== '') {
        $prefix = $folderName . ' / ';
        if (strpos($name, $prefix) !== 0) {
            $name = $prefix . $name;
        }
    }
    $name = function_exists('mb_substr') ? mb_substr($name, 0, 120) : substr($name, 0, 120);
    $desc = trim((string)($raw['desc'] ?? ''));
    $outGeom = null;
    if ($type === 0) {
        $pt = flighthub_point_coords($geometry['coordinates'] ?? null);
        if ($pt === null) {
            return null;
        }
        $outGeom = ['type' => 'Point', 'coordinates' => $pt];
    } elseif ($type === 1) {
        $line = flighthub_line_coords($geometry['coordinates'] ?? null);
        if ($line === null) {
            return null;
        }
        $outGeom = ['type' => 'LineString', 'coordinates' => $line];
    } elseif ($type === 2) {
        $ringsRaw = $geometry['coordinates'] ?? null;
        $ring = is_array($ringsRaw) && isset($ringsRaw[0]) && is_array($ringsRaw[0]) && isset($ringsRaw[0][0]) && is_array($ringsRaw[0][0])
            ? flighthub_ring_coords($ringsRaw[0])
            : flighthub_ring_coords($ringsRaw);
        if ($ring === null) {
            return null;
        }
        $outGeom = ['type' => 'Polygon', 'coordinates' => [$ring]];
    } else {
        return null;
    }
    return [
        'element_source' => 1,
        'name' => $name,
        'desc' => $desc,
        'resource' => [
            'type' => $type,
            'content' => [
                'type' => 'Feature',
                'properties' => [
                    'color' => $color,
                    'clampToGround' => true,
                ],
                'geometry' => $outGeom,
            ],
        ],
    ];
}

function flighthub_http_post(string $url, array $headers, array $body): array
{
    $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return ['ok' => false, 'status' => 0, 'error' => 'JSON encode failed', 'raw' => ''];
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'status' => $status, 'error' => $err !== '' ? $err : 'cURL failed', 'raw' => ''];
        }
        return ['ok' => true, 'status' => $status, 'error' => '', 'raw' => (string)$raw];
    }
    $headerStr = '';
    foreach ($headers as $h) {
        $headerStr .= $h . "\r\n";
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headerStr . "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 25,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])
        && preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    if ($raw === false) {
        return ['ok' => false, 'status' => $status, 'error' => 'HTTP request failed', 'raw' => ''];
    }
    return ['ok' => true, 'status' => $status, 'error' => '', 'raw' => (string)$raw];
}

$cfg = function_exists('map_flighthub_config')
    ? map_flighthub_config()
    : ['configured' => false, 'org_key' => '', 'project_uuid' => '', 'api_base' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    flighthub_json(200, [
        'ok' => true,
        'configured' => !empty($cfg['configured']),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flighthub_json(405, ['ok' => false, 'error' => 'Method not allowed']);
}

if (empty($cfg['configured'])) {
    flighthub_json(503, [
        'ok' => false,
        'error' => 'FlightHub 2 ist nicht konfiguriert (Organization Key / Projekt-UUID fehlen).',
    ]);
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    flighthub_json(400, ['ok' => false, 'error' => 'Empty body']);
}
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    flighthub_json(400, ['ok' => false, 'error' => 'Invalid JSON']);
}

$folderName = trim((string)($payload['name'] ?? 'Overlay'));
$incoming = $payload['elements'] ?? null;
if (!is_array($incoming) || !count($incoming)) {
    flighthub_json(400, ['ok' => false, 'error' => 'Keine Annotationen zum Senden.']);
}
if (count($incoming) > 400) {
    flighthub_json(400, ['ok' => false, 'error' => 'Zu viele Annotationen (max. 400).']);
}

$elements = [];
foreach ($incoming as $item) {
    $el = flighthub_sanitize_element($item, $folderName);
    if ($el !== null) {
        $elements[] = $el;
    }
}
if (!count($elements)) {
    flighthub_json(400, ['ok' => false, 'error' => 'Keine gültige Geometrie für Hub 2.']);
}

$url = $cfg['api_base'] . '/map/api/v1.0/projects/' . rawurlencode($cfg['project_uuid']) . '/elements';
$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
    'X-Organization-Key: ' . $cfg['org_key'],
    'X-User-Token: ' . $cfg['org_key'],
    'X-Project-Uuid: ' . $cfg['project_uuid'],
];

$created = 0;
$failed = [];
foreach ($elements as $idx => $element) {
    $res = flighthub_http_post($url, $headers, $element);
    $decoded = json_decode($res['raw'], true);
    $apiCode = is_array($decoded) && array_key_exists('code', $decoded) ? (int)$decoded['code'] : null;
    $okHttp = $res['ok'] && $res['status'] >= 200 && $res['status'] < 300;
    $okApi = ($apiCode === null && $okHttp) || $apiCode === 0;
    if ($okApi) {
        $created += 1;
        continue;
    }
    $msg = '';
    if (is_array($decoded)) {
        $msg = (string)($decoded['message'] ?? $decoded['msg'] ?? $decoded['error'] ?? '');
    }
    if ($msg === '') {
        $msg = $res['error'] !== '' ? $res['error'] : ('HTTP ' . $res['status']);
    }
    if ($res['status'] === 401 || $apiCode === 401) {
        flighthub_json(401, [
            'ok' => false,
            'error' => '401: Organization Key oder Projekt-UUID ungültig / ohne Berechtigung.',
            'created' => $created,
        ]);
    }
    $failed[] = [
        'index' => $idx,
        'name' => $element['name'] ?? '',
        'error' => $msg,
        'status' => $res['status'],
    ];
}

if ($created === 0) {
    $first = $failed[0]['error'] ?? 'Senden fehlgeschlagen';
    flighthub_json(502, [
        'ok' => false,
        'error' => $first,
        'created' => 0,
        'failed' => count($failed),
    ]);
}

flighthub_json(200, [
    'ok' => true,
    'created' => $created,
    'failed' => count($failed),
    'errors' => array_slice($failed, 0, 5),
]);
