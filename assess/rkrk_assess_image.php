<?php
/**
 * Liefert Assess-Bilder aus dem Dateisystem ($QuizIconLoc), damit Browser-URLs
 * auf dem Portal-Host nicht von einem abweichenden Static-Pfad abhängen.
 *
 * Aufruf: rkrk_assess_image.php?f=dateiname.png&d=optionaler/unterordner
 */
$folderoffset = "../";
include_once($folderoffset . "scripts/rkrk_inputforms.php");
include_once($folderoffset . "scripts/rkrk_csvfiles.php");
include_once(__DIR__ . "/rkrk_assess_common.php");

$f = isset($_GET["f"]) ? (string) $_GET["f"] : "";
$d = isset($_GET["d"]) ? (string) $_GET["d"] : "";
if ($d === "") {
	foreach ($_GET as $gk => $gv) {
		if (preg_match('/(?:^|;)d$/i', (string) $gk) === 1) {
			$d = (string) $gv;
			break;
		}
	}
}

$f = str_replace("\\", "/", $f);
$d = str_replace("\\", "/", $d);
$f = ltrim($f, "/");
$d = trim($d, "/");
for ($__dec = 0; $__dec < 4; $__dec++) {
	$nf = rawurldecode($f);
	$nd = rawurldecode($d);
	if ($nf === $f && $nd === $d) {
		break;
	}
	$f = $nf;
	$d = $nd;
}

// Keine Path-Traversal, nur Dateiname (+ erlaubte Zeichen).
if ($f === "" || strpos($f, "..") !== false || preg_match('#^[\\p{L}\\p{N}_\\-\\. ]+$#u', $f) !== 1) {
	http_response_code(400);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Bad file";
	exit;
}
if ($d !== "" && (strpos($d, "..") !== false || preg_match('#^[\\p{L}\\p{N}_\\-\\./ ]+$#u', $d) !== 1)) {
	http_response_code(400);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Bad dir";
	exit;
}

$root = isset($_GET["root"]) ? (string) $_GET["root"] : "quiz";
$diagramDirs = array();
if ($root === "diagram") {
	if (function_exists("rkrk_assess_template_diagram_fs_dirs")) {
		$diagramDirs = rkrk_assess_template_diagram_fs_dirs();
	} elseif (function_exists("rkrk_assess_template_diagram_fs_dir")) {
		$one = rkrk_assess_template_diagram_fs_dir();
		if ($one !== "") {
			$diagramDirs = array($one);
		}
	}
} else {
	$base = isset($QuizIconLoc) ? rtrim(str_replace("\\", "/", (string) $QuizIconLoc), "/") . "/" : "";
	if ($base !== "" && is_dir($base)) {
		$diagramDirs = array($base);
	}
}
if ($diagramDirs === array()) {
	http_response_code(500);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Image root missing";
	exit;
}

$realFile = false;
$realBase = false;
foreach ($diagramDirs as $base) {
	$path = rtrim(str_replace("\\", "/", $base), "/") . "/";
	if ($d !== "") {
		$path .= $d . "/";
	}
	$path .= $f;
	$candidateBase = realpath($base);
	$candidateFile = realpath($path);
	if ($candidateBase === false || $candidateFile === false || strpos($candidateFile, $candidateBase) !== 0 || !is_file($candidateFile)) {
		continue;
	}
	$realBase = $candidateBase;
	$realFile = $candidateFile;
	break;
}
if ($realFile === false || $realBase === false) {
	http_response_code(404);
	header("Content-Type: text/plain; charset=UTF-8");
	echo "Not found";
	exit;
}

$ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
$types = array(
	"png" => "image/png",
	"jpg" => "image/jpeg",
	"jpeg" => "image/jpeg",
	"gif" => "image/gif",
	"webp" => "image/webp",
	"svg" => "image/svg+xml",
	"ico" => "image/x-icon",
);
$ctype = isset($types[$ext]) ? $types[$ext] : "application/octet-stream";
$mtime = @filemtime($realFile);
$size = @filesize($realFile);

header("Content-Type: " . $ctype);
header("Cache-Control: public, max-age=86400");
if ($mtime) {
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
}
if ($size !== false) {
	header("Content-Length: " . (string) $size);
}
readfile($realFile);
exit;
