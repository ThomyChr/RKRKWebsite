<?php
/**
 * Website/code snapshot of the current web copy only (test27 / test / v2 / pub).
 * Stored in rkrk/SiteBackups/{siteKey}/. Restore only into the same instance.
 */

include_once(__DIR__ . '/rkrk_unterhalt.php');

if (!function_exists('rkrk_site_backup_dir')) {
	function rkrk_site_backup_dir($siteKey = '') {
		if ($siteKey === '') {
			$siteKey = rkrk_unterhalt_current_site_key();
		}
		return rkrk_unterhalt_rkrk_data_root() . '/SiteBackups/' . $siteKey;
	}
}

if (!function_exists('rkrk_site_backup_create')) {
	function rkrk_site_backup_create($user, $note = '', $kind = 'manual') {
		@set_time_limit(300);
		$cfg = rkrk_unterhalt_load();
		$siteKey = rkrk_unterhalt_current_site_key($cfg);
		if (!rkrk_unterhalt_site_backup_allowed($siteKey, $cfg)) {
			return array('ok' => false, 'error' => 'Website-Sicherung nur für test27 oder test (aktuell: ' . $siteKey . ').');
		}
		$siteAbs = rkrk_unterhalt_site_abs($siteKey, $cfg);
		if ($siteAbs === '' || !is_dir($siteAbs)) {
			return array('ok' => false, 'error' => 'Aktueller Webordner nicht gefunden: ' . $siteKey);
		}
		$base = rkrk_site_backup_dir($siteKey);
		if (!rkrk_unterhalt_ensure_dir(dirname($base)) || !rkrk_unterhalt_ensure_dir($base)) {
			return array('ok' => false, 'error' => 'Website-Backup-Ordner konnte nicht erstellt werden.');
		}
		$id = rkrk_unterhalt_new_snapshot_id($base);
		$dir = $base . '/' . $id;
		if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
			return array('ok' => false, 'error' => 'Website-Sicherung konnte nicht angelegt werden.');
		}
		$index = rkrk_unterhalt_index_site($siteAbs);
		$bytes = 0;
		$copied = 0;
		foreach ($index as $rel => $meta) {
			$from = $siteAbs . '/' . $rel;
			$to = $dir . '/' . $rel;
			if (!is_file($from)) {
				continue;
			}
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				rkrk_unterhalt_delete_tree($dir);
				return array('ok' => false, 'error' => 'Datei konnte nicht gesichert werden: ' . $rel);
			}
			$bytes += (int)($meta['size'] ?? 0);
			$copied++;
		}
		$manifest = array(
			'id' => $id,
			'created_at' => date('c'),
			'user' => (string)$user,
			'note' => (string)$note,
			'kind' => (string)$kind,
			'type' => 'site',
			'site' => $siteKey,
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

if (!function_exists('rkrk_site_backup_list')) {
	function rkrk_site_backup_list($siteKey = '') {
		if ($siteKey === '') {
			$siteKey = rkrk_unterhalt_current_site_key();
		}
		return rkrk_unterhalt_list_snapshots(rkrk_site_backup_dir($siteKey));
	}
}

if (!function_exists('rkrk_site_backup_restore')) {
	function rkrk_site_backup_restore($id, $user) {
		$cfg = rkrk_unterhalt_load();
		$siteKey = rkrk_unterhalt_current_site_key($cfg);
		if (!rkrk_unterhalt_site_backup_allowed($siteKey, $cfg)) {
			return array('ok' => false, 'error' => 'Website-Restore in dieser Phase nur für test27 (aktuell: ' . $siteKey . ').');
		}
		$siteAbs = rkrk_unterhalt_site_abs($siteKey, $cfg);
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return array('ok' => false, 'error' => 'Ungültige Sicherung.');
		}
		$src = rkrk_site_backup_dir($siteKey) . '/' . $id;
		if (!is_dir($src)) {
			return array('ok' => false, 'error' => 'Website-Sicherung nicht gefunden.');
		}
		$man = rkrk_unterhalt_read_manifest($src);
		$snapSite = (string)($man['site'] ?? $siteKey);
		if ($snapSite !== '' && $snapSite !== $siteKey) {
			return array('ok' => false, 'error' => 'Diese Website-Sicherung gehört zu ' . $snapSite . ', nicht zu ' . $siteKey . '.');
		}
		if ($siteAbs === '' || !is_dir($siteAbs)) {
			return array('ok' => false, 'error' => 'Aktueller Webordner nicht gefunden.');
		}
		$safety = rkrk_site_backup_create($user, 'Vor Wiederherstellung von ' . $id, 'pre-restore');
		if (empty($safety['ok'])) {
			return array('ok' => false, 'error' => 'Sicherheitssicherung fehlgeschlagen: ' . ($safety['error'] ?? ''));
		}
		$restored = 0;
		foreach (rkrk_unterhalt_collect_files_in($src, true) as $rel) {
			$norm = str_replace('\\', '/', $rel);
			if (strcasecmp(basename($norm), 'manifest.json') === 0 && strpos($norm, '/') === false) {
				continue;
			}
			if (strpos($norm, '..') !== false) {
				continue;
			}
			$from = $src . '/' . $norm;
			$to = $siteAbs . '/' . $norm;
			if (!is_file($from)) {
				continue;
			}
			$base = basename($norm);
			if (!rkrk_unterhalt_allow_file($base)) {
				continue;
			}
			if (!rkrk_unterhalt_copy_file($from, $to)) {
				return array('ok' => false, 'error' => 'Wiederherstellen fehlgeschlagen: ' . $norm, 'safety' => $safety['id'] ?? '');
			}
			$restored++;
		}
		return array('ok' => true, 'error' => '', 'restored' => $restored, 'safety' => $safety['id'] ?? '');
	}
}

if (!function_exists('rkrk_site_backup_delete')) {
	function rkrk_site_backup_delete($id) {
		$siteKey = rkrk_unterhalt_current_site_key();
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return array('ok' => false, 'error' => 'Ungültige Sicherung.');
		}
		$dir = rkrk_site_backup_dir($siteKey) . '/' . $id;
		if (!is_dir($dir)) {
			return array('ok' => false, 'error' => 'Website-Sicherung nicht gefunden.');
		}
		rkrk_unterhalt_delete_tree($dir);
		return array('ok' => true, 'error' => '');
	}
}

if (!function_exists('rkrk_site_backup_download')) {
	function rkrk_site_backup_download($id) {
		$siteKey = rkrk_unterhalt_current_site_key();
		$id = trim((string)$id);
		if (!rkrk_unterhalt_is_snapshot_id($id)) {
			return 'Ungültige Sicherung.';
		}
		$src = rkrk_site_backup_dir($siteKey) . '/' . $id;
		if (!is_dir($src)) {
			return 'Website-Sicherung nicht gefunden.';
		}
		return rkrk_unterhalt_zip_snapshot($src, 'rkrk-site-' . $siteKey . '-' . $id . '.zip');
	}
}

if (!function_exists('rkrk_site_backup_restore_upload')) {
	function rkrk_site_backup_restore_upload(array $file, $user) {
		$cfg = rkrk_unterhalt_load();
		$siteKey = rkrk_unterhalt_current_site_key($cfg);
		if (!rkrk_unterhalt_site_backup_allowed($siteKey, $cfg)) {
			return array('ok' => false, 'error' => 'Website-Restore in dieser Phase nur für test27 (aktuell: ' . $siteKey . ').');
		}
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
		$base = rkrk_site_backup_dir($siteKey);
		if (!rkrk_unterhalt_ensure_dir($base)) {
			return array('ok' => false, 'error' => 'Website-Backup-Ordner konnte nicht erstellt werden.');
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
			'type' => 'site',
			'site' => $siteKey,
			'files' => $stats['files'],
			'bytes' => $stats['bytes'],
		));
		return rkrk_site_backup_restore($workId, $user);
	}
}
