<?php
/**
 * Liefert einen gespeicherten Abschluss-Report (PDF/HTML) aus results/reports/.
 * Direkter Web-Zugriff auf data/.../reports/ ist oft 403.
 * Sucht assess- und liveassess-Reports (optional ?k=live|assess).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once __DIR__ . "/rkrk_assess_common.php";

$stem = isset($_GET["f"]) ? trim((string) $_GET["f"]) : "";
$stem = str_replace(array("\\", "/"), "", $stem);
$stem = preg_replace('/\.(json|pdf|html)$/i', "", $stem);
$stem = preg_replace('/[^A-Za-z0-9._-]+/', "_", (string) $stem);
$stem = trim((string) $stem, "._-");
if ($stem === "") {
	http_response_code(400);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Missing report id.";
	exit;
}

$type = strtolower(trim((string) ($_GET["t"] ?? "")));
$kindParam = strtolower(trim((string) ($_GET["k"] ?? "")));

$dirs = array();
$addDir = static function (string $d) use (&$dirs): void {
	$d = rtrim(str_replace("\\", "/", $d), "/") . "/";
	if ($d === "/" || $d === "./") {
		return;
	}
	$real = @realpath($d);
	if ($real !== false && is_dir($real)) {
		$d = rtrim(str_replace("\\", "/", $real), "/") . "/";
	} elseif (!is_dir($d)) {
		return;
	}
	if (!in_array($d, $dirs, true)) {
		$dirs[] = $d;
	}
};

if (function_exists("rkrk_assess_reports_dir")) {
	$kindOrder = array();
	if ($kindParam === "live" || $kindParam === "assess") {
		$kindOrder[] = $kindParam;
	}
	$kindOrder[] = "assess";
	$kindOrder[] = "live";
	foreach (array_values(array_unique($kindOrder)) as $kindTry) {
		$prevKind = $GLOBALS["rkrk_assess_reports_kind"] ?? null;
		$GLOBALS["rkrk_assess_reports_kind"] = $kindTry;
		$addDir((string) rkrk_assess_reports_dir());
		if ($prevKind === null) {
			unset($GLOBALS["rkrk_assess_reports_kind"]);
		} else {
			$GLOBALS["rkrk_assess_reports_kind"] = $prevKind;
		}
	}
}

// Absolute Fallbacks (relativer reports_dir kann auf manchen Hosts leer bleiben)
$here = str_replace("\\", "/", __DIR__);
$addDir($here . "/../../data/assess/results/reports/");
$addDir($here . "/../../data/liveassess/results/reports/");
if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
	$addDir(rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/") . "/reports/");
}
if (isset($GLOBALS["rkrkLiveassessData"]) && trim((string) $GLOBALS["rkrkLiveassessData"]) !== "") {
	$addDir(rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkLiveassessData"]), "/") . "/results/reports/");
}

if ($dirs === array()) {
	http_response_code(500);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Reports directory unavailable.";
	exit;
}

$candidates = array();
foreach ($dirs as $dir) {
	if ($type === "pdf" || $type === "html") {
		$candidates[] = array($dir, $dir . $stem . "." . $type);
	} else {
		$candidates[] = array($dir, $dir . $stem . ".pdf");
		$candidates[] = array($dir, $dir . $stem . ".html");
	}
}

$path = "";
foreach ($candidates as $pair) {
	$dir = $pair[0];
	$cand = $pair[1];
	$realDir = realpath($dir);
	$realFile = realpath($cand);
	if ($realDir === false || $realFile === false) {
		continue;
	}
	$prefix = rtrim(str_replace("\\", "/", $realDir), "/") . "/";
	$fileNorm = str_replace("\\", "/", $realFile);
	if (strpos($fileNorm, $prefix) !== 0) {
		continue;
	}
	if (is_file($realFile)) {
		$path = $realFile;
		break;
	}
}

if ($path === "") {
	http_response_code(404);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Report not found.";
	exit;
}

$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
$downloadName = $stem . "." . ($ext !== "" ? $ext : "bin");
if ($ext === "pdf") {
	header("Content-Type: application/pdf");
} elseif ($ext === "html" || $ext === "htm") {
	header("Content-Type: text/html; charset=UTF-8");
} else {
	header("Content-Type: application/octet-stream");
}
header("Content-Disposition: inline; filename=\"" . str_replace('"', "", $downloadName) . "\"");
header("Content-Length: " . (string) filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
readfile($path);
exit;
