<?php
/**
 * Personal / Piloten für Live-Beurteilungen — Stammdatenliste.
 * Daten: pilotenliste.json unter $rkrkLivePersonelRoot (= $rkrkLiveassessData/personel).
 * Alle Felder pro Eintrag aus dem JSON, Tabelle such-/sortier-/editierbar, neue Zeilen hinzufügbar.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
register_shutdown_function(function () {
    $e = error_get_last();
    if (!is_array($e)) {
        return;
    }
    $msg = "[shutdown-live-personel] " . ($e['message'] ?? '') . " in " . ($e['file'] ?? '') . ":" . ($e['line'] ?? '') . PHP_EOL;
    @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug-1791ba.log', $msg, FILE_APPEND);
});

/* Puffer: Includes (z. B. common mit schließendem ?>) dürfen keinen Body vor Location-Redirect senden. */
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once('rkrk_assess_common.php');

if (function_exists('ProtectFromAccess')) {
    CheckIPandExit($folderoffset);
}

function rkrk_live_esc($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rkrk_live_personel_valid_key(string $k): bool
{
    if ($k === '' || strlen($k) > 96) {
        return false;
    }
    return preg_match('/^[\p{L}\p{N}_\-.]+$/u', $k) === 1;
}

function rkrk_live_personel_cell_string($v): string
{
    if ($v === null) {
        return '';
    }
    if (is_bool($v)) {
        return $v ? '1' : '';
    }
    if (is_scalar($v)) {
        return (string)$v;
    }
    return json_encode($v, JSON_UNESCAPED_UNICODE);
}

/**
 * @return array{0: array<int,array<string,mixed>>, 1: array<int,string>}
 */
function rkrk_live_personel_load_rows(string $path): array
{
    $columnsFile = [];
    if (!is_file($path)) {
        return [[], []];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === "") {
        return [[], []];
    }
    $data = @json_decode($raw, true);
    if (!is_array($data)) {
        return [[], []];
    }
    if (isset($data["columns"]) && is_array($data["columns"])) {
        foreach ($data["columns"] as $c) {
            if (is_string($c) && rkrk_live_personel_valid_key($c) && $c !== "id") {
                $columnsFile[] = $c;
            }
        }
    }
    if (isset($data["pilots"]) && is_array($data["pilots"])) {
        $rows = $data["pilots"];
    } elseif (isset($data["rows"]) && is_array($data["rows"])) {
        $rows = $data["rows"];
    } elseif (isset($data[0]) || count($data) === 0) {
        $rows = array_values(array_filter($data, "is_array"));
    } else {
        return [[], $columnsFile];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean = [];
        foreach ($row as $k => $v) {
            if (!is_string($k) || !rkrk_live_personel_valid_key($k)) {
                continue;
            }
            $clean[$k] = $v;
        }
        if ($clean === []) {
            continue;
        }
        $out[] = $clean;
    }
    return [$out, $columnsFile];
}

function rkrk_live_personel_discover_columns(array $pilots, array $columnsFile): array
{
    $set = [];
    foreach ($pilots as $r) {
        foreach (array_keys($r) as $k) {
            if ($k === "id") {
                continue;
            }
            if (rkrk_live_personel_valid_key($k)) {
                $set[$k] = true;
            }
        }
    }
    $all = array_keys($set);
    if ($columnsFile !== []) {
        $ordered = [];
        foreach ($columnsFile as $c) {
            if ($c === "name" || $c === "display") {
                continue;
            }
            if (isset($set[$c])) {
                $ordered[] = $c;
            }
        }
        $rest = array_diff($all, $ordered);
        sort($rest, SORT_NATURAL | SORT_FLAG_CASE);
        return rkrk_live_personel_columns_without_legacy_display(array_merge($ordered, $rest));
    }
    $priority = [
        "pilot_id",
        "vorname",
        "nachname",
        "strasse",
        "plz",
        "ort",
        "email",
        "telefon",
        "erste_erfahrung",
        "instruktor",
        "alternative_rollen",
        "drohnentyp",
        "rkrs_pilot_id",
        "remote_pilot_id",
        "betreiber_id",
        "fpl_gueltig",
        "einsatzpruefung_rkrsplus",
        "rkrsplus",
        "bemerkung",
        "note",
        "kommentar",
        "updated_at",
        "version",
    ];
    $ordered = [];
    foreach ($priority as $p) {
        if (in_array($p, $all, true)) {
            $ordered[] = $p;
        }
    }
    $rest = array_diff($all, $ordered);
    sort($rest, SORT_NATURAL | SORT_FLAG_CASE);
    return rkrk_live_personel_columns_without_legacy_display(array_merge($ordered, $rest));
}

/** Keine Spalten name/display mehr (ersetzt durch Vorname + Nachname). */
function rkrk_live_personel_columns_without_legacy_display(array $columns): array
{
    $out = [];
    foreach ($columns as $c) {
        if ($c === "name" || $c === "display") {
            continue;
        }
        $out[] = $c;
    }
    return $out;
}

/**
 * Vor Speichern: name/display entfernen; fehlende Namen aus Legacy-Feldern nach vorname spiegeln.
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function rkrk_live_personel_row_strip_legacy_display_fields(array $r): array
{
    $vn = trim((string)($r["vorname"] ?? ""));
    $nn = trim((string)($r["nachname"] ?? ""));
    if ($vn === "" && $nn === "") {
        $nm = trim((string)($r["name"] ?? ""));
        if ($nm !== "") {
            $r["vorname"] = $nm;
        } else {
            $d = trim((string)($r["display"] ?? ""));
            if ($d !== "") {
                $r["vorname"] = $d;
            }
        }
    }
    unset($r["name"], $r["display"]);
    return $r;
}

/** Stufe 1–6 aus `erste_erfahrung` (erste Ziffer oder ganze Zahl), sonst 0. */
function rkrk_live_personel_exp_level(string $raw): int
{
    $s = trim($raw);
    if ($s === "") {
        return 0;
    }
    if (preg_match('/^([1-6])\b/u', $s, $m)) {
        return (int)$m[1];
    }
    $n = (int)$s;
    if ($n >= 1 && $n <= 6) {
        return $n;
    }
    return 0;
}

/** Spalte „Alternative Rollen“ nach „Instruktor“ (sonst nach „Nachname“), falls noch nicht vorhanden. */
function rkrk_live_personel_columns_ensure_alternative_rollen_col(array $columns): array
{
    if (in_array("alternative_rollen", $columns, true)) {
        return $columns;
    }
    $pos = array_search("instruktor", $columns, true);
    if ($pos !== false) {
        $out = $columns;
        array_splice($out, (int)$pos + 1, 0, ["alternative_rollen"]);
        return $out;
    }
    $pos2 = array_search("nachname", $columns, true);
    if ($pos2 !== false) {
        $out = $columns;
        array_splice($out, (int)$pos2 + 1, 0, ["alternative_rollen"]);
        return $out;
    }
    $out = $columns;
    $out[] = "alternative_rollen";
    return $out;
}

/** Spalte „Drohnentyp“ nach „Alternative Rollen“ (sonst nach „Instruktor“), falls noch nicht vorhanden. */
function rkrk_live_personel_columns_ensure_drohnentyp(array $columns): array
{
    if (in_array("drohnentyp", $columns, true)) {
        return $columns;
    }
    $pos = array_search("alternative_rollen", $columns, true);
    if ($pos !== false) {
        $out = $columns;
        array_splice($out, (int)$pos + 1, 0, ["drohnentyp"]);
        return $out;
    }
    $pos2 = array_search("instruktor", $columns, true);
    if ($pos2 !== false) {
        $out = $columns;
        array_splice($out, (int)$pos2 + 1, 0, ["drohnentyp"]);
        return $out;
    }
    $out = $columns;
    $out[] = "drohnentyp";
    return $out;
}

/** Spalte „Instruktor“ immer nach „erste_erfahrung“ (oder nach Telefon) einfügen. */
function rkrk_live_personel_columns_ensure_instruktor(array $columns): array
{
    // Gewünschte Reihenfolge fix: Drohnentyp, Alternative Rollen, Instruktor.
    $base = [];
    foreach ($columns as $c) {
        if ($c === "drohnentyp" || $c === "alternative_rollen" || $c === "instruktor") {
            continue;
        }
        $base[] = $c;
    }
    $orderedTail = ["drohnentyp", "alternative_rollen", "instruktor"];
    $pos = array_search("erste_erfahrung", $base, true);
    if ($pos !== false) {
        $out = $base;
        array_splice($out, (int)$pos + 1, 0, $orderedTail);
        return $out;
    }
    $pos2 = array_search("telefon", $base, true);
    if ($pos2 !== false) {
        $out = $base;
        array_splice($out, (int)$pos2 + 1, 0, $orderedTail);
        return $out;
    }
    return array_merge($base, $orderedTail);
}

/** Spalte `pilot_id` (Anzeige: PilotID) immer erste Spalte in Tabellen-Reihenfolge / JSON columns. */
function rkrk_live_personel_columns_ensure_pilot_id_front(array $columns): array
{
    $columns = array_values(array_filter($columns, static function ($c): bool {
        return $c !== "pilot_id";
    }));
    array_unshift($columns, "pilot_id");
    return $columns;
}

/**
 * RKRS-/Remote-/Betreiber-ID-Spalten nach Drohnentyp sicherstellen (getrennt von PilotID 1…n).
 */
function rkrk_live_personel_columns_ensure_id_fields(array $columns): array
{
    $required = ["rkrs_pilot_id", "remote_pilot_id", "betreiber_id"];
    $base = [];
    foreach ($columns as $c) {
        if (in_array($c, $required, true)) {
            continue;
        }
        $base[] = $c;
    }
    $pos = array_search("drohnentyp", $base, true);
    if ($pos === false) {
        $pos = array_search("alternative_rollen", $base, true);
    }
    if ($pos === false) {
        $pos = array_search("instruktor", $base, true);
    }
    if ($pos === false) {
        return array_merge($base, $required);
    }
    $out = $base;
    array_splice($out, (int)$pos + 1, 0, $required);
    return $out;
}

/**
 * FPL-Gültigkeit, Einsatzprüfung RKRS+ und RKRS+-Flag nach den ID-Feldern einfügen
 * (bestehende JSON-column-Listen mergen, ohne Daten zu löschen).
 */
function rkrk_live_personel_columns_ensure_rkrsplus_fields(array $columns): array
{
    $required = ["fpl_gueltig", "einsatzpruefung_rkrsplus", "rkrsplus"];
    $base = [];
    foreach ($columns as $c) {
        if (in_array($c, $required, true)) {
            continue;
        }
        $base[] = $c;
    }
    $pos = array_search("betreiber_id", $base, true);
    if ($pos === false) {
        $pos = array_search("remote_pilot_id", $base, true);
    }
    if ($pos === false) {
        $pos = array_search("rkrs_pilot_id", $base, true);
    }
    if ($pos === false) {
        $pos = array_search("instruktor", $base, true);
    }
    if ($pos === false) {
        return array_merge($base, $required);
    }
    $out = $base;
    array_splice($out, (int)$pos + 1, 0, $required);
    return $out;
}

/** Discover + Instruktor-Tail + PilotID-Spalte + RKRS-IDs; Fallback wenn leer. */
function rkrk_live_personel_finalize_columns(array $pilots, array $columnsFileOrder): array
{
    $c = rkrk_live_personel_columns_ensure_instruktor(rkrk_live_personel_discover_columns($pilots, $columnsFileOrder));
    $c = rkrk_live_personel_columns_ensure_pilot_id_front($c);
    $c = rkrk_live_personel_columns_ensure_id_fields($c);
    $c = rkrk_live_personel_columns_ensure_rkrsplus_fields($c);
    if ($c === []) {
        return ["pilot_id", "rkrs_pilot_id", "vorname", "nachname", "note"];
    }
    return $c;
}

/**
 * PilotID eindeutig 1…n gemäss aktueller Reihenfolge der Piloten-Liste.
 * Unabhängig von `rkrs_pilot_id` (RKRS Piloten ID).
 *
 * @return array{0: list<array<string,mixed>>, 1: bool} Geändert-Flag, falls Werte angepasst wurden.
 */
function rkrk_live_personel_apply_pilot_id_sequence(array $rows): array
{
    $out = [];
    $changed = false;
    $i = 1;
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $want = (string)$i;
        $cur = isset($r["pilot_id"]) ? trim((string)$r["pilot_id"]) : "";
        if ($cur !== $want) {
            $changed = true;
        }
        $r["pilot_id"] = $want;
        $out[] = $r;
        $i++;
    }
    return [$out, $changed];
}

function rkrk_live_personel_row_is_empty(array $row): bool
{
    foreach ($row as $k => $v) {
        if ($k === "id" || $k === "pilot_id") {
            continue;
        }
        if (trim(rkrk_live_personel_cell_string($v)) !== "") {
            return false;
        }
    }
    return true;
}

function rkrk_live_personel_ensure_ids(array $rows): array
{
    $out = [];
    $changed = false;
    foreach ($rows as $r) {
        $id = trim((string)($r["id"] ?? ""));
        if ($id === "" || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            $r["id"] = bin2hex(random_bytes(8));
            $changed = true;
        }
        $out[] = $r;
    }
    return [$out, $changed];
}

/** Eindeutigkeit: E-Mail, sonst Vorname+Nachname, sonst name, sonst id. */
function rkrk_live_personel_dedupe_key(array $r): string
{
    $em = strtolower(trim((string)($r["email"] ?? "")));
    if ($em !== "") {
        return "e:" . $em;
    }
    $vn = mb_strtolower(trim((string)($r["vorname"] ?? "")), "UTF-8");
    $nn = mb_strtolower(trim((string)($r["nachname"] ?? "")), "UTF-8");
    $vn = preg_replace('/\s+/u', " ", $vn);
    $nn = preg_replace('/\s+/u', " ", $nn);
    if ($vn !== "" && $nn !== "") {
        return "n:" . $vn . "|" . $nn;
    }
    $nm = mb_strtolower(trim((string)($r["name"] ?? "")), "UTF-8");
    $nm = preg_replace('/\s+/u', " ", $nm);
    if ($nm !== "") {
        return "u:" . $nm;
    }
    $tel = preg_replace('/\D+/u', "", (string)($r["telefon"] ?? ""));
    if (strlen($tel) >= 6) {
        return "t:" . $tel;
    }
    return "i:" . trim((string)($r["id"] ?? ""));
}

function rkrk_live_personel_merge_pilot_rows(array $base, array $other): array
{
    foreach ($other as $k => $v) {
        if ($k === "id") {
            continue;
        }
        if (!is_string($k) || !rkrk_live_personel_valid_key($k)) {
            continue;
        }
        $sv = rkrk_live_personel_cell_string($v);
        $sb = isset($base[$k]) ? rkrk_live_personel_cell_string($base[$k]) : "";
        if (trim((string)$sb) === "") {
            $base[$k] = $v;
            continue;
        }
        if (trim((string)$sv) === "") {
            continue;
        }
        if ((string)$sb === (string)$sv) {
            continue;
        }
        if (preg_match('/note|bemerkung|kommentar|hinweis|alternative_rollen/i', $k)) {
            $base[$k] = trim((string)$sb) . " | " . trim((string)$sv);
        }
    }
    return $base;
}

/**
 * @return array{0: array<int,array<string,mixed>>, 1: bool, 2: int}
 */
function rkrk_live_personel_deduplicate_rows(array $rows): array
{
    $before = count($rows);
    $map = [];
    $out = [];
    $changed = false;
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $key = rkrk_live_personel_dedupe_key($r);
        if (!isset($map[$key])) {
            $map[$key] = count($out);
            $out[] = $r;
            continue;
        }
        $idx = $map[$key];
        $merged = rkrk_live_personel_merge_pilot_rows($out[$idx], $r);
        $out[$idx] = $merged;
        $changed = true;
    }
    $removed = max(0, $before - count($out));
    return [$out, $changed, $removed];
}

function rkrk_live_personel_history_dir(string $path): string
{
    return rtrim(dirname($path), "/\\") . DIRECTORY_SEPARATOR . "history";
}

function rkrk_live_personel_backup_current_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $historyDir = rkrk_live_personel_history_dir($path);
    if (!is_dir($historyDir)) {
        @mkdir($historyDir, 0777, true);
    }
    if (!is_dir($historyDir)) {
        return;
    }
    $stamp = date("Ymd_His");
    $name = pathinfo($path, PATHINFO_FILENAME);
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $suffix = substr(hash("sha1", $path . "|" . microtime(true)), 0, 10);
    $file = $name . "_" . $stamp . "_" . $suffix . ($ext !== "" ? "." . $ext : "");
    $target = $historyDir . DIRECTORY_SEPARATOR . $file;
    @copy($path, $target);
}

function rkrk_live_personel_history_cleanup(string $path, int $days = 30): void
{
    if ($days < 1) {
        $days = 30;
    }
    $historyDir = rkrk_live_personel_history_dir($path);
    if (!is_dir($historyDir)) {
        return;
    }
    $ttl = time() - ($days * 86400);
    $files = @glob($historyDir . DIRECTORY_SEPARATOR . "*");
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $f) {
        if (!is_file($f)) {
            continue;
        }
        $mt = @filemtime($f);
        if ($mt !== false && $mt < $ttl) {
            @unlink($f);
        }
    }
}

/**
 * UI-Einstellungen (z. B. ausgeblendete Spalten) — neben pilotenliste.json.
 */
function rkrk_live_personel_ui_settings_path(): string
{
    global $rkrkLivePersonelRoot;
    $root = isset($rkrkLivePersonelRoot) ? (string)$rkrkLivePersonelRoot : "";
    if ($root === "") {
        return __DIR__ . DIRECTORY_SEPARATOR . "personel_ui.json";
    }
    return rtrim(str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . "personel_ui.json";
}

/**
 * @return array{hidden_columns:list<string>}
 */
function rkrk_live_personel_load_ui_settings(): array
{
    $out = ["hidden_columns" => []];
    $path = rkrk_live_personel_ui_settings_path();
    if (!is_file($path)) {
        return $out;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim((string)$raw) === "") {
        return $out;
    }
    $data = @json_decode((string)$raw, true);
    if (!is_array($data)) {
        return $out;
    }
    if (isset($data["hidden_columns"]) && is_array($data["hidden_columns"])) {
        foreach ($data["hidden_columns"] as $c) {
            if (is_string($c) && $c !== "" && $c !== "id" && rkrk_live_personel_valid_key($c)) {
                $out["hidden_columns"][] = $c;
            }
        }
        $out["hidden_columns"] = array_values(array_unique($out["hidden_columns"]));
    }
    return $out;
}

/**
 * @param array{hidden_columns?:list<string>} $settings
 */
function rkrk_live_personel_save_ui_settings(array $settings): bool
{
    $path = rkrk_live_personel_ui_settings_path();
    $dir = dirname($path);
    if ($dir !== "" && !is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $hidden = [];
    if (isset($settings["hidden_columns"]) && is_array($settings["hidden_columns"])) {
        foreach ($settings["hidden_columns"] as $c) {
            if (is_string($c) && $c !== "" && $c !== "id" && rkrk_live_personel_valid_key($c)) {
                $hidden[] = $c;
            }
        }
    }
    $payload = [
        "version" => 1,
        "updated_at" => date("c"),
        "hidden_columns" => array_values(array_unique($hidden)),
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($json) || $json === "") {
        return false;
    }
    $tmp = $path . ".tmp." . substr(hash("sha1", microtime(true) . "|" . $path), 0, 10);
    if (@file_put_contents($tmp, $json) === false) {
        @unlink($tmp);
        return false;
    }
    if (!@rename($tmp, $path)) {
        $ok = @copy($tmp, $path);
        @unlink($tmp);
        return $ok;
    }
    return true;
}

/**
 * @param list<string> $columns
 * @param list<string> $hidden
 * @return list<string>
 */
function rkrk_live_personel_visible_columns(array $columns, array $hidden): array
{
    $hide = array_fill_keys($hidden, true);
    $out = [];
    foreach ($columns as $c) {
        if (!isset($hide[$c])) {
            $out[] = $c;
        }
    }
    return $out;
}

/**
 * Panel: Spalten ein-/ausblenden (Einstellung wird serverseitig gespeichert).
 *
 * @param list<string> $columns
 * @param list<string> $hiddenColumns
 */
function rkrk_live_personel_echo_column_visibility_panel(array $columns, array $hiddenColumns, string $view): void
{
    $hideSet = array_fill_keys($hiddenColumns, true);
    $actionView = ($view === "list") ? "list" : "edit";
    echo "<details class='personel-col-vis' id='personel-col-vis'>";
    echo "<summary>Spalten ein-/ausblenden</summary>";
    echo "<form method='post' action='rkrk_assess_live_personel.php' class='personel-col-vis-form'>";
    echo "<input type='hidden' name='save_personel_ui' value='1'>";
    echo "<input type='hidden' name='ui_view' value='" . rkrk_live_esc($actionView) . "'>";
    echo "<p class='personel-col-vis-hint'>Abgewählt = ausgeblendet. Die Einstellung wird in <code>personel_ui.json</code> gespeichert (unabhängig von den Pilotendaten). In der Bearbeitung bleiben ausgeblendete Felder beim Speichern erhalten.</p>";
    echo "<div class='personel-col-vis-grid'>";
    foreach ($columns as $col) {
        $id = "personel-col-vis-" . preg_replace('/[^a-zA-Z0-9_\\-]/', "_", $col);
        $checked = !isset($hideSet[$col]) ? " checked" : "";
        echo "<label class='personel-col-vis-item' for='" . rkrk_live_esc($id) . "'>";
        echo "<input type='checkbox' name='col_visible[]' id='" . rkrk_live_esc($id) . "' value='" . rkrk_live_esc($col) . "'" . $checked . ">";
        echo "<span>" . rkrk_live_esc(rkrk_live_personel_label($col)) . "</span></label>";
    }
    echo "</div>";
    echo "<p class='personel-col-vis-actions'><button type='submit' class='btn btn-primary'>Spalten speichern</button>";
    echo " <button type='submit' name='col_vis_show_all' value='1' class='btn secondary'>Alle anzeigen</button></p>";
    echo "</form></details>";
}

function rkrk_live_personel_save_rows(string $path, array $columns, array $rows): bool
{
    $dir = dirname($path);
    if ($dir !== "" && !is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $columnsSave = [];
    foreach (rkrk_live_personel_columns_without_legacy_display($columns) as $c) {
        if ($c !== "id" && rkrk_live_personel_valid_key((string)$c)) {
            $columnsSave[] = (string)$c;
        }
    }
    $pilotsNorm = [];
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $pilotsNorm[] = rkrk_live_personel_row_strip_legacy_display_fields($r);
    }
    $payload = [
        "version" => 2,
        "updated_at" => date("c"),
        "columns" => $columnsSave,
        "pilots" => array_values($pilotsNorm),
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($json) || $json === "") {
        return false;
    }

    rkrk_live_personel_backup_current_file($path);

    $tmp = $path . ".tmp." . substr(hash("sha1", microtime(true) . "|" . $path), 0, 10);
    $okWrite = (@file_put_contents($tmp, $json) !== false);
    if (!$okWrite) {
        @unlink($tmp);
        return false;
    }

    $okMove = @rename($tmp, $path);
    if (!$okMove) {
        $okMove = (@copy($tmp, $path) !== false);
        @unlink($tmp);
    }
    if (!$okMove) {
        return false;
    }

    rkrk_live_personel_history_cleanup($path, 30);
    return true;
}

function rkrk_live_personel_label(string $key): string
{
    static $map = [
        "pilot_id" => "PilotID",
        "vorname" => "Vorname",
        "nachname" => "Nachname",
        "strasse" => "Straße",
        "plz" => "PLZ",
        "ort" => "Ort",
        "email" => "E-Mail",
        "telefon" => "Telefon",
        "erste_erfahrung" => "Maturität",
        "instruktor" => "Instruktor",
        "alternative_rollen" => "Alternative Rollen",
        "drohnentyp" => "Drohnentyp",
        "rkrs_pilot_id" => "RKRS Piloten ID",
        "remote_pilot_id" => "Remote Pilot ID",
        "betreiber_id" => "Betreiber ID",
        "fpl_gueltig" => "FPL gültig bis",
        "einsatzpruefung_rkrsplus" => "EP RKRS+",
        "rkrsplus" => "RKRS+",
        "note" => "Bemerkung",
        "kommentar" => "Kommentar",
        "bemerkung" => "Bemerkung",
        "updated_at" => "Zuletzt geändert",
        "version" => "Version (JSON)",
    ];
    return $map[$key] ?? $key;
}

/** HTML-title/Tooltip: ausgeschriebene Bezeichnung (sonst dasselbe wie das sichtbare Label). */
function rkrk_live_personel_label_title(string $key): string
{
    static $map = [
        "fpl_gueltig" => "Gültigkeitsdatum Fernpilotenlizenz",
        "einsatzpruefung_rkrsplus" => "Einsatzprüfung RKRS+",
        "rkrsplus" => "RKRS+",
        "instruktor" => "Instruktor",
        "erste_erfahrung" => "Maturität",
        "pilot_id" => "PilotID",
        "rkrs_pilot_id" => "RKRS Piloten ID",
        "remote_pilot_id" => "Remote Pilot ID",
        "betreiber_id" => "Betreiber ID",
    ];
    return $map[$key] ?? rkrk_live_personel_label($key);
}

function rkrk_live_personel_is_checkbox_col(string $key): bool
{
    return $key === "instruktor" || $key === "rkrsplus";
}

function rkrk_live_personel_is_date_col(string $key): bool
{
    return $key === "fpl_gueltig" || $key === "einsatzpruefung_rkrsplus";
}

/** Wert für <input type="date"> (YYYY-MM-DD); leere/unparsebare Werte → "". */
function rkrk_live_personel_date_input_value(string $raw): string
{
    $s = trim($raw);
    if ($s === "") {
        return "";
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
        return $m[1] . "-" . $m[2] . "-" . $m[3];
    }
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})$/', $s, $m)) {
        $y = (int)$m[3];
        if ($y < 100) {
            $y += 2000;
        }
        return sprintf("%04d-%02d-%02d", $y, (int)$m[2], (int)$m[1]);
    }
    $ts = strtotime(str_replace("/", ".", $s));
    if ($ts !== false && $ts > 0) {
        return date("Y-m-d", $ts);
    }
    return "";
}

function rkrk_live_personel_date_display(string $raw): string
{
    $iso = rkrk_live_personel_date_input_value($raw);
    if ($iso === "") {
        return trim($raw);
    }
    $ts = strtotime($iso . " 00:00:00");
    if ($ts === false) {
        return trim($raw);
    }
    return date("d.m.Y", $ts);
}

function rkrk_live_personel_use_textarea(string $key, ?string $val = null): bool
{
    if (rkrk_live_personel_is_checkbox_col($key) || rkrk_live_personel_is_date_col($key)) {
        return false;
    }
    if ($key === "pilot_id") {
        return false;
    }
    if (preg_match('/note|bemerkung|kommentar|text|beschreibung|info|adresse|alternative_rollen|rollen/i', $key)) {
        return true;
    }
    if ($val !== null && strlen($val) > 160) {
        return true;
    }
    return false;
}

/**
 * Zellbreiten: Liste kompakt; Bearbeitung mit Mindestbreiten (kein Abschneiden beim Editieren).
 *
 * @param 'list'|'edit' $tableMode
 */
function rkrk_live_personel_col_cell_attrs(string $col, string $tableMode = "list"): string
{
    $edit = $tableMode === "edit";
    if ($col === "pilot_id") {
        return $edit
            ? ' style="min-width:4.5rem;max-width:6rem;width:1%;white-space:nowrap"'
            : ' style="max-width:4rem;width:1%;white-space:nowrap"';
    }
    if ($col === "rkrs_pilot_id" || $col === "remote_pilot_id" || $col === "betreiber_id") {
        return $edit
            ? ' style="min-width:8rem;max-width:none;white-space:normal"'
            : ' style="max-width:9rem;width:1%;white-space:nowrap"';
    }
    if ($col === "plz" || $col === "erste_erfahrung") {
        if ($edit) {
            return $col === "plz"
                ? ' style="min-width:6rem;max-width:none;white-space:normal"'
                : ' style="min-width:9rem;max-width:none;white-space:normal"';
        }
        return ' style="max-width:5rem;width:1%;white-space:nowrap"';
    }
    if ($col === "instruktor" || $col === "rkrsplus") {
        return ' style="max-width:3.5rem;width:1%;text-align:center;white-space:nowrap"';
    }
    if ($col === "fpl_gueltig" || $col === "einsatzpruefung_rkrsplus") {
        return $edit
            ? ' style="min-width:9.5rem;max-width:none;white-space:nowrap"'
            : ' style="max-width:8rem;width:1%;white-space:nowrap"';
    }
    if ($col === "telefon") {
        return ' style="min-width:' . ($edit ? "12rem" : "11rem") . '"';
    }
    if ($col === "vorname" || $col === "nachname") {
        return ' style="min-width:' . ($edit ? "10rem" : "9rem") . '"';
    }
    if ($col === "email") {
        return ' style="min-width:' . ($edit ? "16rem" : "14rem") . '"';
    }
    if ($col === "alternative_rollen") {
        return ' style="min-width:' . ($edit ? "18rem" : "16rem") . '"';
    }
    if ($col === "strasse" || $col === "ort" || $col === "drohnentyp") {
        return ' style="min-width:' . ($edit ? "12rem" : "10rem") . '"';
    }
    if ($edit) {
        return ' style="min-width:8rem;max-width:none"';
    }
    return "";
}

/** Redirect relativ zum Webroot (SCRIPT_NAME) — zuverlässiger als nur Dateiname (Reverse-Proxy / Synology). */
function rkrk_live_personel_redirect(array $queryParams): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $script = (string)($_SERVER["SCRIPT_NAME"] ?? "");
    if ($script === "") {
        $script = "/rkrk_assess_live_personel.php";
    }
    $ak = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
    if ($ak !== "" && !array_key_exists($ak, $queryParams)) {
        $queryParams[$ak] = "";
    }
    $qs = http_build_query($queryParams);
    $target = $script . ($qs !== "" ? "?" . $qs : "");

    if (!headers_sent()) {
        header("Location: " . $target, true, 303);
        exit;
    }

    $safeUrl = htmlspecialchars($target, ENT_QUOTES | ENT_HTML5, "UTF-8");
    $jsUrl = json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Weiterleitung…</title></head><body>";
    echo "<script>window.location.href=" . $jsUrl . ";</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=" . $safeUrl . "'></noscript>";
    echo "<p><a href='" . $safeUrl . "'>Weiter</a></p></body></html>";
    exit;
}

/** Mobil/Tablet: Sortier-Dropdown (thead ist im Kartenlayout ausgeblendet). */
function rkrk_live_personel_echo_mobile_sort_bar(array $columns, string $selectId): void
{
    echo "<div class='personel-mobile-only personel-mobile-bar' role='navigation' aria-label='Sortierung'>";
    echo "<label for='" . rkrk_live_esc($selectId) . "'>Sortierung</label> ";
    echo "<select id='" . rkrk_live_esc($selectId) . "' class='personel-mobile-sort-select'>";
    echo "<option value=''>— Spalte wählen —</option>";
    foreach ($columns as $col) {
        $lab = rkrk_live_personel_label($col);
        echo "<option value='" . rkrk_live_esc($col) . ":1'>" . rkrk_live_esc($lab) . " (aufsteigend)</option>";
        echo "<option value='" . rkrk_live_esc($col) . ":-1'>" . rkrk_live_esc($lab) . " (absteigend)</option>";
    }
    echo "</select></div>";
}

require_once __DIR__ . "/rkrk_assess_live_personel_io.php";
require_once __DIR__ . "/rkrk_checklist_toolbar_inc.php";

GrantAccess($folderoffset );

if (empty($LoggedOn)) {
    if (function_exists('WriteToLog')) {
        WriteToLog("open", basename($_SERVER['PHP_SELF']), "", "", "", "",$folderoffset );
    }
    rkrk_assess_theme_link_tags();
    rkrk_assess_emit_setting_carry_script();
    headerpage(function_exists('rkrk_assess_copyright_holder') ? rkrk_assess_copyright_holder() : (string) ($copyright ?? 'drohnenrehkitzrettung.ch'), false, $folderoffset);
    if (function_exists('RegisterForm')) {
        RegisterForm(getIPAddress(), "Login", true);
    }
    exit;
}

/* POST-Body von PHP verworfen (z. B. post_max_size): $_POST leer → kein save_pilots, wirkt wie „hängendes“ Speichern */
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && (int)($_SERVER["CONTENT_LENGTH"] ?? 0) > 0
    && empty($_POST)
    && empty($_FILES)
) {
    rkrk_live_personel_redirect(["err" => "post_size"]);
}

//$rkrkDataRoot = isset($rkrkAssessFsDataRoot) ? $rkrkAssessFsDataRoot : $RKRKRelRKRKDataLocation;
//$rkrkLiveassessData = $rkrkDataRoot . "data/liveassess";
//$rkrkLivePersonelRoot = $rkrkLiveassessData . "/personel";
//echo "<br><br><div>Root:".$rkrkLivePersonelRoot."</div>";
$pilotFile = $rkrkLivePersonelRoot . DIRECTORY_SEPARATOR . "pilotenliste.json";

if (!is_dir($rkrkLivePersonelRoot)) {
    @mkdir($rkrkLivePersonelRoot, 0777, true);
}

[$rows, $columnsFile] = rkrk_live_personel_load_rows($pilotFile);

/* UI: ausgeblendete Spalten speichern */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_personel_ui"])) {
    $colsTmp = rkrk_live_personel_finalize_columns($rows, $columnsFile);
    $showAll = isset($_POST["col_vis_show_all"]) && (string)$_POST["col_vis_show_all"] !== "";
    $hidden = [];
    if (!$showAll) {
        $visiblePosted = [];
        if (isset($_POST["col_visible"]) && is_array($_POST["col_visible"])) {
            foreach ($_POST["col_visible"] as $c) {
                if (is_string($c) && in_array($c, $colsTmp, true)) {
                    $visiblePosted[$c] = true;
                }
            }
        }
        foreach ($colsTmp as $c) {
            if (!isset($visiblePosted[$c])) {
                $hidden[] = $c;
            }
        }
    }
    rkrk_live_personel_save_ui_settings(["hidden_columns" => $hidden]);
    $redirUi = ["ui_saved" => "1"];
    $uiView = isset($_POST["ui_view"]) ? trim((string)$_POST["ui_view"]) : "";
    if ($uiView === "list") {
        $redirUi["view"] = "list";
    }
    rkrk_live_personel_redirect($redirUi);
}

/* JSON-Export (GET) — Inhalt pilotenliste.json, vor jeder HTML-Ausgabe */
if (isset($_GET["export"]) && (string)$_GET["export"] === "json") {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $out = "";
    if (is_file($pilotFile)) {
        $raw = @file_get_contents($pilotFile);
        $out = $raw !== false ? (string)$raw : "";
    }
    if (trim($out) === "") {
        $columnsEx = rkrk_live_personel_finalize_columns($rows, $columnsFile);
        $out = json_encode([
            "version" => 2,
            "updated_at" => date("c"),
            "columns" => $columnsEx,
            "pilots" => array_values($rows),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    if ($out === "") {
        rkrk_live_personel_redirect(["err" => "export"]);
    }
    $fn = "pilotenliste_" . date("Y-m-d") . ".json";
    header("Content-Type: application/json; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"" . str_replace('"', "", $fn) . "\"");
    header("Content-Length: " . (string)strlen($out));
    header("Cache-Control: private, max-age=0");
    echo $out;
    exit;
}

/* Import (POST, multipart) — zusammenführen oder nur fehlende Instruktoren */
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["import_merge"])
    && isset($_FILES["import_file"])
    && is_uploaded_file((string)($_FILES["import_file"]["tmp_name"] ?? ""))
) {
    $tmpUp = (string)$_FILES["import_file"]["tmp_name"];
    $origName = (string)($_FILES["import_file"]["name"] ?? "");
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $payload = [];
    if ($ext === "csv") {
        $rawImp = (string)@file_get_contents($tmpUp);
        $payload = rkrk_live_personel_io_parse_csv_string($rawImp);
    } elseif ($ext === "xlsx") {
        [$hdr, $mat] = rkrk_live_personel_io_parse_xlsx($tmpUp);
        $payload = rkrk_live_personel_io_matrix_to_rows($hdr, $mat);
    } else {
        rkrk_live_personel_redirect(["err" => "import_fmt"]);
    }
    if ($payload === []) {
        rkrk_live_personel_redirect(["err" => "import_empty"]);
    }
    $action = (string)($_POST["import_action"] ?? "merge");
    if ($action === "only_new_instr") {
        [$merged, $added] = rkrk_live_personel_io_merge_only_new_instructors(
            $rows,
            $payload,
            static function (array $r): string {
                return rkrk_live_personel_dedupe_key($r);
            }
        );
        $upd = 0;
    } else {
        $allInstr = isset($_POST["import_all_instruktor"]) && (string)$_POST["import_all_instruktor"] === "1";
        [$merged, $added, $upd] = rkrk_live_personel_io_merge_rows(
            $rows,
            $payload,
            $allInstr,
            static function (array $r): string {
                return rkrk_live_personel_dedupe_key($r);
            },
            static function (array $a, array $b): array {
                return rkrk_live_personel_merge_pilot_rows($a, $b);
            }
        );
    }
    [$merged, /* $cid */] = rkrk_live_personel_ensure_ids($merged);
    [$merged, $chDed, $dedRem] = rkrk_live_personel_deduplicate_rows($merged);
    [$merged, /* $pidSeq */] = rkrk_live_personel_apply_pilot_id_sequence($merged);
    $columnsImp = rkrk_live_personel_finalize_columns($merged, $columnsFile);
    rkrk_live_personel_save_rows($pilotFile, $columnsImp, $merged);
    $q = ["saved" => "1", "imp" => (string)(int)$added, "imp_upd" => (string)(int)$upd];
    if ($chDed && $dedRem > 0) {
        $q["deduped"] = (string)(int)$dedRem;
    }
    rkrk_live_personel_redirect($q);
}

/* Löschen ZUERST: sonst verschluckt die Auto-Migration (IDs/Dedupe) den ?del=-Parameter per Redirect. */
if (isset($_GET["del"])) {
    $delId = trim((string)$_GET["del"]);
    $idKnown = false;
    foreach ($rows as $rx) {
        if ((string)($rx["id"] ?? "") === $delId) {
            $idKnown = true;
            break;
        }
    }
    if ($idKnown && $delId !== "" && strlen($delId) <= 80 && preg_match('/^[a-zA-Z0-9_\-.]+$/', $delId)) {
        $rows = array_values(array_filter($rows, static function ($r) use ($delId) {
            return (string)($r["id"] ?? "") !== $delId;
        }));
        [$rows, /* $pidSeq */] = rkrk_live_personel_apply_pilot_id_sequence($rows);
        $columns = rkrk_live_personel_finalize_columns($rows, $columnsFile);
        rkrk_live_personel_save_rows($pilotFile, $columns, $rows);
    }
    $redirDel = ["saved" => "1"];
    if (isset($_GET["view"]) && (string)$_GET["view"] === "list") {
        $redirDel["view"] = "list";
    }
    rkrk_live_personel_redirect($redirDel);
}

/*
 * Speichern VOR Auto-Migration (ensure_ids / dedupe + Redirect): sonst kann ein Redirect
 * die POST-Daten verschlucken, bevor save_pilots verarbeitet wird.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_pilots"])) {
    $posted = [];
    $rowsJsonRaw = isset($_POST["rows_json"]) ? (string)$_POST["rows_json"] : "";
    if ($rowsJsonRaw !== "") {
        $rowsDecoded = @json_decode($rowsJsonRaw, true);
        if (is_array($rowsDecoded)) {
            foreach ($rowsDecoded as $rj) {
                if (is_array($rj)) {
                    $posted[] = $rj;
                }
            }
        }
    }
    if ($posted === []) {
        $posted = isset($_POST["row"]) && is_array($_POST["row"]) ? $_POST["row"] : [];
    }
    $orderRaw = isset($_POST["columns_order"]) ? (string)$_POST["columns_order"] : "";
    $orderDecoded = @json_decode($orderRaw, true);
    $orderCols = [];
    if (is_array($orderDecoded)) {
        foreach ($orderDecoded as $c) {
            if (is_string($c) && $c !== "id" && rkrk_live_personel_valid_key($c)) {
                $orderCols[] = $c;
            }
        }
    }
    $newRows = [];
    foreach ($posted as $r) {
        if (!is_array($r)) {
            continue;
        }
        $id = trim((string)($r["id"] ?? ""));
        if ($id === "" || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            $id = bin2hex(random_bytes(8));
        }
        $clean = ["id" => $id];
        foreach ($r as $k => $v) {
            if ($k === "id") {
                continue;
            }
            if (!is_string($k) || !rkrk_live_personel_valid_key($k)) {
                continue;
            }
            $clean[$k] = is_array($v) ? rkrk_live_personel_cell_string($v) : trim((string)$v);
        }
        foreach (["instruktor", "rkrsplus"] as $flagCol) {
            $clean[$flagCol] = (!empty($r[$flagCol]) && (string)$r[$flagCol] !== "0") ? "1" : "";
        }
        if (!rkrk_live_personel_row_is_empty($clean)) {
            $newRows[] = $clean;
        }
    }
    [$newRows, /* $dupChanged */, /* $removed */] = rkrk_live_personel_deduplicate_rows($newRows);
    [$newRows, /* $pidSeq */] = rkrk_live_personel_apply_pilot_id_sequence($newRows);
    $columns = rkrk_live_personel_finalize_columns($newRows, $orderCols);
    rkrk_live_personel_save_rows($pilotFile, $columns, $newRows);
    rkrk_live_personel_redirect(["saved" => "1"]);
}

[$rows, $changedIds] = rkrk_live_personel_ensure_ids($rows);
[$rows, $changedDedupe, $dedupeRemoved] = rkrk_live_personel_deduplicate_rows($rows);
[$rows, $changedPid] = rkrk_live_personel_apply_pilot_id_sequence($rows);
if ($changedIds || $changedDedupe || $changedPid) {
    $columns = rkrk_live_personel_finalize_columns($rows, $columnsFile);
    rkrk_live_personel_save_rows($pilotFile, $columns, $rows);
    if ($changedIds || $changedDedupe) {
        $q = ["saved" => "1"];
        if ($changedDedupe && $dedupeRemoved > 0) {
            $q["deduped"] = (string)(int)$dedupeRemoved;
        }
        rkrk_live_personel_redirect($q);
    }
}
$columns = rkrk_live_personel_finalize_columns($rows, $columnsFile);
$needColMerge = false;
foreach ($columns as $cMerge) {
    if (!in_array($cMerge, $columnsFile, true)) {
        $needColMerge = true;
        break;
    }
}
if ($needColMerge) {
    rkrk_live_personel_save_rows($pilotFile, $columns, $rows);
}
$personelUiSettings = rkrk_live_personel_load_ui_settings();
$hiddenColumns = [];
foreach ($personelUiSettings["hidden_columns"] as $hc) {
    if (in_array($hc, $columns, true)) {
        $hiddenColumns[] = $hc;
    }
}
$visibleColumns = rkrk_live_personel_visible_columns($columns, $hiddenColumns);
if ($visibleColumns === []) {
    // Nie alle Spalten ausblenden — Fallback: alle zeigen.
    $hiddenColumns = [];
    $visibleColumns = $columns;
}

$personelUiView = (isset($_GET["view"]) && (string)$_GET["view"] === "list") ? "list" : "edit";

/** Query für Ansichts-Umschalter (?view=list|edit), behält Statusmeldungen. */
function rkrk_live_personel_view_href(string $view): string
{
    $q = ["view" => $view];
    foreach (["saved", "err", "deduped", "imp", "imp_upd", "GA", "TEST", "RKRK", "RKRS", "RKRSplus", "RKRSprev"] as $k) {
        if (!array_key_exists($k, $_GET)) {
            continue;
        }
        $v = $_GET[$k];
        if (is_array($v)) {
            continue;
        }
        $q[$k] = ($v === null || $v === false) ? "" : (string)$v;
    }
    $ak = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
    if ($ak !== "" && array_key_exists($ak, $_GET)) {
        $v = $_GET[$ak];
        $q[$ak] = (is_string($v) || $v === null || $v === false) ? (string)$v : "";
    }
    $qs = http_build_query($q, "", "&", PHP_QUERY_RFC3986);
    return "rkrk_assess_live_personel.php" . ($qs !== "" ? "?" . $qs : "");
}

/** Listen-Titel aus Vor-/Nachname oder Fallback. */
function rkrk_live_personel_list_title(array $r): string
{
    $vor = trim(rkrk_live_personel_cell_string($r["vorname"] ?? ""));
    $nach = trim(rkrk_live_personel_cell_string($r["nachname"] ?? ""));
    $parts = array_filter([$nach, $vor]);
    $t = implode(", ", $parts);
    if ($t !== "") {
        return $t;
    }
    $em = trim(rkrk_live_personel_cell_string($r["email"] ?? ""));
    if ($em !== "") {
        return $em;
    }
    $tel = trim(rkrk_live_personel_cell_string($r["telefon"] ?? ""));
    if ($tel !== "") {
        return $tel;
    }
    return "Eintrag";
}

function rkrk_live_personel_drohnentyp_warn_level(string $raw): string
{
    $s = mb_strtolower(trim($raw), "UTF-8");
    if ($s === "") {
        return "none";
    }
    if (strpos($s, "dji mavic 3t") === 0) {
        return "orange";
    }
    if (strpos($s, "anderes") === 0) {
        return "red";
    }
    if (strpos($s, "dji mavic 2") === 0) {
        return "red";
    }
    if (strpos($s, "autel") === 0) {
        return "red";
    }
    if (strpos($s, "dji matrice 640") === 0) {
        return "red";
    }
    return "none";
}

/** Sort-/Filtertext für Listen-Tabelle (Attribut data-sort). */
function rkrk_live_personel_list_sort_string(array $r, string $col): string
{
    $val = rkrk_live_personel_cell_string($r[$col] ?? "");
    if (rkrk_live_personel_is_checkbox_col($col)) {
        return ($val === "1" || strcasecmp($val, "ja") === 0) ? "1" : "0";
    }
    return mb_strtolower(trim($val), "UTF-8");
}

/** HTML-Inhalt einer Listen-Zelle (ohne umschließendes td). */
function rkrk_live_personel_list_cell_html(array $r, string $col): string
{
    $val = rkrk_live_personel_cell_string($r[$col] ?? "");
    if (rkrk_live_personel_is_checkbox_col($col)) {
        $on = ($val === "1" || strcasecmp($val, "ja") === 0);
        return $on ? "<span class='personel-instr-yes'>Ja</span>" : "<span class='personel-instr-no'>—</span>";
    }
    if (rkrk_live_personel_is_date_col($col)) {
        if (trim($val) === "") {
            return "";
        }
        return rkrk_live_esc(rkrk_live_personel_date_display($val));
    }
    if ($col === "email" && trim($val) !== "") {
        return "<a href=\"mailto:" . rkrk_live_esc($val) . "\">" . rkrk_live_esc($val) . "</a>";
    }
    if ($col === "drohnentyp" && trim($val) !== "") {
        $lvl = rkrk_live_personel_drohnentyp_warn_level($val);
        if ($lvl === "red") {
            return "<span class='personel-drone-flag personel-drone-flag-red'>" . nl2br(rkrk_live_esc($val)) . "</span>";
        }
        if ($lvl === "orange") {
            return "<span class='personel-drone-flag personel-drone-flag-orange'>" . nl2br(rkrk_live_esc($val)) . "</span>";
        }
    }
    if ($val === "") {
        return "";
    }
    return nl2br(rkrk_live_esc($val));
}

/* Anzeige: Standard Sortierung Nachname, dann Vorname (ohne Datei zu ändern, bis der Benutzer speichert). */
$hasNach = in_array("nachname", $columns, true);
$hasVor = in_array("vorname", $columns, true);
if ($hasNach || $hasVor) {
    usort($rows, static function (array $ra, array $rb) use ($hasNach, $hasVor): int {
        if ($hasNach) {
            $a = rkrk_live_personel_cell_string($ra["nachname"] ?? "");
            $b = rkrk_live_personel_cell_string($rb["nachname"] ?? "");
            $c = strnatcasecmp($a, $b);
            if ($c !== 0) {
                return $c;
            }
        }
        if ($hasVor) {
            return strnatcasecmp(
                rkrk_live_personel_cell_string($ra["vorname"] ?? ""),
                rkrk_live_personel_cell_string($rb["vorname"] ?? "")
            );
        }
        return 0;
    });
    $rows = array_values($rows);
}

if (function_exists('WriteToLog')) {
    WriteToLog("open", basename($_SERVER['PHP_SELF']), "", "", "", "",$folderoffset );
}
rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
headerpage(function_exists('rkrk_assess_copyright_holder') ? rkrk_assess_copyright_holder() : (string) ($copyright ?? 'drohnenrehkitzrettung.ch'), false, $folderoffset);

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast'  && !$onRKRS)
	&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
if ($showTopMenu) {
    CreateTopMenu($folderoffset);
    echo "<br><br>";
	if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
		rkrk_assess_emit_admin_url_managers_menu_strip();
	}
}

rkrk_checklist_toolbar_echo_styles_once();

echo "<style>
/* Volle nutzbare Breite (Theme .rkrk-wrap: max-width 1200px) */
body .rkrk-wrap[data-mode='personel']{max-width:none;width:100%;box-sizing:border-box;margin:0;padding:14px max(14px, env(safe-area-inset-right)) 14px max(14px, env(safe-area-inset-left));}
body .rkrk-wrap[data-mode='personel'] .rkrk-card{max-width:none;}
body .rkrk-wrap[data-mode='personel'] table.personel-table{width:max-content;min-width:100%;box-sizing:border-box;}
.personel-toolbar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:8px;}
.personel-toolbar label{font-size:12px;color:#5f6b66;}
.personel-toolbar input[type=search]{min-width:180px;flex:1;max-width:none;padding:4px 7px;border:1px solid #cfd8cc;border-radius:6px;font-size:12px;}
.personel-toolbar .personel-filter-col-wrap{display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
.personel-toolbar .personel-filter-col-wrap select{max-width:min(100%,18rem);padding:3px 7px;border:1px solid #cfd8cc;border-radius:6px;font-size:12px;}
.personel-table-wrap{overflow:auto;max-width:100%;max-height:calc(100dvh - 7.5rem);border:1px solid var(--border);border-radius:8px;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;}
.personel-table{width:max-content;min-width:100%;border-collapse:separate;border-spacing:0;table-layout:auto;font-size:12px;line-height:1.25;}
.personel-table th,.personel-table td{padding:3px 6px;border-bottom:1px solid var(--border,#e2e8e0);}
.personel-table thead tr.personel-filter-row th{background:var(--table-head-bg);font-weight:400;padding:2px 4px;}
.personel-table .personel-col-filter{width:100%;min-width:4rem;max-width:100%;box-sizing:border-box;padding:3px 5px;border:1px solid #cfd8cc;border-radius:4px;font-size:11px;}
.personel-table th{background:var(--table-head-bg);font-weight:600;font-size:11px;white-space:normal;word-break:break-word;hyphens:auto;line-height:1.15;min-width:3.5rem;}
.personel-table thead th{position:sticky;z-index:5;background:var(--table-head-bg);box-shadow:0 1px 0 var(--border,#d5e0d2);}
.personel-table thead tr:first-child th{top:0;}
.personel-table thead tr.personel-filter-row th{top:var(--personel-sticky-head-h,2.35rem);z-index:4;}
.personel-table.personel-table--edit th.sortable{min-width:5rem;}
.personel-table.personel-table--edit td:not(.personel-actions){vertical-align:top;}
.personel-table.personel-table--edit input.text-input,.personel-table.personel-table--edit textarea.text-input{min-width:7rem;max-width:none;}
.personel-table.personel-table--edit textarea.text-input{min-width:12rem;max-height:min(70vh,28rem);}
.personel-table th.sortable{cursor:pointer;user-select:none;}
.personel-table th.sortable:hover{background:var(--toolbar-hover-bg);}
.personel-table th .sort-ind{font-size:9px;opacity:.55;margin-left:2px;}
.personel-table td{vertical-align:top;}
.personel-table input.text-input,.personel-table textarea.text-input{width:100%;min-width:6.5rem;max-width:none;box-sizing:border-box;padding:3px 6px;border:1px solid #cfd8cc;border-radius:4px;font-size:12px;line-height:1.25;}
.personel-table textarea.text-input{min-height:2.75em;max-height:14rem;resize:vertical;line-height:1.3;}
.personel-actions{white-space:nowrap;width:2.25rem;text-align:center;padding:1px;}
.personel-del{display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:26px;padding:0 5px;border:1px solid #c9d4c5;border-radius:5px;background:#f6faf5;color:#8b2e2e;font-size:14px;line-height:1;text-decoration:none;font-weight:600;}
.personel-del:hover{background:#fdeeee;border-color:#d4a5a5;color:#6a1515;}
.personel-actions .btn-link{background:none;border:none;color:#48673A;cursor:pointer;text-decoration:underline;padding:0;font-size:11px;}
.personel-hint{font-size:11px;color:#5f6b66;margin-top:4px;line-height:1.35;}
.cl-header h1{font-size:1.35rem;margin:0 0 6px;}
.personel-card-hint{color:#5f6b66;font-size:11px;line-height:1.35;margin:0 0 8px;}
.personel-table tbody tr.personel-row.personel-exp-0 > td{background:#fafcfa;}
.personel-table tbody tr.personel-row.personel-exp-1 > td{background:#fceaea;}
.personel-table tbody tr.personel-row.personel-exp-2 > td{background:#fff6dc;}
.personel-table tbody tr.personel-row.personel-exp-3 > td{background:#e8f4ea;}
.personel-table tbody tr.personel-row.personel-exp-4 > td{background:#d8ecde;}
.personel-table tbody tr.personel-row.personel-exp-5 > td{background:#d9f0ec;}
.personel-table tbody tr.personel-row.personel-exp-6 > td{background:#e2ecf8;}
.personel-table input.personel-checkbox{width:1.05rem;height:1.05rem;margin:0;vertical-align:middle;cursor:pointer;accent-color:#48673A;}
.personel-io-ref{font-size:10px;color:#8a9690;margin:0 0 6px;line-height:1.3;}
.personel-io-toolbar{margin-top:10px;}
.personel-io-toolbar.chk-toolbar input[type=file]{max-width:220px;font-size:11px;}
.personel-view-toggle{display:flex;flex-wrap:wrap;gap:0;margin:0 0 14px;}
.personel-view-toggle a{display:inline-flex;align-items:center;padding:8px 16px;border:1px solid #cfd8cc;font-size:13px;text-decoration:none;color:#3d4a42;background:#fff;line-height:1.2;}
.personel-view-toggle a:first-of-type{border-radius:8px 0 0 8px;}
.personel-view-toggle a:last-of-type{border-radius:0 8px 8px 0;}
.personel-view-toggle a:not(:first-of-type){margin-left:-1px;}
.personel-view-toggle a.is-active{background:var(--table-head-bg);font-weight:600;border-color:var(--input-border);color:var(--text);}
.personel-view-toggle a:focus-visible{outline:2px solid #48673A;outline-offset:2px;}
.personel-list-hint{font-size:12px;color:#5f6b66;margin:0 0 10px;line-height:1.4;}
.personel-drone-legend{margin:14px 0 0;padding:10px 12px;border:1px solid #cfd8cc;border-radius:8px;background:#f7faf7;font-size:12px;line-height:1.45;color:#334155;}
.personel-drone-legend summary{cursor:pointer;font-weight:700;color:#1f2c24;list-style-position:outside;}
.personel-drone-legend summary::-webkit-details-marker{color:#48673A;}
.personel-drone-legend[open] summary{margin-bottom:6px;}
.personel-drone-legend p{margin:6px 0 0;}
.personel-drone-legend ul{margin:6px 0 0;padding-left:1.15rem;}
.personel-drone-legend li{margin:3px 0;}
.personel-drone-legend .personel-leg-mat{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;margin-top:8px;align-items:start;font-size:11px;}
.personel-drone-legend .personel-leg-mat > span:nth-child(2n){min-width:0;word-break:break-word;line-height:1.35;}
.personel-drone-legend .personel-leg-swatch{display:inline-block;width:1.1rem;height:1.1rem;border-radius:4px;border:1px solid rgba(0,0,0,.08);vertical-align:middle;}
.personel-drone-legend code{font-size:11px;background:rgba(255,255,255,.7);padding:1px 4px;border-radius:3px;}
.personel-read-wrap{border:1px solid var(--border);border-radius:8px;background:#fafcfa;overflow:auto;max-width:100%;max-height:calc(100dvh - 7.5rem);-webkit-overflow-scrolling:touch;}
.personel-read-toolbar{margin-bottom:8px;}
.personel-read-count{font-size:12px;color:#5f6b66;margin-left:auto;white-space:nowrap;}
.personel-read-table{font-size:13px;line-height:1.35;}
.personel-read-table td,.personel-read-table th{vertical-align:top;}
.personel-read-table td{word-break:break-word;hyphens:auto;max-width:40rem;}
.personel-read-table th{white-space:normal;word-break:break-word;}
.personel-read-table .personel-instr-yes{font-weight:600;color:#254428;}
.personel-read-table .personel-instr-no{color:#9aa89e;}
.personel-read-table .personel-warn-mark{font-weight:700;}
.personel-read-table .personel-warn-mark-red{color:#c22;}
.personel-read-table .personel-warn-mark-orange{color:#c86b00;}
.personel-read-table .personel-warn-col{width:2rem;text-align:center;white-space:nowrap;}
.personel-read-table .personel-drone-flag{display:inline-block;padding:2px 7px;border-radius:6px;font-weight:600;line-height:1.25;}
.personel-read-table .personel-drone-flag-red{background:#f6cfcf;color:#5a1010;}
.personel-read-table .personel-drone-flag-orange{background:#ffe1bd;color:#6a3f00;}
.personel-read-table a{color:#48673A;}
.personel-mobile-only{display:none;}
.personel-mobile-bar{margin-bottom:10px;}
.personel-mobile-bar label{display:block;font-size:12px;color:#5f6b66;margin-bottom:4px;}
.personel-mobile-sort-select{width:100%;max-width:100%;box-sizing:border-box;min-height:44px;font-size:16px;padding:8px 10px;border:1px solid #cfd8cc;border-radius:8px;background:#fff;}
/* Tablet + Mobil: keine Tabellenköpfe, Karten — Lesen & Bearbeiten */
@media (max-width:1100px){
.personel-mobile-only{display:block;}
.cl-header h1{font-size:clamp(1.05rem,4.2vw,1.35rem);}
.personel-view-toggle a{min-height:44px;box-sizing:border-box;}
.personel-read-toolbar .personel-filter-col-wrap{flex:1 1 100%;}
.personel-read-toolbar input#personel-list-search{max-width:none;}
.personel-table-wrap{overflow-x:visible;overflow-y:visible;max-height:none;-webkit-overflow-scrolling:auto;touch-action:manipulation;}
body .rkrk-wrap[data-mode='personel'] table.personel-table{width:100%!important;min-width:0!important;}
.personel-table-wrap .personel-table thead{display:none;}
.personel-table-wrap .personel-table tbody{display:block;}
/* Zwischenstufe Tablet: 2–3 Felder nebeneinander (auto-fill) */
.personel-table-wrap .personel-table tbody tr.personel-row{
display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));column-gap:12px;row-gap:4px;align-items:start;
width:100%;box-sizing:border-box;margin-bottom:14px;border:1px solid var(--border,#e4e7e4);border-radius:12px;padding:10px 14px 14px;
box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-0{background:#fafcfa;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-1{background:#fceaea;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-2{background:#fff6dc;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-3{background:#e8f4ea;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-4{background:#d8ecde;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-5{background:#d9f0ec;}
.personel-table-wrap .personel-table tbody tr.personel-row.personel-exp-6{background:#e2ecf8;}
.personel-table-wrap .personel-table tbody tr.personel-row > td{
display:flex;flex-direction:column;align-items:stretch;width:auto!important;max-width:none!important;min-width:0!important;
border:none!important;border-bottom:none!important;padding:8px 4px!important;background:transparent!important;box-sizing:border-box;
}
.personel-table-wrap .personel-table tbody tr.personel-row > td:not(.personel-actions):not(.personel-empty-msg)::before{
content:attr(data-label);font-size:10px;font-weight:500;color:#6f7a74;margin-bottom:5px;line-height:1.2;word-break:break-word;letter-spacing:.02em;
}
.personel-table-wrap .personel-table tbody tr.personel-row > td.personel-actions{
grid-column:1/-1;flex-direction:row;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;
padding-top:12px!important;margin-top:6px;border-top:1px solid rgba(0,0,0,.1)!important;
}
.personel-table-wrap .personel-table tbody tr.personel-row > td.personel-actions::before{
content:attr(data-label);font-size:10px;font-weight:500;color:#7a8680;margin-bottom:0;flex:1;text-align:left;letter-spacing:.02em;
}
.personel-table-wrap .personel-table .personel-del{min-width:44px;min-height:44px;font-size:20px;border-radius:10px;}
.personel-table-wrap .personel-table .personel-actions .personel-remove-new{min-height:44px;padding:10px 8px;font-size:15px;touch-action:manipulation;}
.personel-table-wrap .personel-table input.text-input,.personel-table-wrap .personel-table textarea.text-input{
min-height:42px!important;font-size:15px!important;line-height:1.35!important;padding:8px 10px!important;border-radius:8px!important;
}
.personel-table-wrap .personel-table textarea.text-input{min-height:5.5rem!important;max-height:40vh!important;}
.personel-table-wrap .personel-table input.personel-checkbox{width:22px;height:22px;min-width:22px;min-height:22px;}
.personel-table-wrap .personel-table td.personel-empty-msg{grid-column:1/-1;display:block!important;text-align:center;padding:20px 12px!important;}
.personel-table-wrap .personel-table td.personel-empty-msg::before{display:none!important;content:none!important;}
}
/* Noch etwas kleinere Datenköpfe auf dem Handy */
@media (max-width:560px){
.personel-table-wrap .personel-table tbody tr.personel-row > td:not(.personel-actions):not(.personel-empty-msg)::before{font-size:9px;}
.personel-table-wrap .personel-table tbody tr.personel-row > td.personel-actions::before{font-size:9px;}
.personel-toolbar{flex-direction:column;align-items:stretch;}
.personel-toolbar label{display:flex;flex-direction:column;gap:4px;}
.personel-toolbar input[type=search],.personel-toolbar select,.personel-toolbar .personel-filter-col-wrap input[type=search]{font-size:16px!important;min-height:44px;box-sizing:border-box;}
.personel-col-filter{font-size:16px!important;min-height:40px;}
body .rkrk-wrap[data-mode='personel'] .rkrk-card .btn-primary{min-height:48px;font-size:16px;padding:12px 18px;}
#personel-add-row{width:100%;min-height:48px;font-size:16px;padding:12px 16px;box-sizing:border-box;}
.personel-table-wrap .personel-table tbody tr.personel-row{grid-template-columns:1fr;column-gap:0;}
.personel-table-wrap .personel-table tbody tr.personel-row > td{
width:100%!important;padding:12px 0!important;border-bottom:1px solid rgba(0,0,0,.08)!important;
}
.personel-table-wrap .personel-table tbody tr.personel-row > td:last-of-type{border-bottom:none!important;}
.personel-table-wrap .personel-table tbody tr.personel-row > td.personel-actions{border-top:1px solid rgba(0,0,0,.1)!important;border-bottom:none!important;}
.personel-table-wrap .personel-table .personel-del{min-width:48px;min-height:48px;font-size:22px;}
.personel-table-wrap .personel-table input.text-input,.personel-table-wrap .personel-table textarea.text-input{
min-height:48px!important;font-size:16px!important;padding:10px 12px!important;
}
.personel-table-wrap .personel-table textarea.text-input{min-height:7rem!important;max-height:50vh!important;}
.personel-table-wrap .personel-table input.personel-checkbox{width:26px;height:26px;min-width:26px;min-height:26px;}
}
.personel-list-toedit{margin-top:14px;}
.personel-col-vis{margin:10px 0 12px;border:1px solid #d7e0d8;border-radius:8px;background:#f7faf7;padding:0 10px 8px;}
.personel-col-vis>summary{cursor:pointer;font-size:13px;font-weight:600;padding:8px 2px;color:#3d4a44;list-style-position:outside;}
.personel-col-vis-hint{margin:0 0 8px;font-size:11px;color:#5f6b66;line-height:1.35;}
.personel-col-vis-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(11rem,1fr));gap:4px 10px;margin:0 0 8px;}
.personel-col-vis-item{display:flex;align-items:center;gap:6px;font-size:12px;color:#3d4a44;padding:2px 0;}
.personel-col-vis-item input{margin:0;}
.personel-col-vis-actions{margin:0;display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.personel-table th.personel-col-is-hidden,.personel-table td.personel-col-is-hidden{display:none !important;}
</style>";
echo "<script>
(function(){
  function syncPersonelStickyHead(){
    document.querySelectorAll('.personel-table-wrap').forEach(function(wrap){
      var first = wrap.querySelector('thead tr:first-child');
      if (!first) return;
      var h = Math.ceil(first.getBoundingClientRect().height);
      if (h > 0) wrap.style.setProperty('--personel-sticky-head-h', h + 'px');
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncPersonelStickyHead);
  } else {
    syncPersonelStickyHead();
  }
  window.addEventListener('resize', syncPersonelStickyHead);
  if (window.ResizeObserver) {
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('.personel-table-wrap thead tr:first-child').forEach(function(row){
        try { new ResizeObserver(syncPersonelStickyHead).observe(row); } catch (e) {}
      });
    });
  }
})();
</script>";

echo "<div class='rkrk-wrap' data-mode='personel'>";
echo "<div class='cl-header' style='position:relative; min-height:48px;'>";
echo "<h1>RKRS+ Personalliste</h1>";
echo "</div>";
echo "<div class='quick-links' style='margin:0 0 14px 0;flex-wrap:wrap;'>";
echo "</div>";
if (function_exists("rkrk_assess_portal_nav_echo")) {
	$lp_nav_admin = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool)$onAdmin);
	$lp_nav_instr = isset($onInstr) && (bool)$onInstr;
	echo "<div id='rkrk-portal-nav-slot' class='rkrk-portal-nav-slot quick-links' style='margin:0 0 14px 0;'>";
	rkrk_assess_portal_nav_echo(array(
		"btn_class" => "ql-btn",
		"schulung_mode" => false,
		"is_admin" => $lp_nav_admin,
		"is_instr" => $lp_nav_instr,
		"selected_quiz" => "",
	));
	echo "</div>";
}

if (isset($_GET["saved"]) || isset($_GET["ui_saved"])) {
    echo "<div class='rkrk-card success'>✓ Gespeichert.";
    if (isset($_GET["ui_saved"])) {
        echo " Spaltenansicht aktualisiert.";
    }
    if (isset($_GET["deduped"]) && (int)$_GET["deduped"] > 0) {
        echo " <strong>" . (int)$_GET["deduped"] . "</strong> doppelte Einträge zusammengeführt.";
    }
    if (isset($_GET["imp"]) && (int)$_GET["imp"] >= 0) {
        echo " Import: <strong>" . (int)$_GET["imp"] . "</strong> neue Zeile(n)";
        if (isset($_GET["imp_upd"]) && (int)$_GET["imp_upd"] > 0) {
            echo ", <strong>" . (int)$_GET["imp_upd"] . "</strong> bestehende aktualisiert.";
        } else {
            echo ".";
        }
    }
    echo "</div>";
}

if (isset($_GET["err"])) {
    $err = (string)$_GET["err"];
    $msg = [
        "export" => "JSON-Export fehlgeschlagen.",
        "import_fmt" => "Import: nur .xlsx oder .csv (erste Zeile = Überschriften wie Vorname, Nachname, Telefon, E-Mail, …).",
        "import_empty" => "Import: keine verwertbaren Zeilen (Kopfzeile prüfen).",
        "post_size" => "Anfrage zu groß (PHP post_max_size) — Liste kürzen/teilen oder Server-Limit erhöhen. Sonst wird nichts gespeichert.",
    ][$err] ?? "Unbekannter Fehler.";
    echo "<div class='rkrk-card' style='border-color:#c9a5a5;background:#fff5f5;'><strong>Hinweis:</strong> " . rkrk_live_esc($msg) . "</div>";
}

echo "<div class='rkrk-card'>";
$hrefList = rkrk_live_personel_view_href("list");
$hrefEdit = rkrk_live_personel_view_href("edit");
echo "<nav class='personel-view-toggle' aria-label='Ansicht wechseln'>";
echo "<a href='" . rkrk_live_esc($hrefList) . "' class='" . ($personelUiView === "list" ? "is-active " : "") . "btn secondary'" . ($personelUiView === "list" ? " aria-current='page'" : "") . ">Liste lesen</a>";
echo "<a href='" . rkrk_live_esc($hrefEdit) . "' class='" . ($personelUiView === "edit" ? "is-active " : "") . "btn secondary'" . ($personelUiView === "edit" ? " aria-current='page'" : "") . ">Tabelle bearbeiten</a>";
echo "</nav>";

if ($personelUiView === "list") {
    echo "<p class='personel-list-hint'>Lesbare Übersicht aus <code>pilotenliste.json</code> — sortieren (Spaltenkopf), filtern (Suche / Spalten). Spalte <strong>PilotID</strong>: fortlaufend 1…<em>n</em> nach Listenreihenfolge (nicht die RKRS-ID). Spalte <strong>RKRS Piloten ID</strong> ist getrennt und kann bei Aufruf von Prüfung/Test/Assessment nachgetragen werden. <strong>Löschen</strong> wirkt sofort. <strong>Farbcodierung</strong> steht unten nach der Tabelle.</p>";
    rkrk_live_personel_echo_column_visibility_panel($columns, $hiddenColumns, "list");
    echo "<div class='personel-read-wrap'>";
    echo "<div class='personel-toolbar personel-read-toolbar'>";
    echo "<label>Suche <input type='search' id='personel-list-search' placeholder='Alle Spalten durchsuchen…' autocomplete='off'></label>";
    echo "<span class='personel-filter-col-wrap'><label for='personel-list-filter-col'>Spalte</label> ";
    echo "<select id='personel-list-filter-col' title='Nur diese Spalte filtern'>";
    echo "<option value=''>— alle —</option>";
    foreach ($visibleColumns as $col) {
        echo "<option value='" . rkrk_live_esc($col) . "'>" . rkrk_live_esc(rkrk_live_personel_label($col)) . "</option>";
    }
    echo "</select>";
    echo " <input type='search' id='personel-list-filter-col-text' placeholder='Text in Spalte…' autocomplete='off' disabled style='min-width:140px;max-width:100%;flex:1;min-height:36px;padding:5px 8px;border:1px solid #cfd8cc;border-radius:6px;font-size:12px;'>";
    echo "</span>";
    echo "<span class='personel-read-count' id='personel-list-count'></span>";
    echo "</div>";
    rkrk_live_personel_echo_mobile_sort_bar($visibleColumns, "personel-list-mobile-sort");
    $listColspan = count($visibleColumns) + 2;
    echo "<div class='personel-table-wrap'><table class='personel-table personel-read-table list-table' id='personel-list-table'><thead><tr>";
    echo "<th class='personel-warn-col' title='Warnung'>!</th>";
    foreach ($visibleColumns as $col) {
        $thTitle = rkrk_live_personel_label_title($col);
        echo "<th class='sortable personel-list-sort' data-col='" . rkrk_live_esc($col) . "' data-sort-key='" . rkrk_live_esc($col) . "' title='" . rkrk_live_esc($thTitle) . "'" . rkrk_live_personel_col_cell_attrs($col) . ">" . rkrk_live_esc(rkrk_live_personel_label($col)) . "<span class='sort-ind'></span></th>";
    }
    echo "<th class='personel-actions' title='Aktion'>×</th>";
    echo "</tr><tr class='personel-filter-row'>";
    echo "<th class='personel-warn-col'></th>";
    foreach ($visibleColumns as $col) {
        echo "<th data-col='" . rkrk_live_esc($col) . "'" . rkrk_live_personel_col_cell_attrs($col) . "><input type='search' class='personel-col-filter personel-list-col-filter' data-col='" . rkrk_live_esc($col) . "' placeholder='…' title='Filter " . rkrk_live_esc(rkrk_live_personel_label($col)) . "' autocomplete='off'></th>";
    }
    echo "<th class='personel-actions'></th>";
    echo "</tr></thead><tbody id='personel-list-tbody'>";
    if ($rows === []) {
        echo "<tr><td colspan='" . (int)$listColspan . "' class='personel-list-hint personel-empty-msg' style='padding:14px;'>Noch keine Einträge — in der Bearbeitungsansicht einen Piloten hinzufügen.</td></tr>";
    }
    foreach ($rows as $r) {
        $id = trim((string)($r["id"] ?? ""));
        if ($id === "" || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            $id = bin2hex(random_bytes(8));
        }
        $expLvl = rkrk_live_personel_exp_level(rkrk_live_personel_cell_string($r["erste_erfahrung"] ?? ""));
        $searchBits = [];
        foreach ($columns as $c) {
            $searchBits[] = rkrk_live_personel_list_sort_string($r, $c);
        }
        $ivs = rkrk_live_personel_cell_string($r["instruktor"] ?? "");
        if ($ivs === "1" || strcasecmp($ivs, "ja") === 0) {
            $searchBits[] = "instruktor ja";
        }
        $rps = rkrk_live_personel_cell_string($r["rkrsplus"] ?? "");
        if ($rps === "1" || strcasecmp($rps, "ja") === 0) {
            $searchBits[] = "rkrsplus ja rkrs+";
        }
        $dataSearch = mb_strtolower(implode(" ", array_filter($searchBits, static function ($s): bool {
            return trim((string)$s) !== "";
        })), "UTF-8");
        echo "<tr data-row-id='" . rkrk_live_esc($id) . "' class='personel-row personel-exp-" . (int)$expLvl . "' data-search='" . rkrk_live_esc($dataSearch) . "'>";
        $warnLvl = rkrk_live_personel_drohnentyp_warn_level(rkrk_live_personel_cell_string($r["drohnentyp"] ?? ""));
        if ($warnLvl === "red") {
            echo "<td class='personel-warn-col'><span class='personel-warn-mark personel-warn-mark-red' title='Auffälliger Drohnentyp'>❗</span></td>";
        } elseif ($warnLvl === "orange") {
            echo "<td class='personel-warn-col'><span class='personel-warn-mark personel-warn-mark-orange' title='DJI Mavic 3T'>❗</span></td>";
        } else {
            echo "<td class='personel-warn-col'></td>";
        }
        foreach ($visibleColumns as $col) {
            $sortAttr = rkrk_live_esc(rkrk_live_personel_list_sort_string($r, $col));
            $inner = rkrk_live_personel_list_cell_html($r, $col);
            $dLab = rkrk_live_esc(rkrk_live_personel_label($col));
            echo "<td data-label='" . $dLab . "' data-col='" . rkrk_live_esc($col) . "' data-sort='" . $sortAttr . "'" . rkrk_live_personel_col_cell_attrs($col) . ">" . $inner . "</td>";
        }
        $delQs = "rkrk_assess_live_personel.php?del=" . rawurlencode($id) . "&view=list";
        echo "<td class='personel-actions' data-label='Aktion'><a href='" . rkrk_live_esc($delQs) . "' class='personel-del' title='Eintrag löschen' onclick='return confirm(\"Diesen Eintrag aus pilotenliste.json löschen?\");'>&times;</a></td>";
        echo "</tr>";
    }
    echo "</tbody></table></div></div>";
    echo "<details class='personel-drone-legend'><summary>Farbcodierung: Maturität (Zeile) &amp; Drohnentyp</summary>";
    echo "<p><strong>Zeilenfarbe</strong> kommt aus der Spalte <strong>Maturität</strong> (<code>erste_erfahrung</code>): erste Ziffer <strong>1–6</strong> (oder ganze Zahl 1–6) bestimmt die Stufe; leer oder ungültig = Stufe 0 (neutral, ohne farbliche Einordnung).</p>";
    echo "<div class='personel-leg-mat'>";
    $legMat = array(
        array("#fafcfa", "Stufe 0 — neutral / keine Stufe zugewiesen"),
        array("#fceaea", "Stufe 1 — Großes Entwicklungspotenzial — enger Förder- & Begleitpfad RKRS+"),
        array("#fff6dc", "Stufe 2 — Anfänger mit Potenzial"),
        array("#e8f4ea", "Stufe 3 — Pilot mit guter technischer Basiserfahrung"),
        array("#d8ecde", "Stufe 4 — Pilot mit Einsatz- und Teamerfahrung"),
        array("#d9f0ec", "Stufe 5 — Pilot und Einsatzexperte mit Führungspotenzial"),
        array("#e2ecf8", "Stufe 6 — Experte / Einsatzleiter"),
    );
    foreach ($legMat as $lm) {
        echo "<span class='personel-leg-swatch' style='background:" . rkrk_live_esc($lm[0]) . ";'></span><span>" . rkrk_live_esc($lm[1]) . "</span>";
    }
    echo "</div>";
    echo "<p><strong>Spalte «!»</strong> und <strong>farbiger Textblock in «Drohnentyp»</strong> beziehen sich nur auf das Gerät, nicht auf die Maturität:</p>";
    echo "<ul>";
    echo "<li><strong>Orange</strong> — Eintrag beginnt mit <strong>DJI Mavic 3T</strong> (Wärmebild; abweichende Anforderungen).</li>";
    echo "<li><strong>Rot</strong> — Eintrag beginnt mit <strong>anderes …</strong>, <strong>DJI Mavic 2 …</strong>, <strong>Autel …</strong> oder <strong>DJI Matrice 640 …</strong> (laut Liste als «besonders zu beachten» markiert).</li>";
    echo "</ul>";
    echo "<p class='personel-card-hint' style='margin:8px 0 0;'>Hinweis: Die Markierung ist eine schnelle visuelle Hilfe; die genaue Gerätebezeichnung steht immer in der Zelle <strong>Drohnentyp</strong>.</p>";
    echo "</details>";
    echo "<p class='personel-list-toedit'><a href='" . rkrk_live_esc($hrefEdit) . "' class='btn btn-primary'>Bearbeitungstabelle</a> <span style='font-size:12px;color:#5f6b66;'>Import, Export, neue Zeilen und Speichern</span></p>";
    echo "<script>
(function(){
  var columns = " . json_encode($visibleColumns, JSON_UNESCAPED_UNICODE) . ";
  var tbody = document.getElementById('personel-list-tbody');
  var search = document.getElementById('personel-list-search');
  var filterCol = document.getElementById('personel-list-filter-col');
  var filterColText = document.getElementById('personel-list-filter-col-text');
  var cnt = document.getElementById('personel-list-count');
  var sortState = { key: null, dir: 1 };
  if (!tbody) return;

  function compareCellStrings(va, vb) {
    var na = parseFloat(String(va).replace(',', '.'));
    var nb = parseFloat(String(vb).replace(',', '.'));
    if (va !== '' && vb !== '' && !isNaN(na) && !isNaN(nb) && /^-?[\\d.,]+$/.test(va) && /^-?[\\d.,]+$/.test(vb)) {
      if (na < nb) return -1;
      if (na > nb) return 1;
      return 0;
    }
    if (va < vb) return -1;
    if (va > vb) return 1;
    return 0;
  }

  function cellValue(tr, key) {
    var el = tr.querySelector('td[data-col=\"' + String(key).replace(/\"/g, '') + '\"]');
    if (!el) return '';
    var ds = el.getAttribute('data-sort');
    if (ds !== null && ds !== '') return String(ds).trim().toLowerCase();
    return (el.textContent || '').trim().toLowerCase();
  }

  function rowText(tr) {
    return (tr.getAttribute('data-search') || '').toLowerCase();
  }

  function syncToolbarColFilter() {
    if (!filterColText || !filterCol) return;
    var on = filterCol.value !== '';
    filterColText.disabled = !on;
    if (!on) filterColText.value = '';
  }

  function applyFilter() {
    var q = (search && search.value || '').trim().toLowerCase();
    var pickKey = filterCol && filterCol.value || '';
    var pickQ = (filterColText && filterColText.value || '').trim().toLowerCase();
    var colFilters = {};
    document.querySelectorAll('.personel-list-col-filter').forEach(function(inp) {
      var k = inp.getAttribute('data-col');
      if (!k) return;
      var v = (inp.value || '').trim().toLowerCase();
      if (v) colFilters[k] = v;
    });
    var vis = 0;
    var total = 0;
    tbody.querySelectorAll('tr[data-search]').forEach(function(tr) {
      total++;
      var show = true;
      if (q && rowText(tr).indexOf(q) === -1) show = false;
      if (show && pickKey && pickQ && cellValue(tr, pickKey).indexOf(pickQ) === -1) show = false;
      if (show) {
        for (var ck in colFilters) {
          if (cellValue(tr, ck).indexOf(colFilters[ck]) === -1) { show = false; break; }
        }
      }
      tr.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    if (cnt) cnt.textContent = total ? (vis + ' von ' + total + ' angezeigt') : '';
  }

  function sortByColumn(key, forceDir) {
    if (typeof forceDir === 'number') {
      sortState.key = key;
      sortState.dir = forceDir < 0 ? -1 : 1;
    } else if (sortState.key === key) {
      sortState.dir = -sortState.dir;
    } else {
      sortState.key = key;
      sortState.dir = 1;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function(a, b) {
      var va = cellValue(a, key);
      var vb = cellValue(b, key);
      var c = compareCellStrings(va, vb);
      if (c !== 0) return sortState.dir * c;
      return 0;
    });
    rows.forEach(function(tr) { tbody.appendChild(tr); });
    document.querySelectorAll('#personel-list-table thead th.personel-list-sort .sort-ind').forEach(function(ind) { ind.textContent = ''; });
    var th = document.querySelector('#personel-list-table thead th[data-sort-key=\"' + key + '\"] .sort-ind');
    if (th) th.textContent = sortState.dir === 1 ? '▲' : '▼';
  }

  var mobileSort = document.getElementById('personel-list-mobile-sort');
  if (mobileSort) {
    mobileSort.addEventListener('change', function() {
      var v = mobileSort.value;
      if (!v || v.indexOf(':') < 0) return;
      var p = v.split(':');
      var k = p[0];
      var dir = parseInt(p[1], 10);
      sortByColumn(k, isNaN(dir) ? 1 : dir);
      mobileSort.value = '';
    });
  }

  function applyDefaultNameSort() {
    var hasN = columns.indexOf('nachname') >= 0;
    var hasV = columns.indexOf('vorname') >= 0;
    if (!hasN && !hasV) return;
    sortState.key = hasN ? 'nachname' : 'vorname';
    sortState.dir = 1;
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));
    rows.sort(function(a, b) {
      if (hasN) {
        var c = compareCellStrings(cellValue(a, 'nachname'), cellValue(b, 'nachname'));
        if (c !== 0) return c;
      }
      if (hasV) {
        return compareCellStrings(cellValue(a, 'vorname'), cellValue(b, 'vorname'));
      }
      return 0;
    });
    rows.forEach(function(tr) { tbody.appendChild(tr); });
    document.querySelectorAll('#personel-list-table thead th.personel-list-sort .sort-ind').forEach(function(ind) { ind.textContent = ''; });
    var indN = document.querySelector('#personel-list-table thead th[data-sort-key=\"nachname\"] .sort-ind');
    var indV = document.querySelector('#personel-list-table thead th[data-sort-key=\"vorname\"] .sort-ind');
    if (hasN && indN) indN.textContent = '▲';
    else if (hasV && indV) indV.textContent = '▲';
  }

  document.querySelectorAll('#personel-list-table thead th.personel-list-sort').forEach(function(th) {
    th.addEventListener('click', function() {
      var k = th.getAttribute('data-sort-key');
      if (k) sortByColumn(k);
    });
  });

  if (search) search.addEventListener('input', applyFilter);
  if (filterCol) filterCol.addEventListener('change', function() { syncToolbarColFilter(); applyFilter(); });
  if (filterColText) filterColText.addEventListener('input', applyFilter);
  document.querySelectorAll('.personel-list-col-filter').forEach(function(inp) {
    inp.addEventListener('input', applyFilter);
  });
  syncToolbarColFilter();

  if (tbody.querySelector('tr[data-search]')) {
    applyDefaultNameSort();
  }
  applyFilter();
})();
</script>";
} else {
    echo "<p class='personel-card-hint' style='margin-top:0;'>Daten: <code>pilotenliste.json</code>. Vorname und Nachname für Formular-Vorschläge (Live-Beurteilung). ";
    echo "<strong>Alternative Rollen:</strong> z. B. Beobachter, Technik, Koordinator (freier Text). ";
    echo "<strong>Erfahrung (Zeilenfarbe):</strong> 1 rot · 2 gelb · 3 grün · 4 dunkelgrün · 5 blaugrün · 6 blau. <strong>Instruktor</strong> / <strong>RKRS+</strong>: Spalte anhaken.</p>";
    echo "<!-- Referenz-Vorlage (lokal): Telefonliste_Instruktoren.xlsx — WK2510 RKRS+ Instruktoren/Teilnehmerlisten -->";
    rkrk_live_personel_echo_column_visibility_panel($columns, $hiddenColumns, "edit");

    echo "<form id='personel-form' method='post' action='rkrk_assess_live_personel.php'>";
echo "<input type='hidden' name='save_pilots' value='1'>";
echo "<input type='hidden' name='columns_order' id='personel-columns-order' value='" . rkrk_live_esc(json_encode($columns, JSON_UNESCAPED_UNICODE)) . "'>";
echo "<input type='hidden' name='rows_json' id='personel-rows-json' value=''>";

echo "<div class='personel-toolbar'>";
echo "<label>Suche <input type='search' id='personel-search' placeholder='Alle Spalten durchsuchen…' autocomplete='off'></label>";
echo "<span class='personel-filter-col-wrap'><label for='personel-filter-col'>Spalte</label> ";
echo "<select id='personel-filter-col' title='Nur diese Spalte filtern (zusätzlich zur Suche)'>";
echo "<option value=''>— alle —</option>";
foreach ($visibleColumns as $col) {
    echo "<option value='" . rkrk_live_esc($col) . "'>" . rkrk_live_esc(rkrk_live_personel_label($col)) . "</option>";
}
echo "</select>";
echo " <input type='search' id='personel-filter-col-text' placeholder='Text in gewählter Spalte…' autocomplete='off' disabled style='min-width:160px;max-width:100%;flex:1;min-height:36px;padding:5px 8px;border:1px solid #cfd8cc;border-radius:6px;font-size:12px;'>";
echo "</span>";
echo "<button type='button' class='btn btn-primary' id='personel-add-row' title='Neue Zeile wird oberhalb der Tabelle eingefügt'>+ Neuer Pilot / Eintrag</button>";
echo "</div>";

rkrk_live_personel_echo_mobile_sort_bar($visibleColumns, "personel-table-mobile-sort");
$hideSetEdit = array_fill_keys($hiddenColumns, true);
echo "<div class='personel-table-wrap'><table class='personel-table personel-table--edit list-table' id='personel-table'><thead><tr>";
foreach ($columns as $col) {
    $hidCls = isset($hideSetEdit[$col]) ? " personel-col-is-hidden" : "";
    $thTitle = rkrk_live_personel_label_title($col);
    echo "<th class='sortable" . $hidCls . "' data-col='" . rkrk_live_esc($col) . "' data-sort-key='" . rkrk_live_esc($col) . "' title='" . rkrk_live_esc($thTitle) . "'" . rkrk_live_personel_col_cell_attrs($col, "edit") . ">" . rkrk_live_esc(rkrk_live_personel_label($col)) . "<span class='sort-ind'></span></th>";
}
echo "<th class='personel-actions' title='Zeile löschen'>×</th>";
echo "</tr><tr class='personel-filter-row'>";
foreach ($columns as $col) {
    $hidCls = isset($hideSetEdit[$col]) ? " personel-col-is-hidden" : "";
    echo "<th" . ($hidCls !== "" ? " class='" . trim($hidCls) . "'" : "") . " data-col='" . rkrk_live_esc($col) . "'" . rkrk_live_personel_col_cell_attrs($col, "edit") . "><input type='search' class='personel-col-filter' data-col='" . rkrk_live_esc($col) . "' placeholder='…' title='Filter nur " . rkrk_live_esc(rkrk_live_personel_label($col)) . "' autocomplete='off'></th>";
}
echo "<th class='personel-actions'></th>";
echo "</tr></thead><tbody id='personel-tbody'>";

foreach ($rows as $i => $r) {
    $id = trim((string)($r["id"] ?? ""));
    if ($id === "" || !preg_match('/^[a-f0-9]{16}$/', $id)) {
        $id = bin2hex(random_bytes(8));
    }
    $pfx = "row[" . (int)$i . "]";
    $expLvl = rkrk_live_personel_exp_level(rkrk_live_personel_cell_string($r["erste_erfahrung"] ?? ""));
    echo "<tr data-row-id='" . rkrk_live_esc($id) . "' data-saved='1' class='personel-row personel-exp-" . (int)$expLvl . "'>";
    $first = true;
    foreach ($columns as $col) {
        $val = rkrk_live_personel_cell_string($r[$col] ?? "");
        $dLabEdit = rkrk_live_esc(rkrk_live_personel_label($col));
        $hidCls = isset($hideSetEdit[$col]) ? " personel-col-is-hidden" : "";
        echo "<td" . ($hidCls !== "" ? " class='" . trim($hidCls) . "'" : "") . " data-label='" . $dLabEdit . "' data-col='" . rkrk_live_esc($col) . "'" . rkrk_live_personel_col_cell_attrs($col, "edit") . ">";
        if ($first) {
            echo "<input type='hidden' name='" . rkrk_live_esc($pfx) . "[id]' value='" . rkrk_live_esc($id) . "' class='personel-id-input' data-field='id'>";
            $first = false;
        }
        if (rkrk_live_personel_is_checkbox_col($col)) {
            $on = ($val === "1" || strcasecmp($val, "ja") === 0);
            echo "<input type='checkbox' name='" . rkrk_live_esc($pfx) . "[" . rkrk_live_esc($col) . "]' value='1' class='personel-field personel-checkbox' data-field='" . rkrk_live_esc($col) . "' title='" . rkrk_live_esc(rkrk_live_personel_label_title($col)) . "'" . ($on ? " checked" : "") . ">";
        } elseif (rkrk_live_personel_is_date_col($col)) {
            $dateVal = rkrk_live_personel_date_input_value($val);
            echo "<input type='date' name='" . rkrk_live_esc($pfx) . "[" . rkrk_live_esc($col) . "]' class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' value='" . rkrk_live_esc($dateVal) . "' title='" . rkrk_live_esc(rkrk_live_personel_label_title($col)) . "'>";
        } elseif (rkrk_live_personel_use_textarea($col, $val)) {
            echo "<textarea name='" . rkrk_live_esc($pfx) . "[" . rkrk_live_esc($col) . "]' class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' rows='2'>" . rkrk_live_esc($val) . "</textarea>";
        } else {
            $pidRo = ($col === "pilot_id") ? " readonly tabindex='-1'" : "";
            echo "<input type='text' name='" . rkrk_live_esc($pfx) . "[" . rkrk_live_esc($col) . "]' class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' value='" . rkrk_live_esc($val) . "'" . $pidRo . ">";
        }
        echo "</td>";
    }
    $delQs = "rkrk_assess_live_personel.php?del=" . rawurlencode($id);
    echo "<td class='personel-actions' data-label='Aktion'><a href='" . rkrk_live_esc($delQs) . "' class='personel-del' title='Pilot endgültig löschen' onclick='return confirm(\"Diesen Eintrag aus pilotenliste.json löschen?\");'>&times;</a></td>";
    echo "</tr>";
}

echo "</tbody></table></div>";
echo "<p class='personel-hint'>Spaltenkopf anklicken sortiert; Kopfzeile „Suche“ und Felder in der zweiten Zeile filtern (nur Anzeige). Ausgeblendete Spalten bleiben beim Speichern erhalten. Reihenfolge in der JSON entspricht der Zeilenreihenfolge nach Speichern. <strong>PilotID</strong> ist die fortlaufende Nummer 1…<em>n</em> (automatisch, nicht manuell editierbar) — getrennt von <strong>RKRS Piloten ID</strong>. <strong>Löschen</strong> wirkt sofort (ohne „Speichern“).</p>";
echo "<p style='margin:6px 0 0;'><button type='submit' class='btn btn-primary'>Speichern</button></p>";
echo "</form>";

echo "<p class='personel-io-ref' style='margin-top:18px;'>Vorlage-Struktur orientiert an <code>Telefonliste_Instruktoren.xlsx</code> (Spalten z. B. Vorname, Nachname, Telefon, E-Mail). <strong>Export JSON</strong> = Backup der Datei <code>pilotenliste.json</code>. Import weiterhin .xlsx/.csv.</p>";
echo "<div class='chk-toolbar no-print personel-io-toolbar' role='region' aria-label='Personal Export und Import'>";
echo "<a class='chk-tbtn' href='?export=json' title='pilotenliste.json als Datei herunterladen (Backup)' aria-label='JSON exportieren'>" . rkrk_checklist_toolbar_svg_export() . "<span>Export JSON</span></a>";
echo "<form class='chk-import' method='post' action='rkrk_assess_live_personel.php' enctype='multipart/form-data' title='Import'>";
echo "<input type='hidden' name='import_merge' value='1'>";
echo "<input type='file' name='import_file' accept='.xlsx,.csv' required title='Telefonliste_Instruktoren.xlsx oder CSV'>";
echo "<label style='font-size:11px;color:#5f6b66;display:inline-flex;align-items:center;gap:4px;'><input type='checkbox' name='import_all_instruktor' value='1'> alle import. Zeilen als Instruktor</label>";
echo "<button type='submit' name='import_action' value='merge' class='chk-tbtn' title='Mit bestehender Liste zusammenführen' aria-label='Import zusammenführen'>" . rkrk_checklist_toolbar_svg_import() . "<span>Import zusammenführen</span></button>";
echo "<button type='submit' name='import_action' value='only_new_instr' class='chk-tbtn' title='Nur neue Personen anlegen, immer Instruktor' aria-label='Fehlende Instruktoren'>" . rkrk_checklist_toolbar_svg_user_plus() . "<span>Fehlende Instruktoren</span></button>";
echo "</form>";
echo "<span class='chk-toolbar-hint'>Abgleich per E-Mail, sonst Vorname+Nachname. Spalte „Name“ in Excel wird beim Import nach Vorname übernommen, falls nur ein Feld vorliegt.</span>";
echo "</div>";

}

echo "</div>";

echo "</div>";

if ($personelUiView === "edit") {
echo "<template id='personel-row-template'>";
echo "<tr data-row-id='' data-saved='0' class='personel-row personel-exp-0'>";
if (!isset($hideSetEdit)) {
    $hideSetEdit = array_fill_keys($hiddenColumns, true);
}
foreach ($columns as $ci => $col) {
    $dLabTpl = rkrk_live_esc(rkrk_live_personel_label($col));
    $hidCls = isset($hideSetEdit[$col]) ? " personel-col-is-hidden" : "";
    echo "<td" . ($hidCls !== "" ? " class='" . trim($hidCls) . "'" : "") . " data-label='" . $dLabTpl . "' data-col='" . rkrk_live_esc($col) . "'" . rkrk_live_personel_col_cell_attrs($col, "edit") . ">";
    if ($ci === 0) {
        echo "<input type='hidden' value='' class='personel-id-input' data-field='id'>";
    }
    if (rkrk_live_personel_is_checkbox_col($col)) {
        echo "<input type='checkbox' value='1' class='personel-field personel-checkbox' data-field='" . rkrk_live_esc($col) . "' title='" . rkrk_live_esc(rkrk_live_personel_label_title($col)) . "'>";
    } elseif (rkrk_live_personel_is_date_col($col)) {
        echo "<input type='date' class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' value='' title='" . rkrk_live_esc(rkrk_live_personel_label_title($col)) . "'>";
    } elseif (rkrk_live_personel_use_textarea($col, "")) {
        echo "<textarea class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' rows='2'></textarea>";
    } else {
        $pidRoTpl = ($col === "pilot_id") ? " readonly tabindex='-1'" : "";
        echo "<input type='text' class='text-input personel-field' data-field='" . rkrk_live_esc($col) . "' value=''" . $pidRoTpl . ">";
    }
    echo "</td>";
}
echo "<td class='personel-actions' data-label='Aktion'><button type='button' class='personel-remove-new btn-link'>Zeile entfernen</button></td>";
echo "</tr>";
echo "</template>";

echo "<script>
(function(){
  var columns = " . json_encode($columns, JSON_UNESCAPED_UNICODE) . ";
  var tbody = document.getElementById('personel-tbody');
  var form = document.getElementById('personel-form');
  var tpl = document.getElementById('personel-row-template');
  var search = document.getElementById('personel-search');
  var filterCol = document.getElementById('personel-filter-col');
  var filterColText = document.getElementById('personel-filter-col-text');
  var sortState = { key: null, dir: 1 };

  function compareCellStrings(va, vb) {
    var na = parseFloat(va.replace(',', '.'));
    var nb = parseFloat(vb.replace(',', '.'));
    if (va !== '' && vb !== '' && !isNaN(na) && !isNaN(nb) && /^-?[\\d.,]+$/.test(va) && /^-?[\\d.,]+$/.test(vb)) {
      if (na < nb) return -1;
      if (na > nb) return 1;
      return 0;
    }
    if (va < vb) return -1;
    if (va > vb) return 1;
    return 0;
  }

  function applyDefaultNameSort() {
    var hasN = columns.indexOf('nachname') >= 0;
    var hasV = columns.indexOf('vorname') >= 0;
    if (!hasN && !hasV) return;
    sortState.key = hasN ? 'nachname' : 'vorname';
    sortState.dir = 1;
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    rows.sort(function(a, b) {
      if (hasN) {
        var c = compareCellStrings(cellValue(a, 'nachname'), cellValue(b, 'nachname'));
        if (c !== 0) return c;
      }
      if (hasV) {
        return compareCellStrings(cellValue(a, 'vorname'), cellValue(b, 'vorname'));
      }
      return 0;
    });
    rows.forEach(function(tr) { tbody.appendChild(tr); });
    document.querySelectorAll('#personel-table thead th.sortable .sort-ind').forEach(function(ind) { ind.textContent = ''; });
    var indN = document.querySelector('#personel-table thead th[data-sort-key=\"nachname\"] .sort-ind');
    var indV = document.querySelector('#personel-table thead th[data-sort-key=\"vorname\"] .sort-ind');
    if (hasN && indN) indN.textContent = '▲';
    else if (hasV && indV) indV.textContent = '▲';
    reindexNames();
  }

  function newRowId() {
    var a = new Uint8Array(8);
    if (window.crypto && crypto.getRandomValues) crypto.getRandomValues(a);
    else for (var i = 0; i < 8; i++) a[i] = Math.floor(Math.random() * 256);
    var h = '';
    for (var j = 0; j < 8; j++) h += ('0' + a[j].toString(16)).slice(-2);
    return h;
  }

  function renumberPilotIdCells() {
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr.personel-row');
    var i = 1;
    rows.forEach(function(tr) {
      var el = tr.querySelector('.personel-field[data-field=\"pilot_id\"]');
      if (el) el.value = String(i++);
    });
  }

  function reindexNames() {
    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function(tr, i) {
      tr.querySelectorAll('.personel-field, .personel-id-input').forEach(function(el) {
        var k = el.getAttribute('data-field');
        if (!k) return;
        el.name = 'row[' + i + '][' + k + ']';
      });
    });
    renumberPilotIdCells();
  }

  function rowText(tr) {
    var s = '';
    tr.querySelectorAll('.personel-field').forEach(function(el) {
      if (el.type === 'checkbox') s += ' ' + (el.checked ? '1' : '');
      else s += ' ' + (el.value || '');
    });
    return s.toLowerCase();
  }

  function applyFilter() {
    var q = (search && search.value || '').trim().toLowerCase();
    var pickKey = filterCol && filterCol.value || '';
    var pickQ = (filterColText && filterColText.value || '').trim().toLowerCase();
    var colFilters = {};
    document.querySelectorAll('.personel-col-filter').forEach(function(inp) {
      var k = inp.getAttribute('data-col');
      if (!k) return;
      var v = (inp.value || '').trim().toLowerCase();
      if (v) colFilters[k] = v;
    });
    tbody.querySelectorAll('tr').forEach(function(tr) {
      var show = true;
      if (q && rowText(tr).indexOf(q) === -1) show = false;
      if (show && pickKey && pickQ && cellValue(tr, pickKey).indexOf(pickQ) === -1) show = false;
      if (show) {
        for (var ck in colFilters) {
          if (cellValue(tr, ck).indexOf(colFilters[ck]) === -1) { show = false; break; }
        }
      }
      tr.style.display = show ? '' : 'none';
    });
  }

  function syncToolbarColFilter() {
    if (!filterColText || !filterCol) return;
    var on = filterCol.value !== '';
    filterColText.disabled = !on;
    if (!on) filterColText.value = '';
  }

  function cellValue(tr, key) {
    var el = tr.querySelector('.personel-field[data-field=\"' + key.replace(/\"/g,'') + '\"]');
    if (!el) return '';
    if (el.type === 'checkbox') return el.checked ? '1' : '';
    return (el.value || '').trim().toLowerCase();
  }

  function syncExpClass(tr) {
    var el = tr.querySelector('.personel-field[data-field=\"erste_erfahrung\"]');
    var raw = el ? String(el.value || '').trim() : '';
    var n = 0;
    var m = raw.match(/^\\s*([1-6])\\b/);
    if (m) n = parseInt(m[1], 10);
    else {
      var p = parseInt(raw, 10);
      if (p >= 1 && p <= 6) n = p;
    }
    [0,1,2,3,4,5,6].forEach(function(k) { tr.classList.remove('personel-exp-' + k); });
    tr.classList.add('personel-exp-' + (n >= 1 && n <= 6 ? n : 0));
  }

  function sortByColumn(key, forceDir) {
    if (typeof forceDir === 'number') {
      sortState.key = key;
      sortState.dir = forceDir < 0 ? -1 : 1;
    } else if (sortState.key === key) {
      sortState.dir = -sortState.dir;
    } else {
      sortState.key = key;
      sortState.dir = 1;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    rows.sort(function(a, b) {
      var va = cellValue(a, key);
      var vb = cellValue(b, key);
      var c = compareCellStrings(va, vb);
      if (c !== 0) return sortState.dir * c;
      return 0;
    });
    rows.forEach(function(tr) { tbody.appendChild(tr); });
    document.querySelectorAll('#personel-table thead th.sortable .sort-ind').forEach(function(ind) { ind.textContent = ''; });
    var th = document.querySelector('#personel-table thead th[data-sort-key=\"' + key + '\"] .sort-ind');
    if (th) th.textContent = sortState.dir === 1 ? '▲' : '▼';
    reindexNames();
  }

  var mobileSortEdit = document.getElementById('personel-table-mobile-sort');
  if (mobileSortEdit) {
    mobileSortEdit.addEventListener('change', function() {
      var v = mobileSortEdit.value;
      if (!v || v.indexOf(':') < 0) return;
      var p = v.split(':');
      var k = p[0];
      var dir = parseInt(p[1], 10);
      sortByColumn(k, isNaN(dir) ? 1 : dir);
      mobileSortEdit.value = '';
    });
  }

  document.querySelectorAll('#personel-table thead th.sortable').forEach(function(th) {
    th.addEventListener('click', function() {
      var k = th.getAttribute('data-sort-key');
      if (k) sortByColumn(k);
    });
  });

  tbody.addEventListener('input', function(e) {
    var t = e.target;
    if (t && t.classList && t.classList.contains('personel-field') && t.getAttribute('data-field') === 'erste_erfahrung') {
      var tr = t.closest('tr');
      if (tr) syncExpClass(tr);
    }
  });
  tbody.addEventListener('change', function(e) {
    var t = e.target;
    if (!t || !t.classList || !t.classList.contains('personel-field')) return;
    var df = t.getAttribute('data-field');
    if (df === 'erste_erfahrung') {
      var tr0 = t.closest('tr');
      if (tr0) syncExpClass(tr0);
    }
    if (df === 'erste_erfahrung' || t.type === 'checkbox') applyFilter();
  });

  if (search) search.addEventListener('input', applyFilter);
  if (filterCol) filterCol.addEventListener('change', function() { syncToolbarColFilter(); applyFilter(); });
  if (filterColText) filterColText.addEventListener('input', applyFilter);
  document.querySelectorAll('.personel-col-filter').forEach(function(inp) {
    inp.addEventListener('input', applyFilter);
  });
  syncToolbarColFilter();

  var addBtn = document.getElementById('personel-add-row');
  if (addBtn) addBtn.addEventListener('click', function() {
    var node = tpl.content.cloneNode(true);
    var tr = node.querySelector('tr');
    var nid = newRowId();
    tr.setAttribute('data-row-id', nid);
    var hid = tr.querySelector('.personel-id-input');
    if (hid) hid.value = nid;
    tbody.insertBefore(tr, tbody.firstChild);
    reindexNames();
    syncExpClass(tr);
    applyFilter();
  });

  tbody.addEventListener('click', function(e) {
    if (!e.target.classList.contains('personel-remove-new')) return;
    var tr = e.target.closest('tr');
    if (!tr) return;
    tr.remove();
    reindexNames();
    applyFilter();
  });

  form.addEventListener('submit', function() {
    reindexNames();
    var orderInp = document.getElementById('personel-columns-order');
    if (orderInp) orderInp.value = JSON.stringify(columns);
    var rowsInp = document.getElementById('personel-rows-json');
    if (rowsInp && tbody) {
      var out = [];
      tbody.querySelectorAll('tr.personel-row').forEach(function(tr) {
        var rec = {};
        var idEl = tr.querySelector('.personel-id-input[data-field=\"id\"]');
        rec.id = idEl && idEl.value ? String(idEl.value).trim() : newRowId();
        columns.forEach(function(col) {
          var el = tr.querySelector('.personel-field[data-field=\"' + col.replace(/\"/g,'') + '\"]');
          if (!el) return;
          if (el.type === 'checkbox') rec[col] = el.checked ? '1' : '';
          else rec[col] = String(el.value || '').trim();
        });
        out.push(rec);
      });
      rowsInp.value = JSON.stringify(out);
    }
  });

  reindexNames();
  applyDefaultNameSort();
  tbody.querySelectorAll('tr.personel-row').forEach(function(tr) { syncExpClass(tr); });
  applyFilter();
})();
</script>";
}
