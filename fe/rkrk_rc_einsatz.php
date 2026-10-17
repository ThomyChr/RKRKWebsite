<?php
/**
 * RC Einsatz: gebuchte Felder auf der Fernsteuerung.
 * Dieselbe Saison-CSV wie die Flugbuchungen. Speichern betrifft nur eine Zeile.
 */
$appRoot = dirname(__DIR__);
chdir($appRoot);
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$rcMission = trim((string)($_REQUEST['mdb'] ?? $_REQUEST['MDB'] ?? '3T'));
if ($rcMission === '') {
	$rcMission = '3T';
}
$_GET['MDB'] = $rcMission;
include_once $appRoot . '/scripts/rkrk_gen.php';
include_once $appRoot . '/scripts/rkrk_csvfiles.php';
include_once $appRoot . '/scripts/rkrk_felddaten.php';
set_include_path($appRoot . '/scripts' . PATH_SEPARATOR . get_include_path());
include_once $appRoot . '/scripts/rkrk_geofence.php';
include_once $appRoot . '/scripts/rkrk_einsatz_shared.php';
include_once $appRoot . '/scripts/rkrk_menu.php';
include_once $appRoot . '/rkrk_access_control.php';

CheckIPandExit();

function rc_einsatz_allowed() {
	return function_exists('MenuEntryAccessGranted') && MenuEntryAccessGranted('RcEinsatz');
}

function rc_einsatz_date($value) {
	$value = trim((string)$value);
	$dt = DateTime::createFromFormat('Y-m-d', $value);
	if ($dt && $dt->format('Y-m-d') === $value) {
		return $value;
	}
	return date('Y-m-d');
}

function rc_einsatz_load($year) {
	$path = rkrk_flights_csv_resolve_path($year);
	$data = array();
	if ($path !== '' && is_file($path)) {
		$loaded = csv_to_array($path, ';');
		if (is_array($loaded)) {
			$data = $loaded;
		}
	}
	return FormatFlugDataArray($data);
}

function rc_einsatz_save($year, array $data) {
	global $historyfilelocation;
	$header = rkrk_einsatz_flight_csv_header();
	$rows = array();
	foreach ($data as $row) {
		if (!is_array($row)) {
			continue;
		}
		if (trim((string)($row['FlugID'] ?? '')) === '') {
			continue;
		}
		$rows[] = $row;
	}
	$targets = rkrk_flights_csv_write_targets($year);
	$ok = false;
	foreach ($targets as $path) {
		if (is_file($path) && !empty($historyfilelocation)) {
			@copy($path, $historyfilelocation . date('YmdH') . '00_rc_' . basename($path));
		}
		if (rkrk_flights_csv_write_rows($path, $header, $rows, ';')) {
			$ok = true;
		}
	}
	return $ok;
}

function rc_einsatz_catalog_row($feldId) {
	global $Feldkatalog;
	$feldId = trim((string)$feldId);
	$jg = function_exists('rkrk_feld_jg_from_feld_id') ? rkrk_feld_jg_from_feld_id($feldId) : '';
	if ($jg !== '' && isset($Feldkatalog[$jg][$feldId]) && is_array($Feldkatalog[$jg][$feldId])) {
		return $Feldkatalog[$jg][$feldId];
	}
	if (is_array($Feldkatalog)) {
		foreach ($Feldkatalog as $fields) {
			if (isset($fields[$feldId]) && is_array($fields[$feldId])) {
				return $fields[$feldId];
			}
		}
	}
	return array();
}

function rc_einsatz_h($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rc_einsatz_web_href($path) {
	$href = str_replace('\\', '/', (string)$path);
	if ($href === '' || preg_match('#^(https?:)?//#', $href) || $href[0] === '/') {
		return $href;
	}
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	if (strpos($script, '/fe/') !== false) {
		return '../' . $href;
	}
	return $href;
}

function rc_einsatz_mission_href($rel) {
	global $MissionLocation;
	$rel = trim(str_replace('\\', '/', (string)$rel), '/');
	if ($rel === '') {
		return '';
	}
	$base = trim(str_replace('\\', '/', (string)$MissionLocation), '/');
	$full = 'rkropen/' . ($base !== '' ? $base . '/' : '') . $rel;
	$encoded = array();
	foreach (explode('/', $full) as $part) {
		if ($part === '') {
			continue;
		}
		$encoded[] = rawurlencode($part);
	}
	return '/' . implode('/', $encoded);
}

function rc_einsatz_kmz_path($feldId) {
	$rel = rc_einsatz_catalog_file($feldId, 'KMZFile');
	if ($rel === '' || !function_exists('rkrk_resolve_mission_file_path')) {
		return '';
	}
	$path = rkrk_resolve_mission_file_path($rel);
	return ($path !== '' && is_file($path)) ? $path : '';
}

function rc_einsatz_catalog_files($feldId, $key) {
	$row = rc_einsatz_catalog_row($feldId);
	$raw = function_exists('rkrk_fluege_scalar_text') ? rkrk_fluege_scalar_text($row[$key] ?? '') : trim((string)($row[$key] ?? ''));
	$files = array();
	foreach (explode(',', $raw) as $part) {
		$part = trim($part);
		if ($part !== '') {
			$files[] = $part;
		}
	}
	return $files;
}

function rc_einsatz_catalog_file($feldId, $key) {
	$files = rc_einsatz_catalog_files($feldId, $key);
	return $files[0] ?? '';
}

function rc_einsatz_plan_file($feldId) {
	$row = rc_einsatz_catalog_row($feldId);
	$files = rc_einsatz_catalog_files($feldId, 'FlugplanBild');
	if ($files === array()) {
		return '';
	}
	if (function_exists('GetFlugplanbild')) {
		$picked = trim((string)GetFlugplanbild($files, $row['StdFlughohe'] ?? ''));
		if ($picked !== '') {
			return $picked;
		}
	}
	return $files[0];
}

function rc_einsatz_icon_src($file) {
	global $IconLoc;
	$base = rtrim(str_replace('\\', '/', (string)$IconLoc), '/');
	return rc_einsatz_web_href(($base !== '' ? $base . '/' : '') . ltrim((string)$file, '/'));
}

function rc_einsatz_store_row(array &$data, $flugId, array $fields, $tag) {
	$flugId = trim((string)$flugId);
	if ($flugId === '' || !isset($data[$flugId]) || !is_array($data[$flugId])) {
		return 'fehlt';
	}
	if ((string)($data[$flugId]['Datum'] ?? '') !== (string)$tag) {
		return 'fehlt';
	}
	rkrk_einsatz_apply_result_fields(
		$data[$flugId],
		$fields['geflogen'] ?? '',
		$fields['kontrollflug'] ?? '',
		$fields['fund'] ?? '',
		$fields['bemerkung'] ?? ''
	);
	rkrk_einsatz_apply_detail_fields($data[$flugId], $fields);
	$newPilot = strtoupper(trim((string)($fields['zu_pilot'] ?? '')));
	$oldPilot = strtoupper(trim((string)($data[$flugId]['Pilot'] ?? '')));
	$allowedPilots = function_exists('rkrk_pilot_select_codes') ? rkrk_pilot_select_codes('assign', $tag, $oldPilot, false) : array();
	$allowedPilots = array_map(static function ($code) {
		return strtoupper(trim((string)$code));
	}, $allowedPilots);
	if ($newPilot === '' || !in_array($newPilot, $allowedPilots, true) || $newPilot === $oldPilot) {
		return 'gespeichert';
	}
	$feldId = trim((string)($data[$flugId]['Feld'] ?? ''));
	foreach ($data as $id => $row) {
		if ((string)$id === $flugId || !is_array($row)) {
			continue;
		}
		if (($row['Datum'] ?? '') === $tag && strcasecmp((string)($row['Pilot'] ?? ''), $newPilot) === 0 && strcasecmp((string)($row['Feld'] ?? ''), $feldId) === 0) {
			return 'doppelt';
		}
	}
	$data[$flugId]['Pilot'] = $newPilot;
	return 'zugewiesen';
}

function rc_einsatz_warning_html($warnung) {
	global $OptionWarning;
	$warnung = trim((string)$warnung);
	if ($warnung === '' || !is_array($OptionWarning ?? null)) {
		return '';
	}
	$html = '';
	foreach ($OptionWarning as $key => $warning) {
		if (!is_array($warning)) {
			continue;
		}
		$hit = function_exists('like') ? like($warnung, (string)$key) : (strpos($warnung, (string)$key) !== false);
		if (!$hit) {
			continue;
		}
		$pic = (string)($warning['Pic'] ?? '');
		if ($pic === '') {
			continue;
		}
		$text = (string)($warning['Text'] ?? $key);
		$html .= '<img src="' . rc_einsatz_h(rc_einsatz_icon_src($pic)) . '" alt="" title="' . rc_einsatz_h($text) . '" height="26">';
	}
	return $html;
}

function rc_einsatz_zone_html($catalog) {
	$zones = array(
		array('120m', '#8dee8d', '120 m Flugzone'),
		array('70m', '#ffee8d', '70 m Flugzone'),
		array('150m', '#599bfe', '150 m Flugzone'),
		array('Nachtflugzone 1', '#327dff', 'Nachtflugzone 1'),
		array('Nachtflugzone 2', '#8fb8ff', 'Nachtflugzone 2'),
		array('Nachtflugzone 3', '#327dff', 'Nachtflugzone 3'),
		array('Nachtflugzone', '#327dff', 'Nachtflugzone'),
		array('Flugverbot', '#e23b3b', 'Flugverbotszone'),
	);
	$name = '';
	if (is_array($catalog) && function_exists('GetPoiOfPointCharacteristics')) {
		$center = trim((string)($catalog['Grundstueckdaten']['Zentrum'] ?? ''));
		$parts = array_map('trim', explode(',', $center));
		if (count($parts) >= 2 && $parts[0] !== '' && $parts[1] !== '') {
			$poi = GetPoiOfPointCharacteristics(array('Lon' => $parts[0], 'Lat' => $parts[1]), array('FlgZ'));
			$name = trim((string)($poi['FlgZ']['Name'] ?? ''));
		}
	}
	if ($name === '') {
		return '';
	}
	foreach ($zones as $zone) {
		if (stripos($name, $zone[0]) === false) {
			continue;
		}
		return '<span class="zone" style="background:' . rc_einsatz_h($zone[1]) . '" title="' . rc_einsatz_h($name) . '"></span>';
	}
	return '<span class="zone" title="' . rc_einsatz_h($name) . '"></span>';
}

function rc_einsatz_kmz_icon($kmzRel) {
	global $IconLoc;
	$height = 'xx';
	if (preg_match('/_(\d+)_[A-Za-z]{1,4}_\d{2}\.[^.]+$/', (string)$kmzRel, $m)) {
		$height = $m[1];
	}
	$dir = rtrim(str_replace('\\', '/', (string)$IconLoc), '/');
	$file = 'kmz' . $height . '.png';
	if ($dir === '' || !is_file($dir . '/' . $file)) {
		$file = 'kmzxx.png';
	}
	return rc_einsatz_icon_src($file);
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$tag = rc_einsatz_date($_POST['tag'] ?? $_GET['tag'] ?? $today);
$pilot = strtoupper(trim((string)($_POST['pilot'] ?? $_GET['pilot'] ?? 'TC')));
$pilotCodes = function_exists('rkrk_pilot_select_codes') ? rkrk_pilot_select_codes('assign', $tag, '', false) : array('TC', 'CH');
$pilotCodes = array_values(array_unique(array_filter(array_map(static function ($code) {
	return strtoupper(trim((string)$code));
}, $pilotCodes), static function ($code) {
	return $code !== '';
})));
if ($pilotCodes === array()) {
	$pilotCodes = array('TC');
}
if (!in_array($pilot, $pilotCodes, true)) {
	$pilot = in_array('TC', $pilotCodes, true) ? 'TC' : $pilotCodes[0];
}
$suche = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
$notice = trim((string)($_GET['msg'] ?? ''));

if ((function_exists('rkrk_catalog_requires_expert') && rkrk_catalog_requires_expert($rcMission) && !rkrk_expert_catalogs_visible()) || !isset($MissionDroneTypes[$rcMission]) || empty($MissionDroneTypes[$rcMission]['active'])) {
	$rcMission = '3T';
	$_GET['MDB'] = '3T';
	$RKRKMissionSelected = '3T';
	SetMissionLocation();
}
if (function_exists('rkrk_apply_m3t_catalog_paths')) {
	rkrk_apply_m3t_catalog_paths();
}
$Feldkatalog = function_exists('GetFeldkatalog') ? GetFeldkatalog((string)$xmlMissionLocation . (string)$RKRKFelderfileXML) : array();
if (!is_array($Feldkatalog)) {
	$Feldkatalog = array();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && rc_einsatz_allowed()) {
	$year = (int)substr($tag, 0, 4);
	$data = rc_einsatz_load($year);
	$action = trim((string)($_POST['action'] ?? ''));
	$msg = 'fehler';
	if ($action === 'save' || $action === 'save_all') {
		$postedRows = array();
		if ($action === 'save_all' && isset($_POST['rows']) && is_array($_POST['rows'])) {
			$postedRows = $_POST['rows'];
		} else {
			$postedRows = array($_POST);
		}
		$results = array();
		foreach ($postedRows as $fields) {
			if (!is_array($fields)) {
				continue;
			}
			$results[] = rc_einsatz_store_row($data, $fields['flug_id'] ?? '', $fields, $tag);
		}
		$results = array_values(array_filter($results, static function ($result) {
			return $result !== 'fehlt';
		}));
		if ($results === array()) {
			$msg = 'fehler';
		} elseif ($action === 'save' && in_array('doppelt', $results, true)) {
			$msg = 'doppelt';
		} elseif (!rc_einsatz_save($year, $data)) {
			$msg = 'fehler';
		} elseif (in_array('doppelt', $results, true)) {
			$msg = 'doppelt';
		} elseif ($action === 'save_all') {
			$msg = 'alles';
		} elseif (in_array('zugewiesen', $results, true)) {
			$msg = 'zugewiesen';
		} else {
			$msg = 'gespeichert';
		}
	} elseif ($action === 'delete') {
		$flugId = trim((string)($_POST['flug_id'] ?? ''));
		$feld = rkrk_einsatz_delete_flight($data, $flugId);
		if ($feld !== null) {
			if (function_exists('WriteToLog')) {
				WriteToLog('del', basename($_SERVER['PHP_SELF']), 'Flug gelöscht', $flugId . ' - ' . $feld, $RKRKMissionSelected ?? '', '');
			}
			$msg = rc_einsatz_save($year, $data) ? 'geloescht' : 'fehler';
		}
	} elseif ($action === 'lock' || $action === 'free') {
		$status = $action === 'lock' ? 'locked' : '';
		$changed = false;
		foreach ($data as $id => $row) {
			if (!is_array($row) || ($row['Datum'] ?? '') !== $tag) {
				continue;
			}
			if (strcasecmp((string)($row['Pilot'] ?? ''), $pilot) !== 0) {
				continue;
			}
			if ($tag >= $today && ($row['Geflogen'] ?? '') === 'Nein') {
				continue;
			}
			$data[$id]['Status'] = $status;
			$changed = true;
		}
		$msg = ($changed && rc_einsatz_save($year, $data)) ? 'freigabe' : 'fehler';
	} elseif ($action === 'add') {
		$feldId = trim((string)($_POST['feld_id'] ?? ''));
		$catalog = rc_einsatz_catalog_row($feldId);
		$duplicate = false;
		foreach ($data as $row) {
			if (($row['Datum'] ?? '') === $tag && strcasecmp((string)($row['Pilot'] ?? ''), $pilot) === 0 && strcasecmp((string)($row['Feld'] ?? ''), $feldId) === 0) {
				$duplicate = true;
				break;
			}
		}
		if ($duplicate) {
			$msg = 'doppelt';
		} elseif ($catalog === array()) {
			$msg = 'unkatalog';
		} else {
			$flugId = rkrk_einsatz_append_catalog_flight(
				$data,
				$feldId,
				$tag,
				$pilot,
				mb_substr(trim((string)($_POST['zeit'] ?? '')), 0, 8)
			);
			$msg = ($flugId !== '' && rc_einsatz_save($year, $data)) ? 'hinzugefuegt' : 'fehler';
		}
	}
	$backQuery = array(
		'tag' => $tag,
		'pilot' => $pilot,
		'mdb' => $rcMission,
		'q' => $suche,
		'msg' => $msg,
	);
	if (trim((string)($_POST['ansicht'] ?? '')) === 'liste') {
		$backQuery['ansicht'] = 'liste';
	}
	$back = $_SERVER['PHP_SELF'] . '?' . http_build_query($backQuery);
	header('Location: ' . $back);
	exit;
}

$rcLoggedOn = isset($_SESSION[$SessNme_RegisteredUser]) && trim((string)$_SESSION[$SessNme_RegisteredUser]) !== '';
if (!$rcLoggedOn) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>RC Einsatz</title>';
	if (function_exists('rkrk_favicon_link_tags_html')) {
		echo rkrk_favicon_link_tags_html();
	}
	echo '</head><body>';
}

GrantAccess();

if (!rc_einsatz_allowed()) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><title>RC Einsatz</title>';
	if (function_exists('rkrk_favicon_link_tags_html')) {
		echo rkrk_favicon_link_tags_html();
	}
	echo '</head><body>';
	echo '<p>RC Einsatz ist für dieses Benutzerkonto im Menü nicht freigegeben.</p>';
	echo '<p><a href="../rkrk.php">Zur Startseite</a></p></body></html>';
	exit;
}

$year = (int)substr($tag, 0, 4);
$flights = rc_einsatz_load($year);
$rows = array();
foreach ($flights as $row) {
	if (!is_array($row)) {
		continue;
	}
	if (($row['Datum'] ?? '') !== $tag) {
		continue;
	}
	if (strcasecmp((string)($row['Pilot'] ?? ''), $pilot) !== 0) {
		continue;
	}
	$rows[] = $row;
}
usort($rows, static function ($a, $b) {
	$time = strcmp((string)($a['Zeit'] ?? ''), (string)($b['Zeit'] ?? ''));
	if ($time !== 0) {
		return $time;
	}
	return strcmp((string)($a['Feld'] ?? ''), (string)($b['Feld'] ?? ''));
});

$kmzAll = array();
$jsonAll = array();
$checklistFields = array();
$checklistTimes = array();
$auftrag = array();
$firstOrt = '';
$firstJg = '';
$firstTime = '';
$locked = false;
foreach ($rows as $row) {
	$feldId = trim((string)($row['Feld'] ?? ''));
	$skipPlan = ($tag >= $today && ($row['Geflogen'] ?? '') === 'Nein');
	if (!$skipPlan && ($row['Status'] ?? '') === 'locked') {
		$locked = true;
	}
	if (rc_einsatz_kmz_path($feldId) !== '') {
		$kmzRel = rc_einsatz_catalog_file($feldId, 'KMZFile');
		$convHref = function_exists('rkrk_kmz_convert_download_href') ? rkrk_kmz_convert_download_href($kmzRel) : '';
		$kmzHref = $convHref !== '' ? rc_einsatz_web_href($convHref) : rc_einsatz_mission_href($kmzRel);
		if ($kmzHref !== '') {
			$kmzAll[$kmzHref] = $kmzHref;
			$kmlHref = function_exists('rkrk_debug_kml_href') ? rkrk_debug_kml_href($kmzRel) : '';
			if ($kmlHref !== '') {
				$kmlHref = rc_einsatz_web_href($kmlHref);
				$kmzAll[$kmlHref] = $kmlHref;
			}
		}
	}
	$jsonRel = rc_einsatz_catalog_file($feldId, 'JasonFile');
	if ($jsonRel !== '' && function_exists('rkrk_resolve_mission_file_path')) {
		$jsonPath = rkrk_resolve_mission_file_path($jsonRel);
		if ($jsonPath !== '' && is_file($jsonPath)) {
			$jsonHref = rc_einsatz_mission_href($jsonRel);
			if ($jsonHref !== '') {
				$jsonAll[$jsonHref] = $jsonHref;
			}
		}
	}
	if ($feldId !== '') {
		$checklistFields[] = $feldId;
		$zeit = trim((string)($row['Zeit'] ?? ''));
		$checklistTimes[] = $feldId . '=' . ($zeit !== '' ? substr($zeit, 0, 5) : '');
	}
	if ($firstTime === '' && trim((string)($row['Zeit'] ?? '')) !== '') {
		$firstTime = substr(trim((string)$row['Zeit']), 0, 5);
	}
	if ($firstOrt === '') {
		$firstOrt = rkrk_fluege_scalar_text($row['Gde'] ?? '');
		$firstJg = rkrk_fluege_scalar_text($row['JG'] ?? '');
	}
	foreach (array($row['Jaeger'] ?? '', $row['Landwirt'] ?? '') as $who) {
		$who = trim((string)$who);
		if ($who !== '') {
			$auftrag[$who] = $who;
		}
	}
}
$checklist = rkrk_einsatz_checklist_href(array(
	'Datum & Zeit' => $tag . 'T' . ($firstTime !== '' ? $firstTime : '00:00'),
	'Flugort' => NormalizeChecklistFlugort($firstOrt, $firstJg),
	'Pilot' => ChecklistPilotName($pilot),
	'Feld#' => implode(', ', $checklistFields),
	'Zeit zum Feld' => implode('; ', $checklistTimes),
	'Auftraggeber' => implode(', ', array_values($auftrag)),
	'Art des Fluges' => 'Auftrag',
));
$planFields = array();
foreach ($checklistFields as $feldId) {
	$planFields[$feldId] = rawurlencode($feldId);
}
$planFilter = implode('+', array_values($planFields));
$pdfHref = rc_einsatz_web_href('rkrk_pdf_fluege.php?FromDate=' . rawurlencode($tag) . '&ToDate=' . rawurlencode($tag) . '&Pilot=' . rawurlencode($pilot) . '&MDB=' . rawurlencode($rcMission));
$mapHref = $planFilter === '' ? '' : rc_einsatz_web_href('rkrk_map.php?FFilter=' . $planFilter . '&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off&WW=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&MDB=' . rawurlencode($rcMission) . '&Top=off&Side=off');

$catalogSearch = array();
if (is_array($Feldkatalog ?? null)) {
	foreach ($Feldkatalog as $fields) {
		if (!is_array($fields)) {
			continue;
		}
		foreach ($fields as $key => $field) {
			if (!is_array($field)) {
				continue;
			}
			$fid = rkrk_fluege_scalar_text($field['FeldID'] ?? $key);
			if ($fid === '' || isset($catalogSearch[$fid])) {
				continue;
			}
			$label = rkrk_fluege_build_field_text($field);
			$gemeinde = rkrk_fluege_scalar_text($field['Gemeinde'] ?? '');
			$jaeger = rkrk_fluege_scalar_text($field['Jaeger'] ?? '');
			$catalogSearch[$fid] = array(
				$fid,
				mb_strtolower($fid . ' ' . $label . ' ' . $gemeinde . ' ' . $jaeger),
				trim($fid . ' · ' . $label . ($gemeinde !== '' ? ' · ' . $gemeinde : '') . ($jaeger !== '' ? ' · ' . $jaeger : '')),
				$jaeger,
			);
		}
	}
}
$catalogSearchJson = json_encode(array_values($catalogSearch), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($catalogSearchJson === false) {
	$catalogSearchJson = '[]';
}

$messages = array(
	'gespeichert' => 'Zeile gespeichert. Dieselbe Datei wie die Flugbuchungen.',
	'alles' => 'Alle Zeilen gespeichert.',
	'zugewiesen' => 'Feld wurde dem anderen Piloten zugewiesen.',
	'geloescht' => 'Feld aus dem Einsatz gelöscht.',
	'freigabe' => 'Freigabe des Pilotentags gespeichert.',
	'hinzugefuegt' => 'Feld aus dem Katalog hinzugefügt.',
	'doppelt' => 'Dieses Feld ist an diesem Tag für den Piloten schon gebucht.',
	'unkatalog' => 'Feldnummer steht nicht im Katalog.',
	'fehler' => 'Speichern ist fehlgeschlagen.',
);
$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$showList = (trim((string)($_GET['ansicht'] ?? '')) === 'liste');
$entryHref = $self . '?tag=' . rc_einsatz_h($tag) . '&amp;pilot=' . rc_einsatz_h($pilot) . '&amp;mdb=' . rc_einsatz_h($rcMission);
$listHref = $entryHref . '&amp;ansicht=liste';
$rcHome = '../rkrk_rc_access.php?mdb=' . rc_einsatz_h($rcMission);
$ansicht = (trim((string)($_GET['ansicht'] ?? '')) === 'liste') ? 'liste' : '';
$qsHome = 'tag=' . rawurlencode($tag) . '&amp;pilot=' . rawurlencode($pilot) . '&amp;mdb=' . rawurlencode($rcMission);
$qsList = $qsHome . '&amp;ansicht=liste';
$rcHome = '../rkrk_rc_access.php?mdb=' . rawurlencode($rcMission);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>RC Einsatz</title>
  <?php if (function_exists('rkrk_favicon_link_tags_html')) { echo rkrk_favicon_link_tags_html(); } ?>
  <style>
    :root { --hit: 34px; --ink: #172033; --muted: #5c6b7a; --line: #d5dde6; --bg: #f3f6f4; --card: #fff; --brand: #48673A; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, Segoe UI, Arial, sans-serif; color: var(--ink); background: var(--bg); }
    header, main { padding: 8px 10px; }
    header { background: #121814; color: #f4f7f2; }
    h1 { margin: 0; font-size: 18px; }
    a { color: inherit; }
    .row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .drone-tag { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; }
    .drone-tag img { height: 28px; width: auto; display: block; }
    .pill, button, .go {
      min-height: var(--hit);
      border-radius: 8px;
      border: 1px solid transparent;
      padding: 0 10px;
      background: var(--brand);
      color: #fff;
      font: inherit;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .pill { background: transparent; border-color: rgba(255,255,255,.35); }
    .pill.is-on { background: #fff; color: #121814; }
    button.quiet, a.quiet { background: #fff; color: var(--ink); border-color: var(--line); }
    a.go.is-off { background: #d5ddd4; color: #5c6b7a; pointer-events: none; }
    input, select {
      width: 100%;
      min-height: var(--hit);
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 4px 8px;
      font: inherit;
      background: #fff;
    }
    input[type=date] { width: 11em; flex: 0 0 auto; }
    .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 8px; margin: 0 0 8px; }
    .meta { color: var(--muted); font-size: 13px; }
    .notice { background: #e7f0e2; border: 1px solid #b7cbb0; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
    label.inline { display: flex; flex-direction: column; gap: 2px; font-size: 12px; color: var(--muted); min-width: 0; }
    a.brand-fav { display: inline-flex; align-items: center; min-height: 0; padding: 0; background: transparent; }
    a.brand-fav img { width: 28px; height: 28px; object-fit: contain; background: transparent; }
    form.line {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 3px 6px;
      margin: 0 0 4px;
    }
    .thumb, .thumb img { width: 46px; height: 34px; flex: 0 0 auto; object-fit: cover; border-radius: 4px; display: block; background: #e7eee4; }
    .who { flex: 1 1 8em; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; }
    .marks { flex: 0 0 104px; width: 104px; display: inline-flex; align-items: center; justify-content: flex-end; gap: 2px; }
    .warns { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 2px; }
    .warns img { height: 26px; width: auto; display: block; }
    .zone { flex: 0 0 auto; width: 22px; height: 22px; border-radius: 4px; border: 1px solid rgba(0,0,0,.25); background: #9aa; }
    button.yn {
      flex: 0 0 auto;
      width: 4.8em;
      min-height: 32px;
      padding: 0 6px;
      background: #fff;
      color: #5c6b7a;
      border: 1px solid var(--line);
    }
    button.yn.is-ja { background: #48673A; color: #fff; border-color: #48673A; }
    button.yn.is-nein { background: #f6e6dc; color: #6a341c; border-color: #e2c3ae; }
    form.line input[name=fund] { flex: 0 0 6em; width: 6em; }
    form.line input[name=bemerkung] { flex: 0 0 14em; width: 14em; }
    button.icon, a.icon { background: transparent; border: 0; padding: 0; min-height: 0; min-width: 34px; }
    button.icon img, a.icon img { height: 32px; width: auto; display: block; }
    button.more { width: auto; min-width: 34px; font-weight: 700; color: var(--ink); }
    dialog.details { border: 0; border-radius: 12px; padding: 14px; width: min(28rem, 94vw); }
    dialog.details::backdrop { background: rgba(18, 24, 20, .45); }
    dialog.details h3 { margin: 0 0 10px; font-size: 16px; }
    dialog.details label { display: flex; flex-direction: column; gap: 2px; margin: 0 0 8px; font-size: 12px; color: var(--muted); }
    dialog.details .dlg-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px; }
    dialog.details .dlg-actions button { width: auto; min-height: 36px; padding: 0 12px; }
    a.icon.is-off { opacity: 0.35; pointer-events: none; }
    .daybar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; font-size: 13px; color: #d5e0d2; }
    .daybar form { margin: 0; }
    .daybar a, .daybar button { background: transparent; border: 0; padding: 0; min-height: 0; min-width: 0; }
    .daybar img { height: 28px; width: auto; display: block; }
    .daybar a.is-off, .daybar button:disabled { opacity: 0.35; pointer-events: none; }
    .search-line { display: flex; gap: 6px; align-items: center; }
    .search-line input { flex: 1 1 auto; }
    .search-hits { margin: 6px 0 0; color: var(--muted); font-size: 13px; line-height: 1.35; white-space: nowrap; overflow-x: auto; }
    .search-hits button.hit { min-height: 0; min-width: 0; padding: 0; border: 0; border-radius: 0; background: transparent; color: inherit; font: inherit; }
    .search-hits button.hit.is-on { color: var(--brand); font-weight: 700; }
    .search-hits button.hit + button.hit::before { content: ', '; color: var(--muted); font-weight: 400; }
    #feldAdd[hidden] { display: none; }
    .open-list { display: none; }
    .menu-fold { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .menu-bar, .menu-close { display: none; }
    .line-top, .line-bot { display: contents; }
    @media (max-width: 1100px) {
      header.home-head { position: sticky; top: 0; z-index: 4; padding: 6px 8px; }
      .menu-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
      }
      .menu-toggle {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        min-height: 36px;
        margin: 0;
        cursor: pointer;
        font-weight: 700;
      }
      .menu-toggle .when { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .menu-toggle .chev { flex: 0 0 auto; display: inline-block; }
      .menu-fold:checked ~ .home-head .menu-bar .chev { transform: rotate(90deg); }
      .menu-fold:not(:checked) ~ .home-head .menu-body,
      .menu-fold:not(:checked) ~ .list-pane .catalog-add { display: none; }
      .menu-body .brand-fav,
      .menu-body h1,
      .menu-body .menu-close,
      .menu-body .drone-tag,
      .menu-body form .row > strong { display: none; }
      form.line {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        grid-template-rows: auto auto;
        align-items: center;
        gap: 4px 6px;
        padding: 4px;
      }
      form.line .thumb,
      form.line .thumb img { width: 42px; height: 58px; grid-row: 1 / span 2; grid-column: 1; }
      form.line .line-top,
      form.line .line-bot {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 4px;
        min-width: 0;
        overflow: hidden;
        grid-column: 2;
      }
      form.line .line-top { grid-row: 1; }
      form.line .line-bot { grid-row: 2; }
      form.line .who { flex: 1 1 auto; font-size: 13px; }
      form.line .warns img { height: 20px; }
      form.line .zone { width: 16px; height: 16px; }
      form.line button.yn { width: 3.2em; min-height: 28px; padding: 0 4px; font-size: 13px; }
      form.line .marks { flex: 0 1 auto; width: auto; }
      form.line input[name="fund"],
      form.line input[name="bemerkung"] { min-height: 28px; flex: 1 1 4em; width: auto; }
      form.line input[name="fund"] { flex: 0 0 4.4em; width: 4.4em; }
      form.line .icon img,
      form.line button.more { height: 26px; }
      form.line .icon,
      form.line button.more { min-width: 26px; min-height: 26px; }
    }
  </style>
</head>
<body>
<input type="checkbox" id="menuFold" class="menu-fold">
<header class="home-head">
  <div class="menu-bar">
    <a class="brand-fav" href="<?php echo rc_einsatz_h($rcHome); ?>" title="RC Zugang"><img src="<?php echo rc_einsatz_h(function_exists('rkrk_favicon_assets') ? (string)(rkrk_favicon_assets()['png'] ?? '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png') : '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png'); ?>" alt="RKRK"></a>
    <label class="menu-toggle" for="menuFold">
      <span class="when">RKRK Einsätze · <?php echo rc_einsatz_h($tag); ?> · <?php echo rc_einsatz_h($pilot); ?> · <?php echo count($rows); ?></span>
      <span class="chev" aria-hidden="true">▸</span>
    </label>
    <span class="drone-tag" title="<?php echo rc_einsatz_h((string)($MissionDroneTypes[$rcMission]['Name'] ?? $rcMission)); ?>"><?php
      $droneBtnBar = (string)($MissionDroneTypes[$rcMission]['button'] ?? '');
      if ($droneBtnBar !== '') {
        echo '<img src="' . rc_einsatz_h(rc_einsatz_icon_src($droneBtnBar)) . '" alt="">';
      }
    ?></span>
  </div>
  <div class="menu-body">
  <form method="get" action="<?php echo $self; ?>">
    <div class="row">
      <a class="brand-fav" href="<?php echo rc_einsatz_h($rcHome); ?>" title="RC Zugang"><img src="<?php echo rc_einsatz_h(function_exists('rkrk_favicon_assets') ? (string)(rkrk_favicon_assets()['png'] ?? '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png') : '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png'); ?>" alt="RKRK"></a>
      <label class="menu-close" for="menuFold" title="Menü zuklappen">▾</label>
      <h1>RC Einsatz</h1>
      <a class="pill<?php echo $tag === $today ? ' is-on' : ''; ?>" href="<?php echo $self . '?tag=' . $today . '&amp;pilot=' . rc_einsatz_h($pilot) . '&amp;mdb=' . rc_einsatz_h($rcMission); ?>">Heute</a>
      <a class="pill<?php echo $tag === $tomorrow ? ' is-on' : ''; ?>" href="<?php echo $self . '?tag=' . $tomorrow . '&amp;pilot=' . rc_einsatz_h($pilot) . '&amp;mdb=' . rc_einsatz_h($rcMission); ?>">Morgen</a>
      <input type="date" name="tag" value="<?php echo rc_einsatz_h($tag); ?>" />
      <input type="hidden" name="pilot" value="<?php echo rc_einsatz_h($pilot); ?>" />
      <input type="hidden" name="mdb" value="<?php echo rc_einsatz_h($rcMission); ?>" />
      <button type="submit">Datum</button>
      <strong><?php echo count($rows); ?></strong>
      <span class="drone-tag" title="<?php echo rc_einsatz_h((string)($MissionDroneTypes[$rcMission]['Name'] ?? $rcMission)); ?>"><?php
        $droneBtn = (string)($MissionDroneTypes[$rcMission]['button'] ?? '');
        if ($droneBtn !== '') {
          echo '<img src="' . rc_einsatz_h(rc_einsatz_icon_src($droneBtn)) . '" alt="">';
        }
      ?></span>
    </div>
  </form>
  <div class="row" style="margin-top:6px;">
    <?php foreach ($pilotCodes as $code): ?>
      <a class="pill<?php echo strcasecmp($code, $pilot) === 0 ? ' is-on' : ''; ?>" href="<?php echo $self . '?tag=' . rc_einsatz_h($tag) . '&amp;pilot=' . rc_einsatz_h($code) . '&amp;mdb=' . rc_einsatz_h($rcMission); ?>"><?php echo rc_einsatz_h($code); ?></a>
    <?php endforeach; ?>
  </div>
  <div class="daybar">
    <button type="button" id="saveAll" title="Alle Zeilen speichern"<?php echo $rows === array() ? ' disabled' : ''; ?>><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('save_line.png')); ?>" alt="Alle speichern"></button>
    <span>Freigabe</span>
    <form method="post" action="<?php echo $self; ?>">
      <input type="hidden" name="action" value="<?php echo $locked ? 'free' : 'lock'; ?>" />
      <input type="hidden" name="tag" value="<?php echo rc_einsatz_h($tag); ?>" />
      <input type="hidden" name="pilot" value="<?php echo rc_einsatz_h($pilot); ?>" />
      <input type="hidden" name="mdb" value="<?php echo rc_einsatz_h($rcMission); ?>" />
      <button type="submit" title="<?php echo $locked ? 'Gesperrt, zum Freigeben drücken' : 'Freigegeben, zum Sperren drücken'; ?>"<?php echo $rows === array() ? ' disabled' : ''; ?>><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src($locked ? 'locked.png' : 'released.png')); ?>" alt="Freigabe"></button>
    </form>
    <span>Tagesflugplan</span>
    <?php if ($pdfHref !== '' && $rows !== array()): ?>
      <a href="<?php echo rc_einsatz_h($pdfHref); ?>" target="_pdfplan" title="Tagesflugplan des Piloten auf PDF"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('pdf_pilot.png')); ?>" alt="PDF"></a>
    <?php else: ?>
      <a class="is-off" href="#" title="Tagesflugplan des Piloten auf PDF"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('pdf_pilot.png')); ?>" alt="PDF"></a>
    <?php endif; ?>
    <?php if ($mapHref !== ''): ?>
      <a href="<?php echo rc_einsatz_h($mapHref); ?>" target="_geomapflight" title="Tagesflugplan des Piloten auf der Karte"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('geomapbutton_pilot.png')); ?>" alt="Karte"></a>
    <?php else: ?>
      <a class="is-off" href="#" title="Tagesflugplan des Piloten auf der Karte"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('geomapbutton_pilot.png')); ?>" alt="Karte"></a>
    <?php endif; ?>
    <a href="<?php echo rc_einsatz_h($checklist); ?>" target="_checklist_new" title="Checkliste"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('button_checklist.png')); ?>" alt="Checkliste"></a>
    <button type="button" id="allKmz" title="Alle KMZ dieses Piloten"<?php echo $kmzAll === array() ? ' disabled' : ''; ?>><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('downloadfiles.png')); ?>" alt="KMZ"></button>
    <button type="button" id="allJson" title="Alle JSON dieses Piloten"<?php echo $jsonAll === array() ? ' disabled' : ''; ?>><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('downloadjsonfiles.png')); ?>" alt="JSON"></button>
    <span>des Piloten</span>
  </div>
  </div>
</header>
<main class="list-pane">
  <?php if (isset($messages[$notice])): ?>
    <div class="notice"><?php echo rc_einsatz_h($messages[$notice]); ?></div>
  <?php endif; ?>

  <div class="card catalog-add">
    <div class="search-line">
      <input id="feldSuche" value="<?php echo rc_einsatz_h($suche); ?>" placeholder="Feld aus dem Katalog" autocomplete="off" />
    </div>
    <p id="searchHits" class="search-hits" hidden></p>
    <form id="feldAdd" method="post" action="<?php echo $self; ?>" hidden>
      <input type="hidden" name="action" value="add" />
      <input type="hidden" name="tag" value="<?php echo rc_einsatz_h($tag); ?>" />
      <input type="hidden" name="pilot" value="<?php echo rc_einsatz_h($pilot); ?>" />
      <input type="hidden" name="q" value="<?php echo rc_einsatz_h($suche); ?>" />
      <?php if ($showList): ?><input type="hidden" name="ansicht" value="liste" /><?php endif; ?>
      <input type="hidden" name="feld_id" value="" />
      <div class="meta"></div>
      <div class="row" style="margin-top:8px;">
        <input name="zeit" placeholder="Zeit" style="max-width:8em;" />
        <button type="submit">Hinzufügen</button>
      </div>
    </form>
  </div>

  <?php
    $saveIcon = rc_einsatz_icon_src('save_line.png');
    $deleteIcon = rc_einsatz_icon_src('delete.png');
    foreach ($rows as $row):
    $feldId = trim((string)($row['Feld'] ?? ''));
    $catalog = rc_einsatz_catalog_row($feldId);
    $name = $catalog !== array() ? rkrk_fluege_build_field_text($catalog) : rkrk_fluege_scalar_text($row['Felder'] ?? '');
    $planRel = rc_einsatz_plan_file($feldId);
    $planHref = rc_einsatz_mission_href($planRel);
    $kmzRel = rc_einsatz_catalog_file($feldId, 'KMZFile');
    $kmzPath = rc_einsatz_kmz_path($feldId);
    $kmzConv = function_exists('rkrk_kmz_convert_download_href') ? rkrk_kmz_convert_download_href($kmzRel) : '';
    $kmzHref = $kmzConv !== '' ? rc_einsatz_web_href($kmzConv) : ($kmzPath !== '' ? rc_einsatz_mission_href($kmzRel) : '');
    $kmlHref = function_exists('rkrk_debug_kml_href') ? rkrk_debug_kml_href($kmzRel) : '';
    if ($kmlHref !== '') {
      $kmlHref = rc_einsatz_web_href($kmlHref);
    }
    $kmzIcon = rc_einsatz_kmz_icon($kmzRel);
    $geflogen = (string)($row['Geflogen'] ?? '');
    $kontroll = (string)($row['Kontrollflug'] ?? '');
    if ($geflogen !== 'Ja' && $geflogen !== 'Nein') { $geflogen = ''; }
    if ($kontroll !== 'Ja' && $kontroll !== 'Nein') { $kontroll = ''; }
    $flugId = (string)($row['FlugID'] ?? '');
    $jaeger = trim((string)($row['Jaeger'] ?? ''));
    if ($jaeger === '' && $catalog !== array()) {
      $jaeger = rkrk_fluege_scalar_text($catalog['Jaeger'] ?? '');
    }
    $title = trim(trim((string)($row['Zeit'] ?? '')) . '  ' . $feldId . '  ' . $name . ($jaeger !== '' ? '  ' . $jaeger : ''));
    $warnung = $catalog !== array() ? rkrk_fluege_scalar_text($catalog['Warnung'] ?? '') : '';
    if ($warnung === '') {
      $warnung = rkrk_fluege_scalar_text($row['Warnung'] ?? '');
    }
    $warnHtml = rc_einsatz_warning_html($warnung);
    $zoneHtml = rc_einsatz_zone_html($catalog);
  ?>
    <form class="line" method="post" action="<?php echo $self; ?>">
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="tag" value="<?php echo rc_einsatz_h($tag); ?>" />
      <input type="hidden" name="pilot" value="<?php echo rc_einsatz_h($pilot); ?>" />
      <input type="hidden" name="flug_id" value="<?php echo rc_einsatz_h($flugId); ?>" />
      <?php if ($showList): ?><input type="hidden" name="ansicht" value="liste" /><?php endif; ?>
      <?php if ($planHref !== ''): ?>
        <a class="thumb" href="<?php echo rc_einsatz_h($planHref); ?>" target="_blank" title="Flugplan"><img src="<?php echo rc_einsatz_h($planHref); ?>" alt="Flugplan"></a>
      <?php else: ?>
        <span class="thumb" title="kein Flugplan"></span>
      <?php endif; ?>
      <div class="line-top">
        <span class="who" title="<?php echo rc_einsatz_h($title); ?>"><?php echo rc_einsatz_h($title); ?></span>
        <span class="marks"><?php echo $zoneHtml; ?><?php if ($warnHtml !== ''): ?><span class="warns"><?php echo $warnHtml; ?></span><?php endif; ?></span>
        <button type="button" class="yn<?php echo $geflogen === 'Ja' ? ' is-ja' : ($geflogen === 'Nein' ? ' is-nein' : ''); ?>" data-name="geflogen" data-prefix="G " title="Geflogen"><?php echo rc_einsatz_h('G ' . ($geflogen === '' ? '–' : $geflogen)); ?></button>
        <input type="hidden" name="geflogen" value="<?php echo rc_einsatz_h($geflogen); ?>" />
        <button type="button" class="yn<?php echo $kontroll === 'Ja' ? ' is-ja' : ($kontroll === 'Nein' ? ' is-nein' : ''); ?>" data-name="kontrollflug" data-prefix="K " title="Kontrollflug"><?php echo rc_einsatz_h('K ' . ($kontroll === '' ? '–' : $kontroll)); ?></button>
        <input type="hidden" name="kontrollflug" value="<?php echo rc_einsatz_h($kontroll); ?>" />
      </div>
      <div class="line-bot">
      <input name="fund" placeholder="Fund" title="Fund" value="<?php echo rc_einsatz_h($row['Fund'] ?? ''); ?>" />
      <input name="bemerkung" placeholder="Bemerkung" title="Bemerkung" value="<?php echo rc_einsatz_h($row['Bemerkung'] ?? ''); ?>" />
      <button class="icon more" type="button" title="Weitere Details">…</button>
      <dialog class="details">
        <h3><?php echo rc_einsatz_h($feldId . '  ' . $name); ?></h3>
        <label>Pilot
          <select name="zu_pilot">
            <?php
              $rowPilot = strtoupper(trim((string)($row['Pilot'] ?? '')));
              $choices = $pilotCodes;
              if ($rowPilot !== '' && !in_array($rowPilot, $choices, true)) {
                $choices[] = $rowPilot;
              }
              foreach ($choices as $code):
            ?>
              <option value="<?php echo rc_einsatz_h($code); ?>"<?php echo $code === $rowPilot ? ' selected' : ''; ?>><?php echo rc_einsatz_h($code); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Zeit<input name="zeit" value="<?php echo rc_einsatz_h($row['Zeit'] ?? ''); ?>" /></label>
        <label>Jäger<input name="jaeger" value="<?php echo rc_einsatz_h($row['Jaeger'] ?? ''); ?>" /></label>
        <label>Landwirt<input name="landwirt" value="<?php echo rc_einsatz_h($row['Landwirt'] ?? ''); ?>" /></label>
        <label>Kontakt<input name="kontakt" value="<?php echo rc_einsatz_h($row['Kontaktnummer'] ?? ''); ?>" /></label>
        <label>Flugzeit<input name="flugzeit" inputmode="decimal" value="<?php echo rc_einsatz_h($row['Flugzeit'] ?? ''); ?>" /></label>
        <label>UAV<input name="uav" value="<?php echo rc_einsatz_h($row['UAV#'] ?? ''); ?>" /></label>
        <label>Anzahl Funde<input name="anz_funde" value="<?php echo rc_einsatz_h($row['#Funde'] ?? ''); ?>" /></label>
        <label>Grundstück<input name="grundstueck" value="<?php echo rc_einsatz_h($row['GrundstückID'] ?? ''); ?>" /></label>
        <div class="dlg-actions">
          <button type="button" class="quiet close-details">Schliessen</button>
          <button type="submit">Speichern</button>
        </div>
      </dialog>
      <?php if ($kmzHref !== ''): ?>
        <a class="icon" href="<?php echo rc_einsatz_h($kmzHref); ?>"<?php echo $kmlHref !== '' ? ' data-kml="' . rc_einsatz_h($kmlHref) . '"' : ''; ?><?php echo $kmzConv === '' ? ' download="' . rc_einsatz_h(basename(str_replace('\\', '/', $kmzRel))) . '"' : ''; ?> title="KMZ herunterladen"><img src="<?php echo rc_einsatz_h($kmzIcon); ?>" alt="KMZ"></a>
      <?php else: ?>
        <a class="icon is-off" href="#" title="keine KMZ"><img src="<?php echo rc_einsatz_h(rc_einsatz_icon_src('kmzxx.png')); ?>" alt="KMZ"></a>
      <?php endif; ?>
      <button class="icon" type="submit" title="Speichern"><img src="<?php echo rc_einsatz_h($saveIcon); ?>" alt="Speichern"></button>
      <button class="icon drop" type="button" title="Feld löschen" data-feld="<?php echo rc_einsatz_h($feldId); ?>"><img src="<?php echo rc_einsatz_h($deleteIcon); ?>" alt="Löschen"></button>
      </div>
    </form>
  <?php endforeach; ?>
</main>
<script>
const catalogSearch = <?php echo $catalogSearchJson; ?>;
const feldSuche = document.getElementById('feldSuche');
const searchHits = document.getElementById('searchHits');
const feldAdd = document.getElementById('feldAdd');
let selectedFeld = '';
function feldHits(term) {
  const needle = String(term || '').trim().toLowerCase();
  if (needle.length < 2) return [];
  const likeSearch = needle.length > 4;
  return catalogSearch.filter((row) => {
    const id = String(row[0] || '');
    const hay = String(row[1] || '');
    if (likeSearch) return hay.indexOf(needle) !== -1;
    return id.toLowerCase().indexOf(needle) === 0;
  });
}
function renderFeldSuche() {
  if (!feldSuche || !searchHits || !feldAdd) return;
  const found = feldHits(feldSuche.value);
  if (found.length === 1) {
    selectedFeld = found[0][0];
  } else if (!found.some((row) => row[0] === selectedFeld)) {
    selectedFeld = '';
  }
  searchHits.replaceChildren();
  if (String(feldSuche.value || '').trim().length < 2) {
    searchHits.hidden = true;
  } else if (found.length === 0) {
    searchHits.hidden = false;
    searchHits.textContent = 'keine Felder';
  } else {
    searchHits.hidden = false;
    found.forEach((row) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'hit' + (row[0] === selectedFeld ? ' is-on' : '');
      btn.textContent = row[3] ? (row[0] + ' ' + row[3]) : row[0];
      btn.addEventListener('click', () => {
        selectedFeld = selectedFeld === row[0] ? '' : row[0];
        renderFeldSuche();
      });
      searchHits.appendChild(btn);
    });
  }
  const chosen = found.filter((row) => row[0] === selectedFeld);
  const q = feldAdd.querySelector('input[name="q"]');
  if (q) q.value = feldSuche.value;
  if (chosen.length === 1) {
    feldAdd.hidden = false;
    feldAdd.querySelector('input[name="feld_id"]').value = chosen[0][0];
    feldAdd.querySelector('.meta').textContent = chosen[0][2] || chosen[0][0];
  } else {
    feldAdd.hidden = true;
  }
}
if (feldSuche) {
  feldSuche.addEventListener('input', renderFeldSuche);
  renderFeldSuche();
}
function startPilotDownloads(urls) {
  if (!Array.isArray(urls) || urls.length === 0) return;
  let i = 0;
  function next() {
    if (i >= urls.length) return;
    const a = document.createElement('a');
    a.href = urls[i];
    if (String(urls[i]).indexOf('kml=1') === -1) {
      a.setAttribute('download', '');
    }
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    a.remove();
    i += 1;
    setTimeout(next, 180);
  }
  next();
}
document.addEventListener('click', (ev) => {
  const a = ev.target.closest('a[data-kml]');
  if (!a) return;
  const kml = a.getAttribute('data-kml');
  if (!kml) return;
  setTimeout(() => startPilotDownloads([kml]), 250);
});
const saveAll = document.getElementById('saveAll');
if (saveAll) {
  saveAll.addEventListener('click', () => {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = <?php echo json_encode($_SERVER['PHP_SELF'] ?? ''); ?>;
    const add = (name, value) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    };
    add('action', 'save_all');
    add('tag', <?php echo json_encode($tag); ?>);
    add('pilot', <?php echo json_encode($pilot); ?>);
    add('mdb', <?php echo json_encode($rcMission); ?>);
    let n = 0;
    document.querySelectorAll('form.line').forEach((row) => {
      const data = new FormData(row);
      data.forEach((value, key) => {
        if (key === 'action' || key === 'tag' || key === 'pilot' || key === 'q') return;
        add('rows[' + n + '][' + key + ']', value);
      });
      n += 1;
    });
    if (n === 0) return;
    document.body.appendChild(form);
    form.submit();
  });
}
const allKmz = document.getElementById('allKmz');
if (allKmz) {
  allKmz.addEventListener('click', () => startPilotDownloads(<?php echo json_encode(array_values($kmzAll), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>));
}
const allJson = document.getElementById('allJson');
if (allJson) {
  allJson.addEventListener('click', () => startPilotDownloads(<?php echo json_encode(array_values($jsonAll), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>));
}
const ynOrder = ['', 'Ja', 'Nein'];
document.querySelectorAll('button.yn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input[name="' + btn.dataset.name + '"]');
    if (!input) return;
    let i = ynOrder.indexOf(input.value);
    if (i < 0) i = 0;
    const next = ynOrder[(i + 1) % ynOrder.length];
    input.value = next;
    btn.textContent = (btn.dataset.prefix || '') + (next === '' ? '–' : next);
    btn.classList.remove('is-ja', 'is-nein');
    if (next === 'Ja') btn.classList.add('is-ja');
    if (next === 'Nein') btn.classList.add('is-nein');
  });
});
document.querySelectorAll('button.more').forEach((btn) => {
  btn.addEventListener('click', () => {
    const dlg = btn.closest('form').querySelector('dialog.details');
    if (dlg && dlg.showModal) dlg.showModal();
  });
});
document.querySelectorAll('button.close-details').forEach((btn) => {
  btn.addEventListener('click', () => {
    const dlg = btn.closest('dialog');
    if (dlg) dlg.close();
  });
});
document.querySelectorAll('button.drop').forEach((btn) => {
  btn.addEventListener('click', () => {
    const feld = btn.dataset.feld || 'dieses Feld';
    if (!window.confirm(feld + ' aus diesem Einsatz löschen?')) return;
    const form = btn.closest('form');
    const action = form.querySelector('input[name="action"]');
    if (action) action.value = 'delete';
    form.submit();
  });
});
</script>
</body>
</html>
