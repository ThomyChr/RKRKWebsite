<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/map_env.php';
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

$targetDir = map_data_dir(true);
if ($targetDir === null) {
    respond(500, [
        'ok' => false,
        'error' => 'Storage folder not found',
        'on_rkrs' => map_is_rkrs_env()
    ]);
}

respond(200, [
    'ok' => true,
    'storage_path' => $targetDir,
    'writable' => is_writable($targetDir),
    'on_rkrs' => map_is_rkrs_env(),
    'public_base' => map_public_data_base($targetDir)
]);
