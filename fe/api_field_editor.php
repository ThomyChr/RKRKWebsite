<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/mapll_env.php';
include_once dirname(__DIR__) . '/scripts/rkrk_encoding.php';
include_once dirname(__DIR__) . '/scripts/rkrk_catalog_io.php';

function fail(int $code, string $error, string $detail = ''): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $error, 'detail' => $detail], JSON_UNESCAPED_UNICODE);
    exit;
}

function resolve_mission_intern_paths(): array
{
    return mapll_mission_intern_paths();
}

function ensure_mission_intern_structure(array $paths): void
{
    $dirs = [
        $paths['root'],
        $paths['root'] . DIRECTORY_SEPARATOR . '_checklists',
        $paths['root'] . DIRECTORY_SEPARATOR . '_deleted',
        $paths['root'] . DIRECTORY_SEPARATOR . '_history',
        $paths['root'] . DIRECTORY_SEPARATOR . '_log',
        $paths['root'] . DIRECTORY_SEPARATOR . '_rt',
        $paths['root'] . DIRECTORY_SEPARATOR . '_temp',
        $paths['root'] . DIRECTORY_SEPARATOR . 'csvfiles',
        $paths['root'] . DIRECTORY_SEPARATOR . 'xmlfiles',
        $paths['root'] . DIRECTORY_SEPARATOR . 'jsonfiles',
        $paths['root'] . DIRECTORY_SEPARATOR . 'TF_Testfelder',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            fail(500, 'mkdir_failed', 'Verzeichnis konnte nicht erstellt werden: ' . $dir);
        }
    }
}

function ensure_xml_seed_file(array $paths): void
{
    if (is_file($paths['xml_path'])) {
        return;
    }
    $sourceCandidates = [
        mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'Missionen' . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml',
        mapll_root() . DIRECTORY_SEPARATOR . 'RKRKFelder.xml',
    ];
    foreach ($sourceCandidates as $src) {
        if (is_file($src) && @copy($src, $paths['xml_path'])) {
            return;
        }
    }
    $empty = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<RKRKFelder/>\n";
    if (@file_put_contents($paths['xml_path'], $empty) === false) {
        fail(500, 'xml_seed_failed', 'RKRKFelder.xml konnte nicht initialisiert werden');
    }
}

function parse_pairs_text(string $raw): array
{
    preg_match_all('/-?\d+(?:\.\d+)?/', $raw, $m);
    $nums = $m[0] ?? [];
    $out = [];
    for ($i = 0; $i + 1 < count($nums); $i += 2) {
        $out[] = [floatval($nums[$i]), floatval($nums[$i + 1])];
    }
    return $out;
}

function parse_triplets_text(string $raw): array
{
    preg_match_all('/-?\d+(?:\.\d+)?/', $raw, $m);
    $nums = $m[0] ?? [];
    $out = [];
    for ($i = 0; $i + 2 < count($nums); $i += 3) {
        $out[] = [floatval($nums[$i]), floatval($nums[$i + 1]), floatval($nums[$i + 2])];
    }
    return $out;
}

function format_pairs_text(array $pts): string
{
    $parts = [];
    foreach ($pts as $p) {
        if (!is_array($p) || count($p) < 2) {
            continue;
        }
        $parts[] = rtrim(rtrim(sprintf('%.13F', floatval($p[0])), '0'), '.') . ', ' .
            rtrim(rtrim(sprintf('%.13F', floatval($p[1])), '0'), '.');
    }
    return implode('     ', $parts);
}

function format_center_text(array $pt): string
{
    if (count($pt) < 2) {
        return '';
    }
    return sprintf('%.4F', floatval($pt[0])) . ', ' . sprintf('%.4F', floatval($pt[1]));
}

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

function ensure_child(SimpleXMLElement $parent, string $name, string $value = ''): SimpleXMLElement
{
    if (isset($parent->{$name})) {
        $parent->{$name} = $value;
        return $parent->{$name};
    }
    return $parent->addChild($name, $value);
}

function editor_field_key(SimpleXMLElement $f): string
{
    $attr = trim((string)$f['id']);
    if ($attr !== '') {
        return $attr;
    }
    return trim((string)($f->FeldID ?? ''));
}

function xml_to_array_simple(SimpleXMLElement $f): array
{
    $fieldId = trim((string)($f->FeldID ?? ''));
    $name = trim((string)($f->Feldname ?? ''));
    $startRaw = trim((string)($f->Grundstueckdaten->Startplatz ?? ''));
    $polyRaw = trim((string)($f->Flaechendaten ?? ''));
    $pathRaw = trim((string)($f->Flugweg ?? ''));
    $missionSettings = [];
    $msRaw = trim((string)($f->MissionSettings ?? ''));
    if ($msRaw !== '') {
        $decoded = json_decode($msRaw, true);
        if (is_array($decoded)) {
            $missionSettings = $decoded;
        }
    }
    return [
        'id' => editor_field_key($f),
        'number' => trim((string)($f->Number ?? '')),
        'feld_id' => $fieldId,
        'name' => $name,
        'grundstueck_id' => trim((string)($f->GrundstueckID ?? '')),
        'jg' => trim((string)($f->JG ?? '')),
        'gemeinde' => trim((string)($f->Gemeinde ?? '')),
        'std_flughoehe' => trim((string)($f->StdFlughohe ?? '50')),
        'flugzeit' => trim((string)($f->Flugzeit ?? '0')),
        'distanz' => trim((string)($f->Distanz ?? '0')),
        'wegpunkte' => trim((string)($f->Wegpunkte ?? '0')),
        'flurname' => trim((string)($f->Flurname ?? '')),
        'flaeche' => trim((string)($f->Flaeche ?? '')),
        'betrieb' => trim((string)($f->Betrieb ?? '')),
        'freigabe' => trim((string)($f->Freigabe ?? '')),
        'warnung' => trim((string)($f->Warnung ?? '')),
        'jaeger' => trim((string)($f->Jaeger ?? '')),
        'bemerkung' => trim((string)($f->Bemerkung ?? '')),
        'flug' => trim((string)($f->Flug ?? '')),
        'funde' => trim((string)($f->Funde ?? '')),
        'bauer' => trim((string)($f->Bauer ?? '')),
        'notiz' => trim((string)($f->Notiz ?? '')),
        'suchfeld_bild' => trim((string)($f->SuchfeldBild ?? '')),
        'flugplan_bild' => trim((string)($f->FlugplanBild ?? '')),
        'jason_file' => trim((string)($f->JasonFile ?? '')),
        'kml_file' => trim((string)($f->KMLFile ?? '')),
        'kmz_file' => trim((string)($f->KMZFile ?? '')),
        'lichti_file' => trim((string)($f->LichtiFile ?? '')),
        'parkplatz' => trim((string)($f->Grundstueckdaten->Parkplatz ?? '')),
        'zentrum' => trim((string)($f->Grundstueckdaten->Zentrum ?? '')),
        'export' => trim((string)($f->Export ?? '')),
        'select' => trim((string)($f->Select ?? '')),
        'erstellt' => trim((string)($f->Erstellt ?? '')),
        'aenderung' => trim((string)($f->Aenderung ?? '')),
        'katalog_update' => trim((string)($f->Update ?? '')),
        'startplatz' => $startRaw,
        'flaechendaten' => $polyRaw,
        'flugweg' => $pathRaw,
        'mission_settings' => $missionSettings,
    ];
}

function editor_put_text(SimpleXMLElement $node, string $xmlName, array $data, string $key, bool $skipEmpty = false): void
{
    if (!array_key_exists($key, $data)) {
        return;
    }
    $value = trim((string)$data[$key]);
    if ($skipEmpty && $value === '') {
        return;
    }
    ensure_child($node, $xmlName, $value);
}

function editor_yn_flag($value, string $fallback = 'Y'): string
{
    $v = strtoupper(trim((string)$value));
    if ($v === 'Y' || $v === 'N') {
        return $v;
    }
    if (in_array($v, ['1', 'ON', 'JA', 'TRUE'], true)) {
        return 'Y';
    }
    if (in_array($v, ['0', 'OFF', 'NEIN', 'FALSE'], true)) {
        return 'N';
    }
    return $fallback === 'N' ? 'N' : 'Y';
}

function editor_computed_catalog_warning(SimpleXMLElement $f): string
{
    $hadJson = array_key_exists('jsonMissionLocation', $GLOBALS);
    $hadXml = array_key_exists('xmlMissionLocation', $GLOBALS);
    $hadPoi = array_key_exists('xml_poifile', $GLOBALS);
    $prevJson = $hadJson ? $GLOBALS['jsonMissionLocation'] : null;
    $prevXml = $hadXml ? $GLOBALS['xmlMissionLocation'] : null;
    $prevPoi = $hadPoi ? $GLOBALS['xml_poifile'] : null;
    editor_load_catalog_geo();
    try {
        if (!function_exists('GetWarnungFlgZOfPOint') || !function_exists('GetSunOfPOint') || !function_exists('GetSoraOfPOint')) {
            return '';
        }
        $flaeche = trim((string)($f->Flaechendaten ?? ''));
        $zentrum = '';
        if ($flaeche !== '') {
            $zentrum = format_center_text(editor_polygon_centroid(parse_pairs_text($flaeche)));
        }
        if ($zentrum === '') {
            $zentrum = trim((string)($f->Grundstueckdaten->Zentrum ?? ''));
        }
        $flg = trim((string)GetWarnungFlgZOfPOint($zentrum));
        $sun = trim((string)GetSunOfPOint($zentrum));
        $feld = ['Flaechendaten' => $flaeche];
        $sora = trim((string)GetSoraOfPOint($feld));
        return trim($flg . ' ' . $sun . ' ' . $sora);
    } finally {
        if ($hadJson) {
            $GLOBALS['jsonMissionLocation'] = $prevJson;
        } else {
            unset($GLOBALS['jsonMissionLocation']);
        }
        if ($hadXml) {
            $GLOBALS['xmlMissionLocation'] = $prevXml;
        } else {
            unset($GLOBALS['xmlMissionLocation']);
        }
        if ($hadPoi) {
            $GLOBALS['xml_poifile'] = $prevPoi;
        } else {
            unset($GLOBALS['xml_poifile']);
        }
    }
}

function editor_write_catalog_details(SimpleXMLElement $f, array $data, bool $isCreate, string $missionFile = ''): void
{
    editor_put_text($f, 'Flurname', $data, 'flurname');
    editor_put_text($f, 'Flaeche', $data, 'flaeche');
    editor_put_text($f, 'Betrieb', $data, 'betrieb');
    editor_put_text($f, 'Freigabe', $data, 'freigabe');
    if ($isCreate) {
        ensure_child($f, 'Freigabe', 'NEU');
    }
    $geoWarn = editor_computed_catalog_warning($f);
    if ($geoWarn !== '') {
        ensure_child($f, 'Warnung', $geoWarn);
    } else {
        $incoming = array_key_exists('warnung', $data) ? trim((string)$data['warnung']) : '';
        if ($incoming !== '') {
            ensure_child($f, 'Warnung', $incoming);
        }
    }
    editor_put_text($f, 'Jaeger', $data, 'jaeger');
    editor_put_text($f, 'Bemerkung', $data, 'bemerkung');
    editor_put_text($f, 'Flug', $data, 'flug');
    editor_put_text($f, 'Funde', $data, 'funde');
    editor_put_text($f, 'Bauer', $data, 'bauer');
    editor_put_text($f, 'Notiz', $data, 'notiz');
    editor_put_text($f, 'SuchfeldBild', $data, 'suchfeld_bild', true);
    editor_put_text($f, 'FlugplanBild', $data, 'flugplan_bild', true);
    editor_put_text($f, 'KMLFile', $data, 'kml_file', true);
    editor_put_text($f, 'KMZFile', $data, 'kmz_file', true);
    editor_put_text($f, 'LichtiFile', $data, 'lichti_file', true);
    if ($isCreate) {
        $jason = trim((string)($data['jason_file'] ?? ''));
        ensure_child($f, 'JasonFile', $jason !== '' ? $jason : $missionFile);
    } else {
        editor_put_text($f, 'JasonFile', $data, 'jason_file', true);
    }
    if (array_key_exists('gemeinde', $data)) {
        ensure_child($f, 'Gemeinde', trim((string)$data['gemeinde']));
    }
    if (array_key_exists('export', $data) || $isCreate) {
        ensure_child($f, 'Export', editor_yn_flag($data['export'] ?? 'Y', 'Y'));
    }
    if (array_key_exists('select', $data) || $isCreate) {
        ensure_child($f, 'Select', editor_yn_flag($data['select'] ?? 'Y', 'Y'));
    }
    $gd = isset($f->Grundstueckdaten) ? $f->Grundstueckdaten : $f->addChild('Grundstueckdaten');
    editor_put_text($gd, 'Parkplatz', $data, 'parkplatz', true);
    editor_put_text($gd, 'Zentrum', $data, 'zentrum', true);
}

function editor_choice_rows(array $src, string $valueKey, string $labelKey): array
{
    $out = [];
    foreach ($src as $row) {
        if (!is_array($row)) {
            continue;
        }
        $value = trim((string)($row[$valueKey] ?? ''));
        if ($value === '') {
            continue;
        }
        $label = trim((string)($row[$labelKey] ?? $value));
        $out[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
    }
    usort($out, static function (array $a, array $b): int {
        return strcasecmp($a['label'], $b['label']);
    });
    return $out;
}

function editor_choice_lists(SimpleXMLElement $xml): array
{
    editor_load_name_tables();
    $jg = [];
    foreach (($GLOBALS['RegionSettings'] ?? []) as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $code = trim((string)($row['Reg'] ?? $key));
        if ($code === '' || in_array($code, ['ALL', 'leer', 'NO'], true)) {
            continue;
        }
        $name = trim((string)($row['GroupAreaName'] ?? $row['Text'] ?? $code));
        $jg[] = ['value' => $code, 'label' => $name !== '' ? $name : $code];
    }
    usort($jg, static function (array $a, array $b): int {
        return strcasecmp($a['label'], $b['label']);
    });
    $jaeger = [];
    foreach ($xml->Feld as $f) {
        $name = trim((string)($f->Jaeger ?? ''));
        if ($name !== '') {
            $jaeger[$name] = $name;
        }
    }
    $jaegerRows = [];
    foreach ($jaeger as $name) {
        $jaegerRows[] = ['value' => $name, 'label' => $name];
    }
    usort($jaegerRows, static function (array $a, array $b): int {
        return strcasecmp($a['label'], $b['label']);
    });
    return [
        'jg' => $jg,
        'gemeinde' => editor_choice_rows($GLOBALS['OptionGemeinde'] ?? [], 'Opt', 'Text'),
        'hoehe' => editor_choice_rows($GLOBALS['OptionFlughoehe'] ?? [], 'Opt', 'Text'),
        'freigabe' => editor_choice_rows($GLOBALS['OptionFrei'] ?? [], 'Opt', 'Text'),
        'warnung' => editor_choice_rows($GLOBALS['OptionWarning'] ?? [], 'Opt', 'Text'),
        'jaeger' => $jaegerRows,
    ];
}

function field_mission_settings_json(array $data): string
{
    $ms = is_array($data['mission_settings'] ?? null) ? $data['mission_settings'] : [];
    $mode = trim((string)($ms['waypoint_settings_mode'] ?? ''));
    $speed = floatval($ms['speed_m_s'] ?? 5);
    if (!is_finite($speed) || $speed <= 0) {
        $speed = 5;
    }
    $gimbalMode = trim((string)($ms['gimbal_mode'] ?? 'Manuell'));
    $gimbalPitch = null;
    if (isset($ms['gimbal_pitch']) && is_numeric($ms['gimbal_pitch'])) {
        $gimbalPitch = max(-90.0, min(30.0, floatval($ms['gimbal_pitch'])));
    }
    $payload = [
        'waypoint_settings_mode' => ($mode === 'per_waypoint') ? 'per_waypoint' : 'route_follow',
        'waypoint_overrides' => is_array($ms['waypoint_overrides'] ?? null) ? $ms['waypoint_overrides'] : [],
        'speed_m_s' => max(1, min(15, $speed)),
        'gimbal_mode' => $gimbalMode !== '' ? $gimbalMode : 'Manuell',
        'gimbal_pitch' => $gimbalPitch,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($json) ? $json : '{}';
}

function write_field_mission_settings(SimpleXMLElement $f, array $data): void
{
    $json = field_mission_settings_json($data);
    if (isset($f->MissionSettings)) {
        $f->MissionSettings = $json;
        return;
    }
    $f->addChild('MissionSettings', $json);
}

function editor_polygon_centroid(array $poly): array
{
    $lon = 0.0;
    $lat = 0.0;
    $n = 0;
    foreach ($poly as $p) {
        if (!is_array($p) || count($p) < 2) {
            continue;
        }
        $lon += floatval($p[0]);
        $lat += floatval($p[1]);
        $n++;
    }
    if ($n < 1) {
        return [];
    }
    return [$lon / $n, $lat / $n];
}

function editor_load_name_tables(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $scripts = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts';
    if (!function_exists('GetGdeOfPOint')) {
        $cwd = getcwd();
        chdir($scripts);
        ob_start();
        include_once $scripts . DIRECTORY_SEPARATOR . 'rkrk_geofence.php';
        ob_end_clean();
        if (is_string($cwd) && $cwd !== '') {
            chdir($cwd);
        }
        foreach (['RegionSettings', 'MissionLocation', 'xmlMissionLocation', 'xml_poifile', 'KMLList', 'PoiArraySession', 'RKRKMissionSelected', 'MissionDroneTypes', 'RKRKLocalLoactionMissionFiles', 'SessNme_MissionDBSetting', 'DefaultNumOfPointsChecked', 'AreaStopDistance', 'AreaStopDistance_SORA', 'searchSDZ', 'replaceSDZ', 'OptionGemeinde', 'OptionFrei', 'OptionWarning', 'OptionFlughoehe'] as $name) {
            if (isset($$name)) {
                $GLOBALS[$name] = $$name;
            }
        }
    }
    if (empty($GLOBALS['MissionLocation']) || !is_string($GLOBALS['MissionLocation'])) {
        $GLOBALS['MissionLocation'] = 'Missionen/';
    }
    if (intval($GLOBALS['DefaultNumOfPointsChecked'] ?? 0) < 1) {
        $GLOBALS['DefaultNumOfPointsChecked'] = 10000;
    }
    $loaded = true;
}

function editor_load_catalog_geo(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    editor_load_name_tables();
    $webdaten = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'rkrk' . DIRECTORY_SEPARATOR . 'webdaten' . DIRECTORY_SEPARATOR;
    $GLOBALS['jsonMissionLocation'] = $webdaten;
    $GLOBALS['xmlMissionLocation'] = $webdaten;
    $GLOBALS['xml_poifile'] = 'rkrk_poidata.xml';
    $poiKey = is_string($GLOBALS['PoiArraySession'] ?? null) ? $GLOBALS['PoiArraySession'] : 'PoiArray';
    if (isset($_SESSION[$poiKey]) && (!is_array($_SESSION[$poiKey]) || $_SESSION[$poiKey] === [])) {
        unset($_SESSION[$poiKey]);
    }
    if ((!isset($_SESSION[$poiKey]) || !is_array($_SESSION[$poiKey]) || $_SESSION[$poiKey] === []) && function_exists('GetPoiArray')) {
        $poi = GetPoiArray($webdaten . 'rkrk_poidata.xml');
        if (is_array($poi) && $poi !== []) {
            $_SESSION[$poiKey] = $poi;
        }
    }
    $loaded = true;
}

function editor_place_from_points(array $candidates): array
{
    $hadJson = array_key_exists('jsonMissionLocation', $GLOBALS);
    $hadXml = array_key_exists('xmlMissionLocation', $GLOBALS);
    $prevJson = $hadJson ? $GLOBALS['jsonMissionLocation'] : null;
    $prevXml = $hadXml ? $GLOBALS['xmlMissionLocation'] : null;
    editor_load_catalog_geo();
    try {
        $gemeinde = '';
        $jg = '';
        $used = '';
        foreach ($candidates as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                continue;
            }
            $text = rtrim(rtrim(sprintf('%.8F', floatval($pt[0])), '0'), '.') . ', ' .
                rtrim(rtrim(sprintf('%.8F', floatval($pt[1])), '0'), '.');
            $gde = function_exists('GetGdeOfPOint') ? trim((string)GetGdeOfPOint($text)) : '';
            $jgd = function_exists('GetJgdOfPOint') ? trim((string)GetJgdOfPOint($text)) : '';
            if ($gemeinde === '' && $gde !== '') {
                $gemeinde = $gde;
            }
            if ($jg === '' && $jgd !== '') {
                $jg = $jgd;
                $used = $text;
            }
            if ($gemeinde !== '' && $jg !== '') {
                $used = $text;
                break;
            }
        }
        return [
            'gemeinde' => $gemeinde,
            'gemeinde_name' => editor_place_name($gemeinde, 'Gde'),
            'jg' => $jg,
            'jg_name' => editor_place_name($jg, 'Jgd'),
            'zentrum' => $used,
        ];
    } finally {
        if ($hadJson) {
            $GLOBALS['jsonMissionLocation'] = $prevJson;
        } else {
            unset($GLOBALS['jsonMissionLocation']);
        }
        if ($hadXml) {
            $GLOBALS['xmlMissionLocation'] = $prevXml;
        } else {
            unset($GLOBALS['xmlMissionLocation']);
        }
    }
}

function editor_place_label(string $id, string $name): string
{
    $id = trim($id);
    $name = trim((string)preg_replace('/\s+/u', ' ', $name));
    if ($id === '') {
        return $name;
    }
    if ($name === '' || strcasecmp($name, $id) === 0) {
        return $id;
    }
    if (preg_match('/^' . preg_quote($id, '/') . '(?:\s|[-_])/u', $name)) {
        return $name;
    }
    return $id . ' ' . $name;
}

function editor_place_name(string $id, string $listKey): string
{
    editor_load_name_tables();
    $id = trim($id);
    if ($id === '') {
        return '';
    }
    if ($listKey === 'Jgd') {
        $regionName = $GLOBALS['RegionSettings'][$id]['GroupAreaName'] ?? '';
        if (is_string($regionName) && trim($regionName) !== '') {
            return editor_place_label($id, $regionName);
        }
    }
    $list = $GLOBALS['KMLList'][$listKey] ?? [];
    if (is_array($list)) {
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (trim((string)($entry['ID'] ?? '')) !== $id) {
                continue;
            }
            return editor_place_label($id, (string)($entry['Name'] ?? ''));
        }
    }
    return $id;
}

function editor_reserve_ttl(): int
{
    return 1800;
}

function editor_reserve_token_is(string $a, string $b): bool
{
    if ($a === '' || $b === '' || strlen($a) !== strlen($b)) {
        return false;
    }
    return hash_equals($a, $b);
}

function editor_reserve_token_from(array $payload): string
{
    $token = trim((string)($payload['token'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_-]{8,80}$/', $token)) {
        return '';
    }
    return $token;
}

function editor_reserve_path(): string
{
    $dir = rtrim((string)($GLOBALS['feldkatalogJsonLocation'] ?? ''), "/\\");
    if ($dir === '') {
        editor_bind_mission_catalog();
        $dir = rtrim((string)($GLOBALS['feldkatalogJsonLocation'] ?? ''), "/\\");
    }
    return $dir . DIRECTORY_SEPARATOR . 'RKRKFelder.reserve.json';
}

function editor_reserve_load(bool $refresh = false): array
{
    $path = editor_reserve_path();
    static $cache = [];
    if ($refresh) {
        unset($cache[$path]);
    }
    if (isset($cache[$path])) {
        return $cache[$path];
    }
    $cache[$path] = editor_reserve_rows_from($path);
    return $cache[$path];
}

function editor_reserve_rows_from(string $path): array
{
    $items = [];
    if (!is_file($path)) {
        return $items;
    }
    $decoded = json_decode((string)file_get_contents($path), true);
    $rows = [];
    if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
        $rows = $decoded['items'];
    } elseif (is_array($decoded)) {
        $rows = $decoded;
    }
    $now = time();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $feldId = trim((string)($row['FeldID'] ?? ''));
        $token = trim((string)($row['token'] ?? ''));
        $jg = trim((string)($row['jg'] ?? ''));
        $until = intval($row['until'] ?? 0);
        if ($feldId === '' || $token === '' || $until < $now) {
            continue;
        }
        $items[] = [
            'FeldID' => $feldId,
            'token' => $token,
            'jg' => $jg,
            'until' => $until,
        ];
    }
    return $items;
}

function editor_reserve_store(array $items): void
{
    $path = editor_reserve_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        fail(500, 'catalog_lock', 'Feldkatalogordner fehlt');
    }
    $clean = [];
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean[] = [
            'FeldID' => trim((string)($row['FeldID'] ?? '')),
            'token' => trim((string)($row['token'] ?? '')),
            'jg' => trim((string)($row['jg'] ?? '')),
            'until' => intval($row['until'] ?? 0),
        ];
    }
    $json = json_encode(['items' => array_values($clean)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json) || file_put_contents($path, $json, LOCK_EX) === false) {
        fail(500, 'reserve_write', 'Reservierung konnte nicht geschrieben werden');
    }
    editor_reserve_load(true);
}

function editor_reserve_drop(string $token, string $feldId = ''): void
{
    $feldId = trim($feldId);
    $items = [];
    foreach (editor_reserve_load(true) as $row) {
        if ($token !== '' && editor_reserve_token_is((string)$row['token'], $token)) {
            continue;
        }
        if ($feldId !== '' && strcasecmp((string)$row['FeldID'], $feldId) === 0) {
            continue;
        }
        $items[] = $row;
    }
    editor_reserve_store($items);
}

function editor_reserve_touch(string $token): bool
{
    if ($token === '') {
        return false;
    }
    $found = false;
    $until = time() + editor_reserve_ttl();
    $items = editor_reserve_load(true);
    foreach ($items as $i => $row) {
        if (!editor_reserve_token_is((string)$row['token'], $token)) {
            continue;
        }
        $items[$i]['until'] = $until;
        $found = true;
    }
    if ($found) {
        editor_reserve_store($items);
    }
    return $found;
}

function editor_suffix_of(string $jg, string $feldId): int
{
    $prefix = $jg . '-';
    if (strpos($feldId, $prefix) !== 0) {
        return 0;
    }
    $suffix = substr($feldId, strlen($prefix));
    if (!preg_match('/^\d+$/', $suffix)) {
        return 0;
    }
    return intval($suffix);
}

function editor_catalog_scan_roots(): array
{
    $skipKeys = ['30T' => true, 'M2' => true, '3TA' => true, '3TD' => true, '4T' => true];
    $bindings = editor_catalog_bindings();
    if ($bindings === []) {
        $bindings = [
            '3T' => 'Missionen',
            '4TD' => 'Missionen Matrice 4TD',
            'D' => 'Missionen Debug',
        ];
    }
    $base = function_exists('mapll_rkropen_root') ? mapll_rkropen_root() : dirname(__DIR__, 2);
    $roots = [];
    foreach ($bindings as $key => $loc) {
        if (isset($skipKeys[(string)$key])) {
            continue;
        }
        $loc = trim(str_replace('\\', '/', (string)$loc), '/');
        if ($loc === '' || stripos($loc, 'webdaten') !== false || stripos($loc, 'Intern') !== false) {
            continue;
        }
        $dir = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $loc);
        $json = $dir . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.json';
        $xml = $dir . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml';
        if (!is_file($json) && !is_file($xml)) {
            continue;
        }
        $roots[$loc] = ['json' => $json, 'xml' => $xml];
    }
    return $roots;
}

function editor_ids_in_slice(string $slice, string $re, array &$found): void
{
    if ($slice === '') {
        return;
    }
    $matched = preg_match_all($re, $slice, $m);
    if ($matched === false) {
        if (strlen($slice) < 8000) {
            return;
        }
        $mid = intdiv(strlen($slice), 2);
        editor_ids_in_slice(substr($slice, 0, $mid + 64), $re, $found);
        editor_ids_in_slice(substr($slice, $mid), $re, $found);
        return;
    }
    if (!$matched) {
        return;
    }
    foreach ($m[1] as $id) {
        $id = trim((string)$id);
        if ($id !== '') {
            $found[strtolower($id)] = $id;
        }
    }
}

function editor_ids_in_text(string $raw): array
{
    $found = [];
    $patterns = [
        '/"FeldID"\s*:\s*"([^"\\\\]+)"/',
        '/<FeldID>\s*([^<]+)\s*<\/FeldID>/i',
    ];
    $len = strlen($raw);
    $window = 400000;
    $overlap = 64;
    foreach ($patterns as $re) {
        $offset = 0;
        while ($offset < $len) {
            $sliceLen = min($window, $len - $offset);
            editor_ids_in_slice(substr($raw, $offset, $sliceLen), $re, $found);
            $next = $offset + $sliceLen;
            if ($next < $len) {
                $next = max($offset + 1, $next - $overlap);
            }
            if ($next <= $offset) {
                break;
            }
            $offset = $next;
        }
    }
    return $found;
}

function editor_ids_from_catalog_files(array $entry): array
{
    $found = [];
    foreach (['json', 'xml'] as $kind) {
        $path = (string)($entry[$kind] ?? '');
        if ($path === '' || !is_file($path)) {
            continue;
        }
        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            continue;
        }
        foreach (editor_ids_in_text($raw) as $key => $id) {
            $found[$key] = $id;
        }
    }
    return $found;
}

function editor_foreign_feld_ids(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    $current = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    foreach (editor_catalog_scan_roots() as $loc => $entry) {
        if ($current !== '' && strcasecmp($loc, $current) === 0) {
            continue;
        }
        foreach (editor_ids_from_catalog_files($entry) as $key => $id) {
            $cache[$key] = $id;
        }
    }
    return $cache;
}

function editor_current_disk_feld_ids(): array
{
    static $cache = null;
    $current = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    if ($current === '') {
        return [];
    }
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    foreach (editor_catalog_scan_roots() as $loc => $entry) {
        if (strcasecmp($loc, $current) === 0) {
            $cache = editor_ids_from_catalog_files($entry);
            break;
        }
    }
    return $cache;
}

function editor_foreign_reserve_rows(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    $own = editor_reserve_path();
    $current = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    foreach (editor_catalog_scan_roots() as $loc => $entry) {
        if ($current !== '' && strcasecmp($loc, $current) === 0) {
            continue;
        }
        $reserve = dirname((string)($entry['json'] ?? '')) . DIRECTORY_SEPARATOR . 'RKRKFelder.reserve.json';
        if (strcasecmp($reserve, $own) === 0) {
            continue;
        }
        foreach (editor_reserve_rows_from($reserve) as $row) {
            $cache[] = $row;
        }
    }
    return $cache;
}

function editor_note_feld_suffix(string $jg, string $feldId, array &$used, int &$max): void
{
    $prefix = $jg . '-';
    if (strpos($feldId, $prefix) !== 0) {
        return;
    }
    $suffix = substr($feldId, strlen($prefix));
    if (!preg_match('/^\d+$/', $suffix)) {
        return;
    }
    $n = intval($suffix);
    $used[$n] = true;
    if ($n > $max) {
        $max = $n;
    }
}

function editor_next_feld_id(SimpleXMLElement $xml, string $jg, string $reserveToken = ''): array
{
    $max = 0;
    $used = [];
    $prefix = $jg . '-';
    foreach ($xml->Feld as $f) {
        $id = trim((string)($f->FeldID ?? ''));
        if (strpos($id, $prefix) !== 0) {
            continue;
        }
        $suffix = substr($id, strlen($prefix));
        if (!preg_match('/^\d+$/', $suffix)) {
            continue;
        }
        $n = intval($suffix);
        $used[$n] = true;
        if ($n > $max) {
            $max = $n;
        }
    }
    foreach (editor_reserve_load() as $row) {
        if ($reserveToken !== '' && editor_reserve_token_is((string)$row['token'], $reserveToken)) {
            continue;
        }
        $id = trim((string)$row['FeldID']);
        if (strpos($id, $prefix) !== 0) {
            continue;
        }
        $suffix = substr($id, strlen($prefix));
        if (!preg_match('/^\d+$/', $suffix)) {
            continue;
        }
        $n = intval($suffix);
        $used[$n] = true;
        if ($n > $max) {
            $max = $n;
        }
    }
    foreach (editor_foreign_feld_ids() as $id) {
        editor_note_feld_suffix($jg, $id, $used, $max);
    }
    foreach (editor_current_disk_feld_ids() as $id) {
        editor_note_feld_suffix($jg, $id, $used, $max);
    }
    foreach (editor_foreign_reserve_rows() as $row) {
        editor_note_feld_suffix($jg, (string)$row['FeldID'], $used, $max);
    }
    $next = $max + 1;
    while (isset($used[$next])) {
        $next++;
    }
    return [
        'feld_id' => $jg . '-' . sprintf('%03d', $next),
        'number' => strval($next),
    ];
}

function editor_feld_id_taken(SimpleXMLElement $xml, string $feldId, string $exceptId = '', string $reserveToken = ''): bool
{
    $feldId = trim($feldId);
    if ($feldId === '') {
        return false;
    }
    foreach ($xml->Feld as $f) {
        if ($exceptId !== '' && editor_field_key($f) === $exceptId) {
            continue;
        }
        if (strcasecmp(trim((string)($f->FeldID ?? '')), $feldId) === 0) {
            return true;
        }
    }
    foreach (editor_reserve_load() as $row) {
        if ($reserveToken !== '' && editor_reserve_token_is((string)$row['token'], $reserveToken)) {
            continue;
        }
        if (strcasecmp((string)$row['FeldID'], $feldId) === 0) {
            return true;
        }
    }
    if (isset(editor_foreign_feld_ids()[strtolower($feldId)]) || isset(editor_current_disk_feld_ids()[strtolower($feldId)])) {
        return true;
    }
    foreach (editor_foreign_reserve_rows() as $row) {
        if (strcasecmp((string)$row['FeldID'], $feldId) === 0) {
            return true;
        }
    }
    return false;
}

function editor_catalog_has_id(SimpleXMLElement $xml, string $feldId): bool
{
    $feldId = trim($feldId);
    if ($feldId === '') {
        return false;
    }
    foreach ($xml->Feld as $f) {
        if (strcasecmp(trim((string)($f->FeldID ?? '')), $feldId) === 0) {
            return true;
        }
    }
    $key = strtolower($feldId);
    return isset(editor_foreign_feld_ids()[$key]) || isset(editor_current_disk_feld_ids()[$key]);
}

function editor_is_existing_catalog_edit(SimpleXMLElement $xml, array $data): bool
{
    if (!empty($data['existing'])) {
        return true;
    }
    $id = trim((string)($data['id'] ?? ''));
    if ($id === '') {
        return false;
    }
    foreach ($xml->Feld as $f) {
        if (editor_field_key($f) === $id) {
            return true;
        }
    }
    return false;
}

function editor_reserve_claim(SimpleXMLElement $xml, string $jg, string $token, string $requested): array
{
    $items = editor_reserve_load(true);
    $own = null;
    foreach ($items as $row) {
        if (editor_reserve_token_is((string)$row['token'], $token)) {
            $own = $row;
            break;
        }
    }
    $requested = editor_normalize_feld_id($requested);
    $note = '';
    $kept = false;
    $want = '';
    $requestedSameJg = ($requested !== '' && editor_suffix_of($jg, $requested) > 0);
    if ($requestedSameJg) {
        $blocked = editor_catalog_has_id($xml, $requested);
        if (!$blocked) {
            foreach ($items as $row) {
                if (strcasecmp((string)$row['FeldID'], $requested) !== 0) {
                    continue;
                }
                if (!editor_reserve_token_is((string)$row['token'], $token)) {
                    $blocked = true;
                    break;
                }
            }
        }
        if ($blocked) {
            $kept = $own !== null;
            $note = 'Die Nummer ' . $requested . ' ist schon vergeben. ';
            if ($own !== null) {
                $note .= 'Die bisherige Reservierung ' . $own['FeldID'] . ' bleibt.';
                $want = (string)$own['FeldID'];
            } else {
                $note .= 'Es wird die nächste freie Nummer reserviert.';
            }
        } else {
            $want = $requested;
        }
    }
    if ($want === '' && $own !== null && editor_suffix_of($jg, (string)$own['FeldID']) > 0 && !editor_catalog_has_id($xml, (string)$own['FeldID'])) {
        $want = (string)$own['FeldID'];
    }
    if ($want === '' || editor_catalog_has_id($xml, $want) || (editor_feld_id_taken($xml, $want, '', $token) && !editor_reserve_token_is((string)($own['token'] ?? ''), $token))) {
        if ($want !== '' && editor_feld_id_taken($xml, $want, '', $token)) {
            $kept = $own !== null && strcasecmp((string)$own['FeldID'], $want) === 0;
        }
        $issued = editor_next_feld_id($xml, $jg, $token);
        $want = $issued['feld_id'];
    }
    $keptItems = [];
    foreach ($items as $row) {
        if (editor_reserve_token_is((string)$row['token'], $token)) {
            continue;
        }
        $keptItems[] = $row;
    }
    $keptItems[] = [
        'FeldID' => $want,
        'token' => $token,
        'jg' => $jg,
        'until' => time() + editor_reserve_ttl(),
    ];
    editor_reserve_store($keptItems);
    $number = strval(intval(substr($want, (int)strrpos($want, '-') + 1)));
    return [
        'feld_id' => $want,
        'number' => $number,
        'reserve_note' => $note,
        'reserve_kept' => $kept,
    ];
}

function editor_mission_key_pure(string $hint): string
{
    $compact = strtolower(preg_replace('/[\s_-]+/', '', trim($hint)) ?? '');
    if ($compact === 'd' || strpos($compact, 'debug') !== false) {
        return 'D';
    }
    $map = [
        '3ta' => '3TA',
        'm3ta' => '3TA',
        'mavic3ta' => '3TA',
        'mavicm3ta' => '3TA',
        'matrice3ta' => '3TA',
        '3td' => '3TD',
        'm3td' => '3TD',
        'mavic3td' => '3TD',
        'matrice3td' => '3TD',
        '4td' => '4TD',
        'm4td' => '4TD',
        'matrice4td' => '4TD',
        '4t' => '4T',
        'm4t' => '4T',
        'matrice4t' => '4T',
        'matricem4t' => '4T',
        'd' => 'D',
        '3t' => '3T',
        'm3t' => '3T',
        'mavic3t' => '3T',
    ];
    if (isset($map[$compact])) {
        return $map[$compact];
    }
    $types = $GLOBALS['MissionDroneTypes'] ?? null;
    if (is_array($types)) {
        foreach ($types as $key => $cfg) {
            if (!is_array($cfg)) {
                continue;
            }
            if (strcasecmp((string)$key, trim($hint)) === 0) {
                return (string)$key;
            }
            $name = strtolower(preg_replace('/[\s_-]+/', '', (string)($cfg['Name'] ?? '')) ?? '');
            if ($name !== '' && $name === $compact) {
                return (string)$key;
            }
        }
    }
    foreach (editor_load_drone_settings() as $key => $cfg) {
        if (!is_array($cfg) || empty($cfg['editor'])) {
            continue;
        }
        $k = strtolower(preg_replace('/[\s_-]+/', '', (string)$key) ?? '');
        $name = strtolower(preg_replace('/[\s_-]+/', '', (string)($cfg['Name'] ?? '')) ?? '');
        if ($compact !== '' && ($compact === $k || $compact === $name)) {
            return (string)$key;
        }
    }
    return '';
}

function editor_load_drone_settings(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    if (isset($GLOBALS['DroneSettings']) && is_array($GLOBALS['DroneSettings'])) {
        $cache = $GLOBALS['DroneSettings'];
        return $cache;
    }
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'rkrk_dji_kmz_interface.php';
    if (!is_file($path)) {
        return $cache;
    }
    ob_start();
    include_once $path;
    ob_end_clean();
    if (isset($DroneSettings) && is_array($DroneSettings)) {
        $cache = $DroneSettings;
        $GLOBALS['DroneSettings'] = $DroneSettings;
    }
    return $cache;
}

function editor_drone_profiles(): array
{
    $out = [];
    foreach (editor_load_drone_settings() as $key => $cfg) {
        if (!is_array($cfg) || empty($cfg['editor'])) {
            continue;
        }
        $name = trim((string)($cfg['Name'] ?? $key));
        $out[] = [
            'key' => (string)$key,
            'name' => $name !== '' ? $name : (string)$key,
            'uav' => trim((string)($cfg['UAVDroneType'] ?? '')),
            'img' => trim((string)($cfg['img'] ?? '')),
        ];
    }
    return $out;
}

function editor_catalog_extra_path(): string
{
    return mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'rkrk' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'catalogs_extra.json';
}

function editor_catalog_extra_read(): array
{
    $path = editor_catalog_extra_path();
    if (!is_file($path)) {
        return ['enabled' => [], 'added' => []];
    }
    $raw = file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        return ['enabled' => [], 'added' => []];
    }
    $enabled = [];
    foreach ((array)($data['enabled'] ?? []) as $key) {
        $key = trim((string)$key);
        if ($key !== '') {
            $enabled[] = $key;
        }
    }
    $added = (isset($data['added']) && is_array($data['added'])) ? $data['added'] : [];
    return ['enabled' => $enabled, 'added' => $added];
}

function editor_catalog_extra_write(array $data): void
{
    $path = editor_catalog_extra_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail(500, 'catalog_create_failed', 'Konfigurationsordner fehlt');
    }
    $json = json_encode([
        'enabled' => array_values(array_unique(array_map('strval', (array)($data['enabled'] ?? [])))),
        'added' => (isset($data['added']) && is_array($data['added'])) ? $data['added'] : [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json) || file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        fail(500, 'catalog_create_failed', 'Die Katalogliste konnte nicht gespeichert werden');
    }
}

function editor_catalog_builtin_keys(): array
{
    return ['3T', '4TD', 'D'];
}

function editor_catalog_bindings(): array
{
    editor_load_name_tables();
    $types = is_array($GLOBALS['MissionDroneTypes'] ?? null) ? $GLOBALS['MissionDroneTypes'] : [];
    $extra = editor_catalog_extra_read();
    $out = [];
    foreach (editor_catalog_builtin_keys() as $key) {
        $loc = trim(str_replace('\\', '/', (string)($types[$key]['MissionLocation'] ?? '')), '/');
        if ($loc !== '') {
            $out[$key] = $loc;
        }
    }
    foreach ($extra['added'] as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $loc = trim(str_replace('\\', '/', (string)($row['MissionLocation'] ?? '')), '/');
        if ($key !== '' && $loc !== '' && function_exists('rkrk_config_catalog_location_ok') && rkrk_config_catalog_location_ok($loc . '/')) {
            $out[(string)$key] = $loc;
        }
    }
    foreach ($extra['enabled'] as $key) {
        if (isset($out[$key])) {
            continue;
        }
        $loc = trim(str_replace('\\', '/', (string)($types[$key]['MissionLocation'] ?? '')), '/');
        if ($loc !== '') {
            $out[$key] = $loc;
        }
    }
    return $out;
}

function editor_catalog_json_file(string $location): string
{
    $loc = trim(str_replace('\\', '/', $location), '/');
    return mapll_rkropen_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $loc)
        . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.json';
}

function editor_catalog_is_ready(string $key): bool
{
    $bindings = editor_catalog_bindings();
    if (!isset($bindings[$key])) {
        return false;
    }
    $file = editor_catalog_json_file($bindings[$key]);
    return function_exists('rkrk_feldkatalog_file_complete') && rkrk_feldkatalog_file_complete($file);
}

function editor_drone_key_known(string $key): bool
{
    if ($key === '') {
        return false;
    }
    foreach (editor_drone_profiles() as $profile) {
        if ($profile['key'] === $key) {
            return true;
        }
    }
    $types = is_array($GLOBALS['MissionDroneTypes'] ?? null) ? $GLOBALS['MissionDroneTypes'] : [];
    return isset($types[$key]);
}

function editor_is_admin(): bool
{
    if (array_key_exists('ADMIN', $_GET)) {
        return true;
    }
    return strcasecmp(trim((string)($_GET['code'] ?? '')), 'ADMIN') === 0;
}

function editor_catalog_can_add(string $key): bool
{
    return false;
}

function editor_catalog_profile(string $key): ?array
{
    foreach (editor_drone_profiles() as $profile) {
        if ($profile['key'] === $key) {
            return $profile;
        }
    }
    return null;
}

function editor_catalog_folder_for(string $key): string
{
    $bindings = editor_catalog_bindings();
    if (isset($bindings[$key])) {
        return $bindings[$key];
    }
    $types = is_array($GLOBALS['MissionDroneTypes'] ?? null) ? $GLOBALS['MissionDroneTypes'] : [];
    $loc = trim(str_replace('\\', '/', (string)($types[$key]['MissionLocation'] ?? '')), '/');
    if ($loc !== '') {
        return $loc;
    }
    $profile = editor_catalog_profile($key);
    $name = trim((string)($profile['name'] ?? ''));
    return $name !== '' ? ('Missionen ' . $name) : '';
}

function editor_catalog_missing_detail(string $key): string
{
    $profile = editor_catalog_profile($key);
    $name = trim((string)($profile['name'] ?? $key));
    if ($key === 'M2') {
        return 'Der Feldkatalog der Mavic II ist entfernt.';
    }
    if (in_array($key, ['30T', '4T', '3TD', '3TA'], true)) {
        return $name . ' hat keinen Feldkatalog. Ein neuer Katalog wird nur in der Website-Konfiguration unter Missionsbibliotheken angelegt.';
    }
    return 'Für ' . $name . ' ist kein Feldkatalog hinterlegt.';
}

function editor_catalog_overview(): array
{
    $rows = [];
    foreach (editor_drone_profiles() as $profile) {
        $key = (string)$profile['key'];
        $ready = editor_catalog_is_ready($key);
        $rows[] = [
            'key' => $key,
            'name' => (string)$profile['name'],
            'uav' => (string)$profile['uav'],
            'catalog' => $ready,
            'can_add' => editor_catalog_can_add($key),
            'folder' => editor_catalog_folder_for($key),
        ];
    }
    return ['ok' => true, 'catalogs' => $rows];
}

function editor_catalog_button_for(string $key): string
{
    $map = [
        '4T' => 'matrice4T_button.png',
        '4TD' => 'matrice4TD_button.png',
        '30T' => 'matrice30T_button.png',
        'D' => 'debug_button.png',
    ];
    return $map[$key] ?? 'mavic3t_button.png';
}

function editor_create_catalog(string $key): array
{
    if (!array_key_exists('KAT', $_GET)) {
        fail(403, 'kat_required', 'Einen Katalog anlegen braucht ?KAT.');
    }
    if (!editor_is_admin()) {
        fail(403, 'admin_required', 'Einen Feldkatalog anlegen und das Speichern freischalten kann nur der Admin.');
    }
    $key = editor_mission_key_pure($key);
    if ($key === '' || $key === 'M2') {
        fail(409, 'catalog_refused', 'Für diesen Drohnentyp wird kein Feldkatalog angelegt.');
    }
    if (in_array($key, editor_catalog_builtin_keys(), true) || editor_catalog_is_ready($key)) {
        fail(409, 'catalog_exists', 'Dieser Feldkatalog ist schon vorhanden.');
    }
    if (!editor_catalog_can_add($key)) {
        fail(409, 'catalog_refused', editor_catalog_missing_detail($key));
    }
    $profile = editor_catalog_profile($key);
    $folder = editor_catalog_folder_for($key);
    if ($profile === null || $folder === '' || !function_exists('rkrk_config_catalog_location_ok') || !rkrk_config_catalog_location_ok($folder . '/')) {
        fail(422, 'catalog_folder', 'Der Katalogordner ist nicht zulässig.');
    }
    $root = mapll_rkropen_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
    $jsonFile = $root . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.json';
    if (is_file($jsonFile)) {
        fail(409, 'catalog_exists', 'In diesem Ordner liegt schon eine Katalogdatei.');
    }
    foreach (['jsonfiles', 'xmlfiles', 'csvfiles'] as $dirName) {
        $dir = $root . DIRECTORY_SEPARATOR . $dirName;
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            fail(500, 'catalog_create_failed', 'Der Katalogordner konnte nicht angelegt werden.');
        }
    }
    $body = json_encode([
        'schema' => 'rkrk-feldkatalog',
        'version' => 1,
        'root' => 'Felder',
        'fields' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($body) || file_put_contents($jsonFile, $body . "\n", LOCK_EX) === false) {
        fail(500, 'catalog_create_failed', 'Der leere Feldkatalog konnte nicht geschrieben werden.');
    }
    $extra = editor_catalog_extra_read();
    $types = is_array($GLOBALS['MissionDroneTypes'] ?? null) ? $GLOBALS['MissionDroneTypes'] : [];
    if (!isset($types[$key])) {
        $button = editor_catalog_button_for($key);
        $extra['added'][$key] = [
            'Name' => (string)$profile['name'],
            'MissionLocation' => $folder . '/',
            'button' => $button,
            'button_orange' => $button,
            'img' => (string)$profile['img'],
        ];
        if (!isset($types[$key])) {
            $GLOBALS['MissionDroneTypes'][$key] = [
                'Name' => (string)$profile['name'],
                'MissionLocation' => $folder . '/',
                'DocLocation' => 'Missionen/',
                'active' => true,
                'release' => 'test, intern',
                'DataFile' => 'SuchfeldBild,FlugplanBild,JasonFile,KMZFile',
                'button' => $button,
                'button_orange' => $button,
                'img' => (string)$profile['img'],
            ];
        }
    } else {
        $extra['enabled'][] = $key;
    }
    editor_catalog_extra_write($extra);
    return [
        'ok' => true,
        'mode' => 'create_catalog',
        'key' => $key,
        'name' => (string)$profile['name'],
        'folder' => $folder,
        'catalog' => editor_catalog_is_ready($key),
    ];
}

function editor_mark_catalog_absent(string $key): void
{
    $GLOBALS['editor_catalog_absent'] = true;
    $GLOBALS['RKRKMissionSelected'] = $key;
    $GLOBALS['MissionLocation'] = '';
    $_GET['MDB'] = $key;
}

function editor_use_mission(string $hint): void
{
    $key = editor_mission_key_pure($hint);
    if ($key === '') {
        $key = '3T';
    }
    $genKeys = ['3T', '4TD', 'D'];
    $extra = editor_catalog_extra_read();
    $safe = $key;
    if (!in_array($key, $genKeys, true) && !isset($extra['added'][$key])) {
        $safe = '3T';
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $sessionKey = 'MissionDBSetting';
    $sessionBackup = array_key_exists($sessionKey, $_SESSION) ? $_SESSION[$sessionKey] : null;
    $_GET['MDB'] = $safe;
    editor_load_name_tables();
    if ($sessionBackup !== null && !is_string($sessionBackup)) {
        $_SESSION[$sessionKey] = $sessionBackup;
    } elseif ($safe !== $key && $sessionBackup !== null) {
        $_SESSION[$sessionKey] = $sessionBackup;
    }
    $_GET['MDB'] = $key;
    if (!editor_catalog_is_ready($key)) {
        if (editor_drone_key_known($key)) {
            editor_mark_catalog_absent($key);
            return;
        }
        $key = '3T';
        $_GET['MDB'] = $key;
        if (!editor_catalog_is_ready($key)) {
            editor_mark_catalog_absent($key);
            return;
        }
    }
    $bindings = editor_catalog_bindings();
    $loc = trim((string)($bindings[$key] ?? ''), '/');
    if ($loc === '' || stripos($loc, 'webdaten') !== false) {
        $loc = ($key === '4TD') ? 'Missionen Matrice 4TD' : 'Missionen';
    }
    $GLOBALS['editor_catalog_absent'] = false;
    $GLOBALS['RKRKMissionSelected'] = $key;
    $GLOBALS['MissionLocation'] = $loc . '/';
    editor_bind_mission_catalog();
}

function editor_normalize_feld_id(string $raw): string
{
    $raw = trim($raw);
    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $raw, $m)) {
        return $m[1] . '-' . sprintf('%03d', intval($m[2]));
    }
    return $raw;
}

function editor_region_rel_dir(string $jg): string
{
    $regions = $GLOBALS['RegionSettings'] ?? null;
    $jg = trim($jg);
    if ($jg === '' || !is_array($regions)) {
        return '';
    }
    if (isset($regions[$jg]) && is_array($regions[$jg])) {
        return trim(str_replace('\\', '/', (string)($regions[$jg]['Dir'] ?? '')), '/');
    }
    foreach ($regions as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $code = trim((string)($row['Reg'] ?? $key));
        if (strcasecmp($code, $jg) === 0 || strcasecmp((string)$key, $jg) === 0) {
            return trim(str_replace('\\', '/', (string)($row['Dir'] ?? '')), '/');
        }
    }
    return '';
}

function editor_feld_id_place(SimpleXMLElement $xml, string $feldId): array
{
    $feldId = editor_normalize_feld_id(trim($feldId));
    $jg = '';
    if (preg_match('/^([^\-\s]+)\s*-/u', $feldId, $m)) {
        $jg = trim((string)$m[1]);
    }
    $mission = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    if ($mission === '' || stripos($mission, 'webdaten') !== false) {
        $mission = editor_catalog_folder_for((string)($GLOBALS['RKRKMissionSelected'] ?? ''));
    }
    $relDir = editor_region_rel_dir($jg);
    $directory = ($mission !== '' && $relDir !== '') ? ($mission . '/' . $relDir) : '';
    $taken = false;
    $name = '';
    $hasCatalog = empty($GLOBALS['editor_catalog_absent']);
    if ($feldId !== '' && $hasCatalog) {
        foreach ($xml->Feld as $f) {
            if (strcasecmp(trim((string)($f->FeldID ?? '')), $feldId) !== 0) {
                continue;
            }
            $taken = true;
            $name = trim((string)($f->Feldname ?? ''));
            break;
        }
    }
    $state = ($feldId !== '' && $hasCatalog) ? ($taken ? 'vergeben' : 'frei') : '';
    return [
        'ok' => true,
        'feld_id' => $feldId,
        'jg' => $jg,
        'directory' => $directory,
        'free' => $state === 'frei',
        'state' => $state,
        'name' => $name,
    ];
}

function editor_feld_id_claim(SimpleXMLElement $xml, string $feldId, string $exceptId, bool $confirm, string $reserveToken = ''): void
{
    $feldId = trim($feldId);
    if ($feldId === '') {
        return;
    }
    foreach (array_merge(editor_reserve_load(), editor_foreign_reserve_rows()) as $row) {
        if (strcasecmp((string)$row['FeldID'], $feldId) !== 0) {
            continue;
        }
        if ($reserveToken !== '' && editor_reserve_token_is((string)$row['token'], $reserveToken)) {
            continue;
        }
        fail(409, 'feld_id_reserved', 'Feldkatalognummer ' . $feldId . ' ist reserviert.');
    }
    if (isset(editor_foreign_feld_ids()[strtolower($feldId)])) {
        $ownsForeign = false;
        if ($exceptId !== '') {
            foreach ($xml->Feld as $f) {
                if (editor_field_key($f) === $exceptId && strcasecmp(trim((string)($f->FeldID ?? '')), $feldId) === 0) {
                    $ownsForeign = true;
                    break;
                }
            }
        }
        if (!$ownsForeign) {
            fail(409, 'feld_id_exists', 'Feldkatalognummer ' . $feldId . ' ist in einem anderen Drohnenkatalog schon vergeben.');
        }
    }
    foreach ($xml->Feld as $f) {
        if ($exceptId !== '' && editor_field_key($f) === $exceptId) {
            continue;
        }
        if (strcasecmp(trim((string)($f->FeldID ?? '')), $feldId) !== 0) {
            continue;
        }
        if ($confirm) {
            return;
        }
        $name = trim((string)($f->Feldname ?? ''));
        $who = $name !== '' ? ($feldId . ' (' . $name . ')') : $feldId;
        fail(409, 'feld_id_exists', 'Feldkatalognummer ' . $who . ' ist schon vergeben.');
    }
}

function editor_catalog_file_abs(string $rel): string
{
    global $MissionLocation;
    $rel = trim(str_replace('\\', '/', $rel), '/');
    $mission = trim(str_replace('\\', '/', (string)($MissionLocation ?: 'Missionen')), '/');
    if ($mission === '') {
        $mission = 'Missionen';
    }
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $mission . '/' . $rel);
}

function editor_rename_catalog_files(SimpleXMLElement $node, string $oldId, string $newId): void
{
    $oldId = trim($oldId);
    $newId = trim($newId);
    if ($oldId === '' || $newId === '' || strcasecmp($oldId, $newId) === 0) {
        return;
    }
    $oldToken = editor_mission_file_token($oldId);
    $newToken = editor_mission_file_token($newId);
    if ($oldToken === '' || $newToken === '' || strcasecmp($oldToken, $newToken) === 0) {
        return;
    }
    $prefix = $oldToken . '_';
    foreach (['JasonFile', 'KMZFile', 'SuchfeldBild', 'FlugplanBild', 'KMLFile', 'LichtiFile'] as $key) {
        $rel = trim(str_replace('\\', '/', (string)($node->{$key} ?? '')));
        if ($rel === '') {
            continue;
        }
        $base = basename($rel);
        if (strncasecmp($base, $prefix, strlen($prefix)) !== 0) {
            continue;
        }
        $newBase = $newToken . substr($base, strlen($oldToken));
        $dir = dirname($rel);
        if ($dir === '.' || $dir === '') {
            $dir = '';
        }
        if ($key === 'JasonFile' || $key === 'KMZFile') {
            $newRel = rkrk_mission_field_file_rel($dir, $oldId, $newBase);
        } else {
            $newRel = ($dir === '') ? $newBase : ($dir . '/' . $newBase);
        }
        if (strcasecmp($rel, $newRel) === 0) {
            continue;
        }
        $absOld = editor_catalog_file_abs($rel);
        $absNew = editor_catalog_file_abs($newRel);
        if (is_file($absOld)) {
            if (is_file($absNew)) {
                fail(409, 'file_exists', 'Datei ' . $newBase . ' gibt es schon. Die Nummer wurde nicht geändert.');
            }
            $newDir = dirname($absNew);
            if (!is_dir($newDir) && !mkdir($newDir, 0777, true) && !is_dir($newDir)) {
                fail(500, 'mkdir_failed', 'Zielordner für ' . $newBase . ' fehlt');
            }
            if (!@rename($absOld, $absNew)) {
                fail(500, 'rename_failed', 'Datei konnte nicht umbenannt werden: ' . $base);
            }
        } elseif (!is_file($absNew)) {
            continue;
        }
        ensure_child($node, $key, $newRel);
    }
}

function editor_mission_file_token(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $value) ?? '';
    return trim($value, '_');
}

function editor_load_mission_export_lib(): void
{
    if (function_exists('build_uav_json_payload')) {
        return;
    }
    if (!defined('RKRK_MISSION_EXPORT_AS_LIB')) {
        define('RKRK_MISSION_EXPORT_AS_LIB', true);
    }
    require_once __DIR__ . '/api_mission_export.php';
}

function editor_region_mission_dir(string $relDir): string
{
    global $MissionLocation;
    $relDir = trim(str_replace('\\', '/', $relDir), '/');
    $mission = trim(str_replace('\\', '/', (string)($MissionLocation ?: 'Missionen/')), '/');
    if ($mission === '') {
        $mission = 'Missionen';
    }
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $mission . '/' . $relDir);
}

function editor_write_mission_files(string $jg, string $feldId, string $name, string $height, array $poly, array $start, array $path, array $missionSettings = [], string $flugzeit = '0', string $distanz = '0', string $gis = ''): array
{
    global $RegionSettings;
    editor_load_mission_export_lib();
    if (!function_exists('build_uav_json_payload')) {
        fail(500, 'mission_export_missing', 'UAV-Missionsformat ist nicht geladen');
    }
    if (!isset($RegionSettings[$jg]) || !is_array($RegionSettings[$jg])) {
        fail(422, 'unknown_jg', 'Jagdgebiet ' . $jg . ' ist in den Regionsdaten nicht vorhanden');
    }
    $relDir = trim(str_replace('\\', '/', (string)($RegionSettings[$jg]['Dir'] ?? '')), '/');
    if ($relDir === '') {
        fail(422, 'missing_region_dir', 'Für Jagdgebiet ' . $jg . ' ist kein Missionsordner hinterlegt');
    }
    $fieldRel = rkrk_mission_field_rel_dir($relDir, $feldId);
    $absDir = editor_region_mission_dir($fieldRel);
    if (!is_dir($absDir) && !mkdir($absDir, 0777, true) && !is_dir($absDir)) {
        fail(500, 'mkdir_failed', 'Missionsordner konnte nicht erstellt werden: ' . $absDir);
    }
    $heightNum = preg_replace('/[^0-9.]+/', '', $height) ?? '';
    if ($heightNum === '') {
        $heightNum = '60';
    }
    $groupName = trim((string)($RegionSettings[$jg]['GroupAreaName'] ?? $jg));
    $terrainDelta = is_array($missionSettings['terrain_delta_by_lonlat'] ?? null) ? $missionSettings['terrain_delta_by_lonlat'] : [];
    unset($missionSettings['terrain_delta_by_lonlat']);
    if (function_exists('normalize_mission_settings')) {
        $missionSettings = normalize_mission_settings($missionSettings);
    }
    if (!isset($missionSettings['altitude_m']) || !is_numeric($missionSettings['altitude_m'])) {
        $missionSettings['altitude_m'] = floatval($heightNum);
    }
    $gis = trim($gis);
    if ($gis === '') {
        $gis = rkrk_catalog_grundstueck_id($feldId);
    }
    $nameParts = rkrk_mission_name_parts_from_field([
        'feld_id' => $feldId,
        'name' => $name,
        'grundstueck_id' => $gis,
        'std_flughoehe' => (string)$missionSettings['altitude_m'],
    ], $missionSettings);
    $file = $nameParts['complete'] . '.json';
    $kmzName = $nameParts['complete'] . '.kmz';
    $rel = $fieldRel . '/' . $file;
    $abs = $absDir . DIRECTORY_SEPARATOR . $file;
    $meta = [
        'feld_id' => $feldId,
        'jg' => $jg,
        'name' => $name,
        'std_agl_m' => floatval($heightNum),
        'mission_settings' => $missionSettings,
        'start_lonlat' => $start,
        'mission_name_parts' => $nameParts,
        'terrain_delta_by_lonlat' => $terrainDelta,
    ];
    $payload = build_uav_json_payload($meta, $path, $poly, $start);
    $payload['fileName'] = $nameParts['feldname'];
    $payload['name'] = $nameParts['feldname'];
    $payload['originalName'] = $nameParts['feldname'];
    $payload['fileNameComplete'] = $nameParts['complete'];
    $payload['fileNameCompleteForExport'] = $nameParts['complete'];
    $payload['pilotGroupAreaName'] = $groupName;
    $payload['catalogIdentifier'] = $feldId;
    $minutes = floatval(str_replace(',', '.', $flugzeit));
    $payload['flightTime'] = $minutes > 0 ? $minutes * 60.0 : floatval($payload['flightTime'] ?? 0);
    $payload['flightDistance'] = floatval(str_replace(',', '.', $distanz));
    if (!isset($payload['data']) || !is_array($payload['data'])) {
        $payload['data'] = [];
    }
    $owner = [
        'firstname' => '',
        'name' => '',
        'address' => ['formatted' => '', 'zip' => '', 'city' => ''],
        'phone' => '',
        'email' => '',
        'notice' => '',
    ];
    if (isset($payload['data']['owner']) && is_array($payload['data']['owner'])) {
        $owner = array_replace($owner, $payload['data']['owner']);
    }
    $payload['data']['owner'] = $owner;
    if (!isset($payload['data']['zoneId'])) {
        $payload['data']['zoneId'] = '';
    }
    if (!isset($payload['contributor']) || !is_array($payload['contributor'])) {
        $payload['contributor'] = [];
    }
    if (!isset($payload['contributor']['notice'])) {
        $payload['contributor']['notice'] = '';
    }
    $jsonWasThere = is_file($abs);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json) || file_put_contents($abs, $json) === false) {
        fail(500, 'mission_write_failed', 'Missionsdatei konnte nicht geschrieben werden: ' . $abs);
    }
    $kmzRel = '';
    $kmzAbs = $absDir . DIRECTORY_SEPARATOR . $kmzName;
    $kmzError = '';
    try {
        if (!class_exists('ZipArchive') || !function_exists('pack_dji_pilot2_kmz_file') || !function_exists('build_dji_pilot2_export_context')) {
            $kmzError = 'KMZ-Export ist auf dem Server nicht verfügbar.';
        } else {
            if (function_exists('ensure_dji_kmz_reference_loaded')) {
                ensure_dji_kmz_reference_loaded();
            }
            $kmzSettings = function_exists('effective_kmz_settings') ? effective_kmz_settings($missionSettings) : $missionSettings;
            $agl = function_exists('clamp_export_agl_m')
                ? clamp_export_agl_m(floatval($kmzSettings['altitude_m'] ?? $heightNum))
                : floatval($heightNum);
            $exportPath = (count($start) >= 2 && function_exists('ensure_start_point_first'))
                ? ensure_start_point_first($path, $start, $agl)
                : $path;
            $ctx = build_dji_pilot2_export_context($meta, $exportPath, $kmzSettings);
            if (empty($ctx['waypoints']) || count($ctx['waypoints']) < 2) {
                $kmzError = 'KMZ braucht mindestens 2 Wegpunkte.';
            } elseif (!pack_dji_pilot2_kmz_file($kmzAbs, build_dji_wpml_template_kml($ctx), build_dji_wpml_waylines_wpml($ctx))) {
                $kmzError = 'KMZ konnte nicht geschrieben werden: ' . $kmzAbs;
            } else {
                $kmzRel = $fieldRel . '/' . $kmzName;
            }
        }
    } catch (Throwable $e) {
        $kmzError = $e->getMessage() !== '' ? $e->getMessage() : 'KMZ-Export fehlgeschlagen.';
        $kmzRel = '';
    }
    if ($kmzError !== '') {
        if (!$jsonWasThere && is_file($abs)) {
            @unlink($abs);
        }
        if (is_file($kmzAbs) && $kmzRel === '') {
            @unlink($kmzAbs);
        }
        fail(500, 'kmz_export_failed', $kmzError . ' Der Feldkatalog wurde nicht gespeichert.');
    }
    return [
        'relative' => $rel,
        'absolute' => $abs,
        'kmz_relative' => $kmzRel,
        'kmz_absolute' => $kmzRel !== '' ? $kmzAbs : '',
        'group_name' => $groupName,
    ];
}

function editor_write_mission_json(string $jg, string $feldId, string $name, string $gemeinde, string $height, array $poly, array $start, array $path, string $gis = '', array $missionSettings = []): array
{
    unset($gemeinde);
    return editor_write_mission_files($jg, $feldId, $name, $height, $poly, $start, $path, $missionSettings, '0', '0', $gis);
}

function editor_attach_mission_files(SimpleXMLElement $node, string $jg, string $feldId, string $name, string $height, array $poly, array $start, array $path, array $data, string $flugzeit, string $distanz, string $gis): array
{
    $mission = null;
    $missionFile = '';
    $kmzFile = '';
    if ($jg === '') {
        fail(422, 'missing_jg', 'JSON und KMZ brauchen ein Jagdgebiet. Der Feldkatalog wurde nicht gespeichert.');
    }
    if ($jg !== '') {
        $postedSettings = is_array($data['mission_settings'] ?? null) ? $data['mission_settings'] : [];
        if (is_array($data['terrain_delta_by_lonlat'] ?? null)) {
            $postedSettings['terrain_delta_by_lonlat'] = $data['terrain_delta_by_lonlat'];
        }
        if (!array_key_exists('kmz_params_active', $postedSettings)) {
            $postedSettings['kmz_params_active'] = true;
        }
        $mission = editor_write_mission_files($jg, $feldId, $name, $height, $poly, $start, $path, $postedSettings, $flugzeit, $distanz, $gis);
        $missionFile = (string)($mission['relative'] ?? '');
        $kmzFile = (string)($mission['kmz_relative'] ?? '');
    }
    if ($missionFile !== '') {
        ensure_child($node, 'JasonFile', $missionFile);
    }
    if ($kmzFile !== '') {
        ensure_child($node, 'KMZFile', $kmzFile);
    }
    return [
        'mission' => $mission,
        'jason_file' => $missionFile,
        'kmz_file' => $kmzFile,
    ];
}

function editor_mission_catalog_root(): string
{
    $mission = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    if ($mission === '') {
        $mission = 'Missionen';
    }
    return mapll_rkropen_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $mission);
}

function editor_catalog_csv_dir(string $root): string
{
    $primary = $root . DIRECTORY_SEPARATOR . 'csvfiles' . DIRECTORY_SEPARATOR;
    if (is_dir($primary)) {
        return $primary;
    }
    $alt = $root . DIRECTORY_SEPARATOR . 'csvfiles (not used)' . DIRECTORY_SEPARATOR;
    if (is_dir($alt)) {
        return $alt;
    }
    return $primary;
}

function editor_bind_mission_catalog(): void
{
    $mission = trim(str_replace('\\', '/', (string)($GLOBALS['MissionLocation'] ?? '')), '/');
    if ($mission === '' || stripos($mission, 'webdaten') !== false) {
        $mission = 'Missionen';
        $GLOBALS['MissionLocation'] = 'Missionen/';
    }
    $root = editor_mission_catalog_root();
    $jsonDir = $root . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR;
    $xmlDir = $root . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR;
    $csvDir = editor_catalog_csv_dir($root);
    $GLOBALS['feldkatalogJsonLocation'] = $jsonDir;
    $GLOBALS['feldkatalogCsvLocation'] = $csvDir;
    $GLOBALS['xmlMissionLocation'] = $xmlDir;
    $GLOBALS['xmlFileDirectory'] = 'xmlfiles/';
    $GLOBALS['jsonFileDirectory'] = 'jsonfiles/';
    $GLOBALS['jsonMissionLocation'] = $jsonDir;
    $GLOBALS['RKRKFelderfileXML'] = 'RKRKFelder.xml';
}

function editor_mission_catalog_xml(): string
{
    editor_bind_mission_catalog();
    $root = editor_mission_catalog_root();
    return $root . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml';
}

function editor_prepare_catalog_save(): void
{
    if (!function_exists('TransferFeldKatFromXMLtoArr')) {
        $scripts = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts';
        $cwd = getcwd();
        chdir($scripts);
        ob_start();
        include_once $scripts . DIRECTORY_SEPARATOR . 'rkrk_felddaten.php';
        ob_end_clean();
        if (is_string($cwd) && $cwd !== '') {
            chdir($cwd);
        }
    }
    editor_bind_mission_catalog();
}

function editor_catalog_lock()
{
    editor_bind_mission_catalog();
    $dir = rtrim((string)($GLOBALS['feldkatalogJsonLocation'] ?? ''), "/\\");
    if ($dir === '' || !is_dir($dir)) {
        fail(500, 'catalog_lock', 'Feldkatalogordner fehlt');
    }
    $fh = fopen($dir . DIRECTORY_SEPARATOR . 'RKRKFelder.lock', 'c+');
    if ($fh === false || !flock($fh, LOCK_EX)) {
        if (is_resource($fh)) {
            fclose($fh);
        }
        fail(500, 'catalog_lock', 'Feldkatalog ist gerade gesperrt');
    }
    register_shutdown_function(static function () use ($fh): void {
        if (is_resource($fh)) {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    });
    return $fh;
}

function editor_reload_catalog(string $xmlPath): SimpleXMLElement
{
    editor_bind_mission_catalog();
    $xml = function_exists('rkrk_catalog_load_simplexml')
        ? rkrk_catalog_load_simplexml($xmlPath)
        : false;
    if (!($xml instanceof SimpleXMLElement) && is_file($xmlPath) && (!function_exists('rkrk_feldkatalog_file_complete') || rkrk_feldkatalog_file_complete($xmlPath))) {
        $xml = simplexml_load_file($xmlPath);
    }
    if (!($xml instanceof SimpleXMLElement)) {
        fail(500, 'catalog_missing', 'Feldkatalog nicht vorhanden');
    }
    return $xml;
}

function editor_catalog_target(): array
{
    editor_bind_mission_catalog();
    $root = editor_mission_catalog_root();
    $jsonDir = $root . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR;
    $xmlDir = $root . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR;
    $csvDir = editor_catalog_csv_dir($root);
    return [
        'mission' => (string)($GLOBALS['RKRKMissionSelected'] ?? ''),
        'mission_location' => (string)($GLOBALS['MissionLocation'] ?? ''),
        'root' => $root,
        'json_dir' => $jsonDir,
        'xml_dir' => $xmlDir,
        'csv_dir' => $csvDir,
        'json' => $jsonDir . 'RKRKFelder.json',
        'xml' => $xmlDir . 'RKRKFelder.xml',
        'csv' => $csvDir . 'RKRKFelder.csv',
    ];
}

function editor_save_catalog_compatible(SimpleXMLElement $xml): bool
{
    $GLOBALS['editorCatalogSaveError'] = '';
    editor_prepare_catalog_save();
    editor_bind_mission_catalog();
    if (!function_exists('SaveFlugkatalog') || !function_exists('TransferFeldKatFromXMLtoArr')) {
        $GLOBALS['editorCatalogSaveError'] = 'SaveFlugkatalog fehlt';
        return false;
    }
    $target = editor_catalog_target();
    $root = $target['root'];
    if ($root === '' || !is_dir($root)) {
        $GLOBALS['editorCatalogSaveError'] = 'Katalogordner fehlt';
        return false;
    }
    $xmlDir = $target['xml_dir'];
    $csvDir = $target['csv_dir'];
    $jsonDir = $target['json_dir'];
    if (!is_dir($xmlDir) || !is_dir($jsonDir)) {
        $GLOBALS['editorCatalogSaveError'] = 'jsonfiles oder xmlfiles fehlt';
        return false;
    }
    $GLOBALS['xmlMissionLocation'] = $xmlDir;
    $GLOBALS['feldkatalogJsonLocation'] = $jsonDir;
    $GLOBALS['feldkatalogCsvLocation'] = $csvDir;
    $GLOBALS['jsonFileDirectory'] = 'jsonfiles/';
    $GLOBALS['jsonMissionLocation'] = $jsonDir;
    $GLOBALS['RKRKFelderfileXML'] = 'RKRKFelder.xml';
    $GLOBALS['historyfilelocation'] = $root . DIRECTORY_SEPARATOR . '_history' . DIRECTORY_SEPARATOR;
    if (!isset($GLOBALS['FlugzeitTreshold'])) {
        $GLOBALS['FlugzeitTreshold'] = 25;
    }
    $optionJaeger = [];
    $max = 0;
    $regions = is_array($GLOBALS['EnabledRegion'] ?? null) ? $GLOBALS['EnabledRegion'] : [];
    ob_start();
    $kat = TransferFeldKatFromXMLtoArr($xml, $regions, $optionJaeger, $max);
    if (!is_array($kat) || ($kat === [] && count($xml->Feld) > 0)) {
        ob_end_clean();
        $GLOBALS['editorCatalogSaveError'] = 'Transfer liefert []';
        return false;
    }
    foreach ($xml->Feld as $f) {
        $jg = trim((string)($f->JG ?? ''));
        $id = trim((string)($f->FeldID ?? ''));
        $ms = trim((string)($f->MissionSettings ?? ''));
        if ($jg === '' || $id === '' || $ms === '' || !isset($kat[$jg][$id]) || !is_array($kat[$jg][$id])) {
            continue;
        }
        $kat[$jg][$id]['MissionSettings'] = $ms;
    }
    SaveFlugkatalog($kat, true, $xmlDir);
    if (function_exists('SaveFlugkatalog2CSV')) {
        SaveFlugkatalog2CSV($kat, $csvDir);
    }
    ob_end_clean();
    $jsonPath = rtrim($jsonDir, "/\\") . DIRECTORY_SEPARATOR . 'RKRKFelder.json';
    if (is_file($jsonPath) && function_exists('rkrk_feldkatalog_min_bytes')) {
        $size = @filesize($jsonPath);
        if ($size !== false && $size > 0 && $size < rkrk_feldkatalog_min_bytes() && @filesize($xmlDir . 'RKRKFelder.xml') >= rkrk_feldkatalog_min_bytes()) {
            return false;
        }
    }
    return is_file($jsonPath) && is_file($xmlDir . 'RKRKFelder.xml');
}

function editor_persist_catalog(string $xmlPath, SimpleXMLElement $xml): bool
{
    return editor_save_catalog_compatible($xml);
}

function editor_catalog_rel(string $rel): string
{
    $rel = trim(str_replace('\\', '/', $rel));
    if ($rel === '' || strpos($rel, '..') !== false) {
        return '';
    }
    if ($rel[0] === '/' || preg_match('/^[A-Za-z]:/', $rel)) {
        return '';
    }
    $parts = [];
    foreach (explode('/', $rel) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if (strcasecmp($part, '_history') === 0 || preg_match('/^(jsonfiles|xmlfiles|csvfiles)$/i', $part)) {
            return '';
        }
        $parts[] = $part;
    }
    if ($parts === []) {
        return '';
    }
    $base = $parts[count($parts) - 1];
    if (preg_match('/^rkrkfelder(\.|$)/i', $base)) {
        return '';
    }
    return implode('/', $parts);
}

function editor_basename_has_field_token(string $base, string $token): bool
{
    if ($base === '' || $token === '') {
        return false;
    }
    if (strncasecmp($base, $token, strlen($token)) !== 0) {
        return false;
    }
    $next = substr($base, strlen($token), 1);
    return $next === '' || $next === '_' || $next === '.';
}

function editor_field_file_abs(string $rel): string
{
    $rel = editor_catalog_rel($rel);
    if ($rel === '') {
        return '';
    }
    $abs = editor_catalog_file_abs($rel);
    if ($abs === '' || is_dir($abs)) {
        return '';
    }
    $real = realpath($abs);
    $root = realpath(editor_mission_catalog_root());
    if ($real === false || $root === false || !is_file($real)) {
        return '';
    }
    $realNorm = str_replace('\\', '/', $real);
    $rootNorm = rtrim(str_replace('\\', '/', $root), '/');
    if ($rootNorm === '' || stripos($realNorm, $rootNorm . '/') !== 0) {
        return '';
    }
    if (preg_match('/^rkrkfelder(\.|$)/i', basename($realNorm))) {
        return '';
    }
    return $real;
}

function editor_field_delete_rels(SimpleXMLElement $feld): array
{
    $token = editor_mission_file_token(trim((string)($feld->FeldID ?? '')));
    $rels = [];
    $slots = [
        'SuchfeldBild' => false,
        'FlugplanBild' => false,
        'JasonFile' => true,
        'KMLFile' => true,
        'KMZFile' => true,
        'LichtiFile' => false,
    ];
    foreach ($slots as $key => $flight) {
        $rel = editor_catalog_rel((string)($feld->{$key} ?? ''));
        if ($rel === '') {
            continue;
        }
        $rels[strtolower($rel)] = $rel;
        if (!$flight) {
            continue;
        }
        $ext = strtolower((string)pathinfo($rel, PATHINFO_EXTENSION));
        if (!in_array($ext, ['json', 'kml', 'kmz'], true)) {
            continue;
        }
        if ($token === '' || !editor_basename_has_field_token(basename($rel), $token)) {
            continue;
        }
        $stem = substr($rel, 0, -strlen($ext) - 1);
        foreach (['json', 'kml', 'kmz'] as $alt) {
            $norm = editor_catalog_rel($stem . '.' . $alt);
            if ($norm !== '') {
                $rels[strtolower($norm)] = $norm;
            }
        }
    }
    return array_values($rels);
}

function editor_catalog_file_refs(SimpleXMLElement $xml): array
{
    $used = [];
    foreach ($xml->Feld as $feld) {
        foreach (['SuchfeldBild', 'FlugplanBild', 'JasonFile', 'KMLFile', 'KMZFile', 'LichtiFile'] as $key) {
            $rel = editor_catalog_rel((string)($feld->{$key} ?? ''));
            if ($rel !== '') {
                $used[strtolower($rel)] = true;
            }
        }
    }
    return $used;
}

function editor_catalog_snapshot(): array
{
    editor_bind_mission_catalog();
    $target = editor_catalog_target();
    $snap = [];
    foreach (['json', 'xml', 'csv'] as $kind) {
        $path = (string)($target[$kind] ?? '');
        if ($path === '' || !is_file($path)) {
            continue;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'rkrk');
        if ($tmp === false || !@copy($path, $tmp)) {
            if (is_string($tmp) && $tmp !== '' && is_file($tmp)) {
                @unlink($tmp);
            }
            editor_catalog_snapshot_discard($snap);
            fail(500, 'catalog_backup', 'Feldkatalog konnte vor dem Löschen nicht gesichert werden. Nichts gelöscht.');
        }
        $snap[$kind] = ['path' => $path, 'tmp' => $tmp];
    }
    if (!isset($snap['json']) || !isset($snap['xml'])) {
        editor_catalog_snapshot_discard($snap);
        fail(500, 'catalog_backup', 'XML oder JSON des Feldkatalogs fehlt. Nichts gelöscht.');
    }
    return $snap;
}

function editor_catalog_snapshot_restore(array $snap): void
{
    foreach ($snap as $row) {
        if (!is_array($row)) {
            continue;
        }
        $path = (string)($row['path'] ?? '');
        $tmp = (string)($row['tmp'] ?? '');
        if ($path !== '' && $tmp !== '' && is_file($tmp)) {
            @copy($tmp, $path);
        }
    }
}

function editor_catalog_snapshot_discard(array $snap): void
{
    foreach ($snap as $row) {
        if (!is_array($row)) {
            continue;
        }
        $tmp = (string)($row['tmp'] ?? '');
        if ($tmp !== '' && is_file($tmp)) {
            @unlink($tmp);
        }
    }
}

function editor_catalog_lost_ids(array $beforeIds, array $afterIds, string $feldId): array
{
    $lost = [];
    foreach ($beforeIds as $key => $id) {
        if (strcasecmp((string)$id, $feldId) === 0) {
            continue;
        }
        if (!isset($afterIds[$key])) {
            $lost[] = (string)$id;
        }
    }
    return $lost;
}

function editor_delete_catalog_field(array $payload): void
{
    if (!array_key_exists('KAT', $_GET)) {
        fail(403, 'kat_required', 'Löschen nur mit ?KAT.');
    }
    if (!array_key_exists('confirm', $payload) || $payload['confirm'] !== true) {
        fail(422, 'confirm_required', 'Ohne Bestätigung wird nichts gelöscht.');
    }
    if (!empty($GLOBALS['editor_catalog_absent'])) {
        fail(404, 'catalog_missing', editor_catalog_missing_detail((string)($GLOBALS['RKRKMissionSelected'] ?? '')));
    }
    $field = is_array($payload['field'] ?? null) ? $payload['field'] : [];
    $wantId = trim((string)($field['id'] ?? ''));
    $wantFeld = editor_normalize_feld_id(trim((string)($field['feld_id'] ?? '')));
    if ($wantId === '' || $wantFeld === '' || !array_key_exists('name', $field)) {
        fail(422, 'missing_id', 'Löschen braucht id, Feldkatalognummer und Feldname.');
    }
    $wantName = trim((string)$field['name']);
    $xmlPath = editor_mission_catalog_xml();
    editor_catalog_lock();
    $xml = editor_reload_catalog($xmlPath);
    $matches = [];
    foreach ($xml->Feld as $feld) {
        if (editor_field_key($feld) !== $wantId) {
            continue;
        }
        $idOn = editor_normalize_feld_id(trim((string)($feld->FeldID ?? '')));
        if (strcasecmp($idOn, $wantFeld) !== 0) {
            continue;
        }
        $matches[] = $feld;
    }
    if (count($matches) !== 1) {
        fail(count($matches) === 0 ? 404 : 409, count($matches) === 0 ? 'field_not_found' : 'field_ambiguous', count($matches) === 0 ? 'Feld nicht gefunden. Nichts gelöscht.' : 'Die Angabe trifft mehr als ein Feld. Nichts gelöscht.');
    }
    $target = $matches[0];
    $catalogName = trim((string)($target->Feldname ?? ''));
    if ($catalogName !== $wantName) {
        fail(409, 'name_mismatch', 'Feldname passt nicht zur Feldkatalognummer. Nichts gelöscht.');
    }
    $feldId = editor_normalize_feld_id(trim((string)($target->FeldID ?? '')));
    $rels = editor_field_delete_rels($target);
    $beforeCount = count($xml->Feld);
    $domNode = dom_import_simplexml($target);
    if (!$domNode || !$domNode->parentNode) {
        fail(500, 'delete_failed', 'Feldeintrag konnte nicht entfernt werden. Nichts gelöscht.');
    }
    $snap = editor_catalog_snapshot();
    $beforeIds = editor_ids_from_catalog_files([
        'json' => (string)$snap['json']['path'],
        'xml' => (string)$snap['xml']['path'],
    ]);
    $domNode->parentNode->removeChild($domNode);
    if (count($xml->Feld) !== $beforeCount - 1) {
        editor_catalog_snapshot_discard($snap);
        fail(500, 'delete_failed', 'Es wäre nicht genau ein Feld entfernt worden. Nichts gelöscht.');
    }
    editor_prepare_catalog_save();
    $saved = editor_persist_catalog($xmlPath, $xml);
    $afterIds = editor_ids_from_catalog_files([
        'json' => (string)$snap['json']['path'],
        'xml' => (string)$snap['xml']['path'],
    ]);
    $lost = editor_catalog_lost_ids($beforeIds, $afterIds, $feldId);
    $stillThere = isset($afterIds[strtolower($feldId)]);
    if (!$saved || $stillThere || $lost !== []) {
        editor_catalog_snapshot_restore($snap);
        editor_catalog_snapshot_discard($snap);
        if ($lost !== []) {
            fail(500, 'catalog_shrink', 'Der Feldkatalog wäre um mehr als dieses Feld verkleinert worden. Die Änderung wurde zurückgenommen.');
        }
        fail(500, 'delete_failed', 'Feldkatalog konnte nicht gespeichert werden. Nichts gelöscht.');
    }
    editor_catalog_snapshot_discard($snap);
    $reserved = false;
    foreach (editor_reserve_load(true) as $row) {
        if (strcasecmp((string)($row['FeldID'] ?? ''), $feldId) === 0) {
            $reserved = true;
            break;
        }
    }
    if ($reserved) {
        editor_reserve_drop('', $feldId);
    }
    $used = editor_catalog_file_refs($xml);
    $removed = [];
    foreach ($rels as $rel) {
        if (isset($used[strtolower($rel)])) {
            continue;
        }
        $abs = editor_field_file_abs($rel);
        if ($abs === '') {
            continue;
        }
        if (@unlink($abs)) {
            $removed[] = $rel;
        }
    }
    echo json_encode([
        'ok' => true,
        'mode' => 'delete',
        'deleted_feld_id' => $feldId,
        'deleted_name' => $catalogName,
        'removed_files' => $removed,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function editor_store_uploaded_jpeg(array $file, string $abs): void
{
    $err = intval($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        fail(422, 'upload_failed', 'Bild wurde nicht empfangen');
    }
    $info = @getimagesize((string)($file['tmp_name'] ?? ''));
    if (!is_array($info) || intval($info[2] ?? 0) !== IMAGETYPE_JPEG) {
        fail(422, 'not_jpeg', 'Such- und Flugplanbild müssen JPEG sein');
    }
    $dir = dirname($abs);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail(500, 'mkdir_failed', 'Bildordner konnte nicht erstellt werden');
    }
    if (!move_uploaded_file((string)$file['tmp_name'], $abs)) {
        fail(500, 'picture_write_failed', 'Bild konnte nicht gespeichert werden');
    }
}

function editor_store_plan_pictures(SimpleXMLElement $xml, string $xmlPath): void
{
    editor_load_name_tables();
    editor_load_mission_export_lib();
    $id = trim((string)($_POST['id'] ?? ''));
    $feldId = trim((string)($_POST['feld_id'] ?? ''));
    $target = null;
    foreach ($xml->Feld as $f) {
        $sameId = $id !== '' && editor_field_key($f) === $id;
        $sameFeld = $feldId !== '' && trim((string)($f->FeldID ?? '')) === $feldId;
        if ($sameId || $sameFeld) {
            $target = $f;
            break;
        }
    }
    if (!$target) {
        fail(404, 'field_not_found', 'Feld für die Planbilder nicht gefunden');
    }
    $feldId = trim((string)($target->FeldID ?? ''));
    $jg = trim((string)($target->JG ?? ''));
    $name = trim((string)($target->Feldname ?? ''));
    $gis = trim((string)($target->GrundstueckID ?? ''));
    $height = trim((string)($target->StdFlughohe ?? '60'));
    $regions = $GLOBALS['RegionSettings'] ?? [];
    if (!isset($regions[$jg]) || !is_array($regions[$jg])) {
        fail(422, 'unknown_jg', 'Jagdgebiet ' . $jg . ' ist in den Regionsdaten nicht vorhanden');
    }
    $relDir = trim(str_replace('\\', '/', (string)($regions[$jg]['Dir'] ?? '')), '/');
    if ($relDir === '') {
        fail(422, 'missing_region_dir', 'Für Jagdgebiet ' . $jg . ' ist kein Missionsordner hinterlegt');
    }
    $absDir = editor_region_mission_dir($relDir);
    $pilot = function_exists('rkrk_export_pilot_code') ? rkrk_export_pilot_code() : 'FE';
    $flug = function_exists('rkrk_mission_name_parts')
        ? rkrk_mission_name_parts($feldId, $name, $gis, $height, $pilot)
        : ['complete' => $feldId . '_flug'];
    $such = function_exists('rkrk_mission_name_parts')
        ? rkrk_mission_name_parts($feldId, $name, $gis, '0', $pilot)
        : ['complete' => $feldId . '_such'];
    $suchName = preg_replace('/_0_([^_]+_\d{2})$/', '_00_$1', (string)$such['complete']);
    if (!is_string($suchName) || $suchName === '') {
        $suchName = (string)$such['complete'];
    }
    $written = [];
    if (!empty($_FILES['suchfeld']) && is_array($_FILES['suchfeld'])) {
        $rel = $relDir . '/' . $suchName . '.jpg';
        editor_store_uploaded_jpeg($_FILES['suchfeld'], $absDir . DIRECTORY_SEPARATOR . $suchName . '.jpg');
        ensure_child($target, 'SuchfeldBild', $rel);
        $written['suchfeld_bild'] = $rel;
    }
    if (!empty($_FILES['flugplan']) && is_array($_FILES['flugplan'])) {
        $rel = $relDir . '/' . $flug['complete'] . '.jpg';
        editor_store_uploaded_jpeg($_FILES['flugplan'], $absDir . DIRECTORY_SEPARATOR . $flug['complete'] . '.jpg');
        ensure_child($target, 'FlugplanBild', $rel);
        $written['flugplan_bild'] = $rel;
    }
    if ($written === []) {
        fail(422, 'missing_picture', 'Kein Planbild übergeben');
    }
    $today = date('d.m.Y');
    ensure_child($target, 'Aenderung', $today);
    ensure_child($target, 'Update', $today);
    if (!editor_persist_catalog($xmlPath, $xml)) {
        fail(500, 'save_failed', 'Planbilder wurden geschrieben, der Feldkatalog aber nicht');
    }
    echo json_encode(['ok' => true] + $written, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$editorRaw = '';
$editorPayload = null;
if ($method === 'POST' && !isset($_FILES['suchfeld']) && !isset($_FILES['flugplan'])) {
    $editorRaw = file_get_contents('php://input');
    $decodedEarly = ($editorRaw !== '') ? json_decode($editorRaw, true) : null;
    $editorPayload = is_array($decodedEarly) ? $decodedEarly : null;
}
$missionHint = trim((string)($_GET['MDB'] ?? $_GET['mdb'] ?? $_POST['mdb'] ?? ''));
if ($missionHint === '' && is_array($editorPayload)) {
    $missionHint = trim((string)($editorPayload['mdb'] ?? ''));
    $missionSettingsHint = $editorPayload['field']['mission_settings'] ?? null;
    if ($missionHint === '' && is_array($missionSettingsHint)) {
        $missionHint = trim((string)($missionSettingsHint['drone_type'] ?? ''));
    }
}
editor_use_mission($missionHint);

if ($method === 'GET' && strtolower(trim((string)($_GET['action'] ?? ''))) === 'catalogs') {
    if (!array_key_exists('KAT', $_GET)) {
        fail(403, 'kat_required', 'Feldkatalog nur mit ?KAT.');
    }
    echo json_encode(editor_catalog_overview(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && is_array($editorPayload)) {
    $earlyMode = strtolower(trim((string)($editorPayload['mode'] ?? '')));
    if ($earlyMode === 'create_catalog') {
        fail(403, 'catalog_config_only', 'Ein Feldkatalog wird nur in der Website-Konfiguration unter Missionsbibliotheken angelegt.');
    }
    if ($earlyMode === 'heartbeat' || $earlyMode === 'release') {
        if (!array_key_exists('KAT', $_GET) || !empty($GLOBALS['editor_catalog_absent'])) {
            echo json_encode(['ok' => true, 'mode' => $earlyMode, 'skipped' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $tokenEarly = editor_reserve_token_from($editorPayload);
        editor_catalog_lock();
        if ($earlyMode === 'release') {
            if ($tokenEarly !== '') {
                editor_reserve_drop($tokenEarly);
            }
            echo json_encode(['ok' => true, 'mode' => 'release'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $touched = $tokenEarly !== '' && editor_reserve_touch($tokenEarly);
        echo json_encode(['ok' => true, 'mode' => 'heartbeat', 'extended' => $touched], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($earlyMode === 'delete') {
        editor_delete_catalog_field($editorPayload);
    }
    if ($earlyMode === 'catalog_plan') {
        editor_prepare_catalog_save();
        editor_bind_mission_catalog();
        $target = editor_catalog_target();
        $jsonBytes = is_file($target['json']) ? (int)filesize($target['json']) : 0;
        $transferFields = null;
        $transferError = '';
        if (!empty($editorPayload['check_transfer'])) {
            try {
                $planXml = function_exists('rkrk_catalog_load_simplexml') ? rkrk_catalog_load_simplexml($target['xml']) : false;
                if (!($planXml instanceof SimpleXMLElement) || !function_exists('TransferFeldKatFromXMLtoArr')) {
                    $transferError = 'katalog oder Transfer fehlt';
                } else {
                    $optionJaeger = [];
                    $maxPlan = 0;
                    $regionsPlan = is_array($GLOBALS['EnabledRegion'] ?? null) ? $GLOBALS['EnabledRegion'] : [];
                    ob_start();
                    $katPlan = TransferFeldKatFromXMLtoArr($planXml, $regionsPlan, $optionJaeger, $maxPlan);
                    ob_end_clean();
                    $transferFields = 0;
                    if (is_array($katPlan)) {
                        foreach ($katPlan as $regionPlan) {
                            if (is_array($regionPlan)) {
                                $transferFields += count($regionPlan);
                            }
                        }
                    } else {
                        $transferError = 'Transfer liefert kein Array';
                    }
                }
            } catch (Throwable $planError) {
                $transferError = $planError->getMessage();
            }
        }
        echo json_encode([
            'ok' => true,
            'mode' => 'catalog_plan',
            'persist' => false,
            'mission' => $target['mission'],
            'mission_location' => $target['mission_location'],
            'json' => $target['json'],
            'xml' => $target['xml'],
            'csv' => $target['csv'],
            'json_bytes' => $jsonBytes,
            'json_is_webdaten' => (stripos($target['json'], 'webdaten') !== false),
            'would_call' => function_exists('SaveFlugkatalog') ? 'SaveFlugkatalog' : '',
            'would_call_csv' => function_exists('SaveFlugkatalog2CSV') ? 'SaveFlugkatalog2CSV' : '',
            'dirs_ready' => is_dir($target['json_dir']) && is_dir($target['xml_dir']) && is_dir($target['csv_dir']),
            'transfer_fields' => $transferFields,
            'transfer_error' => $transferError,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!empty($GLOBALS['editor_catalog_absent'])) {
    $absentKey = (string)($GLOBALS['RKRKMissionSelected'] ?? '');
    if ($method === 'GET') {
        $absentAction = strtolower(trim((string)($_GET['action'] ?? 'list')));
        if (($absentAction === 'list' || $absentAction === 'get') && !array_key_exists('KAT', $_GET)) {
            fail(403, 'kat_required', 'Feldkatalog nur mit ?KAT. Ohne KAT das Feld lokal als KML oder JSON herunterladen.');
        }
        if ($absentAction === 'list') {
            echo json_encode([
                'ok' => true,
                'fields' => [],
                'catalog' => false,
                'can_add' => editor_catalog_can_add($absentKey),
                'mission' => $absentKey,
                'detail' => editor_catalog_missing_detail($absentKey),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($absentAction === 'choices') {
            echo json_encode(['ok' => true, 'choices' => editor_choice_lists(new SimpleXMLElement('<Felder></Felder>'))], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($absentAction === 'feld_place') {
            echo json_encode(editor_feld_id_place(new SimpleXMLElement('<Felder></Felder>'), (string)($_GET['feld_id'] ?? '')), JSON_UNESCAPED_UNICODE);
            exit;
        }
        fail(404, 'catalog_missing', editor_catalog_missing_detail($absentKey));
    }
    $absentMode = is_array($editorPayload) ? strtolower(trim((string)($editorPayload['mode'] ?? ''))) : '';
    if ($absentMode !== 'preview') {
        fail(409, 'catalog_missing', editor_catalog_missing_detail($absentKey));
    }
    $xml = new SimpleXMLElement('<Felder></Felder>');
    $xmlPath = '';
} else {
    $xmlPath = editor_mission_catalog_xml();
    $xml = function_exists('rkrk_catalog_load_simplexml')
        ? rkrk_catalog_load_simplexml($xmlPath)
        : false;
    if (!($xml instanceof SimpleXMLElement) && is_file($xmlPath) && (!function_exists('rkrk_feldkatalog_file_complete') || rkrk_feldkatalog_file_complete($xmlPath))) {
        $xml = simplexml_load_file($xmlPath);
    }
    if (!($xml instanceof SimpleXMLElement)) {
        fail(500, 'catalog_missing', 'Feldkatalog (JSON) nicht gefunden');
    }
}


if ($method === 'GET') {
    $action = strtolower(trim((string)($_GET['action'] ?? 'list')));
    if (($action === 'list' || $action === 'get') && !array_key_exists('KAT', $_GET)) {
        fail(403, 'kat_required', 'Feldkatalog nur mit ?KAT. Ohne KAT das Feld lokal als KML oder JSON herunterladen.');
    }
    if ($action === 'list') {
        $rows = [];
        $placeCache = [];
        $placeLabel = static function (string $id, string $listKey) use (&$placeCache): string {
            $id = trim($id);
            if ($id === '') {
                return '';
            }
            $cacheKey = $listKey . "\0" . $id;
            if (!isset($placeCache[$cacheKey])) {
                $placeCache[$cacheKey] = editor_place_name($id, $listKey);
            }
            return $placeCache[$cacheKey];
        };
        foreach ($xml->Feld as $f) {
            $dynamicFeldParts = [];
            foreach ($f->children() as $child) {
                $key = (string)$child->getName();
                if (!preg_match('/^Feld[0-9]+$/i', $key)) {
                    continue;
                }
                $val = trim((string)$child);
                if ($val === '') {
                    continue;
                }
                $dynamicFeldParts[] = $key . ': ' . $val;
            }
            $jg = trim((string)($f->JG ?? ''));
            $gemeinde = trim((string)($f->Gemeinde ?? ''));
            $rows[] = [
                'id' => editor_field_key($f),
                'feld_id' => trim((string)($f->FeldID ?? '')),
                'name' => trim((string)($f->Feldname ?? '')),
                'jg' => $jg,
                'jg_name' => $placeLabel($jg, 'Jgd'),
                'gemeinde' => $gemeinde,
                'gemeinde_name' => $placeLabel($gemeinde, 'Gde'),
                'gis' => trim((string)($f->GrundstueckID ?? '')),
                'flurname' => trim((string)($f->Flurname ?? '')),
                'freigabe' => trim((string)($f->Freigabe ?? '')),
                'flaeche' => trim((string)($f->Flaeche ?? '')),
                'funde' => trim((string)($f->Funde ?? '')),
                'dynamic_feld_info' => implode(' | ', $dynamicFeldParts),
            ];
        }
        usort($rows, static function (array $a, array $b): int {
            $ka = mb_strtolower(trim(($a['feld_id'] ?? '') . ' ' . ($a['name'] ?? '')));
            $kb = mb_strtolower(trim(($b['feld_id'] ?? '') . ' ' . ($b['name'] ?? '')));
            return $ka <=> $kb;
        });
        echo json_encode(['ok' => true, 'fields' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'get') {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '') {
            fail(422, 'missing_id', 'id fehlt');
        }
        foreach ($xml->Feld as $f) {
            if (editor_field_key($f) === $id) {
                $field = xml_to_array_simple($f);
                $field['jg_name'] = editor_place_name((string)($field['jg'] ?? ''), 'Jgd');
                $field['gemeinde_name'] = editor_place_name((string)($field['gemeinde'] ?? ''), 'Gde');
                echo json_encode(['ok' => true, 'field' => $field], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        fail(404, 'field_not_found', 'Feld nicht gefunden');
    }

    if ($action === 'choices') {
        echo json_encode(['ok' => true, 'choices' => editor_choice_lists($xml)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'feld_place') {
        echo json_encode(editor_feld_id_place($xml, (string)($_GET['feld_id'] ?? '')), JSON_UNESCAPED_UNICODE);
        exit;
    }

    fail(422, 'invalid_action', 'Unbekannte action');
}

if ($method !== 'POST') {
    fail(405, 'method_not_allowed', 'Nur GET/POST erlaubt');
}

if (isset($_FILES['suchfeld']) || isset($_FILES['flugplan'])) {
    if (!array_key_exists('KAT', $_GET)) {
        fail(403, 'kat_required', 'Planbilder nur mit ?KAT.');
    }
    editor_store_plan_pictures($xml, $xmlPath);
}

$raw = $editorRaw;
$payload = $editorPayload;
if (!is_array($payload)) {
    fail(422, 'invalid_json', 'JSON Body ungueltig');
}

$mode = strtolower(trim((string)($payload['mode'] ?? 'update')));
if ($mode !== 'preview' && empty($payload['kat']) && !array_key_exists('KAT', $_GET)) {
    fail(403, 'kat_required', 'Speichern in den Feldkatalog braucht ?KAT. Ohne KAT das Feld lokal als KML oder JSON herunterladen.');
}
$data = is_array($payload['field'] ?? null) ? $payload['field'] : [];
$targetId = trim((string)($data['id'] ?? ''));
$feldId = editor_normalize_feld_id(trim((string)($data['feld_id'] ?? '')));
$name = trim((string)($data['name'] ?? ''));
$gis = trim((string)($data['grundstueck_id'] ?? $data['gis'] ?? ''));
$jg = trim((string)($data['jg'] ?? ''));
$gemeinde = '';
$zentrum = '';
$assignedNumber = trim((string)($data['number'] ?? ''));
$missionFile = '';
$stdFlughoehe = trim((string)($data['std_flughoehe'] ?? '50'));
$startRaw = trim((string)($data['startplatz'] ?? ''));
$polyRaw = trim((string)($data['flaechendaten'] ?? ''));
$pathRaw = trim((string)($data['flugweg'] ?? ''));
$flugzeit = trim((string)($data['flugzeit'] ?? '0'));
$distanz = trim((string)($data['distanz'] ?? '0'));
$wegpunkte = trim((string)($data['wegpunkte'] ?? '0'));

$poly = parse_pairs_text($polyRaw);
if (count($poly) < 3) {
    fail(422, 'invalid_polygon', 'Flaechendaten brauchen mindestens 3 Punkte');
}
$path = parse_triplets_text($pathRaw);
$startPairs = parse_pairs_text($startRaw);
if ($mode === 'preview') {
    $placeCandidates = [editor_polygon_centroid($poly)];
    if (count($startPairs) >= 1) {
        $placeCandidates[] = $startPairs[0];
    }
    $placeCandidates[] = $poly[0];
    $place = editor_place_from_points($placeCandidates);
    $gemeinde = $place['gemeinde'];
    $jg = $place['jg'];
    $zentrum = format_center_text(editor_polygon_centroid($poly));
    $allowMissing = !empty($data['allow_missing_place']);
    if ($jg === '' && !$allowMissing) {
        fail(422, 'jg_not_found', 'Zum Feldmittelpunkt wurde kein Jagdgebiet gefunden');
    }
    $issued = ['feld_id' => '', 'number' => ''];
    $reserveNote = '';
    $reserveKept = false;
    $katOn = array_key_exists('KAT', $_GET);
    if ($katOn && $jg !== '' && empty($GLOBALS['editor_catalog_absent']) && !editor_is_existing_catalog_edit($xml, $data)) {
        $token = editor_reserve_token_from($payload);
        if ($token === '') {
            fail(422, 'missing_token', 'Reservierung braucht ein Token');
        }
        editor_catalog_lock();
        $xml = editor_reload_catalog($xmlPath);
        $claim = editor_reserve_claim($xml, $jg, $token, trim((string)($data['feld_id'] ?? '')));
        $issued = ['feld_id' => $claim['feld_id'], 'number' => $claim['number']];
        $reserveNote = (string)$claim['reserve_note'];
        $reserveKept = !empty($claim['reserve_kept']);
    }
    $regionDir = '';
    $regions = $GLOBALS['RegionSettings'] ?? [];
    if ($jg !== '' && isset($regions[$jg]) && is_array($regions[$jg])) {
        $regionDir = trim(str_replace('\\', '/', (string)($regions[$jg]['Dir'] ?? '')), '/');
    }
    echo json_encode([
        'ok' => true,
        'mode' => 'preview',
        'feld_id' => $issued['feld_id'],
        'number' => $issued['number'],
        'jg' => $jg,
        'jg_name' => $jg !== '' ? ($place['jg_name'] ?? editor_place_name($jg, 'Jgd')) : '',
        'gemeinde' => $gemeinde,
        'gemeinde_name' => $gemeinde !== '' ? ($place['gemeinde_name'] ?? editor_place_name($gemeinde, 'Gde')) : '',
        'missing_jg' => $jg === '',
        'missing_gemeinde' => $gemeinde === '',
        'zentrum' => $zentrum,
        'reserve_note' => $reserveNote,
        'reserve_kept' => $reserveKept,
        'mission' => (string)($GLOBALS['RKRKMissionSelected'] ?? ''),
        'picture_dir' => trim((string)($GLOBALS['MissionLocation'] ?? ''), '/') . ($regionDir !== '' ? ('/' . $regionDir) : ''),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (count($startPairs) < 1) {
    fail(422, 'invalid_start', 'Startplatz braucht Lon/Lat');
}
if ($mode === 'create') {
    if ($name === '') {
        fail(422, 'missing_required', 'Feldname ist Pflicht');
    }
    if (count($path) < 2) {
        fail(422, 'invalid_path', 'Flugweg braucht mindestens 2 Wegpunkte. Zuerst die Route generieren.');
    }
    $placeCandidates = [editor_polygon_centroid($poly), $startPairs[0], $poly[0]];
    $place = editor_place_from_points($placeCandidates);
    $gemeinde = $place['gemeinde'];
    $jg = $place['jg'];
    $zentrum = format_center_text(editor_polygon_centroid($poly));
    $confirmMissing = !empty($data['confirm_missing_place']);
    if ($jg === '' && !$confirmMissing) {
        fail(422, 'jg_not_found', 'Zum Feldmittelpunkt wurde kein Jagdgebiet gefunden');
    }
    $catalogLock = editor_catalog_lock();
    $xml = editor_reload_catalog($xmlPath);
    $reserveToken = editor_reserve_token_from($payload);
    $requested = editor_normalize_feld_id((string)($data['feld_id'] ?? ''));
    $confirmReuse = !empty($data['confirm_existing_feld_id']);
    $jgKey = $jg !== '' ? $jg : 'OHNE';
    $requestedOk = ($requested !== '' && preg_match('/^\d+-\d+$/', $requested));
    $foreignReserve = false;
    $usedElsewhere = $requestedOk && isset(editor_foreign_feld_ids()[strtolower($requested)]);
    if ($requestedOk && $reserveToken !== '') {
        foreach (array_merge(editor_reserve_load(true), editor_foreign_reserve_rows()) as $reservedRow) {
            if (strcasecmp((string)$reservedRow['FeldID'], $requested) !== 0) {
                continue;
            }
            if (!editor_reserve_token_is((string)$reservedRow['token'], $reserveToken)) {
                $foreignReserve = true;
                break;
            }
        }
    }
    if ($requestedOk && !$foreignReserve && !$usedElsewhere && ($confirmReuse || !editor_feld_id_taken($xml, $requested, '', $reserveToken))) {
        $feldId = $requested;
        $assignedNumber = strval(intval(substr($requested, strrpos($requested, '-') + 1)));
    } else {
        $issued = editor_next_feld_id($xml, $jgKey, $reserveToken);
        $feldId = $issued['feld_id'];
        $assignedNumber = $issued['number'];
    }
    if ($mode === 'preview') {
        echo json_encode([
            'ok' => true,
            'mode' => 'preview',
            'feld_id' => $feldId,
            'number' => $assignedNumber,
            'jg' => $jg,
            'jg_name' => $place['jg_name'] ?? editor_place_name($jg, 'Jgd'),
            'gemeinde' => $gemeinde,
            'gemeinde_name' => $place['gemeinde_name'] ?? editor_place_name($gemeinde, 'Gde'),
            'zentrum' => $zentrum,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} elseif ($feldId === '' || $name === '') {
    fail(422, 'missing_required', 'FeldID und Feldname sind Pflicht');
}
if (count($path) < 1) {
    fail(422, 'invalid_path', 'Flugweg braucht mindestens 1 Wegpunkt');
}
$today = date('d.m.Y');

if ($mode === 'create') {
    $maxId = 0;
    foreach ($xml->Feld as $f) {
        $maxId = max($maxId, intval((string)$f['id']));
    }
    editor_feld_id_claim($xml, $feldId, '', !empty($data['confirm_existing_feld_id']), $reserveToken);
    $mission = null;
    $missionFile = '';
    $kmzFile = '';
    $newId = strval($maxId + 1);
    $f = $xml->addChild('Feld');
    $f->addAttribute('id', $newId);
    ensure_child($f, 'Number', $assignedNumber);
    ensure_child($f, 'FeldID', $feldId);
    ensure_child($f, 'JG', $jg);
    ensure_child($f, 'Feldname', $name);
    ensure_child($f, 'Flurname', '');
    ensure_child($f, 'GrundstueckID', $gis);
    ensure_child($f, 'Flugzeit', $flugzeit);
    ensure_child($f, 'Flaeche', '0');
    ensure_child($f, 'Wegpunkte', $wegpunkte !== '' ? $wegpunkte : strval(count($path)));
    ensure_child($f, 'Distanz', $distanz);
    ensure_child($f, 'Gemeinde', $gemeinde);
    ensure_child($f, 'Betrieb', '');
    ensure_child($f, 'Freigabe', 'NEU');
    ensure_child($f, 'Warnung', '');
    ensure_child($f, 'Jaeger', '');
    ensure_child($f, 'Bemerkung', '');
    ensure_child($f, 'Flug', '');
    ensure_child($f, 'Funde', '');
    ensure_child($f, 'StdFlughohe', $stdFlughoehe);
    ensure_child($f, 'SuchfeldBild', '');
    ensure_child($f, 'FlugplanBild', '');
    ensure_child($f, 'JasonFile', $missionFile);
    ensure_child($f, 'KMLFile', '');
    ensure_child($f, 'KMZFile', '');
    ensure_child($f, 'LichtiFile', '');
    ensure_child($f, 'Bauer', '');
    ensure_child($f, 'Notiz', '');
    $gd = isset($f->Grundstueckdaten) ? $f->Grundstueckdaten : $f->addChild('Grundstueckdaten');
    ensure_child($gd, 'Startplatz', format_pairs_text([$startPairs[0]]));
    ensure_child($gd, 'Zentrum', $zentrum !== '' ? $zentrum : format_center_text(editor_polygon_centroid($poly)));
    ensure_child($f, 'Flaechendaten', format_pairs_text($poly));
    ensure_child($f, 'Flugweg', format_triplets_text($path));
    ensure_child($f, 'Export', 'Y');
    ensure_child($f, 'Erstellt', $today);
    ensure_child($f, 'Aenderung', $today);
    ensure_child($f, 'Update', $today);
    ensure_child($f, 'Select', 'Y');
    editor_write_catalog_details($f, $data, true, $missionFile);
    $centerText = format_center_text(editor_polygon_centroid($poly));
    if ($centerText !== '') {
        ensure_child($gd, 'Zentrum', $centerText);
    }
    write_field_mission_settings($f, $data);
    $stored = editor_attach_mission_files($f, $jg, $feldId, $name, $stdFlughoehe, $poly, $startPairs[0], $path, $data, $flugzeit, $distanz, $gis);
    $mission = $stored['mission'];
    $missionFile = $stored['jason_file'];
    $kmzFile = $stored['kmz_file'];
} else {
    if (!isset($catalogLock) || !is_resource($catalogLock)) {
        $catalogLock = editor_catalog_lock();
        $xml = editor_reload_catalog($xmlPath);
    }
    if ($targetId === '') {
        fail(422, 'missing_id', 'Beim Update ist field.id Pflicht');
    }
    $target = null;
    foreach ($xml->Feld as $f) {
        if (editor_field_key($f) === $targetId) {
            $target = $f;
            break;
        }
    }
    if (!$target) {
        fail(404, 'field_not_found', 'Feld nicht gefunden');
    }
    $previousFeldId = trim((string)($target->FeldID ?? ''));
    editor_feld_id_claim($xml, $feldId, $targetId, !empty($data['confirm_existing_feld_id']), editor_reserve_token_from(is_array($payload) ? $payload : []));
    ensure_child($target, 'FeldID', $feldId);
    if (preg_match('/-(\d+)$/', $feldId, $numMatch)) {
        ensure_child($target, 'Number', strval(intval($numMatch[1])));
    }
    ensure_child($target, 'Feldname', $name);
    if ($gis !== '') {
        ensure_child($target, 'GrundstueckID', $gis);
    }
    if ($jg !== '') {
        ensure_child($target, 'JG', $jg);
    }
    ensure_child($target, 'StdFlughohe', $stdFlughoehe);
    ensure_child($target, 'Flugzeit', $flugzeit);
    ensure_child($target, 'Distanz', $distanz);
    ensure_child($target, 'Wegpunkte', $wegpunkte !== '' ? $wegpunkte : strval(count($path)));
    $gd = isset($target->Grundstueckdaten) ? $target->Grundstueckdaten : $target->addChild('Grundstueckdaten');
    ensure_child($gd, 'Startplatz', format_pairs_text([$startPairs[0]]));
    ensure_child($target, 'Flaechendaten', format_pairs_text($poly));
    ensure_child($target, 'Flugweg', format_triplets_text($path));
    editor_write_catalog_details($target, $data, false);
    $centerText = format_center_text(editor_polygon_centroid($poly));
    if ($centerText !== '') {
        ensure_child($gd, 'Zentrum', $centerText);
    }
    write_field_mission_settings($target, $data);
    if (strcasecmp($previousFeldId, $feldId) !== 0) {
        editor_rename_catalog_files($target, $previousFeldId, $feldId);
    }
    $missionJg = $jg !== '' ? $jg : trim((string)($target->JG ?? ''));
    $stored = editor_attach_mission_files($target, $missionJg, $feldId, $name, $stdFlughoehe, $poly, $startPairs[0], $path, $data, $flugzeit, $distanz, $gis);
    $mission = $stored['mission'];
    $missionFile = $stored['jason_file'];
    $kmzFile = $stored['kmz_file'];
    ensure_child($target, 'Aenderung', $today);
    ensure_child($target, 'Update', $today);
}

editor_prepare_catalog_save();
$saved = editor_persist_catalog($xmlPath, $xml);
if ($saved && array_key_exists('KAT', $_GET)) {
    $doneToken = editor_reserve_token_from(is_array($payload) ? $payload : []);
    if ($doneToken !== '' || $feldId !== '') {
        editor_reserve_drop($doneToken, $feldId);
    }
}
if (!$saved) {
    if ($mode === 'create' && is_array($mission)) {
        if ($missionFile !== '' && isset($mission['absolute']) && is_file($mission['absolute'])) {
            @unlink($mission['absolute']);
        }
        if ($kmzFile !== '' && isset($mission['kmz_absolute']) && is_file($mission['kmz_absolute'])) {
            @unlink($mission['kmz_absolute']);
        }
    }
    $why = trim((string)($GLOBALS['editorCatalogSaveError'] ?? ''));
    fail(500, 'save_failed', $why !== '' ? ('Feldkatalog konnte nicht gespeichert werden: ' . $why) : 'Feldkatalog konnte nicht gespeichert werden');
}

echo json_encode([
    'ok' => true,
    'mode' => $mode,
    'saved_field_id' => $feldId,
    'number' => $assignedNumber,
    'jg' => $jg,
    'jg_name' => editor_place_name($jg, 'Jgd'),
    'gemeinde' => $gemeinde,
    'gemeinde_name' => editor_place_name($gemeinde, 'Gde'),
    'jason_file' => $missionFile,
    'kmz_file' => $kmzFile,
], JSON_UNESCAPED_UNICODE);

