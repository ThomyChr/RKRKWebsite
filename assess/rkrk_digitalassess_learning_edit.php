<?php
/**
 * Schulungstext bearbeiten: Editor links, Live-Vorschau rechts (wie auf dem Server).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
ini_set("default_charset", "UTF-8");
if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("UTF-8");
}
if (!headers_sent()) {
	header("Content-Type: text/html; charset=UTF-8");
}

$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_inputforms.php";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_digitalassess_learning_shared.inc.php";
include_once __DIR__ . "/rkrk_digitalassess_read_render.php";

if (!$onRKRS) {
	CheckIPandExit($folderoffset);
	if (function_exists("WriteToLog")) {
		WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", $folderoffset);
	}
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	GrantAccess($folderoffset);
	if (empty($LoggedOn)) {
		if (function_exists("RegisterForm")) {
			RegisterForm(getIPAddress(), "Login", true);
		}
		exit;
	}
	$currentAccessLevel = "";
	if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
		$currentAccessLevel = strtolower(trim((string) $_SESSION[$SessNme_AccessLevel]));
	}
	$isGuestRequest = (isset($_GET["GA"]) && strtolower(trim((string) $_GET["GA"])) === "gast");
	$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== "gast" && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
	}
} else {
	rkrk_assess_theme_link_tags();
}

function dle_h($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function dle_read_assessments($path, $headers) {
	if (!function_exists("rkrk_assess_read_csv_file")) {
		return array();
	}
	list(, $rows) = rkrk_assess_read_csv_file($path, $headers, ";");
	if (!is_array($rows)) {
		return array();
	}
	foreach ($rows as $ri => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach ($row as $hk => $hv) {
			$rows[$ri][$hk] = dle_fix_mojibake((string) $hv);
		}
	}
	return $rows;
}

function dle_fix_mojibake($value) {
	$value = (string) $value;
	if ($value === "") {
		return $value;
	}
	$map = array(
		"Ã¤" => "ä", "Ã„" => "Ä",
		"Ã¶" => "ö", "Ã–" => "Ö",
		"Ã¼" => "ü", "Ãœ" => "Ü",
		"ÃŸ" => "ß",
		"â€“" => "–", "â€”" => "—",
		"â€ž" => "„", "â€œ" => "“", "â€" => "”",
		"â€˜" => "‘", "â€™" => "’",
		"Â°" => "°", "Â" => "",
	);
	return strtr($value, $map);
}

function dle_resolve_learning_path($quizLoc, $lernRel) {
	$lernRel = str_replace("\\", "/", trim((string) $lernRel));
	$lernRel = ltrim($lernRel, "/");
	if ($lernRel === "") {
		return "";
	}
	$candidates = array($quizLoc . $lernRel);
	if (function_exists("rkrk_dle_clean_relative_file")) {
		$clean = rkrk_dle_clean_relative_file($lernRel, "html");
		if ($clean !== "" && ($quizLoc . $clean) !== $candidates[0]) {
			$candidates[] = $quizLoc . $clean;
		}
	}
	foreach ($candidates as $cand) {
		if ($cand !== "" && is_file($cand)) {
			return $cand;
		}
	}
	return "";
}

function dle_find_assessment_index(array $assessments, $needleKey) {
	$needleKey = trim((string) $needleKey);
	if ($needleKey === "") {
		return null;
	}
	foreach ($assessments as $idx => $aRow) {
		if (!is_array($aRow)) {
			continue;
		}
		$k = trim((string) ($aRow["Key"] ?? ""));
		if ($k === "" ) {
			continue;
		}
		if ($k === $needleKey || strcasecmp($k, $needleKey) === 0) {
			return (int) $idx;
		}
	}
	return null;
}

$assessmentHeaders = array("Nr", "Key", "Titel", "Kurzbeschreibung", "Bild", "Einfuehrung", "Erfuellt", "PunkteModus", "DriveLocation", "LernDatei", "FragenDatei", "AnzQuestions", "Audienz", "QuizTime", "Quiz", "Schulung", "AntwortMischen", "FragenBildAnzeigen", "Aktiv", "Locked");
$assessmentPath = $QuizLoc . "AssessmentListe.csv";
$assessments = dle_read_assessments($assessmentPath, $assessmentHeaders);
$assessmentByKey = array();
foreach ($assessments as $idx => $aRow) {
	$key = trim((string) ($aRow["Key"] ?? ""));
	if ($key !== "") {
		$assessmentByKey[$key] = $idx;
	}
}

$selectedKey = isset($_REQUEST["selected_quiz"]) ? trim((string) $_REQUEST["selected_quiz"]) : "";
if ($selectedKey === "" && isset($_REQUEST["quiz"])) {
	$quizParam = trim((string) $_REQUEST["quiz"]);
	if ($quizParam !== "") {
		$quizIdx = dle_find_assessment_index($assessments, $quizParam);
		if ($quizIdx !== null) {
			$selectedKey = trim((string) ($assessments[$quizIdx]["Key"] ?? ""));
		} else {
			foreach ($assessments as $aRow) {
				$aKey = trim((string) ($aRow["Key"] ?? ""));
				$aTitle = trim((string) ($aRow["Titel"] ?? ""));
				if ($aKey !== "" && strcasecmp($aTitle, $quizParam) === 0) {
					$selectedKey = $aKey;
					break;
				}
			}
		}
	}
}
if ($selectedKey === "" && count($assessments) > 0) {
	$selectedKey = trim((string) ($assessments[0]["Key"] ?? ""));
}

$selectedAssessment = array_fill_keys($assessmentHeaders, "");
$selectedIdx = dle_find_assessment_index($assessments, $selectedKey);
if ($selectedIdx !== null) {
	$selectedAssessment = $assessments[$selectedIdx];
	$selectedKey = trim((string) ($selectedAssessment["Key"] ?? $selectedKey));
}

$message = "";
$error = "";
$learningContent = "";
$learningLoadWarn = "";
$learningResolvedPath = "";
if ($selectedKey !== "") {
	$lernRel = trim((string) $selectedAssessment["LernDatei"]);
	if ($lernRel === "") {
		$learningLoadWarn = "Für dieses Assessment ist keine LernDatei in der AssessmentListe eingetragen.";
	} else {
		$learningResolvedPath = dle_resolve_learning_path($QuizLoc, $lernRel);
		if ($learningResolvedPath === "") {
			$learningLoadWarn = "Schulungsdatei nicht gefunden: «" . $lernRel . "» (erwartet unter data/assess/).";
		} else {
			$learningContent = (string) @file_get_contents($learningResolvedPath);
			if ($learningContent === "") {
				$learningLoadWarn = "Schulungsdatei ist leer oder nicht lesbar: «" . basename($learningResolvedPath) . "».";
			}
		}
	}
}

$action = isset($_POST["admin_action"]) ? (string) $_POST["admin_action"] : "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && $action === "save_learning_html" && $selectedKey !== "") {
	$writeCsv = function ($path, $headers, $rows) {
		return function_exists("rkrk_assess_write_csv_file")
			? rkrk_assess_write_csv_file($path, $headers, $rows, ";")
			: false;
	};
	$_POST["learning_file_fallback"] = (string) $selectedAssessment["LernDatei"];
	$r = rkrk_dle_process_save_learning_html(
		$QuizLoc,
		$assessmentPath,
		$assessments,
		$assessmentHeaders,
		$selectedKey,
		$assessmentByKey,
		$_POST,
		$writeCsv
	);
	$message = (string) $r["message"];
	$error = (string) $r["error"];
	if ($r["learning_content"] !== "") {
		$learningContent = (string) $r["learning_content"];
	}
	if ($r["learn_file"] !== "" && $selectedIdx !== null) {
		$selectedAssessment["LernDatei"] = (string) $r["learn_file"];
		$assessments[$selectedIdx]["LernDatei"] = (string) $r["learn_file"];
	}
}

$daLearnEditor = rkrk_dle_learning_editor_parts($learningContent);
$daLearnLernRel = trim((string) ($selectedAssessment["LernDatei"] ?? ""));
$daLearnBaseHref = "";
$daLearnAssessRoot = "";
if ($daLearnLernRel !== "" && function_exists("rkrk_dga_rh_learn_base_dir_url")) {
	$daLearnBaseHref = rkrk_dga_rh_learn_base_dir_url($daLearnLernRel);
	if (function_exists("rkrk_assess_encode_url_for_request")) {
		$daLearnBaseHref = rkrk_assess_encode_url_for_request($daLearnBaseHref);
	}
}
if (function_exists("rkrk_dga_rh_web_assess_root")) {
	$daLearnAssessRoot = rkrk_dga_rh_web_assess_root();
} elseif (function_exists("rkrk_assess_quiz_data_assess_web_prefix")) {
	$daLearnAssessRoot = rkrk_assess_quiz_data_assess_web_prefix();
}
if ((string) $daLearnEditor["body"] !== "") {
	$bodyNorm = (string) $daLearnEditor["body"];
	if ($daLearnLernRel !== "" && function_exists("rkrk_dga_rh_normalize_learn_inner_html")) {
		$daLearnDrive = function_exists("rkrk_assess_effective_drive_location")
			? rkrk_assess_effective_drive_location($selectedAssessment, array())
			: (string) ($selectedAssessment["DriveLocation"] ?? "");
		$bodyNorm = rkrk_dga_rh_normalize_learn_inner_html($bodyNorm, $daLearnLernRel, $daLearnDrive);
	} else {
		$bodyNorm = preg_replace('#/data/assess/quiz/images/#i', "/data/assess/images/", $bodyNorm);
		$bodyNorm = preg_replace('#(["\'(])quiz/images/#i', '$1images/', $bodyNorm);
		if ($daLearnBaseHref !== "" && function_exists("rkrk_dga_rh_resolve_relative_urls_in_fragment")) {
			$bodyNorm = rkrk_dga_rh_resolve_relative_urls_in_fragment($bodyNorm, $daLearnBaseHref);
		}
	}
	$daLearnEditor["body"] = $bodyNorm;
}
$daLearnBodyHash = md5((string) $daLearnEditor["body"]);
$daLearnContentCss = array();
if (function_exists("rkrk_assess_resolve_web_path_from_script")) {
	$daLearnContentCss[] = rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme.css");
	if (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
		$daLearnContentCss[] = rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme_rkrs.css");
	}
}
if ($daLearnBaseHref !== "") {
	$daLearnContentCss[] = rtrim(str_replace("\\", "/", (string) $daLearnBaseHref), "/") . "/lernunterlagen.css";
	$daLearnContentCss[] = rtrim(str_replace("\\", "/", (string) $daLearnBaseHref), "/") . "/public/rkrk_schulung_lern.css";
} elseif (function_exists("rkrk_assess_quiz_data_assess_web_prefix")) {
	$pfxAssess = rtrim(str_replace("\\", "/", (string) rkrk_assess_quiz_data_assess_web_prefix()), "/");
	$daLearnContentCss[] = $pfxAssess . "/lernunterlagen.css";
	$daLearnContentCss[] = $pfxAssess . "/public/rkrk_schulung_lern.css";
}
if (function_exists("rkrk_assess_resolve_web_path_from_script")) {
	$daLearnContentCss[] = rkrk_assess_resolve_web_path_from_script("rkrk_schulung_lern.css");
}
$daLearnContentJs = array();
if ($daLearnBaseHref !== "") {
	$daLearnContentJs[] = rtrim(str_replace("\\", "/", (string) $daLearnBaseHref), "/") . "/schulung_common.js";
	$daLearnContentJs[] = rtrim(str_replace("\\", "/", (string) $daLearnBaseHref), "/") . "/lernunterlagen.js";
} elseif (function_exists("rkrk_assess_quiz_data_assess_web_prefix")) {
	$daLearnPfx = rtrim(str_replace("\\", "/", (string) rkrk_assess_quiz_data_assess_web_prefix()), "/");
	$daLearnContentJs[] = $daLearnPfx . "/schulung_common.js";
	$daLearnContentJs[] = $daLearnPfx . "/lernunterlagen.js";
}
$daLearnServerPreviewUrl = "";
if ($selectedKey !== "" && function_exists("rkrk_assess_append_setting_href") && function_exists("rkrk_assess_merge_query_with_setting")) {
	$daLearnServerPreviewUrl = rkrk_assess_append_setting_href(
		"rkrk_digitalassess_learn.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("quiz" => $selectedKey)))
	);
}
$daLearnPrintTitle = trim((string) ($selectedAssessment["Titel"] ?? ""));
if ($daLearnPrintTitle === "") {
	$daLearnPrintTitle = $selectedKey;
}
$daLearnPrintServerUrl = "";
if ($daLearnServerPreviewUrl !== "") {
	$daLearnPrintServerUrl = $daLearnServerPreviewUrl . (strpos($daLearnServerPreviewUrl, "?") !== false ? "&" : "?") . "print=1";
}
$daLearnPrintLogoSrc = "";
if (function_exists("rkrk_assess_report_logo_src_html")) {
	$daLearnPrintLogoSrc = trim((string) rkrk_assess_report_logo_src_html());
}
if ($daLearnPrintLogoSrc === "") {
	$daLearnLogoFile = isset($logopic) ? basename((string) $logopic) : "rkrs_logo.png";
	if ($daLearnLogoFile === "") {
		$daLearnLogoFile = "rkrs_logo.png";
	}
	$daLearnPrintLogoSrc = rtrim(str_replace("\\", "/", (string) $daLearnAssessRoot), "/") . "/images/" . $daLearnLogoFile;
}
$daLearnPrintCopyright = "© " . date("Y") . " by " . (
	function_exists("rkrk_assess_copyright_holder")
		? rkrk_assess_copyright_holder()
		: (isset($copyright) && trim((string) $copyright) !== "" ? trim((string) $copyright) : "rehkitzrettung.ch")
);
$daLearnPrintLogoAlt = (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode())
	? "Rehkitzrettung Schweiz"
	: "Drohnenrehkitzrettung Knonaueramt";
$daLearnPrintDate = date("d.m.Y");
$dle_is_admin_nav = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool) $onAdmin);
$dle_is_instr_nav = isset($onInstr) && (bool) $onInstr;
$adminHref = function_exists("rkrk_assess_append_setting_href")
	? rkrk_assess_append_setting_href("rkrk_digitalassess_admin.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $selectedKey))))
	: "rkrk_digitalassess_admin.php";

headerpage("Schulungstext bearbeiten", false, $folderoffset);
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}
?>
<style>
.dle-wrap { max-width: none; margin: 0 auto; padding: 6px 10px 10px; box-sizing: border-box; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; display: flex; flex-direction: column; min-height: calc(100vh - 8px); height: calc(100vh - 8px); }
.dle-card { background: #fff; border: 1px solid #d8dfd6; border-radius: 10px; padding: 12px; margin-bottom: 8px; flex-shrink: 0; }
.dle-card-compact { padding: 8px 12px; margin-bottom: 6px; }
.dle-title { margin: 0 0 4px; font-size: 1.15rem; font-weight: 700; color: #1f2937; }
.dle-msg { padding: 8px 10px; border-radius: 8px; margin: 8px 0; }
.dle-ok { background: #e8f4e5; border: 1px solid #9ab08f; }
.dle-err { background: #fde9e9; border: 1px solid #e3abab; }
.dle-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 0; }
.dle-toolbar label { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #334155; }
.dle-toolbar input[type="text"], .dle-toolbar select { border: 1px solid #7aad76; border-radius: 8px; padding: 7px; font: inherit; }
.dle-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid #7a8f78; border-radius: 8px; background: #48673a; color: #fff; padding: 7px 12px; text-decoration: none; cursor: pointer; font-weight: 700; font-size: 14px; }
.dle-btn:hover { background: #3d5632; }
.dle-btn-secondary { background: #f4f7f3; color: #1f2937; }
.dle-btn-secondary:hover { background: #e8f0e6; }
.dle-autosave-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer; user-select: none; }
.dle-autosave-status { font-size: 12px; color: #64748b; min-height: 1.2em; }
#dle-learning-form { flex: 1; min-height: 0; display: flex; flex-direction: column; }
.dle-workspace { flex: 1; min-height: 0; display: flex; flex-direction: column; }
.dle-split { flex: 1; min-height: 0; display: flex; align-items: stretch; }
.dle-split.dle-layout-horizontal { flex-direction: row; gap: 0; min-height: min(82vh, 880px); }
.dle-split.dle-layout-vertical { flex-direction: column; gap: 12px; }
.dle-pane { display: flex; flex-direction: column; min-height: 0; min-width: 240px; border: 1px solid #d8dfd6; border-radius: 10px; background: #fafbfa; overflow: hidden; }
.dle-layout-horizontal .dle-pane-editor { flex: 0 0 50%; max-width: 92%; }
.dle-layout-horizontal .dle-pane-preview { flex: 1; min-width: 160px; }
.dle-layout-vertical .dle-pane-editor,
.dle-layout-vertical .dle-pane-preview { flex: 1 1 auto; max-width: none; width: 100%; min-width: 0; }
.dle-layout-vertical .dle-pane-editor { flex: 1 1 58%; min-height: min(58vh, 640px); }
.dle-layout-vertical .dle-pane-preview { flex: 1 1 42%; min-height: min(30vh, 320px); }
.dle-layout-vertical .dle-preview-frame { height: min(38vh, 420px); flex: none; }
.dle-layout-vertical .dle-split-gutter { display: none; }
.dle-split-gutter { flex: 0 0 10px; width: 10px; cursor: col-resize; touch-action: none; background: #e8ece8; border-left: 1px solid #d8dfd6; border-right: 1px solid #d8dfd6; position: relative; z-index: 2; }
.dle-split-gutter::after { content: ""; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 4px; height: 48px; border-radius: 3px; background: #94a394; opacity: 0.65; }
.dle-split-gutter:hover, .dle-split-gutter.is-dragging { background: #d4e6d4; }
.dle-split.is-dragging { user-select: none; cursor: col-resize; }
.dle-split.is-dragging .dle-preview-frame { pointer-events: none; }
.dle-pane-head { padding: 6px 12px; font-size: 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #e5e7eb; background: #f4f7f3; flex-shrink: 0; }
.dle-pane-body { flex: 1; min-height: 0; padding: 4px; display: flex; flex-direction: column; }
.dle-pane-editor { display: flex; flex-direction: column; }
.dle-pane-editor .dle-pane-body { flex: 1; min-height: 0; }
.dle-editor-wrap { flex: 1; min-height: 0; height: 100%; display: flex; flex-direction: column; overflow: hidden; }
#dle-learning-wysiwyg { width: 100%; max-width: 100%; min-height: 0; height: 100%; box-sizing: border-box; resize: none !important; overflow: auto; }
.dle-editor-wrap .tox-tinymce { flex: 1 1 auto !important; width: 100% !important; height: 100% !important; min-height: 0 !important; max-height: none !important; border-radius: 8px !important; border-color: #d8dfd6 !important; display: flex !important; flex-direction: column !important; }
.dle-editor-wrap .tox .tox-editor-container,
.dle-editor-wrap .tox .tox-sidebar-wrap { flex: 1 1 auto !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; }
.dle-editor-wrap .tox .tox-edit-area { flex: 1 1 auto !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }
.dle-editor-wrap .tox .tox-edit-area__iframe { height: 100% !important; min-height: 0 !important; }
.dle-editor-wrap .tox .tox-edit-area > textarea,
.dle-editor-wrap .tox textarea.tox-textarea { flex: 1 1 auto !important; width: 100% !important; height: 100% !important; min-height: 0 !important; resize: none !important; box-sizing: border-box !important; overflow: auto !important; overflow-y: scroll !important; }
.dle-layout-horizontal .dle-preview-frame { flex: 1; width: 100%; min-height: 0; height: 100%; border: 0; background: #fff; border-radius: 8px; }
.dle-preview-frame { width: 100%; border: 0; background: #fff; border-radius: 8px; }
.dle-help { font-size: 12px; color: #64748b; margin: 4px 0 0; line-height: 1.4; }
.dle-preview-tabs { display: flex; gap: 6px; margin-bottom: 4px; flex-shrink: 0; }
.dle-preview-tabs button { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 700; cursor: pointer; }
.dle-preview-tabs button.active { background: #eef6ee; border-color: #7a8f78; color: #1f2937; }
@media (max-width: 1100px) {
	.dle-split.dle-layout-horizontal { flex-direction: column; }
	.dle-layout-horizontal .dle-split-gutter { display: none; }
	.dle-layout-horizontal .dle-pane-editor,
	.dle-layout-horizontal .dle-pane-preview { flex: 1 1 auto !important; max-width: none !important; min-height: 280px; }
	.dle-layout-horizontal .dle-preview-frame { height: min(50vh, 480px); flex: none; }
}
#rkrk-portal-nav-slot { max-width: none; margin: 0 auto 8px; padding: 0 10px; flex-shrink: 0; }
</style>
<?php
if (function_exists("rkrk_assess_portal_nav_echo")) {
	ob_start();
	rkrk_assess_portal_nav_echo(array(
		"btn_class" => "dle-btn dle-btn-secondary",
		"schulung_mode" => false,
		"is_admin" => $dle_is_admin_nav,
		"is_instr" => $dle_is_instr_nav,
		"selected_quiz" => (string) $selectedKey,
	));
	$portalOut = ob_get_clean();
	if ($portalOut !== "") {
		echo '<div id="rkrk-portal-nav-slot">' . $portalOut . "</div>\n";
	}
}
?>
<div class="dle-wrap">
	<div class="dle-card dle-card-compact">
		<h1 class="dle-title">Schulungstext bearbeiten</h1>
		<p class="dle-help" style="margin:0;">Start 50/50 · ↔/↕ in der Toolbar · Kante ziehen · Doppelklick Kante = Editor maximal. Autosave <code>_autosave</code>.</p>
		<?php if ($message !== "") { ?><div class="dle-msg dle-ok"><?php echo dle_h($message); ?></div><?php } ?>
		<?php if ($error !== "") { ?><div class="dle-msg dle-err"><?php echo dle_h($error); ?></div><?php } ?>
		<?php if ($learningLoadWarn !== "" && $selectedKey !== "") { ?><div class="dle-msg dle-err"><?php echo dle_h($learningLoadWarn); ?></div><?php } ?>
	</div>

	<?php if ($selectedKey === "") { ?>
		<div class="dle-card"><p>Kein Assessment gewählt.</p></div>
	<?php } else { ?>
	<form method="get" class="dle-card dle-toolbar" id="dle-quiz-form">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<label>Assessment
			<select name="selected_quiz" onchange="this.form.submit()">
				<?php foreach ($assessments as $aRow) {
					$k = trim((string) ($aRow["Key"] ?? ""));
					if ($k === "") continue;
					$sel = ($k === $selectedKey || strcasecmp($k, $selectedKey) === 0);
				?>
				<option value="<?php echo dle_h($k); ?>"<?php echo $sel ? " selected" : ""; ?>><?php echo dle_h($k . " — " . ($aRow["Titel"] ?? "")); ?></option>
				<?php } ?>
			</select>
		</label>
		<a class="dle-btn dle-btn-secondary" href="<?php echo dle_h($adminHref); ?>">Fragen Manager</a>
	</form>

	<form method="post" id="dle-learning-form">
		<input type="hidden" name="admin_action" value="save_learning_html">
		<input type="hidden" name="selected_quiz" value="<?php echo dle_h($selectedKey); ?>">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<input type="hidden" name="learning_html_doc_prefix_b64" id="dle-learning-prefix-b64" value="<?php echo dle_h((string) $daLearnEditor["prefix_b64"]); ?>">
		<input type="hidden" name="learning_html_doc_suffix_b64" id="dle-learning-suffix-b64" value="<?php echo dle_h((string) $daLearnEditor["suffix_b64"]); ?>">
		<input type="hidden" name="learning_html_fragment" value="<?php echo !empty($daLearnEditor["fragment"]) ? "1" : "0"; ?>">
		<input type="hidden" name="learning_html_baseline_hash" id="dle-learning-baseline-hash" value="<?php echo dle_h($daLearnBodyHash); ?>">

		<div class="dle-card dle-toolbar">
			<label><span>HTML-Datei</span>
				<input type="text" name="learning_file" value="<?php echo dle_h((string) $selectedAssessment["LernDatei"]); ?>">
			</label>
			<button class="dle-btn" type="submit">Speichern</button>
			<label class="dle-autosave-toggle" title="Automatisch speichern + Archiv">
				<input type="checkbox" id="dle-autosave-switch" checked>
				<span>Autospeichern</span>
			</label>
			<span class="dle-autosave-status" id="dle-autosave-status" aria-live="polite"></span>
			<button type="button" class="dle-btn dle-btn-secondary" id="dle-layout-toggle" aria-pressed="true" title="Editor oben, Vorschau darunter">↕ Untereinander</button>
			<button type="button" class="dle-btn dle-btn-secondary" id="dle-print-btn" title="Aktuellen Lerntext im Druckformat öffnen (Strg+P im neuen Fenster)">Druckansicht</button>
			<?php if ($daLearnPrintServerUrl !== "") { ?>
			<a class="dle-btn dle-btn-secondary" id="dle-print-saved-link" href="<?php echo dle_h($daLearnPrintServerUrl); ?>" target="_blank" rel="noopener" title="Gespeicherte Version drucken">Gespeichert drucken</a>
			<?php } ?>
		</div>

		<div class="dle-workspace">
		<div class="dle-split dle-layout-horizontal" id="dle-split">
			<div class="dle-pane dle-pane-editor" id="dle-pane-editor">
				<div class="dle-pane-head">Editor (WYSIWYG / Quellcode)</div>
				<div class="dle-pane-body">
					<div class="dle-editor-wrap">
						<textarea id="dle-learning-wysiwyg" name="learning_html"><?php echo dle_h((string) $daLearnEditor["body"]); ?></textarea>
					</div>
				</div>
			</div>
			<div class="dle-split-gutter" id="dle-split-gutter" role="separator" aria-orientation="vertical" aria-label="Editor und Vorschau in der Breite anpassen" title="Ziehen zum Anpassen"></div>
			<div class="dle-pane dle-pane-preview" id="dle-pane-preview">
				<div class="dle-pane-head">Vorschau</div>
				<div class="dle-pane-body">
					<div class="dle-preview-tabs">
						<button type="button" class="active" id="dle-preview-tab-live" data-mode="live">Live-Entwurf</button>
						<?php if ($daLearnServerPreviewUrl !== "") { ?>
						<button type="button" id="dle-preview-tab-server" data-mode="server">Gespeichert (Server)</button>
						<?php } ?>
					</div>
					<iframe class="dle-preview-frame" id="dle-preview-frame" title="Schulung Vorschau"></iframe>
				</div>
			</div>
		</div>
		</div>
	</form>
	<script id="dle-tinymce-loader" src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" crossorigin="anonymous"></script>
	<?php } ?>
</div>
<script>
(function () {
	const form = document.getElementById('dle-learning-form');
	if (!form) return;

	const previewFrame = document.getElementById('dle-preview-frame');
	const autosaveStatus = document.getElementById('dle-autosave-status');
	const prefixB64 = document.getElementById('dle-learning-prefix-b64');
	const suffixB64 = document.getElementById('dle-learning-suffix-b64');
	const fragmentInput = form.querySelector('input[name="learning_html_fragment"]');
	const contentCss = <?php echo json_encode(array_values($daLearnContentCss), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const contentJs = <?php echo json_encode(array_values($daLearnContentJs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const serverPreviewUrl = <?php echo json_encode((string) $daLearnServerPreviewUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const learnPrintTitle = <?php echo json_encode((string) $daLearnPrintTitle, JSON_UNESCAPED_UNICODE); ?>;
	const learnPrintServerUrl = <?php echo json_encode((string) $daLearnPrintServerUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const learnPrintLogoSrc = <?php echo json_encode((string) $daLearnPrintLogoSrc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const learnPrintCopyright = <?php echo json_encode((string) $daLearnPrintCopyright, JSON_UNESCAPED_UNICODE); ?>;
	const learnPrintLogoAlt = <?php echo json_encode((string) $daLearnPrintLogoAlt, JSON_UNESCAPED_UNICODE); ?>;
	const learnPrintDate = <?php echo json_encode((string) $daLearnPrintDate, JSON_UNESCAPED_UNICODE); ?>;
	const learnBasePath = <?php echo json_encode((string) $daLearnBaseHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const learnAssessRoot = <?php echo json_encode((string) $daLearnAssessRoot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	let learnBaseAbs = '';
	try {
		if (learnBasePath) {
			learnBaseAbs = new URL(learnBasePath, window.location.href).href;
			if (!/\/$/.test(learnBaseAbs)) learnBaseAbs += '/';
		}
	} catch (e) {
		learnBaseAbs = '';
	}
	const learnBodyStyle = 'html{height:100%;}body{margin:0;padding:12px 14px;box-sizing:border-box;max-width:100%;min-height:100%;overflow-y:auto;background:#f9f9f9;font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#333;}'
		+ 'body>section,section.lern-sektion>section{display:block;margin-bottom:1.75rem;background:#fff;padding:1.25rem 1.5rem;border-radius:10px;border:1px solid #e0e0e0;box-shadow:0 2px 4px rgba(0,0,0,.08);box-sizing:border-box;}'
		+ 'section.lern-sektion{margin-bottom:2rem;padding:0;background:transparent;border:none;border-radius:0;box-shadow:none;}'
		+ 'h1,h2,h3{color:#003366;}.keywords{font-style:italic;color:#666;margin-bottom:1rem;}'
		+ '.highlight{background:#e6f7ff;border-left:4px solid #1890ff;padding:.5rem 1rem;margin:1rem 0;}'
		+ '.pruefungstipp{background:#fffbe6;border-left:4px solid #faad14;padding:.5rem 1rem;margin:1rem 0;}'
		+ '.hinweis{background:#f6ffed;border-left:4px solid #52c41a;padding:.5rem 1rem;margin:1rem 0;}'
		+ 'img{border-radius:5px 15% 10px;max-width:100%;box-sizing:border-box;}'
		+ '.lernimagelarge{height:500px;max-width:100%;}.lernimage{height:300px;max-width:100%;}'
		+ '.lernimagesmall{height:200px;max-width:100%;}.lernimageverysmall{height:100px;max-width:100%;}'
		+ '.lernimagefixwidth{width:600px;max-width:100%;height:auto;}.lernimagefixheight{height:200px;max-width:100%;}'
		+ '.lernimagefixlargeheight{height:500px;max-width:100%;}.lernimagesmallfixwidth{width:450px;max-width:100%;height:auto;}'
		+ '.lerncaption{font-style:italic;font-size:.9em;color:#555;margin-top:.35rem;}'
		+ 'figure{margin:1rem 0;padding:0;max-width:100%;background:transparent;border:none;box-shadow:none;}'
		+ 'figure img,figure picture img{display:inline-block;vertical-align:top;margin:0 .75rem .75rem 0;border:0;box-shadow:none;background:transparent;}'
		+ 'figure img:only-of-type,figure picture img:only-of-type{display:block;margin:0 0 .5rem 0;}'
		+ 'table{width:100%;border-collapse:separate;border-spacing:0;margin:1rem 0 1.5rem;font-size:.95rem;line-height:1.45;border:1px solid #d8dfd6;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.06);background:#fff;}'
		+ 'table thead th{background:linear-gradient(90deg,#003366,#1a5a8a);color:#fff;font-weight:600;text-align:left;padding:.65rem .9rem;border:none;}'
		+ 'table tbody td,table tbody th{padding:.6rem .9rem;border-bottom:1px solid #ecefec;border-left:none;border-right:none;vertical-align:top;}'
		+ 'table tbody tr:last-child td,table tbody tr:last-child th{border-bottom:none;}'
		+ 'table tbody tr:nth-child(even){background:#f8faf8;}table tbody tr:hover{background:#eef6ff;}';
	const learnPrintExtraStyle = '.dle-print-toolbar{margin:0 0 14px;padding:10px 12px;background:#f4f7f3;border:1px solid #d8dfd6;border-radius:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;}'
		+ '.dle-print-toolbar button,.dle-print-toolbar a{border:1px solid #7a8f78;border-radius:8px;background:#48673a;color:#fff;padding:7px 14px;font-weight:700;cursor:pointer;font-size:14px;font-family:inherit;text-decoration:none;}'
		+ '.dle-print-toolbar .dle-print-secondary{background:#f4f7f3;color:#1f2937;}'
		+ '.dle-print-header{margin:0 0 1.25rem;padding-bottom:.6rem;border-bottom:2px solid #003366;}'
		+ '.dle-print-header h1{margin:0 0 .25rem;font-size:1.4rem;color:#003366;line-height:1.25;}'
		+ '.dle-print-meta{margin:0;font-size:.9rem;color:#64748b;}'
		+ '.schulung-print-logo{display:block;position:fixed;top:4mm;right:10mm;z-index:1000;height:28px;max-height:28px;width:auto;max-width:90px;border:0;border-radius:0;box-shadow:none;}'
		+ '.schulung-print-footer{display:flex;justify-content:space-between;align-items:baseline;position:fixed;left:12mm;right:12mm;bottom:5mm;z-index:1000;margin:0;padding:0;font-size:7.5pt;font-style:italic;color:#64748b;background:#fff;line-height:1.3;}'
		+ '@media print{'
		+ '.dle-print-toolbar{display:none!important;}'
		+ '@page{size:A4 portrait;margin:0;}'
		+ 'html,body{margin:0;padding:0;background:#fff!important;color:#000;}'
		+ 'body{padding:8mm 12mm 14mm;max-width:100%;}'
		+ 'section,figure,.highlight,.pruefungstipp,.hinweis{page-break-inside:avoid;}'
		+ 'h1,h2,h3{page-break-after:avoid;}'
		+ 'img:not(.schulung-print-logo),.lernimagelarge,.lernimage,.lernimagesmall,.lernimageverysmall,.lernimagefixwidth,.lernimagefixheight,.lernimagefixlargeheight,.lernimagesmallfixwidth{max-width:100%!important;height:auto!important;}'
		+ 'table{page-break-inside:auto;}'
		+ 'thead{display:table-header-group;}'
		+ 'tr{page-break-inside:avoid;page-break-after:auto;}'
		+ '.schulung-print-logo{display:block!important;position:fixed;top:4mm;right:10mm;z-index:1000;height:28px!important;max-height:28px!important;width:auto!important;max-width:90px!important;border:0;border-radius:0;box-shadow:none;}'
		+ '.schulung-print-footer{display:flex!important;justify-content:space-between;align-items:baseline;position:fixed;left:12mm;right:12mm;bottom:5mm;z-index:1000;margin:0;padding:0;font-size:7.5pt;font-style:italic;color:#64748b;background:#fff;line-height:1.3;}'
		+ '.schulung-print-date{text-align:left;}'
		+ '.schulung-print-copyright{display:inline!important;position:static;margin:0;padding:0;text-align:right;font-size:inherit;font-style:inherit;color:inherit;background:transparent;}'
		+ '.rkrs-copyright{display:none!important;}'
		+ '}';

	function escapeHtmlText(s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function decodeAttrUrl(u) {
		let s = String(u || '');
		const ta = document.createElement('textarea');
		for (let i = 0; i < 12; i++) {
			ta.innerHTML = s;
			const d = ta.value;
			if (d === s) break;
			s = d;
		}
		return s;
	}

	function stripScripts(html) {
		return String(html || '').replace(/<script\b[\s\S]*?<\/script>/gi, '');
	}

	function buildPrintDocument(bodyHtml) {
		let doc = stripScripts(buildPreviewDocument(bodyHtml));
		const title = escapeHtmlText(learnPrintTitle);
		if (/<style>/i.test(doc)) {
			doc = doc.replace(/<style>/i, '<style>' + learnPrintExtraStyle);
		} else if (/<\/head>/i.test(doc)) {
			doc = doc.replace(/<\/head>/i, '<style>' + learnPrintExtraStyle + '</style></head>');
		} else {
			doc = '<style>' + learnPrintExtraStyle + '</style>' + doc;
		}
		if (/<title>/i.test(doc)) {
			doc = doc.replace(/<title>[^<]*<\/title>/i, '<title>' + title + ' — Druck</title>');
		}
		const toolbar = '<div class="dle-print-toolbar">'
			+ '<button type="button" onclick="window.print()">Drucken</button>'
			+ '<button type="button" class="dle-print-secondary" onclick="window.close()">Schliessen</button>'
			+ '</div>';
		const header = '<header class="dle-print-header"><h1>' + title + '</h1>'
			+ '<p class="dle-print-meta">Schulungstext · Entwurf aus Editor</p></header>';
		const printChrome = (learnPrintLogoSrc
			? '<img id="schulung-print-logo" class="schulung-print-logo" src="' + escapeHtmlText(learnPrintLogoSrc) + '" alt="' + escapeHtmlText(learnPrintLogoAlt) + '">'
			: '')
			+ '<div id="schulung-print-footer" class="schulung-print-footer">'
			+ '<span class="schulung-print-date">' + escapeHtmlText(learnPrintDate) + '</span>'
			+ (learnPrintCopyright
				? '<span id="schulung-print-copyright" class="schulung-print-copyright">' + escapeHtmlText(learnPrintCopyright) + '</span>'
				: '')
			+ '</div>';
		if (/<body[^>]*>/i.test(doc)) {
			doc = doc.replace(/<body([^>]*)>/i, function (full, attrs) {
				let a = attrs || '';
				if (/\bclass\s*=/i.test(a)) {
					a = a.replace(/\bclass\s*=\s*(["'])([^"']*)\1/i, function (cm, q, cls) {
						return 'class=' + q + (cls ? cls + ' ' : '') + 'dle-print-doc' + q;
					});
				} else {
					a += ' class="dle-print-doc"';
				}
				return '<body' + a + '>' + toolbar + header + printChrome;
			});
		} else {
			doc = '<body class="dle-print-doc">' + toolbar + header + printChrome + doc + '</body>';
		}
		return doc;
	}

	function openPrintView() {
		const bodyHtml = getEditorBody();
		const hasBody = !!(bodyHtml && String(bodyHtml).replace(/\s/g, '').length);
		if (!hasBody && learnPrintServerUrl) {
			const wSrv = window.open(learnPrintServerUrl, '_blank');
			if (!wSrv) {
				window.alert('Pop-up blockiert — bitte Pop-ups für diese Seite erlauben.');
			}
			return;
		}
		if (!hasBody) {
			window.alert('Kein Lerntext zum Drucken.');
			return;
		}
		const html = buildPrintDocument(bodyHtml);
		try {
			const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
			const url = URL.createObjectURL(blob);
			const wBlob = window.open(url, '_blank');
			if (wBlob) {
				setTimeout(function () { try { URL.revokeObjectURL(url); } catch (e) {} }, 120000);
				try { wBlob.focus(); } catch (e2) {}
				return;
			}
			try { URL.revokeObjectURL(url); } catch (e3) {}
		} catch (e4) {}
		try {
			const wDoc = window.open('about:blank', '_blank');
			if (wDoc && wDoc.document) {
				wDoc.document.open();
				wDoc.document.write(html);
				wDoc.document.close();
				try { wDoc.focus(); } catch (e5) {}
				return;
			}
		} catch (e6) {}
		if (learnPrintServerUrl) {
			const wFb = window.open(learnPrintServerUrl, '_blank');
			if (wFb) return;
		}
		window.alert('Pop-up blockiert — bitte Pop-ups für diese Seite erlauben.');
	}

	const splitEl = document.getElementById('dle-split');
	const layoutToggle = document.getElementById('dle-layout-toggle');
	const quizInput = form.querySelector('input[name="selected_quiz"]');
	const quizKey = quizInput && quizInput.value ? quizInput.value : '_';
	const LAYOUT_KEY = 'da_learning_layout_' + quizKey;

	function isHorizontalLayout() {
		return !!(splitEl && splitEl.classList.contains('dle-layout-horizontal'));
	}

	function applyLearningLayout(mode) {
		if (!splitEl) return;
		const horizontal = mode === 'horizontal';
		splitEl.classList.toggle('dle-layout-horizontal', horizontal);
		splitEl.classList.toggle('dle-layout-vertical', !horizontal);
		if (layoutToggle) {
			layoutToggle.setAttribute('aria-pressed', horizontal ? 'true' : 'false');
			layoutToggle.textContent = horizontal ? '↕ Untereinander' : '↔ Nebeneinander';
			layoutToggle.title = horizontal
				? 'Editor oben, Vorschau darunter'
				: 'Editor links, Vorschau rechts';
		}
		try { localStorage.setItem(LAYOUT_KEY, mode); } catch (e) {}
		if (typeof window.__dleApplySplitRatio === 'function') {
			window.__dleApplySplitRatio();
		}
		syncEditorHeight();
	}

	try {
		const savedLayout = localStorage.getItem(LAYOUT_KEY);
		if (savedLayout === 'horizontal' || savedLayout === 'vertical') {
			applyLearningLayout(savedLayout);
		}
	} catch (e) {}

	if (layoutToggle && splitEl) {
		layoutToggle.addEventListener('click', function () {
			applyLearningLayout(isHorizontalLayout() ? 'vertical' : 'horizontal');
		});
	}

	const printBtn = document.getElementById('dle-print-btn');
	if (printBtn) {
		printBtn.addEventListener('click', openPrintView);
	}

	let previewMode = 'live';
	let previewTimer = null;

	function b64ToUtf8(b64) {
		if (!b64) return '';
		try {
			const bin = atob(b64);
			const bytes = new Uint8Array(bin.length);
			for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
			if (typeof TextDecoder !== 'undefined') {
				return new TextDecoder('utf-8').decode(bytes);
			}
			return bin;
		} catch (e) {
			return '';
		}
	}

	function getEditorBody() {
		const ed = window.tinymce && tinymce.get('dle-learning-wysiwyg');
		if (ed) return ed.getContent();
		const ta = document.getElementById('dle-learning-wysiwyg');
		return ta ? ta.value : '';
	}

	function rebuildFullHtml(body) {
		const isFragment = fragmentInput && fragmentInput.value === '1';
		if (isFragment) return body;
		return b64ToUtf8(prefixB64 ? prefixB64.value : '') + body + b64ToUtf8(suffixB64 ? suffixB64.value : '');
	}

	function encodeUrlPathSegment(seg) {
		if (!seg) return '';
		let s = seg;
		for (let i = 0; i < 6; i++) {
			try {
				const d = decodeURIComponent(s);
				if (d === s) break;
				s = d;
			} catch (e) { break; }
		}
		try { if (typeof s.normalize === 'function') s = s.normalize('NFC'); } catch (e2) {}
		return encodeURIComponent(s);
	}
	function encodeUrlForRequest(u) {
		const raw = String(u || '');
		if (!raw || /^mailto:|^tel:|^data:/i.test(raw) || raw.charAt(0) === '#') return raw;
		if (/^https?:\/\//i.test(raw)) {
			try {
				const parsed = new URL(raw);
				const path = parsed.pathname.split('/').map(encodeUrlPathSegment).filter(Boolean).join('/');
				return parsed.origin + '/' + path + parsed.search + parsed.hash;
			} catch (e) { return raw; }
		}
		let path = raw, query = '', hash = '';
		const qi = path.indexOf('?');
		if (qi >= 0) { query = path.substring(qi); path = path.substring(0, qi); }
		const hi = path.indexOf('#');
		if (hi >= 0) { hash = path.substring(hi); path = path.substring(0, hi); }
		let leading = '';
		if (path.charAt(0) === '/') { leading = '/'; path = path.replace(/^\/+/, ''); }
		return leading + path.split('/').map(encodeUrlPathSegment).filter(Boolean).join('/') + query + hash;
	}
	function rewriteLegacyAssessUrl(url) {
		let u = String(url || '').trim().replace(/\\/g, '/');
		if (!u || u.charAt(0) === '#' || /^(https?:|mailto:|tel:|data:)/i.test(u)) return u;
		if (u.charAt(0) !== '/') return u;
		const target = String(learnAssessRoot || '').replace(/\\/g, '/').replace(/\/?$/, '/');
		if (!target || target === '/') return u;
		if (/^\/data\/assess\//i.test(u)) {
			return target + u.replace(/^\/data\/assess\//i, '');
		}
		const fromPrefixes = [
			'/rkropen/rkrk/data/assess/',
			'/rkropen/rkrs/data/assess/',
			'/rkrsplusassessment/data/assess/',
			'/rkrk/data/assess/',
			'/rkrs/data/assess/'
		];
		const targetNorm = target.replace(/\/$/, '').toLowerCase();
		for (let i = 0; i < fromPrefixes.length; i++) {
			const from = fromPrefixes[i];
			if (u.toLowerCase().indexOf(from.toLowerCase()) !== 0) continue;
			if (from.replace(/\/$/, '').toLowerCase() === targetNorm) return u;
			return target + u.substring(from.length);
		}
		return u;
	}
	function resolveLearnUrlOne(raw) {
		let u = String(raw || '').trim().replace(/\\/g, '/');
		if (!u || u.charAt(0) === '#' || /^(https?:|mailto:|tel:|data:)/i.test(u)) return u;
		if (u.charAt(0) === '/') return rewriteLegacyAssessUrl(u);
		if (learnBaseAbs) {
			try { return new URL(u, learnBaseAbs).href; } catch (e) {}
		}
		return u;
	}
	function encodeLearnUrl(raw) {
		return encodeUrlForRequest(resolveLearnUrlOne(decodeAttrUrl(raw)));
	}
	function encodeUrlsInHtmlFragment(html) {
		let h = String(html || '');
		h = h.replace(/\bsrc\s*=\s*(["'])([^"']*)\1/gi, function (m, q, src) {
			return 'src=' + q + encodeLearnUrl(src) + q;
		});
		h = h.replace(/\bdata-src\s*=\s*(["'])([^"']*)\1/gi, function (m, q, src) {
			return 'data-src=' + q + encodeLearnUrl(src) + q;
		});
		h = h.replace(/\bposter\s*=\s*(["'])([^"']*)\1/gi, function (m, q, src) {
			return 'poster=' + q + encodeLearnUrl(src) + q;
		});
		h = h.replace(/\bsrcset\s*=\s*(["'])([^"']*)\1/gi, function (m, q, srcset) {
			const parts = String(srcset || '').split(/\s*,\s*/);
			const resolved = [];
			for (let i = 0; i < parts.length; i++) {
				const part = parts[i].trim();
				if (!part) continue;
				const sp = part.indexOf(' ');
				if (sp >= 0) {
					resolved.push(encodeLearnUrl(part.substring(0, sp)) + part.substring(sp));
				} else {
					resolved.push(encodeLearnUrl(part));
				}
			}
			return 'srcset=' + q + resolved.join(', ') + q;
		});
		h = h.replace(/\bhref\s*=\s*(["'])([^"']*)\1/gi, function (m, q, href) {
			if (String(href || '').charAt(0) === '#') return m;
			return 'href=' + q + encodeLearnUrl(href) + q;
		});
		h = h.replace(/\bstyle\s*=\s*(["'])([^"']*)\1/gi, function (m, q, style) {
			const fixed = String(style || '').replace(/url\(\s*(["']?)([^"')\s]+)\1\s*\)/gi, function (um, qq, u) {
				return 'url(' + qq + encodeLearnUrl(u) + qq + ')';
			});
			return 'style=' + q + fixed + q;
		});
		return h;
	}
	function normalizeLearnHtml(html) {
		let h = html || '';
		h = h.replace(/\/data\/assess\/quiz\/images\//gi, '/data/assess/images/');
		h = h.replace(/(["'(])quiz\/images\//gi, '$1images/');
		return encodeUrlsInHtmlFragment(h);
	}

	function baseTagHtml() {
		if (!learnBaseAbs) return '';
		const esc = learnBaseAbs.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
		return '<base href="' + esc + '">';
	}

	function injectBaseIntoDocument(html) {
		let doc = normalizeLearnHtml(html);
		const base = baseTagHtml();
		if (!base) return doc;
		if (/<head[^>]*>/i.test(doc)) {
			return doc.replace(/<head([^>]*)>/i, '<head$1>' + base);
		}
		if (/<html[^>]*>/i.test(doc)) {
			return doc.replace(/<html([^>]*)>/i, '<html$1><head>' + base + '<meta charset="UTF-8"></head>');
		}
		return doc;
	}

	function buildPreviewDocument(bodyHtml) {
		const links = (contentCss || []).map(function (href) {
			return '<link rel="stylesheet" href="' + href.replace(/"/g, '&quot;') + '">';
		}).join('');
		const scripts = (contentJs || []).map(function (src) {
			return '<script src="' + src.replace(/"/g, '&quot;') + '" defer><\/script>';
		}).join('');
		const inner = normalizeLearnHtml(bodyHtml);
		const base = baseTagHtml();
		const isFragment = fragmentInput && fragmentInput.value === '1';
		if (isFragment) {
			return '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
				+ base + links + '<style>' + learnBodyStyle + '</style></head><body>' + inner + scripts + '</body></html>';
		}
		const full = rebuildFullHtml(inner);
		if (/^\s*<!DOCTYPE/i.test(full) || /^\s*<html/i.test(full)) {
			return injectBaseIntoDocument(full);
		}
		return '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">' + base + links + '<style>' + learnBodyStyle + '</style></head><body>' + inner + scripts + '</body></html>';
	}

	function updateLivePreview() {
		if (!previewFrame || previewMode !== 'live') return;
		previewFrame.removeAttribute('src');
		previewFrame.srcdoc = buildPreviewDocument(getEditorBody());
	}

	function schedulePreview() {
		if (previewTimer) clearTimeout(previewTimer);
		previewTimer = setTimeout(updateLivePreview, 350);
	}

	function setPreviewMode(mode) {
		previewMode = mode;
		document.querySelectorAll('.dle-preview-tabs button').forEach(function (btn) {
			btn.classList.toggle('active', btn.getAttribute('data-mode') === mode);
		});
		if (mode === 'server' && serverPreviewUrl) {
			previewFrame.removeAttribute('srcdoc');
			previewFrame.src = serverPreviewUrl;
		} else {
			updateLivePreview();
		}
	}

	document.querySelectorAll('.dle-preview-tabs button').forEach(function (btn) {
		btn.addEventListener('click', function () {
			setPreviewMode(btn.getAttribute('data-mode') || 'live');
		});
	});

	function syncEditorHeight() {
		const wrap = document.querySelector('.dle-editor-wrap');
		if (!wrap) return;
		const ed = window.tinymce && tinymce.get('dle-learning-wysiwyg');
		if (!ed || !ed.getContainer) return;
		const w = Math.max(0, wrap.clientWidth);
		const h = Math.max(200, wrap.clientHeight);
		const box = ed.getContainer();
		if (box) {
			box.style.width = w > 0 ? (w + 'px') : '100%';
			box.style.height = h + 'px';
			box.style.minHeight = h + 'px';
			box.style.maxHeight = 'none';
			box.querySelectorAll('.tox-edit-area > textarea, textarea.tox-textarea').forEach(function (ta) {
				ta.style.resize = 'none';
				ta.style.height = '100%';
				ta.style.minHeight = '0';
				ta.style.overflowY = 'auto';
				ta.style.overflowX = 'auto';
			});
		}
		try { ed.dispatch('ResizeEditor'); } catch (e) {}
	}

	function resizeEditor() {
		syncEditorHeight();
	}

	function initSplitResizer() {
		const splitEl = document.getElementById('dle-split');
		const paneEditor = document.getElementById('dle-pane-editor');
		const gutter = document.getElementById('dle-split-gutter');
		if (!splitEl || !paneEditor || !gutter) return;

		const SPLIT_KEY = 'dle_learning_split_ratio';
		const SPLIT_VER_KEY = 'dle_learning_split_ver';
		const SPLIT_MIN = 0.22;
		const SPLIT_MAX = 0.92;
		const SPLIT_DEFAULT = 0.5;
		let splitRatio = SPLIT_DEFAULT;
		try {
			const ver = localStorage.getItem(SPLIT_VER_KEY);
			if (ver !== '3') {
				localStorage.setItem(SPLIT_VER_KEY, '3');
				localStorage.setItem(SPLIT_KEY, String(SPLIT_DEFAULT));
			} else {
				const stored = parseFloat(localStorage.getItem(SPLIT_KEY));
				if (!isNaN(stored) && stored >= SPLIT_MIN && stored <= SPLIT_MAX) splitRatio = stored;
			}
		} catch (e) {}

		function applySplitRatio() {
			if (!isHorizontalLayout() || window.innerWidth <= 1100) {
				paneEditor.style.flex = '';
				paneEditor.style.maxWidth = '';
				syncEditorHeight();
				return;
			}
			paneEditor.style.flex = '0 0 ' + (splitRatio * 100) + '%';
			paneEditor.style.maxWidth = (SPLIT_MAX * 100) + '%';
			syncEditorHeight();
		}

		window.__dleApplySplitRatio = applySplitRatio;
		applySplitRatio();

		let dragging = false;
		function onMove(clientX) {
			const rect = splitEl.getBoundingClientRect();
			const x = clientX - rect.left;
			const ratio = Math.min(SPLIT_MAX, Math.max(SPLIT_MIN, x / rect.width));
			splitRatio = ratio;
			applySplitRatio();
		}

		function stopDrag() {
			if (!dragging) return;
			dragging = false;
			gutter.classList.remove('is-dragging');
			splitEl.classList.remove('is-dragging');
			document.removeEventListener('mousemove', onMouseMove);
			document.removeEventListener('mouseup', stopDrag);
			document.removeEventListener('touchmove', onTouchMove);
			document.removeEventListener('touchend', stopDrag);
			try { localStorage.setItem(SPLIT_KEY, String(splitRatio)); } catch (e) {}
			syncEditorHeight();
		}

		function onMouseMove(e) { if (dragging) onMove(e.clientX); }
		function onTouchMove(e) {
			if (dragging && e.touches && e.touches[0]) {
				e.preventDefault();
				onMove(e.touches[0].clientX);
			}
		}

		function startDrag(e) {
			if (!isHorizontalLayout() || window.innerWidth <= 1100) return;
			dragging = true;
			gutter.classList.add('is-dragging');
			splitEl.classList.add('is-dragging');
			if (e.type === 'mousedown') onMove(e.clientX);
			document.addEventListener('mousemove', onMouseMove);
			document.addEventListener('mouseup', stopDrag);
			document.addEventListener('touchmove', onTouchMove, { passive: false });
			document.addEventListener('touchend', stopDrag);
		}

		gutter.addEventListener('mousedown', startDrag);
		gutter.addEventListener('touchstart', startDrag, { passive: true });
		gutter.addEventListener('dblclick', function () {
			if (!isHorizontalLayout() || window.innerWidth <= 1100) return;
			splitRatio = SPLIT_MAX;
			applySplitRatio();
			try { localStorage.setItem(SPLIT_KEY, String(splitRatio)); } catch (e) {}
		});
		window.addEventListener('resize', function () {
			applySplitRatio();
			syncEditorHeight();
		});
	}

	function startEditor() {
		if (!window.tinymce) {
			updateLivePreview();
			if (autosaveStatus) autosaveStatus.textContent = 'Editor (TinyMCE) nicht geladen — Textarea + Vorschau aktiv.';
			return;
		}
		if (window.__dleEditorInited) {
			resizeEditor();
			return;
		}
		window.__dleEditorInited = true;
		const tinymceBase = learnBaseAbs || (learnBasePath ? (window.location.origin + (learnBasePath.charAt(0) === '/' ? '' : '/') + learnBasePath) : '');
		tinymce.init({
			selector: '#dle-learning-wysiwyg',
			width: '100%',
			height: '100%',
			min_height: 200,
			resize: false,
			menubar: false,
			language: 'de',
			language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@24.12.9/langs7/de.js',
			license_key: 'gpl',
			promotion: false,
			branding: false,
			convert_urls: false,
			relative_urls: true,
			remove_script_host: false,
			document_base_url: tinymceBase,
			plugins: 'lists link image table code anchor charmap searchreplace visualblocks',
			toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | removeformat | code',
			content_css: contentCss.length ? contentCss : false,
			content_style: learnBodyStyle,
			valid_elements: '*[*]',
			extended_valid_elements: '*[*]',
			setup: function (editor) {
				editor.on('change input undo redo SetContent', function () {
					editor.save();
					schedulePreview();
				});
				editor.on('init', function () {
					syncEditorHeight();
					schedulePreview();
				});
				editor.on('SwitchMode', function () {
					requestAnimationFrame(syncEditorHeight);
				});
			}
		});
	}

	initSplitResizer();
	updateLivePreview();

	const tinymceLoader = document.getElementById('dle-tinymce-loader');
	if (tinymceLoader && !window.tinymce) {
		tinymceLoader.addEventListener('load', startEditor);
		tinymceLoader.addEventListener('error', function () {
			updateLivePreview();
			if (autosaveStatus) autosaveStatus.textContent = 'TinyMCE konnte nicht geladen werden (CDN?).';
		});
	} else {
		startEditor();
	}
	window.addEventListener('resize', function () {
		syncEditorHeight();
	});
	if (typeof ResizeObserver !== 'undefined') {
		const wrapObs = document.querySelector('.dle-editor-wrap');
		if (wrapObs) {
			new ResizeObserver(function () { syncEditorHeight(); }).observe(wrapObs);
		}
	}

	form.addEventListener('submit', function () {
		const ed = tinymce.get('dle-learning-wysiwyg');
		if (ed) ed.save();
	});

	/* Autosave (wie Admin) */
	const autosaveSwitch = document.getElementById('dle-autosave-switch');
	const baselineInput = document.getElementById('dle-learning-baseline-hash');
	let lastActivityAt = 0;
	let autosaveInFlight = false;
	let autosaveTimer = null;

	function learnAutosaveKey() {
		const q = form.querySelector('input[name="selected_quiz"]');
		return 'dle_learning_autosave_' + (q && q.value ? q.value : '_');
	}

	function setBaseline(h) { if (baselineInput && h) baselineInput.value = h; }
	function setStatus(t) { if (autosaveStatus) autosaveStatus.textContent = t || ''; }

	function saveAjax() {
		if (autosaveInFlight || !autosaveSwitch || !autosaveSwitch.checked) return Promise.resolve(null);
		const ed = tinymce.get('dle-learning-wysiwyg');
		if (ed) ed.save();
		autosaveInFlight = true;
		setStatus('Speichert…');
		const fd = new FormData(form);
		fd.set('learning_autosave_ajax', '1');
		return fetch(form.action || window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data && data.ok) {
					if (data.content_hash) setBaseline(data.content_hash);
					if (!data.skipped) {
						const t = data.saved_at ? new Date(data.saved_at) : new Date();
						setStatus('Autosave ' + t.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
						if (previewMode === 'server' && previewFrame && serverPreviewUrl) {
							previewFrame.src = serverPreviewUrl + (serverPreviewUrl.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now();
						}
					} else setStatus('');
				} else {
					setStatus(data && data.error ? data.error : 'Autosave fehlgeschlagen');
				}
				return data;
			})
			.catch(function () { setStatus('Autosave fehlgeschlagen'); return null; })
			.finally(function () { autosaveInFlight = false; });
	}

	function noteActivity() { lastActivityAt = Date.now(); }

	autosaveSwitch.addEventListener('change', function () {
		try { localStorage.setItem(learnAutosaveKey(), autosaveSwitch.checked ? '1' : '0'); } catch (e) {}
		if (!autosaveSwitch.checked) setStatus('');
		if (autosaveTimer) clearInterval(autosaveTimer);
		autosaveTimer = autosaveSwitch.checked ? setInterval(function () {
			if (Date.now() - lastActivityAt > 120000) return;
			saveAjax();
		}, 60000) : null;
	});

	try {
		if (localStorage.getItem(learnAutosaveKey()) === '0') autosaveSwitch.checked = false;
	} catch (e) {}
	autosaveSwitch.dispatchEvent(new Event('change'));

	form.addEventListener('input', noteActivity, true);
	form.addEventListener('change', noteActivity, true);
})();
</script>
<?php
if (function_exists("footerpage")) {
	footerpage(false, $folderoffset);
}
exit;
