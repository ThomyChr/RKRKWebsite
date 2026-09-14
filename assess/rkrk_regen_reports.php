<?php
/**
 * Ein/mehrere Abschluss-PDFs neu erzeugen (Diagnose / Layout-Fixes).
 * Web: …/public/rkrk_regen_reports.php?key=rkrs-local-regen&stem=vortest2_roger_volken
 * Alle (max 40): …/public/rkrk_regen_reports.php?key=rkrs-local-regen&all=1
 * Checklisten Material/Praxis: …/public/rkrk_regen_reports.php?key=rkrs-local-regen&assess=1
 */
declare(strict_types=1);

$key = (string) ($_GET["key"] ?? "");
$expect = getenv("RKRK_REGEN_KEY") ?: "rkrs-local-regen";
if (PHP_SAPI !== "cli" && !hash_equals($expect, $key)) {
	http_response_code(403);
	header("Content-Type: text/plain; charset=utf-8");
	echo "forbidden\n";
	exit;
}

header("Content-Type: text/plain; charset=utf-8");

$_GET["ADMIN"] = "1";
$_GET["RKRS"] = "1";
$_SERVER["SCRIPT_NAME"] = "/rkropen/rkrs/assess/public/rkrk_assess_admin.php";
$_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"];

require_once __DIR__ . "/rkrk_assess_common.php";
@include_once __DIR__ . "/rkrk_assess_exam_session.inc.php";
@include_once __DIR__ . "/rkrk_assess_report_pdf.inc.php";

// Portal: DejaVu-Fonts nachziehen, sonst ✓/★/✗ → «?»
if (function_exists("rkrk_assess_report_tcpdf_install_dejavu_fonts")) {
	$fontDir = rkrk_assess_report_tcpdf_install_dejavu_fonts();
	echo "dejavu_fonts=" . ($fontDir !== "" ? $fontDir : "missing") . "\n";
}

$stemArg = trim((string) ($_GET["stem"] ?? (PHP_SAPI === "cli" ? ($argv[1] ?? "") : "")));
$doAll = isset($_GET["all"]) || (PHP_SAPI === "cli" && ($argv[1] ?? "") === "all");
$doLive = isset($_GET["live"]) || (PHP_SAPI === "cli" && in_array("live", $argv ?? array(), true));
$doAssess = isset($_GET["assess"]) || isset($_GET["checklist"])
	|| (PHP_SAPI === "cli" && (in_array("assess", $argv ?? array(), true) || in_array("checklist", $argv ?? array(), true)));

$quizLoc = isset($GLOBALS["QuizLocResults"]) ? (string) $GLOBALS["QuizLocResults"] : "";
$quizLocReal = (string) @realpath($quizLoc);
echo "cwd=" . getcwd() . "\n";
echo "QuizLocResults={$quizLoc}\n";
echo "QuizLocResults_real={$quizLocReal}\n";
$examDirFn = function_exists("rkrk_assess_exam_storage_dir") ? rkrk_assess_exam_storage_dir() : "";
echo "exam_dir_fn={$examDirFn}\n";
$examDir = $examDirFn;
if ($examDir === "" && $quizLocReal !== "") {
	$examDir = rtrim(str_replace("\\", "/", $quizLocReal), "/") . "/exams/";
}
if ($examDir === "" && $quizLoc !== "") {
	$examDir = rtrim(str_replace("\\", "/", $quizLoc), "/") . "/exams/";
}
echo "exam_dir={$examDir}\n";
echo "exam_dir_is_dir=" . (is_dir($examDir) ? "1" : "0") . "\n";
echo "exam_glob=" . count(glob(rtrim($examDir, "/") . "/*.json") ?: array()) . "\n";
echo "reports_dir=" . (function_exists("rkrk_assess_reports_dir") ? rkrk_assess_reports_dir() : "?") . "\n";

if (!function_exists("rkrk_assess_ensure_report_from_completed_exam")) {
	echo "missing ensure_report_from_completed_exam\n";
	exit(1);
}


if ($doAssess) {
	$GLOBALS["rkrk_assess_reports_kind"] = "assess";
	if (!function_exists("showChecklistPrint")) {
		$GLOBALS["rkrk_assess_regen_assess_bootstrap"] = true;
		ob_start();
		include __DIR__ . "/rkrk_assess.php";
		ob_end_clean();
		unset($GLOBALS["rkrk_assess_regen_assess_bootstrap"]);
	}
	$checkDir = "";
	if (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
		$checkDir = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkAssessResults"]), "/") . "/";
	}
	echo "assess_checklists={$checkDir}\n";
	echo "assess_reports=" . (function_exists("rkrk_assess_reports_dir") ? rkrk_assess_reports_dir() : "?") . "\n";

	$created = 0;
	$failed = 0;
	$errors = array();
	if ($checkDir !== "" && is_dir($checkDir) && function_exists("rkrk_assess_save_checklist_print_report") && function_exists("showChecklistPrint")) {
		foreach (glob($checkDir . "*.json") ?: array() as $file) {
			$id = pathinfo($file, PATHINFO_FILENAME);
			if ($id === "") {
				continue;
			}
			$res = rkrk_assess_save_checklist_print_report((string) $id, static function () use ($id) {
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
			if (!empty($res["ok"])) {
				$created++;
				echo "ok {$id} " . ($res["format"] ?? "") . " " . ($res["path"] ?? "") . "\n";
			} else {
				$failed++;
				$errors[] = $id . ": " . ($res["error"] ?? "?");
				echo "fail {$id} " . ($res["error"] ?? "?") . "\n";
			}
		}
	} else {
		echo "assess save helpers missing or checklist dir empty\n";
		echo "has_print=" . (function_exists("showChecklistPrint") ? "1" : "0") . "\n";
		echo "has_save=" . (function_exists("rkrk_assess_save_checklist_print_report") ? "1" : "0") . "\n";
	}
	echo json_encode(array("created" => $created, "failed" => $failed, "errors" => $errors), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	exit;
}

if ($doLive) {
	$GLOBALS["rkrk_assess_reports_kind"] = "live";
	// Live-Hilfsfunktionen (showChecklistPrint + migrate)
	if (!function_exists("showChecklistPrint")) {
		$GLOBALS["rkrk_assess_regen_live_bootstrap"] = true;
		ob_start();
		include __DIR__ . "/rkrk_assess_live.php";
		ob_end_clean();
		unset($GLOBALS["rkrk_assess_regen_live_bootstrap"]);
	}
	$GLOBALS["rkrk_assess_reports_kind"] = "live";
	$liveDir = "";
	if (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
		$liveDir = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/") . "/";
	}
	echo "live_checklists={$liveDir}\n";
	echo "live_reports=" . rkrk_assess_reports_dir() . "\n";

	$created = 0;
	$failed = 0;
	$errors = array();
	if ($liveDir !== "" && is_dir($liveDir) && function_exists("rkrk_assess_save_checklist_print_report") && function_exists("showChecklistPrint")) {
		foreach (glob($liveDir . "*.json") ?: array() as $file) {
			$id = pathinfo($file, PATHINFO_FILENAME);
			if ($id === "") {
				continue;
			}
			$GLOBALS["rkrk_assess_reports_kind"] = "live";
			$res = rkrk_assess_save_checklist_print_report((string) $id, static function () use ($id) {
				$_GET["id"] = $id;
				$GLOBALS["rkrk_assess_print_no_exit"] = true;
				$GLOBALS["rkrk_assess_print_hide_unrated"] = true;
				$GLOBALS["rkrk_assess_print_compact"] = true;
				$_GET["hide_ablauf"] = "1";
				$_GET["hide_printtype"] = "1";
				$_GET["hide_referenz"] = "1";
				ob_start();
				showChecklistPrint();
				$html = (string) ob_get_clean();
				unset($GLOBALS["rkrk_assess_print_no_exit"], $GLOBALS["rkrk_assess_print_hide_unrated"], $GLOBALS["rkrk_assess_print_compact"]);
				return $html;
			});
			if (!empty($res["ok"])) {
				$created++;
				echo "ok {$id} " . ($res["format"] ?? "") . "\n";
			} else {
				$failed++;
				$errors[] = $id . ": " . ($res["error"] ?? "?");
				echo "fail {$id}\n";
			}
		}
	} elseif (function_exists("rkrk_assess_live_migrate_reports_rated_only")) {
		$created = rkrk_assess_live_migrate_reports_rated_only();
	} else {
		echo "live save helpers missing — include rkrk_assess_live.php via ?live=1 from live context\n";
	}
	echo json_encode(array("created" => $created, "failed" => $failed, "errors" => $errors), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	exit;
}

if ($doAll) {
	$stats = rkrk_assess_ensure_missing_exam_reports(40, true);
	if ((int) ($stats["created"] ?? 0) === 0 && (int) ($stats["skipped"] ?? 0) === 0 && $examDir !== "") {
		$created = 0;
		$failed = 0;
		$errors = array();
		foreach (glob($examDir . "*.json") ?: array() as $file) {
			if ($created >= 40) {
				break;
			}
			$stem = pathinfo($file, PATHINFO_FILENAME);
			$_GET["stem"] = $stem;
			// reuse direct load via ensure: build minimal by re-including logic
			$data = function_exists("rkrk_assess_exam_read_json_file") ? rkrk_assess_exam_read_json_file($file) : null;
			if (!is_array($data)) {
				continue;
			}
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			$pick = null;
			foreach ($attempts as $attempt) {
				if (is_array($attempt) && strtolower(trim((string) ($attempt["status"] ?? ""))) === "completed"
					&& isset($attempt["result"]) && is_array($attempt["result"])
				) {
					$pick = $attempt;
					break;
				}
			}
			if ($pick === null) {
				continue;
			}
			$row = array(
				"stem" => $stem,
				"path" => $file,
				"name" => trim((string) ($pick["name"] ?? "")),
				"drone" => trim((string) ($pick["drone"] ?? "")),
				"exam_code" => trim((string) ($data["exam_code"] ?? "")),
				"quiz_key" => trim((string) ($data["quiz_key"] ?? "")),
				"quiz_modus" => trim((string) ($data["quiz_modus"] ?? "")),
				"result" => $pick["result"],
			);
			$res = rkrk_assess_ensure_report_from_completed_exam($row, true);
			if (!empty($res["ok"])) {
				$created++;
			} else {
				$failed++;
				$errors[] = $stem . ": " . ($res["error"] ?? "?");
			}
		}
		$stats = array("created" => $created, "skipped" => 0, "failed" => $failed, "errors" => $errors, "via" => "direct_glob");
	}
	echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	exit;
}

if ($stemArg === "") {
	$rows = function_exists("rkrk_assess_exam_list_completed") ? rkrk_assess_exam_list_completed() : array();
	echo "completed_count=" . count($rows) . "\n";
	foreach (array_slice($rows, 0, 15) as $row) {
		echo "- " . trim((string) ($row["stem"] ?? "?")) . "\n";
	}
	echo "usage: ?key=…&stem=STEM  or  ?key=…&all=1\n";
	exit;
}

$row = null;
if (function_exists("rkrk_assess_exam_list_completed")) {
	foreach (rkrk_assess_exam_list_completed() as $cand) {
		if (!is_array($cand)) {
			continue;
		}
		if (strcasecmp(trim((string) ($cand["stem"] ?? "")), $stemArg) === 0) {
			$row = $cand;
			break;
		}
	}
}

// Fallback: Exam-JSON direkt laden (falls list_completed leer / Pfad-Issue)
if ($row === null && $examDir !== "") {
	$file = rtrim(str_replace("\\", "/", $examDir), "/") . "/" . $stemArg . ".json";
	echo "try_file={$file} exists=" . (is_file($file) ? "1" : "0") . "\n";
	if (is_file($file)) {
		$raw = @file_get_contents($file);
		$data = is_string($raw) ? json_decode($raw, true) : null;
		if (function_exists("rkrk_assess_exam_read_json_file")) {
			$data2 = rkrk_assess_exam_read_json_file($file);
			echo "read_fn=" . (is_array($data2) ? "array" : gettype($data2)) . " json_direct=" . (is_array($data) ? "array" : gettype($data)) . "\n";
			if (is_array($data2)) {
				$data = $data2;
			}
		} else {
			echo "read_fn=missing json_direct=" . (is_array($data) ? "array" : gettype($data)) . "\n";
		}
		if (is_array($data)) {
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			echo "attempts_count=" . count($attempts) . " top_status=" . trim((string) ($data["status"] ?? "")) . "\n";
			$pick = null;
			foreach ($attempts as $attempt) {
				if (!is_array($attempt)) {
					continue;
				}
				$st = strtolower(trim((string) ($attempt["status"] ?? "")));
				$hasRes = isset($attempt["result"]) && is_array($attempt["result"]);
				echo "attempt status={$st} has_result=" . ($hasRes ? "1" : "0") . "\n";
				if ($st === "completed" && $hasRes) {
					$pick = $attempt;
					break;
				}
			}
			if ($pick === null && strtolower(trim((string) ($data["status"] ?? ""))) === "completed"
				&& isset($data["result"]) && is_array($data["result"])
			) {
				$pick = $data;
			}
			if ($pick !== null) {
				$row = array(
					"stem" => $stemArg,
					"path" => $file,
					"name" => trim((string) ($pick["name"] ?? "")),
					"drone" => trim((string) ($pick["drone"] ?? "")),
					"exam_code" => trim((string) ($data["exam_code"] ?? "")),
					"quiz_key" => trim((string) ($data["quiz_key"] ?? "")),
					"quiz_modus" => trim((string) ($data["quiz_modus"] ?? "")),
					"result" => $pick["result"],
				);
				echo "loaded_direct={$file}\n";
			}
		}
	}
}

if ($row === null) {
	echo "stem not found / no completed result: {$stemArg}\n";
	exit(1);
}

$res = rkrk_assess_ensure_report_from_completed_exam($row, true);
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
exit(!empty($res["ok"]) ? 0 : 1);
