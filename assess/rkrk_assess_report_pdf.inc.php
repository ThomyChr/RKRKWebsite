<?php
/**
 * Abschluss-Reports unter data/assess/results/reports/ (Theorie + Checkliste).
 * Portable über $QuizLocResults / $rkrkDataRoot (frog, portal, andere Hosts).
 * TCPDF bevorzugt; Dompdf optionaler Fallback; sonst HTML (.html).
 * Dateiname = gleicher Stamm wie das zugehörige JSON (nur andere Endung).
 */
$__rkrkResultStemInc = __DIR__ . "/rkrk_assess_result_stem.inc.php";
if (is_readable($__rkrkResultStemInc)) {
	include_once $__rkrkResultStemInc;
}
unset($__rkrkResultStemInc);

/** Minimale Fallbacks, falls result_stem.inc.php auf dem Host noch fehlt (Teil-Sync). */
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
	function rkrk_assess_report_stem_from_result(string $resultStemOrPath): string {
		return rkrk_assess_result_stem_sanitize($resultStemOrPath);
	}
}

if (!function_exists("rkrk_assess_reports_dir")) {
	function rkrk_assess_reports_dir(): string {
		$kind = strtolower(trim((string) ($GLOBALS["rkrk_assess_reports_kind"] ?? "assess")));
		$base = "";
		if ($kind === "live") {
			if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
				$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/results/";
			} elseif (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
				$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/"));
				$base = rtrim(str_replace("\\", "/", (string) $parent), "/") . "/";
			}
		}
		if ($base === "") {
			if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
				$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/";
			} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
				$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/results/";
			} elseif (isset($GLOBALS["rkrkAssessResults"]) && trim((string) $GLOBALS["rkrkAssessResults"]) !== "") {
				$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkAssessResults"]), "/"));
				$base = rtrim(str_replace("\\", "/", (string) $parent), "/") . "/";
			}
		}
		if ($base === "") {
			return "";
		}
		$dir = $base . "reports/";
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_exams_pdf_dir")) {
	/** @deprecated Alias für rkrk_assess_reports_dir() */
	function rkrk_assess_exams_pdf_dir(): string {
		return rkrk_assess_reports_dir();
	}
}

if (!function_exists("rkrk_assess_report_basename_safe")) {
	function rkrk_assess_report_basename_safe(string $stem): string {
		return rkrk_assess_result_stem_sanitize($stem);
	}
}

if (!function_exists("rkrk_assess_report_basename_from_json_stem")) {
	/** Report-Dateiname = exakt der JSON-Stamm. */
	function rkrk_assess_report_basename_from_json_stem(string $stem): string {
		return rkrk_assess_report_stem_from_result($stem);
	}
}

if (!function_exists("rkrk_assess_report_basename_from_exam_json")) {
	/** Gleicher Stamm wie exams/{code}_{pilot}_{YYYYMMDD}.json */
	function rkrk_assess_report_basename_from_exam_json(string $code, string $name = "", ?array $session = null): string {
		if (function_exists("rkrk_assess_exam_resolve_storage_path")) {
			$path = rkrk_assess_exam_resolve_storage_path($code, $name, $session);
			if ($path !== "") {
				return rkrk_assess_report_stem_from_result(pathinfo($path, PATHINFO_FILENAME));
			}
		}
		if (!function_exists("rkrk_assess_exam_file_path")) {
			return "";
		}
		$stamp = function_exists("rkrk_assess_exam_completion_stamp_from_session")
			? rkrk_assess_exam_completion_stamp_from_session($session)
			: date("Ymd");
		$path = rkrk_assess_exam_file_path($code, $name, $stamp, true, $session);
		if ($path === "") {
			return "";
		}
		return rkrk_assess_report_stem_from_result(pathinfo($path, PATHINFO_FILENAME));
	}
}

if (!function_exists("rkrk_assess_report_copyright_footer_html")) {
	function rkrk_assess_report_copyright_footer_html(): string {
		$year = date("Y");
		if (function_exists("rkrk_assess_copyright_holder")) {
			$holder = rkrk_assess_copyright_holder();
		} elseif (isset($GLOBALS["copyright"]) && trim((string) $GLOBALS["copyright"]) !== "") {
			$holder = trim((string) $GLOBALS["copyright"]);
		} else {
			$holder = "drohnenrehkitzrettung.ch";
		}
		$h = htmlspecialchars($holder, ENT_QUOTES, "UTF-8");
		return "<footer class=\"rkrk-report-copyright\">"
			. "&copy; " . $year . " " . $h . ". "
			. "Die Inhalte sind ausschliesslich f&uuml;r vereinsinterne Zwecke bestimmt."
			. "</footer>";
	}
}

if (!function_exists("rkrk_assess_report_append_copyright_footer")) {
	function rkrk_assess_report_append_copyright_footer(string $html): string {
		// Nur vorhandenes Markup zählen — CSS-Klasse .rkrk-report-copyright allein reicht nicht
		if (preg_match('/<(?:footer|div)\b[^>]*\bid=["\']rkrk-report-copyright["\']/i', $html)
			|| preg_match('/<(?:footer|div)\b[^>]*\bclass=["\'][^"\']*\brkrk-report-copyright\b/i', $html)
		) {
			return $html;
		}
		$footer = rkrk_assess_report_copyright_footer_html();
		if (preg_match("/<\/body>/i", $html)) {
			return (string) preg_replace("/<\/body>/i", $footer . "</body>", $html, 1);
		}
		return $html . $footer;
	}
}

if (!function_exists("rkrk_assess_try_load_dompdf")) {
	/** @return bool */
	function rkrk_assess_try_load_dompdf(): bool {
		if (class_exists("Dompdf\\Dompdf", false)) {
			return true;
		}
		$candidates = array();
		if (isset($GLOBALS["rkrkDataRoot"])) {
			$root = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkDataRoot"]), "/");
			$candidates[] = $root . "/scripts/vendor/dompdf/autoload.inc.php";
			$candidates[] = $root . "/scripts/lib/dompdf/autoload.inc.php";
		}
		$here = str_replace("\\", "/", __DIR__);
		$candidates[] = $here . "/vendor/dompdf/autoload.inc.php";
		$candidates[] = $here . "/../scripts/vendor/dompdf/autoload.inc.php";
		$candidates[] = $here . "/../lib/dompdf/autoload.inc.php";
		foreach ($candidates as $path) {
			if (is_readable($path)) {
				@include_once $path;
				if (class_exists("Dompdf\\Dompdf", false)) {
					return true;
				}
			}
		}
		return false;
	}
}

if (!function_exists("rkrk_assess_try_load_tcpdf")) {
	/** @return bool */
	function rkrk_assess_try_load_tcpdf(): bool {
		if (class_exists("TCPDF", false)) {
			return true;
		}
		$candidates = array();
		if (isset($GLOBALS["rkrkDataRoot"])) {
			$root = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkDataRoot"]), "/");
			$candidates[] = $root . "/scripts/vendor/tcpdf/tcpdf.php";
			$candidates[] = $root . "/scripts/lib/tcpdf/tcpdf.php";
		}
		$here = str_replace("\\", "/", __DIR__);
		// public/vendor (mit Assess-Code deploybar) + assess/scripts
		$candidates[] = $here . "/vendor/tcpdf/tcpdf.php";
		$candidates[] = $here . "/../scripts/vendor/tcpdf/tcpdf.php";
		$candidates[] = $here . "/../lib/tcpdf/tcpdf.php";
		$candidates[] = $here . "/../scripts/lib/tcpdf/tcpdf.php";
		// frog: public → …/rkropen/rkrk/scripts/vendor (Datenroot ≠ onRKRS ../../)
		$candidates[] = $here . "/../../../rkrk/scripts/vendor/tcpdf/tcpdf.php";
		$candidates[] = $here . "/../../../rkrk/scripts/lib/tcpdf/tcpdf.php";
		// portal.rehkitzrettung.ch: TCPDF unter DocumentRoot/lib/tcpdf (wie map/export_map_pdf.php)
		$candidates[] = $here . "/../../../lib/tcpdf/tcpdf.php";
		$candidates[] = $here . "/../../../../lib/tcpdf/tcpdf.php";
		$docRoot = isset($_SERVER["DOCUMENT_ROOT"]) ? rtrim(str_replace("\\", "/", (string) $_SERVER["DOCUMENT_ROOT"]), "/") : "";
		if ($docRoot !== "") {
			$candidates[] = $docRoot . "/lib/tcpdf/tcpdf.php";
			$candidates[] = $docRoot . "/includes/tcpdf/tcpdf.php";
		}
		foreach ($candidates as $path) {
			if (is_readable($path)) {
				@include_once $path;
				if (class_exists("TCPDF", false)) {
					return true;
				}
			}
		}
		return false;
	}
}

if (!function_exists("rkrk_assess_report_tcpdf_fonts_dirs")) {
	/**
	 * Kandidaten für TCPDF-Font-Verzeichnisse (DejaVu muss mit dem Assess-Code deploybar sein).
	 * Portal lädt oft TCPDF aus DocumentRoot/lib/tcpdf ohne DejaVu — dann greift public/vendor/tcpdf/fonts
	 * bzw. data/assess/tcpdf_fonts (geteilt über Datenroot).
	 *
	 * @return list<string>
	 */
	function rkrk_assess_report_tcpdf_fonts_dirs(): array {
		$dirs = array();
		$here = str_replace("\\", "/", __DIR__);
		$dirs[] = $here . "/vendor/tcpdf/fonts";
		$dirs[] = $here . "/../scripts/vendor/tcpdf/fonts";
		$dirs[] = $here . "/../data/assess/tcpdf_fonts";
		$dirs[] = $here . "/../../data/assess/tcpdf_fonts";
		$dirs[] = $here . "/../../../data/assess/tcpdf_fonts";
		$dirs[] = $here . "/../../../lib/tcpdf/fonts";
		$dirs[] = $here . "/../../../../lib/tcpdf/fonts";
		if (isset($GLOBALS["rkrkDataRoot"])) {
			$root = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkDataRoot"]), "/");
			$dirs[] = $root . "/assess/tcpdf_fonts";
			$dirs[] = $root . "/scripts/vendor/tcpdf/fonts";
			$dirs[] = $root . "/scripts/lib/tcpdf/fonts";
		}
		$docRoot = isset($_SERVER["DOCUMENT_ROOT"]) ? rtrim(str_replace("\\", "/", (string) $_SERVER["DOCUMENT_ROOT"]), "/") : "";
		if ($docRoot !== "") {
			$dirs[] = $docRoot . "/lib/tcpdf/fonts";
			$dirs[] = $docRoot . "/includes/tcpdf/fonts";
			$dirs[] = $docRoot . "/rkrsplusassessment/assess/public/vendor/tcpdf/fonts";
			$dirs[] = $docRoot . "/rkropen/rkrs/assess/public/vendor/tcpdf/fonts";
		}
		if (defined("K_PATH_FONTS")) {
			$dirs[] = rtrim(str_replace("\\", "/", (string) K_PATH_FONTS), "/");
		}
		$out = array();
		$seen = array();
		foreach ($dirs as $d) {
			$d = rtrim(str_replace("\\", "/", (string) $d), "/");
			if ($d === "" || isset($seen[$d]) || !is_dir($d)) {
				continue;
			}
			$seen[$d] = true;
			$out[] = $d;
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_report_tcpdf_install_dejavu_fonts")) {
	/**
	 * DejaVu-Fonts einmalig nach data/assess/tcpdf_fonts (oder public/vendor/…) holen.
	 * Portal hat oft kein vendor/tcpdf/fonts — ohne DejaVu werden ✓/★/✗ zu «?».
	 */
	function rkrk_assess_report_tcpdf_install_dejavu_fonts(): string {
		$candidates = array();
		$here = str_replace("\\", "/", __DIR__);
		if (isset($GLOBALS["rkrkDataRoot"])) {
			$root = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkDataRoot"]), "/");
			$candidates[] = $root . "/assess/tcpdf_fonts";
		}
		$candidates[] = $here . "/../../data/assess/tcpdf_fonts";
		$candidates[] = $here . "/../data/assess/tcpdf_fonts";
		$candidates[] = $here . "/vendor/tcpdf/fonts";

		$dest = "";
		foreach ($candidates as $dir) {
			$dir = rtrim(str_replace("\\", "/", $dir), "/");
			if ($dir === "") {
				continue;
			}
			if (!is_dir($dir)) {
				@mkdir($dir, 0775, true);
			}
			if (is_dir($dir) && is_writable($dir)) {
				$dest = $dir;
				break;
			}
		}
		if ($dest === "") {
			return "";
		}
		$need = array(
			"dejavusans.php", "dejavusans.z", "dejavusans.ctg.z",
			"dejavusansb.php", "dejavusansb.z", "dejavusansb.ctg.z",
			"dejavusansi.php", "dejavusansi.z", "dejavusansi.ctg.z",
			"dejavusansbi.php", "dejavusansbi.z", "dejavusansbi.ctg.z",
		);
		$missing = false;
		foreach ($need as $fn) {
			if (!is_readable($dest . "/" . $fn)) {
				$missing = true;
				break;
			}
		}
		if (!$missing) {
			return $dest;
		}
		$mirrors = array(
			"https://tchr-frog.synology.me/rkropen/rkrs/assess/public/vendor/tcpdf/fonts",
		);
		if (isset($_SERVER["HTTP_HOST"]) && stripos((string) $_SERVER["HTTP_HOST"], "portal.rehkitzrettung.ch") === false) {
			// lokal/frog: nichts aus dem Netz ziehen, wenn Dateien schon unter vendor liegen
			$local = $here . "/vendor/tcpdf/fonts";
			if (is_readable($local . "/dejavusans.z")) {
				foreach ($need as $fn) {
					$src = $local . "/" . $fn;
					$dst = $dest . "/" . $fn;
					if (is_readable($src) && !is_readable($dst)) {
						@copy($src, $dst);
					}
				}
				if (is_readable($dest . "/dejavusans.z")) {
					return $dest;
				}
			}
		}
		if (!ini_get("allow_url_fopen") && !function_exists("curl_init")) {
			return is_readable($dest . "/dejavusans.z") ? $dest : "";
		}
		foreach ($mirrors as $base) {
			$base = rtrim($base, "/");
			$okAll = true;
			foreach ($need as $fn) {
				$dst = $dest . "/" . $fn;
				if (is_readable($dst) && filesize($dst) > 1000) {
					continue;
				}
				$url = $base . "/" . $fn;
				$bin = false;
				if (function_exists("curl_init")) {
					$ch = curl_init($url);
					curl_setopt_array($ch, array(
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_CONNECTTIMEOUT => 8,
						CURLOPT_TIMEOUT => 60,
						CURLOPT_SSL_VERIFYPEER => true,
					));
					$bin = curl_exec($ch);
					$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					if ($code < 200 || $code >= 300 || !is_string($bin) || strlen($bin) < 1000) {
						$bin = false;
					}
				}
				if ($bin === false) {
					$bin = @file_get_contents($url);
					if (!is_string($bin) || strlen($bin) < 1000) {
						$okAll = false;
						break;
					}
				}
				if (@file_put_contents($dst, $bin) === false) {
					$okAll = false;
					break;
				}
			}
			if ($okAll && is_readable($dest . "/dejavusans.z")) {
				return $dest;
			}
		}
		return is_readable($dest . "/dejavusans.z") ? $dest : "";
	}
}

if (!function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font")) {
	/**
	 * Registriert DejaVu Sans (inkl. Bold/Italic) für TCPDF und gibt den Font-Familiennamen zurück.
	 * Benötigt .php + .z im selben Ordner. Fallback: helvetica (ohne ✓/★/✗).
	 */
	function rkrk_assess_report_tcpdf_ensure_unicode_font(\TCPDF $pdf): string {
		// Portal: fehlende Fonts einmalig aus Mirror/vendor nach data/assess/tcpdf_fonts legen
		if (function_exists("rkrk_assess_report_tcpdf_install_dejavu_fonts")) {
			rkrk_assess_report_tcpdf_install_dejavu_fonts();
		}
		foreach (rkrk_assess_report_tcpdf_fonts_dirs() as $dir) {
			$regular = $dir . "/dejavusans.php";
			$regularZ = $dir . "/dejavusans.z";
			if (!is_readable($regular) || !is_readable($regularZ)) {
				continue;
			}
			try {
				$pdf->AddFont("dejavusans", "", $regular);
				$bold = $dir . "/dejavusansb.php";
				if (is_readable($bold) && is_readable($dir . "/dejavusansb.z")) {
					$pdf->AddFont("dejavusans", "B", $bold);
				}
				$italic = $dir . "/dejavusansi.php";
				if (is_readable($italic) && is_readable($dir . "/dejavusansi.z")) {
					$pdf->AddFont("dejavusans", "I", $italic);
				}
				$bi = $dir . "/dejavusansbi.php";
				if (is_readable($bi) && is_readable($dir . "/dejavusansbi.z")) {
					$pdf->AddFont("dejavusans", "BI", $bi);
				}
				return "dejavusans";
			} catch (\Throwable $e) {
				continue;
			}
		}
		return "helvetica";
	}
}

if (!function_exists("rkrk_assess_report_pdf_ascii_symbol_fallback")) {
	/**
	 * Ersetzt ✓/★/✗ durch ASCII, wenn keine Unicode-Schrift eingebettet werden kann (Portal/Helvetica).
	 */
	function rkrk_assess_report_pdf_ascii_symbol_fallback(string $html): string {
		if ($html === "") {
			return $html;
		}
		$map = array(
			"✓" => "[OK]",
			"✔" => "[OK]",
			"★" => "[*]",
			"☆" => "[*]",
			"✗" => "[X]",
			"✘" => "[X]",
			"×" => "[X]",
			"·" => "|",
		);
		return str_replace(array_keys($map), array_values($map), $html);
	}
}

if (!function_exists("rkrk_assess_report_images_web_dir")) {
	/** Root-relative …/data/assess/images/ für Logo (Fallback, wenn relativ nicht passt). */
	function rkrk_assess_report_images_web_dir(): string {
		if (function_exists("rkrk_assess_quiz_data_assess_web_prefix")) {
			return rkrk_assess_quiz_data_assess_web_prefix() . "images/";
		}
		if (function_exists("rkrk_assess_resolve_web_path_from_script")) {
			$probe = rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme.css");
			$public = rtrim(str_replace("\\", "/", dirname($probe)), "/");
			$host = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "")));
			if ($host !== "" && strpos($host, "portal.rehkitzrettung.ch") !== false) {
				$base = preg_replace('#/assess/public$#', "", $public);
				return rtrim((string) $base, "/") . "/data/assess/images/";
			}
		}
		$pic = isset($GLOBALS["examIconLoc"]) ? (string) $GLOBALS["examIconLoc"] : "";
		if ($pic !== "" && isset($pic[0]) && $pic[0] === "/") {
			return rtrim($pic, "/") . "/";
		}
		return "/rkropen/rkrk/data/assess/images/";
	}
}

if (!function_exists("rkrk_assess_report_logo_src_portable")) {
	/**
	 * Relativer Logo-Pfad ab results/reports/ — gleich auf frog und Portal.
	 * live: …/liveassess/results/reports/ → ../../../assess/images/
	 * assess: …/assess/results/reports/ → ../../images/
	 */
	function rkrk_assess_report_logo_src_portable(): string {
		$logo = isset($GLOBALS["logopic"]) ? basename((string) $GLOBALS["logopic"]) : "rkrs_logo.png";
		if ($logo === "") {
			$logo = "rkrs_logo.png";
		}
		$kind = strtolower(trim((string) ($GLOBALS["rkrk_assess_reports_kind"] ?? "assess")));
		if ($kind === "live") {
			return "../../../assess/images/" . $logo;
		}
		return "../../images/" . $logo;
	}
}

if (!function_exists("rkrk_assess_report_inline_print_css")) {
	/**
	 * Druck-CSS + :root (host-unabhängig), damit reports/*.html ohne /rkropen/-Links stilvoll sind.
	 */
	function rkrk_assess_report_inline_print_css(): string {
		static $cached = null;
		if ($cached !== null) {
			return $cached;
		}
		$parts = array();
		$themeFile = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_theme.css";
		if (is_readable($themeFile)) {
			$all = (string) file_get_contents($themeFile);
			if (preg_match('/:root\s*\{[\s\S]*?\n\}/', $all, $m)) {
				$parts[] = $m[0];
			}
			$pos = strpos($all, "/* Druckansicht");
			if ($pos !== false) {
				$chunk = substr($all, $pos);
				// Layout/UI-Rest nach @media print weglassen
				$cut = strpos($chunk, "/* Layout-2023");
				if ($cut !== false) {
					$chunk = substr($chunk, 0, $cut);
				}
				$parts[] = trim($chunk);
			}
		}
		$useRkrs = (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode())
			|| (!empty($GLOBALS["onRKRS"]));
		if ($useRkrs) {
			$rkrsFile = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_theme_rkrs.css";
			if (is_readable($rkrsFile)) {
				$rk = (string) file_get_contents($rkrsFile);
				if (preg_match('/:root\s*\{[\s\S]*?\n\}/', $rk, $m)) {
					$parts[] = $m[0];
				}
			}
		}
		if ($parts === array()) {
			$parts[] = "body.rkrk-assess-print{font-family:Segoe UI,Arial,sans-serif;margin:40px;color:#1a1a1a}"
				. "body.rkrk-assess-print .print-header{position:relative;min-height:86px;margin-bottom:30px}"
				. "body.rkrk-assess-print .print-title{font-size:28px;font-weight:700}"
				. "body.rkrk-assess-print .print-logo{position:absolute;top:0;right:0}"
				. "body.rkrk-assess-print .print-logo img{height:80px}"
				. "body.rkrk-assess-print .section{margin-top:25px;border-top:3px solid #666;padding-top:12px}"
				. "body.rkrk-assess-print .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 40px}"
				. "body.rkrk-assess-print .field{border-bottom:1px solid #ccc;padding-bottom:6px}"
				. "body.rkrk-assess-print .label{font-weight:600}"
				. "body.rkrk-assess-print .no-print{display:none}";
		}
		$cached = implode("\n", $parts);
		return $cached;
	}
}

if (!function_exists("rkrk_assess_report_html_make_portable")) {
	/**
	 * Gespeicherte Reports unter data/…/reports/ brauchen host-korrekte Assets (Portal ≠ frog /rkropen/).
	 * Checklisten-Print: Theme-Links entfernen, Druck-CSS inlinen, Logo-URL korrigieren.
	 */
	function rkrk_assess_report_html_make_portable(string $html): string {
		$html = trim($html);
		if ($html === "") {
			return $html;
		}
		// Bereits digitaler Report mit Inline-Style und ohne Theme-Link: nur Logo/Pfade ggf. korrigieren
		$isChecklistPrint = (stripos($html, "rkrk-assess-print") !== false)
			|| (stripos($html, "rkrk_assess_theme") !== false);

		if ($isChecklistPrint) {
			// Externe Theme-CSS (oft /rkropen/… und auf Portal 404) entfernen
			$html = preg_replace(
				'/<link\b[^>]*rkrk_assess_theme(?:_rkrs)?\.css[^>]*>\s*/i',
				"",
				$html
			);
			$css = rkrk_assess_report_inline_print_css();
			$styleTag = "<style id=\"rkrk-report-print-css\">\n" . $css . "\n</style>\n";
			if (stripos($html, "rkrk-report-print-css") === false) {
				if (preg_match('/<\/head>/i', $html)) {
					$html = (string) preg_replace('/<\/head>/i', $styleTag . "</head>", $html, 1);
				} elseif (preg_match('/<body\b[^>]*>/i', $html, $bm)) {
					$html = (string) preg_replace('/<body\b[^>]*>/i', $bm[0] . $styleTag, $html, 1);
				} else {
					$html = $styleTag . $html;
				}
			}
			// Druck-Optionen-Form funktioniert unter /reports/ nicht
			$html = preg_replace(
				'/<form\b[^>]*class=[\'"][^\'"]*print-options[^\'"]*[\'"][^>]*>[\s\S]*?<\/form>/i',
				"",
				$html
			);
		}

		$imgDir = rkrk_assess_report_images_web_dir();
		$logoUrl = function_exists("rkrk_assess_report_logo_src_html")
			? rkrk_assess_report_logo_src_html()
			: (function_exists("rkrk_assess_report_logo_src_portable")
				? rkrk_assess_report_logo_src_portable()
				: ($imgDir . (isset($GLOBALS["logopic"]) ? basename((string) $GLOBALS["logopic"]) : "rkrs_logo.png")));
		// Nur Datei-URLs ersetzen; vorhandene data:-URIs bleiben (kein rkrs_logo.png im src).
		$html = preg_replace(
			'/(<img\b[^>]*\bsrc=[\'"])[^\'"]*(?:rkrs_logo|rkrk_logo)\.png([\'"][^>]*>)/i',
			'$1' . $logoUrl . '$2',
			$html
		);
		// Weitere frog-spezifische Assess-Public-Pfade → aktueller Public-Webpfad
		if (function_exists("rkrk_assess_resolve_web_path_from_script")) {
			$publicDir = rtrim(str_replace("\\", "/", dirname(rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme.css"))), "/") . "/";
			$html = preg_replace(
				'#(["\'])/rkropen/rkrs/assess/public/#i',
				'$1' . $publicDir,
				$html
			);
			$html = preg_replace(
				'#(["\'])/rkropen/rkrk/data/assess/images/#i',
				'$1' . $imgDir,
				$html
			);
		}

		// Env-Favicon für Browser-Ansicht gespeicherter Report-HTML (Druck/PDF-Quelle)
		if (
			function_exists("rkrk_assess_favicon_link_tags_html")
			&& preg_match('/<\/head>/i', $html)
			&& !preg_match('/<link\b[^>]*\brel=["\'](?:shortcut )?icon["\']/i', $html)
		) {
			$favicon = rkrk_assess_favicon_link_tags_html();
			if ($favicon !== "") {
				$html = (string) preg_replace('/<\/head>/i', $favicon . "</head>", $html, 1);
			}
		}

		return $html;
	}
}

if (!function_exists("rkrk_assess_report_html_strip_unrated_directcheck")) {
	/**
	 * Bestehende Live-Report-HTML: unbewertete Directcheck-Zeilen und leere Blöcke entfernen.
	 */
	function rkrk_assess_report_html_strip_unrated_directcheck(string $html): string {
		if ($html === "" || stripos($html, "unbewertet") === false) {
			return $html;
		}
		// Einzelne unbewertete Kontrollpunkt-Zeilen
		$html = preg_replace(
			'/<div class=[\'"]dc-print-row[\'"]>\s*<strong>.*?<\/strong>\s*—\s*unbewertet\s*<\/div>/su',
			"",
			$html
		);
		// Feld-Blöcke, deren dc-print keine bewertete Zeile mehr hat (nur Stage/leer)
		$html = preg_replace_callback(
			'/<div class=[\'"]field[^\'"]*[\'"]>\s*<div class=[\'"]label[\'"]>.*?<\/div>\s*<div class=[\'"]value[\'"]>\s*<div class=[\'"]dc-print[\'"]>(.*?)<\/div>\s*<\/div>\s*<\/div>/su',
			static function (array $m): string {
				$inner = (string) ($m[1] ?? "");
				if (preg_match('/dc-print-row/i', $inner)) {
					// Stage anpassen, falls noch Zeilen da sind
					$hasNonGreen = (bool) preg_match('/—\s*(teilweise erfüllt|nicht erfüllt)/u', $inner);
					$stageOk = "✓ Alle Kriterien erfüllt";
					$stageBad = "✗ Nicht alle Kriterien erfüllt";
					$inner2 = preg_replace(
						'/<div class=[\'"]dc-print-stage[\'"][^>]*>\s*(?:<font[^>]*>)?\s*<strong>.*?<\/strong>\s*(?:<\/font>)?\s*<\/div>/su',
						'<div class=\'dc-print-stage\'><strong>' . ($hasNonGreen ? $stageBad : $stageOk) . '</strong></div>',
						$inner,
						1
					);
					return str_replace($inner, (string) $inner2, $m[0]);
				}
				return "";
			},
			$html
		);
		// Leere Freitext-Felder
		$html = preg_replace(
			'/<div class=[\'"]field[^\'"]*[\'"]>\s*<div class=[\'"]label[\'"]>.*?<\/div>\s*<div class=[\'"]value[\'"]>\s*<\/div>\s*<\/div>/su',
			"",
			$html
		);
		return $html;
	}
}

if (!function_exists("rkrk_assess_report_migrate_portable_html_files")) {
	/**
	 * Einmalig: bestehende reports/*.html mit /rkropen/-Theme-Links → portable Inline-CSS.
	 * @return int Anzahl aktualisierter Dateien
	 */
	function rkrk_assess_report_migrate_portable_html_files(): int {
		static $ran = false;
		if ($ran) {
			return 0;
		}
		$ran = true;

		$jobs = array();
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$jobs[] = array(
				"dir" => rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/reports/",
				"kind" => "assess",
			);
		}
		if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
			$jobs[] = array(
				"dir" => rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/results/reports/",
				"kind" => "live",
			);
		} elseif (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
			$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/"));
			$jobs[] = array(
				"dir" => rtrim(str_replace("\\", "/", (string) $parent), "/") . "/reports/",
				"kind" => "live",
			);
		}

		$updated = 0;
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		foreach ($jobs as $job) {
			$dir = (string) $job["dir"];
			if ($dir === "" || !is_dir($dir)) {
				continue;
			}
			$files = glob($dir . "*.html");
			if (!is_array($files)) {
				continue;
			}
			$GLOBALS["rkrk_assess_reports_kind"] = (string) $job["kind"];
			foreach ($files as $file) {
				if (!is_string($file) || !is_file($file) || !is_readable($file)) {
					continue;
				}
				$raw = (string) @file_get_contents($file);
				if ($raw === "") {
					continue;
				}
				$needs = (stripos($raw, "rkrk_assess_theme") !== false)
					|| (stripos($raw, "/rkropen/") !== false)
					|| (
						stripos($raw, "rkrk-assess-print") !== false
						&& stripos($raw, "rkrk-report-print-css") === false
					)
					|| (stripos($raw, "unbewertet") !== false);
				if (!$needs) {
					continue;
				}
				$new = rkrk_assess_report_html_make_portable($raw);
				if (function_exists("rkrk_assess_report_html_strip_unrated_directcheck")
					&& (
						(string) $job["kind"] === "live"
						|| stripos($raw, "unbewertet") !== false
					)
				) {
					$new = rkrk_assess_report_html_strip_unrated_directcheck($new);
				}
				if ($new === "" || $new === $raw) {
					continue;
				}
				if (@file_put_contents($file, $new, LOCK_EX) !== false) {
					$updated++;
				}
			}
		}
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		return $updated;
	}
}

if (!function_exists("rkrk_assess_report_logo_fs_path")) {
	/** Absoluter Dateisystem-Pfad zum Logo (für TCPDF); sonst "". */
	function rkrk_assess_report_logo_fs_path(): string {
		$logo = isset($GLOBALS["logopic"]) ? basename((string) $GLOBALS["logopic"]) : "rkrs_logo.png";
		if ($logo === "") {
			$logo = "rkrs_logo.png";
		}
		$dirs = array();
		$addDir = static function (string $dir) use (&$dirs): void {
			$dir = str_replace("\\", "/", trim($dir));
			if ($dir === "") {
				return;
			}
			$dirs[] = rtrim($dir, "/") . "/";
		};

		// QuizIconLoc: absolut oder relativ zu public/ (getcwd ist unzuverlässig)
		if (isset($GLOBALS["QuizIconLoc"]) && trim((string) $GLOBALS["QuizIconLoc"]) !== "") {
			$qil = str_replace("\\", "/", trim((string) $GLOBALS["QuizIconLoc"]));
			$addDir($qil);
			if ($qil !== "" && $qil[0] !== "/" && !preg_match('#^[A-Za-z]:/#', $qil)) {
				$addDir(rtrim(str_replace("\\", "/", (string) __DIR__), "/") . "/" . $qil);
			}
		}
		if (isset($GLOBALS["examIconLoc"]) && trim((string) $GLOBALS["examIconLoc"]) !== "") {
			$eil = str_replace("\\", "/", trim((string) $GLOBALS["examIconLoc"]));
			$addDir($eil);
			if ($eil !== "" && $eil[0] !== "/" && !preg_match('#^[A-Za-z]:/#', $eil)) {
				$addDir(rtrim(str_replace("\\", "/", (string) __DIR__), "/") . "/" . $eil);
			}
		}
		if (isset($GLOBALS["rkrkDataRoot"]) && trim((string) $GLOBALS["rkrkDataRoot"]) !== "") {
			$root = str_replace("\\", "/", trim((string) $GLOBALS["rkrkDataRoot"]));
			$addDir($root . "/data/assess/images/");
			if ($root !== "" && $root[0] !== "/" && !preg_match('#^[A-Za-z]:/#', $root)) {
				$addDir(rtrim(str_replace("\\", "/", (string) __DIR__), "/") . "/" . $root . "/data/assess/images/");
			}
		}

		// public/ → ../../data/assess/images (Portal: …/rkrsplusassessment/assess/public)
		$addDir(str_replace("\\", "/", dirname(__DIR__)) . "/../data/assess/images/");
		$addDir(str_replace("\\", "/", __DIR__) . "/../../data/assess/images/");
		// frog Synology: …/rkrs/assess/public → …/rkrs/data/assess/images
		$addDir(str_replace("\\", "/", dirname(__DIR__, 2)) . "/data/assess/images/");

		$docRoot = isset($_SERVER["DOCUMENT_ROOT"])
			? rtrim(str_replace("\\", "/", (string) $_SERVER["DOCUMENT_ROOT"]), "/")
			: "";
		if ($docRoot !== "") {
			// portal.rehkitzrettung.ch
			$addDir($docRoot . "/rkrsplusassessment/data/assess/images/");
			// frog / rkropen
			$addDir($docRoot . "/rkropen/rkrs/data/assess/images/");
			$addDir($docRoot . "/rkropen/rkrk/data/assess/images/");
			$addDir($docRoot . "/rkrs/data/assess/images/");
		}

		$seen = array();
		foreach ($dirs as $dir) {
			$path = $dir . $logo;
			$key = strtolower($path);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			if (!is_readable($path)) {
				continue;
			}
			$real = realpath($path);
			return ($real !== false) ? str_replace("\\", "/", $real) : str_replace("\\", "/", $path);
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_report_logo_data_uri")) {
	/** data:-URI für zuverlässige TCPDF-/Dompdf-Einbettung; sonst "". */
	function rkrk_assess_report_logo_data_uri(): string {
		$path = rkrk_assess_report_logo_fs_path();
		if ($path === "" || !is_readable($path)) {
			return "";
		}
		$bin = @file_get_contents($path);
		if ($bin === false || $bin === "") {
			return "";
		}
		$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
		$mime = ($ext === "jpg" || $ext === "jpeg") ? "image/jpeg" : "image/png";
		return "data:" . $mime . ";base64," . base64_encode($bin);
	}
}

if (!function_exists("rkrk_assess_report_logo_src_html")) {
	/**
	 * Logo-src für Report-HTML: data-URI (host-unabhängig), sonst Web-Pfad (Portal/frog), sonst relativ.
	 */
	function rkrk_assess_report_logo_src_html(): string {
		$uri = rkrk_assess_report_logo_data_uri();
		if ($uri !== "") {
			return $uri;
		}
		$logo = isset($GLOBALS["logopic"]) ? basename((string) $GLOBALS["logopic"]) : "rkrs_logo.png";
		if ($logo === "") {
			$logo = "rkrs_logo.png";
		}
		if (function_exists("rkrk_assess_report_images_web_dir")) {
			$web = rkrk_assess_report_images_web_dir();
			if ($web !== "") {
				return rtrim($web, "/") . "/" . $logo;
			}
		}
		if (function_exists("rkrk_assess_report_logo_src_portable")) {
			return rkrk_assess_report_logo_src_portable();
		}
		return "../../images/" . $logo;
	}
}

if (!function_exists("rkrk_assess_report_resolve_quiz_title")) {
	/** Anzeigetitel: AssessmentListe, sonst Key. */
	function rkrk_assess_report_resolve_quiz_title(string $quizKey, array $row = array()): string {
		$fromRow = trim((string) ($row["quiz_title"] ?? ($row["quiz_titel"] ?? "")));
		if ($fromRow !== "" && strcasecmp($fromRow, $quizKey) !== 0) {
			return $fromRow;
		}
		if ($quizKey !== "" && function_exists("rkrk_assess_quiz_title_from_key")) {
			$looked = trim((string) rkrk_assess_quiz_title_from_key($quizKey));
			if ($looked !== "") {
				return $looked;
			}
		}
		if ($fromRow !== "") {
			return $fromRow;
		}
		return $quizKey !== "" ? $quizKey : "Quiz";
	}
}

if (!function_exists("rkrk_assess_report_modus_display")) {
	function rkrk_assess_report_modus_display(string $modus): string {
		$raw = trim($modus);
		if ($raw === "") {
			return "";
		}
		$lc = strtolower($raw);
		if ($lc === "pruefung" || $lc === "prüfung") {
			return "Prüfung";
		}
		if ($lc === "quiz" || $lc === "uebung" || $lc === "übung") {
			return "Übung";
		}
		if ($lc === "schulung") {
			return "Schulung";
		}
		$n = function_exists("rkrk_assess_quiz_modus_normalize")
			? rkrk_assess_quiz_modus_normalize($raw)
			: $lc;
		if ($n === "pruefung") {
			return "Prüfung";
		}
		if ($n === "quiz") {
			return "Übung";
		}
		return $raw;
	}
}

if (!function_exists("rkrk_assess_report_file_is_current_digital")) {
	/**
	 * Neues Digital-PDF/HTML: nur Falsche/Teilweise, Abschlussbericht, Teilpunkte.
	 * .prep.pdf und reines HTML gelten als Altformat.
	 */
	function rkrk_assess_report_file_is_current_digital(string $path): bool {
		$path = str_replace("\\", "/", $path);
		if ($path === "" || !is_file($path)) {
			return false;
		}
		if (preg_match('/\.prep\.pdf$/i', $path)) {
			return false;
		}
		$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
		if ($ext === "html" || $ext === "htm") {
			return false;
		}
		$size = (int) @filesize($path);
		// Aktueller TCPDF-Stand bettet DejaVu ein (~1.5 MB). Altformat ~60 KB, Zwischenstand ~260 KB.
		return $size >= 900000;
	}
}

if (!function_exists("rkrk_assess_ensure_report_from_completed_exam")) {
	/**
	 * Erzeugt Abschluss-Report (PDF bevorzugt) für eine abgeschlossene EXAM-Session-Datei.
	 * Dateiname = Stamm der EXAM-JSON (wie rkrk_assess_save_digital_result_report).
	 *
	 * @param array<string, mixed> $row  Eintrag aus rkrk_assess_exam_list_completed()
	 * @return array{ok:bool, path:string, format:string, error:string, skipped:bool}
	 */
	function rkrk_assess_ensure_report_from_completed_exam(array $row, bool $force = false): array {
		$stem = trim((string) ($row["stem"] ?? ""));
		if ($stem === "" && !empty($row["path"])) {
			$stem = pathinfo((string) $row["path"], PATHINFO_FILENAME);
		}
		$stem = rkrk_assess_report_stem_from_result($stem);
		if ($stem === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "stem missing", "skipped" => false);
		}
		$dir = rkrk_assess_reports_dir();
		if ($dir === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "reports dir missing", "skipped" => false);
		}
		if (!$force) {
			foreach (array("pdf", "html") as $fmt) {
				$path = $dir . $stem . "." . $fmt;
				if (is_file($path)) {
					return array("ok" => true, "path" => $path, "format" => $fmt, "error" => "", "skipped" => true);
				}
			}
		}
		$result = isset($row["result"]) && is_array($row["result"]) ? $row["result"] : array();
		if ($result === array()) {
			return array("ok" => false, "path" => "", "format" => "", "error" => "result missing", "skipped" => false);
		}
		$fragen = isset($result["quiz_ergebnis_fragen"]) && is_array($result["quiz_ergebnis_fragen"])
			? $result["quiz_ergebnis_fragen"]
			: array();
		$quizKey = trim((string) ($row["quiz_key"] ?? ($result["quiz"] ?? "")));
		$quizTitle = rkrk_assess_report_resolve_quiz_title($quizKey, $row);
		$meta = array(
			"quiz" => $quizKey,
			"quiz_key" => $quizKey,
			"quiz_title" => $quizTitle,
			"name" => trim((string) ($row["name"] ?? ($result["name"] ?? ""))),
			"drone" => trim((string) ($row["drone"] ?? ($result["drone"] ?? ""))),
			"exam" => trim((string) ($row["exam_code"] ?? "")),
			"quiz_modus" => trim((string) ($row["quiz_modus"] ?? "")),
			"zeit" => trim((string) ($row["zeit"] ?? ($result["zeit"] ?? ""))),
			"punkte_erreicht" => (string) ($result["punkte_erreicht"] ?? ($row["richtig"] ?? "")),
			"punkte_max" => (string) ($result["punkte_max"] ?? ($row["anz"] ?? "")),
			"anz_fragen" => (string) ($result["anz_fragen"] ?? ($row["anz"] ?? "")),
			"fragen_richtig" => (string) ($result["fragen_richtig"] ?? ($row["richtig"] ?? "")),
			"bestanden" => (string) ($row["bestanden"] ?? ($result["bestanden"] ?? "")),
			"erfuellt" => (string) ($result["erfuellt"] ?? ($row["factor"] ?? "")),
		);
		if ($meta["zeit"] === "" && !empty($row["timestamp"])) {
			$meta["zeit"] = date("d.m.Y H:i", (int) $row["timestamp"]);
		}
		$html = rkrk_assess_build_digital_result_report_html($meta, $fragen);
		$save = rkrk_assess_write_report_pdf($stem, $html);
		$save["skipped"] = false;
		return $save;
	}
}

if (!function_exists("rkrk_assess_ensure_missing_exam_reports")) {
	/**
	 * Fehlende Abschluss-Reports für completed EXAM-JSONs erzeugen.
	 *
	 * @return array{created:int, skipped:int, failed:int, errors:list<string>}
	 */
	function rkrk_assess_ensure_missing_exam_reports(int $maxCreate = 50, bool $force = false): array {
		$stats = array("created" => 0, "skipped" => 0, "failed" => 0, "errors" => array());
		if (!function_exists("rkrk_assess_exam_list_completed")) {
			return $stats;
		}
		$rows = rkrk_assess_exam_list_completed();
		foreach ($rows as $row) {
			if ($stats["created"] >= $maxCreate) {
				break;
			}
			$res = rkrk_assess_ensure_report_from_completed_exam($row, $force);
			if (!empty($res["skipped"])) {
				$stats["skipped"]++;
				continue;
			}
			if (!empty($res["ok"])) {
				$stats["created"]++;
				continue;
			}
			$stats["failed"]++;
			$stem = trim((string) ($row["stem"] ?? ""));
			$err = trim((string) ($res["error"] ?? "unknown"));
			$stats["errors"][] = ($stem !== "" ? $stem : "?") . ": " . $err;
		}
		return $stats;
	}
}

if (!function_exists("rkrk_assess_report_rebuild_legacy_exam_reports")) {
	/**
	 * Einmalig (in Batches): alle Exam-Abschluss-PDFs im aktuellen Layout neu erzeugen.
	 * Fortschritt: .report_digital_v3.progress  Abschluss: .report_digital_v3
	 */
	function rkrk_assess_report_rebuild_legacy_exam_reports(int $batch = 8): int {
		$base = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/";
		}
		if ($base === "") {
			return 0;
		}
		$marker = $base . ".report_digital_v3";
		if (is_file($marker)) {
			return 0;
		}
		if (!function_exists("rkrk_assess_exam_list_completed")) {
			$inc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_exam_session.inc.php";
			if (is_readable($inc)) {
				include_once $inc;
			}
		}
		if (!function_exists("rkrk_assess_exam_list_completed")
			|| !function_exists("rkrk_assess_ensure_report_from_completed_exam")
		) {
			return 0;
		}
		if (function_exists("set_time_limit")) {
			@set_time_limit(180);
		}
		$rows = rkrk_assess_exam_list_completed();
		$total = count($rows);
		if ($total < 1) {
			@file_put_contents($marker, date("c") . " empty\n", LOCK_EX);
			return 0;
		}
		$progressFile = $base . ".report_digital_v3.progress";
		$offset = 0;
		if (is_file($progressFile)) {
			$offset = (int) trim((string) @file_get_contents($progressFile));
		}
		if ($offset < 0) {
			$offset = 0;
		}
		$batch = max(1, min(40, $batch));
		$slice = array_slice($rows, $offset, $batch);
		$created = 0;
		foreach ($slice as $row) {
			if (!is_array($row)) {
				continue;
			}
			$res = rkrk_assess_ensure_report_from_completed_exam($row, true);
			if (!empty($res["ok"])) {
				$created++;
			}
		}
		$next = $offset + count($slice);
		$repDir = $base . "reports/";
		if (is_dir($repDir)) {
			foreach (glob($repDir . "*.prep.pdf") ?: array() as $prep) {
				if (is_string($prep) && is_file($prep)) {
					@unlink($prep);
				}
			}
		}
		if ($next >= $total) {
			@file_put_contents($marker, date("c") . " rebuilt=" . $total . "\n", LOCK_EX);
			if (is_file($progressFile)) {
				@unlink($progressFile);
			}
		} else {
			@file_put_contents($progressFile, (string) $next . "\n", LOCK_EX);
		}
		return $created;
	}
}

if (!function_exists("rkrk_assess_report_delete_by_stem")) {
	/**
	 * Löscht Abschluss-Report (pdf + html) zum Dateistamm unter results/reports/.
	 *
	 * @return int Anzahl gelöschter Dateien
	 */
	function rkrk_assess_report_delete_by_stem(string $stem, string $reportsKind = "assess"): int {
		$stem = function_exists("rkrk_assess_report_stem_from_result")
			? rkrk_assess_report_stem_from_result($stem)
			: preg_replace('/\.(json|pdf|html)$/i', "", basename(str_replace("\\", "/", $stem)));
		$stem = trim((string) $stem);
		if ($stem === "" || !preg_match('/^[A-Za-z0-9_.-]+$/', $stem)) {
			return 0;
		}
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = (strtolower(trim($reportsKind)) === "live") ? "live" : "assess";
		$dir = rkrk_assess_reports_dir();
		$deleted = 0;
		if ($dir !== "") {
			$baseReal = realpath(rtrim(str_replace("\\", "/", $dir), "/"));
			if ($baseReal !== false && is_dir($baseReal)) {
				foreach (array("pdf", "html") as $fmt) {
					$candidate = $baseReal . DIRECTORY_SEPARATOR . $stem . "." . $fmt;
					$rp = realpath($candidate);
					if ($rp === false || !is_file($rp)) {
						continue;
					}
					if (strpos($rp, $baseReal) !== 0) {
						continue;
					}
					if (@unlink($rp)) {
						$deleted++;
					}
				}
			}
		}
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		return $deleted;
	}
}


if (!function_exists("rkrk_assess_report_tcpdf_print_css")) {
	/**
	 * Kompaktes Druck-CSS (TCPDF): an digitalen Abschlussbericht angeglichen.
	 */
	function rkrk_assess_report_tcpdf_print_css(): string {
		return "@page{size:A4 portrait;margin:8mm 8mm;}"
			. "body.rkrk-assess-print,body.rkrk-live-report,body.rkrk-checklist-report,body.pf-report{font-family:dejavusans,helvetica,Arial,sans-serif;font-size:9.5pt;margin:0;padding:0;color:#111;line-height:1.25;background:#fff}"
			. "body.rkrk-assess-print .print-header,body.rkrk-assess-print .print-header-table,body.rkrk-assess-print .pf-header{width:100%;margin:0 0 8px;padding:0 0 6px;border:none;border-collapse:collapse}"
			. "body.rkrk-assess-print .print-header-table td{vertical-align:top;padding:0}"
			. "body.rkrk-assess-print .print-title{margin:0;font-size:13pt;line-height:1.2;font-weight:bold;color:#111}"
			. "body.rkrk-assess-print .print-subtitle,body.rkrk-assess-print .pf-sub{display:block;font-size:9pt;color:#475569;font-weight:normal;margin-top:2px}"
			. "body.rkrk-assess-print .print-logo,body.rkrk-assess-print .pf-logo{width:32%;text-align:right;padding:0;margin:0}"
			. "body.rkrk-assess-print .print-logo img,body.rkrk-assess-print .pf-logo img{max-height:40px;height:40px;width:auto;display:block;margin:0 0 0 auto}"
			. "body.rkrk-assess-print .section,body.rkrk-assess-print .pf-sec{margin:6px 0 4px;padding-top:4px;border:none}"
			. "body.rkrk-assess-print .section h2,body.rkrk-assess-print .pf-sec h2{margin:0 0 4px;font-size:9.5pt;color:#1e3a1e;font-weight:bold}"
			. "body.rkrk-assess-print .field-grid,body.rkrk-assess-print .pf-sec-grid{width:100%;border-collapse:collapse;border:none}"
			. "body.rkrk-assess-print .field-grid td.field-cell,body.rkrk-assess-print .pf-sec-grid td.pf-field,body.rkrk-assess-print .pf-sec-grid td.field-cell{width:50%;vertical-align:top;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1}"
			. "body.rkrk-assess-print .pf-sec-grid td.pf-field-empty{border:none;background:transparent}"
			. "body.rkrk-assess-print .field-grid .field,body.rkrk-assess-print .pf-field{margin:0;padding:0;border:none;background:transparent;font-size:9.5pt;line-height:1.25}"
			. "body.rkrk-assess-print .field-grid .field.field-spec-ref{border:none}"
			. "body.rkrk-assess-print .field-grid .field.pf-field-stack .pf-label,body.rkrk-assess-print .field-grid .field.pf-field-stack .label{display:block;font-size:7.5pt;font-weight:700;color:#64748b;margin:0 0 1px}"
			. "body.rkrk-assess-print .field-grid .field.pf-field-stack .pf-line,body.rkrk-assess-print .field-grid .field.pf-field-stack .value{display:block;margin:0;font-weight:600;font-size:9.5pt;color:#111}"
			. "body.rkrk-assess-print .label,body.rkrk-assess-print .pf-label{font-weight:700;font-size:7.5pt;color:#64748b}"
			. "body.rkrk-assess-print .value,body.rkrk-assess-print .pf-line{font-size:9.5pt;color:#111;font-weight:600}"
			. "body.rkrk-assess-print .value-meta{display:block;margin-top:3px;font-size:8pt;color:#64748b;font-weight:400}"
			. "body.rkrk-assess-print .highlight .label,body.rkrk-assess-print .highlight .value{font-weight:700;color:#111}"
			. "body.rkrk-assess-print .no-print{display:none}"
			. "body.rkrk-assess-print .pf-legend,body.rkrk-assess-print .pf-legend-stufe{width:100%;margin:4px 0 8px;padding:0;border:none;border-collapse:collapse}"
			. "body.rkrk-assess-print .pf-legend .result-label,body.rkrk-assess-print .pf-legend-stufe .result-label{font-size:12pt;font-weight:800;line-height:1.2;padding:6px 8px}"
			. "body.rkrk-assess-print .pf-legend .result-score,body.rkrk-assess-print .pf-legend-stufe .result-score{font-size:9.5pt;font-weight:700;text-align:right;padding:6px 8px}"
			. "body.rkrk-assess-print .field.field-gesamtstufe .value{font-size:11pt;font-weight:800}"
			. ".dc-print-row{margin:0;padding:0;font-size:8.5pt;line-height:1.25;border:none;background:transparent}"
			. ".dc-print-row--ok{background:transparent;border:none;color:#14532d}"
			. ".dc-print-row--partial{background:transparent;border:none;color:#9a3412}"
			. ".dc-print-row--bad{background:transparent;border:none;color:#7f1d1d}"
			. ".dc-print-row--none{background:transparent;border:none;color:#64748b}"
			. ".dc-print-sym{font-weight:700;font-family:dejavusans,helvetica,sans-serif}"
			. ".dc-print-stage{margin-top:2px;padding:0;font-size:8.5pt;background:transparent}"
			. ".dc-print-subcap{margin:3px 0 1px;font-size:8pt;color:#64748b}"
			. ".dc-print-sub-indent{margin:1px 0;padding-left:14px;font-size:8pt}"
			. ".dc-print-obs{margin-top:4px;font-size:8.5pt}"
			. ".rkrk-report-copyright{margin-top:10px;padding-top:6px;border:none;font-size:7.5pt;color:#64748b;line-height:1.3;clear:both}";
	}
}

if (!function_exists("rkrk_assess_report_tcpdf_storyboard_css")) {
	/**
	 * Storyboard-PDF: gleiche Typo/Abstände wie Abschlussbericht (TCPDF).
	 */
	function rkrk_assess_report_tcpdf_storyboard_css(): string {
		return "body.rkrk-storyboard-print table{border-collapse:collapse;border:none}"
			. "body.rkrk-storyboard-print td{border:none}"
			. "body.rkrk-storyboard-print .sb-meta{font-size:7.5pt;color:#5d6f5a;margin:0 0 2px;line-height:1.2}"
			. "body.rkrk-storyboard-print .sb-desc{font-size:8.5pt;color:#111;margin:0 0 3px}"
			. "body.rkrk-storyboard-print .sb-phase{margin:8px 0 0;padding:0;border:none}"
			. "body.rkrk-storyboard-print .sb-phase-head{width:100%;border:none;margin:0;background-color:#eef5ec}"
			. "body.rkrk-storyboard-print .sb-phase-head td{border:none;padding:1px 5px;background-color:#eef5ec}"
			. "body.rkrk-storyboard-print .sb-phase-title{margin:0;font-size:8.5pt;font-weight:bold;color:#48673A}"
			. "body.rkrk-storyboard-print .sb-phase-dur{font-weight:normal;color:#5d6f5a;font-size:8pt}"
			. "body.rkrk-storyboard-print .sb-phase-prep,body.rkrk-storyboard-print .sb-cont{margin:0;font-size:7.5pt;color:#475569;font-style:italic}"
			. "body.rkrk-storyboard-print .sb-items{width:100%;border-collapse:collapse;border:none;margin:0;padding:0}"
			. "body.rkrk-storyboard-print .sb-items td,body.rkrk-storyboard-print .sb-item{margin:0;padding:0;border:none;background:transparent;line-height:1.2}"
			. "body.rkrk-storyboard-print .sb-code,body.rkrk-storyboard-print .live-point-code{font-weight:800;font-size:8pt;color:#48673A}"
			. "body.rkrk-storyboard-print .sb-point-title{font-weight:600;font-size:8.5pt;color:#111}"
			. "body.rkrk-storyboard-print .sb-cue{display:inline;margin:0;padding:0;border:none;font-size:8pt;color:#334155}"
			. "body.rkrk-storyboard-print .sb-info{margin:0;padding:0;border:none;font-size:7.5pt;color:#64748b}"
			. "body.rkrk-storyboard-print .sb-stoer{background-color:#ffedd5;color:#9a3412;font-size:8pt;padding:0 4px}"
			. "body.rkrk-storyboard-print .sb-nb{margin:4px 0 0;padding:0;border:none;color:#64748b;font-size:7.5pt}";
	}
}

if (!function_exists("rkrk_assess_report_html_prepare_for_tcpdf")) {
	/**
	 * HTML für TCPDF vorbereiten: Druck-CSS ohne Grid/Vars, Logo als Dateipfad, Header als Tabelle.
	 */
	function rkrk_assess_report_html_prepare_for_tcpdf(string $html): string {
		$html = trim($html);
		if ($html === "") {
			return $html;
		}
		$isChecklistPrint = (stripos($html, "rkrk-assess-print") !== false)
			|| (stripos($html, "rkrk-report-print-css") !== false);
		$isDigitalReport = (stripos($html, "rkrk-digital-report") !== false);

		if ($isChecklistPrint && !$isDigitalReport) {
			$css = rkrk_assess_report_tcpdf_print_css();
			if (stripos($html, "rkrk-storyboard-print") !== false && function_exists("rkrk_assess_report_tcpdf_storyboard_css")) {
				$css .= "\n" . rkrk_assess_report_tcpdf_storyboard_css();
			}
			$styleTag = "<style id=\"rkrk-report-print-css\">\n" . $css . "\n</style>\n";
			if (preg_match('/<style\b[^>]*\bid=[\'"]rkrk-report-print-css[\'"][^>]*>[\s\S]*?<\/style>/i', $html)) {
				$html = (string) preg_replace(
					'/(<style\b[^>]*\bid=[\'"]rkrk-report-print-css[\'"][^>]*>)[\s\S]*?(<\/style>)/i',
					'$1' . "\n" . $css . "\n" . '$2',
					$html,
					1
				);
			} elseif (preg_match('/<\/head>/i', $html)) {
				$html = (string) preg_replace('/<\/head>/i', $styleTag . "</head>", $html, 1);
			} elseif (preg_match('/<body\b[^>]*>/i', $html, $bm)) {
				$html = (string) preg_replace('/<body\b[^>]*>/i', $bm[0] . $styleTag, $html, 1);
			} else {
				$html = $styleTag . $html;
			}
		}

		$logoUri = rkrk_assess_report_logo_data_uri();
		$logoFs = ($logoUri === "") ? rkrk_assess_report_logo_fs_path() : "";
		$logoSrcReplace = ($logoUri !== "") ? $logoUri : $logoFs;
		if ($logoSrcReplace !== "") {
			$html = preg_replace(
				'/(<img\b[^>]*\bsrc=[\'"])[^\'"]*(?:rkrs_logo|rkrk_logo)\.png([\'"][^>]*>)/i',
				'$1' . $logoSrcReplace . '$2',
				$html
			);
			// print-logo / pf-logo img (div oder td) ohne bekannten Dateinamen ebenfalls setzen
			$html = preg_replace(
				'/(<(?:div|td)\b[^>]*\bclass=[\'"][^\'"]*(?:print-logo|pf-logo)[^\'"]*[\'"][^>]*>\s*<img\b[^>]*\bsrc=[\'"])[^\'"]+([\'"])/i',
				'$1' . $logoSrcReplace . '$2',
				$html
			);
		}

		// print-header: absolute Positionierung → einfache 2-Spalten-Tabelle
		// Klassen dürfen Zusatzwerte tragen (z. B. "print-header pf-header").
		if (stripos($html, "print-header") !== false
			&& preg_match(
				'/<div\s+class=[\'"][^\'"]*\bprint-header\b[^\'"]*[\'"]>\s*'
				. '<div\s+class=[\'"][^\'"]*\bprint-title\b[^\'"]*[\'"]>([\s\S]*?)<\/div>\s*'
				. '<div\s+class=[\'"][^\'"]*\bprint-logo\b[^\'"]*[\'"]>\s*(<img\b[^>]*>)\s*<\/div>\s*'
				. '<\/div>/i',
				$html,
				$m
			)
		) {
			$table = "<table class=\"print-header-table pf-header\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr>"
				. "<td class=\"print-title\" valign=\"top\">" . $m[1] . "</td>"
				. "<td class=\"print-logo pf-logo\" align=\"right\" valign=\"top\" width=\"140\">" . $m[2] . "</td>"
				. "</tr></table>";
			$html = str_replace($m[0], $table, $html);
		}

		// Bereits als Tabelle: Logo-Zelle oben rechts erzwingen (fehlende Attribute ergänzen)
		$html = preg_replace_callback(
			'/<td\b([^>]*\bclass=[\'"][^\'"]*\bprint-logo\b[^\'"]*[\'"][^>]*)>/i',
			static function (array $m): string {
				$attrs = (string) ($m[1] ?? "");
				if (stripos($attrs, "align=") === false) {
					$attrs .= " align=\"right\"";
				}
				if (stripos($attrs, "valign=") === false) {
					$attrs .= " valign=\"top\"";
				}
				if (stripos($attrs, "width=") === false) {
					$attrs .= " width=\"140\"";
				}
				return "<td" . $attrs . ">";
			},
			$html
		);

		return $html;
	}
}

if (!function_exists("rkrk_assess_report_render_pdf_tcpdf")) {
	/**
	 * Rendert HTML mit TCPDF zu PDF-Binary; bei Fehler "".
	 */
	function rkrk_assess_report_render_pdf_tcpdf(string $html, string $stem): string {
		if ($html === "" || !class_exists("TCPDF", false)) {
			return "";
		}
		// TCPDF (ohne curl) wirft oft Warnings — dürfen nicht in die PDF-Bytes.
		$prevDisplay = ini_get("display_errors");
		ini_set("display_errors", "0");
		$obLevel = ob_get_level();
		ob_start();
		$out = "";
		try {
			$pdf = new \TCPDF("P", "mm", "A4", true, "UTF-8", false);
			$pdf->SetCreator("RKRS Assess");
			$pdf->SetAuthor("RKRS Assess");
			$pdf->SetTitle($stem !== "" ? $stem : "Report");
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			$pdf->SetMargins(8, 8, 8);
			$pdf->SetAutoPageBreak(true, 10);
			$pdf->AddPage();

			// Portal: TCPDF oft ohne DejaVu → Helvetica ersetzt ✓/★/✗ durch «?».
			$font = function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font")
				? rkrk_assess_report_tcpdf_ensure_unicode_font($pdf)
				: "helvetica";
			if ($font !== "dejavusans" && function_exists("rkrk_assess_report_pdf_ascii_symbol_fallback")) {
				$html = rkrk_assess_report_pdf_ascii_symbol_fallback($html);
			}
			$pdf->SetFont($font, "", 10);
			$pdf->writeHTML($html, true, false, true, false, "");
			// Live-Reports: Logo oben rechts per Image() (zuverlässiger als data:-URI in writeHTML)
			if (stripos($html, "rkrk-live-report") !== false && function_exists("rkrk_assess_report_logo_fs_path")) {
				$__logoFs = rkrk_assess_report_logo_fs_path();
				if ($__logoFs !== "" && is_readable($__logoFs)) {
					$__isz = @getimagesize($__logoFs);
					$__logoH = 11.5;
					$__logoW = $__logoH;
					if (is_array($__isz) && !empty($__isz[0]) && !empty($__isz[1]) && (float) $__isz[1] > 0) {
						$__logoW = $__logoH * ((float) $__isz[0] / (float) $__isz[1]);
					}
					$__m = $pdf->getMargins();
					$__x = $pdf->getPageWidth() - (float) ($__m["right"] ?? 8) - $__logoW;
					$__y = (float) ($__m["top"] ?? 8);
					try {
						$pdf->setPage(1);
						$pdf->Image($__logoFs, $__x, $__y, $__logoW, $__logoH, "", "", "", false, 300, "", false, false, 0, false, false, false);
					} catch (\Throwable $e) {
					}
				}
			}
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

if (!function_exists("rkrk_assess_write_report_pdf")) {
	/**
	 * Schreibt Report als PDF (TCPDF bevorzugt, sonst Dompdf) sonst .html.
	 *
	 * @return array{ok:bool, path:string, format:string, error:string}
	 */
	function rkrk_assess_write_report_pdf(string $basename, string $html): array {
		$dir = rkrk_assess_reports_dir();
		$stem = rkrk_assess_report_stem_from_result($basename);
		if ($dir === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "reports dir missing");
		}
		// Nur stem.pdf — kein .prep.pdf mehr
		$html = trim($html);
		if ($html === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "empty html");
		}
		if (stripos($html, "<html") === false) {
			$favicon = function_exists("rkrk_assess_favicon_link_tags_html")
				? rkrk_assess_favicon_link_tags_html()
				: "";
			$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\">"
				. $favicon
				. "<title>"
				. htmlspecialchars($stem, ENT_QUOTES, "UTF-8")
				. "</title></head><body>" . $html . "</body></html>";
		}
		$html = rkrk_assess_report_html_make_portable($html);
		if (
			function_exists("rkrk_assess_report_html_strip_unrated_directcheck")
			&& strtolower(trim((string) ($GLOBALS["rkrk_assess_reports_kind"] ?? ""))) === "live"
		) {
			$html = rkrk_assess_report_html_strip_unrated_directcheck($html);
		}
		$html = rkrk_assess_report_append_copyright_footer($html);

		if (rkrk_assess_try_load_tcpdf()) {
			try {
				$prepared = rkrk_assess_report_html_prepare_for_tcpdf($html);
				$pdf = rkrk_assess_report_render_pdf_tcpdf($prepared, $stem);
				if ($pdf !== "") {
					$path = $dir . $stem . ".pdf";
					if (@file_put_contents($path, $pdf, LOCK_EX) !== false) {
						$htmlPath = $dir . $stem . ".html";
						if (is_file($htmlPath)) {
							@unlink($htmlPath);
						}
						$prepPath = $dir . $stem . ".prep.pdf";
						if (is_file($prepPath)) {
							@unlink($prepPath);
						}
						return array("ok" => true, "path" => $path, "format" => "pdf", "error" => "");
					}
				}
			} catch (\Throwable $e) {
				// Fallback Dompdf/HTML below.
			}
		}

		if (rkrk_assess_try_load_dompdf()) {
			try {
				$dompdf = new \Dompdf\Dompdf(array(
					"isRemoteEnabled" => true,
					"isHtml5ParserEnabled" => true,
				));
				$dompdf->loadHtml($html, "UTF-8");
				$dompdf->setPaper("A4", "portrait");
				$dompdf->render();
				$pdf = $dompdf->output();
				$path = $dir . $stem . ".pdf";
				if (@file_put_contents($path, $pdf, LOCK_EX) !== false) {
					$htmlPath = $dir . $stem . ".html";
					if (is_file($htmlPath)) {
						@unlink($htmlPath);
					}
					$prepPath = $dir . $stem . ".prep.pdf";
					if (is_file($prepPath)) {
						@unlink($prepPath);
					}
					return array("ok" => true, "path" => $path, "format" => "pdf", "error" => "");
				}
			} catch (\Throwable $e) {
				// Fallback HTML below.
			}
		}

		$path = $dir . $stem . ".html";
		if (@file_put_contents($path, $html, LOCK_EX) === false) {
			return array("ok" => false, "path" => "", "format" => "", "error" => "write failed");
		}
		return array("ok" => true, "path" => $path, "format" => "html", "error" => "");
	}
}

if (!function_exists("rkrk_assess_report_migrate_html_to_pdf")) {
	/**
	 * Einmalig: bestehende reports/*.html → PDF (TCPDF/Dompdf), wenn möglich.
	 * @return int Anzahl erfolgreich als PDF geschriebener Dateien
	 */
	function rkrk_assess_report_migrate_html_to_pdf(): int {
		static $ran = false;
		if ($ran) {
			return 0;
		}
		$ran = true;

		$jobs = array();
		$seenDirs = array();
		$addJob = static function (string $dir, string $kind) use (&$jobs, &$seenDirs): void {
			$dir = rtrim(str_replace("\\", "/", $dir), "/") . "/";
			if ($dir === "/" || !is_dir($dir)) {
				return;
			}
			$key = strtolower($dir);
			if (isset($seenDirs[$key])) {
				return;
			}
			$seenDirs[$key] = true;
			$jobs[] = array("dir" => $dir, "kind" => $kind);
		};

		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$addJob(rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/reports/", "assess");
		}
		if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
			$addJob(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/results/reports/", "live");
		} elseif (isset($GLOBALS["rkrkLiveassessResults"]) && trim((string) $GLOBALS["rkrkLiveassessResults"]) !== "") {
			$parent = dirname(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessResults"]), "/"));
			$addJob(rtrim(str_replace("\\", "/", (string) $parent), "/") . "/reports/", "live");
		}
		// Zusätzlich …/rkrk/data/… falls onRKRS mit ../../ (rkrs/data) läuft, Reports aber auch unter rkrk liegen
		$here = str_replace("\\", "/", __DIR__);
		$addJob($here . "/../../../rkrk/data/assess/results/reports/", "assess");
		$addJob($here . "/../../../rkrk/data/liveassess/results/reports/", "live");

		$converted = 0;
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		foreach ($jobs as $job) {
			$dir = (string) $job["dir"];
			if ($dir === "" || !is_dir($dir)) {
				continue;
			}
			$files = glob($dir . "*.html");
			if (!is_array($files)) {
				continue;
			}
			$GLOBALS["rkrk_assess_reports_kind"] = (string) $job["kind"];
			foreach ($files as $file) {
				if (!is_string($file) || !is_file($file) || !is_readable($file)) {
					continue;
				}
				$raw = (string) @file_get_contents($file);
				if ($raw === "") {
					continue;
				}
				$stem = pathinfo($file, PATHINFO_FILENAME);
				// write_report_pdf nutzt rkrk_assess_reports_dir() — für Fremdpfade direkt ins Quellverzeichnis schreiben
				$prevReportsKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
				$GLOBALS["rkrk_assess_reports_kind"] = (string) $job["kind"];
				$result = null;
				$targetDir = rkrk_assess_reports_dir();
				$sameDir = ($targetDir !== "" && rtrim(str_replace("\\", "/", $targetDir), "/") === rtrim($dir, "/"));
				if ($sameDir) {
					$result = rkrk_assess_write_report_pdf($stem, $raw);
				} else {
					// Fremdes reports-Verzeichnis: PDF dorthin schreiben (TCPDF/Dompdf), ohne QuizLoc-Ziel zu nutzen
					$html = $raw;
					if (stripos($html, "<html") === false) {
						$favicon = function_exists("rkrk_assess_favicon_link_tags_html")
							? rkrk_assess_favicon_link_tags_html()
							: "";
						$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\">"
							. $favicon
							. "<title>"
							. htmlspecialchars($stem, ENT_QUOTES, "UTF-8")
							. "</title></head><body>" . $html . "</body></html>";
					}
					$html = rkrk_assess_report_html_make_portable($html);
					if ((string) $job["kind"] === "live" && function_exists("rkrk_assess_report_html_strip_unrated_directcheck")) {
						$html = rkrk_assess_report_html_strip_unrated_directcheck($html);
					}
					$html = rkrk_assess_report_append_copyright_footer($html);
					$pdfBin = "";
					if (rkrk_assess_try_load_tcpdf()) {
						try {
							$prepared = rkrk_assess_report_html_prepare_for_tcpdf($html);
							$pdfBin = rkrk_assess_report_render_pdf_tcpdf($prepared, $stem);
						} catch (\Throwable $e) {
							$pdfBin = "";
						}
					}
					if ($pdfBin !== "") {
						$pdfPath = $dir . $stem . ".pdf";
						if (@file_put_contents($pdfPath, $pdfBin, LOCK_EX) !== false) {
							@unlink($file);
							$result = array("ok" => true, "path" => $pdfPath, "format" => "pdf", "error" => "");
						}
					}
					if ($result === null) {
						$result = array("ok" => false, "path" => "", "format" => "", "error" => "pdf convert failed");
					}
				}
				if ($prevReportsKind === null) {
					unset($GLOBALS["rkrk_assess_reports_kind"]);
				} else {
					$GLOBALS["rkrk_assess_reports_kind"] = $prevReportsKind;
				}
				if (is_array($result) && !empty($result["ok"]) && (($result["format"] ?? "") === "pdf")) {
					$converted++;
				}
			}
		}
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
		return $converted;
	}
}

if (!function_exists("rkrk_assess_save_checklist_print_report")) {
	/**
	 * @param callable():string $htmlBuilder Captures print HTML (e.g. showChecklistPrint via OB).
	 * @return array{ok:bool, path:string, format:string, error:string}
	 */
	function rkrk_assess_save_checklist_print_report(string $resultId, callable $htmlBuilder): array {
		$basename = rkrk_assess_report_stem_from_result($resultId);
		if ($basename === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "empty id");
		}
		$html = "";
		try {
			$html = (string) $htmlBuilder();
		} catch (\Throwable $e) {
			return array("ok" => false, "path" => "", "format" => "", "error" => $e->getMessage());
		}
		return rkrk_assess_write_report_pdf($basename, $html);
	}
}

if (!function_exists("rkrk_assess_save_checklist_print_report_from_path")) {
	/**
	 * @param callable():string $htmlBuilder
	 * @return array{ok:bool, path:string, format:string, error:string}
	 */
	function rkrk_assess_save_checklist_print_report_from_path(string $jsonPath, callable $htmlBuilder): array {
		return rkrk_assess_save_checklist_print_report(pathinfo($jsonPath, PATHINFO_FILENAME), $htmlBuilder);
	}
}


if (!function_exists("rkrk_assess_report_parse_answer_letters")) {
	/** @return list<string> */
	function rkrk_assess_report_parse_answer_letters(string $raw): array {
		$raw = trim($raw);
		if ($raw === "" || $raw === "—") {
			return array();
		}
		if (strcasecmp($raw, "Ja") === 0 || strcasecmp($raw, "Nein") === 0) {
			return array($raw);
		}
		$parts = preg_split('/\s*\|\s*/u', $raw) ?: array();
		$out = array();
		foreach ($parts as $p) {
			$p = trim((string) $p);
			if ($p === "") {
				continue;
			}
			if (preg_match('/^([A-H]|Ja|Nein)\b/u', $p, $m)) {
				$out[] = $m[1];
			} elseif (preg_match('/^[A-H]$/u', $p)) {
				$out[] = $p;
			}
		}
		return array_values(array_unique($out));
	}
}

if (!function_exists("rkrk_assess_report_parse_option_map_from_labels")) {
	/** @return array<string,string> letter => text */
	function rkrk_assess_report_parse_option_map_from_labels(string ...$labels): array {
		$map = array();
		foreach ($labels as $lab) {
			$lab = trim($lab);
			if ($lab === "") {
				continue;
			}
			foreach (preg_split('/\s*\|\s*/u', $lab) ?: array() as $part) {
				$part = trim((string) $part);
				if (preg_match('/^([A-H])\s*:\s*(.+)$/us', $part, $m)) {
					$letter = $m[1];
					$text = trim($m[2]);
					if ($text !== "" && !isset($map[$letter])) {
						$map[$letter] = $text;
					}
				}
			}
		}
		return $map;
	}
}

if (!function_exists("rkrk_assess_report_option_texts_for_qid")) {
	/**
	 * Optionstexte A.. aus gespeicherter Auswahl oder Quiz-CSV (Nr/QID).
	 *
	 * @param list<string>|string $stored
	 * @return list<string>
	 */
	function rkrk_assess_report_option_texts_for_qid(string $qid, $stored = array(), string $quizKey = ""): array {
		if (is_string($stored)) {
			$stored = $stored !== "" ? array_map("trim", explode("|", $stored)) : array();
		}
		if (is_array($stored)) {
			$stored = array_values(array_filter(array_map(static function ($s) {
				return trim((string) $s);
			}, $stored), static function ($s) {
				return $s !== "";
			}));
			if ($stored !== array()) {
				return $stored;
			}
		}
		$qid = trim($qid);
		if ($qid === "") {
			return array();
		}
		static $cache = array();
		$quizKey = trim($quizKey);
		$cacheKey = strtolower($quizKey) . "|" . $qid;
		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}
		$candidates = array();
		$quizLoc = isset($GLOBALS["QuizLoc"]) ? rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/" : "";
		if ($quizLoc !== "/" && is_dir($quizLoc)) {
			$listPath = $quizLoc . "AssessmentListe.csv";
			if (is_readable($listPath) && $quizKey !== "") {
				$fh = @fopen($listPath, "rb");
				if ($fh) {
					$header = fgetcsv($fh, 0, ";");
					if (is_array($header)) {
						$header = array_map(static function ($h) {
							return strtolower(trim((string) $h));
						}, $header);
						$iKey = array_search("key", $header, true);
						$iFile = array_search("fragendatei", $header, true);
						if ($iKey === false) {
							$iKey = array_search("quizkey", $header, true);
						}
						while (($row = fgetcsv($fh, 0, ";")) !== false) {
							if (!is_array($row) || $iKey === false || $iFile === false) {
								continue;
							}
							$k = trim((string) ($row[$iKey] ?? ""));
							if (strcasecmp($k, $quizKey) !== 0) {
								continue;
							}
							$fn = trim((string) ($row[$iFile] ?? ""));
							if ($fn !== "") {
								$candidates[] = $quizLoc . basename($fn);
							}
							break;
						}
					}
					fclose($fh);
				}
			}
			foreach (array("RKRS Einsatzpruefung.csv", "RKRS_Einsatzpruefung.csv") as $fn) {
				$candidates[] = $quizLoc . $fn;
			}
		}
		$out = array();
		foreach ($candidates as $path) {
			if (!is_readable($path)) {
				continue;
			}
			$fh = @fopen($path, "rb");
			if (!$fh) {
				continue;
			}
			$header = fgetcsv($fh, 0, ";");
			if (!is_array($header)) {
				fclose($fh);
				continue;
			}
			$headerNorm = array_map(static function ($h) {
				$h = strtolower(trim((string) $h));
				$h = preg_replace('/^\xef\xbb\xbf/', "", $h);
				return (string) $h;
			}, $header);
			$iNr = array_search("nr", $headerNorm, true);
			if ($iNr === false) {
				$iNr = array_search("qid", $headerNorm, true);
			}
			$iOpts = array_search("loesungsauswahl", $headerNorm, true);
			if ($iNr === false || $iOpts === false) {
				fclose($fh);
				continue;
			}
			while (($row = fgetcsv($fh, 0, ";")) !== false) {
				if (!is_array($row)) {
					continue;
				}
				if (trim((string) ($row[$iNr] ?? "")) !== $qid) {
					continue;
				}
				$raw = trim((string) ($row[$iOpts] ?? ""));
				if ($raw !== "") {
					$out = array_values(array_filter(array_map("trim", explode("|", $raw)), static function ($s) {
						return $s !== "";
					}));
				}
				break;
			}
			fclose($fh);
			if ($out !== array()) {
				break;
			}
		}
		$cache[$cacheKey] = $out;
		return $out;
	}
}

if (!function_exists("rkrk_assess_report_feedback_opt_row_html")) {
	/**
	 * Eine Optionszeile: farbiges Symbol + dunkler Text + kurzer Status (lesbar, ohne Rahmen).
	 */
	function rkrk_assess_report_feedback_opt_row_html(string $kind, string $sym, string $textHtml, string $tagHtml = ""): string {
		$fg = "#1e293b";
		$accent = "#64748b";
		if ($kind === "richtig") {
			$fg = "#14532d";
			$accent = "#3d6b3a";
		} elseif ($kind === "falsch") {
			$fg = "#7f1d1d";
			$accent = "#b91c1c";
		} elseif ($kind === "verpasst") {
			$fg = "#9a3412";
			$accent = "#c2410c";
		}
		// Einzeilig ohne Block-Abstand (TCPDF setzt sonst Leerzeilen zwischen Divs)
		$html = "";
		if ($sym !== "") {
			$html .= "<font color=\"" . $accent . "\"><b>" . $sym . "</b></font> ";
		} else {
			$html .= "<font color=\"#94a3b8\">·</font> ";
		}
		$html .= "<font color=\"" . $fg . "\">" . $textHtml . "</font>";
		if ($tagHtml !== "") {
			$html .= " <font color=\"" . $accent . "\"><span style=\"font-size:7.5pt;\">" . $tagHtml . "</span></font>";
		}
		return $html;
	}
}

if (!function_exists("rkrk_assess_report_format_feedback_options_html")) {
	/**
	 * Alle Optionen mit Trainings-Feedback: ✓ richtig gewählt / ★ richtig, nicht gewählt / ✗ falsch gewählt.
	 *
	 * @param array<string,mixed> $row
	 */
	function rkrk_assess_report_format_feedback_options_html(array $row, callable $h, string $quizKey = ""): string {
		$antwortRaw = trim((string) ($row["antwort"] ?? ""));
		$loesungRaw = trim((string) ($row["loesung"] ?? ""));
		$antLabel = trim((string) ($row["antwortLabel"] ?? ""));
		$loesLabel = trim((string) ($row["loesungLabel"] ?? ""));
		$picked = rkrk_assess_report_parse_answer_letters($antwortRaw !== "" ? $antwortRaw : $antLabel);
		$correct = rkrk_assess_report_parse_answer_letters($loesungRaw !== "" ? $loesungRaw : $loesLabel);
		$pickedSet = array_fill_keys($picked, true);
		$correctSet = array_fill_keys($correct, true);

		// Ja/Nein — kompakte Tabelle ohne Leerzeilen (TCPDF)
		if (
			(isset($correctSet["Ja"]) || isset($correctSet["Nein"]))
			|| (isset($pickedSet["Ja"]) || isset($pickedSet["Nein"]))
		) {
			$rows = "";
			foreach (array("Ja", "Nein") as $val) {
				$inCorr = isset($correctSet[$val]);
				$inPick = isset($pickedSet[$val]);
				$kind = "";
				$sym = "";
				$tag = "";
				if ($inCorr && $inPick) {
					$kind = "richtig";
					$sym = "✓";
					$tag = "richtig gewählt";
				} elseif ($inPick && !$inCorr) {
					$kind = "falsch";
					$sym = "✗";
					$tag = "falsch gewählt";
				} elseif ($inCorr && !$inPick) {
					$kind = "verpasst";
					$sym = "★";
					$tag = "richtig, nicht gewählt";
				}
				$rows .= "<tr><td style=\"padding:0;margin:0;line-height:1.15;font-size:8.5pt;\">"
					. rkrk_assess_report_feedback_opt_row_html($kind, $sym, $h($val), $tag !== "" ? $h($tag) : "")
					. "</td></tr>";
			}
			return "<table class=\"pf-fb-list\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:1px 0 0;border-collapse:collapse;\">"
				. $rows . "</table>";
		}

		$qid = trim((string) ($row["qid"] ?? ""));
		$opts = rkrk_assess_report_option_texts_for_qid(
			$qid,
			$row["loesungsauswahl"] ?? ($row["Loesungsauswahl"] ?? array()),
			$quizKey
		);
		$letters = range("A", "H");
		$map = rkrk_assess_report_parse_option_map_from_labels($antLabel, $loesLabel);
		if ($opts !== array()) {
			foreach ($opts as $i => $text) {
				if (!isset($letters[$i])) {
					break;
				}
				$L = $letters[$i];
				if (!isset($map[$L]) && trim((string) $text) !== "") {
					$map[$L] = trim((string) $text);
				}
			}
		}
		// Alle Buchstaben aus Map + Antwort/Lösung
		$order = array();
		foreach ($letters as $L) {
			if (isset($map[$L]) || isset($pickedSet[$L]) || isset($correctSet[$L])) {
				$order[] = $L;
			}
		}
		if ($order === array() && $opts !== array()) {
			foreach ($opts as $i => $text) {
				if (!isset($letters[$i])) {
					break;
				}
				$order[] = $letters[$i];
				if (!isset($map[$letters[$i]])) {
					$map[$letters[$i]] = trim((string) $text);
				}
			}
		}
		if ($order === array()) {
			return rkrk_assess_report_format_choice_html(
				$antLabel !== "" ? $antLabel : ($antwortRaw !== "" ? $antwortRaw : "—"),
				$h
			);
		}

		// Legende nur einmal pro Abschnitt (im Report-Body), nicht je Frage
		$rows = "";
		foreach ($order as $L) {
			$inCorr = isset($correctSet[$L]);
			$inPick = isset($pickedSet[$L]);
			$kind = "";
			$sym = "";
			$tag = "";
			if ($inCorr && $inPick) {
				$kind = "richtig";
				$sym = "✓";
				$tag = "richtig gewählt";
			} elseif ($inPick && !$inCorr) {
				$kind = "falsch";
				$sym = "✗";
				$tag = "falsch gewählt";
			} elseif ($inCorr && !$inPick) {
				$kind = "verpasst";
				$sym = "★";
				$tag = "richtig, nicht gewählt";
			}
			$text = isset($map[$L]) ? $map[$L] : "";
			$line = $L . ($text !== "" ? (": " . $text) : "");
			$rows .= "<tr><td style=\"padding:0;margin:0;line-height:1.15;font-size:8.5pt;\">"
				. rkrk_assess_report_feedback_opt_row_html($kind, $sym, $h($line), $tag !== "" ? $h($tag) : "")
				. "</td></tr>";
		}
		return "<table class=\"pf-fb-list\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:1px 0 0;border-collapse:collapse;\">"
			. $rows . "</table>";
	}
}



if (!function_exists("rkrk_assess_report_format_choice_html")) {
	/** Mehrfachantworten «A: … | C: …» als kompakte Liste für PDF. */
	function rkrk_assess_report_format_choice_html(string $raw, callable $h): string {
		$raw = trim(function_exists("rkrk_assess_report_pdf_sanitize_text") ? rkrk_assess_report_pdf_sanitize_text($raw) : $raw);
		if ($raw === "" || $raw === "—") {
			return "<div class=\"pf-q-empty\">—</div>";
		}
		$parts = preg_split('/\s*\|\s*/u', $raw) ?: array();
		$parts = array_values(array_filter(array_map("trim", $parts), static function ($p) {
			return $p !== "";
		}));
		if (count($parts) <= 1) {
			return "<div class=\"pf-q-choice\">" . $h($raw) . "</div>";
		}
		$html = "<ul class=\"pf-q-list\">";
		foreach ($parts as $p) {
			$html .= "<li>" . $h($p) . "</li>";
		}
		$html .= "</ul>";
		return $html;
	}
}

if (!function_exists("rkrk_assess_sum_result_frage_punkte")) {
	/**
	 * Summe punkte/punkteMax aus Ergebniszeilen (PunkteModus).
	 *
	 * @param list<mixed> $fragen
	 * @return array{punkte_erreicht:float,punkte_max:float}|null
	 */
	function rkrk_assess_sum_result_frage_punkte(array $fragen): ?array {
		$pe = 0.0;
		$pm = 0.0;
		$has = false;
		foreach ($fragen as $row) {
			if (!is_array($row)) {
				continue;
			}
			if (!array_key_exists("punkteMax", $row) && !array_key_exists("punkte_max", $row)
				&& !array_key_exists("punkte", $row)
			) {
				continue;
			}
			$has = true;
			$p = $row["punkte"] ?? 0;
			$m = $row["punkteMax"] ?? ($row["punkte_max"] ?? 1);
			$pe += is_numeric((string) $p) ? (float) $p : 0.0;
			$pm += is_numeric((string) $m) ? (float) $m : 0.0;
		}
		if (!$has) {
			return null;
		}
		return array("punkte_erreicht" => $pe, "punkte_max" => $pm);
	}
}

if (!function_exists("rkrk_assess_build_digital_result_report_html")) {
	/**
	 * Gesamtresultat + nur falsch/teilweise (inkl. Loesungstext).
	 * Design-Referenz: Papierformular (rkrk_assess_paper_form_inc.php / mode=paperform).
	 *
	 * @param array<string, mixed> $meta
	 * @param list<array<string, mixed>> $fragen
	 */
	function rkrk_assess_build_digital_result_report_html(array $meta, array $fragen): string {
		$h = static function ($s): string {
			return htmlspecialchars(function_exists("rkrk_assess_report_pdf_sanitize_text") ? rkrk_assess_report_pdf_sanitize_text((string) $s) : (string) $s, ENT_QUOTES, "UTF-8");
		};
		$title = trim((string) ($meta["quiz_title"] ?? $meta["quiz"] ?? "Quiz"));
		$name = trim((string) ($meta["name"] ?? ""));
		$drone = trim((string) ($meta["drone"] ?? ""));
		$exam = trim((string) ($meta["exam"] ?? ""));
		$modus = trim((string) ($meta["quiz_modus"] ?? ""));
		$zeit = trim((string) ($meta["zeit"] ?? date("d.m.Y H:i")));
		$pe = (string) ($meta["punkte_erreicht"] ?? "");
		$pm = (string) ($meta["punkte_max"] ?? "");
		$bestanden = trim((string) ($meta["bestanden"] ?? ""));
		$anz = (string) ($meta["anz_fragen"] ?? "");
		$richtig = (string) ($meta["fragen_richtig"] ?? "");
		$erfuellt = trim((string) ($meta["erfuellt"] ?? ""));

		$metrics = function_exists("rkrk_assess_theory_result_metrics")
			? rkrk_assess_theory_result_metrics(array_merge($meta, array(
				"quiz_ergebnis_fragen" => $fragen,
				"erfuellt" => $erfuellt,
			)))
			: null;
		$peNum = is_array($metrics) ? (float) $metrics["pe"] : (is_numeric($pe) ? (float) $pe : null);
		$pmNum = is_array($metrics) ? (float) $metrics["pm"] : (is_numeric($pm) ? (float) $pm : null);
		if (!is_array($metrics)) {
			$sumPts = function_exists("rkrk_assess_sum_result_frage_punkte")
				? rkrk_assess_sum_result_frage_punkte($fragen)
				: null;
			if (is_array($sumPts) && ($sumPts["punkte_max"] ?? 0) > 0) {
				$peNum = (float) $sumPts["punkte_erreicht"];
				$pmNum = (float) $sumPts["punkte_max"];
			}
		}
		if ($peNum !== null) {
			$pe = (string) $peNum;
		}
		if ($pmNum !== null) {
			$pm = (string) $pmNum;
		}
		// Anzeige: Punkte auf 1/10 runden (5.6667 → 5.7)
		$fmtPt = static function ($n): string {
			if ($n === null || !is_numeric($n)) {
				return "";
			}
			$r = round((float) $n, 1);
			return (abs($r - (int) $r) < 0.00001) ? (string) (int) $r : number_format($r, 1, ".", "");
		};
		$peDisp = ($peNum !== null) ? $fmtPt($peNum) : trim($pe);
		$pmDisp = ($pmNum !== null) ? $fmtPt($pmNum) : trim($pm);
		$anzNum = is_array($metrics) ? (int) $metrics["anz"] : (is_numeric($anz) ? (int) $anz : 0);
		$richtigNum = is_array($metrics) ? (int) $metrics["richtig"] : (is_numeric($richtig) ? (int) $richtig : 0);
		if ($anz === "" && $anzNum > 0) {
			$anz = (string) $anzNum;
		}
		if ($richtig === "" && $richtigNum > 0) {
			$richtig = (string) $richtigNum;
		}
		$pct = is_array($metrics) ? (float) $metrics["pct"] : null;
		if ($pct === null) {
			if ($pmNum !== null && $pmNum > 0 && $peNum !== null) {
				$pct = round(($peNum / $pmNum) * 100, 1);
			} elseif ($anzNum > 0) {
				$pct = round(($richtigNum / $anzNum) * 100, 1);
			}
		}
		$passed = is_array($metrics) ? !empty($metrics["passed"]) : false;
		if (!is_array($metrics)) {
			$passed = (strtolower($bestanden) === "ja")
				|| (stripos($bestanden, "bestanden") !== false && stripos($bestanden, "nicht") === false);
			if ($pmNum !== null && $pmNum > 0 && $peNum !== null && is_numeric($erfuellt)) {
				$thr = (float) $erfuellt;
				$passed = ($peNum / $pmNum) >= $thr;
			} elseif ($bestanden === "" && $pct !== null && is_numeric($erfuellt)) {
				$thr = (float) $erfuellt;
				$passed = ($thr > 0) ? (($pct / 100.0) >= $thr) : $passed;
			}
		}
		$bestanden = $passed ? "Ja" : "Nein";
		$punkteModusLabel = "";
		if (is_array($metrics) && function_exists("rkrk_assess_punkte_modus_label")) {
			$punkteModusLabel = rkrk_assess_punkte_modus_label((string) ($metrics["punkte_modus"] ?? ""));
		}
		$resultClass = $passed ? "pass" : "fail";
		$resultLabel = $passed ? "Bestanden" : "Nicht bestanden";
		if ($bestanden !== "" && strtolower($bestanden) !== "ja" && strtolower($bestanden) !== "nein") {
			$resultLabel = $bestanden;
		}
		$resultMark = $passed ? "✓ " : "✗ ";

		$wrongCount = 0;
		$rowsHtml = "";
		$quizKey = trim((string) ($meta["quiz_key"] ?? ($meta["quiz"] ?? "")));
		foreach ($fragen as $row) {
			if (!is_array($row)) {
				continue;
			}
			$status = strtolower(trim((string) ($row["status"] ?? "")));
			$isFull = !empty($row["richtig"]) || $status === "full";
			if ($isFull || $status === "full") {
				continue;
			}
			if ($status === "" && empty($row["richtig"])) {
				$status = "zero";
			}
			$wrongCount++;
			$nr = (string) ($row["nr"] ?? "");
			$qid = (string) ($row["qid"] ?? "");
			$frage = (string) ($row["frage"] ?? "");
			$lt = trim((string) ($row["loesungstext"] ?? ($row["print"] ?? ($row["erklaerung"] ?? ""))));
			$isPartial = ($status === "partial");
			$statusLabel = $isPartial ? "~ teilweise" : "✗ falsch";
			$hintClass = $isPartial ? "pf-q-answer-hint--multi" : "pf-q-answer-hint--wrong";
			$qAccent = $isPartial ? "pf-q--partial" : "pf-q--wrong";
			$fbHtml = rkrk_assess_report_format_feedback_options_html($row, $h, $quizKey);
			$rowsHtml .= "<div class=\"pf-q " . $qAccent . "\">"
				. "<div class=\"pf-q-text\"><span class=\"pf-q-nr\">Frage " . $h($nr !== "" ? $nr : "?") . "</span>"
				. ($qid !== "" ? "<span class=\"pf-q-ref\">ID " . $h($qid) . "</span>" : "")
				. " <span class=\"pf-q-answer-hint " . $hintClass . "\">" . $h($statusLabel) . "</span></div>"
				. "<div class=\"pf-q-frage\">" . $h($frage) . "</div>"
				. $fbHtml;
			if ($lt !== "") {
				$rowsHtml .= "<div class=\"pf-printblock\"><strong>Erläuterung</strong><br>" . nl2br($h($lt)) . "</div>";
			}
			$rowsHtml .= "</div>";
		}
		if ($rowsHtml === "") {
			$rowsHtml = "<div class=\"pf-ok\">Alle bewerteten Fragen korrekt — keine Korrekturen nötig.</div>";
		}

		$logoSrc = function_exists("rkrk_assess_report_logo_src_html")
			? rkrk_assess_report_logo_src_html()
			: (function_exists("rkrk_assess_report_logo_src_portable")
				? rkrk_assess_report_logo_src_portable()
				: "../../images/" . (isset($GLOBALS["logopic"]) ? basename((string) $GLOBALS["logopic"]) : "rkrs_logo.png"));

		$metaRows = array();
		$addMeta = static function (string $label, string $value) use (&$metaRows): void {
			$value = trim($value);
			if ($value === "") {
				return;
			}
			$metaRows[] = array($label, $value);
		};
		$addMeta("Name Pilot/in", $name);
		$addMeta("Datum", $zeit);
		$addMeta("EXAM", $exam);
		$addMeta("Drohne", $drone);
		$addMeta("Modus", rkrk_assess_report_modus_display($modus));
		if ($punkteModusLabel !== "") {
			$addMeta("Punktezählung", $punkteModusLabel);
		}
		if ($peDisp !== "" || $pmDisp !== "") {
			$addMeta("Punkte", trim($peDisp . " / " . $pmDisp));
		}
		if ($anz !== "" || $richtig !== "") {
			$addMeta("Fragen richtig", trim($richtig . " / " . $anz));
		}
		if ($pct !== null) {
			$addMeta("Ergebnis", $pct . " %");
		}
		if ($erfuellt !== "" && is_numeric($erfuellt)) {
			$addMeta("Schwelle", round(((float) $erfuellt) * 100, 1) . " %");
		}

				$metaHtml = "<table class=\"pf-sec-grid\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"2\"><tr>";
		$col = 0;
		foreach ($metaRows as $pair) {
			if ($col > 0 && ($col % 2) === 0) {
				$metaHtml .= "</tr><tr>";
			}
			$metaHtml .= "<td class=\"pf-field\" width=\"50%\" valign=\"top\" style=\"width:50%;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;\">"
				. "<span class=\"pf-label\" style=\"font-size:7.5pt;font-weight:700;color:#64748b;\">" . $h($pair[0]) . ":</span> "
				. "<span class=\"pf-line\" style=\"font-size:9.5pt;font-weight:600;color:#111;\">" . $h($pair[1]) . "</span>"
				. "</td>";
			$col++;
		}
		if ($col > 0 && ($col % 2) === 1) {
			$metaHtml .= "<td class=\"pf-field pf-field-empty\" width=\"50%\">&nbsp;</td>";
		}
		$metaHtml .= "</tr></table>";

		$scoreBits = array();
		if ($pct !== null) {
			$scoreBits[] = $pct . " %";
		}
		if ($peDisp !== "" || $pmDisp !== "") {
			$scoreBits[] = "Punkte " . trim($peDisp . "/" . $pmDisp);
		} elseif ($anz !== "" || $richtig !== "") {
			$scoreBits[] = "Richtig " . trim($richtig . "/" . $anz);
		}
		$scoreLine = implode(" / ", $scoreBits);
		$wrongNote = $wrongCount > 0
			? ($wrongCount === 1 ? "1 Frage zur Nacharbeit" : ($wrongCount . " Fragen zur Nacharbeit"))
			: "Keine Nacharbeit nötig";

		// Resultat-Banner wie Live-Beurteilung (Stufe-Banner): farbig, links Status, rechts Detail
		$legendBg = $passed ? "#eef6ec" : "#fde8e8";
		$legendFg = $passed ? "#3d6b3a" : "#b91c1c";
		$legendLeft = trim($resultMark . $resultLabel);
		$legendRight = $scoreLine !== "" ? $scoreLine : "—";
		$legendHtml =
			"<table class=\"pf-legend " . $resultClass . "\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\""
			. $h($legendBg) . "\" style=\"width:100%;margin:0 0 8px;padding:0;border:none;border-collapse:collapse;background-color:"
			. $h($legendBg) . ";\">"
			. "<tr>"
			. "<td class=\"result-label\" valign=\"middle\" width=\"40%\" bgcolor=\"" . $h($legendBg)
			. "\" style=\"font-size:12pt;font-weight:800;line-height:1.2;color:"
			. $h($legendFg) . ";padding:6px 8px;background-color:" . $h($legendBg) . ";\">"
			. "<font color=\"" . $h($legendFg) . "\"><b>" . $h($legendLeft) . "</b></font></td>"
			. "<td class=\"result-score\" align=\"right\" valign=\"middle\" width=\"60%\" bgcolor=\"" . $h($legendBg)
			. "\" style=\"font-size:9.5pt;font-weight:700;line-height:1.25;text-align:right;color:"
			. $h($legendFg) . ";padding:6px 8px;background-color:" . $h($legendBg) . ";\">"
			. "<font color=\"" . $h($legendFg) . "\"><b>" . $h($legendRight) . "</b></font></td>"
			. "</tr></table>";

		$css = "
@page{size:A4 portrait;margin:8mm 8mm;}
body.rkrk-digital-report,body.pf-report{font-family:dejavusans,Segoe UI,Arial,sans-serif;font-size:9.5pt;line-height:1.25;color:#111;margin:0;padding:0;background:#fff;}
body.rkrk-digital-report .print-header,body.rkrk-digital-report .print-header-table,body.rkrk-digital-report .pf-header{width:100%;margin:0 0 8px;padding:0 0 6px;border:none;border-collapse:collapse;}
body.rkrk-digital-report .print-header-table td{vertical-align:top;padding:0;}
body.rkrk-digital-report .print-title{margin:0;font-size:13pt;line-height:1.2;font-weight:bold;color:#111;}
body.rkrk-digital-report .print-subtitle,body.rkrk-digital-report .pf-sub{display:block;font-size:9pt;color:#475569;font-weight:normal;margin-top:2px;}
body.rkrk-digital-report .print-logo,body.rkrk-digital-report .pf-logo{width:32%;text-align:right;padding:0 !important;margin:0;}
body.rkrk-digital-report .print-logo img,body.rkrk-digital-report .pf-logo img{max-height:40px;height:40px;width:auto;display:block;margin:0 0 0 auto;}
body.rkrk-digital-report .pf-legend{width:100%;margin:0 0 8px;padding:0;border:none;border-collapse:collapse;}
body.rkrk-digital-report .pf-legend.pass{background:#eef6ec;}
body.rkrk-digital-report .pf-legend.fail{background:#fde8e8;}
body.rkrk-digital-report .pf-legend .result-label{font-size:12pt;font-weight:800;}
body.rkrk-digital-report .pf-legend.pass .result-label{color:#3d6b3a;}
body.rkrk-digital-report .pf-legend.fail .result-label{color:#b91c1c;}
body.rkrk-digital-report .pf-legend .result-score{font-size:9.5pt;font-weight:700;text-align:right;}
body.rkrk-digital-report .pf-legend.pass .result-score{color:#3d6b3a;}
body.rkrk-digital-report .pf-legend.fail .result-score{color:#b91c1c;}
body.rkrk-digital-report .pf-sec{margin:6px 0 4px;padding-top:4px;border:none;}
body.rkrk-digital-report .pf-sec h2{margin:0 0 4px;font-size:9.5pt;color:#1e3a1e;font-weight:bold;}
body.rkrk-digital-report .pf-sec-note{font-size:8.5pt;color:#64748b;margin:0 0 4px;}
body.rkrk-digital-report .pf-sec-grid{width:100%;margin:0;border-collapse:collapse;border:none;}
body.rkrk-digital-report .pf-field{margin:0;padding:1px 6px 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;}
body.rkrk-digital-report .pf-field-empty{border:none;background:transparent;}
body.rkrk-digital-report .pf-label{font-weight:700;font-size:7.5pt;color:#64748b;}
body.rkrk-digital-report .pf-line{font-size:9.5pt;color:#111;font-weight:600;}
body.rkrk-digital-report .pf-q{margin:0 0 6px;padding:2px 0 6px 0;border:none;border-bottom:1px solid #cbd5e1;background:transparent;page-break-inside:avoid;}
body.rkrk-digital-report .pf-q--wrong{background:transparent;}
body.rkrk-digital-report .pf-q--partial{background:transparent;}
body.rkrk-digital-report .pf-fb-legend{font-size:7.5pt;color:#64748b;margin:0 0 2px;line-height:1.15;}
body.rkrk-digital-report .pf-fb-list{margin:0;padding:0;border-collapse:collapse;font-size:8.5pt;line-height:1.15;}
body.rkrk-digital-report .pf-fb-list td{padding:0;margin:0;line-height:1.15;font-size:8.5pt;vertical-align:top;}
body.rkrk-digital-report .pf-fb-opt{margin:0;padding:0;font-size:8.5pt;line-height:1.15;border:none;background:transparent;}
body.rkrk-digital-report .pf-fb-opt--richtig{background:transparent;border:none;color:#14532d;}
body.rkrk-digital-report .pf-fb-opt--falsch{background:transparent;border:none;color:#7f1d1d;}
body.rkrk-digital-report .pf-fb-opt--verpasst{background:transparent;border:none;color:#9a3412;}
body.rkrk-digital-report .pf-fb-sym{font-weight:700;min-width:1em;display:inline-block;font-family:dejavusans,helvetica,sans-serif;}
body.rkrk-digital-report .pf-fb-sym--empty{visibility:hidden;}
body.rkrk-digital-report .pf-q-nr{font-weight:800;margin-right:4px;}
body.rkrk-digital-report .pf-q-ref{font-size:7pt;font-weight:400;color:#94a3b8;margin-left:3px;}
body.rkrk-digital-report .pf-q-text{margin:0 0 1px;font-weight:600;font-size:9.5pt;}
body.rkrk-digital-report .pf-q-frage{margin:0 0 2px;font-weight:400;font-size:9pt;color:#1e293b;}
body.rkrk-digital-report .pf-q-answer-hint{font-size:8pt;margin:0 0 0 4px;padding:1px 6px;border-radius:3px;display:inline-block;font-weight:700;}
body.rkrk-digital-report .pf-q-answer-hint--wrong{color:#991b1b;background:#fde8e8;border:none;}
body.rkrk-digital-report .pf-q-answer-hint--multi{color:#9a5b12;background:#fef6e8;border:none;}
body.rkrk-digital-report .pf-q-col{padding:0 0 2px 0;border:none;}body.rkrk-digital-report .pf-q-col-sol{padding-top:3px;border:none;}
body.rkrk-digital-report .pf-q-list{margin:2px 0 0 0;padding:0 0 0 14px;font-size:8.5pt;line-height:1.3;}
body.rkrk-digital-report .pf-q-list li{margin:0 0 1px;}
body.rkrk-digital-report .pf-q-choice,.pf-q-empty{font-size:8.5pt;margin-top:2px;}
body.rkrk-digital-report .pf-printblock{font-size:8pt;color:#1e293b;margin-top:4px;padding:3px 5px;background:#eef2f6;border:none;}
body.rkrk-digital-report .pf-ok{padding:6px 8px;background:#eef6ec;border:none;color:#3d6b3a;font-size:9.5pt;}
.rkrk-report-copyright{margin-top:10px;padding-top:6px;border:none;font-size:7.5pt;color:#64748b;line-height:1.3;}
";

		return "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\">"
			. (function_exists("rkrk_assess_favicon_link_tags_html") ? rkrk_assess_favicon_link_tags_html() : "")
			. "<title>" . $h($title) . " – Abschlussbericht</title>"
			. "<style id=\"rkrk-digital-report-css\">" . $css . "</style>"
			. "</head><body class=\"rkrk-assess-print rkrk-digital-report pf-report\">"
			. "<table class=\"print-header-table pf-header print-header\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>"
			. "<td class=\"print-title\" valign=\"top\" width=\"68%\">"
			. "<div style=\"font-size:13pt;font-weight:bold;line-height:1.2;color:#111;\">" . $h($title !== "" ? $title : "Abschlussbericht") . "</div>"
			. "<span class=\"print-subtitle pf-sub\">Abschlussbericht</span>"
			. "</td>"
			. "<td class=\"print-logo pf-logo\" align=\"right\" valign=\"top\" width=\"32%\" style=\"padding:0;text-align:right;\">"
			. "<img src=\"" . htmlspecialchars($logoSrc, ENT_QUOTES, "UTF-8") . "\" alt=\"Logo\" height=\"40\" align=\"right\" style=\"height:40px;width:auto;display:block;margin-left:auto;margin-right:0;\">"
			. "</td>"
			. "</tr></table>"
			. "<div class=\"pf-sec\"><h2>Allgemein</h2>" . $metaHtml . "</div>"
			. $legendHtml
			. "<div class=\"pf-sec\"><h2>Falsche / teilweise Antworten</h2>"
			. "<div class=\"pf-sec-note\">" . $h($wrongNote) . "</div>"
			. ($wrongCount > 0 ? "<div class=\"pf-fb-legend\">✓ richtig gewählt · ★ richtig, nicht gewählt · ✗ falsch gewählt</div>" : "")
			. $rowsHtml
			. "</div>"
			. (function_exists("rkrk_assess_report_copyright_footer_html") ? rkrk_assess_report_copyright_footer_html() : "")
			. "</body></html>";
	}
}

if (!function_exists("rkrk_assess_save_digital_result_report")) {
	/**
	 * @param array<string, mixed> $meta
	 * @param list<array<string, mixed>> $fragen
	 * @return array{ok:bool, path:string, format:string, error:string}
	 */
	function rkrk_assess_save_digital_result_report(array $meta, array $fragen): array {
		$code = trim((string) ($meta["exam"] ?? ($meta["exam_code"] ?? "")));
		$name = trim((string) ($meta["name"] ?? ""));
		$session = null;
		if ($code !== "" && function_exists("rkrk_assess_exam_load")) {
			$session = rkrk_assess_exam_load($code, $name);
		}
		$basename = "";
		if ($code !== "") {
			$basename = rkrk_assess_report_basename_from_exam_json($code, $name, is_array($session) ? $session : null);
		}
		if ($basename === "") {
			$quizKey = trim((string) ($meta["quiz_key"] ?? ($meta["quiz"] ?? "")));
			$title = $quizKey;
			if ($quizKey !== "" && function_exists("rkrk_assess_quiz_title_from_key")) {
				$title = rkrk_assess_quiz_title_from_key($quizKey);
			}
			$rkrsId = trim((string) ($meta["rkrs_pilot_id"] ?? ($meta["PilotID"] ?? "")));
			$modus = trim((string) ($meta["quiz_modus"] ?? ($meta["QuizModus"] ?? "")));
			if ($title !== "" && function_exists("rkrk_assess_build_quiz_result_stem")) {
				$basename = rkrk_assess_build_quiz_result_stem($title, $name, $rkrsId, $modus, date("Ymd"), true);
			} elseif ($quizKey !== "" && function_exists("rkrk_assess_build_result_stem")) {
				$basename = rkrk_assess_build_result_stem($title !== "" ? $title : $quizKey, $name, date("Ymd"), true, $rkrsId);
			} elseif ($quizKey !== "") {
				$basename = rkrk_assess_report_stem_from_result($quizKey . "_" . $name . "_" . date("Ymd"));
			}
		}
		if ($basename === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "exam json basename missing");
		}
		$html = rkrk_assess_build_digital_result_report_html($meta, $fragen);
		return rkrk_assess_write_report_pdf($basename, $html);
	}
}

if (!function_exists("rkrk_assess_report_download_href")) {
	/**
	 * Web-URL zum Report-Download (Proxy; results/reports/ ist oft 403).
	 *
	 * @param array{ok?:bool, path?:string, format?:string}|string $saveOrPath
	 */
	function rkrk_assess_report_download_href($saveOrPath, string $format = "", string $kind = ""): string {
		$path = "";
		if (is_array($saveOrPath)) {
			if (empty($saveOrPath["ok"])) {
				return "";
			}
			$path = trim((string) ($saveOrPath["path"] ?? ""));
			if ($format === "") {
				$format = trim((string) ($saveOrPath["format"] ?? ""));
			}
		} else {
			$path = trim((string) $saveOrPath);
		}
		if ($path === "" || !is_file($path)) {
			return "";
		}
		$stem = rkrk_assess_report_stem_from_result(pathinfo($path, PATHINFO_FILENAME));
		if ($stem === "") {
			return "";
		}
		if ($format === "") {
			$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
			$format = ($ext === "pdf") ? "pdf" : "html";
		}
		$script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
		$dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
		$base = ($dir === "" || $dir === ".") ? "/rkrk_assess_report_download.php" : ($dir . "/rkrk_assess_report_download.php");
		$url = $base . "?f=" . rawurlencode($stem) . "&t=" . rawurlencode($format === "pdf" ? "pdf" : "html");
		$kind = strtolower(trim($kind));
		if ($kind === "live" || $kind === "assess") {
			$url .= "&k=" . rawurlencode($kind);
		}
		return $url;
	}
}

if (!function_exists("rkrk_assess_report_href_for_result_stem")) {
	/**
	 * Vorhandenen Abschluss-Report (PDF bevorzugt) zu einem Result-Stamm verlinken.
	 */
	function rkrk_assess_report_href_for_result_stem(string $stem, string $kind = "assess"): string {
		$stem = function_exists("rkrk_assess_report_stem_from_result")
			? rkrk_assess_report_stem_from_result($stem)
			: preg_replace('/\.(json|pdf|html)$/i', "", basename(str_replace("\\", "/", $stem)));
		$stem = trim((string) $stem);
		if ($stem === "" || !function_exists("rkrk_assess_reports_dir") || !function_exists("rkrk_assess_report_download_href")) {
			return "";
		}
		$kind = (strtolower(trim($kind)) === "live") ? "live" : "assess";
		$kinds = ($kind === "live") ? array("live", "assess") : array("assess", "live");
		foreach ($kinds as $kindTry) {
			$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
			$GLOBALS["rkrk_assess_reports_kind"] = $kindTry;
			$dir = rkrk_assess_reports_dir();
			if ($prevKind === null) {
				unset($GLOBALS["rkrk_assess_reports_kind"]);
			} else {
				$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
			}
			if ($dir === "") {
				continue;
			}
			foreach (array("pdf", "html") as $fmt) {
				$path = $dir . $stem . "." . $fmt;
				if (is_file($path)) {
					return rkrk_assess_report_download_href($path, $fmt, $kindTry);
				}
			}
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_report_find_existing_href")) {
	/** Vorhandenen Report (pdf bevorzugt) für Exam/Quiz finden. */
	function rkrk_assess_report_find_existing_href(string $examCode = "", string $name = "", string $quizKey = ""): string {
		$dir = rkrk_assess_reports_dir();
		if ($dir === "") {
			return "";
		}
		$stem = "";
		if ($examCode !== "") {
			$session = (function_exists("rkrk_assess_exam_load")) ? rkrk_assess_exam_load($examCode, $name) : null;
			$stem = rkrk_assess_report_basename_from_exam_json($examCode, $name, is_array($session) ? $session : null);
		}
		if ($stem === "" && $quizKey !== "") {
			$title = function_exists("rkrk_assess_quiz_title_from_key")
				? rkrk_assess_quiz_title_from_key($quizKey)
				: $quizKey;
			if (function_exists("rkrk_assess_build_quiz_result_stem")) {
				$stem = rkrk_assess_build_quiz_result_stem($title, $name, "", "", date("Ymd"), true);
			} elseif (function_exists("rkrk_assess_build_result_stem")) {
				$stem = rkrk_assess_build_result_stem($title, $name, date("Ymd"), true);
			}
		}
		if ($stem !== "") {
			foreach (array("pdf", "html") as $fmt) {
				$path = $dir . $stem . "." . $fmt;
				if (is_file($path)) {
					return rkrk_assess_report_download_href($path, $fmt, "assess");
				}
			}
		}
		// Fallback: Dateiname enthält EXAM-Code und/oder Pilot
		$codeSafe = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower(trim($examCode)));
		$codeSafe = trim((string) $codeSafe, "_");
		$nameSafe = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower(trim($name)));
		$nameSafe = trim((string) $nameSafe, "_");
		$quizSafe = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower(trim($quizKey)));
		$quizSafe = trim((string) $quizSafe, "_");
		$candidates = array();
		$reportFiles = array_merge(
			glob($dir . "*.pdf") ?: array(),
			glob($dir . "*.html") ?: array()
		);
		foreach ($reportFiles as $file) {
			if (!is_file($file)) {
				continue;
			}
			$bn = (string) basename($file);
			if (preg_match('/\.prep\.pdf$/i', $bn)) {
				continue;
			}
			$base = strtolower((string) pathinfo($file, PATHINFO_FILENAME));
			$score = 0;
			if ($codeSafe !== "" && strpos($base, $codeSafe) !== false) {
				$score += 4;
			}
			if ($nameSafe !== "" && strpos($base, $nameSafe) !== false) {
				$score += 2;
			}
			if ($quizSafe !== "" && strpos($base, $quizSafe) !== false) {
				$score += 1;
			}
			if ($score < 2) {
				continue;
			}
			$ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
			$candidates[] = array(
				"score" => $score + (($ext === "pdf") ? 0.5 : 0),
				"mtime" => (int) @filemtime($file),
				"path" => $file,
				"fmt" => ($ext === "pdf") ? "pdf" : "html",
			);
		}
		if (!empty($candidates)) {
			usort($candidates, function ($a, $b) {
				$cmp = ((float) ($b["score"] ?? 0)) <=> ((float) ($a["score"] ?? 0));
				if ($cmp !== 0) {
					return $cmp;
				}
				return ((int) ($b["mtime"] ?? 0)) <=> ((int) ($a["mtime"] ?? 0));
			});
			$best = $candidates[0];
			return rkrk_assess_report_download_href((string) $best["path"], (string) $best["fmt"], "assess");
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_ensure_report_from_completed_exam")) {
	/**
	 * Erzeugt Abschluss-Report (PDF bevorzugt) für eine abgeschlossene EXAM-Session.
	 *
	 * @param array<string, mixed> $row  Eintrag aus rkrk_assess_exam_list_completed()
	 * @return array{ok:bool, path:string, format:string, error:string, skipped:bool}
	 */
	function rkrk_assess_ensure_report_from_completed_exam(array $row): array {
		$stem = trim((string) ($row["stem"] ?? ""));
		if ($stem === "" && !empty($row["path"])) {
			$stem = pathinfo((string) $row["path"], PATHINFO_FILENAME);
		}
		$stem = rkrk_assess_report_stem_from_result($stem);
		if ($stem === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "stem missing", "skipped" => false);
		}
		$dir = rkrk_assess_reports_dir();
		if ($dir === "") {
			return array("ok" => false, "path" => "", "format" => "", "error" => "reports dir missing", "skipped" => false);
		}
		foreach (array("pdf", "html") as $fmt) {
			$path = $dir . $stem . "." . $fmt;
			if (is_file($path)) {
				return array("ok" => true, "path" => $path, "format" => $fmt, "error" => "", "skipped" => true);
			}
		}
		$result = isset($row["result"]) && is_array($row["result"]) ? $row["result"] : array();
		if ($result === array()) {
			return array("ok" => false, "path" => "", "format" => "", "error" => "result missing", "skipped" => false);
		}
		$fragen = isset($result["quiz_ergebnis_fragen"]) && is_array($result["quiz_ergebnis_fragen"])
			? $result["quiz_ergebnis_fragen"]
			: array();
		$quizKey = trim((string) ($row["quiz_key"] ?? ($result["quiz"] ?? "")));
		$meta = array(
			"quiz" => $quizKey,
			"quiz_key" => $quizKey,
			"quiz_title" => $quizKey,
			"name" => trim((string) ($row["name"] ?? ($result["name"] ?? ""))),
			"drone" => trim((string) ($row["drone"] ?? ($result["drone"] ?? ""))),
			"exam" => trim((string) ($row["exam_code"] ?? "")),
			"quiz_modus" => trim((string) ($row["quiz_modus"] ?? "")),
			"zeit" => trim((string) ($row["zeit"] ?? ($result["zeit"] ?? ""))),
			"punkte_erreicht" => (string) ($result["punkte_erreicht"] ?? ($row["richtig"] ?? "")),
			"punkte_max" => (string) ($result["punkte_max"] ?? ($row["anz"] ?? "")),
			"anz_fragen" => (string) ($result["anz_fragen"] ?? ($row["anz"] ?? "")),
			"fragen_richtig" => (string) ($result["fragen_richtig"] ?? ($row["richtig"] ?? "")),
			"bestanden" => (string) ($row["bestanden"] ?? ($result["bestanden"] ?? "")),
			"erfuellt" => (string) ($result["erfuellt"] ?? ($row["factor"] ?? "")),
		);
		if ($meta["zeit"] === "" && !empty($row["timestamp"])) {
			$meta["zeit"] = date("d.m.Y H:i", (int) $row["timestamp"]);
		}
		$html = rkrk_assess_build_digital_result_report_html($meta, $fragen);
		$save = rkrk_assess_write_report_pdf($stem, $html);
		$save["skipped"] = false;
		return $save;
	}
}

if (!function_exists("rkrk_assess_ensure_missing_exam_reports")) {
	/**
	 * Fehlende Abschluss-Reports für completed EXAM-JSONs erzeugen.
	 *
	 * @return array{created:int, skipped:int, failed:int, errors:list<string>}
	 */
	function rkrk_assess_ensure_missing_exam_reports(int $maxCreate = 50): array {
		$stats = array("created" => 0, "skipped" => 0, "failed" => 0, "errors" => array());
		if (!function_exists("rkrk_assess_exam_list_completed")) {
			return $stats;
		}
		$rows = rkrk_assess_exam_list_completed();
		foreach ($rows as $row) {
			if ($stats["created"] >= $maxCreate) {
				break;
			}
			$res = rkrk_assess_ensure_report_from_completed_exam($row);
			if (!empty($res["skipped"])) {
				$stats["skipped"]++;
				continue;
			}
			if (!empty($res["ok"])) {
				$stats["created"]++;
				continue;
			}
			$stats["failed"]++;
			$stem = trim((string) ($row["stem"] ?? ""));
			$err = trim((string) ($res["error"] ?? "unknown"));
			$stats["errors"][] = ($stem !== "" ? $stem : "?") . ": " . $err;
		}
		return $stats;
	}
}
