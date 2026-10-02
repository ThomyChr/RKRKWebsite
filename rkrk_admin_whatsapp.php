<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');
include_once('scripts/rkrk_channels.php');

CheckIPandExit();
if (!rkrk_admin_boot('WhatsApp')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_whatsapp.php');
rkrk_admin_msg_nav('rkrk_admin_whatsapp.php');

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$ok = rkrk_channels_save(array(
		'whatsappEnabled' => !empty($_POST['whatsappEnabled']),
		'whatsappTemplate' => rkrk_channels_text($_POST['whatsappTemplate'] ?? '', 500),
	));
	if ($ok) {
		$msg = 'WhatsApp-Einstellungen gespeichert.';
	} else {
		$err = 'Speichern fehlgeschlagen.';
	}
}

$s = rkrk_channels_settings();
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
if ($err !== '') {
	echo "<p><b>" . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<p>Die Feldliste setzt neben Telefon und Mail einen WhatsApp-Link. Er öffnet WhatsApp mit dem Text; abgeschickt wird erst in WhatsApp. Nummern im Format <code>079…</code>, <code>+41…</code> oder <code>0041…</code> werden dafür auf die Landesvorwahl 41 gebracht. Bei mehreren Nummern gilt die erste.</p>\n";
echo "<form class='rkrk-admin-form' method='post'>\n";
echo "<label><input type='checkbox' name='whatsappEnabled' value='1'" . (!empty($s['whatsappEnabled']) ? ' checked' : '') . "> WhatsApp-Link in der Feldliste anzeigen</label>";
echo "<label>Textvorlage</label><textarea name='whatsappTemplate'>" . htmlspecialchars((string)$s['whatsappTemplate'], ENT_QUOTES, 'UTF-8') . "</textarea>";
echo "<p><code>{feld}</code> wird durch Feld-ID, Feldname und Flurname ersetzt.</p>";
echo "<p><button type='submit'>Speichern</button></p></form>\n";
echo "</main>\n";
footer();
