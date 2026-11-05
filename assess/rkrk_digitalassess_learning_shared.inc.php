<?php
/**
 * Schulungstext speichern / zerlegen — gemeinsam für Admin und rkrk_digitalassess_learning_edit.php
 */

if (!function_exists("rkrk_dle_learning_editor_parts")) {
	function rkrk_dle_learning_editor_parts($html) {
		$html = (string) $html;
		// Kopf (PHP-Gate, DOCTYPE, charset, CSS) und Abschluss bleiben erhalten.
		// Nur der Inhalt zwischen <body> und </body> geht in den Editor.
		if (preg_match("~^(.*?<body[^>]*>)(.*?)(</body>.*)$~is", $html, $m)) {
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

if (!function_exists("rkrk_dle_clean_image_rel")) {
	function rkrk_dle_clean_image_rel(string $rel): string {
		$rel = str_replace("\\", "/", rawurldecode($rel));
		$rel = rawurldecode($rel);
		$rel = ltrim($rel, "/");
		if ($rel === "" || strpos($rel, "..") !== false) {
			return "";
		}
		return $rel;
	}
}

if (!function_exists("rkrk_dle_proxy_src_to_images_rel")) {
	/**
	 * Proxy-, Absolut- oder Host-URL einer Lernabbildung → images/… neben der HTML-Datei.
	 * Gilt für frog und rehkitzrettung.ch, auch wenn der Editor die URL absolut macht.
	 */
	function rkrk_dle_proxy_src_to_images_rel(string $url): string {
		$orig = $url;
		$u = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, "UTF-8");
		$u = trim(str_replace(array("&amp;", "\\"), array("&", "/"), $u));
		if ($u === "" || $u[0] === "#" || preg_match('#^(mailto:|tel:|data:)#i', $u) === 1) {
			return $orig;
		}
		if (preg_match('~^(?:\./)?images/([^?#]+)$~i', $u, $already) === 1) {
			$rel = rkrk_dle_clean_image_rel((string) $already[1]);
			return $rel === "" ? $orig : ("images/" . $rel);
		}
		if (stripos($u, "rkrk_assess_image.php") !== false
			&& preg_match('~rkrk_assess_image\\.php\\?([^#\\s"\']+)~i', $u, $qm) === 1
		) {
			$params = array();
			parse_str((string) $qm[1], $params);
			$f = rkrk_dle_clean_image_rel((string) ($params["f"] ?? ""));
			$d = trim(str_replace("\\", "/", rawurldecode((string) ($params["d"] ?? ""))), "/");
			if ($f === "" || strpos($d, "..") !== false) {
				return $orig;
			}
			$f = str_replace("/", "", $f);
			return "images/" . ($d !== "" ? $d . "/" : "") . $f;
		}
		if (preg_match('~/(?:quiz/)?images/([^?#]+)~i', $u, $im) === 1
			&& (stripos($u, "data/assess") !== false || stripos($u, "rkrsplusassessment") !== false)
		) {
			$rel = rkrk_dle_clean_image_rel((string) $im[1]);
			return $rel === "" ? $orig : ("images/" . $rel);
		}
		return $orig;
	}
}

if (!function_exists("rkrk_dle_relativize_learn_image_urls")) {
	/**
	 * Gespeicherte Schulungs-HTML bleibt host-neutral: images/Ordner/Datei.png
	 * funktioniert auf frog und auf rehkitzrettung.ch.
	 */
	function rkrk_dle_relativize_learn_image_urls(string $html): string {
		$out = preg_replace_callback(
			'/\b(src|data-src|poster)\s*=\s*(["\'])([^"\']+)\2/i',
			static function ($m) {
				$rel = rkrk_dle_proxy_src_to_images_rel((string) $m[3]);
				if ($rel === (string) $m[3]) {
					return $m[0];
				}
				return $m[1] . "=" . $m[2] . htmlspecialchars($rel, ENT_QUOTES, "UTF-8") . $m[2];
			},
			$html
		);
		return is_string($out) ? $out : $html;
	}
}

if (!function_exists("rkrk_dle_learning_needs_php_gate")) {
	function rkrk_dle_learning_needs_php_gate(string $learnFile, string $html): bool {
		$base = basename(str_replace("\\", "/", $learnFile));
		if (in_array($base, array("RKRSplus_Schulung.html", "RKRS Einsatzpruefung.html"), true)) {
			return true;
		}
		return strpos($html, "_hash_protect.php") !== false;
	}
}

if (!function_exists("rkrk_dle_ensure_learning_document_shell")) {
	/**
	 * Direkter Aufruf der .html (als PHP) braucht charset-Header plus Stylesheet-Kopf.
	 * Der Editor darf diesen Rahmen nicht durch den Body-Inhalt ersetzen.
	 */
	function rkrk_dle_ensure_learning_document_shell(string $html, string $learnFile): string {
		$needsPhp = rkrk_dle_learning_needs_php_gate($learnFile, $html);
		$hasCharset = preg_match('/<meta\s+charset\s*=\s*["\']?\s*utf-8/i', $html) === 1;
		$hasCss = stripos($html, "lernunterlagen.css") !== false;
		$hasPhp = strpos($html, "_hash_protect.php") !== false;
		if ($hasCharset && $hasCss && (!$needsPhp || $hasPhp)) {
			if (stripos($html, "</html>") === false && preg_match('/<\/body>/i', $html) === 1) {
				$html = preg_replace('/<\/body>/i', "</body>\n</html>", $html, 1);
			}
			return $html;
		}
		$inner = $html;
		if (preg_match('~<body[^>]*>(.*)</body>~is', $html, $m)) {
			$inner = $m[1];
		}
		$title = "";
		if (preg_match('~<h1[^>]*>(.*?)</h1>~is', $inner, $hm)) {
			$title = trim(html_entity_decode(strip_tags($hm[1]), ENT_QUOTES, "UTF-8"));
		}
		$titleTag = $title !== "" ? "<title>" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "</title>\n" : "";
		$php = $needsPhp ? "<?php require __DIR__ . '/../rkrsplus_ep/_hash_protect.php'; ?>\n" : "";
		$prefix = $php
			. "<!DOCTYPE html>\n<html lang=\"de\">\n<head>\n"
			. "<!-- RKRS Schulungs-Standard: lernunterlagen.css + Tabellen/Ergänzungen -->\n"
			. "<meta charset=\"UTF-8\">\n"
			. $titleTag
			. "<link rel=\"stylesheet\" href=\"lernunterlagen.css\">\n"
			. "<link rel=\"stylesheet\" href=\"public/rkrk_schulung_lern.css\">\n"
			. "<link rel=\"icon\" type=\"image/x-icon\" href=\"RKRS.ico\" id=\"rkrs-favicon\">\n"
			. "<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"RKRS_onrkrs.png\" id=\"rkrs-favicon-16\">\n"
			. "<script src=\"favicon_env.js\"></script>\n"
			. "<script src=\"portal_protect.js\"></script>\n"
			. "</head>\n<body>";
		return $prefix . $inner . "</body>\n</html>\n";
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
		if ($learnFile !== "" && function_exists("rkrk_dle_relativize_learn_image_urls")) {
			$saveParts = rkrk_dle_learning_editor_parts($html);
			$body = (string) $saveParts["body"];
			$body = preg_replace('#/data/assess/quiz/images/#i', "/data/assess/images/", $body);
			$body = preg_replace('#(["\'(])quiz/images/#i', '$1images/', (string) $body);
			$body = rkrk_dle_relativize_learn_image_urls((string) $body);
			$saveParts["body"] = $body;
			$html = rkrk_dle_learning_rebuild_from_post(
				$body,
				(string) ($saveParts["prefix_b64"] ?? ""),
				(string) ($saveParts["suffix_b64"] ?? ""),
				!empty($saveParts["fragment"])
			);
			$learnBody = $body;
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

		$html = rkrk_dle_ensure_learning_document_shell($html, $learnFile);
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
