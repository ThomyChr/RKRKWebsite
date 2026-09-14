<?php
/**
 * Checklist-Form-Fortschritt unter results/checklists_progress/.
 * Getrennt von Theorie-Progress unter results/exams_progress/.
 */
include_once __DIR__ . "/rkrk_assess_result_stem.inc.php";

if (!function_exists("rkrk_assess_checklist_progress_dir")) {
	/** @param string $kind assess|live */
	function rkrk_assess_checklist_progress_dir(string $kind = "assess"): string {
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		$dir = "";
		if ($kind === "live") {
			if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
				$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/results/checklists_progress/";
			} elseif (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
				$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/"));
				$dir = $parent . "/checklists_progress/";
			}
		} else {
			if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
				$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/checklists_progress/";
			} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
				$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/results/checklists_progress/";
			} elseif (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
				$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkAssessResults"]), "/"));
				$dir = $parent . "/checklists_progress/";
			}
		}
		if ($dir === "") {
			return "";
		}
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_checklist_progress_max_age_sec")) {
	function rkrk_assess_checklist_progress_max_age_sec(): int {
		return 7 * 24 * 3600;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_normalize_identity")) {
	/**
	 * @return array{kind:string, file:string, edit:string, draft_id:string}
	 */
	function rkrk_assess_checklist_progress_normalize_identity(array $in): array {
		$kind = strtolower(trim((string) ($in["kind"] ?? "assess")));
		if ($kind !== "live") {
			$kind = "assess";
		}
		return array(
			"kind" => $kind,
			"file" => basename(trim((string) ($in["file"] ?? ""))),
			"edit" => basename(trim((string) ($in["edit"] ?? ""))),
			"draft_id" => preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($in["draft_id"] ?? "")),
		);
	}
}

if (!function_exists("rkrk_assess_checklist_progress_storage_key")) {
	/** Legacy (SHA256) — nur noch Load/Delete-Fallback. */
	function rkrk_assess_checklist_progress_storage_key(array $identity): string {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		$payload = implode("\x1e", array($id["kind"], $id["file"], $id["edit"], $id["draft_id"]));
		return hash("sha256", $payload);
	}
}

if (!function_exists("rkrk_assess_checklist_progress_template_stem")) {
	function rkrk_assess_checklist_progress_template_stem(string $file, string $kind = ""): string {
		$file = basename(trim($file));
		if ($file === "") {
			return "template";
		}
		if (function_exists("rkrk_assess_checklist_title_slug")) {
			$kind = strtolower(trim($kind));
			$tplDir = "";
			if ($kind === "live" && isset($GLOBALS["rkrkLiveassessTemplates"])) {
				$tplDir = (string) $GLOBALS["rkrkLiveassessTemplates"];
			} elseif (isset($GLOBALS["rkrkAssessTemplates"])) {
				$tplDir = (string) $GLOBALS["rkrkAssessTemplates"];
			} elseif (isset($GLOBALS["rkrkLiveassessTemplates"])) {
				$tplDir = (string) $GLOBALS["rkrkLiveassessTemplates"];
			}
			$s = rkrk_assess_checklist_title_slug($file, $tplDir);
			if ($s !== "") {
				return $s;
			}
		}
		$stem = pathinfo($file, PATHINFO_FILENAME);
		$stem = trim((string) $stem);
		if ($stem === "") {
			return "template";
		}
		if (function_exists("rkrk_assess_slugify_file_stem")) {
			$s = rkrk_assess_slugify_file_stem($stem);
			if ($s !== "") {
				return $s;
			}
		}
		return strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', "_", $stem));
	}
}

if (!function_exists("rkrk_assess_checklist_progress_readable_stem")) {
	/**
	 * Gleicher Stamm wie finales Result/Exam-Stil: {template}_{pilot} bzw. edit-ID.
	 *
	 * @param array<string, mixed> $fields
	 */
	function rkrk_assess_checklist_progress_readable_stem(array $identity, array $fields = array()): string {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		if ($id["edit"] !== "") {
			return rkrk_assess_report_stem_from_result($id["edit"]);
		}
		$tplStem = rkrk_assess_checklist_progress_template_stem($id["file"], $id["kind"]);
		$pilotName = "";
		$rkrsId = "";
		if (count($fields) > 0 && function_exists("rkrk_assess_fields_pilot_name_and_id")) {
			$pi = rkrk_assess_fields_pilot_name_and_id($fields);
			$pilotName = (string) ($pi["name"] ?? "");
			$rkrsId = (string) ($pi["id"] ?? "");
		}
		if ($pilotName !== "") {
			return rkrk_assess_build_progress_stem($tplStem, $pilotName, "", $rkrsId);
		}
		return rkrk_assess_build_progress_stem($tplStem, "", $id["draft_id"]);
	}
}

if (!function_exists("rkrk_assess_checklist_progress_legacy_hash_path")) {
	function rkrk_assess_checklist_progress_legacy_hash_path(array $identity): string {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		$dir = rkrk_assess_checklist_progress_dir($id["kind"]);
		if ($dir === "" || $id["file"] === "") {
			return "";
		}
		if ($id["edit"] === "" && $id["draft_id"] === "") {
			return "";
		}
		return $dir . rkrk_assess_checklist_progress_storage_key($id) . ".json";
	}
}

if (!function_exists("rkrk_assess_checklist_progress_file_path")) {
	/**
	 * @param array<string, mixed> $fields Optional — für {template}_{pilot}-Namen beim Speichern
	 */
	function rkrk_assess_checklist_progress_file_path(array $identity, array $fields = array()): string {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		$dir = rkrk_assess_checklist_progress_dir($id["kind"]);
		if ($dir === "" || $id["file"] === "") {
			return "";
		}
		if ($id["edit"] === "" && $id["draft_id"] === "") {
			return "";
		}
		$stem = rkrk_assess_checklist_progress_readable_stem($id, $fields);
		if ($stem === "") {
			return "";
		}
		return $dir . $stem . ".json";
	}
}

if (!function_exists("rkrk_assess_checklist_progress_candidate_paths")) {
	/**
	 * @param array<string, mixed> $fields
	 * @return list<string>
	 */
	function rkrk_assess_checklist_progress_candidate_paths(array $identity, array $fields = array()): array {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		$paths = array();
		$primary = rkrk_assess_checklist_progress_file_path($id, $fields);
		if ($primary !== "") {
			$paths[] = $primary;
		}
		// Ohne Felder zusätzlich Draft-Pfad versuchen
		if ($id["edit"] === "" && $id["draft_id"] !== "" && count($fields) > 0) {
			$draftOnly = rkrk_assess_checklist_progress_file_path($id, array());
			if ($draftOnly !== "" && $draftOnly !== $primary) {
				$paths[] = $draftOnly;
			}
		}
		$legacy = rkrk_assess_checklist_progress_legacy_hash_path($id);
		if ($legacy !== "" && !in_array($legacy, $paths, true)) {
			$paths[] = $legacy;
		}
		foreach (rkrk_assess_checklist_progress_legacy_dirs($id["kind"]) as $legacyDir) {
			$legacyDir = rtrim(str_replace("\\", "/", $legacyDir), "/") . "/";
			$hashLegacy = $legacyDir . rkrk_assess_checklist_progress_storage_key($id) . ".json";
			if ($hashLegacy !== "" && !in_array($hashLegacy, $paths, true)) {
				$paths[] = $hashLegacy;
			}
			if ($id["file"] !== "") {
				$tplStem = rkrk_assess_checklist_progress_template_stem($id["file"], $id["kind"]);
				foreach (glob($legacyDir . $tplStem . "_*.json") ?: array() as $file) {
					if (is_string($file) && !in_array($file, $paths, true)) {
						$paths[] = $file;
					}
				}
			}
		}
		return $paths;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_legacy_dirs")) {
	/** @return list<string> */
	function rkrk_assess_checklist_progress_legacy_dirs(string $kind = "assess"): array {
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		$dirs = array();
		if ($kind === "live") {
			if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
				$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/");
				$dirs[] = $base . "/results/checklist_progress";
				$dirs[] = $base . "/checklists_progress";
			}
		} else {
			if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
				$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/");
				$dirs[] = $base . "/results/checklist_progress";
				$dirs[] = $base . "/checklists_progress";
			}
		}
		return $dirs;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_file_age_ts")) {
	function rkrk_assess_checklist_progress_file_age_ts(string $file, ?array $data = null): int {
		if (is_array($data) && isset($data["updated_at"])) {
			$t = strtotime((string) $data["updated_at"]);
			if ($t !== false) {
				return (int) $t;
			}
		}
		$mt = @filemtime($file);
		return $mt !== false ? (int) $mt : 0;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_purge_old")) {
	function rkrk_assess_checklist_progress_purge_old(string $kind = "assess"): void {
		$dir = rkrk_assess_checklist_progress_dir($kind);
		if ($dir === "") {
			return;
		}
		$cutoff = time() - rkrk_assess_checklist_progress_max_age_sec();
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$raw = @file_get_contents($file);
			$data = (is_string($raw) && $raw !== "") ? json_decode($raw, true) : null;
			if (rkrk_assess_checklist_progress_file_age_ts($file, is_array($data) ? $data : null) < $cutoff) {
				@unlink($file);
			}
		}
	}
}

if (!function_exists("rkrk_assess_checklist_progress_load_from_path")) {
	/** @return array<string, mixed>|null */
	function rkrk_assess_checklist_progress_load_from_path(string $path): ?array {
		if ($path === "" || !is_readable($path)) {
			return null;
		}
		$raw = @file_get_contents($path);
		if ($raw === false || trim($raw) === "") {
			return null;
		}
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return null;
		}
		$cutoff = time() - rkrk_assess_checklist_progress_max_age_sec();
		if (rkrk_assess_checklist_progress_file_age_ts($path, $data) < $cutoff) {
			@unlink($path);
			return null;
		}
		return $data;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_load")) {
	/**
	 * @param array<string, mixed> $fields
	 * @return array<string, mixed>|null
	 */
	function rkrk_assess_checklist_progress_load(array $identity, array $fields = array()): ?array {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		if ($id["file"] === "" || ($id["edit"] === "" && $id["draft_id"] === "")) {
			return null;
		}
		foreach (rkrk_assess_checklist_progress_candidate_paths($id, $fields) as $path) {
			$data = rkrk_assess_checklist_progress_load_from_path($path);
			if (is_array($data)) {
				return $data;
			}
		}
		// Scan: Draft wurde ggf. schon nach {template}_{pilot} umbenannt
		$dir = rkrk_assess_checklist_progress_dir($id["kind"]);
		if ($dir === "") {
			return null;
		}
		$tplStem = rkrk_assess_checklist_progress_template_stem($id["file"], $id["kind"]);
		foreach (glob($dir . $tplStem . "*.json") ?: array() as $file) {
			$data = rkrk_assess_checklist_progress_load_from_path($file);
			if (!is_array($data)) {
				continue;
			}
			$df = basename((string) ($data["file"] ?? ""));
			$de = basename((string) ($data["edit"] ?? ""));
			$dd = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($data["draft_id"] ?? ""));
			if ($df !== "" && $df !== $id["file"]) {
				continue;
			}
			if ($id["edit"] !== "" && $de === $id["edit"]) {
				return $data;
			}
			if ($id["draft_id"] !== "" && $dd === $id["draft_id"]) {
				return $data;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_delete")) {
	function rkrk_assess_checklist_progress_delete(array $identity): bool {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		$ok = true;
		foreach (rkrk_assess_checklist_progress_candidate_paths($id, array()) as $path) {
			if ($path !== "" && is_file($path)) {
				$ok = @unlink($path) && $ok;
			}
		}
		// Auch umbenannte {template}_{pilot}-Dateien mit passender draft_id/edit löschen
		$dir = rkrk_assess_checklist_progress_dir($id["kind"]);
		if ($dir !== "" && $id["file"] !== "") {
			$tplStem = rkrk_assess_checklist_progress_template_stem($id["file"], $id["kind"]);
			foreach (glob($dir . $tplStem . "*.json") ?: array() as $file) {
				$raw = @file_get_contents($file);
				if ($raw === false || trim($raw) === "") {
					continue;
				}
				$data = json_decode($raw, true);
				if (!is_array($data)) {
					continue;
				}
				$de = basename((string) ($data["edit"] ?? ""));
				$dd = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($data["draft_id"] ?? ""));
				if ($id["edit"] !== "" && $de === $id["edit"]) {
					@unlink($file);
				} elseif ($id["draft_id"] !== "" && $dd === $id["draft_id"]) {
					@unlink($file);
				}
			}
		}
		return $ok;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_list")) {
	/**
	 * Alle offenen Checklist-Fortschritte für Admin-Anzeige.
	 *
	 * @return list<array{
	 *   kind:string,file:string,edit:string,draft_id:string,result_stem:string,
	 *   pilot:string,template:string,updated_at:string,created_at:string,path:string,timestamp:int
	 * }>
	 */
	function rkrk_assess_checklist_progress_list(string $kind = "assess"): array {
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		rkrk_assess_checklist_progress_purge_old($kind);
		$dir = rkrk_assess_checklist_progress_dir($kind);
		if ($dir === "") {
			return array();
		}
		$out = array();
		foreach (glob($dir . "*.json") ?: array() as $file) {
			if (!is_file($file)) {
				continue;
			}
			$data = rkrk_assess_checklist_progress_load_from_path($file);
			if (!is_array($data)) {
				continue;
			}
			$fields = isset($data["fields"]) && is_array($data["fields"]) ? $data["fields"] : array();
			if (count($fields) < 1) {
				continue;
			}
			$pilot = "";
			if (function_exists("rkrk_assess_fields_pilot_name_and_id")) {
				$pi = rkrk_assess_fields_pilot_name_and_id($fields);
				$pilot = trim((string) ($pi["name"] ?? ""));
			}
			$fileName = basename(trim((string) ($data["file"] ?? "")));
			$edit = basename(trim((string) ($data["edit"] ?? "")));
			$draftId = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($data["draft_id"] ?? ""));
			if ($fileName === "" || ($edit === "" && $draftId === "")) {
				continue;
			}
			$updatedAt = trim((string) ($data["updated_at"] ?? ""));
			$createdAt = trim((string) ($data["created_at"] ?? ""));
			$ts = 0;
			if ($updatedAt !== "") {
				$parsed = strtotime($updatedAt);
				if ($parsed !== false) {
					$ts = (int) $parsed;
				}
			}
			if ($ts <= 0) {
				$ts = rkrk_assess_checklist_progress_file_age_ts($file, $data);
			}
			$tplLabel = pathinfo($fileName, PATHINFO_FILENAME);
			if ($tplLabel === "") {
				$tplLabel = $fileName;
			}
			$out[] = array(
				"kind" => $kind,
				"file" => $fileName,
				"edit" => $edit,
				"draft_id" => $draftId,
				"result_stem" => trim((string) ($data["result_stem"] ?? pathinfo($file, PATHINFO_FILENAME))),
				"pilot" => $pilot !== "" ? $pilot : "(ohne Pilot)",
				"template" => $tplLabel,
				"updated_at" => $updatedAt,
				"created_at" => $createdAt,
				"path" => $file,
				"timestamp" => $ts,
			);
		}
		usort($out, function ($a, $b) {
			return ((int) ($b["timestamp"] ?? 0)) <=> ((int) ($a["timestamp"] ?? 0));
		});
		return $out;
	}
}

if (!function_exists("rkrk_assess_checklist_progress_delete_by_path")) {
	/** Sicheres Löschen einer Progress-Datei per Basename unter checklists_progress/. */
	function rkrk_assess_checklist_progress_delete_by_path(string $kind, string $basename): bool {
		$kind = strtolower(trim($kind)) === "live" ? "live" : "assess";
		$basename = basename(trim($basename));
		if ($basename === "" || !preg_match('/^[A-Za-z0-9_.-]+\.json$/i', $basename)) {
			return false;
		}
		$dir = rkrk_assess_checklist_progress_dir($kind);
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

if (!function_exists("rkrk_assess_checklist_progress_save")) {
	/**
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool, error?:string, saved_at?:string, path?:string}
	 */
	function rkrk_assess_checklist_progress_save(array $payload): array {
		$id = rkrk_assess_checklist_progress_normalize_identity($payload);
		if ($id["file"] === "") {
			return array("ok" => false, "error" => "file erforderlich.");
		}
		if ($id["edit"] === "" && $id["draft_id"] === "") {
			return array("ok" => false, "error" => "edit oder draft_id erforderlich.");
		}
		$fields = isset($payload["fields"]) && is_array($payload["fields"]) ? $payload["fields"] : array();
		$path = rkrk_assess_checklist_progress_file_path($id, $fields);
		if ($path === "") {
			return array("ok" => false, "error" => "Fortschritts-Ordner nicht verfügbar.");
		}
		$now = date("c");
		$stem = rkrk_assess_checklist_progress_readable_stem($id, $fields);
		$existing = is_file($path) ? rkrk_assess_checklist_progress_load_from_path($path) : null;
		$doc = array(
			"format" => "rkropen_checklist_progress_v1",
			"kind" => $id["kind"],
			"file" => $id["file"],
			"edit" => $id["edit"],
			"draft_id" => $id["draft_id"],
			"result_stem" => $stem,
			"fields" => $fields,
			"weather_json" => (string) ($payload["weather_json"] ?? ""),
			"created_at" => is_array($existing) && isset($existing["created_at"]) ? (string) $existing["created_at"] : $now,
			"updated_at" => $now,
		);
		$json = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false) {
			return array("ok" => false, "error" => "JSON-Fehler.");
		}
		if (@file_put_contents($path, $json, LOCK_EX) === false) {
			return array("ok" => false, "error" => "Speichern fehlgeschlagen.");
		}
		// Alte Hash-/Draft-Dateien aufräumen (nicht die soeben geschriebene)
		$legacy = rkrk_assess_checklist_progress_legacy_hash_path($id);
		if ($legacy !== "" && $legacy !== $path && is_file($legacy)) {
			@unlink($legacy);
		}
		$draftPath = rkrk_assess_checklist_progress_file_path($id, array());
		if ($draftPath !== "" && $draftPath !== $path && is_file($draftPath)) {
			@unlink($draftPath);
		}
		rkrk_assess_checklist_progress_purge_old($id["kind"]);
		return array("ok" => true, "saved_at" => $now, "path" => $path);
	}
}

if (!function_exists("rkrk_assess_checklist_progress_check")) {
	/**
	 * @return array{ok:bool, has_progress:bool, progress?:array<string,mixed>, updated_at?:string}
	 */
	function rkrk_assess_checklist_progress_check(array $identity): array {
		$id = rkrk_assess_checklist_progress_normalize_identity($identity);
		rkrk_assess_checklist_progress_purge_old($id["kind"]);
		if ($id["file"] === "" || ($id["edit"] === "" && $id["draft_id"] === "")) {
			return array("ok" => true, "has_progress" => false);
		}
		$progress = rkrk_assess_checklist_progress_load($id);
		if ($progress === null) {
			return array("ok" => true, "has_progress" => false);
		}
		$fields = isset($progress["fields"]) && is_array($progress["fields"]) ? $progress["fields"] : array();
		if (count($fields) < 1) {
			return array("ok" => true, "has_progress" => false);
		}
		return array(
			"ok" => true,
			"has_progress" => true,
			"progress" => $progress,
			"updated_at" => (string) ($progress["updated_at"] ?? ""),
		);
	}
}

if (!function_exists("rkrk_assess_checklist_progress_emit_form_script")) {
	/**
	 * Idle-Autosave (5 s) + Resume für Checklist-Formulare.
	 *
	 * @param array{kind:string, file:string, edit:string, readonly:bool} $opts
	 */
	function rkrk_assess_checklist_progress_emit_form_script(array $opts): void {
		if (!empty($opts["readonly"])) {
			return;
		}
		$kind = strtolower(trim((string) ($opts["kind"] ?? "assess"))) === "live" ? "live" : "assess";
		$file = basename(trim((string) ($opts["file"] ?? "")));
		$edit = basename(trim((string) ($opts["edit"] ?? "")));
		$draftFromUrl = preg_replace('/[^A-Za-z0-9_-]/', "", (string) ($opts["draft_id"] ?? ($_GET["draft_id"] ?? "")));
		if ($file === "") {
			return;
		}
		$jKind = json_encode($kind, JSON_UNESCAPED_UNICODE);
		$jFile = json_encode($file, JSON_UNESCAPED_UNICODE);
		$jEdit = json_encode($edit, JSON_UNESCAPED_UNICODE);
		$jDraft = json_encode($draftFromUrl, JSON_UNESCAPED_UNICODE);
		$jApi = json_encode("rkrk_assess_checklist_progress.php", JSON_UNESCAPED_UNICODE);
		echo "<div id='rkrk-chk-progress-status' class='no-print' style='position:fixed;right:12px;bottom:12px;z-index:9999;display:none;max-width:280px;padding:8px 12px;border-radius:6px;background:rgba(30,41,59,.92);color:#f8fafc;font-size:12px;line-height:1.35;box-shadow:0 4px 14px rgba(0,0,0,.25);'></div>\n";
		echo "<script>\n(function(){\n";
		echo "var KIND=" . $jKind . ", FILE=" . $jFile . ", EDIT=" . $jEdit . ", URL_DRAFT=" . $jDraft . ", API=" . $jApi . ", IDLE_MS=5000;\n";
		echo <<<'JS'
var form = document.querySelector("form[action*='mode=save']") || document.querySelector("form[method='post']");
if (!form) return;
var statusEl = document.getElementById("rkrk-chk-progress-status");
var timer = null;
var saving = false;
var draftKey = "rkrk_chk_draft_" + KIND + "_" + FILE;
function draftId() {
	if (EDIT) return "";
	if (URL_DRAFT) {
		try { sessionStorage.setItem(draftKey, URL_DRAFT); } catch (e0) {}
		return URL_DRAFT;
	}
	try {
		var d = sessionStorage.getItem(draftKey);
		if (!d) {
			d = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ("d" + Date.now() + Math.random().toString(16).slice(2));
			sessionStorage.setItem(draftKey, d);
		}
		return d;
	} catch (e) { return "d" + Date.now(); }
}
function showStatus(msg, ok) {
	if (!statusEl) return;
	statusEl.style.display = "block";
	statusEl.style.background = ok === false ? "rgba(127,29,29,.94)" : "rgba(30,41,59,.92)";
	statusEl.textContent = msg;
	clearTimeout(showStatus._t);
	showStatus._t = setTimeout(function(){ statusEl.style.display = "none"; }, 3500);
}
function identity() {
	return { kind: KIND, file: FILE, edit: EDIT || "", draft_id: draftId() };
}
function collectFields() {
	var fields = {};
	var els = form.querySelectorAll("input[name^='fields['], select[name^='fields['], textarea[name^='fields[']");
	els.forEach(function(el){
		var name = el.name || "";
		var m = name.match(/^fields\[([^\]]+)\]\[([^\]]+)\](\[\])?$/);
		if (!m) return;
		var sec = m[1], lab = m[2], isArr = !!m[3];
		if (el.type === "radio" && !el.checked) return;
		if (el.type === "checkbox" && !isArr && !el.checked) {
			if (!fields[sec]) fields[sec] = {};
			fields[sec][lab] = "0";
			return;
		}
		if (el.type === "checkbox" && isArr && !el.checked) return;
		var val = el.value;
		if (!fields[sec]) fields[sec] = {};
		if (isArr) {
			if (!Array.isArray(fields[sec][lab])) fields[sec][lab] = [];
			fields[sec][lab].push(val);
		} else if (el.type === "checkbox") {
			fields[sec][lab] = el.checked ? (val || "1") : "0";
		} else {
			fields[sec][lab] = val;
		}
	});
	return fields;
}
function escName(n) {
	return String(n).replace(/\\/g, "\\\\").replace(/"/g, '\\"');
}
function setFieldValue(sec, lab, val) {
	var base = "fields[" + sec + "][" + lab + "]";
	var multi = form.querySelectorAll('[name="' + escName(base) + '[]"]');
	if (multi.length) {
		var want = Array.isArray(val) ? val.map(String) : String(val == null ? "" : val).split(/\s*;\s*/);
		multi.forEach(function(el){
			el.checked = want.indexOf(el.value) !== -1;
			el.dispatchEvent(new Event("change", { bubbles: true }));
		});
		return;
	}
	var radios = form.querySelectorAll('[name="' + escName(base) + '"]');
	if (radios.length > 1 || (radios.length === 1 && radios[0].type === "radio")) {
		var s = String(val == null ? "" : val);
		radios.forEach(function(el){
			if (el.type !== "radio") return;
			el.checked = (el.value === s);
			if (el.checked) el.dispatchEvent(new Event("change", { bubbles: true }));
		});
		return;
	}
	var el = form.querySelector('[name="' + escName(base) + '"]');
	if (!el) return;
	if (el.type === "checkbox") {
		var on = (val === true || val === 1 || val === "1" || String(val).toLowerCase() === "ja" || String(val).toLowerCase() === "on");
		el.checked = on;
	} else {
		el.value = Array.isArray(val) ? val.join(", ") : String(val == null ? "" : val);
	}
	el.dispatchEvent(new Event("input", { bubbles: true }));
	el.dispatchEvent(new Event("change", { bubbles: true }));
}
function applyFields(fields) {
	if (!fields || typeof fields !== "object") return;
	Object.keys(fields).forEach(function(sec){
		var row = fields[sec];
		if (!row || typeof row !== "object") return;
		Object.keys(row).forEach(function(lab){
			setFieldValue(sec, lab, row[lab]);
		});
	});
	if (typeof rkrkUpdateSumFields === "function") {
		try { rkrkUpdateSumFields(); } catch (e0) {}
	}
	if (typeof rkrkUpdateCountFailsFields === "function") {
		try { rkrkUpdateCountFailsFields(); } catch (e1) {}
	}
}
function api(action, body) {
	var url = API + "?action=" + encodeURIComponent(action);
	return fetch(url, {
		method: "POST",
		headers: { "Content-Type": "application/json; charset=UTF-8" },
		credentials: "same-origin",
		body: JSON.stringify(body || {})
	}).then(function(r){ return r.json(); }).catch(function(){ return { ok: false }; });
}
function doSave() {
	if (saving) return;
	saving = true;
	var payload = identity();
	payload.fields = collectFields();
	var w = form.querySelector("[name='weather_json']");
	if (w && w.value) payload.weather_json = w.value;
	api("save", payload).then(function(res){
		saving = false;
		if (res && res.ok) {
			var t = res.saved_at ? String(res.saved_at).replace("T", " ").slice(0, 19) : "";
			showStatus("Zwischengespeichert" + (t ? (" " + t) : ""), true);
		} else {
			showStatus("Zwischenspeicher fehlgeschlagen", false);
		}
	});
}
function schedule() {
	clearTimeout(timer);
	timer = setTimeout(doSave, IDLE_MS);
}
function resume() {
	api("check", identity()).then(function(res){
		if (!res || !res.has_progress || !res.progress) return;
		var fields = res.progress.fields;
		if (!fields) return;
		applyFields(fields);
		if (res.progress.weather_json) {
			var w = form.querySelector("[name='weather_json']");
			if (w) w.value = res.progress.weather_json;
		}
		showStatus("Zwischenstand wiederhergestellt", true);
	});
}
form.addEventListener("input", schedule, true);
form.addEventListener("change", schedule, true);
(function ensureDraftHidden(){
	if (EDIT) return;
	var hid = form.querySelector("input[name='checklist_draft_id']");
	if (!hid) {
		hid = document.createElement("input");
		hid.type = "hidden";
		hid.name = "checklist_draft_id";
		form.appendChild(hid);
	}
	hid.value = draftId();
})();
form.addEventListener("submit", function(){
	clearTimeout(timer);
	api("delete", identity());
	try { if (!EDIT) sessionStorage.removeItem(draftKey); } catch (e2) {}
});
window.addEventListener("DOMContentLoaded", function(){
	setTimeout(resume, 400);
});
})();
</script>
JS;
	}
}
