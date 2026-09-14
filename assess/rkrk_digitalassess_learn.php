<?php
/**
 * Liefert den Schulungs-/Lern-HTML-Inhalt für das iframe in rkrk_digitalassess.php.
 * Bindet rkrk_assess_theme.css (und bei RKRS-KI zusätzlich rkrk_assess_theme_rkrs.css) ein —
 * reine .html-Dateien unter data/assess/ haben diese URLs sonst nicht korrekt.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset . "scripts/rkrk_inputforms.php");
include_once($folderoffset . "scripts/rkrk_csvfiles.php");
include_once(__DIR__ . "/rkrk_assess_common.php");
include_once(__DIR__ . "/rkrk_digitalassess_read_render.php");

if (!$onRKRS) {
	// Iframe-Lerninhalt nicht nochmals hart sperren:
	// Die aufrufende Seite prüft Zugang bereits. Doppel-Checks hier führten in einigen
	// Nicht-RKRS-Setups zu leerem iframe statt Lerntext.
	if (function_exists("WriteToLog")) {
		WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "",$folderoffset);
	}
}

header("Content-Type: text/html; charset=UTF-8");

$quizKey = isset($_GET["quiz"]) ? trim((string)$_GET["quiz"]) : "";
$quizKeyDecoded = urldecode($quizKey);
if ($quizKey === "") {
	http_response_code(400);
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><p>Parameter <code>quiz</code> fehlt.</p></body></html>";
	exit;
}

$assessmentListeCsvPath = $QuizLoc . "AssessmentListe.csv";
$assessment = null;
$rows = csv_to_array_ascII($assessmentListeCsvPath, ";");
if (is_array($rows)) {
	foreach ($rows as $r) {
		if (!is_array($r) || !isset($r["Key"])) {
			continue;
		}
		$rowKey = trim((string)$r["Key"]);
		if (
			$rowKey === $quizKey ||
			$rowKey === $quizKeyDecoded ||
			strcasecmp($rowKey, $quizKey) === 0 ||
			strcasecmp($rowKey, $quizKeyDecoded) === 0
		) {
			$assessment = $r;
			break;
		}
	}
}

if ($assessment === null) {
	http_response_code(404);
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Nicht gefunden</title></head><body><p>Assessment nicht gefunden.</p></body></html>";
	exit;
}

// Sichtbarkeit wird im Parent (rkrk_digitalassess.php) entschieden; hier nur Rendern.

$lernRel = isset($assessment["LernDatei"]) ? trim((string)$assessment["LernDatei"]) : "";
$lernRel = str_replace("\\", "/", $lernRel);
$lernRel = ltrim($lernRel, "/");
$lernRel = preg_replace("/\.\.+/", "", $lernRel);
if ($lernRel === "" || preg_match("/^[\\p{L}\\p{N}_\\-\\/\\. ]+$/u", $lernRel) !== 1) {
	http_response_code(404);
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><p>Ungültige LernDatei.</p></body></html>";
	exit;
}

$learnFs = $QuizLoc . $lernRel;
if (!is_readable($learnFs)) {
	http_response_code(404);
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><p>Lerninhalt nicht lesbar.</p></body></html>";
	exit;
}

$html = @file_get_contents($learnFs);
if ($html === false || $html === "") {
	http_response_code(500);
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><p>Lerninhalt konnte nicht geladen werden.</p></body></html>";
	exit;
}

$inner = $html;
if (preg_match("~<body[^>]*>\\s*(.*?)\\s*</body~is", $html, $m)) {
	$inner = $m[1];
}
$driveLocLearn = function_exists("rkrk_assess_effective_drive_location")
	? rkrk_assess_effective_drive_location($assessment, array())
	: (function_exists("rkrk_assess_clean_drive_location_rel")
		? rkrk_assess_clean_drive_location_rel((string) ($assessment["DriveLocation"] ?? ""))
		: trim((string) ($assessment["DriveLocation"] ?? ""), "/"));
// Browser-Basis-URL (RKRS/RKRK/Portal), nicht Dateisystem-$QuizLoc — sonst laden Bilder im iframe nicht.
$baseDir = function_exists("rkrk_dga_rh_learn_base_dir_url")
	? rkrk_dga_rh_learn_base_dir_url($lernRel)
	: (rtrim(str_replace("\\", "/", $QuizLoc), "/") . "/" . dirname(str_replace("\\", "/", $lernRel)) . "/");

$themeRkrs = function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode();
$baseHref = $baseDir;
if (function_exists("rkrk_dga_rh_normalize_learn_inner_html")) {
	$inner = rkrk_dga_rh_normalize_learn_inner_html($inner, $lernRel, $driveLocLearn);
} elseif (function_exists("rkrk_dga_rh_resolve_relative_urls_in_fragment")) {
	$inner = rkrk_dga_rh_resolve_relative_urls_in_fragment($inner, $baseDir);
} elseif (function_exists("rkrk_assess_encode_urls_in_html_fragment")) {
	$inner = rkrk_assess_encode_urls_in_html_fragment($inner);
}
if (function_exists("rkrk_assess_encode_url_for_request")) {
	$baseHref = rkrk_assess_encode_url_for_request($baseDir);
}
$printView = isset($_GET["print"]) && (string) $_GET["print"] === "1";
$printTitle = trim((string) ($assessment["Titel"] ?? $quizKey));
$printLogoSrc = "";
if (function_exists("rkrk_assess_report_logo_src_html")) {
	$printLogoSrc = trim((string) rkrk_assess_report_logo_src_html());
}
if ($printLogoSrc === "") {
	$printLogoFile = isset($logopic) ? basename((string) $logopic) : "rkrs_logo.png";
	if ($printLogoFile === "") {
		$printLogoFile = "rkrs_logo.png";
	}
	if (function_exists("rkrk_assess_quiz_data_assess_web_prefix")) {
		$printLogoSrc = rtrim((string) rkrk_assess_quiz_data_assess_web_prefix(), "/") . "/images/" . $printLogoFile;
	} else {
		$printLogoSrc = "images/" . $printLogoFile;
	}
}
$printCopyrightHolder = function_exists("rkrk_assess_copyright_holder")
	? rkrk_assess_copyright_holder()
	: (isset($copyright) && trim((string) $copyright) !== "" ? trim((string) $copyright) : "rehkitzrettung.ch");
$printCopyrightText = "© " . date("Y") . " by " . $printCopyrightHolder;
$printDateText = date("d.m.Y");
$printLogoAlt = (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode())
	? "Rehkitzrettung Schweiz"
	: "Drohnenrehkitzrettung Knonaueramt";

// CSS liegt neben dieser PHP-Datei im assess/-Ordner; darf nicht vom <base>-Tag auf data/assess/ umgebogen werden.
$assessScriptDir = str_replace("\\", "/", dirname((string)($_SERVER["SCRIPT_NAME"] ?? "")));
$assessScriptDir = rtrim($assessScriptDir, "/");
if ($assessScriptDir === "" || $assessScriptDir === ".") {
	$assessScriptDir = "";
}
$themeCssHref = ($assessScriptDir !== "" ? $assessScriptDir : "") . "/rkrk_assess_theme.css";
$themeRkrsCssHref = ($assessScriptDir !== "" ? $assessScriptDir : "") . "/rkrk_assess_theme_rkrs.css";

echo "<!DOCTYPE html>\n";
echo "<html lang=\"de\">\n";
echo "<head>\n";
echo "<meta charset=\"UTF-8\">\n";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
echo "<base href=\"" . htmlspecialchars($baseHref, ENT_QUOTES, "UTF-8") . "\">\n";
if (function_exists("rkrk_assess_emit_favicon_link_tags")) {
	rkrk_assess_emit_favicon_link_tags();
} elseif (function_exists("rkrk_assess_favicon_link_tags_html")) {
	echo rkrk_assess_favicon_link_tags_html();
}
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlspecialchars($themeCssHref, ENT_QUOTES, "UTF-8") . "\">\n";
if ($themeRkrs) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlspecialchars($themeRkrsCssHref, ENT_QUOTES, "UTF-8") . "\">\n";
}
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"lernunterlagen.css\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlspecialchars(($assessScriptDir !== "" ? $assessScriptDir : "") . "/rkrk_schulung_lern.css", ENT_QUOTES, "UTF-8") . "\">\n";
if ($printView) {
	echo "<style>"
		. ".dle-learn-print-toolbar{margin:0 0 14px;padding:10px 12px;background:#f4f7f3;border:1px solid #d8dfd6;border-radius:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-family:system-ui,-apple-system,\"Segoe UI\",sans-serif;}"
		. ".dle-learn-print-toolbar button{border:1px solid #7a8f78;border-radius:8px;background:#48673a;color:#fff;padding:7px 14px;font-weight:700;cursor:pointer;font-size:14px;}"
		. ".dle-learn-print-toolbar .dle-learn-print-secondary{background:#f4f7f3;color:#1f2937;}"
		. ".dle-learn-print-header{margin:0 0 1.25rem;padding-bottom:.6rem;border-bottom:2px solid #003366;}"
		. ".dle-learn-print-header h1{margin:0 0 .25rem;font-size:1.4rem;color:#003366;line-height:1.25;}"
		. ".dle-learn-print-meta{margin:0;font-size:.9rem;color:#64748b;}"
		. "@media print{.dle-learn-print-toolbar{display:none!important;}html,body{margin:0;padding:0;background:#fff!important;}"
		. "body{padding:8mm 12mm 14mm;}section,figure{page-break-inside:avoid;}h1,h2,h3{page-break-after:avoid;}"
		. "img:not(.schulung-print-logo){max-width:100%!important;height:auto!important;}thead{display:table-header-group;}tr{page-break-inside:avoid;}}"
		. "</style>\n";
} else {
	echo "<style>html,body{margin:0;}</style>\n";
}
echo "<style>html{scroll-behavior:auto;}#rkrk-learn-doc-top{display:block;height:0;width:0;margin:0;padding:0;border:0;overflow:hidden;}</style>\n";
echo "</head>\n<body>\n";
echo "<span id=\"rkrk-learn-doc-top\" aria-hidden=\"true\"></span>\n";
echo "<img id=\"schulung-print-logo\" class=\"schulung-print-logo\" src=\""
	. htmlspecialchars($printLogoSrc, ENT_QUOTES, "UTF-8")
	. "\" alt=\""
	. htmlspecialchars($printLogoAlt, ENT_QUOTES, "UTF-8")
	. "\">\n";
echo "<div id=\"schulung-print-footer\" class=\"schulung-print-footer\">"
	. "<span class=\"schulung-print-date\">" . htmlspecialchars($printDateText, ENT_QUOTES, "UTF-8") . "</span>"
	. "<span id=\"schulung-print-copyright\" class=\"schulung-print-copyright\">"
	. htmlspecialchars($printCopyrightText, ENT_QUOTES, "UTF-8")
	. "</span></div>\n";
if ($printView) {
	echo "<div class=\"dle-learn-print-toolbar\">"
		. "<button type=\"button\" onclick=\"window.print()\">Drucken</button>"
		. "<button type=\"button\" class=\"dle-learn-print-secondary\" onclick=\"window.close()\">Schliessen</button>"
		. "</div>";
	echo "<header class=\"dle-learn-print-header\"><h1>" . htmlspecialchars($printTitle, ENT_QUOTES, "UTF-8") . "</h1>"
		. "<p class=\"dle-learn-print-meta\">Schulungstext · gespeicherte Version</p></header>";
}
echo $inner;
if (!$printView) {
	$zoomJsFs = "";
	$zoomJsCandidates = array(
		rtrim(str_replace("\\", "/", (string) $QuizLoc), "/") . "/lernunterlagen.js",
		dirname(str_replace("\\", "/", $learnFs)) . "/lernunterlagen.js",
	);
	$learnDir = dirname(str_replace("\\", "/", $lernRel));
	if ($learnDir !== "." && $learnDir !== "") {
		$zoomJsCandidates[] = rtrim(str_replace("\\", "/", (string) $QuizLoc), "/") . "/" . $learnDir . "/lernunterlagen.js";
	}
	$assessDataDir = realpath(dirname(__DIR__) . "/../data/assess");
	if ($assessDataDir !== false) {
		$zoomJsCandidates[] = str_replace("\\", "/", $assessDataDir) . "/lernunterlagen.js";
	}
	foreach ($zoomJsCandidates as $candidate) {
		if (is_readable($candidate)) {
			$zoomJsFs = $candidate;
			break;
		}
	}
	if ($zoomJsFs !== "") {
		echo "\n<script>\n" . file_get_contents($zoomJsFs) . "\n</script>\n";
	}
	$zoomJsWeb = rtrim(str_replace("\\", "/", (string) $baseHref), "/") . "/lernunterlagen.js?v=202607201400";
	echo "<script src=\"" . htmlspecialchars($zoomJsWeb, ENT_QUOTES, "UTF-8") . "\"></script>\n";
	echo "\n<script>\n";
	echo "(function(){function top(){try{if('scrollRestoration' in history)history.scrollRestoration='manual';}catch(e){}\n";
	echo "var a=document.getElementById('rkrk-learn-doc-top');\n";
	echo "if(a){try{a.scrollIntoView({block:'start',inline:'nearest'});}catch(e){}}\n";
	echo "var first=document.querySelector('section.lern-sektion,h1,h2,body>*:not(script):not(style)');\n";
	echo "if(first&&first!==a){try{first.scrollIntoView({block:'start',inline:'nearest'});}catch(e){}}\n";
	echo "window.scrollTo(0,0);document.documentElement.scrollTop=0;document.body.scrollTop=0;\n";
	echo "try{parent.postMessage({rkrkLearn:'scrollTop'},'*');}catch(e){}}\n";
	echo "if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',top);}else{top();}\n";
	echo "window.addEventListener('load',top);\n";
	echo "[0,50,200,600,1200].forEach(function(ms){setTimeout(top,ms);});\n";
	echo "})();\n</script>\n";
}
echo "\n</body>\n</html>";
