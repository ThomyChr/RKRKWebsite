<?php
/**
 * Mail, Telegram und WhatsApp: gemeinsamer Speicher in newsletter_settings.json.
 * SMTP bleibt dort, damit Newsletter und Gemeindebriefe unverändert versenden.
 */

if (!function_exists('rkrk_channels_boot')) {
	function rkrk_channels_boot() {
		if (!function_exists('nl_settings')) {
			require_once dirname(__DIR__) . '/newsletter/rkrk_newsletter_lib.php';
		}
	}
}

if (!function_exists('rkrk_channels_settings')) {
	function rkrk_channels_settings() {
		rkrk_channels_boot();
		return nl_settings();
	}
}

if (!function_exists('rkrk_channels_text')) {
	function rkrk_channels_text($value, $max = 500) {
		$s = str_replace(array("\r\n", "\r"), "\n", (string)$value);
		$s = str_replace("\0", '', $s);
		$s = trim($s);
		if (function_exists('mb_substr')) {
			return mb_substr($s, 0, $max);
		}
		return substr($s, 0, $max);
	}
}

if (!function_exists('rkrk_channels_save')) {
	function rkrk_channels_save(array $posted, array $secretKeys = array()) {
		rkrk_channels_boot();
		$current = nl_settings();
		foreach ($posted as $key => $value) {
			if (in_array($key, $secretKeys, true) && trim((string)$value) === '') {
				continue;
			}
			$current[$key] = $value;
		}
		return nl_write_json('newsletter_settings.json', $current);
	}
}

if (!function_exists('rkrk_whatsapp_digits')) {
	function rkrk_whatsapp_digits($phone) {
		$phone = trim((string)$phone);
		if ($phone === '') {
			return '';
		}
		$parts = preg_split('/[,;\/]+/', $phone);
		$first = trim((string)($parts[0] ?? ''));
		$digits = preg_replace('/\D+/', '', $first);
		if ($digits === '') {
			return '';
		}
		if (strpos($digits, '00') === 0) {
			$digits = substr($digits, 2);
		}
		if ($digits[0] === '0') {
			$digits = '41' . substr($digits, 1);
		} elseif (strlen($digits) === 9 && $digits[0] === '7') {
			$digits = '41' . $digits;
		}
		if (strlen($digits) < 8) {
			return '';
		}
		return $digits;
	}
}

if (!function_exists('rkrk_whatsapp_href')) {
	function rkrk_whatsapp_href($phone, $feld) {
		$s = rkrk_channels_settings();
		if (empty($s['whatsappEnabled'])) {
			return '';
		}
		$digits = rkrk_whatsapp_digits($phone);
		if ($digits === '') {
			return '';
		}
		$tpl = trim((string)($s['whatsappTemplate'] ?? ''));
		if ($tpl === '') {
			$tpl = 'Feldmeldung für Rehkitzrettung mit Drohnen, Feld: {feld}';
		}
		$text = str_replace('{feld}', trim((string)$feld), $tpl);
		return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
	}
}

if (!function_exists('rkrk_whatsapp_anchor')) {
	function rkrk_whatsapp_anchor($phone, $feld, $iconSrc, $height) {
		$href = rkrk_whatsapp_href($phone, $feld);
		if ($href === '') {
			return '';
		}
		return " <a href='" . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . "'><img src='" . htmlspecialchars((string)$iconSrc, ENT_QUOTES, 'UTF-8') . "' height='" . (int)$height . "'></a>";
	}
}

if (!function_exists('rkrk_telegram_send')) {
	function rkrk_telegram_send($chatId, $text) {
		$s = rkrk_channels_settings();
		$token = trim((string)($s['telegramBotToken'] ?? ''));
		if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{20,}$/', $token)) {
			return array('ok' => false, 'error' => 'Bot-Token fehlt oder hat nicht die übliche Form.');
		}
		$chatId = trim((string)$chatId);
		if (!preg_match('/^(-?\d{4,}|@[A-Za-z][A-Za-z0-9_]{3,})$/', $chatId)) {
			return array('ok' => false, 'error' => 'Chat-ID fehlt. Erlaubt ist eine numerische ID oder @kanal.');
		}
		$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
		$body = http_build_query(array(
			'chat_id' => $chatId,
			'text' => (string)$text,
		));
		$raw = '';
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			$raw = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
			if ($raw === false) {
				return array('ok' => false, 'error' => 'Telegram nicht erreichbar: ' . $err);
			}
		} else {
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
					'content' => $body,
					'timeout' => 20,
				),
			));
			$raw = @file_get_contents($url, false, $ctx);
			if ($raw === false) {
				return array('ok' => false, 'error' => 'Telegram nicht erreichbar.');
			}
		}
		$data = json_decode((string)$raw, true);
		if (!is_array($data) || empty($data['ok'])) {
			$desc = is_array($data) ? (string)($data['description'] ?? '') : '';
			return array('ok' => false, 'error' => $desc !== '' ? $desc : 'Telegram hat die Nachricht abgelehnt.');
		}
		return array('ok' => true, 'error' => '');
	}
}
