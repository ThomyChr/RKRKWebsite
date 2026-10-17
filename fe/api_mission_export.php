<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';
require_once __DIR__ . '/dji_wpml_spec.php';
require_once __DIR__ . '/wpml_kmz_export.php';

if (!function_exists('resolve_mission_intern_paths')) {
function resolve_mission_intern_paths(): array
{
    return mapll_mission_intern_paths();
}
}

if (!function_exists('ensure_mission_intern_structure')) {
function ensure_mission_intern_structure(array $paths): void
{
    $dirs = [
        $paths['root'],
        $paths['xml_dir'],
        $paths['csv_dir'],
        $paths['temp_dir'],
        $paths['history_dir'],
        $paths['log_dir'],
        $paths['deleted_dir'],
        $paths['region_dir'],
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            fail_resp(500, 'mkdir_failed', 'Verzeichnis konnte nicht erstellt werden: ' . $dir);
        }
    }
}
}

function sanitize_filename(string $value): string
{
    $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $value);
    return trim((string)$safe, '_');
}

function rkrk_mission_file_token(string $value): string
{
    $value = trim($value);
    $token = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $value);
    if (!is_string($token) || $token === '') {
        $token = sanitize_filename($value);
    }
    return trim($token, '_');
}

function rkrk_export_pilot_code(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $keys = ['Initialen'];
    if (isset($GLOBALS['SessNme_RegisteredInitialen']) && is_string($GLOBALS['SessNme_RegisteredInitialen'])) {
        array_unshift($keys, $GLOBALS['SessNme_RegisteredInitialen']);
    }
    foreach ($keys as $key) {
        $raw = trim((string)($_SESSION[$key] ?? ''));
        if ($raw === '') {
            continue;
        }
        $code = rkrk_mission_file_token($raw);
        if ($code !== '') {
            return $code;
        }
    }
    return 'FE';
}

function rkrk_catalog_grundstueck_id(string $feldId): string
{
    $feldId = trim($feldId);
    if ($feldId === '') {
        return '';
    }
    $io = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'rkrk_catalog_io.php';
    if (!function_exists('rkrk_catalog_load_simplexml') && is_file($io)) {
        require_once $io;
    }
    if (!function_exists('rkrk_catalog_load_simplexml') || !function_exists('mapll_fields_xml_path')) {
        return '';
    }
    $xmlPath = mapll_fields_xml_path();
    if ($xmlPath === '') {
        return '';
    }
    $xml = rkrk_catalog_load_simplexml($xmlPath);
    if (!($xml instanceof SimpleXMLElement)) {
        return '';
    }
    foreach ($xml->Feld as $f) {
        if (trim((string)($f->FeldID ?? '')) === $feldId) {
            return trim((string)($f->GrundstueckID ?? ''));
        }
    }
    return '';
}

/** Feldkatalognummer: gesendete FeldID, sonst Treffer im 3T-Katalog über XML-ID oder Name und GIS. */
function rkrk_catalog_feld_id_for_field(array $field): string
{
    $feldId = trim((string)($field['feld_id'] ?? ''));
    if ($feldId !== '' && strcasecmp($feldId, 'UNDEF') !== 0) {
        return $feldId;
    }
    $io = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'rkrk_catalog_io.php';
    if (!function_exists('rkrk_catalog_load_simplexml') && is_file($io)) {
        require_once $io;
    }
    if (!function_exists('rkrk_catalog_load_simplexml') || !function_exists('mapll_fields_xml_path')) {
        return '';
    }
    $xmlPath = mapll_fields_xml_path();
    if ($xmlPath === '') {
        return '';
    }
    $xml = rkrk_catalog_load_simplexml($xmlPath);
    if (!($xml instanceof SimpleXMLElement)) {
        return '';
    }
    $internalId = trim((string)($field['id'] ?? ''));
    $wantName = rkrk_mission_file_token(trim((string)($field['name'] ?? '')));
    $wantGis = trim((string)($field['grundstueck_id'] ?? $field['gis'] ?? ''));
    $nameHits = [];
    foreach ($xml->Feld as $f) {
        $id = trim((string)($f->FeldID ?? ''));
        if ($id === '') {
            continue;
        }
        if ($internalId !== '' && (string)$f['id'] === $internalId) {
            return $id;
        }
        $name = rkrk_mission_file_token(trim((string)($f->Feldname ?? '')));
        $gis = trim((string)($f->GrundstueckID ?? ''));
        if ($wantName !== '' && strcasecmp($name, $wantName) === 0 && ($wantGis === '' || $gis === $wantGis)) {
            $nameHits[] = $id;
        }
    }
    return count($nameHits) === 1 ? $nameHits[0] : '';
}

/** Katalognummer_Feldname_GISNummer_Flughöhe_Pilotenkürzel_Jahr(YY). */
function rkrk_mission_name_parts(string $feldId, string $feldName, string $gis, string $height, string $pilot = '', ?string $year = null): array
{
    $kat = rkrk_mission_file_token($feldId !== '' ? $feldId : 'UNDEF');
    $name = rkrk_mission_file_token($feldName !== '' ? $feldName : 'Feld');
    $gisToken = rkrk_mission_file_token($gis);
    if ($gisToken === '') {
        $gisToken = '0';
    }
    $heightNum = preg_replace('/[^0-9.]+/', '', $height) ?? '';
    if ($heightNum === '' || !is_numeric($heightNum)) {
        $heightNum = '60';
    }
    $heightToken = (string)intval(round(floatval($heightNum)));
    $pilotToken = rkrk_mission_file_token($pilot !== '' ? $pilot : rkrk_export_pilot_code());
    if ($pilotToken === '') {
        $pilotToken = 'FE';
    }
    $yy = (is_string($year) && preg_match('/^\d{2}$/', $year)) ? $year : date('y');
    $feldname = $name . '_' . $gisToken . '_' . $heightToken . '_' . $pilotToken . '_' . $yy;
    $complete = $kat . '_' . $feldname;
    return [
        'feldname' => $feldname,
        'complete' => $complete,
    ];
}

function rkrk_mission_name_parts_from_field(array $field, array $missionSettings = []): array
{
    $feldId = trim((string)($field['feld_id'] ?? ''));
    $name = trim((string)($field['name'] ?? ''));
    $gis = trim((string)($field['grundstueck_id'] ?? $field['gis'] ?? ''));
    if ($gis === '') {
        $gis = rkrk_catalog_grundstueck_id($feldId);
    }
    $height = trim((string)($missionSettings['altitude_m'] ?? ''));
    if ($height === '') {
        $height = trim((string)($field['std_flughoehe'] ?? '60'));
    }
    $pilot = trim((string)($field['pilot_code'] ?? ''));
    return rkrk_mission_name_parts($feldId, $name, $gis, $height, $pilot);
}

function ensure_field_mission_dir(array $paths, string $feldId): string
{
    $fieldDir = $paths['region_dir'] . DIRECTORY_SEPARATOR . sanitize_filename($feldId !== '' ? $feldId : 'UNDEF');
    if (!is_dir($fieldDir) && !mkdir($fieldDir, 0777, true) && !is_dir($fieldDir)) {
        fail_resp(500, 'mkdir_failed', 'Feldverzeichnis konnte nicht erstellt werden: ' . $fieldDir);
    }
    return $fieldDir;
}

function fail_resp(int $code, string $error, string $detail = ''): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $error, 'detail' => $detail], JSON_UNESCAPED_UNICODE);
    exit;
}

function parse_pairs_from_text(string $raw): array
{
    preg_match_all('/-?\d+(?:\.\d+)?/', $raw, $m);
    $nums = $m[0] ?? [];
    $out = [];
    for ($i = 0; $i + 1 < count($nums); $i += 2) {
        $out[] = [floatval($nums[$i]), floatval($nums[$i + 1])];
    }
    return $out;
}

function parse_triplets_from_text(string $raw): array
{
    preg_match_all('/-?\d+(?:\.\d+)?/', $raw, $m);
    $nums = $m[0] ?? [];
    $out = [];
    for ($i = 0; $i + 2 < count($nums); $i += 3) {
        $out[] = [floatval($nums[$i]), floatval($nums[$i + 1]), floatval($nums[$i + 2])];
    }
    return $out;
}

if (!function_exists('format_triplets_text')) {
function format_triplets_text(array $pts): string
{
    $parts = [];
    foreach ($pts as $p) {
        if (!is_array($p) || count($p) < 3) {
            continue;
        }
        $parts[] = rtrim(rtrim(sprintf('%.13F', floatval($p[0])), '0'), '.') . ', ' .
            rtrim(rtrim(sprintf('%.13F', floatval($p[1])), '0'), '.') . ', ' .
            rtrim(rtrim(sprintf('%.3F', floatval($p[2])), '0'), '.');
    }
    return implode('    ', $parts);
}
}

function mission_settings_defaults(): array
{
    $droneDefaults = drone_specific_defaults_by_name('Mavic 3T');
    return [
        'drone_type' => (string)($droneDefaults['drone_type'] ?? 'Mavic 3T'),
        'kmz_params_active' => true,
        'simple_mode' => true,
        'altitude_m' => 60.0,
        'flight_direction_deg' => -1.0,
        'speed_m_s' => 5.0,
        'overlap_pct' => 15.0,
        'edge_factor_pct' => 40.0,
        'start_speed_active' => false,
        'start_speed_m_s' => 5.0,
        'hfov_deg' => floatval($droneDefaults['hfov_deg'] ?? 49.0),
        'vfov_deg' => floatval($droneDefaults['vfov_deg'] ?? 40.0),
        'dfov_deg' => floatval($droneDefaults['dfov_deg'] ?? 61.0),
        'end_action' => 'Aufgabe beenden',
        'heading_mode' => 'Manuell',
        'waypoint_type' => 'Koordinierte Drehung, Ueberspringt den Wegpunkt',
        'waypoint_settings_mode' => 'route_follow',
        'standard_turn_distance_m' => 5.0,
        'safe_start_altitude_m' => 20.0,
        'direct_to_start_position' => false,
        'gimbal_mode' => 'Manuell',
        'altitude_mode' => 'ALT (Relativ zum Startpunkt)',
        'waypoint_overrides' => [],
    ];
}

function normalize_mission_settings(array $missionSettings): array
{
    $d = mission_settings_defaults();
    $droneType = trim((string)($missionSettings['drone_type'] ?? $d['drone_type']));
    $droneSpecific = drone_specific_defaults_by_name($droneType);
    $hfovDeg = array_key_exists('hfov_deg', $droneSpecific) ? floatval($droneSpecific['hfov_deg']) : floatval($d['hfov_deg']);
    $vfovDeg = array_key_exists('vfov_deg', $droneSpecific) ? floatval($droneSpecific['vfov_deg']) : floatval($d['vfov_deg']);
    $dfovDeg = array_key_exists('dfov_deg', $droneSpecific) ? floatval($droneSpecific['dfov_deg']) : floatval($d['dfov_deg']);
    $pois = export_mission_pois($missionSettings);
    return [
        'drone_type' => $droneType,
        'kmz_params_active' => (bool)($missionSettings['kmz_params_active'] ?? $d['kmz_params_active']),
        'simple_mode' => (bool)($missionSettings['simple_mode'] ?? $d['simple_mode']),
        'altitude_m' => floatval($missionSettings['altitude_m'] ?? $d['altitude_m']),
        'flight_direction_deg' => floatval($missionSettings['flight_direction_deg'] ?? $d['flight_direction_deg']),
        'speed_m_s' => floatval($missionSettings['speed_m_s'] ?? $d['speed_m_s']),
        'overlap_pct' => floatval($missionSettings['overlap_pct'] ?? $d['overlap_pct']),
        'edge_factor_pct' => floatval($missionSettings['edge_factor_pct'] ?? $d['edge_factor_pct']),
        'start_speed_active' => (bool)($missionSettings['start_speed_active'] ?? $d['start_speed_active']),
        'start_speed_m_s' => floatval($missionSettings['start_speed_m_s'] ?? $d['start_speed_m_s']),
        // Drone specific camera parameters come exclusively from DroneSettings reference.
        'hfov_deg' => $hfovDeg,
        'vfov_deg' => $vfovDeg,
        'dfov_deg' => $dfovDeg,
        'end_action' => trim((string)($missionSettings['end_action'] ?? $d['end_action'])),
        'heading_mode' => trim((string)($missionSettings['heading_mode'] ?? $d['heading_mode'])),
        'waypoint_type' => trim((string)($missionSettings['waypoint_type'] ?? $d['waypoint_type'])),
        'waypoint_settings_mode' => (trim((string)($missionSettings['waypoint_settings_mode'] ?? $d['waypoint_settings_mode'])) === 'per_waypoint')
            ? 'per_waypoint'
            : 'route_follow',
        'standard_turn_distance_m' => floatval($missionSettings['standard_turn_distance_m'] ?? $d['standard_turn_distance_m']),
        'safe_start_altitude_m' => floatval($missionSettings['safe_start_altitude_m'] ?? $d['safe_start_altitude_m']),
        'direct_to_start_position' => (bool)($missionSettings['direct_to_start_position'] ?? $d['direct_to_start_position']),
        'gimbal_mode' => trim((string)($missionSettings['gimbal_mode'] ?? $d['gimbal_mode'])),
        'gimbal_pitch' => (isset($missionSettings['gimbal_pitch']) && is_numeric($missionSettings['gimbal_pitch']))
            ? max(-90.0, min(30.0, floatval($missionSettings['gimbal_pitch'])))
            : null,
        'altitude_mode' => trim((string)($missionSettings['altitude_mode'] ?? $d['altitude_mode'])),
        'poi' => $pois[0] ?? null,
        'pois' => $pois,
        'waypoint_overrides' => normalize_waypoint_overrides_raw($missionSettings['waypoint_overrides'] ?? []),
    ];
}

function export_mission_pois(array $mission): array
{
    $out = [];
    $raw = $mission['pois'] ?? null;
    if (is_array($raw)) {
        foreach ($raw as $item) {
            $poi = clean_export_poi($item);
            if ($poi !== null) {
                $out[] = $poi;
            }
        }
    }
    if ($out === []) {
        $one = clean_export_poi($mission['poi'] ?? null);
        if ($one !== null) {
            $out[] = $one;
        }
    }
    return $out;
}

/** Missions- oder Wegpunkt-POI: Länge, Breite, Höhe. */
function clean_export_poi($raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $lon = isset($raw['longitude']) ? floatval($raw['longitude']) : (isset($raw['lng']) ? floatval($raw['lng']) : NAN);
    $lat = isset($raw['latitude']) ? floatval($raw['latitude']) : (isset($raw['lat']) ? floatval($raw['lat']) : NAN);
    if (!is_finite($lon) || !is_finite($lat)) {
        return null;
    }
    $height = isset($raw['height']) && is_numeric($raw['height']) ? floatval($raw['height']) : 0.0;
    return [
        'longitude' => round($lon, 6),
        'latitude' => round($lat, 6),
        'height' => round($height, 1),
    ];
}

/** DJI waypointPoiPoint: Breite, Länge, Höhe. */
function export_poi_point_csv($raw): string
{
    $poi = clean_export_poi($raw);
    if ($poi === null) {
        return '';
    }
    $fmt = static function (float $v, int $dec): string {
        return rtrim(rtrim(sprintf('%.' . $dec . 'F', $v), '0'), '.');
    };
    return $fmt($poi['latitude'], 6) . ',' . $fmt($poi['longitude'], 6) . ',' . $fmt($poi['height'], 1);
}

function normalize_waypoint_overrides_raw($raw): array
{
    if ($raw === null || $raw === '') {
        return [];
    }
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) {
        return [];
    }
    $boolKeys = [
        'use_mission_yaw',
        'use_mission_waypoint_type',
        'use_mission_speed',
        'use_mission_height',
        'use_mission_direction_mode',
        'use_mission_path_mode',
        'use_mission_turn_distance',
        'auto_height',
    ];
    $out = [];
    foreach ($raw as $k => $row) {
        if (!is_array($row)) {
            continue;
        }
        $idx = (int)preg_replace('/\D+/', '', (string)$k);
        if ($idx < 1) {
            continue;
        }
        $sk = (string)$idx;
        $clean = [];
        foreach ($boolKeys as $bk) {
            if (array_key_exists($bk, $row)) {
                $clean[$bk] = (bool)$row[$bk];
            }
        }
        if (isset($row['heading_mode'])) {
            $clean['heading_mode'] = trim((string)$row['heading_mode']);
        }
        if (isset($row['waypoint_type'])) {
            $clean['waypoint_type'] = trim((string)$row['waypoint_type']);
        }
        if (isset($row['speed_m_s']) && is_numeric($row['speed_m_s'])) {
            $clean['speed_m_s'] = floatval($row['speed_m_s']);
        }
        if (isset($row['agl_m']) && is_numeric($row['agl_m'])) {
            $clean['agl_m'] = floatval($row['agl_m']);
        }
        if (isset($row['direction_deg']) && is_numeric($row['direction_deg'])) {
            $clean['direction_deg'] = floatval($row['direction_deg']);
        }
        if (isset($row['path_mode']) && is_string($row['path_mode'])) {
            $pm = strtolower(trim((string)$row['path_mode']));
            if ($pm === 'straight' || $pm === 'curve') {
                $clean['path_mode'] = $pm;
            }
        }
        if (isset($row['turning_distance_m']) && is_numeric($row['turning_distance_m'])) {
            $clean['turning_distance_m'] = floatval($row['turning_distance_m']);
        }
        if (isset($row['gimbal_pitch']) && is_numeric($row['gimbal_pitch'])) {
            $clean['gimbal_pitch'] = floatval($row['gimbal_pitch']);
        }
        if (isset($row['alignment_aircraft']) && is_string($row['alignment_aircraft'])) {
            $al = trim((string)$row['alignment_aircraft']);
            if ($al !== '') {
                $clean['alignment_aircraft'] = $al;
            }
        }
        if (isset($row['heading_angle']) && is_numeric($row['heading_angle'])) {
            $clean['heading_angle'] = floatval($row['heading_angle']);
        }
        $poi = clean_export_poi($row['poi'] ?? null);
        if ($poi !== null) {
            $clean['poi'] = $poi;
        }
        if ($clean !== []) {
            $out[$sk] = $clean;
        }
    }
    ksort($out, SORT_NATURAL);
    return $out;
}

function waypoint_override_row(array $mission, int $index1): array
{
    $map = isset($mission['waypoint_overrides']) && is_array($mission['waypoint_overrides']) ? $mission['waypoint_overrides'] : [];
    $row = $map[(string)$index1] ?? null;
    return is_array($row) ? $row : [];
}

function effective_ch_flags_for_waypoint(array $mission, int $index1): array
{
    $base = ch_waypoint_use_mission_flags($mission);
    $ov = waypoint_override_row($mission, $index1);
    if (array_key_exists('use_mission_yaw', $ov)) {
        $base['useMissionYawDirection'] = (bool)$ov['use_mission_yaw'];
    }
    if (array_key_exists('use_mission_waypoint_type', $ov)) {
        $base['useMissionWaypointType'] = (bool)$ov['use_mission_waypoint_type'];
    }
    if (array_key_exists('use_mission_speed', $ov)) {
        $base['useMissionSpeed'] = (bool)$ov['use_mission_speed'];
    }
    if (array_key_exists('use_mission_height', $ov)) {
        $base['useMissionHeight'] = (bool)$ov['use_mission_height'];
    }
    if (array_key_exists('use_mission_direction_mode', $ov)) {
        $base['useMissionDirectionMode'] = (bool)$ov['use_mission_direction_mode'];
    }
    if (array_key_exists('use_mission_path_mode', $ov)) {
        $base['useMissionPathMode'] = (bool)$ov['use_mission_path_mode'];
    }
    if (array_key_exists('use_mission_turn_distance', $ov)) {
        $base['useMissionTurnDistance'] = (bool)$ov['use_mission_turn_distance'];
    }
    $headingNow = !empty($base['useMissionYawDirection'])
        ? (string)($mission['heading_mode'] ?? '')
        : (string)($ov['heading_mode'] ?? ($mission['heading_mode'] ?? ''));
    $headingNow = mb_strtolower(trim($headingNow));
    if ($headingNow === 'definiert' || $headingNow === 'fixed') {
        $base['useMissionPathMode'] = true;
        $base['useMissionDirectionMode'] = true;
    }
    return $base;
}

/** Pfadmodus-Alias fuer Overrides (`straight` | `curve`) -> gleiche WPML/CH-Bundles wie bekannte deutsche Wegpunkttypen. */
function ch_path_bundle_from_path_mode_slug(string $slug): array
{
    $s = strtolower(trim($slug));
    if ($s === 'curve') {
        return german_waypoint_type_to_wpml_bundle('Gekruemmte Route. Fluggeraet setzt fort');
    }
    return german_waypoint_type_to_wpml_bundle('Koordinierte Drehung, Ueberspringt den Wegpunkt');
}

function load_drone_profiles_from_reference(): array
{
    ensure_dji_kmz_reference_loaded();
    $settings = isset($GLOBALS['DroneSettings']) && is_array($GLOBALS['DroneSettings']) ? $GLOBALS['DroneSettings'] : [];
    $out = [];
    foreach ($settings as $key => $cfg) {
        if (!is_array($cfg)) continue;
        // Strict rule: only drone types with editor=true are selectable in editor UI.
        if (!array_key_exists('editor', $cfg) || $cfg['editor'] !== true) {
            continue;
        }
        $name = trim((string)($cfg['Name'] ?? $key));
        $hfov = floatval($cfg['IRCamAngle_H'] ?? 0);
        $vfov = floatval($cfg['IRCamAngle_V'] ?? 0);
        $dfov = floatval($cfg['DFOV'] ?? 0);
        $out[] = [
            'key' => (string)$key,
            'name' => $name !== '' ? $name : (string)$key,
            'hfov_deg' => $hfov,
            'vfov_deg' => $vfov,
            'dfov_deg' => $dfov,
            'uav_drone_type' => trim((string)($cfg['UAVDroneType'] ?? '')),
            'editor' => true,
        ];
    }
    usort($out, static function (array $a, array $b): int {
        return mb_strtolower((string)($a['name'] ?? '')) <=> mb_strtolower((string)($b['name'] ?? ''));
    });
    return $out;
}

function drone_specific_defaults_by_name(string $droneType): array
{
    $needle = mb_strtolower(trim($droneType));
    $profiles = load_drone_profiles_from_reference();
    if ($needle === '') {
        return [];
    }
    foreach ($profiles as $p) {
        $name = mb_strtolower(trim((string)($p['name'] ?? '')));
        $key = mb_strtolower(trim((string)($p['key'] ?? '')));
        if ($needle === $name || $needle === $key) {
            return [
                'drone_type' => (string)($p['name'] ?? $droneType),
                'hfov_deg' => floatval($p['hfov_deg'] ?? 0.0),
                'vfov_deg' => floatval($p['vfov_deg'] ?? 0.0),
                'dfov_deg' => floatval($p['dfov_deg'] ?? 0.0),
            ];
        }
    }
    return [];
}

function effective_kmz_settings(array $missionSettings): array
{
    if (!empty($missionSettings['kmz_params_active'])) {
        return $missionSettings;
    }
    $defaults = mission_settings_defaults();
    // Keep selected drone type, but ignore custom KMZ parameters.
    $defaults['drone_type'] = trim((string)($missionSettings['drone_type'] ?? $defaults['drone_type']));
    $defaults['waypoint_settings_mode'] = (trim((string)($missionSettings['waypoint_settings_mode'] ?? 'route_follow')) === 'per_waypoint')
        ? 'per_waypoint'
        : 'route_follow';
    $defaults['waypoint_overrides'] = normalize_waypoint_overrides_raw($missionSettings['waypoint_overrides'] ?? []);
    $defaults['kmz_params_active'] = false;
    return $defaults;
}

function load_interface_mapping_config(): array
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'interface_mapping_data.json';
    if (!is_file($path)) {
        fail_resp(500, 'mapping_config_missing', 'Mapping-Datei fehlt: interface_mapping_data.json');
    }
    $raw = @file_get_contents($path);
    $json = $raw ? json_decode($raw, true) : null;
    if (!is_array($json)) {
        fail_resp(500, 'mapping_config_invalid', 'Mapping-Datei ist ungueltig');
    }
    return $json;
}

function ensure_dji_kmz_reference_loaded(): void
{
    if (isset($GLOBALS['DroneSettings']) && is_array($GLOBALS['DroneSettings'])
        && isset($GLOBALS['DJI_DroneInformation']) && is_array($GLOBALS['DJI_DroneInformation'])) {
        return;
    }
    $djiRef = mapll_scripts_file('rkrk_dji_kmz_interface.php');
    if ($djiRef === null || !is_file($djiRef)) {
        fail_resp(500, 'dji_reference_missing', 'DJI Referenzdatei fehlt: rkrk_dji_kmz_interface.php');
    }
    $cwd = getcwd();
    $scriptsParent = dirname((string)mapll_scripts_dir());
    try {
        if ($scriptsParent !== '') {
            @chdir($scriptsParent);
        }
        ob_start();
        require_once $djiRef;
        ob_end_clean();
        // Included file may be evaluated in function scope. Expose required arrays explicitly.
        if (!isset($GLOBALS['DroneSettings']) && isset($DroneSettings) && is_array($DroneSettings)) {
            $GLOBALS['DroneSettings'] = $DroneSettings;
        }
        if (!isset($GLOBALS['DJI_DroneInformation']) && isset($DJI_DroneInformation) && is_array($DJI_DroneInformation)) {
            $GLOBALS['DJI_DroneInformation'] = $DJI_DroneInformation;
        }
    } catch (Throwable $e) {
        fail_resp(500, 'dji_reference_load_failed', 'DJI Referenz konnte nicht geladen werden: ' . $e->getMessage());
    } finally {
        if (is_string($cwd) && $cwd !== '') {
            @chdir($cwd);
        }
    }
    if (!isset($GLOBALS['DJI_DroneInformation']) || !is_array($GLOBALS['DJI_DroneInformation'])) {
        fail_resp(500, 'dji_reference_invalid', 'DJI Referenz geladen, aber DJI_DroneInformation fehlt');
    }
}

function resolve_uav_drone_type_slug(string $droneTypeLabel): string
{
    ensure_dji_kmz_reference_loaded();
    $needle = mb_strtolower(trim($droneTypeLabel));
    if ($needle === '') {
        return 'mavic3T';
    }
    $settings = isset($GLOBALS['DroneSettings']) && is_array($GLOBALS['DroneSettings']) ? $GLOBALS['DroneSettings'] : [];
    foreach ($settings as $arrKey => $cfg) {
        if (!is_array($cfg)) {
            continue;
        }
        $name = mb_strtolower(trim((string)($cfg['Name'] ?? '')));
        $slug = mb_strtolower(trim((string)($cfg['UAVDroneType'] ?? '')));
        $k = mb_strtolower(trim((string)$arrKey));
        if ($needle === $name || $needle === $slug || $needle === $k) {
            $out = trim((string)($cfg['UAVDroneType'] ?? ''));
            return $out !== '' ? $out : 'mavic3T';
        }
    }
    return 'mavic3T';
}

function wpml_height_mode_from_mission_altitude_mode(string $deMode): string
{
    if (function_exists('dji_spec_to_wpml')) {
        $wpml = dji_spec_to_wpml('wpml:heightMode', $deMode, '');
        if ($wpml !== '') {
            return $wpml;
        }
    }
    $m = mb_strtolower(trim($deMode));
    if (str_contains($m, 'ellipsoid') && str_contains($m, 'wgs')) {
        return 'WGS84';
    }
    if (str_contains($m, 'egm') || str_contains($m, 'amsl') || str_contains($m, 'meer')) {
        return 'EGM96';
    }
    return 'relativeToStartPoint';
}

function mission_route_follow_active(array $missionSettings): bool
{
    return (($missionSettings['waypoint_settings_mode'] ?? 'route_follow') !== 'per_waypoint');
}

function all_waypoints_use_mission_flag(array $mission, string $overrideKey): bool
{
    $map = $mission['waypoint_overrides'] ?? [];
    if (!is_array($map)) {
        return true;
    }
    foreach ($map as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (array_key_exists($overrideKey, $row) && !$row[$overrideKey]) {
            return false;
        }
    }
    return true;
}

function ch_waypoint_use_mission_flags(array $missionSettings): array
{
    // Default: Route folgen = Missionswerte. Nur explizite Punkt-Flags schalten auf gespeicherte Punktwerte.
    return [
        'useMissionDirectionMode' => true,
        'useMissionPathMode' => true,
        'useMissionYawDirection' => true,
        'useMissionWaypointType' => true,
        'useMissionSpeed' => true,
        'useMissionHeight' => true,
        'useMissionTurnDistance' => true,
    ];
}

function german_waypoint_type_to_wpml_bundle(string $waypointType): array
{
    if (function_exists('dji_spec_waypoint_bundle_from_internal')) {
        $bundle = dji_spec_waypoint_bundle_from_internal($waypointType);
        if (!empty($bundle['global_wpml_turn'])) {
            return $bundle;
        }
    }
    $t = trim($waypointType);
    if ($t === 'Gerade Flugroute. Fluggeraet stoppt') {
        return [
            'global_wpml_turn' => 'toPointAndStopWithDiscontinuityCurvature',
            'ch_waypoint_type' => 'toPointAndStopWithDiscontinuityCurvature',
            'path_mode' => 'straight',
            'use_straight_line' => 1,
        ];
    }
    if ($t === 'Gekruemmte Route. Fluggeraet setzt fort') {
        return [
            'global_wpml_turn' => 'toPointAndPassWithContinuityCurvature',
            'ch_waypoint_type' => 'toPointAndPassWithContinuityCurvature',
            'path_mode' => 'curve',
            'use_straight_line' => 0,
        ];
    }
    if ($t === 'Gekruemmte Route. Fluggeraet stoppt') {
        return [
            'global_wpml_turn' => 'toPointAndStopWithContinuityCurvature',
            'ch_waypoint_type' => 'toPointAndStopWithContinuityCurvature',
            'path_mode' => 'curve',
            'use_straight_line' => 0,
        ];
    }
    if ($t === 'Wendet vor dem Wegpunkt. Fliegt durch') {
        return [
            'global_wpml_turn' => 'toPointAndPassWithContinuityCurvature2',
            'ch_waypoint_type' => 'toPointAndPassWithContinuityCurvature2',
            'path_mode' => 'curve',
            'use_straight_line' => 1,
        ];
    }
    return [
        'global_wpml_turn' => 'coordinateTurn',
        'ch_waypoint_type' => 'coordinateTurn',
        'path_mode' => 'straight',
        'use_straight_line' => 1,
    ];
}

function german_heading_mode_to_wpml_global(string $heading): string
{
    if (function_exists('dji_spec_to_wpml')) {
        $wpml = dji_spec_to_wpml('wpml:waypointHeadingMode', $heading, '');
        if ($wpml !== '') {
            return $wpml;
        }
    }
    $g = mb_strtolower(trim($heading));
    if (str_contains($g, 'poi') || str_contains($g, 'fokus')) {
        return 'towardPOI';
    }
    if (str_contains($g, 'definiert')) {
        return 'smoothTransition';
    }
    if (str_contains($g, 'schenk') || str_contains($g, 'schwenk')) {
        return 'fixed';
    }
    if ((str_contains($g, 'richtung') && str_contains($g, 'wegpunkt')) || str_contains($g, 'flugroute') || str_contains($g, 'followwayline')) {
        return 'followWayline';
    }
    if (str_contains($g, 'manuel')) {
        return 'manually';
    }
    return 'followWayline';
}

function german_heading_mode_to_ch_direction_strings(string $heading, bool $hasPoi = false): array
{
    $g = mb_strtolower(trim($heading));
    if (str_contains($g, 'definiert')) {
        return ['directionType' => 'followWayline', 'yawDirection' => 'customised'];
    }
    if (str_contains($g, 'schenk') || str_contains($g, 'schwenk')) {
        return ['directionType' => 'followWayline', 'yawDirection' => 'fixed'];
    }
    if ((str_contains($g, 'poi') || str_contains($g, 'fokus')) && $hasPoi) {
        return ['directionType' => 'towardPOI', 'yawDirection' => 'smoothTransition'];
    }
    if (str_contains($g, 'manuel')) {
        return ['directionType' => 'followWayline', 'yawDirection' => 'manually'];
    }
    return ['directionType' => 'followWayline', 'yawDirection' => 'followWayline'];
}

function wpml_heading_path_mode_from_internal(string $alignment): string
{
    if (function_exists('dji_spec_to_wpml')) {
        $wpml = dji_spec_to_wpml('wpml:waypointHeadingPathMode', $alignment, '');
        if ($wpml !== '') {
            return $wpml;
        }
    }
    $a = mb_strtolower(trim($alignment));
    if (str_contains($a, 'gegen') || $a === 'counterclockwise') {
        return 'counterClockwise';
    }
    if (str_contains($a, 'uhrzeig') || $a === 'clockwise') {
        return 'clockwise';
    }
    return 'followBadArc';
}

/** Kinder von wpml:waypointHeadingParam bei lokalem Heading (useGlobalHeadingParam 0 am Wegpunkt). */
function wpml_waypoint_heading_param_children_from_german(string $germanHeading, array $ov = []): array
{
    $g = mb_strtolower(trim($germanHeading));
    $pathMode = 'followBadArc';
    if (str_contains($g, 'schenk') || str_contains($g, 'schwenk')) {
        $mode = 'fixed';
    } elseif (str_contains($g, 'poi') || str_contains($g, 'fokus')) {
        $mode = 'towardPOI';
    } elseif (str_contains($g, 'definiert')) {
        $mode = 'smoothTransition';
    } elseif (str_contains($g, 'manuel')) {
        $mode = 'manually';
    } else {
        $mode = 'followWayline';
    }
    if (isset($ov['alignment_aircraft']) && is_string($ov['alignment_aircraft']) && trim((string)$ov['alignment_aircraft']) !== '') {
        $pathMode = wpml_heading_path_mode_from_internal((string)$ov['alignment_aircraft']);
    }
    $children = [
        'wpml:waypointHeadingMode' => $mode,
        'wpml:waypointHeadingYawPathMode' => $pathMode,
        'wpml:waypointHeadingPathMode' => $pathMode,
    ];
    if ($mode === 'smoothTransition') {
        $angle = null;
        if (isset($ov['heading_angle']) && $ov['heading_angle'] !== '' && $ov['heading_angle'] !== null && is_numeric($ov['heading_angle'])) {
            $angle = floatval($ov['heading_angle']);
        } elseif (isset($ov['direction_deg']) && $ov['direction_deg'] !== '' && $ov['direction_deg'] !== null && is_numeric($ov['direction_deg'])) {
            $angle = floatval($ov['direction_deg']);
        }
        if ($angle !== null && is_finite($angle)) {
            $children['wpml:waypointHeadingAngle'] = max(-180.0, min(180.0, $angle));
        }
    }
    return $children;
}

function build_export_mapping(array $settings, array $config): array
{
    $rows = is_array($config['rows'] ?? null) ? $config['rows'] : [];
    $kmzActive = !empty($settings['kmz_params_active']);
    $alwaysActiveForms = ['msDroneType', 'msKmzParamsActive'];

    $resolved = [];
    foreach ($rows as $r) {
        if (!is_array($r)) continue;
        $sk = (string)($r['settings_key'] ?? '');
        $value = ($sk !== '' && array_key_exists($sk, $settings)) ? $settings[$sk] : null;
        $r['value'] = $value;
        $form = (string)($r['form'] ?? '');
        $r['displayed'] = true;
        $r['active_used'] = $kmzActive || in_array($form, $alwaysActiveForms, true);
        $r['active_option'] = $value;
        if (!array_key_exists('ui_options', $r)) {
            if (($r['type'] ?? '') === 'boolean') {
                $r['ui_options'] = [true, false];
            } else {
                $r['ui_options'] = [];
            }
        }
        $r['status'] = ((string)($r['uav'] ?? '') !== '' && (string)($r['dji'] ?? '') !== '') ? 'mapped' : 'partial';
        $resolved[] = $r;
    }

    return [
        'source' => (string)($config['source'] ?? 'field_editor.mission_settings'),
        'formats' => is_array($config['formats'] ?? null) ? $config['formats'] : [],
        'coverage' => [
            'mapped_count' => count($resolved),
            'fully_mapped_count' => count(array_filter($resolved, static fn($r) => ($r['status'] ?? '') === 'mapped')),
        ],
        'rows' => $resolved,
        'form_to_uav_json' => [
            'msDroneType -> drone_control_settings.drone_type' => $settings['drone_type'],
            'msSimpleMode -> drone_control_settings.simple_mode' => $settings['simple_mode'],
            'msSpeed -> drone_control_settings.speed_m_s' => $settings['speed_m_s'],
            'msStartSpeedActive -> drone_control_settings.start_speed_active' => $settings['start_speed_active'],
            'msStartSpeed -> drone_control_settings.start_speed_m_s' => $settings['start_speed_m_s'],
            'msEndAction -> drone_control_settings.end_action' => $settings['end_action'],
            'msHeadingMode -> drone_control_settings.heading_mode' => $settings['heading_mode'],
            'msWaypointType -> drone_control_settings.waypoint_type' => $settings['waypoint_type'],
            'msWaypointSettingsMode -> drone_control_settings.waypoint_settings_mode' => $settings['waypoint_settings_mode'],
            'msTurnDistance -> drone_control_settings.standard_turn_distance_m' => $settings['standard_turn_distance_m'],
            'msSafeStartAltitude -> drone_control_settings.safe_start_altitude_m' => $settings['safe_start_altitude_m'],
            'msDirectToStart -> drone_control_settings.direct_to_start_position' => $settings['direct_to_start_position'],
            'msGimbalMode -> drone_control_settings.gimbal_mode' => $settings['gimbal_mode'],
            'msAltitudeMode -> drone_control_settings.altitude_mode' => $settings['altitude_mode'],
        ],
        'form_to_dji_kmz' => [
            'msSpeed -> wpml:autoFlightSpeed / globalTransitionalSpeed' => $settings['speed_m_s'],
            'msSafeStartAltitude -> wpml:takeOffSecurityHeight' => $settings['safe_start_altitude_m'],
            'msAltitudeMode -> wpml:heightMode=relativeToStartPoint, useGlobalHeight=0, height=Flughoehe+Gelaendedelta' => $settings['altitude_mode'],
            'msHeadingMode -> wpml:globalWaypointHeadingParam.wpml:waypointHeadingMode' => $settings['heading_mode'],
            'msWaypointType -> wpml:globalWaypointTurnMode / waypointTurnParam' => $settings['waypoint_type'],
            'msWaypointSettingsMode -> wpml:useGlobal* / waypointHeadingParam je Wegpunkt' => $settings['waypoint_settings_mode'],
            'msTurnDistance -> wpml:waypointTurnDampingDist je Wegpunkt (Koordinierte Drehung, Distanz>0)' => $settings['standard_turn_distance_m'],
            'msGimbalMode -> wpml:gimbalPitchMode' => $settings['gimbal_mode'],
            'msEndAction -> wpml:finishAction' => $settings['end_action'],
            'msDroneType -> wpml:droneInfo / payloadInfo profile selection' => $settings['drone_type'],
        ],
        'field_geometry' => is_array($config['field_geometry'] ?? null) ? $config['field_geometry'] : [],
        'config_file' => __DIR__ . DIRECTORY_SEPARATOR . 'interface_mapping_data.json',
        'dji_reference_file' => (string)(mapll_scripts_file('rkrk_dji_kmz_interface.php') ?? ''),
    ];
}

function build_kmz_waypoint_extensions(array $missionSettings, int $i0, float $aglTriplet): array
{
    $idx1 = $i0 + 1;
    $ov = waypoint_override_row($missionSettings, $idx1);
    $flags = effective_ch_flags_for_waypoint($missionSettings, $idx1);
    $altM = clamp_export_agl_m(floatval($missionSettings['altitude_m'] ?? 60.0));
    $missionSpeed = clamp_export_speed_m_s(floatval($missionSettings['speed_m_s'] ?? 5.0));
    $hStr = !empty($flags['useMissionYawDirection'])
        ? (string)($missionSettings['heading_mode'] ?? '')
        : (string)($ov['heading_mode'] ?? $missionSettings['heading_mode'] ?? '');
    $wtStr = !empty($flags['useMissionWaypointType'])
        ? (string)($missionSettings['waypoint_type'] ?? '')
        : (string)($ov['waypoint_type'] ?? $missionSettings['waypoint_type'] ?? '');
    $bundle = german_waypoint_type_to_wpml_bundle($wtStr);
    $headingOv = !empty($flags['useMissionYawDirection']) ? [] : $ov;
    $headingChildren = wpml_waypoint_heading_param_children_from_german($hStr, $headingOv);
    // useGlobalHeadingParam gehört zur Gierrichtung. Richtungsmodus und Pfadmodus haben kein KMZ-Tag.
    $useGlobalHeading = !empty($flags['useMissionYawDirection']) ? 1 : 0;
    $useGlobalSpeed = !empty($flags['useMissionSpeed']) ? 1 : 0;
    $useGlobalHeight = !empty($flags['useMissionHeight']) ? 1 : 0;
    $useGlobalTurn = !empty($flags['useMissionWaypointType']) ? 1 : 0;
    $ptSpeed = $useGlobalSpeed ? $missionSpeed : clamp_export_speed_m_s(floatval($ov['speed_m_s'] ?? $missionSpeed));
    if ($useGlobalHeight) {
        $relForKmz = $altM;
    } else {
        $relForKmz = clamp_export_agl_m(floatval($ov['agl_m'] ?? $aglTriplet));
    }
    $relForKmz = clamp_wpml_rel_height_m($relForKmz);
    $useMissionTurn = !array_key_exists('useMissionTurnDistance', $flags) || !empty($flags['useMissionTurnDistance']);
    $dampSrc = $useMissionTurn
        ? floatval($missionSettings['standard_turn_distance_m'] ?? 0.0)
        : floatval($ov['turning_distance_m'] ?? ($missionSettings['standard_turn_distance_m'] ?? 0.0));
    $damp = clamp_export_turn_m($dampSrc);
    $autoHeight = !array_key_exists('auto_height', $ov) || !empty($ov['auto_height']);
    $gimbal = export_gimbal_pitch_deg($missionSettings, $ov);
    $headingModeName = (string)($headingChildren['wpml:waypointHeadingMode'] ?? '');
    $poiRaw = $ov['poi'] ?? null;
    if ($headingModeName === 'towardPOI' && clean_export_poi($poiRaw) === null) {
        $poiRaw = $missionSettings['poi'] ?? null;
    }
    return [
        '_rel_height_hint' => $relForKmz,
        'wpml_turn_mode' => $bundle['global_wpml_turn'],
        'wpml_use_straight_line' => $bundle['use_straight_line'],
        'wpml_use_global_heading' => $useGlobalHeading,
        'wpml_use_global_speed' => $useGlobalSpeed,
        'wpml_use_global_height' => $useGlobalHeight,
        'wpml_use_global_turn' => $useGlobalTurn,
        'wpml_waypoint_speed' => $ptSpeed,
        'wpml_heading_children' => $headingChildren,
        'wpml_turn_damping_m' => $damp,
        'auto_height' => $autoHeight,
        'gimbal_pitch' => $gimbal,
        'poi_point' => $headingModeName === 'towardPOI' ? export_poi_point_csv($poiRaw) : '',
    ];
}

function random_ch_mission_id(int $len = 9): string
{
    $alph = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $out = '';
    $max = strlen($alph) - 1;
    for ($i = 0; $i < $len; $i++) {
        $out .= $alph[random_int(0, $max)];
    }
    return $out;
}

/** Google Encoded Polyline (precision 5) — gleiche Kodierung wie in den CH `samples/*.json` lineString-Feldern. */
function google_polyline_encode_precision5(array $lonLatPoints): string
{
    $encoded = '';
    $prevLat = 0;
    $prevLng = 0;
    foreach ($lonLatPoints as $pt) {
        if (!is_array($pt) || count($pt) < 2) {
            continue;
        }
        $lat = round(floatval($pt[1]) * 1e5) / 1e5;
        $lng = round(floatval($pt[0]) * 1e5) / 1e5;
        $latE5 = (int)round($lat * 1e5);
        $lngE5 = (int)round($lng * 1e5);
        $dLat = $latE5 - $prevLat;
        $dLng = $lngE5 - $prevLng;
        $prevLat = $latE5;
        $prevLng = $lngE5;
        $encoded .= google_polyline_encode_chunk($dLat);
        $encoded .= google_polyline_encode_chunk($dLng);
    }
    return $encoded;
}

function google_polyline_encode_chunk(int $v): string
{
    $v = $v < 0 ? ~($v << 1) : ($v << 1);
    $o = '';
    while ($v >= 0x20) {
        $o .= chr((int)(0x20 | ($v & 0x1f)) + 63);
        $v >>= 5;
    }
    $o .= chr((int)$v + 63);
    return $o;
}

function bearing_deg_lon_lat(float $lon1, float $lat1, float $lon2, float $lat2): float
{
    $dLon = deg2rad($lon2 - $lon1);
    $y = sin($dLon) * cos(deg2rad($lat2));
    $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dLon);
    $brng = rad2deg(atan2($y, $x));
    return fmod($brng + 360.0, 360.0);
}

function polygon_area_hectares_planar_lon_lat(array $poly): float
{
    $n = count($poly);
    if ($n < 3) {
        return 0.0;
    }
    $sum = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $j = ($i + 1) % $n;
        $xi = floatval($poly[$i][0] ?? 0.0);
        $yi = floatval($poly[$i][1] ?? 0.0);
        $xj = floatval($poly[$j][0] ?? 0.0);
        $yj = floatval($poly[$j][1] ?? 0.0);
        $sum += ($xi * $yj) - ($xj * $yi);
    }
    $deg2 = abs($sum) * 0.5;
    $lat0 = floatval($poly[0][1] ?? 47.0);
    $mPerDegLat = 111320.0;
    $mPerDegLon = 111320.0 * max(0.2, cos(deg2rad($lat0)));
    $m2 = $deg2 * $mPerDegLat * $mPerDegLon;
    return $m2 / 10000.0;
}

function bbox_lon_lat_from_points(array $lonLatPairs): array
{
    $minLon = INF;
    $minLat = INF;
    $maxLon = -INF;
    $maxLat = -INF;
    foreach ($lonLatPairs as $pt) {
        if (!is_array($pt) || count($pt) < 2) {
            continue;
        }
        $lon = floatval($pt[0]);
        $lat = floatval($pt[1]);
        if (!is_finite($lon) || !is_finite($lat)) {
            continue;
        }
        $minLon = min($minLon, $lon);
        $maxLon = max($maxLon, $lon);
        $minLat = min($minLat, $lat);
        $maxLat = max($maxLat, $lat);
    }
    if (!is_finite($minLon)) {
        return [0.0, 0.0, 0.0, 0.0];
    }
    return [$minLon, $minLat, $maxLon, $maxLat];
}

function german_end_action_to_dji_pilot2(string $de): string
{
    if (function_exists('dji_spec_to_wpml')) {
        $wpml = dji_spec_to_wpml('wpml:finishAction', $de, '');
        if ($wpml !== '') {
            return $wpml;
        }
    }
    $s = mb_strtolower(trim($de));
    if (str_contains($s, 'land')) {
        return 'autoLand';
    }
    if (str_contains($s, 'ersten wegpunkt') || str_contains($s, 'first waypoint') || str_contains($s, 'gotofirst')) {
        return 'gotoFirstWaypoint';
    }
    if (str_contains($s, 'rth') || str_contains($s, 'home') || str_contains($s, 'rueckkehr') || str_contains($s, 'rückkehr') || str_contains($s, 'startpunkt')) {
        return 'goHome';
    }
    return 'noAction';
}

/** Sichere Zahlenbereiche fuer CH-/DJI-Export (Missionen-Referenz / Pilot). */
function clamp_export_float(float $v, float $min, float $max, float $fallback): float
{
    if (!is_finite($v)) {
        return $fallback;
    }
    return max($min, min($max, $v));
}

function clamp_export_speed_m_s(float $v): float
{
    return clamp_export_float($v, 0.5, 25.0, 5.0);
}

function clamp_export_agl_m(float $v): float
{
    return clamp_export_float($v, 1.0, 500.0, 60.0);
}

function clamp_export_turn_m(float $v): float
{
    return clamp_export_float($v, 0.0, 50.0, 5.0);
}

function clamp_wpml_rel_height_m(float $h): float
{
    return max(0.0, min(800.0, $h));
}

/** Zusaetzliche djiPilot2-Schluessel wie in `Missionen/28_Mettmenstetten/*.json` (null, nicht weglassen). */
function dji_pilot2_null_overlay_keys(): array
{
    return [
        'dronePayload1' => null,
        'dronePayload2' => null,
        'dronePayload3' => null,
        'dronePayloadSettings1MeasurementMethod' => null,
        'dronePayloadSettings1DistortionCompensation' => null,
        'dronePayloadSettings1ReturnMode' => null,
        'dronePayloadSettings1Samplingrate' => null,
        'dronePayloadSettings1ScanningMode' => null,
        'dronePayloadSettings1RgbColor' => null,
        'dronePayloadSettings2MeasurementMethod' => null,
        'dronePayloadSettings2DistortionCompensation' => null,
        'dronePayloadSettings2ReturnMode' => null,
        'dronePayloadSettings2Samplingrate' => null,
        'dronePayloadSettings2ScanningMode' => null,
        'dronePayloadSettings2RgbColor' => null,
        'dronePayloadSettings3MeasurementMethod' => null,
        'dronePayloadSettings3DistortionCompensation' => null,
        'dronePayloadSettings3ReturnMode' => null,
        'dronePayloadSettings3Samplingrate' => null,
        'dronePayloadSettings3ScanningMode' => null,
        'dronePayloadSettings3RgbColor' => null,
        'savePictureZoom' => null,
        'savePictureWide' => null,
        'savePictureIR' => null,
    ];
}

function mission_settings_ch_sample_branches(array $mission): array
{
    $slug = resolve_uav_drone_type_slug((string)($mission['drone_type'] ?? ''));
    $easy = !empty($mission['easy_mode_form']);
    $spd = clamp_export_speed_m_s(floatval($mission['speed_m_s'] ?? 5.0));
    $hgt = clamp_export_agl_m(floatval($mission['altitude_m'] ?? 60.0));
    $takeoff = clamp_export_float(floatval($mission['safe_start_altitude_m'] ?? 20.0), 1.0, 150.0, 20.0);
    $turnD = clamp_export_turn_m(floatval($mission['standard_turn_distance_m'] ?? 5.0));
    $curveWp = (int)max(0, min(40, (int)round($turnD)));
    $defCurve = (int)max(5, min(40, max($curveWp, 5)));
    $transSource = $spd;
    if (!empty($mission['start_speed_active'])) {
        $transSource = floatval($mission['start_speed_m_s'] ?? $spd);
    }
    $trans = clamp_export_float($transSource, 0.1, 15.0, 5.0);
    $wpBundle = german_waypoint_type_to_wpml_bundle((string)($mission['waypoint_type'] ?? ''));
    $djiPilot2Wp = $wpBundle['ch_waypoint_type'];
    $flyDirect = !empty($mission['direct_to_start_position']);
    $travel = (int)max(5, min(30, (int)round($spd * 3.6)));
    $djiCurve = (int)max(10, min(80, (int)round($turnD * 7.0)));

    $djiPilot2Core = [
        'droneType' => $slug,
        'directionMode' => 'followWayline',
        'endAction' => german_end_action_to_dji_pilot2((string)($mission['end_action'] ?? '')),
        'pathModus' => 'straight',
        'speed' => $spd,
        'defaultCurveSize' => $defCurve,
        'height' => $hgt,
        'takeOffSecurityHeight' => $takeoff,
        'curveSize' => $curveWp,
        'gimbal' => 'none',
        'powerSavingMode' => false,
        'gimbalControl' => mission_gimbal_uses_point_setting($mission) ? 'setEachWaypoint' : 'manuel',
        'flightHeightMode' => wpml_height_mode_from_mission_altitude_mode((string)($mission['altitude_mode'] ?? '')),
        'flyToWaylineMode' => $flyDirect ? true : null,
        'waypointType' => $djiPilot2Wp,
        'easyModeForm' => $easy,
        'easyModeSpeed' => 5,
        'transitionalSpeed' => round($trans, 1),
        'transitionalSpeedActive' => !empty($mission['start_speed_active']),
    ];

    return [
        'dji' => [
            'directionMode' => 'next-point',
            'endAction' => 'none',
            'pathModus' => 'curve',
            'travelSpeed' => $travel,
            'maxFlySpeed' => 30,
            'photoCaptureInterval' => 0,
            'poiIndex' => null,
            'repeat' => 0,
            'version' => 0,
            'unused' => 0,
            'curveSize' => $djiCurve,
            'gimbal' => 'none',
            'photoCaptureType' => 'm',
            'direction' => null,
        ],
        'djiPilot' => [
            'droneType' => 'other',
            'gimbal1' => null,
            'gimbal2' => null,
            'gimbal3' => null,
            'advancedSettings' => false,
            'directionMode' => 'next-point',
            'endAction' => 'none',
            'pathModus' => 'straight',
            'speed' => 1,
            'height' => 0,
            'curveSize' => 0,
            'gimbal' => 'none',
            'powerSavingMode' => false,
        ],
        'djiPilot2' => array_merge(dji_pilot2_null_overlay_keys(), $djiPilot2Core),
        'px' => [
            'hoverSpeed' => 1,
            'cruiseSpeed' => 1,
            'pxReleaseUpdate' => null,
            'autoReleaseDistance' => null,
            'terrainHeightPX' => null,
            'rcDirection' => null,
        ],
        'plan' => [
            'hoverSpeed' => 1,
            'cruiseSpeed' => 1,
            'rcDirection' => null,
        ],
        'mk' => [
            'comingHomeAltitude' => 0,
            'failSafeAltitude' => 0,
            'triggerDelay' => false,
            'rcLost' => false,
            'triggerMode' => 0,
        ],
    ];
}

/** Offener Ring lon/lat. Ein wiederholter Schlusspunkt wird entfernt. */
function export_open_lonlat_ring(array $poly): array
{
    $ring = [];
    foreach ($poly as $q) {
        if (!is_array($q) || count($q) < 2) {
            continue;
        }
        $ring[] = [floatval($q[0] ?? 0.0), floatval($q[1] ?? 0.0)];
    }
    if (count($ring) >= 2) {
        $a0 = $ring[0];
        $a1 = $ring[count($ring) - 1];
        if (abs($a0[0] - $a1[0]) <= 1e-9 && abs($a0[1] - $a1[1]) <= 1e-9) {
            array_pop($ring);
        }
    }
    return $ring;
}

/**
 * Flughöhe relativ zum Startpunkt: Geländehöhe des Punktes gegenüber dem Start plus die gesetzte Flughöhe.
 * Ohne Geländewert bleibt die gesetzte Flughöhe.
 *
 * @param array<string, mixed> $deltaMap
 */
function export_height_rel_start(float $flightAgl, float $lon, float $lat, array $deltaMap, bool $addTerrain): float
{
    $height = $flightAgl;
    if ($addTerrain) {
        $delta = kmz_terrain_delta_from_cache($deltaMap, $lon, $lat);
        if ($delta !== null) {
            $height += $delta;
        }
    }
    return round($height, 2);
}

/**
 * GeoJSON wie `Missionen/28_Mettmenstetten/*.json`: LineString ist die Feldumrandung, danach Start (type 6) und Wegpunkte (type 1).
 * Die Höhe bleibt die gesetzte Flughöhe. Die Geländedifferenz zum Start wird nur einmal in der KMZ addiert.
 */
function build_ch_sample_geojson_features(array $poly, array $path, array $startPt, array $mission): array
{
    $altM = clamp_export_agl_m(floatval($mission['altitude_m'] ?? 60.0));
    if (count($startPt) >= 2) {
        $path = ensure_start_point_first($path, $startPt, $altM);
    }
    $missionSpeed = clamp_export_speed_m_s(floatval($mission['speed_m_s'] ?? 5.0));
    $turnD = clamp_export_turn_m(floatval($mission['standard_turn_distance_m'] ?? 5.0));
    $curveWp = (int)max(0, min(40, (int)round($turnD)));
    $turnWp = $turnD > 0.05 ? (float)$curveWp : null;

    $lsCoords = export_open_lonlat_ring($poly);
    $features = [];
    $features[] = [
        'type' => 'Feature',
        'properties' => [
            'prefix' => 'P',
            'cameraAngle' => 0,
            'gimbalAngle' => -90,
            'gimbalYaw' => 0,
            'gimbalNod' => 0,
            'cameraModeTime' => 1,
            'directionType' => 'followWayline',
            'wpEvent' => 0,
            'autoTrigger' => 0,
            'climbRate' => 0.1,
            'tolerance' => 10,
            'rotation' => 'clockwise',
            'useMissionDirectionMode' => true,
            'direction' => 0,
            'curveSize' => null,
            'useMissionPathMode' => true,
            'useMissionYawDirection' => false,
            'useMissionWaypointType' => false,
            'speed' => $missionSpeed,
            'useMissionSpeed' => true,
            'height' => 0.0,
            'useMissionHeight' => true,
            'type' => 1,
            'pathMode' => 'straight',
            'autoHeight' => true,
            'yawDirection' => 'followWayline',
            'waypointType' => 'coordinateTurn',
            'turningDistance' => null,
            'alignmentAircraft' => 'followBadArc',
            'aboveSea' => [
                'value' => null,
                'lat' => null,
                'lng' => null,
            ],
            '_height' => 0.0,
        ],
        'geometry' => [
            'type' => 'LineString',
            'coordinates' => $lsCoords,
        ],
    ];
    $sLon = floatval($startPt[0] ?? $path[0][0] ?? 0.0);
    $sLat = floatval($startPt[1] ?? $path[0][1] ?? 0.0);
    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [$sLon, $sLat],
        ],
        'properties' => [
            'type' => 6,
        ],
    ];
    foreach (export_mission_pois($mission) as $missionPoi) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [floatval($missionPoi['longitude']), floatval($missionPoi['latitude'])],
            ],
            'properties' => [
                'type' => 2,
                'height' => floatval($missionPoi['height'] ?? 0),
                'aboveSea' => [
                    'value' => null,
                    'lat' => round(floatval($missionPoi['latitude']), 6),
                    'lng' => round(floatval($missionPoi['longitude']), 6),
                ],
            ],
        ];
    }
    $n = count($path);
    for ($i = 0; $i < $n; $i++) {
        $idx1 = $i + 1;
        $p = $path[$i];
        $lon = floatval($p[0] ?? 0.0);
        $lat = floatval($p[1] ?? 0.0);
        $agl = floatval($p[2] ?? 0.0);
        $flags = effective_ch_flags_for_waypoint($mission, $idx1);
        $ov = waypoint_override_row($mission, $idx1);
        $hStr = !empty($flags['useMissionYawDirection'])
            ? (string)($mission['heading_mode'] ?? '')
            : (string)($ov['heading_mode'] ?? $mission['heading_mode'] ?? '');
        $wtStr = !empty($flags['useMissionWaypointType'])
            ? (string)($mission['waypoint_type'] ?? '')
            : (string)($ov['waypoint_type'] ?? $mission['waypoint_type'] ?? '');
        $bundle = german_waypoint_type_to_wpml_bundle($wtStr);
        $pathMode = (string)($bundle['path_mode'] ?? 'straight');
        if (empty($flags['useMissionPathMode']) && isset($ov['path_mode']) && is_string($ov['path_mode'])) {
            $pathBundle = ch_path_bundle_from_path_mode_slug((string)$ov['path_mode']);
            if (isset($pathBundle['path_mode']) && is_string($pathBundle['path_mode']) && $pathBundle['path_mode'] !== '') {
                $pathMode = (string)$pathBundle['path_mode'];
            }
        }
        $poiCandidate = clean_export_poi($ov['poi'] ?? null);
        if ($poiCandidate === null) {
            $missionPois = export_mission_pois($mission);
            if (count($missionPois) === 1) {
                $poiCandidate = $missionPois[0];
            }
        }
        $headingAsked = mb_strtolower($hStr);
        $askedPoi = str_contains($headingAsked, 'poi') || str_contains($headingAsked, 'fokus');
        $hd = german_heading_mode_to_ch_direction_strings($hStr, $poiCandidate !== null);
        $speedVal = !empty($flags['useMissionSpeed'])
            ? $missionSpeed
            : clamp_export_speed_m_s(floatval($ov['speed_m_s'] ?? $missionSpeed));
        $pointHeight = !empty($flags['useMissionHeight'])
            ? $altM
            : clamp_export_agl_m(floatval($ov['agl_m'] ?? $agl));
        $autoHeight = kmz_is_alt_relative_to_start($mission);
        $explicitDir = null;
        if (array_key_exists('heading_angle', $ov) && $ov['heading_angle'] !== null && $ov['heading_angle'] !== '' && is_numeric($ov['heading_angle'])) {
            $explicitDir = floatval($ov['heading_angle']);
        } elseif (array_key_exists('direction_deg', $ov) && $ov['direction_deg'] !== null && $ov['direction_deg'] !== '' && is_numeric($ov['direction_deg'])) {
            $explicitDir = floatval($ov['direction_deg']);
        }
        $yawKind = strtolower((string)($hd['yawDirection'] ?? ''));
        $dirType = strtolower((string)($hd['directionType'] ?? ''));
        $needsAngle = ($yawKind === 'customised' || $yawKind === 'customized');
        $lockedAxis = ($yawKind === 'fixed');
        $poiMode = ($dirType === 'towardpoi' || $yawKind === 'smoothtransition' || $yawKind === 'smooth');
        if ($askedPoi && $poiCandidate === null) {
            $dir = null;
        } elseif ($needsAngle) {
            $dir = ($explicitDir !== null && is_finite($explicitDir)) ? max(-180.0, min(180.0, $explicitDir)) : null;
        } elseif ($lockedAxis || $poiMode) {
            $dir = null;
        } else {
            if ($i + 1 < $n) {
                $dir = bearing_deg_lon_lat($lon, $lat, floatval($path[$i + 1][0]), floatval($path[$i + 1][1]));
            } elseif ($i > 0) {
                $dir = bearing_deg_lon_lat(floatval($path[$i - 1][0]), floatval($path[$i - 1][1]), $lon, $lat);
            } else {
                $dir = 0.0;
            }
            if (empty($flags['useMissionDirectionMode']) && $explicitDir !== null && is_finite($explicitDir)) {
                $dir = fmod(($explicitDir + 720.0), 360.0);
            }
        }
        $curveWpLocal = $curveWp;
        $turnWpLocal = $turnWp;
        if (array_key_exists('use_mission_turn_distance', $ov) && !$ov['use_mission_turn_distance'] && isset($ov['turning_distance_m']) && is_numeric($ov['turning_distance_m'])) {
            $tdLocal = clamp_export_turn_m(floatval($ov['turning_distance_m']));
            $curveWpLocal = (int)max(0, min(40, (int)round($tdLocal)));
            $turnWpLocal = $tdLocal > 0.05 ? (float)$curveWpLocal : null;
        }
        $gimbalWritten = export_gimbal_pitch_deg($mission, $ov);
        $gimbalAngle = $gimbalWritten !== null ? $gimbalWritten : -90.0;
        $poiForJson = (($hd['directionType'] ?? '') === 'towardPOI') ? $poiCandidate : null;
        $alignWpml = 'followBadArc';
        if (!empty($ov['alignment_aircraft'])) {
            $alignWpml = wpml_heading_path_mode_from_internal((string)$ov['alignment_aircraft']);
        }
        $aboveVal = (int)max(0.0, round(400.0 + $pointHeight * 1.2 + ($lat - 47.0) * 50.0));
        $latR = round($lat, 6);
        $lonR = round($lon, 6);
        $props = [
            'prefix' => 'P',
            'cameraAngle' => 0,
            'gimbalAngle' => $gimbalAngle,
            'gimbalYaw' => 0,
            'gimbalNod' => 0,
            'cameraModeTime' => 1,
            'directionType' => $hd['directionType'],
            'wpEvent' => 0,
            'autoTrigger' => 0,
            'climbRate' => 0.1,
            'tolerance' => 10,
            'rotation' => 'clockwise',
            'useMissionDirectionMode' => !empty($flags['useMissionDirectionMode']),
            'direction' => $dir === null ? null : round((float)$dir, 1),
            'curveSize' => $curveWpLocal,
            'useMissionPathMode' => !empty($flags['useMissionPathMode']),
            'useMissionYawDirection' => !empty($flags['useMissionYawDirection']),
            'useMissionWaypointType' => !empty($flags['useMissionWaypointType']),
            'speed' => $speedVal,
            'useMissionSpeed' => !empty($flags['useMissionSpeed']),
            'height' => $pointHeight,
            'useMissionHeight' => !empty($flags['useMissionHeight']),
            'type' => 1,
            'pathMode' => $pathMode,
            'autoHeight' => $autoHeight,
            'yawDirection' => $hd['yawDirection'],
            'waypointType' => $bundle['ch_waypoint_type'],
            'turningDistance' => $turnWpLocal,
            'alignmentAircraft' => $alignWpml,
            'aboveSea' => [
                'value' => $aboveVal,
                'lat' => $latR,
                'lng' => $lonR,
            ],
            '_height' => $pointHeight,
        ];
        if ($poiForJson !== null) {
            $props['poi'] = $poiForJson;
        }
        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$lon, $lat],
            ],
            'properties' => $props,
        ];
    }
    return $features;
}

function connected_planning_stub_array(): array
{
    return [
        'name' => '',
        'pilotId' => '',
        'pilotObject' => [
            '_id' => '',
            'login' => '',
            'email' => [
                'address' => '',
                'verified' => false,
            ],
            'profile' => [
                'name' => '',
                'firstname' => '',
                'phone' => '',
                'address' => [
                    'zip' => '',
                    'formatted' => '',
                    'city' => '',
                    'coordinates' => [0.0, 0.0],
                ],
                'copterProfile' => 'dji-pilot-2',
            ],
            'id' => '',
        ],
    ];
}

function contributor_stub_array(): array
{
    return [
        'name' => '',
        'firstname' => '',
        'address' => [
            'zip' => '',
            'formatted' => '',
            'city' => '',
            'coordinates' => [0.0, 0.0],
        ],
        'email' => '',
        'phone' => '',
    ];
}

function rkrk_export_region_table(): array
{
    if (isset($GLOBALS['RegionSettings']) && is_array($GLOBALS['RegionSettings']) && $GLOBALS['RegionSettings'] !== []) {
        return $GLOBALS['RegionSettings'];
    }
    if (function_exists('editor_load_name_tables')) {
        editor_load_name_tables();
        if (isset($GLOBALS['RegionSettings']) && is_array($GLOBALS['RegionSettings']) && $GLOBALS['RegionSettings'] !== []) {
            return $GLOBALS['RegionSettings'];
        }
    }
    static $tried = false;
    static $table = [];
    if ($tried) {
        return $table;
    }
    $tried = true;
    $scripts = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts';
    $file = $scripts . DIRECTORY_SEPARATOR . 'rkrk_geofence.php';
    if (!is_file($file)) {
        return [];
    }
    $cwd = getcwd();
    chdir($scripts);
    ob_start();
    include_once $file;
    ob_end_clean();
    if (is_string($cwd) && $cwd !== '') {
        @chdir($cwd);
    }
    if (isset($RegionSettings) && is_array($RegionSettings)) {
        $GLOBALS['RegionSettings'] = $RegionSettings;
        $table = $RegionSettings;
    }
    return $table;
}

function rkrk_export_group_area(string $jg, string $feldId): array
{
    $code = trim($jg);
    if ($code === '' && preg_match('/^([A-Za-z0-9]+)\s*-/', trim($feldId), $m)) {
        $code = $m[1];
    }
    $regions = rkrk_export_region_table();
    $row = ($code !== '' && isset($regions[$code]) && is_array($regions[$code])) ? $regions[$code] : null;
    $name = $row ? trim((string)($row['GroupAreaName'] ?? '')) : '';
    $zone = $row ? trim((string)($row['GroupAreaID'] ?? '')) : '';
    return ['jg' => $code, 'name' => $name, 'zoneId' => $zone];
}

/**
 * UAV-Missions-JSON im gleichen Top-Level- und geojson-Schema wie `samples/*.json` (CH / DJI Pilot 2).
 * Hinweis: Verbindungs-/Nutzerfelder sind neutrale Stubs; Geometrie und missionSettings stammen aus dem Feld-Editor.
 */
function build_uav_json_payload(array $meta, array $path, array $poly = [], array $startPt = []): array
{
    $mission = is_array($meta['mission_settings'] ?? null) ? $meta['mission_settings'] : [];
    $cruise = clamp_export_agl_m(floatval($mission['altitude_m'] ?? ($meta['std_agl_m'] ?? 60.0)));
    if (count($startPt) >= 2) {
        $path = ensure_start_point_first($path, $startPt, $cruise);
    }
    $feldId = trim((string)($meta['feld_id'] ?? 'mission'));
    $nameParts = is_array($meta['mission_name_parts'] ?? null) ? $meta['mission_name_parts'] : null;
    $fname = trim((string)($nameParts['feldname'] ?? ($meta['name'] ?? $feldId)));
    $completeName = trim((string)($nameParts['complete'] ?? $fname));
    $id = random_ch_mission_id();
    $now = gmdate('Y-m-d\TH:i:s.v\Z');
    $pathLonLat = [];
    foreach ($path as $p) {
        $pathLonLat[] = [floatval($p[0] ?? 0.0), floatval($p[1] ?? 0.0)];
    }
    $lineString = google_polyline_encode_precision5($pathLonLat);
    $polyRing = export_open_lonlat_ring($poly);
    $polygonLineString = google_polyline_encode_precision5($polyRing);
    $allPts = $pathLonLat;
    if (count($polyRing) > 0) {
        $allPts = array_merge($allPts, $polyRing);
    }
    $bbox = bbox_lon_lat_from_points($allPts);
    $center = [
        ($bbox[0] + $bbox[2]) * 0.5,
        ($bbox[1] + $bbox[3]) * 0.5,
    ];
    $area = count($poly) >= 3 ? polygon_area_hectares_planar_lon_lat($poly) : 0.0;
    $slug = resolve_uav_drone_type_slug((string)($mission['drone_type'] ?? ''));
    $missionSettings = mission_settings_ch_sample_branches($mission);
    $geojson = [
        'type' => 'FeatureCollection',
        'features' => build_ch_sample_geojson_features(
            $poly,
            $path,
            $startPt,
            $mission
        ),
    ];
    $lastAuto = [
        'properties' => [
            'prefix' => 'P',
            'cameraAngle' => 0,
            'gimbalAngle' => -90,
            'gimbalYaw' => 0,
            'gimbalNod' => 0,
            'cameraModeTime' => 1,
            'directionType' => 'none',
            'wpEvent' => 0,
            'autoTrigger' => 0,
            'climbRate' => 0.1,
            'tolerance' => 10,
            'rotation' => 'clockwise',
            'useMissionDirectionMode' => all_waypoints_use_mission_flag($mission, 'use_mission_direction_mode'),
            'direction' => 0,
            'curveSize' => 0,
            'useMissionPathMode' => all_waypoints_use_mission_flag($mission, 'use_mission_path_mode'),
            'useMissionYawDirection' => all_waypoints_use_mission_flag($mission, 'use_mission_yaw'),
            'useMissionWaypointType' => all_waypoints_use_mission_flag($mission, 'use_mission_waypoint_type'),
            'speed' => clamp_export_speed_m_s(floatval($mission['speed_m_s'] ?? 5.0)),
            'useMissionSpeed' => all_waypoints_use_mission_flag($mission, 'use_mission_speed'),
            'height' => clamp_export_agl_m(floatval($mission['altitude_m'] ?? 60.0)),
            'useMissionHeight' => all_waypoints_use_mission_flag($mission, 'use_mission_height'),
            'autoHeight' => true,
            'yawDirection' => 'followWayline',
            'waypointType' => german_waypoint_type_to_wpml_bundle((string)($mission['waypoint_type'] ?? ''))['ch_waypoint_type'],
        ],
        'settings' => [
            'az' => -1,
            'easyModeHeight' => 0,
            'easyModeHeightSelect' => (int)round(clamp_export_agl_m(floatval($mission['altitude_m'] ?? 60.0))),
            'spacing' => 40,
            'border' => 15,
            'easyModeForm' => !empty($mission['easy_mode_form']),
            'easyModeSpeed' => 5,
        ],
    ];
    $aboveSeaLevel = [
        'lat' => floatval($startPt[1] ?? $path[0][1] ?? 0.0),
        'lng' => floatval($startPt[0] ?? $path[0][0] ?? 0.0),
        'value' => (int)max(0.0, round(400.0 + clamp_export_agl_m(floatval($mission['altitude_m'] ?? 60.0)))),
    ];
    $payload = [
        'connectedPlanningData' => connected_planning_stub_array(),
        '_id' => $id,
        'name' => $fname,
        'owner' => '',
        'missionSettings' => $missionSettings,
        'contributedField' => true,
        'contributionInfo' => [
            'status' => 0,
            'doneTicks' => 0,
        ],
        'contributor' => contributor_stub_array(),
        'contributionNumber' => 0,
        'contributionReference' => '',
        'createdAt' => $now,
        'createdBy' => [
            'type' => 'user',
            'id' => '',
        ],
        'bbox' => $bbox,
        'center' => $center,
        'lineString' => $lineString,
        'polygonLineString' => $polygonLineString,
        'area' => $area,
        'featureCount' => [
            'waypoints' => count($path),
            'failsafes' => null,
            'pois' => null,
            'landingpoints' => null,
        ],
        'staticImageUpToDate' => false,
        'copterProfile' => 'dji-pilot-2',
        'opened' => true,
        'sortAndQueryHelper' => [
            'droneType' => $slug,
        ],
        'edited' => true,
        'geojson' => $geojson,
        'lastModified' => $now,
        'lastModifiedBy' => [
            'type' => 'user',
            'id' => '',
        ],
        'controlFlights' => [],
        'data' => [
            'owner' => new stdClass(),
        ],
        'exportSettings' => new stdClass(),
        'id' => $id,
        'orderData' => new stdClass(),
        'lastAutoGenerateSettings' => $lastAuto,
        'aboveSeaLevel' => $aboveSeaLevel,
        'planningInfos' => [],
        'hasValidConnection' => false,
        'originalName' => $fname,
        'fileNameComplete' => $completeName,
        'fileNameCompleteForExport' => $completeName,
        'fileName' => $fname,
    ];
    $area = rkrk_export_group_area((string)($meta['jg'] ?? ''), $feldId);
    $payload['sharedFieldCatalog'] = [
        'catalogIdentifier' => $feldId,
    ];
    if ($area['name'] !== '') {
        $payload['pilotGroupAreaName'] = $area['name'];
    }
    if (!isset($payload['data']) || !is_array($payload['data'])) {
        $payload['data'] = ['owner' => new stdClass()];
    }
    if ($area['zoneId'] !== '') {
        $payload['data']['zoneId'] = $area['zoneId'];
    }
    $poiN = 0;
    foreach ($geojson['features'] as $feature) {
        if (is_array($feature) && (int)($feature['properties']['type'] ?? 0) === 2) {
            $poiN++;
        }
    }
    if ($poiN > 0 && isset($payload['featureCount']) && is_array($payload['featureCount'])) {
        $payload['featureCount']['pois'] = $poiN;
    }
    return $payload;
}

/** WPML-KML an die Referenz samples/.../wpmz/template.kml anpassen (fehlende Knoten ergaenzen). */
function wpmz_kml_normalize_like_ch_sample(string $kml): string
{
    if (strpos($kml, 'xmlns:wpml') === false && preg_match('/<kml\s+xmlns="([^"]+)"/', $kml, $m)) {
        $kml = preg_replace(
            '/<kml\s+xmlns="' . preg_quote($m[1], '/') . '"/',
            '<kml xmlns="' . $m[1] . '" xmlns:wpml="http://www.dji.com/wpmz/1.0.2"',
            $kml,
            1
        );
    }
    if (strpos($kml, '<wpml:exitOnRCLost>') === false && strpos($kml, '<wpml:finishAction>') !== false) {
        $kml = preg_replace(
            '/(<wpml:finishAction>[^<]*<\/wpml:finishAction>)/',
            '$1' . "\n      <wpml:exitOnRCLost>executeLostAction</wpml:exitOnRCLost>\n      <wpml:executeRCLostAction>goBack</wpml:executeRCLostAction>\n      <wpml:flyToWaylineMode>safely</wpml:flyToWaylineMode>",
            $kml,
            1
        );
    }
    if (strpos($kml, '<wpml:caliFlightEnable>') === false && strpos($kml, '<wpml:globalHeight>') !== false) {
        $kml = preg_replace(
            '/(<wpml:globalHeight>[^<]*<\/wpml:globalHeight>)/',
            '$1' . "\n      <wpml:caliFlightEnable>0</wpml:caliFlightEnable>",
            $kml,
            1
        );
    }
    if (strpos($kml, '<wpml:useGlobalTurnParam>') === false) {
        $kml = preg_replace(
            '/(<wpml:useGlobalSpeed>\s*\d+(?:\.\d+)?\s*<\/wpml:useGlobalSpeed>)\s*[\r\n]*\s*(<wpml:useStraightLine>)/',
            '$1' . "\n        <wpml:useGlobalTurnParam>0</wpml:useGlobalTurnParam>\n        $2",
            $kml
        );
    }
    return $kml;
}

function wpml_use_flag_from_text(string $raw): ?bool
{
    $t = strtolower(trim($raw));
    if ($t === '1' || $t === 'true') {
        return true;
    }
    if ($t === '0' || $t === 'false') {
        return false;
    }
    return null;
}

/**
 * Liest die vier useGlobal-Tags. Pfadmodus und Richtungsmodus haben kein Tag und bleiben unangetastet.
 *
 * @return array<string, array<string, bool>>
 */
function waypoint_overrides_from_wpml_kml(string $kml): array
{
    $map = [
        'useGlobalHeight' => 'use_mission_height',
        'useGlobalSpeed' => 'use_mission_speed',
        'useGlobalTurnParam' => 'use_mission_waypoint_type',
        'useGlobalHeadingParam' => 'use_mission_yaw',
    ];
    $out = [];
    if (!preg_match_all('/<Placemark\b[^>]*>(.*?)<\/Placemark>/si', $kml, $blocks)) {
        return $out;
    }
    $n = 0;
    foreach ($blocks[1] as $block) {
        if (!preg_match('/<coordinates>\s*([-0-9.]+)\s*,\s*([-0-9.]+)/i', $block, $coord)) {
            continue;
        }
        $row = [];
        foreach ($map as $tag => $key) {
            if (preg_match('/<wpml:' . $tag . '>\s*([^<]*)<\/wpml:' . $tag . '>/i', $block, $m)) {
                $flag = wpml_use_flag_from_text($m[1]);
                if ($flag !== null) {
                    $row[$key] = $flag;
                }
            }
        }
        if ($row === []) {
            continue;
        }
        $n++;
        $idx = $n;
        if (preg_match('/<wpml:index>\s*(\d+)\s*<\/wpml:index>/i', $block, $im)) {
            $idx = intval($im[1]) + 1;
        }
        $out[(string)$idx] = $row;
    }
    return $out;
}

function build_kml_for_path(array $meta, array $path): string
{
    $name = htmlspecialchars((string)($meta['mission_name_parts']['complete'] ?? $meta['name'] ?? $meta['feld_id'] ?? 'mission'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $coords = [];
    foreach ($path as $p) {
        $coords[] = floatval($p[0] ?? 0.0) . ',' . floatval($p[1] ?? 0.0) . ',' . floatval($p[2] ?? 0.0);
    }
    $coordStr = implode("\n", $coords);
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>{$name}</name>
    <Placemark>
      <name>{$name}</name>
      <LineString>
        <altitudeMode>relativeToGround</altitudeMode>
        <coordinates>
{$coordStr}
        </coordinates>
      </LineString>
    </Placemark>
  </Document>
</kml>
XML;
}

function try_build_kml_with_legacy_scripts(array $meta, array $path): string
{
    $kmlScript = mapll_scripts_file('rkrk_kml.php');
    if ($kmlScript === null || !is_file($kmlScript)) {
        return build_kml_for_path($meta, $path);
    }

    $cwd = getcwd();
    $scriptsParent = dirname((string)mapll_scripts_dir());
    $out = '';
    try {
        if ($scriptsParent !== '') {
            chdir($scriptsParent);
        }
        ob_start();
        require_once $kmlScript;
        ob_end_clean();
        if (function_exists('kmlheader') && function_exists('kmlfooter')) {
            $out .= kmlheader((string)($meta['feld_id'] ?? 'mission'));
            $out .= '  <Placemark>' . "\n";
            $out .= '    <name>Flight Path</name>' . "\n";
            $out .= '    <LineString>' . "\n";
            $out .= '      <altitudeMode>relativeToGround</altitudeMode>' . "\n";
            $out .= '      <coordinates>' . "\n";
            foreach ($path as $p) {
                $out .= floatval($p[0] ?? 0.0) . ',' . floatval($p[1] ?? 0.0) . ',' . floatval($p[2] ?? 0.0) . "\n";
            }
            $out .= '      </coordinates>' . "\n";
            $out .= '    </LineString>' . "\n";
            $out .= '  </Placemark>' . "\n";
            $out .= kmlfooter();
            return $out;
        }
    } catch (Throwable $e) {
        // fallback below
    } finally {
        if (is_string($cwd) && $cwd !== '') {
            @chdir($cwd);
        }
    }
    return build_kml_for_path($meta, $path);
}

function try_build_kmz_doc_with_legacy_scripts(array $meta, array $path, array $missionSettings): string
{
    $kmzScript = mapll_scripts_file('rkrk_kmz.php');
    if ($kmzScript === null || !is_file($kmzScript)) {
        return try_build_kml_with_legacy_scripts($meta, $path);
    }
    $cwd = getcwd();
    $scriptsParent = dirname((string)mapll_scripts_dir());
    try {
        if ($scriptsParent !== '') {
            chdir($scriptsParent);
        }
        ob_start();
        require_once $kmzScript;
        ob_end_clean();
        if (function_exists('compileKmzData') && function_exists('arrayToXml')) {
            $wps = [];
            foreach ($path as $i => $p) {
                $aglT = floatval($p[2] ?? 0.0);
                $ext = build_kmz_waypoint_extensions($missionSettings, $i, $aglT);
                $relH = $ext['_rel_height_hint'];
                unset($ext['_rel_height_hint']);
                $wps[] = array_merge([
                    'lng' => floatval($p[0] ?? 0.0),
                    'lat' => floatval($p[1] ?? 0.0),
                    'value' => $relH,
                    'height' => $relH,
                    'index' => $i,
                ], $ext);
            }
            $aglM = floatval($missionSettings['altitude_m'] ?? 0.0);
            if (!is_finite($aglM) || $aglM <= 0.0) {
                $aglM = floatval($meta['std_agl_m'] ?? 50.0);
            }
            $aglM = clamp_export_agl_m($aglM);
            $droneSlug = resolve_uav_drone_type_slug((string)($missionSettings['drone_type'] ?? ''));
            $wpBundle = german_waypoint_type_to_wpml_bundle((string)($missionSettings['waypoint_type'] ?? ''));
            $hGlobal = german_heading_mode_to_wpml_global((string)($missionSettings['heading_mode'] ?? ''));
            $turnDamp = clamp_export_turn_m(floatval($missionSettings['standard_turn_distance_m'] ?? 5.0));
            $missionSpeedKmz = clamp_export_speed_m_s(floatval($missionSettings['speed_m_s'] ?? 5.0));
            $pointData = [
                'route_follow_mission' => mission_route_follow_active($missionSettings),
                'Profile' => [
                    'droneType' => $droneSlug,
                ],
                'speed' => [
                    'height' => $missionSpeedKmz,
                ],
                'missionSettings' => [
                    'height' => $aglM,
                    'takeOffSecurityHeight' => clamp_export_float(floatval($missionSettings['safe_start_altitude_m'] ?? 20.0), 1.0, 150.0, 20.0),
                    'flightHeightMode' => wpml_height_mode_from_mission_altitude_mode((string)($missionSettings['altitude_mode'] ?? '')),
                    'globalWaypointHeadingMode' => $hGlobal,
                    'globalWaypointTurnMode' => $wpBundle['global_wpml_turn'],
                    'globalUseStraightLine' => $wpBundle['use_straight_line'],
                    'placemarkTurnMode' => $wpBundle['global_wpml_turn'],
                    'placemarkUseStraightLine' => $wpBundle['use_straight_line'],
                    'turnDampingM' => $turnDamp,
                    'perWaypointHeadingParamChildren' => wpml_waypoint_heading_param_children_from_german((string)($missionSettings['heading_mode'] ?? '')),
                ],
                'Wegpunkte' => $wps,
            ];
            $kmzArray = compileKmzData($pointData);
            $xml = arrayToXml($kmzArray);
            if (is_string($xml) && trim($xml) !== '') {
                return wpmz_kml_normalize_like_ch_sample($xml);
            }
        }
    } catch (Throwable $e) {
        // fallback below
    } finally {
        if (is_string($cwd) && $cwd !== '') {
            @chdir($cwd);
        }
    }
    return try_build_kml_with_legacy_scripts($meta, $path);
}

function resolve_dji_interface_drone_key(string $droneType): string
{
    ensure_dji_kmz_reference_loaded();
    $needle = mb_strtolower(trim($droneType));
    $settings = (isset($GLOBALS['DroneSettings']) && is_array($GLOBALS['DroneSettings'])) ? $GLOBALS['DroneSettings'] : [];
    $enums = (isset($GLOBALS['DJI_DroneInformation']['wpml:droneEnumValue']) && is_array($GLOBALS['DJI_DroneInformation']['wpml:droneEnumValue']))
        ? $GLOBALS['DJI_DroneInformation']['wpml:droneEnumValue']
        : [];
    foreach ($settings as $key => $cfg) {
        if (!is_array($cfg) || !isset($enums[$key])) {
            continue;
        }
        $name = mb_strtolower(trim((string)($cfg['Name'] ?? '')));
        $slug = mb_strtolower(trim((string)($cfg['UAVDroneType'] ?? '')));
        $k = mb_strtolower(trim((string)$key));
        if ($needle === $name || $needle === $slug || $needle === $k) {
            return (string)$key;
        }
    }
    return isset($enums['3T']) ? '3T' : (string)(array_key_first($enums) ?? '3T');
}

function mission_gimbal_uses_point_setting(array $mission): bool
{
    return wpml_gimbal_pitch_mode_from_mission((string)($mission['gimbal_mode'] ?? '')) === 'usePointSetting';
}

/** Flag aus: null, die KMZ schreibt keinen Winkel. Flag an: Wegpunktwinkel, sonst der Missionswinkel, sonst −90. */
function export_gimbal_pitch_deg(array $mission, array $ov): ?float
{
    if (!mission_gimbal_uses_point_setting($mission)) {
        return null;
    }
    $raw = null;
    if (isset($ov['gimbal_pitch']) && is_numeric($ov['gimbal_pitch'])) {
        $raw = floatval($ov['gimbal_pitch']);
    } elseif (isset($mission['gimbal_pitch']) && is_numeric($mission['gimbal_pitch'])) {
        $raw = floatval($mission['gimbal_pitch']);
    } else {
        $raw = -90.0;
    }
    return max(-90.0, min(30.0, $raw));
}

function wpml_gimbal_pitch_mode_from_mission(string $deMode): string
{
    if (function_exists('dji_spec_to_wpml')) {
        $wpml = dji_spec_to_wpml('wpml:gimbalPitchMode', $deMode, '');
        if ($wpml !== '') {
            return $wpml;
        }
    }
    $m = mb_strtolower(trim($deMode));
    if (str_contains($m, 'wegpunkt') || str_contains($m, 'punkt') || str_contains($m, 'usepoint')) {
        return 'usePointSetting';
    }
    return 'manual';
}

function wpml_speed_limit_for_drone(string $droneKey, float $speed, float $fallback = 5.0): float
{
    $cmds = (isset($GLOBALS['DJI_Commands']) && is_array($GLOBALS['DJI_Commands'])) ? $GLOBALS['DJI_Commands'] : [];
    $hi = 21.0;
    $lo = 0.1;
    if (isset($cmds['wpml:autoFlightSpeed'][$droneKey]['high'])) {
        $hi = floatval($cmds['wpml:autoFlightSpeed'][$droneKey]['high']);
    }
    if (isset($cmds['wpml:autoFlightSpeed'][$droneKey]['low'])) {
        $lo = max(0.1, floatval($cmds['wpml:autoFlightSpeed'][$droneKey]['low']));
    }
    return clamp_export_float($speed, $lo, $hi, $fallback);
}

function wpml_takeoff_security_for_drone(string $droneKey, float $height): float
{
    $cmds = (isset($GLOBALS['DJI_Commands']) && is_array($GLOBALS['DJI_Commands'])) ? $GLOBALS['DJI_Commands'] : [];
    $lo = 1.2;
    $hi = 1500.0;
    if (isset($cmds['wpml:takeOffSecurityHeight'][$droneKey]['low'])) {
        $lo = floatval($cmds['wpml:takeOffSecurityHeight'][$droneKey]['low']);
    }
    if (isset($cmds['wpml:takeOffSecurityHeight'][$droneKey]['high'])) {
        $hi = floatval($cmds['wpml:takeOffSecurityHeight'][$droneKey]['high']);
    }
    return clamp_export_float($height, $lo, $hi, max($lo, 20.0));
}

function kmz_lonlat_cache_key(float $lon, float $lat): string
{
    return sprintf('%.7F,%.7F', $lon, $lat);
}

/**
 * @param array<string, mixed> $map
 */
function kmz_terrain_delta_from_cache(array $map, float $lon, float $lat): ?float
{
    $key = kmz_lonlat_cache_key($lon, $lat);
    if (!array_key_exists($key, $map)) {
        return null;
    }
    $v = floatval($map[$key]);
    return is_finite($v) ? $v : null;
}

/**
 * @param list<string> $urls
 * @return array<string, mixed>
 */
function kmz_http_get_json_multi(array $urls, int $timeoutSec = 6): array
{
    $out = [];
    $urls = array_values(array_unique(array_filter($urls, static function ($u) {
        return is_string($u) && $u !== '';
    })));
    if ($urls === []) {
        return $out;
    }
    if (function_exists('curl_multi_init') && function_exists('curl_init')) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSec,
                CURLOPT_TIMEOUT => $timeoutSec,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }
        $running = 0;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running > 0);
        foreach ($handles as $url => $ch) {
            $raw = curl_multi_getcontent($ch);
            $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $json = (is_string($raw) && $code >= 200 && $code < 300) ? json_decode($raw, true) : null;
            $out[$url] = is_array($json) ? $json : null;
        }
        curl_multi_close($mh);
        return $out;
    }
    foreach ($urls as $url) {
        $ctx = stream_context_create([
            'http' => ['timeout' => $timeoutSec, 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        $out[$url] = is_array($json) ? $json : null;
    }
    return $out;
}

/**
 * @param list<array{0:float,1:float}> $lonLats
 * @return array<string, float>
 */
function kmz_fetch_geoadmin_terrain_asl_m(array $lonLats): array
{
    $unique = [];
    foreach ($lonLats as $p) {
        if (!is_array($p) || count($p) < 2) {
            continue;
        }
        $lon = floatval($p[0]);
        $lat = floatval($p[1]);
        if (!is_finite($lon) || !is_finite($lat)) {
            continue;
        }
        $unique[kmz_lonlat_cache_key($lon, $lat)] = [$lon, $lat];
    }
    if ($unique === []) {
        return [];
    }
    $reframeUrls = [];
    foreach ($unique as $key => $p) {
        $reframeUrls[$key] = 'https://geodesy.geo.admin.ch/reframe/wgs84tolv95?easting='
            . rawurlencode(sprintf('%.7F', $p[0]))
            . '&northing=' . rawurlencode(sprintf('%.7F', $p[1]))
            . '&format=json';
    }
    $reframeJson = kmz_http_get_json_multi(array_values($reframeUrls));
    $heightUrls = [];
    $heightUrlToKey = [];
    foreach ($reframeUrls as $key => $url) {
        $js = $reframeJson[$url] ?? null;
        $e = is_array($js) ? floatval($js['easting'] ?? NAN) : NAN;
        $n = is_array($js) ? floatval($js['northing'] ?? NAN) : NAN;
        if (!is_finite($e) || !is_finite($n)) {
            continue;
        }
        $hUrl = 'https://api3.geo.admin.ch/rest/services/height?easting='
            . rawurlencode(sprintf('%.3F', $e))
            . '&northing=' . rawurlencode(sprintf('%.3F', $n))
            . '&sr=2056';
        $heightUrls[] = $hUrl;
        $heightUrlToKey[$hUrl] = $key;
    }
    $heightJson = kmz_http_get_json_multi($heightUrls);
    $out = [];
    foreach ($heightUrlToKey as $hUrl => $key) {
        $js = $heightJson[$hUrl] ?? null;
        $h = is_array($js) ? floatval($js['height'] ?? NAN) : NAN;
        if (is_finite($h)) {
            $out[$key] = $h;
        }
    }
    return $out;
}

/**
 * @param array<string, mixed> $deltaCache
 * @param list<array{0:float,1:float}> $lonLats
 * @param array{0:float,1:float}|null $startLonLat
 * @return array<string, float>
 */
function kmz_terrain_delta_map_for_points(array $deltaCache, array $lonLats, $startLonLat): array
{
    $map = [];
    foreach ($deltaCache as $k => $v) {
        if (!is_string($k) && !is_int($k)) {
            continue;
        }
        $num = floatval($v);
        if (is_finite($num)) {
            $map[(string)$k] = $num;
        }
    }
    $missing = [];
    foreach ($lonLats as $p) {
        if (!is_array($p) || count($p) < 2) {
            continue;
        }
        $lon = floatval($p[0]);
        $lat = floatval($p[1]);
        $key = kmz_lonlat_cache_key($lon, $lat);
        if (!array_key_exists($key, $map)) {
            $missing[] = [$lon, $lat];
        }
    }
    if ($missing === []) {
        return $map;
    }
    $fetchPts = $missing;
    $startKey = '';
    if (is_array($startLonLat) && count($startLonLat) >= 2) {
        $startKey = kmz_lonlat_cache_key(floatval($startLonLat[0]), floatval($startLonLat[1]));
        $fetchPts[] = [floatval($startLonLat[0]), floatval($startLonLat[1])];
    }
    $asl = kmz_fetch_geoadmin_terrain_asl_m($fetchPts);
    $startAsl = ($startKey !== '' && isset($asl[$startKey])) ? floatval($asl[$startKey]) : null;
    if ($startAsl === null && $asl !== []) {
        $first = reset($asl);
        $startAsl = is_finite(floatval($first)) ? floatval($first) : null;
    }
    if ($startAsl === null) {
        return $map;
    }
    foreach ($missing as $p) {
        $key = kmz_lonlat_cache_key(floatval($p[0]), floatval($p[1]));
        if (!isset($asl[$key])) {
            continue;
        }
        $map[$key] = floatval($asl[$key]) - $startAsl;
    }
    if ($startKey !== '' && !array_key_exists($startKey, $map)) {
        $map[$startKey] = 0.0;
    }
    return $map;
}

function kmz_is_coordinate_turn(array $missionSettings): bool
{
    $bundle = german_waypoint_type_to_wpml_bundle((string)($missionSettings['waypoint_type'] ?? ''));
    return ((string)($bundle['global_wpml_turn'] ?? '')) === 'coordinateTurn';
}

function kmz_is_alt_relative_to_start(array $missionSettings): bool
{
    $mode = wpml_height_mode_from_mission_altitude_mode((string)($missionSettings['altitude_mode'] ?? ''));
    return $mode === 'relativeToStartPoint';
}

/** @return array<string, mixed> */
function build_dji_pilot2_export_context(array $meta, array $path, array $missionSettings): array
{
    ensure_dji_kmz_reference_loaded();
    $info = (isset($GLOBALS['DJI_DroneInformation']) && is_array($GLOBALS['DJI_DroneInformation'])) ? $GLOBALS['DJI_DroneInformation'] : [];
    $droneKey = resolve_dji_interface_drone_key((string)($missionSettings['drone_type'] ?? 'Mavic 3T'));
    $wpBundle = german_waypoint_type_to_wpml_bundle((string)($missionSettings['waypoint_type'] ?? ''));
    $turnMode = (string)($wpBundle['global_wpml_turn'] ?? 'coordinateTurn');
    $headingMode = german_heading_mode_to_wpml_global((string)($missionSettings['heading_mode'] ?? ''));
    $headingPoiPoint = export_poi_point_csv($missionSettings['poi'] ?? null);
    if ($headingMode === 'towardPOI' && $headingPoiPoint === '') {
        $headingMode = 'followWayline';
    }
    $heightMode = wpml_height_mode_from_mission_altitude_mode((string)($missionSettings['altitude_mode'] ?? ''));
    $executeHeightMode = ($heightMode === 'WGS84') ? 'WGS84' : 'relativeToStartPoint';
    $aglM = clamp_export_agl_m(floatval($missionSettings['altitude_m'] ?? ($meta['std_agl_m'] ?? 60.0)));
    $speed = wpml_speed_limit_for_drone($droneKey, floatval($missionSettings['speed_m_s'] ?? 5.0));
    $transSpeed = $speed;
    if (!empty($missionSettings['start_speed_active'])) {
        $transSpeed = wpml_speed_limit_for_drone($droneKey, floatval($missionSettings['start_speed_m_s'] ?? $speed));
    }
    $transSpeed = clamp_export_float($transSpeed, 0.1, 15.0, 5.0);
    $takeoffH = wpml_takeoff_security_for_drone($droneKey, floatval($missionSettings['safe_start_altitude_m'] ?? 20.0));
    $rthH = max(2.0, $takeoffH);
    $finish = german_end_action_to_dji_pilot2((string)($missionSettings['end_action'] ?? ''));
    $allowedFinish = ['goHome', 'noAction', 'autoLand', 'gotoFirstWaypoint'];
    if (!in_array($finish, $allowedFinish, true)) {
        $finish = 'noAction';
    }
    $startHint = [];
    if (is_array($meta['start_lonlat'] ?? null) && count($meta['start_lonlat']) >= 2) {
        $startHint = [floatval($meta['start_lonlat'][0]), floatval($meta['start_lonlat'][1])];
    } elseif (!empty($path[0]) && is_array($path[0]) && count($path[0]) >= 2) {
        $startHint = [floatval($path[0][0]), floatval($path[0][1])];
    }
    $turnDistRaw = floatval($missionSettings['standard_turn_distance_m'] ?? 0.0);
    if (!is_finite($turnDistRaw) || $turnDistRaw < 0.0) {
        $turnDistRaw = 0.0;
    }
    $turnDist = clamp_export_turn_m($turnDistRaw);
    $applyTurnPerWaypoint = kmz_is_coordinate_turn($missionSettings) && $turnDist > 0.0;
    if ($applyTurnPerWaypoint) {
        $turnMode = 'coordinateTurn';
        $wpBundle['global_wpml_turn'] = 'coordinateTurn';
        $wpBundle['ch_waypoint_type'] = 'coordinateTurn';
        $wpBundle['path_mode'] = 'straight';
        $wpBundle['use_straight_line'] = 1;
    }
    $applyAltPerWaypoint = kmz_is_alt_relative_to_start($missionSettings) && $aglM > 0.05;
    $deltaCache = is_array($meta['terrain_delta_by_lonlat'] ?? null) ? $meta['terrain_delta_by_lonlat'] : [];
    $candidatePts = [];
    foreach ($path as $pathIdx => $p) {
        $aglT = floatval($p[2] ?? $aglM);
        if ($pathIdx === 0 && $aglT < 1.2 && $startHint && function_exists('path_points_same_lonlat') && path_points_same_lonlat($p, $startHint)) {
            continue;
        }
        $candidatePts[] = [
            'lng' => floatval($p[0] ?? 0.0),
            'lat' => floatval($p[1] ?? 0.0),
            'agl' => $aglT,
            'idx' => intval($pathIdx),
        ];
    }
    $terrainDeltas = [];
    if ($applyAltPerWaypoint && $candidatePts !== []) {
        $lonLats = [];
        foreach ($candidatePts as $c) {
            $lonLats[] = [$c['lng'], $c['lat']];
        }
        $terrainDeltas = kmz_terrain_delta_map_for_points($deltaCache, $lonLats, $startHint !== [] ? $startHint : null);
    }
    $waypoints = [];
    foreach ($candidatePts as $i => $c) {
        $ext = build_kmz_waypoint_extensions($missionSettings, intval($c['idx']), floatval($c['agl']));
        $h = clamp_wpml_rel_height_m(floatval($ext['_rel_height_hint'] ?? $aglM));
        $useGlobalHeight = intval($ext['wpml_use_global_height'] ?? 1);
        if ($applyAltPerWaypoint) {
            $h = clamp_wpml_rel_height_m(export_height_rel_start(
                $h,
                floatval($c['lng']),
                floatval($c['lat']),
                $terrainDeltas,
                true
            ));
            $useGlobalHeight = 0;
        } elseif ($h < 1.2) {
            $h = $aglM;
        }
        $ptTurn = (string)($ext['wpml_turn_mode'] ?? $turnMode);
        $headingChildren = is_array($ext['wpml_heading_children'] ?? null) ? $ext['wpml_heading_children'] : [];
        $ptHeading = (string)($headingChildren['wpml:waypointHeadingMode'] ?? $headingMode);
        $poiPoint = (string)($ext['poi_point'] ?? '');
        if ($ptHeading === 'towardPOI' && $poiPoint === '') {
            $ptHeading = 'followWayline';
        }
        $nWp = count($candidatePts);
        $isEnd = ($i === 0 || $i === $nWp - 1);
        $useGlobalTurn = intval($ext['wpml_use_global_turn'] ?? 1);
        $damp = floatval($ext['wpml_turn_damping_m'] ?? 0.0);
        if ($ptTurn !== 'coordinateTurn' || $isEnd) {
            $damp = 0.0;
        }
        $waypoints[] = [
            'lng' => floatval($c['lng']),
            'lat' => floatval($c['lat']),
            'height' => $h,
            'speed' => floatval($ext['wpml_waypoint_speed'] ?? $speed),
            'use_global_height' => $useGlobalHeight,
            'use_global_speed' => intval($ext['wpml_use_global_speed'] ?? 1),
            'use_global_heading' => intval($ext['wpml_use_global_heading'] ?? 1),
            'use_global_turn' => $useGlobalTurn,
            'turn_mode' => $ptTurn,
            'use_straight_line' => intval($ext['wpml_use_straight_line'] ?? ($wpBundle['use_straight_line'] ?? 1)),
            'turn_damping_m' => $damp,
            'heading_mode' => $ptHeading,
            'heading_angle' => array_key_exists('wpml:waypointHeadingAngle', $headingChildren)
                ? floatval($headingChildren['wpml:waypointHeadingAngle'])
                : null,
            'heading_path_mode' => (string)($headingChildren['wpml:waypointHeadingYawPathMode'] ?? ($headingChildren['wpml:waypointHeadingPathMode'] ?? 'followBadArc')),
            'poi_point' => $poiPoint,
            'gimbal_pitch' => is_numeric($ext['gimbal_pitch'] ?? null) ? floatval($ext['gimbal_pitch']) : null,
        ];
    }
    $first = $waypoints[0] ?? ['lat' => 0.0, 'lng' => 0.0, 'height' => $aglM];
    return [
        'author' => 'RKRK',
        'mission_name' => (string)($meta['mission_name_parts']['complete'] ?? ($meta['name'] ?? '')),
        'drone_key' => $droneKey,
        'drone_enum' => intval($info['wpml:droneEnumValue'][$droneKey] ?? 77),
        'drone_sub_enum' => intval($info['wpml:droneSubEnumValue'][$droneKey] ?? 1),
        'payload_enum' => intval($info['wpml:payloadEnumValue'][$droneKey] ?? 67),
        'payload_index' => intval($info['wpml:payloadPositionIndex'][$droneKey] ?? 0),
        'include_m30_payload_param' => ($droneKey === '30T'),
        'finish_action' => $finish,
        'exit_on_rc_lost' => 'executeLostAction',
        'execute_rc_lost' => 'goBack',
        'fly_to_wayline_mode' => !empty($missionSettings['direct_to_start_position']) ? 'pointToPoint' : 'safely',
        'takeoff_security_height' => $takeoffH,
        'global_transitional_speed' => $transSpeed,
        'auto_flight_speed' => $speed,
        'global_height' => $aglM,
        'global_rth_height' => $rthH,
        'height_mode' => ($heightMode === 'EGM96') ? 'EGM96' : (($heightMode === 'WGS84') ? 'WGS84' : 'relativeToStartPoint'),
        'execute_height_mode' => $executeHeightMode,
        'heading_mode' => $headingMode,
        'heading_poi_point' => $headingMode === 'towardPOI' ? $headingPoiPoint : '',
        'turn_mode' => $turnMode,
        'use_straight_line' => intval($wpBundle['use_straight_line'] ?? 1),
        'gimbal_pitch_mode' => wpml_gimbal_pitch_mode_from_mission((string)($missionSettings['gimbal_mode'] ?? '')),
        'takeoff_ref' => [floatval($first['lat']), floatval($first['lng']), floatval($first['height'])],
        'waypoints' => $waypoints,
    ];
}

/** @return array{path:array,metrics:array,used_inputs:array} */
function compute_planned_mission_path(array $poly, array $startPt, array $missionSettings, float $stdAgl, array $manualPath, array $payload): array
{
    $hfov = floatval($missionSettings['hfov_deg'] ?? 0.0);
    if ($hfov <= 0.0) $hfov = floatval($payload['hfov'] ?? 48.0);
    $aglForPlan = floatval($missionSettings['altitude_m'] ?? 0.0);
    if ($aglForPlan <= 0.0) $aglForPlan = $stdAgl;
    $flightDirectionDeg = floatval($missionSettings['flight_direction_deg'] ?? -1.0);
    if (!is_finite($flightDirectionDeg)) $flightDirectionDeg = -1.0;
    if ($flightDirectionDeg < -1.0) $flightDirectionDeg = -1.0;
    if ($flightDirectionDeg > 259.0) $flightDirectionDeg = 259.0;
    $preferredDirection = ($flightDirectionDeg > -1.0) ? 'fixed' : 'auto';
    $fixedAngleDeg = ($flightDirectionDeg > -1.0) ? $flightDirectionDeg : null;
    $edgeAlignApplied = false;
    $edgeAlignAngleDeg = null;
    $edgeAlignLenM = 0.0;
    if ($flightDirectionDeg <= -1.0) {
        $edgeInfo = longest_polygon_edge_angle_deg($poly);
        $edgeAlignAngleDeg = floatval($edgeInfo['angle_deg'] ?? 0.0);
        $edgeAlignLenM = floatval($edgeInfo['length_m'] ?? 0.0);
        $fixedAngleDeg = $edgeAlignAngleDeg;
        $preferredDirection = 'fixed';
        $edgeAlignApplied = true;
    }
    $lowReliefThresholdM = 15.0;
    $curveWaypointAngleStepDeg = 20.0;
    $reqPath = __DIR__ . DIRECTORY_SEPARATOR . 'requirements_data.json';
    if (is_file($reqPath)) {
        $reqRaw = @file_get_contents($reqPath);
        $reqPayload = $reqRaw ? json_decode($reqRaw, true) : null;
        if (is_array($reqPayload) && isset($reqPayload['core_parameters']) && is_array($reqPayload['core_parameters'])) {
            foreach ($reqPayload['core_parameters'] as $cp) {
                $k = trim((string)($cp['key'] ?? ''));
                if ($k === 'low_relief_height_threshold_m' && is_numeric($cp['value'] ?? null)) {
                    $lowReliefThresholdM = max(0.0, floatval($cp['value']));
                    continue;
                }
                if ($k === 'curve_waypoint_angle_step_deg' && is_numeric($cp['value'] ?? null)) {
                    $curveWaypointAngleStepDeg = max(1.0, floatval($cp['value']));
                }
            }
        }
    }
    $derivedDirection = derive_direction_from_height_points($manualPath);
    $heightRangeM = floatval($derivedDirection['height_range_m'] ?? 0.0);
    $isLowRelief = $heightRangeM < $lowReliefThresholdM;
    if ($flightDirectionDeg <= -1.0 && $isLowRelief) {
        $fixedAngleDeg = is_finite(floatval($edgeAlignAngleDeg))
            ? floatval($edgeAlignAngleDeg)
            : $fixedAngleDeg;
        $preferredDirection = 'fixed';
    }
    $overlap = floatval($missionSettings['overlap_pct'] ?? -1.0);
    if ($overlap >= 0.0) {
        $overlap /= 100.0;
    } else {
        $overlap = floatval($payload['overlap'] ?? 0.15);
    }
    if (!is_finite($overlap)) $overlap = 0.15;
    $overlap = max(0.0, min(0.95, $overlap));
    $edgeFactor = floatval($missionSettings['edge_factor_pct'] ?? -1.0);
    if ($edgeFactor >= 0.0) {
        $edgeFactor /= 100.0;
    } else {
        $edgeFactor = floatval($payload['edge_factor'] ?? 0.40);
    }
    if (!is_finite($edgeFactor)) $edgeFactor = 0.40;
    $edgeFactor = max(0.0, min(1.0, $edgeFactor));
    $planned = plan_lawnmower($poly, $startPt, $hfov, $aglForPlan, $overlap, $edgeFactor, $preferredDirection, $fixedAngleDeg, $curveWaypointAngleStepDeg);
    if (!is_array($planned) || !isset($planned['path']) || !is_array($planned['path'])) {
        $planned = ['path' => [], 'angle' => 0.0, 'W' => 0.0, 'S' => 0.0];
    }
    $path = ensure_start_point_first($planned['path'], $startPt, $aglForPlan);
    $maxOutsideM = max(0.0, $edgeFactor * floatval($planned['S'] ?? 0.0));
    $shapeInfo = field_major_axis_and_width($poly);
    $fieldWidthM = floatval($shapeInfo['minor_width_m'] ?? 0.0);
    $narrowThresholdM = 2.0 * floatval($planned['S'] ?? 0.0);
    $isNarrowField = $fieldWidthM < $narrowThresholdM;
    $path = enforce_path_inside_field_except_first_leg($path, $poly, $isNarrowField ? 0.0 : $maxOutsideM);

    $dfovForCoverage = floatval($missionSettings['dfov_deg'] ?? 61.0);
    if (!is_finite($dfovForCoverage) || $dfovForCoverage <= 0.0) {
        $dfovForCoverage = 61.0;
    }
    $turnDiameterM = 2.0 * $aglForPlan * tan(deg2rad($dfovForCoverage / 2.0));
    $minPathCoverageRatio = 0.98;
    if (is_file($reqPath)) {
        $reqRaw2 = @file_get_contents($reqPath);
        $reqPayload2 = $reqRaw2 ? json_decode($reqRaw2, true) : null;
        if (is_array($reqPayload2) && isset($reqPayload2['core_parameters']) && is_array($reqPayload2['core_parameters'])) {
            foreach ($reqPayload2['core_parameters'] as $cp2) {
                $k2 = trim((string)($cp2['key'] ?? ''));
                if ($k2 === 'DFOV_deg' && is_numeric($cp2['value'] ?? null)) {
                    $dfovTmp = max(1.0, floatval($cp2['value']));
                    $turnDiameterM = 2.0 * $aglForPlan * tan(deg2rad($dfovTmp / 2.0));
                    continue;
                }
                if ($k2 === 'min_path_coverage_ratio' && is_numeric($cp2['value'] ?? null)) {
                    $minPathCoverageRatio = max(0.80, min(1.0, floatval($cp2['value'])));
                }
            }
        }
    }
    $coverageBeforeTune = path_coverage_ratio_by_width_and_turns($path, $poly, floatval($planned['W'] ?? 0.0), $turnDiameterM, 80);
    $coverageAfterTune = $coverageBeforeTune;
    $angleTuneApplied = false;
    $angleTuneDeltaDeg = 0.0;
    if ($flightDirectionDeg <= -1.0 && !$isNarrowField && $coverageBeforeTune < $minPathCoverageRatio) {
        $baseTheta = isset($planned['angle']) ? floatval($planned['angle']) : (is_finite(floatval($fixedAngleDeg)) ? floatval($fixedAngleDeg) : 0.0);
        $bestPath = $path;
        $bestCov = $coverageBeforeTune;
        $bestDelta = 0.0;
        $deltaCandidates = [-10.0, -7.5, -5.0, -2.5, 2.5, 5.0, 7.5, 10.0];
        foreach ($deltaCandidates as $dTheta) {
            $candTheta = fmod(($baseTheta + floatval($dTheta) + 360.0), 180.0);
            $cand = plan_lawnmower($poly, $startPt, $hfov, $aglForPlan, $overlap, $edgeFactor, 'fixed', $candTheta, $curveWaypointAngleStepDeg);
            $candPath = ensure_start_point_first($cand['path'] ?? [], $startPt, $aglForPlan);
            $candPath = enforce_path_inside_field_except_first_leg($candPath, $poly, $isNarrowField ? 0.0 : $maxOutsideM);
            $cov = path_coverage_ratio_by_width_and_turns($candPath, $poly, floatval($cand['W'] ?? 0.0), $turnDiameterM, 80);
            if ($cov > $bestCov + 1e-9) {
                $bestCov = $cov;
                $bestPath = $candPath;
                $bestDelta = floatval($dTheta);
            }
            if ($bestCov >= $minPathCoverageRatio) {
                break;
            }
        }
        if ($bestCov > $coverageBeforeTune + 1e-9) {
            $path = $bestPath;
            $coverageAfterTune = $bestCov;
            $angleTuneApplied = true;
            $angleTuneDeltaDeg = $bestDelta;
        }
    }
    // Nordkanten-Mittelpunkt und Kanten-Ende nicht in den Flugweg. Die Suchabdeckung
    // folgt den Wegpunkten der Bahn, nicht diesen eingeschobenen Zwischenpunkten.
    $path = enforce_path_inside_field_except_first_leg($path, $poly, $isNarrowField ? 0.0 : $maxOutsideM);
    if (count($path) < 2 && count($startPt) >= 2 && count($poly) >= 1) {
        $path = ensure_start_point_first([[floatval($poly[0][0]), floatval($poly[0][1]), $aglForPlan]], $startPt, $aglForPlan);
    }
    return [
        'path' => $path,
        'metrics' => path_metrics($path),
        'used_inputs' => [
            'hfov_deg' => $hfov,
            'agl_m' => $aglForPlan,
            'flight_direction_deg' => $flightDirectionDeg,
            'flight_direction_mode' => $preferredDirection,
            'height_range_m' => $heightRangeM,
            'low_relief_threshold_m' => $lowReliefThresholdM,
            'low_relief_parallel_applied' => $isLowRelief && ($flightDirectionDeg <= -1.0),
            'open_direction_edge_align_applied' => $edgeAlignApplied,
            'open_direction_edge_align_angle_deg' => $edgeAlignAngleDeg,
            'open_direction_edge_align_edge_length_m' => $edgeAlignLenM,
            'curve_waypoint_angle_step_deg' => $curveWaypointAngleStepDeg,
            'overlap' => $overlap,
            'edge_factor' => $edgeFactor,
            'narrow_field' => $isNarrowField,
            'field_width_m' => $fieldWidthM,
            'narrow_field_threshold_m' => $narrowThresholdM,
            'coverage_before_angle_tune' => $coverageBeforeTune,
            'coverage_after_angle_tune' => $coverageAfterTune,
            'angle_tune_applied' => $angleTuneApplied,
            'angle_tune_delta_deg' => $angleTuneDeltaDeg,
        ],
    ];
}

if (!defined('RKRK_MISSION_EXPORT_AS_LIB')) {
$raw = file_get_contents('php://input');
$payload = $raw ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    fail_resp(422, 'invalid_json', 'Body muss JSON sein');
}
$action = strtolower(trim((string)($payload['action'] ?? '')));
$katOn = array_key_exists('KAT', $_GET) || !empty($payload['kat']);
if (!$katOn && ($action === 'export_uav_json' || $action === 'export_dji_kmz')) {
    fail_resp(403, 'kat_required', 'Serverdateien nur mit ?KAT. Ohne KAT das Feld lokal als KML oder JSON herunterladen.');
}
$field = is_array($payload['field'] ?? null) ? $payload['field'] : [];
$mappingConfig = load_interface_mapping_config();

$feldId = trim((string)($field['feld_id'] ?? ''));
$name = trim((string)($field['name'] ?? ''));
$stdAgl = floatval($field['std_flughoehe'] ?? 50.0);
$missionSettings = normalize_mission_settings(is_array($field['mission_settings'] ?? null) ? $field['mission_settings'] : []);
$start = parse_pairs_from_text((string)($field['startplatz'] ?? ''));
$poly = parse_pairs_from_text((string)($field['flaechendaten'] ?? ''));
$manualPath = parse_triplets_from_text((string)($field['flugweg'] ?? ''));
$exportMapping = build_export_mapping($missionSettings, $mappingConfig);

if ($action === 'get_mapping_schema') {
    echo json_encode([
        'ok' => true,
        'mapping' => $exportMapping,
        'mission_settings_defaults' => mission_settings_defaults(),
        'note' => 'Mapping between form (Flugvorgaben), UAV JSON and DJI KMZ.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'import_wpml_flags') {
    $kml = (string)($payload['template_kml'] ?? $payload['kml'] ?? '');
    if (trim($kml) === '' && !empty($payload['kmz_base64'])) {
        $rawKmz = base64_decode((string)$payload['kmz_base64'], true);
        if (is_string($rawKmz) && $rawKmz !== '' && class_exists('ZipArchive')) {
            $tmp = tempnam(sys_get_temp_dir(), 'wpml');
            if ($tmp !== false && file_put_contents($tmp, $rawKmz) !== false) {
                $zip = new ZipArchive();
                if ($zip->open($tmp) === true) {
                    $kml = (string)$zip->getFromName('wpmz/template.kml');
                    $zip->close();
                }
                @unlink($tmp);
            }
        }
    }
    if (trim($kml) === '') {
        fail_resp(422, 'missing_kml', 'template.kml oder KMZ fehlt.');
    }
    echo json_encode([
        'ok' => true,
        'waypoint_overrides' => waypoint_overrides_from_wpml_kml($kml),
        'note' => 'useGlobalHeight/Speed/TurnParam und useGlobalHeadingParam (Gierrichtung). Pfadmodus und Richtungsmodus haben kein wpml-Tag.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_dji_spec') {
    $spec = dji_internal_spec();
    if ($spec === []) {
        fail_resp(500, 'dji_spec_missing', 'dji_wpml_internal_spec.json fehlt oder ist ungueltig');
    }
    echo json_encode([
        'ok' => true,
        'spec' => $spec,
        'ui' => dji_spec_ui_bundle(),
        'note' => 'Interne DJI-WPML-Spezifikation (DE). Drohnen-Enums zur Laufzeit aus rkrk_dji_kmz_interface.php.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_drone_profiles') {
    $profiles = load_drone_profiles_from_reference();
    echo json_encode([
        'ok' => true,
        'profiles' => $profiles,
        'defaults' => mission_settings_defaults(),
        'note' => 'Drohnenprofile aus ../scripts/rkrk_dji_kmz_interface.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($feldId === '' || strcasecmp($feldId, 'UNDEF') === 0) {
    $feldId = rkrk_catalog_feld_id_for_field($field);
}
$field['feld_id'] = $feldId;
if (count($start) < 1 || count($poly) < 3) {
    fail_resp(422, 'invalid_field_geometry', 'Startplatz und Flaechendaten erforderlich');
}
$startPt = $start[0];
$meta = [
    'feld_id' => $feldId,
    'name' => $name,
    'std_agl_m' => $stdAgl,
    'mission_settings' => $missionSettings,
];
if ($action === 'compute_path') {
    $planned = compute_planned_mission_path($poly, $startPt, $missionSettings, $stdAgl, $manualPath, $payload);
    echo json_encode([
        'ok' => true,
        'path' => $planned['path'],
        'flugweg_text' => format_triplets_text($planned['path']),
        'metrics' => $planned['metrics'],
        'used_inputs' => $planned['used_inputs'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$missionPaths = resolve_mission_intern_paths();
ensure_mission_intern_structure($missionPaths);
$fieldMissionDir = ensure_field_mission_dir($missionPaths, $feldId);

if (count($manualPath) < 2 && ($action === 'export_dji_kmz' || $action === 'export_uav_json')) {
    $planned = compute_planned_mission_path($poly, $startPt, $missionSettings, $stdAgl, $manualPath, $payload);
    $manualPath = $planned['path'] ?? [];
}

if (count($manualPath) < 2) {
    fail_resp(422, 'invalid_path', 'Kein Flugweg: zuerst Feld und Startpunkt setzen, dann Pfad berechnen oder erneut KMZ exportieren.');
}

if ($action === 'export_uav_json') {
    if ($feldId === '') {
        fail_resp(422, 'missing_feld_id', 'Feldkatalognummer fehlt. Feld zuerst zuordnen oder speichern.');
    }
    $meta['feld_id'] = $feldId;
    $meta['jg'] = trim((string)($field['jg'] ?? ''));
    $meta['start_lonlat'] = $startPt;
    $meta['terrain_delta_by_lonlat'] = is_array($field['terrain_delta_by_lonlat'] ?? null)
        ? $field['terrain_delta_by_lonlat']
        : [];
    $meta['mission_name_parts'] = rkrk_mission_name_parts_from_field($field, $missionSettings);
    $uav = build_uav_json_payload($meta, $manualPath, $poly, $startPt);
    $jsonFile = $meta['mission_name_parts']['complete'] . '.json';
    $jsonPath = $fieldMissionDir . DIRECTORY_SEPARATOR . $jsonFile;
    @file_put_contents($jsonPath, json_encode($uav, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode([
        'ok' => true,
        'filename' => $jsonFile,
        'stored_path' => $jsonPath,
        'schema' => 'ch_uav_mission_json_like_samples',
        'mapping' => $exportMapping,
        'payload' => $uav,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'export_dji_kmz') {
    try {
        ensure_dji_kmz_reference_loaded();
        if (!class_exists('ZipArchive')) {
            fail_resp(500, 'zip_missing', 'PHP-ZipArchive fehlt auf dem Server. KMZ kann nicht gepackt werden.');
        }
        $exportDir = $fieldMissionDir;
        if (!is_dir($exportDir) && !mkdir($exportDir, 0777, true) && !is_dir($exportDir)) {
            fail_resp(500, 'export_dir_failed', 'Export-Verzeichnis konnte nicht erstellt werden: ' . $exportDir);
        }
        $kmzSettings = effective_kmz_settings($missionSettings);
        $aglForExport = clamp_export_agl_m(floatval($kmzSettings['altitude_m'] ?? ($meta['std_agl_m'] ?? 60.0)));
        $meta['start_lonlat'] = $startPt;
        $meta['terrain_delta_by_lonlat'] = is_array($field['terrain_delta_by_lonlat'] ?? null)
            ? $field['terrain_delta_by_lonlat']
            : [];
        $exportPath = (count($startPt) >= 2)
            ? ensure_start_point_first($manualPath, $startPt, $aglForExport)
            : $manualPath;
        if (count($exportPath) < 2) {
            fail_resp(422, 'invalid_path', 'Kein Flugweg fuer KMZ (Feld + Startpunkt noetig).');
        }
        $safeParts = rkrk_mission_name_parts_from_field($field, $kmzSettings);
        $meta['mission_name_parts'] = $safeParts;
        $base = $safeParts['complete'];
        $kmzPath = $exportDir . DIRECTORY_SEPARATOR . $base . '.kmz';
        $ctx = build_dji_pilot2_export_context($meta, $exportPath, $kmzSettings);
        if (empty($ctx['waypoints']) || count($ctx['waypoints']) < 2) {
            fail_resp(422, 'invalid_path', 'KMZ braucht mindestens 2 Wegpunkte nach Start/Steigflug.');
        }
        $templateKml = build_dji_wpml_template_kml($ctx);
        $waylinesWpml = build_dji_wpml_waylines_wpml($ctx);
        if (!pack_dji_pilot2_kmz_file($kmzPath, $templateKml, $waylinesWpml)) {
            fail_resp(500, 'kmz_open_failed', 'KMZ-Zip konnte nicht geschrieben werden: ' . $kmzPath);
        }
        @file_put_contents($exportDir . DIRECTORY_SEPARATOR . $base . '.kml', $templateKml);
        @file_put_contents($exportDir . DIRECTORY_SEPARATOR . $base . '_waylines.wpml', $waylinesWpml);
        $kmzBytes = @file_get_contents($kmzPath);
        $kmzB64 = ($kmzBytes !== false && $kmzBytes !== '') ? base64_encode($kmzBytes) : null;
        if ($kmzB64 === null) {
            fail_resp(500, 'kmz_empty', 'KMZ-Datei wurde erstellt, konnte aber nicht gelesen werden.');
        }
        echo json_encode([
            'ok' => true,
            'filename' => basename($kmzPath),
            'template_filename' => $base . '.kml',
            'template_kml' => $templateKml,
            'stored_path' => $kmzPath,
            'download_url' => null,
            'kmz_base64' => $kmzB64,
            'mapping' => $exportMapping,
            'kmz_params_active' => !empty($missionSettings['kmz_params_active']),
            'wpml' => [
                'format' => 'dji-pilot-2-wpmz',
                'files' => ['wpmz/template.kml', 'wpmz/waylines.wpml'],
                'drone_key' => $ctx['drone_key'],
                'drone_enum' => $ctx['drone_enum'],
                'payload_enum' => $ctx['payload_enum'],
                'waypoint_count' => count($ctx['waypoints']),
                'finish_action' => $ctx['finish_action'],
                'height_mode' => $ctx['height_mode'],
            ],
            'note' => 'DJI Pilot 2 KMZ (WPML): wpmz/template.kml + wpmz/waylines.wpml. Import in der Pilot-2-App unter Flugroute / KMZ.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        fail_resp(500, 'kmz_export_failed', $e->getMessage());
    }
}

fail_resp(422, 'unknown_action', 'Unbekannte action');
}

