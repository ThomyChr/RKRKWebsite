<?php
/**
 * Lesemodus-Markup für rkrk_digitalassess.php (?read_only=1 / ?Lesemodus=1).
 */

if (!function_exists('rkrk_dga_rh')) {
	function rkrk_dga_rh($v) {
		return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
	}
}

if (!function_exists('rkrk_dga_rh_clean_rel_file')) {
	function rkrk_dga_rh_clean_rel_file($value, $defaultExt = "csv") {
		$value = str_replace("\\", "/", trim((string)$value));
		$value = ltrim($value, "/");
		$value = preg_replace('/\.\.+/', '', (string)$value);
		if ($value === "") {
			return "";
		}
		// Wie Admin «da_clean_relative_file»: Umlaute, Ziffern und Leerzeichen im Dateinamen (z. B. «RKRS Einsatzpruefung.html»).
		if (preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) !== 1) {
			return "";
		}
		if (strpos($value, ".") === false && $defaultExt !== "") {
			$value .= "." . $defaultExt;
		}
		return $value;
	}
}

if (!function_exists('rkrk_dga_rh_clean_rel_folder')) {
	function rkrk_dga_rh_clean_rel_folder($value) {
		$value = str_replace("\\", "/", trim((string)$value));
		$value = trim($value, "/");
		$value = preg_replace('/\.\.+/', '', (string)$value);
		if ($value === "") {
			return "";
		}
		// DriveLocation z. B. «RKRS Einsatzprüfung» (Leerzeichen) — wie bei Dateinamen in clean_rel_file.
		if (preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) !== 1) {
			return "";
		}
		return $value;
	}
}

if (!function_exists('rkrk_dga_rh_question_image_rel')) {
	/**
	 * Relativer Bildpfad unter data/assess/images/ (Dateiname + DriveLocation).
	 */
	function rkrk_dga_rh_question_image_rel($rawPath, $driveLoc) {
		$driveLoc = function_exists('rkrk_assess_clean_drive_location_rel')
			? rkrk_assess_clean_drive_location_rel((string)$driveLoc)
			: rkrk_dga_rh_clean_rel_folder((string)$driveLoc);
		$base = function_exists('rkrk_assess_question_image_basename')
			? rkrk_assess_question_image_basename((string)$rawPath, $driveLoc)
			: rkrk_dga_rh_clean_rel_file((string)$rawPath, "");
		if ($base === "") {
			return "";
		}
		return rkrk_dga_rh_with_drive($base, $driveLoc);
	}
}

if (!function_exists('rkrk_dga_rh_question_image_url')) {
	function rkrk_dga_rh_question_image_url($imageBaseWeb, $rawPath, $driveLoc) {
		$rel = rkrk_dga_rh_question_image_rel($rawPath, $driveLoc);
		if ($rel === "") {
			return "";
		}
		return rkrk_dga_rh_image_url($imageBaseWeb, $rel);
	}
}

if (!function_exists('rkrk_dga_rh_with_drive')) {
	function rkrk_dga_rh_with_drive($file, $driveLoc) {
		$file = rkrk_dga_rh_clean_rel_file($file, "");
		$driveLoc = rkrk_dga_rh_clean_rel_folder($driveLoc);
		if ($file === "") {
			return "";
		}
		if ($driveLoc === "") {
			return $file;
		}
		$prefix = rtrim($driveLoc, "/") . "/";
		if (strpos($file, $prefix) === 0) {
			return $file;
		}
		return $prefix . ltrim($file, "/");
	}
}

if (!function_exists('rkrk_dga_rh_fix_mojibake')) {
	function rkrk_dga_rh_fix_mojibake($value) {
		if (function_exists("rkrk_assess_fix_mojibake")) {
			return rkrk_assess_fix_mojibake($value);
		}
		$value = (string)$value;
		if ($value === "") {
			return $value;
		}
		$map = array(
			"Ã¤" => "ä", "Ã„" => "Ä",
			"Ã¶" => "ö", "Ã–" => "Ö",
			"Ã¼" => "ü", "Ãœ" => "Ü",
			"ÃŸ" => "ß",
			"â€“" => "–", "â€”" => "—",
			"â€ž" => "„", "â€œ" => "“", "â€" => "”",
			"â€˜" => "‘", "â€™" => "’",
			"Â°" => "°", "Â" => "",
		);
		return strtr($value, $map);
	}
}

if (!function_exists('rkrk_dga_rh_read_questions')) {
	function rkrk_dga_rh_read_questions($quizLoc, $fragenRel) {
		$headers = array("Nr","Frage","Loesungsauswahl","Bereich","Frage-Bild","Bildformat","Loesungs-Bild","Loesungstext","Loesung","Antwort","Drone","Zeit","Audienz","Prioritaet","Status","Aktiv","Locked");
		$path = $quizLoc . rkrk_dga_rh_clean_rel_file($fragenRel, "csv");
		$rows = array();
		if (function_exists("csv_to_array_ascII")) {
			$rows = csv_to_array_ascII($path, ";");
			if (!is_array($rows)) {
				$rows = array();
			}
		}
		$out = array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$line = array();
			foreach ($headers as $header) {
				$line[$header] = isset($row[$header]) ? rkrk_dga_rh_fix_mojibake((string)$row[$header]) : "";
			}
			if (trim((string)$line["Prioritaet"]) === "" && isset($row["Priorität"])) {
				$line["Prioritaet"] = rkrk_dga_rh_fix_mojibake((string)$row["Priorität"]);
			}
			$out[] = $line;
		}
		if (function_exists("rkrk_assess_fixup_question_image_paths")) {
			rkrk_assess_fixup_question_image_paths($out);
		}
		if (function_exists("rkrk_assess_strip_question_image_paths")) {
			rkrk_assess_strip_question_image_paths($out, "");
		}
		return $out;
	}
}

if (!function_exists('rkrk_dga_rh_normalize_loes_pipe')) {
	function rkrk_dga_rh_normalize_loes_pipe($row) {
		if (isset($row["Loesungsauswahl"]) && is_array($row["Loesungsauswahl"])) {
			$row["Loesungsauswahl"] = implode("|", $row["Loesungsauswahl"]);
		}
		return $row;
	}
}

if (!function_exists('rkrk_dga_rh_web_assess_root')) {
	/**
	 * Browser-Basis-URL zum Assess-Datenordner (…/data/assess/), wie $QuizLoc in rkrk_assess_common.php.
	 * Wird für Schulungs-HTML per iframe gebraucht, damit relative Bildpfade zur LernDatei aufgelöst werden.
	 */
	function rkrk_dga_rh_web_assess_root() {
		$b = rtrim(str_replace('\\', '/', (string)rkrk_dga_rh_web_image_base()), '/');
		$b = preg_replace('#/images$#', '', $b);
		return $b . '/';
	}
}

if (!function_exists('rkrk_dga_rh_web_image_base')) {
	function rkrk_dga_rh_web_image_base() {
		if (function_exists('rkrk_assess_quiz_data_assess_web_prefix')) {
			return rkrk_assess_quiz_data_assess_web_prefix() . 'images/';
		}
		if (isset($GLOBALS['QuizIconLoc']) && trim((string)$GLOBALS['QuizIconLoc']) !== '') {
			$loc = (string)$GLOBALS['QuizIconLoc'];
			if ($loc[0] === '/' || preg_match('#^https?://#i', $loc) === 1) {
				return $loc;
			}
		}
		return '/rkropen/rkrk/data/assess/images/';
	}
}

if (!function_exists('rkrk_dga_rh_image_url')) {
	/**
	 * Baut eine robuste Bild-URL:
	 * - akzeptiert bereits absolute http(s)-URLs unverändert
	 * - entfernt ggf. bereits enthaltene /rkrk|rkrs/data/assess/images/-Präfixe
	 * - hängt ansonsten an die übergebene imageBase an
	 */
	function rkrk_dga_rh_image_url($base, $rawPath) {
		$p = str_replace('\\', '/', trim((string)$rawPath));
		if ($p === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $p) === 1) {
			return $p;
		}
		$markers = function_exists('rkrk_assess_quiz_image_url_markers')
			? rkrk_assess_quiz_image_url_markers()
			: array('/rkrk/data/assess/quiz/images/', '/rkrs/data/assess/quiz/images/', '/rkrsplusassessment/data/assess/quiz/images/', '/rkrk/data/assess/images/', '/rkrs/data/assess/images/', '/rkrsplusassessment/data/assess/images/');
		foreach ($markers as $marker) {
			$pos = stripos($p, $marker);
			if ($pos !== false) {
				$p = substr($p, $pos + strlen($marker));
				break;
			}
		}
		// CSV/Altpfade mit "quiz/images/..." oder "images/..." relativ auf assess/images normalisieren.
		$p = preg_replace('#^(?:quiz/)?images/#i', '', $p);
		$p = ltrim($p, '/');
		$pEnc = function_exists('rkrk_assess_encode_url_path_segments')
			? rkrk_assess_encode_url_path_segments($p)
			: $p;
		$combined = rtrim((string) $base, '/') . '/' . $pEnc;
		return function_exists('rkrk_assess_encode_url_for_request')
			? rkrk_assess_encode_url_for_request($combined)
			: $combined;
	}
}

if (!function_exists('rkrk_dga_rh_learn_base_dir_url')) {
	/** Browser-Basis-URL zum Ordner der LernDatei (…/data/assess/…/). */
	function rkrk_dga_rh_learn_base_dir_url($lernRel) {
		$pfx = function_exists('rkrk_assess_quiz_data_assess_web_prefix')
			? rkrk_assess_quiz_data_assess_web_prefix()
			: (function_exists('rkrk_dga_rh_web_assess_root') ? rkrk_dga_rh_web_assess_root() : '/rkropen/rkrk/data/assess/');
		$learnWebPath = rtrim(str_replace('\\', '/', (string)$pfx), '/') . '/' . str_replace('\\', '/', ltrim((string)$lernRel, '/'));
		$baseDir = preg_replace('#([^/]+)$#', '', $learnWebPath);
		if ($baseDir === '' || $baseDir === '/') {
			$baseDir = rtrim(str_replace('\\', '/', (string)$pfx), '/') . '/';
		} elseif (substr($baseDir, -1) !== '/') {
			$baseDir .= '/';
		}
		return $baseDir;
	}
}

if (!function_exists('rkrk_dga_rh_rewrite_legacy_assess_url')) {
	/**
	 * Absolute Assess-Pfade (RKRK/RKRS/Portal) auf die aktuelle Web-Basis umschreiben.
	 */
	function rkrk_dga_rh_rewrite_legacy_assess_url($url) {
		$u = trim(str_replace('\\', '/', (string)$url));
		if ($u === '' || $u[0] === '#' || preg_match('#^(https?:|mailto:|tel:|data:)#i', $u)) {
			return $u;
		}
		if ($u[0] !== '/') {
			return $u;
		}
		$target = function_exists('rkrk_dga_rh_web_assess_root')
			? rkrk_dga_rh_web_assess_root()
			: '/rkropen/rkrk/data/assess/';
		$target = rtrim($target, '/') . '/';
		if (preg_match('#^/data/assess/#i', $u)) {
			return $target . ltrim(substr($u, strlen('/data/assess/')), '/');
		}
		$fromPrefixes = array(
			'/rkropen/rkrk/data/assess/',
			'/rkropen/rkrs/data/assess/',
			'/rkrsplusassessment/data/assess/',
			'/rkrk/data/assess/',
			'/rkrs/data/assess/',
		);
		foreach ($fromPrefixes as $from) {
			if (stripos($u, $from) !== 0) {
				continue;
			}
			if (strcasecmp(rtrim($from, '/'), rtrim($target, '/')) === 0) {
				return $u;
			}
			return $target . substr($u, strlen($from));
		}
		return $u;
	}
}

if (!function_exists('rkrk_dga_rh_normalize_learn_inner_html')) {
	/**
	 * Schulungs-HTML: Altpfade bereinigen, relative src/href auflösen (RKRS/RKRK/Portal).
	 */
	function rkrk_dga_rh_normalize_learn_inner_html($inner, $lernRel, $driveLocation = "") {
		$inner = (string)$inner;
		$inner = preg_replace('#/data/assess/quiz/images/#i', '/data/assess/images/', $inner);
		$inner = preg_replace('#(["\'(])quiz/images/#i', '$1images/', $inner);
		$baseDir = rkrk_dga_rh_learn_base_dir_url($lernRel);
		if (function_exists('rkrk_dga_rh_resolve_relative_urls_in_fragment')) {
			$inner = rkrk_dga_rh_resolve_relative_urls_in_fragment($inner, $baseDir);
		}
		if (
			trim((string) $driveLocation) !== ""
			&& function_exists("rkrk_assess_rewrite_image_urls_drive_location_in_fragment")
		) {
			$inner = rkrk_assess_rewrite_image_urls_drive_location_in_fragment($inner, (string) $driveLocation);
		}
		// Bilder über PHP-Proxy ausliefern (Portal-Static vs. Dateisystem).
		if (function_exists("rkrk_dga_rh_rewrite_assess_images_to_proxy")) {
			$inner = rkrk_dga_rh_rewrite_assess_images_to_proxy($inner);
		}
		return $inner;
	}
}

if (!function_exists("rkrk_dga_rh_assess_image_proxy_url")) {
	function rkrk_dga_rh_assess_image_proxy_url($relUnderImages) {
		$rel = ltrim(str_replace("\\", "/", (string) $relUnderImages), "/");
		$rel = preg_replace('#^(?:quiz/)?images/#i', "", $rel);
		if ($rel === "" || strpos($rel, "..") !== false) {
			return "";
		}
		if (function_exists("rkrk_assess_image_proxy_script_web_path")) {
			$proxy = rkrk_assess_image_proxy_script_web_path();
		} else {
			$scriptDir = str_replace("\\", "/", dirname((string) ($_SERVER["SCRIPT_NAME"] ?? "")));
			$scriptDir = rtrim($scriptDir, "/");
			if ($scriptDir === "" || $scriptDir === ".") {
				$scriptDir = "/rkropen/rkrs/assess/public";
			}
			$proxy = $scriptDir . "/rkrk_assess_image.php";
		}
		$parts = explode("/", $rel);
		$file = array_pop($parts);
		$dir = implode("/", $parts);
		$url = $proxy . "?f=" . rawurlencode((string) $file);
		if ($dir !== "") {
			$url .= "&d=" . rawurlencode($dir);
		}
		return $url;
	}
}

if (!function_exists("rkrk_dga_rh_rewrite_assess_images_to_proxy")) {
	function rkrk_dga_rh_rewrite_assess_images_to_proxy($html) {
		return preg_replace_callback(
			'/\b(src|data-src|poster)\s*=\s*(["\'])([^"\']*?\/data\/assess\/(?:quiz\/)?images\/)([^"\']+)\2/i',
			static function ($m) {
				$proxy = rkrk_dga_rh_assess_image_proxy_url($m[4]);
				if ($proxy === "") {
					return $m[0];
				}
				return $m[1] . "=" . $m[2] . htmlspecialchars($proxy, ENT_QUOTES, "UTF-8") . $m[2];
			},
			(string) $html
		);
	}
}

if (!function_exists('rkrk_dga_rh_resolve_relative_urls_in_fragment')) {
	/**
	 * Löst in einem HTML-Schnipsel typische relative src/href-URLs gegen die Lern-Datei-Basis auf
	 * (ohne document-weites <base>, damit die Leseseite intakt bleibt).
	 */
	function rkrk_dga_rh_resolve_relative_urls_in_fragment($html, $baseDirUrl) {
		$b = rtrim(str_replace("\\", "/", (string)$baseDirUrl), "/") . "/";
		$absOne = static function ($u) use ($b) {
			$u = trim((string)$u);
			if ($u === "") {
				return "";
			}
			if ($u[0] === "#" || preg_match('#^(https?:|mailto:|tel:|data:)#i', $u)) {
				return $u;
			}
			if ($u[0] === "/") {
				return function_exists('rkrk_dga_rh_rewrite_legacy_assess_url')
					? rkrk_dga_rh_rewrite_legacy_assess_url($u)
					: $u;
			}
			return $b . str_replace("\\", "/", $u);
		};
		$encUrl = static function ($u) use ($absOne) {
			$abs = $absOne($u);
			return function_exists('rkrk_assess_encode_url_for_request')
				? rkrk_assess_encode_url_for_request($abs)
				: $abs;
		};
		$attrUrl = static function (string $attr, array $m) use ($encUrl): string {
			return $attr . "=" . $m[1] . htmlspecialchars($encUrl($m[2]), ENT_QUOTES, "UTF-8") . $m[1];
		};
		$html = preg_replace_callback('/\bsrc\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($attrUrl) {
			return $attrUrl("src", $m);
		}, $html);
		$html = preg_replace_callback('/\bdata-src\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($attrUrl) {
			return $attrUrl("data-src", $m);
		}, $html);
		$html = preg_replace_callback('/\bposter\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($attrUrl) {
			return $attrUrl("poster", $m);
		}, $html);
		$html = preg_replace_callback('/\bsrcset\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($encUrl) {
			$parts = preg_split('/\s*,\s*/', (string) $m[2]);
			$resolved = array();
			foreach ($parts as $part) {
				$part = trim((string) $part);
				if ($part === "") {
					continue;
				}
				if (preg_match('/^(\S+)(\s+.+)?$/', $part, $pm) === 1) {
					$resolved[] = $encUrl($pm[1]) . (isset($pm[2]) ? $pm[2] : "");
				}
			}
			return "srcset=" . $m[1] . htmlspecialchars(implode(", ", $resolved), ENT_QUOTES, "UTF-8") . $m[1];
		}, $html);
		$html = preg_replace_callback('/\bhref\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($encUrl) {
			$t = $m[2];
			if (trim((string)$t) !== "" && $t[0] === "#") {
				return $m[0];
			}
			return "href=" . $m[1] . htmlspecialchars($encUrl($t), ENT_QUOTES, "UTF-8") . $m[1];
		}, $html);
		$html = preg_replace_callback('/\bstyle\s*=\s*(["\'])([^"\']*)\1/i', static function ($m) use ($encUrl) {
			$style = preg_replace_callback(
				'/url\(\s*(["\']?)([^"\')\s]+)\1\s*\)/i',
				static function ($um) use ($encUrl) {
					$q = $um[1];
					return "url(" . $q . $encUrl($um[2]) . $q . ")";
				},
				$m[2]
			);
			return "style=" . $m[1] . htmlspecialchars((string) $style, ENT_QUOTES, "UTF-8") . $m[1];
		}, $html);
		return $html;
	}
}

if (!function_exists('rkrk_dga_rh_prepare_learn_inner_embed')) {
	/**
	 * Schulungs-HTML direkt einbetten (ohne iframe): voller Text auf der Seite, gleiche Pfad-Logik wie rkrk_digitalassess_learn.php.
	 */
	function rkrk_dga_rh_prepare_learn_inner_embed($learnFsPath, $lernRel) {
		$html = @file_get_contents((string)$learnFsPath);
		if ($html === false || $html === "") {
			return "";
		}
		$inner = $html;
		if (preg_match("~<body[^>]*>\\s*(.*?)\\s*</body~is", $html, $m)) {
			$inner = $m[1];
		}
		return function_exists('rkrk_dga_rh_normalize_learn_inner_html')
			? rkrk_dga_rh_normalize_learn_inner_html($inner, $lernRel)
			: $inner;
	}
}

if (!function_exists('rkrk_dga_rh_echo_learn_theme_links')) {
	function rkrk_dga_rh_echo_learn_theme_links() {
		$assessScriptDir = str_replace("\\", "/", dirname((string)($_SERVER["SCRIPT_NAME"] ?? "")));
		$assessScriptDir = rtrim($assessScriptDir, "/");
		if ($assessScriptDir === "" || $assessScriptDir === ".") {
			$assessScriptDir = "";
		}
		$themeCssHref = ($assessScriptDir !== "" ? $assessScriptDir : "") . "/rkrk_assess_theme.css";
		$themeRkrsCssHref = ($assessScriptDir !== "" ? $assessScriptDir : "") . "/rkrk_assess_theme_rkrs.css";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . rkrk_dga_rh($themeCssHref) . "\">\n";
		if (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . rkrk_dga_rh($themeRkrsCssHref) . "\">\n";
		}
	}
}

if (!function_exists('rkrk_dga_rh_echo_styles')) {
	function rkrk_dga_rh_echo_styles($printView) {
		?>
<style>
.dr-wrap{max-width:1200px;margin:0 auto;padding:12px;}
.dr-card{background:var(--card, #fff);border:1px solid var(--border, #d9e0d7);border-radius:10px;padding:12px;margin-bottom:10px;}
.dr-inline{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.dr-select{padding:8px;min-width:280px;}
.dr-btn{display:inline-block;padding:8px 12px;border-radius:6px;background:#2f6f2f;color:#fff;text-decoration:none;border:0;cursor:pointer;}
.dr-list{display:grid;gap:10px;}
.dr-item{border:1px solid #d8dfd6;border-radius:8px;background:#fbfdfb;padding:10px;}
.dr-item-inactive{color:#6b7280;background:#f3f4f6;border-color:#d1d5db;}
.dr-meta-status{font-size:12px;margin:0 0 6px;opacity:.75;}
.dr-q{margin:0 0 8px;font-weight:700;}
.dr-group-title{margin:0 0 8px;padding:6px 8px;border-radius:6px;background:#eef5ff;color:#1e3a8a;font-weight:700;}
.dr-drone-badge{display:inline-block;margin:0 0 8px;padding:2px 8px;border-radius:999px;background:#e5f3e8;color:#166534;font-size:12px;font-weight:700;}
.dr-options{margin:4px 0 0 20px;padding:0;}
.dr-options li{margin:2px 0;}
.dr-correct{font-weight:700;background:#d8f5de;padding:1px 4px;border-radius:4px;}
.dr-solution{margin-top:8px;padding:8px;border:1px solid #dfe7df;border-radius:6px;white-space:pre-wrap;background:#fff;}
.dr-images{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;}
.dr-img{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#334155;font-size:12px;}
.dr-img img{width:48px;height:48px;object-fit:cover;border:1px solid #d1d5db;border-radius:6px;background:#fff;}
.dr-check{display:inline-flex;align-items:center;gap:6px;font-size:13px;}
.dr-learn-content{font-size:15px;line-height:1.5;}
.dr-learn{border-top:1px dashed var(--border, #cfd9cc);}
.dr-learn-inline{max-width:100%;overflow-x:auto;min-height:0;}
.dr-learn-inline figure{max-width:min(100%,400px);margin:14px 0;padding:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);}
.dr-learn-inline figure img,.dr-learn-inline figure picture img{max-width:100%;width:auto!important;height:auto!important;display:block;border:0;border-radius:8px;box-shadow:none;background:transparent;}
.dr-learn-inline img,.dr-learn-inline svg,.dr-learn-inline video{max-width:min(100%,380px);width:auto!important;height:auto;display:block;border-radius:10px;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(15,23,42,.08);background:#fff;box-sizing:border-box;}
</style>
		<?php
		if ($printView) {
			echo "<style>.dr-wrap{max-width:100%;padding:4px;} .dr-card{box-shadow:none;} .dr-print-hide{display:none;}";
			echo " @media print{";
			echo " .dr-learn-inline img,.dr-learn-inline figure{page-break-inside:avoid;max-width:100%!important;height:auto!important;}";
			echo " .dr-learn-inline h1,.dr-learn-inline h2,.dr-learn-inline h3{page-break-after:avoid;}";
			echo "}";
			echo "</style>";
		}
	}
}

if (!function_exists('rkrk_dga_rh_assessment_allowed_hub')) {
	/**
	 * @param bool $portalAdmin Admin-Portal: auch Zeilen mit Aktiv=Nein.
	 * @param bool $portalInstr Instruktor: gesperrte / nicht «released» Vorschau, aber nur bei Aktiv=Ja.
	 */
	function rkrk_dga_rh_assessment_allowed_hub($qs, $setting, $portalAdmin = false, $portalInstr = false) {
		if ($portalAdmin) {
			return true;
		}
		$aktivJa = (trim((string)($qs["Aktiv"] ?? "")) === "Ja");
		$released = $aktivJa && (($qs["Locked"] ?? "") === "Nein");
		$seesAud = function_exists('rkrk_assess_setting_sees_audience')
			&& rkrk_assess_setting_sees_audience((string)$setting, (string)($qs["Audienz"] ?? ""));
		$test = ($setting === "TEST");
		if (!$aktivJa) {
			return false;
		}
		if ($portalInstr) {
			return $released || $seesAud || $test;
		}
		return $released && ($seesAud || $test);
	}
}

if (!function_exists('rkrk_dga_rh_render_hub')) {
	/**
	 * @param bool $portalAdmin Admin: Liste inkl. Aktiv=Nein.
	 * @param bool $portalInstr Instruktor: Vorschau gesperrter, aber nur Aktiv=Ja.
	 */
	function rkrk_dga_rh_render_hub(array $quizList, $setting, $schulungsMode, $onRKRS, $quizLoc, $portalAdmin = false, $portalInstr = false) {
		$assessmentHeaders = array("Nr","Key","Titel","Kurzbeschreibung","Bild","Einfuehrung","Erfuellt","PunkteModus","DriveLocation","LernDatei","FragenDatei","AnzQuestions","Audienz","QuizTime","Quiz","Schulung","AntwortMischen","FragenBildAnzeigen","Aktiv","Locked");
		$list = array();
		foreach ($quizList as $row) {
			if (!is_array($row)) {
				continue;
			}
			$qk = isset($row["Key"]) ? (string)$row["Key"] : "";
			if ($qk === "") {
				continue;
			}
			$pick = array();
			foreach ($assessmentHeaders as $h) {
				$pick[$h] = isset($row[$h]) ? (string)$row[$h] : "";
			}
			if (!rkrk_dga_rh_assessment_allowed_hub($pick, $setting, $portalAdmin, $portalInstr)) {
				continue;
			}
			if ($schulungsMode) {
				if (strtolower(trim((string)$pick["Schulung"])) !== "ja") {
					continue;
				}
			} else {
				if (strtolower(trim((string)$pick["Quiz"])) !== "ja") {
					continue;
				}
			}
			$list[] = $pick;
		}

		$h2 = $schulungsMode
			? ($onRKRS ? "Schulungen — Lesen (Schweiz)" : "Schulungen — Lesen (Knonaueramt)")
			: ($onRKRS ? "Quiz — Lesen (Schweiz)" : "Quiz — Lesen (Knonaueramt)");

		$interactQuiz = rkrk_assess_append_setting_href("rkrk_digitalassess.php");
		$interactSchul = rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("Schulung" => ""))));
		$onlyActive = isset($_GET["only_active"]) && (string)$_GET["only_active"] === "1";
		$printView = isset($_GET["print_view"]) && (string)$_GET["print_view"] === "1";
		$ohneLerntextHub = $schulungsMode && !empty($_GET["ohne_lerntext"]);
		$selQuiz = isset($_GET["quiz"]) ? trim((string)$_GET["quiz"]) : "";
		if ($selQuiz === "" && count($list) > 0) {
			$selQuiz = (string)$list[0]["Key"];
		}
		if ($selQuiz !== "") {
			foreach ($list as $aRowPick) {
				$kk = (string)$aRowPick["Key"];
				if ($kk !== "" && ($kk === $selQuiz || strcasecmp($kk, $selQuiz) === 0)) {
					$selQuiz = $kk;
					break;
				}
			}
		}

		rkrk_dga_rh_echo_styles($printView);
		echo "<div class='dr-wrap'>";
		$_rkrkAdminNav = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool)$onAdmin);
		$_rkrkInstrNav = !empty($onInstr);
		if (function_exists("rkrk_assess_portal_nav_echo")) {
			ob_start();
			rkrk_assess_portal_nav_echo(array(
				"btn_class" => "dr-btn",
				"schulung_mode" => $schulungsMode,
				"is_admin" => $_rkrkAdminNav,
				"is_instr" => $_rkrkInstrNav,
				"selected_quiz" => $selQuiz,
			));
			$__rkrk_portal_out = ob_get_clean();
			if ($__rkrk_portal_out !== "") {
				echo "<div id='rkrk-portal-nav-slot' class='rkrk-portal-nav-slot'>" . $__rkrk_portal_out . "</div>";
			}
		}
		echo "<div class='dr-card dr-print-hide'>";
		echo "<h2 style='margin:0 0 8px;'>" . rkrk_dga_rh($h2) . "</h2>";
		echo "<p style='margin:0 0 10px;opacity:.85;font-size:14px;'>Lesemodus ist in der Quiz-Seite integriert; anschliessend kannst du dieselbe Thematik interaktiv üben.</p>";
		echo "<form method='get' class='dr-inline'>";
		if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
			rkrk_assess_echo_setting_hidden_input();
		}
		echo "<input type='hidden' name='read_only' value='1'>";
		if ($schulungsMode) {
			echo "<input type='hidden' name='Schulung' value=''>";
		}
		echo "<select class='dr-select' name='quiz'>";
		if (count($list) === 0) {
			echo "<option value=''>— keine Einträge —</option>";
		}
		foreach ($list as $aRow) {
			$k = (string)$aRow["Key"];
			$sel = ($k === $selQuiz || ($selQuiz !== "" && strcasecmp($k, $selQuiz) === 0)) ? " selected" : "";
			echo "<option value='" . rkrk_dga_rh($k) . "'" . $sel . ">" . rkrk_dga_rh($k . " - " . (string)$aRow["Titel"]) . "</option>";
		}
		echo "</select>";
		echo "<label class='dr-check'><input type='checkbox' name='only_active' value='1'" . ($onlyActive ? " checked" : "") . "> nur Quiz-Fragen (leerer Status zählt wie «aktiv»)</label>";
		echo "<label class='dr-check'><input type='checkbox' name='print_view' value='1'" . ($printView ? " checked" : "") . "> Druckansicht</label>";
		if ($selQuiz !== "") {
			$paperHubQs = array("quiz" => $selQuiz, "paper" => "1", "paper_mode" => "sample", "drone" => "Alle");
			$paperHubHref = function_exists("rkrk_dga_paper_form_href")
				? rkrk_dga_paper_form_href($selQuiz, $paperHubQs)
				: rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($paperHubQs)));
			$paperHubSolQs = array_merge($paperHubQs, array("show_solutions" => "1"));
			$paperHubSolHref = function_exists("rkrk_dga_paper_form_href")
				? rkrk_dga_paper_form_href($selQuiz, $paperHubSolQs)
				: rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($paperHubSolQs)));
			echo " <a class='dr-btn' style='background:#1e5a3a;margin-left:6px;' href='" . rkrk_dga_rh($paperHubHref) . "' target='_blank' rel='noopener'>Papierformular</a>";
			echo " <a class='dr-btn' style='background:#166534;margin-left:6px;' href='" . rkrk_dga_rh($paperHubSolHref) . "' target='_blank' rel='noopener'>Lösungspapier</a>";
		}
		if ($schulungsMode) {
			echo "<label class='dr-check'><input type='checkbox' name='ohne_lerntext' value='1'" . ($ohneLerntextHub ? " checked" : "") . "> ohne Schulungstext (nur Fragen)</label>";
		}
		echo "<button class='dr-btn' type='submit'>" . rkrk_dga_rh($schulungsMode ? "Schulung anzeigen" : "Quiz anzeigen") . "</button>";
		echo "</form>";
		echo "</div>";

		if ($selQuiz !== "" && count($list) > 0) {
			$match = null;
			foreach ($list as $aRow) {
				$ak = (string)$aRow["Key"];
				if ($ak !== "" && ($ak === $selQuiz || strcasecmp($ak, $selQuiz) === 0)) {
					$match = $aRow;
					break;
				}
			}
			if (is_array($match)) {
				$questions = rkrk_dga_rh_read_questions($quizLoc, (string)$match["FragenDatei"]);
				$learnFs = "";
				$lf = rkrk_dga_rh_clean_rel_file((string)$match["LernDatei"], "html");
				if ($lf !== "" && is_file($quizLoc . $lf)) {
					$learnFs = $quizLoc . $lf;
				}
				$showLearn = $schulungsMode && empty($_GET["ohne_lerntext"]);
				rkrk_dga_rh_render_detail($match, $questions, $onlyActive, $printView, $learnFs, $showLearn, $schulungsMode);
			}
		}
		echo "</div>";
	}
}

if (!function_exists('rkrk_dga_rh_merge_read_query')) {
	function rkrk_dga_rh_merge_read_query(array $overrides) {
		$q = array_merge($_GET, $overrides);
		foreach ($q as $qk => $qv) {
			if ($qv === null) {
				unset($q[$qk]);
			}
		}
		return http_build_query(rkrk_assess_merge_query_with_setting($q));
	}
}

if (!function_exists('rkrk_dga_rh_render_detail')) {
	function rkrk_dga_rh_render_detail(array $selectedAssessment, array $questions, $onlyActive, $printView, $learnFsPath, $showLearnBlock, $schulungsMode) {
		$imageBaseWeb = rkrk_dga_rh_web_image_base();
		echo "<div class='dr-card'>";
		echo "<h3 style='margin:0 0 10px;'>" . rkrk_dga_rh((string)$selectedAssessment["Titel"]) . "</h3>";

		$cont = rkrk_assess_merge_query_with_setting($_GET);
		unset($cont["read_only"], $cont["Lesemodus"]);
		$contStr = http_build_query($cont);
		$toQuiz = rkrk_assess_append_setting_href("rkrk_digitalassess.php" . ($contStr !== "" ? ("?" . $contStr) : ""));
		echo "<div class='dr-inline dr-print-hide' style='margin-bottom:12px;'>";
		echo "<a class='dr-btn' href='" . rkrk_dga_rh($toQuiz) . "'>" . rkrk_dga_rh($schulungsMode ? "Schulungsprüfung (interaktiv)" : "Interaktives Quiz") . "</a>";
		$paperExtra = array("paper_mode" => "sample", "drone" => "Alle");
		$paperHref = function_exists("rkrk_dga_paper_form_href")
			? rkrk_dga_paper_form_href((string) ($selectedAssessment["Key"] ?? ""), $paperExtra)
			: rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . rkrk_dga_rh_merge_read_query(array_merge(array("paper" => "1"), $paperExtra, array("read_only" => null, "Lesemodus" => null))));
		$paperSolExtra = array_merge($paperExtra, array("show_solutions" => "1"));
		$paperSolHref = function_exists("rkrk_dga_paper_form_href")
			? rkrk_dga_paper_form_href((string) ($selectedAssessment["Key"] ?? ""), $paperSolExtra)
			: rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . rkrk_dga_rh_merge_read_query(array_merge(array("paper" => "1"), $paperSolExtra, array("read_only" => null, "Lesemodus" => null))));
		echo "<a class='dr-btn' style='background:#1e5a3a;' href='" . rkrk_dga_rh($paperHref) . "' target='_blank' rel='noopener'>Papierformular</a>";
		echo "<a class='dr-btn' style='background:#166534;margin-left:6px;' href='" . rkrk_dga_rh($paperSolHref) . "' target='_blank' rel='noopener'>Lösungspapier</a>";
		$learnReadable = ($learnFsPath !== "" && is_readable($learnFsPath));
		if ($schulungsMode && $learnReadable) {
			if (!empty($_GET["ohne_lerntext"])) {
				$qs = rkrk_dga_rh_merge_read_query(array("ohne_lerntext" => null));
				$hrefShow = rkrk_assess_append_setting_href("rkrk_digitalassess.php" . ($qs !== "" ? ("?" . $qs) : ""));
				echo "<a class='dr-btn' style='background:#475569;' href='" . rkrk_dga_rh($hrefShow) . "'>Schulungstext einblenden</a>";
			} else {
				$qs = rkrk_dga_rh_merge_read_query(array("ohne_lerntext" => "1"));
				echo "<a class='dr-btn' style='background:#475569;' href='" . rkrk_dga_rh(rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . $qs)) . "'>Nur Fragen (ohne Lerntext)</a>";
			}
		}
		echo "</div>";

		if ($showLearnBlock && $learnReadable) {
			echo "<div class='dr-card dr-learn' style='margin-bottom:12px;'>";
			echo "<h4 style='margin:0 0 8px;'>Schulungstext</h4>";
			echo "<div class='dr-learn-content'>";
			$lfRel = rkrk_dga_rh_clean_rel_file((string)$selectedAssessment["LernDatei"], "html");
			if ($lfRel !== "") {
				rkrk_dga_rh_echo_learn_theme_links();
				$innerEmbed = rkrk_dga_rh_prepare_learn_inner_embed($learnFsPath, $lfRel);
				echo "<div class=\"dr-learn-inline\">";
				echo $innerEmbed !== "" ? $innerEmbed : "<p><em>Schulungstext konnte nicht geladen werden.</em></p>";
				echo "</div>";
			} else {
				include $learnFsPath;
			}
			echo "</div></div>";
		}

		$rows = array_values(array_filter($questions, function ($qRow) use ($onlyActive) {
			if (!is_array($qRow) || trim((string)($qRow["Frage"] ?? "")) === "") {
				return false;
			}
			if ($onlyActive) {
				$live = function_exists("rkrk_assess_question_is_quiz_live")
					? rkrk_assess_question_is_quiz_live($qRow)
					: (strtolower(trim((string)($qRow["Aktiv"] ?? ""))) === "ja");
				if (!$live) {
					return false;
				}
			}
			return true;
		}));

		if (count($rows) === 0) {
			echo "<p>Keine Fragen gefunden.</p></div>";
			return;
		}

		echo "<h4 style='margin:16px 0 8px;'>Fragen &amp; Musterlösung (Lesemodus)</h4>";
		echo "<div class='dr-list'>";
		$lastDroneGroup = null;
		foreach ($rows as $idx => $qRow) {
			$qRow = rkrk_dga_rh_normalize_loes_pipe($qRow);
			$qNr = trim((string)($qRow["Nr"] ?? "")) !== "" ? (string)$qRow["Nr"] : (string)($idx + 1);
			$droneType = trim((string)($qRow["Drone"] ?? ""));
			$droneGroup = ($droneType !== "") ? $droneType : "Allgemein";
			if ($droneGroup !== $lastDroneGroup) {
				$lastDroneGroup = $droneGroup;
				echo "<div class='dr-group-title'>Drohnentyp: " . rkrk_dga_rh($droneGroup) . "</div>";
			}
			$optionList = array_values(array_filter(array_map("trim", explode("|", (string)($qRow["Loesungsauswahl"] ?? ""))), function ($v) {
				return $v !== "";
			}));
			$correctRaw = (string)($qRow["Loesung"] ?? "");
			$correctRaw = str_replace(array(",", ";", "/"), "|", $correctRaw);
			$correctTokens = array_values(array_filter(array_map("trim", explode("|", $correctRaw)), function ($v) {
				return $v !== "";
			}));
			$correctLookup = array();
			foreach ($correctTokens as $token) {
				$t = strtolower(trim($token));
				if ($t === "") {
					continue;
				}
				$correctLookup[$t] = true;
				$tReduced = preg_replace('/[\)\.\:\-_\s]+$/', '', $t);
				if ($tReduced !== null && $tReduced !== "") {
					$correctLookup[$tReduced] = true;
				}
			}
			$isInactive = function_exists("rkrk_assess_question_is_quiz_live")
				? !rkrk_assess_question_is_quiz_live($qRow)
				: (strtolower(trim((string)($qRow["Aktiv"] ?? ""))) !== "ja");
			$driveEff = function_exists("rkrk_assess_effective_drive_location")
				? rkrk_assess_effective_drive_location($selectedAssessment, $questions)
				: (string)($selectedAssessment["DriveLocation"] ?? "");
			$frageBild = rkrk_dga_rh_question_image_rel((string)($qRow["Frage-Bild"] ?? ""), $driveEff);
			$loesungsBild = rkrk_dga_rh_question_image_rel((string)($qRow["Loesungs-Bild"] ?? ""), $driveEff);
			echo "<div class='dr-item" . ($isInactive ? " dr-item-inactive" : "") . "'>";
			if ($droneType !== "") {
				echo "<div class='dr-drone-badge'>" . rkrk_dga_rh($droneType) . "</div>";
			}
			$stDisp = trim((string)($qRow["Status"] ?? ""));
			if ($stDisp !== "") {
				echo "<p class='dr-meta-status'>Status: " . rkrk_dga_rh($stDisp) . "</p>";
			}
			echo "<p class='dr-q'>" . rkrk_dga_rh($qNr . ". " . (string)$qRow["Frage"]) . "</p>";
			if (count($optionList) > 0) {
				echo "<ul class='dr-options'>";
				foreach ($optionList as $optIdx => $optText) {
					$numToken = (string)($optIdx + 1);
					$letterToken = chr(97 + $optIdx);
					$optNorm = strtolower(trim((string)$optText));
					$optReduced = preg_replace('/^[0-9a-z]+[\)\.\:\-]\s*/i', '', $optNorm);
					$isCorrect =
						isset($correctLookup[$optNorm]) ||
						($optReduced !== "" && isset($correctLookup[$optReduced])) ||
						isset($correctLookup[$numToken]) ||
						isset($correctLookup[$letterToken]);
					echo "<li" . ($isCorrect ? " class='dr-correct'" : "") . ">" . rkrk_dga_rh($numToken . ") " . $optText) . "</li>";
				}
				echo "</ul>";
			} elseif (trim((string)$qRow["Loesung"]) !== "") {
				echo "<p><span class='dr-correct'>Richtige Antwort: " . rkrk_dga_rh((string)$qRow["Loesung"]) . "</span></p>";
			}
			echo "<div class='dr-images'>";
			if ($frageBild !== "") {
				$frageUrl = rkrk_dga_rh_image_url($imageBaseWeb, $frageBild);
				echo "<a class='dr-img' target='_blank' rel='noopener' href='" . rkrk_dga_rh($frageUrl) . "'>";
				echo "<img src='" . rkrk_dga_rh($frageUrl) . "' alt='Frage-Bild'><span>Frage-Bild</span></a>";
			}
			if ($loesungsBild !== "") {
				$loesungUrl = rkrk_dga_rh_image_url($imageBaseWeb, $loesungsBild);
				echo "<a class='dr-img' target='_blank' rel='noopener' href='" . rkrk_dga_rh($loesungUrl) . "'>";
				echo "<img src='" . rkrk_dga_rh($loesungUrl) . "' alt='Loesungs-Bild'><span>Loesungs-Bild</span></a>";
			}
			echo "</div>";
			if (trim((string)$qRow["Loesungstext"]) !== "") {
				echo "<div class='dr-solution'>" . rkrk_dga_rh((string)$qRow["Loesungstext"]) . "</div>";
			}
			echo "</div>";
		}
		echo "</div></div>";
	}
}
