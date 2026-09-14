<?php
/**
 * DABS overlay API: Skyguide Daily Airspace Bulletin as GeoJSON polygons/circles.
 * Source: https://www.skybriefing.com/o/dabs?today|tomorrow
 *
 * Site wrapper: auth + scrape core. map/ and mapII/ have their own entrypoints
 * so they can run standalone without this file.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}
include_once __DIR__ . '/scripts/rkrk_gen.php';
include_once __DIR__ . '/rkrk_access_control.php';

if (php_sapi_name() !== 'cli' && function_exists('CheckIPandExit')) {
	CheckIPandExit();
}

require_once __DIR__ . '/scripts/rkrk_dabs_core.php';
rkrk_dabs_emit();
exit;
