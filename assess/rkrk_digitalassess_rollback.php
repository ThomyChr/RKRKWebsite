<?php 
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset.'scripts/rkrk_inputforms.php');
include_once($folderoffset.'scripts/rkrk_csvfiles.php');
include_once('rkrk_assess_common.php');

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
}
$pagetitle = "RKRS+ Assessments";
headerpage("$pagetitle", false, $folderoffset);

if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}

function da_h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function da_slug($value) {
	$value = strtolower(trim((string)$value));
	$value = preg_replace('/[^a-z0-9\-_]+/i', '-', $value);
	$value = trim((string)$value, '-_');
	return $value !== "" ? $value : "quiz";
}

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

/** @see rkrk_digitalassess_admin.php — gleiche Logik fuer AssessmentListe-Speichern */
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
	if (preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $value) !== 1) {
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
	if (preg_match('/^[a-zA-Z0-9_\-\/]+$/', $value) !== 1) {
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
	return $rows;
}

function da_write_csv_assoc($path, $headers, $rows) {
	return function_exists("rkrk_assess_write_csv_file")
		? rkrk_assess_write_csv_file($path, $headers, $rows, ";")
		: false;
}

$pageMode = isset($_GET["mode"]) ? (string)$_GET["mode"] : (isset($_POST["mode"]) ? (string)$_POST["mode"] : "");
if ($pageMode === "manage") {
	$assessmentHeaders = array("Nr","Key","Titel","Kurzbeschreibung","Bild","Einfuehrung","Erfuellt","PunkteModus","DriveLocation","LernDatei","FragenDatei","AnzQuestions","Audienz","QuizTime","Quiz","Schulung","AntwortMischen","FragenBildAnzeigen","Aktiv","Locked");
	$questionHeaders = array("Nr","Frage","Loesungsauswahl","Bereich","Frage-Bild","Bildformat","Loesungs-Bild","Loesungstext","Loesung","Antwort","Zeit","Audienz","Aktiv","Locked");
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
			if ($row["Audienz"] === "") $row["Audienz"] = "Alle";
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

				if ($idxBySelection !== null) {
					$existingIdx = $idxBySelection;
					if ($row["Nr"] === "") {
						$row["Nr"] = (string)$assessments[$existingIdx]["Nr"];
					}
					$assessments[$existingIdx] = $row;
				} elseif (isset($assessmentByKey[$row["Key"]])) {
					$existingIdx = (int)$assessmentByKey[$row["Key"]];
					if ($row["Nr"] === "") {
						$row["Nr"] = (string)$assessments[$existingIdx]["Nr"];
					}
					$assessments[$existingIdx] = $row;
				} else {
					$maxNr = 0;
					foreach ($assessments as $oldRow) {
						$n = (int)$oldRow["Nr"];
						if ($n > $maxNr) $maxNr = $n;
					}
					if ($row["Nr"] === "") {
						$row["Nr"] = (string)($maxNr + 1);
					}
					$assessments[] = $row;
				}
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
		} elseif ($action === "delete_assessment") {
			$deleteKey = isset($_POST["delete_key"]) ? trim((string)$_POST["delete_key"]) : "";
			if ($deleteKey !== "") {
				$assessments = array_values(array_filter($assessments, function($row) use ($deleteKey) {
					return trim((string)$row["Key"]) !== $deleteKey;
				}));
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
	if ($selectedKey !== "" && isset($assessmentByKey[$selectedKey])) {
		$selectedAssessment = $assessments[(int)$assessmentByKey[$selectedKey]];
	}
	if (!is_array($selectedAssessment)) {
		$selectedAssessment = array_fill_keys($assessmentHeaders, "");
	}

	$questionsPath = "";
	$questions = array();
	if (trim((string)$selectedAssessment["FragenDatei"]) !== "") {
		$questionsPath = $QuizLoc . $selectedAssessment["FragenDatei"];
		$questions = da_read_csv_assoc($questionsPath, $questionHeaders);
	}

	$learningContent = "";
	$learningPath = "";
	if (trim((string)$selectedAssessment["LernDatei"]) !== "") {
		$learningPath = $QuizLoc . $selectedAssessment["LernDatei"];
		if (file_exists($learningPath)) {
			$learningContent = (string)@file_get_contents($learningPath);
		}
	}

	$_rkrkSnM = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$_rkrkDnM = trim(dirname($_rkrkSnM), '/');
	$_rkrkSegM = ($_rkrkDnM === '') ? array() : explode('/', $_rkrkDnM);
	$_rkrkRiM = false;
	foreach ($_rkrkSegM as $_iM => $_pM) {
		if (strtolower((string)$_pM) === 'rkropen') {
			$_rkrkRiM = (int)$_iM;
			break;
		}
	}
	if ($_rkrkRiM !== false) {
		$manageImageBaseWeb = '/' . implode('/', array_slice($_rkrkSegM, 0, $_rkrkRiM + 1)) . '/rkrk/data/assess/images/';
	} else {
		$manageImageBaseWeb = '/rkropen/rkrk/data/assess/images/';
	}
	unset($_rkrkSnM, $_rkrkDnM, $_rkrkSegM, $_rkrkRiM, $_iM, $_pM);

	$driveLocCurrent = da_clean_relative_folder((string)$selectedAssessment["DriveLocation"]);
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

	if ($_SERVER["REQUEST_METHOD"] === "POST" && $selectedKey !== "" && $selectedAssessment["Key"] === $selectedKey) {
		if ($action === "save_questions") {
			$driveLocForQuestions = da_clean_relative_folder((string)$selectedAssessment["DriveLocation"]);
			$qFile = isset($_POST["question_file"]) ? da_clean_relative_file($_POST["question_file"], "csv") : "";
			if ($qFile === "") {
				$qFile = da_clean_relative_file((string)$selectedAssessment["FragenDatei"], "csv");
			}
			if ($qFile === "") {
				$qFile = da_normalize_key($selectedKey) . ".csv";
			}
			$raw = isset($_POST["q"]) && is_array($_POST["q"]) ? $_POST["q"] : array();
			$newRows = array();
			$rowCount = isset($raw["Frage"]) && is_array($raw["Frage"]) ? count($raw["Frage"]) : 0;
			for ($i = 0; $i < $rowCount; $i++) {
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
				if ($line["Audienz"] === "") $line["Audienz"] = "Alle";
				$line["Frage-Bild"] = da_with_drive_location($line["Frage-Bild"], $driveLocForQuestions);
				$line["Loesungs-Bild"] = da_with_drive_location($line["Loesungs-Bild"], $driveLocForQuestions);
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
		} elseif ($action === "save_learning_html") {
			$learnFile = isset($_POST["learning_file"]) ? da_clean_relative_file($_POST["learning_file"], "html") : "";
			if ($learnFile === "") {
				$learnFile = da_clean_relative_file((string)$selectedAssessment["LernDatei"], "html");
			}
			if ($learnFile === "") {
				$learnFile = da_normalize_key($selectedKey) . ".html";
			}
			$html = isset($_POST["learning_html"]) ? (string)$_POST["learning_html"] : "";
			$targetLearningPath = $QuizLoc . $learnFile;
			if (@file_put_contents($targetLearningPath, $html) === false) {
				$error = "Schulungstext konnte nicht gespeichert werden.";
			} else {
				$message = "Schulungstext gespeichert.";
				$learningPath = $targetLearningPath;
				$learningContent = $html;
				$selectedAssessment["LernDatei"] = $learnFile;
				if (isset($assessmentByKey[$selectedKey])) {
					$assessments[(int)$assessmentByKey[$selectedKey]]["LernDatei"] = $learnFile;
					if (function_exists("rkrk_assess_fragenkatalog_before_overwrite")) {
						rkrk_assess_fragenkatalog_before_overwrite($assessmentPath, $QuizLoc);
					}
					da_write_csv_assoc($assessmentPath, $assessmentHeaders, $assessments);
				}
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
			$driveLoc = da_clean_relative_folder((string)$selectedAssessment["DriveLocation"]);
			if ($driveLoc === "") {
				$error = "Bitte zuerst DriveLocation setzen. Upload geht nur in den +DriveLocation Ordner.";
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
	?>
	<style>
	.da-wrap { width: 100%; max-width: none; margin: 0; padding: 10px; box-sizing: border-box; }
	:root {
		--da-btn-bg: #2f6f2f;
		--da-btn-bg-hover: #275d27;
		--da-btn-danger-bg: #9f3a3a;
		--da-btn-danger-hover: #8a3131;
	}
	.da-grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
	@media (min-width: 1200px) { .da-grid { grid-template-columns: minmax(280px, 30vw) 10px minmax(0, 1fr); align-items: start; } }
	.da-card { background: #fff; border: 1px solid #d9e0d7; border-radius: 10px; padding: 12px; }
	.da-title { margin: 0 0 10px; }
	.da-fields { display: grid; grid-template-columns: 1fr; gap: 8px; }
	.da-fields input, .da-fields textarea, .da-fields select { width: 100%; box-sizing: border-box; padding: 9px; font-size: 14px; }
	.da-table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 13px; table-layout: auto; }
	.da-table th, .da-table td { border: 1px solid #e0e0e0; padding: 6px; vertical-align: top; }
	.da-table th { background: #f4f7f4; }
	.da-table th { position: sticky; top: 0; z-index: 2; }
	.da-table td textarea, .da-table td input { min-width: 130px; }
	.da-table td textarea { min-height: 52px; resize: vertical; }
	.da-table th:first-child, .da-table td:first-child { width: 56px; min-width: 56px; max-width: 56px; }
	.da-table td:first-child input { min-width: 44px; width: 44px; }
	.da-inline { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 8px; }
	.da-btn {
		border: 1px solid rgba(0,0,0,.08);
		border-radius: 6px;
		padding: 8px 12px;
		background: var(--da-btn-bg);
		color: #fff;
		cursor: pointer;
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
	.da-help { color: #475569; font-size: 12px; margin-top: 6px; }
	.da-msg { padding: 8px 10px; border-radius: 8px; margin-bottom: 8px; }
	.da-ok { background: #e7f7e7; border: 1px solid #9ac79a; }
	.da-err { background: #fde9e9; border: 1px solid #e3abab; }
	.da-section { border: 1px solid #d9e0d7; border-radius: 8px; margin: 8px 0; background: #fbfdfb; }
	.da-section > summary { cursor: pointer; font-weight: 700; padding: 10px 12px; }
	.da-section[open] > summary { border-bottom: 1px solid #e6ece6; }
	.da-section-body { padding: 10px 12px; }
	.da-table-wrap { overflow: auto; max-height: calc(100vh - 280px); border: 1px solid #e4e4e4; border-radius: 8px; }
	@media (max-width: 1199px) { .da-table-wrap { max-height: 70vh; } }
	.da-leftbox > summary { font-size: 16px; }
	.da-picicon { display:inline-flex; align-items:center; gap:6px; margin-top:4px; font-size:12px; color:#334155; text-decoration:none; }
	.da-picicon img { width:28px; height:28px; object-fit:cover; border-radius:4px; border:1px solid #d1d5db; background:#fff; }
	.da-pick-row { display:flex; gap:6px; align-items:center; margin-top:4px; }
	.da-pick-row select { max-width:220px; font-size:12px; padding:4px; }
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
	</style>
	<div class="da-wrap">
		<div class="da-card">
			<h2 class="da-title">Digital Assessment Manager</h2>
			<div class="da-inline">
				<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h(rkrk_assess_append_setting_href("rkrk_digitalassess.php")); ?>">Zur Quiz-Ansicht</a>
				<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h(rkrk_assess_append_setting_href("rkrk_assess.php")); ?>">Zu Assessment</a>
				<a class="da-btn" style="text-decoration:none;" href="<?php echo da_h(rkrk_assess_append_setting_href("rkrk_assess_admin.php")); ?>">Zu Admin</a>
			</div>
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
				<form method="get" class="da-inline">
					<input type="hidden" name="mode" value="manage">
					<select name="selected_quiz">
						<option value="">-- Neues Assessment --</option>
						<?php foreach ($assessments as $aRow) { $k=(string)$aRow["Key"]; ?>
							<option value="<?php echo da_h($k); ?>"<?php echo ($k === $selectedKey ? " selected" : ""); ?>><?php echo da_h($k . " - " . $aRow["Titel"]); ?></option>
						<?php } ?>
					</select>
					<button class="da-btn" type="submit">Laden</button>
				</form>
				<details class="da-section" open>
					<summary>Setup bearbeiten</summary>
					<div class="da-section-body">
						<form method="post">
							<input type="hidden" name="mode" value="manage">
							<input type="hidden" name="admin_action" value="save_assessment">
							<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
							<div class="da-fields">
								<?php foreach ($assessmentHeaders as $h) { ?>
									<label><?php echo da_h($h); ?>
									<?php
									if ($h === "Einfuehrung") {
										echo "<textarea name='a[" . da_h($h) . "]' rows='3'>" . da_h($selectedAssessment[$h]) . "</textarea>";
									} elseif ($h === "Erfuellt") {
										$erPercent = ((float)$selectedAssessment[$h]) * 100.0;
										echo "<input type='number' step='1' min='0' max='100' name='a[" . da_h($h) . "]' value='" . da_h((string)round($erPercent, 2)) . "'>";
										echo "<span class='da-help'>Prozentwert (0-100)</span>";
									} else {
										echo "<input id='" . ($h === "Bild" ? "da-assessment-bild-input" : "") . "' type='text' name='a[" . da_h($h) . "]' value='" . da_h($selectedAssessment[$h]) . "'" . ($h === "Bild" ? " list='da-image-options'" : "") . ">";
									}
									?>
									<?php
									if ($h === "Bild") {
										$imgRel = da_clean_relative_file((string)$selectedAssessment[$h], "");
										echo "<div class='da-pick-row'><select class='da-image-select' data-target='da-assessment-bild-input'><option value=''>Bild aus DriveLocation wählen…</option>";
										foreach ($imageSelectOptions as $imgOpt) {
											$sel = ((string)$imgOpt === (string)$imgRel) ? " selected" : "";
											echo "<option value='" . da_h($imgOpt) . "'" . $sel . ">" . da_h($imgOpt) . "</option>";
										}
										echo "</select></div>";
										if ($imgRel !== "") {
											$imgUrl = $manageImageBaseWeb . ltrim($imgRel, "/");
											echo "<a class='da-picicon' target='_blank' rel='noopener' href='" . da_h($imgUrl) . "'><img src='" . da_h($imgUrl) . "' alt='Bild'><span>Vorschau</span></a>";
										}
									}
									?>
									</label>
								<?php } ?>
								<button class="da-btn" type="submit">Assessment speichern</button>
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
								<div class="da-inline">
									<label>Fragen-Datei <input type="text" name="question_file" value="<?php echo da_h($selectedAssessment["FragenDatei"]); ?>"></label>
									<button class="da-btn" type="submit">Fragen speichern</button>
								</div>
								<div class="da-table-wrap">
									<table class="da-table">
										<thead><tr><?php foreach ($questionHeaders as $qh) { echo "<th>" . da_h($qh) . "</th>"; } ?></tr></thead>
										<tbody id="da-question-body">
										<?php
										if (count($questions) === 0) {
											$questions[] = array_fill_keys($questionHeaders, "");
										}
										foreach ($questions as $i => $qRow) {
											echo "<tr>";
											foreach ($questionHeaders as $qh) {
												$val = isset($qRow[$qh]) ? $qRow[$qh] : "";
												if ($qh === "Frage" || $qh === "Loesungstext" || $qh === "Loesungsauswahl") {
													echo "<td><textarea name='q[" . da_h($qh) . "][" . (int)$i . "]' rows='2'>" . da_h($val) . "</textarea></td>";
												} elseif ($qh === "Frage-Bild" || $qh === "Loesungs-Bild") {
													$imgRelQ = da_with_drive_location((string)$val, (string)$selectedAssessment["DriveLocation"]);
													$fieldId = "q_" . preg_replace('/[^a-z0-9_]+/i', '_', $qh) . "_" . (int)$i;
													echo "<td><input id='" . da_h($fieldId) . "' type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h($val) . "' list='da-image-options'>";
													echo "<div class='da-pick-row'><select class='da-image-select' data-target='" . da_h($fieldId) . "'><option value=''>Bild aus DriveLocation wählen…</option>";
													foreach ($imageSelectOptions as $imgOpt) {
														$sel = ((string)$imgOpt === (string)$imgRelQ) ? " selected" : "";
														echo "<option value='" . da_h($imgOpt) . "'" . $sel . ">" . da_h($imgOpt) . "</option>";
													}
													echo "</select></div>";
													if ($imgRelQ !== "") {
														$imgUrlQ = $manageImageBaseWeb . ltrim($imgRelQ, "/");
														echo "<a class='da-picicon' target='_blank' rel='noopener' href='" . da_h($imgUrlQ) . "'><img src='" . da_h($imgUrlQ) . "' alt='Bild'><span>Bild</span></a>";
													}
													echo "</td>";
												} else {
													echo "<td><input type='text' name='q[" . da_h($qh) . "][" . (int)$i . "]' value='" . da_h($val) . "'></td>";
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
					<details class="da-section">
						<summary>Bilder hochladen</summary>
						<div class="da-section-body">
							<form method="post" enctype="multipart/form-data">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="upload_images">
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
						<summary>Schulungstext (HTML) bearbeiten</summary>
						<div class="da-section-body">
							<form method="post">
								<input type="hidden" name="mode" value="manage">
								<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
								<input type="hidden" name="admin_action" value="save_learning_html">
								<div class="da-inline">
									<label>Schulung HTML-Datei <input type="text" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>"></label>
									<button class="da-btn" type="submit">Schulung speichern</button>
								</div>
								<textarea name="learning_html" rows="18" style="width:100%;box-sizing:border-box;"><?php echo da_h($learningContent); ?></textarea>
								<div class="da-help">Freier HTML-Inhalt. Datei wird unter `data/assess/*.html` gespeichert und im Quiz als Lerninhalt geladen.</div>
							</form>
							<div class="da-inline" style="margin-top:8px;">
								<form method="post" style="display:inline-flex; gap:6px; align-items:center;">
									<input type="hidden" name="mode" value="manage">
									<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
									<input type="hidden" name="admin_action" value="download_learning_html">
									<input type="hidden" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>">
									<button class="da-btn" type="submit">HTML herunterladen</button>
								</form>
								<form method="post" enctype="multipart/form-data" style="display:inline-flex; gap:6px; align-items:center;">
									<input type="hidden" name="mode" value="manage">
									<input type="hidden" name="selected_quiz" value="<?php echo da_h($selectedKey); ?>">
									<input type="hidden" name="admin_action" value="upload_learning_html_file">
									<input type="hidden" name="learning_file" value="<?php echo da_h($selectedAssessment["LernDatei"]); ?>">
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
	</div>
	<script>
	(function () {
		const imageBase = <?php echo json_encode((string)$manageImageBaseWeb); ?>;
		const drivePrefix = <?php echo json_encode($driveLocCurrent !== "" ? (rtrim((string)$driveLocCurrent, "/") . "/") : ""); ?>;
		const grid = document.getElementById('da-grid');
		const splitter = document.getElementById('da-splitter');
		if (!grid || !splitter) return;

		let dragging = false;

		function isDesktop() {
			return window.matchMedia('(min-width: 1200px)').matches;
		}

		function applyColumns(leftPx) {
			if (!isDesktop()) return;
			const safe = Math.max(280, Math.min(leftPx, window.innerWidth - 360));
			grid.style.gridTemplateColumns = safe + 'px 10px minmax(0, 1fr)';
		}

		splitter.addEventListener('mousedown', function () {
			if (!isDesktop()) return;
			dragging = true;
			document.body.style.userSelect = 'none';
		});

		window.addEventListener('mousemove', function (e) {
			if (!dragging) return;
			const rect = grid.getBoundingClientRect();
			applyColumns(e.clientX - rect.left);
		});

		window.addEventListener('mouseup', function () {
			dragging = false;
			document.body.style.userSelect = '';
		});

		document.querySelectorAll('.da-image-select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				const targetId = sel.getAttribute('data-target');
				const target = targetId ? document.getElementById(targetId) : null;
				if (!target) return;
				if (sel.value) target.value = sel.value;
				updatePreview(target);
			});
		});

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
			const url = imageBase + rel;
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

		document.querySelectorAll("input[list='da-image-options'], #da-assessment-bild-input").forEach(function (inp) {
			inp.addEventListener('input', function () { updatePreview(inp); });
			inp.addEventListener('change', function () { updatePreview(inp); });
		});
	})();
	</script>
	</body>
	</html>
	<?php
	exit;
}


?>	

<!-- styles -->
<style>
  /* === Modern responsive layout (keeps existing IDs & JS hooks) === */
  /* CI-Farben: rkrk_assess_theme.css + bei ?RKRS / RKRSplus / RKRSprev rkrk_assess_theme_rkrs.css (kein :root hier — sonst wird Graustil wieder überschrieben) */

  body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--bg); color:var(--text); }

  /* Preserve legacy helper classes (used previously on <td>) */
  .left{ text-align:left; }
  .center{ text-align:center; }
  .right{ text-align:right; }

  /* Responsive container */
  .rkrk-wrap{ max-width:1100px; margin:0 auto; padding:14px; }
  .rkrk-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:14px 16px;
  }

  /* Header */
  .rkrk-topbar{ display:flex; gap:12px; align-items:flex-start; justify-content:space-between; }
  .rkrk-title h1{ margin:0; font-size:24px; line-height:1.2; }
  .rkrk-title #indrodution{ display:block; margin-top:6px; opacity:.8; }
  .rkrk-logo img{ height:56px; width:auto; object-fit:contain; display:block; }

  /* Learning */
  .rkrk-learning{ margin-top:14px; }

  /* Main grid */
  .rkrk-grid{ display:grid; gap:14px; margin-top:14px; grid-template-columns:1fr; }
  @media (min-width: 900px){
    .rkrk-grid{ grid-template-columns: 320px 1fr; align-items:start; }
    .rkrk-sidebar{ position:sticky; top:12px; }
  }

  .rkrk-main {   width: 100%; max-width: 100%; box-sizing: border-box; }
  

  /* Sidebar */
  .rkrk-sidebar #result{ display:block; margin-top:8px; }

  /* Question */
  .rkrk-qtitle{ font-weight:700; margin:10px 0 6px; font-size:16px; }
  .rkrk-qtext{ margin:0 0 12px; line-height:1.45; }

  /* Timers */
  #quiztimer-container, #timer-container{ width:100%; margin:8px 0; }
  #quiztimer-text, #timer-text{ display:block; text-align:center; font-weight:500; margin-bottom:6px; }
  #quiztimer-bar, #timer-bar{ height:10px; width:0%; background:#00c853; border-radius:999px; transition: width .15s linear, background-color .2s linear; }
  #quiztimer-container, #timer-container{ background:#eef1ee; border-radius:999px; padding:8px 10px; box-sizing:border-box; }
 
 /* Images responsive */
  #frage-bild, #loesung-bild{ max-width:100%; height:auto; }

  /* Answer area */
  .rkrk-fieldset{ border:1px solid var(--border); border-radius:12px; padding:12px; }
  .rkrk-fieldset legend{ padding:0 8px; font-weight:700; }

  /* Make each answer option tappable */
  .rkrk-options{ display:grid; gap:10px; grid-template-columns:1fr; margin-top:10px; }
  @media (min-width: 560px){ .rkrk-options{ grid-template-columns:1fr 1fr; } }
  .rkrk-opt{ position:relative; display:block; padding:0; border:0; background:transparent; }
  .rkrk-opt input{
    position:absolute;
    opacity:0;
    pointer-events:none;
  }
  .rkrk-opt label{
    display:block;
    cursor:pointer;
    line-height:1.25;
    padding:12px 14px;
    border:1px solid var(--border);
    border-radius:12px;
    background:#fff;
    transition:background-color .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease;
  }
  .rkrk-opt label:hover{
    border-color:#b7c7b4;
    background:#f7faf6;
  }
  .rkrk-opt input:focus-visible + label{
    outline:2px solid #7aa56d;
    outline-offset:2px;
  }
  .rkrk-opt input:checked + label{
    background:#e8f4e5;
    border-color:#6f9663;
    box-shadow:0 0 0 1px rgba(111,150,99,.15) inset;
    color:#1f3b1b;
    font-weight:600;
  }

  /* Name + drone */
  .rkrk-user{ display:grid; gap:10px; margin-top:12px; }
  .rkrk-field label{ display:block; margin-bottom:6px; font-weight:700; }
  .rkrk-field input, .rkrk-field select{
    width:100%; box-sizing:border-box; padding:11px 12px;
    border-radius:10px; border:1px solid var(--border);
    font-size:16px; background:#fff;
  }

  /* Navigation buttons */
  .rkrk-nav{ display:flex; gap:10px; flex-wrap:wrap; justify-content:space-between; margin-top:14px; }
  .rkrk-nav > span{ flex:1; min-width:160px; }
  .rkrk-nav button{ width:100%; }

  /* Solution */
  .rkrk-solution{ margin-top:14px; padding-top:12px; border-top:1px dashed var(--border); }

  /* Footer */
  .rkrk-footer{ margin-top:14px; font-size:13px; opacity:.85; }
  .rkrk-footer a{ color:#2a5b2a; text-decoration:none; }
  .rkrk-footer a:hover{ text-decoration:underline; }
  .rkrk-footer-links{
    margin-top:10px;
    display:flex;
    gap:10px;
    justify-content:center;
    flex-wrap:wrap;
  }
  .rkrk-footer-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:8px;
    border:1px solid var(--toolbar-btn-bd);
    background:#fff;
    color:var(--toolbar-text) !important;
    text-decoration:none !important;
    font-size:13px;
    line-height:1.2;
    transition:background .15s,border-color .15s,box-shadow .15s;
  }
  .rkrk-footer-btn:hover{
    background:var(--toolbar-hover-bg);
    border-color:var(--toolbar-hover-bd);
    box-shadow:0 1px 3px rgba(45,64,36,.08);
    text-decoration:none !important;
  }

  /*footnote*/
	sup a {  text-decoration: none;  font-size: 0.75em;  vertical-align: super; }

	img {
		border-radius : 5px 15% 10px;	
	}
	
	.lernimagelarge {
		//align:center;
		height:500px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimage {
		//align:center;
		height:300px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagesmall {
		//align:center;
		height:200px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimageverysmall {
		//align:center;
		height:100px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixwidth {
		//align:center;
		width:600px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixheight {
		//align:center;
		height:200px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixlargeheight {
		//align:center;
		height:500px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagesmallfixwidth {
		//align:center;
		width:450px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lerncaption {
		//align: middle;
		font-style: italic;
	}

    .formula {
        font-family: "Courier New", monospace;
        background: #f4f4f4;
        padding: 4px 6px;
        border-radius: 4px;
        display: inline-block;
    }

	.key {
	  background: #f0f8ff;
	  padding: 4px 8px;
	  display: inline-block;
	  margin: 5px 0;
	}

   .keywords {
	  font-style: italic;
	  color: #666;
	  margin-bottom: 1rem;
	}
	.highlight {
	  background-color: #e6f7ff;
	  border-left: 4px solid #1890ff;
	  padding: 0.5rem 1rem;
	  margin: 1rem 0;
	}
	ul.checklist {
	  list-style: none;
	  padding-left: 0;
	}
	ul.checklist li::before {
	  content: "✅ ";
	  color: green;
	}

</style>
<!-- TIMER balken -->
	<style>
	#timer-container,#quiztimer-container  {
		width: 100%;
		height: 20px;
		background: #ddd;
		border-radius: 4px;
		overflow: hidden;
	  }

	#timer-bar,#quiztimer-bar  {
		width: 100%;
		height: 100%;
		background: #00c853;
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;              /* Textfarbe */
		font-weight: bold;
		font-size: 14px;
		text-shadow: 1px 1px 2px black; /* bessere Lesbarkeit */
	}

	#timer-text,#quiztimer-text {
		position: absolute;
		z-index: 2;                /* Text über der Farbe */
		pointer-events: none;      /* Klicks gehen durch */
	}
	</style>

<!-- tabellen, rahmen -->
<style>
/* Always align all cells to the top */
.two-gray-cols td,
.two-gray-cols th,
.one-gray-col td,
.one-gray-col th {
    vertical-align: top;
}
.lightgreen-box {
    background-color: #e8f8e8; /* sehr helles Grün */
    border: 2px solid #8ac78a; /* dezentes Grün für den Rahmen */
    padding: 1rem 1.2rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.lightred-box {
    background-color: #fdeaea; /* sehr helles Rot */
    border: 2px solid #e08a8a; /* dezentes, warmes Rot für den Rahmen */
    padding: 1rem 1.2rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.lightred-box h3,
.lightgreen-box h3 {
    margin-top: 0;
    font-weight: bold;
}

.lightred-box p {
    margin: 0.4rem 0 1rem 0;
    font-weight: bold;
    font-size: 120%;
	color: #b85a5a; /* dezentes, etwas dunklers Rot für den Rahmen */
}

.lightgreen-box p {
    margin: 0.4rem 0 1rem 0;
}

/* Zwei graue Spalten */
.two-gray-cols td:nth-child(-n+2),
.two-gray-cols th:nth-child(-n+2),
.two-gray-cols tr:first-child th {
    background-color: #f0f0f0;
    font-weight: bold;
}

/* Sicherstellen: zweite Spalte ist wirklich fett */
.two-gray-cols td:nth-child(2),
.two-gray-cols th:nth-child(2) {
    font-weight: normal;
}

/* Eine graue Spalte */
.one-gray-col td:nth-child(1),
.one-gray-col th:nth-child(1),
.one-gray-col tr:first-child th {
    background-color: #f0f0f0;
    font-weight: bold;

</style>

<style>
.instruktor-only {
    display: none;
    color: #555;
    font-style: italic;
}
</style>
<?php
if (function_exists('rkrk_assess_theme_rkrs_layer_link')) {
	rkrk_assess_theme_rkrs_layer_link();
}
?>


<?php	
	$urladder = "";
	if (isset($_GET["TEST"])) {
		echo "<body>";
		$urladder = "&TEST";
	} else	{
		echo "<body oncontextmenu='return false;'>";
	}

	$rkSetting = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
	if ($rkSetting !== "") {
		if ($urladder !== "") {
			$urladder .= "&" . $rkSetting;
		} else {
			$urladder = $rkSetting;
		}
	}

	if (isset($_GET["quiz"])) {
		$set_quiz = $_GET["quiz"];
	} elseif (isset($_POST["quiz"])) {
		$set_quiz = $_POST["quiz"];
	} else	{
		$set_quiz = "";
	}	

	if (isset($_GET["mode"])) {
		$mode = $_GET["mode"];
	} else	{
		$mode = "";
	}	

	if (isset($_GET["sortiert"])) {
		$sorted = true;
	} else	{
		$sorted = false;
	}	

	if (isset($_POST["AnzFragen"])) {
		$AnzFragen = $_POST["AnzFragen"];
	} else	{
		$AnzFragen = 0;
	}	

	if (isset($_POST["RichtigBeantwortet"])) {
		$FragenRichtig = $_POST["RichtigBeantwortet"];
	} else	{
		$FragenRichtig = 0;
	}	

	if (isset($_POST["Erfuellt"])) {
		$Erfuellt = $_POST["Erfuellt"];
	} else	{
		$Erfuellt = 0;
	}	

	if (isset($_GET["Status"])) {
		$Status = $_GET["Status"];
	}	
	elseif (isset($_POST["Status"])) {
		$Status = $_POST["Status"];
	}	

	if (isset($_GET["Schulung"])) {
		$Schulung = "Schulung";
		if ($urladder<>"") {
			$urladder = $urladder . "&Schulung";
		} else {
			$urladder = "Schulung";
		}
	}	
	elseif (isset($_POST["Schulung"])) {
		$Schulung = $_POST["Schulung"];
	} else {
		$Schulung = "";
	}

	if (isset($_POST["Name"])) {
		$Name = $_POST["Name"];
	} else	{
		$Name = "";
	}	

	if (isset($_POST["Quiz"])) {
		$Quiz = $_POST["Quiz"];
	} else	{
		$Quiz = "";
	}	

	if (isset($_POST["Drone"])) {
		$Drone = $_POST["Drone"];
	} elseif (isset($_GET["Drone"])) {
		$Drone = $_GET["Drone"];
	} else {
		$Drone = "";   // noch nichts gewählt
	}

	//echo "AnzFragen: " . $AnzFragen . " Richtig: " . $FragenRichtig . " Status: " . $Status . " Quiz: " . $Quiz . "<br>";
	if ($Status=="resultat") {
		$__dgaResultsDir = '';
		if (isset($QuizLocResults) && (string)$QuizLocResults !== '') {
			$__dgaResultsDir = rtrim(str_replace("\\", "/", (string)$QuizLocResults), "/");
			if ($__dgaResultsDir !== '' && !is_dir($__dgaResultsDir)) {
				@mkdir($__dgaResultsDir, 0755, true);
			}
		}
		if ($__dgaResultsDir !== '' && function_exists("rkrk_assess_quiz_results_load_rows")) {
			$data = rkrk_assess_quiz_results_load_rows($__dgaResultsDir);
			if (!is_array($data)) {
				$data = array();
			}
			$i = count($data);
			$data[$i]["Name"] = $Name;
			$data[$i]["Quiz"] = $set_quiz;
			$data[$i]["Zeit"] = date("d.m.Y h:m");
			$data[$i]["AnzFragen"] = $AnzFragen;
			$data[$i]["Richtig"] = $FragenRichtig;
			$data[$i]["Erfuellungsfaktor"] = $Erfuellt;
			$__anz = (int)$AnzFragen;
			$__erf = is_numeric($Erfuellt) ? (float)$Erfuellt : 0.0;
			$__richt = is_numeric($FragenRichtig) ? (int)$FragenRichtig : 0;
			if ($__anz > 0 && ($__richt / $__anz) >= $__erf) {
				$data[$i]["Erfuellung"] = "Ja";
			} else {
				$data[$i]["Erfuellung"] = "Nein";
			}
			rkrk_assess_quiz_results_save_rows($__dgaResultsDir, $data);
		}
	}	

	// ===========================================
	// Definitionen
	// ===========================================
	
	// Öffentliche Quiz-Defaults — RKRS/RKRSplus/RKRSprev nicht überschreiben (Logo, Audienz, Copyright kommen aus rkrk_assess_common.php)
	if (!rkrk_assess_ci_rkrs_mode()) {
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "PUB";
	}
	
	// TO CHANGE
			
	// PHP I/O: absolute path on disk (zentral $rkrkAssessFsDataRoot aus rkrk_assess_common.php, Fallback gen).
	//$_rkrkAssessRoot = isset($rkrkAssessFsDataRoot) ? $rkrkAssessFsDataRoot : $RKRKRelRKRKDataLocation;
	//$QuizLoc = $_rkrkAssessRoot . 'data/assess/';
	// Browser (img src, fetch): URL under $WebLocation — do not use filesystem paths here.
	//$QuizLocWeb = rtrim((string)$WebLocation, '/') . '/rkrk/data/assess/';
	//$QuizIconLoc = $QuizLocWeb . 'images/';
	$_rkrkSn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$_rkrkDn = trim(dirname($_rkrkSn), '/');
	$_rkrkSeg = ($_rkrkDn === '') ? array() : explode('/', $_rkrkDn);
	$_rkrkRi = false;
	foreach ($_rkrkSeg as $_i => $_p) {
		if (strtolower((string)$_p) === 'rkropen') {
			$_rkrkRi = (int)$_i;
			break;
		}
	}
	if ($_rkrkRi !== false) {
		$QuizLocWeb = '/' . implode('/', array_slice($_rkrkSeg, 0, $_rkrkRi + 1)) . '/rkrk/data/assess/';
	} else {
		$QuizLocWeb = '/rkropen/rkrk/data/assess/';
	}
	$QuizIconLoc = $QuizLocWeb . 'images/';
	unset($_rkrkSn, $_rkrkDn, $_rkrkSeg, $_rkrkRi, $_i, $_p);

	$PicutreStyle ="border-radius: 20px;  border: none; object-fit: cover";
	$ButtonStyle ="border-radius: 3px;  border: none;  color: white;  background: var(--brand); padding: 10px;  text-align: center;  text-decoration: none;  display: inline-block;  font-size: 16px;  margin: 4px 2px;  cursor: pointer";
	$ButtonStyleRed ="border-radius: 3px;  border: none;  color: white;  background: var(--danger); padding: 10px;  text-align: center;";  
	$ButtonStyleGrey ="border-radius: 3px;  border: none;  color: white;  background: #E8E8E8; padding: 10px;  text-align: center;";  
	
	$PicWidth = '600';
	$PicHeight = '400';
	$PicHeightSmall = '200';
	$ColWidth=100;

	// ========================================
	// Read csv data
	// ========================================
	
	//pre_R($QuizSelection);
	$csv_desc= ";";
	$QuizList = csv_to_array_ascII($QuizLoc."AssessmentListe.csv",$csv_desc);
	if (!is_array($QuizList)) {
		$QuizList = array();
	}
	$Questionair = array();
	$QSelection = array();
	$LearningContent = "";

	//pre_R($QuizList);

	//-----------------------------------------
	//Fragenauswahl
	//-----------------------------------------
	$selectedQuizRow = null;
	if ($set_quiz !== "") {
		foreach ($QuizList as $qlRow) {
			if (is_array($qlRow) && isset($qlRow["Key"]) && (string)$qlRow["Key"] === (string)$set_quiz) {
				$selectedQuizRow = $qlRow;
				break;
			}
		}
	}
	if (is_array($selectedQuizRow) && isset($selectedQuizRow["Locked"]) && (string)$selectedQuizRow["Locked"] === "Ja") {
		// quiz nicht zugreifbar
		$set_quiz = "";
	}
	//echo "QuizSet: " . $set_quiz . "<br>";

	$i=0;
	//$QuizSelection = Array();
	//pre_R($QuizList);
	foreach($QuizList as $key => $ql) {
		//pre_r($ql);
		//echo "Set: " . $setting . " Aud: " . $ql["Audienz"] . "<br>";
		//echo $i . " Quiz: " . $key . " " . $ql["Key"] . "<br>";
		if (
			(
				($ql["Aktiv"]=="Ja") 
			and ($ql["Locked"]=="Nein")
			or (strpos($ql["Audienz"], $setting) !== false)
				or ($ql["Audienz"]=="Alle") 
				or ($setting == "TEST")
				) 
			) 
			{
			if (($set_quiz==$ql["Key"]) or (($set_quiz=="") and ($ql["Key"]=="RKR"))) {
				$Questionair = $QuizList[$key];
				$QSelection = csv_to_array_ascII($QuizLoc.$ql["FragenDatei"],$csv_desc);
				if (!is_array($QSelection)) {
					$QSelection = array();
				}
				//pre_r($QSelection);
				foreach ($QSelection as $key => $Q) {
					$QSelection [$key]["Loesungsauswahl"] = explode("|", $Q["Loesungsauswahl"]);
					foreach ($QSelection [$key]["Loesungsauswahl"] as $kkey => $s) {
						$QSelection [$key]["Loesungsauswahl"][$kkey] = $s;
					}
					
				if (file_exists($QuizLoc.$ql["LernDatei"]) and ($ql["LernDatei"]!="")) {
					//echo "exists: " . $QuizLoc.$ql["LernDatei"] . "<br>";
					$LearningContent = $QuizLocWeb . $ql["LernDatei"];
				} else {
					$LearningContent ="";
				}

				}	
			}	
			$i++;
		}	
	}
	
	if (count($QSelection)==0) {
		$NoQuiz = 1;
	} else {
		$NoQuiz = 0;
	}

	$DroneTypes = [];

	foreach ($QSelection as $q) {
		$d = trim($q["Drone"]);
		if ($d !== "" && $d !== "Alle" && $q["Aktiv"]=="Ja") {
			$DroneTypes[$d] = true;
		}
	}

	$DroneTypes = array_keys($DroneTypes);
	$HasDroneTypes = count($DroneTypes) > 0;
	$DroneFilterActive = $HasDroneTypes && $Drone !== "" && $Drone !== "Alle";

	//pre_r($DroneTypes);

	//-----------------------------------------
	//Sortiere die Fragen um
	//-----------------------------------------

	$Order = Array();
	$Questions = Array();

	//Define Order of Questions
	foreach ($QSelection as $key => $Q) {
		$Order[$key]["key"] = $key;
		$Order[$key]["random"] = rand(0,100);
	}

	if (($mode!="Korrektur") and !$sorted) {
		aasort($Order, "random");
		//pre_r($Order);
	}	
	
	//pre_R($Order);
	
		// Normalize drone strings
	function normalizeDrone($str) {
		if ($str === null) return '';

		$str = (string)$str;

		// URL-encoded → normal
		$str = urldecode($str);

		// NBSP → normales Space
		$str = str_replace("\xC2\xA0", ' ', $str);

		// Zero-width spaces entfernen
		$str = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $str);

		// Mehrfache Spaces
		$str = preg_replace('/\s+/', ' ', $str);

		// Trim
		$str = trim($str);

		return $str;
	}

	$Drone = normalizeDrone($Drone);
	// echo "Drone (normalisiert): [" . $Drone . "]<br>";

	$i = 0;

	foreach ($Order as $key => $Q) {

		// Drone aus CSV normalisieren
		$drone = normalizeDrone($QSelection[$Q["key"]]["Drone"]);
		// echo "Frage " . $Q["key"] . " Drone=[" . $drone . "]<br>";

		// Drohnen-Matching-Regeln
		if ($Drone === "" || $Drone === "Alle") {
			// Keine spezifische Drohne gewählt → nur Fragen ohne Spezifikation oder "Alle"
			$droneMatch = ($drone === "" || $drone === "Alle");
		} else {
			// Spezifische Drohne gewählt → allgemeine + passende spezifische
			$droneMatch = ($drone === "" || $drone === "Alle" || $drone === $Drone);
		}

		if (
			(strcasecmp($QSelection[$Q["key"]]["Aktiv"],"Ja") == 0) &&
			(
				(strpos($QSelection[$Q["key"]]["Audienz"], $setting) !== false) ||
				(strcasecmp($QSelection[$Q["key"]]["Audienz"],"Alle") == 0) ||
				($setting == "TEST")
			) &&
			$droneMatch
		) {
			$Questions[$i] = $QSelection[$Q["key"]];
			$Questions[$i]["QID"] = $Q["key"];
			$i++;
		}
	}
	//echo "Ql";
	//pre_r($QSelection[5]);
	//echo "Q";
	//pre_r($Questions[5]);
	//echo "Lerninhalt " . $LearningContent . "<br>";

	// ===========================================
	// Inhalt
	// ===========================================
	

	// ===========================================
	// Inhalt (responsive, gleiche IDs & JS-Hooks)
	// ===========================================

	echo "<div class='rkrk-wrap'>" . chr(10);

	// Topbar
	if ($onAdmin) {
		echo "<header class='rkrk-topbar rkrk-card'>" . chr(10);
		echo "  <div class='rkrk-title'>" . chr(10);
		echo "    <h1><span id='quiz-titel'>QuizTitel</span></h1>" . chr(10);
		echo "    <span id='indrodution'></span>" . chr(10);
		if (!(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode())) {
		echo "    <div class='quick-links' id='rkrk-topbar-quicklinks'>" . chr(10);
		echo "      <a class='ql-btn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_assess.php"), ENT_QUOTES, "UTF-8") . "'>Assessments</a>" . chr(10);
		echo "      <a class='ql-btn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_assess_admin.php"), ENT_QUOTES, "UTF-8") . "'>Assessment Admin</a>" . chr(10);
	echo "      <a class='ql-btn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_digitalassess_admin.php"), ENT_QUOTES, "UTF-8") . "'>Fragen Manager</a>" . chr(10);
		echo "      <a class='ql-btn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_assess_live.php"), ENT_QUOTES, "UTF-8") . "'>Beurteilungen</a>" . chr(10);
		echo "      <a class='ql-btn' href='" . htmlspecialchars(rkrk_assess_append_setting_href("rkrk_assess_live_personel.php?view=list"), ENT_QUOTES, "UTF-8") . "'>Personal</a>" . chr(10);
		echo "    </div>" . chr(10);
		}
		echo "  </div>" . chr(10);
		echo "  <div class='rkrk-logo'>" . chr(10);
		if ($logo=="rkrs") {
			echo "<img src='".$QuizIconLoc.$logopic."' id='logopic' name='logopic' width='247' height='80'>" . chr(10);
		} elseif ($logo=="rkrk") {
			echo "<img src='".$QuizIconLoc.$logopic."' id='logopic' name='logopic' height='80'>" . chr(10);
		} else {
			echo "<img src='".$QuizIconLoc."void.png' id='logopic' name='logopic' width='100'>" . chr(10);
		}
		echo "  </div>" . chr(10);
		echo "</header>" . chr(10);
	}
	// Learning content
	echo "<section class='rkrk-card rkrk-learning'  id='rkrk-card'>" . chr(10);
	echo "  <span id='learning'></span>" . chr(10);
	echo "  <span id='submit_learn' hidden><br><br><button id='LernenAbschliessen' onclick='ShowNameFormAfterLearning()' style='".$ButtonStyle ."'>Lerninhalt abschliessen</button></span>" . chr(10);
	echo "</section>" . chr(10);

	// Main grid
	echo "<div class='rkrk-grid'>" . chr(10);

	// Sidebar / Resultate
	echo "<aside class='rkrk-card rkrk-sidebar'  id='rkrk-sidebar'>" . chr(10);
	echo "  <span id='result-titel'></span>" . chr(10);
	echo "  <span id='result'>Resultate</span>" . chr(10);
	echo "</aside>" . chr(10);

	// Main content
	echo "<main class='rkrk-card rkrk-main' id='rkrk-main'>" . chr(10);
	//Teilnehmerinfo
	echo "    <span id='Teilnehmerinfo' hidden>Teilnehmerinfo</span>" . chr(10);

	// Timers + Question
	echo "<div class='rkrk-question'>" . chr(10);
	echo "  <div id='quiztimer-container' hidden><span id='quiztimer-text'>Deine Zeit läuft...</span><div id='quiztimer-bar'></div></div>" . chr(10);
	echo "  <p class='rkrk-qtitle'><span id='Frage_Titel'>Frage_Titel</span></p>" . chr(10);
	echo "  <p class='rkrk-qtext'><span id='Frage'>Frage</span></p>" . chr(10);
	echo "  <div id='timer-container' hidden><span id='timer-text'>Deine Zeit läuft...</span><div id='timer-bar'></div></div>" . chr(10);
	echo "  <img src='".$QuizIconLoc."void.png' id='frage-bild' name='frage-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/>" . chr(10);
	echo "</div>" . chr(10);

	// Form (Antworten + Hidden fields + Close submit)
	echo "<form method='post' action='". htmlspecialchars($_SERVER['REQUEST_URI']) . "' class='rkrk-form' style='text-align: center;'>" . chr(10);
	echo "  <span id='auswahl'>" . chr(10);
	echo "  <fieldset class='rkrk-fieldset'>" . chr(10);
	echo "    <legend>Deine Wahl<span id='mehrfach'></span>:</legend>" . chr(10);
	echo "    <div class='rkrk-options'>" . chr(10);

	// Ja/Nein
	echo "      <span id='a-ja' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='ja' name='response' value='Ja' />" . chr(10);
	echo "        <label for='ja'><b>Ja</b></label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-nein' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='nein' name='response' value='Nein' />" . chr(10);
	echo "        <label for='nein'><b>Nein</b></label>" . chr(10);
	echo "      </span>" . chr(10);

	// A–H (nicht belegte Buchstaben blendet GoToQuestion aus)
	foreach (array("A", "B", "C", "D", "E", "F", "G", "H") as $rkrkOptLetter) {
		$rkrkOptLow = strtolower($rkrkOptLetter);
		echo "      <span id='a-" . $rkrkOptLow . "' class='rkrk-opt'>" . chr(10);
		echo "        <input type='radio' id='" . $rkrkOptLetter . "' name='response' value='" . $rkrkOptLetter . "' />" . chr(10);
		echo "        <label id='label" . $rkrkOptLetter . "' for='" . $rkrkOptLetter . "'>" . $rkrkOptLetter . "</label>" . chr(10);
		echo "      </span>" . chr(10);
	}
	unset($rkrkOptLetter, $rkrkOptLow);

	echo "    </div>" . chr(10);
	echo "  </fieldset>" . chr(10);
	echo "  </span>" . chr(10);

	// User info
	echo "  <div class='rkrk-user'>" . chr(10);
	echo "    <div class='rkrk-field'>" . chr(10);
	echo "      <label for='Name' id='Name-Label'>Dein Name:</label>" . chr(10);
	echo "      <input type='text' id='Name' name='Name' value='".$Name."'>" . chr(10);
	echo "    </div>" . chr(10);

	// Drohnenauswahl nur beim Start anzeigen
	if ($Status == "" && $HasDroneTypes) {
		echo "    <div class='rkrk-field'>" . chr(10);
		echo "      <label for='Drone' id='Drone-Label'>Deine Drohne:</label>" . chr(10);
		echo "      <select id='Drone' name='Drone'>" . chr(10);
		echo "        <option value=''>Bitte wählen…</option>" . chr(10);
		foreach ($DroneTypes as $d) {
			$sel = ($Drone === $d) ? " selected" : "";
			echo "        <option value='".htmlspecialchars($d,ENT_QUOTES)."'".$sel.">".htmlspecialchars($d)."</option>" . chr(10);
		}
		echo "      </select>" . chr(10);
		echo "    </div>" . chr(10);
	} elseif (!$HasDroneTypes) {
		echo "<input type='hidden' id='Drone' name='Drone' value='Alle'>" . chr(10);
	}

	echo "  </div>" . chr(10);

	// Hidden fields
	echo "  <input type='text' id='AnzFragen' name='AnzFragen' hidden>" . chr(10);
	echo "  <input type='text' id='RichtigBeantwortet' name='RichtigBeantwortet' hidden>" . chr(10);
	echo "  <input type='text' id='Erfuellt' name='Erfuellt' hidden>" . chr(10);
	echo "  <input type='text' id='Quiz' name='Quiz' hidden>" . chr(10);
	echo "  <input type='text' id='Status' name='Status' value='".$Status."' hidden>" . chr(10);
	echo "  <input type='text' id='Schulung' name='Schulung' value='".$Schulung."' hidden>" . chr(10);

	// Abschluss
	echo "  <input type='submit' id='close' value='Quiz Abschliessen' style='".$ButtonStyleRed ."' hidden>" . chr(10);
	echo "</form>" . chr(10);

	// Name fehlt Hinweis
	echo "<span id='noname' hidden><button id='noname_button' onclick='NameEingeben()' style='".$ButtonStyleRed ."'>Bitte Name vor dem Abschliessen eingeben.</button></span>" . chr(10);

	// Navigation
	echo "<div class='rkrk-nav'>" . chr(10);
	echo "  <span id='prev_button'><button id='prev' onclick='PrevQuestion()' style='".$ButtonStyle ."'>Zurück</button></span>" . chr(10);
	echo "  <span id='submit_button'><button id='submit' onclick='Submit()' style='".$ButtonStyle ."'>eingeben und auflösen</button></span>" . chr(10);
	echo "  <span id='next_button'><button id='next' onclick='NextQuestion()' style='".$ButtonStyle ."'>Weiter</button></span>" . chr(10);
	echo "</div>" . chr(10);

	// Lösung
	echo "<section class='rkrk-solution' id='rkrk-solution'>" . chr(10);
	echo "  <span id='loesungstext'>Lösungstext</span><br>" . chr(10);
	echo "  <span id='loesung' hidden>" . chr(10);
	echo "    <img src='".$QuizIconLoc."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "    <span id='korrektur' hidden>Korrektur</span><br>" . chr(10);
	echo "  </span><br>" . chr(10);
	echo "</section>" . chr(10);

	// Debug
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "  <div id='debug' name='debug' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug2' name='debug2' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "</section>" . chr(10);
	echo "</main>" . chr(10);

	echo "</div>" . chr(10); // grid
	// Footer
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "<footer class='rkrk-footer'>" . chr(10);
	echo "  <div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "  <div class='rkrk-footer-links'>" . chr(10);
	$__dgaHideWeitereHub = function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode();
	if ($Schulung) {
		echo "<a class='rkrk-footer-btn' href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Schlungen Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</a>" . chr(10);
		if (!$__dgaHideWeitereHub) {
			echo "<a class='rkrk-footer-btn' href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Schulungen</a>" . chr(10);
		}
	} else {
		echo "<a class='rkrk-footer-btn' href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Quiz Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</a>" . chr(10);
		if (!$__dgaHideWeitereHub) {
			echo "<a class='rkrk-footer-btn' href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Quiz</a>" . chr(10);
		}
	}
	echo "  </div>" . chr(10);
	echo "  <span id='buffer'></span>" . chr(10);
	echo "</footer>" . chr(10);
	echo "</section>" . chr(10);
	echo "</div>" . chr(10); // wrap


?>
<!-- TIMER balken --> 
<script>
let timerDuration = 20;
let timerOrange = 5;
let timerRed = 3;
let timerStart = null;
let timerActive = false;

function startTimer() {
    timerStart = null;
    timerActive = true;
    requestAnimationFrame(runTimer);
}

function stopTimer() {
    timerActive = false;
}

function resetTimerBar() {
    const bar = document.getElementById("timer-bar");
    bar.style.width = "100%";
    bar.style.backgroundColor = "#00c853";
}

function runTimer(timestamp) {
    if (!timerActive) return;

    if (!timerStart) timerStart = timestamp;
    const elapsed = (timestamp - timerStart) / 1000;
    const remaining = Math.max(timerDuration - elapsed, 0);

    const bar = document.getElementById("timer-bar");
    const progress = remaining / timerDuration;

    bar.style.width = (progress * 100) + "%";

    // Dynamische Farbschwellen
    let orangeLimit = timerDuration / 3;
    let redLimit = timerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000"; // Rot
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800"; // Orange
    } else {
        bar.style.backgroundColor = "#00c853"; // Grün
    }

    if (remaining > 0) {
        requestAnimationFrame(runTimer);
    } else {
        timerActive = false;
        Submit();
    }
	
}

function runQuizTimer(timestamp) {
	document.getElementById("quiztimer-container").style.display = "block";

    if (!quizTimerActive) return;

    if (!quizTimerStart) quizTimerStart = timestamp;

    const elapsed = (timestamp - quizTimerStart) / 1000;
    const remaining = Math.max(quizTimerDuration - elapsed, 0);

    // mm:ss Format
    let mm = Math.floor(remaining / 60);
    let ss = Math.floor(remaining % 60);
    if (ss < 10) ss = "0" + ss;

    // Elemente
    const bar = document.getElementById("quiztimer-bar");
    const txt = document.getElementById("quiztimer-text");
    const cont = document.getElementById("quiztimer-container");

    // Balken sichtbar halten
    cont.style.display = "block";

    // Balkenbreite
    const progress = remaining / quizTimerDuration;
    bar.style.width = (progress * 100) + "%";

    // Farben
    let orangeLimit = quizTimerDuration / 3;
    let redLimit = quizTimerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000";
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800";
    } else {
        bar.style.backgroundColor = "#00c853";
    }

    // Text
    txt.innerHTML = mm + ":" + ss;

    if (remaining > 0) {
        requestAnimationFrame(runQuizTimer);
    } else {
        quizTimerActive = false;
        document.getElementById("Status").value = "resultat";
        document.forms[0].submit();
    }
}
</script>

<script type='text/javascript'>
<?php
echo "var QuizList = ". json_encode($QuizList) . ";\n"; 
echo "var Questionair = ". json_encode($Questionair) . ";\n"; 
echo "var Questions = ". json_encode($Questions) . ";\n"; 
echo "var QuizIconTopLoc = ". json_encode($QuizIconLoc) . ";\n"; 
$driveLoc = isset($Questionair["DriveLocation"]) ? (string)$Questionair["DriveLocation"] : "";
$driveLoc = str_replace("\\", "/", $driveLoc);
if (preg_match('#/(?:rkrk|rkrs)/data/assess/images/(.*)$#i', $driveLoc, $m) === 1) {
	$driveLoc = (string)$m[1];
}
$driveLoc = trim($driveLoc);
$driveLoc = ltrim($driveLoc, "/");
$driveLoc = rtrim($driveLoc, "/");
if ($driveLoc !== "" && function_exists("rkrk_assess_encode_url_path_segments")) {
	$driveLoc = rkrk_assess_encode_url_path_segments($driveLoc);
}
if ($driveLoc !== "") {
	$driveLoc .= "/";
}
echo "var QuizIconLoc = ". json_encode($QuizIconLoc . $driveLoc) . ";\n"; 
echo "var QuizIntroduction = ". json_encode(isset($Questionair["Einfuehrung"]) ? (string)$Questionair["Einfuehrung"] : "") . ";\n";
echo "var setting = '". $setting . "';\n";
echo "var set_quiz = '". $set_quiz . "';\n";
echo "var urladder = '". $urladder . "';\n";
echo "var allquestions = '". ($mode=="Korrektur")  . "';\n";
echo "var NoQuiz = " . $NoQuiz . ";\n";
echo "var Status = '" . $Status . "';\n";
echo "var Schulung = '" . $Schulung . "';\n";
echo "var FragenRichtig = '" . $FragenRichtig . "';\n";
echo "var AnzFragen = '" . $AnzFragen . "';\n";
echo "var Erfuellt = '" . $Erfuellt . "';\n";
echo "var LearningContent = " . json_encode((string)$LearningContent) . ";\n";
echo "var Quiz = '" . $Quiz . "';\n";
echo "var PicHeight = ". $PicHeight  . ";\n";
echo "var PicHeightSmall = ". $PicHeightSmall  . ";\n";
echo "var PicWidth = '" . $PicWidth . "';\n";
echo "var PicutreStyle = '" . json_encode($PicutreStyle) . "';\n";
echo "var SelectedDrone = '". $Drone ."';\n";
echo "var QuizTime = ". json_encode(isset($Questionair["QuizTime"]) ? (string)$Questionair["QuizTime"] : "") . ";\n";
echo "var Name = '". $Name ."';\n";
?>

function rkrkQuizEncodePathSegment(seg) {
    if (!seg) return "";
    let s = seg;
    for (let i = 0; i < 6; i++) {
        try {
            const d = decodeURIComponent(s);
            if (d === s) break;
            s = d;
        } catch (e) {
            break;
        }
    }
    try {
        if (typeof s.normalize === "function") {
            s = s.normalize("NFC");
        }
    } catch (e2) {}
    return encodeURIComponent(s);
}
function rkrkQuizImageUrl(base, rawPath) {
    const markers = [
        "/rkrk/data/assess/quiz/images/",
        "/rkrs/data/assess/quiz/images/",
        "/rkrsplusassessment/data/assess/quiz/images/",
        "/rkrk/data/assess/images/",
        "/rkrs/data/assess/images/",
        "/rkrsplusassessment/data/assess/images/"
    ];
    let p = String(rawPath || "").replace(/\\/g, "/").trim();
    if (!p) return "";
    if (/^https?:\/\//i.test(p)) return p;
    const low = p.toLowerCase();
    for (let i = 0; i < markers.length; i++) {
        const marker = markers[i];
        const mi = low.indexOf(marker);
        if (mi >= 0) {
            p = p.substring(mi + marker.length);
            break;
        }
    }
    p = p.replace(/^(?:quiz\/)?images\//i, "");
    p = p.replace(/^\/+/, "");
    const enc = p.split("/").map(rkrkQuizEncodePathSegment).filter(Boolean).join("/");
    return String(base || "") + enc;
}

var AtQuestion = 0;
var url = window.location.href.split('?')[0];

// CSV/PHP JSON can arrive as object with numeric keys; normalize to arrays for UI loops.
if (!Array.isArray(QuizList)) {
    QuizList = Object.values(QuizList || {});
}
if (!Array.isArray(Questions)) {
    Questions = Object.values(Questions || {});
}
function rkrkEnsureRows(v){
    if (Array.isArray(v)) return v;
    if (v && typeof v === "object") return Object.values(v);
    return [];
}

// Quiz-Gesamtzeit (in Sekunden aus CSV)
var quizTimerDuration = parseInt(QuizTime || 0);
var quizTimerStart = null;
var quizTimerActive = false;

// Timer ausblenden
if (!quizTimerActive) {
    document.getElementById("timer-container").style.display = "none";
}

// MaxQuestions bestimmen
var MaxQuestions = 0;
if (Questions.length > 0) {
    if ((Questions.length < Questionair["AnzQuestions"]) || (allquestions)) {
        MaxQuestions = Questions.length;
    } else {
        MaxQuestions = Questionair["AnzQuestions"];
    }
}

// Farben
const red = "#fc2222";
const grey = "#aaaaaa";
const green = "#67b516";

// Listener für Antworten
function attachResponseListeners() {
    document.querySelectorAll("input[name='response']").forEach(el => {
        el.addEventListener("change", () => {

            if (el.type === "checkbox") {
                let checked = [...document.querySelectorAll("input[name='response']:checked")]
                    .map(x => x.value);
                Questions[AtQuestion]["Antwort"] = checked.join("|");
            }

            if (el.type === "radio") {
                Questions[AtQuestion]["Antwort"] = el.value;
            }

            document.getElementById("submit_button").style.display = 'block';
        });
    });
}
attachResponseListeners();

// =====================================
// WICHTIG: StartQuiz() – korrigiert
// =====================================
function StartQuiz() {

    let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");
    let drone = droneElement ? droneElement.value : "";

    if (name === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte gib deinen Namen ein.</div>";
        return;
    }

    if (droneElement && drone === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte wähle deine Drohne.</div>";
        return;
    }

    // ❗ NICHT DISABLEN – sonst wird Drohne NICHT übertragen!
    // droneElement.disabled = true;

    // Status setzen
    document.getElementById("Status").value = "Quiz";

    // Formular ABSENDEN → Drohne + Name werden übertragen
    document.forms[0].submit();
}

// =====================================
// Setup
// =====================================
DoSetup();

function DoSetup() {
    const quizTitleEl = document.getElementById("quiz-titel");
    const introEl = document.getElementById("indrodution");

    if (Status == 'resultat') {
        if (quizTitleEl) {
            quizTitleEl.innerHTML = Questionair["Titel"] || "";
        }
        let Auswertung = "";

        Auswertung += "Super, du hast alle Fragen beantwortet!<br><br>";
        Auswertung += "Du hast <b>" + FragenRichtig + "</b> von <b>" + AnzFragen + "</b> richtig beantwortet.<br>";
        Auswertung += "Zum Erfüllen brauchst du " + Math.floor(Erfuellt * 100) + "%.<br><br>";

        if ((FragenRichtig / AnzFragen) >= Erfuellt) {
            Auswertung += "Herzliche Gratulation! Du hast den Test erfolgreich absolviert!<br>";
        } else {
            if (Schulung) {
                Auswertung += "Leider hat es diesmal nicht gereicht. <a href='" + url + "?quiz=" + Quiz + "&" + urladder + "'>Schulung wiederholen</a><br>";
            } else {
                Auswertung += "Leider hat es diesmal nicht gereicht. <a href='" + url + "?quiz=" + Quiz + "&" + urladder + "'>Quiz wiederholen</a><br>";
            }
        }

        if (introEl) {
            introEl.innerHTML = Auswertung;
            introEl.style.fontSize = "12pt";
        }

		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		if (setting != "TEST") {
			document.getElementById("rkrk-debug").style.display = "none";
		}

        ShowHideQuestionFields('none');

		setTopbarQuickLinksVisible(true);
		showTeilnehmerinfo();

		return;
    }

    if ((set_quiz == "") || (NoQuiz == 1)) {
        if (quizTitleEl) {
            quizTitleEl.innerHTML = Schulung ?
                "Bibliothek Rehkitzrettung Knonaueramt" :
                <?php echo $onRKRS ? '"Lernecke Rehkitzrettung Schweiz"' : '"Quiz Ecke Rehkitzrettung Knonaueramt"'; ?>;
        }

        ShowQuizList();
        return;
    }

    if (quizTitleEl) {
        quizTitleEl.innerHTML = Questionair["Titel"] || "";
    }
    if (introEl) {
        introEl.style.fontSize = "10pt";
    }

    ShowLearninhaltOrQuestion(true);
}

// Header-Navigation (Assessment / Admin / …): bei Namenseingabe ausblenden
function setTopbarQuickLinksVisible(show) {
    var el = document.getElementById("rkrk-topbar-quicklinks");
    if (el) el.style.display = show ? "" : "none";
    var slot = document.getElementById("rkrk-portal-nav-slot");
    if (slot) slot.style.display = show ? "" : "none";
}

// =====================================
// Name-Formular
// =====================================
function ShowNameForm() {

    let droneText = "";
    if (<?php echo $HasDroneTypes ? "true" : "false"; ?>) {
        droneText = " und deine Drohne";
    }

    const introEl = document.getElementById("indrodution");
    if (introEl) introEl.innerHTML = `
        <div style="padding: 1em; background:#f0f0f0; border-radius:8px; max-width:400px;">
            <h3>Bitte gib deinen Namen${droneText} ein</h3>
            <p>${droneText ? "Beides ist notwendig, um das Quiz zu starten." : "Bitte gib deinen Namen ein, um das Quiz zu starten."}</p>
            <button onclick="StartQuiz()" 
                    style="border-radius: 3px; border: none; color: white; background: var(--brand); padding: 10px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer">
                Quiz starten
            </button>
        </div>
    `;

    document.getElementById("rkrk-main").style.display = "block";
    document.getElementById("Name").style.display = "block";
    document.getElementById("Name-Label").style.display = "block";

    document.getElementById("rkrk-card").style.display = "none";
    document.getElementById("rkrk-sidebar").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";
    if (setting != "TEST") {
		document.getElementById("rkrk-debug").style.display = "none";
	}
	
    let d = document.getElementById("Drone");
    let dl = document.getElementById("Drone-Label");
    if (d) d.style.display = "block";
    if (dl) dl.style.display = "block";

    setTopbarQuickLinksVisible(false);
    ShowHideQuestionFields('none');
}

function ShowNameFormAfterLearning() {

	ShowNameForm()
    
	// Lerninhalt ausblenden
    document.getElementById("learning").innerHTML = "";
    document.getElementById("submit_learn").style.display = "none";

    // Fragenfelder ausblenden
    ShowHideQuestionFields('none');

/*
    // Headerblock wieder anzeigen
    document.getElementById("quiz-titel").style.display = "block";
    document.getElementById("indrodution").style.display = "block";
    document.getElementById("logopic").style.display = "block";

    // Resultat-Titel ausblenden (sonst überschreibt er den Header)
    document.getElementById("result-titel").style.display = "none";
*/
}

function ShowLearninhaltOrQuestion(EnableLearning) {

    if ((LearningContent != "") && (EnableLearning) && (Status != "Quiz")) {
        ShowLearnContent(LearningContent);
        document.getElementById("submit_learn").style.display = 'block';
		document.getElementById("rkrk-sidebar").style.display = "none";
        Status = "Learning";
        ShowHideQuestionFields('none');
        return;
    } else {
		document.getElementById("rkrk-sidebar").style.display = "block";
	}

	// Name MUSS zuerst eingegeben werden
	if (document.getElementById("Name").value.trim() === "") {
		ShowNameForm();
		return;
	}

	setTopbarQuickLinksVisible(true);

	// QUIZ-TIMER STARTEN (nur einmal)
	if (!quizTimerActive && quizTimerDuration > 0) {
		quizTimerActive = true;
		quizTimerStart = null;
		requestAnimationFrame(runQuizTimer);
	}

	GoToQuestion(0);
    let Output = "";
    let QNum = 1;

    Questions.forEach((qu) => {
        if (MaxQuestions >= QNum) {
            Output += GetButton((QNum - 1), "Frage " + QNum, grey);
        }
        QNum++;
    });

    ShowHideQuestionFields('block');
    document.getElementById("result").innerHTML = Output;
    document.getElementById("loesungstext").innerHTML = "";
    document.getElementById("buffer").innerHTML = "";
    document.getElementById("submit_learn").style.display = 'none';
    document.getElementById("indrodution").innerHTML = QuizIntroduction;
    document.getElementById("learning").innerHTML = "";
}

// =====================================
// Lerninhalt laden
// =====================================
function ShowLearnContent(dateipfad) {

    fetch(dateipfad)
        .then(response => response.text())
        .then(text => {
            document.getElementById("learning").innerHTML = text;
        })
        .catch(() => {
            Status = "";
        });

    ShowHideQuestionFields('none');
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';
	document.getElementById("rkrk-main").style.display = 'none';
}

// =====================================
// Frage auswählen
// =====================================
function SelectQuestion(ToQuestion) {

    if (document.getElementById("Name").value.trim() === "") {
        alert("Bitte gib zuerst deinen Namen ein.");
        document.getElementById("Name").focus();
        return;
    }

    stopTimer();

    let qTime = parseInt(Questions[ToQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

    GoToQuestion(ToQuestion);
    LockWahl();
    ShowResult();
}

// =====================================
// Antwort absenden
// =====================================
function Submit() {

    stopTimer();
	if (!quizTimerActive) {
		document.getElementById("timer-container").style.display = "none";
	}

    if (Questions[AtQuestion]["Antwort"] != "") {
        Questions[AtQuestion]["Locked"] = "Ja";
        document.getElementById("submit_button").style.display = 'none';
    }

    LockWahl();
    UpdateButton(AtQuestion);
    ShowResult();
    CheckEndOfQuiz();
}

// =====================================
// Navigation
// =====================================
function NextQuestion() {
    if (AtQuestion < (MaxQuestions - 1)) AtQuestion++;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
}

function PrevQuestion() {
    if (AtQuestion > 0) AtQuestion--;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
}

// =====================================
// Antwort sperren / anzeigen
// =====================================
function LockWahl() {

    document.querySelectorAll('input[name="response"]').forEach(el => el.checked = false);

    if (Questions[AtQuestion]["Antwort"]) {

        if (Questions[AtQuestion]["Antwort"].includes("|")) {
            let parts = Questions[AtQuestion]["Antwort"].split("|");
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = parts.includes(el.value);
            });
        } else {
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = (el.value === Questions[AtQuestion]["Antwort"]);
            });
        }
    }

    let locked = (Questions[AtQuestion]["Locked"] == "Ja");

    document.querySelectorAll("#ja, #nein, #A, #B, #C, #D, #E, #F, #G, #H")
        .forEach(el => el.disabled = locked);
}

function GoToQuestion(ToQuestion) {

    AtQuestion = ToQuestion;

    stopTimer();

    let qTime = parseInt(Questions[AtQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

	// Learning section    
	document.getElementById("rkrk-card").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";

    // Navigation Buttons
    document.getElementById("prev_button").style.display = (ToQuestion < 1) ? 'none' : 'block';
    document.getElementById("next_button").style.display = (ToQuestion < (MaxQuestions - 1)) ? 'block' : 'none';

	//Telnehmerinformation
	let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");

	if (name !="") {
		document.getElementById("Name-Label").style.display = 'none';
		document.getElementById("Name").style.display = 'none';
		document.getElementById("Teilnehmerinfo").style.display = 'block';
//		document.getElementById("Name-Label").style.display = (ToQuestion < 1) ? 'none' : 'block';
//		document.getElementById("Name").style.display = (ToQuestion < 1) ? 'none' : 'block';
//		document.getElementById("Teilnehmerinfo").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Teilnehmerinfo").innerHTML = "Willkommen zum Quiz <b>" + name + "</b>";
		if (SelectedDrone!="Alle") {
			document.getElementById("Teilnehmerinfo").innerHTML += ", gewählte Drohne <b>" + SelectedDrone + "</b>";
		}
	} else {
		document.getElementById("Name-Label").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Name").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Teilnehmerinfo").style.display = (ToQuestion < 1) ? 'none' : 'block';
	}
	
    // Submit Button
    if (Questions[ToQuestion]["Locked"] == "Ja") {
        document.getElementById("submit_button").style.display = 'none';
    } else {
        if (Questions[ToQuestion]["Antwort"] == "") {
            document.getElementById("submit_button").style.display = 'none';
        } else {
            document.getElementById("submit_button").style.display = 'block';
        }
    }

    document.getElementById("close").style.display = 'none';

    // Mehrfachauswahl
    const isMulti = Questions[ToQuestion]["Loesung"].includes("|");
    document.getElementById("mehrfach").innerHTML = isMulti ? " (Mehrfachauswahl)" : "";

    ["A","B","C","D","E","F","G","H"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = isMulti ? "checkbox" : "radio";
    });

    // Auswahlfelder anzeigen
    if (Questions[ToQuestion]["Loesung"] == "Ja" || Questions[ToQuestion]["Loesung"] == "Nein") {

        document.getElementById("a-ja").style.display = 'block';
        document.getElementById("a-nein").style.display = 'block';

        ["a-a","a-b","a-c","a-d","a-e","a-f","a-g","a-h"].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

    } else {

        document.getElementById("a-ja").style.display = 'none';
        document.getElementById("a-nein").style.display = 'none';

        const labels = ["A","B","C","D","E","F","G","H"];

        labels.forEach((letter, idx) => {
            let span = document.getElementById("a-" + letter.toLowerCase());
            let label = document.getElementById("label" + letter);

            const optText = Questions[ToQuestion]["Loesungsauswahl"][idx];
            if (optText != null && String(optText).trim() !== "") {
                span.style.display = 'block';
                label.innerHTML = "<b>" + letter + "</b> <i>" + optText + "</i><br><br>";
            } else {
                span.style.display = 'none';
                label.innerHTML = "";
            }
        });
    }

    // Titel + Frage
    document.getElementById("Frage_Titel").innerHTML = "Frage " + (ToQuestion + 1);
    document.getElementById("Frage_Titel").style.fontSize = "12px";

    document.getElementById("Frage").innerHTML = "<b>" + Questions[ToQuestion]["Frage"] + "</b>";
    document.getElementById("Frage").style.fontSize = "16px";

    // Fragebild
    if (Questions[ToQuestion]["Frage-Bild"] != "") {
        document.getElementById("frage-bild").style.display = 'block';
        document.getElementById("frage-bild").src = rkrkQuizImageUrl(QuizIconLoc, Questions[ToQuestion]["Frage-Bild"]);

        if (Questions[ToQuestion]["Bildformat"] == "schmal") {
            document.getElementById("frage-bild").height = Math.floor(PicHeight / 2);
        } else {
            document.getElementById("frage-bild").height = PicHeight;
        }
    } else {
        document.getElementById("frage-bild").style.display = 'none';
    }

    // Resultatmodus
    if (Status == 'resultat') {
        document.getElementById("submit_button").style.display = 'none';

        if (document.getElementById("Name").value == "") {
            document.getElementById("noname").style.display = 'block';
            document.getElementById("close").style.display = 'none';
        } else {
            document.getElementById("noname").style.display = 'none';
            document.getElementById("close").style.display = 'block';
        }
    }

    if (setting == "TEST") {
        document.getElementById("debug").innerHTML = "Frage: " + ToQuestion;
    }
}

// =====================================
// Button erzeugen
// =====================================
function GetButton(nr, name, color) {

    let ButtonStyle = 
        "border-radius: 3px; border: none; color: white; background: " + color +
        "; padding: " + (AnzFragen > 20 ? "10px" : "5px") +
        "; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer";

    return "<button id='F" + nr + "' style='" + ButtonStyle + "' onclick='SelectQuestion(" + nr + ")'>" + name + "</button>";
}

// =====================================
// Button aktualisieren
// =====================================
function UpdateButton(nr) {

    if ((Questions[AtQuestion]["Antwort"] == "") || (Questions[AtQuestion]["Locked"] == "Nein")) {
        document.getElementById("F" + nr).style.background = grey;
    } else if (Questions[AtQuestion]["Antwort"] == Questions[AtQuestion]["Loesung"]) {
        document.getElementById("F" + nr).style.background = green;
    } else {
        document.getElementById("F" + nr).style.background = red;
    }

    if (setting == "TEST") {
        document.getElementById("debug").innerHTML =
            "Frage:" + nr + " Antwort: " + Questions[AtQuestion]["Antwort"] +
            " Lösung: " + Questions[AtQuestion]["Loesung"];
    }
}

// =====================================
// Lösung anzeigen
// =====================================
function ShowResult() {

    if ((Questions[AtQuestion]["Antwort"] == "") || (Questions[AtQuestion]["Locked"] == "Nein")) {

        document.getElementById("loesung-bild").src = "";
        document.getElementById("loesungstext").innerHTML = "";
        document.getElementById("loesung").style.display = 'none';
        document.getElementById("rkrk-solution").style.display = 'none';
    } else {

        if (Questions[AtQuestion]["Loesungs-Bild"] != "") {
            document.getElementById("loesung-bild").src = rkrkQuizImageUrl(QuizIconLoc, Questions[AtQuestion]["Loesungs-Bild"]);
            document.getElementById("loesung-bild").style.display = 'block';
        } else {
            document.getElementById("loesung-bild").style.display = 'none';
        }

        document.getElementById("loesungstext").innerHTML = Questions[AtQuestion]["Loesungstext"];

        if (Questions[AtQuestion]["Antwort"] == Questions[AtQuestion]["Loesung"]) {
            document.getElementById("korrektur").innerHTML = "Gratuliere, deine Antwort ist richtig!";
        } else {
            document.getElementById("korrektur").innerHTML = "Tut mir leid, deine Antwort ist nicht korrekt!";
        }

        document.getElementById("rkrk-solution").style.display = 'block';
        document.getElementById("loesung").style.display = 'block';
    }
}

function ShowHideQuestionFields(ShowHide) {

    document.getElementById("frage-bild").style.display = ShowHide;
    document.getElementById("Frage_Titel").style.display = ShowHide;
    document.getElementById("Frage").style.display = ShowHide;
    document.getElementById("auswahl").style.display = ShowHide;
    document.getElementById("prev_button").style.display = ShowHide;
    document.getElementById("submit_button").style.display = ShowHide;
    document.getElementById("next_button").style.display = ShowHide;
    document.getElementById("loesung").style.display = ShowHide;
    document.getElementById("loesung-bild").style.display = ShowHide;
    document.getElementById("loesungstext").style.display = ShowHide;
    document.getElementById("result").style.display = ShowHide;
    document.getElementById("result-titel").style.display = ShowHide;

    // Fehler korrigiert: 'hide' ist kein gültiger Wert
    document.getElementById("korrektur").style.display = 'none';
    document.getElementById("close").style.display = 'none';
}

function ShowQuizList() {

	setTopbarQuickLinksVisible(true);

    let QuizListText = "";

    if (Schulung == "Schulung") {
        QuizListText = "Willkommen in unserer Wissensbibliothek.<br><br>" +
            "Wir haben hier interessante Themen mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>" +
            "Wähle dir ein Thema aus, lese dich ein und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
		if (setting != "TEST") {
			document.getElementById("rkrk-debug").style.display = "none";
		}
    } else {
        QuizListText = "Willkommen in unserer Quiz-Ecke.<br><br>" +
            "Wir haben hier interessante und überraschende Online-Quiz mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>" +
            "Wähle dir ein Quiz und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-debug").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
    }

    const introEl = document.getElementById("indrodution");
    if (introEl) {
        introEl.innerHTML = QuizListText;
        introEl.style.fontSize = "12pt";
    }

    QuizListText += "<ul>";

    const rows = rkrkEnsureRows(QuizList);
    try {
    rows.forEach((qs) => {
        if (!qs || typeof qs !== "object") return;

        let isQuiz = (qs["Quiz"] == "Ja" && Schulung == "");
        let isSchulung = (qs["Schulung"] == "Ja" && Schulung == "Schulung");

        // Keep legacy visibility behavior: also allow by audience/Alle/TEST.
        let allowed =
            ((qs["Locked"] == "Nein") && (qs["Aktiv"] == "Ja")) ||
            ((qs["Audienz"] || "").indexOf(setting) !== -1) ||
            (qs["Audienz"] == "Alle") ||
            (setting == "TEST");

        if ((isQuiz || isSchulung) && allowed) {

            let remark = (qs["Locked"] == "Ja" || qs["Aktiv"] == "Nein")
                ? "<i><span style='color:red'><br>Nicht freigegeben</span></i>"
                : "";

            QuizListText += "<li>";
            QuizListText += "<b><a href='" + url + "?quiz=" + qs["Key"] + "&" + urladder + "'>" + qs["Titel"] + "</a></b> ";

            if (qs["LernDatei"] != "" && qs["FragenDatei"] != "") {
                QuizListText += "(<a href='" + url + "?quiz=" + qs["Key"] + "&Status=Quiz&" + urladder + "'>Quiz</a>)";
            }

            QuizListText += remark + "<br><br>";

            if (qs["Bild"] != "") {
                QuizListText += "<img src='" + rkrkQuizImageUrl(QuizIconTopLoc, qs["Bild"]) + "' width='" + PicWidth +
                    "' height='" + PicHeightSmall + "' style='" + PicutreStyle + "'>";
            }

            QuizListText += "<br><p>" + qs["Kurzbeschreibung"] + "</p><br><br><br>";
            QuizListText += "</li>";
        }
    });
    } catch (e) {
        QuizListText += "<li><span style='color:#b91c1c;'>Quizliste konnte nicht verarbeitet werden.</span></li>";
    }

    QuizListText += "</ul>";
    if (rows.length === 0) {
        QuizListText += "<p><i>Keine Quizdaten gefunden.</i></p>";
    }

    if (introEl) {
        introEl.innerHTML = QuizListText;
        introEl.style.fontSize = "12pt";
    }
    document.getElementById("learning").innerHTML = "";
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';

    ShowHideQuestionFields('none');
}

function showTeilnehmerinfo() {

    let name = document.getElementById("Name").value.trim();
    let info = document.getElementById("Teilnehmerinfo");

    if (name === "") return;

    // Name-Felder ausblenden
    document.getElementById("Name-Label").style.display = 'none';
    document.getElementById("Name").style.display = 'none';

    // Teilnehmerinfo anzeigen
    info.style.display = 'block';
    info.innerHTML = "Teilnehmer: <b>" + name + "</b>";

    // Drohne nur anzeigen, wenn NICHT "Alle"
    if (SelectedDrone !== "" && SelectedDrone !== "Alle") {
        info.innerHTML += ", gewählte Drohne: <b>" + SelectedDrone + "</b>";
    }
}


// =====================================
// Quiz-Ende prüfen
// =====================================
function CheckEndOfQuiz() {

    let FragenBeantwortet = 0;
    let Richtig = 0;

    // Antworten zählen
    Questions.forEach((qu) => {
        if (qu["Locked"] == "Ja") {
            FragenBeantwortet++;
            if (qu["Antwort"] == qu["Loesung"]) {
                Richtig++;
            }
        }
    });

    // Anzahl Fragen = MaxQuestions (nicht nur beantwortete!)
    document.getElementById("AnzFragen").value = MaxQuestions;
    document.getElementById("RichtigBeantwortet").value = Richtig;
    document.getElementById("Erfuellt").value = Questionair["Erfuellt"];
    document.getElementById("Quiz").value = set_quiz;

    let status = "";

    // Wenn alle Fragen beantwortet wurden → Abschlussphase
    if (FragenBeantwortet == MaxQuestions) {

        status = 'fertig';

        // Teilnehmerinfo anzeigen
        showTeilnehmerinfo();
		
        // Navigation ausblenden
        document.getElementById("prev_button").style.display = 'none';
        document.getElementById("next_button").style.display = 'none';

        // Abschluss-Button anzeigen
        document.getElementById("submit_button").style.display = 'block';
        document.getElementById("submit_button").value = 'Quiz abschliessen';
    }

    // Abschluss wurde bestätigt → Resultatmodus
    if (status == 'fertig') {

        document.getElementById("submit_button").style.display = 'none';
        status = 'resultat';

       // Teilnehmerinfo auch im Resultat anzeigen
        showTeilnehmerinfo();

        // Abschluss-Button sichtbar machen
        if (document.getElementById("Name").value.trim() !== "") {
            document.getElementById("noname").style.display = 'none';
            document.getElementById("close").style.display = 'block';
        } else {
            document.getElementById("noname").style.display = 'block';
            document.getElementById("close").style.display = 'none';
        }
    }

    document.getElementById("Status").value = status;
}

// =====================================
// Name prüfen
// =====================================
function NameEingeben() {

    if (document.getElementById("Name").value == "") {
        document.getElementById("noname").style.display = 'block';
        document.getElementById("close").style.display = 'none';
    } else {
        document.getElementById("noname").style.display = 'none';
        document.getElementById("close").style.display = 'block';
    }
}
</script>
<!-- handle hidden fields -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const params = new URLSearchParams(window.location.search);
    const role = (params.get('rolle') || "").toLowerCase();

    function enableInstruktorFields() {
        const elements = document.querySelectorAll('.instruktor-only');

        if (elements.length > 0 && role === "instruktor") {
            elements.forEach(el => {
                el.style.display = 'inline';
            });
            return true;
        }
        return false;
    }

    // 1) Sofort versuchen
    if (enableInstruktorFields()) {
        return;
    }

    // 2) Wenn Elemente später geladen werden → MutationObserver
    const observer = new MutationObserver(() => {
        if (enableInstruktorFields()) {
            observer.disconnect();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

});
</script>



</body>
</html>
