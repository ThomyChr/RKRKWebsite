<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Piloten')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_pilots.php');

global $OptionPilot, $PersonenDaten, $ImageLoc;
$msg = '';
$personFields = rkrk_config_person_fields();
$fileFields = array(
	'Pic' => array('label' => 'Foto', 'accept' => '.jpg,.jpeg,.png,.gif,.webp', 'kinds' => 'image'),
	'QR' => array('label' => 'EASA QR', 'accept' => '.jpg,.jpeg,.png,.gif,.webp', 'kinds' => 'image'),
	'RKRS_MA' => array('label' => 'Mitgliederausweis', 'accept' => '.pdf', 'kinds' => 'pdf'),
	'STSAcc' => array('label' => 'STS-Nachweis', 'accept' => '.pdf', 'kinds' => 'pdf'),
);
$textFields = array(
	'Vorname' => 'Vorname',
	'Name' => 'Name',
	'Tel' => 'Telefon',
	'EASA_ID' => 'EASA-ID',
	'EASA_BID' => 'Betreiber-ID',
	'EASA_exp' => 'Ablauf',
	'Kat' => 'Kategorie',
	'Flugberechtigung' => 'Flugberechtigung',
	'FlugzonenOption' => 'Flugzonenoption',
	'titel' => 'Titel',
	'country' => 'Land',
);

function rkrk_admin_h($s) {
	return htmlspecialchars(function_exists('rkrk_str') ? rkrk_str($s) : (string)$s, ENT_QUOTES, 'UTF-8');
}

function rkrk_admin_csv_set($csv) {
	$set = array();
	foreach (explode(',', strval($csv)) as $p) {
		$p = trim($p);
		if ($p === '') {
			continue;
		}
		$set[strtolower($p)] = $p;
	}
	return $set;
}

function rkrk_admin_posted_csv($posted, $key) {
	if (!isset($posted[$key])) {
		return '';
	}
	$raw = $posted[$key];
	if (is_array($raw)) {
		$raw = implode(',', $raw);
	}
	return rkrk_config_sanitize_pilot_csv($raw);
}

function rkrk_admin_level_list($optionPilot, $field, $defaults) {
	$out = array();
	$seen = array();
	foreach ($defaults as $lvl) {
		$key = strtolower($lvl);
		$out[] = $lvl;
		$seen[$key] = true;
	}
	foreach ($optionPilot as $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach (rkrk_admin_csv_set($row[$field] ?? '') as $low => $orig) {
			if (!isset($seen[$low])) {
				$out[] = $orig;
				$seen[$low] = true;
			}
		}
	}
	return $out;
}

function rkrk_admin_sel_code($raw, $optionPilot) {
	$s = trim((string)$raw);
	if ($s === '__new' || $s === '__none') {
		return $s;
	}
	$code = rkrk_config_sanitize_pilot_code($s);
	if ($code !== '' && isset($optionPilot[$code])) {
		return $code;
	}
	foreach ($optionPilot as $k => $_) {
		return (string)$k;
	}
	return '__new';
}

function rkrk_admin_person_dir() {
	global $ImageLoc;
	$dir = rtrim(str_replace('\\', '/', (string)($ImageLoc ?? 'images/')), '/') . '/pers/';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function rkrk_admin_person_url($file) {
	$file = basename((string)$file);
	if ($file === '') {
		return '';
	}
	$dir = rkrk_admin_person_dir();
	$path = $dir . $file;
	if (!is_file($path)) {
		return '';
	}
	return htmlspecialchars($dir . $file . '?v=' . filemtime($path), ENT_QUOTES, 'UTF-8');
}

function rkrk_admin_take_upload($formKey, $field, $currentName, $kinds, $destPrefix = null) {
	if (empty($_FILES['personfile']['name'][$formKey][$field])) {
		return $currentName;
	}
	$err = intval($_FILES['personfile']['error'][$formKey][$field] ?? UPLOAD_ERR_NO_FILE);
	if ($err === UPLOAD_ERR_NO_FILE || $err !== UPLOAD_ERR_OK) {
		return $currentName;
	}
	$tmp = (string)($_FILES['personfile']['tmp_name'][$formKey][$field] ?? '');
	$orig = (string)($_FILES['personfile']['name'][$formKey][$field] ?? '');
	$size = intval($_FILES['personfile']['size'][$formKey][$field] ?? 0);
	if ($tmp === '' || !is_uploaded_file($tmp) || $size < 1 || $size > 20 * 1024 * 1024) {
		return $currentName;
	}
	$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
	$okImage = array('jpg' => true, 'jpeg' => true, 'png' => true, 'gif' => true, 'webp' => true);
	$okPdf = array('pdf' => true);
	if ($kinds === 'image' && !isset($okImage[$ext])) {
		return $currentName;
	}
	if ($kinds === 'pdf' && !isset($okPdf[$ext])) {
		return $currentName;
	}
	$keep = rkrk_config_sanitize_person_file($currentName);
	$keepExt = strtolower(pathinfo($keep, PATHINFO_EXTENSION));
	$prefix = ($destPrefix !== null && $destPrefix !== '') ? $destPrefix : $formKey;
	if ($keep !== '' && $keepExt === $ext) {
		$destName = $keep;
	} else {
		$destName = rkrk_config_sanitize_person_file($prefix . '_' . strtolower($field) . '.' . $ext);
	}
	if ($destName === '') {
		return $currentName;
	}
	$dest = rkrk_admin_person_dir() . $destName;
	if (!@move_uploaded_file($tmp, $dest)) {
		return $currentName;
	}
	@chmod($dest, 0664);
	return $destName;
}

function rkrk_admin_echo_pills($name, $options, $selectedSet) {
	echo "<span class='rkrk-sels rkrk-reg-pills'>";
	foreach ($options as $opt) {
		$optH = rkrk_admin_h($opt);
		$chk = isset($selectedSet[strtolower($opt)]) ? ' checked' : '';
		echo "<label class='rkrk-sel rkrk-reg-pill'><input type='checkbox' name='{$name}' value='{$optH}'{$chk}> {$optH}</label>";
	}
	echo "</span>";
}

function rkrk_admin_person_library($kinds) {
	static $all = null;
	if ($all === null) {
		$all = array('image' => array(), 'pdf' => array());
		$skip = array(
			'verifi.gif' => true, 'reject.gif' => true, 'easa_logo.png' => true,
			'bazl_logo.png' => true, 'lba_logo.png' => true, 'swiss_flag.png' => true,
			'de-eu_flag.png' => true, 'easa_kopter.png' => true, 'easa_a1a3.png' => true,
			'easa_a2.png' => true, 'easa_sts.png' => true,
		);
		$dir = rkrk_admin_person_dir();
		if (is_dir($dir)) {
			$found = @scandir($dir);
			if (is_array($found)) {
				foreach ($found as $f) {
					$safe = rkrk_config_sanitize_person_file($f);
					if ($safe === '' || isset($skip[strtolower($safe)])) {
						continue;
					}
					$ext = strtolower(pathinfo($safe, PATHINFO_EXTENSION));
					if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png' || $ext === 'gif' || $ext === 'webp') {
						$all['image'][] = $safe;
					} elseif ($ext === 'pdf') {
						$all['pdf'][] = $safe;
					}
				}
			}
		}
		sort($all['image'], SORT_NATURAL | SORT_FLAG_CASE);
		sort($all['pdf'], SORT_NATURAL | SORT_FLAG_CASE);
	}
	return ($kinds === 'pdf') ? $all['pdf'] : $all['image'];
}

function rkrk_admin_echo_file_slot($pv, $field, $meta, $currentName) {
	$pvH = rkrk_admin_h($pv);
	$fieldH = rkrk_admin_h($field);
	$label = rkrk_admin_h($meta['label']);
	$accept = rkrk_admin_h($meta['accept']);
	$kinds = $meta['kinds'];
	$file = rkrk_config_sanitize_person_file($currentName);
	$url = $file !== '' ? rkrk_admin_person_url($file) : '';
	$dir = rkrk_admin_person_dir();
	echo "<div class='rkrk-admin-file'>";
	echo "<div class='rkrk-admin-file-label'>{$label}</div>";
	echo "<div class='rkrk-admin-file-preview' data-preview>";
	if ($file !== '' && $url !== '') {
		if ($kinds === 'image') {
			echo "<img src='{$url}' alt=''>";
		} else {
			echo "<iframe src='{$url}#toolbar=0' title='{$label}'></iframe>";
			echo "<a href='{$url}' target='_blank' rel='noopener'>PDF öffnen</a>";
		}
	} else {
		echo "<span class='rkrk-admin-file-empty'>keine Datei</span>";
	}
	echo "</div>";
	echo "<input type='hidden' name='personen[{$pvH}][{$fieldH}]' value='" . rkrk_admin_h($file) . "' data-file-name>";
	echo "<select data-kinds='" . rkrk_admin_h($kinds) . "' data-dir='" . rkrk_admin_h($dir) . "' onchange='rkrkAdminPickExisting(this)'>";
	echo "<option value=''>bestehende Datei…</option>";
	foreach (rkrk_admin_person_library($kinds) as $existing) {
		$exH = rkrk_admin_h($existing);
		$sel = (strcasecmp($existing, $file) === 0) ? ' selected' : '';
		$exUrl = rkrk_admin_person_url($existing);
		echo "<option value='{$exH}' data-url='{$exUrl}'{$sel}>{$exH}</option>";
	}
	echo "</select>";
	echo "<input type='file' name='personfile[{$pvH}][{$fieldH}]' accept='{$accept}' onchange='rkrkAdminPreview(this)'>";
	echo "</div>";
}

function rkrk_admin_pv_label($pv, $row) {
	$name = trim(strval($row['Vorname'] ?? '') . ' ' . strval($row['Name'] ?? ''));
	return $name !== '' ? ($pv . ' · ' . $name) : $pv;
}

$accessLevels = rkrk_admin_level_list($OptionPilot, 'Access', array('test', 'intern', 'partner', 'extern', 'pub'));
$visibleLevels = rkrk_admin_level_list($OptionPilot, 'VisibleOn', array('test', 'intern', 'partner', 'extern', 'pub'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$overlay = array();
	$postedPilots = (isset($_POST['pilots']) && is_array($_POST['pilots'])) ? $_POST['pilots'] : array();
	foreach ($OptionPilot as $code => $row) {
		$posted = $postedPilots[$code] ?? array();
		if (!is_array($posted)) {
			$posted = array();
		}
		$overlay[$code] = array(
			'Text' => rkrk_str($posted['Text'] ?? ($row['Text'] ?? '')),
			'Access' => rkrk_admin_posted_csv($posted, 'Access'),
			'VisibleOn' => rkrk_admin_posted_csv($posted, 'VisibleOn'),
			'PV' => rkrk_admin_posted_csv($posted, 'PV'),
			'plan' => !empty($posted['plan']),
			'active' => !empty($posted['active']),
			'until' => rkrk_config_sanitize_pilot_until($posted['until'] ?? ($row['until'] ?? '')),
		);
	}
	$newCode = rkrk_config_sanitize_pilot_code($_POST['new_opt'] ?? '');
	if ($newCode !== '' && !isset($overlay[$newCode])) {
		$postedNewPilot = array(
			'Access' => $_POST['new_access'] ?? array(),
			'VisibleOn' => $_POST['new_visible'] ?? array(),
			'PV' => $_POST['new_pv'] ?? array(),
		);
		$overlay[$newCode] = array(
			'Text' => rkrk_str($_POST['new_text'] ?? ''),
			'Access' => rkrk_admin_posted_csv($postedNewPilot, 'Access'),
			'VisibleOn' => rkrk_admin_posted_csv($postedNewPilot, 'VisibleOn'),
			'PV' => rkrk_admin_posted_csv($postedNewPilot, 'PV'),
			'plan' => !empty($_POST['new_plan']),
			'active' => !empty($_POST['new_active']),
			'until' => rkrk_config_sanitize_pilot_until($_POST['new_until'] ?? ''),
		);
	}

	$personOverlay = array();
	$postedPersons = (isset($_POST['personen']) && is_array($_POST['personen'])) ? $_POST['personen'] : array();
	foreach ($PersonenDaten as $pv => $row) {
		$posted = $postedPersons[$pv] ?? array();
		if (!is_array($posted)) {
			$posted = array();
		}
		$entry = array();
		foreach ($personFields as $field) {
			if ($field === 'verfied') {
				$entry[$field] = !empty($posted[$field]);
				continue;
			}
			$raw = $posted[$field] ?? ($row[$field] ?? '');
			if (isset($fileFields[$field])) {
				$current = rkrk_config_sanitize_person_file($raw);
				$entry[$field] = rkrk_admin_take_upload($pv, $field, $current, $fileFields[$field]['kinds']);
			} else {
				$entry[$field] = rkrk_str($raw);
			}
		}
		$personOverlay[$pv] = $entry;
	}
	$newPv = rkrk_config_sanitize_pv_key($_POST['new_pv_key'] ?? '');
	if ($newPv !== '' && !isset($personOverlay[$newPv])) {
		$entry = array();
		$postedNew = $postedPersons['__new'] ?? array();
		if (!is_array($postedNew)) {
			$postedNew = array();
		}
		foreach ($personFields as $field) {
			if ($field === 'verfied') {
				$entry[$field] = !empty($postedNew['verfied']);
				continue;
			}
			$raw = $postedNew[$field] ?? '';
			if (isset($fileFields[$field])) {
				$current = rkrk_config_sanitize_person_file($raw);
				$entry[$field] = rkrk_admin_take_upload('__new', $field, $current, $fileFields[$field]['kinds'], $newPv);
			} else {
				$entry[$field] = rkrk_str($raw);
			}
		}
		$personOverlay[$newPv] = $entry;
		$selForAssign = rkrk_admin_sel_code($_POST['sel'] ?? '', $overlay);
		if ($selForAssign !== '__new' && $selForAssign !== '__none' && isset($overlay[$selForAssign])) {
			$pvSet = rkrk_admin_csv_set($overlay[$selForAssign]['PV'] ?? '');
			if (!isset($pvSet[strtolower($newPv)])) {
				$overlay[$selForAssign]['PV'] = trim(($overlay[$selForAssign]['PV'] ?? '') . ',' . $newPv, ',');
			}
		}
	}

	$msg = rkrk_config_save('pilots', $overlay);
	$pmsg = rkrk_config_save('personen', $personOverlay);
	if ($msg === '' && $pmsg === '') {
		rkrk_config_apply_pilots($OptionPilot);
		rkrk_config_apply_personen($PersonenDaten);
		$msg = 'Gespeichert.';
		if ($newCode !== '') {
			$_POST['sel'] = $newCode;
		}
	} else {
		$msg = trim($msg . ' ' . $pmsg);
	}
}

$sel = rkrk_admin_sel_code($_POST['sel'] ?? ($_GET['sel'] ?? ''), $OptionPilot);
$assignedPv = array();
foreach ($OptionPilot as $code => $row) {
	foreach (rkrk_admin_csv_set($row['PV'] ?? '') as $low => $orig) {
		$assignedPv[$low] = true;
	}
}
$hasOrphans = false;
foreach ($PersonenDaten as $pv => $row) {
	if (empty($assignedPv[strtolower((string)$pv)])) {
		$hasOrphans = true;
		break;
	}
}

$echoPvCard = function ($pv, $row, $visible) use ($textFields, $fileFields) {
	if (!is_array($row)) {
		return;
	}
	$pvH = rkrk_admin_h($pv);
	$name = trim(strval($row['Vorname'] ?? '') . ' ' . strval($row['Name'] ?? ''));
	$picUrl = rkrk_admin_person_url(rkrk_config_sanitize_person_file($row['Pic'] ?? ''));
	$hide = $visible ? '' : ' hidden';
	echo "<article class='rkrk-admin-card' data-pv-card='" . rkrk_admin_h($pv) . "'{$hide}>";
	echo "<h3>";
	if ($picUrl !== '') {
		echo "<img class='rkrk-admin-thumb' src='{$picUrl}' alt=''> ";
	}
	echo "<code>{$pvH}</code>";
	if ($name !== '') {
		echo " · " . rkrk_admin_h($name);
	}
	echo "</h3>\n";
	echo "<div class='rkrk-admin-grid'>\n";
	foreach ($textFields as $field => $label) {
		echo "<label>" . rkrk_admin_h($label) . "<input type='text' name='personen[{$pvH}][" . rkrk_admin_h($field) . "]' value='" . rkrk_admin_h($row[$field] ?? '') . "'></label>";
	}
	echo "<label><span>Status</span><span class='rkrk-sels rkrk-reg-pills'>";
	rkrk_admin_echo_pill('checkbox', "personen[{$pvH}][verfied]", '1', !empty($row['verfied']), 'verifiziert');
	echo "</span></label>";
	echo "</div>\n";
	echo "<div class='rkrk-admin-files'>\n";
	foreach ($fileFields as $field => $meta) {
		rkrk_admin_echo_file_slot($pv, $field, $meta, $row[$field] ?? '');
	}
	echo "</div></article>\n";
};

echo <<<'CSS'
<style>
html.rkrk-shell .rkrk-shell-main:has(.rkrk-admin-split) { max-width: none; padding-bottom: 0; }
.rkrk-admin-split {
  display: grid;
  grid-template-columns: minmax(520px, 1.15fr) minmax(340px, 0.85fr);
  min-height: calc(100vh - 150px);
  border: 1px solid var(--rkrk-border, #d9dde2);
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
  margin: 0 0 16px;
}
.rkrk-admin-split-left {
  overflow: auto;
  border-right: 1px solid #d9dde2;
  background: #fff;
  min-height: 0;
  padding: 10px 10px 18px;
}
.rkrk-admin-split-right {
  overflow: auto;
  min-width: 0;
  min-height: 0;
  padding: 14px 16px 28px;
  background: #f4f7f4;
}
.rkrk-admin-list-head {
  padding: 4px 6px 10px;
  font-size: 12px;
  font-weight: 700;
  color: #5b6f86;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.rkrk-admin-pilot-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.rkrk-admin-pilot-table th {
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  color: #5b6f86;
  padding: 6px 8px;
  border-bottom: 1px solid #d9dde2;
  white-space: nowrap;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 1;
}
.rkrk-admin-pilot-table td {
  vertical-align: top;
  padding: 8px;
  border-bottom: 1px solid #eef1f4;
}
.rkrk-admin-pilot-table tr[data-pilot-row] { cursor: pointer; }
.rkrk-admin-pilot-table tr:hover td { background: #f3f6f3; }
.rkrk-admin-pilot-table tr.is-active td { background: #e8f0e4; }
.rkrk-admin-pilot-table tr.is-off td { opacity: .7; }
.rkrk-admin-pilot-table input[type=text] {
  width: 100%;
  min-width: 90px;
  box-sizing: border-box;
  font-size: 13px;
  padding: 4px 6px;
}
.rkrk-admin-pilot-table .rkrk-sels { max-width: 210px; }
.rkrk-admin-pilot-table .rkrk-sel,
.rkrk-admin-pilot-table .rkrk-reg-pill { font-size: 11px; padding: 1px 8px; min-height: 20px; }
.rkrk-admin-card {
  background: #fff;
  border: 1px solid var(--rkrk-border, #e4e7e4);
  border-radius: 10px;
  padding: 14px 16px;
  margin: 0 0 14px;
}
.rkrk-admin-card h2, .rkrk-admin-card h3 { margin: 0 0 10px; display: flex; align-items: center; gap: 8px; }
.rkrk-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px 14px; }
.rkrk-admin-grid label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: var(--rkrk-muted, #5a675a); }
.rkrk-admin-grid input[type=text] { font-size: 14px; padding: 6px 8px; }
.rkrk-admin-files { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; margin-top: 14px; }
.rkrk-admin-file { border: 1px dashed #c5d0c5; border-radius: 8px; padding: 10px; background: #f7faf7; }
.rkrk-admin-file-label { font-size: 12px; font-weight: 600; margin-bottom: 6px; }
.rkrk-admin-file-preview { min-height: 90px; margin-bottom: 8px; }
.rkrk-admin-file-preview img { max-width: 100%; max-height: 150px; display: block; border-radius: 6px; background: #fff; }
.rkrk-admin-file-preview iframe { width: 100%; height: 150px; border: 0; border-radius: 6px; background: #fff; }
.rkrk-admin-file-empty { color: #889188; font-size: 12px; }
.rkrk-admin-file input[type=file],
.rkrk-admin-file select { width: 100%; font-size: 12px; margin-top: 4px; }
.rkrk-admin-actions { display: flex; gap: 10px; align-items: center; margin: 0 0 12px; }
.rkrk-admin-muted { color: var(--rkrk-muted, #5a675a); font-size: 13px; margin: 0 0 10px; }
.rkrk-admin-thumb { width: 36px; height: 36px; object-fit: cover; border-radius: 50%; background: #fff; }
.rkrk-admin-field { margin: 0 0 12px; }
.rkrk-admin-field > span { display: block; font-size: 12px; font-weight: 600; color: #5a675a; margin-bottom: 6px; }
.rkrk-admin-flags { display: flex; flex-wrap: wrap; gap: 12px 18px; align-items: center; margin: 8px 0 12px; }
@media (max-width: 980px) {
  .rkrk-admin-split { grid-template-columns: 1fr; min-height: 0; }
  .rkrk-admin-split-left { max-height: 46vh; border-right: 0; border-bottom: 1px solid #d9dde2; }
}
</style>
CSS;

echo <<<'JS'
<script>
function rkrkAdminPreview(input) {
  var box = input.closest('.rkrk-admin-file');
  if (!box) return;
  var preview = box.querySelector('[data-preview]');
  var f = input.files && input.files[0];
  if (!preview || !f) return;
  var pick = box.querySelector('select');
  if (pick) pick.selectedIndex = 0;
  var url = URL.createObjectURL(f);
  preview.innerHTML = '';
  if ((f.type || '').indexOf('image/') === 0) {
    var img = document.createElement('img');
    img.src = url;
    img.alt = '';
    preview.appendChild(img);
  } else if (f.type === 'application/pdf' || /\.pdf$/i.test(f.name || '')) {
    var frame = document.createElement('iframe');
    frame.src = url + '#toolbar=0';
    preview.appendChild(frame);
  } else {
    var empty = document.createElement('span');
    empty.className = 'rkrk-admin-file-empty';
    empty.textContent = f.name;
    preview.appendChild(empty);
  }
}
function rkrkAdminPickExisting(sel) {
  var box = sel.closest('.rkrk-admin-file');
  if (!box) return;
  var preview = box.querySelector('[data-preview]');
  var hidden = box.querySelector('[data-file-name]');
  var upload = box.querySelector('input[type=file]');
  var opt = sel.options[sel.selectedIndex];
  var file = sel.value || '';
  if (!file) return;
  if (hidden) hidden.value = file;
  if (upload) upload.value = '';
  if (!preview) return;
  preview.innerHTML = '';
  var url = (opt && opt.getAttribute('data-url')) || ((sel.getAttribute('data-dir') || '') + file);
  if ((sel.getAttribute('data-kinds') || '') === 'image') {
    var img = document.createElement('img');
    img.src = url;
    img.alt = '';
    preview.appendChild(img);
  } else {
    var frame = document.createElement('iframe');
    frame.src = url + '#toolbar=0';
    preview.appendChild(frame);
    var a = document.createElement('a');
    a.href = url;
    a.target = '_blank';
    a.rel = 'noopener';
    a.textContent = 'PDF öffnen';
    preview.appendChild(a);
  }
}
function rkrkAdminSel() {
  var f = document.getElementById('rkrkAdminSel');
  return f ? String(f.value || '') : '';
}
function rkrkAdminSetSel(code) {
  var f = document.getElementById('rkrkAdminSel');
  if (f) f.value = code;
  document.querySelectorAll('[data-pilot-row]').forEach(function(row) {
    row.classList.toggle('is-active', row.getAttribute('data-pilot-row') === code);
  });
  document.querySelectorAll('[data-pilot-panel]').forEach(function(panel) {
    panel.hidden = panel.getAttribute('data-pilot-panel') !== code;
  });
  rkrkAdminSyncPv();
}
function rkrkAdminAssigned(code) {
  var set = {};
  if (code === '__new') {
    document.querySelectorAll('input[data-role="new-pv"]:checked').forEach(function(cb) {
      set[String(cb.value || '').toLowerCase()] = true;
    });
    return set;
  }
  document.querySelectorAll('input[data-role="pv"][data-pilot="' + code + '"]:checked').forEach(function(cb) {
    set[String(cb.value || '').toLowerCase()] = true;
  });
  return set;
}
function rkrkAdminOrphans() {
  var used = {};
  document.querySelectorAll('input[data-role="pv"]:checked, input[data-role="new-pv"]:checked').forEach(function(cb) {
    used[String(cb.value || '').toLowerCase()] = true;
  });
  return used;
}
function rkrkAdminSyncPv() {
  var code = rkrkAdminSel();
  var assigned = rkrkAdminAssigned(code);
  var used = rkrkAdminOrphans();
  document.querySelectorAll('[data-pv-card]').forEach(function(card) {
    var pv = String(card.getAttribute('data-pv-card') || '').toLowerCase();
    if (code === '__none') {
      card.hidden = !!used[pv];
    } else {
      card.hidden = !assigned[pv];
    }
  });
  var neu = document.getElementById('rkrkAdminNewPv');
  if (neu) neu.hidden = (code === '__none');
}
document.addEventListener('click', function(ev) {
  var row = ev.target.closest('[data-pilot-row]');
  if (!row) return;
  rkrkAdminSetSel(row.getAttribute('data-pilot-row'));
});
document.addEventListener('change', function(ev) {
  var t = ev.target;
  if (!t) return;
  if (t.getAttribute('data-role') === 'pv' || t.getAttribute('data-role') === 'new-pv') {
    rkrkAdminSyncPv();
  }
  if (t.getAttribute('data-role') === 'pilot-text') {
    var code = t.getAttribute('data-pilot');
    var lab = document.querySelector('[data-pilot-name="' + code + '"]');
    if (lab) lab.textContent = t.value || code;
  }
});
document.addEventListener('DOMContentLoaded', function() {
  rkrkAdminSetSel(rkrkAdminSel());
});
</script>
JS;

echo "<form method='post' enctype='multipart/form-data' class='rkrk-admin-pilots'>\n";
echo "<input type='hidden' name='MAX_FILE_SIZE' value='20971520'>\n";
echo "<input type='hidden' name='sel' id='rkrkAdminSel' value='" . rkrk_admin_h($sel) . "'>\n";
if ($msg !== '') {
	echo "<p><b>" . rkrk_admin_h($msg) . "</b></p>\n";
}

echo "<div class='rkrk-admin-split'>\n";
echo "<div class='rkrk-admin-split-left'>\n";
echo "<div class='rkrk-admin-list-head'>Piloten</div>\n";
echo "<table class='rkrk-admin-pilot-table'><thead><tr>";
echo "<th>Kürzel</th><th>Name</th><th>Until</th><th>Plan</th><th>Aktiv</th><th>Access</th><th>VisibleOn</th>";
echo "</tr></thead><tbody>\n";
foreach ($OptionPilot as $code => $row) {
	$codeH = rkrk_admin_h($code);
	$activeDefault = !array_key_exists('active', $row) || !empty($row['active']);
	$cls = ($sel === (string)$code ? ' is-active' : '') . ($activeDefault ? '' : ' is-off');
	echo "<tr class='{$cls}' data-pilot-row='{$codeH}'>";
	echo "<td><code>{$codeH}</code></td>";
	echo "<td><input type='text' name='pilots[{$codeH}][Text]' value='" . rkrk_admin_h($row['Text'] ?? '') . "' data-role='pilot-text' data-pilot='{$codeH}'></td>";
	echo "<td><input type='text' name='pilots[{$codeH}][until]' value='" . rkrk_admin_h($row['until'] ?? '') . "' placeholder='YYYY-MM-DD' size='11'></td>";
	echo "<td>";
	rkrk_admin_echo_pill('checkbox', "pilots[{$codeH}][plan]", '1', !empty($row['plan']), 'Plan');
	echo "</td><td>";
	rkrk_admin_echo_pill('checkbox', "pilots[{$codeH}][active]", '1', $activeDefault, 'aktiv');
	echo "</td>";
	echo "<td>";
	rkrk_admin_echo_pills("pilots[{$codeH}][Access][]", $accessLevels, rkrk_admin_csv_set($row['Access'] ?? ''));
	echo "</td><td>";
	rkrk_admin_echo_pills("pilots[{$codeH}][VisibleOn][]", $visibleLevels, rkrk_admin_csv_set($row['VisibleOn'] ?? ''));
	echo "</td></tr>\n";
}
$newActive = ($sel === '__new') ? ' is-active' : '';
echo "<tr class='{$newActive}' data-pilot-row='__new'>";
echo "<td><input name='new_opt' maxlength='5' placeholder='Kürzel' size='5'></td>";
echo "<td><input name='new_text' placeholder='Neues Kürzel'></td>";
echo "<td><input name='new_until' placeholder='YYYY-MM-DD' size='11'></td>";
echo "<td>";
rkrk_admin_echo_pill('checkbox', 'new_plan', '1', false, 'Plan');
echo "</td><td>";
rkrk_admin_echo_pill('checkbox', 'new_active', '1', true, 'aktiv');
echo "</td>";
echo "<td>";
rkrk_admin_echo_pills('new_access[]', $accessLevels, rkrk_admin_csv_set('partner,extern,pub'));
echo "</td><td>";
rkrk_admin_echo_pills('new_visible[]', $visibleLevels, rkrk_admin_csv_set('test,intern,partner,extern'));
echo "</td></tr>\n";
echo "</tbody></table>\n";
if ($hasOrphans) {
	$noneActive = ($sel === '__none') ? ' is-active' : '';
	echo "<p><button type='button' class='rkrk-admin-pilot-row{$noneActive}' data-pilot-row='__none'>Ohne Kürzel</button></p>\n";
}
echo "</div>\n";

echo "<div class='rkrk-admin-split-right'>\n";
echo "<div class='rkrk-admin-actions'><button type='submit'>Speichern</button>";
echo "<span class='rkrk-admin-muted'>Zeile links wählen. PV zuweisen, Sätze erscheinen hier.</span></div>\n";

foreach ($OptionPilot as $code => $row) {
	$codeH = rkrk_admin_h($code);
	$hidden = ($sel === (string)$code) ? '' : ' hidden';
	echo "<section class='rkrk-admin-card' data-pilot-panel='{$codeH}'{$hidden}>\n";
	echo "<h2>PV für <code>{$codeH}</code></h2>\n";
	echo "<div class='rkrk-admin-field'><span>PV zuweisen</span><span class='rkrk-sels rkrk-reg-pills'>";
	$pvSet = rkrk_admin_csv_set($row['PV'] ?? '');
	foreach ($PersonenDaten as $pv => $pdata) {
		$pvH = rkrk_admin_h($pv);
		$lab = rkrk_admin_h(rkrk_admin_pv_label($pv, is_array($pdata) ? $pdata : array()));
		$chk = isset($pvSet[strtolower((string)$pv)]) ? ' checked' : '';
		echo "<label class='rkrk-sel rkrk-reg-pill'><input type='checkbox' name='pilots[{$codeH}][PV][]' value='{$pvH}' data-role='pv' data-pilot='{$codeH}'{$chk}> {$lab}</label>";
	}
	echo "</span></div>\n";
	echo "</section>\n";
}

$newHidden = ($sel === '__new') ? '' : ' hidden';
echo "<section class='rkrk-admin-card' data-pilot-panel='__new'{$newHidden}>\n";
echo "<h2>PV für neues Kürzel</h2>\n";
echo "<div class='rkrk-admin-field'><span>PV zuweisen</span><span class='rkrk-sels rkrk-reg-pills'>";
foreach ($PersonenDaten as $pv => $pdata) {
	$pvH = rkrk_admin_h($pv);
	$lab = rkrk_admin_h(rkrk_admin_pv_label($pv, is_array($pdata) ? $pdata : array()));
	echo "<label class='rkrk-sel rkrk-reg-pill'><input type='checkbox' name='new_pv[]' value='{$pvH}' data-role='new-pv'> {$lab}</label>";
}
echo "</span></div>\n";
echo "</section>\n";

echo "<h2>PV-Datensätze</h2>\n";
echo "<p class='rkrk-admin-muted'>Nur die dem gewählten Kürzel zugewiesenen Sätze. Foto/QR als Bild, Ausweis/STS als PDF — Vorschau nach Auswahl, Speichern übernimmt.</p>\n";
$selPvSet = ($sel !== '__new' && $sel !== '__none' && isset($OptionPilot[$sel]))
	? rkrk_admin_csv_set($OptionPilot[$sel]['PV'] ?? '')
	: array();
foreach ($PersonenDaten as $pv => $row) {
	$low = strtolower((string)$pv);
	if ($sel === '__none') {
		$visible = empty($assignedPv[$low]);
	} elseif ($sel === '__new') {
		$visible = false;
	} else {
		$visible = isset($selPvSet[$low]);
	}
	$echoPvCard($pv, $row, $visible);
}

$newPvHidden = ($sel === '__none') ? ' hidden' : '';
echo "<details class='rkrk-admin-card' id='rkrkAdminNewPv'{$newPvHidden}><summary>Neuen PV-Datensatz anlegen</summary>\n";
echo "<div class='rkrk-admin-grid'>";
echo "<label>PV-Schlüssel <input name='new_pv_key' placeholder='tc-mxmi'></label>";
foreach ($textFields as $field => $label) {
	echo "<label>" . rkrk_admin_h($label) . "<input type='text' name='personen[__new][" . rkrk_admin_h($field) . "]'></label>";
}
echo "<label><span>Status</span><span class='rkrk-sels rkrk-reg-pills'>";
rkrk_admin_echo_pill('checkbox', 'personen[__new][verfied]', '1', true, 'verifiziert');
echo "</span></label>";
echo "</div>";
echo "<div class='rkrk-admin-files'>\n";
foreach ($fileFields as $field => $meta) {
	rkrk_admin_echo_file_slot('__new', $field, $meta, '');
}
echo "</div></details>\n";

echo "<div class='rkrk-admin-actions'><button type='submit'>Speichern</button></div>\n";
echo "</div></div>\n";
echo "</form>\n</main>\n";
footer();
