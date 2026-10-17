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

$targetDir = mapll_data_dir(false);
if ($targetDir === null || !is_dir($targetDir)) {
    respond(200, ['ok' => true, 'drawings' => []]);
}

$files = glob($targetDir . '/*.json');
if ($files === false) {
    respond(500, ['ok' => false, 'error' => 'Failed to read directory']);
}

usort($files, static function (string $a, string $b): int {
    return filemtime($b) <=> filemtime($a);
});

$drawings = [];
foreach ($files as $file) {
    $drawings[] = pathinfo($file, PATHINFO_FILENAME);
}

respond(200, ['ok' => true, 'drawings' => $drawings]);
