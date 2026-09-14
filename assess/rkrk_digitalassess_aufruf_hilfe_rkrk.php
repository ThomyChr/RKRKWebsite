<?php
/**
 * RKRK/interne Aufruf-Doku — nur mit ADMIN in der URL.
 */
$folderoffset = "../";
include_once __DIR__ . "/rkrk_assess_common.php";

$is_admin = isset($_GET["ADMIN"]) || (!empty($onAdmin));

if (!$is_admin) {
	http_response_code(403);
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Zugriff verweigert</title></head><body>";
	echo "<p>Interne Aufruf-Dokumentation ist nur mit <code>ADMIN</code> in der URL verfügbar.</p>";
	echo "</body></html>";
	exit;
}

$htmlPath = __DIR__ . "/rkrk_digitalassess_aufruf_hilfe_rkrk.html";
if (!is_readable($htmlPath)) {
	http_response_code(500);
	echo "Hilfedatei fehlt.";
	exit;
}

$html = (string) file_get_contents($htmlPath);
$injectHead = "";
if (function_exists("rkrk_assess_favicon_link_tags_html")) {
	$injectHead .= rkrk_assess_favicon_link_tags_html();
}
if (function_exists("rkrk_assess_emit_setting_carry_script")) {
	ob_start();
	rkrk_assess_emit_setting_carry_script();
	$injectHead .= ob_get_clean();
}
if ($injectHead !== "" && stripos($html, "</head>") !== false) {
	$html = preg_replace("/<\/head>/i", $injectHead . "\n</head>", $html, 1);
}

header("Content-Type: text/html; charset=UTF-8");
echo $html;
