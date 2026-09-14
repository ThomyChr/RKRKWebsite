<?php
/**
 * Gemeinsame Template-Hilfen: JSON-Export/Import (primär), CSV (Excel) Legacy, Druckansicht.
 * Einbinden nach safe()/loadTemplate(); nutzt dieselbe JSON-Struktur wie Live-Assess.
 */
if (!function_exists('rkrk_template_csv_escape_cell')) {

    function rkrk_template_csv_escape_cell($v)
    {
        $s = (string)$v;
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = str_replace("\n", "|nl|", $s);
        return $s;
    }

    function rkrk_template_csv_unescape_cell($v)
    {
        return str_replace("|nl|", "\n", (string)$v);
    }

    /** @return list<array<string,string>> */
    function rkrk_template_flatten_sections_for_csv(array $sections): array
    {
        $rows = [];
        foreach ($sections as $secName => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            foreach ($fields as $label => $meta) {
                $type = "text";
                $placeholder = $info = $process = $checks = $subchecks = $options = $ref = $calc = $required = $inname = $inresult = $stufeSlot = "";
                if (is_array($meta)) {
                    $type = isset($meta["type"]) ? (string)$meta["type"] : "text";
                    if (isset($meta["placeholder"])) {
                        $placeholder = (string)$meta["placeholder"];
                    }
                    if (isset($meta["info"])) {
                        $info = (string)$meta["info"];
                    }
                    if (isset($meta["process"])) {
                        $process = (string)$meta["process"];
                    }
                    if (isset($meta["checks"])) {
                        $checks = (string)$meta["checks"];
                    }
                    if (isset($meta["subchecks"])) {
                        $subchecks = (string)$meta["subchecks"];
                    }
                    if (isset($meta["options"])) {
                        $options = (string)$meta["options"];
                    }
                    if (isset($meta["ref"])) {
                        $ref = (string)$meta["ref"];
                    }
                    if (isset($meta["calc"])) {
                        $calc = (string)$meta["calc"];
                    }
                    if (isset($meta["required"])) {
                        $required = (string)$meta["required"];
                    }
                    if (isset($meta["inname"])) {
                        $inname = (string)$meta["inname"];
                    }
                    if (isset($meta["inresult"])) {
                        $inresult = (string)$meta["inresult"];
                    }
                    if (isset($meta["stufe_slot"])) {
                        $stufeSlot = (string)$meta["stufe_slot"];
                    }
                } else {
                    $type = (string)$meta;
                }
                $exportType = $type;
                $exportOpts = $options;
                if (strpos($type, "radio:") === 0) {
                    $exportType = "radio";
                    $exportOpts = substr($type, 6);
                } elseif (strpos($type, "multi:") === 0) {
                    $exportType = "multi";
                    $exportOpts = substr($type, 6);
                }
                $rows[] = [
                    "sec" => (string)$secName,
                    "label" => (string)$label,
                    "type" => $exportType,
                    "placeholder" => $placeholder,
                    "info" => $info,
                    "process" => $process,
                    "checks" => $checks,
                    "subchecks" => $subchecks,
                    "options" => $exportOpts,
                    "ref" => $ref,
                    "calc" => $calc,
                    "required" => $required,
                    "inname" => $inname,
                    "inresult" => $inresult,
                    "stufe_slot" => $stufeSlot,
                ];
            }
        }
        return $rows;
    }

    /** @param list<array<string,string>> $rows */
    function rkrk_template_sections_from_csv_rows(array $rows): array
    {
        $sections = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sec = trim((string)($row["sec"] ?? ""));
            $label = trim((string)($row["label"] ?? ""));
            if ($sec === "" || $label === "") {
                continue;
            }
            if (!isset($sections[$sec])) {
                $sections[$sec] = [];
            }
            $typeCell = rkrk_template_csv_unescape_cell(trim((string)($row["type"] ?? "text")));
            $opts = rkrk_template_csv_unescape_cell(trim((string)($row["options"] ?? "")));
            $placeholder = rkrk_template_csv_unescape_cell(trim((string)($row["placeholder"] ?? "")));
            $info = rkrk_template_csv_unescape_cell(trim((string)($row["info"] ?? "")));
            $process = rkrk_template_csv_unescape_cell(trim((string)($row["process"] ?? "")));
            $checks = rkrk_template_csv_unescape_cell(trim((string)($row["checks"] ?? "")));
            $subchecks = rkrk_template_csv_unescape_cell(trim((string)($row["subchecks"] ?? "")));
            $calc = rkrk_template_csv_unescape_cell(trim((string)($row["calc"] ?? "")));
            $refRaw = trim((string)($row["ref"] ?? ""));
            $required = trim((string)($row["required"] ?? ""));
            $inname = trim((string)($row["inname"] ?? "0")) === "1" ? "1" : "0";
            $inresult = trim((string)($row["inresult"] ?? "0")) === "1" ? "1" : "0";
            $stufeSlot = trim((string)($row["stufe_slot"] ?? ""));

            $typeBase = "";
            if (stripos($typeCell, "radio:") === 0 || stripos($typeCell, "multi:") === 0) {
                $type = $typeCell;
            } else {
                $typeBase = $typeCell;
                $type = $typeBase;
                if ($typeBase === "radio" && $opts !== "") {
                    $type = "radio:" . $opts;
                } elseif ($typeBase === "multi" && $opts !== "") {
                    $type = "multi:" . $opts;
                } elseif ($typeBase === "summe" && $calc === "" && $opts !== "") {
                    $calc = $opts;
                    $type = "summe";
                } elseif ($typeBase === "countfails" && $calc === "" && $opts !== "") {
                    $calc = $opts;
                    $type = "countfails";
                } elseif ($typeBase === "countcritical" && $calc === "" && $opts !== "") {
                    $calc = $opts;
                    $type = "countcritical";
                }
            }

            $entry = ["type" => $type];
            if ($placeholder !== "") {
                $entry["placeholder"] = $placeholder;
            }
            if ($info !== "") {
                $entry["info"] = $info;
            }
            if ($process !== "") {
                $entry["process"] = $process;
            }
            if ($checks !== "") {
                $entry["checks"] = $checks;
            }
            if ($subchecks !== "") {
                $entry["subchecks"] = $subchecks;
            }
            $isRadioMultiCell = (stripos((string)$type, "radio:") === 0 || stripos((string)$type, "multi:") === 0);
            if ($opts !== "" && $typeBase !== "radio" && $typeBase !== "multi" && !$isRadioMultiCell) {
                $entry["options"] = $opts;
            }
            if ($calc !== "") {
                $entry["calc"] = $calc;
            }
            if ($required !== "") {
                $entry["required"] = $required;
            }
            if ($refRaw !== "") {
                if (function_exists("rkrk_normalize_ref_id")) {
                    $entry["ref"] = rkrk_normalize_ref_id($refRaw);
                } else {
                    $entry["ref"] = $refRaw;
                }
            }
            if ($type === "directcheck" && $stufeSlot !== "" && preg_match('/^[1-6]$/', $stufeSlot)) {
                $entry["stufe_slot"] = $stufeSlot;
            }
            $entry["inname"] = $inname;
            $entry["inresult"] = $inresult;

            $sections[$sec][$label] = $entry;
        }
        return $sections;
    }
}

/**
 * CSV-Download (UTF-8 BOM, Semikolon). Content-Disposition: attachment → Browser speichert z. B. im Download-Ordner.
 * Bei Erfolg: exit. Bei Fehler: return (Aufrufer leitet um).
 */
function rkrk_template_export_csv_download(string $templateFolder, string $file): void
{
    $file = basename($file);
    if ($file === "" || !preg_match('/\.json$/i', $file)) {
        return;
    }
    $path = $templateFolder . "/" . $file;
    if (!is_file($path)) {
        return;
    }
    $tpl = json_decode((string)file_get_contents($path), true);
    if (!is_array($tpl) || !isset($tpl["sections"]) || !is_array($tpl["sections"])) {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $base = pathinfo($file, PATHINFO_FILENAME);
    $outName = $base . "_template_export.csv";
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"" . str_replace('"', "", $outName) . "\"");
    header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
    $headers = ["sec", "label", "type", "placeholder", "info", "process", "checks", "subchecks", "options", "ref", "calc", "required", "inname", "inresult", "stufe_slot"];
    $out = fopen("php://output", "w");
    if ($out === false) {
        exit;
    }
    fputcsv($out, $headers, ";", '"', "\\");
    foreach (rkrk_template_flatten_sections_for_csv($tpl["sections"]) as $r) {
        $line = [];
        foreach ($headers as $h) {
            $line[] = rkrk_template_csv_escape_cell($r[$h] ?? "");
        }
        fputcsv($out, $line, ";", '"', "\\");
    }
    fclose($out);
    exit;
}

/**
 * Template als JSON-Datei herunterladen (roher Dateiinhalt). Bei Erfolg: exit.
 */
function rkrk_template_export_json_download(string $templateFolder, string $file): void
{
    $file = basename($file);
    if ($file === "" || !preg_match('/\.json$/i', $file)) {
        return;
    }
    $path = $templateFolder . "/" . $file;
    if (!is_file($path)) {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $safeName = str_replace(["\r", "\n", '"'], "", $file);
    header("Content-Type: application/json; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"" . $safeName . "\"");
    header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    header("Expires: 0");
    readfile($path);
    exit;
}

/** @return 'ok'|'err'|'invalid' */
function rkrk_template_import_json_upload(string $templateFolder, string $file, $uploadJson): string
{
    $file = basename($file);
    if ($file === "" || !preg_match('/\.json$/i', $file)) {
        return "err";
    }
    if (!is_array($uploadJson) || (int)($uploadJson["error"] ?? -1) !== UPLOAD_ERR_OK) {
        return "err";
    }
    $tmp = (string)($uploadJson["tmp_name"] ?? "");
    $raw = @file_get_contents($tmp);
    if ($raw === false || $raw === "") {
        return "err";
    }
    $data = json_decode($raw, true, 512, JSON_BIGINT_AS_STRING);
    if (!is_array($data) || !isset($data["sections"]) || !is_array($data["sections"])) {
        return "invalid";
    }
    if (!isset($data["title"]) || trim((string)$data["title"]) === "") {
        $data["title"] = pathinfo($file, PATHINFO_FILENAME);
    }
    $path = $templateFolder . "/" . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return "ok";
}

/** @return 'ok'|'err'|'hdr'|'empty' */
function rkrk_template_import_csv_upload(string $templateFolder, string $file, $uploadCsv): string
{
    $file = basename($file);
    if ($file === "" || !preg_match('/\.json$/i', $file)) {
        return "err";
    }
    if (!is_array($uploadCsv) || (int)($uploadCsv["error"] ?? -1) !== UPLOAD_ERR_OK) {
        return "err";
    }
    $tmp = (string)($uploadCsv["tmp_name"] ?? "");
    $fh = fopen($tmp, "r");
    if ($fh === false) {
        return "err";
    }
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fh);
    }
    $header = fgetcsv($fh, 0, ";", '"');
    if (!is_array($header)) {
        fclose($fh);
        return "hdr";
    }
    $header = array_map("trim", $header);
    $rows = [];
    while (($row = fgetcsv($fh, 0, ";", '"')) !== false) {
        if (!is_array($row) || count($row) < 2) {
            continue;
        }
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = isset($row[$i]) ? rkrk_template_csv_unescape_cell((string)$row[$i]) : "";
        }
        $rows[] = $assoc;
    }
    fclose($fh);

    $sections = rkrk_template_sections_from_csv_rows($rows);
    if ($sections === []) {
        return "empty";
    }

    $path = $templateFolder . "/" . $file;
    $existing = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
    $title = is_array($existing) && isset($existing["title"]) ? (string)$existing["title"] : pathinfo($file, PATHINFO_FILENAME);
    $tpl = ["title" => $title, "sections" => $sections];
    if (is_array($existing)) {
        foreach (["_meta", "_description", "_settings"] as $k) {
            if (isset($existing[$k]) && is_array($existing[$k])) {
                $tpl[$k] = $existing[$k];
            }
        }
    }
    file_put_contents($path, json_encode($tpl, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return "ok";
}

/**
 * HTML-Druckvorschau; nutzt safe() falls vorhanden, sonst htmlspecialchars.
 * Bei Erfolg: exit. Ungültig: return.
 */
function rkrk_template_print_html(string $templateFolder, string $file): void
{
    $file = basename($file);
    if ($file === "" || !preg_match('/\.json$/i', $file)) {
        return;
    }
    $path = $templateFolder . "/" . $file;
    if (!is_file($path)) {
        return;
    }
    $tpl = json_decode((string)file_get_contents($path), true);
    if (!is_array($tpl) || !isset($tpl["sections"])) {
        return;
    }

    $h = static function ($s) {
        if (function_exists("safe")) {
            return safe($s);
        }
        return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
    };
    $fmtRich = static function (string $s) use ($h): string {
        $s = trim($s);
        if ($s === "") {
            return "";
        }
        if (strip_tags($s) !== $s) {
            if (function_exists("rkrk_assess_format_template_html_fragment")) {
                return rkrk_assess_format_template_html_fragment($s, "print");
            }
            if (function_exists("rkrk_paper_format_rich_text")) {
                return rkrk_paper_format_rich_text($s);
            }
            return $s;
        }
        if (function_exists("rkrk_paper_format_rich_text")) {
            return rkrk_paper_format_rich_text($s);
        }
        return nl2br($h($s));
    };

    $title = $tpl["title"] ?? $file;
    header("Content-Type: text/html; charset=UTF-8");
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>" . $h($title) . " — Druckansicht</title>";
    if (function_exists("rkrk_assess_favicon_link_tags_html")) {
        echo rkrk_assess_favicon_link_tags_html();
    } elseif (function_exists("rkrk_assess_emit_favicon_link_tags")) {
        rkrk_assess_emit_favicon_link_tags();
    }
    echo "<style>
    body{font-family:Segoe UI,Arial,sans-serif;margin:18px 22px;color:#1a1f1a;line-height:1.45;}
    h1{font-size:1.35rem;margin:0 0 10px;}
    .meta{color:#4a5a4a;font-size:13px;margin-bottom:18px;}
    h2{font-size:1.05rem;margin:22px 0 8px;border-bottom:1px solid #c5d4c0;padding-bottom:4px;}
    .fld{margin:10px 0 14px;padding:10px 12px;border:1px solid #e2e8e0;border-radius:8px;background:#fafbf9;}
    .lbl{font-weight:700;font-size:14px;}
    .typ{color:#64748b;font-size:12px;margin:2px 0 6px;}
    .box{margin-top:6px;padding:8px 10px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;line-height:1.45;}
    .box p{margin:.35em 0;}
    .box ul,.box ol{margin:.35em 0;padding-left:1.25em;}
    img{border-radius:5px 15% 10px;}
    .box img{max-width:100%;height:auto;width:auto;max-height:300px;display:block;margin:6px auto;object-fit:contain;}
    .lernimagelarge,.box img.lernimagelarge{max-height:500px;}
    .lernimage,.box img.lernimage{max-height:300px;}
    .lernimagesmall,.box img.lernimagesmall{max-height:200px;}
    .lernimageverysmall,.box img.lernimageverysmall{max-height:100px;}
    .lernimagefixwidth,.box img.lernimagefixwidth{width:600px;max-width:100%;height:auto;max-height:none;}
    .lernimagefixheight,.box img.lernimagefixheight{max-height:200px;}
    .lernimagefixlargeheight,.box img.lernimagefixlargeheight{max-height:500px;}
    .lernimagesmallfixwidth,.box img.lernimagesmallfixwidth{width:450px;max-width:100%;height:auto;max-height:none;}
    .lerncaption{font-style:italic;}
    .noprint{margin:14px 0 22px;}
    @media print{.noprint{display:none;} body{margin:12mm;}}
    </style></head><body>";
    echo "<div class=\"noprint\"><a href=\"?mode=edittemplate&amp;file=" . rawurlencode($file) . "\">Zurück zum Editor</a> · <a href=\"?mode=templates\">Alle Templates</a> · <button type=\"button\" onclick=\"window.print()\">Drucken</button></div>";
    echo "<h1>" . $h($title) . "</h1>";
    if (!empty($tpl["_meta"]) && is_array($tpl["_meta"])) {
        $bits = [];
        foreach ($tpl["_meta"] as $k => $v) {
            if (is_scalar($v)) {
                $bits[] = $h((string)$k) . ": " . $h((string)$v);
            }
        }
        if ($bits !== []) {
            echo "<div class=\"meta\">" . implode(" · ", $bits) . "</div>";
        }
    }
    if (!empty($tpl["_description"]) && is_array($tpl["_description"])) {
        foreach ($tpl["_description"] as $k => $v) {
            if (is_string($v) && trim($v) !== "") {
                echo "<div class=\"fld\"><span class=\"lbl\">" . $h((string)$k) . "</span><div class=\"box\">" . $fmtRich((string)$v) . "</div></div>";
            }
        }
    }
    foreach ($tpl["sections"] as $secName => $fields) {
        if (!is_array($fields)) {
            continue;
        }
        echo "<h2>" . $h((string)$secName) . "</h2>";
        foreach ($fields as $label => $meta) {
            echo "<div class=\"fld\"><div class=\"lbl\">" . $h((string)$label) . "</div>";
            if (is_array($meta)) {
                $ts = isset($meta["type"]) ? (string)$meta["type"] : "text";
                echo "<div class=\"typ\">Typ: " . $h($ts) . "</div>";
                foreach (["placeholder", "info", "process", "checks", "subchecks", "options", "calc", "required"] as $k) {
                    if (!isset($meta[$k]) || trim((string)$meta[$k]) === "") {
                        continue;
                    }
                    echo "<div><strong>" . $h($k) . ":</strong><div class=\"box\">" . $fmtRich((string)$meta[$k]) . "</div></div>";
                }
                if (isset($meta["points"]) && is_array($meta["points"])) {
                    $pl = array_map("strval", $meta["points"]);
                    echo "<div><strong>points:</strong><div class=\"box\">" . nl2br($h(implode("\n", $pl))) . "</div></div>";
                }
            } else {
                echo "<div class=\"typ\">Typ: " . $h((string)$meta) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</body></html>";
    exit;
}
