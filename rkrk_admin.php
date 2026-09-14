<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Konfiguration')) {
	exit;
}
rkrk_admin_nav('rkrk_admin.php');

$dir = rkrk_config_dir();
$exists = is_dir($dir);
echo "<p>JSON-Overlay (optional) unter <code>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . "</code>";
echo $exists ? " — Ordner vorhanden." : " — noch kein Ordner; wird beim ersten Speichern angelegt.";
echo "</p>\n";
echo "<p>Ohne JSON gelten weiterhin die PHP-Arrays. Missionsordner und MDB-Schlüssel (<code>3T</code>, <code>4TD</code>, …) sind nicht umbenennbar.</p>\n";
echo "<ul>\n";
echo "<li><a href='rkrk_admin_missions.php'>Missionsbibliotheken</a> — Name, aktiv, Freigabe</li>\n";
echo "<li><a href='rkrk_admin_regions.php'>Regionen</a> — Aktiv, Text, Kartenausschnitt (<code>Dir</code> bleibt)</li>\n";
echo "<li><a href='rkrk_admin_pilots.php'>Piloten</a> — Kürzel, Sichtbarkeit, PV-Datensätze (EASA, Tel, Foto)</li>\n";
echo "<li><a href='rkrk_admin_menu.php'>Menü</a> — sichtbar, Reihenfolge, Freigabe, Personen, Icon</li>\n";
echo "<li><a href='rkrk_admin_branding.php'>Branding / Karte</a> — Default-Farbe, GeoAdmin-URL</li>\n";
echo "<li><a href='rkrk_admin_unterhalt.php'>Unterhalt</a> — Katalog-Backup, Website-Sicherung, Code-Sync (Steuerung: test27)</li>\n";
echo "</ul>\n";
echo "</main>\n";
footer();
