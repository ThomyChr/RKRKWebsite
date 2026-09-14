<?php
/**
 * Shared helpers for Eigene Layer (RKRK).
 * Catalog truth: rkrk_kmlfile_settings.json via rkrk_catalog_io.
 * Colors / ui_defaults / GeoJSON: rkrk/webdaten/mylayer/
 * KML sources: kmlfiles/referenzen/ and Missionen/kmlfiles/referenzen
 */

function ref_layers_region_keys()
{
    global $RegionSettings;
    $keys = array();
    if (is_array($RegionSettings ?? null)) {
        foreach ($RegionSettings as $k => $row) {
            $reg = '';
            if (is_array($row) && isset($row['Reg'])) {
                $reg = trim((string)$row['Reg']);
            } elseif (!is_int($k)) {
                $reg = trim((string)$k);
            }
            if ($reg === '' || strcasecmp($reg, 'NO') === 0) {
                continue;
            }
            $keys[] = $reg;
        }
    }
    $keys[] = 'ALL';
    $keys[] = 'leer';
    $seen = array();
    $out = array();
    foreach ($keys as $k) {
        if (!isset($seen[$k])) {
            $seen[$k] = true;
            $out[] = $k;
        }
    }
    return $out;
}

function ref_layers_kat_keys()
{
    return array(
        'FZ', 'POI', 'Gde', 'JG', 'Kanton', 'HSP', 'SUN', 'WF', 'SORA',
        'GdeLabel', 'Ort', 'JGLabel', 'KtLabel', 'Alert', 'DEV',
    );
}

function ref_layers_group_defs()
{
    $labels = array(
        'FZ' => 'Flugzonen',
        'POI' => 'POI / Flugplaetze',
        'Gde' => 'Gemeindegrenzen',
        'JG' => 'Jagdgebiete',
        'Kanton' => 'Kantonsgrenzen',
        'HSP' => 'Hochspannungsleitungen',
        'SUN' => 'Morgensonne',
        'WF' => 'Wasserflaechen',
        'SORA' => 'SORA-Zonen',
        'GdeLabel' => 'Gemeinde-Labels',
        'Ort' => 'Hauptorte',
        'JGLabel' => 'JG-Labels',
        'KtLabel' => 'Kanton-Labels',
        'Alert' => 'Flugeinschraenkungen',
        'DEV' => 'DEV',
    );
    $out = array();
    foreach (ref_layers_kat_keys() as $k) {
        $out[] = array('key' => $k, 'label' => $labels[$k] ?? $k, 'kat' => array($k));
    }
    return $out;
}

function ref_layers_settings_path($forWrite = false)
{
    global $xmlMissionLocation, $poi_settings_file;
    if (!function_exists('rkrk_catalog_json_path') || empty($xmlMissionLocation) || empty($poi_settings_file)) {
        return null;
    }
    $p = rkrk_catalog_json_path($xmlMissionLocation . $poi_settings_file);
    return ($p !== '') ? $p : null;
}

function ref_layers_cache_dirs($create = true)
{
    global $jsonMissionLocation;
    $dirs = array();
    $base = isset($jsonMissionLocation) ? rtrim(str_replace('\\', '/', (string)$jsonMissionLocation), '/') : '';
    if ($base === '') {
        return $dirs;
    }
    $dir = $base . '/mylayer';
    if ($create && !is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (is_dir($dir)) {
        $dirs[] = $dir;
    }
    return $dirs;
}

function ref_layers_intermediate_dir($create = true)
{
    $dirs = ref_layers_cache_dirs($create);
    return $dirs ? $dirs[0] : null;
}

function ref_layers_cache_path($groupKey, $forWrite = false)
{
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $groupKey);
    if ($safe === '') {
        return null;
    }
    $dirs = ref_layers_cache_dirs(true);
    if ($dirs === array()) {
        return null;
    }
    $name = $safe . '.geojson';
    if (!$forWrite) {
        foreach ($dirs as $dir) {
            $p = $dir . '/' . $name;
            if (is_file($p) && filesize($p) > 20) {
                return $p;
            }
        }
    }
    return $dirs[0] . '/' . $name;
}

function ref_layers_kml_source_dirs()
{
    global $kmlReferenceFilelocations, $RKRKLocalLoactionMissionFiles;
    $candidates = array();
    if (!empty($kmlReferenceFilelocations)) {
        $candidates[] = rtrim(str_replace('\\', '/', (string)$kmlReferenceFilelocations), '/');
    }
    if (!empty($RKRKLocalLoactionMissionFiles)) {
        $candidates[] = rtrim(str_replace('\\', '/', (string)$RKRKLocalLoactionMissionFiles), '/') . '/kmlfiles/referenzen';
    }
    $out = array();
    foreach ($candidates as $d) {
        if ($d !== '' && is_dir($d) && !in_array($d, $out, true)) {
            $out[] = $d;
        }
    }
    return $out;
}

function ref_layers_find_kml($name)
{
    $base = preg_replace('/\.kml$/i', '', trim($name));
    foreach (ref_layers_kml_source_dirs() as $dir) {
        $p = $dir . '/' . $base . '.kml';
        if (is_file($p)) {
            return $p;
        }
    }
    return null;
}

function ref_layers_mission_settings_xml()
{
    global $xmlMissionLocation, $poi_settings_file;
    if (empty($xmlMissionLocation) || empty($poi_settings_file)) {
        return null;
    }
    $p = $xmlMissionLocation . $poi_settings_file;
    return is_file($p) ? $p : null;
}

function ref_layers_norm_file_row($f)
{
    if (!is_array($f)) {
        return null;
    }
    $name = trim((string)($f['name'] ?? ''));
    if ($name === '') {
        return null;
    }
    $region = trim((string)($f['region'] ?? $f['Region'] ?? ''));
    $kat = trim((string)($f['kat'] ?? $f['Kat'] ?? 'POI'));
    if ($kat === '') {
        $kat = 'POI';
    }
    $aktivIn = $f['aktiv'] ?? $f['Aktiv'] ?? false;
    if (is_string($aktivIn)) {
        $aktiv = (strcasecmp(trim($aktivIn), 'Ja') === 0);
    } else {
        $aktiv = !empty($aktivIn);
    }
    $kml = ref_layers_find_kml($name);
    return array(
        'name' => $name,
        'region' => $region,
        'kat' => $kat,
        'aktiv' => $aktiv,
        'kml_exists' => $kml !== null,
        'kml_path' => $kml,
    );
}

function ref_layers_load_settings_from_xml($xmlPath)
{
    $sx = @simplexml_load_file($xmlPath);
    if ($sx === false) {
        return array();
    }
    $rows = array();
    foreach ($sx->File as $file) {
        $row = ref_layers_norm_file_row(array(
            'name' => (string)($file->name ?? ''),
            'Region' => (string)($file->Region ?? ''),
            'Kat' => (string)($file->Kat ?? 'POI'),
            'Aktiv' => (string)($file->Aktiv ?? ''),
        ));
        if ($row) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function ref_layers_files_from_payload($payload)
{
    if (!is_array($payload)) {
        return array();
    }
    if (function_exists('rkrk_catalog_unwrap')) {
        $payload = rkrk_catalog_unwrap($payload);
    }
    $list = array();
    if (isset($payload['data']['File'])) {
        $list = $payload['data']['File'];
    } elseif (isset($payload['files']) && is_array($payload['files'])) {
        $list = $payload['files'];
    }
    if (!is_array($list) || $list === array()) {
        return array();
    }
    if (isset($list['name']) || isset($list['Region']) || isset($list['Kat'])) {
        $list = array($list);
    }
    $rows = array();
    foreach ($list as $f) {
        $row = ref_layers_norm_file_row($f);
        if ($row) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function ref_layers_load_settings()
{
    $path = ref_layers_settings_path();
    if ($path && is_file($path)) {
        $raw = function_exists('rkrk_read_json_file')
            ? rkrk_read_json_file($path)
            : json_decode((string)file_get_contents($path), true);
        $rows = ref_layers_files_from_payload($raw);
        if ($rows) {
            return $rows;
        }
    }
    $xml = ref_layers_mission_settings_xml();
    if ($xml) {
        return ref_layers_load_settings_from_xml($xml);
    }
    $rows = array();
    foreach (ref_layers_kml_source_dirs() as $dir) {
        foreach (glob($dir . '/*.kml') ?: array() as $p) {
            $name = basename($p, '.kml');
            $rows[$name] = array(
                'name' => $name,
                'region' => 'ALL,',
                'kat' => 'POI',
                'aktiv' => false,
                'kml_exists' => true,
                'kml_path' => $p,
            );
        }
    }
    return array_values($rows);
}

function ref_layers_save_settings($files)
{
    global $xmlMissionLocation, $poi_settings_file;
    if (!function_exists('rkrk_catalog_write_json') || empty($xmlMissionLocation) || empty($poi_settings_file)) {
        return false;
    }
    $out = array();
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $name = trim((string)($f['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $region = trim((string)($f['region'] ?? $f['Region'] ?? ''));
        if ($region !== '' && substr($region, -1) !== ',') {
            $region .= ',';
        }
        $kat = trim((string)($f['kat'] ?? $f['Kat'] ?? 'POI'));
        if ($kat === '') {
            $kat = 'POI';
        }
        $aktivIn = $f['aktiv'] ?? $f['Aktiv'] ?? false;
        if (is_string($aktivIn)) {
            $aktiv = (strcasecmp(trim($aktivIn), 'Ja') === 0);
        } else {
            $aktiv = !empty($aktivIn);
        }
        $out[] = array(
            'name' => $name,
            'Region' => $region,
            'Kat' => $kat,
            'Aktiv' => $aktiv ? 'Ja' : 'Nein',
        );
    }
    return rkrk_catalog_write_json($xmlMissionLocation . $poi_settings_file, array(
        'schema' => 'rkrk-catalog',
        'version' => 1,
        'root' => 'Files',
        'data' => array('File' => $out),
    ));
}

function ref_layers_parse_coords($raw)
{
    $pairs = array();
    $raw = preg_replace('/\s+/', ' ', trim($raw));
    if ($raw === '' || $raw === null) {
        return $pairs;
    }
    foreach (preg_split('/\s+/', $raw) as $token) {
        $token = trim((string)$token);
        if ($token === '') {
            continue;
        }
        $parts = explode(',', $token);
        if (count($parts) < 2) {
            continue;
        }
        $lon = (float)$parts[0];
        $lat = (float)$parts[1];
        if (!is_finite($lon) || !is_finite($lat)) {
            continue;
        }
        if (abs($lon) < 0.0001 && abs($lat) < 0.0001) {
            continue;
        }
        $pairs[] = array($lon, $lat);
    }
    return $pairs;
}

function ref_layers_features_from_kml($kmlPath, $region, $kat)
{
    $xml = @simplexml_load_file($kmlPath);
    if ($xml === false) {
        return array();
    }
    $xml->registerXPathNamespace('k', 'http://www.opengis.net/kml/2.2');
    $marks = $xml->xpath('//k:Placemark') ?: $xml->xpath('//Placemark') ?: array();
    $features = array();
    $i = 0;
    foreach ($marks as $pm) {
        $name = trim((string)($pm->name ?? ''));
        $desc = trim(strip_tags((string)($pm->description ?? '')));
        $style = trim((string)($pm->styleUrl ?? ''));
        $geometry = null;
        $type = 'Point';
        if (isset($pm->Polygon->outerBoundaryIs->LinearRing->coordinates)) {
            $coords = ref_layers_parse_coords((string)$pm->Polygon->outerBoundaryIs->LinearRing->coordinates);
            if (count($coords) >= 3) {
                $ring = $coords;
                $first = $ring[0];
                $last = $ring[count($ring) - 1];
                if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                    $ring[] = $first;
                }
                $geometry = array('type' => 'Polygon', 'coordinates' => array($ring));
                $type = 'Polygon';
            }
        } elseif (isset($pm->LineString->coordinates)) {
            $coords = ref_layers_parse_coords((string)$pm->LineString->coordinates);
            if (count($coords) >= 2) {
                $geometry = array('type' => 'LineString', 'coordinates' => $coords);
                $type = 'LineString';
            }
        } elseif (isset($pm->Point->coordinates)) {
            $coords = ref_layers_parse_coords((string)$pm->Point->coordinates);
            if ($coords) {
                $geometry = array('type' => 'Point', 'coordinates' => $coords[0]);
                $type = 'Point';
            }
        } elseif (isset($pm->MultiGeometry)) {
            $mg = $pm->MultiGeometry;
            if (isset($mg->Polygon->outerBoundaryIs->LinearRing->coordinates)) {
                $coords = ref_layers_parse_coords((string)$mg->Polygon->outerBoundaryIs->LinearRing->coordinates);
                if (count($coords) >= 3) {
                    $ring = $coords;
                    $first = $ring[0];
                    $last = $ring[count($ring) - 1];
                    if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                        $ring[] = $first;
                    }
                    $geometry = array('type' => 'Polygon', 'coordinates' => array($ring));
                    $type = 'Polygon';
                }
            }
        }
        if ($geometry === null) {
            continue;
        }
        $features[] = array(
            'type' => 'Feature',
            'properties' => array(
                'id' => (string)$i,
                'sourceIndex' => $i,
                'name' => $name,
                'description' => $desc,
                'kat' => $kat,
                'group' => $kat,
                'styleUrl' => $style,
                'region' => $region,
                'source' => basename($kmlPath),
                'geomType' => $type,
            ),
            'geometry' => $geometry,
        );
        $i++;
    }
    return $features;
}

function ref_layers_write_collection($path, $groupKey, $features, $sourceNote)
{
    $payload = array(
        'type' => 'FeatureCollection',
        'group' => $groupKey,
        'count' => count($features),
        'generated_at' => gmdate('c'),
        'source' => $sourceNote,
        'features' => $features,
    );
    if (function_exists('rkrk_write_json_file')) {
        return rkrk_write_json_file($path, $payload);
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($path, $json) !== false;
}

function ref_layers_poi_type($geomType)
{
    $t = strtolower((string)$geomType);
    if ($t === 'linestring' || $t === 'line' || $t === 'multilinestring') {
        return 'Line';
    }
    if ($t === 'polygon' || $t === 'multipolygon') {
        return 'Polygon';
    }
    return 'Point';
}

function ref_layers_fmt_lonlat($xy)
{
    if (!is_array($xy) || count($xy) < 2) {
        return '';
    }
    return ((float)$xy[0]) . ',' . ((float)$xy[1]) . ',0';
}

function ref_layers_coords_string($geometry)
{
    if (!is_array($geometry)) {
        return '';
    }
    $type = (string)($geometry['type'] ?? '');
    $c = $geometry['coordinates'] ?? array();
    if ($type === 'Point' && is_array($c)) {
        return ref_layers_fmt_lonlat($c);
    }
    if (($type === 'LineString' || $type === 'MultiLineString') && is_array($c)) {
        $line = ($type === 'MultiLineString' && isset($c[0]) && is_array($c[0])) ? $c[0] : $c;
        $pts = array();
        foreach ($line as $p) {
            $s = ref_layers_fmt_lonlat($p);
            if ($s !== '') {
                $pts[] = $s;
            }
        }
        return implode(' ', $pts);
    }
    if (($type === 'Polygon' || $type === 'MultiPolygon') && is_array($c)) {
        $ring = $c;
        if ($type === 'MultiPolygon' && isset($c[0])) {
            $ring = $c[0];
        }
        if (isset($ring[0]) && is_array($ring[0]) && isset($ring[0][0]) && is_array($ring[0][0])) {
            $ring = $ring[0];
        }
        $pts = array();
        foreach ($ring as $p) {
            $s = ref_layers_fmt_lonlat($p);
            if ($s !== '') {
                $pts[] = $s;
            }
        }
        return implode(' ', $pts);
    }
    return '';
}

function ref_layers_features_to_felder($features, $region, $kat)
{
    $out = array();
    foreach ($features as $feat) {
        if (!is_array($feat)) {
            continue;
        }
        $props = is_array($feat['properties'] ?? null) ? $feat['properties'] : array();
        $geom = is_array($feat['geometry'] ?? null) ? $feat['geometry'] : array();
        $coords = ref_layers_coords_string($geom);
        if ($coords === '') {
            continue;
        }
        $out[] = array(
            'name' => (string)($props['name'] ?? ''),
            'description' => (string)($props['description'] ?? ''),
            'styleUrl' => (string)($props['styleUrl'] ?? ''),
            'coordinates' => $coords,
            'Type' => ref_layers_poi_type($geom['type'] ?? ($props['geomType'] ?? 'Point')),
            'Region' => $region,
            'Kat' => $kat,
        );
    }
    return $out;
}

function ref_layers_write_poidata_catalog($felder)
{
    global $xmlMissionLocation, $xml_poifile;
    if (!function_exists('rkrk_catalog_write_json') || empty($xmlMissionLocation) || empty($xml_poifile)) {
        return false;
    }
    return rkrk_catalog_write_json($xmlMissionLocation . $xml_poifile, array(
        'schema' => 'rkrk-catalog',
        'version' => 1,
        'root' => 'Felder',
        'data' => array('Feld' => array_values($felder)),
    ));
}

function ref_layers_rebuild_poidata_from_settings()
{
    $files = ref_layers_load_settings();
    $felder = array();
    foreach ($files as $f) {
        if (empty($f['aktiv'])) {
            continue;
        }
        $kml = ref_layers_find_kml((string)$f['name']);
        if ($kml === null) {
            continue;
        }
        $feats = ref_layers_features_from_kml($kml, (string)$f['region'], (string)$f['kat']);
        foreach (ref_layers_features_to_felder($feats, (string)$f['region'], (string)$f['kat']) as $row) {
            $felder[] = $row;
        }
    }
    $ok = ref_layers_write_poidata_catalog($felder);
    return array('ok' => $ok, 'count' => count($felder), 'path' => 'rkrk_poidata.json');
}

function ref_layers_build_from_kml_sources()
{
    @set_time_limit(300);
    $files = ref_layers_load_settings();
    $byKat = array();
    $results = array();
    $errors = array();
    $felder = array();
    foreach ($files as $f) {
        if (empty($f['aktiv'])) {
            continue;
        }
        $name = (string)$f['name'];
        $kat = (string)$f['kat'];
        $region = (string)$f['region'];
        $kml = ref_layers_find_kml($name);
        if ($kml === null) {
            $errors[] = "KML fehlt: {$name}.kml";
            $results[] = array('name' => $name, 'ok' => false, 'error' => 'kml_missing', 'count' => 0);
            continue;
        }
        $feats = ref_layers_features_from_kml($kml, $region, $kat);
        if (!isset($byKat[$kat])) {
            $byKat[$kat] = array();
        }
        foreach ($feats as $feat) {
            $byKat[$kat][] = $feat;
        }
        foreach (ref_layers_features_to_felder($feats, $region, $kat) as $row) {
            $felder[] = $row;
        }
        $results[] = array('name' => $name, 'ok' => true, 'kat' => $kat, 'count' => count($feats), 'file' => basename($kml));
    }
    $dir = ref_layers_intermediate_dir(true);
    $written = array();
    foreach ($byKat as $kat => $feats) {
        $path = ref_layers_cache_path($kat, true);
        if ($path === null) {
            $errors[] = "Pfad fehlgeschlagen fuer {$kat}";
            continue;
        }
        if (!ref_layers_write_collection($path, $kat, $feats, 'kml-sources')) {
            $errors[] = "Schreiben fehlgeschlagen: {$kat}";
            continue;
        }
        $written[] = array('group' => $kat, 'count' => count($feats), 'path' => basename($path));
    }
    $poi = ref_layers_write_poidata_catalog($felder);
    if (!$poi) {
        $errors[] = 'rkrk_poidata.json konnte nicht geschrieben werden';
    }
    return array(
        'ok' => $errors === array(),
        'intermediate' => $dir,
        'processed' => $results,
        'groups' => $written,
        'errors' => $errors,
        'poidata' => array('ok' => $poi, 'count' => count($felder)),
    );
}

function ref_layers_region_match($regionCsv, $want)
{
    $want = trim($want);
    if ($want === '' || strcasecmp($want, 'ALL') === 0) {
        return true;
    }
    foreach (preg_split('/\s*,\s*/', $regionCsv) ?: array() as $p) {
        $p = trim((string)$p);
        if ($p !== '' && (strcasecmp($p, $want) === 0 || strcasecmp($p, 'ALL') === 0)) {
            return true;
        }
    }
    return false;
}

function ref_layers_bbox_hit($bbox, $coords)
{
    if ($bbox === null || $coords === array()) {
        return true;
    }
    list($w, $s, $e, $n) = $bbox;
    foreach ($coords as $c) {
        if ($c[0] >= $w && $c[0] <= $e && $c[1] >= $s && $c[1] <= $n) {
            return true;
        }
    }
    $minLon = $maxLon = $coords[0][0];
    $minLat = $maxLat = $coords[0][1];
    foreach ($coords as $c) {
        $minLon = min($minLon, $c[0]);
        $maxLon = max($maxLon, $c[0]);
        $minLat = min($minLat, $c[1]);
        $maxLat = max($maxLat, $c[1]);
    }
    return !($maxLon < $w || $minLon > $e || $maxLat < $s || $minLat > $n);
}

function ref_layers_geom_coords($geometry)
{
    $type = (string)($geometry['type'] ?? '');
    $c = $geometry['coordinates'] ?? null;
    if (!is_array($c)) {
        return array();
    }
    if ($type === 'Point') {
        return array(array((float)$c[0], (float)$c[1]));
    }
    if ($type === 'LineString') {
        $out = array();
        foreach ($c as $p) {
            if (is_array($p) && count($p) >= 2) {
                $out[] = array((float)$p[0], (float)$p[1]);
            }
        }
        return $out;
    }
    if ($type === 'Polygon') {
        $ring = is_array($c[0] ?? null) ? $c[0] : array();
        $out = array();
        foreach ($ring as $p) {
            if (is_array($p) && count($p) >= 2) {
                $out[] = array((float)$p[0], (float)$p[1]);
            }
        }
        return $out;
    }
    return array();
}

function ref_layers_default_colors()
{
    return array(
        'groups' => array(
            'FZ' => array('label' => 'Flugzonen', 'stroke' => '#ffee8d', 'fill' => 'rgba(255,238,141,0.40)', 'width' => 2),
            'HSP' => array('label' => 'Hochspannung', 'stroke' => '#ff0000', 'fill' => 'rgba(255,0,0,0.0)', 'width' => 4),
            'Alert' => array('label' => 'Flugeinschraenkungen', 'stroke' => '#ffa500', 'fill' => 'rgba(255,165,0,0.40)', 'width' => 3),
            'POI' => array('label' => 'POI / Flugplaetze', 'stroke' => '#e65100', 'fill' => 'rgba(230,81,0,0.85)', 'width' => 2),
            'Gde' => array('label' => 'Gemeindegrenzen', 'stroke' => '#ffcc66', 'fill' => 'rgba(255,204,102,0.0)', 'width' => 3),
            'JG' => array('label' => 'Jagdgebiete', 'stroke' => '#66ccff', 'fill' => 'rgba(102,204,255,0.08)', 'width' => 2),
            'SORA' => array('label' => 'SORA-Zonen', 'stroke' => '#ff8800', 'fill' => 'rgba(255,136,0,0.20)', 'width' => 2),
            'SUN' => array('label' => 'Morgensonne', 'stroke' => '#ffee8d', 'fill' => 'rgba(255,238,141,0.40)', 'width' => 2),
            'WF' => array('label' => 'Wasserflaechen', 'stroke' => '#42a5f5', 'fill' => 'rgba(66,165,245,0.40)', 'width' => 2),
            'Kanton' => array('label' => 'Kantonsgrenzen', 'stroke' => '#fada80', 'fill' => 'rgba(250,218,128,0.0)', 'width' => 5),
            'Ort' => array('label' => 'Hauptorte', 'stroke' => '#0080dd', 'fill' => 'rgba(0,128,221,0.9)', 'width' => 2),
            'GdeLabel' => array('label' => 'Gemeinde-Labels', 'stroke' => '#0080dd', 'fill' => 'rgba(0,128,221,0.9)', 'width' => 2),
            'JGLabel' => array('label' => 'JG-Labels', 'stroke' => '#0080dd', 'fill' => 'rgba(0,128,221,0.9)', 'width' => 2),
            'KtLabel' => array('label' => 'Kanton-Labels', 'stroke' => '#0080dd', 'fill' => 'rgba(0,128,221,0.9)', 'width' => 2),
            'DEV' => array('label' => 'DEV', 'stroke' => '#888888', 'fill' => 'rgba(136,136,136,0.2)', 'width' => 2),
        ),
        'styles' => array(
            '70mFlugzone' => array('stroke' => '#ffee8d', 'fill' => 'rgba(255,238,141,0.40)', 'width' => 2),
            '120mFlugzone' => array('stroke' => '#8dee8d', 'fill' => 'rgba(141,238,141,0.40)', 'width' => 1),
            '150mFlugzone' => array('stroke' => '#599bfe', 'fill' => 'rgba(89,155,254,0.20)', 'width' => 1),
            'Nachtflugzone' => array('stroke' => '#327dff', 'fill' => 'rgba(50,125,255,0.40)', 'width' => 1),
            'Nachtflugzone1' => array('stroke' => '#327dff', 'fill' => 'rgba(50,125,255,0.40)', 'width' => 1),
            'Nachtflugzone2' => array('stroke' => '#8fb8ff', 'fill' => 'rgba(143,184,255,0.40)', 'width' => 1),
            'Nachtflugzone3' => array('stroke' => '#327dff', 'fill' => 'rgba(50,125,255,0.40)', 'width' => 1),
            'Flugverbotszone' => array('stroke' => '#ff0000', 'fill' => 'rgba(255,0,0,0.20)', 'width' => 1),
            'HSPL' => array('stroke' => '#ff0000', 'fill' => 'rgba(255,0,0,0.0)', 'width' => 4),
            'Alert' => array('stroke' => '#ffa500', 'fill' => 'rgba(255,165,0,0.40)', 'width' => 3),
            'Sora' => array('stroke' => '#ff8800', 'fill' => 'rgba(255,136,0,0.20)', 'width' => 2),
            'Sun' => array('stroke' => '#ffee8d', 'fill' => 'rgba(255,238,141,0.40)', 'width' => 2),
            'Wasser' => array('stroke' => '#42a5f5', 'fill' => 'rgba(66,165,245,0.40)', 'width' => 2),
        ),
    );
}

function ref_layers_colors_path($forWrite = false)
{
    $dirs = ref_layers_cache_dirs(true);
    if ($dirs === array()) {
        return null;
    }
    if (!$forWrite) {
        foreach ($dirs as $dir) {
            $p = $dir . '/colors.json';
            if (is_file($p)) {
                return $p;
            }
        }
    }
    return $dirs[0] . '/colors.json';
}

function ref_layers_normalize_color_entry($entry, $fallback)
{
    if (!is_array($entry)) {
        $entry = array();
    }
    $stroke = trim((string)($entry['stroke'] ?? $fallback['stroke']));
    $fill = trim((string)($entry['fill'] ?? $fallback['fill']));
    $width = isset($entry['width']) ? (float)$entry['width'] : (float)($fallback['width'] ?? 2);
    $opacity = isset($entry['opacity']) ? (float)$entry['opacity'] : (float)($fallback['opacity'] ?? 1);
    if ($stroke === '') {
        $stroke = $fallback['stroke'];
    }
    if ($fill === '') {
        $fill = $fallback['fill'];
    }
    if (!is_finite($width) || $width <= 0) {
        $width = (float)($fallback['width'] ?? 2);
    }
    if (!is_finite($opacity)) {
        $opacity = 1.0;
    }
    if ($opacity < 0) {
        $opacity = 0.0;
    }
    if ($opacity > 1) {
        $opacity = 1.0;
    }
    $out = array(
        'stroke' => $stroke,
        'fill' => $fill,
        'width' => $width,
        'opacity' => $opacity,
    );
    if (isset($fallback['label'])) {
        $out['label'] = (string)($entry['label'] ?? $fallback['label']);
    }
    return $out;
}

function ref_layers_load_colors()
{
    $defaults = ref_layers_default_colors();
    $path = ref_layers_colors_path(false);
    $raw = null;
    if ($path && is_file($path)) {
        $raw = function_exists('rkrk_read_json_file')
            ? rkrk_read_json_file($path)
            : json_decode((string)file_get_contents($path), true);
    }
    $groupsIn = is_array($raw['groups'] ?? null) ? $raw['groups'] : array();
    $stylesIn = is_array($raw['styles'] ?? null) ? $raw['styles'] : array();
    $groups = array();
    foreach ($defaults['groups'] as $key => $def) {
        $groups[$key] = ref_layers_normalize_color_entry($groupsIn[$key] ?? null, $def);
    }
    $styles = array();
    foreach ($defaults['styles'] as $key => $def) {
        $styles[$key] = ref_layers_normalize_color_entry($stylesIn[$key] ?? null, $def);
    }
    foreach ($stylesIn as $key => $entry) {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$key);
        if ($safe === '' || isset($styles[$safe])) {
            continue;
        }
        $styles[$safe] = ref_layers_normalize_color_entry($entry, array(
            'stroke' => '#888888',
            'fill' => 'rgba(136,136,136,0.25)',
            'width' => 2,
        ));
    }
    return array(
        'groups' => $groups,
        'styles' => $styles,
        'source' => ($path && is_file($path)) ? 'mylayer/colors.json' : 'defaults_dokml',
        'path' => $path,
    );
}

function ref_layers_save_colors($payload)
{
    $defaults = ref_layers_default_colors();
    $groupsIn = is_array($payload['groups'] ?? null) ? $payload['groups'] : array();
    $stylesIn = is_array($payload['styles'] ?? null) ? $payload['styles'] : array();
    $groups = array();
    foreach ($defaults['groups'] as $key => $def) {
        $groups[$key] = ref_layers_normalize_color_entry($groupsIn[$key] ?? null, $def);
    }
    $styles = array();
    foreach ($defaults['styles'] as $key => $def) {
        $styles[$key] = ref_layers_normalize_color_entry($stylesIn[$key] ?? null, $def);
    }
    foreach ($stylesIn as $key => $entry) {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$key);
        if ($safe === '' || isset($styles[$safe])) {
            continue;
        }
        $styles[$safe] = ref_layers_normalize_color_entry($entry, array(
            'stroke' => '#888888',
            'fill' => 'rgba(136,136,136,0.25)',
            'width' => 2,
        ));
    }
    $path = ref_layers_colors_path(true);
    if ($path === null) {
        return false;
    }
    $data = array(
        'updated_at' => date('c'),
        'groups' => $groups,
        'styles' => $styles,
    );
    if (function_exists('rkrk_write_json_file')) {
        return rkrk_write_json_file($path, $data);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($path, $json) !== false;
}

function ref_layers_default_ui()
{
    $toggles = array();
    foreach (ref_layers_group_defs() as $d) {
        $key = (string)$d['key'];
        $toggles[$key] = in_array($key, array('FZ', 'HSP', 'Alert'), true);
    }
    return array(
        'toggles' => $toggles,
        'filterBbox' => false,
        'view' => 'ALL',
    );
}

function ref_layers_ui_defaults_path($forWrite = false)
{
    $dirs = ref_layers_cache_dirs(true);
    if ($dirs === array()) {
        return null;
    }
    if (!$forWrite) {
        foreach ($dirs as $dir) {
            $p = $dir . '/ui_defaults.json';
            if (is_file($p)) {
                return $p;
            }
        }
    }
    return $dirs[0] . '/ui_defaults.json';
}

function ref_layers_load_ui_defaults()
{
    $defaults = ref_layers_default_ui();
    $path = ref_layers_ui_defaults_path(false);
    $raw = null;
    if ($path && is_file($path)) {
        $raw = function_exists('rkrk_read_json_file')
            ? rkrk_read_json_file($path)
            : json_decode((string)file_get_contents($path), true);
    }
    $togglesIn = is_array($raw['toggles'] ?? null) ? $raw['toggles'] : array();
    $toggles = array();
    foreach ($defaults['toggles'] as $key => $defOn) {
        $toggles[$key] = array_key_exists($key, $togglesIn) ? !empty($togglesIn[$key]) : $defOn;
    }
    return array(
        'toggles' => $toggles,
        'filterBbox' => array_key_exists('filterBbox', is_array($raw) ? $raw : array())
            ? !empty($raw['filterBbox'])
            : !empty($defaults['filterBbox']),
        'view' => 'ALL',
        'source' => ($path && is_file($path)) ? 'mylayer/ui_defaults.json' : 'defaults',
        'path' => $path,
    );
}

function ref_layers_save_ui_defaults($payload)
{
    $defaults = ref_layers_default_ui();
    $togglesIn = is_array($payload['toggles'] ?? null) ? $payload['toggles'] : array();
    $toggles = array();
    foreach ($defaults['toggles'] as $key => $defOn) {
        $toggles[$key] = array_key_exists($key, $togglesIn) ? !empty($togglesIn[$key]) : $defOn;
    }
    $path = ref_layers_ui_defaults_path(true);
    if ($path === null) {
        return false;
    }
    $data = array(
        'updated_at' => date('c'),
        'toggles' => $toggles,
        'filterBbox' => !empty($payload['filterBbox']),
        'view' => 'ALL',
    );
    if (function_exists('rkrk_write_json_file')) {
        return rkrk_write_json_file($path, $data);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($path, $json) !== false;
}

function ref_layers_safe_kml_stem($name)
{
    $stem = preg_replace('/\.kml$/i', '', trim($name));
    $stem = str_replace(array("\0", '\\', '/'), '', (string)$stem);
    if ($stem === '' || strpos($stem, '..') !== false) {
        return null;
    }
    return $stem;
}

function ref_layers_file_meta($stem)
{
    foreach (ref_layers_load_settings() as $f) {
        $n = preg_replace('/\.kml$/i', '', trim((string)($f['name'] ?? '')));
        if (strcasecmp((string)$n, $stem) === 0) {
            return array(
                'name' => (string)$n,
                'region' => (string)($f['region'] ?? ''),
                'kat' => (string)($f['kat'] ?? 'POI'),
                'aktiv' => !empty($f['aktiv']),
            );
        }
    }
    return array('name' => $stem, 'region' => 'ALL,', 'kat' => 'POI', 'aktiv' => false);
}

function ref_layers_kml_write_path($stem)
{
    global $kmlReferenceFilelocations, $RKRKLocalLoactionMissionFiles;
    $existing = ref_layers_find_kml($stem);
    if ($existing && is_writable($existing)) {
        return $existing;
    }
    $candidates = array();
    if (!empty($RKRKLocalLoactionMissionFiles)) {
        $candidates[] = rtrim(str_replace('\\', '/', (string)$RKRKLocalLoactionMissionFiles), '/') . '/kmlfiles/referenzen';
    }
    if (!empty($kmlReferenceFilelocations)) {
        $candidates[] = rtrim(str_replace('\\', '/', (string)$kmlReferenceFilelocations), '/');
    }
    foreach ($candidates as $dir) {
        if ($dir === '') {
            continue;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $dir . '/' . $stem . '.kml';
        }
    }
    return $existing;
}

function ref_layers_rebuild_kat($kat)
{
    $files = ref_layers_load_settings();
    $feats = array();
    $results = array();
    $errors = array();
    foreach ($files as $f) {
        if (empty($f['aktiv']) || (string)($f['kat'] ?? '') !== $kat) {
            continue;
        }
        $name = (string)$f['name'];
        $kml = ref_layers_find_kml($name);
        if ($kml === null) {
            $errors[] = "KML fehlt: {$name}.kml";
            $results[] = array('name' => $name, 'ok' => false, 'error' => 'kml_missing', 'count' => 0);
            continue;
        }
        $part = ref_layers_features_from_kml($kml, (string)$f['region'], $kat);
        foreach ($part as $feat) {
            $feats[] = $feat;
        }
        $results[] = array('name' => $name, 'ok' => true, 'kat' => $kat, 'count' => count($part));
    }
    $path = ref_layers_cache_path($kat, true);
    if ($path === null) {
        return array('ok' => false, 'error' => 'cache_path', 'processed' => $results, 'errors' => $errors);
    }
    $written = ref_layers_write_collection($path, $kat, $feats, 'kml-edit');
    if (!$written) {
        $errors[] = "Schreiben fehlgeschlagen: {$kat}";
    }
    return array(
        'ok' => $written && $errors === array(),
        'group' => $kat,
        'count' => count($feats),
        'processed' => $results,
        'errors' => $errors,
    );
}

function ref_layers_dom_first(DOMElement $el, $local)
{
    $list = $el->getElementsByTagNameNS('*', $local);
    if ($list->length === 0) {
        $list = $el->getElementsByTagName($local);
    }
    return $list->length ? $list->item(0) : null;
}

function ref_layers_kml_ns(DOMDocument $dom)
{
    $ns = $dom->documentElement ? (string)$dom->documentElement->namespaceURI : '';
    return $ns !== '' ? $ns : 'http://www.opengis.net/kml/2.2';
}

function ref_layers_kml_create(DOMDocument $dom, $local)
{
    $ns = ref_layers_kml_ns($dom);
    return $dom->createElementNS($ns, $local);
}

function ref_layers_kml_fmt_point($xy, $oldText)
{
    $z = '0';
    if (preg_match('/^[^,]*,[^,]*,([^\s]+)/', trim($oldText), $m)) {
        $z = $m[1];
    }
    return sprintf('%.10F,%.10F,%s', (float)$xy[0], (float)$xy[1], $z);
}

function ref_layers_kml_fmt_line($coords, $oldText, $close)
{
    $oldZ = array();
    if (preg_match_all('/[^\s,]+,[^\s,]+,([^\s]+)/', $oldText, $m)) {
        $oldZ = $m[1];
    }
    $pts = array();
    foreach ($coords as $i => $c) {
        if (!is_array($c) || count($c) < 2) {
            continue;
        }
        $z = $oldZ[$i] ?? ($oldZ[0] ?? '0');
        $pts[] = sprintf('%.10F,%.10F,%s', (float)$c[0], (float)$c[1], $z);
    }
    if ($close && $pts) {
        $first = $coords[0];
        $last = $coords[count($coords) - 1];
        if ((float)$first[0] !== (float)$last[0] || (float)$first[1] !== (float)$last[1]) {
            $z = $oldZ[0] ?? '0';
            $pts[] = sprintf('%.10F,%.10F,%s', (float)$first[0], (float)$first[1], $z);
        }
    }
    return implode(' ', $pts);
}

function ref_layers_kml_remove_geoms(DOMElement $pm)
{
    $remove = array();
    foreach ($pm->childNodes as $ch) {
        if (!$ch instanceof DOMElement) {
            continue;
        }
        $n = $ch->localName ?: $ch->nodeName;
        if (in_array($n, array('Point', 'LineString', 'Polygon', 'MultiGeometry', 'LinearRing'), true)) {
            $remove[] = $ch;
        }
    }
    foreach ($remove as $n) {
        $pm->removeChild($n);
    }
}

function ref_layers_kml_set_text(DOMDocument $dom, DOMElement $parent, $local, $text)
{
    $el = ref_layers_dom_first($parent, $local);
    if (!$el) {
        $el = ref_layers_kml_create($dom, $local);
        $parent->appendChild($el);
    }
    while ($el->firstChild) {
        $el->removeChild($el->firstChild);
    }
    $el->appendChild($dom->createTextNode($text));
}

function ref_layers_kml_append_geom(DOMDocument $dom, DOMElement $pm, $geom)
{
    $type = (string)($geom['type'] ?? '');
    $coords = $geom['coordinates'] ?? array();
    if ($type === 'MultiPolygon' && is_array($coords) && isset($coords[0])) {
        $type = 'Polygon';
        $coords = $coords[0];
    }
    if ($type === 'MultiLineString' && is_array($coords) && isset($coords[0])) {
        $type = 'LineString';
        $coords = $coords[0];
    }
    if ($type === 'Point' && is_array($coords) && count($coords) >= 2) {
        $pt = ref_layers_kml_create($dom, 'Point');
        $c = ref_layers_kml_create($dom, 'coordinates');
        $c->appendChild($dom->createTextNode(ref_layers_kml_fmt_point($coords, '')));
        $pt->appendChild($c);
        $pm->appendChild($pt);
        return;
    }
    if ($type === 'LineString' && is_array($coords)) {
        $ls = ref_layers_kml_create($dom, 'LineString');
        $c = ref_layers_kml_create($dom, 'coordinates');
        $c->appendChild($dom->createTextNode(ref_layers_kml_fmt_line($coords, '', false)));
        $ls->appendChild($c);
        $pm->appendChild($ls);
        return;
    }
    if ($type === 'Polygon' && is_array($coords) && isset($coords[0]) && is_array($coords[0])) {
        $poly = ref_layers_kml_create($dom, 'Polygon');
        $outer = ref_layers_kml_create($dom, 'outerBoundaryIs');
        $ring = ref_layers_kml_create($dom, 'LinearRing');
        $c = ref_layers_kml_create($dom, 'coordinates');
        $c->appendChild($dom->createTextNode(ref_layers_kml_fmt_line($coords[0], '', true)));
        $ring->appendChild($c);
        $outer->appendChild($ring);
        $poly->appendChild($outer);
        $pm->appendChild($poly);
    }
}

function ref_layers_kml_update_geom(DOMDocument $dom, DOMElement $pm, $geom)
{
    $type = (string)($geom['type'] ?? '');
    $coords = $geom['coordinates'] ?? array();
    if ($type === 'MultiPolygon' && is_array($coords) && isset($coords[0])) {
        $type = 'Polygon';
        $coords = $coords[0];
    }
    if ($type === 'MultiLineString' && is_array($coords) && isset($coords[0])) {
        $type = 'LineString';
        $coords = $coords[0];
    }
    $hasPoint = ref_layers_dom_first($pm, 'Point');
    $hasLine = ref_layers_dom_first($pm, 'LineString');
    $hasPoly = ref_layers_dom_first($pm, 'Polygon');
    if ($type === 'Point' && $hasPoint && is_array($coords) && count($coords) >= 2) {
        $cEl = ref_layers_dom_first($hasPoint, 'coordinates');
        if ($cEl) {
            $cEl->textContent = ref_layers_kml_fmt_point($coords, (string)$cEl->textContent);
            return;
        }
    }
    if ($type === 'LineString' && $hasLine && is_array($coords)) {
        $cEl = ref_layers_dom_first($hasLine, 'coordinates');
        if ($cEl) {
            $cEl->textContent = ref_layers_kml_fmt_line($coords, (string)$cEl->textContent, false);
            return;
        }
    }
    if ($type === 'Polygon' && $hasPoly && is_array($coords) && isset($coords[0])) {
        $cEl = ref_layers_dom_first($hasPoly, 'coordinates');
        if ($cEl) {
            $cEl->textContent = ref_layers_kml_fmt_line($coords[0], (string)$cEl->textContent, true);
            return;
        }
    }
    ref_layers_kml_remove_geoms($pm);
    $geom['coordinates'] = $coords;
    $geom['type'] = $type;
    ref_layers_kml_append_geom($dom, $pm, $geom);
}

function ref_layers_save_kml_features($stem, $features)
{
    $path = ref_layers_kml_write_path($stem);
    if ($path === null) {
        return array('ok' => false, 'path' => null, 'count' => 0, 'error' => 'write_path_failed');
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (is_file($path)) {
        $loaded = @$dom->load($path);
        if ($loaded === false) {
            return array('ok' => false, 'path' => $path, 'count' => 0, 'error' => 'kml_parse_failed');
        }
    } else {
        $ns = 'http://www.opengis.net/kml/2.2';
        $kml = $dom->createElementNS($ns, 'kml');
        $dom->appendChild($kml);
        $docEl = $dom->createElementNS($ns, 'Document');
        $nameEl = $dom->createElementNS($ns, 'name');
        $nameEl->appendChild($dom->createTextNode($stem));
        $docEl->appendChild($nameEl);
        $kml->appendChild($docEl);
    }
    $xpath = new DOMXPath($dom);
    $ns = ref_layers_kml_ns($dom);
    $xpath->registerNamespace('k', $ns);
    $pmList = $xpath->query('//k:Placemark');
    if ($pmList === false || $pmList->length === 0) {
        $pmList = $xpath->query('//Placemark');
    }
    $original = array();
    if ($pmList) {
        foreach ($pmList as $n) {
            if ($n instanceof DOMElement) {
                $original[] = $n;
            }
        }
    }
    $keep = array();
    $docEl = $dom->getElementsByTagNameNS('*', 'Document')->item(0);
    if (!$docEl instanceof DOMElement) {
        $docEl = $dom->documentElement;
    }
    foreach ($features as $feat) {
        if (!is_array($feat)) {
            continue;
        }
        $props = is_array($feat['properties'] ?? null) ? $feat['properties'] : array();
        $geom = is_array($feat['geometry'] ?? null) ? $feat['geometry'] : array();
        if ($geom === array()) {
            continue;
        }
        $si = $props['sourceIndex'] ?? $props['id'] ?? null;
        $pm = null;
        if ($si !== null && $si !== '' && is_numeric($si)) {
            $i = (int)$si;
            if (isset($original[$i])) {
                $pm = $original[$i];
                $keep[$i] = true;
            }
        }
        if ($pm === null) {
            $pm = ref_layers_kml_create($dom, 'Placemark');
            $docEl->appendChild($pm);
        }
        $name = trim((string)($props['name'] ?? ''));
        if ($name !== '') {
            ref_layers_kml_set_text($dom, $pm, 'name', $name);
        }
        ref_layers_kml_update_geom($dom, $pm, $geom);
    }
    for ($i = count($original) - 1; $i >= 0; $i--) {
        if (!isset($keep[$i]) && $original[$i]->parentNode) {
            $original[$i]->parentNode->removeChild($original[$i]);
        }
    }
    if (is_file($path)) {
        @copy($path, $path . '.bak');
    }
    $xml = $dom->saveXML();
    if ($xml === false || file_put_contents($path, $xml) === false) {
        return array('ok' => false, 'path' => $path, 'count' => 0, 'error' => 'write_failed');
    }
    $meta = ref_layers_file_meta($stem);
    $rebuilt = ref_layers_rebuild_kat($meta['kat']);
    $poi = ref_layers_rebuild_poidata_from_settings();
    return array(
        'ok' => true,
        'path' => basename($path),
        'count' => count($features),
        'kat' => $meta['kat'],
        'rebuilt' => $rebuilt,
        'poidata' => $poi,
    );
}
