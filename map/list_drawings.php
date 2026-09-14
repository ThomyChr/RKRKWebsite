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

try { map_retention_run_all(); } catch (Throwable $e) { /* noop */ }

$candidates = map_data_dir_candidates(false);
if ($candidates === []) {
    respond(200, ['ok' => true, 'drawings' => [], 'retention' => ['archive_days' => 30, 'delete_days' => 90]]);
}

/** @var array<string, int> $mtimeByName */
$mtimeByName = [];
foreach ($candidates as $dir) {
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
    if ($files === false) {
        continue;
    }
    foreach ($files as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        if ($name === '') {
            continue;
        }
        $mtime = (int)@filemtime($file);
        if (!isset($mtimeByName[$name]) || $mtime > $mtimeByName[$name]) {
            $mtimeByName[$name] = $mtime;
        }
    }
}

uksort($mtimeByName, static function (string $a, string $b) use ($mtimeByName): int {
    return $mtimeByName[$b] <=> $mtimeByName[$a];
});

respond(200, [
    'ok' => true,
    'drawings' => array_keys($mtimeByName),
    'retention' => [
        'archive_days' => 30,
        'delete_days' => 90,
        'note' => 'Zeichnungen und KMLs: nach 30 Tagen Archiv, nach 90 Tagen Löschung'
    ]
]);
