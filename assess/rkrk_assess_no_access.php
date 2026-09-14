<?php
/**
 * Sichtbare Rückmeldung bei fehlgeschlagener cs/Hash-Zugriffsprüfung (portal.rehkitzrettung.ch).
 * Direktaufruf ohne Session: klarer Hinweis statt verwirrendem «direct_access».
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

define('RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP', true);
$folderoffset = '../';
include_once __DIR__ . '/rkrk_assess_common.php';

http_response_code(403);
header('Content-Type: text/html; charset=utf-8');

$denyResult = null;
if (isset($_SESSION['rkrkAssessHashGateDeny']) && is_array($_SESSION['rkrkAssessHashGateDeny'])) {
	$denyResult = $_SESSION['rkrkAssessHashGateDeny'];
	unset($_SESSION['rkrkAssessHashGateDeny']);
}

if (is_array($denyResult)) {
	echo rkrk_assess_hash_gate_deny_page_html($denyResult);
	exit;
}

echo rkrk_assess_hash_gate_deny_page_html(array(
	'ok' => false,
	'reason' => 'direct_access',
	'scope' => '',
	'quiz' => '',
	'rule_found' => false,
	'required_fields' => array(),
	'missing_field' => null,
	'payload' => null,
	'client_cs' => '',
	'expected_cs' => '',
));
