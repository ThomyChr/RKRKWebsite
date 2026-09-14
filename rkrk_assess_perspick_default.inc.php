<?php
/**
 * Checklist-Aufruf: PersPick standardmässig «on» (Namensliste aus Pilotenliste).
 * Explizit abschaltbar mit PersPick=off.
 * Nach rkrk_assess_common.php einbinden (rkrk_assess.php / rkrk_assess_live.php).
 */
$__rkrkPpMode = isset($_GET["mode"]) ? trim((string) $_GET["mode"]) : "";
if (
	$__rkrkPpMode !== "paperform"
	&& !isset($_GET["PersPick"])
	&& !isset($_POST["PersPick"])
) {
	$_GET["PersPick"] = "on";
}
unset($__rkrkPpMode);

if (!function_exists("rkrk_assess_checklist_href_with_perspick")) {
	/** Hängt PersPick=on an Formular-URLs, sofern nicht schon gesetzt / nicht off. */
	function rkrk_assess_checklist_href_with_perspick(string $href): string {
		if (preg_match('/[?&]PersPick(?:=|&|$)/i', $href) === 1) {
			return $href;
		}
		$pp = function_exists("rkrk_assess_carry_perspick_value")
			? rkrk_assess_carry_perspick_value()
			: "on";
		if ($pp === "off") {
			return $href;
		}
		$hash = "";
		$u = $href;
		$p = strpos($u, "#");
		if ($p !== false) {
			$hash = substr($u, $p);
			$u = substr($u, 0, $p);
		}
		$sep = (strpos($u, "?") !== false) ? "&" : "?";
		return $u . $sep . "PersPick=on" . $hash;
	}
}
