<?php
/**
 * Web App Manifest for Android Chrome (192px icons). Slot follows the request path.
 */
header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

if (!function_exists('rkrk_str')) {
	function rkrk_str($s) {
		return (string)$s;
	}
}

require_once __DIR__ . '/rkrk_favicon.php';

$a = rkrk_favicon_assets();
$dir = rtrim(str_replace('\\', '/', (string)($a['dir'] ?? '')), '/') . '/';
$slot = (string)($a['slot'] ?? 'pub');
$names = array(
	'test' => 'RKRK Test',
	'v2' => 'RKRK Intern',
	'test27' => 'RKRK Test27',
	'pub' => 'RKRK',
	'extern' => 'RKRK Extern',
	'partner' => 'RKRK Partner',
);
$short = array(
	'test' => 'Test',
	'v2' => 'Intern',
	'test27' => 'Test27',
	'pub' => 'RKRK',
	'extern' => 'Extern',
	'partner' => 'Partner',
);
$scope = function_exists('rkrk_favicon_site_web_prefix') ? rkrk_favicon_site_web_prefix() : '';
$start = $scope;
$app = trim((string)($_GET['app'] ?? ''));
if ($start !== '') {
	$start .= ($app === 'rc') ? 'rkrk_rc_access.php' : 'rkrk.php';
} else {
	$scope = '/';
	$start = '.';
}
if ($app === 'rc' && $scope !== '/') {
	$names[$slot] = 'RKRK RC';
	$short[$slot] = 'RC';
	$scopeId = $start;
} else {
	$scopeId = $scope;
}

$icons = array();
foreach (array(512, 192) as $sz) {
	$icons[] = array(
		'src' => $dir . 'android-icon-' . $sz . 'x' . $sz . '.png',
		'sizes' => $sz . 'x' . $sz,
		'type' => 'image/png',
		'purpose' => 'any',
	);
}

$manifest = array(
	'name' => $names[$slot] ?? 'RKRK',
	'short_name' => $short[$slot] ?? 'RKRK',
	'id' => $scopeId,
	'scope' => $scope,
	'display' => 'standalone',
	'start_url' => $start,
	'theme_color' => '#ffffff',
	'background_color' => '#ffffff',
	'icons' => $icons,
);

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo "\n";
