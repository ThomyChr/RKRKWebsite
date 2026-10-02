<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Nachrichten')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_nachrichten.php');
rkrk_admin_msg_nav('');

echo "<p>Versandkanäle für Newsletter, Gemeindebriefe und Feldmeldungen. Die Werte liegen in der bestehenden Mail-Konfiguration, SMTP-Passwort und Bot-Token bleiben dort gespeichert.</p>\n";
echo "<ul>\n";
echo "<li><a href='rkrk_admin_mail.php'>Mail</a> — Absender, SMTP und Testmail. Newsletter und Gemeindebriefe verwenden dieses Konto.</li>\n";
echo "<li><a href='rkrk_admin_telegram.php'>Telegram</a> — Bot, Chat und Textvorlage für eine Testnachricht.</li>\n";
echo "<li><a href='rkrk_admin_whatsapp.php'>WhatsApp</a> — Textvorlage für den Link in der Feldliste. Der Link öffnet WhatsApp, der Versand bleibt beim Bearbeiter.</li>\n";
echo "</ul>\n";
echo "</main>\n";
footer();
