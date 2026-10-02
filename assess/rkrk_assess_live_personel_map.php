<?php
if (ob_get_level() === 0) {
    ob_start();
}

include_once('rkrk_assess_common.php');

if (function_exists("rkrk_assess_portal_personel_enforce_access")) {
    rkrk_assess_portal_personel_enforce_access();
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (function_exists('ProtectFromAccess')) {
    CheckIPandExit($folderoffset);
}
GrantAccess($folderoffset );

function rkrk_live_map_esc($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rkrk_live_map_valid_key(string $k): bool
{
    if ($k === '' || strlen($k) > 96) {
        return false;
    }
    return preg_match('/^[\p{L}\p{N}_\-.]+$/u', $k) === 1;
}

function rkrk_live_map_cell_string($v): string
{
    if ($v === null) {
        return '';
    }
    if (is_bool($v)) {
        return $v ? '1' : '';
    }
    if (is_scalar($v)) {
        return (string)$v;
    }
    return json_encode($v, JSON_UNESCAPED_UNICODE);
}

/**
 * @return array<int,array<string,mixed>>
 */
function rkrk_live_map_load_rows(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === "") {
        return [];
    }
    $data = @json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    if (isset($data["pilots"]) && is_array($data["pilots"])) {
        $rows = $data["pilots"];
    } elseif (isset($data["rows"]) && is_array($data["rows"])) {
        $rows = $data["rows"];
    } elseif (isset($data[0]) || count($data) === 0) {
        $rows = array_values(array_filter($data, "is_array"));
    } else {
        return [];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean = [];
        foreach ($row as $k => $v) {
            if (!is_string($k) || !rkrk_live_map_valid_key($k)) {
                continue;
            }
            $clean[$k] = $v;
        }
        if ($clean !== []) {
            $out[] = $clean;
        }
    }
    return $out;
}

function rkrk_live_map_exp_level(string $raw): int
{
    $s = trim($raw);
    if ($s === "") {
        return 0;
    }
    if (preg_match('/^([1-6])\b/u', $s, $m)) {
        return (int)$m[1];
    }
    $n = (int)$s;
    return ($n >= 1 && $n <= 6) ? $n : 0;
}

function rkrk_live_map_title(array $r): string
{
    $vn = trim(rkrk_live_map_cell_string($r["vorname"] ?? ""));
    $nn = trim(rkrk_live_map_cell_string($r["nachname"] ?? ""));
    $nm = trim($nn . " " . $vn);
    if ($nm !== "") {
        return $nm;
    }
    $em = trim(rkrk_live_map_cell_string($r["email"] ?? ""));
    if ($em !== "") {
        return $em;
    }
    return "Pilot";
}

function rkrk_live_map_is_warn_drohnentyp(string $raw): bool
{
    $s = mb_strtolower(trim($raw), "UTF-8");
    if ($s === "") {
        return false;
    }
    if (strpos($s, "anderes") === 0) {
        return true;
    }
    if (strpos($s, "dji mavic 2") === 0) {
        return true;
    }
    if (strpos($s, "autel") === 0) {
        return true;
    }
    if (strpos($s, "dji matrice 640") === 0) {
        return true;
    }
    return false;
}

function rkrk_live_map_field_label(string $key): string
{
    static $map = [
        "pilot_id" => "PilotID",
        "vorname" => "Vorname",
        "nachname" => "Nachname",
        "strasse" => "Straße",
        "plz" => "PLZ",
        "ort" => "Ort",
        "email" => "E-Mail",
        "telefon" => "Telefon",
        "erste_erfahrung" => "Maturität",
        "instruktor" => "Instruktor",
        "alternative_rollen" => "Alternative Rollen",
        "drohnentyp" => "Drohnentyp",
        "rkrs_pilot_id" => "RKRS Piloten ID",
        "remote_pilot_id" => "Remote Pilot ID",
        "betreiber_id" => "Betreiber ID",
        "fpl_gueltig" => "FPL gültig bis",
        "einsatzpruefung_rkrsplus" => "EP RKRS+",
        "rkrsplus" => "RKRS+",
        "note" => "Bemerkung",
        "kommentar" => "Kommentar",
        "bemerkung" => "Bemerkung",
    ];
    return $map[$key] ?? $key;
}

function rkrk_live_map_skip_filter_key(string $key): bool
{
    return in_array($key, ["id", "name", "display", "updated_at", "version"], true);
}

function rkrk_live_map_row_fields(array $r): array
{
    $fields = [];
    foreach ($r as $k => $v) {
        if (!is_string($k) || $k === "" || rkrk_live_map_skip_filter_key($k)) {
            continue;
        }
        $s = trim(rkrk_live_map_cell_string($v));
        if ($k === "instruktor" || $k === "rkrsplus") {
            if ($s === "1" || strcasecmp($s, "ja") === 0) {
                $s = "ja";
            } elseif ($s === "0" || strcasecmp($s, "nein") === 0) {
                $s = "nein";
            }
        }
        $fields[$k] = $s;
    }
    return $fields;
}

function rkrk_live_map_search_blob(array $fields, array $extra = []): string
{
    $bits = array_values($fields);
    foreach ($extra as $x) {
        $t = trim((string)$x);
        if ($t !== "") {
            $bits[] = $t;
        }
    }
    if (($fields["instruktor"] ?? "") === "ja") {
        $bits[] = "instruktor ja";
    }
    if (($fields["rkrsplus"] ?? "") === "ja") {
        $bits[] = "rkrsplus ja rkrs+";
    }
    $bits = array_filter($bits, static function ($s): bool {
        return trim((string)$s) !== "";
    });
    return mb_strtolower(implode(" ", $bits), "UTF-8");
}

function rkrk_live_map_geocode_cache_file(string $pilotFile): string
{
    return rtrim(dirname($pilotFile), "/\\") . DIRECTORY_SEPARATOR . "geocode_cache.json";
}

/**
 * @return array<string,array<string,mixed>>
 */
function rkrk_live_map_geocode_cache_load(string $pilotFile): array
{
    $f = rkrk_live_map_geocode_cache_file($pilotFile);
    if (!is_file($f)) {
        return [];
    }
    $raw = @file_get_contents($f);
    if ($raw === false || trim($raw) === "") {
        return [];
    }
    $data = @json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function rkrk_live_map_geocode_cache_save(string $pilotFile, array $cache): void
{
    $f = rkrk_live_map_geocode_cache_file($pilotFile);
    @file_put_contents($f, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (empty($LoggedOn)) {
    if (function_exists('WriteToLog')) {
        WriteToLog("open", basename($_SERVER['PHP_SELF']), "", "", "", "",$folderoffset );
    }
    rkrk_assess_theme_link_tags();
    rkrk_assess_emit_setting_carry_script();
    headerpage(function_exists('rkrk_assess_copyright_holder') ? rkrk_assess_copyright_holder() : (string) ($copyright ?? 'drohnenrehkitzrettung.ch'), false, $folderoffset);
    if (function_exists('RegisterForm')) {
        RegisterForm(getIPAddress(), "Login", true);
    }
    exit;
}

//$rkrkDataRoot = isset($rkrkAssessFsDataRoot) ? $rkrkAssessFsDataRoot : $RKRKRelRKRKDataLocation;
//$rkrkLiveassessData = $rkrkDataRoot . "data/liveassess";
//$rkrkLivePersonelRoot = $rkrkLiveassessData . "/personel";
$pilotFile = $rkrkLivePersonelRoot . DIRECTORY_SEPARATOR . "pilotenliste.json";
if (!is_dir($rkrkLivePersonelRoot)) {
    @mkdir($rkrkLivePersonelRoot, 0777, true);
}

$rows = rkrk_live_map_load_rows($pilotFile);

if (isset($_GET["geocode"]) && (string)$_GET["geocode"] === "1") {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header("Content-Type: application/json; charset=UTF-8");

    $idx = (int)($_GET["idx"] ?? -1);
    if ($idx < 0 || !isset($rows[$idx]) || !is_array($rows[$idx])) {
        echo json_encode(["ok" => false, "error" => "invalid_index"]);
        exit;
    }
    $r = $rows[$idx];
    $street = trim(rkrk_live_map_cell_string($r["strasse"] ?? ""));
    $plz = trim(rkrk_live_map_cell_string($r["plz"] ?? ""));
    $ort = trim(rkrk_live_map_cell_string($r["ort"] ?? ""));
    $q = trim($street . ", " . $plz . " " . $ort);
    if ($q === "") {
        echo json_encode(["ok" => false, "error" => "empty_address"]);
        exit;
    }

    $cache = rkrk_live_map_geocode_cache_load($pilotFile);
    if (isset($cache[$q]) && is_array($cache[$q])) {
        echo json_encode([
            "ok" => true,
            "cached" => true,
            "lat" => $cache[$q]["lat"] ?? null,
            "lon" => $cache[$q]["lon"] ?? null,
            "label" => $cache[$q]["label"] ?? $q
        ]);
        exit;
    }

    $url = "https://api3.geo.admin.ch/rest/services/api/SearchServer?type=locations&limit=1&origins=address&sr=4326&searchText=" . rawurlencode($q);
    $ctx = stream_context_create([
        "http" => [
            "timeout" => 8,
            "header" => "User-Agent: RKRK-Assess-Personel-Map\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || trim($raw) === "") {
        echo json_encode(["ok" => false, "error" => "geocode_failed"]);
        exit;
    }
    $obj = @json_decode($raw, true);
    $res = (isset($obj["results"]) && is_array($obj["results"])) ? $obj["results"] : [];
    if ($res === []) {
        echo json_encode(["ok" => false, "error" => "not_found"]);
        exit;
    }
    $attrs = $res[0]["attrs"] ?? [];
    $lat = isset($attrs["lat"]) ? (float)$attrs["lat"] : null;
    $lon = isset($attrs["lon"]) ? (float)$attrs["lon"] : null;
    if ($lat === null || $lon === null) {
        echo json_encode(["ok" => false, "error" => "missing_coords"]);
        exit;
    }
    $label = strip_tags((string)($attrs["label"] ?? $q), "<b><i><em><strong><br>");
    $cache[$q] = ["lat" => $lat, "lon" => $lon, "label" => $label, "updated_at" => date("c")];
    rkrk_live_map_geocode_cache_save($pilotFile, $cache);
    echo json_encode(["ok" => true, "cached" => false, "lat" => $lat, "lon" => $lon, "label" => $label]);
    exit;
}

$filterPriority = [
    "drohnentyp",
    "nachname",
    "vorname",
    "ort",
    "plz",
    "strasse",
    "email",
    "telefon",
    "erste_erfahrung",
    "instruktor",
    "alternative_rollen",
    "rkrs_pilot_id",
    "remote_pilot_id",
    "betreiber_id",
    "fpl_gueltig",
    "einsatzpruefung_rkrsplus",
    "rkrsplus",
    "bemerkung",
    "note",
    "kommentar",
    "pilot_id",
];
$presentKeys = [];
foreach ($rows as $r) {
    if (!is_array($r)) {
        continue;
    }
    foreach (array_keys($r) as $k) {
        if (is_string($k) && $k !== "" && !rkrk_live_map_skip_filter_key($k)) {
            $presentKeys[$k] = true;
        }
    }
}
$filterColumns = [];
foreach ($filterPriority as $k) {
    if (!empty($presentKeys[$k])) {
        $filterColumns[] = $k;
        unset($presentKeys[$k]);
    }
}
$restKeys = array_keys($presentKeys);
sort($restKeys, SORT_NATURAL | SORT_FLAG_CASE);
$filterColumns = array_merge($filterColumns, $restKeys);
$filterColumnOptions = [];
foreach ($filterColumns as $k) {
    $filterColumnOptions[] = ["key" => $k, "label" => rkrk_live_map_field_label($k)];
}

$points = [];
foreach ($rows as $i => $r) {
    $title = rkrk_live_map_title($r);
    $exp = rkrk_live_map_exp_level(rkrk_live_map_cell_string($r["erste_erfahrung"] ?? ""));
    $phone = trim(rkrk_live_map_cell_string($r["telefon"] ?? ""));
    $mail = trim(rkrk_live_map_cell_string($r["email"] ?? ""));
    $addr = trim(
        rkrk_live_map_cell_string($r["strasse"] ?? "") . ", " .
        rkrk_live_map_cell_string($r["plz"] ?? "") . " " .
        rkrk_live_map_cell_string($r["ort"] ?? "")
    );
    $fields = rkrk_live_map_row_fields($r);
    $points[] = [
        "idx" => $i,
        "title" => $title,
        "vorname" => trim(rkrk_live_map_cell_string($r["vorname"] ?? "")),
        "nachname" => trim(rkrk_live_map_cell_string($r["nachname"] ?? "")),
        "drohnentyp" => trim(rkrk_live_map_cell_string($r["drohnentyp"] ?? "")),
        "warn_drohnentyp" => rkrk_live_map_is_warn_drohnentyp(rkrk_live_map_cell_string($r["drohnentyp"] ?? "")),
        "phone" => $phone,
        "email" => $mail,
        "address" => trim($addr, ", "),
        "exp" => $exp,
        "fields" => $fields,
        "search" => rkrk_live_map_search_blob($fields, [$title, $addr, (string)$exp]),
    ];
}

if (function_exists('WriteToLog')) {
    WriteToLog("open", basename($_SERVER['PHP_SELF']), "", "", "", "",$folderoffset );
}
rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
headerpage(function_exists('rkrk_assess_copyright_holder') ? rkrk_assess_copyright_holder() : (string) ($copyright ?? 'drohnenrehkitzrettung.ch'), false, $folderoffset);

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast' && !$onRKRS)
	&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
if ($showTopMenu) {
    CreateTopMenu($folderoffset);
    echo "<br><br>";
	if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
		rkrk_assess_emit_admin_url_managers_menu_strip();
	}
}

echo "<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' integrity='sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=' crossorigin='anonymous'>";
echo "<style>
.map-wrap{max-width:1200px;margin:0 auto;}
.map-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;}
.map-toolbar input[type=search]{min-width:180px;max-width:420px;flex:1;padding:6px 9px;border:1px solid #cfd8cc;border-radius:7px;}
.map-toolbar .map-filter-col-wrap{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.map-toolbar .map-filter-col-wrap select,.map-toolbar .map-filter-col-wrap input[type=search]{padding:6px 9px;border:1px solid #cfd8cc;border-radius:7px;font-size:13px;}
.map-toolbar .map-filter-col-wrap select{max-width:min(100%,16rem);}
.map-toolbar .map-filter-col-wrap input[type=search]{min-width:140px;max-width:260px;flex:1;}
#personel-map{height:72vh;min-height:420px;border:1px solid #cfd8cc;border-radius:10px;overflow:hidden;}
.pilot-name-label{background:rgba(255,255,255,.92);border:1px solid #cfd8cc;border-radius:6px;padding:1px 6px;color:#1f2c24;font-size:11px;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,.18);}
.warn-mark{color:#c22;font-weight:700;}
.leaflet-popup-content .geo-label b,.leaflet-popup-content .geo-label strong{font-weight:700;}
.map-legend{font-size:12px;color:#5f6b66;margin:8px 0 0;display:flex;gap:10px;flex-wrap:wrap;}
.legend-dot{display:inline-flex;align-items:center;gap:5px;}
.legend-dot i{display:inline-block;width:11px;height:11px;border-radius:50%;}
.map-side{font-size:12px;color:#5f6b66;margin-top:8px;}
</style>";

echo "<div class='rkrk-wrap map-wrap'>";
echo "<div class='cl-header'><h1>Pilotenkarte (Swisstopo)</h1></div>";
echo "<div class='rkrk-card'>";
echo "<div class='map-toolbar'>";
echo "<input type='search' id='map-search' placeholder='Alle Angaben durchsuchen (Name, Drohnentyp, Ort, …)' autocomplete='off'>";
echo "<span class='map-filter-col-wrap'><label for='map-filter-col'>Spalte</label> ";
echo "<select id='map-filter-col' title='Nur diese Listen-Spalte filtern'>";
echo "<option value=''>— alle —</option>";
foreach ($filterColumnOptions as $opt) {
    $sel = (($opt["key"] ?? "") === "drohnentyp") ? " selected" : "";
    echo "<option value='" . rkrk_live_map_esc($opt["key"] ?? "") . "'" . $sel . ">" . rkrk_live_map_esc($opt["label"] ?? "") . "</option>";
}
echo "</select>";
echo " <input type='search' id='map-filter-col-text' list='map-filter-col-list' placeholder='z. B. Drohnentyp …' autocomplete='off'>";
echo "<datalist id='map-filter-col-list'></datalist>";
echo "</span>";
$personelTableHref = "rkrk_assess_live_personel.php";
if (function_exists("rkrk_assess_personel_merge_cs_query")) {
    $personelTableQs = http_build_query(rkrk_assess_personel_merge_cs_query(array()), "", "&", PHP_QUERY_RFC3986);
    if ($personelTableQs !== "") {
        $personelTableHref .= "?" . $personelTableQs;
    }
}
echo "<a class='btn' href='" . rkrk_live_map_esc($personelTableHref) . "'>Personal-Tabelle</a>";
echo "<span id='map-debug' style='margin-left:auto;font-size:12px;color:#5f6b66;'>Lade Punkte ...</span>";
echo "</div>";
echo "<div id='personel-map'></div>";
echo "<div class='map-legend'>
<span class='legend-dot'><i style='background:#c5d0c2'></i>0</span>
<span class='legend-dot'><i style='background:#c44'></i>1</span>
<span class='legend-dot'><i style='background:#c9a227'></i>2</span>
<span class='legend-dot'><i style='background:#4a9d5f'></i>3</span>
<span class='legend-dot'><i style='background:#2d7a45'></i>4</span>
<span class='legend-dot'><i style='background:#2a9d8f'></i>5</span>
<span class='legend-dot'><i style='background:#3d7ab8'></i>6</span>
</div>";
echo "<p class='map-side'>Suche durchsucht alle Listenfelder. Über <strong>Spalte</strong> lässt sich zusätzlich z.&nbsp;B. nach Drohnentyp filtern (Vorschläge erscheinen automatisch). Rechtsklick auf einen Punkt zeigt die Pilotendaten.</p>";
echo "</div></div>";

$geocodeUrlBase = basename(__FILE__) . "?geocode=1";
if (function_exists("rkrk_assess_personel_merge_cs_query")) {
    $geocodeQs = http_build_query(rkrk_assess_personel_merge_cs_query(array()), "", "&", PHP_QUERY_RFC3986);
    if ($geocodeQs !== "") {
        $geocodeUrlBase .= "&" . $geocodeQs;
    }
}

echo "<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' integrity='sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=' crossorigin='anonymous'></script>";
echo "<script>
(function(){
  var points = " . json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";
  var geocodeUrlBase = " . json_encode($geocodeUrlBase, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";
  var swissBounds = L.latLngBounds([45.70, 5.70], [47.95, 10.70]);
  var map = L.map('personel-map', {
    zoomControl:true,
    minZoom: 7,
    maxZoom: 18,
    maxBounds: swissBounds,
    maxBoundsViscosity: 1.0
  }).setView([46.8182, 8.2275], 8);
  L.tileLayer('https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-grau/default/current/3857/{z}/{x}/{y}.jpeg', {
    maxZoom: 19,
    opacity: 0.5,
    noWrap: true,
    bounds: swissBounds,
    attribution: 'Map data &copy; <a href=\"https://www.swisstopo.admin.ch/\">swisstopo</a>'
  }).addTo(map);

  var colorByExp = {0:'#c5d0c2',1:'#c44',2:'#c9a227',3:'#4a9d5f',4:'#2d7a45',5:'#2a9d8f',6:'#3d7ab8'};
  var markers = [];
  var markerLats = [];
  var dbg = document.getElementById('map-debug');
  var searchEl = document.getElementById('map-search');
  var filterColEl = document.getElementById('map-filter-col');
  var filterColTextEl = document.getElementById('map-filter-col-text');
  var filterColListEl = document.getElementById('map-filter-col-list');
  var okCount = 0, failCount = 0;
  function visibleCount(){
    var n = 0;
    markers.forEach(function(m){ if (map.hasLayer(m)) n++; });
    return n;
  }
  function updDbg(){
    if (!dbg) return;
    var vis = visibleCount();
    var extra = vis !== okCount ? (', Filter: ' + vis + ' angezeigt') : '';
    dbg.textContent = 'Punkte: ' + okCount + ' sichtbar, ' + failCount + ' ohne Koordinate' + extra;
  }

  function fieldVal(p, key){
    if (!p) return '';
    if (p.fields && p.fields[key] != null) return String(p.fields[key]);
    if (key === 'telefon') return String(p.phone || '');
    if (key === 'email') return String(p.email || '');
    return String(p[key] || '');
  }
  function matchesFilter(p){
    var q = (searchEl && searchEl.value || '').trim().toLowerCase();
    var col = filterColEl && filterColEl.value || '';
    var colQ = (filterColTextEl && filterColTextEl.value || '').trim().toLowerCase();
    var hay = String(p.search || [p.vorname, p.nachname, p.address, p.email, p.phone, p.drohnentyp, p.title].join(' ')).toLowerCase();
    if (q && hay.indexOf(q) === -1) return false;
    if (col && colQ) {
      var cell = fieldVal(p, col).toLowerCase();
      if (cell.indexOf(colQ) === -1) return false;
    }
    return true;
  }
  function applyFilter(){
    markers.forEach(function(m){
      var show = matchesFilter(m.__pilot || {});
      if (show && !map.hasLayer(m)) m.addTo(map);
      if (!show && map.hasLayer(m)) map.removeLayer(m);
    });
    updDbg();
  }
  function uniqueFieldValues(key){
    var seen = {};
    var out = [];
    points.forEach(function(p){
      var v = fieldVal(p, key).trim();
      if (!v) return;
      var k = v.toLowerCase();
      if (seen[k]) return;
      seen[k] = true;
      out.push(v);
    });
    out.sort(function(a, b){ return a.localeCompare(b, 'de', {sensitivity:'base'}); });
    return out;
  }
  function syncColDatalist(){
    if (!filterColListEl) return;
    var col = filterColEl && filterColEl.value || '';
    filterColListEl.innerHTML = '';
    if (!col) return;
    uniqueFieldValues(col).slice(0, 80).forEach(function(v){
      var opt = document.createElement('option');
      opt.value = v;
      filterColListEl.appendChild(opt);
    });
    if (filterColTextEl) {
      var lab = (filterColEl && filterColEl.options[filterColEl.selectedIndex])
        ? filterColEl.options[filterColEl.selectedIndex].text
        : 'Spalte';
      filterColTextEl.placeholder = lab ? ('Filter ' + lab + ' …') : 'Text in Spalte…';
    }
  }

  function esc(s){
    var m = {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',\"'\":'&#39;'};
    return String(s==null?'':s).replace(/[&<>\"']/g, function(c){ return m[c] || c; });
  }
  function labelHtml(s){
    var raw = String(s==null?'':s);
    var n = 0;
    while (n < 2 && /&lt;\/?[a-z]/i.test(raw)) {
      raw = raw.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '\"');
      n += 1;
    }
    var d = document.createElement('div');
    d.innerHTML = raw;
    Array.prototype.slice.call(d.querySelectorAll('script,style,iframe,object,img,link,meta')).forEach(function(el){ el.remove(); });
    Array.prototype.slice.call(d.querySelectorAll('*')).forEach(function(el){
      var tag = String(el.tagName || '').toLowerCase();
      if (tag === 'b' || tag === 'i' || tag === 'em' || tag === 'strong' || tag === 'br') return;
      var parent = el.parentNode;
      if (!parent) return;
      while (el.firstChild) parent.insertBefore(el.firstChild, el);
      parent.removeChild(el);
    });
    return d.innerHTML;
  }
  function markerIcon(color){
    return L.divIcon({
      className:'pilot-dot',
      html:'<span style=\"display:inline-block;width:14px;height:14px;border-radius:50%;background:'+color+';border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.35)\"></span>',
      iconSize:[14,14],
      iconAnchor:[7,7]
    });
  }
  function popupHtml(p, extra){
    var html = '<strong>'+esc((p.nachname||'') + ' ' + (p.vorname||'')).trim()+'</strong>';
    if (p.address) html += '<br>'+esc(p.address);
    if (p.email) html += '<br><a href=\"mailto:'+esc(p.email)+'\">'+esc(p.email)+'</a>';
    if (p.phone) html += '<br>'+esc(p.phone);
    if (p.drohnentyp) {
      html += '<br>Drohnentyp: ' + (p.warn_drohnentyp ? '<span class=\"warn-mark\">❗</span> ' : '') + esc(p.drohnentyp);
    }
    html += '<br>Stufe: '+esc(p.exp);
    if (extra) html += '<br><span class=\"geo-label\">'+labelHtml(extra)+'</span>';
    return html;
  }

  function geocodeAndPlace(p){
    if (!p.address) return;
    var browserUrl = 'https://api3.geo.admin.ch/rest/services/api/SearchServer?type=locations&limit=1&origins=address&sr=4326&searchText=' + encodeURIComponent(p.address);
    fetch(browserUrl)
      .then(function(r){ return r.ok ? r.json() : Promise.reject(new Error('browser_geocode_http')); })
      .then(function(obj){
        var attrs = obj && obj.results && obj.results[0] && obj.results[0].attrs ? obj.results[0].attrs : null;
        if (!attrs || typeof attrs.lat !== 'number' || typeof attrs.lon !== 'number') {
          return Promise.reject(new Error('browser_geocode_empty'));
        }
        return {ok:true, lat: attrs.lat, lon: attrs.lon, label: attrs.label || p.address};
      })
      .catch(function(){
        return fetch(geocodeUrlBase + '&idx=' + encodeURIComponent(p.idx), {credentials:'same-origin'})
          .then(function(r){return r.json();});
      })
      .then(function(res){
        if (!res || !res.ok || typeof res.lat !== 'number' || typeof res.lon !== 'number') {
          failCount++;
          updDbg();
          return;
        }
        var m = L.marker([res.lat, res.lon], {icon:markerIcon(colorByExp[p.exp] || colorByExp[0])}).addTo(map);
        m.bindPopup(popupHtml(p, res.label || ''), {autoPan:true});
        var nm = ((p.nachname || '') + ' ' + (p.vorname || '')).trim() || (p.title || 'Pilot');
        m.bindTooltip(esc(nm), {permanent:true, direction:'top', offset:[0,-10], className:'pilot-name-label'});
        m.__pilot = p;
        m.on('contextmenu', function(ev){
          var details = {
            name: (p.nachname || '') + ' ' + (p.vorname || ''),
            address: p.address || '',
            email: p.email || '',
            phone: p.phone || '',
            drohnentyp: p.drohnentyp || '',
            warn_drohnentyp: !!p.warn_drohnentyp,
            exp: p.exp,
            lat: ev.latlng.lat,
            lon: ev.latlng.lng
          };
          m.setPopupContent(popupHtml(p, '<code>'+esc(JSON.stringify(details))+'</code>'));
          m.openPopup();
        });
        markers.push(m);
        markerLats.push([res.lat, res.lon]);
        okCount++;
        if (!matchesFilter(p) && map.hasLayer(m)) map.removeLayer(m);
        updDbg();
        if (markerLats.length === 1) {
          map.setView([res.lat, res.lon], 10);
        } else if (markerLats.length % 10 === 0) {
          map.fitBounds(L.latLngBounds(markerLats).pad(0.15));
        }
      })
      .catch(function(){
        failCount++;
        updDbg();
      });
  }

  points.forEach(geocodeAndPlace);
  updDbg();
  setTimeout(function(){
    if (markerLats.length > 1) {
      map.fitBounds(L.latLngBounds(markerLats).pad(0.15));
    }
  }, 1600);

  if (searchEl) searchEl.addEventListener('input', applyFilter);
  if (filterColEl) filterColEl.addEventListener('change', function(){ syncColDatalist(); applyFilter(); });
  if (filterColTextEl) filterColTextEl.addEventListener('input', applyFilter);
  syncColDatalist();
})();
</script>";

