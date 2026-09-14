<?php
declare(strict_types=1);

/**
 * Serve a previously saved KML export with CORS + correct Content-Type
 * so map.geo.admin.ch can load it as layers=KML|<url>.
 *
 * Accepts:
 *   /serve_kml.php/20260721__export.kml
 *   /serve_kml.php?f=20260721__export.kml&on_rkrs=1
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Max-Age: 86400');
header('Cache-Control: no-store, max-age=0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed';
    exit;
}

$rawName = '';
$pathInfo = (string)($_SERVER['PATH_INFO'] ?? '');
if ($pathInfo !== '') {
    $rawName = basename(str_replace('\\', '/', $pathInfo));
} else {
    // Some Synology setups put path after script in REQUEST_URI without PATH_INFO.
    $scriptName = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'serve_kml.php'));
    $reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $qPos = strpos($reqUri, '?');
    if ($qPos !== false) {
        $reqUri = substr($reqUri, 0, $qPos);
    }
    $marker = '/' . $scriptName . '/';
    $idx = stripos($reqUri, $marker);
    if ($idx !== false) {
        $rawName = basename(substr($reqUri, $idx + strlen($marker)));
    }
}
if ($rawName === '') {
    $rawName = (string)($_GET['f'] ?? '');
}

$safeName = basename(str_replace(["\0", '\\'], '', $rawName));
$safeName = preg_replace('/[^A-Za-z0-9 _.-]/', '_', $safeName) ?? '';
$safeName = trim(rawurldecode($safeName));
if ($safeName === '' || substr(strtolower($safeName), -4) !== '.kml') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid KML filename';
    exit;
}

require_once __DIR__ . '/map_env.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);


$onRkrsParam = $_GET['on_rkrs'] ?? null;
if ($onRkrsParam === null) {
    $preferRkrs = map_is_rkrs_env();
} else {
    $preferRkrs = filter_var($onRkrsParam, FILTER_VALIDATE_BOOLEAN);
}

$candidates = [];
$rkrsDirs = map_rkrs_write_dirs();
$legacyDir = map_legacy_data_dir();
$dirList = [];
if ($preferRkrs) {
    foreach ($rkrsDirs as $dir) {
        $dirList[] = rtrim($dir, '/\\');
    }
    $dirList[] = rtrim($legacyDir, '/\\');
} else {
    $dirList[] = rtrim($legacyDir, '/\\');
    foreach ($rkrsDirs as $dir) {
        $dirList[] = rtrim($dir, '/\\');
    }
}
$dirList[] = __DIR__ . '/kml-exports';

foreach ($dirList as $dir) {
    $candidates[] = $dir . '/' . $safeName;
    $candidates[] = $dir . '/archive/' . $safeName;
}

$filePath = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate) && is_readable($candidate)) {
        $filePath = $candidate;
        break;
    }
}

if ($filePath === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'KML not found';
    exit;
}

$size = filesize($filePath);
header('Content-Type: application/vnd.google-earth.kml+xml; charset=utf-8');
header('Content-Disposition: inline; filename="' . $safeName . '"');
if ($size !== false) {
    header('Content-Length: ' . (string)$size);
}

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    http_response_code(200);
    exit;
}

readfile($filePath);
exit;
