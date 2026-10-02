<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');
include_once('scripts/rkrk_channels.php');

CheckIPandExit();
if (!rkrk_admin_boot('Telegram')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_telegram.php');
rkrk_admin_msg_nav('rkrk_admin_telegram.php');

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	if ($action === 'save_settings') {
		$user = ltrim(rkrk_channels_text($_POST['telegramBotUsername'] ?? '', 64), '@');
		if ($user !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_]{3,}$/', $user)) {
			$err = 'Bot-Benutzername: Buchstaben, Ziffern und Unterstrich, mindestens 4 Zeichen.';
		} else {
			$chat = rkrk_channels_text($_POST['telegramChatId'] ?? '', 64);
			$ok = rkrk_channels_save(array(
				'telegramEnabled' => !empty($_POST['telegramEnabled']),
				'telegramBotUsername' => $user,
				'telegramBotToken' => trim((string)($_POST['telegramBotToken'] ?? '')),
				'telegramChatId' => $chat,
				'telegramTemplate' => rkrk_channels_text($_POST['telegramTemplate'] ?? '', 500),
			), array('telegramBotToken'));
			$msg = $ok ? 'Telegram-Einstellungen gespeichert.' : 'Speichern fehlgeschlagen.';
		}
	}
	if ($action === 'test_telegram') {
		$settings = rkrk_channels_settings();
		$tpl = trim((string)($settings['telegramTemplate'] ?? ''));
		if ($tpl === '') {
			$tpl = 'Feldmeldung für Rehkitzrettung mit Drohnen, Feld: {feld}';
		}
		$text = str_replace('{feld}', 'Test ' . date('Y-m-d H:i:s'), $tpl);
		$result = rkrk_telegram_send($settings['telegramChatId'] ?? '', $text);
		if (!empty($result['ok'])) {
			$msg = 'Testnachricht gesendet.';
		} else {
			$err = (string)($result['error'] ?? 'Versand fehlgeschlagen.');
		}
	}
}

$s = rkrk_channels_settings();
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
if ($err !== '') {
	echo "<p><b>" . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<p>Telegram kennt keine Telefon-Links wie WhatsApp. Eine Nachricht geht an einen Bot, den der Empfänger einmal gestartet hat, oder an eine Chat-ID. Ein leeres Token-Feld lässt das gespeicherte Token stehen.</p>\n";
$hasToken = trim((string)($s['telegramBotToken'] ?? '')) !== '';
echo "<p>" . ($hasToken ? 'Ein Bot-Token ist gespeichert.' : 'Noch kein Bot-Token gespeichert.') . "</p>\n";
echo "<form class='rkrk-admin-form' method='post'>\n";
echo "<input type='hidden' name='action' value='save_settings'>";
echo "<label><input type='checkbox' name='telegramEnabled' value='1'" . (!empty($s['telegramEnabled']) ? ' checked' : '') . "> Telegram aktiv</label>";
echo "<label>Bot-Benutzername</label><input type='text' name='telegramBotUsername' value='" . htmlspecialchars((string)$s['telegramBotUsername'], ENT_QUOTES, 'UTF-8') . "' placeholder='RehkitzBot'>";
echo "<label>Bot-Token</label><input type='password' name='telegramBotToken' value='' autocomplete='new-password'>";
echo "<label>Chat-ID für Test und Meldungen</label><input type='text' name='telegramChatId' value='" . htmlspecialchars((string)$s['telegramChatId'], ENT_QUOTES, 'UTF-8') . "' placeholder='-1001234567890 oder @kanal'>";
echo "<label>Textvorlage</label><textarea name='telegramTemplate'>" . htmlspecialchars((string)$s['telegramTemplate'], ENT_QUOTES, 'UTF-8') . "</textarea>";
echo "<p><code>{feld}</code> wird durch die Feldbezeichnung ersetzt.</p>";
echo "<p><button type='submit'>Speichern</button></p></form>\n";
echo "<h2>Testnachricht</h2>\n";
echo "<form method='post'><input type='hidden' name='action' value='test_telegram'>";
echo "<p>Sendet die Vorlage mit einem Test-Feld an die gespeicherte Chat-ID.</p>";
echo "<p><button type='submit'>Testnachricht senden</button></p></form>\n";
echo "</main>\n";
footer();
