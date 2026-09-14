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
        'id' => (string)$f['id'],
        'number' => trim((string)($f->Number ?? '')),
        'feld_id' => $fieldId,
        'name' => $name,
        'jg' => trim((string)($f->JG ?? '')),
        'std_flughoehe' => trim((string)($f->StdFlughohe ?? '50')),
        'flugzeit' => trim((string)($f->Flugzeit ?? '0')),
        'distanz' => trim((string)($f->Distanz ?? '0')),
        'wegpunkte' => trim((string)($f->Wegpunkte ?? '0')),
        'startplatz' => $startRaw,
        'flaechendaten' => $polyRaw,
        'flugweg' => $pathRaw,
        'mission_settings' => $missionSettings,
    ];
}

function field_mission_settings_json(array $data): string
{
    $ms = is_array($data['mission_settings'] ?? null) ? $data['mission_settings'] : [];
    $mode = trim((string)($ms['waypoint_settings_mode'] ?? ''));
    $payload = [
        'waypoint_settings_mode' => ($mode === 'per_waypoint') ? 'per_waypoint' : 'route_follow',
        'waypoint_overrides' => is_array($ms['waypoint_overrides'] ?? null) ? $ms['waypoint_overrides'] : [],
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

$paths = resolve_mission_intern_paths();
ensure_mission_intern_structure($paths);
$xmlPath = $paths['xml_path'];
$xml = function_exists('rkrk_catalog_load_simplexml')
    ? rkrk_catalog_load_simplexml($xmlPath)
    : false;
if (!($xml instanceof SimpleXMLElement) && is_file($xmlPath)) {
    $xml = simplexml_load_file($xmlPath);
}
if (!($xml instanceof SimpleXMLElement)) {
    fail(500, 'catalog_missing', 'Feldkatalog (JSON) nicht gefunden');
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $action = strtolower(trim((string)($_GET['action'] ?? 'list')));
    if ($action === 'list') {
        $rows = [];
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
            $rows[] = [
                'id' => (string)$f['id'],
                'feld_id' => trim((string)($f->FeldID ?? '')),
                'name' => trim((string)($f->Feldname ?? '')),
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
            if ((string)$f['id'] === $id) {
                echo json_encode(['ok' => true, 'field' => xml_to_array_simple($f)], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        fail(404, 'field_not_found', 'Feld nicht gefunden');
    }

    fail(422, 'invalid_action', 'Unbekannte action');
}

if ($method !== 'POST') {
    fail(405, 'method_not_allowed', 'Nur GET/POST erlaubt');
}

$raw = file_get_contents('php://input');
$payload = $raw ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    fail(422, 'invalid_json', 'JSON Body ungueltig');
}

$mode = strtolower(trim((string)($payload['mode'] ?? 'update')));
$data = is_array($payload['field'] ?? null) ? $payload['field'] : [];
$targetId = trim((string)($data['id'] ?? ''));
$feldId = trim((string)($data['feld_id'] ?? ''));
$name = trim((string)($data['name'] ?? ''));
$jg = trim((string)($data['jg'] ?? ''));
$stdFlughoehe = trim((string)($data['std_flughoehe'] ?? '50'));
$startRaw = trim((string)($data['startplatz'] ?? ''));
$polyRaw = trim((string)($data['flaechendaten'] ?? ''));
$pathRaw = trim((string)($data['flugweg'] ?? ''));
$flugzeit = trim((string)($data['flugzeit'] ?? '0'));
$distanz = trim((string)($data['distanz'] ?? '0'));
$wegpunkte = trim((string)($data['wegpunkte'] ?? '0'));

if ($feldId === '' || $name === '') {
    fail(422, 'missing_required', 'FeldID und Feldname sind Pflicht');
}

$poly = parse_pairs_text($polyRaw);
if (count($poly) < 3) {
    fail(422, 'invalid_polygon', 'Flaechendaten brauchen mindestens 3 Punkte');
}
$path = parse_triplets_text($pathRaw);
if (count($path) < 1) {
    fail(422, 'invalid_path', 'Flugweg braucht mindestens 1 Wegpunkt');
}
$startPairs = parse_pairs_text($startRaw);
if (count($startPairs) < 1) {
    fail(422, 'invalid_start', 'Startplatz braucht Lon/Lat');
}
$today = date('d.m.Y');

if ($mode === 'create') {
    $maxId = 0;
    $maxNumber = 0;
    foreach ($xml->Feld as $f) {
        $maxId = max($maxId, intval((string)$f['id']));
        $maxNumber = max($maxNumber, intval((string)($f->Number ?? '0')));
        if (trim((string)($f->FeldID ?? '')) === $feldId) {
            fail(409, 'feld_id_exists', 'FeldID existiert bereits');
        }
    }
    $newId = strval($maxId + 1);
    $newNum = strval($maxNumber + 1);
    $f = $xml->addChild('Feld');
    $f->addAttribute('id', $newId);
    ensure_child($f, 'Number', $newNum);
    ensure_child($f, 'FeldID', $feldId);
    ensure_child($f, 'JG', $jg);
    ensure_child($f, 'Feldname', $name);
    ensure_child($f, 'Flurname', '');
    ensure_child($f, 'GrundstueckID', '');
    ensure_child($f, 'Flugzeit', $flugzeit);
    ensure_child($f, 'Flaeche', '0');
    ensure_child($f, 'Wegpunkte', $wegpunkte !== '' ? $wegpunkte : strval(count($path)));
    ensure_child($f, 'Distanz', $distanz);
    ensure_child($f, 'Gemeinde', '');
    ensure_child($f, 'Betrieb', '');
    ensure_child($f, 'Freigabe', 'OK');
    ensure_child($f, 'Warnung', '');
    ensure_child($f, 'Jaeger', '');
    ensure_child($f, 'Bemerkung', '');
    ensure_child($f, 'Flug', '');
    ensure_child($f, 'Funde', '');
    ensure_child($f, 'StdFlughohe', $stdFlughoehe);
    ensure_child($f, 'SuchfeldBild', '');
    ensure_child($f, 'FlugplanBild', '');
    ensure_child($f, 'JasonFile', '');
    ensure_child($f, 'KMLFile', '');
    ensure_child($f, 'KMZFile', '');
    ensure_child($f, 'LichtiFile', '');
    ensure_child($f, 'Bauer', '');
    ensure_child($f, 'Notiz', '');
    $gd = isset($f->Grundstueckdaten) ? $f->Grundstueckdaten : $f->addChild('Grundstueckdaten');
    ensure_child($gd, 'Startplatz', format_pairs_text([$startPairs[0]]));
    ensure_child($gd, 'Zentrum', format_pairs_text([$startPairs[0]]));
    ensure_child($f, 'Flaechendaten', format_pairs_text($poly));
    ensure_child($f, 'Flugweg', format_triplets_text($path));
    ensure_child($f, 'Export', 'Y');
    ensure_child($f, 'Erstellt', $today);
    ensure_child($f, 'Aenderung', $today);
    ensure_child($f, 'Update', $today);
    ensure_child($f, 'Select', 'Y');
    write_field_mission_settings($f, $data);
} else {
    if ($targetId === '') {
        fail(422, 'missing_id', 'Beim Update ist field.id Pflicht');
    }
    $target = null;
    foreach ($xml->Feld as $f) {
        if ((string)$f['id'] === $targetId) {
            $target = $f;
            break;
        }
    }
    if (!$target) {
        fail(404, 'field_not_found', 'Feld nicht gefunden');
    }
    foreach ($xml->Feld as $f) {
        if ((string)$f['id'] === $targetId) {
            continue;
        }
        if (trim((string)($f->FeldID ?? '')) === $feldId) {
            fail(409, 'feld_id_exists', 'FeldID existiert bereits');
        }
    }
    ensure_child($target, 'FeldID', $feldId);
    ensure_child($target, 'Feldname', $name);
    if ($jg !== '') {
        ensure_child($target, 'JG', $jg);
    }
    ensure_child($target, 'StdFlughohe', $stdFlughoehe);
    ensure_child($target, 'Flugzeit', $flugzeit);
    ensure_child($target, 'Distanz', $distanz);
    ensure_child($target, 'Wegpunkte', $wegpunkte !== '' ? $wegpunkte : strval(count($path)));
    $gd = isset($target->Grundstueckdaten) ? $target->Grundstueckdaten : $target->addChild('Grundstueckdaten');
    ensure_child($gd, 'Startplatz', format_pairs_text([$startPairs[0]]));
    if (!isset($gd->Zentrum) || trim((string)$gd->Zentrum) === '') {
        ensure_child($gd, 'Zentrum', format_pairs_text([$startPairs[0]]));
    }
    ensure_child($target, 'Flaechendaten', format_pairs_text($poly));
    ensure_child($target, 'Flugweg', format_triplets_text($path));
    write_field_mission_settings($target, $data);
    ensure_child($target, 'Aenderung', $today);
    ensure_child($target, 'Update', $today);
}

$saved = false;
if (function_exists('rkrk_feldkatalog_save_json_from_xml')) {
    $saved = rkrk_feldkatalog_save_json_from_xml($xmlPath, $xml);
} elseif (function_exists('rkrk_catalog_write_json')) {
    $fields = array();
    foreach ($xml->Feld as $f) {
        $row = function_exists('rkrk_simplexml_to_array') ? rkrk_simplexml_to_array($f) : xml_to_array_simple($f);
        if (is_array($row) && $row !== array()) {
            $fields[] = $row;
        }
    }
    $saved = rkrk_catalog_write_json($xmlPath, array(
        'schema' => 'rkrk-feldkatalog',
        'version' => 1,
        'root' => 'Felder',
        'fields' => $fields,
    ));
}
if (!$saved && function_exists('rkrk_xml_catalog_support') && rkrk_xml_catalog_support()) {
    $saved = ($xml->asXML($xmlPath) !== false);
}
if (!$saved) {
    fail(500, 'save_failed', 'Feldkatalog konnte nicht gespeichert werden');
}

echo json_encode([
    'ok' => true,
    'mode' => $mode,
    'saved_field_id' => $feldId
], JSON_UNESCAPED_UNICODE);

