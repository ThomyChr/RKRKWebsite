<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Branding / Karte')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_branding.php');

global $MapGeoAdminURL, $BaseColor;
$branding = rkrk_config_branding();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$color = strtolower(rkrk_str($_POST['default_color'] ?? 'white'));
	$url = rkrk_str($_POST['map_geo_admin_url'] ?? '');
	$data = array(
		'default_color' => ($color === 'green') ? 'green' : 'white',
		'map_geo_admin_url' => $url,
	);
	$msg = rkrk_config_save('branding', $data);
	if ($msg === '') {
		$branding = rkrk_config_branding();
		if ($branding['map_geo_admin_url'] !== '') {
			$MapGeoAdminURL = $branding['map_geo_admin_url'];
		}
		$msg = 'Gespeichert. Farbe gilt für Aufrufe ohne Color=; Layout bleibt 2023.';
	}
}

$colorNow = htmlspecialchars($branding['default_color'] ?: 'white', ENT_QUOTES, 'UTF-8');
$urlNow = htmlspecialchars($branding['map_geo_admin_url'] !== '' ? $branding['map_geo_admin_url'] : rkrk_str($MapGeoAdminURL), ENT_QUOTES, 'UTF-8');
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<form method='post'>\n";
echo "<p>Default-Farbe (ohne Query <code>Color=</code>): <span class='rkrk-sels rkrk-reg-pills'>";
rkrk_admin_echo_pill('radio', 'default_color', 'white', $colorNow === 'white', 'white');
rkrk_admin_echo_pill('radio', 'default_color', 'green', $colorNow === 'green', 'green');
echo "</span></p>\n";
echo "<p>GeoAdmin-Basis-URL<br><input name='map_geo_admin_url' value='{$urlNow}' size='80'></p>\n";
echo "<p>Query <code>Color=white|green</code> und <code>MenuOn=</code> bleiben unverändert steuerbar. Layout-Schale ist fest 2023.</p>\n";
echo "<p><button type='submit'>Speichern</button></p>\n";
echo "</form>\n</main>\n";
footer();
