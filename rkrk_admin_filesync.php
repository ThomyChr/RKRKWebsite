<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');
include_once('scripts/rkrk_unterhalt.php');
include_once('scripts/rkrk_file_sync.php');

CheckIPandExit();

$fsAction = (string)($_POST['fs_action'] ?? '');
$okMsg = isset($_GET['ok']) ? (string)$_GET['ok'] : '';
$errMsg = isset($_GET['error']) ? (string)$_GET['error'] : '';
$plan = null;
$planId = (string)($_GET['task'] ?? '');

if (!function_exists('rkrk_filesync_go')) {
	function rkrk_filesync_go($ok, $error = '', $extra = array()) {
		$q = array();
		if ($ok !== '') {
			$q['ok'] = $ok;
		}
		if ($error !== '') {
			$q['error'] = $error;
		}
		foreach ($extra as $key => $value) {
			$q[$key] = $value;
		}
		header('Location: rkrk_admin_filesync.php' . ($q ? ('?' . http_build_query($q)) : ''));
		exit;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fsAction !== '') {
	$GLOBALS['RKRK_AJAX_JSON_MODE'] = true;
	GrantAccess();
	unset($GLOBALS['RKRK_AJAX_JSON_MODE']);
	if (empty($LoggedOn) || !GetAccess('TestOnly')) {
		header('HTTP/1.1 403 Forbidden');
		echo 'Kein Zugriff.';
		exit;
	}
	$tasks = rkrk_filesync_load();
	$posted = rkrk_filesync_from_post($_POST);
	if ($fsAction === 'save') {
		$id = $posted['id'];
		if ($id === '' || !isset($tasks[$id])) {
			$id = rkrk_filesync_fresh_id($posted['name'], $tasks);
		}
		$posted['id'] = $id;
		if (isset($tasks[$id]['last'])) {
			$posted['last'] = $tasks[$id]['last'];
		}
		$tasks[$id] = $posted;
		$msg = rkrk_filesync_save_all($tasks);
		if ($msg === '') {
			rkrk_filesync_go('Task «' . $posted['name'] . '» gespeichert.');
		}
		rkrk_filesync_go('', $msg);
	}
	if ($fsAction === 'delete') {
		$id = rkrk_filesync_id((string)($_POST['id'] ?? ''));
		if ($id === '' || !isset($tasks[$id])) {
			rkrk_filesync_go('', 'Task nicht gefunden.');
		}
		$name = (string)$tasks[$id]['name'];
		unset($tasks[$id]);
		$msg = rkrk_filesync_save_all($tasks);
		if ($msg === '') {
			rkrk_filesync_go('Task «' . $name . '» gelöscht. Dateien im Ziel bleiben liegen.');
		}
		rkrk_filesync_go('', $msg);
	}
	if ($fsAction === 'preview') {
		$id = rkrk_filesync_id((string)($_POST['id'] ?? ''));
		$task = ($id !== '' && isset($tasks[$id])) ? $tasks[$id] : $posted;
		$plan = rkrk_filesync_plan($task);
		$planId = (string)($task['id'] ?? '');
	}
	if ($fsAction === 'apply') {
		$id = rkrk_filesync_id((string)($_POST['id'] ?? ''));
		if ($id === '' || !isset($tasks[$id])) {
			rkrk_filesync_go('', 'Zuerst den Task speichern.');
		}
		$res = rkrk_filesync_apply($tasks[$id]);
		if (!empty($res['ok'])) {
			$tasks[$id]['last'] = array(
				'at' => date('d.m.Y H:i'),
				'mode' => (string)($res['mode'] ?? ''),
				'copied' => (int)($res['copied'] ?? 0),
				'deleted' => (int)($res['deleted'] ?? 0),
				'history' => (string)($res['history'] ?? ''),
			);
			rkrk_filesync_save_all($tasks);
			$modes = rkrk_filesync_modes();
			$modeLabel = $modes[$res['mode']] ?? $res['mode'];
			$msg = $modeLabel . ': ' . (int)$res['copied'] . ' Dateien kopiert.';
			if (($res['mode'] ?? '') === 'mirror') {
				$msg .= ' ' . (int)$res['deleted'] . ' überzählige Dateien gelöscht.';
			}
			if (($res['history'] ?? '') !== '') {
				$msg .= ' Historie: ' . $res['history'] . '.';
			}
			if (!empty($res['log'])) {
				$msg .= ' synch.log im Ziel ergänzt.';
			}
			rkrk_filesync_go($msg, '', array('task' => $id));
		}
		$err = (string)($res['error'] ?? 'Sync fehlgeschlagen.');
		if (!empty($res['log'])) {
			$err .= ' synch.log im Ziel ergänzt.';
		}
		rkrk_filesync_go('', $err, array('task' => $id));
	}
}

if (!rkrk_admin_boot('Datei-Sync')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_filesync.php');

$tasks = rkrk_filesync_load();
$modes = rkrk_filesync_modes();
$example = rkrk_filesync_example();
$h = 'rkrk_filesync_h';

echo "<style>
.fs-lead{color:#d5ddd4;font-size:.9em;line-height:1.35;margin:.15rem 0 .55rem;max-width:920px}
.fs-lead code{color:#f3f6f2}
.fs-task,.fs-card{background:#fff;border:1px solid #d7ddd7;border-radius:8px;padding:.55rem .7rem;margin:.4rem 0}
.fs-task h2,.fs-card h2{margin:0;font-size:1.02rem}
.fs-head{display:flex;flex-wrap:wrap;align-items:baseline;gap:.15rem .65rem}
.fs-meta{color:#5a675a;font-size:.88em}
.fs-route{display:flex;flex-wrap:wrap;align-items:baseline;gap:.2rem .45rem;margin:.28rem 0 .05rem}
.fs-route b{font-weight:650}
.fs-path{font-family:ui-monospace,Consolas,monospace;font-size:.8em;word-break:break-all}
.fs-ok{color:#1b5e20}
.fs-warn{color:#8a5a00}
.fs-bad{color:#b71c1c}
.fs-err{color:#b71c1c;font-weight:600}
.fs-actions{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.35rem}
.fs-actions form{margin:0}
.fs-edit{margin-top:.35rem}
.fs-edit > summary{cursor:pointer;color:#1b3552;font-size:.9em}
.fs-fields{display:grid;grid-template-columns:6.2rem minmax(0,1fr);gap:.28rem .55rem;align-items:center;margin-top:.4rem;max-width:860px}
.fs-fields input[type=text]{width:100%;box-sizing:border-box;padding:4px 6px}
.fs-scroll{overflow:auto;max-height:12rem;margin:.3rem 0}
</style>\n";

if ($okMsg !== '') {
	echo "<p class='fs-ok'>" . $h($okMsg) . "</p>\n";
}
if ($errMsg !== '') {
	echo "<p class='fs-err'>" . $h($errMsg) . "</p>\n";
}

echo "<p class='fs-lead'>Kopiert einen Ordner auf eine Freigabe. Pfad als WebDAV-Adresse oder ab der Freigabe, mindestens Freigabe und Unterordner. Mirror gleicht an und löscht Überzähliges dieses Typs. Ergänzen kopiert nur. Historie legt einen Ordner YYYYMMDD an. <code>jsonfiles</code>, <code>xmlfiles</code>, <code>csvfiles</code> und Namen mit <code>.</code> oder <code>_</code> am Anfang bleiben aussen vor.</p>\n";

if (is_array($plan)) {
	echo "<div class='fs-card'>\n";
	echo "<h2>Vorschau</h2>\n";
	if (empty($plan['ok'])) {
		echo "<p class='fs-err'>" . $h($plan['error'] ?? 'Vorschau fehlgeschlagen') . "</p>\n";
	} else {
		$modeLabel = $modes[$plan['mode']] ?? $plan['mode'];
		echo "<p>" . $h($modeLabel) . " · " . (int)($plan['source_count'] ?? 0) . " passende Dateien in der Quelle · " . count($plan['copy']) . " zu kopieren · " . (int)$plan['same'] . " unverändert";
		if ($plan['mode'] === 'mirror') {
			echo " · " . count($plan['delete']) . " im Ziel zu löschen";
		}
		echo ".</p>\n";
		echo "<p class='fs-muted fs-path'>Quelle: " . $h($plan['source']['abs']) . ($plan['source']['exists'] ? '' : ' (fehlt)') . "<br>Ziel: " . $h($plan['copy_root']) . "</p>\n";
		if (!empty($plan['copy'])) {
			echo "<div class='fs-scroll'><table class='big'><tr><th>Kopieren</th><th>Stand</th></tr>\n";
			$shown = 0;
			foreach ($plan['copy'] as $row) {
				if ($shown >= 40) {
					break;
				}
				echo "<tr><td>" . $h($row['rel']) . "</td><td>" . $h($row['kind']) . "</td></tr>\n";
				$shown++;
			}
			echo "</table></div>\n";
			if (count($plan['copy']) > 40) {
				echo "<p class='fs-muted'>Weitere " . (count($plan['copy']) - 40) . " Dateien.</p>\n";
			}
		}
		if ($plan['mode'] === 'mirror' && !empty($plan['delete'])) {
			echo "<p class='fs-err'>Mirror löscht diese Dateien im Ziel:</p>\n";
			echo "<div class='fs-scroll'><table class='big'><tr><th>Löschen</th></tr>\n";
			$shown = 0;
			foreach ($plan['delete'] as $row) {
				if ($shown >= 40) {
					break;
				}
				echo "<tr><td>" . $h($row['rel']) . "</td></tr>\n";
				$shown++;
			}
			echo "</table></div>\n";
		}
	}
	echo "</div>\n";
}

if (!function_exists('rkrk_filesync_show_path')) {
	function rkrk_filesync_show_path($raw) {
		list($rel, $err) = rkrk_filesync_input_rel($raw);
		if ($rel !== '') {
			return $rel;
		}
		return (string)$raw;
	}
}

if (!function_exists('rkrk_filesync_place_note')) {
	function rkrk_filesync_place_note(array $res, $isDest) {
		if (!empty($res['error'])) {
			return array((string)$res['error'], 'fs-bad');
		}
		if (empty($res['abs'])) {
			return array('Pfad ungültig', 'fs-bad');
		}
		if (!empty($res['denied'])) {
			return array('kein Zugriff', 'fs-bad');
		}
		if (empty($res['exists'])) {
			return array($isDest ? 'wird angelegt' : 'fehlt', $isDest ? 'fs-warn' : 'fs-bad');
		}
		return array('vorhanden', 'fs-ok');
	}
}

if (!function_exists('rkrk_filesync_echo_fields')) {
	function rkrk_filesync_echo_fields($suffix, array $task, array $modes) {
		$h = 'rkrk_filesync_h';
		echo "<div class='fs-fields'>\n";
		echo "<label for='name-" . $h($suffix) . "'>Name</label><input id='name-" . $h($suffix) . "' name='name' type='text' value='" . $h($task['name']) . "' required>\n";
		echo "<label for='source-" . $h($suffix) . "'>Ordner</label><input id='source-" . $h($suffix) . "' name='source' type='text' value='" . $h($task['source']) . "' placeholder='web/rkropen/Missionen' required>\n";
		echo "<label for='dest-" . $h($suffix) . "'>Ziel</label><input id='dest-" . $h($suffix) . "' name='dest' type='text' value='" . $h($task['dest']) . "' placeholder='RKRKPlus/Missionen(kmz)' required>\n";
		echo "<label for='types-" . $h($suffix) . "'>Dateitypen</label><input id='types-" . $h($suffix) . "' name='types' type='text' value='" . $h($task['types']) . "' placeholder='*.kmz' required>\n";
		echo "<span>Unterordner</span><span class='rkrk-sels rkrk-reg-pills'>";
		rkrk_admin_echo_pill('radio', 'subdirs', '1', !empty($task['subdirs']), 'Ja', '', 'Auch Dateien in Unterordnern');
		rkrk_admin_echo_pill('radio', 'subdirs', '0', empty($task['subdirs']), 'Nein', '', 'Nur Dateien direkt im Ordner');
		echo "</span>\n";
		echo "<span>Synchtyp</span><span class='rkrk-sels rkrk-reg-pills'>";
		foreach ($modes as $modeId => $modeLabel) {
			rkrk_admin_echo_pill('radio', 'mode', $modeId, rkrk_filesync_mode($task['mode']) === $modeId, $modeLabel);
		}
		echo "</span>\n";
		echo "</div>\n";
	}
}

$cards = $tasks;
if ($cards === array()) {
	$cards[''] = $example;
}

foreach ($cards as $id => $task) {
	$saved = ($id !== '' && isset($tasks[$id]));
	$srcRes = rkrk_filesync_resolve($task['source']);
	$dstRes = rkrk_filesync_resolve($task['dest']);
	list($srcNote, $srcClass) = rkrk_filesync_place_note($srcRes, false);
	list($dstNote, $dstClass) = rkrk_filesync_place_note($dstRes, true);
	$modeLabel = $modes[rkrk_filesync_mode($task['mode'])] ?? $task['mode'];
	echo "<div class='fs-task'>\n";
	echo "<form method='post' class='rkrk-admin-form'>\n";
	echo "<input type='hidden' name='id' value='" . $h($saved ? $id : '') . "'>\n";
	echo "<div class='fs-head'><h2>" . $h($saved ? $task['name'] : 'Beispiel') . "</h2>";
	echo "<span class='fs-meta'>" . $h($modeLabel) . " · " . $h($task['types']) . " · Unterordner " . (!empty($task['subdirs']) ? 'ja' : 'nein') . "</span>";
	if ($saved && !empty($task['last']['at'])) {
		$last = $task['last'];
		$lastText = $h($last['at']) . ' · ' . (int)($last['copied'] ?? 0) . ' kopiert';
		if (($last['mode'] ?? '') === 'mirror') {
			$lastText .= ', ' . (int)($last['deleted'] ?? 0) . ' gelöscht';
		}
		if (!empty($last['history'])) {
			$lastText .= ', ' . $h($last['history']);
		}
		echo "<span class='fs-meta'>" . $lastText . "</span>";
	}
	echo "</div>\n";
	echo "<div class='fs-route'>";
	echo "<span><b>Quelle</b> <span class='fs-path'>" . $h(rkrk_filesync_show_path($task['source'])) . "</span> <span class='" . $srcClass . "'>" . $h($srcNote) . "</span></span>";
	echo "<span class='fs-meta'>→</span>";
	echo "<span><b>Ziel</b> <span class='fs-path'>" . $h(rkrk_filesync_show_path($task['dest'])) . "</span> <span class='" . $dstClass . "'>" . $h($dstNote) . "</span></span>";
	echo "</div>\n";
	echo "<div class='fs-actions'>\n";
	echo "<button type='submit' name='fs_action' value='save'>Speichern</button>\n";
	echo "<button type='submit' name='fs_action' value='preview'>Vorschau</button>\n";
	if ($saved) {
		echo "<button type='submit' name='fs_action' value='apply' onclick=\"var f=this.form; var m='mirror'; var els=f.mode; if(els){ if(!els.length) m=els.value; else for(var i=0;i<els.length;i++) if(els[i].checked) m=els[i].value; } if(m==='mirror') return confirm('Mirror: passende Dateien kopieren und überzählige Dateien dieses Typs im Ziel löschen. Historienordner bleiben. Weiter?'); if(m==='historie') return confirm('Historie: Dateien in einen neuen Ordner YYYYMMDD kopieren. Nichts wird gelöscht. Weiter?'); return confirm('Ergänzen: fehlende oder neuere Dateien kopieren. Nichts wird gelöscht. Weiter?');\">Synchronisieren</button>\n";
		echo "<button type='submit' name='fs_action' value='delete' onclick=\"return confirm('Nur den Task aus der Konfiguration löschen. Dateien im Ziel bleiben. Weiter?');\">Task löschen</button>\n";
	}
	echo "</div>\n";
	echo "<details class='fs-edit'" . ($saved ? '' : ' open') . "><summary>Eintrag</summary>\n";
	rkrk_filesync_echo_fields($saved ? $id : 'neu', $task, $modes);
	echo "</details>\n";
	echo "</form>\n";
	echo "</div>\n";
}

if ($tasks !== array()) {
	$blank = array('name' => '', 'source' => '', 'subdirs' => true, 'types' => '*.kmz', 'dest' => '', 'mode' => 'mirror');
	echo "<details class='fs-task fs-edit'><summary>Weiterer Task</summary>\n";
	echo "<form method='post' class='rkrk-admin-form'><input type='hidden' name='id' value=''>\n";
	rkrk_filesync_echo_fields('add', $blank, $modes);
	echo "<div class='fs-actions'><button type='submit' name='fs_action' value='save'>Task anlegen</button></div>\n";
	echo "</form></details>\n";
}

echo "</main>\n";
footer();
