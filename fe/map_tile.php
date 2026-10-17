<?php
/**
 * Same-origin tile proxy so the field editor can export a JPEG.
 * Direct swisstopo tiles omit CORS headers and taint the map canvas.
 */
$layers = array(
    'swissimage' => 'ch.swisstopo.swissimage',
);
$layer = (string)($_GET['layer'] ?? '');
$z = filter_var($_GET['z'] ?? null, FILTER_VALIDATE_INT);
$x = filter_var($_GET['x'] ?? null, FILTER_VALIDATE_INT);
$y = filter_var($_GET['y'] ?? null, FILTER_VALIDATE_INT);
if (!isset($layers[$layer]) || $z === false || $x === false || $y === false || $z < 0 || $z > 19 || $x < 0 || $y < 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'bad tile';
    exit;
}
$url = 'https://wmts.geo.admin.ch/1.0.0/' . $layers[$layer] . '/default/current/3857/' . $z . '/' . $x . '/' . $y . '.jpeg';
$body = false;
$type = 'image/jpeg';
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => 'RKRK-Feldeditor',
    ));
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $got = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if ($body === false || $code !== 200) {
        $body = false;
    } elseif ($got !== '') {
        $type = $got;
    }
}
if ($body === false && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(array('http' => array('timeout' => 12, 'header' => "User-Agent: RKRK-Feldeditor\r\n")));
    $raw = @file_get_contents($url, false, $ctx);
    if (is_string($raw) && $raw !== '') {
        $body = $raw;
    }
}
if (!is_string($body) || $body === '') {
    http_response_code(502);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'tile missing';
    exit;
}
header('Content-Type: ' . (stripos($type, 'image/') === 0 ? $type : 'image/jpeg'));
header('Cache-Control: public, max-age=86400');
echo $body;
