<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
if (ob_get_level() === 0) {
	ob_start();
}
$__rkrkAssessModePeek = isset($_GET["mode"]) ? trim((string)$_GET["mode"]) : "";
if (in_array($__rkrkAssessModePeek, array("einsatzpruefunglink_cs", "einsatzpruefunglink_prefs"), true)
	&& !defined("RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP")) {
	define("RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP", true);
}
include_once('rkrk_assess_common.php');
include_once(__DIR__ . '/rkrk_assess_perspick_default.inc.php');
include_once(__DIR__ . '/rkrk_assess_filename_ymd.inc.php');
include_once(__DIR__ . '/rkrk_assess_checklist_progress.inc.php');

$modeEarly = $_GET["mode"] ?? "list";
// Regen/PDF-Bootstrap: kein Seiten-Chrome (sonst stört Output die PDF-Erzeugung)
$rkrkAssessSilentModes = array("save", "deletejson", "templatefields", "saveweatherclient", "weather", "print");
$rkrkAssessSilent = !empty($GLOBALS["rkrk_assess_regen_assess_bootstrap"])
	|| in_array((string) $modeEarly, $rkrkAssessSilentModes, true);

if (!$rkrkAssessSilent && $modeEarly !== "paperform"
	&& $modeEarly !== "einsatzpruefunglink_cs"
	&& $modeEarly !== "einsatzpruefunglink_prefs") {
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
}

if (!$rkrkAssessSilent && !$onRKRS && $modeEarly !== "paperform") {
	//check impropriate IP addresses accessing page
	CheckIPandExit($folderoffset);

	//Write to LOGfile
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","",$folderoffset.$folderoffset);

	GrantAccess($folderoffset.$folderoffset);
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
	$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast' && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
		if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
			rkrk_assess_emit_admin_url_managers_menu_strip();
		}
	}
}

$pagetitle = "RKRS+ Assessments";
if (!$rkrkAssessSilent && $modeEarly !== "paperform"
	&& $modeEarly !== "einsatzpruefunglink_cs"
	&& $modeEarly !== "einsatzpruefunglink_prefs") {
	headerpage("$pagetitle", false, $folderoffset);
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
if ($mode === 'einsatzpruefunglink_cs') {
	einsatzpruefungLinkCsApi();
}
if ($mode === 'einsatzpruefunglink_prefs') {
	einsatzpruefungLinkPrefsApi();
}

/* ERST JETZT HTML */
/*
headerpage("Digitales Assessment", false);
?>
<body>
<?
*/

/* ---------------- FOLDER SETUP ---------------- */
//$rkrkDataRoot = isset($rkrkAssessFsDataRoot) ? $rkrkAssessFsDataRoot : $RKRKRelRKRKDataLocation;
//$rkrkAssessTemplates  = $rkrkDataRoot . "data/assess/templates";
//$rkrkAssessResults      = $rkrkDataRoot . "data/assess/results";
// Logo/images in HTML: URL under $WebLocation, not filesystem ($rkrkDataRoot is e.g. /volume1/.../rkrk/).
//$examIconLoc = rtrim((string)$WebLocation, '/') . '/rkrk/data/assess/images/';
if (!function_exists('rkrk_assess_normalize_path')) {
    function rkrk_assess_normalize_path($path) {
        if (function_exists('rkrk_normalize_rkropen_path')) {
            return rkrk_normalize_rkropen_path($path);
        }
        return str_replace('\\', '/', (string)$path);
    }
}
$_rkrkSn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$_rkrkDn = trim(dirname($_rkrkSn), '/');
$_rkrkSeg = ($_rkrkDn === '') ? array() : explode('/', $_rkrkDn);
$_rkrkRi = false;
foreach ($_rkrkSeg as $_i => $_p) {
    if (strtolower((string)$_p) === 'rkropen') {
        $_rkrkRi = (int)$_i;
        break;
    }
}
if ($_rkrkRi !== false) {
    $examIconLoc = rkrk_assess_normalize_path('/' . implode('/', array_slice($_rkrkSeg, 0, $_rkrkRi + 1)) . '/rkrk/data/assess/images/');
} else {
    $_rkrkN = count($_rkrkSeg);
    if ($_rkrkN >= 2 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'assess' && strtolower((string)$_rkrkSeg[$_rkrkN - 2]) === 'test') {
        $examIconLoc = rkrk_assess_normalize_path('../../rkrk/data/assess/images/');
    } elseif ($_rkrkN >= 1 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'assess') {
        $examIconLoc = rkrk_assess_normalize_path('../rkrk/data/assess/images/');
    } elseif ($_rkrkN >= 2 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'public' && strtolower((string)$_rkrkSeg[$_rkrkN - 2]) === 'assess'
        && is_dir(__DIR__ . '/../../data/assess/images')) {
        // Portal (z. B. /rkrsplusassessment/assess/public/): Daten liegen unter ../../data/assess/
        $examIconLoc = rkrk_assess_normalize_path('../../data/assess/images/');
    } elseif (preg_match('#[/\\\\]assess$#i', str_replace('\\', '/', __DIR__))) {
        $examIconLoc = rkrk_assess_normalize_path('../../rkrk/data/assess/images/');
    } else {
        $examIconLoc = rkrk_assess_normalize_path('../rkrk/data/assess/images/');
    }
}
unset($_rkrkSn, $_rkrkDn, $_rkrkSeg, $_rkrkRi, $_rkrkN, $_i, $_p);

if (!is_dir($rkrkAssessTemplates)) mkdir($rkrkAssessTemplates, 0777, true);
if (!is_dir($rkrkAssessResults)) {
	mkdir($rkrkAssessResults, 0777, true);
}

require_once __DIR__ . "/rkrk_checklist_toolbar_inc.php";
require_once __DIR__ . "/rkrk_template_csv_inc.php";

if ($mode === "paperform") {
	require_once __DIR__ . "/rkrk_assess_paper_form_inc.php";
	$fPf = basename((string)($_GET["file"] ?? ""));
	$backPf = function_exists("rkrk_assess_paperform_resolve_back_url")
		? rkrk_assess_paperform_resolve_back_url($fPf)
		: ("?mode=edittemplate&file=" . rawurlencode($fPf));
	$optsPf = array(
		"back_url" => $backPf,
		"logo_src" => (string)$examIconLoc . (string)$logopic,
	);
	if (!rkrk_assess_paper_form_from_template_file($rkrkAssessTemplates, $fPf, $optsPf)) {
		header("Content-Type: text/html; charset=UTF-8");
		echo "<!DOCTYPE html><html lang=\"de\"><body><p>Template nicht gefunden.</p></body></html>";
		exit;
	}
}

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

function rkrk_normalize_field_label_key($label){
    $label = (string)$label;
    // Ignore trailing color marker changes like "(g)/(o)/(r)/(n)" when matching saved fields.
    $label = preg_replace('/\s*\(([rogn])\)\s*$/iu', '', $label);
    return normalizeKey($label);
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
    global $rkrkAssessTemplates;
    $path = "$rkrkAssessTemplates/$file";
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
            foreach (["placeholder", "info", "process", "checks", "options", "ref", "calc", "threshold"] as $opt) {
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

function listAssessmentTemplateFiles() {
    global $rkrkAssessTemplates;
    $files = glob("$rkrkAssessTemplates/*.json");
    if (!is_array($files)) return [];
    // Keep classic assessment templates separate from live-assessment templates.
    return array_values(array_filter($files, function ($path) {
        $base = strtolower(basename((string)$path));
        return (strpos($base, 'live_') !== 0);
    }));
}

/* ---------------- LOAD CHECKLIST JSON ---------------- */
function loadChecklist($id){
    global $rkrkAssessResults;
    $path = "$rkrkAssessResults/$id.json";
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
    if ($url !== "" && $url[0] === "?") {
        $url = rkrk_assess_append_setting_href($url);
    }

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    /* Nur Query (?mode=…): gegen SCRIPT_NAME auflösen — zuverlässig hinter Reverse-Proxy / Synology. */
    $target = $url;
    if ($url !== "" && $url[0] === "?") {
        $sn = (string)($_SERVER["SCRIPT_NAME"] ?? "");
        if ($sn !== "") {
            $target = $sn . $url;
        }
    }

    if (!headers_sent()) {
        header("Location: " . $target);
        exit;
    }

    $safeUrl = htmlspecialchars($target, ENT_QUOTES);
    $jsUrl = json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>window.location.href=" . $jsUrl . ";</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=" . $safeUrl . "'></noscript>";
    exit;
}

/* ---------------- AUTO DEPENDENT FIELD PARSER ---------------- */
function parseDependentField($label){
    // exam score labels often contain " - " / " – " (e.g. "... – Ist-Pkt")
    // and must NOT be treated as dependency expressions.
    if (preg_match('/\bIst-Pkt\b/ui', (string)$label) === 1) {
        return null;
    }
    // Point labels like "A.2 ...", "B.7 ..." are normal fields, not dependencies.
    if (preg_match('/^[A-Z]\.\d+(?:[*#])?(?:\s|$)/u', trim((string)$label)) === 1) {
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
    // Suffix * / # gehoert zum Punktcode (M.2# != M.2).
    if (preg_match('/^([A-Z]\.\d+)([*#]*)/u', $label, $m)) {
        return $m[1] . $m[2];
    }
    if (preg_match('/^(M\d+)\b/u', $label, $m)) {
        return $m[1];
    }
    return "";
}

function rkrk_label_starts_with_point_code(string $label, string $pointCode): bool
{
    $pointCode = trim($pointCode);
    if ($pointCode === "") {
        return false;
    }
    $label = trim($label);
    $quoted = preg_quote($pointCode, '/');
    // Exact: C.1 Foo, C.1*# Foo, M.2# Spec …
    if (preg_match('/^' . $quoted . '(?:\s|$)/u', $label)) {
        return true;
    }
    // Base code without * / # also matches marked scores: C.1 → C.1*, C.1#, C.1*#
    if (!preg_match('/[*#]/u', $pointCode)
        && preg_match('/^' . $quoted . '[*#]+(?:\s|$)/u', $label)) {
        return true;
    }
    return false;
}

function rkrk_find_point_evaluation_meta($fields, $pointCode){
    if (!is_array($fields) || $pointCode === "") {
        return null;
    }

    $fallback = null;

    foreach ($fields as $candidateLabel => $candidateType) {
        if (!rkrk_label_starts_with_point_code((string)$candidateLabel, $pointCode)) {
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


function rkrk_split_process_steps(string $process): array
{
    $process = trim($process);
    if ($process === "") {
        return [];
    }
    $parts = preg_split('/\R+/u', $process);
    if (!is_array($parts)) {
        return [$process];
    }
    $out = [];
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p !== "") {
            $out[] = $p;
        }
    }
    return $out;
}

function rkrk_split_checks_detailed(string $checks): array
{
    $checks = trim((string)$checks);
    if ($checks === "") {
        return [];
    }
    if (strpos($checks, "\n") !== false) {
        $parts = preg_split('/\R+/u', $checks);
    } elseif (strpos($checks, ";") !== false) {
        $parts = preg_split('/\s*;\s*/u', $checks);
    } else {
        return rkrk_split_checks_list($checks);
    }
    if (!is_array($parts)) {
        return rkrk_split_checks_list($checks);
    }
    $out = [];
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p !== "") {
            $out[] = $p;
        }
    }
    return $out;
}

/** Process-Feld aus Template: HTML (z. B. TIPP/<br>/Listen) oder Fließtext. */
if (!function_exists("rkrk_process_is_html")) {
    function rkrk_process_is_html(string $process): bool
    {
        $process = trim($process);
        return $process !== "" && strip_tags($process) !== $process;
    }
}

/** Ablauf als HTML für Digital- oder Druckansicht. */
if (!function_exists("rkrk_render_process_html")) {
    function rkrk_render_process_html(string $process): string
    {
        $process = trim($process);
        if ($process === "") {
            return "";
        }
        if (rkrk_process_is_html($process)) {
            $html = $process;
            if (function_exists("rkrk_assess_format_template_html_fragment")) {
                $html = rkrk_assess_format_template_html_fragment($html, "screen");
            }
            return $html;
        }
        $steps = rkrk_split_process_steps($process);
        if (count($steps) > 1) {
            $out = "<ol class='live-point-flow__steps'>";
            foreach ($steps as $st) {
                $out .= "<li>" . nl2br(safe($st)) . "</li>";
            }
            return $out . "</ol>";
        }
        return "<div class='live-point-flow__prose'>" . nl2br(safe($process)) . "</div>";
    }
}

function rkrk_assess_emit_point_flow_block(string $process, string $checks, string $pointCode): void
{
    $formatProcessHtml = static function (string $rawProcess): string {
        $rawProcess = trim($rawProcess);
        if ($rawProcess === "") {
            return "";
        }
        if (function_exists("rkrk_assess_format_process_html_for_screen")) {
            return rkrk_assess_format_process_html_for_screen($rawProcess);
        }
        if (function_exists("rkrk_render_process_html")) {
            return rkrk_render_process_html($rawProcess);
        }
        if (strip_tags($rawProcess) !== $rawProcess) {
            $html = $rawProcess;
        } else {
            return nl2br(htmlspecialchars($rawProcess, ENT_QUOTES, "UTF-8"));
        }
        if (function_exists("rkrk_assess_format_template_html_fragment")) {
            return rkrk_assess_format_template_html_fragment($html, "screen");
        }
        if (stripos($html, "<img") === false) {
            return $html;
        }
        $scriptDir = str_replace("\\", "/", dirname((string) ($_SERVER["SCRIPT_NAME"] ?? "")));
        $scriptDir = rtrim($scriptDir, "/");
        if ($scriptDir === "" || $scriptDir === ".") {
            $scriptDir = "/rkropen/rkrs/assess/public";
        }
        $proxy = (preg_match("#/public$#", $scriptDir) ? $scriptDir : $scriptDir . "/public") . "/rkrk_assess_image.php";
        $html = preg_replace_callback(
            "/\\bsrc=(['\"])([^'\"]*)\\1/i",
            static function (array $m) use ($proxy): string {
                $src = trim((string) $m[2]);
                if (preg_match("#(?:^|(?:\\./|\\../)+)images?/(.+)$#i", $src, $dm) === 1) {
                    $file = basename((string) $dm[1]);
                    $src = $proxy . "?f=" . rawurlencode($file) . "&root=diagram";
                }
                return "src=" . $m[1] . htmlspecialchars($src, ENT_QUOTES, "UTF-8") . $m[1];
            },
            $html
        );
        return $html;
    };

    $process = trim($process);
    $checks = trim($checks);
    if ($process === "" && $checks === "") {
        return;
    }
    $pcEsc = htmlspecialchars($pointCode, ENT_QUOTES, "UTF-8");
    echo "<div class='live-point-flow' data-point='$pcEsc'>";
    if ($process !== "") {
        $isHtml = rkrk_process_is_html($process);
        $panelCls = "live-point-flow__panel live-point-flow__panel--process";
        if ($isHtml) {
            $panelCls .= " live-point-flow__panel--rich";
        }
        echo "<details class='$panelCls'>";
        echo "<summary class='live-point-flow__summary'>Ablauf <span class='live-point-code'>" . safe($pointCode) . "</span></summary>";
        echo "<div class='live-point-flow__content'>";
        echo $formatProcessHtml($process);
        echo "</div></details>";
    }
    if ($checks !== "") {
        $items = rkrk_split_checks_detailed($checks);
        echo "<details class='live-point-flow__panel live-point-flow__panel--checks'>";
        echo "<summary class='live-point-flow__summary'>Kontrollpunkte (Checks)</summary>";
        echo "<div class='live-point-flow__content'><ul class='live-point-flow__checks'>";
        foreach ($items as $it) {
            $disp = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$it);
            $disp = trim((string)$disp);
            if ($disp === "") {
                $disp = (string)$it;
            }
            echo "<li><span class='live-check-marker' aria-hidden='true'>✓</span><span class='live-check-text'>" . nl2br(safe($disp)) . "</span></li>";
        }
        echo "</ul></div></details>";
    }
    echo "</div>";
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
    global $IconLoc, $examIconLoc, $logopic, $mode;

    // Fallback falls $mode nicht gesetzt ist
    if (!isset($mode)) {
        $mode = $_GET['mode'] ?? 'list';
    }

    $headerLogoPath = htmlspecialchars((string)$examIconLoc . (string)$logopic, ENT_QUOTES);

    if (!(function_exists('rkrk_assess_skip_legacy_portal_css') && rkrk_assess_skip_legacy_portal_css())) {
        $flexCssHref = function_exists("rkrk_assess_template_asset_href")
            ? rkrk_assess_template_asset_href("flex.css")
            : "../template/flex.css";
        echo "
    <link rel='stylesheet' href='" . htmlspecialchars($flexCssHref, ENT_QUOTES, "UTF-8") . "'>
";
    }
	if (function_exists('rkrk_assess_theme_rkrs_layer_link')) {
		rkrk_assess_theme_rkrs_layer_link();
	}
    echo "
	<div class='rkrk-wrap' data-mode='".safe($mode)."'>
      <div class='cl-header'>
        <h1>".safe($title)."</h1>
        <img src='".$headerLogoPath."' alt='Logo'>
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

// ---------------- BEMERKUNG NUR WENN BEURTEILUNG NICHT (g) — also nur bei (r)/(o) ----------------
function hasRemarkTrigger(value){
    const v = String(value || '').trim();
    return /\(([ro])\)\s*$/i.test(v);
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
        if (!checked || rkrkIsNeutralValue(checked.value)) return 0;
        const v = rkrkToNumber(checked.value);
        return Number.isFinite(v) ? v : 0;
    }

    if (nodes[0].type === 'checkbox') {
        let sum = 0;
        nodes.forEach(n => {
            if (n.checked && !rkrkIsNeutralValue(n.value)) {
                const v = rkrkToNumber(n.value);
                if (Number.isFinite(v)) sum += v;
            }
        });
        return sum;
    }

    const v = rkrkToNumber(nodes[0].value);
    return Number.isFinite(v) ? v : 0;
}

function rkrkIsNeutralValue(v){
    return /\(n\)\s*$/i.test(String(v || '').trim());
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
        const checked = nodes.find(n => n.checked);
        if (checked && rkrkIsNeutralValue(checked.value)) return 0;
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
        if (!checked || rkrkIsNeutralValue(checked.value)) return 0;
        return matcher(checked.value) ? 1 : 0;
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
        const checked = nodes.find(n => n.checked);
        if (checked && rkrkIsNeutralValue(checked.value)) return 0;
        return nodes.some(n => matcher(n.value) && !rkrkIsNeutralValue(n.value)) ? 1 : 0;
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
        const checked = nodes.find(n => n.checked);
        if (checked && rkrkIsNeutralValue(checked.value)) return 0;
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
    rkrkUpdatePassNeed();
}

function rkrkParseThreshold(raw){
    const s = String(raw || '').trim();
    if (!s) return null;
    const hasPct = /%/.test(s);
    const n = rkrkToNumber(s);
    if (!Number.isFinite(n) || n < 0) return null;
    let factor = (hasPct || n > 1) ? (n / 100) : n;
    if (factor > 1) factor = 1;
    return factor;
}

function rkrkUpdatePassNeed(){
    const holders = document.querySelectorAll('[data-pass-need]');
    if (!holders.length) return;
    const source = document.querySelector('[data-sum-field=\"1\"][data-sum-threshold]');
    if (!source) {
        holders.forEach(el => { el.textContent = '–'; });
        return;
    }
    const factor = rkrkParseThreshold(source.dataset.sumThreshold);
    const maxVal = rkrkToNumber(source.dataset.sumMaxValue);
    if (factor == null || !Number.isFinite(maxVal) || maxVal <= 0) {
        holders.forEach(el => { el.textContent = '–'; });
        return;
    }
    const need = Math.ceil(maxVal * factor - 1e-9);
    const pct = String(Math.round(factor * 1000) / 10).replace(/\\.0$/, '');
    const text = need + ' Punkte (' + pct + ' % von ' + maxVal + ')';
    holders.forEach(el => { el.textContent = text; });
}


function rkrkSectionIdFromName(secName) {
    return String(secName || '').toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

function rkrkExpandCalcRefs(formula) {
    const raw = String(formula || '').trim();
    const secMatch = raw.match(/^@section:(.+)$/i);
    if (secMatch) {
        const secId = rkrkSectionIdFromName(secMatch[1].trim());
        const root = document.querySelector('.section-fields[data-section-id=\"' + secId + '\"]');
        if (!root) return [];
        const refs = [];
        root.querySelectorAll('input[type=\"radio\"][data-ref-id]').forEach(el => {
            const name = String(el.name || '');
            if (/bemerkung/i.test(name)) return;
            const rid = String(el.dataset.refId || '').trim();
            if (rid && !refs.includes(rid)) refs.push(rid);
        });
        return refs;
    }
    const parts = raw.split(/[|+,]/).map(s => s.trim()).filter(Boolean);
    const refs = parts.length ? parts : (raw.match(/R?\d+/gi) || []);
    const out = [];
    refs.forEach(r => {
        const digits = String(r).replace(/\D+/g, '');
        if (!digits) return;
        const norm = 'R' + String(parseInt(digits, 10)).padStart(3, '0');
        if (!out.includes(norm)) out.push(norm);
    });
    return out;
}

function rkrkUpdateCountFailsFields(){
    document.querySelectorAll('[data-countfails-field=\"1\"]').forEach(input => {
        const isAuto = String(input.dataset.countfailsAuto || '0') === '1';
        if (!isAuto) return;
        const formula = String(input.dataset.countfailsCalc || '').trim();
        let val = 0;
        let maxVal = 0;
        if (formula !== '') {
            const unique = rkrkExpandCalcRefs(formula);
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
            const unique = rkrkExpandCalcRefs(formula);
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
    global $rkrkAssessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
		
    pageHeaderLocal("Assessment‑Templates");

	if ($isGuest){
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='".safe(rkrk_assess_append_setting_href("?mode=list"))."' class='btn'>⬅ Zurück</a>
              </div>";
        pageFooterLocal();
        return;
    }

    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
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

    foreach (listAssessmentTemplateFiles() as $file){
        $base  = basename($file);
        $benc  = rawurlencode($base);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;
        $jsonDl = htmlspecialchars($base, ENT_QUOTES, "UTF-8");

        echo "<tr>
                <td>".safe($title)."</td>
                <td>".safe($base)."</td>
                <td>
                    <span class='rk-tpl-actions' style='display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center;'>
                    <a class='chk-act' href='?mode=edittemplate&file=$benc' title='Template im Editor bearbeiten' aria-label='Bearbeiten'>" . rkrk_checklist_toolbar_svg_edit() . "</a>
                    <a class='chk-act' href='?mode=templateprint&file=$benc' target='_blank' rel='noopener' title='Druckansicht: Struktur lesen oder ausdrucken' aria-label='Druckansicht'>" . rkrk_checklist_toolbar_svg_print() . "</a>
                    <a class='chk-act' href='?mode=paperform&amp;file=$benc&amp;from=templates' target='_blank' rel='noopener' title='Papierformular zum Ausfüllen' aria-label='Papierformular'>📄</a>
                    <a class='chk-act' href='?mode=templateexport&file=$benc' download='$jsonDl' title='Template als JSON herunterladen (Backup / Bearbeiten extern)' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "</a>
                    <a class='chk-act' href='?mode=copytemplate&file=$benc' onclick='return confirm(\"Template wirklich kopieren?\");' title='Template duplizieren' aria-label='Kopieren'>" . rkrk_checklist_toolbar_svg_copy() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . $benc), ENT_QUOTES, "UTF-8") . "' title='Neues Assessment mit diesem Template' aria-label='Neues Assessment'>" . rkrk_checklist_toolbar_svg_file_plus() . "</a>
                    <a class='chk-act' href='?mode=deletetemplate&file=$benc' onclick='return confirm(\"Template wirklich löschen?\");' title='Template-Datei endgültig löschen' aria-label='Löschen' style='color:#8b2e2e;border-color:#e8c8c8;'>" . rkrk_checklist_toolbar_svg_trash() . "</a>
                    </span>
                </td>
              </tr>";
    }

    echo "</table></div>";

    echo "<div style='text-align:center;margin-top:20px;'>
            <a href='".safe(rkrk_assess_append_setting_href("?mode=list"))."' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE ANLEGEN
----------------------------------------------------------- */
function createTemplate(){
    global $rkrkAssessTemplates, $InputUserInfo;

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
    $path = "$rkrkAssessTemplates/$file";

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
    global $rkrkAssessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates löschen.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $path = "$rkrkAssessTemplates/$file";

    if (file_exists($path)) unlink($path);

    rkrk_redirect("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE KOPIEREN
----------------------------------------------------------- */
function copyTemplate(){
    global $rkrkAssessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates kopieren.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $src  = "$rkrkAssessTemplates/$file";

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
    $dst = "$rkrkAssessTemplates/$newFile";

    $i = 1;
    while (file_exists($dst)) {
        $newFile = $baseName . "_copy_" . $i . ".json";
        $dst = "$rkrkAssessTemplates/$newFile";
        $i++;
    }

    file_put_contents($dst, json_encode($tpl, JSON_PRETTY_PRINT));

    rkrk_redirect("?mode=edittemplate&file=$newFile");
}
/* -----------------------------------------------------------
   TEMPLATE: JSON Export/Import, Druck — rkrk_template_csv_inc.php
----------------------------------------------------------- */
function handleTemplateJsonExport()
{
    global $rkrkAssessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_export_json_download($rkrkAssessTemplates, $f);
    rkrk_redirect("?mode=templates");
}

function handleTemplateJsonImport()
{
    global $rkrkAssessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest || (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST")) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_POST["file"] ?? ""));
    $code = rkrk_template_import_json_upload($rkrkAssessTemplates, $f, $_FILES["jsonfile"] ?? null);
    $fe = rawurlencode($f);
    if ($code === "ok") {
        rkrk_redirect("?mode=edittemplate&file=" . $fe . "&imp=ok");
    }
    if ($code === "invalid") {
        rkrk_redirect("?mode=edittemplate&file=" . $fe . "&imp=invalid");
    }
    rkrk_redirect("?mode=edittemplate&file=" . $fe . "&imp=err");
}

function showTemplatePrintPreview()
{
    global $rkrkAssessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_print_html($rkrkAssessTemplates, $f);
    rkrk_redirect("?mode=templates");
}

function showPaperTemplateForm()
{
    global $rkrkAssessTemplates, $examIconLoc, $logopic;
    require_once __DIR__ . "/rkrk_assess_paper_form_inc.php";
    $f = basename((string)($_GET["file"] ?? ""));
    $back = function_exists("rkrk_assess_paperform_resolve_back_url")
        ? rkrk_assess_paperform_resolve_back_url($f)
        : ("?mode=edittemplate&file=" . rawurlencode($f));
    $opts = array(
        "back_url" => $back,
        "logo_src" => (string)$examIconLoc . (string)$logopic,
    );
    if (!rkrk_assess_paper_form_from_template_file($rkrkAssessTemplates, $f, $opts)) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<!DOCTYPE html><html lang=\"de\"><body><p>Template nicht gefunden.</p></body></html>";
        exit;
    }
}

/* -----------------------------------------------------------
   TEMPLATE EDITOR
----------------------------------------------------------- */
function editTemplate(){
    global $rkrkAssessTemplates, $InputUserInfo;

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

    if (isset($_GET["imp"])) {
        $im = (string)$_GET["imp"];
        if ($im === "ok") {
            echo "<div class='rkrk-card' style='border-color:#86c586;background:#f0faf0;'>JSON-Import: Template-Datei wurde ersetzt.</div>";
        } elseif ($im === "err") {
            echo "<div class='rkrk-card danger'>JSON-Import fehlgeschlagen (Datei fehlt oder Upload-Fehler).</div>";
        } elseif ($im === "invalid") {
            echo "<div class='rkrk-card danger'>JSON-Import: Ungueltige Datei (erwartet wird gueltiges JSON mit einem <code>sections</code>-Objekt).</div>";
        }
    }

    $jsonDlName = htmlspecialchars($file, ENT_QUOTES, "UTF-8");
    $fenc = rawurlencode($file);
    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
    }
    echo "<div class='chk-toolbar no-print' role='region' aria-label='Template JSON und Druck' style='margin-bottom:14px;'>";
    echo "<span class='chk-toolbar-hint' style='flex:0 0 auto;font-weight:600;color:#334155;min-width:unset;'><strong>Backup / Austausch</strong></span>";
    echo "<a class='chk-tbtn' href='?mode=templateprint&file=$fenc' target='_blank' rel='noopener' title='Druckansicht: Struktur lesen oder ausdrucken' aria-label='Druckansicht'>" . rkrk_checklist_toolbar_svg_print() . "<span>Druckansicht</span></a>";
    echo "<a class='chk-act' href='?mode=paperform&amp;file=$fenc&amp;from=templates' target='_blank' rel='noopener' title='Papierformular: leer zum Ausfüllen per Hand' aria-label='Papierformular'>📄</a>";
    echo "<a class='chk-tbtn' href='?mode=templateexport&file=$fenc' download='$jsonDlName' title='Template als JSON herunterladen' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "<span>JSON exportieren</span></a>";
    echo "<form class='chk-import' method='post' action='?mode=templateimport' enctype='multipart/form-data' title='JSON importieren'>";
    echo "<input type='hidden' name='file' value='" . safe($file) . "'>";
    echo "<label style='margin:0;font-size:13px;color:#5f6b66;' title='Vorlage: zuerst exportieren, bearbeiten, dann hier wieder einlesen'>Import JSON:</label>";
    echo "<input type='file' name='jsonfile' accept='.json,application/json' required title='UTF-8 JSON mit sections'>";
    echo "<button class='chk-tbtn' type='submit' title='Ersetzt die gesamte Template-Datei auf dem Server' aria-label='JSON importieren' onclick='return confirm(\"Import ersetzt diese Template-Datei vollstaendig durch die hochgeladene JSON-Datei. Fortfahren?\");'>" . rkrk_checklist_toolbar_svg_import() . "<span>Importieren</span></button>";
    echo "</form>";
    echo "<span class='chk-toolbar-hint'>Struktur wie beim Export; Feld <code>sections</code> ist Pflicht.</span>";
    echo "</div>";

    echo "<div class='rkrk-card'>
        <form method='post' action='?mode=savetemplate'>
		<input type='hidden' id='weather_json' name='weather_json'>
        <input type='hidden' name='file' value='".safe($file)."'>";
?>
<script>
async function loadWeatherForChecklist() {

    // 1) Standort aus dem Assessment holen (falls vorhanden)
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

// Beim Laden des examens automatisch Wetter laden
document.addEventListener("DOMContentLoaded", loadWeatherForChecklist);
</script>
<?php
    echo "<div class='field'>
            <label class='field-label'>Template‑Titel</label>
            <input type='text' name='title' class='text-input' value='".safe($title)."'>
          </div>";

    echo "<h3>Sektionen & Felder</h3>";

    echo "<table class='template-table template-table-editable'>
            <tr>
                <th class='template-drag-col' title='Reihenfolge per Drag &amp; Drop' aria-label='Reihenfolge'>⋮</th>
                <th>Sektion</th>
                <th>Label</th>
                <th>Typ</th>
                <th>Placeholder</th>
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
            $threshold = "";
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
                $threshold = isset($type["threshold"]) ? $type["threshold"] : "";
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
                "threshold"   => $threshold,
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

        echo "<td class='template-drag-col'><span class='template-drag-handle' title='Ziehen zum Verschieben' aria-hidden='true'>⋮⋮</span></td>";

        echo "<td class='template-td-section'>";
        echo "<div class='template-section-wrap'>";
        echo "<div class='template-section-btns'>";
        if ($isFirstRowOfSection) {
            echo "<button type='submit' class='template-sec-move' name='move_sec_up' value='".safe($row["sec"])."' title='Sektion nach oben'>⇑</button>";
            echo "<button type='submit' class='template-sec-move' name='move_sec_down' value='".safe($row["sec"])."' title='Sektion nach unten'>⇓</button>";
        }
        echo "</div>";
        echo "<input type='text' class='template-section-input text-input' name='sec[$i]' value='".safe($row["sec"])."' placeholder='Sektion'>";
        echo "</div></td>";

        echo "<td>
                <input type='text' name='label[$i]' value='".safe($row["label"])."' placeholder='Label' style='width:100%;'>
                <small class='template-ref-line'>Ref: ".safe(rkrk_ref_display($row["ref"]))."</small>
                <input type='hidden' name='ref[$i]' value='".safe($row["ref"])."'>
              </td>";

        echo "<td><select name='type[$i]' class='template-type-select' style='width:100%;'>";
        
		
		$types = [ 
		  "text"=>"Text",
		  "hidden"=>"Hidden (Text, nur JSON)",
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
                <div class='template-action-group' role='group' aria-label='Zeilenaktionen'>
                <button type='submit' class='template-row-move' name='move_up' value='".$i."' title='Nach oben'>▲</button>
                <button type='submit' class='template-row-move' name='move_down' value='".$i."' title='Nach unten'>▼</button>
                <button type='submit' class='template-row-delete' name='delete_row' value='".$i."' title='Zeile löschen' aria-label='Löschen'>🗑</button>
                </div>
              </td>";

        echo "</tr>";
echo "<tr class='template-row-details'>
                <td class='template-drag-col'></td>
                <td></td>
                <td colspan='5'>
                    <div class='template-detail-grid'>
                            <div>
                            <small class='template-field-label'>Optionen / Berechnung (Summe/CountFails)</small>
                            <div class='template-options-calc-cell'>
                                <textarea class='template-options-input' name='options[$i]' rows='2' placeholder='a|b|c' style='width:100%;'>".safe($optVal)."</textarea>
                                <input class='template-calc-input' type='text' name='calc[$i]' value='".safe($row["calc"])."' placeholder='z.B. R001+R002, R010|R011 oder @section:Sektionsname' style='width:100%; margin-top:6px; display:none;'>
                                <input class='template-threshold-input' type='text' name='threshold[$i]' value='".safe(isset($row["threshold"]) ? $row["threshold"] : "")."' placeholder='Schwelle z.B. 80%' style='width:100%; margin-top:6px;' title='Bestehensschwelle, z.B. 80%'>
                            </div>
                        </div>
                        <div>
                            <small class='template-field-label'>Info</small>
                            <textarea name='info[$i]' rows='4' placeholder='Informationen (mehrzeilig)' style='width:100%;'>".safe($row["info"])."</textarea>
                        </div>
                        <div>
                            <small class='template-field-label'>Ablauf (process)</small>
                            <textarea name='process[$i]' rows='2' placeholder='Ablauf (process)' style='width:100%;'>".safe($row["process"])."</textarea>
                        </div>
                        <div>
                            <small class='template-field-label'>Kontrollpunkte (checks)</small>
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
            <th>Label</th>
            <th>Typ</th>
            <th>Placeholder</th>
            <th>N/R</th>
        </tr>

        <tr class='template-row-main'>
            <td><input type='text' name='new_sec' class='text-input' placeholder='Sektion'></td>
            <td>
                <input type='text' name='new_label' placeholder='Label' style='width:100%;'>
                <small class='template-ref-line'>Ref: automatisch (Rxxx)</small>
                <input type='hidden' name='new_ref' value=''>
            </td>
            <td>
                <select name='new_type' class='text-input template-type-select' style='width:100%;'>
                    <option value='text'>Text</option>
                    <option value='hidden'>Hidden (Text, nur JSON)</option>
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
                <div class='template-detail-grid'>
                    <div>
                        <small class='template-field-label'>Optionen / Berechnung (Summe/CountFails)</small>
                        <div class='template-options-calc-cell'>
                            <input class='template-options-input' type='text' name='new_options' placeholder='a|b|c' style='width:100%;'>
                            <input class='template-calc-input' type='text' name='new_calc' placeholder='z.B. R001+R002, R010|R011 oder @section:Sektionsname' style='margin-top:6px; width:100%; display:none;'>
                            <input class='template-threshold-input' type='text' name='new_threshold' placeholder='Schwelle z.B. 80%' style='margin-top:6px; width:100%;' title='Bestehensschwelle, z.B. 80%'>
                        </div>
                    </div>
                    <div>
                        <small class='template-field-label'>Info</small>
                        <textarea name='new_info' rows='4' placeholder='Info (mehrzeilig)' style='width:100%;'></textarea>
                    </div>
                    <div>
                        <small class='template-field-label'>Ablauf (process)</small>
                        <textarea name='new_process' rows='2' placeholder='Ablauf (process)' style='width:100%;'></textarea>
                    </div>
                    <div>
                        <small class='template-field-label'>Kontrollpunkte (checks)</small>
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
            <div><strong>Hidden:</strong> Speichert einen Textwert im JSON wie ein normales Textfeld, zeigt im Formular aber kein sichtbares Feld (z.&nbsp;B. technische IDs). Vorbelegung per URL-Parameter wie bei Text möglich.</div>
            <div><strong>Summe:</strong> Rechnet numerische Werte aus Referenzen, z.B. <code>R010+R011-R012</code>. Optional <em>Schwelle</em> (z.B. <code>80%</code>) am TOTAL und am Bestehens-Check: das Formular zeigt dann die nötigen Punkte.</div>
            <div><strong>CountFails:</strong> Zählt nur Referenz-Felder mit ausgewählter Option <code>(r)</code>. Im Feld <em>Berechnung</em> z.B. <code>R010|R011|R012</code> oder flexibel <code>@section:M: Materialliste — Pflicht (Aufbau und Vorweis)</code> (alle Radio-Felder der Sektion ohne Bemerkung).</div>
            <div><strong>CountCritical:</strong> Zählt Referenz-Felder mit ausgewählter Option <code>(r)</code> oder <code>(o)</code>. Ebenfalls per Referenzliste oder <code>@section:…</code> steuerbar.</div>
            <div><strong>Reihenfolge:</strong> Zeilen per <strong>⋮⋮</strong> ziehen und ablegen; beim Speichern werden Referenzen (R001, R002, …) automatisch neu vergeben.</div>
            <div style='margin-top:8px;'><strong>Optionen für Auswahltypen:</strong></div>
            <div><strong>Radio:</strong> Genau eine Auswahl. Optionen mit <code>|</code> trennen, z.B. <code>Erfüllt (g)|Teilweise (o)|Nicht erfüllt (r)</code>.</div>
            <div><strong>Multi:</strong> Mehrfachauswahl. Optionen ebenfalls mit <code>|</code> trennen, z.B. <code>Kriterium A (g)|Kriterium B (o)|Kriterium C (r)</code>.</div>
            <div><strong>Checkbox:</strong> Einzelnes Ja/Nein-Feld (0/1). Normalerweise keine Optionen nötig; falls Farblogik benötigt wird, Marker direkt im Feldnamen setzen, z.B. <code>Schutzbrille (o)</code>.</div>
            <div><strong>Farbmarker:</strong> <code>(g)</code> = grün, <code>(o)</code> = orange, <code>(r)</code> = rot, <code>(n)</code> = nicht bewertet/neutral (Ist+Max=0, nicht in CountFails/CountCritical).</div>
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


		function rkrkReindexTemplateForm() {
			const form = document.querySelector('form[action*=savetemplate]');
			if (!form) return;
			const mains = Array.from(form.querySelectorAll('tr.template-row-main'));
			mains.forEach((mainRow, newIdx) => {
				const rows = [mainRow];
				if (mainRow.nextElementSibling && mainRow.nextElementSibling.classList.contains('template-row-details')) {
					rows.push(mainRow.nextElementSibling);
				}
				rows.forEach(row => {
					row.querySelectorAll('[name]').forEach(el => {
						if (!el.name) return;
						el.name = el.name.replace(/\[\d+\]/, '[' + newIdx + ']');
					});
				});
				const moveUp = mainRow.querySelector('button[name=move_up]');
				const moveDown = mainRow.querySelector('button[name=move_down]');
				const delBtn = mainRow.querySelector('button[name=delete_row]');
				if (moveUp) moveUp.value = String(newIdx);
				if (moveDown) moveDown.value = String(newIdx);
				if (delBtn) delBtn.value = String(newIdx);
			});
		}

		function rkrkInitTemplateDragDrop() {
			const table = document.querySelector('form[action*=savetemplate] table.template-table-editable');
			if (!table) return;
			const tbody = table.tBodies[0] || table;

			tbody.querySelectorAll('.template-drag-handle').forEach(handle => {
				handle.setAttribute('draggable', 'true');
				handle.addEventListener('dragstart', function (e) {
					const main = handle.closest('tr.template-row-main');
					if (!main) return;
					main.classList.add('template-row-dragging');
					const details = main.nextElementSibling;
					if (details && details.classList.contains('template-row-details')) {
						details.classList.add('template-row-dragging');
					}
					e.dataTransfer.effectAllowed = 'move';
					e.dataTransfer.setData('text/plain', 'template-row');
				});
				handle.addEventListener('dragend', function () {
					tbody.querySelectorAll('.template-row-dragging').forEach(r => r.classList.remove('template-row-dragging'));
				});
			});

			tbody.addEventListener('dragover', function (e) {
				e.preventDefault();
				const draggingMain = tbody.querySelector('tr.template-row-main.template-row-dragging');
				if (!draggingMain) return;
				const draggingDetails = (draggingMain.nextElementSibling && draggingMain.nextElementSibling.classList.contains('template-row-details'))
					? draggingMain.nextElementSibling
					: null;
				const siblings = Array.from(tbody.querySelectorAll('tr.template-row-main:not(.template-row-dragging)'));
				let next = null;
				for (const row of siblings) {
					const box = row.getBoundingClientRect();
					if (e.clientY < box.top + box.height / 2) {
						next = row;
						break;
					}
				}
				if (next) {
					tbody.insertBefore(draggingMain, next);
					if (draggingDetails) tbody.insertBefore(draggingDetails, next);
				} else {
					tbody.appendChild(draggingMain);
					if (draggingDetails) tbody.appendChild(draggingDetails);
				}
			});
		}

		rkrkInitTemplateDragDrop();
		const tplSaveForm = document.querySelector('form[action*=savetemplate]');
		if (tplSaveForm) {
			tplSaveForm.addEventListener('submit', rkrkReindexTemplateForm);
		}

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
            <a href='".safe(rkrk_assess_append_setting_href("?mode=templates"))."' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE SPEICHERN
----------------------------------------------------------- */
function saveTemplate(){
    global $rkrkAssessTemplates, $InputUserInfo;

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
        $oldPath = "$rkrkAssessTemplates/$oldFile";
        $newPath = "$rkrkAssessTemplates/$newFile";

        /* Falls Zielname existiert → _2, _3, ... */
        if (file_exists($newPath)) {
            $base = pathinfo($newFile, PATHINFO_FILENAME);
            $i = 2;
            do {
                $newFile = $base . "_" . $i . ".json";
                $newPath = "$rkrkAssessTemplates/$newFile";
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
    $thresholdArr = isset($_POST['threshold']) ? $_POST['threshold'] : [];
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
        $threshold = trim(isset($thresholdArr[$i]) ? $thresholdArr[$i] : "");

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
            "threshold"   => $threshold,
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
        $newThreshold = isset($_POST['new_threshold']) ? trim($_POST['new_threshold']) : "";

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
            "threshold"   => $newThreshold,
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
        if (isset($row["threshold"]) && trim((string)$row["threshold"]) !== "") $entry["threshold"] = trim((string)$row["threshold"]);

        $entry["inname"]   = $row["inname"] === "1" ? "1" : "0";
        $entry["inresult"] = $row["inresult"] === "1" ? "1" : "0";

        $sections[$row["sec"]][$row["label"]] = $entry;
    }

    /* Template speichern */
    $tpl = [
        "title" => $title,
        "sections" => $sections
    ];

    file_put_contents("$rkrkAssessTemplates/$file", json_encode($tpl, JSON_PRETTY_PRINT));

    rkrk_redirect("?mode=edittemplate&file=$file");
}

function slugifyTitle($title){
    if (function_exists("rkrk_assess_slugify_file_stem")) {
        return rkrk_assess_slugify_file_stem((string)$title);
    }
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
    if (preg_match('/\((g|r|o|n)\)$/i', $label, $m)){
        $colorKey = strtolower($m[1]);

        $map = [
            'g' => '#6bb36b', // green
            'r' => '#d06a6a', // red
            'o' => '#d89b4c', // orange
            'n' => '#94a3b8', // neutral / nicht bewertet
        ];

        $clean = trim(preg_replace('/\((g|r|o|n)\)$/i', '', $label));

        return [$clean, $map[$colorKey] ?? null, $colorKey];
    }

    return [$label, null, null];
}

function rkrk_color_option_marker_attrs($colorKey){
    $k = strtolower(trim((string)$colorKey));
    $titles = [
        'g' => 'grün (g)',
        'o' => 'orange (o)',
        'r' => 'rot (r)',
        'n' => 'neutral / nicht bewertet (n)',
    ];
    if (!isset($titles[$k])) {
        return '';
    }
    return " data-color-key='" . $k . "' title='" . htmlspecialchars($titles[$k], ENT_QUOTES, 'UTF-8') . "'";
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
        $c = htmlspecialchars((string)$color, ENT_QUOTES, "UTF-8");
        return "<font color=\"" . $c . "\"><span style=\"color:" . $c . ";font-weight:700;\">" . safe($clean) . "</span></font>";
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
        return trim((string)preg_replace('/\s*\(([rogn])\)\s*$/iu', '', (string)$v));
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
   examEN-LISTE PRINT
----------------------------------------------------------- */
function showChecklistPrint(){
    global $rkrkAssessResults, $rkrkAssessTemplates, $IconLoc, $examIconLoc, $logopic;

    $id = basename($_GET['id'] ?? "");
    $path = "$rkrkAssessResults/$id.json";

    if (!file_exists($path)) {
        echo "Assessment nicht gefunden.";
        if (!empty($GLOBALS["rkrk_assess_print_no_exit"])) {
            return;
        }
        exit;
    }

    $data = json_decode(file_get_contents($path), true);
    $tplName = $data["template"] ?? "";
    $tpl = loadTemplate($tplName);

    $title = $tpl["title"] ?? "Assessment";
    $fields = $data["fields"] ?? [];
    $refMaxMap = rkrk_build_ref_max_map($tpl["sections"] ?? array());

    // 🔥 Print-Dateiname = gespeicherter JSON-Dateiname
    $printTitle = $id . " – Druckansicht";
    // PDF-/Abschlussbericht: nur prüflingsrelevante Resultate (wie Live-compact / Quiz-Report)
    $compactReport = !empty($GLOBALS["rkrk_assess_print_compact"])
        || !empty($GLOBALS["rkrk_assess_print_hide_unrated"])
        || !empty($GLOBALS["rkrk_assess_print_no_exit"]);
    if ($compactReport) {
        if (!isset($_GET["hide_ablauf"])) {
            $_GET["hide_ablauf"] = "1";
        }
        if (!isset($_GET["hide_printtype"])) {
            $_GET["hide_printtype"] = "1";
        }
    }
    $showAblauf = !$compactReport && (!isset($_GET['hide_ablauf']) || $_GET['hide_ablauf'] !== '1');
    $showKriterien = !isset($_GET['hide_kriterien']) || $_GET['hide_kriterien'] !== '1';
    $showPrintType = !$compactReport && (!isset($_GET['hide_printtype']) || $_GET['hide_printtype'] !== '1');
    $showCalcMeta = !$compactReport;
    $hideEmptyFields = $compactReport || !empty($GLOBALS["rkrk_assess_print_hide_unrated"]);
    $printLogoPath = (string)$examIconLoc . (string)$logopic;
    $printLogoSrc = htmlspecialchars($printLogoPath, ENT_QUOTES);
    // data:-URI (nicht FS-Pfad): TCPDF behandelt /volume1/… sonst als HTTP-URL
    if (function_exists("rkrk_assess_report_logo_src_html")) {
        $__logoHtml = trim((string) rkrk_assess_report_logo_src_html());
        if ($__logoHtml !== "") {
            $printLogoSrc = htmlspecialchars($__logoHtml, ENT_QUOTES, "UTF-8");
        }
    }

    ob_start();
    rkrk_assess_theme_link_tags();
    $printThemeLinks = ob_get_clean();
    $printSettingHidden = "";
    if (rkrk_assess_ci_rkrs_mode()) {
        $psk = htmlspecialchars((string)$GLOBALS["AddURL"], ENT_QUOTES, "UTF-8");
        $printSettingHidden = "<input type='hidden' name='" . $psk . "' value=''>";
    }

    // Ergebnis-Banner (Bestanden / Ergebnis / Gesamtergebnis) — wie Quiz-pf-legend
    $resultLabelRaw = "";
    $resultValRaw = "";
    foreach ((array)$fields as $__sec => $__secFields) {
        if (!is_array($__secFields)) {
            continue;
        }
        foreach ($__secFields as $__lab => $__val) {
            $__labStr = (string)$__lab;
            $isResultLab = (
                stripos($__labStr, "Bestanden") !== false
                || stripos($__labStr, "Gesamtergebnis") !== false
                || preg_match('/^Ergebnis$/ui', $__labStr) === 1
                || stripos($__labStr, "Einsatz Empfehlung") !== false
                || stripos($__labStr, "Einsatzempfehlung") !== false
                || preg_match('/^Pflichtmaterial$/ui', $__labStr) === 1
            );
            if (!$isResultLab) {
                continue;
            }
            $__v = is_array($__val) ? implode(", ", $__val) : trim((string)$__val);
            if ($__v === "") {
                continue;
            }
            // Prefer Bestanden > Einsatz Empfehlung > Pflichtmaterial > anderes
            $prio = 1;
            if (stripos($__labStr, "Bestanden") !== false) {
                $prio = 4;
            } elseif (stripos($__labStr, "Einsatz Empfehlung") !== false || stripos($__labStr, "Einsatzempfehlung") !== false) {
                $prio = 3;
            } elseif (preg_match('/^Pflichtmaterial$/ui', $__labStr) === 1) {
                $prio = 2;
            }
            if ($resultLabelRaw !== "" && $prio < (int)($GLOBALS["__rkrk_result_prio"] ?? 0)) {
                continue;
            }
            $GLOBALS["__rkrk_result_prio"] = $prio;
            $resultLabelRaw = $__labStr;
            $resultValRaw = $__v;
            if ($prio >= 4) {
                break 2;
            }
        }
    }
    unset($GLOBALS["__rkrk_result_prio"]);
    $resultValClean = $resultValRaw;
    if (function_exists("rkrk_strip_option_markers")) {
        $resultValClean = (string)rkrk_strip_option_markers($resultValRaw);
    }
    $resultBannerHtml = "";
    if ($resultValClean !== "") {
        $passed = null;
        $vl = mb_strtolower($resultValClean, "UTF-8");
        if (
            $vl === "ja" || $vl === "bestanden" || $vl === "pass" || $vl === "ok"
            || (strpos($vl, "bestanden") !== false && strpos($vl, "nicht") === false)
            || (strpos($vl, "als pilot einsetzbar") !== false && strpos($vl, "nicht") === false && strpos($vl, "mit einschr") === false)
            || strpos($vl, "material komplett") !== false
        ) {
            $passed = true;
        } elseif (
            $vl === "nein" || $vl === "nicht bestanden" || $vl === "fail"
            || (strpos($vl, "nicht") !== false && strpos($vl, "bestanden") !== false)
            || strpos($vl, "nicht einsatzbereit") !== false
            || strpos($vl, "material nicht vorhanden") !== false
        ) {
            $passed = false;
        }
        $legendBg = "#f1f5f9";
        $legendFg = "#0f172a";
        $legendLeft = htmlspecialchars($resultLabelRaw !== "" ? $resultLabelRaw : "Ergebnis", ENT_QUOTES, "UTF-8");
        $legendRight = htmlspecialchars($resultValClean, ENT_QUOTES, "UTF-8");
        if ($passed === true) {
            $legendBg = "#eef6ec";
            $legendFg = "#3d6b3a";
            if (stripos($resultLabelRaw, "Bestanden") !== false || preg_match('/^Ergebnis$/ui', $resultLabelRaw)) {
                $legendLeft = "✓ Bestanden";
            } else {
                $legendLeft = "✓ " . htmlspecialchars($resultLabelRaw, ENT_QUOTES, "UTF-8");
            }
        } elseif ($passed === false) {
            $legendBg = "#fde8e8";
            $legendFg = "#b91c1c";
            if (stripos($resultLabelRaw, "Bestanden") !== false || preg_match('/^Ergebnis$/ui', $resultLabelRaw)) {
                $legendLeft = "✗ Nicht bestanden";
            } else {
                $legendLeft = "✗ " . htmlspecialchars($resultLabelRaw, ENT_QUOTES, "UTF-8");
            }
        } else {
            // Teilweise / eingeschränkt
            $legendBg = "#fff6dc";
            $legendFg = "#7c4a03";
            $legendLeft = htmlspecialchars($resultLabelRaw !== "" ? $resultLabelRaw : "Ergebnis", ENT_QUOTES, "UTF-8");
        }
        $resultBannerHtml =
            "<table class=\"pf-legend\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\""
            . htmlspecialchars($legendBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"width:100%;margin:4px 0 8px;padding:0;border:none;border-collapse:collapse;background-color:"
            . htmlspecialchars($legendBg, ENT_QUOTES, "UTF-8") . ";\">"
            . "<tr>"
            . "<td class=\"result-label\" valign=\"middle\" width=\"40%\" bgcolor=\""
            . htmlspecialchars($legendBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"font-size:12pt;font-weight:800;line-height:1.2;color:"
            . htmlspecialchars($legendFg, ENT_QUOTES, "UTF-8") . ";padding:6px 8px;\">"
            . "<font color=\"" . htmlspecialchars($legendFg, ENT_QUOTES, "UTF-8") . "\"><b>" . $legendLeft . "</b></font>"
            . "</td>"
            . "<td class=\"result-score\" align=\"right\" valign=\"middle\" width=\"60%\" bgcolor=\""
            . htmlspecialchars($legendBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"font-size:9.5pt;font-weight:700;line-height:1.25;text-align:right;color:"
            . htmlspecialchars($legendFg, ENT_QUOTES, "UTF-8") . ";padding:6px 8px;\">"
            . "<font color=\"" . htmlspecialchars($legendFg, ENT_QUOTES, "UTF-8") . "\"><b>" . $legendRight . "</b></font>"
            . "</td>"
            . "</tr></table>";
    }

    echo "<!doctype html>
<html lang='de'>
<head>
<meta charset='utf-8'>
<title>".htmlspecialchars($printTitle)."</title>
" . $printThemeLinks . "
</head>

<body class='rkrk-assess-print rkrk-checklist-report pf-report'>
<table class='print-header-table pf-header print-header' width='100%' border='0' cellpadding='0' cellspacing='0'><tr>
    <td class='print-title' valign='top' width='68%'>
        <div style='font-size:13pt;font-weight:bold;line-height:1.2;color:#111;'>".htmlspecialchars($title)."</div>
        <span class='print-subtitle pf-sub' style='display:block;font-size:9pt;color:#475569;font-weight:normal;margin-top:2px;'>Abschlussbericht</span>
    </td>
    <td class='print-logo pf-logo' align='right' valign='top' width='32%' style='padding:0;text-align:right;'>
        <img src='".$printLogoSrc."' alt='Logo' height='40' align='right' style='height:40px;width:auto;display:block;margin-left:auto;margin-right:0;'>
    </td>
</tr></table>
<form method='get' class='print-options no-print'>
    <input type='hidden' name='mode' value='print'>
    <input type='hidden' name='id' value='".htmlspecialchars($id, ENT_QUOTES)."'>
    ".$printSettingHidden."
    <label><input type='checkbox' name='hide_ablauf' value='1' ".($showAblauf ? "" : "checked")."> Ablauf ausblenden</label>
    <label><input type='checkbox' name='hide_kriterien' value='1' ".($showKriterien ? "" : "checked")."> Kriterien ausblenden</label>
    <label><input type='checkbox' name='hide_printtype' value='1' ".($showPrintType ? "" : "checked")."> Print-Text ausblenden</label>
    <button type='submit'>Ansicht aktualisieren</button>
</form>
";

    /* ----------------------------------------
       1) TEMPLATE-FELDER + Extra-Felder je Kapitel (gleiches Format)
    ---------------------------------------- */
    // Extra-Felder je Sektion vorbereiten (nicht im Template, aber in Result-JSON)
    $extraBySection = array();
    foreach ((array)$fields as $__secName => $__secFields) {
        if (!is_array($__secFields)) {
            continue;
        }
        foreach ($__secFields as $__lab => $__val) {
            if (isset($tpl["sections"][$__secName][$__lab])) {
                continue;
            }
            $extraBySection[$__secName][$__lab] = $__val;
        }
    }

    $resultBannerPending = ($resultBannerHtml !== "");
    $sectionsToPrint = array();
    foreach ((array)($tpl["sections"] ?? array()) as $__sn => $__sf) {
        $sectionsToPrint[$__sn] = $__sf;
    }
    // Kapitel nur mit Extra-Feldern (ohne Template-Definition) ebenfalls drucken
    foreach ($extraBySection as $__sn => $__sf) {
        if (!isset($sectionsToPrint[$__sn])) {
            $sectionsToPrint[$__sn] = array();
        }
    }

    foreach ($sectionsToPrint as $secName => $secFields) {
        $printSecName = ($secName === "Wetter") ? "Wetter zum Startzeitpunkt" : $secName;
        $sectionFieldsHtml = array();

        foreach ($secFields as $label => $meta) {
            $val = $fields[$secName][$label] ?? "";
            $typeString = strtolower(rkrk_type_string($meta));
            if ($typeString === "hidden") {
                continue;
            }
            if (is_array($val)) $val = implode(", ", $val);
            $val = formatPrintTimeByLabel($label, $val);
            $isRemarkField = (preg_match('/\bBemerkung\b/ui', (string)$label) === 1);
            $isBeobachtungField = (preg_match('/\bBeobachtung\b|\bEinschätzung\b|\bEinschaetzung\b|\bKorrekturen\b|\bAbschlussbeurteilung\b|\bEmpfehlung\b/ui', (string)$label) === 1);
            $isFullWidthText = $isRemarkField || $isBeobachtungField
                || $typeString === "textarea"
                || (strpos($typeString, "text") === 0 && strlen(trim((string) $val)) > 120);
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
            // Instruktionstexte (Ablauf/Prüfkriterien/Optionallisten) nie im Abschlussbericht
            $isInstructionLabel = (
                preg_match('/^(Ablauf|Prüfkriterien|Pruefkriterien)$/ui', (string)$label) === 1
                || stripos((string)$label, "Optionale Materialliste") !== false
                || stripos((string)$label, "Material und Einsatzbereitschaft") !== false
            );
            if ($compactReport && ($typeString === "print" || $isInstructionLabel)) {
                continue;
            }
            if ($typeString === "print" && $printHtml !== "") {
                $valHtml = (strip_tags($printHtml) !== $printHtml) ? $printHtml : nl2br(safe($printHtml));
                if (strip_tags($printHtml) !== $printHtml && function_exists("rkrk_assess_format_template_html_fragment")) {
                    $valHtml = rkrk_assess_format_template_html_fragment($printHtml, "print");
                }
            } elseif ($typeString === "checkbox" || strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0) {
                $valHtml = colorizePrintValue($val, $typeString);
            } else {
                $valHtml = rkrk_print_weather_value($secName, $label, $val);
            }

            // Druckansicht: leere Bemerkungen ausblenden
            if (($isRemarkField || $isBeobachtungField) && trim((string)$val) === "") {
                continue;
            }
            // Banner-Feld nicht nochmals im Body
            if ($resultBannerHtml !== "" && $resultLabelRaw !== "" && (string)$label === $resultLabelRaw) {
                continue;
            }
            // Leere Felder im Abschlussbericht ausblenden (keine Platzhalter)
            if (
                $hideEmptyFields
                && $typeString !== "print"
                && $typeString !== "summe"
                && $typeString !== "countfails"
                && $typeString !== "countcritical"
                && trim((string)$val) === ""
            ) {
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
            if ($showCalcMeta && $typeString === "summe" && $calc !== "") {
                $maxVal = rkrk_eval_formula_numeric($calc, $refMaxMap);
                $maxTxt = ($maxVal !== null) ? (" | Max: " . safe((string)$maxVal)) : "";
                $metaInfo = "<div class='value-meta'>Berechnung: " . safe($calc) . $maxTxt . "</div>";
            } elseif ($showCalcMeta && $typeString === "countfails") {
                if ($calc !== "") {
                    $metaInfo = "<div class='value-meta'>CountFails über: " . safe($calc) . "</div>";
                } else {
                    $metaInfo = "<div class='value-meta'>CountFails: alle gewählten (r)-Bewertungen</div>";
                }
            } elseif ($showCalcMeta && $typeString === "countcritical") {
                if ($calc !== "") {
                    $metaInfo = "<div class='value-meta'>CountCritical über: " . safe($calc) . "</div>";
                } else {
                    $metaInfo = "<div class='value-meta'>CountCritical: alle gewählten (r)/(o)-Bewertungen</div>";
                }
            }
            if ($showAblauf && $process !== "") {
                if (rkrk_process_is_html($process)) {
                    $metaInfo .= "<div class='value-meta value-meta-html'><strong>Ablauf:</strong> " . $process . "</div>";
                } else {
                    $metaInfo .= "<div class='value-meta'><strong>Ablauf:</strong> " . nl2br(safe($process)) . "</div>";
                }
            }
            if ($showKriterien && !empty($missedItems)) {
                $metaInfo .= "<div class='value-meta'><strong>Nicht erfüllte Kriterien:</strong> " . safe(implode(" | ", $missedItems)) . "</div>";
            }

            // Spezialfelder fett
            $isHighlight = in_array($label, [
                "Auftrag", "Feld", "Flugort", "Flugzeit"
            ], true);

            $cls = $isHighlight ? "field highlight" : "field";
            if ($isFullWidthText) {
                $cls .= " field-spec-ref field-beobachtung";
            }
            $useStack = $isFullWidthText || ($typeString === "print" || $metaInfo !== ""
                || (is_string($valHtml) && (strpos($valHtml, "<div") !== false || strpos($valHtml, "<br") !== false || strpos($valHtml, "<table") !== false)));
            if ($useStack) {
                $cls .= " pf-field-stack";
            }

            if ($useStack) {
                $stackVal = $valHtml;
                if ($isFullWidthText && is_string($stackVal) && strpos($stackVal, "<") === false) {
                    $stackVal = nl2br(safe((string) $val));
                }
                $sectionFieldsHtml[] = "<div class=\"" . htmlspecialchars($cls, ENT_QUOTES, "UTF-8") . "\" style=\"margin:0;padding:0;border:none;\">"
                    . "<div class=\"pf-label label\" style=\"display:block;font-size:7.5pt;font-weight:700;color:#64748b;margin:0 0 4px;\"><font color=\"#64748b\">" . htmlspecialchars($label) . "</font></div>"
                    . "<div class=\"pf-line value\" style=\"display:block;font-size:9.5pt;font-weight:600;color:#111;margin:0;\">{$stackVal}{$metaInfo}</div>"
                    . "</div>";
            } else {
                $sectionFieldsHtml[] = "<span class=\"pf-label label\" style=\"font-size:7.5pt;font-weight:700;color:#64748b;\"><font color=\"#64748b\">" . htmlspecialchars($label) . ":</font></span> "
                    . "<span class=\"pf-line value\" style=\"font-size:9.5pt;font-weight:600;color:#111;\">{$valHtml}</span>";
            }
        }

        // Zusätzliche Felder dieses Kapitels — gleiches Format wie Template-Felder
        if (!empty($extraBySection[$secName]) && is_array($extraBySection[$secName])) {
            foreach ($extraBySection[$secName] as $label => $value) {
                if (preg_match('/\bNicht erfüllte Punkte$/u', (string)$label)) {
                    continue;
                }
                $isRemarkField = (preg_match('/\bBemerkung\b/ui', (string)$label) === 1);
                $isBeobachtungField = (preg_match('/\bBeobachtung\b|\bEinschätzung\b|\bEinschaetzung\b|\bKorrekturen\b|\bAbschlussbeurteilung\b|\bEmpfehlung\b|\bHinweis\b/ui', (string)$label) === 1);
                if (is_array($value)) {
                    $value = implode(", ", $value);
                }
                $value = formatPrintTimeByLabel($label, $value);
                if (($isRemarkField || $isBeobachtungField) && trim((string)$value) === "") {
                    continue;
                }
                if ($hideEmptyFields && trim((string)$value) === "") {
                    continue;
                }
                if ($resultBannerHtml !== "" && $resultLabelRaw !== "" && (string)$label === $resultLabelRaw) {
                    continue;
                }
                $valHtml = colorizePrintValue($value);
                $isFullWidthText = $isRemarkField || $isBeobachtungField || strlen(trim((string)$value)) > 120;
                $cls = "field";
                if ($isFullWidthText) {
                    $cls .= " field-spec-ref field-beobachtung pf-field-stack";
                    $stackVal = $valHtml;
                    if (is_string($stackVal) && strpos($stackVal, "<") === false) {
                        $stackVal = nl2br(safe((string)$value));
                    }
                    $sectionFieldsHtml[] = "<div class=\"" . htmlspecialchars($cls, ENT_QUOTES, "UTF-8") . "\" style=\"margin:0;padding:0;border:none;\">"
                        . "<div class=\"pf-label label\" style=\"display:block;font-size:7.5pt;font-weight:700;color:#64748b;margin:0 0 4px;\"><font color=\"#64748b\">" . htmlspecialchars((string)$label) . "</font></div>"
                        . "<div class=\"pf-line value\" style=\"display:block;font-size:9.5pt;font-weight:600;color:#111;margin:0;\">{$stackVal}</div>"
                        . "</div>";
                } else {
                    $sectionFieldsHtml[] = "<span class=\"pf-label label\" style=\"font-size:7.5pt;font-weight:700;color:#64748b;\"><font color=\"#64748b\">" . htmlspecialchars((string)$label) . ":</font></span> "
                        . "<span class=\"pf-line value\" style=\"font-size:9.5pt;font-weight:600;color:#111;\">{$valHtml}</span>";
                }
            }
            unset($extraBySection[$secName]);
        }

        if (!empty($sectionFieldsHtml)) {
            echo "<div class=\"section pf-sec\" style=\"margin:6px 0 4px;padding-top:4px;border:none;\">";
            echo "<h2 style=\"margin:0 0 4px;font-size:9.5pt;color:#1e3a1e;font-weight:bold;\"><font color=\"#1e3a1e\">" . htmlspecialchars($printSecName) . "</font></h2>";
            echo "<table class=\"pf-sec-grid field-grid\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"2\" style=\"width:100%;border-collapse:collapse;border:none;\">";
            $__n = count($sectionFieldsHtml);
            $__cellStyle = "width:50%;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;";
            $__fullStyle = "width:100%;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;";
            for ($__i = 0; $__i < $__n; $__i++) {
                $__cell = $sectionFieldsHtml[$__i];
                $__full = (strpos($__cell, "field-spec-ref") !== false);
                if ($__full) {
                    echo "<tr><td colspan=\"2\" valign=\"top\" class=\"pf-field field-cell field-full\" style=\"" . $__fullStyle . "\">" . $__cell . "</td></tr>";
                    continue;
                }
                $__next = ($__i + 1 < $__n) ? $sectionFieldsHtml[$__i + 1] : "";
                $__nextFull = ($__next !== "" && strpos($__next, "field-spec-ref") !== false);
                echo "<tr>";
                echo "<td width=\"50%\" valign=\"top\" class=\"pf-field field-cell\" style=\"" . $__cellStyle . "\">" . $__cell . "</td>";
                if ($__next !== "" && !$__nextFull) {
                    echo "<td width=\"50%\" valign=\"top\" class=\"pf-field field-cell\" style=\"" . $__cellStyle . "\">" . $__next . "</td>";
                    $__i++;
                } else {
                    echo "<td width=\"50%\" valign=\"top\" class=\"pf-field pf-field-empty field-cell\" style=\"border:none;background:transparent;\">&nbsp;</td>";
                }
                echo "</tr>";
            }
            echo "</table></div>";
            // Ergebnis-Banner direkt unter der Allgemein-Sektion (wie Bestanden im Digital-Report)
            if ($resultBannerPending && ($secName === "Allgemein" || stripos((string) $secName, "Allgemein") === 0)) {
                echo $resultBannerHtml;
                $resultBannerPending = false;
            }
        }
    }
    if (!empty($resultBannerPending)) {
        echo $resultBannerHtml;
        $resultBannerPending = false;
    }

    if (function_exists("rkrk_assess_report_copyright_footer_html")) {
        echo rkrk_assess_report_copyright_footer_html();
    }

    echo "</body></html>";
}


/* -----------------------------------------------------------
   examEN-LISTE
----------------------------------------------------------- */
function showChecklistList($showAll = false){
    global $rkrkAssessResults, $rkrkAssessTemplates;

    // Lokaler Modus – verhindert Undefined Variable Notices
    $mode = $_GET['mode'] ?? 'list';
    $filterTemplate = trim((string)($_GET['f_template'] ?? ''));
    $filterPilot = trim((string)($_GET['f_pilot'] ?? ''));
    $filterText = trim((string)($_GET['f_text'] ?? ''));
    $filterDateFrom = trim((string)($_GET['f_from'] ?? ''));
    $filterDateTo = trim((string)($_GET['f_to'] ?? ''));
    $hasFilters = ($filterTemplate !== '' || $filterPilot !== '' || $filterText !== '' || $filterDateFrom !== '' || $filterDateTo !== '');

    pageHeaderLocal("Assessment");
    rkrk_checklist_toolbar_echo_styles_once();

    if(isset($_GET['saved']))
        echo "<div class='rkrk-card success'>✓ Assessment gespeichert.</div>";

    if(isset($_GET['deleted']))
        echo "<div class='rkrk-card danger'>✓ Eintrag gelöscht.</div>";

    if (isset($_GET["err"]) && (string)$_GET["err"] === "import") {
        echo "<div class='rkrk-card' style='border-color:#fca5a5;background:#fff5f5;'><strong>Import abgebrochen</strong>: Datei fehlt oder ungültige Anfrage.</div>";
    }

    echo "<div class='rkrk-card checklist-header'>
        <div class='checklist-title'>Neues Assessment</div>
        <div class='checklist-buttons'>";

    foreach (listAssessmentTemplateFiles() as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = $tpl["title"] ?? $base;

        $formHref = rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($base));
        $paperHref = function_exists("rkrk_checklist_toolbar_paperform_link")
            ? rkrk_checklist_toolbar_paperform_link($base, null, "list")
            : rkrk_assess_append_setting_href("?mode=paperform&file=" . rawurlencode($base) . "&from=list");
        echo "<span style='display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center;margin:4px 6px 4px 0;'>";
        echo "<a href='".safe($formHref)."' class='btn btn-primary'>➕ ".safe($title)."</a>";
        echo "<a href='".safe($paperHref)."' class='chk-act' target='_blank' rel='noopener' title='Papierformular (leer)' aria-label='Papierformular'>📄</a>";
        echo "</span>";
    }

    echo "</div></div>";

    $templateChoices = array();
    foreach (listAssessmentTemplateFiles() as $tplFile){
        $base = basename($tplFile);
        $tpl = loadTemplate($base);
        $templateChoices[$base] = $tpl["title"] ?? $base;
    }
    asort($templateChoices, SORT_NATURAL | SORT_FLAG_CASE);

    echo "<div class='rkrk-card'>
            <h3>".($showAll ? "Alle gespeicherten Assessment" : "Letzte 12 gespeicherte Assessment")."</h3>";
    echo "<form id='exam-list-filter-form' method='get' style='display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:14px; align-items:end;'>
            <input type='hidden' name='mode' value='".safe($mode)."'>";
    rkrk_assess_echo_setting_hidden_input();
    echo "
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
                <span style='font-size:12px; color:#5f6b66;'>Name / Pilot enthält</span>
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
                <a class='btn' href='".safe(rkrk_assess_append_setting_href("?mode=" . $mode))."'>Zurücksetzen</a>
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

    $jsonFiles = glob("$rkrkAssessResults/*.json");
    usort($jsonFiles, fn($a,$b) => filemtime($b) - filemtime($a));

    if (!$showAll && !$hasFilters) {
        $jsonFiles = array_slice($jsonFiles, 0, 12);
    }

    echo "<table class='list-table'>
            <tr>
                <th>Template</th>
                <th>Datum & Zeit</th>
                <th>Name</th>
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
            if (!$tpl && file_exists("$rkrkAssessTemplates/$tplName.json")) {
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

        $nameCell = rkrk_assess_list_merge_name_pilot_cell($nameParts, $pilot);

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

        if (
            trim((string)$title) === ""
            && trim((string)$dt) === ""
            && trim((string)$nameCell) === ""
            && trim((string)$info) === ""
        ) {
            continue;
        }

        if ($filterTemplate !== '' && $tplName !== $filterTemplate) continue;
        if (!$containsCi(rkrk_assess_list_name_pilot_filter_haystack($nameParts, $pilot), $filterPilot)) continue;
        if (!$containsCi($id . " " . $nameCell . " " . $info, $filterText)) continue;
        if ($fromTs !== null && $dtFilterTs !== false && $dtFilterTs < $fromTs) continue;
        if ($toTs !== null && $dtFilterTs !== false && $dtFilterTs > $toTs) continue;

        $rowCount++;

        echo "<tr>
                <td>".safe($title)."</td>
                <td>".safe($dt)."</td>
                <td>".safe($nameCell)."</td>
                <td class='rkrk-list-info-cell'>".rkrk_assess_format_list_info_ampel_html($info)."</td>
                <td class='checklist-actions' style='white-space:nowrap;'>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($tplName) . "&edit=" . rawurlencode($id)), ENT_QUOTES, "UTF-8") . "' target='_self' title='Assessment bearbeiten' aria-label='Bearbeiten'>" . rkrk_checklist_toolbar_svg_edit() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($tplName) . "&edit=" . rawurlencode($id) . "&readonly=1"), ENT_QUOTES, "UTF-8") . "' target='_self' title='Nur lesen' aria-label='Lesen'>" . rkrk_checklist_toolbar_svg_read() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_export_link($id), ENT_QUOTES, "UTF-8") . "' title='JSON exportieren (Backup)' aria-label='Export' download>" . rkrk_checklist_toolbar_svg_export() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_paperform_link($tplName), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Papierformular (leer, Template)' aria-label='Papierformular'>📄</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_print_link($id), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Druckansicht (Strg+P / PDF)' aria-label='Drucken'>" . rkrk_checklist_toolbar_svg_print() . "</a>
                    " . rkrk_checklist_toolbar_report_act_html($id, "assess") . "
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=deletejson&id=" . rawurlencode($id)), ENT_QUOTES, "UTF-8") . "'
                       onclick='return confirm(\"Eintrag wirklich löschen?\")' title='Eintrag löschen' aria-label='Löschen' style='color:#8b2e2e;border-color:#e8c8c8;'>" . rkrk_checklist_toolbar_svg_trash() . "</a>
                </td>
              </tr>";
    }

    if ($rowCount === 0) {
        echo "<tr><td colspan='5' style='text-align:center; color:#6b7280; padding:12px;'>Keine Assessment mit diesen Filtern gefunden.</td></tr>";
    }

    echo "</table></div>";

    $activeFilters = array();
    if ($filterTemplate !== '') $activeFilters['f_template'] = $filterTemplate;
    if ($filterPilot !== '') $activeFilters['f_pilot'] = $filterPilot;
    if ($filterText !== '') $activeFilters['f_text'] = $filterText;
    if ($filterDateFrom !== '') $activeFilters['f_from'] = $filterDateFrom;
    if ($filterDateTo !== '') $activeFilters['f_to'] = $filterDateTo;
    $listUrl = '?' . http_build_query(rkrk_assess_merge_query_with_setting(array_merge(array('mode' => 'list'), $activeFilters)));
    $listAllUrl = '?' . http_build_query(rkrk_assess_merge_query_with_setting(array_merge(array('mode' => 'listall'), $activeFilters)));

    echo "<div style='text-align:center; margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;'>

    <a href='".safe($listUrl)."' 
       class='btn' 
       ".($mode=='list' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Neueste 12 Assessment
    </a>

    <a href='".safe($listAllUrl)."' 
       class='btn' 
       ".($mode=='listall' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Alle Assessment
    </a>";
	
	//if (GetAccess("TestOnly")) {
	
		if (isset($_GET['ADMIN']) && GetAccessByFunction ('AssessmentTemplate')) {		
			echo "
				<a href='".safe(rkrk_assess_append_setting_href("?mode=templates"))."' class='btn'>
					📁 Templates bearbeiten
				</a>
			";
		}	
		if (isset($_GET['ADMIN'])) {
		echo "
			<a href='".safe(rkrk_assess_append_setting_href("?mode=linkbuilder"))."' class='btn'>
				🔗 URL Generator
			</a>

			<a href='".safe(rkrk_assess_append_setting_href("rkrk_assess_admin.php"))."' class='btn'>
				🛠️ Admin
			</a>
		";
		}
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
        $savedSection = (isset($savedFields[$secName]) && is_array($savedFields[$secName])) ? $savedFields[$secName] : [];
        $labelMap = function_exists("rkrk_map_saved_labels_to_template")
            ? rkrk_map_saved_labels_to_template($fields, $savedSection)
            : [];
        $valueByTpl = [];
        foreach ($labelMap as $savedLabel => $tplLabel) {
            if (!array_key_exists($savedLabel, $savedSection)) {
                continue;
            }
            $v = $savedSection[$savedLabel];
            if (!array_key_exists($tplLabel, $valueByTpl)) {
                $valueByTpl[$tplLabel] = $v;
                continue;
            }
            // Bei Kollision nicht-leeren Wert bevorzugen (Umnummerierung + Restfelder)
            if (trim((string)$valueByTpl[$tplLabel]) === "" && trim((string)$v) !== "") {
                $valueByTpl[$tplLabel] = $v;
            }
        }
        $savedByNorm = [];
        foreach ($savedSection as $savedLabel => $savedValue) {
            $savedByNorm[rkrk_normalize_field_label_key($savedLabel)] = $savedValue;
        }
        foreach ($fields as $label => $meta) {
            if (array_key_exists($label, $valueByTpl)) {
                $merged[$secName][$label] = $valueByTpl[$label];
                continue;
            }
            if (array_key_exists($label, $savedSection)) {
                $merged[$secName][$label] = $savedSection[$label];
                continue;
            }
            $normLabel = rkrk_normalize_field_label_key($label);
            $merged[$secName][$label] = $savedByNorm[$normLabel] ?? "";
        }
    }

    foreach ($savedFields as $secName => $fields) {
        if (!is_array($fields)) {
            continue;
        }
        $tplSec = (isset($templateSections[$secName]) && is_array($templateSections[$secName]))
            ? $templateSections[$secName]
            : [];
        $labelMap = function_exists("rkrk_map_saved_labels_to_template")
            ? rkrk_map_saved_labels_to_template($tplSec, $fields)
            : [];
        foreach ($fields as $label => $value) {
            if (isset($labelMap[$label])) {
                continue;
            }
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

    $normalizeOptionValue = function($v) {
        if (is_array($v)) {
            $v = implode(" ", array_map(static function($x) {
                return is_scalar($x) ? (string)$x : "";
            }, $v));
        }
        $s = trim((string)$v);
        $s = preg_replace('/\s*\(([gor])\)\s*$/iu', '', $s);
        $s = preg_replace('/\s+/u', ' ', (string)$s);
        return trim((string)$s);
    };

    // RADIO
    if (strpos($typeString, "radio:") === 0) {
        $opts = explode("|", substr($typeString, 6));
        $presetNorm = $normalizeOptionValue($preset);
        foreach ($opts as $o) {
            $oTrim = trim($o);
            if ($oTrim === $preset || $normalizeOptionValue($oTrim) === $presetNorm) {
                return $oTrim;
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
    $threshold = "";

    $process = "";
    $checks = "";

    if (is_array($type)) {
        $placeholder = isset($type["placeholder"]) ? $type["placeholder"] : "";
        $info        = isset($type["info"]) ? $type["info"] : "";
        $ref         = isset($type["ref"]) ? rkrk_normalize_ref_id($type["ref"]) : "";
        $calc        = isset($type["calc"]) ? trim((string)$type["calc"]) : "";
        $options     = isset($type["options"]) ? trim((string)$type["options"]) : "";
        $threshold   = isset($type["threshold"]) ? trim((string)$type["threshold"]) : "";
        $process     = isset($type["process"]) ? trim((string)$type["process"]) : "";
        $checks      = isset($type["checks"]) ? trim((string)$type["checks"]) : "";
        $typeString  = isset($type["type"]) ? $type["type"] : "text";
    } else {
        $typeString = $type;
    }
    if (($typeString === "summe" || $typeString === "countfails" || $typeString === "countcritical") && $calc === "" && $options !== "") {
        $calc = $options;
    }

    if (strtolower(trim((string)$typeString)) === "hidden") {
        $value = applyPresetValue($label, $typeString, $value);
        $key   = rkrk_field_key_from_label($label);
        $refAttrH = ($ref !== "") ? " data-ref-id=\"" . htmlspecialchars($ref, ENT_QUOTES) . "\" " : "";
        echo "<input type=\"hidden\"
                     name=\"fields[" . safe($secName) . "][" . safe($label) . "]\"
                     data-field-key=\"" . safe($key) . "\"
                     data-field-type=\"hidden\"
                     $refAttrH
                     value=\"" . htmlspecialchars((string)$value, ENT_QUOTES) . "\">\n";
        return;
    }

    // Bemerkungsfelder immer mehrzeilig darstellen
    if (preg_match('/\bBemerkung\b/ui', (string)$label) === 1) {
        $typeString = "textarea";
    }

    // Geburtsdatum: oft als "text" oder "Date" (Großschreibung) angelegt — für HTML5 date + Default
    if (preg_match('/^Geburtsdatum\b/ui', trim((string)$label)) === 1) {
        $tsNorm = strtolower(trim((string)$typeString));
        if ($tsNorm === "text" || $tsNorm === "string") {
            $typeString = "date";
        }
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
            echo " <span class='info-icon' tabindex='0' role='note' aria-label='Hilfe'>ⓘ
                    <span class='info-tooltip'>".nl2br(safe($info))."</span>
                   </span>";
        }
    }

    echo "</label>";
    if (!$isPrintType && ($process !== "" || $checks !== "") && function_exists("rkrk_assess_emit_point_flow_block")) {
        $pcEmit = function_exists("rkrk_extract_point_code") ? rkrk_extract_point_code((string)$label) : "";
        rkrk_assess_emit_point_flow_block($process, $checks, $pcEmit);
    }
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
            if (strip_tags($printText) !== $printText && function_exists("rkrk_assess_format_template_html_fragment")) {
                $printHtml = rkrk_assess_format_template_html_fragment($printText, "screen");
            }
            $printSummary = safe($label);
            echo "<details class='print-text-block' open style='margin-top:6px;'>
                    <summary class='print-text-summary' style='cursor:pointer; font-weight:600;'>".$printSummary."</summary>
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

    /* Helper: normalize option values so old saved data still matches after marker/color template edits. */
    $normalizeOptionValue = function($v) {
        if (is_array($v)) {
            $v = implode(" ", array_map(static function($x) {
                return is_scalar($x) ? (string)$x : "";
            }, $v));
        }
        $s = trim((string)$v);
        // remove trailing marker suffixes like "(g)", "(o)", "(r)"
        $s = preg_replace('/\s*\(([gor])\)\s*$/iu', '', $s);
        // collapse internal whitespace to avoid formatting mismatches
        $s = preg_replace('/\s+/u', ' ', (string)$s);
        return trim((string)$s);
    };

    /* Helper: builds inline style so selected background == border color */
 
	$buildColorStyle = function($color, $isSelected, $rawOption = "") {
        if (!$color) {
            if (is_array($rawOption)) {
                $rawOption = implode(" ", array_map(static function($x) {
                    return is_scalar($x) ? (string)$x : "";
                }, $rawOption));
            }
            $opt = trim((string)$rawOption);
            // Explicit marker palette first: "(g)" = green, "(o)" = orange, "(r)" = red.
            if (preg_match('/\((g|o|r)\)\s*$/i', $opt, $m) === 1) {
                $mk = strtolower((string)$m[1]);
                if ($mk === "g") {
                    $color = "#6f9b6b";
                } elseif ($mk === "o") {
                    $color = "#d39b45";
                } elseif ($mk === "r") {
                    $color = "#d06a6a";
                }
            }
        }
        if (!$color) {
            $opt = trim((string)$rawOption);
            // Default semantic palette for plain numeric options (e.g. "0|1", "0|1|2|3").
            if ($opt === "0") {
                $color = "#d06a6a";
            } elseif ($opt === "1") {
                $color = "#6f9b6b";
            } elseif ($opt === "2") {
                $color = "#d39b45";
            } elseif ($opt === "3") {
                $color = "#4f7f49";
            } else {
                return "";
            }
        }
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
        $valueNorm = $normalizeOptionValue($value);
        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = ($value === $oTrim) || ($valueNorm !== "" && $normalizeOptionValue($oTrim) === $valueNorm);

            list($cleanLabel, $color, $colorKey) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected, $oTrim);

            echo "<label class='color-option$selectedClass' $style" . rkrk_color_option_marker_attrs($colorKey) . ">
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
        if (function_exists("rkrk_assess_echo_pass_need")) {
            rkrk_assess_echo_pass_need($threshold);
        }
    }

    /* -------------------------
       MULTI (mit Farbcodes)
    ------------------------- */
    elseif (strpos($typeString, "multi:") === 0) {

        $opts     = explode("|", substr($typeString, 6));
        $selected = is_array($value) ? $value : explode(";", (string)$value);
        $selectedNorm = array();
        foreach ((array)$selected as $sv) {
            $nv = $normalizeOptionValue($sv);
            if ($nv !== "") {
                $selectedNorm[] = $nv;
            }
        }

        echo "<div class='option-grid'>";

        foreach ($opts as $o) {

            $oTrim = trim($o);
            $isSelected = in_array($oTrim, $selected, true) || in_array($normalizeOptionValue($oTrim), $selectedNorm, true);

            list($cleanLabel, $color, $colorKey) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected, $oTrim);

            echo "<label class='color-option$selectedClass' $style" . rkrk_color_option_marker_attrs($colorKey) . ">
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

        list($cleanLabel, $color, $colorKey) = extractColor($label);

        $selectedClass = $isSelected ? " selected" : "";
        $style = $buildColorStyle($color, $isSelected, $label);

        echo "<div class='option-grid'>
                <label class='color-option$selectedClass' $style" . rkrk_color_option_marker_attrs($colorKey) . ">
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
    elseif (strtolower((string)$typeString) === "datetime") {
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
    elseif (strtolower((string)$typeString) === "date") {
        global $isEditMode;
        $val = rkrk_html_date_input_value($value, (bool)$isEditMode);
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
        $sumThresholdAttr = $threshold !== "" ? " data-sum-threshold=\"" . htmlspecialchars($threshold, ENT_QUOTES) . "\"" : "";
        echo "<input type='hidden'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     data-sum-field='1'
                     data-sum-calc=\"$sumCalc\"
                     $sumThresholdAttr
                     value='$sumValue'>";
        echo "<div class='sum-value-display' data-sum-display-for=\"$key\">$sumValue</div>";
        if ($calc !== "") {
            echo "<small class='field-placeholder'>Berechnung: ".safe($calc)." &nbsp; Max erreichbar: <span class='sum-max-value' data-sum-max-for=\"$key\">–</span></small>";
        }
        if (function_exists("rkrk_assess_echo_pass_need")) {
            rkrk_assess_echo_pass_need($threshold);
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
        $listAttr = "";
        if (
            $typeString === "text"
            && $secName === "Allgemein"
            && ($label === "Pilot" || $label === "Piloten")
            && !empty($GLOBALS["rkrkAssessPersPickExplicit"])
            && !empty($GLOBALS["rkrkAssessPersPickOptions"])
            && !$readonly
        ) {
            $listAttr = " list=\"rkrk-perspick-datalist\" autocomplete=\"off\"";
        } elseif (
            $typeString === "text"
            && $secName === "Allgemein"
            && ($label === "Instruktor" || $label === "Prüfer" || $label === "Pruefer")
            && !empty($GLOBALS["rkrkAssessPersPickExplicit"])
            && !empty($GLOBALS["rkrkAssessPersPickInstrPrueferOptions"])
            && !$readonly
        ) {
            $listAttr = " list=\"rkrk-perspick-instr-datalist\" autocomplete=\"off\"";
        }
        echo "<input type='text'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     $listAttr
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
    global $rkrkAssessResults;

    $path = "$rkrkAssessResults/" . basename($editId) . ".json";
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
    global $rkrkAssessResults;
    $edit = basename($_POST['edit'] ?? '');
    if ($edit === '') {
        echo "ERR";
        return;
    }
    $path = "$rkrkAssessResults/$edit.json";
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
    global $rkrkAssessTemplates, $rkrkAssessResults, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
    $isGuest=false;

    $file     = basename($_GET['file'] ?? "");
    $edit     = isset($_GET['edit']) ? basename($_GET['edit']) : null;
    $readonly = isset($_GET['readonly']);

    $tpl = loadTemplate($file);
    $title    = $tpl["title"] ?? "Assessment";
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
            $modifiedAt = $json["updated_at"] ?? $json["modified_at"] ?? null;

            $saved = mergeTemplateWithSaved($sections, $savedRaw);
            $editIdForWeather = (string)$edit;

            foreach ($savedRaw as $secName => $fields) {
                if (!is_array($fields)) {
                    continue;
                }
                $tplSec = (isset($sections[$secName]) && is_array($sections[$secName]))
                    ? $sections[$secName]
                    : [];
                $labelMap = function_exists("rkrk_map_saved_labels_to_template")
                    ? rkrk_map_saved_labels_to_template($tplSec, $fields)
                    : [];
                foreach ($fields as $label => $value) {
                    if (isset($labelMap[$label])) {
                        continue;
                    }
                    if (!isset($sections[$secName][$label])) {
                        $extraFields[$secName][$label] = $value;
                    }
                }
            }
        }
    } else {
        $saved = mergeTemplateWithSaved($sections, []);
    }

	if (function_exists("rkrk_assess_migrate_fields_legacy_pilot_ids")) {
		rkrk_assess_migrate_fields_legacy_pilot_ids($saved);
	}
	if (function_exists("rkrk_assess_personel_upsert_from_url_params")) {
		rkrk_assess_personel_upsert_from_url_params($_GET, $sections);
	}

	$GLOBALS["rkrkAssessPersPickExplicit"] = rkrk_assess_pers_pick_explicit_on();
	$GLOBALS["rkrkAssessPersPickOptions"] = !empty($GLOBALS["rkrkAssessPersPickExplicit"]) ? rkrk_assess_personel_flat_for_select() : array();
	$GLOBALS["rkrkAssessPersPickInstrPrueferOptions"] = !empty($GLOBALS["rkrkAssessPersPickExplicit"]) ? rkrk_assess_personel_flat_for_instruktor_pruefer_select() : array();
	if (!empty($GLOBALS["rkrkAssessPersPickExplicit"]) && !empty($GLOBALS["rkrkAssessPersPickOptions"])) {
		$pidGet = rkrk_assess_request_pilot_id_param();
		if ($pidGet !== "") {
			$hit = rkrk_assess_personel_find_by_pilot_id($pidGet);
			if ($hit !== null && function_exists("rkrk_assess_pers_pick_apply_person_to_saved")) {
				rkrk_assess_pers_pick_apply_person_to_saved($saved, $sections, $hit);
			}
		}
	}
	if (!isset($saved["Allgemein"]) || !is_array($saved["Allgemein"])) {
		$saved["Allgemein"] = array();
	}
	$agUrl = &$saved["Allgemein"];
	if (function_exists("rkrk_assess_request_remote_pilot_id_param") && rkrk_assess_template_has_field($sections, "RemotePilotID")) {
		$rv = rkrk_assess_request_remote_pilot_id_param();
		if ($rv !== "" && trim((string)($agUrl["RemotePilotID"] ?? "")) === "") {
			$agUrl["RemotePilotID"] = $rv;
		}
	}
	if (function_exists("rkrk_assess_request_betreiber_id_param") && rkrk_assess_template_has_field($sections, "BetreiberID")) {
		$bv = rkrk_assess_request_betreiber_id_param();
		if ($bv !== "" && trim((string)($agUrl["BetreiberID"] ?? "")) === "") {
			$agUrl["BetreiberID"] = $bv;
		}
	}
	if (function_exists("rkrk_assess_request_pilot_id_param")) {
		$rk = rkrk_assess_request_pilot_id_param();
		if ($rk !== "" && trim((string)($agUrl[rkrk_assess_pers_pick_id_label()] ?? "")) === "") {
			$agUrl[rkrk_assess_pers_pick_id_label()] = $rk;
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

	if (isset($_GET["err"]) && (string)$_GET["err"] === "importjson") {
		echo "<div class='rkrk-card' style='border-color:#fca5a5;background:#fff5f5;margin-bottom:12px;'><strong>Import</strong>: Ungültige JSON-Datei — es wird ein Objekt mit Schlüssel <code>fields</code> (Objekt) erwartet.</div>";
	}
	if (isset($_GET["imported"])) {
		echo "<div class='rkrk-card success' style='margin-bottom:12px;'>✓ Import: Felder aus der JSON-Datei wurden übernommen.</div>";
	}
	rkrk_checklist_toolbar_echo_form_bar($file, $edit, $readonly);

    // Wetter direkt unter dem Titel
	// -------------------- WETTERBOX (nur wenn Flugzeit + Flugort im Template vorhanden) --------------------
	if ($showStartWeather) {
		$flugzeitEsc = safe(formatCHDatetime($flugzeit));
		$flugortEsc  = safe($flugort);
		$editJson = json_encode($editIdForWeather, JSON_UNESCAPED_UNICODE);

		echo <<<HTML
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
	if (!empty($GLOBALS["rkrkAssessPersPickExplicit"])) {
		echo "<input type='hidden' name='PersPick' value='on'>";
	}
	if (!empty($GLOBALS["rkrkAssessPersPickExplicit"]) && !empty($GLOBALS["rkrkAssessPersPickOptions"]) && function_exists("rkrk_assess_emit_perspick_datalist_once")) {
		rkrk_assess_emit_perspick_datalist_once($GLOBALS["rkrkAssessPersPickOptions"]);
	}
	if (!empty($GLOBALS["rkrkAssessPersPickExplicit"]) && !empty($GLOBALS["rkrkAssessPersPickInstrPrueferOptions"]) && function_exists("rkrk_assess_emit_perspick_instr_pruefer_datalist_once")) {
		rkrk_assess_emit_perspick_instr_pruefer_datalist_once($GLOBALS["rkrkAssessPersPickInstrPrueferOptions"]);
	}
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
						$itemDisplay = preg_replace('/\s*\(([rogn])\)\s*$/iu', '', (string)$item);
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
        echo "<div class='system-info-bottom' style='display:flex;align-items:center;gap:6px;'><span style='display:inline-flex;' aria-hidden='true'>" . rkrk_checklist_toolbar_svg_edit() . "</span><span>Zuletzt bearbeitet am: " . safe($modifiedAt) . "</span></div>";
    }

    if ($file !== "") {
        echo "<div style='text-align:center;margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;align-items:center;'>";
        if (function_exists("rkrk_checklist_toolbar_paperform_link")) {
            echo "<a href='" . htmlspecialchars(rkrk_checklist_toolbar_paperform_link($file, $edit, "form"), ENT_QUOTES, "UTF-8") . "' class='chk-act' target='_blank' rel='noopener' title='Leeres Papierformular' aria-label='Papierformular'>📄</a>";
        }
        if ($edit) {
            echo "<a href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=print&id=" . rawurlencode($edit)), ENT_QUOTES, "UTF-8") . "' class='chk-tbtn' target='_blank' rel='noopener' title='Druckansicht in neuem Fenster'>" . rkrk_checklist_toolbar_svg_print() . "<span>Drucken</span></a>";
            $__repBottom = function_exists("rkrk_checklist_toolbar_list_report_link")
                ? rkrk_checklist_toolbar_list_report_link((string)$edit, "assess")
                : "";
            if ($__repBottom !== "") {
                echo "<a href='" . htmlspecialchars($__repBottom, ENT_QUOTES, "UTF-8") . "' class='chk-tbtn' target='_blank' rel='noopener' title='PDF-Abschlussbericht'>" . rkrk_checklist_toolbar_svg_pdf() . "<span>PDF-Report</span></a>";
            }
        }
        echo "<a href='".safe(rkrk_assess_append_setting_href("?mode=listall"))."' class='btn' title='Zurück zur Übersicht'>Zurück zu den Assessments</a>";
        echo "</div>";
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
					<option value='hidden'>Hidden (Text, nur JSON)</option>
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
	if (function_exists("rkrk_assess_checklist_progress_emit_form_script")) {
		rkrk_assess_checklist_progress_emit_form_script(array(
			"kind" => "assess",
			"file" => (string) $file,
			"edit" => (string) ($edit ?? ""),
			"readonly" => (bool) $readonly,
		));
	}
    pageFooterLocal();
}

/* -----------------------------------------------------------
   CHECKLISTE SPEICHERN
----------------------------------------------------------- */
function saveChecklist(){
    global $rkrkAssessResults, $rkrkAssessTemplates;

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
	if (function_exists("rkrk_assess_save_normalize_perspick_fields")) {
		rkrk_assess_save_normalize_perspick_fields($fields);
	}
	if (function_exists("rkrk_assess_migrate_fields_legacy_pilot_ids")) {
		rkrk_assess_migrate_fields_legacy_pilot_ids($fields);
	}
	if (function_exists("rkrk_assess_personel_upsert_from_url_params")) {
		$tplUpsert = loadTemplate($file);
		$secUpsert = is_array($tplUpsert) && isset($tplUpsert["sections"]) ? $tplUpsert["sections"] : array();
		$upsertParams = array();
		if (isset($fields["Allgemein"]) && is_array($fields["Allgemein"])) {
			foreach ($fields["Allgemein"] as $fk => $fv) {
				if (is_scalar($fv)) {
					$upsertParams[(string)$fk] = (string)$fv;
				}
			}
		}
		rkrk_assess_personel_upsert_from_url_params($upsertParams, $secUpsert);
	}

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
        $path = "$rkrkAssessResults/$id.json";
        $existing = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $createdAt = is_array($existing) && isset($existing['created_at']) ? (string) $existing['created_at'] : date('c');
    } else {

        $createdAt = date('c');

        $tplSlug = function_exists("rkrk_assess_checklist_title_slug")
            ? rkrk_assess_checklist_title_slug($file, (string) $rkrkAssessTemplates)
            : slugifyTitle(pathinfo($file, PATHINFO_FILENAME));
        $id = function_exists("rkrk_assess_build_checklist_result_json_stem")
            ? rkrk_assess_build_checklist_result_json_stem($tplSlug, $fields, $createdAt)
            : $tplSlug;
        if (function_exists("rkrk_assess_unique_checklist_result_stem")) {
            $id = rkrk_assess_unique_checklist_result_stem((string) $rkrkAssessResults, $id);
        }
        $path = "$rkrkAssessResults/$id.json";
    }

    /* -----------------------------------------
       6) JSON SCHREIBEN
    ----------------------------------------- */
    $data = [
        'format'      => 'rkropen_checklist_result_v1',
        'result_stem' => $id,
        'template'    => $file,
        'fields'      => $fields,
        'created_at'  => $createdAt,
        'updated_at'  => date('c'),
    ];

	if ($editId && function_exists("rkrk_assess_build_checklist_result_json_stem")) {
		$tplSlug = function_exists("rkrk_assess_checklist_title_slug")
			? rkrk_assess_checklist_title_slug($file, (string) $rkrkAssessTemplates)
			: slugifyTitle(pathinfo($file, PATHINFO_FILENAME));
		$ymdSrc = function_exists("rkrk_assess_checklist_completion_stamp")
			? rkrk_assess_checklist_completion_stamp($id, $createdAt)
			: $createdAt;
		$newStem = rkrk_assess_build_checklist_result_json_stem($tplSlug, $fields, $ymdSrc);
		if (function_exists("rkrk_assess_unique_checklist_result_stem")) {
			$newStem = rkrk_assess_unique_checklist_result_stem((string) $rkrkAssessResults, $newStem, is_file($path) ? $path : null);
		}
		if ($newStem !== "" && $newStem !== $id) {
			$oldStem = $id;
			$newPath = "$rkrkAssessResults/$newStem.json";
			if ($newPath !== $path) {
				if (is_file($newPath) && realpath($newPath) !== realpath($path)) {
					@unlink($newPath);
				}
				if (is_file($path)) {
					@rename($path, $newPath);
				}
				$path = $newPath;
				$id = $newStem;
				$data['result_stem'] = $id;
				if (function_exists("rkrk_assess_rename_stem_sidecars") && function_exists("rkrk_assess_reports_dir")) {
					$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
					$GLOBALS["rkrk_assess_reports_kind"] = "assess";
					$repDir = rkrk_assess_reports_dir();
					if ($prevKind === null) {
						unset($GLOBALS["rkrk_assess_reports_kind"]);
					} else {
						$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
					}
					if ($repDir !== "") {
						rkrk_assess_rename_stem_sidecars($repDir, $oldStem, $newStem);
					}
				}
			}
		}
	}

	if (function_exists("rkrk_assess_checklist_fields_are_empty") && rkrk_assess_checklist_fields_are_empty($fields)) {
		rkrk_redirect(rkrk_assess_append_setting_href("?mode=list"));
	}

    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

	if (function_exists("rkrk_assess_checklist_progress_delete")) {
		$__draft = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($_POST["checklist_draft_id"] ?? ""));
		rkrk_assess_checklist_progress_delete(array(
			"kind" => "assess",
			"file" => $file,
			"edit" => (string) ($editId ? basename((string) $editId) : ""),
			"draft_id" => $__draft,
		));
		if ($editId) {
			rkrk_assess_checklist_progress_delete(array(
				"kind" => "assess",
				"file" => $file,
				"edit" => basename((string) $editId),
				"draft_id" => "",
			));
		}
	}

	if (function_exists("rkrk_assess_save_checklist_print_report_from_path")) {
		rkrk_assess_save_checklist_print_report_from_path($path, function () use ($id) {
			$prev = $_GET["id"] ?? null;
			$_GET["id"] = $id;
			$GLOBALS["rkrk_assess_print_no_exit"] = true;
			$GLOBALS["rkrk_assess_print_compact"] = true;
			$GLOBALS["rkrk_assess_print_hide_unrated"] = true;
			$_GET["hide_ablauf"] = "1";
			$_GET["hide_printtype"] = "1";
			ob_start();
			showChecklistPrint();
			$html = (string) ob_get_clean();
			unset($GLOBALS["rkrk_assess_print_no_exit"], $GLOBALS["rkrk_assess_print_compact"], $GLOBALS["rkrk_assess_print_hide_unrated"]);
			if ($prev === null) {
				unset($_GET["id"]);
			} else {
				$_GET["id"] = $prev;
			}
			return $html;
		});
	}

    rkrk_redirect("?mode=list&saved=1");
}


/* -----------------------------------------------------------
   CHECKLISTE LÖSCHEN
----------------------------------------------------------- */
function deleteJsonChecklist(){
    global $rkrkAssessResults, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge löschen.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    $path = "$rkrkAssessResults/$id.json";

    if (file_exists($path)) unlink($path);

    rkrk_redirect("?mode=list&deleted=1");
}

/* -----------------------------------------------------------
 TEMPLATE FIELDS API (für LinkBuilder Dropdown)
----------------------------------------------------------- */
function templateFieldsApi(){
    global $rkrkAssessTemplates;

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
 LINK BUILDER (Assessment-URL Generator)
----------------------------------------------------------- */
function showLinkBuilder(){
    global $rkrkAssessTemplates;

    pageHeaderLocal("Assessment‑URL Generator");

    // Basis-URL (rkrk_checklist.php)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $selfUrl = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    /* -------------------------------------------------------
       SERVERSEITIG: Template -> Sections -> Labels Map bauen
       (ohne fetch/API, sofort verfügbar)
    ------------------------------------------------------- */
    $fieldMap = [];
    foreach (glob($rkrkAssessTemplates . "/*.json") as $tplFile){
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
    foreach (glob($rkrkAssessTemplates . "/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = isset($tpl['title']) ? $tpl['title'] : $base;
        $tplOptions .= "<option value=\"" . safe($base) . "\">" . safe($title) . "</option>\n";
    }

    echo <<<HTML
<div class='rkrk-card'>
  <h3>🔗 Assessment‑URL erzeugen</h3>
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
  <div style='margin-top:10px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;'>
    <button type='button' class='btn' id='lb_copy_btn' onclick='lb_copy()'>Kopieren</button>
    <span id='lb_copy_hint' style='font-size:13px;color:#48673A;'></span>
  </div>
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

    u += '&PersPick=on';

    // ✅ WICHTIG: HIER KEIN safe(), KEIN innerHTML
    document.getElementById('lb_result').value = u;
}


function lb_copy(){
    var inp = document.getElementById('lb_result');
    if (!inp) return;
    var val = (inp.value || '').replace(/&amp;/g, '&').trim();
    if (!val){ alert('Kein Link zum Kopieren — zuerst URL erzeugen.'); return; }
    var btn = document.getElementById('lb_copy_btn');
    var hint = document.getElementById('lb_copy_hint');
    function done(){
      if (btn){
        if (!btn.getAttribute('data-old-label')) btn.setAttribute('data-old-label', btn.textContent || 'Kopieren');
        btn.textContent = 'Kopiert!';
        setTimeout(function(){ btn.textContent = btn.getAttribute('data-old-label') || 'Kopieren'; }, 1600);
      }
      if (hint){ hint.textContent = 'In Zwischenablage kopiert.'; setTimeout(function(){ hint.textContent = ''; }, 2000); }
    }
    function fallback(){
      inp.value = val; inp.focus(); inp.select();
      try { document.execCommand('copy'); done(); } catch (e) { alert('Kopieren fehlgeschlagen.'); }
    }
    if (navigator.clipboard && navigator.clipboard.writeText){
        navigator.clipboard.writeText(val).then(done).catch(fallback);
    } else {
        fallback();
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

	echo "<div style='text-align:center;margin-top:20px;'><a href='" . safe(rkrk_assess_append_setting_href("?mode=listall")) . "' class='btn'>⬅ Zurück</a></div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
 LINK BUILDER — Quiz / Schulung (rkrk_digitalassess.php)
----------------------------------------------------------- */
function showDigitalQuizLinkBuilder(): void {
	global $folderoffset, $QuizLoc;

	if (!function_exists("csv_to_array_ascII")) {
		@include_once($folderoffset . "scripts/rkrk_csvfiles.php");
	}

	pageHeaderLocal("Quiz- & Schulungs-URL Generator");

	$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
	$host = isset($_SERVER["HTTP_HOST"]) ? (string) $_SERVER["HTTP_HOST"] : "";
	$self = isset($_SERVER["PHP_SELF"]) ? (string) $_SERVER["PHP_SELF"] : "";
	$dir = str_replace("\\", "/", dirname($self));
	$dgBaseUrl = $scheme . "://" . $host . rtrim($dir, "/") . "/rkrk_digitalassess.php";

	$quizMeta = array();
	$assessmentListeCsvPath = $QuizLoc . "AssessmentListe.csv";
	$isDglbAdmin = isset($_GET["ADMIN"]) || (isset($GLOBALS["onAdmin"]) && (bool)$GLOBALS["onAdmin"] === true);
	if (function_exists("csv_to_array_ascII") && is_readable($assessmentListeCsvPath)) {
		$__parse = csv_to_array_ascII($assessmentListeCsvPath, ";");
		$quizList = is_array($__parse) ? $__parse : array();
		foreach ($quizList as $__row) {
			if (!is_array($__row)) {
				continue;
			}
			if (!$isDglbAdmin && trim((string)($__row["Aktiv"] ?? "")) !== "Ja") {
				continue;
			}
			$qKey = trim((string) ($__row["Key"] ?? ""));
			if ($qKey === "") {
				continue;
			}
			$titel = trim((string) ($__row["Titel"] ?? ""));
			if ($titel === "") {
				$titel = $qKey;
			}
			$fragen = trim((string) ($__row["FragenDatei"] ?? ""));
			$lernd = trim((string) ($__row["LernDatei"] ?? ""));
			$hasLearn = ($lernd !== "" && is_readable($QuizLoc . $lernd));
			$locked = ((string) ($__row["Locked"] ?? "")) === "Ja";

			$droneSet = array();
			if ($fragen !== "" && is_readable($QuizLoc . $fragen)) {
				$__qs = csv_to_array_ascII($QuizLoc . $fragen, ";");
				if (is_array($__qs)) {
					foreach ($__qs as $__q) {
						if (!is_array($__q)) {
							continue;
						}
						if (function_exists("rkrk_assess_question_is_quiz_live") && !rkrk_assess_question_is_quiz_live($__q)) {
							continue;
						}
						$dv = trim((string) ($__q["Drone"] ?? ""));
						if ($dv === "" || strcasecmp($dv, "Alle") === 0) {
							continue;
						}
						$droneSet[$dv] = true;
					}
				}
			}
			$drones = array_keys($droneSet);
			sort($drones, SORT_NATURAL | SORT_FLAG_CASE);
			$quizMeta[$qKey] = array(
				"titel" => $titel,
				"hasLearn" => $hasLearn,
				"locked" => $locked,
				"drones" => $drones,
			);
		}
		ksort($quizMeta, SORT_NATURAL | SORT_FLAG_CASE);
	}

	$carrySuffix = function_exists("rkrk_assess_carry_url_suffix")
		? rkrk_assess_carry_url_suffix()
		: "";

	$dgBaseUrlJson = json_encode($dgBaseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$quizMetaJson = json_encode(
		$quizMeta,
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	$carrySuffixJson = json_encode($carrySuffix, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

	$dglbNamesDl = "";
	if (function_exists("rkrk_assess_emit_digitalassess_name_datalist_html")) {
		$dglbNamesDl = rkrk_assess_emit_digitalassess_name_datalist_html();
	}
	$dglbPilotMapJson = function_exists("rkrk_assess_pers_pick_name_to_pilot_id_map_json")
		? rkrk_assess_pers_pick_name_to_pilot_id_map_json()
		: "null";

	$optHtml = "<option value=''>— Quiz wählen —</option>\n";
	foreach ($quizMeta as $k => $m) {
		$lab = safe($k . " — " . ($m["titel"] ?? $k));
		if (!empty($m["locked"])) {
			$lab .= " (gesperrt)";
		}
		$optHtml .= "<option value=\"" . safe($k) . "\">" . $lab . "</option>\n";
	}

	echo "<div class='rkrk-card'>";
	echo "<h3 style='margin-top:0;'>Quiz- / Schulungs-Link erzeugen</h3>";
	echo "<p style='color:#555;margin-top:6px;line-height:1.45;'>Ziel: <code>" . safe($dgBaseUrl) . "</code>. Parameter entsprechen den Links auf der Quiz-Übersicht (u. a. <code>quiz</code>, <code>Schulung</code>, <code>QuizModus=quiz</code>, <code>read_only=1</code>).</p>";

	if (count($quizMeta) === 0) {
		echo "<p style='color:#b45309;'><strong>Hinweis:</strong> Keine Einträge aus <code>AssessmentListe.csv</code> geladen. Prüfen Sie Pfad <code>" . safe($QuizLoc) . "</code> und Dateizugriff.</p>";
	}

	echo "<div class='field'><label class='field-label' for='dglb_quiz'>Quiz</label>";
	echo "<select id='dglb_quiz' class='text-input' style='width:100%;'>" . $optHtml . "</select></div>";

	echo "<div class='field'><label class='field-label' for='dglb_drone'>Drohne</label>";
	echo "<select id='dglb_drone' class='text-input' style='width:100%;'><option value=''>— keine Vorgabe —</option></select>";
	echo "<input type='text' id='dglb_drone_free' class='text-input' style='width:100%;margin-top:6px;display:none;' placeholder='Freitext (Schreibweise wie in der Fragen-CSV)'>";
	echo "<label style='display:block;margin-top:6px;'><input type='checkbox' id='dglb_drone_manual'> Drohne manuell eintragen</label></div>";

	if ($dglbNamesDl !== "") {
		echo $dglbNamesDl;
	}
	$dglbNameExtra = ($dglbNamesDl !== "")
		? " list=\"rkrk-dga-quiz-names\" autocomplete=\"off\""
		: "";
	$dglbNamePh = "Aus Pilotenliste wählen oder manuell eintragen …";

	echo "<div class='field'><label class='field-label' for='dglb_name'>Name / Teilnehmer (optional)</label>";
	echo "<input type='text' id='dglb_name' class='text-input' style='width:100%;' placeholder=\"" . htmlspecialchars($dglbNamePh, ENT_QUOTES, "UTF-8") . "\"$dglbNameExtra></div>";

	echo "<div class='field'><label class='field-label' for='dglb_pilotid'>RKRS Piloten-ID (optional)</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Wird automatisch ausgefüllt, wenn der <strong>exakte</strong> Anzeigename aus der Pilotenliste gewählt oder identisch eingegeben wird. Sonst leer lassen oder manuell eintragen.</small>";
	echo "<input type='text' id='dglb_pilotid' class='text-input' style='width:100%;' placeholder='z. B. aus Liste gesetzt oder manuell'></div>";

	echo "<h4 style='margin-top:16px;'>Optionen</h4>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_schulung'> Schulung (Parameter <code>Schulung</code> — Lerninhalt, falls vorhanden)</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_status_quiz'> Direkt ins Quiz (<code>QuizModus=quiz</code>)</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_sortiert'> Feste Reihenfolge (<code>sortiert</code>)</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_ohne_lerntext'> Ohne Schulungstext (<code>ohne_lerntext=1</code>)</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_ohne_bilder'> Ohne Fragebilder (<code>ohne_bilder=1</code>)</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_readonly'> Lesemodus / Übersicht (<code>read_only=1</code>)</label>";
	echo "<div class='field' style='margin-top:8px;'><label class='field-label' for='dglb_perspick'>PersPick</label>";
	echo "<select id='dglb_perspick' class='text-input' style='width:100%;max-width:280px;'>";
	echo "<option value='on' selected>on (Namensliste aus Pilotenliste)</option>";
	echo "<option value='off'>off</option>";
	echo "<option value=''>(nicht setzen)</option>";
	echo "</select></div>";
	$dglbCarryKeys = function_exists("rkrk_assess_carry_query_keys") ? rkrk_assess_carry_query_keys() : array();
	$dglbAdminChk = in_array("ADMIN", $dglbCarryKeys, true) ? " checked" : "";
	$dglbInstrChk = in_array("INSTR", $dglbCarryKeys, true) ? " checked" : "";
	echo "<label style='display:block;margin-top:10px;'><input type='checkbox' id='dglb_admin'" . $dglbAdminChk . "> <code>ADMIN</code> anhängen</label>";
	echo "<label style='display:block;margin-bottom:6px;'><input type='checkbox' id='dglb_instr'" . $dglbInstrChk . "> <code>INSTR</code> anhängen</label>";

	echo "<div style='margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;'>";
	echo "<button type='button' class='btn btn-primary' onclick='dglb_generate()'>URL erzeugen</button>";
	echo "</div>";

	echo "<h4 style='margin-top:18px;'>Ergebnis</h4>";
	echo "<input type='text' id='dglb_result' class='text-input' readonly style='width:100%;'>";
	echo "<div style='margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;'>";
	echo "<button type='button' class='btn' id='dglb_copy_btn' onclick='dglb_copy()'>Kopieren</button>";
	echo "<span id='dglb_copy_hint' style='font-size:13px;color:#48673A;'></span>";
	echo "</div>";

	echo "</div>";

	echo "<div style='text-align:center;margin-top:20px;'>";
	echo "<a href='" . safe(rkrk_assess_append_setting_href("?mode=list")) . "' class='btn'>Zurück zur Liste</a>";
	echo "</div>";

	echo "<script>\n";
	echo "var DGLB_BASE = " . $dgBaseUrlJson . ";\n";
	echo "var DGLB_QUIZ_META = " . $quizMetaJson . ";\n";
	echo "var DGLB_CARRY = " . $carrySuffixJson . ";\n";
	echo "var DGLB_PILOT_MAP = " . $dglbPilotMapJson . ";\n";
	echo <<<'DGLB_JS'
function dglb_sync_pilotid_from_name(){
  var nm = document.getElementById('dglb_name');
  var pid = document.getElementById('dglb_pilotid');
  if (!nm || !pid) return;
  if (!DGLB_PILOT_MAP || typeof DGLB_PILOT_MAP !== 'object') return;
  var v = (nm.value || '').trim();
  var id = DGLB_PILOT_MAP[v];
  pid.value = (id != null && id !== '') ? String(id) : '';
}

function dglb_toggle_drone_manual(){
  var man = document.getElementById('dglb_drone_manual');
  var sel = document.getElementById('dglb_drone');
  var free = document.getElementById('dglb_drone_free');
  if (!man || !sel || !free) return;
  var on = man.checked;
  sel.style.display = on ? 'none' : '';
  free.style.display = on ? '' : 'none';
}

function dglb_refill_drones(){
  var quizEl = document.getElementById('dglb_quiz');
  var droneSel = document.getElementById('dglb_drone');
  if (!quizEl || !droneSel) return;
  var key = quizEl.value || '';
  var meta = (DGLB_QUIZ_META && DGLB_QUIZ_META[key]) ? DGLB_QUIZ_META[key] : null;
  var drones = (meta && meta.drones) ? meta.drones : [];
  var prev = droneSel.value;
  droneSel.innerHTML = '';
  var o0 = document.createElement('option');
  o0.value = '';
  o0.textContent = '— keine Vorgabe —';
  droneSel.appendChild(o0);
  for (var i = 0; i < drones.length; i++){
    var o = document.createElement('option');
    o.value = drones[i];
    o.textContent = drones[i];
    droneSel.appendChild(o);
  }
  if (prev && Array.prototype.indexOf.call(drones, prev) >= 0){
    droneSel.value = prev;
  }
}

function dglb_generate(){
  dglb_sync_pilotid_from_name();
  var quizEl = document.getElementById('dglb_quiz');
  var key = quizEl ? (quizEl.value || '').trim() : '';
  if (!key){
    alert('Bitte ein Quiz wählen.');
    return;
  }
  var u = DGLB_BASE + '?quiz=' + encodeURIComponent(key);

  if (document.getElementById('dglb_schulung') && document.getElementById('dglb_schulung').checked){
    u += '&Schulung';
  }
  if (document.getElementById('dglb_status_quiz') && document.getElementById('dglb_status_quiz').checked){
    u += '&QuizModus=quiz';
  }
  if (document.getElementById('dglb_sortiert') && document.getElementById('dglb_sortiert').checked){
    u += '&sortiert';
  }
  if (document.getElementById('dglb_ohne_lerntext') && document.getElementById('dglb_ohne_lerntext').checked){
    u += '&ohne_lerntext=1';
  }
  if (document.getElementById('dglb_ohne_bilder') && document.getElementById('dglb_ohne_bilder').checked){
    u += '&ohne_bilder=1';
  }
  if (document.getElementById('dglb_readonly') && document.getElementById('dglb_readonly').checked){
    u += '&read_only=1';
  }

  var ppEl = document.getElementById('dglb_perspick');
  var pp = ppEl ? (ppEl.value || '').trim() : '';
  if (pp !== ''){
    u += '&PersPick=' + encodeURIComponent(pp);
  }

  var nm = document.getElementById('dglb_name');
  var nv = nm ? (nm.value || '').trim() : '';
  if (nv !== ''){
    u += '&Name=' + encodeURIComponent(nv);
  }
  var pid = document.getElementById('dglb_pilotid');
  var pv = pid ? (pid.value || '').trim() : '';
  if (pv !== ''){
    u += '&RKRSPilotID=' + encodeURIComponent(pv);
  }

  var droneVal = '';
  if (document.getElementById('dglb_drone_manual') && document.getElementById('dglb_drone_manual').checked){
    var fr = document.getElementById('dglb_drone_free');
    droneVal = fr ? (fr.value || '').trim() : '';
  } else {
    var ds = document.getElementById('dglb_drone');
    droneVal = ds ? (ds.value || '').trim() : '';
  }
  if (droneVal !== ''){
    u += '&Drone=' + encodeURIComponent(droneVal);
  }

  if (document.getElementById('dglb_admin') && document.getElementById('dglb_admin').checked){
    u += '&ADMIN';
  }
  if (document.getElementById('dglb_instr') && document.getElementById('dglb_instr').checked){
    u += '&INSTR';
  }

  var carry = DGLB_CARRY || '';
  if (carry){
    if (pp !== ''){
      carry = carry.replace(/&PersPick=[^&]*/g, '');
    }
    if (document.getElementById('dglb_admin') && document.getElementById('dglb_admin').checked){
      carry = carry.replace(/&ADMIN\b/g, '');
    }
    if (document.getElementById('dglb_instr') && document.getElementById('dglb_instr').checked){
      carry = carry.replace(/&INSTR\b/g, '');
    }
    u += carry;
  }

  document.getElementById('dglb_result').value = u;
}

function dglb_copy(){
  var inp = document.getElementById('dglb_result');
  if (!inp) return;
  var val = (inp.value || '').replace(/&amp;/g, '&').trim();
  if (!val){ alert('Kein Link zum Kopieren — zuerst URL erzeugen.'); return; }
  var btn = document.getElementById('dglb_copy_btn');
  var hint = document.getElementById('dglb_copy_hint');
  function done(){
    if (btn){
      if (!btn.getAttribute('data-old-label')) btn.setAttribute('data-old-label', btn.textContent || 'Kopieren');
      btn.textContent = 'Kopiert!';
      setTimeout(function(){ btn.textContent = btn.getAttribute('data-old-label') || 'Kopieren'; }, 1600);
    }
    if (hint){ hint.textContent = 'In Zwischenablage kopiert.'; setTimeout(function(){ hint.textContent = ''; }, 2000); }
  }
  function fallback(){
    inp.value = val; inp.focus(); inp.select();
    try { document.execCommand('copy'); done(); } catch (e) { alert('Kopieren fehlgeschlagen.'); }
  }
  if (navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(val).then(done).catch(fallback);
  } else {
    fallback();
  }
}

var dglbQuizSel = document.getElementById('dglb_quiz');
if (dglbQuizSel){
  dglbQuizSel.addEventListener('change', dglb_refill_drones);
  dglbQuizSel.addEventListener('input', dglb_refill_drones);
}
var dglbMan = document.getElementById('dglb_drone_manual');
if (dglbMan){
  dglbMan.addEventListener('change', dglb_toggle_drone_manual);
}
var dglbNm = document.getElementById('dglb_name');
if (dglbNm){
  dglbNm.addEventListener('input', dglb_sync_pilotid_from_name);
  dglbNm.addEventListener('change', dglb_sync_pilotid_from_name);
}
dglb_refill_drones();
DGLB_JS;
	echo "\n</script>\n";

	pageFooterLocal();
}



/* -----------------------------------------------------------
 EINSATZPRÜFUNG LINK BUILDER — Material, Praxis, Theorie + cs
----------------------------------------------------------- */
function eplb_prefs_defaults(): array {
	return array(
		"material_template" => "einsatzpruefung_rkrs_materialliste.json",
		"praxis_template" => "einsatzpruefung_rkrs.json",
		"theorie_quiz" => "RKRSp-EP-preuf",
	);
}

function eplb_prefs_path(): string {
	global $rkrkAssessTemplates;
	$tpl = isset($rkrkAssessTemplates) ? (string)$rkrkAssessTemplates : "";
	if ($tpl === "") {
		return __DIR__ . DIRECTORY_SEPARATOR . "einsatzpruefung_linkbuilder.json";
	}
	$root = dirname(rtrim(str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $tpl), DIRECTORY_SEPARATOR));
	return $root . DIRECTORY_SEPARATOR . "einsatzpruefung_linkbuilder.json";
}

/**
 * @return array{material_template:string,praxis_template:string,theorie_quiz:string}
 */
function eplb_prefs_load(): array {
	$out = eplb_prefs_defaults();
	$path = eplb_prefs_path();
	if (!is_file($path)) {
		return $out;
	}
	$raw = @file_get_contents($path);
	if ($raw === false || trim((string)$raw) === "") {
		return $out;
	}
	$data = @json_decode((string)$raw, true);
	if (!is_array($data)) {
		return $out;
	}
	foreach (array("material_template", "praxis_template", "theorie_quiz") as $k) {
		if (isset($data[$k]) && is_string($data[$k]) && trim($data[$k]) !== "") {
			$out[$k] = basename(str_replace("\\", "/", trim($data[$k])));
		}
	}
	return $out;
}

/**
 * @param array<string,mixed> $in
 */
function eplb_prefs_save(array $in): bool {
	$cur = eplb_prefs_defaults();
	foreach (array("material_template", "praxis_template", "theorie_quiz") as $k) {
		if (isset($in[$k]) && is_string($in[$k]) && trim($in[$k]) !== "") {
			$cur[$k] = basename(str_replace("\\", "/", trim($in[$k])));
		}
	}
	$path = eplb_prefs_path();
	$dir = dirname($path);
	if ($dir !== "" && !is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}
	$payload = array(
		"version" => 1,
		"updated_at" => date("c"),
		"material_template" => $cur["material_template"],
		"praxis_template" => $cur["praxis_template"],
		"theorie_quiz" => $cur["theorie_quiz"],
	);
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if (!is_string($json) || $json === "") {
		return false;
	}
	$tmp = $path . ".tmp." . substr(hash("sha1", microtime(true) . "|" . $path), 0, 10);
	if (@file_put_contents($tmp, $json) === false) {
		@unlink($tmp);
		return false;
	}
	if (!@rename($tmp, $path)) {
		$ok = @copy($tmp, $path);
		@unlink($tmp);
		return (bool)$ok;
	}
	return true;
}

function einsatzpruefungLinkPrefsApi(): void {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header("Content-Type: application/json; charset=utf-8");
	$method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));
	if ($method === "POST") {
		$src = $_POST;
		$raw = file_get_contents("php://input");
		if (is_string($raw) && trim($raw) !== "") {
			$j = @json_decode($raw, true);
			if (is_array($j)) {
				$src = $j;
			}
		}
		$ok = eplb_prefs_save(is_array($src) ? $src : array());
		echo json_encode(array(
			"ok" => $ok,
			"prefs" => eplb_prefs_load(),
		), JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo json_encode(array(
		"ok" => true,
		"prefs" => eplb_prefs_load(),
	), JSON_UNESCAPED_UNICODE);
	exit;
}

function einsatzpruefungLinkCsApi(): void {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header("Content-Type: application/json; charset=utf-8");

	// Params aus GET und optional POST-JSON/POST (POST überschreibt).
	// Quiz bevorzugt als quiz_key — GET quiz=* würde auf dem Portal das Hash-Gate auslösen.
	$src = is_array($_GET) ? $_GET : array();
	$method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));
	if ($method === "POST") {
		$raw = file_get_contents("php://input");
		if (is_string($raw) && trim($raw) !== "") {
			$j = @json_decode($raw, true);
			if (is_array($j)) {
				$src = array_merge($src, $j);
			}
		} elseif (!empty($_POST) && is_array($_POST)) {
			$src = array_merge($src, $_POST);
		}
	}

	$quiz = trim((string)($src["quiz_key"] ?? ""));
	if ($quiz === "") {
		$quiz = trim((string)($src["quiz"] ?? ""));
	}
	if ($quiz === "") {
		$quiz = "RKRSp-EP-preuf";
	}

	// Hash-Felder nur setzen, wenn im Request vorhanden — der Payload-Builder
	// lässt fehlende Parameter weg (muss der URL des Link-Generators entsprechen).
	$params = array(
		"quiz" => $quiz,
		"Name" => trim((string)($src["Name"] ?? $src["Pilot"] ?? "")),
		"Drone" => trim((string)($src["Drone"] ?? "")),
	);
	if (array_key_exists("QuizModus", $src)) {
		$params["QuizModus"] = trim((string)$src["QuizModus"]);
	} elseif ($quiz !== "RKRSp-doku") {
		$params["QuizModus"] = "pruefung";
	}
	if (array_key_exists("EXAM", $src)) {
		$params["EXAM"] = trim((string)$src["EXAM"]);
	}
	if (array_key_exists("Schulung", $src)) {
		$params["Schulung"] = (string)$src["Schulung"];
	}
	$pilotIdVal = trim((string)($src["RKRSPilotID"] ?? $src["PilotID"] ?? ""));
	if ($pilotIdVal !== "") {
		$params["RKRSPilotID"] = $pilotIdVal;
		$params["PilotID"] = $pilotIdVal;
	}
	if (array_key_exists("ohne_bilder", $src)) {
		$params["ohne_bilder"] = (string)$src["ohne_bilder"];
	}
	if (array_key_exists("Status", $src)) {
		$params["Status"] = (string)$src["Status"];
	}
	$assessmentOk = class_exists("assessment") && is_callable(array("assessment", "entry"));
	$build = function_exists("rkrk_assess_hash_build_cs_for_quiz")
		? rkrk_assess_hash_build_cs_for_quiz($quiz, $params)
		: array("payload" => "", "cs" => "", "required_fields" => array());
	$required = isset($build["required_fields"]) && is_array($build["required_fields"])
		? $build["required_fields"]
		: array();
	$gated = count($required) > 0;

	// Bei geschützten Quiz: Pflichtfelder (außer optionalem Schulung/PilotID) müssen befüllt sein.
	$missingField = null;
	if ($gated) {
		foreach ($required as $field) {
			$key = (string)$field;
			if ($key === "" || strcasecmp($key, "Schulung") === 0 || strcasecmp($key, "PilotID") === 0) {
				continue;
			}
			$val = ($key === "quiz") ? $quiz : trim((string)($params[$key] ?? ""));
			if ($val === "") {
				$missingField = $key;
				break;
			}
		}
	}
	if ($missingField !== null) {
		echo json_encode(array(
			"ok" => false,
			"gated" => true,
			"payload" => "",
			"cs" => "",
			"required_fields" => $required,
			"missing_field" => $missingField,
			"assessment_ok" => $assessmentOk,
		), JSON_UNESCAPED_UNICODE);
		exit;
	}

	$cs = (string)($build["cs"] ?? "");
	echo json_encode(array(
		"ok" => !$gated || $cs !== "",
		"gated" => $gated,
		"payload" => $build["payload"] ?? "",
		"cs" => $cs,
		"required_fields" => $required,
		"missing_field" => null,
		"assessment_ok" => $assessmentOk,
	), JSON_UNESCAPED_UNICODE);
	exit;
}

function showEinsatzpruefungLinkBuilder(): void {
	global $folderoffset, $rkrkAssessTemplates, $QuizLoc;

	if (!function_exists("csv_to_array_ascII")) {
		@include_once($folderoffset . "scripts/rkrk_csvfiles.php");
	}

	pageHeaderLocal("Einsatzprüfung — Link-Generator");

	$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
	$host = isset($_SERVER["HTTP_HOST"]) ? (string)$_SERVER["HTTP_HOST"] : "";
	$self = isset($_SERVER["PHP_SELF"]) ? (string)$_SERVER["PHP_SELF"] : "";
	$dir = str_replace("\\", "/", dirname($self));
	$assessBase = $scheme . "://" . $host . rtrim($dir, "/") . "/rkrk_assess.php";
	$quizBase = $scheme . "://" . $host . rtrim($dir, "/") . "/rkrk_digitalassess.php";
	$appRoot = rtrim(str_replace("\\", "/", dirname(dirname(rtrim($dir, "/")))), "/");
	$zieleBase = $scheme . "://" . $host . $appRoot . "/data/rkrsplus_ep/" . rawurlencode("RKRS Ziele.php");
	$carrySuffix = function_exists("rkrk_assess_carry_url_suffix") ? rkrk_assess_carry_url_suffix() : "";
	// Generierte Einsatzprüfungs-Links: Kanal (RKRS / RKRSplus / RKRSprev) nicht mitgeben —
	// Portal erkennt den Kontext über Host/Pfad; Audienz-Familie deckt das ab.
	if ($carrySuffix !== "") {
		$carrySuffix = preg_replace('/(?:^|&)(?:RKRS|RKRSplus|RKRSprev)(?:=[^&]*)?/', "", $carrySuffix);
		$carrySuffix = ltrim((string) $carrySuffix, "&");
		$carrySuffix = ($carrySuffix !== "") ? ("&" . $carrySuffix) : "";
	}
	$csApi = rkrk_assess_append_setting_href("?mode=einsatzpruefunglink_cs");
	$prefsApi = rkrk_assess_append_setting_href("?mode=einsatzpruefunglink_prefs");

	$prefs = eplb_prefs_load();

	$tplOptions = array();
	$tplDir = isset($rkrkAssessTemplates) ? (string)$rkrkAssessTemplates : "";
	if ($tplDir !== "" && is_dir($tplDir)) {
		$files = glob($tplDir . "/*.json");
		if (is_array($files)) {
			foreach ($files as $tplFile) {
				$base = basename((string)$tplFile);
				$tpl = function_exists("loadTemplate") ? loadTemplate($base) : null;
				$title = (is_array($tpl) && isset($tpl["title"]) && trim((string)$tpl["title"]) !== "")
					? trim((string)$tpl["title"])
					: $base;
				$tplOptions[$base] = $title;
			}
		}
	}
	uksort($tplOptions, "strnatcasecmp");
	foreach (array("material_template", "praxis_template") as $pk) {
		if (!isset($tplOptions[$prefs[$pk]]) && $prefs[$pk] !== "") {
			$tplOptions[$prefs[$pk]] = $prefs[$pk];
		}
	}

	$quizOptions = array();
	$assessmentListeCsvPath = (isset($QuizLoc) ? (string)$QuizLoc : "") . "AssessmentListe.csv";
	$isEplbAdmin = isset($_GET["ADMIN"]) || (isset($GLOBALS["onAdmin"]) && (bool)$GLOBALS["onAdmin"] === true);
	if (function_exists("csv_to_array_ascII") && is_readable($assessmentListeCsvPath)) {
		$__parse = csv_to_array_ascII($assessmentListeCsvPath, ";");
		$quizList = is_array($__parse) ? $__parse : array();
		foreach ($quizList as $__row) {
			if (!is_array($__row)) {
				continue;
			}
			if (!$isEplbAdmin && trim((string)($__row["Aktiv"] ?? "")) !== "Ja") {
				continue;
			}
			$qKey = trim((string)($__row["Key"] ?? ""));
			if ($qKey === "") {
				continue;
			}
			$titel = trim((string)($__row["Titel"] ?? ""));
			if ($titel === "") {
				$titel = $qKey;
			}
			$quizOptions[$qKey] = $titel;
		}
		ksort($quizOptions, SORT_NATURAL | SORT_FLAG_CASE);
	}
	if (!isset($quizOptions[$prefs["theorie_quiz"]])) {
		$quizOptions[$prefs["theorie_quiz"]] = $prefs["theorie_quiz"];
	}

	$dglbNamesDl = "";
	if (function_exists("rkrk_assess_emit_digitalassess_name_datalist_html")) {
		$dglbNamesDl = rkrk_assess_emit_digitalassess_name_datalist_html();
	}
	$pilotMapJson = function_exists("rkrk_assess_pers_pick_name_to_ids_map_json")
		? rkrk_assess_pers_pick_name_to_ids_map_json()
		: (function_exists("rkrk_assess_pers_pick_name_to_pilot_id_map_json")
			? rkrk_assess_pers_pick_name_to_pilot_id_map_json()
			: "{}");

	$assessBaseJson = json_encode($assessBase, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$quizBaseJson = json_encode($quizBase, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$zieleBaseJson = json_encode($zieleBase, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$carryJson = json_encode($carrySuffix, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$csApiJson = json_encode($csApi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$prefsApiJson = json_encode($prefsApi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$prefsJson = json_encode($prefs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	$pilotMapJs = $pilotMapJson;

	$optMat = "";
	$optPra = "";
	foreach ($tplOptions as $base => $title) {
		$lab = safe($title) . " (" . safe($base) . ")";
		$selM = ($base === $prefs["material_template"]) ? " selected" : "";
		$selP = ($base === $prefs["praxis_template"]) ? " selected" : "";
		$optMat .= "<option value=\"" . safe($base) . "\"" . $selM . ">" . $lab . "</option>\n";
		$optPra .= "<option value=\"" . safe($base) . "\"" . $selP . ">" . $lab . "</option>\n";
	}
	$optQuiz = "";
	foreach ($quizOptions as $k => $t) {
		$sel = ($k === $prefs["theorie_quiz"]) ? " selected" : "";
		$optQuiz .= "<option value=\"" . safe($k) . "\"" . $sel . ">" . safe($k . " — " . $t) . "</option>\n";
	}

	echo "<div class='rkrk-card' style='max-width:920px;margin:0 auto;'>";
	echo "<h2 style='margin-top:0;'>Einsatzprüfung — Links erzeugen</h2>";
	echo "<p style='color:#555;line-height:1.5;'>URL-Parameter sind der primäre Übergabeweg. Beim Öffnen werden Werte ins Formular übernommen und in <code>pilotenliste.json</code> gespeichert. <strong>RKRS- / Remote- / Betreiber-ID</strong>, <strong>Kontaktnummer</strong>, <strong>Koptertyp</strong> und <strong>Drohne</strong> werden aus der Pilotenliste nachgeschlagen, wenn der Name exakt passt und der Wert vorhanden ist.</p>";

	if ($dglbNamesDl !== "") {
		echo $dglbNamesDl;
	}
	$nameList = ($dglbNamesDl !== "") ? " list=\"rkrk-dga-quiz-names\" autocomplete=\"off\"" : "";

	echo "<div class='field'><label class='field-label' for='eplb_pilot'>Pilot / Name</label>";
	echo "<input type='text' id='eplb_pilot' class='text-input' style='width:100%;' placeholder='Vorname Nachname'" . $nameList . "></div>";

	echo "<div class='field'><label class='field-label' for='eplb_rkrsid'>RKRS Piloten ID</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Automatisch aus der Pilotenliste, wenn vorhanden.</small>";
	echo "<input type='text' id='eplb_rkrsid' class='text-input' style='width:100%;' placeholder='RKRSPilotID'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_remote'>Remote Pilot ID (EASA)</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Lookup aus der Pilotenliste (wenn gesetzt). Wird für die Materialprüfung in die URL übernommen.</small>";
	echo "<input type='text' id='eplb_remote' class='text-input' style='width:100%;' placeholder='nur Materialprüfung'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_betreiber'>Betreiber ID (EASA)</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Lookup aus der Pilotenliste (wenn gesetzt). Wird für die Materialprüfung in die URL übernommen.</small>";
	echo "<input type='text' id='eplb_betreiber' class='text-input' style='width:100%;' placeholder='nur Materialprüfung'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_kontakt'>Kontaktnummer</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Lookup aus der Pilotenliste (Telefon), wenn gesetzt.</small>";
	echo "<input type='text' id='eplb_kontakt' class='text-input' style='width:100%;'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_instr'>Instruktor</label>";
	echo "<input type='text' id='eplb_instr' class='text-input' style='width:100%;'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_kopter'>Koptertyp</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Lookup aus der Pilotenliste (Drohnentyp), wenn gesetzt.</small>";
	echo "<input type='text' id='eplb_kopter' class='text-input' style='width:100%;'></div>";

	echo "<div class='field'><label class='field-label' for='eplb_ort'>Prüfungsort</label>";
	echo "<input type='text' id='eplb_ort' class='text-input' style='width:100%;'></div>";

	echo "<hr style='margin:20px 0'>";

	echo "<h3>Vorlagen</h3>";
	echo "<p style='color:#64748b;font-size:13px;line-height:1.4;'>Template- und Quiz-Auswahl wird serverseitig in <code>einsatzpruefung_linkbuilder.json</code> gespeichert.</p>";
	echo "<div class='field'><label class='field-label' for='eplb_tpl_material'>Materialprüfung — Assessment-Template</label>";
	echo "<select id='eplb_tpl_material' class='text-input' style='width:100%;'>" . $optMat . "</select></div>";
	echo "<div class='field'><label class='field-label' for='eplb_tpl_praxis'>Praxisprüfung — Assessment-Template</label>";
	echo "<select id='eplb_tpl_praxis' class='text-input' style='width:100%;'>" . $optPra . "</select></div>";
	echo "<div class='field'><label class='field-label' for='eplb_quiz_theorie'>Theorieprüfung — Quiz</label>";
	echo "<select id='eplb_quiz_theorie' class='text-input' style='width:100%;'>" . $optQuiz . "</select></div>";
	echo "<div style='margin-top:8px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;'>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_savePrefs(this)'>Auswahl speichern</button>";
	echo "<span id='eplb_prefs_hint' style='font-size:13px;color:#48673A;'></span>";
	echo "</div>";

	echo "<hr style='margin:20px 0'>";

	echo "<h3>1. Materialprüfung</h3>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_genMaterial()'>Material-URL erzeugen</button>";
	echo "<input type='text' id='eplb_res_material' class='text-input' readonly style='width:100%;margin-top:8px;'>";
	echo "<div style='margin-top:8px;'><button type='button' class='btn' onclick=\"eplb_copy('eplb_res_material', this)\">Kopieren</button></div>";

	echo "<h3 style='margin-top:22px;'>2. Praxisprüfung (Flugteil)</h3>";
	echo "<p style='color:#64748b;font-size:13px;'>Ohne Remote/Betreiber-ID in der URL</p>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_genPraxis()'>Praxis-URL erzeugen</button>";
	echo "<input type='text' id='eplb_res_praxis' class='text-input' readonly style='width:100%;margin-top:8px;'>";
	echo "<div style='margin-top:8px;'><button type='button' class='btn' onclick=\"eplb_copy('eplb_res_praxis', this)\">Kopieren</button></div>";

	echo "<h3 style='margin-top:22px;'>3. Theorieprüfung</h3>";
	echo "<p style='color:#64748b;font-size:13px;'>Pflicht für die Theorie-URL: <strong>Name</strong> und <strong>Drohne</strong>. Optional inkl. <code>cs</code> für Portal, falls für das Quiz erforderlich.</p>";
	echo "<div class='field'><span class='field-label'>Modus</span>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_modus' id='eplb_theorie_pruefung' value='pruefung' checked> Prüfung (<code>QuizModus=pruefung</code>)</label>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_modus' id='eplb_theorie_schulung' value='schulung'> Schulung (<code>QuizModus=quiz</code> — kein Prüfungsmodus)</label>";
	echo "</div>";
	echo "<div class='field'><span class='field-label'>Bilder</span>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_bilder' id='eplb_theorie_mit_bilder' value='mit' checked> Mit Fragebildern</label>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_bilder' id='eplb_theorie_ohne_bilder' value='ohne'> Ohne Fragebilder (<code>ohne_bilder=1</code>)</label>";
	echo "</div>";
	echo "<div class='field'><span class='field-label'>Einstieg</span>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_lerntext' id='eplb_theorie_mit_lerntext' value='mit' checked> Mit Schulungstext (normaler Einstieg)</label>";
	echo "<label style='display:block;margin:6px 0;'><input type='radio' name='eplb_theorie_lerntext' id='eplb_theorie_ohne_lerntext' value='ohne'> Direkt zu den Fragen (<code>Status=Quiz</code> — Schulungstext überspringen)</label>";
	echo "</div>";
	echo "<div class='field'><label class='field-label' for='eplb_exam'>EXAM-Code</label>";
	echo "<input type='text' id='eplb_exam' class='text-input' style='width:100%;' placeholder='z. B. EP-PRUEF-2026-001'></div>";
	echo "<div class='field'><label class='field-label' for='eplb_drone'>Drohne</label>";
	echo "<small style=\"display:block;color:#64748b;margin-bottom:6px;line-height:1.35;\">Lookup aus der Pilotenliste (Drohnentyp), wenn gesetzt — für die Theorie-URL.</small>";
	echo "<input type='text' id='eplb_drone' class='text-input' style='width:100%;' placeholder='z. B. DJI Mavic 3TD'></div>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_genTheorie()'>Theorie-URL erzeugen</button>";
	echo "<input type='text' id='eplb_res_theorie' class='text-input' readonly style='width:100%;margin-top:8px;'>";
	echo "<div style='margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;'>";
	echo "<button type='button' class='btn' onclick=\"eplb_copy('eplb_res_theorie', this)\">Theorie-URL kopieren (inkl. cs)</button>";
	echo "</div>";
	echo "<div style='margin-top:8px;'><label class='field-label'>Payload</label><input type='text' id='eplb_res_payload' class='text-input' readonly style='width:100%;'></div>";
	echo "<div style='margin-top:8px;'><label class='field-label'>cs</label><input type='text' id='eplb_res_cs' class='text-input' readonly style='width:100%;'></div>";

	echo "<h3 style='margin-top:22px;'>4. Ziele (Dokumentation)</h3>";
	echo "<p style='color:#64748b;font-size:13px;'>Fester Quiz-Key <code>RKRSp-doku</code>. Pflicht: <strong>Name</strong> und <strong>Drohne</strong>. Optional <code>RKRSPilotID</code> und <code>cs</code> für das Portal.</p>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_genZiele()'>Ziele-URL erzeugen</button>";
	echo "<input type='text' id='eplb_res_ziele' class='text-input' readonly style='width:100%;margin-top:8px;'>";
	echo "<div style='margin-top:8px;'><button type='button' class='btn' onclick=\"eplb_copy('eplb_res_ziele', this)\">Ziele-URL kopieren (inkl. cs)</button></div>";

	echo "<div style='margin-top:18px;padding-top:14px;border-top:1px solid #e2e8f0;'>";
	echo "<button type='button' class='btn btn-primary' onclick='eplb_copyAll(this)'>Alle erzeugten Links kopieren</button>";
	echo "<span id='eplb_copy_hint' style='margin-left:10px;font-size:13px;color:#48673A;'></span>";
	echo "</div>";

	echo "<p style='margin-top:22px;'><a class='btn' href='" . safe(rkrk_assess_append_setting_href("?mode=urlmanagers&ADMIN")) . "'>Zurück URL-Verwaltung</a></p>";
	echo "</div>";

	echo "<script>\n";
	echo "var EPLB_ASSESS = " . $assessBaseJson . ";\n";
	echo "var EPLB_QUIZ = " . $quizBaseJson . ";\n";
	echo "var EPLB_ZIELE = " . $zieleBaseJson . ";\n";
	echo "var EPLB_CARRY = " . $carryJson . ";\n";
	echo "var EPLB_CS_API = " . $csApiJson . ";\n";
	echo "var EPLB_PREFS_API = " . $prefsApiJson . ";\n";
	echo "var EPLB_PREFS = " . $prefsJson . ";\n";
	echo "var EPLB_PILOT_MAP = " . $pilotMapJs . ";\n";
	echo <<<'EPLB_JS'
function eplb_val(id){ var el=document.getElementById(id); return el?(el.value||'').trim():''; }
function eplb_idsForName(nm){
  if(!EPLB_PILOT_MAP||typeof EPLB_PILOT_MAP!=='object')return null;
  var hit=EPLB_PILOT_MAP[nm];
  if(!hit)return null;
  if(typeof hit==='string')return {rkrs:hit,remote:'',betreiber:'',telefon:'',drohnentyp:''};
  if(typeof hit==='object')return {
    rkrs:(hit.rkrs!=null&&hit.rkrs!=='')?String(hit.rkrs):'',
    remote:(hit.remote!=null&&hit.remote!=='')?String(hit.remote):'',
    betreiber:(hit.betreiber!=null&&hit.betreiber!=='')?String(hit.betreiber):'',
    telefon:(hit.telefon!=null&&hit.telefon!=='')?String(hit.telefon):'',
    drohnentyp:(hit.drohnentyp!=null&&hit.drohnentyp!=='')?String(hit.drohnentyp):''
  };
  return null;
}
function eplb_syncRkrsFromName(opts){
  opts=opts||{};
  var onlyEmpty=!!opts.onlyEmpty;
  var nm=eplb_val('eplb_pilot');
  var ids=eplb_idsForName(nm);
  var pid=document.getElementById('eplb_rkrsid');
  var rem=document.getElementById('eplb_remote');
  var bet=document.getElementById('eplb_betreiber');
  var kn=document.getElementById('eplb_kontakt');
  var kop=document.getElementById('eplb_kopter');
  var drone=document.getElementById('eplb_drone');
  if(pid) pid.value=(ids&&ids.rkrs)?ids.rkrs:'';
  if(rem) rem.value=(ids&&ids.remote)?ids.remote:'';
  if(bet) bet.value=(ids&&ids.betreiber)?ids.betreiber:'';
  if(kn) kn.value=(ids&&ids.telefon)?ids.telefon:'';
  // Name-Lookup: Drohnentyp setzen. Beim Link-Erzeugen (onlyEmpty) manuelle Überschreibung behalten.
  if(kop){
    if(!onlyEmpty || !eplb_val('eplb_kopter')){
      kop.value=(ids&&ids.drohnentyp)?ids.drohnentyp:'';
    }
  }
  if(drone){
    if(!onlyEmpty || !eplb_val('eplb_drone')){
      drone.value=(ids&&ids.drohnentyp)?ids.drohnentyp:'';
    }
  }
}
function eplb_commonParams(material){
  var p=[];
  p.push('PersPick=on');
  var pilot=eplb_val('eplb_pilot'); if(pilot)p.push('Pilot='+encodeURIComponent(pilot));
  var rk=eplb_val('eplb_rkrsid'); if(rk)p.push('RKRSPilotID='+encodeURIComponent(rk));
  if(material){
    var rm=eplb_val('eplb_remote'); if(rm)p.push('RemotePilotID='+encodeURIComponent(rm));
    var bt=eplb_val('eplb_betreiber'); if(bt)p.push('BetreiberID='+encodeURIComponent(bt));
  }
  var kn=eplb_val('eplb_kontakt'); if(kn)p.push('Kontaktnummer='+encodeURIComponent(kn));
  var ins=eplb_val('eplb_instr'); if(ins)p.push('Instruktor='+encodeURIComponent(ins));
  var kop=eplb_val('eplb_kopter'); if(kop)p.push('Koptertyp='+encodeURIComponent(kop));
  var ort=eplb_val('eplb_ort'); if(ort)p.push('Prüfungsort='+encodeURIComponent(ort));
  return p;
}
function eplb_writeClipboard(val, btn){
  val=String(val||'').replace(/&amp;/g,'&').trim();
  // Admin-/Instruktor-Zugriff nie in weitergegebene Links übernehmen.
  val=val.split('\n').map(function(line){
    var hashPos=line.indexOf('#');
    var hash=hashPos>=0?line.slice(hashPos):'';
    var base=hashPos>=0?line.slice(0,hashPos):line;
    var queryPos=base.indexOf('?');
    if(queryPos<0)return line;
    var path=base.slice(0,queryPos);
    var query=base.slice(queryPos+1).split('&').filter(function(part){
      var key=part.split('=',1)[0].toUpperCase();
      return key!=='ADMIN'&&key!=='INSTR';
    });
    return path+(query.length?'?'+query.join('&'):'')+hash;
  }).join('\n');
  if(!val){ alert('Kein Link zum Kopieren — zuerst URL erzeugen.'); return; }
  function done(){
    if(!btn)return;
    var old=btn.getAttribute('data-old-label')||btn.textContent||'Kopieren';
    if(!btn.getAttribute('data-old-label')) btn.setAttribute('data-old-label', old);
    btn.textContent='Kopiert!';
    setTimeout(function(){ btn.textContent=btn.getAttribute('data-old-label')||'Kopieren'; }, 1600);
    var hint=document.getElementById('eplb_copy_hint');
    if(hint){ hint.textContent='In Zwischenablage kopiert.'; setTimeout(function(){ hint.textContent=''; }, 2000); }
  }
  if(navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(val).then(done).catch(function(){
      var ta=document.createElement('textarea'); ta.value=val; document.body.appendChild(ta); ta.select();
      try{ document.execCommand('copy'); done(); }catch(e){ alert('Kopieren fehlgeschlagen.'); }
      document.body.removeChild(ta);
    });
  } else {
    var ta=document.createElement('textarea'); ta.value=val; document.body.appendChild(ta); ta.select();
    try{ document.execCommand('copy'); done(); }catch(e){ alert('Kopieren fehlgeschlagen.'); }
    document.body.removeChild(ta);
  }
}
function eplb_copy(id, btn){
  var el=document.getElementById(id);
  eplb_writeClipboard(el?el.value:'', btn);
}
function eplb_copyAll(btn){
  var parts=[];
  ['eplb_res_material','eplb_res_praxis','eplb_res_theorie','eplb_res_ziele'].forEach(function(id){
    var el=document.getElementById(id);
    var v=el?(el.value||'').trim():'';
    if(v) parts.push(v);
  });
  if(!parts.length){ alert('Keine erzeugten Links vorhanden.'); return; }
  eplb_writeClipboard(parts.join('\n'), btn);
}
function eplb_collectPrefs(){
  return {
    material_template: eplb_val('eplb_tpl_material'),
    praxis_template: eplb_val('eplb_tpl_praxis'),
    theorie_quiz: eplb_val('eplb_quiz_theorie')
  };
}
function eplb_savePrefs(btn){
  var prefs=eplb_collectPrefs();
  if(!prefs.material_template||!prefs.praxis_template||!prefs.theorie_quiz){
    alert('Bitte Material-, Praxis-Template und Theorie-Quiz wählen.');
    return;
  }
  var hint=document.getElementById('eplb_prefs_hint');
  if(hint) hint.textContent='Speichern…';
  fetch(EPLB_PREFS_API,{
    method:'POST',
    credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(prefs)
  }).then(function(r){return r.json();}).then(function(j){
    if(!j||!j.ok){ if(hint)hint.textContent='Speichern fehlgeschlagen.'; alert('Auswahl konnte nicht gespeichert werden.'); return; }
    EPLB_PREFS=j.prefs||prefs;
    if(hint)hint.textContent='Gespeichert.';
    if(btn){
      var old=btn.getAttribute('data-old-label')||btn.textContent||'Auswahl speichern';
      if(!btn.getAttribute('data-old-label')) btn.setAttribute('data-old-label', old);
      btn.textContent='Gespeichert!';
      setTimeout(function(){ btn.textContent=btn.getAttribute('data-old-label')||'Auswahl speichern'; },1200);
    }
  }).catch(function(){ if(hint)hint.textContent='Speichern fehlgeschlagen.'; alert('Speichern fehlgeschlagen.'); });
}
function eplb_genMaterial(){
  eplb_syncRkrsFromName({onlyEmpty:true});
  var file=eplb_val('eplb_tpl_material');
  if(!file){ alert('Bitte Material-Template wählen.'); return; }
  var u=EPLB_ASSESS+'?mode=form&file='+encodeURIComponent(file);
  var q=eplb_commonParams(true); if(q.length)u+='&'+q.join('&');
  if(EPLB_CARRY)u+=EPLB_CARRY;
  document.getElementById('eplb_res_material').value=u;
}
function eplb_genPraxis(){
  eplb_syncRkrsFromName({onlyEmpty:true});
  var file=eplb_val('eplb_tpl_praxis');
  if(!file){ alert('Bitte Praxis-Template wählen.'); return; }
  var u=EPLB_ASSESS+'?mode=form&file='+encodeURIComponent(file);
  var q=eplb_commonParams(false); if(q.length)u+='&'+q.join('&');
  if(EPLB_CARRY)u+=EPLB_CARRY;
  document.getElementById('eplb_res_praxis').value=u;
}
function eplb_theorieModus(){
  var el=document.querySelector('input[name="eplb_theorie_modus"]:checked');
  return (el&&el.value==='schulung')?'quiz':'pruefung';
}
function eplb_theorieOhneBilder(){
  var el=document.querySelector('input[name="eplb_theorie_bilder"]:checked');
  return !!(el&&el.value==='ohne');
}
function eplb_theorieOhneLerntext(){
  var el=document.querySelector('input[name="eplb_theorie_lerntext"]:checked');
  return !!(el&&el.value==='ohne');
}
function eplb_quizNeedsExam(quiz){
  return quiz==='RKRSp-EP'||quiz==='RKRSp-EP-preuf';
}
function eplb_genTheorie(){
  eplb_syncRkrsFromName({onlyEmpty:true});
  var quiz=eplb_val('eplb_quiz_theorie');
  var name=eplb_val('eplb_pilot'), rk=eplb_val('eplb_rkrsid'), exam=eplb_val('eplb_exam'), drone=eplb_val('eplb_drone');
  var qmodus=eplb_theorieModus();
  if(!quiz){ alert('Bitte Theorie-Quiz wählen.'); return; }
  if(eplb_quizNeedsExam(quiz)&&!exam){ alert('Bitte EXAM-Code eingeben.'); return; }
  if(!name){ alert('Bitte Pilot/Name eingeben.'); return; }
  if(!drone){ alert('Bitte Drohne eingeben.'); return; }
  // Theorie-URL: Name + Drohne Pflicht; PersPick für Namensliste aus Pilotenliste.
  var qs='quiz='+encodeURIComponent(quiz)+'&QuizModus='+encodeURIComponent(qmodus)
    +'&PersPick=on'
    +'&Name='+encodeURIComponent(name)
    +'&Drone='+encodeURIComponent(drone);
  if(exam){
    qs+='&EXAM='+encodeURIComponent(exam);
  }
  if(rk){
    qs+='&RKRSPilotID='+encodeURIComponent(rk);
  }
  if(eplb_theorieOhneBilder()) qs+='&ohne_bilder=1';
  if(eplb_theorieOhneLerntext()) qs+='&Status=Quiz';
  var body={
    quiz_key:quiz,
    quiz:quiz,
    QuizModus:qmodus,
    Name:name,
    Drone:drone
  };
  if(exam) body.EXAM=exam;
  if(rk) body.RKRSPilotID=rk;
  if(eplb_theorieOhneBilder()) body.ohne_bilder='1';
  if(eplb_theorieOhneLerntext()) body.Status='Quiz';
  // quiz_key= in URL — nie quiz= (Hash-Gate auf Portal). Auch wenn POST-Body ignoriert wird.
  var apiQs='quiz_key='+encodeURIComponent(quiz)+'&QuizModus='+encodeURIComponent(qmodus)
    +'&Name='+encodeURIComponent(name)
    +'&Drone='+encodeURIComponent(drone);
  if(exam) apiQs+='&EXAM='+encodeURIComponent(exam);
  if(rk) apiQs+='&RKRSPilotID='+encodeURIComponent(rk);
  if(eplb_theorieOhneBilder()) apiQs+='&ohne_bilder=1';
  if(eplb_theorieOhneLerntext()) apiQs+='&Status=Quiz';
  var sep=(String(EPLB_CS_API).indexOf('?')>=0)?'&':'?';
  var apiUrl=EPLB_CS_API+sep+apiQs;
  fetch(apiUrl,{
    method:'POST',
    credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(body)
  }).then(function(r){
    return r.text().then(function(t){
      var j=null;
      try{ j=JSON.parse(t); }catch(e){ j=null; }
      if(!j){
        throw new Error('cs-API lieferte kein JSON (möglicherweise Hash-Gate / Zugriff verweigert).');
      }
      return j;
    });
  }).then(function(j){
    if(!j){ alert('cs-Antwort ungültig.'); return; }
    if(j.assessment_ok===false){ alert('cs-Berechnung: assessment-Klasse fehlt auf dem Server.'); return; }
    if(j.missing_field){ alert('cs: Pflichtfeld fehlt: '+j.missing_field); return; }
    if(!j.ok || (j.gated && !j.cs)){ alert('cs konnte nicht berechnet werden'+(j.ok===false?' (ok=false).':'.')); return; }
    var u=EPLB_QUIZ+'?'+qs;
    if(j.cs) u+='&cs='+encodeURIComponent(j.cs);
    var carry=EPLB_CARRY||'';
    if(carry){
      carry=carry.replace(/&PersPick=[^&]*/g,'').replace(/\?PersPick=[^&]*/g,'?');
    }
    if(carry)u+=carry;
    document.getElementById('eplb_res_theorie').value=u;
    document.getElementById('eplb_res_payload').value=j.payload||'';
    document.getElementById('eplb_res_cs').value=j.cs||'';
  }).catch(function(err){ alert('cs-Anfrage fehlgeschlagen'+(err&&err.message?': '+err.message:'.')); });
}
function eplb_genZiele(){
  eplb_syncRkrsFromName({onlyEmpty:true});
  var quiz='RKRSp-doku';
  var name=eplb_val('eplb_pilot'), rk=eplb_val('eplb_rkrsid'), drone=eplb_val('eplb_drone');
  if(!name){ alert('Bitte Pilot/Name eingeben.'); return; }
  if(!drone){ alert('Bitte Drohne eingeben.'); return; }
  var qs='quiz='+encodeURIComponent(quiz);
  if(rk){
    qs+='&RKRSPilotID='+encodeURIComponent(rk);
  }
  qs+='&Name='+encodeURIComponent(name)
    +'&Drone='+encodeURIComponent(drone);
  var body={
    quiz_key:quiz,
    quiz:quiz,
    Name:name,
    Drone:drone
  };
  if(rk) body.RKRSPilotID=rk;
  var apiQs='quiz_key='+encodeURIComponent(quiz)
    +'&Name='+encodeURIComponent(name)
    +'&Drone='+encodeURIComponent(drone);
  if(rk) apiQs+='&RKRSPilotID='+encodeURIComponent(rk);
  var sep=(String(EPLB_CS_API).indexOf('?')>=0)?'&':'?';
  var apiUrl=EPLB_CS_API+sep+apiQs;
  fetch(apiUrl,{
    method:'POST',
    credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(body)
  }).then(function(r){
    return r.text().then(function(t){
      var j=null;
      try{ j=JSON.parse(t); }catch(e){ j=null; }
      if(!j){
        throw new Error('cs-API lieferte kein JSON (möglicherweise Hash-Gate / Zugriff verweigert).');
      }
      return j;
    });
  }).then(function(j){
    if(!j){ alert('cs-Antwort ungültig.'); return; }
    if(j.assessment_ok===false){ alert('cs-Berechnung: assessment-Klasse fehlt auf dem Server.'); return; }
    if(j.missing_field){ alert('cs: Pflichtfeld fehlt: '+j.missing_field); return; }
    if(!j.ok || (j.gated && !j.cs)){ alert('cs konnte nicht berechnet werden'+(j.ok===false?' (ok=false).':'.')); return; }
    var u=EPLB_ZIELE+'?'+qs;
    if(j.cs) u+='&cs='+encodeURIComponent(j.cs);
    var carry=EPLB_CARRY||'';
    if(carry){
      carry=carry.replace(/&PersPick=[^&]*/g,'').replace(/\?PersPick=[^&]*/g,'?');
    }
    if(carry)u+=carry;
    document.getElementById('eplb_res_ziele').value=u;
  }).catch(function(err){ alert('cs-Anfrage fehlgeschlagen'+(err&&err.message?': '+err.message:'.')); });
}
var eplbNm=document.getElementById('eplb_pilot');
if(eplbNm){ eplbNm.addEventListener('input',eplb_syncRkrsFromName); eplbNm.addEventListener('change',eplb_syncRkrsFromName); }
['eplb_tpl_material','eplb_tpl_praxis','eplb_quiz_theorie'].forEach(function(id){
  var el=document.getElementById(id);
  if(el) el.addEventListener('change',function(){ eplb_savePrefs(null); });
});
EPLB_JS;
	echo "\n</script>\n";
	pageFooterLocal();
}


/* -----------------------------------------------------------
 URL MANAGERS HUB (ADMIN) — Assessments, Beurteilungen, Quiz/Schulung
----------------------------------------------------------- */
function showUrlManagersHub(): void {
	if (!isset($_GET["ADMIN"])) {
		rkrk_redirect("?mode=list&ADMIN");
	}
	pageHeaderLocal("URL-Verwaltung (ADMIN)");

	$hAssess = rkrk_assess_append_setting_href("rkrk_assess.php?mode=linkbuilder");
	$hLive = rkrk_assess_append_setting_href("rkrk_assess_live.php?mode=linkbuilder");
	$hQuiz = rkrk_assess_append_setting_href("rkrk_assess.php?mode=digitalquizlinkbuilder");
	$hEp = rkrk_assess_append_setting_href("rkrk_assess.php?mode=einsatzpruefunglinkbuilder");

	echo "<div class='rkrk-card' style='max-width:760px;margin:0 auto;'>";
	echo "<h2 style='margin-top:0;'>URL-Manager</h2>";
	echo "<p style='color:#555;line-height:1.55;'>Vorgaben für Admins (<code>?ADMIN</code>). Generierte URLs übernehmen den Kanal (RKRS, RKRSplus, …), sofern in der aktuellen Session gesetzt.</p>";
	echo "<ul style='list-style:none;padding:0;margin:16px 0 0;'>";
	echo "<li style='margin-bottom:12px;'><a class='btn' href='" . safe($hAssess) . "'>Checklisten / Assessments</a> <span style='color:#64748b;font-size:13px;'>— Preset-URLs (<code>rkrk_assess.php</code>)</span></li>";
	echo "<li style='margin-bottom:12px;'><a class='btn' href='" . safe($hLive) . "'>Live-Beurteilungen</a> <span style='color:#64748b;font-size:13px;'>— Preset-URLs (<code>rkrk_assess_live.php</code>)</span></li>";
	echo "<li style='margin-bottom:12px;'><a class='btn' href='" . safe($hQuiz) . "'>Quiz &amp; Schulung</a> <span style='color:#64748b;font-size:13px;'>— Links zu <code>rkrk_digitalassess.php</code></span></li>";
	echo "<li style='margin-bottom:12px;'><a class='btn' href='" . safe($hEp) . "'>Einsatzprüfung</a> <span style='color:#64748b;font-size:13px;'>— Material, Praxis, Theorie inkl. <code>cs</code></span></li>";
	echo "</ul>";
	echo "<p style='margin-top:22px;'><a class='btn' href='" . safe(rkrk_assess_append_setting_href("?mode=list&ADMIN")) . "'>Zurück zur Checklisten-Liste</a></p>";
	echo "</div>";

	pageFooterLocal();
}



if (!empty($GLOBALS["rkrk_assess_regen_assess_bootstrap"])) {
	return;
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

    case 'templateprint':
        showTemplatePrintPreview();
        break;

    case 'paperform':
        showPaperTemplateForm();
        break;

    case 'templateexport':
        handleTemplateJsonExport();
        break;

    case 'templateimport':
        handleTemplateJsonImport();
        break;

	case 'templatefields':
		templateFieldsApi();
		break;

	case 'linkbuilder':
		showLinkBuilder();
		break;

	case 'digitalquizlinkbuilder':
		showDigitalQuizLinkBuilder();
		break;

	case 'einsatzpruefunglinkbuilder':
		showEinsatzpruefungLinkBuilder();
		break;

	case 'einsatzpruefunglink_cs':
		einsatzpruefungLinkCsApi();
		break;

	case 'einsatzpruefunglink_prefs':
		einsatzpruefungLinkPrefsApi();
		break;

	case 'urlmanagers':
		showUrlManagersHub();
		break;

	case 'print':
		showChecklistPrint();
		break;

	case "exportjson":
		rkrk_checklist_toolbar_handle_export_json($rkrkAssessResults);
		exit;

	case "importjson":
		rkrk_checklist_toolbar_handle_import_json($rkrkAssessResults, static function (string $u): void {
			rkrk_redirect($u);
		});
		exit;

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
