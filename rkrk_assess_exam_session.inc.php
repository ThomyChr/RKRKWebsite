<?php
/**
 * Feste Fragebogen-Sitzungen per URL-Parameter EXAM=Code (Schulungsprüfung).
 * Ein Code erzeugt einmalig einen gemischten Fragenkatalog; nach Abschluss nur noch Ergebnis.
 */

if (!function_exists("rkrk_assess_quiz_modus_normalize")) {
	/** URL/Anzeige: quiz | pruefung | "" */
	function rkrk_assess_quiz_modus_normalize(string $modus): string {
		$v = strtolower(trim($modus));
		if ($v === "pruefung" || $v === "prüfung") {
			return "pruefung";
		}
		if ($v === "quiz") {
			return "quiz";
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_quiz_modus_storage_token")) {
	/** Fortschritt/Sitzung: pruefung oder "" (Training inkl. QuizModus=quiz). */
	function rkrk_assess_quiz_modus_storage_token(string $modus): string {
		return rkrk_assess_quiz_modus_normalize($modus) === "pruefung" ? "pruefung" : "";
	}
}

if (!function_exists("rkrk_assess_quiz_modus_equal")) {
	function rkrk_assess_quiz_modus_equal(string $a, string $b): bool {
		return rkrk_assess_quiz_modus_storage_token($a) === rkrk_assess_quiz_modus_storage_token($b);
	}
}

if (!function_exists("rkrk_assess_exam_normalize_code")) {
	function rkrk_assess_exam_normalize_code($code): string {
		$code = trim((string) $code);
		if ($code === "") {
			return "";
		}
		$code = preg_replace("/[^A-Za-z0-9_-]/", "", $code);
		if ($code === "") {
			return "";
		}
		return substr($code, 0, 48);
	}
}

if (!function_exists("rkrk_assess_exam_read_code_from_request")) {
	function rkrk_assess_exam_read_code_from_request(): string {
		if (isset($_POST["EXAM"])) {
			return rkrk_assess_exam_normalize_code($_POST["EXAM"]);
		}
		if (isset($_GET["EXAM"])) {
			return rkrk_assess_exam_normalize_code($_GET["EXAM"]);
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_exam_storage_dir")) {
	function rkrk_assess_exam_storage_dir(): string {
		$base = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/";
		} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/results/";
		}
		if ($base === "") {
			return "";
		}
		$dir = $base . "exams/";
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_exam_pilot_name_slug")) {
	function rkrk_assess_exam_pilot_name_slug(string $name): string {
		$name = trim($name);
		if ($name === "") {
			return "";
		}
		if (function_exists("rkrk_assess_slugify_file_stem")) {
			return rkrk_assess_slugify_file_stem($name);
		}
		$slug = preg_replace("/[^A-Za-z0-9_-]+/", "_", $name);
		$slug = preg_replace("/_+/", "_", (string) $slug);
		$slug = trim((string) $slug, "_");
		return strtolower(substr($slug, 0, 48));
	}
}

if (!function_exists("rkrk_assess_exam_pilot_name_from_session")) {
	/** @param array<string, mixed> $session */
	function rkrk_assess_exam_pilot_name_from_session(array $session): string {
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		if (count($attempts) === 1) {
			$attempt = reset($attempts);
			if (is_array($attempt)) {
				return trim((string) ($attempt["name"] ?? ""));
			}
		}
		return trim((string) ($session["name"] ?? ""));
	}
}

include_once __DIR__ . "/rkrk_assess_result_stem.inc.php";

if (!function_exists("rkrk_assess_exam_quiz_title_for_stem")) {
	/** @param array<string, mixed>|null $session */
	function rkrk_assess_exam_quiz_title_for_stem(string $code, ?array $session = null): string {
		$quizKey = "";
		if (is_array($session)) {
			$quizKey = trim((string) ($session["quiz_key"] ?? ""));
		}
		if ($quizKey === "") {
			$code = trim($code);
			if (preg_match('/^(.*)-\d{8}-\d{4}$/', $code, $m)) {
				$quizKey = trim((string) $m[1]);
			} else {
				$quizKey = $code;
			}
		}
		if ($quizKey !== "" && function_exists("rkrk_assess_quiz_title_from_key")) {
			$title = rkrk_assess_quiz_title_from_key($quizKey);
			if ($title !== "") {
				return $title;
			}
		}
		return $quizKey !== "" ? $quizKey : $code;
	}
}

if (!function_exists("rkrk_assess_exam_rkrs_id_from_session")) {
	/** @param array<string, mixed>|null $session */
	function rkrk_assess_exam_rkrs_id_from_session(?array $session, string $name = ""): string {
		if (!is_array($session)) {
			return "";
		}
		foreach (array("rkrs_pilot_id", "RKRSPilotID", "PilotID") as $k) {
			$v = trim((string) ($session[$k] ?? ""));
			if ($v !== "") {
				return $v;
			}
		}
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		foreach ($attempts as $attempt) {
			if (!is_array($attempt)) {
				continue;
			}
			if ($name !== "") {
				$an = trim((string) ($attempt["name"] ?? ""));
				if ($an !== "" && function_exists("rkrk_assess_exam_pilot_key")
					&& rkrk_assess_exam_pilot_key($an) !== rkrk_assess_exam_pilot_key($name)) {
					continue;
				}
			}
			foreach (array("rkrs_pilot_id", "RKRSPilotID", "PilotID") as $k) {
				$v = trim((string) ($attempt[$k] ?? ""));
				if ($v !== "") {
					return $v;
				}
			}
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_exam_stem_with_date")) {
	/** @param array<string, mixed>|null $session */
	function rkrk_assess_exam_stem_with_date(?array $session, string $name = ""): bool {
		if (!is_array($session)) {
			return true;
		}
		if ($name !== "" && function_exists("rkrk_assess_exam_get_attempt")) {
			$attempt = rkrk_assess_exam_get_attempt($session, $name);
			if (is_array($attempt)) {
				return strtolower(trim((string) ($attempt["status"] ?? ""))) === "completed";
			}
		}
		if (function_exists("rkrk_assess_exam_is_completed") && rkrk_assess_exam_is_completed($session, $name)) {
			return true;
		}
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		$saw = false;
		foreach ($attempts as $attempt) {
			if (!is_array($attempt)) {
				continue;
			}
			$saw = true;
			if (strtolower(trim((string) ($attempt["status"] ?? ""))) === "completed") {
				return true;
			}
		}
		if ($saw) {
			return false;
		}
		return strtolower(trim((string) ($session["status"] ?? ""))) === "completed";
	}
}

if (!function_exists("rkrk_assess_exam_file_stem")) {
	/** @param array<string, mixed>|null $session */
	function rkrk_assess_exam_file_stem(
		string $code,
		string $name = "",
		string $stampOrDate = "",
		bool $withCompletionDate = true,
		?array $session = null
	): string {
		$code = rkrk_assess_exam_normalize_code($code);
		$title = rkrk_assess_exam_quiz_title_for_stem($code, $session);
		if ($title === "") {
			$title = $code !== "" ? $code : "result";
		}
		$name = trim($name);
		$modus = is_array($session) ? (string) ($session["quiz_modus"] ?? "") : "";
		$rkrsId = rkrk_assess_exam_rkrs_id_from_session($session, $name);
		if (function_exists("rkrk_assess_build_quiz_result_stem")) {
			return rkrk_assess_build_quiz_result_stem($title, $name, $rkrsId, $modus, $stampOrDate, $withCompletionDate);
		}
		return rkrk_assess_build_result_stem($title, $name, $stampOrDate, $withCompletionDate, $rkrsId);
	}
}

if (!function_exists("rkrk_assess_exam_candidate_paths")) {
	/**
	 * @return list<string>
	 */
	function rkrk_assess_exam_candidate_paths(string $code, string $name = ""): array {
		$dir = rkrk_assess_exam_storage_dir();
		$code = rkrk_assess_exam_normalize_code($code);
		if ($dir === "" || $code === "") {
			return array();
		}
		$safe = strtolower($code);
		$name = trim($name);
		$paths = array();
		$seen = array();
		$add = static function (string $path) use (&$paths, &$seen): void {
			if ($path === "" || isset($seen[$path])) {
				return;
			}
			$seen[$path] = true;
			$paths[] = $path;
		};
		if ($name !== "") {
			$slug = rkrk_assess_exam_pilot_name_slug($name);
			if ($slug !== "") {
				$base = $dir . $safe . "_" . $slug;
				$add($base . ".json");
				foreach (glob($base . "_*.json") ?: array() as $file) {
					if (is_string($file)) {
						$add($file);
					}
				}
			}
		}
		$add($dir . $safe . ".json");
		$nameKey = ($name !== "" && function_exists("rkrk_assess_exam_pilot_key"))
			? rkrk_assess_exam_pilot_key($name)
			: strtolower($name);
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_string($file) || !is_file($file)) {
				continue;
			}
			$data = rkrk_assess_exam_read_json_file($file);
			if (!is_array($data)) {
				continue;
			}
			$fileCode = rkrk_assess_exam_normalize_code($data["exam_code"] ?? "");
			if ($fileCode === "" || strtolower($fileCode) !== $safe) {
				continue;
			}
			if ($nameKey !== "") {
				$fileName = function_exists("rkrk_assess_exam_pilot_name_from_session")
					? rkrk_assess_exam_pilot_name_from_session($data)
					: trim((string) ($data["name"] ?? ""));
				$fileNameKey = function_exists("rkrk_assess_exam_pilot_key")
					? rkrk_assess_exam_pilot_key($fileName)
					: strtolower($fileName);
				if ($fileNameKey !== "" && $fileNameKey !== $nameKey) {
					$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
					if (!isset($attempts[$nameKey])) {
						continue;
					}
				}
			}
			$add($file);
		}
		// Frühere Fehlplatzierung: data/assess/exams/ statt results/exams/
		if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$legacyDir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/exams/";
			if (is_dir($legacyDir)) {
				if ($name !== "") {
					$slug = rkrk_assess_exam_pilot_name_slug($name);
					if ($slug !== "") {
						$base = $legacyDir . $safe . "_" . $slug;
						$add($base . ".json");
						foreach (glob($base . "_*.json") ?: array() as $file) {
							if (is_string($file)) {
								$add($file);
							}
						}
					}
				}
				$add($legacyDir . $safe . ".json");
			}
		}
		return $paths;
	}
}

if (!function_exists("rkrk_assess_exam_resolve_storage_path")) {
	/**
	 * @param array<string, mixed>|null $session
	 */
	function rkrk_assess_exam_resolve_storage_path(string $code, string $name = "", ?array $session = null): string {
		$name = trim($name);
		if (is_array($session)) {
			$existingStem = trim((string) ($session["result_stem"] ?? ""));
			if ($existingStem !== "") {
				$dir = rkrk_assess_exam_storage_dir();
				if ($dir !== "") {
					$p = $dir . $existingStem . ".json";
					if (is_file($p)) {
						return $p;
					}
				}
			}
		}
		foreach (rkrk_assess_exam_candidate_paths($code, $name) as $path) {
			if (is_file($path)) {
				return $path;
			}
		}
		$stamp = rkrk_assess_exam_completion_stamp_from_session($session);
		$withDate = rkrk_assess_exam_stem_with_date($session, $name);
		return rkrk_assess_exam_file_path($code, $name, $stamp, $withDate, $session);
	}
}

if (!function_exists("rkrk_assess_exam_file_path")) {
	function rkrk_assess_exam_file_path(
		string $code,
		string $name = "",
		string $stampOrDate = "",
		bool $withCompletionDate = true,
		?array $session = null
	): string {
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return "";
		}
		$stem = rkrk_assess_exam_file_stem($code, $name, $stampOrDate, $withCompletionDate, $session);
		if ($stem === "") {
			return "";
		}
		return $dir . $stem . ".json";
	}
}

if (!function_exists("rkrk_assess_exam_read_json_file")) {
	/** @return array<string, mixed>|null */
	function rkrk_assess_exam_read_json_file(string $path): ?array {
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

if (!function_exists("rkrk_assess_exam_apply_session_migrations")) {
	/** @param array<string, mixed> $data */
	function rkrk_assess_exam_apply_session_migrations(string $code, array &$data, string $name = ""): bool {
		$changed = false;
		if (function_exists("rkrk_assess_exam_migrate_legacy_session") && rkrk_assess_exam_migrate_legacy_session($data)) {
			$changed = true;
		}
		if (function_exists("rkrk_assess_exam_migrate_root_catalog_to_attempts") && rkrk_assess_exam_migrate_root_catalog_to_attempts($data)) {
			$changed = true;
		}
		if ($changed) {
			rkrk_assess_exam_save($code, $data, $name);
		}
		return $changed;
	}
}

if (!function_exists("rkrk_assess_exam_load")) {
	/**
	 * @return array<string, mixed>|null
	 */
	function rkrk_assess_exam_load(string $code, string $name = ""): ?array {
		$code = rkrk_assess_exam_normalize_code($code);
		if ($code === "") {
			return null;
		}
		$name = trim($name);
		foreach (rkrk_assess_exam_candidate_paths($code, $name) as $path) {
			$data = rkrk_assess_exam_read_json_file($path);
			if (!is_array($data)) {
				continue;
			}
			rkrk_assess_exam_apply_session_migrations($code, $data, $name);
			return $data;
		}
		return null;
	}
}

if (!function_exists("rkrk_assess_exam_pilot_key")) {
	function rkrk_assess_exam_pilot_key(string $name): string {
		$name = trim($name);
		if ($name === "") {
			return "";
		}
		if (function_exists("mb_strtolower")) {
			return mb_strtolower($name, "UTF-8");
		}
		return strtolower($name);
	}
}

if (!function_exists("rkrk_assess_exam_migrate_legacy_session")) {
	/** @param array<string, mixed> $session */
	function rkrk_assess_exam_migrate_legacy_session(array &$session): bool {
		$changed = false;
		if (!isset($session["attempts"]) || !is_array($session["attempts"])) {
			$session["attempts"] = array();
			$changed = true;
		}
		if (count($session["attempts"]) > 0) {
			return $changed;
		}
		$legacyName = trim((string) ($session["name"] ?? ""));
		$legacyStatus = strtolower(trim((string) ($session["status"] ?? "")));
		if ($legacyName === "" || ($legacyStatus !== "completed" && $legacyStatus !== "in_progress")) {
			return $changed;
		}
		$key = rkrk_assess_exam_pilot_key($legacyName);
		if ($key === "") {
			return $changed;
		}
		$attempt = array(
			"name" => $legacyName,
			"drone" => (string) ($session["drone"] ?? ""),
			"status" => $legacyStatus,
			"created_at" => (string) ($session["created_at"] ?? gmdate("c")),
		);
		if ($legacyStatus === "completed") {
			$attempt["completed_at"] = (string) ($session["completed_at"] ?? gmdate("c"));
			$attempt["result"] = isset($session["result"]) && is_array($session["result"]) ? $session["result"] : array();
		}
		$rootSnaps = isset($session["question_snapshots"]) && is_array($session["question_snapshots"])
			? $session["question_snapshots"]
			: array();
		if (count($rootSnaps) > 0) {
			$attempt["question_snapshots"] = $rootSnaps;
			$attempt["question_order"] = isset($session["question_order"]) && is_array($session["question_order"])
				? $session["question_order"]
				: array();
			$attempt["question_fingerprint"] = (string) ($session["question_fingerprint"] ?? "");
			$attempt["max_questions"] = (int) ($session["max_questions"] ?? count($rootSnaps));
		}
		$session["attempts"][$key] = $attempt;
		return true;
	}
}

if (!function_exists("rkrk_assess_exam_attempt_has_catalog")) {
	/** @param array<string, mixed> $attempt */
	function rkrk_assess_exam_attempt_has_catalog(array $attempt): bool {
		$snaps = isset($attempt["question_snapshots"]) && is_array($attempt["question_snapshots"])
			? $attempt["question_snapshots"]
			: array();
		return count($snaps) > 0;
	}
}

if (!function_exists("rkrk_assess_exam_migrate_root_catalog_to_attempts")) {
	/** @param array<string, mixed> $session */
	function rkrk_assess_exam_migrate_root_catalog_to_attempts(array &$session): bool {
		$rootSnaps = isset($session["question_snapshots"]) && is_array($session["question_snapshots"])
			? $session["question_snapshots"]
			: array();
		if (count($rootSnaps) < 1) {
			return false;
		}
		if (!isset($session["attempts"]) || !is_array($session["attempts"])) {
			$session["attempts"] = array();
		}
		$changed = false;
		foreach ($session["attempts"] as $key => $attempt) {
			if (!is_array($attempt)) {
				continue;
			}
			if (rkrk_assess_exam_attempt_has_catalog($attempt)) {
				continue;
			}
			if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "in_progress") {
				continue;
			}
			$session["attempts"][$key]["question_snapshots"] = $rootSnaps;
			$session["attempts"][$key]["question_order"] = isset($session["question_order"]) && is_array($session["question_order"])
				? $session["question_order"]
				: array();
			$session["attempts"][$key]["question_fingerprint"] = (string) ($session["question_fingerprint"] ?? "");
			$mq = (int) ($session["max_questions"] ?? 0);
			if ($mq < 1) {
				$mq = count($rootSnaps);
			}
			$session["attempts"][$key]["max_questions"] = $mq;
			$changed = true;
		}
		return $changed;
	}
}

if (!function_exists("rkrk_assess_exam_get_attempt")) {
	/** @return array<string, mixed>|null */
	function rkrk_assess_exam_get_attempt(?array $session, string $name): ?array {
		if (!is_array($session)) {
			return null;
		}
		$key = rkrk_assess_exam_pilot_key($name);
		if ($key === "") {
			return null;
		}
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		$attempt = $attempts[$key] ?? null;
		return is_array($attempt) ? $attempt : null;
	}
}

if (!function_exists("rkrk_assess_exam_ensure_attempt")) {
	/** @param array<string, mixed> $session @param array<string, string> $ctx */
	function rkrk_assess_exam_ensure_attempt(string $code, array &$session, array $ctx): bool {
		$name = trim((string) ($ctx["name"] ?? ""));
		if ($name === "") {
			return false;
		}
		if (!isset($session["attempts"]) || !is_array($session["attempts"])) {
			$session["attempts"] = array();
		}
		$key = rkrk_assess_exam_pilot_key($name);
		if ($key === "") {
			return false;
		}
		if (isset($session["attempts"][$key]) && is_array($session["attempts"][$key])) {
			if (trim((string) ($session["attempts"][$key]["drone"] ?? "")) === "" && trim((string) ($ctx["drone"] ?? "")) !== "") {
				$session["attempts"][$key]["drone"] = (string) $ctx["drone"];
				return rkrk_assess_exam_save($code, $session, $name);
			}
			return true;
		}
		$session["attempts"][$key] = array(
			"name" => $name,
			"drone" => (string) ($ctx["drone"] ?? ""),
			"status" => "in_progress",
			"created_at" => gmdate("c"),
		);
		return rkrk_assess_exam_save($code, $session, $name);
	}
}

if (!function_exists("rkrk_assess_exam_save")) {
	/**
	 * @param array<string, mixed> $data
	 */
	function rkrk_assess_exam_save(string $code, array $data, string $name = ""): bool {
		$name = trim($name);
		if ($name === "") {
			$name = rkrk_assess_exam_pilot_name_from_session($data);
		}
		$rkrsId = rkrk_assess_exam_rkrs_id_from_session($data, $name);
		if ($rkrsId === "" && $name !== "" && function_exists("rkrk_assess_personel_rkrs_id_by_name")) {
			$rkrsId = rkrk_assess_personel_rkrs_id_by_name($name);
		}
		if ($rkrsId !== "") {
			$data["rkrs_pilot_id"] = $rkrsId;
		}
		$path = rkrk_assess_exam_resolve_storage_path($code, $name, $data);
		if ($path === "") {
			return false;
		}
		$sourcePath = is_file($path) ? $path : "";
		$oldStem = $sourcePath !== "" ? pathinfo($sourcePath, PATHINFO_FILENAME) : "";
		$stamp = rkrk_assess_exam_completion_stamp_from_session($data);
		$withDate = rkrk_assess_exam_stem_with_date($data, $name);
		$targetPath = rkrk_assess_exam_file_path($code, $name, $stamp, $withDate, $data);
		$dir = rkrk_assess_exam_storage_dir();
		if ($targetPath !== "" && $dir !== "" && function_exists("rkrk_assess_unique_checklist_result_stem")) {
			$wantStem = pathinfo($targetPath, PATHINFO_FILENAME);
			$uniq = rkrk_assess_unique_checklist_result_stem($dir, $wantStem, is_file($path) ? $path : null);
			if ($uniq !== $wantStem) {
				$targetPath = $dir . $uniq . ".json";
			}
		}
		if ($targetPath !== "" && $targetPath !== $path && is_file($path)) {
			if (is_file($targetPath) && realpath($targetPath) !== realpath($path)) {
				@unlink($targetPath);
			}
			if (@rename($path, $targetPath)) {
				$path = $targetPath;
			}
		} elseif ($targetPath !== "") {
			$path = $targetPath;
		}
		$newStem = pathinfo($path, PATHINFO_FILENAME);
		if ($oldStem !== "" && $newStem !== "" && $oldStem !== $newStem && function_exists("rkrk_assess_rename_stem_sidecars") && function_exists("rkrk_assess_reports_dir")) {
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
		$data["format"] = "rkropen_exam_session_v1";
		$data["result_stem"] = $newStem;
		$data["exam_code"] = rkrk_assess_exam_normalize_code($code);
		$data["updated_at"] = gmdate("c");
		if (!isset($data["created_at"]) || trim((string) $data["created_at"]) === "") {
			$data["created_at"] = $data["updated_at"];
		}
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if (!is_string($json)) {
			return false;
		}
		$tmp = $path . ".tmp." . getmypid();
		if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
			return false;
		}
		if (!@rename($tmp, $path)) {
			@unlink($tmp);
			return false;
		}
		// WebDAV/NAS: rename der Session-Datei kann fehlschlagen — dann bleibt die
		// undatierte in_progress-Datei neben der datierten Abschlussdatei stehen.
		if ($sourcePath !== "" && $sourcePath !== $path && is_file($sourcePath)) {
			$srcReal = realpath($sourcePath);
			$dstReal = realpath($path);
			if ($srcReal === false || $dstReal === false || $srcReal !== $dstReal) {
				@unlink($sourcePath);
			}
		}
		return true;
	}
}

if (!function_exists("rkrk_assess_exam_context_from_request")) {
	/**
	 * @return array{quiz_key:string, setting:string, schulung:string, quiz_modus:string, name:string, drone:string}
	 */
	function rkrk_assess_exam_context_from_request(
		string $quizKey,
		string $setting,
		string $schulung,
		string $quizModus,
		string $name,
		string $drone
	): array {
		return array(
			"quiz_key" => trim($quizKey),
			"setting" => trim($setting),
			"schulung" => trim($schulung),
			"quiz_modus" => trim($quizModus),
			"name" => trim($name),
			"drone" => function_exists("rkrk_assess_normalize_drone")
				? rkrk_assess_normalize_drone($drone)
				: trim($drone),
		);
	}
}

if (!function_exists("rkrk_assess_exam_matches_context")) {
	/**
	 * @param array<string, mixed> $session
	 * @param array<string, string> $ctx
	 */
	function rkrk_assess_exam_matches_context(array $session, array $ctx): bool {
		if (trim((string) ($session["quiz_key"] ?? "")) === "") {
			return true;
		}
		if (
			function_exists("rkrk_assess_quiz_progress_quiz_keys_equal")
			&& !rkrk_assess_quiz_progress_quiz_keys_equal(
				(string) ($session["quiz_key"] ?? ""),
				(string) ($ctx["quiz_key"] ?? "")
			)
		) {
			return false;
		}
		if (strcasecmp((string) ($session["setting"] ?? ""), (string) ($ctx["setting"] ?? "")) !== 0) {
			return false;
		}
		if (strcasecmp((string) ($session["schulung"] ?? ""), (string) ($ctx["schulung"] ?? "")) !== 0) {
			return false;
		}
		if (!rkrk_assess_quiz_modus_equal(
			(string) ($session["quiz_modus"] ?? ""),
			(string) ($ctx["quiz_modus"] ?? "")
		)) {
			return false;
		}
		return true;
	}
}

if (!function_exists("rkrk_assess_exam_build_snapshots_from_questions")) {
	/**
	 * @param list<array<string, mixed>> $questions
	 * @return list<array<string, mixed>>
	 */
	function rkrk_assess_exam_build_snapshots_from_questions(array $questions): array {
		if (!function_exists("rkrk_assess_quiz_progress_snapshot_fields")) {
			return array();
		}
		$fields = rkrk_assess_quiz_progress_snapshot_fields();
		$snaps = array();
		foreach ($questions as $i => $qRow) {
			if (!is_array($qRow)) {
				continue;
			}
			$qid = isset($qRow["QID"]) ? trim((string) $qRow["QID"]) : (string) $i;
			if ($qid === "") {
				$qid = (string) $i;
			}
			$snap = array("qid" => $qid);
			foreach ($fields as $f) {
				if ($f === "qid") {
					continue;
				}
				if ($f === "Loesungsauswahl") {
					$opts = $qRow[$f] ?? array();
					if (!is_array($opts)) {
						$rawOpts = (string) $opts;
						$opts = $rawOpts !== "" ? array_map("trim", explode("|", $rawOpts)) : array();
					}
					$snap[$f] = array_values($opts);
				} else {
					$snap[$f] = (string) ($qRow[$f] ?? "");
				}
			}
			$snaps[] = $snap;
		}
		return $snaps;
	}
}

if (!function_exists("rkrk_assess_exam_progress_view_for_pilot")) {
	/**
	 * @param array<string, mixed> $session
	 * @return array<string, mixed>|null
	 */
	function rkrk_assess_exam_progress_view_for_pilot(array $session, string $name): ?array {
		$attempt = rkrk_assess_exam_get_attempt($session, $name);
		if (is_array($attempt) && rkrk_assess_exam_attempt_has_catalog($attempt)) {
			return array(
				"question_snapshots" => $attempt["question_snapshots"],
				"question_order" => isset($attempt["question_order"]) && is_array($attempt["question_order"])
					? $attempt["question_order"]
					: array(),
				"question_fingerprint" => (string) ($attempt["question_fingerprint"] ?? ""),
				"max_questions" => (int) ($attempt["max_questions"] ?? 0),
				"drive_location" => (string) ($session["drive_location"] ?? ""),
			);
		}
		return null;
	}
}

if (!function_exists("rkrk_assess_exam_questions_from_session")) {
	/**
	 * @param array<string, mixed> $session
	 * @return list<array<string, mixed>>|null
	 */
	function rkrk_assess_exam_questions_from_session(array $session, string $name = ""): ?array {
		if (!function_exists("rkrk_assess_quiz_questions_from_progress_snapshots")) {
			return null;
		}
		$view = trim($name) !== ""
			? rkrk_assess_exam_progress_view_for_pilot($session, $name)
			: null;
		if ($view === null) {
			$snaps = isset($session["question_snapshots"]) && is_array($session["question_snapshots"])
				? $session["question_snapshots"]
				: array();
			if (count($snaps) < 1) {
				return null;
			}
			$view = $session;
		}
		return rkrk_assess_quiz_questions_from_progress_snapshots($view);
	}
}

if (!function_exists("rkrk_assess_exam_has_catalog")) {
	/**
	 * @param array<string, mixed>|null $session
	 */
	function rkrk_assess_exam_has_catalog(?array $session, string $name = ""): bool {
		if (!is_array($session)) {
			return false;
		}
		if (trim($name) !== "") {
			$attempt = rkrk_assess_exam_get_attempt($session, $name);
			return is_array($attempt) && rkrk_assess_exam_attempt_has_catalog($attempt);
		}
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		foreach ($attempts as $attempt) {
			if (is_array($attempt) && rkrk_assess_exam_attempt_has_catalog($attempt)) {
				return true;
			}
		}
		$snaps = isset($session["question_snapshots"]) && is_array($session["question_snapshots"])
			? $session["question_snapshots"]
			: array();
		return count($snaps) > 0;
	}
}

if (!function_exists("rkrk_assess_exam_create_session")) {
	/**
	 * @param array<string, string> $ctx
	 * @param list<array<string, mixed>> $questions
	 */
	function rkrk_assess_exam_create_session(string $code, array $ctx, array $questions, string $driveLoc = ""): ?array {
		$code = rkrk_assess_exam_normalize_code($code);
		if ($code === "" || count($questions) < 1) {
			return null;
		}
		$name = trim((string) ($ctx["name"] ?? ""));
		$key = rkrk_assess_exam_pilot_key($name);
		if ($key === "") {
			return null;
		}
		$existing = rkrk_assess_exam_load($code, $name);
		if (is_array($existing)) {
			$attempt = rkrk_assess_exam_get_attempt($existing, $name);
			if (is_array($attempt)) {
				if (strtolower(trim((string) ($attempt["status"] ?? ""))) === "completed") {
					return $existing;
				}
				if (rkrk_assess_exam_attempt_has_catalog($attempt)) {
					return $existing;
				}
			}
		}
		$order = array();
		foreach ($questions as $qRow) {
			if (!is_array($qRow)) {
				continue;
			}
			$qid = isset($qRow["QID"]) ? trim((string) $qRow["QID"]) : "";
			if ($qid !== "") {
				$order[] = $qid;
			}
		}
		$snaps = rkrk_assess_exam_build_snapshots_from_questions($questions);
		if (count($snaps) < 1) {
			return null;
		}
		$fp = function_exists("rkrk_assess_quiz_progress_fingerprint_from_list")
			? rkrk_assess_quiz_progress_fingerprint_from_list($questions)
			: "";
		$attemptCatalog = array(
			"name" => $name,
			"drone" => (string) ($ctx["drone"] ?? ""),
			"status" => "in_progress",
			"created_at" => gmdate("c"),
			"question_order" => $order,
			"question_snapshots" => $snaps,
			"question_fingerprint" => $fp,
			"max_questions" => count($questions),
		);
		if (!is_array($existing)) {
			$data = array(
				"exam_code" => $code,
				"quiz_key" => (string) ($ctx["quiz_key"] ?? ""),
				"setting" => (string) ($ctx["setting"] ?? ""),
				"schulung" => (string) ($ctx["schulung"] ?? ""),
				"quiz_modus" => (string) ($ctx["quiz_modus"] ?? ""),
				"drive_location" => $driveLoc,
				"attempts" => array($key => $attemptCatalog),
				"created_at" => gmdate("c"),
			);
			if (!rkrk_assess_exam_save($code, $data, $name)) {
				return null;
			}
			return rkrk_assess_exam_load($code, $name);
		}
		rkrk_assess_exam_ensure_attempt($code, $existing, $ctx);
		$existing = rkrk_assess_exam_load($code, $name);
		if (!is_array($existing)) {
			return null;
		}
		if (!isset($existing["attempts"]) || !is_array($existing["attempts"])) {
			$existing["attempts"] = array();
		}
		$existing["attempts"][$key] = array_merge(
			is_array($existing["attempts"][$key] ?? null) ? $existing["attempts"][$key] : array(),
			$attemptCatalog
		);
		if (trim((string) ($existing["quiz_key"] ?? "")) === "") {
			$existing["quiz_key"] = (string) ($ctx["quiz_key"] ?? "");
		}
		if (trim((string) ($existing["setting"] ?? "")) === "") {
			$existing["setting"] = (string) ($ctx["setting"] ?? "");
		}
		if (trim((string) ($existing["schulung"] ?? "")) === "") {
			$existing["schulung"] = (string) ($ctx["schulung"] ?? "");
		}
		if (trim((string) ($existing["quiz_modus"] ?? "")) === "") {
			$existing["quiz_modus"] = (string) ($ctx["quiz_modus"] ?? "");
		}
		if (trim($driveLoc) !== "") {
			$existing["drive_location"] = $driveLoc;
		}
		if (!rkrk_assess_exam_save($code, $existing, $name)) {
			return null;
		}
		return rkrk_assess_exam_load($code, $name);
	}
}

if (!function_exists("rkrk_assess_exam_mark_completed")) {
	/**
	 * @param array<string, mixed> $result
	 */
	function rkrk_assess_exam_mark_completed(string $code, array $result, string $name = ""): bool {
		$name = trim($name) !== "" ? trim($name) : trim((string) ($result["name"] ?? ""));
		if ($name === "") {
			return false;
		}
		// Bestanden/erfüllt-Status ins Ergebnis schreiben (Ja/Nein), analog zur Ergebnisliste.
		$__pe = is_numeric((string) ($result["punkte_erreicht"] ?? "")) ? (float) $result["punkte_erreicht"] : 0.0;
		$__pm = is_numeric((string) ($result["punkte_max"] ?? "")) ? (float) $result["punkte_max"] : 0.0;
		if ($__pe <= 0.0 && is_numeric((string) ($result["fragen_richtig"] ?? ""))) {
			$__pe = (float) ((int) $result["fragen_richtig"]);
		}
		if ($__pm <= 0.0 && is_numeric((string) ($result["anz_fragen"] ?? ""))) {
			$__pm = (float) ((int) $result["anz_fragen"]);
		}
		$__thr = is_numeric((string) ($result["erfuellt"] ?? "")) ? (float) $result["erfuellt"] : 0.0;
		$__passed = ($__pm > 0.0) && (($__pe / $__pm) >= $__thr);
		$result["bestanden"] = $__passed ? "Ja" : "Nein";
		$result["erfuellt_faktor"] = rtrim(rtrim(number_format($__thr, 4, ".", ""), "0"), ".");
		if (trim((string) ($result["zeit"] ?? "")) === "") {
			$result["zeit"] = date("d.m.Y H:i");
		}
		$session = rkrk_assess_exam_load($code, $name);
		if (!is_array($session)) {
			return false;
		}
		$ctx = array("name" => $name, "drone" => (string) ($result["drone"] ?? ""));
		rkrk_assess_exam_ensure_attempt($code, $session, $ctx);
		$session = rkrk_assess_exam_load($code, $name);
		if (!is_array($session)) {
			return false;
		}
		$key = rkrk_assess_exam_pilot_key($name);
		if ($key === "" || !isset($session["attempts"][$key]) || !is_array($session["attempts"][$key])) {
			return false;
		}
		$session["attempts"][$key]["status"] = "completed";
		$session["attempts"][$key]["completed_at"] = gmdate("c");
		$session["attempts"][$key]["result"] = $result;
		$session["attempts"][$key]["name"] = $name;
		if (trim((string) ($result["drone"] ?? "")) !== "") {
			$session["attempts"][$key]["drone"] = (string) $result["drone"];
		}
		$saved = rkrk_assess_exam_save($code, $session, $name);
		if ($saved && function_exists("rkrk_assess_quiz_progress_delete_best_effort")) {
			rkrk_assess_quiz_progress_delete_best_effort(array(
				"quiz_key" => (string) ($session["quiz_key"] ?? ""),
				"name" => $name,
				"drone" => (string) ($result["drone"] ?? ($session["attempts"][$key]["drone"] ?? "")),
				"setting" => (string) ($session["setting"] ?? ""),
				"schulung" => (string) ($session["schulung"] ?? ""),
				"quiz_modus" => (string) ($session["quiz_modus"] ?? ""),
				"exam_code" => $code,
			));
		}
		return $saved;
	}
}

if (!function_exists("rkrk_assess_exam_generate_auto_code")) {
	/**
	 * Einfacher, lesbarer Auto-Code für Läufe ohne EXAM-Parameter: <quizkey>-<YYYYMMDD-HHMM>.
	 * Beispiel: RKRSp-EP-preuf-20260713-1352 (Datum + Uhrzeit des Abschlusses).
	 *
	 * @param array<string, string> $ctx
	 */
	function rkrk_assess_exam_generate_auto_code(array $ctx): string {
		$quizKey = trim((string) ($ctx["quiz_key"] ?? ""));
		if ($quizKey === "") {
			$quizKey = "quiz";
		}
		$code = $quizKey . "-" . date("Ymd-Hi");
		return rkrk_assess_exam_normalize_code($code);
	}
}

if (!function_exists("rkrk_assess_exam_save_completed_standalone")) {
	/**
	 * Speichert einen abgeschlossenen Durchgang als Exam-Session, ohne dass zuvor eine
	 * Session (per EXAM-Start) existieren muss. Für Läufe ohne EXAM-Code.
	 *
	 * @param array<string, string> $ctx  quiz_key, setting, schulung, quiz_modus, name, drone, drive_location
	 * @param array<string, mixed>  $result  anz_fragen, fragen_richtig, punkte_*, erfuellt, name, drone, quiz_ergebnis_fragen
	 */
	function rkrk_assess_exam_save_completed_standalone(string $code, array $ctx, array $result): bool {
		$code = rkrk_assess_exam_normalize_code($code);
		if ($code === "") {
			return false;
		}
		$name = trim((string) ($ctx["name"] ?? ""));
		if ($name === "") {
			$name = trim((string) ($result["name"] ?? ""));
		}
		if ($name === "") {
			$name = "Unbenannt";
		}
		$ctx["name"] = $name;
		if (trim((string) ($result["name"] ?? "")) === "") {
			$result["name"] = $name;
		}
		$session = rkrk_assess_exam_load($code, $name);
		if (!is_array($session)) {
			$session = array(
				"exam_code" => $code,
				"quiz_key" => (string) ($ctx["quiz_key"] ?? ""),
				"setting" => (string) ($ctx["setting"] ?? ""),
				"schulung" => (string) ($ctx["schulung"] ?? ""),
				"quiz_modus" => (string) ($ctx["quiz_modus"] ?? ""),
				"drive_location" => (string) ($ctx["drive_location"] ?? ""),
				"rkrs_pilot_id" => (string) ($ctx["rkrs_pilot_id"] ?? ""),
				"auto_generated" => true,
				"attempts" => array(),
				"created_at" => gmdate("c"),
			);
			if (!rkrk_assess_exam_save($code, $session, $name)) {
				return false;
			}
		} elseif (trim((string) ($ctx["rkrs_pilot_id"] ?? "")) !== "") {
			$session["rkrs_pilot_id"] = trim((string) $ctx["rkrs_pilot_id"]);
			rkrk_assess_exam_save($code, $session, $name);
		}
		return rkrk_assess_exam_mark_completed($code, $result, $name);
	}
}

if (!function_exists("rkrk_assess_exam_is_completed")) {
	/**
	 * @param array<string, mixed>|null $session
	 */
	function rkrk_assess_exam_is_completed(?array $session, string $name = ""): bool {
		if (!is_array($session)) {
			return false;
		}
		if (trim($name) !== "") {
			$attempt = rkrk_assess_exam_get_attempt($session, $name);
			return is_array($attempt) && strtolower(trim((string) ($attempt["status"] ?? ""))) === "completed";
		}
		return strtolower(trim((string) ($session["status"] ?? ""))) === "completed";
	}
}

if (!function_exists("rkrk_assess_exam_in_progress_max_age_sec")) {
	/** Offene EXAM-Kataloge ohne Abschluss: nach 7 Tagen vom Drive entfernen. */
	function rkrk_assess_exam_in_progress_max_age_sec(): int {
		return 7 * 24 * 3600;
	}
}

if (!function_exists("rkrk_assess_exam_stem_is_dated_completion_of")) {
	function rkrk_assess_exam_stem_is_dated_completion_of(string $openStem, string $doneStem): bool {
		$openStem = trim($openStem);
		$doneStem = trim($doneStem);
		if ($openStem === "" || $doneStem === "" || $openStem === $doneStem) {
			return false;
		}
		if (strpos($doneStem, $openStem . "_") !== 0) {
			return false;
		}
		$rest = substr($doneStem, strlen($openStem) + 1);
		return (bool) preg_match('/^\d{8}(_\d+)?$/', $rest);
	}
}

if (!function_exists("rkrk_assess_exam_session_age_ts")) {
	/** @param array<string, mixed>|null $data */
	function rkrk_assess_exam_session_age_ts(string $file, ?array $data): int {
		if (is_array($data)) {
			foreach (array("updated_at", "created_at") as $col) {
				$raw = trim((string) ($data[$col] ?? ""));
				if ($raw === "") {
					continue;
				}
				$ts = strtotime($raw);
				if ($ts !== false) {
					return (int) $ts;
				}
			}
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			foreach ($attempts as $attempt) {
				if (!is_array($attempt)) {
					continue;
				}
				foreach (array("updated_at", "completed_at", "created_at") as $col) {
					$raw = trim((string) ($attempt[$col] ?? ""));
					if ($raw === "") {
						continue;
					}
					$ts = strtotime($raw);
					if ($ts !== false) {
						return (int) $ts;
					}
				}
			}
		}
		$mt = @filemtime($file);
		return $mt !== false ? (int) $mt : 0;
	}
}

if (!function_exists("rkrk_assess_exam_completed_index")) {
	/**
	 * @return array{quiz_pilot:array<string,bool>,stems:array<string,bool>}
	 */
	function rkrk_assess_exam_completed_index(): array {
		static $cache = null;
		if (is_array($cache)) {
			return $cache;
		}
		$quizPilot = array();
		$stems = array();
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			$cache = array("quiz_pilot" => $quizPilot, "stems" => $stems);
			return $cache;
		}
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$data = rkrk_assess_exam_read_json_file($file);
			if (!is_array($data)) {
				continue;
			}
			$quizKey = strtolower(trim((string) ($data["quiz_key"] ?? "")));
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			$hasCompleted = false;
			if ($attempts === array()) {
				if (strtolower(trim((string) ($data["status"] ?? ""))) === "completed") {
					$hasCompleted = true;
					$pilot = function_exists("rkrk_assess_exam_pilot_name_from_session")
						? rkrk_assess_exam_pilot_name_from_session($data)
						: trim((string) ($data["name"] ?? ""));
					$pk = rkrk_assess_exam_pilot_key($pilot);
					if ($pk !== "" && $quizKey !== "") {
						$quizPilot[$pk . "\n" . $quizKey] = true;
					}
				}
			} else {
				foreach ($attempts as $attempt) {
					if (!is_array($attempt)) {
						continue;
					}
					if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "completed") {
						continue;
					}
					$hasCompleted = true;
					$pk = rkrk_assess_exam_pilot_key(trim((string) ($attempt["name"] ?? "")));
					if ($pk !== "" && $quizKey !== "") {
						$quizPilot[$pk . "\n" . $quizKey] = true;
					}
				}
			}
			if ($hasCompleted) {
				$stems[pathinfo($file, PATHINFO_FILENAME)] = true;
			}
		}
		$cache = array("quiz_pilot" => $quizPilot, "stems" => $stems);
		return $cache;
	}
}

if (!function_exists("rkrk_assess_exam_in_progress_is_stale")) {
	/** @param array<string, mixed> $data */
	function rkrk_assess_exam_in_progress_is_stale(string $file, array $data, string $pilot = ""): bool {
		$index = rkrk_assess_exam_completed_index();
		$stem = pathinfo($file, PATHINFO_FILENAME);
		foreach ($index["stems"] as $doneStem => $_) {
			if (rkrk_assess_exam_stem_is_dated_completion_of($stem, (string) $doneStem)) {
				return true;
			}
		}
		$quizKey = strtolower(trim((string) ($data["quiz_key"] ?? "")));
		if ($quizKey !== "") {
			$pilots = array();
			if (trim($pilot) !== "") {
				$pilots[] = $pilot;
			} else {
				$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
				if ($attempts === array()) {
					$n = function_exists("rkrk_assess_exam_pilot_name_from_session")
						? rkrk_assess_exam_pilot_name_from_session($data)
						: trim((string) ($data["name"] ?? ""));
					if ($n !== "") {
						$pilots[] = $n;
					}
				} else {
					foreach ($attempts as $attempt) {
						if (!is_array($attempt)) {
							continue;
						}
						if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "in_progress") {
							continue;
						}
						$n = trim((string) ($attempt["name"] ?? ""));
						if ($n !== "") {
							$pilots[] = $n;
						}
					}
				}
			}
			foreach ($pilots as $n) {
				$pk = rkrk_assess_exam_pilot_key($n);
				if ($pk !== "" && !empty($index["quiz_pilot"][$pk . "\n" . $quizKey])) {
					return true;
				}
			}
		}
		$ts = rkrk_assess_exam_session_age_ts($file, $data);
		$cutoff = time() - rkrk_assess_exam_in_progress_max_age_sec();
		return $ts > 0 && $ts < $cutoff;
	}
}

if (!function_exists("rkrk_assess_exam_purge_stale_in_progress")) {
	function rkrk_assess_exam_purge_stale_in_progress(): void {
		static $ran = false;
		if ($ran) {
			return;
		}
		$ran = true;
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return;
		}
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$data = rkrk_assess_exam_read_json_file($file);
			if (!is_array($data)) {
				continue;
			}
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			$hasCompleted = false;
			$hasOpen = false;
			if ($attempts === array()) {
				$st = strtolower(trim((string) ($data["status"] ?? "")));
				$hasCompleted = ($st === "completed");
				$hasOpen = ($st === "in_progress");
			} else {
				foreach ($attempts as $attempt) {
					if (!is_array($attempt)) {
						continue;
					}
					$st = strtolower(trim((string) ($attempt["status"] ?? "")));
					if ($st === "completed") {
						$hasCompleted = true;
					} elseif ($st === "in_progress") {
						$hasOpen = true;
					}
				}
			}
			if (!$hasOpen || $hasCompleted) {
				continue;
			}
			if (rkrk_assess_exam_in_progress_is_stale($file, $data)) {
				@unlink($file);
			}
		}
	}
}

if (!function_exists("rkrk_assess_exam_list_in_progress")) {
	/**
	 * Offene EXAM-Versuche (status=in_progress) für Admin-Anzeige.
	 *
	 * @return list<array{
	 *   exam_code:string,quiz_key:string,pilot:string,drone:string,status:string,
	 *   created_at:string,updated_at:string,path:string,timestamp:int,schulung:string,quiz_modus:string
	 * }>
	 */
	function rkrk_assess_exam_list_in_progress(): array {
		rkrk_assess_exam_purge_stale_in_progress();
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return array();
		}
		$out = array();
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$data = rkrk_assess_exam_read_json_file($file);
			if (!is_array($data)) {
				continue;
			}
			$examCode = function_exists("rkrk_assess_exam_normalize_code")
				? rkrk_assess_exam_normalize_code($data["exam_code"] ?? "")
				: trim((string) ($data["exam_code"] ?? ""));
			$quizKey = trim((string) ($data["quiz_key"] ?? ""));
			$schulung = trim((string) ($data["schulung"] ?? ""));
			$quizModus = trim((string) ($data["quiz_modus"] ?? ""));
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			$rows = array();
			if ($attempts === array()) {
				if (strtolower(trim((string) ($data["status"] ?? ""))) === "in_progress") {
					$pilot = trim((string) ($data["name"] ?? ""));
					if ($pilot === "" && function_exists("rkrk_assess_exam_pilot_name_from_session")) {
						$pilot = rkrk_assess_exam_pilot_name_from_session($data);
					}
					$rows[] = array(
						"pilot" => $pilot,
						"drone" => trim((string) ($data["drone"] ?? "")),
						"created_at" => trim((string) ($data["created_at"] ?? "")),
						"updated_at" => trim((string) ($data["created_at"] ?? "")),
					);
				}
			} else {
				foreach ($attempts as $attempt) {
					if (!is_array($attempt)) {
						continue;
					}
					if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "in_progress") {
						continue;
					}
					$rows[] = array(
						"pilot" => trim((string) ($attempt["name"] ?? "")),
						"drone" => trim((string) ($attempt["drone"] ?? "")),
						"created_at" => trim((string) ($attempt["created_at"] ?? "")),
						"updated_at" => trim((string) ($attempt["updated_at"] ?? $attempt["created_at"] ?? "")),
					);
				}
			}
			foreach ($rows as $row) {
				$pilot = trim((string) ($row["pilot"] ?? ""));
				$created = trim((string) ($row["created_at"] ?? ""));
				$updated = trim((string) ($row["updated_at"] ?? $created));
				$ts = 0;
				foreach (array($updated, $created) as $rawTs) {
					if ($rawTs === "" || $ts > 0) {
						continue;
					}
					$parsed = strtotime($rawTs);
					if ($parsed !== false) {
						$ts = (int) $parsed;
					}
				}
				if ($ts <= 0) {
					$mt = @filemtime($file);
					$ts = $mt !== false ? (int) $mt : 0;
				}
				$out[] = array(
					"exam_code" => $examCode,
					"quiz_key" => $quizKey,
					"pilot" => $pilot !== "" ? $pilot : "(ohne Pilot)",
					"drone" => trim((string) ($row["drone"] ?? "")),
					"status" => "in_progress",
					"created_at" => $created,
					"updated_at" => $updated,
					"path" => $file,
					"timestamp" => $ts,
					"schulung" => $schulung,
					"quiz_modus" => $quizModus,
				);
			}
		}
		usort($out, function ($a, $b) {
			return ((int) ($b["timestamp"] ?? 0)) <=> ((int) ($a["timestamp"] ?? 0));
		});
		return $out;
	}
}

if (!function_exists("rkrk_assess_exam_delete_file")) {
	/**
	 * Löscht eine EXAM-Session-Datei unter results/exams/ und den zugehörigen Abschluss-Report.
	 *
	 * @param bool $onlyInProgress Wenn true, nur Dateien mit mindestens einem in_progress-Versuch.
	 */
	function rkrk_assess_exam_delete_file(string $basename, bool $onlyInProgress = false): bool {
		$basename = basename(trim($basename));
		if ($basename !== "" && !preg_match('/\.json$/i', $basename)) {
			$basename .= ".json";
		}
		if ($basename === "" || !preg_match('/^[A-Za-z0-9_.-]+\.json$/i', $basename)) {
			return false;
		}
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return false;
		}
		$baseReal = realpath(rtrim(str_replace("\\", "/", $dir), "/"));
		if ($baseReal === false || !is_dir($baseReal)) {
			return false;
		}
		$candidate = $baseReal . DIRECTORY_SEPARATOR . $basename;
		$rp = realpath($candidate);
		if ($rp === false || !is_file($rp)) {
			return false;
		}
		if (strpos($rp, $baseReal) !== 0) {
			return false;
		}
		if ($onlyInProgress) {
			$data = rkrk_assess_exam_read_json_file($rp);
			$hasInProgress = false;
			if (is_array($data)) {
				$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
				if ($attempts === array()) {
					$hasInProgress = strtolower(trim((string) ($data["status"] ?? ""))) === "in_progress";
				} else {
					foreach ($attempts as $attempt) {
						if (is_array($attempt) && strtolower(trim((string) ($attempt["status"] ?? ""))) === "in_progress") {
							$hasInProgress = true;
							break;
						}
					}
				}
			}
			if (!$hasInProgress) {
				return false;
			}
		}
		$stem = pathinfo($rp, PATHINFO_FILENAME);
		$ok = @unlink($rp);
		if ($ok && $stem !== "" && function_exists("rkrk_assess_report_delete_by_stem")) {
			rkrk_assess_report_delete_by_stem($stem, "assess");
		}
		return $ok;
	}
}

if (!function_exists("rkrk_assess_exam_delete_in_progress_file")) {
	/** Sicheres Löschen einer EXAM-Session-Datei unter results/exams/ (nur wenn in_progress). */
	function rkrk_assess_exam_delete_in_progress_file(string $basename): bool {
		if (function_exists("rkrk_assess_exam_delete_file")) {
			return rkrk_assess_exam_delete_file($basename, true);
		}
		return false;
	}
}

if (!function_exists("rkrk_assess_exam_apply_completed_globals")) {
	/**
	 * Setzt Anzeige-Variablen für abgeschlossene EXAM-Sitzung.
	 *
	 * @param array<string, mixed> $session
	 * @return array{quiz_ergebnis_fragen: ?array, anz_fragen: string, fragen_richtig: string, punkte_erreicht: string, punkte_max: string, erfuellt: string, name: string, drone: string}
	 */
	function rkrk_assess_exam_apply_completed_globals(array $session, string $name = ""): array {
		$result = array();
		$displayName = trim($name);
		$displayDrone = "";
		if (trim($name) !== "") {
			$attempt = rkrk_assess_exam_get_attempt($session, $name);
			if (is_array($attempt)) {
				$result = isset($attempt["result"]) && is_array($attempt["result"]) ? $attempt["result"] : array();
				$displayName = (string) ($attempt["name"] ?? $name);
				$displayDrone = (string) ($attempt["drone"] ?? "");
			}
		}
		if ($result === array()) {
			$result = isset($session["result"]) && is_array($session["result"]) ? $session["result"] : array();
			if ($displayName === "") {
				$displayName = (string) ($session["name"] ?? "");
			}
			if ($displayDrone === "") {
				$displayDrone = (string) ($session["drone"] ?? "");
			}
		}
		$out = array(
			"quiz_ergebnis_fragen" => isset($result["quiz_ergebnis_fragen"]) && is_array($result["quiz_ergebnis_fragen"])
				? $result["quiz_ergebnis_fragen"]
				: null,
			"anz_fragen" => (string) ($result["anz_fragen"] ?? ""),
			"fragen_richtig" => (string) ($result["fragen_richtig"] ?? ""),
			"punkte_erreicht" => (string) ($result["punkte_erreicht"] ?? ""),
			"punkte_max" => (string) ($result["punkte_max"] ?? ""),
			"erfuellt" => (string) ($result["erfuellt"] ?? ""),
			"name" => (string) ($result["name"] ?? $displayName),
			"drone" => (string) ($result["drone"] ?? $displayDrone),
		);
		return $out;
	}
}

if (!function_exists("rkrk_assess_exam_list_completed")) {
	/**
	 * Abgeschlossene EXAM-Versuche unter results/exams/ (für Admin/Reports).
	 *
	 * @return list<array{
	 *   exam_code:string,quiz_key:string,name:string,drone:string,status:string,
	 *   path:string,stem:string,timestamp:int,completed_at:string,quiz_modus:string,
	 *   schulung:string,result:array,anz:int,richtig:int,pct:float,passed:bool,
	 *   factor:float,zeit:string,bestanden:string
	 * }>
	 */
	function rkrk_assess_exam_list_completed(): array {
		$dir = rkrk_assess_exam_storage_dir();
		if ($dir === "") {
			return array();
		}
		$out = array();
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$data = rkrk_assess_exam_read_json_file($file);
			if (!is_array($data)) {
				continue;
			}
			$examCode = function_exists("rkrk_assess_exam_normalize_code")
				? rkrk_assess_exam_normalize_code($data["exam_code"] ?? "")
				: trim((string) ($data["exam_code"] ?? ""));
			$quizKey = trim((string) ($data["quiz_key"] ?? ""));
			$quizModus = trim((string) ($data["quiz_modus"] ?? ""));
			$schulung = trim((string) ($data["schulung"] ?? ""));
			$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
			$items = array();
			if ($attempts === array()) {
				if (strtolower(trim((string) ($data["status"] ?? ""))) === "completed"
					&& isset($data["result"]) && is_array($data["result"])
				) {
					$items[] = array(
						"name" => trim((string) ($data["name"] ?? "")),
						"drone" => trim((string) ($data["drone"] ?? "")),
						"status" => "completed",
						"completed_at" => trim((string) ($data["completed_at"] ?? $data["updated_at"] ?? "")),
						"result" => $data["result"],
					);
				}
			} else {
				foreach ($attempts as $attempt) {
					if (is_array($attempt)) {
						$items[] = $attempt;
					}
				}
			}
			$stem = pathinfo($file, PATHINFO_FILENAME);
			foreach ($items as $attempt) {
				if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "completed") {
					continue;
				}
				$result = isset($attempt["result"]) && is_array($attempt["result"]) ? $attempt["result"] : array();
				if ($result === array()) {
					continue;
				}
				$name = trim((string) ($attempt["name"] ?? ($result["name"] ?? "")));
				$drone = trim((string) ($attempt["drone"] ?? ($result["drone"] ?? "")));
				$completedAt = trim((string) ($attempt["completed_at"] ?? ""));
				$zeit = trim((string) ($result["zeit"] ?? ""));
				$metricsSrc = array_merge($result, array(
					"quiz_key" => $quizKey,
					"quiz" => $quizKey,
				));
				$m = function_exists("rkrk_assess_theory_result_metrics")
					? rkrk_assess_theory_result_metrics($metricsSrc)
					: null;
				$anz = is_array($m) ? (int) $m["anz"] : (int) ($result["anz_fragen"] ?? 0);
				$richtig = is_array($m) ? (int) $m["richtig"] : (int) ($result["fragen_richtig"] ?? 0);
				$pe = is_array($m) ? (float) $m["pe"] : (is_numeric((string) ($result["punkte_erreicht"] ?? "")) ? (float) $result["punkte_erreicht"] : (float) $richtig);
				$pm = is_array($m) ? (float) $m["pm"] : (is_numeric((string) ($result["punkte_max"] ?? "")) ? (float) $result["punkte_max"] : (float) $anz);
				$factor = is_array($m) ? (float) $m["factor"] : (is_numeric((string) ($result["erfuellt"] ?? "")) ? (float) $result["erfuellt"] : 0.0);
				$passed = is_array($m) ? !empty($m["passed"]) : false;
				$pct = is_array($m) ? (float) $m["pct"] : (($pm > 0) ? round(($pe / $pm) * 100, 1) : 0.0);
				$punkteModus = is_array($m) ? (string) ($m["punkte_modus"] ?? "standard") : "standard";
				$bestanden = $passed ? "Ja" : "Nein";
				$ts = 0;
				foreach (array($completedAt, $zeit) as $rawTs) {
					if ($rawTs === "" || $ts > 0) {
						continue;
					}
					$parsed = strtotime(str_replace("T", " ", $rawTs));
					if ($parsed !== false) {
						$ts = (int) $parsed;
					}
				}
				if ($ts <= 0) {
					$mt = @filemtime($file);
					$ts = $mt !== false ? (int) $mt : 0;
				}
				if ($zeit === "" && $ts > 0) {
					$zeit = date("d.m.Y H:i", $ts);
				}
				$out[] = array(
					"exam_code" => $examCode,
					"quiz_key" => $quizKey,
					"name" => $name !== "" ? $name : "(ohne Pilot)",
					"drone" => $drone,
					"status" => "completed",
					"path" => $file,
					"stem" => $stem,
					"timestamp" => $ts,
					"completed_at" => $completedAt,
					"quiz_modus" => $quizModus,
					"schulung" => $schulung,
					"result" => $result,
					"anz" => $anz,
					"richtig" => $richtig,
					"punkte_erreicht" => $pe,
					"punkte_max" => $pm,
					"pct" => $pct,
					"passed" => $passed,
					"factor" => $factor,
					"punkte_modus" => $punkteModus,
					"zeit" => $zeit,
					"bestanden" => $bestanden,
				);
			}
		}
		usort($out, function ($a, $b) {
			return ((int) ($b["timestamp"] ?? 0)) <=> ((int) ($a["timestamp"] ?? 0));
		});
		return $out;
	}
}

