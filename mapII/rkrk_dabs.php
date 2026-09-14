<?php
/**
 * Local DABS entry so mapII/ can run standalone (rkrs/mapII) or inside test27.
 * Prefers the site-level API when present; otherwise scrapes without site chrome.
 */
declare(strict_types=1);

$parent = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rkrk_dabs.php';
if (is_file($parent) && realpath($parent) !== realpath(__FILE__)) {
	require $parent;
	exit;
}

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
