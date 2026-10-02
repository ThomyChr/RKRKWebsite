<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');
include_once('scripts/rkrk_channels.php');

CheckIPandExit();
if (!rkrk_admin_boot('Mail')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_mail.php');
rkrk_admin_msg_nav('rkrk_admin_mail.php');

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	if ($action === 'save_settings') {
		$port = (int)($_POST['smtpPort'] ?? 587);
		if ($port < 1 || $port > 65535) {
			$port = 587;
		}
		$secure = (string)($_POST['smtpSecure'] ?? 'starttls');
		$ok = rkrk_channels_save(array(
			'fromName' => rkrk_channels_text($_POST['fromName'] ?? '', 120),
			'fromEmail' => trim((string)($_POST['fromEmail'] ?? '')),
			'unsubscribeEmail' => trim((string)($_POST['unsubscribeEmail'] ?? '')),
			'smtpHost' => rkrk_channels_text($_POST['smtpHost'] ?? '', 200),
			'smtpPort' => $port,
			'smtpSecure' => in_array($secure, array('starttls', 'ssl', 'none'), true) ? $secure : 'starttls',
			'smtpUser' => rkrk_channels_text($_POST['smtpUser'] ?? '', 200),
			'smtpPass' => (string)($_POST['smtpPass'] ?? ''),
		), array('smtpPass'));
		$msg = $ok ? 'Mail-Einstellungen gespeichert.' : 'Speichern fehlgeschlagen.';
	}
	if ($action === 'test_mail') {
		$settings = rkrk_channels_settings();
		$to = trim((string)($settings['fromEmail'] ?? ''));
		if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$err = 'In der Mail-Konfiguration ist keine gültige Absender-Adresse hinterlegt.';
		} else {
			$html = '<p>Testmail aus der Konfiguration.</p><p>Empfänger ist der Absender der Mail-Konfiguration.</p><p>' . nl_h(date('Y-m-d H:i:s')) . '</p>';
			$result = nl_send_mail($to, '', 'Test Newsletter Rehkitzrettung', $html);
			nl_log_mail('test', $to, 'Test Newsletter Rehkitzrettung', $result['ok'], $result['error']);
			if (!empty($result['ok'])) {
				$msg = 'Testmail gesendet an ' . $to;
			} else {
				$err = (string)($result['error'] ?? 'Versand fehlgeschlagen.');
			}
		}
	}
}

$s = rkrk_channels_settings();
if ($msg !== '') {
	echo "<p><b>" . nl_h($msg) . "</b></p>\n";
}
if ($err !== '') {
	echo "<p><b>" . nl_h($err) . "</b></p>\n";
}
echo "<p>Diese Website läuft mit PHP " . nl_h(PHP_VERSION) . ". OpenSSL ist hier " . (nl_php_has_tls() ? 'aktiv' : 'nicht geladen') . ".</p>\n";
if (!nl_php_has_tls()) {
	echo "<p><b>" . nl_h(nl_php_tls_hint()) . "</b></p>\n";
}
echo "<p>Newsletter und Gemeindebriefe versenden über dieses Konto. PHP mail() auf dem NAS ist nicht der Weg. Ein leeres Passwortfeld lässt das gespeicherte Passwort stehen.</p>\n";
echo "<form class='rkrk-admin-form' method='post'>\n";
echo "<input type='hidden' name='action' value='save_settings'>";
echo "<label>Absender-Name</label><input type='text' name='fromName' value='" . nl_h($s['fromName']) . "'>";
echo "<label>Absender-E-Mail</label><input type='email' name='fromEmail' value='" . nl_h($s['fromEmail']) . "'>";
echo "<label>Abmelden an</label><input type='email' name='unsubscribeEmail' value='" . nl_h($s['unsubscribeEmail']) . "'>";
echo "<label>SMTP-Host</label><input type='text' name='smtpHost' value='" . nl_h($s['smtpHost']) . "'>";
echo "<label>Port</label><input type='number' name='smtpPort' value='" . nl_h($s['smtpPort']) . "'>";
echo "<label>Verschlüsselung</label><select name='smtpSecure'>";
foreach (array('starttls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'keine') as $val => $label) {
	echo "<option value='" . nl_h($val) . "'" . ($s['smtpSecure'] === $val ? ' selected' : '') . ">" . nl_h($label) . "</option>";
}
echo "</select>";
echo "<label>SMTP-Benutzer</label><input type='text' name='smtpUser' value='" . nl_h($s['smtpUser']) . "'>";
echo "<label>SMTP-Passwort</label><input type='password' name='smtpPass' value='' autocomplete='new-password'>";
echo "<p><button type='submit'>Speichern</button></p></form>\n";

echo "<h2>Testmail</h2>\n";
echo "<form method='post'><input type='hidden' name='action' value='test_mail'>";
echo "<p>Geht an den Absender: <strong>" . nl_h($s['fromEmail']) . "</strong></p>";
echo "<p><button type='submit'>Testmail senden</button></p></form>\n";

$log = array_reverse(nl_items('newsletter_log.json'));
echo "<h2>Versandlog</h2>\n<table class='big'><tr><th>Zeit</th><th>Art</th><th>An</th><th>OK</th><th>Fehler</th></tr>";
foreach (array_slice($log, 0, 30) as $line) {
	echo "<tr><td>" . nl_h($line['time'] ?? '') . "</td><td>" . nl_h($line['kind'] ?? '') . "</td><td>" . nl_h($line['to'] ?? '') . "</td><td>" . nl_h($line['ok'] ?? '') . "</td><td>" . nl_h($line['error'] ?? '') . "</td></tr>";
}
echo "</table>\n</main>\n";
footer();
