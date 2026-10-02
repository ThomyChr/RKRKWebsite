<?php
/**
 * INSTR/ADMIN: laufende und abgeschlossene Prüfungen, Assessments und Schulungen
 * mit Recovery-Link (Fenster geschlossen / unterbrochen).
 */
if (isset($_GET["rkrkdiag"]) && (string) $_GET["rkrkdiag"] === "path") {
	header("Content-Type: text/plain; charset=utf-8");
	$f = __DIR__ . "/rkrk_assess_common.php";
	$src = is_file($f) ? (string) file_get_contents($f) : "";
	echo "dir=" . __DIR__ . "\n";
	echo "mtime=" . (is_file($f) ? date("c", (int) filemtime($f)) : "missing") . "\n";
	echo "hasUnset=" . (strpos($src, "unset(\$_SESSION)") !== false ? "yes" : "no") . "\n";
	echo "hasMoodleDiag=" . (strpos($src, "Moodle \" . (\$moodleUp") !== false ? "yes" : "no") . "\n";
	exit;
}
if (function_exists("ob_get_level") && ob_get_level() === 0) {
	ob_start();
}
error_reporting(E_ALL);
ini_set("display_errors", "1");
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_assess_exam_session.inc.php";
include_once __DIR__ . "/rkrk_checklist_toolbar_inc.php";
include_once __DIR__ . "/rkrk_assess_checklist_progress.inc.php";
include_once __DIR__ . "/rkrk_assess_quiz_progress.inc.php";

if (function_exists("rkrk_assess_portal_sessions_enforce_access")) {
	rkrk_assess_portal_sessions_enforce_access();
}
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

if (function_exists("ProtectFromAccess")) {
	CheckIPandExit($folderoffset);
}
if (function_exists("WriteToLog")) {
	WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", "../");
}
GrantAccess($folderoffset);

$currentAccessLevel = "";
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string) $_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo["Login"]["Level"])) {
	$currentAccessLevel = strtolower(trim((string) $InputUserInfo["Login"]["Level"]));
}
$isGuestRequest = (isset($_GET["GA"]) && strtolower(trim((string) $_GET["GA"])) === "gast");
$OnRKRKS = (
	(isset($OnRKRKS) && (bool) $OnRKRKS)
	|| (isset($onRKRS) && (bool) $onRKRS)
	|| (isset($OnRKRS) && (bool) $OnRKRS)
);
$isAdminMode = (isset($_GET["ADMIN"]) || isset($_POST["ADMIN"]) || (isset($onAdmin) && (bool) $onAdmin));
$isInstrMode = (isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($onInstr) && (bool) $onInstr));
$canUseExtendedAdmin = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== "gast");
$isStaff = $isAdminMode || $isInstrMode || $canUseExtendedAdmin;

function rkrk_sessions_h($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function rkrk_sessions_btn_label(string $svgFn, string $label): string {
	return function_exists($svgFn) ? $svgFn() : rkrk_sessions_h($label);
}

function rkrk_sessions_btn_attrs(string $label): string {
	$t = rkrk_sessions_h($label);
	return ' title="' . $t . '" aria-label="' . $t . '"';
}

function rkrk_sessions_pick(array $row, array $keys): string {
	foreach ($keys as $k) {
		if (isset($row[$k]) && trim((string) $row[$k]) !== "") {
			return trim((string) $row[$k]);
		}
	}
	return "";
}

function rkrk_sessions_norm_key(string $s): string {
	$s = trim(mb_strtolower($s, "UTF-8"));
	$s = strtr($s, array("ä" => "a", "ö" => "o", "ü" => "u", "Ä" => "a", "Ö" => "o", "Ü" => "u"));
	$s = preg_replace("/\s+/u", " ", $s);
	return (string) $s;
}

function rkrk_sessions_ts($raw, int $fallback = 0): int {
	$raw = trim((string) $raw);
	if ($raw !== "") {
		$p = strtotime(str_replace("T", " ", $raw));
		if ($p !== false && $p > 0) {
			return (int) $p;
		}
	}
	return $fallback > 0 ? $fallback : 0;
}

function rkrk_sessions_href(string $path, array $params): string {
	$params = array_filter($params, static function ($v) {
		return $v !== null && $v !== false;
	});
	$q = http_build_query(function_exists("rkrk_assess_merge_query_with_setting")
		? rkrk_assess_merge_query_with_setting($params)
		: $params);
	$url = $path . ($q !== "" ? ("?" . $q) : "");
	return function_exists("rkrk_assess_append_setting_href")
		? rkrk_assess_append_setting_href($url)
		: $url;
}

function rkrk_sessions_theory_href(array $id, bool $startQuiz): string {
	$params = array(
		"quiz" => (string) ($id["quiz_key"] ?? ""),
		"Name" => (string) ($id["name"] ?? ""),
		"PersPick" => "on",
	);
	$drone = trim((string) ($id["drone"] ?? ""));
	if ($drone !== "") {
		$params["Drone"] = $drone;
	}
	$exam = trim((string) ($id["exam_code"] ?? ""));
	if ($exam !== "") {
		$params["EXAM"] = $exam;
	}
	$qm = trim((string) ($id["quiz_modus"] ?? ""));
	if ($qm !== "" && (strcasecmp($qm, "pruefung") === 0 || strcasecmp($qm, "prüfung") === 0)) {
		$params["QuizModus"] = "pruefung";
	}
	$schul = trim((string) ($id["schulung"] ?? ""));
	if ($schul !== "") {
		$params["Schulung"] = $schul === "1" || strcasecmp($schul, "schulung") === 0 ? "" : $schul;
	}
	$pid = trim((string) ($id["pilot_id"] ?? ""));
	if ($pid !== "") {
		$params["PilotID"] = $pid;
	}
	if ($startQuiz) {
		$params["Status"] = "Quiz";
	}
	return rkrk_sessions_href("rkrk_digitalassess.php", $params);
}

function rkrk_sessions_checklist_href(string $script, string $file, string $edit = "", string $draftId = ""): string {
	$params = array(
		"mode" => "form",
		"file" => $file,
		"PersPick" => "on",
	);
	if ($edit !== "") {
		$params["edit"] = $edit;
	} elseif ($draftId !== "") {
		$params["draft_id"] = $draftId;
	}
	return rkrk_sessions_href($script, $params);
}

function rkrk_sessions_report_href(string $stem, string $kind = "assess"): string {
	if ($stem === "" || !function_exists("rkrk_assess_report_download_href") || !function_exists("rkrk_assess_reports_dir")) {
		return "";
	}
	$stem = function_exists("rkrk_assess_report_stem_from_result")
		? rkrk_assess_report_stem_from_result($stem)
		: preg_replace('/\.(json|pdf|html)$/i', "", basename(str_replace("\\", "/", $stem)));
	$stem = trim((string) $stem);
	if ($stem === "") {
		return "";
	}
	$prev = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
	$GLOBALS["rkrk_assess_reports_kind"] = (strtolower($kind) === "live") ? "live" : "assess";
	$dir = rkrk_assess_reports_dir();
	$href = "";
	if ($dir !== "") {
		foreach (array("pdf", "html") as $fmt) {
			$path = $dir . $stem . "." . $fmt;
			if (is_file($path)) {
				$href = rkrk_assess_report_download_href($path, $fmt);
				break;
			}
		}
	}
	if ($prev === null) {
		unset($GLOBALS["rkrk_assess_reports_kind"]);
	} else {
		$GLOBALS["rkrk_assess_reports_kind"] = $prev;
	}
	return is_string($href) ? $href : "";
}

function rkrk_sessions_template_title(string $templatesDir, string $file): string {
	$base = basename($file);
	$stem = pathinfo($base, PATHINFO_FILENAME);
	if ($stem === "") {
		return $base !== "" ? $base : "(ohne Template)";
	}
	$dir = rtrim(str_replace("\\", "/", $templatesDir), "/");
	foreach (array($base, $stem . ".json", $stem) as $cand) {
		$p = $dir . "/" . $cand;
		if (!is_file($p)) {
			continue;
		}
		$raw = @file_get_contents($p);
		$data = is_string($raw) ? json_decode($raw, true) : null;
		if (is_array($data) && trim((string) ($data["title"] ?? "")) !== "") {
			return trim((string) $data["title"]);
		}
	}
	return $stem;
}

/**
 * @return list<array<string,mixed>>
 */
function rkrk_sessions_load_checklist_results(string $resultsDir, string $templatesDir, string $kind, string $script): array {
	$out = array();
	$dir = rtrim(str_replace("\\", "/", $resultsDir), "/");
	if ($dir === "" || !is_dir($dir)) {
		return $out;
	}
	$files = glob($dir . "/*.json") ?: array();
	foreach ($files as $jsonFile) {
		if (!is_file($jsonFile)) {
			continue;
		}
		$raw = @file_get_contents($jsonFile);
		$data = is_string($raw) ? json_decode($raw, true) : null;
		if (!is_array($data)) {
			continue;
		}
		if (
			(isset($data["exam_code"]) || isset($data["attempts"]))
			&& (!isset($data["template"]) || trim((string) $data["template"]) === "")
			&& (!isset($data["fields"]) || !is_array($data["fields"]))
		) {
			continue;
		}
		$tplFile = basename(trim((string) ($data["template"] ?? $data["file"] ?? "")));
		if ($tplFile === "") {
			continue;
		}
		$fields = (isset($data["fields"]) && is_array($data["fields"])) ? $data["fields"] : array();
		$pilot = "";
		if (function_exists("rkrk_assess_fields_pilot_name_and_id")) {
			$pi = rkrk_assess_fields_pilot_name_and_id($fields);
			$pilot = trim((string) ($pi["name"] ?? ""));
		}
		$id = basename($jsonFile, ".json");
		$updated = trim((string) ($data["updated_at"] ?? $data["modified_at"] ?? $data["created_at"] ?? ""));
		$mt = @filemtime($jsonFile);
		$ts = rkrk_sessions_ts($updated, $mt !== false ? (int) $mt : 0);
		$title = rkrk_sessions_template_title($templatesDir, $tplFile);
		$art = ($kind === "live") ? "beurteilung" : "assessment";
		$out[] = array(
			"state" => "done",
			"art" => $art,
			"art_label" => ($kind === "live") ? "Beurteilung" : "Assessment",
			"pilot" => $pilot !== "" ? $pilot : "(ohne Pilot)",
			"title" => $title,
			"detail" => $tplFile,
			"exam_code" => "",
			"when" => $ts,
			"status_label" => "Abgeschlossen",
			"recovery" => rkrk_sessions_checklist_href($script, $tplFile, $id, ""),
			"form" => rkrk_sessions_checklist_href($script, $tplFile, $id, ""),
			"report" => rkrk_sessions_report_href($id, $kind === "live" ? "live" : "assess"),
			"dedup" => $art . "|done|" . rkrk_sessions_norm_key($pilot) . "|" . rkrk_sessions_norm_key($id),
			"delete" => array(
				"kind" => ($kind === "live") ? "evaluation_json" : "assessment_json",
				"result_id" => $id,
			),
		);
	}
	return $out;
}


function rkrk_sessions_mode_params(array $extra = array()): array {
	$params = $extra;
	if (!empty($GLOBALS["isAdminMode"])) {
		$params["ADMIN"] = "1";
	}
	if (!empty($GLOBALS["isInstrMode"]) && empty($params["ADMIN"])) {
		$params["INSTR"] = "1";
	}
	if (function_exists("rkrk_assess_sessions_merge_cs_query")) {
		$params = rkrk_assess_sessions_merge_cs_query($params);
	}
	return $params;
}

function rkrk_sessions_safe_id(string $id): string {
	$id = basename(str_replace("\\", "/", trim($id)));
	$id = preg_replace('/\.json$/i', "", $id);
	if (!is_string($id) || !preg_match('/^[A-Za-z0-9._-]{1,160}$/', $id)) {
		return "";
	}
	return $id;
}

function rkrk_sessions_unlink_result_json(string $baseDir, string $id): bool {
	$id = rkrk_sessions_safe_id($id);
	$dir = rtrim(str_replace("\\", "/", trim($baseDir)), "/");
	if ($id === "" || $dir === "") {
		return false;
	}
	$candidate = $dir . "/" . $id . ".json";
	if (!is_file($candidate) || basename($candidate) !== ($id . ".json")) {
		return false;
	}
	return @unlink($candidate);
}

function rkrk_sessions_delete_theory_quizlog(array $match): bool {
	$dir = isset($GLOBALS["QuizLocResults"]) ? rtrim((string) $GLOBALS["QuizLocResults"], "/\\") : "";
	if ($dir === "" || !function_exists("rkrk_assess_quiz_results_load_rows") || !function_exists("rkrk_assess_quiz_results_save_rows")) {
		return false;
	}
	$name = rkrk_sessions_norm_key((string) ($match["name"] ?? ""));
	$quiz = rkrk_sessions_norm_key((string) ($match["quiz"] ?? ""));
	$date = trim((string) ($match["date"] ?? ""));
	if ($name === "" && $quiz === "" && $date === "") {
		return false;
	}
	$rows = rkrk_assess_quiz_results_load_rows($dir);
	$idx = -1;
	foreach ($rows as $i => $row) {
		if (!is_array($row)) {
			continue;
		}
		$rn = rkrk_sessions_norm_key(rkrk_sessions_pick($row, array("Name", "Pilot", "Teilnehmer")));
		$rq = rkrk_sessions_norm_key(rkrk_sessions_pick($row, array("Quiz", "quiz", "quiz_key")));
		$rd = rkrk_sessions_pick($row, array("Zeit", "Datum"));
		if ($name !== "" && $rn !== $name) {
			continue;
		}
		if ($quiz !== "" && $rq !== $quiz) {
			continue;
		}
		if ($date !== "" && $rd !== $date && mb_stripos($rd, $date, 0, "UTF-8") === false) {
			continue;
		}
		$idx = (int) $i;
		break;
	}
	if ($idx < 0) {
		return false;
	}
	array_splice($rows, $idx, 1);
	return rkrk_assess_quiz_results_save_rows($dir, $rows);
}

function rkrk_sessions_admin_can_delete(): bool {
	if (function_exists("rkrk_assess_rkrs_access_via") && rkrk_assess_rkrs_access_via("sessions") === "cs") {
		return false;
	}
	return !empty($GLOBALS["canUseExtendedAdmin"]) && !empty($GLOBALS["isAdminMode"]);
}

function rkrk_sessions_delete_entry(array $post): bool {
	$kind = trim((string) ($post["delete_kind"] ?? ""));
	$ok = false;
	if ($kind === "quiz_progress") {
		$quizPath = basename(trim((string) ($post["progress_path"] ?? "")));
		if ($quizPath !== "" && function_exists("rkrk_assess_quiz_progress_delete_by_path")) {
			$ok = rkrk_assess_quiz_progress_delete_by_path($quizPath);
		}
		if (!$ok && function_exists("rkrk_assess_quiz_progress_delete")) {
			$ok = rkrk_assess_quiz_progress_delete(array(
				"quiz_key" => trim((string) ($post["progress_quiz_key"] ?? "")),
				"name" => trim((string) ($post["progress_name"] ?? "")),
				"exam_code" => trim((string) ($post["progress_exam_code"] ?? "")),
				"drone" => trim((string) ($post["progress_drone"] ?? "")),
				"schulung" => trim((string) ($post["progress_schulung"] ?? "")),
				"quiz_modus" => trim((string) ($post["progress_quiz_modus"] ?? "")),
			));
		}
	} elseif ($kind === "exam_session_progress") {
		$examPath = basename(trim((string) ($post["progress_path"] ?? "")));
		$examName = trim((string) ($post["progress_name"] ?? ""));
		if ($examPath !== "" && function_exists("rkrk_assess_exam_delete_in_progress_file")) {
			$ok = rkrk_assess_exam_delete_in_progress_file($examPath);
		}
		if ($ok && $examName !== "" && function_exists("rkrk_assess_quiz_progress_delete_best_effort")) {
			rkrk_assess_quiz_progress_delete_best_effort(array(
				"quiz_key" => trim((string) ($post["progress_quiz_key"] ?? "")),
				"name" => $examName,
				"exam_code" => trim((string) ($post["progress_exam_code"] ?? "")),
			));
		}
	} elseif ($kind === "exam_json") {
		$examStem = rkrk_sessions_safe_id((string) ($post["exam_stem"] ?? $post["progress_path"] ?? ""));
		if ($examStem !== "" && function_exists("rkrk_assess_exam_delete_file")) {
			$ok = rkrk_assess_exam_delete_file($examStem . ".json", false);
		}
		$reportRemoved = 0;
		if ($examStem !== "" && function_exists("rkrk_assess_report_delete_by_stem")) {
			$reportRemoved = rkrk_assess_report_delete_by_stem($examStem, "assess");
		}
		if (!$ok && $reportRemoved > 0) {
			$ok = true;
		}
		if ($ok) {
			rkrk_sessions_delete_theory_quizlog(array(
				"name" => trim((string) ($post["progress_name"] ?? "")),
				"quiz" => trim((string) ($post["progress_quiz_key"] ?? "")),
				"date" => trim((string) ($post["delete_date"] ?? "")),
			));
		}
	} elseif ($kind === "theory_csv") {
		$ok = rkrk_sessions_delete_theory_quizlog(array(
			"name" => trim((string) ($post["delete_name"] ?? $post["progress_name"] ?? "")),
			"quiz" => trim((string) ($post["delete_quiz"] ?? $post["progress_quiz_key"] ?? "")),
			"date" => trim((string) ($post["delete_date"] ?? "")),
		));
	} elseif ($kind === "assessment_json" || $kind === "evaluation_json") {
		$rid = rkrk_sessions_safe_id((string) ($post["result_id"] ?? ""));
		$isLive = ($kind === "evaluation_json");
		$base = $isLive
			? (string) ($GLOBALS["rkrkLiveassessResults"] ?? "")
			: (string) ($GLOBALS["rkrkAssessResults"] ?? "");
		if ($rid !== "" && $base !== "") {
			$ok = rkrk_sessions_unlink_result_json($base, $rid);
		}
		if ($rid !== "" && function_exists("rkrk_assess_report_delete_by_stem")) {
			rkrk_assess_report_delete_by_stem($rid, $isLive ? "live" : "assess");
		}
	} elseif ($kind === "checklist_progress") {
		$progKind = strtolower(trim((string) ($post["progress_kind"] ?? "assess"))) === "live" ? "live" : "assess";
		$progFile = basename(trim((string) ($post["progress_file"] ?? "")));
		$progEdit = basename(trim((string) ($post["progress_edit"] ?? "")));
		$progDraft = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($post["progress_draft_id"] ?? ""));
		$progPathBase = basename(trim((string) ($post["progress_path"] ?? "")));
		if ($progFile !== "" && ($progEdit !== "" || $progDraft !== "") && function_exists("rkrk_assess_checklist_progress_delete")) {
			$ok = rkrk_assess_checklist_progress_delete(array(
				"kind" => $progKind,
				"file" => $progFile,
				"edit" => $progEdit,
				"draft_id" => $progDraft,
			));
		}
		if (!$ok && $progPathBase !== "" && function_exists("rkrk_assess_checklist_progress_delete_by_path")) {
			$ok = rkrk_assess_checklist_progress_delete_by_path($progKind, $progPathBase);
		}
	}
	return (bool) $ok;
}

function rkrk_sessions_delete_form_html(array $row): string {
	if (!rkrk_sessions_admin_can_delete()) {
		return "";
	}
	$del = isset($row["delete"]) && is_array($row["delete"]) ? $row["delete"] : array();
	$kind = trim((string) ($del["kind"] ?? ""));
	if ($kind === "") {
		return "";
	}
	$pilot = trim((string) ($row["pilot"] ?? ""));
	$title = trim((string) ($row["title"] ?? ""));
	$confirm = "Eintrag wirklich löschen?";
	if ($pilot !== "" || $title !== "") {
		$confirm = "Eintrag von " . ($pilot !== "" ? $pilot : "ohne Pilot") . " — " . ($title !== "" ? $title : "Session") . " endgültig löschen?";
	}
	$h = static function ($v): string {
		return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
	};
	$hidden = '<input type="hidden" name="sess_admin_delete" value="1">'
		. '<input type="hidden" name="delete_kind" value="' . $h($kind) . '">'
		. '<input type="hidden" name="ADMIN" value="1">'
		. '<input type="hidden" name="s_state" value="' . $h((string) ($_GET["s_state"] ?? "all")) . '">'
		. '<input type="hidden" name="s_art" value="' . $h((string) ($_GET["s_art"] ?? "all")) . '">'
		. '<input type="hidden" name="s_day" value="' . $h((string) ($_GET["s_day"] ?? date("Y-m-d"))) . '">'
		. '<input type="hidden" name="s_q" value="' . $h((string) ($_GET["s_q"] ?? "")) . '">';
	$map = array(
		"progress_path" => "progress_path",
		"progress_quiz_key" => "progress_quiz_key",
		"quiz_key" => "progress_quiz_key",
		"progress_name" => "progress_name",
		"name" => "progress_name",
		"progress_exam_code" => "progress_exam_code",
		"exam_code" => "progress_exam_code",
		"progress_drone" => "progress_drone",
		"drone" => "progress_drone",
		"progress_schulung" => "progress_schulung",
		"schulung" => "progress_schulung",
		"progress_quiz_modus" => "progress_quiz_modus",
		"quiz_modus" => "progress_quiz_modus",
		"exam_stem" => "exam_stem",
		"delete_name" => "delete_name",
		"delete_date" => "delete_date",
		"date" => "delete_date",
		"delete_quiz" => "delete_quiz",
		"quiz" => "delete_quiz",
		"result_id" => "result_id",
		"progress_kind" => "progress_kind",
		"progress_file" => "progress_file",
		"progress_edit" => "progress_edit",
		"progress_draft_id" => "progress_draft_id",
	);
	$seen = array("sess_admin_delete" => true, "delete_kind" => true, "ADMIN" => true, "s_state" => true, "s_art" => true, "s_day" => true, "s_q" => true);
	foreach ($map as $src => $dst) {
		if (isset($seen[$dst]) || !isset($del[$src])) {
			continue;
		}
		$val = trim((string) $del[$src]);
		if ($val === "") {
			continue;
		}
		$seen[$dst] = true;
		$hidden .= '<input type="hidden" name="' . $h($dst) . '" value="' . $h($val) . '">';
	}
	$settingHidden = "";
	if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
		ob_start();
		rkrk_assess_echo_setting_hidden_input();
		$settingHidden = (string) ob_get_clean();
	}
	return '<form method="post" action="rkrk_assess_sessions.php" style="margin:0;display:inline;" onsubmit="return confirm(' . json_encode($confirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');">'
		. $hidden
		. $settingHidden
		. '<button type="submit" class="rkrk-sess-del"' . rkrk_sessions_btn_attrs("Löschen") . '>' . rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_trash", "Löschen") . '</button>'
		. '</form>';
}


if (
	$_SERVER["REQUEST_METHOD"] === "POST"
	&& !empty($_POST["sess_admin_delete"])
	&& rkrk_sessions_admin_can_delete()
) {
	$ok = rkrk_sessions_delete_entry($_POST);
	$ret = rkrk_sessions_mode_params(array(
		"s_state" => trim((string) ($_POST["s_state"] ?? "all")),
		"s_art" => trim((string) ($_POST["s_art"] ?? "all")),
		"s_day" => trim((string) ($_POST["s_day"] ?? "")),
		"s_q" => trim((string) ($_POST["s_q"] ?? "")),
	));
	if ($ok) {
		$ret["deleted"] = "1";
	} else {
		$ret["delete_err"] = "1";
	}
	$loc = rkrk_sessions_href("rkrk_assess_sessions.php", $ret);
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		ob_end_clean();
	}
	header("Location: " . $loc, true, 303);
	exit;
}

rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
if ($onRKRS) {
	headerpage("Tagesübersicht", false, $folderoffset);
} else {
	headerpage("Tagesübersicht", false, $folderoffset);
}
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (empty($LoggedOn)) {
	if (function_exists("RegisterForm")) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

$filterState = trim((string) ($_GET["s_state"] ?? "all"));
if (!in_array($filterState, array("all", "running", "done"), true)) {
	$filterState = "all";
}
$filterArt = trim((string) ($_GET["s_art"] ?? "all"));
if (!in_array($filterArt, array("all", "pruefung", "schulung", "assessment", "beurteilung", "quiz"), true)) {
	$filterArt = "all";
}
$filterQ = trim((string) ($_GET["s_q"] ?? ""));
$filterDayRaw = array_key_exists("s_day", $_GET) ? trim((string) $_GET["s_day"]) : date("Y-m-d");
$filterDay = "";
if ($filterDayRaw !== "" && strcasecmp($filterDayRaw, "all") !== 0) {
	$dayTs = strtotime($filterDayRaw . " 00:00:00");
	$filterDay = ($dayTs !== false) ? date("Y-m-d", $dayTs) : date("Y-m-d");
}

$rows = array();
$seen = array();

$addRow = static function (array $row) use (&$rows, &$seen): void {
	$k = trim((string) ($row["dedup"] ?? ""));
	if ($k !== "" && isset($seen[$k])) {
		$idx = $seen[$k];
		$cur = $rows[$idx];
		if ((int) ($row["when"] ?? 0) > (int) ($cur["when"] ?? 0)) {
			$rows[$idx] = $row;
		}
		if (trim((string) ($cur["recovery"] ?? "")) === "" && trim((string) ($row["recovery"] ?? "")) !== "") {
			$rows[$idx]["recovery"] = $row["recovery"];
		}
		if (trim((string) ($cur["report"] ?? "")) === "" && trim((string) ($row["report"] ?? "")) !== "") {
			$rows[$idx]["report"] = $row["report"];
		}
		if (trim((string) ($cur["form"] ?? "")) === "" && trim((string) ($row["form"] ?? "")) !== "") {
			$rows[$idx]["form"] = $row["form"];
		}
		if (empty($rows[$idx]["delete"]) && !empty($row["delete"]) && is_array($row["delete"])) {
			$rows[$idx]["delete"] = $row["delete"];
		}
		return;
	}
	if ($k !== "") {
		$seen[$k] = count($rows);
	}
	$rows[] = $row;
};

$classifyTheory = static function (string $quizModus, string $schulung, string $examCode) : string {
	if (trim($schulung) !== "") {
		return "schulung";
	}
	$qm = function_exists("rkrk_assess_quiz_modus_normalize")
		? rkrk_assess_quiz_modus_normalize($quizModus)
		: strtolower($quizModus);
	if ($examCode !== "" || $qm === "pruefung") {
		return "pruefung";
	}
	return "quiz";
};
$artLabel = static function (string $art): string {
	$map = array(
		"pruefung" => "Prüfung",
		"schulung" => "Schulung",
		"assessment" => "Assessment",
		"beurteilung" => "Beurteilung",
		"quiz" => "Quiz",
	);
	return $map[$art] ?? $art;
};

if (function_exists("rkrk_assess_quiz_progress_list_incomplete")) {
	foreach (rkrk_assess_quiz_progress_list_incomplete(true) as $qp) {
		$name = trim((string) ($qp["name"] ?? ""));
		$quizKey = trim((string) ($qp["quiz_key"] ?? ""));
		$exam = trim((string) ($qp["exam_code"] ?? ""));
		$schul = trim((string) ($qp["schulung"] ?? ""));
		$qm = trim((string) ($qp["quiz_modus"] ?? ""));
		$art = $classifyTheory($qm, $schul, $exam);
		$title = $quizKey;
		if (function_exists("rkrk_assess_quiz_title_from_key") && $quizKey !== "") {
			$title = rkrk_assess_quiz_title_from_key($quizKey);
		}
		$answered = (int) ($qp["answered"] ?? 0);
		$maxQ = (int) ($qp["max_questions"] ?? 0);
		if ($answered < 1 && $exam === "") {
			continue;
		}
		$prog = $maxQ > 0 ? ($answered . "/" . $maxQ) : (string) $answered;
		$addRow(array(
			"state" => "running",
			"art" => $art,
			"art_label" => $artLabel($art),
			"pilot" => $name !== "" ? $name : "(ohne Pilot)",
			"title" => $title,
			"detail" => $prog !== "0" ? ("Frage " . $prog) : "In progress",
			"exam_code" => $exam,
			"when" => (int) ($qp["timestamp"] ?? 0),
			"status_label" => "Laufend",
			"recovery" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($qp["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), true),
			"form" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($qp["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"report" => "",
			"dedup" => "theory|run|" . rkrk_sessions_norm_key($exam !== "" ? $exam : $quizKey) . "|" . rkrk_sessions_norm_key($name),
			"delete" => array(
				"kind" => "quiz_progress",
				"progress_path" => basename((string) ($qp["path"] ?? "")),
				"quiz_key" => $quizKey,
				"name" => $name,
				"exam_code" => $exam,
				"drone" => (string) ($qp["drone"] ?? ""),
				"schulung" => $schul,
				"quiz_modus" => $qm,
			),
		));
	}
}

if (function_exists("rkrk_assess_exam_list_in_progress")) {
	foreach (rkrk_assess_exam_list_in_progress() as $ex) {
		$name = trim((string) ($ex["pilot"] ?? ""));
		$quizKey = trim((string) ($ex["quiz_key"] ?? ""));
		$exam = trim((string) ($ex["exam_code"] ?? ""));
		$schul = trim((string) ($ex["schulung"] ?? ""));
		$qm = trim((string) ($ex["quiz_modus"] ?? ""));
		$art = $classifyTheory($qm, $schul, $exam);
		$title = $quizKey;
		if (function_exists("rkrk_assess_quiz_title_from_key") && $quizKey !== "") {
			$title = rkrk_assess_quiz_title_from_key($quizKey);
		}
		$addRow(array(
			"state" => "running",
			"art" => $art,
			"art_label" => $artLabel($art),
			"pilot" => $name !== "" ? $name : "(ohne Pilot)",
			"title" => $title,
			"detail" => "EXAM-Session offen",
			"exam_code" => $exam,
			"when" => (int) ($ex["timestamp"] ?? 0),
			"status_label" => "Laufend",
			"recovery" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($ex["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), true),
			"form" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($ex["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"report" => "",
			"dedup" => "theory|run|" . rkrk_sessions_norm_key($exam !== "" ? $exam : $quizKey) . "|" . rkrk_sessions_norm_key($name),
			"delete" => array(
				"kind" => "exam_session_progress",
				"progress_path" => basename((string) ($ex["path"] ?? "")),
				"exam_code" => $exam,
				"quiz_key" => $quizKey,
				"name" => $name,
			),
		));
	}
}

foreach (array(
	array("assess", isset($rkrkAssessTemplates) ? (string) $rkrkAssessTemplates : "", "rkrk_assess.php", "assessment", "Assessment"),
	array("live", isset($rkrkLiveassessTemplates) ? (string) $rkrkLiveassessTemplates : "", "rkrk_assess_live.php", "beurteilung", "Beurteilung"),
) as $chkKind) {
	if (!function_exists("rkrk_assess_checklist_progress_list")) {
		break;
	}
	foreach (rkrk_assess_checklist_progress_list($chkKind[0]) as $prog) {
		$file = trim((string) ($prog["file"] ?? ""));
		$edit = trim((string) ($prog["edit"] ?? ""));
		$draft = trim((string) ($prog["draft_id"] ?? ""));
		$pilot = trim((string) ($prog["pilot"] ?? ""));
		$title = trim((string) ($prog["template"] ?? $file));
		if ($chkKind[1] !== "") {
			$title = rkrk_sessions_template_title($chkKind[1], $file !== "" ? $file : $title);
		}
		$addRow(array(
			"state" => "running",
			"art" => $chkKind[3],
			"art_label" => $chkKind[4],
			"pilot" => $pilot !== "" ? $pilot : "(ohne Pilot)",
			"title" => $title,
			"detail" => $edit !== "" ? ("Bearbeiten " . $edit) : "Entwurf",
			"exam_code" => "",
			"when" => (int) ($prog["timestamp"] ?? 0),
			"status_label" => "Laufend",
			"recovery" => rkrk_sessions_checklist_href($chkKind[2], $file, $edit, $draft),
			"form" => rkrk_sessions_checklist_href($chkKind[2], $file, $edit, $draft),
			"report" => "",
			"dedup" => $chkKind[3] . "|run|" . rkrk_sessions_norm_key($pilot) . "|" . rkrk_sessions_norm_key($file . "|" . $edit . "|" . $draft),
			"delete" => array(
				"kind" => "checklist_progress",
				"progress_kind" => (string) $chkKind[0],
				"progress_file" => $file,
				"progress_edit" => $edit,
				"progress_draft_id" => $draft,
				"progress_path" => basename((string) ($prog["path"] ?? "")),
			),
		));
	}
}

if (function_exists("rkrk_assess_exam_list_completed")) {
	foreach (rkrk_assess_exam_list_completed() as $ex) {
		$name = trim((string) ($ex["name"] ?? ""));
		$quizKey = trim((string) ($ex["quiz_key"] ?? ""));
		$exam = trim((string) ($ex["exam_code"] ?? ""));
		$schul = trim((string) ($ex["schulung"] ?? ""));
		$qm = trim((string) ($ex["quiz_modus"] ?? ""));
		$art = $classifyTheory($qm, $schul, $exam);
		$title = $quizKey;
		if (function_exists("rkrk_assess_quiz_title_from_key") && $quizKey !== "") {
			$title = rkrk_assess_quiz_title_from_key($quizKey);
		}
		$pct = isset($ex["pct"]) ? (float) $ex["pct"] : 0.0;
		$passed = !empty($ex["passed"]);
		$stem = trim((string) ($ex["stem"] ?? ""));
		$addRow(array(
			"state" => "done",
			"art" => $art,
			"art_label" => $artLabel($art),
			"pilot" => $name !== "" ? $name : "(ohne Pilot)",
			"title" => $title,
			"detail" => ($passed ? "Bestanden" : "Nicht bestanden") . ($pct > 0 ? (" · " . round($pct, 1) . "%") : ""),
			"exam_code" => $exam,
			"when" => (int) ($ex["timestamp"] ?? 0),
			"status_label" => "Abgeschlossen",
			"recovery" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($ex["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"form" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => (string) ($ex["drone"] ?? ""),
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"report" => $stem !== "" ? rkrk_sessions_report_href($stem, "assess") : "",
			"dedup" => "theory|done|" . rkrk_sessions_norm_key($exam !== "" ? $exam : $quizKey) . "|" . rkrk_sessions_norm_key($name),
			"delete" => array(
				"kind" => "exam_json",
				"exam_stem" => $stem,
				"exam_code" => $exam,
				"quiz_key" => $quizKey,
				"name" => $name,
			),
		));
	}
}

$quizResultsDir = isset($QuizLocResults) ? rtrim((string) $QuizLocResults, "/\\") : "";
if ($quizResultsDir !== "" && function_exists("rkrk_assess_quiz_results_load_rows")) {
	foreach (rkrk_assess_quiz_results_load_rows($quizResultsDir) as $qr) {
		$exam = rkrk_sessions_pick($qr, array("EXAM", "Exam", "exam_code"));
		$qm = rkrk_sessions_pick($qr, array("QuizModus", "quiz_modus"));
		$schul = rkrk_sessions_pick($qr, array("Schulung", "schulung"));
		$art = $classifyTheory($qm, $schul, $exam);
		if ($art === "quiz") {
			continue;
		}
		$name = rkrk_sessions_pick($qr, array("Name", "Pilot", "Teilnehmer"));
		$quizKey = rkrk_sessions_pick($qr, array("Quiz", "quiz", "quiz_key"));
		$drone = rkrk_sessions_pick($qr, array("Drone", "Drohne"));
		$zeit = rkrk_sessions_pick($qr, array("Zeit", "Datum"));
		$title = $quizKey;
		if (function_exists("rkrk_assess_quiz_title_from_key") && $quizKey !== "") {
			$title = rkrk_assess_quiz_title_from_key($quizKey);
		}
		$addRow(array(
			"state" => "done",
			"art" => $art,
			"art_label" => $artLabel($art),
			"pilot" => $name !== "" ? $name : "(ohne Pilot)",
			"title" => $title,
			"detail" => $zeit,
			"exam_code" => $exam,
			"when" => rkrk_sessions_ts($zeit),
			"status_label" => "Abgeschlossen",
			"recovery" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => $drone,
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"form" => rkrk_sessions_theory_href(array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => $drone,
				"exam_code" => $exam,
				"quiz_modus" => $qm,
				"schulung" => $schul,
			), false),
			"report" => (function_exists("rkrk_assess_report_find_existing_href")
				? (string) rkrk_assess_report_find_existing_href($exam, $name, $quizKey)
				: ""),
			"dedup" => "theory|done|" . rkrk_sessions_norm_key($exam !== "" ? $exam : ($quizKey . "|" . $zeit)) . "|" . rkrk_sessions_norm_key($name),
			"delete" => array(
				"kind" => "theory_csv",
				"name" => $name,
				"date" => $zeit,
				"quiz" => $quizKey,
			),
		));
	}
}

if (isset($rkrkAssessResults, $rkrkAssessTemplates)) {
	foreach (rkrk_sessions_load_checklist_results((string) $rkrkAssessResults, (string) $rkrkAssessTemplates, "assess", "rkrk_assess.php") as $r) {
		$addRow($r);
	}
}
if (isset($rkrkLiveassessResults, $rkrkLiveassessTemplates)) {
	foreach (rkrk_sessions_load_checklist_results((string) $rkrkLiveassessResults, (string) $rkrkLiveassessTemplates, "live", "rkrk_assess_live.php") as $r) {
		$addRow($r);
	}
}

$doneTheory = array();
foreach ($rows as $r) {
	if ((string) ($r["state"] ?? "") !== "done") {
		continue;
	}
	$art = (string) ($r["art"] ?? "");
	if (!in_array($art, array("pruefung", "schulung", "quiz"), true)) {
		continue;
	}
	$ident = trim((string) ($r["exam_code"] ?? ""));
	if ($ident === "") {
		$ident = (string) ($r["title"] ?? "");
	}
	$doneTheory[rkrk_sessions_norm_key($ident) . "|" . rkrk_sessions_norm_key((string) ($r["pilot"] ?? ""))] = true;
}
$rows = array_values(array_filter($rows, static function ($r) use ($doneTheory) {
	if ((string) ($r["state"] ?? "") !== "running") {
		return true;
	}
	$art = (string) ($r["art"] ?? "");
	if (!in_array($art, array("pruefung", "schulung", "quiz"), true)) {
		return true;
	}
	$ident = trim((string) ($r["exam_code"] ?? ""));
	if ($ident === "") {
		$ident = (string) ($r["title"] ?? "");
	}
	$key = rkrk_sessions_norm_key($ident) . "|" . rkrk_sessions_norm_key((string) ($r["pilot"] ?? ""));
	return empty($doneTheory[$key]);
}));

$filtered = array();
foreach ($rows as $r) {
	if ($filterState !== "all" && (string) ($r["state"] ?? "") !== $filterState) {
		continue;
	}
	if ($filterArt !== "all" && (string) ($r["art"] ?? "") !== $filterArt) {
		continue;
	}
	if ($filterQ !== "") {
		$hay = mb_strtolower(implode(" ", array(
			(string) ($r["pilot"] ?? ""),
			(string) ($r["title"] ?? ""),
			(string) ($r["detail"] ?? ""),
			(string) ($r["exam_code"] ?? ""),
			(string) ($r["art_label"] ?? ""),
		)), "UTF-8");
		if (mb_stripos($hay, $filterQ, 0, "UTF-8") === false) {
			continue;
		}
	}
	if ($filterDay !== "") {
		$when = (int) ($r["when"] ?? 0);
		$whenDay = $when > 0 ? date("Y-m-d", $when) : "";
		$isRunning = ((string) ($r["state"] ?? "") === "running");
		if ($isRunning) {
			// Laufend: sichtbar ab Starttag bis Abschluss (auch an Folgetagen).
			if ($whenDay !== "" && $whenDay > $filterDay) {
				continue;
			}
		} else {
			// Abgeschlossen: nur am Abschluss-/when-Tag.
			if ($whenDay !== $filterDay) {
				continue;
			}
		}
	}
	$filtered[] = $r;
}
usort($filtered, static function ($a, $b) {
	$as = ((string) ($a["state"] ?? "") === "running") ? 0 : 1;
	$bs = ((string) ($b["state"] ?? "") === "running") ? 0 : 1;
	if ($as !== $bs) {
		return $as <=> $bs;
	}
	return ((int) ($b["when"] ?? 0)) <=> ((int) ($a["when"] ?? 0));
});

$runningCount = 0;
$doneCount = 0;
foreach ($filtered as $r) {
	if ((string) ($r["state"] ?? "") === "running") {
		$runningCount++;
	} else {
		$doneCount++;
	}
}

$selfHref = rkrk_sessions_href("rkrk_assess_sessions.php", rkrk_sessions_mode_params());
?>
<style>
.rkrk-sess-table{width:100%;border-collapse:collapse;font-size:13px;}
.rkrk-sess-table th,.rkrk-sess-table td{padding:7px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top;}
.rkrk-sess-table th{font-size:12px;color:#64748b;font-weight:700;white-space:nowrap;}
.rkrk-sess-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;}
.rkrk-sess-badge--run{background:#dbeafe;color:#1e40af;}
.rkrk-sess-badge--done{background:#e2e8f0;color:#334155;}
.rkrk-sess-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center;}
.rkrk-sess-actions a,.rkrk-sess-actions button{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;padding:0;border-radius:6px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;text-decoration:none;cursor:pointer;font-family:inherit;line-height:1;}
.rkrk-sess-actions a.rkrk-sess-rec{background:#1d4ed8;border-color:#1d4ed8;color:#fff;}
.rkrk-sess-actions a.rkrk-sess-rec:hover{background:#1e40af;}
.rkrk-sess-actions svg.chk-ico,.rkrk-sess-filters svg.chk-ico{width:16px;height:16px;flex-shrink:0;}
.rkrk-sess-filters .btn,.rkrk-sess-filters .ql-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;padding:0;}
.rkrk-sess-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin:0 0 12px;}
.rkrk-sess-filters .text-input{min-width:140px;}
.rkrk-sess-row--run{background:#eff6ff;}
.rkrk-sess-actions button.rkrk-sess-del{border-color:#f5b5b5;background:#fee2e2;color:#8b1d1d;font-weight:700;}
.rkrk-sess-actions button.rkrk-sess-del:hover{background:#fecaca;}
.rkrk-sess-flash{margin:0 0 12px;padding:8px 10px;border-radius:8px;font-size:13px;}
.rkrk-sess-flash--ok{background:#dcfce7;color:#166534;}
.rkrk-sess-flash--err{background:#fee2e2;color:#991b1b;}
</style>
<div class="wrap rkrk-wrap">
  <?php
  if (function_exists("rkrk_assess_portal_nav_echo")) {
  	ob_start();
  	rkrk_assess_portal_nav_echo(array(
  		"btn_class" => "ql-btn",
  		"schulung_mode" => false,
  		"is_admin" => $canUseExtendedAdmin && $isAdminMode,
  		"is_instr" => $canUseExtendedAdmin && $isInstrMode,
  		"selected_quiz" => "",
  	));
  	$__nav = ob_get_clean();
  	if ($__nav !== "") {
  		echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $__nav . "</div>";
  	}
  }
  ?>

  <div class="card rkrk-card" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
    <div>
      <h2 style="margin-top:0;">Tagesübersicht</h2>
      <p style="margin:0;color:#64748b;font-size:13px;">Laufende und abgeschlossene Prüfungen, Assessments und Schulungen — mit Recovery-Link, falls ein Fenster geschlossen wurde.</p>
    </div>
    <?php if (!empty($QuizIconLoc) && !empty($logopic)): ?>
    <div>
      <img src="<?php echo rkrk_sessions_h($QuizIconLoc . $logopic); ?>" alt="" style="height:56px;">
    </div>
    <?php endif; ?>
  </div>

  <?php if (!$isStaff): ?>
    <div class="card rkrk-card">Diese Seite ist für INSTR und ADMIN.</div>
  <?php else: ?>

  <div class="card rkrk-card">
    <form class="rkrk-sess-filters" method="get" action="rkrk_assess_sessions.php">
      <?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
      <?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("cs")); } ?>
      <?php if (!empty($isAdminMode)): ?><input type="hidden" name="ADMIN" value="1"><?php endif; ?>
      <?php if (!empty($isInstrMode) && empty($isAdminMode)): ?><input type="hidden" name="INSTR" value="1"><?php endif; ?>
      <div>
        <label><b>Status</b></label><br>
        <select class="text-input" name="s_state">
          <option value="all"<?php echo $filterState === "all" ? " selected" : ""; ?>>Alle</option>
          <option value="running"<?php echo $filterState === "running" ? " selected" : ""; ?>>Nur laufend</option>
          <option value="done"<?php echo $filterState === "done" ? " selected" : ""; ?>>Nur abgeschlossen</option>
        </select>
      </div>
      <div>
        <label><b>Art</b></label><br>
        <select class="text-input" name="s_art">
          <option value="all"<?php echo $filterArt === "all" ? " selected" : ""; ?>>Alle</option>
          <option value="pruefung"<?php echo $filterArt === "pruefung" ? " selected" : ""; ?>>Prüfung</option>
          <option value="schulung"<?php echo $filterArt === "schulung" ? " selected" : ""; ?>>Schulung</option>
          <option value="assessment"<?php echo $filterArt === "assessment" ? " selected" : ""; ?>>Assessment</option>
          <option value="beurteilung"<?php echo $filterArt === "beurteilung" ? " selected" : ""; ?>>Beurteilung</option>
          <option value="quiz"<?php echo $filterArt === "quiz" ? " selected" : ""; ?>>Quiz</option>
        </select>
      </div>
      <div>
        <label><b>Tag</b></label><br>
        <input class="text-input" type="date" name="s_day" value="<?php echo rkrk_sessions_h($filterDay); ?>">
      </div>
      <div>
        <label><b>Suche</b></label><br>
        <input class="text-input" type="search" name="s_q" value="<?php echo rkrk_sessions_h($filterQ); ?>" placeholder="Pilot, Test, EXAM…">
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" type="submit"<?php echo rkrk_sessions_btn_attrs("Filter"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_filter", "Filter"); ?></button>
        <a class="ql-btn" href="<?php echo rkrk_sessions_h($selfHref); ?>"<?php echo rkrk_sessions_btn_attrs("Heute"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_calendar", "Heute"); ?></a>
        <a class="ql-btn" href="<?php echo rkrk_sessions_h(rkrk_sessions_href("rkrk_assess_sessions.php", rkrk_sessions_mode_params(array("s_day" => "all")))); ?>"<?php echo rkrk_sessions_btn_attrs("Alle Tage"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_calendar_range", "Alle Tage"); ?></a>
      </div>
    </form>
    <?php if (!empty($_GET["deleted"])): ?>
      <div class="rkrk-sess-flash rkrk-sess-flash--ok">Eintrag gelöscht.</div>
    <?php elseif (!empty($_GET["delete_err"])): ?>
      <div class="rkrk-sess-flash rkrk-sess-flash--err">Löschen fehlgeschlagen.</div>
    <?php endif; ?>
    <div style="margin:0 0 10px;color:#64748b;font-size:12px;">
      <?php echo (int) count($filtered); ?> Einträge
      · <?php echo (int) $runningCount; ?> laufend
      · <?php echo (int) $doneCount; ?> abgeschlossen
    </div>

    <?php if (empty($filtered)): ?>
      <div style="color:#6b7280;">Keine Sessions für die aktuellen Filter.</div>
    <?php else: ?>
    <div style="overflow:auto;">
      <table class="rkrk-sess-table">
        <thead>
          <tr>
            <th>Zeit</th>
            <th>Status</th>
            <th>Art</th>
            <th>Pilot</th>
            <th>Test</th>
            <th>EXAM</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filtered as $r): ?>
            <?php
              $isRun = ((string) ($r["state"] ?? "") === "running");
              $when = (int) ($r["when"] ?? 0);
              $whenLabel = $when > 0 ? date("d.m.y H:i", $when) : "–";
              $rec = trim((string) ($r["recovery"] ?? ""));
              $formHref = trim((string) ($r["form"] ?? ""));
              $rep = trim((string) ($r["report"] ?? ""));
            ?>
            <tr class="<?php echo $isRun ? "rkrk-sess-row--run" : ""; ?>">
              <td style="white-space:nowrap;"><?php echo rkrk_sessions_h($whenLabel); ?></td>
              <td>
                <span class="rkrk-sess-badge <?php echo $isRun ? "rkrk-sess-badge--run" : "rkrk-sess-badge--done"; ?>">
                  <?php echo rkrk_sessions_h((string) ($r["status_label"] ?? "")); ?>
                </span>
              </td>
              <td><?php echo rkrk_sessions_h((string) ($r["art_label"] ?? "")); ?></td>
              <td><?php echo rkrk_sessions_h((string) ($r["pilot"] ?? "")); ?></td>
              <td>
                <?php echo rkrk_sessions_h((string) ($r["title"] ?? "")); ?>
                <?php if (trim((string) ($r["detail"] ?? "")) !== ""): ?>
                  <div style="font-size:11px;color:#64748b;"><?php echo rkrk_sessions_h((string) $r["detail"]); ?></div>
                <?php endif; ?>
              </td>
              <td style="font-family:ui-monospace,Consolas,monospace;font-size:12px;"><?php echo trim((string) ($r["exam_code"] ?? "")) !== "" ? rkrk_sessions_h((string) $r["exam_code"]) : "–"; ?></td>
              <td>
                <div class="rkrk-sess-actions">
                  <?php if ($rec !== ""): ?>
                    <a class="rkrk-sess-rec" href="<?php echo rkrk_sessions_h($rec); ?>" target="_blank" rel="noopener"<?php echo rkrk_sessions_btn_attrs("Wiederherstellen"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_restore", "Wiederherstellen"); ?></a>
                  <?php endif; ?>
                  <?php if ($formHref !== ""): ?>
                    <a href="<?php echo rkrk_sessions_h($formHref); ?>" target="_blank" rel="noopener"<?php echo rkrk_sessions_btn_attrs("Formular öffnen"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_external", "Formular öffnen"); ?></a>
                  <?php endif; ?>
                  <?php if ($rec !== ""): ?>
                    <button type="button" class="rkrk-sess-copy" data-href="<?php echo rkrk_sessions_h($rec); ?>"<?php echo rkrk_sessions_btn_attrs("Link kopieren"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_copy", "Link kopieren"); ?></button>
                  <?php endif; ?>
                  <?php if ($rep !== ""): ?>
                    <a href="<?php echo rkrk_sessions_h($rep); ?>" target="_blank" rel="noopener"<?php echo rkrk_sessions_btn_attrs("Report"); ?>><?php echo rkrk_sessions_btn_label("rkrk_checklist_toolbar_svg_pdf", "Report"); ?></a>
                  <?php endif; ?>
                  <?php echo rkrk_sessions_delete_form_html($r); ?>
                  <?php if ($rec === "" && $formHref === "" && $rep === "" && empty($r["delete"])): ?>
                    <span style="color:#94a3b8;">kein Link</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<script>
(function () {
  document.querySelectorAll(".rkrk-sess-copy").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var href = btn.getAttribute("data-href") || "";
      if (!href) return;
      var abs = href;
      try { abs = new URL(href, window.location.href).href; } catch (e1) {}
      var done = function () {
        var old = btn.getAttribute("title") || "Link kopieren";
        btn.setAttribute("title", "Kopiert");
        btn.setAttribute("aria-label", "Kopiert");
        setTimeout(function () {
          btn.setAttribute("title", old);
          btn.setAttribute("aria-label", old);
        }, 1400);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(abs).then(done).catch(function () {
          window.prompt("Recovery-Link:", abs);
        });
      } else {
        window.prompt("Recovery-Link:", abs);
      }
    });
  });
})();
</script>
<?php
if (function_exists("pageFooterLocal")) {
	pageFooterLocal();
}
