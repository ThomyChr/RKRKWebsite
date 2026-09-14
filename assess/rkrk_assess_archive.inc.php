<?php
/**
 * Archivierung von Checklisten, EXAM-Sessions, Reports und Fortschritt.
 * Ziel: {results}/archive/{checklists|exams|reports|checklists_progress|exams_progress}/
 * Nach dem Verschieben fehlen die Dateien in der Live-Übersicht; EXAM-/Quiz-Links
 * sind wieder frei, weil results/exams/ und exams_progress/ sie nicht mehr enthalten.
 */

if (!function_exists("rkrk_assess_archive_norm_dir")) {
	function rkrk_assess_archive_norm_dir(string $dir): string {
		$dir = str_replace("\\", "/", trim($dir));
		if ($dir === "") {
			return "";
		}
		return rtrim($dir, "/") . "/";
	}
}

if (!function_exists("rkrk_assess_archive_results_root")) {
	/** Live-Results-Wurzel (assess oder live). */
	function rkrk_assess_archive_results_root(string $kind = "assess"): string {
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		if ($kind === "live") {
			if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
				return rkrk_assess_archive_norm_dir((string) $GLOBALS["rkrkLiveassessData"]) . "results/";
			}
			if (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
				$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/"));
				return rkrk_assess_archive_norm_dir($parent);
			}
			return "";
		}
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			return rkrk_assess_archive_norm_dir((string) $GLOBALS["QuizLocResults"]);
		}
		if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			return rkrk_assess_archive_norm_dir((string) $GLOBALS["QuizLoc"]) . "results/";
		}
		if (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
			$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkAssessResults"]), "/"));
			return rkrk_assess_archive_norm_dir($parent);
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_archive_dir")) {
	/** ./archive unter der jeweiligen results-Wurzel. */
	function rkrk_assess_archive_dir(string $kind = "assess"): string {
		$root = rkrk_assess_archive_results_root($kind);
		if ($root === "") {
			return "";
		}
		$dir = $root . "archive/";
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_archive_bucket_dir")) {
	function rkrk_assess_archive_bucket_dir(string $bucket, string $kind = "assess"): string {
		$bucket = strtolower(trim($bucket));
		$allowed = array(
			"checklists" => "checklists",
			"exams" => "exams",
			"reports" => "reports",
			"checklists_progress" => "checklists_progress",
			"exams_progress" => "exams_progress",
			"quiz_results" => "quiz_results",
			"manager" => "manager",
		);
		if (!isset($allowed[$bucket])) {
			return "";
		}
		$arch = rkrk_assess_archive_dir($kind);
		if ($arch === "") {
			return "";
		}
		$dir = $arch . $allowed[$bucket] . "/";
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_archive_allowed_roots")) {
	/** @return list<string> */
	function rkrk_assess_archive_allowed_roots(): array {
		$out = array();
		$candidates = array();
		if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$candidates[] = (string) $GLOBALS["QuizLoc"];
		}
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$candidates[] = (string) $GLOBALS["QuizLocResults"];
		}
		if (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
			$candidates[] = (string) $GLOBALS["rkrkAssessResults"];
		}
		if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
			$candidates[] = (string) $GLOBALS["rkrkLiveassessData"];
		}
		if (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
			$candidates[] = (string) $GLOBALS["rkrkLiveassessResults"];
		}
		foreach ($candidates as $c) {
			$n = rkrk_assess_archive_norm_dir($c);
			if ($n === "") {
				continue;
			}
			$rp = realpath(rtrim($n, "/"));
			$out[] = $rp !== false ? str_replace("\\", "/", $rp) : rtrim($n, "/");
		}
		return array_values(array_unique($out));
	}
}

if (!function_exists("rkrk_assess_archive_path_allowed")) {
	function rkrk_assess_archive_path_allowed(string $path): bool {
		$path = str_replace("\\", "/", $path);
		$rp = realpath($path);
		$check = $rp !== false ? str_replace("\\", "/", $rp) : $path;
		if ($check === "") {
			return false;
		}
		foreach (rkrk_assess_archive_allowed_roots() as $root) {
			if ($root !== "" && strpos($check, $root) === 0) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists("rkrk_assess_archive_unique_dest")) {
	function rkrk_assess_archive_unique_dest(string $destDir, string $basename): string {
		$destDir = rkrk_assess_archive_norm_dir($destDir);
		$basename = basename($basename);
		if ($destDir === "" || $basename === "") {
			return "";
		}
		$dest = $destDir . $basename;
		if (!is_file($dest)) {
			return $dest;
		}
		$stem = pathinfo($basename, PATHINFO_FILENAME);
		$ext = strtolower((string) pathinfo($basename, PATHINFO_EXTENSION));
		$suffix = date("Ymd_His");
		$n = 0;
		do {
			$try = $stem . "__" . $suffix . ($n > 0 ? ("_" . $n) : "");
			$dest = $destDir . $try . ($ext !== "" ? ("." . $ext) : "");
			$n++;
		} while (is_file($dest) && $n < 50);
		return $dest;
	}
}

if (!function_exists("rkrk_assess_archive_move_file")) {
	/**
	 * Verschiebt eine Datei in den Archiv-Ordner. Quelle muss unter data/assess oder data/liveassess liegen.
	 *
	 * @return string Zielpfad oder "" bei Fehler
	 */
	function rkrk_assess_archive_move_file(string $srcPath, string $destDir): string {
		$srcPath = str_replace("\\", "/", trim($srcPath));
		$destDir = rkrk_assess_archive_norm_dir($destDir);
		if ($srcPath === "" || $destDir === "" || !is_file($srcPath)) {
			return "";
		}
		if (!rkrk_assess_archive_path_allowed($srcPath)) {
			return "";
		}
		$srcNorm = str_replace("\\", "/", $srcPath);
		if (strpos($srcNorm, "/archive/") !== false) {
			return "";
		}
		if (!is_dir($destDir)) {
			@mkdir($destDir, 0755, true);
		}
		if (!is_dir($destDir)) {
			return "";
		}
		$dest = rkrk_assess_archive_unique_dest($destDir, basename($srcPath));
		if ($dest === "") {
			return "";
		}
		if (@rename($srcPath, $dest)) {
			return $dest;
		}
		if (@copy($srcPath, $dest)) {
			@unlink($srcPath);
			return is_file($dest) ? $dest : "";
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_archive_append_json_list")) {
	/**
	 * Hängt Einträge an eine JSON-Liste ({"rows":[...]} oder Array) im Archiv.
	 *
	 * @param list<array<string,mixed>> $entries
	 */
	function rkrk_assess_archive_append_json_list(string $path, array $entries): bool {
		if ($path === "" || $entries === array()) {
			return false;
		}
		$parent = dirname($path);
		if (!is_dir($parent)) {
			@mkdir($parent, 0755, true);
		}
		$existing = array();
		if (is_file($path)) {
			$raw = @file_get_contents($path);
			if ($raw !== false && trim($raw) !== "") {
				$decoded = @json_decode($raw, true);
				if (is_array($decoded)) {
					if (isset($decoded["rows"]) && is_array($decoded["rows"])) {
						$existing = $decoded["rows"];
					} elseif (isset($decoded[0]) || $decoded === array()) {
						$existing = $decoded;
					}
				}
			}
		}
		foreach ($entries as $row) {
			if (is_array($row)) {
				$existing[] = $row;
			}
		}
		$payload = array(
			"format" => "rkropen_archive_list_v1",
			"updated_at" => date("c"),
			"rows" => $existing,
		);
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false) {
			return false;
		}
		return @file_put_contents($path, $json, LOCK_EX) !== false;
	}
}

if (!function_exists("rkrk_assess_archive_log")) {
	/** @param array<string,mixed> $entry */
	function rkrk_assess_archive_log(array $entry, string $kind = "assess"): void {
		$dir = rkrk_assess_archive_dir($kind);
		if ($dir === "") {
			return;
		}
		$entry["at"] = $entry["at"] ?? date("c");
		$line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($line === false) {
			return;
		}
		@file_put_contents($dir . "archive_log.jsonl", $line . "\n", FILE_APPEND | LOCK_EX);
	}
}

if (!function_exists("rkrk_assess_archive_safe_basename")) {
	function rkrk_assess_archive_safe_basename(string $name): string {
		$name = basename(trim($name));
		$name = preg_replace('/\.json$/i', "", $name);
		if ($name === "" || !preg_match('/^[A-Za-z0-9._-]{1,180}$/', $name)) {
			return "";
		}
		return $name;
	}
}

if (!function_exists("rkrk_assess_archive_reports_by_stem")) {
	/** @return list<string> verschobene Zielpfade */
	function rkrk_assess_archive_reports_by_stem(string $stem, string $reportsKind = "assess"): array {
		$stem = rkrk_assess_archive_safe_basename($stem);
		if ($stem === "") {
			return array();
		}
		$moved = array();
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = (strtolower(trim($reportsKind)) === "live") ? "live" : "assess";
		$dir = function_exists("rkrk_assess_reports_dir") ? rkrk_assess_reports_dir() : "";
		$destDir = rkrk_assess_archive_bucket_dir("reports", $reportsKind);
		if ($dir !== "" && $destDir !== "") {
			foreach (array("pdf", "html") as $fmt) {
				$src = rtrim(str_replace("\\", "/", $dir), "/") . "/" . $stem . "." . $fmt;
				if (is_file($src)) {
					$dest = rkrk_assess_archive_move_file($src, $destDir);
					if ($dest !== "") {
						$moved[] = $dest;
					}
				}
			}
		}
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		return $moved;
	}
}

if (!function_exists("rkrk_assess_archive_exam_file")) {
	function rkrk_assess_archive_exam_file(string $basename): string {
		$stem = rkrk_assess_archive_safe_basename($basename);
		if ($stem === "" || !function_exists("rkrk_assess_exam_storage_dir")) {
			return "";
		}
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return "";
		}
		$src = rtrim(str_replace("\\", "/", $dir), "/") . "/" . $stem . ".json";
		if (!is_file($src)) {
			return "";
		}
		$destDir = rkrk_assess_archive_bucket_dir("exams", "assess");
		if ($destDir === "") {
			return "";
		}
		return rkrk_assess_archive_move_file($src, $destDir);
	}
}

if (!function_exists("rkrk_assess_archive_checklist_json")) {
	function rkrk_assess_archive_checklist_json(string $resultId, string $kind = "assess"): string {
		$stem = rkrk_assess_archive_safe_basename($resultId);
		if ($stem === "") {
			return "";
		}
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		$base = "";
		if ($kind === "live") {
			if (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
				$base = rkrk_assess_archive_norm_dir((string) $GLOBALS["rkrkLiveassessResults"]);
			}
		} else {
			if (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
				$base = rkrk_assess_archive_norm_dir((string) $GLOBALS["rkrkAssessResults"]);
			}
		}
		if ($base === "") {
			$root = rkrk_assess_archive_results_root($kind);
			$base = $root !== "" ? ($root . "checklists/") : "";
		}
		if ($base === "") {
			return "";
		}
		$src = $base . $stem . ".json";
		if (!is_file($src)) {
			return "";
		}
		$destDir = rkrk_assess_archive_bucket_dir("checklists", $kind);
		if ($destDir === "") {
			return "";
		}
		return rkrk_assess_archive_move_file($src, $destDir);
	}
}

if (!function_exists("rkrk_assess_archive_quiz_progress_file")) {
	function rkrk_assess_archive_quiz_progress_file(string $basename): string {
		$stem = rkrk_assess_archive_safe_basename($basename);
		if ($stem === "" || !function_exists("rkrk_assess_quiz_progress_dir")) {
			return "";
		}
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return "";
		}
		$src = rtrim(str_replace("\\", "/", $dir), "/") . "/" . $stem . ".json";
		if (!is_file($src)) {
			return "";
		}
		$destDir = rkrk_assess_archive_bucket_dir("exams_progress", "assess");
		if ($destDir === "") {
			return "";
		}
		return rkrk_assess_archive_move_file($src, $destDir);
	}
}

if (!function_exists("rkrk_assess_archive_checklist_progress_file")) {
	function rkrk_assess_archive_checklist_progress_file(string $basename, string $kind = "assess"): string {
		$stem = rkrk_assess_archive_safe_basename($basename);
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		if ($stem === "" || !function_exists("rkrk_assess_checklist_progress_dir")) {
			return "";
		}
		$dir = rkrk_assess_checklist_progress_dir($kind);
		if ($dir === "") {
			return "";
		}
		$src = rtrim(str_replace("\\", "/", $dir), "/") . "/" . $stem . ".json";
		if (!is_file($src)) {
			return "";
		}
		$destDir = rkrk_assess_archive_bucket_dir("checklists_progress", $kind);
		if ($destDir === "") {
			return "";
		}
		return rkrk_assess_archive_move_file($src, $destDir);
	}
}

if (!function_exists("rkrk_assess_archive_quiz_result_row")) {
	/**
	 * Entnimmt eine QuizResultate-Zeile und legt sie im Archiv ab.
	 *
	 * @param array{name?:string,date?:string,quiz?:string,exam_code?:string,line_index?:int} $match
	 */
	function rkrk_assess_archive_quiz_result_row(string $resultsDir, array $match): bool {
		$resultsDir = rtrim(str_replace("\\", "/", $resultsDir), "/");
		if ($resultsDir === "" || !is_dir($resultsDir)) {
			return false;
		}
		if (!function_exists("rkrk_assess_quiz_results_load_rows") || !function_exists("rkrk_assess_quiz_results_save_rows")) {
			return false;
		}
		$data = rkrk_assess_quiz_results_load_rows($resultsDir);
		if (!is_array($data) || $data === array()) {
			return false;
		}
		$name = strtolower(trim((string) ($match["name"] ?? "")));
		$date = trim((string) ($match["date"] ?? ""));
		$quiz = trim((string) ($match["quiz"] ?? ""));
		$exam = strtolower(trim((string) ($match["exam_code"] ?? "")));
		$lineIndex = isset($match["line_index"]) ? (int) $match["line_index"] : -1;
		$idx = -1;
		if ($lineIndex >= 0 && $lineIndex < count($data)) {
			$idx = $lineIndex;
		}
		if ($idx < 0) {
			foreach ($data as $i => $row) {
				if (!is_array($row)) {
					continue;
				}
				if ($exam !== "") {
					$rowExam = strtolower(trim((string) ($row["EXAM"] ?? $row["exam_code"] ?? "")));
					if ($rowExam === $exam) {
						if ($name === "" || strtolower(trim((string) ($row["Name"] ?? ""))) === $name) {
							$idx = (int) $i;
							break;
						}
					}
				}
				$ok = true;
				if ($name !== "" && strtolower(trim((string) ($row["Name"] ?? ""))) !== $name) {
					$ok = false;
				}
				if ($date !== "" && trim((string) ($row["Zeit"] ?? "")) !== $date) {
					$ok = false;
				}
				if ($quiz !== "" && trim((string) ($row["Quiz"] ?? "")) !== $quiz) {
					$ok = false;
				}
				if ($ok && ($name !== "" || $date !== "" || $quiz !== "")) {
					$idx = (int) $i;
					break;
				}
			}
		}
		if ($idx < 0) {
			return false;
		}
		$taken = $data[$idx];
		array_splice($data, $idx, 1);
		if (!rkrk_assess_quiz_results_save_rows($resultsDir, $data)) {
			return false;
		}
		$destDir = rkrk_assess_archive_bucket_dir("quiz_results", "assess");
		if ($destDir === "") {
			return true;
		}
		$taken["_archived_at"] = date("c");
		rkrk_assess_archive_append_json_list($destDir . "QuizResultate.json", array($taken));
		return true;
	}
}

if (!function_exists("rkrk_assess_archive_item")) {
	/**
	 * Archiviert eine Kachel (Checklist / Exam / Report / Progress / Quiz-Zeile).
	 *
	 * @param array<string,mixed> $item
	 * @return array{ok:bool,moved:list<string>,quiz_row:bool,exam_code:string}
	 */
	function rkrk_assess_archive_item(array $item): array {
		$kind = strtolower(trim((string) ($item["kind"] ?? $item["delete_kind"] ?? "")));
		$reportsKind = strtolower(trim((string) ($item["reports_kind"] ?? ""))) === "live" ? "live" : "assess";
		if ($kind === "evaluation_json") {
			$reportsKind = "live";
		}
		$moved = array();
		$quizRow = false;
		$examCode = trim((string) ($item["exam_code"] ?? $item["progress_exam_code"] ?? ""));
		$examStem = rkrk_assess_archive_safe_basename((string) ($item["exam_stem"] ?? ""));
		if ($examStem === "" && ($kind === "exam_json" || $kind === "exam_session_progress")) {
			$examStem = rkrk_assess_archive_safe_basename((string) ($item["progress_path"] ?? ""));
		}
		$resultId = rkrk_assess_archive_safe_basename((string) ($item["result_id"] ?? ""));
		$progressPath = rkrk_assess_archive_safe_basename((string) ($item["progress_path"] ?? ""));

		if ($kind === "exam_json" || $kind === "exam_session_progress" || ($examStem !== "" && $kind !== "quiz_progress" && $kind !== "checklist_progress")) {
			if ($examStem !== "") {
				$dest = rkrk_assess_archive_exam_file($examStem);
				if ($dest !== "") {
					$moved[] = $dest;
				}
				foreach (rkrk_assess_archive_reports_by_stem($examStem, "assess") as $r) {
					$moved[] = $r;
				}
			}
		}

		if ($kind === "assessment_json" || ($kind === "" && $resultId !== "" && $reportsKind === "assess")) {
			if ($resultId !== "") {
				$dest = rkrk_assess_archive_checklist_json($resultId, "assess");
				if ($dest !== "") {
					$moved[] = $dest;
				}
				foreach (rkrk_assess_archive_reports_by_stem($resultId, "assess") as $r) {
					$moved[] = $r;
				}
			}
		}

		if ($kind === "evaluation_json") {
			if ($resultId !== "") {
				$dest = rkrk_assess_archive_checklist_json($resultId, "live");
				if ($dest !== "") {
					$moved[] = $dest;
				}
				foreach (rkrk_assess_archive_reports_by_stem($resultId, "live") as $r) {
					$moved[] = $r;
				}
			}
		}

		if ($kind === "checklist_progress") {
			if ($progressPath !== "") {
				$dest = rkrk_assess_archive_checklist_progress_file($progressPath, "assess");
				if ($dest !== "") {
					$moved[] = $dest;
				}
			}
			$progFile = basename(trim((string) ($item["progress_file"] ?? "")));
			$progEdit = basename(trim((string) ($item["progress_edit"] ?? "")));
			$progDraft = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($item["progress_draft_id"] ?? ""));
			if ($progFile !== "" && ($progEdit !== "" || $progDraft !== "") && function_exists("rkrk_assess_checklist_progress_candidate_paths")) {
				foreach (rkrk_assess_checklist_progress_candidate_paths(array(
					"kind" => "assess",
					"file" => $progFile,
					"edit" => $progEdit,
					"draft_id" => $progDraft,
				), array()) as $cand) {
					if ($cand !== "" && is_file($cand)) {
						$dest = rkrk_assess_archive_checklist_progress_file(basename($cand), "assess");
						if ($dest !== "") {
							$moved[] = $dest;
						}
					}
				}
			}
		}

		if ($kind === "quiz_progress") {
			if ($progressPath !== "") {
				$dest = rkrk_assess_archive_quiz_progress_file($progressPath);
				if ($dest !== "") {
					$moved[] = $dest;
				}
			}
			if ($progressPath === "" && function_exists("rkrk_assess_quiz_progress_file_path")) {
				$path = rkrk_assess_quiz_progress_file_path(array(
					"quiz_key" => trim((string) ($item["quiz_key"] ?? $item["progress_quiz_key"] ?? "")),
					"name" => trim((string) ($item["delete_name"] ?? $item["progress_name"] ?? "")),
					"exam_code" => $examCode,
					"drone" => trim((string) ($item["drone"] ?? $item["progress_drone"] ?? "")),
					"schulung" => trim((string) ($item["schulung"] ?? $item["progress_schulung"] ?? "")),
					"quiz_modus" => trim((string) ($item["quiz_modus"] ?? $item["progress_quiz_modus"] ?? "")),
				));
				if ($path !== "" && is_file($path)) {
					$dest = rkrk_assess_archive_quiz_progress_file(basename($path));
					if ($dest !== "") {
						$moved[] = $dest;
					}
				}
			}
		}

		if ($kind === "theory_csv" || $kind === "exam_json" || $kind === "planned") {
			$resultsDir = "";
			if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
				$resultsDir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/");
			}
			$match = array(
				"name" => trim((string) ($item["delete_name"] ?? $item["progress_name"] ?? "")),
				"date" => trim((string) ($item["delete_date"] ?? "")),
				"quiz" => trim((string) ($item["delete_quiz"] ?? $item["quiz_key"] ?? $item["progress_quiz_key"] ?? "")),
				"exam_code" => $examCode,
				"line_index" => isset($item["csv_line_index"]) ? (int) $item["csv_line_index"] : -1,
			);
			if ($resultsDir !== "" && ($match["name"] !== "" || $match["exam_code"] !== "" || $match["line_index"] >= 0)) {
				$quizRow = rkrk_assess_archive_quiz_result_row($resultsDir, $match);
			}
		}

		$ok = ($moved !== array()) || $quizRow || trim((string) ($item["manager_id"] ?? "")) !== "";
		rkrk_assess_archive_log(array(
			"kind" => $kind,
			"exam_code" => $examCode,
			"exam_stem" => $examStem,
			"result_id" => $resultId,
			"moved" => $moved,
			"quiz_row" => $quizRow,
			"ok" => $ok,
		), $reportsKind);
		return array(
			"ok" => $ok,
			"moved" => $moved,
			"quiz_row" => $quizRow,
			"exam_code" => $examCode,
		);
	}
}
