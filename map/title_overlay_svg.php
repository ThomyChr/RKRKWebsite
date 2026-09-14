<?php
declare(strict_types=1);

/**
 * SVG banner for KML ScreenOverlay (map.geo.admin.ch top-left title).
 * GET ?t=Projekttitel
 */

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');

$raw = isset($_GET['t']) ? (string)$_GET['t'] : '';
$raw = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
if ($raw === '') {
    $raw = 'Titel';
}
if (function_exists('mb_substr')) {
    $raw = mb_substr($raw, 0, 120);
} else {
    $raw = substr($raw, 0, 120);
}

$text = htmlspecialchars($raw, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$len = function_exists('mb_strlen') ? mb_strlen($raw) : strlen($raw);
$width = max(120, min(520, 28 + (int)round($len * 8.2)));
$height = 36;

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
echo '<rect x="0.5" y="0.5" width="' . ($width - 1) . '" height="' . ($height - 1) . '" rx="8" ry="8" fill="rgba(255,255,255,0.94)" stroke="rgba(0,0,0,0.28)" stroke-width="1"/>';
echo '<text x="12" y="23" font-family="Arial, Helvetica, sans-serif" font-size="13" font-weight="600" fill="#111827">' . $text . '</text>';
echo '</svg>';
