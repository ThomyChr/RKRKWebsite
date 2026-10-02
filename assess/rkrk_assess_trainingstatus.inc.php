<?php
/**
 * Trainingsübersicht: Theorie/Quiz je konfigurierbarer Liste (Piloten + EXAM-Codes).
 */

if (!function_exists("rkrk_tr_page_href")) {
	/** URL zur Trainingsübersicht inkl. Setting- und Personal-cs-Parametern. */
	function rkrk_tr_page_href(array $query = array()): string {
		if (function_exists("rkrk_assess_personel_merge_cs_query")) {
			$query = rkrk_assess_personel_merge_cs_query($query);
		}
		foreach (array("ADMIN", "INSTR", "NOH") as $flag) {
			if (array_key_exists($flag, $_GET) && !array_key_exists($flag, $query)) {
				$query[$flag] = (string) $_GET[$flag];
			}
		}
		$path = "rkrk_assess_trainingstatus.php";
		$qs = http_build_query($query);
		if ($qs !== "") {
			$path .= "?" . $qs;
		}
		if (function_exists("rkrk_assess_append_setting_href")) {
			return rkrk_assess_append_setting_href($path);
		}
		return $path;
	}
}

if (!function_exists("rkrk_tr_norm_code")) {
	function rkrk_tr_norm_code(string $code): string {
		$code = trim($code);
		if ($code === "") {
			return "";
		}
		if (function_exists("rkrk_assess_exam_normalize_code")) {
			$code = rkrk_assess_exam_normalize_code($code);
		} else {
			$code = (string) preg_replace("/[^A-Za-z0-9_-]/", "", $code);
		}
		return strtoupper(trim((string) $code));
	}
}

if (!function_exists("rkrk_tr_slug")) {
	function rkrk_tr_slug(string $name): string {
		$s = function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($name) : strtolower(trim($name));
		$s = (string) preg_replace("/\s+/", "-", $s);
		$s = (string) preg_replace("/[^a-z0-9-]/", "", $s);
		$s = trim($s, "-");
		if ($s === "") {
			$s = "liste";
		}
		return substr($s, 0, 48);
	}
}

if (!function_exists("rkrk_tr_name_parts")) {
	/**
	 * Vor- und Nachname einer Personalzeile (Fallback: Anzeigename).
	 *
	 * @return array{0:string,1:string} [vorname, nachname]
	 */
	function rkrk_tr_name_parts(array $row): array {
		$vor = trim((string) ($row["vorname"] ?? ""));
		$nach = trim((string) ($row["nachname"] ?? ""));
		if ($vor !== "" || $nach !== "") {
			return array($vor, $nach);
		}
		$name = trim((string) ($row["name"] ?? ($row["display"] ?? "")));
		if ($name === "" && function_exists("rkrk_assess_personel_row_display_name")) {
			$name = rkrk_assess_personel_row_display_name($row);
		}
		return array($name, "");
	}
}

if (!function_exists("rkrk_tr_cmp_name")) {
	/** Vergleich Nachname/Vorname bzw. Vorname/Nachname. */
	function rkrk_tr_cmp_name(string $nachA, string $vorA, string $nachB, string $vorB, string $mode = "nach"): int {
		if ($mode === "vor") {
			$c = strnatcasecmp($vorA, $vorB);
			return $c !== 0 ? $c : strnatcasecmp($nachA, $nachB);
		}
		$c = strnatcasecmp($nachA, $nachB);
		return $c !== 0 ? $c : strnatcasecmp($vorA, $vorB);
	}
}

if (!function_exists("rkrk_tr_sort_rows")) {
	/**
	 * @param list<array<string,mixed>> $rows
	 * @return list<array<string,mixed>>
	 */
	function rkrk_tr_sort_rows(array $rows, string $mode = "nach"): array {
		$mode = $mode === "vor" ? "vor" : "nach";
		usort($rows, static function (array $a, array $b) use ($mode): int {
			return rkrk_tr_cmp_name(
				(string) ($a["nachname"] ?? ""),
				(string) ($a["vorname"] ?? ""),
				(string) ($b["nachname"] ?? ""),
				(string) ($b["vorname"] ?? ""),
				$mode
			);
		});
		return array_values($rows);
	}
}

if (!function_exists("rkrk_tr_pilot_key")) {
	/** Stabile ID einer Personalzeile für Listen-Zuordnung. */
	function rkrk_tr_pilot_key(array $row): string {
		$id = trim((string) ($row["id"] ?? ""));
		if ($id !== "") {
			return $id;
		}
		$rkrs = function_exists("rkrk_assess_personel_row_effective_rkrs_id")
			? rkrk_assess_personel_row_effective_rkrs_id($row)
			: trim((string) ($row["rkrs_pilot_id"] ?? ""));
		if ($rkrs !== "") {
			return $rkrs;
		}
		$pid = trim((string) ($row["pilot_id"] ?? ""));
		if ($pid !== "") {
			return $pid;
		}
		$name = function_exists("rkrk_assess_personel_row_display_name")
			? rkrk_assess_personel_row_display_name($row)
			: trim((string) ($row["vorname"] ?? "") . " " . (string) ($row["nachname"] ?? ""));
		return function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($name) : strtolower($name);
	}
}

if (!function_exists("rkrk_tr_pilot_keys_of")) {
	/** @return list<string> */
	function rkrk_tr_pilot_keys_of(array $row): array {
		$out = array();
		foreach (array(
			trim((string) ($row["id"] ?? "")),
			rkrk_tr_pilot_key($row),
			trim((string) ($row["pilot_id"] ?? "")),
			function_exists("rkrk_assess_personel_row_effective_rkrs_id")
				? rkrk_assess_personel_row_effective_rkrs_id($row)
				: trim((string) ($row["rkrs_pilot_id"] ?? "")),
		) as $k) {
			$k = trim((string) $k);
			if ($k !== "") {
				$out[$k] = true;
			}
		}
		return array_keys($out);
	}
}

if (!function_exists("rkrk_tr_config_defaults")) {
	/** @return array{lists: list<array<string,mixed>>} */
	function rkrk_tr_config_defaults(): array {
		return array(
			"lists" => array(
				array(
					"id" => "wk26-mogelsberg",
					"name" => "WK26 Mogelsberg",
					"exam_codes" => array(),
					"pilot_ids" => array(),
				),
				array(
					"id" => "wk26-sa-engelberg",
					"name" => "WK26 SA Engelberg",
					"exam_codes" => array(),
					"pilot_ids" => array(),
				),
			),
		);
	}
}

if (!function_exists("rkrk_tr_config_path")) {
	function rkrk_tr_config_path(): string {
		$dir = "";
		$p = function_exists("rkrk_assess_personel_get_file_path") ? rkrk_assess_personel_get_file_path() : null;
		if ($p) {
			$dir = dirname($p);
		} elseif (isset($GLOBALS["rkrkLivePersonelRoot"])) {
			$dir = rtrim((string) $GLOBALS["rkrkLivePersonelRoot"], "/\\");
		}
		if ($dir === "") {
			return "";
		}
		$new = $dir . DIRECTORY_SEPARATOR . "teilnehmerliste_config.json";
		$old = $dir . DIRECTORY_SEPARATOR . "trainingstatus_config.json";
		if (!is_file($new) && is_file($old)) {
			if (@rename($old, $new)) {
				return $new;
			}
			return $old;
		}
		return $new;
	}
}

if (!function_exists("rkrk_tr_parse_codes")) {
	/** @param mixed $src @return list<string> */
	function rkrk_tr_parse_codes($src): array {
		if (is_string($src)) {
			$src = preg_split("/[\s,;]+/", $src) ?: array();
		}
		$codes = array();
		if (is_array($src)) {
			foreach ($src as $c) {
				$n = rkrk_tr_norm_code((string) $c);
				if ($n !== "") {
					$codes[$n] = true;
				}
			}
		}
		return array_keys($codes);
	}
}

if (!function_exists("rkrk_tr_parse_ids")) {
	/** @param mixed $src @return list<string> */
	function rkrk_tr_parse_ids($src): array {
		if (is_string($src)) {
			$src = preg_split("/[\s,;]+/", $src) ?: array();
		}
		$ids = array();
		if (is_array($src)) {
			foreach ($src as $id) {
				$id = trim((string) $id);
				if ($id !== "") {
					$ids["id:" . $id] = $id;
				}
			}
		}
		return array_values($ids);
	}
}

if (!function_exists("rkrk_tr_parse_remarks")) {
	/** @param mixed $src @return array<string,string> keyed as id:… */
	function rkrk_tr_parse_remarks($src): array {
		$out = array();
		if (!is_array($src)) {
			return $out;
		}
		foreach ($src as $k => $v) {
			$id = trim((string) $k);
			if (strpos($id, "id:") === 0) {
				$id = substr($id, 3);
			}
			$id = trim($id);
			$txt = trim((string) $v);
			if ($id !== "" && $txt !== "") {
				$out["id:" . $id] = $txt;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_tr_parse_disabled")) {
	/** @param mixed $src @return array<string,true> keyed as id:… */
	function rkrk_tr_parse_disabled($src): array {
		$out = array();
		if (!is_array($src)) {
			return $out;
		}
		foreach ($src as $k => $v) {
			$id = trim((string) $k);
			if (strpos($id, "id:") === 0) {
				$id = substr($id, 3);
			}
			$id = trim($id);
			if ($id === "") {
				continue;
			}
			$on = $v === true || $v === 1 || $v === "1" || strcasecmp((string) $v, "ja") === 0;
			if ($on) {
				$out["id:" . $id] = true;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_tr_known_codes")) {
	/**
	 * EXAM-Codes und Quiz-Keys aus Resultaten, AssessmentListe und bestehenden Listen.
	 *
	 * @return list<string>
	 */
	function rkrk_tr_known_codes(?array $cfg = null): array {
		$out = array();
		$add = static function ($c) use (&$out): void {
			$n = rkrk_tr_norm_code((string) $c);
			if ($n !== "") {
				$out["c:" . $n] = $n;
			}
		};
		foreach (array("EP_26", "EP_27", "EP_28") as $c) {
			$add($c);
		}
		if (function_exists("rkrk_es_config_load")) {
			foreach ((array) (rkrk_es_config_load()["exam_codes"] ?? array()) as $c) {
				$add($c);
			}
		}
		if ($cfg === null) {
			$cfg = rkrk_tr_config_load();
		}
		foreach ((array) ($cfg["lists"] ?? array()) as $lst) {
			if (!is_array($lst)) {
				continue;
			}
			foreach (rkrk_tr_parse_codes($lst["exam_codes"] ?? array()) as $c) {
				$add($c);
			}
		}
		if (function_exists("rkrk_tr_collect_results")) {
			foreach (rkrk_tr_collect_results() as $r) {
				if (!is_array($r)) {
					continue;
				}
				$add($r["exam_code"] ?? "");
				$add($r["quiz"] ?? "");
			}
		}
		$quizLoc = isset($GLOBALS["QuizLoc"]) ? rtrim((string) $GLOBALS["QuizLoc"], "/\\") : "";
		$liste = $quizLoc !== "" ? $quizLoc . DIRECTORY_SEPARATOR . "AssessmentListe.csv" : "";
		if ($liste !== "" && is_file($liste) && function_exists("csv_to_array_ascII")) {
			$rows = csv_to_array_ascII($liste, ";");
			if (is_array($rows)) {
				foreach ($rows as $row) {
					if (!is_array($row)) {
						continue;
					}
					$add($row["Key"] ?? ($row["key"] ?? ""));
				}
			}
		}
		$keys = array_values($out);
		natcasesort($keys);
		return array_values($keys);
	}
}

if (!function_exists("rkrk_tr_normalize_list")) {
	/** @param array<string,mixed> $raw @return array<string,mixed> */
	function rkrk_tr_normalize_list(array $raw, array $usedIds = array()): array {
		$name = trim((string) ($raw["name"] ?? ""));
		$id = trim((string) ($raw["id"] ?? ""));
		if ($id === "") {
			$id = rkrk_tr_slug($name !== "" ? $name : "liste");
		}
		$base = $id;
		$n = 2;
		while (isset($usedIds[$id])) {
			$id = $base . "-" . $n;
			$n++;
		}
		if ($name === "") {
			$name = $id;
		}
		return array(
			"id" => $id,
			"name" => $name,
			"exam_codes" => rkrk_tr_parse_codes($raw["exam_codes"] ?? array()),
			"pilot_ids" => rkrk_tr_parse_ids($raw["pilot_ids"] ?? array()),
			"show_remark" => true,
			"remarks" => rkrk_tr_parse_remarks($raw["remarks"] ?? array()),
			"disabled" => rkrk_tr_parse_disabled($raw["disabled"] ?? array()),
		);
	}
}

if (!function_exists("rkrk_tr_config_load")) {
	/** @return array{lists: list<array<string,mixed>>} */
	function rkrk_tr_config_load(): array {
		$cfg = rkrk_tr_config_defaults();
		$path = rkrk_tr_config_path();
		if ($path === "" || !is_file($path)) {
			return $cfg;
		}
		$raw = @file_get_contents($path);
		$data = is_string($raw) ? @json_decode($raw, true) : null;
		if (!is_array($data) || !isset($data["lists"]) || !is_array($data["lists"])) {
			return $cfg;
		}
		$lists = array();
		$used = array();
		foreach ($data["lists"] as $item) {
			if (!is_array($item)) {
				continue;
			}
			$one = rkrk_tr_normalize_list($item, $used);
			$used[$one["id"]] = true;
			$lists[] = $one;
		}
		if ($lists !== array()) {
			$cfg["lists"] = $lists;
		}
		return $cfg;
	}
}

if (!function_exists("rkrk_tr_config_save")) {
	/** @param array<string,mixed> $cfg */
	function rkrk_tr_config_save(array $cfg): bool {
		$path = rkrk_tr_config_path();
		if ($path === "") {
			return false;
		}
		$dir = dirname($path);
		if ($dir !== "" && !is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$lists = array();
		$used = array();
		$src = isset($cfg["lists"]) && is_array($cfg["lists"]) ? $cfg["lists"] : array();
		$needSort = false;
		foreach ($src as $a) {
			if (is_array($a) && array_key_exists("ord", $a)) {
				$needSort = true;
				break;
			}
		}
		if ($needSort) {
			usort($src, static function ($a, $b): int {
				$oa = is_array($a) ? (int) ($a["ord"] ?? 0) : 0;
				$ob = is_array($b) ? (int) ($b["ord"] ?? 0) : 0;
				if ($oa === $ob) {
					return 0;
				}
				return $oa < $ob ? -1 : 1;
			});
		}
		$prevById = array();
		$prevCfg = rkrk_tr_config_load();
		foreach ((array) ($prevCfg["lists"] ?? array()) as $old) {
			if (is_array($old) && (string) ($old["id"] ?? "") !== "") {
				$prevById[(string) $old["id"]] = $old;
			}
		}
		foreach ($src as $item) {
			if (!is_array($item)) {
				continue;
			}
			if (!empty($item["delete"])) {
				continue;
			}
			$keepId = trim((string) ($item["id"] ?? ""));
			if ($keepId !== "" && isset($prevById[$keepId])) {
				if (!array_key_exists("remarks", $item)) {
					$item["remarks"] = $prevById[$keepId]["remarks"] ?? array();
				}
				if (!array_key_exists("disabled", $item)) {
					$item["disabled"] = $prevById[$keepId]["disabled"] ?? array();
				}
			}
			$one = rkrk_tr_normalize_list($item, $used);
			$used[$one["id"]] = true;
			$lists[] = $one;
		}
		$out = array("lists" => $lists);
		$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		return is_string($json) && @file_put_contents($path, $json) !== false;
	}
}

if (!function_exists("rkrk_tr_list_by_id")) {
	/** @return array<string,mixed>|null */
	function rkrk_tr_list_by_id(string $id, ?array $cfg = null): ?array {
		if ($cfg === null) {
			$cfg = rkrk_tr_config_load();
		}
		foreach ((array) ($cfg["lists"] ?? array()) as $item) {
			if (is_array($item) && (string) ($item["id"] ?? "") === $id) {
				return $item;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_tr_save_row")) {
	function rkrk_tr_save_row(string $listId, string $pilotId, string $text, ?bool $disabled = null): bool {
		$listId = trim($listId);
		$pilotId = trim($pilotId);
		if ($listId === "" || $pilotId === "") {
			return false;
		}
		$cfg = rkrk_tr_config_load();
		$lists = isset($cfg["lists"]) && is_array($cfg["lists"]) ? $cfg["lists"] : array();
		$found = false;
		foreach ($lists as $i => $item) {
			if (!is_array($item) || (string) ($item["id"] ?? "") !== $listId) {
				continue;
			}
			$remarks = rkrk_tr_parse_remarks($item["remarks"] ?? array());
			$key = "id:" . $pilotId;
			$text = trim($text);
			if ($text === "") {
				unset($remarks[$key]);
			} else {
				$remarks[$key] = $text;
			}
			$lists[$i]["remarks"] = $remarks;
			if ($disabled !== null) {
				$off = rkrk_tr_parse_disabled($item["disabled"] ?? array());
				if ($disabled) {
					$off[$key] = true;
				} else {
					unset($off[$key]);
				}
				$lists[$i]["disabled"] = $off;
			}
			$found = true;
			break;
		}
		if (!$found) {
			return false;
		}
		$cfg["lists"] = $lists;
		return rkrk_tr_config_save($cfg);
	}
}

if (!function_exists("rkrk_tr_save_remark")) {
	function rkrk_tr_save_remark(string $listId, string $pilotId, string $text): bool {
		return rkrk_tr_save_row($listId, $pilotId, $text, null);
	}
}

if (!function_exists("rkrk_tr_result_matches_code")) {
	function rkrk_tr_result_matches_code(array $res, string $want): bool {
		$want = rkrk_tr_norm_code($want);
		if ($want === "") {
			return false;
		}
		$exam = rkrk_tr_norm_code((string) ($res["exam_code"] ?? ""));
		$quiz = rkrk_tr_norm_code((string) ($res["quiz"] ?? ""));
		return ($exam !== "" && $exam === $want) || ($quiz !== "" && $quiz === $want);
	}
}

if (!function_exists("rkrk_tr_collect_results")) {
	/**
	 * Alle Theorie-/Quiz-Resultate (EXAM-Sessions + Quiz-CSV), nicht nur EP_xx.
	 *
	 * @return list<array{name:string,exam_code:string,date:?DateTimeImmutable,passed:bool,quiz:string,pct:float,anz:int,richtig:int,status:string}>
	 */
	function rkrk_tr_collect_results(): array {
		$out = array();
		if (function_exists("rkrk_assess_exam_list_completed")) {
			foreach (rkrk_assess_exam_list_completed() as $ex) {
				if (!is_array($ex)) {
					continue;
				}
				$code = rkrk_tr_norm_code((string) ($ex["exam_code"] ?? ""));
				$quiz = trim((string) ($ex["quiz_key"] ?? ""));
				if ($code === "" && $quiz === "") {
					continue;
				}
				$ts = (int) ($ex["timestamp"] ?? 0);
				$d = null;
				if ($ts > 0) {
					$d = (new DateTimeImmutable("@$ts"))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0);
				}
				if ($d === null && function_exists("rkrk_es_parse_date")) {
					$d = rkrk_es_parse_date((string) ($ex["completed_at"] ?? ($ex["zeit"] ?? "")));
				}
				$out[] = array(
					"name" => trim((string) ($ex["name"] ?? "")),
					"exam_code" => $code,
					"date" => $d,
					"passed" => !empty($ex["passed"]),
					"quiz" => $quiz,
					"pct" => (float) ($ex["pct"] ?? 0),
					"anz" => (int) ($ex["anz"] ?? 0),
					"richtig" => (int) ($ex["richtig"] ?? 0),
					"status" => "done",
				);
			}
		}
		if (function_exists("rkrk_assess_exam_list_in_progress")) {
			foreach (rkrk_assess_exam_list_in_progress() as $ex) {
				if (!is_array($ex)) {
					continue;
				}
				$code = rkrk_tr_norm_code((string) ($ex["exam_code"] ?? ""));
				$quiz = trim((string) ($ex["quiz_key"] ?? ""));
				if ($code === "" && $quiz === "") {
					continue;
				}
				$ts = (int) ($ex["timestamp"] ?? 0);
				$d = null;
				if ($ts > 0) {
					$d = (new DateTimeImmutable("@$ts"))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0);
				}
				$out[] = array(
					"name" => trim((string) ($ex["pilot"] ?? ($ex["name"] ?? ""))),
					"exam_code" => $code,
					"date" => $d,
					"passed" => false,
					"quiz" => $quiz,
					"pct" => 0.0,
					"anz" => 0,
					"richtig" => 0,
					"status" => "progress",
				);
			}
		}
		$resultsDir = isset($GLOBALS["QuizLocResults"]) ? rtrim((string) $GLOBALS["QuizLocResults"], "/\\") : "";
		if ($resultsDir !== "" && function_exists("rkrk_assess_quiz_results_load_rows")) {
			foreach (rkrk_assess_quiz_results_load_rows($resultsDir) as $row) {
				if (!is_array($row)) {
					continue;
				}
				$code = rkrk_tr_norm_code((string) ($row["EXAM"] ?? ($row["exam_code"] ?? "")));
				$quiz = trim((string) ($row["Quiz"] ?? ($row["quiz"] ?? "")));
				if ($code === "" && $quiz === "") {
					continue;
				}
				$metrics = function_exists("rkrk_assess_theory_result_metrics")
					? rkrk_assess_theory_result_metrics($row)
					: array("passed" => false, "pct" => 0, "anz" => 0, "richtig" => 0);
				$d = function_exists("rkrk_es_parse_date")
					? rkrk_es_parse_date((string) ($row["Zeit"] ?? ($row["zeit"] ?? "")))
					: null;
				$out[] = array(
					"name" => trim((string) ($row["Name"] ?? ($row["name"] ?? ""))),
					"exam_code" => $code,
					"date" => $d,
					"passed" => !empty($metrics["passed"]),
					"quiz" => $quiz,
					"pct" => (float) ($metrics["pct"] ?? 0),
					"anz" => (int) ($metrics["anz"] ?? 0),
					"richtig" => (int) ($metrics["richtig"] ?? 0),
					"status" => "done",
				);
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_tr_pick_cell")) {
	/**
	 * Bestes Resultat für einen EXAM-Code: abgeschlossenes (bestanden bevorzugt, sonst neuestes), sonst in Arbeit.
	 *
	 * @param list<array<string,mixed>> $hits
	 * @return array<string,mixed>|null
	 */
	function rkrk_tr_pick_cell(array $hits, string $code): ?array {
		$done = array();
		$prog = array();
		foreach ($hits as $h) {
			if (!rkrk_tr_result_matches_code($h, $code)) {
				continue;
			}
			if ((string) ($h["status"] ?? "") === "progress") {
				$prog[] = $h;
			} else {
				$done[] = $h;
			}
		}
		if ($done !== array() && function_exists("rkrk_es_pick_once_passed")) {
			return rkrk_es_pick_once_passed($done);
		}
		if ($done !== array() && function_exists("rkrk_es_pick_best")) {
			return rkrk_es_pick_best($done, true);
		}
		if ($done !== array()) {
			return $done[0];
		}
		if ($prog !== array() && function_exists("rkrk_es_pick_best")) {
			$best = rkrk_es_pick_best($prog, false);
			if (is_array($best)) {
				$best["status"] = "progress";
			}
			return $best;
		}
		return $prog !== array() ? $prog[0] : null;
	}
}

if (!function_exists("rkrk_tr_cell_tip")) {
	/** Hover-Text: Status + Resultat. */
	function rkrk_tr_cell_tip(?array $hit, string $code): string {
		$code = rkrk_tr_norm_code($code);
		if (!$hit) {
			return $code !== "" ? ("Offen — noch kein Resultat für " . $code) : "Offen — noch kein Resultat";
		}
		$status = (string) ($hit["status"] ?? "done");
		$quiz = trim((string) ($hit["quiz"] ?? ""));
		$exam = rkrk_tr_norm_code((string) ($hit["exam_code"] ?? ""));
		$dateStr = "";
		if (isset($hit["date"]) && $hit["date"] instanceof DateTimeImmutable && function_exists("rkrk_es_fmt_date")) {
			$dateStr = rkrk_es_fmt_date($hit["date"]);
		}
		if ($status === "progress") {
			$tip = "In Arbeit";
			if ($dateStr !== "") {
				$tip .= " seit " . $dateStr;
			}
		} elseif (!empty($hit["passed"])) {
			$tip = "Bestanden";
			if ($dateStr !== "") {
				$tip .= " am " . $dateStr;
			}
		} else {
			$tip = "Nicht bestanden";
			if ($dateStr !== "") {
				$tip .= " am " . $dateStr;
			}
		}
		if ($exam !== "") {
			$tip .= " · EXAM " . $exam;
		} elseif ($code !== "") {
			$tip .= " · EXAM " . $code;
		}
		if ($quiz !== "") {
			$tip .= " · Quiz " . $quiz;
		}
		$pct = (float) ($hit["pct"] ?? 0);
		$anz = (int) ($hit["anz"] ?? 0);
		$richtig = (int) ($hit["richtig"] ?? 0);
		if ($status !== "progress" && ($pct > 0 || $anz > 0)) {
			if ($anz > 0) {
				$tip .= " · Resultat " . rtrim(rtrim(number_format($pct, 1, ".", ""), "0"), ".") . "% (" . $richtig . "/" . $anz . ")";
			} else {
				$tip .= " · Resultat " . rtrim(rtrim(number_format($pct, 1, ".", ""), "0"), ".") . "%";
			}
		}
		return $tip;
	}
}

if (!function_exists("rkrk_tr_result_icon")) {
	function rkrk_tr_result_icon(?array $hit, string $code): string {
		$tip = rkrk_tr_cell_tip($hit, $code);
		$h = function_exists("rkrk_es_h") ? "rkrk_es_h" : "htmlspecialchars";
		$icoFn = function_exists("rkrk_es_ico") ? "rkrk_es_ico" : null;
		if (!$hit) {
			$ico = $icoFn ? $icoFn("open") : "?";
			return "<span class='es-mark es-miss' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
		}
		$status = (string) ($hit["status"] ?? "done");
		if ($status === "progress") {
			$ico = $icoFn ? $icoFn("warn") : "!";
			return "<span class='es-mark es-prog' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
		}
		if (!empty($hit["passed"])) {
			$ico = $icoFn ? $icoFn("ok") : "✓";
			return "<span class='es-mark es-ok' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
		}
		$ico = $icoFn ? $icoFn("no") : "×";
		return "<span class='es-mark es-bad' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
	}
}

if (!function_exists("rkrk_tr_overall_icon")) {
	/** @param array{passed:int,failed:int,open:int,progress:int,total:int} $sum */
	function rkrk_tr_overall_icon(array $sum): string {
		$total = (int) ($sum["total"] ?? 0);
		$passed = (int) ($sum["passed"] ?? 0);
		$failed = (int) ($sum["failed"] ?? 0);
		$open = (int) ($sum["open"] ?? 0);
		$progress = (int) ($sum["progress"] ?? 0);
		$h = function_exists("rkrk_es_h") ? "rkrk_es_h" : "htmlspecialchars";
		$icoFn = function_exists("rkrk_es_ico") ? "rkrk_es_ico" : null;
		if ($total <= 0) {
			$tip = "Keine EXAM-Codes in dieser Liste";
			$ico = $icoFn ? $icoFn("open") : "?";
			return "<span class='es-mark es-neutral' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
		}
		$tip = $passed . " bestanden · " . $failed . " nicht bestanden · " . $open . " offen";
		if ($progress > 0) {
			$tip .= " · " . $progress . " in Arbeit";
		}
		$tip .= " (von " . $total . ")";
		if ($failed > 0) {
			$cls = "es-bad";
			$ico = $icoFn ? $icoFn("no") : "×";
		} elseif ($open > 0 || $progress > 0) {
			$cls = ($progress > 0) ? "es-prog" : "es-miss";
			$ico = $icoFn ? $icoFn($progress > 0 ? "warn" : "open") : "?";
		} else {
			$cls = "es-ok";
			$ico = $icoFn ? $icoFn("ok") : "✓";
			$tip = "Alle " . $total . " Prüfungen bestanden";
		}
		return "<span class='es-mark " . $cls . "' title='" . $h($tip) . "' aria-label='" . $h($tip) . "'>" . $ico . "</span>";
	}
}

if (!function_exists("rkrk_tr_build_rows")) {
	/**
	 * @param array<string,mixed> $list
	 * @return list<array<string,mixed>>
	 */
	function rkrk_tr_build_rows(array $list): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$wanted = array();
		foreach (rkrk_tr_parse_ids($list["pilot_ids"] ?? array()) as $id) {
			$wanted["id:" . $id] = true;
		}
		$codes = rkrk_tr_parse_codes($list["exam_codes"] ?? array());
		$allResults = rkrk_tr_collect_results();
		$out = array();

		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			if ($wanted === array()) {
				continue;
			}
			$keys = rkrk_tr_pilot_keys_of($row);
			$selected = false;
			foreach ($keys as $k) {
				if (isset($wanted["id:" . $k])) {
					$selected = true;
					break;
				}
			}
			if (!$selected) {
				continue;
			}
			list($vorname, $nachname) = rkrk_tr_name_parts($row);
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim($vorname . " " . $nachname);
			if ($name === "") {
				continue;
			}
			$aliases = function_exists("rkrk_es_pilot_aliases")
				? rkrk_es_pilot_aliases($row, $name)
				: array(function_exists("rkrk_es_norm_name") ? rkrk_es_norm_name($name) : strtolower($name));
			$hits = array();
			foreach ($allResults as $t) {
				if (function_exists("rkrk_es_name_matches")) {
					if (rkrk_es_name_matches((string) ($t["name"] ?? ""), $aliases)) {
						$hits[] = $t;
					}
				} elseif (strcasecmp((string) ($t["name"] ?? ""), $name) === 0) {
					$hits[] = $t;
				}
			}
			$cells = array();
			$sum = array("passed" => 0, "failed" => 0, "open" => 0, "progress" => 0, "total" => count($codes));
			foreach ($codes as $code) {
				$hit = rkrk_tr_pick_cell($hits, $code);
				$st = "open";
				if ($hit) {
					if ((string) ($hit["status"] ?? "") === "progress") {
						$st = "progress";
						$sum["progress"]++;
					} elseif (!empty($hit["passed"])) {
						$st = "ok";
						$sum["passed"]++;
					} else {
						$st = "fail";
						$sum["failed"]++;
					}
				} else {
					$sum["open"]++;
				}
				$cells[$code] = array(
					"hit" => $hit,
					"status" => $st,
					"sort" => $st === "ok" ? "3" : ($st === "fail" ? "1" : ($st === "progress" ? "2" : "0")),
				);
			}
			$rowStatus = "ok";
			if ($sum["total"] <= 0) {
				$rowStatus = "empty";
			} elseif ($sum["failed"] > 0) {
				$rowStatus = "fail";
			} elseif ($sum["open"] > 0 || $sum["progress"] > 0) {
				$rowStatus = "open";
			}
			$remark = "";
			$remarks = rkrk_tr_parse_remarks($list["remarks"] ?? array());
			foreach ($keys as $k) {
				if (isset($remarks["id:" . $k])) {
					$remark = (string) $remarks["id:" . $k];
					break;
				}
			}
			$disabled = false;
			$off = rkrk_tr_parse_disabled($list["disabled"] ?? array());
			foreach ($keys as $k) {
				if (isset($off["id:" . $k])) {
					$disabled = true;
					break;
				}
			}
			$out[] = array(
				"id" => rkrk_tr_pilot_key($row),
				"pilot_id" => trim((string) ($row["pilot_id"] ?? "")),
				"rkrs_id" => function_exists("rkrk_assess_personel_row_effective_rkrs_id")
					? rkrk_assess_personel_row_effective_rkrs_id($row)
					: trim((string) ($row["rkrs_pilot_id"] ?? "")),
				"name" => $name,
				"vorname" => $vorname,
				"nachname" => $nachname,
				"rkrsplus" => function_exists("rkrk_assess_personel_cell_truthy")
					? rkrk_assess_personel_cell_truthy($row["rkrsplus"] ?? false)
					: false,
				"cells" => $cells,
				"sum" => $sum,
				"status" => $rowStatus,
				"remark" => $remark,
				"disabled" => $disabled,
			);
		}

		return rkrk_tr_sort_rows($out, "nach");
	}
}

if (!function_exists("rkrk_tr_all_pilots_for_select")) {
	/** @return list<array{key:string,name:string,rkrs:string}> */
	function rkrk_tr_all_pilots_for_select(): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$out = array();
		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			list($vorname, $nachname) = rkrk_tr_name_parts($row);
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim($vorname . " " . $nachname);
			if ($name === "") {
				continue;
			}
			$out[] = array(
				"key" => rkrk_tr_pilot_key($row),
				"name" => $name,
				"vorname" => $vorname,
				"nachname" => $nachname,
				"rkrs" => function_exists("rkrk_assess_personel_row_effective_rkrs_id")
					? rkrk_assess_personel_row_effective_rkrs_id($row)
					: trim((string) ($row["rkrs_pilot_id"] ?? "")),
			);
		}
		return rkrk_tr_sort_rows($out, "nach");
	}
}
