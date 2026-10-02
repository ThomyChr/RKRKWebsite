<?php
/**
 * SMTP-Versand für Newsletter, Gemeindebriefe und andere Seiten.
 * Aufruf: rkrk_mail_send($settings, $to, $cc, $subject, $html, $inline, $attachments)
 * $settings: smtpHost, smtpPort, smtpSecure (ssl|starttls|none), smtpUser, smtpPass, fromEmail, fromName
 * $inline: Liste array('cid' => '...', 'path' => '...'), $attachments: Dateipfade
 */

function rkrk_mail_smtp_read($fp) {
	$data = '';
	while (!feof($fp)) {
		$line = fgets($fp, 515);
		if ($line === false) {
			break;
		}
		$data .= $line;
		if (isset($line[3]) && $line[3] === ' ') {
			break;
		}
	}
	return array((int)substr($data, 0, 3), $data);
}

function rkrk_mail_smtp_expect($fp, $cmd, $okCodes) {
	if ($cmd !== null) {
		fwrite($fp, $cmd . "\r\n");
	}
	list($code, $data) = rkrk_mail_smtp_read($fp);
	if (!in_array($code, $okCodes, true)) {
		throw new RuntimeException(trim($cmd . ' -> ' . $data));
	}
	return $data;
}

function rkrk_mail_encode_header($value) {
	$value = trim(str_replace(array("\r", "\n"), '', (string)$value));
	if (preg_match('/[^\x20-\x7E]/', $value)) {
		return '=?UTF-8?B?' . base64_encode($value) . '?=';
	}
	return $value;
}

function rkrk_mail_dot_stuff($body) {
	$body = str_replace(array("\r\n", "\r"), "\n", $body);
	$body = str_replace("\n", "\r\n", $body);
	$body = preg_replace('/^\./m', '..', $body);
	return $body;
}

function rkrk_mail_has_tls() {
	$transports = function_exists('stream_get_transports') ? stream_get_transports() : array();
	foreach ($transports as $name) {
		if (strcasecmp($name, 'ssl') === 0 || strcasecmp($name, 'tls') === 0) {
			return true;
		}
	}
	return extension_loaded('openssl');
}

function rkrk_mail_tls_hint() {
	return 'PHP auf dem NAS hat OpenSSL nicht geladen. Verschlüsseltes SMTP (Port 465 oder 587) geht so nicht. In der Web Station beim PHP-Profil dieser Website die Erweiterung openssl aktivieren, die Seite neu laden und die Testmail erneut senden.';
}

function rkrk_mail_mime($ext) {
	$ext = strtolower((string)$ext);
	if ($ext === 'png') {
		return 'image/png';
	}
	if ($ext === 'jpg' || $ext === 'jpeg') {
		return 'image/jpeg';
	}
	if ($ext === 'gif') {
		return 'image/gif';
	}
	if ($ext === 'webp') {
		return 'image/webp';
	}
	if ($ext === 'pdf') {
		return 'application/pdf';
	}
	return 'application/octet-stream';
}

function rkrk_mail_send($settings, $to, $cc, $subject, $html, $inline = array(), $attachments = array()) {
	$settings = is_array($settings) ? $settings : array();
	$host = trim((string)($settings['smtpHost'] ?? ''));
	if ($host === '') {
		return array('ok' => false, 'error' => 'SMTP-Host ist leer. Zuerst unter Intern / Konfiguration / Nachrichten / Mail eintragen.');
	}
	$to = trim((string)$to);
	if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		return array('ok' => false, 'error' => 'Ungültige Empfängeradresse.');
	}
	$fromEmail = trim((string)($settings['fromEmail'] ?? ''));
	$fromName = trim((string)($settings['fromName'] ?? ''));
	$text = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", (string)$html)), ENT_QUOTES, 'UTF-8'));
	$boundary = 'rkrk_' . bin2hex(random_bytes(8));
	$alt = 'alt_' . bin2hex(random_bytes(6));
	$headers = 'From: ' . rkrk_mail_encode_header($fromName) . ' <' . $fromEmail . ">\r\n";
	$headers .= 'To: ' . $to . "\r\n";
	$ccList = array();
	foreach (preg_split('/[;,]+/', (string)$cc) as $one) {
		$one = trim($one);
		if (filter_var($one, FILTER_VALIDATE_EMAIL)) {
			$ccList[] = $one;
		}
	}
	if (count($ccList) > 0) {
		$headers .= 'Cc: ' . implode(', ', $ccList) . "\r\n";
	}
	$headers .= 'Subject: ' . rkrk_mail_encode_header($subject) . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";
	$body = '--' . $boundary . "\r\n";
	$body .= 'Content-Type: multipart/alternative; boundary="' . $alt . "\"\r\n\r\n";
	$body .= '--' . $alt . "\r\n";
	$body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $text . "\r\n";
	$body .= '--' . $alt . "\r\n";
	$body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $html . "\r\n";
	$body .= '--' . $alt . "--\r\n";
	$files = array();
	foreach ($inline as $file) {
		if (is_array($file) && !empty($file['path']) && is_file($file['path'])) {
			$files[] = array('cid' => (string)($file['cid'] ?? ''), 'path' => $file['path']);
		}
	}
	foreach ($attachments as $path) {
		if (is_file($path)) {
			$files[] = array('cid' => '', 'path' => $path);
		}
	}
	foreach ($files as $file) {
		$bin = @file_get_contents($file['path']);
		if ($bin === false) {
			continue;
		}
		$filename = basename($file['path']);
		$mime = rkrk_mail_mime(pathinfo($filename, PATHINFO_EXTENSION));
		$body .= '--' . $boundary . "\r\n";
		$body .= 'Content-Type: ' . $mime . '; name="' . $filename . "\"\r\n";
		$body .= "Content-Transfer-Encoding: base64\r\n";
		if ($file['cid'] !== '') {
			$body .= 'Content-ID: <' . $file['cid'] . ">\r\n";
			$body .= "Content-Disposition: inline; filename=\"" . $filename . "\"\r\n\r\n";
		} else {
			$body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
		}
		$body .= chunk_split(base64_encode($bin)) . "\r\n";
	}
	$body .= '--' . $boundary . "--\r\n";
	$port = (int)($settings['smtpPort'] ?? 587);
	if ($port <= 0) {
		$port = 587;
	}
	$secure = (string)($settings['smtpSecure'] ?? 'starttls');
	if ($secure !== 'none' && !rkrk_mail_has_tls()) {
		return array('ok' => false, 'error' => rkrk_mail_tls_hint());
	}
	$remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
	$errno = 0;
	$errstr = '';
	$fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
	if (!$fp) {
		return array('ok' => false, 'error' => 'SMTP-Verbindung fehlgeschlagen: ' . $errstr);
	}
	stream_set_timeout($fp, 20);
	try {
		rkrk_mail_smtp_expect($fp, null, array(220));
		$ehloHost = 'newsletter.local';
		rkrk_mail_smtp_expect($fp, 'EHLO ' . $ehloHost, array(250));
		if ($secure === 'starttls') {
			rkrk_mail_smtp_expect($fp, 'STARTTLS', array(220));
			if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
				throw new RuntimeException('STARTTLS fehlgeschlagen.');
			}
			rkrk_mail_smtp_expect($fp, 'EHLO ' . $ehloHost, array(250));
		}
		$user = (string)($settings['smtpUser'] ?? '');
		$pass = (string)($settings['smtpPass'] ?? '');
		if ($user !== '') {
			rkrk_mail_smtp_expect($fp, 'AUTH LOGIN', array(334));
			rkrk_mail_smtp_expect($fp, base64_encode($user), array(334));
			rkrk_mail_smtp_expect($fp, base64_encode($pass), array(235));
		}
		rkrk_mail_smtp_expect($fp, 'MAIL FROM:<' . $fromEmail . '>', array(250));
		rkrk_mail_smtp_expect($fp, 'RCPT TO:<' . $to . '>', array(250, 251));
		foreach ($ccList as $ccTo) {
			rkrk_mail_smtp_expect($fp, 'RCPT TO:<' . $ccTo . '>', array(250, 251));
		}
		rkrk_mail_smtp_expect($fp, 'DATA', array(354));
		$payload = rkrk_mail_dot_stuff($headers . "\r\n" . $body);
		fwrite($fp, $payload . "\r\n.\r\n");
		list($code, $data) = rkrk_mail_smtp_read($fp);
		if ($code !== 250) {
			throw new RuntimeException(trim($data));
		}
		fwrite($fp, "QUIT\r\n");
		fclose($fp);
		return array('ok' => true, 'error' => '');
	} catch (Exception $ex) {
		fclose($fp);
		return array('ok' => false, 'error' => $ex->getMessage());
	}
}
