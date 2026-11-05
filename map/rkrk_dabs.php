<?php
/**
 * Local DABS entry for this map folder (test27/map, rkrs/map, or tools /map/).
 * Always loads the core beside this file. A straight copy must not jump to a
 * parent rkrk_dabs.php or a scripts/ directory that is not part of the copy.
 */
declare(strict_types=1);

$core = __DIR__ . DIRECTORY_SEPARATOR . 'rkrk_dabs_core.php';
if (is_file($core)) {
	require_once $core;
	rkrk_dabs_emit();
	exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(array(
	'ok' => false,
	'error' => 'DABS-API nicht verfügbar (rkrk_dabs_core.php fehlt).',
	'features' => array(),
	'meta' => array(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
