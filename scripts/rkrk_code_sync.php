<?php
/**
 * Code sync: preview then copy newer/new files. Mirror may delete extras in the matrix.
 * Control UI: test27 or test. Chain: test27 → test → v2 → pub. rkrs from test27 or test.
 */

include_once(__DIR__ . '/rkrk_unterhalt.php');

if (!function_exists('rkrk_code_sync_routes')) {
	function rkrk_code_sync_routes($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$chain = isset($cfg['chain']) && is_array($cfg['chain']) ? $cfg['chain'] : array('test27', 'test', 'v2', 'pub');
		$here = rkrk_unterhalt_current_site_key($cfg);
		$start = 0;
		for ($i = 0; $i < count($chain); $i++) {
			if ((string)$chain[$i] === $here) {
				$start = $i;
				break;
			}
		}
		$rest = array_values(array_slice($chain, $start));
		$steps = array();
		for ($i = 0; $i < count($chain) - 1; $i++) {
			$from = (string)$chain[$i];
			$to = (string)$chain[$i + 1];
			$id = $from . '_to_' . $to;
			$fromLab = $cfg['sites'][$from]['label'] ?? $from;
			$toLab = $cfg['sites'][$to]['label'] ?? $to;
			$steps[$id] = array(
				'id' => $id,
				'kind' => 'full',
				'from' => $from,
				'to' => $to,
				'label' => $from . ' → ' . $to . ' (' . $fromLab . ' → ' . $toLab . ')',
			);
		}
		$restHops = array();
		for ($i = 0; $i < count($rest) - 1; $i++) {
			$restHops[] = $rest[$i] . '_to_' . $rest[$i + 1];
		}
		$rkrsOn = function_exists('rkrk_code_sync_rkrs_enabled') && rkrk_code_sync_rkrs_enabled($cfg);
		$out = array();
		if ($restHops !== array()) {
			$out['all_levels'] = array(
				'id' => 'all_levels',
				'kind' => 'chain',
				'from' => (string)$rest[0],
				'to' => (string)$rest[count($rest) - 1],
				'hops' => $restHops,
				'label' => 'Alle Ebenen: ' . implode(' → ', $rest) . ($rkrsOn ? ' | rkrs' : ''),
			);
		}
		foreach ($steps as $id => $row) {
			if ((string)$row['to'] === $here) {
				continue;
			}
			$out[$id] = $row;
		}
		$rkrsSrc = (string)($cfg['rkrs_source'] ?? 'test27');
		if (!in_array($rkrsSrc, array('test27', 'test'), true)) {
			$rkrsSrc = 'test27';
		}
		if ($rkrsOn) {
			$out['to_rkrs'] = array(
				'id' => 'to_rkrs',
				'kind' => 'rkrs',
				'from' => $rkrsSrc,
				'to' => 'rkrs',
				'label' => $rkrsSrc . ' → rkrs (nur mit aktiviertem Modul)',
			);
		}
		return $out;
	}
}

if (!function_exists('rkrk_code_sync_rkrs_enabled')) {
	/** Site rkrs aktiv und mindestens ein Ordner mit Ziel → rkrs. */
	function rkrk_code_sync_rkrs_enabled($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		if (empty($cfg['sites']['rkrs']['active'])) {
			return false;
		}
		$mods = rkrk_code_sync_rkrs_modules($cfg);
		return $mods !== array();
	}
}

if (!function_exists('rkrk_code_sync_chain_hops')) {
	/** Ketten-Stufen. test27→rkrs ist nie Teil von Alle Ebenen. */
	function rkrk_code_sync_chain_hops(array $route, array $cfg) {
		$hops = array();
		foreach ((array)($route['hops'] ?? array()) as $hopId) {
			$hops[] = (string)$hopId;
		}
		return $hops;
	}
}

if (!function_exists('rkrk_code_sync_rkrs_modules')) {
	function rkrk_code_sync_rkrs_modules($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$out = array();
		foreach ($cfg['folders'] as $fid => $folder) {
			if (empty($folder['active'])) {
				continue;
			}
			$targets = isset($folder['targets']) && is_array($folder['targets']) ? $folder['targets'] : array();
			if (empty($targets['rkrs'])) {
				continue;
			}
			$dest = function_exists('rkrk_unterhalt_rkrs_dest_for')
				? rkrk_unterhalt_rkrs_dest_for($fid, $folder)
				: rkrk_unterhalt_safe_rel($folder['rkrs_dest'] ?? '');
			if ($dest === null || $dest === '') {
				continue;
			}
			$out[$fid] = array(
				'id' => $fid,
				'label' => (string)($folder['label'] ?? $fid),
				'dest' => $dest,
			);
		}
		return $out;
	}
}

if (!function_exists('rkrk_code_sync_selected_rkrs_folders')) {
	function rkrk_code_sync_selected_rkrs_folders($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$allowed = rkrk_code_sync_rkrs_modules($cfg);
		$raw = $_POST['rkrs_mod'] ?? $_GET['rkrs_mod'] ?? array();
		if (!is_array($raw)) {
			$raw = array($raw);
		}
		$out = array();
		foreach ($raw as $id) {
			$id = (string)$id;
			if ($id !== '' && isset($allowed[$id])) {
				$out[$id] = $id;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_code_sync_rkrs_last_label')) {
	function rkrk_code_sync_rkrs_last_label($folderId, array $cfg) {
		$row = isset($cfg['rkrs_last'][$folderId]) && is_array($cfg['rkrs_last'][$folderId])
			? $cfg['rkrs_last'][$folderId]
			: null;
		$at = is_array($row) ? trim((string)($row['at'] ?? '')) : '';
		if ($at === '') {
			return 'noch nie';
		}
		$t = strtotime($at);
		return $t ? date('d.m.Y H:i', $t) : $at;
	}
}

if (!function_exists('rkrk_code_sync_rkrs_remember')) {
	function rkrk_code_sync_rkrs_remember(array $folderIds, array $items) {
		if ($folderIds === array()) {
			return;
		}
		$cfg = rkrk_unterhalt_load();
		if (!isset($cfg['rkrs_last']) || !is_array($cfg['rkrs_last'])) {
			$cfg['rkrs_last'] = array();
		}
		$per = array();
		foreach ($items as $item) {
			$fid = (string)($item['folder'] ?? '');
			if ($fid === '') {
				continue;
			}
			if (!isset($per[$fid])) {
				$per[$fid] = 0;
			}
			$per[$fid]++;
		}
		$user = function_exists('rkrk_unterhalt_user') ? rkrk_unterhalt_user() : '';
		$now = date('c');
		foreach ($folderIds as $fid) {
			$fid = (string)$fid;
			$cfg['rkrs_last'][$fid] = array(
				'at' => $now,
				'user' => $user,
				'copied' => isset($per[$fid]) ? (int)$per[$fid] : 0,
			);
		}
		rkrk_unterhalt_save($cfg);
	}
}

if (!function_exists('rkrk_code_sync_default_route')) {
	function rkrk_code_sync_default_route($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$routes = rkrk_code_sync_routes($cfg);
		$here = rkrk_unterhalt_current_site_key($cfg);
		$chain = isset($cfg['chain']) && is_array($cfg['chain']) ? $cfg['chain'] : array('test27', 'test', 'v2', 'pub');
		for ($i = 0; $i < count($chain) - 1; $i++) {
			$id = $chain[$i] . '_to_' . $chain[$i + 1];
			if ((string)$chain[$i] === $here && isset($routes[$id])) {
				return $id;
			}
		}
		return isset($routes['all_levels']) ? 'all_levels' : 'test27_to_test';
	}
}

if (!function_exists('rkrk_code_sync_map_rel_for_rkrs')) {
	function rkrk_code_sync_map_rel_for_rkrs($rel, array $folder, $folderId = '') {
		$destFolder = function_exists('rkrk_unterhalt_rkrs_dest_for')
			? rkrk_unterhalt_rkrs_dest_for($folderId, $folder)
			: rkrk_unterhalt_safe_rel($folder['rkrs_dest'] ?? ($folder['rel'] ?? ''));
		if ($destFolder === null || $destFolder === '') {
			return null;
		}
		$kind = (string)($folder['kind'] ?? 'dir');
		if ($kind === 'calc') {
			$base = basename(str_replace('\\', '/', $rel));
			return $destFolder . '/' . $base;
		}
		$srcRel = rkrk_unterhalt_safe_rel($folder['rel'] ?? '');
		if ($srcRel === null) {
			return null;
		}
		$norm = str_replace('\\', '/', $rel);
		if ($srcRel === '') {
			return $destFolder . '/' . $norm;
		}
		if ($norm === $srcRel) {
			return $destFolder;
		}
		$prefix = $srcRel . '/';
		if (strpos($norm, $prefix) !== 0) {
			return $destFolder . '/' . basename($norm);
		}
		$rest = substr($norm, strlen($prefix));
		if ($destFolder === 'assess/public' && ($rest === 'public' || strpos($rest, 'public/') === 0)) {
			$rest = ($rest === 'public') ? '' : substr($rest, strlen('public/'));
		}
		if ($rest === '') {
			return $destFolder;
		}
		return $destFolder . '/' . $rest;
	}
}

if (!function_exists('rkrk_code_sync_index_route')) {
	function rkrk_code_sync_index_route(array $route, array $cfg) {
		$fromAbs = rkrk_unterhalt_site_abs($route['from'], $cfg);
		$toAbs = rkrk_unterhalt_site_abs($route['to'], $cfg);
		$src = array();
		$dst = array();
		$pairs = array();
		$kind = (string)($route['kind'] ?? 'full');
		$targetKey = $kind === 'rkrs' ? 'rkrs' : (string)$route['to'];
		$rkrsWant = $kind === 'rkrs' ? rkrk_code_sync_selected_rkrs_folders($cfg) : array();
		foreach ($cfg['folders'] as $fid => $folder) {
			if (!rkrk_unterhalt_folder_for_target($folder, $targetKey)) {
				continue;
			}
			if ($kind === 'rkrs' && !isset($rkrsWant[$fid])) {
				continue;
			}
			if ($kind === 'rkrs') {
				$srcKey = rkrk_unterhalt_rkrs_source_for($folder, $cfg);
				$fromAbs = rkrk_unterhalt_site_abs($srcKey, $cfg);
				if ($fromAbs === '' || $toAbs === '') {
					continue;
				}
				$srcIdx = rkrk_unterhalt_index_folder($fromAbs, $folder);
				$mapped = array();
				foreach ($srcIdx as $rel => $meta) {
					$destRel = rkrk_code_sync_map_rel_for_rkrs($rel, $folder, $fid);
					if ($destRel === null || $destRel === '') {
						continue;
					}
					$mapped[$destRel] = $meta;
					$pairs[$destRel] = array(
						'from_rel' => $rel,
						'from_root' => $fromAbs,
						'to_rel' => $destRel,
						'folder' => $fid,
						'source' => $srcKey,
					);
				}
				$rkrsDestRel = function_exists('rkrk_unterhalt_rkrs_dest_for')
					? rkrk_unterhalt_rkrs_dest_for($fid, $folder)
					: (string)($folder['rkrs_dest'] ?? $folder['rel'] ?? '');
				if (($folder['kind'] ?? '') === 'calc') {
					$dstIdx = array();
					foreach ($mapped as $destRel => $_meta) {
						$full = rtrim(str_replace('\\', '/', $toAbs), '/') . '/' . $destRel;
						if (is_file($full)) {
							$dstIdx[$destRel] = rkrk_unterhalt_file_meta($full);
						}
					}
				} else {
					$destFolder = array(
						'kind' => 'dir',
						'rel' => $rkrsDestRel,
						'allow_ext' => $folder['allow_ext'] ?? null,
						'deny_ext' => $folder['deny_ext'] ?? null,
						'active' => true,
					);
					$dstIdx = rkrk_unterhalt_index_folder($toAbs, $destFolder);
				}
				foreach ($mapped as $rel => $meta) {
					$src[$rel] = $meta;
				}
				foreach ($dstIdx as $rel => $meta) {
					$dst[$rel] = $meta;
				}
				continue;
			}
			if ($fromAbs === '' || $toAbs === '') {
				continue;
			}
			$srcIdx = rkrk_unterhalt_index_folder($fromAbs, $folder);
			$dstIdx = rkrk_unterhalt_index_folder($toAbs, $folder);
			foreach ($srcIdx as $rel => $meta) {
				$src[$rel] = $meta;
				$pairs[$rel] = array(
					'from_rel' => $rel,
					'from_root' => $fromAbs,
					'to_rel' => $rel,
					'folder' => $fid,
					'source' => $route['from'],
				);
			}
			foreach ($dstIdx as $rel => $meta) {
				$dst[$rel] = $meta;
			}
		}
		return array('src' => $src, 'dst' => $dst, 'pairs' => $pairs, 'from_abs' => $fromAbs, 'to_abs' => $toAbs);
	}
}

if (!function_exists('rkrk_code_sync_decide')) {
	function rkrk_code_sync_decide($src, $dst) {
		if ($src === null) {
			return null;
		}
		if ($dst === null) {
			return 'new';
		}
		$sh = (string)($src['hash'] ?? '');
		$dh = (string)($dst['hash'] ?? '');
		if ($sh !== '' && $sh === $dh) {
			return null;
		}
		$sm = (int)($src['mtime'] ?? 0);
		$dm = (int)($dst['mtime'] ?? 0);
		if ($sm > $dm || ($sm === $dm && $sh !== $dh)) {
			return 'newer';
		}
		return null;
	}
}

if (!function_exists('rkrk_code_sync_plan')) {
	function rkrk_code_sync_plan($routeId, $cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$routes = rkrk_code_sync_routes($cfg);
		if (!isset($routes[$routeId])) {
			return array('ok' => false, 'error' => 'Unbekannte Route.', 'items' => array(), 'dest_only' => array());
		}
		$route = $routes[$routeId];
		if (($route['kind'] ?? '') === 'chain') {
			@set_time_limit(300);
			$hops = array();
			$items = array();
			$destOnly = array();
			foreach (rkrk_code_sync_chain_hops($route, $cfg) as $hopId) {
				$hop = rkrk_code_sync_plan((string)$hopId, $cfg);
				if (empty($hop['ok'])) {
					return array(
						'ok' => false,
						'error' => ((string)($hop['route']['label'] ?? $hopId)) . ': ' . (string)($hop['error'] ?? 'Vorschau fehlgeschlagen.'),
						'items' => array(),
						'dest_only' => array(),
						'hops' => $hops,
						'route' => $route,
					);
				}
				$hopLabel = (string)($hop['route']['label'] ?? $hopId);
				foreach ($hop['items'] as $item) {
					$item['hop'] = $hopLabel;
					$item['hop_id'] = (string)$hopId;
					$items[] = $item;
				}
				foreach ($hop['dest_only'] as $drow) {
					$drow['hop'] = $hopLabel;
					$drow['hop_id'] = (string)$hopId;
					$destOnly[] = $drow;
				}
				$hops[] = $hop;
			}
			return array(
				'ok' => true,
				'error' => '',
				'route' => $route,
				'items' => $items,
				'dest_only' => $destOnly,
				'hops' => $hops,
			);
		}
		$fromAbs = rkrk_unterhalt_site_abs($route['from'], $cfg);
		$toAbs = rkrk_unterhalt_site_abs($route['to'], $cfg);
		if (($route['kind'] ?? '') !== 'rkrs') {
			if ($fromAbs === '' || !is_dir($fromAbs)) {
				return array('ok' => false, 'error' => 'Quelle nicht gefunden: ' . $route['from'], 'items' => array(), 'dest_only' => array());
			}
			if ($toAbs === '' || !is_dir($toAbs)) {
				return array('ok' => false, 'error' => 'Ziel nicht gefunden: ' . $route['to'], 'items' => array(), 'dest_only' => array());
			}
		} else {
			if (!rkrk_code_sync_rkrs_enabled($cfg)) {
				return array('ok' => false, 'error' => 'rkrs-Sync ist nicht aktiviert (Site oder Ordner → rkrs).', 'items' => array(), 'dest_only' => array(), 'route' => $route);
			}
			if ($toAbs === '' || !is_dir($toAbs)) {
				return array('ok' => false, 'error' => 'Ziel rkrs nicht gefunden.', 'items' => array(), 'dest_only' => array());
			}
			if (rkrk_code_sync_selected_rkrs_folders($cfg) === array()) {
				return array(
					'ok' => false,
					'error' => 'Bitte ein rkrs-Modul wählen (map, mapII, assess oder calc).',
					'items' => array(),
					'dest_only' => array(),
					'route' => $route,
				);
			}
		}
		$idx = rkrk_code_sync_index_route($route, $cfg);
		$items = array();
		foreach ($idx['pairs'] as $destRel => $pair) {
			$src = $idx['src'][$destRel] ?? null;
			$dst = $idx['dst'][$destRel] ?? null;
			$kind = rkrk_code_sync_decide($src, $dst);
			if ($kind === null) {
				continue;
			}
			$items[] = array(
				'rel' => $destRel,
				'from_rel' => $pair['from_rel'],
				'from_root' => $pair['from_root'],
				'to_rel' => $pair['to_rel'],
				'folder' => $pair['folder'],
				'source' => $pair['source'],
				'kind' => $kind,
				'from_mtime' => (int)($src['mtime'] ?? 0),
				'to_mtime' => $dst !== null ? (int)$dst['mtime'] : 0,
				'bytes' => (int)($src['size'] ?? 0),
			);
		}
		$destOnly = array();
		foreach ($idx['dst'] as $rel => $meta) {
			if (!isset($idx['src'][$rel])) {
				$destOnly[] = array(
					'rel' => $rel,
					'mtime' => (int)($meta['mtime'] ?? 0),
					'bytes' => (int)($meta['size'] ?? 0),
				);
			}
		}
		usort($items, function ($a, $b) {
			return strcmp((string)$a['rel'], (string)$b['rel']);
		});
		usort($destOnly, function ($a, $b) {
			return strcmp((string)$a['rel'], (string)$b['rel']);
		});
		return array(
			'ok' => true,
			'error' => '',
			'route' => $route,
			'items' => $items,
			'dest_only' => $destOnly,
		);
	}
}

if (!function_exists('rkrk_code_sync_mode')) {
	function rkrk_code_sync_mode($raw) {
		return ((string)$raw === 'copy') ? 'copy' : 'mirror';
	}
}

if (!function_exists('rkrk_code_sync_apply')) {
	function rkrk_code_sync_apply($routeId, $cfg = null, $mode = 'mirror') {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$mode = rkrk_code_sync_mode($mode);
		if (!rkrk_unterhalt_sync_apply_allowed($cfg)) {
			return array(
				'ok' => false,
				'error' => 'Synchronisieren nur von test27 oder test. Vorschau bleibt erlaubt.',
				'items' => array(),
				'dest_only' => array(),
				'copied' => 0,
				'deleted' => 0,
				'failed' => array(),
			);
		}
		$routes = rkrk_code_sync_routes($cfg);
		if (isset($routes[$routeId]) && ($routes[$routeId]['kind'] ?? '') !== 'chain') {
			$here = rkrk_unterhalt_current_site_key($cfg);
			if ((string)($routes[$routeId]['to'] ?? '') === $here) {
				return array(
					'ok' => false,
					'error' => 'Ziel ist die aktuelle Instanz (' . $here . '). Von test27 oder test aus in der Kette weitergeben, nicht auf sich selbst kopieren.',
					'items' => array(),
					'dest_only' => array(),
					'copied' => 0,
					'deleted' => 0,
					'failed' => array(),
				);
			}
		}
		if (isset($routes[$routeId]) && ($routes[$routeId]['kind'] ?? '') === 'chain') {
			@set_time_limit(900);
			$copied = 0;
			$deleted = 0;
			$failed = array();
			$hopResults = array();
			foreach (rkrk_code_sync_chain_hops($routes[$routeId], $cfg) as $hopId) {
				$one = rkrk_code_sync_apply((string)$hopId, $cfg, $mode);
				$copied += (int)($one['copied'] ?? 0);
				$deleted += (int)($one['deleted'] ?? 0);
				if (!empty($one['failed']) && is_array($one['failed'])) {
					foreach ($one['failed'] as $rel) {
						$failed[] = (string)$hopId . ': ' . $rel;
					}
				}
				$hopResults[] = array(
					'id' => (string)$hopId,
					'label' => (string)($one['route']['label'] ?? $hopId),
					'ok' => !empty($one['ok']),
					'copied' => (int)($one['copied'] ?? 0),
					'deleted' => (int)($one['deleted'] ?? 0),
					'error' => (string)($one['error'] ?? ''),
				);
				if (empty($one['ok'])) {
					return array(
						'ok' => false,
						'error' => ((string)($one['route']['label'] ?? $hopId)) . ': ' . (string)($one['error'] ?? 'Sync fehlgeschlagen.'),
						'items' => array(),
						'dest_only' => array(),
						'copied' => $copied,
						'deleted' => $deleted,
						'failed' => $failed,
						'hops' => $hopResults,
						'route' => $routes[$routeId],
						'mode' => $mode,
					);
				}
			}
			return array(
				'ok' => true,
				'error' => '',
				'items' => array(),
				'dest_only' => array(),
				'copied' => $copied,
				'deleted' => $deleted,
				'failed' => $failed,
				'hops' => $hopResults,
				'route' => $routes[$routeId],
				'mode' => $mode,
			);
		}
		@set_time_limit(300);
		$plan = rkrk_code_sync_plan($routeId, $cfg);
		if (empty($plan['ok'])) {
			return $plan + array('copied' => 0, 'deleted' => 0, 'failed' => array());
		}
		$toAbs = rkrk_unterhalt_site_abs($plan['route']['to'], $cfg);
		$copied = 0;
		$deleted = 0;
		$failed = array();
		foreach ($plan['items'] as $item) {
			$from = rtrim(str_replace('\\', '/', $item['from_root']), '/') . '/' . $item['from_rel'];
			$to = rtrim($toAbs, '/') . '/' . $item['to_rel'];
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				$failed[] = $item['to_rel'];
				continue;
			}
			$copied++;
		}
		if ($mode === 'mirror') {
			foreach ($plan['dest_only'] as $drow) {
				$rel = rkrk_unterhalt_safe_rel((string)($drow['rel'] ?? ''));
				if ($rel === null || $rel === '') {
					continue;
				}
				$abs = rtrim($toAbs, '/') . '/' . $rel;
				if (!is_file($abs)) {
					continue;
				}
				if (rkrk_unterhalt_delete_dest_file($abs, $toAbs)) {
					$deleted++;
					rkrk_unterhalt_prune_empty_up(dirname($abs), $toAbs);
				} else {
					$failed[] = $rel;
				}
			}
		}
		$plan['copied'] = $copied;
		$plan['deleted'] = $deleted;
		$plan['mode'] = $mode;
		$plan['failed'] = $failed;
		if (($plan['route']['kind'] ?? '') === 'rkrs') {
			rkrk_code_sync_rkrs_remember(rkrk_code_sync_selected_rkrs_folders($cfg), $plan['items']);
		}
		return $plan;
	}
}
