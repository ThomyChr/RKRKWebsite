<?php
/**
 * AJAX-API: Checklist-Form-Fortschritt speichern / prüfen / löschen.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_inputforms.php";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_assess_checklist_progress.inc.php";

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
	"kind" => (string) ($body["kind"] ?? $_REQUEST["kind"] ?? "assess"),
	"file" => (string) ($body["file"] ?? $_REQUEST["file"] ?? ""),
	"edit" => (string) ($body["edit"] ?? $_REQUEST["edit"] ?? ""),
	"draft_id" => (string) ($body["draft_id"] ?? $_REQUEST["draft_id"] ?? ""),
);

if ($action === "check") {
	echo json_encode(rkrk_assess_checklist_progress_check($identity), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($action === "save") {
	$payload = $identity;
	$payload["fields"] = isset($body["fields"]) && is_array($body["fields"]) ? $body["fields"] : array();
	$payload["weather_json"] = (string) ($body["weather_json"] ?? "");
	echo json_encode(rkrk_assess_checklist_progress_save($payload), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($action === "delete") {
	$ok = rkrk_assess_checklist_progress_delete($identity);
	echo json_encode(array("ok" => $ok), JSON_UNESCAPED_UNICODE);
	exit;
}

http_response_code(400);
echo json_encode(array("ok" => false, "error" => "Unbekannte action."), JSON_UNESCAPED_UNICODE);
