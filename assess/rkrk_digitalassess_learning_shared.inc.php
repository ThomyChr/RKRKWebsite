<?php
/**
 * Schulungstext speichern / zerlegen — gemeinsam für Admin und rkrk_digitalassess_learning_edit.php
 */

if (!function_exists("rkrk_dle_learning_editor_parts")) {
	function rkrk_dle_learning_editor_parts($html) {
		$html = (string) $html;
		if (preg_match("~(<body[^>]*>)(.*?)(</body>)~is", $html, $m)) {
			return array(
				"body" => $m[2],
				"prefix_b64" => base64_encode($m[1]),
				"suffix_b64" => base64_encode($m[3]),
				"fragment" => false,
			);
		}
		return array(
			"body" => $html,
			"prefix_b64" => "",
			"suffix_b64" => "",
			"fragment" => true,
		);
	}
}

if (!function_exists("rkrk_dle_learning_rebuild_from_post")) {
	function rkrk_dle_learning_rebuild_from_post($body, $prefixB64, $suffixB64, $isFragment) {
		$body = (string) $body;
		if ($isFragment) {
			return $body;
		}
		$prefix = $prefixB64 !== "" ? base64_decode((string) $prefixB64, true) : false;
		$suffix = $suffixB64 !== "" ? base64_decode((string) $suffixB64, true) : false;
		if ($prefix === false || $suffix === false) {
			return $body;
		}
		return $prefix . $body . $suffix;
	}
}

if (!function_exists("rkrk_dle_normalize_key")) {
	function rkrk_dle_normalize_key($value, $fallbackTitle = "") {
		$value = trim((string) $value);
		if ($value === "") {
			$value = trim((string) $fallbackTitle);
		}
		if ($value === "") {
			return "";
		}
		$value = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $value);
		$value = preg_replace('/-+/', '-', (string) $value);
		return trim((string) $value, '-_');
	}
}

if (!function_exists("rkrk_dle_clean_relative_file")) {
	function rkrk_dle_clean_relative_file($value, $defaultExt = "csv") {
		$value = str_replace("\\", "/", trim((string) $value));
		$value = ltrim($value, "/");
		$value = preg_replace('/\.\.+/', '', (string) $value);
		if ($value === "") {
			return "";
		}
		if (preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) !== 1) {
			return "";
		}
		if (strpos($value, ".") === false && $defaultExt !== "") {
			$value .= "." . $defaultExt;
		}
		return $value;
	}
}

if (!function_exists("rkrk_dle_process_save_learning_html")) {
	/**
	 * Speichert Schulungs-HTML (manuell + Autosave-AJAX). Beendet bei Autosave-AJAX mit JSON.
	 *
	 * @param array<int, array<string, string>> $assessments
	 * @param array<string, int> $assessmentByKey
	 * @return array{message:string, error:string, learn_file:string, learning_path:string, learning_content:string, learn_editor:array, body_hash:string}
	 */
	function rkrk_dle_process_save_learning_html(
		string $quizLoc,
		string $assessmentPath,
		array &$assessments,
		array $assessmentHeaders,
		string $selectedKey,
		array $assessmentByKey,
		array $post,
		callable $writeCsvAssoc
	): array {
		$out = array(
			"message" => "",
			"error" => "",
			"learn_file" => "",
			"learning_path" => "",
			"learning_content" => "",
			"learn_editor" => array("body" => "", "prefix_b64" => "", "suffix_b64" => "", "fragment" => true),
			"body_hash" => "",
		);
		$autosaveAjax = (string) ($post["learning_autosave_ajax"] ?? "") === "1";
		$learnFile = isset($post["learning_file"]) ? rkrk_dle_clean_relative_file($post["learning_file"], "html") : "";
		if ($learnFile === "" && isset($post["learning_file_fallback"])) {
			$learnFile = rkrk_dle_clean_relative_file($post["learning_file_fallback"], "html");
		}
		if ($learnFile === "") {
			$learnFile = rkrk_dle_normalize_key($selectedKey) . ".html";
		}
		$learnBody = isset($post["learning_html"]) ? (string) $post["learning_html"] : "";
		$html = rkrk_dle_learning_rebuild_from_post(
			$learnBody,
			(string) ($post["learning_html_doc_prefix_b64"] ?? ""),
			(string) ($post["learning_html_doc_suffix_b64"] ?? ""),
			(string) ($post["learning_html_fragment"] ?? "") === "1"
		);
		if ($learnFile !== "" && function_exists("rkrk_dga_rh_normalize_learn_inner_html")) {
			$saveParts = rkrk_dle_learning_editor_parts($html);
			$saveParts["body"] = rkrk_dga_rh_normalize_learn_inner_html((string) $saveParts["body"], $learnFile);
			$html = rkrk_dle_learning_rebuild_from_post(
				(string) $saveParts["body"],
				(string) ($saveParts["prefix_b64"] ?? ""),
				(string) ($saveParts["suffix_b64"] ?? ""),
				!empty($saveParts["fragment"])
			);
			$learnBody = (string) $saveParts["body"];
		}
		$baselineHash = trim((string) ($post["learning_html_baseline_hash"] ?? ""));
		$currentBodyHash = md5($learnBody);
		$out["body_hash"] = $currentBodyHash;
		$out["learn_file"] = $learnFile;

		if ($autosaveAjax && $baselineHash !== "" && $baselineHash === $currentBodyHash) {
			if (!headers_sent()) {
				header("Content-Type: application/json; charset=UTF-8");
			}
			echo json_encode(array(
				"ok" => true,
				"skipped" => true,
				"message" => "Keine Änderungen.",
				"content_hash" => $currentBodyHash,
			), JSON_UNESCAPED_UNICODE);
			exit;
		}

		$targetLearningPath = $quizLoc . $learnFile;
		if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
			rkrk_assess_fragenkatalog_before_overwrite($targetLearningPath, $quizLoc);
		}
		if (@file_put_contents($targetLearningPath, $html) === false) {
			$out["error"] = "Schulungstext konnte nicht gespeichert werden.";
		} else {
			if ($autosaveAjax) {
				if (function_exists("rkrk_assess_fragenkatalog_archive_autosave")) {
					rkrk_assess_fragenkatalog_archive_autosave($targetLearningPath, $quizLoc);
				}
				$out["message"] = "Autosave Schulungstext.";
			} else {
				$out["message"] = "Schulungstext gespeichert.";
			}
			$out["learning_path"] = $targetLearningPath;
			$out["learning_content"] = $html;
			$out["learn_editor"] = rkrk_dle_learning_editor_parts($html);
			if (!$autosaveAjax && $selectedKey !== "" && isset($assessmentByKey[$selectedKey])) {
				$assessments[(int) $assessmentByKey[$selectedKey]]["LernDatei"] = $learnFile;
				if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
					rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $quizLoc);
				}
				$writeCsvAssoc($assessmentPath, $assessmentHeaders, $assessments);
			}
		}

		if ($autosaveAjax) {
			if (!headers_sent()) {
				header("Content-Type: application/json; charset=UTF-8");
			}
			if ($out["error"] !== "") {
				echo json_encode(array("ok" => false, "error" => $out["error"]), JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(array(
					"ok" => true,
					"message" => $out["message"],
					"content_hash" => $currentBodyHash,
					"saved_at" => date("c"),
				), JSON_UNESCAPED_UNICODE);
			}
			exit;
		}

		return $out;
	}
}
