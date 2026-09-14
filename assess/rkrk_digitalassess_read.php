<?php
/**
 * Lesemodus: weitergeleitet auf rkrk_digitalassess.php?read_only=1 …
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset . 'scripts/rkrk_inputforms.php');
include_once($folderoffset . 'scripts/rkrk_csvfiles.php');
include_once('rkrk_assess_common.php');

if (!$onRKRS) {
	CheckIPandExit($folderoffset);
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "", "", "", "",$folderoffset);

	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	GrantAccess();
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

$merged = rkrk_assess_merge_query_with_setting($_GET);
if (isset($merged['selected_quiz']) && !isset($merged['quiz'])) {
	$merged['quiz'] = $merged['selected_quiz'];
	unset($merged['selected_quiz']);
}
$merged['read_only'] = '1';
$query = http_build_query($merged);
$target = rkrk_assess_append_setting_href("rkrk_digitalassess.php" . ($query !== '' ? ('?' . $query) : ''));
header("Location: " . $target, true, 302);
exit;
