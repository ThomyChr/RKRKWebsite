<?php
/**
 * Newsletter und Gemeindebriefe: Speicher, Import, Anrede, Flugstatistik.
 * Der SMTP-Versand liegt in scripts/rkrk_mail.php.
 * JSON: rkropen/rkrk/data/newsletter und rkropen/rkrk/data/gemeinde.
 */
require_once dirname(__DIR__) . '/scripts/rkrk_mail.php';

function nl_h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function nl_rkrk_root() {
	global $RKRKRelRKRKDataLocation;
	if (!empty($RKRKRelRKRKDataLocation)) {
		return rtrim(str_replace('\\', '/', (string)$RKRKRelRKRKDataLocation), '/');
	}
	return rtrim(str_replace('\\', '/', dirname(__DIR__) . '/../rkrk'), '/');
}

function nl_file_kind($name) {
	$base = strtolower(basename((string)$name));
	if (strpos($base, 'gemeinde') !== false || $base === 'newsletter_kampagnen.json' || $base === 'gemeinde_ui.json') {
		return 'gemeinde';
	}
	return 'newsletter';
}

function nl_legacy_webdaten() {
	global $jsonDocLocation;
	if (!empty($jsonDocLocation)) {
		return rtrim(str_replace('\\', '/', (string)$jsonDocLocation), '/');
	}
	return rtrim(str_replace('\\', '/', dirname(__DIR__) . '/../rkrk/webdaten'), '/');
}

function nl_data_dir($kind = 'newsletter') {
	$kind = ($kind === 'gemeinde') ? 'gemeinde' : 'newsletter';
	$dir = nl_rkrk_root() . '/data/' . $kind;
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function nl_media_dir($kind = 'newsletter') {
	$dir = nl_data_dir($kind) . '/media';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function nl_json_path($name) {
	$name = basename((string)$name);
	$kind = nl_file_kind($name);
	$dir = nl_data_dir($kind);
	$path = $dir . '/' . $name;
	if (!is_file($path)) {
		$old = nl_legacy_webdaten() . '/' . $name;
		if (is_file($old)) {
			@copy($old, $path);
		}
	}
	return $path;
}

function nl_read_json($name, $fallback) {
	$path = nl_json_path($name);
	if (!is_file($path)) {
		return $fallback;
	}
	$raw = @file_get_contents($path);
	$data = json_decode((string)$raw, true);
	return is_array($data) ? $data : $fallback;
}

function nl_write_json($name, $data) {
	$path = nl_json_path($name);
	$dir = dirname($path);
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	return @file_put_contents($path, $json, LOCK_EX) !== false;
}

function nl_default_settings() {
	return array(
		'fromEmail' => 'thomas.christen@chwu.ch',
		'fromName' => 'Rehkitzrettung Knonaueramt',
		'smtpHost' => '',
		'smtpPort' => 587,
		'smtpSecure' => 'starttls',
		'smtpUser' => '',
		'smtpPass' => '',
		'unsubscribeEmail' => 'thomas.christen@chwu.ch',
		'whatsappEnabled' => true,
		'whatsappTemplate' => 'Feldmeldung für Rehkitzrettung mit Drohnen, Feld: {feld}',
		'telegramEnabled' => false,
		'telegramBotUsername' => '',
		'telegramBotToken' => '',
		'telegramChatId' => '',
		'telegramTemplate' => 'Feldmeldung für Rehkitzrettung mit Drohnen, Feld: {feld}',
	);
}

function nl_default_closing_du() {
	return "Dein Team von drohnenrehkitzrettung.ch\nKnonaueramt\nThomy, Chris, Vesa, Lars, Marina, Marc, Sarina, Armon & Nando\n\nDu erhältst diese Mail, weil du unsere Aktivitäten unterstützt hast. Falls du die News nicht mehr wünschst, kannst du dich jederzeit abmelden.";
}

function nl_default_closing_sie() {
	return "Ihr Team von drohnenrehkitzrettung.ch\nKnonaueramt\nThomy, Chris, Vesa, Lars, Marina, Marc, Sarina, Armon & Nando\n\nSie erhalten diese Mail, weil Sie unsere Aktivitäten unterstützt haben. Falls Sie die News nicht mehr wünschen, können Sie sich jederzeit abmelden.";
}

function nl_default_issue_body() {
	return '<p><strong>Saison 2026 – die bisher intensivste und erfolgreichste Saison</strong></p>'
		. '<p>Die Saison 2026 begann früh und entwickelte sich rasch zu einem der intensivsten Jahre seit Beginn unserer Einsätze. Über die gesamte Periode wurden über 100 Einsatztage, rund 500 Felder und über 1\'300 Hektaren abgeflogen.</p>'
		. '<p>Was diese Saison besonders auszeichnet, ist die Kombination aus hoher Einsatzfrequenz, starker Teamleistung und stabilen Partnerschaften mit Jägern und Landwirten.</p>'
		. '<p>Aktuelle Berichte stehen auf <a href="https://www.drohnenrehkitzrettung.ch/aktuell/newsletter">drohnenrehkitzrettung.ch</a>.</p>';
}

function nl_default_letter_html() {
	return '<p>In diesem Jahr durften wir bereits unsere sechste Saison der Rehkitzrettung mit Drohnen durchführen. Schritt für Schritt haben wir diesen Service aufgebaut, professionalisiert und gemeinsam mit Jägern, Landwirten sowie unseren Drohnenpilotinnen und -piloten in der Region verankert.</p>'
		. '<p><strong>Rückblick auf die Saison – intensiv, erfolgreich und rekordverdächtig</strong></p>'
		. '<p>Die Saison war die bisher intensivste und zugleich erfolgreichste seit Beginn unserer Einsätze. Die Einsätze nahmen ab Mitte Mai stark zu. Besonders eindrucksvoll war der 15. Juni, an dem wir an einem einzigen Tag 86 Felder bearbeiteten.</p>'
		. '<p><strong>Gemeinsam für Natur, Landwirtschaft und Tierschutz</strong></p>'
		. '<p>Um diese Arbeit auch künftig sicherzustellen, sind wir auf verlässliche Partner angewiesen – darunter Gemeinden, die den Schutz von Natur und Landwirtschaft unterstützen möchten. Aktuelle Berichte stehen auf <a href="https://www.drohnenrehkitzrettung.ch">drohnenrehkitzrettung.ch</a>.</p>';
}

function nl_ensure_store() {
	$settings = nl_read_json('newsletter_settings.json', null);
	if (!is_array($settings) || !isset($settings['fromEmail'])) {
		nl_write_json('newsletter_settings.json', nl_default_settings());
	}
	$recipients = nl_read_json('newsletter_recipients.json', null);
	if (!is_array($recipients) || !isset($recipients['items'])) {
		nl_write_json('newsletter_recipients.json', array('items' => array()));
	}
	$issues = nl_items('newsletter_issues.json');
	$issueArchive = nl_data_dir('newsletter') . '/newsletter_issues.archiv.json';
	if (count($issues) === 0 && !is_file(nl_json_path('newsletter_issues.json')) && !is_file($issueArchive)) {
		nl_save_items('newsletter_issues.json', array(array(
			'id' => '2026-07',
			'title' => 'Newsletter Juli 2026',
			'subject' => 'Rehkitzrettung Saison 2026 - Die bisher intensivste und erfolgreichste Saison!',
			'created' => '2026-07-01',
			'published' => false,
			'bodyHtml' => nl_default_issue_body(),
			'closingDu' => nl_default_closing_du(),
			'closingSie' => nl_default_closing_sie(),
		)));
	}
	$log = nl_read_json('newsletter_log.json', null);
	if (!is_array($log) || !isset($log['items'])) {
		nl_write_json('newsletter_log.json', array('items' => array()));
	}
	$gemeinden = nl_read_json('newsletter_gemeinden.json', null);
	if (!is_array($gemeinden) || !isset($gemeinden['items'])) {
		nl_write_json('newsletter_gemeinden.json', array('items' => array()));
	}
	$kampagnen = nl_read_json('newsletter_kampagnen.json', null);
	if (!is_array($kampagnen) || !isset($kampagnen['items']) || count($kampagnen['items']) === 0) {
		nl_write_json('newsletter_kampagnen.json', array('items' => array(array(
			'id' => '202607',
			'title' => 'Sponsorenbrief Gemeinden Juli 2026',
			'from' => '2026-04-01',
			'to' => '2026-07-31',
			'ortDatum' => 'Knonau',
			'sharedHtml' => nl_default_letter_html(),
			'texts' => array(),
		))));
	}
}

function nl_settings() {
	$settings = nl_read_json('newsletter_settings.json', array());
	return array_merge(nl_default_settings(), is_array($settings) ? $settings : array());
}

function nl_issue_file_id($id) {
	return preg_replace('/[^A-Za-z0-9._-]/', '', trim((string)$id));
}

function nl_issues_dir() {
	$dir = nl_data_dir('newsletter') . '/issues';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function nl_issue_media_dir($id) {
	$id = nl_issue_file_id($id);
	$dir = nl_issues_dir() . '/' . $id;
	if ($id !== '' && !is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function nl_issue_json_path($id) {
	$id = nl_issue_file_id($id);
	if ($id === '') {
		return '';
	}
	return nl_issue_media_dir($id) . '/' . $id . '.json';
}

function nl_issue_json_paths() {
	$paths = glob(nl_issues_dir() . '/*/*.json');
	return is_array($paths) ? $paths : array();
}

function nl_lift_issue_dirs() {
	$oldRoot = nl_data_dir('newsletter') . '/media/issues';
	if (!is_dir($oldRoot)) {
		return;
	}
	$dirs = glob($oldRoot . '/*', GLOB_ONLYDIR);
	if (!is_array($dirs)) {
		return;
	}
	foreach ($dirs as $dir) {
		$id = nl_issue_file_id(basename($dir));
		if ($id === '') {
			continue;
		}
		$dest = nl_issues_dir() . '/' . $id;
		if (!is_dir($dest)) {
			@rename($dir, $dest);
		}
	}
}

function nl_rewrite_issue_media_paths() {
	foreach (nl_issue_json_paths() as $path) {
		$raw = (string)@file_get_contents($path);
		if ($raw === '' || (strpos($raw, 'media/issues/') === false && strpos($raw, 'media\\/issues\\/') === false)) {
			continue;
		}
		$next = str_replace(array('media\\/issues\\/', 'media/issues/'), array('issues\\/', 'issues/'), $raw);
		if ($next !== $raw) {
			@file_put_contents($path, $next, LOCK_EX);
		}
	}
}

function nl_move_issue_json_beside_media() {
	$files = glob(nl_issues_dir() . '/*.json');
	if (!is_array($files)) {
		return;
	}
	foreach ($files as $file) {
		$id = nl_issue_file_id(basename($file, '.json'));
		$dest = nl_issue_json_path($id);
		if ($id === '' || $dest === '') {
			continue;
		}
		if (!is_file($dest)) {
			@rename($file, $dest);
		}
		if (is_file($file)) {
			@unlink($file);
		}
	}
}

function nl_write_issue_file($item) {
	if (!is_array($item)) {
		return '';
	}
	$id = nl_issue_file_id($item['id'] ?? '');
	if ($id === '') {
		return '';
	}
	$item['id'] = $id;
	$json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return '';
	}
	$path = nl_issue_json_path($id);
	if ($path === '' || @file_put_contents($path, $json, LOCK_EX) === false) {
		return '';
	}
	return $path;
}

function nl_split_legacy_issues() {
	if (count(nl_issue_json_paths()) > 0 || count((array)glob(nl_issues_dir() . '/*.json')) > 0) {
		return;
	}
	$data = nl_read_json('newsletter_issues.json', null);
	if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
		return;
	}
	$written = array();
	$expected = 0;
	foreach ($data['items'] as $item) {
		if (!is_array($item) || nl_issue_file_id($item['id'] ?? '') === '') {
			continue;
		}
		$expected++;
		$path = nl_write_issue_file($item);
		if ($path === '') {
			foreach ($written as $one) {
				@unlink($one);
			}
			return;
		}
		$written[] = $path;
	}
	if ($expected > 0 && count($written) === $expected) {
		$combined = nl_json_path('newsletter_issues.json');
		if (is_file($combined)) {
			@rename($combined, nl_data_dir('newsletter') . '/newsletter_issues.archiv.json');
		}
	}
}

function nl_load_issue_files() {
	nl_lift_issue_dirs();
	nl_move_issue_json_beside_media();
	nl_rewrite_issue_media_paths();
	nl_split_legacy_issues();
	$items = array();
	$paths = nl_issue_json_paths();
	if (!is_array($paths)) {
		return $items;
	}
	foreach ($paths as $path) {
		$item = json_decode((string)@file_get_contents($path), true);
		if (!is_array($item)) {
			continue;
		}
		if (nl_issue_file_id($item['id'] ?? '') === '') {
			$item['id'] = basename($path, '.json');
		}
		$items[] = $item;
	}
	usort($items, function ($a, $b) {
		return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
	});
	return $items;
}

function nl_save_issue_files($items) {
	$keep = array();
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}
		$path = nl_write_issue_file($item);
		if ($path === '') {
			continue;
		}
		$keep[nl_issue_file_id($item['id'] ?? '')] = true;
	}
	foreach (nl_issue_json_paths() as $path) {
		$id = nl_issue_file_id(basename(dirname($path)));
		if ($id !== '' && !isset($keep[$id])) {
			@unlink($path);
		}
	}
	return true;
}

function nl_rm_tree($dir) {
	$dir = rtrim(str_replace('\\', '/', (string)$dir), '/');
	if ($dir === '' || !is_dir($dir)) {
		return;
	}
	$items = scandir($dir);
	if (!is_array($items)) {
		return;
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$path = $dir . '/' . $item;
		if (is_dir($path)) {
			nl_rm_tree($path);
		} else {
			@unlink($path);
		}
	}
	@rmdir($dir);
}

function nl_newsletter_media_file($rel) {
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, 'media/') !== 0) {
		return '';
	}
	$root = realpath(nl_data_dir('newsletter'));
	$mediaRoot = $root ? realpath($root . '/media') : false;
	$abs = nl_data_dir('newsletter') . '/' . $rel;
	$real = is_file($abs) ? realpath($abs) : false;
	if (!$root || !$mediaRoot || !$real) {
		return '';
	}
	$real = str_replace('\\', '/', $real);
	$mediaRoot = rtrim(str_replace('\\', '/', $mediaRoot), '/');
	if (strpos($real, $mediaRoot . '/') !== 0) {
		return '';
	}
	return $real;
}

function nl_delete_issue($id) {
	$id = nl_issue_file_id($id);
	if ($id === '') {
		return false;
	}
	$items = nl_items('newsletter_issues.json');
	$found = null;
	$keep = array();
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}
		if ((string)($item['id'] ?? '') === $id) {
			$found = $item;
			continue;
		}
		$keep[] = $item;
	}
	if (!$found) {
		return false;
	}
	$used = array();
	foreach ($keep as $item) {
		foreach (nl_issue_media_rels($item) as $rel => $on) {
			$used[$rel] = true;
		}
	}
	if (!nl_save_items('newsletter_issues.json', $keep)) {
		return false;
	}
	foreach (nl_issue_media_rels($found) as $rel => $on) {
		if (isset($used[$rel])) {
			continue;
		}
		$file = nl_newsletter_media_file($rel);
		if ($file !== '') {
			@unlink($file);
		}
	}
	$issueDir = nl_issues_dir() . '/' . $id;
	$issuesRoot = realpath(nl_issues_dir());
	$realDir = is_dir($issueDir) ? realpath($issueDir) : false;
	if ($issuesRoot && $realDir) {
		$issuesRoot = rtrim(str_replace('\\', '/', $issuesRoot), '/');
		$realDir = str_replace('\\', '/', $realDir);
		if ($realDir !== $issuesRoot && strpos($realDir, $issuesRoot . '/') === 0) {
			nl_rm_tree($realDir);
		}
	}
	return true;
}

function nl_items($file) {
	if (basename((string)$file) === 'newsletter_issues.json') {
		return nl_load_issue_files();
	}
	$data = nl_read_json($file, array('items' => array()));
	return isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
}

function nl_save_items($file, $items) {
	if (basename((string)$file) === 'newsletter_issues.json') {
		return nl_save_issue_files($items);
	}
	return nl_write_json($file, array('items' => array_values($items)));
}

function nl_recipient_fields() {
	return array('gemeinde', 'anschrift1', 'anschrift2', 'strasse', 'strasse2', 'plz', 'ort', 'email', 'anrede');
}

function nl_letter_fields() {
	return array_merge(nl_recipient_fields(), array('text', 'dank', 'betrag', 'qr', 'bild1', 'cap1', 'bild2', 'cap2', 'images', 'imageCaps', 'picOver', 'flugdaten', 'absender', 'bemerkung', 'slipMode'));
}

function nl_freeze_letter_recipients(&$campaigns, $gemeinden) {
	$byId = array();
	foreach ($gemeinden as $g) {
		if (is_array($g) && isset($g['id'])) {
			$byId[(string)$g['id']] = $g;
		}
	}
	$changed = false;
	foreach ($campaigns as $i => $camp) {
		if (!isset($camp['letters']) || !is_array($camp['letters'])) {
			continue;
		}
		foreach ($camp['letters'] as $gid => $letter) {
			if (!is_array($letter) || array_key_exists('anschrift1', $letter) || !isset($byId[(string)$gid])) {
				continue;
			}
			$g = $byId[(string)$gid];
			foreach (nl_recipient_fields() as $field) {
				if ($field === 'anrede' && trim((string)($letter['anrede'] ?? '')) !== '') {
					continue;
				}
				$letter[$field] = trim((string)($g[$field] ?? ''));
			}
			$campaigns[$i]['letters'][$gid] = $letter;
			$campaigns[$i]['texts'][$gid] = $letter;
			$changed = true;
		}
	}
	return $changed;
}

function nl_letter_row($campaign, $gid) {
	$gid = (string)$gid;
	if ($gid === '' || !is_array($campaign)) {
		return array();
	}
	if (isset($campaign['letters'][$gid]) && is_array($campaign['letters'][$gid])) {
		return $campaign['letters'][$gid];
	}
	if (isset($campaign['texts'][$gid]) && is_array($campaign['texts'][$gid])) {
		return $campaign['texts'][$gid];
	}
	return array();
}

function nl_with_letter($campaign, $gemeinde) {
	if (!is_array($gemeinde)) {
		return array();
	}
	$row = nl_letter_row($campaign, $gemeinde['id'] ?? '');
	foreach (nl_letter_fields() as $field) {
		if (array_key_exists($field, $row)) {
			$gemeinde[$field] = $row[$field];
		}
	}
	return $gemeinde;
}

function nl_find_item($items, $id) {
	foreach ($items as $item) {
		if (isset($item['id']) && (string)$item['id'] === (string)$id) {
			return $item;
		}
	}
	return null;
}

function nl_upsert_item(&$items, $item, $key) {
	foreach ($items as $i => $existing) {
		if (isset($existing[$key], $item[$key]) && (string)$existing[$key] === (string)$item[$key]) {
			$items[$i] = array_merge($existing, $item);
			return $items[$i]['id'];
		}
	}
	if (empty($item['id'])) {
		$item['id'] = substr(sha1((string)microtime(true) . mt_rand()), 0, 10);
	}
	$items[] = $item;
	return $item['id'];
}

function nl_greeting($person) {
	$custom = trim((string)($person['anrede'] ?? ''));
	if ($custom !== '') {
		return $custom;
	}
	$art = (string)($person['anredeArt'] ?? 'Sie');
	$gender = strtolower(trim((string)($person['geschlecht'] ?? '')));
	$vorname = trim((string)($person['vorname'] ?? ''));
	$name = trim((string)($person['name'] ?? ''));
	if ($art === 'Du') {
		if ($gender === 'f' && $vorname !== '') {
			return 'Liebe ' . $vorname;
		}
		if ($gender === 'm' && $vorname !== '') {
			return 'Lieber ' . $vorname;
		}
		return 'Guten Tag';
	}
	if ($gender === 'f' && $name !== '') {
		return 'Sehr geehrte Frau ' . $name;
	}
	if ($gender === 'm' && $name !== '') {
		return 'Sehr geehrter Herr ' . $name;
	}
	return 'Guten Tag';
}

function nl_looks_like_html($text) {
	return preg_match('/<\/?[a-z][a-z0-9]*\b[^>]*>/i', (string)$text) === 1;
}

function nl_issue_status($issue) {
	$status = (string)($issue['status'] ?? '');
	if ($status === 'draft' || $status === 'published' || $status === 'hidden') {
		return $status;
	}
	return !empty($issue['published']) ? 'published' : 'draft';
}

function nl_issue_is_public($issue) {
	return nl_issue_status($issue) === 'published';
}

function nl_issue_status_label($status) {
	if ($status === 'published') {
		return 'veröffentlicht';
	}
	if ($status === 'hidden') {
		return 'verborgen';
	}
	return 'Entwurf';
}

function nl_issue_legacy($issue) {
	$id = (string)($issue['id'] ?? '');
	if (!preg_match('/^(\d{4})-(\d{2})$/', $id, $m)) {
		return false;
	}
	return ((int)$m[1]) * 100 + (int)$m[2] <= 202403;
}

function nl_repair_glued($html) {
	$html = (string)$html;
	$html = preg_replace('#</strong>\s*,\s*(?=\p{Lu})#u', '</strong><br>', $html);
	$html = preg_replace('#</strong>(?=\p{Lu})#u', '</strong><br>', $html);
	$html = preg_replace('#([.!?])\s*(<strong>)#u', '$1<br><br>$2', $html);
	$parts = preg_split('#(<[^>]+>)#u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (!is_array($parts)) {
		return $html;
	}
	foreach ($parts as $i => $part) {
		if ($part === '' || $part[0] === '<') {
			continue;
		}
		$part = preg_replace('#(\p{Ll})(\p{Lu})#u', '$1<br>$2', $part);
		$part = preg_replace('#•(?=\S)#u', '• ', $part);
		$parts[$i] = $part;
	}
	return implode('', $parts);
}

function nl_caption_heading($caption) {
	$caption = trim((string)$caption);
	if (preg_match('#^<strong>([^<]{2,160})</strong>$#u', $caption, $m)) {
		$plain = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
		if ($plain !== '' && strpos($plain, '>') !== 0 && strpos($plain, '.') !== 0) {
			return $plain;
		}
	}
	return '';
}

function nl_link_site($html) {
	$parts = preg_split('#(<a\b[^>]*>.*?</a>|<[^>]+>)#is', (string)$html, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (!is_array($parts)) {
		return (string)$html;
	}
	$link = '<a href="https://www.drohnenrehkitzrettung.ch">drohnenrehkitzrettung.ch</a>';
	foreach ($parts as $i => $part) {
		if ($part === '' || $part[0] === '<') {
			continue;
		}
		$parts[$i] = preg_replace(
			'#(?<![\w@./])(?:www\.)?drohnenrehkitzrettung\.ch(?![\w./@-])#i',
			$link,
			$part
		);
	}
	return implode('', $parts);
}

function nl_text_to_html($text) {
	$text = str_replace(array("\r\n", "\r"), "\n", (string)$text);
	if (nl_looks_like_html($text)) {
		return nl_link_site(nl_repair_glued(nl_sanitize_html($text)));
	}
	return nl_link_site(nl_repair_glued(nl2br(nl_h($text), false)));
}

function nl_sanitize_html($html) {
	$html = (string)$html;
	$html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
	$html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*\/?>#is', '', $html);
	$html = strip_tags($html, '<p><br><strong><b><em><i><u><a><ul><ol><li><h1><h2><h3><h4><span><div><blockquote><table><thead><tbody><tr><td><th><img><figure><figcaption><hr>');
	$html = preg_replace('#\son\w+\s*=\s*(\'|").*?\1#is', '', $html);
	$html = preg_replace('#\son\w+\s*=\s*[^\s>]+#is', '', $html);
	$html = preg_replace('#javascript:#i', '', $html);
	return $html;
}

function nl_campaign_label($title) {
	return str_replace(array('Sponsorenbrief Gemeinden', 'Sponsorenbrief Gemeinde'), 'Gde', (string)$title);
}

function nl_hid($hooks, $id) {
	return $hooks ? ' id="' . $id . '"' : '';
}

function nl_site_img_web($relFromImages) {
	global $ImageLoc;
	$rel = ltrim(str_replace('\\', '/', (string)$relFromImages), '/');
	$base = rtrim(str_replace('\\', '/', (string)($ImageLoc ?? 'images/')), '/');
	$file = $base . '/' . $rel;
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$prefix = (strpos($script, '/newsletter/') !== false) ? '../' : '';
	return $prefix . $file;
}

function nl_site_img_abs($relFromImages) {
	global $ImageLoc;
	$rel = ltrim(str_replace('\\', '/', (string)$relFromImages), '/');
	$base = rtrim(str_replace('\\', '/', (string)($ImageLoc ?? 'images/')), '/');
	return dirname(__DIR__) . '/' . $base . '/' . $rel;
}

function nl_fn_linked_img($relFromImages, $alt, $width, $href, $class = 'nl-fn-qr') {
	$src = nl_site_img_web($relFromImages);
	$w = (int)$width;
	$cls = trim((string)$class);
	$clsAttr = ($cls !== '') ? ' class="' . nl_h($cls) . '"' : '';
	if ($cls === 'nl-fn-qr') {
		$img = '<img' . $clsAttr . ' src="' . nl_h($src) . '" alt="' . nl_h($alt) . '" width="' . $w . '" style="width:100%;max-width:' . $w . 'px;height:auto;max-height:none;display:block;margin:0 auto;border:0;object-fit:contain;overflow:visible;">';
	} else {
		$img = '<img' . $clsAttr . ' src="' . nl_h($src) . '" alt="' . nl_h($alt) . '" width="' . $w . '" style="width:' . $w . 'px;max-width:' . $w . 'px;height:auto;display:block;border:0;">';
	}
	if ($href === '') {
		return $img;
	}
	$aStyle = ($cls === 'nl-fn-qr') ? ' style="display:block;line-height:0;overflow:visible;"' : '';
	return '<a href="' . nl_h($href) . '" target="_blank" rel="noopener"' . $aStyle . '>' . $img . '</a>';
}

function nl_strip_visual_footnote_from_closing($text) {
	$text = str_replace(array("\r\n", "\r"), "\n", (string)$text);
	$markers = array(
		'Weiter Infos auf unserer Website',
		'Weiter Infos auf unserer Webseite',
		'Weitere Informationen auf unserer Website',
		'Möchtest du uns gerne unterstützen',
		'Möchten Sie uns gerne unterstützen',
		'Member of',
	);
	$cut = null;
	foreach ($markers as $m) {
		$p = stripos($text, $m);
		if ($p !== false && ($cut === null || $p < $cut)) {
			$cut = $p;
		}
	}
	if ($cut !== null) {
		$text = substr($text, 0, $cut);
	}
	return rtrim($text);
}

function nl_is_stock_footnote_text($text) {
	$t = strtolower(trim((string)$text));
	if ($t === '') {
		return true;
	}
	$hasIban = (strpos($t, 'ch12') !== false && strpos($t, '0023') !== false);
	$hasMember = (strpos($t, 'member of') !== false);
	$hasSite = (strpos($t, 'drohnenrehkitzrettung') !== false);
	return $hasSite && ($hasIban || $hasMember);
}

function nl_default_footnote_html($art = 'Du') {
	$web = 'https://www.drohnenrehkitzrettung.ch';
	$donate = 'https://www.drohnenrehkitzrettung.ch/spenden';
	$member = 'https://www.rehkitzrettung.ch';
	$iban = 'CH12 0023 9239 1639 8641 T';
	$qrPx = 86;
	$capUrl = 'www.<wbr>drohnenrehkitz<wbr>rettung.ch';
	$cap = '<div class="nl-fn-cap" style="display:block;position:static;width:100%;max-width:100%;margin:3px auto 0;font-size:8px;line-height:1.25;text-align:center;white-space:normal;overflow:visible;overflow-wrap:anywhere;word-wrap:break-word;"><a href="' . nl_h($web) . '" style="color:#2f6b4f;text-decoration:none;overflow-wrap:anywhere;word-wrap:break-word;">' . $capUrl . '</a></div>';
	$box = 'display:block;width:100%;max-width:' . $qrPx . 'px;margin:0 auto;overflow:visible;';
	$qrWeb = '<div class="nl-fn-qrbox" style="' . $box . '">' . nl_fn_linked_img('qr_drohnenrekitzrettungch.jpg', 'QR-Code Website drohnenrehkitzrettung.ch', $qrPx, $web) . $cap . '</div>';
	$qrTwint = '<div class="nl-fn-qrbox" style="' . $box . '">' . nl_fn_linked_img('twint_frei.jpg', 'TWINT QR-Code Spende', $qrPx, $donate) . $cap . '</div>';
	$qrSwiss = '<div class="nl-fn-qrbox" style="' . $box . '">' . nl_fn_linked_img('qr_swiss_frei.jpg', 'Swiss QR-Code Bankzahlung', $qrPx, $donate) . $cap . '</div>';
	$logo = nl_fn_linked_img('calc/rkrs_logo.png', 'Rehkitzrettung.ch', 110, $member, 'nl-fn-logo');
	$du = ((string)$art === 'Du');
	$ask = $du
		? 'Möchtest du uns gerne unterstützen? Dann verwende einfach den QR-Code für eine Bank- oder TWINT-Zahlung.'
		: 'Möchten Sie uns gerne unterstützen? Dann verwenden Sie einfach den QR-Code für eine Bank- oder TWINT-Zahlung.';
	$thanks = $du
		? 'Durch deine Unterstützung können wir den Landwirten die Drohnensuchflüge kostenfrei anbieten. Herzlichen Dank!'
		: 'Durch Ihre Unterstützung können wir den Landwirten die Drohnensuchflüge kostenfrei anbieten. Herzlichen Dank!';
	$team = $du ? 'Euer Piloten-Team' : 'Ihr Piloten-Team';
	$cell = 'vertical-align:top;padding:0 8px;font-size:11px;line-height:1.25;color:#333;white-space:normal;overflow:hidden;overflow-wrap:break-word;word-wrap:break-word;';
	$qrCell = 'vertical-align:top;padding:0;width:' . $qrPx . 'px;max-width:' . $qrPx . 'px;overflow:visible;text-align:center;white-space:normal;';
	$html = '<table class="nl-fn-std" role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:6px;clear:both;border-top:1px solid #ddd;border-collapse:collapse;width:100%;max-width:100%;table-layout:fixed;">';
	$html .= '<colgroup><col class="nl-fn-c-qr" style="width:' . $qrPx . 'px"><col class="nl-fn-c-ask"><col class="nl-fn-c-qr" style="width:' . $qrPx . 'px"><col class="nl-fn-c-pay"><col class="nl-fn-c-qr" style="width:' . $qrPx . 'px"></colgroup>';
	$html .= '<tr class="nl-fn-head"><td colspan="5" style="padding:4px 0 6px;font-size:11px;line-height:1.2;color:#333;">Weiter Infos auf unserer Website:</td></tr>';
	$html .= '<tr class="nl-fn-row">';
	$html .= '<td class="nl-fn-col nl-fn-qrcol" width="' . $qrPx . '" style="' . $qrCell . '">' . $qrWeb . '</td>';
	$link = '<div style="margin:0 0 4px;"><a href="' . nl_h($web) . '" style="color:#2f6b4f;font-weight:700;text-decoration:none;overflow-wrap:anywhere;">www.<wbr>drohnenrehkitz<wbr>rettung.ch</a></div>';
	$html .= '<td class="nl-fn-col nl-fn-text" style="' . $cell . '">' . $link . nl_h($ask) . '</td>';
	$html .= '<td class="nl-fn-col nl-fn-qrcol" width="' . $qrPx . '" style="' . $qrCell . '">' . $qrTwint . '</td>';
	$html .= '<td class="nl-fn-col nl-fn-text" style="' . $cell . '">';
	$html .= '<p style="margin:0 0 2px;color:#2f6b4f;font-weight:700;">' . nl_h($iban) . '</p>';
	$html .= '<p style="margin:0 0 4px;">c/o Thomas Christen, Drohnenrehkitzrettung, Baumgartenweg 7, 8934 Knonau</p>';
	$html .= '<p style="margin:0 0 2px;font-weight:700;">' . nl_h($thanks) . '</p>';
	$html .= '<p style="margin:0;">' . nl_h($team) . '</p>';
	$html .= '</td>';
	$html .= '<td class="nl-fn-col nl-fn-qrcol" width="' . $qrPx . '" style="' . $qrCell . '">' . $qrSwiss . '</td>';
	$html .= '</tr>';
	$html .= '<tr class="nl-fn-member"><td colspan="5" style="padding-top:10px;font-size:11px;line-height:1.2;color:#333;white-space:nowrap;vertical-align:middle;">';
	$html .= '<span style="font-style:italic;vertical-align:middle;">Member of:</span> ' . $logo;
	$html .= '</td></tr></table>';
	return $html;
}

function nl_blank_section() {
	return array('id' => 's' . substr(md5(uniqid('', true)), 0, 8), 'title' => '', 'text' => '', 'image' => '');
}

function nl_sections_from_html($html) {
	$html = trim((string)$html);
	if ($html === '') {
		return array(nl_blank_section());
	}
	$chunks = preg_split('#(?=<h2\b|<p>\s*<strong>)#i', $html, -1, PREG_SPLIT_NO_EMPTY);
	if (!is_array($chunks) || count($chunks) === 0) {
		$one = nl_blank_section();
		$one['text'] = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $html))));
		return array($one);
	}
	$sections = array();
	$current = null;
	foreach ($chunks as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '') {
			continue;
		}
		if (preg_match('#^<h2\b[^>]*>(.*?)</h2>#is', $chunk, $m) || preg_match('#^<p>\s*<strong>(.*?)</strong>\s*</p>#is', $chunk, $m)) {
			if ($current !== null) {
				$sections[] = $current;
			}
			$current = nl_blank_section();
			$current['title'] = trim(html_entity_decode(strip_tags($m[1])));
			$rest = trim(preg_replace('#^<h2\b[^>]*>.*?</h2>|^<p>\s*<strong>.*?</strong>\s*</p>#is', '', $chunk, 1));
			$current['text'] = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $rest))));
			continue;
		}
		$text = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $chunk))));
		if ($current === null) {
			$current = nl_blank_section();
		}
		$current['text'] = trim($current['text'] . ($current['text'] !== '' && $text !== '' ? "\n\n" : '') . $text);
	}
	if ($current !== null) {
		$sections[] = $current;
	}
	return count($sections) > 0 ? $sections : array(nl_blank_section());
}

function nl_issue_sections($issue) {
	if (isset($issue['sections']) && is_array($issue['sections']) && count($issue['sections']) > 0) {
		$out = array();
		foreach ($issue['sections'] as $section) {
			if (!is_array($section)) {
				continue;
			}
			$row = nl_blank_section();
			if (trim((string)($section['id'] ?? '')) !== '') {
				$row['id'] = (string)$section['id'];
			}
			$row['title'] = (string)($section['title'] ?? '');
			$row['showTitle'] = array_key_exists('showTitle', $section) ? (!empty($section['showTitle']) ? 1 : 0) : 1;
			$row['text'] = (string)($section['text'] ?? '');
			$row['image'] = (string)($section['image'] ?? '');
			$row['caption'] = (string)($section['caption'] ?? '');
			if (isset($section['images']) && is_array($section['images'])) {
				$row['images'] = $section['images'];
			}
			$crop = nl_crop_box(isset($section['crop']) ? $section['crop'] : null);
			if ($crop) {
				$row['crop'] = $crop;
			}
			$out[] = $row;
		}
		if (count($out) > 0) {
			return $out;
		}
	}
	return nl_sections_from_html($issue['bodyHtml'] ?? '');
}

function nl_media_ratio($rel, $crop = null) {
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	$abs = '';
	foreach (array('newsletter', 'gemeinde') as $kind) {
		$try = nl_data_dir($kind) . '/' . $rel;
		if (is_file($try)) {
			$abs = $try;
			break;
		}
	}
	$info = ($abs !== '') ? @getimagesize($abs) : false;
	$iw = (is_array($info) && !empty($info[0])) ? (float)$info[0] : 0;
	$ih = (is_array($info) && !empty($info[1])) ? (float)$info[1] : 0;
	$box = nl_crop_box(is_array($crop) ? $crop : null);
	if ($box && $iw > 0 && $ih > 0) {
		$iw = max(1, $iw * $box['w'] / 100);
		$ih = max(1, $ih * $box['h'] / 100);
	}
	if ($iw < 1 || $ih < 1) {
		return 1.4;
	}
	return $iw / $ih;
}

function nl_row_mm_list(array $ratios) {
	$target = 48;
	$avail = 150;
	$gap = 3;
	$sum = 0;
	foreach ($ratios as $ratio) {
		$sum += (float)$ratio;
	}
	$n = count($ratios);
	$height = $target;
	if ($sum > 0 && ($target * $sum + $gap * max(0, $n - 1)) > $avail) {
		$height = ($avail - $gap * max(0, $n - 1)) / $sum;
	}
	if ($height < 12) {
		$height = 12;
	}
	$out = array();
	foreach ($ratios as $ratio) {
		$out[] = round($height * (float)$ratio, 2);
	}
	return $out;
}

function nl_sections_to_html($sections) {
	$html = '';
	foreach ($sections as $section) {
		if (!is_array($section)) {
			continue;
		}
		$title = trim((string)($section['title'] ?? ''));
		$text = trim((string)($section['text'] ?? ''));
		$image = trim((string)($section['image'] ?? ''));
		$caption = trim((string)($section['caption'] ?? ''));
		$images = array();
		if ($image !== '') {
			$images[] = $image;
		}
		if (isset($section['images']) && is_array($section['images'])) {
			foreach ($section['images'] as $extra) {
				$extra = trim((string)$extra);
				if ($extra !== '' && !in_array($extra, $images, true)) {
					$images[] = $extra;
				}
			}
		}
		if ($title === '' && $text === '' && count($images) === 0) {
			continue;
		}
		$side = ($text !== '' && count($images) > 0);
		$tallSide = ($side && count($images) === 1 && nl_image_is_tall($images[0], isset($section['crop']) ? $section['crop'] : null));
		$forMail = !empty($GLOBALS['nl_for_mail']);
		$cls = 'nl-mail-sec' . ((!$side && count($images) > 0) ? ' nl-sec-full' : '');
		$html .= '<article class="' . $cls . '">';
		$forPdf = !empty($GLOBALS['nl_for_pdf']);
		$showTitle = !array_key_exists('showTitle', $section) || !empty($section['showTitle']);
		$capHead = ($showTitle && $title === '') ? nl_caption_heading($caption) : '';
		if ($capHead !== '') {
			$title = $capHead;
			$caption = '';
		}
		if ($showTitle && $title !== '') {
			$html .= '<h2 style="font-size:22px;margin:18px 0 8px;clear:both;">' . nl_h($title) . '</h2>';
		}
		$picHtml = '';
		$picParts = array();
		$imgIndex = 0;
		$imgLast = count($images) - 1;
		$rowPics = (count($images) >= 2 && count($images) <= 3);
		$rowMm = array();
		if ($rowPics) {
			$ratios = array();
			foreach ($images as $ri => $oneRel) {
				$cropOne = ($ri === 0 && isset($section['crop'])) ? $section['crop'] : null;
				$ratios[] = nl_media_ratio($oneRel, $cropOne);
			}
			$rowMm = nl_row_mm_list($ratios);
			$side = false;
			$tallSide = false;
		}
		foreach ($images as $oneImg) {
			$src = nl_img_src($oneImg);
			$alt = $caption !== '' ? $caption : ($capHead !== '' ? $capHead : $title);
			$crop = ($imgIndex === 0 && isset($section['crop'])) ? $section['crop'] : null;
			$imgTag = nl_crop_img_html($oneImg, $src !== '' ? $src : $oneImg, $alt, $crop);
			if ($rowPics && isset($rowMm[$imgIndex]) && ($forPdf || $forMail)) {
				$pxW = (int)max(1, round($rowMm[$imgIndex] * 96 / 25.4));
				$imgTag = preg_replace('#\sstyle="[^"]*"#', ' style="width:' . $pxW . 'px;height:auto;display:block;border:0;"', $imgTag, 1);
				$imgTag = preg_replace('/<img\b/', '<img width="' . $pxW . '"', $imgTag, 1);
			} elseif (($forPdf || $forMail) && $side) {
				$imgTag = preg_replace('/<img\b/', '<img class="nl-pdf-side"', $imgTag, 1);
			}
			$imgTag = nl_mail_link_img($imgTag, nl_mail_image_href($oneImg, $crop));
			$figAttr = ' style="margin:0 0 8px;"';
			if ($rowPics && isset($rowMm[$imgIndex])) {
				$figAttr = ' class="nl-pic-row" style="margin:0;--nl-row-w:' . $rowMm[$imgIndex] . 'mm;"';
			}
			$fig = '<figure' . $figAttr . '>' . $imgTag;
			if ($caption !== '' && $imgIndex === $imgLast) {
				$fig .= '<figcaption style="display:block;text-align:left;font-size:13px;line-height:1.3;color:#555;margin-top:2px;">' . nl_text_to_html($caption) . '</figcaption>';
			}
			$fig .= '</figure>';
			$picParts[] = $fig;
			$picHtml .= $fig;
			$imgIndex++;
		}
		if ($rowPics && count($picParts) > 0) {
			if ($forPdf || $forMail) {
				$cells = '';
				$lastPart = count($picParts) - 1;
				foreach ($picParts as $pi => $part) {
					$pxW = isset($rowMm[$pi]) ? (int)max(1, round($rowMm[$pi] * 96 / 25.4)) : 0;
					$pad = ($pi < $lastPart) ? 'padding:0 8px 0 0;' : 'padding:0;';
					$cells .= '<td valign="top" align="left" style="' . $pad . ($pxW > 0 ? 'width:' . $pxW . 'px;' : '') . 'vertical-align:top;">' . $part . '</td>';
				}
				$picHtml = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr>' . $cells . '</tr></table>';
			} else {
				$picHtml = '<div class="nl-pics-row">' . $picHtml . '</div>';
			}
		}
		$textHtml = ($text !== '') ? ('<div>' . nl_text_to_html($text) . '</div>') : '';
		if (($forPdf || $forMail) && $side) {
			$picWidth = $tallSide ? '30%' : '42%';
			$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr>';
			$html .= '<td valign="top" style="padding:0 16px 0 0;">' . $textHtml . '</td>';
			$html .= '<td valign="top" width="' . $picWidth . '" style="width:' . $picWidth . ';">' . $picHtml . '</td>';
			$html .= '</tr></table>';
		} elseif ($side) {
			$html .= '<div class="nl-sec-pics' . ($tallSide ? ' nl-tall' : '') . '">' . $picHtml . '</div>';
			$html .= $textHtml;
		} else {
			$html .= $picHtml . $textHtml;
		}
		$html .= '</article>';
	}
	return $html;
}

function nl_store_upload_at($field, $key, $index, $subdir, $kind = 'newsletter') {
	if (!isset($_FILES[$field]['tmp_name'][$key][$index])) {
		return '';
	}
	$tmp = $_FILES[$field]['tmp_name'][$key][$index];
	$name = $_FILES[$field]['name'][$key][$index];
	if ($tmp === '' || !is_uploaded_file($tmp)) {
		return '';
	}
	$saved = $_FILES[$field];
	$_FILES[$field] = array('tmp_name' => $tmp, 'name' => $name);
	$rel = nl_store_upload($field, $subdir, $kind);
	$_FILES[$field] = $saved;
	return $rel;
}

function nl_store_upload_index($field, $index, $subdir, $kind = 'newsletter') {
	if (!isset($_FILES[$field]['tmp_name'][$index])) {
		return '';
	}
	$tmp = $_FILES[$field]['tmp_name'][$index];
	$name = $_FILES[$field]['name'][$index];
	if ($tmp === '' || !is_uploaded_file($tmp)) {
		return '';
	}
	$saved = $_FILES[$field];
	$_FILES[$field] = array('tmp_name' => $tmp, 'name' => $name);
	$rel = nl_store_upload($field, $subdir, $kind);
	$_FILES[$field] = $saved;
	return $rel;
}

function nl_issue_results_source($issue) {
	$src = (string)($issue['resultsSource'] ?? '');
	if ($src === 'statistik' || $src === 'image') {
		return $src;
	}
	return trim((string)($issue['resultsImage'] ?? '')) !== '' ? 'image' : 'statistik';
}

function nl_issue_resultate_on($issue) {
	if (!is_array($issue) || !array_key_exists('resultate', $issue)) {
		return true;
	}
	$flag = $issue['resultate'];
	if ($flag === false || $flag === 0 || $flag === '0' || $flag === 'false' || $flag === 'off') {
		return false;
	}
	return true;
}

function nl_team_stat($issue) {
	$season = nl_campaign_season(is_array($issue) ? $issue : array());
	$year = (int)$season['year'];
	$agg = nl_aggregate_flights(max(2021, $year - 4), $year);
	$series = (isset($agg['team']) && is_array($agg['team'])) ? $agg['team'] : array();
	$row = isset($series[$year]) ? $series[$year] : nl_finish_metric(nl_blank_metric());
	$row['series'] = $series;
	$row['seasonYear'] = $year;
	$row['seasonLabel'] = '';
	$row['show'] = array('einsaetze', 'felder', 'fund', 'flaeche');
	return $row;
}

function nl_team_results_html($issue) {
	$stat = nl_team_stat($issue);
	$metrics = nl_stat_metrics();
	$units = array('flaeche' => 'ha', 'einsaetze' => '', 'felder' => '', 'fund' => '');
	$order = array('flaeche', 'felder', 'einsaetze', 'fund');
	$kpiColor = array('flaeche' => '#1565c0', 'felder' => '#c0392b', 'einsaetze' => '#e67e22', 'fund' => '#2e7d32');
	$html = '<div class="nl-results-live">';
	$flat = !empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf']);
	if ($flat) {
		$mailOnly = !empty($GLOBALS['nl_for_mail']) && empty($GLOBALS['nl_for_pdf']);
		$kpiFont = 'Calibri,Segoe UI,sans-serif';
		$html .= '<table class="nl-kpis" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:6px;margin:8px 0 4px;"><tr>';
	} else {
		$html .= '<div class="nl-kpis">';
	}
	foreach ($order as $key) {
		$num = number_format((int)($stat[$key] ?? 0), 0, '.', "'");
		$unit = ($units[$key] !== '') ? '<span class="nl-kpi-unit">' . nl_h($units[$key]) . '</span>' : '';
		$label = $metrics[$key] ?? $key;
		if ($flat) {
			$html .= '<td width="25%" align="center" style="background:#f7fbf5;border-top:7px solid ' . $kpiColor[$key] . ';padding:8px 4px;font-family:' . $kpiFont . ';">';
			$html .= '<div style="font-size:22px;font-weight:700;line-height:1.05;color:' . $kpiColor[$key] . ';-webkit-print-color-adjust:exact;print-color-adjust:exact;">' . nl_h($num) . $unit . '</div>';
			$html .= '<div style="font-size:11px;color:#555;margin-top:3px;">' . nl_h($label) . '</div></td>';
		} else {
			$html .= '<div class="nl-kpi nl-kpi-' . nl_h($key) . '"><span class="nl-kpi-val">' . nl_h($num) . $unit . '</span><span class="nl-kpi-label">' . nl_h($label) . '</span></div>';
		}
	}
	$html .= $flat ? '</tr></table>' : '</div>';
	$html .= '<div class="nl-chart-wrap">' . nl_stat_chart_svg($stat, (string)($issue['id'] ?? '')) . '</div>';
	$html .= '</div>';
	return $html;
}

function nl_issue_lead_html($issue) {
	$intro = trim((string)($issue['intro'] ?? ''));
	$disc = trim((string)($issue['discussion'] ?? ''));
	$showResults = nl_issue_resultate_on($issue);
	if (!$showResults) {
		$disc = '';
	}
	$introHtml = ($intro !== '') ? ('<div class="nl-intro-text" style="margin:0 0 1.45em;">' . nl_text_to_html($intro) . '</div>') : '';
	$discHtml = ($disc !== '') ? ('<div class="nl-discussion">' . nl_text_to_html($disc) . '</div>') : '';
	$text = $introHtml . $discHtml;
	$cap = trim((string)($issue['resultsCaption'] ?? ''));
	$media = '';
	$isStat = (nl_issue_results_source($issue) === 'statistik');
	if (!$showResults) {
		$isStat = false;
	} elseif ($isStat) {
		$media = nl_team_results_html($issue);
	} else {
		$results = trim((string)($issue['resultsImage'] ?? ''));
		if ($results !== '' || $cap !== '') {
			$media = '<figure class="nl-results">';
			if ($results !== '') {
				$src = nl_img_src($results);
				$sideAttr = (!empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf'])) ? ' class="nl-pdf-side"' : '';
				$media .= nl_mail_link_img('<img' . $sideAttr . ' src="' . nl_h($src !== '' ? $src : $results) . '" alt="' . nl_h(trim(strip_tags($cap))) . '">', nl_mail_image_href($results));
			}
			if ($cap !== '') {
				$media .= '<figcaption>' . nl_text_to_html($cap) . '</figcaption>';
			}
			$media .= '</figure>';
		}
	}
	if ($text === '' && $media === '') {
		return '';
	}
	if ($media === '') {
		return $text;
	}
	if ($text === '') {
		return '<article class="nl-mail-sec nl-lead-block">' . $media . '</article>';
	}
	if ($isStat) {
		return '<article class="nl-mail-sec nl-lead-block">' . $introHtml . $media . $discHtml . '</article>';
	}
	$forSide = !empty($GLOBALS['nl_for_pdf']) || !empty($GLOBALS['nl_for_mail']);
	if ($forSide) {
		return '<article class="nl-mail-sec nl-lead-block"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr>'
			. '<td valign="top" style="padding:0 16px 0 0;">' . $text . '</td>'
			. '<td valign="top" width="42%" style="width:42%;">' . $media . '</td>'
			. '</tr></table></article>';
	}
	return '<article class="nl-mail-sec nl-lead-block"><div class="nl-sec-pics">' . $media . '</div>' . $text . '</article>';
}

function nl_render_newsletter($issue, $person, $hooks = false) {
	$art = (string)($person['anredeArt'] ?? 'Sie');
	$closing = ($art === 'Du') ? (string)($issue['closingDu'] ?? '') : (string)($issue['closingSie'] ?? '');
	$settings = nl_settings();
	$unsub = trim((string)($settings['unsubscribeEmail'] ?? ''));
	$public = !empty($person['public']);
	$prevPublicMedia = !empty($GLOBALS['nl_public_media']);
	if ($public) {
		$GLOBALS['nl_public_media'] = true;
	}
	$body = '';
	$lead = nl_issue_lead_html($issue);
	if (isset($issue['sections']) && is_array($issue['sections']) && count($issue['sections']) > 0) {
		$body = $lead . nl_sections_to_html($issue['sections']);
	}
	if ($body === '') {
		$body = $lead . nl_sanitize_html((string)($issue['bodyHtml'] ?? ''));
	}
	$html = '<div class="nl-mail">';
	$tone = ($art === 'Du') ? 'nl-du' : 'nl-sie';
	$toneAttr = $hooks ? ' class="' . $tone . '"' : '';
	$greet = '';
	$logo = '';
	if (!$public) {
		$greet = '<p class="nl-greet" style="margin:0;"><strong' . $toneAttr . nl_hid($hooks, 'nl-pv-greet') . '>' . nl_h(nl_greeting($person)) . '</strong></p>';
	}
	$header = trim((string)($issue['headerImage'] ?? ''));
	if ($header !== '' && !$public) {
		$src = nl_img_src($header);
		if ($src !== '') {
			$logo = '<p class="nl-logo" style="width:132px;margin:0;"' . nl_hid($hooks, 'nl-pv-logo') . '>' . nl_mail_link_img('<img src="' . nl_h($src) . '" alt="" style="width:132px;height:auto;display:block;">', nl_mail_image_href($header)) . '</p>';
		}
	} elseif ($hooks) {
		$logo = '<p class="nl-logo" id="nl-pv-logo" hidden></p>';
	}
	if ($greet !== '' || $logo !== '') {
		if (!empty($GLOBALS['nl_for_mail'])) {
			$html .= '<table class="nl-mail-head" role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;border-collapse:collapse;"><tr>';
			$html .= '<td valign="top" style="padding:2px 16px 0 0;">' . $greet . '</td>';
			$html .= '<td valign="top" width="132" align="right" style="width:132px;">' . $logo . '</td>';
			$html .= '</tr></table>';
		} else {
			$html .= '<div class="nl-mail-head">' . $greet . $logo . '</div>';
		}
	}
	$headline = trim((string)($issue['title'] ?? ''));
	if ($headline !== '' && nl_issue_legacy($issue) && stripos($body, $headline) === false) {
		$html .= '<p class="nl-issue-headline"><strong>' . nl_h($headline) . '</strong></p>';
	}
	$html .= '<div class="nl-mail-body"' . nl_hid($hooks, 'nl-pv-body') . '>' . $body . '</div>';
	$closing = nl_strip_visual_footnote_from_closing($closing);
	$html .= '<div' . $toneAttr . nl_hid($hooks, 'nl-pv-closing') . '>' . nl_text_to_html($closing) . '</div>';
	$footnote = trim((string)($issue['footnote'] ?? ''));
	if (nl_is_stock_footnote_text($footnote)) {
		$footnote = '';
	}
	if ($footnote !== '' || $hooks) {
		$html .= '<div class="nl-footnote"' . nl_hid($hooks, 'nl-pv-footnote') . '>' . nl_text_to_html($footnote) . '</div>';
	}
	$html .= nl_default_footnote_html($art);
	if ($footnote === '' && $unsub !== '' && !$public) {
		$html .= '<p style="font-size:12px;color:#555">Abmelden: <a href="mailto:' . nl_h($unsub) . '?subject=Newsletter%20abmelden">' . nl_h($unsub) . '</a></p>';
	}
	$html .= '</div>';
	if (!$prevPublicMedia) {
		$GLOBALS['nl_public_media'] = false;
	}
	return $html;
}

function nl_public_base() {
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	$scheme = $https ? 'https' : 'http';
	$host = (string)($_SERVER['HTTP_HOST'] ?? '');
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$dir = rtrim(dirname($script), '/');
	if (substr($dir, -strlen('/newsletter')) !== '/newsletter') {
		$dir .= '/newsletter';
	}
	return $scheme . '://' . $host . $dir;
}

function nl_issue_ym($issue) {
	$id = (string)($issue['id'] ?? '');
	if (preg_match('/^(\d{4})-(\d{2})$/', $id, $m)) {
		return $m[1] . '.' . $m[2];
	}
	$created = (string)($issue['created'] ?? '');
	if (preg_match('/^(\d{4})-(\d{2})/', $created, $m)) {
		return $m[1] . '.' . $m[2];
	}
	return '';
}

function nl_issue_public_label($issue) {
	$id = (string)($issue['id'] ?? '');
	if (preg_match('/^(\d{4})-(\d{2})$/', $id, $m)) {
		$months = array('01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dez');
		$mon = isset($months[$m[2]]) ? $months[$m[2]] : $m[2];
		return "Newsletter " . $mon . "'" . substr($m[1], 2);
	}
	$title = trim((string)($issue['title'] ?? ''));
	return $title !== '' ? $title : $id;
}

function nl_embed_snippet() {
	$url = nl_public_base() . '/rkrk_newsletter_public.php?MenuOn=off';
	return '<iframe src="' . $url . '" style="width:100%;min-height:960px;border:0" title="Newsletter Rehkitzrettung"></iframe>';
}

function nl_xlsx_shared_strings($xml) {
	$strings = array();
	if (!preg_match_all('#<si\b[^>]*>(.*?)</si>#s', (string)$xml, $blocks)) {
		return $strings;
	}
	foreach ($blocks[1] as $inner) {
		preg_match_all('#<t[^>]*>([^<]*)</t>#', $inner, $texts);
		$joined = implode('', $texts[1]);
		$joined = html_entity_decode($joined, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$joined = str_replace('_x000B_', "\n", $joined);
		$strings[] = $joined;
	}
	return $strings;
}

function nl_xlsx_sheet_rows($path, $sheetFile) {
	if (!class_exists('ZipArchive')) {
		return array('error' => 'ZipArchive fehlt in PHP.');
	}
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		return array('error' => 'Datei kann nicht geöffnet werden.');
	}
	$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
	$shared = $sharedXml ? nl_xlsx_shared_strings($sharedXml) : array();
	$sheet = $zip->getFromName($sheetFile);
	$zip->close();
	if ($sheet === false) {
		return array('error' => 'Blatt nicht gefunden: ' . $sheetFile);
	}
	$rows = array();
	if (!preg_match_all('#<row\b[^>]*r="(\d+)"[^>]*>(.*?)</row>#s', $sheet, $rowMatches, PREG_SET_ORDER)) {
		return $rows;
	}
	foreach ($rowMatches as $rowMatch) {
		$rn = (int)$rowMatch[1];
		$cells = array();
		if (preg_match_all('#<c\b([^>]*)(?:/>|>(.*?)</c>)#s', $rowMatch[2], $cellMatches, PREG_SET_ORDER)) {
			foreach ($cellMatches as $cell) {
				$attrs = $cell[1];
				$body = isset($cell[2]) ? $cell[2] : '';
				if (!preg_match('#r="([A-Z]+)\d+"#', $attrs, $ref)) {
					continue;
				}
				$value = '';
				if (strpos($attrs, 't="s"') !== false && preg_match('#<v>([^<]*)</v>#', $body, $v)) {
					$idx = (int)$v[1];
					$value = isset($shared[$idx]) ? $shared[$idx] : '';
				} elseif (strpos($attrs, 't="inlineStr"') !== false && preg_match_all('#<t[^>]*>([^<]*)</t>#', $body, $ts)) {
					$value = html_entity_decode(implode('', $ts[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
				} elseif (preg_match('#<v>([^<]*)</v>#', $body, $v)) {
					$value = html_entity_decode($v[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				}
				$cells[$ref[1]] = $value;
			}
		}
		$rows[$rn] = $cells;
	}
	return $rows;
}

function nl_excel_date($value) {
	if (!is_numeric($value)) {
		return trim((string)$value);
	}
	$serial = (float)$value;
	if ($serial < 20000 || $serial > 80000) {
		return trim((string)$value);
	}
	$unix = (int)round(($serial - 25569) * 86400);
	return gmdate('Y-m-d H:i:s', $unix);
}

function nl_import_newsletter_xlsx($path) {
	$rows = nl_xlsx_sheet_rows($path, 'xl/worksheets/sheet1.xml');
	if (isset($rows['error'])) {
		return $rows;
	}
	$items = nl_items('newsletter_recipients.json');
	$byMail = array();
	foreach ($items as $i => $item) {
		$mail = strtolower(trim((string)($item['email'] ?? '')));
		if ($mail !== '') {
			$byMail[$mail] = $i;
		}
	}
	$added = 0;
	$updated = 0;
	foreach ($rows as $rn => $cells) {
		if ($rn < 12) {
			continue;
		}
		$email = trim((string)($cells['P'] ?? ''));
		$name = trim((string)($cells['F'] ?? ''));
		$vorname = trim((string)($cells['E'] ?? ''));
		if ($email === '' && $name === '' && $vorname === '') {
			continue;
		}
		$record = array(
			'vorname' => $vorname,
			'name' => $name,
			'firma' => trim((string)($cells['G'] ?? '')),
			'anredeArt' => (trim((string)($cells['H'] ?? '')) === 'Du') ? 'Du' : 'Sie',
			'geschlecht' => strtolower(trim((string)($cells['I'] ?? ''))),
			'anrede' => trim((string)($cells['J'] ?? '')),
			'summe' => trim((string)($cells['K'] ?? '')),
			'merci' => trim((string)($cells['L'] ?? '')),
			'statusGeschenk' => trim((string)($cells['M'] ?? '')),
			'rechnungsadresse' => trim((string)($cells['N'] ?? '')),
			'gruppe' => trim((string)($cells['O'] ?? '')),
			'email' => $email,
			'bemerkung' => trim((string)($cells['Q'] ?? '')),
			'cc' => trim((string)($cells['R'] ?? '')),
			'anhang' => trim((string)($cells['S'] ?? '')),
		);
		$key = strtolower($email);
		if ($key !== '' && isset($byMail[$key])) {
			$idx = $byMail[$key];
			$items[$idx] = array_merge($items[$idx], $record);
			$updated++;
		} else {
			$record['id'] = 'n' . $rn;
			$record['ausloesen'] = (strtolower(trim((string)($cells['B'] ?? ''))) === 'ja');
			$record['status'] = trim((string)($cells['C'] ?? ''));
			$record['dateSend'] = nl_excel_date($cells['D'] ?? '');
			$items[] = $record;
			if ($key !== '') {
				$byMail[$key] = count($items) - 1;
			}
			$added++;
		}
	}
	nl_save_items('newsletter_recipients.json', $items);
	$subject = trim((string)($rows[3]['C'] ?? ''));
	if ($subject !== '') {
		$issues = nl_items('newsletter_issues.json');
		if (isset($issues[0]) && trim((string)($issues[0]['subject'] ?? '')) === '') {
			$issues[0]['subject'] = $subject;
			nl_save_items('newsletter_issues.json', $issues);
		}
	}
	return array('added' => $added, 'updated' => $updated);
}

function nl_norm_place($value) {
	$value = mb_strtolower(trim((string)$value), 'UTF-8');
	$value = strtr($value, array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss'));
	return preg_replace('/[^a-z0-9]+/', '', $value);
}

function nl_gemeinde_code($label) {
	$norm = nl_norm_place($label);
	$aliases = array(
		'mettmenstetten' => 'MET',
		'aeugst' => 'AeU',
		'abtwil' => 'AB',
		'hausen' => 'HaA',
		'knonau' => 'KN',
		'maschwanden' => 'MA',
		'rifferswil' => 'RIF',
		'sins' => 'SI',
		'stallikon' => 'STA',
		'kappel' => 'KaA',
		'dietwil' => 'DIE',
		'oberrueti' => 'OR',
		'oberruti' => 'OR',
		'affoltern' => 'AaA',
	);
	foreach ($aliases as $needle => $code) {
		if ($needle !== '' && strpos($norm, $needle) !== false) {
			return $code;
		}
	}
	return '';
}

function nl_qr_rel($betrag) {
	$betrag = preg_replace('/\D+/', '', (string)$betrag);
	if ($betrag === '') {
		return '';
	}
	$rel = 'media/qr/qr' . $betrag . '.png';
	if (is_file(__DIR__ . '/' . $rel)) {
		return $rel;
	}
	return '';
}

function nl_import_gemeinde_xlsx($path, $campaignId) {
	$rows = nl_xlsx_sheet_rows($path, 'xl/worksheets/sheet1.xml');
	if (isset($rows['error'])) {
		return $rows;
	}
	$items = nl_items('newsletter_gemeinden.json');
	$byName = array();
	foreach ($items as $i => $item) {
		$byName[nl_norm_place($item['gemeinde'] ?? '')] = $i;
	}
	$kampagnen = nl_items('newsletter_kampagnen.json');
	$campIndex = null;
	foreach ($kampagnen as $i => $camp) {
		if ((string)($camp['id'] ?? '') === (string)$campaignId) {
			$campIndex = $i;
			break;
		}
	}
	if ($campIndex === null) {
		$blankYear = (int)substr(preg_replace('/\D+/', '', (string)$campaignId), 0, 4);
		if ($blankYear < 2000 || $blankYear > 2100) {
			$blankYear = nl_current_season_year();
		}
		list($blankFrom, $blankTo) = nl_season_dates($blankYear);
		$kampagnen[] = array(
			'id' => (string)$campaignId,
			'title' => 'Gemeindebrief ' . $campaignId,
			'from' => $blankFrom,
			'to' => $blankTo,
			'ortDatum' => 'Knonau',
			'sharedHtml' => '',
			'letters' => array(),
			'texts' => array(),
		);
		$campIndex = count($kampagnen) - 1;
	}
	$added = 0;
	$updated = 0;
	foreach ($rows as $rn => $cells) {
		if ($rn < 2) {
			continue;
		}
		$gemeinde = trim((string)($cells['B'] ?? ''));
		if ($gemeinde === '' || strtolower($gemeinde) === 'gemeinde') {
			continue;
		}
		$betrag = trim((string)($cells['Q'] ?? ''));
		$record = array(
			'gemeinde' => $gemeinde,
			'anschrift1' => trim((string)($cells['C'] ?? '')),
			'anschrift2' => trim((string)($cells['D'] ?? '')),
			'strasse' => trim((string)($cells['H'] ?? '')),
			'strasse2' => trim((string)($cells['I'] ?? '')),
			'plz' => trim((string)($cells['J'] ?? '')),
			'ort' => trim((string)($cells['K'] ?? '')),
			'email' => trim((string)($cells['L'] ?? '')),
			'gdeCode' => nl_gemeinde_code($gemeinde),
		);
		$seriesId = (string)$campaignId;
		$letter = array(
			'gemeinde' => $gemeinde,
			'anschrift1' => $record['anschrift1'],
			'anschrift2' => $record['anschrift2'],
			'strasse' => $record['strasse'],
			'strasse2' => $record['strasse2'],
			'plz' => $record['plz'],
			'ort' => $record['ort'],
			'email' => $record['email'],
			'anrede' => trim((string)($cells['E'] ?? '')),
			'text' => trim((string)($cells['F'] ?? '')),
			'dank' => trim((string)($cells['G'] ?? '')),
			'bild1' => nl_series_media($seriesId, $cells['M'] ?? ''),
			'cap1' => trim((string)($cells['N'] ?? '')),
			'bild2' => nl_series_media($seriesId, $cells['O'] ?? ''),
			'cap2' => trim((string)($cells['P'] ?? '')),
			'betrag' => $betrag,
			'qr' => nl_series_media($seriesId, $cells['R'] ?? ''),
			'flugdaten' => nl_series_media($seriesId, $cells['S'] ?? ''),
			'absender' => trim((string)($cells['T'] ?? '')),
			'bemerkung' => trim((string)($cells['U'] ?? '')),
		);
		if ($letter['qr'] === '') {
			$letter['qr'] = nl_qr_rel($betrag);
		}
		$key = nl_norm_place($gemeinde);
		if (isset($byName[$key])) {
			$idx = $byName[$key];
			$items[$idx] = array_merge($items[$idx], $record);
			$id = $items[$idx]['id'];
			$updated++;
		} else {
			$record['id'] = 'g' . $rn;
			$record['ausloesen'] = false;
			$record['aktiv'] = true;
			$items[] = $record;
			$id = $record['id'];
			$byName[$key] = count($items) - 1;
			$added++;
		}
		if ($campIndex !== null) {
			if (!isset($kampagnen[$campIndex]['letters']) || !is_array($kampagnen[$campIndex]['letters'])) {
				$kampagnen[$campIndex]['letters'] = array();
			}
			$kampagnen[$campIndex]['letters'][$id] = $letter;
			$kampagnen[$campIndex]['texts'][$id] = $letter;
		}
	}
	nl_save_items('newsletter_gemeinden.json', $items);
	if ($campIndex !== null) {
		nl_save_items('newsletter_kampagnen.json', $kampagnen);
	}
	return array('added' => $added, 'updated' => $updated);
}

function nl_series_media($series, $path) {
	$base = basename(str_replace('\\', '/', trim((string)$path)));
	if ($base === '' || $base === '.' || $base === '..') {
		return '';
	}
	$rel = 'media/gemeinde/' . preg_replace('/[^0-9A-Za-z_-]/', '', (string)$series) . '/' . $base;
	if (is_file(__DIR__ . '/' . $rel)) {
		return $rel;
	}
	if (nl_media_src($base) !== '') {
		return nl_media_src($base);
	}
	return '';
}

function nl_store_upload($field, $subdir, $kind = 'newsletter') {
	if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
		return '';
	}
	$orig = (string)($_FILES[$field]['name'] ?? 'datei');
	$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
	$allowed = array('png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf');
	if (!in_array($ext, $allowed, true)) {
		return '';
	}
	$kind = ($kind === 'gemeinde') ? 'gemeinde' : 'newsletter';
	$subdir = trim(str_replace('\\', '/', (string)$subdir), '/');
	$issueId = '';
	if ($kind === 'newsletter' && preg_match('#^issues/([A-Za-z0-9._-]+)$#', $subdir, $issueMatch)) {
		$issueId = $issueMatch[1];
	}
	$dir = ($issueId !== '') ? nl_issue_media_dir($issueId) : (nl_media_dir($kind) . '/' . $subdir);
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	$base = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($orig, PATHINFO_FILENAME));
	$name = $base . '_' . date('YmdHis') . '.' . $ext;
	$dest = $dir . '/' . $name;
	if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
		return '';
	}
	if ($issueId !== '') {
		return 'issues/' . $issueId . '/' . $name;
	}
	return 'media/' . $subdir . '/' . $name;
}

function nl_flight_csv_dir() {
	global $csvDocLocation;
	if (!empty($csvDocLocation) && is_dir($csvDocLocation)) {
		return rtrim(str_replace('\\', '/', $csvDocLocation), '/') . '/';
	}
	return dirname(__DIR__) . '/../rkrk/webcsv/';
}

function nl_current_season_year() {
	$year = (int)date('Y');
	if ((int)date('n') < 4) {
		$year--;
	}
	if ($year < 2000) {
		$year = 2000;
	}
	return $year;
}

function nl_season_dates($year) {
	$year = (int)$year;
	if ($year < 2000 || $year > 2100) {
		$year = nl_current_season_year();
	}
	return array(sprintf('%04d-04-01', $year), sprintf('%04d-07-31', $year));
}

function nl_season_year_of($rec) {
	if (!is_array($rec)) {
		$rec = array();
	}
	$from = trim((string)($rec['from'] ?? ''));
	$to = trim((string)($rec['to'] ?? ''));
	if (preg_match('/^(20\d{2})-04-01$/', $from, $fromMatch) && preg_match('/^(20\d{2})-07-31$/', $to, $toMatch) && $fromMatch[1] === $toMatch[1]) {
		return (int)$fromMatch[1];
	}
	foreach (array('seasonYear', 'saisonJahr', 'saison') as $key) {
		if (!isset($rec[$key])) {
			continue;
		}
		$raw = trim((string)$rec[$key]);
		if (preg_match('/^(20\d{2})$/', $raw, $yearMatch)) {
			return (int)$yearMatch[1];
		}
	}
	$id = preg_replace('/\D+/', '', (string)($rec['id'] ?? ''));
	$year = (int)substr($id, 0, 4);
	if ($year >= 2000 && $year <= 2100) {
		return $year;
	}
	if (preg_match('/^(20\d{2})-\d{2}-\d{2}$/', $to, $bisMatch)) {
		return (int)$bisMatch[1];
	}
	return nl_current_season_year();
}

function nl_brief_result_year($camp) {
	if (!is_array($camp)) {
		$camp = array();
	}
	$id = preg_replace('/\D+/', '', (string)($camp['id'] ?? ''));
	if (strlen($id) >= 6) {
		$year = (int)substr($id, 0, 4);
		$month = (int)substr($id, 4, 2);
		if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
			if ($month < 4) {
				$year--;
			}
			if ($year < 2000) {
				$year = 2000;
			}
			return $year;
		}
	}
	return nl_season_year_of($camp);
}

function nl_apply_brief_season(&$rec) {
	if (!is_array($rec)) {
		$rec = array();
	}
	$year = nl_brief_result_year($rec);
	list($rec['from'], $rec['to']) = nl_season_dates($year);
}

function nl_campaign_season($camp) {
	$year = nl_season_year_of(is_array($camp) ? $camp : array());
	list($from, $to) = nl_season_dates($year);
	return array(
		'year' => $year,
		'from' => $from,
		'to' => $to,
		'label' => 'Saison ' . $year,
	);
}

function nl_apply_season_post(&$rec) {
	if (!is_array($rec)) {
		$rec = array();
	}
	$year = (int)($_POST['seasonYear'] ?? 0);
	if ($year < 2000 || $year > 2100) {
		$year = nl_season_year_of($rec);
	}
	list($rec['from'], $rec['to']) = nl_season_dates($year);
}

function nl_season_year_select($record, $brief = false) {
	$record = is_array($record) ? $record : array();
	$year = $brief ? nl_brief_result_year($record) : nl_season_year_of($record);
	$now = nl_current_season_year();
	$max = ($now > $year) ? $now : $year;
	$max++;
	$min = ($year < 2020) ? $year : 2020;
	$out = "<label>Saison</label><select name='seasonYear'>";
	for ($y = $max; $y >= $min; $y--) {
		$out .= "<option value='" . $y . "'" . ($y === $year ? " selected" : "") . ">" . $y . "</option>";
	}
	$out .= "</select>";
	return $out;
}

function nl_blank_metric() {
	return array('flaeche' => 0, 'felder' => 0, 'fund' => 0, 'tage' => array());
}

function nl_finish_metric($row) {
	$row['einsaetze'] = isset($row['tage']) && is_array($row['tage']) ? count($row['tage']) : 0;
	unset($row['tage']);
	return $row;
}

function nl_stat_metrics() {
	return array(
		'flaeche' => 'Fläche',
		'einsaetze' => 'Einsatztage',
		'felder' => 'Felder',
		'fund' => 'Rehkitze',
	);
}

function nl_stat_show_list($gemeinde) {
	$all = array_keys(nl_stat_metrics());
	$raw = trim((string)($gemeinde['statShow'] ?? ''));
	if ($raw === '' ) {
		return $all;
	}
	if ($raw === 'none') {
		return array();
	}
	$picked = array();
	foreach (explode(',', $raw) as $one) {
		$one = trim($one);
		if (in_array($one, $all, true)) {
			$picked[] = $one;
		}
	}
	return count($picked) > 0 ? $picked : $all;
}

function nl_jg_choices() {
	global $RegionSettings;
	$opts = array('' => '—');
	if (!is_array($RegionSettings)) {
		return $opts;
	}
	foreach ($RegionSettings as $reg) {
		if (empty($reg['Aktiv'])) {
			continue;
		}
		$code = trim((string)($reg['Reg'] ?? ''));
		if ($code === '' || $code === 'ALL' || $code === 'NO') {
			continue;
		}
		$opts[$code] = (string)($reg['Text'] ?? $code);
	}
	return $opts;
}

function nl_jg_for_place($name) {
	$norm = nl_norm_place($name);
	if ($norm === '') {
		return '';
	}
	foreach (nl_jg_choices() as $code => $label) {
		if ($code === '') {
			continue;
		}
		$place = nl_norm_place(preg_replace('/^\d+\s*-\s*/', '', (string)$label));
		if ($place !== '' && strlen($place) >= 4 && strpos($norm, $place) !== false) {
			return (string)$code;
		}
	}
	return '';
}

function nl_aggregate_flights($yearFrom, $yearTo) {
	global $OptionGemeinde, $RegionSettings, $RKRKFlightsCSV;
	$suffix = !empty($RKRKFlightsCSV) ? $RKRKFlightsCSV : '_RKRKFluege.csv';
	$dir = nl_flight_csv_dir();
	$series = array('gde' => array(), 'jg' => array());
	$team = array();
	$today = date('Y-m-d');
	for ($year = (int)$yearFrom; $year <= (int)$yearTo; $year++) {
		list($from, $to) = nl_season_dates($year);
		$file = $dir . $year . $suffix;
		if (!is_file($file) || !function_exists('csv_to_array')) {
			continue;
		}
		$rows = csv_to_array($file, ';');
		if (!is_array($rows)) {
			continue;
		}
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			if (array_key_exists('ListNr', $row) && trim((string)$row['ListNr']) === '') {
				continue;
			}
			$datum = trim((string)($row['Datum'] ?? ''));
			if ($datum === '' || $datum < $from || $datum > $to || $datum > $today) {
				continue;
			}
			$geflogen = trim((string)($row['Geflogen'] ?? ''));
			if ($geflogen !== 'Ja' && $datum > '2023-01-01') {
				continue;
			}
			$gde = trim((string)($row['Gde'] ?? ''));
			if ($gde === '') {
				$gde = 'andere';
			}
			$jg = trim((string)($row['JG'] ?? ''));
			if ($jg === '') {
				$jg = 'andere';
			}
			$ha = (int)round((float)str_replace(',', '.', (string)($row['Flaeche'] ?? 0)));
			$fund = (trim((string)($row['Kontrollflug'] ?? '')) === 'Ja') ? 0 : (int)($row['Fund'] ?? 0);
			if (!isset($team[$year])) {
				$team[$year] = nl_blank_metric();
			}
			$team[$year]['felder'] += 1;
			$team[$year]['flaeche'] += $ha;
			$team[$year]['fund'] += $fund;
			$team[$year]['tage'][$datum] = $datum;
			foreach (array('gde' => $gde, 'jg' => $jg) as $kind => $code) {
				if (!isset($series[$kind][$code][$year])) {
					$series[$kind][$code][$year] = nl_blank_metric();
				}
				$series[$kind][$code][$year]['felder'] += 1;
				$series[$kind][$code][$year]['flaeche'] += $ha;
				$series[$kind][$code][$year]['fund'] += $fund;
				$series[$kind][$code][$year]['tage'][$datum] = $datum;
			}
		}
	}
	$labels = array('gde' => array(), 'jg' => array());
	foreach ($series as $kind => $codes) {
		foreach ($codes as $code => $years) {
			foreach ($years as $y => $metric) {
				$series[$kind][$code][$y] = nl_finish_metric($metric);
			}
			ksort($series[$kind][$code]);
			if ($kind === 'gde' && isset($OptionGemeinde[$code]['Text'])) {
				$labels[$kind][$code] = $OptionGemeinde[$code]['Text'];
			} elseif ($kind === 'jg' && isset($RegionSettings[$code]['Text'])) {
				$labels[$kind][$code] = $RegionSettings[$code]['Text'];
			} else {
				$labels[$kind][$code] = $code;
			}
		}
	}
	foreach ($team as $y => $metric) {
		$team[$y] = nl_finish_metric($metric);
	}
	ksort($team);
	return array('series' => $series, 'labels' => $labels, 'team' => $team);
}

function nl_flight_bundle($camp) {
	$camp = is_array($camp) ? $camp : array();
	$year = nl_brief_result_year($camp);
	list($from, $to) = nl_season_dates($year);
	$season = array(
		'year' => $year,
		'from' => $from,
		'to' => $to,
		'label' => 'Saison ' . $year,
	);
	$agg = nl_aggregate_flights(2021, (int)$season['year']);
	$bundle = array(
		'gde' => array(),
		'jg' => array(),
		'series' => $agg['series'],
		'labels' => $agg['labels'],
		'season' => $season,
	);
	foreach (array('gde', 'jg') as $kind) {
		foreach ($agg['series'][$kind] as $code => $years) {
			$metric = isset($years[$season['year']]) ? $years[$season['year']] : nl_finish_metric(nl_blank_metric());
			$metric['code'] = $code;
			$metric['label'] = $agg['labels'][$kind][$code] ?? $code;
			$bundle[$kind][$code] = $metric;
		}
	}
	return $bundle;
}

function nl_gemeinde_stats($from, $to) {
	$yearFrom = (int)substr((string)$from, 0, 4);
	$yearTo = (int)substr((string)$to, 0, 4);
	if ($yearTo < 2000) {
		$yearTo = (int)date('Y');
	}
	if ($yearFrom < 2000) {
		$yearFrom = $yearTo;
	}
	return nl_flight_bundle(array('id' => $yearTo . '07', 'to' => $to));
}

function nl_stats_for_address($bundle, $gemeinde) {
	$sicht = ((string)($gemeinde['statSicht'] ?? 'gde') === 'jg') ? 'jg' : 'gde';
	if ($sicht === 'jg') {
		$code = trim((string)($gemeinde['jgCode'] ?? ''));
		if ($code === '') {
			$code = nl_jg_for_place($gemeinde['gemeinde'] ?? '');
		}
	} else {
		$code = trim((string)($gemeinde['gdeCode'] ?? ''));
		if ($code === '') {
			$code = nl_gemeinde_code($gemeinde['gemeinde'] ?? '');
		}
	}
	$row = (isset($bundle[$sicht][$code]) && is_array($bundle[$sicht][$code]))
		? $bundle[$sicht][$code]
		: nl_finish_metric(nl_blank_metric());
	$row['code'] = $code;
	if (trim((string)($row['label'] ?? '')) === '') {
		if ($sicht === 'jg' && $code !== '' && isset(nl_jg_choices()[$code])) {
			$row['label'] = nl_jg_choices()[$code];
		} else {
			$row['label'] = (string)($gemeinde['gemeinde'] ?? $code);
		}
	}
	$row['sicht'] = $sicht;
	$row['series'] = (isset($bundle['series'][$sicht][$code]) && is_array($bundle['series'][$sicht][$code]))
		? $bundle['series'][$sicht][$code] : array();
	$row['show'] = nl_stat_show_list($gemeinde);
	$row['seasonYear'] = (int)($bundle['season']['year'] ?? date('Y'));
	$row['seasonLabel'] = '';
	$row['from'] = (string)($bundle['season']['from'] ?? '');
	$row['to'] = (string)($bundle['season']['to'] ?? '');
	return $row;
}

function nl_stats_for_gemeinde($stats, $gemeinde) {
	if (is_array($stats) && isset($stats['season'])) {
		return nl_stats_for_address($stats, $gemeinde);
	}
	$code = trim((string)($gemeinde['gdeCode'] ?? ''));
	if ($code === '') {
		$code = nl_gemeinde_code($gemeinde['gemeinde'] ?? '');
	}
	if ($code !== '' && isset($stats[$code])) {
		return $stats[$code];
	}
	$row = nl_finish_metric(nl_blank_metric());
	$row['code'] = $code;
	$row['label'] = (string)($gemeinde['gemeinde'] ?? '');
	$row['sicht'] = 'gde';
	$row['series'] = array();
	$row['show'] = nl_stat_show_list($gemeinde);
	$row['seasonYear'] = (int)date('Y');
	$row['seasonLabel'] = '';
	$row['from'] = '';
	$row['to'] = '';
	return $row;
}

function nl_suggest_gemeinde_text($gemeinde, $stat) {
	$name = trim((string)($stat['label'] ?? ''));
	if ($name === '') {
		$name = trim((string)($gemeinde['gemeinde'] ?? 'der Gemeinde'));
	}
	$show = isset($stat['show']) && is_array($stat['show']) ? $stat['show'] : nl_stat_show_list($gemeinde);
	$parts = array();
	if (in_array('flaeche', $show, true)) {
		$parts[] = (int)$stat['flaeche'] . ' Hektaren Fläche freigegeben';
	}
	if (in_array('einsaetze', $show, true)) {
		$parts[] = 'an ' . (int)$stat['einsaetze'] . ' Einsatztagen gearbeitet';
	}
	if (in_array('felder', $show, true)) {
		$parts[] = (int)$stat['felder'] . ' Felder abgeflogen';
	}
	if (in_array('fund', $show, true)) {
		$parts[] = (int)$stat['fund'] . ' Rehkitze gerettet';
	}
	$year = (int)($stat['seasonYear'] ?? 0);
	$place = (($stat['sicht'] ?? 'gde') === 'jg') ? ('im Jagdgebiet ' . $name) : ('in ' . $name);
	if ($year >= 2000) {
		$lead = 'In der Saison ' . $year . ' haben wir ' . $place . ' ';
	} else {
		$lead = ucfirst($place) . ' haben wir ';
	}
	return $lead . implode(', ', $parts) . '. Die Anzahl ist wetterabhängig und kann sich jährlich verändern.';
}

function nl_axis_top($max) {
	$max = max(1, (float)$max);
	if ($max <= 100) {
		$unit = 5;
	} elseif ($max <= 500) {
		$unit = 10;
	} elseif ($max <= 1000) {
		$unit = 50;
	} else {
		$unit = 100;
	}
	$top = (int)(ceil($max / ($unit * 4)) * ($unit * 4));
	return max($unit * 4, $top);
}

function nl_chart_label_bw($text) {
	return max(28, strlen((string)$text) * 9 + 16);
}

function nl_chart_nudge_labels(array $labels, $minX, $maxX, $minTop, $maxBottom, $rise, $boxH, $gap, $step) {
	$n = count($labels);
	$rise = (float)$rise;
	$boxH = (float)$boxH;
	$gap = (float)$gap;
	$step = max(1, (float)$step);
	$tyMin = $minTop + $rise;
	$tyMax = $maxBottom - ($boxH - $rise);
	if ($tyMax < $tyMin) {
		$tyMax = $tyMin;
	}
	$clamp = function (&$lab) use ($minX, $maxX, $tyMin, $tyMax) {
		$half = $lab['bw'] / 2;
		if ($lab['tx'] - $half < $minX) {
			$lab['tx'] = $minX + $half;
		}
		if ($lab['tx'] + $half > $maxX) {
			$lab['tx'] = $maxX - $half;
		}
		if ($lab['ty'] < $tyMin) {
			$lab['ty'] = $tyMin;
		}
		if ($lab['ty'] > $tyMax) {
			$lab['ty'] = $tyMax;
		}
	};
	$boxOf = function ($lab) use ($rise, $boxH) {
		$half = $lab['bw'] / 2;
		$top = $lab['ty'] - $rise;
		return array($lab['tx'] - $half, $top, $lab['tx'] + $half, $top + $boxH);
	};
	$hit = function ($a, $b) use ($gap) {
		return !($a[2] + $gap <= $b[0] || $b[2] + $gap <= $a[0] || $a[3] + $gap <= $b[1] || $b[3] + $gap <= $a[1]);
	};
	for ($i = 0; $i < $n; $i++) {
		$clamp($labels[$i]);
	}
	for ($pass = 0; $pass < 40; $pass++) {
		$moved = false;
		for ($i = 0; $i < $n; $i++) {
			for ($j = $i + 1; $j < $n; $j++) {
				$a = $boxOf($labels[$i]);
				$b = $boxOf($labels[$j]);
				if (!$hit($a, $b)) {
					continue;
				}
				$moved = true;
				$ox = min($a[2], $b[2]) - max($a[0], $b[0]);
				$oy = min($a[3], $b[3]) - max($a[1], $b[1]);
				$iUp = ((int)$labels[$i]['prefer'] < 0);
				$jUp = ((int)$labels[$j]['prefer'] < 0);
				$tryVert = ($iUp !== $jUp);
				if ($tryVert) {
					$push = max($step, ceil($oy / 2) + 1);
					$labels[$i]['ty'] += $iUp ? -$push : $push;
					$labels[$j]['ty'] += $jUp ? -$push : $push;
					$clamp($labels[$i]);
					$clamp($labels[$j]);
					if (!$hit($boxOf($labels[$i]), $boxOf($labels[$j]))) {
						continue;
					}
				}
				$pushX = max($step, ceil($ox / 2) + 1);
				if ($labels[$i]['tx'] < $labels[$j]['tx'] || ($labels[$i]['tx'] == $labels[$j]['tx'] && $i < $j)) {
					$labels[$i]['tx'] -= $pushX;
					$labels[$j]['tx'] += $pushX;
				} else {
					$labels[$i]['tx'] += $pushX;
					$labels[$j]['tx'] -= $pushX;
				}
				$clamp($labels[$i]);
				$clamp($labels[$j]);
			}
		}
		if (!$moved) {
			break;
		}
	}
	for ($pass = 0; $pass < 24; $pass++) {
		$hitAny = false;
		for ($i = 0; $i < $n; $i++) {
			for ($j = $i + 1; $j < $n; $j++) {
				$a = $boxOf($labels[$i]);
				$b = $boxOf($labels[$j]);
				if (!$hit($a, $b)) {
					continue;
				}
				$hitAny = true;
				$oy = min($a[3], $b[3]) - max($a[1], $b[1]);
				$push = max($step, $oy + $gap);
				if ($labels[$i]['ty'] <= $labels[$j]['ty']) {
					$labels[$i]['ty'] -= $push / 2;
					$labels[$j]['ty'] += $push / 2;
				} else {
					$labels[$j]['ty'] -= $push / 2;
					$labels[$i]['ty'] += $push / 2;
				}
				$clamp($labels[$i]);
				$clamp($labels[$j]);
				$a = $boxOf($labels[$i]);
				$b = $boxOf($labels[$j]);
				if (!$hit($a, $b)) {
					continue;
				}
				$ox = min($a[2], $b[2]) - max($a[0], $b[0]);
				$pushX = max($step, $ox + $gap);
				if ($labels[$i]['tx'] <= $labels[$j]['tx']) {
					$labels[$i]['tx'] -= $pushX / 2;
					$labels[$j]['tx'] += $pushX / 2;
				} else {
					$labels[$i]['tx'] += $pushX / 2;
					$labels[$j]['tx'] -= $pushX / 2;
				}
				$clamp($labels[$i]);
				$clamp($labels[$j]);
			}
		}
		if (!$hitAny) {
			break;
		}
	}
	for ($i = 0; $i < $n; $i++) {
		if (!isset($labels[$i]['py'])) {
			continue;
		}
		$py = (float)$labels[$i]['py'];
		$topBox = $labels[$i]['ty'] - $rise;
		$botBox = $topBox + $boxH;
		if ($py < $topBox - 4 || $py > $botBox + 4) {
			continue;
		}
		if ((int)$labels[$i]['prefer'] < 0) {
			$labels[$i]['ty'] = $py - ($boxH + $gap);
		} else {
			$labels[$i]['ty'] = $py + $rise + $gap + 6;
		}
		$clamp($labels[$i]);
	}
	return $labels;
}

function nl_stat_line_svg($stat, array $seriesKeys) {
	$colors = array('einsaetze' => '#e67e22', 'felder' => '#c0392b', 'fund' => '#2e7d32', 'flaeche' => '#1565c0');
	$seasonYear = (int)($stat['seasonYear'] ?? date('Y'));
	$years = range($seasonYear - 4, $seasonYear);
	$series = isset($stat['series']) && is_array($stat['series']) ? $stat['series'] : array();
	$keys = array_keys($seriesKeys);
	$max = array();
	foreach ($keys as $key) {
		$max[$key] = 1;
		foreach ($years as $y) {
			$v = (int)($series[$y][$key] ?? 0);
			if ($v > $max[$key]) {
				$max[$key] = $v;
			}
		}
	}
	$axisTop = array();
	foreach ($keys as $key) {
		$axisTop[$key] = nl_axis_top($max[$key]);
	}
	$w = 460;
	$h = 300;
	$left = 62;
	$right = 70;
	$top = 84;
	$bottom = 40;
	$plotW = $w - $left - $right;
	$plotH = $h - $top - $bottom;
	$base = $top + $plotH;
	$n = count($years);
	$title = implode(' / ', array_values($seriesKeys));
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" class="nl-chart" width="100%" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . nl_h($title) . '" style="width:100%;height:auto;">';
	$svg .= '<text x="' . ($w / 2) . '" y="16" text-anchor="middle" font-size="15" font-weight="700" font-family="Calibri, sans-serif" fill="#243">' . nl_h($title) . '</text>';
	$lx = 8;
	foreach ($keys as $key) {
		$svg .= '<line x1="' . $lx . '" y1="32" x2="' . ($lx + 18) . '" y2="32" stroke="' . $colors[$key] . '" stroke-width="3"></line>';
		$svg .= '<text x="' . ($lx + 22) . '" y="36" font-size="13" font-weight="700" font-family="Calibri, sans-serif" fill="' . $colors[$key] . '">' . nl_h($seriesKeys[$key]) . '</text>';
		$lx += 190;
	}
	for ($g = 0; $g <= 4; $g++) {
		$yy = $top + ($plotH * $g / 4);
		$svg .= '<line x1="' . $left . '" y1="' . round($yy, 1) . '" x2="' . ($w - $right) . '" y2="' . round($yy, 1) . '" stroke="#e3eae0"></line>';
		$tickY = $base - ($plotH * $g / 4);
		$svg .= '<text x="' . ($left - 4) . '" y="' . round($tickY + 4, 1) . '" text-anchor="end" font-size="12" font-weight="700" font-family="Calibri, sans-serif" fill="' . $colors[$keys[0]] . '">' . (int)round($axisTop[$keys[0]] * $g / 4) . '</text>';
		if (isset($keys[1])) {
			$svg .= '<text x="' . ($w - $right + 4) . '" y="' . round($tickY + 4, 1) . '" text-anchor="start" font-size="12" font-weight="700" font-family="Calibri, sans-serif" fill="' . $colors[$keys[1]] . '">' . (int)round($axisTop[$keys[1]] * $g / 4) . '</text>';
		}
	}
	$svg .= '<line x1="' . $left . '" y1="' . $top . '" x2="' . $left . '" y2="' . $base . '" stroke="#bbb"></line>';
	$svg .= '<line x1="' . ($w - $right) . '" y1="' . $top . '" x2="' . ($w - $right) . '" y2="' . $base . '" stroke="#bbb"></line>';
	$svg .= '<line x1="' . $left . '" y1="' . $base . '" x2="' . ($w - $right) . '" y2="' . $base . '" stroke="#bbb"></line>';
	$drawn = array();
	foreach ($keys as $idx => $key) {
		$pts = array();
		foreach ($years as $i => $y) {
			$v = (int)($series[$y][$key] ?? 0);
			$x = $left + ($i * $plotW / ($n - 1));
			$den = isset($axisTop[$key]) ? (float)$axisTop[$key] : 1;
			if ($den < 1) {
				$den = 1;
			}
			$yPos = $base - ($plotH * ($v / $den));
			$pts[] = array($x, $yPos, $v);
		}
		$d = array();
		foreach ($pts as $p) {
			$d[] = round($p[0], 1) . ',' . round($p[1], 1);
		}
		$svg .= '<polyline fill="none" stroke="' . $colors[$key] . '" stroke-width="2.5" points="' . implode(' ', $d) . '"></polyline>';
		$drawn[$key] = $pts;
	}
	$labels = array();
	for ($i = 0; $i < $n; $i++) {
		$pair = array();
		foreach ($keys as $key) {
			$pair[] = array('key' => $key, 'p' => $drawn[$key][$i]);
		}
		usort($pair, function ($a, $b) {
			if ($a['p'][1] == $b['p'][1]) {
				return 0;
			}
			return ($a['p'][1] < $b['p'][1]) ? -1 : 1;
		});
		$hi = $pair[0];
		$labels[] = array(
			'key' => $hi['key'],
			'i' => $i,
			'tx' => $hi['p'][0],
			'ty' => $hi['p'][1] - 24,
			'bw' => nl_chart_label_bw((string)(int)$hi['p'][2]),
			'prefer' => -1,
			'py' => $hi['p'][1],
		);
		if (!isset($pair[1])) {
			continue;
		}
		$lo = $pair[1];
		$hy = $labels[count($labels) - 1]['ty'];
		$ly = $lo['p'][1] + 28;
		if (($ly - $hy) < 28) {
			$labels[count($labels) - 1]['ty'] -= 10;
			$ly += 10;
		}
		$hx = $hi['p'][0];
		$lx = $lo['p'][0];
		if (abs($hi['p'][1] - $lo['p'][1]) < 22) {
			$hx -= 18;
			$lx += 18;
		}
		$labels[count($labels) - 1]['tx'] = $hx;
		$labels[] = array(
			'key' => $lo['key'],
			'i' => $i,
			'tx' => $lx,
			'ty' => $ly,
			'bw' => nl_chart_label_bw((string)(int)$lo['p'][2]),
			'prefer' => 1,
			'py' => $lo['p'][1],
		);
	}
	$labels = nl_chart_nudge_labels($labels, $left + 2, $w - $right - 2, 48, $base - 2, 12, 16, 5, 4);
	$placed = array();
	foreach ($labels as $lab) {
		$placed[$lab['key']][$lab['i']] = $lab;
	}
	foreach ($keys as $key) {
		foreach ($drawn[$key] as $i => $p) {
			$svg .= '<circle cx="' . round($p[0], 1) . '" cy="' . round($p[1], 1) . '" r="4" fill="#fff" stroke="' . $colors[$key] . '" stroke-width="2"></circle>';
			$label = (string)(int)$p[2];
			$tx = isset($placed[$key][$i]) ? $placed[$key][$i]['tx'] : $p[0];
			$ty = isset($placed[$key][$i]) ? $placed[$key][$i]['ty'] : ($p[1] - 16);
			$bw = isset($placed[$key][$i]) ? $placed[$key][$i]['bw'] : nl_chart_label_bw($label);
			$svg .= '<rect x="' . round($tx - $bw / 2, 1) . '" y="' . round($ty - 12, 1) . '" width="' . round($bw, 1) . '" height="15" rx="2" fill="#fff" opacity="0.94"></rect>';
			$svg .= '<text x="' . round($tx, 1) . '" y="' . round($ty, 1) . '" text-anchor="middle" font-size="13" font-weight="700" font-family="Calibri, sans-serif" fill="' . $colors[$key] . '">' . $label . '</text>';
		}
	}
	foreach ($years as $i => $y) {
		$x = $left + ($i * $plotW / ($n - 1));
		$svg .= '<text x="' . round($x, 1) . '" y="' . ($base + 16) . '" text-anchor="middle" font-size="12" font-weight="700" font-family="Calibri, sans-serif" fill="#333">' . (int)$y . '</text>';
	}
	$svg .= '</svg>';
	return $svg;
}

function nl_flug_charts_iframe($year) {
	$q = http_build_query(array(
		'MenuOn' => 'off',
		'GA' => 'gast',
		'Color' => 'white',
		'Embed' => '1',
		'Charts' => 'Entwicklung2,Entwicklung1',
		'Jahr' => (int)$year,
		'ChartHeight' => 240,
		'ChartWidth' => 560,
	));
	$src = '../rkrk_flugstatistik.php?' . $q;
	return '<iframe class="nl-flug-frame" title="Entwicklung aus der Flugstatistik" src="' . nl_h($src) . '" loading="lazy"></iframe>';
}

function nl_stat_pdf_table($stat) {
	$series = isset($stat['series']) && is_array($stat['series']) ? $stat['series'] : array();
	$seasonYear = (int)($stat['seasonYear'] ?? date('Y'));
	$years = range($seasonYear - 4, $seasonYear);
	$rows = '';
	foreach ($years as $y) {
		$row = isset($series[$y]) && is_array($series[$y]) ? $series[$y] : array();
		$rows .= '<tr><td>' . (int)$y . '</td>'
			. '<td align="right">' . (int)($row['einsaetze'] ?? 0) . '</td>'
			. '<td align="right">' . (int)($row['fund'] ?? 0) . '</td>'
			. '<td align="right">' . (int)($row['flaeche'] ?? 0) . '</td>'
			. '<td align="right">' . (int)($row['felder'] ?? 0) . '</td></tr>';
	}
	return '<table class="nl-pdf-stat" width="100%" cellspacing="0" cellpadding="3" style="border-collapse:collapse;margin:6px 0 10px;">'
		. '<tr><th align="left">Jahr</th><th align="right">Einsatztage</th><th align="right">Rehkitze</th><th align="right">Fläche ha</th><th align="right">Felder</th></tr>'
		. $rows . '</table>';
}

function nl_chart_font() {
	static $font = null;
	if ($font !== null) {
		return $font;
	}
	$font = '';
	$local = __DIR__ . '/fonts/calibri.ttf';
	if (!is_file($local)) {
		$local = __DIR__ . '/fonts/DejaVuSans.ttf';
	}
	if (is_file($local)) {
		$font = $local;
		return $font;
	}
	foreach (array(
		'/usr/share/fonts/truetype/msttcorefonts/Calibri.ttf',
		'/usr/share/fonts/truetype/calibri/calibri.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
		'/usr/share/fonts/TTF/DejaVuSans.ttf',
		'/usr/share/fonts/dejavu/DejaVuSans.ttf',
	) as $path) {
		if (is_file($path)) {
			$font = $path;
			break;
		}
	}
	return $font;
}

function nl_chart_label($text) {
	$text = (string)$text;
	if (nl_chart_font() !== '') {
		return $text;
	}
	return strtr($text, array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss'));
}

function nl_public_abs($query) {
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
		|| ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
	$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
	if ($host === '') {
		$host = 'tchr-frog.synology.me';
		$https = true;
	}
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
	return ($https ? 'https' : 'http') . '://' . $host . $dir . '/rkrk_newsletter_public.php?MenuOn=off&' . ltrim((string)$query, '&');
}

function nl_mail_image_href($rel, $crop = null) {
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	if ($rel === '' || nl_public_media_file($rel) === '') {
		return '';
	}
	$query = 'bild=' . rawurlencode($rel);
	$box = nl_crop_box(is_array($crop) ? $crop : null);
	if ($box) {
		$query .= '&crop=' . rawurlencode($box['x'] . ',' . $box['y'] . ',' . $box['w'] . ',' . $box['h']);
	}
	return nl_public_abs($query);
}

function nl_mail_link_img($imgTag, $href) {
	if ($href === '' || empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf'])) {
		return $imgTag;
	}
	return '<a href="' . nl_h($href) . '" target="_blank" style="text-decoration:none;border:0;">' . $imgTag . '</a>';
}

function nl_stat_chart_png($stat, array $seriesKeys, $scale = 2) {
	$bin = nl_stat_chart_bin($stat, $seriesKeys, $scale);
	if ($bin === '') {
		return '';
	}
	$alt = implode(' / ', array_values($seriesKeys));
	$showW = 280;
	$showH = 177;
	if (!empty($GLOBALS['nl_for_mail']) && empty($GLOBALS['nl_for_pdf'])) {
		$showW = 312;
		$showH = 197;
	}
	return '<img src="data:image/png;base64,' . base64_encode($bin) . '" alt="' . nl_h($alt) . '" width="' . $showW . '" height="' . $showH . '" style="width:' . $showW . 'px;height:' . $showH . 'px;display:block;border:0;">';
}

function nl_stat_chart_bin($stat, array $seriesKeys, $scale = 1) {
	if (!function_exists('imagecreatetruecolor') || count($seriesKeys) < 2) {
		return '';
	}
	$scale = max(1, min(4, (int)$scale));
	$palette = array('einsaetze' => array(230, 126, 34), 'felder' => array(192, 57, 43), 'fund' => array(46, 125, 50), 'flaeche' => array(21, 101, 192));
	$seasonYear = (int)($stat['seasonYear'] ?? date('Y'));
	$years = range($seasonYear - 4, $seasonYear);
	$series = isset($stat['series']) && is_array($stat['series']) ? $stat['series'] : array();
	$keys = array_keys($seriesKeys);
	$max = array();
	foreach ($keys as $key) {
		$max[$key] = 1;
		foreach ($years as $y) {
			$v = (int)($series[$y][$key] ?? 0);
			if ($v > $max[$key]) {
				$max[$key] = $v;
			}
		}
	}
	$axisTop = array();
	foreach ($keys as $key) {
		$axisTop[$key] = nl_axis_top($max[$key]);
	}
	$w = 460 * $scale;
	$h = 290 * $scale;
	$left = 48 * $scale;
	$right = 52 * $scale;
	$top = 62 * $scale;
	$bottom = 36 * $scale;
	$plotW = $w - $left - $right;
	$plotH = $h - $top - $bottom;
	$base = $top + $plotH;
	$n = count($years);
	$im = imagecreatetruecolor($w, $h);
	if (!$im) {
		return '';
	}
	if (function_exists('imageantialias')) {
		imageantialias($im, true);
	}
	$white = imagecolorallocate($im, 255, 255, 255);
	$grid = imagecolorallocate($im, 227, 234, 224);
	$axis = imagecolorallocate($im, 187, 187, 187);
	$ink = imagecolorallocate($im, 36, 51, 51);
	imagefilledrectangle($im, 0, 0, $w, $h, $white);
	$col = array();
	foreach ($keys as $key) {
		$rgb = isset($palette[$key]) ? $palette[$key] : array(40, 40, 40);
		$col[$key] = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
	}
	$fontFile = nl_chart_font();
	$draw = function ($size, $x, $y, $text, $color, $center) use ($im, $fontFile, $scale) {
		$text = nl_chart_label($text);
		if ($text === '') {
			return;
		}
		$size = (int)max(1, $size * $scale);
		if ($fontFile !== '' && function_exists('imagettftext')) {
			$box = imagettfbbox($size, 0, $fontFile, $text);
			$tw = is_array($box) ? ($box[2] - $box[0]) : 0;
			imagettftext($im, $size, 0, (int)($center ? ($x - $tw / 2) : $x), (int)$y, $color, $fontFile, $text);
			return;
		}
		$font = 5;
		$fontScale = 2 * $scale;
		$tw = imagefontwidth($font) * strlen($text);
		$th = imagefontheight($font);
		if ($tw < 1 || $th < 1) {
			return;
		}
		$tmp = imagecreatetruecolor($tw, $th);
		$bg = imagecolorallocate($tmp, 255, 255, 255);
		$rgb = imagecolorsforindex($im, $color);
		$fg = imagecolorallocate($tmp, (int)$rgb['red'], (int)$rgb['green'], (int)$rgb['blue']);
		imagefilledrectangle($tmp, 0, 0, $tw, $th, $bg);
		imagestring($tmp, $font, 0, 0, $text, $fg);
		$dw = $tw * $fontScale;
		$dh = $th * $fontScale;
		$dx = (int)($center ? ($x - $dw / 2) : $x);
		imagecopyresampled($im, $tmp, $dx, (int)($y - $dh), 0, 0, $dw, $dh, $tw, $th);
		imagedestroy($tmp);
	};
	$draw(26, $w / 2, 24 * $scale, implode(' / ', array_values($seriesKeys)), $ink, true);
	$lx = 16 * $scale;
	foreach ($keys as $key) {
		imagefilledrectangle($im, $lx, 34 * $scale, $lx + 22 * $scale, 40 * $scale, $col[$key]);
		$draw(20, $lx + 28 * $scale, 42 * $scale, $seriesKeys[$key], $col[$key], false);
		$lx += 200 * $scale;
	}
	for ($g = 0; $g <= 4; $g++) {
		$yy = (int)round($top + ($plotH * $g / 4));
		imageline($im, $left, $yy, $w - $right, $yy, $grid);
		$tickY = (int)round($base - ($plotH * $g / 4));
		$draw(18, 4 * $scale, $tickY + 6 * $scale, (string)(int)round($axisTop[$keys[0]] * $g / 4), $col[$keys[0]], false);
		if (isset($keys[1]) && isset($axisTop[$keys[1]]) && isset($col[$keys[1]])) {
			$draw(18, $w - $right + 6 * $scale, $tickY + 6 * $scale, (string)(int)round($axisTop[$keys[1]] * $g / 4), $col[$keys[1]], false);
		}
	}
	imageline($im, $left, $top, $left, $base, $axis);
	imageline($im, $w - $right, $top, $w - $right, $base, $axis);
	imageline($im, $left, $base, $w - $right, $base, $axis);
	imagesetthickness($im, max(2, 2 * $scale));
	$drawn = array();
	foreach ($keys as $key) {
		$pts = array();
		foreach ($years as $i => $y) {
			$v = (int)($series[$y][$key] ?? 0);
			$x = $left + ($n > 1 ? ($i * $plotW / ($n - 1)) : ($plotW / 2));
			$den = isset($axisTop[$key]) ? (float)$axisTop[$key] : 1;
			if ($den < 1) {
				$den = 1;
			}
			$yPos = $base - ($plotH * ($v / $den));
			$pts[] = array($x, $yPos, $v);
		}
		$drawn[$key] = $pts;
		for ($i = 1, $pc = count($pts); $i < $pc; $i++) {
			imageline($im, (int)$pts[$i - 1][0], (int)$pts[$i - 1][1], (int)$pts[$i][0], (int)$pts[$i][1], $col[$key]);
		}
	}
	$labels = array();
	for ($i = 0; $i < $n; $i++) {
		$pair = array();
		foreach ($keys as $key) {
			$pair[] = array('key' => $key, 'p' => $drawn[$key][$i]);
		}
		usort($pair, function ($a, $b) {
			if ($a['p'][1] == $b['p'][1]) {
				return 0;
			}
			return ($a['p'][1] < $b['p'][1]) ? -1 : 1;
		});
		$hi = $pair[0];
		$labels[] = array(
			'key' => $hi['key'],
			'i' => $i,
			'tx' => $hi['p'][0],
			'ty' => $hi['p'][1] - 22 * $scale,
			'bw' => (strlen((string)(int)$hi['p'][2]) * 11 + 14) * $scale,
			'prefer' => -1,
			'py' => $hi['p'][1],
		);
		if (!isset($pair[1])) {
			continue;
		}
		$lo = $pair[1];
		$hy = $labels[count($labels) - 1]['ty'];
		$ly = $lo['p'][1] + 28 * $scale;
		if (($ly - $hy) < 26 * $scale) {
			$labels[count($labels) - 1]['ty'] -= 10 * $scale;
			$ly += 10 * $scale;
		}
		$hx = $hi['p'][0];
		$lx = $lo['p'][0];
		if (abs($hi['p'][1] - $lo['p'][1]) < 18 * $scale) {
			$hx -= 16 * $scale;
			$lx += 16 * $scale;
		}
		$labels[count($labels) - 1]['tx'] = $hx;
		$labels[] = array(
			'key' => $lo['key'],
			'i' => $i,
			'tx' => $lx,
			'ty' => $ly,
			'bw' => (strlen((string)(int)$lo['p'][2]) * 11 + 14) * $scale,
			'prefer' => 1,
			'py' => $lo['p'][1],
		);
	}
	$labels = nl_chart_nudge_labels($labels, $left + 2 * $scale, $w - $right - 2 * $scale, $top - 16 * $scale, $base - 2 * $scale, 14 * $scale, 18 * $scale, 4 * $scale, 4 * $scale);
	$placed = array();
	foreach ($labels as $lab) {
		$placed[$lab['key']][$lab['i']] = $lab;
	}
	imagesetthickness($im, 1);
	foreach ($keys as $key) {
		foreach ($drawn[$key] as $i => $p) {
			imagefilledellipse($im, (int)$p[0], (int)$p[1], 8 * $scale, 8 * $scale, $white);
			imageellipse($im, (int)$p[0], (int)$p[1], 8 * $scale, 8 * $scale, $col[$key]);
			$label = (string)(int)$p[2];
			$tx = isset($placed[$key][$i]) ? $placed[$key][$i]['tx'] : $p[0];
			$ty = isset($placed[$key][$i]) ? $placed[$key][$i]['ty'] : ($p[1] - 14 * $scale);
			$bw = isset($placed[$key][$i]) ? $placed[$key][$i]['bw'] : ((strlen($label) * 9 + 8) * $scale);
			imagefilledrectangle($im, (int)($tx - $bw / 2), (int)($ty - 14 * $scale), (int)($tx + $bw / 2), (int)($ty + 4 * $scale), $white);
			$draw(22, $tx, $ty, $label, $col[$key], true);
		}
	}
	imagesetthickness($im, 1);
	foreach ($years as $i => $y) {
		$x = $left + ($n > 1 ? ($i * $plotW / ($n - 1)) : ($plotW / 2));
		$draw(18, $x, $base + 24 * $scale, (string)(int)$y, $ink, true);
	}
	ob_start();
	imagepng($im);
	$bin = ob_get_clean();
	imagedestroy($im);
	return (is_string($bin) && $bin !== '') ? $bin : '';
}

function nl_stream_public_chart($id, $which) {
	$id = trim((string)$id);
	if (!preg_match('/^[0-9]{4}-[0-9]{2}$/', $id)) {
		http_response_code(404);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'Grafik nicht gefunden';
		exit;
	}
	$which = ((string)$which === '2') ? 2 : 1;
	$stat = nl_team_stat(array('id' => $id));
	$keys = ($which === 2)
		? array('flaeche' => 'Fläche ha', 'felder' => 'Felder')
		: array('einsaetze' => 'Einsatztage', 'fund' => 'Rehkitze');
	$bin = nl_stat_chart_bin($stat, $keys, 3);
	if ($bin === '') {
		http_response_code(404);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'Grafik nicht gefunden';
		exit;
	}
	header('Content-Type: image/png');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: public, max-age=3600');
	header('Content-Length: ' . (string)strlen($bin));
	echo $bin;
	exit;
}

function nl_stat_mail_svg($stat, array $seriesKeys) {
	$colors = array('einsaetze' => '#e67e22', 'felder' => '#c0392b', 'fund' => '#2e7d32', 'flaeche' => '#1565c0');
	$font = 'Calibri, Liberation Sans, DejaVu Sans, sans-serif';
	$seasonYear = (int)($stat['seasonYear'] ?? date('Y'));
	$years = range($seasonYear - 4, $seasonYear);
	$series = isset($stat['series']) && is_array($stat['series']) ? $stat['series'] : array();
	$keys = array_keys($seriesKeys);
	if (count($keys) < 1) {
		return '';
	}
	$max = array();
	foreach ($keys as $key) {
		$max[$key] = 1;
		foreach ($years as $y) {
			$v = (int)($series[$y][$key] ?? 0);
			if ($v > $max[$key]) {
				$max[$key] = $v;
			}
		}
	}
	$axisTop = array();
	foreach ($keys as $key) {
		$axisTop[$key] = nl_axis_top($max[$key]);
	}
	$w = 600;
	$h = 408;
	$left = 74;
	$right = 80;
	$top = 108;
	$bottom = 46;
	$plotW = $w - $left - $right;
	$plotH = $h - $top - $bottom;
	$base = $top + $plotH;
	$n = count($years);
	$span = max(1, $n - 1);
	$rise = 15;
	$boxH = 20;
	$title = implode(' / ', array_values($seriesKeys));
	$svg = '<?xml version="1.0" encoding="UTF-8"?>';
	$svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . ($w * 2) . '" height="' . ($h * 2) . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
	$svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';
	$svg .= '<text x="' . ($w / 2) . '" y="28" text-anchor="middle" font-size="20" font-weight="700" font-family="' . $font . '" fill="#243">' . nl_h($title) . '</text>';
	$lx = 16;
	foreach ($keys as $key) {
		$stroke = isset($colors[$key]) ? $colors[$key] : '#333333';
		$name = (string)$seriesKeys[$key];
		$svg .= '<line x1="' . $lx . '" y1="54" x2="' . ($lx + 22) . '" y2="54" stroke="' . $stroke . '" stroke-width="4"></line>';
		$svg .= '<text x="' . ($lx + 28) . '" y="59" font-size="16" font-weight="700" font-family="' . $font . '" fill="' . $stroke . '">' . nl_h($name) . '</text>';
		$lx += max(170, strlen($name) * 10 + 56);
	}
	for ($g = 0; $g <= 4; $g++) {
		$yy = $top + ($plotH * $g / 4);
		$svg .= '<line x1="' . $left . '" y1="' . round($yy, 1) . '" x2="' . ($w - $right) . '" y2="' . round($yy, 1) . '" stroke="#e3eae0"></line>';
		$tickY = $base - ($plotH * $g / 4);
		$leftTick = (int)round($axisTop[$keys[0]] * $g / 4);
		$svg .= '<text x="' . ($left - 14) . '" y="' . round($tickY + 5, 1) . '" text-anchor="end" font-size="14" font-weight="700" font-family="' . $font . '" fill="' . (isset($colors[$keys[0]]) ? $colors[$keys[0]] : '#333333') . '">' . $leftTick . '</text>';
		if (isset($keys[1]) && isset($axisTop[$keys[1]])) {
			$rightTick = (int)round($axisTop[$keys[1]] * $g / 4);
			$svg .= '<text x="' . ($w - $right + 16) . '" y="' . round($tickY + 5, 1) . '" text-anchor="start" font-size="14" font-weight="700" font-family="' . $font . '" fill="' . (isset($colors[$keys[1]]) ? $colors[$keys[1]] : '#333333') . '">' . $rightTick . '</text>';
		}
	}
	$svg .= '<line x1="' . $left . '" y1="' . $top . '" x2="' . $left . '" y2="' . $base . '" stroke="#bbbbbb"></line>';
	$svg .= '<line x1="' . ($w - $right) . '" y1="' . $top . '" x2="' . ($w - $right) . '" y2="' . $base . '" stroke="#bbbbbb"></line>';
	$svg .= '<line x1="' . $left . '" y1="' . $base . '" x2="' . ($w - $right) . '" y2="' . $base . '" stroke="#bbbbbb"></line>';
	$drawn = array();
	foreach ($keys as $key) {
		$pts = array();
		foreach ($years as $i => $y) {
			$v = (int)($series[$y][$key] ?? 0);
			$x = ($left + 40) + ($i * max(1, ($w - $left - $right - 80)) / $span);
			$den = isset($axisTop[$key]) ? (float)$axisTop[$key] : 1;
			if ($den < 1) {
				$den = 1;
			}
			$yPos = $base - ($plotH * ($v / $den));
			$pts[] = array($x, $yPos, $v);
		}
		$d = array();
		foreach ($pts as $p) {
			$d[] = round($p[0], 1) . ',' . round($p[1], 1);
		}
		$stroke = isset($colors[$key]) ? $colors[$key] : '#333333';
		$svg .= '<polyline fill="none" stroke="' . $stroke . '" stroke-width="3" points="' . implode(' ', $d) . '"></polyline>';
		$drawn[$key] = $pts;
	}
	$labels = array();
	for ($i = 0; $i < $n; $i++) {
		$pair = array();
		foreach ($keys as $key) {
			$pair[] = array('key' => $key, 'p' => $drawn[$key][$i]);
		}
		usort($pair, function ($a, $b) {
			if ($a['p'][1] == $b['p'][1]) {
				return strcmp((string)$a['key'], (string)$b['key']);
			}
			return ($a['p'][1] < $b['p'][1]) ? -1 : 1;
		});
		foreach ($pair as $pi => $item) {
			$above = ($pi === 0);
			$label = (string)(int)$item['p'][2];
			$ty = $above ? ($item['p'][1] - 24) : ($item['p'][1] + 30);
			$prefer = $above ? -1 : 1;
			if (!$above && ($ty + 5) > ($base - 2)) {
				$ty = $item['p'][1] - 24;
				$prefer = -1;
			}
			$labels[] = array(
				'key' => $item['key'],
				'i' => $i,
				'tx' => $item['p'][0],
				'ty' => $ty,
				'bw' => max(32, strlen($label) * 9 + 14),
				'prefer' => $prefer,
				'py' => $item['p'][1],
			);
		}
	}
	$labels = nl_chart_nudge_labels($labels, $left + 6, $w - $right - 6, 74, $base - 6, $rise, $boxH, 4, 3);
	$placed = array();
	foreach ($labels as $lab) {
		$placed[$lab['key']][$lab['i']] = $lab;
	}
	foreach ($keys as $key) {
		$stroke = isset($colors[$key]) ? $colors[$key] : '#333333';
		foreach ($drawn[$key] as $i => $p) {
			$svg .= '<circle cx="' . round($p[0], 1) . '" cy="' . round($p[1], 1) . '" r="5" fill="#ffffff" stroke="' . $stroke . '" stroke-width="2.5"></circle>';
			$label = (string)(int)$p[2];
			$tx = isset($placed[$key][$i]) ? $placed[$key][$i]['tx'] : $p[0];
			$ty = isset($placed[$key][$i]) ? $placed[$key][$i]['ty'] : ($p[1] - 18);
			$bw = isset($placed[$key][$i]) ? $placed[$key][$i]['bw'] : max(36, strlen($label) * 11 + 16);
			$svg .= '<rect x="' . round($tx - $bw / 2, 1) . '" y="' . round($ty - $rise, 1) . '" width="' . round($bw, 1) . '" height="' . $boxH . '" rx="2" fill="#ffffff"></rect>';
			$svg .= '<text x="' . round($tx, 1) . '" y="' . round($ty, 1) . '" text-anchor="middle" font-size="16" font-weight="700" font-family="' . $font . '" fill="' . $stroke . '">' . $label . '</text>';
		}
	}
	foreach ($years as $i => $y) {
		$x = ($left + 40) + ($i * max(1, ($w - $left - $right - 80)) / $span);
		$svg .= '<text x="' . round($x, 1) . '" y="' . ($base + 26) . '" text-anchor="middle" font-size="14" font-weight="700" font-family="' . $font . '" fill="#333333">' . (int)$y . '</text>';
	}
	$svg .= '</svg>';
	return $svg;
}

function nl_svg_png_bin($svg, $dpi) {
	if (!class_exists('Imagick') || $svg === '') {
		return '';
	}
	$dpi = (int)$dpi;
	if ($dpi < 72) {
		$dpi = 72;
	}
	try {
		$im = new Imagick();
		$im->setBackgroundColor(new ImagickPixel('white'));
		$im->setResolution($dpi, $dpi);
		$im->readImageBlob($svg);
		$im->setImageFormat('png');
		$bin = $im->getImageBlob();
		$im->clear();
		$im->destroy();
	} catch (Exception $e) {
		return '';
	}
	return (is_string($bin) && $bin !== '') ? $bin : '';
}

function nl_svg_png_img($svg, $alt, $showW, $showH) {
	$bin = nl_svg_png_bin($svg, 192);
	if ($bin === '') {
		return '';
	}
	return '<img src="data:image/png;base64,' . base64_encode($bin) . '" alt="' . nl_h($alt) . '" width="' . (int)$showW . '" height="' . (int)$showH . '" style="width:' . (int)$showW . 'px;height:' . (int)$showH . 'px;display:block;border:0;">';
}

function nl_stat_mail_png_bin($svg) {
	$bin = nl_svg_png_bin($svg, 96);
	$iw = 0;
	$info = ($bin !== '') ? @getimagesizefromstring($bin) : false;
	if (is_array($info)) {
		$iw = (int)$info[0];
	}
	if ($bin === '' || ($iw > 0 && $iw < 1000)) {
		$bin2 = nl_svg_png_bin($svg, 192);
		$info2 = ($bin2 !== '') ? @getimagesizefromstring($bin2) : false;
		if ($bin2 !== '' && is_array($info2) && (int)$info2[0] >= $iw) {
			$bin = $bin2;
			$iw = (int)$info2[0];
		}
	}
	if ($bin === '' || !class_exists('Imagick') || $iw < 1400) {
		return $bin;
	}
	try {
		$im = new Imagick();
		$im->readImageBlob($bin);
		$nw = 1200;
		$nh = (int)max(1, round($im->getImageHeight() * $nw / max(1, $im->getImageWidth())));
		$filter = defined('Imagick::FILTER_LANCZOS') ? Imagick::FILTER_LANCZOS : 1;
		$im->resizeImage($nw, $nh, $filter, 1);
		$out = $im->getImageBlob();
		$im->clear();
		$im->destroy();
		if (is_string($out) && $out !== '') {
			return $out;
		}
	} catch (Exception $e) {
	}
	return $bin;
}

function nl_chart_labels_collide(array $labels, $rise, $boxH, $gap) {
	$n = count($labels);
	$rise = (float)$rise;
	$boxH = (float)$boxH;
	$gap = (float)$gap;
	for ($i = 0; $i < $n; $i++) {
		for ($j = $i + 1; $j < $n; $j++) {
			$a = $labels[$i];
			$b = $labels[$j];
			$a0 = $a['tx'] - $a['bw'] / 2;
			$a1 = $a['ty'] - $rise;
			$a2 = $a['tx'] + $a['bw'] / 2;
			$a3 = $a1 + $boxH;
			$b0 = $b['tx'] - $b['bw'] / 2;
			$b1 = $b['ty'] - $rise;
			$b2 = $b['tx'] + $b['bw'] / 2;
			$b3 = $b1 + $boxH;
			if (!($a2 + $gap <= $b0 || $b2 + $gap <= $a0 || $a3 + $gap <= $b1 || $b3 + $gap <= $a1)) {
				return true;
			}
		}
	}
	return false;
}

function nl_chart_labels_hit_points(array $labels, array $points, $rise, $boxH, $pad) {
	$rise = (float)$rise;
	$boxH = (float)$boxH;
	$pad = (float)$pad;
	foreach ($labels as $lab) {
		$x0 = $lab['tx'] - $lab['bw'] / 2 - $pad;
		$y0 = $lab['ty'] - $rise - $pad;
		$x1 = $lab['tx'] + $lab['bw'] / 2 + $pad;
		$y1 = $y0 + $boxH + (2 * $pad);
		foreach ($points as $p) {
			if ($p[0] >= $x0 && $p[0] <= $x1 && $p[1] >= $y0 && $p[1] <= $y1) {
				return true;
			}
		}
	}
	return false;
}

function nl_stat_mail_gd_bin($stat, array $seriesKeys, $layout = 'full', &$fit = null) {
	$fit = false;
	if (!function_exists('imagecreatetruecolor')) {
		return '';
	}
	$palette = array('einsaetze' => array(230, 126, 34), 'felder' => array(192, 57, 43), 'fund' => array(46, 125, 50), 'flaeche' => array(21, 101, 192));
	$seasonYear = (int)($stat['seasonYear'] ?? date('Y'));
	$years = range($seasonYear - 4, $seasonYear);
	$series = isset($stat['series']) && is_array($stat['series']) ? $stat['series'] : array();
	$keys = array_keys($seriesKeys);
	if (count($keys) < 1) {
		return '';
	}
	$max = array();
	foreach ($keys as $key) {
		$max[$key] = 1;
		foreach ($years as $y) {
			$v = (int)($series[$y][$key] ?? 0);
			if ($v > $max[$key]) {
				$max[$key] = $v;
			}
		}
	}
	$axisTop = array();
	foreach ($keys as $key) {
		$axisTop[$key] = nl_axis_top($max[$key]);
	}
	$scale = 2;
	$pair = ($layout === 'pair');
	$w = 600;
	$h = 408;
	$left = 74;
	$right = 80;
	$top = 108;
	$bottom = 46;
	$rise = 15;
	$boxH = 20;
	$pad = 40;
	$titleSize = 20;
	$titleY = 28;
	$legendSize = 16;
	$legendLx = 16;
	$legendBarTop = 50;
	$legendBarBot = 57;
	$legendBarW = 22;
	$legendTextX = 28;
	$legendTextY = 59;
	$tickSize = 14;
	$tickLeft = 14;
	$tickRight = 16;
	$tickDy = 5;
	$labelSize = 16;
	$yearSize = 14;
	$yearDy = 26;
	$aboveOff = 24;
	$belowOff = 30;
	$nudgeL = 6;
	$nudgeR = 6;
	$nudgeTop = 74;
	$nudgeBot = 6;
	$nudgeGap = 4;
	$nudgeStep = 3;
	$dot = 13;
	$hole = 7;
	if ($pair) {
		$w = 320;
		$h = 300;
		$left = 48;
		$right = 52;
		$top = 96;
		$bottom = 34;
		$rise = 12;
		$boxH = 16;
		$pad = 8;
		$titleSize = 14;
		$titleY = 16;
		$legendSize = 12;
		$legendLx = 8;
		$legendBarTop = 30;
		$legendBarBot = 36;
		$legendBarW = 16;
		$legendTextX = 20;
		$legendTextY = 36;
		$tickSize = 11;
		$tickLeft = 6;
		$tickRight = 6;
		$tickDy = 4;
		$labelSize = 13;
		$yearSize = 11;
		$yearDy = 20;
		$aboveOff = 18;
		$belowOff = 22;
		$nudgeL = 2;
		$nudgeR = 2;
		$nudgeTop = 48;
		$nudgeBot = 4;
		$nudgeGap = 3;
		$nudgeStep = 2;
		$dot = 9;
		$hole = 4;
	}
	$plotW = $w - $left - $right;
	$plotH = $h - $top - $bottom;
	$base = $top + $plotH;
	$n = count($years);
	$span = max(1, $n - 1);
	$px = function ($v) use ($scale) {
		return (int)round($v * $scale);
	};
	$im = imagecreatetruecolor($px($w), $px($h));
	if (!$im) {
		return '';
	}
	if (function_exists('imageantialias')) {
		imageantialias($im, true);
	}
	$white = imagecolorallocate($im, 255, 255, 255);
	$grid = imagecolorallocate($im, 227, 234, 224);
	$axis = imagecolorallocate($im, 187, 187, 187);
	$ink = imagecolorallocate($im, 36, 51, 51);
	$yearInk = imagecolorallocate($im, 51, 51, 51);
	imagefilledrectangle($im, 0, 0, $px($w), $px($h), $white);
	$col = array();
	foreach ($keys as $key) {
		$rgb = isset($palette[$key]) ? $palette[$key] : array(51, 51, 51);
		$col[$key] = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
	}
	$fontFile = nl_chart_font();
	$textSize = function ($size, $text) use ($fontFile, $scale) {
		$text = nl_chart_label($text);
		$sizePx = (int)max(8, round($size * $scale));
		if ($fontFile !== '' && function_exists('imagettfbbox')) {
			$box = imagettfbbox($sizePx, 0, $fontFile, $text);
			if (is_array($box)) {
				return (int)abs($box[2] - $box[0]);
			}
		}
		return imagefontwidth(5) * strlen($text) * $scale;
	};
	$draw = function ($size, $x, $y, $text, $color, $anchor) use ($im, $fontFile, $scale, $textSize) {
		$text = nl_chart_label($text);
		if ($text === '') {
			return;
		}
		$sizePx = (int)max(8, round($size * $scale));
		$xPx = $x * $scale;
		$yPx = $y * $scale;
		$tw = $textSize($size, $text);
		if ($anchor === 'middle') {
			$xPx -= $tw / 2;
		} elseif ($anchor === 'end') {
			$xPx -= $tw;
		}
		if ($fontFile !== '' && function_exists('imagettftext')) {
			imagettftext($im, $sizePx, 0, (int)round($xPx), (int)round($yPx), $color, $fontFile, $text);
			return;
		}
		$font = 5;
		$bw = imagefontwidth($font) * strlen($text);
		$bh = imagefontheight($font);
		if ($bw < 1 || $bh < 1) {
			return;
		}
		$tmp = imagecreatetruecolor($bw, $bh);
		$bg = imagecolorallocate($tmp, 255, 255, 255);
		$rgb = imagecolorsforindex($im, $color);
		$fg = imagecolorallocate($tmp, (int)$rgb['red'], (int)$rgb['green'], (int)$rgb['blue']);
		imagefilledrectangle($tmp, 0, 0, $bw, $bh, $bg);
		imagestring($tmp, $font, 0, 0, $text, $fg);
		$dw = (int)max(1, round($bw * $scale));
		$dh = (int)max(1, round($bh * $scale));
		imagecopyresampled($im, $tmp, (int)round($xPx), (int)round($yPx - $dh), 0, 0, $dw, $dh, $bw, $bh);
		imagedestroy($tmp);
	};
	$draw($titleSize, $w / 2, $titleY, implode(' / ', array_values($seriesKeys)), $ink, 'middle');
	$lx = $legendLx;
	foreach ($keys as $key) {
		$name = (string)$seriesKeys[$key];
		imagefilledrectangle($im, $px($lx), $px($legendBarTop), $px($lx + $legendBarW), $px($legendBarBot), $col[$key]);
		$draw($legendSize, $lx + $legendTextX, $legendTextY, $name, $col[$key], 'start');
		if ($layout === 'pair') {
			$lx += (int)ceil($legendTextX + ($textSize($legendSize, $name) / $scale) + 12);
		} else {
			$lx += max(170, strlen($name) * 10 + 56);
		}
	}
	for ($g = 0; $g <= 4; $g++) {
		$yy = $top + ($plotH * $g / 4);
		imageline($im, $px($left), $px($yy), $px($w - $right), $px($yy), $grid);
		$tickY = $base - ($plotH * $g / 4);
		$draw($tickSize, $left - $tickLeft, $tickY + $tickDy, (string)(int)round($axisTop[$keys[0]] * $g / 4), $col[$keys[0]], 'end');
		if (isset($keys[1]) && isset($axisTop[$keys[1]]) && isset($col[$keys[1]])) {
			$draw($tickSize, $w - $right + $tickRight, $tickY + $tickDy, (string)(int)round($axisTop[$keys[1]] * $g / 4), $col[$keys[1]], 'start');
		}
	}
	imagesetthickness($im, 2);
	imageline($im, $px($left), $px($top), $px($left), $px($base), $axis);
	imageline($im, $px($w - $right), $px($top), $px($w - $right), $px($base), $axis);
	imageline($im, $px($left), $px($base), $px($w - $right), $px($base), $axis);
	$drawn = array();
	imagesetthickness($im, 4);
	foreach ($keys as $key) {
		$pts = array();
		foreach ($years as $i => $y) {
			$v = (int)($series[$y][$key] ?? 0);
			$x = ($left + $pad) + ($i * max(1, ($w - $left - $right - (2 * $pad))) / $span);
			$den = isset($axisTop[$key]) ? (float)$axisTop[$key] : 1;
			if ($den < 1) {
				$den = 1;
			}
			$yPos = $base - ($plotH * ($v / $den));
			$pts[] = array($x, $yPos, $v);
		}
		$drawn[$key] = $pts;
		for ($i = 1, $pc = count($pts); $i < $pc; $i++) {
			imageline($im, $px($pts[$i - 1][0]), $px($pts[$i - 1][1]), $px($pts[$i][0]), $px($pts[$i][1]), $col[$key]);
		}
	}
	$labels = array();
	for ($i = 0; $i < $n; $i++) {
		$yearPair = array();
		foreach ($keys as $key) {
			$yearPair[] = array('key' => $key, 'p' => $drawn[$key][$i]);
		}
		usort($yearPair, function ($a, $b) {
			if ($a['p'][1] == $b['p'][1]) {
				return strcmp((string)$a['key'], (string)$b['key']);
			}
			return ($a['p'][1] < $b['p'][1]) ? -1 : 1;
		});
		foreach ($yearPair as $pi => $item) {
			$above = ($pi === 0);
			$label = (string)(int)$item['p'][2];
			$ty = $above ? ($item['p'][1] - $aboveOff) : ($item['p'][1] + $belowOff);
			$prefer = $above ? -1 : 1;
			if (!$above && ($ty + 5) > ($base - 2)) {
				$ty = $item['p'][1] - $aboveOff;
				$prefer = -1;
			}
			if ($layout === 'pair') {
				$bw = max(22, ($textSize($labelSize, $label) / $scale) + 8);
			} else {
				$bw = max(32, strlen($label) * 9 + 14);
			}
			$labels[] = array(
				'key' => $item['key'],
				'i' => $i,
				'tx' => $item['p'][0],
				'ty' => $ty,
				'bw' => $bw,
				'prefer' => $prefer,
				'py' => $item['p'][1],
			);
		}
	}
	$labels = nl_chart_nudge_labels($labels, $left + $nudgeL, $w - $right - $nudgeR, $nudgeTop, $base - $nudgeBot, $rise, $boxH, $nudgeGap, $nudgeStep);
	$chartPts = array();
	foreach ($drawn as $pts) {
		foreach ($pts as $p) {
			$chartPts[] = $p;
		}
	}
	if ($layout === 'pair' && (nl_chart_labels_collide($labels, $rise, $boxH, $nudgeGap) || nl_chart_labels_hit_points($labels, $chartPts, $rise, $boxH, 4))) {
		imagedestroy($im);
		return '';
	}
	$fit = true;
	$placed = array();
	foreach ($labels as $lab) {
		$placed[$lab['key']][$lab['i']] = $lab;
	}
	foreach ($keys as $key) {
		foreach ($drawn[$key] as $i => $p) {
			$cx = $px($p[0]);
			$cy = $px($p[1]);
			imagefilledellipse($im, $cx, $cy, $px($dot), $px($dot), $col[$key]);
			imagefilledellipse($im, $cx, $cy, $px($hole), $px($hole), $white);
			$label = (string)(int)$p[2];
			$tx = isset($placed[$key][$i]) ? $placed[$key][$i]['tx'] : $p[0];
			$ty = isset($placed[$key][$i]) ? $placed[$key][$i]['ty'] : ($p[1] - 18);
			if ($layout === 'pair') {
				$fallbackBw = max(22, ($textSize($labelSize, $label) / $scale) + 8);
			} else {
				$fallbackBw = max(36, strlen($label) * 11 + 16);
			}
			$bw = isset($placed[$key][$i]) ? $placed[$key][$i]['bw'] : $fallbackBw;
			imagefilledrectangle($im, $px($tx - $bw / 2), $px($ty - $rise), $px($tx + $bw / 2), $px($ty - $rise + $boxH), $white);
			$draw($labelSize, $tx, $ty, $label, $col[$key], 'middle');
		}
	}
	foreach ($years as $i => $y) {
		$x = ($left + $pad) + ($i * max(1, ($w - $left - $right - (2 * $pad))) / $span);
		$draw($yearSize, $x, $base + $yearDy, (string)(int)$y, $yearInk, 'middle');
	}
	ob_start();
	imagepng($im);
	$bin = ob_get_clean();
	imagedestroy($im);
	return (is_string($bin) && $bin !== '') ? $bin : '';
}

function nl_stat_chart_shot($stat, array $seriesKeys) {
	$alt = implode(' / ', array_values($seriesKeys));
	$showW = 600;
	$showH = 390;
	$svg = nl_stat_mail_svg($stat, $seriesKeys);
	if (preg_match('/viewBox="0 0 ([0-9.]+) ([0-9.]+)"/', $svg, $box) && (float)$box[1] > 0) {
		$showH = (int)round($showW * (float)$box[2] / (float)$box[1]);
	}
	$bin = nl_stat_mail_png_bin($svg);
	if ($bin === '') {
		$bin = nl_stat_mail_gd_bin($stat, $seriesKeys);
	}
	if ($bin === '') {
		$bin = nl_stat_chart_bin($stat, $seriesKeys, 3);
		if ($bin === '') {
			return '';
		}
		$showH = (int)round($showW * 290 / 460);
	}
	return '<img src="data:image/png;base64,' . base64_encode($bin) . '" alt="' . nl_h($alt) . '" width="' . $showW . '" height="' . $showH . '" style="width:' . $showW . 'px;max-width:100%;height:auto;display:block;border:0;">';
}

function nl_stat_mail_pair_img($stat, array $seriesKeys) {
	$fit = false;
	$bin = nl_stat_mail_gd_bin($stat, $seriesKeys, 'pair', $fit);
	if ($bin === '' || !$fit) {
		return '';
	}
	$alt = implode(' / ', array_values($seriesKeys));
	$showW = 320;
	$info = @getimagesizefromstring($bin);
	$iw = (is_array($info) && !empty($info[0])) ? (int)$info[0] : 640;
	$ih = (is_array($info) && !empty($info[1])) ? (int)$info[1] : 512;
	if ($iw < 1) {
		$iw = 1;
	}
	$showH = (int)max(1, round($ih * $showW / $iw));
	return '<img src="data:image/png;base64,' . base64_encode($bin) . '" alt="' . nl_h($alt) . '" width="' . $showW . '" height="' . $showH . '" style="width:' . $showW . 'px;height:' . $showH . 'px;display:block;border:0;">';
}

function nl_stat_chart_svg($stat, $issueId = '') {
	if (!empty($GLOBALS['nl_for_mail']) && empty($GLOBALS['nl_for_pdf'])) {
		$leftKeys = array('einsaetze' => 'Einsatztage', 'fund' => 'Rehkitze');
		$rightKeys = array('flaeche' => 'Fläche ha', 'felder' => 'Felder');
		$leftPair = nl_stat_mail_pair_img($stat, $leftKeys);
		$rightPair = nl_stat_mail_pair_img($stat, $rightKeys);
		$id = trim((string)$issueId);
		if ($leftPair !== '' && $rightPair !== '') {
			if ($id !== '') {
				$leftPair = nl_mail_link_img($leftPair, nl_public_abs('grafik=' . rawurlencode($id) . '&n=1'));
				$rightPair = nl_mail_link_img($rightPair, nl_public_abs('grafik=' . rawurlencode($id) . '&n=2'));
			}
			return '<table role="presentation" width="650" cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:650px;max-width:100%;"><tr>'
				. '<td width="320" valign="top" style="width:320px;">' . $leftPair . '</td>'
				. '<td width="10" style="width:10px;font-size:0;line-height:0;">&nbsp;</td>'
				. '<td width="320" valign="top" style="width:320px;">' . $rightPair . '</td>'
				. '</tr></table>';
		}
		$left = nl_stat_chart_shot($stat, $leftKeys);
		$right = nl_stat_chart_shot($stat, $rightKeys);
		if ($id !== '') {
			if ($left !== '') {
				$left = nl_mail_link_img($left, nl_public_abs('grafik=' . rawurlencode($id) . '&n=1'));
			}
			if ($right !== '') {
				$right = nl_mail_link_img($right, nl_public_abs('grafik=' . rawurlencode($id) . '&n=2'));
			}
		}
		if ($left === '' && $right === '') {
			return nl_stat_pdf_table($stat);
		}
		if ($left === '' || $right === '') {
			$one = ($left !== '') ? $left : $right;
			return '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:600px;max-width:100%;"><tr><td style="padding:0;">' . $one . '</td></tr></table>';
		}
		return '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:600px;max-width:100%;">'
			. '<tr><td style="padding:0 0 16px 0;">' . $left . '</td></tr>'
			. '<tr><td style="padding:0;">' . $right . '</td></tr>'
			. '</table>';
	}
	if (!empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf'])) {
		$left = nl_stat_chart_png($stat, array('einsaetze' => 'Einsatztage', 'fund' => 'Rehkitze'), 3);
		$right = nl_stat_chart_png($stat, array('flaeche' => 'Fläche ha', 'felder' => 'Felder'), 3);
		if ($left === '' || $right === '') {
			return nl_stat_pdf_table($stat);
		}
		$id = trim((string)$issueId);
		if ($id !== '' && empty($GLOBALS['nl_for_pdf'])) {
			$left = nl_mail_link_img($left, nl_public_abs('grafik=' . rawurlencode($id) . '&n=1'));
			$right = nl_mail_link_img($right, nl_public_abs('grafik=' . rawurlencode($id) . '&n=2'));
		}
		return '<table class="nl-chart-row" role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr>'
			. '<td width="50%" valign="top" style="width:50%;padding:0 8px 0 0;">' . $left . '</td>'
			. '<td width="50%" valign="top" style="width:50%;padding:0 0 0 8px;">' . $right . '</td>'
			. '</tr></table>';
	}
	$left = nl_stat_line_svg($stat, array('einsaetze' => 'Einsatztage', 'fund' => 'Rehkitze'));
	$right = nl_stat_line_svg($stat, array('flaeche' => 'Fläche ha', 'felder' => 'Felder'));
	return '<div class="nl-chart-row">' . $left . $right . '</div>';
}

function nl_data_web_prefix($kind = 'newsletter') {
	$kind = ($kind === 'gemeinde') ? 'gemeinde' : 'newsletter';
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$dir = trim(dirname($script), '/');
	$parts = ($dir === '') ? array() : explode('/', $dir);
	foreach ($parts as $i => $part) {
		if (strtolower((string)$part) === 'rkropen') {
			return '/' . implode('/', array_slice($parts, 0, $i + 1)) . '/rkrk/data/' . $kind . '/';
		}
	}
	return '../../rkrk/data/' . $kind . '/';
}

function nl_issue_media_rels($issue) {
	$rels = array();
	$add = function ($rel) use (&$rels) {
		$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
		if ($rel !== '') {
			$rels[$rel] = true;
		}
	};
	if (!is_array($issue)) {
		return $rels;
	}
	$add($issue['headerImage'] ?? '');
	$add($issue['resultsImage'] ?? '');
	if (isset($issue['sections']) && is_array($issue['sections'])) {
		foreach ($issue['sections'] as $section) {
			if (!is_array($section)) {
				continue;
			}
			$add($section['image'] ?? '');
			if (isset($section['images']) && is_array($section['images'])) {
				foreach ($section['images'] as $image) {
					$add($image);
				}
			}
		}
	}
	if (isset($issue['footnoteImages']) && is_array($issue['footnoteImages'])) {
		foreach ($issue['footnoteImages'] as $image) {
			if (is_array($image)) {
				$add($image['src'] ?? '');
			}
		}
	}
	return $rels;
}

function nl_public_media_file($rel) {
	static $allowed = null;
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	if ($allowed === null) {
		$allowed = array();
		foreach (nl_items('newsletter_issues.json') as $issue) {
			if (!nl_issue_is_public($issue)) {
				continue;
			}
			foreach (nl_issue_media_rels($issue) as $one => $on) {
				if (!preg_match('#^issues/[0-9]{4}-[0-9]{2}/[A-Za-z0-9._-]+$#', $one)) {
					continue;
				}
				$abs = nl_data_dir('newsletter') . '/' . $one;
				if (is_file($abs)) {
					$allowed[$one] = $abs;
				}
			}
		}
	}
	return isset($allowed[$rel]) ? $allowed[$rel] : '';
}

function nl_public_media_href($rel) {
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	if (nl_public_media_file($rel) === '') {
		return '';
	}
	return 'rkrk_newsletter_public.php?MenuOn=off&bild=' . rawurlencode($rel);
}

function nl_crop_box($crop) {
	if (!is_array($crop)) {
		return null;
	}
	$x = max(0, min(100, (float)($crop['x'] ?? 0)));
	$y = max(0, min(100, (float)($crop['y'] ?? 0)));
	$w = max(0, min(100, (float)($crop['w'] ?? 0)));
	$h = max(0, min(100, (float)($crop['h'] ?? 0)));
	if ($w < 5 || $h < 5) {
		return null;
	}
	if ($x + $w > 100) {
		$w = 100 - $x;
	}
	if ($y + $h > 100) {
		$h = 100 - $y;
	}
	if ($x < 0.4 && $y < 0.4 && $w > 99.2 && $h > 99.2) {
		return null;
	}
	return array(
		'x' => round($x, 2),
		'y' => round($y, 2),
		'w' => round($w, 2),
		'h' => round($h, 2),
	);
}

function nl_crop_img_html($rel, $src, $alt, $crop, $frameW = 0, $frameH = 0) {
	$alt = trim(strip_tags((string)$alt));
	$box = nl_crop_box($crop);
	if (!empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf'])) {
		$attr = $box ? (' data-crop="' . $box['x'] . ',' . $box['y'] . ',' . $box['w'] . ',' . $box['h'] . '"') : '';
		return '<img src="' . nl_h($src) . '" alt="' . nl_h($alt) . '"' . $attr . ' style="width:100%;height:auto;display:block;">';
	}
	$img = '<img src="' . nl_h($src) . '" alt="' . nl_h($alt) . '">';
	$box = nl_crop_box($crop);
	if (!$box) {
		return $img;
	}
	$abs = nl_data_dir('newsletter') . '/' . ltrim(str_replace('\\', '/', (string)$rel), '/');
	if (!is_file($abs)) {
		$abs = nl_public_media_file($rel);
	}
	$info = ($abs !== '' && is_file($abs)) ? @getimagesize($abs) : false;
	if (!is_array($info) || empty($info[0]) || empty($info[1])) {
		return $img;
	}
	$fw = $info[0] * $box['w'] / 100;
	$fh = $info[1] * $box['h'] / 100;
	if ($fw < 1 || $fh < 1) {
		return $img;
	}
	$pad = round($fh / $fw * 100, 3);
	$imgW = round(100 / ($box['w'] / 100), 3);
	$imgH = round(100 / ($box['h'] / 100), 3);
	$left = round(-$box['x'] / $box['w'] * 100, 3);
	$top = round(-$box['y'] / $box['h'] * 100, 3);
	$style = 'position:absolute;width:' . $imgW . '%;height:' . $imgH . '%;left:' . $left . '%;top:' . $top . '%;max-width:none;max-height:none;object-fit:fill;';
	$ar = round($fw / $fh, 4);
	$view = 'padding-bottom:' . $pad . '%;--nl-ar:' . $ar . ';';
	if ((int)$frameW > 0 && (int)$frameH > 0) {
		$view = 'width:' . (int)$frameW . 'px;height:' . (int)$frameH . 'px;padding-bottom:0;max-width:100%;--nl-ar:' . $ar . ';';
	}
	return '<span class="nl-crop-view" style="' . $view . '"><img src="' . nl_h($src) . '" alt="' . nl_h($alt) . '" style="' . $style . '"></span>';
}

function nl_image_is_tall($rel, $crop = null) {
	$rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
	$abs = nl_data_dir('newsletter') . '/' . $rel;
	if (!is_file($abs)) {
		$abs = nl_public_media_file($rel);
	}
	if ($abs === '' || !is_file($abs)) {
		return false;
	}
	$info = @getimagesize($abs);
	if (!is_array($info) || empty($info[0]) || empty($info[1])) {
		return false;
	}
	$box = nl_crop_box($crop);
	if ($box) {
		return ($info[1] * $box['h']) > ($info[0] * $box['w']);
	}
	return ((int)$info[1] > (int)$info[0]);
}

function nl_img_src($rel) {
	if (!empty($GLOBALS['nl_public_media'])) {
		$href = nl_public_media_href($rel);
		if ($href !== '') {
			return $href;
		}
	}
	return nl_media_src($rel);
}

function nl_stream_public_image($rel) {
	$abs = nl_public_media_file($rel);
	if ($abs === '') {
		http_response_code(404);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'Bild nicht gefunden';
		exit;
	}
	$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
	$types = array(
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		'svg' => 'image/svg+xml',
	);
	$cropRaw = (string)($_GET['crop'] ?? '');
	if (preg_match('/^([0-9.]+),([0-9.]+),([0-9.]+),([0-9.]+)$/', $cropRaw, $cropMatch)) {
		$box = nl_crop_box(array('x' => $cropMatch[1], 'y' => $cropMatch[2], 'w' => $cropMatch[3], 'h' => $cropMatch[4]));
		$bin = ($box) ? @file_get_contents($abs) : false;
		if ($box && is_string($bin) && $bin !== '') {
			list($out, $mime) = nl_apply_crop_binary($bin, $box);
			if ($mime !== '' && $out !== '') {
				header('Content-Type: ' . $mime);
				header('X-Content-Type-Options: nosniff');
				header('Cache-Control: public, max-age=86400');
				header('Content-Length: ' . (string)strlen($out));
				echo $out;
				exit;
			}
		}
	}
	header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: public, max-age=86400');
	header('Content-Length: ' . (string)filesize($abs));
	readfile($abs);
	exit;
}

function nl_media_src($rel, $kind = '') {
	$rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
	if ($rel === '') {
		return '';
	}
	if (preg_match('#^https?://#i', $rel)) {
		return $rel;
	}
	$kinds = ($kind === 'gemeinde' || $kind === 'newsletter') ? array($kind) : array('newsletter', 'gemeinde');
	foreach ($kinds as $k) {
		$abs = nl_data_dir($k) . '/' . $rel;
		if (is_file($abs)) {
			return nl_data_web_prefix($k) . $rel;
		}
	}
	if (is_file(__DIR__ . '/' . $rel)) {
		return $rel;
	}
	$base = basename($rel);
	foreach (array('media/gemeinde/' . $base, 'media/qr/' . $base, 'issues/' . $base, 'media/attachments/' . $base) as $try) {
		foreach ($kinds as $k) {
			if (is_file(nl_data_dir($k) . '/' . $try)) {
				return nl_data_web_prefix($k) . $try;
			}
		}
		if (is_file(__DIR__ . '/' . $try)) {
			return $try;
		}
	}
	return '';
}

function nl_de_month($date) {
	$months = array(1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
	$ts = strtotime((string)$date);
	if (!$ts) {
		return '';
	}
	$m = (int)date('n', $ts);
	return $months[$m] . ' ' . date('Y', $ts);
}

function nl_campaign_letter_when($campaign) {
	$id = preg_replace('/\D+/', '', (string)(is_array($campaign) ? ($campaign['id'] ?? '') : ''));
	if (strlen($id) >= 6) {
		$year = (int)substr($id, 0, 4);
		$month = (int)substr($id, 4, 2);
		if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
			return nl_de_month(sprintf('%04d-%02d-01', $year, $month));
		}
	}
	$to = is_array($campaign) ? (string)($campaign['to'] ?? '') : '';
	return nl_de_month($to);
}

require_once __DIR__ . '/rkrk_newsletter_letter.php';
function nl_smtp_read($fp) {
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

function nl_smtp_expect($fp, $cmd, $okCodes) {
	if ($cmd !== null) {
		fwrite($fp, $cmd . "\r\n");
	}
	list($code, $data) = nl_smtp_read($fp);
	if (!in_array($code, $okCodes, true)) {
		throw new RuntimeException(trim($cmd . ' -> ' . $data));
	}
	return $data;
}

function nl_encode_header($value) {
	$value = trim(str_replace(array("\r", "\n"), '', (string)$value));
	if (preg_match('/[^\x20-\x7E]/', $value)) {
		return '=?UTF-8?B?' . base64_encode($value) . '?=';
	}
	return $value;
}

function nl_dot_stuff($body) {
	$body = str_replace(array("\r\n", "\r"), "\n", $body);
	$body = str_replace("\n", "\r\n", $body);
	$body = preg_replace('/^\./m', '..', $body);
	return $body;
}

function nl_file_for_web_src($src) {
	$src = html_entity_decode((string)$src, ENT_QUOTES, 'UTF-8');
	$src = preg_replace('/[?#].*$/', '', $src);
	if ($src === '' || preg_match('#^(https?:|data:|cid:)#i', $src)) {
		return '';
	}
	$src = str_replace('\\', '/', $src);
	$tries = array(__DIR__ . '/' . $src, dirname(__DIR__) . '/' . ltrim($src, '/'));
	if (strpos($src, '../') === 0) {
		$tries[] = dirname(__DIR__) . '/' . substr($src, 3);
	}
	$rel = ltrim($src, '/');
	if (strpos($rel, 'rkropen/') === 0) {
		$tries[] = dirname(dirname(__DIR__)) . '/' . substr($rel, 8);
	}
	foreach (array('newsletter', 'gemeinde') as $kind) {
		$tries[] = nl_data_dir($kind) . '/' . $rel;
		$base = basename($rel);
		foreach (array('media/gemeinde/' . $base, 'media/qr/' . $base, 'issues/' . $base) as $try) {
			$tries[] = nl_data_dir($kind) . '/' . $try;
		}
	}
	foreach ($tries as $abs) {
		$real = realpath($abs);
		if ($real && is_file($real)) {
			return $real;
		}
	}
	return '';
}

function nl_apply_crop_binary($bin, $box) {
	if (!function_exists('imagecreatefromstring') || !function_exists('imagecrop')) {
		return array($bin, '');
	}
	$im = @imagecreatefromstring($bin);
	if (!$im) {
		return array($bin, '');
	}
	$sw = imagesx($im);
	$sh = imagesy($im);
	$x = (int)round($sw * $box['x'] / 100);
	$y = (int)round($sh * $box['y'] / 100);
	$w = max(1, (int)round($sw * $box['w'] / 100));
	$h = max(1, (int)round($sh * $box['h'] / 100));
	if ($x + $w > $sw) {
		$w = $sw - $x;
	}
	if ($y + $h > $sh) {
		$h = $sh - $y;
	}
	if ($w < 1 || $h < 1) {
		imagedestroy($im);
		return array($bin, '');
	}
	$cropped = imagecrop($im, array('x' => $x, 'y' => $y, 'width' => $w, 'height' => $h));
	imagedestroy($im);
	if (!$cropped) {
		return array($bin, '');
	}
	ob_start();
	imagejpeg($cropped, null, 85);
	$out = ob_get_clean();
	imagedestroy($cropped);
	if (!is_string($out) || $out === '') {
		return array($bin, '');
	}
	return array($out, 'image/jpeg');
}

function nl_pdf_fit_binary($bin, $mime, $maxW = 520, $maxH = 680) {
	$info = @getimagesizefromstring($bin);
	if (!is_array($info) || empty($info[0]) || empty($info[1])) {
		return array($bin, $mime, 480, 320);
	}
	$sw = (int)$info[0];
	$sh = (int)$info[1];
	$scale = min(1, $maxW / max(1, $sw), $maxH / max(1, $sh));
	$dw = max(1, (int)round($sw * $scale));
	$dh = max(1, (int)round($sh * $scale));
	if (function_exists('imagecreatefromstring') && ($dw < $sw || $dh < $sh)) {
		$im = @imagecreatefromstring($bin);
		if ($im) {
			$dst = imagecreatetruecolor($dw, $dh);
			$white = imagecolorallocate($dst, 255, 255, 255);
			imagefilledrectangle($dst, 0, 0, $dw, $dh, $white);
			imagecopyresampled($dst, $im, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
			ob_start();
			imagejpeg($dst, null, 82);
			$out = ob_get_clean();
			imagedestroy($im);
			imagedestroy($dst);
			if (is_string($out) && $out !== '') {
				return array($out, 'image/jpeg', $dw, $dh);
			}
		}
	}
	return array($bin, $mime, $dw, $dh);
}

function nl_pdf_embed_images($html) {
	return preg_replace_callback('#<img\b[^>]*>#i', function ($m) {
		$tag = $m[0];
		if (!preg_match('#\ssrc=(["\'])([^"\']+)\1#i', $tag, $sm)) {
			return $tag;
		}
		$src = html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8');
		$alt = '';
		if (preg_match('#\salt=(["\'])([^"\']*)\1#i', $tag, $am)) {
			$alt = $am[2];
		}
		if (preg_match('#^(data:|cid:)#i', $src)) {
			return $tag;
		}
		$abs = '';
		if (preg_match('/(?:^|[?&])bild=([^&]+)/', $src, $bm)) {
			$abs = nl_public_media_file(rawurldecode($bm[1]));
		}
		if ($abs === '' && !preg_match('#^https?:#i', $src)) {
			$abs = nl_file_for_web_src($src);
		}
		if ($abs === '') {
			return $tag;
		}
		$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
		$mime = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp');
		if (!isset($mime[$ext])) {
			return $tag;
		}
		$bin = @file_get_contents($abs);
		if ($bin === false || $bin === '') {
			return $tag;
		}
		$mimeIn = $mime[$ext];
		if (preg_match('#\sdata-crop=(["\'])([0-9.]+),([0-9.]+),([0-9.]+),([0-9.]+)\1#', $tag, $cm)) {
			$box = nl_crop_box(array('x' => $cm[2], 'y' => $cm[3], 'w' => $cm[4], 'h' => $cm[5]));
			if ($box) {
				list($cropped, $cropMime) = nl_apply_crop_binary($bin, $box);
				if ($cropMime !== '') {
					$bin = $cropped;
					$mimeIn = $cropMime;
				}
			}
		}
		$maxW = (strpos($tag, 'nl-pdf-side') !== false) ? 200 : 520;
		$maxH = (strpos($tag, 'nl-pdf-side') !== false) ? 240 : 680;
		$want = 0;
		if (preg_match('#\swidth=(["\'])(\d+)\1#', $tag, $wm)) {
			$want = (int)$wm[2];
		}
		if ($want > 40 && $want < $maxW) {
			$maxW = $want * 3;
			$maxH = $want * 4;
		}
		list($bin, $mimeOut, $dw, $dh) = nl_pdf_fit_binary($bin, $mimeIn, $maxW, $maxH);
		if ($want > 40 && $dw > $want) {
			$dh = max(1, (int)round($dh * $want / $dw));
			$dw = $want;
		}
		return '<img src="data:' . $mimeOut . ';base64,' . base64_encode($bin) . '" alt="' . nl_h($alt) . '" width="' . (int)$dw . '" height="' . (int)$dh . '" style="width:' . (int)$dw . 'px;height:' . (int)$dh . 'px;display:block;">';
	}, (string)$html);
}

function nl_pdf_note_letter_frame($frame, $canvas, $fontMetrics) {
	if (!is_object($frame) || !method_exists($frame, 'get_node')) {
		return;
	}
	$node = $frame->get_node();
	if (!is_object($node) || !method_exists($node, 'getAttribute')) {
		return;
	}
	$key = trim((string)$node->getAttribute('data-letter'));
	if ($key === '') {
		return;
	}
	if (!isset($GLOBALS['nl_pdf_letter_pages']) || !is_array($GLOBALS['nl_pdf_letter_pages'])) {
		$GLOBALS['nl_pdf_letter_pages'] = array();
	}
	if (isset($GLOBALS['nl_pdf_letter_pages'][$key])) {
		return;
	}
	$page = (is_object($canvas) && method_exists($canvas, 'get_page_number')) ? (int)$canvas->get_page_number() : 0;
	if ($page > 0) {
		$GLOBALS['nl_pdf_letter_pages'][$key] = $page;
	}
}

function nl_pdf_watch_letters($pdf) {
	$GLOBALS['nl_pdf_letter_pages'] = array();
	if (!is_object($pdf) || !method_exists($pdf, 'setCallbacks')) {
		return;
	}
	$list = array();
	if (method_exists($pdf, 'getCallbacks')) {
		$have = $pdf->getCallbacks();
		if (is_array($have)) {
			foreach ($have as $event => $fns) {
				if (!is_array($fns)) {
					continue;
				}
				foreach ($fns as $fn) {
					if (is_callable($fn)) {
						$list[] = array('event' => (string)$event, 'f' => $fn);
					}
				}
			}
		}
	}
	$list[] = array('event' => 'begin_frame', 'f' => 'nl_pdf_note_letter_frame');
	$pdf->setCallbacks($list);
}

function nl_pdf_letter_span($pageNumber, $pageCount, $starts) {
	$pageNumber = (int)$pageNumber;
	$pageCount = (int)$pageCount;
	$n = is_array($starts) ? count($starts) : 0;
	if ($n < 2) {
		return array($pageNumber, $pageCount);
	}
	$idx = -1;
	$i = 0;
	for ($i = 0; $i < $n; $i++) {
		if ((int)$starts[$i] <= $pageNumber) {
			$idx = $i;
		}
	}
	if ($idx < 0) {
		return array($pageNumber, $pageCount);
	}
	$start = (int)$starts[$idx];
	$end = ($idx + 1 < $n) ? ((int)$starts[$idx + 1] - 1) : $pageCount;
	if ($end < $start) {
		$end = $start;
	}
	$local = $pageNumber - $start + 1;
	$total = $end - $start + 1;
	if ($local < 1) {
		$local = 1;
	}
	if ($total < 1) {
		$total = 1;
	}
	return array($local, $total);
}

function nl_pdf_stamp_pages($pdf) {
	$canvas = $pdf->getCanvas();
	if (!$canvas || !method_exists($canvas, 'page_script')) {
		return;
	}
	$starts = array();
	if (isset($GLOBALS['nl_pdf_letter_pages']) && is_array($GLOBALS['nl_pdf_letter_pages'])) {
		foreach ($GLOBALS['nl_pdf_letter_pages'] as $page) {
			$page = (int)$page;
			if ($page > 0) {
				$starts[] = $page;
			}
		}
	}
	$starts = array_values(array_unique($starts));
	sort($starts);
	$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($starts) {
		$font = $fontMetrics->getFont('Calibri', 'normal');
		if (!$font) {
			$font = $fontMetrics->getFont('DejaVu Sans', 'normal');
		}
		if (!$font) {
			return;
		}
		list($local, $total) = nl_pdf_letter_span($pageNumber, $pageCount, $starts);
		$text = $local . '/' . $total;
		$size = 9;
		$width = $fontMetrics->getTextWidth($text, $font, $size);
		$right = 1.5 * 72 / 2.54;
		$x = $canvas->get_width() - $right - $width;
		$y = $canvas->get_height() - (1.15 * 72 / 2.54);
		$canvas->text($x, $y, $text, $font, $size, array(0.2, 0.2, 0.2));
	});
}

function nl_newsletter_pdf_css() {
	return '@page{margin:1.5cm;}body{margin:0;font-family:Calibri,sans-serif;font-size:11px;line-height:1.45;color:#222;}'
		. 'h1{font-size:20px;color:#355e4b;font-weight:500;margin:0 0 4px;}'
		. 'h2{font-size:14px;margin:10px 0 4px;page-break-after:avoid;}'
		. '.nl-lead{font-style:italic;font-weight:700;margin:0 0 8px;}'
		. '.nl-intro{margin:0 0 10px;}'
		. '.nl-logo{text-align:right;margin:0 0 6px;}'
		. '.nl-logo img{width:28mm;height:auto;}'
		. 'article{margin:0 0 10px;}'
		. 'figure{margin:0 0 8px;}'
		. 'img{display:block;margin:0 0 6px;}'
		. 'table{width:100%;border-collapse:collapse;}'
		. '.nl-pdf-stat th,.nl-pdf-stat td{border-bottom:0.4pt solid #ccc;padding:2px 4px;font-size:10px;}'
		. '.nl-fn-qr{width:19mm;height:auto;}'
		. '.nl-fn-logo{width:22mm;height:auto;}'
		. 'p{margin:0 0 6px;}'
		. 'a{color:#2f6b4f;}'
		. 'strong{font-weight:700;}'
		. 'svg{display:none;}';
}

function nl_stream_newsletter_pdf($issue, $prefixHtml) {
	$autoload = dirname(__DIR__) . '/rt/dompdf/vendor/autoload.php';
	if (!is_file($autoload)) {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'PDF-Bibliothek fehlt.';
		exit;
	}
	$prevMail = !empty($GLOBALS['nl_for_mail']);
	$prevPdf = !empty($GLOBALS['nl_for_pdf']);
	$GLOBALS['nl_for_mail'] = true;
	$GLOBALS['nl_for_pdf'] = true;
	try {
		$person = array('anredeArt' => 'Du', 'public' => true, 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
		$body = (string)$prefixHtml . nl_render_newsletter($issue, $person, false);
	} finally {
		$GLOBALS['nl_for_mail'] = $prevMail;
		$GLOBALS['nl_for_pdf'] = $prevPdf;
	}
	require_once $autoload;
	$body = preg_replace('#<svg\b[^>]*>.*?</svg>#is', '', $body);
	$body = nl_pdf_embed_images($body);
	$html = '<!doctype html><html><head><meta charset="utf-8"><style>' . nl_newsletter_pdf_css() . '</style></head><body>' . $body . '</body></html>';
	try {
		$options = new Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', false);
		$pdf = new Dompdf\Dompdf($options);
		$pdf->loadHtml($html);
		$pdf->setPaper('A4', 'portrait');
		nl_pdf_watch_letters($pdf);
		$pdf->render();
		nl_pdf_stamp_pages($pdf);
	} catch (Throwable $e) {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'PDF konnte nicht erstellt werden.';
		exit;
	}
	$name = 'Newsletter_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)($issue['id'] ?? 'ausgabe'));
	$pdf->stream($name . '.pdf', array('Attachment' => true));
	exit;
}

function nl_mail_document($html) {
	$css = 'body{margin:0;padding:0;background:#fff;}'
		. '.nl-mail{font-family:Calibri,"Segoe UI",sans-serif;font-size:16px;line-height:1.45;color:#222;padding:16px 18px 28px;}'
		. '.nl-mail h2{font-size:22px;font-weight:700;margin:18px 0 8px;}'
		. '.nl-mail img{border:0;}'
		. '.nl-chart{width:100%;height:auto;}'
		. '.nl-chart-row{width:100%;}'
		. '.nl-footnote{margin-top:18px;font-size:13px;color:#444;}'
		. '.nl-fn-std{margin-top:8px;}'
		. 'a{color:#2f6b4f;}';
	return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style type="text/css">' . $css . '</style></head><body>' . $html . '</body></html>';
}

function nl_mail_html_with_images($html, &$cidParts) {
	$cidParts = array();
	$html = preg_replace_callback('#<img\b[^>]*>#i', function ($m) use (&$cidParts) {
		$tag = $m[0];
		if (!preg_match('#\ssrc=(["\'])([^"\']+)\1#i', $tag, $sm)) {
			return $tag;
		}
		$src = html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8');
		if (preg_match('#^https?://#i', $src) || strpos($src, 'cid:') === 0) {
			return $tag;
		}
		$bin = '';
		$mime = '';
		$name = 'bild.jpg';
		if (preg_match('#^data:image/(png|jpeg|jpg|gif);base64,([A-Za-z0-9+/=\r\n]+)$#i', $src, $dm)) {
			$bin = base64_decode(preg_replace('/\s+/', '', $dm[2]));
			$kind = strtolower($dm[1]);
			$mime = ($kind === 'png') ? 'image/png' : (($kind === 'gif') ? 'image/gif' : 'image/jpeg');
			$name = 'grafik.' . (($mime === 'image/png') ? 'png' : 'jpg');
			$info = @getimagesizefromstring((string)$bin);
			$iw = (is_array($info) && !empty($info[0])) ? (int)$info[0] : 0;
			$ih = (is_array($info) && !empty($info[1])) ? (int)$info[1] : 0;
			$dispW = 0;
			if (preg_match('#\swidth=(["\'])(\d+)\1#', $tag, $wm)) {
				$dispW = (int)$wm[2];
			} elseif (preg_match('#width:\s*(\d+)px#', $tag, $wm)) {
				$dispW = (int)$wm[1];
			}
			if ($dispW <= 0) {
				$dispW = ($iw > 640) ? 640 : max(1, $iw);
			}
			$showW = $dispW;
			$showH = ($iw > 0) ? max(1, (int)round($ih * $showW / $iw)) : max(1, $ih);
			$tag = preg_replace('#\sstyle=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#\swidth=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#\sheight=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#<img\b#i', '<img width="' . (int)$showW . '" height="' . (int)$showH . '" style="width:' . (int)$showW . 'px;height:' . (int)$showH . 'px;display:block;border:0;"', $tag, 1);
		} else {
			$abs = '';
			if (preg_match('/(?:^|[?&])bild=([^&]+)/', $src, $bm)) {
				$abs = nl_public_media_file(rawurldecode($bm[1]));
			}
			if ($abs === '') {
				$abs = nl_file_for_web_src($src);
			}
			if ($abs === '' || !is_file($abs)) {
				return $tag;
			}
			$bin = @file_get_contents($abs);
			if (!is_string($bin) || $bin === '') {
				return $tag;
			}
			$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
			$types = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp');
			$mime = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
			$name = basename($abs);
			if (preg_match('#\sdata-crop=(["\'])([0-9.]+),([0-9.]+),([0-9.]+),([0-9.]+)\1#', $tag, $cm)) {
				$box = nl_crop_box(array('x' => $cm[2], 'y' => $cm[3], 'w' => $cm[4], 'h' => $cm[5]));
				if ($box) {
					list($cropped, $cropMime) = nl_apply_crop_binary($bin, $box);
					if ($cropMime !== '') {
						$bin = $cropped;
						$mime = $cropMime;
						$name = preg_replace('/\.[A-Za-z0-9]+$/', '', $name) . '.jpg';
					}
				}
			}
			$side = (strpos($tag, 'nl-pdf-side') !== false);
			$dispW = 0;
			$dispH = 0;
			if (preg_match('#\swidth=(["\'])(\d+)\1#', $tag, $wm)) {
				$dispW = (int)$wm[2];
			} elseif (preg_match('#width:\s*(\d+)px#', $tag, $wm)) {
				$dispW = (int)$wm[1];
			}
			if (preg_match('#\sheight=(["\'])(\d+)\1#', $tag, $hm)) {
				$dispH = (int)$hm[2];
			} elseif (preg_match('#height:\s*(\d+)px#', $tag, $hm)) {
				$dispH = (int)$hm[1];
			}
			if ($dispW > 0) {
				$fitH = ($dispH > 0) ? max(1, $dispH * 2) : max(1, $dispW * 3);
				list($bin, $mime, $dw, $dh) = nl_pdf_fit_binary($bin, $mime, max(1, $dispW * 2), $fitH);
				$showW = $dispW;
				if ($dispH > 0) {
					$showH = $dispH;
				} else {
					$showH = ($dw > 0) ? max(1, (int)round($dh * $showW / $dw)) : max(1, (int)$dh);
				}
			} else {
				list($bin, $mime, $dw, $dh) = nl_pdf_fit_binary($bin, $mime, $side ? 280 : 640, $side ? 420 : 800);
				$showW = (int)$dw;
				$showH = (int)$dh;
			}
			$tag = preg_replace('#\sstyle=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#\swidth=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#\sheight=(["\'])[^"\']*\1#i', '', $tag);
			$tag = preg_replace('#<img\b#i', '<img width="' . (int)$showW . '" height="' . (int)$showH . '" style="width:' . (int)$showW . 'px;height:' . (int)$showH . 'px;display:block;border:0;"', $tag, 1);
		}
		if (!is_string($bin) || $bin === '') {
			return $tag;
		}
		$cid = 'img' . count($cidParts) . '@newsletter.local';
		$cidParts[] = array('cid' => $cid, 'path' => '', 'bin' => $bin, 'mime' => $mime, 'name' => $name);
		$tag = preg_replace('#\sdata-crop=(["\'])[^"\']*\1#i', '', $tag);
		$tag = preg_replace('#\ssrc=(["\'])[^"\']+\1#i', ' src="cid:' . $cid . '"', $tag, 1);
		return $tag;
	}, $html);
	return $html;
}

function nl_send_mail($to, $cc, $subject, $html, $extraFiles = array()) {
	$settings = nl_settings();
	$host = trim((string)$settings['smtpHost']);
	if ($host === '') {
		return array('ok' => false, 'error' => 'SMTP-Host ist leer. Zuerst unter Intern / Konfiguration / Nachrichten / Mail eintragen.');
	}
	$to = trim((string)$to);
	if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		return array('ok' => false, 'error' => 'Ungültige Empfängeradresse.');
	}
	$cidParts = array();
	if (stripos($html, '<html') === false && strpos($html, 'nl-mail') !== false) {
		$html = nl_mail_document($html);
	}
	$htmlCid = nl_mail_html_with_images($html, $cidParts);
	$text = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", $htmlCid)), ENT_QUOTES, 'UTF-8'));
	$boundary = 'nl_' . bin2hex(random_bytes(8));
	$alt = 'alt_' . bin2hex(random_bytes(6));
	$fromEmail = trim((string)$settings['fromEmail']);
	$fromName = trim((string)$settings['fromName']);
	$headers = 'From: ' . nl_encode_header($fromName) . ' <' . $fromEmail . ">\r\n";
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
	$headers .= 'Subject: ' . nl_encode_header($subject) . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";
	$body = '--' . $boundary . "\r\n";
	$body .= 'Content-Type: multipart/alternative; boundary="' . $alt . "\"\r\n\r\n";
	$body .= '--' . $alt . "\r\n";
	$body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $text . "\r\n";
	$body .= '--' . $alt . "\r\n";
	$body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $htmlCid . "\r\n";
	$body .= '--' . $alt . "--\r\n";
	$files = $cidParts;
	foreach ($extraFiles as $path) {
		if (is_file($path)) {
			$files[] = array('cid' => '', 'path' => $path);
		}
	}
	foreach ($files as $file) {
		if (isset($file['bin']) && is_string($file['bin']) && $file['bin'] !== '') {
			$bin = $file['bin'];
			$filename = (string)($file['name'] ?? 'bild.jpg');
			$mime = (string)($file['mime'] ?? 'application/octet-stream');
		} else {
			$bin = @file_get_contents($file['path']);
			if ($bin === false) {
				continue;
			}
			$filename = basename($file['path']);
			$mime = 'application/octet-stream';
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if ($ext === 'png') {
				$mime = 'image/png';
			} elseif ($ext === 'jpg' || $ext === 'jpeg') {
				$mime = 'image/jpeg';
			} elseif ($ext === 'pdf') {
				$mime = 'application/pdf';
			} elseif ($ext === 'gif') {
				$mime = 'image/gif';
			}
		}
		$body .= '--' . $boundary . "\r\n";
		if ($file['cid'] !== '') {
			$body .= 'Content-Type: ' . $mime . '; name="' . $filename . "\"\r\n";
			$body .= "Content-Transfer-Encoding: base64\r\n";
			$body .= 'Content-ID: <' . $file['cid'] . ">\r\n";
			$body .= "Content-Disposition: inline; filename=\"" . $filename . "\"\r\n\r\n";
		} else {
			$body .= 'Content-Type: ' . $mime . '; name="' . $filename . "\"\r\n";
			$body .= "Content-Transfer-Encoding: base64\r\n";
			$body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
		}
		$body .= chunk_split(base64_encode($bin)) . "\r\n";
	}
	$body .= '--' . $boundary . "--\r\n";
	$port = (int)$settings['smtpPort'];
	if ($port <= 0) {
		$port = 587;
	}
	$secure = (string)$settings['smtpSecure'];
	if ($secure !== 'none' && !nl_php_has_tls()) {
		return array('ok' => false, 'error' => nl_php_tls_hint());
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
		nl_smtp_expect($fp, null, array(220));
		$ehloHost = 'newsletter.local';
		nl_smtp_expect($fp, 'EHLO ' . $ehloHost, array(250));
		if ($secure === 'starttls') {
			nl_smtp_expect($fp, 'STARTTLS', array(220));
			if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
				throw new RuntimeException('STARTTLS fehlgeschlagen.');
			}
			nl_smtp_expect($fp, 'EHLO ' . $ehloHost, array(250));
		}
		$user = (string)$settings['smtpUser'];
		$pass = (string)$settings['smtpPass'];
		if ($user !== '') {
			nl_smtp_expect($fp, 'AUTH LOGIN', array(334));
			nl_smtp_expect($fp, base64_encode($user), array(334));
			nl_smtp_expect($fp, base64_encode($pass), array(235));
		}
		nl_smtp_expect($fp, 'MAIL FROM:<' . $fromEmail . '>', array(250));
		nl_smtp_expect($fp, 'RCPT TO:<' . $to . '>', array(250, 251));
		foreach ($ccList as $ccTo) {
			nl_smtp_expect($fp, 'RCPT TO:<' . $ccTo . '>', array(250, 251));
		}
		nl_smtp_expect($fp, 'DATA', array(354));
		$payload = nl_dot_stuff($headers . "\r\n" . $body);
		fwrite($fp, $payload . "\r\n.\r\n");
		list($code, $data) = nl_smtp_read($fp);
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

function nl_php_has_tls() {
	$transports = function_exists('stream_get_transports') ? stream_get_transports() : array();
	foreach ($transports as $name) {
		if (strcasecmp($name, 'ssl') === 0 || strcasecmp($name, 'tls') === 0) {
			return true;
		}
	}
	return extension_loaded('openssl');
}

function nl_php_tls_hint() {
	return 'PHP auf dem NAS hat OpenSSL nicht geladen. Verschlüsseltes SMTP (Port 465 oder 587) geht so nicht. In der Web Station beim PHP-Profil dieser Website die Erweiterung openssl aktivieren, die Seite neu laden und die Testmail erneut senden.';
}

function nl_log_mail($kind, $to, $subject, $ok, $error) {
	$items = nl_items('newsletter_log.json');
	$items[] = array(
		'id' => substr(sha1((string)microtime(true)), 0, 8),
		'time' => date('Y-m-d H:i:s'),
		'kind' => $kind,
		'to' => $to,
		'subject' => $subject,
		'ok' => $ok ? 1 : 0,
		'error' => $error,
	);
	if (count($items) > 400) {
		$items = array_slice($items, -400);
	}
	nl_save_items('newsletter_log.json', $items);
}

function nl_groups($items) {
	$groups = array();
	foreach ($items as $item) {
		$g = trim((string)($item['gruppe'] ?? ''));
		if ($g !== '') {
			$groups[$g] = $g;
		}
	}
	natcasesort($groups);
	return array_values($groups);
}

function nl_attachment_path($value) {
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}
	if (is_file($value)) {
		return $value;
	}
	$rel = ltrim(str_replace('\\', '/', $value), '/');
	foreach (array('newsletter', 'gemeinde') as $kind) {
		$abs = nl_data_dir($kind) . '/' . $rel;
		if (is_file($abs)) {
			return $abs;
		}
	}
	if (is_file(__DIR__ . '/' . $rel)) {
		return __DIR__ . '/' . $rel;
	}
	$web = nl_media_src($value);
	if ($web !== '' && is_file(__DIR__ . '/' . $web)) {
		return __DIR__ . '/' . $web;
	}
	return '';
}

function nl_recipient_columns() {
	return array(
		'ausloesen' => array('label' => 'Auslösen', 'type' => 'check', 'w' => 's'),
		'vorname' => array('label' => 'Vorname', 'type' => 'text', 'w' => 'm'),
		'name' => array('label' => 'Name', 'type' => 'text', 'w' => 'm'),
		'firma' => array('label' => 'Firma', 'type' => 'text', 'w' => 'm'),
		'anredeArt' => array('label' => 'Du/Sie', 'type' => 'select', 'options' => array('Sie', 'Du'), 'w' => 's'),
		'geschlecht' => array('label' => 'm/f/b', 'type' => 'text', 'w' => 'xs'),
		'anrede' => array('label' => 'Anrede', 'type' => 'text', 'w' => 'l'),
		'email' => array('label' => 'E-Mail', 'type' => 'email', 'w' => 'l'),
		'cc' => array('label' => 'Cc', 'type' => 'text', 'w' => 'm'),
		'gruppe' => array('label' => 'Gruppe', 'type' => 'text', 'w' => 'm'),
		'summe' => array('label' => 'Summe', 'type' => 'text', 'w' => 's'),
		'merci' => array('label' => 'Merci', 'type' => 'text', 'w' => 'm'),
		'statusGeschenk' => array('label' => 'Geschenk', 'type' => 'text', 'w' => 's'),
		'rechnungsadresse' => array('label' => 'Rechnung', 'type' => 'text', 'w' => 'l'),
		'bemerkung' => array('label' => 'Bemerkung', 'type' => 'text', 'w' => 'l'),
		'anhang' => array('label' => 'Anhang', 'type' => 'text', 'w' => 'l'),
		'status' => array('label' => 'Status', 'type' => 'ro', 'w' => 'm'),
		'dateSend' => array('label' => 'Gesendet', 'type' => 'ro', 'w' => 'm'),
	);
}

function nl_gemeinde_columns() {
	return array(
		'aktiv' => array('label' => 'Aktiv', 'type' => 'check', 'w' => 's'),
		'ausloesen' => array('label' => 'Mail', 'type' => 'check', 'w' => 's'),
		'gemeinde' => array('label' => 'Gemeinde', 'type' => 'text', 'w' => 'm'),
		'statSicht' => array('label' => 'Sicht', 'type' => 'select', 'options' => array('gde' => 'Gemeinde', 'jg' => 'Jagdgebiet'), 'w' => 's'),
		'jgCode' => array('label' => 'Jagdgebiet', 'type' => 'select', 'options' => nl_jg_choices(), 'w' => 'm'),
		'anschrift1' => array('label' => 'Anschrift 1', 'type' => 'text', 'w' => 'l'),
		'anschrift2' => array('label' => 'Anschrift 2', 'type' => 'text', 'w' => 'm'),
		'strasse' => array('label' => 'Strasse', 'type' => 'text', 'w' => 'm'),
		'strasse2' => array('label' => 'Strasse 2', 'type' => 'text', 'w' => 'm'),
		'plz' => array('label' => 'PLZ', 'type' => 'text', 'w' => 'xs'),
		'ort' => array('label' => 'Ort', 'type' => 'text', 'w' => 's'),
		'email' => array('label' => 'E-Mail', 'type' => 'email', 'w' => 'l'),
		'gdeCode' => array('label' => 'Gde', 'type' => 'ro', 'w' => 'xs'),
		'status' => array('label' => 'Status', 'type' => 'ro', 'w' => 'm'),
	);
}

function nl_col_width_class(array $meta) {
	$w = (string)($meta['w'] ?? 'm');
	if (!in_array($w, array('xs', 's', 'm', 'l'), true)) {
		$w = 'm';
	}
	return 'nl-w-' . $w;
}

function nl_ui_hidden_columns($uiFile, array $columns, array $defaultHidden = array()) {
	$data = nl_read_json($uiFile, array());
	$hidden = (isset($data['hidden']) && is_array($data['hidden'])) ? $data['hidden'] : $defaultHidden;
	$valid = array();
	foreach ($hidden as $col) {
		$col = (string)$col;
		if (isset($columns[$col])) {
			$valid[] = $col;
		}
	}
	return $valid;
}

function nl_ui_data($uiFile) {
	$data = nl_read_json($uiFile, array());
	return is_array($data) ? $data : array();
}

function nl_default_col_px(array $meta) {
	$map = array('xs' => 68, 's' => 90, 'm' => 144, 'l' => 208);
	$w = (string)($meta['w'] ?? 'm');
	return isset($map[$w]) ? $map[$w] : 144;
}

function nl_ui_widths($uiFile, array $columns) {
	$data = nl_ui_data($uiFile);
	$raw = (isset($data['widths']) && is_array($data['widths'])) ? $data['widths'] : array();
	$out = array();
	foreach ($columns as $key => $meta) {
		$px = isset($raw[$key]) ? (int)$raw[$key] : nl_default_col_px($meta);
		if ($px < 36) {
			$px = 36;
		}
		if ($px > 640) {
			$px = 640;
		}
		$out[$key] = $px;
	}
	return $out;
}

function nl_ui_save_hidden($uiFile, array $allColumns, array $visible) {
	$hidden = array();
	$vis = array_fill_keys($visible, true);
	foreach (array_keys($allColumns) as $col) {
		if (!isset($vis[$col])) {
			$hidden[] = $col;
		}
	}
	$data = nl_ui_data($uiFile);
	$data['hidden'] = $hidden;
	$data['updated_at'] = date('c');
	return nl_write_json($uiFile, $data);
}

function nl_ui_save_widths($uiFile, array $columns, array $posted) {
	$data = nl_ui_data($uiFile);
	$widths = (isset($data['widths']) && is_array($data['widths'])) ? $data['widths'] : array();
	foreach ($posted as $col => $px) {
		$col = (string)$col;
		if (!isset($columns[$col])) {
			continue;
		}
		$px = (int)$px;
		if ($px < 36 || $px > 640) {
			continue;
		}
		$widths[$col] = $px;
	}
	$data['widths'] = $widths;
	$data['updated_at'] = date('c');
	return nl_write_json($uiFile, $data);
}

function nl_row_is_empty(array $row, array $columns) {
	foreach ($columns as $key => $meta) {
		$type = (string)($meta['type'] ?? 'text');
		if ($type === 'check' || $type === 'ro') {
			continue;
		}
		if (trim((string)($row[$key] ?? '')) !== '') {
			return false;
		}
	}
	return true;
}

function nl_echo_column_panel($uiFile, array $columns, array $hidden, $action, array $hiddenFields = array()) {
	$hideSet = array_fill_keys($hidden, true);
	echo "<details class='nl-col-vis'><summary>Spalten ein-/ausblenden</summary>";
	echo "<form method='post' class='nl-col-vis-form'>";
	echo "<input type='hidden' name='action' value='" . nl_h($action) . "'>";
	foreach ($hiddenFields as $k => $v) {
		echo "<input type='hidden' name='" . nl_h($k) . "' value='" . nl_h($v) . "'>";
	}
	echo "<p class='nl-note'>Abgewählt = ausgeblendet. Die Einstellung bleibt gespeichert, die Daten in den ausgeblendeten Spalten bleiben erhalten.</p>";
	echo "<div class='nl-col-vis-grid'>";
	foreach ($columns as $key => $meta) {
		$id = 'nl-col-vis-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
		$checked = isset($hideSet[$key]) ? '' : ' checked';
		echo "<label class='nl-col-vis-item' for='" . nl_h($id) . "'>";
		echo "<input type='checkbox' name='col_visible[]' id='" . nl_h($id) . "' value='" . nl_h($key) . "'" . $checked . ">";
		echo "<span>" . nl_h($meta['label']) . "</span></label>";
	}
	echo "</div>";
	echo "<p class='nl-actions'><button type='submit'>Spalten speichern</button>";
	echo " <button type='submit' name='col_vis_show_all' value='1' class='nl-btn secondary'>Alle anzeigen</button></p>";
	echo "</form></details>";
}

function nl_echo_grid_cell($i, $key, array $meta, $value) {
	$type = (string)($meta['type'] ?? 'text');
	$name = 'rows[' . (int)$i . '][' . $key . ']';
	$cls = 'nl-col nl-col-' . $key . ' ' . nl_col_width_class($meta);
	if ($type === 'check') {
		echo "<td class='" . nl_h($cls) . "' data-col='" . nl_h($key) . "' data-sort='" . (!empty($value) ? '1' : '0') . "'>";
		echo "<input type='checkbox' name='" . nl_h($name) . "' value='1'" . (!empty($value) ? ' checked' : '') . "></td>";
		return;
	}
	$sort = mb_strtolower(trim((string)$value), 'UTF-8');
	if ($type === 'ro') {
		echo "<td class='" . nl_h($cls) . "' data-col='" . nl_h($key) . "' data-sort='" . nl_h($sort) . "'>";
		echo "<input type='hidden' name='" . nl_h($name) . "' value='" . nl_h($value) . "'>";
		echo "<span>" . nl_h($value) . "</span></td>";
		return;
	}
	echo "<td class='" . nl_h($cls) . "' data-col='" . nl_h($key) . "' data-sort='" . nl_h($sort) . "'>";
	if ($type === 'select') {
		echo "<select name='" . nl_h($name) . "'>";
		foreach ((array)($meta['options'] ?? array()) as $optKey => $opt) {
			$val = is_int($optKey) ? (string)$opt : (string)$optKey;
			$sel = ((string)$value === $val) ? ' selected' : '';
			echo "<option value='" . nl_h($val) . "'" . $sel . ">" . nl_h($opt) . "</option>";
		}
		echo "</select></td>";
		return;
	}
	$inputType = ($type === 'email') ? 'email' : 'text';
	echo "<input type='" . $inputType . "' name='" . nl_h($name) . "' value='" . nl_h($value) . "' autocomplete='off'></td>";
}

function nl_ico($name) {
	$paths = array(
		'star' => '<path fill="currentColor" d="M12 2.6l2.7 5.6 6.2.8-4.5 4.3 1.1 6.1L12 16.8 6.5 19.4l1.1-6.1L3.1 9l6.2-.8z"/>',
		'star-o' => '<path fill="none" stroke="currentColor" stroke-width="1.6" d="M12 3.2l2.4 5 5.5.7-4 3.9 1 5.5L12 16.2 7.1 18.3l1-5.5-4-3.9 5.5-.7z"/>',
		'trash' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M9 7V5h6v2M8 7l1 13h6l1-13"/>',
		'plus' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M12 5v14M5 12h14"/>',
		'save' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7M8 20v-6h8v6"/>',
		'pdf' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M7 3h7l5 5v13H7zM14 3v5h5M9 13h6M9 17h4"/>',
		'print' => '<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round" d="M7 8V3h10v5"/><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M5 8h14a2 2 0 0 1 2 2v5H3v-5a2 2 0 0 1 2-2z"/><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M7 14h10v7H7z"/>',
		'share' => '<circle cx="6" cy="12" r="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="6.5" r="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="17.5" r="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path fill="none" stroke="currentColor" stroke-width="1.8" d="M8 11.1l7-3.4M8 12.9l7 3.4"/>',
		'gear' => '<circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1.1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
	);
	$d = isset($paths[$name]) ? $paths[$name] : '';
	return '<svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true">' . $d . '</svg>';
}

function nl_echo_grid_row($i, array $row, array $columns, $deletable = true) {
	$id = (string)($row['id'] ?? '');
	$fav = !empty($row['favorit']);
	echo "<tr data-nl-row" . ($fav ? " class='nl-fav'" : "") . ">";
	echo "<td class='nl-col-fav'><button type='button' class='nl-fav-btn' title='Favorit' aria-label='Favorit' aria-pressed='" . ($fav ? 'true' : 'false') . "'>" . nl_ico($fav ? 'star' : 'star-o') . "</button>";
	echo "<input type='hidden' name='rows[" . (int)$i . "][favorit]' value='" . ($fav ? '1' : '0') . "'></td>";
	echo "<td class='nl-col-sel'><input type='hidden' name='rows[" . (int)$i . "][id]' value='" . nl_h($id) . "'>";
	if ($deletable) {
		echo "<input type='checkbox' data-nl-del='1' name='delete[]' value='" . nl_h($id !== '' ? $id : ('new-' . $i)) . "' title='Zum Löschen markieren'>";
		echo "<button type='button' class='nl-row-del nl-icon-btn' title='Diese Zeile löschen' aria-label='Diese Zeile löschen'>" . nl_ico('trash') . "</button>";
	}
	echo "</td>";
	foreach ($columns as $key => $meta) {
		nl_echo_grid_cell($i, $key, $meta, $row[$key] ?? '');
	}
	echo "</tr>";
}

function nl_echo_excel_grid($formAction, $saveAction, array $items, array $columns, array $hidden, array $opts = array()) {
	$hideSet = array_fill_keys($hidden, true);
	$hideCss = '';
	foreach ($hideSet as $col => $_) {
		$hideCss .= '.nl-grid .nl-col-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$col) . '{display:none;}';
	}
	if ($hideCss !== '') {
		echo "<style>" . $hideCss . "</style>";
	}
	$ord = 0;
	foreach ($items as $i => $row) {
		if (is_array($row)) {
			$items[$i]['_ord'] = $ord++;
		}
	}
	usort($items, function ($a, $b) {
		$fa = !empty($a['favorit']);
		$fb = !empty($b['favorit']);
		if ($fa !== $fb) {
			return $fa ? -1 : 1;
		}
		return ((int)($a['_ord'] ?? 0)) - ((int)($b['_ord'] ?? 0));
	});
	foreach ($items as $i => $row) {
		if (is_array($row)) {
			unset($items[$i]['_ord']);
		}
	}
	$count = count($items);
	$widths = (isset($opts['widths']) && is_array($opts['widths'])) ? $opts['widths'] : array();
	$widthAction = (string)($opts['widthAction'] ?? '');
	$favoritAction = (string)($opts['favoritAction'] ?? '');
	echo "<form method='post' action='" . nl_h($formAction) . "' class='nl-grid-form' data-nl-grid data-width-action='" . nl_h($widthAction) . "' data-favorit-action='" . nl_h($favoritAction) . "' autocomplete='off'>";
	echo "<input type='hidden' name='action' value='" . nl_h($saveAction) . "'>";
	echo "<input type='hidden' name='rows_json' value=''>";
	echo "<input type='hidden' name='delete_json' value=''>";
	foreach ((array)($opts['hiddenFields'] ?? array()) as $k => $v) {
		echo "<input type='hidden' name='" . nl_h($k) . "' value='" . nl_h($v) . "'>";
	}
	echo "<div class='nl-grid-toolbar'>";
	echo "<label>Suche <input type='search' class='nl-grid-search' placeholder='Alle sichtbaren Spalten…' autocomplete='off'></label>";
	echo "<span class='nl-note' id='nl-grid-count'>" . (int)$count . " Zeilen</span>";
	echo "<button type='submit' name='delete_checked' value='1' class='nl-icon-btn' title='Markierte Zeilen löschen' aria-label='Markierte Zeilen löschen' onclick='return confirm(\"Markierte Zeilen löschen?\")'>" . nl_ico('trash') . "</button>";
	echo "</div>";
	echo "<div class='nl-grid-wrap'><table class='nl-grid'><colgroup><col class='nl-col-fav' style='width:2.4rem'><col class='nl-col-sel' style='width:4.2rem'>";
	foreach ($columns as $key => $meta) {
		$px = isset($widths[$key]) ? (int)$widths[$key] : nl_default_col_px($meta);
		echo "<col data-col='" . nl_h($key) . "' style='width:" . $px . "px'>";
	}
	echo "</colgroup><thead><tr>";
	echo "<th class='nl-col-fav' title='Favoriten stehen oben'>" . nl_ico('star') . "</th>";
	echo "<th class='nl-col-sel' title='Zeilen löschen'><input type='checkbox' class='nl-sel-all' title='Sichtbare Zeilen markieren'></th>";
	foreach ($columns as $key => $meta) {
		$wcls = nl_col_width_class($meta);
		$px = isset($widths[$key]) ? (int)$widths[$key] : nl_default_col_px($meta);
		echo "<th class='nl-col nl-col-" . nl_h($key) . " " . $wcls . " sortable' data-col='" . nl_h($key) . "' style='width:" . $px . "px'>" . nl_h($meta['label']) . "<span class='sort-ind'></span><span class='nl-col-resize' data-col='" . nl_h($key) . "' title='Breite ziehen'></span></th>";
	}
	echo "</tr><tr class='nl-filter-row'>";
	echo "<th class='nl-col-fav'></th><th></th>";
	foreach ($columns as $key => $meta) {
		echo "<th class='nl-col nl-col-" . nl_h($key) . " " . nl_col_width_class($meta) . "'><input type='search' class='nl-col-filter' data-col='" . nl_h($key) . "' placeholder='…' autocomplete='off'></th>";
	}
	echo "</tr></thead><tbody>";
	$i = 0;
	foreach ($items as $row) {
		if (!is_array($row)) {
			continue;
		}
		nl_echo_grid_row($i, $row, $columns);
		$i++;
	}
	$blank = array('id' => '');
	foreach (array_keys($columns) as $key) {
		$blank[$key] = '';
	}
	nl_echo_grid_row($i, $blank, $columns);
	echo "</tbody></table></div>";
	echo "<template id='nl-grid-blank'>";
	nl_echo_grid_row(9999, $blank, $columns);
	echo "</template>";
	echo "<div class='nl-actions'>";
	echo "<button type='submit' class='nl-icon-btn nl-icon-btn-primary' title='Adressen speichern' aria-label='Adressen speichern'>" . nl_ico('save') . "</button>";
	echo "<button type='button' class='nl-icon-btn' data-nl-add-row title='Zeile hinzufügen' aria-label='Zeile hinzufügen'>" . nl_ico('plus') . "</button>";
	echo "<button type='submit' name='delete_checked' value='1' class='nl-icon-btn' title='Markierte Zeilen löschen' aria-label='Markierte Zeilen löschen' onclick='return confirm(\"Markierte Zeilen löschen?\")'>" . nl_ico('trash') . "</button>";
	echo "</div>";
	echo "<p class='nl-note'>Am rechten Spaltenrand ziehen ändert die Breite und speichert sie. Klick auf den Spaltenkopf sortiert (nochmals klicken kehrt um). Enter geht in die Zelle darunter. Zelländerungen erst nach «Adressen speichern».</p>";
	echo "</form>";
	nl_echo_grid_script();
}

function nl_posted_grid_rows() {
	$rows = array();
	if (isset($_POST['rows_json']) && is_string($_POST['rows_json']) && trim($_POST['rows_json']) !== '') {
		$decoded = json_decode($_POST['rows_json'], true);
		if (is_array($decoded)) {
			$rows = $decoded;
		}
	} elseif (isset($_POST['rows']) && is_array($_POST['rows'])) {
		$rows = $_POST['rows'];
	}
	if (empty($_POST['delete_checked'])) {
		foreach ($rows as $i => $row) {
			if (is_array($row) && isset($row['_delete'])) {
				unset($rows[$i]['_delete']);
			}
		}
	}
	return $rows;
}

function nl_posted_grid_deletes() {
	$ids = array();
	$one = trim((string)($_POST['delete_one'] ?? ''));
	if ($one !== '') {
		$ids[] = $one;
	}
	if (empty($_POST['delete_checked']) && $one === '') {
		return $ids;
	}
	if (isset($_POST['delete_json']) && is_string($_POST['delete_json']) && trim($_POST['delete_json']) !== '') {
		$decoded = json_decode($_POST['delete_json'], true);
		if (is_array($decoded)) {
			foreach ($decoded as $id) {
				$ids[] = (string)$id;
			}
			return $ids;
		}
	}
	if (isset($_POST['delete']) && is_array($_POST['delete'])) {
		foreach ($_POST['delete'] as $id) {
			$ids[] = (string)$id;
		}
	}
	return $ids;
}

function nl_set_favorit($file, $id, $on) {
	$id = (string)$id;
	if ($id === '') {
		return false;
	}
	$items = nl_items($file);
	$found = false;
	foreach ($items as $i => $item) {
		if ((string)($item['id'] ?? '') === $id) {
			$items[$i]['favorit'] = (bool)$on;
			$found = true;
			break;
		}
	}
	if ($found) {
		nl_save_items($file, $items);
	}
	return $found;
}

function nl_merge_grid_rows(array $existing, array $posted, array $columns, array $deleteIds) {
	$byId = array();
	foreach ($existing as $row) {
		if (is_array($row) && isset($row['id']) && (string)$row['id'] !== '') {
			$byId[(string)$row['id']] = $row;
		}
	}
	$del = array();
	foreach ($deleteIds as $id) {
		$del[(string)$id] = true;
	}
	$out = array();
	foreach ($posted as $row) {
		if (!is_array($row)) {
			continue;
		}
		$id = trim((string)($row['id'] ?? ''));
		if (!empty($row['_delete'])) {
			continue;
		}
		if ($id !== '' && isset($del[$id])) {
			continue;
		}
		if (strncmp($id, 'new-', 4) === 0 && isset($del[$id])) {
			continue;
		}
		$base = ($id !== '' && isset($byId[$id])) ? $byId[$id] : array();
		foreach ($columns as $key => $meta) {
			$type = (string)($meta['type'] ?? 'text');
			if ($type === 'check') {
				$base[$key] = !empty($row[$key]);
			} elseif ($type === 'ro') {
				if (!isset($base[$key])) {
					$base[$key] = (string)($row[$key] ?? '');
				}
			} else {
				$base[$key] = trim((string)($row[$key] ?? ''));
			}
		}
		if ($id === '' || strncmp($id, 'new-', 4) === 0) {
			if (nl_row_is_empty($base, $columns)) {
				continue;
			}
			$seed = (string)($base['email'] ?? $base['gemeinde'] ?? '') . microtime(true);
			$base['id'] = 'n' . substr(sha1($seed), 0, 8);
		} else {
			$base['id'] = $id;
		}
		if (array_key_exists('favorit', $row)) {
			$base['favorit'] = ((string)$row['favorit'] === '1');
		}
		$out[] = $base;
	}
	return $out;
}

function nl_echo_grid_script() {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	echo "<script src='rkrk_newsletter_grid.js?v=20260924del'></script>\n";
}

function nl_norm_space($value) {
	return preg_replace('/\s+/', ' ', trim((string)$value));
}

function nl_review_akey($name) {
	$name = nl_norm_space($name);
	if (function_exists('mb_strtolower')) {
		return mb_strtolower($name, 'UTF-8');
	}
	return strtolower($name);
}

function nl_review_label($name) {
	$name = nl_norm_space($name);
	return $name === '' ? 'ohne Absender' : $name;
}

function nl_review_new_token() {
	try {
		$raw = random_bytes(16);
	} catch (Exception $e) {
		$raw = substr(hash('sha256', uniqid('review', true) . mt_rand(), true), 0, 16);
	}
	return bin2hex($raw);
}

function nl_issue_explicit_absender($issue) {
	if (!is_array($issue)) {
		return '';
	}
	foreach (array('absender', 'fromName', 'from', 'sender') as $key) {
		if (!array_key_exists($key, $issue)) {
			continue;
		}
		$value = nl_norm_space($issue[$key]);
		if ($value !== '') {
			return $value;
		}
	}
	return '';
}

function nl_recipient_absender_names($recipients) {
	$names = array();
	$seen = array();
	if (!is_array($recipients)) {
		return $names;
	}
	foreach ($recipients as $person) {
		if (!is_array($person)) {
			continue;
		}
		$value = '';
		foreach (array('absender', 'Absender', 'fromName', 'sender') as $key) {
			if (!array_key_exists($key, $person)) {
				continue;
			}
			$value = nl_norm_space($person[$key]);
			if ($value !== '') {
				break;
			}
		}
		if ($value === '') {
			continue;
		}
		$akey = nl_review_akey($value);
		if (isset($seen[$akey])) {
			continue;
		}
		$seen[$akey] = true;
		$names[] = $value;
	}
	return $names;
}

function nl_newsletter_review_bundles($issue, $issues, $recipients) {
	if (!is_array($issue)) {
		return array();
	}
	$id = nl_norm_space($issue['id'] ?? '');
	if ($id === '') {
		return array();
	}
	$explicit = nl_issue_explicit_absender($issue);
	if ($explicit !== '') {
		$ids = array();
		$key = nl_review_akey($explicit);
		foreach ($issues as $one) {
			if (!is_array($one)) {
				continue;
			}
			if (nl_review_akey(nl_issue_explicit_absender($one)) !== $key) {
				continue;
			}
			$oid = nl_norm_space($one['id'] ?? '');
			if ($oid !== '') {
				$ids[] = $oid;
			}
		}
		return array(array(
			'kind' => 'newsletter',
			'mode' => 'absender',
			'page' => 'nl-abs:' . $key,
			'absender' => $explicit,
			'campaign' => '',
			'ids' => $ids,
		));
	}
	$fromPeople = nl_recipient_absender_names($recipients);
	if (count($fromPeople) > 1) {
		$out = array();
		foreach ($fromPeople as $name) {
			$out[] = array(
				'kind' => 'newsletter',
				'mode' => 'issue',
				'page' => 'nl-issue:' . $id,
				'absender' => $name,
				'campaign' => '',
				'ids' => array($id),
			);
		}
		return $out;
	}
	$name = '';
	if (count($fromPeople) === 1) {
		$name = $fromPeople[0];
	} else {
		$settings = nl_settings();
		$name = nl_norm_space($settings['fromName'] ?? '');
	}
	return array(array(
		'kind' => 'newsletter',
		'mode' => 'issue',
		'page' => 'nl-issue:' . $id,
		'absender' => $name,
		'campaign' => '',
		'ids' => array($id),
	));
}

function nl_gemeinde_review_bundles($campaign, $gemeinden) {
	$campId = (string)($campaign['id'] ?? '');
	$groups = array();
	if ($campId === '' || !is_array($gemeinden)) {
		return array();
	}
	foreach ($gemeinden as $g) {
		if (!is_array($g) || empty($g['aktiv'])) {
			continue;
		}
		$gid = (string)($g['id'] ?? '');
		if ($gid === '') {
			continue;
		}
		$row = nl_with_letter($campaign, $g);
		$name = nl_norm_space($row['absender'] ?? '');
		$akey = nl_review_akey($name);
		if (!isset($groups[$akey])) {
			$groups[$akey] = array(
				'kind' => 'gemeinde',
				'mode' => 'bundle',
				'page' => 'gb:' . $campId,
				'absender' => $name,
				'campaign' => $campId,
				'ids' => array(),
			);
		}
		$groups[$akey]['ids'][] = $gid;
	}
	return array_values($groups);
}

function nl_review_items() {
	$data = nl_read_json('newsletter_review.json', array());
	if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
		return array();
	}
	return $data['items'];
}

function nl_review_sync($page, $bundles) {
	$page = (string)$page;
	$items = nl_review_items();
	$old = array();
	$kept = array();
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}
		if ((string)($item['page'] ?? '') === $page) {
			$old[nl_review_akey($item['absender'] ?? '')] = $item;
			continue;
		}
		$kept[] = $item;
	}
	$rows = array();
	if (!is_array($bundles)) {
		$bundles = array();
	}
	foreach ($bundles as $bundle) {
		if (!is_array($bundle)) {
			continue;
		}
		$absender = nl_norm_space($bundle['absender'] ?? '');
		$akey = nl_review_akey($absender);
		$token = '';
		if (isset($old[$akey]['token']) && preg_match('/^[a-f0-9]{32}$/', (string)$old[$akey]['token'])) {
			$token = (string)$old[$akey]['token'];
		} else {
			$token = nl_review_new_token();
		}
		$ids = array();
		if (isset($bundle['ids']) && is_array($bundle['ids'])) {
			foreach ($bundle['ids'] as $id) {
				$id = (string)$id;
				if ($id !== '') {
					$ids[] = $id;
				}
			}
		}
		$item = array(
			'token' => $token,
			'kind' => (string)($bundle['kind'] ?? ''),
			'page' => $page,
			'mode' => (string)($bundle['mode'] ?? ''),
			'absender' => $absender,
			'campaign' => (string)($bundle['campaign'] ?? ''),
			'ids' => $ids,
			'updated' => date('c'),
		);
		$kept[] = $item;
		$rows[] = $item;
	}
	nl_write_json('newsletter_review.json', array('items' => $kept));
	return $rows;
}

function nl_review_publish($bundles) {
	$byPage = array();
	if (!is_array($bundles)) {
		return array();
	}
	foreach ($bundles as $bundle) {
		if (!is_array($bundle)) {
			continue;
		}
		$page = (string)($bundle['page'] ?? '');
		if ($page === '') {
			continue;
		}
		if (!isset($byPage[$page])) {
			$byPage[$page] = array();
		}
		$byPage[$page][] = $bundle;
	}
	$rows = array();
	foreach ($byPage as $page => $list) {
		$synced = nl_review_sync($page, $list);
		foreach ($synced as $row) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function nl_review_find($token) {
	$token = strtolower(trim((string)$token));
	if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
		return null;
	}
	foreach (nl_review_items() as $item) {
		if (!is_array($item)) {
			continue;
		}
		$have = strtolower(trim((string)($item['token'] ?? '')));
		if (strlen($have) === 32 && hash_equals($have, $token)) {
			return $item;
		}
	}
	return null;
}

function nl_review_url($token) {
	return nl_public_base() . '/rkrk_newsletter_review.php?t=' . rawurlencode((string)$token);
}

function nl_review_piece_label($kind, $count) {
	$count = (int)$count;
	if ($kind === 'newsletter') {
		return $count === 1 ? '1 Ausgabe' : ($count . ' Ausgaben');
	}
	return $count === 1 ? '1 Brief' : ($count . ' Briefe');
}

function nl_echo_review_fold($rows, $emptyNote) {
	static $scriptDone = false;
	echo "<details class='nl-fold nl-noprint'><summary class='nl-fold-title'>Vorabreview</summary><div class='nl-fold-body'>";
	echo "<p class='nl-note'>Ein Link pro Absender öffnet die Druckansicht ohne Intern-Login. Es wird keine Mail versendet.</p>";
	if (!is_array($rows) || count($rows) === 0) {
		echo "<p>" . nl_h($emptyNote) . "</p></div></details>\n";
		return;
	}
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$url = nl_review_url($row['token'] ?? '');
		$count = isset($row['ids']) && is_array($row['ids']) ? count($row['ids']) : 0;
		echo "<div class='nl-review-row'>";
		echo "<span class='nl-review-name'>" . nl_h(nl_review_label($row['absender'] ?? '')) . "</span>";
		echo "<span class='nl-review-count'>" . nl_h(nl_review_piece_label($row['kind'] ?? '', $count)) . "</span>";
		echo "<input class='nl-review-url' type='text' readonly value='" . nl_h($url) . "'>";
		echo "<button type='button' class='nl-btn secondary nl-review-copy'>Link kopieren</button>";
		echo "</div>";
	}
	echo "</div></details>\n";
	if ($scriptDone) {
		return;
	}
	$scriptDone = true;
	echo "<script>(function(){document.querySelectorAll('.nl-review-copy').forEach(function(btn){btn.addEventListener('click',function(){var row=btn.parentNode;var input=row?row.querySelector('.nl-review-url'):null;if(!input)return;var done=function(){btn.textContent='Kopiert';setTimeout(function(){btn.textContent='Link kopieren';},1600);};var copyFallback=function(){input.focus();input.select();var ok=false;try{ok=document.execCommand('copy');}catch(e){}if(ok)done();};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(input.value).then(done).catch(copyFallback);return;}copyFallback();});});})();</script>\n";
}

function nl_echo_sheet_pages_script() {
	echo "<script>\n";
	echo "(function(){\n";
	echo "document.body.classList.add('nl-print-body');\n";
	echo "var probe=document.createElement('div');\n";
	echo "probe.style.cssText='position:absolute;left:-9999px;height:297mm;width:0';\n";
	echo "document.body.appendChild(probe);\n";
	echo "var pageH=probe.getBoundingClientRect().height;\n";
	echo "probe.remove();\n";
	echo "window.addEventListener('load', function(){ window.print(); });\n";
	echo "if(!(pageH>0)) return;\n";
	echo "document.querySelectorAll('.nl-a4-doc').forEach(function(doc){\n";
	echo "var letter=doc.querySelector('.nl-a4.nl-letter');\n";
	echo "if(!letter) return;\n";
	echo "var slips=doc.querySelectorAll('.nl-a4.nl-slip-sheet');\n";
	echo "var anchor=slips.length?slips[0]:null;\n";
	echo "var kids=Array.prototype.slice.call(letter.children);\n";
	echo "letter.style.height=pageH+'px';letter.style.maxHeight=pageH+'px';letter.style.overflow='auto';\n";
	echo "function sheet(){var p=letter.cloneNode(false);doc.insertBefore(p,anchor);return p;}\n";
	echo "kids.forEach(function(n){n.remove();});\n";
	echo "var page=letter;\n";
	echo "function place(node,page){\n";
	echo "page.appendChild(node);\n";
	echo "if(page.scrollHeight<=pageH+2) return page;\n";
	echo "if(page.children.length>1){node.remove();page=sheet();return place(node,page);}\n";
	echo "if(node.children&&node.children.length>1){var inner=Array.prototype.slice.call(node.children);node.remove();inner.forEach(function(child){page=place(child,page);});}\n";
	echo "return page;\n";
	echo "}\n";
	echo "kids.forEach(function(node){page=place(node,page);});\n";
	echo "doc.querySelectorAll('.nl-a4.nl-letter').forEach(function(p){if(p.scrollHeight>pageH+2){p.style.height='auto';p.style.maxHeight='none';p.style.overflow='visible';}else{p.style.overflow='hidden';}});\n";
	echo "});\n";
	echo "function nlStamp(list){list.forEach(function(el,i){var mark=el.querySelector('.nl-page-no');if(!mark){mark=document.createElement('div');mark.className='nl-page-no';el.appendChild(mark);}mark.textContent=(i+1)+'/'+list.length;});}\n";
	echo "var seen=[];\n";
	echo "document.querySelectorAll('.nl-a4-doc').forEach(function(doc){var pages=[];doc.querySelectorAll('.nl-a4').forEach(function(el){if(el.style.display==='none')return;pages.push(el);seen.push(el);});if(pages.length)nlStamp(pages);});\n";
	echo "var loose=[];\n";
	echo "document.querySelectorAll('.nl-a4').forEach(function(el){if(el.style.display==='none')return;if(seen.indexOf(el)>=0)return;loose.push(el);});\n";
	echo "if(loose.length)nlStamp(loose);\n";
	echo "})();\n";
	echo "</script>\n";
}
