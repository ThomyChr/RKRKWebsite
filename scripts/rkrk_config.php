<?php
/**
 * M4 config overlay (MOD-040).
 *
 * PHP arrays remain the fallback. Optional JSON in the existing RKRK data tree
 * overlays editable fields only. Mission folder names and MDB keys stay frozen.
 *
 * Path: ../../rkropen/rkrk/config/{missions,regions,menu,branding}.json
 */

if (!function_exists('rkrk_config_dir')) {
	function rkrk_config_dir(): string {
		global $RKRKRelWebLocation;
		$base = function_exists('rkrk_str') ? rkrk_str($RKRKRelWebLocation ?? '') : (string)($RKRKRelWebLocation ?? '');
		if ($base === '') {
			$base = '../../rkropen/';
		}
		return $base . 'rkrk/config/';
	}
}

if (!function_exists('rkrk_config_frozen_mdb_keys')) {
	function rkrk_config_frozen_mdb_keys(): array {
		return array('3T', '4TD', '30T', 'M2', 'D');
	}
}

if (!function_exists('rkrk_config_mission_frozen_fields')) {
	function rkrk_config_mission_frozen_fields(): array {
		return array('MissionLocation', 'DocLocation');
	}
}

if (!function_exists('rkrk_config_region_frozen_fields')) {
	function rkrk_config_region_frozen_fields(): array {
		return array('Reg', 'Dir');
	}
}

if (!function_exists('rkrk_config_file')) {
	function rkrk_config_file(string $name): string {
		$safe = preg_replace('/[^a-z0-9_\-]/i', '', $name);
		return rkrk_config_dir() . $safe . '.json';
	}
}

if (!function_exists('rkrk_config_load')) {
	function rkrk_config_load(string $name): array {
		$path = rkrk_config_file($name);
		if (!is_file($path) || !is_readable($path)) {
			return array();
		}
		$raw = file_get_contents($path);
		if ($raw === false || $raw === '') {
			return array();
		}
		$data = function_exists('rkrk_json_decode_utf8')
			? rkrk_json_decode_utf8($raw)
			: json_decode($raw, true);
		return is_array($data) ? $data : array();
	}
}

if (!function_exists('rkrk_config_merge_assoc')) {
	function rkrk_config_merge_assoc(array $base, array $overlay, array $frozenFields = array()): array {
		foreach ($overlay as $key => $value) {
			if (!array_key_exists($key, $base)) {
				continue;
			}
			if (in_array((string)$key, $frozenFields, true)) {
				continue;
			}
			if (is_array($value) && is_array($base[$key])) {
				$base[$key] = rkrk_config_merge_assoc($base[$key], $value, $frozenFields);
			} else {
				$base[$key] = $value;
			}
		}
		return $base;
	}
}

if (!function_exists('rkrk_config_apply_missions')) {
	function rkrk_config_apply_missions(array &$missionDroneTypes): void {
		$overlay = rkrk_config_load('missions');
		if ($overlay === array()) {
			return;
		}
		$frozen = rkrk_config_mission_frozen_fields();
		foreach ($missionDroneTypes as $mdb => $row) {
			if (!isset($overlay[$mdb]) || !is_array($overlay[$mdb])) {
				continue;
			}
			$missionDroneTypes[$mdb] = rkrk_config_merge_assoc($row, $overlay[$mdb], $frozen);
			foreach ($frozen as $field) {
				if (isset($row[$field])) {
					$missionDroneTypes[$mdb][$field] = $row[$field];
				}
			}
		}
	}
}

if (!function_exists('rkrk_config_apply_regions')) {
	function rkrk_config_apply_regions(array &$regionSettings): void {
		$overlay = rkrk_config_load('regions');
		if ($overlay === array()) {
			return;
		}
		$frozen = rkrk_config_region_frozen_fields();
		foreach ($regionSettings as $reg => $row) {
			if (!isset($overlay[$reg]) || !is_array($overlay[$reg])) {
				continue;
			}
			$regionSettings[$reg] = rkrk_config_merge_assoc($row, $overlay[$reg], $frozen);
			foreach ($frozen as $field) {
				if (isset($row[$field])) {
					$regionSettings[$reg][$field] = $row[$field];
				}
			}
		}
	}
}

if (!function_exists('rkrk_config_sanitize_pilot_code')) {
	function rkrk_config_sanitize_pilot_code($code): string {
		$s = strtoupper(trim(function_exists('rkrk_str') ? rkrk_str($code) : (string)$code));
		if (!preg_match('/^[A-Z][A-Z0-9]{0,4}$/', $s)) {
			return '';
		}
		return $s;
	}
}

if (!function_exists('rkrk_config_sanitize_pilot_csv')) {
	function rkrk_config_sanitize_pilot_csv($value): string {
		$s = function_exists('rkrk_str') ? rkrk_str($value) : (string)$value;
		$parts = array();
		foreach (explode(',', $s) as $p) {
			$p = trim($p);
			if ($p === '') {
				continue;
			}
			if (!preg_match('/^[A-Za-z0-9_\-]{1,40}$/', $p)) {
				continue;
			}
			$parts[] = $p;
		}
		return implode(',', $parts);
	}
}

if (!function_exists('rkrk_config_sanitize_pilot_until')) {
	function rkrk_config_sanitize_pilot_until($value): string {
		$s = trim(function_exists('rkrk_str') ? rkrk_str($value) : (string)$value);
		if ($s === '') {
			return '';
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
			return '';
		}
		return $s;
	}
}

if (!function_exists('rkrk_config_pilot_from_overlay')) {
	function rkrk_config_pilot_from_overlay(array $base, array $over, string $code): array {
		$bool = function ($v, $default) {
			if ($v === null) {
				return $default;
			}
			if (is_bool($v)) {
				return $v;
			}
			$s = strtolower(trim((string)$v));
			if ($s === '' || $s === '0' || $s === 'false' || $s === 'off') {
				return false;
			}
			return true;
		};
		$row = $base;
		$row['Opt'] = $code;
		if (array_key_exists('Text', $over)) {
			$row['Text'] = function_exists('rkrk_str') ? rkrk_str($over['Text']) : (string)$over['Text'];
		}
		if (array_key_exists('Access', $over)) {
			$row['Access'] = rkrk_config_sanitize_pilot_csv($over['Access']);
		}
		if (array_key_exists('VisibleOn', $over)) {
			$row['VisibleOn'] = rkrk_config_sanitize_pilot_csv($over['VisibleOn']);
		}
		if (array_key_exists('PV', $over)) {
			$row['PV'] = rkrk_config_sanitize_pilot_csv($over['PV']);
		}
		if (array_key_exists('plan', $over)) {
			$row['plan'] = $bool($over['plan'], !empty($base['plan']));
		}
		if (array_key_exists('active', $over)) {
			$row['active'] = $bool($over['active'], !array_key_exists('active', $base) || !empty($base['active']));
		}
		if (array_key_exists('until', $over)) {
			$row['until'] = rkrk_config_sanitize_pilot_until($over['until']);
		}
		return $row;
	}
}

if (!function_exists('rkrk_config_apply_pilots')) {
	function rkrk_config_apply_pilots(array &$optionPilot): void {
		$overlay = rkrk_config_load('pilots');
		if ($overlay === array()) {
			return;
		}
		foreach ($optionPilot as $code => $row) {
			if (!isset($overlay[$code]) || !is_array($overlay[$code])) {
				continue;
			}
			$optionPilot[$code] = rkrk_config_pilot_from_overlay(is_array($row) ? $row : array(), $overlay[$code], (string)$code);
		}
		foreach ($overlay as $code => $row) {
			if (isset($optionPilot[$code]) || !is_array($row)) {
				continue;
			}
			$safe = rkrk_config_sanitize_pilot_code($code);
			if ($safe === '') {
				continue;
			}
			$optionPilot[$safe] = rkrk_config_pilot_from_overlay(array(
				'Opt' => $safe,
				'Text' => '',
				'Access' => '',
				'VisibleOn' => '',
				'PV' => '',
				'plan' => false,
				'active' => true,
				'until' => '',
			), $row, $safe);
		}
	}
}

if (!function_exists('rkrk_config_sanitize_pv_key')) {
	function rkrk_config_sanitize_pv_key($code): string {
		$s = strtolower(trim(function_exists('rkrk_str') ? rkrk_str($code) : (string)$code));
		if (!preg_match('/^[a-z0-9]{1,12}-[a-z0-9]{2,12}$/', $s)) {
			return '';
		}
		return $s;
	}
}

if (!function_exists('rkrk_config_sanitize_person_file')) {
	function rkrk_config_sanitize_person_file($value): string {
		$s = str_replace('\\', '/', function_exists('rkrk_str') ? rkrk_str($value) : (string)$value);
		$s = trim($s);
		if ($s === '') {
			return '';
		}
		$base = basename($s);
		if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-]{0,119}$/', $base)) {
			return '';
		}
		return $base;
	}
}

if (!function_exists('rkrk_config_person_fields')) {
	function rkrk_config_person_fields(): array {
		return array(
			'Name', 'Vorname', 'Tel', 'EASA_ID', 'EASA_BID', 'EASA_exp', 'Kat',
			'Pic', 'QR', 'RKRS_MA', 'STSAcc', 'Flugberechtigung', 'FlugzonenOption',
			'titel', 'country', 'verfied',
		);
	}
}

if (!function_exists('rkrk_config_person_from_overlay')) {
	function rkrk_config_person_from_overlay(array $base, array $over, string $pv): array {
		$row = $base;
		$fileFields = array('Pic' => true, 'QR' => true, 'RKRS_MA' => true, 'STSAcc' => true);
		foreach (rkrk_config_person_fields() as $field) {
			if (!array_key_exists($field, $over)) {
				continue;
			}
			if ($field === 'verfied') {
				$v = $over[$field];
				$row[$field] = !($v === false || $v === 0 || $v === '0' || $v === 'false' || $v === '');
				continue;
			}
			if (isset($fileFields[$field])) {
				$row[$field] = rkrk_config_sanitize_person_file($over[$field]);
				continue;
			}
			$row[$field] = function_exists('rkrk_str') ? rkrk_str($over[$field]) : (string)$over[$field];
		}
		return $row;
	}
}

if (!function_exists('rkrk_config_apply_personen')) {
	function rkrk_config_apply_personen(array &$personenDaten): void {
		$overlay = rkrk_config_load('personen');
		if ($overlay === array()) {
			return;
		}
		foreach ($personenDaten as $pv => $row) {
			if (!isset($overlay[$pv]) || !is_array($overlay[$pv])) {
				continue;
			}
			$personenDaten[$pv] = rkrk_config_person_from_overlay(is_array($row) ? $row : array(), $overlay[$pv], (string)$pv);
		}
		foreach ($overlay as $pv => $row) {
			if (isset($personenDaten[$pv]) || !is_array($row)) {
				continue;
			}
			$safe = rkrk_config_sanitize_pv_key($pv);
			if ($safe === '') {
				continue;
			}
			$personenDaten[$safe] = rkrk_config_person_from_overlay(array(), $row, $safe);
		}
	}
}

if (!function_exists('rkrk_config_apply_menu')) {
	function rkrk_config_apply_menu(array &$mainMenu): void {
		$overlay = rkrk_config_load('menu');
		if ($overlay === array()) {
			return;
		}
		foreach ($mainMenu as $key => $row) {
			if (!isset($overlay[$key]) || !is_array($overlay[$key])) {
				continue;
			}
			$allowed = array(
				'title' => true,
				'activ' => true,
				'sort' => true,
				'release' => true,
				'main_menu' => true,
				'image' => true,
				'image_small' => true,
				'users' => true,
			);
			foreach ($overlay[$key] as $field => $value) {
				if (!isset($allowed[$field])) {
					continue;
				}
				if ($field === 'activ') {
					$mainMenu[$key][$field] = !empty($value) && $value !== '0' && $value !== 'false';
				} elseif ($field === 'sort') {
					$mainMenu[$key][$field] = intval($value);
				} elseif ($field === 'image' || $field === 'image_small') {
					$icon = rkrk_config_sanitize_menu_icon($value);
					if ($icon !== '' && function_exists('rkrk_menu_icon_is_readable') && !rkrk_menu_icon_is_readable($icon)) {
						continue;
					}
					$mainMenu[$key][$field] = $icon;
				} elseif ($field === 'users') {
					$mainMenu[$key][$field] = rkrk_config_sanitize_menu_users($value);
				} else {
					$mainMenu[$key][$field] = $value;
				}
			}
		}
	}
}

if (!function_exists('rkrk_config_sanitize_menu_icon')) {
	function rkrk_config_sanitize_menu_icon($value): string {
		$s = str_replace('\\', '/', function_exists('rkrk_str') ? rkrk_str($value) : (string)$value);
		$s = trim($s);
		if ($s === '') {
			return '';
		}
		$base = basename($s);
		if (!preg_match('/^[A-Za-z0-9_\-]+\.png$/i', $base)) {
			return '';
		}
		return '/' . $base;
	}
}

if (!function_exists('rkrk_config_sanitize_menu_users')) {
	function rkrk_config_sanitize_menu_users($value): string {
		$s = function_exists('rkrk_str') ? rkrk_str($value) : (string)$value;
		$parts = array();
		foreach (explode(',', $s) as $p) {
			$p = trim(preg_replace('/\s+/', ' ', $p));
			if ($p === '') {
				continue;
			}
			if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} ._\-]{0,79}$/u', $p)) {
				continue;
			}
			$parts[] = $p;
		}
		return implode(', ', $parts);
	}
}

if (!function_exists('rkrk_config_menu_icon_dir')) {
	function rkrk_config_menu_icon_dir(): string {
		global $IconLoc;
		$dir = function_exists('rkrk_str') ? rkrk_str($IconLoc ?? 'images/23color') : (string)($IconLoc ?? 'images/23color');
		return rtrim(str_replace('\\', '/', $dir), '/');
	}
}

if (!function_exists('rkrk_config_menu_icon_web_base')) {
	function rkrk_config_menu_icon_web_base(): string {
		return rkrk_config_menu_icon_dir();
	}
}

if (!function_exists('rkrk_config_menu_icon_choices')) {
	function rkrk_config_menu_icon_choices(): array {
		global $MainMenu;
		$found = array();
		$dir = rkrk_config_menu_icon_dir();
		if (is_dir($dir)) {
			$files = @scandir($dir);
			if (is_array($files)) {
				foreach ($files as $f) {
					if (preg_match('/^[A-Za-z0-9_\-]+(?:_small)?\.png$/i', $f)) {
						$found['/' . $f] = true;
					}
				}
			}
		}
		if (isset($MainMenu) && is_array($MainMenu)) {
			foreach ($MainMenu as $row) {
				if (!is_array($row)) {
					continue;
				}
				$v = rkrk_config_sanitize_menu_icon($row['image_small'] ?? '');
				if ($v !== '') {
					$found[$v] = true;
				}
			}
		}
		$keys = array_keys($found);
		natcasesort($keys);
		return array_values($keys);
	}
}

if (!function_exists('rkrk_config_menu_icon_pair')) {
	function rkrk_config_menu_icon_pair(string $imageSmall): array {
		$small = rkrk_config_sanitize_menu_icon($imageSmall);
		$image = $small;
		if ($small !== '' && preg_match('#^/(.+)_small\.png$#i', $small, $m)) {
			$largeName = $m[1] . '.png';
			$dir = rkrk_config_menu_icon_dir();
			if (is_file($dir . '/' . $largeName)) {
				$image = '/' . $largeName;
			}
		}
		return array($small, $image);
	}
}

if (!function_exists('rkrk_config_branding')) {
	function rkrk_config_branding(): array {
		$defaults = array(
			'map_geo_admin_url' => '',
			'default_color' => 'white',
			'favicons' => array(),
		);
		$overlay = rkrk_config_load('branding');
		if ($overlay === array()) {
			return $defaults;
		}
		if (isset($overlay['map_geo_admin_url'])) {
			$defaults['map_geo_admin_url'] = rkrk_str($overlay['map_geo_admin_url']);
		}
		if (isset($overlay['default_color'])) {
			$c = strtolower(rkrk_str($overlay['default_color']));
			$defaults['default_color'] = ($c === 'green') ? 'green' : 'white';
		}
		if (isset($overlay['favicons']) && is_array($overlay['favicons'])) {
			$defaults['favicons'] = $overlay['favicons'];
		}
		return $defaults;
	}
}

if (!function_exists('rkrk_config_history_dir')) {
	function rkrk_config_history_dir(): string {
		return rkrk_config_dir() . '_history/';
	}
}

if (!function_exists('rkrk_config_save')) {
	/**
	 * @return string empty on success, otherwise error text
	 */
	function rkrk_config_save(string $name, array $data): string {
		$dir = rkrk_config_dir();
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				return 'Config-Ordner konnte nicht angelegt werden: ' . $dir;
			}
		}
		$path = rkrk_config_file($name);
		if (is_file($path)) {
			$hist = rkrk_config_history_dir();
			if (!is_dir($hist)) {
				@mkdir($hist, 0775, true);
			}
			if (is_dir($hist)) {
				@copy($path, $hist . date('YmdHis') . '_' . basename($path));
			}
		}
		$json = function_exists('rkrk_json_encode_utf8')
			? rkrk_json_encode_utf8($data)
			: json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			return 'JSON-Encode fehlgeschlagen.';
		}
		$ok = function_exists('rkrk_write_utf8_file')
			? rkrk_write_utf8_file($path, $json . "\n")
			: (@file_put_contents($path, $json . "\n", LOCK_EX) !== false);
		if ($ok === false) {
			return 'Schreiben fehlgeschlagen: ' . $path;
		}
		return '';
	}
}

if (!function_exists('rkrk_admin_echo_pill')) {
	function rkrk_admin_echo_pill(string $type, string $name, string $value, bool $checked, string $label, string $extraClass = '', string $title = ''): void {
		$h = static function ($s) {
			return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
		};
		$chk = $checked ? ' checked' : '';
		$cls = 'rkrk-sel rkrk-reg-pill';
		if ($extraClass !== '') {
			$cls .= ' ' . $extraClass;
		}
		$titleAttr = $title !== '' ? (' title="' . $h($title) . '"') : '';
		echo "<label class='" . $h($cls) . "'" . $titleAttr . "><input type='" . $h($type) . "' name='" . $h($name) . "' value='" . $h($value) . "'{$chk}> " . $h($label) . "</label>";
	}
}

if (!function_exists('rkrk_admin_nav')) {
	function rkrk_admin_nav(string $current = ''): void {
		$links = array(
			'rkrk_admin.php' => 'Übersicht',
			'rkrk_admin_missions.php' => 'Missionsbibliotheken',
			'rkrk_admin_regions.php' => 'Regionen',
			'rkrk_admin_pilots.php' => 'Piloten',
			'rkrk_admin_menu.php' => 'Menü',
			'rkrk_admin_branding.php' => 'Branding / Karte',
			'rkrk_admin_unterhalt.php' => 'Unterhalt',
		);
		echo "<nav class='rkrk-sels rkrk-reg-pills rkrk-admin-nav' aria-label='Konfiguration'>";
		foreach ($links as $href => $label) {
			$cls = 'rkrk-sel rkrk-reg-pill';
			if ($href === $current) {
				$cls .= ' is-active';
			}
			echo '<a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
		}
		echo "</nav>\n";
	}
}

if (!function_exists('rkrk_admin_boot')) {
	function rkrk_admin_boot(string $title): bool {
		global $LoggedOn;
		headerpage($title, false);
		GrantAccess();
		if (empty($LoggedOn) || !GetAccess('TestOnly')) {
			echo "<p>Diese Seite ist nur für Benutzer mit Stufe <b>test</b> / Admin.</p>\n";
			footer();
			return false;
		}
		CreateTopMenu();
		echo "<link rel='stylesheet' href='template/rkrk_reg_pills.css?v=20260915adminnav'>\n";
		echo "<main class='rkrk-shell-main'>\n";
		echo "<style>.rkrk-admin-nav{margin:0 0 14px}.rkrk-shell-main table.big td .rkrk-sel,.rkrk-shell-main table.big td .rkrk-reg-pill{font-size:11px;padding:1px 8px;min-height:20px}</style>\n";
		echo "<h1 class='rkrk-shell-page-title'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>\n";
		return true;
	}
}
