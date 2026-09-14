<?php
/**
 * AJAX-API: Quiz-Fortschritt speichern / prüfen / löschen.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_inputforms.php";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_assess_quiz_progress.inc.php";
if (function_exists("rkrk_assess_quiz_progress_purge_old_maybe")) {
	rkrk_assess_quiz_progress_purge_old_maybe();
}

header("Content-Type: application/json; charset=UTF-8");

if (!$onRKRS) {
	if (function_exists("CheckIPandExit")) {
		CheckIPandExit($folderoffset);
	}
	if (function_exists("GrantAccess")) {
		GrantAccess($folderoffset);
	}
}

$action = isset($_REQUEST["action"]) ? trim((string) $_REQUEST["action"]) : "";
$body = array();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$raw = file_get_contents("php://input");
	if (is_string($raw) && trim($raw) !== "") {
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$body = $decoded;
		}
	}
	if ($body === array()) {
		$body = $_POST;
	}
}

$identity = array(
	"quiz_key" => (string) ($body["quiz_key"] ?? $_REQUEST["quiz_key"] ?? ""),
	"name" => (string) ($body["name"] ?? $_REQUEST["name"] ?? ""),
	"drone" => (string) ($body["drone"] ?? $_REQUEST["drone"] ?? ""),
	"setting" => (string) ($body["setting"] ?? $_REQUEST["setting"] ?? ""),
	"schulung" => (string) ($body["schulung"] ?? $_REQUEST["schulung"] ?? ""),
	"quiz_modus" => (string) ($body["quiz_modus"] ?? $_REQUEST["quiz_modus"] ?? ""),
	"exam_code" => (string) ($body["exam_code"] ?? $body["EXAM"] ?? $_REQUEST["EXAM"] ?? ""),
	"rkrs_pilot_id" => (string) ($body["rkrs_pilot_id"] ?? $body["RKRSPilotID"] ?? $body["PilotID"] ?? $_REQUEST["PilotID"] ?? ""),
);

if ($action === "check") {
	$fp = trim((string) ($body["question_fingerprint"] ?? $_REQUEST["question_fingerprint"] ?? ""));
	echo json_encode(rkrk_assess_quiz_progress_check($identity, $fp), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($action === "save") {
	echo json_encode(rkrk_assess_quiz_progress_save($body), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($action === "delete") {
	$ok = rkrk_assess_quiz_progress_delete($identity);
	echo json_encode(array("ok" => $ok), JSON_UNESCAPED_UNICODE);
	exit;
}

http_response_code(400);
echo json_encode(array("ok" => false, "error" => "Unbekannte action."), JSON_UNESCAPED_UNICODE);
