<?php
/**
 * Storyboard-Editor: anlegen, bearbeiten, speichern, löschen, kopieren, Druck/PDF.
 */
if (!function_exists("rkrk_assess_storyboard_template_point_options")) {
	/**
	 * Punktcodes + Kurzlabels aus Template für Auswahlfelder.
	 * @return list<array{code:string,label:string,sec:string}>
	 */
	function rkrk_assess_storyboard_template_point_options(string $templateFile): array {
		$templateFile = basename(trim($templateFile));
		if ($templateFile === "" || !function_exists("loadTemplate")) {
			return array();
		}
		$tpl = loadTemplate($templateFile);
		if (!is_array($tpl) || !isset($tpl["sections"]) || !is_array($tpl["sections"])) {
			return array();
		}
		$out = array();
		foreach ($tpl["sections"] as $secName => $fields) {
			if (!is_array($fields)) {
				continue;
			}
			foreach ($fields as $label => $meta) {
				if (!is_string($label) || preg_match('/\bBemerkung\b/ui', $label)) {
					continue;
				}
				if (function_exists("rkrk_assess_storyboard_is_score_field") && !rkrk_assess_storyboard_is_score_field($meta)) {
					continue;
				}
				$code = function_exists("rkrk_assess_storyboard_field_code")
					? rkrk_assess_storyboard_field_code($label)
					: "";
				if ($code === "") {
					continue;
				}
				$out[] = array(
					"code" => strtoupper($code),
					"label" => $label,
					"sec" => (string) $secName,
				);
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_storyboard_lines_to_list")) {
	/** @return list<string> */
	function rkrk_assess_storyboard_lines_to_list(string $raw): array {
		$out = array();
		foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
			$line = trim((string) $line);
			if ($line !== "") {
				$out[] = $line;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_storyboard_codes_from_text")) {
	/** @return list<string> */
	function rkrk_assess_storyboard_codes_from_text(string $raw): array {
		$out = array();
		foreach (preg_split('/[\s,;|]+/', $raw) as $part) {
			$part = trim((string) $part);
			if ($part === "") {
				continue;
			}
			$code = function_exists("rkrk_assess_storyboard_normalize_code")
				? rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($part) ?: $part)
				: strtoupper($part);
			if ($code !== "") {
				$out[] = $code;
			}
		}
		return array_values(array_unique($out));
	}
}

if (!function_exists("rkrk_assess_storyboard_parse_editor_post")) {
	/**
	 * @param array<string,mixed> $post
	 * @return array<string,mixed>|null
	 */
	function rkrk_assess_storyboard_parse_editor_post(array $post): ?array {
		$id = function_exists("rkrk_assess_storyboard_normalize_id")
			? rkrk_assess_storyboard_normalize_id((string) ($post["id"] ?? ""))
			: preg_replace('/[^A-Za-z0-9_-]+/', "_", (string) ($post["id"] ?? ""));
		$title = trim((string) ($post["title"] ?? ""));
		$template = basename(trim((string) ($post["template"] ?? "")));
		if ($id === "" || $title === "" || $template === "") {
			return null;
		}

		$stepsIn = isset($post["steps"]) && is_array($post["steps"]) ? $post["steps"] : array();
		$steps = array();
		foreach ($stepsIn as $si => $step) {
			if (!is_array($step)) {
				continue;
			}
			$stitle = trim((string) ($step["title"] ?? ""));
			if ($stitle === "") {
				continue;
			}
			$sid = trim((string) ($step["id"] ?? ""));
			if ($sid === "") {
				$sid = "phase_" . ((int) $si + 1);
			}
			$sid = preg_replace('/[^A-Za-z0-9_-]+/', "_", $sid);

			$items = array();
			$itemsIn = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			$ord = 1.0;
			foreach ($itemsIn as $item) {
				if (!is_array($item)) {
					continue;
				}
				$field = trim((string) ($item["field"] ?? ""));
				if ($field === "") {
					continue;
				}
				$row = array(
					"field" => $field,
					"cue" => trim((string) ($item["cue"] ?? "")),
				);
				$info = trim((string) ($item["info"] ?? ""));
				if ($info !== "") {
					$row["info"] = $info;
				}
				$orderRaw = trim((string) ($item["order"] ?? ""));
				if ($orderRaw !== "" && is_numeric($orderRaw)) {
					$row["order"] = (float) $orderRaw;
				} else {
					$row["order"] = $ord;
				}
				$ord += 1.0;
				$inject = rkrk_assess_storyboard_codes_from_text((string) ($item["inject"] ?? ""));
				if ($inject !== array()) {
					$row["inject"] = $inject;
				}
				$items[] = $row;
			}

			$steps[] = array(
				"id" => $sid,
				"title" => $stitle,
				"duration_min" => (int) ($step["duration_min"] ?? 0),
				"mode" => trim((string) ($step["mode"] ?? "score")) !== "" ? trim((string) ($step["mode"] ?? "score")) : "score",
				"prep" => trim((string) ($step["prep"] ?? "")),
				"items" => $items,
				"continuous" => rkrk_assess_storyboard_codes_from_text((string) ($step["continuous"] ?? "")),
			);
		}

		$data = array(
			"id" => $id,
			"title" => $title,
			"version" => trim((string) ($post["version"] ?? date("Y-m-d"))),
			"template" => $template,
			"duration_min" => (int) ($post["duration_min"] ?? 0),
			"description" => trim((string) ($post["description"] ?? "")),
			"source" => trim((string) ($post["source"] ?? "")),
			"field_prep" => rkrk_assess_storyboard_lines_to_list((string) ($post["field_prep"] ?? "")),
			"pilot_prep" => rkrk_assess_storyboard_lines_to_list((string) ($post["pilot_prep"] ?? "")),
			"intro" => trim((string) ($post["intro"] ?? "")),
			"steps" => $steps,
			"nb_fields" => rkrk_assess_storyboard_codes_from_text((string) ($post["nb_fields"] ?? "")),
		);
		return $data;
	}
}

if (!function_exists("rkrk_assess_storyboard_delete")) {
	function rkrk_assess_storyboard_delete(string $id): bool {
		$id = rkrk_assess_storyboard_normalize_id($id);
		if ($id === "") {
			return false;
		}
		$dir = rkrk_assess_storyboards_dir();
		if ($dir === "") {
			return false;
		}
		$path = $dir . $id . ".json";
		if (!is_file($path)) {
			return false;
		}
		return @unlink($path);
	}
}

if (!function_exists("rkrk_assess_storyboard_copy")) {
	function rkrk_assess_storyboard_copy(string $id): ?string {
		$src = rkrk_assess_storyboard_load($id);
		if (!is_array($src)) {
			return null;
		}
		$base = rkrk_assess_storyboard_normalize_id((string) ($src["id"] ?? $id));
		$newId = $base . "_copy";
		$n = 2;
		while (rkrk_assess_storyboard_load($newId) !== null) {
			$newId = $base . "_copy" . $n;
			$n++;
			if ($n > 50) {
				return null;
			}
		}
		$src["id"] = $newId;
		$src["title"] = trim((string) ($src["title"] ?? $newId)) . " (Kopie)";
		unset($src["_file"]);
		if (!rkrk_assess_storyboard_save($src)) {
			return null;
		}
		return $newId;
	}
}

if (!function_exists("rkrk_assess_storyboard_build_print_html")) {
	/**
	 * Storyboard-Druck/PDF — gleicher Assess-Print-Stil wie Abschlussbericht/Papierbogen.
	 * @param array<string,mixed> $story
	 */
	function rkrk_assess_storyboard_build_print_html(array $story, bool $forPdf = false): string {
		$title = htmlspecialchars((string) ($story["title"] ?? $story["id"] ?? "Storyboard"), ENT_QUOTES, "UTF-8");
		$id = htmlspecialchars((string) ($story["id"] ?? ""), ENT_QUOTES, "UTF-8");
		$ver = htmlspecialchars((string) ($story["version"] ?? ""), ENT_QUOTES, "UTF-8");
		$tplFile = basename(trim((string) ($story["template"] ?? "")));
		$tplShow = htmlspecialchars($tplFile, ENT_QUOTES, "UTF-8");
		$tplTitle = "";
		if ($tplFile !== "" && function_exists("loadTemplate")) {
			$t = loadTemplate($tplFile);
			if (is_array($t) && !empty($t["title"])) {
				$tplTitle = trim((string) $t["title"]);
			}
		}
		$dur = (int) ($story["duration_min"] ?? 0);
		$intro = nl2br(htmlspecialchars((string) ($story["intro"] ?? ""), ENT_QUOTES, "UTF-8"));
		$desc = nl2br(htmlspecialchars((string) ($story["description"] ?? ""), ENT_QUOTES, "UTF-8"));
		$src = htmlspecialchars((string) ($story["source"] ?? ""), ENT_QUOTES, "UTF-8");

		$logoSrc = "";
		if (function_exists("rkrk_assess_report_logo_src_html")) {
			$logoSrc = trim((string) rkrk_assess_report_logo_src_html());
		}
		if ($logoSrc === "" && isset($GLOBALS["examIconLoc"], $GLOBALS["logopic"])) {
			$logoSrc = (string) $GLOBALS["examIconLoc"] . (string) $GLOBALS["logopic"];
		}
		$logoSrcEsc = htmlspecialchars($logoSrc, ENT_QUOTES, "UTF-8");

		// Keine CSS-Variablen / Gradients. Farben auch im Druck/PDF (TCPDF: bgcolor + print-color-adjust).
		$cssCore = "
@page{size:A4 portrait;margin:6mm 7mm;}
*{box-sizing:border-box;}
body.rkrk-assess-print,body.rkrk-storyboard-print,body.pf-report{
  font-family:dejavusans,Segoe UI,Arial,sans-serif;margin:0;padding:6mm;color:#111;font-size:9pt;line-height:1.15;background:#fff;
  -webkit-print-color-adjust:exact;print-color-adjust:exact;color-adjust:exact;
}
.sb-toolbar.no-print,.print-options.no-print{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px;padding:8px 10px;border:1px solid #d6dfcc;border-radius:8px;background:#f8fbf6;}
.sb-toolbar a,.sb-toolbar button,.print-options button,.print-options a{
  display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:1px solid #4F6A3C;border-radius:8px;
  background:#4F6A3C;color:#fff;font:inherit;font-size:12px;text-decoration:none;cursor:pointer;
}
.print-header-table,.pf-header,.print-header{width:100%;border-collapse:collapse;margin:0 0 8px;padding:0 0 6px;border-bottom:2px solid #4F6A3C;}
.print-header-table td{vertical-align:top;padding:0;}
.print-title{margin:0;font-size:13pt;line-height:1.2;font-weight:700;color:#111;}
.print-subtitle,.pf-sub{display:block;font-size:9pt;color:#475569;font-weight:400;margin-top:2px;}
.print-logo,.pf-logo{width:32%;text-align:right;padding:0;margin:0;}
.print-logo img,.pf-logo img{max-height:40px;height:40px;width:auto;display:block;margin:0 0 0 auto;}
.pf-sec,.section{margin:3px 0 2px;padding-top:0;border:none;}
.pf-sec>h2,.section h2,.sb-sec-title{margin:0 0 2px;font-size:9pt;color:#1e3a1e;font-weight:700;}
.pf-sec p,.section p{margin:0 0 2px;}
.pf-sec ul{margin:1px 0 0 1.1em;padding:0;}
.pf-sec li{margin:0;}
.sb-meta{font-size:7.5pt;color:#5d6f5a;font-weight:400;margin:0 0 2px;line-height:1.2;}
.sb-desc{margin:0 0 3px;font-size:8.5pt;color:#111;font-weight:400;}
body.rkrk-storyboard-print table{border-collapse:collapse;border:none;}
body.rkrk-storyboard-print td{border:none;}
.sb-phase{margin:8px 0 0;padding:0;border:none;}
.sb-phase-head{width:100%;border-collapse:collapse;border:none;margin:0;background-color:#eef5ec;}
.sb-phase-head td{border:none;padding:1px 5px;background-color:#eef5ec;line-height:1.2;}
.sb-phase-title{margin:0;font-size:8.5pt;font-weight:700;color:#48673A;}
.sb-phase-dur{font-weight:400;color:#5d6f5a;font-size:8pt;}
.sb-phase-prep,.sb-cont{margin:0;font-size:7.5pt;color:#475569;font-style:italic;}
.sb-items{width:100%;border-collapse:collapse;border:none;margin:0;padding:0;}
.sb-items td,body.rkrk-storyboard-print .sb-item{margin:0!important;padding:0!important;border:none!important;background:transparent!important;line-height:1.2;}
.sb-code,.live-point-code{font-weight:800;font-size:8pt;color:#48673A;}
.sb-point-title{font-weight:600;font-size:8.5pt;color:#111;}
body.rkrk-storyboard-print .sb-cue{display:inline;margin:0!important;padding:0!important;border:none!important;background:transparent!important;font-size:8pt;color:#334155;font-weight:400;line-height:1.2;}
.sb-info{margin:0;padding:0;border:none;font-size:7.5pt;color:#64748b;}
.sb-stoer,table.sb-stoer{margin:0;padding:0;border:none;background-color:#ffedd5;color:#9a3412;font-size:8pt;}
.sb-stoer td{background-color:#ffedd5;color:#9a3412;padding:0 4px;border:none;line-height:1.2;}
.sb-nb{margin:4px 0 0;padding:0;border:none;color:#64748b;font-size:7.5pt;}
.rkrk-report-copyright{margin-top:6px;padding-top:3px;border:none;font-size:7pt;color:#64748b;line-height:1.2;clear:both;}
@media print{
  body.rkrk-assess-print,body.rkrk-storyboard-print{padding:0;font-size:8.5pt;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .no-print{display:none!important;}
  .sb-phase,.sb-stoer{break-inside:avoid;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
}
";

		$themeLinks = "";
		if (!$forPdf && function_exists("rkrk_assess_resolve_web_path_from_script")) {
			$themeCss = htmlspecialchars(rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme.css"), ENT_QUOTES, "UTF-8");
			$themeLinks .= "<link rel=\"stylesheet\" href=\"" . $themeCss . "\">";
			if (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
				$rkrsCss = htmlspecialchars(rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme_rkrs.css"), ENT_QUOTES, "UTF-8");
				$themeLinks .= "<link rel=\"stylesheet\" href=\"" . $rkrsCss . "\">";
			}
		}

		$faviconLinks = function_exists("rkrk_assess_favicon_link_tags_html")
			? rkrk_assess_favicon_link_tags_html()
			: "";
		$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
		$html .= "<title>" . $title . "</title>" . $faviconLinks . $themeLinks;
		$html .= "<style id=\"rkrk-report-print-css\">" . $cssCore . "</style></head>";
		$html .= "<body class=\"rkrk-assess-print rkrk-storyboard-print rkrk-checklist-report pf-report\">";

		if (!$forPdf) {
			$pdfHref = htmlspecialchars(
				function_exists("rkrk_assess_append_setting_href")
					? rkrk_assess_append_setting_href("?mode=storyboardpdf&id=" . rawurlencode((string) ($story["id"] ?? "")))
					: ("?mode=storyboardpdf&id=" . rawurlencode((string) ($story["id"] ?? ""))),
				ENT_QUOTES,
				"UTF-8"
			);
			$backHref = htmlspecialchars(
				function_exists("rkrk_assess_append_setting_href")
					? rkrk_assess_append_setting_href("?mode=storyboards")
					: "?mode=storyboards",
				ENT_QUOTES,
				"UTF-8"
			);
			$html .= "<div class=\"sb-toolbar no-print print-options\">";
			$html .= "<button type=\"button\" onclick=\"window.print()\">Drucken</button>";
			$html .= "<a href=\"" . $pdfHref . "\">PDF herunterladen</a>";
			$html .= "<a href=\"" . $backHref . "\">Zur&uuml;ck</a>";
			$html .= "</div>";
		}

		$subParts = array("Storyboard");
		if ($tplTitle !== "") {
			$subParts[] = $tplTitle;
		} elseif ($tplShow !== "") {
			$subParts[] = $tplShow;
		}
		$subtitle = htmlspecialchars(implode(" · ", $subParts), ENT_QUOTES, "UTF-8");

		$html .= "<table class=\"print-header-table pf-header print-header\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
		$html .= "<td class=\"print-title\" valign=\"top\" width=\"68%\">";
		$html .= "<div style=\"font-size:13pt;font-weight:bold;line-height:1.2;color:#111;\">" . $title . "</div>";
		$html .= "<span class=\"print-subtitle pf-sub\" style=\"display:block;font-size:9pt;color:#475569;font-weight:normal;margin-top:2px;\">" . $subtitle . "</span>";
		$html .= "</td>";
		if ($logoSrcEsc !== "") {
			$html .= "<td class=\"print-logo pf-logo\" align=\"right\" valign=\"top\" width=\"32%\" style=\"padding:0;text-align:right;\">";
			$html .= "<img src=\"" . $logoSrcEsc . "\" alt=\"Logo\" height=\"40\" align=\"right\" style=\"height:40px;width:auto;display:block;margin-left:auto;margin-right:0;\">";
			$html .= "</td>";
		}
		$html .= "</tr></table>";

		$html .= "<div class=\"sb-meta meta\">ID: <strong>" . $id . "</strong>";
		if ($ver !== "") {
			$html .= " · Version " . $ver;
		}
		if ($dur > 0) {
			$html .= " · " . $dur . " Min";
		}
		if ($tplShow !== "") {
			$html .= "<br>Template: " . $tplShow;
		}
		if ($src !== "") {
			$html .= "<br>Quelle: " . $src;
		}
		$html .= "</div>";

		if ($desc !== "") {
			$html .= "<p class=\"sb-desc\">" . $desc . "</p>";
		}
		if ($intro !== "") {
			$html .= "<div class=\"pf-sec section\"><h2>Ausgangssituation / Intro</h2><p>" . $intro . "</p></div>";
		}

		$fieldPrep = isset($story["field_prep"]) && is_array($story["field_prep"]) ? $story["field_prep"] : array();
		if ($fieldPrep !== array()) {
			$html .= "<div class=\"pf-sec section\"><h2>Feld-Vorbereitung</h2><ul>";
			foreach ($fieldPrep as $pItem) {
				$html .= "<li>" . htmlspecialchars((string) $pItem, ENT_QUOTES, "UTF-8") . "</li>";
			}
			$html .= "</ul></div>";
		}
		$pilotPrep = isset($story["pilot_prep"]) && is_array($story["pilot_prep"]) ? $story["pilot_prep"] : array();
		if ($pilotPrep !== array()) {
			$html .= "<div class=\"pf-sec section\"><h2>Vorbereitung Pilot</h2><ul>";
			foreach ($pilotPrep as $pItem) {
				$html .= "<li>" . htmlspecialchars((string) $pItem, ENT_QUOTES, "UTF-8") . "</li>";
			}
			$html .= "</ul></div>";
		}

		$pointTitles = array();
		if ($tplFile !== "" && function_exists("rkrk_assess_storyboard_point_titles_map")) {
			$pointTitles = rkrk_assess_storyboard_point_titles_map($tplFile);
		} elseif ($tplFile !== "" && function_exists("rkrk_assess_storyboard_template_point_options")) {
			foreach (rkrk_assess_storyboard_template_point_options($tplFile) as $opt) {
				$code = function_exists("rkrk_assess_storyboard_normalize_code")
					? rkrk_assess_storyboard_normalize_code((string) ($opt["code"] ?? ""))
					: strtoupper(trim((string) ($opt["code"] ?? "")));
				$lab = trim((string) ($opt["label"] ?? ""));
				if ($code === "" || $lab === "") {
					continue;
				}
				$titleTxt = function_exists("rkrk_assess_storyboard_point_title_from_label")
					? rkrk_assess_storyboard_point_title_from_label($lab)
					: $lab;
				if ($titleTxt !== "") {
					$pointTitles[$code] = $titleTxt;
				}
			}
		}

		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		$html .= "<div class=\"pf-sec section\"><h2 class=\"sb-sec-title\">Ablauf</h2>";
		foreach ($steps as $step) {
			if (!is_array($step)) {
				continue;
			}
			$st = htmlspecialchars((string) ($step["title"] ?? ""), ENT_QUOTES, "UTF-8");
			$sd = (int) ($step["duration_min"] ?? 0);
			$html .= "<div class=\"sb-phase\">";
			$html .= "<table class=\"sb-phase-head\" width=\"100%\" cellpadding=\"1\" cellspacing=\"0\" border=\"0\" bgcolor=\"#eef5ec\"><tr>";
			$html .= "<td bgcolor=\"#eef5ec\" style=\"border:none;padding:1px 5px;background-color:#eef5ec;\">";
			$html .= "<font color=\"#48673A\"><span class=\"sb-phase-title\">" . $st;
			if ($sd > 0) {
				$html .= " <span class=\"sb-phase-dur\">(" . $sd . " Min)</span>";
			}
			$html .= "</span></font></td></tr></table>";
			$prep = trim((string) ($step["prep"] ?? ""));
			if ($prep !== "") {
				$html .= "<div class=\"sb-phase-prep\">" . htmlspecialchars($prep, ENT_QUOTES, "UTF-8") . "</div>";
			}
			$cont = isset($step["continuous"]) && is_array($step["continuous"]) ? $step["continuous"] : array();
			if ($cont !== array()) {
				$html .= "<div class=\"sb-cont\"><strong>Durchgehend:</strong> " . htmlspecialchars(implode(", ", array_map("strval", $cont)), ENT_QUOTES, "UTF-8") . "</div>";
			}
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			$html .= "<table class=\"sb-items\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
			foreach ($items as $item) {
				$html .= "<tr><td class=\"sb-item\" valign=\"top\" style=\"padding:0;margin:0;line-height:1.2;border:none;\">";
				if (!is_array($item)) {
					$html .= htmlspecialchars((string) $item, ENT_QUOTES, "UTF-8");
					$html .= "</td></tr>";
					continue;
				}
				$fieldRaw = trim((string) ($item["field"] ?? ""));
				$fieldCode = function_exists("rkrk_assess_storyboard_normalize_code")
					? rkrk_assess_storyboard_normalize_code(
						function_exists("rkrk_assess_storyboard_field_code")
							? (rkrk_assess_storyboard_field_code($fieldRaw) ?: $fieldRaw)
							: $fieldRaw
					)
					: strtoupper($fieldRaw);
				$fieldDisp = htmlspecialchars($fieldRaw !== "" ? $fieldRaw : $fieldCode, ENT_QUOTES, "UTF-8");
				$pointTitle = ($fieldCode !== "" && isset($pointTitles[$fieldCode])) ? (string) $pointTitles[$fieldCode] : "";
				$cue = trim((string) ($item["cue"] ?? ""));
				$info = trim((string) ($item["info"] ?? ""));
				$hasInject = isset($item["inject"]) && is_array($item["inject"]) && $item["inject"] !== array();
				$html .= "<font color=\"#48673A\"><b class=\"sb-code\">" . $fieldDisp . "</b></font>";
				if ($pointTitle !== "") {
					$html .= " <span class=\"sb-point-title\">" . htmlspecialchars($pointTitle, ENT_QUOTES, "UTF-8") . "</span>";
				}
				if ($cue !== "") {
					$cueIsStoer = (stripos($cue, "Störung") === 0 || stripos($cue, "Stoerung") === 0);
					if ($cueIsStoer) {
						$html .= " <span class=\"sb-stoer\" style=\"background-color:#ffedd5;color:#9a3412;\">"
							. htmlspecialchars($cue, ENT_QUOTES, "UTF-8") . "</span>";
					} else {
						$html .= " <span class=\"sb-cue\">— " . htmlspecialchars($cue, ENT_QUOTES, "UTF-8") . "</span>";
					}
				}
				if ($info !== "") {
					$html .= " <span class=\"sb-info\">" . htmlspecialchars($info, ENT_QUOTES, "UTF-8") . "</span>";
				}
				if ($hasInject) {
					$html .= " <span class=\"sb-stoer\" style=\"background-color:#ffedd5;color:#9a3412;padding:0 4px;\"><strong>St&ouml;rung:</strong> "
						. htmlspecialchars(implode(", ", array_map("strval", $item["inject"])), ENT_QUOTES, "UTF-8") . "</span>";
				}
				$html .= "</td></tr>";
			}
			$html .= "</table>";
			$html .= "</div>";
		}
		$html .= "</div>";

		$nb = isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? $story["nb_fields"] : array();
		if ($nb !== array()) {
			$html .= "<div class=\"sb-nb\"><strong>n.b.:</strong> "
				. htmlspecialchars(implode(", ", array_map("strval", $nb)), ENT_QUOTES, "UTF-8") . "</div>";
		}

		if (function_exists("rkrk_assess_report_copyright_footer_html")) {
			$html .= rkrk_assess_report_copyright_footer_html();
		}

		$html .= "</body></html>";
		return $html;
	}
}
if (!function_exists("rkrk_assess_storyboard_output_pdf")) {
	/** @param array<string,mixed> $story */
	function rkrk_assess_storyboard_output_pdf(array $story): bool {
		$prevDisplay = ini_get("display_errors");
		ini_set("display_errors", "0");

		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		ob_start();

		$htmlFull = rkrk_assess_storyboard_build_print_html($story, true);
		$stem = rkrk_assess_storyboard_normalize_id((string) ($story["id"] ?? "storyboard"));
		if ($stem === "") {
			$stem = "storyboard";
		}

		if (function_exists("rkrk_assess_report_append_copyright_footer")) {
			$htmlFull = rkrk_assess_report_append_copyright_footer($htmlFull);
		}

		$bin = "";

		// Wie Abschlussbericht: TCPDF bevorzugt (Assess-Print-CSS + Logo)
		if (function_exists("rkrk_assess_try_load_tcpdf") && rkrk_assess_try_load_tcpdf()) {
			try {
				$prepared = $htmlFull;
				if (function_exists("rkrk_assess_report_html_prepare_for_tcpdf")) {
					$prepared = rkrk_assess_report_html_prepare_for_tcpdf($htmlFull);
				}
				if (function_exists("rkrk_assess_report_render_pdf_tcpdf")) {
					$bin = rkrk_assess_report_render_pdf_tcpdf($prepared, $stem);
				}
			} catch (\Throwable $e) {
				$bin = "";
			}
		}

		if (($bin === "" || strncmp($bin, "%PDF", 4) !== 0)
			&& function_exists("rkrk_assess_try_load_dompdf")
			&& rkrk_assess_try_load_dompdf()) {
			try {
				$dompdf = new \Dompdf\Dompdf(array(
					"isRemoteEnabled" => true,
					"isHtml5ParserEnabled" => true,
				));
				$dompdf->loadHtml($htmlFull, "UTF-8");
				$dompdf->setPaper("A4", "portrait");
				$dompdf->render();
				$out = $dompdf->output();
				$bin = is_string($out) ? $out : "";
			} catch (\Throwable $e) {
				$bin = "";
			}
		}

		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		if ($prevDisplay !== false) {
			ini_set("display_errors", (string) $prevDisplay);
		}

		if ($bin === "" || strncmp($bin, "%PDF", 4) !== 0) {
			return false;
		}
		if (headers_sent()) {
			return false;
		}

		$fname = $stem . ".pdf";
		header("Content-Type: application/pdf");
		header("Content-Disposition: attachment; filename=\"" . $fname . "\"");
		header("Content-Length: " . strlen($bin));
		header("Cache-Control: private, max-age=0, must-revalidate");
		header("Pragma: public");
		echo $bin;
		exit;
	}
}

/* ---------- UI / Handlers (von rkrk_assess.php aufgerufen) ---------- */

if (!function_exists("rkrk_assess_storyboard_h")) {
	function rkrk_assess_storyboard_h(string $s): string {
		return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists("rkrk_assess_storyboard_ico")) {
	function rkrk_assess_storyboard_ico(string $name): string {
		$svgs = array(
			"grip" => '<svg class="sb-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="1.6" fill="currentColor"/><circle cx="15" cy="7" r="1.6" fill="currentColor"/><circle cx="9" cy="12" r="1.6" fill="currentColor"/><circle cx="15" cy="12" r="1.6" fill="currentColor"/><circle cx="9" cy="17" r="1.6" fill="currentColor"/><circle cx="15" cy="17" r="1.6" fill="currentColor"/></svg>',
			"trash" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
			"plus" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
			"insert" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/><path d="M4 19h16"/></svg>',
			"save" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
			"print" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
			"pdf" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
			"play" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>',
			"back" => '<svg class="sb-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
		);
		return isset($svgs[$name]) ? $svgs[$name] : "";
	}
}

if (!function_exists("rkrk_assess_storyboard_editor_styles")) {
	function rkrk_assess_storyboard_editor_styles(): void {
		echo "<style>
.sb-editor .text-input,.sb-editor textarea,.sb-editor select{width:100%;box-sizing:border-box;}
.sb-editor label{display:block;font-size:12px;color:#5f6b66;margin:0 0 4px;font-weight:600;}
.sb-editor .grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.sb-editor .grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.sb-editor .step-card{border:1px solid #c5d4c0;border-radius:10px;padding:12px;margin:12px 0;background:#f8faf7;}
.sb-editor .item-block{border:1px solid #d5dfd0;border-radius:10px;padding:10px;margin:0;background:#fff;position:relative;transition:box-shadow .12s ease,outline-color .12s ease;}
.sb-editor .item-block.sb-dragging{opacity:.5;border-style:dashed;box-shadow:none;}
.sb-editor .item-block.sb-drag-over{outline:2px solid #48673a;outline-offset:2px;}
.sb-editor .item-row{display:grid;grid-template-columns:36px minmax(160px,2fr) 64px minmax(120px,1.2fr) 36px 36px;gap:6px;align-items:end;margin:0 0 8px;}
.sb-editor .sb-drag{display:inline-flex;align-items:center;justify-content:center;gap:2px;min-width:36px;height:36px;color:#2d4024;cursor:grab;user-select:none;border:1px solid #9ab08f;border-radius:8px;background:#e7f0e4;padding:0 4px;touch-action:none;}
.sb-editor .sb-drag:hover{background:#d7e8d2;border-color:#48673a;}
.sb-editor .sb-drag:active,.sb-editor .item-block.sb-dragging .sb-drag{cursor:grabbing;background:#48673a;color:#fff;border-color:#48673a;}
.sb-editor .sb-insert-gap{display:flex;align-items:center;justify-content:center;min-height:28px;margin:4px 0;position:relative;}
.sb-editor .sb-insert-gap::before{content:'';position:absolute;left:8px;right:8px;top:50%;border-top:1px dashed #b7c9b2;}
.sb-editor .sb-insert-gap:hover::before,.sb-editor .sb-insert-gap.sb-drag-over::before{border-top-color:#48673a;border-top-width:2px;}
.sb-editor .sb-insert-gap .sb-insert-btn{position:relative;z-index:1;font-size:11px;font-weight:600;color:#48673a;background:#eef5ec;border:1px solid #9ab08f;border-radius:999px;padding:3px 12px;cursor:pointer;line-height:1.4;}
.sb-editor .sb-insert-gap .sb-insert-btn:hover,.sb-editor .sb-insert-gap.sb-drag-over .sb-insert-btn{background:#48673a;color:#fff;border-color:#48673a;}
.sb-editor .btn-ico{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;padding:0;border:1px solid #cfd8cc;border-radius:8px;background:#fff;color:#2d4024;cursor:pointer;text-decoration:none;}
.sb-editor .btn-ico:hover{background:#eef5ec;border-color:#9ab08f;}
.sb-editor .btn-ico.danger{color:#8b2e2e;border-color:#e8c8c8;}
.sb-editor .btn-ico.danger:hover{background:#fdf2f2;}
.sb-editor .btn-ico.primary{background:#48673a;color:#fff;border-color:#48673a;}
.sb-editor .sb-ico{width:17px;height:17px;display:block;flex-shrink:0;}
.sb-editor .sb-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;align-items:center;}
.sb-editor .muted{color:#64748b;font-size:13px;}
.sb-editor .sb-info-fold{margin-top:6px;border:1px solid #e2e8e0;border-radius:8px;background:#fbfdfb;overflow:hidden;}
.sb-editor .sb-info-fold>summary{cursor:pointer;list-style:none;padding:7px 10px;font-size:12px;font-weight:600;color:#5f6b66;user-select:none;background:#f4f7f3;}
.sb-editor .sb-info-fold>summary::-webkit-details-marker{display:none;}
.sb-editor .sb-info-fold>summary::before{content:'\25B8 ';color:#7a8f78;}
.sb-editor .sb-info-fold[open]>summary::before{content:'\25BE ';}
.sb-editor .sb-info-fold>summary .sb-info-hint{font-weight:500;color:#94a3b8;margin-left:4px;}
.sb-editor .sb-info-fold .sb-info-body{padding:8px 10px 10px;}
@media (max-width:900px){.sb-editor .grid2,.sb-editor .grid3{grid-template-columns:1fr;}.sb-editor .item-row{grid-template-columns:36px 1fr 36px 36px;}}
</style>\n";
	}
}

if (!function_exists("rkrk_assess_storyboard_field_select_html")) {
	/**
	 * @param list<array{code:string,label:string,sec?:string}> $opts
	 */
	function rkrk_assess_storyboard_field_select_html(string $name, string $selected, array $opts, string $extraClass = ""): string {
		$html = "<select name='" . rkrk_assess_storyboard_h($name) . "' class='text-input " . rkrk_assess_storyboard_h($extraClass) . "'>";
		$html .= "<option value=''>— Punkt —</option>";
		foreach ($opts as $o) {
			$code = (string) ($o["code"] ?? "");
			if ($code === "") {
				continue;
			}
			$lab = (string) ($o["label"] ?? $code);
			$short = $code;
			if (strlen($lab) > 42) {
				$lab = substr($lab, 0, 40) . "…";
			}
			$sel = (strcasecmp($selected, $code) === 0) ? " selected" : "";
			$html .= "<option value='" . rkrk_assess_storyboard_h($code) . "'" . $sel . ">" . rkrk_assess_storyboard_h($short . " — " . $lab) . "</option>";
		}
		$html .= "</select>";
		return $html;
	}
}

if (!function_exists("rkrk_assess_show_storyboard_editor")) {
	/**
	 * @param array<string,mixed> $story
	 * @param list<string> $templateFiles
	 */
	function rkrk_assess_show_storyboard_editor(array $story, array $templateFiles, bool $isNew = false): void {
		if (function_exists("pageHeaderLocal")) {
			pageHeaderLocal($isNew ? "Storyboard anlegen" : "Storyboard bearbeiten");
		}
		if (function_exists("rkrk_checklist_toolbar_echo_styles_once")) {
			rkrk_checklist_toolbar_echo_styles_once();
		}
		rkrk_assess_storyboard_editor_styles();

		$id = (string) ($story["id"] ?? "");
		$title = (string) ($story["title"] ?? "");
		$template = basename((string) ($story["template"] ?? ""));
		if ($template === "" && $templateFiles !== array()) {
			$template = basename($templateFiles[0]);
		}
		$opts = rkrk_assess_storyboard_template_point_options($template);
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		if ($steps === array()) {
			$steps = array(array(
				"id" => "phase_1",
				"title" => "Phase 1",
				"duration_min" => 10,
				"mode" => "score",
				"prep" => "",
				"items" => array(array("field" => "", "order" => 1, "cue" => "", "inject" => array())),
				"continuous" => array(),
			));
		}

		$back = function_exists("rkrk_assess_append_setting_href")
			? rkrk_assess_append_setting_href("?mode=storyboards")
			: "?mode=storyboards";
		$action = function_exists("rkrk_assess_append_setting_href")
			? rkrk_assess_append_setting_href("?mode=savestoryboard")
			: "?mode=savestoryboard";

		echo "<div class='rkrk-card sb-editor'>";
		echo "<form method='post' action='" . rkrk_assess_storyboard_h($action) . "' id='sbEditorForm'>";
		if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
			rkrk_assess_echo_setting_hidden_input();
		}
		echo "<input type='hidden' name='is_new' value='" . ($isNew ? "1" : "0") . "'>";
		echo "<input type='hidden' name='original_id' value='" . rkrk_assess_storyboard_h($id) . "'>";

		echo "<div class='grid2'>";
		echo "<div><label>ID (Dateiname)</label><input class='text-input' name='id' required pattern='[A-Za-z0-9_-]+' value='" . rkrk_assess_storyboard_h($id) . "'" . ($isNew ? "" : " readonly") . "></div>";
		echo "<div><label>Titel</label><input class='text-input' name='title' required value='" . rkrk_assess_storyboard_h($title) . "'></div>";
		echo "</div>";

		echo "<div class='grid3' style='margin-top:10px;'>";
		echo "<div><label>Template</label><select class='text-input' name='template' id='sbTemplate'>";
		foreach ($templateFiles as $tf) {
			$base = basename($tf);
			$sel = (strcasecmp($base, $template) === 0) ? " selected" : "";
			$tTitle = $base;
			if (function_exists("loadTemplate")) {
				$t = loadTemplate($base);
				if (is_array($t) && !empty($t["title"])) {
					$tTitle = (string) $t["title"] . " (" . $base . ")";
				}
			}
			echo "<option value='" . rkrk_assess_storyboard_h($base) . "'" . $sel . ">" . rkrk_assess_storyboard_h($tTitle) . "</option>";
		}
		echo "</select></div>";
		echo "<div><label>Version</label><input class='text-input' name='version' value='" . rkrk_assess_storyboard_h((string) ($story["version"] ?? date("Y-m-d"))) . "'></div>";
		echo "<div><label>Dauer (Min)</label><input class='text-input' type='number' min='0' name='duration_min' value='" . (int) ($story["duration_min"] ?? 0) . "'></div>";
		echo "</div>";

		echo "<div style='margin-top:10px;'><label>Beschreibung</label><textarea class='text-input' name='description' rows='2'>" . rkrk_assess_storyboard_h((string) ($story["description"] ?? "")) . "</textarea></div>";
		echo "<div style='margin-top:10px;'><label>Quelle</label><input class='text-input' name='source' value='" . rkrk_assess_storyboard_h((string) ($story["source"] ?? "")) . "'></div>";
		echo "<div style='margin-top:10px;'><label>Intro / Ausgangssituation</label><textarea class='text-input' name='intro' rows='3'>" . rkrk_assess_storyboard_h((string) ($story["intro"] ?? "")) . "</textarea></div>";

		$fp = isset($story["field_prep"]) && is_array($story["field_prep"]) ? implode("\n", array_map("strval", $story["field_prep"])) : "";
		$pp = isset($story["pilot_prep"]) && is_array($story["pilot_prep"]) ? implode("\n", array_map("strval", $story["pilot_prep"])) : "";
		echo "<div class='grid2' style='margin-top:10px;'>";
		echo "<div><label>Feld-Vorbereitung (eine Zeile pro Punkt)</label><textarea class='text-input' name='field_prep' rows='4'>" . rkrk_assess_storyboard_h($fp) . "</textarea></div>";
		echo "<div><label>Pilot-Vorbereitung (eine Zeile pro Punkt)</label><textarea class='text-input' name='pilot_prep' rows='4'>" . rkrk_assess_storyboard_h($pp) . "</textarea></div>";
		echo "</div>";

		$nb = isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? implode(", ", array_map("strval", $story["nb_fields"])) : "";
		echo "<div style='margin-top:10px;'><label>n.b.-Punkte (Codes, kommagetrennt)</label><input class='text-input' name='nb_fields' value='" . rkrk_assess_storyboard_h($nb) . "' placeholder='A.6, A.7, C.5'></div>";
		echo "<p class='muted'>Aktive Punkte kommen aus den Phasen-Items + Continuous. Nicht gelistete Score-Punkte werden beim Lauf automatisch n.b.</p>";

		echo "<h3 style='margin:18px 0 8px;'>Phasen / Ablauf</h3>";
		echo "<div id='sbSteps'>";

		foreach ($steps as $si => $step) {
			if (!is_array($step)) {
				continue;
			}
			$si = (int) $si;
			echo "<div class='step-card' data-step='" . $si . "'>";
			echo "<div class='grid3'>";
			echo "<div><label>Phase-ID</label><input class='text-input' name='steps[" . $si . "][id]' value='" . rkrk_assess_storyboard_h((string) ($step["id"] ?? ("phase_" . ($si + 1)))) . "'></div>";
			echo "<div><label>Titel</label><input class='text-input' name='steps[" . $si . "][title]' value='" . rkrk_assess_storyboard_h((string) ($step["title"] ?? "")) . "'></div>";
			echo "<div><label>Dauer Min</label><input class='text-input' type='number' min='0' name='steps[" . $si . "][duration_min]' value='" . (int) ($step["duration_min"] ?? 0) . "'></div>";
			echo "</div>";
			echo "<div class='grid2' style='margin-top:8px;'>";
			$mode = (string) ($step["mode"] ?? "score");
			echo "<div><label>Modus</label><select class='text-input' name='steps[" . $si . "][mode]'>";
			foreach (array("info", "score", "run") as $m) {
				$sel = ($mode === $m) ? " selected" : "";
				echo "<option value='" . $m . "'" . $sel . ">" . $m . "</option>";
			}
			echo "</select></div>";
			$cont = isset($step["continuous"]) && is_array($step["continuous"]) ? implode(", ", array_map("strval", $step["continuous"])) : "";
			echo "<div><label>Durchgehend (Codes)</label><input class='text-input' name='steps[" . $si . "][continuous]' value='" . rkrk_assess_storyboard_h($cont) . "' placeholder='C.1, C.2, C.8'></div>";
			echo "</div>";
			echo "<div style='margin-top:8px;'><label>Prep / Hinweis</label><input class='text-input' name='steps[" . $si . "][prep]' value='" . rkrk_assess_storyboard_h((string) ($step["prep"] ?? "")) . "'></div>";

			echo "<div class='sb-items' style='margin-top:10px;'>";
			echo "<div class='muted' style='margin-bottom:6px;'>Griff links = Drag&amp;Drop · Zwischenzeile = dort einf&uuml;gen · + unten = anhaengen</div>";
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			if ($items === array()) {
				$items = array(array("field" => "", "order" => 1, "cue" => "", "info" => ""));
			}
			foreach ($items as $ii => $item) {
				if (!is_array($item)) {
					continue;
				}
				$ii = (int) $ii;
				$field = (string) ($item["field"] ?? "");
				$inj = isset($item["inject"]) && is_array($item["inject"]) ? implode(", ", array_map("strval", $item["inject"])) : "";
				$ord = isset($item["order"]) ? (string) $item["order"] : (string) ($ii + 1);
				$infoVal = (string) ($item["info"] ?? "");
				$infoOpen = trim($infoVal) !== "" ? " open" : "";
				echo "<div class='sb-insert-gap' data-step='" . $si . "'><button type='button' class='sb-insert-btn' onclick='sbInsertAt(this," . $si . ")'>+ Hier einf&uuml;gen</button></div>";
				echo "<div class='item-block' data-step='" . $si . "'>";
				echo "<div class='item-row'>";
				echo "<span class='sb-drag' title='Ziehen zum Verschieben' aria-label='Verschieben' role='button' tabindex='0'>" . rkrk_assess_storyboard_ico("grip") . "</span>";
				echo rkrk_assess_storyboard_field_select_html("steps[" . $si . "][items][" . $ii . "][field]", $field, $opts, "sb-field");
				echo "<input class='text-input sb-ord' name='steps[" . $si . "][items][" . $ii . "][order]' value='" . rkrk_assess_storyboard_h($ord) . "' placeholder='Ord' title='Reihenfolge'>";
				echo "<input class='text-input' name='steps[" . $si . "][items][" . $ii . "][inject]' value='" . rkrk_assess_storyboard_h($inj) . "' placeholder='D.3' title='St&ouml;rung'>";
				echo "<button type='button' class='btn-ico danger' onclick='sbRemoveItem(this)' title='Entfernen' aria-label='Entfernen'>" . rkrk_assess_storyboard_ico("trash") . "</button>";
				echo "</div>";
				echo "<div><label>Frage / Beschreibung</label>";
				echo "<input class='text-input' name='steps[" . $si . "][items][" . $ii . "][cue]' value='" . rkrk_assess_storyboard_h((string) ($item["cue"] ?? "")) . "' placeholder='Kontrollfrage oder kurze Beschreibung'></div>";
				echo "<details class='sb-info-fold'" . $infoOpen . "><summary>Zusatzinformation";
				if (trim($infoVal) === "") { echo " <span class='sb-info-hint'>(aufklappen)</span>"; }
				echo "</summary><div class='sb-info-body'><textarea class='text-input' name='steps[" . $si . "][items][" . $ii . "][info]' rows='2' placeholder='Hinweise für Instruktoren, Sollwerte, Ablaufdetails…'>" . rkrk_assess_storyboard_h($infoVal) . "</textarea></div></details>";
				echo "</div>";
			}
			echo "</div>";
			echo "<div class='sb-actions'>";
			echo "<button type='button' class='btn-ico' onclick='sbAddItem(this," . $si . ")' title='Anhaengen' aria-label='Anhaengen'>" . rkrk_assess_storyboard_ico("plus") . "</button>";
			echo "<button type='button' class='btn-ico danger' onclick='this.closest(\".step-card\").remove()' title='Phase entfernen' aria-label='Phase entfernen'>" . rkrk_assess_storyboard_ico("trash") . "</button>";
			echo "</div>";
			echo "</div>";
		}
		echo "</div>";

		echo "<div class='sb-actions' style='margin:12px 0;'>";
		echo "<button type='button' class='btn-ico' onclick='sbAddStep()' title='Phase' aria-label='Phase'>" . rkrk_assess_storyboard_ico("plus") . "</button>";
		echo "<button type='submit' class='btn-ico primary' title='Speichern' aria-label='Speichern'>" . rkrk_assess_storyboard_ico("save") . "</button>";
		echo "<a class='btn-ico' href='" . rkrk_assess_storyboard_h($back) . "' title='Abbrechen' aria-label='Abbrechen'>" . rkrk_assess_storyboard_ico("back") . "</a>";
		if ($id !== "" && !$isNew) {
			$printHref = function_exists("rkrk_assess_append_setting_href")
				? rkrk_assess_append_setting_href("?mode=storyboardprint&id=" . rawurlencode($id))
				: ("?mode=storyboardprint&id=" . rawurlencode($id));
			$pdfHref = function_exists("rkrk_assess_append_setting_href")
				? rkrk_assess_append_setting_href("?mode=storyboardpdf&id=" . rawurlencode($id))
				: ("?mode=storyboardpdf&id=" . rawurlencode($id));
			$startHref = function_exists("rkrk_assess_storyboard_form_href") ? rkrk_assess_storyboard_form_href($story) : "";
			echo "<a class='btn-ico' target='_blank' rel='noopener' href='" . rkrk_assess_storyboard_h($printHref) . "' title='Drucken' aria-label='Drucken'>" . rkrk_assess_storyboard_ico("print") . "</a>";
			echo "<a class='btn-ico' href='" . rkrk_assess_storyboard_h($pdfHref) . "' title='PDF' aria-label='PDF'>" . rkrk_assess_storyboard_ico("pdf") . "</a>";
			if ($startHref !== "") {
				echo "<a class='btn-ico primary' href='" . rkrk_assess_storyboard_h($startHref) . "' title='Start' aria-label='Start'>" . rkrk_assess_storyboard_ico("play") . "</a>";
			}
		}
		echo "</div>";
		echo "</form></div>";

		// Template for JS option list
		$optJson = json_encode($opts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
				echo "<script>\n";
		echo "window.SB_POINT_OPTS = " . ($optJson !== false ? $optJson : "[]") . ";\n";
		$__sbIcos = array(
			"grip" => rkrk_assess_storyboard_ico("grip"),
			"trash" => rkrk_assess_storyboard_ico("trash"),
			"plus" => rkrk_assess_storyboard_ico("plus"),
		);
		echo "window.SB_ICOS = " . json_encode($__sbIcos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";\n";
		echo <<<'JS'
function sbIco(n){return (window.SB_ICOS&&window.SB_ICOS[n])||'';}
function sbFieldSelect(name, selected){
  let h="<select name='"+name+"' class='text-input sb-field'><option value=''>— Punkt —</option>";
  (window.SB_POINT_OPTS||[]).forEach(function(o){
    const c=String(o.code||''); if(!c) return;
    let lab=String(o.label||c); if(lab.length>42) lab=lab.slice(0,40)+'…';
    const sel=(selected&&String(selected).toUpperCase()===c.toUpperCase())?' selected':'';
    h+="<option value='"+c.replace(/"/g,'&quot;')+"'"+sel+">"+c+' — '+lab.replace(/</g,'&lt;')+"</option>";
  });
  return h+"</select>";
}
function sbStepIdx(el){const card=el&&el.closest?el.closest('.step-card'):null;return card?(parseInt(card.getAttribute('data-step')||'0',10)||0):0;}
function sbItemsBox(el){const card=el&&el.closest?el.closest('.step-card'):null;return card?card.querySelector('.sb-items'):null;}
function sbClearDragOver(){document.querySelectorAll('.sb-drag-over').forEach(function(n){n.classList.remove('sb-drag-over');});}
function sbReindexItems(box){
  if(!box) return;
  const step=sbStepIdx(box);
  box.querySelectorAll('.item-block').forEach(function(block,i){
    block.setAttribute('data-step',String(step));
    block.querySelectorAll('[name]').forEach(function(inp){
      inp.name=String(inp.name).replace(/steps\[\d+]\[items]\[\d+]/,'steps['+step+'][items]['+i+']');
    });
    const ord=block.querySelector('.sb-ord'); if(ord) ord.value=String(i+1);
  });
  sbSyncGaps(box);
}
function sbMakeGap(stepIdx){
  const g=document.createElement('div');
  g.className='sb-insert-gap';
  g.setAttribute('data-step',String(stepIdx));
  g.innerHTML="<button type='button' class='sb-insert-btn' onclick='sbInsertAt(this,"+stepIdx+")'>+ Hier einfuegen</button>";
  sbBindGapDrop(g);
  return g;
}
function sbSyncGaps(box){
  if(!box) return;
  const step=sbStepIdx(box);
  box.querySelectorAll('.sb-insert-gap').forEach(function(g){g.remove();});
  const hint=box.querySelector('.muted');
  const items=Array.from(box.querySelectorAll('.item-block'));
  items.forEach(function(it){ box.insertBefore(sbMakeGap(step), it); });
  if(hint&&hint.parentElement===box){box.insertBefore(hint, box.firstChild);}
}
function sbItemHtml(stepIdx, ii){
  return "<div class='item-row'>"+
    "<span class='sb-drag' title='Ziehen zum Verschieben' aria-label='Verschieben' role='button' tabindex='0'>"+sbIco('grip')+"</span>"+
    sbFieldSelect('steps['+stepIdx+'][items]['+ii+'][field]','')+
    "<input class='text-input sb-ord' name='steps["+stepIdx+"][items]["+ii+"][order]' value='"+(ii+1)+"' placeholder='Ord' title='Reihenfolge'>"+
    "<input class='text-input' name='steps["+stepIdx+"][items]["+ii+"][inject]' placeholder='D.3' title='Stoerung'>"+
    "<button type='button' class='btn-ico danger' onclick='sbRemoveItem(this)' title='Entfernen' aria-label='Entfernen'>"+sbIco('trash')+"</button></div>"+
    "<div><label>Frage / Beschreibung</label><input class='text-input' name='steps["+stepIdx+"][items]["+ii+"][cue]' placeholder='Kontrollfrage oder kurze Beschreibung'></div>"+
    "<details class='sb-info-fold'><summary>Zusatzinformation <span class='sb-info-hint'>(aufklappen)</span></summary><div class='sb-info-body'><textarea class='text-input' name='steps["+stepIdx+"][items]["+ii+"][info]' rows='2' placeholder='Hinweise fuer Instruktoren…'></textarea></div></details>";
}
function sbMakeItem(stepIdx, ii){const row=document.createElement('div');row.className='item-block';row.setAttribute('data-step',String(stepIdx));row.innerHTML=sbItemHtml(stepIdx,ii);sbBindDrag(row);return row;}
function sbAddItem(btn, stepIdx){const box=sbItemsBox(btn);if(!box) return;box.appendChild(sbMakeItem(stepIdx, box.querySelectorAll('.item-block').length));sbReindexItems(box);}
function sbInsertAt(btn, stepIdx){
  const gap=btn.closest('.sb-insert-gap');
  const box=sbItemsBox(btn);
  if(!box||!gap) return;
  const next=gap.nextElementSibling;
  const row=sbMakeItem(stepIdx,0);
  if(next) box.insertBefore(row, next); else box.appendChild(row);
  sbReindexItems(box);
}
function sbRemoveItem(btn){const block=btn.closest('.item-block');const box=block&&block.parentElement;if(!block||!box) return;block.remove();sbReindexItems(box);}
let sbDragEl=null;
function sbBindGapDrop(gap){
  gap.addEventListener('dragover', function(e){
    if(!sbDragEl||sbDragEl.parentElement!==gap.parentElement) return;
    e.preventDefault(); sbClearDragOver(); gap.classList.add('sb-drag-over'); e.dataTransfer.dropEffect='move';
  });
  gap.addEventListener('dragleave', function(){gap.classList.remove('sb-drag-over');});
  gap.addEventListener('drop', function(e){
    e.preventDefault(); gap.classList.remove('sb-drag-over');
    if(!sbDragEl||sbDragEl.parentElement!==gap.parentElement) return;
    const box=gap.parentElement;
    const next=gap.nextElementSibling;
    if(next) box.insertBefore(sbDragEl, next); else box.appendChild(sbDragEl);
    sbReindexItems(box);
  });
}
function sbBindDrag(block){
  const handle=block.querySelector('.sb-drag'); if(!handle) return;
  let fromHandle=false;
  block.setAttribute('draggable','true');
  const mark=function(e){ if(e&&e.button!=null&&e.button!==0) return; fromHandle=true; };
  const clear=function(){ fromHandle=false; };
  handle.addEventListener('mousedown', mark);
  handle.addEventListener('touchstart', mark, {passive:true});
  handle.addEventListener('mouseup', clear);
  handle.addEventListener('mouseleave', function(){ if(!sbDragEl) clear(); });
  handle.addEventListener('keydown', function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); } });
  block.addEventListener('dragstart', function(e){
    if(!fromHandle){ e.preventDefault(); return; }
    sbDragEl=block; block.classList.add('sb-dragging');
    e.dataTransfer.effectAllowed='move';
    try{e.dataTransfer.setData('text/plain','item');}catch(_e){}
    try{e.dataTransfer.setDragImage(block, 16, 16);}catch(_e2){}
  });
  block.addEventListener('dragend', function(){
    fromHandle=false;
    block.classList.remove('sb-dragging');
    sbClearDragOver();
    sbDragEl=null;
  });
  block.addEventListener('dragover', function(e){
    if(!sbDragEl||sbDragEl===block||sbDragEl.parentElement!==block.parentElement) return;
    e.preventDefault(); sbClearDragOver(); block.classList.add('sb-drag-over'); e.dataTransfer.dropEffect='move';
  });
  block.addEventListener('dragleave', function(){block.classList.remove('sb-drag-over');});
  block.addEventListener('drop', function(e){
    e.preventDefault(); block.classList.remove('sb-drag-over');
    if(!sbDragEl||sbDragEl===block||sbDragEl.parentElement!==block.parentElement) return;
    const box=block.parentElement;
    const rect=block.getBoundingClientRect();
    const before=(e.clientY-rect.top)<(rect.height/2);
    if(before) box.insertBefore(sbDragEl, block); else {
      let ref=block.nextElementSibling;
      while(ref && !ref.classList.contains('item-block') && !ref.classList.contains('sb-insert-gap')) ref=ref.nextElementSibling;
      box.insertBefore(sbDragEl, ref);
    }
    sbReindexItems(box);
  });
}
function sbAddStep(){
  const wrap=document.getElementById('sbSteps'); const si=wrap.querySelectorAll('.step-card').length;
  const card=document.createElement('div'); card.className='step-card'; card.setAttribute('data-step', String(si));
  card.innerHTML="<div class='grid3'><div><label>Phase-ID</label><input class='text-input' name='steps["+si+"][id]' value='phase_"+(si+1)+"'></div><div><label>Titel</label><input class='text-input' name='steps["+si+"][title]' value='Phase "+(si+1)+"'></div><div><label>Dauer Min</label><input class='text-input' type='number' min='0' name='steps["+si+"][duration_min]' value='10'></div></div>"+
    "<div class='grid2' style='margin-top:8px;'><div><label>Modus</label><select class='text-input' name='steps["+si+"][mode]'><option value='info'>info</option><option value='score' selected>score</option><option value='run'>run</option></select></div><div><label>Durchgehend (Codes)</label><input class='text-input' name='steps["+si+"][continuous]' placeholder='C.1, C.2, C.8'></div></div>"+
    "<div style='margin-top:8px;'><label>Prep / Hinweis</label><input class='text-input' name='steps["+si+"][prep]'></div>"+
    "<div class='sb-items' style='margin-top:10px;'><div class='muted' style='margin-bottom:6px;'>Griff = Drag&Drop · Zwischenzeile = dort einfuegen · + unten = anhaengen</div></div>"+
    "<div class='sb-actions'><button type='button' class='btn-ico' onclick='sbAddItem(this,"+si+")' title='Anhaengen' aria-label='Anhaengen'>"+sbIco('plus')+"</button>"+
    "<button type='button' class='btn-ico danger' onclick='this.closest(\".step-card\").remove()' title='Phase entfernen' aria-label='Phase entfernen'>"+sbIco('trash')+"</button></div>";
  wrap.appendChild(card); sbAddItem(card.querySelector('.sb-actions .btn-ico'), si);
}
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.sb-editor .item-block').forEach(sbBindDrag);
  document.querySelectorAll('.sb-editor .sb-insert-gap').forEach(sbBindGapDrop);
  document.querySelectorAll('.sb-editor .sb-items').forEach(sbSyncGaps);
});
JS;
		echo "\n</script>\n";


		if (function_exists("pageFooterLocal")) {
			pageFooterLocal();
		}
	}
}
