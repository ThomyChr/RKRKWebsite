<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Missionsbibliotheken')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_missions.php');

global $MissionDroneTypes;
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['missions']) && is_array($_POST['missions'])) {
	$overlay = array();
	foreach ($MissionDroneTypes as $mdb => $row) {
		$posted = $_POST['missions'][$mdb] ?? array();
		if (!is_array($posted)) {
			continue;
		}
		$overlay[$mdb] = array(
			'Name' => rkrk_str($posted['Name'] ?? ($row['Name'] ?? '')),
			'release' => rkrk_str($posted['release'] ?? ($row['release'] ?? '')),
			'active' => !empty($posted['active']),
		);
	}
	$msg = rkrk_config_save('missions', $overlay);
	if ($msg === '') {
		rkrk_config_apply_missions($MissionDroneTypes);
		$msg = 'Gespeichert.';
	}
}

echo "<p>Ordnerpfade (<code>MissionLocation</code> / <code>DocLocation</code>) und MDB-Schlüssel sind fest. Speichern schreibt nur Name, aktiv und Freigabe nach <code>rkrk/config/missions.json</code>. Ein Feldkatalog wird nur hier in der Website-Konfiguration geführt, nicht im Feldeditor: ein neuer Katalog hängt an Missionsordner, Freigabe, Karte und Konvertierung.</p>\n";
echo "<p>Feldkatalog vorhanden: Mavic 3T, Matrice 4TD, Mavic 3T Debug. Keinen Feldkatalog haben Matrice 4T, Matrice 3TD und Mavic 3TA. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Die vorhandenen Missionsordner bleiben.</p>\n";
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<form method='post'>\n";
echo "<table class='big'><tr><th>MDB</th><th>Name</th><th>Aktiv</th><th>Freigabe</th><th>Missionen (fest)</th><th>Docs (fest)</th></tr>\n";
foreach ($MissionDroneTypes as $mdb => $row) {
	$mdbH = htmlspecialchars((string)$mdb, ENT_QUOTES, 'UTF-8');
	$name = htmlspecialchars(rkrk_str($row['Name'] ?? ''), ENT_QUOTES, 'UTF-8');
	$rel = htmlspecialchars(rkrk_str($row['release'] ?? ''), ENT_QUOTES, 'UTF-8');
	$ml = htmlspecialchars(rkrk_str($row['MissionLocation'] ?? ''), ENT_QUOTES, 'UTF-8');
	$dl = htmlspecialchars(rkrk_str($row['DocLocation'] ?? ''), ENT_QUOTES, 'UTF-8');
	echo "<tr><td><code>{$mdbH}</code></td>";
	echo "<td><input name='missions[{$mdbH}][Name]' value='{$name}' size='28'></td>";
	echo "<td>";
	rkrk_admin_echo_pill('checkbox', "missions[{$mdbH}][active]", '1', !empty($row['active']), 'aktiv');
	echo "</td>";
	echo "<td><input name='missions[{$mdbH}][release]' value='{$rel}' size='36'></td>";
	echo "<td><code>{$ml}</code></td><td><code>{$dl}</code></td></tr>\n";
}
echo "</table>\n";
echo "<p><button type='submit'>Speichern</button></p>\n";
echo "</form>\n</main>\n";
footer();
