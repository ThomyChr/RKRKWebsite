<?php
session_start();
$folderoffset = '../';
chdir(dirname(__DIR__));
include_once 'scripts/rkrk_gen.php';
include_once 'scripts/rkrk_csvfiles.php';
include_once 'scripts/rkrk_menu.php';
include_once 'rkrk_access_control.php';
include_once 'newsletter/rkrk_newsletter_lib.php';

CheckIPandExit();
nl_ensure_store();

$bereich = isset($_REQUEST['bereich']) ? (string)$_REQUEST['bereich'] : 'newsletter';
if ($bereich === 'gemeinde' || $bereich === 'druck') {
	$q = $_GET;
	if ($bereich === 'druck') {
		$q['druck'] = '1';
	}
	unset($q['bereich']);
	header('Location: rkrk_gemeindebrief.php?' . http_build_query($q));
	exit;
}
$allowed = array('newsletter', 'adressen', 'publikation', 'einstellungen');
if (!in_array($bereich, $allowed, true)) {
	$bereich = 'newsletter';
}
if ($bereich === 'publikation') {
	$bereich = 'newsletter';
}

function nl_page_redirect($bereich, $extra = array()) {
	$q = array_merge(array('bereich' => $bereich), $extra);
	header('Location: rkrk_newsletter.php?' . http_build_query($q));
	exit;
}

function nl_page_status_time($status) {
	$status = trim((string)$status);
	if (preg_match('/^ok:\s*(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}:\d{2}:\d{2})/', $status, $m)) {
		return $m[3] . '-' . $m[2] . '-' . $m[1] . ' ' . $m[4];
	}
	return '';
}

function nl_page_issue_sent_ok($issue, array $recipients) {
	$subject = trim((string)($issue['subject'] ?? ''));
	$latest = array();
	if ($subject !== '') {
		foreach (nl_items('newsletter_log.json') as $line) {
			if (!is_array($line) || (string)($line['kind'] ?? '') !== 'newsletter') {
				continue;
			}
			if (trim((string)($line['subject'] ?? '')) !== $subject) {
				continue;
			}
			$email = strtolower(trim((string)($line['to'] ?? '')));
			if ($email === '') {
				continue;
			}
			$time = trim((string)($line['time'] ?? ''));
			if (isset($latest[$email]) && strcmp($time, $latest[$email]['time']) < 0) {
				continue;
			}
			$latest[$email] = array('time' => $time, 'ok' => !empty($line['ok']));
		}
	}
	$okIds = array();
	foreach ($recipients as $person) {
		if (!is_array($person)) {
			continue;
		}
		$id = (string)($person['id'] ?? '');
		if ($id === '') {
			continue;
		}
		$email = strtolower(trim((string)($person['email'] ?? '')));
		$status = trim((string)($person['status'] ?? ''));
		$statusOk = (strncmp($status, 'ok:', 3) === 0);
		$statusFail = (strncmp($status, 'Fehler:', 7) === 0);
		$statusTime = trim((string)($person['dateSend'] ?? ''));
		if ($statusTime === '') {
			$statusTime = nl_page_status_time($status);
		}
		$sentOk = $statusOk;
		if ($email !== '' && isset($latest[$email])) {
			$log = $latest[$email];
			$logNewer = ($statusTime === '' || strcmp($log['time'], $statusTime) > 0);
			if ($statusFail) {
				$sentOk = ($logNewer && $log['ok']);
			} elseif ($logNewer) {
				$sentOk = $log['ok'];
			}
		}
		if ($sentOk) {
			$okIds[$id] = true;
		}
	}
	return $okIds;
}

function nl_post_person_from_request() {
	$art = (isset($_POST['anredeArt']) && $_POST['anredeArt'] === 'Du') ? 'Du' : 'Sie';
	$gender = strtolower(trim((string)($_POST['geschlecht'] ?? '')));
	if (!in_array($gender, array('m', 'f', 'b', ''), true)) {
		$gender = '';
	}
	return array(
		'id' => trim((string)($_POST['id'] ?? '')),
		'ausloesen' => !empty($_POST['ausloesen']),
		'vorname' => trim((string)($_POST['vorname'] ?? '')),
		'name' => trim((string)($_POST['name'] ?? '')),
		'firma' => trim((string)($_POST['firma'] ?? '')),
		'anredeArt' => $art,
		'geschlecht' => $gender,
		'anrede' => trim((string)($_POST['anrede'] ?? '')),
		'summe' => trim((string)($_POST['summe'] ?? '')),
		'merci' => trim((string)($_POST['merci'] ?? '')),
		'statusGeschenk' => trim((string)($_POST['statusGeschenk'] ?? '')),
		'rechnungsadresse' => trim((string)($_POST['rechnungsadresse'] ?? '')),
		'gruppe' => trim((string)($_POST['gruppe'] ?? '')),
		'email' => trim((string)($_POST['email'] ?? '')),
		'bemerkung' => trim((string)($_POST['bemerkung'] ?? '')),
		'cc' => trim((string)($_POST['cc'] ?? '')),
		'anhang' => trim((string)($_POST['anhang'] ?? '')),
		'status' => trim((string)($_POST['status'] ?? '')),
		'dateSend' => trim((string)($_POST['dateSend'] ?? '')),
	);
}

function nl_page_issue_season_year($issue) {
	if (!is_array($issue)) {
		return (string)nl_current_season_year();
	}
	return (string)nl_season_year_of($issue);
}

function nl_page_apply_season_year($issue, $year) {
	if (!is_array($issue) || !preg_match('/^(20\d{2})$/', (string)$year)) {
		return $issue;
	}
	$year = (string)$year;
	foreach (array('saison', 'saisonJahr', 'year', 'seasonYear') as $key) {
		if (!array_key_exists($key, $issue)) {
			continue;
		}
		$issue[$key] = is_int($issue[$key]) ? (int)$year : $year;
	}
	list($from, $to) = nl_season_dates((int)$year);
	$issue['from'] = $from;
	$issue['to'] = $to;
	if (!array_key_exists('saison', $issue) && !array_key_exists('saisonJahr', $issue) && !array_key_exists('year', $issue) && !array_key_exists('seasonYear', $issue)) {
		$issue['saison'] = $year;
	}
	return $issue;
}

function nl_page_replace_issue_prefix($value, $from, $to) {
	if (is_array($value)) {
		foreach ($value as $key => $item) {
			$value[$key] = nl_page_replace_issue_prefix($item, $from, $to);
		}
		return $value;
	}
	if (is_string($value) && $from !== '' && $from !== $to) {
		return str_replace($from, $to, $value);
	}
	return $value;
}

function nl_page_copy_issue_media($oldId, $newId) {
	$oldId = nl_issue_file_id($oldId);
	$newId = nl_issue_file_id($newId);
	if ($oldId === '' || $newId === '' || $oldId === $newId) {
		return false;
	}
	$srcDir = nl_issues_dir() . '/' . $oldId;
	if (!is_dir($srcDir)) {
		nl_issue_media_dir($newId);
		return true;
	}
	$dstDir = nl_issue_media_dir($newId);
	$names = scandir($srcDir);
	if (!is_array($names)) {
		return false;
	}
	$copied = array();
	foreach ($names as $name) {
		if ($name === '.' || $name === '..') {
			continue;
		}
		$src = $srcDir . '/' . $name;
		if (!is_file($src) || substr($name, -5) === '.json') {
			continue;
		}
		$dst = $dstDir . '/' . $name;
		if (!@copy($src, $dst)) {
			foreach ($copied as $path) {
				@unlink($path);
			}
			return false;
		}
		$copied[] = $dst;
	}
	return true;
}

$nlLoggedIn = isset($SessNme_RegisteredUser) && trim((string)($_SESSION[$SessNme_RegisteredUser] ?? '')) !== '';
$nlAllowed = $nlLoggedIn && MenuEntryAccessGranted('Newsletter');
if ($nlAllowed && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	$msg = '';
	$err = '';
	if ($action === 'test_issue') {
		$settings = nl_settings();
		$to = trim((string)($settings['fromEmail'] ?? ''));
		$issue = nl_find_item(nl_items('newsletter_issues.json'), (string)($_POST['issue'] ?? ''));
		if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
			nl_page_redirect('newsletter', array('err' => 'In der Mail-Konfiguration ist keine gültige Absender-Adresse hinterlegt.', 'issue' => (string)($_POST['issue'] ?? '')));
		}
		if (!$issue) {
			nl_page_redirect('newsletter', array('err' => 'Keine Ausgabe gewählt.'));
		}
		$person = array('anredeArt' => 'Du', 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
		$GLOBALS['nl_for_mail'] = true;
		$html = nl_render_newsletter($issue, $person);
		$GLOBALS['nl_for_mail'] = false;
		$subject = '[Test] ' . (string)$issue['subject'];
		$result = nl_send_mail($to, '', $subject, $html);
		nl_log_mail('test', $to, $subject, $result['ok'], $result['error']);
		$extra = $result['ok'] ? array('msg' => 'Testmail gesendet an ' . $to, 'issue' => $issue['id']) : array('err' => $result['error'], 'issue' => $issue['id']);
		nl_page_redirect('newsletter', $extra);
	}
	if ($action === 'import_newsletter') {
		if (!empty($_FILES['xlsx']['tmp_name'])) {
			$result = nl_import_newsletter_xlsx($_FILES['xlsx']['tmp_name']);
			if (isset($result['error'])) {
				$err = $result['error'];
			} else {
				$msg = 'Import: ' . (int)$result['added'] . ' neu, ' . (int)$result['updated'] . ' aktualisiert.';
			}
		} else {
			$err = 'Keine Datei gewählt.';
		}
		nl_page_redirect('adressen', $err !== '' ? array('err' => $err) : array('msg' => $msg));
	}
	if ($action === 'save_nl_ui') {
		$cols = nl_recipient_columns();
		$visible = !empty($_POST['col_vis_show_all'])
			? array_keys($cols)
			: (isset($_POST['col_visible']) && is_array($_POST['col_visible']) ? $_POST['col_visible'] : array());
		nl_ui_save_hidden('newsletter_ui.json', $cols, $visible);
		nl_page_redirect('adressen', array('msg' => 'Spalten gespeichert.'));
	}
	if ($action === 'save_nl_widths') {
		$posted = isset($_POST['widths']) && is_array($_POST['widths']) ? $_POST['widths'] : array();
		nl_ui_save_widths('newsletter_ui.json', nl_recipient_columns(), $posted);
		if (!empty($_POST['ajax'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo '{"ok":true}';
			exit;
		}
		nl_page_redirect('adressen', array('msg' => 'Spaltenbreiten gespeichert.'));
	}
	if ($action === 'save_nl_favorit') {
		nl_set_favorit('newsletter_recipients.json', (string)($_POST['id'] ?? ''), !empty($_POST['favorit']));
		if (!empty($_POST['ajax'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo '{"ok":true}';
			exit;
		}
		nl_page_redirect('adressen', array('msg' => 'Favorit gespeichert.'));
	}
	if ($action === 'save_recipients_grid') {
		$cols = nl_recipient_columns();
		$posted = nl_posted_grid_rows();
		$delete = nl_posted_grid_deletes();
		$items = nl_merge_grid_rows(nl_items('newsletter_recipients.json'), $posted, $cols, $delete);
		nl_save_items('newsletter_recipients.json', $items);
		nl_page_redirect('adressen', array('msg' => count($items) . ' Adressen gespeichert.'));
	}
	if ($action === 'delete_recipient') {
		$id = (string)($_POST['id'] ?? '');
		$items = array();
		foreach (nl_items('newsletter_recipients.json') as $item) {
			if ((string)$item['id'] !== $id) {
				$items[] = $item;
			}
		}
		nl_save_items('newsletter_recipients.json', $items);
		nl_page_redirect('adressen', array('msg' => 'Adresse gelöscht.'));
	}
	if ($action === 'set_ausloesen') {
		$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
		$on = !empty($_POST['set_on']);
		$items = nl_items('newsletter_recipients.json');
		foreach ($items as $i => $item) {
			if (in_array((string)$item['id'], $ids, true)) {
				$items[$i]['ausloesen'] = $on;
			}
		}
		nl_save_items('newsletter_recipients.json', $items);
		nl_page_redirect('adressen', array('msg' => $on ? 'Markierung gesetzt.' : 'Markierung entfernt.'));
	}
	if ($action === 'save_issue') {
		$sections = array();
		$titles = isset($_POST['sec_title']) && is_array($_POST['sec_title']) ? $_POST['sec_title'] : array();
		$texts = isset($_POST['sec_text']) && is_array($_POST['sec_text']) ? $_POST['sec_text'] : array();
		$images = isset($_POST['sec_image']) && is_array($_POST['sec_image']) ? $_POST['sec_image'] : array();
		$ids = isset($_POST['sec_id']) && is_array($_POST['sec_id']) ? $_POST['sec_id'] : array();
		$caps = isset($_POST['sec_caption']) && is_array($_POST['sec_caption']) ? $_POST['sec_caption'] : array();
		$shows = isset($_POST['sec_show']) && is_array($_POST['sec_show']) ? $_POST['sec_show'] : array();
		$crops = isset($_POST['sec_crop']) && is_array($_POST['sec_crop']) ? $_POST['sec_crop'] : array();
		$issueFolder = nl_issue_file_id($_POST['id'] ?? '');
		if ($issueFolder === '') {
			$issueFolder = date('Y-m');
		}
		foreach ($titles as $i => $title) {
			$image = trim((string)($images[$i] ?? ''));
			$uploaded = nl_store_upload_index('sec_file', $i, 'issues/' . $issueFolder);
			if ($uploaded !== '') {
				$image = $uploaded;
			}
			$rowId = trim((string)($ids[$i] ?? '')) !== '' ? trim((string)$ids[$i]) : ('s' . ($i + 1));
			$more = array();
			$keep = (isset($_POST['sec_more_keep'][$rowId]) && is_array($_POST['sec_more_keep'][$rowId])) ? $_POST['sec_more_keep'][$rowId] : array();
			$keepList = array();
			foreach ($keep as $path) {
				$keepList[] = trim((string)$path);
			}
			$fileCount = 0;
			if (isset($_FILES['sec_more']['name'][$rowId]) && is_array($_FILES['sec_more']['name'][$rowId])) {
				$fileCount = count($_FILES['sec_more']['name'][$rowId]);
			}
			$slotCount = max(count($keepList), $fileCount);
			for ($fi = 0; $fi < $slotCount; $fi++) {
				$uploadedMore = nl_store_upload_at('sec_more', $rowId, $fi, 'issues/' . $issueFolder);
				$path = isset($keepList[$fi]) ? $keepList[$fi] : '';
				$use = ($uploadedMore !== '') ? $uploadedMore : $path;
				if ($use !== '' && $use !== $image && !in_array($use, $more, true)) {
					$more[] = $use;
				}
			}
			$row = array(
				'id' => $rowId,
				'title' => trim((string)$title),
				'showTitle' => ((string)($shows[$i] ?? '1') === '1') ? 1 : 0,
				'text' => (string)($texts[$i] ?? ''),
				'image' => $image,
				'caption' => trim((string)($caps[$i] ?? '')),
				'images' => $more,
			);
			if ($uploaded === '') {
				$crop = nl_crop_box(json_decode((string)($crops[$i] ?? ''), true));
				if ($crop) {
					$row['crop'] = $crop;
				}
			}
			$sections[] = $row;
		}
		if (count($sections) === 0) {
			$sections[] = nl_blank_section();
		}
		$issue = array(
			'id' => trim((string)($_POST['id'] ?? '')),
			'title' => trim((string)($_POST['title'] ?? '')),
			'subject' => trim((string)($_POST['subject'] ?? '')),
			'created' => trim((string)($_POST['created'] ?? date('Y-m-d'))),
			'status' => nl_issue_status(array('status' => (string)($_POST['status'] ?? 'draft'))),
			'published' => (nl_issue_status(array('status' => (string)($_POST['status'] ?? 'draft'))) === 'published'),
			'sections' => $sections,
			'bodyHtml' => nl_sections_to_html($sections),
			'closingDu' => (string)($_POST['closingDu'] ?? ''),
			'closingSie' => (string)($_POST['closingSie'] ?? ''),
			'footnote' => (string)($_POST['footnote'] ?? ''),
			'intro' => (string)($_POST['intro'] ?? ''),
			'discussion' => (string)($_POST['discussion'] ?? ''),
			'resultate' => isset($_POST['resultate']) && (string)$_POST['resultate'] !== '0',
			'resultsCaption' => trim((string)($_POST['resultsCaption'] ?? '')),
			'resultsSource' => ((string)($_POST['resultsSource'] ?? '') === 'statistik') ? 'statistik' : 'image',
			'resultsImage' => trim((string)($_POST['resultsImage'] ?? '')),
			'headerImage' => trim((string)($_POST['headerImage'] ?? '')),
		);
		$headerUp = nl_store_upload('header_file', 'issues/' . preg_replace('/[^A-Za-z0-9_-]/', '', $issue['id'] !== '' ? $issue['id'] : date('Y-m')));
		if ($headerUp !== '') {
			$issue['headerImage'] = $headerUp;
		}
		$uploadedResults = nl_store_upload('results_file', 'issues/' . nl_issue_file_id($issue['id'] !== '' ? $issue['id'] : $issueFolder));
		if ($uploadedResults !== '') {
			$issue['resultsImage'] = $uploadedResults;
			$issue['resultsSource'] = 'image';
		}
		if ($issue['id'] === '') {
			$issue['id'] = date('Y-m');
		}
		nl_apply_season_post($issue);
		$items = nl_items('newsletter_issues.json');
		$found = false;
		foreach ($items as $i => $existing) {
			if ((string)$existing['id'] === $issue['id']) {
				$items[$i] = array_merge($existing, $issue);
				$found = true;
				break;
			}
		}
		if (!$found) {
			$items[] = $issue;
		}
		nl_save_items('newsletter_issues.json', $items);
		nl_page_redirect('newsletter', array('msg' => 'Ausgabe gespeichert.', 'issue' => $issue['id']));
	}
	if ($action === 'copy_issue') {
		if ((string)($_POST['start'] ?? '') !== 'basis') {
			nl_page_redirect('newsletter', array('err' => 'Für einen leeren Start das Formular darunter ausfüllen und speichern.', 'issue' => 'neu'));
		}
		$baseId = nl_issue_file_id($_POST['base'] ?? '');
		$newId = nl_issue_file_id($_POST['id'] ?? '');
		$items = nl_items('newsletter_issues.json');
		$base = ($baseId !== '') ? nl_find_item($items, $baseId) : null;
		if (!$base || $newId === '') {
			nl_page_redirect('newsletter', array('err' => 'Vorlage und neue Kennung angeben.', 'issue' => 'neu'));
		}
		if ($newId === $baseId || nl_find_item($items, $newId)) {
			nl_page_redirect('newsletter', array('err' => 'Diese Kennung gibt es schon. Die Vorlage bleibt unverändert.', 'issue' => 'neu'));
		}
		$copy = $base;
		$copy['id'] = $newId;
		$copy['status'] = 'draft';
		$copy['published'] = false;
		$copy['created'] = '';
		foreach (array('dateSend', 'sentAt', 'sentOn', 'gesendet') as $sentKey) {
			if (array_key_exists($sentKey, $copy)) {
				$copy[$sentKey] = '';
			}
		}
		if (array_key_exists('sent', $copy)) {
			$copy['sent'] = false;
		}
		$year = trim((string)($_POST['saison'] ?? ''));
		if (!preg_match('/^(20\d{2})$/', $year)) {
			$year = nl_page_issue_season_year($base);
		}
		$copy = nl_page_apply_season_year($copy, $year);
		$fromPrefix = 'issues/' . nl_issue_file_id($base['id']) . '/';
		$toPrefix = 'issues/' . $newId . '/';
		if (!nl_page_copy_issue_media($base['id'], $newId)) {
			nl_page_redirect('newsletter', array('err' => 'Bilder der Vorlage konnten nicht kopiert werden.', 'issue' => 'neu'));
		}
		$copy = nl_page_replace_issue_prefix($copy, $fromPrefix, $toPrefix);
		$copy['id'] = $newId;
		$copy['status'] = 'draft';
		$copy['published'] = false;
		$copy['created'] = '';
		$items[] = $copy;
		nl_save_items('newsletter_issues.json', $items);
		nl_page_redirect('newsletter', array('msg' => 'Entwurf aus ' . $baseId . ' angelegt. Es wurde keine Mail versendet.', 'issue' => $newId));
	}
	if ($action === 'delete_issue') {
		$id = nl_issue_file_id($_POST['issue'] ?? '');
		if ($id === '' || !nl_delete_issue($id)) {
			nl_page_redirect('newsletter', array('err' => 'Ausgabe konnte nicht gelöscht werden.', 'issue' => $id));
		}
		nl_page_redirect('newsletter', array('msg' => 'Ausgabe inklusive Daten und Bilder gelöscht.'));
	}
	if ($action === 'save_publication') {
		$posted = (isset($_POST['status']) && is_array($_POST['status'])) ? $_POST['status'] : array();
		$items = nl_items('newsletter_issues.json');
		foreach ($items as $i => $issue) {
			$id = (string)($issue['id'] ?? '');
			$status = isset($posted[$id]) ? nl_issue_status(array('status' => (string)$posted[$id])) : nl_issue_status($issue);
			$items[$i]['status'] = $status;
			$items[$i]['published'] = ($status === 'published');
		}
		nl_save_items('newsletter_issues.json', $items);
		$back = array('msg' => 'Sichtbarkeit gespeichert.');
		$open = trim((string)($_POST['open_issue'] ?? ''));
		if ($open !== '') {
			$back['issue'] = $open;
		}
		nl_page_redirect('newsletter', $back);
	}
	if ($action === 'send_newsletter') {
		if (empty($_POST['confirm'])) {
			nl_page_redirect('newsletter', array('err' => 'Versand nicht bestätigt.'));
		}
		$issue = nl_find_item(nl_items('newsletter_issues.json'), (string)($_POST['issue'] ?? ''));
		if (!$issue) {
			nl_page_redirect('newsletter', array('err' => 'Keine Ausgabe gewählt.'));
		}
		$gruppe = trim((string)($_POST['filterGruppe'] ?? ''));
		$art = trim((string)($_POST['filterArt'] ?? ''));
		$only = !empty($_POST['onlyAusloesen']);
		$postedTo = (isset($_POST['send_to']) && is_array($_POST['send_to'])) ? $_POST['send_to'] : array();
		$wanted = array();
		foreach ($postedTo as $oneId) {
			$oneId = trim((string)$oneId);
			if ($oneId !== '') {
				$wanted[$oneId] = true;
			}
		}
		if (count($wanted) === 0) {
			nl_page_redirect('newsletter', array('err' => 'Keine Empfänger markiert.', 'issue' => (string)$issue['id']));
		}
		$sent = 0;
		$failed = 0;
		$items = nl_items('newsletter_recipients.json');
		$limit = 20;
		foreach ($items as $i => $person) {
			if ($sent + $failed >= $limit) {
				break;
			}
			if (!isset($wanted[(string)($person['id'] ?? '')])) {
				continue;
			}
			if ($only && empty($person['ausloesen'])) {
				continue;
			}
			if ($gruppe !== '' && (string)($person['gruppe'] ?? '') !== $gruppe) {
				continue;
			}
			if ($art !== '' && (string)($person['anredeArt'] ?? '') !== $art) {
				continue;
			}
			$email = trim((string)($person['email'] ?? ''));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			$GLOBALS['nl_for_mail'] = true;
			$html = nl_render_newsletter($issue, $person);
			$GLOBALS['nl_for_mail'] = false;
			$files = array();
			$anhang = nl_attachment_path($person['anhang'] ?? '');
			if ($anhang !== '') {
				$files[] = $anhang;
			}
			$result = nl_send_mail($email, (string)($person['cc'] ?? ''), (string)$issue['subject'], $html, $files);
			nl_log_mail('newsletter', $email, (string)$issue['subject'], $result['ok'], $result['error']);
			if ($result['ok']) {
				$items[$i]['status'] = 'ok: ' . date('d.m.Y H:i:s');
				$items[$i]['dateSend'] = date('Y-m-d H:i:s');
				$items[$i]['ausloesen'] = false;
				$sent++;
			} else {
				$items[$i]['status'] = 'Fehler: ' . $result['error'];
				$failed++;
				if ($sent === 0 && $failed === 1) {
					nl_save_items('newsletter_recipients.json', $items);
					nl_page_redirect('newsletter', array('err' => $result['error'], 'issue' => $issue['id']));
				}
			}
		}
		if ($sent === 0 && $failed === 0) {
			nl_page_redirect('newsletter', array('err' => 'Keine Empfänger markiert.', 'issue' => (string)$issue['id']));
		}
		nl_save_items('newsletter_recipients.json', $items);
		nl_page_redirect('newsletter', array('msg' => $sent . ' gesendet, ' . $failed . ' Fehler. Pro Klick höchstens 20.', 'issue' => $issue['id']));
	}
}

if ($nlAllowed && !empty($_GET['pdf'])) {
	$pdfId = trim((string)($_GET['issue'] ?? ''));
	$pdfIssue = ($pdfId !== '' && $pdfId !== 'neu') ? nl_find_item(nl_items('newsletter_issues.json'), $pdfId) : null;
	if (!$pdfIssue) {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Kein Newsletter.';
		exit;
	}
	nl_stream_newsletter_pdf($pdfIssue, '');
}

$msg = trim((string)($_GET['msg'] ?? ''));
$err = trim((string)($_GET['err'] ?? ''));

headerpage('Newsletter', false, $folderoffset);
GrantAccess();
if (!MenuEntryAccessGranted('Newsletter')) {
	echo "<main class='rkrk-shell-main'><p>Diese Seite ist im Menü Intern für die freigegebenen Personen.</p></main>";
	footer();
	exit;
}
CreateTopMenu($folderoffset);
echo "<link rel='stylesheet' href='rkrk_newsletter.css?v=20260925z21'>\n";
echo "<script src='rkrk_newsletter_view.js?v=20260925page2' defer></script>\n";
echo "<main class='rkrk-shell-main nl-wrap'>\n";
echo "<h1 class='rkrk-shell-page-title'>Newsletter</h1>\n";
echo "<nav class='nl-tabs noprint nl-noprint'>";
foreach (array('newsletter' => 'Newsletter', 'adressen' => 'Adressliste', 'einstellungen' => 'Einstellungen') as $key => $label) {
	$cls = ($bereich === $key) ? " class='on'" : '';
	echo "<a" . $cls . " href='rkrk_newsletter.php?bereich=" . nl_h($key) . "'>" . nl_h($label) . "</a>";
}
echo "<a href='rkrk_gemeindebrief.php'>Gemeindebriefe</a>";
echo "</nav>\n";
if ($msg !== '') {
	echo "<p class='nl-msg'>" . nl_h($msg) . "</p>";
}
if ($err !== '') {
	echo "<p class='nl-msg err'>" . nl_h($err) . "</p>";
}

if ($bereich === 'newsletter') {
	$issues = nl_items('newsletter_issues.json');
	usort($issues, function ($a, $b) {
		return strcmp(nl_issue_ym($b) . (string)($b['id'] ?? ''), nl_issue_ym($a) . (string)($a['id'] ?? ''));
	});
	$issueId = (string)($_GET['issue'] ?? '');
	$issue = $issueId !== '' ? nl_find_item($issues, $issueId) : (isset($issues[0]) ? $issues[0] : null);
	$recipients = nl_items('newsletter_recipients.json');
	$previewId = (string)($_GET['preview'] ?? '');
	$preview = $previewId !== '' ? nl_find_item($recipients, $previewId) : null;
	if (!empty($_GET['druck'])) {
		echo '<style>@media print{.rkrk-shell-page-title,.nl-msg,.nl-tabs{display:none !important}}</style>';
		echo "<p class='nl-noprint'><button class='nl-btn' type='button' onclick='window.print()'>Drucken</button> ";
		echo "<a class='nl-btn secondary' href='rkrk_newsletter.php?bereich=newsletter" . ($issueId !== '' ? '&issue=' . nl_h($issueId) : '') . "'>Zurück</a></p>";
		if ($issue && $issueId !== 'neu') {
			$printPerson = array('anredeArt' => 'Du', 'public' => true, 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
			echo nl_render_newsletter($issue, $printPerson, false);
		} else {
			echo "<p>Kein Newsletter.</p>";
		}
		echo "</main>\n";
		echo "<script>window.addEventListener('load', function(){ window.print(); });</script>\n";
		footer();
		exit;
	}
	echo "<section class='nl-card'>";
	echo "<details class='nl-fold' id='nl-issues' open><summary class='nl-fold-title'>Ausgaben</summary><div class='nl-fold-body'>";
	echo "<ul class='nl-issue-list'>";
	foreach ($issues as $one) {
		$when = nl_issue_ym($one);
		$current = ($issueId !== '' && $issueId !== 'neu' && (string)($one['id'] ?? '') === $issueId);
		$st = nl_issue_status($one);
		echo "<li" . ($current ? " class='is-current'" : "") . ">";
		echo "<a class='nl-issue-link' href='rkrk_newsletter.php?bereich=newsletter&issue=" . nl_h($one['id']) . "'>";
		echo "<span class='nl-issue-when'>" . nl_h($when) . "</span>";
		echo "<span class='nl-issue-name'>" . nl_h($one['title']) . "</span>";
		echo "</a>";
		echo "<form method='post' class='nl-issue-status'>";
		echo "<input type='hidden' name='action' value='save_publication'>";
		echo "<input type='hidden' name='open_issue' value='" . nl_h($issueId) . "'>";
		echo "<select name='status[" . nl_h($one['id']) . "]' class='is-" . nl_h($st) . "' aria-label='Status' onchange='this.form.submit()'>";
		foreach (array('draft' => 'Entwurf', 'published' => 'veröffentlicht', 'hidden' => 'verborgen') as $val => $lab) {
			echo "<option value='" . $val . "'" . ($st === $val ? " selected" : "") . ">" . $lab . "</option>";
		}
		echo "</select></form>";
		$delTitle = trim((string)($one['title'] ?? $one['id']));
		$delAsk = json_encode('Newsletter «' . $delTitle . '» vollständig löschen? Daten und Bilder werden entfernt.', JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo "<form method='post' class='nl-issue-del' onsubmit='return confirm(" . $delAsk . ")'>";
		echo "<input type='hidden' name='bereich' value='newsletter'><input type='hidden' name='action' value='delete_issue'>";
		echo "<input type='hidden' name='issue' value='" . nl_h($one['id']) . "'>";
		echo "<button type='submit' class='nl-issue-del-btn' title='Ausgabe löschen' aria-label='Ausgabe löschen'>" . nl_ico('trash') . "</button>";
		echo "</form>";
		echo "</li>";
	}
	echo "</ul>";
	echo "<p class='nl-note'>Status direkt in der Liste wählen. Entwurf und verborgen erscheinen nicht auf der öffentlichen Seite. <a href='rkrk_newsletter_public.php?MenuOn=off' target='_blank'>Öffentliche Seite öffnen</a></p>";
	echo "</div></details>";
	echo "<p><a class='nl-btn secondary' href='rkrk_newsletter.php?bereich=newsletter&issue=neu'>Neue Ausgabe</a></p></section>";
	echo "<script>(function(){var d=document.getElementById('nl-issues');if(!d)return;try{if(localStorage.getItem('nl-issues-open')==='0')d.open=false;}catch(e){}d.addEventListener('toggle',function(){try{localStorage.setItem('nl-issues-open',d.open?'1':'0');}catch(e){}});var cur=d.querySelector('.nl-issue-list .is-current');var list=d.querySelector('.nl-issue-list');if(cur&&list){var rel=cur.getBoundingClientRect().top-list.getBoundingClientRect().top;if(rel<0||rel+cur.offsetHeight>list.clientHeight)list.scrollTop+=rel;}})();</script>\n";
	if ($issueId === 'neu') {
		$issue = array(
			'id' => '',
			'title' => '',
			'subject' => '',
			'created' => date('Y-m-d'),
			'status' => 'draft',
			'published' => false,
			'sections' => array(nl_blank_section()),
			'bodyHtml' => '',
			'closingDu' => nl_default_closing_du(),
			'closingSie' => nl_default_closing_sie(),
			'footnote' => '',
		);
	}
	if ($issue) {
		if (!$preview && count($recipients) > 0) {
			$preview = $recipients[0];
		}
		if (!$preview) {
			$preview = array('id' => '', 'anredeArt' => 'Sie', 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
		}
		$previewArt = (string)($preview['anredeArt'] ?? 'Sie');
		$reviewRows = nl_review_publish(nl_newsletter_review_bundles($issue, $issues, $recipients));
		$reviewEmpty = (trim((string)($issue['id'] ?? '')) === '') ? 'Die Ausgabe zuerst speichern, dann erscheint der Link.' : 'Kein Absender vorhanden.';
		echo "<div class='nl-split'>";
		echo "<div class='nl-edit'>";
		echo "<section class='nl-card'><h2>Bearbeiten</h2>";
		echo "<p>Eine Fassung. Rechts siehst du die Mail für den gewählten Empfänger. Du und Sie wechseln mit der Anredeart.</p>";
		if ($issueId === 'neu') {
			echo "<form class='nl-form' method='post' id='nl-copy-form'>";
			echo "<input type='hidden' name='action' value='copy_issue'>";
			echo "<p class='nl-note'>Leer beginnen nutzt das Formular darunter. Aufbauen übernimmt eine bestehende Ausgabe in einen neuen Entwurf. Die Vorlage bleibt unverändert, es wird keine Mail versendet.</p>";
			echo "<label class='nl-check'><input type='radio' name='start' value='leer' checked> Leer beginnen</label>";
			echo "<label class='nl-check'><input type='radio' name='start' value='basis'> Auf bestehendem Newsletter aufbauen</label>";
			echo "<div id='nl-copy-basis' hidden>";
			echo "<label>Bestehende Ausgabe</label><select name='base' id='nl-copy-base'>";
			echo "<option value=''>Bitte wählen</option>";
			foreach ($issues as $one) {
				$basisYear = nl_page_issue_season_year($one);
				$basisLabel = trim((string)($one['title'] ?? ''));
				if ($basisLabel === '') {
					$basisLabel = trim((string)($one['subject'] ?? ''));
				}
				$basisText = (string)($one['id'] ?? '');
				if ($basisLabel !== '') {
					$basisText .= ' — ' . $basisLabel;
				}
				echo "<option value='" . nl_h($one['id']) . "' data-year='" . nl_h($basisYear) . "'>" . nl_h($basisText) . "</option>";
			}
			echo "</select>";
			echo "<label>Saison</label><input type='text' name='saison' id='nl-copy-year' inputmode='numeric' maxlength='4' value=''>";
			echo "<p class='nl-note'>1. April bis 31. Juli. Vorschlag aus der Vorlage, frei änderbar.</p>";
			echo "<label>Kennung der neuen Ausgabe</label><input type='text' name='id' value='' placeholder='2027-04'>";
			echo "<div class='nl-actions'><button type='submit' class='nl-btn secondary'>Entwurf anlegen</button></div>";
			echo "</div></form>";
			echo "<script>(function(){var form=document.getElementById('nl-copy-form');if(!form)return;var box=document.getElementById('nl-copy-basis');var radios=form.querySelectorAll('input[name=start]');var base=document.getElementById('nl-copy-base');var year=document.getElementById('nl-copy-year');function sync(){var on=false;for(var i=0;i<radios.length;i++){if(radios[i].checked&&radios[i].value==='basis')on=true;}box.hidden=!on;}function fillYear(){if(!base||!year)return;var opt=base.options[base.selectedIndex];var y=opt?(opt.getAttribute('data-year')||''):'';if(y)year.value=y;}for(var j=0;j<radios.length;j++){radios[j].addEventListener('change',sync);}if(base)base.addEventListener('change',fillYear);sync();})();</script>\n";
		}
		$sentOk = nl_page_issue_sent_ok($issue, $recipients);
		$previewSent = isset($sentOk[(string)($preview['id'] ?? '')]);
		echo "<form class='nl-form' method='get'>";
		echo "<input type='hidden' name='bereich' value='newsletter'><input type='hidden' name='issue' value='" . nl_h($issue['id']) . "'>";
		echo "<label>Ansicht für</label><select name='preview' class='nl-who" . ($previewSent ? " nl-sent-ok" : "") . "' onchange='this.form.submit()'>";
		foreach ($recipients as $person) {
			$sel = ((string)$person['id'] === (string)$preview['id']) ? " selected" : "";
			$okCls = isset($sentOk[(string)($person['id'] ?? '')]) ? " class='nl-sent-ok'" : "";
			echo "<option value='" . nl_h($person['id']) . "'" . $sel . $okCls . ">" . nl_h(trim($person['vorname'] . ' ' . $person['name'] . ' (' . $person['anredeArt'] . ')')) . "</option>";
		}
		echo "</select></form>";
		echo "<ul class='nl-who-list'>";
		foreach ($recipients as $person) {
			$who = trim((string)($person['vorname'] ?? '') . ' ' . (string)($person['name'] ?? ''));
			if ($who === '') {
				$who = trim((string)($person['email'] ?? ''));
			}
			$whoCls = isset($sentOk[(string)($person['id'] ?? '')]) ? " class='nl-sent-ok'" : "";
			$whoTitle = trim((string)($person['status'] ?? ''));
			echo "<li" . $whoCls . ($whoTitle !== '' ? " title='" . nl_h($whoTitle) . "'" : "") . ">" . nl_h($who !== '' ? $who : (string)($person['id'] ?? '')) . "</li>";
		}
		echo "</ul>";
		echo "<form class='nl-form' method='post' enctype='multipart/form-data' id='nl-edit-form'>";
		echo "<input type='hidden' name='action' value='save_issue'>";
		echo "<label>Kennung</label><input type='text' name='id' value='" . nl_h($issue['id']) . "'>";
		echo "<label>Titel</label><input type='text' name='title' value='" . nl_h($issue['title']) . "'>";
		echo "<label>Betreff</label><input type='text' name='subject' id='nl-subject' value='" . nl_h($issue['subject']) . "'>";
		echo "<label>Datum</label><input type='text' name='created' value='" . nl_h($issue['created']) . "'>";
		echo "<label>Einleitung</label><textarea name='intro' id='nl-intro'>" . nl_h($issue['intro'] ?? '') . "</textarea>";
		echo "<div class='nl-signet'><label>Signet</label>";
		echo "<p class='nl-note'>Einmal pro Ausgabe, oben rechts neben der Anrede. Kann ersetzt oder entfernt werden.</p>";
		echo "<input type='hidden' name='headerImage' value='" . nl_h($issue['headerImage'] ?? '') . "'>";
		if (nl_media_src($issue['headerImage'] ?? '') !== '') {
			echo "<img class='nl-sec-preview nl-header-preview' src='" . nl_h(nl_media_src($issue['headerImage'])) . "' alt=''>";
		}
		echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary' id='nl-signet-show'>Signet ändern</button>";
		if (trim((string)($issue['headerImage'] ?? '')) !== '') {
			echo "<button type='button' class='nl-icon-btn nl-icon-danger' id='nl-signet-del' title='Signet entfernen' aria-label='Signet entfernen'>" . nl_ico('trash') . "</button>";
		}
		echo "<input type='file' name='header_file' id='nl-signet-file' accept='image/*' hidden></div></div>";
		$resultateOn = nl_issue_resultate_on($issue);
		echo "<label class='nl-check'><input type='checkbox' name='resultate' id='nl-resultate' value='1'" . ($resultateOn ? " checked" : "") . "> Resultate</label>";
		$resultsSource = nl_issue_results_source($issue);
		echo "<label>Resultate</label><select name='resultsSource' id='nl-results-source'>";
		echo "<option value='image'" . ($resultsSource === 'image' ? " selected" : "") . ">Resultatbild laden</option>";
		echo "<option value='statistik'" . ($resultsSource === 'statistik' ? " selected" : "") . ">Direkt aus der Flugstatistik</option>";
		echo "</select>";
		echo nl_season_year_select($issue);
		echo "<p class='nl-note' id='nl-results-stat-note'" . ($resultsSource === 'image' ? " hidden" : "") . ">Einsatztage, Felder, Funde und Fläche sowie die beiden Verläufe der letzten fünf Jahre kommen aus der Flugstatistik. Die Saison läuft vom 1. April bis zum 31. Juli.</p>";
		echo "<div id='nl-results-image-box'" . ($resultsSource === 'statistik' ? " hidden" : "") . ">";
		echo "<label>Resultatbild</label><div class='nl-file-row'><button type='button' class='nl-btn secondary nl-file-show'>Bild ändern</button>";
		if (trim((string)($issue['resultsImage'] ?? '')) !== '') {
			echo "<button type='button' class='nl-icon-btn nl-icon-danger' id='nl-results-del' title='Bild entfernen' aria-label='Bild entfernen'>" . nl_ico('trash') . "</button>";
		}
		echo "<input class='nl-file-input' type='file' name='results_file' accept='image/*' hidden></div>";
		echo "<input type='hidden' name='resultsImage' value='" . nl_h($issue['resultsImage'] ?? '') . "'>";
		if (nl_media_src($issue['resultsImage'] ?? '') !== '') {
			echo "<img class='nl-sec-preview nl-results-preview' src='" . nl_h(nl_media_src($issue['resultsImage'])) . "' alt=''>";
		}
		echo "</div>";
		echo "<label>Resultate, Beschriftung</label><input type='text' name='resultsCaption' id='nl-results-cap' value='" . nl_h($issue['resultsCaption'] ?? '') . "'>";
		echo "<template id='nl-stats-tpl'>" . nl_team_results_html($issue) . "</template>";
		echo "<label>Diskussion der Resultate</label><textarea name='discussion' id='nl-discussion'>" . nl_h($issue['discussion'] ?? '') . "</textarea>";
		$sections = nl_issue_sections($issue);
		$issue['sections'] = $sections;
		echo "<div class='nl-sec-list' id='nl-sections'>";
		foreach ($sections as $section) {
			$secLabel = trim((string)($section['title'] ?? ''));
			if ($secLabel === '') {
				$secLabel = 'Sektion';
			}
			echo "<article class='nl-sec is-collapsed'>";
			echo "<div class='nl-sec-head'><span class='nl-sec-handle' draggable='true' title='Verschieben'>⋮⋮</span>";
			echo "<button type='button' class='nl-sec-toggle' aria-expanded='false'>" . nl_h($secLabel) . "</button>";
			echo "<button type='button' class='nl-sec-del nl-icon-btn' title='Sektion löschen' aria-label='Sektion löschen'>" . nl_ico('trash') . "</button></div>";
			echo "<div class='nl-sec-body'>";
			echo "<input type='hidden' name='sec_id[]' value='" . nl_h($section['id']) . "'>";
			$showTitle = !array_key_exists('showTitle', $section) || !empty($section['showTitle']);
			echo "<label>Titel</label><input type='text' name='sec_title[]' value='" . nl_h($section['title']) . "'>";
			echo "<label class='nl-check'><input type='checkbox' class='nl-show-title-box'" . ($showTitle ? " checked" : "") . "> Titel anzeigen</label>";
			echo "<input type='hidden' class='nl-show-title' name='sec_show[]' value='" . ($showTitle ? "1" : "0") . "'>";
			echo "<p class='nl-note'>Ohne Häkchen bleibt der Titel nur in der Bearbeitung sichtbar.</p>";
			echo "<label>Text</label><textarea name='sec_text[]'>" . nl_h($section['text']) . "</textarea>";
			echo "<p class='nl-note'>HTML wie &lt;strong&gt; und &lt;br&gt; wird in Ansicht, Mail, PDF und Druck übernommen.</p>";
			$orderedPics = array();
			$mainRel = trim((string)($section['image'] ?? ''));
			if ($mainRel !== '') {
				$orderedPics[] = $mainRel;
			}
			if (isset($section['images']) && is_array($section['images'])) {
				foreach ($section['images'] as $extraImg) {
					$extraImg = trim((string)$extraImg);
					if ($extraImg !== '' && !in_array($extraImg, $orderedPics, true)) {
						$orderedPics[] = $extraImg;
					}
				}
			}
			$leadRel = count($orderedPics) ? $orderedPics[0] : '';
			echo "<label>Bild</label><input type='hidden' name='sec_image[]' value='" . nl_h($leadRel) . "'>";
			echo "<label>Bildbeschriftung</label><input type='text' name='sec_caption[]' value='" . nl_h($section['caption'] ?? '') . "'>";
			echo "<input type='hidden' name='sec_extra[]' value='" . nl_h(json_encode(isset($section['images']) && is_array($section['images']) ? $section['images'] : array(), JSON_UNESCAPED_UNICODE)) . "'>";
			$cropJson = '';
			if (isset($section['crop']) && is_array($section['crop'])) {
				$cropJson = json_encode(nl_crop_box($section['crop']));
			}
			echo "<input type='hidden' name='sec_crop[]' value='" . nl_h($cropJson) . "'>";
			echo "<div class='nl-pic-list'>";
			foreach ($orderedPics as $picIndex => $picRel) {
				$picSrc = nl_media_src($picRel);
				$isMain = ($picIndex === 0);
				echo "<div class='nl-pic" . ($isMain ? " is-main" : "") . "'" . ($isMain ? " data-crop='" . nl_h($cropJson) . "'" : "") . ">";
				echo "<span class='nl-pic-handle' title='Ziehen zum Sortieren'>⋮⋮</span>";
				echo "<div class='nl-img-mask'>";
				if ($picSrc !== '') {
					echo "<img class='nl-pic-preview" . ($isMain ? " nl-sec-preview" : "") . "' src='" . nl_h($picSrc) . "' alt=''>";
				}
				echo "</div>";
				if ($isMain) {
					echo "<input class='nl-pic-path' type='hidden' value='" . nl_h($picRel) . "'>";
					echo "<input class='nl-pic-file nl-file-input' type='file' name='sec_file[]' accept='image/*' hidden>";
				} else {
					echo "<input class='nl-pic-path' type='hidden' name='sec_more_keep[" . nl_h($section['id']) . "][]' value='" . nl_h($picRel) . "'>";
					echo "<input class='nl-pic-file' type='file' name='sec_more[" . nl_h($section['id']) . "][]' accept='image/*' hidden>";
				}
				echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-pic-load'>" . ($picSrc !== '' ? "Bild ändern" : "Bild laden") . "</button>";
				echo "<button type='button' class='nl-icon-btn nl-icon-danger nl-pic-del' title='Bild entfernen' aria-label='Bild entfernen'>" . nl_ico('trash') . "</button>";
				if ($isMain && $picSrc !== '') {
					echo "<button type='button' class='nl-btn secondary nl-crop-show'>Ausschnitt wählen</button>";
				}
				echo "</div></div>";
			}
			echo "</div>";
			if (count($orderedPics) === 0) {
				echo "<input class='nl-pic-spare-file' type='file' name='sec_file[]' hidden>";
			}
			echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-more-show'>Weiteres Bild</button></div>";
			echo "</div></article>";
		}
		echo "</div>";
		echo "<p><button type='button' class='nl-btn secondary' id='nl-sec-add'>Sektion hinzufügen</button></p>";
		echo "<label>Schluss Du</label><textarea class='nl-du-field' name='closingDu' id='nl-close-du'>" . nl_h($issue['closingDu']) . "</textarea>";
		echo "<label>Schluss Sie</label><textarea class='nl-sie-field' name='closingSie' id='nl-close-sie'>" . nl_h($issue['closingSie']) . "</textarea>";
		echo "<label>Fussnote</label><textarea name='footnote' id='nl-footnote'>" . nl_h($issue['footnote'] ?? '') . "</textarea>";
		echo "<p class='nl-note'>Steht unter dem Schluss, auch in der öffentlichen Du-Fassung. Website-, TWINT- und Swiss-QR (wie auf der Partnerseite) sowie Member-Logo werden automatisch angehängt.</p>";
		$stEdit = nl_issue_status($issue);
		echo "<label>Status</label><select name='status'>";
		foreach (array('draft' => 'Entwurf', 'published' => 'veröffentlicht', 'hidden' => 'verborgen') as $val => $lab) {
			echo "<option value='" . $val . "'" . ($stEdit === $val ? " selected" : "") . ">" . $lab . "</option>";
		}
		echo "</select>";
		echo "<p class='nl-note'>Entwurf und verborgen erscheinen nicht auf der öffentlichen Seite. Nur veröffentlichte Ausgaben sind dort sichtbar.</p>";
		echo "<div class='nl-actions'><button type='submit'>Speichern</button>";
		if (trim((string)($issue['id'] ?? '')) !== '') {
			$pdfHref = 'rkrk_newsletter.php?bereich=newsletter&issue=' . rawurlencode((string)$issue['id']) . '&pdf=1';
			$druckHref = 'rkrk_newsletter.php?bereich=newsletter&issue=' . rawurlencode((string)$issue['id']) . '&druck=1';
			echo "<a class='nl-btn' href='" . nl_h($pdfHref) . "'>" . nl_ico('pdf') . " PDF</a>";
			echo "<a class='nl-btn secondary' href='" . nl_h($druckHref) . "' target='_blank'>" . nl_ico('print') . " Druckansicht</a>";
		}
		echo "</div></form>";
		if (trim((string)($issue['id'] ?? '')) !== '') {
			$delTitle = trim((string)($issue['title'] ?? $issue['id']));
			$delAsk = json_encode('Newsletter «' . $delTitle . '» vollständig löschen? Daten und Bilder werden entfernt.', JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
			echo "<form method='post' class='nl-actions' onsubmit='return confirm(" . $delAsk . ")'>";
			echo "<input type='hidden' name='bereich' value='newsletter'><input type='hidden' name='action' value='delete_issue'>";
			echo "<input type='hidden' name='issue' value='" . nl_h($issue['id']) . "'>";
			echo "<button type='submit' class='nl-icon-btn nl-btn-danger' title='Ausgabe löschen' aria-label='Ausgabe löschen'>" . nl_ico('trash') . "</button>";
			echo "</form>";
		}
		echo "</section>";
		echo "</div>";
		echo "<aside class='nl-view' id='nl-pv-root' data-art='" . nl_h($previewArt) . "'>";
		echo "<h2>Ansicht</h2>";
		echo "<p class='nl-note'>Betreff: <span id='nl-pv-subject'>" . nl_h($issue['subject']) . "</span></p>";
		echo nl_render_newsletter($issue, $preview, true);
		echo "</aside></div>";
		echo "<script src='rkrk_newsletter_crop.js?v=20260925open'></script>\n";
		echo "<script>
		(function(){
			var art = document.getElementById('nl-pv-root').getAttribute('data-art') || 'Sie';
			var list = document.getElementById('nl-sections');
			function esc(s){return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\\n/g,'<br>');}
			function cropMarkup(src, raw, nw, nh){
				var safe = String(src || '').replace(/\"/g, '');
				var img = '<img src=\"' + safe + '\" alt=\"\">';
				var c = null;
				try { c = JSON.parse(raw || ''); } catch (e) { c = null; }
				if (!c || !(c.w > 0) || !(c.h > 0) || !nw || !nh) return img;
				if (c.x < 0.5 && c.y < 0.5 && c.w > 99.5 && c.h > 99.5) return img;
				var fw = nw * c.w / 100, fh = nh * c.h / 100;
				if (fw < 1 || fh < 1) return img;
				var pad = (fh / fw * 100).toFixed(3);
				var imgW = (100 / (c.w / 100)).toFixed(3);
				var imgH = (100 / (c.h / 100)).toFixed(3);
				var left = (-c.x / c.w * 100).toFixed(3);
				var top = (-c.y / c.h * 100).toFixed(3);
				return '<span class=\"nl-crop-view\" style=\"padding-bottom:' + pad + '%\"><img src=\"' + safe + '\" alt=\"\" style=\"position:absolute;width:' + imgW + '%;height:' + imgH + '%;left:' + left + '%;top:' + top + '%\"></span>';
			}
			function linkSite(s){
				return String(s == null ? '' : s).replace(/(<a\b[^>]*>[\s\S]*?<\/a>|<[^>]+>)|(?:^|[^\\w@./])((?:www\\.)?drohnenrehkitzrettung\\.ch)(?![\\w./@-])/gi, function(m, tag){
					if (tag) return tag;
					return m.charAt(0) === 'd' || m.charAt(0) === 'w' || m.charAt(0) === 'D' || m.charAt(0) === 'W' ? '<a href=\"https://www.drohnenrehkitzrettung.ch\">drohnenrehkitzrettung.ch</a>' : (m.charAt(0) + '<a href=\"https://www.drohnenrehkitzrettung.ch\">drohnenrehkitzrettung.ch</a>');
				});
			}
			function asHtml(s){s = String(s == null ? '' : s); if (s.indexOf('<') < 0) return linkSite(esc(s)); s = s.split('<' + '/script>').join('').split('<' + '/style>').join(''); s = s.replace(/<script\\b[^>]*>/gi,'').replace(/<style\\b[^>]*>/gi,'').replace(/\\son\\w+=/gi,' data-x=').replace(/javascript:/gi,''); return linkSite(s);}
			function paint(){
				var b = document.getElementById('nl-pv-body');
				var html = '';
				var himg = document.querySelector('.nl-header-preview');
				var logo = document.getElementById('nl-pv-logo');
				if (logo) {
					if (himg) {
						logo.hidden = false;
						logo.innerHTML = '<img src=\"' + himg.getAttribute('src') + '\" alt=\"\">';
					} else {
						logo.hidden = true;
						logo.innerHTML = '';
					}
				}
				var intro = document.getElementById('nl-intro');
				var disc = document.getElementById('nl-discussion');
				var rcap = document.getElementById('nl-results-cap');
				var rimg = document.querySelector('.nl-results-preview');
				if (intro && intro.value.trim()) html += '<div class=\"nl-intro-text\">' + (intro.value.indexOf('<') >= 0 ? asHtml(intro.value) : ('<p>' + esc(intro.value.trim()) + '</p>')) + '</div>';
				var resultateBox = document.getElementById('nl-resultate');
				var showResults = !resultateBox || resultateBox.checked;
				var srcSel = document.getElementById('nl-results-source');
				if (showResults && srcSel && srcSel.value === 'statistik') {
					var tpl = document.getElementById('nl-stats-tpl');
					if (tpl) html += tpl.innerHTML;
				} else if (showResults && rimg) {
					html += '<figure class=\"nl-results\"><img src=\"' + rimg.getAttribute('src') + '\" alt=\"\">';
					if (rcap && rcap.value.trim()) html += '<figcaption>' + esc(rcap.value.trim()) + '</figcaption>';
					html += '</figure>';
				}
				if (showResults && disc && disc.value.trim()) html += '<div class=\"nl-discussion\">' + (disc.value.indexOf('<') >= 0 ? asHtml(disc.value) : ('<p>' + esc(disc.value.trim()) + '</p>')) + '</div>';
				if (list) {
					var cards = list.querySelectorAll('.nl-sec');
					for (var i = 0; i < cards.length; i++) {
						var title = cards[i].querySelector('[name=\"sec_title[]\"]');
						var text = cards[i].querySelector('[name=\"sec_text[]\"]');
						var cap = cards[i].querySelector('[name=\"sec_caption[]\"]');
						var previews = cards[i].querySelectorAll('.nl-pic-preview');
						var cropEl = cards[i].querySelector('[name=\"sec_crop[]\"]');
						var t = title ? title.value.trim() : '';
						var x = text ? text.value.trim() : '';
						var c = cap ? cap.value.trim() : '';
						var cropRaw = cropEl ? cropEl.value : '';
						var showEl = cards[i].querySelector('.nl-show-title');
						var showTitle = !showEl || showEl.value !== '0';
						if ((!t || !showTitle) && !x && !previews.length) continue;
						html += '<article class=\"nl-mail-sec' + (previews.length && !x ? ' nl-sec-full' : '') + '\">';
						if (t && showTitle) html += '<h2>' + esc(t) + '</h2>';
						var pics = [];
						previews.forEach(function(im, idx){
							var ms = im.getAttribute('src');
							if (!ms) return;
							if (idx === 0) pics.push('<figure>' + cropMarkup(ms, cropRaw, im.naturalWidth || 0, im.naturalHeight || 0) + '</figure>');
							else pics.push('<figure><img src=\"' + ms + '\" alt=\"\"></figure>');
						});
						if (pics.length && x) html += '<div class=\"nl-sec-pics\">';
						html += pics.join('');
						if (pics.length && c) html += '<figcaption>' + (c.indexOf('<') >= 0 ? asHtml(c) : esc(c)) + '</figcaption>';
						if (pics.length && x) html += '</div>';
						if (x) html += (x.indexOf('<') >= 0 ? asHtml(x) : ('<p>' + esc(x) + '</p>'));
						html += '</article>';
					}
				}
				if (b) b.innerHTML = linkSite(html);
				var sub = document.getElementById('nl-subject');
				var du = document.getElementById('nl-close-du');
				var sie = document.getElementById('nl-close-sie');
				var c = document.getElementById('nl-pv-closing');
				var ps = document.getElementById('nl-pv-subject');
				if (c && du && sie) {
					var closeTxt = art === 'Du' ? du.value : sie.value;
					var keys = ['Weiter Infos auf unserer Website','Weiter Infos auf unserer Webseite','Möchtest du uns gerne unterstützen','Möchten Sie uns gerne unterstützen','Member of'];
					var cut = -1;
					for (var ki = 0; ki < keys.length; ki++) {
						var p = closeTxt.toLowerCase().indexOf(keys[ki].toLowerCase());
						if (p >= 0 && (cut < 0 || p < cut)) cut = p;
					}
					if (cut >= 0) closeTxt = closeTxt.slice(0, cut).replace(/\s+$/,'');
					c.innerHTML = asHtml(closeTxt);
				}
				if (ps && sub) ps.textContent = sub.value;
				var fn = document.getElementById('nl-footnote');
				var pfn = document.getElementById('nl-pv-footnote');
				if (pfn && fn) {
					var figs = pfn.querySelectorAll('figure');
					var figHtml = '';
					for (var fi = 0; fi < figs.length; fi++) figHtml += figs[fi].outerHTML;
					pfn.innerHTML = asHtml(fn.value) + figHtml;
				}
			}
			function ensureSpare(card){
				var hasMainFile = card.querySelector('.nl-pic-file[name=\"sec_file[]\"]');
				var spare = card.querySelector('.nl-pic-spare-file');
				if (hasMainFile) {
					if (spare) spare.remove();
					return;
				}
				if (!spare) {
					spare = document.createElement('input');
					spare.type = 'file';
					spare.name = 'sec_file[]';
					spare.className = 'nl-pic-spare-file';
					spare.hidden = true;
					var slots = card.querySelector('.nl-pic-list');
					if (slots && slots.parentNode) slots.parentNode.insertBefore(spare, slots.nextSibling);
				}
			}
			function unwrapCrop(slot){
				var frame = slot.querySelector('.nl-sec-preview-frame');
				var img = slot.querySelector('.nl-pic-preview');
				if (!frame || !img) return;
				img.removeAttribute('style');
				frame.parentNode.insertBefore(img, frame);
				frame.remove();
			}
			function syncPics(card){
				var idEl = card.querySelector('[name=\"sec_id[]\"]');
				var id = idEl && idEl.value ? idEl.value : 's';
				var cropEl = card.querySelector('[name=\"sec_crop[]\"]');
				var mainHidden = card.querySelector('[name=\"sec_image[]\"]');
				var slots = card.querySelectorAll('.nl-pic');
				slots.forEach(function(slot, i){
					var path = slot.querySelector('.nl-pic-path');
					var file = slot.querySelector('.nl-pic-file');
					var prev = slot.querySelector('.nl-pic-preview');
					var main = i === 0;
					slot.classList.toggle('is-main', main);
					if (prev) prev.classList.toggle('nl-sec-preview', main);
					if (!main) unwrapCrop(slot);
					if (main) {
						if (path) path.removeAttribute('name');
						if (mainHidden) mainHidden.value = path ? (path.value || '') : '';
						if (file) {
							file.name = 'sec_file[]';
							file.classList.add('nl-file-input');
						}
						if (cropEl) cropEl.value = slot.getAttribute('data-crop') || '';
					} else {
						if (path) path.name = 'sec_more_keep[' + id + '][]';
						if (file) {
							file.name = 'sec_more[' + id + '][]';
							file.classList.remove('nl-file-input');
						}
					}
					var cropBtn = slot.querySelector('.nl-crop-show');
					if (main && prev) {
						if (!cropBtn) {
							cropBtn = document.createElement('button');
							cropBtn.type = 'button';
							cropBtn.className = 'nl-btn secondary nl-crop-show';
							cropBtn.textContent = 'Ausschnitt wählen';
							var row = slot.querySelector('.nl-file-row');
							if (row) row.appendChild(cropBtn);
							cropBtn.addEventListener('click', function(){
								if (window.nlOpenCrop) window.nlOpenCrop(card);
							});
						}
					} else if (cropBtn) {
						cropBtn.remove();
					}
				});
				ensureSpare(card);
				if (window.nlApplySectionCrop) window.nlApplySectionCrop(card);
			}
			function bindPic(box, card){
				var load = box.querySelector('.nl-pic-load');
				var file = box.querySelector('.nl-pic-file');
				var del = box.querySelector('.nl-pic-del');
				var handle = box.querySelector('.nl-pic-handle');
				if (load && file) load.addEventListener('click', function(){ file.click(); });
				if (file) file.addEventListener('change', function(){
					if (!file.files || !file.files[0]) return;
					var mask = box.querySelector('.nl-img-mask');
					var prev = box.querySelector('.nl-pic-preview');
					if (!prev) {
						prev = document.createElement('img');
						prev.className = 'nl-pic-preview';
						prev.alt = '';
						if (mask) mask.appendChild(prev);
					}
					prev.src = URL.createObjectURL(file.files[0]);
					var path = box.querySelector('.nl-pic-path');
					if (path) path.value = '';
					box.setAttribute('data-crop', '');
					box.classList.remove('is-empty');
					if (load) load.textContent = 'Bild ändern';
					syncPics(card);
					paint();
				});
				if (del) del.addEventListener('click', function(){
					box.remove();
					syncPics(card);
					paint();
				});
				if (handle) handle.addEventListener('mousedown', function(){ box.draggable = true; });
				box.addEventListener('dragstart', function(ev){
					if (!box.draggable) {
						ev.preventDefault();
						return;
					}
					var first = card.querySelector('.nl-pic');
					var cropEl = card.querySelector('[name=\"sec_crop[]\"]');
					if (first && cropEl) first.setAttribute('data-crop', cropEl.value || '');
					box.classList.add('is-drag');
					ev.dataTransfer.effectAllowed = 'move';
					ev.dataTransfer.setData('text/plain', 'pic');
					ev.stopPropagation();
					card._picDrag = box;
				});
				box.addEventListener('dragend', function(){
					box.classList.remove('is-drag');
					box.draggable = false;
					card._picDrag = null;
					syncPics(card);
					paint();
				});
			}
			function picSlot(card){
				var box = document.createElement('div');
				box.className = 'nl-pic is-empty';
				box.innerHTML = '<span class=\"nl-pic-handle\" title=\"Ziehen zum Sortieren\">⋮⋮</span>'
					+ '<div class=\"nl-img-mask\"></div>'
					+ '<input class=\"nl-pic-path\" type=\"hidden\" value=\"\">'
					+ '<input class=\"nl-pic-file\" type=\"file\" accept=\"image/*\" hidden>'
					+ '<div class=\"nl-file-row\"><button type=\"button\" class=\"nl-btn secondary nl-pic-load\">Bild laden</button>'
					+ '<button type=\"button\" class=\"nl-icon-btn nl-icon-danger nl-pic-del\" title=\"Bild entfernen\" aria-label=\"Bild entfernen\"><svg class=\"nl-ico\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" d=\"M4 7h16M9 7V5h6v2M8 7l1 13h6l1-13\"/></svg></button></div>';
				bindPic(box, card);
				return box;
			}
			function clearPics(card){
				card.querySelectorAll('.nl-pic').forEach(function(n){ n.remove(); });
				var hidden = card.querySelector('[name=\"sec_image[]\"]');
				if (hidden) hidden.value = '';
				var cropEl = card.querySelector('[name=\"sec_crop[]\"]');
				if (cropEl) cropEl.value = '';
				ensureSpare(card);
			}
			function bindCard(card){
				card.addEventListener('input', paint);
				var tog = card.querySelector('.nl-sec-toggle');
				if (tog) tog.addEventListener('click', function(){
					var closed = card.classList.toggle('is-collapsed');
					tog.setAttribute('aria-expanded', closed ? 'false' : 'true');
				});
				var titleIn = card.querySelector('[name=\"sec_title[]\"]');
				if (titleIn && tog) titleIn.addEventListener('input', function(){ tog.textContent = titleIn.value.trim() || 'Sektion'; });
				var showBox = card.querySelector('.nl-show-title-box');
				var showHid = card.querySelector('.nl-show-title');
				if (showBox && showHid) showBox.addEventListener('change', function(){ showHid.value = showBox.checked ? '1' : '0'; paint(); });
				var moreBtn = card.querySelector('.nl-more-show');
				if (moreBtn) moreBtn.addEventListener('click', function(){
					var slots = card.querySelector('.nl-pic-list');
					if (slots) slots.appendChild(picSlot(card));
					syncPics(card);
				});
				card.querySelectorAll('.nl-pic').forEach(function(box){ bindPic(box, card); });
				var picList = card.querySelector('.nl-pic-list');
				if (picList) picList.addEventListener('dragover', function(ev){
					if (!card._picDrag) return;
					ev.preventDefault();
					ev.stopPropagation();
					var node = ev.target && ev.target.closest ? ev.target : null;
					var over = node && node.closest ? node.closest('.nl-pic') : null;
					if (!over || over === card._picDrag || over.parentNode !== picList) return;
					var box = over.getBoundingClientRect();
					var dx = ev.clientX - (box.left + box.width / 2);
					var dy = ev.clientY - (box.top + box.height / 2);
					var after = Math.abs(dx) > Math.abs(dy) ? dx > 0 : dy > 0;
					picList.insertBefore(card._picDrag, after ? over.nextSibling : over);
				});
				card.querySelectorAll('.nl-crop-show').forEach(function(btn){
					btn.addEventListener('click', function(){
						if (window.nlOpenCrop) window.nlOpenCrop(card);
					});
				});
				var del = card.querySelector('.nl-sec-del');
				if (del) del.addEventListener('click', function(){
					if (list.querySelectorAll('.nl-sec').length < 2) {
						card.querySelectorAll('input[type=text], textarea').forEach(function(el){ el.value = ''; });
						clearPics(card);
						paint();
						return;
					}
					card.remove();
					paint();
				});
				var handle = card.querySelector('.nl-sec-handle');
				if (handle) {
					handle.addEventListener('dragstart', function(ev){
						card.classList.add('is-drag');
						ev.dataTransfer.effectAllowed = 'move';
						ev.dataTransfer.setData('text/plain', 'sec');
						list._drag = card;
					});
					handle.addEventListener('dragend', function(){
						card.classList.remove('is-drag');
						list._drag = null;
						paint();
					});
				}
			}
			if (list) {
				list.querySelectorAll('.nl-sec').forEach(bindCard);
				list.addEventListener('dragover', function(ev){
					if (!list._drag) return;
					ev.preventDefault();
					var node = ev.target && ev.target.closest ? ev.target : (ev.target && ev.target.parentElement);
					var over = node && node.closest ? node.closest('.nl-sec') : null;
					if (!over || over === list._drag) return;
					var box = over.getBoundingClientRect();
					var after = ev.clientY > box.top + box.height / 2;
					list.insertBefore(list._drag, after ? over.nextSibling : over);
				});
			}
			var add = document.getElementById('nl-sec-add');
			if (add && list) add.addEventListener('click', function(){
				var first = list.querySelector('.nl-sec');
				if (!first) return;
				var card = first.cloneNode(true);
				card.querySelectorAll('input[type=text], textarea, input[type=hidden]').forEach(function(el){ if (el.classList.contains('nl-show-title')) return; el.value = ''; });
				var showBoxNew = card.querySelector('.nl-show-title-box');
				var showHidNew = card.querySelector('.nl-show-title');
				if (showBoxNew && showHidNew) showHidNew.value = showBoxNew.checked ? '1' : '0';
				card.querySelectorAll('input[type=file]').forEach(function(el){ el.value = ''; });
				card.querySelectorAll('.nl-pic').forEach(function(n){ n.remove(); });
				ensureSpare(card);
				var id = card.querySelector('[name=\"sec_id[]\"]');
				if (id) id.value = 's' + Date.now();
				var moreIn = card.querySelector('.nl-more-input');
				if (moreIn) {
					moreIn.value = '';
					moreIn.hidden = true;
					if (id) moreIn.name = 'sec_more[' + id.value + '][]';
				}
				var moreBtnNew = card.querySelector('.nl-more-show');
				if (moreBtnNew) moreBtnNew.hidden = false;
				list.appendChild(card);
				bindCard(card);
				paint();
			});
			var resultateBox = document.getElementById('nl-resultate');
			if (resultateBox) resultateBox.addEventListener('change', paint);
			var srcSel = document.getElementById('nl-results-source');
			if (srcSel) srcSel.addEventListener('change', function(){
				var stat = srcSel.value === 'statistik';
				var box = document.getElementById('nl-results-image-box');
				var note = document.getElementById('nl-results-stat-note');
				if (box) box.hidden = stat;
				if (note) note.hidden = !stat;
				paint();
			});
			['nl-close-du','nl-close-sie','nl-subject','nl-footnote','nl-intro','nl-discussion','nl-results-cap'].forEach(function(id){
				var el = document.getElementById(id);
				if (el) el.addEventListener('input', paint);
			});
			var signetBtn = document.getElementById('nl-signet-show');
			if (signetBtn) signetBtn.addEventListener('click', function(){
				var f = document.getElementById('nl-signet-file');
				if (f) f.click();
			});
			var signetDel = document.getElementById('nl-signet-del');
			if (signetDel) signetDel.addEventListener('click', function(){
				var hidden = document.querySelector('[name=\"headerImage\"]');
				if (hidden) hidden.value = '';
				var preview = document.querySelector('.nl-header-preview');
				if (preview) preview.remove();
				signetDel.remove();
				paint();
			});
			var resBtn = document.querySelector('#nl-results-image-box .nl-file-show');
			if (resBtn) resBtn.addEventListener('click', function(){
				var f = document.querySelector('#nl-results-image-box .nl-file-input');
				if (f) f.click();
			});
			var resDel = document.getElementById('nl-results-del');
			if (resDel) resDel.addEventListener('click', function(){
				var hidden = document.querySelector('[name=\"resultsImage\"]');
				if (hidden) hidden.value = '';
				var preview = document.querySelector('.nl-results-preview');
				if (preview) preview.remove();
				resDel.remove();
				paint();
			});
			window.nlRepaint = paint;
			paint();
		})();
		</script>";
		echo "<section class='nl-card nl-noprint'>";
		nl_echo_review_fold($reviewRows, $reviewEmpty);
		echo "</section>";
		echo "<section class='nl-card'><details class='nl-fold' id='nl-versand'><summary class='nl-fold-title'>Versand</summary><div class='nl-fold-body'>";
		$nlSendMarked = 0;
		$nlSendHtml = '';
		foreach ($recipients as $person) {
			if (!is_array($person)) {
				continue;
			}
			$sendId = (string)($person['id'] ?? '');
			$sendEmail = trim((string)($person['email'] ?? ''));
			if ($sendId === '' || !filter_var($sendEmail, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			$sendAus = !empty($person['ausloesen']) ? '1' : '0';
			$sendWho = trim((string)($person['vorname'] ?? '') . ' ' . (string)($person['name'] ?? ''));
			if ($sendWho === '') {
				$sendWho = $sendEmail;
			}
			if ($sendAus === '1') {
				$nlSendMarked++;
			}
			$nlSendHtml .= "<label class='nl-check nl-send-row'" . ($sendAus === '1' ? '' : ' hidden') . ">";
			$nlSendHtml .= "<input type='checkbox' class='nl-send-to' name='send_to[]' value='" . nl_h($sendId) . "' data-gruppe='" . nl_h((string)($person['gruppe'] ?? '')) . "' data-art='" . nl_h((string)($person['anredeArt'] ?? '')) . "' data-aus='" . $sendAus . "'" . ($sendAus === '1' ? ' checked' : ' disabled') . "> ";
			$nlSendHtml .= nl_h($sendWho) . "</label>";
		}
		echo "<form class='nl-form' method='post' id='nl-send-form'>";
		echo "<input type='hidden' name='action' value='send_newsletter'><input type='hidden' name='issue' value='" . nl_h($issue['id']) . "'>";
		echo "<label>Gruppe</label><select name='filterGruppe'><option value=''>alle</option>";
		foreach (nl_groups($recipients) as $group) {
			echo "<option>" . nl_h($group) . "</option>";
		}
		echo "</select>";
		echo "<label>Anrede</label><select name='filterArt'><option value=''>Du und Sie</option><option>Du</option><option>Sie</option></select>";
		echo "<label><input type='checkbox' name='onlyAusloesen' value='1' checked> Nur Zeilen mit Auslösen</label>";
		echo "<label class='nl-check'><input type='checkbox' class='nl-send-all'" . ($nlSendMarked > 0 ? ' checked' : '') . "> Alle</label>";
		echo "<div class='nl-send-list' id='nl-send-list'>" . $nlSendHtml . "</div>";
		echo "<label><input type='checkbox' name='confirm' value='1'> Versand an die gefilterten, ausgelösten Adressen bestätigen</label>";
		echo "<div class='nl-actions'><button type='submit' class='nl-send-btn'" . ($nlSendMarked > 0 ? '' : ' disabled') . ">Mails senden</button>";
		echo "<span class='nl-send-count' id='nl-send-count' aria-live='polite'>" . (int)$nlSendMarked . " Empfänger markiert</span></div>";
		echo "<p>Pro Klick höchstens 20 Empfänger. Gesendete Zeilen verlieren die Auslösen-Markierung.</p>";
		echo "</form>";
		echo "<script>(function(){var form=document.getElementById(\"nl-send-form\");if(!form)return;var boxes=form.querySelectorAll(\"input.nl-send-to\");var all=form.querySelector(\"input.nl-send-all\");var countEl=document.getElementById(\"nl-send-count\");var btn=form.querySelector(\"button.nl-send-btn\");var gruppe=form.querySelector(\"[name=filterGruppe]\");var art=form.querySelector(\"[name=filterArt]\");var only=form.querySelector(\"[name=onlyAusloesen]\");function visible(box){if(only&&only.checked&&box.getAttribute(\"data-aus\")!==\"1\")return false;var g=gruppe?gruppe.value:\"\";var a=art?art.value:\"\";if(g&&box.getAttribute(\"data-gruppe\")!==g)return false;if(a&&box.getAttribute(\"data-art\")!==a)return false;return true;}function sync(){var n=0,vis=0,chk=0,i,box,label,on;for(i=0;i<boxes.length;i++){box=boxes[i];on=visible(box);label=box.parentNode;if(label&&label.classList&&label.classList.contains(\"nl-send-row\"))label.hidden=!on;box.disabled=!on;if(!on)continue;vis++;if(box.checked){n++;chk++;}}if(countEl)countEl.textContent=n+\" Empfänger markiert\";if(btn)btn.disabled=(n===0);if(all){all.checked=vis>0&&chk===vis;all.indeterminate=chk>0&&chk<vis;}}if(all)all.addEventListener(\"change\",function(){var on=all.checked,i;for(i=0;i<boxes.length;i++){if(boxes[i].disabled)continue;boxes[i].checked=on;}sync();});form.addEventListener(\"change\",function(ev){if(ev.target===all)return;sync();});sync();})();</script>\n";
		$testTo = trim((string)(nl_settings()['fromEmail'] ?? ''));
		echo "<form class='nl-form' method='post'>";
		echo "<input type='hidden' name='action' value='test_issue'><input type='hidden' name='issue' value='" . nl_h($issue['id']) . "'>";
		echo "<p class='nl-note'>Testmail an den Absender der Mail-Konfiguration: " . nl_h($testTo) . "</p>";
		echo "<div class='nl-actions'><button type='submit' class='nl-btn secondary'>Testmail senden</button></div></form>";
		echo "</div></details></section>";
	}
}

if ($bereich === 'adressen') {
	$items = nl_items('newsletter_recipients.json');
	$cols = nl_recipient_columns();
	$hidden = nl_ui_hidden_columns('newsletter_ui.json', $cols, array('cc', 'summe', 'merci', 'statusGeschenk', 'rechnungsadresse', 'anhang', 'dateSend'));
	echo "<section class='nl-card nl-grid-card'><h2>Adressliste (" . count($items) . ")</h2>";
	echo "<p>Direkt in der Tabelle bearbeiten, sortieren und filtern — wie in Excel. Speichern schreibt nach <code>rkrk/data/newsletter/</code>.</p>";
	nl_echo_column_panel('newsletter_ui.json', $cols, $hidden, 'save_nl_ui', array('bereich' => 'adressen'));
	nl_echo_excel_grid('rkrk_newsletter.php', 'save_recipients_grid', $items, $cols, $hidden, array(
		'hiddenFields' => array('bereich' => 'adressen'),
		'widths' => nl_ui_widths('newsletter_ui.json', $cols),
		'widthAction' => 'save_nl_widths',
		'favoritAction' => 'save_nl_favorit',
	));
	echo "<h2 class='nl-import'>Import aus Excel</h2>";
	echo "<p>Blatt Email der bisherigen Mappe. Bestehende Adressen werden über die E-Mail ergänzt, der Versandstatus bleibt erhalten.</p>";
	echo "<form class='nl-form' method='post' enctype='multipart/form-data'><input type='hidden' name='action' value='import_newsletter'>";
	echo "<input type='file' name='xlsx' accept='.xlsx,.xlsm'> <button type='submit'>Importieren</button></form>";
	echo "</section>";
}


if ($bereich === 'einstellungen') {
	echo "<section class='nl-card'><h2>Mail</h2>";
	echo "<p>Absender, SMTP und Testmail stehen unter <a href='../rkrk_admin_mail.php'>Intern / Konfiguration / Nachrichten / Mail</a>.</p></section>";
	echo "<section class='nl-card'><h2>Öffentliche Embed-Seite</h2>";
	echo "<p>Einmal in die WordPress-Seite <a href='https://www.drohnenrehkitzrettung.ch/aktuell/newsletter'>Aktuell / Newsletter</a> einfügen. Neue veröffentlichte Ausgaben erscheinen dort ohne weiteren Edit.</p>";
	echo "<p><a href='rkrk_newsletter_public.php?MenuOn=off' target='_blank'>Öffentliche Seite öffnen</a></p>";
	echo "<textarea readonly style='width:100%;min-height:80px'>" . nl_h(nl_embed_snippet()) . "</textarea>";
	echo "<p>Solange das Modul nur auf test27 liegt, zeigt der iframe die Testinstanz.</p></section>";
	$log = array_reverse(nl_items('newsletter_log.json'));
	echo "<section class='nl-card'><h2>Versandlog</h2><table class='nl-table'><tr><th>Zeit</th><th>Art</th><th>An</th><th>OK</th><th>Fehler</th></tr>";
	foreach (array_slice($log, 0, 30) as $line) {
		echo "<tr><td>" . nl_h($line['time'] ?? '') . "</td><td>" . nl_h($line['kind'] ?? '') . "</td><td>" . nl_h($line['to'] ?? '') . "</td><td>" . nl_h($line['ok'] ?? '') . "</td><td>" . nl_h($line['error'] ?? '') . "</td></tr>";
	}
	echo "</table></section>";
}

echo "</main>\n";
footer();
