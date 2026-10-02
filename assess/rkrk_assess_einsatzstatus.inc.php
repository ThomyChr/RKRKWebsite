<?php
/**
 * Einsatzberechtigung: Personal + Theorie (EXAM-Codes) + Material/Praxis + Lizenz.
 * Material und Praxis haben kein Ablaufdatum.
 * Theorie: 2 Jahre · Fernpilotenlizenz: 5 Jahre (Feld «gültig bis»).
 */

if (!function_exists("rkrk_es_h")) {
	function rkrk_es_h($v): string {
		return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists("rkrk_es_page_href")) {
	/** URL zur Einsatzstatus-Seite inkl. Setting- und Personal-cs-Parametern. */
	function rkrk_es_page_href(array $query = array()): string {
		if (function_exists("rkrk_assess_personel_merge_cs_query")) {
			$query = rkrk_assess_personel_merge_cs_query($query);
		}
		foreach (array("ADMIN", "INSTR", "NOH") as $flag) {
			if (array_key_exists($flag, $_GET) && !array_key_exists($flag, $query)) {
				$query[$flag] = (string) $_GET[$flag];
			}
		}
		$path = "rkrk_assess_einsatzstatus.php";
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

if (!function_exists("rkrk_es_exp_level")) {
	/** Stufe 1–6 aus Maturität (erste_erfahrung), sonst 0 — wie Personalliste. */
	function rkrk_es_exp_level(string $raw): int {
		if (function_exists("rkrk_live_personel_exp_level")) {
			return rkrk_live_personel_exp_level($raw);
		}
		$s = trim($raw);
		if ($s === "") {
			return 0;
		}
		if (preg_match('/^([1-6])\b/u', $s, $m)) {
			return (int) $m[1];
		}
		$n = (int) $s;
		return ($n >= 1 && $n <= 6) ? $n : 0;
	}
}

if (!function_exists("rkrk_es_norm_name")) {
	function rkrk_es_norm_name(string $name): string {
		$s = trim($name);
		if ($s === "") {
			return "";
		}
		if (function_exists("mb_strtolower")) {
			$s = mb_strtolower($s, "UTF-8");
		} else {
			$s = strtolower($s);
		}
		$s = strtr($s, array(
			"ä" => "ae", "ö" => "oe", "ü" => "ue", "ß" => "ss",
			"à" => "a", "é" => "e", "è" => "e",
		));
		$s = preg_replace("/[^a-z0-9]+/u", " ", $s);
		return trim((string) preg_replace("/\s+/", " ", (string) $s));
	}
}

if (!function_exists("rkrk_es_parse_date")) {
	function rkrk_es_parse_date(string $raw): ?DateTimeImmutable {
		$s = trim($raw);
		if ($s === "") {
			return null;
		}
		$s = str_replace("T", " ", $s);
		foreach (array("Y-m-d", "Y-m-d H:i", "Y-m-d H:i:s", "d.m.Y", "d.m.Y H:i", "d.m.Y H:i:s") as $fmt) {
			$d = DateTimeImmutable::createFromFormat($fmt, $s);
			if ($d instanceof DateTimeImmutable) {
				return $d->setTime(0, 0, 0);
			}
		}
		$ts = strtotime($s);
		if ($ts !== false && $ts > 0) {
			return (new DateTimeImmutable("@$ts"))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0);
		}
		return null;
	}
}

if (!function_exists("rkrk_es_fmt_date")) {
	function rkrk_es_fmt_date(?DateTimeImmutable $d): string {
		return $d ? $d->format("d.m.Y") : "";
	}
}

if (!function_exists("rkrk_es_is_exam_code")) {
	function rkrk_es_is_exam_code(string $code): bool {
		return preg_match('/^EP[_-]?\d{2}$/i', trim($code)) === 1;
	}
}

if (!function_exists("rkrk_es_norm_exam_code")) {
	function rkrk_es_norm_exam_code(string $code): string {
		$code = strtoupper(trim($code));
		if (preg_match('/^EP[_-]?(\d{2})$/', $code, $m)) {
			return "EP_" . $m[1];
		}
		return $code;
	}
}

if (!function_exists("rkrk_es_is_theory_quiz")) {
	function rkrk_es_is_theory_quiz(string $quizKey): bool {
		$h = rkrk_es_norm_name($quizKey);
		if ($h === "") {
			return false;
		}
		if (strpos($h, "rkrsp ep") !== false || strpos($h, "rkrsp-ep") !== false) {
			return true;
		}
		return (strpos($h, "einsatzpr") !== false && strpos($h, "material") === false);
	}
}

if (!function_exists("rkrk_es_verdict_passed")) {
	function rkrk_es_verdict_passed(string $raw): ?bool {
		$vl = rkrk_es_norm_name($raw);
		if ($vl === "") {
			return null;
		}
		$vl = trim((string) preg_replace("/\s+/", " ", preg_replace("/\b[gorn]\b/", "", $vl)));
		if ($vl === "") {
			return null;
		}
		if (
			$vl === "ja" || $vl === "bestanden" || $vl === "pass" || $vl === "ok"
			|| (strpos($vl, "bestanden") !== false && strpos($vl, "nicht") === false)
			|| (strpos($vl, "als pilot einsetzbar") !== false && strpos($vl, "nicht") === false && strpos($vl, "einschraenk") === false)
			|| strpos($vl, "material komplett") !== false
		) {
			return true;
		}
		if (
			$vl === "nein" || $vl === "nicht bestanden" || $vl === "fail"
			|| (strpos($vl, "nicht") !== false && strpos($vl, "bestanden") !== false)
			|| strpos($vl, "nicht einsatzbereit") !== false
			|| strpos($vl, "material nicht") !== false
		) {
			return false;
		}
		return null;
	}
}

if (!function_exists("rkrk_es_pilot_aliases")) {
	/** @return list<string> */
	function rkrk_es_pilot_aliases(array $row, string $display): array {
		$out = array();
		$add = static function (string $s) use (&$out): void {
			$n = rkrk_es_norm_name($s);
			if ($n !== "") {
				// Prefix keeps numeric ids (rkrs_pilot_id) as string keys; PHP
				// would otherwise cast "40" → int 40 and strpos() would warn.
				$out["k:" . $n] = $n;
			}
		};
		$add($display);
		$vn = trim((string) ($row["vorname"] ?? ""));
		$nn = trim((string) ($row["nachname"] ?? ""));
		if ($vn !== "" && $nn !== "") {
			$add($nn . " " . $vn);
			$add($vn . " " . $nn);
		}
		$add((string) ($row["rkrs_pilot_id"] ?? ""));
		$add((string) ($row["name"] ?? ""));
		return array_values($out);
	}
}

if (!function_exists("rkrk_es_name_matches")) {
	/** @param list<string> $aliases */
	function rkrk_es_name_matches(string $hay, array $aliases): bool {
		$n = rkrk_es_norm_name($hay);
		if ($n === "") {
			return false;
		}
		foreach ($aliases as $a) {
			$a = (string) $a;
			if ($a === "") {
				continue;
			}
			if ($a === $n) {
				return true;
			}
			if (strlen($a) >= 4 && (strpos($n, $a) !== false || strpos($a, $n) !== false)) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists("rkrk_es_collect_theory")) {
	/** @return list<array{name:string,exam_code:string,date:?DateTimeImmutable,passed:bool,quiz:string,pct:float}> */
	function rkrk_es_collect_theory(): array {
		$out = array();
		if (function_exists("rkrk_assess_exam_list_completed")) {
			foreach (rkrk_assess_exam_list_completed() as $ex) {
				if (!is_array($ex)) {
					continue;
				}
				$code = rkrk_es_norm_exam_code((string) ($ex["exam_code"] ?? ""));
				$quiz = trim((string) ($ex["quiz_key"] ?? ""));
				if ($code === "" && !rkrk_es_is_theory_quiz($quiz)) {
					continue;
				}
				if ($code !== "" && !rkrk_es_is_exam_code($code) && !rkrk_es_is_theory_quiz($quiz)) {
					continue;
				}
				$ts = (int) ($ex["timestamp"] ?? 0);
				$d = $ts > 0 ? (new DateTimeImmutable("@$ts"))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0) : null;
				if ($d === null) {
					$d = rkrk_es_parse_date((string) ($ex["completed_at"] ?? ($ex["zeit"] ?? "")));
				}
				$out[] = array(
					"name" => trim((string) ($ex["name"] ?? "")),
					"exam_code" => rkrk_es_is_exam_code($code) ? $code : "",
					"date" => $d,
					"passed" => !empty($ex["passed"]),
					"quiz" => $quiz,
					"pct" => (float) ($ex["pct"] ?? 0),
				);
			}
		}
		$resultsDir = isset($GLOBALS["QuizLocResults"]) ? rtrim((string) $GLOBALS["QuizLocResults"], "/\\") : "";
		if ($resultsDir !== "" && function_exists("rkrk_assess_quiz_results_load_rows")) {
			foreach (rkrk_assess_quiz_results_load_rows($resultsDir) as $row) {
				if (!is_array($row)) {
					continue;
				}
				$code = rkrk_es_norm_exam_code((string) ($row["EXAM"] ?? ($row["exam_code"] ?? "")));
				$quiz = trim((string) ($row["Quiz"] ?? ($row["quiz"] ?? "")));
				if (!rkrk_es_is_exam_code($code) && !rkrk_es_is_theory_quiz($quiz)) {
					continue;
				}
				$metrics = function_exists("rkrk_assess_theory_result_metrics")
					? rkrk_assess_theory_result_metrics($row)
					: array("passed" => false, "pct" => 0);
				$d = rkrk_es_parse_date((string) ($row["Zeit"] ?? ($row["zeit"] ?? "")));
				$out[] = array(
					"name" => trim((string) ($row["Name"] ?? ($row["name"] ?? ""))),
					"exam_code" => rkrk_es_is_exam_code($code) ? $code : "",
					"date" => $d,
					"passed" => !empty($metrics["passed"]),
					"quiz" => $quiz,
					"pct" => (float) ($metrics["pct"] ?? 0),
				);
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_es_tpl_stem")) {
	function rkrk_es_tpl_stem(string $tpl): string {
		$b = strtolower(basename(str_replace("\\", "/", trim($tpl))));
		if ($b !== "" && substr($b, -5) === ".json") {
			$b = substr($b, 0, -5);
		}
		return $b;
	}
}

if (!function_exists("rkrk_es_tpl_equals")) {
	function rkrk_es_tpl_equals(string $got, string $want): bool {
		$a = rkrk_es_tpl_stem($got);
		$b = rkrk_es_tpl_stem($want);
		return $a !== "" && $a === $b;
	}
}

if (!function_exists("rkrk_es_cfg_tpl_list")) {
	/** @return list<string> */
	function rkrk_es_cfg_tpl_list(array $cfg, string $kind): array {
		$key = $kind === "material" ? "material_templates" : "praxis_templates";
		$single = $kind === "material" ? "material_template" : "praxis_template";
		$src = $cfg[$key] ?? ($cfg[$single] ?? array());
		if (is_string($src)) {
			$src = array($src);
		}
		$out = array();
		if (is_array($src)) {
			foreach ($src as $t) {
				$b = basename(str_replace("\\", "/", trim((string) $t)));
				if ($b !== "") {
					$out[$b] = true;
				}
			}
		}
		return array_keys($out);
	}
}

if (!function_exists("rkrk_es_checklist_kind")) {
	function rkrk_es_checklist_kind(string $tpl, array $cfg = array()): string {
		foreach (rkrk_es_cfg_tpl_list($cfg, "material") as $mat) {
			if (rkrk_es_tpl_equals($tpl, $mat)) {
				return "material";
			}
		}
		foreach (rkrk_es_cfg_tpl_list($cfg, "praxis") as $pra) {
			if (rkrk_es_tpl_equals($tpl, $pra)) {
				return "praxis";
			}
		}
		$h = rkrk_es_norm_name($tpl);
		if ($h === "") {
			return "";
		}
		if (strpos($h, "material") !== false) {
			return "material";
		}
		if (strpos($h, "einsatzpr") !== false || strpos($h, "praxis") !== false) {
			return "praxis";
		}
		return "";
	}
}

if (!function_exists("rkrk_es_checklist_dirs")) {
	/** @return list<string> */
	function rkrk_es_checklist_dirs(): array {
		$dirs = array();
		foreach (array("rkrkAssessResults", "rkrkLiveassessResults") as $gk) {
			$d = isset($GLOBALS[$gk]) ? rtrim(str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, (string) $GLOBALS[$gk]), DIRECTORY_SEPARATOR) : "";
			if ($d !== "" && is_dir($d)) {
				$dirs[$d] = true;
			}
		}
		return array_keys($dirs);
	}
}

if (!function_exists("rkrk_es_list_templates")) {
	/** @return list<string> */
	function rkrk_es_list_templates(): array {
		$out = array();
		foreach (array("rkrkAssessTemplates", "rkrkLiveassessTemplates") as $gk) {
			$d = isset($GLOBALS[$gk]) ? rtrim(str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, (string) $GLOBALS[$gk]), DIRECTORY_SEPARATOR) : "";
			if ($d === "" || !is_dir($d)) {
				continue;
			}
			foreach (glob($d . DIRECTORY_SEPARATOR . "*.json") ?: array() as $f) {
				$out[basename($f)] = true;
			}
		}
		$keys = array_keys($out);
		natcasesort($keys);
		return array_values($keys);
	}
}

if (!function_exists("rkrk_es_collect_checklists")) {
	/** @return list<array{kind:string,name:string,date:?DateTimeImmutable,passed:?bool,label:string,tpl:string}> */
	function rkrk_es_collect_checklists(?array $cfg = null): array {
		$cfg = $cfg !== null ? $cfg : rkrk_es_config_load();
		$out = array();
		foreach (rkrk_es_checklist_dirs() as $dir) {
		foreach (glob($dir . DIRECTORY_SEPARATOR . "*.json") ?: array() as $file) {
			$raw = @file_get_contents($file);
			if (!is_string($raw) || $raw === "") {
				continue;
			}
			$data = @json_decode($raw, true);
			if (!is_array($data)) {
				continue;
			}
			$tpl = (string) ($data["template"] ?? ($data["file"] ?? ""));
			$kind = rkrk_es_checklist_kind($tpl, $cfg);
			if ($kind === "") {
				continue;
			}
			$fields = isset($data["fields"]) && is_array($data["fields"]) ? $data["fields"] : array();
			$name = "";
			$pilotId = "";
			if (function_exists("rkrk_assess_fields_pilot_name_and_id")) {
				$pi = rkrk_assess_fields_pilot_name_and_id($fields);
				$name = trim((string) ($pi["name"] ?? ""));
				$pilotId = trim((string) ($pi["id"] ?? ""));
			}
			$allg = isset($fields["Allgemein"]) && is_array($fields["Allgemein"]) ? $fields["Allgemein"] : array();
			if ($name === "") {
				$pilotRaw = $allg["Pilot"] ?? ($allg["Piloten"] ?? ($allg["Name"] ?? ""));
				$name = is_array($pilotRaw) ? implode(" ", $pilotRaw) : trim((string) $pilotRaw);
			}
			if ($pilotId !== "") {
				$name = trim($name . " " . $pilotId);
			}
			$dateRaw = (string) ($allg["Prüfungsdatum"] ?? ($allg["Pruefungsdatum"] ?? ($allg["Flugzeit"] ?? ($allg["Datum & Zeit"] ?? ""))));
			if ($dateRaw === "") {
				foreach ($fields as $sec) {
					if (!is_array($sec)) {
						continue;
					}
					foreach (array("Prüfungsdatum", "Pruefungsdatum", "Flugzeit", "Datum & Zeit") as $lab) {
						if (isset($sec[$lab]) && trim((string) $sec[$lab]) !== "") {
							$dateRaw = (string) $sec[$lab];
							break 2;
						}
					}
				}
			}
			$d = rkrk_es_parse_date($dateRaw);
			if ($d === null) {
				$mt = @filemtime($file);
				if ($mt) {
					$d = (new DateTimeImmutable("@$mt"))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0);
				}
			}
			$verdict = "";
			foreach ($fields as $sec) {
				if (!is_array($sec)) {
					continue;
				}
				foreach ($sec as $lab => $val) {
					$labS = (string) $lab;
					$hit = (
						stripos($labS, "Gesamtentscheid") !== false
						|| stripos($labS, "Bestehensentscheid") !== false
						|| preg_match('/^Entscheid$/ui', $labS) === 1
						|| stripos($labS, "Bestanden") !== false
						|| preg_match('/^Pflichtmaterial$/ui', $labS) === 1
						|| stripos($labS, "Einsatz Empfehlung") !== false
						|| stripos($labS, "Einsatzempfehlung") !== false
						|| preg_match('/^Ergebnis$/ui', $labS) === 1
					);
					if (!$hit) {
						continue;
					}
					$v = is_array($val) ? implode(", ", $val) : trim((string) $val);
					if ($v === "") {
						continue;
					}
					$verdict = $v;
					if (stripos($labS, "Gesamtentscheid") !== false || stripos($labS, "Bestanden") !== false) {
						break 2;
					}
				}
			}
			$out[] = array(
				"kind" => $kind,
				"name" => $name,
				"date" => $d,
				"passed" => rkrk_es_verdict_passed($verdict),
				"label" => $verdict,
				"tpl" => $tpl,
			);
		}
		}
		return $out;
	}
}

if (!function_exists("rkrk_es_renew_tip")) {
	function rkrk_es_renew_tip(string $label, ?DateTimeImmutable $due, string $cls = ""): string {
		if ($cls === "assumed") {
			return $label . ": noch nicht abgefragt — bis Sept. 2027 als gültig angenommen";
		}
		if (!$due) {
			return $label . ": nächste Erneuerung nicht hinterlegt";
		}
		$d = rkrk_es_fmt_date($due);
		if ($cls === "expired") {
			return $label . ": Erneuerung überfällig seit " . $d;
		}
		if ($cls === "soon") {
			return $label . ": nächste Erneuerung demnächst fällig am " . $d;
		}
		return $label . ": nächste Erneuerung fällig am " . $d;
	}
}

if (!function_exists("rkrk_es_pick_best")) {
	/**
	 * @param list<array<string,mixed>> $hits
	 * @return array<string,mixed>|null
	 */
	function rkrk_es_pick_best(array $hits, bool $preferPassed = false): ?array {
		if ($hits === array()) {
			return null;
		}
		$best = null;
		foreach ($hits as $h) {
			if ($best === null) {
				$best = $h;
				continue;
			}
			$bd = $best["date"] instanceof DateTimeImmutable ? $best["date"]->getTimestamp() : 0;
			$hd = $h["date"] instanceof DateTimeImmutable ? $h["date"]->getTimestamp() : 0;
			if ($hd > $bd) {
				$best = $h;
				continue;
			}
			if ($hd === $bd && $preferPassed && !empty($h["passed"]) && empty($best["passed"])) {
				$best = $h;
			}
		}
		return $best;
	}
}

if (!function_exists("rkrk_es_pick_once_passed")) {
	/**
	 * Material/Praxis: einmal bestanden bleibt bestanden (kein Ablauf).
	 * Sonst neuestes Resultat (für Hover bei nicht bestanden).
	 *
	 * @param list<array<string,mixed>> $hits
	 * @return array<string,mixed>|null
	 */
	function rkrk_es_pick_once_passed(array $hits): ?array {
		$passed = array();
		foreach ($hits as $h) {
			if (!empty($h["passed"])) {
				$passed[] = $h;
			}
		}
		if ($passed !== array()) {
			return rkrk_es_pick_best($passed, true);
		}
		return rkrk_es_pick_best($hits, false);
	}
}

if (!function_exists("rkrk_es_config_defaults")) {
	/** @return array<string, mixed> */
	function rkrk_es_config_defaults(): array {
		return array(
			"theorie_jahre" => 2,
			"lizenz_jahre" => 5,
			"warn_tage" => 90,
			"exam_codes" => array("EP_26", "EP_27", "EP_28"),
			"braucht_theorie" => true,
			"braucht_lizenz" => true,
			"braucht_material" => true,
			"braucht_praxis" => true,
			"material_template" => "einsatzpruefung_rkrs_materialliste.json",
			"praxis_template" => "einsatzpruefung_rkrs.json",
			"material_templates" => array("einsatzpruefung_rkrs_materialliste.json"),
			"praxis_templates" => array("einsatzpruefung_rkrs.json"),
			"pflicht_ab" => "2027-09-01",
		);
	}
}

if (!function_exists("rkrk_es_config_path")) {
	function rkrk_es_config_path(): string {
		$p = function_exists("rkrk_assess_personel_get_file_path") ? rkrk_assess_personel_get_file_path() : null;
		if ($p) {
			return dirname($p) . DIRECTORY_SEPARATOR . "einsatzstatus_config.json";
		}
		$root = isset($GLOBALS["rkrkLivePersonelRoot"]) ? rtrim((string) $GLOBALS["rkrkLivePersonelRoot"], "/\\") : "";
		return $root !== "" ? $root . DIRECTORY_SEPARATOR . "einsatzstatus_config.json" : "";
	}
}

if (!function_exists("rkrk_es_config_load")) {
	/** @return array<string, mixed> */
	function rkrk_es_config_load(): array {
		$cfg = rkrk_es_config_defaults();
		$path = rkrk_es_config_path();
		if ($path === "" || !is_file($path)) {
			return $cfg;
		}
		$raw = @file_get_contents($path);
		$data = is_string($raw) ? @json_decode($raw, true) : null;
		if (!is_array($data)) {
			return $cfg;
		}
		foreach (array("theorie_jahre", "lizenz_jahre", "warn_tage") as $k) {
			if (isset($data[$k]) && is_numeric($data[$k])) {
				$cfg[$k] = max(0, (int) $data[$k]);
			}
		}
		foreach (array("braucht_theorie", "braucht_lizenz", "braucht_material", "braucht_praxis") as $k) {
			if (array_key_exists($k, $data)) {
				$cfg[$k] = !empty($data[$k]) && $data[$k] !== "0" && $data[$k] !== "false";
			}
		}
		foreach (array("material_template" => "material_templates", "praxis_template" => "praxis_templates") as $single => $multi) {
			$list = array();
			if (isset($data[$multi])) {
				$src = $data[$multi];
				if (is_string($src)) {
					$src = preg_split("/[\s,;]+/", $src) ?: array();
				}
				if (is_array($src)) {
					foreach ($src as $t) {
						$b = basename(str_replace("\\", "/", trim((string) $t)));
						if ($b !== "") {
							$list[$b] = true;
						}
					}
				}
			}
			if (isset($data[$single]) && is_string($data[$single]) && trim($data[$single]) !== "") {
				$b = basename(str_replace("\\", "/", trim($data[$single])));
				if ($b !== "") {
					$list[$b] = true;
				}
			}
			if ($list !== array()) {
				$cfg[$multi] = array_keys($list);
				$cfg[$single] = array_keys($list)[0];
			}
		}
		if (isset($data["pflicht_ab"])) {
			$pd = function_exists("rkrk_es_parse_date")
				? rkrk_es_parse_date((string) $data["pflicht_ab"])
				: null;
			if ($pd instanceof DateTimeImmutable) {
				$cfg["pflicht_ab"] = $pd->format("Y-m-d");
			}
		}
		if (isset($data["exam_codes"])) {
			$codes = array();
			$src = $data["exam_codes"];
			if (is_string($src)) {
				$src = preg_split("/[\s,;]+/", $src) ?: array();
			}
			if (is_array($src)) {
				foreach ($src as $c) {
					$n = rkrk_es_norm_exam_code((string) $c);
					if ($n !== "") {
						$codes[$n] = true;
					}
				}
			}
			if ($codes !== array()) {
				$cfg["exam_codes"] = array_keys($codes);
			}
		}
		return $cfg;
	}
}

if (!function_exists("rkrk_es_config_save")) {
	/** @param array<string, mixed> $cfg */
	function rkrk_es_config_save(array $cfg): bool {
		$path = rkrk_es_config_path();
		if ($path === "") {
			return false;
		}
		$dir = dirname($path);
		if ($dir !== "" && !is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$out = rkrk_es_config_defaults();
		$out["theorie_jahre"] = max(0, (int) ($cfg["theorie_jahre"] ?? 2));
		$out["lizenz_jahre"] = max(0, (int) ($cfg["lizenz_jahre"] ?? 5));
		$out["warn_tage"] = max(0, (int) ($cfg["warn_tage"] ?? 90));
		$out["braucht_theorie"] = !empty($cfg["braucht_theorie"]);
		$out["braucht_lizenz"] = !empty($cfg["braucht_lizenz"]);
		$out["braucht_material"] = !empty($cfg["braucht_material"]);
		$out["braucht_praxis"] = !empty($cfg["braucht_praxis"]);
		$pd = function_exists("rkrk_es_parse_date")
			? rkrk_es_parse_date((string) ($cfg["pflicht_ab"] ?? "2027-09-01"))
			: null;
		$out["pflicht_ab"] = ($pd instanceof DateTimeImmutable) ? $pd->format("Y-m-d") : "2027-09-01";
		$codes = array();
		$rawCodes = $cfg["exam_codes"] ?? array();
		if (is_string($rawCodes)) {
			$rawCodes = preg_split("/[\s,;]+/", $rawCodes) ?: array();
		}
		if (is_array($rawCodes)) {
			foreach ($rawCodes as $c) {
				$n = rkrk_es_norm_exam_code((string) $c);
				if ($n !== "") {
					$codes[$n] = true;
				}
			}
		}
		$out["exam_codes"] = array_keys($codes);
		foreach (array("material_templates" => "material_template", "praxis_templates" => "praxis_template") as $multi => $single) {
			$list = array();
			$src = $cfg[$multi] ?? ($cfg[$single] ?? array());
			if (is_string($src)) {
				$src = preg_split("/[\s,;]+/", $src) ?: array();
			}
			if (is_array($src)) {
				foreach ($src as $t) {
					$b = basename(str_replace("\\", "/", trim((string) $t)));
					if ($b !== "") {
						$list[$b] = true;
					}
				}
			}
			$out[$multi] = array_keys($list);
			$out[$single] = $out[$multi] !== array() ? $out[$multi][0] : "";
		}
		$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		return is_string($json) && @file_put_contents($path, $json) !== false;
	}
}

if (!function_exists("rkrk_es_pflicht_cutoff")) {
	function rkrk_es_pflicht_cutoff(?array $cfg = null): DateTimeImmutable {
		if ($cfg === null && function_exists("rkrk_es_config_load")) {
			$cfg = rkrk_es_config_load();
		}
		$raw = trim((string) (($cfg["pflicht_ab"] ?? "") ?: "2027-09-01"));
		$d = DateTimeImmutable::createFromFormat("Y-m-d", $raw);
		if (!$d instanceof DateTimeImmutable) {
			$d = new DateTimeImmutable("2027-09-01");
		}
		return $d->setTime(0, 0, 0);
	}
}

if (!function_exists("rkrk_es_due_class")) {
	function rkrk_es_due_class(?DateTimeImmutable $due, DateTimeImmutable $today, int $warnDays = 90): string {
		if (!$due) {
			return "missing";
		}
		if ($due < $today) {
			return "expired";
		}
		$warn = $today->modify("+" . $warnDays . " days");
		if ($due <= $warn) {
			return "soon";
		}
		return "ok";
	}
}

if (!function_exists("rkrk_es_build_rows")) {
	/**
	 * @return list<array<string,mixed>>
	 */
	function rkrk_es_build_rows(): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$cfg = rkrk_es_config_load();
		$theoryYears = max(0, (int) ($cfg["theorie_jahre"] ?? 2));
		$warnDays = max(0, (int) ($cfg["warn_tage"] ?? 90));
		$theoryAll = rkrk_es_collect_theory();
		$checks = rkrk_es_collect_checklists($cfg);
		$today = (new DateTimeImmutable("today"))->setTime(0, 0, 0);
		$out = array();

		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim((string) ($row["vorname"] ?? "") . " " . (string) ($row["nachname"] ?? ""));
			if ($name === "") {
				continue;
			}
			$aliases = rkrk_es_pilot_aliases($row, $name);
			$thHits = array();
			foreach ($theoryAll as $t) {
				if (rkrk_es_name_matches((string) $t["name"], $aliases)) {
					$thHits[] = $t;
				}
			}
			$matHits = array();
			$praHits = array();
			foreach ($checks as $c) {
				if (!rkrk_es_name_matches((string) $c["name"], $aliases)) {
					continue;
				}
				if ($c["kind"] === "material") {
					$matHits[] = $c;
				} elseif ($c["kind"] === "praxis") {
					$praHits[] = $c;
				}
			}
			$theory = rkrk_es_pick_best($thHits, false);
			$material = rkrk_es_pick_once_passed($matHits);
			$praxis = rkrk_es_pick_once_passed($praHits);

			$epFallback = rkrk_es_parse_date((string) ($row["einsatzpruefung_rkrsplus"] ?? ""));
			if ($theory === null && $epFallback) {
				$theory = array(
					"name" => $name,
					"exam_code" => "",
					"date" => $epFallback,
					"passed" => true,
					"quiz" => "",
					"pct" => 0,
				);
			}

			$theoryDate = ($theory && ($theory["date"] instanceof DateTimeImmutable)) ? $theory["date"] : null;
			$manualCode = rkrk_es_norm_exam_code((string) ($row["theorie_exam_code"] ?? ""));
			if ($manualCode !== "" && !rkrk_es_is_exam_code($manualCode)) {
				$manualCode = trim((string) ($row["theorie_exam_code"] ?? ""));
			}
			$manualDue = rkrk_es_parse_date((string) ($row["theorie_gueltig"] ?? ""));
			$examCode = $manualCode !== ""
				? $manualCode
				: (is_array($theory) ? (string) ($theory["exam_code"] ?? "") : "");
			$theoryDue = $manualDue ?: ($theoryDate ? $theoryDate->modify("+" . $theoryYears . " years") : null);
			if (is_array($theory) && $examCode !== "") {
				$theory["exam_code"] = $examCode;
			} elseif ($examCode !== "") {
				$theory = array(
					"name" => $name,
					"exam_code" => $examCode,
					"date" => $theoryDate,
					"passed" => true,
					"quiz" => "",
					"pct" => 0,
				);
			}
			$license = rkrk_es_parse_date((string) ($row["fpl_gueltig"] ?? ""));
			$suspended = function_exists("rkrk_assess_personel_cell_truthy")
				? rkrk_assess_personel_cell_truthy($row["suspendiert"] ?? false)
				: false;
			$theoryPassed = ($theory && !empty($theory["passed"])) || $manualDue || $manualCode !== "";
			$theoryOk = $theoryPassed && $theoryDue && $theoryDue >= $today;
			$pflichtAb = rkrk_es_pflicht_cutoff($cfg);
			$inGrace = $today < $pflichtAb;
			$licenseAssumed = ($license === null && $inGrace);
			$licenseOk = ($license && $license >= $today) || $licenseAssumed;
			$epPlus = rkrk_es_parse_date((string) ($row["einsatzpruefung_rkrsplus"] ?? ""));
			if ((!$material || $material["passed"] !== true) && $epPlus) {
				$material = array(
					"name" => $name,
					"date" => $epPlus,
					"passed" => true,
					"label" => "Einsatzprüfung RKRS+ (Personal)",
					"tpl" => "einsatzpruefung_rkrsplus",
				);
			}
			if ((!$praxis || $praxis["passed"] !== true) && $epPlus) {
				$praxis = array(
					"name" => $name,
					"date" => $epPlus,
					"passed" => true,
					"label" => "Einsatzprüfung RKRS+ (Personal)",
					"tpl" => "einsatzpruefung_rkrsplus",
				);
			}
			$materialOk = $material && $material["passed"] === true;
			$praxisOk = $praxis && $praxis["passed"] === true;
			$finalOk = !$suspended && $materialOk && $praxisOk && $theoryOk && $licenseOk;
			$graceFly = $inGrace && !$suspended && $licenseOk;
			$berechtigt = $finalOk || $graceFly;

			$status = "nicht_berechtigt";
			$mark = "no";
			if ($suspended) {
				$status = "suspendiert";
				$mark = "ban";
			} elseif ($finalOk) {
				$status = "berechtigt";
				$mark = "ok";
			} elseif ($graceFly) {
				$status = "neutral";
				$mark = "neutral";
			} elseif (!$inGrace) {
				$status = "gesperrt";
				$mark = "ban";
			}

			$bemerkung = trim((string) ($row["einsatz_bemerkung"] ?? ""));
			if ($bemerkung === "") {
				$bemerkung = trim((string) ($row["bemerkung"] ?? ($row["note"] ?? ($row["kommentar"] ?? ""))));
			}

			$out[] = array(
				"id" => trim((string) ($row["id"] ?? "")),
				"pilot_id" => trim((string) ($row["pilot_id"] ?? "")),
				"rkrs_id" => function_exists("rkrk_assess_personel_row_effective_rkrs_id")
					? rkrk_assess_personel_row_effective_rkrs_id($row)
					: trim((string) ($row["rkrs_pilot_id"] ?? "")),
				"name" => $name,
				"maturitaet" => trim((string) ($row["erste_erfahrung"] ?? "")),
				"rkrsplus" => function_exists("rkrk_assess_personel_cell_truthy")
					? rkrk_assess_personel_cell_truthy($row["rkrsplus"] ?? false)
					: false,
				"instruktor" => function_exists("rkrk_assess_personel_row_instruktor_flag")
					? rkrk_assess_personel_row_instruktor_flag($row)
					: false,
				"drone" => trim((string) ($row["drohnentyp"] ?? "")),
				"license" => $license,
				"license_assumed" => $licenseAssumed,
				"license_cls" => $licenseAssumed ? "assumed" : rkrk_es_due_class($license, $today, $warnDays),
				"exam_code" => $examCode,
				"theory" => $theory,
				"theory_due" => $theoryDue,
				"theory_cls" => rkrk_es_due_class($theoryDue, $today, $warnDays),
				"material" => $material,
				"praxis" => $praxis,
				"suspended" => $suspended,
				"berechtigt" => $finalOk,
				"flugbereit" => $berechtigt,
				"exam_passed" => ($materialOk && $praxisOk),
				"grace" => $inGrace,
				"mark" => $mark,
				"status" => $status,
				"bemerkung" => $bemerkung,
			);
		}

		usort($out, static function (array $a, array $b): int {
			return strcasecmp((string) $a["name"], (string) $b["name"]);
		});
		return $out;
	}
}

if (!function_exists("rkrk_es_ymd")) {
	function rkrk_es_ymd(?DateTimeImmutable $d): string {
		return $d ? $d->format("Y-m-d") : "";
	}
}

if (!function_exists("rkrk_es_normalize_date_input")) {
	function rkrk_es_normalize_date_input(string $raw): string {
		$d = rkrk_es_parse_date($raw);
		return $d ? $d->format("Y-m-d") : "";
	}
}

if (!function_exists("rkrk_es_save_pilot_fields")) {
	/**
	 * @param array<string, mixed> $fields
	 */
	function rkrk_es_save_pilot_fields(string $id, array $fields): bool {
		$path = function_exists("rkrk_assess_personel_get_file_path") ? rkrk_assess_personel_get_file_path() : null;
		if ($path === null || $path === "") {
			return false;
		}
		$pack = rkrk_assess_personel_load_file($path);
		$pilots = $pack["pilots"];
		$columns = $pack["columns"];
		foreach (array("suspendiert", "einsatz_bemerkung", "theorie_exam_code", "theorie_gueltig", "fpl_gueltig") as $need) {
			if (!in_array($need, $columns, true)) {
				$columns[] = $need;
			}
		}
		$found = false;
		foreach ($pilots as $i => $row) {
			if (!is_array($row)) {
				continue;
			}
			$rid = trim((string) ($row["id"] ?? ""));
			$rkrs = function_exists("rkrk_assess_personel_row_effective_rkrs_id")
				? rkrk_assess_personel_row_effective_rkrs_id($row)
				: trim((string) ($row["rkrs_pilot_id"] ?? ""));
			if ($rid !== $id && $rkrs !== $id) {
				continue;
			}
			foreach ($fields as $k => $v) {
				$pilots[$i][(string) $k] = $v;
			}
			$found = true;
			break;
		}
		if (!$found) {
			return false;
		}
		return rkrk_assess_personel_save_file($path, $columns, $pilots);
	}
}

if (!function_exists("rkrk_es_save_pilot_flags")) {
	function rkrk_es_save_pilot_flags(string $id, bool $suspended, string $bemerkung): bool {
		return rkrk_es_save_pilot_fields($id, array(
			"suspendiert" => $suspended ? "1" : "",
			"einsatz_bemerkung" => $bemerkung,
		));
	}
}

if (!function_exists("rkrk_es_ico")) {
	function rkrk_es_ico(string $name): string {
		$svgs = array(
			"save" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
			"filter" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
			"reset" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95L1 10"/></svg>',
			"cfg" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1.1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>',
			"ok" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>',
			"no" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
			"open" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
			"ban" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="5" y1="5" x2="19" y2="19"/></svg>',
			"warn" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.3 4.2 1.8 19a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L12.7 4.2a2 2 0 0 0-2.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
			"pdf" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h5"/></svg>',
			"xlsx" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>',
			"personel" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
			"mail" => '<svg class="es-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
		);
		return isset($svgs[$name]) ? $svgs[$name] : "";
	}
}
