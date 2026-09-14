<?php
/**
 * Storyboards: ablaufsteuernde Schicht über Assessment-Templates.
 * Ein Template kann beliebig viele Storyboards haben (z. B. story_board_a/b).
 */
if (!function_exists("rkrk_assess_storyboards_dir")) {
	function rkrk_assess_storyboards_dir(): string {
		if (isset($GLOBALS["rkrkAssessStoryboards"]) && trim((string) $GLOBALS["rkrkAssessStoryboards"]) !== "") {
			$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["rkrkAssessStoryboards"]), "/") . "/";
		} elseif (isset($GLOBALS["QuizLocStoryboards"]) && trim((string) $GLOBALS["QuizLocStoryboards"]) !== "") {
			$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocStoryboards"]), "/") . "/";
		} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$dir = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/storyboards/";
		} else {
			$dir = "";
		}
		if ($dir !== "" && !is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_assess_storyboard_normalize_id")) {
	function rkrk_assess_storyboard_normalize_id(string $id): string {
		$id = trim($id);
		$id = preg_replace('/\.json$/i', "", $id);
		$id = basename(str_replace("\\", "/", $id));
		$id = preg_replace('/[^A-Za-z0-9_-]+/', "_", (string) $id);
		return trim((string) $id, "_");
	}
}

if (!function_exists("rkrk_assess_storyboard_list")) {
	/**
	 * Alle Storyboards, optional gefiltert nach Template-Dateiname.
	 * @return list<array<string,mixed>>
	 */
	function rkrk_assess_storyboard_list(string $templateFile = ""): array {
		$dir = rkrk_assess_storyboards_dir();
		if ($dir === "") {
			return array();
		}
		$templateFile = basename(trim($templateFile));
		$out = array();
		$files = glob($dir . "*.json");
		if (!is_array($files)) {
			return array();
		}
		foreach ($files as $path) {
			$raw = @file_get_contents($path);
			if (!is_string($raw) || $raw === "") {
				continue;
			}
			$data = json_decode($raw, true);
			if (!is_array($data)) {
				continue;
			}
			$id = rkrk_assess_storyboard_normalize_id((string) ($data["id"] ?? pathinfo($path, PATHINFO_FILENAME)));
			if ($id === "") {
				continue;
			}
			$tpl = basename(trim((string) ($data["template"] ?? "")));
			if ($templateFile !== "" && $tpl !== "" && strcasecmp($tpl, $templateFile) !== 0) {
				continue;
			}
			$data["id"] = $id;
			$data["_file"] = basename($path);
			$out[] = $data;
		}
		usort($out, static function ($a, $b) {
			return strcasecmp((string) ($a["title"] ?? $a["id"]), (string) ($b["title"] ?? $b["id"]));
		});
		return $out;
	}
}

if (!function_exists("rkrk_assess_storyboard_load")) {
	/** @return array<string,mixed>|null */
	function rkrk_assess_storyboard_load(string $id): ?array {
		$id = rkrk_assess_storyboard_normalize_id($id);
		if ($id === "") {
			return null;
		}
		$dir = rkrk_assess_storyboards_dir();
		if ($dir === "") {
			return null;
		}
		$path = $dir . $id . ".json";
		if (!is_readable($path)) {
			return null;
		}
		$raw = @file_get_contents($path);
		if (!is_string($raw) || $raw === "") {
			return null;
		}
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return null;
		}
		$data["id"] = $id;
		$data["_file"] = basename($path);
		return $data;
	}
}

if (!function_exists("rkrk_assess_storyboard_save")) {
	/** @param array<string,mixed> $data */
	function rkrk_assess_storyboard_save(array $data): bool {
		$id = rkrk_assess_storyboard_normalize_id((string) ($data["id"] ?? ""));
		if ($id === "") {
			return false;
		}
		$dir = rkrk_assess_storyboards_dir();
		if ($dir === "") {
			return false;
		}
		$data["id"] = $id;
		unset($data["_file"]);
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json) || $json === "") {
			return false;
		}
		return @file_put_contents($dir . $id . ".json", $json . "\n", LOCK_EX) !== false;
	}
}

if (!function_exists("rkrk_assess_storyboard_field_code")) {
	/** Extrahiert Punktcode aus Label oder Storyboard-Ref (A.1, C.1*# …). */
	function rkrk_assess_storyboard_field_code(string $labelOrRef): string {
		$s = trim($labelOrRef);
		if ($s === "") {
			return "";
		}
		if (function_exists("rkrk_extract_point_code")) {
			$code = trim((string) rkrk_extract_point_code($s));
			if ($code !== "") {
				return strtoupper($code);
			}
		}
		if (preg_match('/^([A-Za-z]\.?\d+[A-Za-z]?)/u', $s, $m)) {
			return strtoupper(str_replace(" ", "", $m[1]));
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_storyboard_normalize_code")) {
	function rkrk_assess_storyboard_normalize_code(string $code): string {
		$code = strtoupper(trim($code));
		$code = preg_replace('/[^A-Z0-9.]/', "", $code);
		return (string) $code;
	}
}

if (!function_exists("rkrk_assess_storyboard_cue_is_stoer")) {
	/** Cue beginnt mit Inject:/Störung: oder Punkt ist Inject-Ziel. */
	function rkrk_assess_storyboard_cue_is_stoer(string $cue): bool {
		return preg_match('/^(Inject|Störung|Stoerung)\s*:/iu', trim($cue)) === 1;
	}
}

if (!function_exists("rkrk_assess_storyboard_cue_strip_stoer_prefix")) {
	/** Entfernt „Inject:“ / „Störung:“-Präfix aus dem Cue-Text. */
	function rkrk_assess_storyboard_cue_strip_stoer_prefix(string $cue): string {
		$cue = trim($cue);
		$out = preg_replace('/^(Inject|Störung|Stoerung)\s*:\s*/iu', "", $cue);
		return trim(is_string($out) ? $out : $cue);
	}
}


if (!function_exists("rkrk_assess_storyboard_point_title_from_label")) {
	function rkrk_assess_storyboard_point_title_from_label(string $label): string {
		$label = trim($label);
		if ($label === "") {
			return "";
		}
		// C.2# Titel / D.1* Titel / C.1*# Titel
		if (preg_match('/^[A-Za-z]\.?\d+[A-Za-z]?[*#]*\s+(.+)$/u', $label, $m)) {
			return trim((string) $m[1]);
		}
		return $label;
	}
}

if (!function_exists("rkrk_assess_storyboard_point_titles_map")) {
	/** @return array<string,string> normalisierter Code → Titel */
	function rkrk_assess_storyboard_point_titles_map(string $templateFile): array {
		$out = array();
		$templateFile = basename(trim($templateFile));
		if ($templateFile === "" || !function_exists("rkrk_assess_storyboard_template_point_options")) {
			return $out;
		}
		foreach (rkrk_assess_storyboard_template_point_options($templateFile) as $opt) {
			$code = rkrk_assess_storyboard_normalize_code((string) ($opt["code"] ?? ""));
			$lab = trim((string) ($opt["label"] ?? ""));
			if ($code === "" || $lab === "") {
				continue;
			}
			$title = rkrk_assess_storyboard_point_title_from_label($lab);
			if ($title !== "") {
				$out[$code] = $title;
			}
		}
		return $out;
	}
}
if (!function_exists("rkrk_assess_storyboard_point_title_from_label")) {
	/** Template-Label → Anzeigetitel ohne Code/*/#. */
	function rkrk_assess_storyboard_point_title_from_label(string $label): string {
		$label = trim($label);
		if ($label === "") {
			return "";
		}
		// C.2# Titel / D.1* Titel / C.1*# Titel
		if (preg_match('/^[A-Za-z]\.?\d+[A-Za-z]?[*#]*\s+(.+)$/u', $label, $m)) {
			return trim((string) $m[1]);
		}
		return $label;
	}
}

if (!function_exists("rkrk_assess_storyboard_point_titles_map")) {
	/**
	 * Normalisierter Punktcode → Punkttitel aus Template.
	 * @return array<string,string>
	 */
	function rkrk_assess_storyboard_point_titles_map(string $templateFile): array {
		$out = array();
		$templateFile = basename(trim($templateFile));
		if ($templateFile === "" || !function_exists("rkrk_assess_storyboard_template_point_options")) {
			return $out;
		}
		foreach (rkrk_assess_storyboard_template_point_options($templateFile) as $opt) {
			$code = rkrk_assess_storyboard_normalize_code((string) ($opt["code"] ?? ""));
			$lab = trim((string) ($opt["label"] ?? ""));
			if ($code === "" || $lab === "") {
				continue;
			}
			$title = rkrk_assess_storyboard_point_title_from_label($lab);
			if ($title !== "") {
				$out[$code] = $title;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_assess_storyboard_codes_match")) {
	function rkrk_assess_storyboard_codes_match(string $a, string $b): bool {
		$a = rkrk_assess_storyboard_normalize_code($a);
		$b = rkrk_assess_storyboard_normalize_code($b);
		if ($a === "" || $b === "") {
			return false;
		}
		return $a === $b;
	}
}

if (!function_exists("rkrk_assess_storyboard_find_label")) {
	/**
	 * Findet Template-Feldlabel zu Storyboard-Feldref (z. B. "A.1" → voller Key).
	 * @param array<string,mixed> $sections
	 */
	function rkrk_assess_storyboard_find_label(array $sections, string $fieldRef): string {
		$want = rkrk_assess_storyboard_field_code($fieldRef);
		if ($want === "") {
			$fieldRef = trim($fieldRef);
			foreach ($sections as $fields) {
				if (!is_array($fields)) {
					continue;
				}
				if (isset($fields[$fieldRef])) {
					return $fieldRef;
				}
			}
			return "";
		}
		foreach ($sections as $fields) {
			if (!is_array($fields)) {
				continue;
			}
			foreach ($fields as $label => $_meta) {
				if (!is_string($label)) {
					continue;
				}
				if (preg_match('/\bBemerkung\b/ui', $label)) {
					continue;
				}
				$code = rkrk_assess_storyboard_field_code($label);
				if (rkrk_assess_storyboard_codes_match($code, $want)) {
					return $label;
				}
			}
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_storyboard_is_score_field")) {
	/** @param mixed $meta */
	function rkrk_assess_storyboard_is_score_field($meta): bool {
		$type = is_array($meta) ? (string) ($meta["type"] ?? "") : (string) $meta;
		return (stripos($type, "radio:") === 0);
	}
}

if (!function_exists("rkrk_assess_storyboard_collect_active_codes")) {
	/**
	 * @param array<string,mixed> $story
	 * @return list<string>
	 */
	function rkrk_assess_storyboard_collect_active_codes(array $story): array {
		$codes = array();
		$add = static function (string $ref) use (&$codes): void {
			$c = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($ref));
			if ($c !== "" && !in_array($c, $codes, true)) {
				$codes[] = $c;
			}
		};
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		foreach ($steps as $step) {
			if (!is_array($step)) {
				continue;
			}
			foreach (array("items", "continuous") as $key) {
				$list = isset($step[$key]) && is_array($step[$key]) ? $step[$key] : array();
				foreach ($list as $item) {
					if (is_string($item)) {
						$add($item);
					} elseif (is_array($item)) {
						$add((string) ($item["field"] ?? ""));
						$inject = isset($item["inject"]) && is_array($item["inject"]) ? $item["inject"] : array();
						foreach ($inject as $inj) {
							$add((string) $inj);
						}
					}
				}
			}
		}
		return $codes;
	}
}

if (!function_exists("rkrk_assess_storyboard_nb_option_value")) {
	/**
	 * Findet gespeicherten Radio-Wert für n.b.(n) Option.
	 * @param mixed $meta
	 */
	function rkrk_assess_storyboard_nb_option_value($meta): string {
		$opts = "";
		if (is_array($meta)) {
			$opts = trim((string) ($meta["options"] ?? ""));
			$type = (string) ($meta["type"] ?? "");
			if ($opts === "" && stripos($type, "radio:") === 0) {
				$opts = substr($type, 6);
			}
		} elseif (is_string($meta) && stripos($meta, "radio:") === 0) {
			$opts = substr($meta, 6);
		}
		if ($opts === "") {
			return "n.b.(n)";
		}
		foreach (explode("|", $opts) as $o) {
			$o = trim($o);
			if ($o === "") {
				continue;
			}
			if (preg_match('/\(n\)\s*$/i', $o) || preg_match('/^n\.?b\.?/i', $o)) {
				return $o;
			}
		}
		return "n.b.(n)";
	}
}

if (!function_exists("rkrk_assess_storyboard_apply_nb_to_saved")) {
	/**
	 * Setzt alle nicht aktiven Score-Felder auf n.b.(n).
	 * @param array<string,mixed> $sections template sections
	 * @param array<string,mixed> $saved by-ref fields map
	 * @param array<string,mixed> $story
	 */
	function rkrk_assess_storyboard_apply_nb_to_saved(array $sections, array &$saved, array $story): void {
		$active = rkrk_assess_storyboard_collect_active_codes($story);
		$nbExplicit = array();
		$nbList = isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? $story["nb_fields"] : array();
		foreach ($nbList as $ref) {
			$c = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) $ref));
			if ($c !== "") {
				$nbExplicit[$c] = true;
			}
		}
		foreach ($sections as $secName => $fields) {
			if (!is_array($fields)) {
				continue;
			}
			if (!isset($saved[$secName]) || !is_array($saved[$secName])) {
				$saved[$secName] = array();
			}
			foreach ($fields as $label => $meta) {
				if (!is_string($label) || preg_match('/\bBemerkung\b/ui', $label)) {
					continue;
				}
				if (!rkrk_assess_storyboard_is_score_field($meta)) {
					continue;
				}
				$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($label));
				if ($code === "") {
					continue;
				}
				$forceNb = isset($nbExplicit[$code]) || !in_array($code, $active, true);
				if (!$forceNb) {
					continue;
				}
				$saved[$secName][$label] = rkrk_assess_storyboard_nb_option_value($meta);
			}
		}
	}
}

if (!function_exists("rkrk_assess_storyboard_label_is_active")) {
	/**
	 * @param array<string,mixed> $story
	 */
	function rkrk_assess_storyboard_label_is_active(array $story, string $label): bool {
		$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($label));
		if ($code === "") {
			return true;
		}
		$nbList = isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? $story["nb_fields"] : array();
		foreach ($nbList as $ref) {
			if (rkrk_assess_storyboard_codes_match($code, rkrk_assess_storyboard_field_code((string) $ref))) {
				return false;
			}
		}
		$active = rkrk_assess_storyboard_collect_active_codes($story);
		return in_array($code, $active, true);
	}
}

if (!function_exists("rkrk_assess_storyboard_cue_for_label")) {
	/**
	 * @param array<string,mixed> $story
	 */
	function rkrk_assess_storyboard_cue_for_label(array $story, string $label): string {
		$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($label));
		if ($code === "") {
			return "";
		}
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		foreach ($steps as $step) {
			if (!is_array($step)) {
				continue;
			}
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}
				$fc = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) ($item["field"] ?? "")));
				if ($fc === $code) {
					return trim((string) ($item["cue"] ?? ""));
				}
			}
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_storyboard_index_score_fields")) {
	/**
	 * Index Score- und Bemerkungsfelder nach Punktcode.
	 * @param array<string,mixed> $sections
	 * @return array{by_code:array<string,array{sec:string,label:string,meta:mixed}>,remark_by_code:array<string,array{sec:string,label:string,meta:mixed}>,point_sections:list<string>}
	 */
	function rkrk_assess_storyboard_index_score_fields(array $sections): array {
		$byCode = array();
		$remarkByCode = array();
		$pointSections = array();
		foreach ($sections as $secName => $fields) {
			if (!is_array($fields)) {
				continue;
			}
			$secHasPoint = false;
			foreach ($fields as $label => $meta) {
				if (!is_string($label)) {
					continue;
				}
				$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($label));
				if ($code === "") {
					continue;
				}
				if (preg_match('/\bBemerkung\b/ui', $label)) {
					$remarkByCode[$code] = array("sec" => (string) $secName, "label" => $label, "meta" => $meta);
					$secHasPoint = true;
					continue;
				}
				if (!rkrk_assess_storyboard_is_score_field($meta)) {
					continue;
				}
				$byCode[$code] = array("sec" => (string) $secName, "label" => $label, "meta" => $meta);
				$secHasPoint = true;
			}
			if ($secHasPoint) {
				$pointSections[] = (string) $secName;
			}
		}
		return array(
			"by_code" => $byCode,
			"remark_by_code" => $remarkByCode,
			"point_sections" => $pointSections,
		);
	}
}

if (!function_exists("rkrk_assess_storyboard_chrono_plan")) {
	/**
	 * Chronologischer Render-Plan: Phasen mit Cues; n.b. separat.
	 * @param array<string,mixed> $story
	 * @param array<string,mixed> $sections
	 * @return array<string,mixed>
	 */
	function rkrk_assess_storyboard_chrono_plan(array $story, array $sections): array {
		$idx = rkrk_assess_storyboard_index_score_fields($sections);
		$byCode = $idx["by_code"];
		$remarkByCode = $idx["remark_by_code"];
		$pointSecSet = array_fill_keys($idx["point_sections"], true);

		$active = rkrk_assess_storyboard_collect_active_codes($story);
		$nbExplicit = array();
		foreach ((isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? $story["nb_fields"] : array()) as $ref) {
			$c = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) $ref));
			if ($c !== "") {
				$nbExplicit[$c] = true;
			}
		}

		$usedCodes = array();
		$phases = array();
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		foreach ($steps as $si => $step) {
			if (!is_array($step)) {
				continue;
			}
			$entries = array();
			$seenInPhase = array();

			$addEntry = static function (string $code, string $cue, string $info, bool $continuous) use (&$entries, &$seenInPhase, &$usedCodes, $byCode, $remarkByCode): void {
				$code = rkrk_assess_storyboard_normalize_code($code);
				if ($code === "" || !isset($byCode[$code])) {
					return;
				}
				if (isset($seenInPhase[$code])) {
					foreach ($entries as &$e) {
						if (($e["code"] ?? "") !== $code || ($e["kind"] ?? "") !== "score") {
							continue;
						}
						if ($cue !== "" && strpos((string) ($e["cue"] ?? ""), $cue) === false) {
							$e["cue"] = trim((string) ($e["cue"] ?? "") . " · " . $cue, " ·");
						}
						if ($info !== "" && strpos((string) ($e["info"] ?? ""), $info) === false) {
							$e["info"] = trim((string) ($e["info"] ?? "") . "\n" . $info);
						}
					}
					unset($e);
					return;
				}
				$seenInPhase[$code] = true;
				$usedCodes[$code] = true;
				$hit = $byCode[$code];
				$entries[] = array(
					"code" => $code,
					"sec" => $hit["sec"],
					"label" => $hit["label"],
					"meta" => $hit["meta"],
					"cue" => $cue,
					"info" => $info,
					"continuous" => $continuous,
					"kind" => "score",
				);
				if (isset($remarkByCode[$code])) {
					$rm = $remarkByCode[$code];
					$entries[] = array(
						"code" => $code,
						"sec" => $rm["sec"],
						"label" => $rm["label"],
						"meta" => $rm["meta"],
						"cue" => "",
						"info" => "",
						"continuous" => $continuous,
						"kind" => "remark",
					);
				}
			};

			$clist = isset($step["continuous"]) && is_array($step["continuous"]) ? $step["continuous"] : array();
			foreach ($clist as $cref) {
				$addEntry(rkrk_assess_storyboard_field_code((string) $cref), "Durchgehend bewerteter Kontrollpunkt", "", true);
			}

			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			usort($items, static function ($a, $b) {
				$oa = is_array($a) && isset($a["order"]) ? (float) $a["order"] : 0.0;
				$ob = is_array($b) && isset($b["order"]) ? (float) $b["order"] : 0.0;
				if ($oa === $ob) {
					return 0;
				}
				return $oa < $ob ? -1 : 1;
			});
			foreach ($items as $item) {
				$ref = is_array($item) ? (string) ($item["field"] ?? "") : (string) $item;
				$cue = is_array($item) ? trim((string) ($item["cue"] ?? "")) : "";
				$info = is_array($item) ? trim((string) ($item["info"] ?? "")) : "";
				$addEntry(rkrk_assess_storyboard_field_code($ref), $cue, $info, false);
				if (is_array($item) && isset($item["inject"]) && is_array($item["inject"])) {
					foreach ($item["inject"] as $inj) {
						$ic = rkrk_assess_storyboard_field_code((string) $inj);
						if ($ic !== "" && !isset($seenInPhase[rkrk_assess_storyboard_normalize_code($ic)])) {
							$addEntry($ic, "Störung", "", false);
						}
					}
				}
			}

			$phases[] = array(
				"id" => (string) ($step["id"] ?? ("step_" . $si)),
				"title" => (string) ($step["title"] ?? ("Schritt " . ($si + 1))),
				"prep" => trim((string) ($step["prep"] ?? "")),
				"mode" => (string) ($step["mode"] ?? "score"),
				"duration_min" => (int) ($step["duration_min"] ?? 0),
				"entries" => $entries,
			);
		}

		// Aktive Codes ohne Step-Item (nur nb_fields-Logik) — ans Ende der letzten Score-Phase
		foreach ($active as $code) {
			if (isset($usedCodes[$code]) || !isset($byCode[$code])) {
				continue;
			}
			if (isset($nbExplicit[$code])) {
				continue;
			}
			if ($phases === array()) {
				$phases[] = array(
					"id" => "phase_extra",
					"title" => "Weitere Kontrollpunkte",
					"prep" => "",
					"mode" => "score",
					"duration_min" => 0,
					"entries" => array(),
				);
			}
			$last = count($phases) - 1;
			$hit = $byCode[$code];
			$phases[$last]["entries"][] = array(
				"code" => $code,
				"sec" => $hit["sec"],
				"label" => $hit["label"],
				"meta" => $hit["meta"],
				"cue" => "",
				"info" => "",
				"continuous" => false,
				"kind" => "score",
			);
			$usedCodes[$code] = true;
			if (isset($remarkByCode[$code])) {
				$rm = $remarkByCode[$code];
				$phases[$last]["entries"][] = array(
					"code" => $code,
					"sec" => $rm["sec"],
					"label" => $rm["label"],
					"meta" => $rm["meta"],
					"cue" => "",
					"info" => "",
					"continuous" => false,
					"kind" => "remark",
				);
			}
		}

		$nbEntries = array();
		foreach ($byCode as $code => $hit) {
			$forceNb = isset($nbExplicit[$code]) || !in_array($code, $active, true);
			if (!$forceNb) {
				continue;
			}
			$nbEntries[] = array(
				"code" => $code,
				"sec" => $hit["sec"],
				"label" => $hit["label"],
				"meta" => $hit["meta"],
				"cue" => "",
				"info" => "",
				"continuous" => false,
				"kind" => "score",
				"is_nb" => true,
			);
			if (isset($remarkByCode[$code])) {
				$rm = $remarkByCode[$code];
				$nbEntries[] = array(
					"code" => $code,
					"sec" => $rm["sec"],
					"label" => $rm["label"],
					"meta" => $rm["meta"],
					"cue" => "",
					"info" => "",
					"continuous" => false,
					"kind" => "remark",
					"is_nb" => true,
				);
			}
		}

		// Abschnitts-Summen / andere Nicht-Score-Felder aus Punkt-Sektionen erhalten
		// (z. B. R028 „A Summe Abschnitt“) — sonst brechen Punkte-pro-Abschnitt, TOTAL und 80%-Schwelle.
		$residuals = array();
		foreach ($sections as $secName => $fields) {
			if (!isset($pointSecSet[$secName]) || !is_array($fields)) {
				continue;
			}
			$keep = array();
			foreach ($fields as $label => $meta) {
				if (!is_string($label)) {
					continue;
				}
				$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($label));
				if ($code !== "" && isset($byCode[$code]) && rkrk_assess_storyboard_is_score_field($meta)) {
					continue;
				}
				if ($code !== "" && isset($remarkByCode[$code]) && preg_match('/\bBemerkung\b/ui', $label)) {
					continue;
				}
				if (preg_match('/\bNicht erfüllte Punkte$/u', $label)) {
					continue;
				}
				$keep[$label] = $meta;
			}
			if ($keep !== array()) {
				$residuals[(string) $secName] = $keep;
			}
		}

		$leading = array();
		$trailing = array();
		$seenPoint = false;
		foreach ($sections as $secName => $fields) {
			if (isset($pointSecSet[$secName])) {
				$seenPoint = true;
				continue;
			}
			if (!$seenPoint) {
				$leading[$secName] = $fields;
			} else {
				$trailing[$secName] = $fields;
			}
		}

		return array(
			"leading" => $leading,
			"phases" => $phases,
			"trailing" => $trailing,
			"residuals" => $residuals,
			"nb_entries" => $nbEntries,
			"point_sections" => $idx["point_sections"],
		);
	}
}

if (!function_exists("rkrk_assess_storyboard_overview_html")) {
	/**
	 * Storyboard-Übersicht (Phasen/Fragen/Zusatzinfo) — wie Druckansicht, ohne Dokument-Chrome.
	 * @param array<string,mixed> $story
	 * @param array<string,mixed> $opts class_prefix|h_tag|include_meta|include_intro|include_prep
	 */
	function rkrk_assess_storyboard_overview_html(array $story, array $opts = array()): string {
		$p = isset($opts["class_prefix"]) ? (string) $opts["class_prefix"] : "rkrk-sb-ov";
		$h = isset($opts["h_tag"]) ? strtolower(trim((string) $opts["h_tag"])) : "h3";
		if (!in_array($h, array("h2", "h3", "h4"), true)) {
			$h = "h3";
		}
		$esc = static function ($s): string {
			return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
		};
		$html = "";
		$title = trim((string) ($story["title"] ?? $story["id"] ?? "Storyboard"));
		$id = trim((string) ($story["id"] ?? ""));
		$ver = trim((string) ($story["version"] ?? ""));
		$dur = (int) ($story["duration_min"] ?? 0);

		$html .= "<div class=\"" . $esc($p) . "\">";
		$html .= "<" . $h . " class=\"" . $esc($p) . "__title\">" . $esc($title) . "</" . $h . ">";

		if (!empty($opts["include_meta"])) {
			$html .= "<div class=\"" . $esc($p) . "__meta\">";
			$bits = array();
			if ($id !== "") {
				$bits[] = "ID: <strong>" . $esc($id) . "</strong>";
			}
			if ($ver !== "") {
				$bits[] = "Version " . $esc($ver);
			}
			if ($dur > 0) {
				$bits[] = $dur . " Min";
			}
			$html .= implode(" · ", $bits);
			$tpl = trim((string) ($story["template"] ?? ""));
			if ($tpl !== "") {
				$html .= ($bits !== array() ? "<br>" : "") . "Template: " . $esc($tpl);
			}
			$html .= "</div>";
		}

		if (!empty($opts["include_intro"])) {
			$intro = trim((string) ($story["intro"] ?? ""));
			$desc = trim((string) ($story["description"] ?? ""));
			if ($desc !== "") {
				$html .= "<p class=\"" . $esc($p) . "__desc\">" . nl2br($esc($desc)) . "</p>";
			}
			if ($intro !== "") {
				$html .= "<p class=\"" . $esc($p) . "__intro\"><strong>Ausgangssituation:</strong> " . nl2br($esc($intro)) . "</p>";
			}
		}

		if (!empty($opts["include_prep"])) {
			$fieldPrep = isset($story["field_prep"]) && is_array($story["field_prep"]) ? $story["field_prep"] : array();
			if ($fieldPrep !== array()) {
				$html .= "<div class=\"" . $esc($p) . "__prep\"><strong>Feld-Vorbereitung</strong><ul>";
				foreach ($fieldPrep as $prepItem) {
					$html .= "<li>" . $esc((string) $prepItem) . "</li>";
				}
				$html .= "</ul></div>";
			}
		}

		$pointTitles = function_exists("rkrk_assess_storyboard_point_titles_map")
			? rkrk_assess_storyboard_point_titles_map((string) ($story["template"] ?? ""))
			: array();

		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		foreach ($steps as $step) {
			if (!is_array($step)) {
				continue;
			}
			$st = trim((string) ($step["title"] ?? ""));
			$sd = (int) ($step["duration_min"] ?? 0);
			$html .= "<div class=\"" . $esc($p) . "__phase\">";
			$html .= "<div class=\"" . $esc($p) . "__phase-title\"><strong>" . $esc($st !== "" ? $st : "Phase") . "</strong>";
			if ($sd > 0) {
				$html .= " <span class=\"" . $esc($p) . "__dur\">(" . $sd . " Min)</span>";
			}
			$html .= "</div>";
			$prep = trim((string) ($step["prep"] ?? ""));
			if ($prep !== "") {
				$html .= "<p class=\"" . $esc($p) . "__phase-prep\"><em>" . $esc($prep) . "</em></p>";
			}
			$cont = isset($step["continuous"]) && is_array($step["continuous"]) ? $step["continuous"] : array();
			if ($cont !== array()) {
				$html .= "<p class=\"" . $esc($p) . "__cont\"><strong>Durchgehend:</strong> " . $esc(implode(", ", array_map("strval", $cont))) . "</p>";
			}
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			if ($items !== array()) {
				$html .= "<ol class=\"" . $esc($p) . "__items\">";
				foreach ($items as $item) {
					if (!is_array($item)) {
						$html .= "<li>" . $esc((string) $item) . "</li>";
						continue;
					}
					$field = trim((string) ($item["field"] ?? ""));
					$fieldCode = function_exists("rkrk_assess_storyboard_normalize_code")
						? rkrk_assess_storyboard_normalize_code(
							function_exists("rkrk_assess_storyboard_field_code")
								? (rkrk_assess_storyboard_field_code($field) ?: $field)
								: $field
						)
						: strtoupper($field);
					$pointTitle = ($fieldCode !== "" && isset($pointTitles[$fieldCode]))
						? (string) $pointTitles[$fieldCode]
						: "";
					$cue = trim((string) ($item["cue"] ?? ""));
					$info = trim((string) ($item["info"] ?? ""));
					$html .= "<li><strong>" . $esc($field !== "" ? $field : $fieldCode) . "</strong>";
					if ($pointTitle !== "") {
						$html .= " " . $esc($pointTitle);
					}
					if ($cue !== "") {
						$html .= "<div class=\"" . $esc($p) . "__cue\">" . $esc($cue) . "</div>";
					}
					if ($info !== "") {
						$html .= "<div class=\"" . $esc($p) . "__info\"><em>Zusatzinfo:</em> " . nl2br($esc($info)) . "</div>";
					}
					if (isset($item["inject"]) && is_array($item["inject"]) && $item["inject"] !== array()) {
						$html .= "<div class=\"" . $esc($p) . "__inject\"><em>Störung:</em> " . $esc(implode(", ", array_map("strval", $item["inject"]))) . "</div>";
					}
					$html .= "</li>";
				}
				$html .= "</ol>";
			}
			$html .= "</div>";
		}

		$nb = isset($story["nb_fields"]) && is_array($story["nb_fields"]) ? $story["nb_fields"] : array();
		if ($nb !== array()) {
			$html .= "<p class=\"" . $esc($p) . "__nb\"><strong>Nicht bewertet (n.b.):</strong> " . $esc(implode(", ", array_map("strval", $nb))) . "</p>";
		}
		$html .= "</div>";
		return $html;
	}
}

if (!function_exists("rkrk_assess_storyboard_point_meta_map")) {
	/**
	 * Punktcode → Frage (cue) + Zusatzinfo (info) aus dem Storyboard.
	 * @param array<string,mixed> $story
	 * @return array<string,array{cue:string,info:string}>
	 */
	function rkrk_assess_storyboard_point_meta_map(array $story): array {
		$map = array();
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();
		foreach ($steps as $step) {
			if (!is_array($step)) {
				continue;
			}
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}
				$code = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) ($item["field"] ?? "")));
				if ($code === "") {
					continue;
				}
				$cue = trim((string) ($item["cue"] ?? ""));
				$info = trim((string) ($item["info"] ?? ""));
				if (!isset($map[$code])) {
					$map[$code] = array("cue" => $cue, "info" => $info);
					continue;
				}
				if ($cue !== "" && strpos((string) $map[$code]["cue"], $cue) === false) {
					$map[$code]["cue"] = trim($map[$code]["cue"] . " · " . $cue, " ·");
				}
				if ($info !== "" && strpos((string) $map[$code]["info"], $info) === false) {
					$map[$code]["info"] = trim($map[$code]["info"] . "\n" . $info);
				}
			}
		}
		return $map;
	}
}

if (!function_exists("rkrk_assess_storyboard_request_id")) {
	function rkrk_assess_storyboard_request_id(): string {
		foreach (array("story", "storyboard", "Story", "Storyboard") as $k) {
			if (isset($_GET[$k]) && trim((string) $_GET[$k]) !== "") {
				return rkrk_assess_storyboard_normalize_id((string) $_GET[$k]);
			}
			if (isset($_POST[$k]) && trim((string) $_POST[$k]) !== "") {
				return rkrk_assess_storyboard_normalize_id((string) $_POST[$k]);
			}
		}
		return "";
	}
}

if (!function_exists("rkrk_assess_storyboard_form_href")) {
	/**
	 * Direktstart digitale Prüfung: Template + Storyboard.
	 * @param array<string,mixed>|string $storyOrId
	 */
	function rkrk_assess_storyboard_form_href($storyOrId, string $templateFile = ""): string {
		$id = "";
		$tpl = basename(trim($templateFile));
		if (is_array($storyOrId)) {
			$id = rkrk_assess_storyboard_normalize_id((string) ($storyOrId["id"] ?? ""));
			if ($tpl === "") {
				$tpl = basename(trim((string) ($storyOrId["template"] ?? "")));
			}
		} else {
			$id = rkrk_assess_storyboard_normalize_id((string) $storyOrId);
			if ($tpl === "" && $id !== "" && function_exists("rkrk_assess_storyboard_load")) {
				$loaded = rkrk_assess_storyboard_load($id);
				if (is_array($loaded)) {
					$tpl = basename(trim((string) ($loaded["template"] ?? "")));
				}
			}
		}
		if ($id === "" || $tpl === "") {
			return "";
		}
		$href = "?mode=form&file=" . rawurlencode($tpl) . "&story=" . rawurlencode($id);
		if (function_exists("rkrk_assess_append_setting_href")) {
			return rkrk_assess_append_setting_href($href);
		}
		return $href;
	}
}

if (!function_exists("rkrk_assess_storyboard_resolve_for_form")) {
	/**
	 * Löst Storyboard + Template für Formularstart auf.
	 * Aufruf nur mit story=… (ohne file) ist erlaubt — Template kommt aus dem Storyboard.
	 *
	 * @return array{0:string,1:?array<string,mixed>,2:string} [templateFile, story|null, storyId]
	 */
	function rkrk_assess_storyboard_resolve_for_form(string $templateFile, string $storyId = ""): array {
		$templateFile = basename(trim($templateFile));
		if ($storyId === "" && function_exists("rkrk_assess_storyboard_request_id")) {
			$storyId = rkrk_assess_storyboard_request_id();
		} else {
			$storyId = rkrk_assess_storyboard_normalize_id($storyId);
		}
		$story = null;
		if ($storyId !== "" && function_exists("rkrk_assess_storyboard_load")) {
			$story = rkrk_assess_storyboard_load($storyId);
		}
		if (!is_array($story)) {
			$GLOBALS["rkrkAssessActiveStory"] = null;
			return array($templateFile, null, "");
		}
		$storyTpl = basename(trim((string) ($story["template"] ?? "")));
		if ($storyTpl !== "") {
			$templateFile = $storyTpl;
		}
		$storyId = rkrk_assess_storyboard_normalize_id((string) ($story["id"] ?? $storyId));
		$GLOBALS["rkrkAssessActiveStory"] = $story;
		return array($templateFile, $story, $storyId);
	}
}

if (!function_exists("rkrk_assess_storyboard_emit_run_ui")) {
	/**
	 * Stepper + CSS/JS für Storyboard-Run-Mode.
	 * @param array<string,mixed> $story
	 * @param array<string,mixed> $sections
	 */
	function rkrk_assess_storyboard_emit_run_ui(array $story, array $sections): void {
		$id = htmlspecialchars((string) ($story["id"] ?? ""), ENT_QUOTES, "UTF-8");
		$title = htmlspecialchars((string) ($story["title"] ?? $id), ENT_QUOTES, "UTF-8");
		$ver = htmlspecialchars((string) ($story["version"] ?? ""), ENT_QUOTES, "UTF-8");
		$intro = trim((string) ($story["intro"] ?? ""));
		$steps = isset($story["steps"]) && is_array($story["steps"]) ? $story["steps"] : array();

		$stepPayload = array();
		foreach ($steps as $idx => $step) {
			if (!is_array($step)) {
				continue;
			}
			$fieldCodes = array();
			$items = isset($step["items"]) && is_array($step["items"]) ? $step["items"] : array();
			foreach ($items as $item) {
				$ref = is_array($item) ? (string) ($item["field"] ?? "") : (string) $item;
				$c = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code($ref));
				if ($c !== "") {
					$fieldCodes[] = $c;
				}
				if (is_array($item) && isset($item["inject"]) && is_array($item["inject"])) {
					foreach ($item["inject"] as $inj) {
						$ic = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) $inj));
						if ($ic !== "") {
							$fieldCodes[] = $ic;
						}
					}
				}
			}
			$cont = array();
			$clist = isset($step["continuous"]) && is_array($step["continuous"]) ? $step["continuous"] : array();
			foreach ($clist as $cref) {
				$cc = rkrk_assess_storyboard_normalize_code(rkrk_assess_storyboard_field_code((string) $cref));
				if ($cc !== "") {
					$cont[] = $cc;
				}
			}
			$stepPayload[] = array(
				"id" => (string) ($step["id"] ?? ("step_" . $idx)),
				"title" => (string) ($step["title"] ?? ("Schritt " . ($idx + 1))),
				"duration_min" => (int) ($step["duration_min"] ?? 0),
				"mode" => (string) ($step["mode"] ?? "score"),
				"prep" => (string) ($step["prep"] ?? ""),
				"fields" => array_values(array_unique($fieldCodes)),
				"continuous" => array_values(array_unique($cont)),
			);
		}

		echo "<div class='rkrk-story-banner' id='rkrkStoryBanner'>";
		echo "<div class='rkrk-story-banner-title'><strong>Storyboard:</strong> " . $title;
		if ($ver !== "") {
			echo " <span class='rkrk-story-ver'>v" . $ver . "</span>";
		}
		echo "</div>";
		echo "<details class='rkrk-story-overview-fold'>";
		echo "<summary>Übersicht</summary>";
		echo "<div class='rkrk-story-overview-body'>";
		echo rkrk_assess_storyboard_overview_html($story, array(
			"class_prefix" => "rkrk-sb-ov",
			"h_tag" => "h3",
			"include_meta" => true,
			"include_intro" => false,
			"include_prep" => true,
		));
		echo "</div></details>";
		if ($intro !== "") {
			echo "<div class='rkrk-story-intro'>" . htmlspecialchars($intro, ENT_QUOTES, "UTF-8") . "</div>";
		}
		echo "<nav class='rkrk-story-rail' id='rkrkStoryRail' aria-label='Prüfungsphasen'>";
		foreach ($stepPayload as $i => $sp) {
			$dur = ((int) $sp["duration_min"] > 0) ? (" · " . (int) $sp["duration_min"] . " Min") : "";
			echo "<button type='button' class='rkrk-story-step" . ($i === 0 ? " active" : "") . "' data-step-idx='" . (int) $i . "'>";
			echo htmlspecialchars((string) $sp["title"], ENT_QUOTES, "UTF-8") . $dur;
			echo "</button>";
		}
		echo "</nav>";
		echo "<div class='rkrk-story-navbtns'>";
		echo "<button type='button' class='rkrk-story-btn' id='rkrkStoryPrev'>Zurück</button>";
		echo "<button type='button' class='rkrk-story-btn primary' id='rkrkStoryNext'>Weiter</button>";
		echo "<button type='button' class='rkrk-story-btn' id='rkrkStoryShowAll'>n.b. Punkte zeigen</button>";
		echo "</div>";
		echo "<div class='rkrk-story-stepmeta' id='rkrkStoryStepMeta'></div>";
		echo "<div class='rkrk-story-continuous' id='rkrkStoryContinuous'></div>";
		echo "</div>";

		echo "<style>
.rkrk-story-banner{margin:0 0 14px;padding:12px 14px;border:1px solid #c5d4c0;border-radius:10px;background:linear-gradient(180deg,#f4faf3,#eef6ec);}
.rkrk-story-banner-title{font-size:15px;margin:0 0 6px;color:#1f3d1f;}
.rkrk-story-ver{font-size:12px;color:#64748b;font-weight:500;}
.rkrk-story-intro{font-size:13px;color:#334155;margin:0 0 8px;line-height:1.4;}
.rkrk-story-overview-fold{margin:0 0 10px;border:1px solid #c5d4c0;border-radius:8px;background:#fff;overflow:hidden;}
.rkrk-story-overview-fold>summary{cursor:pointer;font-weight:700;color:#1f3d1f;padding:8px 12px;list-style:none;background:#f4faf3;user-select:none;}
.rkrk-story-overview-fold>summary::-webkit-details-marker{display:none;}
.rkrk-story-overview-fold>summary::before{content:'▸ ';display:inline-block;transition:transform .15s ease;}
.rkrk-story-overview-fold[open]>summary::before{content:'▾ ';}
.rkrk-story-overview-hint{font-weight:500;font-size:12px;color:#64748b;}
.rkrk-story-overview-body{padding:8px 12px 12px;border-top:1px solid #e2e8e0;}
.rkrk-sb-ov{font-size:13px;color:#334155;line-height:1.4;}
.rkrk-sb-ov__title{font-size:14px;margin:0 0 6px;color:#1f3d1f;}
.rkrk-sb-ov__meta{font-size:12px;color:#64748b;margin:0 0 8px;}
.rkrk-sb-ov__phase{margin:8px 0 10px;padding:6px 8px;border-left:3px solid #48673a;background:#f7fbf6;}
.rkrk-sb-ov__phase-title{margin:0 0 4px;}
.rkrk-sb-ov__dur{font-weight:400;color:#64748b;}
.rkrk-sb-ov__phase-prep,.rkrk-sb-ov__cont{margin:0 0 4px;font-size:12px;}
.rkrk-sb-ov__items{margin:4px 0 0 1.2em;padding:0;}
.rkrk-sb-ov__items li{margin:3px 0;}
.rkrk-sb-ov__cue,.rkrk-sb-ov__info,.rkrk-sb-ov__inject{margin:2px 0;font-size:12px;}
.rkrk-sb-ov__nb{margin:8px 0 0;font-size:12px;color:#64748b;}
.rkrk-story-prep{font-size:13px;margin:0 0 8px;}
.rkrk-story-prep ul{margin:6px 0 0 1.1em;padding:0;}
.rkrk-story-rail{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0;}
.rkrk-story-step{border:1px solid #9bb89a;background:#fff;color:#1f3d1f;border-radius:999px;padding:6px 12px;font-size:12px;cursor:pointer;}
.rkrk-story-step.active{background:#3f6b3d;color:#fff;border-color:#3f6b3d;font-weight:700;}
.rkrk-story-step.done{background:#e8f2e7;}
.rkrk-story-navbtns{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;}
.rkrk-story-btn{border:1px solid #7a8f78;border-radius:8px;background:#f4f7f3;color:#1f2937;padding:7px 12px;font-size:13px;cursor:pointer;}
.rkrk-story-btn.primary{background:#48673a;color:#fff;border-color:#48673a;font-weight:700;}
.rkrk-story-stepmeta{font-size:13px;color:#334155;min-height:1.2em;margin:4px 0 8px;}
.rkrk-story-phase-prep{margin:0 0 12px;padding:8px 10px;border-left:3px solid #94a3b8;background:#f8fafc;font-size:13px;color:#475569;}
.rkrk-story-point{margin:0 0 16px;padding:10px 12px;border:1px solid #d9e5d6;border-radius:10px;background:#fcfefb;}
.rkrk-story-point--stoer{border-color:#fdba74;border-left:4px solid #c2410c;background:#fff7ed;}
.rkrk-story-stoer-badge{display:inline-block;margin:0 0 8px;padding:2px 8px;border-radius:999px;background:#c2410c;color:#fff;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
.rkrk-story-point--stoer .rkrk-story-cue{border-left-color:#c2410c;background:#ffedd5;}
.rkrk-story-cue{display:block;margin:0 0 8px;padding:8px 10px;border-left:3px solid #48673a;background:#f7fbf6;font-size:13px;color:#1f3d1f;line-height:1.4;}
.rkrk-story-cue-label,.rkrk-story-info-label{display:inline-block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#48673a;margin-right:6px;}
.rkrk-story-info{margin:0 0 10px;padding:8px 10px;border-left:3px solid #64748b;background:#f8fafc;font-size:13px;color:#334155;line-height:1.45;}
.rkrk-story-info-label{color:#475569;}
.rkrk-story-info-body{margin-top:4px;}
.rkrk-story-nb{opacity:.92;border-color:#e2e8f0;background:#f8fafc;}
.rkrk-story-nb .field-label::after{content:' · n.b.';color:#94a3b8;font-weight:500;font-size:12px;}
.rkrk-story-nb-fold{display:block;margin:12px 0;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;overflow:hidden;}
.rkrk-story-nb-fold>summary.section-title-box{cursor:pointer;list-style:none;display:block;}
.rkrk-story-nb-fold>summary::-webkit-details-marker{display:none;}
.rkrk-story-nb-hint{font-size:13px;color:#64748b;margin:0 0 10px;}
.rkrk-story-continuous{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;}
.rkrk-story-chip{font-size:12px;padding:4px 8px;border-radius:6px;background:#dfece0;color:#1f3d1f;}
.section-row.rkrk-story-sec-hidden{display:none!important;}
.rkrk-story-phase.rkrk-story-phase-focus{outline:2px solid #48673a;outline-offset:2px;}
@media (max-width:720px){.rkrk-story-rail{flex-direction:column;}.rkrk-story-step{border-radius:8px;text-align:left;}}
</style>";

		$jsonSteps = json_encode($stepPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		echo "<script>\n";
		echo "(function(){\n";
		echo "const steps = " . ($jsonSteps !== false ? $jsonSteps : "[]") . ";\n";
		echo "let idx = 0;\n";
		echo "function applyStep(){\n";
		echo "  const rail=document.getElementById('rkrkStoryRail');\n";
		echo "  if(rail){\n";
		echo "    rail.querySelectorAll('.rkrk-story-step').forEach(function(btn,i){\n";
		echo "      btn.classList.toggle('active', i===idx);\n";
		echo "      btn.classList.toggle('done', i<idx);\n";
		echo "    });\n";
		echo "  }\n";
		echo "  const step=steps[idx]||{title:'',prep:'',continuous:[]};\n";
		echo "  const meta=document.getElementById('rkrkStoryStepMeta');\n";
		echo "  if(meta){\n";
		echo "    let t='Phase: '+String(step.title||'');\n";
		echo "    if(step.prep) t+=' — '+String(step.prep);\n";
		echo "    meta.textContent=t;\n";
		echo "  }\n";
		echo "  const cont=document.getElementById('rkrkStoryContinuous');\n";
		echo "  if(cont){\n";
		echo "    cont.innerHTML='';\n";
		echo "    (step.continuous||[]).forEach(function(c){\n";
		echo "      const chip=document.createElement('span');\n";
		echo "      chip.className='rkrk-story-chip';\n";
		echo "      chip.textContent='Durchgehend: '+c;\n";
		echo "      cont.appendChild(chip);\n";
		echo "    });\n";
		echo "  }\n";
		echo "  document.querySelectorAll('.rkrk-story-phase').forEach(function(row,i){\n";
		echo "    row.classList.toggle('rkrk-story-phase-focus', i===idx);\n";
		echo "  });\n";
		echo "  const focus=document.querySelectorAll('.rkrk-story-phase')[idx];\n";
		echo "  if(focus && focus.scrollIntoView){ try{ focus.scrollIntoView({behavior:'smooth',block:'start'}); }catch(e){} }\n";
		echo "}\n";
		echo "document.getElementById('rkrkStoryPrev')?.addEventListener('click', function(){ idx=Math.max(0,idx-1); applyStep(); });\n";
		echo "document.getElementById('rkrkStoryNext')?.addEventListener('click', function(){ idx=Math.min(steps.length-1,idx+1); applyStep(); });\n";
		echo "const showAllBtn=document.getElementById('rkrkStoryShowAll');\n";
		echo "if(showAllBtn){\n";
		echo "  showAllBtn.addEventListener('click', function(){\n";
		echo "    const fold=document.getElementById('rkrkStoryNbFold');\n";
		echo "    if(fold){ fold.open=!fold.open; this.textContent=fold.open?'n.b. einklappen':'n.b. Punkte zeigen'; }\n";
		echo "  });\n";
		echo "}\n";
		echo "document.getElementById('rkrkStoryRail')?.addEventListener('click', function(e){\n";
		echo "  const btn=e.target.closest('.rkrk-story-step'); if(!btn) return;\n";
		echo "  idx=parseInt(btn.getAttribute('data-step-idx')||'0',10)||0; applyStep();\n";
		echo "});\n";
		echo "applyStep();\n";
		echo "})();\n";
		echo "</script>\n";
	}
}

if (!function_exists("rkrk_assess_storyboard_emit_chooser")) {
	/**
	 * Auswahl Storyboard vs. Vollkatalog (nur UI-Start; Bewertung bleibt Template).
	 * @param list<array<string,mixed>> $stories
	 */
	function rkrk_assess_storyboard_emit_chooser(string $templateFile, array $stories): void {
		if (!empty($GLOBALS["rkrkAssessActiveStory"]) && is_array($GLOBALS["rkrkAssessActiveStory"])) {
			return;
		}
		if (function_exists("rkrk_assess_storyboard_request_id") && rkrk_assess_storyboard_request_id() !== "") {
			return;
		}
		if (!(function_exists("rkrk_assess_url_has_staff_flag") && rkrk_assess_url_has_staff_flag())) {
			return;
		}
		$templateFile = basename(trim($templateFile));
		if ($templateFile === "" || $stories === array()) {
			return;
		}
		$base = "?mode=form&file=" . rawurlencode($templateFile);
		if (function_exists("rkrk_assess_append_setting_href")) {
			$fullHref = htmlspecialchars(rkrk_assess_append_setting_href($base), ENT_QUOTES, "UTF-8");
		} else {
			$fullHref = htmlspecialchars($base, ENT_QUOTES, "UTF-8");
		}
		echo "<style>
.rkrk-story-chooser{margin:0 0 14px;border:1px solid #c5d4c0;border-radius:8px;background:#fff;overflow:hidden;}
.rkrk-story-chooser>summary{cursor:pointer;font-weight:700;color:#1f3d1f;padding:8px 12px;list-style:none;background:#f4faf3;user-select:none;}
.rkrk-story-chooser>summary::-webkit-details-marker{display:none;}
.rkrk-story-chooser>summary::before{content:'▸ ';display:inline-block;}
.rkrk-story-chooser[open]>summary::before{content:'▾ ';}
.rkrk-story-chooser-body{padding:8px 12px 12px;border-top:1px solid #e2e8e0;}
.rkrk-story-chooser-hint{margin:0 0 10px;color:#64748b;font-size:14px;}
.rkrk-story-chooser-btns{display:flex;flex-wrap:wrap;gap:8px;}
</style>";
		echo "<details class='rkrk-card rkrk-story-chooser' id='rkrkStoryChooser' open>";
		echo "<summary>Prüfablauf wählen</summary>";
		echo "<div class='rkrk-story-chooser-body'>";
		echo "<p class='rkrk-story-chooser-hint'>Storyboard steuert nur Ablauf und setzt ungenutzte Punkte auf <code>n.b.(n)</code>. Bewertung und Schlussreport bleiben wie bisher (Template).</p>";
		echo "<div class='rkrk-story-chooser-btns'>";
		foreach ($stories as $s) {
			if (!is_array($s)) {
				continue;
			}
			$sid = rkrk_assess_storyboard_normalize_id((string) ($s["id"] ?? ""));
			if ($sid === "") {
				continue;
			}
			$st = trim((string) ($s["title"] ?? $sid));
			$href = function_exists("rkrk_assess_storyboard_form_href")
				? rkrk_assess_storyboard_form_href($s, $templateFile)
				: "";
			if ($href === "") {
				$href = $base . "&story=" . rawurlencode($sid);
				if (function_exists("rkrk_assess_append_setting_href")) {
					$href = rkrk_assess_append_setting_href($href);
				}
			}
			echo "<a class='btn btn-primary' href='" . htmlspecialchars($href, ENT_QUOTES, "UTF-8") . "'>▶ " . htmlspecialchars($st, ENT_QUOTES, "UTF-8") . "</a>";
		}
		echo "<a class='btn' href='" . $fullHref . "' style='opacity:.85;'>Vollkatalog (ohne Storyboard)</a>";
		echo "</div></div></details>\n";
		echo "<script>(function(){var el=document.getElementById('rkrkStoryChooser');if(!el)return;var k='rkrk-story-chooser-open';try{var s=localStorage.getItem(k);if(s==='0')el.removeAttribute('open');if(s==='1')el.setAttribute('open','');}catch(e){}el.addEventListener('toggle',function(){try{localStorage.setItem(k,el.open?'1':'0');}catch(e){}});})();</script>\n";
	}
}
