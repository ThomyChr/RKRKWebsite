<?php 
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset.'scripts/rkrk_inputforms.php');
include_once($folderoffset.'scripts/rkrk_csvfiles.php');
include_once('rkrk_assess_common.php');
include_once(__DIR__ . '/rkrk_digitalassess_learning_shared.inc.php');
include_once(__DIR__ . '/rkrk_digitalassess_read_render.php');

if (!$onRKRS) {
	//check impropriate IP addresses accessing page
	CheckIPandExit($folderoffset);

	//Write to LOGfile
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","",$folderoffset);

	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	GrantAccess($folderoffset);
	if (empty($LoggedOn)) {
		if (function_exists('RegisterForm')) {
			RegisterForm(getIPAddress(), "Login", true);
		}
		exit;
	}

	$currentAccessLevel = '';
	if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
		$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
	} elseif (isset($InputUserInfo['Login']['Level'])) {
		$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
	}
	$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
	$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast' && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
	}
} else {
	// RKRS: kein Login-Block — hier dieselben Theme-Styles wie im RKRK-Zweig, sonst fehlen rkrk_assess_theme*.css
	rkrk_assess_theme_link_tags();
}
$pagetitle = "RKRS+ Assessments";
headerpage("$pagetitle", false, $folderoffset);
if (function_exists('rkrk_assess_theme_rkrs_layer_link') && function_exists('rkrk_assess_ci_rkrs_mode') && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}

function da_h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function da_learning_editor_parts($html) {
	return rkrk_dle_learning_editor_parts($html);
}

function da_learning_rebuild_from_post($body, $prefixB64, $suffixB64, $isFragment) {
	return rkrk_dle_learning_rebuild_from_post($body, $prefixB64, $suffixB64, $isFragment);
}

/** Audienz-Liste normalisieren: Trennung , ; | / → kommasepariert ohne Leer Spaces. */
function da_normalize_audienz_csv($raw) {
	$segs = function_exists("rkrk_assess_audience_segments") ? rkrk_assess_audience_segments($raw) : array_filter(array_map("trim", explode(",", trim((string)$raw))));
	return count($segs) ? implode(",", $segs) : "";
}

function da_fix_mojibake($value) {
	$value = (string)$value;
	if ($value === "") return $value;
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

/** Kleinschreibungs-Slug nur für Vergleich / Dateinamen-Fallback (nicht für gespeicherten Key). */
function da_slug($value) {
	$value = strtolower(trim((string)$value));
	$value = preg_replace('/[^a-z0-9\-_]+/i', '-', $value);
	$value = trim((string)$value, '-_');
	return $value !== "" ? $value : "quiz";
}

/**
 * Assessment-Key bereinigen — Gross-/Kleinschreibung bleibt erhalten.
 */
function da_normalize_key($value, $fallbackTitle = "") {
	$value = trim((string)$value);
	if ($value === "") {
		$value = trim((string)$fallbackTitle);
	}
	if ($value === "") {
		return "";
	}
	$value = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $value);
	$value = preg_replace('/-+/', '-', (string)$value);
	return trim((string)$value, '-_');
}

/**
 * Zeilenindex in $assessments anhand des Keys aus der Liste (z. B. selected_quiz).
 * Verhindert Dubletten, wenn da_slug(Key/Titel) vom gespeicherten Key abweicht (Umlaute, Grossbuchstaben).
 */
function da_find_assessment_row_index(array $assessments, $needleKey) {
	$needleKey = trim((string)$needleKey);
	if ($needleKey === "") {
		return null;
	}
	$needleSlug = da_slug($needleKey);
	foreach ($assessments as $idx => $aRow) {
		if (!is_array($aRow)) {
			continue;
		}
		$k = trim((string)($aRow["Key"] ?? ""));
		if ($k === "") {
			continue;
		}
		if ($k === $needleKey) {
			return (int)$idx;
		}
		if (strcasecmp($k, $needleKey) === 0) {
			return (int)$idx;
		}
		if ($needleSlug !== "" && $needleSlug !== "quiz" && da_slug($k) === $needleSlug) {
			return (int)$idx;
		}
	}
	return null;
}

function da_clean_relative_file($value, $defaultExt = "csv") {
	$value = str_replace("\\", "/", trim((string)$value));
	$value = ltrim($value, "/");
	$value = preg_replace('/\.\.+/', '', (string)$value);
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

function da_clean_relative_folder($value) {
	$value = str_replace("\\", "/", trim((string)$value));
	$value = trim($value, "/");
	$value = preg_replace('/\.\.+/', '', (string)$value);
	if ($value === "") {
		return "";
	}
	// Unicode letters (e.g. Einsatzprüfung), digits, underscore, hyphen, slash — same spirit as da_clean_relative_file.
	if (preg_match('/^[\p{L}\p{N}_\-\/]+$/u', $value) !== 1) {
		return "";
	}
	return $value;
}

function da_with_drive_location($file, $driveLoc) {
	$file = da_clean_relative_file($file, "");
	$driveLoc = da_clean_relative_folder($driveLoc);
	if ($file === "") return "";
	if ($driveLoc === "") return $file;
	$prefix = rtrim($driveLoc, "/") . "/";
	if (strpos($file, $prefix) === 0) return $file;
	return $prefix . ltrim($file, "/");
}

function da_read_csv_assoc($path, $expectedHeaders) {
	if (!function_exists("rkrk_assess_read_csv_file")) {
		return array();
	}
	list(, $rows) = rkrk_assess_read_csv_file($path, $expectedHeaders, ";");
	foreach ($rows as $ri => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach ($row as $hk => $hv) {
			$rows[$ri][$hk] = da_fix_mojibake((string) $hv);
		}
	}
	return $rows;
}

function da_write_csv_assoc($path, $headers, $rows) {
	return function_exists("rkrk_assess_write_csv_file")
		? rkrk_assess_write_csv_file($path, $headers, $rows, ";")
		: false;
}

/**
 * Dropdown mit festen Optionen; unbekannter aktueller Wert wird zusätzlich angeboten (CSV-Kompatibilität).
 *
 * @param array<int|string,string> $options
 */
function da_render_enum_select($name, $current, array $options, $allowBlank = false, $extraClass = "") {
	$current = trim((string)$current);
	$uniq = array();
	foreach ($options as $o) {
		$o = trim((string)$o);
		if ($allowBlank && $o === "") {
			continue;
		}
		if ($o === "") {
			continue;
		}
		if (!isset($uniq[$o])) {
			$uniq[$o] = true;
		}
	}
	$optsList = array_keys($uniq);

	$classes = trim("da-select-compact " . (string)$extraClass);
	$nameAttr = ((string)$name !== "") ? (" name='" . da_h((string)$name) . "'") : "";
	$html = "<select" . $nameAttr . " class='" . da_h($classes) . "'>";
	if ($allowBlank) {
		$sel = ($current === "") ? " selected" : "";
		$html .= "<option value=''" . $sel . ">-</option>";
	}
	$matched = false;
	foreach ($optsList as $opt) {
		$sel = ($opt === $current) ? " selected" : "";
		if ($sel !== "") {
			$matched = true;
		}
		$html .= "<option value='" . da_h($opt) . "'" . $sel . ">" . da_h($opt) . "</option>";
	}
	if ($current !== "" && !$matched) {
		$html .= "<option value='" . da_h($current) . "' selected>" . da_h($current) . "</option>";
	}
	$html .= "</select>";

	return $html;
}

/**
 * Ja/Nein als farbige Radio-Buttons (Fallback: Select bei abweichendem CSV-Wert).
 *
 * @param non-empty-string $name
 * @param string $defaultWhenEmpty "Ja"|"Nein" wenn Wert leer
 */
function da_render_ja_nein_radios($name, $current, $defaultWhenEmpty = "Ja") {
	$c = trim((string) $current);
	$norm = strtolower($c);
	if ($c !== "" && $norm !== "ja" && $norm !== "nein") {
		return da_render_enum_select($name, $c, array("Ja", "Nein"), false, "da-select-compact");
	}
	$def = strtolower(trim((string) $defaultWhenEmpty)) === "nein" ? "nein" : "ja";
	if ($c === "") {
		$pickJa = ($def === "ja");
		$pickNein = !$pickJa;
	} else {
		$pickJa = ($norm === "ja");
		$pickNein = ($norm === "nein");
	}
	$nm = da_h((string) $name);
	return "<span class='da-jn-wrap' role='group' aria-label='Ja oder Nein'>"
		. "<label class='da-jn da-jn-yes'><input type='radio' name='" . $nm . "' value='Ja'" . ($pickJa ? " checked" : "") . "><span>Ja</span></label>"
		. "<label class='da-jn da-jn-no'><input type='radio' name='" . $nm . "' value='Nein'" . ($pickNein ? " checked" : "") . "><span>Nein</span></label>"
		. "</span>";
}

function da_question_header_col_class($qh) {
	$slug = strtolower(preg_replace('/[^a-z0-9]+/iu', '-', (string)$qh));
	$slug = trim((string)$slug, "-");

	return "da-col-" . $slug;
}

/** HTML title-Attribut für Hover-Hilfe (leer = weglassen). */
function da_attr_title($text) {
	$text = trim((string)$text);
	if ($text === "") {
		return "";
	}
	return " title=\"" . da_h($text) . "\"";
}

/** Nach POST: Wahrscheinlichkeits-Parameter (Fragen/Drohne) nicht verlieren. */
function da_echo_ui_persist_hidden_inputs() {
	if (isset($GLOBALS["daProbPickN"])) {
		echo "<input type=\"hidden\" name=\"da_prob_pickn\" value=\"" . da_h((string) $GLOBALS["daProbPickN"]) . "\">\n";
		echo "<input type=\"hidden\" name=\"da_prob_drone\" value=\"" . da_h((string) ($GLOBALS["daProbDrone"] ?? "Alle")) . "\">\n";
	}
	if (function_exists("rkrk_assess_echo_setting_hidden_input")) {
		rkrk_assess_echo_setting_hidden_input();
	}
}

function da_question_header_tooltip($qh) {
	static $map = null;
	if ($map === null) {
		$map = array(
			"Nr" => "Beim Speichern fortlaufend 1…n in der CSV. Reihenfolge = Zeilenfolge (oder per Drag sortieren).",
			"Frage" => "Fragetext wie im Quiz. Zeilenumbrüche möglich; HTML je nach Darstellung im Frontend.",
			"Loesungsauswahl" => "Antworten mit | trennen: 1. Teil → A, 2. → B … bis F. Fehlende Teile = ausgeblendeter Button.",
			"Bereich" => "Freies Stichwort/Gliederung (z. B. Thema oder gleicher Text wie der Assessment-Titel).",
			"Herkunft" => "Optionale Quelle der Frage, z. B. übernommener Katalog oder «neu aus Kapitel …».",
			"Frage-Bild" => "Relativer Pfad unter images/: nur Dateiname oder unterordner/datei.jpg — DriveLocation wird bei Speichern vorangestellt.",
			"Bildformat" => "Layout: schmal, normal, breit oder Seite 1 (Darstellung im Quiz).",
			"Loesungs-Bild" => "Wie Frage-Bild: Bild zur Lösung/Erklärung.",
			"Loesungstext" => "Text nach der Antwort; darf HTML enthalten (z. B. <b>, <br>, Links).",
			"Loesung" => "Richtig: Bei Ja/Nein-Fragen „Ja“ oder „Nein“. Sonst A–F. Mehrere richtige mit | (z. B. A|C) → Mehrfachauswahl.",
			"Antwort" => "Optional Startwert der Teilnehmerantwort; leer = keine Vorauswahl. Format wie Lösung (Buchstabe, Ja/Nein, mehrere mit |).",
			"Drone" => "Filter: leer oder „Alle“ = für alle Drohnen; sonst exakt dieselbe Bezeichnung wie in anderen Fragen.",
			"Zeit" => "Countdown für diese Frage in Sekunden (Zahl). 0 oder leer = kein Fragen-Timer. „alle“ = ohne Limit.",
			"Audienz" => "Sichtbarkeit: „Alle“, ein Code oder mehrere Codes kommagetrennt (z. B. RKRS, RKRSplus). Optional auch ; oder | als Trenner.",
			"Status" => "neu / prüfen / geprüft / aktiv / inaktiv — leer oder Spalte fehlt = wie «aktiv». Gesetzt: nur «aktiv» ist quiz-relevant.",
			"Rueckmeldung" => "Review-Rückmeldung zur Frage: wichtig, gut, überarbeiten, nicht relevant oder offen.",
			"RueckmeldungText" => "Freitext zur Review-Rückmeldung; kann in der Review-Ansicht mit einem Klick geleert werden.",
			"Aktiv" => "Legacy-Spalte; die Quiz-Auswahl folgt «Status» (siehe dort). Kann in älteren CSV noch stehen.",
			"Locked" => "„Ja“ sperrt die Antwortwahl nach dem Absenden (kein erneuter Submit wie im Quiz-Verhalten).",
			"Änderung" => "Datum (TT.MM.JJJJ) der letzten inhaltlichen Frage-Änderung; wird beim Speichern gesetzt, nicht manuell pflegen.",
			"Prioritaet" => "Einsatzrelevanz bei Auswahl begrenzter Fragenzahl (höhere Zahl zuerst): 4 = sicherheits-, rechts- oder einsatzkritisch; 3 = stark praxisrelevant für Drohneneinsatz/Suche/Technik; 2 = unterstützendes Grundlagenwissen; 1 = randständige Detailfragen; 0 = keine Prioritätsstufe / wie bisher.",
		);
	}
	return isset($map[$qh]) ? $map[$qh] : "";
}

function da_assessment_field_tooltip($h) {
	static $map = null;
	if ($map === null) {
		$map = array(
			"Nr" => "Fortlaufende ID in AssessmentListe.csv; wird beim Speichern vergeben, nicht manuell setzen.",
			"Key" => "Eindeutiger Schlüssel (URL-tauglich). Buchstaben (Gross/Klein), Zahlen, Bindestrich, Unterstrich; leer → aus dem Titel abgeleitet. Vergleich im System ohne Beachtung der Gross-/Kleinschreibung.",
			"Titel" => "Anzeigename in Listen und beim Öffnen des Quiz.",
			"Kurzbeschreibung" => "Kurztext für Übersichten; mehrere Zeilen möglich.",
			"Bild" => "Titelbild: relativer Pfad unter images/ (Datei oder unterordner/Datei entsprechend DriveLocation).",
			"Einfuehrung" => "Einleitung vor Quiz-Start (Text; HTML je nach Anzeige im Quiz).",
			"Erfuellt" => "Bestehensgrenze in Prozent (0–100); intern als Anteil 0–1 gespeichert.",
			"PunkteModus" => "Punktezählung im Quiz: Standard (1 Punkt/Frage), Teilpunkte gewichtet (max 1 je Mehrfachfrage) oder Teilpunkte pro Option. Beispiel für ähnliche Schwierigkeit wie Standard 80%: teilpunkt_gewichtet ca. 85-86%, teilpunkt_pro_option ca. 88-89%.",
			"DriveLocation" => "Unterordner unter images/ für Bild-Picker (z. B. Einsatzpruefung oder Einsatzprüfung); Buchstaben inkl. Umlaute, Ziffern, _ - / erlaubt, kein ..",
			"LernDatei" => "HTML-Schulung relativ zum Assess-Ordner; wird als Lerninhalt verlinkt/ausgeliefert.",
			"FragenDatei" => "CSV der Fragen relativ zum Ordner (z. B. mein-quiz.csv).",
			"AnzQuestions" => "Anzahl Fragen pro Durchgang für Digital-Quiz und Papierformular (ganze Zahl ≥ 0). Gilt je Assessment/Quiz-Typ.",
			"Audienz" => "Wer sieht dieses Assessment: „Alle“ oder mehrere Codes kommagetrennt (Abgleich pro Gruppe zum Kanal; RKRS gilt für RKRSplus/RKRSprev).",
			"QuizTime" => "Gesamt-Timer für das Quiz in Sekunden; 0 = kein automatischer Abbruch.",
			"Quiz" => "„Ja“: normales Bewertungs-Quiz (Anzeige/Logik entsprechend).",
			"Schulung" => "„Ja“: in Schulungs-/Wissens-Ansichten nutzbar.",
			"AntwortMischen" => "„Ja“: Antwortoptionen (A/B/C …) pro Frage beim Quiz- und Schulungsabruf zufällig mischen; die gespeicherte Lösung wird angepasst.",
			"FragenBildAnzeigen" => "„Ja“: Fragebilder werden im Quiz angezeigt. „Nein“: Fragebilder werden ausgeblendet.",
			"Aktiv" => "„Nein“: Assessment in Standard-Auswahl ausgeblendet.",
			"Locked" => "„Ja“: Quiz-Set gesperrt, wird beim Laden nicht angeboten.",
		);
	}
	return isset($map[$h]) ? $map[$h] : "";
}

$pageMode = "manage";
if ($pageMode === "manage") {
	$assessmentHeaders = array("Nr","Key","Titel","Kurzbeschreibung","Bild","Einfuehrung","Erfuellt","PunkteModus","DriveLocation","LernDatei","FragenDatei","AnzQuestions","Audienz","QuizTime","Quiz","Schulung","AntwortMischen","FragenBildAnzeigen","Aktiv","Locked");
	$questionHeaders = rkrk_assess_question_csv_headers();
	$assessmentPath = $QuizLoc . "AssessmentListe.csv";
	$assessments = da_read_csv_assoc($assessmentPath, $assessmentHeaders);
	$assessmentByKey = array();
	foreach ($assessments as $idx => $aRow) {
		$key = trim((string)$aRow["Key"]);
		if ($key !== "") {
			$assessmentByKey[$key] = $idx;
		}
	}

	$selectedKey = isset($_REQUEST["selected_quiz"]) ? trim((string)$_REQUEST["selected_quiz"]) : "";
	if ($selectedKey === "" && isset($_REQUEST["quiz"])) {
		$quizParam = trim((string)$_REQUEST["quiz"]);
		if ($quizParam !== "") {
			$quizIdx = da_find_assessment_row_index($assessments, $quizParam);
			if ($quizIdx !== null) {
				$selectedKey = trim((string)($assessments[$quizIdx]["Key"] ?? ""));
			} else {
				foreach ($assessments as $aRow) {
					$aKey = trim((string)($aRow["Key"] ?? ""));
					$aTitle = trim((string)($aRow["Titel"] ?? ""));
					if ($aKey !== "" && strcasecmp($aTitle, $quizParam) === 0) {
						$selectedKey = $aKey;
						break;
					}
				}
			}
		}
	}
	if ($selectedKey !== "") {
		$selIdx = da_find_assessment_row_index($assessments, $selectedKey);
		if ($selIdx !== null) {
			$selectedKey = trim((string)($assessments[$selIdx]["Key"] ?? $selectedKey));
		}
	}
	if ($selectedKey === "" && count($assessments) > 0) {
		$selectedKey = (string)$assessments[0]["Key"];
	}

	$message = "";
	$error = "";
	$action = isset($_POST["admin_action"]) ? (string)$_POST["admin_action"] : "";

	if ($_SERVER["REQUEST_METHOD"] === "POST" && $action !== "") {
		if ($action === "save_assessment") {
			$form = isset($_POST["a"]) && is_array($_POST["a"]) ? $_POST["a"] : array();
			$row = array();
			foreach ($assessmentHeaders as $h) {
				if ($h === "Nr") {
					continue;
				}
				$row[$h] = isset($form[$h]) ? trim((string)$form[$h]) : "";
			}
			$row["Key"] = da_normalize_key($row["Key"], $row["Titel"]);
			$row["FragenDatei"] = da_clean_relative_file($row["FragenDatei"], "csv");
			$row["LernDatei"] = da_clean_relative_file($row["LernDatei"], "html");
			$row["Bild"] = da_clean_relative_file($row["Bild"], "");
			$row["DriveLocation"] = da_clean_relative_folder($row["DriveLocation"]);
			if ($row["Aktiv"] === "") $row["Aktiv"] = "Ja";
			if ($row["Locked"] === "") $row["Locked"] = "Nein";
			if ($row["Quiz"] === "") $row["Quiz"] = "Ja";
			if ($row["Schulung"] === "") $row["Schulung"] = "Nein";
			if ($row["AntwortMischen"] === "") $row["AntwortMischen"] = "Nein";
			if ($row["FragenBildAnzeigen"] === "") $row["FragenBildAnzeigen"] = "Ja";
			$__punkteModus = strtolower(trim((string)($row["PunkteModus"] ?? "")));
			$__punkteModusAllowed = array("standard", "teilpunkt_gewichtet", "teilpunkt_pro_option");
			if ($__punkteModus === "" || !in_array($__punkteModus, $__punkteModusAllowed, true)) {
				$__punkteModus = "standard";
			}
			$row["PunkteModus"] = $__punkteModus;
			if (strcasecmp((string)$row["FragenBildAnzeigen"], "Nein") === 0) {
				$row["FragenBildAnzeigen"] = "Nein";
			} else {
				$row["FragenBildAnzeigen"] = "Ja";
			}
			$row["Audienz"] = da_normalize_audienz_csv(isset($row["Audienz"]) ? $row["Audienz"] : "");
			if ($row["Audienz"] === "") {
				$row["Audienz"] = "Alle";
			}
			if ($row["Erfuellt"] === "") {
				$row["Erfuellt"] = "0.75";
			} else {
				// UI sends percent (e.g. 75), CSV stores factor (0.75)
				$erVal = (float)str_replace(',', '.', (string)$row["Erfuellt"]);
				if ($erVal > 1.0) {
					$erVal = $erVal / 100.0;
				}
				if ($erVal < 0) $erVal = 0;
				if ($erVal > 1) $erVal = 1;
				$row["Erfuellt"] = rtrim(rtrim(number_format($erVal, 4, '.', ''), '0'), '.');
				if ($row["Erfuellt"] === "") $row["Erfuellt"] = "0";
			}
			if ($row["AnzQuestions"] === "") $row["AnzQuestions"] = "30";

			if ($row["Key"] === "" || $row["Titel"] === "") {
				$error = "Bitte mindestens Key/Titel ausfuellen.";
			} else {
				$postedSel = isset($_POST["selected_quiz"]) ? trim((string)$_POST["selected_quiz"]) : "";
				$idxBySelection = ($postedSel !== "") ? da_find_assessment_row_index($assessments, $postedSel) : null;

				$idxByKey = da_find_assessment_row_index($assessments, $row["Key"]);
				if ($idxBySelection !== null) {
					if ($idxByKey !== null && $idxByKey !== $idxBySelection) {
						$error = "Key ist bereits vergeben (Gross-/Kleinschreibung wird beim Vergleich ignoriert).";
					} else {
						$existingIdx = $idxBySelection;
						$row["Nr"] = trim((string)($assessments[$existingIdx]["Nr"] ?? ""));
						if ($row["Nr"] === "") {
							$maxNr = 0;
							foreach ($assessments as $oldRow) {
								$n = (int)$oldRow["Nr"];
								if ($n > $maxNr) {
									$maxNr = $n;
								}
							}
							$row["Nr"] = (string)($maxNr + 1);
						}
						$assessments[$existingIdx] = $row;
					}
				} elseif ($idxByKey !== null) {
					$existingIdx = $idxByKey;
					$row["Nr"] = trim((string)($assessments[$existingIdx]["Nr"] ?? ""));
					if ($row["Nr"] === "") {
						$maxNr = 0;
						foreach ($assessments as $oldRow) {
							$n = (int)$oldRow["Nr"];
							if ($n > $maxNr) {
								$maxNr = $n;
							}
						}
						$row["Nr"] = (string)($maxNr + 1);
					}
					$assessments[$existingIdx] = $row;
				} else {
					$maxNr = 0;
					foreach ($assessments as $oldRow) {
						$n = (int)$oldRow["Nr"];
						if ($n > $maxNr) {
							$maxNr = $n;
						}
					}
					$row["Nr"] = (string)($maxNr + 1);
					$assessments[] = $row;
				}
				if ($error === "") {
					if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
						rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
					}
					if (!da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments)) {
						$error = "AssessmentListe.csv konnte nicht gespeichert werden.";
					} else {
						$message = "Assessment gespeichert.";
						$selectedKey = $row["Key"];
					}
				}
			}
		} elseif ($action === "delete_assessment") {
			$deleteKey = isset($_POST["delete_key"]) ? trim((string)$_POST["delete_key"]) : "";
			if ($deleteKey !== "") {
				$deleteIdx = da_find_assessment_row_index($assessments, $deleteKey);
				if ($deleteIdx !== null) {
					unset($assessments[$deleteIdx]);
					$assessments = array_values($assessments);
				}
				if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
					rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
				}
				if (!da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments)) {
					$error = "Assessment konnte nicht geloescht werden.";
				} else {
					$message = "Assessment geloescht.";
					$selectedKey = count($assessments) ? (string)$assessments[0]["Key"] : "";
				}
			}
		}

		$assessmentByKey = array();
		foreach ($assessments as $idx => $aRow) {
			$key = trim((string)$aRow["Key"]);
			if ($key !== "") {
				$assessmentByKey[$key] = $idx;
			}
		}
	}

	$selectedAssessment = null;
	$selectedIdx = ($selectedKey !== "") ? da_find_assessment_row_index($assessments, $selectedKey) : null;
	if ($selectedIdx !== null) {
		$selectedAssessment = $assessments[(int)$selectedIdx];
		$selectedKey = trim((string)($selectedAssessment["Key"] ?? $selectedKey));
	}
	if (!is_array($selectedAssessment)) {
		$selectedAssessment = array_fill_keys($assessmentHeaders, "");
	}

	$questionsPath = "";
	$questions = array();
	$daQuestionsFileWarn = "";
	$rawFragenCsv = trim(str_replace("\\", "/", (string)$selectedAssessment["FragenDatei"]));
	if ($rawFragenCsv !== "") {
		$cleanRel = da_clean_relative_file($rawFragenCsv, "csv");
		$candidates = array();
		$p0 = $QuizLoc . ltrim($rawFragenCsv, "/");
		$candidates[] = $p0;
		if ($cleanRel !== "" && ($QuizLoc . $cleanRel) !== $p0) {
			$candidates[] = $QuizLoc . $cleanRel;
		}
		$resolved = "";
		foreach ($candidates as $cand) {
			if ($cand !== "" && is_file($cand)) {
				$resolved = $cand;
				break;
			}
		}
		if ($resolved !== "") {
			$questionsPath = $resolved;
			$questions = da_read_csv_assoc($questionsPath, $questionHeaders);
		} else {
			if ($cleanRel === "") {
				$daQuestionsFileWarn = "Fragen-Datei: Ungültiger Pfad nach Prüfung. Erlaubt sind Buchstaben (inkl. Umlaute), Ziffern, Unterstrich, Bindestrich, Schrägstrich und Punkt.";
			} else {
				$daQuestionsFileWarn = "Fragen-CSV nicht gefunden. In der Liste steht: «" . da_h($rawFragenCsv) . "». "
					. "Liegt die Datei wirklich im Ordner und endet sie z. B. korrekt mit «Flugrecht.csv»? Nach dem Laden erscheint hier der vollständige Name.";
			}
		}
	}

	$learningContent = "";
	$learningPath = "";
	if (trim((string)$selectedAssessment["LernDatei"]) !== "") {
		$learningPath = $QuizLoc . $selectedAssessment["LernDatei"];
		if (file_exists($learningPath)) {
			$learningContent = (string)@file_get_contents($learningPath);
		}
	}
	$daLearnEditor = da_learning_editor_parts($learningContent);
	$daLearnBodyHash = md5((string)$daLearnEditor["body"]);
	$daLearnContentCss = array();
	if (function_exists("rkrk_assess_resolve_web_path_from_script")) {
		$daLearnContentCss[] = rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme.css");
		if (function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
			$daLearnContentCss[] = rkrk_assess_resolve_web_path_from_script("rkrk_assess_theme_rkrs.css");
		}
	}
	$daLearnPreviewUrl = "";
	if ($selectedKey !== "" && function_exists("rkrk_assess_append_setting_href") && function_exists("rkrk_assess_merge_query_with_setting")) {
		$daLearnPreviewUrl = rkrk_assess_append_setting_href(
			"rkrk_digitalassess_learn.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("quiz" => $selectedKey)))
		);
	}

	$manageImageBaseWeb = function_exists('rkrk_dga_rh_web_image_base') ? rkrk_dga_rh_web_image_base() : $QuizIconLoc;

	$daJaNeinChoices = array("Ja", "Nein");
	$daAudienzAssessmentChoices = array("Alle", "PUB", "RKRK", "RKRS", "RKRSplus", "RKRSprev", "TEST", "ADMIN");
	foreach ($assessments as $aRw) {
		$toks = function_exists("rkrk_assess_audience_segments")
			? rkrk_assess_audience_segments((string)($aRw["Audienz"] ?? ""))
			: array(trim((string)($aRw["Audienz"] ?? "")));
		foreach ($toks as $tok) {
			if ($tok !== "" && !in_array($tok, $daAudienzAssessmentChoices, true)) {
				$daAudienzAssessmentChoices[] = $tok;
			}
		}
	}

	$daQAudienzChoices = array_merge($daAudienzAssessmentChoices, $daJaNeinChoices);
	foreach (array("ja", "nein") as $__qn) {
		if (!in_array($__qn, $daQAudienzChoices, true)) {
			$daQAudienzChoices[] = $__qn;
		}
	}
	unset($__qn);

	$daQBereichChoices = array("");
	$titForBereich = trim((string)($selectedAssessment["Titel"]));
	if ($titForBereich !== "") {
		$daQBereichChoices[] = $titForBereich;
	}
	$daQBildformatChoices = array("", "schmal", "normal", "breit", "Seite 1");
	$daQZeitChoices = array("", "alle", "0", "10", "15", "20", "30", "45", "60", "90", "120");
	$daQPrioritaetChoices = array("0", "1", "2", "3", "4");
	$daQStatusChoices = function_exists("rkrk_assess_question_status_options") ? rkrk_assess_question_status_options() : array("neu", "prüfen", "geprüft", "aktiv", "inaktiv");
	$daQReviewChoices = array("offen", "wichtig", "gut", "überarbeiten", "nicht relevant");
	$daQDroneChoices = array("", "Alle");
	foreach ($questions as $qx) {
		if (!is_array($qx)) {
			continue;
		}
		$bToks = function_exists("rkrk_assess_audience_segments")
			? rkrk_assess_audience_segments((string)($qx["Audienz"] ?? ""))
			: array(trim((string)($qx["Audienz"] ?? "")));
		foreach ($bToks as $tok) {
			if ($tok !== "" && !in_array($tok, $daQAudienzChoices, true)) {
				$daQAudienzChoices[] = $tok;
			}
		}
		$bBe = trim((string)($qx["Bereich"] ?? ""));
		if ($bBe !== "" && !in_array($bBe, $daQBereichChoices, true)) {
			$daQBereichChoices[] = $bBe;
		}
		$bBf = trim((string)($qx["Bildformat"] ?? ""));
		if ($bBf !== "" && !in_array($bBf, $daQBildformatChoices, true)) {
			$daQBildformatChoices[] = $bBf;
		}
		$bZ = trim((string)($qx["Zeit"] ?? ""));
		if ($bZ !== "" && !in_array($bZ, $daQZeitChoices, true)) {
			$daQZeitChoices[] = $bZ;
		}
		$bD = trim((string)($qx["Drone"] ?? ""));
		if ($bD !== "" && !in_array($bD, $daQDroneChoices, true)) {
			$daQDroneChoices[] = $bD;
		}
		$bPr = trim((string)($qx["Prioritaet"] ?? ""));
		if ($bPr !== "" && !in_array($bPr, $daQPrioritaetChoices, true)) {
			$daQPrioritaetChoices[] = $bPr;
		}
		$bSt = trim((string)($qx["Status"] ?? ""));
		if ($bSt !== "" && !in_array($bSt, $daQStatusChoices, true)) {
			$daQStatusChoices[] = $bSt;
		}
		$bRev = trim((string)($qx["Rueckmeldung"] ?? ""));
		if ($bRev !== "" && !in_array($bRev, $daQReviewChoices, true)) {
			$daQReviewChoices[] = $bRev;
		}
	}

	// Quiz-Auswahl: Anzeige-Wahrscheinlichkeit (Monte-Carlo, gleiche Formel wie rkrk_digitalassess.php)
	$daProbPickN = isset($_REQUEST["da_prob_pickn"])
		? max(0, (int) $_REQUEST["da_prob_pickn"])
		: max(0, (int) ($selectedAssessment["AnzQuestions"] ?? 0));
	if ($daProbPickN < 1) {
		$daProbPickN = 30;
	}
	$daProbDrone = isset($_REQUEST["da_prob_drone"]) ? trim((string) $_REQUEST["da_prob_drone"]) : "Alle";
	$daProbSetting = isset($setting) ? (string) $setting : "RKRS";
	$daProbIterations = 5000;
	// Monte-Carlo ist teuer (5000 Läufe, bei Filter «Alle» je Drohnentyp). Daher nur berechnen,
	// wenn ausdrücklich angefordert (Klick auf «Berechnen» setzt da_prob_calc=1). Beim normalen
	// Laden/Wechseln des Assessments bleibt der leere Fallback -> Seite lädt schnell.
	$daProbCalc = isset($_REQUEST["da_prob_calc"])
		&& (string) $_REQUEST["da_prob_calc"] !== ""
		&& (string) $_REQUEST["da_prob_calc"] !== "0";
	$daProbPreview = ($daProbCalc && $selectedKey !== "" && function_exists("rkrk_dga_build_question_probability_preview"))
		? rkrk_dga_build_question_probability_preview($questions, $daProbSetting, $daProbPickN, $daProbDrone, $daProbIterations)
		: array(
			"prob_map" => array(),
			"eligible_keys" => array(),
			"eligible" => array(),
			"pick_effective" => 0,
			"pool_count" => 0,
			"blended" => false,
			"drone_filter" => $daProbDrone,
			"drone_types" => array(),
			"iterations" => $daProbIterations,
		);
	$daProbEligible = isset($daProbPreview["eligible"]) && is_array($daProbPreview["eligible"]) ? $daProbPreview["eligible"] : array();
	$daProbPickEffective = (int) ($daProbPreview["pick_effective"] ?? 0);
	$daProbMap = isset($daProbPreview["prob_map"]) && is_array($daProbPreview["prob_map"]) ? $daProbPreview["prob_map"] : array();
	$daProbEligibleKeys = array();
	foreach ((isset($daProbPreview["eligible_keys"]) && is_array($daProbPreview["eligible_keys"])) ? $daProbPreview["eligible_keys"] : array() as $daProbEk => $_) {
		$daProbEligibleKeys[(string) $daProbEk] = true;
	}
	$daProbBlended = !empty($daProbPreview["blended"]);
	$daProbDroneTypesInQuiz = isset($daProbPreview["drone_types"]) && is_array($daProbPreview["drone_types"]) ? $daProbPreview["drone_types"] : array();
	$daProbPoolCountDisplay = (int) ($daProbPreview["pool_count"] ?? count($daProbEligible));
	$daProbDroneOptions = array("Alle");
	foreach ($questions as $qx) {
		if (!is_array($qx)) {
			continue;
		}
		$d = function_exists("rkrk_assess_normalize_drone")
			? rkrk_assess_normalize_drone(isset($qx["Drone"]) ? $qx["Drone"] : "")
			: trim((string) ($qx["Drone"] ?? ""));
		if ($d !== "" && $d !== "Alle" && !in_array($d, $daProbDroneOptions, true)) {
			$daProbDroneOptions[] = $d;
		}
	}
	sort($daProbDroneOptions, SORT_STRING);
	if ($daProbDrone !== "" && $daProbDrone !== "Alle" && !in_array($daProbDrone, $daProbDroneOptions, true)) {
		$daProbDroneOptions[] = $daProbDrone;
	}
	$GLOBALS["daProbPickN"] = $daProbPickN;
	$GLOBALS["daProbDrone"] = $daProbDrone;
	$daProbPriorityHelp = "Prioritaet 1 = optional, 2 = relevant, 3 = wichtig, 4 = kritisch. Gewicht im Pool: w = (P+1)².";

	if ($_SERVER["REQUEST_METHOD"] === "POST" && $selectedKey !== "" && strcasecmp(trim((string)$selectedAssessment["Key"]), $selectedKey) === 0) {
		if ($action === "save_questions") {
			$questionsSnapshot = $questions;
			$driveLocForQuestions = da_clean_relative_folder((string)(function_exists("rkrk_assess_effective_drive_location")
				? rkrk_assess_effective_drive_location($selectedAssessment, $questions)
				: $selectedAssessment["DriveLocation"]));
			$qFile = isset($_POST["question_file"]) ? da_clean_relative_file($_POST["question_file"], "csv") : "";
			if ($qFile === "") {
				$qFile = da_clean_relative_file((string)$selectedAssessment["FragenDatei"], "csv");
			}
			if ($qFile === "") {
				$qFile = da_normalize_key($selectedKey) . ".csv";
			}
			$darOrig = function_exists("rkrk_assess_decode_dar_q_row_orig_from_post")
				? rkrk_assess_decode_dar_q_row_orig_from_post($_POST)
				: array();
			$raw = rkrk_assess_post_decode_questions($questionHeaders, $_POST);
			$newRows = array();
			$maxIdx = -1;
			foreach ($raw as $colVals) {
				if (!is_array($colVals)) {
					continue;
				}
				foreach (array_keys($colVals) as $k) {
					$ik = (int)$k;
					if ($ik > $maxIdx) {
						$maxIdx = $ik;
					}
				}
			}
			for ($i = 0; $i <= $maxIdx; $i++) {
				$line = array();
				foreach ($questionHeaders as $h) {
					$line[$h] = isset($raw[$h][$i]) ? trim((string)$raw[$h][$i]) : "";
				}
				$hasContent = trim($line["Frage"]) !== "" || trim($line["Loesung"]) !== "";
				if (!$hasContent) {
					continue;
				}
				$line["Nr"] = (string)(count($newRows) + 1);
				if ($line["Aktiv"] === "") $line["Aktiv"] = "Ja";
				if ($line["Locked"] === "") $line["Locked"] = "Nein";
				if (trim((string)($line["Status"] ?? "")) === "") $line["Status"] = "aktiv";
				$line["Audienz"] = da_normalize_audienz_csv(isset($line["Audienz"]) ? $line["Audienz"] : "");
				if ($line["Audienz"] === "") {
					$line["Audienz"] = "Alle";
				}
				if (trim((string)($line["Prioritaet"] ?? "")) === "") {
					$line["Prioritaet"] = "0";
				}
				if (trim((string)($line["Rueckmeldung"] ?? "")) === "") {
					$line["Rueckmeldung"] = "offen";
				}
				$line["Drone"] = trim((string)($line["Drone"] ?? ""));
				$linePreImg = $line;
				$line["Frage-Bild"] = da_with_drive_location($line["Frage-Bild"], $driveLocForQuestions);
				$line["Loesungs-Bild"] = da_with_drive_location($line["Loesungs-Bild"], $driveLocForQuestions);
				$origI = isset($darOrig[$i]) ? (int) $darOrig[$i] : $i;
				$oldRow = ($origI >= 0 && $origI < count($questionsSnapshot) && isset($questionsSnapshot[$origI]) && is_array($questionsSnapshot[$origI]))
					? $questionsSnapshot[$origI]
					: null;
				if (function_exists("rkrk_assess_apply_aenderung_stempel")) {
					rkrk_assess_apply_aenderung_stempel($oldRow, $line, $linePreImg);
				}
				$newRows[] = $line;
			}
			$targetQuestionPath = $QuizLoc . $qFile;
			if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
				rkrk_assess_fragenkatalog_before_overwrite($targetQuestionPath, $QuizLoc);
			}
			if (!da_write_csv_assoc($targetQuestionPath, $questionHeaders, $newRows)) {
				$error = "Fragen konnten nicht gespeichert werden.";
			} else {
				$message = "Fragen gespeichert.";
				$questionsPath = $targetQuestionPath;
				$questions = $newRows;
				$selectedAssessment["FragenDatei"] = $qFile;
				if (isset($assessmentByKey[$selectedKey])) {
					$assessments[(int)$assessmentByKey[$selectedKey]]["FragenDatei"] = $qFile;
					if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
						rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
					}
					da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments);
				}
			}
		} elseif ($action === "save_question_review") {
			if ($questionsPath === "" || !is_file($questionsPath)) {
				$error = "Keine Fragen-CSV für Review gefunden.";
			} else {
				$questionsSnapshot = $questions;
				$darOrig = function_exists("rkrk_assess_decode_dar_q_row_orig_from_post")
					? rkrk_assess_decode_dar_q_row_orig_from_post($_POST)
					: array();
				$raw = rkrk_assess_post_decode_questions($questionHeaders, $_POST);
				$newRows = array();
				$maxIdx = -1;
				foreach ($raw as $colVals) {
					if (!is_array($colVals)) {
						continue;
					}
					foreach (array_keys($colVals) as $k) {
						$ik = (int)$k;
						if ($ik > $maxIdx) {
							$maxIdx = $ik;
						}
					}
				}
				for ($i = 0; $i <= $maxIdx; $i++) {
					$line = array();
					foreach ($questionHeaders as $h) {
						$line[$h] = isset($raw[$h][$i]) ? trim((string)$raw[$h][$i]) : "";
					}
					$hasContent = trim($line["Frage"]) !== "" || trim($line["Loesung"]) !== "";
					if (!$hasContent) {
						continue;
					}
					$line["Nr"] = (string)(count($newRows) + 1);
					if ($line["Aktiv"] === "") $line["Aktiv"] = "Ja";
					if ($line["Locked"] === "") $line["Locked"] = "Nein";
					if (trim((string)$line["Status"]) === "") $line["Status"] = "aktiv";
					if (trim((string)$line["Prioritaet"]) === "") $line["Prioritaet"] = "0";
					if (trim((string)$line["Rueckmeldung"]) === "") $line["Rueckmeldung"] = "offen";
					$line["Audienz"] = da_normalize_audienz_csv(isset($line["Audienz"]) ? $line["Audienz"] : "");
					if ($line["Audienz"] === "") {
						$line["Audienz"] = "Alle";
					}
					$origI = isset($darOrig[$i]) ? (int) $darOrig[$i] : $i;
					$oldRow = ($origI >= 0 && $origI < count($questionsSnapshot) && isset($questionsSnapshot[$origI]) && is_array($questionsSnapshot[$origI]))
						? $questionsSnapshot[$origI]
						: null;
					if (function_exists("rkrk_assess_apply_aenderung_stempel")) {
						rkrk_assess_apply_aenderung_stempel($oldRow, $line, null);
					}
					$newRows[] = $line;
				}
				if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
					rkrk_assess_fragenkatalog_before_overwrite($questionsPath, $QuizLoc);
				}
				if (!da_write_csv_assoc($questionsPath, $questionHeaders, $newRows)) {
					$error = "Review konnte nicht gespeichert werden.";
				} else {
					$message = "Review gespeichert.";
					$questions = $newRows;
				}
			}
		} elseif ($action === "save_learning_html") {
			$_POST["learning_file_fallback"] = (string) $selectedAssessment["LernDatei"];
			$rLearnSave = rkrk_dle_process_save_learning_html(
				$QuizLoc,
				$assessmentPath,
				$assessments,
				$assessmentHeaders,
				$selectedKey,
				$assessmentByKey,
				$_POST,
				static function ($path, $headers, $rows) {
					return da_write_csv_assoc($path, $headers, $rows);
				}
			);
			$message = (string) $rLearnSave["message"];
			$error = (string) $rLearnSave["error"];
			if ($rLearnSave["learning_path"] !== "") {
				$learningPath = (string) $rLearnSave["learning_path"];
			}
			if ($rLearnSave["learning_content"] !== "") {
				$learningContent = (string) $rLearnSave["learning_content"];
				$daLearnEditor = $rLearnSave["learn_editor"];
				$daLearnBodyHash = md5((string) $daLearnEditor["body"]);
			}
			if ($rLearnSave["learn_file"] !== "" && isset($assessmentByKey[$selectedKey])) {
				$selectedAssessment["LernDatei"] = (string) $rLearnSave["learn_file"];
			}
		} elseif ($action === "download_learning_html") {
			$learnFile = isset($_POST["learning_file"]) ? da_clean_relative_file($_POST["learning_file"], "html") : "";
			if ($learnFile === "") {
				$learnFile = da_clean_relative_file((string)$selectedAssessment["LernDatei"], "html");
			}
			if ($learnFile === "") {
				$error = "Keine LernDatei definiert.";
			} else {
				$targetLearningPath = $QuizLoc . $learnFile;
				if (!file_exists($targetLearningPath)) {
					$error = "Schulungstext-Datei nicht gefunden: " . $learnFile;
				} else {
					header('Content-Type: text/html; charset=UTF-8');
					header('Content-Disposition: attachment; filename="' . basename($learnFile) . '"');
					header('Content-Length: ' . (string)filesize($targetLearningPath));
					readfile($targetLearningPath);
					exit;
				}
			}
		} elseif ($action === "upload_learning_html_file") {
			$learnFile = isset($_POST["learning_file"]) ? da_clean_relative_file($_POST["learning_file"], "html") : "";
			if ($learnFile === "") {
				$learnFile = da_clean_relative_file((string)$selectedAssessment["LernDatei"], "html");
			}
			if ($learnFile === "") {
				$learnFile = da_normalize_key($selectedKey) . ".html";
			}
			$tmp = isset($_FILES["learning_html_file"]["tmp_name"]) ? (string)$_FILES["learning_html_file"]["tmp_name"] : "";
			$err = isset($_FILES["learning_html_file"]["error"]) ? (int)$_FILES["learning_html_file"]["error"] : UPLOAD_ERR_NO_FILE;
			if ($err !== UPLOAD_ERR_OK || $tmp === "") {
				$error = "Kein HTML-Upload empfangen.";
			} else {
				$content = @file_get_contents($tmp);
				if ($content === false) {
					$error = "Upload konnte nicht gelesen werden.";
				} else {
					$targetLearningPath = $QuizLoc . $learnFile;
					if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
						rkrk_assess_fragenkatalog_before_overwrite($targetLearningPath, $QuizLoc);
					}
					if (@file_put_contents($targetLearningPath, (string)$content) === false) {
						$error = "Schulungstext-Upload konnte nicht gespeichert werden.";
					} else {
						$message = "Schulungstext per HTML-Upload ersetzt.";
						$learningPath = $targetLearningPath;
						$learningContent = (string)$content;
						$selectedAssessment["LernDatei"] = $learnFile;
						if (isset($assessmentByKey[$selectedKey])) {
							$assessments[(int)$assessmentByKey[$selectedKey]]["LernDatei"] = $learnFile;
							if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
								rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
							}
							da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments);
						}
					}
				}
			}
		} elseif ($action === "upload_images" || $action === "upload_all_images") {
			$driveLoc = da_clean_relative_folder((string)(function_exists("rkrk_assess_effective_drive_location")
				? rkrk_assess_effective_drive_location($selectedAssessment, $questions)
				: $selectedAssessment["DriveLocation"]));
			if ($driveLoc === "") {
				$error = "Kein Bildordner: DriveLocation im Setup leer und aus den Frage-Bildern kein Ordner ableitbar.";
			} else {
			$targetDir = rtrim((string)$QuizLoc, "/\\") . "/images/";
			if ($driveLoc !== "") {
				$targetDir .= $driveLoc . "/";
			}
			if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true)) {
				$error = "Bildordner konnte nicht erstellt werden: " . $targetDir;
			} else {
				$uploaded = 0;
				$uploadedNames = array();
				if (isset($_FILES["upload_images"]) && is_array($_FILES["upload_images"]["name"])) {
					$count = count($_FILES["upload_images"]["name"]);
					for ($i = 0; $i < $count; $i++) {
						$tmp = (string)($_FILES["upload_images"]["tmp_name"][$i] ?? "");
						$orig = (string)($_FILES["upload_images"]["name"][$i] ?? "");
						$err = (int)($_FILES["upload_images"]["error"][$i] ?? UPLOAD_ERR_NO_FILE);
						if ($err !== UPLOAD_ERR_OK || $tmp === "" || $orig === "") {
							continue;
						}
						$base = basename($orig);
						$base = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $base);
						$dest = $targetDir . $base;
						if (@move_uploaded_file($tmp, $dest)) {
							$uploaded++;
							$uploadedNames[] = $base;
						}
					}
				}
				if ($action === "upload_all_images" && $uploaded > 0) {
					$uploadedMap = array();
					foreach ($uploadedNames as $nm) {
						$uploadedMap[strtolower($nm)] = $nm;
					}
					$drivePrefix = ($driveLoc !== "") ? (rtrim($driveLoc, "/") . "/") : "";
					$changed = 0;

					$assessmentBild = trim((string)$selectedAssessment["Bild"]);
					if ($assessmentBild !== "") {
						$assessmentBase = strtolower(basename($assessmentBild));
						if (isset($uploadedMap[$assessmentBase])) {
							$selectedAssessment["Bild"] = $drivePrefix . $uploadedMap[$assessmentBase];
							if (isset($assessmentByKey[$selectedKey])) {
								$assessments[(int)$assessmentByKey[$selectedKey]]["Bild"] = $selectedAssessment["Bild"];
							}
							$changed++;
						}
					}

					foreach ($questions as $qi => $qRow) {
						foreach (array("Frage-Bild","Loesungs-Bild") as $imgField) {
							$oldVal = trim((string)($qRow[$imgField] ?? ""));
							if ($oldVal === "") continue;
							$base = strtolower(basename($oldVal));
							if (!isset($uploadedMap[$base])) continue;
							$questions[$qi][$imgField] = $drivePrefix . $uploadedMap[$base];
							$changed++;
						}
					}

					if ($changed > 0) {
						if ($questionsPath !== "") {
							if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
								rkrk_assess_fragenkatalog_before_overwrite($questionsPath, $QuizLoc);
							}
							@da_write_csv_assoc($questionsPath, $questionHeaders, $questions);
						}
						if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
							rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
						}
						@da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments);
					}
				}
				$message = $uploaded > 0
					? ("Bilder hochgeladen: " . $uploaded . ". Zielordner: " . $targetDir)
					: "Keine Bilder hochgeladen.";
			}
			}
		}
	}

	$driveLocCurrent = da_clean_relative_folder((string)(function_exists("rkrk_assess_effective_drive_location")
		? rkrk_assess_effective_drive_location($selectedAssessment, $questions)
		: $selectedAssessment["DriveLocation"]));
	$imageSelectDir = rtrim((string)$QuizLoc, "/\\") . "/images/" . ($driveLocCurrent !== "" ? ($driveLocCurrent . "/") : "");
	$imageSelectOptions = array();
	if (is_dir($imageSelectDir)) {
		$list = glob($imageSelectDir . "*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}", GLOB_BRACE);
		if (is_array($list)) {
			natcasesort($list);
			foreach ($list as $p) {
				$bn = basename((string)$p);
				$imageSelectOptions[] = ($driveLocCurrent !== "" ? ($driveLocCurrent . "/") : "") . $bn;
			}
		}
	}
	?>
	<style>
	/* Schrift wie rkrk_assess_theme: einheitlich für ganze Seite (Portal-CSS + Manager) */
	body {
		font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
		font-size: 15px;
		line-height: 1.45;
		font-weight: 400;
		-webkit-font-smoothing: antialiased;
	}
	body button,
	body input,
	body select,
	body textarea {
		font-family: inherit;
	}
	.da-wrap {
		width: 100%;
		max-width: none;
		margin: 0;
		padding: 10px;
		box-sizing: border-box;
		--da-input-border: #7aad76;
		--da-input-border-focus: var(--brand, #67b516);
	}
	.da-assessment-toolbar { align-items: flex-end; }
	.da-assessment-toolbar select[name="selected_quiz"] {
		flex: 1 1 280px;
		min-width: min(560px, 100%);
		max-width: 100%;
		padding: 8px;
		box-sizing: border-box;
	}
	:root {
		--da-btn-bg: var(--brand);
		--da-btn-bg-hover: var(--brand-dark);
		--da-btn-danger-bg: var(--danger);
		--da-btn-danger-hover: #8f1111;
	}
	/* Grüne Rahmen wie Theme-Akzent (Textfelder, Zahlen, Selects, Datei-Upload) */
	.da-wrap input[type="text"],
	.da-wrap input[type="number"],
	.da-wrap input[type="file"],
	.da-wrap textarea,
	.da-wrap select {
		border: 1px solid var(--da-input-border);
		border-radius: 8px;
		background: var(--card, #fff);
	}
	.da-wrap input[type="text"]:focus,
	.da-wrap input[type="number"]:focus,
	.da-wrap input[type="file"]:focus,
	.da-wrap textarea:focus,
	.da-wrap select:focus {
		outline: none;
		border-color: var(--da-input-border-focus);
		box-shadow: 0 0 0 2px rgba(74, 140, 52, 0.18);
	}
	.da-grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
	@media (min-width: 1200px) { .da-grid { grid-template-columns: minmax(280px, 30vw) 10px minmax(0, 1fr); align-items: start; } }
	.da-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
	.da-title { margin: 0 0 10px; font-size: 1.25rem; font-weight: 700; line-height: 1.3; color: var(--text); }
	.da-fields { display: grid; grid-template-columns: 1fr; gap: 8px; }
	.da-fields.da-fields-setup {
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 10px 14px;
		align-items: start;
	}
	.da-field-label { display: flex; flex-direction: column; gap: 4px; margin: 0; min-width: 0; font-weight: inherit; }
	.da-field-cap { font-size: 12px; font-weight: 700; letter-spacing: 0.01em; color: var(--text); opacity: .95; }
	.da-id-badge {
		display: inline-block;
		padding: 4px 8px;
		border-radius: 6px;
		background: var(--kpi-bg);
		border: 1px solid var(--border);
		font-size: 12px;
		line-height: 1.35;
		font-family: inherit;
	}
	.da-id-badge.da-id-muted { color: #64748b; }
	.da-table thead th[title], .da-field-cap[title] { cursor: help; }
	.da-field-span-2 { grid-column: 1 / -1; }
	.da-field-compact-slot .da-input-compact,
	.da-field-compact-slot .da-select-compact {
		max-width: 10rem;
		width: 100%;
		box-sizing: border-box;
	}
	.da-field-compact-slot.da-slot-key .da-input-compact { max-width: min(100%, 16rem); }
	.da-field-compact-slot.da-slot-nr .da-input-compact { max-width: 5rem; }
	.da-fields input, .da-fields textarea, .da-fields select { width: 100%; box-sizing: border-box; padding: 9px; font-size: 14px; }
	.da-select-compact, .da-input-compact { font-size: 14px; }
	.da-table {
		width: 100%;
		min-width: 56rem;
		border-collapse: collapse;
		font-size: 14px;
		table-layout: auto;
	}
	.da-table th, .da-table td { border: 1px solid var(--table-row-border); padding: 6px; vertical-align: top; }
	.da-table th { background: var(--table-head-bg); white-space: nowrap; }
	.da-table td { overflow-wrap: anywhere; word-break: break-word; }
	.da-table th { position: sticky; top: 0; z-index: 2; }
	/* Schmal + kompakt: nur so breit wie nötig, Rest geht an die drei Text-Spalten */
	.da-table th.da-col-action,
	.da-table td.da-col-action { width: 1%; min-width: 3rem; max-width: 3.75rem; padding: 4px 3px; }
	.da-table th.da-col-nr,
	.da-table td.da-col-nr { width: 1%; min-width: 2rem; max-width: 3rem; text-align: center; white-space: nowrap; }
	.da-table th.da-col-bereich, .da-table td.da-col-bereich,
	.da-table th.da-col-bildformat, .da-table td.da-col-bildformat,
	.da-table th.da-col-loesung, .da-table td.da-col-loesung,
	.da-table th.da-col-antwort, .da-table td.da-col-antwort,
	.da-table th.da-col-zeit, .da-table td.da-col-zeit,
	.da-table th.da-col-audienz, .da-table td.da-col-audienz,
	.da-table th.da-col-prioritaet, .da-table td.da-col-prioritaet,
	.da-table th.da-col-status, .da-table td.da-col-status,
	.da-table th.da-col-aktiv, .da-table td.da-col-aktiv,
	.da-table th.da-col-locked, .da-table td.da-col-locked {
		width: 1%;
		max-width: 6.75rem;
		min-width: 4rem;
	}
	/* Drohne: etwas breiter, Text im Dropdown besser lesbar */
	.da-table th.da-col-drone, .da-table td.da-col-drone {
		width: 1%;
		min-width: 7.5rem;
		max-width: 12rem;
	}
	.da-table td.da-col-drone select.da-select-compact {
		font-size: 13px;
	}
	.da-table th.da-col-frage-bild,
	.da-table td.da-col-frage-bild,
	.da-table th.da-col-loesungs-bild,
	.da-table td.da-col-loesungs-bild {
		width: 1%;
		min-width: 4.5rem;
		max-width: clamp(4.75rem, 7vw, 7rem);
		padding: 4px 3px;
	}
	/* Obergrenze pro Text-Spalte, damit Bild/Dropdowns in einem Screen erreichbar bleiben (Scroll im Wrap) */
	.da-table th.da-col-frage,
	.da-table td.da-col-frage,
	.da-table th.da-col-loesungsauswahl,
	.da-table td.da-col-loesungsauswahl,
	.da-table th.da-col-loesungstext,
	.da-table td.da-col-loesungstext {
		width: auto;
		min-width: 11rem;
		max-width: 19rem;
	}
	.da-table td.da-col-frage textarea,
	.da-table td.da-col-loesungsauswahl textarea,
	.da-table td.da-col-loesungstext textarea {
		display: block;
		box-sizing: border-box;
		width: 100%;
		min-width: 0;
		min-height: 72px;
		resize: vertical;
	}
	.da-table td textarea { min-height: 44px; resize: vertical; }
	.da-table td input[type="text"] { min-width: 0; box-sizing: border-box; max-width: 100%; }
	.da-table td.da-col-loesungs-bild select,
	.da-table td.da-col-frage-bild select { max-width: 100%; box-sizing: border-box; font-size: 11px; }
	.da-table td.da-col-frage-bild input[type="text"],
	.da-table td.da-col-loesungs-bild input[type="text"] {
		min-width: 0;
		width: 100%;
		max-width: 100%;
	}
	.da-table td select.da-select-compact {
		min-width: 0;
		width: 100%;
		max-width: 100%;
		box-sizing: border-box;
		padding: 4px;
		font-size: 11px;
	}
	.da-inline { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 8px; }
	.da-jn-wrap { display: inline-flex; border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1; vertical-align: middle; }
	.da-jn { display: inline-flex; align-items: stretch; margin: 0; cursor: pointer; user-select: none; position: relative; }
	.da-jn input { position: absolute; opacity: 0; width: 0; height: 0; margin: 0; }
	.da-jn span { padding: 4px 11px; font-size: 11px; font-weight: 600; display: inline-block; line-height: 1.3; background: #e2e8f0; color: #475569; }
	.da-jn-yes:has(input:checked) span { background: #15803d; color: #fff; }
	.da-jn-no:has(input:checked) span { background: #475569; color: #fff; }
	.da-fields-setup .da-jn span { padding: 5px 14px; font-size: 13px; }
	/* volle Pfad-Anzeige (lange Namen wie …Flugrecht.csv sonst optisch „abgeschnitten“) */
	.da-question-file-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 10px; }
	.da-question-file-label { flex: 1 1 320px; min-width: min(100%, 220px); margin: 0; }
	.da-question-file-label .da-field-cap { margin-bottom: 2px; }
	.da-input-question-file { width: 100%; box-sizing: border-box; padding: 9px; font-size: 14px; }
	.da-question-file-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
	.da-btn {
		border: 0;
		border-radius: 8px;
		padding: 8px 12px;
		background: var(--da-btn-bg);
		color: #fff;
		text-decoration: none;
		cursor: pointer;
		font-family: inherit;
		font-size: 14px;
		font-weight: 600;
		line-height: 1.25;
		transition: background-color .15s ease, border-color .15s ease, opacity .15s ease;
	}
	.da-btn:hover { background: var(--da-btn-bg-hover); }
	.da-btn-danger { background: var(--da-btn-danger-bg); }
	.da-btn-danger:hover { background: var(--da-btn-danger-hover); }
	.da-btn:disabled,
	.da-btn-danger:disabled {
		opacity: .55;
		cursor: not-allowed;
	}
	.da-help { color: var(--text); opacity: .8; font-size: 12px; margin-top: 6px; }
	.da-msg { padding: 8px 10px; border-radius: 8px; margin-bottom: 8px; }
	.da-ok { background: #e8f4e5; border: 1px solid #9ab08f; }
	.da-err { background: #fde9e9; border: 1px solid #e3abab; }
	.da-section { border: 1px solid var(--border); border-radius: 8px; margin: 8px 0; background: var(--kpi-bg); }
	.da-section > summary { cursor: pointer; font-weight: 700; padding: 10px 12px; color: var(--text); }
	.da-section[open] > summary { border-bottom: 1px solid var(--table-row-border); }
	.da-section-body { padding: 10px 12px; }
	.da-learning-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }
	.da-btn-secondary { background: var(--kpi-bg); color: var(--text); border: 1px solid var(--border); }
	.da-btn-secondary:hover { background: #e8f0e6; }
	.da-learning-workspace { display: flex; flex-direction: column; gap: 12px; width: 100%; margin-top: 8px; }
	.da-learning-workspace.da-learning-layout-horizontal {
		flex-direction: row;
		align-items: stretch;
		gap: 12px;
		min-height: min(82vh, 880px);
	}
	.da-learning-workspace.da-learning-layout-vertical {
		min-height: min(80vh, 800px);
	}
	.da-learning-pane {
		display: flex;
		flex-direction: column;
		min-width: 0;
		border: 1px solid var(--border);
		border-radius: 8px;
		background: var(--kpi-bg);
		overflow: hidden;
	}
	.da-learning-pane-label {
		padding: 6px 12px;
		font-size: 12px;
		font-weight: 700;
		color: var(--text);
		border-bottom: 1px solid var(--table-row-border);
		background: #f4f7f3;
		flex-shrink: 0;
	}
	.da-learning-pane-editor { min-height: 0; }
	.da-learning-pane-preview { min-height: 0; }
	.da-learning-layout-horizontal .da-learning-pane-editor {
		flex: 0 0 50%;
		max-width: 85%;
		min-height: 0;
	}
	.da-learning-layout-horizontal .da-learning-pane-preview {
		flex: 1 1 0;
		min-width: 200px;
		min-height: 0;
	}
	#da-learning-form .da-learning-editor-wrap {
		width: 100%;
		max-width: 100%;
		box-sizing: border-box;
		flex: 1;
		min-height: 0;
		display: flex;
		flex-direction: column;
		padding: 4px;
		overflow: hidden;
	}
	#da-learning-wysiwyg { display: block; width: 100%; max-width: 100%; min-height: 0; height: 100%; box-sizing: border-box; resize: none !important; overflow: auto; }
	.da-learning-pane-editor { display: flex; flex-direction: column; }
	.da-learning-pane-editor .da-learning-editor-wrap { flex: 1; min-height: 0; }
	.da-learning-editor-wrap .tox-tinymce {
		flex: 1 1 auto !important;
		width: 100% !important;
		max-width: 100% !important;
		height: 100% !important;
		min-height: 0 !important;
		max-height: none !important;
		box-sizing: border-box;
		border-radius: 8px;
		border-color: var(--border) !important;
		display: flex !important;
		flex-direction: column !important;
	}
	.da-learning-editor-wrap .tox .tox-editor-container,
	.da-learning-editor-wrap .tox .tox-sidebar-wrap { flex: 1 1 auto !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; }
	.da-learning-editor-wrap .tox .tox-edit-area { flex: 1 1 auto !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }
	.da-learning-editor-wrap .tox .tox-edit-area__iframe { width: 100% !important; height: 100% !important; min-height: 0 !important; }
	.da-learning-editor-wrap .tox .tox-edit-area > textarea,
	.da-learning-editor-wrap .tox textarea.tox-textarea { flex: 1 1 auto !important; width: 100% !important; height: 100% !important; min-height: 0 !important; resize: none !important; box-sizing: border-box !important; overflow: auto !important; overflow-y: scroll !important; }
	.da-learning-preview-frame {
		width: 100%;
		border: 1px solid var(--border);
		border-radius: 8px;
		background: #fff;
		display: block;
	}
	.da-learning-layout-vertical .da-learning-pane-editor { flex: 1 1 58%; min-height: min(58vh, 640px); }
	.da-learning-layout-vertical .da-learning-pane-preview { flex: 1 1 42%; }
	.da-learning-layout-vertical .da-learning-preview-frame { height: min(38vh, 420px); flex: none; }
	.da-learning-layout-horizontal .da-learning-pane-preview { display: flex; flex-direction: column; }
	.da-learning-layout-horizontal .da-learning-preview-frame {
		flex: 1;
		min-height: 0;
		height: 100%;
		border: 0;
		border-radius: 0;
	}
	@media (max-width: 1100px) {
		.da-learning-workspace.da-learning-layout-horizontal {
			flex-direction: column;
			min-height: 0;
		}
		.da-learning-layout-horizontal .da-learning-pane-editor,
		.da-learning-layout-horizontal .da-learning-pane-preview {
			flex: 1 1 auto !important;
			max-width: none !important;
		}
		.da-learning-layout-horizontal .da-learning-preview-frame {
			height: min(50vh, 480px);
			flex: none;
		}
	}
	.da-learning-autosave-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #334155; cursor: pointer; user-select: none; }
	.da-learning-autosave-toggle input { width: 16px; height: 16px; margin: 0; cursor: pointer; }
	.da-learning-autosave-status { font-size: 12px; color: #64748b; min-height: 1.2em; }
	.da-table-wrap { overflow: auto; max-height: calc(100vh - 280px); border: 1px solid var(--border); border-radius: 8px; }
	@media (max-width: 1199px) { .da-table-wrap { max-height: 70vh; } }
	.da-leftbox > summary { font-size: 16px; }
	.da-picicon { display:inline-flex; align-items:center; gap:6px; margin-top:4px; font-size:12px; color:#334155; text-decoration:none; }
	.da-picicon img { width:28px; height:28px; object-fit:cover; border-radius:4px; border:1px solid #d1d5db; background:#fff; }
	.da-pick-row { display:flex; gap:6px; align-items:center; margin-top:4px; min-width: 0; }
	.da-pick-row select { max-width:100%; min-width:0; flex:1 1 auto; font-size:12px; padding:4px; box-sizing:border-box; }
	.da-splitter { display:none; }
	@media (min-width: 1200px) {
		.da-splitter {
			display:block;
			width:10px;
			cursor: col-resize;
			background: linear-gradient(90deg, #e5ebe4 0%, #cfd9cc 50%, #e5ebe4 100%);
			border-radius: 8px;
			min-height: 400px;
		}
	}
	.da-right-topbar { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px; }
	.da-read-list { display:grid; gap:10px; }
	.da-read-item { border:1px solid #d8dfd6; border-radius:8px; background:#ffffff; padding:10px; }
	.da-read-item.da-read-muted { opacity:0.55; background:#f8faf8; }
	.da-read-q { margin:0 0 8px; font-weight:700; color:#1f2937; }
	.da-read-meta { margin:0 0 8px; font-size:12px; color:#475569; }
	.da-read-drone { display:inline-block; margin:0 0 8px; padding:2px 8px; border-radius:999px; background:#e5f3e8; color:#166534; font-size:12px; font-weight:700; }
	.da-read-options { margin:6px 0 0 20px; padding:0; }
	.da-read-options li { margin:2px 0; }
	.da-read-correct { font-weight:700; color:#0f5132; }
	.da-read-images { display:flex; flex-wrap:wrap; gap:10px; margin-top:8px; }
	.da-read-image { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#334155; text-decoration:none; }
	.da-read-image img { width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #d1d5db; background:#fff; }
	.da-read-solution { margin-top:8px; padding:8px; border:1px solid #e5e7eb; border-radius:6px; background:#f8fafc; white-space:pre-wrap; }
	.da-review-toolbar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between; margin-bottom:10px; }
	.da-review-list { display:grid; gap:12px; }
	.da-review-card { border:1px solid #d8dfd6; border-radius:12px; background:#fff; padding:12px; box-shadow:0 1px 2px rgba(15,23,42,.05); }
	.da-review-head { display:flex; gap:8px; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
	.da-review-title { margin:0; font-size:16px; line-height:1.35; color:#1f2937; }
	.da-review-meta { display:flex; flex-wrap:wrap; gap:6px; align-items:center; margin:6px 0 8px; font-size:12px; color:#475569; }
	.da-review-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; background:#eef6ee; border:1px solid #d8dfd6; color:#1f2937; }
	.da-review-options { margin:8px 0 0 20px; padding:0; }
	.da-review-options li { margin:3px 0; }
	.da-review-correct { color:#0f5132; font-weight:700; }
	.da-review-solution-wrap { margin-top:8px; padding:9px; border:1px solid #e5e7eb; border-radius:8px; background:#f8fafc; }
	.da-review-solution-wrap--empty .da-review-solution { color:#94a3b8; font-style:italic; }
	.da-review-solution-wrap.da-review-solution-changed { background:#fff7ed; border-color:#fdba74; box-shadow:inset 0 0 0 1px #fdba74; }
	.da-review-solution-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b; margin:0 0 4px; }
	.da-review-solution { white-space:pre-wrap; color:#1e3a5f; line-height:1.45; }
	.da-review-controls { display:grid; grid-template-columns: repeat(3, minmax(140px, 1fr)); gap:8px; align-items:end; margin-top:10px; }
	.da-review-controls label { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:700; color:#334155; }
	.da-review-controls textarea { grid-column:1/-1; min-height:72px; resize:vertical; }
	.da-review-text-actions { display:flex; gap:8px; align-items:center; margin-top:6px; }
	.da-review-edit-btn { white-space:nowrap; }
	.da-review-dialog { border:1px solid #94a3b8; border-radius:12px; padding:0; max-width:min(900px, 92vw); width:900px; }
	.da-review-dialog::backdrop { background:rgba(15,23,42,.45); }
	.da-review-dialog-body { padding:14px; background:#fff; }
	.da-review-dialog-grid { display:grid; gap:10px; }
	.da-review-dialog-grid label { display:grid; gap:4px; font-size:12px; font-weight:700; color:#334155; }
	.da-review-dialog-grid textarea, .da-review-dialog-grid input { width:100%; box-sizing:border-box; }
	.da-review-dialog-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
	@media (max-width: 900px) { .da-review-controls { grid-template-columns:1fr; } }
	.da-q-handle { cursor: grab; font-size: 16px; line-height: 1; user-select: none; touch-action: none; }
	.da-q-handle:active { cursor: grabbing; }
	.da-q-actions { display:flex; gap:4px; align-items:center; justify-content:center; min-width:0; max-width:100%; flex-wrap:wrap; }
	.da-q-del { border:0; background:transparent; color:#b42318; cursor:pointer; font-size:16px; line-height:1; padding:2px 4px; }
	.da-q-del:hover { color:#7a1f14; }
	.da-q-nr { display:inline-block; min-width:24px; text-align:center; font-weight:700; }
	.da-input-audienz { min-width: 10rem; width: 100%; max-width: 28rem; font-size: 13px; padding: 6px 8px; box-sizing: border-box; border-radius: 8px; border: 1px solid var(--da-input-border); }
	.da-prob-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; align-items: end; margin-top: 8px; }
	.da-prob-form label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 700; color: var(--text, #334155); }
	.da-prob-note { font-size: 12px; color: #475569; margin: 10px 0 0; line-height: 1.45; }
	.da-prob-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; }
	.da-prob-table th, .da-prob-table td { border: 1px solid var(--table-row-border, #e2e8f0); padding: 6px 8px; text-align: left; vertical-align: middle; }
	.da-prob-table th { background: var(--table-head-bg, #f8fafc); font-size: 12px; white-space: nowrap; }
	.da-prob-table tr.da-prob-muted { opacity: .55; }
	.da-prob-bar-wrap { min-width: 100px; background: #eef2f7; border-radius: 6px; height: 10px; overflow: hidden; }
	.da-prob-bar { height: 100%; background: linear-gradient(90deg, var(--brand, #7a9e78), var(--brand-dark, #3d6b3a)); border-radius: 6px; }
	.da-prob-pct { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
	.da-prob-frage { max-width: 28rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.da-prob-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #eef5ff; color: #1e40af; font-size: 11px; font-weight: 700; }
	.da-prob-badge.da-prob-high { background: #dcfce7; color: #166534; }
	.da-prob-badge.da-prob-low { background: #f1f5f9; color: #64748b; }
	.da-prob-stats { display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 13px; color: #475569; margin-top: 8px; }
	.da-prob-rules { margin: 0 0 12px; border: 1px solid var(--border, #d8dfd6); border-radius: 8px; background: #f8fafc; }
	.da-prob-rules > summary { padding: 10px 12px; cursor: pointer; font-weight: 700; color: var(--text, #1f2937); list-style: none; }
	.da-prob-rules > summary::-webkit-details-marker { display: none; }
	.da-prob-rules .da-prob-rules-body { padding: 0 12px 12px; font-size: 13px; line-height: 1.5; color: #334155; }
	.da-prob-rules .da-prob-rules-body ol { margin: 0; padding-left: 1.25em; }
	.da-prob-rules .da-prob-rules-body li { margin: 6px 0; }
	.da-prob-rules .da-prob-rules-body code { font-size: 12px; }
	.da-prob-charts { margin: 14px 0 0; padding: 14px; border: 1px solid var(--border, #d8dfd6); border-radius: 10px; background: #fff; }
	.da-prob-chart-title { margin: 0 0 8px; font-size: 15px; color: var(--text, #1f2937); }
	.da-prob-chart-intro { margin: 0 0 12px; font-size: 12px; line-height: 1.5; color: #475569; }
	.da-prob-chart-legend { display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 12px; color: #334155; margin-bottom: 8px; }
	.da-prob-swatch { display: inline-block; width: 14px; height: 8px; border-radius: 2px; vertical-align: middle; margin-right: 4px; }
	.da-prob-swatch-pool { background: #94a3b8; }
	.da-prob-swatch-target { background: #ea580c; }
	.da-prob-swatch-exp { background: #166534; }
	.da-prob-chart-svg { width: 100%; max-width: 720px; height: auto; display: block; }
	.da-prob-svg-label { font-size: 11px; fill: #334155; font-family: system-ui, sans-serif; }
	.da-prob-svg-val { font-size: 10px; fill: #64748b; font-family: ui-monospace, monospace; }
	.da-prob-svg-bar-pool { fill: #94a3b8; }
	.da-prob-svg-bar-target { fill: #ea580c; }
	.da-prob-svg-bar-exp { fill: #166534; }
	.da-prob-pie-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 8px; align-items: start; }
	@media (max-width: 900px) { .da-prob-pie-grid { grid-template-columns: 1fr; } }
	.da-prob-pie-card { display: flex; flex-direction: column; align-items: center; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; }
	.da-prob-pie-card-title { margin: 0 0 4px; font-size: 13px; font-weight: 700; color: var(--text, #1f2937); text-align: center; }
	.da-prob-pie-card-cap { margin: 0 0 10px; font-size: 11px; line-height: 1.4; color: #64748b; text-align: center; }
	.da-prob-pie { width: 140px; height: 140px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); margin-bottom: 8px; }
	.da-prob-pie-legend { list-style: none; margin: 0; padding: 0; font-size: 12px; line-height: 1.45; }
	.da-prob-pie-legend li { display: flex; align-items: flex-start; gap: 8px; margin: 6px 0; }
	.da-prob-pie-dot { width: 10px; height: 10px; border-radius: 2px; margin-top: 3px; flex-shrink: 0; }
	.da-prob-pie-sub { color: #64748b; font-weight: 400; }
	.da-prob-chart-table-wrap { overflow-x: auto; margin-top: 12px; }
	.da-prob-chart-table { width: 100%; border-collapse: collapse; font-size: 12px; }
	.da-prob-chart-table th, .da-prob-chart-table td { border: 1px solid #e2e8f0; padding: 5px 8px; text-align: left; }
	.da-prob-chart-table th { background: #f8fafc; }
	.da-prob-chart-empty { font-size: 13px; color: #64748b; margin: 10px 0 0; }
	/* Portal-Submenüs über Manager-Karten (nicht von .da-card / sticky Tabellen verdeckt) */
	#rkrk-portal-nav-slot { position: relative; z-index: 200; margin: 0 10px 14px; }
	.da-docfooter {
		margin: 28px 10px 20px;
		padding-top: 10px;
		border-top: 1px solid #e2e8f0;
		font-size: 11px;
		line-height: 1.6;
		color: #64748b;
		text-align: center;
	}
	.da-docfooter a {
		color: #48673a;
		margin: 0 12px;
		text-decoration: none;
	}
	.da-docfooter a:hover { text-decoration: underline; }
	</style>
	<?php
	$da_portal_is_admin = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool)$onAdmin);
	$da_portal_is_instr = isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($onInstr) && (bool)$onInstr);
	$da_help_links = array();
	if ($da_portal_is_admin || $da_portal_is_instr) {
		$da_help_links[] = array(
			"label" => $onRKRS ? "Aufruf-Hilfe RKRS" : "Aufruf-Hilfe RKRS (extern)",
			"href" => function_exists("rkrk_assess_append_setting_href")
				? rkrk_assess_append_setting_href("rkrk_digitalassess_aufruf_hilfe.php")
				: "rkrk_digitalassess_aufruf_hilfe.php",
		);
	}
	if ($da_portal_is_admin) {
		$da_help_links[] = array(
			"label" => "Aufruf-Hilfe intern (RKRK)",
			"href" => function_exists("rkrk_assess_append_setting_href")
				? rkrk_assess_append_setting_href("rkrk_digitalassess_aufruf_hilfe_rkrk.php")
				: "rkrk_digitalassess_aufruf_hilfe_rkrk.php",
		);
	}
	if (function_exists("rkrk_assess_portal_nav_echo")) {
		ob_start();
		rkrk_assess_portal_nav_echo(array(
			"btn_class" => "da-btn",
			"schulung_mode" => false,
			"is_admin" => $da_portal_is_admin,
			"is_instr" => $da_portal_is_instr,
			"selected_quiz" => (string)$selectedKey,
		));
		$__rkrk_portal_out = ob_get_clean();
		if ($__rkrk_portal_out !== "") {
			echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $__rkrk_portal_out . "</div>\n";
		}
	}
	?>
	<div class="da-wrap">
		<datalist id="da-audienz-suggestions">
<?php foreach ($daAudienzAssessmentChoices as $__audOpt) {
	$__tok = trim((string)$__audOpt);
	if ($__tok !== "") {
		echo "\t\t\t<option value=\"" . da_h($__tok) . "\"></option>\n";
	}
} ?>
		</datalist>
		<div class="da-card">
			<h2 class="da-title"><?php echo da_h($onRKRS ? "Digital Assessment Manager — Schweiz" : "Digital Assessment Manager — Knonaueramt"); ?></h2>
			<?php if ($message !== "") { echo "<div class='da-msg da-ok'>" . da_h($message) . "</div>"; } ?>
			<?php if ($error !== "") { echo "<div class='da-msg da-err'>" . da_h($error) . "</div>"; } ?>
		</div>
		<div class="da-grid" id="da-grid">
			<details class="da-card da-leftbox" open>
				<summary>
					<?php
					$leftTitle = trim((string)$selectedAssessment["Titel"]);
					$leftKey = trim((string)$selectedAssessment["Key"]);
					if ($leftTitle !== "") {
						echo "Assessment Setup: " . da_h($leftTitle);
					} elseif ($leftKey !== "") {
						echo "Assessment Setup: " . da_h($leftKey);
					} else {
						echo "Assessment Setup";
					}
					?>
				</summary>
				<div class="da-section-body">
				<h3 class="da-title">AssessmentListe.csv</h3>
				<form method="get" class="da-inline da-assessment-toolbar">
					<input type="hidden" name="mode" value="manage">
					<?php da_echo_ui_persist_hidden_inputs(); ?>
					<select name="selected_quiz" id="da-toolbar-quiz-select" title="Assessment wechseln — Seite wird sofort neu geladen" onchange="this.form.submit();">
						<option value="">-- Neues Assessment --</option>
						<?php foreach ($assessments as $aRow) { $k=(string)$aRow["Key"]; ?>
							<option value="<?php echo da_h($k); ?>"<?php echo ($k === $selectedKey ? " selected" : ""); ?>><?php echo da_h($k . " - " . $aRow["Titel"]); ?></option>
						<?php } ?>
					</select>
					<button class="da-btn" type="submit" title="Assessment aus Liste laden">Laden</button>
					<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h(rkrk_assess_append_setting_href("?mode=manage&selected_quiz=")); ?>" title="Neues Digital Assessment anlegen">Neu</a>
					<?php if ($selectedKey !== "" && function_exists("rkrk_dga_paper_form_href")) {
						$daAnzPaper = max(0, (int) ($selectedAssessment["AnzQuestions"] ?? 0));
						$daPaperSampleHrefToolbar = rkrk_dga_paper_form_href($selectedKey, array(
							"drone" => $daProbDrone,
							"paper_mode" => "sample",
						));
						?>
					<a class="da-btn" style="text-decoration:none;background:#1e5a3a;color:#fff;border-color:#14532d;" href="<?php echo da_h($daPaperSampleHrefToolbar); ?>" target="_blank" rel="noopener" title="Papierprüfung (leer): gleiche Auswahl und Anzahl wie Live-Quiz (AnzQuestions<?php echo $daAnzPaper > 0 ? ": " . da_h((string) $daAnzPaper) : ""; ?>)">📄 Papier</a>
					<?php } ?>
				</form>
				<details class="da-section" open>
					<summary>Setup bearbeiten</summary>
					<div class="da-section-body">
						<form method="post">
							<input type="hidden" name="mode" value="manage">
							<input type="hidden" name="admin_action" value="save_assessment">
							<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
							<?php da_echo_ui_persist_hidden_inputs(); ?>
							<div class="da-fields da-fields-setup">
								<?php
								$daSetupSpan2 = array("Titel", "Kurzbeschreibung", "Einfuehrung", "Bild", "DriveLocation", "LernDatei", "FragenDatei");
								foreach ($assessmentHeaders as $h) {
									$hCompactKey = ($h === "Key");
									$hCompactNum = ($h === "AnzQuestions" || $h === "QuizTime" || $h === "Erfuellt" || $h === "PunkteModus");
									$labClass = "da-field-label";
									if (in_array($h, $daSetupSpan2, true)) {
										$labClass .= " da-field-span-2";
									}
									if ($h === "Nr") {
										$labClass .= " da-field-span-2";
									} elseif ($hCompactKey) {
										$labClass .= " da-field-compact-slot da-slot-key";
									} elseif ($hCompactNum) {
										$labClass .= " da-field-compact-slot";
									}
									echo "<label class='" . $labClass . "'><span class='da-field-cap'" . da_attr_title(da_assessment_field_tooltip($h)) . ">" . da_h($h) . "</span>";
									if ($h === "Quiz") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Ja");
									} elseif ($h === "Schulung") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Nein");
									} elseif ($h === "AntwortMischen") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Nein");
									} elseif ($h === "FragenBildAnzeigen") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Ja");
									} elseif ($h === "Aktiv") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Ja");
									} elseif ($h === "Locked") {
										echo da_render_ja_nein_radios("a[" . $h . "]", (string)$selectedAssessment[$h], "Nein");
									} elseif ($h === "Audienz") {
										echo "<input class='da-input-audienz' type='text' name='a[" . da_h($h) . "]' value='" . da_h((string)$selectedAssessment[$h]) . "' list='da-audienz-suggestions' placeholder='Alle oder z.B. RKRS, RKRSplus' spellcheck='false' autocomplete='off'>";
									} elseif ($h === "Nr") {
										$nrDisp = trim((string)$selectedAssessment["Nr"]);
										if ($nrDisp === "") {
											echo "<span class='da-id-badge da-id-muted'>-</span>";
										} else {
											echo "<span class='da-id-badge'>" . da_h($nrDisp) . "</span>";
										}
									} elseif ($h === "AnzQuestions" || $h === "QuizTime") {
										echo "<input class='da-input-compact' type='number' min='0' step='1' name='a[" . da_h($h) . "]' value='" . da_h((string)$selectedAssessment[$h]) . "'>";
									} elseif ($h === "Einfuehrung") {
										echo "<textarea name='a[" . da_h($h) . "]' rows='3'>" . da_h((string)$selectedAssessment[$h]) . "</textarea>";
									} elseif ($h === "Kurzbeschreibung") {
										echo "<textarea name='a[" . da_h($h) . "]' rows='3'>" . da_h((string)$selectedAssessment[$h]) . "</textarea>";
									} elseif ($h === "Erfuellt") {
										$erPercent = ((float)$selectedAssessment[$h]) * 100.0;
										echo "<input class='da-input-compact' type='number' step='1' min='0' max='100' name='a[" . da_h($h) . "]' value='" . da_h((string)round($erPercent, 2)) . "'>";
										echo "<span class='da-help'>Prozentwert (0-100)</span>";
									} elseif ($h === "PunkteModus") {
										$pmVal = strtolower(trim((string)$selectedAssessment[$h]));
										if ($pmVal === "") {
											$pmVal = "standard";
										}
										$pmOpts = array(
											"standard" => "Standard — 1 Punkt pro Frage",
											"teilpunkt_gewichtet" => "Teilpunkte gewichtet (max. 1 Pkt./Frage)",
											"teilpunkt_pro_option" => "Teilpunkte pro Option"
										);
										echo "<select class='da-select-compact' name='a[" . da_h($h) . "]'>";
										foreach ($pmOpts as $pmKey => $pmLabel) {
											$sel = ($pmVal === $pmKey) ? " selected" : "";
											echo "<option value='" . da_h($pmKey) . "'" . $sel . ">" . da_h($pmLabel) . "</option>";
										}
										echo "</select>";
									} else {
										echo "<input id='" . ($h === "Bild" ? "da-assessment-bild-input" : "") . "' class='" . ($h === "Key" ? "da-input-compact " : "") . "' type='text' name='a[" . da_h($h) . "]' value='" . da_h((string)$selectedAssessment[$h]) . "'" . ($h === "Bild" ? " list='da-image-options'" : "") . ">";
									}
									if ($h === "Bild") {
										$imgRel = da_clean_relative_file((string)$selectedAssessment[$h], "");
										echo "<div class='da-pick-row'><select class='da-image-select' data-target='da-assessment-bild-input'><option value=''>Bild aus DriveLocation wählen…</option>";
										foreach ($imageSelectOptions as $imgOpt) {
											$sel = ((string)$imgOpt === (string)$imgRel) ? " selected" : "";
											echo "<option value='" . da_h($imgOpt) . "'" . $sel . ">" . da_h($imgOpt) . "</option>";
										}
										echo "</select></div>";
										if ($imgRel !== "") {
											$imgUrl = function_exists('rkrk_dga_rh_image_url')
												? rkrk_dga_rh_image_url($manageImageBaseWeb, $imgRel)
												: ($manageImageBaseWeb . ltrim($imgRel, "/"));
											echo "<a class='da-picicon' target='_blank' rel='noopener' href='" . da_h($imgUrl) . "'><img src='" . da_h($imgUrl) . "' alt='Bild'><span>Vorschau</span></a>";
										}
									}
									echo "</label>";
								}
								?>
								<button class="da-btn" type="submit" style="grid-column:1/-1;justify-self:start;margin-top:6px;">Assessment speichern</button>
							</div>
						</form>
					</div>
				</details>
				<?php if ($selectedKey !== "") { ?>
				<details class="da-section">
					<summary>Assessment loeschen</summary>
					<div class="da-section-body">
						<form method="post" onsubmit="return confirm('Assessment wirklich loeschen?');">
							<input type="hidden" name="mode" value="manage">
							<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
							<input type="hidden" name="admin_action" value="delete_assessment">
							<input type="hidden" name="delete_key" value="<?php echo da_h($selectedKey); ?>">
							<?php da_echo_ui_persist_hidden_inputs(); ?>
							<button class="da-btn da-btn-danger" type="submit">Assessment loeschen</button>
						</form>
					</div>
				</details>
				<?php } ?>
				<div class="da-help">Hier werden Setup und Sichtbarkeit aus `AssessmentListe.csv` gepflegt.</div>
				</div>
			</details>
			<div class="da-splitter" id="da-splitter" title="Breite ziehen"></div>
			<div class="da-card" id="da-rightbox">
				<div class="da-right-topbar">
					<h3 class="da-title" style="margin:0;">Fragen + Schulung</h3>
				</div>
				<?php if ($selectedKey === "") { ?>
					<p>Zuerst links ein Assessment speichern oder laden.</p>
				<?php } else { ?>
					<details class="da-section" open>
						<summary>Fragen bearbeiten</summary>
						<div class="da-section-body">
							<form method="post">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="save_questions">
								<input type="hidden" name="dar_q_json" id="da-q-json" value="">
								<input type="hidden" name="dar_q_row_orig" id="da-q-row-orig" value="">
								<?php da_echo_ui_persist_hidden_inputs(); ?>
								<div class="da-question-file-bar">
									<label class="da-field-label da-question-file-label">
										<span class="da-field-cap"<?php echo da_attr_title("Dateiname der Fragen-CSV relativ zum Assess-Ordner; wird beim Speichern geschrieben (Semikolon-getrennt)."); ?>>Fragen-Datei</span>
										<input class="da-input-question-file" type="text" name="question_file" value="<?php echo da_h($selectedAssessment["FragenDatei"]); ?>" spellcheck="false" autocomplete="off">
									</label>
									<div class="da-question-file-actions">
										<button class="da-btn" type="submit" title="Fragen-CSV speichern">Speichern</button>
										<button class="da-btn" type="button" id="da-add-question-btn" title="Neue Fragezeile">+ Frage</button>
										<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h(rkrk_assess_append_setting_href("rkrk_digitalassess_review.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $selectedKey))))); ?>" title="Fragen Review">Review</a>
										<?php if ($selectedKey !== "" && function_exists("rkrk_dga_paper_form_href")) {
											$daPaperPoolHref = rkrk_dga_paper_form_href($selectedKey, array(
												"drone" => $daProbDrone,
												"paper_mode" => "pool",
											));
											?>
										<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h($daPaperPoolHref); ?>" target="_blank" rel="noopener" title="Alle im Quiz-Pool zulässigen Fragen">📄 Pool</a>
										<?php } ?>
									</div>
								</div>
								<?php if ($daQuestionsFileWarn !== "") { ?>
									<div class="da-msg da-err" style="margin-bottom:10px;"><?php echo da_h($daQuestionsFileWarn); ?></div>
								<?php } ?>
								<div class="da-table-wrap">
									<table class="da-table">
										<thead><tr><th class="da-col-action"<?php echo da_attr_title("Zeile per Drag ⋮⋮ verschieben; Zeile löschen mit Papierkorb."); ?>>Aktion</th><?php foreach ($questionHeaders as $qh) {
											$qhThClass = da_question_header_col_class($qh);
											echo "<th class='" . da_h($qhThClass) . "'" . da_attr_title(da_question_header_tooltip($qh)) . ">" . da_h($qh) . "</th>";
										} ?></tr></thead>
										<tbody id="da-question-body">
										<?php
										if (count($questions) === 0) {
											$seed = array_fill_keys($questionHeaders, "");
											$seed["Aktiv"] = "Ja";
											$seed["Status"] = "neu";
											$seed["Rueckmeldung"] = "offen";
											$seed["RueckmeldungText"] = "";
											$seed["Locked"] = "Nein";
											$seed["Audienz"] = "Alle";
											$seed["Prioritaet"] = "0";
											$questions[] = $seed;
										}
										foreach ($questions as $i => $qRow) {
											echo "<tr class='da-q-row' data-orig-idx='" . (int) $i . "'>";
											echo "<td class='da-col-action'><div class='da-q-actions'><span class='da-q-handle' draggable='true' title='Reihenfolge nur hier per Drag & Drop'>⋮⋮</span><button type='button' class='da-q-del' title='Frage löschen' aria-label='Frage löschen'>🗑</button></div></td>";
											foreach ($questionHeaders as $qh) {
												$val = isset($qRow[$qh]) ? $qRow[$qh] : "";
												$qhTdClass = da_question_header_col_class($qh);
												if ($qh === "Nr") {
													echo "<td class='" . da_h($qhTdClass) . "'><span class='da-q-nr'>" . da_h((string)($i + 1)) . "</span></td>";
												} elseif ($qh === "Frage" || $qh === "Loesungstext" || $qh === "Loesungsauswahl") {
													echo "<td class='" . da_h($qhTdClass) . "'><textarea name='q[" . da_h($qh) . "][" . (int)$i . "]' rows='4'>" . da_h((string)$val) . "</textarea></td>";
												} elseif ($qh === "Frage-Bild" || $qh === "Loesungs-Bild") {
													$imgRelQ = da_with_drive_location((string)$val, $driveLocCurrent);
													$fieldId = "q_" . preg_replace('/[^a-z0-9_]+/i', '_', $qh) . "_" . (int)$i;
													echo "<td class='" . da_h($qhTdClass) . "'><input id='" . da_h($fieldId) . "' type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h((string)$val) . "' list='da-image-options'>";
													echo "<div class='da-pick-row'><select class='da-image-select' data-target='" . da_h($fieldId) . "'><option value=''>Bild aus DriveLocation wählen…</option>";
													foreach ($imageSelectOptions as $imgOpt) {
														$sel = ((string)$imgOpt === (string)$imgRelQ) ? " selected" : "";
														echo "<option value='" . da_h($imgOpt) . "'" . $sel . ">" . da_h($imgOpt) . "</option>";
													}
													echo "</select></div>";
													if ($imgRelQ !== "") {
														$imgUrlQ = function_exists('rkrk_dga_rh_image_url')
															? rkrk_dga_rh_image_url($manageImageBaseWeb, $imgRelQ)
															: ($manageImageBaseWeb . ltrim($imgRelQ, "/"));
														echo "<a class='da-picicon' target='_blank' rel='noopener' href='" . da_h($imgUrlQ) . "'><img src='" . da_h($imgUrlQ) . "' alt='Bild'><span>Bild</span></a>";
													}
													echo "</td>";
												} elseif ($qh === "Bereich") {
													$bPick = array_values(array_filter($daQBereichChoices, function ($v) {
														return trim((string)$v) !== "";
													}));
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $bPick, true) . "</td>";
												} elseif ($qh === "Bildformat") {
													$bfPick = array_values(array_filter($daQBildformatChoices, function ($v) {
														return trim((string)$v) !== "";
													}));
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $bfPick, true) . "</td>";
												} elseif ($qh === "Zeit") {
													$ztPick = array_values(array_filter($daQZeitChoices, function ($v) {
														return trim((string)$v) !== "";
													}));
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $ztPick, true) . "</td>";
												} elseif ($qh === "Prioritaet") {
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $daQPrioritaetChoices, false) . "</td>";
												} elseif ($qh === "Status") {
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $daQStatusChoices, true) . "</td>";
												} elseif ($qh === "Audienz") {
													echo "<td class='" . da_h($qhTdClass) . "'><input class='da-input-audienz' type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h((string)$val) . "' list='da-audienz-suggestions' placeholder='z.B. Alle, RKRS, PUB' spellcheck='false' autocomplete='off'></td>";
												} elseif ($qh === "Aktiv" || $qh === "Locked") {
													$defJn = ($qh === "Locked") ? "Nein" : "Ja";
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_ja_nein_radios("q[" . $qh . "][" . (int)$i . "]", (string)$val, $defJn) . "</td>";
												} elseif ($qh === "Drone") {
													$dPick = array_values(array_filter($daQDroneChoices, function ($v) {
														return trim((string)$v) !== "";
													}));
													echo "<td class='" . da_h($qhTdClass) . "'>" . da_render_enum_select("q[" . $qh . "][" . (int)$i . "]", (string)$val, $dPick, true) . "</td>";
												} elseif ($qh === "Loesung" || $qh === "Antwort") {
													echo "<td class='" . da_h($qhTdClass) . "'><input type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h((string)$val) . "'></td>";
												} else {
													echo "<td class='" . da_h($qhTdClass) . "'><input type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h((string)$val) . "'></td>";
												}
											}
											echo "</tr>";
										}
										?>
										</tbody>
									</table>
								</div>
							</form>
							<div class="da-help">Bilder in `Frage-Bild` / `Loesungs-Bild` relativ zu `data/assess/images/` eintragen (z.B. `saison2026/meteo.png`).</div>
						</div>
					</details>
					<details class="da-section" id="fragen-probability">
						<summary>Quiz-Auswahl — Anzeige-Wahrscheinlichkeit</summary>
						<div class="da-section-body">
							<details class="da-prob-rules">
								<summary>Wie wird die Auswahl im Quiz gemacht?</summary>
								<div class="da-prob-rules-body">
									<?php
									if (function_exists("rkrk_dga_echo_quiz_selection_rules_help")) {
										rkrk_dga_echo_quiz_selection_rules_help();
									}
									if ($daProbPickEffective > 0 && count($daProbEligible) > 0 && function_exists("rkrk_dga_echo_quiz_topic_distribution_charts")) {
										$daProbChartOpts = array();
										if ($daProbBlended && count($daProbDroneTypesInQuiz) > 0) {
											$daProbChartOpts = array(
												"blended" => true,
												"questions" => $questions,
												"setting" => $daProbSetting,
												"drone_types" => $daProbDroneTypesInQuiz,
											);
										}
										rkrk_dga_echo_quiz_topic_distribution_charts($daProbEligible, $daProbPickEffective, $daProbMap, $daProbIterations, $daProbChartOpts);
									}
									?>
								</div>
							</details>
							<form method="get" class="da-prob-form">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="da_prob_calc" value="1">
								<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
								<label title="Anzahl Fragen pro Quiz-Durchgang (aus Setup oder manuell)">
									<span class="da-field-cap">Fragen pro Durchgang</span>
									<input type="number" name="da_prob_pickn" min="1" max="999" value="<?php echo da_h((string) $daProbPickN); ?>">
								</label>
								<label>
									<span class="da-field-cap">Drohnentyp (Filter)</span>
									<select name="da_prob_drone">
										<?php foreach ($daProbDroneOptions as $daProbDOpt) {
											$daProbDOptLabel = (string) $daProbDOpt;
											if ($daProbDOpt === "Alle" && count($daProbDroneTypesInQuiz) > 0) {
												$daProbDOptLabel = "Alle Typen (Mittelwert)";
											}
											?>
											<option value="<?php echo da_h($daProbDOpt); ?>"<?php echo ($daProbDrone === (string) $daProbDOpt ? " selected" : ""); ?>><?php echo da_h($daProbDOptLabel); ?></option>
										<?php } ?>
									</select>
								</label>
								<label style="align-self:end;">
									<button class="da-btn" type="submit">Berechnen</button>
								</label>
							</form>
							<p class="da-prob-note">
								Monte-Carlo mit <?php echo da_h((string) $daProbIterations); ?> Läufen (Kanal <?php echo da_h($daProbSetting); ?>).
								<?php if ($daProbBlended && count($daProbDroneTypesInQuiz) > 0) { ?>
									Mittelwert über <?php echo da_h((string) count($daProbDroneTypesInQuiz)); ?> Drohnentypen — im Live-Quiz wählt der Teilnehmer pro Fragebogen genau einen Typ (<?php echo da_h(implode(", ", $daProbDroneTypesInQuiz)); ?>).
								<?php } else { ?>
									Drohne «<?php echo da_h($daProbDrone); ?>» (ein Typ pro Fragebogen).
								<?php } ?>
								Details zur Formel: Abschnitt oben aufklappen.
							</p>
							<?php if ($selectedKey !== "" && function_exists("rkrk_dga_paper_form_href") && $daProbPickEffective > 0) {
								$daAnzPaperProb = max(0, (int) ($selectedAssessment["AnzQuestions"] ?? 0));
								$daPaperProbHref = rkrk_dga_paper_form_href($selectedKey, array(
									"drone" => $daProbDrone,
									"paper_mode" => "sample",
								));
								$daPaperProbPoolHref = rkrk_dga_paper_form_href($selectedKey, array(
									"drone" => $daProbDrone,
									"paper_mode" => "pool",
								));
								?>
							<p class="da-prob-note" style="margin-top:8px;">
								<a class="da-btn" style="text-decoration:none;background:#1e5a3a;color:#fff;border-color:#14532d;" href="<?php echo da_h($daPaperProbHref); ?>" target="_blank" rel="noopener" title="Papierprüfung drucken">📄 Papier</a>
								<span style="margin:0 6px;">— <?php echo da_h((string) ($daAnzPaperProb > 0 ? $daAnzPaperProb : $daProbPickEffective)); ?> Fragen laut Setup (AnzQuestions), gleiche Regeln wie Live-Quiz (neu laden = neue Zufallsauswahl).</span>
								<a class="da-btn" style="text-decoration:none;margin-left:6px;" href="<?php echo da_h($daPaperProbPoolHref); ?>" target="_blank" rel="noopener" title="Alle Fragen im Pool">📄 Pool (<?php echo da_h((string) count($daProbEligible)); ?>)</a>
							</p>
							<?php } ?>
							<?php if (!$daProbCalc) { ?>
								<div class="da-help">Die Anzeige-Wahrscheinlichkeiten werden aus Performance-Gründen nicht automatisch berechnet (Monte-Carlo mit <?php echo da_h((string) $daProbIterations); ?> Läufen). Stelle oben «Fragen pro Durchgang» und «Drohnentyp» ein und klicke auf <strong>Berechnen</strong>.</div>
							<?php } elseif (count($questions) === 0) { ?>
								<div class="da-help">Keine Fragen vorhanden.</div>
							<?php } elseif ($daProbPickEffective < 1) { ?>
								<div class="da-help">Keine Frage im Auswahl-Pool (Status/Audienz/Drohne prüfen).</div>
							<?php } else {
								$daProbRows = array();
								foreach ($questions as $probIdx => $probRow) {
									if (!is_array($probRow) || trim((string) ($probRow["Frage"] ?? "")) === "") {
										continue;
									}
									$probKey = (string) $probIdx;
									$inPool = isset($daProbEligibleKeys[$probKey]);
									$p = $inPool && isset($daProbMap[$probKey]) ? (float) $daProbMap[$probKey] : 0.0;
									$daProbRows[] = array(
										"idx" => $probIdx,
										"row" => $probRow,
										"inPool" => $inPool,
										"p" => $p,
									);
								}
								usort($daProbRows, static function ($a, $b) {
									if ($a["inPool"] !== $b["inPool"]) {
										return $b["inPool"] <=> $a["inPool"];
									}
									if ($a["p"] !== $b["p"]) {
										return $b["p"] <=> $a["p"];
									}
									return ((int) $a["idx"]) <=> ((int) $b["idx"]);
								});
								$daProbPoolCount = $daProbPoolCountDisplay > 0 ? $daProbPoolCountDisplay : count($daProbEligible);
								$daProbMean = $daProbPoolCount > 0 ? min(1.0, $daProbPickEffective / $daProbPoolCount) : 0.0;
							?>
							<div class="da-prob-stats">
								<span>Pool<?php echo $daProbBlended ? " (Ø je Typ)" : ""; ?>: <strong><?php echo da_h((string) $daProbPoolCount); ?></strong> Fragen</span>
								<span>Durchgang: <strong><?php echo da_h((string) $daProbPickEffective); ?></strong> gezogen</span>
								<?php if ($daProbBlended && count($daProbDroneTypesInQuiz) > 0) { ?>
								<span>Drohnen: <strong><?php echo da_h((string) count($daProbDroneTypesInQuiz)); ?></strong> Typen</span>
								<?php } ?>
								<span>Gleichverteilung (nur Prio 0): ca. <strong><?php echo da_h(number_format($daProbMean * 100, 1, ",", "")); ?>&nbsp;%</strong></span>
								<span>Kanal: <strong><?php echo da_h($daProbSetting); ?></strong></span>
							</div>
							<div class="da-table-wrap" style="margin-top:10px;">
								<table class="da-prob-table">
									<thead>
										<tr>
											<th>Nr</th>
											<th>Frage</th>
											<th>Bereich</th>
											<th>Drohne</th>
											<th title="<?php echo da_h($daProbPriorityHelp); ?>">Prio</th>
											<th>Status</th>
											<th>P(Anzeige)</th>
											<th>Verteilung</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($daProbRows as $daProbItem) {
										$probRow = $daProbItem["row"];
										$probIdx = (int) $daProbItem["idx"];
										$inPool = $daProbItem["inPool"];
										$p = $daProbItem["p"];
										$pct = $inPool ? round($p * 100, 1) : 0.0;
										$qNr = trim((string) ($probRow["Nr"] ?? "")) !== "" ? (string) $probRow["Nr"] : (string) ($probIdx + 1);
										$frageShort = trim((string) ($probRow["Frage"] ?? ""));
										if (function_exists("mb_strlen") && mb_strlen($frageShort) > 72) {
											$frageShort = mb_substr($frageShort, 0, 69) . "…";
										} elseif (strlen($frageShort) > 72) {
											$frageShort = substr($frageShort, 0, 69) . "…";
										}
										$barW = $inPool ? min(100, max(0, $pct)) : 0;
										$badgeClass = "da-prob-badge";
										if (!$inPool) {
											$badgeClass .= " da-prob-low";
										} elseif ($pct >= 50) {
											$badgeClass .= " da-prob-high";
										}
										$st = trim((string) ($probRow["Status"] ?? ""));
										if ($st === "") {
											$st = "aktiv";
										}
										$prioInt = (int) (trim((string) ($probRow["Prioritaet"] ?? "")) !== "" && is_numeric($probRow["Prioritaet"]) ? $probRow["Prioritaet"] : 0);
										$prioW = function_exists("rkrk_dga_quiz_prio_weight") ? (int) rkrk_dga_quiz_prio_weight($prioInt) : 1;
									?>
										<tr class="<?php echo (!$inPool ? "da-prob-muted" : ""); ?>">
											<td><?php echo da_h($qNr); ?></td>
											<td class="da-prob-frage" title="<?php echo da_h((string) ($probRow["Frage"] ?? "")); ?>"><?php echo da_h($frageShort); ?></td>
											<td><?php echo da_h(trim((string) ($probRow["Bereich"] ?? "")) !== "" ? (string) $probRow["Bereich"] : "—"); ?></td>
											<td><?php echo da_h(trim((string) ($probRow["Drone"] ?? "")) !== "" ? (string) $probRow["Drone"] : "—"); ?></td>
											<td><?php echo da_h((string) $prioInt); ?></td>
											<td><?php echo da_h($st); ?></td>
											<td>
												<?php if ($inPool) { ?>
													<span class="da-prob-pct"><?php echo da_h(number_format($pct, 1, ",", "")); ?> %</span>
													<span class="<?php echo da_h($badgeClass); ?>">w=<?php echo da_h((string) $prioW); ?></span>
												<?php } else { ?>
													<span class="da-prob-pct">—</span>
												<?php } ?>
											</td>
											<td>
												<?php if ($inPool) { ?>
													<div class="da-prob-bar-wrap" title="<?php echo da_h(number_format($pct, 1, ",", "")); ?> %"><div class="da-prob-bar" style="width:<?php echo da_h((string) $barW); ?>%;"></div></div>
												<?php } else { ?>
													<span class="da-help">nicht im Pool</span>
												<?php } ?>
											</td>
										</tr>
									<?php } ?>
									</tbody>
								</table>
							</div>
							<?php } ?>
						</div>
					</details>
					<details class="da-section" id="fragen-review" open>
						<summary>Fragen Review</summary>
						<div class="da-section-body">
							<?php
							$reviewableCount = 0;
							foreach ($questions as $rq) {
								if (is_array($rq) && trim((string)($rq["Frage"] ?? "")) !== "") {
									$reviewableCount++;
								}
							}
							?>
							<?php if ($reviewableCount === 0) { ?>
								<div class="da-help">Keine Fragen vorhanden.</div>
							<?php } else { ?>
								<form method="post" id="da-review-form">
									<input type="hidden" name="mode" value="manage">
									<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
									<input type="hidden" name="admin_action" value="save_question_review">
									<input type="hidden" name="dar_q_json" id="da-review-q-json" value="">
									<input type="hidden" name="dar_q_row_orig" id="da-review-q-row-orig" value="">
									<?php da_echo_ui_persist_hidden_inputs(); ?>
									<div class="da-review-toolbar">
										<div class="da-help">Alle Fragen untereinander. Priorität, Status und Rückmeldung werden direkt in der Fragen-CSV gespeichert.</div>
										<button class="da-btn" type="submit">Review speichern</button>
									</div>
									<div class="da-review-list">
									<?php
									$reviewCardNr = 0;
									foreach ($questions as $origI => $qRow) {
										if (!is_array($qRow)) {
											continue;
										}
										if (trim((string)($qRow["Frage"] ?? "")) === "") {
											continue;
										}
										$reviewCardNr++;
										$qNr = trim((string)($qRow["Nr"] ?? "")) !== "" ? (string)$qRow["Nr"] : (string)$reviewCardNr;
										$qText = trim((string)($qRow["Frage"] ?? ""));
										$rawOptions = trim((string)($qRow["Loesungsauswahl"] ?? ""));
										$optionList = $rawOptions !== "" ? array_map("trim", explode("|", $rawOptions)) : array();
										$optionList = array_values(array_filter($optionList, function($v){ return $v !== ""; }));
										$correctRaw = trim((string)($qRow["Loesung"] ?? ""));
										$correctTokens = array_values(array_filter(array_map("trim", explode("|", $correctRaw)), function($v){ return $v !== ""; }));
										$correctLookup = array();
										foreach ($correctTokens as $token) {
											$correctLookup[strtolower($token)] = true;
										}
										$loesungstext = trim((string)($qRow["Loesungstext"] ?? ""));
										$reviewVal = trim((string)($qRow["Rueckmeldung"] ?? ""));
										if ($reviewVal === "") $reviewVal = "offen";
										$reviewText = trim((string)($qRow["RueckmeldungText"] ?? ""));
									?>
										<div class="da-review-card" data-review-card="<?php echo (int)$reviewCardNr; ?>" data-orig-idx="<?php echo (int)$origI; ?>">
											<?php foreach ($questionHeaders as $qh) {
												$hiddenVal = isset($qRow[$qh]) ? (string)$qRow[$qh] : "";
												if ($qh === "Nr") {
													$hiddenVal = (string)$reviewCardNr;
												} elseif ($qh === "Status" && trim($hiddenVal) === "") {
													$hiddenVal = "aktiv";
												} elseif ($qh === "Rueckmeldung" && trim($hiddenVal) === "") {
													$hiddenVal = "offen";
												}
												echo "<input type='hidden' data-review-field='" . da_h($qh) . "' value='" . da_h($hiddenVal) . "'>";
											} ?>
											<div class="da-review-head">
												<h4 class="da-review-title"><span class="da-review-nr"><?php echo da_h($qNr); ?></span>. <span class="da-review-question"><?php echo da_h($qText); ?></span></h4>
												<button class="da-btn da-review-edit-btn" type="button">bearbeiten</button>
											</div>
											<div class="da-review-meta">
												<?php if (trim((string)($qRow["Bereich"] ?? "")) !== "") { ?><span class="da-review-pill">Bereich: <span class="da-review-area"><?php echo da_h((string)$qRow["Bereich"]); ?></span></span><?php } ?>
												<?php if (trim((string)($qRow["Herkunft"] ?? "")) !== "") { ?><span class="da-review-pill">Herkunft: <?php echo da_h((string)$qRow["Herkunft"]); ?></span><?php } ?>
												<span class="da-review-pill">Lösung: <span class="da-review-sol"><?php echo da_h($correctRaw); ?></span></span>
											</div>
											<?php if (count($optionList) > 0) { ?>
												<ol class="da-review-options" type="A">
													<?php foreach ($optionList as $optIdx => $optText) {
														$letterToken = chr(65 + (int)$optIdx);
														$numToken = (string)($optIdx + 1);
														$isCorrect = isset($correctLookup[strtolower($letterToken)]) || isset($correctLookup[strtolower($numToken)]) || isset($correctLookup[strtolower($optText)]);
													?>
														<li<?php echo $isCorrect ? " class='da-review-correct'" : ""; ?>><?php echo da_h($optText); ?></li>
													<?php } ?>
												</ol>
											<?php } ?>
											<div class="da-review-solution-wrap<?php echo $loesungstext === "" ? " da-review-solution-wrap--empty" : ""; ?>" data-baseline-loesungstext="<?php echo da_h($loesungstext); ?>">
												<div class="da-review-solution-label">Lösungstext</div>
												<div class="da-review-solution"><?php echo $loesungstext !== "" ? da_h($loesungstext) : "—"; ?></div>
											</div>
											<div class="da-review-controls">
												<label>Priorität
													<?php echo da_render_enum_select("", (string)($qRow["Prioritaet"] ?? ""), $daQPrioritaetChoices, false, "da-sel-prio"); ?>
												</label>
												<label>Status
													<?php echo da_render_enum_select("", (string)($qRow["Status"] ?? "aktiv"), $daQStatusChoices, true, "da-sel-status"); ?>
												</label>
												<label>Rückmeldung
													<?php echo da_render_enum_select("", $reviewVal, $daQReviewChoices, false, "da-sel-rueck"); ?>
												</label>
												<label>Rückmeldung Text
													<textarea class="da-review-feedback-text" rows="3"><?php echo da_h($reviewText); ?></textarea>
												</label>
											</div>
											<div class="da-review-text-actions">
												<button class="da-btn" type="button" data-clear-feedback>Textfeld löschen</button>
											</div>
										</div>
									<?php } ?>
									</div>
									<div class="da-review-toolbar" style="margin-top:12px;">
										<div class="da-help">Textänderungen an Frage, Antworten und Lösungstext über <strong>bearbeiten</strong> öffnen und danach Review speichern.</div>
										<button class="da-btn" type="submit">Review speichern</button>
									</div>
								</form>
								<dialog class="da-review-dialog" id="da-review-dialog">
									<div class="da-review-dialog-body">
										<h3 class="da-title" style="margin-top:0;">Frage bearbeiten</h3>
										<div class="da-review-dialog-grid">
											<label>Frage
												<textarea id="da-review-edit-frage" rows="4"></textarea>
											</label>
											<label>Antworten (mit | trennen)
												<textarea id="da-review-edit-options" rows="4"></textarea>
											</label>
											<label>Lösung (z.B. A oder A|C)
												<input id="da-review-edit-solution" type="text">
											</label>
											<label>Lösungstext
												<textarea id="da-review-edit-solution-text" rows="5"></textarea>
											</label>
										</div>
										<div class="da-review-dialog-actions">
											<button class="da-btn" type="button" id="da-review-edit-cancel">Abbrechen</button>
											<button class="da-btn" type="button" id="da-review-edit-apply">Übernehmen</button>
										</div>
									</div>
								</dialog>
							<?php } ?>
						</div>
					</details>
					<details class="da-section">
						<summary>Fragen Leseansicht</summary>
						<div class="da-section-body">
							<?php
							$readRows = array_values(array_filter($questions, function ($qRow) {
								if (!is_array($qRow)) return false;
								return trim((string)($qRow["Frage"] ?? "")) !== "";
							}));
							?>
							<?php if (count($readRows) === 0) { ?>
								<div class="da-help">Keine Fragen vorhanden.</div>
							<?php } else { ?>
								<div class="da-read-list">
								<?php foreach ($readRows as $idx => $qRow) {
									$qNr = trim((string)($qRow["Nr"] ?? "")) !== "" ? (string)$qRow["Nr"] : (string)($idx + 1);
									$qText = trim((string)($qRow["Frage"] ?? ""));
									$rawOptions = trim((string)($qRow["Loesungsauswahl"] ?? ""));
									$optionList = $rawOptions !== "" ? array_map("trim", explode("|", $rawOptions)) : array();
									$optionList = array_values(array_filter($optionList, function($v){ return $v !== ""; }));
									$correctRaw = trim((string)($qRow["Loesung"] ?? ""));
									$correctTokens = array_values(array_filter(array_map("trim", explode("|", $correctRaw)), function($v){ return $v !== ""; }));
									$correctLookup = array();
									foreach ($correctTokens as $token) {
										$correctLookup[strtolower($token)] = true;
									}
									$frageBild = da_with_drive_location((string)($qRow["Frage-Bild"] ?? ""), $driveLocCurrent);
									$loesungsBild = da_with_drive_location((string)($qRow["Loesungs-Bild"] ?? ""), $driveLocCurrent);
									$loesungstext = trim((string)($qRow["Loesungstext"] ?? ""));
									$droneType = trim((string)($qRow["Drone"] ?? ""));
								?>
									<div class="da-read-item<?php echo (function_exists('rkrk_assess_question_is_quiz_live') && !rkrk_assess_question_is_quiz_live($qRow)) ? ' da-read-muted' : ''; ?>">
										<?php if ($droneType !== "") { ?>
											<div class="da-read-drone"><?php echo da_h($droneType); ?></div>
										<?php } ?>
										<p class="da-read-q"><?php echo da_h($qNr . ". " . $qText); ?></p>
										<?php if (trim((string)($qRow["Bereich"] ?? "")) !== "") { ?>
											<p class="da-read-meta">Bereich: <?php echo da_h((string)$qRow["Bereich"]); ?></p>
										<?php } ?>

										<?php if (count($optionList) > 0) { ?>
											<ul class="da-read-options">
												<?php foreach ($optionList as $optIdx => $optText) {
													$numToken = (string)($optIdx + 1);
													$isCorrect = isset($correctLookup[strtolower($optText)]) || isset($correctLookup[strtolower($numToken)]);
												?>
													<li<?php echo $isCorrect ? " class='da-read-correct'" : ""; ?>>
														<?php echo da_h($numToken . ") " . $optText); ?>
													</li>
												<?php } ?>
											</ul>
										<?php } elseif ($correctRaw !== "") { ?>
											<p class="da-read-meta"><span class="da-read-correct">Richtige Antwort: <?php echo da_h($correctRaw); ?></span></p>
										<?php } ?>

										<div class="da-read-images">
											<?php if ($frageBild !== "") {
												$frageImgUrl = function_exists('rkrk_dga_rh_image_url')
													? rkrk_dga_rh_image_url($manageImageBaseWeb, $frageBild)
													: ($manageImageBaseWeb . ltrim($frageBild, "/"));
											?>
												<a class="da-read-image" target="_blank" rel="noopener" href="<?php echo da_h($frageImgUrl); ?>">
													<img src="<?php echo da_h($frageImgUrl); ?>" alt="Frage-Bild">
													<span>Frage-Bild</span>
												</a>
											<?php } ?>
											<?php if ($loesungsBild !== "") {
												$loesungImgUrl = function_exists('rkrk_dga_rh_image_url')
													? rkrk_dga_rh_image_url($manageImageBaseWeb, $loesungsBild)
													: ($manageImageBaseWeb . ltrim($loesungsBild, "/"));
											?>
												<a class="da-read-image" target="_blank" rel="noopener" href="<?php echo da_h($loesungImgUrl); ?>">
													<img src="<?php echo da_h($loesungImgUrl); ?>" alt="Loesungs-Bild">
													<span>Loesungs-Bild</span>
												</a>
											<?php } ?>
										</div>

										<?php if ($loesungstext !== "") { ?>
											<div class="da-read-solution"><?php echo da_h($loesungstext); ?></div>
										<?php } ?>
									</div>
								<?php } ?>
								</div>
							<?php } ?>
						</div>
					</details>
					<details class="da-section">
						<summary>Bilder hochladen</summary>
						<div class="da-section-body">
							<form method="post" enctype="multipart/form-data">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="upload_images">
								<?php da_echo_ui_persist_hidden_inputs(); ?>
								<div class="da-inline">
									<span>Zielordner: <code><?php echo da_h(rtrim((string)$QuizLoc, "/\\") . "/images/" . ($driveLocCurrent !== "" ? $driveLocCurrent . "/" : "")); ?></code></span>
								</div>
								<div class="da-inline">
									<input type="file" name="upload_images[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp" <?php echo ($driveLocCurrent === "" ? "disabled" : ""); ?>>
									<button class="da-btn" type="submit" <?php echo ($driveLocCurrent === "" ? "disabled" : ""); ?>>Bilder hochladen</button>
								</div>
								<div class="da-help">Nach Upload nur den Dateinamen bzw. relativen Pfad in `Frage-Bild`/`Loesungs-Bild` eintragen.</div>
								<div class="da-help">Auswahlvorschläge kommen direkt aus diesem DriveLocation-Ordner.</div>
								<?php if ($driveLocCurrent === "") { ?><div class="da-help" style="color:#b91c1c;">DriveLocation ist leer: Bitte im Setup setzen, dann Upload aktiv.</div><?php } ?>
							</form>
						</div>
					</details>
					<details class="da-section">
						<summary>Alle Bilder hochladen + automatisch zuordnen</summary>
						<div class="da-section-body">
							<form method="post" enctype="multipart/form-data">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="upload_all_images">
								<?php da_echo_ui_persist_hidden_inputs(); ?>
								<div class="da-inline">
									<input type="file" name="upload_images[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp" <?php echo ($driveLocCurrent === "" ? "disabled" : ""); ?>>
									<button class="da-btn" type="submit" <?php echo ($driveLocCurrent === "" ? "disabled" : ""); ?>>Alle Bilder hochladen</button>
								</div>
								<div class="da-help">Ordnet Bilder automatisch zu, wenn Dateinamen mit vorhandenen Einträgen in `Bild`, `Frage-Bild` oder `Loesungs-Bild` übereinstimmen.</div>
								<?php if ($driveLocCurrent === "") { ?><div class="da-help" style="color:#b91c1c;">DriveLocation ist leer: Bitte im Setup setzen, dann Upload aktiv.</div><?php } ?>
							</form>
						</div>
					</details>
					<details class="da-section">
						<summary>Schulungstext bearbeiten</summary>
						<div class="da-section-body">
							<?php
							$dlePageHref = function_exists("rkrk_assess_append_setting_href")
								? rkrk_assess_append_setting_href("rkrk_digitalassess_learning_edit.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $selectedKey))))
								: "rkrk_digitalassess_learning_edit.php?selected_quiz=" . rawurlencode((string) $selectedKey);
							?>
							<p class="da-help" style="margin:0 0 10px;"><a class="da-btn" style="text-decoration:none;" href="<?php echo da_h($dlePageHref); ?>">Editor mit Live-Vorschau (empfohlen)</a> — links bearbeiten, rechts Vorschau.</p>
							<form method="post" id="da-learning-form">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="save_learning_html">
								<?php da_echo_ui_persist_hidden_inputs(); ?>
								<input type="hidden" name="learning_html_doc_prefix_b64" id="da-learning-prefix-b64" value="<?php echo da_h((string)$daLearnEditor["prefix_b64"]); ?>">
								<input type="hidden" name="learning_html_doc_suffix_b64" id="da-learning-suffix-b64" value="<?php echo da_h((string)$daLearnEditor["suffix_b64"]); ?>">
								<input type="hidden" name="learning_html_fragment" value="<?php echo !empty($daLearnEditor["fragment"]) ? "1" : "0"; ?>">
								<input type="hidden" name="learning_html_baseline_hash" id="da-learning-baseline-hash" value="<?php echo da_h($daLearnBodyHash); ?>">
								<div class="da-learning-toolbar da-inline">
									<label><span class="da-field-cap"<?php echo da_attr_title("Dateiname der Schulungs-HTML relativ zum Assess-Ordner (.html); wird beim Speichern dort abgelegt."); ?>>Schulung HTML-Datei</span> <input type="text" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>"></label>
									<button class="da-btn" type="submit">Schulung speichern</button>
									<label class="da-learning-autosave-toggle" title="Speichert bei Aktivität automatisch jede Minute; Archiv mit _autosave, manuell mit 10-Minuten-Raster">
										<input type="checkbox" id="da-learning-autosave-switch" checked>
										<span>Autospeichern</span>
									</label>
									<span class="da-learning-autosave-status" id="da-learning-autosave-status" aria-live="polite"></span>
									<?php if ($daLearnPreviewUrl !== "") { ?>
									<button type="button" class="da-btn da-btn-secondary" id="da-learning-layout-toggle" aria-pressed="false" title="Editor und Vorschau nebeneinander anzeigen">↔ Nebeneinander</button>
									<?php } ?>
								</div>
								<div class="da-help">WYSIWYG mit Theme wie im Quiz. <strong>Quellcode</strong> für HTML. Autosave: Archiv <code>_YYYYMMDDhhmm_autosave</code>; manuelles Speichern: 10-Minuten-Archiv wie beim Review.</div>
								<?php if ($daLearnPreviewUrl !== "") { ?>
								<div class="da-learning-workspace da-learning-layout-vertical" id="da-learning-workspace">
									<div class="da-learning-pane da-learning-pane-editor">
										<div class="da-learning-pane-label">Editor (WYSIWYG / Quellcode)</div>
										<div class="da-learning-editor-wrap">
											<textarea id="da-learning-wysiwyg" name="learning_html" rows="18"><?php echo da_h((string)$daLearnEditor["body"]); ?></textarea>
										</div>
									</div>
									<div class="da-learning-pane da-learning-pane-preview">
										<div class="da-learning-pane-label">Vorschau wie auf dem Server</div>
										<iframe class="da-learning-preview-frame" id="da-learning-preview-frame" src="<?php echo da_h($daLearnPreviewUrl); ?>" title="Schulung Vorschau" loading="lazy"></iframe>
									</div>
								</div>
								<?php } else { ?>
								<div class="da-learning-editor-wrap">
									<textarea id="da-learning-wysiwyg" name="learning_html" rows="18"><?php echo da_h((string)$daLearnEditor["body"]); ?></textarea>
								</div>
								<?php } ?>
							</form>
							<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" crossorigin="anonymous"></script>
							<div class="da-inline" style="margin-top:8px;">
								<form method="post" style="display:inline-flex; gap:6px; align-items:center;">
									<input type="hidden" name="mode" value="manage">
									<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
									<input type="hidden" name="admin_action" value="download_learning_html">
									<input type="hidden" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>">
									<?php da_echo_ui_persist_hidden_inputs(); ?>
									<button class="da-btn" type="submit">HTML herunterladen</button>
								</form>
								<form method="post" enctype="multipart/form-data" style="display:inline-flex; gap:6px; align-items:center;">
									<input type="hidden" name="mode" value="manage">
									<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
									<input type="hidden" name="admin_action" value="upload_learning_html_file">
									<input type="hidden" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>">
									<?php da_echo_ui_persist_hidden_inputs(); ?>
									<input type="file" name="learning_html_file" accept=".html,text/html">
									<button class="da-btn" type="submit">HTML hochladen (ersetzt Inhalt)</button>
								</form>
							</div>
						</div>
					</details>
					<datalist id="da-image-options">
						<?php foreach ($imageSelectOptions as $imgOpt) { ?>
							<option value="<?php echo da_h($imgOpt); ?>"></option>
						<?php } ?>
					</datalist>
				<?php } ?>
			</div>
		</div>
		<?php if (!empty($da_help_links)) { ?>
		<footer class="da-docfooter" aria-label="Aufruf-Dokumentation">
			<?php foreach ($da_help_links as $da_hl_i => $da_hl) {
				if ($da_hl_i > 0) {
					echo "<span aria-hidden=\"true\">·</span> ";
				}
				echo "<a href=\"" . da_h($da_hl["href"]) . "\" target=\"_blank\" rel=\"noopener\">" . da_h($da_hl["label"]) . "</a>";
			} ?>
		</footer>
		<?php } ?>
	</div>
	<script>
	(function () {
		const imageBase = <?php echo json_encode((string)$manageImageBaseWeb); ?>;
		const drivePrefix = <?php echo json_encode($driveLocCurrent !== "" ? (rtrim((string)$driveLocCurrent, "/") . "/") : ""); ?>;
		const grid = document.getElementById('da-grid');
		const splitter = document.getElementById('da-splitter');
		const SPLIT_STORAGE_KEY = 'rkrk_da_admin_grid_split_px';
		let splitDragging = false;
		let lastSplitSafe = null;

		function isDesktop() {
			return window.matchMedia('(min-width: 1200px)').matches;
		}

		function loadSavedSplit() {
			try {
				const v = localStorage.getItem(SPLIT_STORAGE_KEY);
				if (v === null || v === '') return null;
				const n = parseFloat(v);
				return Number.isFinite(n) ? n : null;
			} catch (e) {
				return null;
			}
		}

		function saveSavedSplit(px) {
			try {
				localStorage.setItem(SPLIT_STORAGE_KEY, String(px));
			} catch (e) {}
		}

		function applyColumns(leftPx) {
			if (!isDesktop() || !grid) return null;
			const safe = Math.max(280, Math.min(leftPx, window.innerWidth - 360));
			grid.style.gridTemplateColumns = safe + 'px 10px minmax(0, 1fr)';
			return safe;
		}

		if (grid && splitter) {
			const savedSplit = loadSavedSplit();
			if (savedSplit !== null) {
				applyColumns(savedSplit);
			}

			splitter.addEventListener('mousedown', function () {
				if (!isDesktop()) return;
				splitDragging = true;
				lastSplitSafe = null;
				document.body.style.userSelect = 'none';
			});
			window.addEventListener('mousemove', function (e) {
				if (!splitDragging || !grid) return;
				const rect = grid.getBoundingClientRect();
				const safe = applyColumns(e.clientX - rect.left);
				if (safe !== null) lastSplitSafe = safe;
			});
			window.addEventListener('mouseup', function () {
				if (splitDragging && lastSplitSafe !== null) {
					saveSavedSplit(lastSplitSafe);
				}
				splitDragging = false;
				document.body.style.userSelect = '';
			});
		}

		function daEncodePathSeg(seg) {
			if (!seg) return '';
			let s = seg;
			for (let i = 0; i < 6; i++) {
				try {
					const d = decodeURIComponent(s);
					if (d === s) break;
					s = d;
				} catch (e) { break; }
			}
			try { if (typeof s.normalize === 'function') s = s.normalize('NFC'); } catch (e2) {}
			return encodeURIComponent(s);
		}
		function daImageUrlFromRel(rel) {
			const r = String(rel || '').replace(/\\/g, '/').trim().replace(/^\/+/, '');
			if (!r) return '';
			return String(imageBase || '') + r.split('/').map(daEncodePathSeg).filter(Boolean).join('/');
		}

		function toDrivePath(raw) {
			let v = String(raw || '').trim().replace(/^\/+/, '');
			if (!v) return '';
			if (drivePrefix && v.indexOf(drivePrefix) !== 0) v = drivePrefix + v;
			return v;
		}

		function updatePreview(input) {
			if (!input) return;
			const rel = toDrivePath(input.value);
			const host = input.closest('td, label, .da-section-body') || input.parentElement;
			if (!host) return;
			let a = host.querySelector('a.da-picicon');
			if (!rel) {
				if (a) a.style.display = 'none';
				return;
			}
			const url = daImageUrlFromRel(rel);
			if (!a) {
				a = document.createElement('a');
				a.className = 'da-picicon';
				a.target = '_blank';
				a.rel = 'noopener';
				a.innerHTML = "<img alt='Bild'><span>Bild</span>";
				host.appendChild(a);
			}
			const img = a.querySelector('img');
			a.href = url;
			if (img) img.src = url;
			a.style.display = 'inline-flex';
		}

		function bindImageSelect(sel) {
			if (!sel || sel.dataset.bound === '1') return;
			sel.dataset.bound = '1';
			sel.addEventListener('change', function () {
				const targetId = sel.getAttribute('data-target');
				const target = targetId ? document.getElementById(targetId) : null;
				if (!target) return;
				if (sel.value) target.value = sel.value;
				updatePreview(target);
			});
		}

		function bindImageInput(inp) {
			if (!inp || inp.dataset.bound === '1') return;
			inp.dataset.bound = '1';
			inp.addEventListener('input', function () { updatePreview(inp); });
			inp.addEventListener('change', function () { updatePreview(inp); });
		}

		document.querySelectorAll('.da-image-select').forEach(bindImageSelect);
		document.querySelectorAll("input[list='da-image-options'], #da-assessment-bild-input").forEach(bindImageInput);

		const qBody = document.getElementById('da-question-body');
		const addBtn = document.getElementById('da-add-question-btn');
		const qForm = qBody ? qBody.closest('form') : null;

		function reindexQuestionRows() {
			if (!qBody) return;
			const rows = Array.from(qBody.querySelectorAll('tr.da-q-row'));
			rows.forEach(function (row, idx) {
				const nr = row.querySelector('.da-q-nr');
				if (nr) nr.textContent = String(idx + 1);
				row.querySelectorAll('input[name^="q["], textarea[name^="q["], select[name^="q["]').forEach(function (el) {
					const n = el.getAttribute('name') || '';
					const m = n.match(/^q\[([^\]]+)\]\[\d+\]$/);
					if (!m) return;
					el.setAttribute('name', 'q[' + m[1] + '][' + idx + ']');
				});
			});
		}

		function bindRowActions(row) {
			if (!row || row.dataset.bound === '1') return;
			row.dataset.bound = '1';
			const delBtn = row.querySelector('.da-q-del');
			if (delBtn) {
				delBtn.addEventListener('click', function () {
					if (!qBody) return;
					if (qBody.querySelectorAll('tr.da-q-row').length <= 1) {
						row.querySelectorAll('input[name^="q["], textarea[name^="q["], select[name^="q["]').forEach(function (el) {
							if (el.tagName === 'SELECT') el.selectedIndex = 0;
							else el.value = '';
						});
						reindexQuestionRows();
						return;
					}
					row.remove();
					reindexQuestionRows();
				});
			}
			const handle = row.querySelector('.da-q-handle');
			if (handle) {
				handle.addEventListener('dragstart', function (e) {
					row.classList.add('is-dragging');
					e.dataTransfer.effectAllowed = 'move';
					try {
						e.dataTransfer.setData('text/plain', 'row');
					} catch (err) {}
				});
				handle.addEventListener('dragend', function () {
					row.classList.remove('is-dragging');
					reindexQuestionRows();
				});
			}
		}

		if (qBody) {
			Array.from(qBody.querySelectorAll('tr.da-q-row')).forEach(bindRowActions);
			qBody.addEventListener('dragover', function (e) {
				e.preventDefault();
				const draggingRow = qBody.querySelector('.is-dragging');
				if (!draggingRow) return;
				const siblings = Array.from(qBody.querySelectorAll('tr.da-q-row:not(.is-dragging)'));
				const next = siblings.find(function (s) {
					const box = s.getBoundingClientRect();
					return e.clientY <= box.top + box.height / 2;
				});
				if (next) qBody.insertBefore(draggingRow, next);
				else qBody.appendChild(draggingRow);
			});
		}

		if (addBtn && qBody) {
			addBtn.addEventListener('click', function () {
				const rows = qBody.querySelectorAll('tr.da-q-row');
				if (!rows.length) return;
				const clone = rows[rows.length - 1].cloneNode(true);
				clone.querySelectorAll('input[name^="q["], textarea[name^="q["], select[name^="q["]').forEach(function (el) {
					if (el.tagName === 'SELECT') {
						el.selectedIndex = 0;
					} else {
						el.value = '';
					}
					el.dataset.bound = '';
					if (el.id) {
						const newid = el.id + '_n' + Date.now().toString(36);
						clone.querySelectorAll('[data-target="' + el.id + '"]').forEach(function (sel) {
							sel.setAttribute('data-target', newid);
						});
						el.id = newid;
					}
				});
				clone.querySelectorAll('a.da-picicon').forEach(function (a) { a.remove(); });
				clone.querySelectorAll('.da-image-select').forEach(function (sel) {
					sel.selectedIndex = 0;
					sel.dataset.bound = '';
				});
				clone.dataset.bound = '';
				clone.classList.remove('is-dragging');
				clone.setAttribute('data-orig-idx', '-1');
				qBody.appendChild(clone);
				const stSel = clone.querySelector('select[name^="q[Status]"]');
				if (stSel) {
					stSel.value = 'neu';
				}
				bindRowActions(clone);
				clone.querySelectorAll('.da-image-select').forEach(bindImageSelect);
				clone.querySelectorAll("input[list='da-image-options']").forEach(bindImageInput);
				reindexQuestionRows();
			});
		}

		if (qForm) {
			qForm.addEventListener('submit', function () {
				reindexQuestionRows();
				const jsonInput = document.getElementById('da-q-json');
				if (jsonInput && qBody) {
					const rows = [];
					qBody.querySelectorAll('tr.da-q-row').forEach(function (row, idx) {
						const rowObj = {};
						row.querySelectorAll('input[name^="q["], textarea[name^="q["], select[name^="q["]').forEach(function (el) {
							const m = el.name && el.name.match(/^q\[([^\]]+)\]\[(\d+)\]$/);
							if (m) {
								rowObj[m[1]] = el.value;
							}
						});
						rows.push(rowObj);
					});
					jsonInput.value = JSON.stringify(rows);
					const origInput = document.getElementById('da-q-row-orig');
					if (origInput && qBody) {
						const orig = [];
						qBody.querySelectorAll('tr.da-q-row').forEach(function (row) {
							var a = row.getAttribute('data-orig-idx');
							orig.push(a === null || a === '' ? -1 : parseInt(a, 10));
						});
						origInput.value = JSON.stringify(orig);
					}
					qBody.querySelectorAll('input[name^="q["], textarea[name^="q["], select[name^="q["]').forEach(function (el) {
						el.disabled = true;
					});
				}
			});
		}
		reindexQuestionRows();

		const reviewForm = document.getElementById('da-review-form');
		const reviewDialog = document.getElementById('da-review-dialog');
		const editFrage = document.getElementById('da-review-edit-frage');
		const editOptions = document.getElementById('da-review-edit-options');
		const editSolution = document.getElementById('da-review-edit-solution');
		const editSolutionText = document.getElementById('da-review-edit-solution-text');
		const editCancel = document.getElementById('da-review-edit-cancel');
		const editApply = document.getElementById('da-review-edit-apply');
		let activeReviewCard = null;

		function reviewField(card, field) {
			return card ? card.querySelector('input[type="hidden"][data-review-field="' + field + '"]') : null;
		}

		function setReviewField(card, field, value) {
			const input = reviewField(card, field);
			if (input) input.value = value == null ? '' : String(value);
		}

		function getReviewField(card, field) {
			const input = reviewField(card, field);
			return input ? input.value : '';
		}

		function solutionLookup(raw) {
			const lookup = {};
			String(raw || '').split('|').map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (s) {
				lookup[s.toLowerCase()] = true;
			});
			return lookup;
		}

		function refreshReviewCard(card) {
			if (!card) return;
			const frage = getReviewField(card, 'Frage');
			const optsRaw = getReviewField(card, 'Loesungsauswahl');
			const sol = getReviewField(card, 'Loesung');
			const solText = getReviewField(card, 'Loesungstext');
			const qEl = card.querySelector('.da-review-question');
			const solEl = card.querySelector('.da-review-sol');
			const solutionWrap = card.querySelector('.da-review-solution-wrap');
			const solutionBox = card.querySelector('.da-review-solution');
			if (qEl) qEl.textContent = frage;
			if (solEl) solEl.textContent = sol;
			if (solutionBox) {
				solutionBox.textContent = solText || '—';
			}
			if (solutionWrap) {
				solutionWrap.classList.toggle('da-review-solution-wrap--empty', !solText);
				const baseline = solutionWrap.getAttribute('data-baseline-loesungstext') || '';
				solutionWrap.classList.toggle('da-review-solution-changed', String(solText || '').trim() !== baseline);
			}
			const oldList = card.querySelector('.da-review-options');
			const opts = optsRaw.split('|').map(function (s) { return s.trim(); }).filter(Boolean);
			if (oldList) oldList.remove();
			if (opts.length) {
				const ol = document.createElement('ol');
				ol.className = 'da-review-options';
				ol.type = 'A';
				const lookup = solutionLookup(sol);
				opts.forEach(function (opt, idx) {
					const li = document.createElement('li');
					const letter = String.fromCharCode(65 + idx);
					const num = String(idx + 1);
					li.textContent = opt;
					if (lookup[letter.toLowerCase()] || lookup[num.toLowerCase()] || lookup[opt.toLowerCase()]) {
						li.className = 'da-review-correct';
					}
					ol.appendChild(li);
				});
				const meta = card.querySelector('.da-review-meta');
				if (meta) meta.insertAdjacentElement('afterend', ol);
			}
		}

		function syncReviewControls(card) {
			if (!card) return;
			const prio = card.querySelector('select.da-sel-prio');
			const status = card.querySelector('select.da-sel-status');
			const feedback = card.querySelector('select.da-sel-rueck');
			const feedbackText = card.querySelector('.da-review-feedback-text');
			if (prio) setReviewField(card, 'Prioritaet', prio.value);
			if (status) setReviewField(card, 'Status', status.value || 'aktiv');
			if (feedback) setReviewField(card, 'Rueckmeldung', feedback.value || 'offen');
			if (feedbackText) setReviewField(card, 'RueckmeldungText', feedbackText.value);
		}

		if (reviewForm) {
			reviewForm.querySelectorAll('.da-review-card').forEach(function (card) {
				const prio = card.querySelector('select.da-sel-prio');
				const status = card.querySelector('select.da-sel-status');
				const feedback = card.querySelector('select.da-sel-rueck');
				const feedbackText = card.querySelector('.da-review-feedback-text');
				[prio, status, feedback, feedbackText].forEach(function (el) {
					if (!el) return;
					el.addEventListener('input', function () { syncReviewControls(card); });
					el.addEventListener('change', function () { syncReviewControls(card); });
				});
				const clearBtn = card.querySelector('[data-clear-feedback]');
				if (clearBtn && feedbackText) {
					clearBtn.addEventListener('click', function () {
						feedbackText.value = '';
						syncReviewControls(card);
						feedbackText.focus();
					});
				}
				const editBtn = card.querySelector('.da-review-edit-btn');
				if (editBtn) {
					editBtn.addEventListener('click', function () {
						activeReviewCard = card;
						if (editFrage) editFrage.value = getReviewField(card, 'Frage');
						if (editOptions) editOptions.value = getReviewField(card, 'Loesungsauswahl');
						if (editSolution) editSolution.value = getReviewField(card, 'Loesung');
						if (editSolutionText) editSolutionText.value = getReviewField(card, 'Loesungstext');
						if (reviewDialog && typeof reviewDialog.showModal === 'function') {
							reviewDialog.showModal();
						} else if (reviewDialog) {
							reviewDialog.setAttribute('open', 'open');
						}
					});
				}
				syncReviewControls(card);
			});
			reviewForm.addEventListener('submit', function () {
				reviewForm.querySelectorAll('.da-review-card').forEach(syncReviewControls);
				const jsonInput = document.getElementById('da-review-q-json');
				if (jsonInput) {
					const rows = [];
					reviewForm.querySelectorAll('.da-review-card').forEach(function (card) {
						const row = {};
						card.querySelectorAll('input[type="hidden"][data-review-field]').forEach(function (inp) {
							const k = inp.getAttribute('data-review-field');
							if (k) {
								row[k] = inp.value;
							}
						});
						rows.push(row);
					});
					jsonInput.value = JSON.stringify(rows);
				}
				const reviewOrigInput = document.getElementById('da-review-q-row-orig');
				if (reviewOrigInput) {
					const orig = [];
					reviewForm.querySelectorAll('.da-review-card').forEach(function (card) {
						var a = card.getAttribute('data-orig-idx');
						orig.push(a === null || a === '' ? -1 : parseInt(a, 10));
					});
					reviewOrigInput.value = JSON.stringify(orig);
				}
			});
		}

		if (editCancel && reviewDialog) {
			editCancel.addEventListener('click', function () {
				if (typeof reviewDialog.close === 'function') reviewDialog.close();
				else reviewDialog.removeAttribute('open');
			});
		}
		if (editApply && reviewDialog) {
			editApply.addEventListener('click', function () {
				if (!activeReviewCard) return;
				setReviewField(activeReviewCard, 'Frage', editFrage ? editFrage.value : '');
				setReviewField(activeReviewCard, 'Loesungsauswahl', editOptions ? editOptions.value : '');
				setReviewField(activeReviewCard, 'Loesung', editSolution ? editSolution.value : '');
				setReviewField(activeReviewCard, 'Loesungstext', editSolutionText ? editSolutionText.value : '');
				refreshReviewCard(activeReviewCard);
				syncReviewControls(activeReviewCard);
				if (typeof reviewDialog.close === 'function') reviewDialog.close();
				else reviewDialog.removeAttribute('open');
			});
		}

		// UI-Zustand: offene Abschnitte, Scroll, Wahrscheinlichkeits-Filter beim Speichern erhalten
		const daWrap = document.querySelector('.da-wrap');
		const daQuizKeyEl = document.querySelector('input[name="selected_quiz"]');
		const daQuizKey = daQuizKeyEl && daQuizKeyEl.value ? String(daQuizKeyEl.value) : '_';
		const DA_SECTIONS_KEY = 'rkrk_da_admin_sections_' + daQuizKey;
		const DA_SCROLL_KEY = 'rkrk_da_admin_scroll_' + daQuizKey;

		function daSectionKey(el) {
			if (!el) return '';
			if (el.id) return el.id;
			const sum = el.querySelector('summary');
			return sum ? String(sum.textContent || '').trim() : '';
		}

		function daSaveSections() {
			if (!daWrap) return;
			const open = [];
			daWrap.querySelectorAll('details.da-section, details.da-leftbox').forEach(function (d) {
				const k = daSectionKey(d);
				if (k && d.open) open.push(k);
			});
			try { localStorage.setItem(DA_SECTIONS_KEY, JSON.stringify(open)); } catch (e) {}
		}

		function daRestoreSections() {
			if (!daWrap) return;
			let open = null;
			try {
				const raw = localStorage.getItem(DA_SECTIONS_KEY);
				if (raw === null || raw === '') return;
				open = JSON.parse(raw);
			} catch (e) { return; }
			if (!Array.isArray(open)) return;
			const set = new Set(open);
			daWrap.querySelectorAll('details.da-section, details.da-leftbox').forEach(function (d) {
				const k = daSectionKey(d);
				if (k) d.open = set.has(k);
			});
		}

		function daSetFormHidden(form, name, value) {
			if (!form) return;
			let el = form.querySelector('input[name="' + name + '"]');
			if (!el) {
				el = document.createElement('input');
				el.type = 'hidden';
				el.name = name;
				form.appendChild(el);
			}
			el.value = value == null ? '' : String(value);
		}

		function daSyncUiStateToForm(form) {
			if (!form) return;
			const probPick = document.querySelector('#fragen-probability input[name="da_prob_pickn"]');
			const probDrone = document.querySelector('#fragen-probability select[name="da_prob_drone"]');
			if (probPick) daSetFormHidden(form, 'da_prob_pickn', probPick.value);
			if (probDrone) daSetFormHidden(form, 'da_prob_drone', probDrone.value);
			daSaveSections();
			try { sessionStorage.setItem(DA_SCROLL_KEY, String(window.scrollY)); } catch (e) {}
		}

		daRestoreSections();
		if (daWrap) {
			daWrap.querySelectorAll('details.da-section, details.da-leftbox').forEach(function (d) {
				d.addEventListener('toggle', daSaveSections);
			});
		}
		if (daWrap) {
			daWrap.querySelectorAll('form[method="post"]').forEach(function (f) {
				f.addEventListener('submit', function () { daSyncUiStateToForm(f); });
			});
		}
		(function daRestoreScroll() {
			try {
				const y = sessionStorage.getItem(DA_SCROLL_KEY);
				if (y === null) return;
				sessionStorage.removeItem(DA_SCROLL_KEY);
				const n = parseInt(y, 10);
				if (!isNaN(n)) requestAnimationFrame(function () { window.scrollTo(0, n); });
			} catch (e) {}
		})();

		(function daInitLearningWysiwyg() {
			const textarea = document.getElementById('da-learning-wysiwyg');
			const learnForm = document.getElementById('da-learning-form');
			const learnDetails = learnForm ? learnForm.closest('details') : null;
			const learnWorkspace = document.getElementById('da-learning-workspace');
			const layoutToggle = document.getElementById('da-learning-layout-toggle');
			if (!textarea || !window.tinymce) return;

			const contentCss = <?php echo json_encode(array_values($daLearnContentCss), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
			const learnBodyStyle = 'html{height:100%;}body{margin:0;padding:12px 14px;box-sizing:border-box;max-width:100%;min-height:100%;overflow-y:auto;}'
				+ 'figure{max-width:min(100%,400px);margin:14px 0;padding:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;}'
				+ 'figure img,figure picture img{max-width:100%;width:auto!important;height:auto!important;display:block;border:0;border-radius:8px;}'
				+ 'img,svg,video{max-width:min(100%,380px);width:auto!important;height:auto;display:block;border-radius:10px;border:1px solid #e5e7eb;}';

			const quizInput = learnForm ? learnForm.querySelector('input[name="selected_quiz"]') : null;
			const quizKey = quizInput && quizInput.value ? quizInput.value : '_';
			const LAYOUT_KEY = 'da_learning_layout_' + quizKey;
			let learnResizeObs = null;

			function isHorizontalLayout() {
				return !!(learnWorkspace && learnWorkspace.classList.contains('da-learning-layout-horizontal'));
			}

			function applyLearningLayout(mode) {
				if (!learnWorkspace) return;
				const horizontal = mode === 'horizontal';
				learnWorkspace.classList.toggle('da-learning-layout-horizontal', horizontal);
				learnWorkspace.classList.toggle('da-learning-layout-vertical', !horizontal);
				if (layoutToggle) {
					layoutToggle.setAttribute('aria-pressed', horizontal ? 'true' : 'false');
					layoutToggle.textContent = horizontal ? '↕ Untereinander' : '↔ Nebeneinander';
					layoutToggle.title = horizontal
						? 'Editor und Vorschau untereinander anzeigen'
						: 'Editor und Vorschau nebeneinander anzeigen';
				}
				try { localStorage.setItem(LAYOUT_KEY, mode); } catch (e) {}
				resizeLearnEditor();
			}

			function resizeLearnEditor() {
				const ed = tinymce.get('da-learning-wysiwyg');
				if (!ed) return;
				const wrap = textarea.closest('.da-learning-editor-wrap');
				if (wrap && ed.getContainer) {
					const box = ed.getContainer();
					const w = Math.max(0, wrap.clientWidth);
					const h = Math.max(200, wrap.clientHeight);
					box.style.width = w > 0 ? (w + 'px') : '100%';
					box.style.height = h + 'px';
					box.style.minHeight = h + 'px';
					box.style.maxHeight = 'none';
					box.querySelectorAll('.tox-edit-area > textarea, textarea.tox-textarea').forEach(function (ta) {
						ta.style.resize = 'none';
						ta.style.height = '100%';
						ta.style.minHeight = '0';
						ta.style.overflowY = 'auto';
						ta.style.overflowX = 'auto';
					});
				}
				try { ed.dispatch('ResizeEditor'); } catch (e) {}
			}

			function setupLearnResizeObserver() {
				const wrap = textarea.closest('.da-learning-editor-wrap');
				if (!wrap || !window.ResizeObserver) return;
				if (learnResizeObs) learnResizeObs.disconnect();
				learnResizeObs = new ResizeObserver(function () {
					resizeLearnEditor();
				});
				learnResizeObs.observe(wrap);
			}

			if (learnWorkspace) {
				try {
					const saved = localStorage.getItem(LAYOUT_KEY);
					if (saved === 'horizontal' || saved === 'vertical') {
						applyLearningLayout(saved);
					}
				} catch (e) {}
				setupLearnResizeObserver();
			}

			if (layoutToggle && learnWorkspace) {
				layoutToggle.addEventListener('click', function () {
					applyLearningLayout(isHorizontalLayout() ? 'vertical' : 'horizontal');
				});
			}

			function startLearnEditor() {
				if (window.__daLearnEditorInited) {
					resizeLearnEditor();
					return;
				}
				if (learnDetails && !learnDetails.open) return;
				window.__daLearnEditorInited = true;

				tinymce.init({
					selector: '#da-learning-wysiwyg',
					width: '100%',
					height: '100%',
					resize: false,
					menubar: false,
					language: 'de',
					language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@24.12.9/langs7/de.js',
					license_key: 'gpl',
					promotion: false,
					branding: false,
					convert_urls: false,
					relative_urls: false,
					remove_script_host: false,
					plugins: 'lists link image table code anchor charmap searchreplace visualblocks',
					toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | removeformat | code',
					content_css: contentCss.length ? contentCss : false,
					content_style: learnBodyStyle,
					valid_elements: '*[*]',
					extended_valid_elements: '*[*]',
					setup: function (editor) {
						editor.on('change input undo redo', function () {
							editor.save();
						});
						editor.on('init', function () {
							resizeLearnEditor();
						});
						editor.on('SwitchMode', function () {
							requestAnimationFrame(resizeLearnEditor);
						});
					}
				});
			}

			if (learnForm) {
				learnForm.addEventListener('submit', function () {
					const ed = tinymce.get('da-learning-wysiwyg');
					if (ed) ed.save();
				});
			}
			if (learnDetails) {
				learnDetails.addEventListener('toggle', function () {
					if (learnDetails.open) {
						startLearnEditor();
						requestAnimationFrame(resizeLearnEditor);
					}
				});
			}
			startLearnEditor();
			window.addEventListener('resize', resizeLearnEditor);
		})();

		(function daLearningAutosave() {
			const learnForm = document.getElementById('da-learning-form');
			const autosaveSwitch = document.getElementById('da-learning-autosave-switch');
			const autosaveStatus = document.getElementById('da-learning-autosave-status');
			const baselineInput = document.getElementById('da-learning-baseline-hash');
			if (!learnForm || !autosaveSwitch) return;

			let lastActivityAt = 0;
			let autosaveInFlight = false;
			let autosaveTimer = null;
			const LEARN_AUTOSAVE_MS = 60000;
			const LEARN_AUTOSAVE_IDLE_MS = 120000;

			function learnAutosaveStorageKey() {
				const q = learnForm.querySelector('input[name="selected_quiz"]');
				return 'da_learning_autosave_' + (q && q.value ? q.value : '_');
			}

			function getLearnBody() {
				const ed = window.tinymce && tinymce.get('da-learning-wysiwyg');
				if (ed) return ed.getContent();
				const ta = document.getElementById('da-learning-wysiwyg');
				return ta ? ta.value : '';
			}

			function syncLearnBodyToTextarea() {
				const ta = document.getElementById('da-learning-wysiwyg');
				if (ta) ta.value = getLearnBody();
			}

			function setLearnBaseline(hash) {
				if (baselineInput && hash) baselineInput.value = hash;
			}

			function setLearnAutosaveStatus(text) {
				if (autosaveStatus) autosaveStatus.textContent = text || '';
			}

			function formatLearnAutosaveTime(date) {
				try {
					return date.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
				} catch (e) {
					return date.toLocaleTimeString();
				}
			}

			function saveLearningAjax() {
				if (autosaveInFlight || !autosaveSwitch.checked) return Promise.resolve(null);
				syncLearnBodyToTextarea();
				autosaveInFlight = true;
				setLearnAutosaveStatus('Speichert…');
				const fd = new FormData(learnForm);
				fd.set('learning_autosave_ajax', '1');
				return fetch(learnForm.action || window.location.href, {
					method: 'POST',
					body: fd,
					credentials: 'same-origin'
				}).then(function (r) { return r.json(); }).then(function (data) {
					if (data && data.ok) {
						if (data.content_hash) setLearnBaseline(data.content_hash);
						if (!data.skipped) {
							setLearnAutosaveStatus('Autosave ' + formatLearnAutosaveTime(data.saved_at ? new Date(data.saved_at) : new Date()));
							const fr = document.getElementById('da-learning-preview-frame');
							if (fr && fr.src) fr.src = fr.src;
						} else {
							setLearnAutosaveStatus('');
						}
					} else {
						setLearnAutosaveStatus(data && data.error ? data.error : 'Autosave fehlgeschlagen');
					}
					return data;
				}).catch(function () {
					setLearnAutosaveStatus('Autosave fehlgeschlagen');
					return null;
				}).finally(function () {
					autosaveInFlight = false;
				});
			}

			function noteLearnActivity() {
				lastActivityAt = Date.now();
			}

			function tickLearnAutosave() {
				if (!autosaveSwitch.checked || autosaveInFlight) return;
				if (Date.now() - lastActivityAt > LEARN_AUTOSAVE_IDLE_MS) return;
				saveLearningAjax();
			}

			function updateLearnAutosaveTimer() {
				if (autosaveTimer) {
					clearInterval(autosaveTimer);
					autosaveTimer = null;
				}
				if (autosaveSwitch.checked) {
					autosaveTimer = window.setInterval(tickLearnAutosave, LEARN_AUTOSAVE_MS);
				}
			}

			autosaveSwitch.addEventListener('change', function () {
				try { localStorage.setItem(learnAutosaveStorageKey(), autosaveSwitch.checked ? '1' : '0'); } catch (e) {}
				if (!autosaveSwitch.checked) setLearnAutosaveStatus('');
				updateLearnAutosaveTimer();
			});

			try {
				if (localStorage.getItem(learnAutosaveStorageKey()) === '0') autosaveSwitch.checked = false;
			} catch (e) {}

			learnForm.addEventListener('input', noteLearnActivity, true);
			learnForm.addEventListener('change', noteLearnActivity, true);

			const learnHookTimer = window.setInterval(function () {
				const ed = window.tinymce && tinymce.get('da-learning-wysiwyg');
				if (!ed) return;
				window.clearInterval(learnHookTimer);
				ed.on('change input undo redo SetContent', noteLearnActivity);
			}, 400);

			updateLearnAutosaveTimer();
		})();

	})();
	</script>
	</body>
	</html>
	<?php
	exit;
}
