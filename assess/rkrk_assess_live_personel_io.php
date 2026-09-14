<?php
/**
 * Excel (.xlsx) Import/Export für pilotenliste.json — ohne Composer.
 * Erste Tabelle, erste Zeile = Kopfzeile (deutsche/englische Spaltennamen).
 */

declare(strict_types=1);

/** Spaltenindex 0=A → Excel-Spaltenbuchstaben */
function rkrk_live_personel_io_col_letters(int $idx): string
{
    $s = "";
    $n = $idx;
    do {
        $s = chr(65 + ($n % 26)) . $s;
        $n = intdiv($n, 26) - 1;
    } while ($n >= 0);
    return $s;
}

/** A / AA → 0-basierter Spaltenindex */
function rkrk_live_personel_io_letters_to_idx(string $L): int
{
    $L = strtoupper(trim($L));
    $n = 0;
    $len = strlen($L);
    for ($i = 0; $i < $len; $i++) {
        $n = $n * 26 + (ord($L[$i]) - 64);
    }
    return $n - 1;
}

/** Kopfzeile aus Excel/CSV → interner Schlüssel */
function rkrk_live_personel_io_header_to_key(string $h): ?string
{
    $t = trim(preg_replace('/\s+/u', " ", $h));
    if ($t === "") {
        return null;
    }
    $u = mb_strtolower($t, "UTF-8");
    $map = [
        "vorname" => "vorname",
        "nachname" => "nachname",
        "name" => "name",
        "anzeigename" => "display",
        "display" => "display",
        "kurzname" => "name",
        "strasse" => "strasse",
        "straße" => "strasse",
        "adresse" => "strasse",
        "plz" => "plz",
        "ort" => "ort",
        "e-mail" => "email",
        "email" => "email",
        "telefon" => "telefon",
        "tel" => "telefon",
        "mobil" => "telefon",
        "mobiltelefon" => "telefon",
        "handy" => "telefon",
        "erste erfahrung" => "erste_erfahrung",
        "erfahrung" => "erste_erfahrung",
        "stufe" => "erste_erfahrung",
        "drohnentyp" => "drohnentyp",
        "drohne typ" => "drohnentyp",
        "drone type" => "drohnentyp",
        "bemerkung" => "bemerkung",
        "note" => "note",
        "kommentar" => "kommentar",
        "instruktor" => "instruktor",
        "instructor" => "instruktor",
        "instruktor/in" => "instruktor",
        "alternative rollen" => "alternative_rollen",
        "alternative rollen (pilot)" => "alternative_rollen",
        "alternative roles" => "alternative_rollen",
        "alt. rollen" => "alternative_rollen",
        "alt rollen" => "alternative_rollen",
        "fpl gültig bis" => "fpl_gueltig",
        "fpl gültig" => "fpl_gueltig",
        "fpl_gueltig" => "fpl_gueltig",
        "gültigkeitsdatum fernpilotenlizenz" => "fpl_gueltig",
        "gueltigkeitsdatum fernpilotenlizenz" => "fpl_gueltig",
        "ep rkrs+" => "einsatzpruefung_rkrsplus",
        "einsatzprüfung rkrs+" => "einsatzpruefung_rkrsplus",
        "einsatzpruefung rkrs+" => "einsatzpruefung_rkrsplus",
        "einsatzpruefung_rkrsplus" => "einsatzpruefung_rkrsplus",
        "rkrs+" => "rkrsplus",
        "rkrs plus" => "rkrsplus",
        "rkrsplus" => "rkrsplus",
        "rkrs_plus" => "rkrsplus",
    ];
    return $map[$u] ?? null;
}

function rkrk_live_personel_io_row_has_identity(array $clean): bool
{
    $em = trim((string)($clean["email"] ?? ""));
    if ($em !== "") {
        return true;
    }
    $vn = trim((string)($clean["vorname"] ?? ""));
    $nn = trim((string)($clean["nachname"] ?? ""));
    if ($vn !== "" && $nn !== "") {
        return true;
    }
    if (trim((string)($clean["name"] ?? "")) !== "") {
        return true;
    }
    if (trim((string)($clean["display"] ?? "")) !== "") {
        return true;
    }
    $tel = preg_replace('/\D+/u', "", (string)($clean["telefon"] ?? ""));
    if (strlen($tel) >= 6) {
        return true;
    }
    return false;
}

function rkrk_live_personel_io_parse_bool_instruktor(string $v): string
{
    $s = mb_strtolower(trim($v), "UTF-8");
    if ($s === "" || $s === "0" || $s === "nein" || $s === "no" || $s === "false") {
        return "";
    }
    if ($s === "1" || $s === "ja" || $s === "yes" || $s === "x" || $s === "j" || $s === "true" || $s === "instruktor") {
        return "1";
    }
    return "";
}

/**
 * @return array<int, array<string, string>> Zeilen als flache string-Arrays (nur gemappte Keys)
 */
function rkrk_live_personel_io_parse_csv_string(string $raw): array
{
    $raw = preg_replace('/^\xEF\xBB\xBF/', "", $raw);
    $lines = preg_split("/\R/u", $raw) ?: [];
    if ($lines === []) {
        return [];
    }
    $delim = str_contains($lines[0], ";") && !str_contains($lines[0], ",") ? ";" : ",";
    $headerLine = array_shift($lines);
    $headers = str_getcsv($headerLine, $delim);
    $keys = [];
    foreach ($headers as $i => $h) {
        $k = rkrk_live_personel_io_header_to_key((string)$h);
        if ($k !== null) {
            $keys[$i] = $k;
        }
    }
    $out = [];
    foreach ($lines as $line) {
        if (trim($line) === "") {
            continue;
        }
        $cells = str_getcsv($line, $delim);
        $row = [];
        foreach ($keys as $i => $key) {
            $row[$key] = trim((string)($cells[$i] ?? ""));
        }
        if ($row !== []) {
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * @return array{0: list<string>, 1: list<list<string>>}
 */
function rkrk_live_personel_io_parse_xlsx(string $path): array
{
    if (!class_exists("ZipArchive")) {
        return [[], []];
    }
    $z = new ZipArchive();
    if ($z->open($path) !== true) {
        return [[], []];
    }
    $ssXml = $z->getFromName("xl/sharedStrings.xml");
    $strings = [];
    if ($ssXml !== false && $ssXml !== "") {
        $dom = new DOMDocument();
        if (@$dom->loadXML($ssXml)) {
            foreach ($dom->getElementsByTagName("si") as $si) {
                $strings[] = $si->textContent;
            }
        }
    }
    $sheetXml = $z->getFromName("xl/worksheets/sheet1.xml");
    $z->close();
    if ($sheetXml === false || $sheetXml === "") {
        return [[], []];
    }
    $dom = new DOMDocument();
    if (!@$dom->loadXML($sheetXml)) {
        return [[], []];
    }
    /** @var array<int, array<int, string>> $grid */
    $grid = [];
    foreach ($dom->getElementsByTagName("c") as $c) {
        $ref = $c->getAttribute("r");
        if ($ref === "" || !preg_match('/^([A-Z]+)(\d+)$/i', $ref, $m)) {
            continue;
        }
        $colIdx = rkrk_live_personel_io_letters_to_idx($m[1]);
        $rowNum = (int)$m[2];
        if ($rowNum < 1) {
            continue;
        }
        $t = $c->getAttribute("t");
        $vEl = null;
        $isEl = null;
        foreach ($c->childNodes as $ch) {
            if ($ch->nodeName === "v") {
                $vEl = $ch;
            }
            if ($ch->nodeName === "is") {
                $isEl = $ch;
            }
        }
        $text = "";
        if ($t === "inlineStr" && $isEl !== null) {
            $text = $isEl->textContent;
        } elseif ($t === "s" && $vEl !== null) {
            $idx = (int)trim($vEl->textContent);
            $text = $strings[$idx] ?? "";
        } elseif ($vEl !== null) {
            $text = trim($vEl->textContent);
        }
        $grid[$rowNum][$colIdx] = $text;
    }
    if ($grid === []) {
        return [[], []];
    }
    ksort($grid, SORT_NUMERIC);
    $matrix = [];
    foreach ($grid as $rn => $cols) {
        ksort($cols, SORT_NUMERIC);
        $maxC = max(array_keys($cols));
        $line = [];
        for ($j = 0; $j <= $maxC; $j++) {
            $line[] = $cols[$j] ?? "";
        }
        $matrix[] = $line;
    }
    if ($matrix === []) {
        return [[], []];
    }
    $headerCells = array_shift($matrix);
    $headers = [];
    foreach ($headerCells as $h) {
        $headers[] = (string)$h;
    }
    return [$headers, $matrix];
}

/**
 * @param list<string> $headerRow
 * @param list<list<string>> $dataRows
 * @return array<int, array<string, string>>
 */
function rkrk_live_personel_io_matrix_to_rows(array $headerRow, array $dataRows): array
{
    $keys = [];
    foreach ($headerRow as $i => $h) {
        $k = rkrk_live_personel_io_header_to_key((string)$h);
        if ($k !== null) {
            $keys[$i] = $k;
        }
    }
    $out = [];
    foreach ($dataRows as $cells) {
        $row = [];
        foreach ($keys as $i => $key) {
            $row[$key] = trim((string)($cells[$i] ?? ""));
        }
        if ($row !== []) {
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * @param array<int, array<string, string>> $imported
 * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int} merged rows, added count, updated count
 */
function rkrk_live_personel_io_merge_rows(
    array $existing,
    array $imported,
    bool $allAsInstruktor,
    callable $dedupeKey,
    callable $mergeTwo
): array {
    $byKey = [];
    foreach ($existing as $r) {
        if (!is_array($r)) {
            continue;
        }
        $byKey[$dedupeKey($r)] = $r;
    }
    $added = 0;
    $updated = 0;
    foreach ($imported as $imp) {
        $clean = [];
        foreach ($imp as $k => $v) {
            if (!is_string($k) || !preg_match('/^[\p{L}\p{N}_\-.]+$/u', $k)) {
                continue;
            }
            if ($k === "instruktor" || $k === "rkrsplus") {
                $clean[$k] = rkrk_live_personel_io_parse_bool_instruktor((string)$v);
            } else {
                $clean[$k] = trim((string)$v);
            }
        }
        if (!rkrk_live_personel_io_row_has_identity($clean)) {
            continue;
        }
        if ($allAsInstruktor) {
            $clean["instruktor"] = "1";
        } elseif (!isset($clean["instruktor"]) || $clean["instruktor"] === "") {
            $clean["instruktor"] = "";
        }
        $tmp = $clean + ["id" => ""];
        $key = $dedupeKey($tmp);
        if (!isset($byKey[$key])) {
            $new = ["id" => bin2hex(random_bytes(8))];
            foreach ($clean as $kk => $vv) {
                if ($kk === "id") {
                    continue;
                }
                $new[$kk] = $vv;
            }
            $byKey[$key] = $new;
            $added++;
            continue;
        }
        $merged = $mergeTwo($byKey[$key], $clean);
        if ($merged !== $byKey[$key]) {
            $updated++;
        }
        $byKey[$key] = $merged;
    }
    return [array_values($byKey), $added, $updated];
}

/**
 * Nur neue Zeilen anhängen, immer mit instruktor=1 (Telefonliste Instruktoren).
 *
 * @param array<int, array<string, mixed>> $existing
 * @param array<int, array<string, string>> $imported
 * @return array{0: array<int, array<string, mixed>>, 1: int}
 */
function rkrk_live_personel_io_merge_only_new_instructors(
    array $existing,
    array $imported,
    callable $dedupeKey
): array {
    $byKey = [];
    foreach ($existing as $r) {
        if (!is_array($r)) {
            continue;
        }
        $byKey[$dedupeKey($r)] = $r;
    }
    $added = 0;
    foreach ($imported as $imp) {
        $clean = [];
        foreach ($imp as $k => $v) {
            if (!is_string($k) || !preg_match('/^[\p{L}\p{N}_\-.]+$/u', $k)) {
                continue;
            }
            if ($k === "instruktor" || $k === "rkrsplus") {
                $clean[$k] = rkrk_live_personel_io_parse_bool_instruktor((string)$v);
            } else {
                $clean[$k] = trim((string)$v);
            }
        }
        if (!rkrk_live_personel_io_row_has_identity($clean)) {
            continue;
        }
        $clean["instruktor"] = "1";
        $tmp = $clean + ["id" => ""];
        $key = $dedupeKey($tmp);
        if (isset($byKey[$key])) {
            continue;
        }
        $new = ["id" => bin2hex(random_bytes(8))];
        foreach ($clean as $kk => $vv) {
            if ($kk === "id") {
                continue;
            }
            $new[$kk] = $vv;
        }
        $byKey[$key] = $new;
        $added++;
    }
    return [array_values($byKey), $added];
}

function rkrk_live_personel_io_build_xlsx_bytes(array $columns, array $rows): string
{
    $esc = static function (string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, "UTF-8");
    };
    $sb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sb .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>';
    $rowNum = 1;
    $sb .= '<row r="' . $rowNum . '">';
    $ci = 0;
    foreach ($columns as $col) {
        $L = rkrk_live_personel_io_col_letters($ci);
        $label = function_exists("rkrk_live_personel_label") ? rkrk_live_personel_label($col) : $col;
        $sb .= '<c r="' . $L . $rowNum . '" t="inlineStr"><is><t>' . $esc($label) . '</t></is></c>';
        $ci++;
    }
    $sb .= '</row>';
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $rowNum++;
        $sb .= '<row r="' . $rowNum . '">';
        $ci = 0;
        foreach ($columns as $col) {
            $L = rkrk_live_personel_io_col_letters($ci);
            $val = isset($r[$col]) ? (string)(is_scalar($r[$col]) ? $r[$col] : json_encode($r[$col], JSON_UNESCAPED_UNICODE)) : "";
            if ($col === "instruktor" || $col === "rkrsplus") {
                $val = (trim($val) === "1" || strcasecmp(trim($val), "ja") === 0) ? "Ja" : "";
            }
            $sb .= '<c r="' . $L . $rowNum . '" t="inlineStr"><is><t>' . $esc($val) . '</t></is></c>';
            $ci++;
        }
        $sb .= '</row>';
    }
    $sb .= '</sheetData></worksheet>';
    $sheet1 = $sb;

    $styles = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
        . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
        . '<borders count="1"><border/></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
        . '</styleSheet>';

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Personal" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $wbRels = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $rootRels = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $ct = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    if (!class_exists("ZipArchive")) {
        return "";
    }
    $tmp = tempnam(sys_get_temp_dir(), "rkxl");
    if ($tmp === false) {
        return "";
    }
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
        @unlink($tmp);
        return "";
    }
    $zip->addFromString("[Content_Types].xml", $ct);
    $zip->addFromString("_rels/.rels", $rootRels);
    $zip->addFromString("xl/workbook.xml", $workbook);
    $zip->addFromString("xl/_rels/workbook.xml.rels", $wbRels);
    $zip->addFromString("xl/styles.xml", $styles);
    $zip->addFromString("xl/worksheets/sheet1.xml", $sheet1);
    $zip->close();
    $bin = (string)@file_get_contents($tmp);
    @unlink($tmp);
    return $bin;
}
