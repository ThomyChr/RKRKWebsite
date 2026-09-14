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
    $safeName = 'drawing';
}

$targetDir = mapll_data_dir(true);
if ($targetDir === null || !is_dir($targetDir)) {
    respond(500, ['ok' => false, 'error' => 'Cannot create target directory']);
}

$filePath = $targetDir . '/' . $safeName . '.json';
$payload = [
    'name' => $safeName,
    'saved_at' => gmdate('c'),
    'drawing' => $drawing
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    respond(500, ['ok' => false, 'error' => 'Failed to encode JSON']);
}

if (file_put_contents($filePath, $json) === false) {
    respond(500, [
        'ok' => false,
        'error' => 'Failed to write file',
        'storage_path' => $targetDir
    ]);
}

respond(200, [
    'ok' => true,
    'name' => $safeName,
    'storage_path' => $targetDir
]);
