<?php

function parse_triplets(?string $raw, bool $with_alt = false): array
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return [];
    }
    preg_match_all('/-?\d+(?:\.\d+)?/', $raw, $matches);
    $nums = $matches[0] ?? [];
    $out = [];
    $step = $with_alt ? 3 : 2;
    for ($i = 0; $i + $step - 1 < count($nums); $i += $step) {
        $out[] = $with_alt
            ? [floatval($nums[$i]), floatval($nums[$i + 1]), floatval($nums[$i + 2])]
            : [floatval($nums[$i]), floatval($nums[$i + 1])];
    }
    return $out;
}

function lonlat_to_xy(array $p, float $lon0, float $lat0): array
{
    $m_per_deg_lat = 111320.0;
    $m_per_deg_lon = cos(deg2rad($lat0)) * 111320.0;
    return [($p[0] - $lon0) * $m_per_deg_lon, ($p[1] - $lat0) * $m_per_deg_lat];
}

function xy_to_lonlat(array $p, float $lon0, float $lat0): array
{
    $m_per_deg_lat = 111320.0;
    $m_per_deg_lon = cos(deg2rad($lat0)) * 111320.0;
    return [$lon0 + ($p[0] / $m_per_deg_lon), $lat0 + ($p[1] / $m_per_deg_lat)];
}

function distance_m(array $a, array $b): float
{
    $dx = $a[0] - $b[0];
    $dy = $a[1] - $b[1];
    $dz = (count($a) > 2 ? $a[2] : 0.0) - (count($b) > 2 ? $b[2] : 0.0);
    return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
}

function poly_bounds(array $xy): array
{
    $xs = array_column($xy, 0);
    $ys = array_column($xy, 1);
    return [min($xs), min($ys), max($xs), max($ys)];
}

function rotate_xy(array $p, float $deg): array
{
    $r = deg2rad($deg);
    $c = cos($r);
    $s = sin($r);
    return [$p[0] * $c - $p[1] * $s, $p[0] * $s + $p[1] * $c];
}

function horizontal_intersections(array $poly, float $y): array
{
    $ints = [];
    $n = count($poly);
    for ($i = 0; $i < $n - 1; $i++) {
        $a = $poly[$i];
        $b = $poly[$i + 1];
        if (($a[1] <= $y && $b[1] > $y) || ($b[1] <= $y && $a[1] > $y)) {
            $t = ($y - $a[1]) / ($b[1] - $a[1]);
            $ints[] = $a[0] + ($b[0] - $a[0]) * $t;
        }
    }
    sort($ints);
    $segs = [];
    for ($i = 0; $i + 1 < count($ints); $i += 2) {
        $segs[] = [$ints[$i], $ints[$i + 1]];
    }
    return $segs;
}

function plan_lawnmower(array $polygon_lonlat, array $start_lonlat, float $hfov_deg, float $agl_m, float $overlap, float $edge_factor): array
{
    if (count($polygon_lonlat) < 3) {
        return [];
    }
    if ($polygon_lonlat[0] !== $polygon_lonlat[count($polygon_lonlat) - 1]) {
        $polygon_lonlat[] = $polygon_lonlat[0];
    }

    $lon0 = $polygon_lonlat[0][0];
    $lat0 = $polygon_lonlat[0][1];
    $poly_xy = [];
    foreach ($polygon_lonlat as $p) {
        $poly_xy[] = lonlat_to_xy($p, $lon0, $lat0);
    }
    $start_xy = lonlat_to_xy($start_lonlat, $lon0, $lat0);

    $W = 2.0 * $agl_m * tan(deg2rad($hfov_deg / 2.0));
    $S = max(2.0, (1.0 - $overlap) * $W);
    $edge_margin = $edge_factor * $S;

    $best = ['score' => INF, 'points' => []];
    foreach ([0.0, 90.0] as $angle) {
        $rot_poly = [];
        foreach ($poly_xy as $p) {
            $rot_poly[] = rotate_xy($p, -$angle);
        }
        [$minx, $miny, $maxx, $maxy] = poly_bounds($rot_poly);
        $y = $miny + $edge_margin;
        $rows = [];
        while ($y <= $maxy - $edge_margin + 1e-9) {
            $rows[] = $y;
            $y += $S;
        }
        if (empty($rows)) {
            $rows = [($miny + $maxy) / 2.0];
        }

        $points = [];
        $row_idx = 0;
        foreach ($rows as $row_y) {
            $segments = horizontal_intersections($rot_poly, $row_y);
            foreach ($segments as $seg) {
                $a = [$seg[0], $row_y];
                $b = [$seg[1], $row_y];
                if ($row_idx % 2 === 1) {
                    $tmp = $a;
                    $a = $b;
                    $b = $tmp;
                }
                $points[] = rotate_xy($a, $angle);
                $points[] = rotate_xy($b, $angle);
            }
            $row_idx++;
        }

        if (empty($points)) {
            continue;
        }
        $len = 0.0;
        $prev = $start_xy;
        foreach ($points as $p) {
            $len += distance_m($prev, $p);
            $prev = $p;
        }
        $rough = 0.0;
        for ($i = 0; $i + 1 < count($points); $i++) {
            $rough += abs(($points[$i + 1][1] - $points[$i][1]) * 0.002);
        }
        $score = $len + 8.0 * count($points) + 40.0 * $rough;
        if ($score < $best['score']) {
            $best = ['score' => $score, 'points' => $points, 'angle' => $angle, 'W' => $W, 'S' => $S];
        }
    }

    $out = [];
    foreach ($best['points'] as $p) {
        $ll = xy_to_lonlat($p, $lon0, $lat0);
        $out[] = [$ll[0], $ll[1], $agl_m];
    }
    return ['path' => $out, 'angle' => $best['angle'] ?? 0.0, 'W' => $best['W'] ?? 0.0, 'S' => $best['S'] ?? 0.0];
}

function path_metrics(array $path): array
{
    if (count($path) < 2) {
        return ['distance_m' => 0.0, 'waypoints' => count($path), 'height_delta_m' => 0.0];
    }
    $lon0 = floatval($path[0][0]);
    $lat0 = floatval($path[0][1]);
    $len = 0.0;
    $height_delta = 0.0;
    for ($i = 0; $i + 1 < count($path); $i++) {
        $a2 = lonlat_to_xy([floatval($path[$i][0]), floatval($path[$i][1])], $lon0, $lat0);
        $b2 = lonlat_to_xy([floatval($path[$i + 1][0]), floatval($path[$i + 1][1])], $lon0, $lat0);
        $a = [$a2[0], $a2[1], floatval($path[$i][2] ?? 0.0)];
        $b = [$b2[0], $b2[1], floatval($path[$i + 1][2] ?? 0.0)];
        $len += distance_m($a, $b);
        $height_delta += abs(($path[$i + 1][2] ?? 0.0) - ($path[$i][2] ?? 0.0));
    }
    return ['distance_m' => $len, 'waypoints' => count($path), 'height_delta_m' => $height_delta];
}

function load_fields_xml(string $path): array
{
    $xml = simplexml_load_file($path);
    if (!$xml) {
        return [];
    }
    $fields = [];
    foreach ($xml->Feld as $f) {
        $id = (string)$f['id'];
        $name = (string)$f->Feldname;
        $fieldId = (string)$f->FeldID;
        $start = parse_triplets((string)$f->Grundstueckdaten->Startplatz, false);
        $poly = parse_triplets((string)$f->Flaechendaten, false);
        $flugweg = parse_triplets((string)$f->Flugweg, true);
        $fields[] = [
            'id' => $id,
            'feld_id' => $fieldId,
            'name' => $name,
            'start' => $start[0] ?? null,
            'polygon' => $poly,
            'manual_path' => $flugweg,
            'manual_meta' => [
                'flugzeit_min' => floatval((string)$f->Flugzeit),
                'distanz_m' => floatval((string)$f->Distanz),
                'wegpunkte' => intval((string)$f->Wegpunkte),
            ],
        ];
    }
    return $fields;
}

