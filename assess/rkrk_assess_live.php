<?php
/**
 * Live-Beurteilungen (rekonstruiert aus rkrk_assess.php).
 * Seed (Lieferumfang): ./templates/*.json → wird nach $rkrkLiveassessTemplates kopiert.
 * Laufzeit: $rkrkLiveassessTemplates, $rkrkLiveassessResults , pilotenliste unter $rkrkLivePersonelRoot (alle unter $rkrkLiveassessData).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
include_once('rkrk_assess_common.php');
include_once(__DIR__ . '/rkrk_assess_perspick_default.inc.php');
include_once(__DIR__ . '/rkrk_assess_filename_ymd.inc.php');
include_once(__DIR__ . '/rkrk_assess_checklist_progress.inc.php');

$modeEarly = $_GET["mode"] ?? "list";
// Speichern/Löschen/API: kein Seiten-Chrome (sonst stört Output die PDF-Erzeugung / Redirect)
$rkrkLiveSilentModes = array(
	"save",
	"deletejson",
	"templatefields",
	"saveweatherclient",
	"weather",
	"print",
	"templateprint",
	"templateexport",
);
$rkrkLiveSilent = !empty($GLOBALS["rkrk_assess_regen_live_bootstrap"])
	|| in_array((string) $modeEarly, $rkrkLiveSilentModes, true);

if (!$rkrkLiveSilent && $modeEarly !== "paperform") {
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
}

if (!$onRKRS && $modeEarly !== "paperform") {
	//check impropriate IP addresses accessing page
	CheckIPandExit($folderoffset);

	//Write to LOGfile
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","",$folderoffset);

	GrantAccess($folderoffset );
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
	$showTopMenu = !$rkrkLiveSilent && (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast' && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
		if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
			rkrk_assess_emit_admin_url_managers_menu_strip();
		}
	}
}

$pagetitle = "Beurteilungen";
if (!$rkrkLiveSilent && $modeEarly !== "paperform") {
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

/* ERST JETZT HTML */
/*
headerpage("Digitale Beurteilungen", false);
?>
<body>
<?
*/



// Logo/Bilder: Web-URL (nicht $rkrkDataRoot / FS-Pfad).
// Zuerst anhand von SCRIPT_NAME: root-relative /…/rkropen/rkrk/data/assess/images/ wenn "rkropen" im URL-Pfad vorkommt.
if (!function_exists('rkrk_assess_live_normalize_path')) {
	function rkrk_assess_live_normalize_path($path) {
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
	$examIconLoc = rkrk_assess_live_normalize_path('/' . implode('/', array_slice($_rkrkSeg, 0, $_rkrkRi + 1)) . '/rkrk/data/assess/images/');
} else {
	$_rkrkN = count($_rkrkSeg);
	if ($_rkrkN >= 2 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'assess' && strtolower((string)$_rkrkSeg[$_rkrkN - 2]) === 'test') {
		$examIconLoc = rkrk_assess_live_normalize_path('../../rkrk/data/assess/images/');
	} elseif ($_rkrkN >= 1 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'assess') {
		$examIconLoc = rkrk_assess_live_normalize_path('../rkrk/data/assess/images/');
	} elseif ($_rkrkN >= 2 && strtolower((string)$_rkrkSeg[$_rkrkN - 1]) === 'public' && strtolower((string)$_rkrkSeg[$_rkrkN - 2]) === 'assess'
		&& is_dir(__DIR__ . '/../../data/assess/images')) {
		// Portal (z. B. /rkrsplusassessment/assess/public/): Daten liegen unter ../../data/assess/
		$examIconLoc = rkrk_assess_live_normalize_path('../../data/assess/images/');
	} elseif (preg_match('#[/\\\\]assess$#i', str_replace('\\', '/', __DIR__))) {
		$examIconLoc = rkrk_assess_live_normalize_path('../../rkrk/data/assess/images/');
	} else {
		$examIconLoc = rkrk_assess_live_normalize_path('../rkrk/data/assess/images/');
	}
}
unset($_rkrkSn, $_rkrkDn, $_rkrkSeg, $_rkrkRi, $_rkrkN, $_i, $_p);

foreach ([$rkrkLiveassessData, $rkrkLiveassessTemplates, $rkrkLiveassessResults , $rkrkLivePersonelRoot] as $_rkrkLaDir) {
    if ($_rkrkLaDir !== "" && !is_dir($_rkrkLaDir)) {
        @mkdir($_rkrkLaDir, 0777, true);
    }
}
unset($_rkrkLaDir);

/* Standard-Templates: Seed aus diesem Paket (assess/templates) → $rkrkLiveassessTemplates (= $rkrkLiveassessData/templates), nur wenn Ziel fehlt */
foreach (["pilotenbeurteilung.json", "teambeurteilung.json"] as $_bn) {
    $_seedTpl = __DIR__ . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $_bn;
    $_destTpl = $rkrkLiveassessTemplates . "/" . $_bn;
    if (is_dir($rkrkLiveassessTemplates) && is_file($_seedTpl) && !is_file($_destTpl)) {
        @copy($_seedTpl, $_destTpl);
    }
}
unset($_bn, $_seedTpl, $_destTpl);

require_once __DIR__ . "/rkrk_template_csv_inc.php";
require_once __DIR__ . "/rkrk_checklist_toolbar_inc.php";

if (($modeEarly ?? "") === "paperform") {
	require_once __DIR__ . "/rkrk_assess_paper_form_inc.php";
	$fPf = basename((string) ($_GET["file"] ?? ""));
	$backPf = function_exists("rkrk_assess_paperform_resolve_back_url")
		? rkrk_assess_paperform_resolve_back_url($fPf)
		: rkrk_assess_append_setting_href("?mode=templates");
	$optsPf = array(
		"back_url" => $backPf,
		"logo_src" => (string) $examIconLoc . (string) $logopic,
	);
	if (!rkrk_assess_paper_form_from_template_file($rkrkLiveassessTemplates, $fPf, $optsPf)) {
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
    global $rkrkLiveassessTemplates;
    $path = "$rkrkLiveassessTemplates/$file";
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
            foreach (["placeholder", "info", "process", "checks", "subchecks", "options", "ref", "calc", "stufe_slot"] as $opt) {
                if (isset($meta[$opt]) && !is_string($meta[$opt])) {
                    unset($tpl["sections"][$secName][$label][$opt]);
                    $changed = true;
                }
            }

            // Referenznummern nur vergeben wenn noch keine gesetzt (calc-Formeln bleiben stabil).
            $curRef = isset($tpl["sections"][$secName][$label]["ref"]) ? trim((string)$tpl["sections"][$secName][$label]["ref"]) : "";
            if ($curRef === "") {
                $autoRef = "R" . str_pad((string)$autoRefCounter, 3, "0", STR_PAD_LEFT);
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
    global $rkrkLiveassessTemplates;
    $files = glob("$rkrkLiveassessTemplates/*.json");
    if (!is_array($files)) return [];
    // Unterscheidung: Dateinamen prefix live_* von klassischen Assess-Templates ausblenden.
    return array_values(array_filter($files, function ($path) {
        $base = strtolower(basename((string)$path));
        return (strpos($base, 'live_') !== 0);
    }));
}

/* ---------------- LOAD CHECKLIST JSON ---------------- */
function loadChecklist($id){
    global $rkrkLiveassessResults ;
    $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/$id.json";
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

function rkrk_is_m2_spec_label(string $label): bool
{
    return (bool)preg_match('/^M\.2#\s+Drohnen\s+Spezifikationen/iu', trim($label));
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

function rkrk_parse_point_label_rules(string $label): array
{
    $label = trim((string)$label);
    $star = strpos($label, '*') !== false;
    $hash = strpos($label, '#') !== false;
    $display = $label;
    if (preg_match('/^([A-Z]\.\d+|M\.\d+)\s*([*#]+)\s*(.+)$/u', $label, $m)) {
        $display = $m[1] . ' ' . trim($m[3]);
        $ruleStr = $m[2];
        $star = strpos($ruleStr, '*') !== false;
        $hash = strpos($ruleStr, '#') !== false;
    }
    return ['display' => trim($display), 'star' => $star, 'hash' => $hash];
}

function rkrk_live_echo_field_label_html(string $label): void
{
    $p = rkrk_parse_point_label_rules($label);
    echo safe($p['display']);
    if ($p['star']) {
        echo " <span class='rk-rule rk-rule-star' title='Kritisch: Stufe 0 blockiert'>*</span>";
    }
    if ($p['hash']) {
        echo " <span class='rk-rule rk-rule-hash' title='MUSS-Kriterium: mindestens Stufe 2'>#</span>";
    }
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

/** Bemerkung → zugehöriges Bewertungsfeld (M.3 Bemerkung → M.3 Drohne, M.1# Bemerkung → M.1# Drohnentyp). */
function rkrk_resolve_remark_parent_label(array $fields, string $remarkLabel): ?string
{
    $remarkLabel = trim($remarkLabel);
    if (!preg_match('/\bBemerkung\s*$/iu', $remarkLabel)) {
        return null;
    }
    $stem = preg_replace('/\s+Bemerkung\s*$/iu', '', $remarkLabel);
    if ($stem !== $remarkLabel && isset($fields[$stem]) && is_array($fields[$stem])) {
        return $stem;
    }
    if (preg_match('/^(M\.\d+#)\s+Bemerkung$/iu', $remarkLabel, $hm)) {
        $pfx = $hm[1];
        foreach ($fields as $candidateLabel => $candidateType) {
            if (preg_match('/\bBemerkung\b/ui', (string)$candidateLabel)) {
                continue;
            }
            if (strpos((string)$candidateLabel, $pfx) !== 0 || !is_array($candidateType)) {
                continue;
            }
            $t = strtolower(trim((string)($candidateType['type'] ?? '')));
            if (strpos($t, 'radio:') === 0 || strpos($t, 'multi:') === 0) {
                return (string)$candidateLabel;
            }
        }
    }
    $pointCode = rkrk_extract_point_code($remarkLabel);
    if ($pointCode === '') {
        return null;
    }
    $best = null;
    $bestPri = -1;
    foreach ($fields as $candidateLabel => $candidateType) {
        if (preg_match('/\bBemerkung\b/ui', (string)$candidateLabel)) {
            continue;
        }
        if (!rkrk_label_starts_with_point_code((string)$candidateLabel, $pointCode)) {
            continue;
        }
        if (!is_array($candidateType)) {
            continue;
        }
        $t = strtolower(trim((string)($candidateType['type'] ?? '')));
        if (strpos($t, 'radio:') !== 0 && strpos($t, 'multi:') !== 0 && $t !== 'directcheck') {
            continue;
        }
        $quoted = preg_quote($pointCode, '/');
        $pri = 0;
        // Prefer exact base (C.1 Foo) over marked (C.1*# Foo) when remark uses base code.
        if (preg_match('/^' . $quoted . '\s+/u', (string)$candidateLabel)) {
            $pri = 2;
        } elseif (preg_match('/^' . $quoted . '[*#]+/u', (string)$candidateLabel)) {
            $pri = 1;
        }
        if ($pri > $bestPri) {
            $bestPri = $pri;
            $best = (string)$candidateLabel;
        }
    }
    if ($best !== null) {
        return $best;
    }
    $meta = rkrk_find_point_evaluation_meta($fields, $pointCode);
    return (is_array($meta) && isset($meta['score_label'])) ? (string)$meta['score_label'] : null;
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

/**
 * Teilpunkte pro Hauptzeile: subchecks-Text in Blöcke anhand Zeile nur „---“ trennen (Reihenfolge = Reihenfolge der Kontrollpunkte).
 * @return list<list<string>>
 */
function rkrk_directcheck_subchecks_per_main(string $subchecksSrc, int $nMain): array
{
    if ($nMain <= 0) {
        return [];
    }
    $raw = trim(str_replace("\r\n", "\n", $subchecksSrc));
    if ($raw === "") {
        $empty = [];
        for ($i = 0; $i < $nMain; $i++) {
            $empty[] = [];
        }
        return $empty;
    }
    $parts = preg_split('/^\s*---\s*$/m', $raw);
    if (!is_array($parts)) {
        $parts = [$raw];
    }
    $blocks = [];
    foreach ($parts as $p) {
        $lines = [];
        foreach (preg_split('/\R+/u', trim((string)$p)) as $ln) {
            $ln = trim((string)$ln);
            if ($ln !== "") {
                $lines[] = $ln;
            }
        }
        $blocks[] = $lines;
    }
    $out = [];
    for ($i = 0; $i < $nMain; $i++) {
        if (isset($blocks[$i])) {
            $out[] = $blocks[$i];
        } elseif (count($blocks) === 1 && $nMain > 1) {
            $out[] = ($i === 0) ? $blocks[0] : [];
        } else {
            $out[] = [];
        }
    }
    return $out;
}

/** Direktbewertung: v3 { main, subHit{mainIdx:{subIdx:true}}, obs } — v2 flaches subHit wird verworfen. */
function rkrk_directcheck_parse_value($raw): array
{
    $out = ["v" => 3, "main" => [], "subHit" => [], "obs" => ""];
    if (!is_string($raw) || trim($raw) === "") {
        return $out;
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        return $out;
    }
    $out["v"] = (int)($j["v"] ?? 3);
    $out["obs"] = isset($j["obs"]) ? (string)$j["obs"] : "";
    if (isset($j["main"]) && is_array($j["main"])) {
        foreach ($j["main"] as $k => $v) {
            if (!is_numeric($k)) {
                continue;
            }
            $k = (string)((int)$k);
            $v = (string)$v;
            $out["main"][$k] = in_array($v, ["none", "green", "orange", "red"], true) ? $v : "none";
        }
    }
    if (isset($j["subHit"]) && is_array($j["subHit"])) {
        $nested = false;
        foreach ($j["subHit"] as $v) {
            if (is_array($v)) {
                $nested = true;
                break;
            }
        }
        if ($nested) {
            foreach ($j["subHit"] as $mk => $subm) {
                if (!is_numeric($mk)) {
                    continue;
                }
                $mk = (string)((int)$mk);
                $out["subHit"][$mk] = [];
                if (!is_array($subm)) {
                    continue;
                }
                foreach ($subm as $sk => $on) {
                    // JSON kann [true,false] oder {"0":true} speichern
                    if (is_int($sk) || is_string($sk) && ctype_digit((string)$sk)) {
                        $sk = (string)((int)$sk);
                        if ($on === true || $on === 1 || $on === "1") {
                            $out["subHit"][$mk][$sk] = true;
                        }
                    }
                }
            }
        } else {
            // flaches Legacy-Format verwerfen, aber Objekt {mainIdx:{subIdx:true}} ohne Array-Werte
            foreach ($j["subHit"] as $mk => $subm) {
                if (!is_numeric($mk) || !is_array($subm)) {
                    continue;
                }
                $mk = (string)((int)$mk);
                $out["subHit"][$mk] = [];
                foreach ($subm as $sk => $on) {
                    if (!is_numeric($sk)) {
                        continue;
                    }
                    $sk = (string)((int)$sk);
                    if ($on === true || $on === 1 || $on === "1") {
                        $out["subHit"][$mk][$sk] = true;
                    }
                }
            }
        }
    }
    return $out;
}

/**
 * @param list<list<string>> $subLinesByMain
 */
function rkrk_directcheck_normalize_states(array $parsed, int $nMain, array $subLinesByMain): array
{
    $main = [];
    for ($i = 0; $i < $nMain; $i++) {
        $k = (string)$i;
        $st = $parsed["main"][$k] ?? "none";
        $main[$k] = in_array($st, ["none", "green", "orange", "red"], true) ? $st : "none";
    }
    $subHit = [];
    for ($i = 0; $i < $nMain; $i++) {
        $k = (string)$i;
        $subHit[$k] = [];
        $lines = $subLinesByMain[$i] ?? [];
        $nSub = count($lines);
        for ($j = 0; $j < $nSub; $j++) {
            $sk = (string)$j;
            if (!empty($parsed["subHit"][$k][$sk])) {
                $subHit[$k][$sk] = true;
            }
        }
    }
    return [
        "v" => 3,
        "main" => $main,
        "subHit" => $subHit,
        "obs" => (string)($parsed["obs"] ?? ""),
    ];
}

function rkrk_directcheck_encode_value(array $data): string
{
    return json_encode(
        [
            "v" => 3,
            "main" => $data["main"] ?? [],
            "subHit" => $data["subHit"] ?? [],
            "obs" => (string)($data["obs"] ?? ""),
        ],
        JSON_UNESCAPED_UNICODE
    );
}

function rkrk_directcheck_state_label(string $st): string
{
    if ($st === "green") {
        return "erfüllt";
    }
    if ($st === "orange") {
        return "teilweise erfüllt";
    }
    if ($st === "red") {
        return "nicht erfüllt";
    }
    return "unbewertet";
}

/** Symbol + Farben für Directcheck-Druck (wie Digital ✓/★/✗). @return array{sym:string,fg:string,bg:string,bd:string,cls:string} */
function rkrk_directcheck_state_style(string $st): array
{
    // Print: nur Symbol + Schriftfarbe, kein farbiger Hintergrund.
    if ($st === "green") {
        return array("sym" => "✓", "fg" => "#14532d", "bg" => "transparent", "bd" => "transparent", "cls" => "dc-print-row--ok");
    }
    if ($st === "orange") {
        return array("sym" => "★", "fg" => "#9a3412", "bg" => "transparent", "bd" => "transparent", "cls" => "dc-print-row--partial");
    }
    if ($st === "red") {
        return array("sym" => "✗", "fg" => "#7f1d1d", "bg" => "transparent", "bd" => "transparent", "cls" => "dc-print-row--bad");
    }
    return array("sym" => "·", "fg" => "#64748b", "bg" => "transparent", "bd" => "transparent", "cls" => "dc-print-row--none");
}

function rkrk_directcheck_format_print_html(string $rawVal, string $checksSrc, string $subchecksSrc): string
{
    $parsed = rkrk_directcheck_parse_value($rawVal);
    $mainLines = rkrk_split_checks_detailed($checksSrc);
    $nMain = count($mainLines);
    $subByMain = rkrk_directcheck_subchecks_per_main($subchecksSrc, $nMain);
    $norm = rkrk_directcheck_normalize_states($parsed, $nMain, $subByMain);
    $hideUnrated = !empty($GLOBALS["rkrk_assess_print_hide_unrated"]);

    $bits = [];
    if ($nMain === 0) {
        if ($hideUnrated) {
            return "";
        }
        $bits[] = "<em>Keine Kontrollpunkte</em>";
    } else {
        $allGreen = true;
        $ratedCount = 0;
        $anyRated = false;
        // Zuerst prüfen, ob die Subkategorie überhaupt bewertet wurde
        foreach ($mainLines as $i => $line) {
            $st = $norm["main"][(string)$i];
            if ($st === "green" || $st === "orange" || $st === "red") {
                $anyRated = true;
                $ratedCount++;
            }
            if ($st !== "green") {
                $allGreen = false;
            }
        }
        if ($hideUnrated && !$anyRated) {
            return "";
        }
        foreach ($mainLines as $i => $line) {
            $st = $norm["main"][(string)$i];
            $disp = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$line);
            $disp = trim((string)$disp);
            if ($disp === "") {
                $disp = (string)$line;
            }
            $sty = rkrk_directcheck_state_style($st);
            $bits[] =
                "<div class=\"dc-print-row " . $sty["cls"] . "\" style=\"margin:0;padding:0;font-size:8.5pt;line-height:1.25;"
                . "border:none;background:transparent;color:" . $sty["fg"] . ";\">"
                . "<font color=\"" . $sty["fg"] . "\">"
                . "<b>" . $sty["sym"] . "</b> "
                . "<strong>" . safe($disp) . "</strong> — " . safe(rkrk_directcheck_state_label($st))
                . "</font>"
                . "</div>";
            // Nicht erfüllte Teilpunkte (subHits) immer unter orange/rot ausweisen
            if (($st === "orange" || $st === "red") && !empty($subByMain[$i])) {
                $hitsForLine = [];
                foreach ($subByMain[$i] as $j => $sline) {
                    if (empty($norm["subHit"][(string)$i][(string)$j])) {
                        continue;
                    }
                    $sd = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$sline);
                    $sd = trim((string)$sd);
                    if ($sd === "") {
                        $sd = (string)$sline;
                    }
                    $hitsForLine[] = $sd;
                }
                if (!empty($hitsForLine)) {
                    $bits[] =
                        "<div class=\"dc-print-subcap\" style=\"margin:2px 0 0 12px;font-size:7.5pt;color:#64748b;\">"
                        . "<font color=\"#64748b\">" . safe("Nicht erfüllte Punkte:") . "</font></div>";
                    foreach ($hitsForLine as $sd) {
                        $bits[] =
                            "<div class=\"dc-print-row dc-print-sub-indent\" style=\"margin:0;padding:0 0 0 12px;font-size:8pt;border:none;color:#9a3412;\">"
                            . "<font color=\"#9a3412\">✗ " . safe($sd) . "</font></div>";
                    }
                }
            }
        }
        if ($hideUnrated && $ratedCount === 0) {
            return "";
        }
        // Falls Teilpunkte markiert sind, aber die zugehörige Zeile unbewertet/versteckt war:
        // trotzdem in bewerteter Subkategorie unter «Nicht erfüllte Punkte» ausweisen.
        $extraHits = [];
        if ($ratedCount > 0 || !$hideUnrated) {
            foreach ($mainLines as $i => $line) {
                if (empty($subByMain[$i])) {
                    continue;
                }
                $st = $norm["main"][(string)$i];
                // Unter der Zeile bereits ausgegeben wenn orange/rot — hier nur Nachzug für andere Fälle
                if ($st === "orange" || $st === "red") {
                    continue;
                }
                foreach ($subByMain[$i] as $j => $sline) {
                    if (empty($norm["subHit"][(string)$i][(string)$j])) {
                        continue;
                    }
                    $sd = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$sline);
                    $sd = trim((string)$sd);
                    if ($sd === "") {
                        $sd = (string)$sline;
                    }
                    $extraHits[$sd] = true;
                }
            }
        }
        if (!empty($extraHits)) {
            $bits[] =
                "<div class=\"dc-print-subcap\" style=\"margin:3px 0 0;font-size:7.5pt;color:#64748b;\">"
                . "<font color=\"#64748b\">" . safe("Nicht erfüllte Punkte:") . "</font></div>";
            foreach (array_keys($extraHits) as $sd) {
                $bits[] =
                    "<div class=\"dc-print-row dc-print-sub-indent\" style=\"margin:0;padding:0 0 0 12px;font-size:8pt;border:none;color:#9a3412;\">"
                    . "<font color=\"#9a3412\">✗ " . safe($sd) . "</font></div>";
            }
        }
        if (!$hideUnrated || $ratedCount > 0) {
            $stageOk = $allGreen;
            $stageFg = $stageOk ? "#14532d" : "#7f1d1d";
            $stageSym = $stageOk ? "✓" : "✗";
            $stageTxt = $stageOk ? "Alle Kriterien erfüllt" : "Nicht alle Kriterien erfüllt";
            $bits[] =
                "<div class=\"dc-print-stage\" style=\"margin-top:2px;padding:0;font-size:8.5pt;"
                . "border:none;background:transparent;color:" . $stageFg . ";\">"
                . "<font color=\"" . $stageFg . "\"><strong>" . $stageSym . " " . $stageTxt . "</strong></font>"
                . "</div>";
        }
    }

    $anyOR = false;
    foreach ($norm["main"] as $st) {
        if ($st === "orange" || $st === "red") {
            $anyOR = true;
            break;
        }
    }

    if ($anyOR) {
        $obs = trim((string)$norm["obs"]);
        if ($obs !== "") {
            $bits[] =
                "<div class=\"dc-print-obs\" style=\"margin-top:4px;font-size:8.5pt;\">"
                . "<strong>Beobachtungen &amp; Bemerkungen:</strong><br>"
                . nl2br(safe($obs))
                . "</div>";
        }
    }

    return "<div class=\"dc-print\">" . implode("", $bits) . "</div>";
}

/** Ablaufzeilen (mehrzeiliger Text → nummerierte Schritte im Formular). */
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

/**
 * Kontrollpunkte detaillierter als nur „ – “: Zeilenumbrüche, Semikolon, sonst Gedankenstrich-Liste.
 * @return list<string>
 */
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
        if ($p === "") {
            continue;
        }
        if (strlen($p) > 120 && preg_match('/\s+[–-]\s+/u', $p)) {
            foreach (preg_split('/\s+[–-]\s+/u', $p) as $sub) {
                $sub = trim((string)$sub);
                if ($sub !== "") {
                    $out[] = $sub;
                }
            }
        } else {
            $out[] = $p;
        }
    }
    return $out;
}

/** Process-Feld aus Template: HTML (z. B. NKL-Tabelle) oder Fließtext. */
function rkrk_process_is_html(string $process): bool
{
    $process = trim($process);
    return $process !== "" && strip_tags($process) !== $process;
}

/** Ablauf/Referenz als HTML für Live- oder Druckansicht. */
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

/** Ablauf + Checks vor dem Bewertungsfeld in einem durchgehenden Block (einspaltig). */
function rkrk_live_emit_point_flow_block(string $process, string $checks, string $pointCode): void
{
    $process = trim($process);
    $checks = trim($checks);
    if (!empty($GLOBALS["rkrkAssessActiveStory"]) && is_array($GLOBALS["rkrkAssessActiveStory"])) {
        $process = "";
    }
    if ($process === "" && $checks === "") {
        return;
    }
    $pcEsc = htmlspecialchars($pointCode, ENT_QUOTES, "UTF-8");
    echo "<div class='live-point-flow' data-point='$pcEsc'>";
    if ($process !== "") {
        $isHtml = rkrk_process_is_html($process);
        $panelCls = "live-point-flow__panel live-point-flow__panel--process";
        if ($isHtml) {
            $panelCls .= " live-point-flow__panel--spec";
        }
        echo "<details class='$panelCls' open>";
        if ($isHtml) {
            echo "<summary class='live-point-flow__summary'>NKL-Spezifikation Kap. 4.1</summary>";
        } else {
            echo "<summary class='live-point-flow__summary'>Ablauf <span class='live-point-code'>" . safe($pointCode) . "</span></summary>";
        }
        echo "<div class='live-point-flow__content'>";
        if (function_exists("rkrk_assess_format_process_html_for_screen")) {
            echo rkrk_assess_format_process_html_for_screen($process);
        } else {
            echo rkrk_render_process_html($process);
        }
        echo "</div>";
        echo "</details>";
    }
    if ($checks !== "") {
        $items = rkrk_split_checks_detailed($checks);
        echo "<details class='live-point-flow__panel live-point-flow__panel--checks' open>";
        echo "<summary class='live-point-flow__summary'>Kontrollpunkte (Checks)</summary>";
        echo "<div class='live-point-flow__content'>";
        echo "<ul class='live-point-flow__checks'>";
        foreach ($items as $it) {
            $disp = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$it);
            $disp = trim((string)$disp);
            if ($disp === "") {
                $disp = (string)$it;
            }
            echo "<li><span class='live-check-marker' aria-hidden='true'>✓</span><span class='live-check-text'>" . nl2br(safe($disp)) . "</span></li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</details>";
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

/**
 * Gespeicherte «Nicht erfüllte Punkte» zum Bewertungsfeld finden
 * (Label vollständig, Punktcode, oder Fuzzy-Match in der Sektion).
 *
 * @param array<string,mixed> $secFields
 * @return list<string>
 */
function rkrk_find_not_fulfilled_items_for_field(array $secFields, string $label, string $pointCode = ""): array
{
    $label = trim($label);
    $pointCode = trim($pointCode);
    if ($pointCode === "" && $label !== "") {
        $pointCode = rkrk_extract_point_code($label);
    }
    $candidates = array();
    if ($label !== "") {
        $candidates[] = $label . " Nicht erfüllte Punkte";
    }
    if ($pointCode !== "") {
        $candidates[] = $pointCode . " Nicht erfüllte Punkte";
    }
    foreach ($candidates as $key) {
        if (!array_key_exists($key, $secFields)) {
            continue;
        }
        $items = rkrk_decode_not_fulfilled_points($secFields[$key]);
        if (!empty($items)) {
            return $items;
        }
    }
    foreach ($secFields as $key => $raw) {
        $key = (string) $key;
        if (!preg_match('/\bNicht erfüllte Punkte$/u', $key)) {
            continue;
        }
        $prefix = trim(preg_replace('/\s*Nicht erfüllte Punkte$/u', "", $key));
        $match = false;
        if ($label !== "" && ($prefix === $label || strpos($prefix, $label) === 0 || strpos($label, $prefix) === 0)) {
            $match = true;
        } elseif ($pointCode !== "" && function_exists("rkrk_label_starts_with_point_code")
            && rkrk_label_starts_with_point_code($prefix !== "" ? $prefix : $key, $pointCode)) {
            $match = true;
        }
        if (!$match) {
            continue;
        }
        $items = rkrk_decode_not_fulfilled_points($raw);
        if (!empty($items)) {
            return $items;
        }
    }
    return array();
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
            : "template/flex.css";
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
<style>
.remark-with-meta[data-ro-dependent]:not(.visible){ display:none !important; }
.field[data-ro-dependent]:not(.visible){ display:none !important; }
</style>
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
            if (i.classList && i.classList.contains('dc-json') && i.type === 'hidden') {
                try {
                    const o = JSON.parse(i.value || '{}');
                    const m = o.main || {};
                    const vals = Object.values(m);
                    if (isNotFulfilledBox) {
                        if (vals.some(function(s) { return s === 'orange' || s === 'red'; })) show = true;
                    } else {
                        if (vals.some(function(s) { return s === 'green' || s === 'orange' || s === 'red'; })) show = true;
                    }
                } catch (e) {}
                return;
            }
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
        dep.hidden = !show;
        dep.style.display = show ? '' : 'none';
    }

    inputs.forEach(i => {
        i.addEventListener('change', updateRemarkVisibility);
        i.addEventListener('input', updateRemarkVisibility);
    });
    updateRemarkVisibility();
});


document.addEventListener('change', function(e) {
    var t = e.target;
    if (!t || !t.getAttribute) return;
    var key = t.getAttribute('data-field-key');
    if (!key) return;
    if (t.type !== 'radio' && t.type !== 'checkbox' && t.tagName !== 'SELECT') return;
    document.querySelectorAll('[data-ro-parent-key=\"' + key + '\"]').forEach(function(dep) {
        var inputs = document.querySelectorAll('[data-field-key=\"' + key + '\"]');
        var isNf = dep.classList.contains('notfulfilled-box');
        var matcher = isNf ? hasNotFulfilledTrigger : hasRemarkTrigger;
        var show = false;
        inputs.forEach(function(i) {
            if (i.type === 'radio' && i.checked && matcher(i.value)) show = true;
            if (i.type === 'checkbox' && i.checked && matcher(i.value)) show = true;
            if (i.tagName === 'SELECT' && matcher(i.value)) show = true;
        });
        dep.classList.toggle('visible', show);
        dep.hidden = !show;
        dep.style.display = show ? '' : 'none';
    });
});

window.rkrkRefreshRemarkDependents = function(parentKey) {
    if (!parentKey) return;
    document.querySelectorAll('[data-field-key=\"' + parentKey + '\"]').forEach(function(i) {
        i.dispatchEvent(new Event('change', { bubbles: true }));
    });
};

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


function rkrkIsGreenValue(v){
    return /\(g\)\s*$/i.test(String(v || '').trim());
}

function rkrkInputIsMussHash(input){
    if (!input || !input.closest) return false;
    const wrap = input.closest('.field');
    return !!(wrap && wrap.hasAttribute('data-rk-hash-muss'));
}

function rkrkValueCountsForMatcher(value, matcher, isMuss){
    const v = String(value || '').trim();
    if (!v) return false;
    if (rkrkIsNeutralValue(v)) return false;
    if (isMuss && matcher === rkrkIsCriticalValue) {
        return !rkrkIsGreenValue(v);
    }
    return matcher(v);
}

function rkrkRefCountByMatcher(refId, matcher){
    const nodes = rkrkRefNodes(refId);
    if (!nodes.length) return 0;

    if (nodes[0].type === 'radio') {
        const checked = nodes.find(n => n.checked);
        if (!checked) return 0;
        const isMuss = rkrkInputIsMussHash(checked);
        return rkrkValueCountsForMatcher(checked.value, matcher, isMuss) ? 1 : 0;
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
        const isMuss = rkrkInputIsMussHash(nodes[0]);
        if (isMuss && matcher === rkrkIsCriticalValue) {
            return nodes.some(n => !rkrkIsGreenValue(n.value) && !rkrkIsNeutralValue(n.value)) ? 1 : 0;
        }
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
}


function rkrkSectionIdFromName(secName) {
    return String(secName || '').toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

function rkrkExpandCalcRefs(formula) {
    const raw = String(formula || '').trim();
    const secMatch = raw.match(/^@section:(.+)$/i);
    if (secMatch) {
        const secTitle = secMatch[1].trim();
        const secId = rkrkSectionIdFromName(secTitle);
        let root = document.querySelector('.section-fields[data-section-id=\"' + secId + '\"]');
        if (!root) {
            root = Array.from(document.querySelectorAll('.section-fields[data-section-title]')).find(function(el) {
                return String(el.dataset.sectionTitle || '').trim() === secTitle;
            }) || null;
        }
        if (!root) return [];
        const refs = [];
        root.querySelectorAll('input[type=\"radio\"][data-ref-id]').forEach(el => {
            const name = String(el.name || '');
            if (/bemerkung/i.test(name)) return;
            if (!/\]\[(M|O)\.\d/i.test(name)) return;
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
    const inLevelsPane = !!title.closest('.live-assess-levels-pane');

    if (!inLevelsPane && localStorage.getItem(key) === '1'){
        fields.classList.add('collapsed');
        title.classList.add('open');
    }
    if (inLevelsPane) {
        fields.classList.remove('collapsed');
        title.classList.remove('open');
        title.addEventListener('click', ()=>{
            const collapsed = fields.classList.toggle('collapsed');
            title.classList.toggle('open', collapsed);
        });
        return;
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

// ---------------- DIRECT CHECK (Mehrfachklick / Teilpunkte) ----------------
(function(){
    const DC_GAP = 420;

    function dcApplyMain(prev, n) {
        if (prev !== 'none' && n === 1) return 'none';
        var base = prev;
        if (prev !== 'none' && n > 1) base = 'none';
        if (base === 'none') {
            if (n === 1) return 'green';
            if (n === 2) return 'orange';
            if (n >= 3) return 'red';
        }
        return prev;
    }

    function dcRead(root) {
        var h = root.querySelector('.dc-json');
        try { return JSON.parse(h.value || '{}'); } catch (e) {
            return { v:3, main:{}, subHit:{}, obs:'' };
        }
    }

    function dcWrite(root, data) {
        root.querySelector('.dc-json').value = JSON.stringify({
            v: 3,
            main: data.main || {},
            subHit: data.subHit || {},
            obs: data.obs || ''
        });
        root.querySelector('.dc-json').dispatchEvent(new Event('input', { bubbles: true }));
        root.querySelector('.dc-json').dispatchEvent(new Event('change', { bubbles: true }));
    }

    function dcPersistFromUi(root) {
        var data = dcRead(root);
        data.main = data.main || {};
        data.subHit = data.subHit || {};
        root.querySelectorAll('.dc-main').forEach(function(btn) {
            var i = String(parseInt(btn.dataset.idx, 10));
            data.main[i] = btn.dataset.state || 'none';
        });
        root.querySelectorAll('.dc-sub-chip').forEach(function(btn) {
            var mi = String(parseInt(btn.dataset.mainIdx, 10));
            var j = String(parseInt(btn.dataset.idx, 10));
            if (!data.subHit[mi]) data.subHit[mi] = {};
            if (btn.classList.contains('is-hit')) data.subHit[mi][j] = true;
            else delete data.subHit[mi][j];
        });
        Object.keys(data.subHit).forEach(function(mi) {
            if (data.subHit[mi] && typeof data.subHit[mi] === 'object' && Object.keys(data.subHit[mi]).length === 0) {
                delete data.subHit[mi];
            }
        });
        var ta = root.querySelector('.dc-obs');
        if (ta) data.obs = ta.value;
        dcWrite(root, data);
    }

    function dcSetMainBtn(btn, state) {
        btn.dataset.state = state;
        btn.classList.remove('dc-state-none','dc-state-green','dc-state-orange','dc-state-red');
        btn.classList.add('dc-state-' + state);
    }

    function dcUpdateStage(root) {
        var mains = Array.from(root.querySelectorAll('.dc-main'));
        var inner = root.querySelector('.directcheck-stage-inner');
        if (!inner) return;
        if (!mains.length) {
            inner.textContent = 'Keine Punkte';
            inner.className = 'directcheck-stage-inner stage-none';
            return;
        }
        var allG = mains.every(function(b) { return (b.dataset.state || '') === 'green'; });
        var allN = mains.every(function(b) { return (b.dataset.state || '') === 'none'; });
        if (allG) {
            inner.textContent = 'Alle erfüllt';
            inner.className = 'directcheck-stage-inner stage-ok';
        } else if (allN) {
            inner.textContent = 'Noch unbewertet';
            inner.className = 'directcheck-stage-inner stage-none';
        } else {
            inner.textContent = 'Mit Abweichungen';
            inner.className = 'directcheck-stage-inner stage-partial';
        }
    }

    function dcUpdateFollowup(root) {
        var wrap = root.querySelector('[data-dc-followup]');
        if (!wrap) return;
        var show = Array.from(root.querySelectorAll('.dc-main')).some(function(b) {
            var s = b.dataset.state || '';
            return s === 'orange' || s === 'red';
        });
        wrap.hidden = !show;
    }

    function dcUpdateLineSubs(root) {
        root.querySelectorAll('.dc-main').forEach(function(btn) {
            var idx = String(parseInt(btn.dataset.idx, 10));
            var st = btn.dataset.state || 'none';
            var panel = root.querySelector('[data-dc-subs-for=\"' + idx + '\"]');
            if (!panel) return;
            var show = (st === 'orange' || st === 'red') && !!panel.querySelector('ul.directcheck-sub-chips li');
            panel.hidden = !show;
        });
    }

    var dcPending = new WeakMap();
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.dc-main');
        if (!btn || btn.disabled) return;
        var root = btn.closest('[data-dc-root]');
        if (!root || root.dataset.dcReadonly === '1') return;
        e.preventDefault();
        var cur = dcPending.get(btn);
        if (!cur) { cur = { n:0, t:null }; dcPending.set(btn, cur); }
        cur.n += 1;
        if (cur.t) clearTimeout(cur.t);
        cur.t = setTimeout(function() {
            var n = cur.n;
            cur.n = 0;
            cur.t = null;
            var prev = btn.dataset.state || 'none';
            var next = dcApplyMain(prev, n);
            dcSetMainBtn(btn, next);
            dcPersistFromUi(root);
            dcUpdateStage(root);
            dcUpdateFollowup(root);
            dcUpdateLineSubs(root);
        }, DC_GAP);
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.dc-sub-chip');
        if (!btn || btn.disabled) return;
        var root = btn.closest('[data-dc-root]');
        if (!root || root.dataset.dcReadonly === '1') return;
        e.preventDefault();
        btn.classList.toggle('is-hit');
        btn.setAttribute('aria-pressed', btn.classList.contains('is-hit') ? 'true' : 'false');
        dcPersistFromUi(root);
    });

    document.querySelectorAll('[data-dc-root]').forEach(function(root) {
        if (root.dataset.dcReadonly === '1') return;
        var ta = root.querySelector('.dc-obs');
        if (ta) {
            ta.addEventListener('input', function() { dcPersistFromUi(root); });
        }
        dcUpdateStage(root);
        dcUpdateFollowup(root);
        dcUpdateLineSubs(root);
    });
})();

// Rollenmodell: Zusammenfassung Stufen 6–1 + Hinweis an Gesamtstufe bei Teilerfüllung
(function(){
    function rkParseDcJson(inp) {
        try { return JSON.parse(inp.value || '{}'); } catch (e) { return {}; }
    }
    function rkStufeStatusFromMain(main) {
        var m = main || {};
        var keys = Object.keys(m).filter(function(k) { return /^\\d+$/.test(k); });
        if (!keys.length) return 'offen';
        var vals = keys.map(function(k) { return m[k] || 'none'; });
        if (vals.every(function(s) { return s === 'none'; })) return 'offen';
        if (vals.every(function(s) { return s === 'green'; })) return 'erfuellt';
        if (vals.some(function(s) { return s === 'red'; })) return 'kritisch';
        return 'teilweise';
    }
    function rkCollectSlots() {
        var bySlot = {};
        document.querySelectorAll('.directcheck-root[data-rk-stufe-slot]').forEach(function(root) {
            var slot = root.getAttribute('data-rk-stufe-slot');
            if (!slot) return;
            var h = root.querySelector('.dc-json');
            if (!h) return;
            var d = rkParseDcJson(h);
            bySlot[slot] = rkStufeStatusFromMain(d.main);
        });
        return bySlot;
    }
    function rkRenderSummary() {
        var el = document.getElementById('rk-rollenmodell-summary-inner');
        if (!el) return;
        var bySlot = rkCollectSlots();
        var order = ['6','5','4','3','2','1'];
        var anyTeilweise = false;
        var html = '<table class=\"rk-rm-sum-table\" border=\"0\"><thead><tr><th>Stufe</th><th>Status</th></tr></thead><tbody>';
        order.forEach(function(s) {
            var st = bySlot[s] || 'offen';
            var label = '';
            var cls = 'rk-rm-sum-row--offen';
            if (st === 'erfuellt') { label = 'erreicht'; cls = 'rk-rm-sum-row--ok'; }
            else if (st === 'teilweise') { label = 'teilweise'; cls = 'rk-rm-sum-row--teil'; anyTeilweise = true; }
            else if (st === 'kritisch') { label = 'Abweichungen / kritisch'; cls = 'rk-rm-sum-row--bad'; }
            else { label = 'offen / nicht bewertet'; }
            html += '<tr class=\"' + cls + '\"><td>Stufe ' + s + '</td><td>' + label + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
        document.querySelectorAll('[data-rk-gesamtstufe]').forEach(function(wrap) {
            if (anyTeilweise) wrap.classList.add('rk-field-gesamtstufe--warn');
            else wrap.classList.remove('rk-field-gesamtstufe--warn');
        });
    }
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('dc-json')) rkRenderSummary();
    }, true);
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('dc-json')) rkRenderSummary();
    }, true);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', rkRenderSummary);
    } else {
        rkRenderSummary();
    }
})();

</script>
</div>";
}

/* -----------------------------------------------------------
   TEMPLATE MANAGER
----------------------------------------------------------- */
function showTemplateManager(){
    global $rkrkLiveassessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
		
    pageHeaderLocal("Templates für Beurteilungen");

    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
    }

	if ($isGuest){
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates bearbeiten.</div>";
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='".safe(rkrk_assess_append_setting_href("?mode=list"))."' class='btn'>⬅ Zurück</a>
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
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=paperform&file=" . $base . "&from=templates"), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Papierformular zum Ausfüllen' aria-label='Papierformular'>📄</a>
                    <a class='chk-act' href='?mode=templateexport&file=$benc' download='$jsonDl' title='Template als JSON herunterladen (Backup / Bearbeiten extern)' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "</a>
                    <a class='chk-act' href='?mode=copytemplate&file=$benc' onclick='return confirm(\"Template wirklich kopieren?\");' title='Template duplizieren' aria-label='Kopieren'>" . rkrk_checklist_toolbar_svg_copy() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . $benc), ENT_QUOTES, "UTF-8") . "' title='Neue Beurteilung mit diesem Template' aria-label='Neue Beurteilung'>" . rkrk_checklist_toolbar_svg_file_plus() . "</a>
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
    global $rkrkLiveassessTemplates, $InputUserInfo;

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
    $path = "$rkrkLiveassessTemplates/$file";

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
    global $rkrkLiveassessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates löschen.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $path = "$rkrkLiveassessTemplates/$file";

    if (file_exists($path)) unlink($path);

    rkrk_redirect("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE KOPIEREN
----------------------------------------------------------- */
function copyTemplate(){
    global $rkrkLiveassessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates kopieren.</div>";
        return;
    }

    $file = basename($_GET['file'] ?? "");
    $src  = "$rkrkLiveassessTemplates/$file";

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
    $dst = "$rkrkLiveassessTemplates/$newFile";

    $i = 1;
    while (file_exists($dst)) {
        $newFile = $baseName . "_copy_" . $i . ".json";
        $dst = "$rkrkLiveassessTemplates/$newFile";
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
    global $rkrkLiveassessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_export_json_download($rkrkLiveassessTemplates, $f);
    rkrk_redirect("?mode=templates");
}

function handleTemplateJsonImport()
{
    global $rkrkLiveassessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest || (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST")) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_POST["file"] ?? ""));
    $code = rkrk_template_import_json_upload($rkrkLiveassessTemplates, $f, $_FILES["jsonfile"] ?? null);
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
    global $rkrkLiveassessTemplates, $InputUserInfo;
    $isGuest = false;
    if ($isGuest) {
        rkrk_redirect("?mode=templates");
    }
    $f = basename((string)($_GET["file"] ?? ""));
    rkrk_template_print_html($rkrkLiveassessTemplates, $f);
    rkrk_redirect("?mode=templates");
}

/* -----------------------------------------------------------
   TEMPLATE EDITOR
----------------------------------------------------------- */
function editTemplate(){
    global $rkrkLiveassessTemplates, $InputUserInfo;

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
    echo "<a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=paperform&file=" . $file . "&from=templates"), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Papierformular: leer zum Ausfüllen per Hand' aria-label='Papierformular'>📄</a>";
    echo "<a class='chk-tbtn' href='?mode=templateexport&file=$fenc' download='$jsonDlName' title='Template als JSON herunterladen' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "<span>JSON exportieren</span></a>";
    echo "<form class='chk-import' method='post' action='?mode=templateimport' enctype='multipart/form-data' title='JSON importieren'>";
    echo "<input type='hidden' name='file' value='" . safe($file) . "'>";
    echo "<label style='margin:0;font-size:13px;color:#5f6b66;' title='Vorlage: zuerst exportieren, bearbeiten, dann hier wieder einlesen'>Import JSON:</label>";
    echo "<input type='file' name='jsonfile' accept='.json,application/json' required title='UTF-8 JSON mit sections'>";
    echo "<button class='chk-tbtn' type='submit' title='Ersetzt die gesamte Template-Datei auf dem Server' aria-label='JSON importieren' onclick='return confirm(\"Import ersetzt diese Template-Datei vollstaendig durch die hochgeladene JSON-Datei. Fortfahren?\");'>" . rkrk_checklist_toolbar_svg_import() . "<span>Importieren</span></button>";
    echo "</form>";
    echo "<span class='chk-toolbar-hint'>Struktur wie beim Export; Feld <code>sections</code> ist Pflicht.</span>";
    echo "</div>";

    $refTpl = "";
    if (is_array($tpl) && isset($tpl["_meta"]) && is_array($tpl["_meta"])) {
        $refTpl = trim((string)($tpl["_meta"]["reference_template"] ?? ""));
    }
    if ($refTpl !== "") {
        $refEnc = rawurlencode($refTpl);
        echo "<div class='rkrk-card' style='background:#f8fafc;border-color:#cbd5e1;'>";
        echo "<strong>Referenz-Template:</strong> ";
        echo "<a href='?mode=edittemplate&file=$refEnc'>".safe($refTpl)."</a>";
        echo " <span style='color:#64748b;font-size:13px;'>(Original bleibt; Bahn/Parameter dieser Version separat)</span>";
        echo "</div>";
    }

    echo "<div class='rkrk-card'>
        <form method='post' action='?mode=savetemplate'>
		<input type='hidden' id='weather_json' name='weather_json'>
        <input type='hidden' name='file' value='".safe($file)."'>";
?>
<script>
async function loadWeatherForChecklist() {

    // 1) Standort aus der Beurteilung holen (falls vorhanden)
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

    echo "<table class='template-table'>
            <tr>
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
            $subchecks = "";
            $options = "";
            $ref = "";
            $calc = "";
            $inname = "";
            $inresult = "";
            $stufeSlot = "";
            $typeVal = $type;

            if (is_array($type)){
                $placeholder = isset($type["placeholder"]) ? $type["placeholder"] : "";
                $info = isset($type["info"]) ? $type["info"] : "";
                $process = isset($type["process"]) ? $type["process"] : "";
                $checks = isset($type["checks"]) ? $type["checks"] : "";
                $subchecks = isset($type["subchecks"]) ? $type["subchecks"] : "";
                $options = isset($type["options"]) ? $type["options"] : "";
                $ref = isset($type["ref"]) ? $type["ref"] : "";
                $calc = isset($type["calc"]) ? $type["calc"] : "";
                $inname = isset($type["inname"]) ? $type["inname"] : "0";
                $inresult = isset($type["inresult"]) ? $type["inresult"] : "0";
                $stufeSlot = isset($type["stufe_slot"]) ? trim((string)$type["stufe_slot"]) : "";
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
                "subchecks"   => $subchecks,
                "options"     => $options,
                "ref"         => $ref,
                "calc"        => $calc,
                "inname"      => $inname,
                "inresult"    => $inresult,
                "stufe_slot"  => $stufeSlot
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
		  "directcheck"=>"Direct check",
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
                <td></td>
                <td colspan='5'>
                    <div class='template-detail-grid'>
                        <div>
                            <small class='template-field-label'>Optionen / Berechnung (Summe/CountFails)</small>
                            <div class='template-options-calc-cell'>
                                <textarea class='template-options-input' name='options[$i]' rows='2' placeholder='a|b|c' style='width:100%;'>".safe($optVal)."</textarea>
                                <input class='template-calc-input' type='text' name='calc[$i]' value='".safe($row["calc"])."' placeholder='z.B. R001+R002 oder R010|R011' style='width:100%; margin-top:6px; display:none;'>
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
                        <div style='grid-column:1/-1'>
                            <small class='template-field-label'>Teilpunkte pro Kontrollzeile (subchecks) — Blöcke in Zeile nur <code>---</code> trennen (Reihenfolge = checks)</small>
                            <textarea name='subchecks[$i]' rows='3' placeholder='Pro Block: Zeilen a) b) … · nächster Block nach ---' style='width:100%;'>".safe($row["subchecks"] ?? "")."</textarea>
                        </div>
                        <div>
                            <small class='template-field-label'>Stufe-Slot (nur Direct check, optional)</small>
                            <input type='text' name='stufe_slot[$i]' value='".safe($row["stufe_slot"] ?? "")."' maxlength='1' inputmode='numeric' pattern='[1-6]' placeholder='1–6' style='max-width:5rem' title='Ordnet das Feld der Rollenmodell-Zeile Stufe 1–6 zu (alternativ: „Stufe 6 …“ im Label).'>
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
                    <option value='directcheck'>Direct check</option>
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
                            <input class='template-calc-input' type='text' name='new_calc' placeholder='z.B. R001+R002 oder R010|R011' style='margin-top:6px; width:100%; display:none;'>
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
                    <div style='grid-column:1/-1'>
                        <small class='template-field-label'>Teilpunkte pro Kontrollzeile · Blöcke mit Zeile <code>---</code> trennen</small>
                        <textarea name='new_subchecks' rows='3' placeholder='Block 1 Zeilen · dann --- · Block 2 …' style='width:100%;'></textarea>
                    </div>
                    <div>
                        <small class='template-field-label'>Stufe-Slot (nur Direct check)</small>
                        <input type='text' name='new_stufe_slot' maxlength='1' inputmode='numeric' pattern='[1-6]' placeholder='1–6' style='max-width:5rem' title='Rollenmodell-Zeile 1–6'>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class='template-save-bar' style='display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:8px;'>
      <button class='btn btn-primary' type='submit' name='save_mode' value='overwrite'>Template speichern</button>
      <button class='btn secondary' type='submit' name='save_mode' value='new_version'
              title='Original bleibt unverändert; speichert Bahn/Parameter als neue Template-Datei'
              onclick="var s=document.getElementById('save_as_stem'); if(s && !s.value.trim()){ s.value=document.querySelector('[name=title]').value + ' Version'; }">
        Als neue Version speichern
      </button>
      <label style='font-size:13px;color:#475569;display:flex;align-items:center;gap:6px;'>
        Dateiname (optional):
        <input type='text' id='save_as_stem' name='save_as_stem' class='text-input'
               placeholder='z.B. basispruefung_rkrs_papier_v6'
               style='min-width:16rem;'
               title='Ohne Angabe: Titel + Version. Original-Datei wird nicht überschrieben.'>
      </label>
    </div>
    <p style='margin:8px 0 0;font-size:12px;color:#64748b;'>
      <strong>Neue Version:</strong> Original bleibt als Referenz. Neue Bahn und Parameter liegen in der neuen Datei und können für Beurteilungen übernommen werden.
    </p>
    </form>
    <div class='rkrk-card' style='margin-top:14px; background:#f8fbf6; border-color:#d6dfcc;'>
        <strong>Legende Berechnungskriterien</strong>
        <div style='margin-top:8px; font-size:13px; color:#35523a; line-height:1.5;'>
            <div><strong>Hidden:</strong> Speichert einen Textwert im JSON wie ein Textfeld, zeigt im Formular aber kein sichtbares Feld. Vorbelegung per URL-Parameter wie bei Text möglich.</div>
            <div><strong>Summe:</strong> Rechnet numerische Werte aus Referenzen, z.B. <code>R010+R011-R012</code>.</div>
            <div><strong>CountFails:</strong> Zählt nur Referenz-Felder mit ausgewählter Option <code>(r)</code>. Für gezielte Berechnung im Feld <em>Berechnung</em> z.B. <code>R010|R011|R012</code> eintragen.</div>
            <div><strong>CountCritical:</strong> Zählt Referenz-Felder mit ausgewählter Option <code>(r)</code> oder <code>(o)</code>. Ebenfalls per Referenzliste steuerbar, z.B. <code>R020|R021</code>.</div>
            <div style='margin-top:8px;'><strong>Optionen für Auswahltypen:</strong></div>
            <div><strong>Radio:</strong> Genau eine Auswahl. Optionen mit <code>|</code> trennen, z.B. <code>Erfüllt (g)|Teilweise (o)|Nicht erfüllt (r)</code>.</div>
            <div><strong>Multi:</strong> Mehrfachauswahl. Optionen ebenfalls mit <code>|</code> trennen, z.B. <code>Kriterium A (g)|Kriterium B (o)|Kriterium C (r)</code>.</div>
            <div><strong>Checkbox:</strong> Einzelnes Ja/Nein-Feld (0/1). Normalerweise keine Optionen nötig; falls Farblogik benötigt wird, Marker direkt im Feldnamen setzen, z.B. <code>Schutzbrille (o)</code>.</div>
            <div><strong>Farbmarker:</strong> <code>(g)</code> = grün, <code>(o)</code> = orange, <code>(r)</code> = rot, <code>(n)</code> = nicht bewertet/neutral (Ist+Max=0, nicht in CountFails/CountCritical).</div>
            <div style='margin-top:8px;'><strong>Direct check:</strong> <em>checks</em> = Hauptzeilen; <em>subchecks</em> = je Kontrollpunkt ein Block (Zeilen a,b,…), Blöcke mit eigener Zeile <code>---</code> in Reihenfolge der Hauptzeilen. Orange/rot: Teilpunkte nur unter dieser Zeile. Optional <em>Stufe-Slot</em> 1–6 oder Label mit „Stufe 6 …“ für die Rollenmodell-Übersicht.</div>
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
            <a href='".safe(rkrk_assess_append_setting_href("?mode=templates"))."' class='btn'>⬅ Zurück</a>
          </div>";

    pageFooterLocal();
}

/* -----------------------------------------------------------
   TEMPLATE SPEICHERN
----------------------------------------------------------- */
function saveTemplate(){
    global $rkrkLiveassessTemplates, $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Templates speichern.</div>";
        return;
    }

    $file  = basename($_POST['file']);
    $title = trim($_POST['title'] ?? "");
    $saveMode = trim((string)($_POST["save_mode"] ?? "overwrite"));
    $saveAsStem = trim((string)($_POST["save_as_stem"] ?? ""));
    $sourceFileForRef = $file;

    /* Neue Version: Original unverändert lassen, unter neuem Namen speichern */
    if ($saveMode === "new_version") {
        $stemBase = $saveAsStem !== "" ? $saveAsStem : ($title . " Version");
        $cand = slugifyTitle($stemBase) . ".json";
        $dst = "$rkrkLiveassessTemplates/$cand";
        if ($cand === $file || is_file($dst)) {
            $base = pathinfo($cand, PATHINFO_FILENAME);
            $i = 2;
            do {
                $cand = $base . "_" . $i . ".json";
                $dst = "$rkrkLiveassessTemplates/$cand";
                $i++;
            } while (is_file($dst) || $cand === $file);
        }
        $file = $cand;
        $oldFile = $file;
        $newFile = $file;
    } else {
    /* Template-Dateiname ggf. ändern (nur bei normalem Speichern) */
    $oldFile = $file;
    $newFile = slugifyTitle($title) . ".json";

    if ($newFile !== $oldFile) {
        $oldPath = "$rkrkLiveassessTemplates/$oldFile";
        $newPath = "$rkrkLiveassessTemplates/$newFile";

        /* Falls Zielname existiert → _2, _3, ... */
        if (file_exists($newPath)) {
            $base = pathinfo($newFile, PATHINFO_FILENAME);
            $i = 2;
            do {
                $newFile = $base . "_" . $i . ".json";
                $newPath = "$rkrkLiveassessTemplates/$newFile";
                $i++;
            } while (file_exists($newPath));
        }

        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }

        /* Ab hier mit neuem Dateinamen weiterarbeiten */
        $file = $newFile;
    }
    } /* end else overwrite */

    /* Eingabefelder einlesen */
    $secArr   = isset($_POST['sec']) ? $_POST['sec'] : [];
    $labelArr = isset($_POST['label']) ? $_POST['label'] : [];
    $typeArr  = isset($_POST['type']) ? $_POST['type'] : [];
    $placeholderArr = isset($_POST['placeholder']) ? $_POST['placeholder'] : [];
    $infoArr  = isset($_POST['info']) ? $_POST['info'] : [];
    $processArr = isset($_POST['process']) ? $_POST['process'] : [];
    $checksArr = isset($_POST['checks']) ? $_POST['checks'] : [];
    $subchecksArr = isset($_POST['subchecks']) ? $_POST['subchecks'] : [];
    $refArr = isset($_POST['ref']) ? $_POST['ref'] : [];
    $calcArr = isset($_POST['calc']) ? $_POST['calc'] : [];
    $optionsArr = isset($_POST['options']) ? $_POST['options'] : [];
    $innameArr = isset($_POST['inname']) ? $_POST['inname'] : [];
    $inresultArr = isset($_POST['inresult']) ? $_POST['inresult'] : [];
    $stufeSlotArr = isset($_POST['stufe_slot']) ? $_POST['stufe_slot'] : [];

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
        $subchecks = trim(isset($subchecksArr[$i]) ? $subchecksArr[$i] : "");
        $stufeSlot = trim(isset($stufeSlotArr[$i]) ? $stufeSlotArr[$i] : "");
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
            "subchecks"   => $subchecks,
            "ref"         => $ref,
            "calc"        => $calc,
            "inname"      => $inname,
            "inresult"    => $inresult,
            "stufe_slot"  => $stufeSlot
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
        $newSubchecks = isset($_POST['new_subchecks']) ? trim($_POST['new_subchecks']) : "";
        $newStufeSlot = isset($_POST['new_stufe_slot']) ? trim((string)$_POST['new_stufe_slot']) : "";
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
            "subchecks"   => $newSubchecks,
            "ref"         => $newRef,
            "calc"        => $newCalc,
            "inname"      => $newinname,
            "inresult"    => $newinresult,
            "stufe_slot"  => $newStufeSlot
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
        if (isset($row["subchecks"]) && $row["subchecks"] !== "") $entry["subchecks"] = $row["subchecks"];
        if (isset($row["options"]) && trim((string)$row["options"]) !== "") $entry["options"] = trim((string)$row["options"]);
        if (isset($row["ref"]) && $row["ref"] !== "") $entry["ref"] = $row["ref"];
        if (isset($row["calc"]) && $row["calc"] !== "") $entry["calc"] = $row["calc"];

        $entry["inname"]   = $row["inname"] === "1" ? "1" : "0";
        $entry["inresult"] = $row["inresult"] === "1" ? "1" : "0";

        if ($row["type"] === "directcheck") {
            $ss = trim((string)($row["stufe_slot"] ?? ""));
            if ($ss !== "" && preg_match('/^[1-6]$/', $ss)) {
                $entry["stufe_slot"] = $ss;
            }
        }

        $sections[$row["sec"]][$row["label"]] = $entry;
    }

    /* Template speichern — Metadaten aus bestehender Datei uebernehmen */
    $tpl = [
        "title" => $title,
        "sections" => $sections
    ];
    $metaSourcePath = "$rkrkLiveassessTemplates/$sourceFileForRef";
    if (!is_file($metaSourcePath)) {
        $metaSourcePath = "$rkrkLiveassessTemplates/$file";
    }
    if (is_file($metaSourcePath)) {
        $existing = json_decode((string)@file_get_contents($metaSourcePath), true);
        if (is_array($existing)) {
            foreach (["_meta", "_description", "_settings"] as $metaKey) {
                if (isset($existing[$metaKey]) && is_array($existing[$metaKey])) {
                    $tpl[$metaKey] = $existing[$metaKey];
                }
            }
        }
    }
    if ($saveMode === "new_version") {
        if (!isset($tpl["_meta"]) || !is_array($tpl["_meta"])) {
            $tpl["_meta"] = [];
        }
        $tpl["_meta"]["reference_template"] = $sourceFileForRef;
        $tpl["_meta"]["saved_as_version"] = date("Y-m-d");
        $tpl["_meta"]["note"] = "Neue Feldprogrammierung (Bahn/Parameter). Referenz: " . $sourceFileForRef;
    }

    file_put_contents("$rkrkLiveassessTemplates/$file", json_encode($tpl, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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
    global $rkrkLiveassessResults , $rkrkLiveassessTemplates, $IconLoc, $examIconLoc, $logopic;

    $id = basename($_GET['id'] ?? "");
    $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/$id.json";

    if (!file_exists($path)) {
        echo "Beurteilung nicht gefunden.";
        if (!empty($GLOBALS["rkrk_assess_print_no_exit"])) {
            return;
        }
        exit;
    }

    $data = json_decode(file_get_contents($path), true);
    $tplName = $data["template"] ?? "";
    $tpl = loadTemplate($tplName);

    $title = $tpl["title"] ?? "Beurteilung";
    $fields = $data["fields"] ?? [];
    $refMaxMap = rkrk_build_ref_max_map($tpl["sections"] ?? array());

    // 🔥 Print-Dateiname = gespeicherter JSON-Dateiname
    $printTitle = $id . " – Beurteilung – Druckansicht";
    $compactReport = !empty($GLOBALS['rkrk_assess_print_hide_unrated']) || !empty($GLOBALS['rkrk_assess_print_compact']);
    $showAblauf = !$compactReport && (!isset($_GET['hide_ablauf']) || $_GET['hide_ablauf'] !== '1');
    $showKriterien = !isset($_GET['hide_kriterien']) || $_GET['hide_kriterien'] !== '1';
    $showPrintType = !$compactReport && (!isset($_GET['hide_printtype']) || $_GET['hide_printtype'] !== '1');
    $showReferenz = !$compactReport && (!isset($_GET['hide_referenz']) || $_GET['hide_referenz'] !== '1');
    $showCalcMeta = !$compactReport;
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
    // Print-Dokument hat eigenes <head> (kein headerpage) — Env-Favicon hier setzen
    if (function_exists("rkrk_assess_favicon_link_tags_html")) {
        echo rkrk_assess_favicon_link_tags_html();
    }
    rkrk_assess_theme_link_tags();
    $printThemeLinks = ob_get_clean();
    $printSettingHidden = "";
    if (rkrk_assess_ci_rkrs_mode()) {
        $psk = htmlspecialchars((string)$GLOBALS["AddURL"], ENT_QUOTES, "UTF-8");
        $printSettingHidden = "<input type='hidden' name='" . $psk . "' value=''>";
    }

    // Erreichte Gesamtstufe für Header-Banner (wie Resultat-Zeile im digitalen Abschlussbericht)
    $gesamtLabel = "";
    $gesamtValRaw = "";
    $gesamtLevel = "";
    foreach ((array)$fields as $__sec => $__secFields) {
        if (!is_array($__secFields)) {
            continue;
        }
        foreach ($__secFields as $__lab => $__val) {
            if (stripos((string)$__lab, "Gesamtstufe") === false) {
                continue;
            }
            $__v = is_array($__val) ? implode(", ", $__val) : trim((string)$__val);
            if ($__v === "") {
                continue;
            }
            $gesamtLabel = (string)$__lab;
            $gesamtValRaw = $__v;
            if (preg_match('/^\s*(?:stufe\s*)?([1-6])\b/ui', $__v, $__m) === 1) {
                $gesamtLevel = (string)$__m[1];
            } elseif (preg_match('/\bstufe\s*([1-6])\b/ui', $__v, $__m) === 1) {
                $gesamtLevel = (string)$__m[1];
            }
            break 2;
        }
    }
    $gesamtValClean = $gesamtValRaw;
    if (function_exists("rkrk_strip_option_markers")) {
        $gesamtValClean = (string)rkrk_strip_option_markers($gesamtValRaw);
    }
    $gesamtScoreTxt = $gesamtValClean;
    if ($gesamtLevel !== "" && preg_match('/^\s*' . preg_quote($gesamtLevel, '/') . '\s*[-–—:]\s*(.+)$/u', $gesamtValClean, $__gm) === 1) {
        $gesamtScoreTxt = trim((string)$__gm[1]);
    }
    $stufeBg = "#f1f5f9";
    $stufeFg = "#0f172a";
    if ($gesamtLevel === "1") { $stufeBg = "#fceaea"; $stufeFg = "#7f1d1d"; }
    elseif ($gesamtLevel === "2") { $stufeBg = "#fff6dc"; $stufeFg = "#7c4a03"; }
    elseif ($gesamtLevel === "3") { $stufeBg = "#eef6ec"; $stufeFg = "#3d6b3a"; }
    elseif ($gesamtLevel === "4") { $stufeBg = "#e8f4ea"; $stufeFg = "#3d6b3a"; }
    elseif ($gesamtLevel === "5") { $stufeBg = "#d9f0ec"; $stufeFg = "#0f4f46"; }
    elseif ($gesamtLevel === "6") { $stufeBg = "#e2ecf8"; $stufeFg = "#1e3a8a"; }
    $stufeBannerHtml = "";
    if ($gesamtValClean !== "") {
        $stufeLeft = ($gesamtLevel !== "")
            ? ("Stufe " . htmlspecialchars($gesamtLevel, ENT_QUOTES, "UTF-8"))
            : "Gesamtstufe";
        $stufeRight = htmlspecialchars($gesamtScoreTxt !== "" ? $gesamtScoreTxt : $gesamtValClean, ENT_QUOTES, "UTF-8");
        // Resultat-Banner unter Allgemein — TCPDF: bgcolor + Inline-Styles (wie Digital «Bestanden»)
        $stufeBannerHtml =
            "<table class=\"pf-legend pf-legend-stufe\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\""
            . htmlspecialchars($stufeBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"width:100%;margin:4px 0 8px;padding:0;border:none;border-collapse:collapse;background-color:"
            . htmlspecialchars($stufeBg, ENT_QUOTES, "UTF-8") . ";\">"
            . "<tr>"
            . "<td class=\"result-label\" valign=\"middle\" width=\"40%\" bgcolor=\""
            . htmlspecialchars($stufeBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"font-size:12pt;font-weight:800;line-height:1.2;color:"
            . htmlspecialchars($stufeFg, ENT_QUOTES, "UTF-8") . ";padding:6px 8px;\">"
            . "<font color=\"" . htmlspecialchars($stufeFg, ENT_QUOTES, "UTF-8") . "\"><b>" . $stufeLeft . "</b></font>"
            . "</td>"
            . "<td class=\"result-score\" align=\"right\" valign=\"middle\" width=\"60%\" bgcolor=\""
            . htmlspecialchars($stufeBg, ENT_QUOTES, "UTF-8")
            . "\" style=\"font-size:9.5pt;font-weight:700;line-height:1.25;text-align:right;color:"
            . htmlspecialchars($stufeFg, ENT_QUOTES, "UTF-8") . ";padding:6px 8px;\">"
            . "<font color=\"" . htmlspecialchars($stufeFg, ENT_QUOTES, "UTF-8") . "\"><b>" . $stufeRight . "</b></font>"
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

<body class='rkrk-assess-print rkrk-live-report pf-report'>
<table class='print-header-table pf-header print-header' width='100%' border='0' cellpadding='0' cellspacing='0'><tr>
    <td class='print-title' valign='top' width='68%'>
        <div style='font-size:13pt;font-weight:bold;line-height:1.2;color:#111;'>".htmlspecialchars($title)."</div>
        <span class='print-subtitle pf-sub' style='display:block;font-size:9pt;color:#475569;font-weight:normal;margin-top:2px;'>Beurteilung</span>
    </td>
    <td class='print-logo pf-logo' align='right' valign='top' width='32%' style='padding:0;text-align:right;'><div style='height:40px;width:124px;'></div></td>
</tr></table>
<form method='get' class='print-options no-print'>
    <input type='hidden' name='mode' value='print'>
    <input type='hidden' name='id' value='".htmlspecialchars($id, ENT_QUOTES)."'>
    ".$printSettingHidden."
    <label><input type='checkbox' name='hide_ablauf' value='1' ".($showAblauf ? "" : "checked")."> Ablauf ausblenden</label>
    <label><input type='checkbox' name='hide_kriterien' value='1' ".($showKriterien ? "" : "checked")."> Kriterien ausblenden</label>
    <label><input type='checkbox' name='hide_printtype' value='1' ".($showPrintType ? "" : "checked")."> Print-Text ausblenden</label>
    <label><input type='checkbox' name='hide_referenz' value='1' ".($showReferenz ? "" : "checked")."> Referenz/NKL ausblenden</label>
    <button type='submit'>Ansicht aktualisieren</button>
</form>
";

    /* ----------------------------------------
       1) TEMPLATE-FELDER DRUCKEN (zweispaltig)
    ---------------------------------------- */
    $stufeBannerPending = ($stufeBannerHtml !== "");
    foreach ($tpl["sections"] as $secName => $secFields) {
        $printSecName = ($secName === "Wetter") ? "Wetter zum Startzeitpunkt" : $secName;
        ob_start();
        $sectionFieldCount = 0;
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
            $checksPrint = "";
            $subchecksPrint = "";
            $referencePrint = "";
            if (is_array($meta)) {
                $calc = trim((string)($meta["calc"] ?? ""));
                $process = trim((string)($meta["process"] ?? ""));
                $referencePrint = trim((string)($meta["reference"] ?? ""));
                $checksPrint = trim((string)($meta["checks"] ?? ""));
                $subchecksPrint = trim((string)($meta["subchecks"] ?? ""));
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
                if (strip_tags($printHtml) !== $printHtml && function_exists("rkrk_assess_format_template_html_fragment")) {
                    $valHtml = rkrk_assess_format_template_html_fragment($printHtml, "print");
                }
            } elseif ($typeString === "directcheck") {
                $valHtml = rkrk_directcheck_format_print_html((string)$val, $checksPrint, $subchecksPrint);
                // Report: Blöcke ohne bewertete Punkte komplett ausblenden
                if (!empty($GLOBALS["rkrk_assess_print_hide_unrated"]) && trim(strip_tags($valHtml)) === "") {
                    continue;
                }
            } elseif ($typeString === "checkbox" || strpos($typeString, "radio:") === 0 || strpos($typeString, "multi:") === 0) {
                $valHtml = colorizePrintValue($val, $typeString);
            } else {
                $valHtml = rkrk_print_weather_value($secName, $label, $val);
            }

            // Druckansicht: leere Bemerkungen / Beobachtungen ausblenden
            if (($isRemarkField || $isBeobachtungField) && trim((string)$val) === "") {
                continue;
            }
            // Live-Report: leere Freitext-/Ergebnis-Felder ohne Inhalt ausblenden
            if (
                !empty($GLOBALS["rkrk_assess_print_hide_unrated"])
                && $typeString !== "print"
                && $typeString !== "directcheck"
                && trim((string)$val) === ""
                && trim(strip_tags((string)$valHtml)) === ""
            ) {
                continue;
            }
            $missedItems = array();
            if (isset($fields[$secName]) && is_array($fields[$secName])) {
                $missedItems = rkrk_find_not_fulfilled_items_for_field($fields[$secName], (string)$label, $pointCode);
            }
            // Directcheck: gespeicherte «Nicht erfüllte Punkte» (Bemerkungs-Chips) in den Block einfügen
            if ($typeString === "directcheck" && $showKriterien && !empty($missedItems)
                && strpos((string)$valHtml, "Nicht erfüllte Punkte:") === false) {
                $nfBits =
                    "<div class=\"dc-print-subcap\" style=\"margin:3px 0 0;font-size:7.5pt;color:#64748b;\">"
                    . "<font color=\"#64748b\">" . safe("Nicht erfüllte Punkte:") . "</font></div>";
                foreach ($missedItems as $mi) {
                    $nfBits .=
                        "<div class=\"dc-print-row dc-print-sub-indent\" style=\"margin:0;padding:0 0 0 12px;font-size:8pt;border:none;color:#9a3412;\">"
                        . "<font color=\"#9a3412\">✗ " . safe((string)$mi) . "</font></div>";
                }
                if (substr((string)$valHtml, -6) === "</div>") {
                    $valHtml = substr((string)$valHtml, 0, -6) . $nfBits . "</div>";
                } else {
                    $valHtml .= $nfBits;
                }
                $missedItems = array(); // nicht nochmals als Meta
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
            if ($showReferenz && $referencePrint !== "") {
                $metaInfo .= "<div class='value-meta value-meta-html rk-drone-req-print'><strong>NKL-Spezifikation:</strong> " . $referencePrint . "</div>";
            }
            if ($process !== "") {
                if (rkrk_process_is_html($process)) {
                    if ($showReferenz) {
                        $metaInfo .= "<div class='value-meta value-meta-html rk-drone-req-print'><strong>Referenz:</strong> " . $process . "</div>";
                    }
                } elseif ($showAblauf) {
                    $metaInfo .= "<div class='value-meta'><strong>Ablauf:</strong> " . nl2br(safe($process)) . "</div>";
                }
            }
            if ($showKriterien && !empty($missedItems)) {
                $metaInfo .= "<div class='value-meta' style=\"margin-top:2px;\"><strong>Nicht erfüllte Punkte:</strong> "
                    . "<font color=\"#9a3412\">" . safe(implode(" · ", $missedItems)) . "</font></div>";
            }

            // Gesamtstufe steht im Header-Banner — im Report-Body nicht nochmals
            if ($stufeBannerHtml !== "" && stripos((string)$label, "Gesamtstufe") !== false) {
                continue;
            }

            // Spezialfelder fett
            $isHighlight = in_array($label, [
                "Auftrag", "Feld", "Ort", "Flugort", "Flugzeit"
            ], true);
            $isGesamt = (stripos((string)$label, "Gesamtstufe") !== false);

            $cls = ($isHighlight || $isGesamt) ? "field highlight" : "field";
            if ($isGesamt) {
                $cls .= " field-gesamtstufe";
            }
            if ($showReferenz && (($process !== "" && rkrk_process_is_html($process)) || $referencePrint !== "")) {
                $cls .= " field-spec-ref";
            }
            if ($isFullWidthText) {
                $cls .= " field-spec-ref field-beobachtung";
            }
            // Mehrzeilige Inhalte (DirectCheck, Meta, HTML, Beobachtung) gestapelt; sonst einzeilig wie digitaler Abschlussbericht
            $useStack = $isFullWidthText || ($typeString === "directcheck" || $typeString === "print" || $metaInfo !== "" || $isGesamt
                || (is_string($valHtml) && (strpos($valHtml, "<div") !== false || strpos($valHtml, "<br") !== false || strpos($valHtml, "<table") !== false)));
            if ($useStack) {
                $cls .= " pf-field-stack";
            }

            if ($useStack) {
                // Freitext: Zeilenumbrüche im Wert erhalten; Label immer darüber
                $stackVal = $valHtml;
                if ($isFullWidthText && is_string($stackVal) && strpos($stackVal, "<") === false) {
                    $stackVal = nl2br(safe((string) $val));
                }
                $sectionFieldsHtml[] = "<div class=\"" . htmlspecialchars($cls, ENT_QUOTES, "UTF-8") . "\" style=\"margin:0;padding:0;border:none;\">"
                    . "<div class=\"pf-label\" style=\"display:block;font-size:7.5pt;font-weight:700;color:#64748b;margin:0 0 4px;\"><font color=\"#64748b\">" . htmlspecialchars($label) . "</font></div>"
                    . "<div class=\"pf-line\" style=\"display:block;font-size:9.5pt;font-weight:600;color:#111;margin:0;\">{$stackVal}{$metaInfo}</div>"
                    . "</div>";
            } else {
                // Einzeilig wie Digital-Allgemein
                $sectionFieldsHtml[] = "<span class=\"pf-label\" style=\"font-size:7.5pt;font-weight:700;color:#64748b;\"><font color=\"#64748b\">" . htmlspecialchars($label) . ":</font></span> "
                    . "<span class=\"pf-line\" style=\"font-size:9.5pt;font-weight:600;color:#111;\">{$valHtml}</span>";
            }
            $sectionFieldCount++;
        }

        ob_end_clean();
        if ($sectionFieldCount > 0 && !empty($sectionFieldsHtml)) {
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
            // Stufe-Banner direkt unter der Allgemein-Sektion (wie Bestanden unter Allgemein im Digital-Report)
            if ($stufeBannerPending && ($secName === "Allgemein" || stripos((string) $secName, "Allgemein") === 0)) {
                echo $stufeBannerHtml;
                $stufeBannerPending = false;
            }
        }
    }
    if (!empty($stufeBannerPending)) {
        echo $stufeBannerHtml;
        $stufeBannerPending = false;
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
        echo "<div class='section pf-sec' style='margin:6px 0 4px;padding-top:4px;border:none;'>";
        echo "<h2 style='margin:0 0 4px;font-size:9.5pt;color:#1e3a1e;font-weight:bold;'>Zusätzliche Felder</h2>";
        echo "<table class='field-grid pf-sec-grid' width='100%' border='0' cellpadding='0' cellspacing='2' style='width:100%;border-collapse:collapse;border:none;'>";
        $__extraCells = array();
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
                $__extraCells[] = "<div class='field' style='margin:0;padding:0;border:none;font-size:9.5pt;line-height:1.25;'>"
                    . "<span class='label' style='font-size:7.5pt;font-weight:700;color:#64748b;'><font color='#64748b'>".htmlspecialchars($label).":</font></span> "
                    . "<span class='value' style='font-size:9.5pt;font-weight:600;color:#111;'>{$valueHtml}</span>"
                    . "</div>";
            }
        }
        $__n = count($__extraCells);
        $__cellStyle = "width:50%;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;";
        for ($__i = 0; $__i < $__n; $__i += 2) {
            echo "<tr>";
            echo "<td width='50%' valign='top' class='field-cell' style='".$__cellStyle."'>".$__extraCells[$__i]."</td>";
            echo "<td width='50%' valign='top' class='field-cell' style='".(isset($__extraCells[$__i + 1]) ? $__cellStyle : "border:none;background:transparent;")."'>".($__extraCells[$__i + 1] ?? "&nbsp;")."</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }

    echo "</body></html>";
}

/* -----------------------------------------------------------
   examEN-LISTE
----------------------------------------------------------- */
function showChecklistList($showAll = false){
    global $rkrkLiveassessResults , $rkrkLiveassessTemplates;

    // Lokaler Modus – verhindert Undefined Variable Notices
    $mode = $_GET['mode'] ?? 'list';
    $filterTemplate = trim((string)($_GET['f_template'] ?? ''));
    $filterPilot = trim((string)($_GET['f_pilot'] ?? ''));
    $filterText = trim((string)($_GET['f_text'] ?? ''));
    $filterDateFrom = trim((string)($_GET['f_from'] ?? ''));
    $filterDateTo = trim((string)($_GET['f_to'] ?? ''));
    $hasFilters = ($filterTemplate !== '' || $filterPilot !== '' || $filterText !== '' || $filterDateFrom !== '' || $filterDateTo !== '');

    pageHeaderLocal("Beurteilungen");
    if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
        rkrk_checklist_toolbar_echo_styles_once();
    }

    if(isset($_GET['saved']))
        echo "<div class='rkrk-card success'>✓ Beurteilung gespeichert.</div>";

    if(isset($_GET['deleted']))
        echo "<div class='rkrk-card danger'>✓ Eintrag gelöscht.</div>";

    echo "<div class='rkrk-card checklist-header'>
        <div class='checklist-title'>Neue Beurteilung</div>
        <div class='checklist-buttons'>";

    echo "<a href='".safe(rkrk_assess_append_setting_href("rkrk_assess_live_personel.php?view=list"))."' class='btn secondary'>"
        . rkrk_checklist_toolbar_svg_user_plus()
        . "<span>Personal / Piloten</span></a>";

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
            <h3>".($showAll ? "Alle gespeicherten Beurteilungen" : "Letzte 12 gespeicherte Beurteilungen")."</h3>";
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

    $jsonPattern = rtrim((string)$rkrkLiveassessResults, "/\\") . "/*.json";
    $jsonFiles = glob($jsonPattern);
    if (!is_array($jsonFiles)) $jsonFiles = array();
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
            if (!$tpl && file_exists("$rkrkLiveassessTemplates/$tplName.json")) {
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

		$pilotRaw = $fields["Allgemein"]["Pilot"]
            ?? $fields["Allgemein"]["Piloten"]
            ?? $fields["Allgemein"]["Team / Bezeichnung"]
            ?? $fields["Allgemein"]["Team"]
            ?? "";
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
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($tplName) . "&edit=" . rawurlencode($id)), ENT_QUOTES, "UTF-8") . "' target='_self' title='Beurteilung bearbeiten' aria-label='Bearbeiten'>" . rkrk_checklist_toolbar_svg_edit() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($tplName) . "&edit=" . rawurlencode($id) . "&readonly=1"), ENT_QUOTES, "UTF-8") . "' target='_self' title='Nur lesen' aria-label='Lesen'>" . rkrk_checklist_toolbar_svg_read() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_export_link($id), ENT_QUOTES, "UTF-8") . "' title='JSON exportieren (Backup)' aria-label='Export' download>" . rkrk_checklist_toolbar_svg_export() . "</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_paperform_link($tplName), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Papierformular (leer, Template)' aria-label='Papierformular'>📄</a>
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_list_print_link($id), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Druckansicht (Strg+P / PDF)' aria-label='Drucken'>" . rkrk_checklist_toolbar_svg_print() . "</a>
                    " . rkrk_checklist_toolbar_report_act_html($id, "live") . "
                    <a class='chk-act' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=deletejson&id=" . rawurlencode($id)), ENT_QUOTES, "UTF-8") . "'
                       onclick='return confirm(\"Eintrag wirklich löschen?\")' title='Eintrag löschen' aria-label='Löschen' style='color:#8b2e2e;border-color:#e8c8c8;'>" . rkrk_checklist_toolbar_svg_trash() . "</a>
                </td>
              </tr>";
    }

    if ($rowCount === 0) {
        echo "<tr><td colspan='5' style='text-align:center; color:#6b7280; padding:12px;'>Keine Beurteilungen mit diesen Filtern gefunden.</td></tr>";
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
        📄 Neueste 12 Beurteilungen
    </a>

    <a href='".safe($listAllUrl)."' 
       class='btn' 
       ".($mode=='listall' ? "style='opacity:0.5; pointer-events:none;'" : "").">
        📄 Alle Beurteilungen
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
   Namen aus Personal (JSON) fuer datalist-Vorschlag (Pilot, Piloten, Instruktor)
----------------------------------------------------------- */
function rkrk_live_personel_rows_from_file(string $path): array
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
        return $data["pilots"];
    }
    if (isset($data["rows"]) && is_array($data["rows"])) {
        return $data["rows"];
    }
    return [];
}

/** Anzeige für Live-Formulare: Vorname + Nachname; Legacy name/display nur falls noch in JSON. */
function rkrk_live_row_display_name(array $row): string
{
    $vn = trim((string)($row["vorname"] ?? ""));
    $nn = trim((string)($row["nachname"] ?? ""));
    if ($vn !== "" || $nn !== "") {
        return trim($vn . " " . $nn);
    }
    $n = trim((string)($row["name"] ?? ""));
    if ($n !== "") {
        return $n;
    }
    return trim((string)($row["display"] ?? ""));
}

function rkrk_live_row_is_instruktor(array $row): bool
{
	if (function_exists("rkrk_assess_personel_row_instruktor_flag")) {
		return rkrk_assess_personel_row_instruktor_flag($row);
	}
    $v = $row["instruktor"] ?? null;
    if (is_bool($v)) {
        return $v;
    }
    $s = trim((string)$v);
    return $s === "1" || strcasecmp($s, "ja") === 0;
}

function rkrk_live_pilot_name_suggestions(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    global $rkrkLivePersonelRoot;
    $path = $rkrkLivePersonelRoot . DIRECTORY_SEPARATOR . "pilotenliste.json";
    foreach (rkrk_live_personel_rows_from_file($path) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $n = rkrk_live_row_display_name($row);
        if ($n !== "") {
            $cache[] = $n;
        }
    }
    $cache = array_values(array_unique($cache));
    sort($cache, SORT_NATURAL | SORT_FLAG_CASE);
    return $cache;
}

/** Nur als Instruktor markierte Personen (Spalte instruktor in pilotenliste.json). */
function rkrk_live_instructor_name_suggestions(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    global $rkrkLivePersonelRoot;
    $path = $rkrkLivePersonelRoot . DIRECTORY_SEPARATOR . "pilotenliste.json";
    foreach (rkrk_live_personel_rows_from_file($path) as $row) {
        if (!is_array($row) || !rkrk_live_row_is_instruktor($row)) {
            continue;
        }
        $n = rkrk_live_row_display_name($row);
        if ($n !== "") {
            $cache[] = $n;
        }
    }
    $cache = array_values(array_unique($cache));
    sort($cache, SORT_NATURAL | SORT_FLAG_CASE);
    return $cache;
}

/** Einmal pro Seite: HTML datalist fuer Pilot-Vorschlagsliste */
function rkrk_live_emit_pilot_datalist_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $names = rkrk_live_pilot_name_suggestions();
    if (count($names) === 0) {
        return;
    }
    $done = true;
    echo "<datalist id=\"rk-live-pilot-names\">";
    foreach ($names as $nm) {
        echo "<option value=\"" . htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') . "\">";
    }
    echo "</datalist>";
}

/** Einmal pro Seite: HTML datalist nur fuer Instruktor-Felder */
function rkrk_live_emit_instructor_datalist_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $names = rkrk_live_instructor_name_suggestions();
    if (count($names) === 0) {
        return;
    }
    $done = true;
    echo "<datalist id=\"rk-live-instructor-names\">";
    foreach ($names as $nm) {
        echo "<option value=\"" . htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') . "\">";
    }
    echo "</datalist>";
}

/** Normalisierter Vergleichsstring fuer Pilotennamen (Liste vs. Eingabe). */
function rkrk_live_normalize_person_name_key(string $s): string
{
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }
    return strtolower($s);
}

/**
 * Stufe 1–6 aus einer Personalzeile (flexible Spaltennamen in pilotenliste.json).
 */
function rkrk_live_pilot_row_stufe(array $row): ?int
{
    $keys = ['stufe', 'bewertung', 'level', 'note', 'aktueller_stand', 'gesamtstufe', 'erfahrungsstufe', 'einsatzstufe', 'pilotenstufe'];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $row)) {
            continue;
        }
        $raw = $row[$k];
        if (is_int($raw) || is_float($raw)) {
            $n = (int)$raw;
            if ($n >= 1 && $n <= 6) {
                return $n;
            }
            continue;
        }
        $s = trim((string)$raw);
        if ($s !== '' && ctype_digit($s)) {
            $n = (int)$s;
            if ($n >= 1 && $n <= 6) {
                return $n;
            }
        }
        if ($s !== '' && preg_match('/\b([1-6])\b/', $s, $m)) {
            return (int)$m[1];
        }
    }
    return null;
}

/**
 * @return array{pilots: array<int, array{name: string, stufe: int}>}
 */
function rkrk_live_pilotenliste_stufe_payload_for_js(): array
{
    global $rkrkLivePersonelRoot;
    $path = $rkrkLivePersonelRoot . DIRECTORY_SEPARATOR . "pilotenliste.json";
    $pilots = [];
    foreach (rkrk_live_personel_rows_from_file($path) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = rkrk_live_row_display_name($row);
        if ($name === '') {
            continue;
        }
        $st = rkrk_live_pilot_row_stufe($row);
        if ($st === null) {
            continue;
        }
        $pilots[] = ['name' => $name, 'stufe' => $st];
    }
    return ['pilots' => $pilots];
}

/** True wenn mindestens ein Direct-Check-Feld (Rollenmodell / Stufen) im Template vorkommt. */
function rkrk_live_sections_contain_directcheck(array $sections): bool
{
    foreach ($sections as $fields) {
        if (!is_array($fields)) {
            continue;
        }
        foreach ($fields as $meta) {
            $t = is_array($meta) ? strtolower(trim((string)($meta["type"] ?? "text"))) : strtolower(trim((string)$meta));
            if ($t === "directcheck") {
                return true;
            }
        }
    }
    return false;
}

/** Sektion enthält ein Gesamtstufe-Feld (Rollenübersicht direkt darüber platzieren). */
function rkrk_live_section_contains_gesamtstufe(array $fields): bool
{
    foreach (array_keys($fields) as $label) {
        if (stripos((string)$label, "Gesamtstufe") !== false) {
            return true;
        }
    }
    return false;
}

/**
 * HTML-Innenbereich fuer print-Felder: vermeidet Dopplung von info-Text und points-Liste
 * (Pilot-Referenz: info enthaelt oft dieselben Bullet-Zeilen wie points[]).
 */
function rkrk_live_print_field_inner_html(string $printText, array $typeMeta): string
{
    $points = [];
    if (isset($typeMeta["points"]) && is_array($typeMeta["points"])) {
        foreach ($typeMeta["points"] as $p) {
            $p = trim((string)$p);
            if ($p !== "") {
                $points[] = $p;
            }
        }
    }

    $renderUl = static function (array $pts): string {
        if ($pts === []) {
            return "";
        }
        $lis = [];
        foreach ($pts as $p) {
            $lis[] = "<li>" . (strip_tags($p) !== $p ? $p : safe($p)) . "</li>";
        }
        return "<ul class=\"print-text-points\" style=\"margin:10px 0 0 1.15em;padding:0;line-height:1.45;\">" . implode("", $lis) . "</ul>";
    };

    $safeBlock = static function (string $raw): string {
        $raw = trim($raw);
        if ($raw === "") {
            return "";
        }
        $inner = (strip_tags($raw) !== $raw) ? $raw : nl2br(safe($raw));
        if (function_exists("rkrk_assess_format_template_html_fragment")) {
            $inner = rkrk_assess_format_template_html_fragment($inner, "screen");
        }
        return "<div class=\"print-text-intro\">" . $inner . "</div>";
    };

    if ($points === []) {
        return $printText === "" ? "" : $safeBlock($printText);
    }

    $printText = trim($printText);
    if ($printText === "") {
        return $renderUl($points);
    }

    $parts = preg_split('/\R{2,}/u', $printText, 2);
    $head = trim((string)($parts[0] ?? ""));
    $tail = isset($parts[1]) ? trim((string)$parts[1]) : "";

    $tailLines = [];
    if ($tail !== "") {
        foreach (preg_split('/\R/u', $tail) as $ln) {
            $c = trim(preg_replace('/^[\x{2022}\-\*•]\s*/u', '', trim($ln)));
            if ($c !== "") {
                $tailLines[] = $c;
            }
        }
    }

    $sortedPts = $points;
    $sortedTail = $tailLines;
    sort($sortedPts);
    sort($sortedTail);
    $tailMatchesPoints = ($sortedPts === $sortedTail && count($points) === count($tailLines) && count($points) > 0);

    $out = "";
    if ($tailMatchesPoints) {
        if ($head !== "" && $head !== $points[0]) {
            $out .= $safeBlock($head);
        }
        $out .= $renderUl($points);
        return $out;
    }

    if ($head !== "" && $head !== ($points[0] ?? "")) {
        $out .= $safeBlock($head);
    }
    if ($tail !== "") {
        $ti = (strip_tags($tail) !== $tail) ? $tail : nl2br(safe($tail));
        if (function_exists("rkrk_assess_format_template_html_fragment")) {
            $ti = rkrk_assess_format_template_html_fragment($ti, "screen");
        }
        $out .= "<div class=\"print-text-tail\" style=\"margin-top:8px;line-height:1.45;\">" . $ti . "</div>";
    }
    $out .= $renderUl($points);
    return $out;
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
    $requiredFlag = false;
    $printNoCollapse = false;

    if (is_array($type)) {
        $placeholder = isset($type["placeholder"]) ? $type["placeholder"] : "";
        $info        = isset($type["info"]) ? $type["info"] : "";
        $ref         = isset($type["ref"]) ? rkrk_normalize_ref_id($type["ref"]) : "";
        $calc        = isset($type["calc"]) ? trim((string)$type["calc"]) : "";
        $options     = isset($type["options"]) ? trim((string)$type["options"]) : "";
        $typeString  = isset($type["type"]) ? $type["type"] : "text";
        $rq          = strtolower(trim((string)($type["required"] ?? "")));
        $requiredFlag = in_array($rq, ["1", "true", "yes", "pflicht"], true);
        $printNoCollapse = (trim((string)($type["nocollapse"] ?? "")) === "1");
        $reference   = trim((string)($type["reference"] ?? ""));
        $hintsHtml   = trim((string)($type["hints"] ?? ""));
    } else {
        $typeString = $type;
        $reference = "";
        $hintsHtml = "";
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
    $rkGesamtCls = "";
    $rkGesamtAttr = "";

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
        if (empty($renderMeta["remark_ro_on_wrapper"])) {
            $extraAttr .= " data-remark-dependent='1' data-remark-parent-key='$remarkParentKey' data-ro-dependent='1' data-ro-parent-key='$remarkParentKey' ";
        }
    }

    $disabled = $readonly ? "disabled" : "";
    $isPrintType = ($typeString === "print");
    $reqAttr = (!$readonly && $requiredFlag) ? " required aria-required=\"true\"" : "";
    $rkDroneSpecAttr = "";
    if (strpos((string)$label, '#') !== false
        && (stripos((string)$label, 'Spezifikation') !== false || stripos((string)$label, 'Specification') !== false)) {
        $rkDroneSpecAttr = " data-rk-drone-spec='1' ";
    }
    if (strpos((string)$label, "Gesamtstufe") !== false && strpos((string)$typeString, "radio:") === 0) {
        $rkGesamtCls = " rk-field-gesamtstufe ";
        $rkGesamtAttr = " data-rk-gesamtstufe='1' ";
    }
    $rkHashMussAttr = '';
    $rkLblRules = rkrk_parse_point_label_rules((string)$label);
    if (!empty($rkLblRules['hash'])) {
        $rkHashMussAttr = " data-rk-hash-muss='1' ";
    }
    echo "<div class='field $depClass$rkGesamtCls' $depAttr $extraAttr $rkGesamtAttr$rkDroneSpecAttr$rkHashMussAttr>";
    echo "<label class='field-label'>";
    rkrk_live_echo_field_label_html((string)$label);
    if ($requiredFlag && !$readonly && !$isPrintType) {
        echo " <abbr title='Pflichtfeld' class='field-required-mark'>*</abbr>";
    }

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
    if ($rkDroneSpecAttr !== "" && is_array($type) && !rkrk_is_m2_spec_label((string)$label)) {
        $flowProcess = trim((string)($type["process"] ?? ""));
        $flowChecks = trim((string)($type["checks"] ?? ""));
        $flowPc = rkrk_extract_point_code((string)$label);
        if ($flowPc !== "" && ($flowProcess !== "" || $flowChecks !== "")) {
            rkrk_live_emit_point_flow_block($flowProcess, $flowChecks, $flowPc);
        }
    }
    if ($isPrintType) {
        $typeMeta = is_array($type) ? $type : [];
        $printText = trim((string)$info);
        if ($printText === "") {
            $printText = trim((string)$placeholder);
        }
        if ($printText === "") {
            $printText = trim((string)$value);
        }
        $inner = rkrk_live_print_field_inner_html($printText, $typeMeta);
        if ($inner === "") {
            echo "</div>";
            return;
        }
        $nocls = $printNoCollapse ? " print-nocollapse" : "";
        $refStufeAttr = "";
        if (preg_match('/^Stufe\s+(\d+)/iu', (string)$label, $rm)) {
            $sn = (int)$rm[1];
            if ($sn >= 1 && $sn <= 6) {
                $refStufeAttr = " data-rk-ref-stufe=\"" . $sn . "\"";
            }
        }
        $sumSafe = htmlspecialchars(
            trim((string)$placeholder) !== "" ? trim((string)$placeholder) : "Referenztext anzeigen",
            ENT_QUOTES,
            'UTF-8'
        );
        if ($printNoCollapse) {
            echo "<div class='print-text-block print-nocollapse{$nocls}' style='margin-top:6px;'>
                    <div class='print-text-content' style='margin-top:4px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; background:#fafafa; white-space:normal;'>".$inner."</div>
                  </div>";
        } else {
            echo "<details class='print-text-block{$nocls}'{$refStufeAttr} style='margin-top:6px;'>
                    <summary class='print-text-summary' style='cursor:pointer; font-weight:600; user-select:none; list-style:none;color:#3d5433;'>".$sumSafe."</summary>
                    <div class='print-text-content' style='margin-top:8px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; background:#fafafa; white-space:normal;'>".$inner."</div>
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

        if ($reference !== "") {
            echo "<div class='rk-drone-spec-reference'>" . $reference . "</div>";
        }
        $opts = explode("|", substr($typeString, 6));
        echo "<div class='option-grid'>";

        foreach ($opts as $oIdx => $o) {

            $oTrim = trim($o);
            $isSelected = ($value === $oTrim);

            list($cleanLabel, $color, $colorKey) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);
            $radioReq = (!$readonly && $requiredFlag && $oIdx === 0) ? $reqAttr : "";

            echo "<label class='color-option$selectedClass' $style" . rkrk_color_option_marker_attrs($colorKey) . ">
                    <input type='radio'
                           name='fields[$secName][$label]'
                           data-field-key=\"$key\"
                           $refAttr
                           value='".safe($oTrim)."'
                           ".($isSelected ? "checked" : "")."
                           $disabled$radioReq>
                    <span class='color-badge'>".safe($cleanLabel)."</span>
                  </label>";
        }

        echo "</div>";
        if ($hintsHtml !== "" && $rkDroneSpecAttr !== "") {
            echo "<div class='rk-drone-spec-hints'>" . $hintsHtml . "</div>";
        }
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

            list($cleanLabel, $color, $colorKey) = extractColor($oTrim);

            $selectedClass = $isSelected ? " selected" : "";
            $style = $buildColorStyle($color, $isSelected);

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
        $style = $buildColorStyle($color, $isSelected);

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

    /* DIRECT CHECK (Kontrollpunkte: Klickstufen; Teilpunkte pro Zeile, subchecks-Blöcke mit ---) */
    elseif ($typeString === "directcheck") {
        $checksSrc = "";
        $subchecksSrc = "";
        if (is_array($type)) {
            $checksSrc = isset($type["checks"]) ? trim((string)$type["checks"]) : "";
            $subchecksSrc = isset($type["subchecks"]) ? trim((string)$type["subchecks"]) : "";
        }
        $mainLines = rkrk_split_checks_detailed($checksSrc);
        $nMain = count($mainLines);
        $subByMain = rkrk_directcheck_subchecks_per_main($subchecksSrc, $nMain);
        $parsed = rkrk_directcheck_parse_value(is_string($value) ? $value : "");
        $norm = rkrk_directcheck_normalize_states($parsed, $nMain, $subByMain);
        $jsonEsc = htmlspecialchars(rkrk_directcheck_encode_value($norm), ENT_QUOTES, "UTF-8");

        $showFollowup = false;
        foreach ($norm["main"] as $st) {
            if ($st === "orange" || $st === "red") {
                $showFollowup = true;
                break;
            }
        }

        $roAttr = $readonly ? " data-dc-readonly='1'" : "";

        $rkStufeSlot = "";
        if (is_array($type)) {
            $rkStufeSlot = isset($type["stufe_slot"]) ? trim((string)$type["stufe_slot"]) : "";
        }
        if ($rkStufeSlot === "" && preg_match('/\bStufe\s+(\d+)\b/u', (string)$label, $rkStufeM)) {
            $rkStufeSlot = $rkStufeM[1];
        }
        $rkStufeDataAttr = ($rkStufeSlot !== "") ? " data-rk-stufe-slot=\"" . htmlspecialchars($rkStufeSlot, ENT_QUOTES, "UTF-8") . "\"" : "";

        $rkDroneSpecAttr = (strpos((string)$label, '#') !== false
            && (strpos((string)$label, 'Spezifikation') !== false || strpos((string)$label, 'Specification') !== false))
            ? " data-rk-drone-spec='1'" : "";

        echo "<div class='directcheck-root' data-dc-root='1'$roAttr$rkStufeDataAttr$rkDroneSpecAttr>";
        echo "<details class='directcheck-details' open>";
        echo "<summary class='directcheck-summary'>";
        echo "<span class='directcheck-summary-title'>Kontrollpunkte</span> ";
        echo "<span class='directcheck-stage' data-dc-stage='1'><span class='directcheck-stage-inner'></span></span>";
        echo "</summary>";

        echo "<div class='directcheck-inner'>";
        echo "<ul class='directcheck-bullet-list' data-dc-main-list='1'>";
        foreach ($mainLines as $i => $line) {
            $disp = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$line);
            $disp = trim((string)$disp);
            if ($disp === "") {
                $disp = (string)$line;
            }
            $stClass = $norm["main"][(string)$i];
            if (!in_array($stClass, ["none", "green", "orange", "red"], true)) {
                $stClass = "none";
            }
            $stAttr = htmlspecialchars($stClass, ENT_QUOTES, "UTF-8");
            $btnDis = $readonly ? " disabled" : "";
            $showSubs = ($stClass === "orange" || $stClass === "red") && !empty($subByMain[$i]);
            $subsHidden = $showSubs ? "" : " hidden";
            echo "<li class='dc-kp-item'>";
            echo "<button type='button' class='dc-row dc-main dc-state-$stClass' data-dc-main='1' data-idx='" . (int)$i . "' data-state='$stAttr' title='Neutral: 1× grün · 2× orange · 3× rot · farbig 1× wieder neutral'$btnDis>";
            echo "<span class='dc-row-label'>" . nl2br(safe($disp)) . "</span></button>";
            echo "<div class='dc-subs-for-line'$subsHidden data-dc-subs-for='" . (int)$i . "'>";
            if (!empty($subByMain[$i])) {
                echo "<div class='dc-subs-caption'>Teilpunkte nicht erfüllt (anklicken)</div>";
                echo "<ul class='directcheck-sub-chips'>";
                foreach ($subByMain[$i] as $j => $sline) {
                    $sd = preg_replace('/\s*\(([rogn])\)\s*$/iu', "", (string)$sline);
                    $sd = trim((string)$sd);
                    if ($sd === "") {
                        $sd = (string)$sline;
                    }
                    $hit = !empty($norm["subHit"][(string)$i][(string)$j]);
                    $hitClass = $hit ? " is-hit" : "";
                    $aria = $hit ? "true" : "false";
                    $btnDis2 = $readonly ? " disabled" : "";
                    echo "<li><button type='button' class='dc-sub-chip$hitClass' data-dc-sub='1' data-main-idx='" . (int)$i . "' data-idx='" . (int)$j . "' aria-pressed='$aria' title='Als nicht erfüllt markieren'$btnDis2>";
                    echo nl2br(safe($sd)) . "</button></li>";
                }
                echo "</ul>";
            }
            echo "</div>";
            echo "</li>";
        }
        echo "</ul>";
        if ($nMain === 0) {
            echo "<div class='directcheck-empty'>Keine Kontrollpunkte im Template („Kontrollpunkte / checks“).</div>";
        }

        $fuAttr = $showFollowup ? "" : " hidden";
        echo "<div class='directcheck-followup' data-dc-followup='1'$fuAttr>";
        echo "<label class='directcheck-obs-label'>Beobachtungen &amp; Bemerkungen";
        $obsRo = $readonly ? " readonly disabled" : "";
        echo "<textarea class='dc-obs' rows='3' placeholder='Freitext …'$obsRo>" . safe($norm["obs"]) . "</textarea>";
        echo "</label>";
        echo "</div>";

        echo "</div>";
        echo "</details>";

        echo "<input type='hidden' class='dc-json' name='fields[$secName][$label]' data-field-key=\"" . htmlspecialchars($key, ENT_QUOTES, "UTF-8") . "\" $refAttr value='$jsonEsc' $disabled>";
        echo "</div>";
    }

    /* TEXTAREA */
    elseif ($typeString === "textarea") {
        echo "<textarea
                 name='fields[$secName][$label]'
                 data-field-key=\"$key\"
                 $refAttr
                 rows='3'
                 placeholder='".safe($placeholder)."'
                 $disabled$reqAttr>"
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
                     $disabled$reqAttr>";
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
                     $disabled$reqAttr>";
    }

    /* NUMBER */
    elseif ($typeString === "number") {
        echo "<input type='number'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled$reqAttr>";
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
        echo "<div class='sum-value-display' data-sum-display-for=\"$key\">$sumValue</div>";
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
		echo "<input id='field_$key' data-field-type='flugzeit' data-field-key='$key' $refAttr type='datetime-local' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' $disabled$reqAttr />";
	}

	/* ------------------------- FLUGORT ------------------------- */
	elseif ($typeString === "flugort") {
		// normaler Textinput
		$val = (string)$value;
		echo "<input id='field_$key' data-field-type='flugort' data-field-key='$key' $refAttr type='text' name='fields[".safe($secName)."][".safe($label)."]' value='".htmlspecialchars($val, ENT_QUOTES)."' placeholder='".htmlspecialchars($placeholder, ENT_QUOTES)."' $disabled$reqAttr />";
	}

    /* STANDARD TEXT */
    else {
        $listAttr = "";
        if ($secName === "Allgemein" && ($label === "Pilot" || $label === "Piloten")) {
            if (!empty($GLOBALS["rkrkAssessPersPickExplicit"]) && !empty($GLOBALS["rkrkAssessPersPickOptions"]) && !$readonly) {
                $listAttr = " list=\"rkrk-perspick-datalist\" autocomplete=\"off\" ";
            } elseif (empty($GLOBALS["rkrkAssessPersPickExplicit"]) && count(rkrk_live_pilot_name_suggestions()) > 0) {
                rkrk_live_emit_pilot_datalist_once();
                $listAttr = " list=\"rk-live-pilot-names\" ";
            }
        } elseif ($secName === "Allgemein" && ($label === "Instruktor" || $label === "Prüfer" || $label === "Pruefer")) {
            if (!empty($GLOBALS["rkrkAssessPersPickExplicit"]) && !empty($GLOBALS["rkrkAssessPersPickInstrPrueferOptions"]) && !$readonly) {
                $listAttr = " list=\"rkrk-perspick-instr-datalist\" autocomplete=\"off\" ";
            } elseif (empty($GLOBALS["rkrkAssessPersPickExplicit"]) && $label === "Instruktor" && count(rkrk_live_instructor_name_suggestions()) > 0) {
                rkrk_live_emit_instructor_datalist_once();
                $listAttr = " list=\"rk-live-instructor-names\" ";
            }
        }
        echo "<input type='text'
                     name='fields[$secName][$label]'
                     data-field-key=\"$key\"
                     $refAttr
                     $listAttr
                     value='".safe($value)."'
                     placeholder='".safe($placeholder)."'
                     $disabled$reqAttr>";
        if ($secName === "Allgemein" && $label === "Pilot") {
            echo "<div class='rk-live-pilot-liste-hinweis' data-rk-pilot-liste-badge aria-live='polite'></div>";
        }
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
    global $rkrkLiveassessResults ;

    $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/" . basename($editId) . ".json";
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
    global $rkrkLiveassessResults ;
    $edit = basename($_POST['edit'] ?? '');
    if ($edit === '') {
        echo "ERR";
        return;
    }
    $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/$edit.json";
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


/** Eine Checklisten-Sektion inkl. Extra-Felder und „Neues Feld“ ausgeben (Live-Beurteilung). */
function rkrk_live_emit_checklist_section(
    string $secName,
    array $fields,
    array $saved,
    array $savedRaw,
    array $extraFields,
    bool $readonly,
    bool $isGuest
): void {
    $secId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $secName));
    $secTitleAttr = htmlspecialchars($secName, ENT_QUOTES);
    $flowEmitted = [];

    echo "<div class='section-row'>
				<div class='section-title-box' data-section-id='$secId'>".safe($secName)."</div>
				<div class='section-fields' data-section-id='$secId' data-section-title='$secTitleAttr'>";

    foreach ($fields as $label => $type){
        $value = $saved[$secName][$label] ?? ($_GET[$label] ?? "");
        $fieldRenderMeta = array();
        $pointMeta = null;
        $isRemarkField = (preg_match('/\bBemerkung\b/ui', (string)$label) === 1);

        $pcFlow = rkrk_extract_point_code((string)$label);
        if ($pcFlow !== "") {
            $pmFlow = rkrk_find_point_evaluation_meta($fields, $pcFlow);
            if (is_array($pmFlow)) {
                $srcLabel = isset($pmFlow["score_label"]) ? (string)$pmFlow["score_label"] : "";
                $flowKey = trim($srcLabel) !== "" ? $srcLabel : $pcFlow;
                if (empty($flowEmitted[$flowKey])) {
                    $pr = trim((string)($pmFlow["process"] ?? ""));
                    $ch = trim((string)($pmFlow["checks"] ?? ""));
                    $srcType = "";
                    if ($srcLabel !== "" && isset($fields[$srcLabel]) && is_array($fields[$srcLabel])) {
                        $srcType = strtolower(trim((string)($fields[$srcLabel]["type"] ?? "")));
                    }
                    $pcEmit = rkrk_extract_point_code($flowKey);
                    if ($pcEmit === "") {
                        $pcEmit = $pcFlow;
                    }
                    // M.2#: Ablauf/Checks werden am Feld selbst ausgegeben (siehe unten).
                    $isInlineDroneSpec = rkrk_is_m2_spec_label($srcLabel);
                    if ($isInlineDroneSpec) {
                        $flowEmitted[$flowKey] = true;
                    } elseif ($srcType === "directcheck") {
                        $prOnly = trim((string)($pmFlow["process"] ?? ""));
                        if ($prOnly !== "") {
                            rkrk_live_emit_point_flow_block($prOnly, "", $pcEmit);
                        }
                        $flowEmitted[$flowKey] = true;
                    } elseif ($pr !== "" || $ch !== "") {
                        rkrk_live_emit_point_flow_block($pr, $ch, $pcEmit);
                        $flowEmitted[$flowKey] = true;
                    }
                }
            }
        }

        if (! $isRemarkField && is_array($type) && rkrk_is_m2_spec_label((string)$label)) {
            $prM2 = trim((string)($type["process"] ?? ""));
            $chM2 = trim((string)($type["checks"] ?? ""));
            if ($prM2 !== "" || $chM2 !== "") {
                rkrk_live_emit_point_flow_block($prM2, $chM2, "M.2");
            }
        }

        $parentLabel = null;
        if ($isRemarkField) {
            $pointMeta = null;
            $parentLabel = rkrk_resolve_remark_parent_label($fields, (string)$label);
            if ($parentLabel !== null && isset($fields[$parentLabel]) && is_array($fields[$parentLabel])) {
                $parentType = $fields[$parentLabel];
                $pointMeta = [
                    "score_label" => $parentLabel,
                    "process" => trim((string)($parentType["process"] ?? "")),
                    "checks" => trim((string)($parentType["checks"] ?? "")),
                ];
                $fieldRenderMeta["remark_parent_key"] = rkrk_field_key_from_label($parentLabel);
            }
        }

        if ($isRemarkField && is_array($pointMeta)) {
            $process = isset($pointMeta["process"]) ? trim((string)$pointMeta["process"]) : "";
            $checks = isset($pointMeta["checks"]) ? trim((string)$pointMeta["checks"]) : "";
            $checkItems = rkrk_split_checks_detailed($checks);
            $pointCode = rkrk_extract_point_code($label);
            $scoreLabel = isset($pointMeta["score_label"]) ? trim((string)$pointMeta["score_label"]) : "";
            $notFulfilledLabel = ($scoreLabel !== "")
                ? ($scoreLabel . " Nicht erfüllte Punkte")
                : (($pointCode !== "") ? ($pointCode . " Nicht erfüllte Punkte") : ("Nicht erfüllte Punkte"));
            $notFulfilledSaved = isset($savedRaw[$secName][$notFulfilledLabel]) ? $savedRaw[$secName][$notFulfilledLabel] : "";
            $notFulfilledSelected = rkrk_decode_not_fulfilled_points($notFulfilledSaved);
            $notFulfilledJson = htmlspecialchars(json_encode($notFulfilledSelected, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            $remarkParentKey = isset($fieldRenderMeta["remark_parent_key"]) ? $fieldRenderMeta["remark_parent_key"] : "";
            $remarkParentKeySafe = htmlspecialchars((string)$remarkParentKey, ENT_QUOTES);
            echo "<div class='remark-with-meta remark-dependent' data-ro-dependent='1' data-ro-parent-key='$remarkParentKeySafe' hidden style='display:none'>";
            $fieldRenderMeta["remark_ro_on_wrapper"] = true;
            if (!empty($checkItems)) {
                $btnDisabled = $readonly ? " disabled" : "";
                echo "<div class='notfulfilled-box'>";
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
        } elseif ($isRemarkField && $parentLabel !== null && isset($fields[$parentLabel])) {
            $rkPk = htmlspecialchars(rkrk_field_key_from_label($parentLabel), ENT_QUOTES);
            echo "<div class='remark-with-meta remark-dependent' data-ro-dependent='1' data-ro-parent-key='$rkPk' hidden style='display:none'>";
            $fieldRenderMeta["remark_ro_on_wrapper"] = true;
            renderField($secName, $label, $type, $value, $readonly, $fieldRenderMeta);
            echo "</div>";
        } else {
            renderField($secName, $label, $type, $value, $readonly, $fieldRenderMeta);
        }
    }

    if ($secName === "Wetter" && isset($saved["weather_saved_at"])) {
        echo "<div class='system-info-box' style='margin-top:10px;'>
					🌤️ Wetterdaten gespeichert am: " . safe($saved["weather_saved_at"]) . "
				  </div>";
    }

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


function showChecklistForm(){
    global $rkrkLiveassessTemplates, $rkrkLiveassessResults , $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
    $isGuest=false;

    $file     = basename($_GET['file'] ?? "");
    $edit     = isset($_GET['edit']) ? basename($_GET['edit']) : null;
    $readonly = isset($_GET['readonly']);

    // Storyboard: nur Ablauf/UI + Auto-n.b.; Bewertung/Schlussreport unverändert (Template).
    $storyId = "";
    $story = null;
    if (function_exists("rkrk_assess_storyboard_resolve_for_form")) {
        list($file, $story, $storyId) = rkrk_assess_storyboard_resolve_for_form($file);
    } else {
        $storyId = function_exists("rkrk_assess_storyboard_request_id") ? rkrk_assess_storyboard_request_id() : "";
        $story = ($storyId !== "" && function_exists("rkrk_assess_storyboard_load"))
            ? rkrk_assess_storyboard_load($storyId)
            : null;
        if (is_array($story)) {
            $storyTpl = basename(trim((string) ($story["template"] ?? "")));
            if ($storyTpl !== "") {
                $file = $storyTpl;
            }
            $GLOBALS["rkrkAssessActiveStory"] = $story;
        } else {
            $story = null;
            $storyId = "";
            $GLOBALS["rkrkAssessActiveStory"] = null;
        }
    }

    $tpl = loadTemplate($file);
    $title    = $tpl["title"] ?? "Live-Beurteilung";
    $sections = $tpl["sections"] ?? [];
    $tplSchema = "";
    if (is_array($tpl) && isset($tpl["_meta"]) && is_array($tpl["_meta"])) {
        $tplSchema = trim((string)($tpl["_meta"]["schema"] ?? ""));
    }

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
            if ($storyId === "" && !empty($json["storyboard_id"]) && function_exists("rkrk_assess_storyboard_load")) {
                $storyId = rkrk_assess_storyboard_normalize_id((string) $json["storyboard_id"]);
                $story = rkrk_assess_storyboard_load($storyId);
                if (!is_array($story)) {
                    $story = null;
                    $storyId = "";
                    $GLOBALS["rkrkAssessActiveStory"] = null;
                } else {
                    $GLOBALS["rkrkAssessActiveStory"] = $story;
                }
            }

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
        if (is_array($story) && function_exists("rkrk_assess_storyboard_apply_nb_to_saved")) {
            rkrk_assess_storyboard_apply_nb_to_saved($sections, $saved, $story);
        }
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

    rkrk_checklist_toolbar_echo_form_bar($file, $edit, $readonly);

    if (is_array($tpl) && !empty($tpl["_meta"]) && is_array($tpl["_meta"])) {
        $rev = trim((string)($tpl["_meta"]["revision"] ?? ""));
        $schema = trim((string)($tpl["_meta"]["schema"] ?? ""));
        if ($schema !== "" || $rev !== "") {
            $bits = [];
            if ($schema !== "") {
                $bits[] = "Schema: " . $schema;
            }
            if ($rev !== "") {
                $bits[] = "Stand: " . $rev;
            }
            echo "<div class='rkrk-card' style='padding:10px 14px;margin-bottom:12px;font-size:13px;color:#4a5a4a;'><em>" . safe(implode(" · ", $bits)) . "</em></div>";
        }
    }

    if (!empty($tpl["_description"]) && is_array($tpl["_description"])) {
        echo "<div class='rkrk-card rk-live-template-desc' style='margin-bottom:12px;'>";
        echo "<details class='rk-live-desc-details' style='border:1px solid #e5e7eb;border-radius:10px;padding:0;background:#fafbf9;'>";
        echo "<summary class='rk-live-desc-summary' style='cursor:pointer;font-weight:700;padding:12px 14px;user-select:none;list-style:none;'>Bewertungsbeschreibung &amp; Skala (Template)</summary>";
        echo "<div style='padding:12px 16px 16px;border-top:1px solid #e5e7eb;font-size:14px;line-height:1.5;color:#334155;'>";
        foreach ($tpl["_description"] as $k => $v) {
            if (!is_string($v) || trim($v) === "") {
                continue;
            }
            echo "<div style='margin-bottom:12px;'><strong>" . safe((string)$k) . ":</strong><br>" . nl2br(safe($v)) . "</div>";
        }
        echo "</div></details></div>";
    }

    if ($tplSchema === "rkrs_pilot_v01") {
        $plPayload = rkrk_live_pilotenliste_stufe_payload_for_js();
        echo "<script type=\"application/json\" id=\"rk-live-pilot-stufe-map\">" . json_encode($plPayload, JSON_UNESCAPED_UNICODE) . "</script>";
    }

	if (false) {
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
	.field-required-mark{
	  color:#b91c1c;
	  font-weight:700;
	  text-decoration:none;
	  cursor:help;
	}
	.live-point-flow__content img{max-width:100%;height:auto;width:auto;max-height:300px;display:block;margin:8px auto;border-radius:5px 15% 10px;object-fit:contain;}
	.print-text-block.print-nocollapse .print-text-content{ margin-top:4px !important; }
	.print-text-content figure,.print-text-intro figure{margin:10px 0;text-align:center;}
	.print-text-content img,.print-text-intro img{max-width:100%;height:auto;width:auto;display:block;margin:8px auto;border-radius:5px 15% 10px;object-fit:contain;}
	.print-text-content img.lernimagelarge,.print-text-intro img.lernimagelarge{max-height:500px;}
	.print-text-content img.lernimage,.print-text-intro img.lernimage{max-height:300px;}
	.print-text-content img.lernimagesmall,.print-text-intro img.lernimagesmall{max-height:200px;}
	.print-text-content img.lernimageverysmall,.print-text-intro img.lernimageverysmall{max-height:100px;}
	.print-text-content img.lernimagefixwidth,.print-text-intro img.lernimagefixwidth{width:600px;max-width:100%;height:auto;}
	.print-text-content img.lernimagefixheight,.print-text-intro img.lernimagefixheight{max-height:200px;}
	.print-text-content img.lernimagefixlargeheight,.print-text-intro img.lernimagefixlargeheight{max-height:500px;}
	.print-text-content img.lernimagesmallfixwidth,.print-text-intro img.lernimagesmallfixwidth{width:450px;max-width:100%;height:auto;}
	.print-text-content figcaption,.print-text-intro figcaption{font-size:12px;color:#64748b;margin-top:4px;}
	details.print-text-block > summary.print-text-summary::-webkit-details-marker{ display:none; }
	.rk-live-desc-details > summary.rk-live-desc-summary::-webkit-details-marker{ display:none; }

	.live-point-flow{
	  margin:0 0 12px 0;
	  padding:10px 12px 12px;
	  border:1px solid #d5dfd0;
	  border-left:4px solid #48673A;
	  border-radius:0 10px 10px 0;
	  background:linear-gradient(180deg,#f9fbf8 0%,#f4f7f2 100%);
	  font-size:13px;
	  line-height:1.45;
	  color:#1f2a1f;
	}
	.live-point-flow__head{
	  font-weight:700;
	  font-size:12px;
	  text-transform:uppercase;
	  letter-spacing:.03em;
	  color:#3d5433;
	  margin:10px 0 6px;
	}
	.live-point-flow__head:first-child{ margin-top:0; }
	.live-point-flow__panel{
	  margin:8px 0;
	  border:1px solid #dde6d8;
	  border-radius:8px;
	  background:#fff;
	  overflow:hidden;
	}
	.live-point-flow__summary{
	  cursor:pointer;
	  font-weight:700;
	  font-size:12px;
	  text-transform:uppercase;
	  letter-spacing:.03em;
	  color:#3d5433;
	  padding:8px 10px;
	  list-style:none;
	  background:#f0f5ee;
	}
	.live-point-flow__summary::-webkit-details-marker{ display:none; }
	.live-point-flow__content{
	  padding:8px 10px 10px;
	}
	.live-point-code{
	  display:inline-block;
	  margin-left:6px;
	  padding:1px 8px;
	  border-radius:6px;
	  background:#e8eee6;
	  font-weight:800;
	  letter-spacing:0;
	  text-transform:none;
	}
	.live-point-flow__steps{ margin:4px 0 0 1.1em; padding:0; }
	.live-point-flow__steps li{ margin:4px 0; }
	.live-point-flow__prose{ margin:4px 0 0; }
	.live-point-flow__checks{ list-style:none; margin:4px 0 0; padding:0; }
	.live-point-flow__checks li{
	  display:flex;
	  gap:8px;
	  align-items:flex-start;
	  margin:6px 0;
	  padding:6px 8px;
	  border-radius:8px;
	  background:rgba(255,255,255,.65);
	  border:1px solid #e0e8db;
	}
	.live-check-marker{ flex:0 0 auto; color:#48673A; font-weight:700; line-height:1.4; }
	.live-check-text{ flex:1; min-width:0; }

	.directcheck-root{ margin-top:6px; --dc-neutral-bg:#ffffff; --dc-neutral-bd:#e2e8f0; --dc-green-bg:#ecfdf5; --dc-green-bd:#34d399; --dc-green-tx:#065f46; --dc-amber-bg:#fffbeb; --dc-amber-bd:#fbbf24; --dc-amber-tx:#92400e; --dc-red-bg:#fef2f2; --dc-red-bd:#fca5a5; --dc-red-tx:#991b1b; }
	.directcheck-details{ margin:4px 0 10px; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px 14px; background:linear-gradient(180deg,#fafbfc 0%,#f8fafc 100%); }
	.directcheck-summary{ cursor:pointer; font-weight:600; color:#1e293b; list-style:none; display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
	.directcheck-summary::-webkit-details-marker{ display:none; }
	.directcheck-summary-title{ margin-right:6px; }
	.directcheck-inner{ margin-top:10px; }
	.directcheck-bullet-list{ margin:0; padding-left:1.15em; list-style:disc; }
	.directcheck-bullet-list > li.dc-kp-item{ margin:10px 0; padding-left:2px; }
	.directcheck-stage-inner{ display:inline-block; padding:5px 11px; border-radius:999px; font-weight:700; font-size:11px; letter-spacing:.02em; text-transform:uppercase; }
	.directcheck-stage-inner.stage-ok{ background:var(--dc-green-bg); color:var(--dc-green-tx); border:1px solid var(--dc-green-bd); }
	.directcheck-stage-inner.stage-partial{ background:var(--dc-amber-bg); color:var(--dc-amber-tx); border:1px solid var(--dc-amber-bd); }
	.directcheck-stage-inner.stage-none{ background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; }
	.dc-row{
	  display:flex; align-items:flex-start; text-align:left; width:100%; max-width:100%;
	  padding:10px 12px; border-radius:10px; border:1px solid var(--dc-neutral-bd);
	  background:var(--dc-neutral-bg); cursor:pointer; font:inherit; color:#0f172a;
	  box-shadow:0 1px 2px rgba(15,23,42,.04); transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
	}
	.dc-row:hover:not(:disabled){ border-color:#94a3b8; box-shadow:0 2px 6px rgba(15,23,42,.06); }
	.dc-row:disabled{ opacity:.72; cursor:default; }
	.dc-row-label{ flex:1; min-width:0; text-align:left; line-height:1.45; }
	.dc-state-none{ background:var(--dc-neutral-bg); border-color:var(--dc-neutral-bd); color:#334155; }
	.dc-state-green{ background:var(--dc-green-bg) !important; border-color:var(--dc-green-bd) !important; color:var(--dc-green-tx) !important; border-left-width:4px !important; }
	.dc-state-orange{ background:var(--dc-amber-bg) !important; border-color:var(--dc-amber-bd) !important; color:var(--dc-amber-tx) !important; border-left-width:4px !important; }
	.dc-state-red{ background:var(--dc-red-bg) !important; border-color:var(--dc-red-bd) !important; color:var(--dc-red-tx) !important; border-left-width:4px !important; }
	.dc-subs-for-line{ margin:8px 0 4px 1em; padding:10px 10px 8px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
	.dc-subs-caption{ font-size:12px; font-weight:600; color:#64748b; margin-bottom:8px; }
	.directcheck-followup{ margin-top:14px; padding:12px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
	.directcheck-sub-chips{ list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:8px; }
	.directcheck-sub-chips li{ margin:0; }
	.dc-sub-chip{
	  display:inline-block; padding:6px 11px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc; color:#475569;
	  cursor:pointer; font-size:12px; text-align:left; max-width:100%; transition: background .15s ease, border-color .15s ease, color .15s ease;
	}
	.dc-sub-chip:hover:not(:disabled){ background:#f1f5f9; border-color:#94a3b8; }
	.dc-sub-chip.is-hit{ background:#fef2f2; border-color:#f87171; color:#b91c1c; font-weight:600; }
	.dc-sub-chip:disabled{ opacity:.68; cursor:default; }
	.directcheck-obs-label{ display:block; margin-top:0; font-weight:600; font-size:13px; color:#334155; }
	.directcheck-obs-label textarea{ width:100%; margin-top:6px; box-sizing:border-box; border-radius:8px; border:1px solid #cbd5e1; padding:8px 10px; }
	.directcheck-empty{ color:#64748b; font-size:14px; }
	.rk-rm-sum-intro{ margin-bottom:12px; font-size:14px; color:#334155; line-height:1.45; }
	.rk-rm-sum-inner{ min-height:2em; }
	.rk-rm-sum-table{ width:100%; border-collapse:collapse; font-size:14px; margin-top:6px; }
	.rk-rm-sum-table th, .rk-rm-sum-table td{ padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; }
	.rk-rm-sum-table th{ font-weight:600; color:#475569; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
	.rk-rm-sum-row--ok{ background:#ecfdf5; border-left:4px solid #34d399; }
	.rk-rm-sum-row--teil{ background:#fffbeb; border-left:4px solid #f59e0b; }
	.rk-rm-sum-row--bad{ background:#fef2f2; border-left:4px solid #ef4444; }
	.rk-rm-sum-row--offen{ background:#f8fafc; border-left:4px solid #94a3b8; }
	.field.rk-field-gesamtstufe--warn{ box-shadow:inset 0 0 0 2px #f59e0b; border-radius:10px; padding:4px; margin-left:-4px; margin-right:-4px; transition:box-shadow .2s ease; }
	</style>";
	}

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

    $tplSchemaEsc = htmlspecialchars($tplSchema, ENT_QUOTES, "UTF-8");
    echo "<div class='rkrk-card'>
            <form method='post' action='?mode=save' data-rk-template-schema=\"$tplSchemaEsc\">
            <input type='hidden' name='file' value='$file'>";
	if (is_array($story) && $storyId !== "") {
		echo "<input type='hidden' name='story' value='" . htmlspecialchars($storyId, ENT_QUOTES, "UTF-8") . "'>";
		if (function_exists("rkrk_assess_storyboard_emit_run_ui")) {
			rkrk_assess_storyboard_emit_run_ui($story, $sections);
		}
	}
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

    /* Einspaltig: Sektionen in Template-Reihenfolge; Rollenübersicht direkt vor der Sektion mit Gesamtstufe. */
    $rkRollenmodellSummaryEmitted = false;
    foreach ($sections as $secName => $fields) {
        if (
            !$rkRollenmodellSummaryEmitted
            && rkrk_live_sections_contain_directcheck($sections)
            && rkrk_live_section_contains_gesamtstufe($fields)
        ) {
            echo "<div class='rkrk-card' id='rk-rollenmodell-summary' style='margin-bottom:12px;'>
            <h3 style='margin:0 0 8px;font-size:1.05rem;'>Rollenmodell – Übersicht</h3>
            <p class='rk-rm-sum-intro' style='margin:0 0 10px;'>Status der Stufen 6–1 je Direct-Check-Feld. Bei teilweiser Erfüllung wird die Gesamtstufe darunter markiert.</p>
            <div id='rk-rollenmodell-summary-inner' class='rk-rm-sum-inner'><span style='color:#64748b;font-size:14px;'>Noch keine Bewertungen</span></div>
        </div>";
            $rkRollenmodellSummaryEmitted = true;
        }
        rkrk_live_emit_checklist_section($secName, $fields, $saved, $savedRaw, $extraFields, $readonly, $isGuest);
    }

    if (!$readonly && !$isGuest){
        echo "<button class='btn btn-primary'>✓ Speichern</button>";
    }

    echo "</form></div>";

    echo "<script>
(function(){
\"use strict\";
function norm(s){ return String(s||\"\").trim().replace(/\\s+/g,\" \").toLowerCase(); }
function payload(){
  var el = document.getElementById(\"rk-live-pilot-stufe-map\");
  if (!el) return null;
  try { return JSON.parse(el.textContent || \"{}\"); } catch(e){ return null; }
}
function rowsFrom(data){ return (data && Array.isArray(data.pilots)) ? data.pilots : []; }
function findStufe(rows, typed){
  var t = norm(typed);
  if (!t || !rows.length) return NaN;
  for (var i = 0; i < rows.length; i++){
    var r = rows[i];
    if (r && norm(r.name) === t) return parseInt(String(r.stufe), 10);
  }
  return NaN;
}
function refDetails(){ return Array.from(document.querySelectorAll(\"details.print-text-block[data-rk-ref-stufe]\")); }
function pilotInput(){ return document.querySelector('[data-field-key=\"pilot\"]'); }
function badgeEl(){ return document.querySelector(\"[data-rk-pilot-liste-badge]\"); }
function applyPilotRefOpen(){
  var form = document.querySelector(\"form[data-rk-template-schema]\");
  if (!form || form.getAttribute(\"data-rk-template-schema\") !== \"rkrs_pilot_v01\") return;
  var rows = rowsFrom(payload());
  var inp = pilotInput();
  var badge = badgeEl();
  var st = findStufe(rows, inp ? inp.value : \"\");
  if (badge){
    if (st >= 1 && st <= 6){
      var lo = Math.max(1, st - 1), hi = Math.min(6, st + 1);
      badge.textContent = \"Personal (Liste): Stufe \" + st + \" — Referenz «Stufe \" + lo + \"» bis «Stufe \" + hi + \"» sind geoeffnet.\";
    } else if (rows.length){
      badge.textContent = \"Kein Stufenwert in der Personaldatei zu diesem Namen — Referenztexte zugeklappt.\";
    } else {
      badge.textContent = \"\";
    }
  }
  refDetails().forEach(function(det){
    var slot = parseInt(det.getAttribute(\"data-rk-ref-stufe\") || \"0\", 10);
    if (!(st >= 1 && st <= 6)){ det.open = false; return; }
    det.open = (slot >= st - 1 && slot <= st + 1);
  });
}
document.addEventListener(\"DOMContentLoaded\", function(){
  var inp = pilotInput();
  if (inp){ inp.addEventListener(\"input\", applyPilotRefOpen); inp.addEventListener(\"change\", applyPilotRefOpen); }
  applyPilotRefOpen();
});
})();
</script>";

    echo "<script>
(function(){
\"use strict\";
var GAPS = {
  \"DJI Mavic 2AE\": [
    \"Flugzeit\",
    \"RGB-Zoom\",
    \"W\\u00e4rmebild\",
    \"Stroboskop\",
    \"Nachtflug\"
  ],
  \"Yuneec H520E + E10T\": [
    \"Flugzeit\",
    \"RGB-Weitwinkel\",
    \"RGB-Zoom\",
    \"W\\u00e4rmebild\",
    \"Hinderniserkennung\",
    \"RC-\\u00dcbertragung\",
    \"POI-Markierung\",
    \"Stroboskop\",
    \"Nachtflug\"
  ],
  \"Yuneec H520E + E10Tx\": [
    \"Flugzeit\",
    \"RGB-Weitwinkel\",
    \"RGB-Zoom\",
    \"W\\u00e4rmebild\",
    \"Hinderniserkennung\",
    \"RC-\\u00dcbertragung\",
    \"POI-Markierung\",
    \"Stroboskop\",
    \"Nachtflug\"
  ]
};
var GREEN_SUITABLE = {
  \"DJI Mavic 3TD\": 1,
  \"DJI Matrice M30T\": 1,
  \"DJI Matrice 4T\": 1,
  \"DJI Matrice 4TD\": 1
};
var ABZUEGE = [
  { code: \"A\", label: \"A \\u2014 nicht vorhanden\" },
  { code: \"B\", label: \"B \\u2014 nicht aufgebaut\" },
  { code: \"C\", label: \"C \\u2014 defekt\" },
  { code: \"D\", label: \"D \\u2014 Vorweis unvollst\\u00e4ndig\" },
  { code: \"E\", label: \"E \\u2014 Menge\" },
  { code: \"F\", label: \"F \\u2014 Dokument\" },
  { code: \"G\", label: \"G \\u2014 ungeeignet\", recommended: true }
];
var droneAbzugSelected = [];
var droneAbzugListBuilt = false;
var lastDroneTypApplied = null;
function normDroneVal(v){
  return String(v || \"\").replace(/\\s*\\(([gro])\\)\\s*$/i, \"\").trim();
}
function typColor(raw){
  var m = String(raw || \"\").match(/\\(([gro])\\)\\s*$/i);
  return m ? m[1].toLowerCase() : \"\";
}
function m1DrohnentypRemarkField(){
  return document.querySelector('[data-field-key=\"m_1__bemerkung\"]');
}
function currentDroneTypRaw(){
  var radios = document.querySelectorAll('[data-field-key=\"m_1__drohnentyp\"]');
  for (var i = 0; i < radios.length; i++) {
    if (radios[i].checked) return String(radios[i].value || \"\").trim();
  }
  return \"\";
}
function currentDroneTyp(){
  return normDroneVal(currentDroneTypRaw());
}
function isGreenOther(){
  return currentDroneTyp() === \"Andere\" && typColor(currentDroneTypRaw()) === \"g\";
}
function isRedOther(){
  return currentDroneTyp() === \"Andere\" && typColor(currentDroneTypRaw()) === \"r\";
}
function isFullyGreenTyp(){
  var raw = currentDroneTypRaw();
  if (!raw) return false;
  if (typColor(raw) !== \"g\") return false;
  var typ = currentDroneTyp();
  if (typ === \"Andere\") return true;
  return !!GREEN_SUITABLE[typ];
}
function isTypSpecFail(){
  var typ = currentDroneTyp();
  if (!typ) return false;
  if (isFullyGreenTyp()) return false;
  if (typColor(currentDroneTypRaw()) === \"o\") return false;
  return true;
}
function specFieldWrap(){
  return document.querySelector('[data-rk-drone-spec=\"1\"]');
}
function m2SpecRadios(){
  return document.querySelectorAll('[data-field-key=\"m_2__drohnen_spezifikationen\"]');
}
function m2SpecRemarkField(){
  return document.querySelector('[data-field-key=\"m_2__bemerkung\"]');
}
function getM2SpecStufe(){
  var radios = m2SpecRadios();
  for (var i = 0; i < radios.length; i++) {
    if (radios[i].checked) {
      var m = String(radios[i].value || \"\").match(/^(\\d)/);
      return m ? parseInt(m[1], 10) : null;
    }
  }
  return null;
}
function setM2SpecStufe(stufe){
  var target = String(stufe);
  var radios = m2SpecRadios();
  for (var i = 0; i < radios.length; i++) {
    var v = String(radios[i].value || \"\");
    var checked = v.charAt(0) === target;
    radios[i].checked = checked;
    var lbl = radios[i].closest(\".color-option\");
    if (lbl) lbl.classList.toggle(\"selected\", checked);
  }
  if (radios.length) {
    radios[0].dispatchEvent(new Event(\"change\", { bubbles: true }));
  }
}
function resetSpecRating(){
  m2SpecRadios().forEach(function(r){
    r.checked = false;
    var lbl = r.closest(\".color-option\");
    if (lbl) lbl.classList.remove(\"selected\");
  });
}
function collectFailedSpecsFromGaps(){
  var typ = currentDroneTyp();
  var keys = GAPS[typ];
  if (!keys || !keys.length) return [];
  return keys.slice();
}
function applyGapsIfTypChanged(typ){
  if (typ === lastDroneTypApplied) return;
  lastDroneTypApplied = typ;
  if (!isTypSpecFail()) return;
  if (getM2SpecStufe() !== null) return;
  if (GAPS[typ] && GAPS[typ].length) setM2SpecStufe(0);
}
function stripM2RemarkPrefixes(val){
  return String(val || \"\")
    .replace(/^Fehlende MUSS:\s*[^\n|]+(?:\s*\|\s*)?/i, \"\")
    .trim();
}
function syncM2SpecRemarkFromGaps(){
  var ta = m2SpecRemarkField();
  if (!ta || !isTypSpecFail()) return;
  var failed = collectFailedSpecsFromGaps();
  if (!failed.length) return;
  var base = stripM2RemarkPrefixes(ta.value);
  var prefix = \"Fehlende MUSS: \" + failed.join(\"; \");
  ta.value = base ? (prefix + \": \" + base) : prefix;
}
function stripTypRemarkPrefixes(val){
  return String(val || \"\")
    .replace(/^Abzug [A-G](?:, [A-G])*(?: \\([^)]+\\))?:\\s*/i, \"\")
    .replace(/^Fehlende MUSS:\\s*[^\\n|]+(?:\\s*\\|\\s*)?/i, \"\")
    .replace(/^Typ:\\s*[^\\n|]+(?:\\s*\\|\\s*)?/i, \"\")
    .trim();
}
function syncTypRemarkCombined(){
  var ta = m1DrohnentypRemarkField();
  if (!ta) return;
  var typ = currentDroneTyp();
  var base = stripTypRemarkPrefixes(ta.value);
  var parts = [];
  if (droneAbzugSelected.length) {
    parts.push(\"Abzug \" + droneAbzugSelected.slice().sort().join(\", \") + (typ ? \" (\" + typ + \")\" : \"\"));
  }
  /* Fehlende MUSS → M.2# Bemerkung (syncM2SpecRemarkFromGaps) */
  if (isGreenOther() && base && !/^Typ:/i.test(ta.value)) {
    parts.push(\"Typ: \" + base);
    base = \"\";
  }
  ta.value = (parts.length ? parts.join(\" | \") + (base ? \": \" : \"\") : \"\") + base;
}
function buildDroneAbzugButtons(){
  var host = document.getElementById(\"rk-drone-abzug-list\");
  if (!host || droneAbzugListBuilt) return;
  droneAbzugListBuilt = true;
  ABZUEGE.forEach(function(a){
    var btn = document.createElement(\"button\");
    btn.type = \"button\";
    btn.className = \"notfulfilled-btn rk-drone-abzug-btn\" + (a.recommended ? \" recommended\" : \"\");
    btn.setAttribute(\"data-abzug-code\", a.code);
    btn.textContent = a.label;
    btn.addEventListener(\"click\", function(){
      var code = a.code;
      var idx = droneAbzugSelected.indexOf(code);
      if (idx >= 0) {
        droneAbzugSelected.splice(idx, 1);
        btn.classList.remove(\"selected\");
      } else {
        droneAbzugSelected.push(code);
        btn.classList.add(\"selected\");
      }
      syncTypRemarkCombined();
    });
    host.appendChild(btn);
  });
}
function selectDroneAbzugDefault(){
  var host = document.getElementById(\"rk-drone-abzug-list\");
  if (!host) return;
  droneAbzugSelected = [\"G\"];
  host.querySelectorAll(\".rk-drone-abzug-btn\").forEach(function(btn){
    btn.classList.toggle(\"selected\", btn.getAttribute(\"data-abzug-code\") === \"G\");
  });
  syncTypRemarkCombined();
}
function clearDroneAbzugSelection(){
  droneAbzugSelected = [];
  var host = document.getElementById(\"rk-drone-abzug-list\");
  if (host) {
    host.querySelectorAll(\".rk-drone-abzug-btn\").forEach(function(btn){
      btn.classList.remove(\"selected\");
    });
  }
  syncTypRemarkCombined();
}
function setFieldVisible(wrap, show){
  if (!wrap) return;
  wrap.style.display = show ? \"\" : \"none\";
  wrap.hidden = !show;
}
function syncTypBemerkungVisibility(){
  var m1Wrap = document.querySelector('.remark-with-meta[data-ro-parent-key=\"m_1__drohnentyp\"]');
  if (m1Wrap && isGreenOther()) {
    m1Wrap.classList.add('visible');
    m1Wrap.hidden = false;
    m1Wrap.style.display = '';
    return;
  }
  if (typeof window.rkrkRefreshRemarkDependents === \"function\") {
    window.rkrkRefreshRemarkDependents(\"m_1__drohnentyp\");
  }
}
function syncDroneTypSpecPanel(){
  var typ = currentDroneTyp();
  var specWrap = specFieldWrap();
  var okHint = document.getElementById(\"rk-drone-typ-ok-hint\");
  var abzugBox = document.getElementById(\"rk-drone-abzug-box\");
  if (!typ) {
    setFieldVisible(specWrap, false);
    if (okHint) okHint.hidden = true;
    if (abzugBox) abzugBox.hidden = true;
    clearDroneAbzugSelection();
    return;
  }
  if (isFullyGreenTyp()) {
    setM2SpecStufe(2);
    setFieldVisible(specWrap, false);
    if (okHint) okHint.hidden = true;
    if (abzugBox) abzugBox.hidden = true;
    clearDroneAbzugSelection();
    return;
  }

  setFieldVisible(specWrap, true);
  if (typColor(currentDroneTypRaw()) === \"o\") {
    if (okHint) okHint.hidden = true;
    if (abzugBox) abzugBox.hidden = true;
    clearDroneAbzugSelection();
    return;
  }
  if (isTypSpecFail()) {
    if (okHint) okHint.hidden = true;
    if (abzugBox) abzugBox.hidden = false;
    buildDroneAbzugButtons();
    applyGapsIfTypChanged(typ);
    syncM2SpecRemarkFromGaps();
    if (!droneAbzugSelected.length) selectDroneAbzugDefault();
    return;
  }
  if (okHint) okHint.hidden = true;
  if (abzugBox) abzugBox.hidden = true;
  clearDroneAbzugSelection();
}
function updateDroneTypSelection(){
  buildDroneAbzugButtons();
  syncDroneTypSpecPanel();
  syncTypBemerkungVisibility();
  syncM2SpecRemarkFromGaps();
  syncTypRemarkCombined();
  if (typeof window.rkrkRefreshRemarkDependents === \"function\") {
    window.rkrkRefreshRemarkDependents(\"m_2__drohnen_spezifikationen\");
  }
}
document.addEventListener(\"DOMContentLoaded\", function(){
  updateDroneTypSelection();
});
document.addEventListener(\"change\", function(e){
  if (e.target && e.target.matches('[data-field-key=\"m_1__drohnentyp\"]')) {
    lastDroneTypApplied = null;
    droneAbzugSelected = [];
    resetSpecRating();
    updateDroneTypSelection();
  }
});
})();
</script>";

    if ($createdAt){
        echo "<div class='system-info-box'>🕒 Erstellt am: ".safe($createdAt)."</div>";
    }

    if ($modifiedAt){
        echo "<div class='system-info-bottom' style='display:flex;align-items:center;gap:6px;'><span style='display:inline-flex;' aria-hidden='true'>" . rkrk_checklist_toolbar_svg_edit() . "</span><span>Zuletzt bearbeitet am: " . safe($modifiedAt) . "</span></div>";
    }

    if ($edit){
        rkrk_checklist_toolbar_echo_styles_once();
        echo "<div style='text-align:center;margin-top:20px;'>
                <a href='".safe(rkrk_assess_append_setting_href("?mode=print&id=" . rawurlencode((string)$edit)))."' class='chk-tbtn' target='_blank' rel='noopener' title='Druckansicht'>" . rkrk_checklist_toolbar_svg_print() . "<span>PDF drucken</span></a>";
        $__repBottom = function_exists("rkrk_checklist_toolbar_list_report_link")
            ? rkrk_checklist_toolbar_list_report_link((string)$edit, "live")
            : "";
        if ($__repBottom !== "") {
            echo "<a href='".safe($__repBottom)."' class='chk-tbtn' target='_blank' rel='noopener' title='PDF-Abschlussbericht'>" . rkrk_checklist_toolbar_svg_pdf() . "<span>PDF-Report</span></a>";
        }
        if (!(function_exists("rkrk_assess_hide_overview_back_link") && rkrk_assess_hide_overview_back_link())) {
            echo "<a href='".safe(rkrk_assess_append_setting_href("?mode=listall"))."' class='btn'>📄 Zurück zu den Live-Beurteilungen</a>";
        }
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
					<option value='directcheck'>Direct check</option>
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
			"kind" => "live",
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

/**
 * PDF/HTML-Abschlussreport für eine Live-Beurteilung neu erzeugen.
 * @return array{ok:bool, path:string, format:string, error:string}
 */
function rkrk_assess_live_regenerate_print_report(string $resultId, string $previousId = ""): array
{
	$resultId = basename(preg_replace('/\.json$/i', "", $resultId));
	$previousId = basename(preg_replace('/\.json$/i', "", $previousId));
	$empty = array("ok" => false, "path" => "", "format" => "", "error" => "unavailable");
	if ($resultId === "") {
		return array("ok" => false, "path" => "", "format" => "", "error" => "empty id");
	}
	$inc = __DIR__ . "/rkrk_assess_report_pdf.inc.php";
	if (is_readable($inc)) {
		@include_once $inc;
	}
	if (!function_exists("rkrk_assess_save_checklist_print_report") || !function_exists("showChecklistPrint")) {
		return $empty;
	}

	// Alte Reports entfernen, falls der Stem beim Speichern umbenannt wurde
	$oldIds = array();
	if ($previousId !== "" && $previousId !== $resultId) {
		$oldIds[] = $previousId;
	}
	foreach ($oldIds as $oldId) {
		if (!function_exists("rkrk_assess_reports_dir")) {
			break;
		}
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = "live";
		$dir = rkrk_assess_reports_dir();
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		if ($dir === "") {
			continue;
		}
		foreach (array(".pdf", ".html", ".prep.pdf") as $ext) {
			$p = $dir . $oldId . $ext;
			if (is_file($p)) {
				@unlink($p);
			}
		}
	}

	$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
	$GLOBALS["rkrk_assess_reports_kind"] = "live";
	$res = rkrk_assess_save_checklist_print_report($resultId, static function () use ($resultId) {
		$prev = $_GET["id"] ?? null;
		$_GET["id"] = $resultId;
		$GLOBALS["rkrk_assess_print_no_exit"] = true;
		$GLOBALS["rkrk_assess_print_hide_unrated"] = true;
		$GLOBALS["rkrk_assess_print_compact"] = true;
		$_GET["hide_ablauf"] = "1";
		$_GET["hide_printtype"] = "1";
		$_GET["hide_referenz"] = "1";
		while (ob_get_level() > 0) {
			// innere Capture-Ebene: bestehende Buffer nicht zerstören — nur neue Ebene
			break;
		}
		ob_start();
		try {
			showChecklistPrint();
			$html = (string) ob_get_clean();
		} catch (\Throwable $e) {
			if (ob_get_level() > 0) {
				ob_end_clean();
			}
			$html = "";
		}
		unset($GLOBALS["rkrk_assess_print_no_exit"], $GLOBALS["rkrk_assess_print_hide_unrated"], $GLOBALS["rkrk_assess_print_compact"]);
		unset($_GET["hide_ablauf"], $_GET["hide_printtype"], $_GET["hide_referenz"]);
		if ($prev === null) {
			unset($_GET["id"]);
		} else {
			$_GET["id"] = $prev;
		}
		return $html;
	});
	if ($prevKind === null) {
		unset($GLOBALS["rkrk_assess_reports_kind"]);
	} else {
		$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
	}
	return is_array($res) ? $res : $empty;
}

function saveChecklist(){
    global $rkrkLiveassessResults , $rkrkLiveassessTemplates;

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
	// Storyboard: vor Speichern n.b. erzwingen — Summen/Bestehen/Report unverändert über Template.
	$storyIdSave = function_exists("rkrk_assess_storyboard_request_id") ? rkrk_assess_storyboard_request_id() : "";
	$storySave = ($storyIdSave !== "" && function_exists("rkrk_assess_storyboard_load"))
		? rkrk_assess_storyboard_load($storyIdSave)
		: null;
	if (is_array($storySave) && function_exists("rkrk_assess_storyboard_apply_nb_to_saved")) {
		$tplForNb = loadTemplate($file);
		$secForNb = is_array($tplForNb) && isset($tplForNb["sections"]) ? $tplForNb["sections"] : array();
		rkrk_assess_storyboard_apply_nb_to_saved($secForNb, $fields, $storySave);
	}
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
    $previousId = "";
    if ($editId){
        $id = basename($editId);
        $previousId = $id;
        $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/$id.json";
        $existing = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $createdAt = is_array($existing) && isset($existing['created_at']) ? (string) $existing['created_at'] : date('c');
    } else {

        $createdAt = date('c');

        $tplSlug = function_exists("rkrk_assess_checklist_title_slug")
            ? rkrk_assess_checklist_title_slug($file, (string) $rkrkLiveassessTemplates)
            : slugifyTitle(pathinfo($file, PATHINFO_FILENAME));
        $resDir = rtrim((string)$rkrkLiveassessResults, "/\\");
        $id = function_exists("rkrk_assess_build_checklist_result_json_stem")
            ? rkrk_assess_build_checklist_result_json_stem($tplSlug, $fields, $createdAt)
            : $tplSlug;
        if (function_exists("rkrk_assess_unique_checklist_result_stem")) {
            $id = rkrk_assess_unique_checklist_result_stem($resDir, $id);
        }
        $path = $resDir . "/$id.json";
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
	// Optionale Meta (ändert weder Felder noch Report-Logik)
	if (is_array($storySave) && $storyIdSave !== "") {
		$data['storyboard_id'] = $storyIdSave;
		$data['storyboard_version'] = (string) ($storySave['version'] ?? '');
		$data['storyboard_title'] = (string) ($storySave['title'] ?? '');
	}

	$resDir = rtrim((string)$rkrkLiveassessResults, "/\\");
	if ($editId && function_exists("rkrk_assess_build_checklist_result_json_stem")) {
		$tplSlug = function_exists("rkrk_assess_checklist_title_slug")
			? rkrk_assess_checklist_title_slug($file, (string) $rkrkLiveassessTemplates)
			: slugifyTitle(pathinfo($file, PATHINFO_FILENAME));
		$ymdSrc = function_exists("rkrk_assess_checklist_completion_stamp")
			? rkrk_assess_checklist_completion_stamp($id, $createdAt)
			: $createdAt;
		$newStem = rkrk_assess_build_checklist_result_json_stem($tplSlug, $fields, $ymdSrc);
		if (function_exists("rkrk_assess_unique_checklist_result_stem")) {
			$newStem = rkrk_assess_unique_checklist_result_stem($resDir, $newStem, is_file($path) ? $path : null);
		}
		if ($newStem !== "" && $newStem !== $id) {
			$oldStem = $id;
			$newPath = $resDir . "/" . $newStem . ".json";
			if ($newPath !== $path) {
				$rpNew = is_file($newPath) ? realpath($newPath) : false;
				$rpOld = is_file($path) ? realpath($path) : false;
				if ($rpNew !== false && ($rpOld === false || $rpNew !== $rpOld)) {
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
					$GLOBALS["rkrk_assess_reports_kind"] = "live";
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
			"kind" => "live",
			"file" => $file,
			"edit" => (string) ($editId ? basename((string) $editId) : ""),
			"draft_id" => $__draft,
		));
		if ($editId) {
			rkrk_assess_checklist_progress_delete(array(
				"kind" => "live",
				"file" => $file,
				"edit" => basename((string) $editId),
				"draft_id" => "",
			));
		}
	}

	// PDF-Report immer neu erzeugen (aktueller Stand der gespeicherten Beurteilung)
	rkrk_assess_live_regenerate_print_report((string) $id, (string) $previousId);

    rkrk_redirect("?mode=list&saved=1");
}


/* -----------------------------------------------------------
   CHECKLISTE LÖSCHEN
----------------------------------------------------------- */
function deleteJsonChecklist(){
    global $rkrkLiveassessResults , $InputUserInfo;

    //$isGuest = ($InputUserInfo["Login"]["Level"] === "gast");
	$isGuest=false;
	
	if ($isGuest) {
        echo "<div class='rkrk-card danger'>❌ Gäste dürfen keine Einträge löschen.</div>";
        return;
    }

    $id = basename($_GET['id'] ?? "");
    $path = rtrim((string)$rkrkLiveassessResults, "/\\") . "/$id.json";

    if (file_exists($path)) unlink($path);

	// Zugehörigen Report mitlöschen (neues + altes Format)
	if ($id !== "" && function_exists("rkrk_assess_reports_dir")) {
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = "live";
		$dir = rkrk_assess_reports_dir();
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		if ($dir !== "") {
			foreach (array(".pdf", ".html", ".prep.pdf") as $ext) {
				$p = $dir . $id . $ext;
				if (is_file($p)) {
					@unlink($p);
				}
			}
		}
	}

    rkrk_redirect("?mode=list&deleted=1");
}

/* -----------------------------------------------------------
 TEMPLATE FIELDS API (für LinkBuilder Dropdown)
----------------------------------------------------------- */
function templateFieldsApi(){
    global $rkrkLiveassessTemplates;

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
 LINK BUILDER (Live-Beurteilungs-URL-Generator)
----------------------------------------------------------- */
function showLinkBuilder(){
    global $rkrkLiveassessTemplates;

    pageHeaderLocal("Live-Beurteilungs-URL-Generator");

    // Basis-URL (rkrk_checklist.php)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $selfUrl = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    /* -------------------------------------------------------
       SERVERSEITIG: Template -> Sections -> Labels Map bauen
       (ohne fetch/API, sofort verfügbar)
    ------------------------------------------------------- */
    $fieldMap = [];
    foreach (glob($rkrkLiveassessTemplates . "/*.json") as $tplFile){
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
    foreach (glob($rkrkLiveassessTemplates . "/*.json") as $tplFile){
        $base  = basename($tplFile);
        $tpl   = loadTemplate($base);
        $title = isset($tpl['title']) ? $tpl['title'] : $base;
        $tplOptions .= "<option value=\"" . safe($base) . "\">" . safe($title) . "</option>\n";
    }

    echo <<<HTML
<div class='rkrk-card'>
  <h3>🔗 Live-Beurteilungs-URL erzeugen</h3>
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
   Migration: Live-Reports aus Checklisten-JSON neu schreiben
   (nur bewertete Punkte; bestehende HTML mit „unbewertet“ aktualisieren)
----------------------------------------------------------- */
function rkrk_assess_live_migrate_reports_rated_only(): int {
	static $ran = false;
	if ($ran) {
		return 0;
	}
	$ran = true;
	if (!function_exists("rkrk_assess_live_regenerate_print_report") && !function_exists("rkrk_assess_save_checklist_print_report")) {
		return 0;
	}
	if (!function_exists("showChecklistPrint")) {
		return 0;
	}
	global $rkrkLiveassessResults;
	$resDir = rtrim(str_replace("\\", "/", (string) $rkrkLiveassessResults), "/");
	if ($resDir === "" || !is_dir($resDir)) {
		return 0;
	}
	$files = glob($resDir . "/*.json");
	if (!is_array($files) || $files === array()) {
		return 0;
	}
	$reportsDir = "";
	if (function_exists("rkrk_assess_reports_dir")) {
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = "live";
		$reportsDir = rkrk_assess_reports_dir();
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
	}
	$updated = 0;
	foreach ($files as $jsonPath) {
		if (!is_string($jsonPath) || !is_file($jsonPath)) {
			continue;
		}
		$id = pathinfo($jsonPath, PATHINFO_FILENAME);
		if ($id === "") {
			continue;
		}
		$needs = true;
		if ($reportsDir !== "") {
			$htmlPath = $reportsDir . $id . ".html";
			$pdfPath = $reportsDir . $id . ".pdf";
			if (is_file($htmlPath)) {
				$raw = (string) @file_get_contents($htmlPath);
				$marker = $reportsDir . ".compact_style_v3";
				$needs = ($raw === "")
					|| (stripos($raw, "unbewertet") !== false)
					|| (stripos($raw, "rkrk_assess_theme") !== false)
					|| (stripos($raw, "/rkropen/") !== false)
					|| (stripos($raw, "rk-drone-req-print") !== false)
					|| (stripos($raw, "NKL-Spezifikation") !== false)
					|| !is_file($marker);
			} elseif (is_file($pdfPath)) {
				// Einmalig: Compact-Reports ohne Referenzteil (Marker unter reports/)
				$marker = $reportsDir . ".compact_style_v3";
				$needs = !is_file($marker);
			} else {
				$needs = true; // noch kein Report → anlegen
			}
		}
		if (!$needs) {
			continue;
		}
		$res = rkrk_assess_live_regenerate_print_report((string) $id);
		if (is_array($res) && !empty($res["ok"])) {
			$updated++;
		}
	}
	if ($reportsDir !== "" && is_dir($reportsDir)) {
		@file_put_contents($reportsDir . ".compact_style_v3", date("c") . "\n", LOCK_EX);
	}
	return $updated;
}

$__rkrkLiveMigCount = rkrk_assess_live_migrate_reports_rated_only();
if (!empty($GLOBALS["rkrk_assess_regen_live_bootstrap"])) {
	$GLOBALS["rkrk_assess_regen_live_count"] = (int) $__rkrkLiveMigCount;
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

	case 'print':
		showChecklistPrint();
		break;

	case "exportjson":
		rkrk_checklist_toolbar_handle_export_json($rkrkLiveassessResults);
		exit;

	case "importjson":
		rkrk_checklist_toolbar_handle_import_json($rkrkLiveassessResults, static function (string $u): void {
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
</html>






