<?php

if (session_status() !== PHP_SESSION_ACTIVE) {

	session_start();

}
ini_set("default_charset", "UTF-8");
if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("UTF-8");
}
if (!headers_sent()) {
	header("Content-Type: text/html; charset=UTF-8");
}


$folderoffset = "../";

include_once($folderoffset.'scripts/rkrk_inputforms.php');

include_once($folderoffset.'scripts/rkrk_csvfiles.php');

include_once('rkrk_assess_common.php');

include_once(__DIR__ . '/rkrk_digitalassess_read_render.php');

include_once('rkrk_checklist_toolbar_inc.php');



if (!$onRKRS) {

	CheckIPandExit($folderoffset);

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

	// RKRS: Theme-CSS (Portal-Menü nutzt --brand-dark u. a.)
	rkrk_assess_theme_link_tags();

}



headerpage("Fragen Review", false, $folderoffset);

if (function_exists('rkrk_assess_theme_rkrs_layer_link') && function_exists('rkrk_assess_ci_rkrs_mode') && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}

if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}



function dar_h($value) {

	return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");

}



function dar_fix_mojibake($value) {
	$value = (string)$value;
	if ($value === "") return $value;
	return strtr($value, array(
		"Ã¤" => "ä", "Ã„" => "Ä", "Ã¶" => "ö", "Ã–" => "Ö", "Ã¼" => "ü", "Ãœ" => "Ü", "ÃŸ" => "ß",
		"â€“" => "–", "â€”" => "—", "â€ž" => "„", "â€œ" => "“", "â€" => "”", "â€˜" => "‘", "â€™" => "’",
		"Â°" => "°", "Â" => "",
	));
}

function dar_clean_relative_file($value, $defaultExt = "csv") {

	$value = str_replace("\\", "/", trim((string)$value));

	$value = ltrim($value, "/");

	$value = preg_replace('/\.\.+/', '', (string)$value);

	if ($value === "") return "";

	if (preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) !== 1) return "";

	if (strpos($value, ".") === false && $defaultExt !== "") {

		$value .= "." . $defaultExt;

	}

	return $value;

}



function dar_clean_relative_folder($value) {

	$value = str_replace("\\", "/", trim((string)$value));

	$value = trim($value, "/");

	$value = preg_replace('/\.\.+/', '', (string)$value);

	if ($value === "") return "";

	// DriveLocation z. B. «RKRS Einsatzprüfung» (Leerzeichen).
	return preg_match('/^[\p{L}\p{N}_\-\/\. ]+$/u', $value) === 1 ? $value : "";

}



function dar_with_drive_location($file, $driveLoc) {

	$file = dar_clean_relative_file($file, "");

	$driveLoc = dar_clean_relative_folder($driveLoc);

	if ($file === "") return "";

	if ($driveLoc === "") return $file;

	$prefix = rtrim($driveLoc, "/") . "/";

	return strpos($file, $prefix) === 0 ? $file : $prefix . ltrim($file, "/");

}



function dar_question_image_rel(string $rawPath, string $driveLoc): string {

	if (function_exists("rkrk_dga_rh_question_image_rel")) {

		return rkrk_dga_rh_question_image_rel($rawPath, $driveLoc);

	}

	return dar_with_drive_location($rawPath, $driveLoc);

}



function dar_read_csv($path) {
	if (!function_exists("rkrk_assess_read_csv_file")) {
		return array(array(), array());
	}
	list($headers, $rows) = rkrk_assess_read_csv_file($path, null, ";");
	foreach ($rows as $ri => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach ($row as $hk => $hv) {
			$rows[$ri][$hk] = dar_fix_mojibake((string) $hv);
		}
	}
	return array($headers, $rows);
}

function dar_write_csv($path, $headers, $rows) {
	return function_exists("rkrk_assess_write_csv_file")
		? rkrk_assess_write_csv_file($path, $headers, $rows, ";")
		: false;
}

function dar_ensure_headers($headers) {

	$target = rkrk_assess_question_csv_headers();

	$out = array();

	foreach ($target as $h) {

		if (in_array($h, $headers, true)) $out[] = $h;

	}

	foreach ($target as $h) {

		if (!in_array($h, $out, true)) $out[] = $h;

	}

	foreach ($headers as $h) {

		if ($h !== "" && !in_array($h, $out, true)) $out[] = $h;

	}

	return $out;

}



function dar_option_select($name, $current, $options, $className = "") {

	$current = trim((string)$current);

	$nameAttr = ((string)$name !== "") ? (" name='" . dar_h((string)$name) . "'") : "";

	$classAttr = ($className !== "") ? (" class='" . dar_h($className) . "'") : "";

	$html = "<select" . $nameAttr . $classAttr . ">";

	foreach ($options as $opt) {

		$sel = ((string)$opt === $current) ? " selected" : "";

		$html .= "<option value='" . dar_h($opt) . "'" . $sel . ">" . dar_h($opt) . "</option>";

	}

	if ($current !== "" && !in_array($current, $options, true)) {

		$html .= "<option value='" . dar_h($current) . "' selected>" . dar_h($current) . "</option>";

	}

	return $html . "</select>";

}



function dar_question_live($row) {

	if (function_exists('rkrk_assess_question_is_quiz_live')) {

		return rkrk_assess_question_is_quiz_live($row);

	}

	$status = strtolower(trim((string)($row["Status"] ?? "")));

	if ($status === "") $status = "aktiv";

	return $status === "aktiv";

}



$assessmentHeaders = array("Nr","Key","Titel","Kurzbeschreibung","Bild","Einfuehrung","Erfuellt","PunkteModus","DriveLocation","LernDatei","FragenDatei","AnzQuestions","Audienz","QuizTime","Quiz","Schulung","AntwortMischen","FragenBildAnzeigen","Aktiv","Locked");

$assessmentPath = $QuizLoc . "AssessmentListe.csv";

list($assessmentCsvHeaders, $assessmentsRaw) = dar_read_csv($assessmentPath);

$assessments = array();

foreach ($assessmentsRaw as $row) {

	$line = array();

	foreach ($assessmentHeaders as $h) {

		$line[$h] = isset($row[$h]) ? (string)$row[$h] : "";

	}

	$assessments[] = $line;

}

$dar_review_admin = isset($_GET["ADMIN"]) || (isset($onAdmin) && (bool)$onAdmin);

$assessmentsSelectable = array_values(array_filter($assessments, static function ($row) use ($dar_review_admin) {
	if ($dar_review_admin) {
		return true;
	}
	return trim((string)($row["Aktiv"] ?? "")) === "Ja";
}));

$selectedKey = isset($_REQUEST["selected_quiz"]) ? trim((string)$_REQUEST["selected_quiz"]) : "";

if ($selectedKey === "" && isset($_REQUEST["quiz"])) {

	$quizParam = trim((string)$_REQUEST["quiz"]);

	foreach ($assessments as $row) {

		$key = trim((string)$row["Key"]);

		$title = trim((string)$row["Titel"]);

		if ($key !== "" && (strcasecmp($key, $quizParam) === 0 || strcasecmp($title, $quizParam) === 0)) {

			if (!$dar_review_admin && trim((string)($row["Aktiv"] ?? "")) !== "Ja") {

				continue;

			}

			$selectedKey = $key;

			break;

		}

	}

}

if ($selectedKey === "" && count($assessmentsSelectable) > 0) {

	$selectedKey = (string)$assessmentsSelectable[0]["Key"];

}

if ($selectedKey !== "" && !$dar_review_admin) {

	$__darCanon = "";

	foreach ($assessmentsSelectable as $row) {

		$k = trim((string)$row["Key"]);

		if ($k !== "" && ($k === $selectedKey || strcasecmp($k, $selectedKey) === 0)) {

			$__darCanon = $k;

			break;

		}

	}

	$selectedKey = $__darCanon;

	if ($selectedKey === "" && count($assessmentsSelectable) > 0) {

		$selectedKey = (string)$assessmentsSelectable[0]["Key"];

	}

}

$selectedAssessment = null;

foreach ($assessments as $row) {

	$rk = trim((string)$row["Key"]);

	if ($rk !== "" && ($rk === $selectedKey || strcasecmp($rk, $selectedKey) === 0)) {

		$selectedAssessment = $row;

		$selectedKey = $rk;

		break;

	}

}

if (!is_array($selectedAssessment)) {

	$selectedAssessment = array_fill_keys($assessmentHeaders, "");

}



$message = "";

$error = "";

$questionFile = dar_clean_relative_file((string)$selectedAssessment["FragenDatei"], "csv");

$questionsPath = $questionFile !== "" ? ($QuizLoc . $questionFile) : "";

$questionHeaders = array();

$questions = array();

if ($questionsPath !== "" && is_file($questionsPath)) {

	list($questionHeaders, $questions) = dar_read_csv($questionsPath);

	$questionHeaders = dar_ensure_headers($questionHeaders);

	foreach ($questions as &$qRow) {

		foreach ($questionHeaders as $h) {

			if (!isset($qRow[$h])) $qRow[$h] = "";

		}

		if (trim((string)($qRow["Drone"] ?? "")) === "") {

			if (isset($qRow["Drohne"]) && trim((string)$qRow["Drohne"]) !== "") {

				$qRow["Drone"] = trim((string)$qRow["Drohne"]);

			} elseif (isset($qRow["Drohnentyp"]) && trim((string)$qRow["Drohnentyp"]) !== "") {

				$qRow["Drone"] = trim((string)$qRow["Drohnentyp"]);

			}

		}

		if (trim((string)$qRow["Status"]) === "") $qRow["Status"] = "aktiv";

		if (trim((string)$qRow["Rueckmeldung"]) === "") $qRow["Rueckmeldung"] = "offen";

	}

	unset($qRow);

}



// Build a snapshot hash per row for change-detection (sent to client; compared on save).
// Key: original row index, value: md5 of the review-relevant columns.
$_dar_review_cols = array("Prioritaet", "Status", "Rueckmeldung", "RueckmeldungText",
	"Frage", "Loesungsauswahl", "Loesung", "Loesungstext", "Antwort");
$darSnapshotHashes = array();
foreach ($questions as $_dsi => $_dsRow) {
	$_dsStr = "";
	foreach ($_dar_review_cols as $_dsc) {
		$_dsStr .= "\x00" . (isset($_dsRow[$_dsc]) ? (string)$_dsRow[$_dsc] : "");
	}
	$darSnapshotHashes[$_dsi] = md5($_dsStr);
}
unset($_dsi, $_dsRow, $_dsStr, $_dsc);

$darIsAutosaveAjax = ($_SERVER["REQUEST_METHOD"] === "POST"
	&& (string)($_POST["admin_action"] ?? "") === "save_review"
	&& (string)($_POST["dar_autosave_ajax"] ?? "") === "1");

if ($_SERVER["REQUEST_METHOD"] === "POST" && (string)($_POST["admin_action"] ?? "") === "save_review") {

	if ($questionsPath === "" || !is_file($questionsPath)) {

		$error = "Keine Fragen-CSV gefunden.";

	} else {

		$mergeOk = false;
		$submittedByOrig = array();

		// Decode which rows the client actually changed (indices into the submitted row array).
		$darChangedRaw = isset($_POST["dar_q_changed_indices"]) ? trim((string)$_POST["dar_q_changed_indices"]) : "";
		$darChangedSet = array();
		if ($darChangedRaw !== "") {
			$dec = json_decode($darChangedRaw, true);
			if (is_array($dec)) {
				foreach ($dec as $ci) {
					$darChangedSet[(int)$ci] = true;
				}
			}
		}

		$darOrig = function_exists("rkrk_assess_decode_dar_q_row_orig_from_post")
			? rkrk_assess_decode_dar_q_row_orig_from_post($_POST)
			: array();

		$raw = rkrk_assess_post_decode_questions($questionHeaders, $_POST);

		// Collect submitted rows indexed by original CSV row index.
		$maxIdx = -1;
		foreach ($raw as $col => $colVals) {
			if (!is_array($colVals)) continue;
			foreach (array_keys($colVals) as $k) {
				$ik = (int)$k;
				if ($ik > $maxIdx) $maxIdx = $ik;
			}
		}

		// Map: origIdx => submitted row (only for changed rows).
		for ($i = 0; $i <= $maxIdx; $i++) {
			// Skip rows not marked as changed by the client (unless no change-set sent = legacy fallback).
			if (!empty($darChangedSet) && !isset($darChangedSet[$i])) continue;

			$line = array();
			foreach ($questionHeaders as $h) {
				$line[$h] = isset($raw[$h][$i]) ? trim((string)$raw[$h][$i]) : "";
			}
			if (trim((string)$line["Frage"]) === "" && trim((string)$line["Loesung"]) === "") continue;

			if (trim((string)$line["Status"]) === "")       $line["Status"] = "aktiv";
			if (trim((string)$line["Rueckmeldung"]) === "") $line["Rueckmeldung"] = "offen";
			if (trim((string)$line["Prioritaet"]) === "")   $line["Prioritaet"] = "0";
			if (trim((string)$line["Aktiv"]) === "")        $line["Aktiv"] = "Ja";
			if (trim((string)$line["Locked"]) === "")       $line["Locked"] = "Nein";

			$origI = isset($darOrig[$i]) ? (int)$darOrig[$i] : $i;
			$submittedByOrig[$origI] = array("line" => $line, "submitIdx" => $i);
		}

		// Row-level merge: open CSV with exclusive lock, apply only changed rows, rewrite.
		$lockPath = $questionsPath . ".lock";
		$lockFh = @fopen($lockPath, "cb");
		$mergeError = "";

		if ($lockFh === false) {
			$mergeError = "Lock-Datei konnte nicht erstellt werden.";
		} elseif (!flock($lockFh, LOCK_EX)) {
			fclose($lockFh);
			$mergeError = "Datei ist gerade gesperrt. Bitte kurz warten und erneut versuchen.";
		} else {
			// Re-read the CSV fresh from disk while holding the lock.
			list($freshHeaders, $freshRows) = dar_read_csv($questionsPath);
			$freshHeaders = dar_ensure_headers($freshHeaders);
			foreach ($freshRows as &$fr) {
				foreach ($freshHeaders as $fh_) {
					if (!isset($fr[$fh_])) $fr[$fh_] = "";
				}
			}
			unset($fr);

			// Apply submitted changes onto the freshly-read rows.
			$mergedRows = array();
			foreach ($freshRows as $fi => $freshRow) {
				if (isset($submittedByOrig[$fi])) {
					$line = $submittedByOrig[$fi]["line"];
					// Preserve columns this page does not manage (not in submitted hidden fields).
					foreach ($freshHeaders as $fh_) {
						if (!array_key_exists($fh_, $line)) {
							$line[$fh_] = isset($freshRow[$fh_]) ? (string)$freshRow[$fh_] : "";
						}
					}
					if (function_exists("rkrk_assess_apply_aenderung_stempel")) {
						rkrk_assess_apply_aenderung_stempel($freshRow, $line, null);
					}
					$line["Nr"] = (string)($fi + 1);
					$mergedRows[] = $line;
				} else {
					$mergedRows[] = $freshRow;
				}
			}

			// Renumber.
			foreach ($mergedRows as $mi => &$mr) {
				$mr["Nr"] = (string)($mi + 1);
			}
			unset($mr);

			if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
				rkrk_assess_fragenkatalog_before_overwrite($questionsPath, $QuizLoc);
			}

			if (dar_write_csv($questionsPath, $freshHeaders, $mergedRows)) {
				$mergeOk = true;
				$questions = $mergedRows;
				$questionHeaders = $freshHeaders;
				// Rebuild snapshot hashes for the re-rendered page.
				$darSnapshotHashes = array();
				foreach ($questions as $_dsi2 => $_dsRow2) {
					$_dsStr2 = "";
					foreach ($_dar_review_cols as $_dsc2) {
						$_dsStr2 .= "\x00" . (isset($_dsRow2[$_dsc2]) ? (string)$_dsRow2[$_dsc2] : "");
					}
					$darSnapshotHashes[$_dsi2] = md5($_dsStr2);
				}
				unset($_dsi2, $_dsRow2, $_dsStr2, $_dsc2);
			} else {
				$mergeError = "Review konnte nicht gespeichert werden.";
			}

			flock($lockFh, LOCK_UN);
			fclose($lockFh);
			@unlink($lockPath);
		}

		if ($mergeOk) {
			$changedCount = count($submittedByOrig);
			if ($darIsAutosaveAjax) {
				if (function_exists("rkrk_assess_fragenkatalog_archive_autosave")) {
					rkrk_assess_fragenkatalog_archive_autosave($questionsPath, $QuizLoc);
				}
				$message = "Autosave (" . $changedCount . " Zeile" . ($changedCount === 1 ? "" : "n") . " aktualisiert).";
			} else {
				$message = "Review gespeichert (" . $changedCount . " Zeile" . ($changedCount === 1 ? "" : "n") . " aktualisiert).";
			}
		} else {
			$error = $mergeError !== "" ? $mergeError : "Review konnte nicht gespeichert werden.";
		}

	}

	if ($darIsAutosaveAjax) {
		if (!headers_sent()) {
			header("Content-Type: application/json; charset=UTF-8");
		}
		if ($error !== "") {
			echo json_encode(array("ok" => false, "error" => $error), JSON_UNESCAPED_UNICODE);
		} elseif ($mergeOk && count($submittedByOrig) === 0) {
			echo json_encode(array("ok" => true, "skipped" => true, "message" => "Keine Änderungen."), JSON_UNESCAPED_UNICODE);
		} else {
			echo json_encode(array(
				"ok" => true,
				"message" => $message,
				"changed_count" => isset($changedCount) ? (int)$changedCount : 0,
				"snapshot_hashes" => $darSnapshotHashes,
				"saved_at" => date("c"),
			), JSON_UNESCAPED_UNICODE);
		}
		exit;
	}

}



$imageBaseWeb = function_exists('rkrk_dga_rh_web_image_base') ? rkrk_dga_rh_web_image_base() : $QuizIconLoc;

$driveLocCurrent = function_exists("rkrk_assess_effective_drive_location")
	? rkrk_assess_effective_drive_location($selectedAssessment, $questions)
	: (function_exists("rkrk_assess_clean_drive_location_rel")
		? rkrk_assess_clean_drive_location_rel((string)($selectedAssessment["DriveLocation"] ?? ""))
		: dar_clean_relative_folder((string)($selectedAssessment["DriveLocation"] ?? "")));

$prioOptions = array("0", "1", "2", "3", "4");
$priorityHelp = "Prioritaet 1 = optional/geringe Pruefungsrelevanz, 2 = relevant, 3 = wichtig, 4 = kritisch und sollte moeglichst immer abgefragt werden.";

$statusOptions = function_exists("rkrk_assess_question_status_options") ? rkrk_assess_question_status_options() : array("neu", "prüfen", "geprüft", "aktiv", "inaktiv");

if (!in_array("aktiv", $statusOptions, true)) $statusOptions[] = "aktiv";

$reviewOptions = array("offen", "wichtig", "gut", "überarbeiten", "nicht relevant");

$darFilterText = isset($_REQUEST["dar_filter_text"]) ? (string)$_REQUEST["dar_filter_text"] : "";

$darFilterBereich = isset($_REQUEST["dar_filter_bereich"]) ? trim((string)$_REQUEST["dar_filter_bereich"]) : "";

$darFilterPrio = isset($_REQUEST["dar_filter_prio"]) ? trim((string)$_REQUEST["dar_filter_prio"]) : "";

$darFilterStatus = isset($_REQUEST["dar_filter_status"]) ? trim((string)$_REQUEST["dar_filter_status"]) : "";

$darFilterReview = isset($_REQUEST["dar_filter_review"]) ? trim((string)$_REQUEST["dar_filter_review"]) : "";

$darSchulungOffen = true;
if (isset($_REQUEST["dar_schulung_offen"])) {
	$darSchulungOffen = trim((string) $_REQUEST["dar_schulung_offen"]) !== "0";
}

$dar_is_admin_nav = $dar_review_admin;
$dar_is_instr_nav = !empty($onInstr);

// --- Anzeige-Wahrscheinlichkeit (gleiche Auswahlformel wie rkrk_digitalassess.php) ---
$darProbPickN = isset($_REQUEST["dar_prob_pickn"])
	? max(0, (int) $_REQUEST["dar_prob_pickn"])
	: max(0, (int) ($selectedAssessment["AnzQuestions"] ?? 0));
if ($darProbPickN < 1) {
	$darProbPickN = 30;
}
$darProbDrone = isset($_REQUEST["dar_prob_drone"]) ? trim((string) $_REQUEST["dar_prob_drone"]) : "Alle";
$darProbIterations = 5000;
$darProbEligible = function_exists("rkrk_dga_build_quiz_eligible_pool")
	? rkrk_dga_build_quiz_eligible_pool($questions, (string) $setting, $darProbDrone)
	: array();
$darProbPickEffective = count($darProbEligible) > 0
	? min(count($darProbEligible), $darProbPickN)
	: 0;
$darProbMap = array();
if ($darProbPickEffective > 0 && function_exists("rkrk_dga_simulate_selection_probabilities")) {
	$darProbMap = rkrk_dga_simulate_selection_probabilities($darProbEligible, $darProbPickEffective, $darProbIterations);
}
$darProbDroneOptions = array("Alle");
foreach ($questions as $qRow) {
	if (!is_array($qRow)) {
		continue;
	}
	$d = function_exists("rkrk_assess_normalize_drone")
		? rkrk_assess_normalize_drone(isset($qRow["Drone"]) ? $qRow["Drone"] : "")
		: trim((string) ($qRow["Drone"] ?? ""));
	if ($d !== "" && $d !== "Alle" && !in_array($d, $darProbDroneOptions, true)) {
		$darProbDroneOptions[] = $d;
	}
}
sort($darProbDroneOptions, SORT_STRING);
if ($darProbDrone !== "" && $darProbDrone !== "Alle" && !in_array($darProbDrone, $darProbDroneOptions, true)) {
	$darProbDroneOptions[] = $darProbDrone;
}

?>

<style>

.dar-wrap{max-width:1180px;margin:0 auto;padding:12px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#1f2937}

.dar-card{background:#fff;border:1px solid #d8dfd6;border-radius:12px;padding:12px;margin:10px 0;box-shadow:0 1px 2px rgba(15,23,42,.05)}

.dar-top{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between}

.dar-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid #7a8f78;border-radius:8px;background:#f4f7f3;color:#1f2937;padding:7px 11px;text-decoration:none;cursor:pointer;font-weight:700}

.dar-btn:hover{background:#e8f0e6}

.dar-msg{padding:8px 10px;border-radius:8px;margin:8px 0}.dar-ok{background:#e8f4e5;border:1px solid #9ab08f}.dar-err{background:#fde9e9;border:1px solid #e3abab}

.dar-list{display:grid;gap:12px}.dar-q{border:1px solid #d8dfd6;border-radius:12px;background:#fff;padding:12px}.dar-q.muted{opacity:.62;background:#f8faf8}

.dar-q[hidden]{display:none!important}.dar-q.dragging{opacity:.45}.dar-q.drag-over{outline:2px dashed #7a8f78;outline-offset:3px}

.dar-head{display:flex;gap:10px;align-items:flex-start;justify-content:space-between}.dar-head-btns{display:flex;gap:6px;align-items:center;flex-shrink:0}.dar-title{margin:0;font-size:17px;line-height:1.35}
.dar-q-del-btn{display:inline-flex;align-items:center;justify-content:center;border:0;background:transparent;color:#b42318;cursor:pointer;line-height:1;padding:6px 8px;border-radius:8px}.dar-q-del-btn:hover{background:#fde8e8;color:#7a1111}.dar-q-del-btn svg{width:18px;height:18px;display:block;flex-shrink:0}.dar-btn svg.chk-ico{width:16px;height:16px;flex-shrink:0}

.dar-nr-handle{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:28px;margin-right:6px;border-radius:8px;background:#eef6ee;border:1px solid #b9c8b5;cursor:grab;font-weight:900;user-select:none}

.dar-nr-handle:active{cursor:grabbing}

.dar-meta{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0;font-size:12px;color:#475569}.dar-pill{padding:2px 8px;border-radius:999px;background:#eef6ee;border:1px solid #d8dfd6}
.dar-drone-group{margin:0 0 8px;padding:6px 8px;border-radius:6px;background:#eef5ff;color:#1e3a8a;font-weight:700}
.dar-drone-badge{display:inline-block;margin:0 0 8px;padding:2px 8px;border-radius:999px;background:#e5f3e8;color:#166534;font-size:12px;font-weight:700}

.dar-options{margin:8px 0 0 22px;padding:0}.dar-options li{margin:3px 0}.dar-correct{font-weight:800;color:#0f5132}

.dar-sol-pair{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:8px 0}
@media (max-width:720px){.dar-sol-pair{grid-template-columns:1fr}}
.dar-loesungstext-block,.dar-antwort-block{margin:0;padding:8px;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;font-size:13px;line-height:1.45}
.dar-loesungstext-block-label,.dar-antwort-block-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin:0 0 4px}
.dar-loesungstext-preview,.dar-antwort-preview{white-space:pre-wrap;color:#1e3a5f}
.dar-loesungstext-block--empty .dar-loesungstext-preview,.dar-antwort-block--empty .dar-antwort-preview{color:#94a3b8;font-style:italic}
.dar-loesungstext-block.dar-loesungstext-changed,.dar-antwort-block.dar-antwort-changed{background:#fff7ed;border-color:#fdba74;box-shadow:inset 0 0 0 1px #fdba74}

.dar-images{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}.dar-image{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#334155;text-decoration:none}

.dar-image img{width:54px;height:54px;object-fit:cover;border-radius:7px;border:1px solid #d1d5db;background:#fff}

.dar-controls{display:grid;grid-template-columns:repeat(3,minmax(140px,1fr));gap:8px;align-items:end;margin-top:10px}.dar-controls label{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:800;color:#334155}
.dar-controls textarea.dar-feedback,.dar-controls textarea.dar-loesungstext,.dar-controls textarea.dar-antwort{font-weight:400}
.dar-controls .dar-sol-edit-pair{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:8px}
@media (max-width:720px){.dar-controls .dar-sol-edit-pair{grid-template-columns:1fr}}
.dar-controls .dar-sol-edit-pair label{display:flex;flex-direction:column;gap:4px}
.dar-controls .dar-sol-edit-pair textarea{grid-column:auto;min-height:76px}

.dar-controls textarea{grid-column:1/-1;min-height:76px;resize:vertical}.dar-controls select,.dar-controls textarea,.dar-dialog input,.dar-dialog textarea{border:1px solid #cbd5e1;border-radius:8px;padding:7px;box-sizing:border-box}

.dar-filters{display:grid;grid-template-columns:minmax(220px,2fr) repeat(4,minmax(140px,1fr));gap:8px;align-items:end;margin-top:10px}.dar-filters label{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:800;color:#334155}.dar-filters input,.dar-filters select{border:1px solid #cbd5e1;border-radius:8px;padding:7px;box-sizing:border-box}
.dar-filters input:focus,.dar-filters select:focus,#dar-load-form select#dar-selected-quiz:focus{outline:none;border-color:#94a3b8;box-shadow:0 0 0 2px rgba(148,163,184,.25)}

#dar-load-form select#dar-selected-quiz{flex:1 1 min(560px,100%);min-width:min(100%,220px);max-width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:7px;box-sizing:border-box;font:inherit;font-size:14px;line-height:1.3;background:#fff;color:#1f2937}

.dar-actions{display:flex;gap:8px;align-items:center;margin-top:8px}.dar-save-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}.dar-autosave-toggle{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#334155;cursor:pointer;user-select:none}.dar-autosave-toggle input{width:16px;height:16px;margin:0;cursor:pointer}.dar-autosave-status{font-size:12px;color:#64748b;min-height:1.2em}.dar-dialog{border:1px solid #94a3b8;border-radius:12px;padding:0;max-width:min(900px,92vw);width:900px}.dar-dialog::backdrop{background:rgba(15,23,42,.45)}

.dar-training{overflow:hidden;scroll-margin-top:12px}.dar-training summary{cursor:pointer;font-weight:800}.dar-training-frame{width:100%;height:min(78vh,920px);min-height:70vh;border:1px solid #d8dfd6;border-radius:10px;background:#fff;margin-top:10px;display:block}

.dar-dialog-body{padding:14px;background:#fff}.dar-dialog-grid{display:grid;gap:10px}.dar-dialog-grid label{display:grid;gap:4px;font-size:12px;font-weight:800}.dar-dialog-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}

.dar-prob-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end;margin-top:10px}
.dar-prob-form label{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:800;color:#334155}
.dar-prob-form input,.dar-prob-form select{border:1px solid #cbd5e1;border-radius:8px;padding:7px;box-sizing:border-box}
.dar-prob-note{font-size:12px;color:#475569;margin:10px 0 0;line-height:1.45}
.dar-prob-table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
.dar-prob-table th,.dar-prob-table td{border:1px solid #e2e8f0;padding:6px 8px;text-align:left;vertical-align:middle}
.dar-prob-table th{background:#f8fafc;font-size:12px}
.dar-prob-table tr.dar-prob-muted{opacity:.55}
.dar-prob-bar-wrap{min-width:120px;background:#eef2f7;border-radius:6px;height:10px;overflow:hidden}
.dar-prob-bar{height:100%;background:linear-gradient(90deg,#7a9e78,#3d6b3a);border-radius:6px}
.dar-prob-pct{font-variant-numeric:tabular-nums;font-weight:700;white-space:nowrap}
.dar-prob-badge{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:#eef5ff;color:#1e40af;font-size:11px;font-weight:700;vertical-align:middle}
.dar-prob-badge.dar-prob-high{background:#dcfce7;color:#166534}
.dar-prob-badge.dar-prob-low{background:#f1f5f9;color:#64748b}

@media(max-width:900px){.dar-controls,.dar-filters{grid-template-columns:1fr}.dar-head{display:block}}

/* Portal-Navigation volle Breite, über Review-Karten */
#rkrk-portal-nav-slot{max-width:1180px;margin:0 auto 14px;padding:0 12px;box-sizing:border-box;position:relative;z-index:200;}

</style>

<?php
if (function_exists("rkrk_assess_portal_nav_echo")) {
	ob_start();
	rkrk_assess_portal_nav_echo(array(
		"btn_class" => "dar-btn",
		"schulung_mode" => false,
		"is_admin" => $dar_is_admin_nav,
		"is_instr" => $dar_is_instr_nav,
		"selected_quiz" => (string)$selectedKey,
	));
	$__rkrk_portal_out = ob_get_clean();
	if ($__rkrk_portal_out !== "") {
		echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $__rkrk_portal_out . "</div>\n";
	}
}
?>

<div class="dar-wrap">

	<div class="dar-card">

		<div class="dar-top">

			<div>

				<h2 style="margin:0 0 4px;">Fragen Review</h2>

				<div style="font-size:13px;color:#475569;"><?php echo dar_h((string)$selectedAssessment["Titel"]); ?> · <?php echo dar_h($questionFile); ?> · <?php echo count($questions); ?> Fragen</div>

			</div>
			<?php if ($dar_review_admin) { ?>
			<a class="dar-btn" style="text-decoration:none;" href="<?php echo dar_h(rkrk_assess_append_setting_href("rkrk_digitalassess_review_merge.php?ADMIN&base_quiz=" . rawurlencode((string)$selectedKey))); ?>">Review zusammenführen</a>
			<?php } ?>

		</div>

		<form method="get" class="dar-top" id="dar-load-form" style="justify-content:flex-start;margin-top:10px;">

			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>

			<select name="selected_quiz" id="dar-selected-quiz">

				<?php foreach ($assessmentsSelectable as $aRow) {

					$key = (string)$aRow["Key"];

					if ($key === "") continue;

					$__darSel = ($key === $selectedKey || strcasecmp($key, $selectedKey) === 0);

				?>

					<option value="<?php echo dar_h($key); ?>"<?php echo ($__darSel ? " selected" : ""); ?>><?php echo dar_h($key . " - " . $aRow["Titel"]); ?></option>

				<?php } ?>

			</select>

			<span style="font-size:13px;color:#475569;">Auswahl lädt automatisch.</span>

		</form>

		<?php if ($message !== "") { ?><div class="dar-msg dar-ok"><?php echo dar_h($message); ?></div><?php } ?>

		<?php if ($error !== "") { ?><div class="dar-msg dar-err"><?php echo dar_h($error); ?></div><?php } ?>

		<div class="dar-filters" id="dar-filters" data-initial-bereich="<?php echo dar_h($darFilterBereich); ?>">

			<label>Suche

				<input type="search" id="dar-filter-text" value="<?php echo dar_h($darFilterText); ?>" placeholder="Frage, Antwort, Lösungstext, Rückmeldung..." autocomplete="off">

			</label>

			<label>Bereich

				<select id="dar-filter-bereich"><option value="">Alle</option></select>

			</label>

			<label title="<?php echo dar_h($priorityHelp); ?>">Priorität
				<select id="dar-filter-prio" title="<?php echo dar_h($priorityHelp); ?>"><option value="">Alle</option><?php foreach ($prioOptions as $opt) { ?><option value="<?php echo dar_h($opt); ?>"<?php echo ($darFilterPrio === (string)$opt ? " selected" : ""); ?>><?php echo dar_h($opt); ?></option><?php } ?></select>

			</label>

			<label>Status

				<select id="dar-filter-status"><option value="">Alle</option><?php foreach ($statusOptions as $opt) { ?><option value="<?php echo dar_h($opt); ?>"<?php echo ($darFilterStatus === (string)$opt ? " selected" : ""); ?>><?php echo dar_h($opt); ?></option><?php } ?></select>

			</label>

			<label>Rückmeldung

				<select id="dar-filter-review"><option value="">Alle</option><?php foreach ($reviewOptions as $opt) { ?><option value="<?php echo dar_h($opt); ?>"<?php echo ($darFilterReview === (string)$opt ? " selected" : ""); ?>><?php echo dar_h($opt); ?></option><?php } ?></select>

			</label>

		</div>

		<div class="dar-actions">

			<button class="dar-btn" type="button" id="dar-filter-reset">Filter zurücksetzen</button>

			<span id="dar-filter-count" style="font-size:13px;color:#475569;"></span>

		</div>

	</div>

	<?php if (trim((string)$selectedAssessment["LernDatei"]) !== "") { ?>

		<details class="dar-card dar-training"<?php echo $darSchulungOffen ? " open" : ""; ?>>

			<summary>Schulung anzeigen: <?php echo dar_h((string)$selectedAssessment["LernDatei"]); ?></summary>

			<iframe id="dar-training-iframe" class="dar-training-frame" src="<?php echo dar_h(rkrk_assess_append_setting_href("rkrk_digitalassess_learn.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("quiz" => $selectedKey))))); ?>" title="Schulung <?php echo dar_h((string)$selectedAssessment["Titel"]); ?>"></iframe>

		</details>

	<?php } ?>

	<?php if (count($questions) === 0) { ?>

		<div class="dar-card">Keine Fragen gefunden.</div>

	<?php } else { ?>

	<form method="post" id="dar-form">

		<input type="hidden" name="selected_quiz" value="<?php echo dar_h($selectedKey); ?>">

		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>

		<input type="hidden" name="admin_action" value="save_review">

		<input type="hidden" name="dar_q_json" id="dar-q-json" value="">

		<input type="hidden" name="dar_q_row_orig" id="dar-q-row-orig" value="">

		<input type="hidden" name="dar_q_changed_indices" id="dar-q-changed-indices" value="">

		<input type="hidden" id="dar-q-snapshot" value="<?php echo dar_h(json_encode($darSnapshotHashes, JSON_UNESCAPED_UNICODE)); ?>">

		<div class="dar-post-state" aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">
			<input type="hidden" name="dar_filter_text" id="dar-h-filter-text" value="<?php echo dar_h($darFilterText); ?>">
			<input type="hidden" name="dar_filter_bereich" id="dar-h-filter-bereich" value="<?php echo dar_h($darFilterBereich); ?>">
			<input type="hidden" name="dar_filter_prio" id="dar-h-filter-prio" value="<?php echo dar_h($darFilterPrio); ?>">
			<input type="hidden" name="dar_filter_status" id="dar-h-filter-status" value="<?php echo dar_h($darFilterStatus); ?>">
			<input type="hidden" name="dar_filter_review" id="dar-h-filter-review" value="<?php echo dar_h($darFilterReview); ?>">
			<input type="hidden" name="dar_schulung_offen" id="dar-h-schulung-offen" value="<?php echo $darSchulungOffen ? "1" : "0"; ?>">
		</div>

		<div class="dar-card dar-top">

			<div style="font-size:13px;color:#475569;">Priorität, Status, Rückmeldung und Textänderungen werden in der CSV gespeichert. Frage- und Lösungsbilder erscheinen als kleine Vorschau.</div>

			<div class="dar-save-row">
				<button class="dar-btn" type="submit">Review speichern</button>
				<label class="dar-autosave-toggle" title="Speichert bei Aktivität automatisch jede Minute und legt Archiv-Versionen an">
					<input type="checkbox" id="dar-autosave-switch" checked>
					<span>Autospeichern</span>
				</label>
				<span class="dar-autosave-status" id="dar-autosave-status" aria-live="polite"></span>
			</div>

		</div>

		<div class="dar-list">
		<?php $lastDroneGroup = null; ?>

		<?php foreach ($questions as $idx => $qRow) {

			$frageDisplay = trim((string)($qRow["Frage"] ?? ""));

			if ($frageDisplay === "") $frageDisplay = "(ohne Fragentext)";

			$qNr = trim((string)($qRow["Nr"] ?? "")) !== "" ? (string)$qRow["Nr"] : (string)($idx + 1);

			$opts = array_values(array_filter(array_map("trim", explode("|", (string)($qRow["Loesungsauswahl"] ?? ""))), function($v){ return $v !== ""; }));

			$sol = trim((string)($qRow["Loesung"] ?? ""));

			$lookup = array();

			foreach (array_filter(array_map("trim", explode("|", $sol))) as $tok) $lookup[strtolower($tok)] = true;

			$frageBild = dar_question_image_rel((string)($qRow["Frage-Bild"] ?? ""), $driveLocCurrent);

			$loesungsBild = dar_question_image_rel((string)($qRow["Loesungs-Bild"] ?? ""), $driveLocCurrent);

			$reviewVal = trim((string)($qRow["Rueckmeldung"] ?? "")); if ($reviewVal === "") $reviewVal = "offen";
			$droneType = trim((string)($qRow["Drone"] ?? ""));
			$droneGroup = $droneType !== "" ? $droneType : "Allgemein";
			$loesungstextDisplay = trim((string)($qRow["Loesungstext"] ?? ""));
			$antwortDisplay = trim((string)($qRow["Antwort"] ?? ""));

		?>

		<?php if ($droneGroup !== $lastDroneGroup) { $lastDroneGroup = $droneGroup; ?><div class="dar-drone-group">Drohnentyp: <?php echo dar_h($droneGroup); ?></div><?php } ?>
		<div class="dar-q<?php echo dar_question_live($qRow) ? "" : " muted"; ?>" data-card="<?php echo (int)$idx; ?>" data-orig-idx="<?php echo (int)$idx; ?>">

			<?php foreach ($questionHeaders as $h) {

				$v = isset($qRow[$h]) ? (string)$qRow[$h] : "";

				if ($h === "Nr") $v = (string)($idx + 1);

				if ($h === "Status" && trim($v) === "") $v = "aktiv";

				if ($h === "Rueckmeldung" && trim($v) === "") $v = "offen";

				echo "<input type='hidden' data-field='" . dar_h($h) . "' value='" . dar_h($v) . "'>";

			} ?>

			<?php if ($droneType !== "") { ?><div class="dar-drone-badge"><?php echo dar_h($droneType); ?></div><?php } ?>
			<div class="dar-head">

				<h3 class="dar-title"><span class="dar-nr-handle" draggable="true" title="Zum Sortieren ziehen"><span class="dar-nr"><?php echo dar_h($qNr); ?></span></span><span class="dar-frage"><?php echo dar_h($frageDisplay); ?></span></h3>

				<div class="dar-head-btns">
					<button class="dar-btn dar-edit" type="button">bearbeiten</button>
					<?php if ($dar_is_admin_nav) { ?>
					<button type="button" class="dar-q-del-btn" title="Frage aus der CSV entfernen (danach «Review speichern»)" aria-label="Frage löschen"><?php echo rkrk_checklist_toolbar_svg_trash(); ?></button>
					<?php } ?>
				</div>

			</div>

			<div class="dar-meta">

				<?php if (trim((string)($qRow["Bereich"] ?? "")) !== "") { ?><span class="dar-pill">Bereich: <?php echo dar_h((string)$qRow["Bereich"]); ?></span><?php } ?>

				<?php if (trim((string)($qRow["Herkunft"] ?? "")) !== "") { ?><span class="dar-pill">Herkunft: <?php echo dar_h((string)$qRow["Herkunft"]); ?></span><?php } ?>

				<span class="dar-pill">Lösung: <span class="dar-sol"><?php echo dar_h($sol); ?></span></span>

			</div>

			<?php if (count($opts) > 0) { ?>

				<ol class="dar-options" type="A">

				<?php foreach ($opts as $optIdx => $optText) {

					$letter = chr(65 + (int)$optIdx);

					$num = (string)($optIdx + 1);

					$isCorrect = isset($lookup[strtolower($letter)]) || isset($lookup[strtolower($num)]) || isset($lookup[strtolower($optText)]);

				?>

					<li<?php echo $isCorrect ? " class='dar-correct'" : ""; ?>><?php echo dar_h($optText); ?></li>

				<?php } ?>

				</ol>

			<?php } ?>

			<div class="dar-sol-pair">
				<div class="dar-loesungstext-block<?php echo $loesungstextDisplay === "" ? " dar-loesungstext-block--empty" : ""; ?>" data-baseline-loesungstext="<?php echo dar_h($loesungstextDisplay); ?>">
					<div class="dar-loesungstext-block-label">Lösungstext</div>
					<div class="dar-loesungstext-preview"><?php echo $loesungstextDisplay !== "" ? dar_h($loesungstextDisplay) : "—"; ?></div>
				</div>
				<div class="dar-antwort-block<?php echo $antwortDisplay === "" ? " dar-antwort-block--empty" : ""; ?>" data-baseline-antwort="<?php echo dar_h($antwortDisplay); ?>">
					<div class="dar-antwort-block-label">Antwort</div>
					<div class="dar-antwort-preview"><?php echo $antwortDisplay !== "" ? dar_h($antwortDisplay) : "—"; ?></div>
				</div>
			</div>

			<div class="dar-images">

				<?php if ($frageBild !== "") {
					$url = function_exists('rkrk_dga_rh_image_url') ? rkrk_dga_rh_image_url($imageBaseWeb, $frageBild) : ($imageBaseWeb . ltrim($frageBild, "/"));
				?>

					<a class="dar-image" target="_blank" rel="noopener" href="<?php echo dar_h($url); ?>"><img src="<?php echo dar_h($url); ?>" alt="Frage-Bild"><span>Frage-Bild</span></a>

				<?php } ?>

				<?php if ($loesungsBild !== "") {
					$url = function_exists('rkrk_dga_rh_image_url') ? rkrk_dga_rh_image_url($imageBaseWeb, $loesungsBild) : ($imageBaseWeb . ltrim($loesungsBild, "/"));
				?>

					<a class="dar-image" target="_blank" rel="noopener" href="<?php echo dar_h($url); ?>"><img src="<?php echo dar_h($url); ?>" alt="Lösungs-Bild"><span>Lösungs-Bild</span></a>

				<?php } ?>

			</div>

			<div class="dar-controls">

				<label title="<?php echo dar_h($priorityHelp); ?>">Priorität<?php echo dar_option_select("", (string)($qRow["Prioritaet"] ?? ""), $prioOptions, "dar-sel-prio"); ?></label>

				<label>Status<?php echo dar_option_select("", (string)($qRow["Status"] ?? "aktiv"), $statusOptions, "dar-sel-status"); ?></label>

				<label>Rückmeldung<?php echo dar_option_select("", $reviewVal, $reviewOptions, "dar-sel-rueck"); ?></label>

				<div class="dar-sol-edit-pair">
					<label>Lösungstext<textarea class="dar-loesungstext" rows="4"><?php echo dar_h((string)($qRow["Loesungstext"] ?? "")); ?></textarea></label>
					<label>Antwort<textarea class="dar-antwort" rows="4"><?php echo dar_h((string)($qRow["Antwort"] ?? "")); ?></textarea></label>
				</div>

				<label>Rückmeldung Text<textarea class="dar-feedback" rows="3"><?php echo dar_h((string)($qRow["RueckmeldungText"] ?? "")); ?></textarea></label>

			</div>

			<div class="dar-actions"><button class="dar-btn dar-clear" type="button" title="Rückmeldungstext leeren" aria-label="Textfeld löschen"><?php echo rkrk_checklist_toolbar_svg_trash(); ?>Textfeld löschen</button></div>

		</div>

		<?php } ?>

		</div>

		<div class="dar-card dar-top">
			<span></span>
			<div class="dar-save-row">
				<button class="dar-btn" type="submit">Review speichern</button>
				<label class="dar-autosave-toggle" for="dar-autosave-switch" title="Speichert bei Aktivität automatisch jede Minute und legt Archiv-Versionen an">
					<span>Autospeichern</span>
				</label>
			</div>
		</div>

	</form>

	<dialog class="dar-dialog" id="dar-dialog">

		<div class="dar-dialog-body">

			<h3 style="margin-top:0;">Frage bearbeiten</h3>

			<div class="dar-dialog-grid">

				<label>Frage<textarea id="dar-edit-frage" rows="4"></textarea></label>

				<label>Antworten (mit | trennen)<textarea id="dar-edit-options" rows="4"></textarea></label>

				<label>Lösung<input id="dar-edit-solution" type="text"></label>

				<label>Lösungstext<textarea id="dar-edit-solution-text" rows="5"></textarea></label>

				<label>Antwort<textarea id="dar-edit-antwort" rows="4"></textarea></label>

			</div>

			<div class="dar-dialog-actions">

				<button class="dar-btn" type="button" id="dar-edit-cancel">Abbrechen</button>

				<button class="dar-btn" type="button" id="dar-edit-apply">Übernehmen</button>

			</div>

		</div>

	</dialog>

	<?php } ?>

</div>

<script>

(function(){

	const form = document.getElementById('dar-form');

	const dialog = document.getElementById('dar-dialog');

	const editFrage = document.getElementById('dar-edit-frage');

	const editOptions = document.getElementById('dar-edit-options');

	const editSolution = document.getElementById('dar-edit-solution');

	const editSolutionText = document.getElementById('dar-edit-solution-text');

	const editAntwort = document.getElementById('dar-edit-antwort');

	let active = null;

	let dragging = null;

	// Dirty tracking: set of card indices (data-card) that the user has changed.
	const dirtyCards = new Set();

	const autosaveSwitch = document.getElementById('dar-autosave-switch');
	const autosaveStatus = document.getElementById('dar-autosave-status');

	let lastActivityAt = 0;
	let autosaveInFlight = false;
	let autosaveTimer = null;
	const AUTOSAVE_INTERVAL_MS = 60000;
	const AUTOSAVE_IDLE_MS = 120000;

	// Snapshot hashes from server (origIdx → md5 of review columns when page was rendered).
	let snapshotHashes = {};
	try {
		const snapEl = document.getElementById('dar-q-snapshot');
		if (snapEl) snapshotHashes = JSON.parse(snapEl.value) || {};
	} catch(e) {}

	// Columns used for the PHP snapshot hash (must match $_dar_review_cols order).
	const SNAP_COLS = ['Prioritaet','Status','Rueckmeldung','RueckmeldungText',
		'Frage','Loesungsauswahl','Loesung','Loesungstext','Antwort'];

	function cardHash(card) {
		// Mirrors the PHP md5 logic: NUL-separated column values.
		const parts = SNAP_COLS.map(c => get(card, c) || '');
		return parts.join('\x00');
	}

	function markDirty(card) {
		const idx = parseInt(card.getAttribute('data-card'), 10);
		if (!isNaN(idx)) dirtyCards.add(idx);
		noteActivity();
	}

	function noteActivity() {
		lastActivityAt = Date.now();
	}

	function autosaveStorageKey() {
		const qEl = document.querySelector('#dar-form input[name="selected_quiz"]');
		return 'dar_review_autosave_' + (qEl && qEl.value ? qEl.value : '_');
	}

	function syncAutosaveSwitches(checked) {
		if (autosaveSwitch) autosaveSwitch.checked = checked;
	}

	function setAutosaveStatus(text) {
		if (autosaveStatus) autosaveStatus.textContent = text || '';
	}

	function formatAutosaveTime(date) {
		try {
			return date.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
		} catch (e) {
			return date.toLocaleTimeString();
		}
	}

	function refreshSnapshotBaselines() {
		if (!form) return;
		dirtyCards.clear();
		form.querySelectorAll('.dar-q').forEach(card => {
			const origIdx = card.getAttribute('data-orig-idx');
			if (origIdx !== null && origIdx !== '') snapshotHashes[String(origIdx)] = cardHash(card);
			const block = card.querySelector('.dar-loesungstext-block');
			if (block) block.setAttribute('data-baseline-loesungstext', String(get(card,'Loesungstext') || '').trim());
			const antBlock = card.querySelector('.dar-antwort-block');
			if (antBlock) antBlock.setAttribute('data-baseline-antwort', String(get(card,'Antwort') || '').trim());
			updateLoesungstextPreview(card);
			updateAntwortPreview(card);
		});
	}

	function prepareFormForSubmit() {
		if (!form) return false;

		syncFilterHiddens();

		reindexCards();

		form.querySelectorAll('.dar-q').forEach(sync);

		const jsonInput = document.getElementById('dar-q-json');

		if (!jsonInput) return false;

		const rows = [];
		const orig = [];

		form.querySelectorAll('.dar-q').forEach((card, idx) => {
			const origIdx = parseInt(card.getAttribute('data-orig-idx') || '-1', 10);
			const serverHash = snapshotHashes[String(origIdx)];
			const currentHash = cardHash(card);
			if (serverHash === undefined || currentHash !== serverHash) {
				dirtyCards.add(idx);
			}
		});

		form.querySelectorAll('.dar-q').forEach((card, idx) => {
			const row = {};
			card.querySelectorAll('input[type="hidden"][data-field]').forEach(inp => {
				const k = inp.getAttribute('data-field');
				if (k) row[k] = inp.value;
			});
			rows.push(row);
			const o = card.getAttribute('data-orig-idx');
			orig.push(o === null || o === '' ? -1 : parseInt(o, 10));
		});

		jsonInput.value = JSON.stringify(rows);

		const origInput = document.getElementById('dar-q-row-orig');
		if (origInput) origInput.value = JSON.stringify(orig);

		const changedInput = document.getElementById('dar-q-changed-indices');
		if (changedInput) changedInput.value = JSON.stringify(Array.from(dirtyCards).sort((a, b) => a - b));

		return dirtyCards.size > 0;
	}

	function saveReviewAjax() {
		if (!form || autosaveInFlight) return Promise.resolve(null);
		if (!prepareFormForSubmit()) return Promise.resolve(null);

		autosaveInFlight = true;
		setAutosaveStatus('Speichert…');

		const fd = new FormData(form);
		fd.set('dar_autosave_ajax', '1');

		return fetch(form.getAttribute('action') || window.location.href, {
			method: 'POST',
			body: fd,
			credentials: 'same-origin'
		}).then(r => r.json()).then(data => {
			if (data && data.ok) {
				if (data.skipped) {
					setAutosaveStatus('');
					return data;
				}
				refreshSnapshotBaselines();
				const t = data.saved_at ? new Date(data.saved_at) : new Date();
				setAutosaveStatus('Autosave ' + formatAutosaveTime(t));
				return data;
			}
			const err = data && data.error ? data.error : 'Autosave fehlgeschlagen';
			setAutosaveStatus(err);
			return data;
		}).catch(() => {
			setAutosaveStatus('Autosave fehlgeschlagen');
			return null;
		}).finally(() => {
			autosaveInFlight = false;
		});
	}

	function tickAutosave() {
		const enabled = autosaveSwitch && autosaveSwitch.checked;
		if (!enabled || !form) return;
		if (autosaveInFlight) return;
		if (Date.now() - lastActivityAt > AUTOSAVE_IDLE_MS) return;
		if (!prepareFormForSubmit()) return;
		saveReviewAjax();
	}

	function updateAutosaveTimer() {
		if (autosaveTimer) {
			clearInterval(autosaveTimer);
			autosaveTimer = null;
		}
		if (autosaveSwitch && autosaveSwitch.checked) {
			autosaveTimer = window.setInterval(tickAutosave, AUTOSAVE_INTERVAL_MS);
		}
	}

	function bindAutosaveSwitch(el) {
		if (!el) return;
		el.addEventListener('change', () => {
			const checked = !!el.checked;
			syncAutosaveSwitches(checked);
			try { localStorage.setItem(autosaveStorageKey(), checked ? '1' : '0'); } catch (e) {}
			if (!checked) setAutosaveStatus('');
			updateAutosaveTimer();
		});
	}

	function field(card, name){ return card ? card.querySelector('input[type="hidden"][data-field="' + name + '"]') : null; }

	function get(card, name){ const f = field(card, name); return f ? f.value : ''; }

	function set(card, name, value){ const f = field(card, name); if (f) f.value = value == null ? '' : String(value); }

	function lookup(raw){ const o={}; String(raw||'').split('|').map(s=>s.trim()).filter(Boolean).forEach(s=>{ o[s.toLowerCase()]=true; }); return o; }

	function sync(card){

		if (!card) return;

		const p = card.querySelector('select.dar-sel-prio');

		const s = card.querySelector('select.dar-sel-status');

		const r = card.querySelector('select.dar-sel-rueck');

		const t = card.querySelector('.dar-feedback');
		const lt = card.querySelector('.dar-loesungstext');
		const ant = card.querySelector('.dar-antwort');

		if (p) set(card,'Prioritaet',p.value);

		if (s) set(card,'Status',s.value || 'aktiv');

		if (r) set(card,'Rueckmeldung',r.value || 'offen');

		if (lt) set(card,'Loesungstext',lt.value);

		if (ant) set(card,'Antwort',ant.value);

		if (t) set(card,'RueckmeldungText',t.value);

	}

	function reindexCards(){

		if (!form) return;

		Array.from(form.querySelectorAll('.dar-q')).forEach((card, idx) => {

			card.setAttribute('data-card', String(idx));

			const nr = card.querySelector('.dar-nr');

			if (nr) nr.textContent = String(idx + 1);

			set(card, 'Nr', String(idx + 1));

		});

	}

	function updateLoesungstextPreview(card){

		if (!card) return;

		const solText = String(get(card,'Loesungstext') || '').trim();

		const block = card.querySelector('.dar-loesungstext-block');

		const preview = card.querySelector('.dar-loesungstext-preview');

		const ltEl = card.querySelector('.dar-loesungstext');

		if (ltEl && ltEl.value !== get(card,'Loesungstext')) ltEl.value = get(card,'Loesungstext');

		if (preview) preview.textContent = solText || '—';

		if (block) {
			block.classList.toggle('dar-loesungstext-block--empty', !solText);
			const baseline = block.getAttribute('data-baseline-loesungstext') || '';
			block.classList.toggle('dar-loesungstext-changed', solText !== baseline);
		}

	}

	function updateAntwortPreview(card){

		if (!card) return;

		const antText = String(get(card,'Antwort') || '').trim();

		const block = card.querySelector('.dar-antwort-block');

		const preview = card.querySelector('.dar-antwort-preview');

		const antEl = card.querySelector('.dar-antwort');

		if (antEl && antEl.value !== get(card,'Antwort')) antEl.value = get(card,'Antwort');

		if (preview) preview.textContent = antText || '—';

		if (block) {
			block.classList.toggle('dar-antwort-block--empty', !antText);
			const baseline = block.getAttribute('data-baseline-antwort') || '';
			block.classList.toggle('dar-antwort-changed', antText !== baseline);
		}

	}

	function refresh(card){

		if (!card) return;

		const frage = get(card,'Frage'), optsRaw = get(card,'Loesungsauswahl'), sol = get(card,'Loesung'), solText = get(card,'Loesungstext'), antText = get(card,'Antwort');

		const q = card.querySelector('.dar-frage'), solEl = card.querySelector('.dar-sol'), ltEl = card.querySelector('.dar-loesungstext'), antEl = card.querySelector('.dar-antwort');

		if (q) q.textContent = frage;

		if (solEl) solEl.textContent = sol;

		if (ltEl) ltEl.value = solText;

		if (antEl) antEl.value = antText;

		updateLoesungstextPreview(card);
		updateAntwortPreview(card);

		const old = card.querySelector('.dar-options');

		if (old) old.remove();

		const opts = optsRaw.split('|').map(s=>s.trim()).filter(Boolean);

		if (opts.length) {

			const ol = document.createElement('ol');

			ol.className = 'dar-options'; ol.type = 'A';

			const lu = lookup(sol);

			opts.forEach((opt, idx) => {

				const li = document.createElement('li');

				const letter = String.fromCharCode(65 + idx), num = String(idx + 1);

				li.textContent = opt;

				if (lu[letter.toLowerCase()] || lu[num.toLowerCase()] || lu[opt.toLowerCase()]) li.className = 'dar-correct';

				ol.appendChild(li);

			});

			const meta = card.querySelector('.dar-meta');

			if (meta) meta.insertAdjacentElement('afterend', ol);

		}

	}

	if (form) {

		form.querySelectorAll('.dar-q').forEach(card => {

			['dar-sel-prio', 'dar-sel-status', 'dar-sel-rueck'].forEach(cls => {

				const el = card.querySelector('select.' + cls);

				if (el) {
					el.addEventListener('change', () => { sync(card); markDirty(card); });
					el.addEventListener('input',  () => { sync(card); markDirty(card); });
				}

			});

			const feedback = card.querySelector('.dar-feedback');
			const loesungstext = card.querySelector('.dar-loesungstext');
			const antwort = card.querySelector('.dar-antwort');

			if (loesungstext) {
				loesungstext.addEventListener('input',  () => { sync(card); updateLoesungstextPreview(card); markDirty(card); });
				loesungstext.addEventListener('change', () => { sync(card); updateLoesungstextPreview(card); markDirty(card); });
			}

			if (antwort) {
				antwort.addEventListener('input',  () => { sync(card); updateAntwortPreview(card); markDirty(card); });
				antwort.addEventListener('change', () => { sync(card); updateAntwortPreview(card); markDirty(card); });
			}

			if (feedback) {
				feedback.addEventListener('input',  () => { sync(card); markDirty(card); });
				feedback.addEventListener('change', () => { sync(card); markDirty(card); });
			}

			const clear = card.querySelector('.dar-clear');

			if (clear && feedback) clear.addEventListener('click', () => { feedback.value = ''; sync(card); markDirty(card); feedback.focus(); });

			const edit = card.querySelector('.dar-edit');

			if (edit) edit.addEventListener('click', () => {

				active = card;

				if (editFrage) editFrage.value = get(card,'Frage');

				if (editOptions) editOptions.value = get(card,'Loesungsauswahl');

				if (editSolution) editSolution.value = get(card,'Loesung');

				if (editSolutionText) editSolutionText.value = get(card,'Loesungstext');

				if (editAntwort) editAntwort.value = get(card,'Antwort');

				if (dialog && dialog.showModal) dialog.showModal(); else if (dialog) dialog.setAttribute('open','open');

			});

			const delBtn = card.querySelector('.dar-q-del-btn');

			if (delBtn) delBtn.addEventListener('click', () => {

				if (!form) return;

				const n = form.querySelectorAll('.dar-q').length;

				if (n <= 1) { window.alert('Die letzte Frage kann nicht gelöscht werden.'); return; }

				if (!window.confirm('Diese Frage wirklich löschen? Speichern nicht vergessen («Review speichern»).')) return;

				// When a card is deleted the whole list order changes — mark all as dirty so the full reordered set is sent.
				if (form) form.querySelectorAll('.dar-q').forEach(c => markDirty(c));

				card.remove();

				reindexCards();

				applyFilters();

			});

			const handle = card.querySelector('.dar-nr-handle');

			if (handle) {

				handle.addEventListener('dragstart', e => {

					dragging = card;

					card.classList.add('dragging');

					e.dataTransfer.effectAllowed = 'move';

					try { e.dataTransfer.setData('text/plain', get(card, 'Nr')); } catch(err) {}

				});

			handle.addEventListener('dragend', () => {

				card.classList.remove('dragging');

				form.querySelectorAll('.dar-q.drag-over').forEach(el => el.classList.remove('drag-over'));

				dragging = null;

				reindexCards();

				// Drag-drop reorders rows — all rows get new Nr values, mark all dirty.
				if (form) form.querySelectorAll('.dar-q').forEach(c => markDirty(c));

				applyFilters();

			});

			}

			card.addEventListener('dragover', e => {

				if (!dragging || dragging === card) return;

				e.preventDefault();

				card.classList.add('drag-over');

				const list = card.parentElement;

				const rect = card.getBoundingClientRect();

				const before = e.clientY < rect.top + rect.height / 2;

				if (list) list.insertBefore(dragging, before ? card : card.nextSibling);

			});

			card.addEventListener('dragleave', () => card.classList.remove('drag-over'));

			sync(card);

		});

	form.addEventListener('submit', () => {

		syncFilterHiddens();

		try { sessionStorage.setItem(darScrollStorageKey(), String(window.scrollY)); } catch (e) {}

		prepareFormForSubmit();

	});

	}

	try {
		const savedAutosave = localStorage.getItem(autosaveStorageKey());
		syncAutosaveSwitches(savedAutosave !== '0');
	} catch (e) {
		syncAutosaveSwitches(true);
	}

	bindAutosaveSwitch(autosaveSwitch);
	updateAutosaveTimer();

	if (form) {
		form.addEventListener('input', noteActivity, true);
		form.addEventListener('change', noteActivity, true);
	}



	const filterText = document.getElementById('dar-filter-text');

	const filterBereich = document.getElementById('dar-filter-bereich');

	const filterPrio = document.getElementById('dar-filter-prio');

	const filterStatus = document.getElementById('dar-filter-status');

	const filterReview = document.getElementById('dar-filter-review');

	const filterReset = document.getElementById('dar-filter-reset');

	const filterCount = document.getElementById('dar-filter-count');

	const filtersRoot = document.getElementById('dar-filters');

	const loadForm = document.getElementById('dar-load-form');

	const selectedQuiz = document.getElementById('dar-selected-quiz');

	const trainingDetails = document.querySelector('.dar-training');

	function syncFilterHiddens(){

		const pairs = [

			['dar-h-filter-text', filterText],

			['dar-h-filter-bereich', filterBereich],

			['dar-h-filter-prio', filterPrio],

			['dar-h-filter-status', filterStatus],

			['dar-h-filter-review', filterReview]

		];

		for (let i = 0; i < pairs.length; i++) {

			const hid = document.getElementById(pairs[i][0]);

			const vis = pairs[i][1];

			if (hid && vis) hid.value = vis.value;

		}

		const hsch = document.getElementById('dar-h-schulung-offen');

		if (hsch && trainingDetails) hsch.value = trainingDetails.open ? '1' : '0';

	}

	function darScrollStorageKey(){

		const qEl = document.querySelector('#dar-form input[name="selected_quiz"]') || document.getElementById('dar-selected-quiz');

		return 'dar_review_scroll_' + (qEl && qEl.value ? qEl.value : '_');

	}

	function cardText(card){

		return [

			get(card,'Frage'), get(card,'Loesungsauswahl'), get(card,'Loesungstext'), get(card,'Antwort'),

			get(card,'Loesung'), get(card,'Bereich'), get(card,'Herkunft'), get(card,'Drone'),

			get(card,'Rueckmeldung'), get(card,'RueckmeldungText')

		].join(' ').toLowerCase();

	}

	function applyFilters(){

		if (!form) return;

		const q = filterText ? filterText.value.trim().toLowerCase() : '';

		const bereich = filterBereich ? filterBereich.value : '';

		const prio = filterPrio ? filterPrio.value : '';

		const status = filterStatus ? filterStatus.value : '';

		const review = filterReview ? filterReview.value : '';

		let shown = 0, total = 0;

		form.querySelectorAll('.dar-q').forEach(card => {

			total++;

			sync(card);

			let ok = true;

			if (q && cardText(card).indexOf(q) < 0) ok = false;

			if (bereich && get(card,'Bereich') !== bereich) ok = false;

			if (prio && get(card,'Prioritaet') !== prio) ok = false;

			if (status && get(card,'Status') !== status) ok = false;

			if (review && get(card,'Rueckmeldung') !== review) ok = false;

			card.hidden = !ok;

			if (ok) shown++;

		});

		if (filterCount) filterCount.textContent = shown + ' von ' + total + ' Fragen sichtbar';

		syncFilterHiddens();

	}

	function populateBereichFilter(){

		if (!filterBereich || !form) return;

		const initialB = filtersRoot ? (filtersRoot.getAttribute('data-initial-bereich') || '') : '';

		const previous = (filterBereich.value || initialB).trim();

		while (filterBereich.options.length > 1) filterBereich.remove(1);

		const existing = new Set();

		form.querySelectorAll('.dar-q').forEach(card => {

			const b = get(card,'Bereich');

			if (b) existing.add(b);

		});

		Array.from(existing).sort((a,b)=>a.localeCompare(b, 'de')).forEach(b => {

			const opt = document.createElement('option');

			opt.value = b; opt.textContent = b;

			filterBereich.appendChild(opt);

		});

		if (previous && !Array.from(filterBereich.options).some(o => o.value === previous)) {

			const opt = document.createElement('option');

			opt.value = previous; opt.textContent = previous;

			filterBereich.appendChild(opt);

		}

		if (previous) filterBereich.value = previous;

	}

	[filterText, filterBereich, filterPrio, filterStatus, filterReview].forEach(el => {

		if (!el) return;

		el.addEventListener('input', applyFilters);

		el.addEventListener('change', applyFilters);

	});

	if (filterReset) filterReset.addEventListener('click', () => {

		if (filterText) filterText.value = '';

		if (filterBereich) filterBereich.value = '';

		if (filterPrio) filterPrio.value = '';

		if (filterStatus) filterStatus.value = '';

		if (filterReview) filterReview.value = '';

		applyFilters();

	});

	populateBereichFilter();

	applyFilters();

	if (loadForm && selectedQuiz) {

		selectedQuiz.addEventListener('change', () => loadForm.submit());

	}

	function darScrollTrainingDocTop() {
		const iframe = document.getElementById('dar-training-iframe') || document.querySelector('.dar-training-frame');
		if (!trainingDetails || !iframe || !trainingDetails.open) return;
		try { trainingDetails.scrollIntoView({ behavior: 'auto', block: 'start' }); } catch (e) {}
		try {
			const win = iframe.contentWindow;
			const doc = win && win.document;
			if (doc) {
				const marker = doc.getElementById('rkrk-learn-doc-top');
				const first = doc.querySelector('section.lern-sektion, h1, h2, body > *:not(script):not(style)');
				const target = marker || first;
				if (target) {
					try { target.scrollIntoView({ block: 'start', inline: 'nearest' }); } catch (e2) {}
				}
				win.scrollTo(0, 0);
				doc.documentElement.scrollTop = 0;
				if (doc.body) doc.body.scrollTop = 0;
			}
		} catch (e) {}
		const topY = iframe.getBoundingClientRect().top + window.pageYOffset - 12;
		window.scrollTo(0, Math.max(0, topY));
	}

	function darScheduleTrainingTopScroll() {
		darScrollTrainingDocTop();
		[50, 150, 400, 900, 1800].forEach(function (ms) {
			setTimeout(darScrollTrainingDocTop, ms);
		});
	}

	const darTrainFrame = document.getElementById('dar-training-iframe') || document.querySelector('.dar-training-frame');
	if (darTrainFrame) {
		darTrainFrame.addEventListener('load', darScheduleTrainingTopScroll, { once: true });
		try {
			const d = darTrainFrame.contentWindow && darTrainFrame.contentWindow.document;
			if (d && d.readyState === 'complete') {
				darScheduleTrainingTopScroll();
			}
		} catch (e) {}
	}
	if (trainingDetails) {
		trainingDetails.addEventListener('toggle', function () {
			syncFilterHiddens();
			if (trainingDetails.open) darScheduleTrainingTopScroll();
		});
	}
	if (!window.__rkrkLearnScrollMsgReview) {
		window.__rkrkLearnScrollMsgReview = true;
		window.addEventListener('message', function (ev) {
			if (!ev || !ev.data || ev.data.rkrkLearn !== 'scrollTop') return;
			darScheduleTrainingTopScroll();
		});
	}

	(function restoreDarScroll(){

		try {

			const k = darScrollStorageKey();

			const y = sessionStorage.getItem(k);

			if (y !== null) {

				sessionStorage.removeItem(k);

				if (trainingDetails && trainingDetails.open) {
					darScheduleTrainingTopScroll();
				} else {

					const n = parseInt(y, 10);

					if (!isNaN(n)) requestAnimationFrame(function () { window.scrollTo(0, n); });

				}

			} else if (trainingDetails && trainingDetails.open) {

				darScheduleTrainingTopScroll();

			}

		} catch (e) {}

	})();

	const cancel = document.getElementById('dar-edit-cancel');

	const apply = document.getElementById('dar-edit-apply');

	if (cancel && dialog) cancel.addEventListener('click', () => dialog.close ? dialog.close() : dialog.removeAttribute('open'));

		if (apply && dialog) apply.addEventListener('click', () => {

		if (!active) return;

		set(active,'Frage', editFrage ? editFrage.value : '');

		set(active,'Loesungsauswahl', editOptions ? editOptions.value : '');

		set(active,'Loesung', editSolution ? editSolution.value : '');

		set(active,'Loesungstext', editSolutionText ? editSolutionText.value : '');

		set(active,'Antwort', editAntwort ? editAntwort.value : '');

		refresh(active); sync(active); markDirty(active);

		dialog.close ? dialog.close() : dialog.removeAttribute('open');

	});

})();

</script>

</body>

</html>

<?php

exit;

?>

