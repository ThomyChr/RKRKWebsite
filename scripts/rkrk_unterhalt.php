<?php
/**
 * Unterhalt: site registry, folder matrix, skip lists, shared snapshot helpers.
 * Overlay: rkrk/config/unterhalt.json (created on first save).
 */

if (!function_exists('rkrk_unterhalt_defaults')) {
	function rkrk_unterhalt_defaults() {
		$targets = array('test' => 1, 'v2' => 1, 'pub' => 1, 'rkrs' => 0);
		$rkrs_on = array('test' => 0, 'v2' => 0, 'pub' => 0, 'rkrs' => 1);
		$both_map = array('test' => 1, 'v2' => 1, 'pub' => 1, 'rkrs' => 1);
		return array(
			'sites' => array(
				'test27' => array('label' => 'Dev', 'path' => 'test27', 'role' => 'source', 'active' => true),
				'test' => array('label' => 'Staging', 'path' => 'test', 'role' => 'staging', 'active' => true),
				'v2' => array('label' => 'Intern', 'path' => 'v2', 'role' => 'intern', 'active' => true),
				'pub' => array('label' => 'Public', 'path' => 'pub', 'role' => 'public', 'active' => true),
				'rkrs' => array('label' => 'rkrs-Module', 'path' => 'rkrs', 'role' => 'spinoff', 'active' => true),
			),
			'chain' => array('test27', 'test', 'v2', 'pub'),
			'rkrs_source' => 'test27',
			'rkrs_last' => array(),
			'sync_apply' => true,
			'sync_mode' => 'mirror',
			'site_backup_sites' => array('test27', 'test'),
			'data_path' => 'rkrk',
			'data_includes' => array('webdaten', 'config', 'webcsv', 'data'),
			'retention_days' => 180,
			'keep_min' => 2,
			'folders' => array(
				'root' => array(
					'label' => 'Root PHP (rkrk*)',
					'kind' => 'root_php',
					'rel' => '',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'scripts' => array(
					'label' => 'scripts/',
					'kind' => 'dir',
					'rel' => 'scripts',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'newsletter' => array(
					'label' => 'newsletter/',
					'kind' => 'dir',
					'rel' => 'newsletter',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'template' => array(
					'label' => 'template/',
					'kind' => 'dir',
					'rel' => 'template',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'map' => array(
					'label' => 'map/',
					'kind' => 'dir',
					'rel' => 'map',
					'rkrs_dest' => 'map',
					'targets' => $both_map,
					'rkrs_source' => '',
					'active' => true,
				),
				'mapII' => array(
					'label' => 'fe/',
					'kind' => 'dir',
					'rel' => 'fe',
					'rkrs_dest' => 'mapII',
					'targets' => $both_map,
					'rkrs_source' => '',
					'active' => true,
				),
				'assess' => array(
					'label' => 'assess/',
					'kind' => 'dir',
					'rel' => 'assess',
					'rkrs_dest' => 'assess/public',
					'targets' => $both_map,
					'rkrs_source' => '',
					'active' => true,
				),
				'exam' => array(
					'label' => 'exam/',
					'kind' => 'dir',
					'rel' => 'exam',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'quiz' => array(
					'label' => 'quiz/ (ohne Medien)',
					'kind' => 'dir',
					'rel' => 'quiz',
					'deny_ext' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'avi'),
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'rt' => array(
					'label' => 'rt/ (ohne vendor)',
					'kind' => 'dir',
					'rel' => 'rt',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'ar' => array(
					'label' => 'ar/',
					'kind' => 'dir',
					'rel' => 'ar',
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'images' => array(
					'label' => 'images/ (UI)',
					'kind' => 'dir',
					'rel' => 'images',
					'allow_ext' => array('png', 'svg', 'ico', 'gif', 'webp'),
					'targets' => $targets,
					'rkrs_source' => '',
					'active' => true,
				),
				'calc' => array(
					'label' => 'Calc-Seiten',
					'kind' => 'calc',
					'rel' => '',
					'rkrs_dest' => 'calc',
					'targets' => $rkrs_on,
					'rkrs_source' => '',
					'active' => true,
				),
			),
		);
	}
}

if (!function_exists('rkrk_unterhalt_load')) {
	function rkrk_unterhalt_load() {
		$defaults = rkrk_unterhalt_defaults();
		$saved = function_exists('rkrk_config_load') ? rkrk_config_load('unterhalt') : array();
		if (!is_array($saved) || $saved === array()) {
			return $defaults;
		}
		if (!empty($saved['sites']) && is_array($saved['sites'])) {
			foreach ($defaults['sites'] as $key => $row) {
				if (!isset($saved['sites'][$key]) || !is_array($saved['sites'][$key])) {
					continue;
				}
				$ov = $saved['sites'][$key];
				foreach (array('label', 'path', 'role') as $f) {
					if (isset($ov[$f]) && is_string($ov[$f])) {
						$defaults['sites'][$key][$f] = $ov[$f];
					}
				}
				if (array_key_exists('active', $ov)) {
					$defaults['sites'][$key]['active'] = !empty($ov['active']);
				}
			}
		}
		if (!empty($saved['chain']) && is_array($saved['chain'])) {
			$chain = array();
			foreach ($saved['chain'] as $k) {
				$k = (string)$k;
				if (isset($defaults['sites'][$k])) {
					$chain[] = $k;
				}
			}
			if (count($chain) >= 2) {
				$defaults['chain'] = $chain;
			}
		}
		if (!empty($saved['rkrs_source']) && in_array((string)$saved['rkrs_source'], array('test27', 'test'), true)) {
			$defaults['rkrs_source'] = (string)$saved['rkrs_source'];
		}
		if (!empty($saved['rkrs_last']) && is_array($saved['rkrs_last'])) {
			$last = array();
			foreach ($saved['rkrs_last'] as $fid => $row) {
				$fid = (string)$fid;
				if ($fid === '' || !is_array($row) || !isset($defaults['folders'][$fid])) {
					continue;
				}
				$at = trim((string)($row['at'] ?? ''));
				if ($at === '') {
					continue;
				}
				$last[$fid] = array(
					'at' => $at,
					'user' => (string)($row['user'] ?? ''),
					'copied' => (int)($row['copied'] ?? 0),
				);
			}
			$defaults['rkrs_last'] = $last;
		}
		if (array_key_exists('sync_apply', $saved)) {
			$defaults['sync_apply'] = !empty($saved['sync_apply']);
		}
		if (!empty($saved['site_backup_sites']) && is_array($saved['site_backup_sites'])) {
			$sites = array();
			foreach ($saved['site_backup_sites'] as $sk) {
				$sk = (string)$sk;
				if (isset($defaults['sites'][$sk])) {
					$sites[] = $sk;
				}
			}
			if ($sites !== array()) {
				$defaults['site_backup_sites'] = $sites;
			}
		}
		if (isset($saved['data_path']) && is_string($saved['data_path'])) {
			$safe = rkrk_unterhalt_safe_rel($saved['data_path']);
			if ($safe !== null && $safe !== '') {
				$defaults['data_path'] = $safe;
			}
		}
		if (!empty($saved['data_includes']) && is_array($saved['data_includes'])) {
			$inc = array();
			foreach ($saved['data_includes'] as $item) {
				$safe = rkrk_unterhalt_safe_rel($item);
				if ($safe !== null && $safe !== '') {
					$inc[] = $safe;
				}
			}
			if ($inc !== array()) {
				$defaults['data_includes'] = $inc;
			}
		}
		if (isset($saved['mission_includes']) && is_array($saved['mission_includes'])) {
			$defaults['mission_includes'] = $saved['mission_includes'];
		}
		if (isset($saved['retention_days'])) {
			$days = (int)$saved['retention_days'];
			if ($days > 0) {
				$defaults['retention_days'] = $days;
			}
		}
		if (isset($saved['keep_min'])) {
			$min = (int)$saved['keep_min'];
			if ($min > 0) {
				$defaults['keep_min'] = $min;
			}
		}
		if (!empty($saved['folders']) && is_array($saved['folders'])) {
			foreach ($defaults['folders'] as $id => $row) {
				if (!isset($saved['folders'][$id]) || !is_array($saved['folders'][$id])) {
					continue;
				}
				$ov = $saved['folders'][$id];
				if (isset($ov['label']) && is_string($ov['label'])) {
					$defaults['folders'][$id]['label'] = $ov['label'];
				}
				if (isset($ov['rel']) && is_string($ov['rel'])) {
					$safe = rkrk_unterhalt_safe_rel($ov['rel']);
					if ($safe !== null) {
						$defaults['folders'][$id]['rel'] = $safe;
					}
				}
				if (isset($ov['rkrs_dest']) && is_string($ov['rkrs_dest'])) {
					$safe = rkrk_unterhalt_safe_rel($ov['rkrs_dest']);
					if ($safe !== null && $safe !== '') {
						$defaults['folders'][$id]['rkrs_dest'] = $safe;
					}
				}
				if (isset($ov['targets']) && is_array($ov['targets'])) {
					foreach ($defaults['folders'][$id]['targets'] as $t => $v) {
						if (array_key_exists($t, $ov['targets'])) {
							$defaults['folders'][$id]['targets'][$t] = !empty($ov['targets'][$t]) ? 1 : 0;
						}
					}
				}
				if (array_key_exists('rkrs_source', $ov)) {
					$src = trim((string)$ov['rkrs_source']);
					$defaults['folders'][$id]['rkrs_source'] = in_array($src, array('test27', 'test', ''), true) ? $src : '';
				}
				if (array_key_exists('active', $ov)) {
					$defaults['folders'][$id]['active'] = !empty($ov['active']);
				}
			}
		}
		if (isset($defaults['folders']['assess']) && is_array($defaults['folders']['assess'])) {
			$assessDest = rkrk_unterhalt_safe_rel($defaults['folders']['assess']['rkrs_dest'] ?? '');
			if ($assessDest === null || $assessDest === '' || $assessDest === 'assess') {
				$defaults['folders']['assess']['rkrs_dest'] = 'assess/public';
			}
		}
		return $defaults;
	}
}

if (!function_exists('rkrk_unterhalt_save')) {
	function rkrk_unterhalt_save(array $data) {
		if (!function_exists('rkrk_config_save')) {
			return 'Config-Schreiben nicht verfügbar.';
		}
		return rkrk_config_save('unterhalt', $data);
	}
}

if (!function_exists('rkrk_unterhalt_safe_rel')) {
	function rkrk_unterhalt_safe_rel($rel) {
		$rel = str_replace('\\', '/', trim((string)$rel));
		$rel = trim($rel, '/');
		if ($rel === '.') {
			return '';
		}
		if (strpos($rel, '..') !== false) {
			return null;
		}
		if (preg_match('#^[a-zA-Z]:#', $rel) || strpos($rel, '//') === 0) {
			return null;
		}
		return $rel;
	}
}

if (!function_exists('rkrk_unterhalt_rkropen_root')) {
	function rkrk_unterhalt_rkropen_root() {
		$candidates = array();
		$candidates[] = dirname(__DIR__, 2);
		$candidates[] = dirname(__DIR__) . '/..';
		global $RKRKRelWebLocation;
		$rel = rtrim(str_replace('\\', '/', (string)($RKRKRelWebLocation ?? '')), '/');
		if ($rel !== '') {
			$candidates[] = $rel;
		}
		foreach ($candidates as $c) {
			$rp = @realpath($c);
			if ($rp !== false && is_dir($rp)) {
				return str_replace('\\', '/', $rp);
			}
		}
		return str_replace('\\', '/', dirname(__DIR__, 2));
	}
}

if (!function_exists('rkrk_unterhalt_rkrk_data_root')) {
	function rkrk_unterhalt_rkrk_data_root($cfg = null) {
		if ($cfg === null && function_exists('rkrk_unterhalt_load')) {
			$cfg = rkrk_unterhalt_load();
		}
		if (!is_array($cfg)) {
			$cfg = array();
		}
		$rel = rkrk_unterhalt_safe_rel($cfg['data_path'] ?? 'rkrk');
		if ($rel === null || $rel === '') {
			$rel = 'rkrk';
		}
		$candidates = array();
		$candidates[] = dirname(__DIR__) . '/../' . $rel;
		$candidates[] = dirname(__DIR__, 2) . '/' . $rel;
		$candidates[] = rkrk_unterhalt_rkropen_root() . '/' . $rel;
		global $RKRKRelRKRKDataLocation;
		$legacy = rtrim(str_replace('\\', '/', (string)($RKRKRelRKRKDataLocation ?? '')), '/');
		if ($legacy !== '') {
			$candidates[] = $legacy;
		}
		foreach ($candidates as $c) {
			$rp = @realpath($c);
			if ($rp !== false && is_dir($rp)) {
				return str_replace('\\', '/', $rp);
			}
		}
		return str_replace('\\', '/', dirname(__DIR__) . '/../' . $rel);
	}
}

if (!function_exists('rkrk_unterhalt_data_probe')) {
	function rkrk_unterhalt_data_probe($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$root = rkrk_unterhalt_rkrk_data_root($cfg);
		$includes = isset($cfg['data_includes']) && is_array($cfg['data_includes'])
			? $cfg['data_includes']
			: array('webdaten', 'config', 'webcsv', 'data');
		$rows = array();
		foreach ($includes as $rel) {
			$safe = rkrk_unterhalt_safe_rel($rel);
			if ($safe === null || $safe === '') {
				continue;
			}
			$dir = $root . '/' . $safe;
			$rows[] = array(
				'rel' => $safe,
				'abs' => $dir,
				'exists' => is_dir($dir),
			);
		}
		$dataPath = rkrk_unterhalt_safe_rel($cfg['data_path'] ?? 'rkrk');
		if ($dataPath === null || $dataPath === '') {
			$dataPath = 'rkrk';
		}
		return array(
			'root' => $root,
			'exists' => is_dir($root),
			'from_web' => '../' . $dataPath,
			'rows' => $rows,
		);
	}
}

if (!function_exists('rkrk_unterhalt_mission_libs')) {
	function rkrk_unterhalt_mission_libs($cfg = null) {
		global $MissionDroneTypes;
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$saved = isset($cfg['mission_includes']) && is_array($cfg['mission_includes']) ? $cfg['mission_includes'] : null;
		$hasSaved = is_array($saved);
		$rkropen = rkrk_unterhalt_rkropen_root();
		$out = array();
		if (!isset($MissionDroneTypes) || !is_array($MissionDroneTypes)) {
			return $out;
		}
		foreach ($MissionDroneTypes as $mdb => $row) {
			if (!is_array($row)) {
				continue;
			}
			$path = rkrk_unterhalt_safe_rel($row['MissionLocation'] ?? '');
			if ($path === null || $path === '') {
				continue;
			}
			$doc = rkrk_unterhalt_safe_rel($row['DocLocation'] ?? '');
			if ($doc === null) {
				$doc = '';
			}
			$include = true;
			if ($hasSaved) {
				if (array_key_exists($mdb, $saved)) {
					$include = !empty($saved[$mdb]);
				} else {
					$include = in_array((string)$mdb, $saved, true);
				}
			}
			$abs = $rkropen . '/' . $path;
			$out[(string)$mdb] = array(
				'mdb' => (string)$mdb,
				'name' => (string)($row['Name'] ?? $mdb),
				'path' => $path,
				'doc' => $doc,
				'active' => !empty($row['active']),
				'include' => $include,
				'abs' => $abs,
				'exists' => is_dir($abs),
			);
		}
		return $out;
	}
}

if (!function_exists('rkrk_unterhalt_site_abs')) {
	function rkrk_unterhalt_site_abs($key, $cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$key = (string)$key;
		if (!isset($cfg['sites'][$key]) || empty($cfg['sites'][$key]['active'])) {
			return '';
		}
		$rel = rkrk_unterhalt_safe_rel($cfg['sites'][$key]['path'] ?? $key);
		if ($rel === null || $rel === '') {
			return '';
		}
		$root = rkrk_unterhalt_rkropen_root();
		$abs = $root . '/' . $rel;
		$rp = @realpath($abs);
		return $rp !== false ? str_replace('\\', '/', $rp) : $abs;
	}
}

if (!function_exists('rkrk_unterhalt_current_site_key')) {
	function rkrk_unterhalt_current_site_key($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		$uri = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
		$keys = array('test27', 'v2', 'pub', 'test', 'rkrs');
		foreach ($keys as $key) {
			if (preg_match('#/' . preg_quote($key, '#') . '/#', $uri)) {
				return $key;
			}
		}
		$folder = strtolower(basename(str_replace('\\', '/', dirname(__DIR__))));
		if (isset($cfg['sites'][$folder])) {
			return $folder;
		}
		return 'test27';
	}
}

if (!function_exists('rkrk_unterhalt_is_test27')) {
	function rkrk_unterhalt_is_test27($cfg = null) {
		return rkrk_unterhalt_current_site_key($cfg) === 'test27';
	}
}

if (!function_exists('rkrk_unterhalt_control_sites')) {
	function rkrk_unterhalt_control_sites() {
		return array('test27', 'test');
	}
}

if (!function_exists('rkrk_unterhalt_is_control_site')) {
	function rkrk_unterhalt_is_control_site($cfg = null) {
		return in_array(rkrk_unterhalt_current_site_key($cfg), rkrk_unterhalt_control_sites(), true);
	}
}

if (!function_exists('rkrk_unterhalt_site_backup_allowed')) {
	function rkrk_unterhalt_site_backup_allowed($siteKey = '', $cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		if ($siteKey === '') {
			$siteKey = rkrk_unterhalt_current_site_key($cfg);
		}
		if (in_array((string)$siteKey, rkrk_unterhalt_control_sites(), true)) {
			return true;
		}
		$allow = isset($cfg['site_backup_sites']) && is_array($cfg['site_backup_sites'])
			? $cfg['site_backup_sites']
			: rkrk_unterhalt_control_sites();
		return in_array((string)$siteKey, $allow, true);
	}
}

if (!function_exists('rkrk_unterhalt_sync_apply_allowed')) {
	function rkrk_unterhalt_sync_apply_allowed($cfg = null) {
		if ($cfg === null) {
			$cfg = rkrk_unterhalt_load();
		}
		return !empty($cfg['sync_apply']) && rkrk_unterhalt_is_control_site($cfg);
	}
}

if (!function_exists('rkrk_unterhalt_user')) {
	function rkrk_unterhalt_user() {
		global $SessNme_RegisteredInitialen, $SessNme_RegisteredUser;
		if (!empty($SessNme_RegisteredInitialen) && !empty($_SESSION[$SessNme_RegisteredInitialen])) {
			return (string)$_SESSION[$SessNme_RegisteredInitialen];
		}
		if (!empty($SessNme_RegisteredUser) && !empty($_SESSION[$SessNme_RegisteredUser])) {
			return (string)$_SESSION[$SessNme_RegisteredUser];
		}
		return 'admin';
	}
}

if (!function_exists('rkrk_unterhalt_skip_dir_names')) {
	function rkrk_unterhalt_skip_dir_names() {
		return array(
			'kmlfiles', 'tmp', '._dav', '.git', 'node_modules', 'vendor',
			'data', 'results', 'jsonfiles', 'webdaten', 'missionen', '_history', '_log',
			'_deleted', '_temp', 'backups', 'sitebackups', 'archiv',
			'volume1', '__macosx', '@eadir',
		);
	}
}

if (!function_exists('rkrk_unterhalt_allow_ext')) {
	function rkrk_unterhalt_allow_ext() {
		return array(
			'php', 'css', 'js', 'json', 'txt', 'svg', 'png', 'ico', 'gif', 'webp',
			'htaccess', 'map', 'md', 'htm', 'html',
		);
	}
}

if (!function_exists('rkrk_unterhalt_skip_file_names')) {
	function rkrk_unterhalt_skip_file_names() {
		return array(
			'sync.log', '.ds_store', 'thumbs.db', 'desktop.ini',
			// Live-geführt von rehkitzrettung.ch → ./rkrs; nie unbewusst spiegeln.
			'pilotenliste.json',
			'pilotlistfull.json',
			'teilnehmerliste_config.json',
			'trainingstatus_config.json',
		);
	}
}

if (!function_exists('rkrk_unterhalt_should_skip_dir')) {
	function rkrk_unterhalt_should_skip_dir($name) {
		$n = strtolower((string)$name);
		if (in_array($n, rkrk_unterhalt_skip_dir_names(), true)) {
			return true;
		}
		if (strpos($n, ' kopie') !== false || strpos((string)$name, ' Kopie') !== false) {
			return true;
		}
		if (preg_match('/\s-\s\d{8,}/', (string)$name)) {
			return true;
		}
		if (strncmp($n, '._', 2) === 0) {
			return true;
		}
		return false;
	}
}

if (!function_exists('rkrk_unterhalt_allow_file')) {
	function rkrk_unterhalt_allow_file($name, $allowExt = null, $denyExt = null) {
		$base = strtolower((string)$name);
		if (in_array($base, rkrk_unterhalt_skip_file_names(), true)) {
			return false;
		}
		if (strncmp($base, '._', 2) === 0) {
			return false;
		}
		if ($base === '.htaccess') {
			return true;
		}
		$ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
		if ($denyExt !== null && $ext !== '' && in_array($ext, $denyExt, true)) {
			return false;
		}
		$list = is_array($allowExt) && $allowExt !== array() ? $allowExt : rkrk_unterhalt_allow_ext();
		return $ext !== '' && in_array($ext, $list, true);
	}
}

if (!function_exists('rkrk_unterhalt_format_bytes')) {
	function rkrk_unterhalt_format_bytes($bytes) {
		$bytes = (int)$bytes;
		if ($bytes < 1024) {
			return $bytes . ' B';
		}
		if ($bytes < 1048576) {
			return number_format($bytes / 1024, 1, '.', "'") . ' KB';
		}
		return number_format($bytes / 1048576, 2, '.', "'") . ' MB';
	}
}

if (!function_exists('rkrk_unterhalt_ensure_htaccess')) {
	function rkrk_unterhalt_ensure_htaccess($dir) {
		$path = rtrim(str_replace('\\', '/', $dir), '/') . '/.htaccess';
		if (is_file($path)) {
			return;
		}
		@file_put_contents($path, "Require all denied\n");
	}
}

if (!function_exists('rkrk_unterhalt_ensure_dir')) {
	function rkrk_unterhalt_ensure_dir($dir) {
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		if ($dir === '') {
			return false;
		}
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				return false;
			}
		}
		rkrk_unterhalt_ensure_htaccess($dir);
		return true;
	}
}

if (!function_exists('rkrk_unterhalt_is_snapshot_id')) {
	function rkrk_unterhalt_is_snapshot_id($id) {
		$id = trim((string)$id);
		return (bool)preg_match('/^(\d{8}-\d{6}|upload-\d{8}-\d{6})(-[a-zA-Z0-9]+)?$/', $id);
	}
}

if (!function_exists('rkrk_unterhalt_new_snapshot_id')) {
	function rkrk_unterhalt_new_snapshot_id($dir) {
		$id = date('Ymd-His');
		$full = rtrim(str_replace('\\', '/', $dir), '/') . '/' . $id;
		if (is_dir($full)) {
			$id .= '-' . bin2hex(random_bytes(2));
		}
		return $id;
	}
}

if (!function_exists('rkrk_unterhalt_write_manifest')) {
	function rkrk_unterhalt_write_manifest($dir, array $manifest) {
		$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			return false;
		}
		$path = rtrim(str_replace('\\', '/', $dir), '/') . '/manifest.json';
		return @file_put_contents($path, $json . "\n") !== false;
	}
}

if (!function_exists('rkrk_unterhalt_read_manifest')) {
	function rkrk_unterhalt_read_manifest($dir) {
		$path = rtrim(str_replace('\\', '/', $dir), '/') . '/manifest.json';
		if (!is_file($path)) {
			return array();
		}
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '') {
			return array();
		}
		$data = json_decode($raw, true);
		return is_array($data) ? $data : array();
	}
}

if (!function_exists('rkrk_unterhalt_copy_file')) {
	function rkrk_unterhalt_copy_file($from, $to) {
		if (!is_file($from)) {
			return false;
		}
		$dir = dirname($to);
		if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
			return false;
		}
		if (is_file($to)) {
			@unlink($to);
		}
		$ok = @copy($from, $to);
		if (!$ok) {
			$raw = @file_get_contents($from);
			if ($raw === false || @file_put_contents($to, $raw) === false) {
				return false;
			}
		}
		$mtime = @filemtime($from);
		if ($mtime !== false) {
			@touch($to, $mtime);
		}
		return true;
	}
}

if (!function_exists('rkrk_unterhalt_dest_path_inside')) {
	function rkrk_unterhalt_dest_path_inside($abs, $root) {
		$root = rtrim(str_replace('\\', '/', (string)$root), '/');
		$abs = str_replace('\\', '/', (string)$abs);
		if ($root === '' || $abs === '') {
			return false;
		}
		$rr = @realpath($root);
		if ($rr === false) {
			return false;
		}
		$rr = rtrim(str_replace('\\', '/', $rr), '/');
		$rp = @realpath($abs);
		if ($rp === false) {
			return false;
		}
		$rp = str_replace('\\', '/', $rp);
		return ($rp === $rr || strpos($rp, $rr . '/') === 0);
	}
}

if (!function_exists('rkrk_unterhalt_delete_dest_file')) {
	function rkrk_unterhalt_delete_dest_file($abs, $root) {
		if (!is_file($abs) || !rkrk_unterhalt_dest_path_inside($abs, $root)) {
			return false;
		}
		return @unlink($abs);
	}
}

if (!function_exists('rkrk_unterhalt_prune_empty_up')) {
	function rkrk_unterhalt_prune_empty_up($dir, $stopAbs) {
		$dir = rtrim(str_replace('\\', '/', (string)$dir), '/');
		$stopAbs = rtrim(str_replace('\\', '/', (string)$stopAbs), '/');
		$rr = @realpath($stopAbs);
		if ($rr !== false) {
			$stopAbs = rtrim(str_replace('\\', '/', $rr), '/');
		}
		while ($dir !== '' && $dir !== $stopAbs && strpos($dir . '/', $stopAbs . '/') === 0) {
			if (!is_dir($dir) || !rkrk_unterhalt_dest_path_inside($dir, $stopAbs)) {
				break;
			}
			$rp = @realpath($dir);
			if ($rp === false) {
				break;
			}
			$dir = rtrim(str_replace('\\', '/', $rp), '/');
			if ($dir === $stopAbs) {
				break;
			}
			$entries = @scandir($dir);
			if ($entries === false) {
				break;
			}
			$rest = array_diff($entries, array('.', '..'));
			if ($rest !== array()) {
				break;
			}
			if (!@rmdir($dir)) {
				break;
			}
			$dir = dirname($dir);
		}
	}
}

if (!function_exists('rkrk_unterhalt_delete_tree')) {
	function rkrk_unterhalt_delete_tree($dir) {
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		if ($dir === '' || !is_dir($dir)) {
			return;
		}
		$entries = @scandir($dir);
		if ($entries === false) {
			return;
		}
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$full = $dir . '/' . $entry;
			if (is_dir($full)) {
				rkrk_unterhalt_delete_tree($full);
			} else {
				@unlink($full);
			}
		}
		@rmdir($dir);
	}
}

if (!function_exists('rkrk_unterhalt_collect_files_in')) {
	function rkrk_unterhalt_collect_files_in($root, $skipManifest = false) {
		$out = array();
		rkrk_unterhalt_walk_stored($root, '', $out, $skipManifest);
		sort($out, SORT_STRING);
		return $out;
	}
}

if (!function_exists('rkrk_unterhalt_walk_stored')) {
	function rkrk_unterhalt_walk_stored($abs, $rel, array &$out, $skipManifest) {
		$entries = @scandir($abs);
		if ($entries === false) {
			return;
		}
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$childRel = $rel === '' ? $entry : ($rel . '/' . $entry);
			$childAbs = rtrim(str_replace('\\', '/', $abs), '/') . '/' . $entry;
			if (is_dir($childAbs)) {
				rkrk_unterhalt_walk_stored($childAbs, $childRel, $out, $skipManifest);
				continue;
			}
			if (!is_file($childAbs)) {
				continue;
			}
			if ($skipManifest && strcasecmp($entry, 'manifest.json') === 0 && $rel === '') {
				continue;
			}
			$out[] = $childRel;
		}
	}
}

if (!function_exists('rkrk_unterhalt_dir_stats')) {
	function rkrk_unterhalt_dir_stats($dir) {
		$files = rkrk_unterhalt_collect_files_in($dir, false);
		$bytes = 0;
		$n = 0;
		$root = rtrim(str_replace('\\', '/', $dir), '/');
		foreach ($files as $rel) {
			if (strcasecmp(basename($rel), 'manifest.json') === 0 && strpos($rel, '/') === false) {
				continue;
			}
			$sz = @filesize($root . '/' . $rel);
			$bytes += ($sz !== false) ? (int)$sz : 0;
			$n++;
		}
		return array('files' => $n, 'bytes' => $bytes);
	}
}

if (!function_exists('rkrk_unterhalt_list_snapshots')) {
	function rkrk_unterhalt_list_snapshots($dir) {
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		if (!is_dir($dir)) {
			return array();
		}
		$entries = @scandir($dir);
		if ($entries === false) {
			return array();
		}
		$out = array();
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$full = $dir . '/' . $entry;
			if (!is_dir($full) || !rkrk_unterhalt_is_snapshot_id($entry)) {
				continue;
			}
			$man = rkrk_unterhalt_read_manifest($full);
			if ($man === array()) {
				$stats = rkrk_unterhalt_dir_stats($full);
				$man = array(
					'id' => $entry,
					'created_at' => date('c', (int)@filemtime($full)),
					'user' => '',
					'note' => '',
					'kind' => 'manual',
					'files' => $stats['files'],
					'bytes' => $stats['bytes'],
				);
			}
			$man['id'] = $entry;
			$out[] = $man;
		}
		usort($out, function ($a, $b) {
			return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
		});
		return $out;
	}
}

if (!function_exists('rkrk_unterhalt_prune_snapshots')) {
	function rkrk_unterhalt_prune_snapshots($dir, $keepMin, $retentionDays) {
		$snaps = rkrk_unterhalt_list_snapshots($dir);
		$keepMin = max(1, (int)$keepMin);
		$retentionDays = max(1, (int)$retentionDays);
		$cutoff = time() - ($retentionDays * 86400);
		$count = count($snaps);
		foreach ($snaps as $snap) {
			if ($count <= $keepMin) {
				break;
			}
			$id = (string)($snap['id'] ?? '');
			$kind = (string)($snap['kind'] ?? '');
			if ($kind === 'pre-restore') {
				continue;
			}
			$at = strtotime((string)($snap['created_at'] ?? ''));
			if ($at === false) {
				$at = 0;
			}
			if ($at >= $cutoff) {
				continue;
			}
			rkrk_unterhalt_delete_tree(rtrim(str_replace('\\', '/', $dir), '/') . '/' . $id);
			$count--;
		}
	}
}

if (!function_exists('rkrk_unterhalt_zip_snapshot')) {
	function rkrk_unterhalt_zip_snapshot($srcDir, $downloadName) {
		if (!class_exists('ZipArchive')) {
			return 'ZIP-Erweiterung fehlt auf dem Server.';
		}
		$srcDir = rtrim(str_replace('\\', '/', $srcDir), '/');
		$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'rkrk-' . bin2hex(random_bytes(4)) . '.zip';
		$zip = new ZipArchive();
		if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			return 'ZIP-Datei konnte nicht erzeugt werden.';
		}
		foreach (rkrk_unterhalt_collect_files_in($srcDir, false) as $rel) {
			$full = $srcDir . '/' . $rel;
			if (is_file($full)) {
				$zip->addFile($full, str_replace('\\', '/', $rel));
			}
		}
		$zip->close();
		if (!is_file($tmp)) {
			return 'ZIP-Datei ist leer.';
		}
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $downloadName . '"');
		header('Content-Length: ' . (string)filesize($tmp));
		header('Cache-Control: no-store');
		readfile($tmp);
		@unlink($tmp);
		exit;
	}
}

if (!function_exists('rkrk_unterhalt_unzip_to')) {
	function rkrk_unterhalt_unzip_to($tmpFile, $destDir) {
		if (!class_exists('ZipArchive')) {
			return 'ZIP-Erweiterung fehlt auf dem Server.';
		}
		$zip = new ZipArchive();
		if ($zip->open($tmpFile) !== true) {
			return 'ZIP konnte nicht gelesen werden.';
		}
		$count = $zip->numFiles;
		for ($i = 0; $i < $count; $i++) {
			$name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
			if ($name === '' || substr($name, -1) === '/') {
				continue;
			}
			if (strpos($name, '..') !== false || strpos($name, ':') !== false) {
				continue;
			}
			if (stripos($name, '__macosx/') === 0) {
				continue;
			}
			$base = strtolower(basename($name));
			if ($base === '.ds_store' || $base === 'thumbs.db') {
				continue;
			}
			$destRel = ltrim($name, '/');
			$dest = rtrim(str_replace('\\', '/', $destDir), '/') . '/' . $destRel;
			$destParent = dirname($dest);
			if (!is_dir($destParent) && !@mkdir($destParent, 0775, true) && !is_dir($destParent)) {
				$zip->close();
				return 'Entpacken fehlgeschlagen.';
			}
			$contents = $zip->getFromIndex($i);
			if ($contents === false) {
				continue;
			}
			@file_put_contents($dest, $contents);
		}
		$zip->close();
		return '';
	}
}

if (!function_exists('rkrk_unterhalt_index_folder')) {
	function rkrk_unterhalt_index_folder($siteAbs, array $folder) {
		$out = array();
		$kind = (string)($folder['kind'] ?? 'dir');
		$allow = isset($folder['allow_ext']) && is_array($folder['allow_ext']) ? $folder['allow_ext'] : null;
		$deny = isset($folder['deny_ext']) && is_array($folder['deny_ext']) ? $folder['deny_ext'] : null;
		$siteAbs = rtrim(str_replace('\\', '/', $siteAbs), '/');
		if ($kind === 'calc') {
			$names = array('rkrk_calc.php', 'rkrk_calc_flightparameter.php');
			foreach ($names as $name) {
				$full = $siteAbs . '/' . $name;
				if (is_file($full) && rkrk_unterhalt_allow_file($name, $allow, $deny)) {
					$out[$name] = rkrk_unterhalt_file_meta($full);
				}
			}
			$js = $siteAbs . '/scripts/calcpath.js';
			if (is_file($js)) {
				$out['scripts/calcpath.js'] = rkrk_unterhalt_file_meta($js);
			}
			return $out;
		}
		if ($kind === 'root_php') {
			$entries = @scandir($siteAbs);
			if ($entries === false) {
				return $out;
			}
			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') {
					continue;
				}
				$full = $siteAbs . '/' . $entry;
				if (!is_file($full)) {
					continue;
				}
				if (!preg_match('/^rkrk.*\.(php|css)$/i', $entry) && strcasecmp($entry, '.htaccess') !== 0) {
					continue;
				}
				if (!rkrk_unterhalt_allow_file($entry, $allow, $deny)) {
					continue;
				}
				$out[$entry] = rkrk_unterhalt_file_meta($full);
			}
			return $out;
		}
		$rel = rkrk_unterhalt_safe_rel($folder['rel'] ?? '');
		if ($rel === null) {
			return $out;
		}
		$abs = $rel === '' ? $siteAbs : ($siteAbs . '/' . $rel);
		if (!is_dir($abs)) {
			return $out;
		}
		rkrk_unterhalt_walk_code($abs, $rel, $out, $allow, $deny, 0);
		return $out;
	}
}

if (!function_exists('rkrk_unterhalt_file_meta')) {
	function rkrk_unterhalt_file_meta($full) {
		$mtime = @filemtime($full);
		$size = @filesize($full);
		$hash = @hash_file('sha1', $full);
		return array(
			'mtime' => $mtime !== false ? (int)$mtime : 0,
			'size' => $size !== false ? (int)$size : 0,
			'hash' => is_string($hash) ? $hash : '',
		);
	}
}

if (!function_exists('rkrk_unterhalt_walk_code')) {
	function rkrk_unterhalt_walk_code($abs, $rel, array &$out, $allow, $deny, $depth) {
		if ($depth > 24) {
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
			$isDir = is_dir($childAbs);
			if ($isDir) {
				if (rkrk_unterhalt_should_skip_dir($entry)) {
					continue;
				}
				rkrk_unterhalt_walk_code($childAbs, $childRel, $out, $allow, $deny, $depth + 1);
				continue;
			}
			if (!is_file($childAbs) || !rkrk_unterhalt_allow_file($entry, $allow, $deny)) {
				continue;
			}
			$normRel = str_replace('\\', '/', strtolower($childRel));
			$ext = strtolower((string)pathinfo($entry, PATHINFO_EXTENSION));
			if (preg_match('#(^|/)quiz/#', $normRel) && in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'avi'), true)) {
				continue;
			}
			$out[$childRel] = rkrk_unterhalt_file_meta($childAbs);
		}
	}
}

if (!function_exists('rkrk_unterhalt_index_site')) {
	function rkrk_unterhalt_index_site($siteAbs) {
		$out = array();
		$siteAbs = rtrim(str_replace('\\', '/', $siteAbs), '/');
		if (!is_dir($siteAbs)) {
			return $out;
		}
		rkrk_unterhalt_walk_code($siteAbs, '', $out, null, null, 0);
		return $out;
	}
}

if (!function_exists('rkrk_unterhalt_folder_for_target')) {
	function rkrk_unterhalt_folder_for_target(array $folder, $targetKey) {
		if (empty($folder['active'])) {
			return false;
		}
		$targets = isset($folder['targets']) && is_array($folder['targets']) ? $folder['targets'] : array();
		return !empty($targets[$targetKey]);
	}
}

if (!function_exists('rkrk_unterhalt_rkrs_source_for')) {
	function rkrk_unterhalt_rkrs_source_for(array $folder, array $cfg) {
		$src = trim((string)($folder['rkrs_source'] ?? ''));
		if ($src === '') {
			$src = (string)($cfg['rkrs_source'] ?? 'test27');
		}
		return in_array($src, array('test27', 'test'), true) ? $src : 'test27';
	}
}

if (!function_exists('rkrk_unterhalt_rkrs_dest_for')) {
	/** rkrs-Zielordner. Assess-Skripte liegen unter assess/public/, nicht assess/. */
	function rkrk_unterhalt_rkrs_dest_for($folderId, array $folder) {
		$id = (string)$folderId;
		$rel = rkrk_unterhalt_safe_rel($folder['rel'] ?? '');
		$dest = rkrk_unterhalt_safe_rel($folder['rkrs_dest'] ?? '');
		if ($id === 'assess' || $rel === 'assess') {
			if ($dest === null || $dest === '' || $dest === 'assess') {
				return 'assess/public';
			}
			return $dest;
		}
		if ($dest !== null && $dest !== '') {
			return $dest;
		}
		return ($rel !== null && $rel !== '') ? $rel : '';
	}
}

if (!function_exists('rkrk_unterhalt_h')) {
	function rkrk_unterhalt_h($s) {
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('rkrk_unterhalt_from_post')) {
	function rkrk_unterhalt_from_post(array $post) {
		$cfg = rkrk_unterhalt_load();
		// Keep sync_apply and site_backup_sites from saved config; this form must not enable Apply.
		$src = trim((string)($post['rkrs_source'] ?? 'test27'));
		$cfg['rkrs_source'] = in_array($src, array('test27', 'test'), true) ? $src : 'test27';
		if (isset($post['data_path'])) {
			$safe = rkrk_unterhalt_safe_rel($post['data_path']);
			if ($safe !== null && $safe !== '') {
				$cfg['data_path'] = $safe;
			}
		}
		$allowedInc = array('webdaten', 'config', 'webcsv', 'data');
		if (isset($post['data_includes']) && is_array($post['data_includes'])) {
			$inc = array();
			foreach ($post['data_includes'] as $item) {
				$safe = rkrk_unterhalt_safe_rel($item);
				if ($safe !== null && in_array($safe, $allowedInc, true)) {
					$inc[] = $safe;
				}
			}
			if ($inc !== array()) {
				$cfg['data_includes'] = $inc;
			}
		}
		if (!empty($post['mission_includes_posted'])) {
			$picked = array();
			if (isset($post['mission_includes']) && is_array($post['mission_includes'])) {
				foreach ($post['mission_includes'] as $mdb => $val) {
					$key = is_string($mdb) && !is_numeric($mdb) ? (string)$mdb : (string)$val;
					if ($key !== '') {
						$picked[$key] = 1;
					}
				}
			}
			$cfg['mission_includes'] = $picked;
		}
		if (isset($post['sites']) && is_array($post['sites'])) {
			foreach ($cfg['sites'] as $key => $row) {
				if (!isset($post['sites'][$key]) || !is_array($post['sites'][$key])) {
					continue;
				}
				$ov = $post['sites'][$key];
				if (isset($ov['label'])) {
					$cfg['sites'][$key]['label'] = trim((string)$ov['label']);
				}
				if (isset($ov['path'])) {
					$safe = rkrk_unterhalt_safe_rel($ov['path']);
					if ($safe !== null && $safe !== '') {
						$cfg['sites'][$key]['path'] = $safe;
					}
				}
				$cfg['sites'][$key]['active'] = !empty($ov['active']);
			}
		}
		if (isset($post['folders']) && is_array($post['folders'])) {
			foreach ($cfg['folders'] as $id => $row) {
				if (!isset($post['folders'][$id]) || !is_array($post['folders'][$id])) {
					$cfg['folders'][$id]['targets'] = array('test' => 0, 'v2' => 0, 'pub' => 0, 'rkrs' => 0);
					$cfg['folders'][$id]['active'] = false;
					continue;
				}
				$ov = $post['folders'][$id];
				$cfg['folders'][$id]['active'] = !empty($ov['active']);
				foreach (array('test', 'v2', 'pub', 'rkrs') as $t) {
					$cfg['folders'][$id]['targets'][$t] = !empty($ov['targets'][$t]) ? 1 : 0;
				}
				$fs = trim((string)($ov['rkrs_source'] ?? ''));
				$cfg['folders'][$id]['rkrs_source'] = in_array($fs, array('test27', 'test', ''), true) ? $fs : '';
			}
		}
		return $cfg;
	}
}