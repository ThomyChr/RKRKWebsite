<?php
/**
 * Einmalige Umbenennung auf einheitliche Stämme
 * (Checkliste: titel_nachname_vorname_id_YYYYMMDD,
 *  Quiz: titel_nachname_vorname_id_modus_YYYYMMDD).
 * Idempotent; Marker .stem_schema_v2 unter results/.
 */

if (!function_exists("rkrk_assess_stem_migrate_read_json")) {
	/** @return array<string, mixed>|null */
	function rkrk_assess_stem_migrate_read_json(string $path): ?array {
		if ($path === "" || !is_file($path)) {
			return null;
		}
		$raw = @file_get_contents($path);
		if (!is_string($raw) || trim($raw) === "") {
			return null;
		}
		$flags = 0;
		if (defined("JSON_INVALID_UTF8_IGNORE")) {
			$flags |= (int) constant("JSON_INVALID_UTF8_IGNORE");
		}
		$data = json_decode($raw, true, 512, $flags);
		return is_array($data) ? $data : null;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_write_json")) {
	/** @param array<string, mixed> $data */
	function rkrk_assess_stem_migrate_write_json(string $path, array $data): bool {
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if (!is_string($json)) {
			return false;
		}
		return @file_put_contents($path, $json, LOCK_EX) !== false;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_rename_file")) {
	function rkrk_assess_stem_migrate_rename_file(string $from, string $to): bool {
		$from = str_replace("\\", "/", $from);
		$to = str_replace("\\", "/", $to);
		if ($from === "" || $to === "" || $from === $to || !is_file($from)) {
			return $from === $to;
		}
		if (is_file($to) && realpath($to) !== realpath($from)) {
			return false;
		}
		$parent = dirname($to);
		if (!is_dir($parent)) {
			@mkdir($parent, 0755, true);
		}
		return @rename($from, $to);
	}
}

if (!function_exists("rkrk_assess_stem_migrate_one_json")) {
	/**
	 * @return string Neuer Stamm (leer = unverändert/fehler)
	 */
	function rkrk_assess_stem_migrate_one_json(
		string $jsonPath,
		string $newStem,
		string $reportsKind = ""
	): string {
		$jsonPath = str_replace("\\", "/", $jsonPath);
		$oldStem = pathinfo($jsonPath, PATHINFO_FILENAME);
		$newStem = function_exists("rkrk_assess_result_stem_sanitize")
			? rkrk_assess_result_stem_sanitize($newStem)
			: $newStem;
		if ($oldStem === "" || $newStem === "") {
			return "";
		}
		$dir = rtrim(str_replace("\\", "/", dirname($jsonPath)), "/") . "/";
		if (function_exists("rkrk_assess_unique_checklist_result_stem")) {
			$newStem = rkrk_assess_unique_checklist_result_stem($dir, $newStem, $jsonPath);
		}
		if ($oldStem === $newStem) {
			return $oldStem;
		}
		$to = $dir . $newStem . ".json";
		if (!rkrk_assess_stem_migrate_rename_file($jsonPath, $to)) {
			return "";
		}
		if (function_exists("rkrk_assess_rename_stem_sidecars")) {
			$repDir = "";
			if ($reportsKind !== "" && function_exists("rkrk_assess_reports_dir")) {
				$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
				$GLOBALS["rkrk_assess_reports_kind"] = ($reportsKind === "live") ? "live" : "assess";
				$repDir = rkrk_assess_reports_dir();
				if ($prevKind === null) {
					unset($GLOBALS["rkrk_assess_reports_kind"]);
				} else {
					$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
				}
			}
			if ($repDir === "" && is_dir($dir . "../reports")) {
				$repDir = rtrim(str_replace("\\", "/", $dir . "../reports"), "/") . "/";
			}
			if ($repDir !== "") {
				rkrk_assess_rename_stem_sidecars($repDir, $oldStem, $newStem);
			}
		}
		return $newStem;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_checklist_dir")) {
	function rkrk_assess_stem_migrate_checklist_dir(string $dir, string $templatesDir, string $reportsKind, bool $progress): int {
		$dir = rtrim(str_replace("\\", "/", $dir), "/") . "/";
		if (!is_dir($dir)) {
			return 0;
		}
		$n = 0;
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_string($file) || !is_file($file)) {
				continue;
			}
			$data = rkrk_assess_stem_migrate_read_json($file);
			if (!is_array($data)) {
				continue;
			}
			$fields = isset($data["fields"]) && is_array($data["fields"]) ? $data["fields"] : array();
			$tplFile = basename((string) ($data["template"] ?? ($data["file"] ?? "")));
			$titleSlug = function_exists("rkrk_assess_checklist_title_slug")
				? rkrk_assess_checklist_title_slug($tplFile, $templatesDir)
				: pathinfo($tplFile !== "" ? $tplFile : pathinfo($file, PATHINFO_FILENAME), PATHINFO_FILENAME);
			$stamp = "";
			if (!$progress) {
				$stamp = function_exists("rkrk_assess_checklist_completion_stamp")
					? rkrk_assess_checklist_completion_stamp(pathinfo($file, PATHINFO_FILENAME), (string) ($data["created_at"] ?? ""))
					: (string) ($data["created_at"] ?? "");
			}
			$newStem = "";
			if ($progress) {
				$pi = function_exists("rkrk_assess_fields_pilot_name_and_id")
					? rkrk_assess_fields_pilot_name_and_id($fields)
					: array("name" => "", "id" => "");
				$draft = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($data["draft_id"] ?? ""));
				$newStem = function_exists("rkrk_assess_build_progress_stem")
					? rkrk_assess_build_progress_stem($titleSlug, (string) ($pi["name"] ?? ""), $draft, (string) ($pi["id"] ?? ""))
					: "";
			} elseif (function_exists("rkrk_assess_build_checklist_result_json_stem")) {
				$newStem = rkrk_assess_build_checklist_result_json_stem($titleSlug, $fields, $stamp);
			}
			if ($newStem === "") {
				continue;
			}
			$renamed = rkrk_assess_stem_migrate_one_json($file, $newStem, $progress ? "" : $reportsKind);
			if ($renamed === "") {
				continue;
			}
			$finalPath = $dir . $renamed . ".json";
			if ($renamed !== (string) ($data["result_stem"] ?? "") || $renamed !== pathinfo($file, PATHINFO_FILENAME)) {
				$data["result_stem"] = $renamed;
				rkrk_assess_stem_migrate_write_json($finalPath, $data);
				$n++;
			}
		}
		return $n;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_exam_dir")) {
	function rkrk_assess_stem_migrate_exam_dir(string $dir, bool $progress): int {
		$dir = rtrim(str_replace("\\", "/", $dir), "/") . "/";
		if (!is_dir($dir)) {
			return 0;
		}
		$n = 0;
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_string($file) || !is_file($file)) {
				continue;
			}
			$data = rkrk_assess_stem_migrate_read_json($file);
			if (!is_array($data)) {
				continue;
			}
			$code = function_exists("rkrk_assess_exam_normalize_code")
				? rkrk_assess_exam_normalize_code($data["exam_code"] ?? "")
				: trim((string) ($data["exam_code"] ?? ""));
			$name = function_exists("rkrk_assess_exam_pilot_name_from_session")
				? rkrk_assess_exam_pilot_name_from_session($data)
				: trim((string) ($data["name"] ?? ""));
			if ($name === "") {
				$name = trim((string) ($data["name"] ?? ""));
			}
			$rkrsId = "";
			if (function_exists("rkrk_assess_exam_rkrs_id_from_session")) {
				$rkrsId = rkrk_assess_exam_rkrs_id_from_session($data, $name);
			}
			if ($rkrsId === "" && $name !== "" && function_exists("rkrk_assess_personel_rkrs_id_by_name")) {
				$rkrsId = rkrk_assess_personel_rkrs_id_by_name($name);
			}
			if ($rkrsId !== "") {
				$data["rkrs_pilot_id"] = $rkrsId;
			}
			$quizKey = trim((string) ($data["quiz_key"] ?? ""));
			if ($quizKey === "" && $code !== "") {
				$quizKey = $code;
			}
			$title = $quizKey;
			if (function_exists("rkrk_assess_exam_quiz_title_for_stem")) {
				$title = rkrk_assess_exam_quiz_title_for_stem($code !== "" ? $code : $quizKey, $data);
			} elseif (function_exists("rkrk_assess_quiz_title_from_key")) {
				$title = rkrk_assess_quiz_title_from_key($quizKey);
			}
			$modus = (string) ($data["quiz_modus"] ?? "");
			$withDate = false;
			$stamp = "";
			if (!$progress) {
				$withDate = function_exists("rkrk_assess_exam_stem_with_date")
					? rkrk_assess_exam_stem_with_date($data, $name)
					: true;
				if ($withDate) {
					$stamp = function_exists("rkrk_assess_exam_completion_stamp_from_session")
						? rkrk_assess_exam_completion_stamp_from_session($data)
						: (string) ($data["created_at"] ?? "");
				}
			}
			$newStem = "";
			if (function_exists("rkrk_assess_build_quiz_result_stem")) {
				$newStem = rkrk_assess_build_quiz_result_stem($title, $name, $rkrsId, $modus, $stamp, $withDate);
			}
			if ($newStem === "") {
				continue;
			}
			$renamed = rkrk_assess_stem_migrate_one_json($file, $newStem, $progress ? "" : "assess");
			if ($renamed === "") {
				continue;
			}
			$finalPath = $dir . $renamed . ".json";
			$data["result_stem"] = $renamed;
			rkrk_assess_stem_migrate_write_json($finalPath, $data);
			$n++;
		}
		return $n;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_orphan_reports")) {
	function rkrk_assess_stem_migrate_orphan_reports(string $reportsDir, string $checklistsDir, string $examsDir = ""): int {
		$reportsDir = rtrim(str_replace("\\", "/", $reportsDir), "/") . "/";
		if (!is_dir($reportsDir)) {
			return 0;
		}
		$known = array();
		foreach (array($checklistsDir, $examsDir) as $srcDir) {
			$srcDir = rtrim(str_replace("\\", "/", (string) $srcDir), "/") . "/";
			if ($srcDir === "/" || !is_dir($srcDir)) {
				continue;
			}
			foreach (glob($srcDir . "*.json") ?: array() as $jf) {
				if (!is_string($jf)) {
					continue;
				}
				$st = pathinfo($jf, PATHINFO_FILENAME);
				if ($st !== "") {
					$known[$st] = true;
				}
			}
		}
		$n = 0;
		foreach (array("*.pdf", "*.html") as $pat) {
			foreach (glob($reportsDir . $pat) ?: array() as $file) {
				if (!is_string($file) || !is_file($file)) {
					continue;
				}
				$stem = pathinfo($file, PATHINFO_FILENAME);
				$stem = preg_replace('/\.prep$/i', "", $stem);
				if ($stem === "" || isset($known[$stem])) {
					continue;
				}
				// Kein JSON mehr: unverändert lassen
			}
		}
		return $n;
	}
}

if (!function_exists("rkrk_assess_stem_migrate_all")) {
	function rkrk_assess_stem_migrate_all(): int {
		if (!function_exists("rkrk_assess_exam_file_stem")) {
			$__examInc = __DIR__ . "/rkrk_assess_exam_session.inc.php";
			if (is_readable($__examInc)) {
				include_once $__examInc;
			}
			unset($__examInc);
		}
		if (!function_exists("rkrk_assess_build_quiz_result_stem")) {
			$__stemInc = __DIR__ . "/rkrk_assess_result_stem.inc.php";
			if (is_readable($__stemInc)) {
				include_once $__stemInc;
			}
			unset($__stemInc);
		}
		static $ran = false;
		if ($ran) {
			return 0;
		}
		$ran = true;
		$total = 0;

		$quizResults = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$quizResults = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/";
		}
		$assessTpl = isset($GLOBALS["rkrkAssessTemplates"]) ? (string) $GLOBALS["rkrkAssessTemplates"] : "";
		if ($quizResults !== "") {
			$marker = $quizResults . ".stem_schema_v2";
			if (!is_file($marker)) {
				$total += rkrk_assess_stem_migrate_checklist_dir($quizResults . "checklists/", $assessTpl, "assess", false);
				$total += rkrk_assess_stem_migrate_checklist_dir($quizResults . "checklists_progress/", $assessTpl, "assess", true);
				$total += rkrk_assess_stem_migrate_exam_dir($quizResults . "exams/", false);
				$total += rkrk_assess_stem_migrate_exam_dir($quizResults . "exams_progress/", true);
				rkrk_assess_stem_migrate_orphan_reports(
					$quizResults . "reports/",
					$quizResults . "checklists/",
					$quizResults . "exams/"
				);
				@file_put_contents($marker, date("c") . "\n", LOCK_EX);
			}
		}

		$liveData = "";
		if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
			$liveData = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/";
		}
		$liveTpl = isset($GLOBALS["rkrkLiveassessTemplates"]) ? (string) $GLOBALS["rkrkLiveassessTemplates"] : "";
		if ($liveData !== "") {
			$liveResults = $liveData . "results/";
			$marker = $liveResults . ".stem_schema_v2";
			if (!is_file($marker)) {
				$total += rkrk_assess_stem_migrate_checklist_dir($liveResults . "checklists/", $liveTpl, "live", false);
				$total += rkrk_assess_stem_migrate_checklist_dir($liveResults . "checklists_progress/", $liveTpl, "live", true);
				rkrk_assess_stem_migrate_orphan_reports($liveResults . "reports/", $liveResults . "checklists/", "");
				@file_put_contents($marker, date("c") . "\n", LOCK_EX);
			}
		}
		return $total;
	}
}
