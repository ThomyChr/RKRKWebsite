<?php
/**
 * Archiv-Kopien unter data/assess/Archiv/ (Fragen-CSV, AssessmentListe.csv, Schulungs-HTML, …).
 * Zeitstempel YYYYMMDDhhmm, Minuten auf 10er-Raster; Aufbewahrung 30 Tage.
 */
if (!function_exists("rkrk_assess_fragenkatalog_archiv_retention_seconds")) {
	function rkrk_assess_fragenkatalog_archiv_retention_seconds(): int {
		return 30 * 24 * 60 * 60;
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_normalize_fs_path")) {
	function rkrk_assess_fragenkatalog_normalize_fs_path($p): string {
		$p = str_replace("\\", "/", (string) $p);
		return rtrim($p, "/");
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_archiv_timestamp")) {
	/** YYYYMMDDhhmm, Minuten auf 10 abgerundet (00,10,…,50), lokale Serverzeit. */
	function rkrk_assess_fragenkatalog_archiv_timestamp(): string {
		$ts = time();
		$m10 = (int) (floor((int) date("i", $ts) / 10) * 10);
		return date("YmdH", $ts) . sprintf("%02d", $m10);
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_cleanup_archiv")) {
	/**
	 * @param string $archivDir Absoluter Pfad zu …/assess/Archiv
	 */
	function rkrk_assess_fragenkatalog_cleanup_archiv($archivDir): void {
		$archivDir = rkrk_assess_fragenkatalog_normalize_fs_path($archivDir);
		if ($archivDir === "" || !is_dir($archivDir)) {
			return;
		}
		$cutoff = time() - rkrk_assess_fragenkatalog_archiv_retention_seconds();
		$dh = @opendir($archivDir);
		if ($dh === false) {
			return;
		}
		while (($ent = readdir($dh)) !== false) {
			if ($ent === "." || $ent === "..") {
				continue;
			}
			$full = $archivDir . "/" . $ent;
			if (!is_file($full)) {
				continue;
			}
			$mt = @filemtime($full);
			if ($mt !== false && $mt < $cutoff) {
				@unlink($full);
			}
		}
		closedir($dh);
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_is_under_assess_root")) {
	function rkrk_assess_fragenkatalog_is_under_assess_root($fileAbs, $quizLocAbs): bool {
		$root = rkrk_assess_fragenkatalog_normalize_fs_path($quizLocAbs);
		$rpRoot = realpath($root);
		if ($rpRoot !== false) {
			$root = rkrk_assess_fragenkatalog_normalize_fs_path($rpRoot);
		}
		$rpFile = realpath($fileAbs);
		if ($rpFile === false || !is_file($rpFile)) {
			return false;
		}
		$f = rkrk_assess_fragenkatalog_normalize_fs_path($rpFile);
		if ($f === $root) {
			return true;
		}
		$prefix = $root . "/";
		return strlen($f) > strlen($prefix) && strncmp($f, $prefix, strlen($prefix)) === 0;
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_archive_prepare_dir")) {
	function rkrk_assess_fragenkatalog_archive_prepare_dir(string $quizLocAbs): string {
		$root = rkrk_assess_fragenkatalog_normalize_fs_path($quizLocAbs);
		if ($root === "") {
			return "";
		}
		$archDir = $root . "/Archiv";
		@mkdir($archDir, 0775, true);
		rkrk_assess_fragenkatalog_cleanup_archiv($archDir);
		return $archDir;
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_purge_autosave_archives")) {
	/** Entfernt Autosave-Archivdateien ({stem}_*_autosave.{ext}) nach 10-Minuten-Archivierung. */
	function rkrk_assess_fragenkatalog_purge_autosave_archives(string $archDir, string $stem, string $ext): void {
		$archDir = rkrk_assess_fragenkatalog_normalize_fs_path($archDir);
		$stem = trim($stem);
		$ext = strtolower(trim($ext));
		if ($archDir === "" || $stem === "" || $ext === "") {
			return;
		}
		$re = '/^' . preg_quote($stem, '/') . '_\d{12}_autosave\.' . preg_quote($ext, '/') . '$/i';
		$dh = @opendir($archDir);
		if ($dh === false) {
			return;
		}
		while (($ent = readdir($dh)) !== false) {
			if ($ent === "." || $ent === "..") {
				continue;
			}
			if (preg_match($re, $ent) === 1) {
				@unlink($archDir . "/" . $ent);
			}
		}
		closedir($dh);
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_archive_copy")) {
	/**
	 * Archiv-Kopie unter data/assess/Archiv/.
	 *
	 * @param string $variant standard = 10-Minuten-Raster (manuell/vor Überschreiben), autosave = pro Minute mit _autosave
	 */
	function rkrk_assess_fragenkatalog_archive_copy($fileAbsolutePath, $quizLocAbs, string $variant = "standard"): void {
		$fileAbsolutePath = trim((string) $fileAbsolutePath);
		$quizLocAbs = trim((string) $quizLocAbs);
		$variant = strtolower(trim($variant));
		if ($fileAbsolutePath === "" || $quizLocAbs === "" || !is_file($fileAbsolutePath)) {
			return;
		}
		if (!rkrk_assess_fragenkatalog_is_under_assess_root($fileAbsolutePath, $quizLocAbs)) {
			return;
		}
		$archDir = rkrk_assess_fragenkatalog_archive_prepare_dir($quizLocAbs);
		if ($archDir === "") {
			return;
		}
		$bn = basename(str_replace("\\", "/", $fileAbsolutePath));
		if (!preg_match('/^(.+)\.(csv|html|htm)$/i', $bn, $m)) {
			return;
		}
		$stem = (string) $m[1];
		$ext = strtolower((string) $m[2]);
		if ($variant === "autosave") {
			$destName = $stem . "_" . date("YmdHi") . "_autosave." . $ext;
			$destPath = $archDir . "/" . $destName;
			if (!is_file($destPath)) {
				@copy($fileAbsolutePath, $destPath);
			}
			return;
		}
		$destName = $stem . "_" . rkrk_assess_fragenkatalog_archiv_timestamp() . "." . $ext;
		$destPath = $archDir . "/" . $destName;
		// 10-Minuten-Archiv vorhanden oder neu erstellt → Autosave-Versionen derselben Datei entfernen
		if (is_file($destPath)) {
			rkrk_assess_fragenkatalog_purge_autosave_archives($archDir, $stem, $ext);
			return;
		}
		if (@copy($fileAbsolutePath, $destPath)) {
			rkrk_assess_fragenkatalog_purge_autosave_archives($archDir, $stem, $ext);
		}
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
	/** Vor dem Überschreiben: Archiv der bestehenden Datei (10-Minuten-Raster). */
	function rkrk_assess_fragenkatalog_before_overwrite($fileAbsolutePath, $quizLocAbs): void {
		rkrk_assess_fragenkatalog_archive_copy($fileAbsolutePath, $quizLocAbs, "standard");
	}
}

if (!function_exists("rkrk_assess_fragenkatalog_archive_autosave")) {
	/** Nach Autosave: Archiv der gespeicherten Datei (Minuten-Raster, Suffix _autosave). */
	function rkrk_assess_fragenkatalog_archive_autosave($fileAbsolutePath, $quizLocAbs): void {
		rkrk_assess_fragenkatalog_archive_copy($fileAbsolutePath, $quizLocAbs, "autosave");
	}
}
