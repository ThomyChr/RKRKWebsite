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

$targetDir = mapll_data_dir(true);
if ($targetDir === null) {
    $targetDir = mapll_rkrs_local_data_dir();
}

$resolved = realpath($targetDir);
$displayPath = $resolved !== false ? $resolved : $targetDir;

respond(200, [
    'ok' => true,
    'storage_path' => $displayPath,
    'exists' => is_dir($targetDir),
    'writable' => is_writable($targetDir)
]);
