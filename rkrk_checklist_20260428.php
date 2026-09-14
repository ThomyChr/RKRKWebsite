<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php');

	CheckIPandExit();
	headerpage("Drohnenrehkitzrettung.ch", false);
	GrantAccess();

  $currentAccessLevel = '';
  if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
  } elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
  }
  $isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
  $showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
  if ($showTopMenu) {
    CreateTopMenu();
    echo "<br><br>";
    // Prevent duplicate menu bar: keep mobile menu hidden by default and
    // only show it when ToggleMyMenu adds the "show" class.
    echo "<style>#navDemo{display:none !important;}#navDemo.show{display:block !important;}</style>";
    // Hard-fix for duplicate menu rendering on this page.
    echo "<script>
    (function(){
      window.addEventListener('DOMContentLoaded', function(){
        var navBars = document.querySelectorAll('div.top > div.bar.black.card');
        if (navBars.length > 1) {
          for (var i = 1; i < navBars.length; i++) {
            var wrap = navBars[i].parentElement;
            if (wrap) wrap.remove();
          }
        }
        var firstBar = document.querySelector('div.top > div.bar.black.card');
        if (firstBar) {
          var seen = {};
          var nodes = firstBar.querySelectorAll('a.bar-item.button, .dropdown-hover.hide-small');
          for (var j = 0; j < nodes.length; j++) {
            var n = nodes[j];
            var sig = '';
            if (n.classList.contains('dropdown-hover')) {
              var btn = n.querySelector('button');
              sig = 'D:' + (btn ? btn.textContent.trim() : n.textContent.trim());
            } else {
              var href = n.getAttribute('href') || '';
              var txt = n.textContent.trim();
              sig = 'A:' + href + '|' + txt;
            }
            if (!sig) continue;
            if (seen[sig]) {
              n.remove();
            } else {
              seen[sig] = 1;
            }
          }
        }
      });
    })();
    </script>";
  }

//Write to LOGfile
WriteToLog("get", basename($_SERVER['PHP_SELF']), "","","","");

ob_start();

$mode = $_GET['mode'] ?? 'list';
if ($mode === 'templatefields') {
    templateFieldsApi();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
//session_start();

/* MODE SOFORT LESEN */
$mode = $_GET['mode'] ?? 'list';

/* API MODES: DÜRFEN KEIN HTML SEHEN */
if ($mode === 'templatefields') {
    templateFieldsApi(); // gibt JSON aus und exit
}

/* ERST JETZT HTML */
/*
headerpage("Digitale Checkliste", false);
?>
<body>
<?
*/

/* ---------------- FOLDER SETUP ---------------- */
$TEMPLATE_FOLDER  = $RKRKLocalLoactionMissionFiles . "_checklists/templates";
$JSON_FOLDER      = $RKRKLocalLoactionMissionFiles . "_checklists/json";

if (!is_dir($TEMPLATE_FOLDER)) mkdir($TEMPLATE_FOLDER, 0777, true);
if (!is_dir($JSON_FOLDER)) mkdir($JSON_FOLDER, 0777, true);

/* ---------------- NORMALIZE KEYS ---------------- */
function normalizeKey($str){
    $str = trim($str);

    // Umlaute vereinheitlichen
    $str = str_replace(
        ["ä","ö","ü","Ä","Ö","Ü","ß"],
        ["ae","oe","ue","Ae","Oe","Ue","ss"],
        $str
    );

    // Alles Nicht-Alphanumerische → Unterstrich
    $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);

    // Mehrere Unterstriche entfernen
    $str = preg_replace('/_+/', '_', $str);

    return strtolower(trim($str, "_"));
}

function getPresetForLabel($label) {
    $target = normalizeKey($label);

    foreach ($_GET as $k => $v) {
        if (normalizeKey($k) === $target) {
            return $v;
        }
    }

    return null; // no matching preset
}

function rkrk_status_multi_type(){
    return "multi:geplant|auf RC|geprüft|geflogen|Keine Funde|1 Kitz|2 Kitze|andere";
}

function rkrk_is_dynamic_status_label($label){
    return preg_match('/^Status und Suchresultate\s*\([^)]+\)$/u', trim((string)$label)) === 1;
}

function rkrk_is_dynamic_status_other_label($label){
    return preg_match('/^Status und Suchresultate andere\s*\([^)]+\)$/u', trim((string)$label)) === 1;
}

function rkrk_get_field_name_map(){
    $out = array();
    if (function_exists('SetMissionLocation')) {
        SetMissionLocation();
    }

    global $xmlMissionLocation, $RKRKFelderfileXML, $RKRKLocalLoactionMissionFiles, $xmlFileDirectory;
    $xmlFile = trim((string)($RKRKFelderfileXML ?? 'RKRKFelder.xml'));
    $candidates = array();
    $base = trim((string)($xmlMissionLocation ?? ''));
    if ($base !== '') $candidates[] = rtrim($base, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
    $missionBase = trim((string)($RKRKLocalLoactionMissionFiles ?? ''));
    $xmlDir = trim((string)($xmlFileDirectory ?? 'xmlfiles/'));
    if ($missionBase !== '') {
        $candidates[] = rtrim($missionBase, "/\\") . DIRECTORY_SEPARATOR . trim($xmlDir, "/\\") . DIRECTORY_SEPARATOR . $xmlFile;
    }

    $xmlPath = '';
    foreach ($candidates as $cand) {
        if ($cand !== '' && file_exists($cand)) {
            $xmlPath = $cand;
            break;
        }
    }
    if ($xmlPath === '') return $out;

    $xml = null;
    if (function_exists('rkrk_load_xml_file_safe')) {
        $xml = @rkrk_load_xml_file_safe($xmlPath);
    }
    if (!$xml) {
        $raw = @file_get_contents($xmlPath);
        if ($raw !== false) $xml = @simplexml_load_string($raw);
    }
    if (!$xml || !isset($xml->Feld)) return $out;

    foreach ($xml->Feld as $feld) {
        $fid = trim((string)($feld->FeldID ?? ''));
        if ($fid === '') continue;
        $name = trim((string)($feld->Feldname ?? ''));
        if ($name === '') continue;
        $out[$fid] = $name;
    }
    return $out;
}

/* ---------------- SAFE OUTPUT ---------------- */
function safe($v){
    if (is_array($v)) return htmlspecialchars(implode("; ", $v));
    if (!is_string($v)) return "";
    return htmlspecialchars($v);
}

function safeListValue($v){
    if (is_array($v)) $v = implode("; ", $v);
    if (!is_string($v)) return "";
    // Show stored entities like "&amp;" as "&" in list output.
    $decoded = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, "UTF-8");
    return safe($decoded);
}

function redirectTo($url){
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    if (!headers_sent()) {
        header("Location:$url");
        exit;
    }

    $urlEsc = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
    $urlJs = json_encode($url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<script>window.location.href=$urlJs;</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=$urlEsc'></noscript>";
    exit;
}

/* ---------------- LOAD TEMPLATE JSON ---------------- */
function loadTemplate($file){
    global $TEMPLATE_FOLDER;
    $path = "$TEMPLATE_FOLDER/$file";
    if (!file_exists($path)) return null;

    $tpl = json_decode(file_get_contents($path), true);

    if (!$tpl || !isset($tpl["sections"])) {
        return ["title" => $file, "sections" => []];
    }

    $changed = false;

    // Ungültige Feldnamen aus alten Versionen
    $invalidLabels = ["index","id","parent","trigger","undefined","0",""];

    foreach ($tpl["sections"] as $secName => $fields) {

        if (!is_array($fields)) {
            $tpl["sections"][$secName] = [];
            $changed = true;
            continue;
        }

        foreach ($fields as $label => $meta) {

            // ❌ Ungültige Labels entfernen
            if (in_array(trim($label), $invalidLabels, true)) {
                unset($tpl["sections"][$secName][$label]);
                $changed = true;
                continue;
            }

            // Meta muss Array oder Typstring sein
            if (!is_array($meta)) {
                if (is_string($meta) && $meta !== "") {
                    $tpl["sections"][$secName][$label] = [
                        "type"     => $meta,
                        "inname"   => "0",
                        "inresult" => "0"
                    ];
                } else {
                    unset($tpl["sections"][$secName][$label]);
                }
                $changed = true;
                continue;
            }

            // Typ reparieren
            if (!isset($meta["type"]) || trim($meta["type"]) === "") {
                $tpl["sections"][$secName][$label]["type"] = "text";
                $changed = true;
            }

            // Flags reparieren
            if (!isset($meta["inname"])) {
                $tpl["sections"][$secName][$label]["inname"] = "0";
                $changed = true;
            }
            if (!isset($meta["inresult"])) {
                $tpl["sections"][$secName][$label]["inresult"] = "0";
                $changed = true;
            }

            // Placeholder/Info nur Strings
            foreach (["placeholder", "info"] as $opt) {
                if (isset($meta[$opt]) && !is_string($meta[$opt])) {
                    unset($tpl["sections"][$secName][$opt]);
                    $changed = true;
                }
            }
        }
    }

    if ($changed) {
        file_put_contents($path, json_encode($tpl, JSON_PRETTY_PRINT));
    }

    return $tpl;
}

/* ---------------- LOAD CHECKLIST JSON ---------------- */
function loadChecklist($id){
    global $JSON_FOLDER;
    $path = "$JSON_FOLDER/$id.json";
    if (!file_exists($path)) return null;
    return json_decode(file_get_contents($path), true);
}

/* ---------------- AUTO DEPENDENT FIELD PARSER ---------------- */
function parseDependentField($label){
    $label = trim((string)$label);
    if ($label === '') return null;

    // Flexible separators:
    // - "=" with optional spaces (e.g. "Parent=ja", "Parent = ja")
    // - "–" with optional spaces
    // - "-" only with spaces around to avoid breaking names like "Kompass-Kalibrierung"
    $patterns = [
        '/^(.+?)\s*=\s*(.+)$/u',
        '/^(.+?)\s*–\s*(.+)$/u',
        '/^(.+?)\s+-\s+(.+)$/u',
    ];

    foreach ($patterns as $rx) {
        if (preg_match($rx, $label, $m)) {
            return [
                "parent"  => trim($m[1]),
                "trigger" => trim($m[2])
            ];
        }
    }

    return null;
}

/* ---------------- HEADER ---------------- */
function pageHeaderLocal($title){
    global $IconLoc, $mode;

    // Fallback falls $mode nicht gesetzt ist
    if (!isset($mode)) {
        $mode = $_GET['mode'] ?? 'list';
    }

    echo "
    <link rel='stylesheet' href='template/flex.css'>
	<div class='rkrk-wrap' data-mode='".safe($mode)."'>
      <div class='cl-header'>
        <h1>".safe($title)."</h1>
		<!--
        <img src='" . $IconLoc . "/logo.jpg' style='height:80px; position:absolute; top:20px; right:20px;'><br><br>
		-->
      </div>
    ";
}

/* ---------------- FOOTER + JS ENGINE ---------------- */
function pageFooterLocal(){
    echo "
<script>
// ---------------- DEPENDENT FIELD ENGINE (robust, color-safe) ----------------

// Farbcode am Ende entfernen: \"OK (g)\" -> \"OK\"
function stripColorCode(v){
    if (typeof v !== 'string') return v;
    return v.replace(/\\s*\\([a-z]\\)\\s*$/i, '').trim();
}

document.querySelectorAll('[data-dependent]').forEach(dep => {

    const parentKey = dep.dataset.parentKey;
    const trigger   = dep.dataset.trigger;

    const inputs = document.querySelectorAll('[data-field-key=\"' + parentKey + '\"]');

    function update() {
        let show = false;

        inputs.forEach(i => {

            // RADIO
            if (
                i.type === 'radio' &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // SINGLE CHECKBOX
            if (
                i.type === 'checkbox' &&
                !i.name.endsWith('[]') &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // MULTI CHECKBOX (array)
            if (
                i.type === 'checkbox' &&
                i.name.endsWith('[]') &&
                i.checked &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // TEXT / NUMBER / DATE / DATETIME
            if (
                (i.type === 'text' ||
                 i.type === 'number' ||
                 i.type === 'date' ||
                 i.type === 'datetime-local') &&
                stripColorCode(i.value.trim()) === stripColorCode(trigger)
            ) {
                show = true;
            }

            // SELECT
            if (
                i.tagName === 'SELECT' &&
                stripColorCode(i.value) === stripColorCode(trigger)
            ) {
                show = true;
            }
        });

        dep.classList.toggle('visible', show);
    }

    inputs.forEach(i => i.addEventListener('change', update));
    update();
});

// ---------------- COLLAPSIBLE SECTIONS ----------------
document.querySelectorAll('.section-title-box').forEach(title=>{
    const fields = title.nextElementSibling;
    if (!fields) return;

    const id = title.dataset.sectionId;
    const key = 'cl_section_' + id;

    if(localStorage.getItem(key) === '1'){
        fields.classList.add('collapsed');
        title.classList.add('open');
    }

    title.addEventListener('click', ()=>{
        const collapsed = fields.classList.toggle('collapsed');
        title.classList.toggle('open', collapsed);
        localStorage.setItem(key, collapsed ? '1' : '0');
    });
});

// ---------------- INFO POPUP (MODAL WINDOW) ----------------
document.querySelectorAll('.info-icon').forEach(icon => {
    icon.addEventListener('click', () => {
        const text = icon.dataset.info;

        const overlay = document.createElement('div');
        overlay.className = 'info-overlay';

        const popup = document.createElement('div');
        popup.className = 'info-popup';
        popup.innerHTML =
            \"<div class='info-popup-close'>✖</div>\" +
            \"<div style='padding-top:5px;'>\" +
            text.replace(/\\\\n/g, \"<br>\") +
            \"</div>\";

        document.body.appendChild(overlay);
        document.body.appendChild(popup);

        popup.querySelector('.info-popup-close').addEventListener('click', () => {
            popup.remove();
            overlay.remove();
        });

        overlay.addEventListener('click', () => {
            popup.remove();
            overlay.remove();
        });
    });
});

// ---------------- N/R CHIP TOGGLING ----------------
document.querySelectorAll('.nr-chip input').forEach(chk => {
    chk.addEventListener('change', () => {
        const chip = chk.closest('.nr-chip');
        if (chk.checked) {
            chip.classList.add('active');
        } else {
            chip.classList.remove('active');
        }
    });
});

</script>
</div>";
}

/* -----------------------------------------------------------
   TEMPLATE MANAGER
----------------------------------------------------------- */
function showTemplateManager(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
		
    pageHeaderLocal("Checklisten‑Templates");

	if ($isGuest){
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='?mode=list' class='btn'>⬅ Zurück</a>
              </div>";
        pageFooterLocal();
        return;
    }
	
    echo "<div class='rkrk-card'>
            <h3>Neues Template erstellen</h3>
            <form method='post' action='?mode=createtemplate'>
                <input type='text' name='title' placeholder='Template‑Titel' class='text-input'>
                <button class='btn btn-primary'>Template anlegen</button>
            </form>
          </div>";

    echo "<div class='rkrk-card'>
            <h3>Bestehende Templates</h3>
            <table class='list-table'>
                <tr>
                    <th>Titel</th>
                    <th>Datei</th>
                    <th>Aktion</th>
                </tr>";

    foreach (glob("$TEMPLATE_FOLDER/*.json") as $file){
        $base  = basename($file);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;

        echo "<tr>
                <td>".safe($title)."</td>
                <td>".safe($base)."</td>
                <td>
                    <a href='?mode=edittemplate&file=$base'>Bearbeiten</a> |
                    <a href='?mode=copytemplate&file=$base'
                       onclick='return confirm(\"Template wirklich kopieren?\")'>
                       Kopieren
                    </a> |
                    <a href='?mode=form&file=$base'>Neue Checkliste</a> |
                    <a href='?mode=deletetemplate&file=$base'
                       onclick='return confirm(\"Template wirklich löschen?\")'>
                       Löschen
                    </a>
                </td>
              </tr>";
    }

    echo "</table></div>";

    echo "<div style='text-align:center;margin-top:20px;'>
            <a href='?mode=list' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE ANLEGEN
----------------------------------------------------------- */
function createTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

     //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates erstellen.</div>";
        return;
    }

    $title = trim($_POST['title'] ?? "");
    if ($title === ""){
        redirectTo("?mode=templates");
    }

    $file = slugifyTitle($title) . ".json";
    $path = "$TEMPLATE_FOLDER/$file";

    if (!file_exists($path)){
        $tpl = [
            "title" => $title,
            "sections" => [
                "Allgemein" => [
                    "Pilot" => "text"
                ]
            ]
        ];
        file_put_contents($path, json_encode($tpl, JSON_PRETTY_PRINT));
    }

    redirectTo("?mode=edittemplate&file=$file");
}
/* -----------------------------------------------------------
   TEMPLATE LÖSCHEN
----------------------------------------------------------- */
function deleteTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates löschen.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $path = "$TEMPLATE_FOLDER/$file";

    if (file_exists($path)) unlink($path);

    redirectTo("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE KOPIEREN
----------------------------------------------------------- */
function copyTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates kopieren.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $src  = "$TEMPLATE_FOLDER/$file";

    if (!file_exists($src)) {
        redirectTo("?mode=templates");
    }

    $tpl = loadTemplate($file);
    if (!$tpl) {
        redirectTo("?mode=templates");
    }

    $title = $tpl["title"] ?? "template";
    $baseName = slugifyTitle($title);
    $baseName = trim($baseName, "_");

    if ($baseName === "") {
        $baseName = "template";
    }

    $newFile = $baseName . ".json";
    $dst = "$TEMPLATE_FOLDER/$newFile";

    $i = 1;
    while (file_exists($dst)) {
        $newFile = $baseName . "_copy_" . $i . ".json";
        $dst = "$TEMPLATE_FOLDER/$newFile";
        $i++;
    }

    file_put_contents($dst, json_encode($tpl, JSON_PRETTY_PRINT));

    redirectTo("?mode=edittemplate&file=$newFile");
}
/* -----------------------------------------------------------
   TEMPLATE EDITOR
----------------------------------------------------------- */
function editTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $tpl  = loadTemplate($file);

    if (!$tpl){
        pageHeaderLocal("Template nicht gefunden");
        echo "<div class='rkrk-card'>Template existiert nicht.</div>";
        pageFooterLocal();
        return;
    }

    $title    = $tpl["title"];
    $sections = $tpl["sections"];

    pageHeaderLocal("Template bearbeiten");

    echo "<div class='rkrk-card'>
        <form method='post' action='?mode=savetemplate'>
		<input type='hidden' id='weather_json' name='weather_json'>
        <input type='hidden' name='file' value='".safe($file)."'>";
?>
<script>
async function loadWeatherForChecklist() {

    // 1) Standort aus der Checkliste holen (falls vorhanden)
    let lat = null;
    let lon = null;

    const latField = document.querySelector('[data-field-key="lat"]');
    const lonField = document.querySelector('[data-field-key="lon"]');

    if (latField && lonField) {
        lat = parseFloat(latField.value);
        lon = parseFloat(lonField.value);
    }

    // Falls keine Koordinaten → Abbrechen
    if (!lat || !lon) return;

    // 2) Open-Meteo API abrufen
    const url =
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}` +
      `&timezone=auto&current=temperature_2m,relative_humidity_2m,dew_point_2m,weather_code,wind_speed_80m,wind_direction_10m,wind_gusts_10m,visibility,precipitation_probability,cloud_cover`;

    try {
        const res = await fetch(url);
        const weather = await res.json();

        // 3) Snapshot bauen (wie in deiner Wetterbox)
        const cur = weather.current || {};

        const snapshot = {
            time: cur.time,
            weather_code: cur.weather_code,
            temp_c: cur.temperature_2m,
            humidity_pct: cur.relative_humidity_2m,
            dewpoint_c: cur.dew_point_2m,
            precip_prob_pct: cur.precipitation_probability,
            visibility_m: cur.visibility,
            cloud_cover_pct: cur.cloud_cover,
            wind_80m_kmh: cur.wind_speed_80m,
            wind_dir_deg: cur.wind_direction_10m,
            wind_gusts_kmh: cur.wind_gusts_10m
        };

        // 4) Speichern im Hidden Field
        document.getElementById("weather_json").value = JSON.stringify({
            snapshot: snapshot
        });

    } catch (e) {
        console.warn("Wetter konnte nicht geladen werden:", e);
    }
}

// Beim Laden der Checkliste automatisch Wetter laden
document.addEventListener("DOMContentLoaded", loadWeatherForChecklist);
</script>
<?php
    echo "<div class='field'>
            <label class='field-label'>Template‑Titel</label>
            <input type='text' name='title' value='".safe($title)."'>
          </div>";

    echo "<h3>Sektionen & Felder</h3>";

    echo "<table class='template-table'>
            <tr>
                <th>Sektion</th>
                <th>Label</th>
                <th>Typ</th>
                <th style='width:450px'>Optionen</th>
                <th>Placeholder</th>
                <th style='width:450px'>Info</th>
                <th>N/R</th>
                <th>Aktion</th>
            </tr>";

    $flat = [];
    foreach ($sections as $secName => $fields){
        foreach ($fields as $label => $type){
            $placeholder = "";
            $info = "";
            $inname = "";
            $inresult = "";
            $typeVal = $type;

            if (is_array($type)){
                $placeholder = $type["placeholder"] ?? "";
                $info = $type["info"] ?? "";
                $inname = $type["inname"] ?? "0";
                $inresult = $type["inresult"] ?? "0";
                $typeVal = $type["type"] ?? $typeVal;
            }

            $flat[] = [
                "sec"         => $secName,
                "label"       => $label,
                "type"        => $typeVal,
                "placeholder" => $placeholder,
                "info"        => $info,
                "inname"      => $inname,
                "inresult"    => $inresult
            ];
        }
    }

    $prevSec = null;

    foreach ($flat as $i => $row){

        $isFirstRowOfSection = ($row["sec"] !== $prevSec);
        $prevSec = $row["sec"];

        $typeStored = $row["type"];
        $typeBase   = $typeStored;
        $optVal     = "";

        if (strpos($typeStored, "radio:") === 0){
            $typeBase = "radio";
            $optVal = substr($typeStored, 6);
        }
        elseif (strpos($typeStored, "multi:") === 0){
            $typeBase = "multi";
            $optVal = substr($typeStored, 6);
        }

        echo "<tr>";

        echo "<td style='white-space:nowrap'>";
        if ($isFirstRowOfSection){
            echo "<span style='display:inline-block; margin-right:6px'>";
            echo "<button type='submit' name='move_sec_up' value='".safe($row["sec"])."'>⇑</button>";
            echo "<button type='submit' name='move_sec_down' value='".safe($row["sec"])."'>⇓</button>";
            echo "</span>";
        }
        echo "<input type='text' name='sec[]' value='".safe($row["sec"])."' style='width:160px; text-align:right; float:right' placeholder='Sektion'>";
        echo "</td>";

        echo "<td>
                <input type='text' name='label[]' value='".safe($row["label"])."' placeholder='Label'>
              </td>";

        echo "<td><select name='type[]' style='width:100%'>";
        
		
		$types = [ 
		  "text"=>"Text",
		  "textarea"=>"Textarea",
		  "number"=>"Number",
		  "date"=>"Date",
		  "datetime"=>"Datetime",
		  "flugzeit"=>"Flugzeit",
		  "flugort"=>"Flugort",
		  "checkbox"=>"Checkbox",
		  "radio"=>"Radio",
		  "multi"=>"Multi"
		];

        foreach($types as $k=>$v){
            $sel = ($typeBase === $k) ? "selected" : "";
            echo "<option value='".safe($k)."' $sel>".safe($v)."</option>";
        }
        echo "</select></td>";

        echo "<td><textarea name='options[]' rows='2' placeholder='a|b|c'>".safe($optVal)."</textarea></td>";

        echo "<td><input type='text' name='placeholder[]' value='".safe($row["placeholder"])."' placeholder='Platzhalter'></td>";

        echo "<td><textarea name='info[]' rows='2' placeholder='Infomationen (mehrzeilig)'>".safe($row["info"])."</textarea></td>";

        $chkName   = ($row["inname"] === "1") ? "checked" : "";
        $chkResult = ($row["inresult"] === "1") ? "checked" : "";

        echo "<td style='text-align:center'>
                <div class='nr-toggle'>
                    <label class='nr-chip ".($chkName ? "active" : "")."'>
                        N
                        <input type='checkbox' name='inname[$i]' value='1' ".($chkName ? "checked" : "").">
                    </label>

                    <label class='nr-chip ".($chkResult ? "active" : "")."'>
                        R
                        <input type='checkbox' name='inresult[$i]' value='1' ".($chkResult ? "checked" : "").">
                    </label>
                </div>
              </td>";
?>		
		<script>
		document.addEventListener("DOMContentLoaded", () => {
			if (window.weatherData) {
				document.getElementById("weather_json").value = JSON.stringify(window.weatherData);
			}
		});
		</script>
<?php
        echo "<td>
                <button type='submit' name='move_up' value='".$i."'>▲</button>
                <button type='submit' name='move_down' value='".$i."'>▼</button>
                <button type='submit' name='delete_row' value='".$i."'>🗑</button>
              </td>";

        echo "</tr>";
    }

    echo "</table>";

    echo "<h3>Neues Feld hinzufügen</h3>

    <table class='template-table'>
        <tr>
            <th>Sektion</th>
            <th>Label</th>
            <th>Typ</th>
            <th style='width:450px'>Optionen</th>
            <th>Placeholder</th>
            <th style='width:450px'>Info</th>
            <th>N/R</th>
        </tr>

        <tr>
            <td><input type='text' name='new_sec' placeholder='Sektion'></td>
            <td><input type='text' name='new_label' placeholder='Label'></td>
            <td>
                <select name='new_type' class='text-input'>
                    <option value='text'>Text</option>
                    <option value='textarea'>Textarea</option>
                    <option value='number'>Number</option>
                    <option value='date'>Date</option>
                    <option value='datetime'>Datetime</option>
                    <option value='flugzeit'>Flugzeit</option>
                    <option value='flugort'>Flugort</option>
                    <option value='checkbox'>Checkbox</option>
                    <option value='radio'>Radio</option>
                    <option value='multi'>Multi</option>
                </select>
            </td>
            <td><input type='text' name='new_options' placeholder='a|b|c'></td>
            <td><input type='text' name='new_placeholder' placeholder='Platzhalter'></td>
            <td><textarea name='new_info' rows='2' placeholder='Info (mehrzeilig)'></textarea></td>

            <td style='text-align:center'>
                <div class='nr-toggle'>
                    <input type='hidden' name='new_inname' value='0'>
                    <label class='nr-chip'>
                        N
                        <input type='checkbox' name='new_inname' value='1'>
                    </label>

                    <input type='hidden' name='new_inresult' value='0'>
                    <label class='nr-chip'>
                        R
                        <input type='checkbox' name='new_inresult' value='1'>
                    </label>
                </div>
            </td>
        </tr>
    </table>

    <button class='btn btn-primary'>Template speichern</button>
    </form>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const weatherBox = document.getElementById('weatherBox');
		const hidden = document.getElementById('weather_json');

		if (!weatherBox || !hidden) return;

		const syncWeather = () => {
			const dataEl = weatherBox.querySelector('#weather-data');
			if (dataEl && dataEl.dataset.weatherJson) {
				hidden.value = dataEl.dataset.weatherJson;
			}
		};

		syncWeather();

		new MutationObserver(syncWeather).observe(weatherBox, {
			childList: true,
			subtree: true
		});
	});
</script>
    </div>";

    echo "<div style='text-align:center;margin-top:20px;'>
            <a href='?mode=templates' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE SPEICHERN
----------------------------------------------------------- */
function saveTemplate(){
    global $TEMPLATE_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates speichern.</div>";
        return;
    }

    $file  = basename($_POST['file']);
    $title = trim($_POST['title'] ?? "");

    /* Template-Dateiname ggf. ändern */
    $oldFile = $file;
    $newFile = slugifyTitle($title) . ".json";

    if ($newFile !== $oldFile) {
        $oldPath = "$TEMPLATE_FOLDER/$oldFile";
        $newPath = "$TEMPLATE_FOLDER/$newFile";

        /* Falls Zielname existiert → _2, _3, ... */
        if (file_exists($newPath)) {
            $base = pathinfo($newFile, PATHINFO_FILENAME);
            $i = 2;
            do {
                $newFile = $base . "_" . $i . ".json";
                $newPath = "$TEMPLATE_FOLDER/$newFile";
                $i++;
            } while (file_exists($newPath));
        }

        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }

        /* Ab hier mit neuem Dateinamen weiterarbeiten */
        $file = $newFile;
    }

    /* Eingabefelder einlesen */
    $secArr   = $_POST['sec']   ?? [];
    $labelArr = $_POST['label'] ?? [];
    $typeArr  = $_POST['type']  ?? [];
    $placeholderArr = $_POST['placeholder'] ?? [];
    $infoArr  = $_POST['info'] ?? [];
    $optionsArr = $_POST['options'] ?? [];
    $innameArr = $_POST['inname'] ?? [];
    $inresultArr = $_POST['inresult'] ?? [];

    /* Flache Liste erzeugen */
    $flat = [];
    foreach ($secArr as $i => $sec){
        $sec   = trim($sec);
        $label = trim($labelArr[$i] ?? "");
        $typeBase = trim($typeArr[$i] ?? "");
        $opts = trim($optionsArr[$i] ?? "");
        $placeholder = trim($placeholderArr[$i] ?? "");
        $info = trim($infoArr[$i] ?? "");

        $inname   = ($innameArr[$i] ?? "0") === "1" ? "1" : "0";
        $inresult = ($inresultArr[$i] ?? "0") === "1" ? "1" : "0";

        if ($sec === "" || $label === "") continue;

        if ($typeBase === "radio" && $opts !== "") $type = "radio:" . $opts;
        elseif ($typeBase === "multi" && $opts !== "") $type = "multi:" . $opts;
        else $type = $typeBase;

        $flat[] = [
            "sec"         => $sec,
            "label"       => $label,
            "type"        => $type,
            "placeholder" => $placeholder,
            "info"        => $info,
            "inname"      => $inname,
            "inresult"    => $inresult
        ];
    }

    /* Reihenfolge ändern */
    if (isset($_POST['move_up'])) {
        $i = intval($_POST['move_up']);
        if ($i > 0) {
            $tmp = $flat[$i];
            $flat[$i] = $flat[$i-1];
            $flat[$i-1] = $tmp;
        }
    }

    if (isset($_POST['move_down'])) {
        $i = intval($_POST['move_down']);
        if ($i < count($flat)-1) {
            $tmp = $flat[$i];
            $flat[$i] = $flat[$i+1];
            $flat[$i+1] = $tmp;
        }
    }

    /* Sektionen verschieben */
    if (isset($_POST['move_sec_up'])) {
        $secName = $_POST['move_sec_up'];
        $secKeys = array_values(array_unique(array_column($flat, "sec")));
        $pos = array_search($secName, $secKeys);

        if ($pos > 0) {
            $tmp = $secKeys[$pos];
            $secKeys[$pos] = $secKeys[$pos-1];
            $secKeys[$pos-1] = $tmp;
        }

        $newFlat = [];
        foreach ($secKeys as $sec) {
            foreach ($flat as $row) {
                if ($row["sec"] === $sec) $newFlat[] = $row;
            }
        }
        $flat = $newFlat;
    }

    if (isset($_POST['move_sec_down'])) {
        $secName = $_POST['move_sec_down'];
        $secKeys = array_values(array_unique(array_column($flat, "sec")));
        $pos = array_search($secName, $secKeys);

        if ($pos < count($secKeys)-1) {
            $tmp = $secKeys[$pos];
            $secKeys[$pos] = $secKeys[$pos+1];
            $secKeys[$pos+1] = $tmp;
        }

        $newFlat = [];
        foreach ($secKeys as $sec) {
            foreach ($flat as $row) {
                if ($row["sec"] === $sec) $newFlat[] = $row;
            }
        }
        $flat = $newFlat;
    }

    /* Zeile löschen */
    if (isset($_POST['delete_row'])) {
        $i = intval($_POST['delete_row']);
        unset($flat[$i]);
        $flat = array_values($flat);
    }

    /* Neues Feld hinzufügen */
    if (!empty($_POST['new_label'])) {
        $newSec   = trim($_POST['new_sec']);
        $newLabel = trim($_POST['new_label']);
        $newType  = trim($_POST['new_type']);
        $newOpts  = trim($_POST['new_options']);
        $newPlaceholder = trim($_POST['new_placeholder']);
        $newInfo  = trim($_POST['new_info']);

        $newinname   = ($_POST['new_inname'] ?? "0") === "1" ? "1" : "0";
        $newinresult = ($_POST['new_inresult'] ?? "0") === "1" ? "1" : "0";

        if (($newType === "radio" || $newType === "multi") && $newOpts !== "") {
            $newType = $newType . ":" . $newOpts;
        }

        $flat[] = [
            "sec"         => $newSec,
            "label"       => $newLabel,
            "type"        => $newType,
            "placeholder" => $newPlaceholder,
            "info"        => $newInfo,
            "inname"      => $newinname,
            "inresult"    => $newinresult
        ];
    }

    /* Struktur wiederherstellen */
    $sections = [];
    foreach ($flat as $row){
        if (!isset($sections[$row["sec"]])) $sections[$row["sec"]] = [];

        $entry = ["type" => $row["type"]];

        if ($row["placeholder"] !== "") $entry["placeholder"] = $row["placeholder"];
        if ($row["info"] !== "") $entry["info"] = $row["info"];

        $entry["inname"]   = $row["inname"] === "1" ? "1" : "0";
        $entry["inresult"] = $row["inresult"] === "1" ? "1" : "0";

        $sections[$row["sec"]][$row["label"]] = $entry;
    }

    /* Template speichern */
    $tpl = [
        "title" => $title,
        "sections" => $sections
    ];

    file_put_contents("$TEMPLATE_FOLDER/$file", json_encode($tpl, JSON_PRETTY_PRINT));

    /* Redirect robust: Header, sonst JS-Fallback */
    redirectTo("?mode=edittemplate&file=$file");
}

function slugifyTitle($title){
    $title = trim((string)$title);
    $title = str_replace(
        ["ä","ö","ü","Ä","Ö","Ü","ß"],
        ["ae","oe","ue","Ae","Oe","Ue","ss"],
        $title
    );
    $title = preg_replace('/[^a-zA-Z0-9]+/', '_', $title);
    $title = preg_replace('/_+/', '_', $title);
    $title = strtolower(trim($title, '_'));
    return $title !== "" ? $title : "template";
}


function extractColor($label){
    if (preg_match('/\((g|r|o)\)$/i', $label, $m)){
        $colorKey = strtolower($m[1]);

        $map = [
            'g' => '#6bb36b', // green
            'r' => '#d06a6a', // red
            'o' => '#d89b4c', // orange
        ];

        $clean = trim(preg_replace('/\((g|r|o)\)$/i', '', $label));

        return [$clean, $map[$colorKey] ?? null];
    }

    return [$label, null];
}

function formatCHDatetime($raw){
    $raw = trim((string)$raw);
    if ($raw === "") return "";

    // HTML datetime-local
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
    if ($dt) return rkrk_format_display_datetime($dt);

    // ISO mit Sekunden
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw);
    if ($dt) return rkrk_format_display_datetime($dt);

    // Fallback
    try {
        $dt = new DateTime($raw);
        return rkrk_format_display_datetime($dt);
    } catch (Exception $e) {
        return $raw;
    }
}

function rkrk_format_display_datetime(DateTime $dt){
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez'
    ];
    $m = intval($dt->format('n'));
    $mmm = $months[$m] ?? $dt->format('M');
    return sprintf(
        '%02d.%s.%04d %02d:%02d',
        intval($dt->format('d')),
        $mmm,
        intval($dt->format('Y')),
        intval($dt->format('H')),
        intval($dt->format('i'))
    );
}

function formatChecklistDatetime($dtRaw){
    $dtRaw = trim((string)$dtRaw);
    if ($dtRaw === "") return "";

    // 1) HTML datetime-local Format: 2026-03-31T18:33
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dtRaw);

    // 2) Fallback: gespeichertes Format mit Sekunden: 2026-03-31 18:33:00
    if (!$dt) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dtRaw);
    }

    // 3) Fallback: einfach versuchen zu parsen
    if (!$dt) {
        try { $dt = new DateTime($dtRaw); } catch (Exception $e) { return $dtRaw; }
    }

    // Ausgabe: dd.mmm.yyyy hh:mm  -> z.B. 31.Mar.2026 18:33
    return rkrk_format_display_datetime($dt);
}

function formatChecklistDatetimeNumeric($dtRaw){
    $dtRaw = trim((string)$dtRaw);
    if ($dtRaw === "") return "";

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dtRaw);
    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dtRaw);
    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i', $dtRaw);
    if (!$dt) {
        try { $dt = new DateTime($dtRaw); } catch (Exception $e) { return $dtRaw; }
    }
    return $dt->format('d.m.Y H:i');
}

function formatPrintTimeByLabel($label, $value){
    $l = mb_strtolower(trim((string)$label), 'UTF-8');
    if ($l === 'flugzeit' || $l === 'zeit' || $l === 'datum & zeit') {
        return formatChecklistDatetimeNumeric($value);
    }
    return $value;
}

/* -----------------------------------------------------------
   COLORIZE PRINT
----------------------------------------------------------- */
function colorizePrintValue($value, $typeString = ''){
    if ($value === "" || $value === null) return "";

    // Multiline ONLY for textarea or real newlines
    $isTextarea = ($typeString === "textarea");
    $hasNewline = (!is_array($value) && preg_match("/\r\n|\r|\n/", (string)$value));

    if ($isTextarea || $hasNewline) {
        return nl2br(safe((string)$value));
    }

    // Font-color-only styling (color comes from extractColor, same as form options)
    $renderOne = function($raw){
        list($clean, $color) = extractColor($raw);
        if (!$color) {
            return safe($clean);
        }
        return "<span style='color:$color; font-weight:600;'>".safe($clean)."</span>";
    };

    // MULTI (array) → one line, comma-separated
    if (is_array($value)) {
        $out = [];
        foreach ($value as $v) {
            if (trim((string)$v) === "") continue;
            $out[] = $renderOne($v);
        }
        return implode(", ", $out);
    }

    // MULTI stored as string "a, b" or "a; b" → still one line
    $raw = trim((string)$value);
    if (strpos($raw, ",") !== false || strpos($raw, ";") !== false) {
        $parts = preg_split('/\s*[,;]\s*/', $raw);
        if (count($parts) > 1) {
            $out = [];
            foreach ($parts as $p) {
                if (trim($p) === "") continue;
                $out[] = $renderOne($p);
            }
            return implode(", ", $out);
        }
    }

    // SINGLE value
    return $renderOne($raw);
}

/* -----------------------------------------------------------
   CHECKLISTEN-LISTE PRINT
----------------------------------------------------------- */
function showChecklistPrint(){
    global $JSON_FOLDER, $TEMPLATE_FOLDER, $IconLoc;

    $id = basename($_GET['id'] ?? "");
    $path = "$JSON_FOLDER/$id.json";

    if (!file_exists($path)) {
        echo "Checkliste nicht gefunden.";
        exit;
    }

    $data = json_decode(file_get_contents($path), true);
    $tplName = $data["template"] ?? "";
    $tpl = loadTemplate($tplName);

    $title = $tpl["title"] ?? "Checkliste";
    $fields = $data["fields"] ?? [];

    // 🔥 Print-Dateiname = gespeicherter JSON-Dateiname
    $printTitle = $id . " – Druckansicht";

    echo "<!doctype html>
<html lang='de'>
<head>
<meta charset='utf-8'>
<title>".htmlspecialchars($printTitle)."</title>

<style>
body {
    font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;
    margin: 40px;
    color: #1a1a1a;
}

/* Header */
.print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.print-title {
    font-size: 28px;
    font-weight: 700;
    color: #4F6A3C; /* Grün */
}

.print-logo img {
    height: 80px;
    opacity: 0.95;
}

/* Abschnitt */
.section {
    margin-top: 25px;
    border-top: 3px solid #4F6A3C; /* Grün */
    padding-top: 12px;
}

.section h2 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #4F6A3C; /* Grün */
}

/* Zweispaltiges Layout */
.field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 40px;
}

/* Feld */
.field {
    border-bottom: 1px solid #A9C48A; /* hellgrün */
    padding-bottom: 6px;
}

.label {
    font-weight: 600;
    color: #4F6A3C; /* Grün */
}

.value {
    margin-top: 2px;
    color: #000;
}

/* Hervorgehobene Felder */
.highlight .label,
.highlight .value {
    font-weight: 700;
    color: #2F4A1F; /* dunkleres Grün */
}

.wx-print-warn {
    color: #9a5600;
    font-weight: 700;
}

.wx-print-bad {
    color: #8f1624;
    font-weight: 700;
}

/* Print */
@media print {
    body { margin: 20px; }
    .no-print { display: none !important; }
    .wx-print-warn, .wx-print-bad {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}</style>
</head>

<body>
<div class='print-header'>
    <div class='print-title'>".htmlspecialchars($title)."</div>
    <div class='print-logo'>
        <img src='".$IconLoc."/logo.jpg'>
    </div>
</div>
";

    /* ----------------------------------------
       1) TEMPLATE-FELDER DRUCKEN (zweispaltig)
    ---------------------------------------- */
    foreach ($tpl["sections"] as $secName => $secFields) {
        $printSecName = ($secName === "Wetter") ? "Wetter zum Startzeitpunkt" : $secName;
        echo "<div class='section'><h2>".htmlspecialchars($printSecName)."</h2>";
        echo "<div class='field-grid'>";

        foreach ($secFields as $label => $meta) {
            $val = $fields[$secName][$label] ?? "";
            if (is_array($val)) $val = implode(", ", $val);
            $val = formatPrintTimeByLabel($label, $val);
            $valHtml = rkrk_print_weather_value($secName, $label, $val);

            // 🔥 Spezialfelder fett
            $isHighlight = in_array($label, [
                "Datum & Zeit", "Auftrag", "Feld", "Ort", "Flugort", "Flugzeit"
            ]);

            $cls = $isHighlight ? "field highlight" : "field";

            echo "<div class='$cls'>
                    <div class='label'>".htmlspecialchars($label)."</div>
                    <div class='value'>{$valHtml}</div>
                  </div>";
        }

        echo "</div></div>";
    }

    /* ----------------------------------------
       2) ZUSÄTZLICHE FELDER DRUCKEN
    ---------------------------------------- */
    $extra = [];

    foreach ($fields as $secName => $secFields) {
        foreach ($secFields as $label => $value) {
            if (!isset($tpl["sections"][$secName][$label])) {
                $extra[$secName][$label] = $value;
            }
        }
    }

    if (!empty($extra)) {
        echo "<div class='section'><h2>Zusätzliche Felder</h2>";
        echo "<div class='field-grid'>";

        foreach ($extra as $secName => $secFields) {
            foreach ($secFields as $label => $value) {
                if (is_array($value)) $value = implode(", ", $value);
                $value = formatPrintTimeByLabel($label, $value);
                $valueHtml = rkrk_print_weather_value($secName, $label, $value);

                echo "<div class='field'>
                        <div class='label'>".htmlspecialchars($label)."</div>
                        <div class='value'>{$valueHtml}</div>
                      </div>";
            }
        }

        echo "</div></div>";
    }

    echo "</body></html>";
}

/* -----------------------------------------------------------
   CHECKLISTEN-LISTE
----------------------------------------------------------- */
function showChecklistList($showAll = false){
    global $JSON_FOLDER, $TEMPLATE_FOLDER;

    // Lokaler Modus – verhindert Undefined Variable Notices
    $mode = $_GET['mode'] ?? 'list';

    pageHeaderLocal("Checklisten");

    if(isset($_GET['saved']))
        echo "<div class='rkrk-card success'>✓ Checkliste gespeichert.</div>";

    if(isset($_GET['deleted']))
        echo "<div class='rkrk-card danger'>✓ Eintrag gelöscht.</div>";

    echo "<div class='rkrk-card checklist-header'>
        <div class='checklist-title'>Neue Checkliste</div>
        <div class='checklist-buttons'>";

    foreach (glob("$TEMPLATE_FOLDER/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;

        echo "<a href='?mode=form&file=$base' class='btn btn-primary'>
                ➕ ".safe($title)."
              </a>";
    }

    echo "</div></div>";

    echo "<div class='rkrk-card'>
            <h3>".($showAll ? "Alle gespeicherten Checklisten" : "Letzte 12 gespeicherte Checklisten")."</h3>";

    $fltTemplate = trim((string)($_GET['flt_template'] ?? ''));
    $fltPilot    = trim((string)($_GET['flt_pilot'] ?? ''));
    $fltText     = trim((string)($_GET['flt_text'] ?? ''));
    $fltFrom     = trim((string)($_GET['flt_from'] ?? ''));
    $fltTo       = trim((string)($_GET['flt_to'] ?? ''));

    if ($showAll) {
        echo "<form method='get' action='' id='listallFilterForm' style='margin:10px 0 14px 0; display:flex; gap:8px; flex-wrap:wrap; align-items:end;'>
                <input type='hidden' name='mode' value='listall'>
                <div>
                    <label style='font-size:12px; color:#666;'>Template</label><br>
                    <input type='text' data-live-filter='1' name='flt_template' value='".safe($fltTemplate)."' style='min-width:180px;' placeholder='z.B. Rehkitzsuche'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Pilot</label><br>
                    <input type='text' data-live-filter='1' name='flt_pilot' value='".safe($fltPilot)."' style='min-width:140px;' placeholder='z.B. Thomy'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Textsuche</label><br>
                    <input type='text' data-live-filter='1' name='flt_text' value='".safe($fltText)."' style='min-width:220px;' placeholder='Name/Info enthält...'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Von</label><br>
                    <input type='date' data-live-filter='1' name='flt_from' value='".safe($fltFrom)."'>
                </div>
                <div>
                    <label style='font-size:12px; color:#666;'>Bis</label><br>
                    <input type='date' data-live-filter='1' name='flt_to' value='".safe($fltTo)."'>
                </div>
                <a class='btn' href='?mode=listall'>Reset</a>
              </form>
              <script>
              (function(){
                var form = document.getElementById('listallFilterForm');
                if (!form) return;
                var t = null;
                var submitSoon = function(){
                  clearTimeout(t);
                  t = setTimeout(function(){ form.submit(); }, 220);
                };
                form.querySelectorAll('[data-live-filter=\"1\"]').forEach(function(el){
                  el.addEventListener('input', submitSoon);
                  el.addEventListener('change', submitSoon);
                });
              })();
              </script>";
    }

    $jsonFiles = glob("$JSON_FOLDER/*.json");
    usort($jsonFiles, fn($a,$b) => filemtime($b) - filemtime($a));

    if (!$showAll) {
        $jsonFiles = array_slice($jsonFiles, 0, 12);
    }

    echo "<table class='list-table'>
            <tr>
                <th>Template</th>
                <th>Datum & Zeit</th>
                <th>Name</th>
                <th>Pilot</th>
                <th>Information</th>
                <th>Aktion</th>
            </tr>";

    foreach ($jsonFiles as $jsonFile){

        $id   = basename($jsonFile, ".json");
        $data = loadChecklist($id);

        if (!$data) continue;

        /* Template laden – robust */
        $tplName = $data["template"] ?? "";
        $tpl = null;

        if ($tplName !== "") {
            // 1) exakter Name
            $tpl = loadTemplate($tplName);

            // 2) falls ohne .json gespeichert → ergänzen
            if (!$tpl && file_exists("$TEMPLATE_FOLDER/$tplName.json")) {
                $tpl = loadTemplate("$tplName.json");
            }
        }

        /* Falls Template fehlt → leere Struktur */
        if (!$tpl || !isset($tpl["sections"])) {
            $tpl = ["title" => $tplName, "sections" => []];
        }

        $title = $tpl["title"] ?? $tplName;
        $fields = $data["fields"] ?? [];

        /* Standardfelder */
        
		// Flugzeit bevorzugen, falls vorhanden
		$flugzeitRaw = $fields["Allgemein"]["Flugzeit"] ?? "";
		$datumZeitRaw = $fields["Allgemein"]["Datum & Zeit"] ?? "";

		// Formatierung
		if (trim($flugzeitRaw) !== "") {
			$dt = formatChecklistDatetime($flugzeitRaw);
		} else {
			$dt = formatChecklistDatetime($datumZeitRaw);
		}

		$pilotRaw = $fields["Allgemein"]["Pilot"] ?? "";
        $pilot = is_array($pilotRaw) ? implode(", ", $pilotRaw) : $pilotRaw;

        /* -------------------------
           Name (N‑Flag)
        ------------------------- */
        $nameParts = [];

        foreach ($tpl["sections"] as $secName => $secFields){
            foreach ($secFields as $label => $meta){

                if (($meta["inname"] ?? "0") !== "1") continue;

                $val = $fields[$secName][$label] ?? "";

                if (is_array($val)) $val = implode("; ", $val);
                if (trim($val) === "") continue;

                $nameParts[] = $label.": ".$val;
            }
        }

        $name = implode(" | ", $nameParts);

        /* -------------------------
           Information (R‑Flag)
        ------------------------- */
        $infoParts = [];

        foreach ($tpl["sections"] as $secName => $secFields){
            foreach ($secFields as $label => $meta){

                if (($meta["inresult"] ?? "0") !== "1") continue;

                $val = $fields[$secName][$label] ?? "";

                if (is_array($val)) $val = implode("; ", $val);
                if (trim($val) === "") continue;

                $infoParts[] = $label.": ".$val;
            }
        }

        // Dynamische Feld#-Statuszeilen aus gespeicherten Daten ergänzen,
        // auch wenn diese Felder nicht (mehr) im Template existieren.
        $knownInfoLabels = [];
        foreach ($infoParts as $chunk) {
            $p = strpos($chunk, ':');
            if ($p !== false) {
                $knownInfoLabels[trim(substr($chunk, 0, $p))] = true;
            }
        }
        foreach ($fields as $secName => $secValues) {
            if (!is_array($secValues)) continue;
            foreach ($secValues as $label => $val) {
                $label = trim((string)$label);
                if ($label === '') continue;
                if (
                    !rkrk_is_dynamic_status_label($label) &&
                    !rkrk_is_dynamic_status_other_label($label)
                ) {
                    continue;
                }
                if (isset($knownInfoLabels[$label])) continue;
                if (is_array($val)) $val = implode("; ", $val);
                $val = trim((string)$val);
                if ($val === '') continue;
                $infoParts[] = $label . ": " . $val;
                $knownInfoLabels[$label] = true;
            }
        }

        $info = implode(" | ", $infoParts);

        // Filters (active mainly for listall)
        $hayTemplate = mb_strtolower((string)$title);
        $hayPilot    = mb_strtolower((string)$pilot);
        $hayBlob     = mb_strtolower((string)($name . " | " . $info));
        $dtDay = "";
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$flugzeitRaw, $m)) {
            $dtDay = $m[1];
        } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$datumZeitRaw, $m2)) {
            $dtDay = $m2[1];
        }

        if ($fltTemplate !== '' && mb_strpos($hayTemplate, mb_strtolower($fltTemplate)) === false) continue;
        if ($fltPilot !== '' && mb_strpos($hayPilot, mb_strtolower($fltPilot)) === false) continue;
        if ($fltText !== '' && mb_strpos($hayBlob, mb_strtolower($fltText)) === false) continue;
        if ($fltFrom !== '' && $dtDay !== '' && $dtDay < $fltFrom) continue;
        if ($fltTo !== '' && $dtDay !== '' && $dtDay > $fltTo) continue;

        echo "<tr>
                <td>".safeListValue($title)."</td>
                <td>".safeListValue($dt)."</td>
                <td>".safeListValue($name)."</td>
                <td>".safeListValue($pilot)."</td>
                <td>".safeListValue($info)."</td>
                <td class='checklist-actions' style='white-space:nowrap;'>
                    <a class='btn' style='padding:5px 10px; font-size:16px;' title='Bearbeiten' aria-label='Bearbeiten' href='?mode=form&file=$tplName&edit=$id'>✏️</a>
                    <a class='btn' style='padding:5px 10px; font-size:16px;' title='Lesen' aria-label='Lesen' href='?mode=form&file=$tplName&edit=$id&readonly=1'>👁️</a>
                    <a class='btn' style='padding:5px 10px; font-size:16px;' title='Kopieren' aria-label='Kopieren' href='?mode=copyjson&id=$id'>📄</a>
					<a class='btn' style='padding:5px 10px; font-size:16px;' title='Drucken' aria-label='Drucken' href='?mode=print&id=$id' target='_blank'>🖨️</a>
                    <a class='btn btn-danger' style='padding:5px 10px; font-size:16px;' title='Löschen' aria-label='Löschen' href='?mode=deletejson&id=$id'
                       onclick='return confirm(\"Eintrag wirklich löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.\")'>🗑️</a>
                </td>
              </tr>";
    }

    echo "</table></div>";

    echo "<div style='text-align:center; margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;'>

    <a href='?mode=list' 
       class='btn' 
       ".($mode=='list' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Neueste 12 Checklisten
    </a>

    <a href='?mode=listall' 
       class='btn' 
       ".($mode=='listall' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Alle Checklisten
    </a>";
	
	//if (GetAccess("TestOnly")) {
	

		if (GetAccessByFunction ('ChecklistTemplate')) {		
			echo "
				<a href='?mode=templates' class='btn'>
					📁 Templates bearbeiten
				</a>
			";
		}	
		echo "	
			<a href='?mode=linkbuilder' class='btn'>
				🔗 URL Generator
			</a>
		";
	//}
echo "	
</div>
";
    pageFooterLocal();
}

/* -----------------------------------------------------------
   Merge Template with Saved Checklist
----------------------------------------------------------- */
function mergeTemplateWithSaved($templateSections, $savedFields) {
    $merged = [];

    foreach ($templateSections as $secName => $fields) {
        foreach ($fields as $label => $meta) {
            $merged[$secName][$label] = $savedFields[$secName][$label] ?? "";
        }
    }

    foreach ($savedFields as $secName => $fields) {
        foreach ($fields as $label => $value) {
            if (!isset($merged[$secName][$label])) {
                $merged[$secName][$label] = $value;
            }
        }
    }

    return $merged;
}

function applyPresetValue($label, $typeString, $currentValue) {

    // Find matching GET param by normalized key
    $raw = getPresetForLabel($label);
    if ($raw === null) {
        return $currentValue; // nothing to preset
    }

    // Ensure typeString is always a string
    if (is_array($typeString)) {
        $typeString = $typeString['type'] ?? 'text';
    }

    $preset = trim((string)$raw);

    // MULTI: a;b;c or a,b,c
    if (strpos($typeString, "multi:") === 0) {
        $parts = preg_split('/[;,]/', $preset);
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts, fn($x) => $x !== ""));
    }

    // CHECKBOX (single)
    if ($typeString === "checkbox") {
        $p = strtolower($preset);
        return ($p === "1" || $p === "true" || $p === "ja" || $p === "on") ? "1" : "0";
    }

    // RADIO
    if (strpos($typeString, "radio:") === 0) {
        $opts = explode("|", substr($typeString, 6));
        foreach ($opts as $o) {
            if (trim($o) === $preset) {
                return $preset;
            }
        }
        return $currentValue; // invalid preset → ignore
    }

    // TEXT / NUMBER / DATE / DATETIME
    return $preset;
}

/* -----------------------------------------------------------
   Render Felder
----------------------------------------------------------- */
function renderField($secName, $label, $type, $value, $readonly) {

    $placeholder = "";
    $info = "";

    if (is_array($type)) {
        $placeholder = $type["placeholder"] ?? "";
        $info        = $type["info"] ?? "";
        $typeString  = $type["type"] ?? "text";
    } else {
        $typeString = $type;
    }

    $value = applyPresetValue($label, $typeString, $value);
    $key   = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));

    /* Dependencies */
    $dep = parseDependentField($label);
    $depAttr = "";
    $depClass = "";

    if ($dep) {
        $parentKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $dep['parent']));
        $depAttr =
            " data-dependent='1' " .
            " data-parent-key=\"$parentKey\" " .
            " data-trigger=\"" . htmlspecialchars($dep['trigger'], ENT_QUOTES) . "\" ";
        $depClass = " dependent-field ";
    }

    $disabled = $readonly ? "disabled" : "";

    echo "<div class='field $depClass' $depAttr>";
    echo "<label class='field-label'>".safe($label);

    if (!empty($info)) {
        $containsHtml = strip_tags($info) !== $info;

        if ($containsHtml) {
            echo " <span class='info-icon' data-info=\"".htmlspecialchars($info, ENT_QUOTES)."\">ⓘ</span>";
        } else {
            echo " <span class='info-icon'>ⓘ
                    <span class='info-tooltip'>".nl2br(safe($info))."</span>
                   </span>";
        }
    }

    echo "</label>";

    if (!empty($placeholder) &&
        (strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0)) {

        echo "<small class='field-placeholder'>".safe($placeholder)."</small>";
    }

    /* Helper: builds inline style so selected background == border color */
 
	$buildColorStyle = function($color, $isSelected) {
        if (!$color) return "";
        $parts = [];
        $parts[] = "border-color:$color";
        $parts[] = "--option-color:$color";
        //if ($isSelected) {
            // This is the key change: selected background = border color
            //$parts[] = "background:$color";
        //}
        return "style='" . implode("; ", $parts) . ";'";
    };

    /* -------------------------
       RADIO (mit Farbcodes)
    ------------------------- */
    if (strpos($typeString, "radio:") === 0) {

        $opts = explode("|", substr($typeString, 6));
        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = ($value === $oTrim);

            list($cleanLabel, $color) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);

            echo "<label class='color-option$selectedClass' $style>
                    <input type='radio'
                           name='fields[$secName][$label]'
                           data-field-key=\"$key\"
                           value='".safe($oTrim)."'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                  </label>";
        }

        echo "</div>";
    }

    /* -------------------------
       MULTI (mit Farbcodes)
    ------------------------- */
    elseif (strpos($typeString, "multi:") === 0) {

        $opts     = explode("|", substr($typeString, 6));
        $selected = is_array($value) ? $value : explode(";", (string)$value);

        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = in_array($oTrim, $selected, true);

            list($cleanLabel, $color) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);

            echo "<label class='color-option$selectedClass' $style>
                    <input type='checkbox'
                           name='fields[$secName][$label][]'
                           data-field-key=\"$key\"
                           value='".safe($oTrim)."'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                  </label>";
        }

        echo "</div>";
    }

    /* -------------------------
       CHECKBOX (einzeln)
    ------------------------- */
    elseif ($typeString === "checkbox") {

        $isSelected = ($value == "1");

        list($cleanLabel, $color) = extractColor($label);

        $selectedClass = $isSelected ? " selected" : "";
        $style = $buildColorStyle($color, $isSelected);

        echo "<div class='option-grid'>
                <label class='color-option$selectedClass' $style>
                    <input type='checkbox'
                           name='fields[$secName][$label]'
                           data-field-key=\"$key\"
                           value='1'
                           ".($isSelected ? "checked" : "")."
                           $disabled>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                </label>
              </div>";
    }

    /* TEXTAREA */
    elseif ($typeString === "textarea") {
        echo "<textarea
                 name='fields[$secName][$label]'
                 data-field-key=\"$key\"
                 rows='3'
                 placeholder='".safe($placeholder)."'
                 $disabled>"
                .safe($value).
             "</textarea>";
    }

    /* DATETIME */
    elseif ($typeString === "datetime") {
        global $isEditMode;
        $val = (!$isEditMode && ($value === "" || $value === null))
            ? date("Y-m-d\TH:i")
            : $value;

        echo "<input type='datetime-local'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($val)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* DATE */
    elseif ($typeString === "date") {
        $val = $value ?: date("Y-m-d");
        echo "<input type='date'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($val)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* NUMBER */
    elseif ($typeString === "number") {
        echo "<input type='number'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

	/* ------------------------- FLUGZEIT ------------------------- */
	elseif ($typeString === "flugzeit") {
		// identisch wie datetime-local, aber mit eigener Semantik
		global $isEditMode;
		$val = (!$isEditMode && ($value === "" || $value === null)) ? date("Y-m-d\\TH:i") : $value;
		echo "<input id='field_$key' data-field-type='flugzeit' data-field-key='$key' type='datetime-local' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' $disabled />";
	}

	/* ------------------------- FLUGORT ------------------------- */
	elseif ($typeString === "flugort") {
		// normaler Textinput
		$val = (string)$value;
		echo "<input id='field_$key' data-field-type='flugort' data-field-key='$key' type='text' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' placeholder='".htmlspecialchars($placeholder, ENT_QUOTES)."' $disabled />";
	}

    /* STANDARD TEXT */
    else {
        echo "<input type='text'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    echo "</div>";
}

/* -----------------------------------------------------------
   CHECKLIST FORM
----------------------------------------------------------- */



/* -----------------------------------------------------------
   WETTER (Flugort / Flugzeit)
   - Live-Abfrage (Open-Meteo) wenn Flugzeit nicht >6h in der Vergangenheit
   - Speichern der Abfrage in der Checklist-JSON bei vorhandenem Edit-ID
   - Anzeige aus JSON + Hinweis, wenn Flugzeit >6h in der Vergangenheit
   - Anzeige enthält Taupunkt + Windrichtung
   - Zusätzlich: Vorhersage ±1 Stunde um die Flugzeit
   - Ort kann via Ortsname UND/ODER via LAT/LON angegeben werden.
----------------------------------------------------------- */

function rkrk_http_get_json($url){
    // robust: auch wenn allow_url_fopen deaktiviert ist, per curl versuchen
    if (function_exists('curl_init')){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: RKRK-Checklist/1.0'],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $raw === '' || $code >= 400) {
            // fallback to file_get_contents
        } else {
            $json = json_decode($raw, true);
            return is_array($json) ? $json : null;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 8,
            'header'  => "User-Agent: RKRK-Checklist/1.0\r\nAccept: application/json\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || trim($raw) === '') return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function rkrk_parse_flight_dt($raw){
    $raw = trim((string)$raw);
    if ($raw === '') return null;

    $tz = new DateTimeZone(date_default_timezone_get());

    // 1) HTML datetime-local: 2026-04-07T06:00
    $dt = DateTime::createFromFormat('Y-m-d\\TH:i', $raw, $tz);
    if ($dt) return $dt;

    // 2) ISO mit Sekunden: 2026-04-07 06:00:00
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw, $tz);
    if ($dt) return $dt;

    // ✅ 3) DE-Format: 07.04.2026 06:00
    $dt = DateTime::createFromFormat('d.m.Y H:i', $raw, $tz);
    if ($dt) return $dt;

    // 4) Fallback
    try {
        return new DateTime($raw, $tz);
    } catch (Exception $e) {
        return null;
    }
}

function rkrk_is_older_than_6h(DateTime $dt){
    $now = new DateTime('now', $dt->getTimezone());
    return (($now->getTimestamp() - $dt->getTimestamp()) > 6*3600);
}

function rkrk_deg_to_compass($deg){
    if ($deg === null || $deg === '') return '';
    $deg = floatval($deg);
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $idx = (int)round(($deg % 360) / 22.5) % 16;
    return $dirs[$idx];
}

function rkrk_valid_latlon($lat, $lon){
    $lat = str_replace(',', '.', trim((string)$lat));
    $lon = str_replace(',', '.', trim((string)$lon));
    if ($lat === '' || $lon === '') return false;
    if (!is_numeric($lat) || !is_numeric($lon)) return false;
    $lat = floatval($lat);
    $lon = floatval($lon);
    return !($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180);
}

function rkrk_parse_latlon_inline($input){
    $s = trim((string)$input);
    if ($s === '') return null;

    $s2 = preg_replace('/\b(lat|latitude|lon|lng|longitude)\b\s*[:=]?/i', '', $s);
    $s2 = trim($s2);
    $s2 = str_replace([';', '/', "\t", "\n", "\r"], ' ', $s2);
    $s2 = preg_replace('/\s*,\s*/', ' ', $s2);

    $parts = preg_split('/\s+/', trim($s2));
    if (count($parts) < 2) return null;

    $lat = str_replace(',', '.', $parts[0]);
    $lon = str_replace(',', '.', $parts[1]);
    if (!is_numeric($lat) || !is_numeric($lon)) return null;

    $lat = floatval($lat);
    $lon = floatval($lon);
    if ($lat < -90 || $lat > 90) return null;
    if ($lon < -180 || $lon > 180) return null;

    return ['latitude'=>$lat, 'longitude'=>$lon];
}

function rkrk_geocode_open_meteo($name){
    // 1) Koordinaten im Ortsfeld
    $ll = rkrk_parse_latlon_inline($name);
    if ($ll){
        return [
            'name'     => trim((string)$name),
            'latitude' => $ll['latitude'],
            'longitude'=> $ll['longitude'],
            'timezone' => 'auto',
            'admin1'   => null,
            'country'  => null,
            'source'   => 'latlon_inline'
        ];
    }

    // 2) Ortsname → Geocoding
    $nameEnc = rawurlencode($name);
    $url = "https://geocoding-api.open-meteo.com/v1/search?name={$nameEnc}&count=1&language=de&format=json";
    $j = rkrk_http_get_json($url);
    if (!$j || empty($j['results'][0])) return null;
    $r = $j['results'][0];
    return [
        'name'     => $r['name'] ?? $name,
        'latitude' => $r['latitude'] ?? null,
        'longitude'=> $r['longitude'] ?? null,
        'timezone' => $r['timezone'] ?? 'auto',
        'admin1'   => $r['admin1'] ?? null,
        'country'  => $r['country'] ?? null,
        'source'   => 'name'
    ];
}

function rkrk_pick_hourly_window($hourly, $targetTs){
    $times = $hourly['time'] ?? [];
    if (!$times) return [];

    $bestI = 0; $bestD = null;
    foreach ($times as $i => $t){
        $d = abs(intval($t) - intval($targetTs));
        if ($bestD === null || $d < $bestD){ $bestD = $d; $bestI = $i; }
    }

    $out = [];
    foreach ([-1,0,1] as $off){
        $i = $bestI + $off;
        if (!isset($times[$i])) continue;
        $out[] = [
            'time'              => $times[$i],
            'temperature_2m'    => $hourly['temperature_2m'][$i] ?? null,
            'dewpoint_2m'       => $hourly['dewpoint_2m'][$i] ?? null,
            'precipitation_probability' => $hourly['precipitation_probability'][$i] ?? null,
            'cloudcover'        => $hourly['cloudcover'][$i] ?? null,
            'visibility'        => $hourly['visibility'][$i] ?? null,
            'windspeed_10m'     => $hourly['windspeed_10m'][$i] ?? null,
            'windspeed_80m'     => $hourly['windspeed_80m'][$i] ?? null,
            'winddirection_10m' => $hourly['winddirection_10m'][$i] ?? null,
            'winddirection_80m' => $hourly['winddirection_80m'][$i] ?? null,
            'weathercode'       => $hourly['weathercode'][$i] ?? null,
        ];
    }
    return $out;
}

function rkrk_fetch_weather_open_meteo($lat, $lon, DateTime $dt, $timezone='auto'){
    if ($lat === null || $lon === null) return null;
    $date = $dt->format('Y-m-d');

    $vars = 'temperature_2m,dewpoint_2m,precipitation_probability,cloudcover,visibility,windspeed_10m,windspeed_80m,winddirection_10m,winddirection_80m,weathercode';

	$url = "https://api.open-meteo.com/v1/forecast"
	 . "?latitude={$lat}"
	 . "&longitude={$lon}"
	 . "&hourly={$vars}"
	 . "&daily=sunrise,sunset"
	 . "&start_date={$date}"
	 . "&end_date={$date}"
	 . "&timezone=" . rawurlencode($timezone)
	 . "&timeformat=unixtime";

    $j = rkrk_http_get_json($url);
    if (!$j || empty($j['hourly']['time'])) return null;

    $hourly = $j['hourly'];
    $target = $dt->getTimestamp();
    $window = rkrk_pick_hourly_window($hourly, $target);
    $sunrise = $j['daily']['sunrise'][0] ?? null;
    $sunset  = $j['daily']['sunset'][0] ?? null;

    return [
        'provider'   => 'open-meteo',
        'fetched_at' => date('Y-m-d H:i:s'),
        'timezone'   => $j['timezone'] ?? $timezone,
        'target_unix'=> $target,
        'window'     => $window,
        'sunrise'    => $sunrise,
        'sunset'     => $sunset,
        'units'      => $j['hourly_units'] ?? [],
        'kp'         => rkrk_fetch_kp_index($target),
    ];
}

function rkrk_kp_label($kp){
    if (!is_numeric($kp)) return '';
    $k = floatval($kp);
    if ($k <= 2) return 'ruhig / leicht aktiv';
    if ($k <= 3) return 'leicht aktiv';
    if ($k <= 4) return 'aktiv';
    if ($k <= 5) return 'stuermisch';
    return 'stark stuermisch';
}

function rkrk_pick_latest_kp_from_rows($rows, $mode = 'object'){
    if (!is_array($rows) || count($rows) === 0) return null;
    $fallback = null;

    for ($i = count($rows)-1; $i >= 0; $i--){
        $row = $rows[$i];
        $kp = null;
        $time = null;

        if ($mode === 'products'){
            if (!is_array($row)) continue;
            $kp = $row['Kp'] ?? ($row['kp'] ?? ($row['kp_index'] ?? ($row[1] ?? null)));
            $time = $row['time_tag'] ?? ($row[0] ?? null);
        } else {
            if (!is_array($row)) continue;
            $kp = $row['kp'] ?? $row['kp_index'] ?? null;
            $time = $row['time_tag'] ?? null;
        }

        if (!is_numeric($kp)) continue;
        $kpf = floatval($kp);
        if (!is_finite($kpf) || $kpf < 0 || $kpf > 9) continue;

        $candidate = [
            'value' => $kpf,
            'label' => rkrk_kp_label($kpf),
            'time_utc' => $time
        ];
        // 0 kann real sein, wird aber nur als Fallback genommen.
        if ($kpf > 0) return $candidate;
        if ($fallback === null) $fallback = $candidate;
    }
    return $fallback;
}

function rkrk_fetch_kp_index($targetTs = null){
    // KPI aus NOAA 3-Day Forecast (3h-Bins)
    $j = rkrk_http_get_json("https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json");
    if (!is_array($j) || count($j) === 0) return null;

    $target = is_numeric($targetTs) ? intval($targetTs) : time();
    $entries = [];

    foreach ($j as $row){
        if (!is_array($row)) continue;
        $t = $row['time_tag'] ?? ($row[0] ?? null);
        $kpRaw = $row['Kp'] ?? ($row['kp'] ?? ($row['kp_index'] ?? ($row[1] ?? null)));
        if (!is_numeric($kpRaw)) continue;
        $kpf = floatval($kpRaw);
        if (!is_finite($kpf) || $kpf < 0 || $kpf > 9) continue;
        $tsRaw = trim((string)$t);
        if ($tsRaw === '') continue;
        $epoch = strtotime($tsRaw . ' UTC');
        if ($epoch === false) {
            $epoch = strtotime($tsRaw);
        }
        if ($epoch === false) continue;
        $entries[] = ['ts' => intval($epoch), 'kp' => $kpf, 'time_utc' => $tsRaw];
    }
    if (!$entries) return null;

    usort($entries, fn($a,$b) => $a['ts'] <=> $b['ts']);
    $picked = null;
    foreach ($entries as $e){
        if ($e['ts'] >= $target){ $picked = $e; break; }
    }
    if ($picked === null) $picked = $entries[count($entries)-1];

    return [
        'value' => $picked['kp'],
        'label' => rkrk_kp_label($picked['kp']),
        'time_utc' => $picked['time_utc']
    ];
}

function rkrk_estimate_visible_satellites($cloudCoverPct, $targetTs, $sunriseTs=null, $sunsetTs=null){
    // GNSS-Sichtbarkeit ist nicht vom Wetter/Tag-Nacht abhängig.
    // Ohne TLE-Berechnung nutzen wir eine konservative GPS-dominierte Schätzung,
    // die in Mitteleuropa typischerweise um ~11 liegt.
    $elevationMaskDeg = 10.0;
    $totalGnssSat = 31.0;
    $fraction = (1.0 - sin(deg2rad($elevationMaskDeg))) / 2.0;
    $estimate = intval(round($totalGnssSat * $fraction * 0.9));
    return max(8, min(16, $estimate));
}

/* -----------------------------------------------------------
   ZENTRALE KONFIGURATION: Kritische Wetterwerte
----------------------------------------------------------- */
function rkrk_weather_critical_config(){
    return [
        'wind_80m_kmh_max' => 28.0,
        'precipitation_probability_pct_max' => 60.0,
        'cloudcover_pct_max' => 85.0,
        'visibility_km_warn' => 2.0,
        'visibility_km_bad' => 0.5,
        'visibility_km_min' => 0.5,
        'kp_index_warn' => 5.0,
        'kp_index_bad' => 7.0,
        'kp_index_min' => 7.0
    ];
}

function rkrk_first_number($value){
    if (is_numeric($value)) return floatval($value);
    $s = str_replace(',', '.', trim((string)$value));
    if (preg_match('/-?\d+(?:\.\d+)?/', $s, $m)) return floatval($m[0]);
    return null;
}

function rkrk_visibility_km_from_value($value){
    $raw = trim((string)$value);
    if ($raw === '') return null;
    $n = rkrk_first_number($raw);
    if ($n === null) return null;
    $s = mb_strtolower($raw, 'UTF-8');
    if (strpos($s, 'km') !== false) return $n;
    if (strpos($s, ' m') !== false || preg_match('/\d\s*m$/u', $s)) return $n / 1000.0;
    if ($n > 50) return $n / 1000.0; // nackte Meterwerte (z.B. 1800)
    return $n; // bereits km
}

function rkrk_is_weather_value_critical($metric, $value){
    $cfg = rkrk_weather_critical_config();
    $n = rkrk_first_number($value);
    if ($n === null) return false;
    switch ($metric){
        case 'wind_80m_kmh':
            return $n >= $cfg['wind_80m_kmh_max'];
        case 'precipitation_probability':
            return $n >= $cfg['precipitation_probability_pct_max'];
        case 'cloudcover':
            return $n >= $cfg['cloudcover_pct_max'];
        case 'visibility_km':
            $km = rkrk_visibility_km_from_value($value);
            if ($km === null) return false;
            return $km < $cfg['visibility_km_bad'];
        case 'kp_index':
            return $n > $cfg['kp_index_bad'];
        default:
            return false;
    }
}

function rkrk_mark_weather_value($value, $level){
    $safeVal = safe((string)$value);
    $lvl = ($level === true) ? 'bad' : (string)$level;
    if ($lvl === 'bad') return "<span style='color:#b91c1c;font-weight:700;'>⚠️ {$safeVal} (kritisch)</span>";
    if ($lvl === 'warn') return "<span style='color:#9a5600;font-weight:700;'>⚠️ {$safeVal} (Warnung)</span>";
    return $safeVal;
}

function rkrk_kp_level($value){
    $cfg = rkrk_weather_critical_config();
    $n = rkrk_first_number($value);
    if ($n === null) return '';
    if ($n > $cfg['kp_index_bad']) return 'bad';
    if ($n > $cfg['kp_index_warn']) return 'warn';
    return '';
}

function rkrk_weather_print_level($label, $value){
    $lbl = strtolower(trim((string)$label));
    if (strpos($lbl, 'sichtweite') !== false) {
        $cfg = rkrk_weather_critical_config();
        $km = rkrk_visibility_km_from_value($value);
        if ($km === null) return '';
        if ($km < $cfg['visibility_km_bad']) return 'bad';
        if ($km < $cfg['visibility_km_warn']) return 'warn';
        return '';
    }
    $n = rkrk_first_number($value);
    if ($n === null) return '';

    if (strpos($lbl, 'temperatur') !== false) {
        if ($n < 0) return 'bad';
        if ($n < 5) return 'warn';
        return '';
    }
    if (strpos($lbl, 'windb') !== false || strpos($lbl, 'boeen') !== false || strpos($lbl, 'böen') !== false) {
        if ($n >= 36) return 'bad';
        if ($n >= 25) return 'warn';
        return '';
    }
    if (strpos($lbl, 'windrichtung') !== false || strpos($lbl, 'richtung') !== false) {
        return '';
    }
    if (strpos($lbl, 'wind') !== false) {
        if ($n >= 36) return 'bad';
        if ($n >= 25) return 'warn';
        return '';
    }
    if (strpos($lbl, 'regen') !== false && rkrk_is_weather_value_critical('precipitation_probability', $value)) return 'bad';
    if (strpos($lbl, 'bew') !== false && rkrk_is_weather_value_critical('cloudcover', $value)) return 'bad';
    if (strpos($lbl, 'sichtweite') !== false && rkrk_is_weather_value_critical('visibility_km', $value)) return 'bad';
    if (strpos($lbl, 'kp') !== false || strpos($lbl, 'aurora') !== false) {
        return rkrk_kp_level($value);
    }

    return '';
}

function rkrk_print_weather_value($secName, $label, $value){
    $safeVal = safe((string)$value);
    if (strcasecmp((string)$secName, 'Wetter') !== 0) return $safeVal;
    $lvl = rkrk_weather_print_level($label, $value);
    if ($lvl === 'warn') return "<span class='wx-print-warn'>{$safeVal}</span>";
    if ($lvl === 'bad') return "<span class='wx-print-bad'>{$safeVal}</span>";
    return $safeVal;
}

function rkrk_render_weather_html($flugort, DateTime $dt, $geo, $wx, $note=''){
    if (!$wx) {
        return "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
    }
    $isSavedPayload = isset($wx['Temperatur']) || isset($wx['Wind (80m)']) || isset($wx['Wettercode']);
    if ($isSavedPayload) {
        $temp = $wx['Temperatur'] ?? '–';
        $dew  = $wx['Taupunkt'] ?? '–';
        $wind = $wx['Wind (80m)'] ?? ($wx['Wind'] ?? '–');
        $dir  = $wx['Windrichtung'] ?? ($wx['Richtung'] ?? '–');
        $code = $wx['Wettercode'] ?? '–';
        $rain = $wx['Regenwahrscheinlichkeit'] ?? '–';
        $cc   = $wx['Bewölkung'] ?? '–';
        $vis  = $wx['Sichtweite'] ?? '–';
        $kpText = $wx['KPI Aurora'] ?? ($wx['KP Index'] ?? '–');
        $auroraText = $wx['Aurora'] ?? '–';
        $satText = $wx['Sichtbare GNSS Satelliten'] ?? ($wx['Sichtbare Satelliten'] ?? '–');
    } else {
        if (!isset($wx['window']) || !is_array($wx['window']) || count($wx['window']) === 0) {
            return "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
        }
        $c = $wx['window'][1] ?? $wx['window'][0];
        $temp = $c['temperature_2m'] ?? '–';
        $dew  = $c['dewpoint_2m'] ?? '–';
        $windSpeed = $c['windspeed_80m'] ?? $c['windspeed_10m'] ?? null;
        $wind = ($windSpeed === null || $windSpeed === '') ? '–' : ($windSpeed . " km/h");
        $dirRaw = $c['winddirection_80m'] ?? $c['winddirection_10m'] ?? null;
        $dir = ($dirRaw === null || $dirRaw === '') ? '–' : (intval(round($dirRaw)) . "° (" . rkrk_deg_to_compass($dirRaw) . ")");
        $code = $c['weathercode'] ?? '–';
        $rain = $c['precipitation_probability'] ?? '–';
        $cc   = $c['cloudcover'] ?? '–';
        $visRaw = $c['visibility'] ?? null;
        $vis = ($visRaw === null || $visRaw === '') ? '–' : (intval(round($visRaw / 1000)) . " km");
        $kp = $wx['kp']['value'] ?? null;
        $kpLabel = $wx['kp']['label'] ?? '';
        $kpText = ($kp === null || $kp === '') ? '–' : (intval(round($kp)) . " " . $kpLabel);
        $auroraText = ($kpLabel === '') ? '–' : $kpLabel;
        $satCount = rkrk_estimate_visible_satellites(
            $c['cloudcover'] ?? null,
            $wx['target_unix'] ?? $dt->getTimestamp(),
            $wx['sunrise'] ?? null,
            $wx['sunset'] ?? null
        );
        $satText = strval($satCount);
    }

    $critWind = rkrk_is_weather_value_critical('wind_80m_kmh', $wind);
    $critRain = rkrk_is_weather_value_critical('precipitation_probability', $rain);
    $critCloud = rkrk_is_weather_value_critical('cloudcover', $cc);
    $critVis = rkrk_is_weather_value_critical('visibility_km', $vis);
    $kpLevel = rkrk_kp_level($kpText);

    $windOut = rkrk_mark_weather_value($wind, $critWind);
    $rainOut = rkrk_mark_weather_value($rain . " %", $critRain);
    $ccOut = rkrk_mark_weather_value($cc . " %", $critCloud);
    $visOut = rkrk_mark_weather_value($vis, $critVis);
    $kpOut = rkrk_mark_weather_value($kpText, $kpLevel);
    $hasCritical = ($critWind || $critRain || $critCloud || $critVis || $kpLevel === 'bad');

    $html = "<div class='wxcard'>
        <b>Wetter für {$flugort}</b><br>
        <small>" . rkrk_format_display_datetime($dt) . "</small><br><br>

        Temperatur: {$temp} °C<br>
        Taupunkt: {$dew} °C<br>
        Wind (80m): {$windOut}<br>
        Windrichtung: {$dir}<br>
        Regenwahrscheinlichkeit: {$rainOut}<br>
        Bewölkung: {$ccOut}<br>
        Sichtweite: {$visOut}<br>
        KPI Aurora: {$kpOut}<br>
        Aurora: {$auroraText}<br>
        Sichtbare GNSS Satelliten: {$satText}<br>
        Wettercode: {$code}<br>
    ";

    if ($hasCritical) {
        $html .= "<br><span style='color:#b91c1c;font-weight:700;'>⚠️ Kritische Wetterparameter erkannt</span>";
    }

    if ($note !== '') {
        $html .= "<br><i>{$note}</i>";
    }

    return $html . "</div>";
}


function rkrk_load_saved_weather($editId){
    if (!$editId) return null;

    $data = loadChecklist($editId);
    if (!$data) return null;

    return $data['fields']['Wetter'] ?? null;
}


function rkrk_save_weather_to_checklist($editId, $payload){
    if (!$editId) return false;
    global $JSON_FOLDER;

    $path = "$JSON_FOLDER/" . basename($editId) . ".json";
    if (!file_exists($path)) return false;

    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];

    if (!isset($data['fields']) || !is_array($data['fields'])) {
        $data['fields'] = [];
    }
    if (!isset($data['fields']['Wetter'])) {
        $data['fields']['Wetter'] = [];
    }

    // Wetterdaten aus Payload
    $wx  = $payload['wx']  ?? null;
    $geo = $payload['geo'] ?? null;

    // Stabil: wir nehmen IMMER den nächsten Stundenwert
    $c = null;
    if (isset($wx['window']) && is_array($wx['window']) && count($wx['window']) > 0) {
        // Nimm den mittleren Wert (0 = vorher, 1 = exakt, 2 = nachher)
        $mid = floor(count($wx['window']) / 2);
        $c = $wx['window'][$mid];
    }

    if (!$c) return false;

    $windSpeed = $c['windspeed_80m'] ?? $c['windspeed_10m'] ?? "";
    $windDirRaw = $c['winddirection_80m'] ?? $c['winddirection_10m'] ?? null;
    $windDirText = ($windDirRaw === null || $windDirRaw === '')
        ? ""
        : (intval(round($windDirRaw)) . "° (" . rkrk_deg_to_compass($windDirRaw) . ")");
    $visRaw = $c['visibility'] ?? null;
    $visText = ($visRaw === null || $visRaw === '') ? "" : (intval(round($visRaw / 1000)) . " km");
    $kp = $wx['kp']['value'] ?? null;
    $kpLabel = $wx['kp']['label'] ?? '';
    $kpText = ($kp === null || $kp === '') ? "" : (intval(round($kp)) . " " . $kpLabel);
    $satCount = rkrk_estimate_visible_satellites(
        $c['cloudcover'] ?? null,
        $wx['target_unix'] ?? time(),
        $wx['sunrise'] ?? null,
        $wx['sunset'] ?? null
    );

    $data['fields']['Wetter'] = [
        "Ort"                   => $payload['flugort'] ?? "",
        "Zeit"                  => $payload['flugzeit'] ?? "",
        "Temperatur"            => $c['temperature_2m'] ?? "",
        "Taupunkt"              => $c['dewpoint_2m'] ?? "",
        "Regenwahrscheinlichkeit" => $c['precipitation_probability'] ?? "",
        "Wind (80m)"            => ($windSpeed === "" ? "" : ($windSpeed . " km/h")),
        "Windrichtung"          => $windDirText,
        "Bewölkung"             => $c['cloudcover'] ?? "",
        "Sichtweite"            => $visText,
        "KPI Aurora"            => $kpText,
        "KP Index"              => $kpText,
        "Aurora"                => $kpLabel,
        "Sichtbare GNSS Satelliten" => strval($satCount),
        "Sichtbare Satelliten"  => strval($satCount),
        "Wettercode"            => $c['weathercode'] ?? "",
        "Koordinaten"           => ($geo['latitude'] ?? "") . ", " . ($geo['longitude'] ?? ""),
        "Gespeichert am"        => date('Y-m-d H:i:s')
    ];

    $data['modified_at'] = date('Y-m-d H:i:s');

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}


function rkrk_resolve_flugort($flugort){
    $flugort = trim((string)$flugort);
    if ($flugort === '') return null;

    // -------------------------------------------------
    // 1) LAT/LON direkt im Text (z.B. "47.17, 8.52")
    // -------------------------------------------------
    if (preg_match(
        '/^\s*(-?\d{1,2}(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/',
        $flugort,
        $m
    )){
        $lat = floatval(str_replace(',', '.', $m[1]));
        $lon = floatval(str_replace(',', '.', $m[2]));
        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180){
            return [
                'name'      => $flugort,
                'latitude'  => $lat,
                'longitude' => $lon,
                'timezone'  => 'Europe/Zurich',
                'country'   => 'CH',
                'source'    => 'latlon_inline'
            ];
        }
    }

    // -------------------------------------------------
    // 2) Online‑Geocoding (kann fehlschlagen)
    // -------------------------------------------------
    $nameEnc = rawurlencode($flugort);
    $url = "https://geocoding-api.open-meteo.com/v1/search"
         . "?name={$nameEnc}&count=5&language=de&format=json";

    $j = rkrk_http_get_json($url);

    if (is_array($j) && !empty($j['results'])){
        foreach ($j['results'] as $r){
            if (!isset($r['latitude'], $r['longitude'])) continue;
            return [
                'name'      => $r['name'] ?? $flugort,
                'latitude'  => floatval($r['latitude']),
                'longitude' => floatval($r['longitude']),
                'timezone'  => $r['timezone'] ?? 'Europe/Zurich',
                'admin1'    => $r['admin1'] ?? null,
                'country'   => $r['country'] ?? 'CH',
                'source'    => 'geocoding_online'
            ];
        }
    }

    // -------------------------------------------------
    // 3) ✅ HARTE CH‑FALLBACKS (wenn HTTP blockiert ist)
    // -------------------------------------------------
    $chFallback = [
        'zug'    => [47.1724, 8.5174],
        'knonau' => [47.2190, 8.4560],
        'zürich' => [47.3769, 8.5417],
        'bern'   => [46.9480, 7.4474],
        'basel'  => [47.5596, 7.5886],
        'luzern' => [47.0502, 8.3093],
        'aarau'  => [47.3925, 8.0450],
        'chur'   => [46.8508, 9.5320]
    ];

    $key = strtolower($flugort);
    if (isset($chFallback[$key])){
        return [
            'name'      => $flugort,
            'latitude'  => $chFallback[$key][0],
            'longitude' => $chFallback[$key][1],
            'timezone'  => 'Europe/Zurich',
            'country'   => 'CH',
            'source'    => 'ch_fallback'
        ];
    }

    // -------------------------------------------------
    // 4) ❌ Nicht auflösbar
    // -------------------------------------------------
    return null;
}

function rkrk_get_weather_for_flight($flugort, $flugzeitRaw, $editId='', $lat=null, $lon=null){
    $dt = rkrk_parse_flight_dt($flugzeitRaw);
    if (!$dt){
        return [ 'html' => "<div class='system-info-box'>⚠️ Ungültige Flugzeit.</div>" ];
    }

    // Wenn älter als 6h → gespeichertes Wetter
    if (rkrk_is_older_than_6h($dt)){
        $saved = rkrk_load_saved_weather($editId);
        if (!$saved){
            return [ 'html' => "<div class='system-info-box'>ℹ️ Keine gespeicherten Wetterdaten vorhanden.</div>" ];
        }
        return [ 'html' => rkrk_render_weather_html($flugort, $dt, null, $saved, 'gespeichert') ];
    }

    // Geodaten bestimmen
    if (rkrk_valid_latlon($lat, $lon)){
        $geo = [
            'name'      => $flugort,
            'latitude'  => floatval($lat),
            'longitude' => floatval($lon),
            'timezone'  => 'auto'
        ];
    } else {
        $geo = rkrk_resolve_flugort($flugort);
        // Fallback-Kette: alternative Resolver inkl. Koordinaten-im-Text
        if (!$geo) {
            $geo = rkrk_geocode_open_meteo($flugort);
        }
    }

    if (!$geo){
        return [ 'html' => "<div class='system-info-box'>⚠️ Flugort nicht gefunden.</div>" ];
    }

    // Wetter abrufen
    $wx = rkrk_fetch_weather_open_meteo($geo['latitude'], $geo['longitude'], $dt, $geo['timezone']);

    // Speichern
    if ($wx && $editId){
        rkrk_save_weather_to_checklist($editId, [
            'flugort'  => $flugort,
            'flugzeit' => $flugzeitRaw,
            'geo'      => $geo,
            'wx'       => $wx
        ]);
    }

    return [ 'html' => rkrk_render_weather_html($flugort, $dt, $geo, $wx) ];
}


function handleWeatherMode() {
    $ort  = $_GET['flugort']  ?? '';
    $zeit = $_GET['flugzeit'] ?? '';
    $edit = $_GET['edit']     ?? '';
    $lat  = $_GET['lat']      ?? null;
    $lon  = $_GET['lon']      ?? null;

    if (!$ort || !$zeit) {
        echo "<div class='system-info-box'>⚠️ Ungültige Wetteranfrage.</div>";
        return;
    }

    $wx = rkrk_get_weather_for_flight($ort, $zeit, $edit, $lat, $lon);

    echo $wx['html'] ?? "<div class='system-info-box'>⚠️ Keine Wetterdaten verfügbar.</div>";
}

function handleSaveWeatherClientMode() {
    global $JSON_FOLDER;
    $edit = basename($_POST['edit'] ?? '');
    if ($edit === '') {
        echo "ERR";
        return;
    }
    $path = "$JSON_FOLDER/$edit.json";
    if (!file_exists($path)) {
        echo "ERR";
        return;
    }

    $payload = json_decode($_POST['weather'] ?? '', true);
    if (!is_array($payload)) {
        echo "ERR";
        return;
    }
    $zeitRaw = (string)($payload['Zeit'] ?? '');
    $zeitDt = rkrk_parse_flight_dt($zeitRaw);
    if (!$zeitDt || rkrk_is_older_than_6h($zeitDt)) {
        echo "SKIP";
        return;
    }

    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];
    if (!isset($data['fields']) || !is_array($data['fields'])) $data['fields'] = [];

    $data['fields']['Wetter'] = [
        "Ort" => (string)($payload['Ort'] ?? ''),
        "Zeit" => (string)($payload['Zeit'] ?? ''),
        "Temperatur" => (string)($payload['Temperatur'] ?? ''),
        "Taupunkt" => (string)($payload['Taupunkt'] ?? ''),
        "Regenwahrscheinlichkeit" => (string)($payload['Regenwahrscheinlichkeit'] ?? ''),
        "Wind (80m)" => (string)($payload['Wind (80m)'] ?? ''),
        "Windrichtung" => (string)($payload['Windrichtung'] ?? ''),
        "Bewölkung" => (string)($payload['Bewölkung'] ?? ''),
        "Sichtweite" => (string)($payload['Sichtweite'] ?? ''),
        "KPI Aurora" => (string)($payload['KPI Aurora'] ?? ''),
        "KP Index" => (string)($payload['KP Index'] ?? ''),
        "Aurora" => (string)($payload['Aurora'] ?? ''),
        "Sichtbare Satelliten" => (string)($payload['Sichtbare Satelliten'] ?? ''),
        "Wettercode" => (string)($payload['Wettercode'] ?? ''),
        "Koordinaten" => (string)($payload['Koordinaten'] ?? ''),
        "Gespeichert am" => date('Y-m-d H:i:s')
    ];
    $data['modified_at'] = date('Y-m-d H:i:s');
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "OK";
}


function showChecklistForm(){
    global $TEMPLATE_FOLDER, $JSON_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
    $isGuest=false;

    $file     = basename($_GET['file'] ?? "");
    $edit     = isset($_GET['edit']) ? basename($_GET['edit']) : null;
    $readonly = isset($_GET['readonly']);

    $tpl = loadTemplate($file);
    $title    = $tpl["title"] ?? "Checkliste";
    $sections = $tpl["sections"] ?? [];

    $saved = [];
    $extraFields = [];
    $createdAt = null;
    $modifiedAt = null;
    $editIdForWeather = "";
    $isNewChecklist = !$edit;

    // --- NEU: Flugzeit/Flugort für Auto-Wetterpanel sammeln
    $flugzeit = "";
    $flugort  = "";

    if ($edit){
        $json = loadChecklist($edit);
        if ($json){
            $savedRaw   = $json["fields"] ?? [];
            $createdAt  = $json["created_at"]  ?? null;
            $modifiedAt = $json["modified_at"] ?? null;

            $saved = mergeTemplateWithSaved($sections, $savedRaw);
            $editIdForWeather = (string)$edit;

            foreach ($savedRaw as $secName => $fields) {
                foreach ($fields as $label => $value) {
                    if (!isset($sections[$secName][$label])) {
                        $extraFields[$secName][$label] = $value;
                    }
                }
            }
        }
    }

	// Flugzeit/Flugort vorab ermitteln (für Wetteranzeige direkt unter dem Titel)
	// Grundlage ist der Feldtyp im Template: "flugzeit" / "flugort"
	$hasFlightTimeField = false;
	$hasFlightPlaceField = false;
	foreach ($sections as $secName => $fieldsMeta){
	  foreach ($fieldsMeta as $label => $meta){

		$t = is_array($meta) ? ($meta['type'] ?? 'text') : (string)$meta;

		if ($t === 'flugzeit') {
		  $hasFlightTimeField = true;
		}
		if ($t === 'flugort') {
		  $hasFlightPlaceField = true;
		}

		if ($t !== 'flugzeit' && $t !== 'flugort') {
		  continue;
		}

		$v = $saved[$secName][$label] ?? ($_GET[$label] ?? '');
		if (trim((string)$v) === '') continue;

		if ($t === 'flugzeit') $flugzeit = (string)$v;
		if ($t === 'flugort') $flugort = (string)$v;
	  }
	}

	$showStartWeather = ($hasFlightTimeField && $hasFlightPlaceField);

    pageHeaderLocal($title);

    // Wetter direkt unter dem Titel
	// -------------------- WETTERBOX (nur wenn Flugzeit + Flugort im Template vorhanden) --------------------
	if ($showStartWeather) {
		$flugzeitEsc = safe(formatCHDatetime($flugzeit));
		$flugortEsc  = safe($flugort);
    	$editJson = json_encode($editIdForWeather, JSON_UNESCAPED_UNICODE);

		echo <<<HTML
		<style>
		/* Wetterbox – kompakt, keine Färbung/Highlight */
		.wxbox{margin:12px 0 18px 0; padding:12px; border:1px solid #e5e7eb; border-radius:12px; background:#fff;}
		.wxbox h4{margin:0;}
		.wxtitlebar{display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 10px 0;}
		.wxmeta{margin:0; color:#374151; font-size:0.95em; text-align:right;}
		@media (max-width: 900px){
		  .wxtitlebar{flex-direction:column; align-items:flex-start;}
		  .wxmeta{text-align:left;}
		}
		.wxnote{margin-top:10px; color:#6b7280; font-size:0.9em;}

		.wxcard{border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; background:#fafafa;}
		.wxtop{display:flex; align-items:center; justify-content:space-between; gap:10px; font-weight:700;}
		.wxdesc{color:#374151; font-size:0.95em; margin-top:4px;}
		.wxsep{height:1px; background:#e5e7eb; margin:10px 0;}

		/* Werte: 1/2/3 Spalten */
		.wxkvgrid{display:grid; gap:8px 14px; grid-template-columns:1fr; margin-top:10px;}
		@media (min-width: 720px){ .wxkvgrid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
		@media (min-width: 1100px){ .wxkvgrid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
		.wxkv{display:flex; gap:8px; align-items:baseline; min-width:0;}
		.wxk{color:#6b7280; font-size:0.88em; min-width:0;}
		.wxv{color:#111827; font-weight:600; font-size:0.95em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
		.wxv-warn{color:#9a5600; font-weight:700;}
		.wxv-critical{color:#b91c1c; font-weight:700;}
		</style>

		<div class="wxbox" id="flightWxBox">
		  <div class="wxtitlebar">
			<h4>Wetter beim Start</h4>
			<div class="wxmeta" id="flightWxMeta">
			  <b>Flugort:</b> <span id="flightWxPlace">{$flugortEsc}</span>
			  &nbsp; <b>Flugzeit:</b> <span id="flightWxTime">{$flugzeitEsc}</span>
			</div>
		  </div>
		  <div id="flightWxContent" class="wxnote">ℹ️ Wetter wird geladen sobald Flugort und Flugzeit gesetzt sind…</div>
		</div>

		<script>
		(function(){
		  const \$ = (sel) => document.querySelector(sel);
		  const weatherHidden = () => document.querySelector("input[name='weather_json']");
		  function setWeatherSnapshot(snapshot){
			const hidden = weatherHidden();
			if (!hidden) return;
			hidden.value = JSON.stringify({ snapshot: snapshot || {} });
		  }
		  function clearWeatherSnapshot(){
			const hidden = weatherHidden();
			if (!hidden) return;
			hidden.value = '';
		  }

		  function findFlightInputs(){
			const timeInput =
			  document.querySelector("input[data-field-type='flugzeit']") ||
			  document.querySelector("[data-field-type='flugzeit']");
			const placeInput =
			  document.querySelector("input[data-field-type='flugort']") ||
			  document.querySelector("[data-field-type='flugort']");
			return { timeInput, placeInput };
		  }

		  function esc(s){
			return String(s ?? '').replace(/[&<>"']/g, m => ({
			  '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
			}[m]));
		  }

		  function fmtDisplayDateTime(raw){
			const s = String(raw || '').trim();
			if (!s) return '';
			const d = new Date(s);
			if (!isFinite(d.getTime())) return s;
			const mmm = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
			const dd = String(d.getDate()).padStart(2,'0');
			const mm = mmm[d.getMonth()] || String(d.getMonth()+1).padStart(2,'0');
			const yyyy = String(d.getFullYear());
			const hh = String(d.getHours()).padStart(2,'0');
			const mi = String(d.getMinutes()).padStart(2,'0');
			return dd + '.' + mm + '.' + yyyy + ' ' + hh + ':' + mi;
		  }

		  function wxIcon(code){
			const c = parseInt(code,10);
			if (Number.isNaN(c)) return '❓';
			if (c===0) return '☀️';
			if (c>=1 && c<=3) return '🌤️';
			if (c===45 || c===48) return '🌫️';
			if ([51,53,55,56,57].includes(c)) return '🌦️';
			if ([61,63,65,66,67,80,81,82].includes(c)) return '🌧️';
			if ([71,73,75,77,85,86].includes(c)) return '🌨️';
			if ([95,96,99].includes(c)) return '⛈️';
			return '🌡️';
		  }

		  function wxText(code){
			const c = parseInt(code,10);
			if (Number.isNaN(c)) return 'unbekannt';
			if (c===0) return 'klar';
			if (c===1) return 'überwiegend klar';
			if (c===2) return 'teilweise bewölkt';
			if (c===3) return 'bedeckt';
			if (c===45) return 'Nebel';
			if (c===48) return 'Reif-Nebel';
			if ([51,53,55].includes(c)) return 'Niesel';
			if ([56,57].includes(c)) return 'gefrierender Niesel';
			if ([61,63,65].includes(c)) return 'Regen';
			if ([66,67].includes(c)) return 'gefrierender Regen';
			if ([71,73,75].includes(c)) return 'Schnee';
			if (c===77) return 'Schneekörner';
			if ([80,81,82].includes(c)) return 'Regenschauer';
			if ([85,86].includes(c)) return 'Schneeschauer';
			if (c===95) return 'Gewitter';
			if ([96,99].includes(c)) return 'Gewitter mit Hagel';
			return 'Wettercode ' + c;
		  }

		  function toCompass(deg){
			const d = Number(deg);
			if (!isFinite(d)) return '';
			const dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
			return dirs[Math.round(((d%360)/22.5))%16];
		  }

		  function nearestIndex(times, targetTs){
			let bestI = 0, bestD = null;
			for (let i=0;i<times.length;i++){
			  const d = Math.abs((times[i]||0) - targetTs);
			  if (bestD===null || d<bestD){ bestD=d; bestI=i; }
			}
			return bestI;
		  }
		  async function primeWeatherSnapshot(geo, dtLocal){
			if (!geo || geo.lat == null || geo.lon == null) return;
			const dateStr = String(dtLocal || '').slice(0,10);
			const targetTs = Math.floor(new Date(dtLocal).getTime()/1000);
			if (!dateStr || !isFinite(targetTs)) return;
			const day = await fetchDay(geo.lat, geo.lon, dateStr, geo.timezone);
			const h = day && day.hourly ? day.hourly : null;
			if (!h || !h.time || !h.time.length) return;
			const i = nearestIndex(h.time, targetTs);
			setWeatherSnapshot({
			  time: (h.time && h.time[i]) ? new Date(Number(h.time[i]) * 1000).toISOString() : dtLocal,
			  temp_c: h.temperature_2m ? h.temperature_2m[i] : '',
			  precip_prob_pct: h.precipitation_probability ? h.precipitation_probability[i] : '',
			  wind_80m_kmh: h.windspeed_80m ? h.windspeed_80m[i] : '',
			  wind_dir_deg: h.winddirection_80m ? h.winddirection_80m[i] : '',
			  cloud_cover_pct: h.cloudcover ? h.cloudcover[i] : '',
			  visibility_m: h.visibility ? h.visibility[i] : ''
			});
		  }

		  function parseLatLonInline(s){
			const m = String(s||'').trim().match(/^\\s*(-?\\d{1,2}(?:[\\.,]\\d+)?)\\s*[,;\\s]\\s*(-?\\d{1,3}(?:[\\.,]\\d+)?)\\s*$/);
			if (!m) return null;
			const lat = parseFloat(m[1].replace(',','.'));
			const lon = parseFloat(m[2].replace(',','.'));
			if (!isFinite(lat) || !isFinite(lon)) return null;
			if (lat<-90 || lat>90 || lon<-180 || lon>180) return null;
			return { lat, lon, timezone:'auto', name:String(lat)+','+String(lon) };
		  }

		  async function geocode(name){
			const ll = parseLatLonInline(name);
			if (ll) return ll;

			const url = 'https://geocoding-api.open-meteo.com/v1/search?name='
			  + encodeURIComponent(name) + '&count=1&language=de&format=json';

			const r = await fetch(url);
			if (!r.ok) return null;

			const j = await r.json();
			const res = j && j.results ? j.results[0] : null;
			if (!res) return null;

			return {
			  name: res.name ?? name,
			  lat: res.latitude,
			  lon: res.longitude,
			  timezone: res.timezone ?? 'auto',
			  admin1: res.admin1 ?? null,
			  country: res.country ?? null
			};
		  }

		  async function fetchDay(lat, lon, dateStr, timezone){
			const hourlyVars = [
			  'temperature_2m',
			  'relative_humidity_2m',
			  'dewpoint_2m',
			  'cloudcover',
			  'visibility',
			  'precipitation_probability',
			  'windspeed_80m',
			  'winddirection_80m',
			  'windgusts_10m',
			  'weathercode'
			].join(',');

			const dailyVars = ['sunrise','sunset'].join(',');

			const url = 'https://api.open-meteo.com/v1/forecast'
			  + '?latitude=' + encodeURIComponent(lat)
			  + '&longitude=' + encodeURIComponent(lon)
			  + '&hourly=' + encodeURIComponent(hourlyVars)
			  + '&daily=' + encodeURIComponent(dailyVars)
			  + '&start_date=' + encodeURIComponent(dateStr)
			  + '&end_date=' + encodeURIComponent(dateStr)
			  + '&timezone=' + encodeURIComponent(timezone || 'auto')
			  + '&timeformat=unixtime';

			const r = await fetch(url);
			if (!r.ok) return null;
			return await r.json();
		  }

		  // KP (optional) – aus NOAA 3-Day Forecast passend zur Flugzeit
		  async function fetchKp(targetTs){
			try{
			  const labelFor = (kpVal) =>
				kpVal <= 2 ? 'ruhig / leicht aktiv' :
				kpVal <= 3 ? 'leicht aktiv' :
				kpVal <= 4 ? 'aktiv' :
				kpVal <= 5 ? 'stürmisch' :
				'stark stürmisch';

			  try {
				const r1 = await fetch('https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json', { cache:'no-store' });
				if (r1.ok) {
				  const j1 = await r1.json();
				  if (Array.isArray(j1) && j1.length){
					const targetMs = Number.isFinite(Number(targetTs)) ? Number(targetTs) * 1000 : Date.now();
					const entries = [];
					for (let i=0; i<j1.length; i++){
					  const row = j1[i];
					  if (!row) continue;
					  const ts = String((row.time_tag ?? row[0] ?? '')).trim();
					  const kpVal = parseFloat(row.Kp ?? row.kp ?? row.kp_index ?? row[1]);
					  if (!ts || !isFinite(kpVal) || kpVal < 0 || kpVal > 9) continue;
					  const ms = Date.parse(/Z$/.test(ts) ? ts : (ts + 'Z'));
					  if (!isFinite(ms)) continue;
					  entries.push({ ms, ts, kp: kpVal });
					}
					if (entries.length){
					  entries.sort((a,b)=>a.ms-b.ms);
					  const next = entries.find(e => e.ms >= targetMs);
					  const pick = next || entries[entries.length - 1];
					  return { kp: pick.kp, label: labelFor(pick.kp), time_utc: pick.ts };
					}
				  }
				}
			  } catch(e) {}

			  return null;
			}catch(e){
			  return null;
			}
		  }

		  function fmtNum(v, digits){
			const n = Number(v);
			if (!isFinite(n)) return '–';
			return n.toFixed(digits);
		  }
		  function fmtInt(v){
			const n = Number(v);
			if (!isFinite(n)) return '–';
			return String(Math.round(n));
		  }
		  function fmtKmFromMeters(m){
			const n = Number(m);
			if (!isFinite(n)) return '–';
			return (n/1000).toFixed(0) + ' km';
		  }
		  function fmtTimeHHMMFromUnix(ts){
			const n = Number(ts);
			if (!isFinite(n)) return '–';
			const d = new Date(n*1000);
			return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
		  }

		  // Zentrale Grenzwerte fuer kritische Wetterparameter
		  const WX_CRITICAL_CFG = {
			wind80Max: 28,
			precipProbMax: 60,
			cloudCoverMax: 85,
			visibilityKmWarn: 2,
			visibilityKmBad: 0.5,
			kpWarn: 5,
			kpBad: 7
		  };

		  function firstNum(v){
			if (v == null) return NaN;
			if (typeof v === 'number') return v;
			const m = String(v).replace(',', '.').match(/-?\d+(?:\.\d+)?/);
			return m ? Number(m[0]) : NaN;
		  }
		  function isCrit(metric, value){
			const n = firstNum(value);
			if (!isFinite(n)) return false;
			if (metric === 'wind80') return n >= WX_CRITICAL_CFG.wind80Max;
			if (metric === 'precip') return n >= WX_CRITICAL_CFG.precipProbMax;
			if (metric === 'cloud') return n >= WX_CRITICAL_CFG.cloudCoverMax;
			if (metric === 'visKm') return n < WX_CRITICAL_CFG.visibilityKmBad;
			if (metric === 'kp') return n > WX_CRITICAL_CFG.kpBad;
			return false;
		  }
		  function wxVal(text, level){
			const normalized = (level === true) ? 'bad' : (level || '');
			const cls = normalized === 'bad' ? 'wxv wxv-critical' : (normalized === 'warn' ? 'wxv wxv-warn' : 'wxv');
			const prefix = normalized === 'bad' ? '⚠️ ' : '';
			const suffix = normalized === 'bad' ? ' (kritisch)' : (normalized === 'warn' ? ' (Warnung)' : '');
			return '<div class="' + cls + '">' + prefix + esc(text) + suffix + '</div>';
		  }
		  function visLevelFromKm(visKm){
			const n = Number(visKm);
			if (!isFinite(n)) return '';
			if (n < WX_CRITICAL_CFG.visibilityKmBad) return 'bad';
			if (n < WX_CRITICAL_CFG.visibilityKmWarn) return 'warn';
			return '';
		  }
		  function kpLevelFromValue(kp){
			const n = Number(kp);
			if (!isFinite(n)) return '';
			if (n > WX_CRITICAL_CFG.kpBad) return 'bad';
			if (n > WX_CRITICAL_CFG.kpWarn) return 'warn';
			return '';
		  }

		  function estimateGnssVisibleCount(){
			const elevationMaskDeg = 10;
			const totalGnssSat = 31;
			const fraction = (1 - Math.sin(elevationMaskDeg * Math.PI / 180)) / 2;
			const est = Math.round(totalGnssSat * fraction * 0.9);
			return Math.max(8, Math.min(16, est));
		  }

		  function renderSingleHour(day, targetTs, kpObj){
			const h = day && day.hourly ? day.hourly : null;
			const u = day && day.hourly_units ? day.hourly_units : {};
			if (!h || !h.time || !h.time.length) return '<div class="wxnote">⚠️ Keine Daten verfügbar.</div>';

			const i = nearestIndex(h.time, targetTs);

			const wc = h.weathercode ? h.weathercode[i] : null;

			const temp = h.temperature_2m ? h.temperature_2m[i] : null;
			const rh   = h.relative_humidity_2m ? h.relative_humidity_2m[i] : null;
			const dp   = h.dewpoint_2m ? h.dewpoint_2m[i] : null;
			const cc   = h.cloudcover ? h.cloudcover[i] : null;
			const vis  = h.visibility ? h.visibility[i] : null;
			const pp   = h.precipitation_probability ? h.precipitation_probability[i] : null;

			const ws80 = h.windspeed_80m ? h.windspeed_80m[i] : null;
			const wd80 = h.winddirection_80m ? h.winddirection_80m[i] : null;
			const gust = h.windgusts_10m ? h.windgusts_10m[i] : null;

			const sunrise = day && day.daily && day.daily.sunrise ? day.daily.sunrise[0] : null;
			const sunset  = day && day.daily && day.daily.sunset  ? day.daily.sunset[0]  : null;
			const satCount = estimateGnssVisibleCount();

			const windDirText = (wd80==null || wd80==='') ? '–' : (fmtInt(wd80) + '° (' + toCompass(wd80) + ')');

			const kpText = kpObj ? (kpObj.kp.toFixed(0) + ' ' + kpObj.label) : '–';

			const tUnit  = u.temperature_2m || '°C';
			const rhUnit = u.relative_humidity_2m || '%';
			const dpUnit = u.dewpoint_2m || '°C';
			const ccUnit = u.cloudcover || '%';
			const ppUnit = u.precipitation_probability || '%';
			const wsUnit = u.windspeed_80m || 'km/h';
			const gsUnit = u.windgusts_10m || 'km/h';

			const hh = esc(new Date(h.time[i]*1000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}));
			const visText = fmtKmFromMeters(vis);
			const windText = fmtNum(ws80,1) + ' ' + esc(wsUnit);
			const rainText = fmtInt(pp) + ' ' + esc(ppUnit);
			const cloudText = fmtInt(cc) + ' ' + esc(ccUnit);
			const kpDisplay = kpText;

			const cWind = isCrit('wind80', ws80);
			const cRain = isCrit('precip', pp);
			const cCloud = isCrit('cloud', cc);
			const visLevel = visLevelFromKm(firstNum(visText));
			const cVis = (visLevel === 'bad');
			const kpLevel = kpLevelFromValue(kpObj ? kpObj.kp : null);
			const cKp = (kpLevel === 'bad');
			const hasCrit = (cWind || cRain || cCloud || cVis || cKp);

			return ''
			  + '<div class="wxcard">'
			  + '  <div class="wxtop"><span><b>Wetter</b>: ' + wxIcon(wc) + ' ' + esc(wxText(wc)) + '</span><span>' + hh + '</span></div>'
			  + '  <div class="wxsep"></div>'
			  + '  <div class="wxkvgrid">'
			  + '    <div class="wxkv"><div class="wxk">Temperatur</div>' + wxVal(fmtNum(temp,1) + ' ' + tUnit, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Luftfeuchte</div>' + wxVal(fmtInt(rh) + ' ' + rhUnit, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Taupunkt</div>' + wxVal(fmtNum(dp,1) + ' ' + dpUnit, false) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Bewölkung</div>' + wxVal(cloudText, cCloud) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Sichtweite</div>' + wxVal(visText, visLevel) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Niederschlag (W’keit)</div>' + wxVal(rainText, cRain) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Wind (80m)</div>' + wxVal(windText, cWind) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Windrichtung</div>' + wxVal(windDirText, false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Windböen</div>' + wxVal(fmtNum(gust,1) + ' ' + gsUnit, false) + '</div>'

			  + '    <div class="wxkv"><div class="wxk">Sonnenauf/untergang</div>' + wxVal(fmtTimeHHMMFromUnix(sunrise) + ' / ' + fmtTimeHHMMFromUnix(sunset), false) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">KP</div>' + wxVal(kpDisplay, kpLevel) + '</div>'
			  + '    <div class="wxkv"><div class="wxk">Sichtbare GNSS Satelliten</div>' + wxVal(String(satCount), false) + '</div>'
			  + '  </div>'
			  + (hasCrit ? '  <div class="wxnote" style="color:#b91c1c;font-weight:700;">⚠️ Kritische Wetterparameter erkannt</div>' : '')
			  + '</div>';
		  }

		  async function loadWeather(){
			const inp = findFlightInputs();
			const timeInput = inp.timeInput;
			const placeInput = inp.placeInput;

			const dtLocal = (timeInput && timeInput.value) ? String(timeInput.value).trim() : '';
			const place = (placeInput && placeInput.value) ? String(placeInput.value).trim() : '';

			if (\$('#flightWxPlace')) \$('#flightWxPlace').textContent = place;
			if (\$('#flightWxTime'))  \$('#flightWxTime').textContent  = fmtDisplayDateTime(dtLocal);

			if (!dtLocal || !place){
			  clearWeatherSnapshot();
			  \$('#flightWxContent').innerHTML =
				'<div class="wxnote">ℹ️ Wetter wird angezeigt sobald <b>Flugzeit</b> und <b>Flugort</b> gesetzt sind.</div>';
			  return;
			}

			\$('#flightWxContent').innerHTML = '<div class="wxnote">⏳ Wetter wird geladen…</div>';

			try{
			  const editId = {$editJson};
			  let geo = null;
			  try { geo = await geocode(place); } catch(e) { geo = null; }
			  const params = new URLSearchParams({
				mode: 'weather',
				flugort: place,
				flugzeit: dtLocal
			  });
			  if (geo && geo.lat != null && geo.lon != null){
				params.set('lat', String(geo.lat));
				params.set('lon', String(geo.lon));
				primeWeatherSnapshot(geo, dtLocal).catch(() => {});
			  }
			  if (editId) params.set('edit', editId);
			  const res = await fetch('?' + params.toString(), { cache: 'no-store' });
			  if (!res.ok) throw new Error('Wetterdienst nicht erreichbar');
			  const html = await res.text();
			  const looksEmpty = /Keine Wetterdaten verfügbar|Flugort nicht gefunden|Ungültige Wetteranfrage/i.test(html || '');
			  if (!looksEmpty){
				\$('#flightWxContent').innerHTML = html;
				return;
			  }

			  // Fallback: direkte Browser-Abfrage, falls Serverzugriff blockiert ist
			  if (!geo || geo.lat == null || geo.lon == null){
				\$('#flightWxContent').innerHTML = html;
				return;
			  }
			  const dateStr = dtLocal.slice(0,10);
			  const targetTs = Math.floor(new Date(dtLocal).getTime()/1000);
			  const day = await fetchDay(geo.lat, geo.lon, dateStr, geo.timezone);
			  if (!day || !day.hourly || !day.hourly.time || !day.hourly.time.length){
				\$('#flightWxContent').innerHTML = html;
				return;
			  }
			  let kpObj = null;
			  try { kpObj = await fetchKp(targetTs); } catch(e) { kpObj = null; }
			  \$('#flightWxContent').innerHTML = renderSingleHour(day, targetTs, kpObj);
			  const hSnap = day.hourly || {};
			  const iSnap = nearestIndex(hSnap.time || [], targetTs);
			  setWeatherSnapshot({
				time: (hSnap.time && hSnap.time[iSnap]) ? new Date(Number(hSnap.time[iSnap]) * 1000).toISOString() : dtLocal,
				temp_c: hSnap.temperature_2m ? hSnap.temperature_2m[iSnap] : '',
				precip_prob_pct: hSnap.precipitation_probability ? hSnap.precipitation_probability[iSnap] : '',
				wind_80m_kmh: hSnap.windspeed_80m ? hSnap.windspeed_80m[iSnap] : '',
				wind_dir_deg: hSnap.winddirection_80m ? hSnap.winddirection_80m[iSnap] : '',
				cloud_cover_pct: hSnap.cloudcover ? hSnap.cloudcover[iSnap] : '',
				visibility_m: hSnap.visibility ? hSnap.visibility[iSnap] : ''
			  });

			  // Nach Anzeige im Hintergrund speichern (falls edit vorhanden)
			  if (editId){
				try{
				  const h = day.hourly || {};
				  const i = nearestIndex(h.time || [], targetTs);
				  const sat = estimateGnssVisibleCount();
				  const wd = h.winddirection_80m ? Number(h.winddirection_80m[i]) : null;
				  const ws = h.windspeed_80m ? Number(h.windspeed_80m[i]) : null;
				  const kpText = kpObj ? (Math.round(kpObj.kp) + ' ' + kpObj.label) : '';
				  const payload = {
					"Ort": place,
					"Zeit": dtLocal,
					"Temperatur": h.temperature_2m ? String(h.temperature_2m[i] ?? '') : '',
					"Taupunkt": h.dewpoint_2m ? String(h.dewpoint_2m[i] ?? '') : '',
					"Regenwahrscheinlichkeit": h.precipitation_probability ? String(h.precipitation_probability[i] ?? '') : '',
					"Wind (80m)": Number.isFinite(ws) ? (ws + ' km/h') : '',
					"Windrichtung": Number.isFinite(wd) ? (Math.round(wd) + '° (' + toCompass(wd) + ')') : '',
					"Bewölkung": h.cloudcover ? String(h.cloudcover[i] ?? '') : '',
					"Sichtweite": h.visibility ? (Math.round(Number(h.visibility[i] || 0)/1000) + ' km') : '',
					"KPI Aurora": kpText,
					"KP Index": kpText,
					"Aurora": kpObj ? kpObj.label : '',
					"Sichtbare GNSS Satelliten": String(sat),
					"Sichtbare Satelliten": String(sat),
					"Wettercode": h.weathercode ? String(h.weathercode[i] ?? '') : '',
					"Koordinaten": (geo.lat + ', ' + geo.lon)
				  };
				  const body = new URLSearchParams();
				  body.set('edit', editId);
				  body.set('weather', JSON.stringify(payload));
				  fetch('?mode=saveweatherclient', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
					body: body.toString()
				  }).catch(() => {});
				}catch(e){}
			  }

			}catch(e){
			  console.error(e);
			  const msg = (e && e.message) ? e.message : String(e);
			  \$('#flightWxContent').innerHTML =
				'<div class="wxnote">⚠️ Fehler: <b>' + esc(msg) + '</b></div>';
			}
		  }

		  let t=null;
		  function schedule(){ clearTimeout(t); t=setTimeout(loadWeather, 350); }

		  window.addEventListener('DOMContentLoaded', () => {
			loadWeather();
			const inp = findFlightInputs();
			if (inp.timeInput) inp.timeInput.addEventListener('input', schedule);
			if (inp.placeInput) inp.placeInput.addEventListener('input', schedule);
		  });
		})();
		</script>
		HTML;
	}
    // Auto-Refresh wenn Flugzeit oder Flugort geändert werden

    echo "<div class='rkrk-card'>
            <form method='post' action='?mode=save'>
            <input type='hidden' name='file' value='$file'>
            <input type='hidden' id='weather_json' name='weather_json' value=''>";
    if ($edit){
        echo "<input type='hidden' name='edit' value='$edit'>";
    }

	foreach ($sections as $secName => $fields){

		$secId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $secName));

		echo "<div class='section-row'>
				<div class='section-title-box' data-section-id='$secId'>".safe($secName)."</div>
				<div class='section-fields' data-section-id='$secId'>";

		$deferredFundeValue = null;

		// Felder rendern
		foreach ($fields as $label => $type){
			if (!$readonly && strcasecmp((string)$secName, "Suchresultat") === 0) {
				if (strcasecmp((string)$label, "Funde") === 0) {
					$tmpFunde = $saved[$secName][$label] ?? ($_GET[$label] ?? "");
					if (trim((string)$tmpFunde) === "") $tmpFunde = "keine";
					$deferredFundeValue = $tmpFunde;
					continue;
				}
				if (strcasecmp((string)$label, "Beboachtungen") === 0) {
					continue;
				}
			}
			$value = $saved[$secName][$label] ?? ($_GET[$label] ?? "");
			renderField($secName, $label, $type, $value, $readonly);
		}

		// Wetter-Hinweis anzeigen
		if ($secName === "Wetter" && isset($saved["weather_saved_at"])) {
			echo "<div class='system-info-box' style='margin-top:10px;'>
					🌤️ Wetterdaten gespeichert am: " . safe($saved["weather_saved_at"]) . "
				  </div>";
		}

		// Dynamische Status-Zeilen im Suchresultat immer editierbar anzeigen
		if (!$readonly && strcasecmp((string)$secName, "Suchresultat") === 0) {
			echo "<div id='dynamic-status-rows' data-sec-name='".safe($secName)."'></div>";
			renderField($secName, "Funde", "text", $deferredFundeValue ?? "keine", $readonly);
		}

		// Extra-Felder
		if (isset($extraFields[$secName])) {
			foreach ($extraFields[$secName] as $label => $value) {
				// Diese Felder werden im dynamischen Feld#-Block gerendert.
				if (
					!$readonly &&
					strcasecmp((string)$secName, "Suchresultat") === 0 &&
					(rkrk_is_dynamic_status_label($label) || rkrk_is_dynamic_status_other_label($label))
				) {
					continue;
				}
				$extraType = "text";
				if (rkrk_is_dynamic_status_label($label)) {
					$extraType = rkrk_status_multi_type();
				} elseif (rkrk_is_dynamic_status_other_label($label)) {
					$extraType = "text";
				}
				renderField($secName, $label, $extraType, $value, $readonly);
			}
		}

        if (!$readonly && !$isGuest){
            $newSecId = $secId . "_new";

            echo "
            <div class='new-field-add-button'>
                <button type='button' class='btn-newfield' onclick=\"addNewField('$secName', '$newSecId')\">
                    +
                </button>
            </div>

            <div class='section-fields new-fields-container collapsed' id='new-fields-$newSecId'></div>
            ";
        }

        echo "</div></div>";
    }

    if (!$readonly && !$isGuest){
        echo "<button class='btn btn-primary' style='border:1px solid #6aa56a;'>✓ Speichern</button>";
    }

    echo "</form></div>";

    if ($createdAt){
        echo "<div class='system-info-box'>🕒 Erstellt am: ".safe($createdAt)."</div>";
    }

    if ($modifiedAt){
        echo "<div class='system-info-bottom'>✏️ Zuletzt bearbeitet am: ".safe($modifiedAt)."</div>";
    }

    if ($edit){
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='?mode=print&id=$edit' class='btn' target='_blank'>🖨️ PDF drucken</a>
                <a href='?mode=listall' class='btn'>📄 Zurück zu den Checklisten</a>
              </div>";
    }
	echo "
	<script>
	let newFieldCounter = 0;

	function addNewField(secName, secId) {

		const container = document.getElementById('new-fields-' + secId);

		// Toggle Sichtbarkeit
		if (container.classList.contains('collapsed')) {
			container.classList.remove('collapsed');
		} else {
			container.classList.add('collapsed');
			return; // nicht erneut Feld hinzufügen
		}

		const index = newFieldCounter++;

		const html = `
			<div class='field new-field-block'>
				<label class='field-label'>Neues Feld</label>

				<input type='text'
					   name='new_fields[\${secName}][label][\${index}]'
					   placeholder='Label'>

				<select name='new_fields[\${secName}][type][\${index}]'>
					<option value='text'>Text</option>
					<option value='textarea'>Textarea</option>
					<option value='number'>Number</option>
					<option value='date'>Date</option>
					<option value='datetime'>Datetime</option>
					<option value='checkbox'>Checkbox</option>
					<option value='radio'>Radio (Optionen folgen)</option>
					<option value='multi'>Multi (Optionen folgen)</option>
				</select>

				<input type='text'
					   name='new_fields[\${secName}][options][\${index}]'
					   placeholder='Optionen (a|b|c)'>

				<input type='text'
					   name='new_fields[\${secName}][value][\${index}]'
					   placeholder='Wert'>
			</div>
		`;

		container.insertAdjacentHTML('beforeend', html);
	}
	</script>";

    if (!$readonly) {
        $fieldNameMapJson = json_encode(rkrk_get_field_name_map(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$fieldNameMapJson) $fieldNameMapJson = "{}";
        echo <<<HTML
<script>
(function(){
    const host = document.getElementById('dynamic-status-rows');
    if (!host) return;
    if (!document.getElementById('dynamic-status-inline-style')) {
        const st = document.createElement('style');
        st.id = 'dynamic-status-inline-style';
        st.textContent = ''
            + '.generated-status-grid{display:flex;flex-wrap:nowrap;gap:6px;overflow-x:auto;padding-bottom:2px;}'
            + '.generated-compact-option{margin:0;min-width:auto;flex:0 0 auto;}'
            + '.generated-compact-option .color-badge{padding:4px 8px;font-size:0.9em;line-height:1.1;white-space:nowrap;}'
            + '.generated-group-title{font-size:0.8em;color:#4b5563;margin:2px 0 4px 2px;font-weight:600;}'
            + '.generated-group-wrap{margin-bottom:4px;}';
        document.head.appendChild(st);
    }

    const secName = host.dataset.secName || 'Suchresultat';
    const feldInput = document.querySelector('[data-field-key="feld_"]');
    if (!feldInput) return;
    const FIELD_NAME_MAP = $fieldNameMapJson || {};

    const OPTIONS = ['geplant','auf RC','geprüft','geflogen','Keine Funde','1 Kitz','2 Kitze','andere'];

    function escHtml(s){
        return String(s ?? '').replace(/[&<>"']/g, (m) => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[m]));
    }

    function parseFieldList(raw){
        const parts = String(raw || '')
            .split(/[;,\\n]+/)
            .map(s => s.trim())
            .filter(Boolean);
        const out = [];
        const seen = {};
        for (const p of parts){
            if (!seen[p]) {
                seen[p] = true;
                out.push(p);
            }
        }
        return out;
    }

    function inputName(label, isArray){
        return 'fields[' + secName + '][' + label + ']' + (isArray ? '[]' : '');
    }

    function getFundeInput(){
        return document.querySelector('input[name="' + inputName('Funde', false) + '"]');
    }

    function statusLabel(fieldId){
        return 'Status und Suchresultate (' + fieldDisplay(fieldId) + ')';
    }

    function otherLabel(fieldId){
        return 'Status und Suchresultate andere (' + fieldDisplay(fieldId) + ')';
    }

    function normalizeFieldId(token){
        const t = String(token || '').trim();
        const m = t.match(/(\d{1,3}-\d{1,4})/);
        return m ? m[1] : t;
    }

    function fieldDisplay(fieldId){
        const id = normalizeFieldId(fieldId);
        const name = String(FIELD_NAME_MAP[id] || '').trim();
        return name ? (id + ' - ' + name) : id;
    }

    function restoreState(){
        const map = {};
        function readCheckedByName(name){
            const out = [];
            document.querySelectorAll('input[type="checkbox"][name="' + name + '"]').forEach(cb => {
                if (cb.checked) out.push(cb.value);
            });
            return out;
        }
        function readValueByName(name){
            const el = document.querySelector('input[name="' + name + '"]');
            return el ? String(el.value || '') : '';
        }
        const fields = parseFieldList(feldInput.value);
        fields.forEach(fieldId => {
            const sLbl = statusLabel(fieldId);
            const oLbl = otherLabel(fieldId);
            const checked = readCheckedByName(inputName(sLbl, true));
            const other = readValueByName(inputName(oLbl, false));
            map[fieldId] = { checked, other };
        });

        host.querySelectorAll('[data-generated-field]').forEach(row => {
            const fieldId = row.dataset.generatedField || '';
            if (!fieldId) return;
            const checked = [];
            row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (cb.checked) checked.push(cb.value);
            });
            const otherInput = host.querySelector('input[data-generated-other-for="' + fieldId + '"]');
            map[fieldId] = {
                checked: checked,
                other: otherInput ? otherInput.value : ''
            };
        });
        return map;
    }

    function updateRules(fieldId){
        const row = host.querySelector('[data-generated-field="' + fieldId + '"]');
        if (!row) return;
        const cbPlanned = row.querySelector('input[type="checkbox"][value="geplant"]');
        const cbRc = row.querySelector('input[type="checkbox"][value="auf RC"]');
        const cbChecked = row.querySelector('input[type="checkbox"][value="geprüft"]');
        const cbOther = row.querySelector('input[type="checkbox"][value="andere"]');
        const otherWrap = host.querySelector('[data-generated-other-wrap="' + fieldId + '"]');

        if (cbChecked && cbChecked.checked) {
            if (cbPlanned) cbPlanned.checked = true;
            if (cbRc) cbRc.checked = true;
        }
        // Reverse dependency: removing prerequisites removes "geprüft".
        if (cbChecked && (!cbPlanned || !cbPlanned.checked || !cbRc || !cbRc.checked)) {
            cbChecked.checked = false;
        }

        if (otherWrap) {
            otherWrap.style.display = (cbOther && cbOther.checked) ? '' : 'none';
        }
        updateFundeFromStatuses();
    }

    function updateFundeFromStatuses(){
        const fundeInput = getFundeInput();
        if (!fundeInput) return;

        const chunks = [];
        host.querySelectorAll('[data-generated-field]').forEach(row => {
            const fieldId = row.dataset.generatedField || '';
            if (!fieldId) return;

            const checkedVals = [];
            row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (cb.checked) checkedVals.push(cb.value);
            });

            let kitzCount = 0;
            if (checkedVals.includes('2 Kitze')) kitzCount = 2;
            else if (checkedVals.includes('1 Kitz')) kitzCount = 1;

            if (kitzCount > 0) {
                chunks.push(fieldDisplay(fieldId) + ': ' + (kitzCount === 1 ? '1 Kitz' : '2 Kitze'));
            }

            if (checkedVals.includes('andere')) {
                const otherInput = host.querySelector('input[data-generated-other-for="' + fieldId + '"]');
                const otherTxt = otherInput ? String(otherInput.value || '').trim() : '';
                if (otherTxt !== '') {
                    chunks.push(fieldDisplay(fieldId) + ': ' + otherTxt);
                }
            }
        });

        fundeInput.value = chunks.length ? chunks.join('; ') : 'keine';
    }

    function bindRow(fieldId){
        const row = host.querySelector('[data-generated-field="' + fieldId + '"]');
        if (!row) return;
        row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', () => updateRules(fieldId));
        });
        const otherInput = host.querySelector('input[data-generated-other-for="' + fieldId + '"]');
        if (otherInput) {
            otherInput.addEventListener('input', updateFundeFromStatuses);
            otherInput.addEventListener('change', updateFundeFromStatuses);
        }
        updateRules(fieldId);
    }

    function buildRows(){
        const fundeInput = getFundeInput();
        if (fundeInput) {
            fundeInput.readOnly = true;
            fundeInput.title = 'Wird automatisch aus Status und Suchresultate berechnet';
        }
        const keep = restoreState();
        const fields = parseFieldList(feldInput.value);
        host.innerHTML = '';

        fields.forEach(fieldId => {
            const statusLbl = statusLabel(fieldId);
            const otherLbl = otherLabel(fieldId);
            const selected = (keep[fieldId] && Array.isArray(keep[fieldId].checked)) ? keep[fieldId].checked : [];
            const otherVal = (keep[fieldId] && typeof keep[fieldId].other === 'string') ? keep[fieldId].other : '';

            const statusOptions = ['geplant','auf RC','geprüft','geflogen'];
            const fundOptions = ['Keine Funde','1 Kitz','2 Kitze','andere'];

            let statusOptionsHtml = '';
            statusOptions.forEach(opt => {
                const checked = selected.includes(opt) ? 'checked' : '';
                statusOptionsHtml +=
                    '<label class="color-option generated-compact-option">' +
                    '<input type="checkbox" name="' + escHtml(inputName(statusLbl, true)) + '" value="' + escHtml(opt) + '" ' + checked + '>' +
                    '<span class="color-badge">' + escHtml(opt) + '</span>' +
                    '</label>';
            });

            let fundOptionsHtml = '';
            fundOptions.forEach(opt => {
                const checked = selected.includes(opt) ? 'checked' : '';
                fundOptionsHtml +=
                    '<label class="color-option generated-compact-option">' +
                    '<input type="checkbox" name="' + escHtml(inputName(statusLbl, true)) + '" value="' + escHtml(opt) + '" ' + checked + '>' +
                    '<span class="color-badge">' + escHtml(opt) + '</span>' +
                    '</label>';
            });

            const blockHtml =
                '<div class="field generated-status" data-generated-field="' + escHtml(fieldId) + '">' +
                    '<label class="field-label">' + escHtml(statusLbl) + '</label>' +
                    '<div class="generated-group-wrap">' +
                        '<div class="generated-group-title">Status (bis geflogen)</div>' +
                        '<div class="option-grid generated-status-grid">' + statusOptionsHtml + '</div>' +
                    '</div>' +
                    '<div class="generated-group-wrap">' +
                        '<div class="generated-group-title">Funde</div>' +
                        '<div class="option-grid generated-status-grid">' + fundOptionsHtml + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="field generated-status-other" data-generated-other-wrap="' + escHtml(fieldId) + '" style="display:none;">' +
                    '<label class="field-label">' + escHtml(otherLbl) + '</label>' +
                    '<input type="text" data-generated-other-for="' + escHtml(fieldId) + '" name="' + escHtml(inputName(otherLbl, false)) + '" value="' + escHtml(otherVal) + '">' +
                '</div>';

            host.insertAdjacentHTML('beforeend', blockHtml);
            bindRow(fieldId);
        });
        updateFundeFromStatuses();
    }

    feldInput.addEventListener('input', buildRows);
    feldInput.addEventListener('change', buildRows);
    buildRows();
})();
</script>
HTML;
    }

    pageFooterLocal();
}

/* -----------------------------------------------------------
   CHECKLISTE SPEICHERN
----------------------------------------------------------- */
function saveChecklist(){
    global $JSON_FOLDER, $TEMPLATE_FOLDER;

    /* -----------------------------------------
       1) BASIS: Template-Datei
    ----------------------------------------- */
    $file = basename($_POST['file'] ?? "");
    if ($file === "") {
        redirectTo("?mode=list");
    }

    /* -----------------------------------------
       2) EDIT-MODUS ODER NEUANLAGE
    ----------------------------------------- */
    $editId = $_POST['edit'] ?? null;
    $fields = $_POST['fields'] ?? [];

	/* -----------------------------------------
	   3) WETTERDATEN ÜBERNEHMEN (falls vorhanden)
	----------------------------------------- */
	if (!empty($_POST['weather_json'])) {

		$w = json_decode($_POST['weather_json'], true);

		if ($w && isset($w["snapshot"]["time"])) {

			$weatherTime = strtotime($w["snapshot"]["time"]);
			$now = time();

			if ($weatherTime && ($now - $weatherTime) <= 6 * 3600) {

				if (!isset($fields["Wetter"])) {
					$fields["Wetter"] = [];
				}

				$s = $w["snapshot"];

				$fields["Wetter"]["Zeit"] = $s["time"] ?? "";
				$fields["Wetter"]["Temperatur"] = $s["temp_c"] ?? "";
				$fields["Wetter"]["Regenwahrscheinlichkeit"] = $s["precip_prob_pct"] ?? "";
				$fields["Wetter"]["Wind (80m)"] = $s["wind_80m_kmh"] ?? "";
				$fields["Wetter"]["Windrichtung"] = $s["wind_dir_deg"] ?? "";
				$fields["Wetter"]["Bewölkung"] = $s["cloud_cover_pct"] ?? "";
				$fields["Wetter"]["Sichtweite"] = $s["visibility_m"] ?? "";
			}
		}
	}

    /* -----------------------------------------
       4) NEUE FELDER EINBAUEN
    ----------------------------------------- */
    if (isset($_POST['new_fields'])) {
        foreach ($_POST['new_fields'] as $secName => $items) {

            if (!isset($fields[$secName])) {
                $fields[$secName] = [];
            }

            foreach (array_keys($items['label']) as $i) {

                $label = trim($items['label'][$i]);
                if ($label === "") continue;

                $type    = trim($items['type'][$i] ?? "text");
                $options = trim($items['options'][$i] ?? "");
                $value   = trim($items['value'][$i] ?? "");

                if ($type === "radio" && $options !== "") {
                    $type = "radio:" . $options;
                }
                if ($type === "multi" && $options !== "") {
                    $type = "multi:" . $options;
                }

                $fields[$secName][$label] = $value;

                // Template NICHT erweitern!
            }
        }
    }

    /* -----------------------------------------
       5) SPEICHERN: EDIT ODER NEU
    ----------------------------------------- */
    if ($editId){
        $id = basename($editId);
        $path = "$JSON_FOLDER/$id.json";
        $existing = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $createdAt = $existing['created_at'] ?? date('Y-m-d H:i:s');
    } else {

        $createdAt = date('Y-m-d H:i:s');

        $tpl = loadTemplate($file);
        $tplTitle = $tpl["title"] ?? $file;
        $tplSlug = slugifyTitle($tplTitle);

        $stamp = date('Ymd_Hi');
        $idBase = $tplSlug . "_" . $stamp;
        $id = $idBase;
        $path = "$JSON_FOLDER/$id.json";

        $i = 2;
        while (file_exists($path)) {
            $id = $idBase . "_" . $i;
            $path = "$JSON_FOLDER/$id.json";
            $i++;
        }
    }

    /* -----------------------------------------
       6) JSON SCHREIBEN
    ----------------------------------------- */
    $data = [
        'template'    => $file,
        'fields'      => $fields,
        'created_at'  => $createdAt,
        'modified_at' => date('Y-m-d H:i:s')
    ];

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

    redirectTo("?mode=list&saved=1");
}


/* -----------------------------------------------------------
   CHECKLISTE LÖSCHEN
----------------------------------------------------------- */
function deleteJsonChecklist(){
    global $JSON_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge löschen.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    $path = "$JSON_FOLDER/$id.json";

    if (file_exists($path)) unlink($path);

    redirectTo("?mode=list&deleted=1");
}

function copyJsonChecklist(){
    global $JSON_FOLDER, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
    $isGuest=false;
    if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge kopieren.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    if ($id === "") {
        redirectTo("?mode=list");
    }

    $src = "$JSON_FOLDER/$id.json";
    if (!file_exists($src)) {
        redirectTo("?mode=list");
    }

    $data = json_decode(file_get_contents($src), true);
    if (!is_array($data)) {
        redirectTo("?mode=list");
    }

    $base = $id . "_copy_" . date('Ymd_His');
    $newId = $base;
    $dst = "$JSON_FOLDER/$newId.json";
    $i = 2;
    while (file_exists($dst)) {
        $newId = $base . "_" . $i;
        $dst = "$JSON_FOLDER/$newId.json";
        $i++;
    }

    $now = date('Y-m-d H:i:s');
    $data['created_at'] = $now;
    $data['modified_at'] = $now;

    file_put_contents($dst, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $tplName = basename((string)($data['template'] ?? ''));
    if ($tplName === '') {
        redirectTo("?mode=list&saved=1");
    }
    redirectTo("?mode=form&file=" . urlencode($tplName) . "&edit=" . urlencode($newId));
}

/* -----------------------------------------------------------
 TEMPLATE FIELDS API (für LinkBuilder Dropdown)
----------------------------------------------------------- */
function templateFieldsApi(){
    global $TEMPLATE_FOLDER;

    // Falls vorher HTML kam: weg damit
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    $file = basename($_GET['file'] ?? '');
    if ($file === '') {
        echo json_encode(['ok'=>false,'error'=>'missing file']);
        exit;
    }

    $tpl = loadTemplate($file);
    if (!$tpl || !isset($tpl['sections'])) {
        echo json_encode(['ok'=>false,'error'=>'invalid template']);
        exit;
    }

    $out = [];
    foreach ($tpl['sections'] as $sec => $fields) {
        if (is_array($fields)) {
            $out[$sec] = array_keys($fields);
        }
    }

    echo json_encode(['ok'=>true,'sections'=>$out], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -----------------------------------------------------------
 LINK BUILDER (Checklisten-URL Generator)
----------------------------------------------------------- */
function showLinkBuilder(){
    global $TEMPLATE_FOLDER;

    pageHeaderLocal("Checklisten‑URL Generator");

    // Basis-URL (rkrk_checklist.php)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $selfUrl = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    /* -------------------------------------------------------
       SERVERSEITIG: Template -> Sections -> Labels Map bauen
       (ohne fetch/API, sofort verfügbar)
    ------------------------------------------------------- */
    $fieldMap = [];
    foreach (glob($TEMPLATE_FOLDER . "/*.json") as $tplFile){
        $base = basename($tplFile);
        $tpl  = loadTemplate($base);
        $sections = isset($tpl["sections"]) ? $tpl["sections"] : [];

        $out = [];
        if (is_array($sections)){
            foreach ($sections as $secName => $fields){
                if (!is_array($fields)) continue;
                $out[$secName] = array_keys($fields);
            }
        }
        $fieldMap[$base] = $out;
    }

    // Sicher für JS einbetten
    $fieldMapJson = json_encode(
        $fieldMap,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    $selfUrlJson = json_encode(
        $selfUrl,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    // Template-Optionen bauen
    $tplOptions = "";
    foreach (glob($TEMPLATE_FOLDER . "/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = isset($tpl['title']) ? $tpl['title'] : $base;
        $tplOptions .= "<option value=\"" . safe($base) . "\">" . safe($title) . "</option>\n";
    }

    echo <<<HTML
<div class='rkrk-card'>
  <h3>🔗 Checklisten‑URL erzeugen</h3>
  <p style='color:#555; margin-top:6px;'>
    Wähle ein Template, dann ein Feld aus der Liste, trage den Wert ein – und erzeuge eine Preset‑URL.
  </p>

  <div class='field'>
    <label class='field-label'>Template</label>
    <select id='lb_template' class='text-input' style='width:100%'>
      {$tplOptions}
    </select>
  </div>

  <hr style='margin:18px 0'>

  <h4>📌 Vorbelegte Felder (aus Template wählbar)</h4>

  <div id='lb_params' style='display:flex; flex-direction:column; gap:10px;'>
    <div class='lb_row' style='display:grid; grid-template-columns: 1fr 1fr auto; gap:10px;'>
      <select class='text-input lb_field' style='width:100%'>
        <option value=''>— Feld wählen —</option>
      </select>
      <input type='text' class='text-input lb_value' placeholder='Wert'>
      <button type='button' class='btn' onclick='lb_remove(this)'>✖</button>
    </div>
  </div>

  <div style='margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;'>
    <button type='button' class='btn' onclick='lb_add()'>➕ Feld hinzufügen</button>
    <button type='button' class='btn btn-primary' onclick='lb_generate()'>✅ URL erzeugen</button>
  </div>

  <hr style='margin:18px 0'>

  <h4>⚙️ Optionen</h4>
  <label>
    <input type='checkbox' id='lb_readonly'> Readonly (nur Lesen)
  </label>

  <hr style='margin:18px 0'>

  <h4>🔗 Ergebnis</h4>
  <input type='text' id='lb_result' class='text-input' readonly style='width:100%;'>
  <div style='margin-top:10px; display:flex; gap:10px;'>
    <button type='button' class='btn' onclick='lb_copy()'>📋 Kopieren</button>
  </div>
</div>

<div style='text-align:center;margin-top:20px;'>
  <a href='?mode=listall' class='btn'>⬅ Zurück</a>
</div>

<script>
var LB_SELF = {$selfUrlJson};
var LB_FIELD_MAP = {$fieldMapJson};
var LB_FIELDS = {};

function lb_getCurrentFields(){
  var tplEl = document.getElementById('lb_template');
  var tpl = tplEl ? tplEl.value : '';
  LB_FIELDS = (LB_FIELD_MAP && LB_FIELD_MAP[tpl]) ? LB_FIELD_MAP[tpl] : {};
}

function lb_fillSelect(sel){
  var current = sel.value;
  sel.innerHTML = "<option value=''>— Feld wählen —</option>";

  // Optgroups nach Sektion (ES5 kompatibel)
  for (var sec in LB_FIELDS){
    if (!LB_FIELDS.hasOwnProperty(sec)) continue;
    var labels = LB_FIELDS[sec] || [];
    if (!labels.length) continue;

    var og = document.createElement('optgroup');
    og.label = sec;

    for (var i=0; i<labels.length; i++){
      var label = labels[i];
      var opt = document.createElement('option');
      opt.value = label;
      opt.textContent = label;
      og.appendChild(opt);
    }
    sel.appendChild(og);
  }

  if (current) sel.value = current;
}

function lb_fillAllSelects(){
  var sels = document.querySelectorAll('.lb_field');
  for (var i=0; i<sels.length; i++){
    lb_fillSelect(sels[i]);
  }
}

function lb_reloadFieldsNow(){
  lb_getCurrentFields();
  lb_fillAllSelects();
}

function lb_add(){
  var wrap = document.getElementById('lb_params');
  var row = document.createElement('div');
  row.className = 'lb_row';
  row.style.display = 'grid';
  row.style.gridTemplateColumns = '1fr 1fr auto';
  row.style.gap = '10px';

  row.innerHTML =
    "<select class='text-input lb_field' style='width:100%'><option value=''>— Feld wählen —</option></select>" +
    "<input type='text' class='text-input lb_value' placeholder='Wert'>" +
    "<button type='button' class='btn' onclick='lb_remove(this)'>✖</button>";

  wrap.appendChild(row);

  // neue Select sofort befüllen
  lb_getCurrentFields();
  lb_fillSelect(row.querySelector('.lb_field'));
}

function lb_remove(btn){
  var rows = document.querySelectorAll('.lb_row');
  if (rows.length <= 1){
    // mind. eine Zeile behalten
    var inputs = rows[0].querySelectorAll('input');
    for (var i=0; i<inputs.length; i++) inputs[i].value = '';
    var selects = rows[0].querySelectorAll('select');
    for (var j=0; j<selects.length; j++) selects[j].value = '';
    return;
  }
  btn.parentNode.remove();
}

function lb_generate(){
    var tpl = document.getElementById('lb_template').value;

    var u = LB_SELF + '?mode=form&file=' + encodeURIComponent(tpl);

    document.querySelectorAll('.lb_row').forEach(function(row){
        var field = (row.querySelector('.lb_field') ? row.querySelector('.lb_field').value : '').trim();
        var val   = (row.querySelector('.lb_value') ? row.querySelector('.lb_value').value : '').trim();

        if (field !== ''){
            u += '&' + encodeURIComponent(field) + '=' + encodeURIComponent(val);
        }
    });

    if (document.getElementById('lb_readonly') && document.getElementById('lb_readonly').checked){
        u += '&readonly=1';
    }

    // ✅ WICHTIG: HIER KEIN safe(), KEIN innerHTML
    document.getElementById('lb_result').value = u;
}


function lb_copy(){
    var inp = document.getElementById('lb_result');
    var val = inp.value || '';

    // Falls doch irgendwo HTML-escaped
    val = val.replace(/&amp;/g, '&');

    if (navigator.clipboard && navigator.clipboard.writeText){
        navigator.clipboard.writeText(val);
    } else {
        inp.value = val;
        inp.focus();
        inp.select();
        document.execCommand('copy');
    }
}

// Trigger: Templatewechsel => sofort Felder neu laden
var tplSel = document.getElementById('lb_template');
if (tplSel){
  tplSel.addEventListener('change', lb_reloadFieldsNow);
  tplSel.addEventListener('input', lb_reloadFieldsNow);
}

// Initial füllen
lb_reloadFieldsNow();
</script>
HTML;

    pageFooterLocal();
}



/* -----------------------------------------------------------
   ROUTER / DISPATCH
----------------------------------------------------------- */
switch ($mode) {

	case 'weather':
		handleWeatherMode();
		exit;

	case 'saveweatherclient':
		handleSaveWeatherClientMode();
		exit;

    case 'list':
        showChecklistList();
        break;

    case 'listall':
        showChecklistList(true);
        break;

    case 'form':
        showChecklistForm();
        break;

    case 'save':
        saveChecklist();
        break;

    case 'deletejson':
        deleteJsonChecklist();
        break;

    case 'copyjson':
        copyJsonChecklist();
        break;

    case 'templates':
        showTemplateManager();
        break;

    case 'createtemplate':
        createTemplate();
        break;

    case 'edittemplate':
        editTemplate();
        break;

    case 'savetemplate':
        saveTemplate();
        break;

    case 'deletetemplate':
        deleteTemplate();
        break;

    case 'copytemplate':
        copyTemplate();
        break;

	case 'templatefields':
		templateFieldsApi();
		break;

	case 'linkbuilder':
		showLinkBuilder();
		break;

	case 'print':
		showChecklistPrint();
		break;

    default:
        showChecklistList();
        break;

}



?>
<script>
(function(){
  function isVisible(el){
    return !!(el && (el.offsetParent !== null));
  }

  // Nur echte Eingabefelder (keine Buttons), damit "letztes Feld" wirklich das letzte Feld ist
  function getFields(form){
    const sel = 'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])';
    return Array.from(form.querySelectorAll(sel)).filter(isVisible);
  }

  document.addEventListener('keydown', function(e){
    if (e.key !== 'Enter') return;

    const t = e.target;
    if (!t || !t.matches('input, select, textarea')) return;

    const form = t.form;
    if (!form) return; // nur innerhalb eines Formulars

    const fields = getFields(form);
    const idx = fields.indexOf(t);
    const isLast = (idx >= 0 && idx === fields.length - 1);

    // Ctrl+Enter: nur im letzten Feld absenden
    if (e.ctrlKey){
      if (isLast){
        e.preventDefault();
        form.submit();
      }
      return; // in allen anderen Feldern: nichts tun
    }

    // Normales Enter: Textarea erlaubt Zeilenumbruch
    if (t.tagName === 'TEXTAREA') return;

    // Normales Enter: NICHT submitten, sondern zum nächsten Feld
    e.preventDefault();

    if (idx >= 0 && idx + 1 < fields.length){
      fields[idx + 1].focus();
    }
  }, true);
})();
</script>

</body>
</html>