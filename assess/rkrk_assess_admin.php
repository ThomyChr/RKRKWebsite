<?php
// access_control wird mit rkrk_assess_common.php eingebunden und sendet sofort <script>/Echo.
// Puffer: sonst schlägt header(Location) nach POST-Löschen fehl ("headers already sent").
if (function_exists('ob_get_level') && ob_get_level() === 0) {
	ob_start();
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
register_shutdown_function(function () {
	$e = error_get_last();
	if (!is_array($e)) {
		return;
	}
	$msg = "[shutdown] " . ($e['message'] ?? '') . " in " . ($e['file'] ?? '') . ":" . ($e['line'] ?? '') . PHP_EOL;
	@file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug-1791ba.log', $msg, FILE_APPEND);
});
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
if (!isset($SecParamters) || !is_array($SecParamters)) {
	$SecParamters = array();
}
if (!array_key_exists('GA', $SecParamters)) {
	$SecParamters['GA'] = '';
}
if (!isset($_GET['GA'])) {
	$_GET['GA'] = '';
}
$folderoffset = "../";
include_once($folderoffset.'scripts/rkrk_csvfiles.php');
include_once('rkrk_assess_common.php');
include_once(__DIR__ . '/rkrk_assess_exam_session.inc.php');
include_once(__DIR__ . '/rkrk_assess_checklist_progress.inc.php');
include_once(__DIR__ . '/rkrk_assess_quiz_progress.inc.php');
include_once(__DIR__ . '/rkrk_assess_archive.inc.php');
if (!isset($SecParamters) || !is_array($SecParamters)) {
	$SecParamters = array();
}
if (!array_key_exists('GA', $SecParamters)) {
	$SecParamters['GA'] = '';
}

//check impropriate IP addresses accessing page
if (function_exists('ProtectFromAccess')) {
	CheckIPandExit($folderoffset);
}

// Write to LOGfile when legacy logger bootstrap exists.
if (function_exists('WriteToLog')) {
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","","../");
}

GrantAccess($folderoffset);

if (strtolower(trim((string)($_GET['page'] ?? ''))) === 'access') {
	$loc = 'rkrk_assess_admin_access.php';
	if (function_exists('rkrk_assess_append_setting_href')) {
		$loc = rkrk_assess_append_setting_href($loc);
	}
	while (function_exists('ob_get_level') && ob_get_level() > 0) {
		@ob_end_clean();
	}
	header('Location: ' . $loc, true, 303);
	exit;
}

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
	$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$OnRKRKS = (
	(isset($OnRKRKS) && (bool)$OnRKRKS)
	|| (isset($onRKRS) && (bool)$onRKRS)
	|| (isset($OnRKRS) && (bool)$OnRKRS)
);
$isAdminMode = (isset($_GET['ADMIN']) || (isset($onAdmin) && (bool)$onAdmin));
$isInstrMode = (isset($_GET['INSTR']) || isset($_POST['INSTR']) || (isset($onInstr) && (bool)$onInstr));
$canUseExtendedAdmin = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast'  && !$OnRKRKS)
	&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());

// Zeile aus Theorie/Assessment/Beurteilung löschen: Redirect muss vor headerpage() erfolgen
// (sonst sendet rkrk_access_control.php bereits Ausgabe → "Cannot modify header information").
if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& !empty($_POST['exam_admin_delete'])
	&& $canUseExtendedAdmin
) {
	if (!isset($QuizLoc) || trim((string)$QuizLoc) === '') {
		throw new RuntimeException('QuizLoc fehlt in rkrk_assess_common.php');
	}
	if (!isset($QuizIconLoc) || trim((string)$QuizIconLoc) === '') {
		throw new RuntimeException('QuizIconLoc fehlt in rkrk_assess_common.php');
	}
	$CalcIconLoc = $QuizIconLoc;
	$quizLogPath = rtrim((string)$QuizLocResults, "/\\") . '/' . rkrk_assess_quiz_results_json_basename();
	$delKind = trim((string)($_POST['delete_kind'] ?? ''));
	$retRaw = isset($_POST['return_q']) ? (string)$_POST['return_q'] : '';
	parse_str(ltrim($retRaw, '?'), $retParsed);
	if (!is_array($retParsed)) {
		$retParsed = array();
	}
	$retQ = exam_admin_whitelist_admin_query($retParsed);
	$ok = false;
	if ($delKind === 'theory_csv') {
		$li = (int)($_POST['csv_line_index'] ?? -1);
		$match = array(
			'name' => trim((string)($_POST['delete_name'] ?? '')),
			'date' => trim((string)($_POST['delete_date'] ?? '')),
			'quiz' => trim((string)($_POST['delete_quiz'] ?? '')),
		);
		$ok = exam_admin_delete_theory_quizlog_row_match(
			rtrim((string)$QuizLocResults, "/\\"),
			$li,
			$match
		);
		$examStem = basename(trim((string)($_POST['exam_stem'] ?? '')));
		$examStem = preg_replace('/\.json$/i', '', $examStem);
		if ($ok && $examStem !== '' && function_exists('rkrk_assess_exam_delete_file')) {
			rkrk_assess_exam_delete_file($examStem . '.json', false);
		} elseif ($ok && $examStem !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			rkrk_assess_report_delete_by_stem($examStem, 'assess');
		}
	} elseif ($delKind === 'exam_json') {
		$examStem = basename(trim((string)($_POST['exam_stem'] ?? $_POST['progress_path'] ?? '')));
		$examStem = preg_replace('/\.json$/i', '', (string)$examStem);
		if ($examStem !== '' && function_exists('rkrk_assess_exam_delete_file')) {
			$ok = rkrk_assess_exam_delete_file($examStem . '.json', false);
		}
		// Falls Session schon weg / Delete nur Report: Report trotzdem entfernen
		$reportRemoved = 0;
		if ($examStem !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			$reportRemoved = rkrk_assess_report_delete_by_stem($examStem, 'assess');
		}
		if (!$ok && $reportRemoved > 0) {
			$ok = true;
		}
		// Zugehörige QuizResultate-Zeile mit entfernen (best effort)
		if ($ok) {
			$examName = trim((string)($_POST['progress_name'] ?? $_POST['delete_name'] ?? ''));
			$examQuiz = trim((string)($_POST['progress_quiz_key'] ?? $_POST['delete_quiz'] ?? ''));
			$examDate = trim((string)($_POST['delete_date'] ?? ''));
			if ($examName !== '' || $examQuiz !== '') {
				exam_admin_delete_theory_quizlog_row_match(
					rtrim((string)$QuizLocResults, "/\\"),
					-1,
					array(
						'name' => $examName,
						'date' => $examDate,
						'quiz' => $examQuiz,
					)
				);
			}
		}
	} elseif ($delKind === 'assessment_json') {
		$rid = exam_admin_safe_result_id((string)($_POST['result_id'] ?? ''));
		if ($rid !== '' && isset($rkrkAssessResults) && trim((string)$rkrkAssessResults) !== '') {
			$rp = exam_admin_json_result_realpath((string)$rkrkAssessResults, $rid);
			if ($rp !== null) {
				$ok = @unlink($rp);
			}
		}
		if ($rid !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			rkrk_assess_report_delete_by_stem($rid, 'assess');
		}
	} elseif ($delKind === 'evaluation_json') {
		$rid = exam_admin_safe_result_id((string)($_POST['result_id'] ?? ''));
		if ($rid !== '' && isset($rkrkLiveassessResults) && trim((string)$rkrkLiveassessResults) !== '') {
			$rp = exam_admin_json_result_realpath((string)$rkrkLiveassessResults, $rid);
			if ($rp !== null) {
				$ok = @unlink($rp);
			}
		}
		if ($rid !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			rkrk_assess_report_delete_by_stem($rid, 'live');
		}
	} elseif ($delKind === 'checklist_progress') {
		$progFile = basename(trim((string)($_POST['progress_file'] ?? '')));
		$progEdit = basename(trim((string)($_POST['progress_edit'] ?? '')));
		$progDraft = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['progress_draft_id'] ?? ''));
		$progPathBase = basename(trim((string)($_POST['progress_path'] ?? '')));
		if ($progFile !== '' && ($progEdit !== '' || $progDraft !== '') && function_exists('rkrk_assess_checklist_progress_delete')) {
			$ok = rkrk_assess_checklist_progress_delete(array(
				'kind' => 'assess',
				'file' => $progFile,
				'edit' => $progEdit,
				'draft_id' => $progDraft,
			));
		}
		if (!$ok && $progPathBase !== '' && function_exists('rkrk_assess_checklist_progress_delete_by_path')) {
			$ok = rkrk_assess_checklist_progress_delete_by_path('assess', $progPathBase);
		}
	} elseif ($delKind === 'exam_session_progress') {
		$examPath = basename(trim((string)($_POST['progress_path'] ?? '')));
		$examCode = trim((string)($_POST['progress_exam_code'] ?? ''));
		$examName = trim((string)($_POST['progress_name'] ?? ''));
		if ($examPath !== '' && function_exists('rkrk_assess_exam_delete_in_progress_file')) {
			$ok = rkrk_assess_exam_delete_in_progress_file($examPath);
		}
		if ($ok && $examName !== '' && function_exists('rkrk_assess_quiz_progress_delete_best_effort')) {
			rkrk_assess_quiz_progress_delete_best_effort(array(
				'quiz_key' => trim((string)($_POST['progress_quiz_key'] ?? '')),
				'name' => $examName,
				'exam_code' => $examCode,
			));
		}
	} elseif ($delKind === 'quiz_progress') {
		$quizPath = basename(trim((string)($_POST['progress_path'] ?? '')));
		if ($quizPath !== '' && function_exists('rkrk_assess_quiz_progress_delete_by_path')) {
			$ok = rkrk_assess_quiz_progress_delete_by_path($quizPath);
		}
		if (!$ok && function_exists('rkrk_assess_quiz_progress_delete')) {
			$ok = rkrk_assess_quiz_progress_delete(array(
				'quiz_key' => trim((string)($_POST['progress_quiz_key'] ?? '')),
				'name' => trim((string)($_POST['progress_name'] ?? '')),
				'exam_code' => trim((string)($_POST['progress_exam_code'] ?? '')),
				'drone' => trim((string)($_POST['progress_drone'] ?? '')),
				'schulung' => trim((string)($_POST['progress_schulung'] ?? '')),
				'quiz_modus' => trim((string)($_POST['progress_quiz_modus'] ?? '')),
			));
		}
	}
	$retQ = preg_replace('/([?&])deleted=[^&]*&?/', '$1', $retQ);
	$retQ = preg_replace('/\?&/', '?', $retQ);
	$retQ = rtrim($retQ, '&');
	$sep = (strpos($retQ, '?') !== false) ? '&' : '?';
	$suffix = $ok ? 'deleted=1' : 'delete_err=1';
	$loc = rkrk_assess_append_setting_href('rkrk_assess_admin.php' . $retQ . $sep . $suffix);
	while (function_exists('ob_get_level') && ob_get_level() > 0) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Location: ' . $loc, true, 302);
		exit;
	}
	$locEsc = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $locEsc . '"><title>Weiterleitung</title></head><body><p>Weiterleitung… <a href="' . $locEsc . '">hier klicken</a>, falls die Seite nicht automatisch wechselt.</p></body></html>';
	exit;
}

if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& !empty($_POST['exam_admin_archive'])
	&& $canUseExtendedAdmin
	&& $isAdminMode
) {
	$retRaw = isset($_POST['return_q']) ? (string)$_POST['return_q'] : '';
	parse_str(ltrim($retRaw, '?'), $retParsed);
	if (!is_array($retParsed)) {
		$retParsed = array();
	}
	$retQ = exam_admin_whitelist_admin_query($retParsed);
	$rawItems = $_POST['archive_items'] ?? array();
	if (!is_array($rawItems)) {
		$rawItems = array($rawItems);
	}
	$archived = 0;
	$archiveErr = 0;
	$managerIds = array();
	$managerSnapshots = array();
	foreach ($rawItems as $rawItem) {
		$decoded = null;
		if (is_string($rawItem) && trim($rawItem) !== '') {
			$decoded = json_decode($rawItem, true);
		} elseif (is_array($rawItem)) {
			$decoded = $rawItem;
		}
		if (!is_array($decoded)) {
			$archiveErr++;
			continue;
		}
		$mid = trim((string)($decoded['manager_id'] ?? ''));
		if ($mid !== '') {
			$managerIds[$mid] = true;
		}
		$result = function_exists('rkrk_assess_archive_item')
			? rkrk_assess_archive_item($decoded)
			: array('ok' => false);
		if (!empty($result['ok']) || $mid !== '') {
			$archived++;
		} else {
			$archiveErr++;
		}
	}
	if (!empty($managerIds) && isset($QuizLoc) && trim((string)$QuizLoc) !== '') {
		$mgrHeaders = array('Id','SeriesId','SeriesTitle','Person','Source','QuizKey','TestType','TestRef','PlannedAt','DoneAt','TheoryPassed','PracticalPassed','ResultStatus','Note');
		$mgrPath = rtrim((string)$QuizLoc, "/\\") . DIRECTORY_SEPARATOR . 'AssessmentManager.csv';
		$mgrRows = function_exists('exam_admin_read_csv_rows')
			? exam_admin_read_csv_rows($mgrPath, $mgrHeaders)
			: array();
		$kept = array();
		foreach ((array)$mgrRows as $mgrRow) {
			$id = trim((string)($mgrRow['Id'] ?? ''));
			if ($id !== '' && isset($managerIds[$id])) {
				$mgrRow['_archived_at'] = date('c');
				$managerSnapshots[] = $mgrRow;
				continue;
			}
			$kept[] = $mgrRow;
		}
		if (count($kept) !== count($mgrRows) && function_exists('exam_admin_write_csv_rows')) {
			exam_admin_write_csv_rows($mgrPath, $mgrHeaders, $kept);
		}
		if ($managerSnapshots !== array() && function_exists('rkrk_assess_archive_bucket_dir')) {
			$destDir = rkrk_assess_archive_bucket_dir('manager', 'assess');
			if ($destDir !== '') {
				rkrk_assess_archive_append_json_list($destDir . 'AssessmentManager.json', $managerSnapshots);
			}
		}
	}
	$retQ = preg_replace('/([?&])archived=[^&]*&?/', '$1', $retQ);
	$retQ = preg_replace('/([?&])archive_err=[^&]*&?/', '$1', $retQ);
	$retQ = preg_replace('/\?&/', '?', $retQ);
	$retQ = rtrim($retQ, '&');
	$sep = (strpos($retQ, '?') !== false) ? '&' : '?';
	$suffix = ($archived > 0) ? ('archived=' . (int)$archived) : 'archive_err=1';
	if ($archived > 0 && $archiveErr > 0) {
		$suffix .= '&archive_err=' . (int)$archiveErr;
	}
	$loc = rkrk_assess_append_setting_href('rkrk_assess_admin.php' . $retQ . $sep . $suffix);
	while (function_exists('ob_get_level') && ob_get_level() > 0) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Location: ' . $loc, true, 302);
		exit;
	}
	$locEsc = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $locEsc . '"><title>Weiterleitung</title></head><body><p>Weiterleitung… <a href="' . $locEsc . '">hier klicken</a>, falls die Seite nicht automatisch wechselt.</p></body></html>';
	exit;
}

if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& !empty($_POST['exam_admin_bulk_delete'])
	&& $canUseExtendedAdmin
	&& $isAdminMode
) {
	$retRaw = isset($_POST['return_q']) ? (string)$_POST['return_q'] : '';
	parse_str(ltrim($retRaw, '?'), $retParsed);
	if (!is_array($retParsed)) {
		$retParsed = array();
	}
	$retQ = exam_admin_whitelist_admin_query($retParsed);
	$rawItems = $_POST['delete_items'] ?? array();
	if (!is_array($rawItems)) {
		$rawItems = array($rawItems);
	}
	$deleted = 0;
	$deleteErr = 0;
	$managerIds = array();
	foreach ($rawItems as $rawItem) {
		$decoded = null;
		if (is_string($rawItem) && trim($rawItem) !== '') {
			$decoded = json_decode($rawItem, true);
		} elseif (is_array($rawItem)) {
			$decoded = $rawItem;
		}
		if (!is_array($decoded)) {
			$deleteErr++;
			continue;
		}
		$mid = trim((string)($decoded['manager_id'] ?? ''));
		if ($mid !== '') {
			$managerIds[$mid] = true;
		}
		$okItem = function_exists('exam_admin_delete_item')
			? exam_admin_delete_item($decoded)
			: false;
		if ($okItem || $mid !== '') {
			$deleted++;
		} else {
			$deleteErr++;
		}
	}
	if (!empty($managerIds) && isset($QuizLoc) && trim((string)$QuizLoc) !== '') {
		$mgrHeaders = array('Id','SeriesId','SeriesTitle','Person','Source','QuizKey','TestType','TestRef','PlannedAt','DoneAt','TheoryPassed','PracticalPassed','ResultStatus','Note');
		$mgrPath = rtrim((string)$QuizLoc, "/\\") . DIRECTORY_SEPARATOR . 'AssessmentManager.csv';
		$mgrRows = function_exists('exam_admin_read_csv_rows')
			? exam_admin_read_csv_rows($mgrPath, $mgrHeaders)
			: array();
		$kept = array();
		foreach ((array)$mgrRows as $mgrRow) {
			$id = trim((string)($mgrRow['Id'] ?? ''));
			if ($id !== '' && isset($managerIds[$id])) {
				continue;
			}
			$kept[] = $mgrRow;
		}
		if (count($kept) !== count($mgrRows) && function_exists('exam_admin_write_csv_rows')) {
			exam_admin_write_csv_rows($mgrPath, $mgrHeaders, $kept);
		}
	}
	$retQ = preg_replace('/([?&])deleted=[^&]*&?/', '$1', $retQ);
	$retQ = preg_replace('/([?&])delete_err=[^&]*&?/', '$1', $retQ);
	$retQ = preg_replace('/\?&/', '?', $retQ);
	$retQ = rtrim($retQ, '&');
	$sep = (strpos($retQ, '?') !== false) ? '&' : '?';
	$suffix = ($deleted > 0) ? ('deleted=' . (int)$deleted) : 'delete_err=1';
	if ($deleted > 0 && $deleteErr > 0) {
		$suffix .= '&delete_err=' . (int)$deleteErr;
	}
	$loc = rkrk_assess_append_setting_href('rkrk_assess_admin.php' . $retQ . $sep . $suffix);
	while (function_exists('ob_get_level') && ob_get_level() > 0) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Location: ' . $loc, true, 302);
		exit;
	}
	$locEsc = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $locEsc . '"><title>Weiterleitung</title></head><body><p>Weiterleitung… <a href="' . $locEsc . '">hier klicken</a>, falls die Seite nicht automatisch wechselt.</p></body></html>';
	exit;
}

rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
if ($onRKRS) {
	headerpage("RKRS Pruefungs-Admin", false, $folderoffset);
} else {
	headerpage("RKRK Pruefungs-Admin", false, $folderoffset);
}
if (function_exists('rkrk_assess_theme_rkrs_layer_link') && function_exists('rkrk_assess_ci_rkrs_mode') && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (empty($LoggedOn)) {
	if (function_exists('RegisterForm')) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

if (!isset($QuizLoc) || trim((string)$QuizLoc) === '') {
	throw new RuntimeException('QuizLoc fehlt in rkrk_assess_common.php');
}
if (!isset($QuizIconLoc) || trim((string)$QuizIconLoc) === '') {
	throw new RuntimeException('QuizIconLoc fehlt in rkrk_assess_common.php');
}
$CalcIconLoc = $QuizIconLoc;
$quizLogPath = rtrim((string)$QuizLocResults, "/\\") . '/' . rkrk_assess_quiz_results_json_basename();

if ($showTopMenu) {
	CreateTopMenu($folderoffset);
	echo "<br><br>";
	if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
		rkrk_assess_emit_admin_url_managers_menu_strip();
	}
}


function esc_html($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_key($v) {
    return strtolower(trim((string)$v));
}

function exam_admin_theory_modus_label(array $row): string {
	$norm = function_exists('rkrk_assess_quiz_modus_normalize')
		? rkrk_assess_quiz_modus_normalize((string)($row['QuizModus'] ?? ''))
		: strtolower(trim((string)($row['QuizModus'] ?? '')));
	if ($norm === 'pruefung') {
		return 'Prüfung';
	}
	return 'Übung';
}

function exam_admin_theory_modus_is_uebung(array $attempt): bool {
	return ((string)($attempt['modus_label'] ?? 'Übung')) === 'Übung';
}

function exam_admin_theory_modus_badge_html(array $attempt): string {
	$label = (string)($attempt['modus_label'] ?? 'Übung');
	$cls = exam_admin_theory_modus_is_uebung($attempt)
		? 'exam-theory-art exam-theory-art--uebung'
		: 'exam-theory-art exam-theory-art--pruefung';
	return '<span class="' . esc_html($cls) . '">' . esc_html($label) . '</span>';
}

function exam_admin_debug_log($runId, $hypothesisId, $location, $message, $data = array()) {
    $entry = array(
        'sessionId' => '1791ba',
        'runId' => (string)$runId,
        'hypothesisId' => (string)$hypothesisId,
        'location' => (string)$location,
        'message' => (string)$message,
        'data' => is_array($data) ? $data : array('value' => (string)$data),
        'timestamp' => round(microtime(true) * 1000),
    );
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $paths = array(
        __DIR__ . DIRECTORY_SEPARATOR . 'debug-1791ba.log',
        '\\\\tchr-frog.synology.me@ssl@5606\\DavWWWRoot\\web\\rkropen\\test\\exam\\debug-1791ba.log',
    );
    foreach ($paths as $path) {
        @file_put_contents($path, $line, FILE_APPEND);
    }
}

function parse_attempt_ts($dateText, $fallbackIndex) {
    $dateText = trim((string)$dateText);
    if ($dateText === '') {
        return $fallbackIndex;
    }
    $patterns = array('d.m.Y H:i', 'd.m.Y h:i', 'd.m.Y h:m');
    foreach ($patterns as $pattern) {
        $dt = DateTime::createFromFormat($pattern, $dateText);
        if ($dt instanceof DateTime) {
            return $dt->getTimestamp();
        }
    }
    $ts = strtotime($dateText);
    if ($ts !== false) {
        return $ts;
    }
    return $fallbackIndex;
}

/**
 * Findet die QuizResultate-Zeile zu einem abgeschlossenen Exam (EXAM-Code, Stem oder Quiz+Tag).
 *
 * @param array<int,array<string,mixed>> $attempts
 * @param array<string,mixed> $ex
 * @param array<int,bool> $usedIdx
 */
function exam_admin_find_attempt_index_for_exam(array $attempts, array $ex, array $usedIdx): int {
    $ek = mb_strtolower(trim((string)($ex['exam_code'] ?? '')), 'UTF-8');
    $nk = normalize_key((string)($ex['name'] ?? ''));
    $qk = mb_strtolower(trim((string)($ex['quiz_key'] ?? '')), 'UTF-8');
    $ts = (int)($ex['timestamp'] ?? 0);
    $day = ($ts > 0) ? date('Y-m-d', $ts) : '';
    $stem = trim((string)($ex['stem'] ?? ''));
    $best = -1;
    $bestScore = -1;
    foreach ($attempts as $i => $a) {
        if (isset($usedIdx[$i])) {
            continue;
        }
        if (normalize_key((string)($a['name'] ?? '')) !== $nk) {
            continue;
        }
        $score = 0;
        $aStem = trim((string)($a['exam_stem'] ?? ''));
        $aEk = mb_strtolower(trim((string)($a['exam_code'] ?? '')), 'UTF-8');
        $aQk = mb_strtolower(trim((string)($a['quiz'] ?? '')), 'UTF-8');
        $aTs = (int)($a['timestamp'] ?? 0);
        $aDay = ($aTs > 0) ? date('Y-m-d', $aTs) : '';
        if ($stem !== '' && $aStem !== '' && strcasecmp($aStem, $stem) === 0) {
            $score += 100;
        }
        if ($ek !== '' && $aEk !== '' && $aEk === $ek) {
            $score += 80;
        }
        if ($qk !== '' && $aQk === $qk && $day !== '' && $aDay === $day) {
            $score += 40;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = (int)$i;
        }
    }

    return ($bestScore >= 40) ? $best : -1;
}

/**
 * Übernimmt Score, Zeit und Stem aus dem Exam-JSON (Quelle für den PDF-Report).
 *
 * @param array<string,mixed> $a
 * @param array<string,mixed> $ex
 * @return array<string,mixed>
 */
function exam_admin_overlay_exam_on_attempt(array $a, array $ex): array {
    $merged = $ex;
    if (isset($ex['result']) && is_array($ex['result'])) {
        $merged = array_merge($ex['result'], $ex);
    }
    if (function_exists('rkrk_assess_theory_result_metrics')) {
        $m = rkrk_assess_theory_result_metrics($merged);
        $a['pct'] = (float)$m['pct'];
        $a['passed'] = !empty($m['passed']);
        $a['anz'] = ((int)$m['anz'] > 0) ? (int)$m['anz'] : (int)($ex['anz'] ?? ($a['anz'] ?? 0));
        $a['richtig'] = (int)$m['richtig'];
        if ((float)$m['factor'] > 0) {
            $a['factor'] = (float)$m['factor'];
        }
        $a['punkte_erreicht'] = (float)$m['pe'];
        $a['punkte_max'] = (float)$m['pm'];
        $a['use_points'] = !empty($m['use_points']);
        $a['punkte_modus'] = (string)($m['punkte_modus'] ?? 'standard');
    } else {
        $a['pct'] = (float)($ex['pct'] ?? ($a['pct'] ?? 0));
        $a['passed'] = !empty($ex['passed']);
        if (isset($ex['punkte_erreicht'])) {
            $a['punkte_erreicht'] = (float)$ex['punkte_erreicht'];
        }
        if (isset($ex['punkte_max'])) {
            $a['punkte_max'] = (float)$ex['punkte_max'];
            $a['use_points'] = ((float)$ex['punkte_max'] > 0);
        }
    }
    $zeit = trim((string)($ex['zeit'] ?? ''));
    if ($zeit !== '') {
        $a['date'] = $zeit;
    }
    $ts = (int)($ex['timestamp'] ?? 0);
    if ($ts > 0) {
        $a['timestamp'] = $ts;
    }
    $stem = trim((string)($ex['stem'] ?? ''));
    if ($stem !== '') {
        $a['exam_stem'] = $stem;
    }
    $code = trim((string)($ex['exam_code'] ?? ''));
    if ($code !== '') {
        $a['exam_code'] = $code;
    }
    $quiz = trim((string)($ex['quiz_key'] ?? ''));
    if ($quiz !== '' && trim((string)($a['quiz'] ?? '')) === '') {
        $a['quiz'] = $quiz;
    }
    $qm = trim((string)($ex['quiz_modus'] ?? ''));
    if ($qm !== '') {
        $a['quiz_modus'] = $qm;
        $a['modus_label'] = $qm;
    }
    $src = trim((string)($a['source'] ?? ''));
    $a['source'] = ($src === '' || $src === 'exams_json') ? 'exams_json' : ($src . '+exams_json');
    return $a;
}

function resolve_first_existing_file($paths) {
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return $paths[0];
}

function exam_admin_normalize_path($path) {
    if (function_exists('rkrk_normalize_rkropen_path')) {
        return rkrk_normalize_rkropen_path($path);
    }
    return rtrim(str_replace('\\', '/', (string)$path), '/') . '/';
}

function exam_admin_load_json_file($path) {
    if (!file_exists($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = @json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function exam_admin_read_csv_rows($path, $headers) {
    $rows = array();
    if (function_exists('csv_to_array_ascII')) {
        $rows = csv_to_array_ascII($path, ';');
        if (!is_array($rows)) $rows = array();
    }
    $out = array();
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $line = array();
        foreach ($headers as $h) {
            $line[$h] = isset($row[$h]) ? (string)$row[$h] : '';
        }
        $out[] = $line;
    }
    return $out;
}

function exam_admin_write_csv_rows($path, $headers, $rows) {
    $fh = @fopen($path, 'wb');
    if ($fh === false) return false;
    fputcsv($fh, $headers, ';');
    foreach ($rows as $row) {
        $line = array();
        foreach ($headers as $h) {
            $line[] = isset($row[$h]) ? (string)$row[$h] : '';
        }
        fputcsv($fh, $line, ';');
    }
    fclose($fh);
    return true;
}

/** Nur Dateiname-ID (Assessment/Live-Ergebnis-JSON), ohne Pfad. */
function exam_admin_safe_result_id($id): string {
    $id = basename((string)$id);
    if (!preg_match('/^[A-Za-z0-9._-]{1,160}$/', $id)) {
        return '';
    }
    return $id;
}

/**
 * Erlaubte GET-Parameter für Redirect nach Löschen (Planung oder Ergebnisübersicht).
 * @param array<string, string|array> $q
 */
function exam_admin_whitelist_admin_query(array $q): string {
	$page = trim((string)($q['page'] ?? ''));
	if ($page === 'planning') {
		$allowed = array('page', 'm_from', 'm_to', 'm_pilot', 'm_test', 'm_status', 'm_layout', 'm_kind');
	} else {
		$allowed = array(
			'page', 'quiz', 'name', 'status', 'theory_from', 'theory_to',
			'assessment_template', 'assessment_pilot', 'assessment_text', 'assessment_from', 'exam_to', 'exam_status',
		);
	}
	$out = array();
	foreach ($allowed as $k) {
		if (!isset($q[$k])) {
			continue;
		}
		$v = $q[$k];
		if (is_array($v)) {
			continue;
		}
		$vs = trim((string)$v);
		if ($vs === '' && $k !== 'page') {
			continue;
		}
		$out[$k] = $vs;
	}
	if (($out['page'] ?? '') !== 'planning' && ($out['page'] ?? '') !== 'results') {
		$out['page'] = 'results';
	}
	return '?' . http_build_query($out);
}

/**
 * @param array<string, string|array> $q
 */
function exam_admin_whitelist_results_query(array $q): string {
	if (!isset($q['page']) || trim((string)$q['page']) === '') {
		$q['page'] = 'results';
	}
	$parsed = array();
	parse_str(ltrim(exam_admin_whitelist_admin_query($q), '?'), $parsed);
	foreach (array('ADMIN', 'INSTR', 'RKRS', 'RKRSplus', 'RKRSprev', 'PersPick') as $carryKey) {
		if (!array_key_exists($carryKey, $q)) {
			continue;
		}
		$cv = $q[$carryKey];
		if (is_array($cv)) {
			continue;
		}
		$parsed[$carryKey] = (string)$cv;
	}
	return '?' . http_build_query($parsed);
}

/**
 * @param array<string, string|array> $get
 */
function exam_admin_planning_return_query(array $get): string {
	return exam_admin_whitelist_admin_query(array(
		'page' => 'planning',
		'm_from' => $get['m_from'] ?? '',
		'm_to' => $get['m_to'] ?? '',
		'm_pilot' => $get['m_pilot'] ?? '',
		'm_test' => $get['m_test'] ?? '',
		'm_status' => $get['m_status'] ?? 'all',
		'm_layout' => $get['m_layout'] ?? 'chips',
		'm_kind' => $get['m_kind'] ?? 'all',
	));
}

/**
 * Löschen-Formular für Resultat-Zeile in der Planungsübersicht.
 */
function exam_admin_result_delete_form_html(array $done, string $returnQ, bool $compact = false): string {
	$kind = trim((string)($done['delete_kind'] ?? ''));
	if ($kind === '') {
		return '';
	}
	$confirm = 'Dieses Resultat endgültig löschen?';
	$hidden = '<input type="hidden" name="exam_admin_delete" value="1">'
		. '<input type="hidden" name="delete_kind" value="' . esc_html($kind) . '">'
		. '<input type="hidden" name="return_q" value="' . esc_html($returnQ) . '">';
	if ($kind === 'theory_csv') {
		$li = (int)($done['csv_line_index'] ?? -1);
		if ($li < 0) {
			return '';
		}
		$confirm = 'Diesen Theorie-Eintrag aus QuizResultate.json endgültig löschen?';
		$hidden .= '<input type="hidden" name="csv_line_index" value="' . $li . '">'
			. '<input type="hidden" name="delete_name" value="' . esc_html((string)($done['delete_name'] ?? '')) . '">'
			. '<input type="hidden" name="delete_date" value="' . esc_html((string)($done['delete_date'] ?? '')) . '">'
			. '<input type="hidden" name="delete_quiz" value="' . esc_html((string)($done['delete_quiz'] ?? '')) . '">'
			. '<input type="hidden" name="exam_stem" value="' . esc_html((string)($done['exam_stem'] ?? '')) . '">';
	} elseif ($kind === 'exam_json') {
		$stem = basename(trim((string)($done['exam_stem'] ?? $done['progress_path'] ?? '')));
		$stem = preg_replace('/\.json$/i', '', $stem);
		if ($stem === '') {
			return '';
		}
		$confirm = 'Diese EXAM-Session inkl. Abschluss-PDF endgültig löschen?';
		$hidden .= '<input type="hidden" name="exam_stem" value="' . esc_html($stem) . '">'
			. '<input type="hidden" name="progress_path" value="' . esc_html($stem . '.json') . '">'
			. '<input type="hidden" name="progress_exam_code" value="' . esc_html((string)($done['exam_code'] ?? '')) . '">'
			. '<input type="hidden" name="progress_quiz_key" value="' . esc_html((string)($done['delete_quiz'] ?? $done['quiz_key'] ?? '')) . '">'
			. '<input type="hidden" name="progress_name" value="' . esc_html((string)($done['delete_name'] ?? $done['pilot_display'] ?? '')) . '">'
			. '<input type="hidden" name="delete_date" value="' . esc_html((string)($done['delete_date'] ?? $done['date'] ?? '')) . '">';
	} elseif ($kind === 'assessment_json') {
		$rid = exam_admin_safe_result_id((string)($done['result_id'] ?? ''));
		if ($rid === '') {
			return '';
		}
		$confirm = 'Diese Assessment-Datei endgültig löschen?';
		$hidden .= '<input type="hidden" name="result_id" value="' . esc_html($rid) . '">';
	} elseif ($kind === 'evaluation_json') {
		$rid = exam_admin_safe_result_id((string)($done['result_id'] ?? ''));
		if ($rid === '') {
			return '';
		}
		$confirm = 'Diese Beurteilungs-Datei endgültig löschen?';
		$hidden .= '<input type="hidden" name="result_id" value="' . esc_html($rid) . '">';
	} elseif ($kind === 'checklist_progress') {
		$progFile = basename(trim((string)($done['progress_file'] ?? '')));
		$progEdit = basename(trim((string)($done['progress_edit'] ?? '')));
		$progDraft = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($done['progress_draft_id'] ?? ''));
		$progPath = basename(trim((string)($done['progress_path'] ?? '')));
		if ($progFile === '' && $progPath === '') {
			return '';
		}
		$confirm = 'Diesen Assessment-Fortschritt (in progress) endgültig löschen?';
		$hidden .= '<input type="hidden" name="progress_file" value="' . esc_html($progFile) . '">'
			. '<input type="hidden" name="progress_edit" value="' . esc_html($progEdit) . '">'
			. '<input type="hidden" name="progress_draft_id" value="' . esc_html($progDraft) . '">'
			. '<input type="hidden" name="progress_path" value="' . esc_html($progPath) . '">';
	} elseif ($kind === 'exam_session_progress') {
		$progPath = basename(trim((string)($done['progress_path'] ?? '')));
		if ($progPath === '') {
			return '';
		}
		$confirm = 'Diese offene EXAM-Session (in progress) endgültig löschen?';
		$hidden .= '<input type="hidden" name="progress_path" value="' . esc_html($progPath) . '">'
			. '<input type="hidden" name="progress_exam_code" value="' . esc_html((string)($done['exam_code'] ?? '')) . '">'
			. '<input type="hidden" name="progress_quiz_key" value="' . esc_html((string)($done['quiz_key'] ?? '')) . '">'
			. '<input type="hidden" name="progress_name" value="' . esc_html((string)($done['pilot_display'] ?? $done['delete_name'] ?? '')) . '">';
	} elseif ($kind === 'quiz_progress') {
		$progPath = basename(trim((string)($done['progress_path'] ?? '')));
		$quizKey = trim((string)($done['quiz_key'] ?? ''));
		$progName = trim((string)($done['pilot_display'] ?? $done['delete_name'] ?? ''));
		if ($progPath === '' && ($quizKey === '' || $progName === '')) {
			return '';
		}
		$confirm = 'Diesen offenen Quiz-/Theorie-Fortschritt endgültig löschen?';
		$hidden .= '<input type="hidden" name="progress_path" value="' . esc_html($progPath) . '">'
			. '<input type="hidden" name="progress_quiz_key" value="' . esc_html($quizKey) . '">'
			. '<input type="hidden" name="progress_name" value="' . esc_html($progName) . '">'
			. '<input type="hidden" name="progress_exam_code" value="' . esc_html((string)($done['exam_code'] ?? '')) . '">'
			. '<input type="hidden" name="progress_drone" value="' . esc_html((string)($done['drone'] ?? '')) . '">'
			. '<input type="hidden" name="progress_schulung" value="' . esc_html((string)($done['schulung'] ?? '')) . '">'
			. '<input type="hidden" name="progress_quiz_modus" value="' . esc_html((string)($done['quiz_modus'] ?? '')) . '">';
	} else {
		return '';
	}
	$btnStyle = $compact
		? 'padding:0 5px;font-size:12px;line-height:1.3;border:1px solid #f5b5b5;background:#fee2e2;color:#8b1d1d;border-radius:4px;cursor:pointer;'
		: 'padding:4px 8px;font-size:12px;border:1px solid #f5b5b5;background:#fee2e2;color:#8b1d1d;border-radius:4px;cursor:pointer;';
	$label = $compact ? '×' : 'Löschen';
	$settingHidden = '';
	if (function_exists('rkrk_assess_echo_setting_hidden_input')) {
		ob_start();
		rkrk_assess_echo_setting_hidden_input();
		$settingHidden = (string)ob_get_clean();
	}
	return '<form method="post" style="margin:0;display:inline;" onsubmit="return confirm(' . json_encode($confirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');">'
		. $hidden
		. $settingHidden
		. '<button type="submit" class="ql-btn" style="' . esc_html($btnStyle) . '" title="Resultat löschen">' . esc_html($label) . '</button>'
		. '</form>';
}

/**
 * JSON-Payload für Mehrfach-Archivierung einer Kachel.
 *
 * @param array<string,mixed> $src
 * @return array<string,string>
 */
function exam_admin_archive_payload_from_entry(array $src, array $extra = array()): array {
	$kind = trim((string)($src['delete_kind'] ?? $src['kind'] ?? ''));
	$examStem = basename(trim((string)($src['exam_stem'] ?? $src['_exam_stem'] ?? '')));
	$examStem = preg_replace('/\.json$/i', '', $examStem);
	$progressPath = basename(trim((string)($src['progress_path'] ?? '')));
	if ($examStem === '' && $progressPath !== '' && preg_match('/\.json$/i', $progressPath) && ($kind === 'exam_json' || $kind === 'exam_session_progress')) {
		$examStem = preg_replace('/\.json$/i', '', $progressPath);
	}
	$payload = array(
		'kind' => $kind,
		'exam_stem' => (string)$examStem,
		'exam_code' => trim((string)($src['exam_code'] ?? $src['_exam_code'] ?? $src['progress_exam_code'] ?? '')),
		'result_id' => exam_admin_safe_result_id((string)($src['result_id'] ?? $src['_result_id'] ?? '')),
		'progress_path' => $progressPath,
		'progress_file' => basename(trim((string)($src['progress_file'] ?? ''))),
		'progress_edit' => basename(trim((string)($src['progress_edit'] ?? ''))),
		'progress_draft_id' => preg_replace('/[^A-Za-z0-9_-]/', '', (string)($src['progress_draft_id'] ?? '')),
		'csv_line_index' => (string)(int)($src['csv_line_index'] ?? -1),
		'delete_name' => trim((string)($src['delete_name'] ?? $src['pilot_display'] ?? $src['progress_name'] ?? $src['Person'] ?? '')),
		'delete_date' => trim((string)($src['delete_date'] ?? $src['date'] ?? $src['DoneAt'] ?? '')),
		'delete_quiz' => trim((string)($src['delete_quiz'] ?? $src['quiz_key'] ?? $src['progress_quiz_key'] ?? '')),
		'quiz_key' => trim((string)($src['quiz_key'] ?? $src['progress_quiz_key'] ?? $src['delete_quiz'] ?? '')),
		'progress_name' => trim((string)($src['progress_name'] ?? $src['pilot_display'] ?? $src['delete_name'] ?? '')),
		'progress_drone' => trim((string)($src['drone'] ?? $src['progress_drone'] ?? '')),
		'progress_schulung' => trim((string)($src['schulung'] ?? $src['progress_schulung'] ?? '')),
		'progress_quiz_modus' => trim((string)($src['quiz_modus'] ?? $src['progress_quiz_modus'] ?? '')),
		'manager_id' => trim((string)($src['manager_id'] ?? $src['Id'] ?? '')),
		'reports_kind' => ($kind === 'evaluation_json') ? 'live' : 'assess',
	);
	foreach ($extra as $k => $v) {
		if (!is_scalar($v) && $v !== null) {
			continue;
		}
		$payload[(string)$k] = trim((string)$v);
	}
	if ($payload['kind'] === '' && $payload['manager_id'] !== '') {
		$payload['kind'] = 'planned';
	}
	return $payload;
}

/**
 * @param array<string,mixed> $row AssessmentManager-Zeile inkl. _exam_stem/_result_id
 * @return array<string,string>
 */
function exam_admin_archive_payload_from_planned(array $row): array {
	$type = trim((string)($row['TestType'] ?? 'theory'));
	$kind = 'planned';
	$examStem = trim((string)($row['_exam_stem'] ?? ''));
	$resultId = trim((string)($row['_result_id'] ?? ''));
	if ($type === 'theory') {
		$kind = ($examStem !== '') ? 'exam_json' : 'theory_csv';
	} elseif ($type === 'evaluation_template') {
		$kind = 'evaluation_json';
	} elseif ($type === 'assessment_template') {
		$kind = 'assessment_json';
	}
	$quizRef = ($type === 'theory')
		? trim((string)($row['TestRef'] ?? $row['QuizKey'] ?? ''))
		: '';
	return exam_admin_archive_payload_from_entry($row, array(
		'kind' => $kind,
		'manager_id' => trim((string)($row['Id'] ?? '')),
		'exam_stem' => $examStem,
		'exam_code' => trim((string)($row['_exam_code'] ?? '')),
		'result_id' => $resultId,
		'delete_name' => trim((string)($row['Person'] ?? '')),
		'delete_date' => trim((string)($row['DoneAt'] ?? '')),
		'delete_quiz' => $quizRef,
		'quiz_key' => $quizRef,
		'reports_kind' => ($type === 'evaluation_template') ? 'live' : 'assess',
	));
}

function exam_admin_archive_checkbox_html(array $payload): string {
	$kind = trim((string)($payload['kind'] ?? ''));
	$hasId = trim((string)($payload['manager_id'] ?? '')) !== ''
		|| trim((string)($payload['exam_stem'] ?? '')) !== ''
		|| trim((string)($payload['result_id'] ?? '')) !== ''
		|| trim((string)($payload['progress_path'] ?? '')) !== ''
		|| ((int)($payload['csv_line_index'] ?? -1) >= 0)
		|| (trim((string)($payload['delete_name'] ?? '')) !== '' && trim((string)($payload['delete_quiz'] ?? '')) !== '');
	if ($kind === '' && !$hasId) {
		return '';
	}
	$json = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
	return '<label class="mgr-tile-select" title="Zum Archivieren oder Löschen auswählen" onclick="event.stopPropagation();">'
		. '<input type="checkbox" class="mgr-archive-cb" value="' . $json . '" aria-label="Kachel auswählen">'
		. '</label>';
}

function exam_admin_json_result_realpath(string $baseDir, string $id): ?string {
    $id = exam_admin_safe_result_id($id);
    if ($id === '') {
        return null;
    }
    $base = realpath(rtrim(str_replace('\\', DIRECTORY_SEPARATOR, $baseDir), DIRECTORY_SEPARATOR));
    if ($base === false || !is_dir($base)) {
        return null;
    }
    $candidate = $base . DIRECTORY_SEPARATOR . $id . '.json';
    $rp = realpath($candidate);
    if ($rp === false || !is_file($rp)) {
        return null;
    }
    if (strpos($rp, $base) !== 0) {
        return null;
    }
    return $rp;
}

/**
 * Eine Theorie-Zeile aus QuizResultate.json entfernen (0-basierter Index in der «rows»-Liste).
 */
function exam_admin_delete_theory_quizlog_row(string $quizResultsPath, int $lineIndex): bool {
	return exam_admin_delete_theory_quizlog_row_match(
		dirname(trim((string)$quizResultsPath)),
		$lineIndex,
		array()
	);
}

function exam_admin_theory_row_matches(array $row, array $match): bool {
	$name = trim((string)($match['name'] ?? ''));
	$date = trim((string)($match['date'] ?? ''));
	$quiz = trim((string)($match['quiz'] ?? ''));
	if ($name === '' && $date === '' && $quiz === '') {
		return false;
	}
	if ($name !== '' && normalize_key(trim((string)($row['Name'] ?? ''))) !== normalize_key($name)) {
		return false;
	}
	if ($date !== '' && trim((string)($row['Zeit'] ?? '')) !== $date) {
		return false;
	}
	if ($quiz !== '' && trim((string)($row['Quiz'] ?? '')) !== $quiz) {
		return false;
	}
	return true;
}

function exam_admin_delete_theory_quizlog_row_match(string $resultsDir, int $lineIndex, array $match): bool {
	$dir = trim((string)$resultsDir);
	if ($dir === '' || !is_dir($dir)) {
		return false;
	}
	if (!function_exists('rkrk_assess_quiz_results_load_rows') || !function_exists('rkrk_assess_quiz_results_save_rows')) {
		return false;
	}
	$data = rkrk_assess_quiz_results_load_rows($dir);
	if (!is_array($data)) {
		return false;
	}
	$idx = -1;
	if ($lineIndex >= 0 && $lineIndex < count($data)) {
		if (empty($match) || exam_admin_theory_row_matches($data[$lineIndex], $match)) {
			$idx = $lineIndex;
		}
	}
	if ($idx < 0 && !empty($match)) {
		foreach ($data as $i => $row) {
			if (!is_array($row)) {
				continue;
			}
			if (exam_admin_theory_row_matches($row, $match)) {
				$idx = (int)$i;
				break;
			}
		}
	}
	if ($idx < 0) {
		return false;
	}
	array_splice($data, $idx, 1);
	return rkrk_assess_quiz_results_save_rows($dir, $data);
}

/**
 * Löscht Dateien/Zeilen zu einer Kachel (gleiche Payload-Felder wie Archivieren).
 *
 * @param array<string,mixed> $item
 */
function exam_admin_delete_item(array $item): bool {
	$kind = strtolower(trim((string)($item['kind'] ?? $item['delete_kind'] ?? '')));
	$ok = false;
	$resultsDir = '';
	if (isset($GLOBALS['QuizLocResults']) && trim((string)$GLOBALS['QuizLocResults']) !== '') {
		$resultsDir = rtrim((string)$GLOBALS['QuizLocResults'], "/\\");
	}
	$examStem = basename(trim((string)($item['exam_stem'] ?? '')));
	$examStem = preg_replace('/\.json$/i', '', (string)$examStem);
	if ($examStem === '' && ($kind === 'exam_json' || $kind === 'exam_session_progress')) {
		$examStem = preg_replace('/\.json$/i', '', basename(trim((string)($item['progress_path'] ?? ''))));
	}
	$resultId = exam_admin_safe_result_id((string)($item['result_id'] ?? ''));
	$progressPath = basename(trim((string)($item['progress_path'] ?? '')));
	$name = trim((string)($item['delete_name'] ?? $item['progress_name'] ?? ''));
	$date = trim((string)($item['delete_date'] ?? ''));
	$quiz = trim((string)($item['delete_quiz'] ?? $item['quiz_key'] ?? $item['progress_quiz_key'] ?? ''));
	$examCode = trim((string)($item['exam_code'] ?? $item['progress_exam_code'] ?? ''));
	$reportsKind = strtolower(trim((string)($item['reports_kind'] ?? ''))) === 'live' ? 'live' : 'assess';

	if ($kind === 'theory_csv') {
		if ($resultsDir !== '') {
			$ok = exam_admin_delete_theory_quizlog_row_match(
				$resultsDir,
				(int)($item['csv_line_index'] ?? -1),
				array('name' => $name, 'date' => $date, 'quiz' => $quiz)
			);
		}
		if ($examStem !== '' && function_exists('rkrk_assess_exam_delete_file')) {
			rkrk_assess_exam_delete_file($examStem . '.json', false);
		} elseif ($examStem !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			rkrk_assess_report_delete_by_stem($examStem, 'assess');
		}
	} elseif ($kind === 'exam_json' || $kind === 'exam_session_progress') {
		if ($kind === 'exam_session_progress' && $progressPath !== '' && function_exists('rkrk_assess_exam_delete_in_progress_file')) {
			$ok = rkrk_assess_exam_delete_in_progress_file($progressPath);
		} elseif ($examStem !== '' && function_exists('rkrk_assess_exam_delete_file')) {
			$ok = rkrk_assess_exam_delete_file($examStem . '.json', $kind === 'exam_session_progress');
		}
		$reportRemoved = 0;
		if ($examStem !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			$reportRemoved = rkrk_assess_report_delete_by_stem($examStem, 'assess');
		}
		if (!$ok && $reportRemoved > 0) {
			$ok = true;
		}
		if ($ok && $resultsDir !== '' && ($name !== '' || $quiz !== '')) {
			exam_admin_delete_theory_quizlog_row_match(
				$resultsDir,
				-1,
				array('name' => $name, 'date' => $date, 'quiz' => $quiz)
			);
		}
		if ($ok && $name !== '' && function_exists('rkrk_assess_quiz_progress_delete_best_effort')) {
			rkrk_assess_quiz_progress_delete_best_effort(array(
				'quiz_key' => $quiz,
				'name' => $name,
				'exam_code' => $examCode,
			));
		}
	} elseif ($kind === 'assessment_json') {
		if ($resultId !== '' && isset($GLOBALS['rkrkAssessResults']) && trim((string)$GLOBALS['rkrkAssessResults']) !== '') {
			$rp = exam_admin_json_result_realpath((string)$GLOBALS['rkrkAssessResults'], $resultId);
			if ($rp !== null) {
				$ok = @unlink($rp);
			}
		}
		if ($resultId !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			$n = rkrk_assess_report_delete_by_stem($resultId, 'assess');
			if ($n > 0) {
				$ok = true;
			}
		}
	} elseif ($kind === 'evaluation_json') {
		if ($resultId !== '' && isset($GLOBALS['rkrkLiveassessResults']) && trim((string)$GLOBALS['rkrkLiveassessResults']) !== '') {
			$rp = exam_admin_json_result_realpath((string)$GLOBALS['rkrkLiveassessResults'], $resultId);
			if ($rp !== null) {
				$ok = @unlink($rp);
			}
		}
		if ($resultId !== '' && function_exists('rkrk_assess_report_delete_by_stem')) {
			$n = rkrk_assess_report_delete_by_stem($resultId, 'live');
			if ($n > 0) {
				$ok = true;
			}
		}
	} elseif ($kind === 'checklist_progress') {
		$progFile = basename(trim((string)($item['progress_file'] ?? '')));
		$progEdit = basename(trim((string)($item['progress_edit'] ?? '')));
		$progDraft = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($item['progress_draft_id'] ?? ''));
		if ($progFile !== '' && ($progEdit !== '' || $progDraft !== '') && function_exists('rkrk_assess_checklist_progress_delete')) {
			$ok = rkrk_assess_checklist_progress_delete(array(
				'kind' => 'assess',
				'file' => $progFile,
				'edit' => $progEdit,
				'draft_id' => $progDraft,
			));
		}
		if (!$ok && $progressPath !== '' && function_exists('rkrk_assess_checklist_progress_delete_by_path')) {
			$ok = rkrk_assess_checklist_progress_delete_by_path('assess', $progressPath);
		}
	} elseif ($kind === 'quiz_progress') {
		if ($progressPath !== '' && function_exists('rkrk_assess_quiz_progress_delete_by_path')) {
			$ok = rkrk_assess_quiz_progress_delete_by_path($progressPath);
		}
		if (!$ok && function_exists('rkrk_assess_quiz_progress_delete')) {
			$ok = rkrk_assess_quiz_progress_delete(array(
				'quiz_key' => trim((string)($item['quiz_key'] ?? $item['progress_quiz_key'] ?? '')),
				'name' => $name,
				'exam_code' => $examCode,
				'drone' => trim((string)($item['drone'] ?? $item['progress_drone'] ?? '')),
				'schulung' => trim((string)($item['schulung'] ?? $item['progress_schulung'] ?? '')),
				'quiz_modus' => trim((string)($item['quiz_modus'] ?? $item['progress_quiz_modus'] ?? '')),
			));
		}
	} elseif ($kind === 'planned') {
		if ($examStem !== '' && function_exists('rkrk_assess_exam_delete_file')) {
			$ok = rkrk_assess_exam_delete_file($examStem . '.json', false) || $ok;
		}
		if ($resultId !== '') {
			$base = ($reportsKind === 'live')
				? (isset($GLOBALS['rkrkLiveassessResults']) ? (string)$GLOBALS['rkrkLiveassessResults'] : '')
				: (isset($GLOBALS['rkrkAssessResults']) ? (string)$GLOBALS['rkrkAssessResults'] : '');
			if (trim($base) !== '') {
				$rp = exam_admin_json_result_realpath($base, $resultId);
				if ($rp !== null) {
					$ok = @unlink($rp) || $ok;
				}
			}
			if (function_exists('rkrk_assess_report_delete_by_stem')) {
				$n = rkrk_assess_report_delete_by_stem($resultId, $reportsKind);
				if ($n > 0) {
					$ok = true;
				}
			}
		}
		if ($resultsDir !== '' && ($name !== '' || $quiz !== '')) {
			if (exam_admin_delete_theory_quizlog_row_match($resultsDir, (int)($item['csv_line_index'] ?? -1), array(
				'name' => $name, 'date' => $date, 'quiz' => $quiz,
			))) {
				$ok = true;
			}
		}
	}

	if (!$ok && trim((string)($item['manager_id'] ?? '')) !== '') {
		return true;
	}
	return $ok;
}

function exam_admin_load_template($templatesDir, $tplName) {
    $tplName = trim((string)$tplName);
    if ($tplName === '') return null;

    $candidates = array(
        $templatesDir . '/' . $tplName,
        $templatesDir . '/' . $tplName . '.json',
    );
    foreach ($candidates as $path) {
        $tpl = exam_admin_load_json_file($path);
        if (is_array($tpl)) return $tpl;
    }
    return null;
}

function exam_admin_format_datetime($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';

    $patterns = array('Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i');
    foreach ($patterns as $pattern) {
        $dt = DateTime::createFromFormat($pattern, $raw);
        if ($dt instanceof DateTime) {
            return ($dt->format('H:i') === '00:00') ? $dt->format('d.m.Y') : $dt->format('d.m.Y H:i');
        }
    }
    $ts = strtotime($raw);
    if ($ts !== false) {
        return (date('H:i', $ts) === '00:00') ? date('d.m.Y', $ts) : date('d.m.Y H:i', $ts);
    }
    return $raw;
}

function exam_admin_value_to_text($value) {
    if (is_array($value)) return implode('; ', array_map('strval', $value));
    return trim((string)$value);
}

function exam_admin_strip_markers($text) {
    $text = (string)$text;
    $text = preg_replace('/\s*\(([rgo])\)\s*/iu', ' ', $text);
    $text = preg_replace('/\s{2,}/u', ' ', $text);
    return trim((string)$text);
}

function exam_admin_is_material_assessment($title, $tplFile = '') {
    $hay = mb_strtolower(trim((string)$title . ' ' . (string)$tplFile), 'UTF-8');
    if ($hay === '') {
        return false;
    }
    return (strpos($hay, 'material') !== false);
}

function exam_admin_norm_search_hay($a, $b = '') {
    $s = mb_strtolower(trim((string)$a . ' ' . (string)$b), 'UTF-8');
    if ($s === '') {
        return '';
    }
    return strtr($s, array(
        'ä' => 'a', 'ö' => 'o', 'ü' => 'u',
        'Ä' => 'a', 'Ö' => 'o', 'Ü' => 'u',
    ));
}

function exam_admin_is_ep_theory_ref($ref, $title = '') {
    $h = exam_admin_norm_search_hay($ref, $title);
    if ($h === '') {
        return false;
    }
    if (strpos($h, 'rkrsp-ep') !== false) {
        return true;
    }
    return (strpos($h, 'einsatzpr') !== false && strpos($h, 'material') === false);
}

function exam_admin_is_einsatzpruefung_assessment($title, $tplFile = '') {
    if (exam_admin_is_material_assessment($title, $tplFile)) {
        return false;
    }
    $h = exam_admin_norm_search_hay($title, $tplFile);
    return ($h !== '' && strpos($h, 'einsatzpr') !== false);
}

function exam_admin_theory_is_exam_modus($modusLabel, $quizModus = '') {
    $ml = trim((string)$modusLabel);
    if ($ml === 'Prüfung' || strcasecmp($ml, 'pruefung') === 0 || strcasecmp($ml, 'prüfung') === 0) {
        return true;
    }
    $raw = trim((string)$quizModus);
    $norm = function_exists('rkrk_assess_quiz_modus_normalize')
        ? rkrk_assess_quiz_modus_normalize($raw)
        : strtolower($raw);
    return ($norm === 'pruefung');
}

/**
 * RKRS+ prüfungsrelevant: Theorie im Prüfungsmodus (EP-Quiz), Einsatzprüfung, Materialprüfung.
 *
 * @param array<string,mixed> $ctx
 */
function exam_admin_is_rkrsplus_exam_relevant(array $ctx): bool {
    $type = trim((string)($ctx['type'] ?? ''));
    $label = (string)($ctx['label'] ?? '');
    $ref = trim((string)($ctx['ref'] ?? $ctx['quiz_key'] ?? ''));
    $isMat = !empty($ctx['is_material']) || exam_admin_is_material_assessment($label, $ref);
    if ($isMat) {
        return true;
    }
    if ($type === 'theory' || $type === 'quiz_progress') {
        if (!exam_admin_is_ep_theory_ref($ref, $label)) {
            return false;
        }
        if (!empty($ctx['planned_only'])) {
            return true;
        }
        if ($type === 'quiz_progress') {
            $qm = trim((string)($ctx['quiz_modus'] ?? ''));
            $ml = trim((string)($ctx['modus_label'] ?? ''));
            if ($qm === '' && $ml === '') {
                return true;
            }
            return exam_admin_theory_is_exam_modus($ml, $qm);
        }
        return exam_admin_theory_is_exam_modus((string)($ctx['modus_label'] ?? ''), (string)($ctx['quiz_modus'] ?? ''));
    }
    if ($type === 'assessment_template' || $type === 'practical' || $type === 'assessment_progress') {
        return exam_admin_is_einsatzpruefung_assessment($label, $ref);
    }
    return false;
}

function exam_admin_is_empfehlung_label($label) {
    $l = trim((string)$label);
    if ($l === '') {
        return false;
    }
    if (preg_match('/einsatz\s*empfehlung|einsatzempfehlung/ui', $l) === 1) {
        return true;
    }
    return (preg_match('/^empfehlung$/ui', $l) === 1);
}

function exam_admin_find_empfehlung_value($fields, $sections = array()) {
    if (!is_array($fields)) {
        return '';
    }
    if (is_array($sections)) {
        foreach ($sections as $secName => $secFields) {
            if (!is_array($secFields)) {
                continue;
            }
            foreach ($secFields as $label => $meta) {
                if (!exam_admin_is_empfehlung_label($label)) {
                    continue;
                }
                $val = isset($fields[$secName][$label]) ? exam_admin_value_to_text($fields[$secName][$label]) : '';
                if (trim((string)$val) !== '') {
                    return trim((string)$val);
                }
            }
        }
    }
    foreach ($fields as $secFields) {
        if (!is_array($secFields)) {
            continue;
        }
        foreach ($secFields as $label => $rawVal) {
            if (!exam_admin_is_empfehlung_label($label)) {
                continue;
            }
            $val = exam_admin_value_to_text($rawVal);
            if (trim((string)$val) !== '') {
                return trim((string)$val);
            }
        }
    }
    return '';
}

function exam_admin_empfehlung_marker($raw) {
    $v = trim((string)$raw);
    if ($v === '') {
        return '';
    }
    if (preg_match('/\(([gor])\)/i', $v, $m) === 1) {
        return strtolower((string)$m[1]);
    }
    $vl = mb_strtolower(exam_admin_strip_markers($v), 'UTF-8');
    if ($vl === '') {
        return '';
    }
    if (strpos($vl, 'einschr') !== false || strpos($vl, 'teilweise') !== false) {
        return 'o';
    }
    if (
        strpos($vl, 'nicht einsatzbereit') !== false
        || strpos($vl, 'nicht einsetzbar') !== false
        || strpos($vl, 'nicht als pilot') !== false
        || strpos($vl, 'material nicht') !== false
        || (strpos($vl, 'nicht') !== false && strpos($vl, 'bestanden') !== false)
    ) {
        return 'r';
    }
    if (
        (strpos($vl, 'als pilot einsetzbar') !== false && strpos($vl, 'nicht') === false)
        || strpos($vl, 'material komplett') !== false
        || $vl === 'ja' || $vl === 'ok' || $vl === 'bestanden' || $vl === 'pass'
    ) {
        return 'g';
    }
    return '';
}

function exam_admin_empfehlung_colors($raw) {
    $m = exam_admin_empfehlung_marker($raw);
    if ($m === 'g') {
        return array('bg' => '#16a34a', 'fg' => '#ffffff', 'code' => 'emp_g', 'marker' => 'g');
    }
    if ($m === 'o') {
        return array('bg' => '#f59e0b', 'fg' => '#111827', 'code' => 'emp_o', 'marker' => 'o');
    }
    if ($m === 'r') {
        return array('bg' => '#dc2626', 'fg' => '#ffffff', 'code' => 'emp_r', 'marker' => 'r');
    }
    return array('bg' => '', 'fg' => '', 'code' => '', 'marker' => '');
}

function exam_admin_find_first_field_value($fields, $labels) {
    if (!is_array($fields)) return '';
    foreach ($labels as $label) {
        if (isset($fields['Allgemein']) && is_array($fields['Allgemein']) && isset($fields['Allgemein'][$label])) {
            $val = trim((string)$fields['Allgemein'][$label]);
            if ($val !== '') return $val;
        }
    }
    foreach ($fields as $secFields) {
        if (!is_array($secFields)) continue;
        foreach ($labels as $label) {
            if (!isset($secFields[$label])) continue;
            $val = trim((string)$secFields[$label]);
            if ($val !== '') return $val;
        }
    }
    // Präfix-/Teilmatch: z. B. «Gesamtstufe (nach Pilot V01)» für Label «Gesamtstufe»
    foreach ($labels as $label) {
        $want = mb_strtolower(trim((string)$label), 'UTF-8');
        if ($want === '') continue;
        foreach ($fields as $secFields) {
            if (!is_array($secFields)) continue;
            foreach ($secFields as $lab => $raw) {
                $have = mb_strtolower(trim((string)$lab), 'UTF-8');
                if ($have === $want || strpos($have, $want) === 0) {
                    $val = trim((string)$raw);
                    if ($val !== '') return $val;
                }
            }
        }
    }
    return '';
}

function exam_admin_parse_ts($raw, $fallback = 0) {
    $raw = trim((string)$raw);
    if ($raw === '') return (int)$fallback;
    $ts = strtotime(str_replace('T', ' ', $raw));
    return ($ts === false) ? (int)$fallback : (int)$ts;
}

function exam_admin_rating_badge($value) {
    $rawValue = trim((string)$value);
    if ($rawValue === '') return '';

    $ec = exam_admin_empfehlung_colors($rawValue);
    $bg = '#e5e7eb';
    $fg = '#374151';
    if ((string)($ec['bg'] ?? '') !== '') {
        $bg = (string)$ec['bg'];
        $fg = (string)$ec['fg'];
    } elseif (preg_match('/\(r\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'nicht', 0, 'UTF-8') !== false || mb_stripos($rawValue, 'fail', 0, 'UTF-8') !== false) {
        $bg = '#dc2626';
        $fg = '#ffffff';
    } elseif (preg_match('/\(o\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'warn', 0, 'UTF-8') !== false) {
        $bg = '#f59e0b';
        $fg = '#111827';
    } elseif (preg_match('/\(g\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'bestanden', 0, 'UTF-8') !== false || mb_stripos($rawValue, 'ok', 0, 'UTF-8') !== false) {
        $bg = '#16a34a';
        $fg = '#ffffff';
    }

    $value = exam_admin_strip_markers($rawValue);

    return "<span style='display:inline-block;padding:3px 8px;border-radius:999px;background:" . esc_html($bg) . ";color:" . esc_html($fg) . ";font-weight:700;'>" . esc_html($value) . "</span>";
}

$quizListPath = rtrim((string)$QuizLoc, "/\\") . '/AssessmentListe.csv';

$quizRows = csv_to_array_ascII($quizListPath, ';');
if (!is_array($quizRows)) {
    $quizRows = array();
}
$quizTitleByKey = array();
$quizPunkteModusByKey = array();
foreach ($quizRows as $q) {
    $k = trim((string)(isset($q['Key']) ? $q['Key'] : ''));
    if ($k === '') continue;
    $quizTitleByKey[$k] = trim((string)(isset($q['Titel']) ? $q['Titel'] : $k));
    $quizPunkteModusByKey[$k] = function_exists('rkrk_assess_punkte_modus_normalize')
        ? rkrk_assess_punkte_modus_normalize($q['PunkteModus'] ?? '')
        : 'standard';
}

$rows = function_exists('rkrk_assess_quiz_results_load_rows')
    ? rkrk_assess_quiz_results_load_rows(rtrim((string)$QuizLocResults, "/\\"))
    : array();
if (!is_array($rows)) {
    $rows = array();
}

$attempts = array();
$idx = 0;
foreach ($rows as $row) {
    $idx++;
    $name = trim((string)(isset($row['Name']) ? $row['Name'] : ''));
    $quiz = trim((string)(isset($row['Quiz']) ? $row['Quiz'] : ''));
    $date = trim((string)(isset($row['Zeit']) ? $row['Zeit'] : ''));
    if (trim((string)($row['PunkteModus'] ?? '')) === '' && $quiz !== '') {
        $row['PunkteModus'] = $quizPunkteModusByKey[$quiz]
            ?? (function_exists('rkrk_assess_quiz_punkte_modus') ? rkrk_assess_quiz_punkte_modus($quiz) : 'standard');
    }
    $metrics = function_exists('rkrk_assess_theory_result_metrics')
        ? rkrk_assess_theory_result_metrics($row)
        : array(
            'anz' => (int)(isset($row['AnzFragen']) ? $row['AnzFragen'] : 0),
            'richtig' => (int)(isset($row['Richtig']) ? $row['Richtig'] : 0),
            'factor' => (float)(isset($row['Erfuellungsfaktor']) ? $row['Erfuellungsfaktor'] : 0),
            'pct' => 0.0,
            'passed' => false,
            'pe' => 0.0,
            'pm' => 0.0,
            'use_points' => false,
        );
    $attempts[] = array(
        'idx' => $idx,
        'csv_line_index' => $idx - 1,
        'name' => $name,
        'name_key' => normalize_key($name),
        'quiz' => $quiz,
        'quiz_title' => (isset($quizTitleByKey[$quiz]) ? $quizTitleByKey[$quiz] : $quiz),
        'date' => $date,
        'timestamp' => parse_attempt_ts($date, $idx),
        'anz' => (int)$metrics['anz'],
        'richtig' => (int)$metrics['richtig'],
        'factor' => (float)$metrics['factor'],
        'pct' => (float)$metrics['pct'],
        'passed' => !empty($metrics['passed']),
        'punkte_erreicht' => (float)$metrics['pe'],
        'punkte_max' => (float)$metrics['pm'],
        'use_points' => !empty($metrics['use_points']),
        'punkte_modus' => (string)($metrics['punkte_modus'] ?? ($quizPunkteModusByKey[$quiz] ?? 'standard')),
        'modus_label' => exam_admin_theory_modus_label($row),
        'quiz_modus' => trim((string)(isset($row['QuizModus']) ? $row['QuizModus'] : '')),
        'exam_code' => trim((string)(isset($row['EXAM']) ? $row['EXAM'] : '')),
        'source' => 'quiz_results',
    );
}

usort($attempts, function ($a, $b) {
    if ($a['timestamp'] === $b['timestamp']) return $b['idx'] <=> $a['idx'];
    return $b['timestamp'] <=> $a['timestamp'];
});

// Abgeschlossene EXAM-Sessions aus results/exams/ in die Theorie-Resultate übernehmen
// (QuizResultate.json enthält oft nur einen Teil; Session-JSONs sind die Quelle der Wahrheit).
$examAdminReportEnsure = array('created' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => array());
$examStemByCodeName = array();
if (function_exists('rkrk_assess_exam_list_completed')) {
    $examCompletedRows = rkrk_assess_exam_list_completed();
    foreach ($examCompletedRows as $ex) {
        $ek = mb_strtolower(trim((string)($ex['exam_code'] ?? '')), 'UTF-8');
        $nk = normalize_key((string)($ex['name'] ?? ''));
        $stem = trim((string)($ex['stem'] ?? ''));
        if ($ek !== '' && $nk !== '' && $stem !== '') {
            $examStemByCodeName[$ek . '|' . $nk] = $stem;
        }
    }
    foreach ($attempts as &$aRef) {
        if (trim((string)($aRef['exam_stem'] ?? '')) !== '') {
            continue;
        }
        $ek = mb_strtolower(trim((string)($aRef['exam_code'] ?? '')), 'UTF-8');
        $nk = normalize_key((string)($aRef['name'] ?? ''));
        if ($ek !== '' && $nk !== '' && isset($examStemByCodeName[$ek . '|' . $nk])) {
            $aRef['exam_stem'] = $examStemByCodeName[$ek . '|' . $nk];
        }
    }
    unset($aRef);
    $usedAttemptIdx = array();
    $idxBase = count($attempts);
    foreach ($examCompletedRows as $ex) {
        $matchIdx = exam_admin_find_attempt_index_for_exam($attempts, $ex, $usedAttemptIdx);
        if ($matchIdx >= 0) {
            $usedAttemptIdx[$matchIdx] = true;
            $attempts[$matchIdx] = exam_admin_overlay_exam_on_attempt($attempts[$matchIdx], $ex);
            continue;
        }
        $name = trim((string)($ex['name'] ?? ''));
        $examCode = trim((string)($ex['exam_code'] ?? ''));
        $quiz = trim((string)($ex['quiz_key'] ?? ''));
        $nk = normalize_key($name);
        $idxBase++;
        $attempts[] = exam_admin_overlay_exam_on_attempt(array(
            'idx' => $idxBase,
            'csv_line_index' => -1,
            'name' => $name,
            'name_key' => $nk,
            'quiz' => $quiz,
            'quiz_title' => (isset($quizTitleByKey[$quiz]) ? $quizTitleByKey[$quiz] : $quiz),
            'date' => trim((string)($ex['zeit'] ?? '')),
            'timestamp' => (int)($ex['timestamp'] ?? 0),
            'anz' => (int)($ex['anz'] ?? 0),
            'richtig' => (int)($ex['richtig'] ?? 0),
            'factor' => (float)($ex['factor'] ?? 0),
            'pct' => (float)($ex['pct'] ?? 0),
            'passed' => !empty($ex['passed']),
            'punkte_erreicht' => (float)($ex['punkte_erreicht'] ?? 0),
            'punkte_max' => (float)($ex['punkte_max'] ?? 0),
            'use_points' => ((float)($ex['punkte_max'] ?? 0) > 0),
            'modus_label' => trim((string)($ex['quiz_modus'] ?? '')),
            'quiz_modus' => trim((string)($ex['quiz_modus'] ?? '')),
            'exam_code' => $examCode,
            'exam_stem' => trim((string)($ex['stem'] ?? '')),
            'source' => 'exams_json',
        ), $ex);
    }
    usort($attempts, function ($a, $b) {
        if ($a['timestamp'] === $b['timestamp']) return $b['idx'] <=> $a['idx'];
        return $b['timestamp'] <=> $a['timestamp'];
    });
}

// Fehlende / aktualisierte Abschluss-PDFs für completed EXAM-Sessions nachziehen (Admin)
if ($canUseExtendedAdmin && $isAdminMode && function_exists('rkrk_assess_ensure_missing_exam_reports')) {
    $forceReports = isset($_GET['regenerate_reports']);
    $doEnsure = $forceReports || isset($_GET['ensure_reports']) || !isset($_GET['no_ensure_reports']);
    if ($doEnsure) {
        $examAdminReportEnsure = rkrk_assess_ensure_missing_exam_reports(40, $forceReports);
    }
}

$quizFilter = trim((string)(isset($_GET['quiz']) ? $_GET['quiz'] : ''));
$nameFilter = trim((string)(isset($_GET['name']) ? $_GET['name'] : ''));
$statusFilter = trim((string)(isset($_GET['status']) ? $_GET['status'] : 'all'));
$theoryFromFilter = trim((string)(isset($_GET['theory_from']) ? $_GET['theory_from'] : ''));
$theoryToFilter = trim((string)(isset($_GET['theory_to']) ? $_GET['theory_to'] : ''));
$theoryFromTs = ($theoryFromFilter !== '') ? strtotime($theoryFromFilter . ' 00:00:00') : null;
$theoryToTs = ($theoryToFilter !== '') ? strtotime($theoryToFilter . ' 23:59:59') : null;

$filtered = array_values(array_filter($attempts, function ($a) use ($quizFilter, $nameFilter, $statusFilter, $theoryFromTs, $theoryToTs) {
    if ($quizFilter !== '' && $a['quiz'] !== $quizFilter) return false;
    if ($nameFilter !== '' && mb_stripos((string)$a['name'], $nameFilter, 0, 'UTF-8') === false) return false;
    if ($statusFilter === 'passed' && !$a['passed']) return false;
    if ($statusFilter === 'failed' && $a['passed']) return false;
    if ($theoryFromTs !== null && $theoryFromTs !== false && (int)$a['timestamp'] < $theoryFromTs) return false;
    if ($theoryToTs !== null && $theoryToTs !== false && (int)$a['timestamp'] > $theoryToTs) return false;
    return true;
}));
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H21',
    'rkrk_assess_admin.php:theory-filter-state',
    'Theory filters current state (before date support)',
    array(
        'quiz' => $quizFilter,
        'name' => $nameFilter,
        'status' => $statusFilter,
        'theory_from' => $theoryFromFilter,
        'theory_to' => $theoryToFilter,
        'theory_from_ts' => $theoryFromTs,
        'theory_to_ts' => $theoryToTs,
        'filteredCount' => count($filtered),
    )
);
// #endregion
$theoryDisplayRows = array_slice($filtered, 0, 15);

$participantMap = array();
$quizStats = array();
$sumPct = 0.0;
$sumPassed = 0;
foreach ($filtered as $a) {
    $sumPct += $a['pct'];
    if ($a['passed']) $sumPassed++;

    if (!isset($participantMap[$a['name_key']])) {
        $participantMap[$a['name_key']] = array(
            'name' => $a['name'],
            'attempts' => 0,
            'best_pct' => $a['pct'],
            'passed' => 0,
            'latest' => $a['date'],
            'latest_ts' => $a['timestamp'],
        );
    }
    $participantMap[$a['name_key']]['attempts']++;
    $participantMap[$a['name_key']]['best_pct'] = max($participantMap[$a['name_key']]['best_pct'], $a['pct']);
    if ($a['passed']) $participantMap[$a['name_key']]['passed']++;
    if ($a['timestamp'] > $participantMap[$a['name_key']]['latest_ts']) {
        $participantMap[$a['name_key']]['latest_ts'] = $a['timestamp'];
        $participantMap[$a['name_key']]['latest'] = $a['date'];
    }

    $qKey = $a['quiz'];
    if (!isset($quizStats[$qKey])) {
        $quizStats[$qKey] = array('title' => $a['quiz_title'], 'attempts' => 0, 'sum_pct' => 0.0, 'passed' => 0);
    }
    $quizStats[$qKey]['attempts']++;
    $quizStats[$qKey]['sum_pct'] += $a['pct'];
    if ($a['passed']) $quizStats[$qKey]['passed']++;
}

$participantRows = array_values($participantMap);
usort($participantRows, function ($a, $b) {
    return $b['latest_ts'] <=> $a['latest_ts'];
});
ksort($quizStats);

$attemptCount = count($filtered);
$participantCount = count($participantRows);
$avgPct = ($attemptCount > 0) ? round($sumPct / $attemptCount, 1) : 0.0;
$passRate = ($attemptCount > 0) ? round(($sumPassed / $attemptCount) * 100, 1) : 0.0;

$allQuizOptions = array();
$allNameOptions = array();
foreach ($attempts as $a) {
    $allQuizOptions[$a['quiz']] = $a['quiz_title'];
    $allNameOptions[$a['name_key']] = $a['name'];
}
asort($allQuizOptions);
asort($allNameOptions);

$examListRows = array();

if (isset($rkrkAssessResults) && is_dir($rkrkAssessResults)) {
    $examFiles = glob(rtrim((string)$rkrkAssessResults, "/\\") . '/*.json');
    if (is_array($examFiles)) {
        usort($examFiles, function($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $examFiles = array_slice($examFiles, 0, 50);

        foreach ($examFiles as $jsonFile) {
            $id = basename($jsonFile, '.json');
            $data = exam_admin_load_json_file($jsonFile);
            if (!is_array($data)) continue;

            // Exam-/Digital-Sessions (ohne Checklist-template/fields) nicht als Praxis-Assessment zählen
            if (
                (isset($data["exam_code"]) || isset($data["attempts"]))
                && (!isset($data["template"]) || trim((string) $data["template"]) === "")
                && (!isset($data["fields"]) || !is_array($data["fields"]))
            ) {
                continue;
            }

            $tplName = isset($data['template']) ? (string)$data['template'] : '';
            if ($tplName === '') {
                continue;
            }
            $tpl = exam_admin_load_template($QuizLocTemplates, $tplName);
            if (!is_array($tpl)) {
                $tpl = array('title' => $tplName, 'sections' => array());
            }

            $title = isset($tpl['title']) ? (string)$tpl['title'] : $tplName;
            $fields = (isset($data['fields']) && is_array($data['fields'])) ? $data['fields'] : array();
            $sections = (isset($tpl['sections']) && is_array($tpl['sections'])) ? $tpl['sections'] : array();

            $flugzeitRaw = exam_admin_find_first_field_value($fields, array('Flugzeit'));
            $datumZeitRaw = exam_admin_find_first_field_value($fields, array('Datum & Zeit'));
            $pruefungsdatumRaw = exam_admin_find_first_field_value($fields, array('Prüfungsdatum', 'Pruefungsdatum'));
            if (trim((string)$flugzeitRaw) !== '') {
                $dtRaw = $flugzeitRaw;
            } elseif (trim((string)$datumZeitRaw) !== '') {
                $dtRaw = $datumZeitRaw;
            } else {
                $dtRaw = $pruefungsdatumRaw;
            }
            $dt = exam_admin_format_datetime($dtRaw);

            $pilotRaw = isset($fields['Allgemein']['Pilot']) ? $fields['Allgemein']['Pilot'] : '';
            $pilot = exam_admin_value_to_text($pilotRaw);

            $nameParts = array();
            $infoParts = array();
            $overallRating = '';
            foreach ($sections as $secName => $secFields) {
                if (!is_array($secFields)) continue;
                foreach ($secFields as $label => $meta) {
                    if (!is_array($meta)) continue;
                    $val = isset($fields[$secName][$label]) ? exam_admin_value_to_text($fields[$secName][$label]) : '';
                    if ($val === '') continue;

                    if ((isset($meta['inname']) ? (string)$meta['inname'] : '0') === '1') {
                        $nameParts[] = exam_admin_strip_markers($val);
                    }
                    if ((isset($meta['inresult']) ? (string)$meta['inresult'] : '0') === '1') {
                        if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) === 1) {
                            $overallRating = $val;
                        } else {
                            $infoParts[] = (string)$label . ': ' . exam_admin_strip_markers($val);
                        }
                    }
                }
            }

            if ($overallRating === '') {
                foreach ($fields as $secName => $secFields) {
                    if (!is_array($secFields)) continue;
                    foreach ($secFields as $label => $rawVal) {
                        if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) === 1) {
                            $overallRating = exam_admin_value_to_text($rawVal);
                            break 2;
                        }
                    }
                }
            }
            if (exam_admin_is_material_assessment($title, $tplName)) {
                $empfehlungVal = exam_admin_find_empfehlung_value($fields, $sections);
                if ($empfehlungVal !== '') {
                    $overallRating = $empfehlungVal;
                }
            }

            $examListRows[] = array(
                'id' => $id,
                'entry_type' => 'assessment',
                'template' => $title,
                'tpl_file' => $tplName,
                'datetime' => $dt,
                'timestamp' => exam_admin_parse_ts($dtRaw, filemtime($jsonFile)),
                'name' => implode(' | ', $nameParts),
                'pilot' => $pilot,
                'test_info' => implode(' | ', $infoParts),
                'overall_rating' => $overallRating,
            );
        }
    }
}

// Zusätzlich Live-Beurteilungen (rkrk_assess_live.php) in die Übersicht übernehmen.
if (isset($rkrkLiveassessResults) && isset($rkrkLiveassessTemplates)) {
    $liveEvalRowsForOverview = exam_admin_collect_live_evaluation_rows(
        (string)$rkrkLiveassessResults,
        (string)$rkrkLiveassessTemplates
    );
    if (is_array($liveEvalRowsForOverview) && !empty($liveEvalRowsForOverview)) {
        foreach ($liveEvalRowsForOverview as $lr) {
            if (!is_array($lr)) continue;
            if (!isset($lr['name']) || trim((string)$lr['name']) === '') {
                $lr['name'] = (string)($lr['pilot'] ?? '');
            }
            // Für die bestehende praktische Tabelle kompatibel halten.
            if (!isset($lr['test_info']) || trim((string)$lr['test_info']) === '') {
                $lvl = trim((string)($lr['level'] ?? ''));
                $lr['test_info'] = ($lvl !== '') ? ('Stufe: ' . $lvl) : '';
            }
            $examListRows[] = $lr;
        }
    }
}

$examFilterTemplate = trim((string)(isset($_GET['assessment_template']) ? $_GET['assessment_template'] : ''));
$examFilterPilot = trim((string)(isset($_GET['assessment_pilot']) ? $_GET['assessment_pilot'] : ''));
$examFilterText = trim((string)(isset($_GET['assessment_text']) ? $_GET['assessment_text'] : ''));
$examFilterFrom = trim((string)(isset($_GET['assessment_from']) ? $_GET['assessment_from'] : ''));
$examFilterTo = trim((string)(isset($_GET['exam_to']) ? $_GET['exam_to'] : ''));
$examStatusFilter = trim((string)(isset($_GET['exam_status']) ? $_GET['exam_status'] : ''));
$examFromTs = ($examFilterFrom !== '') ? strtotime($examFilterFrom . ' 00:00:00') : null;
$examToTs = ($examFilterTo !== '') ? strtotime($examFilterTo . ' 23:59:59') : null;

$examTemplateOptions = array();
foreach ($examListRows as $row) {
    $examTemplateOptions[(string)$row['tpl_file']] = (string)$row['template'];
}
asort($examTemplateOptions, SORT_NATURAL | SORT_FLAG_CASE);

$normalizePracticalStatus = function($raw) {
    return mb_strtolower(exam_admin_strip_markers((string)$raw), 'UTF-8');
};
$practicalStatusOptions = array();
foreach ($examListRows as $row) {
    $statusKey = $normalizePracticalStatus($row['overall_rating'] ?? '');
    if ($statusKey === '') continue;
    if (!isset($practicalStatusOptions[$statusKey])) {
        $practicalStatusOptions[$statusKey] = exam_admin_strip_markers((string)($row['overall_rating'] ?? ''));
    }
}
asort($practicalStatusOptions, SORT_NATURAL | SORT_FLAG_CASE);

$examFilteredRows = array_values(array_filter($examListRows, function($row) use ($examFilterTemplate, $examFilterPilot, $examFilterText, $examFromTs, $examToTs, $examStatusFilter, $normalizePracticalStatus) {
    if ($examFilterTemplate !== '' && (string)$row['tpl_file'] !== $examFilterTemplate) return false;
    if ($examFilterPilot !== '') {
        $pilotHay = trim((string)($row['pilot'] ?? ''));
        $nameHay = trim((string)($row['name'] ?? ''));
        if (
            mb_stripos($pilotHay, $examFilterPilot, 0, 'UTF-8') === false &&
            mb_stripos($nameHay, $examFilterPilot, 0, 'UTF-8') === false
        ) {
            return false;
        }
    }
    if ($examFilterText !== '') {
        $hay = (string)$row['name'] . ' ' . (string)$row['test_info'] . ' ' . (string)$row['overall_rating'];
        if (mb_stripos($hay, $examFilterText, 0, 'UTF-8') === false) return false;
    }
    if ($examStatusFilter !== '') {
        $rowStatus = $normalizePracticalStatus($row['overall_rating'] ?? '');
        // Für Beurteilungen toleranter filtern: nur ausschließen, wenn ein Status vorhanden ist und explizit nicht passt.
        if ($rowStatus !== '' && $rowStatus !== $examStatusFilter) return false;
    }
    $ts = isset($row['timestamp']) ? (int)$row['timestamp'] : 0;
    if ($examFromTs !== null && $examFromTs !== false && $ts > 0 && $ts < $examFromTs) return false;
    if ($examToTs !== null && $examToTs !== false && $ts > 0 && $ts > $examToTs) return false;
    return true;
}));
$practicalFilteredRows = array_values(array_filter($examFilteredRows, function($row) {
    return ((string)($row['entry_type'] ?? 'assessment') !== 'evaluation');
}));
$evaluationFilteredRows = array_values(array_filter($examFilteredRows, function($row) {
    return ((string)($row['entry_type'] ?? '') === 'evaluation');
}));
$practicalDisplayRows = array_slice($practicalFilteredRows, 0, 10);
$evaluationDisplayRows = array_slice($evaluationFilteredRows, 0, 10);

$practicalTemplateCount = 0;
$practicalRatingBuckets = array('green' => 0, 'orange' => 0, 'red' => 0, 'other' => 0);
if (!empty($practicalFilteredRows)) {
    $tplMap = array();
    foreach ($practicalFilteredRows as $row) {
        $tplMap[(string)$row['template']] = true;
        $ratingRaw = trim((string)($row['overall_rating'] ?? ''));
        if (preg_match('/\(r\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'nicht', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'fail', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['red']++;
        } elseif (preg_match('/\(o\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'warn', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['orange']++;
        } elseif (preg_match('/\(g\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'bestanden', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'ok', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['green']++;
        } else {
            $practicalRatingBuckets['other']++;
        }
    }
    $practicalTemplateCount = count($tplMap);
}
$practicalParticipantCount = 0;
if (!empty($practicalFilteredRows)) {
    $practicalParticipantMap = array();
    foreach ($practicalFilteredRows as $row) {
        $participantKey = mb_strtolower(trim((string)($row['name'] ?? '')), 'UTF-8');
        if ($participantKey === '') continue;
        $practicalParticipantMap[$participantKey] = true;
    }
    $practicalParticipantCount = count($practicalParticipantMap);
}
$practicalTotal = count($practicalFilteredRows);
$practicalRed = (int)$practicalRatingBuckets['red'];
$practicalPassRate = ($practicalTotal > 0) ? round((($practicalTotal - $practicalRed) / $practicalTotal) * 100, 1) : 0.0;
$practicalTemplateStats = array();
$practicalParticipantStats = array();
$practicalPilotOverviewStats = array();
foreach ($practicalFilteredRows as $row) {
    $tplKey = (string)($row['template'] ?? '');
    if ($tplKey === '') $tplKey = '(ohne Template)';
    if (!isset($practicalTemplateStats[$tplKey])) {
        $practicalTemplateStats[$tplKey] = array(
            'title' => $tplKey,
            'count' => 0,
            'red' => 0,
        );
    }
    $practicalTemplateStats[$tplKey]['count']++;
    $ratingRaw = trim((string)($row['overall_rating'] ?? ''));
    if (preg_match('/\(r\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'nicht', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'fail', 0, 'UTF-8') !== false) {
        $practicalTemplateStats[$tplKey]['red']++;
    }

    $participantKey = mb_strtolower(trim((string)($row['name'] ?? '')), 'UTF-8');
    if ($participantKey === '') $participantKey = '(ohne Name)';
    if (!isset($practicalParticipantStats[$participantKey])) {
        $practicalParticipantStats[$participantKey] = array(
            'name' => trim((string)($row['name'] ?? '')) !== '' ? (string)$row['name'] : '(ohne Name)',
            'attempts' => 0,
            'latest' => (string)($row['datetime'] ?? ''),
            'latest_ts' => (int)($row['timestamp'] ?? 0),
        );
    }
    $practicalParticipantStats[$participantKey]['attempts']++;
    $rowTs = (int)($row['timestamp'] ?? 0);
    if ($rowTs > $practicalParticipantStats[$participantKey]['latest_ts']) {
        $practicalParticipantStats[$participantKey]['latest_ts'] = $rowTs;
        $practicalParticipantStats[$participantKey]['latest'] = (string)($row['datetime'] ?? '');
    }

    if (!isset($practicalPilotOverviewStats[$participantKey])) {
        $practicalPilotOverviewStats[$participantKey] = array(
            'name' => trim((string)($row['name'] ?? '')) !== '' ? (string)$row['name'] : '(ohne Name)',
            'attempts' => 0,
            'passed' => 0,
            'failed' => 0,
            'latest' => (string)($row['datetime'] ?? ''),
            'latest_ts' => (int)($row['timestamp'] ?? 0),
            'templates' => array(),
        );
    }
    $practicalPilotOverviewStats[$participantKey]['attempts']++;
    $isFailed = (preg_match('/\(r\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'nicht', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'fail', 0, 'UTF-8') !== false);
    if ($isFailed) {
        $practicalPilotOverviewStats[$participantKey]['failed']++;
    } else {
        $practicalPilotOverviewStats[$participantKey]['passed']++;
    }
    if ($rowTs > $practicalPilotOverviewStats[$participantKey]['latest_ts']) {
        $practicalPilotOverviewStats[$participantKey]['latest_ts'] = $rowTs;
        $practicalPilotOverviewStats[$participantKey]['latest'] = (string)($row['datetime'] ?? '');
    }
    $tplTitle = trim((string)($row['template'] ?? ''));
    if ($tplTitle === '') $tplTitle = '(ohne Template)';
    if (!isset($practicalPilotOverviewStats[$participantKey]['templates'][$tplTitle])) {
        $practicalPilotOverviewStats[$participantKey]['templates'][$tplTitle] = 0;
    }
    $practicalPilotOverviewStats[$participantKey]['templates'][$tplTitle]++;
}
ksort($practicalTemplateStats);
$practicalParticipantRows = array_values($practicalParticipantStats);
usort($practicalParticipantRows, function ($a, $b) {
    return $b['latest_ts'] <=> $a['latest_ts'];
});
$practicalPilotOverviewRows = array_values($practicalPilotOverviewStats);
usort($practicalPilotOverviewRows, function ($a, $b) {
    if ((int)$b['attempts'] === (int)$a['attempts']) return ((int)$b['latest_ts']) <=> ((int)$a['latest_ts']);
    return ((int)$b['attempts']) <=> ((int)$a['attempts']);
});

$evaluationTotal = count($evaluationFilteredRows);
$evaluationTemplateMap = array();
$evaluationParticipantMap = array();
$evaluationLevelBuckets = array('1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, '6' => 0, 'other' => 0);
foreach ($evaluationFilteredRows as $row) {
    $tplName = trim((string)($row['template'] ?? ''));
    if ($tplName !== '') {
        $evaluationTemplateMap[$tplName] = true;
    }
    $nameKey = mb_strtolower(trim((string)($row['name'] ?? '')), 'UTF-8');
    if ($nameKey !== '') {
        $evaluationParticipantMap[$nameKey] = true;
    }
    $lvl = exam_admin_extract_level_number((string)($row['level'] ?? ''));
    if ($lvl !== '' && isset($evaluationLevelBuckets[$lvl])) {
        $evaluationLevelBuckets[$lvl]++;
    } else {
        $evaluationLevelBuckets['other']++;
    }
}
$evaluationTemplateCount = count($evaluationTemplateMap);
$evaluationParticipantCount = count($evaluationParticipantMap);
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H22',
    'rkrk_assess_admin.php:practical-overview-candidates',
    'Practical overview candidate metrics',
    array(
        'practicalFilteredCount' => count($examFilteredRows),
        'practicalDisplayedCount' => count($practicalDisplayRows),
        'practicalTemplateCount' => $practicalTemplateCount,
        'ratingBuckets' => $practicalRatingBuckets,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H29',
    'rkrk_assess_admin.php:practical-overview-metrics',
    'Practical overview metrics candidates for UI block',
    array(
        'practicalTotal' => $practicalTotal,
        'practicalParticipantCount' => $practicalParticipantCount,
        'practicalTemplateCount' => $practicalTemplateCount,
        'practicalPassRate' => $practicalPassRate,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H32',
    'rkrk_assess_admin.php:practical-overview-block',
    'Practical overview block data prepared',
    array(
        'templateStatsCount' => count($practicalTemplateStats),
        'participantStatsCount' => count($practicalParticipantRows),
        'practicalTotal' => $practicalTotal,
        'practicalPassRate' => $practicalPassRate,
    )
);
// #endregion

$theoryParticipantLabel = 'Teilnehmer enthält';
$practicalParticipantLabel = 'Teilnehmer';
$practicalSearchHint = 'Suche (Resultate)';
$quickLinkPracticalLabel = 'Assessments';
$quickLinkTheoryLabel = 'Quiz';
$quickLinkSchulungLabel = 'Schulung';
$quickLinkQuestionLabel = 'Fragen Manager';
$quickLinkLiveLabel = 'Beurteilungen';
$quickLinkPersonelLabel = 'Personal';
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H24',
    'rkrk_assess_admin.php:label-baseline',
    'Filter labels after requested rename',
    array(
        'theoryParticipantLabel' => $theoryParticipantLabel,
        'practicalParticipantLabel' => $practicalParticipantLabel,
        'practicalSearchHint' => $practicalSearchHint,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H30',
    'rkrk_assess_admin.php:quick-link-labels',
    'Header quick-link labels after rename',
    array(
        'practicalLinkLabel' => $quickLinkPracticalLabel,
        'theoryLinkLabel' => $quickLinkTheoryLabel,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H25',
    'rkrk_assess_admin.php:theory-filters-updated',
    'Theory filter mode updated to contains+date-range',
    array(
        'nameFilterMode' => 'contains',
        'theory_from' => $theoryFromFilter,
        'theory_to' => $theoryToFilter,
        'filteredCount' => count($filtered),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H28',
    'rkrk_assess_admin.php:theory-filter-layout',
    'Theory filter layout updated: status moved back, reset next line',
    array(
        'order' => array('quiz', 'name', 'theory_from', 'theory_to', 'status', 'reset_next_line'),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H26',
    'rkrk_assess_admin.php:practical-status-options',
    'Practical status options derived from overall ratings',
    array(
        'selectedStatus' => $examStatusFilter,
        'statusOptionCount' => count($practicalStatusOptions),
        'statusOptions' => array_values($practicalStatusOptions),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H27',
    'rkrk_assess_admin.php:practical-status-filter',
    'Practical status filter applied',
    array(
        'selectedStatus' => $examStatusFilter,
        'filteredCount' => count($examFilteredRows),
        'displayedCount' => count($practicalDisplayRows),
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H2',
    'rkrk_assess_admin.php:header-init',
    'Admin page render context',
    array(
        'script' => isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '',
        'requestUri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
        'examRows' => count($examListRows),
        'examFilteredRows' => count($examFilteredRows),
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H6',
    'rkrk_assess_admin.php:table-counts',
    'Table row counts before limiting',
    array(
        'theoryFilteredCount' => count($filtered),
        'practicalFilteredCount' => count($examFilteredRows),
        'theoryFirstTimestamp' => isset($filtered[0]) ? (int)$filtered[0]['timestamp'] : null,
        'practicalFirstTimestamp' => isset($examFilteredRows[0]) ? (int)$examFilteredRows[0]['timestamp'] : null,
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H7',
    'rkrk_assess_admin.php:table-counts-limited',
    'Table row counts after limiting',
    array(
        'theoryFilteredCount' => count($filtered),
        'theoryDisplayedCount' => count($theoryDisplayRows),
        'practicalFilteredCount' => count($examFilteredRows),
        'practicalDisplayedCount' => count($practicalDisplayRows),
    )
);
// #endregion

$reportingTitle = 'Konsolidiertes Reporting';
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H1',
    'rkrk_assess_admin.php:reporting-title',
    'Reporting title before output',
    array('title' => $reportingTitle)
);
// #endregion

$practicalTestsTitle = 'Assessment-Liste (Daten aus rkrk_assess.php)';
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H5',
    'rkrk_assess_admin.php:practical-tests-title',
    'Practical tests section title before output',
    array('title' => $practicalTestsTitle)
);
// #endregion

$theoryResultsDir = dirname((string)$quizLogPath);
$theoryResultsSource = (string)$quizLogPath;
$theoryResultsSourceExists = file_exists($theoryResultsSource);
$practicalResultsDir = isset($rkrkAssessResults) ? (string)$rkrkAssessResults : '';
$practicalResultsDirExists = is_dir($practicalResultsDir);
$practicalResultsPattern = rtrim((string)$practicalResultsDir, "/\\") . DIRECTORY_SEPARATOR . "*.json";
$practicalResultFiles = $practicalResultsDirExists ? glob($practicalResultsPattern) : array();
$practicalResultsFilesFound = is_array($practicalResultFiles) && count($practicalResultFiles) > 0;
$practicalResultsFileCount = is_array($examListRows) ? count($examListRows) : 0;

function exam_admin_manager_status_meta($row) {
    $planned = trim((string)($row['PlannedAt'] ?? ''));
    $done = trim((string)($row['DoneAt'] ?? ''));
    $result = mb_strtolower(trim((string)($row['ResultStatus'] ?? '')), 'UTF-8');
    $type = trim((string)($row['TestType'] ?? ''));
    $level = trim((string)($row['_level'] ?? ''));

    // Live-Beurteilungen: nur Stufeneinteilung, kein Bestanden/NIO
    if ($done !== '' && $type === 'evaluation_template') {
        $lc = exam_admin_level_colors($level);
        $label = $level !== '' ? ('Stufe ' . $level) : 'Ausgeführt';
        if ($result !== '' && strpos($result, 'stufe') !== false) {
            $label = trim((string)($row['ResultStatus'] ?? $label));
            if ($level === '') {
                $level = exam_admin_extract_level_number($label);
            }
            $lc = exam_admin_level_colors($level);
        }
        return array(
            'code' => $level !== '' ? ('level_' . $level) : 'done',
            'label' => $label,
            'short' => exam_admin_chip_status_short($label, $level),
            'bg' => ((string)($lc['bg'] ?? '') !== '') ? (string)$lc['bg'] : '#64748b',
            'fg' => ((string)($lc['fg'] ?? '') !== '') ? (string)$lc['fg'] : '#ffffff',
            'date' => $done,
        );
    }

    $isMaterial = !empty($row['_is_material']);
    if (!$isMaterial && $type === 'assessment_template') {
        $isMaterial = exam_admin_is_material_assessment(
            (string)($row['TestRef'] ?? ''),
            (string)($row['template'] ?? '')
        );
    }
    if ($done !== '' && $isMaterial) {
        $src = trim((string)($row['_rating'] ?? ''));
        if ($src === '') {
            $src = trim((string)($row['ResultStatus'] ?? ''));
        }
        $ec = exam_admin_empfehlung_colors($src);
        if ((string)($ec['bg'] ?? '') !== '') {
            $label = exam_admin_strip_markers($src);
            if ($label === '') {
                $label = 'Ausgeführt';
            }
            return array(
                'code' => (string)$ec['code'],
                'label' => $label,
                'short' => exam_admin_chip_status_short($label),
                'bg' => (string)$ec['bg'],
                'fg' => (string)$ec['fg'],
                'date' => $done,
            );
        }
    }

    if ($done !== '') {
        if (in_array($result, array('bestanden', 'ja', 'pass', 'ok', 'true', '1'), true)) {
            return array('code' => 'passed', 'label' => 'Bestanden', 'short' => 'OK', 'bg' => '#16a34a', 'fg' => '#ffffff', 'date' => $done);
        }
        return array('code' => 'failed', 'label' => 'Nicht bestanden', 'short' => 'NIO', 'bg' => '#dc2626', 'fg' => '#ffffff', 'date' => $done);
    }

    if ($planned !== '') {
        $plannedTs = strtotime($planned . ' 23:59:59');
        if ($plannedTs !== false && $plannedTs < time()) {
            return array('code' => 'planned_overdue', 'label' => 'Geplant, noch nicht ausgefuehrt', 'short' => 'überfällig', 'bg' => '#f97316', 'fg' => '#ffffff', 'date' => $planned);
        }
        return array('code' => 'planned', 'label' => 'Geplant', 'short' => 'geplant', 'bg' => '#eab308', 'fg' => '#111827', 'date' => $planned);
    }

    return array('code' => 'open', 'label' => 'Offen', 'short' => 'offen', 'bg' => '#94a3b8', 'fg' => '#ffffff', 'date' => '');
}

/** Kompakte Chip-Statusanzeige (volle Texte bleiben im Hover). */
function exam_admin_chip_status_short($label, $level = '') {
    $level = trim((string)$level);
    // (g)/(o)/(r) = Farbcodes — Chip-Hintergrund zeigt die Farbe bereits
    $label = exam_admin_strip_markers((string)$label);
    $l = mb_strtolower(trim($label), 'UTF-8');
    if ($level !== '') {
        $n = exam_admin_extract_level_number($level);
        return $n !== '' ? ('S' . $n) : ('Stufe ' . $level);
    }
    if ($l === 'bestanden' || $l === 'ok') return 'OK';
    if ($l === 'nicht bestanden' || $l === 'nio') return 'NIO';
    if ($l === 'in progress' || $l === 'läuft' || $l === 'laeuft') return 'läuft';
    if (strpos($l, 'überfällig') !== false || strpos($l, 'ueberfaellig') !== false) return 'überfällig';
    if ($l === 'geplant') return 'geplant';
    return $label;
}

/**
 * Report-Datei zu einem JSON-/Result-Stamm (PDF bevorzugt).
 */
function exam_admin_report_href_for_stem(string $stem, string $reportsKind = 'assess'): string {
	if (!function_exists('rkrk_assess_report_download_href') || !function_exists('rkrk_assess_reports_dir')) {
		return '';
	}
	$stem = function_exists('rkrk_assess_report_stem_from_result')
		? rkrk_assess_report_stem_from_result($stem)
		: preg_replace('/\.(json|pdf|html)$/i', '', basename(str_replace('\\', '/', $stem)));
	$stem = trim((string)$stem);
	if ($stem === '') {
		return '';
	}
	$prevKind = $GLOBALS['rkrk_assess_reports_kind'] ?? null;
	$GLOBALS['rkrk_assess_reports_kind'] = (strtolower(trim($reportsKind)) === 'live') ? 'live' : 'assess';
	$dir = rkrk_assess_reports_dir();
	$href = '';
	if ($dir !== '') {
		foreach (array('pdf', 'html') as $fmt) {
			$path = $dir . $stem . '.' . $fmt;
			if (is_file($path)) {
				$href = rkrk_assess_report_download_href($path, $fmt);
				break;
			}
		}
	}
	if ($prevKind === null) {
		unset($GLOBALS['rkrk_assess_reports_kind']);
	} else {
		$GLOBALS['rkrk_assess_reports_kind'] = $prevKind;
	}
	return is_string($href) ? $href : '';
}

/**
 * Abschluss-Report-URL (PDF bevorzugt) für Manager-/Resultat-Einträge.
 *
 * @param array<string,mixed> $entry
 */
function exam_admin_report_href_for_entry(array $entry, string $pilotName = ''): string {
	if (!empty($entry['report_href']) && is_string($entry['report_href'])) {
		$cached = trim($entry['report_href']);
		if ($cached !== '') {
			return $cached;
		}
	}
	$isLive = ((string)($entry['delete_kind'] ?? '') === 'evaluation_json')
		|| ((string)($entry['delete_kind'] ?? '') === 'assessment_json')
		|| ((string)($entry['type'] ?? '') === 'evaluation')
		|| ((string)($entry['type'] ?? '') === 'practical')
		|| ((string)($entry['result_entry_type'] ?? '') === 'evaluation');
	// Live-Checklisten: Reports unter liveassess/…/reports — beide Kinds prüfen.
	$kinds = $isLive ? array('live', 'assess') : array('assess', 'live');

	$stem = trim((string)($entry['exam_stem'] ?? $entry['stem'] ?? ''));
	$rid = trim((string)($entry['result_id'] ?? ''));
	foreach ($kinds as $kind) {
		if ($stem !== '') {
			$href = exam_admin_report_href_for_stem($stem, $kind);
			if ($href !== '') {
				return $href;
			}
		}
		if ($rid !== '') {
			$href = exam_admin_report_href_for_stem($rid, $kind);
			if ($href !== '') {
				return $href;
			}
		}
	}

	$exam = trim((string)($entry['exam_code'] ?? ''));
	$name = trim((string)($entry['delete_name'] ?? $pilotName));
	$quiz = trim((string)($entry['delete_quiz'] ?? $entry['quiz'] ?? $entry['quiz_key'] ?? ''));
	if (function_exists('rkrk_assess_report_find_existing_href') && ($exam !== '' || ($name !== '' && $quiz !== ''))) {
		foreach ($kinds as $kind) {
			$prevKind = $GLOBALS['rkrk_assess_reports_kind'] ?? null;
			$GLOBALS['rkrk_assess_reports_kind'] = $kind;
			$href = (string)rkrk_assess_report_find_existing_href($exam, $name, $quiz);
			if ($prevKind === null) {
				unset($GLOBALS['rkrk_assess_reports_kind']);
			} else {
				$GLOBALS['rkrk_assess_reports_kind'] = $prevKind;
			}
			if ($href !== '') {
				return $href;
			}
		}
	}
	return '';
}

/**
 * Chip-Titel: Theorie:/Beurteilung:/Assessment: -> fett T:/B:/A:
 */
function exam_admin_chip_type_title_html(string $label): string {
	$label = trim($label);
	if ($label === '') {
		return '';
	}
	if (preg_match('/^(Theorie|Beurteilung|Assessment)\s*:\s*(.*)$/us', $label, $m)) {
		$map = array('Theorie' => 'T', 'Beurteilung' => 'B', 'Assessment' => 'A');
		$letter = $map[$m[1]] ?? mb_substr($m[1], 0, 1, 'UTF-8');
		$rest = trim((string)$m[2]);
		$html = '<b>' . htmlspecialchars($letter, ENT_QUOTES, 'UTF-8') . ':</b>';
		if ($rest !== '') {
			$html .= ' ' . htmlspecialchars($rest, ENT_QUOTES, 'UTF-8');
		}
		return $html;
	}
	return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
}


function exam_admin_report_link_html(string $href, bool $compact = false): string {
	$href = trim($href);
	if ($href === '') {
		return '';
	}
	$isPdf = (stripos($href, 't=pdf') !== false);
	$label = $compact ? ($isPdf ? 'PDF' : 'HTML') : ($isPdf ? 'Abschluss-PDF' : 'Abschluss-HTML');
	$style = $compact
		? 'display:inline-block;padding:0 4px;font-size:9px;line-height:1.35;border:1px solid rgba(255,255,255,.55);border-radius:3px;background:rgba(15,23,42,.12);color:inherit;text-decoration:none;white-space:nowrap;'
		: 'display:inline-block;padding:2px 8px;font-size:12px;line-height:1.3;border:1px solid #bfdbfe;border-radius:4px;background:#eff6ff;color:#1d4ed8;text-decoration:none;white-space:nowrap;';
	return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" title="Abschluss-Report öffnen" style="' . $style . '">' . esc_html($label) . '</a>';
}

/** Tooltip-Zusatzzeilen für Theorie-/Resultat-Hits. */
function exam_admin_hit_detail_lines(array $hit) {
    $lines = array();
    $pct = isset($hit['pct']) ? (float)$hit['pct'] : null;
    $richtig = isset($hit['richtig']) ? (int)$hit['richtig'] : null;
    $anz = isset($hit['anz']) ? (int)$hit['anz'] : null;
    $pe = isset($hit['punkte_erreicht']) && is_numeric((string)$hit['punkte_erreicht'])
        ? (float)$hit['punkte_erreicht'] : null;
    $pm = isset($hit['punkte_max']) && is_numeric((string)$hit['punkte_max'])
        ? (float)$hit['punkte_max'] : null;
    $pmode = trim((string)($hit['punkte_modus'] ?? ''));
    if ($pmode !== '' && function_exists('rkrk_assess_punkte_modus_label')) {
        $lines[] = 'Punktezählung: ' . rkrk_assess_punkte_modus_label($pmode);
    }
    if ($pct !== null && $pm !== null && $pm > 0 && function_exists('rkrk_assess_format_punkte')) {
        $lines[] = 'Punkte: ' . rkrk_assess_format_punkte($pe) . '/' . rkrk_assess_format_punkte($pm) . ' (' . $pct . '%)';
    } elseif ($pct !== null && $anz !== null && $anz > 0) {
        $lines[] = 'Score: ' . $pct . '% (' . (int)$richtig . '/' . (int)$anz . ')';
    } elseif ($pct !== null) {
        $lines[] = 'Ergebnis: ' . $pct . '%';
    }
    if ($anz !== null && $anz > 0) {
        $lines[] = 'Fragen richtig: ' . (int)$richtig . '/' . (int)$anz;
    }
    if (isset($hit['factor']) && (float)$hit['factor'] > 0) {
        $lines[] = 'Schwelle: ' . round((float)$hit['factor'] * 100, 1) . '%';
    }
    $exam = trim((string)($hit['exam_code'] ?? ''));
    if ($exam !== '') {
        $lines[] = 'EXAM: ' . $exam;
    }
    $modus = trim((string)($hit['modus_label'] ?? ''));
    if ($modus !== '') {
        $lines[] = 'Modus: ' . $modus;
    }
    $rating = exam_admin_strip_markers(trim((string)($hit['rating'] ?? '')));
    if ($rating !== '') {
        $lines[] = 'Bewertung: ' . $rating;
    }
    $level = trim((string)($hit['level'] ?? ''));
    if ($level !== '') {
        $lines[] = 'Stufe: ' . $level;
    }
    $rid = trim((string)($hit['result_id'] ?? ''));
    if ($rid !== '') {
        $lines[] = 'Result-ID: ' . $rid;
    }
    return $lines;
}

function exam_admin_format_short_date($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    $ts = strtotime(str_replace('T', ' ', $raw));
    if ($ts !== false) return date('d.m.y', $ts);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
        return $m[3] . '.' . $m[2] . '.' . substr($m[1], -2);
    }
    return $raw;
}

function exam_admin_extract_level_number($raw) {
    if (is_array($raw)) {
        if (isset($raw['v']) && is_scalar($raw['v'])) {
            return exam_admin_extract_level_number((string)$raw['v']);
        }
        $raw = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $txt = trim((string)$raw);
    if ($txt === '') return '';
    $json = @json_decode($txt, true);
    // Direct-Check-JSON ({"v":3,"main":[...]}) ist Kriterium-Status, keine Gesamtstufe —
    // nur verwenden, wenn klar eine reine Stufenzahl gemeint ist (kein "main"-Array).
    if (is_array($json) && isset($json['v']) && !isset($json['main'])) {
        return exam_admin_extract_level_number($json['v']);
    }
    // «6 - Experte / Einsatzleiter» oder «Stufe 6»
    if (preg_match('/^\s*(?:stufe\s*)?([1-6])\b/ui', $txt, $m) === 1) {
        return (string)$m[1];
    }
    if (preg_match('/\bstufe\s*([1-6])\b/ui', $txt, $m) === 1) {
        return (string)$m[1];
    }
    if (preg_match('/\b([1-6])\b/u', $txt, $m) === 1) {
        return (string)$m[1];
    }
    return '';
}

function exam_admin_level_badge($raw) {
    $level = exam_admin_extract_level_number($raw);
    if ($level === '') return '';
    $bg = '#f3f4f6';
    $fg = '#111827';
    if ($level === '1') { $bg = '#fceaea'; $fg = '#7f1d1d'; }
    elseif ($level === '2') { $bg = '#fff6dc'; $fg = '#7c4a03'; }
    elseif ($level === '3') { $bg = '#e8f4ea'; $fg = '#14532d'; }
    elseif ($level === '4') { $bg = '#d8ecde'; $fg = '#14532d'; }
    elseif ($level === '5') { $bg = '#d9f0ec'; $fg = '#0f4f46'; }
    elseif ($level === '6') { $bg = '#e2ecf8'; $fg = '#1e3a8a'; }
    return "<span style='display:inline-block;padding:3px 8px;border-radius:999px;background:" . esc_html($bg) . ";color:" . esc_html($fg) . ";font-weight:700;'>Stufe " . esc_html($level) . "</span>";
}

function exam_admin_level_colors($raw) {
    $level = exam_admin_extract_level_number($raw);
    $bg = '';
    $fg = '';
    if ($level === '1') { $bg = '#fceaea'; $fg = '#7f1d1d'; }
    elseif ($level === '2') { $bg = '#fff6dc'; $fg = '#7c4a03'; }
    elseif ($level === '3') { $bg = '#e8f4ea'; $fg = '#14532d'; }
    elseif ($level === '4') { $bg = '#d8ecde'; $fg = '#14532d'; }
    elseif ($level === '5') { $bg = '#d9f0ec'; $fg = '#0f4f46'; }
    elseif ($level === '6') { $bg = '#e2ecf8'; $fg = '#1e3a8a'; }
    return array('level' => $level, 'bg' => $bg, 'fg' => $fg);
}

function exam_admin_match_key($raw) {
    $s = mb_strtolower(trim((string)$raw), 'UTF-8');
    if ($s === '') return '';
    $s = preg_replace('/[^a-z0-9]+/u', '', $s);
    return (string)$s;
}

function exam_admin_is_positive_result($raw) {
    $v = mb_strtolower(trim((string)$raw), 'UTF-8');
    if ($v === '') return false;
    $m = exam_admin_empfehlung_marker($raw);
    if ($m === 'g') return true;
    if ($m === 'r' || $m === 'o') return false;
    return (
        strpos($v, 'bestanden') !== false ||
        strpos($v, 'ok') !== false ||
        strpos($v, '(g)') !== false ||
        $v === 'ja' || $v === 'pass' || $v === 'passed'
    );
}

function exam_admin_is_negative_result($raw) {
    $v = mb_strtolower(trim((string)$raw), 'UTF-8');
    if ($v === '') return false;
    $m = exam_admin_empfehlung_marker($raw);
    if ($m === 'r') return true;
    if ($m === 'g' || $m === 'o') return false;
    return (
        strpos($v, 'nicht') !== false ||
        strpos($v, 'fail') !== false ||
        strpos($v, '(r)') !== false
    );
}

function exam_admin_collect_live_evaluation_rows($resultsDir, $templatesDir) {
    $rows = array();
    $resultsDir = trim((string)$resultsDir);
    if ($resultsDir === '' || !is_dir($resultsDir)) return $rows;

    $files = glob(rtrim($resultsDir, "/\\") . '/*.json');
    if (!is_array($files)) return $rows;

    foreach ($files as $jsonFile) {
        $id = basename((string)$jsonFile, '.json');
        $data = exam_admin_load_json_file((string)$jsonFile);
        if (!is_array($data)) continue;

        $tplName = trim((string)($data['template'] ?? ''));
        $tpl = exam_admin_load_template((string)$templatesDir, $tplName);
        if (!is_array($tpl)) $tpl = array('title' => $tplName, 'sections' => array());
        $title = trim((string)($tpl['title'] ?? $tplName));
        if ($title === '') $title = $tplName;

        $fields = (isset($data['fields']) && is_array($data['fields'])) ? $data['fields'] : array();
        $sections = (isset($tpl['sections']) && is_array($tpl['sections'])) ? $tpl['sections'] : array();

        $dtRaw = exam_admin_find_first_field_value($fields, array('Flugzeit', 'Datum & Zeit', 'Prüfungsdatum', 'Pruefungsdatum', 'Datum'));
        $dt = exam_admin_format_datetime($dtRaw);

        $pilot = exam_admin_find_first_field_value($fields, array('Pilot', 'Piloten', 'Team / Bezeichnung', 'Team'));
        $name = exam_admin_find_first_field_value($fields, array('Name', 'Vorname', 'Teilnehmer', 'Beurteilter'));
        if (trim((string)$pilot) === '' && is_array($fields)) {
            foreach ($fields as $secFields) {
                if (!is_array($secFields)) continue;
                foreach ($secFields as $label => $value) {
                    if (preg_match('/pilot|team|bezeichnung|instruktor/ui', (string)$label) !== 1) continue;
                    $candidate = exam_admin_value_to_text($value);
                    if (trim((string)$candidate) === '') continue;
                    $pilot = (string)$candidate;
                    break 2;
                }
            }
        }

        $overallRating = '';
        foreach ($sections as $secName => $secFields) {
            if (!is_array($secFields)) continue;
            foreach ($secFields as $label => $meta) {
                if (!is_array($meta)) continue;
                if ((isset($meta['inresult']) ? (string)$meta['inresult'] : '0') !== '1') continue;
                if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) !== 1) continue;
                $val = isset($fields[$secName][$label]) ? exam_admin_value_to_text($fields[$secName][$label]) : '';
                if (trim((string)$val) !== '') {
                    $overallRating = (string)$val;
                    break 2;
                }
            }
        }
        if ($overallRating === '') {
            foreach ($fields as $secFields) {
                if (!is_array($secFields)) continue;
                foreach ($secFields as $label => $rawVal) {
                    if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) !== 1) continue;
                    $overallRating = exam_admin_value_to_text($rawVal);
                    break 2;
                }
            }
        }

        $levelText = exam_admin_find_first_field_value($fields, array(
            'Gesamtstufe',
            'Gesamtstufe (nach Pilot V01)',
            'Erfüllungsstufe',
            'Erfuellungsstufe',
            'Abschlussstufe',
            'Stufe',
            'Level',
        ));
        if (trim((string)$levelText) === '' && is_array($fields)) {
            // Bevorzuge Labels, die die Gesamtstufe meinen — nicht Bewertungskriterien / S.n-Zeilen.
            $preferred = array();
            $fallback = array();
            foreach ($fields as $secFields) {
                if (!is_array($secFields)) continue;
                foreach ($secFields as $label => $rawVal) {
                    $lab = (string)$label;
                    if (preg_match('/bewertungskriterien|kontrollpunkt|direct\s*check/ui', $lab) === 1) {
                        continue;
                    }
                    if (preg_match('/^S\.\s*\d+/ui', $lab) === 1) {
                        continue;
                    }
                    $isGesamt = (preg_match('/gesamt\s*stufe|erfuellungs\s*stufe|erfüllungs\s*stufe|abschluss.*(stufe|fazit)|stufeneinteilung/ui', $lab) === 1);
                    $isStufe = (preg_match('/\bstufe\b|\blevel\b/ui', $lab) === 1);
                    if (!$isGesamt && !$isStufe) {
                        continue;
                    }
                    $candidate = exam_admin_value_to_text($rawVal);
                    if (trim((string)$candidate) === '') {
                        continue;
                    }
                    if ($isGesamt) {
                        $preferred[] = (string)$candidate;
                    } else {
                        $fallback[] = (string)$candidate;
                    }
                }
            }
            if (!empty($preferred)) {
                $levelText = $preferred[0];
            } elseif (!empty($fallback)) {
                $levelText = $fallback[0];
            }
        }

        $displayName = trim((string)$name) !== '' ? (string)$name : (string)$pilot;
        $rows[] = array(
            'id' => $id,
            'entry_type' => 'evaluation',
            'tpl_file' => $tplName,
            'template' => $title,
            'datetime' => $dt,
            'timestamp' => exam_admin_parse_ts($dtRaw, filemtime((string)$jsonFile)),
            'pilot' => (string)$pilot,
            'name' => $displayName,
            'overall_rating' => (string)$overallRating,
            'level' => exam_admin_extract_level_number((string)$levelText),
            '_source' => 'live_eval',
        );
    }
    return $rows;
}

function exam_admin_add_done_entry(&$map, $personRaw, $entry) {
    $keys = array_unique(array(
        normalize_key((string)$personRaw),
        exam_admin_match_key((string)$personRaw),
    ));
    foreach ($keys as $k) {
        if ($k === '') continue;
        if (!isset($map[$k])) $map[$k] = array();
        $map[$k][] = $entry;
    }
}

function exam_admin_manager_text_contains(string $haystack, string $needle): bool {
    $needle = trim($needle);
    if ($needle === '') {
        return true;
    }
    return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
}

function exam_admin_done_entry_status_code(array $done): string {
    $doneLabel = trim((string)($done['label'] ?? ''));
    $doneIsEval = ((string)($done['delete_kind'] ?? '') === 'evaluation_json')
        || ((string)($done['type'] ?? '') === 'evaluation')
        || (mb_stripos($doneLabel, 'Beurteilung:', 0, 'UTF-8') === 0);
    $doneIsMaterial = !empty($done['is_material'])
        || exam_admin_is_material_assessment($doneLabel, (string)($done['result_id'] ?? ''));
    if ($doneIsEval) {
        $doneLvl = exam_admin_extract_level_number((string)($done['level'] ?? ''));
        return $doneLvl !== '' ? ('level_' . $doneLvl) : 'done';
    }
    if ($doneIsMaterial) {
        $ec = exam_admin_empfehlung_colors(trim((string)($done['rating'] ?? $done['status'] ?? '')));
        return ((string)($ec['code'] ?? '') !== '') ? (string)$ec['code'] : 'failed';
    }
    return (trim((string)($done['status'] ?? '')) === 'Bestanden') ? 'passed' : 'failed';
}

function exam_admin_done_entry_test_haystack(array $done): string {
    return trim(implode(' ', array(
        (string)($done['label'] ?? ''),
        (string)($done['exam_code'] ?? ''),
        (string)($done['exam_stem'] ?? ''),
        (string)($done['quiz_key'] ?? ''),
        (string)($done['delete_quiz'] ?? ''),
        (string)($done['result_id'] ?? ''),
        (string)($done['template'] ?? ''),
    )));
}

function exam_admin_manager_filters_allow(
    string $person,
    string $testHay,
    string $statusCode,
    string $filterPilot,
    string $filterTest,
    string $filterStatus
): bool {
    if (!exam_admin_manager_text_contains($person, $filterPilot)) {
        return false;
    }
    if (!exam_admin_manager_text_contains($testHay, $filterTest)) {
        return false;
    }
    if ($filterStatus !== 'all' && $filterStatus !== '' && $statusCode !== $filterStatus) {
        return false;
    }
    return true;
}

/**
 * Vergleicht Ergebnis-Zeilen in der Planung: gleicher Timestamp + gleiches Template nach Prefix.
 */
function exam_admin_done_label_template_fingerprint(array $done) {
    $label = trim((string)($done['label'] ?? ''));
    if (preg_match('/^(?:Assessment|Beurteilung|Theorie)\s*:\s*(.+)$/u', $label, $m)) {
        return normalize_key(trim($m[1]));
    }

    return normalize_key($label);
}

function exam_admin_done_entry_merge_preference(array $a, array $b) {
    $score = function (array $x) {
        $s = 0;
        if (trim((string)($x['exam_stem'] ?? '')) !== '') {
            $s += 8;
        }
        if (trim((string)($x['report_href'] ?? '')) !== '') {
            $s += 4;
        }
        if ((float)($x['punkte_max'] ?? 0) > 0) {
            $s += 3;
        }
        if (trim((string)($x['level'] ?? '')) !== '') {
            $s += 4;
        }
        $lab = (string)($x['label'] ?? '');
        if (mb_stripos($lab, 'Beurteilung:', 0, 'UTF-8') === 0) {
            $s += 2;
        }
        if (mb_stripos($lab, 'Assessment:', 0, 'UTF-8') === 0) {
            $s += 1;
        }

        return $s;
    };

    $winner = ($score($a) >= $score($b)) ? $a : $b;
    $loser = ($winner === $a) ? $b : $a;
    foreach (array('exam_stem', 'exam_code', 'report_href', 'modus_label') as $f) {
        if (trim((string)($winner[$f] ?? '')) === '' && trim((string)($loser[$f] ?? '')) !== '') {
            $winner[$f] = $loser[$f];
        }
    }
    foreach (array('pct', 'punkte_erreicht', 'punkte_max', 'richtig', 'anz', 'factor') as $f) {
        if ((!isset($winner[$f]) || $winner[$f] === '' || $winner[$f] === null)
            && isset($loser[$f]) && $loser[$f] !== '' && $loser[$f] !== null
        ) {
            $winner[$f] = $loser[$f];
        }
    }

    return $winner;
}

function exam_admin_done_logical_keys(array $row) {
    $keys = array();
    $stem = trim((string)($row['exam_stem'] ?? ''));
    if ($stem !== '') {
        $keys[] = 'stem:' . mb_strtolower($stem, 'UTF-8');
    }
    $exam = mb_strtolower(trim((string)($row['exam_code'] ?? '')), 'UTF-8');
    $type = trim((string)($row['type'] ?? ''));
    if ($exam !== '') {
        $keys[] = 'exam:' . $exam . ':' . $type;
    }
    $href = trim((string)($row['report_href'] ?? ''));
    if ($href !== '') {
        $keys[] = 'href:' . $href;
    }
    $ts = (int)($row['timestamp'] ?? 0);
    $fp = exam_admin_done_label_template_fingerprint($row);
    $keys[] = $ts . "\x1e" . $fp;

    return $keys;
}

/**
 * Entfernt doppelte Zeilen (z. B. dieselbe Live-Beurteilung als Assessment + Beurteilung,
 * oder QuizResultate + Exam-JSON zum selben Abschluss).
 *
 * @param array<int,array<string,mixed>> $rows
 * @return array<int,array<string,mixed>>
 */
function exam_admin_deduplicate_manager_done_bucket(array $rows) {
    if (count($rows) < 2) {
        return $rows;
    }
    $groups = array();
    $out = array();
    foreach ($rows as $row) {
        $matchIdx = null;
        $keys = exam_admin_done_logical_keys($row);
        foreach ($keys as $k) {
            if (isset($groups[$k])) {
                $matchIdx = $groups[$k];
                break;
            }
        }
        if ($matchIdx === null) {
            $idx = count($out);
            $out[] = $row;
            foreach ($keys as $k) {
                $groups[$k] = $idx;
            }
            continue;
        }
        $out[$matchIdx] = exam_admin_done_entry_merge_preference($out[$matchIdx], $row);
        foreach ($keys as $k) {
            $groups[$k] = $matchIdx;
        }
    }

    return $out;
}

/** Indexiert Stem/Report/EXAM eines geplanten Chips, damit ungeplante Zwillinge ausgeblendet werden. */
function exam_admin_index_planned_identity(array $prow, array &$stems, array &$hrefs, array &$exams) {
    $stem = trim((string)($prow['_exam_stem'] ?? ''));
    if ($stem !== '') {
        $stems[mb_strtolower($stem, 'UTF-8')] = true;
    }
    $href = trim((string)($prow['_report_href'] ?? ''));
    if ($href !== '') {
        $hrefs[$href] = true;
    }
    $exam = mb_strtolower(trim((string)($prow['_exam_code'] ?? '')), 'UTF-8');
    if ($exam !== '') {
        $exams[$exam] = true;
    }
}

/**
 * True, wenn ein Done-Eintrag bereits als geplanter Chip derselben Durchführung erscheint.
 *
 * @param array<string,mixed> $done
 * @param array<string,bool> $plannedMatchKeys
 * @param array<string,bool> $plannedDateStatus
 * @param array<string,bool> $plannedStems
 * @param array<string,bool> $plannedHrefs
 * @param array<string,bool> $plannedExams
 */
function exam_admin_done_is_planned_equivalent(
    array $done,
    array $plannedMatchKeys,
    array $plannedDateStatus,
    array $plannedStems = array(),
    array $plannedHrefs = array(),
    array $plannedExams = array()
) {
    $stem = mb_strtolower(trim((string)($done['exam_stem'] ?? '')), 'UTF-8');
    if ($stem !== '' && isset($plannedStems[$stem])) {
        return true;
    }
    $href = trim((string)($done['report_href'] ?? ''));
    if ($href !== '' && isset($plannedHrefs[$href])) {
        return true;
    }
    $exam = mb_strtolower(trim((string)($done['exam_code'] ?? '')), 'UTF-8');
    if ($exam !== '' && isset($plannedExams[$exam])) {
        return true;
    }
    $doneLabel = trim((string)($done['label'] ?? ''));
    $doneMatch = exam_admin_match_key($doneLabel);
    if ($doneMatch !== '' && isset($plannedMatchKeys[$doneMatch])) {
        return true;
    }
    $parts = explode(':', $doneLabel, 2);
    if (count($parts) === 2) {
        $doneMatch2 = exam_admin_match_key(trim((string)$parts[1]));
        if ($doneMatch2 !== '' && isset($plannedMatchKeys[$doneMatch2])) {
            return true;
        }
    }
    $doneTs = strtotime((string)($done['date'] ?? ''));
    $doneIsEval = ((string)($done['delete_kind'] ?? '') === 'evaluation_json')
        || ((string)($done['type'] ?? '') === 'evaluation')
        || (mb_stripos($doneLabel, 'Beurteilung:', 0, 'UTF-8') === 0);
    if ($doneIsEval) {
        $doneLvl = exam_admin_extract_level_number((string)($done['level'] ?? ''));
        $doneCode = $doneLvl !== '' ? ('level_' . $doneLvl) : 'done';
    } else {
        $doneCode = (trim((string)($done['status'] ?? '')) === 'Bestanden') ? 'passed' : 'failed';
    }
    if ($doneTs !== false && $doneTs > 0) {
        $ds = date('Y-m-d', $doneTs) . '|' . $doneCode;
        if (isset($plannedDateStatus[$ds])) {
            return true;
        }
    }

    return false;
}

/** Logischer Schlüssel für Planungszeilen: gleiche Person/Serie/Test/Datum gilt als Duplikat. */
function exam_admin_manager_plan_duplicate_key(array $row) {
    $seriesId = trim((string)($row['SeriesId'] ?? ''));
    if ($seriesId === '') {
        $seriesId = trim((string)($row['Id'] ?? ''));
    }
    $testType = trim((string)($row['TestType'] ?? ''));
    $testRef = trim((string)($row['TestRef'] ?? ''));
    if ($testType === '' && trim((string)($row['QuizKey'] ?? '')) !== '') {
        $testType = 'theory';
        $testRef = trim((string)$row['QuizKey']);
    }
    if ($testType === '') {
        $testType = 'theory';
    }
    if ($testRef === '') {
        $testRef = trim((string)($row['QuizKey'] ?? ''));
    }
    $person = normalize_key(trim((string)($row['Person'] ?? '')));
    $plannedRaw = trim((string)($row['PlannedAt'] ?? ''));
    $plannedKey = $plannedRaw;
    if ($plannedRaw !== '') {
        $ts = strtotime($plannedRaw);
        if ($ts !== false && $ts > 0) {
            $plannedKey = date('Y-m-d', $ts);
        }
    }
    return $person . "\x1e" . $seriesId . "\x1e" . $testType . "\x1e"
        . mb_strtolower($testRef, 'UTF-8') . "\x1e" . mb_strtolower($plannedKey, 'UTF-8');
}

/**
 * Entfernt doppelte Ids sowie logisch identische Planungszeilen (bevorzugt Zeile mit DoneAt/Status).
 *
 * @param array<int,array<string,string>> $rows
 * @return array<int,array<string,string>>
 */
function exam_admin_manager_deduplicate_rows(array $rows) {
    $seenId = array();
    $afterId = array();
    foreach ($rows as $row) {
        $id = trim((string)($row['Id'] ?? ''));
        if ($id !== '') {
            if (isset($seenId[$id])) {
                continue;
            }
            $seenId[$id] = true;
        }
        $afterId[] = $row;
    }
    $groups = array();
    foreach ($afterId as $row) {
        $k = exam_admin_manager_plan_duplicate_key($row);
        if (!isset($groups[$k])) {
            $groups[$k] = array();
        }
        $groups[$k][] = $row;
    }
    $out = array();
    foreach ($groups as $list) {
        if (count($list) === 1) {
            $out[] = $list[0];
            continue;
        }
        usort($list, function ($a, $b) {
            $da = trim((string)($a['DoneAt'] ?? '')) !== '' ? 1 : 0;
            $db = trim((string)($b['DoneAt'] ?? '')) !== '' ? 1 : 0;
            if ($da !== $db) {
                return $db - $da;
            }
            $rsa = trim((string)($a['ResultStatus'] ?? '')) !== '' ? 1 : 0;
            $rsb = trim((string)($b['ResultStatus'] ?? '')) !== '' ? 1 : 0;
            if ($rsa !== $rsb) {
                return $rsb - $rsa;
            }
            $na = strlen(trim((string)($a['Note'] ?? '')));
            $nb = strlen(trim((string)($b['Note'] ?? '')));
            return $nb - $na;
        });
        $out[] = $list[0];
    }
    return array_values($out);
}

function exam_admin_load_pilot_suggestions($personelRoot) {
    $suggestions = array();
    $personelRoot = trim((string)$personelRoot);
    if ($personelRoot === '') return $suggestions;
    $jsonPath = rtrim($personelRoot, "/\\") . '/pilotenliste.json';
    if (!is_file($jsonPath)) return $suggestions;
    $raw = @file_get_contents($jsonPath);
    if ($raw === false) return $suggestions;
    $data = @json_decode($raw, true);
    if (!is_array($data)) return $suggestions;

    $rows = array();
    if (isset($data['pilots']) && is_array($data['pilots'])) {
        $rows = $data['pilots'];
    } elseif (isset($data[0]) && is_array($data[0])) {
        $rows = $data;
    }
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $vor = trim((string)($row['vorname'] ?? ''));
        $nach = trim((string)($row['nachname'] ?? ''));
        $display = trim((string)($row['display'] ?? ''));
        $name = trim((string)($row['name'] ?? ''));
        $options = array();
        if ($display !== '') $options[] = $display;
        if ($name !== '') $options[] = $name;
        $full = trim($vor . ' ' . $nach);
        if ($full !== '') $options[] = $full;
        foreach ($options as $candidate) {
            $key = mb_strtolower($candidate, 'UTF-8');
            if (!isset($suggestions[$key])) {
                $suggestions[$key] = $candidate;
            }
        }
    }
    asort($suggestions, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($suggestions);
}

// Assessment Manager (Planung / Status / Links)
$managerHeaders = array('Id','SeriesId','SeriesTitle','Person','Source','QuizKey','TestType','TestRef','PlannedAt','DoneAt','TheoryPassed','PracticalPassed','ResultStatus','Note');
$managerDir = rtrim((string)$QuizLoc, "/\\");
$managerPath = $managerDir . '/AssessmentManager.csv';
$legacyManagerDir = rtrim((string)$QuizLoc, "/\\") . '/results';
$legacyManagerPath = $legacyManagerDir . '/AssessmentManager.csv';
if (!is_dir($managerDir)) {
    @mkdir($managerDir, 0775, true);
}
$managerRows = exam_admin_read_csv_rows($managerPath, $managerHeaders);
if (empty($managerRows) && is_file($legacyManagerPath)) {
    $legacyRows = exam_admin_read_csv_rows($legacyManagerPath, $managerHeaders);
    if (!empty($legacyRows)) {
        $managerRows = $legacyRows;
        exam_admin_write_csv_rows($managerPath, $managerHeaders, $managerRows);
    }
}
$managerRowsNormalized = array();
foreach ($managerRows as $row) {
    $seriesId = trim((string)($row['SeriesId'] ?? ''));
    if ($seriesId === '') $seriesId = trim((string)($row['Id'] ?? ''));
    $seriesTitle = trim((string)($row['SeriesTitle'] ?? ''));
    if ($seriesTitle === '') $seriesTitle = 'Standard-Serie';
    $testType = trim((string)($row['TestType'] ?? ''));
    $testRef = trim((string)($row['TestRef'] ?? ''));
    if ($testType === '' && trim((string)($row['QuizKey'] ?? '')) !== '') {
        $testType = 'theory';
        $testRef = trim((string)$row['QuizKey']);
    }
    if ($testType === '') $testType = 'theory';

    $resultStatus = trim((string)($row['ResultStatus'] ?? ''));
    if ($resultStatus === '' && trim((string)($row['DoneAt'] ?? '')) !== '') {
        $tOk = trim((string)($row['TheoryPassed'] ?? 'Nein')) === 'Ja';
        $pOk = trim((string)($row['PracticalPassed'] ?? 'Nein')) === 'Ja';
        $resultStatus = ($tOk || $pOk) ? 'Bestanden' : 'Nicht bestanden';
    }

    $row['SeriesId'] = $seriesId;
    $row['SeriesTitle'] = $seriesTitle;
    $row['TestType'] = $testType;
    $row['TestRef'] = $testRef;
    $row['ResultStatus'] = $resultStatus;
    $managerRowsNormalized[] = $row;
}
$managerRows = $managerRowsNormalized;
$managerRowCountBeforeDedup = count($managerRows);
$managerRows = exam_admin_manager_deduplicate_rows($managerRows);
if ($managerRowCountBeforeDedup !== count($managerRows)) {
    exam_admin_write_csv_rows($managerPath, $managerHeaders, $managerRows);
}
$managerAction = trim((string)($_POST['manager_action'] ?? ''));
$managerPlanDeleted = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $managerAction !== '' && $canUseExtendedAdmin) {
    if ($managerAction === 'save_entry') {
        $id = trim((string)($_POST['m_id'] ?? ''));
        $seriesId = trim((string)($_POST['m_series_id'] ?? ''));
        $seriesTitle = trim((string)($_POST['m_series_title'] ?? ''));
        if ($seriesTitle === '') $seriesTitle = 'Standard-Serie';
        if ($seriesId === '') $seriesId = uniqid('series_', false);
        $mTestManual = trim((string)($_POST['m_test_manual'] ?? ''));
        $mTestRef = trim((string)($_POST['m_test_ref'] ?? ''));
        if ($mTestManual !== '') {
            $mTestRef = $mTestManual;
        }
        $mTestType = trim((string)($_POST['m_test_type'] ?? 'theory'));

        $isKnownTheory = isset($allQuizOptions[$mTestRef]);
        $isKnownAssessmentTemplate = isset($assessmentTemplateOptionsByFile[$mTestRef]);
        $isKnownEvaluationTemplate = isset($evaluationTemplateOptionsByFile[$mTestRef]);
        if ($mTestManual === '') {
            // TestTyp/TestAuswahl konsistent halten: Auswahl steuert finalen Typ.
            if ($isKnownTheory) {
                $mTestType = 'theory';
            } elseif ($isKnownAssessmentTemplate) {
                $mTestType = 'assessment_template';
            } elseif ($isKnownEvaluationTemplate) {
                $mTestType = 'evaluation_template';
            }
        }
        $row = array(
            'Id' => $id !== '' ? $id : uniqid('plan_', false),
            'SeriesId' => $seriesId,
            'SeriesTitle' => $seriesTitle,
            'Person' => trim((string)($_POST['m_person'] ?? '')),
            'Source' => trim((string)($_POST['m_source'] ?? 'personnel')),
            'QuizKey' => '',
            'TestType' => $mTestType,
            'TestRef' => $mTestRef,
            'PlannedAt' => trim((string)($_POST['m_planned'] ?? '')),
            'DoneAt' => trim((string)($_POST['m_done'] ?? '')),
            'TheoryPassed' => '',
            'PracticalPassed' => '',
            'ResultStatus' => trim((string)($_POST['m_result_status'] ?? '')),
            'Note' => trim((string)($_POST['m_note'] ?? '')),
        );
        if ($row['TestType'] === 'theory' && $mTestRef !== '') {
            $row['QuizKey'] = $row['TestRef'];
        }
        $updated = false;
        foreach ($managerRows as $i => $old) {
            if ((string)$old['Id'] === $row['Id']) {
                $managerRows[$i] = $row;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $dupKey = exam_admin_manager_plan_duplicate_key($row);
            foreach ($managerRows as $i => $old) {
                if ($dupKey !== exam_admin_manager_plan_duplicate_key($old)) {
                    continue;
                }
                $row['Id'] = (string)$old['Id'];
                $managerRows[$i] = $row;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $managerRows[] = $row;
        }
        $managerRows = exam_admin_manager_deduplicate_rows($managerRows);
        exam_admin_write_csv_rows($managerPath, $managerHeaders, $managerRows);
    } elseif ($managerAction === 'delete_entry') {
        $id = trim((string)($_POST['m_id'] ?? ''));
        if ($id !== '') {
            $managerRows = array_values(array_filter($managerRows, function($r) use ($id) {
                return (string)$r['Id'] !== $id;
            }));
            exam_admin_write_csv_rows($managerPath, $managerHeaders, $managerRows);
            $managerPlanDeleted = true;
        }
    }
}

if (!isset($allNameOptions) || !is_array($allNameOptions)) $allNameOptions = array();
if (!isset($allQuizOptions) || !is_array($allQuizOptions)) $allQuizOptions = array();
if (!isset($practicalParticipantRows) || !is_array($practicalParticipantRows)) $practicalParticipantRows = array();
if (!isset($practicalTemplateStats) || !is_array($practicalTemplateStats)) $practicalTemplateStats = array();
if (!isset($practicalPilotOverviewRows) || !is_array($practicalPilotOverviewRows)) $practicalPilotOverviewRows = array();
if (!isset($practicalDisplayRows) || !is_array($practicalDisplayRows)) $practicalDisplayRows = array();
if (!isset($evaluationDisplayRows) || !is_array($evaluationDisplayRows)) $evaluationDisplayRows = array();
if (!isset($theoryDisplayRows) || !is_array($theoryDisplayRows)) $theoryDisplayRows = array();
if (!isset($quizStats) || !is_array($quizStats)) $quizStats = array();
if (!isset($participantRows) || !is_array($participantRows)) $participantRows = array();
if (!isset($managerRows) || !is_array($managerRows)) $managerRows = array();
if (!isset($examTemplateOptions) || !is_array($examTemplateOptions)) $examTemplateOptions = array();
if (!isset($QuizLocTemplates)) $QuizLocTemplates = '';
if (!isset($quickLinkPracticalLabel) || trim((string)$quickLinkPracticalLabel) === '') $quickLinkPracticalLabel = 'Assessments';
if (!isset($quickLinkTheoryLabel) || trim((string)$quickLinkTheoryLabel) === '') $quickLinkTheoryLabel = 'Quiz';
if (!isset($quickLinkSchulungLabel) || trim((string)$quickLinkSchulungLabel) === '') $quickLinkSchulungLabel = 'Schulung';
if (!isset($quickLinkQuestionLabel) || trim((string)$quickLinkQuestionLabel) === '') $quickLinkQuestionLabel = 'Fragen Manager';
if (!isset($quickLinkLiveLabel) || trim((string)$quickLinkLiveLabel) === '') $quickLinkLiveLabel = 'Beurteilungen';
if (!isset($quickLinkPersonelLabel) || trim((string)$quickLinkPersonelLabel) === '') $quickLinkPersonelLabel = 'Personal';

$managerPersonOptions = array();
$pilotSuggestions = exam_admin_load_pilot_suggestions(isset($rkrkLivePersonelRoot) ? (string)$rkrkLivePersonelRoot : '');
foreach ((array)$pilotSuggestions as $pilotName) {
    $n = trim((string)$pilotName);
    if ($n !== '') $managerPersonOptions[$n] = true;
}
foreach ((array)$allNameOptions as $nameValue) {
    $n = trim((string)$nameValue);
    if ($n !== '') $managerPersonOptions[$n] = true;
}
foreach ((array)$practicalParticipantRows as $p) {
    $n = trim((string)($p['name'] ?? ''));
    if ($n !== '') $managerPersonOptions[$n] = true;
}
foreach ((array)$managerRows as $r) {
    $n = trim((string)($r['Person'] ?? ''));
    if ($n !== '') $managerPersonOptions[$n] = true;
}
$managerPersonOptions = array_keys($managerPersonOptions);
sort($managerPersonOptions, SORT_NATURAL | SORT_FLAG_CASE);

$assessmentTemplateOptionsByFile = array();
foreach ((array)$examTemplateOptions as $tplFile => $tplTitle) {
    $assessmentTemplateOptionsByFile[(string)$tplFile] = (string)$tplTitle;
}
if (is_dir($QuizLocTemplates)) {
    $templateFiles = glob(rtrim((string)$QuizLocTemplates, "/\\") . '/*.json');
    if (is_array($templateFiles)) {
        foreach ($templateFiles as $tplPath) {
            $tplFile = basename((string)$tplPath, '.json');
            if (!isset($assessmentTemplateOptionsByFile[$tplFile])) {
                $assessmentTemplateOptionsByFile[$tplFile] = $tplFile;
            }
        }
    }
}
asort($assessmentTemplateOptionsByFile, SORT_NATURAL | SORT_FLAG_CASE);

usort($managerRows, function($a, $b) {
    $aTs = strtotime((string)($a['DoneAt'] ?: $a['PlannedAt']));
    $bTs = strtotime((string)($b['DoneAt'] ?: $b['PlannedAt']));
    $aTs = ($aTs === false) ? 0 : $aTs;
    $bTs = ($bTs === false) ? 0 : $bTs;
    if ($aTs === $bTs) return strcmp((string)($a['Person'] ?? ''), (string)($b['Person'] ?? ''));
    return $bTs <=> $aTs;
});

$liveEvalRows = exam_admin_collect_live_evaluation_rows(
    isset($rkrkLiveassessResults) ? (string)$rkrkLiveassessResults : '',
    isset($rkrkLiveassessTemplates) ? (string)$rkrkLiveassessTemplates : ''
);
$evaluationTemplateOptionsByFile = array();
foreach ((array)$liveEvalRows as $evr) {
    $k = trim((string)($evr['tpl_file'] ?? ''));
    if ($k === '') continue;
    $evaluationTemplateOptionsByFile[$k] = trim((string)($evr['template'] ?? $k));
}
if (isset($rkrkLiveassessTemplates) && is_dir((string)$rkrkLiveassessTemplates)) {
    $evTplFiles = glob(rtrim((string)$rkrkLiveassessTemplates, "/\\") . '/*.json');
    if (is_array($evTplFiles)) {
        foreach ($evTplFiles as $tplPath) {
            $tplFile = basename((string)$tplPath, '.json');
            if (!isset($evaluationTemplateOptionsByFile[$tplFile])) {
                $evaluationTemplateOptionsByFile[$tplFile] = $tplFile;
            }
        }
    }
}
asort($evaluationTemplateOptionsByFile, SORT_NATURAL | SORT_FLAG_CASE);
$practicalAndEvalRows = array_merge((array)$examListRows, (array)$liveEvalRows);

// Manager-Einträge automatisch mit Resultaten abgleichen (Theorie + Praktisch)
$theoryLookup = array();
$theoryLookupByMatch = array();
$theoryLookupAll = array();
$theoryLookupByPerson = array();
foreach ((array)$attempts as $a) {
    $personKey = normalize_key((string)($a['name'] ?? ''));
    $quizKey = trim((string)($a['quiz'] ?? ''));
    if ($personKey === '' || $quizKey === '') continue;
    $k = $personKey . '|' . $quizKey;
    $ts = (int)($a['timestamp'] ?? 0);
    $theoryHit = array(
        'timestamp' => $ts,
        'date' => trim((string)($a['date'] ?? '')),
        'passed' => !empty($a['passed']),
        'pct' => (float)($a['pct'] ?? 0),
        'richtig' => (int)($a['richtig'] ?? 0),
        'anz' => (int)($a['anz'] ?? 0),
        'factor' => (float)($a['factor'] ?? 0),
        'punkte_erreicht' => (float)($a['punkte_erreicht'] ?? 0),
        'punkte_max' => (float)($a['punkte_max'] ?? 0),
        'punkte_modus' => trim((string)($a['punkte_modus'] ?? '')),
        'exam_code' => trim((string)($a['exam_code'] ?? '')),
        'exam_stem' => trim((string)($a['exam_stem'] ?? '')),
        'modus_label' => trim((string)($a['modus_label'] ?? '')),
        'quiz_modus' => trim((string)($a['quiz_modus'] ?? '')),
    );
    if (!isset($theoryLookup[$k]) || $ts > (int)$theoryLookup[$k]['timestamp']) {
        $theoryLookup[$k] = $theoryHit;
    }
    $quizMatch = exam_admin_match_key($quizKey);
    if ($quizMatch !== '') {
        $km = $personKey . '|' . $quizMatch;
        if (!isset($theoryLookupByMatch[$km]) || $ts > (int)$theoryLookupByMatch[$km]['timestamp']) {
            $theoryLookupByMatch[$km] = $theoryHit;
        }
    }
    if (!isset($theoryLookupByPerson[$personKey])) $theoryLookupByPerson[$personKey] = array();
    $theoryLookupByPerson[$personKey][] = array_merge($theoryHit, array('match' => $quizMatch));
    if (!isset($theoryLookupAll[$k])) $theoryLookupAll[$k] = array();
    $theoryLookupAll[$k][] = $theoryHit;
}

$practicalLookup = array();
$practicalLookupByMatch = array();
$practicalLookupAll = array();
$practicalLookupByPerson = array();
foreach ((array)$practicalAndEvalRows as $row) {
    $tpl = trim((string)($row['tpl_file'] ?? ''));
    if ($tpl === '') continue;
    $ts = (int)($row['timestamp'] ?? 0);
    $rating = trim((string)($row['overall_rating'] ?? ''));
    $passed = exam_admin_is_positive_result($rating) && !exam_admin_is_negative_result($rating);
    $tplTitle = trim((string)($row['template'] ?? ''));
    $tplMatchA = exam_admin_match_key($tpl);
    $tplMatchB = exam_admin_match_key($tplTitle);
    $resultId = trim((string)($row['id'] ?? ''));
    $resultEntryType = trim((string)($row['entry_type'] ?? 'assessment'));
    $hitBase = array(
        'timestamp' => $ts,
        'date' => trim((string)($row['datetime'] ?? '')),
        'passed' => $passed,
        'level' => exam_admin_extract_level_number((string)($row['level'] ?? '')),
        'rating' => $rating,
        'result_id' => $resultId,
        'result_entry_type' => $resultEntryType,
    );

    $candidateNames = array(
        normalize_key((string)($row['pilot'] ?? '')),
        normalize_key((string)($row['name'] ?? '')),
    );
    foreach ($candidateNames as $personKey) {
        if ($personKey === '') continue;
        $k = $personKey . '|' . $tpl;
        if (!isset($practicalLookup[$k]) || $ts > (int)$practicalLookup[$k]['timestamp']) {
            $practicalLookup[$k] = $hitBase;
        }
        foreach (array($tplMatchA, $tplMatchB) as $tplMatch) {
            if ($tplMatch === '') continue;
            $km = $personKey . '|' . $tplMatch;
            if (!isset($practicalLookupByMatch[$km]) || $ts > (int)$practicalLookupByMatch[$km]['timestamp']) {
                $practicalLookupByMatch[$km] = $hitBase;
            }
        }
        if (!isset($practicalLookupByPerson[$personKey])) $practicalLookupByPerson[$personKey] = array();
        $practicalLookupByPerson[$personKey][] = array_merge($hitBase, array(
            'match' => $tplMatchA !== '' ? $tplMatchA : $tplMatchB,
        ));
        if (!isset($practicalLookupAll[$k])) $practicalLookupAll[$k] = array();
        $practicalLookupAll[$k][] = $hitBase;
    }
}

$managerRowsResolved = array();
foreach ((array)$managerRows as $row) {
    $personKey = normalize_key((string)($row['Person'] ?? ''));
    $testType = trim((string)($row['TestType'] ?? 'theory'));
    $testRef = trim((string)($row['TestRef'] ?? $row['QuizKey'] ?? ''));
    $matTitle = (isset($assessmentTemplateOptionsByFile[$testRef]))
        ? (string)$assessmentTemplateOptionsByFile[$testRef]
        : '';
    $row['_is_material'] = ($testType === 'assessment_template')
        && exam_admin_is_material_assessment($matTitle, $testRef);

    $autoHit = null;
    $allHits = array();
    $entryDate = trim((string)($row['PlannedAt'] ?? ''));
    if ($entryDate === '') $entryDate = trim((string)($row['DoneAt'] ?? ''));
    $entryTs = strtotime((string)$entryDate);
    $entryMonthKey = ($entryTs !== false) ? date('Y-m', $entryTs) : date('Y-m');
    $launchUrl = '';

    if ($personKey !== '' && $testRef !== '') {
        $refMatch = exam_admin_match_key($testRef);
        if ($testType === 'theory') {
            $key = $personKey . '|' . $testRef;
            if (isset($theoryLookup[$key])) $autoHit = $theoryLookup[$key];
            if (isset($theoryLookupAll[$key]) && is_array($theoryLookupAll[$key])) $allHits = $theoryLookupAll[$key];
            if (!is_array($autoHit) && $refMatch !== '') {
                $km = $personKey . '|' . $refMatch;
                if (isset($theoryLookupByMatch[$km])) $autoHit = $theoryLookupByMatch[$km];
            }
            // Fallback: falls Test als Theorie geplant ist, aber praktisch durchgeführt wurde.
            if (!is_array($autoHit) && $refMatch !== '') {
                $km = $personKey . '|' . $refMatch;
                if (isset($practicalLookupByMatch[$km])) $autoHit = $practicalLookupByMatch[$km];
            }
            if (!is_array($autoHit) && $refMatch !== '' && isset($practicalLookupByPerson[$personKey])) {
                foreach ((array)$practicalLookupByPerson[$personKey] as $cand) {
                    $cm = (string)($cand['match'] ?? '');
                    if ($cm === '') continue;
                    if (strpos($cm, $refMatch) !== false || strpos($refMatch, $cm) !== false) {
                        if (!is_array($autoHit) || (int)$cand['timestamp'] > (int)($autoHit['timestamp'] ?? 0)) {
                            $autoHit = $cand;
                        }
                    }
                }
            }
            $launchUrl = $canUseExtendedAdmin ? rkrk_assess_append_setting_href('rkrk_digitalassess.php?quiz=' . rawurlencode($testRef) . '&name=' . rawurlencode((string)($row['Person'] ?? ''))) : '';
        } else {
            $key = $personKey . '|' . $testRef;
            if (isset($practicalLookup[$key])) $autoHit = $practicalLookup[$key];
            if (isset($practicalLookupAll[$key]) && is_array($practicalLookupAll[$key])) $allHits = $practicalLookupAll[$key];
            if (!is_array($autoHit) && $refMatch !== '') {
                $km = $personKey . '|' . $refMatch;
                if (isset($practicalLookupByMatch[$km])) $autoHit = $practicalLookupByMatch[$km];
            }
            // Fallback: falls Test als praktisch geplant ist, aber Theorie-Resultat gematcht werden kann.
            if (!is_array($autoHit) && $refMatch !== '') {
                $km = $personKey . '|' . $refMatch;
                if (isset($theoryLookupByMatch[$km])) $autoHit = $theoryLookupByMatch[$km];
            }
            if (!is_array($autoHit) && $refMatch !== '' && isset($practicalLookupByPerson[$personKey])) {
                foreach ((array)$practicalLookupByPerson[$personKey] as $cand) {
                    $cm = (string)($cand['match'] ?? '');
                    if ($cm === '') continue;
                    if (strpos($cm, $refMatch) !== false || strpos($refMatch, $cm) !== false) {
                        if (!is_array($autoHit) || (int)$cand['timestamp'] > (int)($autoHit['timestamp'] ?? 0)) {
                            $autoHit = $cand;
                        }
                    }
                }
            }
            if (!is_array($autoHit) && $refMatch !== '' && isset($theoryLookupByPerson[$personKey])) {
                foreach ((array)$theoryLookupByPerson[$personKey] as $cand) {
                    $cm = (string)($cand['match'] ?? '');
                    if ($cm === '') continue;
                    if (strpos($cm, $refMatch) !== false || strpos($refMatch, $cm) !== false) {
                        if (!is_array($autoHit) || (int)$cand['timestamp'] > (int)($autoHit['timestamp'] ?? 0)) {
                            $autoHit = $cand;
                        }
                    }
                }
            }
        }
    }

    $monthHits = array();
    foreach ($allHits as $hit) {
        $hitTs = (int)($hit['timestamp'] ?? 0);
        if ($hitTs <= 0) continue;
        if (date('Y-m', $hitTs) !== $entryMonthKey) continue;
        $monthHits[] = $hit;
    }
    usort($monthHits, function($a, $b) {
        return ((int)($b['timestamp'] ?? 0)) <=> ((int)($a['timestamp'] ?? 0));
    });

    if (!empty($monthHits)) {
        $latestHit = $monthHits[0];
        $row['DoneAt'] = trim((string)($latestHit['date'] ?? ''));
        $row['_level'] = exam_admin_extract_level_number((string)($latestHit['level'] ?? ''));
        $row['_rating'] = trim((string)($latestHit['rating'] ?? ''));
        $row['_result_source'] = 'results';
        $row['_exam_code'] = trim((string)($latestHit['exam_code'] ?? ''));
        $row['_exam_stem'] = trim((string)($latestHit['exam_stem'] ?? ''));
        $row['_pct'] = isset($latestHit['pct']) ? (float)$latestHit['pct'] : null;
        $row['_punkte_erreicht'] = isset($latestHit['punkte_erreicht']) ? (float)$latestHit['punkte_erreicht'] : null;
        $row['_punkte_max'] = isset($latestHit['punkte_max']) ? (float)$latestHit['punkte_max'] : null;
        $row['_richtig'] = isset($latestHit['richtig']) ? (int)$latestHit['richtig'] : null;
        $row['_anz'] = isset($latestHit['anz']) ? (int)$latestHit['anz'] : null;
        $row['_punkte_modus'] = trim((string)($latestHit['punkte_modus'] ?? ''));
        $row['_modus_label'] = trim((string)($latestHit['modus_label'] ?? ''));
        $row['_quiz_modus'] = trim((string)($latestHit['quiz_modus'] ?? ''));
        $row['_result_id'] = trim((string)($latestHit['result_id'] ?? ''));
        $row['_report_href'] = exam_admin_report_href_for_entry(array_merge($latestHit, array(
            'type' => ((string)$testType === 'evaluation_template') ? 'evaluation' : (((string)$testType === 'theory') ? 'theory' : 'practical'),
            'delete_kind' => ((string)$testType === 'evaluation_template') ? 'evaluation_json' : (((string)$testType === 'theory') ? 'theory_csv' : 'assessment_json'),
            'delete_name' => (string)($row['Person'] ?? ''),
            'delete_quiz' => ((string)$testType === 'theory') ? $testRef : '',
        )), (string)($row['Person'] ?? ''));
        if ((string)$testType === 'evaluation_template') {
            // Live-Beurteilung: nur Stufe, kein Bestanden/NIO
            $row['ResultStatus'] = ((string)$row['_level'] !== '') ? ('Stufe ' . (string)$row['_level']) : 'Ausgeführt';
        } elseif (!empty($row['_is_material'])) {
            $src = (string)$row['_rating'];
            $row['ResultStatus'] = ($src !== '') ? exam_admin_strip_markers($src) : 'Ausgeführt';
        } else {
            $row['ResultStatus'] = !empty($latestHit['passed']) ? 'Bestanden' : 'Nicht bestanden';
        }
    } elseif (is_array($autoHit)) {
        $row['DoneAt'] = trim((string)($autoHit['date'] ?? ''));
        $row['_level'] = exam_admin_extract_level_number((string)($autoHit['level'] ?? ''));
        $row['_rating'] = trim((string)($autoHit['rating'] ?? ''));
        $row['_result_source'] = 'results';
        $row['_exam_code'] = trim((string)($autoHit['exam_code'] ?? ''));
        $row['_exam_stem'] = trim((string)($autoHit['exam_stem'] ?? ''));
        $row['_pct'] = isset($autoHit['pct']) ? (float)$autoHit['pct'] : null;
        $row['_punkte_erreicht'] = isset($autoHit['punkte_erreicht']) ? (float)$autoHit['punkte_erreicht'] : null;
        $row['_punkte_max'] = isset($autoHit['punkte_max']) ? (float)$autoHit['punkte_max'] : null;
        $row['_richtig'] = isset($autoHit['richtig']) ? (int)$autoHit['richtig'] : null;
        $row['_anz'] = isset($autoHit['anz']) ? (int)$autoHit['anz'] : null;
        $row['_punkte_modus'] = trim((string)($autoHit['punkte_modus'] ?? ''));
        $row['_modus_label'] = trim((string)($autoHit['modus_label'] ?? ''));
        $row['_quiz_modus'] = trim((string)($autoHit['quiz_modus'] ?? ''));
        $row['_result_id'] = trim((string)($autoHit['result_id'] ?? ''));
        $row['_report_href'] = exam_admin_report_href_for_entry(array_merge($autoHit, array(
            'type' => ((string)$testType === 'evaluation_template') ? 'evaluation' : (((string)$testType === 'theory') ? 'theory' : 'practical'),
            'delete_kind' => ((string)$testType === 'evaluation_template') ? 'evaluation_json' : (((string)$testType === 'theory') ? 'theory_csv' : 'assessment_json'),
            'delete_name' => (string)($row['Person'] ?? ''),
            'delete_quiz' => ((string)$testType === 'theory') ? $testRef : '',
        )), (string)($row['Person'] ?? ''));
        if ((string)$testType === 'evaluation_template') {
            $row['ResultStatus'] = ((string)$row['_level'] !== '') ? ('Stufe ' . (string)$row['_level']) : 'Ausgeführt';
        } elseif (!empty($row['_is_material'])) {
            $src = (string)$row['_rating'];
            $row['ResultStatus'] = ($src !== '') ? exam_admin_strip_markers($src) : 'Ausgeführt';
        } else {
            $row['ResultStatus'] = !empty($autoHit['passed']) ? 'Bestanden' : 'Nicht bestanden';
        }
    } else {
        $row['_level'] = '';
        $row['_rating'] = '';
        $row['_result_source'] = 'manual';
        $row['_exam_code'] = '';
        $row['_exam_stem'] = '';
        $row['_pct'] = null;
        $row['_punkte_erreicht'] = null;
        $row['_punkte_max'] = null;
        $row['_richtig'] = null;
        $row['_anz'] = null;
        $row['_punkte_modus'] = '';
        $row['_result_id'] = '';
        $row['_report_href'] = '';
    }

    if ((string)$testType === 'theory' && trim((string)($row['_punkte_modus'] ?? '')) === '' && $testRef !== '') {
        $row['_punkte_modus'] = isset($quizPunkteModusByKey[$testRef])
            ? (string)$quizPunkteModusByKey[$testRef]
            : (function_exists('rkrk_assess_quiz_punkte_modus') ? rkrk_assess_quiz_punkte_modus($testRef) : 'standard');
    }

    if (
        $canUseExtendedAdmin
        && $personKey !== ''
        && $testRef !== ''
        && (string)$testType !== 'theory'
    ) {
        $hitForLaunch = null;
        if (!empty($monthHits)) {
            $hitForLaunch = $monthHits[0];
        } elseif (is_array($autoHit)) {
            $hitForLaunch = $autoHit;
        }
        $rid = (is_array($hitForLaunch)) ? trim((string)($hitForLaunch['result_id'] ?? '')) : '';
        $rEt = (is_array($hitForLaunch)) ? trim((string)($hitForLaunch['result_entry_type'] ?? '')) : '';
        $personDisp = (string)($row['Person'] ?? '');
        $useLive = ((string)$testType === 'evaluation_template' || $rEt === 'evaluation');
        $path = $useLive ? 'rkrk_assess_live.php' : 'rkrk_assess.php';
        $q = '?mode=form&file=' . rawurlencode($testRef) . '&PersPick=on';
        if ($rid !== '') {
            $q .= '&edit=' . rawurlencode($rid);
        }
        if ($personDisp !== '') {
            $q .= '&pilot=' . rawurlencode($personDisp);
        }
        $launchUrl = rkrk_assess_append_setting_href($path . $q);
    }

    $resultList = array();
    foreach ($monthHits as $hit) {
        $d = trim((string)($hit['date'] ?? ''));
        if ($d === '') $d = date('d.m.Y H:i', (int)$hit['timestamp']);
        if ((string)$testType === 'evaluation_template') {
            $lvl = exam_admin_extract_level_number((string)($hit['level'] ?? ''));
            $line = $d . ' - ' . ($lvl !== '' ? ('Stufe ' . $lvl) : 'Ausgeführt');
        } elseif (!empty($row['_is_material'])) {
            $r = trim((string)($hit['rating'] ?? ''));
            $line = $d . ' - ' . ($r !== '' ? exam_admin_strip_markers($r) : 'Ausgeführt');
        } else {
            $line = $d . ' - ' . (!empty($hit['passed']) ? 'Bestanden' : 'Nicht bestanden');
        }
        $details = exam_admin_hit_detail_lines($hit);
        if (!empty($details)) {
            $line .= ' | ' . implode(' | ', $details);
        }
        $resultList[] = $line;
    }
    $row['_results_list'] = $resultList;
    $row['_launch_url'] = $launchUrl;
    $row['_month_key'] = $entryMonthKey;
    $managerRowsResolved[] = $row;
}
$managerRows = $managerRowsResolved;

$managerFilterFrom = trim((string)($_GET['m_from'] ?? ''));
$managerFilterTo = trim((string)($_GET['m_to'] ?? ''));
$managerFilterPilot = trim((string)($_GET['m_pilot'] ?? ''));
$managerFilterTest = trim((string)($_GET['m_test'] ?? ''));
$managerFilterStatus = trim((string)($_GET['m_status'] ?? 'all'));
$managerFilterKind = trim((string)($_GET['m_kind'] ?? 'all'));
$allowedKinds = array('all', 'planned', 'result', 'progress', 'theory', 'assessment', 'evaluation', 'rkrsplus_exam');
if (!in_array($managerFilterKind, $allowedKinds, true)) {
    $managerFilterKind = 'all';
}
$managerFilterFromTs = ($managerFilterFrom !== '') ? strtotime($managerFilterFrom . ' 00:00:00') : null;
$managerFilterToTs = ($managerFilterTo !== '') ? strtotime($managerFilterTo . ' 23:59:59') : null;

$managerByPilot = array();
foreach ((array)$managerRows as $row) {
    $refTs = strtotime((string)($row['PlannedAt'] ?? $row['DoneAt'] ?? ''));
    if ($refTs === false) $refTs = 0;
    $person = trim((string)($row['Person'] ?? ''));
    if ($person === '') $person = '(ohne Person)';

    if ($managerFilterFromTs !== null && $managerFilterFromTs !== false && $refTs > 0 && $refTs < $managerFilterFromTs) continue;
    if ($managerFilterToTs !== null && $managerFilterToTs !== false && $refTs > 0 && $refTs > $managerFilterToTs) continue;
    if ($managerFilterPilot !== '' && mb_stripos($person, $managerFilterPilot, 0, 'UTF-8') === false) continue;

    $testRef = trim((string)($row['TestRef'] ?? $row['QuizKey'] ?? ''));
    $type = trim((string)($row['TestType'] ?? 'theory'));
    $testLabel = $testRef;
    if ($type === 'theory' && isset($allQuizOptions[$testRef])) {
        $testLabel = (string)$allQuizOptions[$testRef];
    } elseif ($type === 'assessment_template' && isset($assessmentTemplateOptionsByFile[$testRef])) {
        $testLabel = (string)$assessmentTemplateOptionsByFile[$testRef];
    } elseif ($type === 'evaluation_template' && isset($evaluationTemplateOptionsByFile[$testRef])) {
        $testLabel = (string)$evaluationTemplateOptionsByFile[$testRef];
    }
    $examCode = trim((string)($row['_exam_code'] ?? ''));
    if ($managerFilterTest !== '' && mb_stripos($testLabel . ' ' . $testRef . ' ' . $examCode, $managerFilterTest, 0, 'UTF-8') === false) continue;

    $statusMeta = exam_admin_manager_status_meta($row);
    if ($managerFilterStatus !== 'all' && $managerFilterStatus !== '') {
        if ((string)$statusMeta['code'] !== $managerFilterStatus) continue;
    }
    if ($managerFilterKind === 'rkrsplus_exam') {
        if (!exam_admin_is_rkrsplus_exam_relevant(array(
            'type' => $type,
            'label' => $testLabel,
            'ref' => $testRef,
            'is_material' => !empty($row['_is_material']),
            'modus_label' => (string)($row['_modus_label'] ?? ''),
            'quiz_modus' => (string)($row['_quiz_modus'] ?? ''),
            'planned_only' => trim((string)($row['DoneAt'] ?? '')) === '',
        ))) {
            continue;
        }
    }

    if (!isset($managerByPilot[$person])) $managerByPilot[$person] = array();
    $managerByPilot[$person][] = $row;
}
ksort($managerByPilot, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($managerByPilot as &$pilotRows) {
    usort($pilotRows, function($a, $b) {
        $aTs = strtotime((string)($a['DoneAt'] ?: $a['PlannedAt']));
        $bTs = strtotime((string)($b['DoneAt'] ?: $b['PlannedAt']));
        $aTs = ($aTs === false) ? 0 : $aTs;
        $bTs = ($bTs === false) ? 0 : $bTs;
        return $bTs <=> $aTs;
    });
}
unset($pilotRows);

$managerDoneByPilot = array();
foreach ((array)$attempts as $a) {
    $personName = trim((string)($a['name'] ?? ''));
    $personKey = normalize_key($personName);
    $ts = (int)($a['timestamp'] ?? 0);
    if ($personKey === '' || $ts <= 0) continue;
    if ($managerFilterFromTs !== null && $managerFilterFromTs !== false && $ts < $managerFilterFromTs) continue;
    if ($managerFilterToTs !== null && $managerFilterToTs !== false && $ts > $managerFilterToTs) continue;
    $quizKey = trim((string)($a['quiz'] ?? ''));
    $quizTitle = trim((string)($a['quiz_title'] ?? $quizKey));
    $examCode = trim((string)($a['exam_code'] ?? ''));
    $testHay = 'Theorie: ' . $quizTitle . ' ' . $quizKey . ' ' . $examCode . ' ' . trim((string)($a['exam_stem'] ?? ''));
    $statusCode = !empty($a['passed']) ? 'passed' : 'failed';
    if (!exam_admin_manager_filters_allow($personName, $testHay, $statusCode, $managerFilterPilot, $managerFilterTest, $managerFilterStatus)) {
        continue;
    }
    if ($managerFilterKind === 'rkrsplus_exam') {
        if (!exam_admin_is_rkrsplus_exam_relevant(array(
            'type' => 'theory',
            'label' => trim((string)($a['quiz_title'] ?? $a['quiz'] ?? '')),
            'ref' => trim((string)($a['quiz'] ?? '')),
            'modus_label' => (string)($a['modus_label'] ?? ''),
            'quiz_modus' => (string)($a['quiz_modus'] ?? ''),
        ))) {
            continue;
        }
    }
    exam_admin_add_done_entry($managerDoneByPilot, $personName, array(
        'date' => trim((string)($a['date'] ?? '')),
        'label' => 'Theorie: ' . $quizTitle,
        'status' => !empty($a['passed']) ? 'Bestanden' : 'Nicht bestanden',
        'type' => 'theory',
        'rating' => '',
        'timestamp' => $ts,
        'pct' => (float)($a['pct'] ?? 0),
        'richtig' => (int)($a['richtig'] ?? 0),
        'anz' => (int)($a['anz'] ?? 0),
        'factor' => (float)($a['factor'] ?? 0),
        'punkte_erreicht' => (float)($a['punkte_erreicht'] ?? 0),
        'punkte_max' => (float)($a['punkte_max'] ?? 0),
        'punkte_modus' => trim((string)($a['punkte_modus'] ?? '')),
        'exam_code' => trim((string)($a['exam_code'] ?? '')),
        'exam_stem' => trim((string)($a['exam_stem'] ?? '')),
        'modus_label' => trim((string)($a['modus_label'] ?? '')),
        'delete_kind' => ((int)($a['csv_line_index'] ?? -1) >= 0)
            ? 'theory_csv'
            : (trim((string)($a['exam_stem'] ?? '')) !== '' ? 'exam_json' : ''),
        'csv_line_index' => (int)($a['csv_line_index'] ?? -1),
        'delete_name' => trim((string)($a['name'] ?? '')),
        'delete_date' => trim((string)($a['date'] ?? '')),
        'delete_quiz' => trim((string)($a['quiz'] ?? '')),
        'exam_stem' => trim((string)($a['exam_stem'] ?? '')),
        'progress_path' => trim((string)($a['exam_stem'] ?? '')) !== '' ? (trim((string)$a['exam_stem']) . '.json') : '',
        'report_href' => exam_admin_report_href_for_entry(array(
            'type' => 'theory',
            'delete_kind' => 'theory_csv',
            'exam_code' => trim((string)($a['exam_code'] ?? '')),
            'exam_stem' => trim((string)($a['exam_stem'] ?? '')),
            'delete_name' => trim((string)($a['name'] ?? '')),
            'delete_quiz' => trim((string)($a['quiz'] ?? '')),
        ), trim((string)($a['name'] ?? ''))),
    ));
}
foreach ((array)$examListRows as $row) {
    // Live-Beurteilungen stehen bereits in examListRows (Übersicht) und werden unten nochmals aus
    // liveEvalRows als "Beurteilung:" eingetragen — nur einmal fuer die Timeline verwenden.
    if ((string)($row['entry_type'] ?? 'assessment') === 'evaluation') {
        continue;
    }
    $displayNames = array();
    foreach (array($row['pilot'] ?? '', $row['name'] ?? '') as $rawName) {
        $rawName = trim((string)$rawName);
        if ($rawName !== '') {
            $displayNames[$rawName] = true;
        }
    }
    $ts = (int)($row['timestamp'] ?? 0);
    if ($ts <= 0) continue;
    if ($managerFilterFromTs !== null && $managerFilterFromTs !== false && $ts < $managerFilterFromTs) continue;
    if ($managerFilterToTs !== null && $managerFilterToTs !== false && $ts > $managerFilterToTs) continue;
    $rating = trim((string)($row['overall_rating'] ?? ''));
    $tplForMat = trim((string)($row['template'] ?? $row['tpl_file'] ?? ''));
    $isMaterialDone = exam_admin_is_material_assessment($tplForMat, (string)($row['tpl_file'] ?? ''));
    if ($isMaterialDone && $rating !== '') {
        $status = exam_admin_strip_markers($rating);
    } else {
        $status = (exam_admin_is_positive_result($rating) && !exam_admin_is_negative_result($rating)) ? 'Bestanden' : 'Nicht bestanden';
    }
    $label = 'Assessment: ' . trim((string)($row['template'] ?? $row['tpl_file'] ?? ''));
    if ($managerFilterKind === 'rkrsplus_exam') {
        if (!exam_admin_is_rkrsplus_exam_relevant(array(
            'type' => 'practical',
            'label' => $label,
            'ref' => $tplForMat . ' ' . (string)($row['tpl_file'] ?? ''),
            'is_material' => $isMaterialDone,
        ))) {
            continue;
        }
    }
    $testHay = $label . ' ' . $tplForMat . ' ' . trim((string)($row['tpl_file'] ?? '')) . ' ' . trim((string)($row['id'] ?? ''));
    foreach (array_keys($displayNames) as $personName) {
        if ($personName === '') continue;
        $doneEntry = array(
            'date' => trim((string)($row['datetime'] ?? '')),
            'label' => $label,
            'status' => $status,
            'type' => 'practical',
            'rating' => $rating,
            'is_material' => $isMaterialDone,
            'timestamp' => $ts,
            'delete_kind' => 'assessment_json',
            'delete_name' => $personName,
            'result_id' => trim((string)($row['id'] ?? '')),
            'report_href' => exam_admin_report_href_for_entry(array(
                'type' => 'practical',
                'delete_kind' => 'assessment_json',
                'result_id' => trim((string)($row['id'] ?? '')),
            ), $personName),
        );
        $statusCode = exam_admin_done_entry_status_code($doneEntry);
        if (!exam_admin_manager_filters_allow($personName, $testHay, $statusCode, $managerFilterPilot, $managerFilterTest, $managerFilterStatus)) {
            continue;
        }
        exam_admin_add_done_entry($managerDoneByPilot, $personName, $doneEntry);
    }
}
foreach ((array)$liveEvalRows as $row) {
    if ($managerFilterKind === 'rkrsplus_exam') {
        continue;
    }
    $displayNames = array();
    foreach (array($row['pilot'] ?? '', $row['name'] ?? '') as $rawName) {
        $rawName = trim((string)$rawName);
        if ($rawName !== '') {
            $displayNames[$rawName] = true;
        }
    }
    $ts = (int)($row['timestamp'] ?? 0);
    if ($ts <= 0) continue;
    if ($managerFilterFromTs !== null && $managerFilterFromTs !== false && $ts < $managerFilterFromTs) continue;
    if ($managerFilterToTs !== null && $managerFilterToTs !== false && $ts > $managerFilterToTs) continue;
    $rating = trim((string)($row['overall_rating'] ?? ''));
    $levelText = trim((string)($row['level'] ?? ''));
    $levelNum = exam_admin_extract_level_number($levelText !== '' ? $levelText : $rating);
    // Live-Beurteilung: nur Stufe, kein Bestanden/NIO
    $status = $levelNum !== '' ? ('Stufe ' . $levelNum) : 'Ausgeführt';
    $label = 'Beurteilung: ' . trim((string)($row['template'] ?? $row['tpl_file'] ?? ''));
    $testHay = $label . ' ' . trim((string)($row['template'] ?? '')) . ' ' . trim((string)($row['tpl_file'] ?? '')) . ' ' . trim((string)($row['id'] ?? ''));
    foreach (array_keys($displayNames) as $personName) {
        if ($personName === '') continue;
        $doneEntry = array(
            'date' => trim((string)($row['datetime'] ?? '')),
            'label' => $label,
            'status' => $status,
            'type' => 'evaluation',
            'rating' => $rating,
            'level' => $levelNum !== '' ? $levelNum : $levelText,
            'timestamp' => $ts,
            'delete_kind' => 'evaluation_json',
            'delete_name' => $personName,
            'result_id' => trim((string)($row['id'] ?? '')),
            'report_href' => exam_admin_report_href_for_entry(array(
                'type' => 'evaluation',
                'delete_kind' => 'evaluation_json',
                'result_id' => trim((string)($row['id'] ?? '')),
            ), $personName),
        );
        $statusCode = exam_admin_done_entry_status_code($doneEntry);
        if (!exam_admin_manager_filters_allow($personName, $testHay, $statusCode, $managerFilterPilot, $managerFilterTest, $managerFilterStatus)) {
            continue;
        }
        exam_admin_add_done_entry($managerDoneByPilot, $personName, $doneEntry);
    }
}
foreach ($managerDoneByPilot as &$doneRowsRef) {
    $doneRowsRef = exam_admin_deduplicate_manager_done_bucket($doneRowsRef);
}
unset($doneRowsRef);
foreach ($managerDoneByPilot as &$doneRows) {
    usort($doneRows, function($a, $b) {
        return ((int)($b['timestamp'] ?? 0)) <=> ((int)($a['timestamp'] ?? 0));
    });
}
unset($doneRows);

if ($managerFilterPilot !== '' || $managerFilterTest !== '' || ($managerFilterStatus !== 'all' && $managerFilterStatus !== '')) {
    foreach ($managerDoneByPilot as $personKey => $doneRowsBucket) {
        $kept = array();
        foreach ((array)$doneRowsBucket as $done) {
            $personHay = trim((string)($done['delete_name'] ?? ''));
            if ($personHay === '') {
                foreach ((array)$managerPersonOptions as $candidate) {
                    if (normalize_key((string)$candidate) === (string)$personKey || exam_admin_match_key((string)$candidate) === (string)$personKey) {
                        $personHay = (string)$candidate;
                        break;
                    }
                }
            }
            if ($personHay === '') {
                $personHay = (string)$personKey;
            }
            $statusCode = exam_admin_done_entry_status_code($done);
            $testHay = exam_admin_done_entry_test_haystack($done);
            if (!exam_admin_manager_filters_allow($personHay, $testHay, $statusCode, $managerFilterPilot, $managerFilterTest, $managerFilterStatus)) {
                continue;
            }
            $kept[] = $done;
        }
        if (empty($kept)) {
            unset($managerDoneByPilot[$personKey]);
        } else {
            $managerDoneByPilot[$personKey] = $kept;
        }
    }
}

// Offene Fortschritte: nur Progress-Folder (checklists_progress + exams_progress)
$managerProgressByPilot = array();
$progressDedupKeys = array();

$addProgressChip = function (array $entry) use (&$managerProgressByPilot, &$progressDedupKeys, $managerFilterFromTs, $managerFilterToTs, $managerFilterPilot, $managerFilterTest, $managerFilterStatus, $managerFilterKind) {
    $pilotName = trim((string)($entry['pilot_display'] ?? ''));
    if ($pilotName === '') {
        $pilotName = '(ohne Pilot)';
    }
    $ts = (int)($entry['timestamp'] ?? 0);
    if ($managerFilterFromTs !== null && $managerFilterFromTs !== false && $ts > 0 && $ts < $managerFilterFromTs) {
        return;
    }
    if ($managerFilterToTs !== null && $managerFilterToTs !== false && $ts > 0 && $ts > $managerFilterToTs) {
        return;
    }
    if ($managerFilterPilot !== '' && mb_stripos($pilotName, $managerFilterPilot, 0, 'UTF-8') === false) {
        return;
    }
    $label = trim((string)($entry['label'] ?? ''));
    $quizKey = trim((string)($entry['quiz_key'] ?? ''));
    $examCode = trim((string)($entry['exam_code'] ?? ''));
    if ($managerFilterTest !== '' && mb_stripos($label . ' ' . $quizKey . ' ' . $examCode, $managerFilterTest, 0, 'UTF-8') === false) {
        return;
    }
    if ($managerFilterStatus !== 'all' && $managerFilterStatus !== '' && $managerFilterStatus !== 'in_progress') {
        return;
    }
    if ($managerFilterKind === 'rkrsplus_exam') {
        $progFile = trim((string)($entry['progress_file'] ?? $entry['template'] ?? ''));
        if (!exam_admin_is_rkrsplus_exam_relevant(array(
            'type' => trim((string)($entry['type'] ?? '')),
            'label' => $label,
            'ref' => trim($quizKey . ' ' . $progFile),
            'quiz_key' => $quizKey,
            'quiz_modus' => (string)($entry['quiz_modus'] ?? ''),
            'modus_label' => (string)($entry['modus_label'] ?? ''),
            'is_material' => exam_admin_is_material_assessment($label, $progFile),
        ))) {
            return;
        }
    }
    $dedup = strtolower(normalize_key($pilotName) . '|' . $examCode . '|' . $quizKey . '|' . (string)($entry['delete_kind'] ?? '') . '|' . basename((string)($entry['progress_path'] ?? '')));
    if ($dedup !== '|||' && isset($progressDedupKeys[$dedup])) {
        return;
    }
    $progressDedupKeys[$dedup] = true;
    $personKey = normalize_key($pilotName);
    if (!isset($managerProgressByPilot[$personKey])) {
        $managerProgressByPilot[$personKey] = array();
    }
    $entry['pilot_display'] = $pilotName;
    $managerProgressByPilot[$personKey][] = $entry;
};

// 1) Checklist-Fortschritt (ASSESS)
$checklistProgressRows = (function_exists('rkrk_assess_checklist_progress_list'))
    ? rkrk_assess_checklist_progress_list('assess')
    : array();
foreach ((array)$checklistProgressRows as $prog) {
    $pilotName = trim((string)($prog['pilot'] ?? ''));
    $tplLabel = trim((string)($prog['template'] ?? $prog['file'] ?? ''));
    $tplFile = trim((string)($prog['file'] ?? ''));
    if ($tplFile !== '' && isset($assessmentTemplateOptionsByFile[$tplFile])) {
        $tplLabel = (string)$assessmentTemplateOptionsByFile[$tplFile];
    } elseif ($tplFile !== '') {
        $stem = pathinfo($tplFile, PATHINFO_FILENAME);
        if ($stem !== '' && isset($assessmentTemplateOptionsByFile[$stem])) {
            $tplLabel = (string)$assessmentTemplateOptionsByFile[$stem];
        } elseif ($stem !== '' && isset($assessmentTemplateOptionsByFile[$stem . '.json'])) {
            $tplLabel = (string)$assessmentTemplateOptionsByFile[$stem . '.json'];
        }
    }
    $addProgressChip(array(
        'date' => trim((string)($prog['updated_at'] ?? $prog['created_at'] ?? '')),
        'label' => 'Assessment: ' . $tplLabel,
        'template' => $tplLabel,
        'status' => 'In progress',
        'type' => 'assessment_progress',
        'timestamp' => (int)($prog['timestamp'] ?? 0),
        'updated_at' => trim((string)($prog['updated_at'] ?? '')),
        'created_at' => trim((string)($prog['created_at'] ?? '')),
        'progress_file' => trim((string)($prog['file'] ?? '')),
        'progress_edit' => trim((string)($prog['edit'] ?? '')),
        'progress_draft_id' => trim((string)($prog['draft_id'] ?? '')),
        'progress_path' => basename((string)($prog['path'] ?? '')),
        'result_stem' => trim((string)($prog['result_stem'] ?? '')),
        'delete_kind' => 'checklist_progress',
        'pilot_display' => $pilotName !== '' ? $pilotName : '(ohne Pilot)',
    ));
}

// 2) Quiz-/Theorie-Progress (exams_progress) — echte In-progress-Ablage
//    EXAM-JSONs unter results/exams/ sind Abschluss-/Session-Dateien, kein Progress-Folder.
$quizProgressRows = (function_exists('rkrk_assess_quiz_progress_list_incomplete'))
    ? rkrk_assess_quiz_progress_list_incomplete(true)
    : array();
foreach ((array)$quizProgressRows as $qp) {
    $pilotName = trim((string)($qp['name'] ?? ''));
    $examCode = trim((string)($qp['exam_code'] ?? ''));
    $quizKey = trim((string)($qp['quiz_key'] ?? ''));
    $quizTitle = $quizKey;
    if ($quizKey !== '' && isset($allQuizOptions[$quizKey])) {
        $quizTitle = (string)$allQuizOptions[$quizKey];
    }
    $label = 'Theorie';
    if ($examCode !== '') {
        $label .= ' EXAM ' . $examCode;
    }
    $label .= ': ' . $quizTitle;
    $answered = (int)($qp['answered'] ?? 0);
    $maxQ = (int)($qp['max_questions'] ?? 0);
    $statusText = 'In progress';
    if ($maxQ > 0) {
        $statusText = 'In progress (' . $answered . '/' . $maxQ . ')';
    }
    $addProgressChip(array(
        'date' => trim((string)($qp['updated_at'] ?? $qp['started_at'] ?? '')),
        'label' => $label,
        'status' => $statusText,
        'type' => 'quiz_progress',
        'timestamp' => (int)($qp['timestamp'] ?? 0),
        'updated_at' => trim((string)($qp['updated_at'] ?? '')),
        'created_at' => trim((string)($qp['started_at'] ?? '')),
        'exam_code' => $examCode,
        'quiz_key' => $quizKey,
        'quiz_modus' => trim((string)($qp['quiz_modus'] ?? '')),
        'schulung' => trim((string)($qp['schulung'] ?? '')),
        'drone' => trim((string)($qp['drone'] ?? '')),
        'progress_path' => basename((string)($qp['path'] ?? '')),
        'answered' => $answered,
        'max_questions' => $maxQ,
        'delete_kind' => 'quiz_progress',
        'pilot_display' => $pilotName !== '' ? $pilotName : '(ohne Pilot)',
        'delete_name' => $pilotName,
    ));
}
unset($addProgressChip);

$managerPilotUnion = array();
foreach ((array)$managerByPilot as $pilotName => $rows) {
    $managerPilotUnion[(string)$pilotName] = true;
}
foreach ((array)$managerDoneByPilot as $pilotKey => $rows) {
    $resolvedName = '';
    foreach ((array)$managerPersonOptions as $candidate) {
        if (normalize_key((string)$candidate) === (string)$pilotKey || exam_admin_match_key((string)$candidate) === (string)$pilotKey) {
            $resolvedName = (string)$candidate;
            break;
        }
    }
    if ($resolvedName === '') $resolvedName = (string)$pilotKey;
    if ($resolvedName === '') continue;
    $managerPilotUnion[$resolvedName] = true;
}
foreach ((array)$managerProgressByPilot as $pilotKey => $rows) {
    $resolvedName = '';
    foreach ((array)$rows as $pr) {
        if (trim((string)($pr['pilot_display'] ?? '')) !== '') {
            $resolvedName = trim((string)$pr['pilot_display']);
            break;
        }
    }
    if ($resolvedName === '') {
        foreach ((array)$managerPersonOptions as $candidate) {
            if (normalize_key((string)$candidate) === (string)$pilotKey || exam_admin_match_key((string)$candidate) === (string)$pilotKey) {
                $resolvedName = (string)$candidate;
                break;
            }
        }
    }
    if ($resolvedName === '') $resolvedName = (string)$pilotKey;
    if ($resolvedName === '') continue;
    $managerPilotUnion[$resolvedName] = true;
}
if ($managerFilterStatus === 'in_progress') {
    $progressPilotNames = array();
    foreach ((array)$managerProgressByPilot as $pilotKey => $rows) {
        foreach ((array)$rows as $pr) {
            $dn = trim((string)($pr['pilot_display'] ?? ''));
            if ($dn !== '') $progressPilotNames[$dn] = true;
        }
        foreach ((array)$managerPersonOptions as $candidate) {
            if (normalize_key((string)$candidate) === (string)$pilotKey) {
                $progressPilotNames[(string)$candidate] = true;
            }
        }
    }
    $managerPilotUnion = $progressPilotNames;
    $managerByPilot = array();
}
$managerPilotNames = array_keys($managerPilotUnion);
if ($managerFilterPilot !== '') {
    $managerPilotNames = array_values(array_filter($managerPilotNames, function ($n) use ($managerFilterPilot) {
        return exam_admin_manager_text_contains((string)$n, $managerFilterPilot);
    }));
}
sort($managerPilotNames, SORT_NATURAL | SORT_FLAG_CASE);

$managerFilterLayout = trim((string)($_GET['m_layout'] ?? 'chips'));
if ($managerFilterLayout !== 'list') {
    $managerFilterLayout = 'chips';
}

// Flache Listenansicht: alle Einträge (geplant / Resultat / in progress)
$managerFlatRows = array();
foreach ((array)$managerPilotNames as $pilotName) {
    $pilotTests = (isset($managerByPilot[$pilotName]) && is_array($managerByPilot[$pilotName])) ? $managerByPilot[$pilotName] : array();
    $pilotDone = $managerDoneByPilot[normalize_key((string)$pilotName)] ?? ($managerDoneByPilot[exam_admin_match_key((string)$pilotName)] ?? array());
    $pilotProgress = $managerProgressByPilot[normalize_key((string)$pilotName)] ?? ($managerProgressByPilot[exam_admin_match_key((string)$pilotName)] ?? array());

    $plannedMatchKeys = array();
    $plannedDateStatus = array();
    $plannedStems = array();
    $plannedHrefs = array();
    $plannedExams = array();
    foreach ((array)$pilotTests as $prow) {
        $pRef = trim((string)($prow['TestRef'] ?? $prow['QuizKey'] ?? ''));
        $pType = trim((string)($prow['TestType'] ?? 'theory'));
        $pLabel = $pRef;
        if ($pType === 'theory' && isset($allQuizOptions[$pRef])) {
            $pLabel = (string)$allQuizOptions[$pRef];
        } elseif ($pType === 'assessment_template' && isset($assessmentTemplateOptionsByFile[$pRef])) {
            $pLabel = (string)$assessmentTemplateOptionsByFile[$pRef];
        } elseif ($pType === 'evaluation_template' && isset($evaluationTemplateOptionsByFile[$pRef])) {
            $pLabel = (string)$evaluationTemplateOptionsByFile[$pRef];
        }
        $plannedMatchKeys[exam_admin_match_key($pRef)] = true;
        $plannedMatchKeys[exam_admin_match_key($pLabel)] = true;
        exam_admin_index_planned_identity($prow, $plannedStems, $plannedHrefs, $plannedExams);
        $pMeta = exam_admin_manager_status_meta($prow);
        $pTs = strtotime((string)($prow['DoneAt'] ?? ''));
        if ($pTs === false || $pTs <= 0) $pTs = strtotime((string)($prow['PlannedAt'] ?? ''));
        if ($pTs !== false && $pTs > 0) {
            $plannedDateStatus[date('Y-m-d', $pTs) . '|' . (string)($pMeta['code'] ?? '')] = true;
        }

        $meta = exam_admin_manager_status_meta($prow);
        $refTs = strtotime((string)($prow['DoneAt'] ?? $prow['PlannedAt'] ?? ''));
        if ($refTs === false) $refTs = 0;
        $kindCat = ($pType === 'evaluation_template') ? 'evaluation' : (($pType === 'assessment_template') ? 'assessment' : 'theory');
        $detailBits = exam_admin_hit_detail_lines(array(
            'pct' => $prow['_pct'] ?? null,
            'exam_code' => $prow['_exam_code'] ?? '',
            'level' => $prow['_level'] ?? '',
            'punkte_erreicht' => $prow['_punkte_erreicht'] ?? null,
            'punkte_max' => $prow['_punkte_max'] ?? null,
            'richtig' => $prow['_richtig'] ?? null,
            'anz' => $prow['_anz'] ?? null,
            'punkte_modus' => $prow['_punkte_modus'] ?? '',
            'factor' => $prow['_factor'] ?? null,
        ));
        // Prefer details from matched results list
        $extra = '';
        if (!empty($prow['_results_list']) && is_array($prow['_results_list'])) {
            $extra = implode(' · ', array_slice($prow['_results_list'], 0, 2));
        }
        $managerFlatRows[] = array(
            'timestamp' => $refTs,
            'date' => trim((string)($meta['date'] ?? '')),
            'pilot' => (string)$pilotName,
            'group' => 'planned',
            'kind' => $kindCat,
            'type_label' => ($pType === 'theory') ? 'Theorie' : (($pType === 'assessment_template') ? 'Assessment' : 'Beurteilung'),
            'test_label' => $pLabel,
            'series' => trim((string)($prow['SeriesTitle'] ?? '')) !== '' ? (string)$prow['SeriesTitle'] : 'Standard-Serie',
            'status' => (string)($meta['label'] ?? ''),
            'status_code' => (string)($meta['code'] ?? ''),
            'exam_code' => trim((string)($prow['_exam_code'] ?? '')),
            'details' => $extra,
            'note' => trim((string)($prow['Note'] ?? '')),
            'manager_id' => trim((string)($prow['Id'] ?? '')),
            'row_json' => $prow,
            'launch_url' => trim((string)($prow['_launch_url'] ?? '')),
            'report_href' => trim((string)($prow['_report_href'] ?? '')),
            'delete' => array('delete_kind' => '', 'manager_action' => 'delete_entry', 'm_id' => trim((string)($prow['Id'] ?? ''))),
        );
    }

    if ($managerFilterStatus !== 'in_progress') {
        foreach ((array)$pilotDone as $done) {
            if (exam_admin_done_is_planned_equivalent(
                $done,
                $plannedMatchKeys,
                $plannedDateStatus,
                $plannedStems,
                $plannedHrefs,
                $plannedExams
            )) {
                continue;
            }
            $doneLabel = trim((string)($done['label'] ?? ''));
            $doneIsEval = ((string)($done['delete_kind'] ?? '') === 'evaluation_json')
                || ((string)($done['type'] ?? '') === 'evaluation')
                || (mb_stripos($doneLabel, 'Beurteilung:', 0, 'UTF-8') === 0);
            $doneTs = strtotime((string)($done['date'] ?? ''));
            $doneCode = exam_admin_done_entry_status_code($done);

            $kindCat = 'theory';
            $typeLabel = 'Theorie';
            if ($doneIsEval) {
                $kindCat = 'evaluation';
                $typeLabel = 'Beurteilung';
            } elseif ((string)($done['type'] ?? '') === 'practical' || mb_stripos($doneLabel, 'Assessment:', 0, 'UTF-8') === 0) {
                $kindCat = 'assessment';
                $typeLabel = 'Assessment';
            }
            $statusLabel = trim((string)($done['status'] ?? ''));
            if ($doneIsEval) {
                $lvl = exam_admin_extract_level_number((string)($done['level'] ?? ''));
                $statusLabel = $lvl !== '' ? ('Stufe ' . $lvl) : 'Ausgeführt';
            }
            $details = exam_admin_hit_detail_lines($done);
            $reportHref = trim((string)($done['report_href'] ?? ''));
            if ($reportHref === '') {
                $reportHref = exam_admin_report_href_for_entry($done, (string)$pilotName);
            }
            $managerFlatRows[] = array(
                'timestamp' => (int)($done['timestamp'] ?? ($doneTs !== false ? $doneTs : 0)),
                'date' => trim((string)($done['date'] ?? '')),
                'pilot' => (string)$pilotName,
                'group' => 'result',
                'kind' => $kindCat,
                'type_label' => $typeLabel,
                'test_label' => $doneLabel,
                'series' => '',
                'status' => $statusLabel,
                'status_code' => $doneCode,
                'exam_code' => trim((string)($done['exam_code'] ?? '')),
                'details' => implode(' · ', $details),
                'note' => '',
                'manager_id' => '',
                'row_json' => null,
                'launch_url' => '',
                'report_href' => $reportHref,
                'delete' => $done,
            );
        }
    }

    foreach ((array)$pilotProgress as $ip) {
        $kindCat = 'progress';
        $typeLabel = 'In progress';
        $ipType = (string)($ip['type'] ?? '');
        if ($ipType === 'assessment_progress') {
            $typeLabel = 'Assessment (läuft)';
        } elseif ($ipType === 'quiz_progress') {
            $typeLabel = 'Theorie (läuft)';
            $kindCat = 'theory';
        }
        $details = array();
        if (trim((string)($ip['exam_code'] ?? '')) !== '') $details[] = 'EXAM: ' . trim((string)$ip['exam_code']);
        if (trim((string)($ip['quiz_key'] ?? '')) !== '') $details[] = 'Quiz: ' . trim((string)$ip['quiz_key']);
        if ((int)($ip['max_questions'] ?? 0) > 0) {
            $details[] = 'Fortschritt: ' . (int)($ip['answered'] ?? 0) . '/' . (int)$ip['max_questions'];
        }
        $managerFlatRows[] = array(
            'timestamp' => (int)($ip['timestamp'] ?? 0),
            'date' => trim((string)($ip['updated_at'] ?? $ip['date'] ?? '')),
            'pilot' => (string)$pilotName,
            'group' => 'progress',
            'kind' => $kindCat,
            'type_label' => $typeLabel,
            'test_label' => trim((string)($ip['label'] ?? '')),
            'series' => '',
            'status' => trim((string)($ip['status'] ?? 'In progress')),
            'status_code' => 'in_progress',
            'exam_code' => trim((string)($ip['exam_code'] ?? '')),
            'details' => implode(' · ', $details),
            'note' => '',
            'manager_id' => '',
            'row_json' => null,
            'launch_url' => '',
            'report_href' => '',
            'delete' => $ip,
        );
    }
}

// Pilot/Test/Status/Art-Filter auf Flachliste (Resultate waren zuvor nicht gefiltert)
$managerFlatRows = array_values(array_filter($managerFlatRows, function ($r) use ($managerFilterPilot, $managerFilterTest, $managerFilterStatus, $managerFilterKind) {
    $pilot = (string)($r['pilot'] ?? '');
    $testHay = trim((string)($r['test_label'] ?? '') . ' ' . (string)($r['exam_code'] ?? '') . ' ' . (string)($r['details'] ?? ''));
    $statusCode = (string)($r['status_code'] ?? '');
    if (!exam_admin_manager_filters_allow($pilot, $testHay, $statusCode, $managerFilterPilot, $managerFilterTest, $managerFilterStatus)) {
        return false;
    }
    if ($managerFilterKind === 'all') {
        return true;
    }
    $group = (string)($r['group'] ?? '');
    $kind = (string)($r['kind'] ?? '');
    if ($managerFilterKind === 'planned') return $group === 'planned';
    if ($managerFilterKind === 'result') return $group === 'result';
    if ($managerFilterKind === 'progress') return $group === 'progress';
    if ($managerFilterKind === 'rkrsplus_exam') return true;
    return $kind === $managerFilterKind;
}));
usort($managerFlatRows, function ($a, $b) {
    $cmp = ((int)($b['timestamp'] ?? 0)) <=> ((int)($a['timestamp'] ?? 0));
    if ($cmp !== 0) return $cmp;
    return strcasecmp((string)($a['pilot'] ?? ''), (string)($b['pilot'] ?? ''));
});

$adminView = trim((string)($_GET['view'] ?? 'manager'));
if ($adminView !== 'manager' && $adminView !== 'overview') {
    $adminView = 'manager';
}
$requestedPage = trim((string)($_GET['page'] ?? ''));
$allowedPages = array('planning', 'results');
if (in_array($requestedPage, $allowedPages, true)) {
    $adminPage = $requestedPage;
} else {
    $adminPage = ($adminView === 'overview') ? 'results' : 'planning';
}
$planningHref = rkrk_assess_append_setting_href('rkrk_assess_admin.php?page=planning');
$resultsHref = rkrk_assess_append_setting_href('rkrk_assess_admin.php?page=results');
$planningChipsHref = rkrk_assess_append_setting_href(
    'rkrk_assess_admin.php?' . http_build_query(array_filter(array(
        'page' => 'planning',
        'm_layout' => 'chips',
        'm_from' => $managerFilterFrom,
        'm_to' => $managerFilterTo,
        'm_pilot' => $managerFilterPilot,
        'm_test' => $managerFilterTest,
        'm_status' => $managerFilterStatus,
        'm_kind' => $managerFilterKind !== 'all' ? $managerFilterKind : '',
    ), function ($v) { return $v !== '' && $v !== null; }))
);
$planningListHref = rkrk_assess_append_setting_href(
    'rkrk_assess_admin.php?' . http_build_query(array_filter(array(
        'page' => 'planning',
        'm_layout' => 'list',
        'm_from' => $managerFilterFrom,
        'm_to' => $managerFilterTo,
        'm_pilot' => $managerFilterPilot,
        'm_test' => $managerFilterTest,
        'm_status' => $managerFilterStatus,
        'm_kind' => $managerFilterKind !== 'all' ? $managerFilterKind : '',
    ), function ($v) { return $v !== '' && $v !== null; }))
);
$digitalSchulungBrowseHref = rkrk_assess_append_setting_href(
	'rkrk_digitalassess.php?' . http_build_query(rkrk_assess_merge_query_with_setting(array('Schulung' => '')))
);
$adminPageTitle = ($adminPage === 'results') ? 'Ergebnisübersicht' : 'Planung Übersicht';
$examAdminPlanningReturnQ = exam_admin_planning_return_query(isset($_GET) && is_array($_GET) ? $_GET : array());
$examAdminShowPlanningDeletes = ($canUseExtendedAdmin && $isAdminMode);
?>
<body>
<script>
// #region agent log
(function () {
  // local debug ingest disabled in production/browser use
})();
// #endregion
</script>
<div class="wrap rkrk-wrap">
  <?php /* Datenquellen Resultate bewusst ausgeblendet. */ ?>
  <?php
  if (function_exists('rkrk_assess_portal_nav_echo')) {
    ob_start();
    rkrk_assess_portal_nav_echo(array(
      'btn_class' => 'ql-btn',
      'schulung_mode' => false,
      'is_admin' => $canUseExtendedAdmin && $isAdminMode,
      'is_instr' => $canUseExtendedAdmin && $isInstrMode,
      'selected_quiz' => '',
    ));
    $__rkrk_portal_out = ob_get_clean();
    if ($__rkrk_portal_out !== '') {
      echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $__rkrk_portal_out . '</div>';
    }
  }
  ?>

  <div class="card rkrk-card" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
    <div>
      <h2 style="margin-top:0;"><?php echo esc_html($adminPageTitle); ?></h2>
    </div>
    <div>
      <img src="<?php echo esc_html($QuizIconLoc . $logopic); ?>" alt="RKRK Logo" style="height:56px;">
    </div>
  </div>

  <?php if ($adminPage === 'planning'): ?>
  <?php if (!empty($examAdminReportEnsure['created'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#93c5fd;background:#eff6ff;color:#1e3a8a;">
      <?php echo esc_html((string)(int)$examAdminReportEnsure['created']); ?> Abschluss-Report(s) erzeugt/aktualisiert
      <?php if (!empty($examAdminReportEnsure['failed'])): ?>
        · <?php echo esc_html((string)(int)$examAdminReportEnsure['failed']); ?> fehlgeschlagen
      <?php endif; ?>
    </div>
  <?php elseif (!empty($examAdminReportEnsure['failed'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#fda4af;background:#fff1f2;color:#9f1239;">
      Abschluss-Reports konnten nicht erzeugt werden (<?php echo esc_html((string)(int)$examAdminReportEnsure['failed']); ?>).
      <?php if (!empty($examAdminReportEnsure['errors'][0])): ?>
        <div style="font-size:12px;margin-top:4px;"><?php echo esc_html((string)$examAdminReportEnsure['errors'][0]); ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($_GET['archived'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#86efac;background:#f0fdf4;color:#166534;">
      <?php echo esc_html((string)(int)$_GET['archived']); ?> Kachel(n) archiviert (Dateien unter results/archive/, aus dieser Übersicht entfernt).
      Quiz-/EXAM-Links sind damit wieder frei.
      <?php if (!empty($_GET['archive_err'])): ?>
        <div style="font-size:12px;margin-top:4px;">Einige Einträge konnten nicht archiviert werden (<?php echo esc_html((string)(int)$_GET['archive_err']); ?>).</div>
      <?php endif; ?>
    </div>
  <?php elseif (!empty($_GET['archive_err'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#fda4af;background:#fff1f2;color:#9f1239;">
      Archivieren fehlgeschlagen (Datei nicht gefunden, keine Berechtigung oder ungültige Auswahl).
    </div>
  <?php elseif (!empty($_GET['deleted'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#86efac;background:#f0fdf4;color:#166534;">
      <?php
        $nDel = (int)$_GET['deleted'];
        echo $nDel > 1
          ? esc_html((string)$nDel . ' Kacheln endgültig gelöscht.')
          : 'Eintrag wurde endgültig gelöscht.';
      ?>
      <?php if (!empty($_GET['delete_err'])): ?>
        <div style="font-size:12px;margin-top:4px;">Einige Einträge konnten nicht gelöscht werden (<?php echo esc_html((string)(int)$_GET['delete_err']); ?>).</div>
      <?php endif; ?>
    </div>
  <?php elseif (!empty($_GET['delete_err'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#fda4af;background:#fff1f2;color:#9f1239;">
      Löschen fehlgeschlagen (Datei nicht gefunden, keine Berechtigung oder ungültige Auswahl).
    </div>
  <?php elseif (!empty($managerPlanDeleted)): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#86efac;background:#f0fdf4;color:#166534;">
      Geplanter Test wurde aus AssessmentManager.csv entfernt.
    </div>
  <?php endif; ?>
  <div class="card rkrk-card" id="assessment-manager-card">
    <?php if ($examAdminShowPlanningDeletes): ?>
    <form method="post" id="mgr-archive-form">
      <input type="hidden" name="return_q" value="<?php echo esc_html($examAdminPlanningReturnQ); ?>">
      <?php if (function_exists('rkrk_assess_echo_setting_hidden_input')) { rkrk_assess_echo_setting_hidden_input(); } ?>
      <div id="mgr-archive-payloads"></div>
    </form>
    <?php endif; ?>
    <div class="kpis" style="margin-bottom:12px;">
      <div class="kpi"><b>Geplante Tests</b><br><?php echo esc_html((string)count($managerRows)); ?></div>
      <div class="kpi"><b>Piloten in Planung</b><br><?php echo esc_html((string)count($managerPilotNames)); ?></div>
      <div class="kpi"><b>Ergebnisübersicht</b><br><?php echo esc_html((string)$practicalTotal); ?></div>
      <div class="kpi"><b>Beurteilungen</b><br><?php echo esc_html((string)$evaluationTotal); ?></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
      <div>
        <h3 style="margin-top:0;">Assessment Manager (Standard)</h3>
        <?php if ($examAdminShowPlanningDeletes): ?>
        <p style="margin:0 0 6px;color:#475569;font-size:13px;">Mehrfachauswahl: Kacheln per Checkbox markieren, dann «Archivieren» (nach results/archive/, Links wieder frei) oder «Löschen» (endgültig). Einzelnes Löschen bleibt per × am Chip möglich. <a href="<?php echo esc_html($resultsHref, ENT_QUOTES, 'UTF-8'); ?>">Ergebnisübersicht</a></p>
        <?php endif; ?>
        <p style="margin:0;color:#64748b;font-size:12px;">Klick auf geplanten Test = bearbeiten · Rechtsklick = Planung löschen · Checkbox = auswählen (archivieren oder löschen).</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <style>
          .mgr-view-toggle{display:inline-flex;align-items:center;gap:2px;padding:3px;border:1px solid #c5d0c3;border-radius:999px;background:#f3f6f2;box-shadow:inset 0 1px 2px rgba(15,23,42,.04);}
          .mgr-view-toggle a{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;border:0;background:transparent;color:#475569;font-size:12px;font-weight:600;line-height:1;text-decoration:none !important;white-space:nowrap;transition:background .15s ease,color .15s ease,box-shadow .15s ease;}
          .mgr-view-toggle a svg{width:14px;height:14px;flex:0 0 auto;opacity:.85;}
          .mgr-view-toggle a:hover{background:#fff;color:#1f2937;}
          .mgr-view-toggle a.is-active{background:#48673a;color:#fff;box-shadow:0 1px 3px rgba(72,103,58,.35);}
          .mgr-view-toggle a.is-active svg{opacity:1;}
          .mgr-view-toggle a:focus-visible{outline:2px solid #48673a;outline-offset:2px;}
          .mgr-tile{position:relative;}
          .mgr-tile.is-selected{outline:2px solid #48673a;outline-offset:1px;border-radius:5px;}
          .mgr-tile-select{display:inline-flex;align-items:flex-start;padding:1px 0 0;cursor:pointer;flex:0 0 auto;}
          .mgr-tile-select input{width:12px;height:12px;margin:0;accent-color:#48673a;cursor:pointer;}
          .mgr-archive-bar{position:sticky;bottom:8px;z-index:40;display:none;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;padding:10px 12px;border:1px solid #c5d0c3;border-radius:10px;background:#f8faf8;box-shadow:0 6px 18px rgba(15,23,42,.12);}
          .mgr-archive-bar.is-visible{display:flex;}
        </style>
        <div class="mgr-view-toggle" role="group" aria-label="Ansicht wählen">
          <a
            href="<?php echo htmlspecialchars($planningChipsHref, ENT_QUOTES, 'UTF-8'); ?>"
            class="<?php echo $managerFilterLayout === 'chips' ? 'is-active' : ''; ?>"
            <?php echo $managerFilterLayout === 'chips' ? 'aria-current="page"' : ''; ?>
            title="Block-Ansicht"
          >
            <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2" fill="currentColor"/><rect x="9" y="1.5" width="5.5" height="5.5" rx="1.2" fill="currentColor"/><rect x="1.5" y="9" width="5.5" height="5.5" rx="1.2" fill="currentColor"/><rect x="9" y="9" width="5.5" height="5.5" rx="1.2" fill="currentColor"/></svg>
            <span>Blöcke</span>
          </a>
          <a
            href="<?php echo htmlspecialchars($planningListHref, ENT_QUOTES, 'UTF-8'); ?>"
            class="<?php echo $managerFilterLayout === 'list' ? 'is-active' : ''; ?>"
            <?php echo $managerFilterLayout === 'list' ? 'aria-current="page"' : ''; ?>
            title="Listenansicht"
          >
            <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><rect x="1.5" y="2.2" width="13" height="2.2" rx="1" fill="currentColor"/><rect x="1.5" y="6.9" width="13" height="2.2" rx="1" fill="currentColor"/><rect x="1.5" y="11.6" width="13" height="2.2" rx="1" fill="currentColor"/></svg>
            <span>Liste</span>
          </a>
        </div>
        <?php if ($canUseExtendedAdmin): ?>
        <button id="manager-new-btn" class="btn btn-primary" type="button">+ Neue Test-Serie</button>
        <?php endif; ?>
        <?php if ($examAdminShowPlanningDeletes): ?>
        <button id="mgr-archive-select-all" class="ql-btn" type="button" title="Alle sichtbaren Kacheln auswählen">Alle auswählen</button>
        <button id="mgr-archive-submit" class="btn" type="submit" form="mgr-archive-form" data-mgr-action="archive" style="background:#e8f0e4;border-color:#b7c9b0;color:#1f3d18;">Archivieren</button>
        <button id="mgr-delete-submit" class="btn" type="submit" form="mgr-archive-form" data-mgr-action="delete" style="background:#fee2e2;border-color:#f5b5b5;color:#8b1d1d;">Löschen</button>
        <?php endif; ?>
      </div>
    </div>

    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end;margin-bottom:10px;">
      <input type="hidden" name="page" value="planning">
      <input type="hidden" name="m_layout" value="<?php echo esc_html($managerFilterLayout); ?>">
      <div>
        <label><b>Von Datum</b></label><br>
        <input class="text-input" type="date" name="m_from" value="<?php echo esc_html($managerFilterFrom); ?>">
      </div>
      <div>
        <label><b>Bis Datum</b></label><br>
        <input class="text-input" type="date" name="m_to" value="<?php echo esc_html($managerFilterTo); ?>">
      </div>
      <div>
        <label><b>Pilot</b></label><br>
        <input class="text-input" type="text" name="m_pilot" value="<?php echo esc_html($managerFilterPilot); ?>" placeholder="Name enthält">
      </div>
      <div>
        <label><b>Test</b></label><br>
        <input class="text-input" type="text" name="m_test" value="<?php echo esc_html($managerFilterTest); ?>" placeholder="Test enthält">
      </div>
      <div>
        <label><b>Status</b></label><br>
        <select class="text-input" name="m_status">
          <option value="all"<?php echo ($managerFilterStatus==='all'?' selected':''); ?>>Alle</option>
          <option value="in_progress"<?php echo ($managerFilterStatus==='in_progress'?' selected':''); ?>>In progress</option>
          <option value="planned"<?php echo ($managerFilterStatus==='planned'?' selected':''); ?>>Geplant</option>
          <option value="planned_overdue"<?php echo ($managerFilterStatus==='planned_overdue'?' selected':''); ?>>Geplant / überfällig</option>
          <option value="passed"<?php echo ($managerFilterStatus==='passed'?' selected':''); ?>>Bestanden</option>
          <option value="failed"<?php echo ($managerFilterStatus==='failed'?' selected':''); ?>>Nicht bestanden</option>
        </select>
      </div>
      <div>
        <label><b>Art</b></label><br>
        <select class="text-input" name="m_kind" title="RKRS+ Prüfung: Theorie im Prüfungsmodus, Einsatzprüfung, Materialprüfung">
          <option value="all"<?php echo ($managerFilterKind==='all'?' selected':''); ?>>Alle</option>
          <option value="rkrsplus_exam"<?php echo ($managerFilterKind==='rkrsplus_exam'?' selected':''); ?>>RKRS+ Prüfung</option>
          <option value="progress"<?php echo ($managerFilterKind==='progress'?' selected':''); ?>>Nur In progress</option>
          <option value="planned"<?php echo ($managerFilterKind==='planned'?' selected':''); ?>>Nur geplant</option>
          <option value="result"<?php echo ($managerFilterKind==='result'?' selected':''); ?>>Nur Resultate</option>
          <option value="theory"<?php echo ($managerFilterKind==='theory'?' selected':''); ?>>Theorie</option>
          <option value="assessment"<?php echo ($managerFilterKind==='assessment'?' selected':''); ?>>Assessment</option>
          <option value="evaluation"<?php echo ($managerFilterKind==='evaluation'?' selected':''); ?>>Beurteilung</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="ql-btn" href="<?php echo htmlspecialchars($planningHref, ENT_QUOTES, 'UTF-8'); ?>">Reset</a>
      </div>
    </form>

    <div style="margin:0 0 10px;color:#64748b;font-size:12px;">
      <?php echo esc_html((string)count($managerFlatRows)); ?> Einträge
      <?php if ($managerFilterLayout === 'list'): ?> · Listenansicht<?php else: ?> · Block-Ansicht<?php endif; ?>
    </div>

    <?php if ($managerFilterLayout === 'list'): ?>
      <?php if (empty($managerFlatRows)): ?>
        <div style="color:#6b7280;">Keine Einträge für die aktuellen Filter.</div>
      <?php else: ?>
      <div style="overflow:auto;">
      <table class="manager-flat-table" style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr>
            <?php if ($examAdminShowPlanningDeletes): ?>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;width:28px;" title="Auswählen"></th>
            <?php endif; ?>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;white-space:nowrap;">Datum</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Pilot</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Art</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Test</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Status</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">EXAM</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Details</th>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;white-space:nowrap;">Report</th>
            <?php if ($examAdminShowPlanningDeletes || $canUseExtendedAdmin): ?>
            <th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e5e7eb;width:90px;">Aktion</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($managerFlatRows as $fr): ?>
            <?php
              $frDate = exam_admin_format_short_date((string)($fr['date'] ?? ''));
              if ($frDate === '' && !empty($fr['timestamp'])) {
                $frDate = date('d.m.y', (int)$fr['timestamp']);
              }
              $frGroup = (string)($fr['group'] ?? '');
              $frStatusCode = (string)($fr['status_code'] ?? '');
              $rowBg = '';
              $statusFg = '';
              if ($frGroup === 'progress' || $frStatusCode === 'in_progress') {
                $rowBg = 'background:#eff6ff;';
              } elseif ($frStatusCode === 'passed' || $frStatusCode === 'emp_g') {
                $rowBg = 'background:#f0fdf4;';
                $statusFg = 'color:#166534;font-weight:700;';
              } elseif ($frStatusCode === 'emp_o') {
                $rowBg = 'background:#fffbeb;';
                $statusFg = 'color:#92400e;font-weight:700;';
              } elseif ($frStatusCode === 'failed' || $frStatusCode === 'emp_r') {
                $rowBg = 'background:#fff1f2;';
                $statusFg = 'color:#9f1239;font-weight:700;';
              } elseif (strpos($frStatusCode, 'level_') === 0) {
                $rowBg = 'background:#f8fafc;';
              } elseif ($frStatusCode === 'planned_overdue') {
                $rowBg = 'background:#fff7ed;';
              }
              $frJson = '';
              if (!empty($fr['row_json']) && is_array($fr['row_json'])) {
                $frJson = htmlspecialchars(json_encode($fr['row_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
              }
              $frArchiveHtml = '';
              if ($examAdminShowPlanningDeletes) {
                if ($frGroup === 'planned' && !empty($fr['row_json']) && is_array($fr['row_json'])) {
                  $frArchiveHtml = exam_admin_archive_checkbox_html(exam_admin_archive_payload_from_planned($fr['row_json']));
                } elseif (!empty($fr['delete']) && is_array($fr['delete'])) {
                  $frArchiveHtml = exam_admin_archive_checkbox_html(exam_admin_archive_payload_from_entry($fr['delete']));
                }
              }
            ?>
            <tr style="<?php echo $rowBg; ?>" class="mgr-tile">
              <?php if ($examAdminShowPlanningDeletes): ?>
              <td style="padding:6px 4px;border-bottom:1px solid #f1f5f9;vertical-align:top;"><?php echo $frArchiveHtml !== '' ? $frArchiveHtml : ''; ?></td>
              <?php endif; ?>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;white-space:nowrap;vertical-align:top;"><?php echo esc_html($frDate !== '' ? $frDate : '-'); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;"><?php echo esc_html((string)($fr['pilot'] ?? '')); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;white-space:nowrap;"><?php echo esc_html((string)($fr['type_label'] ?? '')); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;">
                <?php if ($frJson !== '' && $canUseExtendedAdmin): ?>
                  <button type="button" class="manager-entry-btn" data-row="<?php echo $frJson; ?>" data-launch-url="<?php echo htmlspecialchars((string)($fr['launch_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="border:0;background:transparent;padding:0;color:#0f172a;cursor:pointer;text-align:left;font:inherit;">
                    <?php echo esc_html((string)($fr['test_label'] ?? '')); ?>
                  </button>
                <?php else: ?>
                  <?php echo esc_html((string)($fr['test_label'] ?? '')); ?>
                <?php endif; ?>
                <?php if (trim((string)($fr['series'] ?? '')) !== ''): ?>
                  <div style="font-size:11px;color:#64748b;"><?php echo esc_html((string)$fr['series']); ?></div>
                <?php endif; ?>
              </td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;white-space:nowrap;<?php echo $statusFg; ?>"><?php echo esc_html(exam_admin_strip_markers((string)($fr['status'] ?? ''))); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-family:ui-monospace,Consolas,monospace;font-size:12px;"><?php echo esc_html(trim((string)($fr['exam_code'] ?? '')) !== '' ? (string)$fr['exam_code'] : '–'); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#475569;font-size:12px;max-width:320px;"><?php echo esc_html(trim((string)($fr['details'] ?? '')) !== '' ? (string)$fr['details'] : (trim((string)($fr['note'] ?? '')) !== '' ? (string)$fr['note'] : '–')); ?></td>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;white-space:nowrap;">
                <?php
                  $frReport = trim((string)($fr['report_href'] ?? ''));
                  echo $frReport !== '' ? exam_admin_report_link_html($frReport, false) : '–';
                ?>
              </td>
              <?php if ($examAdminShowPlanningDeletes || $canUseExtendedAdmin): ?>
              <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;">
                <?php if ($frGroup === 'planned' && $canUseExtendedAdmin && trim((string)($fr['manager_id'] ?? '')) !== ''): ?>
                  <form method="post" style="margin:0;display:inline;" onsubmit="return confirm('Geplanten Test aus AssessmentManager.csv löschen?');">
                    <input type="hidden" name="manager_action" value="delete_entry">
                    <input type="hidden" name="m_id" value="<?php echo esc_html((string)$fr['manager_id']); ?>">
                    <button type="submit" class="ql-btn" style="padding:2px 8px;font-size:12px;border:1px solid #f5b5b5;background:#fee2e2;color:#8b1d1d;">Löschen</button>
                  </form>
                <?php elseif ($examAdminShowPlanningDeletes && !empty($fr['delete']) && is_array($fr['delete']) && trim((string)($fr['delete']['delete_kind'] ?? '')) !== ''): ?>
                  <?php echo exam_admin_result_delete_form_html($fr['delete'], $examAdminPlanningReturnQ, false); ?>
                <?php else: ?>
                  –
                <?php endif; ?>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    <?php elseif (empty($managerPilotNames)): ?>
      <div style="color:#6b7280;"><?php echo ($managerFilterKind !== 'all') ? 'Keine Einträge für die aktuellen Filter.' : 'Noch keine Tests geplant. Mit „Neue Test-Serie“ kannst du sofort starten.'; ?></div>
    <?php else: ?>
      <table>
        <tr><th style="width:220px;">Pilot</th><th>Geplante Tests / Resultate</th></tr>
        <?php foreach ($managerPilotNames as $pilotName): ?>
          <?php
            $pilotTests = (isset($managerByPilot[$pilotName]) && is_array($managerByPilot[$pilotName])) ? $managerByPilot[$pilotName] : array();
            $pilotDone = $managerDoneByPilot[normalize_key((string)$pilotName)] ?? ($managerDoneByPilot[exam_admin_match_key((string)$pilotName)] ?? array());
            $pilotProgress = $managerProgressByPilot[normalize_key((string)$pilotName)] ?? ($managerProgressByPilot[exam_admin_match_key((string)$pilotName)] ?? array());
            $hideDoneForProgressFilter = ($managerFilterStatus === 'in_progress');
            if (empty($pilotTests) && empty($pilotProgress) && ($hideDoneForProgressFilter || empty($pilotDone))) {
                continue;
            }
            $chipWrapStyle = 'display:flex;gap:3px;flex-wrap:wrap;align-items:flex-start;';
            $chipInnerStyle = 'border-radius:4px;padding:2px 5px;min-width:78px;max-width:128px;text-align:left;';
            $chipTextStyle = 'font-size:8px;line-height:1.15;';
            $chipLineEllipsis = 'max-width:118px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:6px;">
                <b><?php echo esc_html((string)$pilotName); ?></b>
                <?php if ($canUseExtendedAdmin): ?>
                <button
                  type="button"
                  class="pilot-quick-add-btn"
                  data-pilot-name="<?php echo esc_html((string)$pilotName); ?>"
                  title="Neuer Test für diesen Piloten"
                  aria-label="Neuer Test für diesen Piloten"
                  style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1px solid #cfd8cc;border-radius:999px;background:#f8faf8;color:#334155;cursor:pointer;font-size:13px;line-height:1;padding:0;"
                >+</button>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <?php
                $plannedMatchKeys = array();
                $plannedDateStatus = array();
                $plannedStems = array();
                $plannedHrefs = array();
                $plannedExams = array();
                foreach ((array)$pilotTests as $prow) {
                    $pRef = trim((string)($prow['TestRef'] ?? $prow['QuizKey'] ?? ''));
                    $pType = trim((string)($prow['TestType'] ?? 'theory'));
                    $pLabel = $pRef;
                    if ($pType === 'theory' && isset($allQuizOptions[$pRef])) {
                        $pLabel = (string)$allQuizOptions[$pRef];
                    } elseif ($pType === 'assessment_template' && isset($assessmentTemplateOptionsByFile[$pRef])) {
                        $pLabel = (string)$assessmentTemplateOptionsByFile[$pRef];
                    } elseif ($pType === 'evaluation_template' && isset($evaluationTemplateOptionsByFile[$pRef])) {
                        $pLabel = (string)$evaluationTemplateOptionsByFile[$pRef];
                    }
                    $plannedMatchKeys[exam_admin_match_key($pRef)] = true;
                    $plannedMatchKeys[exam_admin_match_key($pLabel)] = true;
                    exam_admin_index_planned_identity($prow, $plannedStems, $plannedHrefs, $plannedExams);
                    $pMeta = exam_admin_manager_status_meta($prow);
                    $pTs = strtotime((string)($prow['DoneAt'] ?? ''));
                    if ($pTs === false || $pTs <= 0) $pTs = strtotime((string)($prow['PlannedAt'] ?? ''));
                    if ($pTs !== false && $pTs > 0) {
                        $plannedDateStatus[date('Y-m-d', $pTs) . '|' . (string)($pMeta['code'] ?? '')] = true;
                    }
                }
              ?>
              <?php if (!empty($pilotTests)): ?>
              <div style="<?php echo $chipWrapStyle; ?>">
                <?php foreach ($pilotTests as $row): ?>
                      <?php
                        $type = trim((string)($row['TestType'] ?? 'theory'));
                        $testRef = trim((string)($row['TestRef'] ?? $row['QuizKey'] ?? ''));
                        $testLabel = $testRef;
                        if ($type === 'theory' && isset($allQuizOptions[$testRef])) {
                            $testLabel = (string)$allQuizOptions[$testRef];
                        } elseif ($type === 'assessment_template' && isset($assessmentTemplateOptionsByFile[$testRef])) {
                            $testLabel = (string)$assessmentTemplateOptionsByFile[$testRef];
                        } elseif ($type === 'evaluation_template' && isset($evaluationTemplateOptionsByFile[$testRef])) {
                            $testLabel = (string)$evaluationTemplateOptionsByFile[$testRef];
                        }
                        $meta = exam_admin_manager_status_meta($row);
                        $btnBg = (string)$meta['bg'];
                        $btnFg = (string)$meta['fg'];
                        $isMaterialChip = !empty($row['_is_material'])
                            || exam_admin_is_material_assessment($testLabel, $testRef);
                        if ($isMaterialChip && $type === 'assessment_template') {
                            $matRating = trim((string)($row['_rating'] ?? ''));
                            $ec = exam_admin_empfehlung_colors($matRating !== '' ? $matRating : (string)$meta['label']);
                            if ((string)($ec['bg'] ?? '') !== '') {
                                $btnBg = (string)$ec['bg'];
                                $btnFg = (string)$ec['fg'];
                            }
                        } elseif (($type === 'assessment_template' || $type === 'evaluation_template') && trim((string)($row['_level'] ?? '')) !== '') {
                            $lc = exam_admin_level_colors((string)$row['_level']);
                            if ((string)$lc['bg'] !== '') {
                                $btnBg = (string)$lc['bg'];
                                $btnFg = (string)$lc['fg'];
                            }
                        } elseif ($type === 'evaluation_template') {
                            // Bereits in status_meta Stufenfarben; Meta-Werte beibehalten
                            $btnBg = (string)$meta['bg'];
                            $btnFg = (string)$meta['fg'];
                        }
                        $dateLabel = trim((string)$meta['date']) !== '' ? exam_admin_format_short_date((string)$meta['date']) : '';
                        $seriesTitle = trim((string)($row['SeriesTitle'] ?? '')) !== '' ? (string)$row['SeriesTitle'] : 'Standard-Serie';
                        $typeLabel = ($type === 'theory') ? 'Theorie Fragen' : (($type === 'assessment_template') ? 'Assessment Template' : 'Beurteilungs Template');
                        $resultLines = (isset($row['_results_list']) && is_array($row['_results_list']) && !empty($row['_results_list'])) ? implode("\n", $row['_results_list']) : '-';
                        $periodDoneLines = '-';
                        if (isset($pilotDone) && is_array($pilotDone) && !empty($pilotDone)) {
                            $periodDoneLines = implode("\n", array_map(function($done) {
                                $extra = exam_admin_hit_detail_lines($done);
                                $extraPart = !empty($extra) ? (' | ' . implode(' | ', $extra)) : '';
                                $ratingPart = '';
                                if (trim((string)($done['rating'] ?? '')) !== '') {
                                    $ratingPart = ' - Bewertung: ' . (string)$done['rating'];
                                }
                                $levelPart = '';
                                if (trim((string)($done['level'] ?? '')) !== '') {
                                    $levelPart = ' - Stufe: ' . (string)$done['level'];
                                }
                                return (string)($done['date'] ?? '') . ' - ' . (string)($done['label'] ?? '') . ' - ' . (string)($done['status'] ?? '') . $ratingPart . $levelPart . $extraPart;
                            }, $pilotDone));
                        }
                        $statusShort = exam_admin_chip_status_short(
                            (string)$meta['label'],
                            ($isMaterialChip && $type === 'assessment_template') ? '' : (string)($row['_level'] ?? '')
                        );
                        $tooltip = "Serie: " . $seriesTitle . "\n" . $typeLabel . ': ' . $testLabel . "\nStatus: " . (string)$meta['label'] . "\nGeplant: " . (string)($row['PlannedAt'] ?? '-') . "\nAusgefuehrt: " . (string)($row['DoneAt'] ?? '-') . "\nResultate zu diesem Test:\n" . $resultLines . "\nDurchgefuehrt im selben Zeitraum:\n" . $periodDoneLines . "\nNotiz: " . (string)($row['Note'] ?? '');
                        $rowJson = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        $launchUrl = trim((string)($row['_launch_url'] ?? ''));
                        $typeLetter = ($type === 'theory') ? 'T' : (($type === 'assessment_template') ? 'A' : 'B');
                        $chipMidLine = $typeLetter . ': ' . $testLabel;
                        $chipMidLineHtml = '<b>' . htmlspecialchars($typeLetter, ENT_QUOTES, 'UTF-8') . ':</b> ' . htmlspecialchars($testLabel, ENT_QUOTES, 'UTF-8');
                        $chipExamCode = trim((string)($row['_exam_code'] ?? $row['exam_code'] ?? ''));
                        if ($chipExamCode !== '') {
                            $tooltip .= "\nEXAM: " . $chipExamCode;
                        }
                        $chipReportHref = trim((string)($row['_report_href'] ?? ''));
                        $chipArchiveHtml = $examAdminShowPlanningDeletes
                          ? exam_admin_archive_checkbox_html(exam_admin_archive_payload_from_planned($row))
                          : '';
                      ?>
                  <?php if ($canUseExtendedAdmin): ?>
                  <div class="mgr-tile" style="display:inline-flex;align-items:flex-start;gap:1px;">
                  <?php echo $chipArchiveHtml; ?>
                  <button
                    type="button"
                    class="manager-entry-btn"
                    data-row="<?php echo $rowJson; ?>"
                    data-launch-url="<?php echo htmlspecialchars($launchUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    title="<?php echo esc_html($tooltip); ?>"
                    style="border:0;<?php echo $chipInnerStyle; ?>cursor:pointer;background:<?php echo esc_html($btnBg); ?>;color:<?php echo esc_html($btnFg); ?>;box-shadow:0 1px 1px rgba(0,0,0,.1);"
                  >
                  <?php else: ?>
                  <div
                    title="<?php echo esc_html($tooltip); ?>"
                    style="border:0;<?php echo $chipInnerStyle; ?>background:<?php echo esc_html($btnBg); ?>;color:<?php echo esc_html($btnFg); ?>;box-shadow:0 1px 1px rgba(0,0,0,.1);"
                  >
                  <?php endif; ?>
                    <?php if ($dateLabel !== ''): ?>
                    <div style="<?php echo $chipTextStyle; ?>font-weight:700;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($dateLabel); ?></div>
                    <?php endif; ?>
                    <div style="<?php echo $chipTextStyle; ?>opacity:.95;<?php echo $chipLineEllipsis; ?>"><?php echo isset($chipMidLineHtml) ? $chipMidLineHtml : esc_html($chipMidLine); ?></div>
                    <?php if ($chipExamCode !== ''): ?>
                    <div style="<?php echo $chipTextStyle; ?>opacity:.9;font-family:ui-monospace,Consolas,monospace;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($chipExamCode); ?></div>
                    <?php endif; ?>
                    <div style="<?php echo $chipTextStyle; ?>font-weight:700;margin-top:1px;"><?php echo esc_html($statusShort); ?></div>
                  <?php if ($canUseExtendedAdmin): ?>
                  </button>
                  <?php if ($chipReportHref !== ''): ?>
                    <?php echo exam_admin_report_link_html($chipReportHref, true); ?>
                  <?php endif; ?>
                  <form method="post" style="margin:0;" onsubmit="return confirm('Geplanten Test aus AssessmentManager.csv löschen?');">
                    <input type="hidden" name="manager_action" value="delete_entry">
                    <input type="hidden" name="m_id" value="<?php echo esc_html((string)($row['Id'] ?? '')); ?>">
                    <button type="submit" title="Planung löschen" aria-label="Planung löschen" style="border:1px solid #e5e7eb;border-radius:3px;background:#fff;color:#8b1d1d;cursor:pointer;font-size:10px;line-height:1;padding:1px 3px;">×</button>
                  </form>
                  </div>
                  <?php else: ?>
                  <?php if ($chipReportHref !== ''): ?>
                    <?php echo exam_admin_report_link_html($chipReportHref, true); ?>
                  <?php endif; ?>
                  </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
              <?php elseif (empty($pilotProgress)): ?>
                <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">Keine Planung vorhanden.</div>
              <?php endif; ?>
              <?php if (!empty($pilotProgress)): ?>
                <div style="<?php echo $chipWrapStyle; ?><?php echo !empty($pilotTests) ? 'margin-top:4px;' : ''; ?>">
                  <?php foreach ($pilotProgress as $ip): ?>
                    <?php
                      $ipDateShort = exam_admin_format_short_date((string)($ip['updated_at'] ?? $ip['date'] ?? ''));
                      $ipLabel = trim((string)($ip['label'] ?? 'Assessment'));
                      $ipStatus = trim((string)($ip['status'] ?? 'In progress'));
                      $ipExam = trim((string)($ip['exam_code'] ?? ''));
                      $ipQuiz = trim((string)($ip['quiz_key'] ?? ''));
                      $ipTooltip = "Status: " . $ipStatus . "\n" . $ipLabel
                        . "\nAktualisiert: " . trim((string)($ip['updated_at'] ?? '-'))
                        . "\nErstellt: " . trim((string)($ip['created_at'] ?? '-'));
                      if ($ipExam !== '') {
                        $ipTooltip .= "\nEXAM: " . $ipExam;
                      }
                      if ($ipQuiz !== '') {
                        $ipTooltip .= "\nQuiz: " . $ipQuiz;
                      }
                      if (trim((string)($ip['quiz_modus'] ?? '')) !== '') {
                        $ipTooltip .= "\nModus: " . trim((string)$ip['quiz_modus']);
                      }
                      if ((int)($ip['max_questions'] ?? 0) > 0) {
                        $ipTooltip .= "\nFortschritt: " . (int)($ip['answered'] ?? 0) . '/' . (int)$ip['max_questions'];
                      }
                      if (trim((string)($ip['progress_file'] ?? '')) !== '') {
                        $ipTooltip .= "\nTemplate-Datei: " . trim((string)$ip['progress_file']);
                      }
                      if (trim((string)($ip['progress_path'] ?? '')) !== '') {
                        $ipTooltip .= "\nDatei: " . trim((string)$ip['progress_path']);
                      }
                      $ipStatusShort = (stripos($ipStatus, 'in progress') === 0 || $ipStatus === 'In progress')
                        ? exam_admin_chip_status_short('In progress')
                        : exam_admin_chip_status_short($ipStatus);
                      if ((int)($ip['max_questions'] ?? 0) > 0) {
                        $ipStatusShort = 'läuft ' . (int)($ip['answered'] ?? 0) . '/' . (int)$ip['max_questions'];
                      }
                      $ipArchiveHtml = $examAdminShowPlanningDeletes
                        ? exam_admin_archive_checkbox_html(exam_admin_archive_payload_from_entry($ip))
                        : '';
                    ?>
                    <span class="mgr-tile" style="display:inline-flex;align-items:flex-start;gap:1px;">
                    <?php echo $ipArchiveHtml; ?>
                    <span title="<?php echo esc_html($ipTooltip); ?>" style="display:inline-flex;align-items:flex-start;gap:1px;<?php echo $chipInnerStyle; ?>background:#fff;border:2px solid #2563eb;color:#1e3a8a;">
                      <span style="display:inline-block;min-width:0;">
                        <?php if ($ipDateShort !== ''): ?>
                        <div style="<?php echo $chipTextStyle; ?>font-weight:700;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($ipDateShort); ?></div>
                        <?php endif; ?>
                        <div style="<?php echo $chipTextStyle; ?>opacity:.95;<?php echo $chipLineEllipsis; ?>"><?php echo exam_admin_chip_type_title_html($ipLabel); ?></div>
                        <?php if ($ipExam !== ''): ?>
                        <div style="<?php echo $chipTextStyle; ?>opacity:.9;font-family:ui-monospace,Consolas,monospace;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($ipExam); ?></div>
                        <?php endif; ?>
                        <div style="<?php echo $chipTextStyle; ?>font-weight:700;margin-top:1px;"><?php echo esc_html($ipStatusShort); ?></div>
                      </span>
                      <?php if ($examAdminShowPlanningDeletes): ?>
                        <?php echo exam_admin_result_delete_form_html($ip, $examAdminPlanningReturnQ, true); ?>
                      <?php endif; ?>
                    </span>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php
                $unscheduledDone = array();
                if (!$hideDoneForProgressFilter) {
                foreach ((array)$pilotDone as $done) {
                    if (!exam_admin_done_is_planned_equivalent(
                        $done,
                        $plannedMatchKeys,
                        $plannedDateStatus,
                        $plannedStems,
                        $plannedHrefs,
                        $plannedExams
                    )) {
                        $unscheduledDone[] = $done;
                    }
                }
                }
              ?>
              <?php if (!empty($unscheduledDone)): ?>
                <div style="<?php echo $chipWrapStyle; ?>margin-top:4px;">
                  <?php foreach ($unscheduledDone as $ud): ?>
                    <?php
                      $udRating = trim((string)($ud['rating'] ?? ''));
                      $udLevel = trim((string)($ud['level'] ?? ''));
                      $udIsEval = ((string)($ud['delete_kind'] ?? '') === 'evaluation_json')
                        || ((string)($ud['type'] ?? '') === 'evaluation')
                        || (mb_stripos(trim((string)($ud['label'] ?? '')), 'Beurteilung:', 0, 'UTF-8') === 0);
                      $udIsMaterial = !empty($ud['is_material'])
                        || exam_admin_is_material_assessment((string)($ud['label'] ?? ''), (string)($ud['result_id'] ?? ''));
                      $bg = '#f59e0b';
                      $fg = '#111827';
                      $udMaterialEmp = false;
                      if ($udIsMaterial && $udRating !== '') {
                        $ec = exam_admin_empfehlung_colors($udRating);
                        if ((string)($ec['bg'] ?? '') !== '') {
                          $bg = (string)$ec['bg'];
                          $fg = (string)$ec['fg'];
                          $udMaterialEmp = true;
                        }
                      }
                      if (!$udMaterialEmp && ($udIsEval || $udLevel !== '')) {
                        // Live-Beurteilung / Stufe: nur Stufenfarben, kein Bestanden/NIO
                        if ($udLevel === '' && $udRating !== '') {
                          $udLevel = exam_admin_extract_level_number($udRating);
                        }
                        $lc = exam_admin_level_colors($udLevel);
                        if ((string)$lc['bg'] !== '') {
                          $bg = (string)$lc['bg'];
                          $fg = (string)$lc['fg'];
                        } else {
                          $bg = '#64748b';
                          $fg = '#ffffff';
                        }
                      } elseif (!$udMaterialEmp) {
                        $isGreen = ($udRating !== '') ? (exam_admin_is_positive_result($udRating) && !exam_admin_is_negative_result($udRating)) : (trim((string)($ud['status'] ?? '')) === 'Bestanden');
                        $isRed = ($udRating !== '') ? exam_admin_is_negative_result($udRating) : (trim((string)($ud['status'] ?? '')) === 'Nicht bestanden');
                        if ($isGreen) { $bg = '#16a34a'; $fg = '#ffffff'; }
                        elseif ($isRed) { $bg = '#dc2626'; $fg = '#ffffff'; }
                      }
                      $udDetails = exam_admin_hit_detail_lines($ud);
                      $udStatusForTitle = $udMaterialEmp
                        ? exam_admin_strip_markers($udRating)
                        : ($udIsEval
                          ? (($udLevel !== '') ? ('Stufe ' . exam_admin_extract_level_number($udLevel)) : 'Ausgeführt')
                          : trim((string)($ud['status'] ?? '')));
                      $udTitle = 'Ungeplant: ' . trim((string)($ud['label'] ?? '')) . "\nDatum: " . trim((string)($ud['date'] ?? '-')) . "\nStatus: " . $udStatusForTitle;
                      if (!empty($udDetails)) {
                        $udTitle .= "\n" . implode("\n", $udDetails);
                      }
                      $udDateShort = exam_admin_format_short_date((string)($ud['date'] ?? ''));
                      if ($udMaterialEmp) {
                        $udStatusText = exam_admin_chip_status_short($udRating);
                      } elseif ($udIsEval || $udLevel !== '') {
                        $udStatusText = exam_admin_chip_status_short('', $udLevel !== '' ? $udLevel : $udStatusForTitle);
                        if ($udStatusText === '' || $udStatusText === 'Ausgeführt') {
                          $udStatusText = $udLevel !== '' ? ('S' . exam_admin_extract_level_number($udLevel)) : 'Ausgeführt';
                        }
                      } elseif ($udRating !== '') {
                        $udStatusText = exam_admin_chip_status_short($udRating);
                      } else {
                        $udStatusText = exam_admin_chip_status_short(trim((string)($ud['status'] ?? '')));
                      }
                      $udTitleLine = trim((string)($ud['label'] ?? ''));
                      $udExamCode = trim((string)($ud['exam_code'] ?? ''));
                      $udReportHref = trim((string)($ud['report_href'] ?? ''));
                      if ($udReportHref === '') {
                        $udReportHref = exam_admin_report_href_for_entry($ud, (string)$pilotName);
                      }
                      $udArchiveHtml = $examAdminShowPlanningDeletes
                        ? exam_admin_archive_checkbox_html(exam_admin_archive_payload_from_entry($ud))
                        : '';
                    ?>
                    <span class="mgr-tile" style="display:inline-flex;align-items:flex-start;gap:1px;">
                    <?php echo $udArchiveHtml; ?>
                    <span title="<?php echo esc_html($udTitle); ?>" style="display:inline-flex;align-items:flex-start;gap:1px;<?php echo $chipInnerStyle; ?>background:<?php echo esc_html($bg); ?>;color:<?php echo esc_html($fg); ?>;box-shadow:0 1px 1px rgba(0,0,0,.1);">
                      <span style="display:inline-block;min-width:0;">
                      <?php if ($udDateShort !== ''): ?>
                      <div style="<?php echo $chipTextStyle; ?>font-weight:700;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($udDateShort); ?></div>
                      <?php endif; ?>
                      <div style="<?php echo $chipTextStyle; ?>opacity:.95;<?php echo $chipLineEllipsis; ?>"><?php echo exam_admin_chip_type_title_html($udTitleLine); ?></div>
                      <?php if ($udExamCode !== ''): ?>
                      <div style="<?php echo $chipTextStyle; ?>opacity:.9;font-family:ui-monospace,Consolas,monospace;<?php echo $chipLineEllipsis; ?>"><?php echo esc_html($udExamCode); ?></div>
                      <?php endif; ?>
                      <?php if ($udStatusText !== ''): ?>
                      <div style="<?php echo $chipTextStyle; ?>font-weight:700;margin-top:1px;"><?php echo esc_html($udStatusText); ?></div>
                      <?php endif; ?>
                      </span>
                      <?php if ($udReportHref !== ''): ?>
                        <?php echo exam_admin_report_link_html($udReportHref, true); ?>
                      <?php endif; ?>
                      <?php if ($examAdminShowPlanningDeletes): ?>
                        <?php echo exam_admin_result_delete_form_html($ud, $examAdminPlanningReturnQ, true); ?>
                      <?php endif; ?>
                    </span>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <?php if ($examAdminShowPlanningDeletes): ?>
    <div id="mgr-archive-bar" class="mgr-archive-bar" role="status">
      <strong id="mgr-archive-count">0 ausgewählt</strong>
      <span style="color:#475569;font-size:13px;">Auswahl archivieren (results/archive/, Links frei) oder endgültig löschen.</span>
      <button class="btn" type="submit" form="mgr-archive-form" data-mgr-action="archive" style="background:#e8f0e4;border-color:#b7c9b0;color:#1f3d18;margin-left:auto;">Auswahl archivieren</button>
      <button class="btn" type="submit" form="mgr-archive-form" data-mgr-action="delete" style="background:#fee2e2;border-color:#f5b5b5;color:#8b1d1d;">Auswahl löschen</button>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($canUseExtendedAdmin): ?>
  <div id="manager-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;padding:18px;overflow:auto;">
    <div style="max-width:900px;margin:24px auto;background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 30px rgba(0,0,0,.2);">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px;">
        <h4 id="manager-modal-title" style="margin:0;">Test bearbeiten</h4>
        <button type="button" id="manager-close-btn" class="ql-btn">Schliessen</button>
      </div>
      <form method="post" id="manager-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;align-items:end;">
        <input type="hidden" name="manager_action" value="save_entry">
        <input type="hidden" name="m_id" id="m_id" value="">
        <input type="hidden" name="m_series_id" id="m_series_id" value="">
        <input type="hidden" name="m_source" id="m_source" value="personnel">
        <div>
          <label><b>Test-Serie</b></label><br>
          <input class="text-input" type="text" name="m_series_title" id="m_series_title" placeholder="z.B. Grundausbildung Mai">
        </div>
        <div>
          <label><b>Pilot (Liste oder manuell)</b></label><br>
          <input class="text-input" list="manager-persons" name="m_person" id="m_person" required>
          <datalist id="manager-persons">
            <?php foreach ($managerPersonOptions as $pName): ?>
              <option value="<?php echo esc_html((string)$pName); ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div>
          <label><b>Test Typ</b></label><br>
          <select class="text-input" name="m_test_type" id="m_test_type">
            <option value="theory">Theorie Fragen</option>
            <option value="assessment_template">Assessment Template</option>
            <option value="evaluation_template">Beurteilungs Template</option>
          </select>
        </div>
        <div>
          <label><b>Test Auswahl</b></label><br>
          <select class="text-input" name="m_test_ref" id="m_test_ref"></select>
        </div>
        <div>
          <label><b>Oder Test manuell</b></label><br>
          <input class="text-input" type="text" name="m_test_manual" id="m_test_manual" placeholder="Freitext, optional">
        </div>
        <div>
          <label><b>Geplant am</b></label><br>
          <input class="text-input" type="date" name="m_planned" id="m_planned">
        </div>
        <div>
          <label><b>Ausgefuehrt am</b></label><br>
          <input class="text-input" type="date" name="m_done" id="m_done">
        </div>
        <div>
          <label><b>Resultat</b></label><br>
          <select class="text-input" name="m_result_status" id="m_result_status">
            <option value="">Offen</option>
            <option value="Bestanden">Bestanden</option>
            <option value="Nicht bestanden">Nicht bestanden</option>
          </select>
        </div>
        <div style="grid-column:1 / -1;">
          <label><b>Notiz</b></label><br>
          <input class="text-input" type="text" name="m_note" id="m_note">
        </div>
        <div style="display:flex;gap:8px;grid-column:1 / -1;flex-wrap:wrap;">
          <button class="btn btn-primary" type="submit">Speichern</button>
          <button class="btn" id="manager-save-add-test" type="button">Als weiterer Test in gleicher Serie speichern</button>
        </div>
      </form>
      <form method="post" id="manager-delete-form" onsubmit="return confirm('Eintrag loeschen?');" style="margin-top:12px;display:none;">
        <input type="hidden" name="manager_action" value="delete_entry">
        <input type="hidden" name="m_id" id="delete_m_id" value="">
        <button class="btn" type="submit" style="background:#fee2e2;border-color:#f5b5b5;color:#8b1d1d;">Loeschen</button>
      </form>
    </div>
  </div>
  <script>
  (function () {
    const modal = document.getElementById('manager-modal');
    if (!modal) return;
    const form = document.getElementById('manager-form');
    const deleteForm = document.getElementById('manager-delete-form');
    const testType = document.getElementById('m_test_type');
    const testRef = document.getElementById('m_test_ref');
    const fields = {
      id: document.getElementById('m_id'),
      seriesId: document.getElementById('m_series_id'),
      seriesTitle: document.getElementById('m_series_title'),
      person: document.getElementById('m_person'),
      source: document.getElementById('m_source'),
      planned: document.getElementById('m_planned'),
      done: document.getElementById('m_done'),
      result: document.getElementById('m_result_status'),
      note: document.getElementById('m_note'),
      testManual: document.getElementById('m_test_manual'),
      deleteId: document.getElementById('delete_m_id'),
      title: document.getElementById('manager-modal-title')
    };
    const optionsByType = {
      theory: [
        <?php foreach ($allQuizOptions as $qKey => $qTitle): ?>
        { value: <?php echo json_encode((string)$qKey); ?>, label: <?php echo json_encode((string)$qTitle); ?> },
        <?php endforeach; ?>
      ],
      assessment_template: [
        <?php foreach ($assessmentTemplateOptionsByFile as $tplFile => $tplTitle): ?>
        { value: <?php echo json_encode((string)$tplFile); ?>, label: <?php echo json_encode((string)$tplTitle); ?> },
        <?php endforeach; ?>
      ],
      evaluation_template: [
        <?php foreach ($evaluationTemplateOptionsByFile as $tplFile => $tplTitle): ?>
        { value: <?php echo json_encode((string)$tplFile); ?>, label: <?php echo json_encode((string)$tplTitle); ?> },
        <?php endforeach; ?>
      ]
    };
    const renderTestOptions = (type, selectedValue) => {
      const list = optionsByType[type] || [];
      testRef.innerHTML = '';
      if (!list.length) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Keine Auswahl vorhanden';
        testRef.appendChild(opt);
        return;
      }
      list.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.value;
        opt.textContent = item.label;
        if (String(item.value) === String(selectedValue || '')) opt.selected = true;
        testRef.appendChild(opt);
      });
      if (!selectedValue) testRef.selectedIndex = 0;
    };
    const openModal = (row) => {
      const isEdit = !!row;
      fields.title.textContent = isEdit ? 'Test bearbeiten' : 'Neue Test-Serie';
      fields.id.value = isEdit ? (row.Id || '') : '';
      fields.seriesId.value = isEdit ? (row.SeriesId || row.Id || '') : '';
      fields.seriesTitle.value = isEdit ? (row.SeriesTitle || 'Standard-Serie') : '';
      fields.person.value = isEdit ? (row.Person || '') : '';
      fields.source.value = isEdit ? (row.Source || 'personnel') : 'personnel';
      const tType = isEdit ? (row.TestType || (row.QuizKey ? 'theory' : 'theory')) : 'theory';
      testType.value = tType;
      renderTestOptions(tType, isEdit ? (row.TestRef || row.QuizKey || '') : '');
      fields.planned.value = isEdit ? (row.PlannedAt || '') : '';
      fields.done.value = isEdit ? (row.DoneAt || '') : '';
      fields.result.value = isEdit ? (row.ResultStatus || '') : '';
      fields.note.value = isEdit ? (row.Note || '') : '';
      fields.testManual.value = '';
      fields.deleteId.value = isEdit ? (row.Id || '') : '';
      deleteForm.style.display = isEdit ? 'block' : 'none';
      modal.style.display = 'block';
    };
    document.getElementById('manager-new-btn')?.addEventListener('click', () => openModal(null));
    Array.from(document.querySelectorAll('.pilot-quick-add-btn')).forEach(btn => {
      btn.addEventListener('click', () => {
        const pilotName = String(btn.getAttribute('data-pilot-name') || '').trim();
        openModal(null);
        if (pilotName && fields.person) fields.person.value = pilotName;
      });
    });
    document.getElementById('manager-close-btn')?.addEventListener('click', () => { modal.style.display = 'none'; });
    modal.addEventListener('click', (ev) => {
      if (ev.target === modal) modal.style.display = 'none';
    });
    testType?.addEventListener('change', () => renderTestOptions(testType.value, ''));
    Array.from(document.querySelectorAll('.manager-entry-btn')).forEach(btn => {
      btn.addEventListener('click', () => {
        let row = null;
        try { row = JSON.parse(btn.getAttribute('data-row') || '{}'); } catch (err) { row = null; }
        openModal(row);
      });
      btn.addEventListener('contextmenu', async (ev) => {
        ev.preventDefault();
        let row = null;
        try { row = JSON.parse(btn.getAttribute('data-row') || '{}'); } catch (err) { row = null; }
        const rowId = String((row && row.Id) ? row.Id : '').trim();
        if (!rowId) {
          alert('Kein geplanter Test zum Loeschen gefunden.');
          return;
        }
        const ok = confirm('Geplanten Test loeschen?');
        if (!ok) return;
        if (fields.deleteId) {
          fields.deleteId.value = rowId;
          deleteForm?.requestSubmit();
        }
      });
    });
    form?.addEventListener('submit', () => {
      const person = String(fields.person.value || '').trim();
      if (!person) {
        alert('Bitte Person eintragen.');
        return false;
      }
      const seriesTitle = String(fields.seriesTitle.value || '').trim();
      if (!seriesTitle) {
        alert('Bitte Test-Serie angeben.');
        return false;
      }
      return true;
    });
    document.getElementById('manager-save-add-test')?.addEventListener('click', () => {
      const seriesTitle = String(fields.seriesTitle.value || '').trim();
      const person = String(fields.person.value || '').trim();
      if (!person) {
        alert('Bitte Person eintragen.');
        return;
      }
      if (!seriesTitle) {
        alert('Bitte Test-Serie angeben.');
        return;
      }
      fields.id.value = '';
      form.requestSubmit();
    });
  })();
  </script>
  <script src="rkrk_assess_admin_archive.js"></script>
  <?php endif; ?>

  <?php endif; ?>

  <?php if ($adminPage === 'results'): ?>
  <?php
    $examAdminReturnQ = exam_admin_whitelist_results_query(isset($_GET) && is_array($_GET) ? $_GET : array());
    $examAdminShowDeletes = ($canUseExtendedAdmin && $isAdminMode);
  ?>
  <?php if (!empty($_GET['deleted'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#86efac;background:#f0fdf4;color:#166534;">
      Eintrag wurde gelöscht (CSV bzw. JSON angepasst).
    </div>
  <?php elseif (!empty($_GET['delete_err'])): ?>
    <div class="card rkrk-card" style="margin-bottom:12px;border-color:#fda4af;background:#fff1f2;color:#9f1239;">
      Löschen fehlgeschlagen (Datei nicht gefunden, keine Berechtigung oder ungültige Parameter).
    </div>
  <?php endif; ?>
  <div class="card rkrk-card">
    <h3 style="margin-top:0;">Theorie Prüfungen</h3>
    <form id="theory-filters-form" class="filters" method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:10px;">
      <input type="hidden" name="page" value="results">
      <div>
        <label for="quiz"><b>Test</b></label><br>
        <select id="quiz" name="quiz">
          <option value="">Alle</option>
          <?php foreach ($allQuizOptions as $qKey => $qTitle): ?>
            <option value="<?php echo esc_html($qKey); ?>"<?php echo ($quizFilter===$qKey?' selected':''); ?>><?php echo esc_html($qTitle); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="name"><b><?php echo esc_html($theoryParticipantLabel); ?></b></label><br>
        <input id="name" type="text" name="name" value="<?php echo esc_html($nameFilter); ?>" placeholder="z.B. Müller">
      </div>
      <div>
        <label for="theory_from"><b>Von Datum</b></label><br>
        <input id="theory_from" type="date" name="theory_from" value="<?php echo esc_html($theoryFromFilter); ?>">
      </div>
      <div>
        <label for="theory_to"><b>Bis Datum</b></label><br>
        <input id="theory_to" type="date" name="theory_to" value="<?php echo esc_html($theoryToFilter); ?>">
      </div>
      <div>
        <label for="status"><b>Status</b></label><br>
        <select id="status" name="status">
          <option value="all"<?php echo ($statusFilter==='all'?' selected':''); ?>>Alle</option>
          <option value="passed"<?php echo ($statusFilter==='passed'?' selected':''); ?>>Bestanden</option>
          <option value="failed"<?php echo ($statusFilter==='failed'?' selected':''); ?>>Nicht bestanden</option>
        </select>
      </div>
      <div style="display:flex;align-items:end;gap:8px;grid-column:1 / -1;">
        <a class="ql-btn" href="?page=results&amp;quiz=&amp;name=&amp;status=all&amp;theory_from=&amp;theory_to=&amp;assessment_template=<?php echo rawurlencode((string)$examFilterTemplate); ?>&amp;assessment_pilot=<?php echo rawurlencode((string)$examFilterPilot); ?>&amp;assessment_text=<?php echo rawurlencode((string)$examFilterText); ?>&amp;assessment_from=<?php echo rawurlencode((string)$examFilterFrom); ?>&amp;exam_to=<?php echo rawurlencode((string)$examFilterTo); ?>&amp;exam_status=<?php echo rawurlencode((string)$examStatusFilter); ?>">Reset</a>
      </div>
    </form>
    <script>
    // #region agent log
    (function () {
      const form = document.getElementById('theory-filters-form');
      const log = (hypothesisId, message, data) => {
        void hypothesisId; void message; void data;
      };
      if (!form) {
        log('H1', 'Theory filters form not found', { formId: 'theory-filters-form' });
        return;
      }
      const controls = Array.from(form.querySelectorAll('select,input[type="text"],input[type="date"]'));
      log('H16', 'Theory filters mounted for instant apply', { controls: controls.map(c => c.name) });
      let timer = null;
      const submitNow = () => {
        const fd = new FormData(form);
        log('H17', 'Theory filters auto-submit', { query: new URLSearchParams(fd).toString() });
        form.submit();
      };
      controls.forEach(control => {
        control.addEventListener('change', () => {
          log('H4', 'Theory filter change event', { name: control.name, value: control.value });
          submitNow();
        });
        if (control.type === 'text') {
          control.addEventListener('input', () => {
            log('H5', 'Theory filter text input event', { name: control.name, valueLength: String(control.value || '').length });
            if (timer) clearTimeout(timer);
            timer = setTimeout(submitNow, 350);
          });
        }
      });
    })();
    // #endregion
    </script>

    <div class="kpis" style="margin-bottom:14px;">
      <div class="kpi"><b>Versuche</b><br><?php echo esc_html($attemptCount); ?></div>
      <div class="kpi"><b>Teilnehmer</b><br><?php echo esc_html($participantCount); ?></div>
      <div class="kpi"><b>Durchschnitt</b><br><?php echo esc_html($avgPct); ?>%</div>
      <div class="kpi"><b>Bestehensquote</b><br><?php echo esc_html($passRate); ?>%</div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(360px,1fr)); gap:12px; margin-bottom:14px;">
      <div>
        <h4 style="margin:0 0 8px 0;">Uebersicht pro Test</h4>
        <table>
          <tr><th>Test</th><th>Versuche</th><th>Durchschnitt</th><th>Bestehensquote</th></tr>
          <?php foreach ($quizStats as $q): $qAvg = ($q['attempts']>0)?round($q['sum_pct']/$q['attempts'],1):0; $qPass = ($q['attempts']>0)?round(($q['passed']/$q['attempts'])*100,1):0; ?>
          <tr>
            <td><?php echo esc_html($q['title']); ?></td>
            <td><?php echo esc_html($q['attempts']); ?></td>
            <td><?php echo esc_html($qAvg); ?>%</td>
            <td><?php echo esc_html($qPass); ?>%</td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div>
        <h4 style="margin:0 0 8px 0;">Teilnehmerliste</h4>
        <table>
          <tr><th>Name</th><th>Versuche</th><th>Bestanden</th><th>Bester Score</th><th>Letzter Versuch</th></tr>
          <?php foreach ($participantRows as $p): ?>
          <tr>
            <td><?php echo esc_html($p['name']); ?></td>
            <td><?php echo esc_html($p['attempts']); ?></td>
            <td><?php echo esc_html($p['passed']); ?></td>
            <td><?php echo esc_html($p['best_pct']); ?>%</td>
            <td><?php echo esc_html($p['latest']); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    <h4 style="margin:0 0 8px 0;">Resultate (letzte Versuche zuerst)</h4>
    <style>
    .exam-theory-row--uebung td {
      background: #f8fafc;
      color: #64748b;
    }
    .exam-theory-row--uebung td.ok,
    .exam-theory-row--uebung td.bad {
      opacity: 0.88;
    }
    .exam-theory-art {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 0.88em;
      font-weight: 600;
      white-space: nowrap;
    }
    .exam-theory-art--uebung {
      background: #f1f5f9;
      color: #94a3b8;
      border: 1px solid #e2e8f0;
    }
    .exam-theory-art--pruefung {
      background: #dbeafe;
      color: #1e40af;
      border: 1px solid #93c5fd;
    }
    </style>
    <table style="margin-bottom:14px;">
      <tr><th>Zeit</th><th>Name</th><th>Test</th><th>Art</th><th>Richtig</th><th>Fragen</th><th>Score</th><th>Status</th><?php if ($examAdminShowDeletes): ?><th>Aktion</th><?php endif; ?></tr>
      <?php foreach ($theoryDisplayRows as $a): ?>
      <tr<?php echo exam_admin_theory_modus_is_uebung($a) ? ' class="exam-theory-row--uebung"' : ''; ?>>
        <td><?php echo esc_html($a['date']); ?></td>
        <td><?php echo esc_html($a['name']); ?></td>
        <td><?php echo esc_html($a['quiz_title']); ?></td>
        <td><?php echo exam_admin_theory_modus_badge_html($a); ?></td>
        <td><?php echo esc_html($a['richtig']); ?></td>
        <td><?php echo esc_html($a['anz']); ?></td>
        <td><?php
          $scoreTxt = (string)$a['pct'] . '%';
          if (!empty($a['use_points']) && (float)($a['punkte_max'] ?? 0) > 0 && function_exists('rkrk_assess_format_punkte')) {
              $scoreTxt .= ' (' . rkrk_assess_format_punkte($a['punkte_erreicht'] ?? 0) . '/' . rkrk_assess_format_punkte($a['punkte_max']) . ')';
          }
          echo esc_html($scoreTxt);
        ?></td>
        <td class="<?php echo ($a['passed']?'ok':'bad'); ?>"><?php echo ($a['passed']?'Bestanden':'Nicht bestanden'); ?></td>
        <?php if ($examAdminShowDeletes): ?>
        <td>
          <form method="post" style="margin:0;" onsubmit="return confirm('Diesen Theorie-Eintrag aus QuizResultate.json endgültig löschen?');">
            <input type="hidden" name="exam_admin_delete" value="1">
            <input type="hidden" name="delete_kind" value="theory_csv">
            <input type="hidden" name="csv_line_index" value="<?php echo (int)($a['csv_line_index']); ?>">
            <input type="hidden" name="delete_name" value="<?php echo esc_html((string)($a['name'] ?? '')); ?>">
            <input type="hidden" name="delete_date" value="<?php echo esc_html((string)($a['date'] ?? '')); ?>">
            <input type="hidden" name="delete_quiz" value="<?php echo esc_html((string)($a['quiz'] ?? '')); ?>">
            <input type="hidden" name="return_q" value="<?php echo esc_html($examAdminReturnQ); ?>">
            <?php if (function_exists('rkrk_assess_echo_setting_hidden_input')) { rkrk_assess_echo_setting_hidden_input(); } ?>
            <button type="submit" class="ql-btn" style="padding:6px 10px;font-size:13px;">Löschen</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </table>

  </div>

  <div class="card rkrk-card">
    <h3 style="margin-top:0;">Praktische Tests</h3>
    <form id="practical-filters-form" class="filters" method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:10px;">
      <input type="hidden" name="page" value="results">
      <input type="hidden" name="quiz" value="<?php echo esc_html($quizFilter); ?>">
      <input type="hidden" name="name" value="<?php echo esc_html($nameFilter); ?>">
      <input type="hidden" name="status" value="<?php echo esc_html($statusFilter); ?>">
      <input type="hidden" name="theory_from" value="<?php echo esc_html($theoryFromFilter); ?>">
      <input type="hidden" name="theory_to" value="<?php echo esc_html($theoryToFilter); ?>">
      <?php if ($canUseExtendedAdmin): ?>
      <div>
        <label for="assessment_template"><b>Template</b></label><br>
        <select id="assessment_template" name="assessment_template">
          <option value="">Alle</option>
          <?php foreach ($examTemplateOptions as $tplFile => $tplTitle): ?>
            <option value="<?php echo esc_html($tplFile); ?>"<?php echo ($examFilterTemplate===$tplFile?' selected':''); ?>><?php echo esc_html($tplTitle); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label for="assessment_pilot"><b><?php echo esc_html($practicalParticipantLabel); ?></b></label><br>
        <input id="assessment_pilot" type="text" name="assessment_pilot" value="<?php echo esc_html($examFilterPilot); ?>" placeholder="z.B. Müller">
      </div>
      <div>
        <label for="assessment_text"><b><?php echo esc_html($practicalSearchHint); ?></b></label><br>
        <input id="assessment_text" type="text" name="assessment_text" value="<?php echo esc_html($examFilterText); ?>" placeholder="Suchtext">
      </div>
      <div>
        <label for="assessment_from"><b>Von Datum</b></label><br>
        <input id="assessment_from" type="date" name="assessment_from" value="<?php echo esc_html($examFilterFrom); ?>">
      </div>
      <div>
        <label for="exam_to"><b>Bis Datum</b></label><br>
        <input id="exam_to" type="date" name="exam_to" value="<?php echo esc_html($examFilterTo); ?>">
      </div>
      <div>
        <label for="exam_status"><b>Status</b></label><br>
        <select id="exam_status" name="exam_status">
          <option value="">Alle</option>
          <?php foreach ($practicalStatusOptions as $statusKey => $statusLabel): ?>
            <option value="<?php echo esc_html($statusKey); ?>"<?php echo ($examStatusFilter===$statusKey?' selected':''); ?>><?php echo esc_html($statusLabel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;align-items:end;gap:8px;">
        <a class="ql-btn" href="?page=results&amp;quiz=<?php echo rawurlencode((string)$quizFilter); ?>&amp;name=<?php echo rawurlencode((string)$nameFilter); ?>&amp;status=<?php echo rawurlencode((string)$statusFilter); ?>&amp;theory_from=<?php echo rawurlencode((string)$theoryFromFilter); ?>&amp;theory_to=<?php echo rawurlencode((string)$theoryToFilter); ?>&amp;assessment_template=&amp;assessment_pilot=&amp;assessment_text=&amp;assessment_from=&amp;exam_to=&amp;exam_status=">Reset</a>
      </div>
    </form>
    <script>
    // #region agent log
    (function () {
      const theoryForm = document.getElementById('theory-filters-form');
      const practicalForm = document.getElementById('practical-filters-form');
      const log = (hypothesisId, message, data) => {
        void hypothesisId; void message; void data;
      };

      log('H11', 'Filter forms presence', {
        theoryFormFound: !!theoryForm,
        practicalFormFound: !!practicalForm
      });

      if (!practicalForm) return;

      const controls = Array.from(practicalForm.querySelectorAll('select,input[type="text"],input[type="date"]'));
      log('H18', 'Practical filters mounted for instant apply', { controls: controls.map(c => c.name) });
      let timer = null;
      const submitNow = () => {
        const fd = new FormData(practicalForm);
        log('H19', 'Practical filters auto-submit', { query: new URLSearchParams(fd).toString() });
        practicalForm.submit();
      };

      controls.forEach(control => {
        control.addEventListener('change', () => {
          log('H14', 'Practical filter change event', { name: control.name, value: control.value });
          submitNow();
        });
        if (control.type === 'text') {
          control.addEventListener('input', () => {
            log('H15', 'Practical filter text input event', { name: control.name, valueLength: String(control.value || '').length });
            if (timer) clearTimeout(timer);
            timer = setTimeout(submitNow, 350);
          });
        }
      });
    })();
    // #endregion
    </script>
    <div class="rkrk-card" style="margin-bottom:12px;">
      <h4 style="margin:0 0 8px 0;">Assessments pro Pilot</h4>
      <table>
        <tr><th>Pilot</th><th>Einträge</th><th>Bestanden</th><th>Nicht bestanden</th><th>Templates</th><th>Letzter Eintrag</th></tr>
        <?php if (empty($practicalPilotOverviewRows)): ?>
          <tr><td colspan="6" style="text-align:center;color:#6b7280;">Keine Assessments im aktuellen Filter.</td></tr>
        <?php else: ?>
          <?php foreach ($practicalPilotOverviewRows as $row): ?>
            <?php
              $tplParts = array();
              if (isset($row['templates']) && is_array($row['templates'])) {
                arsort($row['templates']);
                foreach ($row['templates'] as $tplName => $cnt) {
                  $tplParts[] = (string)$tplName . ' (' . (int)$cnt . ')';
                  if (count($tplParts) >= 3) break;
                }
              }
            ?>
            <tr>
              <td><?php echo esc_html((string)($row['name'] ?? '')); ?></td>
              <td><?php echo esc_html((string)($row['attempts'] ?? 0)); ?></td>
              <td><?php echo esc_html((string)($row['passed'] ?? 0)); ?></td>
              <td><?php echo esc_html((string)($row['failed'] ?? 0)); ?></td>
              <td><?php echo esc_html(implode(', ', $tplParts)); ?></td>
              <td><?php echo esc_html((string)($row['latest'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </table>
    </div>
    <div class="kpis" style="margin-bottom:14px;">
      <div class="kpi"><b>Praktische Einträge</b><br><?php echo esc_html($practicalTotal); ?></div>
      <div class="kpi"><b>Teilnehmer</b><br><?php echo esc_html($practicalParticipantCount); ?></div>
      <div class="kpi"><b>Templates</b><br><?php echo esc_html($practicalTemplateCount); ?></div>
      <div class="kpi"><b>Bestehensquote</b><br><?php echo esc_html($practicalPassRate); ?>%</div>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(360px,1fr)); gap:12px; margin-bottom:14px;">
      <div>
        <h4 style="margin:0 0 8px 0;">Uebersicht pro Template</h4>
        <table>
          <tr><th>Template</th><th>Einträge</th><th>Bestehensquote</th></tr>
          <?php foreach ($practicalTemplateStats as $s): $tplPass = ($s['count']>0) ? round((($s['count'] - $s['red']) / $s['count']) * 100, 1) : 0; ?>
          <tr>
            <td><?php echo esc_html($s['title']); ?></td>
            <td><?php echo esc_html($s['count']); ?></td>
            <td><?php echo esc_html($tplPass); ?>%</td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div>
        <h4 style="margin:0 0 8px 0;">Teilnehmerliste</h4>
        <table>
          <tr><th>Name</th><th>Einträge</th><th>Letzter Eintrag</th></tr>
          <?php foreach ($practicalParticipantRows as $p): ?>
          <tr>
            <td><?php echo esc_html($p['name']); ?></td>
            <td><?php echo esc_html($p['attempts']); ?></td>
            <td><?php echo esc_html($p['latest']); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
    <table>
      <tr><th>Datum &amp; Zeit</th><th>Name</th><th>Test (Template)</th><th>Resultate</th><th>Gesamtbewertung</th><?php if ($examAdminShowDeletes): ?><th>Aktion</th><?php endif; ?></tr>
      <?php if (empty($practicalDisplayRows)): ?>
      <tr><td colspan="<?php echo $examAdminShowDeletes ? '6' : '5'; ?>" style="text-align:center;color:#6b7280;">Keine Assessment gefunden.</td></tr>
      <?php else: ?>
      <?php foreach ($practicalDisplayRows as $row): ?>
      <tr>
        <td><?php echo esc_html($row['datetime']); ?></td>
        <td>
          <a href="<?php echo htmlspecialchars(rkrk_assess_append_setting_href('rkrk_assess.php?mode=form&file=' . rawurlencode((string)$row['tpl_file']) . '&edit=' . rawurlencode((string)$row['id']) . '&PersPick=on'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_html((string)$row['name']); ?></a>
        </td>
        <td><?php echo esc_html($row['template']); ?></td>
        <td><?php echo esc_html($row['test_info']); ?></td>
        <td><?php echo exam_admin_rating_badge((string)$row['overall_rating']); ?></td>
        <?php if ($examAdminShowDeletes): ?>
        <td>
          <form method="post" style="margin:0;" onsubmit="return confirm('Dieses Assessment-Ergebnis (JSON) endgültig löschen?');">
            <input type="hidden" name="exam_admin_delete" value="1">
            <input type="hidden" name="delete_kind" value="assessment_json">
            <input type="hidden" name="result_id" value="<?php echo esc_html((string)$row['id']); ?>">
            <input type="hidden" name="return_q" value="<?php echo esc_html($examAdminReturnQ); ?>">
            <button type="submit" class="ql-btn" style="padding:6px 10px;font-size:13px;">Löschen</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>

  <div class="card rkrk-card">
    <h3 style="margin-top:0;">Beurteilungen</h3>
    <div class="kpis" style="margin-bottom:14px;">
      <div class="kpi"><b>Einträge</b><br><?php echo esc_html($evaluationTotal); ?></div>
      <div class="kpi"><b>Teilnehmer</b><br><?php echo esc_html($evaluationParticipantCount); ?></div>
      <div class="kpi"><b>Templates</b><br><?php echo esc_html($evaluationTemplateCount); ?></div>
      <div class="kpi"><b>Stufe 3+</b><br><?php echo esc_html((int)$evaluationLevelBuckets['3'] + (int)$evaluationLevelBuckets['4'] + (int)$evaluationLevelBuckets['5'] + (int)$evaluationLevelBuckets['6']); ?></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;margin-bottom:12px;">
      <?php foreach (array('1','2','3','4','5','6') as $lvl): ?>
        <?php $lc = exam_admin_level_colors($lvl); ?>
        <div style="padding:8px 10px;border-radius:8px;background:<?php echo esc_html((string)$lc['bg']); ?>;color:<?php echo esc_html((string)$lc['fg']); ?>;font-weight:700;">
          Stufe <?php echo esc_html($lvl); ?>: <?php echo esc_html((string)($evaluationLevelBuckets[$lvl] ?? 0)); ?>
        </div>
      <?php endforeach; ?>
    </div>
    <table>
      <tr><th>Datum &amp; Zeit</th><th>Pilot</th><th>Template</th><th>Stufe</th><th>Gesamtbewertung</th><?php if ($examAdminShowDeletes): ?><th>Aktion</th><?php endif; ?></tr>
      <?php if (empty($evaluationDisplayRows)): ?>
      <tr><td colspan="<?php echo $examAdminShowDeletes ? '6' : '5'; ?>" style="text-align:center;color:#6b7280;">Keine Beurteilungen gefunden.</td></tr>
      <?php else: ?>
      <?php foreach ($evaluationDisplayRows as $row): ?>
      <?php $evalName = trim((string)($row['name'] ?? '')) !== '' ? (string)$row['name'] : (string)($row['pilot'] ?? ''); ?>
      <?php
        $evalDateOnly = trim((string)($row['datetime'] ?? ''));
        $evalTs = strtotime($evalDateOnly);
        if ($evalTs !== false && $evalTs > 0) {
            $evalDateOnly = date('d.m.Y', $evalTs);
        } else {
            if (preg_match('/^(\d{2}\.\d{2}\.\d{4})/', $evalDateOnly, $mEval) === 1) $evalDateOnly = (string)$mEval[1];
        }
      ?>
      <tr>
        <td><?php echo esc_html($evalDateOnly); ?></td>
        <td>
          <a href="<?php echo htmlspecialchars(rkrk_assess_append_setting_href('rkrk_assess_live.php?mode=form&file=' . rawurlencode((string)$row['tpl_file']) . '&edit=' . rawurlencode((string)$row['id']) . '&PersPick=on'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_html($evalName); ?></a>
        </td>
        <td><?php echo esc_html((string)$row['template']); ?></td>
        <td><?php echo exam_admin_level_badge((string)($row['level'] ?? '')); ?></td>
        <td><?php echo exam_admin_rating_badge((string)$row['overall_rating']); ?></td>
        <?php if ($examAdminShowDeletes): ?>
        <td>
          <form method="post" style="margin:0;" onsubmit="return confirm('Diese Beurteilung (JSON unter Live-Assess) endgültig löschen?');">
            <input type="hidden" name="exam_admin_delete" value="1">
            <input type="hidden" name="delete_kind" value="evaluation_json">
            <input type="hidden" name="result_id" value="<?php echo esc_html((string)$row['id']); ?>">
            <input type="hidden" name="return_q" value="<?php echo esc_html($examAdminReturnQ); ?>">
            <button type="submit" class="ql-btn" style="padding:6px 10px;font-size:13px;">Löschen</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
