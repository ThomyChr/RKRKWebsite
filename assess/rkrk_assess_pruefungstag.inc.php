<?php
/**
 * Prüfungstag: Plätze nach Tagesablauf 0.3.
 * Pro Stationsgruppe 9 Plätze, Material/Flug/Theorie rotieren parallel.
 * Kapazität = je Halbtag Arbeitsplätze × 3 × parallele Gruppen.
 */

if (!function_exists("rkrk_pt_halfday_defs")) {
	/** @return list<array{id:string,label:string,letter:string,start:string}> */
	function rkrk_pt_halfday_defs(): array {
		return array(
			array("id" => "vormittag", "label" => "Vormittag", "letter" => "A", "start" => "08:30"),
			array("id" => "nachmittag", "label" => "Nachmittag", "letter" => "B", "start" => "13:00"),
			array("id" => "abend", "label" => "Abend", "letter" => "C", "start" => "17:30"),
		);
	}
}

if (!function_exists("rkrk_pt_page_href")) {
	function rkrk_pt_page_href(array $query = array()): string {
		$path = "rkrk_assess_pruefungstag.php";
		$qs = http_build_query($query);
		if ($qs !== "") {
			$path .= "?" . $qs;
		}
		if (function_exists("rkrk_assess_append_setting_href")) {
			return rkrk_assess_append_setting_href($path);
		}
		return $path;
	}
}

if (!function_exists("rkrk_pt_h")) {
	function rkrk_pt_h(string $s): string {
		return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists("rkrk_pt_date_label")) {
	function rkrk_pt_date_label(DateTime $when): string {
		$weekdays = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
		$months = array(
			1 => "Januar",
			2 => "Februar",
			3 => "März",
			4 => "April",
			5 => "Mai",
			6 => "Juni",
			7 => "Juli",
			8 => "August",
			9 => "September",
			10 => "Oktober",
			11 => "November",
			12 => "Dezember",
		);
		$w = (int) $when->format("w");
		$n = (int) $when->format("n");
		$dayName = $weekdays[$w] ?? "";
		$monthName = $months[$n] ?? $when->format("m");
		return $dayName . ", " . (int) $when->format("j") . ". " . $monthName . " " . $when->format("Y");
	}
}

if (!function_exists("rkrk_pt_exam_datetime")) {
	/** Datum aus dem Text, sonst aus der Bezeichnung, z. B. «29. November 2026» oder «EP 2611». */
	function rkrk_pt_exam_datetime(array $day): ?DateTime {
		$months = array(
			"januar" => 1, "februar" => 2, "märz" => 3, "maerz" => 3, "april" => 4,
			"mai" => 5, "juni" => 6, "juli" => 7, "august" => 8, "september" => 9,
			"oktober" => 10, "november" => 11, "dezember" => 12,
		);
		$chapters = isset($day["chapters"]) && is_array($day["chapters"]) ? $day["chapters"] : array();
		$blobs = array();
		foreach ($chapters as $chapter) {
			if (!is_array($chapter)) {
				continue;
			}
			$title = trim((string) ($chapter["title"] ?? ""));
			$body = trim((string) ($chapter["body"] ?? ""));
			if (stripos($title, "datum") !== false) {
				array_unshift($blobs, $title . "\n" . $body);
			} else {
				$blobs[] = $title . "\n" . $body;
			}
		}
		foreach ($blobs as $blob) {
			if (preg_match("/(\\d{1,2})\\.\\s*([\\p{L}]+)\\s+(20\\d{2})/u", $blob, $written) !== 1) {
				continue;
			}
			$monthName = str_replace(array("ä", "ö", "ü"), array("ae", "oe", "ue"), mb_strtolower($written[2], "UTF-8"));
			if ($monthName === "marz") {
				$monthName = "maerz";
			}
			if (!isset($months[$monthName]) || !checkdate($months[$monthName], (int) $written[1], (int) $written[3])) {
				continue;
			}
			try {
				$zone = new DateTimeZone("Europe/Zurich");
			} catch (Throwable $e) {
				$zone = new DateTimeZone("UTC");
			}
			$dt = DateTime::createFromFormat("!Y-n-j", $written[3] . "-" . $months[$monthName] . "-" . (int) $written[1], $zone);
			return $dt instanceof DateTime ? $dt : null;
		}
		$name = trim((string) ($day["name"] ?? ""));
		if (preg_match("/(?:^|\\D)(\\d{2})(\\d{2})(?:\\D|$)/", $name, $m) !== 1) {
			return null;
		}
		$dayNum = (int) $m[1];
		$month = (int) $m[2];
		if ($dayNum < 1 || $dayNum > 31 || $month < 1 || $month > 12) {
			return null;
		}
		try {
			$zone = new DateTimeZone("Europe/Zurich");
		} catch (Throwable $e) {
			$zone = new DateTimeZone("UTC");
		}
		$now = new DateTime("now", $zone);
		$baseYear = (int) $now->format("Y");
		$best = null;
		$bestAbs = null;
		foreach (array($baseYear - 1, $baseYear, $baseYear + 1) as $year) {
			if (!checkdate($month, $dayNum, $year)) {
				continue;
			}
			$dt = DateTime::createFromFormat("!Y-n-j", $year . "-" . $month . "-" . $dayNum, $zone);
			if (!$dt instanceof DateTime) {
				continue;
			}
			$abs = abs($dt->getTimestamp() - $now->getTimestamp());
			if ($bestAbs === null || $abs < $bestAbs) {
				$best = $dt;
				$bestAbs = $abs;
			}
		}
		return $best;
	}
}

if (!function_exists("rkrk_pt_exam_label")) {
	function rkrk_pt_exam_label(array $day): string {
		$when = rkrk_pt_exam_datetime($day);
		return $when instanceof DateTime ? rkrk_pt_date_label($when) : "";
	}
}

if (!function_exists("rkrk_pt_revision_label")) {
	/** Änderungszeit der JSON-Datei dieses Prüfungstags, Europe/Zurich. */
	function rkrk_pt_revision_label(array $day): string {
		$dir = rkrk_pt_day_dir((string) ($day["id"] ?? ""));
		$file = $dir !== "" ? $dir . DIRECTORY_SEPARATOR . "tag.json" : "";
		$mtime = ($file !== "" && is_file($file)) ? filemtime($file) : false;
		if ($mtime === false) {
			return "";
		}
		try {
			$zone = new DateTimeZone("Europe/Zurich");
		} catch (Throwable $e) {
			$zone = new DateTimeZone("UTC");
		}
		$dt = (new DateTime("@" . (string) $mtime))->setTimezone($zone);
		return $dt->format("j.n.Y H:i");
	}
}

if (!function_exists("rkrk_pt_config_path")) {
	function rkrk_pt_config_path(): string {
		$base = function_exists("rkrk_tr_config_path") ? rkrk_tr_config_path() : "";
		if ($base === "") {
			return "";
		}
		return dirname($base) . DIRECTORY_SEPARATOR . "pruefungstag.json";
	}
}

if (!function_exists("rkrk_pt_root")) {
	function rkrk_pt_root(): string {
		$legacy = rkrk_pt_config_path();
		if ($legacy === "") {
			return "";
		}
		return dirname($legacy) . DIRECTORY_SEPARATOR . "pruefungstag";
	}
}

if (!function_exists("rkrk_pt_day_id_ok")) {
	function rkrk_pt_day_id_ok(string $id): bool {
		return preg_match("/^[a-z0-9-]{1,64}$/", $id) === 1;
	}
}

if (!function_exists("rkrk_pt_day_dir")) {
	function rkrk_pt_day_dir(string $id): string {
		$id = strtolower(trim($id));
		$root = rkrk_pt_root();
		if ($root === "" || !rkrk_pt_day_id_ok($id)) {
			return "";
		}
		return $root . DIRECTORY_SEPARATOR . $id;
	}
}

if (!function_exists("rkrk_pt_ensure_dir")) {
	function rkrk_pt_ensure_dir(string $dir): bool {
		if ($dir === "") {
			return false;
		}
		if (is_dir($dir)) {
			return true;
		}
		return @mkdir($dir, 0777, true);
	}
}

if (!function_exists("rkrk_pt_slug")) {
	function rkrk_pt_slug(string $name): string {
		$s = function_exists("rkrk_tr_slug") ? rkrk_tr_slug($name) : "tag";
		if ($s === "" || $s === "liste") {
			$s = "tag";
		}
		return substr($s, 0, 48);
	}
}

if (!function_exists("rkrk_pt_normalize_time")) {
	function rkrk_pt_normalize_time(string $raw, string $fallback): string {
		$raw = trim($raw);
		if (preg_match("/^(\d{1,2}):(\d{2})$/", $raw, $m)) {
			$h = (int) $m[1];
			$min = (int) $m[2];
			if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
				return sprintf("%02d:%02d", $h, $min);
			}
		}
		return $fallback;
	}
}

if (!function_exists("rkrk_pt_minutes")) {
	function rkrk_pt_minutes(string $hhmm): int {
		if (!preg_match("/^(\d{2}):(\d{2})$/", $hhmm, $m)) {
			return 8 * 60 + 30;
		}
		return ((int) $m[1]) * 60 + (int) $m[2];
	}
}

if (!function_exists("rkrk_pt_hhmm")) {
	function rkrk_pt_hhmm(int $mins): string {
		$day = 24 * 60;
		$mins = $mins % $day;
		if ($mins < 0) {
			$mins += $day;
		}
		return sprintf("%02d:%02d", intdiv($mins, 60), $mins % 60);
	}
}

if (!function_exists("rkrk_pt_range")) {
	function rkrk_pt_range(int $start, int $from, int $to): string {
		return rkrk_pt_hhmm($start + $from) . "–" . rkrk_pt_hhmm($start + $to);
	}
}

if (!function_exists("rkrk_pt_clamp_int")) {
	function rkrk_pt_clamp_int($raw, int $min, int $max, int $fallback): int {
		if ($raw === null || $raw === "") {
			return $fallback;
		}
		$n = (int) $raw;
		if ($n < $min) {
			return $min;
		}
		if ($n > $max) {
			return $max;
		}
		return $n;
	}
}

if (!function_exists("rkrk_pt_timing_defaults")) {
	/** @return array{briefing:int,prep:int,slot:int,close:int} */
	function rkrk_pt_timing_defaults(): array {
		return array("briefing" => 10, "prep" => 20, "slot" => 60, "close" => 20);
	}
}

if (!function_exists("rkrk_pt_timing")) {
	/** @return array{briefing:int,prep:int,slot:int,close:int} */
	function rkrk_pt_timing(array $day): array {
		$raw = isset($day["timing"]) && is_array($day["timing"]) ? $day["timing"] : array();
		$out = array();
		foreach (rkrk_pt_timing_defaults() as $key => $fallback) {
			$out[$key] = rkrk_pt_clamp_int($raw[$key] ?? $fallback, 5, 180, $fallback);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_places_of")) {
	function rkrk_pt_places_of(array $day, string $halfId): int {
		$raw = isset($day["places"]) && is_array($day["places"]) ? ($day["places"][$halfId] ?? 3) : 3;
		return rkrk_pt_clamp_int($raw, 1, 3, 3);
	}
}

if (!function_exists("rkrk_pt_groups_of")) {
	function rkrk_pt_groups_of(array $day, string $halfId): int {
		$fallback = (int) ($day["parallel"] ?? 1);
		if ($fallback < 1) {
			$fallback = 1;
		}
		if ($fallback > 6) {
			$fallback = 6;
		}
		if (isset($day["groups"]) && is_array($day["groups"]) && array_key_exists($halfId, $day["groups"])) {
			return rkrk_pt_clamp_int($day["groups"][$halfId], 1, 6, $fallback);
		}
		return $fallback;
	}
}

if (!function_exists("rkrk_pt_empty_day")) {
	/** @return array<string,mixed> */
	function rkrk_pt_empty_day(string $name, array $usedIds = array()): array {
		$id = rkrk_pt_slug($name !== "" ? $name : "Pruefungstag");
		$base = $id;
		$n = 2;
		while (isset($usedIds[$id])) {
			$id = $base . "-" . $n;
			$n++;
		}
		if (trim($name) === "") {
			$name = "Prüfungstag";
		}
		return array(
			"id" => $id,
			"name" => $name,
			"list_id" => "",
			"parallel" => 1,
			"halfdays" => array("vormittag" => true, "nachmittag" => true, "abend" => false),
			"starts" => array("vormittag" => "08:30", "nachmittag" => "13:00", "abend" => "17:30"),
			"places" => array("vormittag" => 3, "nachmittag" => 3, "abend" => 3),
			"groups" => array("vormittag" => 1, "nachmittag" => 1, "abend" => 1),
			"timing" => array("briefing" => 10, "prep" => 20, "slot" => 60, "close" => 20),
			"order" => array(),
			"staff" => array(),
			"share" => "",
			"share_instr" => "",
			"roster_share" => false,
			"roster_pdf" => false,
			"roster_instr_teil_link" => false,
			"roster_instr_instr_link" => false,
			"roster_teil_teil_link" => false,
			"roster_teil_instr_link" => false,
			"roster_instr_pdf" => false,
			"roster_teil_pdf" => false,
			"withdrawn" => array(),
			"log" => array(),
			"marks" => array(),
			"place" => rkrk_pt_place_defaults(),
			"chapters" => array(),
		);
	}
}

if (!function_exists("rkrk_pt_normalize_day")) {
	/** @param array<string,mixed> $raw @return array<string,mixed> */
	function rkrk_pt_normalize_day(array $raw, array $usedIds = array()): array {
		$name = trim((string) ($raw["name"] ?? ""));
		if (function_exists("mb_substr")) {
			$name = mb_substr($name, 0, 80, "UTF-8");
		} else {
			$name = substr($name, 0, 80);
		}
		$day = rkrk_pt_empty_day($name !== "" ? $name : "Prüfungstag", $usedIds);
		$id = trim((string) ($raw["id"] ?? ""));
		if ($id !== "" && preg_match("/^[a-z0-9-]{1,64}$/", $id) && !isset($usedIds[$id])) {
			$day["id"] = $id;
		}
		$day["name"] = $name !== "" ? $name : $day["name"];
		$day["list_id"] = trim((string) ($raw["list_id"] ?? ""));
		$parallel = (int) ($raw["parallel"] ?? 1);
		if ($parallel < 1) {
			$parallel = 1;
		}
		if ($parallel > 6) {
			$parallel = 6;
		}
		$day["parallel"] = $parallel;
		$halfRaw = isset($raw["halfdays"]) && is_array($raw["halfdays"]) ? $raw["halfdays"] : array();
		$startRaw = isset($raw["starts"]) && is_array($raw["starts"]) ? $raw["starts"] : array();
		$placeRaw = isset($raw["places"]) && is_array($raw["places"]) ? $raw["places"] : array();
		$groupRaw = isset($raw["groups"]) && is_array($raw["groups"]) ? $raw["groups"] : array();
		$day["groups"] = array();
		foreach (rkrk_pt_halfday_defs() as $hd) {
			$hid = $hd["id"];
			$flag = $halfRaw[$hid] ?? false;
			$day["halfdays"][$hid] = ($flag === true || $flag === 1 || $flag === "1" || $flag === "on");
			$day["starts"][$hid] = rkrk_pt_normalize_time((string) ($startRaw[$hid] ?? ""), $hd["start"]);
			$day["places"][$hid] = rkrk_pt_places_of(array("places" => $placeRaw), $hid);
			$day["groups"][$hid] = rkrk_pt_groups_of(array(
				"parallel" => $parallel,
				"groups" => $groupRaw,
			), $hid);
		}
		$timeRaw = isset($raw["timing"]) && is_array($raw["timing"]) ? $raw["timing"] : array();
		$day["timing"] = rkrk_pt_timing(array("timing" => $timeRaw));
		$order = array();
		$seenOrder = array();
		$src = isset($raw["order"]) && is_array($raw["order"]) ? $raw["order"] : array();
		foreach ($src as $pid) {
			$pid = trim((string) $pid);
			if ($pid === "") {
				$order[] = "";
				continue;
			}
			if (!isset($seenOrder[$pid])) {
				$order[] = $pid;
				$seenOrder[$pid] = true;
			}
		}
		$day["order"] = $order;
		$day["staff"] = rkrk_pt_normalize_staff($raw["staff"] ?? array());
		$share = strtolower(trim((string) ($raw["share"] ?? "")));
		$day["share"] = (preg_match("/^[a-f0-9]{32}$/", $share) === 1) ? $share : "";
		$shareInstr = strtolower(trim((string) ($raw["share_instr"] ?? "")));
		$day["share_instr"] = (preg_match("/^[a-f0-9]{32}$/", $shareInstr) === 1) ? $shareInstr : "";
		$day["roster_share"] = !empty($raw["roster_share"]);
		$day["roster_pdf"] = !empty($raw["roster_pdf"]);
		$legacyShare = $day["roster_share"];
		$legacyPdf = $day["roster_pdf"];
		$legacyInstrShare = array_key_exists("roster_instr_share", $raw) ? !empty($raw["roster_instr_share"]) : $legacyShare;
		$legacyTeilShare = array_key_exists("roster_teil_share", $raw) ? !empty($raw["roster_teil_share"]) : $legacyShare;
		$day["roster_instr_teil_link"] = array_key_exists("roster_instr_teil_link", $raw) ? !empty($raw["roster_instr_teil_link"]) : $legacyInstrShare;
		$day["roster_instr_instr_link"] = array_key_exists("roster_instr_instr_link", $raw) ? !empty($raw["roster_instr_instr_link"]) : $legacyInstrShare;
		$day["roster_teil_teil_link"] = array_key_exists("roster_teil_teil_link", $raw) ? !empty($raw["roster_teil_teil_link"]) : $legacyTeilShare;
		$day["roster_teil_instr_link"] = array_key_exists("roster_teil_instr_link", $raw) ? !empty($raw["roster_teil_instr_link"]) : $legacyTeilShare;
		$day["roster_instr_pdf"] = array_key_exists("roster_instr_pdf", $raw) ? !empty($raw["roster_instr_pdf"]) : $legacyPdf;
		$day["roster_teil_pdf"] = array_key_exists("roster_teil_pdf", $raw) ? !empty($raw["roster_teil_pdf"]) : $legacyPdf;
		$day["withdrawn"] = rkrk_pt_normalize_withdrawn($raw["withdrawn"] ?? array());
		$day["log"] = rkrk_pt_normalize_log($raw["log"] ?? array());
		$day["marks"] = rkrk_pt_normalize_marks($raw["marks"] ?? array());
		$day["place"] = rkrk_pt_normalize_place($raw["place"] ?? array());
		$day["chapters"] = rkrk_pt_normalize_chapters($raw["chapters"] ?? array());
		return $day;
	}
}

if (!function_exists("rkrk_pt_place_show_defaults")) {
	/** @return array<string,array{teil:bool,instr:bool}> */
	function rkrk_pt_place_show_defaults(): array {
		return array(
			"maps" => array("teil" => true, "instr" => true),
			"swisstopo" => array("teil" => true, "instr" => true),
			"overlay" => array("teil" => false, "instr" => false),
		);
	}
}

if (!function_exists("rkrk_pt_place_show_normalize")) {
	/** @param mixed $raw @return array<string,array{teil:bool,instr:bool}> */
	function rkrk_pt_place_show_normalize($raw): array {
		$out = rkrk_pt_place_show_defaults();
		if (!is_array($raw)) {
			return $out;
		}
		foreach ($out as $key => $flags) {
			if (!isset($raw[$key]) || !is_array($raw[$key])) {
				continue;
			}
			$out[$key]["teil"] = !empty($raw[$key]["teil"]);
			$out[$key]["instr"] = !empty($raw[$key]["instr"]);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_place_link_visible")) {
	function rkrk_pt_place_link_visible(array $place, string $key, string $audience): bool {
		if ($audience === "admin") {
			return true;
		}
		$defaults = rkrk_pt_place_show_defaults();
		$flags = $defaults[$key] ?? array("teil" => false, "instr" => false);
		$show = isset($place["show"][$key]) && is_array($place["show"][$key]) ? $place["show"][$key] : null;
		if (is_array($show)) {
			$flags = $show;
		}
		if ($audience === "teil") {
			return !empty($flags["teil"]);
		}
		if ($audience === "instr") {
			return !empty($flags["instr"]);
		}
		return false;
	}
}

if (!function_exists("rkrk_pt_place_defaults")) {
	/** @return array{ort:string,lat:string,lng:string,maps:string,note:string,swisstopo:string,edit:string,overlay:string,image:string,show:array<string,array{teil:bool,instr:bool}>} */
	function rkrk_pt_place_defaults(): array {
		return array(
			"ort" => "",
			"lat" => "",
			"lng" => "",
			"maps" => "",
			"note" => "",
			"swisstopo" => "",
			"edit" => "",
			"overlay" => "",
			"image" => "",
			"show" => rkrk_pt_place_show_defaults(),
		);
	}
}

if (!function_exists("rkrk_pt_place_clip")) {
	function rkrk_pt_place_clip(string $text, int $max): string {
		$text = trim(str_replace(array("\r\n", "\r"), "\n", $text));
		if (function_exists("mb_substr")) {
			return mb_substr($text, 0, $max, "UTF-8");
		}
		return substr($text, 0, $max);
	}
}

if (!function_exists("rkrk_pt_place_url")) {
	function rkrk_pt_place_url(string $url): string {
		$url = trim($url);
		if ($url === "" || strlen($url) > 400) {
			return "";
		}
		if (preg_match("#^https?://#i", $url) !== 1) {
			return "";
		}
		return $url;
	}
}

if (!function_exists("rkrk_pt_place_coord")) {
	function rkrk_pt_place_coord(string $value): string {
		$value = trim(str_replace(",", ".", $value));
		if ($value === "" || !is_numeric($value)) {
			return "";
		}
		$n = (float) $value;
		if ($n < -180 || $n > 180) {
			return "";
		}
		$out = number_format($n, 6, ".", "");
		$out = rtrim(rtrim($out, "0"), ".");
		return $out === "" ? "0" : $out;
	}
}

if (!function_exists("rkrk_pt_normalize_place")) {
	/** @param mixed $raw @return array{ort:string,lat:string,lng:string,maps:string,note:string,swisstopo:string,edit:string,overlay:string,image:string,show:array<string,array{teil:bool,instr:bool}>} */
	function rkrk_pt_normalize_place($raw): array {
		$out = rkrk_pt_place_defaults();
		if (!is_array($raw)) {
			return $out;
		}
		$out["ort"] = rkrk_pt_place_clip((string) ($raw["ort"] ?? ""), 80);
		$out["lat"] = rkrk_pt_place_coord((string) ($raw["lat"] ?? ""));
		$out["lng"] = rkrk_pt_place_coord((string) ($raw["lng"] ?? ""));
		$out["maps"] = rkrk_pt_place_url((string) ($raw["maps"] ?? ""));
		$out["note"] = rkrk_pt_place_clip((string) ($raw["note"] ?? ""), 600);
		$out["swisstopo"] = rkrk_pt_place_url((string) ($raw["swisstopo"] ?? ""));
		$out["edit"] = rkrk_pt_place_url((string) ($raw["edit"] ?? ""));
		$out["overlay"] = rkrk_pt_place_url((string) ($raw["overlay"] ?? ""));
		$out["show"] = rkrk_pt_place_show_normalize($raw["show"] ?? null);
		$image = trim((string) ($raw["image"] ?? ""));
		if (preg_match("/^[a-z0-9-]{1,64}\\.(png|jpg)$/", $image) === 1) {
			$out["image"] = $image;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_normalize_chapters")) {
	/** @param mixed $raw @return list<array{id:string,title:string,body:string,staff:bool}> */
	function rkrk_pt_normalize_chapters($raw): array {
		if (!is_array($raw)) {
			return array();
		}
		$out = array();
		$seen = array();
		$n = 0;
		foreach ($raw as $item) {
			if (!is_array($item)) {
				continue;
			}
			$n++;
			if ($n > 40) {
				break;
			}
			$title = rkrk_pt_place_clip((string) ($item["title"] ?? ""), 120);
			$body = rkrk_pt_place_clip((string) ($item["body"] ?? ""), 8000);
			$image = rkrk_pt_chapter_file_name((string) ($item["image"] ?? ""));
			$imageAlign = rkrk_pt_chapter_image_align($item["image_align"] ?? "full");
			$files = rkrk_pt_normalize_chapter_files($item["files"] ?? array());
			$links = rkrk_pt_normalize_chapter_links($item["links"] ?? array());
			if ($title === "" && $body === "" && $image === "" && $files === array() && $links === array()) {
				continue;
			}
			$id = strtolower(trim((string) ($item["id"] ?? "")));
			if (preg_match("/^[a-z0-9-]{1,40}$/", $id) !== 1) {
				$id = "kapitel-" . $n;
			}
			if (isset($seen[$id])) {
				$id = substr($id, 0, 32) . "-" . $n;
			}
			$seen[$id] = true;
			$out[] = array(
				"id" => $id,
				"title" => $title !== "" ? $title : ("Kapitel " . $n),
				"body" => $body,
				"staff" => !empty($item["staff"]),
				"image" => $image,
				"image_align" => $imageAlign,
				"image_caption" => rkrk_pt_place_clip((string) ($item["image_caption"] ?? ""), 200),
				"files" => $files,
				"links" => $links,
			);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_rich_html")) {
	function rkrk_pt_rich_href(string $href): string {
		$href = trim(html_entity_decode($href, ENT_QUOTES, "UTF-8"));
		$href = str_replace(array("\r", "\n", "\t"), "", $href);
		if ($href === "" || strlen($href) > 500 || preg_match("/[\\x00-\\x1F]/", $href) === 1) {
			return "";
		}
		if (preg_match("/^mailto:/i", $href) === 1) {
			$href = preg_replace("/\\s+/", "%20", $href);
			if (!is_string($href) || preg_match("/^mailto:[^\\s<>\"']+$/i", $href) !== 1) {
				return "";
			}
			return $href;
		}
		if (preg_match("/^https?:\\/\\//i", $href) !== 1 || preg_match("/\\s/", $href) === 1) {
			return "";
		}
		return $href;
	}

	function rkrk_pt_rich_node(DOMNode $node, string $parent = "", array $mails = array()): string {
		if ($node instanceof DOMText) {
			if (($parent === "ul" || $parent === "ol") && trim($node->textContent) === "") {
				return "";
			}
			return rkrk_pt_h($node->textContent);
		}
		if ($node instanceof DOMComment) {
			return "";
		}
		if (!($node instanceof DOMElement)) {
			return "";
		}
		$tag = strtolower($node->tagName);
		if (in_array($tag, array("script", "style", "iframe", "object", "embed", "noscript"), true)) {
			return "";
		}
		if ($tag === "strong") {
			$tag = "b";
		} elseif ($tag === "em") {
			$tag = "i";
		}
		$allowed = array("b", "i", "u", "br", "p", "ul", "ol", "li", "a");
		if (!in_array($tag, $allowed, true)) {
			$plain = "";
			foreach ($node->childNodes as $child) {
				$plain .= rkrk_pt_rich_node($child, $parent, $mails);
			}
			return $plain;
		}
		if ($tag === "br") {
			if ($parent === "ul" || $parent === "ol") {
				return "";
			}
			return "<br>";
		}
		$attr = "";
		if ($tag === "a") {
			$rawHref = $node->getAttribute("href");
			if (isset($mails[$rawHref])) {
				$rawHref = $mails[$rawHref];
			}
			$href = rkrk_pt_rich_href($rawHref);
			if ($href === "") {
				$plain = "";
				foreach ($node->childNodes as $child) {
					$plain .= rkrk_pt_rich_node($child, $tag, $mails);
				}
				return $plain;
			}
			$attr = " href=\"" . rkrk_pt_h($href) . "\"";
		}
		$inner = "";
		foreach ($node->childNodes as $child) {
			$inner .= rkrk_pt_rich_node($child, $tag, $mails);
		}
		return "<" . $tag . $attr . ">" . $inner . "</" . $tag . ">";
	}

	function rkrk_pt_rich_html(string $html): string {
		$html = str_replace(array("\r\n", "\r"), "\n", $html);
		$html = preg_replace("/\\bhref\\s*:\\s*/i", "href=", $html);
		if (!is_string($html)) {
			return "";
		}
		$mails = array();
		$hideMails = static function (string $html) use (&$mails): string {
			$out = preg_replace_callback(
				"/href\\s*=\\s*([\"'])\\s*(mailto:[^\"']+)\\1/i",
				static function (array $m) use (&$mails): string {
					$key = "rkrkmail" . count($mails);
					$mails[$key] = html_entity_decode(trim($m[2]), ENT_QUOTES, "UTF-8");
					return "href=" . $m[1] . $key . $m[1];
				},
				$html
			);
			return is_string($out) ? $out : $html;
		};
		$html = $hideMails($html);
		$linked = preg_replace_callback(
			"/(^|[\\s>(])([A-Za-z0-9._%+\\-]+@[A-Za-z0-9.\\-]+\\.[A-Za-z]{2,})(?=$|[\\s<),.;])/",
			static function (array $m): string {
				return $m[1] . "<a href=\"mailto:" . $m[2] . "\">" . $m[2] . "</a>";
			},
			$html
		);
		if (is_string($linked)) {
			$html = $linked;
		}
		$html = $hideMails($html);
		if (!is_string($html)) {
			return "";
		}
		if (trim($html) === "" || !class_exists("DOMDocument")) {
			return rkrk_pt_h($html);
		}
		$prev = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML(
			"<?xml encoding=\"UTF-8\"><html><body><div>" . $html . "</div></body></html>",
			LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors($prev);
		$out = "";
		foreach ($doc->getElementsByTagName("div") as $div) {
			foreach ($div->childNodes as $child) {
				$out .= rkrk_pt_rich_node($child, "", $mails);
			}
			break;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_chapter_body_html")) {
	function rkrk_pt_chapter_body_html(string $body): string {
		$body = str_replace(array("\r\n", "\r"), "\n", trim($body));
		if ($body === "") {
			return "";
		}
		$parts = preg_split("/\n{2,}/", $body);
		if (!is_array($parts)) {
			return "";
		}
		$html = "";
		foreach ($parts as $part) {
			$part = trim((string) $part);
			if ($part === "") {
				continue;
			}
			$lines = explode("\n", $part);
			$list = $lines !== array();
			foreach ($lines as $line) {
				if (preg_match("/^(?:•|-)\s+\S/u", $line) !== 1) {
					$list = false;
					break;
				}
			}
			if ($list) {
				$html .= "<ul>";
				foreach ($lines as $line) {
					$item = preg_replace("/^(?:•|-)\s+/u", "", $line);
					$html .= "<li>" . rkrk_pt_rich_html((string) $item) . "</li>";
				}
				$html .= "</ul>";
			} elseif (preg_match("/^<(?:p|ul|ol)\\b/i", $part) === 1) {
				$html .= rkrk_pt_rich_html($part);
			} else {
				$html .= "<p>" . rkrk_pt_rich_html(str_replace("\n", "<br>", $part)) . "</p>";
			}
		}
		return $html;
	}
}

if (!function_exists("rkrk_pt_chapters_html")) {
	function rkrk_pt_chapters_html(array $day, bool $includeStaff, bool $forPdf = false): string {
		$chapters = isset($day["chapters"]) && is_array($day["chapters"]) ? $day["chapters"] : array();
		$html = "";
		$num = 0;
		foreach ($chapters as $chapter) {
			if (!is_array($chapter)) {
				continue;
			}
			$staff = !empty($chapter["staff"]);
			if ($staff && !$includeStaff) {
				continue;
			}
			$title = trim((string) ($chapter["title"] ?? ""));
			$body = rkrk_pt_chapter_body_html((string) ($chapter["body"] ?? ""));
			$filesHtml = rkrk_pt_chapter_files_html($day, $chapter);
			$imageHtml = rkrk_pt_chapter_image_html($day, $chapter, $forPdf);
			if ($title === "" && $body === "" && $filesHtml === "" && $imageHtml === "") {
				continue;
			}
			$num++;
			$heading = $num . ". " . $title;
			$align = rkrk_pt_chapter_image_align($chapter["image_align"] ?? "full");
			if ($imageHtml === "") {
				$align = "full";
			}
			if ($forPdf) {
				$tag = $staff ? " <span style=\"font-size:8pt;color:#7F7F7F;\">Für Instruktoren</span>" : "";
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td height=\"4\">&nbsp;</td></tr></table>"
					. "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td align=\"left\" style=\"border-bottom:0.3mm solid #111111;font-size:13pt;color:#111111;\"><b>"
					. rkrk_pt_h($heading) . "</b>" . $tag . "</td></tr></table>";
				if ($imageHtml !== "" && ($align === "left" || $align === "right")) {
					$imgCell = "<td width=\"42%\" valign=\"top\">" . $imageHtml . "</td>";
					$textCell = "<td width=\"58%\" valign=\"top\">" . $body . $filesHtml . "</td>";
					$html .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr>"
						. ($align === "right" ? $textCell . $imgCell : $imgCell . $textCell)
						. "</tr></table>";
				} elseif ($align === "right") {
					$html .= $body . $imageHtml . $filesHtml;
				} else {
					$html .= $imageHtml . $body . $filesHtml;
				}
			} else {
				$tag = $staff ? " <span class=\"pt-stafftag\">Nur Instruktoren</span>" : "";
				$html .= "<section class=\"pt-chapter pt-img-" . $align . "\"><h2>" . rkrk_pt_h($heading) . $tag . "</h2>"
					. "<div class=\"pt-chapter-row\">" . $imageHtml . "<div class=\"pt-chapter-copy\">" . $body . $filesHtml . "</div></div></section>";
			}
		}
		return $html;
	}
}

if (!function_exists("rkrk_pt_chapter_file_name")) {
	function rkrk_pt_chapter_file_name(string $name): string {
		$name = strtolower(basename(str_replace("\\", "/", trim($name))));
		if (preg_match("/^[a-z0-9][a-z0-9._-]{0,80}\\.(png|jpe?g|zip|kml|json|mbtiles|pdf)$/", $name) !== 1) {
			return "";
		}
		return $name;
	}
}

if (!function_exists("rkrk_pt_normalize_chapter_files")) {
	/** @param mixed $raw @return list<array{name:string,label:string}> */
	function rkrk_pt_normalize_chapter_files($raw): array {
		if (!is_array($raw)) {
			return array();
		}
		$out = array();
		foreach ($raw as $file) {
			if (!is_array($file) || count($out) >= 8) {
				continue;
			}
			$name = rkrk_pt_chapter_file_name((string) ($file["name"] ?? ""));
			if ($name === "") {
				continue;
			}
			$label = rkrk_pt_place_clip((string) ($file["label"] ?? ""), 120);
			$out[] = array(
				"name" => $name,
				"label" => $label !== "" ? $label : $name,
			);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_normalize_chapter_links")) {
	/** @param mixed $raw @return list<array{href:string,label:string}> */
	function rkrk_pt_normalize_chapter_links($raw): array {
		if (!is_array($raw)) {
			return array();
		}
		$out = array();
		foreach ($raw as $link) {
			if (!is_array($link) || count($out) >= 8) {
				continue;
			}
			$href = rkrk_pt_rich_href((string) ($link["href"] ?? ""));
			if ($href === "") {
				continue;
			}
			$label = rkrk_pt_place_clip((string) ($link["label"] ?? ""), 120);
			$out[] = array(
				"href" => $href,
				"label" => $label !== "" ? $label : $href,
			);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_chapter_dir")) {
	function rkrk_pt_chapter_dir(array $day): string {
		$dir = rkrk_pt_day_dir((string) ($day["id"] ?? ""));
		if ($dir === "") {
			return "";
		}
		return $dir . DIRECTORY_SEPARATOR . "dateien";
	}
}

if (!function_exists("rkrk_pt_chapter_file_fs")) {
	function rkrk_pt_chapter_file_fs(array $day, string $name): string {
		$name = rkrk_pt_chapter_file_name($name);
		$dir = rkrk_pt_chapter_dir($day);
		if ($name === "" || $dir === "") {
			return "";
		}
		$full = $dir . DIRECTORY_SEPARATOR . $name;
		return is_file($full) ? $full : "";
	}
}

if (!function_exists("rkrk_pt_chapter_file_visible")) {
	function rkrk_pt_chapter_file_visible(array $day, string $name, bool $includeStaff): bool {
		$name = rkrk_pt_chapter_file_name($name);
		if ($name === "") {
			return false;
		}
		$chapters = isset($day["chapters"]) && is_array($day["chapters"]) ? $day["chapters"] : array();
		foreach ($chapters as $chapter) {
			if (!is_array($chapter)) {
				continue;
			}
			if (!empty($chapter["staff"]) && !$includeStaff) {
				continue;
			}
			if (rkrk_pt_chapter_file_name((string) ($chapter["image"] ?? "")) === $name) {
				return true;
			}
			$files = isset($chapter["files"]) && is_array($chapter["files"]) ? $chapter["files"] : array();
			foreach ($files as $file) {
				if (is_array($file) && rkrk_pt_chapter_file_name((string) ($file["name"] ?? "")) === $name) {
					return true;
				}
			}
		}
		return false;
	}
}

if (!function_exists("rkrk_pt_chapter_asset_url")) {
	function rkrk_pt_chapter_asset_url(array $day, string $name, bool $publicShare): string {
		$name = rkrk_pt_chapter_file_name($name);
		if ($name === "") {
			return "";
		}
		$instr = strtolower(trim((string) ($day["share_instr"] ?? "")));
		$reqInstr = strtolower(trim((string) ($_GET["instruktion"] ?? "")));
		if ($instr !== "" && $reqInstr !== "" && hash_equals($instr, $reqInstr)) {
			return rkrk_pt_instr_url($instr) . "&datei=" . rawurlencode($name);
		}
		$share = strtolower(trim((string) ($day["share"] ?? "")));
		if ($publicShare && preg_match("/^[a-f0-9]{32}$/", $share) === 1) {
			return rkrk_pt_share_url($share) . "&datei=" . rawurlencode($name);
		}
		return rkrk_pt_page_href(array(
			"day" => (string) ($day["id"] ?? ""),
			"datei" => $name,
		));
	}
}

if (!function_exists("rkrk_pt_chapter_file_mime")) {
	function rkrk_pt_chapter_file_mime(string $name): string {
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if ($ext === "png") {
			return "image/png";
		}
		if ($ext === "jpg" || $ext === "jpeg") {
			return "image/jpeg";
		}
		if ($ext === "pdf") {
			return "application/pdf";
		}
		if ($ext === "json") {
			return "application/json";
		}
		if ($ext === "kml") {
			return "application/vnd.google-earth.kml+xml";
		}
		if ($ext === "zip") {
			return "application/zip";
		}
		return "application/octet-stream";
	}
}

if (!function_exists("rkrk_pt_chapter_image_align")) {
	function rkrk_pt_chapter_image_align($raw): string {
		$align = strtolower(trim((string) $raw));
		if ($align === "links" || $align === "left") {
			return "left";
		}
		if ($align === "rechts" || $align === "right") {
			return "right";
		}
		return "full";
	}
}

if (!function_exists("rkrk_pt_chapter_image_html")) {
	/** @param array<string,mixed> $chapter */
	function rkrk_pt_chapter_image_html(array $day, array $chapter, bool $forPdf): string {
		$image = rkrk_pt_chapter_file_name((string) ($chapter["image"] ?? ""));
		$imageFs = $image !== "" ? rkrk_pt_chapter_file_fs($day, $image) : "";
		if ($imageFs === "") {
			return "";
		}
		$align = rkrk_pt_chapter_image_align($chapter["image_align"] ?? "full");
		$caption = rkrk_pt_place_clip((string) ($chapter["image_caption"] ?? ""), 200);
		if ($forPdf) {
			$bin = @file_get_contents($imageFs);
			if (!is_string($bin) || $bin === "") {
				return "";
			}
			$ext = strtolower(pathinfo($imageFs, PATHINFO_EXTENSION));
			$mime = ($ext === "jpg" || $ext === "jpeg") ? "image/jpeg" : "image/png";
			$width = $align === "full" ? "480" : "200";
			$img = "<img src=\"data:" . $mime . ";base64," . base64_encode($bin) . "\" width=\"" . $width . "\">";
			if ($caption === "") {
				return $img;
			}
			return "<table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"" . $width . "\"><tr><td>" . $img . "</td></tr><tr><td style=\"font-size:8pt;font-style:italic;color:#595959;\">" . rkrk_pt_h($caption) . "</td></tr></table>";
		}
		$url = rkrk_pt_chapter_asset_url($day, $image, empty($chapter["staff"]));
		$alt = $caption !== "" ? $caption : (string) ($chapter["title"] ?? "");
		$html = "<figure class=\"pt-figure\"><img class=\"pt-chapter-img\" src=\"" . rkrk_pt_h($url) . "\" alt=\"" . rkrk_pt_h($alt) . "\" title=\"Doppelklick zeigt das Bild gross\">";
		if ($caption !== "") {
			$html .= "<figcaption class=\"pt-figcap\">" . rkrk_pt_h($caption) . "</figcaption>";
		}
		return $html . "</figure>";
	}
}

if (!function_exists("rkrk_pt_chapter_files_html")) {
	/** @param array<string,mixed> $chapter */
	function rkrk_pt_chapter_files_html(array $day, array $chapter): string {
		$publicShare = empty($chapter["staff"]);
		$items = "";
		$links = isset($chapter["links"]) && is_array($chapter["links"]) ? $chapter["links"] : array();
		foreach ($links as $link) {
			if (!is_array($link)) {
				continue;
			}
			$href = rkrk_pt_rich_href((string) ($link["href"] ?? ""));
			if ($href === "") {
				continue;
			}
			$label = trim((string) ($link["label"] ?? ""));
			if ($label === "") {
				$label = $href;
			}
			$extra = stripos($href, "mailto:") === 0 ? "" : " target=\"_blank\" rel=\"noopener\"";
			$items .= "<li><a href=\"" . rkrk_pt_h($href) . "\"" . $extra . ">" . rkrk_pt_h($label) . "</a></li>";
		}
		$files = isset($chapter["files"]) && is_array($chapter["files"]) ? $chapter["files"] : array();
		foreach ($files as $file) {
			if (!is_array($file)) {
				continue;
			}
			$name = rkrk_pt_chapter_file_name((string) ($file["name"] ?? ""));
			if ($name === "" || rkrk_pt_chapter_file_fs($day, $name) === "") {
				continue;
			}
			$label = trim((string) ($file["label"] ?? ""));
			if ($label === "") {
				$label = $name;
			}
			$url = rkrk_pt_chapter_asset_url($day, $name, $publicShare);
			$items .= "<li><a href=\"" . rkrk_pt_h($url) . "\">" . rkrk_pt_h($label) . "</a></li>";
		}
		if ($items === "") {
			return "";
		}
		return "<ul class=\"pt-files\">" . $items . "</ul>";
	}
}

if (!function_exists("rkrk_pt_chapter_store_upload")) {
	/** @param array<string,mixed> $file */
	function rkrk_pt_chapter_store_upload(array $day, array $file, bool $imageOnly): string {
		$err = (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE);
		if ($err !== UPLOAD_ERR_OK) {
			return "";
		}
		$tmp = (string) ($file["tmp_name"] ?? "");
		$size = (int) ($file["size"] ?? 0);
		if ($tmp === "" || !is_uploaded_file($tmp) || $size < 1) {
			return "";
		}
		$max = $imageOnly ? 8 * 1024 * 1024 : 20 * 1024 * 1024;
		if ($size > $max) {
			return "";
		}
		$ext = "";
		if ($imageOnly) {
			$info = @getimagesize($tmp);
			$type = is_array($info) ? (int) ($info[2] ?? 0) : 0;
			if ($type === IMAGETYPE_PNG) {
				$ext = "png";
			} elseif ($type === IMAGETYPE_JPEG) {
				$ext = "jpg";
			}
		} else {
			$orig = strtolower((string) ($file["name"] ?? ""));
			$try = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
			if ($try === "jpeg") {
				$try = "jpg";
			}
			if (in_array($try, array("png", "jpg", "zip", "kml", "json", "mbtiles", "pdf"), true)) {
				$ext = $try;
			}
		}
		if ($ext === "") {
			return "";
		}
		$dir = rkrk_pt_chapter_dir($day);
		if ($dir === "") {
			return "";
		}
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$name = bin2hex(random_bytes(6)) . "." . $ext;
		$dest = $dir . DIRECTORY_SEPARATOR . $name;
		if (!@move_uploaded_file($tmp, $dest)) {
			return "";
		}
		return $name;
	}
}

if (!function_exists("rkrk_pt_chapters_from_request")) {
	/** @param array<string,mixed> $day @return list<array<string,mixed>> */
	function rkrk_pt_chapters_from_request(array $day): array {
		$old = array();
		$prevChapters = isset($day["chapters"]) && is_array($day["chapters"]) ? $day["chapters"] : array();
		foreach ($prevChapters as $chapter) {
			if (is_array($chapter)) {
				$old[(string) ($chapter["id"] ?? "")] = $chapter;
			}
		}
		$ids = isset($_POST["ch_id"]) && is_array($_POST["ch_id"]) ? array_values($_POST["ch_id"]) : array();
		$titles = isset($_POST["ch_title"]) && is_array($_POST["ch_title"]) ? array_values($_POST["ch_title"]) : array();
		$bodies = isset($_POST["ch_body"]) && is_array($_POST["ch_body"]) ? array_values($_POST["ch_body"]) : array();
		$audiences = isset($_POST["ch_audience"]) && is_array($_POST["ch_audience"]) ? array_values($_POST["ch_audience"]) : array();
		$aligns = isset($_POST["ch_align"]) && is_array($_POST["ch_align"]) ? array_values($_POST["ch_align"]) : array();
		$captions = isset($_POST["ch_caption"]) && is_array($_POST["ch_caption"]) ? array_values($_POST["ch_caption"]) : array();
		$fileLabels = isset($_POST["ch_file_label"]) && is_array($_POST["ch_file_label"]) ? array_values($_POST["ch_file_label"]) : array();
		$linkHrefs = isset($_POST["ch_link_href"]) && is_array($_POST["ch_link_href"]) ? array_values($_POST["ch_link_href"]) : array();
		$linkLabels = isset($_POST["ch_link_label"]) && is_array($_POST["ch_link_label"]) ? array_values($_POST["ch_link_label"]) : array();
		$drops = isset($_POST["ch_drop_file"]) && is_array($_POST["ch_drop_file"]) ? $_POST["ch_drop_file"] : array();
		$dropLinks = isset($_POST["ch_drop_link"]) && is_array($_POST["ch_drop_link"]) ? $_POST["ch_drop_link"] : array();
		$clears = isset($_POST["ch_clear"]) && is_array($_POST["ch_clear"]) ? $_POST["ch_clear"] : array();
		$clearSet = array();
		foreach ($clears as $clearId) {
			$clearSet[(string) $clearId] = true;
		}
		$imageFiles = (isset($_FILES["ch_image"]) && is_array($_FILES["ch_image"])) ? $_FILES["ch_image"] : array();
		$fileFiles = (isset($_FILES["ch_file"]) && is_array($_FILES["ch_file"])) ? $_FILES["ch_file"] : array();
		$count = max(count($ids), count($titles), count($bodies));
		if ($count > 40) {
			$count = 40;
		}
		$rows = array();
		$used = array();
		for ($i = 0; $i < $count; $i++) {
			$postedId = strtolower(trim((string) ($ids[$i] ?? "")));
			$prev = isset($old[$postedId]) && is_array($old[$postedId]) ? $old[$postedId] : null;
			$id = $prev !== null ? $postedId : ("k" . bin2hex(random_bytes(4)));
			$files = ($prev !== null && isset($prev["files"]) && is_array($prev["files"])) ? $prev["files"] : array();
			$dropNames = isset($drops[$postedId]) && is_array($drops[$postedId]) ? $drops[$postedId] : array();
			$dropSet = array();
			foreach ($dropNames as $dropName) {
				$safeDrop = rkrk_pt_chapter_file_name((string) $dropName);
				if ($safeDrop !== "") {
					$dropSet[$safeDrop] = true;
				}
			}
			$keptFiles = array();
			foreach ($files as $file) {
				if (!is_array($file)) {
					continue;
				}
				$fname = rkrk_pt_chapter_file_name((string) ($file["name"] ?? ""));
				if ($fname === "" || isset($dropSet[$fname])) {
					continue;
				}
				$keptFiles[] = $file;
				$used[$fname] = true;
			}
			$upload = array(
				"name" => $fileFiles["name"][$i] ?? "",
				"type" => $fileFiles["type"][$i] ?? "",
				"tmp_name" => $fileFiles["tmp_name"][$i] ?? "",
				"error" => $fileFiles["error"][$i] ?? UPLOAD_ERR_NO_FILE,
				"size" => $fileFiles["size"][$i] ?? 0,
			);
			$stored = rkrk_pt_chapter_store_upload($day, $upload, false);
			if ($stored !== "") {
				$keptFiles[] = array(
					"name" => $stored,
					"label" => (string) ($fileLabels[$i] ?? ""),
				);
				$used[$stored] = true;
			}
			$links = ($prev !== null && isset($prev["links"]) && is_array($prev["links"])) ? $prev["links"] : array();
			$goneLinks = isset($dropLinks[$postedId]) && is_array($dropLinks[$postedId]) ? $dropLinks[$postedId] : array();
			$goneSet = array();
			foreach ($goneLinks as $goneHref) {
				$goneSet[(string) $goneHref] = true;
			}
			$keptLinks = array();
			foreach ($links as $link) {
				if (!is_array($link)) {
					continue;
				}
				$href = (string) ($link["href"] ?? "");
				if ($href === "" || isset($goneSet[$href])) {
					continue;
				}
				$keptLinks[] = $link;
			}
			$newHref = (string) ($linkHrefs[$i] ?? "");
			if (trim($newHref) !== "") {
				$keptLinks[] = array(
					"href" => $newHref,
					"label" => (string) ($linkLabels[$i] ?? ""),
				);
			}
			$image = $prev !== null ? rkrk_pt_chapter_file_name((string) ($prev["image"] ?? "")) : "";
			if ($image !== "" && isset($clearSet[$postedId])) {
				$image = "";
			}
			$imageUpload = array(
				"name" => $imageFiles["name"][$i] ?? "",
				"type" => $imageFiles["type"][$i] ?? "",
				"tmp_name" => $imageFiles["tmp_name"][$i] ?? "",
				"error" => $imageFiles["error"][$i] ?? UPLOAD_ERR_NO_FILE,
				"size" => $imageFiles["size"][$i] ?? 0,
			);
			$newImage = rkrk_pt_chapter_store_upload($day, $imageUpload, true);
			if ($newImage !== "") {
				$image = $newImage;
			}
			if ($image !== "") {
				$used[$image] = true;
			}
			$rows[] = array(
				"id" => $id,
				"title" => (string) ($titles[$i] ?? ""),
				"body" => (string) ($bodies[$i] ?? ""),
				"staff" => ((string) ($audiences[$i] ?? "")) === "instr",
				"image" => $image,
				"image_align" => (string) ($aligns[$i] ?? "full"),
				"image_caption" => (string) ($captions[$i] ?? ""),
				"files" => $keptFiles,
				"links" => $keptLinks,
			);
		}
		foreach ($prevChapters as $chapter) {
			if (!is_array($chapter)) {
				continue;
			}
			$names = array();
			$prevImage = rkrk_pt_chapter_file_name((string) ($chapter["image"] ?? ""));
			if ($prevImage !== "") {
				$names[] = $prevImage;
			}
			$prevFiles = isset($chapter["files"]) && is_array($chapter["files"]) ? $chapter["files"] : array();
			foreach ($prevFiles as $file) {
				if (is_array($file)) {
					$names[] = rkrk_pt_chapter_file_name((string) ($file["name"] ?? ""));
				}
			}
			foreach ($names as $name) {
				if ($name !== "" && !isset($used[$name])) {
					$fs = rkrk_pt_chapter_file_fs($day, $name);
					if ($fs !== "") {
						@unlink($fs);
					}
				}
			}
		}
		return rkrk_pt_normalize_chapters($rows);
	}
}

if (!function_exists("rkrk_pt_chapter_editor_html")) {
	function rkrk_pt_chapter_editor_html(array $day): string {
		$chapters = isset($day["chapters"]) && is_array($day["chapters"]) ? $day["chapters"] : array();
		$open = isset($_GET["info"]) || $chapters === array();
		ob_start();
		?>
		<details class="pt-fold" id="pt-info-edit"<?php echo $open ? " open" : ""; ?>>
			<summary>Informationen bearbeiten</summary>
			<div class="pt-fold-body">
			<p class="pt-note">Jedes Kapitel einzeln. Die Nummer ist die Reihenfolge, ziehen sortiert sie. Teilnehmer sehen nur Kapitel mit Anzeige Teilnehmer. Instruktoren-Kapitel erscheinen in der angemeldeten Bearbeitung und in deren PDF. Im Text sind HTML-Angaben erlaubt: &lt;b&gt;fett&lt;/b&gt;, &lt;i&gt;kursiv&lt;/i&gt;, &lt;a href="https://..."&gt;Link&lt;/a&gt; und &lt;a href="mailto:name@example.ch"&gt;E-Mail&lt;/a&gt;.</p>
			<form method="post" enctype="multipart/form-data" id="pt-chapters-form">
				<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
				<input type="hidden" name="pt_action" value="chapters">
				<input type="hidden" name="day" value="<?php echo rkrk_pt_h((string) ($day["id"] ?? "")); ?>">
				<div id="pt-chapter-list">
				<?php
				$pos = 0;
				foreach ($chapters as $chapter) {
					if (!is_array($chapter)) {
						continue;
					}
					$pos++;
					$cid = (string) ($chapter["id"] ?? "");
					$align = rkrk_pt_chapter_image_align($chapter["image_align"] ?? "full");
					$aud = !empty($chapter["staff"]) ? "instr" : "tn";
					$image = rkrk_pt_chapter_file_name((string) ($chapter["image"] ?? ""));
					$imageUrl = ($image !== "" && rkrk_pt_chapter_file_fs($day, $image) !== "")
						? rkrk_pt_chapter_asset_url($day, $image, false) : "";
					?>
					<article class="pt-ch-edit">
						<div class="pt-ch-bar">
							<span class="pt-ch-handle" draggable="true" title="Ziehen zum Sortieren">ziehen</span>
							<span class="pt-ch-pos"><?php echo (int) $pos; ?>.</span>
							<input type="hidden" name="ch_id[]" value="<?php echo rkrk_pt_h($cid); ?>">
							<input type="text" name="ch_title[]" maxlength="120" value="<?php echo rkrk_pt_h((string) ($chapter["title"] ?? "")); ?>" aria-label="Titel" required>
							<select name="ch_audience[]" aria-label="Anzeige">
								<option value="tn"<?php echo $aud === "tn" ? " selected" : ""; ?>>Teilnehmer</option>
								<option value="instr"<?php echo $aud === "instr" ? " selected" : ""; ?>>Instruktoren</option>
							</select>
							<button class="pt-btn pt-ch-del" type="button">Löschen</button>
						</div>
						<label>Text</label>
						<textarea name="ch_body[]" maxlength="8000"><?php echo rkrk_pt_h((string) ($chapter["body"] ?? "")); ?></textarea>
						<details class="pt-chapter-extra">
						<summary>Bild und Dateien</summary>
						<div class="pt-ch-meta">
							<div>
								<label>Bild</label>
								<input type="file" name="ch_image[]" accept="image/png,image/jpeg,.png,.jpg,.jpeg">
							</div>
							<div>
								<label>Bildlage</label>
								<select name="ch_align[]">
									<option value="full"<?php echo $align === "full" ? " selected" : ""; ?>>gesamte Breite</option>
									<option value="left"<?php echo $align === "left" ? " selected" : ""; ?>>links vom Text</option>
									<option value="right"<?php echo $align === "right" ? " selected" : ""; ?>>rechts vom Text</option>
								</select>
							</div>
							<div>
								<label>Bildunterschrift</label>
								<input type="text" name="ch_caption[]" maxlength="200" value="<?php echo rkrk_pt_h((string) ($chapter["image_caption"] ?? "")); ?>">
							</div>
						</div>
						<?php if ($imageUrl !== ""): ?>
						<p><img src="<?php echo rkrk_pt_h($imageUrl); ?>" alt="" style="max-width:220px;height:auto;">
						<label><input type="checkbox" name="ch_clear[]" value="<?php echo rkrk_pt_h($cid); ?>"> Bild entfernen</label></p>
						<?php endif; ?>
						<?php
						$files = isset($chapter["files"]) && is_array($chapter["files"]) ? $chapter["files"] : array();
						foreach ($files as $file) {
							if (!is_array($file)) {
								continue;
							}
							$fname = rkrk_pt_chapter_file_name((string) ($file["name"] ?? ""));
							if ($fname === "") {
								continue;
							}
							$flabel = trim((string) ($file["label"] ?? ""));
							if ($flabel === "") {
								$flabel = $fname;
							}
							echo "<label><input type=\"checkbox\" name=\"ch_drop_file[" . rkrk_pt_h($cid) . "][]\" value=\"" . rkrk_pt_h($fname) . "\"> Datei entfernen: " . rkrk_pt_h($flabel) . "</label>";
						}
						$links = isset($chapter["links"]) && is_array($chapter["links"]) ? $chapter["links"] : array();
						foreach ($links as $link) {
							if (!is_array($link)) {
								continue;
							}
							$href = trim((string) ($link["href"] ?? ""));
							if ($href === "") {
								continue;
							}
							$llabel = trim((string) ($link["label"] ?? ""));
							if ($llabel === "") {
								$llabel = $href;
							}
							echo "<label><input type=\"checkbox\" name=\"ch_drop_link[" . rkrk_pt_h($cid) . "][]\" value=\"" . rkrk_pt_h($href) . "\"> Link entfernen: " . rkrk_pt_h($llabel) . "</label>";
						}
						?>
						<div class="pt-ch-meta">
							<div>
								<label>Weitere Datei</label>
								<input type="file" name="ch_file[]">
							</div>
							<div>
								<label>Dateiname für Teilnehmer</label>
								<input type="text" name="ch_file_label[]" maxlength="120" placeholder="z. B. MBTiles herunterladen">
							</div>
							<div>
								<label>Weiterer Link</label>
								<input type="text" name="ch_link_href[]" maxlength="400" placeholder="https:// oder mailto:name@example.ch">
							</div>
							<div>
								<label>Linktext</label>
								<input type="text" name="ch_link_label[]" maxlength="120">
							</div>
						</div>
						</details>
					</article>
					<?php
				}
				?>
				</div>
				<div class="pt-actions">
					<button class="pt-btn" type="button" id="pt-ch-add">Kapitel hinzufügen</button>
					<button class="pt-btn" type="submit">Informationen speichern</button>
				</div>
			</form>
			<template id="pt-ch-template">
				<article class="pt-ch-edit">
					<div class="pt-ch-bar">
						<span class="pt-ch-handle" draggable="true" title="Ziehen zum Sortieren">ziehen</span>
						<span class="pt-ch-pos"></span>
						<input type="hidden" name="ch_id[]" value="">
						<input type="text" name="ch_title[]" maxlength="120" value="" aria-label="Titel" required>
						<select name="ch_audience[]" aria-label="Anzeige">
							<option value="tn" selected>Teilnehmer</option>
							<option value="instr">Instruktoren</option>
						</select>
						<button class="pt-btn pt-ch-del" type="button">Löschen</button>
					</div>
					<label>Text</label>
					<textarea name="ch_body[]" maxlength="8000"></textarea>
					<details class="pt-chapter-extra">
					<summary>Bild und Dateien</summary>
					<div class="pt-ch-meta">
						<div>
							<label>Bild</label>
							<input type="file" name="ch_image[]" accept="image/png,image/jpeg,.png,.jpg,.jpeg">
						</div>
						<div>
							<label>Bildlage</label>
							<select name="ch_align[]">
								<option value="full" selected>gesamte Breite</option>
								<option value="left">links vom Text</option>
								<option value="right">rechts vom Text</option>
							</select>
						</div>
						<div>
							<label>Bildunterschrift</label>
							<input type="text" name="ch_caption[]" maxlength="200" value="">
						</div>
					</div>
					<div class="pt-ch-meta">
						<div>
							<label>Weitere Datei</label>
							<input type="file" name="ch_file[]">
						</div>
						<div>
							<label>Dateiname für Teilnehmer</label>
							<input type="text" name="ch_file_label[]" maxlength="120" placeholder="z. B. MBTiles herunterladen">
						</div>
						<div>
							<label>Weiterer Link</label>
							<input type="text" name="ch_link_href[]" maxlength="400" placeholder="https:// oder mailto:name@example.ch">
						</div>
						<div>
							<label>Linktext</label>
							<input type="text" name="ch_link_label[]" maxlength="120">
						</div>
					</div>
					</details>
				</article>
			</template>
			<script>
			(function () {
				var list = document.getElementById("pt-chapter-list");
				var tpl = document.getElementById("pt-ch-template");
				var add = document.getElementById("pt-ch-add");
				if (!list || !tpl) return;
				var drag = null;
				function pos() {
					var rows = list.querySelectorAll(".pt-ch-edit");
					for (var i = 0; i < rows.length; i++) {
						var n = rows[i].querySelector(".pt-ch-pos");
						if (n) n.textContent = (i + 1) + ".";
					}
				}
				function bind(row) {
					var del = row.querySelector(".pt-ch-del");
					if (del) del.addEventListener("click", function () {
						if (confirm("Dieses Kapitel löschen?")) {
							row.remove();
							pos();
						}
					});
				}
				list.querySelectorAll(".pt-ch-edit").forEach(bind);
				list.addEventListener("dragstart", function (e) {
					var row = e.target.closest(".pt-ch-edit");
					if (!row || !e.target.closest(".pt-ch-handle")) {
						e.preventDefault();
						return;
					}
					drag = row;
					e.dataTransfer.effectAllowed = "move";
					e.dataTransfer.setData("text/plain", "chapter");
					row.classList.add("pt-ch-dragging");
				});
				list.addEventListener("dragend", function () {
					if (drag) drag.classList.remove("pt-ch-dragging");
					drag = null;
					pos();
				});
				list.addEventListener("dragover", function (e) {
					if (!drag) return;
					e.preventDefault();
					var row = e.target.closest(".pt-ch-edit");
					if (!row || row === drag) return;
					var rect = row.getBoundingClientRect();
					list.insertBefore(drag, (e.clientY > rect.top + rect.height / 2) ? row.nextSibling : row);
				});
				if (add) add.addEventListener("click", function () {
					var node = tpl.content.firstElementChild.cloneNode(true);
					var id = node.querySelector("input[name='ch_id[]']");
					if (id) id.value = "neu-" + Math.random().toString(16).slice(2, 10);
					list.appendChild(node);
					bind(node);
					pos();
					var title = node.querySelector("input[name='ch_title[]']");
					if (title) title.focus();
				});
				pos();
			})();
			</script>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}
}

if (!function_exists("rkrk_pt_place_dir")) {
	function rkrk_pt_place_dir(?array $day = null): string {
		$dir = rkrk_pt_day_dir((string) (is_array($day) ? ($day["id"] ?? "") : ""));
		if ($dir === "") {
			return "";
		}
		return $dir . DIRECTORY_SEPARATOR . "bilder";
	}
}

if (!function_exists("rkrk_pt_place_image_fs")) {
	function rkrk_pt_place_image_fs(array $day): string {
		$place = isset($day["place"]) && is_array($day["place"]) ? $day["place"] : array();
		$image = trim((string) ($place["image"] ?? ""));
		if ($image === "") {
			return "";
		}
		$dir = rkrk_pt_place_dir($day);
		if ($dir === "") {
			return "";
		}
		$full = $dir . DIRECTORY_SEPARATOR . $image;
		return is_file($full) ? $full : "";
	}
}

if (!function_exists("rkrk_pt_place_image_mime")) {
	function rkrk_pt_place_image_mime(string $fs): string {
		$ext = strtolower(pathinfo($fs, PATHINFO_EXTENSION));
		if ($ext === "jpg" || $ext === "jpeg") {
			return "image/jpeg";
		}
		return "image/png";
	}
}

if (!function_exists("rkrk_pt_qr_matrix")) {
	/** @return array{num_rows:int,num_cols:int,bcode:list<list<int>>}|array{} */
	function rkrk_pt_qr_matrix(string $url): array {
		$url = trim($url);
		if ($url === "") {
			return array();
		}
		$lib = __DIR__ . "/rkrk_pt_qr.inc.php";
		if (!function_exists("rkrk_pt_qr_encode") && is_file($lib)) {
			require_once $lib;
		}
		if (!function_exists("rkrk_pt_qr_encode")) {
			return array();
		}
		$arr = rkrk_pt_qr_encode($url);
		if (!is_array($arr) || empty($arr["bcode"]) || (int) ($arr["num_cols"] ?? 0) < 1) {
			return array();
		}
		return $arr;
	}
}

if (!function_exists("rkrk_pt_qr_svg")) {
	function rkrk_pt_qr_svg(string $url): string {
		$arr = rkrk_pt_qr_matrix($url);
		if ($arr === array()) {
			return "";
		}
		$cols = (int) $arr["num_cols"];
		$rows = (int) $arr["num_rows"];
		$quiet = 2;
		$span = $cols + ($quiet * 2);
		$dots = "";
		for ($r = 0; $r < $rows; $r++) {
			$row = isset($arr["bcode"][$r]) && is_array($arr["bcode"][$r]) ? $arr["bcode"][$r] : array();
			for ($c = 0; $c < $cols; $c++) {
				if ((int) ($row[$c] ?? 0) !== 1) {
					continue;
				}
				$cx = $quiet + $c + 0.5;
				$cy = $quiet + $r + 0.5;
				$dots .= "<circle cx=\"" . $cx . "\" cy=\"" . $cy . "\" r=\"0.46\"/>";
			}
		}
		return "<svg class=\"pt-qr\" viewBox=\"0 0 " . $span . " " . $span . "\" role=\"img\" aria-hidden=\"true\">"
			. "<rect width=\"100%\" height=\"100%\" fill=\"#fff\"/>"
			. "<g fill=\"#111\">" . $dots . "</g></svg>";
	}
}

if (!function_exists("rkrk_pt_qr_link_html")) {
	function rkrk_pt_qr_link_html(string $url, string $label): string {
		$url = trim($url);
		$svg = rkrk_pt_qr_svg($url);
		if ($svg === "") {
			return "";
		}
		return "<span class=\"pt-qr-item\">"
			. "<button type=\"button\" class=\"pt-qr-open\" data-pt-qr-label=\"" . rkrk_pt_h($label) . "\" title=\"Doppelklick vergrössert\">"
			. $svg . "</button>"
			. "<a href=\"" . rkrk_pt_h($url) . "\" target=\"_blank\" rel=\"noopener\">" . rkrk_pt_h($label) . "</a>"
			. "</span>";
	}
}

if (!function_exists("rkrk_pt_pdf_qr_dots")) {
	function rkrk_pt_pdf_qr_dots($pdf, string $url, float $x, float $y, float $size): bool {
		$arr = rkrk_pt_qr_matrix($url);
		if ($arr === array() || !is_object($pdf) || !method_exists($pdf, "Rect")) {
			return false;
		}
		$cols = (int) $arr["num_cols"];
		$rows = (int) $arr["num_rows"];
		$quiet = 2;
		$mod = $size / ($cols + ($quiet * 2));
		$pdf->SetFillColor(17, 17, 17);
		for ($r = 0; $r < $rows; $r++) {
			$row = isset($arr["bcode"][$r]) && is_array($arr["bcode"][$r]) ? $arr["bcode"][$r] : array();
			for ($c = 0; $c < $cols; $c++) {
				if ((int) ($row[$c] ?? 0) !== 1) {
					continue;
				}
				$pdf->Rect($x + (($quiet + $c) * $mod), $y + (($quiet + $r) * $mod), $mod, $mod, "F");
			}
		}
		return true;
	}
}

if (!function_exists("rkrk_pt_pdf_qr_img")) {
	function rkrk_pt_pdf_qr_img(string $url, string $label): string {
		$arr = rkrk_pt_qr_matrix($url);
		if ($arr === array() || !function_exists("imagecreatetruecolor")) {
			return "";
		}
		$cols = (int) $arr["num_cols"];
		$rows = (int) $arr["num_rows"];
		$quiet = 2;
		$mod = 4;
		$span = ($cols + ($quiet * 2)) * $mod;
		$im = imagecreatetruecolor($span, $span);
		if ($im === false) {
			return "";
		}
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 17, 17, 17);
		imagefilledrectangle($im, 0, 0, $span - 1, $span - 1, $white);
		for ($r = 0; $r < $rows; $r++) {
			$row = isset($arr["bcode"][$r]) && is_array($arr["bcode"][$r]) ? $arr["bcode"][$r] : array();
			for ($c = 0; $c < $cols; $c++) {
				if ((int) ($row[$c] ?? 0) !== 1) {
					continue;
				}
				$x0 = ($quiet + $c) * $mod;
				$y0 = ($quiet + $r) * $mod;
				imagefilledrectangle($im, $x0, $y0, $x0 + $mod - 1, $y0 + $mod - 1, $black);
			}
		}
		ob_start();
		imagepng($im);
		$png = ob_get_clean();
		imagedestroy($im);
		if (!is_string($png) || $png === "") {
			return "";
		}
		$h = "rkrk_pt_h";
		return "<td align=\"center\" valign=\"top\"><img src=\"data:image/png;base64," . base64_encode($png) . "\" width=\"78\" height=\"78\"><br>"
			. "<a href=\"" . $h($url) . "\"><font size=\"7\">" . $h($label) . "</font></a></td>";
	}
}

if (!function_exists("rkrk_pt_rm_dir")) {
	function rkrk_pt_rm_dir(string $dir): void {
		$root = rkrk_pt_root();
		$realRoot = ($root !== "" && is_dir($root)) ? realpath($root) : false;
		$real = is_dir($dir) ? realpath($dir) : false;
		if ($realRoot === false || $real === false || strpos($real, $realRoot . DIRECTORY_SEPARATOR) !== 0) {
			return;
		}
		$items = scandir($real);
		if (!is_array($items)) {
			return;
		}
		foreach ($items as $item) {
			if ($item === "." || $item === "..") {
				continue;
			}
			$full = $real . DIRECTORY_SEPARATOR . $item;
			if (is_dir($full)) {
				rkrk_pt_rm_dir($full);
			} else {
				@unlink($full);
			}
		}
		@rmdir($real);
	}
}

if (!function_exists("rkrk_pt_copy_dir")) {
	function rkrk_pt_copy_dir(string $from, string $to): void {
		if ($from === "" || $to === "" || !is_dir($from)) {
			return;
		}
		rkrk_pt_ensure_dir($to);
		$items = scandir($from);
		if (!is_array($items)) {
			return;
		}
		foreach ($items as $item) {
			if ($item === "." || $item === "..") {
				continue;
			}
			$src = $from . DIRECTORY_SEPARATOR . $item;
			$dest = $to . DIRECTORY_SEPARATOR . $item;
			if (is_dir($src)) {
				rkrk_pt_copy_dir($src, $dest);
			} elseif (is_file($src) && !is_file($dest)) {
				@copy($src, $dest);
			}
		}
	}
}

if (!function_exists("rkrk_pt_unique_day_id")) {
	function rkrk_pt_unique_day_id(string $name, string $keepId, array $taken): string {
		$base = rkrk_pt_slug($name !== "" ? $name : "Pruefungstag");
		if (!rkrk_pt_day_id_ok($base)) {
			$base = "pruefungstag";
		}
		$id = $base;
		$n = 2;
		while (isset($taken[$id]) && $id !== $keepId) {
			$id = $base . "-" . $n;
			$n++;
		}
		return $id;
	}
}

if (!function_exists("rkrk_pt_rename_day_storage")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_rename_day_storage(string $oldId, string $newId, array &$day): void {
		$oldId = strtolower(trim($oldId));
		$newId = strtolower(trim($newId));
		if ($oldId === $newId || !rkrk_pt_day_id_ok($oldId) || !rkrk_pt_day_id_ok($newId)) {
			return;
		}
		$oldDir = rkrk_pt_day_dir($oldId);
		$newDir = rkrk_pt_day_dir($newId);
		if ($oldDir !== "" && is_dir($oldDir) && $newDir !== "" && !is_dir($newDir)) {
			@rename($oldDir, $newDir);
		}
		$place = isset($day["place"]) && is_array($day["place"]) ? $day["place"] : array();
		$image = trim((string) ($place["image"] ?? ""));
		$ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
		if ($ext !== "jpg" && $ext !== "png") {
			$ext = "png";
		}
		$bilder = rkrk_pt_place_dir(array("id" => $newId));
		$destName = $newId . "." . $ext;
		if ($bilder !== "") {
			$from = $image !== "" ? $bilder . DIRECTORY_SEPARATOR . $image : "";
			$to = $bilder . DIRECTORY_SEPARATOR . $destName;
			if ($from !== "" && is_file($from) && $from !== $to && !is_file($to)) {
				@rename($from, $to);
			}
			if (is_file($to)) {
				$place["image"] = $destName;
			}
		}
		$day["place"] = $place;
		$day["id"] = $newId;
	}
}

if (!function_exists("rkrk_pt_write_day_file")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_write_day_file(array $day): bool {
		$id = strtolower(trim((string) ($day["id"] ?? "")));
		$dir = rkrk_pt_day_dir($id);
		if ($dir === "" || !rkrk_pt_ensure_dir($dir)) {
			return false;
		}
		rkrk_pt_ensure_dir($dir . DIRECTORY_SEPARATOR . "bilder");
		rkrk_pt_ensure_dir($dir . DIRECTORY_SEPARATOR . "dateien");
		$json = json_encode($day, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if (!is_string($json)) {
			return false;
		}
		return @file_put_contents($dir . DIRECTORY_SEPARATOR . "tag.json", $json, LOCK_EX) !== false;
	}
}

if (!function_exists("rkrk_pt_migrate_legacy")) {
	function rkrk_pt_migrate_legacy(): void {
		$legacy = rkrk_pt_config_path();
		$root = rkrk_pt_root();
		if ($legacy === "" || $root === "" || !is_file($legacy)) {
			return;
		}
		if (is_dir($root)) {
			$have = glob($root . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . "tag.json");
			if (is_array($have) && $have !== array()) {
				return;
			}
		}
		$raw = @file_get_contents($legacy);
		$data = is_string($raw) ? @json_decode($raw, true) : null;
		if (!is_array($data) || !isset($data["days"]) || !is_array($data["days"])) {
			return;
		}
		$personel = dirname($legacy);
		$taken = array();
		$order = array();
		foreach ($data["days"] as $item) {
			if (!is_array($item)) {
				continue;
			}
			$oldId = strtolower(trim((string) ($item["id"] ?? "")));
			$name = trim((string) ($item["name"] ?? ""));
			$newId = rkrk_pt_unique_day_id($name !== "" ? $name : $oldId, "", $taken);
			$taken[$newId] = true;
			$order[] = $newId;
			$dir = rkrk_pt_day_dir($newId);
			if ($dir === "") {
				continue;
			}
			rkrk_pt_ensure_dir($dir);
			rkrk_pt_ensure_dir($dir . DIRECTORY_SEPARATOR . "bilder");
			rkrk_pt_ensure_dir($dir . DIRECTORY_SEPARATOR . "dateien");
			if (rkrk_pt_day_id_ok($oldId)) {
				$oldFiles = $personel . DIRECTORY_SEPARATOR . "pruefungstag-dateien" . DIRECTORY_SEPARATOR . $oldId;
				rkrk_pt_copy_dir($oldFiles, $dir . DIRECTORY_SEPARATOR . "dateien");
			}
			$place = isset($item["place"]) && is_array($item["place"]) ? $item["place"] : array();
			$image = trim((string) ($place["image"] ?? ""));
			if ($image !== "") {
				$from = $personel . DIRECTORY_SEPARATOR . "pruefungstag-bilder" . DIRECTORY_SEPARATOR . $image;
				$ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
				if ($ext !== "jpg") {
					$ext = "png";
				}
				$destName = $newId . "." . $ext;
				$to = $dir . DIRECTORY_SEPARATOR . "bilder" . DIRECTORY_SEPARATOR . $destName;
				if (is_file($from) && !is_file($to)) {
					@copy($from, $to);
				}
				if (is_file($to)) {
					$place["image"] = $destName;
					$item["place"] = $place;
				}
			}
			$item["id"] = $newId;
			$json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$written = is_string($json) && @file_put_contents($dir . DIRECTORY_SEPARATOR . "tag.json", $json, LOCK_EX) !== false;
			if ($written && rkrk_pt_day_id_ok($oldId)) {
				$oldFiles = $personel . DIRECTORY_SEPARATOR . "pruefungstag-dateien" . DIRECTORY_SEPARATOR . $oldId;
				if (is_dir($oldFiles)) {
					$oldItems = scandir($oldFiles);
					if (is_array($oldItems)) {
						foreach ($oldItems as $oldItem) {
							if ($oldItem === "." || $oldItem === "..") {
								continue;
							}
							$oldPath = $oldFiles . DIRECTORY_SEPARATOR . $oldItem;
							$newPath = $dir . DIRECTORY_SEPARATOR . "dateien" . DIRECTORY_SEPARATOR . $oldItem;
							if (is_file($oldPath) && is_file($newPath)) {
								@unlink($oldPath);
							}
						}
					}
					@rmdir($oldFiles);
				}
				if ($image !== "" && is_file($from) && is_file($to)) {
					@unlink($from);
				}
			}
		}
		if ($order !== array()) {
			rkrk_pt_ensure_dir($root);
			@file_put_contents(
				$root . DIRECTORY_SEPARATOR . "index.json",
				json_encode(array("order" => $order), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
				LOCK_EX
			);
			@rename($legacy, $legacy . ".bak");
		}
	}
}

if (!function_exists("rkrk_pt_config_load")) {
	/** @return array{days: list<array<string,mixed>>} */
	function rkrk_pt_config_load(): array {
		rkrk_pt_migrate_legacy();
		$root = rkrk_pt_root();
		if ($root === "" || !is_dir($root)) {
			return array("days" => array());
		}
		$found = array();
		$scan = scandir($root);
		if (is_array($scan)) {
			foreach ($scan as $name) {
				if (!rkrk_pt_day_id_ok((string) $name)) {
					continue;
				}
				$file = $root . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "tag.json";
				if (!is_file($file)) {
					continue;
				}
				$raw = @file_get_contents($file);
				$item = is_string($raw) ? @json_decode($raw, true) : null;
				if (!is_array($item)) {
					continue;
				}
				$item["id"] = (string) $name;
				$found[(string) $name] = $item;
			}
		}
		$order = array();
		$indexRaw = @file_get_contents($root . DIRECTORY_SEPARATOR . "index.json");
		$index = is_string($indexRaw) ? @json_decode($indexRaw, true) : null;
		if (is_array($index) && isset($index["order"]) && is_array($index["order"])) {
			$order = $index["order"];
		}
		$seq = array();
		foreach ($order as $id) {
			$id = strtolower(trim((string) $id));
			if (isset($found[$id])) {
				$seq[] = $found[$id];
				unset($found[$id]);
			}
		}
		foreach ($found as $item) {
			$seq[] = $item;
		}
		$days = array();
		$used = array();
		foreach ($seq as $item) {
			$one = rkrk_pt_normalize_day($item, $used);
			$used[$one["id"]] = true;
			$days[] = $one;
		}
		return array("days" => $days);
	}
}

if (!function_exists("rkrk_pt_config_save")) {
	/** @param array{days?:list<array<string,mixed>>} $cfg */
	function rkrk_pt_config_save(array $cfg): bool {
		$root = rkrk_pt_root();
		if ($root === "" || !rkrk_pt_ensure_dir($root)) {
			return false;
		}
		$used = array();
		$order = array();
		$keep = array();
		$src = isset($cfg["days"]) && is_array($cfg["days"]) ? $cfg["days"] : array();
		foreach ($src as $item) {
			if (!is_array($item)) {
				continue;
			}
			$one = rkrk_pt_normalize_day($item, $used);
			$id = (string) ($one["id"] ?? "");
			if (!rkrk_pt_day_id_ok($id)) {
				continue;
			}
			$used[$id] = true;
			$keep[$id] = true;
			$order[] = $id;
			if (!rkrk_pt_write_day_file($one)) {
				return false;
			}
		}
		$scan = scandir($root);
		if (is_array($scan)) {
			foreach ($scan as $name) {
				$name = (string) $name;
				if (!rkrk_pt_day_id_ok($name) || isset($keep[$name])) {
					continue;
				}
				$tag = $root . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "tag.json";
				if (is_file($tag)) {
					rkrk_pt_rm_dir($root . DIRECTORY_SEPARATOR . $name);
				}
			}
		}
		$index = json_encode(array("order" => $order), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		return is_string($index) && @file_put_contents($root . DIRECTORY_SEPARATOR . "index.json", $index, LOCK_EX) !== false;
	}
}

if (!function_exists("rkrk_pt_day_by_id")) {
	/** @return array<string,mixed>|null */
	function rkrk_pt_day_by_id(string $id, ?array $cfg = null): ?array {
		if ($cfg === null) {
			$cfg = rkrk_pt_config_load();
		}
		foreach ((array) ($cfg["days"] ?? array()) as $item) {
			if (is_array($item) && (string) ($item["id"] ?? "") === $id) {
				return $item;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_pt_people_for_list")) {
	/**
	 * Teilnehmer in der Reihenfolge der Liste. Inaktive werden übersprungen.
	 *
	 * @param array<string,mixed>|null $list
	 * @return array{people:list<array{id:string,name:string,drone:string}>,skipped:int}
	 */
	function rkrk_pt_people_for_list(?array $list): array {
		if (!is_array($list)) {
			return array("people" => array(), "skipped" => 0);
		}
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$index = array();
		foreach ($pilots as $row) {
			if (!is_array($row) || !function_exists("rkrk_tr_pilot_keys_of")) {
				continue;
			}
			foreach (rkrk_tr_pilot_keys_of($row) as $k) {
				if (!isset($index[$k])) {
					$index[$k] = $row;
				}
			}
		}
		$disabled = function_exists("rkrk_tr_parse_disabled")
			? rkrk_tr_parse_disabled($list["disabled"] ?? array())
			: array();
		$people = array();
		$skipped = 0;
		$seen = array();
		$ids = function_exists("rkrk_tr_parse_ids")
			? rkrk_tr_parse_ids($list["pilot_ids"] ?? array())
			: array();
		foreach ($ids as $id) {
			$row = $index[$id] ?? null;
			if (!is_array($row)) {
				if (!isset($seen[$id])) {
					$people[] = array("id" => $id, "name" => $id, "drone" => "");
					$seen[$id] = true;
				}
				continue;
			}
			$key = function_exists("rkrk_tr_pilot_key") ? rkrk_tr_pilot_key($row) : $id;
			if (isset($seen[$key])) {
				continue;
			}
			$off = false;
			if (function_exists("rkrk_tr_pilot_keys_of")) {
				foreach (rkrk_tr_pilot_keys_of($row) as $k) {
					if (isset($disabled["id:" . $k])) {
						$off = true;
						break;
					}
				}
			}
			if ($off) {
				$skipped++;
				$seen[$key] = true;
				continue;
			}
			list($vor, $nach) = function_exists("rkrk_tr_name_parts")
				? rkrk_tr_name_parts($row)
				: array("", "");
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim($vor . " " . $nach);
			if ($name === "") {
				$name = $key;
			}
			$drone = function_exists("rkrk_tr_drone_label")
				? rkrk_tr_drone_label($row["drohnentyp"] ?? "")
				: trim((string) ($row["drohnentyp"] ?? ""));
			$people[] = array("id" => $key, "name" => $name, "drone" => $drone);
			$seen[$key] = true;
		}
		return array("people" => $people, "skipped" => $skipped);
	}
}

if (!function_exists("rkrk_pt_order_from_list")) {
	/**
	 * Sitzreihenfolge nach Anmeldungseingang: Reihenfolge der Teilnehmerliste, ohne Inaktive.
	 *
	 * @param array<string,mixed>|null $list
	 * @return list<string>
	 */
	function rkrk_pt_order_from_list(?array $list): array {
		$pack = rkrk_pt_people_for_list($list);
		$order = array();
		foreach ($pack["people"] as $p) {
			$id = trim((string) ($p["id"] ?? ""));
			if ($id !== "") {
				$order[] = $id;
			}
		}
		return $order;
	}
}

if (!function_exists("rkrk_pt_reset_for_new_list")) {
	/**
	 * Neue Teilnehmerliste: Historie und Abmeldungen weg, Plätze nach Anmeldungseingang.
	 *
	 * @param array<string,mixed> $day
	 * @param array<string,mixed>|null $list
	 */
	function rkrk_pt_reset_for_new_list(array &$day, ?array $list): void {
		$day["log"] = array();
		$day["withdrawn"] = array();
		$marks = rkrk_pt_normalize_marks($day["marks"] ?? array());
		foreach (array_keys($marks) as $key) {
			if (strncmp((string) $key, "p:", 2) === 0) {
				unset($marks[$key]);
			}
		}
		$day["marks"] = $marks;
		$day["order"] = rkrk_pt_order_from_list($list);
	}
}

if (!function_exists("rkrk_pt_normalize_withdrawn")) {
	/** @param mixed $raw @return list<string> */
	function rkrk_pt_normalize_withdrawn($raw): array {
		$out = array();
		$seen = array();
		if (!is_array($raw)) {
			return $out;
		}
		foreach ($raw as $pid) {
			$pid = rkrk_pt_clean_pid((string) $pid);
			if ($pid === "" || isset($seen[$pid])) {
				continue;
			}
			$out[] = $pid;
			$seen[$pid] = true;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_normalize_log")) {
	/** @param mixed $raw @return list<array{at:string,action:string,pid:string,name:string}> */
	function rkrk_pt_normalize_log($raw): array {
		$out = array();
		if (!is_array($raw)) {
			return $out;
		}
		foreach ($raw as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$action = (string) ($entry["action"] ?? "");
			if ($action !== "angemeldet" && $action !== "abgemeldet") {
				continue;
			}
			$pid = rkrk_pt_clean_pid((string) ($entry["pid"] ?? ""));
			if ($pid === "") {
				continue;
			}
			$at = trim((string) ($entry["at"] ?? ""));
			if (strlen($at) > 40) {
				$at = substr($at, 0, 40);
			}
			$name = trim((string) ($entry["name"] ?? ""));
			if (function_exists("mb_substr")) {
				$name = mb_substr($name, 0, 80, "UTF-8");
			} else {
				$name = substr($name, 0, 80);
			}
			$out[] = array(
				"at" => $at,
				"action" => $action,
				"pid" => $pid,
				"name" => $name,
			);
		}
		if (count($out) > 200) {
			$out = array_slice($out, -200);
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_withdrawn_set")) {
	/** @param array<string,mixed> $day @return array<string,bool> */
	function rkrk_pt_withdrawn_set(array $day): array {
		$set = array();
		foreach (rkrk_pt_normalize_withdrawn($day["withdrawn"] ?? array()) as $pid) {
			$set[$pid] = true;
		}
		return $set;
	}
}

if (!function_exists("rkrk_pt_people_active")) {
	/**
	 * @param list<array{id:string,name:string,drone:string}> $people
	 * @param array<string,mixed> $day
	 * @return list<array{id:string,name:string,drone:string}>
	 */
	function rkrk_pt_people_active(array $people, array $day): array {
		$off = rkrk_pt_withdrawn_set($day);
		if ($off === array()) {
			return $people;
		}
		$out = array();
		foreach ($people as $p) {
			if (!is_array($p)) {
				continue;
			}
			$id = trim((string) ($p["id"] ?? ""));
			if ($id !== "" && isset($off[$id])) {
				continue;
			}
			$out[] = $p;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_now_label")) {
	function rkrk_pt_now_label(): string {
		$now = new DateTime("now", new DateTimeZone("Europe/Zurich"));
		return $now->format("d.m.Y H:i");
	}
}

if (!function_exists("rkrk_pt_mark_now")) {
	function rkrk_pt_mark_now(): string {
		$now = new DateTime("now", new DateTimeZone("Europe/Zurich"));
		return $now->format("Y-m-d H:i:s");
	}
}

if (!function_exists("rkrk_pt_mark_stamp")) {
	function rkrk_pt_mark_stamp(string $at): int {
		$at = trim($at);
		if ($at === "") {
			return 0;
		}
		$dt = DateTime::createFromFormat("Y-m-d H:i:s", $at, new DateTimeZone("Europe/Zurich"));
		return $dt instanceof DateTime ? $dt->getTimestamp() : 0;
	}
}

if (!function_exists("rkrk_pt_mark_fresh_at")) {
	function rkrk_pt_mark_fresh_at(string $at): bool {
		$ts = rkrk_pt_mark_stamp($at);
		if ($ts <= 0) {
			return false;
		}
		$now = (new DateTime("now", new DateTimeZone("Europe/Zurich")))->getTimestamp();
		$age = $now - $ts;
		return $age >= -3600 && $age <= 7 * 86400;
	}
}

if (!function_exists("rkrk_pt_normalize_marks")) {
	/** @param mixed $raw @return array<string,string> */
	function rkrk_pt_normalize_marks($raw): array {
		$out = array();
		if (!is_array($raw)) {
			return $out;
		}
		foreach ($raw as $key => $at) {
			$key = trim((string) $key);
			if ($key === "" || strlen($key) > 120 || preg_match("/^(s|l|p):[A-Za-z0-9_.:@|-]+$/", $key) !== 1) {
				continue;
			}
			$at = trim((string) $at);
			if (!rkrk_pt_mark_fresh_at($at)) {
				continue;
			}
			$out[$key] = (new DateTime("@" . (string) rkrk_pt_mark_stamp($at)))
				->setTimezone(new DateTimeZone("Europe/Zurich"))
				->format("Y-m-d H:i:s");
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_mark_is")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_mark_is(array $day, string $key): bool {
		$marks = isset($day["marks"]) && is_array($day["marks"]) ? $day["marks"] : array();
		return isset($marks[$key]) && rkrk_pt_mark_fresh_at((string) $marks[$key]);
	}
}

if (!function_exists("rkrk_pt_mark_title")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_mark_title(array $day, string $key): string {
		if (!rkrk_pt_mark_is($day, $key)) {
			return "";
		}
		$at = (string) ($day["marks"][$key] ?? "");
		$dt = DateTime::createFromFormat("Y-m-d H:i:s", $at, new DateTimeZone("Europe/Zurich"));
		if (!$dt instanceof DateTime) {
			return "Geändert";
		}
		return "Geändert am " . $dt->format("d.m.Y H:i");
	}
}

if (!function_exists("rkrk_pt_mark_count")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_mark_count(array $day): int {
		$n = 0;
		$marks = isset($day["marks"]) && is_array($day["marks"]) ? $day["marks"] : array();
		foreach ($marks as $at) {
			if (rkrk_pt_mark_fresh_at((string) $at)) {
				$n++;
			}
		}
		return $n;
	}
}

if (!function_exists("rkrk_pt_changed_pid_set")) {
	/** @param array<string,mixed> $day @return array<string,bool> */
	function rkrk_pt_changed_pid_set(array $day): array {
		$out = array();
		$marks = isset($day["marks"]) && is_array($day["marks"]) ? $day["marks"] : array();
		foreach ($marks as $key => $at) {
			$key = (string) $key;
			if (strpos($key, "p:") !== 0 || !rkrk_pt_mark_fresh_at((string) $at)) {
				continue;
			}
			$pid = substr($key, 2);
			if ($pid !== "") {
				$out[$pid] = true;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_placement_map")) {
	/**
	 * Sichtbarer Platz: Sitzfolge s0, s1, … oder Wartelistenrang w0, w1, …
	 *
	 * @param array<string,mixed> $day
	 * @param list<array<string,mixed>> $people
	 * @return array<string,string>
	 */
	function rkrk_pt_placement_map(array $day, array $people): array {
		$plan = rkrk_pt_plan($day, $people);
		$map = array();
		$seatNo = 0;
		$blocks = isset($plan["blocks"]) && is_array($plan["blocks"]) ? $plan["blocks"] : array();
		foreach ($blocks as $block) {
			if (!is_array($block)) {
				continue;
			}
			$seats = isset($block["seats"]) && is_array($block["seats"]) ? $block["seats"] : array();
			foreach ($seats as $seat) {
				$person = is_array($seat) ? ($seat["person"] ?? null) : null;
				$id = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
				if ($id !== "" && !isset($map[$id])) {
					$map[$id] = "s" . $seatNo;
				}
				$seatNo++;
			}
		}
		$wait = isset($plan["wait"]) && is_array($plan["wait"]) ? $plan["wait"] : array();
		$rank = 0;
		foreach ($wait as $person) {
			if (!is_array($person)) {
				continue;
			}
			$id = trim((string) ($person["id"] ?? ""));
			if ($id !== "" && !isset($map[$id])) {
				$map[$id] = "w" . $rank;
			}
			$rank++;
		}
		return $map;
	}
}

if (!function_exists("rkrk_pt_mark_placement_diff")) {
	/**
	 * @param array<string,mixed> $day
	 * @param array<string,string> $before
	 * @param array<string,string> $after
	 */
	function rkrk_pt_mark_placement_diff(array &$day, array $before, array $after): void {
		$marks = rkrk_pt_normalize_marks($day["marks"] ?? array());
		$now = rkrk_pt_mark_now();
		$ids = array_unique(array_merge(array_keys($before), array_keys($after)));
		foreach ($ids as $pid) {
			if (($before[$pid] ?? "") !== ($after[$pid] ?? "")) {
				$marks["p:" . $pid] = $now;
			}
		}
		$day["marks"] = $marks;
	}
}

if (!function_exists("rkrk_pt_mark_staff_diff")) {
	/** @param array<string,mixed> $day @param array<string,mixed> $beforeStaff @param array<string,mixed> $afterStaff */
	function rkrk_pt_mark_staff_diff(array &$day, array $beforeStaff, array $afterStaff, string $halfId, int $lane, bool $leadPosted): void {
		$before = rkrk_pt_normalize_staff($beforeStaff);
		$after = rkrk_pt_normalize_staff($afterStaff);
		$marks = rkrk_pt_normalize_marks($day["marks"] ?? array());
		$now = rkrk_pt_mark_now();
		$bRoles = isset($before[$halfId][(string) $lane]["roles"]) && is_array($before[$halfId][(string) $lane]["roles"])
			? $before[$halfId][(string) $lane]["roles"] : array();
		$aRoles = isset($after[$halfId][(string) $lane]["roles"]) && is_array($after[$halfId][(string) $lane]["roles"])
			? $after[$halfId][(string) $lane]["roles"] : array();
		foreach (rkrk_pt_station_roles() as $role) {
			$rid = $role["id"];
			for ($i = 0; $i < 3; $i++) {
				$bv = rkrk_pt_clean_pid((string) ($bRoles[$rid][$i] ?? ""));
				$av = rkrk_pt_clean_pid((string) ($aRoles[$rid][$i] ?? ""));
				if ($bv !== $av) {
					$marks["s:" . $halfId . ":" . $lane . ":" . $rid . ":" . $i] = $now;
				}
			}
		}
		if ($leadPosted) {
			foreach (array("tageschef", "techn") as $leadId) {
				$bv = rkrk_pt_clean_pid((string) ($before[$halfId]["lead"][$leadId] ?? ""));
				$av = rkrk_pt_clean_pid((string) ($after[$halfId]["lead"][$leadId] ?? ""));
				if ($bv !== $av) {
					$marks["l:" . $halfId . ":" . $leadId] = $now;
				}
			}
		}
		$day["marks"] = $marks;
	}
}

if (!function_exists("rkrk_pt_log_add")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_log_add(array &$day, string $action, string $pid, string $name): void {
		$log = rkrk_pt_normalize_log($day["log"] ?? array());
		$log[] = array(
			"at" => rkrk_pt_now_label(),
			"action" => $action,
			"pid" => rkrk_pt_clean_pid($pid),
			"name" => $name,
		);
		$day["log"] = rkrk_pt_normalize_log($log);
	}
}

if (!function_exists("rkrk_pt_material_order")) {
	/**
	 * Sitzreihenfolge inklusive freier Plätze, danach die Warteliste.
	 *
	 * @param list<array{id:string,name:string,drone:string}> $people
	 * @return list<string>
	 */
	function rkrk_pt_material_order(array $day, array $people): array {
		$plan = rkrk_pt_plan($day, $people);
		$ids = array();
		$blocks = isset($plan["blocks"]) && is_array($plan["blocks"]) ? $plan["blocks"] : array();
		foreach ($blocks as $block) {
			if (!is_array($block)) {
				continue;
			}
			$seats = isset($block["seats"]) && is_array($block["seats"]) ? $block["seats"] : array();
			foreach ($seats as $seat) {
				$person = is_array($seat) ? ($seat["person"] ?? null) : null;
				$ids[] = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
			}
		}
		$wait = isset($plan["wait"]) && is_array($plan["wait"]) ? $plan["wait"] : array();
		foreach ($wait as $person) {
			if (!is_array($person)) {
				continue;
			}
			$id = trim((string) ($person["id"] ?? ""));
			if ($id !== "") {
				$ids[] = $id;
			}
		}
		return $ids;
	}
}

if (!function_exists("rkrk_pt_order_withdraw")) {
	/**
	 * Nimmt die Person aus der Reihe. Ihr Platz geht an die erste Person der Warteliste.
	 *
	 * @param list<string> $order
	 * @return list<string>
	 */
	function rkrk_pt_order_withdraw(array $order, string $pid, int $capacity): array {
		$idx = null;
		foreach ($order as $i => $id) {
			if ((string) $id === $pid) {
				$idx = (int) $i;
				break;
			}
		}
		if ($idx === null) {
			return $order;
		}
		if ($capacity < 0) {
			$capacity = 0;
		}
		if ($idx < $capacity) {
			$take = null;
			$n = count($order);
			for ($j = $capacity; $j < $n; $j++) {
				if (trim((string) $order[$j]) !== "") {
					$take = $j;
					break;
				}
			}
			if ($take !== null) {
				$order[$idx] = (string) $order[$take];
				array_splice($order, $take, 1);
			} else {
				$order[$idx] = "";
			}
			return array_values($order);
		}
		array_splice($order, $idx, 1);
		return array_values($order);
	}
}

if (!function_exists("rkrk_pt_order_enroll")) {
	/**
	 * Hängt die Person ans Ende der materialisierten Reihe.
	 * Das ist hinter den Plätzen und hinter der Warteliste.
	 *
	 * @param list<string> $order
	 * @return list<string>
	 */
	function rkrk_pt_order_enroll(array $order, string $pid): array {
		$pid = trim($pid);
		$out = array();
		foreach ($order as $id) {
			if ((string) $id !== $pid) {
				$out[] = (string) $id;
			}
		}
		if ($pid !== "") {
			$out[] = $pid;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_person_label")) {
	function rkrk_pt_person_label(string $pid): string {
		$pid = trim($pid);
		if ($pid === "" || !function_exists("rkrk_assess_personel_load_file") || !function_exists("rkrk_tr_pilot_keys_of")) {
			return $pid;
		}
		$file = rkrk_assess_personel_load_file();
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			$hit = false;
			foreach (rkrk_tr_pilot_keys_of($row) as $k) {
				if ($k === $pid) {
					$hit = true;
					break;
				}
			}
			if (!$hit) {
				continue;
			}
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: trim((string) ($row["vorname"] ?? "") . " " . (string) ($row["nachname"] ?? ""));
			return $name !== "" ? $name : $pid;
		}
		return $pid;
	}
}

if (!function_exists("rkrk_pt_person_aliases")) {
	/** @return array<string,bool> */
	function rkrk_pt_person_aliases(string $pid): array {
		$pid = trim($pid);
		$aliases = array();
		if ($pid === "") {
			return $aliases;
		}
		$aliases[$pid] = true;
		if (!function_exists("rkrk_assess_personel_load_file") || !function_exists("rkrk_tr_pilot_keys_of")) {
			return $aliases;
		}
		$file = rkrk_assess_personel_load_file();
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		foreach ($pilots as $row) {
			if (!is_array($row)) {
				continue;
			}
			$keys = rkrk_tr_pilot_keys_of($row);
			$hit = false;
			foreach ($keys as $k) {
				if ($k === $pid) {
					$hit = true;
					break;
				}
			}
			if (!$hit) {
				continue;
			}
			foreach ($keys as $k) {
				$aliases[$k] = true;
			}
			break;
		}
		return $aliases;
	}
}

if (!function_exists("rkrk_pt_list_set_member")) {
	/** Teilnehmerliste ohne Historie: nur Mitgliedschaft. */
	function rkrk_pt_list_set_member(string $listId, string $pid, bool $on): bool {
		$listId = trim($listId);
		$pid = trim($pid);
		if ($listId === "" || $pid === "" || !function_exists("rkrk_tr_config_load") || !function_exists("rkrk_tr_config_save") || !function_exists("rkrk_tr_parse_ids")) {
			return false;
		}
		$aliases = rkrk_pt_person_aliases($pid);
		$cfg = rkrk_tr_config_load();
		$lists = isset($cfg["lists"]) && is_array($cfg["lists"]) ? $cfg["lists"] : array();
		$found = false;
		foreach ($lists as $i => $item) {
			if (!is_array($item) || (string) ($item["id"] ?? "") !== $listId) {
				continue;
			}
			$ids = rkrk_tr_parse_ids($item["pilot_ids"] ?? array());
			$next = array();
			$present = false;
			foreach ($ids as $id) {
				if (isset($aliases[$id])) {
					$present = true;
					if (!$on) {
						continue;
					}
				}
				$next[] = $id;
			}
			if ($on && !$present) {
				$next[] = $pid;
			}
			if ($on && function_exists("rkrk_tr_parse_disabled")) {
				$off = rkrk_tr_parse_disabled($item["disabled"] ?? array());
				foreach (array_keys($aliases) as $k) {
					unset($off["id:" . $k]);
				}
				$lists[$i]["disabled"] = $off;
			}
			$lists[$i]["pilot_ids"] = $next;
			$found = true;
			break;
		}
		if (!$found) {
			return false;
		}
		$cfg["lists"] = $lists;
		return rkrk_tr_config_save($cfg);
	}
}

if (!function_exists("rkrk_pt_day_mark_withdrawn")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_day_mark_withdrawn(array &$day, string $pid, string $name): void {
		$pid = trim($pid);
		if ($pid === "" || isset(rkrk_pt_withdrawn_set($day)[$pid])) {
			return;
		}
		$list = function_exists("rkrk_tr_list_by_id")
			? rkrk_tr_list_by_id((string) ($day["list_id"] ?? ""))
			: null;
		$pack = rkrk_pt_people_for_list($list);
		$people = $pack["people"];
		$have = false;
		foreach ($people as $p) {
			if ((string) ($p["id"] ?? "") === $pid) {
				$have = true;
				break;
			}
		}
		if (!$have) {
			$people[] = array("id" => $pid, "name" => $name !== "" ? $name : $pid, "drone" => "");
		}
		$active = rkrk_pt_people_active($people, $day);
		$planNow = rkrk_pt_plan($day, $active);
		$beforeMap = rkrk_pt_placement_map($day, $active);
		$order = rkrk_pt_order_withdraw(
			rkrk_pt_material_order($day, $active),
			$pid,
			(int) ($planNow["capacity"] ?? 0)
		);
		$gone = rkrk_pt_normalize_withdrawn($day["withdrawn"] ?? array());
		$gone[] = $pid;
		$day["order"] = $order;
		$day["withdrawn"] = $gone;
		$afterPeople = rkrk_pt_people_active($people, $day);
		rkrk_pt_mark_placement_diff($day, $beforeMap, rkrk_pt_placement_map($day, $afterPeople));
		rkrk_pt_log_add($day, "abgemeldet", $pid, $name !== "" ? $name : $pid);
	}
}

if (!function_exists("rkrk_pt_day_mark_enrolled")) {
	/** @param array<string,mixed> $day */
	function rkrk_pt_day_mark_enrolled(array &$day, string $pid, string $name): void {
		$pid = trim($pid);
		if ($pid === "" || !isset(rkrk_pt_withdrawn_set($day)[$pid])) {
			return;
		}
		$list = function_exists("rkrk_tr_list_by_id")
			? rkrk_tr_list_by_id((string) ($day["list_id"] ?? ""))
			: null;
		$pack = rkrk_pt_people_for_list($list);
		$active = rkrk_pt_people_active($pack["people"], $day);
		$beforeMap = rkrk_pt_placement_map($day, $active);
		$order = rkrk_pt_order_enroll(rkrk_pt_material_order($day, $active), $pid);
		$stay = array();
		foreach (rkrk_pt_normalize_withdrawn($day["withdrawn"] ?? array()) as $wid) {
			if ($wid !== $pid) {
				$stay[] = $wid;
			}
		}
		$day["order"] = $order;
		$day["withdrawn"] = $stay;
		$afterPeople = rkrk_pt_people_active($pack["people"], $day);
		rkrk_pt_mark_placement_diff($day, $beforeMap, rkrk_pt_placement_map($day, $afterPeople));
		rkrk_pt_log_add($day, "angemeldet", $pid, $name !== "" ? $name : $pid);
	}
}

if (!function_exists("rkrk_pt_sync_list_membership")) {
	/**
	 * Änderung der Teilnehmerliste auf jeden verknüpften Prüfungstag übertragen.
	 * Historie bleibt nur am Prüfungstag.
	 *
	 * @param array<string,mixed> $before
	 * @param array<string,mixed> $after
	 */
	function rkrk_pt_sync_list_membership(array $before, array $after): void {
		if (!function_exists("rkrk_tr_parse_ids")) {
			return;
		}
		$index = static function (array $cfg): array {
			$out = array();
			foreach ((array) ($cfg["lists"] ?? array()) as $item) {
				if (!is_array($item)) {
					continue;
				}
				$id = (string) ($item["id"] ?? "");
				if ($id === "") {
					continue;
				}
				$out[$id] = array();
				foreach (rkrk_tr_parse_ids($item["pilot_ids"] ?? array()) as $pid) {
					$out[$id][$pid] = true;
				}
			}
			return $out;
		};
		$oldBy = $index($before);
		$newBy = $index($after);
		$pt = rkrk_pt_config_load();
		$days = isset($pt["days"]) && is_array($pt["days"]) ? $pt["days"] : array();
		$changed = false;
		$listIds = array();
		foreach (array_keys($oldBy) as $id) {
			$listIds[$id] = true;
		}
		foreach (array_keys($newBy) as $id) {
			$listIds[$id] = true;
		}
		foreach (array_keys($listIds) as $listId) {
			$prev = $oldBy[$listId] ?? array();
			$next = $newBy[$listId] ?? array();
			$removed = array();
			foreach (array_keys($prev) as $pid) {
				if (!isset($next[$pid])) {
					$removed[] = $pid;
				}
			}
			$added = array();
			foreach (array_keys($next) as $pid) {
				if (!isset($prev[$pid])) {
					$added[] = $pid;
				}
			}
			if ($removed === array() && $added === array()) {
				continue;
			}
			foreach ($days as $i => $day) {
				if (!is_array($day) || (string) ($day["list_id"] ?? "") !== $listId) {
					continue;
				}
				foreach ($removed as $pid) {
					if (isset(rkrk_pt_withdrawn_set($days[$i])[$pid])) {
						continue;
					}
					rkrk_pt_day_mark_withdrawn($days[$i], $pid, rkrk_pt_person_label($pid));
					$changed = true;
				}
				foreach ($added as $pid) {
					if (!isset(rkrk_pt_withdrawn_set($days[$i])[$pid])) {
						continue;
					}
					rkrk_pt_day_mark_enrolled($days[$i], $pid, rkrk_pt_person_label($pid));
					$changed = true;
				}
			}
		}
		if ($changed) {
			rkrk_pt_config_save(array("days" => $days));
		}
	}
}

if (!function_exists("rkrk_pt_apply_order")) {
	/**
	 * @param list<array{id:string,name:string,drone:string}> $people
	 * @param list<string> $order
	 * @return list<array{id:string,name:string,drone:string}>
	 */
	function rkrk_pt_apply_order(array $people, array $order): array {
		$byId = array();
		foreach ($people as $p) {
			$byId[$p["id"]] = $p;
		}
		$out = array();
		$seen = array();
		foreach ($order as $id) {
			$id = (string) $id;
			if (isset($byId[$id]) && !isset($seen[$id])) {
				$out[] = $byId[$id];
				$seen[$id] = true;
			}
		}
		foreach ($people as $p) {
			if (!isset($seen[$p["id"]])) {
				$out[] = $p;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_active_halfday_count")) {
	function rkrk_pt_active_halfday_count(array $day): int {
		$n = 0;
		$half = isset($day["halfdays"]) && is_array($day["halfdays"]) ? $day["halfdays"] : array();
		foreach (rkrk_pt_halfday_defs() as $hd) {
			if (!empty($half[$hd["id"]])) {
				$n++;
			}
		}
		return $n;
	}
}

if (!function_exists("rkrk_pt_plan")) {
	/**
	 * @param list<array{id:string,name:string,drone:string}> $people
	 * @return array{blocks:list<array<string,mixed>>,wait:list<array{id:string,name:string,drone:string}>,capacity:int,parallel:int,halfdays:int}
	 */
	function rkrk_pt_order_slots(array $people, array $order, int $capacity): array {
		$byId = array();
		foreach ($people as $p) {
			if (!is_array($p)) {
				continue;
			}
			$id = trim((string) ($p["id"] ?? ""));
			if ($id !== "") {
				$byId[$id] = $p;
			}
		}
		if ($capacity < 0) {
			$capacity = 0;
		}
		$hasGap = false;
		foreach ($order as $id) {
			if (trim((string) $id) === "") {
				$hasGap = true;
				break;
			}
		}
		$slots = array_fill(0, $capacity, null);
		$used = array();
		$wait = array();
		$slotMode = $hasGap || count($order) >= $capacity;
		if ($slotMode && $capacity > 0) {
			$i = 0;
			foreach ($order as $id) {
				$id = trim((string) $id);
				if ($i < $capacity) {
					if ($id !== "" && isset($byId[$id]) && !isset($used[$id])) {
						$slots[$i] = $byId[$id];
						$used[$id] = true;
					}
					$i++;
					continue;
				}
				if ($id !== "" && isset($byId[$id]) && !isset($used[$id])) {
					$wait[] = $byId[$id];
					$used[$id] = true;
				}
			}
			foreach ($people as $p) {
				if (!is_array($p)) {
					continue;
				}
				$id = trim((string) ($p["id"] ?? ""));
				if ($id !== "" && !isset($used[$id])) {
					$wait[] = $p;
				}
			}
			$next = 0;
			$waiting = count($wait);
			for ($s = 0; $s < $capacity && $next < $waiting; $s++) {
				if ($slots[$s] !== null) {
					continue;
				}
				$slots[$s] = $wait[$next];
				$next++;
			}
			if ($next > 0) {
				$wait = array_slice($wait, $next);
			}
			return array("slots" => $slots, "wait" => $wait);
		}
		$ordered = rkrk_pt_apply_order($people, $order);
		for ($i = 0; $i < $capacity; $i++) {
			$slots[$i] = $ordered[$i] ?? null;
		}
		return array("slots" => $slots, "wait" => array_slice($ordered, $capacity));
	}

	/**
	 * @param list<array{id:string,name:string,drone:string}> $people
	 * @return array{blocks:list<array<string,mixed>>,wait:list<array{id:string,name:string,drone:string}>,capacity:int,parallel:int,halfdays:int}
	 */
	function rkrk_pt_plan(array $day, array $people): array {
		$half = isset($day["halfdays"]) && is_array($day["halfdays"]) ? $day["halfdays"] : array();
		$starts = isset($day["starts"]) && is_array($day["starts"]) ? $day["starts"] : array();
		$order = isset($day["order"]) && is_array($day["order"]) ? $day["order"] : array();
		$capacity = 0;
		$maxGroups = 1;
		foreach (rkrk_pt_halfday_defs() as $hd) {
			if (empty($half[$hd["id"]])) {
				continue;
			}
			$groups = rkrk_pt_groups_of($day, $hd["id"]);
			if ($groups > $maxGroups) {
				$maxGroups = $groups;
			}
			$capacity += rkrk_pt_places_of($day, $hd["id"]) * 3 * $groups;
		}
		$layout = rkrk_pt_order_slots($people, $order, $capacity);
		$slots = $layout["slots"];
		$blocks = array();
		$cursor = 0;
		$seatNo = 1;
		foreach (rkrk_pt_halfday_defs() as $hd) {
			if (empty($half[$hd["id"]])) {
				continue;
			}
			$start = rkrk_pt_normalize_time((string) ($starts[$hd["id"]] ?? ""), $hd["start"]);
			$places = rkrk_pt_places_of($day, $hd["id"]);
			$groups = rkrk_pt_groups_of($day, $hd["id"]);
			$seatCount = $places * 3;
			for ($lane = 1; $lane <= $groups; $lane++) {
				$seats = array();
				for ($i = 0; $i < $seatCount; $i++) {
					$seats[] = array(
						"nr" => $seatNo,
						"local" => $i + 1,
						"person" => $slots[$cursor] ?? null,
					);
					$seatNo++;
					$cursor++;
				}
				$label = "Gruppe " . $hd["letter"];
				if ($groups > 1) {
					$label .= (string) $lane;
				}
				$blocks[] = array(
					"half_id" => $hd["id"],
					"half_label" => $hd["label"],
					"lane" => $lane,
					"groups" => $groups,
					"label" => $label,
					"start" => $start,
					"places" => $places,
					"seats" => $seats,
				);
			}
		}
		return array(
			"blocks" => $blocks,
			"wait" => $layout["wait"],
			"capacity" => $capacity,
			"parallel" => $maxGroups,
			"halfdays" => rkrk_pt_active_halfday_count($day),
		);
	}
}

if (!function_exists("rkrk_pt_seat_names")) {
	/** @param list<array<string,mixed>> $seats @param list<int> $locals 1-based */
	function rkrk_pt_seat_names(array $seats, array $locals): string {
		$parts = array();
		foreach ($locals as $local) {
			$idx = (int) $local - 1;
			if (!isset($seats[$idx]) || !is_array($seats[$idx])) {
				continue;
			}
			$person = $seats[$idx]["person"] ?? null;
			$name = is_array($person) ? trim((string) ($person["name"] ?? "")) : "";
			if ($name === "") {
				$name = "frei";
			}
			$parts[] = "P" . (int) ($seats[$idx]["nr"] ?? $local) . " " . $name;
		}
		return implode("\n", $parts);
	}
}

if (!function_exists("rkrk_pt_seat_name_list")) {
	/**
	 * @param list<array<string,mixed>> $seats
	 * @param list<int> $locals
	 * @return list<string>
	 */
	function rkrk_pt_seat_name_list(array $seats, array $locals): array {
		$names = rkrk_pt_seat_names($seats, $locals);
		if ($names === "") {
			return array();
		}
		$lines = preg_split("/\n/", $names);
		return is_array($lines) ? $lines : array();
	}
}

if (!function_exists("rkrk_pt_seat_id_list")) {
	/**
	 * @param list<array<string,mixed>> $seats
	 * @param list<int> $locals
	 * @return list<string>
	 */
	function rkrk_pt_seat_id_list(array $seats, array $locals): array {
		$out = array();
		foreach ($locals as $local) {
			$idx = (int) $local - 1;
			$person = isset($seats[$idx]) && is_array($seats[$idx]) ? ($seats[$idx]["person"] ?? null) : null;
			$out[] = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_cell_html")) {
	function rkrk_pt_cell_html(string $text, bool $forPdf = false): string {
		$lines = preg_split("/\r\n|\n/", $text);
		if (!is_array($lines)) {
			$lines = array($text);
		}
		$safe = array();
		foreach ($lines as $line) {
			$safe[] = rkrk_pt_h($line);
		}
		if ($safe === array()) {
			return "";
		}
		if ($forPdf) {
			return implode("<br>", $safe);
		}
		return "<span class=\"pt-pilot\">" . implode("</span><span class=\"pt-pilot\">", $safe) . "</span>";
	}
}

if (!function_exists("rkrk_pt_rotation_rows")) {
	/**
	 * @param list<array<string,mixed>> $seats
	 * @return list<array{time:string,round:string,material:string,flight:string,theory:string}>
	 */
	function rkrk_pt_station_locals(int $places, int $round, int $station): array {
		$span = $places * 3;
		if ($span < 1) {
			return array();
		}
		$start = (($round + $station) * $places) % $span;
		$out = array();
		for ($i = 0; $i < $places; $i++) {
			$out[] = $start + $i + 1;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_rotation_rows")) {
	/**
	 * @param list<array<string,mixed>> $seats
	 * @param array{briefing?:int,prep?:int,slot?:int,close?:int}|null $timing
	 * @return list<array{time:string,round:string,material:string,flight:string,theory:string}>
	 */
	function rkrk_pt_rotation_rows(array $seats, string $start, ?array $timing = null): array {
		$places = intdiv(count($seats), 3);
		if ($places < 1) {
			$places = 1;
		}
		$base = rkrk_pt_timing(is_array($timing) ? array("timing" => $timing) : array());
		$brief = $base["briefing"];
		$prep = $base["prep"];
		$slot = $base["slot"];
		$close = $base["close"];
		$t0 = rkrk_pt_minutes($start);
		$rows = array(
			array(
				"time" => rkrk_pt_range($t0, 0, $brief),
				"round" => "Start",
				"material" => "Briefing",
				"flight" => "alle",
				"theory" => "—",
			),
			array(
				"time" => rkrk_pt_range($t0, $brief, $brief + $prep),
				"round" => "Start",
				"material" => "Vorbereitung",
				"flight" => "—",
				"theory" => "—",
			),
		);
		$from = $brief + $prep;
		for ($round = 0; $round < 3; $round++) {
			$matLocals = rkrk_pt_station_locals($places, $round, 0);
			$flyLocals = rkrk_pt_station_locals($places, $round, 1);
			$thyLocals = rkrk_pt_station_locals($places, $round, 2);
			$mat = rkrk_pt_seat_name_list($seats, $matLocals);
			$fly = rkrk_pt_seat_name_list($seats, $flyLocals);
			$thy = rkrk_pt_seat_name_list($seats, $thyLocals);
			$rows[] = array(
				"time" => rkrk_pt_range($t0, $from, $from + $slot),
				"round" => (string) ($round + 1),
				"material" => implode("\n", $mat),
				"flight" => implode("\n", $fly),
				"theory" => implode("\n", $thy),
				"ap" => array($mat, $fly, $thy),
				"ap_ids" => array(
					rkrk_pt_seat_id_list($seats, $matLocals),
					rkrk_pt_seat_id_list($seats, $flyLocals),
					rkrk_pt_seat_id_list($seats, $thyLocals),
				),
			);
			$from += $slot;
		}
		$rows[] = array(
			"time" => rkrk_pt_range($t0, $from, $from + $close),
			"round" => "Schluss",
			"material" => "Schlussbriefing",
			"flight" => "alle",
			"theory" => "—",
		);
		$rows[] = array(
			"time" => rkrk_pt_hhmm($t0 + $from + $close),
			"round" => "Schluss",
			"material" => "Blockende",
			"flight" => "—",
			"theory" => "—",
		);
		return $rows;
	}
}

if (!function_exists("rkrk_pt_rotation_table_html")) {
	/**
	 * @param list<array<string,mixed>> $seats
	 */
	function rkrk_pt_rotation_table_html(array $seats, string $start, ?array $timing = null, bool $forPdf = false, array $changedPids = array()): string {
		$rows = rkrk_pt_rotation_rows($seats, $start, $timing);
		$places = intdiv(count($seats), 3);
		if ($places < 1) {
			$places = 1;
		}
		foreach ($rows as $row) {
			if (isset($row["ap"][0]) && is_array($row["ap"][0]) && count($row["ap"][0]) > 0) {
				$places = count($row["ap"][0]);
				break;
			}
		}
		$h = "rkrk_pt_h";
		$labels = array("Material", "Flug", "Theorie");
		$zeitW = 18;
		$roundW = 12;
		$statW = 16;
		$apWs = array();
		$usedW = $zeitW + $roundW + $statW;
		for ($i = 1; $i <= $places; $i++) {
			$w = (int) floor((100 - $zeitW - $roundW - $statW) / $places);
			if ($i === $places) {
				$w = 100 - $usedW;
			}
			$apWs[] = $w;
			$usedW += $w;
		}
		$spanW = $statW;
		foreach ($apWs as $w) {
			$spanW += $w;
		}
		$pdfLn = " style=\"border:none;border-bottom:0.18mm solid #808080;\"";
		if ($forPdf) {
			$html = "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr nobr=\"true\">"
				. "<td width=\"" . $zeitW . "%\"" . $pdfLn . "><b>Zeit</b></td>"
				. "<td width=\"" . $roundW . "%\"" . $pdfLn . "><b>Runde</b></td>"
				. "<td width=\"" . $statW . "%\"" . $pdfLn . "><b></b></td>";
		} else {
			$html = "<table class=\"pt-matrix pt-rot\"><thead><tr><th>Zeit</th><th>Runde</th><th></th>";
		}
		for ($i = 1; $i <= $places; $i++) {
			$label = "Arbeitsplatz " . $i;
			$html .= $forPdf
				? "<td width=\"" . $apWs[$i - 1] . "%\"" . $pdfLn . "><b>" . $h($label) . "</b></td>"
				: "<th>" . $h($label) . "</th>";
		}
		$html .= $forPdf ? "</tr>" : "</tr></thead><tbody>";
		$rowNo = 0;
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$bgOf = static function (int $n) use ($forPdf): string {
				if (!$forPdf) {
					return "";
				}
				return " bgcolor=\"#ffffff\"";
			};
			$wOf = static function (int $pct) use ($forPdf, $pdfLn): string {
				return $forPdf ? " width=\"" . $pct . "%\"" . $pdfLn : "";
			};
			if (!isset($row["ap"]) || !is_array($row["ap"])) {
				$rowNo++;
				$bg = $bgOf($rowNo);
				$text = trim((string) ($row["material"] ?? ""));
				$flight = trim((string) ($row["flight"] ?? ""));
				if ($flight !== "" && $flight !== "—") {
					$text .= " · " . $flight;
				}
				$html .= "<tr nobr=\"true\"><td" . $wOf($zeitW) . $bg . ">" . $h((string) ($row["time"] ?? "")) . "</td><td" . $wOf($roundW) . $bg . ">"
					. $h((string) ($row["round"] ?? "")) . "</td><td" . $wOf($spanW) . $bg . " colspan=\"" . (1 + $places) . "\">"
					. $h($text) . "</td></tr>";
				continue;
			}
			foreach ($labels as $si => $station) {
				$rowNo++;
				$bg = $bgOf($rowNo);
				$pilots = isset($row["ap"][$si]) && is_array($row["ap"][$si]) ? $row["ap"][$si] : array();
				$html .= "<tr" . ($forPdf ? " nobr=\"true\"" : "") . ">";
				if ($forPdf || $si === 0) {
					$span = $forPdf ? "" : " rowspan=\"3\"";
					$html .= "<td" . $wOf($zeitW) . $bg . $span . ">" . $h((string) ($row["time"] ?? "")) . "</td>"
						. "<td" . $wOf($roundW) . $bg . $span . ">" . $h((string) ($row["round"] ?? "")) . "</td>";
				}
				$html .= "<td" . $wOf($statW) . $bg . ">" . $h($station) . "</td>";
				for ($i = 0; $i < $places; $i++) {
					$pilot = trim((string) ($pilots[$i] ?? "—"));
					$pid = isset($row["ap_ids"][$si][$i]) ? trim((string) $row["ap_ids"][$si][$i]) : "";
					$marked = $pid !== "" && isset($changedPids[$pid]);
					$cellBg = ($forPdf && $marked) ? " bgcolor=\"#fff4cc\"" : $bg;
					$cls = "";
					if (!$forPdf && $marked) {
						$cls = " class=\"pt-chg\" title=\"Geänderte Einteilung\"";
					} elseif (!$forPdf && ($pilot === "frei" || strpos($pilot, " frei") !== false)) {
						$cls = " class=\"pt-free\"";
					}
					$html .= "<td" . $wOf((int) ($apWs[$i] ?? 0)) . $cellBg . $cls . ">" . $h($pilot !== "" ? $pilot : "—") . "</td>";
				}
				$html .= "</tr>";
			}
		}
		$html .= $forPdf ? "</table>" : "</tbody></table>";
		return $html;
	}
}

if (!function_exists("rkrk_pt_staff_roles")) {
	/** @return list<array{id:string,label:string}> */
	function rkrk_pt_lead_roles(): array {
		return array(
			array("id" => "tageschef", "label" => "Tages Chef"),
			array("id" => "techn", "label" => "Technischer Experte"),
		);
	}
}

if (!function_exists("rkrk_pt_station_roles")) {
	/** @return list<array{id:string,label:string}> */
	function rkrk_pt_station_roles(): array {
		return array(
			array("id" => "theorie", "label" => "Theorie"),
			array("id" => "material", "label" => "Material"),
			array("id" => "flug", "label" => "Flug"),
		);
	}
}

if (!function_exists("rkrk_pt_staff_roles")) {
	/** @return list<array{id:string,label:string}> */
	function rkrk_pt_staff_roles(): array {
		return array_merge(rkrk_pt_lead_roles(), rkrk_pt_station_roles());
	}
}

if (!function_exists("rkrk_pt_staff_role_ids")) {
	/** @return array<string,true> */
	function rkrk_pt_staff_role_ids(): array {
		$ids = array();
		foreach (rkrk_pt_staff_roles() as $role) {
			$ids[$role["id"]] = true;
		}
		return $ids;
	}
}

if (!function_exists("rkrk_pt_normalize_staff")) {
	/**
	 * @param mixed $raw
	 * @return array<string,array<string,array{roles:array<string,list<string>>,note:string}>>
	 */
	function rkrk_pt_clean_pid(string $pid): string {
		$pid = trim($pid);
		if ($pid !== "" && strlen($pid) <= 80 && preg_match("/^[A-Za-z0-9_.:@-]+$/", $pid) === 1) {
			return $pid;
		}
		return "";
	}
}

if (!function_exists("rkrk_pt_normalize_staff")) {
	/**
	 * Tages Chef und Technischer Experte liegen einmal unter lead.
	 * Theorie, Material und Flug bleiben je Gruppe mit bis zu drei Plätzen.
	 *
	 * @param mixed $raw
	 * @return array<string,array<string,mixed>>
	 */
	function rkrk_pt_normalize_staff($raw): array {
		if (!is_array($raw)) {
			return array();
		}
		$halfIds = array();
		foreach (rkrk_pt_halfday_defs() as $hd) {
			$halfIds[$hd["id"]] = true;
		}
		$stationIds = array();
		foreach (rkrk_pt_station_roles() as $role) {
			$stationIds[$role["id"]] = true;
		}
		$out = array();
		foreach ($raw as $halfId => $lanes) {
			$halfId = (string) $halfId;
			if (!isset($halfIds[$halfId]) || !is_array($lanes)) {
				continue;
			}
			$lead = array("tageschef" => "", "techn" => "");
			if (isset($lanes["lead"]) && is_array($lanes["lead"])) {
				foreach ($lead as $key => $_empty) {
					$lead[$key] = rkrk_pt_clean_pid((string) ($lanes["lead"][$key] ?? ""));
				}
			}
			foreach ($lanes as $lane => $block) {
				if ((string) $lane === "lead" || !is_array($block)) {
					continue;
				}
				$laneNo = (int) $lane;
				if ($laneNo < 1 || $laneNo > 6) {
					continue;
				}
				$roleRaw = isset($block["roles"]) && is_array($block["roles"]) ? $block["roles"] : array();
				if ($lead["tageschef"] === "") {
					$srcLead = isset($roleRaw["tageschef"]) && is_array($roleRaw["tageschef"]) ? $roleRaw["tageschef"] : array();
					$lead["tageschef"] = rkrk_pt_clean_pid((string) ($srcLead[0] ?? ""));
				}
				if ($lead["techn"] === "") {
					$srcLead = isset($roleRaw["techn"]) && is_array($roleRaw["techn"]) ? $roleRaw["techn"] : array();
					$lead["techn"] = rkrk_pt_clean_pid((string) ($srcLead[0] ?? ""));
				}
				$roles = array();
				foreach ($stationIds as $roleId => $_ok) {
					$slots = array("", "", "");
					$src = isset($roleRaw[$roleId]) && is_array($roleRaw[$roleId]) ? array_values($roleRaw[$roleId]) : array();
					for ($i = 0; $i < 3; $i++) {
						$slots[$i] = rkrk_pt_clean_pid((string) ($src[$i] ?? ""));
					}
					$roles[$roleId] = $slots;
				}
				$note = trim((string) ($block["note"] ?? ""));
				$note = str_replace(array("\r\n", "\r"), "\n", $note);
				if (function_exists("mb_substr")) {
					$note = mb_substr($note, 0, 400, "UTF-8");
				} else {
					$note = substr($note, 0, 400);
				}
				$out[$halfId][(string) $laneNo] = array("roles" => $roles, "note" => $note);
			}
			$out[$halfId]["lead"] = $lead;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_staff_block")) {
	/** @return array{roles:array<string,list<string>>,note:string} */
	function rkrk_pt_staff_lead(array $day, string $halfId): array {
		$staff = isset($day["staff"]) && is_array($day["staff"]) ? $day["staff"] : array();
		$lead = isset($staff[$halfId]["lead"]) && is_array($staff[$halfId]["lead"]) ? $staff[$halfId]["lead"] : array();
		return array(
			"tageschef" => rkrk_pt_clean_pid((string) ($lead["tageschef"] ?? "")),
			"techn" => rkrk_pt_clean_pid((string) ($lead["techn"] ?? "")),
		);
	}
}

if (!function_exists("rkrk_pt_staff_block")) {
	/** @return array{roles:array<string,list<string>>,note:string} */
	function rkrk_pt_staff_block(array $day, string $halfId, int $lane): array {
		$emptyRoles = array();
		foreach (rkrk_pt_station_roles() as $role) {
			$emptyRoles[$role["id"]] = array("", "", "");
		}
		$staff = isset($day["staff"]) && is_array($day["staff"]) ? $day["staff"] : array();
		$block = $staff[$halfId][(string) $lane] ?? null;
		if (!is_array($block)) {
			return array("roles" => $emptyRoles, "note" => "");
		}
		$norm = rkrk_pt_normalize_staff(array($halfId => array((string) $lane => $block)));
		if (isset($norm[$halfId][(string) $lane])) {
			return $norm[$halfId][(string) $lane];
		}
		return array("roles" => $emptyRoles, "note" => "");
	}
}

if (!function_exists("rkrk_pt_staff_catalog")) {
	/**
	 * @return array{instr:list<array{key:string,name:string}>,other:list<array{key:string,name:string}>,names:array<string,string>}
	 */
	function rkrk_pt_staff_catalog(): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$instr = array();
		$other = array();
		$names = array();
		foreach ($pilots as $row) {
			if (!is_array($row) || !function_exists("rkrk_tr_pilot_key")) {
				continue;
			}
			$key = rkrk_tr_pilot_key($row);
			if ($key === "" || isset($names[$key])) {
				continue;
			}
			$name = function_exists("rkrk_assess_personel_row_display_name")
				? rkrk_assess_personel_row_display_name($row)
				: $key;
			if ($name === "") {
				continue;
			}
			$names[$key] = $name;
			$item = array(
				"key" => $key,
				"name" => $name,
				"nachname" => trim((string) ($row["nachname"] ?? "")),
				"vorname" => trim((string) ($row["vorname"] ?? "")),
			);
			$isInstr = function_exists("rkrk_assess_personel_row_instruktor_flag")
				&& rkrk_assess_personel_row_instruktor_flag($row);
			if ($isInstr) {
				$instr[] = $item;
			} else {
				$other[] = $item;
			}
		}
		$sort = static function (array $rows): array {
			usort($rows, static function (array $a, array $b): int {
				$c = strnatcasecmp((string) ($a["nachname"] ?? ""), (string) ($b["nachname"] ?? ""));
				return $c !== 0 ? $c : strnatcasecmp((string) ($a["vorname"] ?? ""), (string) ($b["vorname"] ?? ""));
			});
			return $rows;
		};
		return array("instr" => $sort($instr), "other" => $sort($other), "names" => $names);
	}
}

if (!function_exists("rkrk_pt_day_by_share")) {
	/** @return array<string,mixed>|null */
	function rkrk_pt_day_by_share(string $token, ?array $cfg = null): ?array {
		$token = strtolower(trim($token));
		if (preg_match("/^[a-f0-9]{32}$/", $token) !== 1) {
			return null;
		}
		if ($cfg === null) {
			$cfg = rkrk_pt_config_load();
		}
		foreach ((array) ($cfg["days"] ?? array()) as $item) {
			if (!is_array($item)) {
				continue;
			}
			$share = (string) ($item["share"] ?? "");
			if ($share !== "" && hash_equals($share, $token)) {
				return $item;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_pt_new_share_token")) {
	function rkrk_pt_new_share_token(): string {
		try {
			return bin2hex(random_bytes(16));
		} catch (Throwable $e) {
			$b = function_exists("openssl_random_pseudo_bytes") ? openssl_random_pseudo_bytes(16) : false;
			if (is_string($b) && $b !== "") {
				return bin2hex($b);
			}
			return substr(hash("sha256", uniqid("", true)), 0, 32);
		}
	}
}

if (!function_exists("rkrk_pt_share_url")) {
	function rkrk_pt_share_url(string $token): string {
		$https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
			|| ((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
		$host = trim((string) ($_SERVER["HTTP_HOST"] ?? ""));
		$script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
		$dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
		$path = ($dir === "" ? "" : $dir) . "/rkrk_assess_pruefungstag.php?einsicht=" . rawurlencode($token);
		if ($host === "") {
			return $path;
		}
		return ($https ? "https" : "http") . "://" . $host . $path;
	}
}

if (!function_exists("rkrk_pt_day_by_instr")) {
	/** @return array<string,mixed>|null */
	function rkrk_pt_day_by_instr(string $token, ?array $cfg = null): ?array {
		$token = strtolower(trim($token));
		if (preg_match("/^[a-f0-9]{32}$/", $token) !== 1) {
			return null;
		}
		if ($cfg === null) {
			$cfg = rkrk_pt_config_load();
		}
		foreach ((array) ($cfg["days"] ?? array()) as $item) {
			if (!is_array($item)) {
				continue;
			}
			$share = (string) ($item["share_instr"] ?? "");
			if ($share !== "" && hash_equals($share, $token)) {
				return $item;
			}
		}
		return null;
	}
}

if (!function_exists("rkrk_pt_instr_url")) {
	function rkrk_pt_instr_url(string $token): string {
		$https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
			|| ((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
		$host = trim((string) ($_SERVER["HTTP_HOST"] ?? ""));
		$script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
		$dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
		$path = ($dir === "" ? "" : $dir) . "/rkrk_assess_pruefungstag.php?instruktion=" . rawurlencode($token);
		if ($host === "") {
			return $path;
		}
		return ($https ? "https" : "http") . "://" . $host . $path;
	}
}

if (!function_exists("rkrk_pt_person_index")) {
	/** @return array<string,array<string,mixed>> */
	function rkrk_pt_person_index(): array {
		$file = function_exists("rkrk_assess_personel_load_file")
			? rkrk_assess_personel_load_file()
			: array("pilots" => array());
		$pilots = isset($file["pilots"]) && is_array($file["pilots"]) ? $file["pilots"] : array();
		$index = array();
		foreach ($pilots as $row) {
			if (!is_array($row) || !function_exists("rkrk_tr_pilot_keys_of")) {
				continue;
			}
			foreach (rkrk_tr_pilot_keys_of($row) as $k) {
				if (!isset($index[$k])) {
					$index[$k] = $row;
				}
			}
		}
		return $index;
	}
}

if (!function_exists("rkrk_pt_contact_fields")) {
	/**
	 * Eine Zeile: Vorname, Name, Wohnort, Telefon, E-Mail, Drohne, Betreiber-Nr., Piloten-ID.
	 *
	 * @param array<string,mixed>|null $row
	 * @return array{vorname:string,nachname:string,ort:string,telefon:string,email:string,drohne:string,betreiber:string,pilotenid:string}
	 */
	function rkrk_pt_contact_fields(?array $row, string $fallbackId = ""): array {
		$empty = array(
			"vorname" => "",
			"nachname" => $fallbackId,
			"ort" => "",
			"telefon" => "",
			"email" => "",
			"drohne" => "",
			"betreiber" => "",
			"pilotenid" => "",
		);
		if (!is_array($row)) {
			return $empty;
		}
		$plz = trim((string) ($row["plz"] ?? ""));
		$ort = trim((string) ($row["ort"] ?? ""));
		$drone = function_exists("rkrk_tr_drone_label")
			? rkrk_tr_drone_label($row["drohnentyp"] ?? "")
			: trim((string) ($row["drohnentyp"] ?? ""));
		$pilotId = function_exists("rkrk_assess_personel_row_effective_rkrs_id")
			? rkrk_assess_personel_row_effective_rkrs_id($row)
			: trim((string) ($row["rkrs_pilot_id"] ?? ""));
		if ($pilotId === "") {
			$pilotId = trim((string) ($row["pilot_id"] ?? ""));
		}
		return array(
			"vorname" => trim((string) ($row["vorname"] ?? "")),
			"nachname" => trim((string) ($row["nachname"] ?? "")),
			"ort" => trim($plz . " " . $ort),
			"telefon" => trim((string) ($row["telefon"] ?? "")),
			"email" => trim((string) ($row["email"] ?? "")),
			"drohne" => $drone,
			"betreiber" => function_exists("rkrk_assess_personel_row_betreiber_id")
				? rkrk_assess_personel_row_betreiber_id($row)
				: trim((string) ($row["betreiber_id"] ?? "")),
			"pilotenid" => $pilotId,
			"test" => "—",
			"test_code" => "",
		);
	}
}

if (!function_exists("rkrk_pt_seat_contacts")) {
	/**
	 * Kontaktdaten eines Sitzes über die Personen-Id.
	 * Freier Platz: Name «frei», übrige Felder «—».
	 *
	 * @param array<string,mixed>|null $person
	 * @param array<string,array<string,mixed>> $index
	 * @return array{name:string,ort:string,telefon:string,email:string,drohne:string,betreiber:string,pilotenid:string}
	 */
	function rkrk_pt_seat_contacts(?array $person, array $index): array {
		$pid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
		$pname = is_array($person) ? trim((string) ($person["name"] ?? "")) : "";
		$drone = is_array($person) ? trim((string) ($person["drone"] ?? "")) : "";
		$name = $pname !== "" ? $pname : "frei";
		$dash = static function (string $value): string {
			$value = trim($value);
			return $value !== "" ? $value : "—";
		};
		if ($pid === "") {
			return array(
				"name" => $name,
				"ort" => "—",
				"telefon" => "—",
				"email" => "—",
				"drohne" => "—",
				"betreiber" => "—",
				"pilotenid" => "—",
			);
		}
		$row = isset($index[$pid]) && is_array($index[$pid]) ? $index[$pid] : null;
		$fields = rkrk_pt_contact_fields($row, $pid);
		$drohne = trim((string) ($fields["drohne"] ?? ""));
		if ($drohne === "") {
			$drohne = $drone;
		}
		return array(
			"name" => $name,
			"ort" => $dash((string) ($fields["ort"] ?? "")),
			"telefon" => $dash((string) ($fields["telefon"] ?? "")),
			"email" => $dash((string) ($fields["email"] ?? "")),
			"drohne" => $dash($drohne),
			"betreiber" => $dash((string) ($fields["betreiber"] ?? "")),
			"pilotenid" => $dash((string) ($fields["pilotenid"] ?? "")),
		);
	}
}

if (!function_exists("rkrk_pt_test_status_map")) {
	/**
	 * Status der EXAM-Codes auf der gewählten Teilnehmerliste, je Personen-Schlüssel.
	 *
	 * @param array<string,mixed> $day
	 * @return array<string,string>
	 */
	function rkrk_pt_test_status_map(array $day): array {
		static $cache = array();
		$lid = trim((string) ($day["list_id"] ?? ""));
		if (isset($cache[$lid])) {
			return $cache[$lid];
		}
		$map = array();
		if ($lid !== "" && function_exists("rkrk_tr_list_by_id") && function_exists("rkrk_tr_build_rows")) {
			$list = rkrk_tr_list_by_id($lid);
			if (is_array($list)) {
				foreach (rkrk_tr_build_rows($list) as $row) {
					if (!is_array($row)) {
						continue;
					}
					$st = (string) ($row["status"] ?? "");
					foreach (array("id", "pilot_id", "rkrs_id") as $k) {
						$key = trim((string) ($row[$k] ?? ""));
						if ($key !== "") {
							$map[$key] = $st;
						}
					}
				}
			}
		}
		$cache[$lid] = $map;
		return $map;
	}
}

if (!function_exists("rkrk_pt_test_label")) {
	function rkrk_pt_test_label(string $status): string {
		if ($status === "ok") {
			return "erfüllt";
		}
		if ($status === "fail") {
			return "nicht erfüllt";
		}
		if ($status === "open") {
			return "offen";
		}
		if ($status === "empty") {
			return "kein Test";
		}
		return "—";
	}
}

if (!function_exists("rkrk_pt_half_roster")) {
	/**
	 * @param array<string,mixed> $day
	 * @param array<string,mixed> $plan
	 * @return array{instr:list<array<string,string>>,teilnehmer:list<array<string,string>>}
	 */
	function rkrk_pt_half_roster(array $day, array $plan, string $halfId): array {
		$index = rkrk_pt_person_index();
		$tests = rkrk_pt_test_status_map($day);
		$paidSet = array();
		$list = function_exists("rkrk_tr_list_by_id")
			? rkrk_tr_list_by_id((string) ($day["list_id"] ?? ""))
			: null;
		if (is_array($list) && function_exists("rkrk_tr_parse_disabled")) {
			$paidSet = rkrk_tr_parse_disabled($list["paid"] ?? array());
		}
		$paidFor = static function (string $id) use ($index, $paidSet): string {
			$id = trim($id);
			if ($id !== "" && isset($paidSet["id:" . $id])) {
				return "1";
			}
			$row = ($id !== "" && isset($index[$id])) ? $index[$id] : null;
			if (is_array($row) && function_exists("rkrk_tr_pilot_keys_of")) {
				foreach (rkrk_tr_pilot_keys_of($row) as $k) {
					if (isset($paidSet["id:" . $k])) {
						return "1";
					}
				}
			}
			return "0";
		};
		$line = static function (string $id) use ($index, $tests): array {
			$id = trim($id);
			$row = ($id !== "" && isset($index[$id])) ? $index[$id] : null;
			$fields = rkrk_pt_contact_fields(is_array($row) ? $row : null, $id);
			$st = $tests[$id] ?? "";
			if ($st === "" && is_array($row) && function_exists("rkrk_tr_pilot_keys_of")) {
				foreach (rkrk_tr_pilot_keys_of($row) as $k) {
					if (isset($tests[$k])) {
						$st = $tests[$k];
						break;
					}
				}
			}
			$fields["test"] = rkrk_pt_test_label($st);
			$fields["test_code"] = $st;
			return $fields;
		};
		$instr = array();
		$seen = array();
		$push = static function (string $id) use (&$instr, &$seen, $line): void {
			$id = trim($id);
			if ($id === "" || isset($seen[$id])) {
				return;
			}
			$seen[$id] = true;
			$instr[] = $line($id);
		};
		$lead = rkrk_pt_staff_lead($day, $halfId);
		$push((string) ($lead["tageschef"] ?? ""));
		$push((string) ($lead["techn"] ?? ""));
		$blocks = isset($plan["blocks"]) && is_array($plan["blocks"]) ? $plan["blocks"] : array();
		foreach ($blocks as $block) {
			if (!is_array($block) || (string) ($block["half_id"] ?? "") !== $halfId) {
				continue;
			}
			$staff = rkrk_pt_staff_block($day, $halfId, (int) ($block["lane"] ?? 1));
			$roles = isset($staff["roles"]) && is_array($staff["roles"]) ? $staff["roles"] : array();
			foreach (rkrk_pt_station_roles() as $role) {
				$slots = isset($roles[$role["id"]]) && is_array($roles[$role["id"]]) ? $roles[$role["id"]] : array();
				foreach ($slots as $pid) {
					$push((string) $pid);
				}
			}
		}
		$teilnehmer = array();
		$seenPeople = array();
		foreach ($blocks as $block) {
			if (!is_array($block) || (string) ($block["half_id"] ?? "") !== $halfId) {
				continue;
			}
			$seats = isset($block["seats"]) && is_array($block["seats"]) ? $block["seats"] : array();
			foreach ($seats as $seat) {
				if (!is_array($seat)) {
					continue;
				}
				$person = is_array($seat["person"] ?? null) ? $seat["person"] : null;
				$pid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
				if ($pid === "" || isset($seenPeople[$pid])) {
					continue;
				}
				$seenPeople[$pid] = true;
				$one = $line($pid);
				$one["paid"] = $paidFor($pid);
				$teilnehmer[] = $one;
			}
		}
		return array("instr" => $instr, "teilnehmer" => $teilnehmer);
	}
}

if (!function_exists("rkrk_pt_mail_join")) {
	/** Eindeutige Mailadressen, mit Semikolon für Outlook. @param list<array<string,string>> $rows */
	function rkrk_pt_mail_join(array $rows): string {
		$seen = array();
		$out = array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$mail = trim((string) ($row["email"] ?? ""));
			$key = strtolower($mail);
			if ($mail === "" || strpos($mail, "@") === false || isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$out[] = $mail;
		}
		return implode("; ", $out);
	}
}

if (!function_exists("rkrk_pt_mail_copy_button")) {
	function rkrk_pt_mail_copy_button(string $label, string $mails): string {
		$mails = trim($mails);
		$n = $mails === "" ? 0 : (substr_count($mails, ";") + 1);
		$text = $label . ($n > 0 ? " (" . $n . ")" : "");
		if ($n < 1) {
			return "<button type=\"button\" class=\"pt-btn pt-mail\" disabled title=\"Keine Mailadressen\">" . rkrk_pt_h($text) . "</button>";
		}
		return "<button type=\"button\" class=\"pt-btn pt-mail\" data-pt-mails=\"" . rkrk_pt_h($mails) . "\" title=\"" . (int) $n . " Mailadressen kopieren\">" . rkrk_pt_h($text) . "</button>";
	}
}

if (!function_exists("rkrk_pt_mark_icon")) {
	function rkrk_pt_mark_icon(string $code, string $title): string {
		$shapes = array(
			"ok" => '<polyline points="4.2 8.2 6.8 11 11.8 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
			"fail" => '<path d="M5 5l6 6M11 5L5 11" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
			"open" => '<circle cx="8" cy="8" r="5.2" fill="none" stroke="currentColor" stroke-width="1.6"/>',
			"empty" => '<path d="M4 8h8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
			"paid" => '<polyline points="4.2 8.2 6.8 11 11.8 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
			"unpaid" => '<circle cx="8" cy="8" r="5.2" fill="none" stroke="currentColor" stroke-width="1.6"/>',
		);
		$cls = array(
			"ok" => "pt-ok",
			"fail" => "pt-bad",
			"open" => "pt-open",
			"empty" => "pt-miss",
			"paid" => "pt-ok",
			"unpaid" => "pt-miss",
		);
		if (!isset($shapes[$code])) {
			$code = "empty";
		}
		$svg = '<svg class="pt-ico" viewBox="0 0 16 16" aria-hidden="true">' . $shapes[$code] . "</svg>";
		return '<span class="pt-mark ' . $cls[$code] . '" title="' . rkrk_pt_h($title) . '" aria-label="' . rkrk_pt_h($title) . '">' . $svg . "</span>";
	}
}

if (!function_exists("rkrk_pt_roster_table_html")) {
	/** @param list<array<string,string>> $rows */
	function rkrk_pt_roster_table_html(array $rows, bool $forPdf = false, bool $withTest = true, bool $withPaid = false, bool $withIds = true): string {
		if ($forPdf) {
			$withTest = false;
			$withPaid = false;
		}
		$h = "rkrk_pt_h";
		$heads = array("Vorname", "Name", "Wohnort", "Telefon", "E-Mail", "Drohne");
		$keys = array("vorname", "nachname", "ort", "telefon", "email", "drohne");
		if ($withIds) {
			$heads[] = "Betreiber-Nr.";
			$heads[] = "Piloten-ID";
			$keys[] = "betreiber";
			$keys[] = "pilotenid";
		}
		if ($withPaid) {
			$heads[] = "Bezahlt";
			$keys[] = "paid";
		}
		if ($withTest) {
			$heads[] = "Test";
			$keys[] = "test";
		}
		$widths = $withIds
			? ($withTest
				? array(
					"vorname" => "8%",
					"nachname" => "10%",
					"ort" => "14%",
					"telefon" => "11%",
					"email" => "16%",
					"drohne" => "10%",
					"betreiber" => "12%",
					"pilotenid" => "8%",
					"test" => "11%",
				)
				: array(
					"vorname" => "9%",
					"nachname" => "11%",
					"ort" => "16%",
					"telefon" => "12%",
					"email" => "18%",
					"drohne" => "11%",
					"betreiber" => "14%",
					"pilotenid" => "9%",
				))
			: array(
				"vorname" => "14%",
				"nachname" => "16%",
				"ort" => "18%",
				"telefon" => "16%",
				"email" => "22%",
				"drohne" => "14%",
			);
		$cell = $forPdf
			? " style=\"border:none;border-bottom:0.18mm solid #808080;font-size:8pt;white-space:nowrap;\""
			: "";
		$html = "<table class=\"pt-table pt-roster\"" . ($forPdf ? " border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"" : "") . ">";
		if (!$forPdf) {
			$html .= "<thead>";
		}
		$html .= "<tr>";
		foreach ($keys as $i => $key) {
			$w = $forPdf ? " width=\"" . $widths[$key] . "\"" : "";
			$html .= $forPdf
				? "<td" . $w . $cell . "><b>" . $h($heads[$i]) . "</b></td>"
				: "<th" . $cell . ">" . $h($heads[$i]) . "</th>";
		}
		$html .= "</tr>";
		if (!$forPdf) {
			$html .= "</thead><tbody>";
		}
		if ($rows === array()) {
			$html .= "<tr><td colspan=\"" . count($keys) . "\">—</td></tr>";
		}
		$rowNo = 0;
		foreach ($rows as $row) {
			$rowNo++;
			$bg = $forPdf ? " bgcolor=\"#ffffff\"" : "";
			$html .= "<tr>";
			foreach ($keys as $key) {
				$val = trim((string) ($row[$key] ?? ""));
				$cls = "";
				if (!$forPdf && $key === "test") {
					$code = (string) ($row["test_code"] ?? "");
					$inner = rkrk_pt_mark_icon($code !== "" ? $code : "empty", $val !== "" ? $val : "—");
					$cls = ' class="pt-c"';
				} elseif (!$forPdf && $key === "paid") {
					$on = $val === "1";
					$inner = rkrk_pt_mark_icon($on ? "paid" : "unpaid", $on ? "Bezahlt" : "nicht bezahlt");
					$cls = ' class="pt-c"';
				} else {
					$inner = $h($val !== "" ? $val : "—");
				}
				$w = $forPdf ? " width=\"" . ($widths[$key] ?? "") . "\"" : "";
				$html .= "<td" . $w . $bg . $cell . $cls . ">" . $inner . "</td>";
			}
			$html .= "</tr>";
		}
		$html .= $forPdf ? "</table>" : "</tbody></table>";
		return $html;
	}
}

if (!function_exists("rkrk_pt_pdf_name")) {
	function rkrk_pt_pdf_name(array $names, string $id): string {
		$id = trim($id);
		if ($id === "") {
			return "—";
		}
		return (string) ($names[$id] ?? $id);
	}
}

if (!function_exists("rkrk_pt_pdf_html")) {
	/**
	 * @param array<string,mixed> $day
	 * @param array<string,mixed> $plan
	 * @param array<string,string> $names
	 */
	function rkrk_pt_pdf_html(array $day, array $plan, array $names, string $listName, bool $includeStaff = false, string $audience = "admin"): string {
		$h = "rkrk_pt_h";
		$timing = rkrk_pt_timing($day);
		$html = "<style>p, li, ul, ol { text-indent: 0; margin-left: 0; } td, th, p, li { font-size: 10pt; }</style>";
		$html .= "<p style=\"font-size:8pt;\">";
		if ($listName !== "") {
			$html .= "Teilnehmerliste: " . $h($listName) . ". ";
		}
		$html .= "Briefing " . (int) $timing["briefing"] . " min, Vorbereitung " . (int) $timing["prep"]
			. " min, Slot " . (int) $timing["slot"] . " min, Schluss " . (int) $timing["close"] . " min.</p>";
		$place = isset($day["place"]) && is_array($day["place"]) ? $day["place"] : array();
		$ort = trim((string) ($place["ort"] ?? ""));
		$lat = trim((string) ($place["lat"] ?? ""));
		$lng = trim((string) ($place["lng"] ?? ""));
		$maps = trim((string) ($place["maps"] ?? ""));
		if ($maps === "" && $lat !== "" && $lng !== "") {
			$maps = "https://www.google.com/maps?q=" . rawurlencode($lat . "," . $lng);
		}
		$note = trim((string) ($place["note"] ?? ""));
		$swiss = trim((string) ($place["swisstopo"] ?? ""));
		$overlay = trim((string) ($place["overlay"] ?? ""));
		$showMaps = $maps !== "" && rkrk_pt_place_link_visible($place, "maps", $audience);
		$showSwiss = $swiss !== "" && rkrk_pt_place_link_visible($place, "swisstopo", $audience);
		$showOverlay = $overlay !== "" && rkrk_pt_place_link_visible($place, "overlay", $audience);
		$imgFs = rkrk_pt_place_image_fs($day);
		if ($ort !== "" || $maps !== "" || $note !== "" || $swiss !== "" || $overlay !== "" || $imgFs !== "") {
			$left = "";
			if ($ort !== "" || ($lat !== "" && $lng !== "") || $showMaps) {
				$left .= "<b>Ort:</b> " . $h($ort);
				if ($lat !== "" && $lng !== "") {
					$left .= " (" . $h($lat) . ", " . $h($lng) . ")";
				}
				if ($showMaps) {
					$left .= " &nbsp;<a href=\"" . $h($maps) . "\">Standort Google Maps</a>";
				}
				$left .= "<br>";
			}
			if ($note !== "") {
				$left .= "<b>Parkplatz / Treffpunkt:</b> " . nl2br($h($note)) . "<br>";
			}
			$placeLinks = array();
			if ($showSwiss) {
				$placeLinks[] = "<a href=\"" . $h($swiss) . "\">Swisstopo</a>";
			}
			if ($showOverlay) {
				$placeLinks[] = "<a href=\"" . $h($overlay) . "\">Overlay-Editor</a>";
			}
			if ($placeLinks !== array()) {
				$left .= implode(" &nbsp;", $placeLinks);
			}
			if ($left !== "" || $imgFs !== "") {
				$html .= "<!--PT-PLACE-START-->" . $left . "<!--PT-PLACE-END--><!--PT-AFTER-->";
			}
		}
		$chapterHtml = rkrk_pt_chapters_html($day, $includeStaff, true);
		if ($chapterHtml !== "" && strpos($html, "<!--PT-AFTER-->") !== false) {
			$html = str_replace("<!--PT-AFTER-->", "<!--PT-AFTER-->" . $chapterHtml, $html);
		} elseif ($chapterHtml !== "") {
			$html .= $chapterHtml;
		}
		$lastHalf = "";
		$blocks = isset($plan["blocks"]) && is_array($plan["blocks"]) ? $plan["blocks"] : array();
		$gap = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td height=\"8\">&nbsp;</td></tr></table>";
		$pdfLn = " style=\"border:none;border-bottom:0.18mm solid #808080;\"";
		$shade = static function (int $rowNo): string {
			return ($rowNo % 2 === 1) ? " bgcolor=\"#f2f2f2\"" : " bgcolor=\"#ffffff\"";
		};
		$rosterInstrPdf = !empty($day["roster_instr_pdf"]);
		$rosterTeilPdf = !empty($day["roster_teil_pdf"]);
		$pilotIdsPdf = $rosterTeilPdf && ($audience === "instr" || $audience === "admin");
		$seatIndex = $rosterTeilPdf ? rkrk_pt_person_index() : array();
		$instrIdsPdf = $audience !== "teil";
		$appendInstr = static function (string $halfId) use (&$html, $day, $plan, $gap, $rosterInstrPdf, $instrIdsPdf): void {
			if (!$rosterInstrPdf || $halfId === "") {
				return;
			}
			$roster = rkrk_pt_half_roster($day, $plan, $halfId);
			$html .= $gap . "<p style=\"font-size:11pt;\"><b>Instruktoren</b></p>" . rkrk_pt_roster_table_html($roster["instr"], true, false, false, $instrIdsPdf);
		};
		$waitPreview = isset($plan["wait"]) && is_array($plan["wait"]) ? $plan["wait"] : array();
		if ($blocks !== array() || $waitPreview !== array()) {
			$html .= "<!--PT-PLAN--><table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr><td style=\"border-bottom:0.4mm solid #000000;font-size:16pt;\"><b>Zeitplan und Einteilung</b></td></tr></table>";
		}
		foreach ($blocks as $block) {
			if (!is_array($block)) {
				continue;
			}
			$halfId = (string) ($block["half_id"] ?? "");
			$places = max(1, (int) ($block["places"] ?? 3));
			if ($halfId !== $lastHalf) {
				if ($rosterInstrPdf && $lastHalf !== "") {
					$appendInstr($lastHalf);
				}
				$lastHalf = $halfId;
				$html .= $gap . "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr><td style=\"border-bottom:0.4mm solid #000000;font-size:13pt;color:#4d4d4d;\"><b>"
					. $h((string) ($block["half_label"] ?? "")) . "</b></td></tr></table>" . $gap;
			}
			$groups = (int) ($block["groups"] ?? 1);
			if ($groups > 1) {
				$html .= "<p style=\"font-size:11pt;\"><b>" . $h((string) ($block["label"] ?? "")) . "</b></p>";
			}
			$seats = isset($block["seats"]) && is_array($block["seats"]) ? $block["seats"] : array();
			$staff = rkrk_pt_staff_block($day, $halfId, (int) ($block["lane"] ?? 1));
			$roleW = 22;
			$placeWs = array();
			$usedW = $roleW;
			for ($i = 1; $i <= $places; $i++) {
				$w = (int) floor((100 - $roleW) / $places);
				if ($i === $places) {
					$w = 100 - $usedW;
				}
				$placeWs[] = $w;
				$usedW += $w;
			}
			$spanW = 100 - $roleW;
			if ($includeStaff) {
			$html .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr nobr=\"true\">"
				. "<td width=\"" . $roleW . "%\"" . $pdfLn . "><b>Rolle</b></td>";
			for ($i = 1; $i <= $places; $i++) {
				$html .= "<td width=\"" . $placeWs[$i - 1] . "%\"" . $pdfLn . "><b>Platz " . $i . "</b></td>";
			}
			$html .= "</tr>";
			$matrixRow = 0;
			$matrixCell = static function (string $text, int $span, int $width, int $rowNo, string $bg = "") use ($h, $shade, $pdfLn): string {
				$spanAttr = $span > 1 ? " colspan=\"" . $span . "\"" : "";
				$color = $bg !== "" ? $bg : $shade($rowNo);
				return "<td width=\"" . $width . "%\"" . $color . $pdfLn . $spanAttr . ">" . $h($text) . "</td>";
			};
			$markBg = " bgcolor=\"#fff4cc\"";
			if ((int) ($block["lane"] ?? 1) === 1) {
				$lead = rkrk_pt_staff_lead($day, $halfId);
				foreach (array("tageschef" => "Tageschef", "techn" => "Technischer Experte") as $leadId => $leadLabel) {
					$matrixRow++;
					$leadMark = rkrk_pt_mark_is($day, "l:" . $halfId . ":" . $leadId) ? $markBg : "";
					$html .= "<tr nobr=\"true\">" . $matrixCell($leadLabel, 1, $roleW, $matrixRow)
						. $matrixCell(rkrk_pt_pdf_name($names, (string) ($lead[$leadId] ?? "")), $places, $spanW, $matrixRow, $leadMark)
						. "</tr>";
				}
			}
			foreach (rkrk_pt_station_roles() as $role) {
				$matrixRow++;
				$html .= "<tr nobr=\"true\">" . $matrixCell($role["label"], 1, $roleW, $matrixRow);
				$slots = isset($staff["roles"][$role["id"]]) && is_array($staff["roles"][$role["id"]])
					? $staff["roles"][$role["id"]] : array();
				for ($i = 0; $i < $places; $i++) {
					$slotMark = rkrk_pt_mark_is($day, "s:" . $halfId . ":" . (int) ($block["lane"] ?? 1) . ":" . $role["id"] . ":" . $i) ? $markBg : "";
					$html .= $matrixCell(rkrk_pt_pdf_name($names, (string) ($slots[$i] ?? "")), 1, (int) $placeWs[$i], $matrixRow, $slotMark);
				}
				$html .= "</tr>";
			}
			$html .= "</table>";
			$note = trim((string) ($staff["note"] ?? ""));
			if ($note !== "") {
				$html .= "<p>" . nl2br($h($note)) . "</p>";
			}
			}
			if ($rosterTeilPdf) {
				$seatStyle = " style=\"border:none;border-bottom:0.18mm solid #808080;font-size:8pt;white-space:nowrap;\"";
				$seatCols = $pilotIdsPdf
					? array(
						array("w" => 6, "label" => "Pilot"),
						array("w" => 16, "label" => "Name"),
						array("w" => 15, "label" => "Wohnort"),
						array("w" => 12, "label" => "Telefon"),
						array("w" => 17, "label" => "E-Mail"),
						array("w" => 11, "label" => "Drohne"),
						array("w" => 14, "label" => "Betreiber-Nr."),
						array("w" => 9, "label" => "Piloten-ID"),
					)
					: array(
						array("w" => 8, "label" => "Pilot"),
						array("w" => 22, "label" => "Name"),
						array("w" => 18, "label" => "Wohnort"),
						array("w" => 14, "label" => "Telefon"),
						array("w" => 22, "label" => "E-Mail"),
						array("w" => 16, "label" => "Drohne"),
					);
				$html .= $gap . "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr nobr=\"true\">";
				foreach ($seatCols as $col) {
					$html .= "<td width=\"" . (int) $col["w"] . "%\"" . $seatStyle . "><b>" . $h((string) $col["label"]) . "</b></td>";
				}
				$html .= "</tr>";
				$seatNo = 0;
				foreach ($seats as $seat) {
					if (!is_array($seat)) {
						continue;
					}
					$seatNo++;
					$person = is_array($seat["person"] ?? null) ? $seat["person"] : null;
					$seatPid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
					$bg = ($seatPid !== "" && rkrk_pt_mark_is($day, "p:" . $seatPid)) ? " bgcolor=\"#fff4cc\"" : $shade($seatNo);
					$contact = rkrk_pt_seat_contacts(is_array($person) ? $person : null, $seatIndex);
					$vals = array(
						"P" . (int) ($seat["nr"] ?? 0),
						(string) $contact["name"],
						(string) $contact["ort"],
						(string) $contact["telefon"],
						(string) $contact["email"],
						(string) $contact["drohne"],
					);
					if ($pilotIdsPdf) {
						$vals[] = (string) $contact["betreiber"];
						$vals[] = (string) $contact["pilotenid"];
					}
					$html .= "<tr nobr=\"true\">";
					foreach ($vals as $vi => $val) {
						$html .= "<td width=\"" . (int) $seatCols[$vi]["w"] . "%\"" . $bg . $seatStyle . ">" . $h($val) . "</td>";
					}
					$html .= "</tr>";
				}
				$html .= "</table>";
			} else {
				$html .= $gap . "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr nobr=\"true\">"
					. "<td width=\"14%\"" . $pdfLn . "><b>Pilot</b></td>"
					. "<td width=\"46%\"" . $pdfLn . "><b>Name</b></td>"
					. "<td width=\"40%\"" . $pdfLn . "><b>Drohne</b></td></tr>";
				$seatNo = 0;
				foreach ($seats as $seat) {
					if (!is_array($seat)) {
						continue;
					}
					$seatNo++;
					$person = is_array($seat["person"] ?? null) ? $seat["person"] : null;
					$seatPid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
					$bg = ($seatPid !== "" && rkrk_pt_mark_is($day, "p:" . $seatPid)) ? " bgcolor=\"#fff4cc\"" : $shade($seatNo);
					$pname = is_array($person) ? trim((string) ($person["name"] ?? "")) : "";
					$drone = is_array($person) ? trim((string) ($person["drone"] ?? "")) : "";
					$html .= "<tr nobr=\"true\"><td width=\"14%\"" . $bg . $pdfLn . ">P" . (int) ($seat["nr"] ?? 0) . "</td><td width=\"46%\"" . $bg . $pdfLn . ">" . $h($pname !== "" ? $pname : "frei")
						. "</td><td width=\"40%\"" . $bg . $pdfLn . ">" . $h($drone !== "" ? $drone : "—") . "</td></tr>";
				}
				$html .= "</table>";
			}
			$html .= $gap . rkrk_pt_rotation_table_html($seats, (string) ($block["start"] ?? "08:30"), $timing, true, rkrk_pt_changed_pid_set($day));
		}
		if ($rosterInstrPdf && $lastHalf !== "") {
			$appendInstr($lastHalf);
		}
		$wait = isset($plan["wait"]) && is_array($plan["wait"]) ? $plan["wait"] : array();
		if ($wait !== array()) {
			$html .= $gap . "<p style=\"font-size:11pt;\"><b>Warteliste</b></p><table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\"><tr nobr=\"true\">"
				. "<td width=\"14%\"" . $pdfLn . "><b></b></td><td width=\"46%\"" . $pdfLn . "><b>Name</b></td><td width=\"40%\"" . $pdfLn . "><b>Drohne</b></td></tr>";
			$w = 1;
			foreach ($wait as $person) {
				if (!is_array($person)) {
					continue;
				}
				$waitPid = trim((string) ($person["id"] ?? ""));
				$bg = ($waitPid !== "" && rkrk_pt_mark_is($day, "p:" . $waitPid)) ? " bgcolor=\"#fff4cc\"" : $shade($w);
				$html .= "<tr nobr=\"true\"><td width=\"14%\"" . $bg . $pdfLn . ">W" . $w . "</td><td width=\"46%\"" . $bg . $pdfLn . ">" . $h((string) ($person["name"] ?? ""))
					. "</td><td width=\"40%\"" . $bg . $pdfLn . ">" . $h((string) ($person["drone"] ?? "")) . "</td></tr>";
				if ($w === 2) {
					$html .= "<tr nobr=\"true\"><td colspan=\"3\" width=\"100%\" style=\"border:none;border-top:0.45mm solid #111;font-size:8pt;\">Diese zwei bereiten sich vor und rücken nach, wenn jemand ausfällt.</td></tr>";
				}
				$w++;
			}
			$html .= "</table>";
		}
		return $html;
	}
}

if (!function_exists("rkrk_pt_send_pdf")) {
	/**
	 * @param array<string,mixed> $day
	 * @param array<string,mixed> $plan
	 * @param array<string,mixed> $catalog
	 */
	function rkrk_pt_send_pdf(array $day, array $plan, array $catalog, string $listName, bool $includeStaff = false, string $audience = "admin"): void {
		$curlConsts = array(
			"CURLOPT_CONNECTTIMEOUT" => 78,
			"CURLOPT_MAXREDIRS" => 68,
			"CURLOPT_PROTOCOLS" => 181,
			"CURLPROTO_HTTP" => 1,
			"CURLPROTO_HTTPS" => 2,
			"CURLPROTO_FTP" => 4,
			"CURLPROTO_FTPS" => 8,
			"CURLOPT_SSL_VERIFYHOST" => 81,
			"CURLOPT_SSL_VERIFYPEER" => 64,
			"CURLOPT_TIMEOUT" => 13,
			"CURLOPT_USERAGENT" => 10018,
			"CURLOPT_FAILONERROR" => 45,
			"CURLOPT_RETURNTRANSFER" => 19913,
			"CURLOPT_FOLLOWLOCATION" => 52,
			"CURLOPT_URL" => 10002,
		);
		foreach ($curlConsts as $curlName => $curlValue) {
			if (!defined($curlName)) {
				define($curlName, $curlValue);
			}
		}
		if (!function_exists("rkrk_assess_try_load_tcpdf")) {
			$inc = __DIR__ . "/rkrk_assess_report_pdf.inc.php";
			if (is_file($inc)) {
				include_once $inc;
			}
		}
		if (!function_exists("rkrk_assess_try_load_tcpdf") || !rkrk_assess_try_load_tcpdf()) {
			header("Content-Type: text/plain; charset=UTF-8", true, 500);
			echo "PDF-Bibliothek nicht verfügbar.";
			return;
		}
		if ($audience !== "teil" && $audience !== "instr") {
			$audience = "admin";
		}
		$names = isset($catalog["names"]) && is_array($catalog["names"]) ? $catalog["names"] : array();
		$html = rkrk_pt_pdf_html($day, $plan, $names, $listName, $includeStaff, $audience);
		$pdf = new class("P", "mm", "A4", true, "UTF-8", false) extends \TCPDF {
			public function Footer(): void {
				$this->SetY(-11);
				$this->SetFont("helvetica", "", 8);
				$this->SetTextColor(100, 116, 139);
				$this->Cell(0, 6, "Seite " . $this->getAliasNumPage() . " / " . $this->getAliasNbPages(), 0, 0, "C");
				$this->SetTextColor(0, 0, 0);
			}
		};
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(true);
		$pdf->SetMargins(10, 10, 10);
		$pdf->SetFooterMargin(8);
		$pdf->SetAutoPageBreak(true, 16);
		$title = trim((string) ($day["name"] ?? "Prüfungstag"));
		if ($title === "") {
			$title = "Prüfungstag";
		}
		$pdf->SetTitle($title);
		$pdf->AddPage();
		$font = "helvetica";
		if (function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font")) {
			$font = rkrk_assess_report_tcpdf_ensure_unicode_font($pdf);
		}
		$pdf->setCellHeightRatio(1.2);
		$pdf->setListIndentWidth(4);
		$pdf->SetFont($font, "", 10);
		$placeLeft = "";
		if (preg_match("/^(.*)<!--PT-PLACE-START-->(.*)<!--PT-PLACE-END-->(.*)$/s", $html, $placeMatch) === 1) {
			$html = (string) $placeMatch[1] . (string) $placeMatch[3];
			$placeLeft = (string) $placeMatch[2];
		}
		$before = $html;
		$after = "";
		if ($placeLeft !== "" && preg_match("/^(.*)<!--PT-AFTER-->(.*)$/s", $html, $afterMatch) === 1) {
			$before = (string) $afterMatch[1];
			$after = (string) $afterMatch[2];
		}
		$planHtml = "";
		$takePlan = static function (string $chunk) use (&$planHtml): string {
			$mark = "<!--PT-PLAN-->";
			$at = strpos($chunk, $mark);
			if ($at === false) {
				return $chunk;
			}
			$planHtml .= substr($chunk, $at + strlen($mark));
			return substr($chunk, 0, $at);
		};
		$before = $takePlan($before);
		$after = $takePlan($after);
		$margins = $pdf->getMargins();
		$headLeft = (float) ($margins["left"] ?? 10);
		$headTop = (float) ($margins["top"] ?? 10);
		$headRight = (float) ($margins["right"] ?? 10);
		$headW = $pdf->getPageWidth() - $headLeft - $headRight;
		$logoFsHead = function_exists("rkrk_assess_report_logo_fs_path") ? rkrk_assess_report_logo_fs_path() : "";
		$logoWHead = 0.0;
		$logoHHead = 0.0;
		if ($logoFsHead !== "" && is_readable($logoFsHead)) {
			$logoInfo = @getimagesize($logoFsHead);
			$logoHHead = 11.0;
			$logoWHead = $logoHHead;
			if (is_array($logoInfo) && (int) ($logoInfo[0] ?? 0) > 0 && (int) ($logoInfo[1] ?? 0) > 0) {
				$logoWHead = $logoHHead * ((float) $logoInfo[0] / (float) $logoInfo[1]);
			}
			if ($logoWHead > 46.0) {
				$logoHHead = $logoHHead * (46.0 / $logoWHead);
				$logoWHead = 46.0;
			}
		}
		$titleW = $logoWHead > 0 ? max(80.0, $headW - $logoWHead - 6.0) : $headW;
		$pdf->SetXY($headLeft, $headTop);
		$pdf->SetFont($font, "B", 14);
		$pdf->MultiCell($titleW, 6.2, $title, 0, "L", false, 1, $headLeft, $headTop, true, 0, false, true, 0, "T", false);
		$headExam = function_exists("rkrk_pt_exam_label") ? rkrk_pt_exam_label($day) : "";
		if ($headExam !== "") {
			$pdf->SetX($headLeft);
			$pdf->SetFont($font, "", 11);
			$pdf->MultiCell($titleW, 5.2, $headExam, 0, "L", false, 1, $headLeft, $pdf->GetY(), true, 0, false, true, 0, "T", false);
		}
		$titleBottom = $pdf->GetY();
		$logoBottom = $headTop;
		if ($logoWHead > 0) {
			$logoXHead = $pdf->getPageWidth() - $headRight - $logoWHead;
			try {
				$pdf->Image($logoFsHead, $logoXHead, $headTop, $logoWHead, $logoHHead, "", "", "", false, 300, "", false, false, 0, false, false, false);
			} catch (Throwable $e) {
				$logoWHead = 0.0;
			}
			$revHead = function_exists("rkrk_pt_revision_label") ? rkrk_pt_revision_label($day) : "";
			if ($logoWHead > 0 && $revHead !== "") {
				$pdf->SetFont($font, "", 8);
				$pdf->SetTextColor(80, 80, 80);
				$pdf->SetXY($logoXHead, $headTop + $logoHHead + 3.2);
				$pdf->Cell($logoWHead, 4, $revHead, 0, 0, "R", false, "", 0, false, "T", "T");
				$pdf->SetTextColor(0, 0, 0);
				$logoBottom = $headTop + $logoHHead + 3.2 + 4.0;
			} elseif ($logoWHead > 0) {
				$logoBottom = $headTop + $logoHHead;
			}
		}
		$pdf->SetFont($font, "", 10);
		$pdf->SetXY($headLeft, max($titleBottom, $logoBottom) + 1.2);
		$pdf->writeHTML($before, true, false, true, false, "");
		if ($placeLeft !== "") {
			$margins = $pdf->getMargins();
			$leftX = (float) ($margins["left"] ?? 10);
			$contentW = $pdf->getPageWidth() - $leftX - (float) ($margins["right"] ?? 10);
			$imgFs = rkrk_pt_place_image_fs($day);
			$imgW = 0.0;
			$imgH = 0.0;
			if ($imgFs !== "") {
				$info = @getimagesize($imgFs);
				$imgW = min(88.0, $contentW * 0.46);
				if (is_array($info) && (int) ($info[0] ?? 0) > 0 && (int) ($info[1] ?? 0) > 0) {
					$imgH = $imgW * ((float) $info[1] / (float) $info[0]);
					if ($imgH > 62.0) {
						$imgH = 62.0;
						$imgW = $imgH * ((float) $info[0] / (float) $info[1]);
					}
				} else {
					$imgH = 48.0;
				}
			}
			$gap = 4.0;
			$textW = $contentW;
			if ($imgW > 0) {
				$textW = $contentW - $imgW - $gap;
				if ($textW < 72.0) {
					$textW = 72.0;
					$imgW = max(36.0, $contentW - $textW - $gap);
					if (is_array($info) && (int) ($info[0] ?? 0) > 0) {
						$imgH = $imgW * ((float) $info[1] / (float) $info[0]);
					}
				}
			}
			$y = $pdf->GetY();
			$pdf->writeHTMLCell($textW, 0, $leftX, $y, "<div style=\"font-size:8pt;\">" . $placeLeft . "</div>", 0, 1, false, true, "L", true);
			$textBottom = $pdf->GetY();
			$placeNow = isset($day["place"]) && is_array($day["place"]) ? $day["place"] : array();
			$qrItems = array();
			$qrMaps = trim((string) ($placeNow["maps"] ?? ""));
			$qrLat = trim((string) ($placeNow["lat"] ?? ""));
			$qrLng = trim((string) ($placeNow["lng"] ?? ""));
			if ($qrMaps === "" && $qrLat !== "" && $qrLng !== "") {
				$qrMaps = "https://www.google.com/maps?q=" . rawurlencode($qrLat . "," . $qrLng);
			}
			if ($qrMaps !== "" && rkrk_pt_place_link_visible($placeNow, "maps", $audience)) {
				$qrItems[] = array("Standort Google Maps", $qrMaps);
			}
			$qrSwiss = trim((string) ($placeNow["swisstopo"] ?? ""));
			if ($qrSwiss !== "" && rkrk_pt_place_link_visible($placeNow, "swisstopo", $audience)) {
				$qrItems[] = array("Swisstopo", $qrSwiss);
			}
			$qrOverlay = trim((string) ($placeNow["overlay"] ?? ""));
			if ($qrOverlay !== "" && rkrk_pt_place_link_visible($placeNow, "overlay", $audience)) {
				$qrItems[] = array("Overlay-Editor", $qrOverlay);
			}
			$qrShare = trim((string) ($day["share"] ?? ""));
			if ($qrShare !== "" && function_exists("rkrk_pt_share_url")) {
				$qrItems[] = array("Link", rkrk_pt_share_url($qrShare));
			}
			$qrBottom = $textBottom;
			if ($imgFs !== "" && $imgW > 0) {
				try {
					$pdf->Image($imgFs, $leftX + $textW + $gap, $y, $imgW, $imgH > 0 ? $imgH : 0, "", "", "", false, 300, "", false, false, 0, false, false, false);
				} catch (Throwable $e) {
					$imgH = 0.0;
				}
			}
			$pdf->SetY(max($textBottom, $y + $imgH, $qrBottom) + 2);
			$qrCells = array();
			foreach ($qrItems as $qrItem) {
				$qrCells[] = rkrk_pt_pdf_qr_img($qrItem[1], $qrItem[0]);
			}
			$qrCells = array_values(array_filter($qrCells, static function (string $cell): bool {
				return $cell !== "";
			}));
			if ($qrCells !== array()) {
				$pdf->writeHTML("<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\"><tr>" . implode("", $qrCells) . "</tr></table>", true, false, true, false, "");
			}
		}
		if ($after !== "") {
			$pdf->writeHTML($after, true, false, true, false, "");
		}
		if ($planHtml !== "") {
			$pdf->AddPage("L");
			$pdf->SetFont($font, "", 10);
			$pdf->writeHTML($planHtml, true, false, true, false, "");
		}
		$slug = function_exists("rkrk_pt_slug") ? rkrk_pt_slug($title) : "pruefungstag";
		header("Cache-Control: no-store");
		$pdf->Output("Pruefungstag-" . $slug . ".pdf", "I");
	}
}
