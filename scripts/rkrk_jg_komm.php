<?php

if (!function_exists('rkrk_jg_komm_file')) {
	function rkrk_jg_komm_file() {
		global $jsonDocLocation;
		if (!empty($jsonDocLocation)) {
			$dir = rtrim(str_replace('\\', '/', (string)$jsonDocLocation), '/') . '/';
		} else {
			if (!function_exists('rkrk_config_dir')) {
				include_once(__DIR__ . '/rkrk_config.php');
			}
			$dir = dirname(rtrim(str_replace('\\', '/', rkrk_config_dir()), '/')) . '/webdaten/';
		}
		return $dir . 'jagdgebietsseite.json';
	}
}

if (!function_exists('rkrk_jg_komm_norm_reg')) {
	function rkrk_jg_komm_norm_reg($reg) {
		$parts = preg_split('/[\s,;]+/', trim((string)$reg), -1, PREG_SPLIT_NO_EMPTY);
		$out = array();
		if (!is_array($parts)) {
			return '';
		}
		foreach ($parts as $part) {
			$part = trim((string)$part);
			if ($part !== '' && !in_array($part, $out, true)) {
				$out[] = $part;
			}
		}
		return implode(' ', $out);
	}
}

if (!function_exists('rkrk_jg_komm_pages')) {
	function rkrk_jg_komm_pages() {
		global $RegionSettings, $OptionRegUp;
		$regions = (isset($RegionSettings) && is_array($RegionSettings)) ? $RegionSettings : array();
		$upLabels = (isset($OptionRegUp) && is_array($OptionRegUp)) ? $OptionRegUp : array();
		$leaves = array();
		foreach ($regions as $key => $row) {
			if (!is_array($row) || empty($row['Aktiv'])) {
				continue;
			}
			$reg = trim((string)($row['Reg'] ?? $key));
			if ($reg === '' || strcasecmp($reg, 'ALL') === 0 || strcasecmp($reg, 'leer') === 0) {
				continue;
			}
			$list = preg_split('/[\s,;]+/', (string)($row['JGList'] ?? $reg), -1, PREG_SPLIT_NO_EMPTY);
			$list = is_array($list) ? array_values(array_unique(array_map('trim', $list))) : array();
			if (count($list) > 1) {
				continue;
			}
			$up = trim((string)($row['RegUp'] ?? ''));
			if ($up === '' || $up === '0') {
				$up = '_';
			}
			if (!isset($leaves[$up])) {
				$leaves[$up] = array();
			}
			$leaves[$up][] = array(
				'reg' => $reg,
				'label' => (string)($row['Text'] ?? $reg),
			);
		}
		$order = array();
		foreach ($upLabels as $upKey => $unused) {
			$order[] = (string)$upKey;
		}
		foreach (array_keys($leaves) as $upKey) {
			if (!in_array($upKey, $order, true)) {
				$order[] = $upKey;
			}
		}
		$pages = array();
		foreach ($order as $up) {
			if (empty($leaves[$up])) {
				continue;
			}
			$members = $leaves[$up];
			usort($members, function ($a, $b) {
				return strnatcasecmp((string)$a['label'], (string)$b['label']);
			});
			$regs = array();
			foreach ($members as $member) {
				$regs[] = $member['reg'];
			}
			$label = (string)($upLabels[$up]['Text'] ?? ($regions[$up]['Text'] ?? $up));
			$pages[] = array(
				'label' => $label,
				'reg' => count($regs) > 1 ? implode(' ', $regs) : $regs[0],
				'group' => count($regs) > 1,
				'members' => $members,
			);
		}
		return $pages;
	}
}

if (!function_exists('rkrk_jg_komm_empty_store')) {
	function rkrk_jg_komm_empty_store() {
		return array('areas' => array(), 'notices' => array());
	}
}

if (!function_exists('rkrk_jg_komm_read_store')) {
	function rkrk_jg_komm_read_store() {
		$path = rkrk_jg_komm_file();
		if (!is_file($path)) {
			return rkrk_jg_komm_empty_store();
		}
		$raw = file_get_contents($path);
		$data = json_decode((string)$raw, true);
		if (!is_array($data)) {
			return rkrk_jg_komm_empty_store();
		}
		if (!isset($data['areas']) || !is_array($data['areas'])) {
			$data['areas'] = array();
		}
		if (!isset($data['notices']) || !is_array($data['notices'])) {
			$data['notices'] = array();
		}
		return $data;
	}
}

if (!function_exists('rkrk_jg_komm_write_store')) {
	function rkrk_jg_komm_write_store($data) {
		$path = rkrk_jg_komm_file();
		$dir = dirname($path);
		if (!is_dir($dir)) {
			return false;
		}
		$existing = array('areas' => array(), 'notices' => array());
		if (is_file($path)) {
			$decoded = json_decode((string)file_get_contents($path), true);
			if (!is_array($decoded)) {
				return false;
			}
			$existing = $decoded;
		}
		if (!isset($existing['areas']) || !is_array($existing['areas'])) {
			$existing['areas'] = array();
		}
		if (!isset($data['areas']) || !is_array($data['areas'])) {
			$data['areas'] = array();
		}
		foreach ($existing['areas'] as $key => $area) {
			if (!array_key_exists($key, $data['areas'])) {
				$data['areas'][$key] = $area;
			}
		}
		if (!isset($data['notices']) || !is_array($data['notices'])) {
			$data['notices'] = (isset($existing['notices']) && is_array($existing['notices'])) ? $existing['notices'] : array();
		}
		$out = $existing;
		$out['areas'] = $data['areas'];
		$out['notices'] = $data['notices'];
		$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if ($json === false) {
			return false;
		}
		return file_put_contents($path, $json, LOCK_EX) !== false;
	}
}

if (!function_exists('rkrk_jg_komm_union_published')) {
	function rkrk_jg_komm_union_published($draft) {
		if (!is_array($draft)) {
			$draft = rkrk_jg_komm_empty_store();
		}
		$file = rkrk_jg_komm_read_store();
		if (!isset($draft['areas']) || !is_array($draft['areas'])) {
			$draft['areas'] = array();
		}
		if (isset($file['areas']) && is_array($file['areas'])) {
			foreach ($file['areas'] as $key => $area) {
				if (!array_key_exists($key, $draft['areas']) && is_array($area)) {
					$draft['areas'][$key] = $area;
				}
			}
		}
		if (!isset($draft['notices']) || !is_array($draft['notices'])) {
			$draft['notices'] = (isset($file['notices']) && is_array($file['notices'])) ? $file['notices'] : array();
		}
		return $draft;
	}
}

if (!function_exists('rkrk_jg_komm_logged_in')) {
	function rkrk_jg_komm_logged_in() {
		return isset($_SESSION['Benutzername']) && trim((string)$_SESSION['Benutzername']) !== '';
	}
}

if (!function_exists('rkrk_jg_komm_is_preview')) {
	function rkrk_jg_komm_is_preview() {
		if (!isset($_GET['Preview']) || (string)$_GET['Preview'] !== '1') {
			return false;
		}
		return rkrk_jg_komm_logged_in() && isset($_SESSION['jg_komm_draft']) && is_array($_SESSION['jg_komm_draft']);
	}
}

if (!function_exists('rkrk_jg_komm_effective')) {
	function rkrk_jg_komm_effective() {
		if (rkrk_jg_komm_is_preview()) {
			return $_SESSION['jg_komm_draft'];
		}
		return rkrk_jg_komm_read_store();
	}
}

if (!function_exists('rkrk_jg_komm_area')) {
	function rkrk_jg_komm_area($store, $reg) {
		if (!is_array($store) || !isset($store['areas'][$reg]) || !is_array($store['areas'][$reg])) {
			return null;
		}
		$area = $store['areas'][$reg];
		if (empty($area['active'])) {
			return null;
		}
		return $area;
	}
}

if (!function_exists('rkrk_jg_komm_norm_list')) {
	function rkrk_jg_komm_norm_list($value) {
		if (!is_array($value)) {
			return array();
		}
		$out = array();
		foreach ($value as $item) {
			$item = trim((string)$item);
			if ($item !== '') {
				$out[$item] = $item;
			}
		}
		return array_values($out);
	}
}

if (!function_exists('rkrk_jg_komm_stv_names')) {
	function rkrk_jg_komm_stv_names($assigned, $posted, $previous, $explicit) {
		$allow = array();
		foreach (rkrk_jg_komm_norm_list($assigned) as $name) {
			$allow[$name] = true;
		}
		$source = $explicit ? $posted : $previous;
		$out = array();
		foreach (rkrk_jg_komm_norm_list($source) as $name) {
			if (isset($allow[$name])) {
				$out[] = $name;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_public_jg')) {
	function rkrk_jg_komm_public_jg($person) {
		$token = trim((string)($person['stv_reg'] ?? ''));
		if ($token === '') {
			$token = trim((string)($person['JG'] ?? ''));
		}
		if ($token === '') {
			$token = trim((string)($person['Jagdgebiet'] ?? ''));
		}
		$token = trim($token, "() \t");
		if (!empty($person['stv']) && $token !== '') {
			return '(' . $token . ')';
		}
		return $token;
	}
}

if (!function_exists('rkrk_jg_komm_filter_people')) {
	function rkrk_jg_komm_filter_people($list, $allowed) {
		if (!is_array($list)) {
			return array();
		}
		$keep = array();
		foreach ($allowed as $name) {
			$keep[(string)$name] = true;
		}
		$out = array();
		foreach ($list as $key => $person) {
			$name = is_array($person) ? trim((string)($person['Name'] ?? $key)) : '';
			if ($name !== '' && isset($keep[$name])) {
				$out[$key] = $person;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_people_by_names')) {
	function rkrk_jg_komm_people_by_names($rows, $names, $kind) {
		$wanted = rkrk_jg_komm_norm_list($names);
		$byName = array();
		if (is_array($rows)) {
			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}
				$name = trim((string)($row['Name'] ?? ''));
				if ($name === '' || isset($byName[$name])) {
					continue;
				}
				$typ = (string)($row['Typ'] ?? '');
				if ($kind === 'jaeger' && !in_array($typ, array('Jäger', 'Jaeger', 'Jäger Amt'), true)) {
					continue;
				}
				if ($kind === 'pilot' && !in_array($typ, array('Pilot', 'Pilot RKRK', 'Pilot Zug', 'Pilot Neu', 'Pilot inactive'), true)) {
					continue;
				}
				$byName[$name] = $row;
			}
		}
		$out = array();
		foreach ($wanted as $name) {
			if (isset($byName[$name])) {
				$out[$name] = $byName[$name];
			} else {
				$out[$name] = array('Name' => $name, 'JG' => '', 'Jagdgebiet' => '', 'Kontaktnummer' => '');
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_rows_for_regs')) {
	function rkrk_jg_komm_rows_for_regs($rows, $store, $regs, $kind) {
		$field = ($kind === 'pilot') ? 'piloten' : 'jaeger';
		$stvField = ($kind === 'pilot') ? 'piloten_stv' : 'jaeger_stv';
		$out = array();
		if (!is_array($regs)) {
			return $out;
		}
		$areas = (is_array($store) && isset($store['areas']) && is_array($store['areas'])) ? $store['areas'] : array();
		foreach ($regs as $one) {
			$one = rkrk_jg_komm_norm_reg($one);
			if ($one === '') {
				continue;
			}
			$area = (isset($areas[$one]) && is_array($areas[$one])) ? $areas[$one] : null;
			$stvNames = (is_array($area)) ? rkrk_jg_komm_norm_list($area[$stvField] ?? array()) : array();
			$stv = array();
			foreach ($stvNames as $stvName) {
				$stv[$stvName] = true;
			}
			if (is_array($area) && !empty($area['assigned'])) {
				$names = rkrk_jg_komm_norm_list($area[$field] ?? array());
			} else {
				$names = array_keys(rkrk_jg_komm_people_for_reg($rows, $one, $kind));
			}
			foreach ($stvNames as $stvName) {
				if (!in_array($stvName, $names, true)) {
					$names[] = $stvName;
				}
			}
			$found = rkrk_jg_komm_people_by_names($rows, $names, $kind);
			foreach ($names as $name) {
				$person = (isset($found[$name]) && is_array($found[$name])) ? $found[$name] : array('Name' => $name, 'Kontaktnummer' => '');
				$person['JG'] = $one;
				$person['Jagdgebiet'] = $one;
				$person['stv'] = isset($stv[$name]);
				$person['stv_reg'] = $person['stv'] ? $one : '';
				unset($person['Mail'], $person['Adresse'], $person['P Telefon'], $person['GeoMapLoc']);
				$out[] = $person;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_apply_public_stv')) {
	function rkrk_jg_komm_apply_public_stv($people, $rows, $store, $regs, $kind) {
		$stvField = ($kind === 'pilot') ? 'piloten_stv' : 'jaeger_stv';
		$areas = (is_array($store) && isset($store['areas']) && is_array($store['areas'])) ? $store['areas'] : array();
		$pageRegs = array();
		$stvByReg = array();
		if (is_array($regs)) {
			foreach ($regs as $one) {
				$one = rkrk_jg_komm_norm_reg($one);
				if ($one === '' || in_array($one, $pageRegs, true)) {
					continue;
				}
				$pageRegs[] = $one;
				$area = (isset($areas[$one]) && is_array($areas[$one])) ? $areas[$one] : array();
				$stvByReg[$one] = array();
				foreach (rkrk_jg_komm_norm_list($area[$stvField] ?? array()) as $name) {
					$stvByReg[$one][$name] = true;
				}
			}
		}
		$out = array();
		$seen = array();
		if (is_array($people)) {
			foreach ($people as $person) {
				if (!is_array($person)) {
					continue;
				}
				$name = trim((string)($person['Name'] ?? ''));
				if ($name === '') {
					continue;
				}
				$hit = '';
				foreach ($pageRegs as $one) {
					if (isset($stvByReg[$one][$name])) {
						$hit = $one;
						break;
					}
				}
				$person['stv'] = ($hit !== '');
				$person['stv_reg'] = $hit;
				unset($person['Mail'], $person['Adresse'], $person['P Telefon'], $person['GeoMapLoc']);
				$seen[$name] = true;
				$out[] = $person;
			}
		}
		$missing = array();
		$missingReg = array();
		foreach ($pageRegs as $one) {
			foreach ($stvByReg[$one] as $name => $on) {
				if (!isset($seen[$name])) {
					$missing[] = $name;
					$missingReg[$name] = $one;
					$seen[$name] = true;
				}
			}
		}
		$found = rkrk_jg_komm_people_by_names($rows, $missing, $kind);
		foreach ($missing as $name) {
			$person = (isset($found[$name]) && is_array($found[$name])) ? $found[$name] : array('Name' => $name, 'Kontaktnummer' => '');
			$reg = (string)($missingReg[$name] ?? '');
			$person['JG'] = $reg;
			$person['Jagdgebiet'] = $reg;
			$person['stv'] = true;
			$person['stv_reg'] = $reg;
			unset($person['Mail'], $person['Adresse'], $person['P Telefon'], $person['GeoMapLoc']);
			$out[] = $person;
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_hunter_catalog')) {
	function rkrk_jg_komm_hunter_catalog($rows) {
		$out = array();
		$seen = array();
		if (!is_array($rows)) {
			return $out;
		}
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$typ = (string)($row['Typ'] ?? '');
			if (!in_array($typ, array('Jäger', 'Jaeger', 'Jäger Amt'), true)) {
				continue;
			}
			$name = trim((string)($row['Name'] ?? ''));
			if ($name === '' || isset($seen[$name])) {
				continue;
			}
			$seen[$name] = true;
			$out[] = array(
				'name' => $name,
				'label' => $name,
				'code' => '',
				'jg' => trim((string)($row['JG'] ?? '')),
			);
		}
		usort($out, function ($a, $b) {
			return strcasecmp($a['label'], $b['label']);
		});
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_pilot_catalog')) {
	function rkrk_jg_komm_pilot_catalog($rows) {
		global $OptionPilot;
		$byInitial = array();
		$byName = array();
		if (is_array($rows)) {
			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}
				$name = trim((string)($row['Name'] ?? ''));
				$ini = strtoupper(trim((string)($row['Initialen'] ?? '')));
				if ($ini !== '' && !isset($byInitial[$ini])) {
					$byInitial[$ini] = $row;
				}
				if ($name !== '' && !isset($byName[$name])) {
					$byName[$name] = $row;
				}
			}
		}
		$out = array();
		$seen = array();
		if (!is_array($OptionPilot)) {
			return $out;
		}
		foreach ($OptionPilot as $code => $pilot) {
			if (function_exists('rkrk_pilot_is_selectable') && !rkrk_pilot_is_selectable($pilot, date('Y-m-d'), '', 'assign')) {
				continue;
			}
			if (function_exists('rkrk_pilot_normalize')) {
				$pilot = rkrk_pilot_normalize($pilot, is_string($code) ? $code : '');
			}
			$opt = strtoupper(trim((string)($pilot['Opt'] ?? $code)));
			$text = trim((string)($pilot['Text'] ?? ''));
			if ($opt === '' && $text === '') {
				continue;
			}
			$row = ($opt !== '' && isset($byInitial[$opt])) ? $byInitial[$opt] : (($text !== '' && isset($byName[$text])) ? $byName[$text] : null);
			$name = is_array($row) ? trim((string)($row['Name'] ?? '')) : '';
			if ($name === '') {
				$name = $text !== '' ? $text : $opt;
			}
			if (isset($seen[$name])) {
				continue;
			}
			$seen[$name] = true;
			$jg = '';
			if (is_array($row)) {
				$jg = trim((string)($row['Jagdgebiet'] ?? ''));
				if ($jg === '') {
					$jg = trim((string)($row['JG'] ?? ''));
				}
			}
			$out[] = array(
				'name' => $name,
				'label' => $text !== '' ? $text : $name,
				'code' => $opt,
				'jg' => $jg,
			);
		}
		usort($out, function ($a, $b) {
			return strcasecmp($a['label'], $b['label']);
		});
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_phone_digits')) {
	function rkrk_jg_komm_phone_digits($phone) {
		$first = preg_split('/[,;\/]/', (string)$phone);
		$digits = preg_replace('/\D+/', '', (string)($first[0] ?? ''));
		if (strpos($digits, '00') === 0) {
			$digits = substr($digits, 2);
		}
		if (isset($digits[0]) && $digits[0] === '0') {
			$digits = '41' . substr($digits, 1);
		}
		return $digits;
	}
}

if (!function_exists('rkrk_jg_komm_person_links')) {
	function rkrk_jg_komm_person_links($person, $showPhones, $sms, $whatsapp) {
		$number = trim((string)($person['Kontaktnummer'] ?? ''));
		$html = '';
		if ($showPhones && $number !== '') {
			$html .= ' <b>' . htmlspecialchars($number, ENT_QUOTES, 'UTF-8') . '</b>';
		}
		$digits = rkrk_jg_komm_phone_digits($number);
		if ($digits !== '' && $sms) {
			$html .= ' <a href="sms:' . htmlspecialchars($digits, ENT_QUOTES, 'UTF-8') . '">SMS</a>';
		}
		if ($digits !== '' && $whatsapp) {
			$html .= ' <a href="https://wa.me/' . htmlspecialchars($digits, ENT_QUOTES, 'UTF-8') . '" target="_blank">WhatsApp</a>';
		}
		return $html;
	}
}

if (!function_exists('rkrk_jg_komm_date_active')) {
	function rkrk_jg_komm_date_active($from, $to, $onDate, $yearly) {
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$to) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$onDate)) {
			return false;
		}
		if (!$yearly) {
			return $onDate >= $from && $onDate <= $to;
		}
		$fromMd = substr($from, 5);
		$toMd = substr($to, 5);
		$onMd = substr($onDate, 5);
		if ($fromMd <= $toMd) {
			return $onMd >= $fromMd && $onMd <= $toMd;
		}
		return $onMd >= $fromMd || $onMd <= $toMd;
	}
}

if (!function_exists('rkrk_jg_komm_notice_kinds')) {
	function rkrk_jg_komm_notice_kinds() {
		return array(
			'info' => array('label' => 'Info', 'color' => '#527642'),
			'orange' => array('label' => 'Hinweis', 'color' => '#FF7F50'),
			'alert' => array('label' => 'Warnung', 'color' => '#D11111'),
			'green' => array('label' => 'Freigabe', 'color' => '#419043'),
		);
	}
}

if (!function_exists('rkrk_jg_komm_norm_scope')) {
	function rkrk_jg_komm_norm_scope($value) {
		$parts = preg_split('/[\s,;]+/', (string)$value);
		$out = array();
		if (!is_array($parts)) {
			return '';
		}
		foreach ($parts as $part) {
			$part = trim((string)$part);
			if ($part !== '' && !in_array($part, $out, true)) {
				$out[] = $part;
			}
		}
		return implode(',', $out);
	}
}

if (!function_exists('rkrk_jg_komm_legacy_notices')) {
	function rkrk_jg_komm_legacy_notices() {
		$mail = 'mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Fragen%20zur%20Rehkitzrettung%20mit%20Drohnen';
		$thanks = 'mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rehkitzrettung%20mit%20Drohnen%20-%20R%C3%BCckmeldung%20Saison';
		$regions = array('21', '24', '25', '26', '27', '28', '32', 'AZ', '136', '143', '145', '148', 'ZG1', 'ZG2', 'ZG3', 'ZG4', 'ZG5', 'ZG6', 'LU', 'LME', 'SZ');
		return array(
			array(
				'id' => 'ex-offsaison',
				'scope' => 'all',
				'targets' => array('all'),
				'from' => '2026-07-15',
				'to' => '2027-03-31',
				'yearly' => true,
				'kind' => 'info',
				'title' => '',
				'text' => 'Die Rehkitzrettungsaison geht von Ende April bis Mitte Juli, zur Zeit werden keine Suchflüge angeboten. Für Fragen stehen wir aber gerne zur Verfügung.',
				'link_label' => 'Anfrage schicken',
				'link_url' => $mail,
			),
			array(
				'id' => 'ex-saisonstart',
				'scope' => 'all',
				'targets' => array('all'),
				'from' => '2026-04-01',
				'to' => '2026-04-19',
				'yearly' => true,
				'kind' => 'info',
				'title' => 'Bald geht es los!',
				'text' => 'Die Rehkitzrettungsaison geht von Ende April bis Mitte Juli. Für Fragen stehen wir aber gerne zur Verfügung.',
				'link_label' => 'Anfrage schicken',
				'link_url' => $mail,
			),
			array(
				'id' => 'ex-schnitt',
				'scope' => 'all',
				'targets' => array('all'),
				'from' => '2026-06-17',
				'to' => '2026-06-25',
				'yearly' => true,
				'kind' => 'orange',
				'title' => '',
				'text' => 'Der grosse Schnitt vom 15. ist gemacht. Für die bestehenden Felder lohnt sich eine Meldung immer noch. Im letzten Jahr wurde uns in unserem Gebiet sogar am 15. Juli ein junges Rehkitz als vermäht gemeldet.',
				'link_label' => '',
				'link_url' => '',
			),
			array(
				'id' => 'ex-dank',
				'scope' => 'all',
				'targets' => array('all'),
				'from' => '2026-06-25',
				'to' => '2026-07-14',
				'yearly' => true,
				'kind' => 'info',
				'title' => 'Herzlichen Dank',
				'text' => 'Die Rehkitzrettungssaison mit Drohnen ist in unseren Regionen grossmehrheitlich vorbei. Die jungen Rehkitze sind meist mobil und stark genug. Für die tolle Zusammenarbeit in der Saison. Wir hoffen, dass wir einen guten Service liefern konnten. Rückmeldungen sind herzlich willkommen.',
				'link_label' => 'Mail schicken',
				'link_url' => $thanks,
			),
			array(
				'id' => 'ex-dabs-2026',
				'scope' => implode(',', $regions),
				'targets' => $regions,
				'from' => '2026-06-15',
				'to' => '2026-06-22',
				'yearly' => false,
				'kind' => 'info',
				'title' => '',
				'text' => 'Im DABS 2026 JUN 22 Version 1 ist die Flugverbotszone 46.3 km um den Bürgenstock aktiv, betrifft den Drohnenflug unserer Jagdgebiete jedoch nicht. Die Flüge können wie geplant durchgeführt werden.',
				'link_label' => '',
				'link_url' => '',
			),
			array(
				'id' => 'ex-buergen-2024-mai',
				'scope' => '25,26,27,28,136,143,148,145,ZG1,ZG2,ZG3,ZG4,ZG5,ZG6',
				'targets' => array('25', '26', '27', '28', '136', '143', '148', '145', 'ZG1', 'ZG2', 'ZG3', 'ZG4', 'ZG5', 'ZG6'),
				'from' => '2024-05-24',
				'to' => '2024-05-27',
				'yearly' => false,
				'kind' => 'alert',
				'title' => 'Flugeinschränkung seit 17. Juni 00:00h aufgehoben!',
				'text' => 'Wir danken für das Verständnis und euren Einsatz über die letzten Tage. Ab diesem Zeitpunkt können wir den Flugbetrieb wieder wie gewohnt aufnehmen. Wir danken für die frühzeitige Meldung der Felder, die am nächsten Tag zu fliegen sind. Die Flüge der letzten Tage haben gezeigt, dass wir im Unterland immer noch runde Geissen und frische Kitze haben. Ein Abfliegen der Felder lohnt sich also immer noch. Euer Drohnenrehkitzrettungsteam',
				'link_label' => 'mehr',
				'link_url' => 'https://www.bazl.admin.ch/bazl/de/home/infrastruktur/flugsicherung-und-luftraum/ukraine_conference_buergenstock.html',
			),
			array(
				'id' => 'ex-buergen-2024-juni',
				'scope' => '25,26,27,28,136,143,148,145,ZG1,ZG2,ZG3,ZG4,ZG5,ZG6',
				'targets' => array('25', '26', '27', '28', '136', '143', '148', '145', 'ZG1', 'ZG2', 'ZG3', 'ZG4', 'ZG5', 'ZG6'),
				'from' => '2024-06-12',
				'to' => '2024-06-17',
				'yearly' => false,
				'kind' => 'alert',
				'title' => 'Flugeinschränkung seit 17. Juni 00:00h aufgehoben!',
				'text' => 'Wir danken für das Verständnis und euren Einsatz über die letzten Tage. Ab diesem Zeitpunkt können wir den Flugbetrieb wieder wie gewohnt aufnehmen. Wir danken für die frühzeitige Meldung der Felder, die am nächsten Tag zu fliegen sind. Die Flüge der letzten Tage haben gezeigt, dass wir im Unterland immer noch runde Geissen und frische Kitze haben. Ein Abfliegen der Felder lohnt sich also immer noch. Euer Drohnenrehkitzrettungsteam',
				'link_label' => 'mehr',
				'link_url' => 'https://www.bazl.admin.ch/bazl/de/home/infrastruktur/flugsicherung-und-luftraum/ukraine_conference_buergenstock.html',
			),
		);
	}
}

if (!function_exists('rkrk_jg_komm_notice_phase')) {
	function rkrk_jg_komm_notice_phase($notice, $onDate) {
		$from = (string)($notice['from'] ?? '');
		$to = (string)($notice['to'] ?? '');
		$yearly = !empty($notice['yearly']);
		if (rkrk_jg_komm_date_active($from, $to, $onDate, $yearly)) {
			return 'now';
		}
		if ($yearly) {
			return 'plan';
		}
		if ($from !== '' && $onDate < $from) {
			return 'plan';
		}
		return 'past';
	}
}

if (!function_exists('rkrk_jg_komm_notice_enabled')) {
	function rkrk_jg_komm_notice_enabled($notice) {
		if (!is_array($notice) || !array_key_exists('enabled', $notice)) {
			return true;
		}
		$value = $notice['enabled'];
		return $value !== false && $value !== 0 && $value !== '0' && $value !== 'false';
	}
}

if (!function_exists('rkrk_jg_komm_notice_html')) {
	function rkrk_jg_komm_notice_html($notice) {
		$kinds = rkrk_jg_komm_notice_kinds();
		$kind = (string)($notice['kind'] ?? 'info');
		$color = (string)($kinds[$kind]['color'] ?? '#527642');
		$title = trim((string)($notice['title'] ?? ''));
		$text = trim((string)($notice['text'] ?? ''));
		$body = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
		$label = trim((string)($notice['link_label'] ?? ''));
		$url = trim((string)($notice['link_url'] ?? ''));
		$safe = '';
		if (preg_match('/^mailto:[^\s<>"]+$/i', $url) || preg_match('#^https?://[^\s<>"]+$#i', $url)) {
			$safe = $url;
		}
		if ($safe !== '' && $label !== '') {
			$body .= ' -&gt; <a href="' . htmlspecialchars($safe, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
		}
		$colorAttr = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
		if ($title !== '') {
			return '<details style="color:' . $colorAttr . ';margin:8px 0"><summary style="cursor:pointer"><b>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</b></summary><div style="margin-top:4px">' . $body . '</div></details>';
		}
		return '<p style="color:' . $colorAttr . '">' . $body . '</p>';
	}
}

if (!function_exists('rkrk_jg_komm_notice_matches')) {
	function rkrk_jg_komm_notice_matches($notice, $reg, $onDate) {
		if (!is_array($notice)) {
			return false;
		}
		$from = (string)($notice['from'] ?? '');
		$to = (string)($notice['to'] ?? '');
		if (!rkrk_jg_komm_date_active($from, $to, $onDate, !empty($notice['yearly']))) {
			return false;
		}
		$scope = (string)($notice['scope'] ?? '');
		$targets = (isset($notice['targets']) && is_array($notice['targets'])) ? $notice['targets'] : array();
		if (count($targets) === 0) {
			if ($scope === 'all') {
				$targets = array('all');
			} else {
				$targets = array_filter(array_map('trim', explode(',', $scope)));
			}
		}
		$pageKey = rkrk_jg_komm_norm_reg($reg);
		foreach ($targets as $target) {
			$target = trim((string)$target);
			if ($target === 'all') {
				return true;
			}
			if (rkrk_jg_komm_norm_reg($target) === $pageKey && $pageKey !== '') {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('rkrk_jg_komm_active_notices')) {
	function rkrk_jg_komm_active_notices($store, $reg, $onDate) {
		$out = array();
		$notices = (is_array($store) && isset($store['notices']) && is_array($store['notices'])) ? $store['notices'] : array();
		foreach ($notices as $notice) {
			if (!rkrk_jg_komm_notice_enabled($notice)) {
				continue;
			}
			if (rkrk_jg_komm_notice_matches($notice, $reg, $onDate)) {
				$out[] = $notice;
			}
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_alert_catalog')) {
	function rkrk_jg_komm_alert_catalog() {
		if (!function_exists('ref_layers_alert_kml_catalog')) {
			$lib = __DIR__ . '/rkrk_ref_layers_lib.php';
			if (is_file($lib)) {
				include_once $lib;
			}
		}
		if (!function_exists('ref_layers_alert_kml_catalog')) {
			return array();
		}
		$rows = ref_layers_alert_kml_catalog();
		return is_array($rows) ? $rows : array();
	}
}

if (!function_exists('rkrk_jg_komm_alert_window_text')) {
	function rkrk_jg_komm_alert_window_text($row) {
		$from = (string)($row['from'] ?? '');
		$to = (string)($row['to'] ?? '');
		if ($from === '' || $to === '') {
			return 'ohne Zeitlimit';
		}
		$text = $from . '–' . $to;
		if (!empty($row['yearly'])) {
			$text .= ', jedes Jahr';
		}
		return $text;
	}
}

if (!function_exists('rkrk_jg_komm_restrictions_from_post')) {
	function rkrk_jg_komm_restrictions_from_post() {
		$files = (isset($_POST['restriction_file']) && is_array($_POST['restriction_file'])) ? $_POST['restriction_file'] : array();
		$allowed = array();
		foreach (rkrk_jg_komm_alert_catalog() as $row) {
			if (is_array($row) && isset($row['name']) && (string)$row['name'] !== '') {
				$allowed[(string)$row['name']] = true;
			}
		}
		$out = array();
		$seen = array();
		foreach ($files as $file) {
			$name = function_exists('ref_layers_alert_kml_safe_name') ? ref_layers_alert_kml_safe_name($file) : '';
			if ($name === '' || !isset($allowed[$name]) || isset($seen[$name])) {
				continue;
			}
			$seen[$name] = true;
			$out[] = array('file' => $name);
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_notice_alert_file')) {
	function rkrk_jg_komm_notice_alert_file($value) {
		$name = function_exists('ref_layers_alert_kml_safe_name') ? ref_layers_alert_kml_safe_name($value) : '';
		if ($name === '') {
			return '';
		}
		foreach (rkrk_jg_komm_alert_catalog() as $row) {
			if (is_array($row) && (string)($row['name'] ?? '') === $name) {
				return $name;
			}
		}
		return '';
	}
}

if (!function_exists('rkrk_jg_komm_notice_alert_zones')) {
	function rkrk_jg_komm_notice_alert_zones($store, $reg, $onDate) {
		$notices = (is_array($store) && isset($store['notices']) && is_array($store['notices'])) ? $store['notices'] : array();
		if ($notices === array()) {
			return array();
		}
		$byName = array();
		foreach (rkrk_jg_komm_alert_catalog() as $row) {
			if (is_array($row) && isset($row['name'])) {
				$byName[(string)$row['name']] = $row;
			}
		}
		$out = array();
		$seen = array();
		foreach ($notices as $notice) {
			if (!is_array($notice) || !rkrk_jg_komm_notice_enabled($notice)) {
				continue;
			}
			if (!rkrk_jg_komm_notice_matches($notice, $reg, $onDate)) {
				continue;
			}
			$file = rkrk_jg_komm_notice_alert_file($notice['alert'] ?? '');
			if ($file === '' || isset($seen[$file]) || !isset($byName[$file])) {
				continue;
			}
			$seen[$file] = true;
			$out[] = $byName[$file];
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_active_alert_zones')) {
	function rkrk_jg_komm_active_alert_zones($area, $onDate) {
		if (!is_array($area)) {
			return array();
		}
		$list = (isset($area['restrictions']) && is_array($area['restrictions'])) ? $area['restrictions'] : array();
		if ($list === array() || !function_exists('ref_layers_alert_row_on')) {
			if ($list !== array() && !function_exists('ref_layers_alert_row_on')) {
				rkrk_jg_komm_alert_catalog();
			}
			if (!function_exists('ref_layers_alert_row_on')) {
				return array();
			}
		}
		$byName = array();
		foreach (rkrk_jg_komm_alert_catalog() as $row) {
			if (is_array($row) && isset($row['name'])) {
				$byName[(string)$row['name']] = $row;
			}
		}
		$out = array();
		$seen = array();
		foreach ($list as $item) {
			$file = is_array($item) ? (string)($item['file'] ?? '') : (string)$item;
			$file = function_exists('ref_layers_alert_kml_safe_name') ? ref_layers_alert_kml_safe_name($file) : '';
			if ($file === '' || isset($seen[$file]) || !isset($byName[$file])) {
				continue;
			}
			if (!ref_layers_alert_row_on($byName[$file], $onDate)) {
				continue;
			}
			$seen[$file] = true;
			$out[] = $byName[$file];
		}
		return $out;
	}
}

if (!function_exists('rkrk_jg_komm_payload_from_post')) {
	function rkrk_jg_komm_payload_from_post($reg) {
		$area = array(
			'active' => isset($_POST['active']),
			'title' => trim((string)($_POST['title'] ?? '')),
			'subtitle' => trim((string)($_POST['subtitle'] ?? '')),
			'info' => trim((string)($_POST['info'] ?? '')),
			'show_phones' => isset($_POST['show_phones']),
			'show_map' => isset($_POST['show_map']),
			'show_geflogen' => isset($_POST['show_geflogen']),
			'show_jaeger' => isset($_POST['show_jaeger']),
			'show_piloten' => isset($_POST['show_piloten']),
			'show_orders' => isset($_POST['show_orders']),
			'show_stats' => isset($_POST['show_stats']),
			'show_flights' => isset($_POST['show_flights']),
			'orders_text' => trim((string)($_POST['orders_text'] ?? '')),
			'sms' => isset($_POST['sms']),
			'whatsapp' => isset($_POST['whatsapp']),
		);
		$base = (isset($_SESSION['jg_komm_draft']) && is_array($_SESSION['jg_komm_draft']))
			? $_SESSION['jg_komm_draft']
			: rkrk_jg_komm_read_store();
		if (!isset($base['areas']) || !is_array($base['areas'])) {
			$base['areas'] = array();
		}
		$areas = $base['areas'];
		$prev = (isset($areas[$reg]) && is_array($areas[$reg])) ? $areas[$reg] : array();
		$area = array_merge($prev, $area);
		$area['jaeger'] = rkrk_jg_komm_norm_list((isset($_POST['jaeger']) && is_array($_POST['jaeger'])) ? $_POST['jaeger'] : ((isset($prev['jaeger']) && is_array($prev['jaeger'])) ? $prev['jaeger'] : array()));
		$area['piloten'] = rkrk_jg_komm_norm_list((isset($_POST['piloten']) && is_array($_POST['piloten'])) ? $_POST['piloten'] : ((isset($prev['piloten']) && is_array($prev['piloten'])) ? $prev['piloten'] : array()));
		$area['jaeger_stv'] = rkrk_jg_komm_stv_names(
			$area['jaeger'],
			(isset($_POST['jaeger_stv']) && is_array($_POST['jaeger_stv'])) ? $_POST['jaeger_stv'] : array(),
			(isset($prev['jaeger_stv']) && is_array($prev['jaeger_stv'])) ? $prev['jaeger_stv'] : array(),
			isset($_POST['jaeger_stv_set'])
		);
		$area['piloten_stv'] = rkrk_jg_komm_stv_names(
			$area['piloten'],
			(isset($_POST['piloten_stv']) && is_array($_POST['piloten_stv'])) ? $_POST['piloten_stv'] : array(),
			(isset($prev['piloten_stv']) && is_array($prev['piloten_stv'])) ? $prev['piloten_stv'] : array(),
			isset($_POST['piloten_stv_set'])
		);
		if (isset($_POST['restrictions_set'])) {
			$area['restrictions'] = rkrk_jg_komm_restrictions_from_post();
		}
		if (!empty($prev['assigned'])) {
			$area['assigned'] = true;
		}
		$areas[$reg] = $area;
		$assignRegs = (isset($_POST['assign_regs']) && is_array($_POST['assign_regs'])) ? $_POST['assign_regs'] : null;
		if (is_array($assignRegs)) {
			$assignJaeger = (isset($_POST['assign_jaeger']) && is_array($_POST['assign_jaeger'])) ? $_POST['assign_jaeger'] : array();
			$assignPiloten = (isset($_POST['assign_piloten']) && is_array($_POST['assign_piloten'])) ? $_POST['assign_piloten'] : array();
			$assignJaegerStv = (isset($_POST['assign_jaeger_stv']) && is_array($_POST['assign_jaeger_stv'])) ? $_POST['assign_jaeger_stv'] : array();
			$assignPilotenStv = (isset($_POST['assign_piloten_stv']) && is_array($_POST['assign_piloten_stv'])) ? $_POST['assign_piloten_stv'] : array();
			$jaegerStvSet = array();
			$pilotenStvSet = array();
			if (isset($_POST['assign_jaeger_stv_set']) && is_array($_POST['assign_jaeger_stv_set'])) {
				foreach ($_POST['assign_jaeger_stv_set'] as $mark) {
					$mark = rkrk_jg_komm_norm_reg($mark);
					if ($mark !== '') {
						$jaegerStvSet[$mark] = true;
					}
				}
			}
			if (isset($_POST['assign_piloten_stv_set']) && is_array($_POST['assign_piloten_stv_set'])) {
				foreach ($_POST['assign_piloten_stv_set'] as $mark) {
					$mark = rkrk_jg_komm_norm_reg($mark);
					if ($mark !== '') {
						$pilotenStvSet[$mark] = true;
					}
				}
			}
			foreach ($assignRegs as $leafRaw) {
				$leaf = rkrk_jg_komm_norm_reg($leafRaw);
				if ($leaf === '') {
					continue;
				}
				if (!isset($areas[$leaf]) || !is_array($areas[$leaf])) {
					$areas[$leaf] = array(
						'active' => false,
						'title' => '',
						'subtitle' => '',
						'info' => '',
						'show_phones' => true,
						'show_map' => true,
						'show_geflogen' => true,
						'sms' => false,
						'whatsapp' => false,
						'jaeger' => array(),
						'piloten' => array(),
						'jaeger_stv' => array(),
						'piloten_stv' => array(),
					);
				}
				$prevJaegerStv = (isset($areas[$leaf]['jaeger_stv']) && is_array($areas[$leaf]['jaeger_stv'])) ? $areas[$leaf]['jaeger_stv'] : array();
				$prevPilotenStv = (isset($areas[$leaf]['piloten_stv']) && is_array($areas[$leaf]['piloten_stv'])) ? $areas[$leaf]['piloten_stv'] : array();
				$areas[$leaf]['assigned'] = true;
				$areas[$leaf]['jaeger'] = rkrk_jg_komm_norm_list((isset($assignJaeger[$leaf]) && is_array($assignJaeger[$leaf])) ? $assignJaeger[$leaf] : array());
				$areas[$leaf]['piloten'] = rkrk_jg_komm_norm_list((isset($assignPiloten[$leaf]) && is_array($assignPiloten[$leaf])) ? $assignPiloten[$leaf] : array());
				$areas[$leaf]['jaeger_stv'] = rkrk_jg_komm_stv_names(
					$areas[$leaf]['jaeger'],
					(isset($assignJaegerStv[$leaf]) && is_array($assignJaegerStv[$leaf])) ? $assignJaegerStv[$leaf] : array(),
					$prevJaegerStv,
					isset($jaegerStvSet[$leaf]) || array_key_exists($leaf, $assignJaegerStv)
				);
				$areas[$leaf]['piloten_stv'] = rkrk_jg_komm_stv_names(
					$areas[$leaf]['piloten'],
					(isset($assignPilotenStv[$leaf]) && is_array($assignPilotenStv[$leaf])) ? $assignPilotenStv[$leaf] : array(),
					$prevPilotenStv,
					isset($pilotenStvSet[$leaf]) || array_key_exists($leaf, $assignPilotenStv)
				);
			}
		}
		$notices = array();
		$ids = isset($_POST['notice_id']) && is_array($_POST['notice_id']) ? $_POST['notice_id'] : array();
		$scopes = isset($_POST['notice_scope']) && is_array($_POST['notice_scope']) ? $_POST['notice_scope'] : array();
		$scopeLists = isset($_POST['notice_scope_list']) && is_array($_POST['notice_scope_list']) ? $_POST['notice_scope_list'] : array();
		$froms = isset($_POST['notice_from']) && is_array($_POST['notice_from']) ? $_POST['notice_from'] : array();
		$tos = isset($_POST['notice_to']) && is_array($_POST['notice_to']) ? $_POST['notice_to'] : array();
		$texts = isset($_POST['notice_text']) && is_array($_POST['notice_text']) ? $_POST['notice_text'] : array();
		$titles = isset($_POST['notice_title']) && is_array($_POST['notice_title']) ? $_POST['notice_title'] : array();
		$kinds = isset($_POST['notice_kind']) && is_array($_POST['notice_kind']) ? $_POST['notice_kind'] : array();
		$yearlies = isset($_POST['notice_yearly']) && is_array($_POST['notice_yearly']) ? $_POST['notice_yearly'] : array();
		$linkLabels = isset($_POST['notice_link_label']) && is_array($_POST['notice_link_label']) ? $_POST['notice_link_label'] : array();
		$linkUrls = isset($_POST['notice_link_url']) && is_array($_POST['notice_link_url']) ? $_POST['notice_link_url'] : array();
		$enabledFlags = isset($_POST['notice_enabled']) && is_array($_POST['notice_enabled']) ? $_POST['notice_enabled'] : array();
		$noticeAlerts = isset($_POST['notice_alert']) && is_array($_POST['notice_alert']) ? $_POST['notice_alert'] : array();
		$count = max(count($ids), count($texts));
		$allowedKinds = array_keys(rkrk_jg_komm_notice_kinds());
		$targetSets = (isset($_POST['notice_targets']) && is_array($_POST['notice_targets'])) ? $_POST['notice_targets'] : array();
		for ($i = 0; $i < $count; $i++) {
			$text = trim((string)($texts[$i] ?? ''));
			$title = trim((string)($titles[$i] ?? ''));
			if ($text === '' && $title === '') {
				continue;
			}
			$id = trim((string)($ids[$i] ?? ''));
			if ($id === '') {
				$id = 'n' . date('YmdHis') . $i;
			}
			if (isset($targetSets[$i]) && is_array($targetSets[$i])) {
				$picked = $targetSets[$i];
				if (in_array('all', $picked, true)) {
					$targets = array('all');
					$scope = 'all';
				} else {
					$targets = array();
					foreach ($picked as $item) {
						$norm = rkrk_jg_komm_norm_reg($item);
						if ($norm !== '' && !in_array($norm, $targets, true)) {
							$targets[] = $norm;
						}
					}
					if (count($targets) === 0) {
						$targets = array(rkrk_jg_komm_norm_reg($reg));
					}
					$scope = implode(',', $targets);
				}
			} else {
				$mode = (string)($scopes[$i] ?? 'reg');
				if ($mode === 'all') {
					$scope = 'all';
					$targets = array('all');
				} elseif ($mode === 'list') {
					$scope = rkrk_jg_komm_norm_scope((string)($scopeLists[$i] ?? ''));
					if ($scope === '') {
						$scope = (string)$reg;
					}
					$targets = array($scope);
				} else {
					$scope = (string)$reg;
					$targets = array(rkrk_jg_komm_norm_reg($reg));
				}
			}
			$from = (string)($froms[$i] ?? '');
			$to = (string)($tos[$i] ?? '');
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
				continue;
			}
			$yearly = (string)($yearlies[$i] ?? '') === '1';
			if (!$yearly && $to < $from) {
				$swap = $from;
				$from = $to;
				$to = $swap;
			}
			$kind = (string)($kinds[$i] ?? 'info');
			if (!in_array($kind, $allowedKinds, true)) {
				$kind = 'info';
			}
			$enabled = true;
			if (array_key_exists($i, $enabledFlags)) {
				$flag = $enabledFlags[$i];
				$enabled = !($flag === false || $flag === 0 || $flag === '0' || $flag === 'false' || $flag === '');
			}
			$notices[] = array(
				'id' => $id,
				'scope' => $scope,
				'targets' => $targets,
				'from' => $from,
				'to' => $to,
				'yearly' => $yearly,
				'kind' => $kind,
				'title' => $title,
				'text' => $text,
				'link_label' => trim((string)($linkLabels[$i] ?? '')),
				'link_url' => trim((string)($linkUrls[$i] ?? '')),
				'enabled' => $enabled,
				'alert' => rkrk_jg_komm_notice_alert_file($noticeAlerts[$i] ?? ''),
			);
		}
		return rkrk_jg_komm_union_published(array('areas' => $areas, 'notices' => $notices));
	}
}

if (!function_exists('rkrk_jg_komm_hash')) {
	function rkrk_jg_komm_hash($data) {
		$json = json_encode($data, JSON_UNESCAPED_UNICODE);
		return md5((string)$json);
	}
}

if (!function_exists('rkrk_jg_komm_merge_draft_area')) {
	function rkrk_jg_komm_merge_draft_area($published, $reg) {
		$draft = $published;
		if (!isset($draft['areas'][$reg]) || !is_array($draft['areas'][$reg])) {
			$draft['areas'][$reg] = array(
				'active' => false,
				'title' => '',
				'subtitle' => '',
				'info' => '',
				'show_phones' => true,
				'show_map' => true,
				'show_geflogen' => true,
				'sms' => false,
				'whatsapp' => false,
				'jaeger' => array(),
				'piloten' => array(),
				'jaeger_stv' => array(),
				'piloten_stv' => array(),
			);
		}
		return $draft;
	}
}

if (!function_exists('rkrk_jg_komm_people_for_reg')) {
	function rkrk_jg_komm_people_for_reg($rows, $reg, $kind) {
		$out = array();
		if (!is_array($rows)) {
			return $out;
		}
		$regList = explode(',', str_replace(' ', ',', (string)$reg));
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$typ = (string)($row['Typ'] ?? '');
			if ($kind === 'pilot') {
				$okType = in_array($typ, array('Pilot', 'Pilot RKRK', 'Pilot Zug', 'Pilot Neu'), true);
				$field = (string)($row['Jagdgebiet'] ?? '');
			} elseif ($kind === 'jaeger') {
				$okType = ($typ === 'Jäger' || $typ === 'Jaeger' || $typ === 'Jäger Amt');
				$field = (string)($row['JG'] ?? '');
			} else {
				$okType = true;
				$field = (string)($row['JG'] ?? ($row['Jagdgebiet'] ?? ($row['Reg'] ?? ($row['Gebiet'] ?? ''))));
			}
			if (!$okType) {
				continue;
			}
			if ($kind !== 'landwirt' && (string)($row['Kontakt'] ?? '') !== 'Ja') {
				continue;
			}
			if ($kind === 'landwirt' && isset($row['Kontakt']) && (string)$row['Kontakt'] !== '' && (string)$row['Kontakt'] !== 'Ja') {
				continue;
			}
			$parts = explode(',', str_replace(' ', ',', $field));
			if ($field !== '' && function_exists('ElementOfArrayInArray') && !ElementOfArrayInArray($parts, $regList)) {
				continue;
			}
			$name = trim((string)($row['Name'] ?? ''));
			if ($name === '') {
				continue;
			}
			$out[$name] = $row;
		}
		ksort($out);
		return $out;
	}
}
