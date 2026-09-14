<?php
/**
 * Ordnerlayout unter …/results/:
 *   checklists/, checklists_progress/, exams/, exams_progress/, reports/
 * Idempotent: verschiebt/benennt Altpfade und Dateien ins Ziel.
 */

if (!function_exists("rkrk_assess_storage_migrate_ensure_dir")) {
	function rkrk_assess_storage_migrate_ensure_dir(string $dir): bool {
		$dir = rtrim(str_replace("\\", "/", $dir), "/") . "/";
		if ($dir === "/" || $dir === "./") {
			return false;
		}
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir);
	}
}

if (!function_exists("rkrk_assess_storage_migrate_move_file")) {
	function rkrk_assess_storage_migrate_move_file(string $from, string $to): void {
		$from = str_replace("\\", "/", $from);
		$to = str_replace("\\", "/", $to);
		if ($from === "" || $to === "" || !is_file($from) || $from === $to) {
			return;
		}
		if (is_file($to)) {
			return;
		}
		$parent = dirname($to);
		if (!is_dir($parent)) {
			@mkdir($parent, 0755, true);
		}
		@rename($from, $to);
	}
}

if (!function_exists("rkrk_assess_storage_migrate_merge_dir")) {
	/**
	 * Verschiebt Inhalt von $from nach $to; leert/entfernt $from wenn möglich.
	 * Wenn $from fehlt oder == $to: nichts. Wenn $to fehlt: rename.
	 */
	function rkrk_assess_storage_migrate_merge_dir(string $from, string $to): void {
		$from = rtrim(str_replace("\\", "/", $from), "/");
		$to = rtrim(str_replace("\\", "/", $to), "/");
		if ($from === "" || $to === "" || $from === $to || !is_dir($from)) {
			return;
		}
		if (!is_dir($to)) {
			$parent = dirname($to);
			if (!is_dir($parent)) {
				@mkdir($parent, 0755, true);
			}
			if (@rename($from, $to)) {
				return;
			}
			if (!rkrk_assess_storage_migrate_ensure_dir($to)) {
				return;
			}
		}
		$entries = @scandir($from);
		if (!is_array($entries)) {
			return;
		}
		foreach ($entries as $name) {
			if ($name === "." || $name === "..") {
				continue;
			}
			$src = $from . "/" . $name;
			$dst = $to . "/" . $name;
			if (is_dir($src)) {
				rkrk_assess_storage_migrate_merge_dir($src, $dst);
				@rmdir($src);
				continue;
			}
			if (is_file($src)) {
				rkrk_assess_storage_migrate_move_file($src, $dst);
			}
		}
		@rmdir($from);
	}
}

if (!function_exists("rkrk_assess_storage_migrate_json_files")) {
	/**
	 * @param list<string> $skipBasenames
	 */
	function rkrk_assess_storage_migrate_json_files(string $fromDir, string $toDir, array $skipBasenames = array()): void {
		$fromDir = rtrim(str_replace("\\", "/", $fromDir), "/") . "/";
		$toDir = rtrim(str_replace("\\", "/", $toDir), "/") . "/";
		if (!is_dir($fromDir)) {
			return;
		}
		if (!rkrk_assess_storage_migrate_ensure_dir($toDir)) {
			return;
		}
		$files = glob($fromDir . "*.json");
		if (!is_array($files)) {
			return;
		}
		foreach ($files as $file) {
			if (!is_string($file) || !is_file($file)) {
				continue;
			}
			$bn = basename($file);
			if (in_array($bn, $skipBasenames, true)) {
				continue;
			}
			rkrk_assess_storage_migrate_move_file($file, $toDir . $bn);
		}
	}
}

if (!function_exists("rkrk_assess_storage_migrate_report_files")) {
	function rkrk_assess_storage_migrate_report_files(string $fromDir, string $toDir): void {
		$fromDir = rtrim(str_replace("\\", "/", $fromDir), "/") . "/";
		$toDir = rtrim(str_replace("\\", "/", $toDir), "/") . "/";
		if (!is_dir($fromDir)) {
			return;
		}
		if (!rkrk_assess_storage_migrate_ensure_dir($toDir)) {
			return;
		}
		foreach (array("*.pdf", "*.html") as $pattern) {
			$files = glob($fromDir . $pattern);
			if (!is_array($files)) {
				continue;
			}
			foreach ($files as $file) {
				if (is_string($file) && is_file($file)) {
					rkrk_assess_storage_migrate_move_file($file, $toDir . basename($file));
				}
			}
		}
	}
}

if (!function_exists("rkrk_assess_storage_migrate_legacy_layout")) {
	function rkrk_assess_storage_migrate_legacy_layout(): void {
		static $ran = false;
		if ($ran) {
			return;
		}
		$ran = true;

		$quizLoc = "";
		if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$quizLoc = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/";
		}
		if ($quizLoc !== "") {
			$results = $quizLoc . "results/";
			rkrk_assess_storage_migrate_ensure_dir($results);

			// Falsche Top-Level-Ordner (vorherige Migration) → results/
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "checklists", $results . "checklists");
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "checklists_progress", $results . "checklists_progress");
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "exams", $results . "exams");
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "progress", $results . "exams_progress");
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "exams_progress", $results . "exams_progress");
			rkrk_assess_storage_migrate_merge_dir($quizLoc . "reports", $results . "reports");

			// Alte results/-Unterordner → neue Namen
			rkrk_assess_storage_migrate_merge_dir($results . "checklist_progress", $results . "checklists_progress");
			rkrk_assess_storage_migrate_merge_dir($results . "exams_pdf", $results . "reports");
			rkrk_assess_storage_migrate_merge_dir($results . "progress", $results . "exams_progress");
			rkrk_assess_storage_migrate_merge_dir($results . "exams_porgress", $results . "exams_progress");

			// Lose Checklist-JSON / Reports in results/ Root
			rkrk_assess_storage_migrate_json_files(
				$results,
				$results . "checklists/",
				array("QuizResultate.json")
			);
			rkrk_assess_storage_migrate_report_files($results, $results . "reports/");

			// Fehlplatzierte Exam-Sessions aus checklists/ → exams/
			$clDir = $results . "checklists/";
			$exDir = $results . "exams/";
			if (is_dir($clDir)) {
				rkrk_assess_storage_migrate_ensure_dir($exDir);
				foreach (glob($clDir . "*.json") ?: array() as $file) {
					if (!is_string($file) || !is_file($file)) {
						continue;
					}
					$raw = @file_get_contents($file);
					if (!is_string($raw) || trim($raw) === "") {
						continue;
					}
					$data = @json_decode($raw, true);
					if (!is_array($data)) {
						continue;
					}
					$isExam = (isset($data["exam_code"]) || isset($data["attempts"]))
						&& (!isset($data["template"]) || trim((string) $data["template"]) === "")
						&& (!isset($data["fields"]) || !is_array($data["fields"]));
					if (!$isExam) {
						continue;
					}
					rkrk_assess_storage_migrate_move_file($file, $exDir . basename($file));
				}
			}

			// QuizResultate ggf. fälschlich unter data/assess/
			rkrk_assess_storage_migrate_move_file(
				$quizLoc . "QuizResultate.json",
				$results . "QuizResultate.json"
			);
			foreach (array("QuizLog.csv", "QuizResultate.csv", "AssessmentManager.csv") as $csv) {
				rkrk_assess_storage_migrate_move_file($quizLoc . $csv, $results . $csv);
			}
		}

		$liveData = "";
		if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
			$liveData = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/";
		}
		if ($liveData !== "") {
			$liveResults = $liveData . "results/";
			rkrk_assess_storage_migrate_ensure_dir($liveResults);

			rkrk_assess_storage_migrate_merge_dir($liveData . "checklists", $liveResults . "checklists");
			rkrk_assess_storage_migrate_merge_dir($liveData . "checklists_progress", $liveResults . "checklists_progress");
			rkrk_assess_storage_migrate_merge_dir($liveData . "reports", $liveResults . "reports");

			rkrk_assess_storage_migrate_merge_dir($liveResults . "checklist_progress", $liveResults . "checklists_progress");
			rkrk_assess_storage_migrate_json_files($liveResults, $liveResults . "checklists/");
			rkrk_assess_storage_migrate_report_files($liveResults, $liveResults . "reports/");
		}
	}
}
