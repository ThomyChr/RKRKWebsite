<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/mapll_env.php';
mapll_require_tools_auth();

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

$targetDir = mapll_data_dir(false);
if ($targetDir === null || !is_dir($targetDir)) {
    respond(404, ['ok' => false, 'error' => 'Storage folder not found']);
}

$filePath = $targetDir . '/' . $safeName . '.json';
if (!is_file($filePath)) {
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
