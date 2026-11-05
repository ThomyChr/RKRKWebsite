<?php
/**
 * Versuche, deren Name keinem registrierten Teilnehmer entspricht,
 * einem Personaleintrag zuweisen. JSON und Report ziehen mit.
 */

if (!function_exists("rkrk_es_norm_name")) {
	$__naEs = __DIR__ . "/rkrk_assess_einsatzstatus.inc.php";
	if (is_file($__naEs)) {
		include_once $__naEs;
	}
	unset($__naEs);
}

if (!function_exists("rkrk_na_h")) {
	function rkrk_na_h(string $s): string {
		return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists("rkrk_na_basename_ok")) {
	function rkrk_na_basename_ok(string $base): bool {
		return preg_match('/^[A-Za-z0-9_.-]+\.json$/', $base) === 1;
	}
}

if (!function_exists("rkrk_na_people")) {
	/**
	 * @return list<array{id:string,name:string,rkrs:string,row:array<string,mixed>}>
	 */
	function rkrk_na_people(): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$out = array();
		$seen = array();
		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = function_exists("rkrk_tr_pilot_key") ? rkrk_tr_pilot_key($row) : trim((string) ($row["id"] ?? ""));
			if ($id === "" || isset($seen[$id]) || !rkrk_na_person_is_real($row)) {
				continue;
			}
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim((string) ($row["vorname"] ?? "") . " " . (string) ($row["nachname"] ?? ""));
			if ($name === "") {
				continue;
			}
			$rkrs = function_exists("rkrk_assess_personel_row_effective_rkrs_id")
				? rkrk_assess_personel_row_effective_rkrs_id($row)
				: trim((string) ($row["rkrs_pilot_id"] ?? ""));
			$out[] = array("id" => $id, "name" => $name, "rkrs" => $rkrs, "row" => $row);
			$seen[$id] = true;
		}
		usort($out, static function (array $a, array $b): int {
			return strcasecmp($a["name"], $b["name"]);
		});
		return $out;
	}
}

if (!function_exists("rkrk_na_person_is_real")) {
	/** Vorname ohne Nachname und ohne Piloten-ID zählt nicht als Zuordnung. */
	function rkrk_na_person_is_real(array $row): bool {
		$vn = trim((string) ($row["vorname"] ?? ""));
		$nn = trim((string) ($row["nachname"] ?? ""));
		$rkrs = function_exists("rkrk_assess_personel_row_effective_rkrs_id")
			? rkrk_assess_personel_row_effective_rkrs_id($row)
			: trim((string) ($row["rkrs_pilot_id"] ?? ""));
		if ($rkrs !== "") {
			return true;
		}
		return $vn !== "" && $nn !== "";
	}
}

if (!function_exists("rkrk_na_exact_aliases")) {
	/** @param list<array{id:string,name:string,rkrs:string,row:array<string,mixed>}> $people @return array<string,true> */
	function rkrk_na_exact_aliases(array $people): array {
		$set = array();
		foreach ($people as $person) {
			$row = $person["row"];
			$names = array($person["name"]);
			$vn = trim((string) ($row["vorname"] ?? ""));
			$nn = trim((string) ($row["nachname"] ?? ""));
			if ($vn !== "" && $nn !== "") {
				$names[] = $vn . " " . $nn;
				$names[] = $nn . " " . $vn;
			}
			if ($person["rkrs"] !== "") {
				$names[] = $person["rkrs"];
			}
			foreach ($names as $name) {
				$n = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($name) : strtolower(trim($name));
				if ($n !== "") {
					$set[$n] = true;
				}
			}
		}
		return $set;
	}
}

if (!function_exists("rkrk_na_known")) {
	/** @param array<string,true> $aliases */
	function rkrk_na_known(string $name, array $aliases): bool {
		$n = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($name) : strtolower(trim($name));
		return $n !== "" && isset($aliases[$n]);
	}
}

if (!function_exists("rkrk_na_read_json")) {
	/** @return array<string,mixed>|null */
	function rkrk_na_read_json(string $path): ?array {
		if ($path === "" || !is_file($path)) {
			return null;
		}
		$raw = @file_get_contents($path);
		if (!is_string($raw) || trim($raw) === "") {
			return null;
		}
		$data = json_decode($raw, true);
		return is_array($data) ? $data : null;
	}
}

if (!function_exists("rkrk_na_write_json")) {
	/** @param array<string,mixed> $data */
	function rkrk_na_write_json(string $path, array $data): bool {
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
		return true;
	}
}

if (!function_exists("rkrk_na_exam_dir")) {
	function rkrk_na_exam_dir(): string {
		if (!function_exists("rkrk_assess_exam_storage_dir")) {
			$inc = __DIR__ . "/rkrk_assess_exam_session.inc.php";
			if (is_file($inc)) {
				include_once $inc;
			}
		}
		if (!function_exists("rkrk_assess_exam_storage_dir")) {
			return "";
		}
		$dir = rkrk_assess_exam_storage_dir();
		return $dir !== "" ? rtrim(str_replace("\\", "/", $dir), "/") . "/" : "";
	}
}

if (!function_exists("rkrk_na_check_dirs")) {
	/** @return array<string,string> */
	function rkrk_na_check_dirs(): array {
		$out = array();
		foreach (array(
			"assess" => "rkrkAssessResults",
			"live" => "rkrkLiveassessResults",
		) as $kind => $global) {
			$raw = trim((string) ($GLOBALS[$global] ?? ""));
			if ($raw === "") {
				continue;
			}
			$dir = rtrim(str_replace("\\", "/", $raw), "/") . "/";
			if (is_dir($dir)) {
				$out[$kind] = $dir;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_na_unmatched")) {
	/**
	 * @return list<array{kind:string,file:string,name:string,when:string,what:string}>
	 */
	function rkrk_na_unmatched(): array {
		$people = rkrk_na_people();
		$aliases = rkrk_na_exact_aliases($people);
		$rows = array();
		$dir = rkrk_na_exam_dir();
		if ($dir !== "") {
			foreach (glob($dir . "*.json") ?: array() as $file) {
				if (!is_string($file) || !is_file($file)) {
					continue;
				}
				$data = rkrk_na_read_json($file);
				if (!is_array($data)) {
					continue;
				}
				$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
				$name = "";
				$when = trim((string) ($data["updated_at"] ?? $data["created_at"] ?? ""));
				if ($attempts === array()) {
					$name = trim((string) ($data["name"] ?? ""));
				} else {
					foreach ($attempts as $attempt) {
						if (!is_array($attempt)) {
							continue;
						}
						$try = trim((string) ($attempt["name"] ?? ""));
						if ($try === "" && isset($attempt["result"]) && is_array($attempt["result"])) {
							$try = trim((string) ($attempt["result"]["name"] ?? ""));
						}
						if ($try === "") {
							continue;
						}
						$name = $try;
						$stamp = trim((string) ($attempt["completed_at"] ?? $attempt["created_at"] ?? ""));
						if ($stamp !== "") {
							$when = $stamp;
						}
						break;
					}
				}
				if ($name === "" || rkrk_na_known($name, $aliases)) {
					continue;
				}
				$what = trim((string) ($data["schulung"] ?? ""));
				if ($what === "") {
					$what = trim((string) ($data["quiz_key"] ?? "Quiz"));
				}
				$modus = trim((string) ($data["quiz_modus"] ?? ""));
				if ($modus !== "") {
					$what .= " · " . $modus;
				}
				$rows[] = array(
					"kind" => "exam",
					"file" => basename($file),
					"name" => $name,
					"when" => $when,
					"what" => $what,
				);
			}
		}
		foreach (rkrk_na_check_dirs() as $kind => $checkDir) {
			foreach (glob($checkDir . "*.json") ?: array() as $file) {
				if (!is_string($file) || !is_file($file)) {
					continue;
				}
				$data = rkrk_na_read_json($file);
				if (!is_array($data) || (string) ($data["format"] ?? "") !== "rkropen_checklist_result_v1") {
					continue;
				}
				$fields = isset($data["fields"]) && is_array($data["fields"]) ? $data["fields"] : array();
				$ag = isset($fields["Allgemein"]) && is_array($fields["Allgemein"]) ? $fields["Allgemein"] : array();
				$name = trim((string) ($ag["Pilot"] ?? ""));
				if ($name === "" || rkrk_na_known($name, $aliases)) {
					continue;
				}
				$rows[] = array(
					"kind" => $kind,
					"file" => basename($file),
					"name" => $name,
					"when" => trim((string) ($data["created_at"] ?? "")),
					"what" => "Assessment",
				);
			}
		}
		usort($rows, static function (array $a, array $b): int {
			return strcmp($b["when"], $a["when"]);
		});
		if (count($rows) > 40) {
			$rows = array_slice($rows, 0, 40);
		}
		return $rows;
	}
}

if (!function_exists("rkrk_na_groups")) {
	/**
	 * Versuche mit demselben ungültigen Namen, als eine Gruppe.
	 *
	 * @return list<array{key:string,name:string,items:list<array{kind:string,file:string,name:string,when:string,what:string}>}>
	 */
	function rkrk_na_groups(): array {
		$groups = array();
		foreach (rkrk_na_unmatched() as $row) {
			$key = function_exists("rkrk_es_norm_name")
				? rkrk_es_norm_name((string) ($row["name"] ?? ""))
				: strtolower(trim((string) ($row["name"] ?? "")));
			if ($key === "") {
				continue;
			}
			if (!isset($groups[$key])) {
				$groups[$key] = array(
					"key" => $key,
					"name" => (string) ($row["name"] ?? ""),
					"items" => array(),
				);
			}
			$groups[$key]["items"][] = $row;
		}
		$list = array_values($groups);
		usort($list, static function (array $a, array $b): int {
			$ca = count($a["items"]);
			$cb = count($b["items"]);
			if ($ca !== $cb) {
				return $cb <=> $ca;
			}
			return strcasecmp($a["name"], $b["name"]);
		});
		return $list;
	}
}

if (!function_exists("rkrk_na_assign_group")) {
	/** Alle offenen Versuche dieser Namensgruppe dem Mitglied zuweisen. */
	function rkrk_na_assign_group(string $nameKey, string $personId): int {
		$want = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($nameKey) : strtolower(trim($nameKey));
		if ($want === "" || trim($personId) === "") {
			return 0;
		}
		$n = 0;
		foreach (rkrk_na_unmatched() as $row) {
			$key = function_exists("rkrk_es_norm_name")
				? rkrk_es_norm_name((string) ($row["name"] ?? ""))
				: strtolower(trim((string) ($row["name"] ?? "")));
			if ($key !== $want) {
				continue;
			}
			if (rkrk_na_assign((string) ($row["kind"] ?? ""), (string) ($row["file"] ?? ""), $personId)) {
				$n++;
			}
		}
		if ($n > 0) {
			rkrk_na_sync_quiz_names();
		}
		return $n;
	}
}

if (!function_exists("rkrk_na_minute_key")) {
	/** d.m.Y H:i oder ISO-Zeit auf Minute genau. */
	function rkrk_na_minute_key(string $raw): string {
		$raw = trim(str_replace("T", " ", $raw));
		if ($raw === "") {
			return "";
		}
		if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{2}))?/', $raw, $m) === 1) {
			return sprintf("%04d-%02d-%02d %02d:%02d", (int) $m[3], (int) $m[2], (int) $m[1], (int) ($m[4] ?? 0), (int) ($m[5] ?? 0));
		}
		$ts = strtotime($raw);
		if ($ts === false) {
			return "";
		}
		return date("Y-m-d H:i", $ts);
	}
}

if (!function_exists("rkrk_na_sync_quiz_names")) {
	/**
	 * QuizResultate und geplante Tests auf den Namen der schon zugewiesenen Prüfung ziehen.
	 * Gleiche Zeit oder gleicher Vorname am selben Tag und Quiz.
	 */
	function rkrk_na_sync_quiz_names(): int {
		if (!function_exists("rkrk_assess_exam_list_completed")) {
			$inc = __DIR__ . "/rkrk_assess_exam_session.inc.php";
			if (is_file($inc)) {
				include_once $inc;
			}
		}
		if (
			!function_exists("rkrk_assess_exam_list_completed")
			|| !function_exists("rkrk_assess_quiz_results_load_rows")
			|| !function_exists("rkrk_assess_quiz_results_save_rows")
		) {
			return 0;
		}
		$resultsDir = isset($GLOBALS["QuizLocResults"]) ? rtrim((string) $GLOBALS["QuizLocResults"], "/\\") : "";
		if ($resultsDir === "") {
			return 0;
		}
		$exams = rkrk_assess_exam_list_completed();
		$rows = rkrk_assess_quiz_results_load_rows($resultsDir);
		if (!is_array($rows) || $rows === array()) {
			return 0;
		}
		$changed = 0;
		$renameMap = array();
		foreach ($rows as $i => $row) {
			if (!is_array($row)) {
				continue;
			}
			$oldName = trim((string) ($row["Name"] ?? ""));
			if ($oldName === "") {
				continue;
			}
			$oldNorm = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($oldName) : strtolower($oldName);
			$oldToken = preg_split("/\s+/", $oldNorm);
			$oldToken = is_array($oldToken) ? (string) ($oldToken[0] ?? "") : "";
			$rowMin = rkrk_na_minute_key((string) ($row["Zeit"] ?? ""));
			$rowDay = substr($rowMin, 0, 10);
			$rowQuiz = strtolower(trim((string) ($row["Quiz"] ?? "")));
			$rowCode = function_exists("rkrk_assess_exam_normalize_code")
				? strtolower(rkrk_assess_exam_normalize_code((string) ($row["EXAM"] ?? "")))
				: strtolower(trim((string) ($row["EXAM"] ?? "")));
			$names = array();
			foreach ($exams as $ex) {
				if (!is_array($ex)) {
					continue;
				}
				$exName = trim((string) ($ex["name"] ?? ""));
				if ($exName === "" || $exName === "(ohne Pilot)") {
					continue;
				}
				$exQuiz = strtolower(trim((string) ($ex["quiz_key"] ?? "")));
				if ($rowQuiz !== "" && $exQuiz !== "" && $rowQuiz !== $exQuiz) {
					continue;
				}
				$exCode = function_exists("rkrk_assess_exam_normalize_code")
					? strtolower(rkrk_assess_exam_normalize_code((string) ($ex["exam_code"] ?? "")))
					: strtolower(trim((string) ($ex["exam_code"] ?? "")));
				if ($rowCode !== "" && $exCode !== "" && $rowCode !== $exCode) {
					continue;
				}
				$exMin = rkrk_na_minute_key((string) ($ex["zeit"] ?? ""));
				$exDay = substr($exMin, 0, 10);
				$exNorm = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($exName) : strtolower($exName);
				$exToken = preg_split("/\s+/", $exNorm);
				$exToken = is_array($exToken) ? (string) ($exToken[0] ?? "") : "";
				$timeOk = ($rowMin !== "" && $rowMin === $exMin);
				$nameOk = ($oldToken !== "" && $oldToken === $exToken && $rowDay !== "" && $rowDay === $exDay && $exNorm !== $oldNorm);
				if (!$timeOk && !$nameOk) {
					continue;
				}
				$names[$exNorm] = $exName;
			}
			if (count($names) !== 1) {
				continue;
			}
			$newName = (string) reset($names);
			$newNorm = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($newName) : strtolower($newName);
			if ($newName === "" || $newNorm === "" || $newNorm === $oldNorm) {
				continue;
			}
			$rows[$i]["Name"] = $newName;
			$rkrs = function_exists("rkrk_assess_personel_rkrs_id_by_name")
				? rkrk_assess_personel_rkrs_id_by_name($newName)
				: "";
			if ($rkrs !== "") {
				$rows[$i]["PilotID"] = $rkrs;
				$rows[$i]["RKRSPilotID"] = $rkrs;
				if (array_key_exists("PilotenID", $rows[$i])) {
					$rows[$i]["PilotenID"] = $rkrs;
				}
			}
			if (!isset($renameMap[$oldName])) {
				$renameMap[$oldName] = array();
			}
			$renameMap[$oldName][$newName] = true;
			$changed++;
		}
		if ($changed > 0) {
			rkrk_assess_quiz_results_save_rows($resultsDir, $rows);
			rkrk_na_rename_manager_people($renameMap);
		}
		return $changed;
	}
}

if (!function_exists("rkrk_na_rename_manager_people")) {
	/** @param array<string, array<string, true>> $renameMap */
	function rkrk_na_rename_manager_people(array $renameMap): void {
		$unique = array();
		foreach ($renameMap as $oldName => $targets) {
			if (!is_array($targets) || count($targets) !== 1) {
				continue;
			}
			$newName = "";
			foreach ($targets as $candidate => $_unused) {
				$newName = (string) $candidate;
				break;
			}
			if ($newName === "") {
				continue;
			}
			$unique[(string) $oldName] = $newName;
		}
		if ($unique === array()) {
			return;
		}
		$quizLoc = isset($GLOBALS["QuizLoc"]) ? rtrim((string) $GLOBALS["QuizLoc"], "/\\") : "";
		if ($quizLoc === "") {
			return;
		}
		$path = $quizLoc . DIRECTORY_SEPARATOR . "AssessmentManager.csv";
		if (!is_file($path)) {
			return;
		}
		$headers = array("Id", "SeriesId", "SeriesTitle", "Person", "Source", "QuizKey", "TestType", "TestRef", "PlannedAt", "DoneAt", "TheoryPassed", "PracticalPassed", "ResultStatus", "Note");
		if (!function_exists("exam_admin_read_csv_rows") || !function_exists("exam_admin_write_csv_rows")) {
			return;
		}
		$rows = exam_admin_read_csv_rows($path, $headers);
		$dirty = false;
		foreach ($rows as $i => $row) {
			$person = trim((string) ($row["Person"] ?? ""));
			if ($person !== "" && isset($unique[$person])) {
				$rows[$i]["Person"] = $unique[$person];
				$dirty = true;
			}
		}
		if ($dirty) {
			exam_admin_write_csv_rows($path, $headers, $rows);
		}
	}
}

if (!function_exists("rkrk_na_person")) {
	/** @return array{id:string,name:string,rkrs:string,row:array<string,mixed>}|null */
	function rkrk_na_person(string $id): ?array {
		$id = trim($id);
		if ($id === "") {
			return null;
		}
		foreach (rkrk_na_people() as $person) {
			if ($person["id"] === $id) {
				return $person;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_na_resolve")) {
	/** @return array{kind:string,path:string,dir:string}|null */
	function rkrk_na_resolve(string $kind, string $file): ?array {
		if (!rkrk_na_basename_ok($file)) {
			return null;
		}
		$dir = "";
		if ($kind === "exam") {
			$dir = rkrk_na_exam_dir();
		} else {
			$dirs = rkrk_na_check_dirs();
			$dir = $dirs[$kind] ?? "";
		}
		if ($dir === "") {
			return null;
		}
		$path = $dir . $file;
		$realDir = realpath($dir);
		$real = realpath($path);
		if ($realDir === false || $real === false || !is_file($real)) {
			return null;
		}
		$realDir = rtrim(str_replace("\\", "/", $realDir), "/") . "/";
		$realNorm = str_replace("\\", "/", $real);
		if (strpos($realNorm, $realDir) !== 0) {
			return null;
		}
		return array("kind" => $kind, "path" => $realNorm, "dir" => $realDir);
	}
}

if (!function_exists("rkrk_na_rebuild_exam_report")) {
	/** @param array<string,mixed> $data */
	function rkrk_na_rebuild_exam_report(array $data, string $path): bool {
		if (!function_exists("rkrk_assess_ensure_report_from_completed_exam")) {
			$inc = __DIR__ . "/rkrk_assess_report_pdf.inc.php";
			if (is_file($inc)) {
				include_once $inc;
			}
		}
		if (!function_exists("rkrk_assess_ensure_report_from_completed_exam")) {
			return false;
		}
		$attempt = null;
		$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
		foreach ($attempts as $item) {
			if (is_array($item) && isset($item["result"]) && is_array($item["result"])) {
				$attempt = $item;
				break;
			}
		}
		if (!is_array($attempt)) {
			return false;
		}
		$result = $attempt["result"];
		$row = array(
			"stem" => pathinfo($path, PATHINFO_FILENAME),
			"path" => $path,
			"result" => $result,
			"quiz_key" => trim((string) ($data["quiz_key"] ?? "")),
			"name" => trim((string) ($attempt["name"] ?? ($result["name"] ?? ""))),
			"drone" => trim((string) ($attempt["drone"] ?? ($result["drone"] ?? ""))),
			"exam_code" => trim((string) ($data["exam_code"] ?? "")),
			"quiz_modus" => trim((string) ($data["quiz_modus"] ?? "")),
			"zeit" => trim((string) ($result["zeit"] ?? "")),
			"bestanden" => trim((string) ($result["bestanden"] ?? "")),
		);
		$save = rkrk_assess_ensure_report_from_completed_exam($row, true);
		return !empty($save["ok"]);
	}
}

if (!function_exists("rkrk_na_assign_exam")) {
	/** @param array{id:string,name:string,rkrs:string,row:array<string,mixed>} $person */
	function rkrk_na_assign_exam(string $path, array $person): bool {
		if (!function_exists("rkrk_assess_exam_save")) {
			$inc = __DIR__ . "/rkrk_assess_exam_session.inc.php";
			if (is_file($inc)) {
				include_once $inc;
			}
		}
		if (!function_exists("rkrk_assess_exam_save") || !function_exists("rkrk_assess_exam_pilot_key")) {
			return false;
		}
		$data = rkrk_na_read_json($path);
		if (!is_array($data)) {
			return false;
		}
		$newName = $person["name"];
		$newKey = rkrk_assess_exam_pilot_key($newName);
		if ($newKey === "") {
			return false;
		}
		$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
		$next = array();
		foreach ($attempts as $attempt) {
			if (!is_array($attempt)) {
				continue;
			}
			$attempt["name"] = $newName;
			if (isset($attempt["result"]) && is_array($attempt["result"])) {
				$attempt["result"]["name"] = $newName;
			}
			$next[$newKey] = $attempt;
		}
		if ($next === array() && trim((string) ($data["name"] ?? "")) !== "") {
			$data["name"] = $newName;
		}
		$data["attempts"] = $next;
		$data["name"] = $newName;
		if ($person["rkrs"] !== "") {
			$data["rkrs_pilot_id"] = $person["rkrs"];
			$data["RKRSPilotID"] = $person["rkrs"];
		}
		$code = trim((string) ($data["exam_code"] ?? ""));
		if ($code === "") {
			return false;
		}
		if (!rkrk_assess_exam_save($code, $data, $newName)) {
			return false;
		}
		$lookup = $data;
		unset($lookup["result_stem"]);
		$newPath = function_exists("rkrk_assess_exam_resolve_storage_path")
			? rkrk_assess_exam_resolve_storage_path($code, $newName, $lookup)
			: $path;
		if ($newPath === "" || !is_file($newPath)) {
			$newPath = $path;
		}
		$saved = rkrk_na_read_json($newPath);
		if (!is_array($saved)) {
			$saved = $data;
			$saved["name"] = $newName;
		}
		rkrk_na_rebuild_exam_report($saved, $newPath);
		return true;
	}
}

if (!function_exists("rkrk_na_assign_check")) {
	/** @param array{id:string,name:string,rkrs:string,row:array<string,mixed>} $person */
	function rkrk_na_assign_check(string $path, string $kind, array $person): bool {
		$data = rkrk_na_read_json($path);
		if (!is_array($data)) {
			return false;
		}
		$fields = isset($data["fields"]) && is_array($data["fields"]) ? $data["fields"] : array();
		if (!isset($fields["Allgemein"]) || !is_array($fields["Allgemein"])) {
			$fields["Allgemein"] = array();
		}
		$oldName = trim((string) ($fields["Allgemein"]["Pilot"] ?? ""));
		$fields["Allgemein"]["Pilot"] = $person["name"];
		if ($person["rkrs"] !== "") {
			$fields["Allgemein"]["RKRSPilotID"] = $person["rkrs"];
		}
		$data["fields"] = $fields;
		$tpl = basename((string) ($data["template"] ?? ""));
		$title = function_exists("rkrk_assess_checklist_title_slug")
			? rkrk_assess_checklist_title_slug($tpl, "")
			: pathinfo($tpl, PATHINFO_FILENAME);
		$stamp = function_exists("rkrk_assess_checklist_completion_stamp")
			? rkrk_assess_checklist_completion_stamp(pathinfo($path, PATHINFO_FILENAME), (string) ($data["created_at"] ?? ""))
			: (string) ($data["created_at"] ?? "");
		$newStem = function_exists("rkrk_assess_build_checklist_result_json_stem")
			? rkrk_assess_build_checklist_result_json_stem($title, $fields, $stamp)
			: "";
		$oldStem = pathinfo($path, PATHINFO_FILENAME);
		if ($newStem === "") {
			$newStem = $oldStem;
		}
		$data["result_stem"] = $newStem;
		if (!rkrk_na_write_json($path, $data)) {
			return false;
		}
		$dir = rtrim(str_replace("\\", "/", dirname($path)), "/") . "/";
		if ($newStem !== $oldStem && function_exists("rkrk_assess_rename_result_and_reports")) {
			$renamed = rkrk_assess_rename_result_and_reports($dir, $oldStem, $newStem, $kind === "live" ? "live" : "assess");
			$final = $dir . $renamed . ".json";
			if (is_file($final)) {
				$again = rkrk_na_read_json($final);
				if (is_array($again)) {
					$again["result_stem"] = $renamed;
					rkrk_na_write_json($final, $again);
				}
			}
		}
		if ($oldName !== "" && $oldName !== $person["name"] && function_exists("rkrk_assess_reports_dir")) {
			$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
			$GLOBALS["rkrk_assess_reports_kind"] = ($kind === "live") ? "live" : "assess";
			$rep = rkrk_assess_reports_dir();
			if ($prevKind === null) {
				unset($GLOBALS["rkrk_assess_reports_kind"]);
			} else {
				$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
			}
			$htmlPath = ($rep !== "" ? rtrim(str_replace("\\", "/", $rep), "/") . "/" : "") . $newStem . ".html";
			if ($htmlPath !== "" && is_file($htmlPath) && function_exists("rkrk_assess_write_report_pdf")) {
				$html = (string) @file_get_contents($htmlPath);
				$html = str_replace($oldName, $person["name"], $html);
				@file_put_contents($htmlPath, $html, LOCK_EX);
				rkrk_assess_write_report_pdf($newStem, $html);
			}
		}
		return true;
	}
}

if (!function_exists("rkrk_na_assign")) {
	function rkrk_na_assign(string $kind, string $file, string $personId): bool {
		$found = rkrk_na_resolve($kind, $file);
		$person = rkrk_na_person($personId);
		if ($found === null || $person === null) {
			return false;
		}
		if ($kind === "exam") {
			return rkrk_na_assign_exam($found["path"], $person);
		}
		return rkrk_na_assign_check($found["path"], $kind, $person);
	}
}
