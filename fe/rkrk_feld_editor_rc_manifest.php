<?php
header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=300');

if (!function_exists('rkrk_str')) {
    function rkrk_str($s) {
        return (string)$s;
    }
}

require_once dirname(__DIR__) . '/scripts/rkrk_favicon.php';

$assets = function_exists('rkrk_favicon_assets') ? rkrk_favicon_assets() : array();
$icons = array();
foreach (array('png192' => '192x192', 'png96' => '96x96', 'png48' => '48x48', 'apple' => '180x180', 'png' => '32x32') as $key => $size) {
    $src = trim((string)($assets[$key] ?? ''));
    if ($src === '') {
        continue;
    }
    $icons[] = array(
        'src' => $src,
        'sizes' => $size,
        'type' => 'image/png',
        'purpose' => 'any',
    );
}

$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
$start = ($dir === '' ? '' : $dir) . '/rkrk_feld_editor_rc.php';

echo json_encode(array(
    'id' => $start,
    'name' => 'Feld Editor RC',
    'short_name' => 'Feld RC',
    'description' => 'Feld Editor für die DJI-Fernsteuerung',
    'start_url' => $start,
    'scope' => ($dir === '' ? '/' : $dir . '/'),
    'display' => 'standalone',
    'background_color' => '#121814',
    'theme_color' => '#121814',
    'icons' => $icons,
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo "\n";
