<?php
/**
 * Catalog snapshots of the shared tree ../rkrk (webdaten, config, webcsv, data/*.json).
 * Not: Missionen JPG/KMZ. Restore always writes a safety snapshot first.
 */

include_once(__DIR__ . '/rkrk_unterhalt.php');

if (!function_exists('rkrk_catalog_backup_dir')) {
	function rkrk_catalog_backup_dir() {
		return rkrk_unterhalt_rkrk_data_root() . '/Backups/catalogs';
	}
}

if (!function_exists('rkrk_catalog_backup_allow_ext')) {
	function rkrk_catalog_backup_allow_ext() {
		return array('json', 'csv', 'kml');
	}
}

if (!function_exists('rkrk_catalog_backup_skip_dir')) {
	function rkrk_catalog_backup_skip_dir($name) {
		$n = strtolower((string)$name);
		if ($n === 'jsonfiles') {
			return false;
		}
		return in_array($n, array(
			'backups', 'sitebackups', 'archiv', '_history', '_tmp', '_temp',
			'belege', 'images', 'node_modules', '.git', '._dav',
			'xmlfiles', 'kmlfiles',
		), true) || strpos($n, 'csvfiles') === 0 || rkrk_unterhalt_should_skip_dir($name);
	}
}

if (!function_exists('rkrk_catalog_backup_includes')) {
	function rkrk_catalog_backup_includes($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$inc = isset($cfg['data_includes']) && is_array($cfg['data_includes'])
			? $cfg['data_includes']
			: array('webdaten', 'config', 'webcsv', 'data');
		$out = array();
		foreach ($inc as $rel) {
			$safe = rkrk_unterhalt_safe_rel($rel);
			if ($safe !== null && $safe !== '') {
				$out[] = $safe;
			}
		}
		return $out !== array() ? $out : array('webdaten', 'config');
	}
}

if (!function_exists('rkrk_catalog_backup_source_map')) {
	function rkrk_catalog_backup_source_map($cfg = null) {
		$root = rtrim(rkrk_unterhalt_rkrk_data_root($cfg), '/');
		$map = array();
		foreach (rkrk_catalog_backup_includes($cfg) as $rel) {
			$map[$rel] = $root . '/' . $rel;
		}
		return $map;
	}
}

if (!function_exists('rkrk_catalog_backup_mission_map')) {
	function rkrk_catalog_backup_mission_map($cfg = null) {
		$map = array();
		foreach (rkrk_unterhalt_mission_libs($cfg) as $lib) {
			if (empty($lib['include']) || empty($lib['path'])) {
				continue;
			}
			$map[$lib['path']] = rtrim(str_replace('\\', '/', $lib['abs']), '/');
		}
		return $map;
	}
}

if (!function_exists('rkrk_catalog_backup_restore_roots')) {
	function rkrk_catalog_backup_restore_roots($cfg = null) {
		$out = array();
		$dataRoot = rtrim(rkrk_unterhalt_rkrk_data_root($cfg), '/');
		foreach (rkrk_catalog_backup_includes($cfg) as $rel) {
			$out[$rel] = $dataRoot;
		}
		$rkropen = rkrk_unterhalt_rkropen_root();
		foreach (rkrk_catalog_backup_mission_map($cfg) as $folder => $abs) {
			$out[$folder] = $rkropen;
		}
		return $out;
	}
}

if (!function_exists('rkrk_catalog_backup_walk')) {
	function rkrk_catalog_backup_walk($abs, $rel, array &$out, $depth) {
		if ($depth > 16) {
			return;
		}
		$entries = @scandir($abs);
		if ($entries === false) {
			return;
		}
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$childAbs = rtrim(str_replace('\\', '/', $abs), '/') . '/' . $entry;
			$childRel = $rel === '' ? $entry : ($rel . '/' . $entry);
			if (is_dir($childAbs)) {
				if (rkrk_catalog_backup_skip_dir($entry)) {
					continue;
				}
				rkrk_catalog_backup_walk($childAbs, $childRel, $out, $depth + 1);
				continue;
			}
			if (!is_file($childAbs)) {
				continue;
			}
			$ext = strtolower((string)pathinfo($entry, PATHINFO_EXTENSION));
			if (!in_array($ext, rkrk_catalog_backup_allow_ext(), true)) {
				continue;
			}
			$out[$childRel] = $childAbs;
		}
	}
}

if (!function_exists('rkrk_catalog_backup_collect')) {
	function rkrk_catalog_backup_collect($cfg = null) {
		$out = array();
		foreach (rkrk_catalog_backup_source_map($cfg) as $prefix => $dir) {
			$dir = rtrim(str_replace('\\', '/', $dir), '/');
			if (!is_dir($dir)) {
				continue;
			}
			rkrk_catalog_backup_walk($dir, $prefix, $out, 0);
		}
		foreach (rkrk_catalog_backup_mission_map($cfg) as $prefix => $dir) {
			$dir = rtrim(str_replace('\\', '/', $dir), '/');
			if (!is_dir($dir)) {
				continue;
			}
			rkrk_catalog_backup_walk($dir, $prefix, $out, 0);
		}
		ksort($out, SORT_STRING);
		return $out;
	}
}

if (!function_exists('rkrk_catalog_backup_create')) {
	function rkrk_catalog_backup_create($user, $note = '', $kind = 'manual') {
		@set_time_limit(300);
		if (!rkrk_unterhalt_is_test27()) {
			return array('ok' => false, 'error' => 'Katalog-Sicherung in dieser Phase nur von test27.');
		}
		$base = rkrk_catalog_backup_dir();
		if (!rkrk_unterhalt_ensure_dir(dirname($base)) || !rkrk_unterhalt_ensure_dir($base)) {
			return array('ok' => false, 'error' => 'Backup-Ordner konnte nicht erstellt werden.');
		}
		$id = rkrk_unterhalt_new_snapshot_id($base);
		$dir = $base . '/' . $id;
		if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
			return array('ok' => false, 'error' => 'Sicherung konnte nicht angelegt werden.');
		}
		$files = rkrk_catalog_backup_collect();
		$bytes = 0;
		$copied = 0;
		foreach ($files as $rel => $from) {
			$to = $dir . '/' . str_replace('/', '/', $rel);
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				rkrk_unterhalt_delete_tree($dir);
				return array('ok' => false, 'error' => 'Datei konnte nicht gesichert werden: ' . $rel);
			}
			$sz = @filesize($to);
			$bytes += ($sz !== false) ? (int)$sz : 0;
			$copied++;
		}
		$cfg = rkrk_unterhalt_load();
		$manifest = array(
			'id' => $id,
			'created_at' => date('c'),
			'user' => (string)$user,
			'note' => (string)$note,
			'kind' => (string)$kind,
			'type' => 'catalog',
			'files' => $copied,
			'bytes' => $bytes,
		);
		rkrk_unterhalt_write_manifest($dir, $manifest);
		rkrk_unterhalt_prune_snapshots($base, (int)$cfg['keep_min'], (int)$cfg['retention_days']);
		$manifest['ok'] = true;
		$manifest['error'] = '';
		return $manifest;
	}
}

if (!function_exists('rkrk_catalog_backup_maybe_auto_daily')) {
	function rkrk_catalog_backup_maybe_auto_daily($user) {
		$today = date('Ymd');
		foreach (rkrk_catalog_backup_list() as $snap) {
			$id = (string)($snap['id'] ?? '');
			if (strpos($id, $today) === 0) {
				return array('ok' => true, 'skipped' => true);
			}
		}
		return rkrk_catalog_backup_create($user, 'Automatisch (täglich)', 'auto');
	}
}

if (!function_exists('rkrk_catalog_backup_list')) {
	function rkrk_catalog_backup_list() {
		return rkrk_unterhalt_list_snapshots(rkrk_catalog_backup_dir());
	}
}

if (!function_exists('rkrk_catalog_backup_restore')) {
	function rkrk_catalog_backup_restore($id, $user) {
		if (!rkrk_unterhalt_is_test27()) {
			return array('ok' => false, 'error' => 'Katalog-Restore in dieser Phase nur von test27.');
		}
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return array('ok' => false, 'error' => 'Ungültige Sicherung.');
		}
		$src = rkrk_catalog_backup_dir() . '/' . $id;
		if (!is_dir($src)) {
			return array('ok' => false, 'error' => 'Sicherung nicht gefunden.');
		}
		$safety = rkrk_catalog_backup_create($user, 'Vor Wiederherstellung von ' . $id, 'pre-restore');
		if (empty($safety['ok'])) {
			return array('ok' => false, 'error' => 'Sicherheitssicherung fehlgeschlagen: ' . ($safety['error'] ?? ''));
		}
		$roots = rkrk_catalog_backup_restore_roots();
		$restored = 0;
		foreach (rkrk_unterhalt_collect_files_in($src, true) as $rel) {
			$norm = str_replace('\\', '/', $rel);
			if ($norm === '' || strpos($norm, '..') !== false) {
				continue;
			}
			if (strcasecmp(basename($norm), 'manifest.json') === 0 && strpos($norm, '/') === false) {
				continue;
			}
			$prefix = explode('/', $norm, 2)[0];
			if (!isset($roots[$prefix])) {
				continue;
			}
			$ext = strtolower((string)pathinfo($norm, PATHINFO_EXTENSION));
			if (!in_array($ext, rkrk_catalog_backup_allow_ext(), true)) {
				continue;
			}
			$from = $src . '/' . $norm;
			$to = rtrim($roots[$prefix], '/') . '/' . $norm;
			if (!is_file($from)) {
				continue;
			}
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				return array('ok' => false, 'error' => 'Wiederherstellen fehlgeschlagen: ' . $rel, 'safety' => $safety['id'] ?? '');
			}
			$restored++;
		}
		return array('ok' => true, 'error' => '', 'restored' => $restored, 'safety' => $safety['id'] ?? '');
	}
}

if (!function_exists('rkrk_catalog_backup_delete')) {
	function rkrk_catalog_backup_delete($id) {
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return array('ok' => false, 'error' => 'Ungültige Sicherung.');
		}
		$dir = rkrk_catalog_backup_dir() . '/' . $id;
		if (!is_dir($dir)) {
			return array('ok' => false, 'error' => 'Sicherung nicht gefunden.');
		}
		rkrk_unterhalt_delete_tree($dir);
		return array('ok' => true, 'error' => '');
	}
}

if (!function_exists('rkrk_catalog_backup_download')) {
	function rkrk_catalog_backup_download($id) {
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return 'Ungültige Sicherung.';
		}
		$src = rkrk_catalog_backup_dir() . '/' . $id;
		if (!is_dir($src)) {
			return 'Sicherung nicht gefunden.';
		}
		return rkrk_unterhalt_zip_snapshot($src, 'rkrk-catalog-' . $id . '.zip');
	}
}

if (!function_exists('rkrk_catalog_backup_restore_upload')) {
	function rkrk_catalog_backup_restore_upload(array $file, $user) {
		$err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
		if ($err !== UPLOAD_ERR_OK) {
			return array('ok' => false, 'error' => 'Upload fehlgeschlagen (Code ' . $err . ').');
		}
		$tmp = (string)($file['tmp_name'] ?? '');
		$orig = (string)($file['name'] ?? '');
		if ($tmp === '' || !is_uploaded_file($tmp)) {
			return array('ok' => false, 'error' => 'Keine gültige Upload-Datei.');
		}
		if (strtolower((string)pathinfo($orig, PATHINFO_EXTENSION)) !== 'zip') {
			return array('ok' => false, 'error' => 'Nur ZIP-Dateien sind erlaubt.');
		}
		$base = rkrk_catalog_backup_dir();
		if (!rkrk_unterhalt_ensure_dir($base)) {
			return array('ok' => false, 'error' => 'Backup-Ordner konnte nicht erstellt werden.');
		}
		$workId = 'upload-' . date('Ymd-His');
		$work = $base . '/_tmp-' . $workId;
		if (!@mkdir($work, 0775, true) && !is_dir($work)) {
			return array('ok' => false, 'error' => 'Temporärer Ordner fehlgeschlagen.');
		}
		$unzip = rkrk_unterhalt_unzip_to($tmp, $work);
		if ($unzip !== '') {
			rkrk_unterhalt_delete_tree($work);
			return array('ok' => false, 'error' => $unzip);
		}
		$imported = $base . '/' . $workId;
		@rename($work, $imported);
		$stats = rkrk_unterhalt_dir_stats($imported);
		rkrk_unterhalt_write_manifest($imported, array(
			'id' => $workId,
			'created_at' => date('c'),
			'user' => (string)$user,
			'note' => 'Upload: ' . $orig,
			'kind' => 'upload',
			'type' => 'catalog',
			'files' => $stats['files'],
			'bytes' => $stats['bytes'],
		));
		return rkrk_catalog_backup_restore($workId, $user);
	}
}

if (!function_exists('rkrk_catalog_history_dirs')) {
	function rkrk_catalog_history_dirs() {
		global $historyfilelocation, $jsonMissionLocation;
		$dirs = array();
		if (!empty($historyfilelocation)) {
			$dirs[] = rtrim(str_replace('\\', '/', (string)$historyfilelocation), '/') . '/';
		}
		$root = rtrim(str_replace('\\', '/', rkrk_unterhalt_rkrk_data_root()), '/');
		if ($root !== '') {
			$dirs[] = $root . '/webdaten/_history/';
			$dirs[] = $root . '/config/_history/';
		}
		if (!empty($jsonMissionLocation)) {
			$dirs[] = rtrim(str_replace('\\', '/', (string)$jsonMissionLocation), '/') . '/_history/';
		}
		$seen = array();
		$out = array();
		foreach ($dirs as $d) {
			$key = strtolower($d);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$out[] = $d;
		}
		return $out;
	}
}

if (!function_exists('rkrk_catalog_history_parse')) {
	function rkrk_catalog_history_parse($name) {
		if (!preg_match('/^(\d{14})_(.+\.json)$/i', $name, $m)) {
			return null;
		}
		$stamp = $m[1];
		$ts = strtotime(
			substr($stamp, 0, 4) . '-' . substr($stamp, 4, 2) . '-' . substr($stamp, 6, 2) . ' '
			. substr($stamp, 8, 2) . ':' . substr($stamp, 10, 2) . ':' . substr($stamp, 12, 2)
		);
		return array(
			'stamp' => $stamp,
			'original' => $m[2],
			'label' => $ts !== false ? date('d.m.Y H:i:s', $ts) : $stamp,
		);
	}
}

if (!function_exists('rkrk_catalog_history_list')) {
	function rkrk_catalog_history_list($limit = 200) {
		$out = array();
		foreach (rkrk_catalog_history_dirs() as $dir) {
			if (!is_dir($dir)) {
				continue;
			}
			$entries = @scandir($dir);
			if ($entries === false) {
				continue;
			}
			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') {
					continue;
				}
				$full = $dir . $entry;
				if (!is_file($full)) {
					continue;
				}
				$parsed = rkrk_catalog_history_parse($entry);
				if ($parsed === null) {
					continue;
				}
				$sz = @filesize($full);
				$mtime = @filemtime($full);
				$out[] = array(
					'name' => $entry,
					'dir' => $dir,
					'original' => $parsed['original'],
					'stamp' => $parsed['stamp'],
					'label' => $parsed['label'],
					'bytes' => $sz !== false ? (int)$sz : 0,
					'mtime' => $mtime !== false ? (int)$mtime : 0,
				);
			}
		}
		usort($out, function ($a, $b) {
			return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
		});
		if ($limit > 0 && count($out) > $limit) {
			$out = array_slice($out, 0, $limit);
		}
		return $out;
	}
}

if (!function_exists('rkrk_catalog_history_restore')) {
	function rkrk_catalog_history_restore($dir, $name, $user) {
		if (!rkrk_unterhalt_is_test27()) {
			return array('ok' => false, 'error' => 'History-Restore in dieser Phase nur von test27.');
		}
		$name = basename((string)$name);
		if ($name === '' || strpos($name, '..') !== false) {
			return array('ok' => false, 'error' => 'Ungültiger Archivname.');
		}
		$allowed = false;
		$src = '';
		foreach (rkrk_catalog_history_dirs() as $hist) {
			if (rtrim(str_replace('\\', '/', $dir), '/') . '/' === $hist || $dir === $hist) {
				$allowed = true;
				$src = $hist . $name;
				break;
			}
		}
		if (!$allowed) {
			return array('ok' => false, 'error' => 'History-Ordner nicht erlaubt.');
		}
		if (!is_file($src)) {
			return array('ok' => false, 'error' => 'Archivdatei nicht gefunden.');
		}
		$parsed = rkrk_catalog_history_parse($name);
		if ($parsed === null) {
			return array('ok' => false, 'error' => 'Archivdatei nicht erkannt.');
		}
		$safety = rkrk_catalog_backup_create($user, 'Vor Datei-Restore: ' . $parsed['original'], 'pre-restore');
		if (empty($safety['ok'])) {
			return array('ok' => false, 'error' => 'Sicherheitssicherung fehlgeschlagen: ' . ($safety['error'] ?? ''));
		}
		$orig = $parsed['original'];
		$root = rtrim(rkrk_unterhalt_rkrk_data_root(), '/');
		$configFile = $root . '/config/' . $orig;
		$dest = $root . '/webdaten/' . $orig;
		if (is_file($configFile) || strpos(str_replace('\\', '/', $dir), '/config/') !== false) {
			$dest = $configFile;
		}
		if (!rkrk_unterhalt_copy_file($src, $dest)) {
			return array('ok' => false, 'error' => 'Zurücksetzen fehlgeschlagen.', 'safety' => $safety['id'] ?? '');
		}
		return array('ok' => true, 'error' => '', 'restored' => $orig, 'safety' => $safety['id'] ?? '');
	}
}
