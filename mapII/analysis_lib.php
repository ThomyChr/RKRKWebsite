<?php

require_once __DIR__ . '/mapll_env.php';

if (!function_exists('is_finite')) {
    function is_finite($value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        $n = floatval($value);
        return !is_infinite($n) && !is_nan($n);
    }
}

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

function polygon_area_xy(array $poly): float
{
    $n = count($poly);
    if ($n < 3) {
        return 0.0;
    }
    $a = 0.0;
    for ($i = 0; $i < $n - 1; $i++) {
        $x1 = floatval($poly[$i][0]);
        $y1 = floatval($poly[$i][1]);
        $x2 = floatval($poly[$i + 1][0]);
        $y2 = floatval($poly[$i + 1][1]);
        $a += $x1 * $y2 - $x2 * $y1;
    }
    return abs($a) * 0.5;
}

function polygon_area_lonlat_m2(array $polyLonLat): float
{
    if (count($polyLonLat) < 3) {
        return 0.0;
    }
    $poly = $polyLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $xy = [];
    foreach ($poly as $p) {
        $xy[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    return polygon_area_xy($xy);
}

function cap_secondary_sections_area_ratio(array $sections, array $fieldPolyLonLat, float $maxRatio = 0.20): array
{
    if (count($sections) < 2 || $maxRatio <= 0.0 || $maxRatio >= 1.0) {
        return ['sections' => $sections, 'changed' => false, 'largest_area_m2' => 0.0];
    }
    $areas = [];
    foreach ($sections as $i => $sec) {
        $poly = $sec['polygon'] ?? [];
        $areas[$i] = polygon_area_lonlat_m2(is_array($poly) ? $poly : []);
    }
    $largest = 0.0;
    foreach ($areas as $a) {
        $largest = max($largest, floatval($a));
    }
    if ($largest <= 1e-9) {
        return ['sections' => $sections, 'changed' => false, 'largest_area_m2' => 0.0];
    }
    $changed = false;
    $limit = $largest * $maxRatio;
    foreach ($sections as $i => &$sec) {
        $poly = $sec['polygon'] ?? [];
        if (!is_array($poly) || count($poly) < 4) {
            continue;
        }
        $a = floatval($areas[$i] ?? 0.0);
        if ($a <= $limit + 1e-9 || abs($a - $largest) <= 1e-9) {
            continue;
        }
        $ratioTarget = sqrt(max(1e-9, $limit / max(1e-9, $a)));
        $work = $poly;
        $n = count($work);
        $cx = 0.0;
        $cy = 0.0;
        foreach ($work as $p) {
            $cx += floatval($p[0]);
            $cy += floatval($p[1]);
        }
        $cx /= max(1, $n);
        $cy /= max(1, $n);
        $scaled = [];
        foreach ($work as $p) {
            $scaled[] = [
                $cx + (floatval($p[0]) - $cx) * $ratioTarget,
                $cy + (floatval($p[1]) - $cy) * $ratioTarget
            ];
        }
        if (!shape_polygon_inside_field($scaled, $fieldPolyLonLat, 12)) {
            $scaled = shrink_shape_uniform_to_fit_field($scaled, $fieldPolyLonLat, 80);
        }
        $sec['polygon'] = $scaled;
        $changed = true;
    }
    unset($sec);
    return ['sections' => $sections, 'changed' => $changed, 'largest_area_m2' => $largest];
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

function point_in_polygon_xy(array $p, array $poly): bool
{
    $inside = false;
    $n = count($poly);
    if ($n < 3) {
        return false;
    }
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = floatval($poly[$i][0]);
        $yi = floatval($poly[$i][1]);
        $xj = floatval($poly[$j][0]);
        $yj = floatval($poly[$j][1]);
        $intersects = (($yi > $p[1]) !== ($yj > $p[1]))
            && ($p[0] < (($xj - $xi) * ($p[1] - $yi)) / (($yj - $yi) + 1e-12) + $xi);
        if ($intersects) {
            $inside = !$inside;
        }
    }
    return $inside;
}

function polygon_is_convex_xy(array $poly): bool
{
    $n = count($poly);
    if ($n < 4) {
        return true;
    }
    $sign = 0;
    for ($i = 0; $i < $n - 2; $i++) {
        $a = $poly[$i];
        $b = $poly[$i + 1];
        $c = $poly[$i + 2];
        $cross = ($b[0] - $a[0]) * ($c[1] - $b[1]) - ($b[1] - $a[1]) * ($c[0] - $b[0]);
        if (abs($cross) < 1e-9) {
            continue;
        }
        $curr = $cross > 0 ? 1 : -1;
        if ($sign === 0) {
            $sign = $curr;
            continue;
        }
        if ($sign !== $curr) {
            return false;
        }
    }
    return true;
}

function classify_basic_shape(array $polyXY): string
{
    $n = max(3, count($polyXY) - 1);
    if ($n === 3) {
        return 'dreieck';
    }
    if ($n === 4) {
        return 'rechteck_parallelogramm';
    }
    if ($n <= 6) {
        return 'einfach_oval_rund';
    }
    return 'zusammengesetzt';
}

function estimate_polygon_main_axis_deg(array $polyXY): float
{
    $n = count($polyXY);
    if ($n < 3) {
        return 0.0;
    }
    $cx = 0.0;
    $cy = 0.0;
    for ($i = 0; $i < $n - 1; $i++) {
        $cx += floatval($polyXY[$i][0]);
        $cy += floatval($polyXY[$i][1]);
    }
    $m = max(1, $n - 1);
    $cx /= $m;
    $cy /= $m;

    $sxx = 0.0;
    $syy = 0.0;
    $sxy = 0.0;
    for ($i = 0; $i < $n - 1; $i++) {
        $dx = floatval($polyXY[$i][0]) - $cx;
        $dy = floatval($polyXY[$i][1]) - $cy;
        $sxx += $dx * $dx;
        $syy += $dy * $dy;
        $sxy += $dx * $dy;
    }
    $theta = 0.5 * atan2(2.0 * $sxy, $sxx - $syy);
    $deg = rad2deg($theta);
    $deg = fmod(($deg + 360.0), 180.0);
    return $deg;
}

function field_major_axis_and_width(array $polygonLonLat): array
{
    if (count($polygonLonLat) < 3) {
        return ['major_axis_deg' => 0.0, 'minor_width_m' => 0.0];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $major = estimate_polygon_main_axis_deg($polyXY);
    $rot = [];
    foreach ($polyXY as $p) {
        $rot[] = rotate_xy($p, -$major);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($rot);
    return [
        'major_axis_deg' => $major,
        'minor_width_m' => max(0.0, $maxY - $minY),
    ];
}

function longest_polygon_edge_angle_deg(array $polygonLonLat): array
{
    if (count($polygonLonLat) < 2) {
        return ['angle_deg' => 0.0, 'length_m' => 0.0];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    if (count($poly) < 3) {
        return ['angle_deg' => 0.0, 'length_m' => 0.0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $bestLen = 0.0;
    $bestAngle = 0.0;
    for ($i = 0; $i + 1 < count($poly); $i++) {
        $a = $poly[$i];
        $b = $poly[$i + 1];
        $axy = lonlat_to_xy([floatval($a[0]), floatval($a[1])], $lon0, $lat0);
        $bxy = lonlat_to_xy([floatval($b[0]), floatval($b[1])], $lon0, $lat0);
        $dx = floatval($bxy[0]) - floatval($axy[0]);
        $dy = floatval($bxy[1]) - floatval($axy[1]);
        $len = hypot($dx, $dy);
        if ($len <= $bestLen) {
            continue;
        }
        $ang = fmod((rad2deg(atan2($dy, $dx)) + 360.0), 180.0);
        $bestLen = $len;
        $bestAngle = $ang;
    }
    return ['angle_deg' => $bestAngle, 'length_m' => $bestLen];
}

function shape_polygon_inside_field(array $shapeLonLat, array $fieldPolyLonLat, int $samplesPerEdge = 8): bool
{
    if (count($shapeLonLat) < 3 || count($fieldPolyLonLat) < 3) {
        return false;
    }
    $field = $fieldPolyLonLat;
    if ($field[0] !== $field[count($field) - 1]) {
        $field[] = $field[0];
    }
    $shape = $shapeLonLat;
    if ($shape[0] !== $shape[count($shape) - 1]) {
        $shape[] = $shape[0];
    }
    $lon0 = floatval($field[0][0]);
    $lat0 = floatval($field[0][1]);
    $fieldXY = [];
    foreach ($field as $p) {
        $fieldXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $shapeXY = [];
    foreach ($shape as $p) {
        $shapeXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    for ($i = 0; $i + 1 < count($shapeXY); $i++) {
        $a = $shapeXY[$i];
        $b = $shapeXY[$i + 1];
        if (!point_in_polygon_xy($a, $fieldXY) || !point_in_polygon_xy($b, $fieldXY)) {
            return false;
        }
        for ($k = 1; $k < $samplesPerEdge; $k++) {
            $t = $k / $samplesPerEdge;
            $m = [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t];
            if (!point_in_polygon_xy($m, $fieldXY)) {
                return false;
            }
        }
    }
    return true;
}

function point_to_segment_distance_xy(array $p, array $a, array $b): float
{
    $vx = $b[0] - $a[0];
    $vy = $b[1] - $a[1];
    $wx = $p[0] - $a[0];
    $wy = $p[1] - $a[1];
    $c1 = $vx * $wx + $vy * $wy;
    if ($c1 <= 0.0) {
        return sqrt(($p[0] - $a[0]) ** 2 + ($p[1] - $a[1]) ** 2);
    }
    $c2 = $vx * $vx + $vy * $vy;
    if ($c2 <= 1e-12) {
        return sqrt(($p[0] - $a[0]) ** 2 + ($p[1] - $a[1]) ** 2);
    }
    $t = min(1.0, max(0.0, $c1 / $c2));
    $qx = $a[0] + $t * $vx;
    $qy = $a[1] + $t * $vy;
    return sqrt(($p[0] - $qx) ** 2 + ($p[1] - $qy) ** 2);
}

/**
 * @return array{0:float,1:float,2:float} qx, qy, t on [a,b]
 */
function point_foot_on_segment_xy(array $p, array $a, array $b): array
{
    $vx = floatval($b[0]) - floatval($a[0]);
    $vy = floatval($b[1]) - floatval($a[1]);
    $wx = floatval($p[0]) - floatval($a[0]);
    $wy = floatval($p[1]) - floatval($a[1]);
    $c2 = $vx * $vx + $vy * $vy;
    if ($c2 <= 1e-12) {
        return [floatval($a[0]), floatval($a[1]), 0.0];
    }
    $c1 = $vx * $wx + $vy * $wy;
    $t = min(1.0, max(0.0, $c1 / $c2));
    return [
        floatval($a[0]) + $t * $vx,
        floatval($a[1]) + $t * $vy,
        $t
    ];
}

/**
 * One midpoint on the northernmost long leg (typ. upper field edge / top swath),
 * and one tail point stepped along the field boundary from the path end (outward from centroid).
 *
 * @param array<int, array{0:mixed,1:mixed,2?:mixed}> $pathLonLatAlt
 * @return array<int, array{0:float,1:float,2:float}>
 */
function mission_path_add_top_midpoint_and_edge_tail(array $pathLonLatAlt, array $fieldPolygonLonLat, float $swathWidthM): array
{
    if (count($pathLonLatAlt) < 4 || count($fieldPolygonLonLat) < 3 || $swathWidthM <= 1e-6) {
        return $pathLonLatAlt;
    }
    $W = floatval($swathWidthM);
    $poly = $fieldPolygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($polyXY);
    $nu = count($polyXY) - 1;
    $cx = 0.0;
    $cy = 0.0;
    for ($i = 0; $i < $nu; $i++) {
        $cx += floatval($polyXY[$i][0]);
        $cy += floatval($polyXY[$i][1]);
    }
    if ($nu > 0) {
        $cx /= $nu;
        $cy /= $nu;
    }
    $path = $pathLonLatAlt;
    $n = count($path);
    $minSegM = max(15.0, 0.28 * $W);
    $bestI = -1;
    $bestNorth = -INF;
    for ($i = 2; $i + 1 < $n; $i++) {
        $a = lonlat_to_xy([floatval($path[$i][0]), floatval($path[$i][1])], $lon0, $lat0);
        $b = lonlat_to_xy([floatval($path[$i + 1][0]), floatval($path[$i + 1][1])], $lon0, $lat0);
        $segLen = hypot(floatval($b[0]) - floatval($a[0]), floatval($b[1]) - floatval($a[1]));
        if ($segLen < $minSegM) {
            continue;
        }
        $north = 0.5 * (floatval($path[$i][1]) + floatval($path[$i + 1][1]));
        if ($north > $bestNorth + 1e-12) {
            $bestNorth = $north;
            $bestI = $i;
        }
    }
    if ($bestI >= 0) {
        $lonA = floatval($path[$bestI][0]);
        $latA = floatval($path[$bestI][1]);
        $lonB = floatval($path[$bestI + 1][0]);
        $latB = floatval($path[$bestI + 1][1]);
        $zIns = 0.5 * (floatval($path[$bestI][2] ?? 0.0) + floatval($path[$bestI + 1][2] ?? 0.0));
        $midLon = 0.5 * ($lonA + $lonB);
        $midLat = 0.5 * ($latA + $latB);
        $mxy = lonlat_to_xy([$midLon, $midLat], $lon0, $lat0);
        if (point_in_polygon_xy($mxy, $polyXY)) {
            $dup = false;
            foreach ($path as $wp) {
                $wxy = lonlat_to_xy([floatval($wp[0]), floatval($wp[1])], $lon0, $lat0);
                if (hypot($wxy[0] - $mxy[0], $wxy[1] - $mxy[1]) < min(9.0, 0.2 * $W)) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                array_splice($path, $bestI + 1, 0, [[$midLon, $midLat, $zIns]]);
                $n = count($path);
            }
        }
    }
    if ($n < 3) {
        return $path;
    }
    $lastIdx = $n - 1;
    $lastXY = lonlat_to_xy([floatval($path[$lastIdx][0]), floatval($path[$lastIdx][1])], $lon0, $lat0);
    $bestD = INF;
    $bestQ = null;
    $bestTx = 0.0;
    $bestTy = 0.0;
    $nPoly = count($polyXY);
    for ($ei = 0; $ei + 1 < $nPoly; $ei++) {
        $A = $polyXY[$ei];
        $B = $polyXY[$ei + 1];
        $ft = point_foot_on_segment_xy($lastXY, $A, $B);
        $qx = floatval($ft[0]);
        $qy = floatval($ft[1]);
        $d = hypot(floatval($lastXY[0]) - $qx, floatval($lastXY[1]) - $qy);
        $vx = floatval($B[0]) - floatval($A[0]);
        $vy = floatval($B[1]) - floatval($A[1]);
        $len = hypot($vx, $vy);
        if ($len < 1e-9) {
            continue;
        }
        $tx = $vx / $len;
        $ty = $vy / $len;
        if ($d < $bestD - 1e-9) {
            $bestD = $d;
            $bestQ = [$qx, $qy];
            $bestTx = $tx;
            $bestTy = $ty;
        }
    }
    if ($bestQ === null || !is_finite($bestD) || $bestD > 28.0) {
        return $path;
    }
    $along = min(22.0, max(9.0, 0.24 * $W));
    $qx = floatval($bestQ[0]);
    $qy = floatval($bestQ[1]);
    $ox = $qx - $cx;
    $oy = $qy - $cy;
    $olen = hypot($ox, $oy);
    if ($olen > 1e-9) {
        $ox /= $olen;
        $oy /= $olen;
    } else {
        $ox = 1.0;
        $oy = 0.0;
    }
    $align = $bestTx * $ox + $bestTy * $oy;
    if ($align < 0.0) {
        $bestTx = -$bestTx;
        $bestTy = -$bestTy;
    }
    $nx = $qx + $bestTx * $along;
    $ny = $qy + $bestTy * $along;
    $clamp = clamp_point_inside_polygon_xy([$nx, $ny], $anchor, $polyXY);
    $ll = xy_to_lonlat($clamp, $lon0, $lat0);
    $zTail = floatval($path[$lastIdx][2] ?? 0.0);
    $tail = [floatval($ll[0]), floatval($ll[1]), $zTail];
    $twxy = lonlat_to_xy([$tail[0], $tail[1]], $lon0, $lat0);
    if (hypot($twxy[0] - floatval($lastXY[0]), $twxy[1] - floatval($lastXY[1])) < max(4.0, 0.12 * $W)) {
        return $path;
    }
    $path[] = $tail;
    return $path;
}

function point_to_polygon_edge_distance_xy(array $p, array $poly): float
{
    $n = count($poly);
    if ($n < 2) {
        return 0.0;
    }
    $best = INF;
    for ($i = 0; $i + 1 < $n; $i++) {
        $d = point_to_segment_distance_xy($p, $poly[$i], $poly[$i + 1]);
        if ($d < $best) {
            $best = $d;
        }
    }
    return is_finite($best) ? $best : 0.0;
}

function shape_polygon_max_outside_m(array $shapeLonLat, array $fieldPolyLonLat, int $samplesPerEdge = 10): float
{
    if (count($shapeLonLat) < 3 || count($fieldPolyLonLat) < 3) {
        return 0.0;
    }
    $field = $fieldPolyLonLat;
    if ($field[0] !== $field[count($field) - 1]) {
        $field[] = $field[0];
    }
    $shape = $shapeLonLat;
    if ($shape[0] !== $shape[count($shape) - 1]) {
        $shape[] = $shape[0];
    }
    $lon0 = floatval($field[0][0]);
    $lat0 = floatval($field[0][1]);
    $fieldXY = [];
    foreach ($field as $p) {
        $fieldXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $shapeXY = [];
    foreach ($shape as $p) {
        $shapeXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $maxOutside = 0.0;
    for ($i = 0; $i + 1 < count($shapeXY); $i++) {
        $a = $shapeXY[$i];
        $b = $shapeXY[$i + 1];
        for ($k = 0; $k <= $samplesPerEdge; $k++) {
            $t = $samplesPerEdge > 0 ? ($k / $samplesPerEdge) : 0.0;
            $m = [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t];
            if (point_in_polygon_xy($m, $fieldXY)) {
                continue;
            }
            $d = point_to_polygon_edge_distance_xy($m, $fieldXY);
            if ($d > $maxOutside) {
                $maxOutside = $d;
            }
        }
    }
    return $maxOutside;
}

function sections_coverage_ratio(array $sections, array $fieldPolyLonLat, int $gridN = 70): float
{
    if (count($fieldPolyLonLat) < 3 || $gridN < 10) {
        return 0.0;
    }
    $field = $fieldPolyLonLat;
    if ($field[0] !== $field[count($field) - 1]) {
        $field[] = $field[0];
    }
    $lon0 = floatval($field[0][0]);
    $lat0 = floatval($field[0][1]);
    $fieldXY = [];
    foreach ($field as $p) {
        $fieldXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($fieldXY);
    $dx = max(1e-9, ($maxX - $minX) / $gridN);
    $dy = max(1e-9, ($maxY - $minY) / $gridN);

    $secXY = [];
    foreach ($sections as $sec) {
        $poly = $sec['polygon'] ?? [];
        if (count($poly) < 3) {
            continue;
        }
        if ($poly[0] !== $poly[count($poly) - 1]) {
            $poly[] = $poly[0];
        }
        $xy = [];
        foreach ($poly as $p) {
            $xy[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
        }
        $secXY[] = $xy;
    }
    if (empty($secXY)) {
        return 0.0;
    }

    $inField = 0;
    $covered = 0;
    for ($iy = 0; $iy <= $gridN; $iy++) {
        $y = $minY + $iy * $dy;
        for ($ix = 0; $ix <= $gridN; $ix++) {
            $x = $minX + $ix * $dx;
            $p = [$x, $y];
            if (!point_in_polygon_xy($p, $fieldXY)) {
                continue;
            }
            $inField++;
            foreach ($secXY as $poly) {
                if (point_in_polygon_xy($p, $poly)) {
                    $covered++;
                    break;
                }
            }
        }
    }
    if ($inField <= 0) {
        return 0.0;
    }
    $r = $covered / $inField;
    return min(1.0, max(0.0, $r));
}

function shrink_shape_uniform_to_fit_field(array $shapeLonLat, array $fieldPolyLonLat, int $maxIter = 40): array
{
    if (count($shapeLonLat) < 3) {
        return $shapeLonLat;
    }
    $shape = $shapeLonLat;
    if ($shape[0] !== $shape[count($shape) - 1]) {
        $shape[] = $shape[0];
    }
    if (shape_polygon_inside_field($shape, $fieldPolyLonLat, 10)) {
        return $shape;
    }
    $work = $shape;
    for ($iter = 0; $iter < $maxIter; $iter++) {
        $cx = 0.0;
        $cy = 0.0;
        $n = count($work);
        for ($i = 0; $i < $n; $i++) {
            $cx += floatval($work[$i][0]);
            $cy += floatval($work[$i][1]);
        }
        $cx /= max(1, $n);
        $cy /= max(1, $n);
        $next = [];
        foreach ($work as $p) {
            $next[] = [
                $cx + (floatval($p[0]) - $cx) * 0.97,
                $cy + (floatval($p[1]) - $cy) * 0.97
            ];
        }
        $work = $next;
        if (shape_polygon_inside_field($work, $fieldPolyLonLat, 10)) {
            return $work;
        }
    }
    return $work;
}

function rect_polygon_rotated(float $minX, float $minY, float $maxX, float $maxY, float $angleDeg, float $lon0, float $lat0): array
{
    $pts = [
        [$minX, $minY],
        [$maxX, $minY],
        [$maxX, $maxY],
        [$minX, $maxY],
        [$minX, $minY]
    ];
    $out = [];
    foreach ($pts as $p) {
        $r = rotate_xy($p, $angleDeg);
        $ll = xy_to_lonlat($r, $lon0, $lat0);
        $out[] = [floatval($ll[0]), floatval($ll[1])];
    }
    return $out;
}

function find_inside_anchor_xy(array $polyXY): array
{
    $n = count($polyXY);
    if ($n < 3) {
        return [0.0, 0.0];
    }
    $sumX = 0.0;
    $sumY = 0.0;
    for ($i = 0; $i < $n - 1; $i++) {
        $sumX += floatval($polyXY[$i][0]);
        $sumY += floatval($polyXY[$i][1]);
    }
    $c = [$sumX / max(1, $n - 1), $sumY / max(1, $n - 1)];
    if (point_in_polygon_xy($c, $polyXY)) {
        return $c;
    }

    [$minX, $minY, $maxX, $maxY] = poly_bounds($polyXY);
    $stepX = max(1.0, ($maxX - $minX) / 20.0);
    $stepY = max(1.0, ($maxY - $minY) / 20.0);
    for ($y = $minY; $y <= $maxY + 1e-9; $y += $stepY) {
        for ($x = $minX; $x <= $maxX + 1e-9; $x += $stepX) {
            if (point_in_polygon_xy([$x, $y], $polyXY)) {
                return [$x, $y];
            }
        }
    }
    return [floatval($polyXY[0][0]), floatval($polyXY[0][1])];
}

function clamp_point_inside_polygon_xy(array $p, array $anchor, array $polyXY): array
{
    if (point_in_polygon_xy($p, $polyXY)) {
        return $p;
    }
    $lo = 0.0;
    $hi = 1.0;
    $best = $anchor;
    for ($i = 0; $i < 30; $i++) {
        $t = ($lo + $hi) / 2.0;
        $q = [
            $anchor[0] + ($p[0] - $anchor[0]) * $t,
            $anchor[1] + ($p[1] - $anchor[1]) * $t
        ];
        if (point_in_polygon_xy($q, $polyXY)) {
            $best = $q;
            $lo = $t;
        } else {
            $hi = $t;
        }
    }
    return $best;
}

function clamp_lonlat_polygon_inside_field(array $shapeLonLat, array $fieldPolyLonLat): array
{
    if (count($shapeLonLat) < 3 || count($fieldPolyLonLat) < 3) {
        return $shapeLonLat;
    }
    $field = $fieldPolyLonLat;
    if ($field[0] !== $field[count($field) - 1]) {
        $field[] = $field[0];
    }
    $lon0 = floatval($field[0][0]);
    $lat0 = floatval($field[0][1]);
    $fieldXY = [];
    foreach ($field as $p) {
        $fieldXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($fieldXY);

    $shapeXY = [];
    foreach ($shapeLonLat as $p) {
        $xy = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
        $shapeXY[] = clamp_point_inside_polygon_xy($xy, $anchor, $fieldXY);
    }
    if (count($shapeXY) > 1) {
        $first = $shapeXY[0];
        $last = $shapeXY[count($shapeXY) - 1];
        if (abs($first[0] - $last[0]) > 1e-9 || abs($first[1] - $last[1]) > 1e-9) {
            $shapeXY[] = $first;
        }
    }

    // Refine every edge so that also edge segments stay inside concave polygons.
    $refined = [];
    $splitEdgeInside = function (array $a, array $b, int $depth) use (&$splitEdgeInside, $fieldXY, $anchor): array {
        $aIn = point_in_polygon_xy($a, $fieldXY);
        $bIn = point_in_polygon_xy($b, $fieldXY);
        $mid = [($a[0] + $b[0]) * 0.5, ($a[1] + $b[1]) * 0.5];
        $midIn = point_in_polygon_xy($mid, $fieldXY);
        if ($depth <= 0 || ($aIn && $bIn && $midIn)) {
            return [$a, $b];
        }
        $aa = $aIn ? $a : clamp_point_inside_polygon_xy($a, $anchor, $fieldXY);
        $bb = $bIn ? $b : clamp_point_inside_polygon_xy($b, $anchor, $fieldXY);
        $mm = $midIn ? $mid : clamp_point_inside_polygon_xy($mid, $anchor, $fieldXY);
        $left = $splitEdgeInside($aa, $mm, $depth - 1);
        $right = $splitEdgeInside($mm, $bb, $depth - 1);
        array_pop($left);
        return array_merge($left, $right);
    };

    for ($i = 0; $i + 1 < count($shapeXY); $i++) {
        $seg = $splitEdgeInside($shapeXY[$i], $shapeXY[$i + 1], 7);
        if (empty($refined)) {
            $refined = $seg;
        } else {
            array_shift($seg);
            $refined = array_merge($refined, $seg);
        }
    }

    $allInside = function (array $poly) use ($fieldXY): bool {
        if (count($poly) < 3) {
            return true;
        }
        for ($i = 0; $i + 1 < count($poly); $i++) {
            $a = $poly[$i];
            $b = $poly[$i + 1];
            if (!point_in_polygon_xy($a, $fieldXY) || !point_in_polygon_xy($b, $fieldXY)) {
                return false;
            }
            for ($k = 1; $k <= 4; $k++) {
                $t = $k / 5.0;
                $m = [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t];
                if (!point_in_polygon_xy($m, $fieldXY)) {
                    return false;
                }
            }
        }
        return true;
    };

    // Hard safety: if any segment sample is still outside, shrink shape in place
    // (around its own centroid) to avoid drifting distortions.
    if (!$allInside($refined)) {
        $orig = $refined;
        $best = $refined;
        $ok = false;
        $cx = 0.0;
        $cy = 0.0;
        $cnt = max(1, count($orig));
        foreach ($orig as $p) {
            $cx += $p[0];
            $cy += $p[1];
        }
        $cx /= $cnt;
        $cy /= $cnt;
        for ($s = 0; $s < 35; $s++) {
            $factor = 1.0 - ($s + 1) * 0.02; // 0.98 .. 0.30
            $cand = [];
            foreach ($orig as $p) {
                $cand[] = [
                    $cx + ($p[0] - $cx) * $factor,
                    $cy + ($p[1] - $cy) * $factor
                ];
            }
            if ($allInside($cand)) {
                $best = $cand;
                $ok = true;
                break;
            }
        }
        if ($ok) {
            $refined = $best;
        }
    }

    $out = [];
    foreach ($refined as $p) {
        $ll = xy_to_lonlat($p, $lon0, $lat0);
        $out[] = [floatval($ll[0]), floatval($ll[1])];
    }
    return $out;
}

function fit_inscribed_rotated_rectangle(array $polygonLonLat, float $angleDeg, float $marginM = 0.0): array
{
    if (count($polygonLonLat) < 3) {
        return ['polygon' => [], 'area_m2' => 0.0];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $rotPoly = [];
    foreach ($polyXY as $p) {
        $rotPoly[] = rotate_xy($p, -$angleDeg);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($rotPoly);
    $height = max(1.0, $maxY - $minY);
    $rows = [];
    $rowCount = 60;
    for ($i = 0; $i <= $rowCount; $i++) {
        $y = $minY + ($height * $i / $rowCount);
        $segs = horizontal_intersections($rotPoly, $y);
        if (empty($segs)) {
            continue;
        }
        // choose widest in-field segment on this scanline
        $best = $segs[0];
        $bestW = floatval($best[1]) - floatval($best[0]);
        foreach ($segs as $s) {
            $w = floatval($s[1]) - floatval($s[0]);
            if ($w > $bestW) {
                $best = $s;
                $bestW = $w;
            }
        }
        $rows[] = [
            'y' => $y,
            'x0' => floatval($best[0]) + $marginM,
            'x1' => floatval($best[1]) - $marginM,
        ];
    }

    $bestArea = -1.0;
    $bestCorners = [];
    $nRows = count($rows);
    for ($i = 0; $i < $nRows; $i++) {
        $commonLeft = $rows[$i]['x0'];
        $commonRight = $rows[$i]['x1'];
        for ($j = $i + 1; $j < $nRows; $j++) {
            $commonLeft = max($commonLeft, $rows[$j]['x0']);
            $commonRight = min($commonRight, $rows[$j]['x1']);
            if ($commonRight <= $commonLeft) {
                break;
            }
            $h = $rows[$j]['y'] - $rows[$i]['y'];
            if ($h <= 0.5) {
                continue;
            }
            $w = $commonRight - $commonLeft;
            if ($w <= 0.5) {
                continue;
            }
            $area = $w * $h;
            if ($area <= $bestArea) {
                continue;
            }
            $bestArea = $area;
            $bestCorners = [
                [$commonLeft, $rows[$i]['y']],
                [$commonRight, $rows[$i]['y']],
                [$commonRight, $rows[$j]['y']],
                [$commonLeft, $rows[$j]['y']],
                [$commonLeft, $rows[$i]['y']],
            ];
        }
    }

    if (empty($bestCorners)) {
        return ['polygon' => [], 'area_m2' => 0.0];
    }
    $out = [];
    foreach ($bestCorners as $p) {
        $xy = rotate_xy($p, $angleDeg);
        $ll = xy_to_lonlat($xy, $lon0, $lat0);
        $out[] = [floatval($ll[0]), floatval($ll[1])];
    }
    $out = clamp_lonlat_polygon_inside_field($out, $polygonLonLat);
    return ['polygon' => $out, 'area_m2' => $bestArea];
}

function fit_inscribed_rotated_quadrilateral(array $polygonLonLat, float $angleDeg, float $marginM = 0.0): array
{
    if (count($polygonLonLat) < 3) {
        return ['polygon' => [], 'area_m2' => 0.0];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $rotPoly = [];
    foreach ($polyXY as $p) {
        $rotPoly[] = rotate_xy($p, -$angleDeg);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($rotPoly);
    $h = max(1.0, $maxY - $minY);

    $rows = [];
    $rowCount = 70;
    for ($i = 0; $i <= $rowCount; $i++) {
        $y = $minY + ($h * $i / $rowCount);
        $segs = horizontal_intersections($rotPoly, $y);
        if (empty($segs)) {
            continue;
        }
        $best = $segs[0];
        $bestW = floatval($best[1]) - floatval($best[0]);
        foreach ($segs as $s) {
            $w = floatval($s[1]) - floatval($s[0]);
            if ($w > $bestW) {
                $best = $s;
                $bestW = $w;
            }
        }
        $rows[] = [
            'y' => $y,
            'x0' => floatval($best[0]) + $marginM,
            'x1' => floatval($best[1]) - $marginM,
            'w' => max(0.0, floatval($best[1]) - floatval($best[0]) - 2.0 * $marginM)
        ];
    }
    if (count($rows) < 2) {
        return ['polygon' => [], 'area_m2' => 0.0];
    }

    $bestArea = -1.0;
    $bestQuad = [];
    $n = count($rows);
    for ($i = 0; $i < $n; $i++) {
        if ($rows[$i]['w'] <= 0.5) {
            continue;
        }
        for ($j = $i + 1; $j < $n; $j++) {
            if ($rows[$j]['w'] <= 0.5) {
                continue;
            }
            $hh = $rows[$j]['y'] - $rows[$i]['y'];
            if ($hh <= 0.5) {
                continue;
            }
            $area = (($rows[$i]['w'] + $rows[$j]['w']) * 0.5) * $hh;
            if ($area <= $bestArea) {
                continue;
            }
            $bestArea = $area;
            $bestQuad = [
                [$rows[$i]['x0'], $rows[$i]['y']],
                [$rows[$i]['x1'], $rows[$i]['y']],
                [$rows[$j]['x1'], $rows[$j]['y']],
                [$rows[$j]['x0'], $rows[$j]['y']],
                [$rows[$i]['x0'], $rows[$i]['y']],
            ];
        }
    }
    if (empty($bestQuad)) {
        return ['polygon' => [], 'area_m2' => 0.0];
    }
    $out = [];
    foreach ($bestQuad as $p) {
        $xy = rotate_xy($p, $angleDeg);
        $ll = xy_to_lonlat($xy, $lon0, $lat0);
        $out[] = [floatval($ll[0]), floatval($ll[1])];
    }
    $out = clamp_lonlat_polygon_inside_field($out, $polygonLonLat);
    return ['polygon' => $out, 'area_m2' => $bestArea];
}

function analyze_field_form(array $polygonLonLat, ?float $baseThetaDeg = null): array
{
    if (count($polygonLonLat) < 3) {
        return ['is_simple' => true, 'shape_type' => 'unbekannt', 'sections' => []];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $convex = polygon_is_convex_xy($polyXY);
    $shapeType = classify_basic_shape($polyXY);
    $isSimple = $convex && (count($polyXY) - 1) <= 6;

    $thetaDir = $baseThetaDeg !== null ? floatval($baseThetaDeg) : 0.0;
    $thetaForm = estimate_polygon_main_axis_deg($polyXY);
    $thetaSeed = fmod((0.9 * $thetaForm + 0.1 * $thetaDir + 360.0), 180.0);
    $rotSeed = [];
    foreach ($polyXY as $p) {
        $rotSeed[] = rotate_xy($p, -$thetaForm);
    }
    [$sx0, $sy0, $sx1, $sy1] = poly_bounds($rotSeed);
    $seedW = max(1e-9, $sx1 - $sx0);
    $seedH = max(1e-9, $sy1 - $sy0);
    $elongation = max($seedW / $seedH, $seedH / $seedW);

    $sections = [];
    $fieldArea = polygon_area_xy($polyXY);
    // Default behavior: one rotatable best-fit base pattern for the full field.
    // Angle optimization: global coarse scan + local refinement.
    $candidateAngles = [];
    if ($elongation >= 2.2) {
        // For long/narrow fields, force orientation close to major axis.
        for ($d = -12.0; $d <= 12.0; $d += 2.0) {
            $candidateAngles[] = fmod(($thetaForm + $d + 360.0), 180.0);
        }
    } else {
        for ($a = 0.0; $a < 180.0; $a += 5.0) {
            $candidateAngles[] = $a;
        }
        foreach ([$thetaSeed, $thetaForm, $thetaDir] as $a) {
            $candidateAngles[] = fmod(($a + 360.0), 180.0);
        }
    }
    $candidateAngles = array_values(array_unique(array_map(static fn($a) => round($a, 3), $candidateAngles)));
    $bestFit = ['polygon' => [], 'area_m2' => -1.0, 'angle' => $thetaSeed];
    foreach ($candidateAngles as $a) {
        $fr = fit_inscribed_rotated_rectangle($polygonLonLat, floatval($a), 0.0);
        $fq = fit_inscribed_rotated_quadrilateral($polygonLonLat, floatval($a), 0.0);
        $ar = floatval($fr['area_m2'] ?? 0.0);
        $aq = floatval($fq['area_m2'] ?? 0.0);
        $f = ($aq >= $ar * 0.97) ? $fq : $fr; // prefer quadrilateral when similar/better
        $area = floatval($f['area_m2'] ?? 0.0);
        if ($area > floatval($bestFit['area_m2'])) {
            $bestFit = ['polygon' => $f['polygon'] ?? [], 'area_m2' => $area, 'angle' => floatval($a)];
        }
    }
    // Local refinement around best coarse angle.
    $refinedBest = $bestFit;
    for ($a = $bestFit['angle'] - 4.0; $a <= $bestFit['angle'] + 4.0; $a += 1.0) {
        $aa = fmod(($a + 360.0), 180.0);
        $fr = fit_inscribed_rotated_rectangle($polygonLonLat, floatval($aa), 0.0);
        $fq = fit_inscribed_rotated_quadrilateral($polygonLonLat, floatval($aa), 0.0);
        $ar = floatval($fr['area_m2'] ?? 0.0);
        $aq = floatval($fq['area_m2'] ?? 0.0);
        $f = ($aq >= $ar * 0.97) ? $fq : $fr;
        $area = floatval($f['area_m2'] ?? 0.0);
        if ($area > floatval($refinedBest['area_m2'])) {
            $refinedBest = ['polygon' => $f['polygon'] ?? [], 'area_m2' => $area, 'angle' => floatval($aa)];
        }
    }
    $bestFit = $refinedBest;
    $theta = floatval($bestFit['angle']);
    if ($elongation >= 2.2) {
        // Narrow field: keep orientation strictly longitudinal.
        $theta = fmod(($thetaForm + 360.0), 180.0);
    }
    $fit = ['polygon' => $bestFit['polygon'], 'area_m2' => $bestFit['area_m2']];
    $rot = [];
    foreach ($polyXY as $p) {
        $rot[] = rotate_xy($p, -$theta);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($rot);
    $w = max(1.0, $maxX - $minX);
    $h = max(1.0, $maxY - $minY);
    $mx = min(8.0, $w * 0.04);
    $my = min(8.0, $h * 0.04);
    $sectionPoly = !empty($fit['polygon'])
        ? $fit['polygon']
        : clamp_lonlat_polygon_inside_field(
            rect_polygon_rotated($minX + $mx, $minY + $my, $maxX - $mx, $maxY - $my, $theta, $lon0, $lat0),
            $polygonLonLat
        );
    $primaryArea = floatval($fit['area_m2'] ?? 0.0);
    $rawCoverageRatio = ($fieldArea > 1e-9) ? ($primaryArea / $fieldArea) : 0.0;
    $coverageRatio = $rawCoverageRatio;
    $sections[] = [
        'id' => 'FS-1',
        'shape' => $isSimple ? $shapeType : 'zusammengesetzt_bestfit',
        'heading_deg' => $theta,
        'polygon' => $sectionPoly
    ];

    // Safety: coverage > 100% is invalid for the primary pattern.
    $invalidPrimaryCoverage = $rawCoverageRatio > 1.0 + 1e-9;

    // Narrow fields should be represented with multiple longitudinal rectangles.
    $isNarrowByShape = $elongation >= 2.2;
    $needMulti = $invalidPrimaryCoverage || $isNarrowByShape || ((!$isSimple) && ($coverageRatio < 0.52));
    $usedMultiSection = false;
    if ($needMulti) {
        [$rx0, $ry0, $rx1, $ry1] = poly_bounds($rot);
        $spanX = $rx1 - $rx0;
        $spanY = $ry1 - $ry0;
        $elongation = ($spanY > 1e-9) ? ($spanX / $spanY) : 999.0;
        if ($elongation < 1.0) {
            $elongation = 1.0 / max(1e-9, $elongation);
        }
        $parts = $isNarrowByShape ? 3 : (($coverageRatio < 0.42 && $elongation > 2.7) ? 3 : 2);
        $splitOnX = ($spanX >= $spanY);
        $axisMin = $splitOnX ? $rx0 : $ry0;
        $axisMax = $splitOnX ? $rx1 : $ry1;
        $axisSpan = max(1.0, $axisMax - $axisMin);
        $samples = [];
        $sxStep = max(1.0, $spanX / 70.0);
        $syStep = max(1.0, $spanY / 24.0);
        for ($yy = $ry0; $yy <= $ry1 + 1e-9; $yy += $syStep) {
            for ($xx = $rx0; $xx <= $rx1 + 1e-9; $xx += $sxStep) {
                if (!point_in_polygon_xy([$xx, $yy], $rot)) {
                    continue;
                }
                $samples[] = [$xx, $yy];
            }
        }
        if (count($samples) >= 40) {
            $mkPoly = static function (array $pts, float $thetaDeg, float $lon0, float $lat0): array {
                $minX = min(array_column($pts, 0));
                $maxX = max(array_column($pts, 0));
                $minY = min(array_column($pts, 1));
                $maxY = max(array_column($pts, 1));
                $poly = [[$minX, $minY], [$maxX, $minY], [$maxX, $maxY], [$minX, $maxY], [$minX, $minY]];
                $out = [];
                foreach ($poly as $q) {
                    $xy = rotate_xy($q, $thetaDeg);
                    $ll = xy_to_lonlat($xy, $lon0, $lat0);
                    $out[] = [floatval($ll[0]), floatval($ll[1])];
                }
                return $out;
            };
            $clusters = array_fill(0, $parts, []);
            $sliceW = $axisSpan / max(1, $parts);
            foreach ($samples as $p) {
                $v = $splitOnX ? $p[0] : $p[1];
                $ci = (int)floor(($v - $axisMin) / ($axisSpan / $parts));
                $ci = min($parts - 1, max(0, $ci));
                $clusters[$ci][] = $p;
            }
            // Fallback fill: if a slice is too sparse, borrow nearby samples around slice center.
            for ($ci = 0; $ci < $parts; $ci++) {
                if (count($clusters[$ci]) >= 6) {
                    continue;
                }
                $center = $axisMin + ($ci + 0.5) * $sliceW;
                foreach ($samples as $p) {
                    $v = $splitOnX ? $p[0] : $p[1];
                    if (abs($v - $center) <= 0.7 * $sliceW) {
                        $clusters[$ci][] = $p;
                    }
                }
            }
            $newSections = [];
            if ($isNarrowByShape) {
                // Build longitudinal "band rectangles" along major axis.
                $allY = array_column($samples, 1);
                sort($allY);
                $nAllY = count($allY);
                $gLo = $allY[max(0, min($nAllY - 1, (int)floor($nAllY * 0.08)))];
                $gHi = $allY[max(0, min($nAllY - 1, (int)ceil($nAllY * 0.92) - 1))];
                if ($gHi <= $gLo) {
                    $gLo = $ry0;
                    $gHi = $ry1;
                }
                for ($ci = 0; $ci < $parts; $ci++) {
                    $sliceA = $axisMin + $ci * $sliceW;
                    $sliceB = $axisMin + ($ci + 1) * $sliceW;
                    $cluster = [];
                    foreach ($samples as $p) {
                        $v = $splitOnX ? $p[0] : $p[1];
                        if ($v >= $sliceA && $v <= $sliceB) {
                            $cluster[] = $p;
                        }
                    }
                    if (count($cluster) < 6) {
                        continue;
                    }
                    $yLo = $gLo;
                    $yHi = $gHi;
                    if ($yHi <= $yLo) {
                        continue;
                    }
                    $rotBand = [
                        [$sliceA, $yLo],
                        [$sliceB, $yLo],
                        [$sliceB, $yHi],
                        [$sliceA, $yHi],
                        [$sliceA, $yLo],
                    ];
                    $bandLonLat = [];
                    foreach ($rotBand as $q) {
                        $xy = rotate_xy($q, $theta);
                        $ll = xy_to_lonlat($xy, $lon0, $lat0);
                        $bandLonLat[] = [floatval($ll[0]), floatval($ll[1])];
                    }
                    $polySec = shrink_shape_uniform_to_fit_field($bandLonLat, $polygonLonLat, 55);
                    $newSections[] = [
                        'id' => 'FS-' . strval($ci + 1),
                        'shape' => 'teilflaeche_' . strval($ci + 1),
                        'heading_deg' => $theta,
                        'polygon' => $polySec
                    ];
                }
            } else {
                for ($ci = 0; $ci < $parts; $ci++) {
                    $cluster = $clusters[$ci];
                    if (count($cluster) < 6) {
                        continue;
                    }
                    $polySec = clamp_lonlat_polygon_inside_field($mkPoly($cluster, $theta, $lon0, $lat0), $polygonLonLat);
                    $newSections[] = [
                        'id' => 'FS-' . strval($ci + 1),
                        'shape' => 'teilflaeche_' . strval($ci + 1),
                        'heading_deg' => $theta,
                        'polygon' => $polySec
                    ];
                }
            }
            if ($isNarrowByShape && count($newSections) === 3) {
                $sections = $newSections;
                $usedMultiSection = true;
            } elseif (!$isNarrowByShape && count($newSections) >= 2) {
                $sections = $newSections;
                $usedMultiSection = true;
            }
        }
    }
    // Safety clamp: keep drawn base patterns inside field boundary.
    foreach ($sections as $si => $sec) {
        $polySec = $sec['polygon'] ?? [];
        if (!is_array($polySec) || count($polySec) < 4) {
            continue;
        }
        if (!shape_polygon_inside_field($polySec, $polygonLonLat, 12)) {
            $sections[$si]['polygon'] = shrink_shape_uniform_to_fit_field($polySec, $polygonLonLat, 80);
        }
    }
    return [
        'is_simple' => $isSimple,
        'shape_type' => $shapeType,
        'sections' => $sections,
        'primary_coverage_ratio' => min(1.0, max(0.0, $coverageRatio)),
        'primary_coverage_ratio_raw' => $rawCoverageRatio,
        'invalid_primary_coverage' => $invalidPrimaryCoverage,
        'used_multi_section' => $usedMultiSection,
        'best_fit_angle_deg' => $theta,
        'elongation_ratio' => $elongation
    ];
}

function build_longitudinal_band_sections(array $polygonLonLat, int $parts): array
{
    if (count($polygonLonLat) < 3 || $parts < 1) {
        return [];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $thetaForm = estimate_polygon_main_axis_deg($polyXY);
    $candidates = [];
    for ($d = -25.0; $d <= 25.0; $d += 5.0) {
        $candidates[] = fmod(($thetaForm + $d + 360.0), 180.0);
    }
    for ($a = 0.0; $a < 180.0; $a += 15.0) {
        $candidates[] = $a;
    }
    $candidates = array_values(array_unique(array_map(static fn($v) => round($v, 3), $candidates)));

    $bestSections = [];
    $bestCoverage = -1.0;
    foreach ($candidates as $theta) {
        $rot = [];
        foreach ($polyXY as $p) {
            $rot[] = rotate_xy($p, -$theta);
        }
        [$rx0, $ry0, $rx1, $ry1] = poly_bounds($rot);
        $spanX = max(1.0, $rx1 - $rx0);
        $spanY = max(1.0, $ry1 - $ry0);
        $sxStep = max(1.0, $spanX / 70.0);
        $syStep = max(1.0, $spanY / 24.0);
        $samples = [];
        for ($yy = $ry0; $yy <= $ry1 + 1e-9; $yy += $syStep) {
            for ($xx = $rx0; $xx <= $rx1 + 1e-9; $xx += $sxStep) {
                if (!point_in_polygon_xy([$xx, $yy], $rot)) {
                    continue;
                }
                $samples[] = [$xx, $yy];
            }
        }
        if (count($samples) < 30) {
            continue;
        }
        $allY = array_column($samples, 1);
        sort($allY);
        $nAllY = count($allY);
        $gLo = $allY[max(0, min($nAllY - 1, (int)floor($nAllY * 0.02)))];
        $gHi = $allY[max(0, min($nAllY - 1, (int)ceil($nAllY * 0.98) - 1))];
        if ($gHi <= $gLo) {
            $gLo = $ry0;
            $gHi = $ry1;
        }
        $sliceW = ($rx1 - $rx0) / max(1, $parts);
        $sections = [];
        for ($ci = 0; $ci < $parts; $ci++) {
            $sliceA = $rx0 + $ci * $sliceW;
            $sliceB = $rx0 + ($ci + 1) * $sliceW;
            $rotBand = [
                [$sliceA, $gLo],
                [$sliceB, $gLo],
                [$sliceB, $gHi],
                [$sliceA, $gHi],
                [$sliceA, $gLo],
            ];
            $bandLonLat = [];
            foreach ($rotBand as $q) {
                $xy = rotate_xy($q, $theta);
                $ll = xy_to_lonlat($xy, $lon0, $lat0);
                $bandLonLat[] = [floatval($ll[0]), floatval($ll[1])];
            }
            // First clamp to preserve area; then shrink only if still outside.
            $polySec = clamp_lonlat_polygon_inside_field($bandLonLat, $polygonLonLat);
            if (!shape_polygon_inside_field($polySec, $polygonLonLat, 12)) {
                $polySec = shrink_shape_uniform_to_fit_field($polySec, $polygonLonLat, 120);
            }
            $sections[] = [
                'id' => 'FS-' . strval($ci + 1),
                'shape' => 'parallelogramm_band_' . strval($ci + 1),
                'heading_deg' => $theta,
                'polygon' => $polySec
            ];
        }
        $cov = sections_coverage_ratio($sections, $polygonLonLat, 72);
        if ($cov > $bestCoverage) {
            $bestCoverage = $cov;
            $bestSections = $sections;
        }
    }
    return $bestSections;
}

function build_track_rows(float $minY, float $maxY, float $spacing, float $maxEdgeDist): array
{
    $span = max(0.0, $maxY - $minY);
    if ($spacing <= 1e-9 || $span <= 1e-9) {
        return [($minY + $maxY) / 2.0];
    }

    $maxEdgeDist = max(0.0, $maxEdgeDist);
    $minLanes = max(1, (int)ceil(($span - 2.0 * $maxEdgeDist) / $spacing) + 1);
    $maxLanes = max(1, (int)floor($span / $spacing) + 1);
    $lanes = min(max($minLanes, 1), $maxLanes);
    $covered = ($lanes - 1) * $spacing;
    $margin = max(0.0, ($span - $covered) / 2.0);

    $rows = [];
    for ($i = 0; $i < $lanes; $i++) {
        $rows[] = $minY + $margin + $i * $spacing;
    }
    return $rows;
}

function build_lawnmower_points_for_rows(array $rotPoly, array $rows, float $angleDeg, float $endpointInset, bool $rowsForward = true): array
{
    $points = [];
    $nRows = count($rows);
    if ($nRows <= 0) {
        return $points;
    }
    $rowIndices = $rowsForward
        ? range(0, $nRows - 1)
        : range($nRows - 1, 0);
    foreach ($rowIndices as $rowIdx) {
        $rowY = floatval($rows[$rowIdx]);
        $segments = horizontal_intersections($rotPoly, $rowY);
        foreach ($segments as $seg) {
            $sx0 = floatval($seg[0]);
            $sx1 = floatval($seg[1]);
            if ($sx1 < $sx0) {
                $tmp = $sx0;
                $sx0 = $sx1;
                $sx1 = $tmp;
            }
            $segLen = max(0.0, $sx1 - $sx0);
            // Keep a small safety margin, but never shrink short segments to zero.
            $localInset = min($endpointInset, max(0.0, 0.5 * $segLen - 0.25));
            $x0 = $sx0 + $localInset;
            $x1 = $sx1 - $localInset;
            if ($x1 <= $x0) {
                continue;
            }
            $a = [$x0, $rowY];
            $b = [$x1, $rowY];
            if ($rowIdx % 2 === 1) {
                $tmp = $a;
                $a = $b;
                $b = $tmp;
            }
            $points[] = rotate_xy($a, $angleDeg);
            $points[] = rotate_xy($b, $angleDeg);
        }
    }
    return $points;
}

function intersect_lines_xy(array $p, array $dirP, array $q, array $dirQ): ?array
{
    $det = floatval($dirP[0]) * floatval($dirQ[1]) - floatval($dirP[1]) * floatval($dirQ[0]);
    if (abs($det) <= 1e-9) {
        return null;
    }
    $dx = floatval($q[0]) - floatval($p[0]);
    $dy = floatval($q[1]) - floatval($p[1]);
    $t = ($dx * floatval($dirQ[1]) - $dy * floatval($dirQ[0])) / $det;
    return [
        floatval($p[0]) + floatval($dirP[0]) * $t,
        floatval($p[1]) + floatval($dirP[1]) * $t
    ];
}

function normalize_vec2(array $v): ?array
{
    $len = hypot(floatval($v[0]), floatval($v[1]));
    if ($len <= 1e-9) {
        return null;
    }
    return [floatval($v[0]) / $len, floatval($v[1]) / $len];
}

function smooth_polyline_with_angle_step_xy(array $points, float $headingStepDeg = 20.0): array
{
    $n = count($points);
    if ($n < 3) {
        return $points;
    }
    $stepDeg = max(1.0, $headingStepDeg);
    $stepRad = deg2rad($stepDeg);
    $out = [[$points[0][0], $points[0][1]]];
    for ($i = 1; $i + 1 < $n; $i++) {
        $a = [floatval($points[$i - 1][0]), floatval($points[$i - 1][1])];
        $b = [floatval($points[$i][0]), floatval($points[$i][1])];
        $c = [floatval($points[$i + 1][0]), floatval($points[$i + 1][1])];
        $vInRaw = [$b[0] - $a[0], $b[1] - $a[1]];
        $vOutRaw = [$c[0] - $b[0], $c[1] - $b[1]];
        $lenIn = hypot($vInRaw[0], $vInRaw[1]);
        $lenOut = hypot($vOutRaw[0], $vOutRaw[1]);
        $vIn = normalize_vec2($vInRaw);
        $vOut = normalize_vec2($vOutRaw);
        if (!$vIn || !$vOut || $lenIn <= 1e-9 || $lenOut <= 1e-9) {
            $out[] = [$b[0], $b[1]];
            continue;
        }
        $dot = max(-1.0, min(1.0, $vIn[0] * $vOut[0] + $vIn[1] * $vOut[1]));
        $phi = acos($dot);
        if ($phi <= $stepRad + 1e-9 || $phi <= deg2rad(2.0)) {
            $out[] = [$b[0], $b[1]];
            continue;
        }
        $turnSign = ($vIn[0] * $vOut[1] - $vIn[1] * $vOut[0]) >= 0.0 ? 1.0 : -1.0;
        $tangentDist = min($lenIn, $lenOut) * 0.25;
        if ($tangentDist < 1.0) {
            $out[] = [$b[0], $b[1]];
            continue;
        }
        $t1 = [$b[0] - $vIn[0] * $tangentDist, $b[1] - $vIn[1] * $tangentDist];
        $t2 = [$b[0] + $vOut[0] * $tangentDist, $b[1] + $vOut[1] * $tangentDist];
        $n1 = $turnSign > 0.0 ? [-$vIn[1], $vIn[0]] : [$vIn[1], -$vIn[0]];
        $n2 = $turnSign > 0.0 ? [-$vOut[1], $vOut[0]] : [$vOut[1], -$vOut[0]];
        $center = intersect_lines_xy($t1, $n1, $t2, $n2);
        if (!$center) {
            $out[] = [$b[0], $b[1]];
            continue;
        }
        $r = hypot($t1[0] - $center[0], $t1[1] - $center[1]);
        if ($r <= 1e-6) {
            $out[] = [$b[0], $b[1]];
            continue;
        }
        $a1 = atan2($t1[1] - $center[1], $t1[0] - $center[0]);
        $a2 = atan2($t2[1] - $center[1], $t2[0] - $center[0]);
        if ($turnSign > 0.0) {
            while ($a2 < $a1) $a2 += 2.0 * pi();
        } else {
            while ($a2 > $a1) $a2 -= 2.0 * pi();
        }
        $delta = $a2 - $a1;
        $arcSteps = max(1, intval(ceil(abs($delta) / $stepRad)));
        $last = $out[count($out) - 1];
        if (hypot($last[0] - $t1[0], $last[1] - $t1[1]) > 1e-6) {
            $out[] = $t1;
        }
        for ($s = 1; $s < $arcSteps; $s++) {
            $tt = $s / $arcSteps;
            $aa = $a1 + $delta * $tt;
            $out[] = [$center[0] + cos($aa) * $r, $center[1] + sin($aa) * $r];
        }
        $out[] = $t2;
    }
    $lastPt = $points[$n - 1];
    $out[] = [floatval($lastPt[0]), floatval($lastPt[1])];
    return $out;
}

function plan_lawnmower(
    array $polygon_lonlat,
    array $start_lonlat,
    float $hfov_deg,
    float $agl_m,
    float $overlap,
    float $edge_factor,
    string $preferred_direction = 'auto',
    ?float $fixed_angle_deg = null,
    float $curveWaypointAngleStepDeg = 20.0
): array
{
    if (count($polygon_lonlat) < 3) {
        return [
            'path' => [],
            'angle' => 0.0,
            'W' => 0.0,
            'S' => 0.0,
            'narrow_field' => false,
            'field_width_m' => 0.0,
            'narrow_field_threshold_m' => 0.0,
            'edge_margin' => 0.0,
            'endpoint_inset' => 0.0,
            'curve_waypoint_angle_step_deg' => max(1.0, $curveWaypointAngleStepDeg),
        ];
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

    // Narrow-field rule: if width < 2*S, force lanes parallel to a real field edge (prefer longest edge).
    $majorAxisDeg = estimate_polygon_main_axis_deg($poly_xy);
    $longestEdgeInfo = longest_polygon_edge_angle_deg($polygon_lonlat);
    $edgeAxisDeg = floatval($longestEdgeInfo['angle_deg'] ?? $majorAxisDeg);
    if (!is_finite($edgeAxisDeg)) {
        $edgeAxisDeg = $majorAxisDeg;
    }
    $rotMajor = [];
    foreach ($poly_xy as $p) {
        $rotMajor[] = rotate_xy($p, -$edgeAxisDeg);
    }
    [$mx0, $my0, $mx1, $my1] = poly_bounds($rotMajor);
    $fieldWidthAlongMinor = max(0.0, $my1 - $my0);
    $isNarrowField = $fieldWidthAlongMinor < (2.0 * $S);

    $best = ['score' => INF, 'points' => []];
    $angles = [0.0, 90.0];
    if ($fixed_angle_deg !== null) {
        $angles = [fmod(($fixed_angle_deg + 360.0), 180.0)];
    } elseif ($isNarrowField) {
        $angles = [fmod(($edgeAxisDeg + 360.0), 180.0)];
    } else {
        $dir = strtolower(trim($preferred_direction));
        if ($dir === 'x') {
            $angles = [0.0];
        } elseif ($dir === 'y') {
            $angles = [90.0];
        }
    }
    foreach ($angles as $angle) {
        $rot_poly = [];
        foreach ($poly_xy as $p) {
            $rot_poly[] = rotate_xy($p, -$angle);
        }
        [$minx, $miny, $maxx, $maxy] = poly_bounds($rot_poly);
        $rows = build_track_rows($miny, $maxy, $S, $edge_margin);
        $spanY = max(0.0, $maxy - $miny);
        $centerY = 0.5 * ($miny + $maxy);
        $ultraNarrow = $isNarrowField && ($spanY < 1.2 * $S);
        if ($ultraNarrow && $spanY > 1e-6) {
            // Ultra-narrow fields: prefer one central strip flown forth and parallel back.
            // Keep both lanes centered (not at edges) and parallel to field borders.
            $sep = min(0.45 * $S, max(0.6, 0.35 * $spanY));
            $half = 0.5 * $sep;
            $guard = min(0.35, max(0.05, 0.05 * $spanY));
            $yA = max($miny + $guard, min($maxy - $guard, $centerY - $half));
            $yB = max($miny + $guard, min($maxy - $guard, $centerY + $half));
            if (abs($yB - $yA) < 0.2 && $spanY > 0.4) {
                $yA = $centerY - 0.1;
                $yB = $centerY + 0.1;
            }
            $rows = [$yA, $yB];
        }
        if ($isNarrowField && count($rows) < 2 && $spanY > 1e-6) {
            $inset = max(0.05 * $spanY, min(0.25 * $spanY, 1.0));
            $rows = [$miny + $inset, $maxy - $inset];
        }
        $endpointInset = max(0.0, min($edge_margin, $S * 0.5));
        // Try both stripe orders so the leg from the configured start to the first in-field
        // waypoint is as short as practical (same coverage, less deadhead / edge overlap).
        $bestPointsThisAngle = [];
        $bestScoreThisAngle = INF;
        foreach ([true, false] as $rowsForward) {
            $points = build_lawnmower_points_for_rows($rot_poly, $rows, $angle, $endpointInset, $rowsForward);

            // For narrow fields, enforce at least two lanes if geometry allows.
            if ($isNarrowField && count($points) < 4 && $spanY > 1e-6) {
                $fallbackRows = [$miny + 0.2 * $spanY, $miny + 0.8 * $spanY];
                $points = build_lawnmower_points_for_rows($rot_poly, $fallbackRows, $angle, $endpointInset, $rowsForward);
            }

            // Moderate endpoint extension inside each segment:
            // helps close uncovered edge zones without aggressive path distortion.
            if (!empty($points)) {
                $extendedPoints = [];
                $extendByM = max(0.0, min(6.0, 0.35 * $S));
                $nPts = count($points);
                for ($i = 0; $i + 1 < $nPts; $i += 2) {
                    $aXY = rotate_xy($points[$i], -$angle);
                    $bXY = rotate_xy($points[$i + 1], -$angle);
                    $rowY = 0.5 * (floatval($aXY[1]) + floatval($bXY[1]));
                    $segments = horizontal_intersections($rot_poly, $rowY);
                    if (empty($segments)) {
                        $extendedPoints[] = $points[$i];
                        $extendedPoints[] = $points[$i + 1];
                        continue;
                    }
                    $aX = floatval($aXY[0]);
                    $bX = floatval($bXY[0]);
                    $midX = 0.5 * ($aX + $bX);
                    $bestSeg = null;
                    $bestDist = INF;
                    foreach ($segments as $seg) {
                        $sx0 = floatval($seg[0]);
                        $sx1 = floatval($seg[1]);
                        if ($sx1 < $sx0) {
                            $tmp = $sx0;
                            $sx0 = $sx1;
                            $sx1 = $tmp;
                        }
                        if ($midX < $sx0 - 1e-9 || $midX > $sx1 + 1e-9) {
                            continue;
                        }
                        $dist = abs($midX - 0.5 * ($sx0 + $sx1));
                        if ($dist < $bestDist) {
                            $bestDist = $dist;
                            $bestSeg = [$sx0, $sx1];
                        }
                    }
                    if ($bestSeg === null) {
                        $extendedPoints[] = $points[$i];
                        $extendedPoints[] = $points[$i + 1];
                        continue;
                    }
                    $sx0 = $bestSeg[0];
                    $sx1 = $bestSeg[1];
                    $left = min($aX, $bX);
                    $right = max($aX, $bX);
                    // Last traverse only: modest extra reach toward polygon span on that row
                    // (extending the first strip as well distorted many fields vs. benefit).
                    $isLastStrip = ($i + 2 >= $nPts);
                    $ex = $extendByM;
                    if ($isLastStrip) {
                        $ex = max($extendByM, min(0.45 * $S, 10.0));
                    }
                    $newLeft = max($sx0, $left - $ex);
                    $newRight = min($sx1, $right + $ex);
                    if ($newRight <= $newLeft + 1e-9) {
                        $newLeft = $left;
                        $newRight = $right;
                    }
                    if ($aX <= $bX) {
                        $aNew = [$newLeft, $rowY];
                        $bNew = [$newRight, $rowY];
                    } else {
                        $aNew = [$newRight, $rowY];
                        $bNew = [$newLeft, $rowY];
                    }
                    $extendedPoints[] = rotate_xy($aNew, $angle);
                    $extendedPoints[] = rotate_xy($bNew, $angle);
                }
                if (count($extendedPoints) >= 2) {
                    $points = $extendedPoints;
                }
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
            if ($score < $bestScoreThisAngle) {
                $bestScoreThisAngle = $score;
                $bestPointsThisAngle = $points;
            }
        }

        // No waypoint interpolation on turns: keep a single waypoint at turns.
        // Curvature is visualized in UI via turn-distance guide lines.

        if (empty($bestPointsThisAngle)) {
            continue;
        }
        if ($bestScoreThisAngle < $best['score']) {
            $best = [
                'score' => $bestScoreThisAngle,
                'points' => $bestPointsThisAngle,
                'angle' => $angle,
                'W' => $W,
                'S' => $S,
                'edge_margin' => $edge_margin,
                'endpoint_inset' => $endpointInset
            ];
        }
    }

    $out = [];
    foreach ($best['points'] as $p) {
        $ll = xy_to_lonlat($p, $lon0, $lat0);
        $out[] = [$ll[0], $ll[1], $agl_m];
    }
    return [
        'path' => $out,
        'angle' => $best['angle'] ?? 0.0,
        'W' => $best['W'] ?? 0.0,
        'S' => $best['S'] ?? 0.0,
        'narrow_field' => $isNarrowField,
        'field_width_m' => $fieldWidthAlongMinor,
        'narrow_field_threshold_m' => 2.0 * $S,
        'edge_margin' => $best['edge_margin'] ?? 0.0,
        'endpoint_inset' => $best['endpoint_inset'] ?? 0.0
        ,'curve_waypoint_angle_step_deg' => max(1.0, $curveWaypointAngleStepDeg)
    ];
}

function derive_direction_from_height_points(array $heightPoints): array
{
    // heightPoints format: [lon, lat, z]
    if (count($heightPoints) < 3) {
        return [
            'preferred_direction' => 'auto',
            'fixed_theta_deg' => null,
            'source' => 'insufficient_height_points',
            'height_range_m' => 0.0
        ];
    }

    $lon0 = floatval($heightPoints[0][0]);
    $lat0 = floatval($heightPoints[0][1]);
    $x = [];
    $y = [];
    $z = [];
    foreach ($heightPoints as $p) {
        if (!isset($p[0], $p[1], $p[2])) {
            continue;
        }
        $xy = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
        $x[] = $xy[0];
        $y[] = $xy[1];
        $z[] = floatval($p[2]);
    }
    $n = count($x);
    if ($n < 3) {
        return [
            'preferred_direction' => 'auto',
            'fixed_theta_deg' => null,
            'source' => 'invalid_height_points',
            'height_range_m' => 0.0
        ];
    }
    $zMin = min($z);
    $zMax = max($z);
    $zRange = floatval($zMax - $zMin);

    $mx = array_sum($x) / $n;
    $my = array_sum($y) / $n;
    $mz = array_sum($z) / $n;
    $varX = 0.0;
    $varY = 0.0;
    $covXZ = 0.0;
    $covYZ = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $dx = $x[$i] - $mx;
        $dy = $y[$i] - $my;
        $dz = $z[$i] - $mz;
        $varX += $dx * $dx;
        $varY += $dy * $dy;
        $covXZ += $dx * $dz;
        $covYZ += $dy * $dz;
    }
    $gx = $varX > 1e-9 ? ($covXZ / $varX) : 0.0; // dz/dx
    $gy = $varY > 1e-9 ? ($covYZ / $varY) : 0.0; // dz/dy

    // Fly along contour direction to minimize climb: d = (-gy, gx).
    $dxContour = -$gy;
    $dyContour = $gx;
    $theta = rad2deg(atan2($dyContour, $dxContour));
    if (!is_finite($theta)) {
        $theta = 0.0;
    }
    $theta = fmod(($theta + 360.0), 180.0);

    // For x/y-only planners, choose axis with lower vertical change.
    $preferred = (abs($gx) <= abs($gy)) ? 'x' : 'y';
    return [
        'preferred_direction' => $preferred,
        'fixed_theta_deg' => $theta,
        'source' => 'height_points',
        'height_range_m' => $zRange,
        'gradient' => ['dz_dx' => $gx, 'dz_dy' => $gy]
    ];
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

function path_timing_with_turn_delay(array $path, float $speedMps, float $turnDelayS = 1.5, float $turnMinAngleDeg = 8.0): array
{
    $n = count($path);
    if ($n < 1 || $speedMps <= 0.0) {
        return [
            'speed_m_s' => max(0.0, $speedMps),
            'turn_delay_s' => max(0.0, $turnDelayS),
            'turn_min_angle_deg' => max(0.0, $turnMinAngleDeg),
            'total_time_s' => 0.0,
            'total_time_min' => 0.0,
            'distance_time_s' => 0.0,
            'turns_count' => 0,
            'turn_delay_total_s' => 0.0,
            'waypoints' => []
        ];
    }

    $turnDelayS = max(0.0, $turnDelayS);
    $turnMinAngleDeg = max(0.0, $turnMinAngleDeg);
    $lon0 = floatval($path[0][0] ?? 0.0);
    $lat0 = floatval($path[0][1] ?? 0.0);

    $ptsXY = [];
    for ($i = 0; $i < $n; $i++) {
        $p = $path[$i];
        $xy = lonlat_to_xy([floatval($p[0] ?? 0.0), floatval($p[1] ?? 0.0)], $lon0, $lat0);
        $ptsXY[] = [$xy[0], $xy[1]];
    }

    $rows = [];
    $cum = 0.0;
    $distanceTime = 0.0;
    $turnDelayTotal = 0.0;
    $turns = 0;

    $rows[] = [
        'index' => 0,
        'segment_distance_m' => 0.0,
        'segment_time_s' => 0.0,
        'turn_delay_s' => 0.0,
        'turn_angle_deg' => 0.0,
        'is_turn' => false,
        'cumulative_time_s' => 0.0
    ];

    for ($i = 1; $i < $n; $i++) {
        $a = $ptsXY[$i - 1];
        $b = $ptsXY[$i];
        $segDist = distance_m([$a[0], $a[1]], [$b[0], $b[1]]);
        $segTime = $segDist / $speedMps;
        $distanceTime += $segTime;
        $cum += $segTime;

        $turnAngle = 0.0;
        $isTurn = false;
        $addTurnDelay = 0.0;
        if ($i < $n - 1) {
            $c = $ptsXY[$i + 1];
            $v1x = $b[0] - $a[0];
            $v1y = $b[1] - $a[1];
            $v2x = $c[0] - $b[0];
            $v2y = $c[1] - $b[1];
            $n1 = sqrt($v1x * $v1x + $v1y * $v1y);
            $n2 = sqrt($v2x * $v2x + $v2y * $v2y);
            if ($n1 > 1e-9 && $n2 > 1e-9) {
                $dot = ($v1x * $v2x + $v1y * $v2y) / ($n1 * $n2);
                $dot = max(-1.0, min(1.0, $dot));
                $turnAngle = rad2deg(acos($dot));
                $isTurn = $turnAngle >= $turnMinAngleDeg;
            }
            if ($isTurn) {
                $addTurnDelay = $turnDelayS;
                $turns += 1;
                $turnDelayTotal += $addTurnDelay;
                $cum += $addTurnDelay;
            }
        }

        $rows[] = [
            'index' => $i,
            'segment_distance_m' => $segDist,
            'segment_time_s' => $segTime,
            'turn_delay_s' => $addTurnDelay,
            'turn_angle_deg' => $turnAngle,
            'is_turn' => $isTurn,
            'cumulative_time_s' => $cum
        ];
    }

    return [
        'speed_m_s' => $speedMps,
        'turn_delay_s' => $turnDelayS,
        'turn_min_angle_deg' => $turnMinAngleDeg,
        'total_time_s' => $cum,
        'total_time_min' => $cum / 60.0,
        'distance_time_s' => $distanceTime,
        'turns_count' => $turns,
        'turn_delay_total_s' => $turnDelayTotal,
        'waypoints' => $rows
    ];
}

function build_control_points_from_path(array $path, float $stepMeters, float $aglM = 0.0): array
{
    if (count($path) < 2 || $stepMeters <= 0.0) {
        return [];
    }
    $out = [];
    $distanceSinceLast = 0.0;
    $prev = $path[0];
    $out[] = [floatval($prev[0]), floatval($prev[1]), floatval($prev[2] ?? 0.0), $aglM];

    for ($i = 1; $i < count($path); $i++) {
        $curr = $path[$i];
        $lon0 = floatval($prev[0]);
        $lat0 = floatval($prev[1]);
        $a2 = lonlat_to_xy([floatval($prev[0]), floatval($prev[1])], $lon0, $lat0);
        $b2 = lonlat_to_xy([floatval($curr[0]), floatval($curr[1])], $lon0, $lat0);
        $segLen = distance_m([$a2[0], $a2[1]], [$b2[0], $b2[1]]);
        if ($segLen < 1e-9) {
            $prev = $curr;
            continue;
        }

        $segPos = 0.0;
        while ($distanceSinceLast + ($segLen - $segPos) >= $stepMeters) {
            $need = $stepMeters - $distanceSinceLast;
            $segPos += $need;
            $t = $segPos / $segLen;
            $lon = floatval($prev[0]) + (floatval($curr[0]) - floatval($prev[0])) * $t;
            $lat = floatval($prev[1]) + (floatval($curr[1]) - floatval($prev[1])) * $t;
            $alt = floatval($prev[2] ?? 0.0) + (floatval($curr[2] ?? 0.0) - floatval($prev[2] ?? 0.0)) * $t;
            $out[] = [$lon, $lat, $alt, $aglM];
            $distanceSinceLast = 0.0;
        }
        $distanceSinceLast += max(0.0, $segLen - $segPos);
        $prev = $curr;
    }

    $last = $path[count($path) - 1];
    $out[] = [floatval($last[0]), floatval($last[1]), floatval($last[2] ?? 0.0), $aglM];
    return $out;
}

function build_control_points_over_field(array $polygonLonLat, float $stepMeters, float $altM = 0.0): array
{
    if (count($polygonLonLat) < 3 || $stepMeters <= 0.0) {
        return [];
    }
    $poly = $polygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }

    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($polyXY);

    $out = [];
    for ($y = $minY; $y <= $maxY + 1e-9; $y += $stepMeters) {
        for ($x = $minX; $x <= $maxX + 1e-9; $x += $stepMeters) {
            if (!point_in_polygon_xy([$x, $y], $polyXY)) {
                continue;
            }
            $ll = xy_to_lonlat([$x, $y], $lon0, $lat0);
            $out[] = [floatval($ll[0]), floatval($ll[1]), $altM, 0.0];
        }
    }
    return $out;
}

function path_points_same_lonlat($a, $b, float $epsDeg = 1.0e-7): bool
{
    if (!is_array($a) || !is_array($b) || count($a) < 2 || count($b) < 2) {
        return false;
    }
    return abs(floatval($a[0]) - floatval($b[0])) < $epsDeg
        && abs(floatval($a[1]) - floatval($b[1])) < $epsDeg;
}

/** Number of leading waypoints that sit on the configured start (takeoff + climb). */
function path_start_prefix_len(array $path, array $startLonLat): int
{
    if (empty($path) || count($startLonLat) < 2) {
        return 0;
    }
    $n = 0;
    foreach ($path as $pt) {
        if (!path_points_same_lonlat($pt, $startLonLat)) {
            break;
        }
        $n++;
    }
    return $n;
}

/**
 * If a start point exists: path begins there at relative 0 m, climbs vertically
 * to cruise AGL, then flies to the nearest first in-field waypoint.
 */
function ensure_start_point_first(array $path, array $startLonLat, float $cruiseAlt, float $takeoffAlt = 0.0): array
{
    if (count($startLonLat) < 2) {
        return $path;
    }
    $cruiseAlt = floatval($cruiseAlt);
    $takeoffAlt = floatval($takeoffAlt);
    if ($takeoffAlt > $cruiseAlt) {
        $takeoffAlt = 0.0;
    }
    $startLon = floatval($startLonLat[0]);
    $startLat = floatval($startLonLat[1]);
    $startLL = [$startLon, $startLat];
    $ground = [$startLon, $startLat, $takeoffAlt];
    $atCruise = [$startLon, $startLat, $cruiseAlt];

    if (empty($path)) {
        if (abs($cruiseAlt - $takeoffAlt) < 1e-6) {
            return [$atCruise];
        }
        return [$ground, $atCruise];
    }

    // Choose the path direction whose first in-field waypoint is nearest to start.
    if (count($path) >= 2) {
        $a = $path[0];
        $b = $path[count($path) - 1];
        $sxy = lonlat_to_xy($startLL, $startLon, $startLat);
        $axy = lonlat_to_xy([floatval($a[0]), floatval($a[1])], $startLon, $startLat);
        $bxy = lonlat_to_xy([floatval($b[0]), floatval($b[1])], $startLon, $startLat);
        $da = distance_m([$sxy[0], $sxy[1]], [$axy[0], $axy[1]]);
        $db = distance_m([$sxy[0], $sxy[1]], [$bxy[0], $bxy[1]]);
        if ($db + 1e-9 < $da) {
            $path = array_reverse($path);
        }
    }

    while (!empty($path) && path_points_same_lonlat($path[0], $startLL)) {
        array_shift($path);
    }

    if (abs($cruiseAlt - $takeoffAlt) < 1e-6) {
        array_unshift($path, $atCruise);
        return $path;
    }
    array_unshift($path, $atCruise);
    array_unshift($path, $ground);
    return $path;
}

/** Last index that may lie outside the field: start/climb plus first in-field waypoint (approach). */
function path_outside_allow_until_index(array $path): int
{
    if (empty($path)) {
        return -1;
    }
    if (count($path) >= 2 && path_points_same_lonlat($path[0], $path[1])) {
        return max(1, path_start_prefix_len($path, $path[0]));
    }
    return 1;
}

function enforce_path_inside_field_except_first_leg(array $path, array $fieldPolygonLonLat, float $maxOutsideM = 0.0): array
{
    if (count($path) < 3 || count($fieldPolygonLonLat) < 3) {
        return $path;
    }
    $poly = $fieldPolygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($polyXY);
    $out = [];
    $maxOutsideM = max(0.0, $maxOutsideM);
    $allowUntil = path_outside_allow_until_index($path);
    for ($i = 0; $i < count($path); $i++) {
        $pt = $path[$i];
        if ($i <= $allowUntil) {
            $out[] = $pt; // Start, climb, and first field waypoint may lie outside.
            continue;
        }
        $xy = lonlat_to_xy([floatval($pt[0]), floatval($pt[1])], $lon0, $lat0);
        if (!point_in_polygon_xy($xy, $polyXY)) {
            $insideXY = clamp_point_inside_polygon_xy($xy, $anchor, $polyXY);
            $dx = floatval($xy[0]) - floatval($insideXY[0]);
            $dy = floatval($xy[1]) - floatval($insideXY[1]);
            $outsideDist = hypot($dx, $dy);
            if ($outsideDist > $maxOutsideM + 1e-9) {
                if ($outsideDist <= 1e-9) {
                    $xy = $insideXY;
                } else {
                    $t = $maxOutsideM / $outsideDist;
                    $xy = [
                        floatval($insideXY[0]) + $dx * $t,
                        floatval($insideXY[1]) + $dy * $t
                    ];
                }
            }
            $ll = xy_to_lonlat($xy, $lon0, $lat0);
            $pt[0] = floatval($ll[0]);
            $pt[1] = floatval($ll[1]);
        }
        $out[] = $pt;
    }
    return $out;
}

function enforce_path_inside_field_all_legs(array $path, array $fieldPolygonLonLat, float $maxOutsideM = 0.0): array
{
    if (count($path) < 2 || count($fieldPolygonLonLat) < 3) {
        return $path;
    }
    $poly = $fieldPolygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($polyXY);
    $out = [];
    $maxOutsideM = max(0.0, $maxOutsideM);
    $allowUntil = path_outside_allow_until_index($path);
    for ($i = 0; $i < count($path); $i++) {
        $pt = $path[$i];
        if ($i <= $allowUntil) {
            $out[] = $pt; // Approach start → field may fly outside the contour.
            continue;
        }
        $xy = lonlat_to_xy([floatval($pt[0]), floatval($pt[1])], $lon0, $lat0);
        if (!point_in_polygon_xy($xy, $polyXY)) {
            $insideXY = clamp_point_inside_polygon_xy($xy, $anchor, $polyXY);
            $dx = floatval($xy[0]) - floatval($insideXY[0]);
            $dy = floatval($xy[1]) - floatval($insideXY[1]);
            $outsideDist = hypot($dx, $dy);
            if ($outsideDist > $maxOutsideM + 1e-9) {
                if ($outsideDist <= 1e-9) {
                    $xy = $insideXY;
                } else {
                    $t = $maxOutsideM / $outsideDist;
                    $xy = [
                        floatval($insideXY[0]) + $dx * $t,
                        floatval($insideXY[1]) + $dy * $t
                    ];
                }
            }
            $ll = xy_to_lonlat($xy, $lon0, $lat0);
            $pt[0] = floatval($ll[0]);
            $pt[1] = floatval($ll[1]);
        }
        $out[] = $pt;
    }
    return $out;
}

function path_max_outside_m_except_first_leg(array $path, array $fieldPolygonLonLat): float
{
    if (count($path) < 3 || count($fieldPolygonLonLat) < 3) {
        return 0.0;
    }
    $poly = $fieldPolygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($polyXY);
    $maxOutside = 0.0;
    $allowUntil = path_outside_allow_until_index($path);
    for ($i = max(1, $allowUntil); $i + 1 < count($path); $i++) {
        $a = $path[$i];
        $b = $path[$i + 1];
        $axy = lonlat_to_xy([floatval($a[0]), floatval($a[1])], $lon0, $lat0);
        $bxy = lonlat_to_xy([floatval($b[0]), floatval($b[1])], $lon0, $lat0);
        $segLen = distance_m($axy, $bxy);
        $samples = max(1, intval(ceil($segLen / 10.0)));
        for ($s = 0; $s <= $samples; $s++) {
            $t = $samples > 0 ? ($s / $samples) : 0.0;
            $x = floatval($axy[0]) + (floatval($bxy[0]) - floatval($axy[0])) * $t;
            $y = floatval($axy[1]) + (floatval($bxy[1]) - floatval($axy[1])) * $t;
            $p = [$x, $y];
            if (point_in_polygon_xy($p, $polyXY)) {
                continue;
            }
            $inside = clamp_point_inside_polygon_xy($p, $anchor, $polyXY);
            $d = hypot(floatval($p[0]) - floatval($inside[0]), floatval($p[1]) - floatval($inside[1]));
            if ($d > $maxOutside) {
                $maxOutside = $d;
            }
        }
    }
    return $maxOutside;
}

function path_max_outside_m_all_legs(array $path, array $fieldPolygonLonLat): float
{
    if (count($path) < 2 || count($fieldPolygonLonLat) < 3) {
        return 0.0;
    }
    $poly = $fieldPolygonLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    $anchor = find_inside_anchor_xy($polyXY);
    $maxOutside = 0.0;
    for ($i = 0; $i + 1 < count($path); $i++) {
        $a = $path[$i];
        $b = $path[$i + 1];
        $axy = lonlat_to_xy([floatval($a[0]), floatval($a[1])], $lon0, $lat0);
        $bxy = lonlat_to_xy([floatval($b[0]), floatval($b[1])], $lon0, $lat0);
        $segLen = distance_m($axy, $bxy);
        $samples = max(1, intval(ceil($segLen / 10.0)));
        for ($s = 0; $s <= $samples; $s++) {
            $t = $samples > 0 ? ($s / $samples) : 0.0;
            $x = floatval($axy[0]) + (floatval($bxy[0]) - floatval($axy[0])) * $t;
            $y = floatval($axy[1]) + (floatval($bxy[1]) - floatval($axy[1])) * $t;
            $p = [$x, $y];
            if (point_in_polygon_xy($p, $polyXY)) {
                continue;
            }
            $inside = clamp_point_inside_polygon_xy($p, $anchor, $polyXY);
            $d = hypot(floatval($p[0]) - floatval($inside[0]), floatval($p[1]) - floatval($inside[1]));
            if ($d > $maxOutside) {
                $maxOutside = $d;
            }
        }
    }
    return $maxOutside;
}

function path_coverage_ratio_by_width_and_turns(array $pathLonLatAlt, array $fieldPolyLonLat, float $widthM, float $turnDiameterM = 0.0, int $gridN = 80): float
{
    if (count($pathLonLatAlt) < 3 || count($fieldPolyLonLat) < 3 || $widthM <= 0.0) {
        return 0.0;
    }
    $poly = $fieldPolyLonLat;
    if ($poly[0] !== $poly[count($poly) - 1]) {
        $poly[] = $poly[0];
    }
    $lon0 = floatval($poly[0][0]);
    $lat0 = floatval($poly[0][1]);
    $polyXY = [];
    foreach ($poly as $p) {
        $polyXY[] = lonlat_to_xy([floatval($p[0]), floatval($p[1])], $lon0, $lat0);
    }
    [$minX, $minY, $maxX, $maxY] = poly_bounds($polyXY);
    $r = max(0.0, $widthM * 0.5);
    $r2 = $r * $r;

    $segments = [];
    for ($i = 1; $i + 1 < count($pathLonLatAlt); $i++) {
        $a = $pathLonLatAlt[$i];
        $b = $pathLonLatAlt[$i + 1];
        $axy = lonlat_to_xy([floatval($a[0]), floatval($a[1])], $lon0, $lat0);
        $bxy = lonlat_to_xy([floatval($b[0]), floatval($b[1])], $lon0, $lat0);
        $segments[] = [$axy, $bxy];
    }
    if (empty($segments)) {
        return 0.0;
    }
    $turnR = max(0.0, $turnDiameterM * 0.5);
    $turnR2 = $turnR * $turnR;
    $turnPts = [];
    if ($turnR > 0.0 && count($pathLonLatAlt) >= 4) {
        for ($i = 2; $i + 1 < count($pathLonLatAlt); $i++) {
            $p0 = lonlat_to_xy([floatval($pathLonLatAlt[$i - 1][0]), floatval($pathLonLatAlt[$i - 1][1])], $lon0, $lat0);
            $p1 = lonlat_to_xy([floatval($pathLonLatAlt[$i][0]), floatval($pathLonLatAlt[$i][1])], $lon0, $lat0);
            $p2 = lonlat_to_xy([floatval($pathLonLatAlt[$i + 1][0]), floatval($pathLonLatAlt[$i + 1][1])], $lon0, $lat0);
            $v1x = $p1[0] - $p0[0];
            $v1y = $p1[1] - $p0[1];
            $v2x = $p2[0] - $p1[0];
            $v2y = $p2[1] - $p1[1];
            $n1 = hypot($v1x, $v1y);
            $n2 = hypot($v2x, $v2y);
            if ($n1 <= 1e-9 || $n2 <= 1e-9) {
                continue;
            }
            $dot = (($v1x * $v2x) + ($v1y * $v2y)) / ($n1 * $n2);
            $dot = max(-1.0, min(1.0, $dot));
            $ang = rad2deg(acos($dot));
            if ($ang >= 20.0) {
                $turnPts[] = [$p1[0], $p1[1]];
            }
        }
    }

    $inField = 0;
    $covered = 0;
    for ($ix = 0; $ix < $gridN; $ix++) {
        $x = $minX + ($ix + 0.5) * (($maxX - $minX) / max(1, $gridN));
        for ($iy = 0; $iy < $gridN; $iy++) {
            $y = $minY + ($iy + 0.5) * (($maxY - $minY) / max(1, $gridN));
            $pt = [$x, $y];
            if (!point_in_polygon_xy($pt, $polyXY)) {
                continue;
            }
            $inField++;
            foreach ($segments as $seg) {
                $d = point_to_segment_distance_xy($pt, $seg[0], $seg[1]);
                if (($d * $d) <= $r2 + 1e-9) {
                    $covered++;
                    break;
                }
            }
            if ($turnR > 0.0 && !empty($turnPts)) {
                foreach ($turnPts as $tp) {
                    $dx = $x - $tp[0];
                    $dy = $y - $tp[1];
                    if (($dx * $dx + $dy * $dy) <= $turnR2 + 1e-9) {
                        $covered++;
                        break;
                    }
                }
            }
        }
    }
    if ($inField <= 0) {
        return 0.0;
    }
    return max(0.0, min(1.0, $covered / $inField));
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
        $stdFlughoehe = floatval((string)$f->StdFlughohe);
        if ($stdFlughoehe <= 0.0 && !empty($flugweg)) {
            $stdFlughoehe = floatval($flugweg[0][2] ?? 0.0);
        }
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
                'std_flughoehe_m' => $stdFlughoehe,
            ],
        ];
    }
    return $fields;
}

function resolve_fields_xml_path(): string
{
    return mapll_fields_xml_path();
}

