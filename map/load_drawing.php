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

$name = trim((string)($_GET['name'] ?? ''));
if ($name === '') {
    respond(400, ['ok' => false, 'error' => 'Name is required']);
}

$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '_', $name);
$safeName = trim((string)$safeName);
if ($safeName === '') {
    respond(400, ['ok' => false, 'error' => 'Invalid name']);
}

$candidates = map_data_dir_candidates(false);
if ($candidates === []) {
    respond(404, ['ok' => false, 'error' => 'Storage folder not found']);
}

$filePath = map_retention_find_file($candidates, $safeName . '.json');

if ($filePath === null) {
    respond(404, ['ok' => false, 'error' => 'Drawing not found']);
}

$raw = file_get_contents($filePath);
if ($raw === false) {
    respond(500, ['ok' => false, 'error' => 'Failed to read drawing file']);
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['drawing']) || !is_array($payload['drawing'])) {
    respond(500, ['ok' => false, 'error' => 'Invalid drawing file']);
}

respond(200, [
    'ok' => true,
    'name' => $safeName,
    'saved_at' => $payload['saved_at'] ?? null,
    'drawing' => $payload['drawing']
]);
