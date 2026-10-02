<?php
if (($_GET["token"] ?? "") !== "rkrk-where-20260926") {
	http_response_code(404);
	exit;
}
header("Content-Type: text/plain; charset=utf-8");
echo "open_basedir=" . ini_get("open_basedir") . "\n";
echo "this=" . __DIR__ . "\n";
$web = "/volume1/web";
$names = array();
if (is_dir($web)) {
	foreach (scandir($web) as $name) {
		if ($name === "." || $name === "..") {
			continue;
		}
		$names[] = $name;
	}
}
echo "web=" . implode(", ", $names) . "\n";
$confs = array(
	"/etc/nginx/app.d",
	"/usr/local/etc/nginx/sites-enabled",
	"/var/tmp/nginx",
	"/etc/nginx",
	"/volume1/@appstore/WebStation",
);
foreach ($confs as $c) {
	echo "CONFDIR $c exists=" . (is_dir($c) ? "yes" : "no") . "\n";
}
$needles = array("rkrsplusassessment", "portal.rehkitzrettung");
$confFiles = array(
	"/etc/nginx/nginx.conf",
	"/etc/nginx/nginx.conf.default",
);
if (is_dir("/etc/nginx")) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("/etc/nginx", FilesystemIterator::SKIP_DOTS));
	$n = 0;
	foreach ($it as $f) {
		if (!$f->isFile()) {
			continue;
		}
		$n++;
		if ($n > 400) {
			break;
		}
		$path = $f->getPathname();
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if (!in_array($ext, array("conf", "mustache", ""), true) && strpos($path, ".conf") === false) {
			continue;
		}
		$txt = @file_get_contents($path);
		if (!is_string($txt)) {
			continue;
		}
		foreach ($needles as $needle) {
			if (stripos($txt, $needle) !== false) {
				echo "NGINX $path\n";
				break;
			}
		}
	}
	echo "nginx_files_seen=$n\n";
}
$roots = array(
	"/volume1/web/portal.rehkitzrettung.ch/rkrsplusassessment",
	"/volume1/web/rkrsplusassessment",
	"/volume1/web/rkropen/rkrsplusassessment",
	"/volume1/web/portal.rehkitzrettung.ch",
);
foreach ($names as $name) {
	$roots[] = $web . "/" . $name . "/rkrsplusassessment";
}
$seen = array();
foreach ($roots as $root) {
	if (isset($seen[$root])) {
		continue;
	}
	$seen[$root] = true;
	if (!is_dir($root)) {
		continue;
	}
	$common = $root . "/assess/public/rkrk_assess_common.php";
	$sess = $root . "/assess/public/rkrk_assess_sessions.php";
	echo "HIT $root\n";
	echo "  writable=" . (is_writable($root) ? "yes" : "no") . "\n";
	if (is_file($common)) {
		$src = (string) file_get_contents($common);
		echo "  common mtime=" . date("c", (int) filemtime($common));
		echo " unset=" . (strpos($src, "unset(\$_SESSION)") !== false ? "yes" : "no");
		echo " bytes=" . strlen($src) . "\n";
	} else {
		echo "  common=missing\n";
	}
	if (is_file($sess)) {
		$ss = (string) file_get_contents($sess);
		echo "  sessions diag=" . (strpos($ss, "rkrkdiag") !== false ? "yes" : "no") . "\n";
	}
}
