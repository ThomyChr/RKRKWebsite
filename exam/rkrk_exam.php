<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset.'scripts/rkrk_gen.php');
include_once($folderoffset.'scripts/rkrk_menu.php');
include_once($folderoffset.'rkrk_access_control.php');
include_once('rkrk_exam_common.php');

CheckIPandExit();

//Write to LOGfile
WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","");

echo "<link href='rkrk_exam_theme.css' rel='stylesheet' type='text/css'>" . chr(10);
headerpage("Drohnenrehkitzrettung.ch", false, $folderoffset);
GrantAccess();
if (empty($LoggedOn)) {
	if (function_exists('RegisterForm')) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
	$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
if ($showTopMenu) {
	CreateTopMenu($folderoffset);
	echo "<br><br>";
}



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
headerpage("Digitales Examen", false);
?>
<body>
<?
*/

/* ---------------- FOLDER SETUP ---------------- */
$TEMPLATE_FOLDER  = $folderoffset. $RKRKRelRKRKDataLocation . "data/exam/templates";
$JSON_FOLDER      = $folderoffset. $RKRKRelRKRKDataLocation . "data/exam/results";
$EXAMIconLoc = $folderoffset. $RKRKRelRKRKDataLocation . 'data/exam/images/';

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

/* ---------------- SAFE OUTPUT ---------------- */
function safe($v){
    if (is_array($v)) return htmlspecialchars(implode("; ", $v));
    if (!is_string($v)) return "";
    return htmlspecialchars($v);
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
    $autoRefCounter = 1;

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

            // Placeholder/Info/Ablauf/Kontrollpunkte/Optionen/Referenz/Berechnung nur Strings
            foreach (["placeholder", "info", "process", "checks", "options", "ref", "calc"] as $opt) {
                if (isset($meta[$opt]) && !is_string($meta[$opt])) {
                    unset($tpl["sections"][$secName][$label][$opt]);
                    $changed = true;
                }
            }

            // Referenznummern systemseitig fortlaufend pflegen
            $autoRef = "R" . str_pad((string)$autoRefCounter, 3, "0", STR_PAD_LEFT);
            if (!isset($tpl["sections"][$secName][$label]["ref"]) || (string)$tpl["sections"][$secName][$label]["ref"] !== $autoRef) {
                $tpl["sections"][$secName][$label]["ref"] = $autoRef;
                $changed = true;
            }
            $autoRefCounter++;
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

function rkrk_normalize_ref_id($ref){
    $digits = preg_replace('/\D+/', '', (string)$ref);
    if ($digits === "") return "";
    return "R" . str_pad((string)intval($digits), 3, "0", STR_PAD_LEFT);
}

function rkrk_ref_display($ref){
    return rkrk_normalize_ref_id($ref);
}

function rkrk_type_string($meta){
    if (is_array($meta)) {
        return isset($meta["type"]) ? (string)$meta["type"] : "text";
    }
    return (string)$meta;
}

function rkrk_type_options_string($meta){
    $typeString = rkrk_type_string($meta);
    if (strpos($typeString, "radio:") === 0) return substr($typeString, 6);
    if (strpos($typeString, "multi:") === 0) return substr($typeString, 6);
    if (is_array($meta) && isset($meta["options"]) && trim((string)$meta["options"]) !== "") {
        return trim((string)$meta["options"]);
    }
    return "";
}

function rkrk_number_from_text($raw){
    $s = trim((string)$raw);
    if ($s === "") return null;
    $s = str_replace(",", ".", $s);
    if (preg_match('/-?\d+(?:\.\d+)?/', $s, $m)) {
        return (float)$m[0];
    }
    return null;
}

function rkrk_max_for_type($meta){
    $typeString = strtolower(rkrk_type_string($meta));
    $optsRaw = rkrk_type_options_string($meta);

    if (strpos($typeString, "radio:") === 0) {
        $max = null;
        foreach (explode("|", $optsRaw) as $opt) {
            $n = rkrk_number_from_text($opt);
            if ($n === null) continue;
            if ($max === null || $n > $max) $max = $n;
        }
        return $max;
    }

    if (strpos($typeString, "multi:") === 0) {
        $sum = 0.0;
        $has = false;
        foreach (explode("|", $optsRaw) as $opt) {
            $n = rkrk_number_from_text($opt);
            if ($n === null || $n <= 0) continue;
            $sum += $n;
            $has = true;
        }
        return $has ? $sum : null;
    }

    if ($typeString === "checkbox") {
        return 1.0;
    }

    if ($typeString === "number" || $typeString === "referenz") {
        if (is_array($meta) && isset($meta["placeholder"])) {
            $ph = (string)$meta["placeholder"];
            if (preg_match('/\b\d+(?:[.,]\d+)?\s*-\s*(\d+(?:[.,]\d+)?)/', $ph, $m)) {
                return (float)str_replace(",", ".", $m[1]);
            }
        }
    }

    return null;
}

function rkrk_eval_formula_numeric($formula, $refValues){
    $expr = (string)$formula;
    $expr = preg_replace_callback('/R(\d+)/i', function($m) use ($refValues){
        $ref = "R" . str_pad((string)intval($m[1]), 3, "0", STR_PAD_LEFT);
        $v = isset($refValues[$ref]) ? $refValues[$ref] : 0;
        return (string)(is_numeric($v) ? $v : 0);
    }, $expr);
    $safe = preg_replace('/\s+/', '', $expr);
    if ($safe === "" || !preg_match('/^[0-9+\-*\/().,]*$/', $safe)) {
        return null;
    }
    $phpExpr = str_replace(',', '.', $safe);
    try {
        // Controlled arithmetic-only expression after whitelist check.
        // phpcs:ignore Squiz.PHP.Eval.Discouraged
        $result = @eval('return (' . $phpExpr . ');');
        return is_numeric($result) ? (float)$result : null;
    } catch (Throwable $e) {
        return null;
    }
}

function rkrk_build_ref_max_map($sections){
    $refMaxMap = array();
    $sumFormulas = array();

    if (!is_array($sections)) return $refMaxMap;

    foreach ($sections as $secFields) {
        if (!is_array($secFields)) continue;
        foreach ($secFields as $fMeta) {
            if (!is_array($fMeta)) continue;

            $refId = isset($fMeta["ref"]) ? rkrk_normalize_ref_id($fMeta["ref"]) : "";
            if ($refId === "") continue;

            $typeString = strtolower(rkrk_type_string($fMeta));
            $calc = trim((string)($fMeta["calc"] ?? ""));
            $options = trim((string)($fMeta["options"] ?? ""));
            if (($typeString === "summe" || $typeString === "countfails" || $typeString === "countcritical") && $calc === "" && $options !== "") {
                $calc = $options;
            }

            if ($typeString === "summe" && $calc !== "") {
                $sumFormulas[$refId] = $calc;
                if (!isset($refMaxMap[$refId])) {
                    $refMaxMap[$refId] = 0.0;
                }
                continue;
            }

            $maxVal = rkrk_max_for_type($fMeta);
            if ($maxVal !== null && is_numeric($maxVal)) {
                $refMaxMap[$refId] = (float)$maxVal;
            }
        }
    }

    // Iterativ, damit "Summe von Summen" korrekt aufgeloest wird.
    for ($pass = 0; $pass < 12; $pass++) {
        $changed = false;
        foreach ($sumFormulas as $refId => $formula) {
            $maxVal = rkrk_eval_formula_numeric($formula, $refMaxMap);
            if ($maxVal === null || !is_numeric($maxVal)) continue;
            $maxVal = (float)$maxVal;
            $old = isset($refMaxMap[$refId]) ? (float)$refMaxMap[$refId] : null;
            if ($old === null || abs($old - $maxVal) > 0.0000001) {
                $refMaxMap[$refId] = $maxVal;
                $changed = true;
            }
        }
        if (!$changed) break;
    }

    return $refMaxMap;
}

function rkrk_redirect($url){
    $url = (string)$url;

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    if (!headers_sent()) {
        header("Location:" . $url);
        exit;
    }

    $safeUrl = htmlspecialchars($url, ENT_QUOTES);
    $jsUrl = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>window.location.href=" . $jsUrl . ";</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=" . $safeUrl . "'></noscript>";
    exit;
}

/* ---------------- AUTO DEPENDENT FIELD PARSER ---------------- */
function parseDependentField($label){
    // Exam score labels often contain " - " / " – " (e.g. "... – Ist-Pkt")
    // and must NOT be treated as dependency expressions.
    if (preg_match('/\bIst-Pkt\b/ui', (string)$label) === 1) {
        return null;
    }
    // Point labels like "A.2 ...", "B.7 ..." are normal fields, not dependencies.
    if (preg_match('/^[A-Z]\.\d+\b/u', trim((string)$label)) === 1) {
        return null;
    }

    if (strpos($label, "-") === false && strpos($label, "–") === false){
        return null;
    }

    $parts = preg_split('/[-–]/', $label);
    if (count($parts) !== 2) return null;

    return [
        "parent"  => trim($parts[0]),
        "trigger" => trim($parts[1])
    ];
}

function rkrk_field_key_from_label($label){
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$label));
}

function rkrk_extract_point_code($label){
    $label = trim((string)$label);
    if (preg_match('/^([A-Z]\.\d+)/u', $label, $m)) {
        return $m[1];
    }
    return "";
}

function rkrk_find_point_evaluation_meta($fields, $pointCode){
    if (!is_array($fields) || $pointCode === "") {
        return null;
    }

    $fallback = null;

    foreach ($fields as $candidateLabel => $candidateType) {
        if (strpos($candidateLabel, $pointCode) !== 0) {
            continue;
        }

        $process = "";
        $checks = "";
        if (is_array($candidateType)) {
            $process = isset($candidateType["process"]) ? trim((string)$candidateType["process"]) : "";
            $checks = isset($candidateType["checks"]) ? trim((string)$candidateType["checks"]) : "";
        }

        $meta = [
            "score_label" => $candidateLabel,
            "process" => $process,
            "checks" => $checks
        ];

        // Primär: klassischer Bewertungs-Label mit "Ist-Pkt"
        if (strpos($candidateLabel, "Ist-Pkt") !== false) {
            return $meta;
        }

        // Fallback: erstes passendes Feld mit Ablauf/Kontrollpunkten.
        if (($process !== "" || $checks !== "") && $fallback === null) {
            $fallback = $meta;
        }
    }

    return $fallback;
}

function rkrk_split_checks_list($checks){
    $checks = trim((string)$checks);
    if ($checks === "") {
        return array();
    }

    $parts = preg_split('/\s+[–-]\s+/u', $checks);
    if (!is_array($parts)) {
        $parts = array($checks);
    }

    $out = array();
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p === "") {
            continue;
        }
        $out[] = $p;
    }
    return $out;
}

function rkrk_decode_not_fulfilled_points($raw){
    if (is_array($raw)) {
        $raw = implode(" | ", $raw);
    }
    $raw = trim((string)$raw);
    if ($raw === "") {
        return array();
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded), function($v){
            return $v !== "";
        }));
    }

    $parts = preg_split('/\s*\|\s*/u', $raw);
    if (!is_array($parts)) {
        return array();
    }

    $out = array();
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p !== "") {
            $out[] = $p;
        }
    }
    return $out;
}

/* ---------------- HEADER ---------------- */
function pageHeaderLocal($title){
    global $IconLoc, $EXAMIconLoc, $logopic, $mode;

    // Fallback falls $mode nicht gesetzt ist
    if (!isset($mode)) {
        $mode = $_GET['mode'] ?? 'list';
    }

    $headerLogoPath = htmlspecialchars((string)$EXAMIconLoc . (string)$logopic, ENT_QUOTES);

    echo "
    <link rel='stylesheet' href='../template/flex.css'>
	<div class='rkrk-wrap' data-mode='".safe($mode)."'>
      <div class='cl-header' style='position:relative; min-height:84px;'>
        <h1>".safe($title)."</h1>
        <img src='".$headerLogoPath."' alt='Logo' style='height:80px; position:absolute; top:0; right:0;'>
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

// ---------------- BEMERKUNG NUR BEI (r)/(g)/(o) ----------------
function hasRemarkTrigger(value){
    const v = String(value || '').trim();
    return /\(([rog])\)\s*$/i.test(v);
}

// ---------------- NICHT ERFUELLTE PUNKTE NUR BEI (r)/(o) ----------------
function hasNotFulfilledTrigger(value){
    const v = String(value || '').trim();
    return /\(([ro])\)\s*$/i.test(v);
}

document.querySelectorAll('[data-ro-dependent]').forEach(dep => {
    const parentKey = dep.dataset.roParentKey || dep.dataset.remarkParentKey;
    const inputs = document.querySelectorAll('[data-field-key=\"' + parentKey + '\"]');
    const isNotFulfilledBox = dep.classList.contains('notfulfilled-box');

    function updateRemarkVisibility() {
        let show = false;
        const matcher = isNotFulfilledBox ? hasNotFulfilledTrigger : hasRemarkTrigger;

        inputs.forEach(i => {
            if (i.type === 'radio' && i.checked && matcher(i.value)) {
                show = true;
            }
            if (i.type === 'checkbox' && i.checked && matcher(i.value)) {
                show = true;
            }
            if (i.tagName === 'SELECT' && matcher(i.value)) {
                show = true;
            }
        });

        dep.classList.toggle('visible', show);
        dep.style.display = show ? '' : 'none';
    }

    inputs.forEach(i => {
        i.addEventListener('change', updateRemarkVisibility);
        i.addEventListener('input', updateRemarkVisibility);
    });
    updateRemarkVisibility();
});

// ---------------- NICHT ERFUELLTE PUNKTE BUTTONS ----------------
document.querySelectorAll('.notfulfilled-box').forEach(box => {
    const hidden = box.querySelector('.notfulfilled-hidden');
    const buttons = Array.from(box.querySelectorAll('.notfulfilled-btn'));
    if (!hidden || !buttons.length) return;

    function readSelected() {
        const raw = String(hidden.value || '').trim();
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                return parsed.map(v => String(v).trim()).filter(v => v !== '');
            }
        } catch (e) {}
        return raw.split('|').map(v => String(v).trim()).filter(v => v !== '');
    }

    function writeSelected(values) {
        hidden.value = JSON.stringify(values);
    }

    function syncButtons() {
        const selected = readSelected();
        buttons.forEach(btn => {
            const value = String(btn.dataset.value || '');
            const isSelected = selected.includes(value);
            btn.classList.toggle('selected', isSelected);
            btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const value = String(btn.dataset.value || '');
            const selected = readSelected();
            const idx = selected.indexOf(value);
            if (idx >= 0) {
                selected.splice(idx, 1);
            } else {
                selected.push(value);
            }
            writeSelected(selected);
            syncButtons();
        });
    });

    syncButtons();
});

// ---------------- SUMME-BERECHNUNG UEBER REFERENZEN ----------------
function rkrkToNumber(v){
    if (v == null) return NaN;
    if (typeof v === 'number') return v;
    const s = String(v).replace(',', '.').trim();
    if (!s) return NaN;
    const n = Number(s);
    if (!Number.isNaN(n)) return n;
    const m = s.match(/-?\d+(?:\.\d+)?/);
    return m ? Number(m[0]) : NaN;
}

function rkrkRefNodes(refId){
    const raw = String(refId == null ? '' : refId).trim();
    const digits = raw.replace(/\D+/g, '');
    const normalized = digits ? ('R' + String(parseInt(digits, 10)).padStart(3, '0')) : '';
    const selectors = [];
    if (raw) selectors.push('[data-ref-id=\"' + CSS.escape(raw) + '\"]');
    if (normalized && normalized !== raw) selectors.push('[data-ref-id=\"' + CSS.escape(normalized) + '\"]');
    if (digits) {
        selectors.push('[data-ref-id=\"' + CSS.escape(digits) + '\"]');
        const bare = String(parseInt(digits, 10));
        if (bare !== digits) selectors.push('[data-ref-id=\"' + CSS.escape(bare) + '\"]');
    }

    let nodes = [];
    for (const sel of selectors) {
        nodes = Array.from(document.querySelectorAll(sel));
        if (nodes.length) break;
    }
    return nodes;
}

function rkrkRefValue(refId){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        const checked = nodes.find(n => n.checked);
        if (!checked) return 0;
        const v = rkrkToNumber(checked.value);
        return Number.isFinite(v) ? v : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let sum = 0;
        nodes.forEach(n => {
            if (n.checked) {
                const v = rkrkToNumber(n.value);
                if (Number.isFinite(v)) sum += v;
            }
        });
        return sum;
    }

    const v = rkrkToNumber(nodes[0].value);
    return Number.isFinite(v) ? v : 0;
}

function rkrkRefMaxValue(refId){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    const sumNode = nodes.find(n => String(n.dataset.sumField || '') === '1');
    if (sumNode) {
        const maxFromSum = rkrkToNumber(sumNode.dataset.sumMaxValue);
        return Number.isFinite(maxFromSum) ? maxFromSum : 0;
    }

    if (nodes[0].type === 'radio') {
        let max = 0;
        nodes.forEach(n => {
            const v = rkrkToNumber(n.value);
            if (Number.isFinite(v) && v > max) max = v;
        });
        return max;
    }

    if (nodes[0].type === 'checkbox') {
        const isMulti = String(nodes[0].name || '').endsWith('[]');
        if (isMulti) {
            let sum = 0;
            nodes.forEach(n => {
                const v = rkrkToNumber(n.value);
                if (Number.isFinite(v) && v > 0) sum += v;
            });
            return sum;
        }
        const v = rkrkToNumber(nodes[0].value);
        return Number.isFinite(v) && v > 0 ? v : 1;
    }

    const v = rkrkToNumber(nodes[0].value);
    return Number.isFinite(v) && v > 0 ? v : 0;
}

function rkrkIsFailValue(v){
    return /\(r\)\s*$/i.test(String(v || '').trim());
}

function rkrkIsCriticalValue(v){
    return /\(([ro])\)\s*$/i.test(String(v || '').trim());
}

function rkrkRefFailCount(refId){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        const checked = nodes.find(n => n.checked);
        return checked && rkrkIsFailValue(checked.value) ? 1 : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let cnt = 0;
        nodes.forEach(n => {
            if (n.checked && rkrkIsFailValue(n.value)) cnt += 1;
        });
        return cnt;
    }

    return rkrkIsFailValue(nodes[0].value) ? 1 : 0;
}

function rkrkRefCountByMatcher(refId, matcher){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        const checked = nodes.find(n => n.checked);
        return checked && matcher(checked.value) ? 1 : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let cnt = 0;
        nodes.forEach(n => {
            if (n.checked && matcher(n.value)) cnt += 1;
        });
        return cnt;
    }

    return matcher(nodes[0].value) ? 1 : 0;
}

function rkrkRefMaxByMatcher(refId, matcher){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        return nodes.some(n => matcher(n.value)) ? 1 : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let max = 0;
        nodes.forEach(n => {
            if (matcher(n.value)) max += 1;
        });
        return max;
    }

    return matcher(nodes[0].value) ? 1 : 0;
}

function rkrkRefFailMax(refId){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        return nodes.some(n => rkrkIsFailValue(n.value)) ? 1 : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let max = 0;
        nodes.forEach(n => {
            if (rkrkIsFailValue(n.value)) max += 1;
        });
        return max;
    }

    return rkrkIsFailValue(nodes[0].value) ? 1 : 0;
}

function rkrkComputeFormula(formula){
    let expr = String(formula || '');
    expr = expr.replace(/R(\d+)/gi, (_, n) => String(rkrkRefValue(n)));
    expr = expr.replace(/\[(\d+)\]/g, (_, n) => String(rkrkRefValue(n)));
    expr = expr.replace(/\{(\d+)\}/g, (_, n) => String(rkrkRefValue(n)));
    expr = expr.replace(/#(\d+)/g, (_, n) => String(rkrkRefValue(n)));

    const safeExpr = expr.replace(/\s+/g, '');
    if (!/^[0-9+\-*/().,]*$/.test(safeExpr)) return NaN;

    try {
        const jsExpr = safeExpr.replace(/,/g, '.');
        // eslint-disable-next-line no-new-func
        return Number(Function('return (' + jsExpr + ');')());
    } catch (e) {
        return NaN;
    }
}

function rkrkComputeFormulaWithResolver(formula, resolver){
    let expr = String(formula || '');
    expr = expr.replace(/R(\d+)/gi, (_, n) => String(resolver(n)));
    expr = expr.replace(/\[(\d+)\]/g, (_, n) => String(resolver(n)));
    expr = expr.replace(/\{(\d+)\}/g, (_, n) => String(resolver(n)));
    expr = expr.replace(/#(\d+)/g, (_, n) => String(resolver(n)));

    const safeExpr = expr.replace(/\s+/g, '');
    if (!/^[0-9+\-*/().,]*$/.test(safeExpr)) return NaN;
    try {
        const jsExpr = safeExpr.replace(/,/g, '.');
        // eslint-disable-next-line no-new-func
        return Number(Function('return (' + jsExpr + ');')());
    } catch (e) {
        return NaN;
    }
}

function rkrkUpdateSumFields(){
    const sumInputs = Array.from(document.querySelectorAll('[data-sum-field=\"1\"]'));
    if (!sumInputs.length) return;

    // Multiple passes allow Summe fields referencing other Summe fields.
    for (let pass = 0; pass < 4; pass += 1) {
        sumInputs.forEach(sumInput => {
            const formula = sumInput.dataset.sumCalc || '';
            if (!formula) return;
            const val = rkrkComputeFormula(formula);
            if (Number.isFinite(val)) {
                const out = String(Math.round((val + Number.EPSILON) * 1000) / 1000);
                sumInput.value = out;
                const display = document.querySelector('[data-sum-display-for=\"' + CSS.escape(String(sumInput.dataset.fieldKey || '')) + '\"]');
                if (display) display.textContent = out;
            }
        });
    }

    // Max-Werte ebenfalls iterativ, damit Summen mit Summen-Referenzen stimmen.
    for (let pass = 0; pass < 6; pass += 1) {
        sumInputs.forEach(sumInput => {
            const formula = sumInput.dataset.sumCalc || '';
            if (!formula) return;
            const maxVal = rkrkComputeFormulaWithResolver(formula, (ref) => rkrkRefMaxValue(ref));
            const holder = document.querySelector('[data-sum-max-for=\"' + CSS.escape(String(sumInput.dataset.fieldKey || '')) + '\"]');
            if (Number.isFinite(maxVal)) {
                const out = Math.round((maxVal + Number.EPSILON) * 1000) / 1000;
                sumInput.dataset.sumMaxValue = String(out);
                if (holder) holder.textContent = String(out);
            } else if (holder) {
                holder.textContent = '–';
            }
        });
    }

    sumInputs.forEach(sumInput => {
        const holder = document.querySelector('[data-sum-max-for=\"' + CSS.escape(String(sumInput.dataset.fieldKey || '')) + '\"]');
        if (!holder) return;
        const maxFromData = rkrkToNumber(sumInput.dataset.sumMaxValue);
        if (Number.isFinite(maxFromData)) {
            holder.textContent = String(Math.round((maxFromData + Number.EPSILON) * 1000) / 1000);
        }
    });
}

function rkrkUpdateCountFailsFields(){
    document.querySelectorAll('[data-countfails-field=\"1\"]').forEach(input => {
        const isAuto = String(input.dataset.countfailsAuto || '0') === '1';
        if (!isAuto) return;
        const formula = String(input.dataset.countfailsCalc || '').trim();
        let val = 0;
        let maxVal = 0;
        if (formula !== '') {
            const refs = formula.match(/R?\d+/gi) || [];
            const unique = Array.from(new Set(refs.map(r => String(r).replace(/\D+/g, '')))).filter(Boolean);
            unique.forEach(ref => {
                val += rkrkRefCountByMatcher(ref, rkrkIsFailValue);
                maxVal += rkrkRefMaxByMatcher(ref, rkrkIsFailValue);
            });
        }
        input.value = String(val);
        const holder = document.querySelector('[data-count-max-for=\"' + CSS.escape(String(input.dataset.fieldKey || '')) + '\"]');
        if (holder) {
            holder.textContent = String(maxVal);
        }
    });
}

function rkrkUpdateCountCriticalFields(){
    document.querySelectorAll('[data-countcritical-field=\"1\"]').forEach(input => {
        const isAuto = String(input.dataset.countcriticalAuto || '0') === '1';
        if (!isAuto) return;
        const formula = String(input.dataset.countcriticalCalc || '').trim();
        let val = 0;
        let maxVal = 0;
        if (formula !== '') {
            const refs = formula.match(/R?\d+/gi) || [];
            const unique = Array.from(new Set(refs.map(r => String(r).replace(/\D+/g, '')))).filter(Boolean);
            unique.forEach(ref => {
                val += rkrkRefCountByMatcher(ref, rkrkIsCriticalValue);
                maxVal += rkrkRefMaxByMatcher(ref, rkrkIsCriticalValue);
            });
        }
        input.value = String(val);
        const holder = document.querySelector('[data-countcritical-max-for=\"' + CSS.escape(String(input.dataset.fieldKey || '')) + '\"]');
        if (holder) {
            holder.textContent = String(maxVal);
        }
    });
}

document.addEventListener('input', rkrkUpdateSumFields);
document.addEventListener('change', rkrkUpdateSumFields);
document.addEventListener('input', rkrkUpdateCountFailsFields);
document.addEventListener('change', rkrkUpdateCountFailsFields);
document.addEventListener('input', rkrkUpdateCountCriticalFields);
document.addEventListener('change', rkrkUpdateCountCriticalFields);
rkrkUpdateSumFields();
rkrkUpdateCountFailsFields();
rkrkUpdateCountCriticalFields();

// ---------------- COLORED OPTION BUTTON UI SYNC ----------------
document.querySelectorAll('.option-grid').forEach(grid => {
    const options = Array.from(grid.querySelectorAll('.color-option'));
    if (!options.length) return;

    const syncColorSelection = () => {
        options.forEach(opt => {
            const input = opt.querySelector('input');
            if (!input) return;
            const isChecked = !!input.checked;
            opt.classList.toggle('selected', isChecked);
            opt.setAttribute('aria-pressed', isChecked ? 'true' : 'false');
        });
    };

    options.forEach(opt => {
        const input = opt.querySelector('input');
        if (!input) return;
        input.addEventListener('change', syncColorSelection);
        input.addEventListener('input', syncColorSelection);
    });

    syncColorSelection();
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
		
    pageHeaderLocal("Examen‑Templates");

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
                    <a href='?mode=form&file=$base'>Neues Examen</a> |
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
        rkrk_redirect("?mode=templates");
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

    rkrk_redirect("?mode=edittemplate&file=$file");
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

    rkrk_redirect("?mode=templates");
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
        rkrk_redirect("?mode=templates");
    }

    $tpl = loadTemplate($file);
    if (!$tpl) {
        rkrk_redirect("?mode=templates");
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

    rkrk_redirect("?mode=edittemplate&file=$newFile");
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

    // 1) Standort aus dem Examen holen (falls vorhanden)
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

// Beim Laden des Examens automatisch Wetter laden
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
                <th style='width:360px'>Label</th>
                <th style='width:190px'>Typ</th>
                <th style='width:320px'>Placeholder</th>
                <th>N/R</th>
                <th>Aktion</th>
            </tr>";

    $flat = [];
    foreach ($sections as $secName => $fields){
        foreach ($fields as $label => $type){
            $placeholder = "";
            $info = "";
            $process = "";
            $checks = "";
            $options = "";
            $ref = "";
            $calc = "";
            $inname = "";
            $inresult = "";
            $typeVal = $type;

            if (is_array($type)){
                $placeholder = isset($type["placeholder"]) ? $type["placeholder"] : "";
                $info = isset($type["info"]) ? $type["info"] : "";
                $process = isset($type["process"]) ? $type["process"] : "";
                $checks = isset($type["checks"]) ? $type["checks"] : "";
                $options = isset($type["options"]) ? $type["options"] : "";
                $ref = isset($type["ref"]) ? $type["ref"] : "";
                $calc = isset($type["calc"]) ? $type["calc"] : "";
                $inname = isset($type["inname"]) ? $type["inname"] : "0";
                $inresult = isset($type["inresult"]) ? $type["inresult"] : "0";
                $typeVal = isset($type["type"]) ? $type["type"] : $typeVal;
                if ($typeVal === "summe" && $calc === "" && $options !== "") {
                    $calc = $options;
                }
            }

            $flat[] = [
                "sec"         => $secName,
                "label"       => $label,
                "type"        => $typeVal,
                "placeholder" => $placeholder,
                "info"        => $info,
                "process"     => $process,
                "checks"      => $checks,
                "options"     => $options,
                "ref"         => $ref,
                "calc"        => $calc,
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
        elseif (isset($row["options"]) && trim((string)$row["options"]) !== ""){
            $optVal = trim((string)$row["options"]);
        }

        echo "<tr class='template-row-main'>";

        echo "<td style='white-space:nowrap'>";
        if ($isFirstRowOfSection){
            echo "<span style='display:inline-block; margin-right:6px'>";
            echo "<button type='submit' name='move_sec_up' value='".safe($row["sec"])."'>⇑</button>";
            echo "<button type='submit' name='move_sec_down' value='".safe($row["sec"])."'>⇓</button>";
            echo "</span>";
        }
        echo "<input type='text' name='sec[$i]' value='".safe($row["sec"])."' style='width:160px; text-align:right; float:right' placeholder='Sektion'>";
        echo "</td>";

        echo "<td>
                <input type='text' name='label[$i]' value='".safe($row["label"])."' placeholder='Label' style='width:100%; min-width:300px;'>
                <small style='display:block; margin-top:3px; color:#5d6f5a; font-size:12px;'>Ref: ".safe(rkrk_ref_display($row["ref"]))."</small>
                <input type='hidden' name='ref[$i]' value='".safe($row["ref"])."'>
              </td>";

        echo "<td><select name='type[$i]' class='template-type-select' style='width:100%; min-width:150px;'>";
        
		
		$types = [ 
		  "text"=>"Text",
		  "textarea"=>"Textarea",
		  "print"=>"Print (Klapptext)",
		  "referenz"=>"Referenz",
		  "summe"=>"Summe",
		  "countfails"=>"CountFails",
		  "countcritical"=>"CountCritical",
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

        echo "<td><input type='text' name='placeholder[$i]' value='".safe($row["placeholder"])."' placeholder='Platzhalter' style='width:100%;'></td>";

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
        echo "<tr class='template-row-details'>
                <td></td>
                <td colspan='6'>
                    <div class='template-detail-grid' style='display:grid; grid-template-columns:repeat(2, minmax(260px, 1fr)); gap:10px 14px;'>
                        <div>
                            <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Optionen / Berechnung (Summe/CountFails)</small>
                            <div class='template-options-calc-cell'>
                                <textarea class='template-options-input' name='options[$i]' rows='2' placeholder='a|b|c' style='width:100%;'>".safe($optVal)."</textarea>
                                <input class='template-calc-input' type='text' name='calc[$i]' value='".safe($row["calc"])."' placeholder='z.B. R001+R002 oder R010|R011' style='width:100%; margin-top:6px; display:none;'>
                            </div>
                        </div>
                        <div>
                            <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Info</small>
                            <textarea name='info[$i]' rows='4' placeholder='Informationen (mehrzeilig)' style='width:100%;'>".safe($row["info"])."</textarea>
                        </div>
                        <div>
                            <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Ablauf (process)</small>
                            <textarea name='process[$i]' rows='2' placeholder='Ablauf (process)' style='width:100%;'>".safe($row["process"])."</textarea>
                        </div>
                        <div>
                            <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Kontrollpunkte (checks)</small>
                            <textarea name='checks[$i]' rows='2' placeholder='Kontrollpunkte (checks)' style='width:100%;'>".safe($row["checks"])."</textarea>
                        </div>
                    </div>
                </td>
              </tr>";
    }

    echo "</table>";

    echo "<h3>Neues Feld hinzufügen</h3>

    <table class='template-table'>
        <tr>
            <th>Sektion</th>
            <th style='width:360px'>Label</th>
            <th style='width:190px'>Typ</th>
            <th style='width:320px'>Placeholder</th>
            <th>N/R</th>
        </tr>

        <tr class='template-row-main'>
            <td><input type='text' name='new_sec' placeholder='Sektion'></td>
            <td>
                <input type='text' name='new_label' placeholder='Label' style='width:100%; min-width:300px;'>
                <small style='display:block; margin-top:3px; color:#5d6f5a; font-size:12px;'>Ref: automatisch (Rxxx)</small>
                <input type='hidden' name='new_ref' value=''>
            </td>
            <td>
                <select name='new_type' class='text-input template-type-select' style='width:100%; min-width:150px;'>
                    <option value='text'>Text</option>
                    <option value='textarea'>Textarea</option>
                    <option value='print'>Print (Klapptext)</option>
                    <option value='referenz'>Referenz</option>
                    <option value='summe'>Summe</option>
                    <option value='countfails'>CountFails</option>
                    <option value='countcritical'>CountCritical</option>
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
            <td><input type='text' name='new_placeholder' placeholder='Platzhalter' style='width:100%;'></td>

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
        <tr class='template-row-details'>
            <td></td>
            <td colspan='5'>
                <div class='template-detail-grid' style='display:grid; grid-template-columns:repeat(2, minmax(260px, 1fr)); gap:10px 14px;'>
                    <div>
                        <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Optionen / Berechnung (Summe/CountFails)</small>
                        <div class='template-options-calc-cell'>
                            <input class='template-options-input' type='text' name='new_options' placeholder='a|b|c' style='width:100%;'>
                            <input class='template-calc-input' type='text' name='new_calc' placeholder='z.B. R001+R002 oder R010|R011' style='margin-top:6px; width:100%; display:none;'>
                        </div>
                    </div>
                    <div>
                        <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Info</small>
                        <textarea name='new_info' rows='4' placeholder='Info (mehrzeilig)' style='width:100%;'></textarea>
                    </div>
                    <div>
                        <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Ablauf (process)</small>
                        <textarea name='new_process' rows='2' placeholder='Ablauf (process)' style='width:100%;'></textarea>
                    </div>
                    <div>
                        <small style='display:block; margin-bottom:4px; color:#5d6f5a;'>Kontrollpunkte (checks)</small>
                        <textarea name='new_checks' rows='2' placeholder='Kontrollpunkte (checks)' style='width:100%;'></textarea>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <button class='btn btn-primary'>Template speichern</button>
    </form>
    <div class='rkrk-card' style='margin-top:14px; background:#f8fbf6; border-color:#d6dfcc;'>
        <strong>Legende Berechnungskriterien</strong>
        <div style='margin-top:8px; font-size:13px; color:#35523a; line-height:1.5;'>
            <div><strong>Summe:</strong> Rechnet numerische Werte aus Referenzen, z.B. <code>R010+R011-R012</code>.</div>
            <div><strong>CountFails:</strong> Zählt nur Referenz-Felder mit ausgewählter Option <code>(r)</code>. Für gezielte Berechnung im Feld <em>Berechnung</em> z.B. <code>R010|R011|R012</code> eintragen.</div>
            <div><strong>CountCritical:</strong> Zählt Referenz-Felder mit ausgewählter Option <code>(r)</code> oder <code>(o)</code>. Ebenfalls per Referenzliste steuerbar, z.B. <code>R020|R021</code>.</div>
            <div style='margin-top:8px;'><strong>Optionen für Auswahltypen:</strong></div>
            <div><strong>Radio:</strong> Genau eine Auswahl. Optionen mit <code>|</code> trennen, z.B. <code>Erfüllt (g)|Teilweise (o)|Nicht erfüllt (r)</code>.</div>
            <div><strong>Multi:</strong> Mehrfachauswahl. Optionen ebenfalls mit <code>|</code> trennen, z.B. <code>Kriterium A (g)|Kriterium B (o)|Kriterium C (r)</code>.</div>
            <div><strong>Checkbox:</strong> Einzelnes Ja/Nein-Feld (0/1). Normalerweise keine Optionen nötig; falls Farblogik benötigt wird, Marker direkt im Feldnamen setzen, z.B. <code>Schutzbrille (o)</code>.</div>
            <div><strong>Farbmarker:</strong> <code>(g)</code> = grün, <code>(o)</code> = orange, <code>(r)</code> = rot (wirkt auf Anzeige/Print und auf Count-Regeln).</div>
        </div>
    </div>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const weatherBox = document.getElementById('weatherBox');
		const hidden = document.getElementById('weather_json');

		const isOptionsType = (t) => ['radio', 'checkbox', 'multi'].includes(String(t || '').toLowerCase());
		const isSumType = (t) => {
			const v = String(t || '').toLowerCase();
			return v === 'summe' || v === 'countfails' || v === 'countcritical';
		};

		const toggleRowFields = (row, typeVal) => {
			const detailsRow = row.nextElementSibling && row.nextElementSibling.classList.contains('template-row-details')
				? row.nextElementSibling
				: row;
			const mergedCell = row.querySelector('.template-options-calc-cell') || detailsRow.querySelector('.template-options-calc-cell');
			const optionsInput = mergedCell ? mergedCell.querySelector('.template-options-input') : null;
			const calcInput = mergedCell ? mergedCell.querySelector('.template-calc-input') : null;
			const showOptions = isOptionsType(typeVal);
			const showCalc = isSumType(typeVal);
			const showMerged = showOptions || showCalc;

			if (mergedCell) {
				// Keep column alignment stable; only hide controls, not table cells.
				mergedCell.style.visibility = showMerged ? '' : 'hidden';
				mergedCell.style.opacity = showMerged ? '1' : '0.45';
			}
			if (optionsInput) {
				optionsInput.style.display = showOptions ? '' : 'none';
				optionsInput.disabled = !showOptions;
			}
			if (calcInput) {
				calcInput.style.display = showCalc ? '' : 'none';
				calcInput.disabled = !showCalc;
			}
		};

		document.querySelectorAll('tr').forEach(row => {
			const typeSel = row.querySelector('.template-type-select');
			if (!typeSel) return;
			const apply = () => toggleRowFields(row, typeSel.value);
			typeSel.addEventListener('change', apply);
			apply();
		});

		if (weatherBox && hidden) {
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
		}
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
    $secArr   = isset($_POST['sec']) ? $_POST['sec'] : [];
    $labelArr = isset($_POST['label']) ? $_POST['label'] : [];
    $typeArr  = isset($_POST['type']) ? $_POST['type'] : [];
    $placeholderArr = isset($_POST['placeholder']) ? $_POST['placeholder'] : [];
    $infoArr  = isset($_POST['info']) ? $_POST['info'] : [];
    $processArr = isset($_POST['process']) ? $_POST['process'] : [];
    $checksArr = isset($_POST['checks']) ? $_POST['checks'] : [];
    $refArr = isset($_POST['ref']) ? $_POST['ref'] : [];
    $calcArr = isset($_POST['calc']) ? $_POST['calc'] : [];
    $optionsArr = isset($_POST['options']) ? $_POST['options'] : [];
    $innameArr = isset($_POST['inname']) ? $_POST['inname'] : [];
    $inresultArr = isset($_POST['inresult']) ? $_POST['inresult'] : [];

    /* Flache Liste erzeugen */
    $flat = [];
    foreach ($secArr as $i => $sec){
        $sec   = trim($sec);
        $label = trim(isset($labelArr[$i]) ? $labelArr[$i] : "");
        $typeBase = trim(isset($typeArr[$i]) ? $typeArr[$i] : "");
        $opts = trim(isset($optionsArr[$i]) ? $optionsArr[$i] : "");
        $placeholder = trim(isset($placeholderArr[$i]) ? $placeholderArr[$i] : "");
        $info = trim(isset($infoArr[$i]) ? $infoArr[$i] : "");
        $process = trim(isset($processArr[$i]) ? $processArr[$i] : "");
        $checks = trim(isset($checksArr[$i]) ? $checksArr[$i] : "");
        $ref = rkrk_normalize_ref_id(isset($refArr[$i]) ? $refArr[$i] : "");
        $calc = trim(isset($calcArr[$i]) ? $calcArr[$i] : "");

        $inname   = (isset($innameArr[$i]) ? $innameArr[$i] : "0") === "1" ? "1" : "0";
        $inresult = (isset($inresultArr[$i]) ? $inresultArr[$i] : "0") === "1" ? "1" : "0";

        if ($sec === "" || $label === "") continue;

        if ($typeBase === "radio" && $opts !== "") {
            $type = "radio:" . $opts;
        }
        elseif ($typeBase === "multi" && $opts !== "") {
            $type = "multi:" . $opts;
        }
        elseif ($typeBase === "summe") {
            // Robust fallback: if calc was posted via shared options field, keep it.
            if ($calc === "" && $opts !== "") {
                $calc = $opts;
            }
            $type = "summe";
        }
        elseif ($typeBase === "countfails") {
            if ($calc === "" && $opts !== "") {
                $calc = $opts;
            }
            $type = "countfails";
        }
        elseif ($typeBase === "countcritical") {
            if ($calc === "" && $opts !== "") {
                $calc = $opts;
            }
            $type = "countcritical";
        }
        else {
            $type = $typeBase;
        }

        $flat[] = [
            "sec"         => $sec,
            "label"       => $label,
            "type"        => $type,
            "options"     => $opts,
            "placeholder" => $placeholder,
            "info"        => $info,
            "process"     => $process,
            "checks"      => $checks,
            "ref"         => $ref,
            "calc"        => $calc,
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
        $newProcess = isset($_POST['new_process']) ? trim($_POST['new_process']) : "";
        $newChecks = isset($_POST['new_checks']) ? trim($_POST['new_checks']) : "";
        $newRef = isset($_POST['new_ref']) ? rkrk_normalize_ref_id($_POST['new_ref']) : "";
        $newCalc = isset($_POST['new_calc']) ? trim($_POST['new_calc']) : "";

        $newinname   = (isset($_POST['new_inname']) ? $_POST['new_inname'] : "0") === "1" ? "1" : "0";
        $newinresult = (isset($_POST['new_inresult']) ? $_POST['new_inresult'] : "0") === "1" ? "1" : "0";

        if (($newType === "radio" || $newType === "multi") && $newOpts !== "") {
            $newType = $newType . ":" . $newOpts;
        }
        elseif (($newType === "summe" || $newType === "countfails" || $newType === "countcritical") && $newCalc === "" && $newOpts !== "") {
            // Robust fallback for shared options/calc editor cell.
            $newCalc = $newOpts;
        }

        $flat[] = [
            "sec"         => $newSec,
            "label"       => $newLabel,
            "type"        => $newType,
            "options"     => $newOpts,
            "placeholder" => $newPlaceholder,
            "info"        => $newInfo,
            "process"     => $newProcess,
            "checks"      => $newChecks,
            "ref"         => $newRef,
            "calc"        => $newCalc,
            "inname"      => $newinname,
            "inresult"    => $newinresult
        ];
    }

    // Referenzen immer fortlaufend und stabil neu vergeben
    foreach ($flat as $idx => $row) {
        $flat[$idx]["ref"] = "R" . str_pad((string)($idx + 1), 3, "0", STR_PAD_LEFT);
    }

    /* Struktur wiederherstellen */
    $sections = [];
    foreach ($flat as $row){
        if (!isset($sections[$row["sec"]])) $sections[$row["sec"]] = [];

        $entry = ["type" => $row["type"]];

        if ($row["placeholder"] !== "") $entry["placeholder"] = $row["placeholder"];
        if ($row["info"] !== "") $entry["info"] = $row["info"];
        if (isset($row["process"]) && $row["process"] !== "") $entry["process"] = $row["process"];
        if (isset($row["checks"]) && $row["checks"] !== "") $entry["checks"] = $row["checks"];
        if (isset($row["options"]) && trim((string)$row["options"]) !== "") $entry["options"] = trim((string)$row["options"]);
        if (isset($row["ref"]) && $row["ref"] !== "") $entry["ref"] = $row["ref"];
        if (isset($row["calc"]) && $row["calc"] !== "") $entry["calc"] = $row["calc"];

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

    rkrk_redirect("?mode=edittemplate&file=$file");
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

function rkrk_strip_option_markers($value){
    $cleanOne = function($v){
        return trim((string)preg_replace('/\s*\(([rog])\)\s*$/iu', '', (string)$v));
    };

    if (is_array($value)) {
        $out = array();
        foreach ($value as $v) {
            $c = $cleanOne($v);
            if ($c !== "") $out[] = $c;
        }
        return $out;
    }

    $raw = trim((string)$value);
    if ($raw === "") return "";

    if (strpos($raw, ",") !== false || strpos($raw, ";") !== false) {
        $parts = preg_split('/\s*[,;]\s*/', $raw);
        $out = array();
        foreach ((array)$parts as $p) {
            $c = $cleanOne($p);
            if ($c !== "") $out[] = $c;
        }
        return implode(", ", $out);
    }

    return $cleanOne($raw);
}

/* -----------------------------------------------------------
   EXAMEN-LISTE PRINT
----------------------------------------------------------- */
function showChecklistPrint(){
    global $JSON_FOLDER, $TEMPLATE_FOLDER, $IconLoc, $EXAMIconLoc, $logopic;

    $id = basename($_GET['id'] ?? "");
    $path = "$JSON_FOLDER/$id.json";

    if (!file_exists($path)) {
        echo "Examen nicht gefunden.";
        exit;
    }

    $data = json_decode(file_get_contents($path), true);
    $tplName = $data["template"] ?? "";
    $tpl = loadTemplate($tplName);

    $title = $tpl["title"] ?? "Examen";
    $fields = $data["fields"] ?? [];
    $refMaxMap = rkrk_build_ref_max_map($tpl["sections"] ?? array());

    // 🔥 Print-Dateiname = gespeicherter JSON-Dateiname
    $printTitle = $id . " – Druckansicht";
    $showAblauf = !isset($_GET['hide_ablauf']) || $_GET['hide_ablauf'] !== '1';
    $showKriterien = !isset($_GET['hide_kriterien']) || $_GET['hide_kriterien'] !== '1';
    $showPrintType = !isset($_GET['hide_printtype']) || $_GET['hide_printtype'] !== '1';
    $printLogoPath = (string)$EXAMIconLoc . (string)$logopic;
    $printLogoSrc = htmlspecialchars($printLogoPath, ENT_QUOTES);

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
    position: relative;
    min-height: 86px;
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
.print-logo {
    position: absolute;
    top: 0;
    right: 0;
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
.value-meta {
    margin-top: 4px;
    font-size: 12px;
    color: #5d6f5a;
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
.print-options {
    margin: 6px 0 18px 0;
    padding: 10px 12px;
    border: 1px solid #d6dfcc;
    border-radius: 8px;
    background: #f8fbf6;
}
.print-options label {
    margin-right: 14px;
    font-size: 13px;
    white-space: nowrap;
}
.print-options button {
    padding: 4px 10px;
    border: 1px solid #4F6A3C;
    background: #4F6A3C;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
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
        <img src='".$printLogoSrc."'>
    </div>
</div>
<form method='get' class='print-options no-print'>
    <input type='hidden' name='mode' value='print'>
    <input type='hidden' name='id' value='".htmlspecialchars($id, ENT_QUOTES)."'>
    <label><input type='checkbox' name='hide_ablauf' value='1' ".($showAblauf ? "" : "checked")."> Ablauf ausblenden</label>
    <label><input type='checkbox' name='hide_kriterien' value='1' ".($showKriterien ? "" : "checked")."> Kriterien ausblenden</label>
    <label><input type='checkbox' name='hide_printtype' value='1' ".($showPrintType ? "" : "checked")."> Print-Text ausblenden</label>
    <button type='submit'>Ansicht aktualisieren</button>
</form>
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
            $typeString = strtolower(rkrk_type_string($meta));
            if (is_array($val)) $val = implode(", ", $val);
            $val = formatPrintTimeByLabel($label, $val);
            $isRemarkField = (preg_match('/\bBemerkung\b/ui', (string)$label) === 1);
            $calc = "";
            $process = "";
            $pointCode = rkrk_extract_point_code($label);
            $missedItems = array();
            $printHtml = "";
            if (is_array($meta)) {
                $calc = trim((string)($meta["calc"] ?? ""));
                $process = trim((string)($meta["process"] ?? ""));
                if ($typeString === "print") {
                    $printHtml = trim((string)($meta["info"] ?? ""));
                    if ($printHtml === "") {
                        $printHtml = trim((string)($meta["placeholder"] ?? ""));
                    }
                }
                if ($calc === "") {
                    $calc = trim((string)($meta["options"] ?? ""));
                }
            }
            if ($typeString === "print" && $printHtml !== "") {
                $valHtml = (strip_tags($printHtml) !== $printHtml) ? $printHtml : nl2br(safe($printHtml));
            } elseif ($typeString === "checkbox" || strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0) {
                $valHtml = colorizePrintValue($val, $typeString);
            } else {
                $valHtml = rkrk_print_weather_value($secName, $label, $val);
            }

            // Druckansicht: leere Bemerkungen ausblenden
            if ($isRemarkField && trim((string)$val) === "") {
                continue;
            }
            if ($pointCode !== "") {
                $missedLabel = $pointCode . " Nicht erfüllte Punkte";
                $missedRaw = isset($fields[$secName][$missedLabel]) ? $fields[$secName][$missedLabel] : "";
                $missedItems = rkrk_decode_not_fulfilled_points($missedRaw);
            }
            $metaInfo = "";
            if ($typeString === "print" && !$showPrintType) {
                continue;
            }
            if ($typeString === "summe" && $calc !== "") {
                $maxVal = rkrk_eval_formula_numeric($calc, $refMaxMap);
                $maxTxt = ($maxVal !== null) ? (" | Max: " . safe((string)$maxVal)) : "";
                $metaInfo = "<div class='value-meta'>Berechnung: " . safe($calc) . $maxTxt . "</div>";
            } elseif ($typeString === "countfails") {
                if ($calc !== "") {
                    $metaInfo = "<div class='value-meta'>CountFails über: " . safe($calc) . "</div>";
                } else {
                    $metaInfo = "<div class='value-meta'>CountFails: alle gewählten (r)-Bewertungen</div>";
                }
            } elseif ($typeString === "countcritical") {
                if ($calc !== "") {
                    $metaInfo = "<div class='value-meta'>CountCritical über: " . safe($calc) . "</div>";
                } else {
                    $metaInfo = "<div class='value-meta'>CountCritical: alle gewählten (r)/(o)-Bewertungen</div>";
                }
            }
            if ($showAblauf && $process !== "") {
                $metaInfo .= "<div class='value-meta'><strong>Ablauf:</strong> " . nl2br(safe($process)) . "</div>";
            }
            if ($showKriterien && !empty($missedItems)) {
                $metaInfo .= "<div class='value-meta'><strong>Nicht erfüllte Kriterien:</strong> " . safe(implode(" | ", $missedItems)) . "</div>";
            }

            // 🔥 Spezialfelder fett
            $isHighlight = in_array($label, [
                "Datum & Zeit", "Auftrag", "Feld", "Ort", "Flugort", "Flugzeit"
            ]);

            $cls = $isHighlight ? "field highlight" : "field";

            echo "<div class='$cls'>
                    <div class='label'>".htmlspecialchars($label)."</div>
                    <div class='value'>{$valHtml}{$metaInfo}</div>
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
                if (preg_match('/\bNicht erfüllte Punkte$/u', (string)$label)) {
                    continue;
                }
                if (preg_match('/\bBemerkung\b/ui', (string)$label) === 1 && trim((string)$value) === "") {
                    continue;
                }
                if (is_array($value)) $value = implode(", ", $value);
                $value = formatPrintTimeByLabel($label, $value);
                $valueHtml = colorizePrintValue($value);

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
   EXAMEN-LISTE
----------------------------------------------------------- */
function showChecklistList($showAll = false){
    global $JSON_FOLDER, $TEMPLATE_FOLDER;

    // Lokaler Modus – verhindert Undefined Variable Notices
    $mode = $_GET['mode'] ?? 'list';
    $filterTemplate = trim((string)($_GET['f_template'] ?? ''));
    $filterPilot = trim((string)($_GET['f_pilot'] ?? ''));
    $filterText = trim((string)($_GET['f_text'] ?? ''));
    $filterDateFrom = trim((string)($_GET['f_from'] ?? ''));
    $filterDateTo = trim((string)($_GET['f_to'] ?? ''));
    $hasFilters = ($filterTemplate !== '' || $filterPilot !== '' || $filterText !== '' || $filterDateFrom !== '' || $filterDateTo !== '');

    pageHeaderLocal("Examen");

    if(isset($_GET['saved']))
        echo "<div class='rkrk-card success'>✓ Examen gespeichert.</div>";

    if(isset($_GET['deleted']))
        echo "<div class='rkrk-card danger'>✓ Eintrag gelöscht.</div>";

    echo "<div class='rkrk-card checklist-header'>
        <div class='checklist-title'>Neues Examen</div>
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

    $templateChoices = array();
    foreach (glob("$TEMPLATE_FOLDER/*.json") as $tplFile){
        $base = basename($tplFile);
        $tpl = loadTemplate($base);
        $templateChoices[$base] = $tpl["title"] ?? $base;
    }
    asort($templateChoices, SORT_NATURAL | SORT_FLAG_CASE);

    echo "<div class='rkrk-card'>
            <h3>".($showAll ? "Alle gespeicherten Examen" : "Letzte 12 gespeicherte Examen")."</h3>";
    echo "<form id='exam-list-filter-form' method='get' style='display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:14px; align-items:end;'>
            <input type='hidden' name='mode' value='".safe($mode)."'>
            <label style='display:flex; flex-direction:column; gap:4px;'>
                <span style='font-size:12px; color:#5f6b66;'>Template</span>
                <select name='f_template' class='text-input'>
                    <option value=''>Alle</option>";
    foreach ($templateChoices as $base => $title) {
        $sel = ($filterTemplate === $base) ? "selected" : "";
        echo "<option value='".safe($base)."' $sel>".safe($title)."</option>";
    }
    echo "      </select>
            </label>
            <label style='display:flex; flex-direction:column; gap:4px;'>
                <span style='font-size:12px; color:#5f6b66;'>Pilot enthält</span>
                <input type='text' name='f_pilot' class='text-input' value='".safe($filterPilot)."' placeholder='z.B. Müller'>
            </label>
            <label style='display:flex; flex-direction:column; gap:4px;'>
                <span style='font-size:12px; color:#5f6b66;'>Suche in ID/Name/Info</span>
                <input type='text' name='f_text' class='text-input' value='".safe($filterText)."' placeholder='Suchtext'>
            </label>
            <label style='display:flex; flex-direction:column; gap:4px;'>
                <span style='font-size:12px; color:#5f6b66;'>Von Datum</span>
                <input type='date' name='f_from' class='text-input' value='".safe($filterDateFrom)."'>
            </label>
            <label style='display:flex; flex-direction:column; gap:4px;'>
                <span style='font-size:12px; color:#5f6b66;'>Bis Datum</span>
                <input type='date' name='f_to' class='text-input' value='".safe($filterDateTo)."'>
            </label>
            <div style='display:flex; gap:8px; flex-wrap:wrap;'>
                <a class='btn' href='?mode=".safe($mode)."'>Zurücksetzen</a>
            </div>
          </form>";
    echo "<script>
            (function(){
                const form = document.getElementById('exam-list-filter-form');
                if (!form) return;
                let submitTimer = null;
                let lastSerialized = '';

                const serialize = () => {
                    const fd = new FormData(form);
                    return new URLSearchParams(fd).toString();
                };

                const submitIfChanged = (delayMs) => {
                    const run = () => {
                        const current = serialize();
                        if (current === lastSerialized) return;
                        lastSerialized = current;
                        form.submit();
                    };
                    if (delayMs > 0) {
                        if (submitTimer) clearTimeout(submitTimer);
                        submitTimer = setTimeout(run, delayMs);
                    } else {
                        run();
                    }
                };

                lastSerialized = serialize();
                form.querySelectorAll('select, input[type=\"date\"]').forEach(el => {
                    el.addEventListener('change', () => submitIfChanged(0));
                });
                form.querySelectorAll('input[type=\"text\"]').forEach(el => {
                    el.addEventListener('input', () => submitIfChanged(350));
                    el.addEventListener('change', () => submitIfChanged(0));
                });
            })();
          </script>";

    $jsonFiles = glob("$JSON_FOLDER/*.json");
    usort($jsonFiles, fn($a,$b) => filemtime($b) - filemtime($a));

    if (!$showAll && !$hasFilters) {
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

    $rowCount = 0;
    $containsCi = function($haystack, $needle) {
        if ($needle === '') return true;
        return mb_stripos((string)$haystack, (string)$needle, 0, 'UTF-8') !== false;
    };
    $fromTs = ($filterDateFrom !== '') ? strtotime($filterDateFrom . ' 00:00:00') : null;
    $toTs = ($filterDateTo !== '') ? strtotime($filterDateTo . ' 23:59:59') : null;

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
        
		// Flugzeit bevorzugen, dann Datum & Zeit, dann Prüfungsdatum (auch ausserhalb "Allgemein")
		$flugzeitRaw = $fields["Allgemein"]["Flugzeit"] ?? "";
		$datumZeitRaw = $fields["Allgemein"]["Datum & Zeit"] ?? "";
        $pruefungsdatumRaw = $fields["Allgemein"]["Prüfungsdatum"] ?? ($fields["Allgemein"]["Pruefungsdatum"] ?? "");
        if (trim((string)$pruefungsdatumRaw) === "" && is_array($fields)) {
            foreach ($fields as $secFields) {
                if (!is_array($secFields)) continue;
                if (isset($secFields["Prüfungsdatum"]) && trim((string)$secFields["Prüfungsdatum"]) !== "") {
                    $pruefungsdatumRaw = $secFields["Prüfungsdatum"];
                    break;
                }
                if (isset($secFields["Pruefungsdatum"]) && trim((string)$secFields["Pruefungsdatum"]) !== "") {
                    $pruefungsdatumRaw = $secFields["Pruefungsdatum"];
                    break;
                }
            }
        }
        $dtSourceRaw = "";

		// Formatierung
		if (trim((string)$flugzeitRaw) !== "") {
            $dtSourceRaw = (string)$flugzeitRaw;
			$dt = formatChecklistDatetime($flugzeitRaw);
		} elseif (trim((string)$datumZeitRaw) !== "") {
            $dtSourceRaw = (string)$datumZeitRaw;
			$dt = formatChecklistDatetime($datumZeitRaw);
		} else {
            $dtSourceRaw = (string)$pruefungsdatumRaw;
            $dtObj = DateTime::createFromFormat('Y-m-d', trim((string)$pruefungsdatumRaw));
            if ($dtObj instanceof DateTime) {
                $dt = $dtObj->format('d.m.Y');
            } else {
                $dt = trim((string)$pruefungsdatumRaw);
            }
		}
        if (preg_match('/\s00:00$/', (string)$dt) === 1) {
            $dt = preg_replace('/\s00:00$/', '', (string)$dt);
        }
        $dtFilterRaw = $dtSourceRaw;
        $dtFilterTs = strtotime(str_replace('T', ' ', (string)$dtFilterRaw));

		$pilotRaw = $fields["Allgemein"]["Pilot"] ?? "";
        $pilot = is_array($pilotRaw) ? implode(", ", $pilotRaw) : (string)$pilotRaw;

        /* -------------------------
           Name (N‑Flag)
        ------------------------- */
        $nameParts = [];

        foreach ($tpl["sections"] as $secName => $secFields){
            foreach ($secFields as $label => $meta){

                if (($meta["inname"] ?? "0") !== "1") continue;

                $val = $fields[$secName][$label] ?? "";
                if (is_array($val)) $val = implode("; ", $val);
                $val = trim((string)$val);
                if ($val === "") continue;
                $nameParts[] = $val;
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
                $val = trim((string)$val);
                if ($val === "") continue;
                $infoParts[] = $label . ": " . $val;
            }
        }

        $info = implode(" | ", $infoParts);

        if ($filterTemplate !== '' && $tplName !== $filterTemplate) continue;
        if (!$containsCi($pilot, $filterPilot)) continue;
        if (!$containsCi($id . " " . $name . " " . $info, $filterText)) continue;
        if ($fromTs !== null && $dtFilterTs !== false && $dtFilterTs < $fromTs) continue;
        if ($toTs !== null && $dtFilterTs !== false && $dtFilterTs > $toTs) continue;

        $rowCount++;

        echo "<tr>
                <td>".safe($title)."</td>
                <td>".safe($dt)."</td>
                <td>".safe($name)."</td>
                <td>".safe($pilot)."</td>
                <td>".safe($info)."</td>
                <td>
                    <a href='?mode=form&file=$tplName&edit=$id'>Bearbeiten</a> |
                    <a href='?mode=form&file=$tplName&edit=$id&readonly=1'>Lesen</a> |
					<a href='?mode=print&id=$id' target='_blank'>Drucken</a> |
                    <a href='?mode=deletejson&id=$id'
                       onclick='return confirm(\"Eintrag wirklich löschen?\")'>
                       Löschen
                    </a>
                </td>
              </tr>";
    }

    if ($rowCount === 0) {
        echo "<tr><td colspan='6' style='text-align:center; color:#6b7280; padding:12px;'>Keine Examen mit diesen Filtern gefunden.</td></tr>";
    }

    echo "</table></div>";

    $activeFilters = array();
    if ($filterTemplate !== '') $activeFilters['f_template'] = $filterTemplate;
    if ($filterPilot !== '') $activeFilters['f_pilot'] = $filterPilot;
    if ($filterText !== '') $activeFilters['f_text'] = $filterText;
    if ($filterDateFrom !== '') $activeFilters['f_from'] = $filterDateFrom;
    if ($filterDateTo !== '') $activeFilters['f_to'] = $filterDateTo;
    $listUrl = '?' . http_build_query(array_merge(array('mode' => 'list'), $activeFilters));
    $listAllUrl = '?' . http_build_query(array_merge(array('mode' => 'listall'), $activeFilters));

    echo "<div style='text-align:center; margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;'>

    <a href='".safe($listUrl)."' 
       class='btn' 
       ".($mode=='list' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Neueste 12 Examen
    </a>

    <a href='".safe($listAllUrl)."' 
       class='btn' 
       ".($mode=='listall' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Alle Examen
    </a>";
	
	//if (GetAccess("TestOnly")) {
	
		if (GetAccessByFunction ('ExamTemplate')) {		
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

			<a href='rkrk_exam_admin.php' class='btn'>
				🛠️ Admin
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
function renderField($secName, $label, $type, $value, $readonly, $renderMeta = array()) {

    $placeholder = "";
    $info = "";
    $ref = "";
    $calc = "";
    $options = "";

    if (is_array($type)) {
        $placeholder = isset($type["placeholder"]) ? $type["placeholder"] : "";
        $info        = isset($type["info"]) ? $type["info"] : "";
        $ref         = isset($type["ref"]) ? rkrk_normalize_ref_id($type["ref"]) : "";
        $calc        = isset($type["calc"]) ? trim((string)$type["calc"]) : "";
        $options     = isset($type["options"]) ? trim((string)$type["options"]) : "";
        $typeString  = isset($type["type"]) ? $type["type"] : "text";
    } else {
        $typeString = $type;
    }
    if (($typeString === "summe" || $typeString === "countfails" || $typeString === "countcritical") && $calc === "" && $options !== "") {
        $calc = $options;
    }

    // Bemerkungsfelder immer mehrzeilig darstellen
    if (preg_match('/\bBemerkung\b/ui', (string)$label) === 1) {
        $typeString = "textarea";
    }

    $value = applyPresetValue($label, $typeString, $value);
    $key   = rkrk_field_key_from_label($label);

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

    $extraAttr = "";
    if (isset($renderMeta["remark_parent_key"]) && trim((string)$renderMeta["remark_parent_key"]) !== "") {
        $remarkParentKey = htmlspecialchars(trim((string)$renderMeta["remark_parent_key"]), ENT_QUOTES);
        $depClass .= " remark-dependent ";
        $extraAttr .= " data-remark-dependent='1' data-remark-parent-key='$remarkParentKey' data-ro-dependent='1' data-ro-parent-key='$remarkParentKey' ";
    }

    $disabled = $readonly ? "disabled" : "";
    $isPrintType = ($typeString === "print");
    echo "<div class='field $depClass' $depAttr $extraAttr>";
    echo "<label class='field-label'>".safe($label);

    if (!empty($info) && !$isPrintType) {
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
    if ($isPrintType) {
        $printText = trim((string)$info);
        if ($printText === "") {
            $printText = trim((string)$placeholder);
        }
        if ($printText === "") {
            $printText = trim((string)$value);
        }
        if ($printText !== "") {
            $printHtml = (strip_tags($printText) !== $printText)
                ? $printText
                : nl2br(safe($printText));
            echo "<details class='print-text-block' open style='margin-top:6px;'>
                    <summary style='cursor:pointer; font-weight:600;'>Text anzeigen</summary>
                    <div class='print-text-content' style='margin-top:8px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; background:#fafafa; white-space:normal;'>".$printHtml."</div>
                  </details>";
        }
        echo "</div>";
        return;
    }

    $refAttr = ($ref !== "") ? " data-ref-id=\"" . htmlspecialchars($ref, ENT_QUOTES) . "\" " : "";

    if (!empty($placeholder) &&
        (strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0)) {

        echo "<small class='field-placeholder'>".safe($placeholder)."</small>";
    }

    /* Helper: builds inline style so selected background == border color */
 
	$buildColorStyle = function($color, $isSelected) {
        if (!$color) return "";
        $base = strtolower(trim((string)$color));
        $fill = $base;
        $selectedText = "#1f2a1f";

        if ($base === '#d06a6a') {
            $selectedText = "#ffffff";
            $fill = $base;
        } else {
            if (preg_match('/^#([0-9a-f]{6})$/i', $base, $m)) {
                $hex = $m[1];
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));

                // Leicht getoente Flaeche wie im Referenzbild
                $mix = 0.72; // Anteil Weiss
                $rf = (int)round($r * (1 - $mix) + 255 * $mix);
                $gf = (int)round($g * (1 - $mix) + 255 * $mix);
                $bf = (int)round($b * (1 - $mix) + 255 * $mix);
                $fill = sprintf('#%02x%02x%02x', $rf, $gf, $bf);
            }
        }

        $parts = [];
        $parts[] = "border-color:$color";
        $parts[] = "--option-color:$color";
        $parts[] = "--option-fill:$fill";
        $parts[] = "--option-selected-text:$selectedText";
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
                           $refAttr
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
                           $refAttr
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
                           $refAttr
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
                 $refAttr
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
                     $refAttr
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
                     $refAttr
                     value='".safe($val)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* NUMBER */
    elseif ($typeString === "number") {
        echo "<input type='number'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* REFERENZ (numerischer Eingabewert) */
    elseif ($typeString === "referenz") {
        echo "<input type='number'
                     step='any'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled>";
    }

    /* SUMME (berechnetes Feld) */
    elseif ($typeString === "summe") {
        $sumValueRaw = (string)$value;
        $sumValue = safe($sumValueRaw);
        $sumCalc = htmlspecialchars($calc, ENT_QUOTES);
        echo "<input type='hidden'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     data-sum-field='1'
                     data-sum-calc=\"$sumCalc\"
                     value='$sumValue'>";
        echo "<div class='sum-value-display' data-sum-display-for=\"$key\" style='padding:8px 10px; border:1px solid #d6dfcc; border-radius:8px; background:#f8fbf6; font-weight:700;'>$sumValue</div>";
        if ($calc !== "") {
            echo "<small class='field-placeholder'>Berechnung: ".safe($calc)." &nbsp; Max erreichbar: <span class='sum-max-value' data-sum-max-for=\"$key\">–</span></small>";
        }
    }
    /* COUNtFAILS (zaehlt (r)-Bewertungen) */
    elseif ($typeString === "countfails") {
        $sumValue = safe($value);
        $sumCalc = htmlspecialchars($calc, ENT_QUOTES);
        $isAutoCountFails = trim((string)$calc) !== "";
        echo "<input type='number'
                     step='1'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     data-countfails-field='1'
                     data-countfails-auto='" . ($isAutoCountFails ? "1" : "0") . "'
                     data-countfails-calc=\"$sumCalc\"
                     value='$sumValue'
                     placeholder='".safe($placeholder)."'
                     " . ($isAutoCountFails ? "readonly" : "") . "
                     $disabled>";
        if ($calc !== "") {
            echo "<small class='field-placeholder'>Berechnung: ".safe($calc)." &nbsp; Max erreichbar: <span class='count-max-value' data-count-max-for=\"$key\">–</span></small>";
        } else {
            echo "<small class='field-placeholder'>Zählt alle nicht erfüllten Punkte (r).</small>";
        }
    }
    /* COUNTCRITICAL (zaehlt (r)/(o)-Bewertungen) */
    elseif ($typeString === "countcritical") {
        $sumValue = safe($value);
        $sumCalc = htmlspecialchars($calc, ENT_QUOTES);
        $isAutoCountCritical = trim((string)$calc) !== "";
        echo "<input type='number'
                     step='1'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     data-countcritical-field='1'
                     data-countcritical-auto='" . ($isAutoCountCritical ? "1" : "0") . "'
                     data-countcritical-calc=\"$sumCalc\"
                     value='$sumValue'
                     placeholder='".safe($placeholder)."'
                     " . ($isAutoCountCritical ? "readonly" : "") . "
                     $disabled>";
        if ($calc !== "") {
            echo "<small class='field-placeholder'>Berechnung: ".safe($calc)." &nbsp; Max erreichbar: <span class='countcritical-max-value' data-countcritical-max-for=\"$key\">–</span></small>";
        } else {
            echo "<small class='field-placeholder'>Zählt alle kritischen Punkte (r/o).</small>";
        }
    }

	/* ------------------------- FLUGZEIT ------------------------- */
	elseif ($typeString === "flugzeit") {
		// identisch wie datetime-local, aber mit eigener Semantik
		global $isEditMode;
		$val = (!$isEditMode && ($value === "" || $value === null)) ? date("Y-m-d\\TH:i") : $value;
		echo "<input id='field_$key' data-field-type='flugzeit' data-field-key='$key' $refAttr type='datetime-local' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' $disabled />";
	}

	/* ------------------------- FLUGORT ------------------------- */
	elseif ($typeString === "flugort") {
		// normaler Textinput
		$val = (string)$value;
		echo "<input id='field_$key' data-field-type='flugort' data-field-key='$key' $refAttr type='text' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' placeholder='".htmlspecialchars($placeholder, ENT_QUOTES)."' $disabled />";
	}

    /* STANDARD TEXT */
    else {
        echo "<input type='text'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
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
    $title    = $tpl["title"] ?? "Examen";
    $sections = $tpl["sections"] ?? [];

    $saved = [];
    $savedRaw = [];
    $extraFields = [];
    $createdAt = null;
    $modifiedAt = null;
    $editIdForWeather = "";

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
	$hasFlightTimeField = false;
	$hasFlightPlaceField = false;
	foreach ($sections as $secName => $fieldsMeta){
	  foreach ($fieldsMeta as $label => $meta){

		$t = is_array($meta) ? ($meta['type'] ?? 'text') : (string)$meta;

		// Flugzeit/Flugort strikt ueber Feldtyp erkennen

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

	// Fallback: Falls Flugzeit/Flugort als Extra-Felder existieren (nicht im Template)
	if (($flugzeit === '' || $flugort === '') && !empty($savedRaw) && is_array($savedRaw)) {
	  foreach ($savedRaw as $secName => $fields) {
		if (!is_array($fields)) continue;
		foreach ($fields as $label => $val) {
		  $k = strtolower(trim((string)$label));
		  if ($flugzeit === '' && $k === 'flugzeit' && trim((string)$val) !== '') $flugzeit = (string)$val;
		  if ($flugort === '' && $k === 'flugort' && trim((string)$val) !== '') $flugort = (string)$val;
		}
	  }
	}

    pageHeaderLocal($title);

	echo "<style>
	.remark-with-meta{
	  display:block;
	  margin-bottom:10px;
	}
	.remark-with-meta .field{
	  display:block;
	  width:100%;
	}
	.notfulfilled-box{
	  border:1px solid #f0c2c2;
	  border-radius:10px;
	  padding:10px;
	  background:#fff7f7;
	  margin-bottom:10px;
	}
	.notfulfilled-title{
	  font-weight:700;
	  margin-bottom:8px;
	  color:#7f1d1d;
	}
	.notfulfilled-list{
	  display:flex;
	  flex-wrap:wrap;
	  gap:8px;
	}
	.notfulfilled-btn{
	  display:inline-flex;
	  align-items:center;
	  border:2px solid #cfd8cc;
	  border-radius:10px;
	  background:#fff;
	  color:#48673A;
	  padding:8px 10px;
	  cursor:pointer;
	  font-size:14px;
	  line-height:1.2;
	  transition:all .15s ease;
	}
	.notfulfilled-btn.selected{
	  background:#b30000;
	  border-color:#b30000;
	  color:#fff;
	}
	.notfulfilled-btn:disabled{
	  opacity:.6;
	  cursor:not-allowed;
	}
	</style>";

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

		  function findFlightInputs(){
			const timeInput =
			  document.querySelector("[data-field-type='flugzeit']") ||
			  document.querySelector("input[data-field-type='flugzeit']");
			const placeInput =
			  document.querySelector("[data-field-type='flugort']") ||
			  document.querySelector("input[data-field-type='flugort']");
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
            <input type='hidden' name='file' value='$file'>";
    if ($edit){
        echo "<input type='hidden' name='edit' value='$edit'>";
    }

	foreach ($sections as $secName => $fields){

		$secId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $secName));

		echo "<div class='section-row'>
				<div class='section-title-box' data-section-id='$secId'>".safe($secName)."</div>
				<div class='section-fields' data-section-id='$secId'>";

		// Felder rendern
		foreach ($fields as $label => $type){
			$value = $saved[$secName][$label] ?? ($_GET[$label] ?? "");
			$fieldRenderMeta = array();
			$pointMeta = null;
			$isRemarkField = (preg_match('/\bBemerkung\b/ui', (string)$label) === 1);

			if ($isRemarkField) {
				$pointCode = rkrk_extract_point_code($label);
				if ($pointCode !== "") {
					$pointMeta = rkrk_find_point_evaluation_meta($fields, $pointCode);
					if (is_array($pointMeta) && isset($pointMeta["score_label"])) {
						$fieldRenderMeta["remark_parent_key"] = rkrk_field_key_from_label($pointMeta["score_label"]);
					}
				}
			}

			if ($isRemarkField && is_array($pointMeta)) {
				$process = isset($pointMeta["process"]) ? trim((string)$pointMeta["process"]) : "";
				$checks = isset($pointMeta["checks"]) ? trim((string)$pointMeta["checks"]) : "";
				$checkItems = rkrk_split_checks_list($checks);
				$pointCode = rkrk_extract_point_code($label);
				$notFulfilledLabel = ($pointCode !== "") ? ($pointCode . " Nicht erfüllte Punkte") : ("Nicht erfüllte Punkte");
				$notFulfilledSaved = isset($savedRaw[$secName][$notFulfilledLabel]) ? $savedRaw[$secName][$notFulfilledLabel] : "";
				$notFulfilledSelected = rkrk_decode_not_fulfilled_points($notFulfilledSaved);
				$notFulfilledJson = htmlspecialchars(json_encode($notFulfilledSelected, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
				$remarkParentKey = isset($fieldRenderMeta["remark_parent_key"]) ? $fieldRenderMeta["remark_parent_key"] : "";
				$remarkParentKeySafe = htmlspecialchars((string)$remarkParentKey, ENT_QUOTES);
				$boxHiddenClass = (!empty($notFulfilledSelected) ? " visible" : "");

				echo "<div class='remark-with-meta'>";
				if (!empty($checkItems)) {
					$btnDisabled = $readonly ? " disabled" : "";
					echo "<div class='notfulfilled-box remark-dependent$boxHiddenClass' data-ro-dependent='1' data-ro-parent-key='$remarkParentKeySafe'>";
					echo "<div class='notfulfilled-title'>Nicht erfüllte Punkte:</div>";
					echo "<div class='notfulfilled-list'>";
					foreach ($checkItems as $item) {
						$itemDisplay = preg_replace('/\s*\(([rog])\)\s*$/iu', '', (string)$item);
						$itemDisplay = trim((string)$itemDisplay);
						if ($itemDisplay === "") {
							$itemDisplay = (string)$item;
						}
						$itemSafe = safe($itemDisplay);
						$itemAttr = htmlspecialchars($item, ENT_QUOTES);
						$isSelected = in_array($item, $notFulfilledSelected, true);
						$selClass = $isSelected ? " selected" : "";
						echo "<button type='button' class='notfulfilled-btn$selClass' data-value='$itemAttr' aria-pressed='".($isSelected ? "true" : "false")."'$btnDisabled>$itemSafe</button>";
					}
					echo "</div>";
					echo "<input type='hidden' class='notfulfilled-hidden' name='fields[$secName][$notFulfilledLabel]' value='$notFulfilledJson'>";
					echo "</div>";
				}
				renderField($secName, $label, $type, $value, $readonly, $fieldRenderMeta);
				echo "</div>";

				if ($process !== "" || $checks !== "") {
					echo "<details class='point-meta-block' style='margin:8px 0 14px 0;'>
							<summary style='cursor:pointer; font-weight:600;'>Ablauf & Kontrollpunkte</summary>
							<div class='point-meta-content' style='margin-top:8px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; background:#fafafa;'>
						";
					if ($process !== "") {
						echo "<div><strong>Ablauf:</strong><br>".nl2br(safe($process))."</div>";
					}
					if ($checks !== "") {
						echo "<div style='margin-top:8px;'><strong>Kontrollpunkte:</strong><br>".nl2br(safe($checks))."</div>";
					}
					echo "	</div>
						</details>";
				}
			} else {
				renderField($secName, $label, $type, $value, $readonly, $fieldRenderMeta);
			}
		}

		// Wetter-Hinweis anzeigen
		if ($secName === "Wetter" && isset($saved["weather_saved_at"])) {
			echo "<div class='system-info-box' style='margin-top:10px;'>
					🌤️ Wetterdaten gespeichert am: " . safe($saved["weather_saved_at"]) . "
				  </div>";
		}

		// Extra-Felder
		if (isset($extraFields[$secName])) {
			foreach ($extraFields[$secName] as $label => $value) {
				if (preg_match('/\bNicht erfüllte Punkte$/u', (string)$label)) {
					continue;
				}
				renderField($secName, $label, "text", $value, $readonly);
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
        echo "<button class='btn btn-primary'>✓ Speichern</button>";
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
                <a href='?mode=listall' class='btn'>📄 Zurück zu den Examen</a>
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
					<option value='print'>Print (Klapptext)</option>
					<option value='referenz'>Referenz</option>
					<option value='summe'>Summe</option>
					<option value='countfails'>CountFails</option>
					<option value='countcritical'>CountCritical</option>
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
        rkrk_redirect("?mode=list");
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

			if ($weatherTime && ($now - $weatherTime) <= 24 * 3600) {

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

    rkrk_redirect("?mode=list&saved=1");
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

    rkrk_redirect("?mode=list&deleted=1");
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
 LINK BUILDER (Examen-URL Generator)
----------------------------------------------------------- */
function showLinkBuilder(){
    global $TEMPLATE_FOLDER;

    pageHeaderLocal("Examen‑URL Generator");

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
  <h3>🔗 Examen‑URL erzeugen</h3>
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