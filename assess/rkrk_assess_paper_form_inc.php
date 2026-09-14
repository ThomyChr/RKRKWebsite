<?php
/**
 * Papierformulare: leere Beurteilungs-/Assessment- und Quiz-Bögen zum Ausfüllen per Hand.
 * Nutzt dieselben Template-JSON- und Fragen-CSV-Strukturen wie die interaktiven Module.
 */

if (!function_exists("rkrk_paper_h")) {
	function rkrk_paper_h($s) {
		return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
	}

	/** Plain text → escaped + Zeilenumbrüche; vorhandenes HTML aus Templates beibehalten. */
	function rkrk_paper_format_rich_text(string $text): string {
		$text = trim($text);
		if ($text === "") {
			return "";
		}
		if (strip_tags($text) !== $text) {
			if (function_exists("rkrk_assess_format_template_html_fragment")) {
				return rkrk_assess_format_template_html_fragment($text, "print");
			}
			return $text;
		}
		return nl2br(rkrk_paper_h($text));
	}

	function rkrk_paper_type_string($meta) {
		if (function_exists("rkrk_type_string")) {
			return strtolower(trim((string) rkrk_type_string($meta)));
		}
		if (is_array($meta)) {
			return strtolower(trim((string) ($meta["type"] ?? "text")));
		}
		return strtolower(trim((string) $meta));
	}

	function rkrk_paper_type_options_raw($meta) {
		if (function_exists("rkrk_type_options_string")) {
			$s = trim((string) rkrk_type_options_string($meta));
			if ($s !== "") {
				return $s;
			}
		}
		$ts = rkrk_paper_type_string($meta);
		if (strpos($ts, "radio:") === 0) {
			return substr($ts, 6);
		}
		if (strpos($ts, "multi:") === 0) {
			return substr($ts, 6);
		}
		return "";
	}

	/** @return list<string> */
	function rkrk_paper_parse_option_labels($optsRaw) {
		$out = array();
		foreach (preg_split("/\|/", (string) $optsRaw) as $p) {
			$p = trim((string) $p);
			if ($p === "") {
				continue;
			}
			$p = preg_replace("/\s*\([rgo]\)\s*$/i", "", $p);
			$p = trim((string) $p);
			if ($p !== "") {
				$out[] = $p;
			}
		}
		return $out;
	}

	/** @return list<string> */
	function rkrk_paper_split_checks($checks) {
		if (function_exists("rkrk_split_checks_list")) {
			return rkrk_split_checks_list($checks);
		}
		$checks = trim((string) $checks);
		if ($checks === "") {
			return array();
		}
		$parts = preg_split("/\s+[–-]\s+/u", $checks);
		if (!is_array($parts)) {
			return array($checks);
		}
		$out = array();
		foreach ($parts as $p) {
			$p = trim((string) $p);
			if ($p !== "") {
				$out[] = $p;
			}
		}
		return $out;
	}

	function rkrk_paper_field_is_full_width($label, $type, $meta, $isEvalPoint, $hasSubchecks) {
		if ($type === "print" || $type === "textarea" || $isEvalPoint) {
			return true;
		}
		if ($type === "directcheck" && $hasSubchecks) {
			return true;
		}
		if (preg_match("/\b(Hinweis|Abschluss|Bemerkung|Referenz|Gesamtstufe)\b/ui", (string) $label) === 1) {
			return true;
		}
		$info = is_array($meta) ? trim(strip_tags((string) ($meta["info"] ?? ""))) : "";
		if (strlen($info) > 220) {
			return true;
		}
		return false;
	}

	function rkrk_paper_echo_styles() {
		echo "<style>
@page{size:A4 portrait;margin:8mm 9mm;}
.pf-body{font-family:Segoe UI,system-ui,Arial,sans-serif;font-size:10pt;line-height:1.3;color:#111;margin:0;padding:8mm 9mm;max-width:210mm;box-sizing:border-box;}
.pf-body.pf-a4{width:210mm;min-height:297mm;}
.pf-noprint{margin:0 0 14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.pf-noprint a,.pf-noprint button{font-size:13px;padding:6px 10px;border:1px solid #94a3b8;border-radius:6px;background:#f8fafc;cursor:pointer;text-decoration:none;color:#1e293b;}
.pf-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;border-bottom:2px solid #2f6f2f;padding-bottom:8px;margin-bottom:12px;}
.pf-header h1{margin:0;font-size:1.25rem;line-height:1.25;}
.pf-header .pf-sub{font-size:10pt;color:#475569;margin-top:4px;}
.pf-logo img{max-height:44px;width:auto;}
.pf-form-id{border:2px solid #1e5a3a;border-radius:6px;padding:5px 10px;text-align:right;min-width:9.5em;background:#f8fafc;flex-shrink:0;}
.pf-body.pf-solution-mode .pf-form-id{border-color:#15803d;background:#f0fdf4;}
.pf-form-id-label{display:block;font-size:7pt;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b;margin:0 0 2px;}
.pf-form-id-num{display:block;font-family:ui-monospace,Consolas,monospace;font-size:13pt;font-weight:800;line-height:1.15;color:#14532d;letter-spacing:.02em;}
.pf-form-id-code{display:block;font-family:ui-monospace,Consolas,monospace;font-size:7.5pt;color:#64748b;margin-top:2px;}
.pf-form-id-drone{display:block;font-weight:800;font-size:10pt;line-height:1.2;color:#14532d;margin-top:4px;max-width:14em;}
.pf-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px 14px;margin:0 0 14px;font-size:10.5pt;}
.pf-meta .pf-line{border-bottom:1px solid #334155;min-height:1.4em;margin-top:2px;}
.pf-meta .pf-meta-cap{font-size:8pt;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;}
.pf-meta-drone-val{font-weight:800;font-size:12pt;line-height:1.25;color:#14532d;margin-top:3px;padding:4px 0 2px;border-bottom:2px solid #1e5a3a;}
.pf-sec{margin:10px 0 6px;padding-top:5px;border-top:1px solid #cbd5e1;}
.pf-sec h2{margin:0 0 6px;font-size:10pt;color:#1e3a1e;grid-column:1/-1;}
.pf-sec-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px 8px;align-items:start;}
.pf-field{margin:0;padding:5px 7px;border:1px solid #e2e8e0;border-radius:4px;break-inside:avoid;min-width:0;}
.pf-field-full{grid-column:1/-1;}
.pf-sec-ref .pf-field-print-compact{padding:3px 6px;}
.pf-sec-ref .pf-printblock{font-size:7.5pt;line-height:1.22;padding:4px 6px;margin:0;max-height:none;column-count:2;column-gap:10px;}
.pf-field-compact{padding:4px 8px;}
.pf-label{font-weight:700;font-size:10.5pt;}
.pf-ref{font-weight:400;color:#64748b;font-size:9pt;margin-left:6px;}
.pf-hint{font-size:9.5pt;color:#475569;margin:3px 0 4px;}
.pf-hint strong{font-weight:600;}
.pf-rich p{margin:.25em 0;}
.pf-rich ul,.pf-rich ol{margin:.3em 0;padding-left:1.25em;}
.pf-rich li{margin:.15em 0;}
.pf-rich figure,.print-text-content figure,.print-text-intro figure{margin:8px 0;text-align:center;}
.pf-rich img,.print-text-content img,.print-text-intro img{max-width:100%;height:auto;width:auto;display:block;margin:6px auto;border-radius:5px 15% 10px;object-fit:contain;}
.pf-rich img.lernimagelarge,.print-text-content img.lernimagelarge,.print-text-intro img.lernimagelarge{max-height:500px;}
.pf-rich img.lernimage,.print-text-content img.lernimage,.print-text-intro img.lernimage{max-height:300px;}
.pf-rich img.lernimagesmall,.print-text-content img.lernimagesmall,.print-text-intro img.lernimagesmall{max-height:200px;}
.pf-rich img.lernimageverysmall,.print-text-content img.lernimageverysmall,.print-text-intro img.lernimageverysmall{max-height:100px;}
.pf-rich img.lernimagefixwidth,.print-text-content img.lernimagefixwidth,.print-text-intro img.lernimagefixwidth{width:600px;max-width:100%;height:auto;}
.pf-rich img.lernimagefixheight,.print-text-content img.lernimagefixheight,.print-text-intro img.lernimagefixheight{max-height:200px;}
.pf-rich img.lernimagefixlargeheight,.print-text-content img.lernimagefixlargeheight,.print-text-intro img.lernimagefixlargeheight{max-height:500px;}
.pf-rich img.lernimagesmallfixwidth,.print-text-content img.lernimagesmallfixwidth,.print-text-intro img.lernimagesmallfixwidth{width:450px;max-width:100%;height:auto;}
.pf-rich figcaption,.print-text-content figcaption,.print-text-intro figcaption{font-size:8.5pt;color:#64748b;margin-top:3px;}
.pf-line-write{border-bottom:1px solid #334155;min-height:1.35em;margin-top:4px;}
.pf-line-write.tall{min-height:3.2em;}
.pf-chk-row{display:flex;flex-wrap:wrap;gap:6px 14px;margin-top:4px;}
.pf-chk{display:inline-flex;align-items:flex-start;gap:5px;font-size:10.5pt;max-width:100%;}
.pf-box{display:inline-block;width:13px;height:13px;border:1.5px solid #334155;border-radius:2px;flex-shrink:0;margin-top:2px;}
.pf-chk-list{margin:4px 0 0;padding:0;list-style:none;}
.pf-chk-list li{display:flex;align-items:flex-start;gap:6px;margin:3px 0;font-size:10.5pt;}
.pf-chk-list-2col{display:grid;grid-template-columns:1fr 1fr;column-gap:16px;row-gap:5px;margin:4px 0 0;padding:0;list-style:none;}
.pf-chk-list-2col li{display:flex;align-items:flex-start;gap:5px;margin:0;font-size:10pt;line-height:1.3;}
.pf-chk-sub{margin:3px 0 0;padding:0;list-style:none;}
.pf-chk-sub li{font-size:8.5pt;margin:1px 0;}
.pf-subgrid{display:grid;grid-template-columns:1fr 1fr;gap:4px 8px;margin-top:4px;}
.pf-subgrp{padding:3px 5px;border:1px dashed #cbd5e1;border-radius:3px;background:#fafafa;break-inside:avoid;}
.pf-subgrp-title{display:block;font-size:8pt;font-weight:700;color:#475569;margin:0 0 2px;line-height:1.2;}
.pf-subgrp .pf-chk-list-2col{font-size:8.5pt;column-gap:8px;row-gap:2px;}
.pf-score-opts-many{display:grid;grid-template-columns:1fr 1fr;gap:3px 8px;align-items:start;}
.pf-score-opts-many .pf-score-opt{flex-direction:row;align-items:center;gap:4px;min-width:0;}
.pf-score-opts-many .pf-score-num{font-size:8.5pt;text-align:left;}
.pf-score-block{display:block;width:100%;margin-top:5px;padding:5px 8px;border:1px solid #64748b;border-radius:6px;background:#fff;box-sizing:border-box;}
.pf-score-block .pf-score-title{display:block;margin:0 0 4px;}
.pf-score-opts-stacked{display:grid;grid-template-columns:1fr 1fr;gap:4px 10px;width:100%;}
.pf-score-opt-row{flex-direction:row!important;align-items:flex-start!important;gap:5px;min-width:0;width:100%;}
.pf-score-opt-row .pf-score-box{margin-top:1px;flex-shrink:0;}
.pf-score-lbl{font-size:8.5pt;line-height:1.25;font-weight:600;color:#1e293b;flex:1;min-width:0;}
.pf-field-score-stacked .pf-label-row{display:block;}
.pf-field-score-stacked .pf-label-row .pf-label{display:block;width:100%;}
.pf-eval-grid{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:8px 14px;margin-top:4px;}
.pf-eval-grid-checks-only{display:block;}
.pf-eval-main{flex:1;min-width:min(100%,280px);}
.pf-eval-score{flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:4px;max-width:100%;}
.pf-eval-score .pf-score-block{width:min(100%,280px);align-self:stretch;}
.pf-eval-max{font-size:9pt;font-weight:800;color:#1e293b;line-height:1.2;white-space:nowrap;}
.pf-eval-score .pf-score-inline,.pf-eval-score .pf-score-block{margin-top:0;}
.pf-eval-main .pf-chk-heading{display:block;font-size:8.5pt;font-weight:700;color:#475569;margin:2px 0 3px;text-transform:uppercase;letter-spacing:.03em;}
.pf-label-row{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:8px 12px;}
.pf-label-row .pf-label{flex:1;min-width:12rem;margin:0;}
.pf-score-inline{flex-shrink:0;display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px 10px;padding:4px 8px;border:1px solid #64748b;border-radius:6px;background:#fff;}
.pf-score-inline .pf-score-title{font-size:8pt;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#1e293b;margin:0;padding:0;border:none;line-height:1;}
.pf-score-opts{display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px 12px;}
.pf-score-opt{display:inline-flex;flex-direction:column;align-items:center;gap:2px;margin:0;padding:0 2px;min-width:1.75em;}
.pf-score-box{display:block;width:14px;height:14px;border:1.5px solid #334155;border-radius:50%;flex-shrink:0;}
.pf-score-num{font-weight:800;font-size:10pt;line-height:1;color:#0f172a;}
.pf-score-opt.pf-m-r .pf-score-box{border-color:#d06a6a;border-width:2px;background:#fde8e8;}
.pf-score-opt.pf-m-r .pf-score-num{color:#b91c1c;}
.pf-score-opt.pf-m-g .pf-score-box{border-color:#6f9b6b;border-width:2px;background:#eef6ec;}
.pf-score-opt.pf-m-g .pf-score-num{color:#3d6b3a;}
.pf-score-opt.pf-m-o .pf-score-box{border-color:#d39b45;border-width:2px;background:#fef6e8;}
.pf-score-opt.pf-m-o .pf-score-num{color:#9a5b12;}
.pf-box-r{border-color:#d06a6a;background:#fde8e8;}
.pf-box-g{border-color:#6f9b6b;background:#eef6ec;}
.pf-box-o{border-color:#d39b45;background:#fef6e8;}
.pf-rule{display:inline-flex;align-items:center;justify-content:center;min-width:1.15em;height:1.15em;margin-left:5px;font-weight:900;font-size:9.5pt;line-height:1;border-radius:3px;padding:0 4px;vertical-align:middle;}
.pf-rule-star{background:#fecaca;color:#991b1b;border:1.5px solid #dc2626;}
.pf-rule-hash{background:#ffedd5;color:#9a3412;border:1.5px solid #ea580c;}
.pf-field-rule-star{border:1px solid #fecaca;border-left:6px solid #dc2626;background:#fff5f5;padding-left:10px;}
.pf-field-rule-hash{border:1px solid #ffedd5;border-left:6px solid #ea580c;background:#fffaf5;padding-left:10px;}
.pf-field-rule-star.pf-field-rule-hash{border-left:6px solid #dc2626;box-shadow:inset 5px 0 0 #fdba74;background:#fff7f5;}
.pf-sec-subtotal{margin:10px 0 2px;padding:8px 10px;border:1.5px dashed #94a3b8;border-radius:6px;background:#f1f5f9;display:flex;flex-wrap:wrap;align-items:center;gap:6px 10px;font-size:10.5pt;page-break-inside:avoid;}
.pf-sec-subtotal strong{font-weight:700;color:#1e293b;}
.pf-sec-subtotal .pf-subtotal-line{flex:0 1 5.5em;min-width:4.5em;border-bottom:2px solid #334155;min-height:1.45em;}
.pf-sec-subtotal .pf-subtotal-max{color:#475569;font-size:10pt;}
.pf-sec-subtotal .pf-subtotal-hint{color:#64748b;font-size:9pt;}
.pf-legend{display:flex;flex-wrap:wrap;gap:8px 14px;margin:0 0 12px;padding:6px 10px;font-size:9pt;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;}
.pf-legend-item{display:inline-flex;align-items:center;gap:4px;}
.pf-field-eval{padding:5px 8px;border-color:#b8c9b8;background:#fcfdfb;}
.pf-field-eval.pf-field-rule-star{border:1px solid #fecaca;border-left:6px solid #dc2626;background:#fff5f5;}
.pf-field-eval.pf-field-rule-hash{border:1px solid #ffedd5;border-left:6px solid #ea580c;background:#fffaf5;}
.pf-field-eval.pf-field-rule-star.pf-field-rule-hash{border-left:6px solid #dc2626;box-shadow:inset 5px 0 0 #fdba74;background:#fff7f5;}
.pf-field-eval .pf-hint{margin:2px 0 3px;font-size:9pt;}
.pf-chk-list-2col{column-gap:12px;row-gap:3px;}
.pf-printblock{font-size:8.5pt;color:#1e293b;margin-top:3px;padding:4px 6px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:3px;max-height:none;overflow:visible;}
.pf-printblock.pf-print-full{max-height:none;}
.pf-q{margin:0 0 10px;padding:6px 8px;border:1px solid #d8dfd6;border-radius:6px;page-break-inside:avoid;}
.pf-q-nr{font-weight:800;margin-right:4px;}
.pf-q-ref{font-size:7pt;font-weight:400;color:#94a3b8;margin-left:3px;white-space:nowrap;}
.pf-q-text{margin:0 0 4px;font-weight:600;font-size:10.5pt;}
.pf-q-meta{font-size:9pt;color:#64748b;margin-bottom:4px;}
.pf-q-answer-hint{font-size:9pt;margin:0 0 5px;padding:2px 8px;border-radius:6px;display:inline-block;}
.pf-q-answer-hint--single{color:#166534;background:#f0fdf4;border:1px solid #dcfce7;}
.pf-q-answer-hint--multi{color:#1d4ed8;background:#eff6ff;border:1px solid #dbeafe;}
.pf-q-img{margin:4px 0 6px;}
.pf-q-img img{display:block;max-width:100%;max-height:46mm;height:auto;object-fit:contain;border:1px solid #cbd5e1;border-radius:4px;background:#fafafa;}
.pf-noprint .pf-paper-img-toggle,.pf-noprint .pf-paper-mode-toggle,.pf-noprint .pf-paper-drone-wrap{display:inline-flex;gap:6px;align-items:center;margin-left:10px;padding-left:10px;border-left:1px solid #cbd5e1;}
.pf-noprint .pf-paper-img-toggle a,.pf-noprint .pf-paper-mode-toggle a{font-size:13px;}
.pf-noprint .pf-paper-img-toggle .pf-active,.pf-noprint .pf-paper-mode-toggle .pf-active{font-weight:700;color:#1e5a3a;}
.pf-noprint .pf-paper-drone-wrap label{font-size:13px;color:#334155;display:inline-flex;align-items:center;gap:6px;}
.pf-paper-drone-select{font-size:13px;padding:4px 8px;border:1px solid #94a3b8;border-radius:6px;background:#fff;max-width:min(240px,42vw);}
.pf-meta-drone-sel{font-size:8.5pt;color:#64748b;margin:0 0 2px;line-height:1.2;}
.pf-chk.pf-chk-correct .pf-box{background:#4ade80;border:2px solid #14532d;}
.pf-chk.pf-chk-correct span:last-child{font-weight:700;color:#052e16;}
.pf-sol-block{margin-top:5px;padding:5px 7px;border:1px dashed #22c55e;border-radius:4px;background:#dcfce7;font-size:9.5pt;}
.pf-sol-block .pf-sol-title{font-weight:700;color:#14532d;margin:0 0 3px;font-size:9pt;}
.pf-body.pf-solution-mode .pf-header{border-bottom-color:#15803d;}
.pf-quiz-grid{display:block;}
.pf-quiz-grid .pf-q{margin:0 0 8px;}
.pf-quiz-grid .pf-chk-row{display:grid;grid-template-columns:1fr 1fr;column-gap:12px;row-gap:4px;margin-top:4px;}
.pf-quiz-grid .pf-chk{align-items:flex-start;min-width:0;}
.pf-footer{margin-top:16px;padding-top:8px;border-top:1px solid #cbd5e1;font-size:9pt;color:#64748b;}
.pf-story-point{margin:0 0 8px;padding:8px 10px;border:1px solid #d9e5d6;border-radius:8px;background:#fcfefb;grid-column:1/-1;page-break-inside:avoid;}
.pf-story-cue{display:block;margin:0 0 6px;padding:6px 8px;border-left:3px solid #48673a;background:#f7fbf6;font-size:10pt;color:#1f3d1f;line-height:1.35;}
.pf-story-cue-label,.pf-story-info-label{display:inline-block;font-size:8pt;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#48673a;margin-right:6px;}
.pf-story-info{margin:0 0 8px;padding:6px 8px;border-left:3px solid #64748b;background:#f8fafc;font-size:9.5pt;color:#334155;line-height:1.35;}
.pf-story-info-label{color:#475569;}
.pf-sec-phase{border-top:2px solid #48673a;}
.pf-phase-prep{grid-column:1/-1;margin:0 0 6px;padding:5px 8px;border-left:3px solid #94a3b8;background:#f8fafc;font-size:9pt;color:#475569;}
.pf-sec-story{border:1px solid #c5d4c0;border-radius:8px;padding:8px 10px;background:#f8faf7;margin:0 0 12px;}
.pf-sec-story h2{margin:0 0 6px;border:0;padding:0;color:#1f3d1f;}
.pf-sb-ov{font-size:9.5pt;line-height:1.35;color:#334155;}
.pf-sb-ov__title{font-size:11pt;margin:0 0 6px;color:#1f3d1f;}
.pf-sb-ov__meta{font-size:8.5pt;color:#64748b;margin:0 0 6px;}
.pf-sb-ov__phase{margin:6px 0 8px;padding:4px 6px;border-left:3px solid #48673a;background:#fff;page-break-inside:avoid;}
.pf-sb-ov__phase-title{margin:0 0 3px;}
.pf-sb-ov__dur{font-weight:400;color:#64748b;}
.pf-sb-ov__phase-prep,.pf-sb-ov__cont{margin:0 0 3px;font-size:9pt;}
.pf-sb-ov__items{margin:3px 0 0 1.15em;padding:0;}
.pf-sb-ov__items li{margin:2px 0;}
.pf-sb-ov__cue,.pf-sb-ov__info,.pf-sb-ov__inject{margin:1px 0;font-size:9pt;}
.pf-sb-ov__nb{margin:6px 0 0;font-size:8.5pt;color:#64748b;}
.pf-nb-note{margin:8px 0 0;font-size:8.5pt;color:#64748b;}
@media print{
	.pf-noprint{display:none!important;}
	.pf-body,.pf-body.pf-a4{width:auto;max-width:none;padding:0;margin:0;font-size:8.5pt;line-height:1.22;}
	.pf-header{margin-bottom:6px;padding-bottom:4px;}
	.pf-header h1{font-size:11pt;}
	.pf-form-id{padding:3px 7px;}
	.pf-form-id-num{font-size:11pt;}
	.pf-meta{font-size:8.5pt;margin-bottom:6px;}
	.pf-legend{font-size:7.5pt;padding:4px 6px;margin-bottom:6px;}
	.pf-field{padding:4px 5px;}
	.pf-label{font-size:8.5pt;}
	.pf-hint{font-size:7.5pt;}
	.pf-sec h2{font-size:9pt;}
	.pf-sec-grid{gap:4px 6px;}
	.pf-quiz-grid .pf-chk-row{column-gap:8px;row-gap:3px;}
	.pf-quiz-grid .pf-q{margin-bottom:6px;}
	.pf-chk-list-2col{font-size:8pt;}
	.pf-subgrid{gap:3px 5px;}
	.pf-score-opts-stacked{gap:3px 6px;}
	.pf-score-lbl{font-size:8pt;}
	.pf-eval-grid{gap:4px 8px;margin-top:2px;}
	.pf-eval-max{font-size:8pt;}
	.pf-chk-heading{font-size:7.5pt;}
	.pf-field,.pf-q,.pf-subgrp{break-inside:avoid;}
	.pf-sec-ref .pf-printblock{font-size:7pt;}
}
</style>\n";
	}

	/**
	 * @return array{text: string, marker: string|null}
	 */
	function rkrk_paper_parse_color_marker($text) {
		$raw = trim((string) $text);
		$marker = null;
		if (preg_match("/\(([rgo])\)\s*$/iu", $raw, $m)) {
			$marker = strtolower($m[1]);
		}
		$clean = preg_replace("/\s*\([rgo]\)\s*$/iu", "", $raw);
		$clean = trim((string) $clean);
		if ($clean === "") {
			$clean = $raw;
		}
		return array("text" => $clean, "marker" => $marker);
	}

	function rkrk_paper_clean_check_label($item) {
		return rkrk_paper_parse_color_marker($item)["text"];
	}

	/**
	 * @return array{clean: string, star: bool, hash: bool}
	 */
	function rkrk_paper_parse_point_label_rules($label) {
		$label = trim((string) $label);
		$star = (strpos($label, "*") !== false);
		$hash = (strpos($label, "#") !== false);
		$clean = $label;
		if (preg_match("/^([A-Z]\.\d+)\s*([*#]+)\s*(.+)$/u", $label, $m)) {
			$clean = $m[1] . " " . trim($m[3]);
			$ruleStr = $m[2];
			$star = (strpos($ruleStr, "*") !== false);
			$hash = (strpos($ruleStr, "#") !== false);
		} elseif (preg_match("/^([A-Z]\.\d+)\s*(.+)$/u", $label, $m)) {
			$clean = $m[1] . " " . trim($m[2]);
		}
		return array("clean" => trim($clean), "star" => $star, "hash" => $hash);
	}

	function rkrk_paper_echo_rule_badges($star, $hash) {
		if ($star) {
			echo "<span class=\"pf-rule pf-rule-star\" title=\"Kritisch: nie Gütestufe 0\">*</span>";
		}
		if ($hash) {
			echo "<span class=\"pf-rule pf-rule-hash\" title=\"Mindestens Gütestufe 2\">#</span>";
		}
	}

	function rkrk_paper_field_rule_classes($star, $hash) {
		if ($star && $hash) {
			return " pf-field-rule-star pf-field-rule-hash";
		}
		if ($star) {
			return " pf-field-rule-star";
		}
		if ($hash) {
			return " pf-field-rule-hash";
		}
		return "";
	}

	function rkrk_paper_marker_box_class($marker) {
		if ($marker === "r") {
			return " pf-box-r";
		}
		if ($marker === "g") {
			return " pf-box-g";
		}
		if ($marker === "o") {
			return " pf-box-o";
		}
		return "";
	}

	/** @return list<array{text: string, marker: string|null}> */
	function rkrk_paper_parse_scored_options($optsRaw) {
		$out = array();
		foreach (preg_split("/\|/", (string) $optsRaw) as $p) {
			$p = trim((string) $p);
			if ($p === "") {
				continue;
			}
			$out[] = rkrk_paper_parse_color_marker($p);
		}
		return $out;
	}

	function rkrk_paper_echo_check_item($rawText) {
		$parsed = rkrk_paper_parse_color_marker($rawText);
		$boxCls = "pf-box" . rkrk_paper_marker_box_class($parsed["marker"]);
		echo "<li><span class=\"" . $boxCls . "\" aria-hidden=\"true\"></span><span>" . rkrk_paper_h($parsed["text"]) . "</span></li>";
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	function rkrk_paper_field_max_points($meta) {
		$optsRaw = rkrk_paper_type_options_raw($meta);
		if ($optsRaw === "") {
			return 0;
		}
		$max = 0;
		foreach (rkrk_paper_parse_scored_options($optsRaw) as $opt) {
			$t = (string) $opt["text"];
			if (preg_match("/^(\d+)/", $t, $m)) {
				$max = max($max, (int) $m[1]);
			}
		}
		return $max;
	}

	/**
	 * @param array<string, mixed> $fields
	 * @return array{max: int, count: int}
	 */
	function rkrk_paper_section_scoring_stats(array $fields) {
		$maxTotal = 0;
		$count = 0;
		foreach ($fields as $meta) {
			if (!is_array($meta)) {
				continue;
			}
			$type = rkrk_paper_type_string($meta);
			if ($type === "hidden" || $type === "summe" || $type === "countfails" || $type === "countcritical") {
				continue;
			}
			$fp = rkrk_paper_field_max_points($meta);
			if ($fp > 0) {
				$maxTotal += $fp;
				$count++;
			}
		}
		return array("max" => $maxTotal, "count" => $count);
	}

	function rkrk_paper_echo_section_subtotal($maxPoints, $pointCount) {
		if ($maxPoints <= 0) {
			return;
		}
		echo "<div class=\"pf-sec-subtotal\" aria-label=\"Zwischentotal\">";
		echo "<strong>Zwischentotal</strong>";
		echo "<span class=\"pf-subtotal-line\" aria-hidden=\"true\"></span>";
		echo "<span class=\"pf-subtotal-max\">/ " . (int) $maxPoints . " Pkt.</span>";
		if ($pointCount > 0) {
			echo "<span class=\"pf-subtotal-hint\">(" . (int) $pointCount . " bewertete Prüfpunkte)</span>";
		}
		echo "</div>";
	}

	function rkrk_paper_echo_form_legend() {
		echo "<div class=\"pf-legend\" aria-label=\"Legende Bewertungsregeln\">";
		echo "<span class=\"pf-legend-item\"><span class=\"pf-rule pf-rule-star\">*</span> nie Stufe 0</span>";
		echo "<span class=\"pf-legend-item\"><span class=\"pf-rule pf-rule-hash\">#</span> min. Stufe 2</span>";
		echo "<span class=\"pf-legend-item\"><span class=\"pf-box pf-box-r\" style=\"width:11px;height:11px;\"></span> (r)</span>";
		echo "<span class=\"pf-legend-item\"><span class=\"pf-box pf-box-o\" style=\"width:11px;height:11px;\"></span> (o)</span>";
		echo "<span class=\"pf-legend-item\"><span class=\"pf-box pf-box-g\" style=\"width:11px;height:11px;\"></span> (g)</span>";
		echo "</div>";
	}

	/**
	 * @param array<string, mixed> $opts two_cols, hide_process, hide_info, hide_print, print_full
	 */
	function rkrk_paper_render_checks_block($checks, $subchecks, array $opts = array()) {
		$items = rkrk_paper_split_checks($checks);
		if (count($items) === 0) {
			return;
		}
		$twoCols = !isset($opts["two_cols"]) || !empty($opts["two_cols"]);
		$listCls = "pf-chk-list" . ($twoCols ? " pf-chk-list-2col" : "");
		echo "<ul class=\"" . $listCls . "\">";
		foreach ($items as $i => $it) {
			rkrk_paper_echo_check_item($it);
		}
		echo "</ul>";
		$sub = trim((string) $subchecks);
		if ($sub !== "") {
			$groups = preg_split("/\s*---\s*/", $sub);
			if (is_array($groups)) {
				echo "<div class=\"pf-subgrid\">";
				foreach ($groups as $gi => $grp) {
					$grp = trim((string) $grp);
					if ($grp === "") {
						continue;
					}
					$subItems = preg_split("/\r\n|\n|\|/", $grp);
					if (!is_array($subItems)) {
						continue;
					}
					$subTitle = "Teilpunkte " . ($gi + 1);
					if (isset($items[$gi])) {
						$short = rkrk_paper_clean_check_label($items[$gi]);
						if (function_exists("mb_strlen") && mb_strlen($short) > 48) {
							$short = mb_substr($short, 0, 45) . "…";
						} elseif (strlen($short) > 48) {
							$short = substr($short, 0, 45) . "…";
						}
						$subTitle .= " — " . $short;
					}
					echo "<div class=\"pf-subgrp\">";
					echo "<span class=\"pf-subgrp-title\">" . rkrk_paper_h($subTitle) . "</span>";
					echo "<ul class=\"pf-chk-list pf-chk-sub pf-chk-list-2col\">";
					foreach ($subItems as $si) {
						$si = trim((string) $si);
						if ($si === "") {
							continue;
						}
						rkrk_paper_echo_check_item($si);
					}
					echo "</ul></div>";
				}
				echo "</div>";
			}
		}
	}

	/**
	 * Lange Stufen-/Textoptionen (z. B. «6 — Experte …») → Raster unter dem Titel statt horizontal in der Kopfzeile.
	 *
	 * @param list<array{text: string, marker: string|null}> $options
	 */
	function rkrk_paper_score_use_stacked_layout(array $options) {
		if (count($options) > 4) {
			return true;
		}
		foreach ($options as $opt) {
			$t = (string) $opt["text"];
			if (strlen($t) > 10) {
				return true;
			}
			if (preg_match("/[—–\-]\s*/u", $t)) {
				return true;
			}
			if (!preg_match("/^\d+$/", $t)) {
				return true;
			}
		}
		return false;
	}

	/** Punktvergabe (Radio-Optionen): kompakt horizontal oder gestapelt mit Beschriftung. */
	function rkrk_paper_render_score_panel($optsRaw, $title = "Pkt") {
		$options = rkrk_paper_parse_scored_options($optsRaw);
		if (count($options) === 0) {
			return;
		}
		$stacked = rkrk_paper_score_use_stacked_layout($options);
		$panelCls = $stacked ? "pf-score-block" : "pf-score-inline";
		echo "<aside class=\"" . $panelCls . "\" aria-label=\"Punktvergabe\">";
		echo "<span class=\"pf-score-title\">" . rkrk_paper_h($title) . "</span>";
		$optsCls = "pf-score-opts";
		if ($stacked) {
			$optsCls .= " pf-score-opts-stacked";
		} elseif (count($options) > 4) {
			$optsCls .= " pf-score-opts-many";
		}
		echo "<span class=\"" . $optsCls . "\">";
		foreach ($options as $opt) {
			$m = $opt["marker"];
			$cls = "pf-score-opt" . ($stacked ? " pf-score-opt-row" : "");
			if ($m === "r" || $m === "g" || $m === "o") {
				$cls .= " pf-m-" . $m;
			}
			$textCls = $stacked ? "pf-score-lbl" : "pf-score-num";
			echo "<label class=\"" . $cls . "\"><span class=\"pf-score-box\" aria-hidden=\"true\"></span><span class=\"" . $textCls . "\">" . rkrk_paper_h($opt["text"]) . "</span></label>";
		}
		echo "</span></aside>";
	}

	/**
	 * @param array<string, mixed> $meta
	 * @param array<string, mixed> $opts
	 */
	function rkrk_paper_render_template_field($label, $meta, array $opts = array()) {
		$type = rkrk_paper_type_string($meta);
		if ($type === "hidden" || $type === "summe" || $type === "countfails") {
			return;
		}

		$ref = is_array($meta) && isset($meta["ref"]) ? trim((string) $meta["ref"]) : "";
		$process = is_array($meta) ? trim((string) ($meta["process"] ?? "")) : "";
		$info = is_array($meta) ? trim((string) ($meta["info"] ?? "")) : "";
		$placeholder = is_array($meta) ? trim((string) ($meta["placeholder"] ?? "")) : "";
		$checks = is_array($meta) ? trim((string) ($meta["checks"] ?? "")) : "";
		$subchecks = is_array($meta) ? trim((string) ($meta["subchecks"] ?? "")) : "";
		$optsRaw = rkrk_paper_type_options_raw($meta);

		$hideProcess = !empty($opts["hide_process"]);
		$hideInfo = !empty($opts["hide_info"]);
		$hidePrint = !empty($opts["hide_print"]);

		$isScoreType = (strpos($type, "radio:") === 0 || $type === "radio" || strpos($type, "multi:") === 0 || $type === "multi");
		$scoreLabels = $isScoreType ? rkrk_paper_parse_option_labels($optsRaw) : array();
		$hasScore = count($scoreLabels) > 0;
		$checkItems = rkrk_paper_split_checks($checks);
		$hasCheckItems = count($checkItems) > 0;
		$isEvalPoint = $hasCheckItems && $hasScore;

		$labelRules = rkrk_paper_parse_point_label_rules($label);
		$displayLabel = $labelRules["clean"] !== "" ? $labelRules["clean"] : $label;

		$hasSubchecks = trim((string) $subchecks) !== "";
		$scoreOptions = $hasScore ? rkrk_paper_parse_scored_options($optsRaw) : array();
		$stackedScore = $hasScore && rkrk_paper_score_use_stacked_layout($scoreOptions);
		$spanFull = rkrk_paper_field_is_full_width($label, $type, $meta, $isEvalPoint, $hasSubchecks) || $stackedScore;
		$compact = !$isEvalPoint && !$stackedScore && ($type === "checkbox" || $isScoreType);
		$fieldCls = "pf-field" . ($compact ? " pf-field-compact" : "") . ($isEvalPoint ? " pf-field-eval" : "");
		if ($stackedScore) {
			$fieldCls .= " pf-field-score-stacked";
		}
		if ($type === "print") {
			$fieldCls .= " pf-field-print-compact";
		}
		$fieldCls .= rkrk_paper_field_rule_classes($labelRules["star"], $labelRules["hash"]);
		if ($spanFull) {
			$fieldCls .= " pf-field-full";
		}
		echo "<div class=\"" . $fieldCls . "\">";
		echo "<div class=\"pf-label-row\">";
		echo "<div class=\"pf-label\">" . rkrk_paper_h($displayLabel);
		rkrk_paper_echo_rule_badges($labelRules["star"], $labelRules["hash"]);
		if ($ref !== "" && function_exists("rkrk_ref_display")) {
			echo "<span class=\"pf-ref\">" . rkrk_paper_h(rkrk_ref_display($ref)) . "</span>";
		} elseif ($ref !== "") {
			echo "<span class=\"pf-ref\">" . rkrk_paper_h($ref) . "</span>";
		}
		echo "</div>";
		// Eval-Punkte: Punktvergabe unten neben Max/Kontrollpunkte — sonst in der Kopfzeile.
		if (!$isEvalPoint && !$stackedScore && ($isScoreType && $hasScore)) {
			rkrk_paper_render_score_panel($optsRaw);
		}
		echo "</div>";

		if (!$isEvalPoint && $stackedScore) {
			rkrk_paper_render_score_panel($optsRaw);
		}

		if ($type === "print") {
			if (!$hidePrint && $info !== "") {
				$formatted = rkrk_paper_format_rich_text($info);
				if ($formatted !== "") {
					echo "<div class=\"pf-printblock pf-print-full pf-rich\">" . $formatted . "</div>";
				}
			}
			echo "</div>";
			return;
		}

		if ($process !== "" && !$hideProcess) {
			$procFmt = rkrk_paper_format_rich_text($process);
			echo "<div class=\"pf-hint pf-rich\"><strong>Ablauf:</strong> " . $procFmt . "</div>";
		}

		if ($isEvalPoint) {
			$maxPts = rkrk_paper_field_max_points($meta);
			echo "<div class=\"pf-eval-grid\">";
			echo "<div class=\"pf-eval-main\">";
			if ($info !== "" && !$hideInfo) {
				$infoFmt = rkrk_paper_format_rich_text($info);
				if ($infoFmt !== "") {
					echo "<div class=\"pf-hint pf-rich\">" . $infoFmt . "</div>";
				}
			}
			if ($placeholder !== "") {
				echo "<div class=\"pf-hint\"><em>" . rkrk_paper_h($placeholder) . "</em></div>";
			}
			echo "<span class=\"pf-chk-heading\">Kontrollpunkte</span>";
			$chkOpts = array_merge($opts, array("two_cols" => true));
			rkrk_paper_render_checks_block($checks, $subchecks, $chkOpts);
			echo "</div>";
			echo "<div class=\"pf-eval-score\">";
			if ($maxPts > 0) {
				echo "<div class=\"pf-eval-max\">Max " . (int) $maxPts . " Pkt.</div>";
			}
			rkrk_paper_render_score_panel($optsRaw);
			echo "</div>";
			echo "</div>";
			echo "</div>";
			return;
		}

		if ($info !== "" && !$hideInfo && $type !== "print") {
			$infoFmt = rkrk_paper_format_rich_text($info);
			if ($infoFmt !== "") {
				echo "<div class=\"pf-hint pf-rich\">" . $infoFmt . "</div>";
			}
		}
		if ($placeholder !== "" && $type !== "print") {
			echo "<div class=\"pf-hint\"><em>" . rkrk_paper_h($placeholder) . "</em></div>";
		}

		$chkOpts = array_merge($opts, array("two_cols" => true));
		if ($type === "directcheck" && $hasCheckItems) {
			rkrk_paper_render_checks_block($checks, $subchecks, $chkOpts);
		} elseif ($hasCheckItems && !$isEvalPoint) {
			rkrk_paper_render_checks_block($checks, $subchecks, $chkOpts);
		} elseif ($isScoreType && $hasScore) {
			// Punktvergabe bereits in der Kopfzeile
		} elseif ($isScoreType) {
			echo "<div class=\"pf-line-write\"></div>";
		} elseif ($type === "checkbox") {
			echo "<label class=\"pf-chk\"><span class=\"pf-box\"></span><span>Ja / erfüllt</span></label>";
		} elseif ($type === "date") {
			echo "<div class=\"pf-line-write\" style=\"max-width:10em;\"></div>";
		} elseif ($type === "textarea") {
			echo "<div class=\"pf-line-write tall\"></div>";
			echo "<div class=\"pf-line-write tall\"></div>";
		} else {
			$isRemark = (preg_match("/\bBemerkung\b/ui", (string) $label) === 1);
			echo "<div class=\"pf-line-write" . ($isRemark ? " tall" : "") . "\"></div>";
		}

		echo "</div>";
	}

	/**
	 * @param array<string, mixed> $tpl
	 * @param array<string, mixed> $opts
	 */
	function rkrk_paper_render_template_form(array $tpl, array $opts = array()) {
		$title = isset($tpl["title"]) ? (string) $tpl["title"] : "Beurteilung";
		$sections = isset($tpl["sections"]) && is_array($tpl["sections"]) ? $tpl["sections"] : array();
		$story = (isset($opts["story"]) && is_array($opts["story"])) ? $opts["story"] : null;
		$storyTitle = is_array($story) ? trim((string) ($story["title"] ?? $story["id"] ?? "")) : "";
		$subtitle = "Papierformular — zum Ausfüllen per Hand";
		if ($storyTitle !== "") {
			$subtitle = "Papierformular · Storyboard: " . $storyTitle;
		}

		rkrk_paper_emit_page_start($title, $subtitle, $opts);

		echo "<div class=\"pf-meta\">";
		echo "<div><span>Name Pilot/in</span><div class=\"pf-line\"></div></div>";
		echo "<div><span>Datum</span><div class=\"pf-line\"></div></div>";
		echo "<div><span>Prüfer/in</span><div class=\"pf-line\"></div></div>";
		echo "</div>";

		rkrk_paper_echo_form_legend();

		$renderSectionFields = static function (string $secName, array $fields, array $fieldOptsBase) {
			if ($fields === array()) {
				return;
			}
			$secCls = "pf-sec";
			if (preg_match("/referenz/ui", $secName)) {
				$secCls .= " pf-sec-ref";
			}
			if (!empty($fieldOptsBase["section_class"])) {
				$secCls .= " " . trim((string) $fieldOptsBase["section_class"]);
			}
			echo "<div class=\"" . $secCls . "\"><div class=\"pf-sec-grid\"><h2>" . rkrk_paper_h($secName) . "</h2>";
			if (!empty($fieldOptsBase["phase_prep"])) {
				echo "<div class=\"pf-phase-prep\">" . rkrk_paper_h((string) $fieldOptsBase["phase_prep"]) . "</div>";
			}
			$fieldOpts = $fieldOptsBase;
			unset($fieldOpts["section_class"], $fieldOpts["phase_prep"], $fieldOpts["story_entries"]);
			if (preg_match("/referenz/ui", $secName)) {
				$fieldOpts["print_full"] = true;
			}
			$storyEntries = (isset($fieldOptsBase["story_entries"]) && is_array($fieldOptsBase["story_entries"]))
				? $fieldOptsBase["story_entries"]
				: null;

			if (is_array($storyEntries)) {
				foreach ($storyEntries as $entry) {
					if (!is_array($entry) || (($entry["kind"] ?? "") === "remark")) {
						continue;
					}
					$lab = (string) ($entry["label"] ?? "");
					if ($lab === "" || !array_key_exists($lab, $fields)) {
						continue;
					}
					$cue = trim((string) ($entry["cue"] ?? ""));
					$sinfo = trim((string) ($entry["info"] ?? ""));
					echo "<div class=\"pf-story-point\">";
					if ($cue !== "") {
						echo "<div class=\"pf-story-cue\">" . rkrk_paper_h($cue) . "</div>";
					}
					if ($sinfo !== "") {
						echo "<div class=\"pf-story-info\"><span class=\"pf-story-info-label\">Zusatzinformation</span>"
							. "<div>" . nl2br(rkrk_paper_h($sinfo)) . "</div></div>";
					}
					rkrk_paper_render_template_field($lab, $fields[$lab], $fieldOpts);
					// Bemerkung direkt danach
					foreach ($fields as $rlab => $rmeta) {
						if (!is_string($rlab) || !preg_match('/\bBemerkung\b/ui', $rlab)) {
							continue;
						}
						$rc = function_exists("rkrk_assess_storyboard_normalize_code")
							? rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($rlab))
							: "";
						$ec = function_exists("rkrk_assess_storyboard_normalize_code")
							? rkrk_assess_storyboard_normalize_code((string) ($entry["code"] ?? ""))
							: "";
						if ($rc !== "" && $rc === $ec) {
							rkrk_paper_render_template_field($rlab, $rmeta, $fieldOpts);
							break;
						}
					}
					echo "</div>";
				}
			} else {
				foreach ($fields as $label => $meta) {
					rkrk_paper_render_template_field((string) $label, $meta, $fieldOpts);
				}
			}

			$secStats = rkrk_paper_section_scoring_stats($fields);
			if ($secStats["max"] > 0 && $secStats["count"] > 1) {
				echo "<div class=\"pf-field pf-field-full\">";
				rkrk_paper_echo_section_subtotal($secStats["max"], $secStats["count"]);
				echo "</div>";
			}
			echo "</div></div>";
		};

		if (is_array($story) && function_exists("rkrk_assess_storyboard_chrono_plan")) {
			if (function_exists("rkrk_assess_storyboard_overview_html")) {
				echo "<details class=\"pf-sec pf-sec-story\" open>";
				echo "<summary style=\"cursor:pointer;font-weight:700;color:#1f3d1f;margin:0 0 6px;\">Übersicht</summary>";
				echo rkrk_assess_storyboard_overview_html($story, array(
					"class_prefix" => "pf-sb-ov",
					"h_tag" => "h2",
					"include_meta" => true,
					"include_intro" => true,
					"include_prep" => true,
				));
				echo "</details>";
			}

			$plan = rkrk_assess_storyboard_chrono_plan($story, $sections);

			foreach (($plan["leading"] ?? array()) as $secName => $fields) {
				if (!is_array($fields)) {
					continue;
				}
				$renderSectionFields((string) $secName, $fields, $opts);
			}

			foreach (($plan["phases"] ?? array()) as $phase) {
				if (!is_array($phase)) {
					continue;
				}
				$phaseTitle = (string) ($phase["title"] ?? "Phase");
				$dur = (int) ($phase["duration_min"] ?? 0);
				$titleShow = $phaseTitle . ($dur > 0 ? (" (" . $dur . " Min)") : "");
				$prep = trim((string) ($phase["prep"] ?? ""));
				$phaseFields = array();
				$entries = array();
				foreach (($phase["entries"] ?? array()) as $entry) {
					if (!is_array($entry)) {
						continue;
					}
					$lab = (string) ($entry["label"] ?? "");
					if ($lab === "") {
						continue;
					}
					$phaseFields[$lab] = $entry["meta"] ?? "text";
					if (($entry["kind"] ?? "") !== "remark") {
						$entries[] = $entry;
					}
				}
				$fo = array_merge($opts, array(
					"section_class" => "pf-sec-phase",
					"phase_prep" => $prep,
					"story_entries" => $entries,
				));
				$renderSectionFields($titleShow, $phaseFields, $fo);
			}

			// n.b.-Punkte analog digital ausgeblendet (nicht auf dem Papierbogen)
			$nbEntries = isset($plan["nb_entries"]) && is_array($plan["nb_entries"]) ? $plan["nb_entries"] : array();
			$nbCodes = array();
			foreach ($nbEntries as $entry) {
				if (!is_array($entry) || (($entry["kind"] ?? "") === "remark")) {
					continue;
				}
				$c = trim((string) ($entry["code"] ?? ""));
				if ($c !== "") {
					$nbCodes[$c] = true;
				}
			}
			if ($nbCodes !== array()) {
				echo "<p class=\"pf-nb-note\">Nicht bewertete Punkte (n.b.) ausgeblendet: "
					. rkrk_paper_h(implode(", ", array_keys($nbCodes))) . "</p>";
			}

			foreach (($plan["trailing"] ?? array()) as $secName => $fields) {
				if (!is_array($fields)) {
					continue;
				}
				$renderSectionFields((string) $secName, $fields, $opts);
			}
		} else {
			foreach ($sections as $secName => $fields) {
				if (!is_array($fields)) {
					continue;
				}
				$renderSectionFields((string) $secName, $fields, $opts);
			}
		}

		$footerNote = "Formular erzeugt aus Template · Kreuzen Sie zutreffende Felder an bzw. tragen Sie Werte in die Linien ein.";
		if ($storyTitle !== "") {
			$footerNote = "Formular · Storyboard «" . $storyTitle . "» · Reihenfolge wie digitale Prüfung · n.b.-Punkte ausgeblendet.";
		}
		echo "<div class=\"pf-footer\">" . rkrk_paper_h($footerNote) . "</div>";
		rkrk_paper_emit_page_end($opts);
	}

	/**
	 * Bild-URL für Quiz-Frage (gleiche Logik wie Lesemodus).
	 */
	function rkrk_paper_quiz_question_image_url(string $rawPath, string $imageBase): string {
		$rawPath = trim($rawPath);
		if ($rawPath === "") {
			return "";
		}
		if (function_exists("rkrk_dga_rh_image_url")) {
			return rkrk_dga_rh_image_url($imageBase, $rawPath);
		}
		$b = rtrim(str_replace("\\", "/", $imageBase), "/");
		$p = ltrim(preg_replace('#^(?:quiz/)?images/#i', "", str_replace("\\", "/", $rawPath)), "/");
		return $b . "/" . $p;
	}

	/**
	 * Antwort-Hinweis für Quiz-Papier (gleiche Logik wie Live-Quiz in rkrk_digitalassess.php).
	 *
	 * @param mixed $loesRaw Loesungsauswahl (String oder Array)
	 * @return array{text: string, multi: bool}|null
	 */
	function rkrk_paper_quiz_answer_mode_hint(string $sol, int $optionCount, $loesRaw) {
		$solTrim = trim($sol);
		$loesStr = is_array($loesRaw) ? implode("|", $loesRaw) : (string) $loesRaw;
		$isJaNein = ($solTrim === "Ja" || $solTrim === "Nein")
			|| preg_match("/^(ja|nein)$/i", $solTrim)
			|| preg_match("/^ja\s*\|\s*nein$/i", $loesStr);
		$hasMc = $optionCount > 0;

		if (!$hasMc && !$isJaNein) {
			return null;
		}
		if ($isJaNein) {
			return array(
				"text" => "Genau eine Antwort ist richtig (Ja oder Nein).",
				"multi" => false,
			);
		}
		if (strpos($solTrim, "|") !== false) {
			return array(
				"text" => "Mehrfachauswahl: es können mehrere Antworten zutreffen.",
				"multi" => true,
			);
		}
		return array(
			"text" => "Genau eine Antwort ist richtig.",
			"multi" => false,
		);
	}

	function rkrk_paper_echo_quiz_answer_mode_hint(string $sol, int $optionCount, $loesRaw): void {
		$hint = rkrk_paper_quiz_answer_mode_hint($sol, $optionCount, $loesRaw);
		if ($hint === null) {
			return;
		}
		$cls = "pf-q-answer-hint" . (!empty($hint["multi"]) ? " pf-q-answer-hint--multi" : " pf-q-answer-hint--single");
		echo "<div class=\"" . $cls . "\">" . rkrk_paper_h($hint["text"]) . "</div>";
	}

	function rkrk_paper_echo_quiz_image_block(string $rawPath, string $qNr, string $caption, string $imgBase, bool $showImages): void {
		$rawPath = trim($rawPath);
		if ($rawPath === "" || !$showImages) {
			return;
		}
		if ($imgBase === "") {
			return;
		}
		$src = rkrk_paper_quiz_question_image_url($rawPath, $imgBase);
		if ($src === "") {
			return;
		}
		echo "<div class=\"pf-q-img\"><img src=\"" . rkrk_paper_h($src) . "\" alt=\"" . rkrk_paper_h($caption . " " . $qNr) . "\"></div>";
	}

	function rkrk_paper_quiz_parse_answer_parts(string $raw): array {
		$parts = array();
		foreach (explode("|", $raw) as $piece) {
			$piece = trim($piece);
			if ($piece !== "") {
				$parts[] = $piece;
			}
		}
		return $parts;
	}

	function rkrk_paper_quiz_single_part_matches_option(string $part, string $letter, string $optText): bool {
		$part = trim($part);
		if ($part === "") {
			return false;
		}
		if (strcasecmp($part, $letter) === 0) {
			return true;
		}
		if (preg_match("/^" . preg_quote($letter, "/") . "[\)\.\:\s]/iu", $part)) {
			return true;
		}
		if (strcasecmp($part, trim($optText)) === 0) {
			return true;
		}
		$norm = preg_replace("/\s+/u", " ", mb_strtolower($part, "UTF-8"));
		$normOpt = preg_replace("/\s+/u", " ", mb_strtolower(trim($optText), "UTF-8"));
		return $normOpt !== "" && $norm === $normOpt;
	}

	function rkrk_paper_quiz_option_is_correct(string $sol, string $letter, string $optText): bool {
		$s = trim($sol);
		if ($s === "") {
			return false;
		}
		if (strpos($s, "|") !== false) {
			foreach (rkrk_paper_quiz_parse_answer_parts($s) as $part) {
				if (rkrk_paper_quiz_single_part_matches_option($part, $letter, $optText)) {
					return true;
				}
			}
			return false;
		}
		return rkrk_paper_quiz_single_part_matches_option($s, $letter, $optText);
	}

	function rkrk_paper_quiz_ja_nein_is_correct(string $sol, string $label): bool {
		$s = mb_strtolower(trim($sol), "UTF-8");
		$l = mb_strtolower(trim($label), "UTF-8");
		if ($s === "" || $l === "") {
			return false;
		}
		if ($l === "ja") {
			return $s === "ja" || $s === "j" || strpos($s, "ja") === 0;
		}
		if ($l === "nein") {
			return $s === "nein" || $s === "n" || strpos($s, "nein") === 0;
		}
		return strcasecmp($s, $l) === 0;
	}

	/**
	 * @param array<string, mixed> $assessment
	 * @param array<int, array<string, mixed>> $questions
	 * @param array<string, mixed> $opts only_active, show_solutions; optional paper_drive_questions (voller Fragenpool für DriveLocation-Ableitung)
	 */
	function rkrk_paper_render_quiz_form(array $assessment, array $questions, array $opts = array()) {
		$onlyActive = !isset($opts["only_active"]) || (bool) $opts["only_active"];
		$showSolutions = !empty($opts["show_solutions"]);

		$rows = array();
		foreach ($questions as $idx => $qRow) {
			if (!is_array($qRow) || trim((string) ($qRow["Frage"] ?? "")) === "") {
				continue;
			}
			if ($onlyActive && function_exists("rkrk_assess_question_is_quiz_live")) {
				if (!rkrk_assess_question_is_quiz_live($qRow)) {
					continue;
				}
			}
			$rows[] = $qRow;
		}

		$title = trim((string) ($assessment["Titel"] ?? "")) !== "" ? (string) $assessment["Titel"] : "Quiz";
		$sub = ($showSolutions ? "Lösungspapier" : "Papierformular") . " · " . count($rows) . " Fragen";
		if (!empty($opts["selection_meta"]) && is_array($opts["selection_meta"])) {
			$sm = $opts["selection_meta"];
			$poolC = (int) ($sm["pool_count"] ?? 0);
			$pickN = (int) ($sm["pick_n"] ?? 0);
			$mode = (string) ($sm["mode"] ?? "");
			$sub .= " · Pool " . $poolC;
			if ($mode === "sample") {
				$sub .= ", stochastische Auswahl wie Live-Quiz (" . $pickN . " Fragen laut Setup";
				$smDrone = function_exists("rkrk_assess_normalize_drone")
					? rkrk_assess_normalize_drone((string) ($sm["drone"] ?? "Alle"))
					: trim((string) ($sm["drone"] ?? "Alle"));
				if ($smDrone !== "" && $smDrone !== "Alle") {
					$sub .= ", allgemein + «" . rkrk_paper_h((string) ($sm["drone"] ?? "")) . "»";
				} else {
					$sub .= ", nur allgemeine Fragen";
				}
				$sub .= ")";
			} elseif ($mode === "pool") {
				$sub .= ", alle zulässigen Fragen (Kanal " . rkrk_paper_h((string) ($sm["setting"] ?? "")) . ", Drohne «" . rkrk_paper_h((string) ($sm["drone"] ?? "Alle")) . "»)";
			}
		} elseif (!empty($assessment["AnzQuestions"])) {
			$sub .= " (max. " . (int) $assessment["AnzQuestions"] . " im Durchgang)";
		}
		if (isset($opts["paper_show_images"])) {
			if ($showSolutions) {
				$sub .= !empty($opts["paper_show_images"]) ? " · mit Frage- und Lösungsbildern" : " · ohne Bilder";
			} else {
				$sub .= !empty($opts["paper_show_images"]) ? " · mit Frage-Bildern" : " · ohne Bilder";
			}
		}
		$droneDisp = isset($opts["paper_drone_filter"]) ? trim((string) $opts["paper_drone_filter"]) : "Alle";
		$droneN = function_exists("rkrk_assess_normalize_drone") ? rkrk_assess_normalize_drone($droneDisp) : $droneDisp;
		if ($droneN !== "" && $droneN !== "Alle") {
			$sub .= " · Drohne «" . rkrk_paper_h($droneDisp) . "»";
		}

		$opts["paper_is_solution"] = $showSolutions;
		rkrk_paper_emit_page_start($title, $sub, $opts);

		echo "<div class=\"pf-meta\">";
		echo "<div><span class=\"pf-meta-cap\">Name</span><div class=\"pf-line\"></div></div>";
		echo "<div><span class=\"pf-meta-cap\">Datum</span><div class=\"pf-line\"></div></div>";
		echo "<div><span class=\"pf-meta-cap\">Drohnentyp</span>";
		$dHd = isset($opts["paper_drone_filter"]) ? trim((string) $opts["paper_drone_filter"]) : "Alle";
		$dHn = function_exists("rkrk_assess_normalize_drone") ? rkrk_assess_normalize_drone($dHd) : $dHd;
		if ($dHn !== "" && $dHn !== "Alle") {
			echo "<div class=\"pf-meta-drone-val\">" . rkrk_paper_h($dHd) . "</div>";
		} else {
			echo "<div class=\"pf-line\"></div>";
		}
		echo "</div>";
		echo "</div>";

		if (count($rows) === 0) {
			echo "<p>Keine Fragen im Pool.</p>";
			rkrk_paper_emit_page_end($opts);
			return;
		}

		$drivePool = $rows;
		if (!empty($opts["paper_drive_questions"]) && is_array($opts["paper_drive_questions"])) {
			$drivePool = $opts["paper_drive_questions"];
		}
		$driveEff = "";
		if (function_exists("rkrk_assess_effective_drive_location")) {
			$driveEff = rkrk_assess_effective_drive_location($assessment, $drivePool);
		} elseif (isset($assessment["DriveLocation"])) {
			$driveEff = trim((string) $assessment["DriveLocation"]);
		}

		echo "<div class=\"pf-quiz-grid\">";
		foreach ($rows as $idx => $qRow) {
			$paperNr = (int) $idx + 1;
			$refNr = trim((string) ($qRow["Nr"] ?? ""));
			$qNr = (string) $paperNr;
			$loesRaw = $qRow["Loesungsauswahl"] ?? "";
			if (is_array($loesRaw)) {
				$optsList = array_values(array_filter(array_map("trim", $loesRaw), function ($v) {
					return $v !== "";
				}));
			} else {
				$optsList = array_values(array_filter(array_map("trim", explode("|", (string) $loesRaw)), function ($v) {
					return $v !== "";
				}));
			}
			$sol = trim((string) ($qRow["Loesung"] ?? ""));
			$imgBase = isset($opts["quiz_image_base"]) ? (string) $opts["quiz_image_base"] : "";
			$wantImg = !empty($opts["paper_show_images"]);

			echo "<div class=\"pf-q\">";
			$metaBits = array();
			if (trim((string) ($qRow["Bereich"] ?? "")) !== "") {
				$metaBits[] = "Bereich: " . (string) $qRow["Bereich"];
			}
			$dRow = trim((string) ($qRow["Drone"] ?? ""));
			if ($dRow !== "") {
				$dNorm = function_exists("rkrk_assess_normalize_drone") ? rkrk_assess_normalize_drone($dRow) : $dRow;
				if ($dNorm !== "" && strcasecmp($dNorm, "Alle") !== 0) {
					$metaBits[] = "Drohnentyp: " . $dRow;
				}
			}
			echo "<p class=\"pf-q-text\"><span class=\"pf-q-nr\">" . rkrk_paper_h($qNr) . ".</span> " . rkrk_paper_h((string) $qRow["Frage"]);
			if ($refNr !== "" && $refNr !== $qNr) {
				echo " <span class=\"pf-q-ref\" title=\"Referenz-Nr. im Fragenpool\">Ref. " . rkrk_paper_h($refNr) . "</span>";
			}
			echo "</p>";
			if (count($metaBits) > 0) {
				echo "<div class=\"pf-q-meta\">" . rkrk_paper_h(implode(" · ", $metaBits)) . "</div>";
			}
			rkrk_paper_echo_quiz_answer_mode_hint($sol, count($optsList), $loesRaw);
			$fbRaw = trim((string) ($qRow["Frage-Bild"] ?? ""));
			$fb = function_exists("rkrk_dga_rh_with_drive") ? rkrk_dga_rh_with_drive($fbRaw, $driveEff) : $fbRaw;
			rkrk_paper_echo_quiz_image_block($fb, $qNr, "Frage-Bild", $imgBase, $wantImg);

			if (count($optsList) > 0) {
				echo "<div class=\"pf-chk-row\">";
				foreach ($optsList as $oi => $optText) {
					$letter = chr(65 + (int) $oi);
					$chkCls = "pf-chk";
					if ($showSolutions && rkrk_paper_quiz_option_is_correct($sol, $letter, $optText)) {
						$chkCls .= " pf-chk-correct";
					}
					echo "<label class=\"" . $chkCls . "\"><span class=\"pf-box\"></span><span><strong>" . rkrk_paper_h($letter) . ")</strong> " . rkrk_paper_h($optText) . "</span></label>";
				}
				echo "</div>";
			} elseif (preg_match("/^(ja|nein)$/i", $sol) || preg_match("/^ja\s*\|\s*nein$/i", is_array($loesRaw) ? implode("|", $loesRaw) : (string) $loesRaw)) {
				echo "<div class=\"pf-chk-row\">";
				foreach (array("Ja", "Nein") as $ynLbl) {
					$chkCls = "pf-chk";
					if ($showSolutions && rkrk_paper_quiz_ja_nein_is_correct($sol, $ynLbl)) {
						$chkCls .= " pf-chk-correct";
					}
					echo "<label class=\"" . $chkCls . "\"><span class=\"pf-box\"></span><span>" . rkrk_paper_h($ynLbl) . "</span></label>";
				}
				echo "</div>";
			} else {
				echo "<div class=\"pf-line-write\"></div>";
			}

			if ($showSolutions) {
				$hasSol = $sol !== "" || trim((string) ($qRow["Loesungstext"] ?? "")) !== "" || trim((string) ($qRow["Loesungs-Bild"] ?? "")) !== "";
				if ($hasSol) {
					echo "<div class=\"pf-sol-block\">";
					echo "<div class=\"pf-sol-title\">Lösung</div>";
					if ($sol !== "") {
						echo "<div class=\"pf-hint\" style=\"margin:0 0 4px;\"><strong>Antwort:</strong> " . rkrk_paper_h($sol) . "</div>";
					}
					$loesTxt = trim((string) ($qRow["Loesungstext"] ?? ""));
					if ($loesTxt !== "") {
						echo "<div class=\"pf-hint\" style=\"margin:0 0 4px;\">" . nl2br(rkrk_paper_h($loesTxt)) . "</div>";
					}
					$lbRaw = trim((string) ($qRow["Loesungs-Bild"] ?? ""));
					$lb = function_exists("rkrk_dga_rh_with_drive") ? rkrk_dga_rh_with_drive($lbRaw, $driveEff) : $lbRaw;
					rkrk_paper_echo_quiz_image_block($lb, $qNr, "Lösungs-Bild", $imgBase, $wantImg);
					echo "</div>";
				}
			}

			echo "</div>";
		}
		echo "</div>";

		if ($showSolutions) {
			$foot = "Lösungspapier — nur für Prüfer/Instruktor · grün markiert = richtige Antwort.";
		} else {
			$foot = "Antworten durch Ankreuzen oder Eintragen · ohne Lösungshinweise (Prüfungsmodus).";
		}
		$paperIdFoot = isset($opts["paper_form_id"]) ? trim((string) $opts["paper_form_id"]) : "";
		if ($paperIdFoot !== "") {
			$dispFoot = function_exists("rkrk_dga_paper_form_id_display")
				? rkrk_dga_paper_form_id_display($paperIdFoot)
				: $paperIdFoot;
			$kindFoot = $showSolutions ? "Lösung zu Fragebogen-Nr. " : "Fragebogen-Nr. ";
			$foot = $kindFoot . $dispFoot . " · " . $foot;
		}
		echo "<div class=\"pf-footer\">" . rkrk_paper_h($foot) . "</div>";
		rkrk_paper_emit_page_end($opts);
	}

	/**
	 * @param array<string, mixed> $opts back_url, logo_src
	 */
	function rkrk_paper_emit_page_start($title, $subtitle, array $opts = array()) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		if (!headers_sent()) {
			header("Content-Type: text/html; charset=UTF-8");
		}
		echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
		$isSolution = !empty($opts["paper_is_solution"]);
		$pageKind = $isSolution ? "Lösungspapier" : "Papierformular";
		$paperIdRaw = isset($opts["paper_form_id"]) ? trim((string) $opts["paper_form_id"]) : "";
		$paperIdDisp = "";
		if ($paperIdRaw !== "") {
			$paperIdDisp = function_exists("rkrk_dga_paper_form_id_display")
				? rkrk_dga_paper_form_id_display($paperIdRaw)
				: $paperIdRaw;
		}
		$titleExtra = $paperIdDisp !== "" ? (" — " . $paperIdDisp) : "";
		echo "<title>" . rkrk_paper_h($title) . " — " . rkrk_paper_h($pageKind) . rkrk_paper_h($titleExtra) . "</title>";
		if (function_exists("rkrk_assess_favicon_link_tags_html")) {
			echo rkrk_assess_favicon_link_tags_html();
		} elseif (function_exists("rkrk_assess_emit_favicon_link_tags")) {
			rkrk_assess_emit_favicon_link_tags();
		}
		rkrk_paper_echo_styles();
		$bodyCls = "pf-body pf-a4" . ($isSolution ? " pf-solution-mode" : "");
		echo "</head><body class=\"" . rkrk_paper_h($bodyCls) . "\">";

		echo "<div class=\"pf-noprint\">";
		echo "<button type=\"button\" onclick=\"window.print()\">Drucken</button>";
		if (!empty($opts["paper_quiz_mode_toggle"]) && is_array($opts["paper_quiz_mode_toggle"])) {
			$mt = $opts["paper_quiz_mode_toggle"];
			$he = isset($mt["exam_href"]) ? (string) $mt["exam_href"] : "";
			$hs = isset($mt["solution_href"]) ? (string) $mt["solution_href"] : "";
			$mcur = isset($mt["mode"]) ? (string) $mt["mode"] : "exam";
			if ($he !== "" && $hs !== "") {
				echo "<span class=\"pf-paper-mode-toggle\" role=\"group\" aria-label=\"Papierart\">";
				echo "<a href=\"" . rkrk_paper_h($he) . "\"" . ($mcur === "exam" ? " class=\"pf-active\"" : "") . ">Prüfungsbogen</a>";
				echo "<span aria-hidden=\"true\">|</span>";
				echo "<a href=\"" . rkrk_paper_h($hs) . "\"" . ($mcur === "solution" ? " class=\"pf-active\"" : "") . ">Lösungspapier</a>";
				echo "</span>";
			}
		}
		if (!empty($opts["paper_quiz_image_toggle"]) && is_array($opts["paper_quiz_image_toggle"])) {
			$tgl = $opts["paper_quiz_image_toggle"];
			$hw = isset($tgl["with_href"]) ? (string) $tgl["with_href"] : "";
			$ho = isset($tgl["without_href"]) ? (string) $tgl["without_href"] : "";
			$cur = isset($tgl["mode"]) ? (string) $tgl["mode"] : "without";
			if ($hw !== "" && $ho !== "") {
				$imgAria = $isSolution ? "Frage- und Lösungsbilder" : "Fragenbilder";
				echo "<span class=\"pf-paper-img-toggle\" role=\"group\" aria-label=\"" . rkrk_paper_h($imgAria) . "\">";
				echo "<a href=\"" . rkrk_paper_h($hw) . "\"" . ($cur === "with" ? " class=\"pf-active\"" : "") . ">Mit Bildern</a>";
				echo "<span aria-hidden=\"true\">|</span>";
				echo "<a href=\"" . rkrk_paper_h($ho) . "\"" . ($cur === "without" ? " class=\"pf-active\"" : "") . ">Ohne Bilder</a>";
				echo "</span>";
			}
		}
		if (!empty($opts["paper_quiz_drone_picker"]) && is_array($opts["paper_quiz_drone_picker"])) {
			$dp = $opts["paper_quiz_drone_picker"];
			$items = isset($dp["items"]) && is_array($dp["items"]) ? $dp["items"] : array();
			if (count($items) > 1) {
				echo "<span class=\"pf-paper-drone-wrap\" role=\"group\" aria-label=\"Drohnentyp für Fragenpool\">";
				echo "<label for=\"pf-paper-drone\">Drohne ";
				echo "<select id=\"pf-paper-drone\" class=\"pf-paper-drone-select\" onchange=\"if(this.value){location.href=this.value;}\">";
				foreach ($items as $it) {
					if (!is_array($it)) {
						continue;
					}
					$h = isset($it["href"]) ? (string) $it["href"] : "";
					$lab = isset($it["drone"]) ? (string) $it["drone"] : "";
					if ($h === "" || $lab === "") {
						continue;
					}
					$sel = !empty($it["active"]) ? " selected" : "";
					echo "<option value=\"" . rkrk_paper_h($h) . "\"" . $sel . ">" . rkrk_paper_h($lab) . "</option>";
				}
				echo "</select></label></span>";
			}
		}
		if (!empty($opts["paper_new_href"])) {
			echo "<a href=\"" . rkrk_paper_h((string) $opts["paper_new_href"]) . "\" title=\"Neue Zufallsauswahl mit neuer Fragebogen-Nummer\">Neuer Bogen</a>";
		}
		echo "</div>";

		echo "<header class=\"pf-header\"><div>";
		echo "<h1>" . rkrk_paper_h($title) . "</h1>";
		if ($subtitle !== "") {
			echo "<div class=\"pf-sub\">" . rkrk_paper_h($subtitle) . "</div>";
		}
		echo "</div>";
		$paperIdRawH = isset($opts["paper_form_id"]) ? trim((string) $opts["paper_form_id"]) : "";
		if ($paperIdRawH !== "") {
			$paperIdDispH = function_exists("rkrk_dga_paper_form_id_display")
				? rkrk_dga_paper_form_id_display($paperIdRawH)
				: $paperIdRawH;
			$idCap = !empty($opts["paper_is_solution"]) ? "Lösung zu Fragebogen-Nr." : "Fragebogen-Nr.";
			echo "<div class=\"pf-form-id\" title=\"" . rkrk_paper_h($paperIdRawH) . "\">";
			echo "<span class=\"pf-form-id-label\">" . rkrk_paper_h($idCap) . "</span>";
			echo "<span class=\"pf-form-id-num\">" . rkrk_paper_h($paperIdDispH) . "</span>";
			echo "<span class=\"pf-form-id-code\">" . rkrk_paper_h($paperIdRawH) . "</span>";
			$droneId = isset($opts["paper_drone_filter"]) ? trim((string) $opts["paper_drone_filter"]) : "";
			$droneIdN = function_exists("rkrk_assess_normalize_drone") ? rkrk_assess_normalize_drone($droneId) : $droneId;
			if ($droneIdN !== "" && $droneIdN !== "Alle") {
				echo "<span class=\"pf-form-id-drone\">" . rkrk_paper_h($droneId) . "</span>";
			}
			echo "</div>";
		}
		if (!empty($opts["logo_src"])) {
			echo "<div class=\"pf-logo\"><img src=\"" . rkrk_paper_h((string) $opts["logo_src"]) . "\" alt=\"\"></div>";
		}
		echo "</header>";
	}

	function rkrk_paper_emit_page_end(array $opts = array()) {
		echo "</body></html>";
		exit;
	}

	/**
	 * @param string $templateFolder
	 * @param string $file
	 * @param array<string, mixed> $opts
	 */
	function rkrk_assess_paper_form_from_template_file(string $templateFolder, $file, array $opts = array()) {
		$file = basename($file);
		if ($file === "" || !preg_match('/\.json$/i', $file)) {
			return false;
		}
		$path = rtrim($templateFolder, "/\\") . "/" . $file;
		if (!is_file($path)) {
			return false;
		}
		$tpl = json_decode((string) file_get_contents($path), true);
		if (!is_array($tpl) || !isset($tpl["sections"])) {
			return false;
		}
		rkrk_paper_render_template_form($tpl, $opts);
		return true;
	}
}
