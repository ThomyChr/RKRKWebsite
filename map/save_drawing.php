<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/map_env.php';
require_once __DIR__ . '/map_retention.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);

function respond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?? '', true);
if (!is_array($body)) {
    respond(400, ['ok' => false, 'error' => 'Invalid JSON body']);
}

$name = trim((string)($body['name'] ?? ''));
$drawing = $body['drawing'] ?? null;
if ($name === '') {
    respond(400, ['ok' => false, 'error' => 'Name is required']);
}
if (!is_array($drawing)) {
    respond(400, ['ok' => false, 'error' => 'Drawing payload is required']);
}

$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '_', $name);
$safeName = trim((string)$safeName);
if ($safeName === '') {
    respond(400, ['ok' => false, 'error' => 'Invalid name']);
}

$targetDir = map_data_dir(true);
if ($targetDir === null || !is_dir($targetDir)) {
    respond(500, ['ok' => false, 'error' => 'Storage folder not found']);
}

try { map_retention_run_all(); } catch (Throwable $e) { /* noop */ }

$filePath = $targetDir . DIRECTORY_SEPARATOR . $safeName . '.json';
$payload = [
    'name' => $safeName,
    'saved_at' => gmdate('c'),
    'drawing' => $drawing
];
$written = @file_put_contents(
    $filePath,
    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);
if ($written === false) {
    respond(500, ['ok' => false, 'error' => 'Failed to write drawing file']);
}

respond(200, [
    'ok' => true,
    'name' => $safeName,
    'storage_path' => $targetDir,
    'on_rkrs' => map_is_rkrs_env()
]);
