<?php
/**
 * Gemeinsame Toolbar: Export/Import (JSON) + Druck — für rkrk_assess.php und rkrk_checklist.php.
 */

declare(strict_types=1);

if (!function_exists('rkrk_assess_append_setting_href')) {
    /**
     * Fallback wenn rkrk_assess_common.php nicht eingebunden (z. B. rkrk_checklist.php).
     */
    function rkrk_assess_append_setting_href(string $href): string
    {
        return $href;
    }
}

function rkrk_checklist_toolbar_echo_styles_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if (function_exists('rkrk_assess_theme_link_tags')) {
        /* assess/* lädt rkrk_assess_theme.css */
        return;
    }
    echo "<style>
.chk-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:0 0 14px 0;padding:10px 12px;background:#f4f8f3;border:1px solid #d5e0d2;border-radius:8px;}
.chk-toolbar .chk-tbtn,a.chk-tbtn,button.chk-tbtn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 12px;border:1px solid #c9d4c5;border-radius:8px;background:#fff;color:#2d4024;font-size:13px;text-decoration:none;cursor:pointer;font-family:inherit;line-height:1.2;transition:background .15s,border-color .15s,box-shadow .15s;}
.chk-toolbar .chk-tbtn:hover,a.chk-tbtn:hover,button.chk-tbtn:hover{background:#eef5ec;border-color:#9ab08f;box-shadow:0 1px 3px rgba(45,64,36,.08);}
.chk-toolbar .chk-tbtn:focus,a.chk-tbtn:focus,button.chk-tbtn:focus{outline:2px solid #48673A;outline-offset:2px;}
.chk-toolbar .chk-tbtn svg.chk-ico,a.chk-tbtn svg.chk-ico,button.chk-tbtn svg.chk-ico{width:18px;height:18px;flex-shrink:0;}
.chk-toolbar form.chk-import{display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0;padding:0;}
.chk-toolbar input[type=file]{font-size:12px;max-width:220px;}
.chk-toolbar .chk-toolbar-hint{font-size:11px;color:#5f6b66;flex:1;min-width:12rem;}
.chk-act{display:inline-flex;align-items:center;justify-content:center;width:36px;height:32px;padding:0;border:1px solid #cfd8cc;border-radius:8px;background:#fff;color:#2d4024;text-decoration:none;vertical-align:middle;transition:background .15s,border-color .15s;}
.chk-act:hover{background:#eef5ec;border-color:#9ab08f;}
.chk-act svg.chk-ico{width:17px;height:17px;}
</style>";
}

function rkrk_checklist_toolbar_svg_export(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
}

function rkrk_checklist_toolbar_svg_import(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>';
}

function rkrk_checklist_toolbar_svg_print(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
}

function rkrk_checklist_toolbar_svg_edit(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
}

function rkrk_checklist_toolbar_svg_read(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function rkrk_checklist_toolbar_svg_trash(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';
}

function rkrk_checklist_toolbar_svg_copy(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
}

/** Neues Formular / neuer Eintrag (Dokument mit Plus) — gleicher Strichstil wie übrige chk-ico */
function rkrk_checklist_toolbar_svg_file_plus(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>';
}

/** Person hinzufügen (z. B. fehlende Instruktoren) */
function rkrk_checklist_toolbar_svg_user_plus(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>';
}

/** Wiederherstellen / Recovery (z. B. Tagesübersicht) */
function rkrk_checklist_toolbar_svg_restore(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>';
}

/** Filter */
function rkrk_checklist_toolbar_svg_filter(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>';
}

/** Kalender / Heute */
function rkrk_checklist_toolbar_svg_calendar(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
}

/** Alle Tage */
function rkrk_checklist_toolbar_svg_calendar_range(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>';
}

/** Formular / Link in neuem Fenster öffnen */
function rkrk_checklist_toolbar_svg_external(): string
{
    return '<svg class="chk-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
}

/** Papierformular-URL für ein Template (Beurteilung / Assessment). */
function rkrk_checklist_toolbar_paperform_link(string $templateFile, ?string $edit = null, string $from = "form", string $storyId = ""): string
{
    $templateFile = basename($templateFile);
    $q = array(
        "mode" => "paperform",
        "file" => $templateFile,
        "from" => $from,
    );
    if ($edit !== null && $edit !== "") {
        $q["edit"] = basename($edit);
    }
    if ($storyId === "" && function_exists("rkrk_assess_storyboard_request_id")) {
        $storyId = rkrk_assess_storyboard_request_id();
    }
    $storyId = function_exists("rkrk_assess_storyboard_normalize_id")
        ? rkrk_assess_storyboard_normalize_id($storyId)
        : preg_replace('/[^A-Za-z0-9_-]+/', "_", trim($storyId));
    if ($storyId !== "") {
        $q["story"] = $storyId;
    }
    return rkrk_assess_append_setting_href("?" . http_build_query($q));
}

/** Zurück-Link auf der Papierformular-Seite je nach Aufrufkontext. */
function rkrk_assess_paperform_resolve_back_url(string $templateFile): string
{
    $templateFile = basename($templateFile);
    $from = isset($_GET["from"]) ? trim((string) $_GET["from"]) : "";
    $storyId = function_exists("rkrk_assess_storyboard_request_id") ? rkrk_assess_storyboard_request_id() : "";
    if ($from === "form") {
        $q = array("mode" => "form", "file" => $templateFile);
        $edit = isset($_GET["edit"]) ? basename((string) $_GET["edit"]) : "";
        if ($edit !== "") {
            $q["edit"] = $edit;
        }
        if ($storyId !== "") {
            $q["story"] = $storyId;
        }
        return rkrk_assess_append_setting_href("?" . http_build_query($q));
    }
    if ($from === "storyboards") {
        return rkrk_assess_append_setting_href("?mode=storyboards");
    }
    if ($from === "listall") {
        return rkrk_assess_append_setting_href("?mode=listall");
    }
    if ($from === "list") {
        return rkrk_assess_append_setting_href("?mode=list");
    }
    if ($from === "templates") {
        return rkrk_assess_append_setting_href("?mode=templates");
    }
    return rkrk_assess_append_setting_href("?mode=edittemplate&file=" . rawurlencode($templateFile));
}

/**
 * Toolbar oberhalb des Beurteilungs-Formulars (Papierformular immer; Export/Druck/Import wenn gespeichert).
 */
function rkrk_checklist_toolbar_echo_form_bar(string $file, ?string $edit, bool $readonly): void
{
    $file = basename($file);
    if ($file === "") {
        return;
    }
    $fileEsc = htmlspecialchars($file, ENT_QUOTES, "UTF-8");
    $hasEdit = ($edit !== null && $edit !== "");
    $editEsc = $hasEdit ? htmlspecialchars($edit, ENT_QUOTES, "UTF-8") : "";
    rkrk_checklist_toolbar_echo_styles_once();
    echo "<div class='chk-toolbar no-print' role='region' aria-label='Papierformular, Export, Import, Druck'>";
    echo "<a class='chk-act' href='" . htmlspecialchars(rkrk_checklist_toolbar_paperform_link($file, $hasEdit ? $edit : null, "form"), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Leeres Papierformular zum Ausfüllen per Hand (Template-Struktur)' aria-label='Papierformular'>📄</a>";
    if (!$hasEdit) {
        echo "<span class='chk-toolbar-hint'>Leerer Bogen nach Template — zum Ausfüllen vor Ort, danach hier digital erfassen.</span>";
        echo "</div>";
        return;
    }
    echo "<a class='chk-tbtn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=exportjson&edit=" . rawurlencode($edit)), ENT_QUOTES, "UTF-8") . "' title='Checkliste als JSON-Datei herunterladen (Backup, Bearbeiten extern)' download>"
        . rkrk_checklist_toolbar_svg_export() . "<span>Export</span></a>";
    echo "<a class='chk-tbtn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=print&id=" . rawurlencode($edit)), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='Druckansicht in neuem Fenster (Strg+P / Als PDF speichern)'>"
        . rkrk_checklist_toolbar_svg_print() . "<span>Drucken</span></a>";
    $reportKind = (stripos((string) ($_SERVER["SCRIPT_NAME"] ?? ""), "assess_live") !== false) ? "live" : "assess";
    $reportHref = rkrk_checklist_toolbar_list_report_link($edit, $reportKind);
    if ($reportHref !== "") {
        echo "<a class='chk-tbtn' href='" . htmlspecialchars($reportHref, ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener' title='PDF-Abschlussbericht'>"
            . rkrk_checklist_toolbar_svg_pdf() . "<span>PDF-Report</span></a>";
    }
    if (!$readonly) {
        echo "<form class='chk-import' method='post' action='" . htmlspecialchars(rkrk_assess_append_setting_href("?mode=importjson"), ENT_QUOTES, "UTF-8") . "' enctype='multipart/form-data' title='Felder aus JSON mit dieser Checkliste zusammenführen'>";
        echo "<input type='hidden' name='file' value='" . $fileEsc . "'>";
        echo "<input type='hidden' name='edit' value='" . $editEsc . "'>";
        echo "<input type='file' name='import_json' accept='.json,application/json' required aria-label='JSON-Datei wählen'>";
        echo "<button type='submit' class='chk-tbtn' title='JSON importieren: Felder werden nach Abschnitt/Feldname überschrieben (Template bleibt)'>"
            . rkrk_checklist_toolbar_svg_import() . "<span>Import</span></button>";
        echo "</form>";
    }
    echo "<span class='chk-toolbar-hint'>Papierformular = leer. Export/Drucken = gespeicherte Beurteilung.</span>";
    echo "</div>";
}

/** Kompakte Aktions-Links für Tabellenzeilen (Liste) */
function rkrk_checklist_toolbar_svg_pdf(): string
{
    return "<svg class='chk-ico' viewBox='0 0 24 24' width='18' height='18' aria-hidden='true' focusable='false'><path fill='currentColor' d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1 7V3.5L18.5 9H15zM8 13h2.5a1.5 1.5 0 0 1 0 3H9v2H8v-5zm1 1v1h1.5a.5.5 0 0 0 0-1H9zm4.5-.5c.8 0 1.5.7 1.5 1.5v2c0 .8-.7 1.5-1.5 1.5H12v-5h1.5zm0 1H13v3h.5c.3 0 .5-.2.5-.5v-2c0-.3-.2-.5-.5-.5zM18 13h-2v5h1v-1.5h1a1.5 1.5 0 0 0 0-3zm0 1a.5.5 0 0 1 0 1h-1v-1h1z'/></svg>";
}

function rkrk_checklist_toolbar_list_export_link(string $id): string
{
    return rkrk_assess_append_setting_href("?mode=exportjson&edit=" . rawurlencode($id));
}

function rkrk_checklist_toolbar_list_print_link(string $id): string
{
    return rkrk_assess_append_setting_href("?mode=print&id=" . rawurlencode($id));
}

/** Abschluss-Report-URL (PDF) für Listen/Toolbar; $kind = live|assess. */
function rkrk_checklist_toolbar_list_report_link(string $id, string $kind = "assess"): string
{
    if (!function_exists("rkrk_assess_report_href_for_result_stem")) {
        return "";
    }
    return rkrk_assess_report_href_for_result_stem($id, $kind);
}

/** HTML-Link «PDF» nur wenn Report-Datei vorhanden. */
function rkrk_checklist_toolbar_report_act_html(string $id, string $kind = "assess", string $class = "chk-act"): string
{
    $href = rkrk_checklist_toolbar_list_report_link($id, $kind);
    if ($href === "") {
        return "";
    }
    $cls = htmlspecialchars($class, ENT_QUOTES, "UTF-8");
    return "<a class='" . $cls . "' href='" . htmlspecialchars($href, ENT_QUOTES, "UTF-8")
        . "' target='_blank' rel='noopener' title='PDF-Abschlussbericht öffnen' aria-label='PDF-Report'>"
        . (function_exists("rkrk_checklist_toolbar_svg_pdf") ? rkrk_checklist_toolbar_svg_pdf() : "PDF")
        . "</a>";
}

function rkrk_checklist_toolbar_list_paperform_link(string $templateFile): string
{
    return rkrk_checklist_toolbar_paperform_link($templateFile, null, "list");
}

function rkrk_checklist_toolbar_merge_fields(array $baseFields, array $incomingFields): array
{
    foreach ($incomingFields as $sec => $flds) {
        if (!is_string($sec) || !is_array($flds)) {
            continue;
        }
        if (!isset($baseFields[$sec])) {
            $baseFields[$sec] = [];
        }
        foreach ($flds as $lab => $val) {
            if (!is_string($lab)) {
                continue;
            }
            $baseFields[$sec][$lab] = $val;
        }
    }
    return $baseFields;
}

function rkrk_checklist_toolbar_handle_export_json(string $jsonFolder): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $id = basename((string)($_GET["edit"] ?? $_GET["id"] ?? ""));
    if ($id === "") {
        http_response_code(400);
        header("Content-Type: text/plain; charset=utf-8");
        echo "Fehlender Parameter edit/id.";
        exit;
    }
    $path = rtrim($jsonFolder, "/\\") . DIRECTORY_SEPARATOR . $id . ".json";
    if (!is_file($path)) {
        http_response_code(404);
        header("Content-Type: text/plain; charset=utf-8");
        echo "Datei nicht gefunden.";
        exit;
    }
    $raw = (string)@file_get_contents($path);
    if ($raw === "") {
        http_response_code(500);
        exit;
    }
    $fn = "Checkliste_" . preg_replace('/[^a-zA-Z0-9_-]+/', "_", $id) . ".json";
    header("Content-Type: application/json; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"" . $fn . "\"");
    header("Content-Length: " . (string)strlen($raw));
    header("Cache-Control: private, max-age=0");
    echo $raw;
    exit;
}

/**
 * @param callable(string): void $redirect muss Location setzen und exit
 */
function rkrk_checklist_toolbar_handle_import_json(string $jsonFolder, callable $redirect): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $edit = basename((string)($_POST["edit"] ?? ""));
    $file = basename((string)($_POST["file"] ?? ""));
    if (
        $edit === ""
        || $file === ""
        || !isset($_FILES["import_json"])
        || !is_uploaded_file((string)($_FILES["import_json"]["tmp_name"] ?? ""))
    ) {
        $redirect(rkrk_assess_append_setting_href("?mode=list&err=import"));
        exit;
    }
    $path = rtrim($jsonFolder, "/\\") . DIRECTORY_SEPARATOR . $edit . ".json";
    if (!is_file($path)) {
        $redirect(rkrk_assess_append_setting_href("?mode=list&err=import"));
        exit;
    }
    $raw = (string)@file_get_contents((string)$_FILES["import_json"]["tmp_name"]);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data["fields"]) || !is_array($data["fields"])) {
        $redirect(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($file) . "&edit=" . rawurlencode($edit) . "&err=importjson"));
        exit;
    }
    $existing = json_decode((string)@file_get_contents($path), true);
    if (!is_array($existing)) {
        $existing = [];
    }
    if (!isset($existing["fields"]) || !is_array($existing["fields"])) {
        $existing["fields"] = [];
    }
    $existing["fields"] = rkrk_checklist_toolbar_merge_fields($existing["fields"], $data["fields"]);
    $existing["modified_at"] = date("Y-m-d H:i:s");
    if (empty($existing["created_at"])) {
        $existing["created_at"] = date("Y-m-d H:i:s");
    }
    if (empty($existing["template"])) {
        $existing["template"] = $file;
    }
    @file_put_contents($path, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $redirect(rkrk_assess_append_setting_href("?mode=form&file=" . rawurlencode($file) . "&edit=" . rawurlencode($edit) . "&imported=1"));
    exit;
}
