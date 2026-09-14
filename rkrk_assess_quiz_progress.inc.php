<?php
/**
 * Quiz-/Schulungs-Fortschritt (Autosave, Wiederaufnehmen nach Verbindungsabbruch).
 */
include_once __DIR__ . "/rkrk_assess_result_stem.inc.php";

if (!function_exists("rkrk_assess_quiz_progress_dir")) {
	function rkrk_assess_quiz_progress_dir(): string {
		$base = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/";
		} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/results/";
		}
		if ($base === "") {
			return "";
		}
		$dir = $base . "exams_progress/";
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		if (!is_dir($dir)) {
			foreach (array($base . "progress/", $base . "exams_porgress/") as $legacy) {
				if (is_dir($legacy)) {
					return $legacy;
				}
			}
			return "";
		}
		return $dir;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_normalize_identity")) {
	/**
	 * @return array{quiz_key:string, name:string, drone:string, setting:string, schulung:string, quiz_modus:string, exam_code:string, rkrs_pilot_id:string}
	 */
	function rkrk_assess_quiz_progress_normalize_identity(array $in): array {
		$exam = "";
		if (function_exists("rkrk_assess_exam_normalize_code")) {
			$exam = rkrk_assess_exam_normalize_code($in["exam_code"] ?? ($in["EXAM"] ?? ""));
		}
		$rkrs = trim((string) ($in["rkrs_pilot_id"] ?? ($in["RKRSPilotID"] ?? ($in["PilotID"] ?? ""))));
		$name = trim((string) ($in["name"] ?? ""));
		if ($rkrs === "" && $name !== "" && function_exists("rkrk_assess_personel_rkrs_id_by_name")) {
			$rkrs = rkrk_assess_personel_rkrs_id_by_name($name);
		}
		return array(
			"quiz_key" => trim((string) ($in["quiz_key"] ?? "")),
			"name" => $name,
			"drone" => trim((string) ($in["drone"] ?? "")),
			"setting" => trim((string) ($in["setting"] ?? "")),
			"schulung" => trim((string) ($in["schulung"] ?? "")),
			"quiz_modus" => function_exists("rkrk_assess_quiz_modus_storage_token")
				? rkrk_assess_quiz_modus_storage_token((string) ($in["quiz_modus"] ?? ""))
				: trim((string) ($in["quiz_modus"] ?? "")),
			"exam_code" => $exam,
			"rkrs_pilot_id" => $rkrs,
		);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_storage_key")) {
	function rkrk_assess_quiz_progress_storage_key(array $identity): string {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		$payload = implode("\x1e", array(
			$id["quiz_key"],
			mb_strtolower($id["name"], "UTF-8"),
			mb_strtolower($id["drone"], "UTF-8"),
			$id["setting"],
			$id["schulung"],
			$id["quiz_modus"],
			mb_strtolower($id["exam_code"], "UTF-8"),
			$id["rkrs_pilot_id"],
		));
		return hash("sha256", $payload);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_readable_basename")) {
	/**
	 * Gleicher Basename wie Exam-JSON: {examcode|quiz}_{pilot_slug}.json
	 */
	function rkrk_assess_quiz_progress_readable_basename(array $identity): string {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		$code = $id["exam_code"] !== "" ? $id["exam_code"] : $id["quiz_key"];
		$code = trim((string) $code);
		if ($code === "") {
			return "";
		}
		$title = $id["quiz_key"] !== "" ? $id["quiz_key"] : $code;
		if (function_exists("rkrk_assess_quiz_title_from_key") && $id["quiz_key"] !== "") {
			$title = rkrk_assess_quiz_title_from_key($id["quiz_key"]);
		}
		$stem = "";
		if (function_exists("rkrk_assess_build_quiz_result_stem")) {
			$stem = rkrk_assess_build_quiz_result_stem(
				$title,
				$id["name"],
				$id["rkrs_pilot_id"],
				$id["quiz_modus"],
				"",
				false
			);
		} else {
			$stem = rkrk_assess_build_progress_stem($title, $id["name"], "", $id["rkrs_pilot_id"]);
		}
		if ($stem === "") {
			return "";
		}
		return $stem . ".json";
	}
}

if (!function_exists("rkrk_assess_quiz_progress_legacy_dirs")) {
	/** @return list<string> */
	function rkrk_assess_quiz_progress_legacy_dirs(): array {
		$dirs = array();
		$bases = array();
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$bases[] = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/");
		}
		if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$ql = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/");
			$bases[] = $ql;
			$bases[] = $ql . "/results";
		}
		$seen = array();
		foreach ($bases as $base) {
			foreach (array("progress", "exams_porgress", "exams_progress") as $name) {
				$path = $base . "/" . $name;
				if (isset($seen[$path]) || !is_dir($path)) {
					continue;
				}
				$seen[$path] = true;
				$dirs[] = $path;
			}
		}
		$primary = rtrim(str_replace("\\", "/", rkrk_assess_quiz_progress_dir()), "/");
		return array_values(array_filter($dirs, static function ($d) use ($primary) {
			return $d !== $primary;
		}));
	}
}

if (!function_exists("rkrk_assess_quiz_progress_legacy_hash_path")) {
	function rkrk_assess_quiz_progress_legacy_hash_path(array $identity): string {
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return "";
		}
		return $dir . rkrk_assess_quiz_progress_storage_key($identity) . ".json";
	}
}

if (!function_exists("rkrk_assess_quiz_progress_file_path")) {
	function rkrk_assess_quiz_progress_file_path(array $identity): string {
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return "";
		}
		$base = rkrk_assess_quiz_progress_readable_basename($identity);
		if ($base === "") {
			return "";
		}
		return $dir . $base;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_fingerprint_from_list")) {
	/**
	 * @param list<array<string, mixed>> $questions
	 */
	function rkrk_assess_quiz_progress_fingerprint_from_list(array $questions): string {
		$parts = array();
		foreach ($questions as $i => $q) {
			if (!is_array($q)) {
				continue;
			}
			$qid = isset($q["QID"]) ? trim((string) $q["QID"]) : (string) $i;
			if ($qid === "") {
				$qid = (string) $i;
			}
			$parts[] = $qid;
		}
		return md5(implode("|", $parts));
	}
}

if (!function_exists("rkrk_assess_quiz_progress_snapshot_fields")) {
	/** @return list<string> */
	function rkrk_assess_quiz_progress_snapshot_fields(): array {
		return array(
			"qid",
			"Frage",
			"Loesungsauswahl",
			"Loesung",
			"Loesungstext",
			"AntwortText",
			"Frage-Bild",
			"Loesungs-Bild",
			"Bildformat",
			"Zeit",
		);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_normalize_snapshots")) {
	/**
	 * @param mixed $snaps
	 * @return list<array<string, mixed>>
	 */
	function rkrk_assess_quiz_progress_normalize_snapshots($snaps): array {
		if (!is_array($snaps)) {
			return array();
		}
		$fields = rkrk_assess_quiz_progress_snapshot_fields();
		$out = array();
		foreach ($snaps as $snap) {
			if (!is_array($snap)) {
				continue;
			}
			$row = array();
			foreach ($fields as $f) {
				if ($f === "Loesungsauswahl") {
					$opts = $snap[$f] ?? array();
					if (!is_array($opts)) {
						$rawOpts = (string) $opts;
						if (function_exists("rkrk_assess_fix_mojibake")) {
							$rawOpts = rkrk_assess_fix_mojibake($rawOpts);
						}
						$opts = $rawOpts !== "" ? array_map("trim", explode("|", $rawOpts)) : array();
					}
					$cleanOpts = array();
					foreach ($opts as $opt) {
						$o = (string) $opt;
						if (function_exists("rkrk_assess_fix_mojibake")) {
							$o = rkrk_assess_fix_mojibake($o);
						}
						$cleanOpts[] = $o;
					}
					$row[$f] = array_values($cleanOpts);
				} else {
					$cell = (string) ($snap[$f] ?? "");
					if (function_exists("rkrk_assess_fix_mojibake")) {
						$cell = rkrk_assess_fix_mojibake($cell);
					}
					$row[$f] = $cell;
				}
			}
			if (trim($row["qid"]) === "") {
				continue;
			}
			$out[] = $row;
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_normalize_question_order")) {
	/**
	 * @param mixed $order
	 * @return list<string>
	 */
	function rkrk_assess_quiz_progress_normalize_question_order($order): array {
		if (!is_array($order)) {
			return array();
		}
		$out = array();
		foreach ($order as $qid) {
			$s = trim((string) $qid);
			if ($s !== "") {
				$out[] = $s;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_quiz_questions_from_progress_snapshots")) {
	/**
	 * Quiz-Fragen exakt wie beim Speichern (Reihenfolge + gemischte Antwortoptionen).
	 *
	 * @return list<array<string, mixed>>|null
	 */
	function rkrk_assess_quiz_questions_from_progress_snapshots(array $progress): ?array {
		$snaps = rkrk_assess_quiz_progress_normalize_snapshots($progress["question_snapshots"] ?? array());
		if (count($snaps) < 1) {
			return null;
		}
		$byQid = array();
		foreach ($snaps as $snap) {
			$byQid[trim((string) $snap["qid"])] = $snap;
		}
		$order = rkrk_assess_quiz_progress_normalize_question_order($progress["question_order"] ?? array());
		if (count($order) < 1) {
			$order = array_keys($byQid);
		}
		$ordered = array();
		foreach ($order as $qid) {
			if (!isset($byQid[$qid])) {
				continue;
			}
			$snap = $byQid[$qid];
			$ordered[] = array(
				"QID" => $qid,
				"Frage" => (string) ($snap["Frage"] ?? ""),
				"Loesungsauswahl" => is_array($snap["Loesungsauswahl"] ?? null) ? $snap["Loesungsauswahl"] : array(),
				"Loesung" => (string) ($snap["Loesung"] ?? ""),
				"Loesungstext" => (string) ($snap["Loesungstext"] ?? ""),
				"AntwortText" => (string) ($snap["AntwortText"] ?? ""),
				"Frage-Bild" => (string) ($snap["Frage-Bild"] ?? ""),
				"Loesungs-Bild" => (string) ($snap["Loesungs-Bild"] ?? ""),
				"Bildformat" => (string) ($snap["Bildformat"] ?? ""),
				"Zeit" => (string) ($snap["Zeit"] ?? ""),
				"Antwort" => "",
				"Locked" => "Nein",
			);
		}
		if (count($ordered) < 1) {
			return null;
		}
		$maxQ = max(0, (int) ($progress["max_questions"] ?? 0));
		if ($maxQ > 0 && count($ordered) > $maxQ) {
			$ordered = array_slice($ordered, 0, $maxQ);
		}
		if (function_exists("rkrk_assess_strip_question_image_paths")) {
			$driveLoc = isset($progress["drive_location"]) ? (string) $progress["drive_location"] : "";
			rkrk_assess_strip_question_image_paths($ordered, $driveLoc);
		}
		return array_values($ordered);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_read_resume_cookie")) {
	/**
	 * @return array<string, string>|null
	 */
	function rkrk_assess_quiz_progress_read_resume_cookie(): ?array {
		if (!isset($_COOKIE["rkrk_dga_progress_identity"])) {
			return null;
		}
		$raw = trim((string) $_COOKIE["rkrk_dga_progress_identity"]);
		if ($raw === "") {
			return null;
		}
		$decoded = json_decode(rawurldecode($raw), true);
		if (!is_array($decoded)) {
			$decoded = json_decode($raw, true);
		}
		return is_array($decoded) ? $decoded : null;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_drone_for_match")) {
	function rkrk_assess_quiz_progress_drone_for_match(string $drone): string {
		$d = function_exists("rkrk_assess_normalize_drone")
			? rkrk_assess_normalize_drone($drone)
			: trim(str_replace("\xC2\xA0", " ", $drone));
		if ($d === "" || strcasecmp($d, "Alle") === 0) {
			return "";
		}
		return mb_strtolower($d, "UTF-8");
	}
}

if (!function_exists("rkrk_assess_quiz_progress_quiz_keys_equal")) {
	function rkrk_assess_quiz_progress_quiz_keys_equal(string $a, string $b): bool {
		$a = trim($a);
		$b = trim($b);
		if ($a === "" || $b === "") {
			return false;
		}
		return $a === $b || strcasecmp($a, $b) === 0;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_names_equal")) {
	function rkrk_assess_quiz_progress_names_equal(string $a, string $b): bool {
		if (function_exists("rkrk_assess_personel_names_equal")) {
			return rkrk_assess_personel_names_equal($a, $b);
		}
		return mb_strtolower(trim($a), "UTF-8") === mb_strtolower(trim($b), "UTF-8");
	}
}

if (!function_exists("rkrk_assess_quiz_progress_identity_matches_saved")) {
	/**
	 * Fortschritt nur nutzen, wenn Quiz-Key + Name stimmen und Drohne (falls relevant) übereinstimmt.
	 *
	 * @param array<string, string> $identity
	 * @param array<string, mixed> $progress
	 */
	function rkrk_assess_quiz_progress_identity_matches_saved(array $identity, array $progress, bool $droneRequired = false): bool {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return false;
		}
		$savedKey = trim((string) ($progress["quiz_key"] ?? ""));
		$savedName = trim((string) ($progress["name"] ?? ""));
		if (!rkrk_assess_quiz_progress_quiz_keys_equal($id["quiz_key"], $savedKey)) {
			return false;
		}
		if (!rkrk_assess_quiz_progress_names_equal($id["name"], $savedName)) {
			return false;
		}
		$idDrone = rkrk_assess_quiz_progress_drone_for_match($id["drone"]);
		$savedDrone = rkrk_assess_quiz_progress_drone_for_match((string) ($progress["drone"] ?? ""));
		$needDrone = $droneRequired || $idDrone !== "" || $savedDrone !== "";
		if ($needDrone) {
			if ($idDrone === "" || $savedDrone === "" || $idDrone !== $savedDrone) {
				return false;
			}
		}
		return true;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_try_load_incomplete")) {
	function rkrk_assess_quiz_progress_try_load_incomplete(array $identity, bool $droneRequired = false): ?array {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return null;
		}
		$progress = rkrk_assess_quiz_progress_load($id);
		if (!is_array($progress)) {
			return null;
		}
		if (!rkrk_assess_quiz_progress_identity_matches_saved($id, $progress, $droneRequired)) {
			return null;
		}
		$maxQ = max(0, (int) ($progress["max_questions"] ?? 0));
		if (!rkrk_assess_quiz_progress_is_incomplete($progress, $maxQ)) {
			return null;
		}
		return $progress;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_count_answered")) {
	function rkrk_assess_quiz_progress_count_answered(array $progress): int {
		$n = 0;
		$rows = isset($progress["questions"]) && is_array($progress["questions"]) ? $progress["questions"] : array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$locked = strtolower(trim((string) ($row["locked"] ?? "")));
			$ant = trim((string) ($row["antwort"] ?? ""));
			if ($locked === "ja" && $ant !== "") {
				$n++;
			}
		}
		return $n;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_is_incomplete")) {
	function rkrk_assess_quiz_progress_is_incomplete(array $progress, int $maxQuestions = 0): bool {
		$status = strtolower(trim((string) ($progress["status"] ?? "")));
		if ($status === "resultat") {
			return false;
		}
		$answered = rkrk_assess_quiz_progress_count_answered($progress);
		if ($answered > 0) {
			return true;
		}
		if ($status === "quiz" || $status === "fertig") {
			return true;
		}
		if (!empty($progress["started"])) {
			return true;
		}
		if ($maxQuestions > 0 && $answered > 0 && $answered < $maxQuestions) {
			return true;
		}
		return false;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_max_age_sec")) {
	/** Aufbewahrung für Fortschritt in results/exams_progress/ */
	function rkrk_assess_quiz_progress_max_age_sec(): int {
		return 86400;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_file_age_ts")) {
	/**
	 * Alter eines Fortschritts-Eintrags: updated_at / started_at aus JSON, sonst Datei-mtime.
	 */
	function rkrk_assess_quiz_progress_file_age_ts(string $file, ?array $data = null): int {
		if ($data === null) {
			$raw = @file_get_contents($file);
			if (is_string($raw) && trim($raw) !== "") {
				$decoded = json_decode($raw, true);
				$data = is_array($decoded) ? $decoded : null;
			}
		}
		if (is_array($data)) {
			foreach (array("updated_at", "started_at") as $col) {
				if (!isset($data[$col]) || trim((string) $data[$col]) === "") {
					continue;
				}
				$ts = strtotime((string) $data[$col]);
				if ($ts !== false) {
					return (int) $ts;
				}
			}
		}
		$mtime = @filemtime($file);
		return $mtime !== false ? (int) $mtime : time();
	}
}

if (!function_exists("rkrk_assess_quiz_progress_purge_old")) {
	/**
	 * Löscht *.json in results/exams_progress/, die älter als $maxAgeSec sind (Standard: 24 h).
	 */
	function rkrk_assess_quiz_progress_purge_old(?int $maxAgeSec = null): void {
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return;
		}
		if ($maxAgeSec === null) {
			$maxAgeSec = rkrk_assess_quiz_progress_max_age_sec();
		}
		$maxAgeSec = max(60, (int) $maxAgeSec);
		$cutoff = time() - $maxAgeSec;
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$raw = @file_get_contents($file);
			$data = null;
			if (is_string($raw) && trim($raw) !== "") {
				$decoded = json_decode($raw, true);
				$data = is_array($decoded) ? $decoded : null;
			}
			if (rkrk_assess_quiz_progress_file_age_ts($file, $data) < $cutoff) {
				@unlink($file);
			}
		}
	}
}

if (!function_exists("rkrk_assess_quiz_progress_purge_old_maybe")) {
	/**
	 * Hintergrund-Bereinigung (max. einmal pro Stunde), z. B. beim Seitenaufruf.
	 */
	function rkrk_assess_quiz_progress_purge_old_maybe(): void {
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return;
		}
		$stamp = $dir . ".purge_stamp";
		$now = time();
		$last = 0;
		if (is_file($stamp)) {
			$last = (int) trim((string) @file_get_contents($stamp));
		}
		if ($last > 0 && ($now - $last) < 3600) {
			return;
		}
		rkrk_assess_quiz_progress_purge_old();
		@file_put_contents($stamp, (string) $now, LOCK_EX);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_load")) {
	function rkrk_assess_quiz_progress_load(array $identity): ?array {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return null;
		}
		$paths = array();
		$primary = rkrk_assess_quiz_progress_file_path($id);
		if ($primary !== "") {
			$paths[] = $primary;
		}
		$bn = rkrk_assess_quiz_progress_readable_basename($id);
		$dir = rkrk_assess_quiz_progress_dir();
		$code = $id["exam_code"] !== "" ? $id["exam_code"] : $id["quiz_key"];
		$nameSlug = function_exists("rkrk_assess_slugify_file_stem")
			? rkrk_assess_slugify_file_stem($id["name"])
			: strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', "_", $id["name"]));
		if ($dir !== "" && $code !== "" && $nameSlug !== "") {
			$paths[] = $dir . strtolower($code) . "_" . $nameSlug . ".json";
			$titleSlug = function_exists("rkrk_assess_result_title_slug")
				? rkrk_assess_result_title_slug($id["quiz_key"] !== "" ? $id["quiz_key"] : $code)
				: strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', "_", $id["quiz_key"] !== "" ? $id["quiz_key"] : $code));
			if ($titleSlug !== "") {
				$paths[] = $dir . $titleSlug . "_" . $nameSlug . ".json";
			}
		}
		foreach (rkrk_assess_quiz_progress_legacy_dirs() as $legacyDir) {
			$legacyDir = rtrim(str_replace("\\", "/", $legacyDir), "/") . "/";
			if ($bn !== "") {
				$paths[] = $legacyDir . $bn;
			}
			$paths[] = $legacyDir . rkrk_assess_quiz_progress_storage_key($id) . ".json";
		}
		$legacy = rkrk_assess_quiz_progress_legacy_hash_path($id);
		if ($legacy !== "" && $legacy !== $primary) {
			$paths[] = $legacy;
		}
		$paths = array_values(array_unique(array_filter($paths)));
		$cutoff = time() - rkrk_assess_quiz_progress_max_age_sec();
		foreach ($paths as $path) {
			if ($path === "" || !is_readable($path)) {
				continue;
			}
			$raw = @file_get_contents($path);
			if ($raw === false || trim($raw) === "") {
				continue;
			}
			$data = json_decode($raw, true);
			if (!is_array($data)) {
				continue;
			}
			if (rkrk_assess_quiz_progress_file_age_ts($path, $data) < $cutoff) {
				@unlink($path);
				continue;
			}
			return $data;
		}
		return null;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_delete")) {
	function rkrk_assess_quiz_progress_delete(array $identity): bool {
		$ok = true;
		$primary = rkrk_assess_quiz_progress_file_path($identity);
		if ($primary !== "" && is_file($primary)) {
			$ok = @unlink($primary) && $ok;
		}
		$legacy = rkrk_assess_quiz_progress_legacy_hash_path($identity);
		if ($legacy !== "" && $legacy !== $primary && is_file($legacy)) {
			$ok = @unlink($legacy) && $ok;
		}
		return $ok;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_delete_best_effort")) {
	/**
	 * Löscht gespeicherten Fortschritt auch wenn setting in der Identität abweicht.
	 */
	function rkrk_assess_quiz_progress_delete_best_effort(array $identity): bool {
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return true;
		}
		rkrk_assess_quiz_progress_delete($id);
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return true;
		}
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$raw = @file_get_contents($file);
			if ($raw === false || trim($raw) === "") {
				continue;
			}
			$data = json_decode($raw, true);
			if (!is_array($data)) {
				continue;
			}
			$match = rkrk_assess_quiz_progress_normalize_identity(array(
				"quiz_key" => (string) ($data["quiz_key"] ?? ""),
				"name" => (string) ($data["name"] ?? ""),
				"drone" => (string) ($data["drone"] ?? ""),
				"setting" => (string) ($data["setting"] ?? ""),
				"schulung" => (string) ($data["schulung"] ?? ""),
				"quiz_modus" => (string) ($data["quiz_modus"] ?? ""),
				"exam_code" => (string) ($data["exam_code"] ?? ""),
			));
			if (
				$match["quiz_key"] === $id["quiz_key"]
				&& mb_strtolower($match["name"], "UTF-8") === mb_strtolower($id["name"], "UTF-8")
				&& mb_strtolower($match["drone"], "UTF-8") === mb_strtolower($id["drone"], "UTF-8")
				&& $match["schulung"] === $id["schulung"]
				&& $match["quiz_modus"] === $id["quiz_modus"]
			) {
				@unlink($file);
			}
		}
		return true;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_list_incomplete")) {
	/**
	 * Offene Theorie-/Quiz-Fortschritte für Admin-Anzeige.
	 *
	 * @return list<array{
	 *   quiz_key:string,name:string,drone:string,exam_code:string,status:string,
	 *   quiz_modus:string,schulung:string,updated_at:string,started_at:string,
	 *   path:string,timestamp:int,at_question:int,max_questions:int,answered:int
	 * }>
	 */
	function rkrk_assess_quiz_progress_list_incomplete(bool $forAdmin = false): array {
		if (!$forAdmin) {
			rkrk_assess_quiz_progress_purge_old_maybe();
		}
		$dir = rkrk_assess_quiz_progress_dir();
		if ($dir === "") {
			return array();
		}
		$out = array();
		$cutoff = time() - rkrk_assess_quiz_progress_max_age_sec();
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$raw = @file_get_contents($file);
			if ($raw === false || trim($raw) === "") {
				continue;
			}
			$data = json_decode($raw, true);
			if (!is_array($data)) {
				continue;
			}
			$ageTs = rkrk_assess_quiz_progress_file_age_ts($file, $data);
			if (!$forAdmin && $ageTs > 0 && $ageTs < $cutoff) {
				@unlink($file);
				continue;
			}
			if (!rkrk_assess_quiz_progress_is_incomplete($data, (int) ($data["max_questions"] ?? 0))) {
				continue;
			}
			$status = strtolower(trim((string) ($data["status"] ?? "")));
			if ($status === "fertig" || $status === "resultat" || $status === "completed") {
				continue;
			}
			$name = trim((string) ($data["name"] ?? ""));
			$quizKey = trim((string) ($data["quiz_key"] ?? ""));
			if ($name === "" || $quizKey === "") {
				continue;
			}
			$updatedAt = trim((string) ($data["updated_at"] ?? ""));
			$startedAt = trim((string) ($data["started_at"] ?? ""));
			$ts = 0;
			if ($updatedAt !== "") {
				$parsed = strtotime($updatedAt);
				if ($parsed !== false) {
					$ts = (int) $parsed;
				}
			}
			if ($ts <= 0) {
				$ts = $ageTs;
			}
			$out[] = array(
				"quiz_key" => $quizKey,
				"name" => $name,
				"drone" => trim((string) ($data["drone"] ?? "")),
				"exam_code" => trim((string) ($data["exam_code"] ?? "")),
				"status" => trim((string) ($data["status"] ?? "Quiz")),
				"quiz_modus" => trim((string) ($data["quiz_modus"] ?? "")),
				"schulung" => trim((string) ($data["schulung"] ?? "")),
				"updated_at" => $updatedAt,
				"started_at" => $startedAt,
				"path" => $file,
				"timestamp" => $ts,
				"at_question" => max(0, (int) ($data["at_question"] ?? 0)),
				"max_questions" => max(0, (int) ($data["max_questions"] ?? 0)),
				"answered" => rkrk_assess_quiz_progress_count_answered($data),
			);
		}
		usort($out, function ($a, $b) {
			return ((int) ($b["timestamp"] ?? 0)) <=> ((int) ($a["timestamp"] ?? 0));
		});
		return $out;
	}
}

if (!function_exists("rkrk_assess_quiz_progress_delete_by_path")) {
	function rkrk_assess_quiz_progress_delete_by_path(string $basename): bool {
		$basename = basename(trim($basename));
		if ($basename === "" || !preg_match('/^[A-Za-z0-9_.-]+\.json$/i', $basename)) {
			return false;
		}
		$dir = rkrk_assess_quiz_progress_dir();
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
		return @unlink($rp);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_save")) {
	/**
	 * @param array<string, mixed> $payload
	 */
	function rkrk_assess_quiz_progress_save(array $payload): array {
		$id = rkrk_assess_quiz_progress_normalize_identity($payload);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return array("ok" => false, "error" => "quiz_key und name erforderlich.");
		}
		$path = rkrk_assess_quiz_progress_file_path($id);
		if ($path === "") {
			return array("ok" => false, "error" => "Fortschritts-Ordner nicht verfügbar.");
		}
		$now = date("c");
		$existing = rkrk_assess_quiz_progress_load($id);
		$startedAt = is_array($existing) && isset($existing["started_at"]) ? (string) $existing["started_at"] : $now;
		$questions = isset($payload["questions"]) && is_array($payload["questions"]) ? $payload["questions"] : array();
		$cleanQ = array();
		foreach ($questions as $row) {
			if (!is_array($row)) {
				continue;
			}
			$lockedRaw = trim((string) ($row["locked"] ?? "Nein"));
			$cleanQ[] = array(
				"qid" => trim((string) ($row["qid"] ?? "")),
				"idx" => max(0, (int) ($row["idx"] ?? -1)),
				"antwort" => (string) ($row["antwort"] ?? ""),
				"locked" => strcasecmp($lockedRaw, "ja") === 0 ? "Ja" : "Nein",
			);
		}
		$cleanSnaps = rkrk_assess_quiz_progress_normalize_snapshots($payload["question_snapshots"] ?? array());
		$cleanOrder = rkrk_assess_quiz_progress_normalize_question_order($payload["question_order"] ?? array());
		if (count($cleanOrder) < 1 && count($cleanSnaps) > 0) {
			foreach ($cleanSnaps as $snap) {
				$cleanOrder[] = trim((string) ($snap["qid"] ?? ""));
			}
		}
		$doc = array(
			"format" => "rkropen_quiz_progress_v1",
			"result_stem" => pathinfo($path, PATHINFO_FILENAME),
			"quiz_key" => $id["quiz_key"],
			"name" => $id["name"],
			"drone" => $id["drone"],
			"setting" => $id["setting"],
			"schulung" => $id["schulung"],
			"quiz_modus" => $id["quiz_modus"],
			"exam_code" => $id["exam_code"],
			"rkrs_pilot_id" => $id["rkrs_pilot_id"],
			"question_fingerprint" => trim((string) ($payload["question_fingerprint"] ?? "")),
			"max_questions" => max(0, (int) ($payload["max_questions"] ?? 0)),
			"at_question" => max(0, (int) ($payload["at_question"] ?? 0)),
			"quiz_timer_remaining" => max(0, (int) ($payload["quiz_timer_remaining"] ?? 0)),
			"exam_finish_confirmed" => !empty($payload["exam_finish_confirmed"]),
			"status" => trim((string) ($payload["status"] ?? "Quiz")),
			"learning_skipped" => !empty($payload["learning_skipped"]),
			"started" => true,
			"questions" => $cleanQ,
			"question_order" => $cleanOrder,
			"question_snapshots" => $cleanSnaps,
			"drive_location" => function_exists("rkrk_assess_clean_drive_location_rel")
				? rkrk_assess_clean_drive_location_rel((string) ($payload["drive_location"] ?? ""))
				: trim((string) ($payload["drive_location"] ?? ""), "/"),
			"started_at" => $startedAt,
			"updated_at" => $now,
		);
		$json = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false) {
			return array("ok" => false, "error" => "JSON-Fehler.");
		}
		if (@file_put_contents($path, $json, LOCK_EX) === false) {
			return array("ok" => false, "error" => "Speichern fehlgeschlagen.");
		}
		$legacy = rkrk_assess_quiz_progress_legacy_hash_path($id);
		if ($legacy !== "" && $legacy !== $path && is_file($legacy)) {
			@unlink($legacy);
		}
		$dir = rkrk_assess_quiz_progress_dir();
		$code = $id["exam_code"] !== "" ? $id["exam_code"] : $id["quiz_key"];
		$nameSlug = function_exists("rkrk_assess_slugify_file_stem")
			? rkrk_assess_slugify_file_stem($id["name"])
			: strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', "_", $id["name"]));
		if ($dir !== "" && $code !== "" && $nameSlug !== "") {
			$oldNamed = $dir . strtolower($code) . "_" . $nameSlug . ".json";
			if ($oldNamed !== $path && is_file($oldNamed)) {
				@unlink($oldNamed);
			}
		}
		rkrk_assess_quiz_progress_purge_old();
		return array("ok" => true, "saved_at" => $now);
	}
}

if (!function_exists("rkrk_assess_quiz_progress_check")) {
	function rkrk_assess_quiz_progress_check(array $identity, string $expectedFingerprint = ""): array {
		rkrk_assess_quiz_progress_purge_old_maybe();
		$id = rkrk_assess_quiz_progress_normalize_identity($identity);
		if ($id["quiz_key"] === "" || $id["name"] === "") {
			return array("ok" => true, "has_progress" => false);
		}
		if ($id["exam_code"] !== "") {
			if (!function_exists("rkrk_assess_exam_is_completed")) {
				@include_once __DIR__ . "/rkrk_assess_exam_session.inc.php";
			}
			if (function_exists("rkrk_assess_exam_load") && function_exists("rkrk_assess_exam_is_completed")) {
				$examSession = rkrk_assess_exam_load($id["exam_code"], $id["name"]);
				if (rkrk_assess_exam_is_completed($examSession, $id["name"])) {
					rkrk_assess_quiz_progress_delete($id);
					return array("ok" => true, "has_progress" => false, "exam_completed" => true);
				}
			}
		}
		$progress = rkrk_assess_quiz_progress_load($id);
		if ($progress === null) {
			return array("ok" => true, "has_progress" => false);
		}
		$maxQ = max(0, (int) ($progress["max_questions"] ?? 0));
		if (!rkrk_assess_quiz_progress_is_incomplete($progress, $maxQ)) {
			rkrk_assess_quiz_progress_delete($id);
			return array("ok" => true, "has_progress" => false);
		}
		if (!rkrk_assess_quiz_progress_identity_matches_saved($id, $progress)) {
			return array("ok" => true, "has_progress" => false, "identity_mismatch" => true);
		}
		$fp = trim((string) ($progress["question_fingerprint"] ?? ""));
		$fingerprintMismatch = (
			$expectedFingerprint !== ""
			&& $fp !== ""
			&& $fp !== $expectedFingerprint
		);
		if ($fingerprintMismatch) {
			return array("ok" => true, "has_progress" => false, "fingerprint_mismatch" => true);
		}
		return array(
			"ok" => true,
			"has_progress" => true,
			"progress" => $progress,
			"answered_count" => rkrk_assess_quiz_progress_count_answered($progress),
			"updated_at" => (string) ($progress["updated_at"] ?? ""),
			"fingerprint_mismatch" => false,
			"identity_mismatch" => false,
		);
	}
}
