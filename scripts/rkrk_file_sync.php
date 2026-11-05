<?php
/**
 * Datei-Sync für externe Freigaben (Mirror, Ergänzen, Historie).
 * Konfiguration: rkrk/config/filesync.json
 */

if (!function_exists('rkrk_filesync_h')) {
	function rkrk_filesync_h($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('rkrk_filesync_modes')) {
	function rkrk_filesync_modes() {
		return array(
			'mirror' => 'Mirror',
			'ergaenzen' => 'Ergänzen',
			'historie' => 'Historie',
		);
	}
}

if (!function_exists('rkrk_filesync_mode')) {
	function rkrk_filesync_mode($raw) {
		$raw = strtolower(trim((string)$raw));
		if ($raw === 'copy' || $raw === 'ergänzen' || $raw === 'ergaenzen') {
			return 'ergaenzen';
		}
		if ($raw === 'historie' || $raw === 'history') {
			return 'historie';
		}
		return 'mirror';
	}
}

if (!function_exists('rkrk_filesync_example')) {
	function rkrk_filesync_example() {
		return array(
			'id' => '',
			'name' => 'Missionen',
			'source' => 'https://tchr-frog.synology.me:5706/web/rkropen/Missionen',
			'subdirs' => true,
			'types' => '*.kmz',
			'dest' => 'https://tchr-frog.synology.me:5706/RKRKPlus/Missionen(kmz)',
			'mode' => 'mirror',
		);
	}
}

if (!function_exists('rkrk_filesync_load')) {
	function rkrk_filesync_load() {
		$data = function_exists('rkrk_config_load') ? rkrk_config_load('filesync') : array();
		$rows = (isset($data['tasks']) && is_array($data['tasks'])) ? $data['tasks'] : array();
		$tasks = array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = rkrk_filesync_id((string)($row['id'] ?? ''));
			if ($id === '') {
				continue;
			}
			$tasks[$id] = rkrk_filesync_normalize($row, $id);
		}
		return $tasks;
	}
}

if (!function_exists('rkrk_filesync_save_all')) {
	function rkrk_filesync_save_all(array $tasks) {
		$rows = array();
		foreach ($tasks as $id => $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = rkrk_filesync_id((string)$id);
			if ($id === '') {
				continue;
			}
			$rows[] = rkrk_filesync_normalize($row, $id);
		}
		if (!function_exists('rkrk_config_save')) {
			return 'Config-Schreiben nicht verfügbar.';
		}
		return rkrk_config_save('filesync', array('tasks' => $rows));
	}
}

if (!function_exists('rkrk_filesync_id')) {
	function rkrk_filesync_id($raw) {
		$id = strtolower(trim((string)$raw));
		$id = preg_replace('/[^a-z0-9_\-]+/', '-', $id);
		return trim((string)$id, '-');
	}
}

if (!function_exists('rkrk_filesync_fresh_id')) {
	function rkrk_filesync_fresh_id($name, array $tasks) {
		$base = rkrk_filesync_id($name);
		if ($base === '') {
			$base = 'task';
		}
		$id = $base;
		$n = 2;
		while (isset($tasks[$id])) {
			$id = $base . '-' . $n;
			$n++;
		}
		return $id;
	}
}

if (!function_exists('rkrk_filesync_normalize')) {
	function rkrk_filesync_normalize(array $row, $id) {
		return array(
			'id' => rkrk_filesync_id($id),
			'name' => trim((string)($row['name'] ?? '')),
			'source' => trim((string)($row['source'] ?? '')),
			'subdirs' => !empty($row['subdirs']),
			'types' => trim((string)($row['types'] ?? '')),
			'dest' => trim((string)($row['dest'] ?? '')),
			'mode' => rkrk_filesync_mode($row['mode'] ?? 'mirror'),
			'last' => is_array($row['last'] ?? null) ? $row['last'] : array(),
		);
	}
}

if (!function_exists('rkrk_filesync_from_post')) {
	function rkrk_filesync_from_post(array $post) {
		return rkrk_filesync_normalize(array(
			'name' => $post['name'] ?? '',
			'source' => $post['source'] ?? '',
			'subdirs' => !empty($post['subdirs']),
			'types' => $post['types'] ?? '',
			'dest' => $post['dest'] ?? '',
			'mode' => $post['mode'] ?? 'mirror',
		), (string)($post['id'] ?? ''));
	}
}

if (!function_exists('rkrk_filesync_volume_roots')) {
	function rkrk_filesync_volume_roots() {
		$roots = array();
		if (function_exists('rkrk_unterhalt_rkropen_root')) {
			$rk = rtrim(str_replace('\\', '/', (string)rkrk_unterhalt_rkropen_root()), '/');
			if ($rk !== '') {
				$vol = dirname(dirname($rk));
				if ($vol !== '' && $vol !== '.' && $vol !== '/' && is_dir($vol)) {
					$roots[] = $vol;
				}
			}
		}
		foreach (array('/volume1', '/volume2', '/volumeUSB1') as $vol) {
			if (is_dir($vol)) {
				$roots[] = $vol;
			}
		}
		$out = array();
		foreach ($roots as $vol) {
			$rp = @realpath($vol);
			$key = strtolower(str_replace('\\', '/', $rp !== false ? $rp : $vol));
			if ($key !== '' && !isset($out[$key])) {
				$out[$key] = rtrim(str_replace('\\', '/', $rp !== false ? $rp : $vol), '/');
			}
		}
		return array_values($out);
	}
}

if (!function_exists('rkrk_filesync_input_rel')) {
	function rkrk_filesync_input_rel($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return array('', 'Pfad fehlt.');
		}
		if (preg_match('#^https?://#i', $raw)) {
			$path = parse_url($raw, PHP_URL_PATH);
			$raw = is_string($path) ? rawurldecode($path) : '';
		}
		$raw = str_replace('\\', '/', $raw);
		if (preg_match('#DavWWWRoot/(.+)$#i', $raw, $m)) {
			$raw = $m[1];
		}
		if (strpos($raw, '..') !== false) {
			return array('', 'Pfad darf nicht .. enthalten.');
		}
		if (preg_match('#^(?:/volume[^/]*|[A-Za-z]:)/#', $raw)) {
			return array(rtrim($raw, '/'), '');
		}
		$rel = trim($raw, '/');
		if ($rel === '') {
			return array('', 'Pfad fehlt.');
		}
		$parts = explode('/', $rel);
		if (count($parts) < 2) {
			return array('', 'Ordner liegt zu weit oben. Mindestens Freigabe und ein Unterordner.');
		}
		return array($rel, '');
	}
}

if (!function_exists('rkrk_filesync_resolve')) {
	function rkrk_filesync_resolve($raw) {
		list($rel, $err) = rkrk_filesync_input_rel($raw);
		if ($err !== '') {
			return array('ok' => false, 'abs' => '', 'exists' => false, 'error' => $err, 'input' => (string)$raw);
		}
		if (preg_match('#^(?:/volume[^/]*|[A-Za-z]:)/#', $rel)) {
			$windowsDrive = (bool)preg_match('#^[A-Za-z]:/#', $rel);
			if ($windowsDrive && DIRECTORY_SEPARATOR !== '\\') {
				$letter = strtoupper(substr($rel, 0, 2));
				return array(
					'ok' => false,
					'abs' => $rel,
					'exists' => false,
					'error' => 'Laufwerk ' . $letter . ' sieht nur der PC. Der Webserver legt den Ordner deshalb nicht an. Ziel als NAS-Freigabe eintragen, zum Beispiel RKRKPlus/Missionen(kmz).',
					'input' => (string)$raw,
				);
			}
			$rp = @realpath($rel);
			$abs = rtrim(str_replace('\\', '/', $rp !== false ? $rp : $rel), '/');
			return array(
				'ok' => true,
				'abs' => $abs,
				'exists' => is_dir($abs),
				'error' => '',
				'input' => (string)$raw,
			);
		}
		$found = '';
		foreach (rkrk_filesync_volume_roots() as $vol) {
			$abs = rtrim($vol, '/') . '/' . $rel;
			if (is_dir($abs)) {
				$rp = @realpath($abs);
				$found = rtrim(str_replace('\\', '/', $rp !== false ? $rp : $abs), '/');
				break;
			}
			if ($found === '') {
				$found = $abs;
			}
		}
		if ($found === '') {
			return array('ok' => false, 'abs' => '', 'exists' => false, 'error' => 'Kein Volume für den Pfad gefunden.', 'input' => (string)$raw);
		}
		$exists = is_dir($found);
		$denied = false;
		$error = '';
		if (!$exists) {
			$parent = dirname($found);
			if ($parent !== '' && $parent !== '/' && $parent !== '.' && is_dir($parent) && !is_readable($parent)) {
				$denied = true;
				$error = 'Freigabe nicht zugänglich: ' . $parent . '. Der Webserver-Benutzer http darf den Ordner nicht öffnen.';
			}
		}
		return array(
			'ok' => true,
			'abs' => $found,
			'exists' => $exists,
			'denied' => $denied,
			'error' => $error,
			'input' => (string)$raw,
		);
	}
}

if (!function_exists('rkrk_filesync_patterns')) {
	function rkrk_filesync_patterns($types) {
		$parts = preg_split('/[\s,;]+/', trim((string)$types));
		$out = array();
		if (!is_array($parts)) {
			return $out;
		}
		foreach ($parts as $part) {
			$part = trim((string)$part);
			if ($part === '') {
				continue;
			}
			if ($part === '*' || $part === '*.*') {
				$out[] = '#^.+$#i';
				continue;
			}
			if (strpos($part, '*') === false && strpos($part, '?') === false) {
				$part = '*.' . ltrim($part, '.');
			}
			$quoted = preg_quote($part, '#');
			$re = '#^' . str_replace(array('\\*', '\\?'), array('.*', '.'), $quoted) . '$#i';
			$out[] = $re;
		}
		return $out;
	}
}

if (!function_exists('rkrk_filesync_name_matches')) {
	function rkrk_filesync_name_matches($name, array $patterns) {
		foreach ($patterns as $re) {
			if (preg_match($re, (string)$name)) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('rkrk_filesync_skip_dir')) {
	function rkrk_filesync_skip_dir($name, $skipHistory) {
		$name = (string)$name;
		if ($name === '' || $name === '.' || $name === '..') {
			return true;
		}
		if ($name[0] === '.' || $name[0] === '_') {
			return true;
		}
		$low = strtolower($name);
		if (in_array($low, array('jsonfiles', 'xmlfiles', 'csvfiles'), true)) {
			return true;
		}
		if ($skipHistory && preg_match('/^\d{8}(-\d+)?$/', $name)) {
			return true;
		}
		return false;
	}
}

if (!function_exists('rkrk_filesync_scan')) {
	function rkrk_filesync_scan($root, $subdirs, array $patterns, $skipHistory) {
		$root = rtrim(str_replace('\\', '/', (string)$root), '/');
		$found = array();
		if ($root === '' || !is_dir($root) || $patterns === array()) {
			return $found;
		}
		$walk = function ($dir, $rel) use (&$walk, &$found, $root, $subdirs, $patterns, $skipHistory) {
			$names = @scandir($dir);
			if (!is_array($names)) {
				return;
			}
			foreach ($names as $name) {
				if ($name === '.' || $name === '..') {
					continue;
				}
				$abs = $dir . '/' . $name;
				if (is_link($abs)) {
					continue;
				}
				if ($rel === '' && strcasecmp($name, 'synch.log') === 0) {
					continue;
				}
				if (is_dir($abs)) {
					if (!$subdirs || rkrk_filesync_skip_dir($name, $skipHistory)) {
						continue;
					}
					$next = $rel === '' ? $name : ($rel . '/' . $name);
					$walk($abs, $next);
					continue;
				}
				if (!is_file($abs) || !rkrk_filesync_name_matches($name, $patterns)) {
					continue;
				}
				$key = $rel === '' ? $name : ($rel . '/' . $name);
				$found[$key] = array(
					'size' => (int)@filesize($abs),
					'mtime' => (int)@filemtime($abs),
					'abs' => $abs,
				);
			}
		};
		$walk($root, '');
		return $found;
	}
}

if (!function_exists('rkrk_filesync_inside')) {
	function rkrk_filesync_inside($abs, $root) {
		$root = rtrim(str_replace('\\', '/', (string)$root), '/');
		$abs = rtrim(str_replace('\\', '/', (string)$abs), '/');
		if ($root !== '' && $abs !== '' && ($abs === $root || strpos($abs . '/', $root . '/') === 0)) {
			return true;
		}
		if (function_exists('rkrk_unterhalt_dest_path_inside') && is_dir($abs) && is_dir($root)) {
			return rkrk_unterhalt_dest_path_inside($abs, $root);
		}
		return false;
	}
}

if (!function_exists('rkrk_filesync_history_name')) {
	function rkrk_filesync_history_name($destAbs) {
		$base = date('Ymd');
		$name = $base;
		$n = 2;
		$destAbs = rtrim(str_replace('\\', '/', (string)$destAbs), '/');
		while ($destAbs !== '' && is_dir($destAbs . '/' . $name)) {
			$name = $base . '-' . $n;
			$n++;
		}
		return $name;
	}
}

if (!function_exists('rkrk_filesync_prepare')) {
	function rkrk_filesync_prepare(array $task) {
		$name = trim((string)($task['name'] ?? ''));
		if ($name === '') {
			return array('ok' => false, 'error' => 'Name fehlt.');
		}
		$patterns = rkrk_filesync_patterns($task['types'] ?? '');
		if ($patterns === array()) {
			return array('ok' => false, 'error' => 'Dateitypen fehlen. Zum Beispiel *.kmz');
		}
		$src = rkrk_filesync_resolve($task['source'] ?? '');
		$dst = rkrk_filesync_resolve($task['dest'] ?? '');
		if (empty($src['ok'])) {
			return array('ok' => false, 'error' => 'Quelle: ' . (string)($src['error'] ?? 'ungültig'));
		}
		if (empty($dst['ok'])) {
			return array('ok' => false, 'error' => 'Ziel: ' . (string)($dst['error'] ?? 'ungültig'));
		}
		if (!empty($dst['denied'])) {
			return array('ok' => false, 'error' => (string)$dst['error']);
		}
		if (empty($src['exists'])) {
			return array('ok' => false, 'error' => 'Quellordner nicht gefunden: ' . $src['abs']);
		}
		$srcAbs = (string)$src['abs'];
		$dstAbs = (string)$dst['abs'];
		if (strcasecmp($srcAbs, $dstAbs) === 0 || rkrk_filesync_inside($dstAbs, $srcAbs) || rkrk_filesync_inside($srcAbs, $dstAbs)) {
			return array('ok' => false, 'error' => 'Quelle und Ziel dürfen nicht derselbe Ordner sein und nicht ineinander liegen.');
		}
		$mode = rkrk_filesync_mode($task['mode'] ?? 'mirror');
		$history = '';
		$copyRoot = $dstAbs;
		if ($mode === 'historie') {
			$history = rkrk_filesync_history_name($dstAbs);
			$copyRoot = $dstAbs . '/' . $history;
		}
		return array(
			'ok' => true,
			'error' => '',
			'task' => $task,
			'mode' => $mode,
			'patterns' => $patterns,
			'source' => $src,
			'dest' => $dst,
			'history' => $history,
			'copy_root' => $copyRoot,
			'subdirs' => !empty($task['subdirs']),
		);
	}
}

if (!function_exists('rkrk_filesync_plan')) {
	function rkrk_filesync_plan(array $task) {
		$prep = rkrk_filesync_prepare($task);
		if (empty($prep['ok'])) {
			return $prep + array('copy' => array(), 'same' => 0, 'delete' => array());
		}
		@set_time_limit(180);
		$srcIdx = rkrk_filesync_scan($prep['source']['abs'], $prep['subdirs'], $prep['patterns'], false);
		$dstIdx = array();
		if ($prep['mode'] !== 'historie' && !empty($prep['dest']['exists'])) {
			$dstIdx = rkrk_filesync_scan($prep['dest']['abs'], $prep['subdirs'], $prep['patterns'], true);
		}
		$copy = array();
		$same = 0;
		foreach ($srcIdx as $rel => $src) {
			$dst = $dstIdx[$rel] ?? null;
			$needed = ($prep['mode'] === 'historie') || $dst === null
				|| (int)$dst['size'] !== (int)$src['size']
				|| (int)$src['mtime'] > (int)$dst['mtime'];
			if (!$needed) {
				$same++;
				continue;
			}
			$copy[] = array(
				'rel' => $rel,
				'bytes' => (int)$src['size'],
				'kind' => $dst === null ? 'neu' : 'neuer',
			);
		}
		$delete = array();
		if ($prep['mode'] === 'mirror') {
			foreach ($dstIdx as $rel => $meta) {
				if (!isset($srcIdx[$rel])) {
					$delete[] = array('rel' => $rel, 'bytes' => (int)$meta['size']);
				}
			}
		}
		usort($copy, function ($a, $b) {
			return strcmp((string)$a['rel'], (string)$b['rel']);
		});
		usort($delete, function ($a, $b) {
			return strcmp((string)$a['rel'], (string)$b['rel']);
		});
		$prep['copy'] = $copy;
		$prep['same'] = $same;
		$prep['delete'] = $delete;
		$prep['source_count'] = count($srcIdx);
		return $prep;
	}
}

if (!function_exists('rkrk_filesync_apply')) {
	function rkrk_filesync_apply(array $task) {
		if (function_exists('rkrk_unterhalt_sync_apply_allowed')) {
			$cfg = function_exists('rkrk_unterhalt_load') ? rkrk_unterhalt_load() : array();
			if (!rkrk_unterhalt_sync_apply_allowed($cfg)) {
				return array('ok' => false, 'error' => 'Synchronisieren nur von test27 oder test.');
			}
		}
		$plan = rkrk_filesync_plan($task);
		if (empty($plan['ok'])) {
			return $plan;
		}
		if (!function_exists('rkrk_unterhalt_copy_file')) {
			return array('ok' => false, 'error' => 'Kopierfunktion fehlt.');
		}
		@set_time_limit(300);
		$destAbs = (string)$plan['dest']['abs'];
		if (!is_dir($destAbs)) {
			$parent = dirname($destAbs);
			if (!is_dir($parent) || !@mkdir($destAbs, 0775, true) && !is_dir($destAbs)) {
				return array('ok' => false, 'error' => 'Zielordner konnte nicht angelegt werden: ' . $destAbs);
			}
		}
		$copyRoot = (string)$plan['copy_root'];
		if ($plan['mode'] === 'historie' && !is_dir($copyRoot) && !@mkdir($copyRoot, 0775, true) && !is_dir($copyRoot)) {
			return array('ok' => false, 'error' => 'Historienordner konnte nicht angelegt werden: ' . $copyRoot);
		}
		$srcRoot = rtrim((string)$plan['source']['abs'], '/');
		$copied = 0;
		$deleted = 0;
		$failed = array();
		$logLines = array();
		foreach ($plan['copy'] as $item) {
			$rel = str_replace('\\', '/', (string)$item['rel']);
			if ($rel === '' || strpos($rel, '..') !== false) {
				$failed[] = $rel;
				$logLines[] = 'fehlgeschlagen  ' . $rel;
				continue;
			}
			$from = $srcRoot . '/' . $rel;
			$to = rtrim($copyRoot, '/') . '/' . $rel;
			$label = ((string)($item['kind'] ?? '') === 'neu') ? 'neu' : 'überschrieben';
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				$failed[] = $rel;
				$logLines[] = 'fehlgeschlagen  ' . $rel;
				continue;
			}
			$copied++;
			$logLines[] = $label . '  ' . $rel;
		}
		if ($plan['mode'] === 'mirror') {
			foreach ($plan['delete'] as $item) {
				$rel = str_replace('\\', '/', (string)$item['rel']);
				if ($rel === '' || strpos($rel, '..') !== false) {
					continue;
				}
				$abs = $destAbs . '/' . $rel;
				$gone = function_exists('rkrk_unterhalt_delete_dest_file')
					? rkrk_unterhalt_delete_dest_file($abs, $destAbs)
					: (is_file($abs) && @unlink($abs));
				if ($gone) {
					$deleted++;
					$logLines[] = 'gelöscht  ' . $rel;
					if (function_exists('rkrk_unterhalt_prune_empty_up')) {
						rkrk_unterhalt_prune_empty_up(dirname($abs), $destAbs);
					}
				} else {
					$failed[] = $rel;
					$logLines[] = 'fehlgeschlagen  ' . $rel;
				}
			}
		}
		$plan['log'] = rkrk_filesync_append_log($destAbs, $task, $plan, $logLines, $copied, $deleted);
		$plan['ok'] = ($failed === array());
		$plan['copied'] = $copied;
		$plan['deleted'] = $deleted;
		$plan['failed'] = $failed;
		if ($failed !== array()) {
			$plan['error'] = count($failed) . ' Dateien fehlgeschlagen.';
		}
		return $plan;
	}
}

if (!function_exists('rkrk_filesync_append_log')) {
	function rkrk_filesync_append_log($destAbs, array $task, array $plan, array $lines, $copied, $deleted) {
		$destAbs = rtrim(str_replace('\\', '/', (string)$destAbs), '/');
		if ($destAbs === '' || !is_dir($destAbs)) {
			return false;
		}
		$modes = rkrk_filesync_modes();
		$mode = (string)($plan['mode'] ?? '');
		$modeLabel = $modes[$mode] ?? $mode;
		$name = trim((string)($task['name'] ?? ''));
		$block = '---- ' . date('d.m.Y H:i') . '  ' . $name . '  ' . $modeLabel . " ----\n";
		if (!empty($plan['history'])) {
			$block .= 'Ordner: ' . $plan['history'] . "\n";
		}
		if ($lines === array()) {
			$block .= "keine Änderung\n";
		} else {
			$block .= implode("\n", $lines) . "\n";
		}
		$block .= (int)$copied . ' kopiert, ' . (int)$deleted . ' gelöscht, ' . (int)($plan['same'] ?? 0) . " unverändert\n\n";
		return @file_put_contents($destAbs . '/synch.log', $block, FILE_APPEND | LOCK_EX) !== false;
	}
}
