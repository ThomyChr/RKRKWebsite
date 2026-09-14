<?php
/**
 * Review-Merge: Reviews in eine neue Basis (Master-Katalog) überführen,
 * Basis-Quiz in AssessmentListe umstellen, Archiv-Kopien.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
ini_set("default_charset", "UTF-8");
if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("UTF-8");
}
if (!headers_sent()) {
	header("Content-Type: text/html; charset=UTF-8");
}

$folderoffset = "../";
include_once($folderoffset . "scripts/rkrk_inputforms.php");
include_once($folderoffset . "scripts/rkrk_csvfiles.php");
include_once("rkrk_assess_common.php");

if (!$onRKRS) {
	CheckIPandExit($folderoffset);
	WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", $folderoffset);
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	GrantAccess($folderoffset);
	if (empty($LoggedOn)) {
		if (function_exists("RegisterForm")) {
			RegisterForm(getIPAddress(), "Login", true);
		}
		exit;
	}
} else {
	rkrk_assess_theme_link_tags();
}

$darm_admin = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool)$onAdmin);
if (!$darm_admin) {
	headerpage("Review zusammenführen", false, $folderoffset);
	echo "<div style='max-width:720px;margin:2rem auto;padding:1rem;font-family:system-ui,sans-serif;'>";
	echo "<p>Zum Zusammenführen von Review-Katalogen ist <strong>?ADMIN</strong> erforderlich.</p>";
	echo "<p><a href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_digitalassess_review_merge.php?ADMIN"), ENT_QUOTES, "UTF-8") . "'>Mit ADMIN öffnen</a></p>";
	echo "</div>";
	exit;
}

function darm_h($v) {
	return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/** Select wie Fragen-Review (data-edit-field statt name). */
function darm_option_select(string $current, array $options, string $editField): string {
	$current = trim((string)$current);
	$html = '<select data-edit-field="' . darm_h($editField) . '">';
	foreach ($options as $opt) {
		$sel = ((string)$opt === $current) ? " selected" : "";
		$html .= '<option value="' . darm_h((string)$opt) . '"' . $sel . ">" . darm_h((string)$opt) . "</option>";
	}
	if ($current !== "" && !in_array($current, $options, true)) {
		$html .= '<option value="' . darm_h($current) . '" selected>' . darm_h($current) . "</option>";
	}
	return $html . "</select>";
}

function darm_fix_mojibake($value) {
	$value = (string)$value;
	if ($value === "") {
		return $value;
	}
	return strtr($value, array(
		"Ã¤" => "ä", "Ã„" => "Ä", "Ã¶" => "ö", "Ã–" => "Ö", "Ã¼" => "ü", "Ãœ" => "Ü", "ÃŸ" => "ß",
		"â€“" => "–", "â€”" => "—", "â€ž" => "„", "â€œ" => "“", "â€" => "”",
		"Â°" => "°", "Â" => "",
	));
}

/** Zeilenumbrüche vereinheitlichen (LF), Inhalt sonst unverändert. */
function darm_normalize_line_endings($value): string {
	$s = (string)$value;
	if ($s === "") {
		return "";
	}
	return str_replace(array("\r\n", "\r"), "\n", $s);
}

/** Mehrzeilige CSV-Felder beim Merge erhalten (Lösungstext, Rückmeldung, Frage). */
function darm_preserve_multiline_field($value): string {
	return darm_normalize_line_endings($value);
}

function darm_multiline_field_is_empty($value): bool {
	return trim(darm_normalize_line_endings($value)) === "";
}

/** @return list<string> */
function darm_multiline_merge_fields(): array {
	return array("Frage", "Loesungstext", "Antwort", "RueckmeldungText");
}

function darm_merge_field_values_equal(string $field, $a, $b): bool {
	if (in_array($field, darm_multiline_merge_fields(), true)) {
		return darm_normalize_line_endings($a) === darm_normalize_line_endings($b);
	}
	return (string)$a === (string)$b;
}

function darm_clean_relative_file($value, $defaultExt = "csv") {
	$value = str_replace("\\", "/", trim((string)$value));
	$value = ltrim($value, "/");
	$value = preg_replace('/\.\.+/', '', (string)$value);
	if ($value === "") {
		return "";
	}
	if (preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) !== 1) {
		return "";
	}
	if (strpos($value, ".") === false && $defaultExt !== "") {
		$value .= "." . $defaultExt;
	}
	return $value;
}

function darm_read_csv($path) {
	if (!function_exists("rkrk_assess_read_csv_file")) {
		return array(array(), array());
	}
	list($headers, $rows) = rkrk_assess_read_csv_file($path, null, ";");
	foreach ($rows as $ri => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach ($row as $hk => $hv) {
			$cell = darm_fix_mojibake((string)$hv);
			if (in_array($hk, darm_multiline_merge_fields(), true)) {
				$cell = darm_preserve_multiline_field($cell);
			}
			$rows[$ri][$hk] = $cell;
		}
	}
	return array($headers, $rows);
}

function darm_write_csv($path, $headers, $rows) {
	return function_exists("rkrk_assess_write_csv_file")
		? rkrk_assess_write_csv_file($path, $headers, $rows, ";")
		: false;
}

function darm_ensure_headers($headers) {
	$target = rkrk_assess_question_csv_headers();
	$out = array();
	foreach ($target as $h) {
		if (in_array($h, $headers, true)) {
			$out[] = $h;
		}
	}
	foreach ($target as $h) {
		if (!in_array($h, $out, true)) {
			$out[] = $h;
		}
	}
	foreach ($headers as $h) {
		if (!in_array($h, $out, true)) {
			$out[] = $h;
		}
	}
	return $out;
}

function darm_normalize_row($row, $headers) {
	$out = array();
	$multiline = darm_multiline_merge_fields();
	foreach ($headers as $h) {
		$v = isset($row[$h]) ? (string)$row[$h] : "";
		if (in_array($h, $multiline, true)) {
			$v = darm_preserve_multiline_field($v);
		}
		$out[$h] = $v;
	}
	if (trim((string)$out["Status"]) === "") {
		$out["Status"] = "aktiv";
	}
	if (trim((string)$out["Rueckmeldung"]) === "") {
		$out["Rueckmeldung"] = "offen";
	}
	return $out;
}

/** Alle Spalten aus Basis + Reviews für vollständige CSV-Zeilen. */
function darm_collect_merge_headers(array $sources): array {
	$merged = array();
	foreach ($sources as $cat) {
		if (!is_array($cat)) {
			continue;
		}
		$h = isset($cat["headers"]) && is_array($cat["headers"]) ? $cat["headers"] : array();
		if (count($h) > 0) {
			$merged = array_merge($merged, $h);
		}
	}
	if (count($merged) === 0) {
		return function_exists("rkrk_assess_question_csv_headers")
			? rkrk_assess_question_csv_headers()
			: array();
	}
	return darm_ensure_headers($merged);
}

function darm_row_nr_key($row, $fallbackIdx) {
	$nr = trim((string)($row["Nr"] ?? ""));
	if ($nr === "") {
		$nr = (string)((int)$fallbackIdx + 1);
	}
	return $nr;
}

/** @return array<string, array<string, string>> */
function darm_rows_by_nr(array $rows, array $headers) {
	$map = array();
	foreach ($rows as $i => $row) {
		if (!is_array($row)) {
			continue;
		}
		$line = darm_normalize_row($row, $headers);
		if (trim((string)$line["Frage"]) === "" && trim((string)$line["Loesung"]) === "") {
			continue;
		}
		$k = darm_row_nr_key($line, $i);
		$map[$k] = $line;
	}
	return $map;
}

function darm_fragen_catalog_stem(string $fragenFile) {
	$base = preg_replace('/\.csv$/iu', '', basename(str_replace("\\", "/", $fragenFile)));
	$base = preg_replace('/-(roger|philipp|simon|alain)$/iu', '', $base);
	$base = preg_replace('/-basis-\d{12}$/iu', '', $base);
	$base = preg_replace('/-merged-\d{12}$/iu', '', $base);
	return trim((string)$base);
}

function darm_basis_stem_from_assessment(array $baseAss): string {
	return darm_fragen_catalog_stem((string)($baseAss["FragenDatei"] ?? ""));
}

/** Dateiname für eine neue Basis-Version (nicht Review-Suffix). */
function darm_default_new_basis_filename(array $baseAss): string {
	$stem = darm_basis_stem_from_assessment($baseAss);
	if ($stem === "") {
		return "basis-" . date("YmdHi") . ".csv";
	}
	return $stem . "-basis-" . date("YmdHi") . ".csv";
}

/**
 * Basis-Quiz in AssessmentListe auf neue Fragen-CSV umstellen.
 */
function darm_promote_base_quiz_fragen_file(array &$assessments, string $baseKey, string $fragenFile, int $questionCount): bool {
	$updated = false;
	foreach ($assessments as $i => $a) {
		$k = trim((string)($a["Key"] ?? ""));
		if ($k === "" || ($k !== $baseKey && strcasecmp($k, $baseKey) !== 0)) {
			continue;
		}
		$assessments[$i]["FragenDatei"] = $fragenFile;
		$assessments[$i]["AnzQuestions"] = (string)$questionCount;
		$updated = true;
	}
	return $updated;
}

function darm_merge_drafts_dir(string $quizLoc): string {
	$dir = rtrim(str_replace("\\", "/", $quizLoc), "/") . "/merge-drafts";
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir . "/";
}

function darm_merge_draft_fingerprint(string $baseKey, array $revKeys): string {
	$parts = array("base:" . trim($baseKey));
	foreach (darm_review_slot_ids() as $sid) {
		$parts[] = $sid . ":" . trim((string)($revKeys[$sid] ?? ""));
	}
	return md5(implode("\x1e", $parts));
}

function darm_merge_draft_path(string $quizLoc, string $draftId): string {
	$id = preg_replace('/[^a-f0-9]/', '', strtolower((string)$draftId));
	if ($id === "") {
		return "";
	}
	return darm_merge_drafts_dir($quizLoc) . "merge_" . $id . ".json";
}

/**
 * @param array<string, mixed> $draft
 */
function darm_save_merge_draft(string $quizLoc, string $draftId, array $draft): bool {
	$path = darm_merge_draft_path($quizLoc, $draftId);
	if ($path === "") {
		return false;
	}
	$draft["format"] = "rkropen_merge_draft_v1";
	$draft["updated_at"] = date("c");
	$draft["draft_id"] = $draftId;
	$json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	if ($json === false) {
		return false;
	}
	return @file_put_contents($path, $json) !== false;
}

/**
 * @return array<string, mixed>|null
 */
function darm_load_merge_draft(string $quizLoc, string $draftId) {
	$path = darm_merge_draft_path($quizLoc, $draftId);
	if ($path === "" || !is_file($path)) {
		return null;
	}
	$raw = @file_get_contents($path);
	if ($raw === false || trim($raw) === "") {
		return null;
	}
	$data = json_decode($raw, true);
	return is_array($data) ? $data : null;
}

function darm_delete_merge_draft(string $quizLoc, string $draftId): void {
	$path = darm_merge_draft_path($quizLoc, $draftId);
	if ($path !== "" && is_file($path)) {
		@unlink($path);
	}
}

/**
 * @param list<array<string, string>> $assessments
 * @return list<array<string, mixed>>
 */
function darm_list_merge_drafts(string $quizLoc, array $assessments): array {
	$dir = darm_merge_drafts_dir($quizLoc);
	$files = glob($dir . "merge_*.json");
	if (!is_array($files)) {
		return array();
	}
	$titleByKey = array();
	foreach ($assessments as $a) {
		$k = trim((string)($a["Key"] ?? ""));
		if ($k !== "") {
			$titleByKey[$k] = trim((string)($a["Titel"] ?? $k));
		}
	}
	$out = array();
	foreach ($files as $path) {
		$raw = @file_get_contents($path);
		if ($raw === false) {
			continue;
		}
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			continue;
		}
		$id = (string)($data["draft_id"] ?? "");
		if ($id === "") {
			$id = preg_replace('/^merge_|\.json$/', "", basename($path));
		}
		$base = trim((string)($data["base_quiz"] ?? ""));
		$revKeys = isset($data["rev_keys"]) && is_array($data["rev_keys"]) ? $data["rev_keys"] : array();
		$label = trim((string)($data["label"] ?? ""));
		if ($label === "") {
			$label = $base !== "" ? ($titleByKey[$base] ?? $base) : "Merge-Entwurf";
			$nRev = 0;
			foreach (darm_review_slot_ids() as $sid) {
				if (trim((string)($revKeys[$sid] ?? "")) !== "") {
					$nRev++;
				}
			}
			if ($nRev > 0) {
				$label .= " · " . $nRev . " Reviews";
			}
		}
		$progress = isset($data["progress"]) && is_array($data["progress"]) ? $data["progress"] : array();
		$out[] = array(
			"id" => $id,
			"label" => $label,
			"updated_at" => (string)($data["updated_at"] ?? ""),
			"base_quiz" => $base,
			"rev_keys" => $revKeys,
			"decided" => (int)($progress["decided"] ?? 0),
			"total" => (int)($progress["total"] ?? 0),
		);
	}
	usort($out, function ($a, $b) {
		return strcmp((string)($b["updated_at"] ?? ""), (string)($a["updated_at"] ?? ""));
	});
	return $out;
}

/**
 * @param array<int, array<string, mixed>> $decisions
 * @return array{decided: int, total: int}
 */
function darm_merge_draft_progress(array $decisions, int $totalQuestions): array {
	$decided = 0;
	foreach ($decisions as $item) {
		if (!is_array($item)) {
			continue;
		}
		if (darm_payload_item_is_decided($item)) {
			$decided++;
		}
	}
	return array("decided" => $decided, "total" => $totalQuestions);
}

/**
 * @param array<string, mixed> $item
 */
function darm_payload_item_is_decided(array $item): bool {
	$src = trim((string)($item["source"] ?? ""));
	if ($src !== "" && $src !== "base") {
		return true;
	}
	if ($src === "custom") {
		return true;
	}
	$add = isset($item["add"]) && is_array($item["add"]) ? $item["add"] : array();
	return count($add) > 0;
}

/**
 * @param array<string, array<string, array<string, string>>> $sources
 * @param list<string> $headers
 * @return array<string, string>|null
 */
function darm_resolve_row_from_payload_item(array $item, array $sources, array $headers) {
	$nr = trim((string)($item["nr"] ?? ""));
	$src = trim((string)($item["source"] ?? "base"));
	if ($nr === "") {
		return null;
	}
	$fullFromSource = function (string $sourceId) use ($sources, $headers, $nr): ?array {
		if ($sourceId === "" || !isset($sources[$sourceId]["by_nr"][$nr])) {
			return null;
		}
		return darm_normalize_row($sources[$sourceId]["by_nr"][$nr], $headers);
	};
	if ($src === "custom" && isset($item["custom"]) && is_array($item["custom"])) {
		$templateSrc = trim((string)($item["template_source"] ?? ""));
		if ($templateSrc === "" || $templateSrc === "custom") {
			$templateSrc = "base";
		}
		$row = $fullFromSource($templateSrc);
		if (!is_array($row)) {
			$row = $fullFromSource("base");
		}
		if (!is_array($row)) {
			$row = darm_normalize_row(array(), $headers);
		}
		foreach ($item["custom"] as $k => $v) {
			if (is_string($k) && $k !== "") {
				$val = (string)$v;
				if (in_array($k, darm_multiline_merge_fields(), true)) {
					$val = darm_preserve_multiline_field($val);
				}
				$row[$k] = $val;
			}
		}
		$row["Nr"] = $nr;
		return $row;
	}
	$row = $fullFromSource($src);
	if (is_array($row)) {
		return $row;
	}
	return $fullFromSource("base");
}

/**
 * @param array<string, string> $row
 */
function darm_apply_merged_row_meta(array &$row, bool $markUeberarbeitet, bool $resetReview, string $suffix = ""): void {
	if ($markUeberarbeitet) {
		$row["Status"] = "überarbeitet";
	}
	if ($resetReview) {
		$row["Rueckmeldung"] = "offen";
		$row["RueckmeldungText"] = "";
	}
	$chg = "neue Basis " . date("Y-m-d H:i");
	if ($suffix !== "") {
		$chg .= " " . $suffix;
	}
	$row["Änderung"] = $chg;
}

/**
 * @param array<int, array<string, mixed>> $payload
 * @param array<string, array<string, mixed>> $sources
 * @param list<string> $headers
 * @return list<array<string, string>>
 */
function darm_build_merged_rows_from_payload(array $payload, array $sources, array $headers, bool $resetReview): array {
	$out = array();
	foreach ($payload as $item) {
		if (!is_array($item)) {
			continue;
		}
		$nr = trim((string)($item["nr"] ?? ""));
		if ($nr === "") {
			continue;
		}
		$src = trim((string)($item["source"] ?? "base"));
		$add = isset($item["add"]) && is_array($item["add"]) ? $item["add"] : array();
		$add = array_values(array_unique(array_filter(array_map("trim", $add), function ($v) {
			return $v !== "" && $v !== "base";
		})));

		$primary = darm_resolve_row_from_payload_item($item, $sources, $headers);
		if (is_array($primary)) {
			$row = $primary;
			$markPrimary = ($src !== "base") || $src === "custom";
			darm_apply_merged_row_meta($row, $markPrimary, $resetReview && $markPrimary);
			$out[] = array("sort" => (int)preg_replace('/\D/', '', $nr) * 1000, "sub" => 0, "row" => $row);
		}

		$sub = 1;
		foreach ($add as $addSrc) {
			if ($addSrc === $src) {
				continue;
			}
			if (!isset($sources[$addSrc]["by_nr"][$nr])) {
				continue;
			}
			$addRowRaw = $sources[$addSrc]["by_nr"][$nr];
			if (!is_array($addRowRaw)) {
				continue;
			}
			$addRow = darm_normalize_row($addRowRaw, $headers);
			$herkunft = trim((string)($addRow["Herkunft"] ?? ""));
			if ($herkunft === "") {
				$addRow["Herkunft"] = "zusätzlich aus Review " . $addSrc;
			} else {
				$addRow["Herkunft"] = $herkunft . " | zusätzlich " . $addSrc;
			}
			darm_apply_merged_row_meta($addRow, true, $resetReview, "(+)");
			$out[] = array("sort" => (int)preg_replace('/\D/', '', $nr) * 1000 + $sub, "sub" => $sub, "row" => $addRow);
			$sub++;
		}
	}
	usort($out, function ($a, $b) {
		if ($a["sort"] === $b["sort"]) {
			return $a["sub"] <=> $b["sub"];
		}
		return $a["sort"] <=> $b["sort"];
	});
	$merged = array();
	$nrAssign = 1;
	foreach ($out as $entry) {
		$row = $entry["row"];
		$row["Nr"] = (string)$nrAssign;
		$merged[] = $row;
		$nrAssign++;
	}
	return $merged;
}

/**
 * @param array<string, mixed> $draft
 * @param array<string, string> $revKeys
 */
function darm_merge_draft_matches_selection(array $draft, string $baseKey, array $revKeys): bool {
	if (trim((string)($draft["base_quiz"] ?? "")) !== trim($baseKey)) {
		return false;
	}
	$saved = isset($draft["rev_keys"]) && is_array($draft["rev_keys"]) ? $draft["rev_keys"] : array();
	foreach (darm_review_slot_ids() as $sid) {
		if (trim((string)($saved[$sid] ?? "")) !== trim((string)($revKeys[$sid] ?? ""))) {
			return false;
		}
	}
	return true;
}

function darm_merge_draft_resume_query(array $draft): array {
	$q = array("ADMIN" => "", "compare" => "1", "resume_draft" => "1");
	$q["base_quiz"] = trim((string)($draft["base_quiz"] ?? ""));
	$revKeys = isset($draft["rev_keys"]) && is_array($draft["rev_keys"]) ? $draft["rev_keys"] : array();
	for ($i = 1; $i <= 5; $i++) {
		$sid = "rev" . $i;
		$v = trim((string)($revKeys[$sid] ?? ""));
		if ($v !== "") {
			$q["rev" . $i . "_quiz"] = $v;
		}
	}
	return $q;
}

function darm_row_signature(array $row) {
	$parts = array("Frage", "Loesungsauswahl", "Loesung", "Loesungstext", "Antwort", "Rueckmeldung", "RueckmeldungText", "Status", "Prioritaet", "Bereich");
	$s = "";
	foreach ($parts as $p) {
		$s .= "\x1e" . (isset($row[$p]) ? (string)$row[$p] : "");
	}
	return md5($s);
}

function darm_compare_fields() {
	return array("Frage", "Loesungsauswahl", "Loesung", "Loesungstext", "Antwort", "Rueckmeldung", "RueckmeldungText", "Status", "Prioritaet", "Bereich");
}

/** Anzeigenamen für Abweichungen gegenüber der Basis. */
function darm_compare_field_label(string $field): string {
	static $labels = array(
		"Frage" => "Frage",
		"Loesungsauswahl" => "Antwortoptionen",
		"Loesung" => "Lösung",
		"Loesungstext" => "Lösungstext",
		"Antwort" => "Antworttext",
		"Rueckmeldung" => "Rückmeldung",
		"RueckmeldungText" => "Rückmeldungstext",
		"Status" => "Status",
		"Prioritaet" => "Priorität",
		"Bereich" => "Bereich",
	);
	return isset($labels[$field]) ? $labels[$field] : $field;
}

/** @return list<string> Felder, die in $row von $baseRow abweichen. */
function darm_diff_fields_vs_base(?array $baseRow, ?array $row): array {
	if (!is_array($baseRow) || !is_array($row)) {
		return array();
	}
	$diff = array();
	foreach (darm_compare_fields() as $field) {
		if (!darm_merge_field_values_equal($field, $baseRow[$field] ?? "", $row[$field] ?? "")) {
			$diff[] = $field;
		}
	}
	return $diff;
}

function darm_question_has_diff_vs_base(array $activeSlots, string $nr): bool {
	$baseRow = null;
	foreach ($activeSlots as $s) {
		if (($s["id"] ?? "") === "base") {
			$baseRow = isset($s["map"][$nr]) ? $s["map"][$nr] : null;
			break;
		}
	}
	foreach ($activeSlots as $s) {
		if (($s["id"] ?? "") === "base") {
			continue;
		}
		if (!isset($s["map"][$nr])) {
			continue;
		}
		if (!is_array($baseRow)) {
			return true;
		}
		if (count(darm_diff_fields_vs_base($baseRow, $s["map"][$nr])) > 0) {
			return true;
		}
	}
	return false;
}

function darm_render_vs_base_summary_html(array $diffFields): string {
	if (count($diffFields) === 0) {
		return "";
	}
	$parts = array();
	foreach ($diffFields as $field) {
		$parts[] = darm_compare_field_label((string)$field);
	}
	return '<div class="darm-vs-base"><strong>Δ Basis:</strong> ' . darm_h(implode(" · ", $parts)) . "</div>";
}

function darm_field_differs(array $maps, string $nr, string $field) {
	$vals = array();
	foreach ($maps as $m) {
		if (!isset($m[$nr])) {
			continue;
		}
		$vals[] = (string)($m[$nr][$field] ?? "");
	}
	if (count($vals) < 2) {
		return false;
	}
	$first = $vals[0];
	foreach ($vals as $v) {
		if (!darm_merge_field_values_equal($field, $first, $v)) {
			return true;
		}
	}
	return false;
}

function darm_assessment_by_key(array $assessments, string $key) {
	foreach ($assessments as $row) {
		$k = trim((string)($row["Key"] ?? ""));
		if ($k !== "" && ($k === $key || strcasecmp($k, $key) === 0)) {
			return $row;
		}
	}
	return null;
}

/** @return list<string> */
function darm_review_slot_ids(): array {
	return array("rev1", "rev2", "rev3", "rev4", "rev5");
}

/** @return array<string, string> */
function darm_rev_keys_from_request(array $source): array {
	$out = array();
	for ($i = 1; $i <= 5; $i++) {
		$out["rev" . $i] = trim((string)($source["rev" . $i . "_quiz"] ?? ""));
	}
	return $out;
}

/** @return list<string> */
function darm_list_fragen_csv_files(string $quizLoc): array {
	$files = array();
	$dir = rtrim(str_replace("\\", "/", $quizLoc), "/");
	if ($dir === "" || !is_dir($dir)) {
		return $files;
	}
	$dh = @opendir($dir);
	if ($dh === false) {
		return $files;
	}
	while (($ent = readdir($dh)) !== false) {
		if ($ent === "." || $ent === "..") {
			continue;
		}
		if (!preg_match('/\.csv$/i', $ent)) {
			continue;
		}
		if (strcasecmp($ent, "AssessmentListe.csv") === 0) {
			continue;
		}
		$files[] = $ent;
	}
	closedir($dh);
	sort($files, SORT_NATURAL | SORT_FLAG_CASE);
	return $files;
}

/**
 * Kopie der geschriebenen CSV nach data/assess/Archiv/ (Merge-Version).
 */
function darm_archive_written_version(string $csvAbsolutePath, string $quizLoc): void {
	if (!is_file($csvAbsolutePath) || !function_exists("rkrk_assess_fragenkatalog_archiv_timestamp")) {
		return;
	}
	$root = rtrim(str_replace("\\", "/", $quizLoc), "/");
	if ($root === "") {
		return;
	}
	$archDir = $root . "/Archiv";
	@mkdir($archDir, 0775, true);
	if (function_exists("rkrk_assess_fragenkatalog_cleanup_archiv")) {
		rkrk_assess_fragenkatalog_cleanup_archiv($archDir);
	}
	if (function_exists("rkrk_assess_fragenkatalog_is_under_assess_root")
		&& !rkrk_assess_fragenkatalog_is_under_assess_root($csvAbsolutePath, $quizLoc)) {
		return;
	}
	$bn = basename(str_replace("\\", "/", $csvAbsolutePath));
	$stem = preg_replace('/\.csv$/i', "", $bn);
	$dest = $archDir . "/" . $stem . "_" . rkrk_assess_fragenkatalog_archiv_timestamp() . ".csv";
	if (!is_file($dest)) {
		@copy($csvAbsolutePath, $dest);
	}
}

/** @return array<string, bool> */
function darm_loesung_token_lookup(array $row): array {
	$lookup = array();
	$sol = trim((string)($row["Loesung"] ?? ""));
	foreach (array_filter(array_map("trim", explode("|", $sol))) as $tok) {
		$lookup[strtolower($tok)] = true;
	}
	return $lookup;
}

function darm_antworten_differ(array $maps, string $nr): bool {
	foreach (array("Loesungsauswahl", "Loesung", "Antwort") as $field) {
		if (darm_field_differs($maps, $nr, $field)) {
			return true;
		}
	}
	return false;
}

/**
 * Antwortoptionen als Liste (hervorgehoben wenn $emphasize).
 */
function darm_render_antworten_html(array $row, bool $emphasize, array $diffFields = array()): string {
	$opts = array_values(array_filter(array_map("trim", explode("|", (string)($row["Loesungsauswahl"] ?? ""))), function ($v) {
		return $v !== "";
	}));
	$lookup = darm_loesung_token_lookup($row);
	$loes = trim((string)($row["Loesung"] ?? ""));
	$antwort = trim((string)($row["Antwort"] ?? ""));
	$wrapCls = "darm-antworten darm-row-sync" . ($emphasize ? " darm-antworten-diff" : "");
	$html = '<div class="' . $wrapCls . '">';
	if (count($opts) > 0) {
		$optLblCls = "darm-antworten-label" . (in_array("Loesungsauswahl", $diffFields, true) ? " darm-field-diff" : "");
		$html .= '<div class="' . $optLblCls . '">Antwortoptionen</div><ul class="darm-options">';
		foreach ($opts as $i => $opt) {
			$letter = chr(65 + $i);
			$isCorrect = isset($lookup[strtolower($letter)]);
			if (!$isCorrect) {
				foreach ($lookup as $tok => $_) {
					if (strlen($tok) > 1 && stripos($opt, $tok) !== false) {
						$isCorrect = true;
						break;
					}
				}
			}
			$cls = $isCorrect ? " darm-opt-correct" : "";
			$html .= '<li class="darm-opt' . $cls . '"><span class="darm-opt-letter">' . darm_h($letter) . ".</span> " . darm_h($opt) . "</li>";
		}
		$html .= "</ul>";
	}
	if ($loes !== "") {
		$loesCls = "darm-loes-kurz" . (in_array("Loesung", $diffFields, true) ? " darm-field-diff" : "");
		$html .= '<div class="' . $loesCls . '"><strong>Lösung:</strong> ' . darm_h($loes) . "</div>";
	}
	$antCls = "darm-antwort-text" . (in_array("Antwort", $diffFields, true) ? " darm-field-diff" : "");
	$html .= '<div class="' . $antCls . '"><strong>Antworttext:</strong> ';
	$html .= $antwort !== "" ? darm_h($antwort) : '<em class="darm-antwort-text--leer">—</em>';
	$html .= "</div>";
	$html .= "</div>";
	return $html;
}

/** Meta-Zeile (Rückmeldung, Prio, Status) — eine Grid-Zeile pro Spalte. */
function darm_render_col_meta_html(array $row, array $diffFields = array()): string {
	$rev = trim((string)($row["Rueckmeldung"] ?? "offen"));
	$revCls = "darm-pill";
	if ($rev === "wichtig") {
		$revCls .= " rev-wichtig";
	} elseif ($rev === "gut") {
		$revCls .= " rev-gut";
	} elseif (stripos($rev, "arbeit") !== false) {
		$revCls .= " rev-überarbeiten";
	}
	if (in_array("Rueckmeldung", $diffFields, true)) {
		$revCls .= " darm-field-diff";
	}
	$html = '<div class="darm-meta darm-row-sync"><span class="' . darm_h($revCls) . '">' . darm_h($rev) . "</span>";
	$prio = trim((string)($row["Prioritaet"] ?? ""));
	if ($prio !== "") {
		$prioCls = "darm-pill" . (in_array("Prioritaet", $diffFields, true) ? " darm-field-diff" : "");
		$html .= '<span class="' . darm_h($prioCls) . '">Prio ' . darm_h($prio) . "</span>";
	}
	$st = trim((string)($row["Status"] ?? ""));
	if ($st !== "") {
		$stCls = "darm-pill" . (in_array("Status", $diffFields, true) ? " darm-field-diff" : "");
		$html .= '<span class="' . darm_h($stCls) . '">' . darm_h($st) . "</span>";
	}
	return $html . "</div>";
}

/** Lösungstext + Antwort nebeneinander (immer sichtbar; leer = «—» für Spaltenvergleich). */
function darm_render_col_loesungstext_html(array $row, array $diffFields = array()): string {
	$solText = darm_preserve_multiline_field($row["Loesungstext"] ?? "");
	$antwort = darm_preserve_multiline_field($row["Antwort"] ?? "");
	$solCls = "darm-loesungstext";
	if (darm_multiline_field_is_empty($solText)) {
		$solCls .= " darm-loesungstext--empty";
	}
	if (in_array("Loesungstext", $diffFields, true)) {
		$solCls .= " darm-field-diff";
	}
	$antCls = "darm-antwort";
	if (darm_multiline_field_is_empty($antwort)) {
		$antCls .= " darm-antwort--empty";
	}
	if (in_array("Antwort", $diffFields, true)) {
		$antCls .= " darm-field-diff";
	}
	$solBody = !darm_multiline_field_is_empty($solText) ? darm_h($solText) : '<em class="darm-empty-hint">—</em>';
	$antBody = !darm_multiline_field_is_empty($antwort) ? darm_h($antwort) : '<em class="darm-empty-hint">—</em>';
	return '<div class="darm-sol-pair darm-row-sync">'
		. '<div class="' . darm_h($solCls) . '"><div class="darm-loesungstext-label">Lösungstext</div><div class="darm-loesungstext-body">' . $solBody . "</div></div>"
		. '<div class="' . darm_h($antCls) . '"><div class="darm-antwort-label">Antwort</div><div class="darm-antwort-body">' . $antBody . "</div></div>"
		. "</div>";
}

/** Rückmeldungstext-Zeile (leer = Platzhalter für Zeilenausrichtung). */
function darm_render_col_rm_text_html(array $row, array $diffFields = array()): string {
	$rmText = darm_preserve_multiline_field($row["RueckmeldungText"] ?? "");
	if (darm_multiline_field_is_empty($rmText)) {
		return '<div class="darm-rm-text darm-rm-text--empty darm-row-sync">&#8203;</div>';
	}
	$cls = "darm-rm-text darm-row-sync";
	if (in_array("RueckmeldungText", $diffFields, true)) {
		$cls .= " darm-field-diff";
	}
	$body = darm_h($rmText);
	return '<div class="' . darm_h($cls) . '"><div class="darm-rm-text-label">Rückmeldung Text</div><div class="darm-rm-text-body">' . $body . "</div></div>";
}

/** Antwortblock-Zeile (immer vorhanden für Höhen-Sync). */
function darm_render_col_antwort_fill_html(array $row, bool $antwortDiff, array $diffFields = array()): string {
	return '<div class="darm-col-fill darm-row-sync">' . darm_render_antworten_html($row, $antwortDiff, $diffFields) . "</div>";
}

/** Vorschau-Spalte «Ergebnis» (rechts) — gleicher Zeilenaufbau wie Review-Spalten. */
function darm_render_merge_result_col_html(?array $row, bool $emptyPlaceholder = true, bool $antwortDiff = false, array $diffFields = array()): string {
	if (!is_array($row) || trim((string)($row["Frage"] ?? "")) === "") {
		if (!$emptyPlaceholder) {
			return "";
		}
		return '<div class="darm-frage darm-frage--placeholder darm-row-sync"><div class="darm-result-empty"><em>Zuerst Vorlage wählen, dann <strong>Anpassen</strong> — die angepasste Version erscheint hier.</em></div></div>'
			. '<div class="darm-meta darm-meta--empty darm-row-sync"></div>'
			. darm_render_col_loesungstext_html(array())
			. darm_render_col_rm_text_html(array())
			. darm_render_col_antwort_fill_html(array(), $antwortDiff);
	}
	$frageCls = "darm-frage darm-row-sync" . (in_array("Frage", $diffFields, true) ? " darm-field-diff" : "");
	return '<div class="' . darm_h($frageCls) . '">' . darm_h((string)$row["Frage"]) . "</div>"
		. darm_render_col_meta_html($row, $diffFields)
		. darm_render_col_loesungstext_html($row, $diffFields)
		. darm_render_col_rm_text_html($row, $diffFields)
		. darm_render_col_antwort_fill_html($row, $antwortDiff, $diffFields);
}

function darm_load_catalog(string $quizLoc, array $assessmentRow) {
	$file = darm_clean_relative_file((string)($assessmentRow["FragenDatei"] ?? ""), "csv");
	$path = $file !== "" ? ($quizLoc . $file) : "";
	$headers = array();
	$rows = array();
	if ($path !== "" && is_file($path)) {
		list($headers, $rows) = darm_read_csv($path);
		$headers = darm_ensure_headers($headers);
	}
	return array(
		"file" => $file,
		"path" => $path,
		"headers" => $headers,
		"rows" => $rows,
		"by_nr" => darm_rows_by_nr($rows, $headers),
		"title" => trim((string)($assessmentRow["Titel"] ?? "")),
		"key" => trim((string)($assessmentRow["Key"] ?? "")),
	);
}

$assessmentHeaders = array("Nr", "Key", "Titel", "Kurzbeschreibung", "Bild", "Einfuehrung", "Erfuellt", "PunkteModus", "DriveLocation", "LernDatei", "FragenDatei", "AnzQuestions", "Audienz", "QuizTime", "Quiz", "Schulung", "AntwortMischen", "FragenBildAnzeigen", "Aktiv", "Locked");
$assessmentPath = $QuizLoc . "AssessmentListe.csv";
list($assessmentCsvHeaders, $assessmentsRaw) = darm_read_csv($assessmentPath);
$assessments = array();
foreach ($assessmentsRaw as $row) {
	$line = array();
	foreach ($assessmentHeaders as $h) {
		$line[$h] = isset($row[$h]) ? (string)$row[$h] : "";
	}
	$assessments[] = $line;
}

$message = "";
$error = "";
$compareMode = false;
$mergeData = null;

$baseKey = trim((string)($_REQUEST["base_quiz"] ?? ""));
$darmRevKeys = darm_rev_keys_from_request($_REQUEST);
$rev1Key = $darmRevKeys["rev1"];
$rev2Key = $darmRevKeys["rev2"];
$rev3Key = $darmRevKeys["rev3"];
$rev4Key = $darmRevKeys["rev4"];
$rev5Key = $darmRevKeys["rev5"];
$darmCsvFileOptions = darm_list_fragen_csv_files($QuizLoc);
$darmOpenDrafts = darm_list_merge_drafts($QuizLoc, $assessments);

if ($_SERVER["REQUEST_METHOD"] === "POST" && (string)($_POST["merge_action"] ?? "") === "delete_draft") {
	$delId = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_POST["draft_id"] ?? "")));
	if ($delId !== "") {
		darm_delete_merge_draft($QuizLoc, $delId);
		$message = "Merge-Entwurf gelöscht.";
	}
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && (string)($_POST["merge_action"] ?? "") === "save_draft") {
	$payloadRaw = (string)($_POST["merge_payload"] ?? "");
	$payload = json_decode($payloadRaw, true);
	$baseKey = trim((string)($_POST["base_quiz"] ?? ""));
	$darmRevKeys = darm_rev_keys_from_request($_POST);
	$draftId = darm_merge_draft_fingerprint($baseKey, $darmRevKeys);
	$totalQ = max(0, (int)($_POST["draft_total_questions"] ?? 0));
	$progress = is_array($payload) ? darm_merge_draft_progress($payload, $totalQ) : array("decided" => 0, "total" => $totalQ);
	$label = trim((string)($_POST["draft_label"] ?? ""));
	if ($label === "") {
		$ba = darm_assessment_by_key($assessments, $baseKey);
		$label = is_array($ba) ? trim((string)$ba["Titel"]) : $baseKey;
	}
	$draftDoc = array(
		"label" => $label,
		"base_quiz" => $baseKey,
		"rev_keys" => $darmRevKeys,
		"decisions" => is_array($payload) ? $payload : array(),
		"progress" => $progress,
		"ui" => array(
			"basis_mode" => trim((string)($_POST["basis_mode"] ?? "new_basis")),
			"basis_fragen_file" => trim((string)($_POST["basis_fragen_file"] ?? "")),
			"promote_base_quiz" => !isset($_POST["promote_base_quiz"]) || (string)$_POST["promote_base_quiz"] === "1",
			"reset_review_fields" => isset($_POST["reset_review_fields"]) && (string)$_POST["reset_review_fields"] === "1",
			"only_diff" => isset($_POST["draft_only_diff"]) && (string)$_POST["draft_only_diff"] === "1",
			"last_nr" => trim((string)($_POST["draft_last_nr"] ?? "")),
		),
	);
	$isAjax = isset($_POST["draft_ajax"]) && (string)$_POST["draft_ajax"] === "1";
	if ($baseKey === "" || $draftId === "") {
		if ($isAjax) {
			header("Content-Type: application/json; charset=UTF-8");
			echo json_encode(array("ok" => false, "error" => "Basis-Quiz fehlt."), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$error = "Entwurf konnte nicht gespeichert werden (Basis-Quiz fehlt).";
	} elseif (!darm_save_merge_draft($QuizLoc, $draftId, $draftDoc)) {
		if ($isAjax) {
			header("Content-Type: application/json; charset=UTF-8");
			echo json_encode(array("ok" => false, "error" => "Schreiben fehlgeschlagen."), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$error = "Entwurf konnte nicht gespeichert werden.";
	} else {
		if ($isAjax) {
			header("Content-Type: application/json; charset=UTF-8");
			echo json_encode(array(
				"ok" => true,
				"draft_id" => $draftId,
				"updated_at" => date("c"),
				"progress" => $progress,
			), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$message = "Merge-Entwurf gespeichert (" . (int)$progress["decided"] . " von " . (int)$progress["total"] . " Fragen bearbeitet).";
		$rev1Key = $darmRevKeys["rev1"];
		$rev2Key = $darmRevKeys["rev2"];
		$rev3Key = $darmRevKeys["rev3"];
		$rev4Key = $darmRevKeys["rev4"];
		$rev5Key = $darmRevKeys["rev5"];
		$_GET["compare"] = "1";
		$darmOpenDrafts = darm_list_merge_drafts($QuizLoc, $assessments);
	}
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && (string)($_POST["merge_action"] ?? "") === "save_merge") {
	$payloadRaw = (string)($_POST["merge_payload"] ?? "");
	$payload = json_decode($payloadRaw, true);
	$basisMode = trim((string)($_POST["basis_mode"] ?? "new_basis"));
	if ($basisMode === "") {
		$basisMode = "new_basis";
	}
	$basisFileRaw = (string)($_POST["basis_fragen_file"] ?? $_POST["target_fragen_file"] ?? "");
	$targetFile = darm_clean_relative_file($basisFileRaw, "csv");
	$promoteBaseQuiz = !isset($_POST["promote_base_quiz"]) || (string)$_POST["promote_base_quiz"] === "1";
	$newQuizKey = preg_replace('/[^A-Za-z0-9_\-]/', "", (string)($_POST["new_quiz_key"] ?? ""));
	$newQuizTitle = trim((string)($_POST["new_quiz_title"] ?? ""));
	$registerQuiz = isset($_POST["register_assessment"]) && (string)$_POST["register_assessment"] === "1";
	$resetReview = isset($_POST["reset_review_fields"]) && (string)$_POST["reset_review_fields"] === "1";
	$baseKey = trim((string)($_POST["base_quiz"] ?? ""));
	$darmRevKeys = darm_rev_keys_from_request($_POST);
	$rev1Key = $darmRevKeys["rev1"];
	$rev2Key = $darmRevKeys["rev2"];
	$rev3Key = $darmRevKeys["rev3"];
	$rev4Key = $darmRevKeys["rev4"];
	$rev5Key = $darmRevKeys["rev5"];
	$newFile = $targetFile;

	if (!is_array($payload) || count($payload) === 0) {
		$error = "Keine Merge-Entscheidungen übermittelt.";
	} elseif ($newFile === "") {
		$error = "Ungültiger Dateiname für die neue Basis (CSV unter data/assess/).";
	} elseif ($basisMode === "replace_basis" && !is_file($QuizLoc . $newFile)) {
		$error = "Aktuelle Basis-CSV existiert nicht — «Neue Basis-Datei» wählen oder Dateinamen prüfen.";
	} else {
		$baseAss = darm_assessment_by_key($assessments, $baseKey);
		if (!is_array($baseAss)) {
			$error = "Basis-Quiz nicht gefunden.";
		} else {
			$sources = array("base" => darm_load_catalog($QuizLoc, $baseAss));
			foreach ($darmRevKeys as $slot => $k) {
				if ($k === "") {
					continue;
				}
				$a = darm_assessment_by_key($assessments, $k);
				if (is_array($a)) {
					$sources[$slot] = darm_load_catalog($QuizLoc, $a);
				}
			}
			$headers = darm_collect_merge_headers($sources);
			$mergedRows = darm_build_merged_rows_from_payload(
				is_array($payload) ? $payload : array(),
				$sources,
				$headers,
				$resetReview
			);
			$outPath = $QuizLoc . $newFile;
			if (function_exists("rkrk_assess_fragenkatalog_before_overwrite") && is_file($outPath)) {
				rkrk_assess_fragenkatalog_before_overwrite($outPath, $QuizLoc);
			}
			if (!darm_write_csv($outPath, $headers, $mergedRows)) {
				$error = "Neue Basis konnte nicht geschrieben werden.";
			} else {
				darm_archive_written_version($outPath, $QuizLoc);
				$msgParts = array(
					"Neue Basis gespeichert: " . $newFile . " (" . count($mergedRows) . " Fragen)",
					"Archiv-Kopie in data/assess/Archiv/ angelegt.",
				);
				if ($promoteBaseQuiz) {
					$assHeaders = count($assessmentCsvHeaders) > 0 ? $assessmentCsvHeaders : $assessmentHeaders;
					if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
						rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
					}
					if (darm_promote_base_quiz_fragen_file($assessments, $baseKey, $newFile, count($mergedRows))
						&& darm_write_csv($assessmentPath, $assHeaders, $assessments)) {
						$msgParts[] = "Basis-Quiz «" . $baseKey . "» verwendet jetzt " . $newFile . ".";
					} else {
						$msgParts[] = "Basis-Quiz «" . $baseKey . "» konnte in der AssessmentListe nicht aktualisiert werden.";
					}
				}
				if ($registerQuiz && $newQuizKey !== "" && $newQuizTitle !== "") {
					$maxNr = 0;
					foreach ($assessments as $a) {
						$maxNr = max($maxNr, (int)($a["Nr"] ?? 0));
					}
					$newLine = $baseAss;
					$newLine["Nr"] = (string)($maxNr + 1);
					$newLine["Key"] = $newQuizKey;
					$newLine["Titel"] = $newQuizTitle;
					$newLine["FragenDatei"] = $newFile;
					$newLine["AnzQuestions"] = (string)count($mergedRows);
					$newLine["Aktiv"] = "Ja";
					$newLine["Locked"] = "Nein";
					$exists = false;
					foreach ($assessments as $a) {
						if (strcasecmp(trim((string)$a["Key"]), $newQuizKey) === 0) {
							$exists = true;
							break;
						}
					}
					if ($exists) {
						$msgParts[] = "AssessmentListe: Key bereits vorhanden — Eintrag nicht angelegt.";
					} else {
						$assessments[] = $newLine;
						$assHeaders = count($assessmentCsvHeaders) > 0 ? $assessmentCsvHeaders : $assessmentHeaders;
						if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
							rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
						}
						if (darm_write_csv($assessmentPath, $assHeaders, $assessments)) {
							$msgParts[] = "AssessmentListe: Eintrag «" . $newQuizKey . "» angelegt.";
						} else {
							$msgParts[] = "AssessmentListe konnte nicht aktualisiert werden.";
						}
					}
				}
				$message = implode(" ", $msgParts);
				darm_delete_merge_draft($QuizLoc, darm_merge_draft_fingerprint($baseKey, $darmRevKeys));
			}
		}
	}
}

if ($error === "" && isset($_GET["compare"]) && (string)$_GET["compare"] === "1") {
	$baseAss = darm_assessment_by_key($assessments, $baseKey);
	if (!is_array($baseAss)) {
		$error = "Bitte ein Basis-Quiz wählen.";
	} else {
		$slots = array(array("id" => "base", "key" => $baseKey, "label" => "Basis"));
		$revNum = 0;
		foreach (darm_review_slot_ids() as $sid) {
			$k = $darmRevKeys[$sid];
			if ($k === "") {
				continue;
			}
			$revNum++;
			$slots[] = array("id" => $sid, "key" => $k, "label" => "Review " . $revNum);
		}
		$catalogs = array();
		$activeSlots = array();
		foreach ($slots as $slot) {
			if ($slot["id"] !== "base" && $slot["key"] === "") {
				continue;
			}
			$a = darm_assessment_by_key($assessments, $slot["key"]);
			if (!is_array($a)) {
				if ($slot["id"] === "base") {
					$error = "Basis-Quiz nicht gefunden.";
				}
				continue;
			}
			$cat = darm_load_catalog($QuizLoc, $a);
			$slot["title"] = $cat["title"] !== "" ? $cat["title"] : $slot["key"];
			$slot["file"] = $cat["file"];
			$slot["map"] = $cat["by_nr"];
			$catalogs[$slot["id"]] = $cat;
			$activeSlots[] = $slot;
		}
		if ($error === "") {
			$allNr = array();
			foreach ($activeSlots as $s) {
				foreach (array_keys($s["map"]) as $nr) {
					$allNr[$nr] = true;
				}
			}
			$nrList = array_keys($allNr);
			usort($nrList, function ($a, $b) {
				$ia = (int)preg_replace('/\D/', '', $a);
				$ib = (int)preg_replace('/\D/', '', $b);
				if ($ia === $ib) {
					return strcmp($a, $b);
				}
				return $ia <=> $ib;
			});
			$questions = array();
			foreach ($nrList as $nr) {
				$hasDiff = darm_question_has_diff_vs_base($activeSlots, $nr);
				$present = array();
				foreach ($activeSlots as $s) {
					$present[$s["id"]] = isset($s["map"][$nr]);
				}
				$questions[] = array("nr" => $nr, "has_diff" => $hasDiff, "present" => $present);
			}
			$baseFragenFile = darm_clean_relative_file((string)$baseAss["FragenDatei"], "csv");
			$defaultNewBasisFile = darm_default_new_basis_filename($baseAss);
			$draftId = darm_merge_draft_fingerprint($baseKey, $darmRevKeys);
			$loadedDraft = darm_load_merge_draft($QuizLoc, $draftId);
			if (!is_array($loadedDraft) || !darm_merge_draft_matches_selection($loadedDraft, $baseKey, $darmRevKeys)) {
				$loadedDraft = null;
			}
			$mergeData = array(
				"base_ass" => $baseAss,
				"base_quiz_key" => $baseKey,
				"slots" => $activeSlots,
				"questions" => $questions,
				"headers" => $catalogs["base"]["headers"],
				"default_new_basis_file" => $defaultNewBasisFile,
				"default_replace_basis_file" => $baseFragenFile,
				"csv_files" => $darmCsvFileOptions,
				"default_extra_key" => preg_replace('/[^A-Za-z0-9_\-]/', "", $baseKey) . "-merged",
				"default_extra_title" => trim((string)$baseAss["Titel"]) . " (Kopie)",
				"draft_id" => $draftId,
				"draft" => $loadedDraft,
				"question_count" => count($questions),
			);
			$compareMode = true;
			if (is_array($loadedDraft) && $message === "") {
				$ts = strtotime((string)($loadedDraft["updated_at"] ?? ""));
				$when = ($ts !== false) ? date("d.m.Y H:i", $ts) : "";
				if (isset($_GET["resume_draft"])) {
					$message = "Entwurf" . ($when !== "" ? " vom " . $when : "") . " wird fortgesetzt.";
				} else {
					$message = "Gespeicherter Entwurf" . ($when !== "" ? " vom " . $when : "") . " wurde geladen.";
				}
			}
		}
	}
}

/** Review-Quiz-Vorschläge zur gleichen Katalog-Familie */
$familyGroups = array();
foreach ($assessments as $a) {
	$f = darm_clean_relative_file((string)($a["FragenDatei"] ?? ""), "csv");
	if ($f === "") {
		continue;
	}
	$stem = darm_fragen_catalog_stem($f);
	if ($stem === "") {
		continue;
	}
	if (!isset($familyGroups[$stem])) {
		$familyGroups[$stem] = array();
	}
	$familyGroups[$stem][] = $a;
}

if ($baseKey === "" && count($assessments) > 0) {
	foreach ($assessments as $a) {
		$k = trim((string)($a["Key"] ?? ""));
		if ($k === "RKRSp-EP") {
			$baseKey = $k;
			break;
		}
	}
}
if ($rev1Key === "" && $baseKey === "RKRSp-EP") {
	$rev1Key = "RKRSp-EP-Roger";
}
if ($rev2Key === "" && $baseKey === "RKRSp-EP") {
	$rev2Key = "RKRSp-EP-Philipp";
}
if ($rev3Key === "" && $baseKey === "RKRSp-EP") {
	$rev3Key = "RKRSp-EP-Simon";
}
if ($rev4Key === "" && $baseKey === "RKRSp-EP") {
	$rev4Key = "RKRSp-EP-Alain";
}

headerpage("Review zusammenführen", false, $folderoffset);
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}
?>
<style>
.darm-wrap{max-width:none;width:100%;margin:0;padding:12px 16px;box-sizing:border-box;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#1f2937}
.darm-card{background:#fff;border:1px solid #d8dfd6;border-radius:12px;padding:14px;margin:12px 0;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.darm-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #7a8f78;border-radius:8px;background:#f4f7f3;color:#1f2937;padding:7px 12px;text-decoration:none;cursor:pointer;font-weight:700;font-size:13px}
.darm-btn:hover{background:#e8f0e6}
.darm-btn-primary{background:var(--brand,#48673A);color:#fff;border-color:var(--brand-dark,#3d5632)}
.darm-btn-primary:hover{filter:brightness(1.05)}
.darm-msg{padding:10px 12px;border-radius:8px;margin:10px 0}.darm-ok{background:#e8f4e5;border:1px solid #9ab08f}.darm-err{background:#fde9e9;border:1px solid #e3abab}
.darm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;align-items:end}
.darm-grid label{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:700;color:#334155}
.darm-grid select,.darm-grid input{border:1px solid #cbd5e1;border-radius:8px;padding:8px;font:inherit}
.darm-q{border:1px solid #d8dfd6;border-radius:12px;margin:14px 0;background:#fff;overflow:visible;max-width:100%}
.darm-cols-wrap{overflow-x:auto;max-width:100%;-webkit-overflow-scrolling:touch}
.darm-q.diff{border-color:#f59e0b;box-shadow:0 0 0 1px rgba(245,158,11,.25)}
.darm-q-head{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;padding:10px 12px;background:#f8faf8;border-bottom:1px solid #e5e7eb}
.darm-q-nr{font-weight:900;font-size:15px;color:var(--brand-dark,#48673A)}
.darm-pick{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.darm-pick label{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:4px 8px;border-radius:999px;border:1px solid #d8dfd6;background:#fff;cursor:pointer}
.darm-pick input{margin:0}
.darm-pick label:has(input:checked){background:#eef6ee;border-color:#7a9e78}
.darm-add-pick{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:8px 12px;background:#f0f7ff;border-top:1px solid #dbeafe;font-size:12px}
.darm-add-pick>span{font-weight:700;color:#1e40af;margin-right:4px}
.darm-add-pick label{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;border:1px solid #bfdbfe;background:#fff;cursor:pointer}
.darm-add-pick label:has(input:checked){background:#dbeafe;border-color:#3b82f6}
.darm-q.has-additions{box-shadow:0 0 0 1px rgba(59,130,246,.2)}
.darm-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr));grid-template-rows:repeat(6,auto);gap:0;align-items:start;min-width:0;width:100%}
.darm-col{padding:10px;border-right:1px solid #eef2f7;font-size:12px;line-height:1.4;display:grid;grid-template-rows:subgrid;grid-row:1/-1;align-content:start;min-width:0;max-width:100%;box-sizing:border-box;overflow-wrap:anywhere;word-break:break-word}
.darm-col-inner{display:contents}
.darm-col-title{grid-row:1;margin-bottom:0}
.darm-frage{grid-row:2}
.darm-meta{grid-row:3}
.darm-sol-pair{grid-row:4;display:grid;grid-template-columns:1fr 1fr;gap:6px;align-items:stretch;min-width:0;max-width:100%}
@media (max-width:520px){.darm-sol-pair{grid-template-columns:1fr}}
.darm-rm-text{grid-row:5}
.darm-col-fill{grid-row:6}
.darm-col-fill{display:flex;flex-direction:column;justify-content:flex-start}
.darm-antworten{width:100%;max-width:100%;box-sizing:border-box;overflow:hidden;overflow-wrap:anywhere;word-break:break-word}
.darm-rm-text{color:#334155;font-size:12px;line-height:1.45;max-width:100%;overflow-wrap:anywhere;word-break:break-word}
.darm-rm-text-label,.darm-loesungstext-label,.darm-antwort-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin:0 0 4px}
.darm-rm-text-body,.darm-loesungstext-body,.darm-antwort-body{white-space:pre-wrap}
.darm-loesungstext,.darm-antwort{color:#1e3a5f;font-size:12px;line-height:1.45;max-width:100%;overflow-wrap:anywhere;word-break:break-word;margin:0;padding:8px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb;box-sizing:border-box}
.darm-loesungstext--empty,.darm-antwort--empty,.darm-rm-text--empty,.darm-meta--empty,.darm-antworten--empty{min-height:0}
.darm-empty-hint{color:#94a3b8;font-style:italic}
.darm-edit-vs-base{font-size:11px;line-height:1.4;color:#475569;margin:0 0 6px;padding:6px 8px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0}
.darm-edit-vs-base--diff{background:#fff7ed;border-color:#fdba74;color:#9a3412}
.darm-edit-vs-base-label{font-weight:800;margin-right:4px}
.darm-edit-vs-base-text{white-space:pre-wrap}
.darm-vs-base{margin-top:6px;font-size:11px;font-weight:600;color:#9a3412;line-height:1.35}
.darm-field-diff{background:#fff7ed;border-radius:6px;box-shadow:inset 0 0 0 1px #fdba74;padding:2px 4px}
.darm-col-changed{background:#fffbeb}
.darm-col-changed .darm-col-title{color:#9a3412}
.darm-frage--placeholder .darm-result-empty{margin:0}
.darm-col-result{background:#f0fdf4;border-left:2px solid var(--brand,#48673A);position:sticky;right:0;z-index:2;box-shadow:-4px 0 8px rgba(0,0,0,.04);min-width:0;max-width:100%;overflow:hidden}
.darm-col-result .darm-col-title{color:var(--brand-dark,#48673A)}
.darm-result-empty{font-size:12px;color:#64748b;line-height:1.45}
.darm-pick-hint{font-size:11px;color:#64748b;margin:0 0 6px;width:100%}
.darm-btn-anpassen{background:#eef6ee;border-color:#7a9e78}
.darm-col-missing{opacity:.45;background:#fafafa}
.darm-col-title{font-weight:800;font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:#64748b;margin:0 0 6px;max-width:100%;overflow-wrap:anywhere;word-break:break-word}
.darm-frage{font-weight:700;font-size:13px;color:#1f2937;margin:0 0 6px;max-width:100%;overflow-wrap:anywhere;word-break:break-word}
.darm-meta{display:flex;flex-wrap:wrap;gap:4px;margin:0 0 6px;align-items:flex-start}
.darm-pill{padding:2px 6px;border-radius:999px;background:#eef6ee;border:1px solid #d8dfd6;font-size:11px}
.darm-pill.warn{background:#fff7ed;border-color:#fdba74;color:#9a3412}
.darm-pill.rev-wichtig{background:#fef3c7}
.darm-pill.rev-gut{background:#dcfce7}
.darm-pill.rev-überarbeiten,.darm-pill.rev-uberarbeiten{background:#fee2e2}
.darm-antworten{margin-top:8px;padding:8px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb}
.darm-antworten.darm-antworten-diff{background:#fff7ed;border-color:#fdba74}
.darm-antworten-label{font-size:11px;font-weight:800;color:#64748b;margin-bottom:4px}
.darm-options{margin:0;padding-left:18px;max-width:100%;box-sizing:border-box}
.darm-options li{margin:2px 0;overflow-wrap:anywhere;word-break:break-word;padding-right:2px}
.darm-opt-correct{color:#0f5132;font-weight:800}
.darm-opt-letter{font-weight:800;margin-right:2px}
.darm-loes-kurz,.darm-antwort-text{margin-top:6px;font-size:11px;max-width:100%;overflow-wrap:anywhere;word-break:break-word}
.darm-antwort-text--leer{color:#94a3b8;font-style:italic;font-weight:400}
.darm-target-modes{display:flex;flex-wrap:wrap;gap:12px;margin:8px 0 10px;font-size:13px}
.darm-target-file-row{display:grid;grid-template-columns:minmax(200px,1fr);gap:8px;align-items:end}
@media(min-width:700px){.darm-target-file-row{grid-template-columns:minmax(220px,1fr) minmax(280px,2fr)}}
.darm-edit-panel{display:none;padding:12px;border-top:1px solid #e5e7eb;background:#f8fafc}
.darm-edit-panel[hidden]{display:none!important}
.darm-edit-panel.open{display:block}
.darm-edit-done{margin-top:10px;display:flex;justify-content:flex-end}
.darm-edit-grid{display:grid;gap:8px}
.darm-edit-grid label{display:grid;gap:3px;font-size:10px;font-weight:600;color:#64748b;line-height:1.3}
.darm-edit-grid textarea,.darm-edit-grid input,.darm-edit-grid select{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:8px;padding:6px 8px;font-family:inherit;font-size:13px;font-weight:400;line-height:1.45;color:#1f2937;background:#fff}
.darm-sticky{position:sticky;bottom:0;z-index:50;background:#fff;border-top:2px solid var(--brand,#48673A);padding:12px;margin:0 -12px -12px;border-radius:0 0 12px 12px}
.darm-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px}
.darm-filter-only-diff{margin-left:auto}
.darm-q.decided{border-left:4px solid var(--brand,#48673A)}
.darm-draft-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 12px;background:#eef6ee;border:1px solid #c5d9c0;border-radius:10px;margin-bottom:12px;font-size:13px}
.darm-draft-bar strong{color:var(--brand-dark,#48673A)}
.darm-draft-list{margin:12px 0 0;padding:0;list-style:none}
.darm-draft-list li{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px 0;border-bottom:1px solid #e5e7eb}
.darm-draft-list li:last-child{border-bottom:0}
@media(max-width:900px){.darm-cols{grid-template-columns:1fr}.darm-col{border-right:0;border-bottom:1px solid #eef2f7}}
#rkrk-portal-nav-slot{max-width:none;width:100%;margin:0 0 14px;padding:0 16px;box-sizing:border-box}
</style>
<?php
if (function_exists("rkrk_assess_portal_nav_echo")) {
	ob_start();
	rkrk_assess_portal_nav_echo(array(
		"btn_class" => "darm-btn",
		"schulung_mode" => false,
		"is_admin" => true,
		"is_instr" => false,
		"selected_quiz" => $baseKey,
	));
	$__nav = ob_get_clean();
	if ($__nav !== "") {
		echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $__nav . "</div>\n";
	}
}
?>
<div class="darm-wrap">
	<div class="darm-card">
		<h2 style="margin:0 0 6px;">Review zusammenführen</h2>
		<p style="margin:0;font-size:13px;color:#475569;">Pro Frage: <strong>Vorlage wählen</strong> (Radio) → <strong>Anpassen</strong> (Editor) → <strong>Fertig</strong> (Editor einklappen) → Ergebnis rechts. Oder nur Vorlage ohne Anpassen. <strong>Zusätzlich</strong> per Checkbox. Entwürfe: <code>merge-drafts/</code>.</p>
		<p style="margin:8px 0 0;font-size:13px;"><a class="darm-btn" href="<?php echo darm_h(rkrk_assess_append_setting_href("rkrk_digitalassess_review.php?ADMIN")); ?>">← Fragen Review</a>
		<a class="darm-btn" href="<?php echo darm_h(rkrk_assess_append_setting_href("rkrk_digitalassess_admin.php?ADMIN")); ?>">Fragen Manager</a></p>
	</div>

	<?php if ($message !== "") { ?><div class="darm-msg darm-ok"><?php echo darm_h($message); ?></div><?php } ?>
	<?php if ($error !== "") { ?><div class="darm-msg darm-err"><?php echo darm_h($error); ?></div><?php } ?>

	<?php if (!$compareMode) { ?>
	<?php if (count($darmOpenDrafts) > 0) { ?>
	<div class="darm-card">
		<h3 style="margin:0 0 8px;">Offene Merge-Entwürfe</h3>
		<p style="margin:0 0 10px;font-size:13px;color:#475569;">Gestartete Arbeit fortsetzen — gleiche Quiz-Auswahl und Ihre bisherigen Entscheidungen werden geladen.</p>
		<ul class="darm-draft-list">
		<?php foreach ($darmOpenDrafts as $dr) {
			$resumeQ = darm_merge_draft_resume_query(array(
				"base_quiz" => (string)$dr["base_quiz"],
				"rev_keys" => isset($dr["rev_keys"]) && is_array($dr["rev_keys"]) ? $dr["rev_keys"] : array(),
			));
			$resumeHref = rkrk_assess_append_setting_href("rkrk_digitalassess_review_merge.php?" . http_build_query($resumeQ));
			$when = "";
			$ts = strtotime((string)($dr["updated_at"] ?? ""));
			if ($ts !== false) {
				$when = date("d.m.Y H:i", $ts);
			}
			?>
			<li>
				<strong><?php echo darm_h((string)$dr["label"]); ?></strong>
				<span style="color:#64748b;"><?php echo (int)$dr["decided"]; ?> / <?php echo (int)$dr["total"]; ?> bearbeitet<?php echo $when !== "" ? " · " . darm_h($when) : ""; ?></span>
				<a class="darm-btn darm-btn-primary" href="<?php echo darm_h($resumeHref); ?>">Fortsetzen</a>
				<form method="post" style="display:inline;margin:0;" onsubmit="return confirm('Entwurf wirklich löschen?');">
					<input type="hidden" name="merge_action" value="delete_draft">
					<input type="hidden" name="draft_id" value="<?php echo darm_h((string)$dr["id"]); ?>">
					<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
						rkrk_assess_echo_setting_hidden_input();
					} ?>
					<button type="submit" class="darm-btn">Löschen</button>
				</form>
			</li>
		<?php } ?>
		</ul>
	</div>
	<?php } ?>
	<div class="darm-card">
		<form method="get" class="darm-grid">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
				rkrk_assess_echo_setting_hidden_input();
			} ?>
			<input type="hidden" name="compare" value="1">
			<label>Basis-Quiz (Referenz)
				<select name="base_quiz" required>
					<option value="">— wählen —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $baseKey) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"] . " (" . $a["FragenDatei"] . ")") . "</option>";
					} ?>
				</select>
			</label>
			<label>Review 1
				<select name="rev1_quiz" required>
					<option value="">— wählen —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $rev1Key) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"]) . "</option>";
					} ?>
				</select>
			</label>
			<label>Review 2 <span style="font-weight:400;color:#64748b;">(optional)</span>
				<select name="rev2_quiz">
					<option value="">— keine —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $rev2Key) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"]) . "</option>";
					} ?>
				</select>
			</label>
			<label>Review 3 <span style="font-weight:400;color:#64748b;">(optional)</span>
				<select name="rev3_quiz">
					<option value="">— keine —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $rev3Key) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"]) . "</option>";
					} ?>
				</select>
			</label>
			<label>Review 4 <span style="font-weight:400;color:#64748b;">(optional)</span>
				<select name="rev4_quiz">
					<option value="">— keine —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $rev4Key) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"]) . "</option>";
					} ?>
				</select>
			</label>
			<label>Review 5 <span style="font-weight:400;color:#64748b;">(optional)</span>
				<select name="rev5_quiz">
					<option value="">— keine —</option>
					<?php foreach ($assessments as $a) {
						$k = trim((string)$a["Key"]);
						if ($k === "") {
							continue;
						}
						$sel = ($k === $rev5Key) ? " selected" : "";
						echo "<option value='" . darm_h($k) . "'" . $sel . ">" . darm_h($k . " — " . $a["Titel"]) . "</option>";
					} ?>
				</select>
			</label>
			<div style="grid-column:1/-1;">
				<button type="submit" class="darm-btn darm-btn-primary">Vergleichen</button>
			</div>
		</form>
		<?php if (count($familyGroups) > 0) { ?>
		<details style="margin-top:14px;font-size:13px;">
			<summary style="cursor:pointer;font-weight:700;">Katalog-Familien (Vorschlag)</summary>
			<ul style="margin:8px 0 0 18px;padding:0;">
			<?php foreach ($familyGroups as $stem => $members) {
				if (count($members) < 2) {
					continue;
				}
				echo "<li><strong>" . darm_h($stem) . "</strong>: ";
				$parts = array();
				foreach ($members as $m) {
					$parts[] = darm_h((string)$m["Key"]);
				}
				echo implode(", ", $parts) . "</li>";
			} ?>
			</ul>
		</details>
		<?php } ?>
	</div>
	<?php } else {
		$md = $mergeData;
		$slots = $md["slots"];
		$darmPrioOptions = array("0", "1", "2", "3", "4");
		$darmPriorityHelp = "Prioritaet 1 = optional/geringe Pruefungsrelevanz, 2 = relevant, 3 = wichtig, 4 = kritisch und sollte moeglichst immer abgefragt werden.";
		$darmStatusOptions = function_exists("rkrk_assess_question_status_options")
			? rkrk_assess_question_status_options()
			: array("neu", "prüfen", "geprüft", "aktiv", "inaktiv");
		if (!in_array("aktiv", $darmStatusOptions, true)) {
			$darmStatusOptions[] = "aktiv";
		}
		$darmReviewOptions = array("offen", "wichtig", "gut", "überarbeiten", "nicht relevant");
		$baseRowByNr = array();
		foreach ($slots as $s) {
			if (($s["id"] ?? "") === "base") {
				$baseRowByNr = isset($s["map"]) && is_array($s["map"]) ? $s["map"] : array();
				break;
			}
		}
		?>
	<div class="darm-card darm-toolbar">
		<?php
		$backQs = array("ADMIN" => "", "base_quiz" => $baseKey);
		foreach ($darmRevKeys as $sid => $kv) {
			if ($kv !== "") {
				$backQs[$sid . "_quiz"] = $kv;
			}
		}
		?>
		<a class="darm-btn" href="<?php echo darm_h(rkrk_assess_append_setting_href("rkrk_digitalassess_review_merge.php?" . http_build_query($backQs))); ?>">← Auswahl ändern</a>
		<button type="button" class="darm-btn" id="darm-bulk-base">Alle: Basis</button>
		<?php foreach ($slots as $s) {
			if ($s["id"] === "base") {
				continue;
			}
			echo '<button type="button" class="darm-btn darm-bulk-src" data-src="' . darm_h($s["id"]) . '">Alle: ' . darm_h($s["label"]) . "</button>";
		} ?>
		<label class="darm-filter-only-diff" style="font-size:12px;font-weight:600;"><input type="checkbox" id="darm-only-diff"> Nur Abweichungen</label>
		<button type="button" class="darm-btn" id="darm-next-diff">Nächste Abweichung</button>
		<span style="font-size:12px;color:#64748b;"><?php echo count($md["questions"]); ?> Fragen · Basis: <?php echo darm_h((string)$md["base_ass"]["FragenDatei"]); ?></span>
	</div>

	<div class="darm-draft-bar" id="darm-draft-bar">
		<strong>Entwurf</strong>
		<span id="darm-draft-progress">Fortschritt: —</span>
		<span id="darm-draft-saved" style="color:#64748b;"></span>
		<button type="button" class="darm-btn" id="darm-save-draft-btn">Entwurf speichern</button>
		<?php if (is_array($md["draft"])) { ?>
		<span class="darm-pill">gespeicherter Stand wird geladen</span>
		<?php } ?>
	</div>

	<form method="post" id="darm-merge-form">
		<input type="hidden" name="merge_action" id="darm-merge-action" value="save_merge">
		<input type="hidden" name="merge_payload" id="darm-merge-payload" value="">
		<input type="hidden" name="draft_total_questions" value="<?php echo (int)$md["question_count"]; ?>">
		<input type="hidden" name="draft_last_nr" id="darm-draft-last-nr" value="">
		<input type="hidden" name="draft_only_diff" id="darm-draft-only-diff" value="0">
		<input type="hidden" name="base_quiz" value="<?php echo darm_h($baseKey); ?>">
		<input type="hidden" name="rev1_quiz" value="<?php echo darm_h($rev1Key); ?>">
		<input type="hidden" name="rev2_quiz" value="<?php echo darm_h($rev2Key); ?>">
		<input type="hidden" name="rev3_quiz" value="<?php echo darm_h($rev3Key); ?>">
		<input type="hidden" name="rev4_quiz" value="<?php echo darm_h($rev4Key); ?>">
		<input type="hidden" name="rev5_quiz" value="<?php echo darm_h($rev5Key); ?>">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
			rkrk_assess_echo_setting_hidden_input();
		} ?>

		<?php foreach ($md["questions"] as $qi => $qinfo) {
			$nr = (string)$qinfo["nr"];
			$diffClass = !empty($qinfo["has_diff"]) ? " diff" : "";
			$fragePreview = "";
			if (isset($slots[0]["map"][$nr]["Frage"])) {
				$fragePreview = trim((string)$slots[0]["map"][$nr]["Frage"]);
			}
			foreach ($slots as $s) {
				if ($fragePreview !== "") {
					break;
				}
				if (isset($s["map"][$nr]["Frage"])) {
					$fragePreview = trim((string)$s["map"][$nr]["Frage"]);
				}
			}
			if ($fragePreview === "") {
				$fragePreview = "(ohne Fragentext)";
			}
			$rowData = array();
			foreach ($slots as $s) {
				$rowData[$s["id"]] = isset($s["map"][$nr]) ? $s["map"][$nr] : null;
			}
			$baseRow = isset($baseRowByNr[$nr]) ? $baseRowByNr[$nr] : null;
			$hasBaseRow = is_array($baseRow);
			?>
		<div class="darm-q<?php echo $diffClass; ?>" data-nr="<?php echo darm_h($nr); ?>" data-has-diff="<?php echo !empty($qinfo["has_diff"]) ? "1" : "0"; ?>" data-rows="<?php echo darm_h(json_encode($rowData, JSON_UNESCAPED_UNICODE)); ?>">
			<div class="darm-q-head">
				<span class="darm-q-nr">Nr <?php echo darm_h($nr); ?></span>
				<span style="flex:1;min-width:120px;font-size:13px;color:#475569;"><?php
					$fpShort = $fragePreview;
					if (function_exists("mb_strlen") && mb_strlen($fpShort, "UTF-8") > 120) {
						$fpShort = mb_substr($fpShort, 0, 117, "UTF-8") . "…";
					} elseif (strlen($fpShort) > 120) {
						$fpShort = substr($fpShort, 0, 117) . "…";
					}
					echo darm_h($fpShort);
				?></span>
				<div class="darm-pick">
					<span class="darm-pick-hint">1. Bessere Vorlage wählen · 2. <strong>Anpassen</strong> (übernimmt die gewählte Vorlage in den Editor)</span>
					<?php
					$firstPick = true;
					foreach ($slots as $s) {
						if ($s["id"] === "base" && !$hasBaseRow) {
							continue;
						}
						if ($s["id"] !== "base" && !isset($s["map"][$nr])) {
							continue;
						}
						$chk = $firstPick ? " checked" : "";
						$firstPick = false;
						?>
					<label><input type="radio" class="darm-pick-radio" name="pick_<?php echo darm_h($nr); ?>" value="<?php echo darm_h($s["id"]); ?>"<?php echo $chk; ?>> <?php echo darm_h($s["label"]); ?></label>
					<?php } ?>
					<button type="button" class="darm-btn darm-btn-anpassen darm-anpassen-btn" style="padding:4px 10px;font-size:12px;">Anpassen</button>
				</div>
			</div>
			<?php
			$hasAddOptions = false;
			foreach ($slots as $s) {
				if ($s["id"] === "base") {
					continue;
				}
				if (isset($s["map"][$nr])) {
					$hasAddOptions = true;
					break;
				}
			}
			if ($hasAddOptions) { ?>
			<div class="darm-add-pick" data-add-nr="<?php echo darm_h($nr); ?>">
				<span>Zusätzlich (nicht ersetzen):</span>
				<?php foreach ($slots as $s) {
					if ($s["id"] === "base" || !isset($s["map"][$nr])) {
						continue;
					}
					?>
				<label><input type="checkbox" name="add_<?php echo darm_h($nr); ?>[]" value="<?php echo darm_h($s["id"]); ?>" class="darm-add-cb"> <?php echo darm_h($s["label"]); ?></label>
				<?php } ?>
			</div>
			<?php } ?>
			<?php $darmColCount = count($slots) + 1; ?>
			<div class="darm-cols-wrap">
			<div class="darm-cols" style="--darm-col-count:<?php echo (int)$darmColCount; ?>;">
				<?php foreach ($slots as $s) {
					$row = isset($s["map"][$nr]) ? $s["map"][$nr] : null;
					$miss = !is_array($row);
					$isBaseCol = (($s["id"] ?? "") === "base");
					$diffVsBase = (!$isBaseCol && !$miss && is_array($baseRow))
						? darm_diff_fields_vs_base($baseRow, $row)
						: array();
					$colAntwortDiff = in_array("Loesungsauswahl", $diffVsBase, true)
						|| in_array("Loesung", $diffVsBase, true)
						|| in_array("Antwort", $diffVsBase, true);
					$frageCls = "darm-frage darm-row-sync";
					if (!$miss && in_array("Frage", $diffVsBase, true)) {
						$frageCls .= " darm-field-diff";
					}
					?>
				<div class="darm-col<?php echo $miss ? " darm-col-missing" : ""; ?><?php echo (!$miss && count($diffVsBase) > 0) ? " darm-col-changed" : ""; ?>">
					<div class="darm-col-title darm-row-sync"><?php echo darm_h($s["label"]); ?> · <?php echo darm_h($s["file"]); ?><?php echo darm_render_vs_base_summary_html($diffVsBase); ?></div>
					<div class="darm-col-inner">
					<?php if ($miss) { ?>
						<div class="darm-frage darm-frage--missing darm-row-sync"><em>Frage fehlt in diesem Katalog</em></div>
						<div class="darm-meta darm-meta--empty darm-row-sync"></div>
						<?php echo darm_render_col_loesungstext_html(array()); ?>
						<?php echo darm_render_col_rm_text_html(array()); ?>
						<?php echo darm_render_col_antwort_fill_html(array(), false); ?>
					<?php } else { ?>
						<div class="<?php echo darm_h($frageCls); ?>"><?php echo darm_h((string)$row["Frage"]); ?></div>
						<?php
						echo darm_render_col_meta_html($row, $diffVsBase);
						echo darm_render_col_loesungstext_html($row, $diffVsBase);
						echo darm_render_col_rm_text_html($row, $diffVsBase);
						echo darm_render_col_antwort_fill_html($row, $colAntwortDiff, $diffVsBase);
						?>
					<?php } ?>
					</div>
				</div>
				<?php } ?>
				<div class="darm-col darm-col-result" data-result-col="<?php echo darm_h($nr); ?>">
					<div class="darm-col-title darm-row-sync">Ergebnis (neue Version)</div>
					<div class="darm-col-inner darm-result-body" data-result-body="<?php echo darm_h($nr); ?>">
						<?php echo darm_render_merge_result_col_html(null, true, false); ?>
					</div>
				</div>
			</div>
			</div>
			<div class="darm-edit-panel" data-edit-panel="<?php echo darm_h($nr); ?>" hidden>
				<div class="darm-edit-grid">
					<label>Frage
						<textarea rows="3" data-edit-field="Frage" placeholder=""></textarea>
					</label>
					<label>Antworten (mit | trennen)
						<textarea rows="2" data-edit-field="Loesungsauswahl" placeholder=""></textarea>
					</label>
					<label>Lösung
						<input type="text" data-edit-field="Loesung" value="" placeholder="z.&nbsp;B. A|B|C">
					</label>
					<label>Lösungstext
						<div class="darm-edit-vs-base" data-edit-vs-base="Loesungstext" hidden>
							<span class="darm-edit-vs-base-label">Basis-Lösungstext:</span>
							<span class="darm-edit-vs-base-text"></span>
						</div>
						<textarea rows="4" data-edit-field="Loesungstext" placeholder="Erklärung / Musterlösung"></textarea>
					</label>
					<label>Antwort
						<div class="darm-edit-vs-base" data-edit-vs-base="Antwort" hidden>
							<span class="darm-edit-vs-base-label">Basis-Antwort:</span>
							<span class="darm-edit-vs-base-text"></span>
						</div>
						<textarea rows="2" data-edit-field="Antwort" placeholder=""></textarea>
					</label>
					<label title="<?php echo darm_h($darmPriorityHelp); ?>">Priorität
						<?php echo darm_option_select("", $darmPrioOptions, "Prioritaet"); ?>
					</label>
					<label>Status
						<?php echo darm_option_select("aktiv", $darmStatusOptions, "Status"); ?>
					</label>
					<label>Rückmeldung
						<?php echo darm_option_select("offen", $darmReviewOptions, "Rueckmeldung"); ?>
					</label>
					<label>Rückmeldung Text
						<textarea rows="3" data-edit-field="RueckmeldungText" placeholder=""></textarea>
					</label>
					<label>Bereich
						<input type="text" data-edit-field="Bereich" value="" placeholder="">
					</label>
				</div>
				<div class="darm-edit-done">
					<button type="button" class="darm-btn darm-anpassen-done-btn">Fertig (Editor schliessen)</button>
				</div>
			</div>
		</div>
		<?php } ?>

		<div class="darm-card">
			<div class="darm-sticky">
				<h3 style="margin:0 0 10px;">Neue Basis speichern</h3>
				<p style="margin:0 0 10px;font-size:13px;color:#475569;">Das Merge-Ergebnis wird der neue Master-Katalog. Review-CSV der Einzelprüfer bleiben unverändert.</p>
				<div class="darm-target-modes">
					<label><input type="radio" name="basis_mode" value="new_basis" id="darm-basis-new" checked> Neue Basis-Datei (empfohlen, z.&nbsp;B. <code><?php echo darm_h((string)$md["default_new_basis_file"]); ?></code>)</label>
					<label><input type="radio" name="basis_mode" value="replace_basis" id="darm-basis-replace"> Aktuelle Basis-CSV ersetzen (<code><?php echo darm_h((string)$md["default_replace_basis_file"]); ?></code>, Vorgänger → Archiv)</label>
				</div>
				<div class="darm-target-file-row">
					<label>Vorlage / vorhandene CSV
						<select id="darm-basis-pick">
							<option value="">— Dateiname unten —</option>
							<?php foreach ((array)$md["csv_files"] as $cf) {
								echo "<option value='" . darm_h($cf) . "'>" . darm_h($cf) . "</option>";
							} ?>
						</select>
					</label>
					<label>Basis-Datei (relativ zu data/assess/)
						<input type="text" name="basis_fragen_file" id="darm-basis-file" value="<?php echo darm_h((string)$md["default_new_basis_file"]); ?>" required spellcheck="false" autocomplete="off">
					</label>
				</div>
				<p style="margin:6px 0 10px;font-size:12px;color:#64748b;">Nach dem Speichern: Archiv-Kopie der geschriebenen Basis. Bei «Basis-Quiz umstellen» zeigt <strong><?php echo darm_h((string)$md["base_quiz_key"]); ?></strong> künftig auf diese Datei.</p>
				<div style="margin:10px 0;display:flex;flex-wrap:wrap;gap:14px;font-size:13px;">
					<label><input type="checkbox" name="promote_base_quiz" value="1" checked> <strong>Basis-Quiz «<?php echo darm_h((string)$md["base_quiz_key"]); ?>»</strong> auf diese Basis-Datei umstellen (AssessmentListe)</label>
					<label><input type="checkbox" name="reset_review_fields" value="1" checked> Review-Felder in der neuen Basis auf «offen» setzen</label>
				</div>
				<details style="margin:8px 0 12px;font-size:13px;">
					<summary style="cursor:pointer;font-weight:700;">Optional: zusätzlicher Quiz-Eintrag</summary>
					<div class="darm-grid" style="margin-top:10px;">
						<label>Zusätzlicher Quiz-Key
							<input type="text" name="new_quiz_key" value="<?php echo darm_h((string)$md["default_extra_key"]); ?>" pattern="[A-Za-z0-9_\-]+">
						</label>
						<label>Titel
							<input type="text" name="new_quiz_title" value="<?php echo darm_h((string)$md["default_extra_title"]); ?>">
						</label>
					</div>
					<label style="display:block;margin-top:8px;"><input type="checkbox" name="register_assessment" value="1"> Zusätzlichen Eintrag in AssessmentListe anlegen (nicht die neue Basis)</label>
				</details>
				<button type="submit" class="darm-btn darm-btn-primary" id="darm-save-btn">Neue Basis speichern (abschliessen)</button>
			</div>
		</div>
	</form>
	<script>
	(function(){
		var form = document.getElementById("darm-merge-form");
		var mergeActionEl = document.getElementById("darm-merge-action");
		var payloadEl = document.getElementById("darm-merge-payload");
		var draftProgressEl = document.getElementById("darm-draft-progress");
		var draftSavedEl = document.getElementById("darm-draft-saved");
		var draftLastNrEl = document.getElementById("darm-draft-last-nr");
		var draftOnlyDiffEl = document.getElementById("darm-draft-only-diff");
		var questionTotal = <?php echo (int)$md["question_count"]; ?>;
		var savedDraft = <?php echo json_encode(isset($md["draft"]) && is_array($md["draft"]) ? $md["draft"] : null, JSON_UNESCAPED_UNICODE); ?>;
		var headers = <?php echo json_encode($md["headers"], JSON_UNESCAPED_UNICODE); ?>;
		var defaultNewBasisFile = <?php echo json_encode((string)$md["default_new_basis_file"], JSON_UNESCAPED_UNICODE); ?>;
		var defaultReplaceBasisFile = <?php echo json_encode((string)$md["default_replace_basis_file"], JSON_UNESCAPED_UNICODE); ?>;
		var basisFileInput = document.getElementById("darm-basis-file");
		var basisPick = document.getElementById("darm-basis-pick");
		var basisNew = document.getElementById("darm-basis-new");
		var basisReplace = document.getElementById("darm-basis-replace");

		function applyBasisMode(){
			if(!basisFileInput) return;
			if(basisReplace && basisReplace.checked){
				if(defaultReplaceBasisFile) basisFileInput.value = defaultReplaceBasisFile;
				if(basisPick && defaultReplaceBasisFile) basisPick.value = defaultReplaceBasisFile;
			} else if(basisNew && basisNew.checked){
				basisFileInput.value = defaultNewBasisFile || "";
				if(basisPick) basisPick.value = "";
			}
		}
		if(basisNew) basisNew.addEventListener("change", applyBasisMode);
		if(basisReplace) basisReplace.addEventListener("change", applyBasisMode);
		if(basisPick) basisPick.addEventListener("change", function(){
			if(basisPick.value && basisFileInput) basisFileInput.value = basisPick.value;
		});
		applyBasisMode();

		function qCards(){ return Array.prototype.slice.call(document.querySelectorAll(".darm-q")); }

		function getRowFromCard(card, src){
			var raw = card.getAttribute("data-rows");
			if(!raw) return null;
			var data;
			try{ data = JSON.parse(raw); }catch(e){ return null; }
			if(src === "custom") return null;
			return data[src] || null;
		}

		function collectCustom(card){
			var panel = card.querySelector(".darm-edit-panel");
			if(!panel) return null;
			var templateSrc = getCheckedSource(card);
			if(templateSrc === "custom") templateSrc = "base";
			var templateRow = getRowFromCard(card, templateSrc) || getRowFromCard(card, "base");
			var row = {};
			headers.forEach(function(h){
				row[h] = (templateRow && templateRow[h] != null) ? String(templateRow[h]) : "";
			});
			row.Nr = card.getAttribute("data-nr") || "";
			panel.querySelectorAll("[data-edit-field]").forEach(function(el){
				var f = el.getAttribute("data-edit-field");
				if(!f) return;
				var val = el.value;
				if(multilineMergeFields.indexOf(f) >= 0) val = preserveMultilineField(val);
				row[f] = val;
			});
			return row;
		}

		function collectAdds(card, nr){
			var adds = [];
			card.querySelectorAll('input[name="add_'+nr+'[]"]:checked').forEach(function(cb){
				if(cb.value) adds.push(cb.value);
			});
			return adds;
		}

		function getCheckedSource(card){
			var nr = card.getAttribute("data-nr");
			var pick = card.querySelector('input[name="pick_'+nr+'"]:checked');
			return pick ? pick.value : "base";
		}

		function buildPayload(){
			var out = [];
			qCards().forEach(function(card){
				var nr = card.getAttribute("data-nr");
				var src = card.getAttribute("data-custom-active") === "1" ? "custom" : getCheckedSource(card);
				var item = { nr: nr, source: src, add: collectAdds(card, nr) };
				if(src === "custom"){
					item.custom = collectCustom(card);
					item.template_source = getCheckedSource(card);
				}
				out.push(item);
			});
			return out;
		}

		function itemIsDecided(it){
			if(!it) return false;
			if(it.source && it.source !== "base") return true;
			if(it.source === "custom") return true;
			return Array.isArray(it.add) && it.add.length > 0;
		}

		function countDecided(payload){
			var n = 0;
			payload.forEach(function(it){
				if(itemIsDecided(it)) n++;
			});
			return n;
		}

		function updateProgressUi(payload){
			if(!draftProgressEl) return;
			var p = payload || buildPayload();
			draftProgressEl.textContent = "Fortschritt: " + countDecided(p) + " / " + questionTotal + " bearbeitet";
		}

		function markDecidedCards(payload){
			var byNr = {};
			payload.forEach(function(it){ byNr[it.nr] = it; });
			qCards().forEach(function(card){
				var nr = card.getAttribute("data-nr");
				var it = byNr[nr];
				var decided = it && itemIsDecided(it);
				if(decided){
					card.classList.add("decided");
				} else {
					card.classList.remove("decided");
				}
				if(it && Array.isArray(it.add) && it.add.length > 0){
					card.classList.add("has-additions");
				} else {
					card.classList.remove("has-additions");
				}
			});
		}

		var onlyDiffFilter = document.getElementById("darm-only-diff");

		function escHtml(s){
			var d = document.createElement("div");
			d.textContent = s == null ? "" : String(s);
			return d.innerHTML;
		}

		function loesungTokenLookup(row){
			var lookup = {};
			String(row.Loesung || "").split("|").forEach(function(tok){
				tok = tok.trim();
				if(tok) lookup[tok.toLowerCase()] = true;
			});
			return lookup;
		}

		var compareFields = <?php echo json_encode(darm_compare_fields(), JSON_UNESCAPED_UNICODE); ?>;
		var compareFieldLabels = <?php echo json_encode(array_combine(darm_compare_fields(), array_map("darm_compare_field_label", darm_compare_fields())), JSON_UNESCAPED_UNICODE); ?>;
		var multilineMergeFields = <?php echo json_encode(darm_multiline_merge_fields(), JSON_UNESCAPED_UNICODE); ?>;

		function normalizeLineEndings(s){
			return String(s == null ? "" : s).replace(/\r\n/g, "\n").replace(/\r/g, "\n");
		}

		function multilineFieldIsEmpty(s){
			return normalizeLineEndings(s).trim() === "";
		}

		function mergeFieldValuesEqual(field, a, b){
			if(multilineMergeFields.indexOf(field) >= 0){
				return normalizeLineEndings(a) === normalizeLineEndings(b);
			}
			return String(a == null ? "" : a) === String(b == null ? "" : b);
		}

		function preserveMultilineField(s){
			return normalizeLineEndings(s);
		}

		function diffFieldsVsBase(baseRow, row){
			if(!baseRow || !row) return [];
			var out = [];
			compareFields.forEach(function(f){
				if(!mergeFieldValuesEqual(f, baseRow[f], row[f])) out.push(f);
			});
			return out;
		}

		function renderAntwortenHtml(row, emphasize, diffFields){
			diffFields = diffFields || [];
			var opts = String(row.Loesungsauswahl || "").split("|").map(function(x){ return x.trim(); }).filter(Boolean);
			var lookup = loesungTokenLookup(row);
			var loes = String(row.Loesung || "").trim();
			var antwort = String(row.Antwort || "").trim();
			var wrapCls = "darm-antworten darm-row-sync" + (emphasize ? " darm-antworten-diff" : "");
			var html = '<div class="'+wrapCls+'">';
			if(opts.length){
				var optLblCls = "darm-antworten-label" + (diffFields.indexOf("Loesungsauswahl") >= 0 ? " darm-field-diff" : "");
				html += '<div class="'+optLblCls+'">Antwortoptionen</div><ul class="darm-options">';
				opts.forEach(function(opt, i){
					var letter = String.fromCharCode(65 + i);
					var isCorrect = !!lookup[letter.toLowerCase()];
					if(!isCorrect){
						Object.keys(lookup).forEach(function(tok){
							if(tok.length > 1 && opt.toLowerCase().indexOf(tok) >= 0) isCorrect = true;
						});
					}
					var cls = isCorrect ? " darm-opt-correct" : "";
					html += '<li class="darm-opt'+cls+'"><span class="darm-opt-letter">'+letter+'.</span> '+escHtml(opt)+'</li>';
				});
				html += '</ul>';
			}
			if(loes){
				var loesCls = "darm-loes-kurz" + (diffFields.indexOf("Loesung") >= 0 ? " darm-field-diff" : "");
				html += '<div class="'+loesCls+'"><strong>Lösung:</strong> '+escHtml(loes)+'</div>';
			}
			var antCls = "darm-antwort-text" + (diffFields.indexOf("Antwort") >= 0 ? " darm-field-diff" : "");
			html += '<div class="'+antCls+'"><strong>Antworttext:</strong> ';
			html += antwort ? escHtml(antwort) : '<em class="darm-antwort-text--leer">—</em>';
			html += '</div>';
			html += '</div>';
			return html;
		}

		function renderColMetaHtml(row, diffFields){
			diffFields = diffFields || [];
			var rev = (row.Rueckmeldung || "offen").trim();
			var revCls = "darm-pill";
			if(rev === "wichtig") revCls += " rev-wichtig";
			else if(rev === "gut") revCls += " rev-gut";
			else if(rev.toLowerCase().indexOf("arbeit") >= 0) revCls += " rev-überarbeiten";
			if(diffFields.indexOf("Rueckmeldung") >= 0) revCls += " darm-field-diff";
			var html = '<div class="darm-meta darm-row-sync"><span class="'+revCls+'">'+escHtml(rev)+'</span>';
			var prio = (row.Prioritaet || "").trim();
			if(prio){
				var prioCls = "darm-pill" + (diffFields.indexOf("Prioritaet") >= 0 ? " darm-field-diff" : "");
				html += '<span class="'+prioCls+'">Prio '+escHtml(prio)+'</span>';
			}
			var st = (row.Status || "").trim();
			if(st){
				var stCls = "darm-pill" + (diffFields.indexOf("Status") >= 0 ? " darm-field-diff" : "");
				html += '<span class="'+stCls+'">'+escHtml(st)+'</span>';
			}
			return html + '</div>';
		}

		function renderColLoesungstextHtml(row, diffFields){
			diffFields = diffFields || [];
			var solText = preserveMultilineField(row.Loesungstext || "");
			var antwort = preserveMultilineField(row.Antwort || "");
			var solCls = "darm-loesungstext";
			if(multilineFieldIsEmpty(solText)) solCls += " darm-loesungstext--empty";
			if(diffFields.indexOf("Loesungstext") >= 0) solCls += " darm-field-diff";
			var antCls = "darm-antwort";
			if(multilineFieldIsEmpty(antwort)) antCls += " darm-antwort--empty";
			if(diffFields.indexOf("Antwort") >= 0) antCls += " darm-field-diff";
			var solBody = !multilineFieldIsEmpty(solText) ? escHtml(solText) : '<em class="darm-empty-hint">—</em>';
			var antBody = !multilineFieldIsEmpty(antwort) ? escHtml(antwort) : '<em class="darm-empty-hint">—</em>';
			return '<div class="darm-sol-pair darm-row-sync">'
				+'<div class="'+solCls+'"><div class="darm-loesungstext-label">Lösungstext</div><div class="darm-loesungstext-body">'+solBody+'</div></div>'
				+'<div class="'+antCls+'"><div class="darm-antwort-label">Antwort</div><div class="darm-antwort-body">'+antBody+'</div></div>'
				+'</div>';
		}

		function renderColRmTextHtml(row, diffFields){
			diffFields = diffFields || [];
			var rmText = preserveMultilineField(row.RueckmeldungText || "");
			if(multilineFieldIsEmpty(rmText)) return '<div class="darm-rm-text darm-rm-text--empty darm-row-sync">&#8203;</div>';
			var cls = "darm-rm-text darm-row-sync";
			if(diffFields.indexOf("RueckmeldungText") >= 0) cls += " darm-field-diff";
			return '<div class="'+cls+'"><div class="darm-rm-text-label">Rückmeldung Text</div><div class="darm-rm-text-body">'+escHtml(rmText)+'</div></div>';
		}

		function renderColAntwortFillHtml(row, antwortDiff, diffFields){
			return '<div class="darm-col-fill darm-row-sync">'+renderAntwortenHtml(row, !!antwortDiff, diffFields || [])+ '</div>';
		}

		function renderResultColHtml(row, diffFields){
			diffFields = diffFields || [];
			var antwortDiff = diffFields.indexOf("Loesungsauswahl") >= 0 || diffFields.indexOf("Loesung") >= 0 || diffFields.indexOf("Antwort") >= 0;
			if(!row || !String(row.Frage || "").trim()){
				return '<div class="darm-frage darm-frage--placeholder darm-row-sync"><div class="darm-result-empty"><em>Zuerst Vorlage wählen, dann <strong>Anpassen</strong>.</em></div></div>'
					+'<div class="darm-meta darm-meta--empty darm-row-sync"></div>'
					+ renderColLoesungstextHtml({}, [])
					+'<div class="darm-rm-text darm-rm-text--empty darm-row-sync">&#8203;</div>'
					+ renderColAntwortFillHtml({}, false, []);
			}
			var frageCls = "darm-frage darm-row-sync" + (diffFields.indexOf("Frage") >= 0 ? " darm-field-diff" : "");
			return '<div class="'+frageCls+'">'+escHtml(row.Frage)+'</div>'
				+ renderColMetaHtml(row, diffFields)
				+ renderColLoesungstextHtml(row, diffFields)
				+ renderColRmTextHtml(row, diffFields)
				+ renderColAntwortFillHtml(row, antwortDiff, diffFields);
		}

		function resultRowForCard(card){
			if(card.getAttribute("data-custom-active") === "1"){
				return collectCustom(card);
			}
			return getRowFromCard(card, getCheckedSource(card));
		}

		function refreshEditVsBaseLoesungstext(card){
			var panel = card ? card.querySelector(".darm-edit-panel") : null;
			var hint = panel ? panel.querySelector('[data-edit-vs-base="Loesungstext"]') : null;
			if(!hint) return;
			var baseRow = getRowFromCard(card, "base");
			var ta = panel.querySelector('[data-edit-field="Loesungstext"]');
			var cur = ta ? preserveMultilineField(ta.value || "") : "";
			var baseText = baseRow ? preserveMultilineField(baseRow.Loesungstext || "") : "";
			if(!baseRow){
				hint.hidden = true;
				return;
			}
			hint.hidden = false;
			var body = hint.querySelector(".darm-edit-vs-base-text");
			if(body) body.textContent = multilineFieldIsEmpty(baseText) ? "—" : baseText;
			hint.classList.toggle("darm-edit-vs-base--diff", !mergeFieldValuesEqual("Loesungstext", baseText, cur));
		}

		function refreshEditVsBaseAntwort(card){
			var panel = card ? card.querySelector(".darm-edit-panel") : null;
			var hint = panel ? panel.querySelector('[data-edit-vs-base="Antwort"]') : null;
			if(!hint) return;
			var baseRow = getRowFromCard(card, "base");
			var ta = panel.querySelector('[data-edit-field="Antwort"]');
			var cur = ta ? preserveMultilineField(ta.value || "") : "";
			var baseText = baseRow ? preserveMultilineField(baseRow.Antwort || "") : "";
			if(!baseRow){
				hint.hidden = true;
				return;
			}
			hint.hidden = false;
			var body = hint.querySelector(".darm-edit-vs-base-text");
			if(body) body.textContent = multilineFieldIsEmpty(baseText) ? "—" : baseText;
			hint.classList.toggle("darm-edit-vs-base--diff", !mergeFieldValuesEqual("Antwort", baseText, cur));
		}

		function refreshResultColumn(card){
			var nr = card.getAttribute("data-nr");
			var body = card.querySelector('[data-result-body="'+nr+'"]');
			if(!body) return;
			var row = resultRowForCard(card);
			var baseRow = getRowFromCard(card, "base");
			var diffFields = diffFieldsVsBase(baseRow, row);
			body.innerHTML = renderResultColHtml(row && String(row.Frage || "").trim() ? row : null, diffFields);
			scheduleCompareRowSync(card);
		}

		function syncSectionHeights(card, selector){
			var root = card.querySelector(".darm-cols");
			if(!root) return;
			var els = root.querySelectorAll(selector);
			if(!els.length) return;
			els.forEach(function(el){ el.style.minHeight = ""; });
			var max = 0;
			els.forEach(function(el){ max = Math.max(max, el.offsetHeight); });
			if(max > 0){
				els.forEach(function(el){ el.style.minHeight = max + "px"; });
			}
		}

		function syncCompareRowHeights(card){
			if(!card) return;
			syncSectionHeights(card, ".darm-col-title");
			syncSectionHeights(card, ".darm-frage");
			syncSectionHeights(card, ".darm-meta");
			syncSectionHeights(card, ".darm-sol-pair");
			syncSectionHeights(card, ".darm-rm-text");
			syncSectionHeights(card, ".darm-antworten");
		}

		function scheduleCompareRowSync(card){
			requestAnimationFrame(function(){
				requestAnimationFrame(function(){ syncCompareRowHeights(card); });
			});
		}

		function closeAnpassenPanel(card){
			if(!card) return;
			var panel = card.querySelector(".darm-edit-panel");
			if(panel){
				panel.classList.remove("open");
				panel.setAttribute("hidden", "");
			}
		}

		function closeAllAnpassenPanels(exceptCard){
			qCards().forEach(function(c){
				if(exceptCard && c === exceptCard) return;
				closeAnpassenPanel(c);
			});
		}

		function finishAnpassen(card){
			if(!card) return;
			closeAnpassenPanel(card);
			onCardDecisionChange(card);
		}

		function openAnpassen(card){
			var panel = card.querySelector(".darm-edit-panel");
			var alreadyCustom = card.getAttribute("data-custom-active") === "1";
			closeAllAnpassenPanels(card);
			if(!alreadyCustom){
				var src = getCheckedSource(card);
				var row = getRowFromCard(card, src);
				if(!row){
					alert("Keine Vorlage für diese Nr in der gewählten Quelle.");
					return;
				}
				seedEditorFromSource(card, src);
				var stEl = card.querySelector('[data-edit-field="Status"]');
				if(stEl) stEl.value = "überarbeitet";
				card.setAttribute("data-custom-active", "1");
				card.setAttribute("data-custom-template", src);
			}
			if(panel){
				panel.classList.add("open");
				panel.removeAttribute("hidden");
			}
			refreshEditVsBaseLoesungstext(card);
			refreshEditVsBaseAntwort(card);
			refreshResultColumn(card);
			onCardDecisionChange(card);
		}

		function applyDraft(draft){
			if(!draft || !Array.isArray(draft.decisions)) return;
			var byNr = {};
			draft.decisions.forEach(function(it){ if(it && it.nr) byNr[it.nr] = it; });
			qCards().forEach(function(card){
				var nr = card.getAttribute("data-nr");
				var it = byNr[nr];
				if(!it) return;
				if(it.source === "custom" && it.custom){
					var panel = card.querySelector(".darm-edit-panel");
					if(panel){
						panel.querySelectorAll("[data-edit-field]").forEach(function(el){
							var f = el.getAttribute("data-edit-field");
							if(f && it.custom[f] !== undefined){
								var cv = it.custom[f];
								if(multilineMergeFields.indexOf(f) >= 0) cv = preserveMultilineField(cv);
								el.value = cv;
							}
						});
					}
					card.setAttribute("data-custom-active", "1");
					refreshResultColumn(card);
					refreshEditVsBaseLoesungstext(card);
					refreshEditVsBaseAntwort(card);
				} else {
					var radio = card.querySelector('input[name="pick_'+nr+'"][value="'+it.source+'"]');
					if(radio) radio.checked = true;
					card.removeAttribute("data-custom-active");
					refreshResultColumn(card);
				}
				if(Array.isArray(it.add)){
					it.add.forEach(function(addSrc){
						var cb = card.querySelector('input[name="add_'+nr+'[]"][value="'+addSrc+'"]');
						if(cb) cb.checked = true;
					});
				}
			});
			markDecidedCards(draft.decisions);
			if(draft.ui){
				if(draft.ui.basis_mode === "replace_basis" && basisReplace) basisReplace.checked = true;
				if(draft.ui.basis_mode === "new_basis" && basisNew) basisNew.checked = true;
				if(draft.ui.basis_fragen_file && basisFileInput) basisFileInput.value = draft.ui.basis_fragen_file;
				var prom = form.querySelector('input[name="promote_base_quiz"]');
				if(prom && draft.ui.promote_base_quiz === false) prom.checked = false;
				var reset = form.querySelector('input[name="reset_review_fields"]');
				if(reset) reset.checked = !!draft.ui.reset_review_fields;
				if(draft.ui.only_diff && onlyDiffFilter){
					onlyDiffFilter.checked = true;
					onlyDiffFilter.dispatchEvent(new Event("change"));
				}
				if(draft.ui.last_nr){
					var card = document.querySelector('.darm-q[data-nr="'+draft.ui.last_nr+'"]');
					if(card) card.scrollIntoView({ behavior: "auto", block: "start" });
				}
			}
			applyBasisMode();
			markDecidedCards(draft.decisions);
			updateProgressUi(draft.decisions);
			qCards().forEach(function(c){ scheduleCompareRowSync(c); });
		}

		function syncDraftMeta(){
			if(draftOnlyDiffEl && onlyDiffFilter) draftOnlyDiffEl.value = onlyDiffFilter.checked ? "1" : "0";
		}

		function saveDraftAjax(){
			var p = buildPayload();
			payloadEl.value = JSON.stringify(p);
			syncDraftMeta();
			if(mergeActionEl) mergeActionEl.value = "save_draft";
			var fd = new FormData(form);
			fd.set("merge_action", "save_draft");
			fd.set("draft_ajax", "1");
			fd.set("merge_payload", JSON.stringify(p));
			return fetch(form.getAttribute("action") || window.location.href, {
				method: "POST",
				body: fd,
				credentials: "same-origin"
			}).then(function(r){ return r.json(); }).then(function(data){
				if(mergeActionEl) mergeActionEl.value = "save_merge";
				if(data && data.ok && draftSavedEl){
					var t = data.updated_at ? new Date(data.updated_at) : new Date();
					draftSavedEl.textContent = "Gespeichert " + t.toLocaleString("de-CH");
					markDecidedCards(p);
					updateProgressUi(p);
				}
				return data;
			}).catch(function(){
				if(mergeActionEl) mergeActionEl.value = "save_merge";
			});
		}

		if(form){
			form.addEventListener("submit", function(ev){
				var act = mergeActionEl ? mergeActionEl.value : "save_merge";
				var p = buildPayload();
				if(!p.length){ ev.preventDefault(); alert("Keine Fragen."); return; }
				payloadEl.value = JSON.stringify(p);
				syncDraftMeta();
				if(act === "save_merge"){
					if(!confirm("Merge abschliessen und neue Basis schreiben? Der Entwurf wird danach gelöscht.")) ev.preventDefault();
				}
			});
		}

		var saveDraftBtn = document.getElementById("darm-save-draft-btn");
		if(saveDraftBtn){
			saveDraftBtn.addEventListener("click", function(){
				saveDraftAjax();
			});
		}

		function onCardDecisionChange(card){
			updateProgressUi();
			markDecidedCards(buildPayload());
			if(draftLastNrEl) draftLastNrEl.value = card.getAttribute("data-nr") || "";
		}

		function seedEditorFromSource(card, srcId){
			var row = getRowFromCard(card, srcId);
			if(!row) return;
			var panel = card.querySelector(".darm-edit-panel");
			if(!panel) return;
			panel.querySelectorAll("[data-edit-field]").forEach(function(el){
				var f = el.getAttribute("data-edit-field");
				if(!f || row[f] === undefined) return;
				var val = row[f];
				if(multilineMergeFields.indexOf(f) >= 0) val = preserveMultilineField(val);
				el.value = val;
			});
			refreshEditVsBaseLoesungstext(card);
			refreshEditVsBaseAntwort(card);
		}

		qCards().forEach(function(card){
			card.querySelectorAll(".darm-pick-radio").forEach(function(radio){
				radio.addEventListener("change", function(){
					closeAnpassenPanel(card);
					card.removeAttribute("data-custom-active");
					card.removeAttribute("data-custom-template");
					refreshResultColumn(card);
					onCardDecisionChange(card);
				});
			});
			card.querySelectorAll(".darm-anpassen-btn").forEach(function(btn){
				btn.addEventListener("click", function(){
					openAnpassen(btn.closest(".darm-q"));
				});
			});
			card.querySelectorAll(".darm-anpassen-done-btn").forEach(function(btn){
				btn.addEventListener("click", function(){
					finishAnpassen(btn.closest(".darm-q"));
				});
			});
			card.querySelectorAll(".darm-add-cb").forEach(function(cb){
				cb.addEventListener("change", function(){ onCardDecisionChange(card); });
			});
			card.querySelectorAll("[data-edit-field]").forEach(function(el){
				el.addEventListener("input", function(){
					if(card.getAttribute("data-custom-active") === "1"){
						refreshResultColumn(card);
						if(el.getAttribute("data-edit-field") === "Loesungstext") refreshEditVsBaseLoesungstext(card);
						if(el.getAttribute("data-edit-field") === "Antwort") refreshEditVsBaseAntwort(card);
					}
				});
			});
		});

		qCards().forEach(function(card){ closeAnpassenPanel(card); });
		if(savedDraft) applyDraft(savedDraft);
		else {
			qCards().forEach(function(card){ refreshResultColumn(card); });
			updateProgressUi();
		}
		qCards().forEach(function(card){ scheduleCompareRowSync(card); });
		window.addEventListener("resize", function(){
			qCards().forEach(function(card){ syncCompareRowHeights(card); });
		});

		window.setInterval(function(){ saveDraftAjax(); }, 90000);

		document.getElementById("darm-bulk-base").addEventListener("click", function(){
			closeAllAnpassenPanels(null);
			qCards().forEach(function(c){
				var nr = c.getAttribute("data-nr");
				var r = c.querySelector('input[name="pick_'+nr+'"][value="base"]');
				if(r) r.checked = true;
				c.removeAttribute("data-custom-active");
				refreshResultColumn(c);
			});
			updateProgressUi();
		});
		document.querySelectorAll(".darm-bulk-src").forEach(function(btn){
			btn.addEventListener("click", function(){
				closeAllAnpassenPanels(null);
				var src = btn.getAttribute("data-src");
				qCards().forEach(function(c){
					var nr = c.getAttribute("data-nr");
					var r = c.querySelector('input[name="pick_'+nr+'"][value="'+src+'"]');
					if(r) r.checked = true;
					c.removeAttribute("data-custom-active");
					refreshResultColumn(c);
				});
				updateProgressUi();
			});
		});

		if(onlyDiffFilter){
			onlyDiffFilter.addEventListener("change", function(){
				qCards().forEach(function(c){
					if(!onlyDiffFilter.checked){ c.style.display = ""; return; }
					c.style.display = c.getAttribute("data-has-diff") === "1" ? "" : "none";
				});
				syncDraftMeta();
			});
		}

		var diffIdx = 0;
		document.getElementById("darm-next-diff").addEventListener("click", function(){
			var vis = qCards().filter(function(c){ return c.style.display !== "none" && c.getAttribute("data-has-diff")==="1"; });
			if(!vis.length) return;
			diffIdx = (diffIdx + 1) % vis.length;
			vis[diffIdx].scrollIntoView({ behavior: "smooth", block: "start" });
		});

		qCards().forEach(function(card){
			card.querySelectorAll(".darm-pick input[type=radio]").forEach(function(radio){
				radio.addEventListener("change", function(){
					if(radio.value !== "custom") return;
					var panel = card.querySelector(".darm-edit-panel");
					if(panel && !panel.classList.contains("open")) panel.classList.add("open");
					var src = card.querySelector('.darm-pick input[type=radio]:not([value="custom"]):checked');
					// seed editor from last non-custom if empty
				});
			});
		});
	})();
	</script>
	<?php } ?>
</div>
<?php
if (function_exists("footerpage")) {
	footerpage(false, $folderoffset);
}
