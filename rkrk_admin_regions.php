<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Regionen')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_regions.php');

global $RegionSettings;
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regions']) && is_array($_POST['regions'])) {
	$overlay = array();
	foreach ($RegionSettings as $reg => $row) {
		$posted = $_POST['regions'][$reg] ?? array();
		if (!is_array($posted)) {
			continue;
		}
		$geo = is_array($row['GeoMapSetting'] ?? null) ? $row['GeoMapSetting'] : array();
		$overlay[$reg] = array(
			'Text' => rkrk_str($posted['Text'] ?? ($row['Text'] ?? '')),
			'Aktiv' => !empty($posted['Aktiv']),
			'Gebiettyp' => rkrk_str($posted['Gebiettyp'] ?? ($row['Gebiettyp'] ?? '')),
			'GeoMapSetting' => array(
				'East' => floatval($posted['East'] ?? ($geo['East'] ?? 0)),
				'North' => floatval($posted['North'] ?? ($geo['North'] ?? 0)),
				'Zoom' => floatval($posted['Zoom'] ?? ($geo['Zoom'] ?? 0)),
			),
		);
	}
	$msg = rkrk_config_save('regions', $overlay);
	if ($msg === '') {
		rkrk_config_apply_regions($RegionSettings);
		$msg = 'Gespeichert.';
	}
}

echo "<p><code>Reg</code> und <code>Dir</code> (Ordnernamen im Feldkatalog) bleiben unverändert.</p>\n";
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<form method='post'>\n";
echo "<table class='big'><tr><th>Reg</th><th>Dir (fest)</th><th>Text</th><th>Aktiv</th><th>Typ</th><th>East</th><th>North</th><th>Zoom</th></tr>\n";
foreach ($RegionSettings as $reg => $row) {
	$regH = htmlspecialchars((string)$reg, ENT_QUOTES, 'UTF-8');
	$dir = htmlspecialchars(rkrk_str($row['Dir'] ?? ''), ENT_QUOTES, 'UTF-8');
	$text = htmlspecialchars(rkrk_str($row['Text'] ?? ''), ENT_QUOTES, 'UTF-8');
	$typ = htmlspecialchars(rkrk_str($row['Gebiettyp'] ?? ''), ENT_QUOTES, 'UTF-8');
	$geo = is_array($row['GeoMapSetting'] ?? null) ? $row['GeoMapSetting'] : array();
	$east = htmlspecialchars(rkrk_str($geo['East'] ?? ''), ENT_QUOTES, 'UTF-8');
	$north = htmlspecialchars(rkrk_str($geo['North'] ?? ''), ENT_QUOTES, 'UTF-8');
	$zoom = htmlspecialchars(rkrk_str($geo['Zoom'] ?? ''), ENT_QUOTES, 'UTF-8');
	echo "<tr><td><code>{$regH}</code></td><td><code>{$dir}</code></td>";
	echo "<td><input name='regions[{$regH}][Text]' value='{$text}' size='22'></td>";
	echo "<td>";
	rkrk_admin_echo_pill('checkbox', "regions[{$regH}][Aktiv]", '1', !empty($row['Aktiv']), 'aktiv');
	echo "</td>";
	echo "<td><input name='regions[{$regH}][Gebiettyp]' value='{$typ}' size='10'></td>";
	echo "<td><input name='regions[{$regH}][East]' value='{$east}' size='12'></td>";
	echo "<td><input name='regions[{$regH}][North]' value='{$north}' size='12'></td>";
	echo "<td><input name='regions[{$regH}][Zoom]' value='{$zoom}' size='6'></td></tr>\n";
}
echo "</table>\n";
echo "<p><button type='submit'>Speichern</button></p>\n";
echo "</form>\n</main>\n";
footer();
