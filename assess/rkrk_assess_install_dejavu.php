<?php
/**
 * Einmalig DejaVu-Fonts für TCPDF installieren (Portal ohne vendor/tcpdf/fonts).
 * Aufruf: …/public/rkrk_assess_install_dejavu.php?key=rkrs-local-regen
 */
declare(strict_types=1);

$key = (string) ($_GET["key"] ?? "");
$expect = getenv("RKRK_REGEN_KEY") ?: "rkrs-local-regen";
if (!hash_equals($expect, $key)) {
	http_response_code(403);
	header("Content-Type: text/plain; charset=utf-8");
	echo "forbidden\n";
	exit;
}

header("Content-Type: text/plain; charset=utf-8");

$need = array(
	"dejavusans.php", "dejavusans.z", "dejavusans.ctg.z",
	"dejavusansb.php", "dejavusansb.z", "dejavusansb.ctg.z",
	"dejavusansi.php", "dejavusansi.z", "dejavusansi.ctg.z",
	"dejavusansbi.php", "dejavusansbi.z", "dejavusansbi.ctg.z",
);

$here = str_replace("\\", "/", __DIR__);
$docRoot = isset($_SERVER["DOCUMENT_ROOT"]) ? rtrim(str_replace("\\", "/", (string) $_SERVER["DOCUMENT_ROOT"]), "/") : "";
$targets = array(
	$here . "/vendor/tcpdf/fonts",
	$here . "/../../data/assess/tcpdf_fonts",
);
if ($docRoot !== "") {
	$targets[] = $docRoot . "/lib/tcpdf/fonts";
	$targets[] = $docRoot . "/includes/tcpdf/fonts";
}

$mirrors = array(
	"https://tchr-frog.synology.me/rkropen/rkrs/assess/public/vendor/tcpdf/fonts",
);
$localSrc = $here . "/vendor/tcpdf/fonts";

function rkrk_font_fetch(string $url): string {
	if (function_exists("curl_init")) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_SSL_VERIFYPEER => true,
		));
		$bin = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($code >= 200 && $code < 300 && is_string($bin) && strlen($bin) > 1000) {
			return $bin;
		}
	}
	$bin = @file_get_contents($url);
	return (is_string($bin) && strlen($bin) > 1000) ? $bin : "";
}

$cache = array();
foreach ($need as $fn) {
	$local = $localSrc . "/" . $fn;
	if (is_readable($local) && filesize($local) > 1000) {
		$cache[$fn] = (string) file_get_contents($local);
		echo "local {$fn} " . strlen($cache[$fn]) . "\n";
		continue;
	}
	foreach ($mirrors as $base) {
		$bin = rkrk_font_fetch(rtrim($base, "/") . "/" . $fn);
		if ($bin !== "") {
			$cache[$fn] = $bin;
			echo "fetch {$fn} " . strlen($bin) . "\n";
			break;
		}
	}
	if (!isset($cache[$fn])) {
		echo "MISSING {$fn}\n";
	}
}

if (count($cache) < 3) {
	echo "abort: not enough font files\n";
	exit(1);
}

foreach ($targets as $dir) {
	$dir = rtrim($dir, "/");
	if ($dir === "") {
		continue;
	}
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	if (!is_dir($dir) || !is_writable($dir)) {
		echo "skip not writable {$dir}\n";
		continue;
	}
	$n = 0;
	foreach ($cache as $fn => $bin) {
		$dst = $dir . "/" . $fn;
		if (@file_put_contents($dst, $bin) !== false) {
			$n++;
		}
	}
	echo "wrote {$n}/" . count($cache) . " -> {$dir}\n";
}

echo "done\n";
echo "hint: regenerate PDF afterwards (rkrk_regen_reports.php?key=…&stem=…)\n";
