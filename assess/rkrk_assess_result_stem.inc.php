<?php
/**
 * Einheitliche Dateinamen für Abschluss-JSON, Fortschritt und Reports.
 *
 * Checkliste: {titel}_{nachname}_{vorname}_{rkrsId}_{YYYYMMDD}
 * Quiz:       {titel}_{nachname}_{vorname}_{rkrsId}_{schulung|pruefung}_{YYYYMMDD}
 * Fortschritt: dieselben Felder ohne Datum (bzw. {prefix}_draft_{id})
 * Report:     gleicher Stamm wie das zugehörige JSON (.pdf / .html)
 */
include_once __DIR__ . "/rkrk_assess_filename_ymd.inc.php";

if (!function_exists("rkrk_assess_result_title_slug")) {
	/** Titel/Checklistenname für Dateinamen (kein Exam-Code-Normalizer). */
	function rkrk_assess_result_title_slug(string $title): string {
		$title = trim($title);
		if ($title === "") {
			return "result";
		}
		if (function_exists("rkrk_assess_slugify_file_stem")) {
			$slug = rkrk_assess_slugify_file_stem($title);
			if ($slug !== "") {
				return $slug;
			}
		}
		$slug = preg_replace("/[^A-Za-z0-9_-]+/", "_", $title);
		$slug = preg_replace("/_+/", "_", (string) $slug);
		$slug = trim((string) $slug, "_");
		return $slug !== "" ? strtolower($slug) : "result";
	}
}

if (!function_exists("rkrk_assess_result_prefix_slug")) {
	function rkrk_assess_result_prefix_slug(string $prefix): string {
		return rkrk_assess_result_title_slug($prefix);
	}
}

if (!function_exists("rkrk_assess_stem_token_slug")) {
	function rkrk_assess_stem_token_slug(string $value): string {
		$value = trim($value);
		if ($value === "") {
			return "";
		}
		if (function_exists("rkrk_assess_slugify_file_stem")) {
			$slug = rkrk_assess_slugify_file_stem($value);
			if ($slug !== "") {
				return $slug;
			}
		}
		$slug = preg_replace("/[^A-Za-z0-9_-]+/", "_", $value);
		$slug = preg_replace("/_+/", "_", (string) $slug);
		$slug = strtolower(trim((string) $slug, "_"));
		return $slug;
	}
}

if (!function_exists("rkrk_assess_stem_pilot_parts")) {
	/**
	 * @return array{nachname:string, vorname:string}
	 */
	function rkrk_assess_stem_pilot_parts(string $pilotName): array {
		$pilotName = trim($pilotName);
		$vor = "";
		$nach = "";
		if ($pilotName !== "") {
			if (function_exists("rkrk_assess_split_display_name")) {
				$nn = rkrk_assess_split_display_name($pilotName);
				$vor = trim((string) ($nn["vorname"] ?? ""));
				$nach = trim((string) ($nn["nachname"] ?? ""));
			} else {
				$parts = preg_split('/\s+/u', $pilotName, 2);
				$vor = trim((string) ($parts[0] ?? ""));
				$nach = trim((string) ($parts[1] ?? ""));
			}
		}
		if ($nach === "" && $vor !== "") {
			$nach = $vor;
			$vor = "x";
		}
		if ($vor === "" && $nach !== "") {
			$vor = "x";
		}
		return array(
			"nachname" => rkrk_assess_stem_token_slug($nach),
			"vorname" => rkrk_assess_stem_token_slug($vor),
		);
	}
}

if (!function_exists("rkrk_assess_stem_rkrs_id")) {
	function rkrk_assess_stem_rkrs_id(string $id): string {
		$id = preg_replace('/[^0-9A-Za-z_-]/', "", trim($id));
		if (strlen($id) > 48) {
			$id = substr($id, 0, 48);
		}
		return $id !== "" ? $id : "ohneid";
	}
}

if (!function_exists("rkrk_assess_stem_resolve_rkrs_id")) {
	function rkrk_assess_stem_resolve_rkrs_id(string $id, string $pilotName = ""): string {
		$id = trim($id);
		if ($id !== "") {
			return rkrk_assess_stem_rkrs_id($id);
		}
		$pilotName = trim($pilotName);
		if ($pilotName !== "" && function_exists("rkrk_assess_personel_rkrs_id_by_name")) {
			$found = rkrk_assess_personel_rkrs_id_by_name($pilotName);
			if ($found !== "") {
				return rkrk_assess_stem_rkrs_id($found);
			}
		}
		return "ohneid";
	}
}

if (!function_exists("rkrk_assess_quiz_modus_filename_token")) {
	function rkrk_assess_quiz_modus_filename_token(string $modus): string {
		if (function_exists("rkrk_assess_quiz_modus_normalize")) {
			$n = rkrk_assess_quiz_modus_normalize($modus);
			return $n === "pruefung" ? "pruefung" : "schulung";
		}
		$v = strtolower(trim($modus));
		if ($v === "pruefung" || $v === "prüfung") {
			return "pruefung";
		}
		return "schulung";
	}
}

if (!function_exists("rkrk_assess_stem_join_parts")) {
	/**
	 * @param list<string> $parts
	 */
	function rkrk_assess_stem_join_parts(array $parts): string {
		$out = array();
		foreach ($parts as $p) {
			$p = trim((string) $p, "_");
			if ($p !== "") {
				$out[] = $p;
			}
		}
		$stem = implode("_", $out);
		$stem = preg_replace("/_+/", "_", (string) $stem);
		$stem = trim((string) $stem, "_");
		return $stem !== "" ? $stem : "result";
	}
}

if (!function_exists("rkrk_assess_result_pilot_slug")) {
	function rkrk_assess_result_pilot_slug(string $pilotName): string {
		$parts = rkrk_assess_stem_pilot_parts($pilotName);
		return rkrk_assess_stem_join_parts(array($parts["nachname"], $parts["vorname"]));
	}
}

if (!function_exists("rkrk_assess_build_named_stem")) {
	/**
	 * @param list<string> $extra After id, before optional date (e.g. modus)
	 */
	function rkrk_assess_build_named_stem(
		string $title,
		string $pilotName = "",
		string $rkrsId = "",
		array $extra = array(),
		string $stampOrDate = "",
		bool $withCompletionDate = true
	): string {
		$titleSlug = rkrk_assess_result_title_slug($title);
		$parts = array($titleSlug);
		$nameParts = rkrk_assess_stem_pilot_parts($pilotName);
		if ($nameParts["nachname"] !== "" || $nameParts["vorname"] !== "") {
			$parts[] = $nameParts["nachname"];
			$parts[] = $nameParts["vorname"];
			$parts[] = rkrk_assess_stem_resolve_rkrs_id($rkrsId, $pilotName);
		} elseif ($rkrsId !== "") {
			$parts[] = rkrk_assess_stem_resolve_rkrs_id($rkrsId, $pilotName);
		}
		foreach ($extra as $ex) {
			$ex = trim((string) $ex);
			if ($ex !== "") {
				$parts[] = $ex;
			}
		}
		$stem = rkrk_assess_stem_join_parts($parts);
		if (!$withCompletionDate) {
			return $stem;
		}
		if (function_exists("rkrk_assess_stem_ensure_ymd")) {
			$stamp = trim($stampOrDate);
			if ($stamp === "") {
				$stamp = date("Ymd");
			}
			return rkrk_assess_stem_ensure_ymd($stem, $stamp);
		}
		return $stem;
	}
}

if (!function_exists("rkrk_assess_build_result_stem")) {
	/**
	 * Checklisten-Abschlussstamm (ohne Quiz-Modus).
	 *
	 * @param bool $withCompletionDate YYYYMMDD anhängen (Abschluss); Fortschritt = false
	 */
	function rkrk_assess_build_result_stem(
		string $prefix,
		string $pilotName = "",
		string $stampOrDate = "",
		bool $withCompletionDate = true,
		string $rkrsId = ""
	): string {
		return rkrk_assess_build_named_stem(
			$prefix,
			$pilotName,
			$rkrsId,
			array(),
			$stampOrDate,
			$withCompletionDate
		);
	}
}

if (!function_exists("rkrk_assess_build_quiz_result_stem")) {
	function rkrk_assess_build_quiz_result_stem(
		string $title,
		string $pilotName = "",
		string $rkrsId = "",
		string $modus = "",
		string $stampOrDate = "",
		bool $withCompletionDate = true
	): string {
		$modeTok = rkrk_assess_quiz_modus_filename_token($modus);
		return rkrk_assess_build_named_stem(
			$title,
			$pilotName,
			$rkrsId,
			array($modeTok),
			$stampOrDate,
			$withCompletionDate
		);
	}
}

if (!function_exists("rkrk_assess_build_progress_stem")) {
	function rkrk_assess_build_progress_stem(
		string $prefix,
		string $pilotName = "",
		string $draftId = "",
		string $rkrsId = ""
	): string {
		if (trim($pilotName) !== "") {
			return rkrk_assess_build_result_stem($prefix, $pilotName, "", false, $rkrsId);
		}
		$prefix = rkrk_assess_result_title_slug($prefix);
		$draftId = preg_replace("/[^A-Za-z0-9_-]/", "", trim($draftId));
		if ($draftId !== "") {
			return $prefix . "_draft_" . substr($draftId, 0, 12);
		}
		return $prefix;
	}
}

if (!function_exists("rkrk_assess_result_stem_sanitize")) {
	function rkrk_assess_result_stem_sanitize(string $stem): string {
		$stem = trim($stem);
		$stem = preg_replace('/\.(json|pdf|html)$/i', "", $stem);
		$stem = basename(str_replace("\\", "/", $stem));
		$stem = preg_replace('/[^A-Za-z0-9._-]+/', "_", $stem);
		$stem = preg_replace("/_+/", "_", (string) $stem);
		$stem = trim((string) $stem, "._-");
		if ($stem === "") {
			return "report_" . date("Ymd_His");
		}
		return $stem;
	}
}

if (!function_exists("rkrk_assess_report_stem_from_result")) {
	/** Report-Stamm = exakt der JSON-Stamm (kein zusätzliches Datum). */
	function rkrk_assess_report_stem_from_result(string $resultStemOrPath): string {
		return rkrk_assess_result_stem_sanitize($resultStemOrPath);
	}
}

if (!function_exists("rkrk_assess_rename_stem_sidecars")) {
	/**
	 * Benennt Report-Begleitdateien (.pdf / .html / .prep.pdf) im Ordner um.
	 */
	function rkrk_assess_rename_stem_sidecars(string $dir, string $oldStem, string $newStem): void {
		$dir = rtrim(str_replace("\\", "/", $dir), "/") . "/";
		$oldStem = rkrk_assess_result_stem_sanitize($oldStem);
		$newStem = rkrk_assess_result_stem_sanitize($newStem);
		if ($dir === "/" || $oldStem === "" || $newStem === "" || $oldStem === $newStem || !is_dir($dir)) {
			return;
		}
		foreach (array(".pdf", ".html", ".prep.pdf") as $ext) {
			$from = $dir . $oldStem . $ext;
			$to = $dir . $newStem . $ext;
			if (!is_file($from) || $from === $to) {
				continue;
			}
			if (is_file($to)) {
				@unlink($from);
				continue;
			}
			@rename($from, $to);
		}
	}
}

if (!function_exists("rkrk_assess_rename_result_and_reports")) {
	/**
	 * JSON umbenennen und Reports gleichen Stamms mitziehen.
	 *
	 * @return string Neuer Stamm (oder alt, wenn nichts geändert)
	 */
	function rkrk_assess_rename_result_and_reports(
		string $jsonDir,
		string $oldStem,
		string $newStem,
		string $reportsKind = ""
	): string {
		$jsonDir = rtrim(str_replace("\\", "/", $jsonDir), "/") . "/";
		$oldStem = rkrk_assess_result_stem_sanitize($oldStem);
		$newStem = rkrk_assess_result_stem_sanitize($newStem);
		if ($oldStem === "" || $newStem === "" || $oldStem === $newStem) {
			return $oldStem !== "" ? $oldStem : $newStem;
		}
		$from = $jsonDir . $oldStem . ".json";
		$to = $jsonDir . $newStem . ".json";
		if (is_file($from) && $from !== $to) {
			if (is_file($to) && realpath($to) !== realpath($from)) {
				@unlink($to);
			}
			if (is_file($from)) {
				@rename($from, $to);
			}
		}
		$reportsDir = "";
		if ($reportsKind !== "" && function_exists("rkrk_assess_reports_dir")) {
			$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
			$GLOBALS["rkrk_assess_reports_kind"] = ($reportsKind === "live") ? "live" : "assess";
			$reportsDir = rkrk_assess_reports_dir();
			if ($prevKind === null) {
				unset($GLOBALS["rkrk_assess_reports_kind"]);
			} else {
				$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
			}
		}
		if ($reportsDir === "" && is_dir($jsonDir . "../reports")) {
			$reportsDir = rtrim(str_replace("\\", "/", $jsonDir . "../reports"), "/") . "/";
		}
		if ($reportsDir !== "") {
			rkrk_assess_rename_stem_sidecars($reportsDir, $oldStem, $newStem);
		}
		return $newStem;
	}
}

if (!function_exists("rkrk_assess_exam_completion_stamp_from_session")) {
	/** @param array<string, mixed>|null $session */
	function rkrk_assess_exam_completion_stamp_from_session(?array $session): string {
		if (!is_array($session)) {
			return date("Ymd");
		}
		$attempts = isset($session["attempts"]) && is_array($session["attempts"]) ? $session["attempts"] : array();
		foreach ($attempts as $attempt) {
			if (!is_array($attempt)) {
				continue;
			}
			foreach (array("completed_at", "updated_at", "created_at") as $key) {
				$raw = trim((string) ($attempt[$key] ?? ""));
				if ($raw !== "") {
					return $raw;
				}
			}
		}
		foreach (array("completed_at", "updated_at", "created_at") as $key) {
			$raw = trim((string) ($session[$key] ?? ""));
			if ($raw !== "") {
				return $raw;
			}
		}
		return date("Ymd");
	}
}

if (!function_exists("rkrk_assess_checklist_completion_stamp")) {
	function rkrk_assess_checklist_completion_stamp(string $existingStem = "", string $createdAt = ""): string {
		if ($existingStem !== "" && function_exists("rkrk_assess_stem_contains_ymd") && rkrk_assess_stem_contains_ymd($existingStem)) {
			if (preg_match('/(?:^|_)(\d{8})(?:_|$)/', $existingStem, $m)) {
				return $m[1];
			}
		}
		$createdAt = trim($createdAt);
		if ($createdAt !== "") {
			return $createdAt;
		}
		return date("Ymd");
	}
}
