<?php
/**
 * Häufigkeit und Trefferquote der Fragen aus abgeschlossenen Examen (results/exams).
 * Buchstaben werden je Versuch auf den Optionstext abgebildet, damit gemischte Antworten vergleichbar bleiben.
 */

if (!function_exists("rkrk_dqs_plain")) {
	function rkrk_dqs_plain($value): string {
		$s = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, "UTF-8");
		$s = preg_replace("/\s+/u", " ", $s);
		return trim((string) $s);
	}
}

if (!function_exists("rkrk_dqs_fold")) {
	function rkrk_dqs_fold(string $value): string {
		$s = rkrk_dqs_plain($value);
		if ($s === "") {
			return "";
		}
		return function_exists("mb_strtolower") ? mb_strtolower($s, "UTF-8") : strtolower($s);
	}
}

if (!function_exists("rkrk_dqs_tokens")) {
	/** @return list<string> */
	function rkrk_dqs_tokens($raw): array {
		$s = trim((string) $raw);
		if ($s === "") {
			return array();
		}
		if (strcasecmp($s, "Ja") === 0) {
			return array("JA");
		}
		if (strcasecmp($s, "Nein") === 0) {
			return array("NEIN");
		}
		$parts = preg_split("/\s*\|\s*/", $s);
		$out = array();
		if (!is_array($parts)) {
			return $out;
		}
		foreach ($parts as $p) {
			$t = strtoupper(trim((string) $p));
			if ($t !== "") {
				$out[] = $t;
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_dqs_option_map")) {
	/** @return array<string,string> letter => text */
	function rkrk_dqs_option_map($raw): array {
		$items = array();
		if (is_array($raw)) {
			$items = $raw;
		} else {
			$s = trim((string) $raw);
			if ($s !== "") {
				$split = preg_split("/\s*\|\s*/", $s);
				$items = is_array($split) ? $split : array();
			}
		}
		$letters = array("A", "B", "C", "D", "E", "F", "G", "H");
		$map = array();
		$i = 0;
		foreach ($items as $item) {
			if (is_array($item)) {
				$item = (string) ($item["text"] ?? ($item["label"] ?? ""));
			}
			$text = rkrk_dqs_plain($item);
			$letter = $letters[$i] ?? ("N" . $i);
			$i++;
			if ($text === "") {
				continue;
			}
			$map[$letter] = $text;
		}
		return $map;
	}
}

if (!function_exists("rkrk_dqs_label_map")) {
	/** @return array<string,string> */
	function rkrk_dqs_label_map($label): array {
		$map = array();
		$parts = preg_split("/\s*\|\s*/", (string) $label);
		if (!is_array($parts)) {
			return $map;
		}
		foreach ($parts as $p) {
			$p = trim((string) $p);
			if ($p === "") {
				continue;
			}
			if (preg_match('/^(JA|NEIN|[A-H])\s*:\s*(.+)$/iu', $p, $m)) {
				$key = strtoupper($m[1]);
				$text = rkrk_dqs_plain($m[2]);
				if ($text !== "") {
					$map[$key] = $text;
				}
			}
		}
		return $map;
	}
}

if (!function_exists("rkrk_dqs_exam_dir")) {
	function rkrk_dqs_exam_dir(): string {
		$base = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/");
		} elseif (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/results";
		}
		if ($base === "") {
			return "";
		}
		$dir = $base . "/exams/";
		return is_dir($dir) ? $dir : "";
	}
}

if (!function_exists("rkrk_dqs_cache_path")) {
	function rkrk_dqs_cache_path(): string {
		$base = "";
		if (isset($GLOBALS["QuizLocResults"]) && trim((string) $GLOBALS["QuizLocResults"]) !== "") {
			$base = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocResults"]), "/");
		}
		if ($base === "") {
			return "";
		}
		return $base . "/_cache/question_stats_v1.json";
	}
}

if (!function_exists("rkrk_dqs_modus")) {
	function rkrk_dqs_modus(array $data, string $file): string {
		$raw = (string) ($data["quiz_modus"] ?? "");
		if (function_exists("rkrk_assess_quiz_modus_storage_token")) {
			if (rkrk_assess_quiz_modus_storage_token($raw) === "pruefung") {
				return "pruefung";
			}
		} else {
			$v = strtolower(trim($raw));
			if ($v === "pruefung" || $v === "prüfung") {
				return "pruefung";
			}
		}
		$base = strtolower(basename($file));
		if (strpos($base, "_pruefung") !== false) {
			return "pruefung";
		}
		return "schulung";
	}
}

if (!function_exists("rkrk_dqs_result_rows")) {
	/** @return list<array<string,mixed>> */
	function rkrk_dqs_result_rows(array $data): array {
		$attempts = isset($data["attempts"]) && is_array($data["attempts"]) ? $data["attempts"] : array();
		$items = array();
		if ($attempts !== array()) {
			foreach ($attempts as $attempt) {
				if (is_array($attempt)) {
					$items[] = $attempt;
				}
			}
		} elseif (strtolower(trim((string) ($data["status"] ?? ""))) === "completed" && isset($data["result"]) && is_array($data["result"])) {
			$items[] = array("status" => "completed", "result" => $data["result"]);
		}
		$out = array();
		foreach ($items as $attempt) {
			if (strtolower(trim((string) ($attempt["status"] ?? ""))) !== "completed") {
				continue;
			}
			$result = isset($attempt["result"]) && is_array($attempt["result"]) ? $attempt["result"] : array();
			if ($result === array()) {
				continue;
			}
			$out[] = $result;
		}
		return $out;
	}
}

if (!function_exists("rkrk_dqs_empty_modus")) {
	function rkrk_dqs_empty_modus(): array {
		return array(
			"asked" => 0,
			"full" => 0,
			"partial" => 0,
			"wrong" => 0,
			"blank" => 0,
			"options" => array(),
			"patterns" => array(),
		);
	}
}

if (!function_exists("rkrk_dqs_fingerprint")) {
	function rkrk_dqs_fingerprint(string $dir): string {
		$files = glob($dir . "*.json");
		if (!is_array($files)) {
			$files = array();
		}
		sort($files, SORT_STRING);
		$parts = array("v1");
		foreach ($files as $file) {
			$mt = @filemtime($file);
			$sz = @filesize($file);
			$parts[] = basename($file) . ":" . (string) ($mt === false ? 0 : $mt) . ":" . (string) ($sz === false ? 0 : $sz);
		}
		return sha1(implode("\n", $parts));
	}
}

if (!function_exists("rkrk_dqs_note_option")) {
	function rkrk_dqs_note_option(array &$modus, string $text, bool $offered, bool $picked, bool $correct): void {
		$fold = rkrk_dqs_fold($text);
		if ($fold === "") {
			return;
		}
		if (!isset($modus["options"][$fold])) {
			$modus["options"][$fold] = array(
				"text" => $text,
				"offered" => 0,
				"picked" => 0,
				"as_correct" => 0,
				"picked_wrong" => 0,
				"missed" => 0,
			);
		}
		if ($offered) {
			$modus["options"][$fold]["offered"]++;
		}
		if ($correct) {
			$modus["options"][$fold]["as_correct"]++;
		}
		if ($picked) {
			$modus["options"][$fold]["picked"]++;
			if (!$correct) {
				$modus["options"][$fold]["picked_wrong"]++;
			}
		} elseif ($correct) {
			$modus["options"][$fold]["missed"]++;
		}
	}
}

if (!function_exists("rkrk_dqs_prose_from_result")) {
	/** Lösungstext, sonst kurzer Antworttext. Leer, wenn beides fehlt. */
	function rkrk_dqs_prose_from_result(array $row): string {
		$lt = rkrk_dqs_plain($row["loesungstext"] ?? ($row["Loesungstext"] ?? ""));
		if ($lt !== "") {
			return $lt;
		}
		return rkrk_dqs_plain($row["antwortText"] ?? ($row["antworttext"] ?? ($row["AntwortText"] ?? "")));
	}
}

if (!function_exists("rkrk_dqs_scan")) {
	function rkrk_dqs_scan(): array {
		$dir = rkrk_dqs_exam_dir();
		$agg = array(
			"version" => 2,
			"built_at" => date("c"),
			"fingerprint" => $dir !== "" ? rkrk_dqs_fingerprint($dir) : "",
			"files" => 0,
			"bad_files" => 0,
			"attempts" => array("pruefung" => 0, "schulung" => 0),
			"attempts_detail" => array("pruefung" => 0, "schulung" => 0),
			"q" => array(),
		);
		if ($dir === "") {
			return $agg;
		}
		$files = glob($dir . "*.json");
		if (!is_array($files)) {
			return $agg;
		}
		usort($files, static function ($a, $b) {
			$ma = @filemtime($a);
			$mb = @filemtime($b);
			$ma = $ma === false ? 0 : (int) $ma;
			$mb = $mb === false ? 0 : (int) $mb;
			if ($ma === $mb) {
				return strcmp($a, $b);
			}
			return $ma <=> $mb;
		});
		foreach ($files as $file) {
			if (!is_file($file)) {
				continue;
			}
			$agg["files"]++;
			$raw = @file_get_contents($file);
			if ($raw === false || trim($raw) === "") {
				$agg["bad_files"]++;
				continue;
			}
			$flags = 0;
			if (defined("JSON_INVALID_UTF8_SUBSTITUTE")) {
				$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
			}
			$data = json_decode($raw, true, 512, $flags);
			if (!is_array($data)) {
				$agg["bad_files"]++;
				continue;
			}
			$modus = rkrk_dqs_modus($data, $file);
			$quiz = trim((string) ($data["quiz_key"] ?? ""));
			if ($quiz === "") {
				$quiz = "(ohne Quiz)";
			}
			foreach (rkrk_dqs_result_rows($data) as $result) {
				$agg["attempts"][$modus] = (int) ($agg["attempts"][$modus] ?? 0) + 1;
				$fragen = $result["quiz_ergebnis_fragen"] ?? null;
				if (!is_array($fragen) || $fragen === array()) {
					continue;
				}
				$agg["attempts_detail"][$modus] = (int) ($agg["attempts_detail"][$modus] ?? 0) + 1;
				foreach ($fragen as $row) {
					if (!is_array($row)) {
						continue;
					}
					$qid = trim((string) ($row["qid"] ?? ($row["QID"] ?? "")));
					$frage = rkrk_dqs_plain($row["frage"] ?? ($row["Frage"] ?? ""));
					if ($qid === "") {
						$qid = "t:" . substr(sha1(rkrk_dqs_fold($frage)), 0, 10);
					}
					$key = $quiz . "\n" . $qid;
					if (!isset($agg["q"][$key])) {
						$agg["q"][$key] = array(
							"quiz_key" => $quiz,
							"qid" => $qid,
							"frage" => "",
							"loesung" => "",
							"antworttext" => "",
							"modi" => array(),
						);
					}
					if ($frage !== "") {
						$agg["q"][$key]["frage"] = $frage;
					}
					if (!isset($agg["q"][$key]["modi"][$modus])) {
						$agg["q"][$key]["modi"][$modus] = rkrk_dqs_empty_modus();
					}
					$slot = &$agg["q"][$key]["modi"][$modus];
					$status = strtolower(trim((string) ($row["status"] ?? "")));
					$antwort = trim((string) ($row["antwort"] ?? ($row["Antwort"] ?? "")));
					$loesung = trim((string) ($row["loesung"] ?? ($row["Loesung"] ?? "")));
					$richtig = !empty($row["richtig"]);
					if ($status === "full" || ($richtig && $status !== "partial" && $status !== "zero")) {
						$kind = "full";
					} elseif ($status === "partial") {
						$kind = "partial";
					} elseif ($antwort === "") {
						$kind = "blank";
					} else {
						$kind = "wrong";
					}
					$slot["asked"]++;
					$slot[$kind]++;

					$map = rkrk_dqs_option_map($row["loesungsauswahl"] ?? ($row["Loesungsauswahl"] ?? array()));
					if ($map === array()) {
						$map = rkrk_dqs_label_map((string) ($row["antwortLabel"] ?? ""));
						foreach (rkrk_dqs_label_map((string) ($row["loesungLabel"] ?? "")) as $lk => $lt) {
							if (!isset($map[$lk])) {
								$map[$lk] = $lt;
							}
						}
					}
					$pickedLetters = rkrk_dqs_tokens($antwort);
					$correctLetters = rkrk_dqs_tokens($loesung);
					if ($map === array() && ($pickedLetters === array("JA") || $pickedLetters === array("NEIN") || $correctLetters === array("JA") || $correctLetters === array("NEIN"))) {
						$map = array("JA" => "Ja", "NEIN" => "Nein");
					}
					$correctFold = array();
					$correctTexts = array();
					foreach ($correctLetters as $letter) {
						$text = $map[$letter] ?? $letter;
						$correctTexts[] = $text;
						$correctFold[rkrk_dqs_fold($text)] = true;
					}
					if ($correctTexts !== array()) {
						$show = $correctTexts;
						sort($show, SORT_STRING);
						$agg["q"][$key]["loesung"] = implode(" | ", $show);
					}
					$prose = rkrk_dqs_prose_from_result($row);
					if ($prose !== "") {
						$agg["q"][$key]["antworttext"] = $prose;
					}
					$pickedFold = array();
					$pickedTexts = array();
					foreach ($pickedLetters as $letter) {
						$text = $map[$letter] ?? $letter;
						$pickedTexts[] = $text;
						$pickedFold[rkrk_dqs_fold($text)] = true;
					}
					$seenFold = array();
					foreach ($map as $letter => $text) {
						$fold = rkrk_dqs_fold($text);
						if ($fold === "" || isset($seenFold[$fold])) {
							continue;
						}
						$seenFold[$fold] = true;
						$isCorrect = isset($correctFold[$fold]);
						$isPicked = in_array($letter, $pickedLetters, true) || isset($pickedFold[$fold]);
						rkrk_dqs_note_option($slot, $text, true, $isPicked, $isCorrect);
					}
					if ($kind !== "full") {
						if ($pickedTexts === array()) {
							$label = "keine Antwort";
						} else {
							$labels = $pickedTexts;
							sort($labels, SORT_STRING);
							$label = implode(" | ", $labels);
							$subset = true;
							foreach ($pickedTexts as $pt) {
								if (!isset($correctFold[rkrk_dqs_fold($pt)])) {
									$subset = false;
									break;
								}
							}
							if ($kind === "partial" && $subset) {
								$label = "unvollständig: " . $label;
							}
						}
						$pf = rkrk_dqs_fold($label);
						if ($pf === "") {
							$pf = "_";
						}
						if (!isset($slot["patterns"][$pf])) {
							$slot["patterns"][$pf] = array("text" => $label, "n" => 0);
						}
						$slot["patterns"][$pf]["n"]++;
					}
					unset($slot);
				}
			}
		}
		return $agg;
	}
}

if (!function_exists("rkrk_dqs_load_agg")) {
	function rkrk_dqs_load_agg(bool $refresh): array {
		if (function_exists("set_time_limit")) {
			@set_time_limit(180);
		}
		$dir = rkrk_dqs_exam_dir();
		$fp = $dir !== "" ? rkrk_dqs_fingerprint($dir) : "";
		$path = rkrk_dqs_cache_path();
		if (!$refresh && $path !== "" && is_file($path)) {
			$raw = @file_get_contents($path);
			$cached = is_string($raw) ? json_decode($raw, true) : null;
			if (is_array($cached) && (string) ($cached["fingerprint"] ?? "") === $fp && (int) ($cached["version"] ?? 0) === 2) {
				$cached["from_cache"] = true;
				return $cached;
			}
		}
		$agg = rkrk_dqs_scan();
		$agg["from_cache"] = false;
		if ($path !== "") {
			$dirCache = dirname($path);
			if (!is_dir($dirCache)) {
				@mkdir($dirCache, 0755, true);
			}
			if (is_dir($dirCache) && is_writable($dirCache)) {
				$json = json_encode($agg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				if (is_string($json)) {
					$tmp = $path . ".tmp";
					if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
						if (is_file($path)) {
							@unlink($path);
						}
						if (!@rename($tmp, $path)) {
							@unlink($tmp);
						}
					}
				}
			}
		}
		return $agg;
	}
}

if (!function_exists("rkrk_dqs_merge_modi")) {
	function rkrk_dqs_merge_modi(array $modi, string $scope): array {
		$keys = $scope === "alle" ? array("pruefung", "schulung") : array($scope);
		$out = rkrk_dqs_empty_modus();
		foreach ($keys as $k) {
			if (!isset($modi[$k]) || !is_array($modi[$k])) {
				continue;
			}
			$src = $modi[$k];
			foreach (array("asked", "full", "partial", "wrong", "blank") as $field) {
				$out[$field] += (int) ($src[$field] ?? 0);
			}
			foreach (($src["options"] ?? array()) as $fold => $opt) {
				if (!is_array($opt)) {
					continue;
				}
				if (!isset($out["options"][$fold])) {
					$out["options"][$fold] = $opt;
					continue;
				}
				foreach (array("offered", "picked", "as_correct", "picked_wrong", "missed") as $field) {
					$out["options"][$fold][$field] += (int) ($opt[$field] ?? 0);
				}
			}
			foreach (($src["patterns"] ?? array()) as $fold => $pat) {
				if (!is_array($pat)) {
					continue;
				}
				if (!isset($out["patterns"][$fold])) {
					$out["patterns"][$fold] = $pat;
					continue;
				}
				$out["patterns"][$fold]["n"] += (int) ($pat["n"] ?? 0);
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_dqs_assessments")) {
	/** @return array<string,array{titel:string,file:string}> */
	function rkrk_dqs_assessments(): array {
		$out = array();
		$root = isset($GLOBALS["QuizLoc"]) ? rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") : "";
		if ($root === "" || !function_exists("csv_to_array_ascII")) {
			return $out;
		}
		$rows = csv_to_array_ascII($root . "/AssessmentListe.csv", ";");
		if (!is_array($rows)) {
			return $out;
		}
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$key = trim((string) ($row["Key"] ?? ""));
			if ($key === "") {
				continue;
			}
			$out[$key] = array(
				"titel" => trim((string) ($row["Titel"] ?? "")),
				"file" => trim((string) ($row["FragenDatei"] ?? "")),
			);
		}
		return $out;
	}
}

if (!function_exists("rkrk_dqs_catalog")) {
	/** @return array<string,array{nr:string,frage:string,bereich:string,loesung:string,antworttext:string}> */
	function rkrk_dqs_catalog(string $quizKey, array $assessments): array {
		$out = array();
		if ($quizKey === "" || !isset($assessments[$quizKey])) {
			$found = "";
			foreach ($assessments as $k => $meta) {
				if (strcasecmp($k, $quizKey) === 0) {
					$found = $k;
					break;
				}
			}
			if ($found === "") {
				return $out;
			}
			$quizKey = $found;
		}
		$file = (string) ($assessments[$quizKey]["file"] ?? "");
		$root = isset($GLOBALS["QuizLoc"]) ? rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") : "";
		if ($file === "" || $root === "" || !function_exists("csv_to_array_ascII")) {
			return $out;
		}
		$path = $root . "/" . ltrim(str_replace("\\", "/", $file), "/");
		$rows = csv_to_array_ascII($path, ";");
		if (!is_array($rows)) {
			return $out;
		}
		if (function_exists("rkrk_assess_fixup_csv_assoc_rows")) {
			$rows = rkrk_assess_fixup_csv_assoc_rows($rows);
		}
		foreach ($rows as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$frage = rkrk_dqs_plain($row["Frage"] ?? "");
			if ($frage === "") {
				continue;
			}
			$loesung = "";
			$map = rkrk_dqs_option_map($row["Loesungsauswahl"] ?? "");
			$letters = rkrk_dqs_tokens($row["Loesung"] ?? "");
			if ($map === array() && ($letters === array("JA") || $letters === array("NEIN"))) {
				$map = array("JA" => "Ja", "NEIN" => "Nein");
			}
			$texts = array();
			foreach ($letters as $letter) {
				if (isset($map[$letter])) {
					$texts[] = $map[$letter];
				}
			}
			if ($texts !== array()) {
				sort($texts, SORT_STRING);
				$loesung = implode(" | ", $texts);
			}
			$prose = function_exists("rkrk_assess_question_solution_prose")
				? rkrk_dqs_plain(rkrk_assess_question_solution_prose($row))
				: rkrk_dqs_prose_from_result($row);
			$nr = trim((string) ($row["Nr"] ?? ""));
			$out[(string) $idx] = array(
				"nr" => $nr !== "" ? $nr : (string) ((int) $idx + 1),
				"frage" => $frage,
				"bereich" => rkrk_dqs_plain($row["Bereich"] ?? ""),
				"loesung" => $loesung,
				"antworttext" => $prose,
			);
		}
		return $out;
	}
}

if (!function_exists("rkrk_dqs_pct")) {
	function rkrk_dqs_pct(int $full, int $asked): ?float {
		if ($asked < 1) {
			return null;
		}
		return round(($full / $asked) * 100, 1);
	}
}

if (!function_exists("rkrk_dqs_urteil")) {
	function rkrk_dqs_urteil(int $asked, int $full, int $min, bool $fromCatalogOnly): string {
		if ($fromCatalogOnly || $asked < 1) {
			return "nie";
		}
		if ($asked < $min) {
			return "wenig";
		}
		if (($full * 2) > $asked) {
			return "leicht";
		}
		if (($full * 2) < $asked) {
			return "schwer";
		}
		return "gleich";
	}
}

if (!function_exists("rkrk_dqs_row_from_slot")) {
	function rkrk_dqs_row_from_slot(array $q, array $slot, int $min, ?array $cat): array {
		$asked = (int) $slot["asked"];
		$full = (int) $slot["full"];
		$pct = rkrk_dqs_pct($full, $asked);
		$frage = (string) ($q["frage"] ?? "");
		$drift = false;
		$catalogFrage = "";
		$bereich = "";
		$nr = (string) ($q["qid"] ?? "");
		$inCatalog = is_array($cat);
		if (is_array($cat)) {
			$catalogFrage = (string) $cat["frage"];
			$nr = (string) $cat["nr"];
			if ($frage === "") {
				$frage = $catalogFrage;
			} elseif (rkrk_dqs_fold($frage) !== rkrk_dqs_fold($catalogFrage)) {
				$drift = true;
			}
			if (!$drift) {
				$bereich = (string) $cat["bereich"];
			}
		}
		$options = array_values($slot["options"]);
		usort($options, static function ($a, $b) {
			$wa = (int) ($a["picked_wrong"] ?? 0);
			$wb = (int) ($b["picked_wrong"] ?? 0);
			if ($wa !== $wb) {
				return $wb <=> $wa;
			}
			return ((int) ($b["missed"] ?? 0)) <=> ((int) ($a["missed"] ?? 0));
		});
		$patterns = array_values($slot["patterns"]);
		usort($patterns, static function ($a, $b) {
			return ((int) ($b["n"] ?? 0)) <=> ((int) ($a["n"] ?? 0));
		});
		$patterns = array_slice($patterns, 0, 6);
		$top = isset($patterns[0]) ? (string) $patterns[0]["text"] : "";
		$topN = isset($patterns[0]) ? (int) $patterns[0]["n"] : 0;
		$loesung = (string) ($q["loesung"] ?? "");
		if ($loesung === "" && is_array($cat)) {
			$loesung = (string) $cat["loesung"];
		}
		$antworttext = (string) ($q["antworttext"] ?? "");
		if ($antworttext === "" && is_array($cat) && !$drift) {
			$antworttext = (string) ($cat["antworttext"] ?? "");
		}
		return array(
			"quiz_key" => (string) ($q["quiz_key"] ?? ""),
			"qid" => (string) ($q["qid"] ?? ""),
			"nr" => $nr,
			"frage" => $frage,
			"catalog_frage" => $catalogFrage,
			"drift" => $drift,
			"bereich" => $bereich,
			"in_catalog" => $inCatalog,
			"asked" => $asked,
			"full" => $full,
			"partial" => (int) $slot["partial"],
			"wrong" => (int) $slot["wrong"],
			"blank" => (int) $slot["blank"],
			"pct" => $pct,
			"urteil" => rkrk_dqs_urteil($asked, $full, $min, false),
			"loesung" => $loesung,
			"antworttext" => $antworttext,
			"options" => $options,
			"patterns" => $patterns,
			"top_wrong" => $top,
			"top_wrong_n" => $topN,
		);
	}
}

if (!function_exists("rkrk_dqs_build")) {
	/**
	 * @param array{quiz?:string,scope?:string,min?:int,sort?:string,fokus?:string,refresh?:bool} $opt
	 */
	function rkrk_dqs_build(array $opt): array {
		$quizFilter = trim((string) ($opt["quiz"] ?? ""));
		$scope = (string) ($opt["scope"] ?? "pruefung");
		if (!in_array($scope, array("pruefung", "schulung", "alle"), true)) {
			$scope = "pruefung";
		}
		$min = (int) ($opt["min"] ?? 1);
		if ($min < 1) {
			$min = 1;
		}
		$prosa = !empty($opt["prosa"]);
		$pdfKritisch = !empty($opt["pdf_kritisch"]);
		if ($min > 100) {
			$min = 100;
		}
		$sort = (string) ($opt["sort"] ?? "schwer");
		if (!in_array($sort, array("schwer", "haeufig", "leicht", "name"), true)) {
			$sort = "schwer";
		}
		$fokus = (string) ($opt["fokus"] ?? "alle");
		if (!in_array($fokus, array("alle", "schwer", "leicht", "wenig"), true)) {
			$fokus = "alle";
		}
		$agg = rkrk_dqs_load_agg(!empty($opt["refresh"]));
		$assessments = rkrk_dqs_assessments();
		$catalog = $quizFilter !== "" ? rkrk_dqs_catalog($quizFilter, $assessments) : array();
		$quizTitle = static function (string $key) use ($assessments): string {
			if (isset($assessments[$key]) && $assessments[$key]["titel"] !== "") {
				return (string) $assessments[$key]["titel"];
			}
			foreach ($assessments as $k => $meta) {
				if (strcasecmp($k, $key) === 0 && $meta["titel"] !== "") {
					return (string) $meta["titel"];
				}
			}
			if (function_exists("rkrk_assess_quiz_title_from_key")) {
				$t = rkrk_assess_quiz_title_from_key($key);
				if ($t !== "") {
					return $t;
				}
			}
			return $key;
		};
		$wantQuiz = static function (string $key) use ($quizFilter): bool {
			if ($quizFilter === "") {
				return true;
			}
			return strcasecmp($key, $quizFilter) === 0;
		};

		$rows = array();
		$seenQid = array();
		foreach (($agg["q"] ?? array()) as $q) {
			if (!is_array($q) || !$wantQuiz((string) ($q["quiz_key"] ?? ""))) {
				continue;
			}
			$slot = rkrk_dqs_merge_modi(isset($q["modi"]) && is_array($q["modi"]) ? $q["modi"] : array(), $scope);
			if ((int) $slot["asked"] < 1) {
				continue;
			}
			$qid = (string) ($q["qid"] ?? "");
			$seenQid[$qid] = true;
			$cat = isset($catalog[$qid]) ? $catalog[$qid] : null;
			$row = rkrk_dqs_row_from_slot($q, $slot, $min, $cat);
			$row["quiz_title"] = $quizTitle($row["quiz_key"]);
			$rows[] = $row;
		}
		if ($quizFilter !== "") {
			foreach ($catalog as $qid => $cat) {
				if (isset($seenQid[$qid])) {
					continue;
				}
				$empty = rkrk_dqs_empty_modus();
				$q = array(
					"quiz_key" => $quizFilter,
					"qid" => $qid,
					"frage" => (string) $cat["frage"],
					"loesung" => (string) $cat["loesung"],
					"antworttext" => (string) ($cat["antworttext"] ?? ""),
					"modi" => array(),
				);
				$row = rkrk_dqs_row_from_slot($q, $empty, $min, $cat);
				$row["urteil"] = "nie";
				$row["quiz_title"] = $quizTitle($quizFilter);
				$rows[] = $row;
			}
		}

		$counts = array("shown" => 0, "schwer" => 0, "leicht" => 0, "gleich" => 0, "wenig" => 0, "nie" => 0);
		foreach ($rows as $row) {
			$u = (string) $row["urteil"];
			if (isset($counts[$u])) {
				$counts[$u]++;
			}
		}
		$hard = array();
		$easy = array();
		foreach ($rows as $row) {
			if ($row["urteil"] === "schwer") {
				$hard[] = $row;
			} elseif ($row["urteil"] === "leicht") {
				$easy[] = $row;
			}
		}
		$cmp = static function ($a, $b) use ($sort) {
			$pa = $a["pct"];
			$pb = $b["pct"];
			$paN = $pa === null ? -1 : (float) $pa;
			$pbN = $pb === null ? -1 : (float) $pb;
			if ($sort === "haeufig") {
				if ((int) $a["asked"] !== (int) $b["asked"]) {
					return ((int) $b["asked"]) <=> ((int) $a["asked"]);
				}
				return $paN <=> $pbN;
			}
			if ($sort === "leicht") {
				if ($paN !== $pbN) {
					return $pbN <=> $paN;
				}
				return ((int) $b["asked"]) <=> ((int) $a["asked"]);
			}
			if ($sort === "name") {
				return strcasecmp((string) $a["frage"], (string) $b["frage"]);
			}
			if ($pa === null && $pb !== null) {
				return 1;
			}
			if ($pb === null && $pa !== null) {
				return -1;
			}
			if ($paN !== $pbN) {
				return $paN <=> $pbN;
			}
			return ((int) $b["asked"]) <=> ((int) $a["asked"]);
		};
		usort($hard, $cmp);
		usort($easy, $cmp);
		$visible = array();
		foreach ($rows as $row) {
			$u = (string) $row["urteil"];
			if ($fokus === "schwer" && $u !== "schwer") {
				continue;
			}
			if ($fokus === "leicht" && $u !== "leicht") {
				continue;
			}
			if ($fokus === "wenig" && $u !== "wenig" && $u !== "nie") {
				continue;
			}
			$visible[] = $row;
		}
		usort($visible, $cmp);

		$attempts = 0;
		$detail = 0;
		$scopes = $scope === "alle" ? array("pruefung", "schulung") : array($scope);
		foreach ($scopes as $sc) {
			$attempts += (int) ($agg["attempts"][$sc] ?? 0);
			$detail += (int) ($agg["attempts_detail"][$sc] ?? 0);
		}
		$quizOptions = array();
		foreach ($assessments as $key => $meta) {
			$quizOptions[$key] = $meta["titel"] !== "" ? $meta["titel"] : $key;
		}
		foreach (($agg["q"] ?? array()) as $q) {
			if (!is_array($q)) {
				continue;
			}
			$k = (string) ($q["quiz_key"] ?? "");
			if ($k !== "" && !isset($quizOptions[$k])) {
				$quizOptions[$k] = $quizTitle($k);
			}
		}
		asort($quizOptions, SORT_NATURAL | SORT_FLAG_CASE);

		return array(
			"built_at" => (string) ($agg["built_at"] ?? ""),
			"from_cache" => !empty($agg["from_cache"]),
			"files" => (int) ($agg["files"] ?? 0),
			"bad_files" => (int) ($agg["bad_files"] ?? 0),
			"attempts" => $attempts,
			"attempts_detail" => $detail,
			"quiz" => $quizFilter,
			"scope" => $scope,
			"min" => $min,
			"sort" => $sort,
			"fokus" => $fokus,
			"prosa" => $prosa,
			"pdf_kritisch" => $pdfKritisch,
			"quiz_options" => $quizOptions,
			"counts" => $counts,
			"hard" => $hard,
			"easy" => $easy,
			"rows" => $visible,
		);
	}
}

if (!function_exists("rkrk_dqs_pick_tone")) {
	/** bad = falsche Wahl, mid = unvollständig, muted = keine Antwort. */
	function rkrk_dqs_pick_tone(string $text): string {
		if (strncmp($text, "unvollständig:", 15) === 0) {
			return "mid";
		}
		if ($text === "keine Antwort") {
			return "muted";
		}
		return "bad";
	}
}

if (!function_exists("rkrk_dqs_pdf_h")) {
	function rkrk_dqs_pdf_h($value): string {
		return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists("rkrk_dqs_pdf_pct")) {
	function rkrk_dqs_pdf_pct($pct): string {
		if ($pct === null) {
			return "–";
		}
		return number_format((float) $pct, 1, ",", "") . " %";
	}
}

if (!function_exists("rkrk_dqs_pdf_clip")) {
	function rkrk_dqs_pdf_clip(string $text, int $max): string {
		$text = rkrk_dqs_plain($text);
		if ($max < 8) {
			return $text;
		}
		if (function_exists("mb_strlen") && mb_strlen($text, "UTF-8") > $max) {
			return mb_substr($text, 0, $max - 1, "UTF-8") . "…";
		}
		if (strlen($text) > $max) {
			return substr($text, 0, $max - 3) . "...";
		}
		return $text;
	}
}

if (!function_exists("rkrk_dqs_pdf_fit")) {
	function rkrk_dqs_pdf_fit(\TCPDF $pdf, string $text, float $width): string {
		$text = trim((string) preg_replace("/\s+/u", " ", rkrk_dqs_plain($text)));
		if ($text === "" || $width < 2) {
			return "";
		}
		if ($pdf->GetStringWidth($text) <= $width) {
			return $text;
		}
		$ellipsis = "…";
		$len = function_exists("mb_strlen") ? mb_strlen($text, "UTF-8") : strlen($text);
		$lo = 0;
		$hi = $len;
		while ($lo < $hi) {
			$mid = intdiv($lo + $hi + 1, 2);
			$slice = function_exists("mb_substr") ? mb_substr($text, 0, $mid, "UTF-8") : substr($text, 0, $mid);
			if ($pdf->GetStringWidth($slice . $ellipsis) <= $width) {
				$lo = $mid;
			} else {
				$hi = $mid - 1;
			}
		}
		$slice = function_exists("mb_substr") ? mb_substr($text, 0, $lo, "UTF-8") : substr($text, 0, $lo);
		return rtrim((string) $slice) . $ellipsis;
	}
}

if (!function_exists("rkrk_dqs_pdf_tone_rgb")) {
	/** @return array{0:int,1:int,2:int} */
	function rkrk_dqs_pdf_tone_rgb($pct): array {
		if ($pct === null) {
			return array(90, 98, 108);
		}
		$p = (float) $pct;
		if ($p >= 70) {
			return array(16, 72, 40);
		}
		if ($p >= 50) {
			return array(120, 80, 18);
		}
		return array(128, 36, 50);
	}
}

if (!function_exists("rkrk_dqs_pdf_tone_fill_rgb")) {
	/** Hellere Flächenfarbe, damit die Schrift daneben lesbar bleibt. */
	function rkrk_dqs_pdf_tone_fill_rgb($pct): array {
		if ($pct === null) {
			return array(176, 184, 176);
		}
		$p = (float) $pct;
		if ($p >= 70) {
			return array(118, 158, 128);
		}
		if ($p >= 50) {
			return array(196, 168, 104);
		}
		return array(196, 132, 138);
	}
}

if (!function_exists("rkrk_dqs_pdf_bar")) {
	function rkrk_dqs_pdf_bar(\TCPDF $pdf, float $x, float $y, float $w, float $h, $pct): void {
		$pdf->SetFillColor(226, 232, 224);
		$pdf->Rect($x, $y, $w, $h, "F");
		if ($pct === null || $w < 0.4) {
			return;
		}
		$p = max(0.0, min(100.0, (float) $pct));
		if ($p <= 0) {
			return;
		}
		$rgb = rkrk_dqs_pdf_tone_fill_rgb($p);
		$pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
		$pdf->Rect($x, $y, max(0.6, $w * $p / 100), $h, "F");
	}
}

if (!function_exists("rkrk_dqs_pdf_new_page")) {
	function rkrk_dqs_pdf_new_page(\TCPDF $pdf, string $font, string $running): void {
		$pdf->AddPage();
		$pdf->SetFont($font, "", 8);
		$pdf->SetTextColor(100, 116, 139);
		$pdf->Cell(0, 4.5, $running, 0, 1, "L");
		$pdf->SetTextColor(28, 36, 22);
	}
}

if (!function_exists("rkrk_dqs_pdf_room")) {
	function rkrk_dqs_pdf_room(\TCPDF $pdf, string $font, string $running, float $need): void {
		$limit = $pdf->getPageHeight() - 14;
		if ($pdf->GetY() + $need <= $limit) {
			return;
		}
		rkrk_dqs_pdf_new_page($pdf, $font, $running);
	}
}

if (!function_exists("rkrk_dqs_pdf_draw")) {
	function rkrk_dqs_pdf_draw(\TCPDF $pdf, array $view, string $font): void {
		$pdf->SetAutoPageBreak(false);
		$m = $pdf->getMargins();
		$x0 = (float) $m["left"];
		$w = $pdf->getPageWidth() - (float) $m["left"] - (float) $m["right"];
		$scopeLabel = array(
			"pruefung" => "Nur Prüfungen",
			"schulung" => "Nur Schulungen",
			"alle" => "Prüfungen und Schulungen",
		);
		$scope = (string) ($view["scope"] ?? "pruefung");
		$quiz = trim((string) ($view["quiz"] ?? ""));
		$counts = isset($view["counts"]) && is_array($view["counts"]) ? $view["counts"] : array();
		$schwer = (int) ($counts["schwer"] ?? 0);
		$leicht = (int) ($counts["leicht"] ?? 0);
		$gleich = (int) ($counts["gleich"] ?? 0);
		$wenig = (int) ($counts["wenig"] ?? 0) + (int) ($counts["nie"] ?? 0);
		$running = "Fragenauswertung · " . ($quiz !== "" ? $quiz : "Alle Assessments");

		$pdf->SetTextColor(28, 36, 22);
		$pdf->SetFont($font, "B", 16);
		$pdf->Cell(0, 7, "Fragenauswertung", 0, 1, "L");
		$pdf->SetFont($font, "", 8);
		$pdf->SetTextColor(80, 92, 74);
		$sub = ($scopeLabel[$scope] ?? $scope)
			. " · " . ($quiz !== "" ? $quiz : "Alle Assessments")
			. " · mindestens " . (int) ($view["min"] ?? 1) . " Abfragen"
			. " · " . (int) ($view["attempts_detail"] ?? 0) . " Versuche mit Einzelfragen"
			. (!empty($view["pdf_kritisch"]) ? " · nur kritische Fragen" : "")
			. " · " . date("d.m.Y");
		$pdf->Cell(0, 4.5, rkrk_dqs_pdf_fit($pdf, $sub, $w), 0, 1, "L");
		$pdf->Ln(1.5);

		$kpis = array(
			array((string) (int) ($view["attempts_detail"] ?? 0), "Versuche mit Einzelfragen", array(244, 247, 243), array(28, 36, 22)),
			array((string) $schwer, "Mehrheitlich falsch", array(252, 246, 246), array(128, 36, 50)),
			array((string) $leicht, "Mehrheitlich richtig", array(244, 249, 243), array(16, 72, 40)),
			array((string) $wenig, "Unter der Mindestzahl", array(241, 245, 249), array(71, 85, 105)),
		);
		$gap = 3.0;
		$boxW = ($w - 3 * $gap) / 4;
		$boxH = 16.0;
		$y = $pdf->GetY();
		foreach ($kpis as $i => $kpi) {
			$bx = $x0 + $i * ($boxW + $gap);
			$pdf->SetFillColor($kpi[2][0], $kpi[2][1], $kpi[2][2]);
			$pdf->RoundedRect($bx, $y, $boxW, $boxH, 1.6, "1111", "F");
			$pdf->SetFont($font, "B", 15);
			$pdf->SetTextColor($kpi[3][0], $kpi[3][1], $kpi[3][2]);
			$pdf->SetXY($bx + 3, $y + 1.2);
			$pdf->Cell($boxW - 6, 7, $kpi[0], 0, 0, "L");
			$pdf->SetFont($font, "", 8);
			$pdf->SetTextColor(70, 80, 64);
			$pdf->SetXY($bx + 3, $y + 8.6);
			$pdf->Cell($boxW - 6, 5, rkrk_dqs_pdf_fit($pdf, $kpi[1], $boxW - 6), 0, 0, "L");
		}
		$pdf->SetY($y + $boxH + 3);

		$total = $schwer + $leicht + $gleich + $wenig;
		$parts = array(
			array($schwer, array(196, 132, 138), "falsch"),
			array($gleich, array(196, 168, 104), "gleichauf"),
			array($leicht, array(118, 158, 128), "richtig"),
			array($wenig, array(148, 163, 184), "wenig / nie"),
		);
		$y = $pdf->GetY();
		$barH = 5.5;
		if ($total > 0) {
			$cursor = $x0;
			foreach ($parts as $part) {
				$seg = $w * ((int) $part[0]) / $total;
				if ($seg <= 0) {
					continue;
				}
				$pdf->SetFillColor($part[1][0], $part[1][1], $part[1][2]);
				$pdf->Rect($cursor, $y, $seg, $barH, "F");
				$cursor += $seg;
			}
		} else {
			$pdf->SetFillColor(226, 232, 224);
			$pdf->Rect($x0, $y, $w, $barH, "F");
		}
		$lx = $x0;
		$ly = $y + $barH + 1.6;
		$pdf->SetFont($font, "", 7.5);
		foreach ($parts as $part) {
			$pdf->SetFillColor($part[1][0], $part[1][1], $part[1][2]);
			$pdf->Rect($lx, $ly + 0.7, 3.2, 2.4, "F");
			$pdf->SetTextColor(55, 65, 50);
			$pdf->SetXY($lx + 4.2, $ly);
			$label = (int) $part[0] . " " . $part[2];
			$pdf->Cell(36, 4, $label, 0, 0, "L");
			$lx += 42;
		}
		$pdf->SetY($ly + 6);
		$pdf->SetTextColor(28, 36, 22);

		$pdf->SetFont($font, "B", 12);
		$pdf->Cell(0, 6, "Schwierige Fragen", 0, 1, "L");
		$pdf->SetFont($font, "", 8);
		$pdf->SetTextColor(80, 92, 74);
		$pdf->Cell(0, 4, "Grün = richtige Lösung, Rot = falsche Wahl, Gelb = unvollständig. Jede Frage steht für sich.", 0, 1, "L");
		$pdf->Ln(1);
		$pdf->SetTextColor(28, 36, 22);

		$hard = isset($view["hard"]) && is_array($view["hard"]) ? $view["hard"] : array();
		if ($hard === array()) {
			$pdf->SetFont($font, "", 9);
			$pdf->Cell(0, 5, "Keine Frage ist bei dieser Mindestzahl mehrheitlich falsch.", 0, 1, "L");
		}
		$prosa = !empty($view["prosa"]);
		foreach ($hard as $row) {
			if (!is_array($row)) {
				continue;
			}
			rkrk_dqs_pdf_hard_card($pdf, $font, $row, $prosa, $x0, $w, $running);
		}

		if (empty($view["pdf_kritisch"])) {
		$pdf->Ln(2);
		rkrk_dqs_pdf_room($pdf, $font, $running, 18);
		$pdf->SetFont($font, "B", 12);
		$pdf->SetTextColor(28, 36, 22);
		$rows = isset($view["rows"]) && is_array($view["rows"]) ? $view["rows"] : array();
		$pdf->Cell(0, 6, "Fragenliste (" . count($rows) . ")", 0, 1, "L");
		$pdf->SetFont($font, "", 8);
		$pdf->SetTextColor(80, 92, 74);
		$pdf->Cell(0, 4, "Eine Zeile pro Frage. Der Balken ist der Anteil voll richtiger Antworten.", 0, 1, "L");
		$pdf->Ln(1);
		rkrk_dqs_pdf_list($pdf, $font, $rows, $x0, $w, $running, $quiz === "");
		}
	}
}

if (!function_exists("rkrk_dqs_pdf_hard_card")) {
	function rkrk_dqs_pdf_hard_card(\TCPDF $pdf, string $font, array $row, bool $prosa, float $x, float $w, string $running): void {
		$pct = $row["pct"] ?? null;
		$rgb = rkrk_dqs_pdf_tone_rgb($pct);
		$patterns = array();
		foreach (array_slice(isset($row["patterns"]) && is_array($row["patterns"]) ? $row["patterns"] : array(), 0, 2) as $pat) {
			if (!is_array($pat)) {
				continue;
			}
			$patText = (string) ($pat["text"] ?? "");
			$tone = rkrk_dqs_pick_tone($patText);
			if (strncmp($patText, "unvollständig:", 15) === 0) {
				$patText = trim(substr($patText, 15));
			}
			$label = $tone === "mid" ? "Teilweise" : ($tone === "muted" ? "Keine Antwort" : "Falsch");
			$patterns[] = array($tone, $label . "   " . (int) ($pat["n"] ?? 0) . "×   " . $patText);
		}
		$hasSol = trim((string) ($row["loesung"] ?? "")) !== "";
		$hasProse = $prosa && trim((string) ($row["antworttext"] ?? "")) !== "";
		$h = 8.2 + 4.4 + ($hasSol ? 4.4 : 0) + (count($patterns) * 4.4) + ($hasProse ? 4.4 : 0) + 2.2;
		rkrk_dqs_pdf_room($pdf, $font, $running, $h + 1.6);
		$y = $pdf->GetY();
		$bg = ($pct !== null && (float) $pct < 50) ? array(255, 248, 248) : array(255, 255, 255);
		$pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
		$pdf->SetDrawColor(213, 224, 207);
		$pdf->Rect($x, $y, $w, $h, "DF");
		$stripe = rkrk_dqs_pdf_tone_fill_rgb($pct);
		$pdf->SetFillColor($stripe[0], $stripe[1], $stripe[2]);
		$pdf->Rect($x, $y, 2.4, $h, "F");

		rkrk_dqs_pdf_bar($pdf, $x + 5, $y + 2.6, 24, 3.1, $pct);
		$pdf->SetFont($font, "B", 9);
		$pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
		$pdf->SetXY($x + 31, $y + 2.1);
		$pdf->Cell(18, 4.2, rkrk_dqs_pdf_pct($pct), 0, 0, "L");
		$pdf->SetTextColor(28, 36, 22);
		$pdf->SetFont($font, "B", 9.5);
		$q = rkrk_dqs_pdf_fit($pdf, (string) ($row["frage"] ?? ""), $w - 56);
		$pdf->SetXY($x + 50, $y + 2.1);
		$pdf->Cell($w - 54, 4.2, $q, 0, 0, "L");

		$meta = (int) ($row["full"] ?? 0) . "/" . (int) ($row["asked"] ?? 0);
		$qk = trim((string) ($row["quiz_key"] ?? ""));
		if ($qk !== "") {
			$meta .= "  ·  " . $qk;
		}
		$nr = trim((string) ($row["nr"] ?? ""));
		if ($nr !== "") {
			$meta .= "  ·  ID " . $nr;
		}
		$bereich = trim((string) ($row["bereich"] ?? ""));
		if ($bereich !== "") {
			$meta .= "  ·  " . $bereich;
		}
		$cy = $y + 8.0;
		$pdf->SetFont($font, "", 7.5);
		$pdf->SetTextColor(90, 102, 84);
		$pdf->SetXY($x + 5, $cy);
		$pdf->Cell($w - 10, 4, rkrk_dqs_pdf_fit($pdf, $meta, $w - 10), 0, 0, "L");
		$cy += 4.4;

		if ($hasSol) {
			$pdf->SetFont($font, "", 8);
			$pdf->SetTextColor(16, 72, 40);
			$pdf->SetXY($x + 5, $cy);
			$pdf->Cell($w - 10, 4, rkrk_dqs_pdf_fit($pdf, "Richtig    " . (string) $row["loesung"], $w - 10), 0, 0, "L");
			$cy += 4.4;
		}
		foreach ($patterns as $pat) {
			$tone = $pat[0];
			$col = $tone === "mid" ? array(120, 80, 18) : ($tone === "muted" ? array(71, 85, 105) : array(128, 36, 50));
			$pdf->SetFont($font, "", 8);
			$pdf->SetTextColor($col[0], $col[1], $col[2]);
			$pdf->SetXY($x + 5, $cy);
			$pdf->Cell($w - 10, 4, rkrk_dqs_pdf_fit($pdf, $pat[1], $w - 10), 0, 0, "L");
			$cy += 4.4;
		}
		if ($hasProse) {
			$pdf->SetFont($font, "", 7.5);
			$pdf->SetTextColor(71, 85, 105);
			$pdf->SetXY($x + 5, $cy);
			$pdf->Cell($w - 10, 4, rkrk_dqs_pdf_fit($pdf, "Antworttext    " . (string) $row["antworttext"], $w - 10), 0, 0, "L");
		}
		$pdf->SetY($y + $h + 2.0);
		$pdf->SetTextColor(28, 36, 22);
	}
}

if (!function_exists("rkrk_dqs_pdf_list")) {
	function rkrk_dqs_pdf_list(\TCPDF $pdf, string $font, array $rows, float $x, float $w, string $running, bool $showQuiz): void {
		$nW = 11.0;
		$numW = 11.0;
		$barW = 32.0;
		$wrongW = 78.0;
		$quizW = $showQuiz ? 26.0 : 0.0;
		$frageW = $w - $barW - $nW - (3 * $numW) - $wrongW - $quizW;
		$cols = array(
			array("Quote", $barW, "L"),
			array("Frage", $frageW, "L"),
		);
		if ($showQuiz) {
			$cols[] = array("Quiz", $quizW, "L");
		}
		$cols[] = array("n", $nW, "R");
		$cols[] = array("R", $numW, "R");
		$cols[] = array("T", $numW, "R");
		$cols[] = array("F", $numW, "R");
		$cols[] = array("Fehlantwort", $wrongW, "L");

		$drawHead = function () use ($pdf, $font, $x, $cols): void {
			$pdf->SetFillColor(231, 240, 227);
			$pdf->SetTextColor(28, 36, 22);
			$pdf->SetFont($font, "B", 7.5);
			$pdf->SetX($x);
			foreach ($cols as $col) {
				$pdf->Cell($col[1], 5.5, $col[0], 0, 0, $col[2], true);
			}
			$pdf->Ln(5.5);
		};
		$drawHead();
		if ($rows === array()) {
			$pdf->SetFont($font, "", 8);
			$pdf->Cell(0, 5, "Keine Fragen in dieser Sicht.", 0, 1, "L");
			return;
		}
		$i = 0;
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$limit = $pdf->getPageHeight() - 14;
			if ($pdf->GetY() + 5.4 > $limit) {
				rkrk_dqs_pdf_new_page($pdf, $font, $running);
				$drawHead();
			}
			$pct = $row["pct"] ?? null;
			$falsch = (int) ($row["wrong"] ?? 0) + (int) ($row["blank"] ?? 0);
			$wrong = trim((string) ($row["top_wrong"] ?? ""));
			if ($wrong !== "" && (int) ($row["top_wrong_n"] ?? 0) > 0) {
				$wrong .= " (" . (int) $row["top_wrong_n"] . "×)";
			}
			$fill = ($i % 2) === 0;
			$bg = $fill ? array(248, 249, 247) : array(255, 255, 255);
			$ink = array(20, 24, 20);
			$pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
			$pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
			$pdf->SetFont($font, "", 7.5);
			$pdf->SetX($x);
			$y = $pdf->GetY();
			$pdf->Cell($barW, 5.2, "", 0, 0, "L", true);
			rkrk_dqs_pdf_bar($pdf, $x + 1.2, $y + 1.7, 14, 1.8, $pct);
			$pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
			$rgb = rkrk_dqs_pdf_tone_rgb($pct);
			$pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
			$pdf->SetFont($font, "B", 7);
			$pdf->SetXY($x + 16.2, $y);
			$pdf->Cell($barW - 17, 5.2, rkrk_dqs_pdf_pct($pct), 0, 0, "L", false);
			$pdf->SetFont($font, "", 7.5);
			$pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
			$pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
			$frage = (string) ($row["frage"] ?? "");
			$nr = trim((string) ($row["nr"] ?? ""));
			if ($nr !== "") {
				$frage = "ID " . $nr . "  " . $frage;
			}
			$pdf->SetXY($x + $barW, $y);
			$pdf->Cell($frageW, 5.2, rkrk_dqs_pdf_fit($pdf, $frage, $frageW - 1.5), 0, 0, "L", true);
			$cx = $x + $barW + $frageW;
			if ($showQuiz) {
				$pdf->SetXY($cx, $y);
				$pdf->Cell($quizW, 5.2, rkrk_dqs_pdf_fit($pdf, (string) ($row["quiz_key"] ?? ""), $quizW - 1), 0, 0, "L", true);
				$cx += $quizW;
			}
			$pdf->SetXY($cx, $y);
			$pdf->Cell($nW, 5.2, (string) (int) ($row["asked"] ?? 0), 0, 0, "R", true);
			$cx += $nW;
			$pdf->SetTextColor(16, 72, 40);
			$pdf->SetXY($cx, $y);
			$pdf->Cell($numW, 5.2, (string) (int) ($row["full"] ?? 0), 0, 0, "R", true);
			$cx += $numW;
			$pdf->SetTextColor((int) ($row["partial"] ?? 0) > 0 ? 120 : 28, (int) ($row["partial"] ?? 0) > 0 ? 80 : 36, (int) ($row["partial"] ?? 0) > 0 ? 18 : 22);
			$pdf->SetXY($cx, $y);
			$pdf->Cell($numW, 5.2, (string) (int) ($row["partial"] ?? 0), 0, 0, "R", true);
			$cx += $numW;
			$pdf->SetTextColor($falsch > 0 ? 128 : 28, $falsch > 0 ? 36 : 36, $falsch > 0 ? 50 : 22);
			$pdf->SetXY($cx, $y);
			$pdf->Cell($numW, 5.2, (string) $falsch, 0, 0, "R", true);
			$cx += $numW;
			$pdf->SetTextColor($wrong !== "" ? 128 : 100, $wrong !== "" ? 36 : 116, $wrong !== "" ? 50 : 139);
			$pdf->SetXY($cx, $y);
			$pdf->Cell($wrongW, 5.2, $wrong !== "" ? rkrk_dqs_pdf_fit($pdf, $wrong, $wrongW - 1.5) : "–", 0, 0, "L", true);
			$pdf->SetY($y + 5.2);
			$i++;
		}
		$pdf->SetTextColor(28, 36, 22);
	}
}

if (!function_exists("rkrk_dqs_send_pdf")) {
	function rkrk_dqs_send_pdf(array $view): bool {
		if (function_exists("set_time_limit")) {
			@set_time_limit(180);
		}
		// TCPDF 6.10 wertet cURL-Optionen als Klassenkonstanten aus, auch ohne php-curl.
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
		if (!class_exists("TCPDF", false)) {
			$tcpdf = __DIR__ . "/vendor/tcpdf/tcpdf.php";
			if (is_readable($tcpdf)) {
				require_once $tcpdf;
			}
		}
		if (!class_exists("TCPDF", false) && function_exists("rkrk_assess_try_load_tcpdf")) {
			rkrk_assess_try_load_tcpdf();
		}
		if (!class_exists("TCPDF", false)) {
			$inc = __DIR__ . "/rkrk_assess_report_pdf.inc.php";
			if (is_readable($inc)) {
				include_once $inc;
			}
			if (function_exists("rkrk_assess_try_load_tcpdf")) {
				rkrk_assess_try_load_tcpdf();
			}
		}
		if (!class_exists("TCPDF", false)) {
			$GLOBALS["rkrk_dqs_pdf_error"] = "TCPDF nicht geladen";
			return false;
		}
		$prevDisplay = ini_get("display_errors");
		ini_set("display_errors", "0");
		$obLevel = ob_get_level();
		ob_start();
		$out = "";
		try {
			$pdf = new \TCPDF("L", "mm", "A4", true, "UTF-8", false);
			$pdf->SetCreator("RKRS Fragenauswertung");
			$pdf->SetAuthor("RKRS");
			$pdf->SetTitle("Fragenauswertung");
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(true);
			$pdf->SetMargins(10, 10, 10);
			$pdf->SetAutoPageBreak(true, 12);
			$pdf->SetFooterMargin(8);
			$pdf->AddPage();
			$fontInc = __DIR__ . "/rkrk_assess_report_pdf.inc.php";
			if (!function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font") && is_readable($fontInc)) {
				include_once $fontInc;
			}
			$font = function_exists("rkrk_assess_report_tcpdf_ensure_unicode_font")
				? rkrk_assess_report_tcpdf_ensure_unicode_font($pdf)
				: "helvetica";
			if ($font === "") {
				$font = "helvetica";
			}
			$pdf->SetFont($font, "", 9);
			$pdf->setFooterFont(array($font, "", 8));
			rkrk_dqs_pdf_draw($pdf, $view, $font);
			$rendered = $pdf->Output("", "S");
			$out = is_string($rendered) ? $rendered : "";
		} catch (\Throwable $e) {
			$GLOBALS["rkrk_dqs_pdf_error"] = $e->getMessage();
			$out = "";
		}
		while (ob_get_level() > $obLevel) {
			@ob_end_clean();
		}
		if ($prevDisplay !== false) {
			ini_set("display_errors", (string) $prevDisplay);
		}
		if ($out === "" || strncmp($out, "%PDF", 4) !== 0) {
			if (empty($GLOBALS["rkrk_dqs_pdf_error"])) {
				$GLOBALS["rkrk_dqs_pdf_error"] = "Leere PDF-Ausgabe";
			}
			return false;
		}
		$quiz = trim((string) ($view["quiz"] ?? ""));
		$slug = $quiz !== "" ? $quiz : "alle";
		$slug = preg_replace("/[^A-Za-z0-9_-]+/", "-", $slug);
		$slug = trim((string) $slug, "-");
		if ($slug === "") {
			$slug = "alle";
		}
		$scope = (string) ($view["scope"] ?? "pruefung");
		if (!in_array($scope, array("pruefung", "schulung", "alle"), true)) {
			$scope = "pruefung";
		}
		$filename = date("Ymd") . "-Fragenauswertung_" . $scope . "_" . $slug . (!empty($view["pdf_kritisch"]) ? "_kritisch" : "") . ".pdf";
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		if (!headers_sent()) {
			header("Content-Type: application/pdf");
			header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
			header("Content-Length: " . (string) strlen($out));
			header("Cache-Control: private, max-age=0");
		}
		echo $out;
		exit;
	}
}
