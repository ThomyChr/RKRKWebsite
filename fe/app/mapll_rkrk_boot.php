<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/mapll_env.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$folderoffset = '../../';
$MenuDirOffset = $folderoffset;
$rkrkIntegrated = mapll_bootstrap_rkrk($folderoffset);
$OnRKRS = mapll_is_rkrs_env();

if (!$rkrkIntegrated) {
    if (!isset($LoggedOn)) {
        $LoggedOn = false;
    }
    if (!isset($Layout)) {
        $Layout = 2023;
    }
    if (!isset($RegionSelected)) {
        $RegionSelected = '';
    }
    if (!isset($StandardSettingMap)) {
        $StandardSettingMap = '';
    }
    if (!function_exists('GetAccess')) {
        function GetAccess($level = '')
        {
            return false;
        }
    }
    if (!function_exists('CreateSiteLink')) {
        function CreateSiteLink($name, $qs = '')
        {
            return '';
        }
    }
}

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = ($rkrkIntegrated && !$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
