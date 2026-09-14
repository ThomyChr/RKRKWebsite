<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

/**
 * Build a public HTTPS URL for a path on this host.
 */
function buildLocalPublicUrl(string $publicPath): string
{
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (
        $forwardedProto === 'https'
        || (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443')
    );
    // map.geo.admin.ch only loads HTTPS KML sources.
    $scheme = 'https';
    if (!$isHttps && $forwardedProto === 'http') {
        $scheme = 'http';
    }
    $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    if ($host === '') {
        return $publicPath;
    }
    return $scheme . '://' . $host . $publicPath;
}

/**
 * Parse geo.admin KML admin API JSON response into a public files URL.
 *
 * @param mixed $payload
 * @return array{ok:bool,url?:string,id?:string,error?:string,http?:int}
 */
function parseGeoAdminUploadResponse($payload, int $status): array
{
    if (!is_array($payload)) {
        return ['ok' => false, 'error' => 'geo.admin upload invalid JSON', 'http' => $status];
    }

    $kmlUrl = '';
    if (!empty($payload['links']['kml']) && is_string($payload['links']['kml'])) {
        $kmlUrl = $payload['links']['kml'];
    } elseif (!empty($payload['id']) && is_string($payload['id'])) {
        $kmlUrl = 'https://public.geo.admin.ch/api/kml/files/' . $payload['id'];
    }

    if ($status >= 200 && $status < 300 && $kmlUrl !== '') {
        return [
            'ok' => true,
            'url' => $kmlUrl,
            'id' => isset($payload['id']) ? (string)$payload['id'] : '',
        ];
    }

    $err = isset($payload['error']) ? (string)$payload['error'] : ('HTTP ' . $status);
    return ['ok' => false, 'error' => $err, 'http' => $status];
}

/**
 * Upload KML via PHP streams (no curl extension required).
 *
 * @return array{ok:bool,url?:string,id?:string,error?:string,http?:int}
 */
function uploadKmlToGeoAdminViaStream(string $endpoint, string $kml, string $author): array
{
    $boundary = '----rkrkBoundary' . bin2hex(random_bytes(8));
    $eol = "\r\n";
    $body = '';
    $body .= '--' . $boundary . $eol;
    $body .= 'Content-Disposition: form-data; name="author"' . $eol . $eol;
    $body .= $author . $eol;
    $body .= '--' . $boundary . $eol;
    $body .= 'Content-Disposition: form-data; name="kml"; filename="export.kml"' . $eol;
    $body .= 'Content-Type: application/vnd.google-earth.kml+xml' . $eol . $eol;
    $body .= $kml . $eol;
    $body .= '--' . $boundary . '--' . $eol;

    $headers = [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . (string)strlen($body),
        'Origin: https://map.geo.admin.ch',
        'Referer: https://map.geo.admin.ch/',
        'Accept: application/json',
    ];

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 45,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $ctx);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $hdrLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string)$hdrLine, $m)) {
                $status = (int)$m[1];
                break;
            }
        }
    }
    if ($response === false) {
        $err = error_get_last();
        $detail = is_array($err) && isset($err['message']) ? (string)$err['message'] : 'stream failed';
        return [
            'ok' => false,
            'error' => 'geo.admin upload network error (stream)',
            'detail' => $detail,
            'http' => $status,
        ];
    }
    return parseGeoAdminUploadResponse(json_decode((string)$response, true), $status);
}

/**
 * Upload KML to public.geo.admin.ch so the map viewer can load it reliably
 * (same hosting as drawings created inside map.geo.admin.ch).
 *
 * @return array{ok:bool,url?:string,id?:string,error?:string,http?:int}
 */
function uploadKmlToGeoAdmin(string $kml, string $author = 'rkrk-map'): array
{
    $endpoint = 'https://public.geo.admin.ch/api/kml/admin';

    if (function_exists('curl_init')) {
        $tmp = tempnam(sys_get_temp_dir(), 'rkrk_kml_');
        if ($tmp === false) {
            return uploadKmlToGeoAdminViaStream($endpoint, $kml, $author);
        }
        $tmpKml = $tmp . '.kml';
        @unlink($tmp);
        if (@file_put_contents($tmpKml, $kml) === false) {
            return uploadKmlToGeoAdminViaStream($endpoint, $kml, $author);
        }

        $ch = curl_init($endpoint);
        if ($ch === false) {
            @unlink($tmpKml);
            return uploadKmlToGeoAdminViaStream($endpoint, $kml, $author);
        }

        $cfile = class_exists('CURLFile')
            ? new CURLFile($tmpKml, 'application/vnd.google-earth.kml+xml', 'export.kml')
            : '@' . $tmpKml . ';type=application/vnd.google-earth.kml+xml';

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Origin: https://map.geo.admin.ch',
                'Referer: https://map.geo.admin.ch/',
            ],
            CURLOPT_POSTFIELDS => [
                'kml' => $cfile,
                'author' => $author,
            ],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tmpKml);

        if ($errno === 0 && $body !== false) {
            return parseGeoAdminUploadResponse(json_decode((string)$body, true), $status);
        }
        // Fall through to stream transport.
    }

    return uploadKmlToGeoAdminViaStream($endpoint, $kml, $author);
}

require_once __DIR__ . '/map_env.php';
require_once __DIR__ . '/map_retention.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);


$raw = file_get_contents('php://input');
$body = json_decode($raw ?? '', true);
if (!is_array($body)) {
    respond(400, ['ok' => false, 'error' => 'Invalid JSON body']);
}

$name = trim((string)($body['name'] ?? ''));
$kml = (string)($body['kml'] ?? '');
// Prefer server-side env detection; accept client on_rkrs as additional hint.
$onRkrsClient = filter_var($body['on_rkrs'] ?? false, FILTER_VALIDATE_BOOLEAN);
$onRkrs = map_is_rkrs_env() || $onRkrsClient;

if ($kml === '' || stripos($kml, '<kml') === false) {
    respond(400, ['ok' => false, 'error' => 'KML payload is required']);
}

$safeName = preg_replace('/[^A-Za-z0-9 _.-]/', '_', $name);
$safeName = trim((string)$safeName);
if ($safeName === '') {
    $safeName = 'object_export';
}
if (substr(strtolower($safeName), -4) !== '.kml') {
    $safeName .= '.kml';
}
$safeName = ltrim($safeName, '.');

// Always prepend creation timestamp to filename.
$timestampPrefix = gmdate('Ymd_His');
$safeName = $timestampPrefix . '__' . $safeName;

$baseExportDir = $onRkrs
    ? (map_is_tools_host() ? map_rkrs_local_data_dir() : map_rkrs_preferred_data_dir())
    : map_legacy_data_dir();
$targetDir = map_ensure_dir($baseExportDir);
$publicRelativeBase = map_public_data_base($targetDir);
$usedFallback = false;

$fallbackBaseDir = __DIR__ . '/kml-exports';
$fallbackTargetDir = rtrim($fallbackBaseDir, '/\\');
$altRkrsDir = map_is_tools_host() ? map_rkrs_preferred_data_dir() : map_rkrs_local_data_dir();

if ($targetDir === null) {
    if ($onRkrs) {
        $targetDir = map_ensure_dir($altRkrsDir);
        if ($targetDir !== null) {
            $publicRelativeBase = map_public_data_base($targetDir);
        }
    }
}
if ($targetDir === null) {
    $targetDir = $fallbackTargetDir;
    $publicRelativeBase = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/') . '/kml-exports';
    $usedFallback = true;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        respond(500, ['ok' => false, 'error' => 'Cannot create KML export directory']);
    }
}

map_retention_run_all();

$filePath = $targetDir . '/' . $safeName;
$written = @file_put_contents($filePath, $kml);
if ($written === false) {
    $targetDir = $fallbackTargetDir;
    $publicRelativeBase = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/') . '/kml-exports';
    $usedFallback = true;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        respond(500, ['ok' => false, 'error' => 'Failed to write KML file (fallback directory unavailable)']);
    }
    $filePath = $targetDir . '/' . $safeName;
    $written = @file_put_contents($filePath, $kml);
    if ($written === false) {
        $dirWritable = is_writable($targetDir) ? 'yes' : 'no';
        respond(500, [
            'ok' => false,
            'error' => 'Failed to write KML file',
            'details' => [
                'target_dir' => $targetDir,
                'dir_writable' => $dirWritable
            ]
        ]);
    }
}

$scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
// Path-style URL only (no ?/&): those break map.geo.admin.ch layers= parsing.
$servePath = $scriptDir . '/serve_kml.php/' . rawurlencode($safeName);
$localDirectUrl = buildLocalPublicUrl(rtrim($publicRelativeBase, '/') . '/' . rawurlencode($safeName));
$localServeUrl = buildLocalPublicUrl($servePath);

// Prefer hosting on public.geo.admin.ch (stable CORS/HTTPS for the map viewer).
$geoUpload = uploadKmlToGeoAdmin($kml, 'rkrk-map');
// Lokal immer serve_kml.php (CORS) — nie Rohdatei ohne CORS (tools.rehkitzrettung.ch
// sonst oft weisse Polygone / Standard-Pins in map.geo.admin.ch).
$publicUrl = $localServeUrl;
$hosting = 'local_serve';
if (!empty($geoUpload['ok']) && !empty($geoUpload['url'])) {
    $publicUrl = (string)$geoUpload['url'];
    $hosting = 'geoadmin';
}

respond(200, [
    'ok' => true,
    'name' => $safeName,
    'storage_path' => $targetDir,
    'public_url' => $publicUrl,
    'local_url' => $localServeUrl,
    'local_direct_url' => $localDirectUrl,
    'hosting' => $hosting,
    'geoadmin_upload' => $geoUpload,
    'used_fallback_dir' => $usedFallback,
]);
