<?php
/**
 * Trainingsübersicht: PDF-, Excel- und Personallisten-Export plus Mailto.
 */

if (!function_exists("rkrk_tr_result_score")) {
	/** Punktzahl/Prozent eines Resultats, sonst "". */
	function rkrk_tr_result_score(?array $hit): string {
		if (!$hit) {
			return "";
		}
		$pct = (float) ($hit["pct"] ?? 0);
		$anz = (int) ($hit["anz"] ?? 0);
		$richtig = (int) ($hit["richtig"] ?? 0);
		$pctStr = rtrim(rtrim(number_format($pct, 1, ".", ""), "0"), ".");
		if ($anz > 0) {
			$s = $richtig . "/" . $anz;
			if ($pctStr !== "") {
				$s .= " · " . $pctStr . "%";
			}
			return $s;
		}
		if ($pct > 0) {
			return $pctStr . "%";
		}
		return "";
	}
}

if (!function_exists("rkrk_tr_cell_text")) {
	function rkrk_tr_cell_text(?array $hit): string {
		if (!$hit) {
			return "–";
		}
		$status = (string) ($hit["status"] ?? "done");
		if ($status === "progress") {
			return "In Arbeit";
		}
		$score = function_exists("rkrk_tr_result_score") ? rkrk_tr_result_score($hit) : "";
		if ($score !== "") {
			return $score;
		}
		return !empty($hit["passed"]) ? "Bestanden" : "Nicht bestanden";
	}
}

if (!function_exists("rkrk_tr_fill_key")) {
	function rkrk_tr_fill_key(string $st): string {
		if ($st === "ok") {
			return "ok";
		}
		if ($st === "fail") {
			return "fail";
		}
		if ($st === "progress" || $st === "prog") {
			return "progress";
		}
		return "open";
	}
}

if (!function_exists("rkrk_tr_overall_text")) {
	function rkrk_tr_overall_text(array $row): string {
		$st = (string) ($row["status"] ?? "");
		if ($st === "ok") {
			return "Vollständig";
		}
		if ($st === "fail") {
			return "Nicht bestanden";
		}
		if ($st === "empty") {
			return "Keine Codes";
		}
		return "Offen";
	}
}

if (!function_exists("rkrk_tr_export_stem")) {
	function rkrk_tr_export_stem(string $listName, string $kind = "uebersicht"): string {
		$slug = function_exists("rkrk_tr_slug") ? rkrk_tr_slug($listName) : "liste";
		$prefix = $kind === "personel" ? "Teilnehmerliste" : "Trainingsuebersicht";
		return $prefix . "_" . $slug . "_" . date("Y-m-d");
	}
}

if (!function_exists("rkrk_tr_xlsx_col")) {
	function rkrk_tr_xlsx_col(int $idx): string {
		$s = "";
		$n = $idx;
		do {
			$s = chr(65 + ($n % 26)) . $s;
			$n = intdiv($n, 26) - 1;
		} while ($n >= 0);
		return $s;
	}
}

if (!function_exists("rkrk_tr_xlsx_bytes")) {
	/**
	 * @param list<array{name:string,headers:list<string>,rows:list<list<string>>}> $sheets
	 */
	function rkrk_tr_xlsx_bytes(array $sheets): string {
		if (!class_exists("ZipArchive")) {
			return "";
		}
		$esc = static function (string $s): string {
			$s = (string) preg_replace("/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/", "", $s);
			return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, "UTF-8");
		};
		$sheetXml = array();
		$usedNames = array();
		$si = 0;
		foreach ($sheets as $sh) {
			if (!is_array($sh)) {
				continue;
			}
			$si++;
			$name = trim((string) ($sh["name"] ?? ("Blatt" . $si)));
			$name = (string) preg_replace("/[:\\\\\\/\\?\\*\\[\\]]/", " ", $name);
			$name = trim($name) !== "" ? substr($name, 0, 31) : ("Blatt" . $si);
			$base = $name;
			$n = 2;
			while (isset($usedNames[strtolower($name)])) {
				$suffix = "-" . $n;
				$name = substr($base, 0, 31 - strlen($suffix)) . $suffix;
				$n++;
			}
			$usedNames[strtolower($name)] = true;
			$headers = isset($sh["headers"]) && is_array($sh["headers"]) ? $sh["headers"] : array();
			$rows = isset($sh["rows"]) && is_array($sh["rows"]) ? $sh["rows"] : array();
			$fills = isset($sh["fills"]) && is_array($sh["fills"]) ? $sh["fills"] : array();
			$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
			$xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
			$rowNum = 1;
			$xml .= '<row r="' . $rowNum . '">';
			$ci = 0;
			foreach ($headers as $h) {
				$xml .= '<c r="' . rkrk_tr_xlsx_col($ci) . $rowNum . '" s="1" t="inlineStr"><is><t>' . $esc((string) $h) . '</t></is></c>';
				$ci++;
			}
			$xml .= '</row>';
			$ri = 0;
			foreach ($rows as $r) {
				if (!is_array($r)) {
					continue;
				}
				$rowNum++;
				$xml .= '<row r="' . $rowNum . '">';
				$ci = 0;
				foreach ($headers as $hi => $_h) {
					$val = isset($r[$hi]) ? (string) $r[$hi] : "";
					$fill = isset($fills[$ri][$hi]) ? (string) $fills[$ri][$hi] : "";
					$xf = 0;
					if ($fill === "ok") {
						$xf = 2;
					} elseif ($fill === "fail") {
						$xf = 3;
					} elseif ($fill === "progress" || $fill === "prog") {
						$xf = 5;
					} elseif ($fill === "open") {
						$xf = 4;
					}
					$sAttr = $xf > 0 ? ' s="' . $xf . '"' : "";
					$xml .= '<c r="' . rkrk_tr_xlsx_col($ci) . $rowNum . '"' . $sAttr . ' t="inlineStr"><is><t>' . $esc($val) . '</t></is></c>';
					$ci++;
				}
				$xml .= '</row>';
				$ri++;
			}
			$xml .= '</sheetData></worksheet>';
			$sheetXml[] = array("name" => $name, "xml" => $xml);
		}
		if ($sheetXml === array()) {
			return "";
		}
		$wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
		$wbRels = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
		$ct = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
		foreach ($sheetXml as $i => $one) {
			$id = $i + 1;
			$rid = "rId" . $id;
			$wb .= '<sheet name="' . $esc($one["name"]) . '" sheetId="' . $id . '" r:id="' . $rid . '"/>';
			$wbRels .= '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
			$ct .= '<Override PartName="/xl/worksheets/sheet' . $id . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
		}
		$wb .= '</sheets></workbook>';
		$stylesRid = "rId" . (count($sheetXml) + 1);
		$wbRels .= '<Relationship Id="' . $stylesRid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
		$wbRels .= '</Relationships>';
		$ct .= '</Types>';
		$styles = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<fonts count="2">'
			. '<font><sz val="11"/><name val="Calibri"/></font>'
			. '<font><b/><sz val="11"/><name val="Calibri"/></font>'
			. '</fonts>'
			. '<fills count="6">'
			. '<fill><patternFill patternType="none"/></fill>'
			. '<fill><patternFill patternType="gray125"/></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FFD9F2C9"/></patternFill></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FFFDE8E8"/></patternFill></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF8E8"/></patternFill></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEDD5"/></patternFill></fill>'
			. '</fills>'
			. '<borders count="1"><border/></borders>'
			. '<cellXfs count="6">'
			. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
			. '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/>'
			. '<xf numFmtId="0" fontId="0" fillId="2" borderId="0" applyFill="1"/>'
			. '<xf numFmtId="0" fontId="0" fillId="3" borderId="0" applyFill="1"/>'
			. '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" applyFill="1"/>'
			. '<xf numFmtId="0" fontId="0" fillId="5" borderId="0" applyFill="1"/>'
			. '</cellXfs>'
			. '</styleSheet>';
		$rootRels = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';
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
		$zip->addFromString("xl/workbook.xml", $wb);
		$zip->addFromString("xl/_rels/workbook.xml.rels", $wbRels);
		$zip->addFromString("xl/styles.xml", $styles);
		foreach ($sheetXml as $i => $one) {
			$zip->addFromString("xl/worksheets/sheet" . ($i + 1) . ".xml", $one["xml"]);
		}
		$zip->close();
		$bin = (string) @file_get_contents($tmp);
		@unlink($tmp);
		return $bin;
	}
}

if (!function_exists("rkrk_tr_status_sheet")) {
	/**
	 * @param list<array<string,mixed>> $visible
	 * @param list<string> $codes
	 * @return array{name:string,headers:list<string>,rows:list<list<string>>}
	 */
	function rkrk_tr_status_sheet(array $visible, array $codes, string $listName, bool $withRemark = false): array {
		$headers = array("Nachname", "Vorname", "Name", "RKRS-ID");
		foreach ($codes as $c) {
			$headers[] = (string) $c;
		}
		$headers[] = "Gesamt";
		$headers[] = "Aktiv";
		$headers[] = "Bemerkung";
		$rows = array();
		$fills = array();
		foreach ($visible as $r) {
			if (!is_array($r)) {
				continue;
			}
			$off = !empty($r["disabled"]);
			$line = array(
				(string) ($r["nachname"] ?? ""),
				(string) ($r["vorname"] ?? ""),
				(string) ($r["name"] ?? ""),
				(string) (($r["rkrs_id"] ?? "") !== "" ? $r["rkrs_id"] : ($r["pilot_id"] ?? "")),
			);
			$fill = array("", "", "", "");
			foreach ($codes as $code) {
				$cell = isset($r["cells"][$code]) && is_array($r["cells"][$code]) ? $r["cells"][$code] : array();
				$hit = isset($cell["hit"]) && is_array($cell["hit"]) ? $cell["hit"] : null;
				$line[] = rkrk_tr_cell_text($hit);
				$fill[] = function_exists("rkrk_tr_fill_key")
					? rkrk_tr_fill_key((string) ($cell["status"] ?? "open"))
					: "";
			}
			$line[] = rkrk_tr_overall_text($r);
			$fill[] = function_exists("rkrk_tr_fill_key")
				? rkrk_tr_fill_key((string) ($r["status"] ?? "open"))
				: "";
			$line[] = $off ? "Nein" : "Ja";
			$fill[] = $off ? "fail" : "ok";
			$line[] = (string) ($r["remark"] ?? "");
			$fill[] = "";
			$rows[] = $line;
			$fills[] = $fill;
		}
		$name = $listName !== "" ? $listName : "Uebersicht";
		return array("name" => $name, "headers" => $headers, "rows" => $rows, "fills" => $fills);
	}
}

if (!function_exists("rkrk_tr_personel_lookup")) {
	/** @param list<array<string,mixed>> $visible @return list<array<string,mixed>> */
	function rkrk_tr_personel_lookup(array $visible): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$byKey = array();
		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			foreach (rkrk_tr_pilot_keys_of($row) as $k) {
				$byKey["id:" . $k] = $row;
			}
			$byKey["id:" . rkrk_tr_pilot_key($row)] = $row;
		}
		$out = array();
		foreach ($visible as $v) {
			if (!is_array($v)) {
				continue;
			}
			$id = (string) ($v["id"] ?? "");
			$src = ($id !== "" && isset($byKey["id:" . $id])) ? $byKey["id:" . $id] : array();
			if ($src === array()) {
				$src = array(
					"vorname" => (string) ($v["vorname"] ?? ""),
					"nachname" => (string) ($v["nachname"] ?? ""),
					"rkrs_pilot_id" => (string) ($v["rkrs_id"] ?? ""),
					"pilot_id" => (string) ($v["pilot_id"] ?? ""),
				);
			}
			if (function_exists("rkrk_tr_name_parts")) {
				list($vor, $nach) = rkrk_tr_name_parts($src);
				$src["vorname"] = $vor !== "" ? $vor : (string) ($v["vorname"] ?? "");
				$src["nachname"] = $nach !== "" ? $nach : (string) ($v["nachname"] ?? "");
			}
			if (function_exists("rkrk_assess_personel_row_effective_rkrs_id") && trim((string) ($src["rkrs_pilot_id"] ?? "")) === "") {
				$src["rkrs_pilot_id"] = rkrk_assess_personel_row_effective_rkrs_id($src);
			}
			$src["aktiv"] = empty($v["disabled"]) ? "Ja" : "Nein";
			$src["bemerkung"] = (string) ($v["remark"] ?? "");
			$out[] = $src;
		}
		return $out;
	}
}

if (!function_exists("rkrk_tr_personel_cell")) {
	/** @param mixed $val */
	function rkrk_tr_personel_cell($val, string $col): string {
		if (is_array($val) || is_object($val)) {
			$val = json_encode($val, JSON_UNESCAPED_UNICODE);
		}
		$s = trim((string) $val);
		if ($col === "rkrsplus" || $col === "instruktor") {
			$on = function_exists("rkrk_assess_personel_cell_truthy")
				? rkrk_assess_personel_cell_truthy($val)
				: ($s === "1" || strcasecmp($s, "ja") === 0);
			return $on ? "Ja" : "";
		}
		return $s;
	}
}

if (!function_exists("rkrk_tr_personel_sheet")) {
	/**
	 * @param list<array<string,mixed>> $visible
	 * @return array{name:string,headers:list<string>,rows:list<list<string>>}
	 */
	function rkrk_tr_personel_sheet(array $visible, string $listName): array {
		$cols = array(
			"nachname" => "Nachname",
			"vorname" => "Vorname",
			"rkrs_pilot_id" => "RKRS Piloten ID",
			"pilot_id" => "PilotID",
			"telefon" => "Telefon",
			"email" => "E-Mail",
			"strasse" => "Strasse",
			"plz" => "PLZ",
			"ort" => "Ort",
			"drohnentyp" => "Drohnentyp",
			"erste_erfahrung" => "Maturität",
			"rkrsplus" => "RKRS+",
			"instruktor" => "Instruktor",
			"fpl_gueltig" => "FPL gültig bis",
			"aktiv" => "Aktiv",
			"bemerkung" => "Bemerkung",
		);
		$headers = array("Liste");
		foreach ($cols as $lab) {
			$headers[] = $lab;
		}
		$rows = array();
		foreach (rkrk_tr_personel_lookup($visible) as $src) {
			$line = array($listName);
			foreach (array_keys($cols) as $col) {
				$line[] = rkrk_tr_personel_cell($src[$col] ?? "", $col);
			}
			$rows[] = $line;
		}
		return array("name" => "Teilnehmer", "headers" => $headers, "rows" => $rows);
	}
}

if (!function_exists("rkrk_tr_collect_emails")) {
	/**
	 * E-Mail-Adressen der sichtbaren Listenpersonen, ohne Duplikate.
	 *
	 * @param list<array<string,mixed>> $visible
	 * @return list<string>
	 */
	function rkrk_tr_collect_emails(array $visible): array {
		$out = array();
		foreach (rkrk_tr_personel_lookup($visible) as $src) {
			$em = trim((string) ($src["email"] ?? ($src["e-mail"] ?? ($src["Email"] ?? ""))));
			if ($em === "" || strpos($em, "@") === false) {
				continue;
			}
			$key = strtolower($em);
			if (!isset($out[$key])) {
				$out[$key] = $em;
			}
		}
		return array_values($out);
	}
}

if (!function_exists("rkrk_tr_mailto_href")) {
	/** @param list<array<string,mixed>> $visible */
	function rkrk_tr_mailto_href(array $visible, string $listName): string {
		$emails = rkrk_tr_collect_emails($visible);
		if ($emails === array()) {
			return "";
		}
		$subj = $listName !== "" ? $listName : "Teilnehmerliste";
		$bodyLines = array("Teilnehmer " . $subj . ":");
		foreach ($visible as $r) {
			if (!is_array($r)) {
				continue;
			}
			$nm = trim((string) ($r["name"] ?? ""));
			if ($nm !== "") {
				$bodyLines[] = "- " . $nm;
			}
		}
		$q = array(
			"bcc" => implode(",", $emails),
			"subject" => $subj,
			"body" => implode("\n", $bodyLines),
		);
		return "mailto:?" . http_build_query($q, "", "&", PHP_QUERY_RFC3986);
	}
}

if (!function_exists("rkrk_tr_pdf_html")) {
	/**
	 * @param list<array<string,mixed>> $visible
	 * @param list<string> $codes
	 */
	function rkrk_tr_pdf_html(array $visible, array $codes, string $listName, bool $withRemark = false): string {
		$h = function_exists("rkrk_es_h") ? "rkrk_es_h" : "htmlspecialchars";
		$title = "Teilnehmerliste";
		if ($listName !== "") {
			$title .= " — " . $listName;
		}
		$n = count($visible);
		$nOk = 0;
		$nFail = 0;
		$nOpen = 0;
		foreach ($visible as $r) {
			$st = (string) ($r["status"] ?? "");
			if ($st === "ok") {
				$nOk++;
			} elseif ($st === "fail") {
				$nFail++;
			} else {
				$nOpen++;
			}
		}
		$html = '<style>
h1{font-size:16pt;color:#003366;margin:0 0 4pt;}
.meta{font-size:9pt;color:#475569;margin:0 0 8pt;}
table{border-collapse:collapse;width:100%;font-size:8pt;}
th{background-color:#003366;color:#ffffff;font-weight:bold;text-align:left;padding:3pt 4pt;}
td{border:0.3pt solid #cbd5e1;padding:3pt 4pt;}
.c{text-align:center;}
</style>';
		$html .= "<h1>" . $h($title) . "</h1>";
		$html .= '<p class="meta">' . $h(date("d.m.Y H:i")) . " · " . (int) $n . " Personen · " . (int) $nOk . " vollständig · offen " . (int) $nOpen . " · nicht bestanden " . (int) $nFail . "</p>";
		$html .= "<table cellpadding=\"3\" cellspacing=\"0\"><thead><tr><th>Teilnehmer</th>";
		foreach ($codes as $c) {
			$html .= "<th class=\"c\">" . $h((string) $c) . "</th>";
		}
		$html .= "<th class=\"c\">Gesamt</th>";
		$html .= "<th class=\"c\">Aktiv</th>";
		$html .= "<th>Bemerkung</th>";
		$html .= "</tr></thead><tbody>";
		foreach ($visible as $r) {
			if (!is_array($r)) {
				continue;
			}
			$st = (string) ($r["status"] ?? "");
			$id = (string) (($r["rkrs_id"] ?? "") !== "" ? $r["rkrs_id"] : ($r["pilot_id"] ?? ""));
			$html .= "<tr>";
			$nameBg = !empty($r["disabled"]) ? ' bgcolor="#e2e8f0"' : "";
			$html .= "<td" . $nameBg . "><b>" . $h((string) ($r["name"] ?? "")) . "</b>";
			if ($id !== "") {
				$html .= " " . $h($id);
			}
			$html .= "</td>";
			foreach ($codes as $code) {
				$cell = isset($r["cells"][$code]) && is_array($r["cells"][$code]) ? $r["cells"][$code] : array();
				$hit = isset($cell["hit"]) && is_array($cell["hit"]) ? $cell["hit"] : null;
				$cst = (string) ($cell["status"] ?? "open");
				$cbg = "#fff8e8";
				if ($cst === "ok") {
					$cbg = "#d9f2c9";
				} elseif ($cst === "fail") {
					$cbg = "#fde8e8";
				} elseif ($cst === "progress") {
					$cbg = "#ffedd5";
				}
				$html .= '<td class="c" bgcolor="' . $cbg . '">' . $h(rkrk_tr_cell_text($hit)) . "</td>";
			}
			$sumBg = $st === "ok" ? "#d9f2c9" : ($st === "fail" ? "#fde8e8" : "#fff8e8");
			if (!empty($r["disabled"])) {
				$sumBg = "#e2e8f0";
			}
			$html .= '<td class="c" bgcolor="' . $sumBg . '">' . $h(rkrk_tr_overall_text($r)) . "</td>";
			$html .= '<td class="c">' . (!empty($r["disabled"]) ? "Nein" : "Ja") . "</td>";
			$html .= "<td>" . $h((string) ($r["remark"] ?? "")) . "</td>";
			$html .= "</tr>";
		}
		if ($visible === array()) {
			$colspan = 4 + count($codes);
			$html .= '<tr><td colspan="' . (int) $colspan . '">Keine Teilnehmer in dieser Liste.</td></tr>';
		}
		$html .= "</tbody></table>";
		return $html;
	}
}

if (!function_exists("rkrk_tr_render_pdf")) {
	function rkrk_tr_render_pdf(string $html, string $title): string {
		$inc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_report_pdf.inc.php";
		if (is_file($inc)) {
			include_once $inc;
		}
		if (function_exists("rkrk_assess_try_load_tcpdf")) {
			rkrk_assess_try_load_tcpdf();
		}
		if (!class_exists("TCPDF", false)) {
			return "";
		}
		$prevDisplay = ini_get("display_errors");
		ini_set("display_errors", "0");
		$obLevel = ob_get_level();
		ob_start();
		$out = "";
		try {
			$pdf = new \TCPDF("L", "mm", "A4", true, "UTF-8", false);
			$pdf->SetCreator("RKRK Teilnehmerliste");
			$pdf->SetAuthor("RKRK");
			$pdf->SetTitle($title !== "" ? $title : "Teilnehmerliste");
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			$pdf->SetMargins(8, 8, 8);
			$pdf->SetAutoPageBreak(true, 10);
			$pdf->AddPage();
			$font = function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font")
				? rkrk_assess_report_tcpdf_ensure_unicode_font($pdf)
				: "helvetica";
			if ($font !== "dejavusans" && function_exists("rkrk_assess_report_pdf_ascii_symbol_fallback")) {
				$html = rkrk_assess_report_pdf_ascii_symbol_fallback($html);
			}
			$pdf->SetFont($font, "", 9);
			$pdf->writeHTML($html, true, false, true, false, "");
			$rendered = $pdf->Output("", "S");
			$out = is_string($rendered) ? $rendered : "";
		} catch (\Throwable $e) {
			$out = "";
		}
		while (ob_get_level() > $obLevel) {
			@ob_end_clean();
		}
		if ($prevDisplay !== false) {
			ini_set("display_errors", (string) $prevDisplay);
		}
		return $out;
	}
}

if (!function_exists("rkrk_tr_send_download")) {
	function rkrk_tr_send_download(string $filename, string $mime, string $bytes): void {
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		$filename = str_replace(array("\"", "\r", "\n"), "", $filename);
		header("Content-Type: " . $mime);
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
		header("Content-Length: " . (string) strlen($bytes));
		header("Cache-Control: private, max-age=0");
		echo $bytes;
		exit;
	}
}

if (!function_exists("rkrk_tr_export")) {
	/**
	 * @param list<array<string,mixed>> $visible
	 * @param list<string> $codes
	 * @param array<string,mixed>|null $active
	 */
	function rkrk_tr_export(string $kind, ?array $active, array $visible, array $codes): bool {
		$listName = is_array($active) ? trim((string) ($active["name"] ?? "")) : "";
		$withRemark = true;
		$kind = strtolower(trim($kind));
		if ($kind === "pdf") {
			$html = rkrk_tr_pdf_html($visible, $codes, $listName, $withRemark);
			$bin = rkrk_tr_render_pdf($html, $listName !== "" ? ("Teilnehmerliste — " . $listName) : "Teilnehmerliste");
			if ($bin === "" || strncmp($bin, "%PDF", 4) !== 0) {
				return false;
			}
			rkrk_tr_send_download(rkrk_tr_export_stem($listName, "uebersicht") . ".pdf", "application/pdf", $bin);
			return true;
		}
		if ($kind === "xlsx" || $kind === "excel") {
			$sheets = array(rkrk_tr_status_sheet($visible, $codes, $listName !== "" ? $listName : "Uebersicht", $withRemark));
			$bin = rkrk_tr_xlsx_bytes($sheets);
			if ($bin === "") {
				return false;
			}
			rkrk_tr_send_download(
				rkrk_tr_export_stem($listName, "uebersicht") . ".xlsx",
				"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
				$bin
			);
			return true;
		}
		if ($kind === "personel" || $kind === "personal") {
			$sheets = array(rkrk_tr_personel_sheet($visible, $listName));
			$bin = rkrk_tr_xlsx_bytes($sheets);
			if ($bin === "") {
				return false;
			}
			rkrk_tr_send_download(
				rkrk_tr_export_stem($listName, "personel") . ".xlsx",
				"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
				$bin
			);
			return true;
		}
		return false;
	}
}
