<?php
/**
 * Rollen und Zugriffe — Hilfe.
 * Offen mit ADMIN oder INSTR, oder für wen die Policy-Seite selbst offen ist
 * (Kerngruppe / Site-Admin auf Portal und RKRS Live). Ein ausgeschaltetes
 * Extra-Gate (via gate_off auf RKRK/NAS) zählt hier nicht als Freigabe.
 */
$folderoffset = "../";
include_once __DIR__ . "/rkrk_assess_common.php";

$isAdmin = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool) $onAdmin);
$isInstr = isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($onInstr) && (bool) $onInstr);
$viaPolicy = false;
if (function_exists("rkrk_assess_access_policy_evaluate")) {
	$eval = rkrk_assess_access_policy_evaluate("admin_access");
	$via = (string) ($eval["via"] ?? "");
	$viaPolicy = !empty($eval["ok"]) && $via !== "" && $via !== "gate_off";
}

if (!$isAdmin && !$isInstr && !$viaPolicy) {
	http_response_code(403);
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Zugriff verweigert</title></head><body>";
	echo "<p>Die Hilfe zu Rollen und Zugriffen ist mit <code>ADMIN</code> oder <code>INSTR</code> in der URL verfügbar, auf Portal und RKRS Live ausserdem für die Kerngruppe.</p>";
	echo "</body></html>";
	exit;
}

$htmlPath = __DIR__ . "/rkrk_assess_zugriff_hilfe.html";
if (!is_readable($htmlPath)) {
	http_response_code(500);
	echo "Hilfedatei fehlt.";
	exit;
}

$html = (string) file_get_contents($htmlPath);

$h = static function ($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
};

$hostLine = "Die Tabelle zeigt die gespeicherte Policy.";
if (function_exists("rkrk_assess_access_policy_runtime_status")) {
	$status = rkrk_assess_access_policy_runtime_status();
	$hostLine = "Dieser Aufruf: " . (string) ($status["host_label"] ?? "")
		. " · " . (string) ($status["host"] ?? "");
	if (!empty($status["policy_file"])) {
		$hostLine .= " · Datei " . (string) $status["policy_file"];
	}
	if (!empty($status["policy_exists"])) {
		$hostLine .= " · gespeichert";
	} else {
		$hostLine .= " · noch die Standardregeln (Datei fehlt)";
	}
}
$html = str_replace("<!--LIVE_HOST-->", $h($hostLine), $html);

$rows = "";
if (function_exists("rkrk_assess_access_policy_load") && function_exists("rkrk_assess_access_policy_rule_summary")) {
	$policy = rkrk_assess_access_policy_load();
	$hostIds = function_exists("rkrk_assess_access_policy_host_ids")
		? rkrk_assess_access_policy_host_ids()
		: array("portal", "rkrs", "rkrk", "other");
	$pages = (isset($policy["pages"]) && is_array($policy["pages"])) ? $policy["pages"] : array();
	foreach ($pages as $page) {
		if (!is_array($page)) {
			continue;
		}
		$scripts = (isset($page["scripts"]) && is_array($page["scripts"])) ? $page["scripts"] : array();
		$rows .= "<tr><td><strong>" . $h($page["label"] ?? "") . "</strong>";
		if ($scripts) {
			$rows .= "<div class=\"muted\">" . $h(implode(", ", $scripts)) . "</div>";
		}
		$note = trim((string) ($page["note"] ?? ""));
		if ($note !== "") {
			$rows .= "<div class=\"muted\">" . $h($note) . "</div>";
		}
		$rows .= "</td>";
		$hosts = (isset($page["hosts"]) && is_array($page["hosts"])) ? $page["hosts"] : array();
		foreach ($hostIds as $hid) {
			$rule = (isset($hosts[$hid]) && is_array($hosts[$hid])) ? $hosts[$hid] : array();
			$rows .= "<td>" . $h(rkrk_assess_access_policy_rule_summary($rule)) . "</td>";
		}
		$rows .= "</tr>\n";
	}
}
if ($rows === "") {
	$rows = "<tr><td colspan=\"5\">Policy konnte nicht gelesen werden.</td></tr>";
}
$html = str_replace("<!--LIVE_POLICY-->", $rows, $html);

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
