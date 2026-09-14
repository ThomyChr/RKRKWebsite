<?php
declare(strict_types=1);

/**
 * Build DJI Pilot 2 compatible MBTiles (overlay, PNG/RGBA, TMS tile_row).
 * Schema matches common DJI custom layers (e.g. SAR_*.mbtiles):
 *   metadata(name,value) + tiles(zoom_level,tile_column,tile_row,tile_data)
 *   UNIQUE INDEX tile_index(zoom_level, tile_column, tile_row)
 *
 * POST JSON:
 * {
 *   "name": "Layer",
 *   "description": "...",
 *   "bounds": "w,s,e,n",
 *   "center": "lon,lat,z",
 *   "minzoom": 14,
 *   "maxzoom": 18,
 *   "tiles": [ { "z":14, "x":1, "y":2, "png":"<base64>" }, ... ]
 * }
 * y is TMS row (MBTiles convention).
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/map_env.php';

function mbtiles_json_error(int $status, string $msg): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mbtiles_json_error(405, 'Method not allowed');
}

if (!class_exists('SQLite3')) {
    mbtiles_json_error(500, 'SQLite3 extension missing on server');
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    mbtiles_json_error(400, 'Empty body');
}
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    mbtiles_json_error(400, 'Invalid JSON');
}

$tiles = $payload['tiles'] ?? null;
if (!is_array($tiles) || !count($tiles)) {
    mbtiles_json_error(400, 'No tiles');
}
if (count($tiles) > 8000) {
    mbtiles_json_error(400, 'Too many tiles (max 8000)');
}

$rkrs = !empty($payload['on_rkrs']);
if (function_exists('map_is_rkrs_env')) {
    $rkrs = $rkrs || map_is_rkrs_env();
}
$product = $rkrs ? 'RKRS Overlay-Editor' : 'RKRK Overlay-Editor';
$name = trim((string)($payload['name'] ?? $product));
if ($name === '') {
    $name = $product;
}
$safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: 'overlay';
$safeName = substr($safeName, 0, 48);

$description = trim((string)($payload['description'] ?? ($product . ' objects for DJI Pilot 2')));
$bounds = trim((string)($payload['bounds'] ?? ''));
$center = trim((string)($payload['center'] ?? ''));
$minzoom = (int)($payload['minzoom'] ?? 0);
$maxzoom = (int)($payload['maxzoom'] ?? 22);
if ($minzoom < 0) {
    $minzoom = 0;
}
if ($maxzoom > 22) {
    $maxzoom = 22;
}
if ($maxzoom < $minzoom) {
    $maxzoom = $minzoom;
}

$tmp = tempnam(sys_get_temp_dir(), 'mbtiles_');
if ($tmp === false) {
    mbtiles_json_error(500, 'Temp file failed');
}
$dbPath = $tmp . '.mbtiles';
@unlink($tmp);

try {
    $db = new SQLite3($dbPath);
    $db->exec('PRAGMA synchronous = OFF');
    $db->exec('PRAGMA journal_mode = MEMORY');
    $db->exec('CREATE TABLE metadata (name TEXT, value TEXT)');
    $db->exec('CREATE TABLE tiles (zoom_level INTEGER, tile_column INTEGER, tile_row INTEGER, tile_data BLOB)');
    $db->exec('CREATE UNIQUE INDEX tile_index ON tiles (zoom_level, tile_column, tile_row)');

    $insMeta = $db->prepare('INSERT INTO metadata (name, value) VALUES (:n, :v)');
    $meta = [
        'name' => $name,
        'description' => $description,
        'format' => 'png',
        'type' => 'overlay',
        'version' => '1.1',
        'minzoom' => (string)$minzoom,
        'maxzoom' => (string)$maxzoom,
    ];
    if ($bounds !== '') {
        $meta['bounds'] = $bounds;
    }
    if ($center !== '') {
        $meta['center'] = $center;
    }
    foreach ($meta as $k => $v) {
        $insMeta->bindValue(':n', $k, SQLITE3_TEXT);
        $insMeta->bindValue(':v', (string)$v, SQLITE3_TEXT);
        $insMeta->execute();
        $insMeta->reset();
    }

    $insTile = $db->prepare(
        'INSERT OR REPLACE INTO tiles (zoom_level, tile_column, tile_row, tile_data) VALUES (:z, :x, :y, :d)'
    );
    $written = 0;
    foreach ($tiles as $t) {
        if (!is_array($t)) {
            continue;
        }
        $z = (int)($t['z'] ?? -1);
        $x = (int)($t['x'] ?? -1);
        $y = (int)($t['y'] ?? -1);
        $b64 = (string)($t['png'] ?? '');
        if ($z < 0 || $x < 0 || $y < 0 || $b64 === '') {
            continue;
        }
        if (strpos($b64, 'data:') === 0) {
            $comma = strpos($b64, ',');
            $b64 = $comma === false ? '' : substr($b64, $comma + 1);
        }
        $bin = base64_decode($b64, true);
        if ($bin === false || strlen($bin) < 8) {
            continue;
        }
        // PNG magic
        if (substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            continue;
        }
        $insTile->bindValue(':z', $z, SQLITE3_INTEGER);
        $insTile->bindValue(':x', $x, SQLITE3_INTEGER);
        $insTile->bindValue(':y', $y, SQLITE3_INTEGER);
        $insTile->bindValue(':d', $bin, SQLITE3_BLOB);
        $insTile->execute();
        $insTile->reset();
        $written++;
    }
    $db->close();

    if ($written < 1) {
        @unlink($dbPath);
        mbtiles_json_error(400, 'No valid PNG tiles written');
    }

    $filename = $safeName . '_dji_pilot2.mbtiles';
    $size = filesize($dbPath);
    header('Content-Type: application/x-sqlite3');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    if ($size !== false) {
        header('Content-Length: ' . (string)$size);
    }
    header('X-RKRK-Mbtiles-Tiles: ' . (string)$written);
    readfile($dbPath);
    @unlink($dbPath);
    exit;
} catch (Throwable $e) {
    if (isset($db) && $db instanceof SQLite3) {
        try {
            $db->close();
        } catch (Throwable $_) {
        }
    }
    @unlink($dbPath);
    mbtiles_json_error(500, 'MBTiles build failed: ' . $e->getMessage());
}
