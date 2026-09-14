<?php
/**
 * Site chrome for ar/public/: headerpage + main menu (CreateTopMenu).
 * Call rkrk_ar_boot() once at the top, rkrk_ar_emit_chrome() before HTML output.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = '../../';
include_once $folderoffset . 'scripts/rkrk_gen.php';
include_once $folderoffset . 'scripts/rkrk_menu.php';
include_once $folderoffset . 'rkrk_access_control.php';

function rkrk_ar_boot(): void
{
	if (function_exists('CheckIPandExit')) {
		CheckIPandExit();
	}
}

function rkrk_ar_emit_chrome(string $extraCss = 'styles.css'): void
{
	global $folderoffset, $LoggedOn;
	headerpage('Drohnenrehkitzrettung.ch', false, $folderoffset);
	if (function_exists('GrantAccess')) {
		GrantAccess();
	}
	if (empty($LoggedOn) && function_exists('RegisterForm')) {
		RegisterForm(getIPAddress(), 'Login', true);
		exit;
	}
	if (function_exists('CreateTopMenu')) {
		CreateTopMenu($folderoffset);
	}
	if ($extraCss !== '') {
		$href = $extraCss;
		if (strpos($href, '?') === false) {
			$href .= '?v=20260907gap';
		}
		echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
	}
}
