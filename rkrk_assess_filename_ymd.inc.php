<?php
/**
 * Dateinamen: Abschlussdatum YYYYMMDD anhängen, falls noch nicht im Stamm.
 */

if (!function_exists("rkrk_assess_filename_ymd_token")) {
	function rkrk_assess_filename_ymd_token(string $stampOrDate = ""): string {
		$stampOrDate = trim($stampOrDate);
		if ($stampOrDate !== "" && preg_match('/(\d{8})/', $stampOrDate, $m)) {
			return $m[1];
		}
		if ($stampOrDate !== "") {
			$t = strtotime(str_replace("T", " ", $stampOrDate));
			if ($t !== false) {
				return date("Ymd", $t);
			}
		}
		return date("Ymd");
	}
}

if (!function_exists("rkrk_assess_stem_contains_ymd")) {
	function rkrk_assess_stem_contains_ymd(string $stem): bool {
		return (bool) preg_match('/(?:^|_)(\d{8})(?:_|$)/', $stem);
	}
}

if (!function_exists("rkrk_assess_stem_ensure_ymd")) {
	function rkrk_assess_stem_ensure_ymd(string $stem, string $stampOrDate = ""): string {
		$stem = trim($stem, "_");
		if ($stem === "") {
			$stem = "result";
		}
		if (rkrk_assess_stem_contains_ymd($stem)) {
			return $stem;
		}
		return $stem . "_" . rkrk_assess_filename_ymd_token($stampOrDate);
	}
}
